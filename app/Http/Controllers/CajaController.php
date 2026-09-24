<?php

namespace App\Http\Controllers;

use App\Exceptions\CajaException;
use App\Models\Caja;
use App\Models\Caja_operacion;
use App\Services\CajaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controlador de Cajas Físicas / Puntos de Venta.
 *
 * Administra el ciclo de vida del catálogo de cajas registradas:
 * - Creación y configuración de cajas físicas.
 * - Consulta de cajas con sus turnos y movimientos asociados.
 * - Edición de descripciones y modos de apertura.
 * - Desactivación lógica segura (impide desactivar cajas con turnos abiertos).
 */
class CajaController extends Controller
{
    /**
     * Lista todas las cajas registradas en orden descendente.
     *
     * Requiere que el usuario cuente con permisos de acceso a cajas o sea Administrador.
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        app(CajaService::class)->autorizar($request->user());

        return response()->json(Caja::orderBy('caja_id', 'desc')->get());
    }

    /**
     * Registra una nueva caja física en el sistema.
     *
     * Toda nueva caja se inicializa en estado activa (estado = 1) y 'Cerrada',
     * lista para recibir su primera apertura formal de turno.
     *
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        app(CajaService::class)->autorizar($request->user());
        $validated = $request->validate([
            'descripcion_caja' => 'required|string|max:128',
            'tipo_apertura' => 'required|string|max:16',
        ]);

        // Se conservan las reglas de negocio: nueva caja activa y cerrada.
        $caja = Caja::create($validated + ['estado_caja' => 'Cerrada', 'estado' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Caja registrada correctamente.',
            'caja' => $caja,
        ], 201);
    }

    /**
     * Obtiene el detalle de una caja específica incluyendo sus turnos y movimientos.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show(Request $request, $id)
    {
        app(CajaService::class)->autorizar($request->user());

        $usuario = $request->user();

        return response()->json(Caja::with([
            'caja_operaciones' => function ($query) use ($usuario) {
                if (! $usuario->esAdmin()) {
                    $query->where('id_usuario', $usuario->usuario_id);
                }
            },
            'caja_movimiento_ventas' => fn ($query) => $query->visiblesPara($usuario),
        ])->findOrFail($id));
    }

    /**
     * Actualiza la información básica de una caja.
     *
     * El estado operativo ('estado_caja': 'Abierta'/'Cerrada') está prohibido aquí,
     * ya que solo se altera mediante aperturas y cierres en CajaOperacionController.
     * Si se solicita desactivar la caja (estado = 0), se valida que no tenga turnos abiertos.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        app(CajaService::class)->autorizar($request->user());
        $validated = $request->validate([
            'descripcion_caja' => 'sometimes|required|string|max:128',
            'tipo_apertura' => 'sometimes|required|string|max:16',
            'estado' => 'sometimes|integer|in:0,1',
            'estado_caja' => 'prohibited',
        ]);

        return DB::transaction(function () use ($validated, $id) {
            $caja = Caja::lockForUpdate()->findOrFail($id);
            if (isset($validated['estado']) && (int) $validated['estado'] === 0) {
                $this->validarDesactivacion($caja);
            }
            $caja->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Caja actualizada correctamente.',
                'caja' => $caja,
            ]);
        }, 3);
    }

    /**
     * Realiza la desactivación lógica de una caja (estado = 0).
     *
     * Bloquea la fila y comprueba que la caja no esté abierta ni contenga
     * turnos operativos pendientes de cierre antes de proceder.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        app(CajaService::class)->autorizar($request->user());

        return DB::transaction(function () use ($id) {
            $caja = Caja::lockForUpdate()->findOrFail($id);
            $this->validarDesactivacion($caja);
            CajaException::rechazarSi((int) $caja->estado === 0, 409, 'La caja ya se encuentra inactiva.');
            $caja->update(['estado' => 0]);

            return response()->json([
                'success' => true,
                'message' => 'Caja desactivada correctamente.',
            ]);
        }, 3);
    }

    /**
     * Valida de manera estricta que la caja no tenga un turno activo o pendiente de cierre.
     *
     * @throws CajaException Si la caja está abierta o tiene turnos sin cerrar.
     */
    private function validarDesactivacion(Caja $caja): void
    {
        CajaException::rechazarSi(
            $caja->estado_caja === 'Abierta' || Caja_operacion::where('id_caja', $caja->caja_id)->whereNull('fecha_hora_cierre')->exists(),
            409,
            'Debe cerrar el turno antes de desactivar la caja.'
        );
    }
}
