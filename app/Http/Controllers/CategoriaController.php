<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'codigo_categoria' => 'nullable|string|max:16|unique:categoria,codigo_categoria',
            'nombre_categoria' => 'required|string|max:24|unique:categoria,nombre_categoria',
            'descripcion_categoria' => 'nullable|string|max:64',
            'estado' => 'required|integer|in:0,1',
        ], [
            'codigo_categoria.unique' => 'El código de categoría ya está en uso.',
            'nombre_categoria.required' => 'El nombre de la categoría es obligatorio.',
            'nombre_categoria.unique' => 'Ya existe una categoría con este nombre.',
            'estado.in' => 'El estado debe ser 1 (Activo) o 0 (Inactivo).',
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
            'codigo_categoria' => [
                'nullable',
                'string',
                'max:16',
                Rule::unique('categoria', 'codigo_categoria')->ignore($categoria->categoria_id, 'categoria_id'),
            ],
            'nombre_categoria' => [
                'sometimes',
                'required',
                'string',
                'max:24',
                Rule::unique('categoria', 'nombre_categoria')->ignore($categoria->categoria_id, 'categoria_id'),
            ],
            'descripcion_categoria' => 'nullable|string|max:64',
            'estado' => 'sometimes|required|integer|in:0,1',
        ], [
            'codigo_categoria.unique' => 'El código de categoría ya está en uso por otra categoría.',
            'nombre_categoria.unique' => 'Ya existe una categoría con este nombre.',
            'estado.in' => 'El estado debe ser 1 (Activo) o 0 (Inactivo).',
        ]);

        $categoria->update($validated);
        return response()->json($categoria);
    }

    public function destroy($id)
    {
        $categoria = Categoria::findOrFail($id);

        if ($categoria->productos()->where('estado', 1)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede desactivar la categoría porque tiene productos activos asociados. Reasigne o desactive primero sus productos.',
            ], 409);
        }

        $categoria->update(['estado' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Categoría desactivada correctamente',
            'categoria' => $categoria,
        ]);
    }
}
