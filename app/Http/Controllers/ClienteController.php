<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;

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
            'codigo_cliente' => 'nullable|string|max:32',
            'nombre_apellido_cliente' => 'required|string|max:128',
            'telefono_cliente' => 'nullable|string|max:16',
            'estado' => 'required|integer',
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
            'codigo_cliente' => 'nullable|string|max:32',
            'nombre_apellido_cliente' => 'sometimes|string|max:128',
            'telefono_cliente' => 'nullable|string|max:16',
            'estado' => 'sometimes|integer',
        ]);

        $cliente->update($validated);
        return response()->json($cliente);
    }

    public function destroy($id)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->delete();
        return response()->json(['message' => 'Cliente eliminado correctamente']);
    }
}
