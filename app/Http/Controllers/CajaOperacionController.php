<?php

namespace App\Http\Controllers;

use App\Models\Caja_operacion;
use Illuminate\Http\Request;

class CajaOperacionController extends Controller
{
    public function index()
    {
        $operaciones = Caja_operacion::with(['caja', 'usuario'])->get();
        return response()->json($operaciones);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_caja' => 'required|exists:caja,caja_id',
            'id_usuario' => 'required|exists:usuario,usuario_id',
            'fecha_hora_apertura' => 'nullable|date',
            'monto_apertura' => 'nullable|numeric|min:0',
            'monto_cierre' => 'nullable|numeric|min:0',
            'fecha_hora_cierre' => 'nullable|date',
            'estado' => 'required|integer',
        ]);

        $operacion = Caja_operacion::create($validated);
        return response()->json($operacion, 201);
    }

    public function show($id)
    {
        $operacion = Caja_operacion::with(['caja', 'usuario'])->findOrFail($id);
        return response()->json($operacion);
    }

    public function update(Request $request, $id)
    {
        $operacion = Caja_operacion::findOrFail($id);
        $validated = $request->validate([
            'id_caja' => 'sometimes|exists:caja,caja_id',
            'id_usuario' => 'sometimes|exists:usuario,usuario_id',
            'fecha_hora_apertura' => 'nullable|date',
            'monto_apertura' => 'nullable|numeric|min:0',
            'monto_cierre' => 'nullable|numeric|min:0',
            'fecha_hora_cierre' => 'nullable|date',
            'estado' => 'sometimes|integer',
        ]);

        $operacion->update($validated);
        return response()->json($operacion);
    }

    public function destroy($id)
    {
        $operacion = Caja_operacion::findOrFail($id);
        $operacion->delete();
        return response()->json(['message' => 'Operación de caja eliminada correctamente']);
    }
}
