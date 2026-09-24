<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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

        if (! $usuario || ! Hash::check($validated['contrasenia_usuario'], $usuario->contrasenia_usuario)) {
            if ($usuario) {
                Bitacora::create([
                    'id_usuario' => $usuario->usuario_id,
                    'accion_bitacora' => 'LOGIN_FALLIDO',
                    'descripcion_bitacora' => "Intento fallido de inicio de sesión para el usuario: {$usuario->nombre_usuario}",
                    'fecha_hora_bitacora' => now(),
                    'estado' => 1,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas. Verifique su usuario y contraseña.',
            ], 401);
        }

        if ((int) $usuario->estado !== 1) {
            Bitacora::create([
                'id_usuario' => $usuario->usuario_id,
                'accion_bitacora' => 'LOGIN_BLOQUEADO',
                'descripcion_bitacora' => "Intento de inicio de sesión con cuenta inactiva: {$usuario->nombre_usuario}",
                'fecha_hora_bitacora' => now(),
                'estado' => 1,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Su cuenta se encuentra inactiva. Contacte al administrador del sistema.',
            ], 403);
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
                \Illuminate\Support\Facades\Log::warning('Error registrando bitácora logout: ' . $e->getMessage());
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
            ],
        ]);
    }
}
