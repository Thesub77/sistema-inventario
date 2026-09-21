<?php

namespace App\Http\Controllers;

use App\Models\Venta_detalle;
use Illuminate\Http\Request;

class VentaDetalleController extends Controller
{
    public function index()
    {
        return response()->json(Venta_detalle::with(['venta', 'producto'])->get());
    }

    public function store(Request $request)
    {
        return response()->json(['message' => 'Los detalles se registran al confirmar la venta mediante POST /api/ventas.'], 405);
    }

    public function show($id)
    {
        return response()->json(Venta_detalle::with(['venta', 'producto'])->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        Venta_detalle::findOrFail($id);

        return response()->json(['message' => 'Una venta confirmada no se modifica. Anule la venta y registre una nueva.'], 405);
    }

    public function destroy($id)
    {
        Venta_detalle::findOrFail($id);

        return response()->json(['message' => 'Los detalles se conservan como historial. Anule la venta completa.'], 405);
    }
}
