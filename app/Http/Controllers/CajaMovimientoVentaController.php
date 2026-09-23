<?php

namespace App\Http\Controllers;

use App\Models\Caja_movimiento_venta;
use App\Services\CajaService;
use Illuminate\Http\Request;

class CajaMovimientoVentaController extends Controller
{
    public function index(Request $request)
    {
        app(CajaService::class)->autorizar($request->user());
        $datos = $request->validate([
            'id_caja' => 'sometimes|integer|exists:caja,caja_id',
            'id_caja_operacion' => 'sometimes|integer|exists:caja_operacion,caja_operacion_id',
            'estado' => 'sometimes|integer|in:0,1',
        ]);
        $query = Caja_movimiento_venta::with(['caja', 'venta']);
        foreach ($datos as $campo => $valor) {
            $query->where($campo, $valor);
        }

        return response()->json($query->orderByDesc('caja_movimiento_venta_id')->get());
    }

    public function show(Request $request, $id)
    {
        app(CajaService::class)->autorizar($request->user());

        return response()->json(Caja_movimiento_venta::with(['caja', 'venta'])->findOrFail($id));
    }

    public function store(Request $request)
    {
        return $this->historial();
    }

    public function update(Request $request, $id)
    {
        Caja_movimiento_venta::findOrFail($id);

        return $this->historial();
    }

    public function destroy($id)
    {
        Caja_movimiento_venta::findOrFail($id);

        return $this->historial();
    }

    private function historial()
    {
        return response()->json(['message' => 'Los movimientos se generan al vender o anular; no se modifican ni eliminan directamente.'], 405);
    }
}
