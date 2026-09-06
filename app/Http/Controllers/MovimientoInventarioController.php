<?php

namespace App\Http\Controllers;

use App\Models\Movimiento_inventario;
use Illuminate\Http\Request;

class MovimientoInventarioController extends Controller
{
    public function index()
    {
        $movimientos = Movimiento_inventario::with(['producto', 'usuario'])->get();
        return response()->json($movimientos);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_producto' => 'required|exists:producto,producto_id',
            'id_usuario' => 'required|exists:usuario,usuario_id',
            'tipo_movimiento' => 'required|string|max:24',
            'cantidad_movimimiento' => 'required|integer',
            'stock_anterior_producto' => 'required|integer',
            'stock_resultante_producto' => 'required|integer',
            'fecha_movimiento' => 'required|date',
            'estado' => 'required|integer',
        ]);

        $movimiento = Movimiento_inventario::create($validated);
        return response()->json($movimiento, 201);
    }

    public function show($id)
    {
        $movimiento = Movimiento_inventario::with(['producto', 'usuario'])->findOrFail($id);
        return response()->json($movimiento);
    }

    public function update(Request $request, $id)
    {
        $movimiento = Movimiento_inventario::findOrFail($id);
        $validated = $request->validate([
            'id_producto' => 'sometimes|exists:producto,producto_id',
            'id_usuario' => 'sometimes|exists:usuario,usuario_id',
            'tipo_movimiento' => 'sometimes|string|max:24',
            'cantidad_movimimiento' => 'sometimes|integer',
            'stock_anterior_producto' => 'sometimes|integer',
            'stock_resultante_producto' => 'sometimes|integer',
            'fecha_movimiento' => 'sometimes|date',
            'estado' => 'sometimes|integer',
        ]);

        $movimiento->update($validated);
        return response()->json($movimiento);
    }

    public function destroy($id)
    {
        $movimiento = Movimiento_inventario::findOrFail($id);
        $movimiento->delete();
        return response()->json(['message' => 'Movimiento de inventario eliminado correctamente']);
    }
}
