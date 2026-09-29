<?php

namespace App\Http\Controllers;

use App\Exceptions\CajaException;
use App\Models\Bitacora;
use App\Models\Caja_movimiento_venta;
use App\Models\Caja_operacion;
use App\Services\CajaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Controlador de Movimientos Financieros de Caja por Ventas, Devoluciones y Operaciones Extraordinarias.
 *
 * Expone la consulta y registro auditado de los flujos de dinero en caja:
 * - Ingresos por ventas confirmadas.
 * - Egresos (montos negativos) por anulaciones y devoluciones.
 * - Ingresos y egresos extraordinarios de efectivo (gastos menores y sencillo) con justificación obligatoria (RF-25).
 * - Filtros por caja, por turno operativo, o por movimientos históricos sin turno.
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
        $query = Caja_movimiento_venta::visiblesPara($request->user())->with(['caja', 'venta', 'turno']);
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

        return response()->json(Caja_movimiento_venta::visiblesPara($request->user())->with(['caja', 'venta', 'turno'])->findOrFail($id));
    }

    /**
     * Registra un movimiento de efectivo extraordinario en caja (RF-25).
     *
     * Permite registrar entradas de sencillo o egresos por gastos menores durante el turno
     * con su debida justificación obligatoria para evitar descuadres en el arqueo (RF-28).
     *
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        app(CajaService::class)->autorizar($request->user());

        $datos = $request->validate([
            'id_caja' => 'sometimes|nullable|integer|exists:caja,caja_id',
            'id_caja_operacion' => 'sometimes|nullable|integer|exists:caja_operacion,caja_operacion_id',
            'tipo_movimiento' => ['required', 'string', Rule::in(['Ingreso', 'Entrada', 'Egreso', 'Salida', 'Gasto'])],
            'monto' => 'required|numeric|min:0.01|max:999999999.99',
            'justificacion' => 'required|string|min:3|max:255',
        ], [
            'tipo_movimiento.required' => 'El tipo de movimiento es obligatorio (Ingreso o Egreso).',
            'tipo_movimiento.in' => 'El tipo de movimiento debe ser Ingreso o Egreso.',
            'monto.required' => 'El monto del movimiento es obligatorio.',
            'monto.min' => 'El monto debe ser mayor a 0.',
            'justificacion.required' => 'El motivo o justificación del movimiento es obligatorio.',
        ]);

        return DB::transaction(function () use ($datos, $request) {
            $service = app(CajaService::class);
            $usuario = $request->user();

            if (! empty($datos['id_caja_operacion'])) {
                $turno = Caja_operacion::lockForUpdate()->findOrFail($datos['id_caja_operacion']);
                $service->autorizar($usuario, $turno);
                CajaException::exigir($turno->fecha_hora_cierre === null && (int) $turno->estado === 1, 409, 'El turno indicado no está abierto.');
            } else {
                $idCaja = $datos['id_caja'] ?? null;
                $turno = $service->paraVenta($idCaja, $usuario);
            }

            $esIngreso = in_array($datos['tipo_movimiento'], ['Ingreso', 'Entrada'], true);
            $montoCentavos = $service->centavos($datos['monto']);
            $montoFinal = $esIngreso ? $service->importe($montoCentavos) : $service->importe(-$montoCentavos);

            $movimiento = Caja_movimiento_venta::create([
                'id_caja' => $turno->id_caja,
                'id_caja_operacion' => $turno->caja_operacion_id,
                'id_venta' => null,
                'monto_movimiento' => $montoFinal,
                'fecha_hora_movimiento' => now(),
                'estado' => 1,
            ]);

            Bitacora::create([
                'id_usuario' => $usuario->usuario_id,
                'accion_bitacora' => $esIngreso ? 'INGRESO_EFECTIVO_CAJA' : 'EGRESO_EFECTIVO_CAJA',
                'descripcion_bitacora' => "Movimiento extraordinario #{$movimiento->caja_movimiento_venta_id} en Caja #{$turno->id_caja} (Turno #{$turno->caja_operacion_id}): ".($esIngreso ? 'Ingreso' : 'Egreso')." de C$ {$datos['monto']}. Motivo: {$datos['justificacion']}",
                'fecha_hora_bitacora' => now(),
                'estado' => 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Movimiento de caja registrado correctamente.',
                'movimiento' => $movimiento->load('caja', 'turno'),
            ], 201);
        });
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
     * Retorna la respuesta estándar de rechazo para operaciones de alteración de historial.
     *
     * @return JsonResponse 405
     */
    private function historial()
    {
        return response()->json([
            'success' => false,
            'message' => 'Los movimientos financieros no se modifican ni eliminan para garantizar la inmutabilidad de la auditoría.',
        ], 405);
    }
}
