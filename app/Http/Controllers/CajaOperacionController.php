<?php

namespace App\Http\Controllers;

use App\Exceptions\CajaException;
use App\Models\Bitacora;
use App\Models\Caja;
use App\Models\Caja_operacion;
use App\Models\Venta_espera;
use App\Services\CajaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Controlador de Operaciones y Turnos de Caja (Apertura, Arqueo y Cierre).
 *
 * Administra el ciclo operativo financiero de los turnos de caja:
 * 1. Apertura (POST): Registro de monto inicial real en efectivo y activación del turno.
 * 2. Arqueo (GET /show): Cálculo en tiempo real del saldo esperado (apertura + ventas en efectivo - devoluciones en efectivo).
 * 3. Cierre (PUT/PATCH): Verificación del conteo físico final, cálculo automático de diferencias (faltante/sobrante)
 *    y exigencia de justificación obligatoria si existe descuadre.
 * 4. Auditoría: Registro de bitácoras y protección contra borrados de historial.
 */
class CajaOperacionController extends Controller
{
    /**
     * @param  CajaService  $cajas  Servicio central con las reglas de negocio de cajas.
     */
    public function __construct(private CajaService $cajas) {}

    /**
     * Lista los turnos de caja registrados.
     *
     * - Un usuario Administrador puede consultar el historial de todos los turnos.
     * - Un usuario con permiso de caja/POS solo visualiza los turnos asociados a su propio usuario.
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $this->cajas->autorizar($request->user());
        $query = Caja_operacion::with(['caja', 'usuario']);
        if (! $request->user()->esAdmin()) {
            $query->where('id_usuario', $request->user()->usuario_id);
        }

        return response()->json($query->orderByDesc('caja_operacion_id')->get());
    }

    /**
     * Registra la Apertura formal de un turno de caja con su monto inicial.
     *
     * Reglas de negocio aplicadas:
     * - La caja debe estar activa y en estado válido ('Cerrada' o 'Abierta' sin turno).
     * - Impide aperturas simultáneas en una caja que ya cuenta con un turno abierto sin cerrar.
     * - El monto de apertura debe ser un importe decimal no negativo.
     * - Se bloquea la fila de la caja (`lockForUpdate`), se crea el registro del turno,
     *   se actualiza la caja a 'Abierta' y se audita en la bitácora dentro de la misma transacción.
     *
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $usuario = $request->user();
        $this->cajas->autorizar($usuario);
        $datos = $request->validate([
            'id_caja' => 'required|integer|exists:caja,caja_id',
            'id_usuario' => ['sometimes', 'integer', Rule::in([$usuario->usuario_id])],
            'monto_apertura' => 'required|numeric|decimal:0,2|min:0|max:999999999.99',
            'estado' => 'sometimes|integer|in:1',
            'monto_cierre' => 'prohibited',
            'fecha_hora_cierre' => 'prohibited',
            'fecha_hora_apertura' => 'prohibited',
        ]);

        return DB::transaction(function () use ($datos, $usuario) {
            // El bloqueo y la excepción controlada impiden aperturas duplicadas o parciales concurrentes.
            $caja = Caja::lockForUpdate()->findOrFail($datos['id_caja']);
            // Una caja heredada abierta sin turno también permite registrar su monto real explícito.
            CajaException::exigir((int) $caja->estado === 1 && in_array($caja->estado_caja, ['Cerrada', 'Abierta'], true), 409, 'La caja debe estar activa y en un estado válido para registrar la apertura.');

            // 1. El usuario no puede tener más de un turno abierto simultáneamente
            $turnoExistente = Caja_operacion::with('caja')
                ->where('id_usuario', $usuario->usuario_id)
                ->whereNull('fecha_hora_cierre')
                ->where('estado', 1)
                ->first();
            CajaException::rechazarSi($turnoExistente !== null, 409, "Ya tienes el Turno #{$turnoExistente?->caja_operacion_id} abierto en '{$turnoExistente?->caja?->descripcion_caja}'. Debes cerrarlo antes de aperturar otra caja.");

            // 2. La caja física no puede tener un turno abierto por otro cajero
            CajaException::rechazarSi(
                Caja_operacion::where('id_caja', $caja->caja_id)->whereNull('fecha_hora_cierre')->where('estado', 1)->exists(),
                409,
                "La caja '{$caja->descripcion_caja}' ya se encuentra abierta y en uso por otro cajero."
            );

            $turno = Caja_operacion::create([
                'id_caja' => $caja->caja_id,
                'id_usuario' => $usuario->usuario_id,
                'monto_apertura' => $datos['monto_apertura'],
                'fecha_hora_apertura' => now(),
                'estado' => 1,
            ]);
            $caja->update(['estado_caja' => 'Abierta']);
            $this->cajas->bitacora($usuario, 'APERTURA_CAJA', $turno);

            return response()->json($turno, 201);
        }, 3);
    }

    /**
     * Consulta el detalle y arqueo financiero en tiempo real de un turno.
     *
     * Retorna el turno junto con los cálculos dinámicos de:
     * - Desglose de ventas por método de pago (Efectivo, Tarjeta, Transferencia).
     * - Desglose de devoluciones y anulaciones por método de pago.
     * - Monto total de efectivo físico esperado en caja.
     *
     * @param  int  $id  Identificador del turno (caja_operacion_id).
     * @return JsonResponse
     */
    public function show(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $referencia = Caja_operacion::findOrFail($id);
            Caja::lockForUpdate()->findOrFail($referencia->id_caja);
            $turno = Caja_operacion::with(['caja', 'usuario'])->lockForUpdate()->findOrFail($id);
            $this->cajas->autorizar($request->user(), $turno);
            $turno->setAttribute('arqueo', $this->cajas->arqueo($turno));

            return response()->json($turno);
        });
    }

    /**
     * Ejecuta el Cierre formal de un turno de caja.
     *
     * Reglas de negocio aplicadas:
     * - Comprueba que el turno siga abierto y pertenezca al usuario responsable (o que sea Admin).
     * - Calcula el saldo esperado mediante arqueo financiero.
     * - Compara el monto físico reportado (`monto_cierre`) contra el `monto_esperado`.
     * - Si hay discrepancia (`diferencia != 0.00`, faltante o sobrante), exige obligatoriamente
     *   especificar una `observacion_cierre` (código 422 si se omite).
     * - Guarda fecha de cierre, monto de cierre, monto esperado y diferencia calculada.
     * - Cambia el estado del turno a inactivo (estado = 0) y la caja a 'Cerrada'.
     * - Registra la acción en la bitácora de auditoría.
     *
     * @param  int  $id  Identificador del turno (caja_operacion_id).
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        $datos = $request->validate([
            'monto_cierre' => 'required|numeric|decimal:0,2|min:0|max:999999999.99',
            'observacion_cierre' => 'nullable|string|max:255',
            'descartar_ventas_espera' => 'sometimes|boolean',
            'id_caja' => 'prohibited',
            'id_usuario' => 'prohibited',
            'monto_apertura' => 'prohibited',
            'fecha_hora_apertura' => 'prohibited',
            'fecha_hora_cierre' => 'prohibited',
            'estado' => 'sometimes|integer|in:0',
        ]);

        return DB::transaction(function () use ($request, $datos, $id) {
            $referencia = Caja_operacion::findOrFail($id);
            $caja = Caja::lockForUpdate()->findOrFail($referencia->id_caja);
            $turno = $this->cajas->turno($caja, $request->user());
            // Verificar si es cierre propio o cierre supervisado por Administrador
            $esAdminCierre = (int) $turno->id_usuario !== (int) $request->user()->usuario_id;
            if ($esAdminCierre) {
                CajaException::exigir($request->user()->esAdmin(), 403, 'No tienes permiso para cerrar el turno de otro cajero.');
            }

            // Verificar si existen ventas en espera pendientes asociadas a la caja o al cajero
            $ventasEsperaQuery = Venta_espera::where('estado', 1)
                ->where(function ($q) use ($turno) {
                    $q->where('id_caja', $turno->id_caja)
                        ->orWhere('id_usuario', $turno->id_usuario);
                });

            $hayVentasEspera = (clone $ventasEsperaQuery)->exists();

            if ($hayVentasEspera) {
                if ($request->user()->esAdmin() && $request->boolean('descartar_ventas_espera')) {
                    $ventasADescartar = $ventasEsperaQuery->get();
                    foreach ($ventasADescartar as $v) {
                        $v->update(['estado' => 0]);
                        $v->detalles()->update(['estado' => 0]);
                    }

                    Bitacora::create([
                        'id_usuario' => $request->user()->usuario_id,
                        'accion_bitacora' => 'DESCARTE_ESPERA_CIERRE',
                        'descripcion_bitacora' => mb_substr("Admin descartó {$ventasADescartar->count()} ventas en espera en Caja #{$turno->id_caja} para Cierre de Turno #{$turno->caja_operacion_id}", 0, 128),
                        'fecha_hora_bitacora' => now(),
                        'estado' => 1,
                    ]);
                } else {
                    throw new CajaException(422, 'No puedes cerrar el turno mientras tengas ventas en espera activas. Debes reanudarlas o descartarlas antes de continuar.');
                }
            }

            // El historial sin turno se consulta por separado. Su existencia no bloquea
            // el cierre ni autoriza a sumarlo al efectivo del turno actual.
            $arqueo = $this->cajas->arqueo($turno);
            $diferencia = $this->cajas->centavos($datos['monto_cierre']) - $this->cajas->centavos($arqueo['monto_esperado']);
            CajaException::rechazarSi($diferencia !== 0 && trim($datos['observacion_cierre'] ?? '') === '', 422, 'Indique una observación para el faltante o sobrante.');

            $turno->update([
                'monto_cierre' => $datos['monto_cierre'],
                'monto_esperado' => $arqueo['monto_esperado'],
                'diferencia' => $this->cajas->importe($diferencia),
                'observacion_cierre' => $datos['observacion_cierre'] ?? null,
                'id_usuario_cierre' => $request->user()->usuario_id,
                'fecha_hora_cierre' => now(),
                'estado' => 0,
            ]);
            $caja->update(['estado_caja' => 'Cerrada']);

            $accion = $esAdminCierre ? 'CIERRE_SUPERVISADO_CAJA' : 'CIERRE_CAJA';
            $this->cajas->bitacora($request->user(), $accion, $turno);

            return response()->json($turno);
        }, 3);
    }

    /**
     * Bloquea la eliminación física o lógica directa de los turnos de caja.
     *
     * Los turnos representan el registro histórico financiero auditado del negocio
     * y nunca deben ser borrados de la base de datos.
     *
     * @param  int  $id
     * @return JsonResponse 405 Method Not Allowed
     */
    public function destroy($id)
    {
        Caja_operacion::findOrFail($id);

        return response()->json([
            'success' => false,
            'message' => 'Los turnos se conservan como historial y no se eliminan.',
        ], 405);
    }
}
