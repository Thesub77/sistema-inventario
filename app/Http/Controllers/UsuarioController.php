<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = Usuario::with('rol')->get();

        return response()->json($usuarios);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_rol' => 'required|integer|exists:rol,rol_id',
            'nombre_apellido' => 'required|string|min:3|max:128',
            'nombre_usuario' => 'required|string|min:3|max:24|unique:usuario,nombre_usuario',
            'contrasenia_usuario' => 'required|string|min:6|max:256',
            'fecha_registro' => 'nullable|date',
            'estado' => 'required|integer|in:0,1',
        ], [
            'id_rol.required' => 'El rol es obligatorio.',
            'id_rol.integer' => 'El identificador del rol debe ser un número entero.',
            'id_rol.exists' => 'El rol seleccionado no existe en el sistema.',
            'nombre_apellido.required' => 'El nombre y apellido es obligatorio.',
            'nombre_apellido.min' => 'El nombre y apellido debe tener al menos 3 caracteres.',
            'nombre_apellido.max' => 'El nombre y apellido no puede superar los 128 caracteres.',
            'nombre_usuario.required' => 'El nombre de usuario es obligatorio.',
            'nombre_usuario.min' => 'El nombre de usuario debe tener al menos 3 caracteres.',
            'nombre_usuario.max' => 'El nombre de usuario no puede superar los 24 caracteres.',
            'nombre_usuario.unique' => 'Este nombre de usuario ya está registrado, elija otro.',
            'contrasenia_usuario.required' => 'La contraseña es obligatoria.',
            'contrasenia_usuario.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'contrasenia_usuario.max' => 'La contraseña no puede superar los 256 caracteres.',
            'fecha_registro.date' => 'La fecha de registro debe tener un formato de fecha válido.',
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser 1 (Activo) o 0 (Inactivo).',
        ]);

        if (empty($validated['fecha_registro'])) {
            $validated['fecha_registro'] = now()->toDateString();
        }

        $validated['contrasenia_usuario'] = Hash::make($validated['contrasenia_usuario']);

        $usuario = Usuario::create($validated);

        return response()->json($usuario, 201);
    }

    public function show($id)
    {
        $usuario = Usuario::with(['rol', 'ventas', 'caja_operaciones', 'bitacoras'])->findOrFail($id);

        return response()->json($usuario);
    }

    public function update(Request $request, $id)
    {
        $usuario = Usuario::with('rol')->findOrFail($id);

        $validated = $request->validate([
            'id_rol' => 'sometimes|required|integer|exists:rol,rol_id',
            'nombre_apellido' => 'sometimes|required|string|min:3|max:128',
            'nombre_usuario' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:24',
                Rule::unique('usuario', 'nombre_usuario')->ignore($usuario->usuario_id, 'usuario_id'),
            ],
            'contrasenia_usuario' => 'nullable|string|min:6|max:256',
            'fecha_registro' => 'sometimes|date',
            'estado' => 'sometimes|required|integer|in:0,1',
        ], [
            'id_rol.required' => 'El rol es obligatorio si se proporciona.',
            'id_rol.integer' => 'El identificador del rol debe ser un número entero.',
            'id_rol.exists' => 'El rol seleccionado no existe en el sistema.',
            'nombre_apellido.required' => 'El nombre y apellido no puede estar vacío.',
            'nombre_apellido.min' => 'El nombre y apellido debe tener al menos 3 caracteres.',
            'nombre_apellido.max' => 'El nombre y apellido no puede superar los 128 caracteres.',
            'nombre_usuario.required' => 'El nombre de usuario no puede estar vacío.',
            'nombre_usuario.min' => 'El nombre de usuario debe tener al menos 3 caracteres.',
            'nombre_usuario.max' => 'El nombre de usuario no puede superar los 24 caracteres.',
            'nombre_usuario.unique' => 'Este nombre de usuario ya está registrado por otro usuario.',
            'contrasenia_usuario.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'contrasenia_usuario.max' => 'La contraseña no puede superar los 256 caracteres.',
            'fecha_registro.date' => 'La fecha de registro debe tener un formato de fecha válido.',
            'estado.required' => 'El estado es obligatorio si se proporciona.',
            'estado.in' => 'El estado debe ser 1 (Activo) o 0 (Inactivo).',
        ]);

        $esMismoUsuario = Auth::check() && (int) Auth::id() === (int) $usuario->usuario_id;
        $esAdmin = $usuario->rol && ($usuario->rol->nombre_rol === 'Administrador' || in_array('*', $usuario->rol->permisos ?? [], true));

        // 1. Blindaje: Impedir cambio de rol propio o degradar al último administrador
        if (array_key_exists('id_rol', $validated) && (int) $validated['id_rol'] !== (int) $usuario->id_rol) {
            if ($esMismoUsuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes modificar tu propio rol de usuario.',
                ], 403);
            }

            if ($esAdmin) {
                $activeAdmins = Usuario::whereHas('rol', fn ($q) => $q->where('nombre_rol', 'Administrador'))
                    ->where('estado', 1)
                    ->count();

                if ($activeAdmins <= 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No puedes cambiar el rol al único Administrador activo del sistema.',
                    ], 403);
                }
            }
        }

        // 2. Blindaje: Impedir auto-bloqueo o auto-desactivación y proteger cuenta administrador
        if (array_key_exists('estado', $validated) && (int) $validated['estado'] !== (int) $usuario->estado) {
            if ($esMismoUsuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes bloquear o cambiar el estado de tu propia cuenta en sesión.',
                ], 403);
            }

            if ((int) $validated['estado'] === 0 && $esAdmin) {
                $activeAdmins = Usuario::whereHas('rol', fn ($q) => $q->where('nombre_rol', 'Administrador'))
                    ->where('estado', 1)
                    ->count();

                if ($activeAdmins <= 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede desactivar o bloquear al único Administrador activo del sistema.',
                    ], 403);
                }
            }
        }

        // Si se desactiva el usuario, revocar sus tokens de sesión
        if (array_key_exists('estado', $validated) && (int) $validated['estado'] === 0 && (int) $usuario->estado === 1) {
            $usuario->tokens()->delete();
        }

        // Si se reactiva / desbloquea el usuario (de 0 a 1), registrar en bitácora para resetear contador de intentos
        if (array_key_exists('estado', $validated) && (int) $validated['estado'] === 1 && (int) $usuario->estado === 0) {
            Bitacora::create([
                'id_usuario' => $usuario->usuario_id,
                'accion_bitacora' => 'USUARIO_DESBLOQUEADO',
                'descripcion_bitacora' => "Cuenta de {$usuario->nombre_usuario} reactivada/desbloqueada por el administrador.",
                'fecha_hora_bitacora' => now(),
                'estado' => 1,
            ]);
        }

        if (! empty($validated['contrasenia_usuario'])) {
            $validated['contrasenia_usuario'] = Hash::make($validated['contrasenia_usuario']);
            Bitacora::create([
                'id_usuario' => $usuario->usuario_id,
                'accion_bitacora' => 'RESTABLECER_CONTRASENIA',
                'descripcion_bitacora' => "Contraseña del usuario {$usuario->nombre_usuario} actualizada por el administrador.",
                'fecha_hora_bitacora' => now(),
                'estado' => 1,
            ]);
        } else {
            unset($validated['contrasenia_usuario']);
        }

        $usuario->update($validated);

        return response()->json($usuario->load('rol'));
    }

    public function destroy($id)
    {
        $usuario = Usuario::with('rol')->findOrFail($id);

        if (Auth::check() && (int) Auth::id() === (int) $usuario->usuario_id) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes desactivar tu propio usuario en sesión.',
            ], 403);
        }

        $esAdmin = $usuario->rol && ($usuario->rol->nombre_rol === 'Administrador' || in_array('*', $usuario->rol->permisos ?? [], true));
        if ($esAdmin && (int) $usuario->estado === 1) {
            $activeAdmins = Usuario::whereHas('rol', fn ($q) => $q->where('nombre_rol', 'Administrador'))
                ->where('estado', 1)
                ->count();

            if ($activeAdmins <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede desactivar al único Administrador activo del sistema.',
                ], 403);
            }
        }

        $usuario->update(['estado' => 0]);
        $usuario->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Usuario desactivado correctamente',
            'usuario' => $usuario,
        ]);
    }
}
