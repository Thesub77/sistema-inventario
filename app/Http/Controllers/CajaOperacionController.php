<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Caja_movimiento_venta;
use App\Models\Caja_operacion;
use App\Services\CajaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CajaOperacionController extends Controller
{
    public function __construct(private CajaService $cajas) {}

    public function index(Request $request)
    {
        $this->cajas->autorizar($request->user());
        $query = Caja_operacion::with(['caja', 'usuario']);
        if (! $request->user()->esAdmin()) {
            $query->where('id_usuario', $request->user()->usuario_id);
        }

        return response()->json($query->orderByDesc('caja_operacion_id')->get());
    }

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
            $caja = Caja::lockForUpdate()->findOrFail($datos['id_caja']);
            abort_unless((int) $caja->estado === 1 && $caja->estado_caja === 'Cerrada', 409, 'La caja debe estar activa y cerrada.');
            abort_if(Caja_operacion::where('id_caja', $caja->caja_id)->whereNull('fecha_hora_cierre')->exists(), 409, 'Ya existe una apertura sin cerrar.');
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

    // PUT/PATCH conserva la ruta existente y representa exclusivamente el cierre.
    public function update(Request $request, $id)
    {
        $datos = $request->validate([
            'monto_cierre' => 'required|numeric|decimal:0,2|min:0|max:999999999.99',
            'observacion_cierre' => 'nullable|string|max:255',
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
            abort_unless((int) $turno->caja_operacion_id === (int) $id, 409, 'El turno ya está cerrado.');
            abort_if(Caja_movimiento_venta::where('id_caja', $caja->caja_id)->whereNull('id_caja_operacion')
                ->where('estado', 1)->where('fecha_hora_movimiento', '>=', $turno->fecha_hora_apertura)->exists(), 409, 'Hay movimientos sin turno asignado; concilie el historial antes de cerrar.');
            $arqueo = $this->cajas->arqueo($turno);
            $diferencia = $this->cajas->centavos($datos['monto_cierre']) - $this->cajas->centavos($arqueo['monto_esperado']);
            abort_if($diferencia !== 0 && trim($datos['observacion_cierre'] ?? '') === '', 422, 'Indique una observación para el faltante o sobrante.');
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
            $this->cajas->bitacora($request->user(), 'CIERRE_CAJA', $turno);

            return response()->json($turno);
        }, 3);
    }

    public function destroy($id)
    {
        Caja_operacion::findOrFail($id);

        return response()->json(['message' => 'Los turnos se conservan como historial y no se eliminan.'], 405);
    }
}
