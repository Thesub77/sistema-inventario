<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use Illuminate\Http\Request;

class CajaController extends Controller
{
    public function index()
    {
        $cajas = Caja::all();
        return response()->json($cajas);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'descripcion_caja' => 'required|string|max:128',
            'tipo_apertura' => 'required|string|max:16',
            'estado_caja' => 'required|string|max:16',
            'estado' => 'required|integer',
        ]);

        $caja = Caja::create($validated);
        return response()->json($caja, 201);
    }

    public function show($id)
    {
        $caja = Caja::with(['caja_operaciones', 'caja_movimiento_ventas'])->findOrFail($id);
        return response()->json($caja);
    }

    public function update(Request $request, $id)
    {
        $caja = Caja::findOrFail($id);
        $validated = $request->validate([
            'descripcion_caja' => 'sometimes|string|max:128',
            'tipo_apertura' => 'sometimes|string|max:16',
            'estado_caja' => 'sometimes|string|max:16',
            'estado' => 'sometimes|integer',
        ]);

        $caja->update($validated);
        return response()->json($caja);
    }

    public function destroy($id)
    {
        $caja = Caja::findOrFail($id);
        $caja->delete();
        return response()->json(['message' => 'Caja eliminada correctamente']);
    }
}
