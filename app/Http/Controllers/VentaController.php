<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Caja;
use App\Models\Caja_movimiento_venta;
use App\Models\Movimiento_inventario;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\Venta_detalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VentaController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date'.($request->filled('fecha_desde') ? '|after_or_equal:fecha_desde' : ''),
            'id_usuario' => 'nullable|exists:usuario,usuario_id',
            'codigo_venta' => 'nullable|string|max:32',
        ]);

        $query = Venta::with([
            'usuario',
            'cliente',
            'venta_detalles.producto',
        ]);

        if (! empty($validated['fecha_desde'])) {
            $query->whereDate(
                'fecha_hora_venta',
                '>=',
                $validated['fecha_desde']
            );
        }

        if (! empty($validated['fecha_hasta'])) {
            $query->whereDate(
                'fecha_hora_venta',
                '<=',
                $validated['fecha_hasta']
            );
        }

        if (! empty($validated['id_usuario'])) {
            $query->where(
                'id_usuario',
                $validated['id_usuario']
            );
        }

        if (! empty($validated['codigo_venta'])) {
            $query->where(
                'codigo_venta',
                'like',
                '%'.$validated['codigo_venta'].'%'
            );
        }

        $ventas = $query
            ->orderBy('venta_id', 'desc')
            ->get();

        return response()->json($ventas);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_usuario' => ['required', 'integer', Rule::exists('usuario', 'usuario_id')->where('estado', 1)],

            'id_cliente' => ['required', 'integer', Rule::exists('cliente', 'cliente_id')->where('estado', 1)],

            'codigo_venta' => 'required|string|max:32|unique:venta,codigo_venta',

            'metodo_pago' => 'required|string|max:16',

            'fecha_hora_venta' => 'required|date',

            'descuento_venta' => 'nullable|numeric|decimal:0,2|min:0|max:999.99',

            'id_caja' => 'nullable|exists:caja,caja_id',

            'detalles' => 'required|array|min:1',

            'detalles.*.id_producto' => 'required|exists:producto,producto_id|distinct',

            'detalles.*.cantidad' => 'required|integer|min:1|max:2147483647',
        ]);

        return DB::transaction(function () use ($validated) {

            $idCaja = $validated['id_caja'] ?? null;

            $queryCaja = Caja::where('estado_caja', 'Abierta')
                ->where('estado', 1);

            if ($idCaja) {
                $queryCaja->where('caja_id', $idCaja);
            }

            $cajaAbierta = $queryCaja
                ->lockForUpdate()
                ->first();

            if (! $cajaAbierta) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede realizar la venta porque no hay una caja abierta.',
                ], 409);
            }

            $idCaja = $cajaAbierta->caja_id;

            $metodoPago = ucfirst(
                strtolower(trim($validated['metodo_pago']))
            );

            $metodosPermitidos = [
                'Efectivo',
                'Tarjeta',
                'Transferencia',
            ];

            if (! in_array($metodoPago, $metodosPermitidos)) {
                throw ValidationException::withMessages([
                    'metodo_pago' => 'El método de pago debe ser Efectivo, Tarjeta o Transferencia.',
                ]);
            }

            $subtotalVenta = 0;

            $detallesProcesados = [];

            foreach (collect($validated['detalles'])->sortBy('id_producto') as $index => $item) {

                $producto = Producto::where(
                    'producto_id',
                    $item['id_producto']
                )
                    ->where('estado', 1)
                    ->lockForUpdate()
                    ->first();

                if (! $producto) {
                    throw ValidationException::withMessages([
                        "detalles.{$index}.id_producto" => 'El producto no existe o se encuentra inactivo.',
                    ]);
                }

                if (
                    $item['cantidad']
                    >
                    $producto->existencia_bodega
                ) {
                    throw ValidationException::withMessages([
                        "detalles.{$index}.cantidad" => "Stock insuficiente para {$producto->nombre_producto}. ".
                            "Existencia disponible: {$producto->existencia_bodega}.",
                    ]);
                }

                $precioUnitario =
                    $this->centavos($producto->precio_venta);

                $cantidad =
                    (int) $item['cantidad'];

                if ($precioUnitario > intdiv(999999999999999999 - $subtotalVenta, $cantidad)) {
                    throw ValidationException::withMessages(['detalles' => 'El importe supera la capacidad de la venta.']);
                }
                $subtotalDetalle = $precioUnitario * $cantidad;

                $subtotalVenta +=
                    $subtotalDetalle;

                $detallesProcesados[] = [
                    'producto' => $producto,

                    'cantidad' => $cantidad,

                    'precio_unitario' => $this->importe($precioUnitario),

                    'subtotal_venta_detalle' => $this->importe($subtotalDetalle),
                ];
            }

            $descuentoVenta =
                $this->centavos($validated['descuento_venta'] ?? 0);

            if ($descuentoVenta > $subtotalVenta) {
                throw ValidationException::withMessages([
                    'descuento_venta' => 'El descuento no puede ser mayor que el subtotal de la venta.',
                ]);
            }

            $totalVenta = $this->importe($subtotalVenta - $descuentoVenta);
            $subtotalVenta = $this->importe($subtotalVenta);
            $descuentoVenta = $this->importe($descuentoVenta);

            $venta = Venta::create([
                'id_usuario' => $validated['id_usuario'],

                'id_cliente' => $validated['id_cliente'],

                'codigo_venta' => $validated['codigo_venta'],

                'metodo_pago' => $metodoPago,

                'fecha_hora_venta' => $validated['fecha_hora_venta'],

                'subtotal_venta' => $subtotalVenta,

                'descuento_venta' => $descuentoVenta,

                'total_venta' => $totalVenta,

                'estado' => 1,
            ]);

            foreach ($detallesProcesados as $detalle) {

                $producto =
                    $detalle['producto'];

                Venta_detalle::create([
                    'id_venta' => $venta->venta_id,

                    'id_producto' => $producto->producto_id,

                    'cantidad' => $detalle['cantidad'],

                    'precio_unitario' => $detalle['precio_unitario'],

                    'subtotal_venta_detalle' => $detalle['subtotal_venta_detalle'],

                    'estado' => 1,
                ]);

                $stockAnterior =
                    $producto->existencia_bodega;

                $stockNuevo =
                    $stockAnterior
                    -
                    $detalle['cantidad'];

                $producto->update([
                    'existencia_bodega' => $stockNuevo,
                ]);

                Movimiento_inventario::create([
                    'id_producto' => $producto->producto_id,

                    'id_usuario' => $validated['id_usuario'],

                    'tipo_movimiento' => 'Salida por Venta',

                    'cantidad_movimimiento' => $detalle['cantidad'],

                    'stock_anterior_producto' => $stockAnterior,

                    'stock_resultante_producto' => $stockNuevo,

                    'fecha_movimiento' => $validated['fecha_hora_venta'],

                    'estado' => 1,
                ]);
            }

            Caja_movimiento_venta::create([
                'id_caja' => $idCaja,

                'id_venta' => $venta->venta_id,

                'monto_movimiento' => $totalVenta,

                'fecha_hora_movimiento' => $validated['fecha_hora_venta'],

                'estado' => 1,
            ]);

            Bitacora::create([
                'id_usuario' => $validated['id_usuario'],

                'accion_bitacora' => 'NUEVA_VENTA',

                'descripcion_bitacora' => "Venta {$venta->codigo_venta} registrada por total de C$ {$totalVenta}",

                'fecha_hora_bitacora' => now(),

                'estado' => 1,
            ]);

            return response()->json([
                'success' => true,

                'message' => 'Venta registrada correctamente.',

                'venta' => $venta->load([
                    'usuario',
                    'cliente',
                    'venta_detalles.producto',
                ]),
            ], 201);
        });
    }

    public function show($id)
    {
        $venta = Venta::with([
            'usuario',
            'cliente',
            'venta_detalles.producto',
            'caja_movimiento_ventas',
        ])
            ->findOrFail($id);

        return response()->json($venta);
    }

    public function update(Request $request, $id)
    {
        $venta = Venta::findOrFail($id);

        return response()->json([
            'success' => false,

            'message' => 'Una venta confirmada no puede modificarse directamente. '.
                'Si existe un error, debe anularse y registrar una nueva venta.',
        ], 405);
    }

    public function destroy(Request $request, $id)
    {
        $validated = $request->validate([
            'id_usuario' => ['required', 'integer', Rule::exists('usuario', 'usuario_id')->where('estado', 1)],
        ]);

        return DB::transaction(function () use (
            $id,
            $validated
        ) {

            $venta = Venta::with([
                'venta_detalles',
            ])
                ->lockForUpdate()
                ->findOrFail($id);

            if ($venta->estado == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'La venta ya se encuentra anulada.',
                ], 409);
            }

            foreach ($venta->venta_detalles->sortBy('id_producto') as $detalle) {

                $producto = Producto::where(
                    'producto_id',
                    $detalle->id_producto
                )
                    ->lockForUpdate()
                    ->first();

                if (! $producto) {
                    throw ValidationException::withMessages([
                        'producto' => "No se encontró el producto {$detalle->id_producto} ".
                            'asociado a la venta.',
                    ]);
                }

                $stockAnterior =
                    $producto->existencia_bodega;

                $stockNuevo =
                    $stockAnterior
                    +
                    $detalle->cantidad;

                if ($stockNuevo > 2147483647) {
                    throw ValidationException::withMessages(['producto' => 'La devolución supera el límite de existencias del producto.']);
                }

                $producto->update([
                    'existencia_bodega' => $stockNuevo,
                ]);

                Movimiento_inventario::create([
                    'id_producto' => $producto->producto_id,

                    'id_usuario' => $validated['id_usuario'],

                    'tipo_movimiento' => 'Anulación de Venta',

                    'cantidad_movimimiento' => $detalle->cantidad,

                    'stock_anterior_producto' => $stockAnterior,

                    'stock_resultante_producto' => $stockNuevo,

                    'fecha_movimiento' => now(),

                    'estado' => 1,
                ]);
            }

            Venta_detalle::where(
                'id_venta',
                $venta->venta_id
            )
                ->update([
                    'estado' => 0,
                ]);

            Caja_movimiento_venta::where(
                'id_venta',
                $venta->venta_id
            )
                ->update([
                    'estado' => 0,
                ]);

            $venta->update([
                'estado' => 0,
            ]);

            Bitacora::create([
                'id_usuario' => $validated['id_usuario'],

                'accion_bitacora' => 'ANULAR_VENTA',

                'descripcion_bitacora' => "Venta {$venta->codigo_venta} anulada.",

                'fecha_hora_bitacora' => now(),

                'estado' => 1,
            ]);

            return response()->json([
                'success' => true,

                'message' => 'Venta anulada correctamente.',
            ]);
        });
    }

    public function comprobante($id)
    {
        // Los importes y precios son los guardados al confirmar, no los del catálogo actual.
        $venta = Venta::with(['usuario', 'cliente', 'venta_detalles.producto'])->findOrFail($id);

        return response()->json([
            'comprobante' => $venta,
            'anulada' => (int) $venta->estado === 0,
        ]);
    }

    private function centavos($valor): int
    {
        $partes = explode('.', (string) $valor, 2);

        return ((int) $partes[0] * 100) + (int) str_pad($partes[1] ?? '', 2, '0');
    }

    private function importe(int $centavos): string
    {
        return intdiv($centavos, 100).'.'.str_pad((string) ($centavos % 100), 2, '0', STR_PAD_LEFT);
    }
}
