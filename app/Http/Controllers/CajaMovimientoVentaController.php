<?php

namespace App\Http\Controllers;

use App\Models\Caja_movimiento_venta;
use App\Services\CajaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Controlador de Movimientos Financieros de Caja por Ventas y Devoluciones.
 *
 * Expone la consulta auditada de los flujos de dinero en caja originados por ventas:
 * - Ingresos por ventas confirmadas.
 * - Egresos (montos negativos) por anulaciones y devoluciones.
 * - Filtros por caja, por turno operativo, o por movimientos históricos sin turno.
 *
 * Nota de Integridad: Este controlador es estrictamente de LECTURA.
 * Los movimientos de caja solo se generan mediante transacciones controladas
 * al emitir una venta (`VentaController@store`) o al procesar una anulación (`VentaController@destroy`).
 */
class CajaMovimientoVentaController extends Controller
{
    /**
     * Consulta el listado de movimientos financieros registrados en caja.
     *
     * Admite filtros opcionales:
     * - `id_caja`: Movimientos de una caja física determinada.
     * - `id_caja_operacion`: Movimientos pertenecientes a un turno específico.
     * - `sin_turno`: Booleano para consultar exclusivamente movimientos históricos que no tuvieron turno asignado.
     * - `estado`: Estado del movimiento (1 = Activo, 0 = Inactivo).
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        app(CajaService::class)->autorizar($request->user());
        $datos = $request->validate([
            'id_caja' => 'sometimes|integer|exists:caja,caja_id',
            'id_caja_operacion' => ['sometimes', 'integer', Rule::prohibitedIf($request->boolean('sin_turno')), 'exists:caja_operacion,caja_operacion_id'],
            'sin_turno' => 'sometimes|boolean',
            'estado' => 'sometimes|integer|in:0,1',
        ]);

        // Filtro de lectura para conservar el historial sin reasignarlo a una apertura.
        $soloHistorico = $datos['sin_turno'] ?? false;
        unset($datos['sin_turno']);
        $query = Caja_movimiento_venta::visiblesPara($request->user())->with(['caja', 'venta']);
        if ($soloHistorico) {
            $query->whereNull('id_caja_operacion');
        }
        foreach ($datos as $campo => $valor) {
            $query->where($campo, $valor);
        }

        return response()->json($query->orderByDesc('caja_movimiento_venta_id')->get());
    }

    /**
     * Obtiene el detalle de un movimiento financiero individual.
     *
     * @param  int  $id  Identificador del movimiento (caja_movimiento_venta_id).
     * @return JsonResponse
     */
    public function show(Request $request, $id)
    {
        app(CajaService::class)->autorizar($request->user());

        return response()->json(Caja_movimiento_venta::visiblesPara($request->user())->with(['caja', 'venta'])->findOrFail($id));
    }

    /**
     * Bloquea la creación directa manual de movimientos de caja.
     *
     * @return JsonResponse 405 Method Not Allowed
     */
    public function store(Request $request)
    {
        return $this->historial();
    }

    /**
     * Bloquea la modificación directa manual de movimientos de caja.
     *
     * @param  int  $id
     * @return JsonResponse 405 Method Not Allowed
     */
    public function update(Request $request, $id)
    {
        Caja_movimiento_venta::findOrFail($id);

        return $this->historial();
    }

    /**
     * Bloquea la eliminación manual de movimientos de caja.
     *
     * @param  int  $id
     * @return JsonResponse 405 Method Not Allowed
     */
    public function destroy($id)
    {
        Caja_movimiento_venta::findOrFail($id);

        return $this->historial();
    }

    /**
     * Retorna la respuesta estándar de rechazo para operaciones de escritura manual.
     *
     * @return JsonResponse 405
     */
    private function historial()
    {
        return response()->json([
            'success' => false,
            'message' => 'Los movimientos se generan al vender o anular; no se modifican ni eliminan directamente.',
        ], 405);
    }
}
