<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Movimiento_inventario;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MovimientoInventarioController extends Controller
{
    public function index(Request $request)
    {
        $datos = $request->validate([
            'id_producto' => 'sometimes|required|integer|exists:producto,producto_id',
            'tipo_movimiento' => 'sometimes|required|string|max:24',
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date'.($request->filled('fecha_desde') ? '|after_or_equal:fecha_desde' : ''),
        ]);
        $query = Movimiento_inventario::with(['producto', 'usuario']);
        foreach (['id_producto', 'tipo_movimiento'] as $campo) {
            if (isset($datos[$campo])) {
                $query->where($campo, $datos[$campo]);
            }
        }
        if (!empty($datos['fecha_desde'])) {
            $query->whereDate('fecha_movimiento', '>=', $datos['fecha_desde']);
        }
        if (!empty($datos['fecha_hasta'])) {
            $query->whereDate('fecha_movimiento', '<=', $datos['fecha_hasta']);
        }

        return response()->json($query->orderByDesc('movimiento_inventario_id')->get());
    }

    public function store(Request $request)
    {
        // Ambos nombres de entrada se guardan en la columna original de la BD.
        if ($request->has('cantidad_movimiento') && !$request->has('cantidad_movimimiento')) {
            $request->merge(['cantidad_movimimiento' => $request->input('cantidad_movimiento')]);
        }
        $ajuste = $request->input('tipo_movimiento') === 'Ajuste Manual';
        $datos = $request->validate([
            'id_producto' => 'required|integer|exists:producto,producto_id',
            'id_usuario' => ['required', 'integer', Rule::exists('usuario', 'usuario_id')->where('estado', 1)],
            'tipo_movimiento' => ['required', Rule::in(['Entrada', 'Entrada por Compra', 'Salida', 'Salida por Merma', 'Ajuste Manual'])],
            'cantidad_movimimiento' => ($ajuste ? 'sometimes' : 'required').'|integer|min:1|max:2147483647',
            'stock_resultante_producto' => ($ajuste ? 'required' : 'sometimes').'|integer|min:0|max:2147483647',
            'stock_anterior_producto' => 'sometimes|integer|min:0|max:2147483647',
            'fecha_movimiento' => 'required|date',
            'estado' => 'sometimes|integer|in:1',
            'justificacion' => [Rule::requiredIf($ajuste || in_array($request->input('tipo_movimiento'), ['Salida', 'Salida por Merma'], true)), 'nullable', 'string', 'max:90'],
        ]);

        return DB::transaction(function () use ($datos, $ajuste) {
            $producto = Producto::lockForUpdate()->findOrFail($datos['id_producto']);
            $anterior = (int) $producto->existencia_bodega;
            $entrada = in_array($datos['tipo_movimiento'], ['Entrada', 'Entrada por Compra'], true);
            $nuevo = $ajuste ? (int) $datos['stock_resultante_producto']
                : $anterior + ($entrada ? 1 : -1) * $datos['cantidad_movimimiento'];

            if ($nuevo < 0 || $nuevo > 2147483647 || $nuevo === $anterior) {
                throw ValidationException::withMessages(['cantidad_movimimiento' => 'El movimiento debe cambiar el stock sin dejar existencias negativas ni superar el límite permitido.']);
            }
            // Los saldos enviados son precondiciones; el stock se calcula en el servidor.
            if (isset($datos['stock_anterior_producto']) && (int) $datos['stock_anterior_producto'] !== $anterior) {
                throw ValidationException::withMessages(['stock_anterior_producto' => 'Las existencias cambiaron. Consulte el stock actual antes de registrar el movimiento.']);
            }
            if (!$ajuste && isset($datos['stock_resultante_producto']) && (int) $datos['stock_resultante_producto'] !== $nuevo) {
                throw ValidationException::withMessages(['stock_resultante_producto' => 'El saldo enviado no coincide con el movimiento solicitado.']);
            }

            $movimiento = Movimiento_inventario::create([
                'id_producto' => $producto->producto_id,
                'id_usuario' => $datos['id_usuario'],
                'tipo_movimiento' => $datos['tipo_movimiento'],
                'cantidad_movimimiento' => abs($nuevo - $anterior),
                'stock_anterior_producto' => $anterior,
                'stock_resultante_producto' => $nuevo,
                'fecha_movimiento' => $datos['fecha_movimiento'],
                'estado' => 1,
            ]);
            $producto->update(['existencia_bodega' => $nuevo]);
            Bitacora::create([
                'id_usuario' => $datos['id_usuario'],
                'accion_bitacora' => 'MOVIMIENTO_INVENTARIO',
                'descripcion_bitacora' => 'Movimiento #'.$movimiento->movimiento_inventario_id.': '.($datos['justificacion'] ?? $datos['tipo_movimiento']),
                'fecha_hora_bitacora' => now(),
                'estado' => 1,
            ]);

            return response()->json(['success' => true, 'message' => 'Movimiento de inventario registrado correctamente.', 'movimiento' => $movimiento], 201);
        });
    }

    public function show($id)
    {
        return response()->json(Movimiento_inventario::with(['producto', 'usuario'])->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        Movimiento_inventario::findOrFail($id);

        return response()->json(['message' => 'El historial no se modifica. Registre un ajuste con su justificación.'], 405);
    }

    public function destroy($id)
    {
        Movimiento_inventario::findOrFail($id);

        return response()->json(['message' => 'El historial no se elimina. Registre un ajuste con su justificación.'], 405);
    }
}
