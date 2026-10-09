<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RolController extends Controller
{
    public function index()
    {
        $roles = Rol::withCount('usuarios')->get();

        return response()->json($roles);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre_rol' => 'required|string|max:64|unique:rol,nombre_rol',
            'descripcion_rol' => 'nullable|string|max:64',
            'permisos' => 'nullable|array',
            'permisos.*' => 'string|max:64',
            'estado' => 'required|integer|in:0,1',
        ], [
            'nombre_rol.required' => 'El nombre del rol es obligatorio.',
            'nombre_rol.unique' => 'Ya existe un rol con este nombre.',
            'permisos.array' => 'Los permisos deben ser una lista de claves válidas.',
        ]);

        $rol = Rol::create($validated);

        return response()->json($rol, 201);
    }

    public function show($id)
    {
        $rol = Rol::with('usuarios')->findOrFail($id);

        return response()->json($rol);
    }

    public function update(Request $request, $id)
    {
        $rol = Rol::findOrFail($id);

        // Blindaje: El rol Administrador es inmutable y no puede ser modificado ni desactivado
        if ($rol->nombre_rol === 'Administrador' || in_array('*', $rol->permisos ?? [], true)) {
            return response()->json([
                'success' => false,
                'message' => 'El rol Administrador es inmutable y no puede ser modificado ni desactivado.',
            ], 403);
        }

        $validated = $request->validate([
            'nombre_rol' => [
                'sometimes',
                'required',
                'string',
                'max:64',
                Rule::unique('rol', 'nombre_rol')->ignore($rol->rol_id, 'rol_id'),
            ],
            'descripcion_rol' => 'nullable|string|max:64',
            'permisos' => 'nullable|array',
            'permisos.*' => 'string|max:64',
            'estado' => 'sometimes|integer|in:0,1',
        ]);

        // Si se desactiva el rol (estado = 0), bloquear automáticamente a todos los usuarios asignados y revocar sus sesiones
        if (isset($validated['estado']) && (int) $validated['estado'] === 0 && (int) $rol->estado === 1) {
            $usuariosAfectados = $rol->usuarios()->where('estado', 1)->get();
            foreach ($usuariosAfectados as $u) {
                $u->update(['bloqueado' => 1]);
                $u->tokens()->delete();

                Bitacora::create([
                    'id_usuario' => $u->usuario_id,
                    'accion_bitacora' => 'USUARIO_BLOQUEADO',
                    'descripcion_bitacora' => "Usuario {$u->nombre_usuario} bloqueado automáticamente por desactivación del rol {$rol->nombre_rol}.",
                    'fecha_hora_bitacora' => now(),
                    'estado' => 1,
                ]);
            }
        }

        $rol->update($validated);

        return response()->json($rol);
    }

    public function destroy($id)
    {
        $rol = Rol::findOrFail($id);

        if ($rol->nombre_rol === 'Administrador' || in_array('*', $rol->permisos ?? [], true)) {
            return response()->json([
                'success' => false,
                'message' => 'El rol Administrador no puede ser eliminado.',
            ], 403);
        }

        if ($rol->usuarios()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el rol porque tiene usuarios asignados. Reasigne los usuarios antes de eliminarlo.',
            ], 409);
        }

        $rol->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rol eliminado correctamente.',
        ]);
    }
}
