<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use Illuminate\Http\Request;

class BitacoraController extends Controller
{
    public function index()
    {
        $bitacoras = Bitacora::with('usuario')->get();
        return response()->json($bitacoras);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_usuario' => 'required|exists:usuario,usuario_id',
            'accion_bitacora' => 'required|string|max:24',
            'descripcion_bitacora' => 'required|string|max:128',
            'fecha_hora_bitacora' => 'required|date',
            'estado' => 'required|integer',
        ]);

        $bitacora = Bitacora::create($validated);
        return response()->json($bitacora, 201);
    }

    public function show($id)
    {
        $bitacora = Bitacora::with('usuario')->findOrFail($id);
        return response()->json($bitacora);
    }

    public function update(Request $request, $id)
    {
        $bitacora = Bitacora::findOrFail($id);
        $validated = $request->validate([
            'id_usuario' => 'sometimes|exists:usuario,usuario_id',
            'accion_bitacora' => 'sometimes|string|max:24',
            'descripcion_bitacora' => 'sometimes|string|max:128',
            'fecha_hora_bitacora' => 'sometimes|date',
            'estado' => 'sometimes|integer',
        ]);

        $bitacora->update($validated);
        return response()->json($bitacora);
    }

    public function destroy($id)
    {
        $bitacora = Bitacora::findOrFail($id);
        $bitacora->delete();
        return response()->json(['message' => 'Bitácora eliminada correctamente']);
    }
}
