<?php

namespace App\Http\Controllers;

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

        // Evitar desactivar el rol Administrador
        if ($rol->nombre_rol === 'Administrador' && isset($validated['estado']) && (int) $validated['estado'] === 0) {
            return response()->json([
                'success' => false,
                'message' => 'El rol Administrador no puede ser desactivado.',
            ], 403);
        }

        $rol->update($validated);

        return response()->json($rol);
    }

    public function destroy($id)
    {
        $rol = Rol::findOrFail($id);

        if ($rol->nombre_rol === 'Administrador') {
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
