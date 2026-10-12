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
        $roles = Rol::where('estado', 1)
            ->withCount(['usuarios' => function ($query) {
                $query->where('estado', 1);
            }])->get();

        return response()->json($roles);
    }

    public function store(Request $request)
    {
        $usuarioAutenticado = $request->user();
        if (! $usuarioAutenticado || ! $usuarioAutenticado->esAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Solo los administradores tienen autorización para crear nuevos roles.',
            ], 403);
        }

        $validated = $request->validate([
            'nombre_rol' => [
                'required',
                'string',
                'max:64',
                Rule::unique('rol', 'nombre_rol')->where(fn ($query) => $query->where('estado', 1)),
            ],
            'descripcion_rol' => 'nullable|string|max:64',
            'permisos' => 'nullable|array',
            'permisos.*' => 'string|max:64',
            'bloqueado' => 'sometimes|integer|in:0,1',
            'estado' => 'sometimes|integer|in:0,1',
        ], [
            'nombre_rol.required' => 'El nombre del rol es obligatorio.',
            'nombre_rol.unique' => 'Ya existe un rol con este nombre.',
            'permisos.array' => 'Los permisos deben ser una lista de claves válidas.',
        ]);

        $validated['estado'] = $validated['estado'] ?? 1;
        $validated['bloqueado'] = $validated['bloqueado'] ?? 0;

        $rol = Rol::create($validated);

        if ($usuarioAutenticado) {
            Bitacora::create([
                'id_usuario' => $usuarioAutenticado->usuario_id,
                'accion_bitacora' => 'ROL_CREADO',
                'descripcion_bitacora' => "Rol '{$rol->nombre_rol}' creado en el sistema.",
                'fecha_hora_bitacora' => now(),
                'estado' => 1,
            ]);
        }

        return response()->json($rol, 201);
    }

    public function show($id)
    {
        $rol = Rol::where('estado', 1)
            ->with(['usuarios' => function ($query) {
                $query->where('estado', 1);
            }])->findOrFail($id);

        return response()->json($rol);
    }

    public function update(Request $request, $id)
    {
        $usuarioAutenticado = $request->user();
        if (! $usuarioAutenticado || ! $usuarioAutenticado->esAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Solo los administradores tienen autorización para modificar roles.',
            ], 403);
        }

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
                Rule::unique('rol', 'nombre_rol')
                    ->ignore($rol->rol_id, 'rol_id')
                    ->where(fn ($query) => $query->where('estado', 1)),
            ],
            'descripcion_rol' => 'nullable|string|max:64',
            'permisos' => 'nullable|array',
            'permisos.*' => 'string|max:64',
            'bloqueado' => 'sometimes|integer|in:0,1',
            'estado' => 'sometimes|integer|in:0,1',
        ]);

        // Si se bloquea/desactiva el rol (bloqueado = 1), bloquear automáticamente a todos los usuarios asignados y revocar sus sesiones
        $estaBloqueando = (isset($validated['bloqueado']) && (int) $validated['bloqueado'] === 1 && (int) $rol->bloqueado === 0)
            || (isset($validated['estado']) && (int) $validated['estado'] === 0 && (int) $rol->estado === 1);

        if ($estaBloqueando) {
            $usuariosAfectados = $rol->usuarios()->where('estado', 1)->get();
            foreach ($usuariosAfectados as $u) {
                $u->update(['bloqueado' => 1]);
                $u->tokens()->delete();

                Bitacora::create([
                    'id_usuario' => $u->usuario_id,
                    'accion_bitacora' => 'USUARIO_BLOQUEADO',
                    'descripcion_bitacora' => "Usuario {$u->nombre_usuario} bloqueado automáticamente por desactivación o bloqueo del rol {$rol->nombre_rol}.",
                    'fecha_hora_bitacora' => now(),
                    'estado' => 1,
                ]);
            }
        }

        $rol->update($validated);

        if ($usuarioAutenticado) {
            Bitacora::create([
                'id_usuario' => $usuarioAutenticado->usuario_id,
                'accion_bitacora' => 'ROL_ACTUALIZADO',
                'descripcion_bitacora' => "Rol '{$rol->nombre_rol}' actualizado en el sistema.",
                'fecha_hora_bitacora' => now(),
                'estado' => 1,
            ]);
        }

        return response()->json($rol);
    }

    public function destroy($id)
    {
        $usuarioAutenticado = request()->user();
        if (! $usuarioAutenticado || ! $usuarioAutenticado->esAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Solo los administradores tienen autorización para eliminar roles.',
            ], 403);
        }

        $rol = Rol::findOrFail($id);

        if ($rol->nombre_rol === 'Administrador' || in_array('*', $rol->permisos ?? [], true)) {
            return response()->json([
                'success' => false,
                'message' => 'El rol Administrador no puede ser eliminado.',
            ], 403);
        }

        if ($rol->usuarios()->where('estado', 1)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el rol porque tiene usuarios asignados. Reasigne los usuarios antes de eliminarlo.',
            ], 409);
        }

        $rol->update(['estado' => 0]);

        if ($usuarioAutenticado) {
            Bitacora::create([
                'id_usuario' => $usuarioAutenticado->usuario_id,
                'accion_bitacora' => 'ROL_ELIMINADO',
                'descripcion_bitacora' => "Rol '{$rol->nombre_rol}' eliminado lógicamente del sistema.",
                'fecha_hora_bitacora' => now(),
                'estado' => 1,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rol eliminado correctamente.',
        ]);
    }
}
