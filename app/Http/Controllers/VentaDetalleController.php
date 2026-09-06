<?php

namespace App\Http\Controllers;

use App\Models\Venta_detalle;
use Illuminate\Http\Request;

class VentaDetalleController extends Controller
{
    public function index()
    {
        $detalles = Venta_detalle::with(['venta', 'producto'])->get();
        return response()->json($detalles);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_venta' => 'required|exists:venta,venta_id',
            'id_producto' => 'required|exists:producto,producto_id',
            'cantidad' => 'required|integer|min:1',
            'subtotal_venta_detalle' => 'required|numeric|min:0',
            'precio_unitario' => 'required|numeric|min:0',
            'estado' => 'required|integer',
        ]);

        $detalle = Venta_detalle::create($validated);
        return response()->json($detalle, 201);
    }

    public function show($id)
    {
        $detalle = Venta_detalle::with(['venta', 'producto'])->findOrFail($id);
        return response()->json($detalle);
    }

    public function update(Request $request, $id)
    {
        $detalle = Venta_detalle::findOrFail($id);
        $validated = $request->validate([
            'id_venta' => 'sometimes|exists:venta,venta_id',
            'id_producto' => 'sometimes|exists:producto,producto_id',
            'cantidad' => 'sometimes|integer|min:1',
            'subtotal_venta_detalle' => 'sometimes|numeric|min:0',
            'precio_unitario' => 'sometimes|numeric|min:0',
            'estado' => 'sometimes|integer',
        ]);

        $detalle->update($validated);
        return response()->json($detalle);
    }

    public function destroy($id)
    {
        $detalle = Venta_detalle::findOrFail($id);
        $detalle->delete();
        return response()->json(['message' => 'Detalle de venta eliminado correctamente']);
    }
}
