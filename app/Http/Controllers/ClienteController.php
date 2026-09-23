<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    public function index()
    {
        $clientes = Cliente::all();
        return response()->json($clientes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo_cliente' => 'nullable|string|max:32|unique:cliente,codigo_cliente',
            'nombre_apellido_cliente' => 'required|string|min:3|max:128',
            'telefono_cliente' => 'nullable|string|max:16',
            'estado' => 'required|integer|in:0,1',
        ], [
            'codigo_cliente.unique' => 'El código de cliente ya está registrado.',
            'nombre_apellido_cliente.required' => 'El nombre del cliente es obligatorio.',
            'nombre_apellido_cliente.min' => 'El nombre del cliente debe tener al menos 3 caracteres.',
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
            'telefono_cliente' => 'nullable|string|max:16',
            'estado' => 'sometimes|required|integer|in:0,1',
        ], [
            'codigo_cliente.unique' => 'El código de cliente ya está en uso por otro cliente.',
            'nombre_apellido_cliente.min' => 'El nombre del cliente debe tener al menos 3 caracteres.',
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
