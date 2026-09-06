<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use Illuminate\Http\Request;

class RolController extends Controller
{
    public function index()
    {
        $roles = Rol::all();
        return response()->json($roles);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre_rol' => 'required|string|max:64',
            'descripcion_rol' => 'nullable|string|max:64',
            'estado' => 'required|integer',
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
            'nombre_rol' => 'sometimes|string|max:64',
            'descripcion_rol' => 'nullable|string|max:64',
            'estado' => 'sometimes|integer',
        ]);

        $rol->update($validated);
        return response()->json($rol);
    }

    public function destroy($id)
    {
        $rol = Rol::findOrFail($id);
        $rol->delete();
        return response()->json(['message' => 'Rol eliminado correctamente']);
    }
}
