<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Caja_operacion;
use App\Services\CajaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CajaController extends Controller
{
    public function index(Request $request)
    {
        app(CajaService::class)->autorizar($request->user());

        return response()->json(Caja::orderBy('caja_id', 'desc')->get());
    }

    public function store(Request $request)
    {
        app(CajaService::class)->autorizar($request->user());
        $validated = $request->validate([
            'descripcion_caja' => 'required|string|max:128',
            'tipo_apertura' => 'required|string|max:16',
        ]);
        // Se conservan las reglas locales: nueva caja activa y cerrada.
        $caja = Caja::create($validated + ['estado_caja' => 'Cerrada', 'estado' => 1]);

        return response()->json(['success' => true, 'message' => 'Caja registrada correctamente.', 'caja' => $caja], 201);
    }

    public function show(Request $request, $id)
    {
        app(CajaService::class)->autorizar($request->user());

        return response()->json(Caja::with(['caja_operaciones', 'caja_movimiento_ventas'])->findOrFail($id));
    }

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

            return response()->json(['success' => true, 'message' => 'Caja actualizada correctamente.', 'caja' => $caja]);
        }, 3);
    }

    public function destroy(Request $request, $id)
    {
        app(CajaService::class)->autorizar($request->user());

        return DB::transaction(function () use ($id) {
            $caja = Caja::lockForUpdate()->findOrFail($id);
            $this->validarDesactivacion($caja);
            abort_if((int) $caja->estado === 0, 409, 'La caja ya se encuentra inactiva.');
            $caja->update(['estado' => 0]);

            return response()->json(['success' => true, 'message' => 'Caja desactivada correctamente.']);
        }, 3);
    }

    private function validarDesactivacion(Caja $caja): void
    {
        abort_if($caja->estado_caja === 'Abierta' || Caja_operacion::where('id_caja', $caja->caja_id)
            ->whereNull('fecha_hora_cierre')->exists(), 409, 'Debe cerrar el turno antes de desactivar la caja.');
    }
}
