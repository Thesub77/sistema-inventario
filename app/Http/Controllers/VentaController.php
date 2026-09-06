<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\Venta_detalle;
use App\Models\Producto;
use App\Models\Caja;
use App\Models\Caja_movimiento_venta;
use App\Models\Movimiento_inventario;
use App\Models\Bitacora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
    public function index()
    {
        $ventas = Venta::with(['usuario', 'cliente', 'venta_detalles.producto'])->orderBy('venta_id', 'desc')->get();
        return response()->json($ventas);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_usuario' => 'required|exists:usuario,usuario_id',
            'id_cliente' => 'required|exists:cliente,cliente_id',
            'codigo_venta' => 'required|string|max:32',
            'metodo_pago' => 'required|string|max:16',
            'fecha_hora_venta' => 'required|date',
            'subtotal_venta' => 'required|numeric|min:0',
            'descuento_venta' => 'required|numeric|min:0',
            'total_venta' => 'required|numeric|min:0',
            'estado' => 'required|integer',
            'id_caja' => 'nullable|exists:caja,caja_id',
            'detalles' => 'nullable|array',
            'detalles.*.id_producto' => 'required_with:detalles|exists:producto,producto_id',
            'detalles.*.cantidad' => 'required_with:detalles|integer|min:1',
            'detalles.*.precio_unitario' => 'required_with:detalles|numeric|min:0',
            'detalles.*.subtotal_venta_detalle' => 'required_with:detalles|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $ventaData = [
                'id_usuario' => $validated['id_usuario'],
                'id_cliente' => $validated['id_cliente'],
                'codigo_venta' => $validated['codigo_venta'],
                'metodo_pago' => $validated['metodo_pago'],
                'fecha_hora_venta' => $validated['fecha_hora_venta'],
                'subtotal_venta' => $validated['subtotal_venta'],
                'descuento_venta' => $validated['descuento_venta'],
                'total_venta' => $validated['total_venta'],
                'estado' => $validated['estado'],
            ];

            $venta = Venta::create($ventaData);

            // Process detalles if provided
            if (!empty($validated['detalles'])) {
                foreach ($validated['detalles'] as $item) {
                    Venta_detalle::create([
                        'id_venta' => $venta->venta_id,
                        'id_producto' => $item['id_producto'],
                        'cantidad' => $item['cantidad'],
                        'precio_unitario' => $item['precio_unitario'],
                        'subtotal_venta_detalle' => $item['subtotal_venta_detalle'],
                        'estado' => 1,
                    ]);

                    // Descontar inventario y registrar movimiento
                    $producto = Producto::find($item['id_producto']);
                    if ($producto) {
                        $stockAnterior = $producto->existencia_bodega;
                        $stockNuevo = max(0, $stockAnterior - $item['cantidad']);
                        $producto->update(['existencia_bodega' => $stockNuevo]);

                        Movimiento_inventario::create([
                            'id_producto' => $producto->producto_id,
                            'id_usuario' => $validated['id_usuario'],
                            'tipo_movimiento' => 'Salida por Venta',
                            'cantidad_movimimiento' => $item['cantidad'],
                            'stock_anterior_producto' => $stockAnterior,
                            'stock_resultante_producto' => $stockNuevo,
                            'fecha_movimiento' => $validated['fecha_hora_venta'],
                            'estado' => 1,
                        ]);
                    }
                }
            }

            // Registrar movimiento de caja si se especificó caja o buscar primera abierta
            $idCaja = $request->input('id_caja');
            if (!$idCaja) {
                $cajaAbierta = Caja::where('estado_caja', 'Abierta')->where('estado', 1)->first();
                if ($cajaAbierta) {
                    $idCaja = $cajaAbierta->caja_id;
                }
            }

            if ($idCaja) {
                Caja_movimiento_venta::create([
                    'id_caja' => $idCaja,
                    'id_venta' => $venta->venta_id,
                    'monto_movimiento' => $validated['total_venta'],
                    'fecha_hora_movimiento' => $validated['fecha_hora_venta'],
                    'estado' => 1,
                ]);
            }

            // Registrar en bitácora
            Bitacora::create([
                'id_usuario' => $validated['id_usuario'],
                'accion_bitacora' => 'NUEVA_VENTA',
                'descripcion_bitacora' => "Venta {$venta->codigo_venta} registrada por total de C$ {$venta->total_venta}",
                'fecha_hora_bitacora' => now(),
                'estado' => 1,
            ]);

            return response()->json($venta->load(['usuario', 'cliente', 'venta_detalles.producto']), 201);
        });
    }

    public function show($id)
    {
        $venta = Venta::with(['usuario', 'cliente', 'venta_detalles.producto', 'caja_movimiento_ventas'])->findOrFail($id);
        return response()->json($venta);
    }

    public function update(Request $request, $id)
    {
        $venta = Venta::findOrFail($id);
        $validated = $request->validate([
            'id_usuario' => 'sometimes|exists:usuario,usuario_id',
            'id_cliente' => 'sometimes|exists:cliente,cliente_id',
            'codigo_venta' => 'sometimes|string|max:32',
            'metodo_pago' => 'sometimes|string|max:16',
            'fecha_hora_venta' => 'sometimes|date',
            'subtotal_venta' => 'sometimes|numeric|min:0',
            'descuento_venta' => 'sometimes|numeric|min:0',
            'total_venta' => 'sometimes|numeric|min:0',
            'estado' => 'sometimes|integer',
        ]);

        $venta->update($validated);
        return response()->json($venta);
    }

    public function destroy($id)
    {
        $venta = Venta::findOrFail($id);
        $venta->delete();
        return response()->json(['message' => 'Venta eliminada correctamente']);
    }
}
