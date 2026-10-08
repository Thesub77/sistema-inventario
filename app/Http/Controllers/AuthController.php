<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'nombre_usuario' => 'required|string',
            'contrasenia_usuario' => 'required|string',
        ], [
            'nombre_usuario.required' => 'El nombre de usuario es obligatorio.',
            'contrasenia_usuario.required' => 'La contraseña es obligatoria.',
        ]);

        $usuario = Usuario::with('rol')
            ->where('nombre_usuario', $validated['nombre_usuario'])
            ->first();

        // Si el usuario existe pero está eliminado lógicamente del sistema
        if ($usuario && (int) $usuario->estado !== 1) {
            Bitacora::create([
                'id_usuario' => $usuario->usuario_id,
                'accion_bitacora' => 'LOGIN_BLOQUEADO',
                'descripcion_bitacora' => "Intento de inicio de sesión con cuenta inactiva o dada de baja: {$usuario->nombre_usuario}",
                'fecha_hora_bitacora' => now(),
                'estado' => 1,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas o la cuenta ha sido dada de baja del sistema.',
            ], 403);
        }

        // Si el usuario existe y está activo, pero su acceso se encuentra bloqueado
        if ($usuario && (int) $usuario->bloqueado === 1) {
            Bitacora::create([
                'id_usuario' => $usuario->usuario_id,
                'accion_bitacora' => 'LOGIN_BLOQUEADO',
                'descripcion_bitacora' => "Intento de inicio de sesión con cuenta bloqueada: {$usuario->nombre_usuario}",
                'fecha_hora_bitacora' => now(),
                'estado' => 1,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Su cuenta se encuentra inactiva o bloqueada por seguridad. Contacte al administrador del sistema.',
            ], 403);
        }

        // Si el usuario no existe o la contraseña es incorrecta
        if (! $usuario || ! Hash::check($validated['contrasenia_usuario'], $usuario->contrasenia_usuario)) {
            if ($usuario) {
                // 1. Registrar el intento fallido actual
                $bitacoraFallida = Bitacora::create([
                    'id_usuario' => $usuario->usuario_id,
                    'accion_bitacora' => 'LOGIN_FALLIDO',
                    'descripcion_bitacora' => "Intento fallido de inicio de sesión para el usuario: {$usuario->nombre_usuario}",
                    'fecha_hora_bitacora' => now(),
                    'estado' => 1,
                ]);

                // 2. Buscar el último evento que haya reiniciado el contador de intentos (login exitoso, desbloqueo, cambio de clave o bloqueo previo)
                $ultimoResetId = Bitacora::where('id_usuario', $usuario->usuario_id)
                    ->whereIn('accion_bitacora', [
                        'LOGIN_EXITOSO',
                        'USUARIO_BLOQUEADO',
                        'USUARIO_DESBLOQUEADO',
                        'USUARIO_REACTIVADO',
                        'RESTABLECER_CONTRASENIA',
                    ])
                    ->where('id_bitacora', '<', $bitacoraFallida->id_bitacora)
                    ->max('id_bitacora');

                // 3. Contar intentos fallidos posteriores al último reinicio
                $intentosFallidosQuery = Bitacora::where('id_usuario', $usuario->usuario_id)
                    ->where('accion_bitacora', 'LOGIN_FALLIDO');

                if ($ultimoResetId) {
                    $intentosFallidosQuery->where('id_bitacora', '>', $ultimoResetId);
                }

                $intentosFallidos = $intentosFallidosQuery->count();

                $esAdmin = $usuario->rol && ($usuario->rol->nombre_rol === 'Administrador' || in_array('*', $usuario->rol->permisos ?? [], true));

                // 4. Bloqueo automático al tercer intento fallido consecutivo (para usuarios no administradores)
                if (! $esAdmin && $intentosFallidos >= 3) {
                    $usuario->update(['bloqueado' => 1]);
                    $usuario->tokens()->delete();

                    Bitacora::create([
                        'id_usuario' => $usuario->usuario_id,
                        'accion_bitacora' => 'USUARIO_BLOQUEADO',
                        'descripcion_bitacora' => "Usuario {$usuario->nombre_usuario} bloqueado automáticamente por acumular {$intentosFallidos} intentos fallidos de contraseña.",
                        'fecha_hora_bitacora' => now(),
                        'estado' => 1,
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Su cuenta ha sido bloqueada por exceder el límite de 3 intentos fallidos de contraseña. Contacte al administrador para reactivarla.',
                    ], 403);
                }

                $intentosRestantes = max(0, 3 - $intentosFallidos);

                $mensaje = $esAdmin
                    ? 'Credenciales incorrectas. Verifique su usuario y contraseña.'
                    : "Credenciales incorrectas. Le quedan {$intentosRestantes} intento(s) antes del bloqueo de cuenta.";

                return response()->json([
                    'success' => false,
                    'message' => $mensaje,
                    'intentos_restantes' => $intentosRestantes,
                ], 401);
            }

            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas. Verifique su usuario y contraseña.',
            ], 401);
        }

        $token = $usuario->createToken('auth-token')->plainTextToken;

        Bitacora::create([
            'id_usuario' => $usuario->usuario_id,
            'accion_bitacora' => 'LOGIN_EXITOSO',
            'descripcion_bitacora' => "Inicio de sesión exitoso del usuario: {$usuario->nombre_usuario}",
            'fecha_hora_bitacora' => now(),
            'estado' => 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión exitoso.',
            'token' => $token,
            'usuario' => [
                'usuario_id' => $usuario->usuario_id,
                'nombre_apellido' => $usuario->nombre_apellido,
                'nombre_usuario' => $usuario->nombre_usuario,
                'rol' => $usuario->rol?->nombre_rol,
                'permisos' => $usuario->rol?->permisos ?? [],
                'es_principal' => $usuario->esAdminPrincipal(),
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $usuario = $request->user();

        if ($usuario) {
            try {
                $usuario->currentAccessToken()?->delete();
            } catch (\Throwable $e) {
                // Continuar aunque ya no exista el token
            }

            try {
                Bitacora::create([
                    'id_usuario' => $usuario->usuario_id,
                    'accion_bitacora' => 'LOGOUT',
                    'descripcion_bitacora' => "Cierre de sesión del usuario: {$usuario->nombre_usuario}",
                    'fecha_hora_bitacora' => now(),
                    'estado' => 1,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Error registrando bitácora logout: '.$e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    public function me(Request $request)
    {
        $usuario = $request->user()->load('rol');

        return response()->json([
            'success' => true,
            'usuario' => [
                'usuario_id' => $usuario->usuario_id,
                'nombre_apellido' => $usuario->nombre_apellido,
                'nombre_usuario' => $usuario->nombre_usuario,
                'rol' => $usuario->rol?->nombre_rol,
                'permisos' => $usuario->rol?->permisos ?? [],
                'es_principal' => $usuario->esAdminPrincipal(),
            ],
        ]);
    }
}
