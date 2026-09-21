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
use Illuminate\Validation\ValidationException;

class VentaController extends Controller
{
public function index(Request $request)
    {
        $validated = $request->validate([
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde',
            'id_usuario' => 'nullable|exists:usuario,usuario_id',
            'codigo_venta' => 'nullable|string|max:32',
        ]);

        $query = Venta::with([
            'usuario',
            'cliente',
            'venta_detalles.producto'
        ]);

        if (!empty($validated['fecha_desde'])) {
            $query->whereDate(
                'fecha_hora_venta',
                '>=',
                $validated['fecha_desde']
            );
        }

        if (!empty($validated['fecha_hasta'])) {
            $query->whereDate(
                'fecha_hora_venta',
                '<=',
                $validated['fecha_hasta']
            );
        }

        if (!empty($validated['id_usuario'])) {
            $query->where(
                'id_usuario',
                $validated['id_usuario']
            );
        }

        if (!empty($validated['codigo_venta'])) {
            $query->where(
                'codigo_venta',
                'like',
                '%' . $validated['codigo_venta'] . '%'
            );
        }

        $ventas = $query
            ->orderBy('venta_id', 'desc')
            ->get();

        return response()->json($ventas);
    }


    /*
    |--------------------------------------------------------------------------
    | REGISTRAR VENTA
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_usuario' => 'required|exists:usuario,usuario_id',

            'id_cliente' => 'required|exists:cliente,cliente_id',

            'codigo_venta' =>
                'required|string|max:32|unique:venta,codigo_venta',

            'metodo_pago' =>
                'required|string|max:16',

            'fecha_hora_venta' =>
                'required|date',

            'descuento_venta' =>
                'nullable|numeric|min:0',

            'id_caja' =>
                'nullable|exists:caja,caja_id',

            // Una venta debe llevar por lo menos un producto
            'detalles' =>
                'required|array|min:1',

            'detalles.*.id_producto' =>
                'required|exists:producto,producto_id|distinct',

            'detalles.*.cantidad' =>
                'required|integer|min:1',
        ]);


        return DB::transaction(function () use ($validated) {

            /*
            |--------------------------------------------------------------------------
            | 1. VERIFICAR CAJA ABIERTA
            |--------------------------------------------------------------------------
            */

            $idCaja = $validated['id_caja'] ?? null;

            $queryCaja = Caja::where('estado_caja', 'Abierta')
                ->where('estado', 1);

            /*
             * Si el frontend manda una caja específica,
             * esa caja debe estar abierta.
             */
            if ($idCaja) {
                $queryCaja->where('caja_id', $idCaja);
            }

            $cajaAbierta = $queryCaja
                ->lockForUpdate()
                ->first();


            /*
            |--------------------------------------------------------------------------
            | NO PERMITIR VENTA SIN CAJA ABIERTA
            |--------------------------------------------------------------------------
            */

