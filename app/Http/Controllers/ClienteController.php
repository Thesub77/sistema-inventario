<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'buscar' => 'sometimes|required|string|max:128',
            'estado' => 'sometimes|required|integer|in:0,1',
            'por_pagina' => 'sometimes|required|integer|min:1|max:100',
            'page' => 'sometimes|required|integer|min:1',
        ]);

        $query = Cliente::orderBy('cliente_id');

        if (isset($validated['buscar'])) {
            $query->where(function ($q) use ($validated) {
                $q->whereLike('nombre_apellido_cliente', '%'.$validated['buscar'].'%')
                    ->orWhereLike('codigo_cliente', '%'.$validated['buscar'].'%')
                    ->orWhereLike('telefono_cliente', '%'.$validated['buscar'].'%');
            });
        }

        if (array_key_exists('estado', $validated)) {
            $query->where('estado', $validated['estado']);
        }

        if (isset($validated['por_pagina'])) {
            return response()->json($query->paginate((int) $validated['por_pagina'])->withQueryString());
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo_cliente' => 'nullable|string|max:32|unique:cliente,codigo_cliente',
            'nombre_apellido_cliente' => 'required|string|min:3|max:128',
            'telefono_cliente' => ['nullable', 'string', 'max:16', 'regex:/^[0-9+\-\s()]{7,16}$/'],
            'estado' => 'required|integer|in:0,1',
        ], [
            'codigo_cliente.unique' => 'El código de cliente ya está registrado.',
            'nombre_apellido_cliente.required' => 'El nombre del cliente es obligatorio.',
            'nombre_apellido_cliente.min' => 'El nombre del cliente debe tener al menos 3 caracteres.',
            'telefono_cliente.regex' => 'El formato del teléfono es inválido. Debe contener entre 7 y 16 dígitos o caracteres permitidos (+, -, espacios o paréntesis).',
            'estado.in' => 'El estado debe ser 1 (Activo) o 0 (Inactivo).',
        ]);

        $cliente = Cliente::create($validated);
        return response()->json($cliente, 201);
    }

    public function show($id)
    {
        $cliente = Cliente::with('ventas')->findOrFail($id);
        return response()->json($cliente);
    }

    public function update(Request $request, $id)
    {
        $cliente = Cliente::findOrFail($id);
        $validated = $request->validate([
            'codigo_cliente' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('cliente', 'codigo_cliente')->ignore($cliente->cliente_id, 'cliente_id'),
            ],
            'nombre_apellido_cliente' => 'sometimes|required|string|min:3|max:128',
            'telefono_cliente' => ['nullable', 'string', 'max:16', 'regex:/^[0-9+\-\s()]{7,16}$/'],
            'estado' => 'sometimes|required|integer|in:0,1',
        ], [
            'codigo_cliente.unique' => 'El código de cliente ya está en uso por otro cliente.',
            'nombre_apellido_cliente.min' => 'El nombre del cliente debe tener al menos 3 caracteres.',
            'telefono_cliente.regex' => 'El formato del teléfono es inválido. Debe contener entre 7 y 16 dígitos o caracteres permitidos (+, -, espacios o paréntesis).',
            'estado.in' => 'El estado debe ser 1 (Activo) o 0 (Inactivo).',
        ]);

        $cliente->update($validated);
        return response()->json($cliente);
    }

    public function destroy($id)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->update(['estado' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Cliente desactivado correctamente',
            'cliente' => $cliente,
        ]);
    }
}
