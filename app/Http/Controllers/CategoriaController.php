<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoriaController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'buscar' => 'sometimes|required|string|max:64',
            'estado' => 'sometimes|required|integer|in:0,1',
            'por_pagina' => 'sometimes|required|integer|min:1|max:100',
            'page' => 'sometimes|required|integer|min:1',
        ]);

        $query = Categoria::orderBy('categoria_id');

        if (isset($validated['buscar'])) {
            $query->where(function ($q) use ($validated) {
                $q->whereLike('nombre_categoria', '%'.$validated['buscar'].'%')
                    ->orWhereLike('codigo_categoria', '%'.$validated['buscar'].'%');
            });
        }

        if (array_key_exists('estado', $validated)) {
            $query->where('estado', $validated['estado']);
        } else {
            $query->where('estado', 1);
        }

        if (isset($validated['por_pagina'])) {
            return response()->json($query->paginate((int) $validated['por_pagina'])->withQueryString());
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo_categoria' => [
                'nullable',
                'string',
                'max:16',
                'regex:/^[a-zA-Z0-9\-]+$/',
                Rule::unique('categoria', 'codigo_categoria')->where(fn ($query) => $query->where('estado', 1)),
            ],
            'nombre_categoria' => [
                'required',
                'string',
                'max:24',
                Rule::unique('categoria', 'nombre_categoria')->where(fn ($query) => $query->where('estado', 1)),
            ],
            'descripcion_categoria' => 'nullable|string|max:64',
            'estado' => 'sometimes|integer|in:0,1',
        ], [
            'codigo_categoria.unique' => 'El código de categoría ya está en uso.',
            'codigo_categoria.regex' => 'El código de categoría solo debe contener letras, números y guiones.',
            'nombre_categoria.required' => 'El nombre de la categoría es obligatorio.',
            'nombre_categoria.unique' => 'Ya existe una categoría con este nombre.',
            'estado.in' => 'El estado debe ser 1 (Activo) o 0 (Inactivo).',
        ]);

        $validated['estado'] = $validated['estado'] ?? 1;

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
                'regex:/^[a-zA-Z0-9\-]+$/',
                Rule::unique('categoria', 'codigo_categoria')
                    ->ignore($categoria->categoria_id, 'categoria_id')
                    ->where(fn ($query) => $query->where('estado', 1)),
            ],
            'nombre_categoria' => [
                'sometimes',
                'required',
                'string',
                'max:24',
                Rule::unique('categoria', 'nombre_categoria')
                    ->ignore($categoria->categoria_id, 'categoria_id')
                    ->where(fn ($query) => $query->where('estado', 1)),
            ],
            'descripcion_categoria' => 'nullable|string|max:64',
            'estado' => 'sometimes|required|integer|in:0,1',
        ], [
            'codigo_categoria.unique' => 'El código de categoría ya está en uso por otra categoría.',
            'codigo_categoria.regex' => 'El código de categoría solo debe contener letras, números y guiones.',
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
                'message' => 'No se puede eliminar la categoría porque tiene productos activos asociados. Reasigne o elimine primero sus productos.',
            ], 409);
        }

        $categoria->update(['estado' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Categoría eliminada correctamente',
            'categoria' => $categoria,
        ]);
    }
}
