<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index()
    {
        $categorias = Categoria::all();
        return response()->json($categorias);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo_categoria' => 'nullable|string|max:16',
            'nombre_categoria' => 'required|string|max:24',
            'descripcion_categoria' => 'nullable|string|max:64',
            'estado' => 'required|integer',
        ]);

        $categoria = Categoria::create($validated);
        return response()->json($categoria, 201);
    }

    public function show($id)
    {
        $categoria = Categoria::with('productos')->findOrFail($id);
        return response()->json($categoria);
    }

    public function update(Request $request, $id)
    {
        $categoria = Categoria::findOrFail($id);
        $validated = $request->validate([
            'codigo_categoria' => 'nullable|string|max:16',
            'nombre_categoria' => 'sometimes|string|max:24',
            'descripcion_categoria' => 'nullable|string|max:64',
            'estado' => 'sometimes|integer',
        ]);

        $categoria->update($validated);
        return response()->json($categoria);
    }

    public function destroy($id)
    {
        $categoria = Categoria::findOrFail($id);
        $categoria->delete();
        return response()->json(['message' => 'Categoría eliminada correctamente']);
    }
}
