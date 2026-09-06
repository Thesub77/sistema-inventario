<?php

namespace App\Http\Controllers;

use App\Models\Caja_movimiento_venta;
use Illuminate\Http\Request;

class CajaMovimientoVentaController extends Controller
{
    public function index()
    {
        $movimientos = Caja_movimiento_venta::with(['caja', 'venta'])->get();
        return response()->json($movimientos);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_caja' => 'required|exists:caja,caja_id',
            'id_venta' => 'nullable|exists:venta,venta_id',
            'monto_movimiento' => 'required|numeric',
            'fecha_hora_movimiento' => 'required|date',
            'estado' => 'required|integer',
        ]);

        $movimiento = Caja_movimiento_venta::create($validated);
        return response()->json($movimiento, 201);
    }

    public function show($id)
    {
        $movimiento = Caja_movimiento_venta::with(['caja', 'venta'])->findOrFail($id);
        return response()->json($movimiento);
    }

    public function update(Request $request, $id)
    {
        $movimiento = Caja_movimiento_venta::findOrFail($id);
        $validated = $request->validate([
            'id_caja' => 'sometimes|exists:caja,caja_id',
            'id_venta' => 'nullable|exists:venta,venta_id',
            'monto_movimiento' => 'sometimes|numeric',
            'fecha_hora_movimiento' => 'sometimes|date',
            'estado' => 'sometimes|integer',
        ]);

        $movimiento->update($validated);
        return response()->json($movimiento);
    }

    public function destroy($id)
    {
        $movimiento = Caja_movimiento_venta::findOrFail($id);
        $movimiento->delete();
        return response()->json(['message' => 'Movimiento de caja eliminado correctamente']);
    }
}