            if (!$cajaAbierta) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'No se puede realizar la venta porque no hay una caja abierta.'
                ], 409);
            }

            $idCaja = $cajaAbierta->caja_id;


            /*
            |--------------------------------------------------------------------------
            | 2. VALIDAR MÉTODO DE PAGO
            |--------------------------------------------------------------------------
            */

            $metodoPago = ucfirst(
                strtolower(trim($validated['metodo_pago']))
            );

            $metodosPermitidos = [
                'Efectivo',
                'Tarjeta',
                'Transferencia'
            ];

            if (!in_array($metodoPago, $metodosPermitidos)) {
                throw ValidationException::withMessages([
                    'metodo_pago' =>
                        'El método de pago debe ser Efectivo, Tarjeta o Transferencia.'
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | 3. VALIDAR PRODUCTOS Y CALCULAR SUBTOTAL
            |--------------------------------------------------------------------------
    
            */

            $subtotalVenta = 0;

            $detallesProcesados = [];


            foreach ($validated['detalles'] as $index => $item) {

                /*
                |--------------------------------------------------------------------------
                | BUSCAR PRODUCTO
                |--------------------------------------------------------------------------
                */

                $producto = Producto::where(
                        'producto_id',
                        $item['id_producto']
                    )
                    ->where('estado', 1)
                    ->lockForUpdate()
                    ->first();


                if (!$producto) {
                    throw ValidationException::withMessages([
                        "detalles.{$index}.id_producto" =>
                            'El producto no existe o se encuentra inactivo.'
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | 4. VALIDAR EXISTENCIA
                |--------------------------------------------------------------------------
                */

                if (
                    $item['cantidad']
                    >
                    $producto->existencia_bodega
                ) {
                    throw ValidationException::withMessages([
                        "detalles.{$index}.cantidad" =>
                            "Stock insuficiente para {$producto->nombre_producto}. " .
                            "Existencia disponible: {$producto->existencia_bodega}."
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | 5. OBTENER PRECIO DESDE PRODUCTO
                |--------------------------------------------------------------------------
                */

                $precioUnitario =
                    (float) $producto->precio_venta;

                $cantidad =
                    (int) $item['cantidad'];


                /*
                |--------------------------------------------------------------------------
                | CALCULAR SUBTOTAL DEL PRODUCTO
                |--------------------------------------------------------------------------
                */

                $subtotalDetalle = round(
                    $precioUnitario * $cantidad,
                    2
                );


                $subtotalVenta += $subtotalDetalle;


                /*
                 * Guardamos los datos temporalmente.
                 */
                $detallesProcesados[] = [
                    'producto' => $producto,
                    'cantidad' => $cantidad,
                    'subtotal_venta_detalle' => $subtotalDetalle,
                ];
            }


            $subtotalVenta = round(
                $subtotalVenta,
                2
            );


            /*
            |--------------------------------------------------------------------------
            | 6. DESCUENTO
            |--------------------------------------------------------------------------
            */

            $descuentoVenta =
                (float) ($validated['descuento_venta'] ?? 0);


            if ($descuentoVenta > $subtotalVenta) {
                throw ValidationException::withMessages([
                    'descuento_venta' =>
                        'El descuento no puede ser mayor que el subtotal de la venta.'
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | CALCULAR TOTAL
            |--------------------------------------------------------------------------
            | 
            */

            $totalVenta = round(
                $subtotalVenta - $descuentoVenta,
                2
            );


            /*
            |--------------------------------------------------------------------------
            | CREAR VENTA
            |--------------------------------------------------------------------------
            */

            $ventaData = [
                'id_usuario' =>
                    $validated['id_usuario'],

                'id_cliente' =>
                    $validated['id_cliente'],

                'codigo_venta' =>
                    $validated['codigo_venta'],

                'metodo_pago' =>
                    $metodoPago,

                'fecha_hora_venta' =>
                    $validated['fecha_hora_venta'],

                'subtotal_venta' =>
                    $subtotalVenta,

                'descuento_venta' =>
                    $descuentoVenta,

                'total_venta' =>
                    $totalVenta,

                /*
                 * La venta se registra activa/confirmada.
                 */
                'estado' => 1,
            ];


            $venta = Venta::create($ventaData);


            /*
            |--------------------------------------------------------------------------
            |CREAR DETALLES Y ACTUALIZAR INVENTARIO
            |--------------------------------------------------------------------------
            */

            foreach ($detallesProcesados as $detalle) {

                $producto =
                    $detalle['producto'];


                /*
                |--------------------------------------------------------------------------
                | CREAR DETALLE
                |--------------------------------------------------------------------------
                |
                | Según tu modelo Venta_detalle NO tiene precio_unitario.
                |
                */

                Venta_detalle::create([
                    'id_venta' =>
                        $venta->venta_id,

                    'id_producto' =>
                        $producto->producto_id,

                    'cantidad' =>
                        $detalle['cantidad'],

                    'subtotal_venta_detalle' =>
                        $detalle['subtotal_venta_detalle'],

                    'estado' => 1,
                ]);


                /*
                |--------------------------------------------------------------------------
                | STOCK ANTERIOR
                |--------------------------------------------------------------------------
                */

                $stockAnterior =
                    $producto->existencia_bodega;


                /*
                |--------------------------------------------------------------------------
                | STOCK NUEVO
                |--------------------------------------------------------------------------
                */

                $stockNuevo =
                    $stockAnterior
                    -
                    $detalle['cantidad'];


                /*
                |--------------------------------------------------------------------------
                | ACTUALIZAR PRODUCTO
                |--------------------------------------------------------------------------
                */

                $producto->update([
                    'existencia_bodega' =>
                        $stockNuevo
                ]);


                /*
                |--------------------------------------------------------------------------
                |REGISTRAR MOVIMIENTO DE INVENTARIO
                |--------------------------------------------------------------------------
                | 
                */

                Movimiento_inventario::create([
                    'id_producto' =>
                        $producto->producto_id,

                    'id_usuario' =>
                        $validated['id_usuario'],

                    'tipo_movimiento' =>
                        'Salida por Venta',

                    'cantidad_movimiento' =>
                        $detalle['cantidad'],

                    'stock_anterior_producto' =>
                        $stockAnterior,

                    'stock_resultante_producto' =>
                        $stockNuevo,

                    'fecha_movimiento' =>
                        $validated['fecha_hora_venta'],

                    'estado' => 1,
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | REGISTRAR MOVIMIENTO DE CAJA
            |--------------------------------------------------------------------------
            | 
            */

            Caja_movimiento_venta::create([
                'id_caja' =>
                    $idCaja,

                'id_venta' =>
                    $venta->venta_id,

                'monto_movimiento' =>
                    $totalVenta,

                'fecha_hora_movimiento' =>
                    $validated['fecha_hora_venta'],

                'estado' => 1,
            ]);


            /*
            |--------------------------------------------------------------------------
            |BITÁCORA
            |--------------------------------------------------------------------------
            */

            Bitacora::create([
                'id_usuario' =>
                    $validated['id_usuario'],

                'accion_bitacora' =>
                    'NUEVA_VENTA',

                'descripcion_bitacora' =>
                    "Venta {$venta->codigo_venta} registrada por total de C$ {$totalVenta}",

                'fecha_hora_bitacora' =>
                    now(),

                'estado' => 1,
            ]);


            /*
            |--------------------------------------------------------------------------
            | RESPUESTA
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,

                'message' =>
                    'Venta registrada correctamente.',

                'venta' =>
                    $venta->load([
                        'usuario',
                        'cliente',
                        'venta_detalles.producto'
                    ])
            ], 201);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | CONSULTAR UNA VENTA
    |--------------------------------------------------------------------------
    */
    public function show($id)
    {
        $venta = Venta::with([
            'usuario',
            'cliente',
            'venta_detalles.producto',
            'caja_movimiento_ventas'
        ])
        ->findOrFail($id);


        return response()->json($venta);
    }


    /*
    |--------------------------------------------------------------------------
    | MODIFICAR VENTA
    |--------------------------------------------------------------------------
    
    */
    public function update(Request $request, $id)
    {
        $venta = Venta::findOrFail($id);


        return response()->json([
            'success' => false,

            'message' =>
                'Una venta confirmada no puede modificarse directamente. ' .
                'Si existe un error, debe anularse y registrar una nueva venta.'
        ], 405);
    }


    /*
    |--------------------------------------------------------------------------
    | ANULAR VENTA
    |--------------------------------------------------------------------------
    |
    | No eliminamos físicamente la venta.
    |
    */
    public function destroy(Request $request, $id)
    {
        /*
         * Usuario que está realizando la anulación.
         * Se utiliza para la bitácora.
         */
        $validated = $request->validate([
            'id_usuario' =>
                'required|exists:usuario,usuario_id'
        ]);


        return DB::transaction(function () use (
            $id,
            $validated
        ) {

            /*
            |--------------------------------------------------------------------------
            | BUSCAR VENTA
            |--------------------------------------------------------------------------
            */

            $venta = Venta::with([
                'venta_detalles'
            ])
            ->lockForUpdate()
            ->findOrFail($id);


            /*
            |--------------------------------------------------------------------------
            | VALIDAR QUE NO ESTÉ ANULADA
            |--------------------------------------------------------------------------
            */

            if ($venta->estado == 0) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'La venta ya se encuentra anulada.'
                ], 409);
            }


            /*
            |--------------------------------------------------------------------------
            | DEVOLVER PRODUCTOS AL INVENTARIO
            |--------------------------------------------------------------------------
            */

            foreach ($venta->venta_detalles as $detalle) {

                $producto = Producto::where(
                        'producto_id',
                        $detalle->id_producto
                    )
                    ->lockForUpdate()
                    ->first();


                if (!$producto) {
                    throw ValidationException::withMessages([
                        'producto' =>
                            "No se encontró el producto {$detalle->id_producto} " .
                            "asociado a la venta."
                    ]);
                }


                $stockAnterior =
                    $producto->existencia_bodega;


                $stockNuevo =
                    $stockAnterior
                    +
                    $detalle->cantidad;


                /*
                |--------------------------------------------------------------------------
                | ACTUALIZAR INVENTARIO
                |--------------------------------------------------------------------------
                */

                $producto->update([
                    'existencia_bodega' =>
                        $stockNuevo
                ]);


                /*
                |--------------------------------------------------------------------------
                | MOVIMIENTO POR ANULACIÓN
                |--------------------------------------------------------------------------
                */

                Movimiento_inventario::create([
                    'id_producto' =>
                        $producto->producto_id,

                    'id_usuario' =>
                        $validated['id_usuario'],

                    'tipo_movimiento' =>
                        'Anulación de Venta',

                    'cantidad_movimiento' =>
                        $detalle->cantidad,

                    'stock_anterior_producto' =>
                        $stockAnterior,

                    'stock_resultante_producto' =>
                        $stockNuevo,

                    'fecha_movimiento' =>
                        now(),

                    'estado' => 1,
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | ANULAR DETALLES
            |--------------------------------------------------------------------------
            */

            Venta_detalle::where(
                'id_venta',
                $venta->venta_id
            )
            ->update([
                'estado' => 0
            ]);


            /*
            |--------------------------------------------------------------------------
            | ANULAR MOVIMIENTO DE CAJA
            |--------------------------------------------------------------------------
            |
            | Se conserva el registro pero queda inactivo.
            |
            */

            Caja_movimiento_venta::where(
                'id_venta',
                $venta->venta_id
            )
            ->update([
                'estado' => 0
            ]);


            /*
            |--------------------------------------------------------------------------
            | ANULAR VENTA
            |--------------------------------------------------------------------------
            */

            $venta->update([
                'estado' => 0
            ]);


            /*
            |--------------------------------------------------------------------------
            | REGISTRAR BITÁCORA
            |--------------------------------------------------------------------------
            */

            Bitacora::create([
                'id_usuario' =>
                    $validated['id_usuario'],

                'accion_bitacora' =>
                    'ANULAR_VENTA',

                'descripcion_bitacora' =>
                    "Venta {$venta->codigo_venta} anulada.",

                'fecha_hora_bitacora' =>
                    now(),

                'estado' => 1,
            ]);


            return response()->json([
                'success' => true,

                'message' =>
                    'Venta anulada correctamente.'
            ]);
        });
    }
}
