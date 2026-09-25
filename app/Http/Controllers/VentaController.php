<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Caja_movimiento_venta;
use App\Models\Movimiento_inventario;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\Venta_detalle;
use App\Services\CajaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * API de ventas: consulta, confirmación, comprobante y anulación lógica.
 *
 *
 *
 * Integración con cajas:
 * - Vender exige un turno abierto válido con fecha y monto real de apertura.
 * - Caja y turno del movimiento se obtienen del servicio, no del formulario.
 * - Una devolución de otro turno genera un egreso sin cambiar el cierre anterior.
 * - Solo el administrador puede autorizar una devolución sin turno disponible.
 *
 * Los errores de validación y CajaException se propagan al manejador de la API,
 * que conserva el estado HTTP y agrega success: false y message a errores 4xx.
 */
class VentaController extends Controller
{
    /**
     * GET /api/ventas: lista ventas, incluidas las anuladas, de más reciente a antigua.
     * Admite fechas inclusivas, usuario y coincidencia parcial del código de venta.
     * Devuelve un arreglo con usuario, cliente y detalles; no aplica paginación.
     */
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

    /**
     * POST /api/ventas: confirma una venta y devuelve success, message y venta (201).
     *
     *
     * CajaService selecciona un turno propio único o una caja abierta única si
     * no se indica id_caja. No crea aperturas ni presume un monto inicial cero.
     * Cualquier excepción revierte todas las escrituras de esta confirmación.
     */
    public function store(Request $request)
    {
        // El responsable real proviene del token, nunca del formulario.
        $request->merge(['id_usuario' => $request->user()->usuario_id]);

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

        return DB::transaction(function () use ($validated, $request) {

            // El servicio bloquea primero la caja y el turno para coordinar la venta
            // con otras ventas y con el cierre. Después se bloquean los productos.
            $turno = app(CajaService::class)->paraVenta(isset($validated['id_caja']) ? (int) $validated['id_caja'] : null, $request->user());
            // La fecha de entrada se valida por contrato; la fecha guardada es la del servidor.
            $validated['fecha_hora_venta'] = now();

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

            // Orden estable de bloqueo para reducir interbloqueos entre transacciones.
            // Primero se validan todas las líneas y se calculan sus importes en centavos.
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

                // Comprueba la capacidad antes de multiplicar y acumular el subtotal.
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

            // Con las líneas ya validadas, guarda el precio aplicado, descuenta stock
            // y registra las existencias anteriores y resultantes para auditoría.
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

            // Ambas referencias provienen del mismo turno validado, nunca del formulario.
            Caja_movimiento_venta::create([
                'id_caja_operacion' => $turno->caja_operacion_id,
                'id_caja' => $turno->id_caja,

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

    /**
     * GET /api/ventas/{id}: devuelve la venta con detalles y movimientos de caja.
     * Incluye ingresos y devoluciones relacionados; responde 404 si no existe.
     */
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

    /**
     * PUT/PATCH /api/ventas/{id}: rechaza cambios sobre una venta existente (405).
     * Para corregir una venta se debe anular y registrar otra; no se reescriben importes.
     */
    public function update(Request $request, $id)
    {
        $venta = Venta::findOrFail($id);

        return response()->json([
            'success' => false,

            'message' => 'Una venta confirmada no puede modificarse directamente. '.
                'Si existe un error, debe anularse y registrar una nueva venta.',
        ], 405);
    }

    /**
     * DELETE /api/ventas/{id}: anula lógicamente la venta y repone su inventario.
     * El id_caja opcional indica dónde registrar la devolución, no la caja original.
     * CajaService comprueba autorización y selecciona el turno de destino; si no
     * hay uno disponible, solo un administrador puede registrar el egreso sin turno.
     *
     *
     * Los detalles y la venta pasan a estado 0. La bitácora identifica al usuario
     * que anuló y el destino del egreso. Todo se confirma o revierte conjuntamente.
     * Los reintentos se rechazan (409) para impedir una segunda reposición de stock.
     */
    public function destroy(Request $request, $id)
    {
        // El responsable real proviene del token, nunca del formulario.
        $request->merge(['id_usuario' => $request->user()->usuario_id]);

        $validated = $request->validate([
            'id_usuario' => ['required', 'integer', Rule::exists('usuario', 'usuario_id')->where('estado', 1)],
            'id_caja' => 'sometimes|required|integer|exists:caja,caja_id',
        ]);

        return DB::transaction(function () use (
            $id,
            $validated,
            $request
        ) {

            Venta::findOrFail($id);
            // Bloquea las cajas antes de la venta y los productos. El servicio también
            // rechaza movimientos de origen ambiguos antes de modificar inventario.
            $turnoDevolucion = app(CajaService::class)->paraAnular((int) $id, $request->user(), isset($validated['id_caja']) ? (int) $validated['id_caja'] : null);

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

            // Se restituyen las cantidades originales incluso si el producto está
            // inactivo; se exige que exista y que la existencia no desborde su columna.
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

            $origen = Caja_movimiento_venta::with('turno')->where('id_venta', $venta->venta_id)->lockForUpdate()->firstOrFail();
            // null representa la autorización administrativa sin turno. Una referencia
            // distinta exige un egreso separado para no alterar el arqueo de origen.
            $registrarEgreso = $turnoDevolucion === null || $origen->id_caja_operacion === null
                || (int) $origen->id_caja_operacion !== (int) $turnoDevolucion->caja_operacion_id;
            // Los cierres emitidos conservan su ingreso; el egreso pertenece al presente.
            if ($origen->turno === null || ! $registrarEgreso) {
                $origen->update(['estado' => 0]);
            }

            $venta->update([
                'estado' => 0,
            ]);

            if ($registrarEgreso) {
                // La salida actual no reasigna el ingreso histórico a otro turno.
                $cajas = app(CajaService::class);
                Caja_movimiento_venta::create([
                    'id_caja' => $turnoDevolucion?->id_caja ?? $origen->id_caja,
                    'id_caja_operacion' => $turnoDevolucion?->caja_operacion_id,
                    'id_venta' => $venta->venta_id,
                    'monto_movimiento' => $cajas->importe(-$cajas->centavos($venta->total_venta)),
                    'fecha_hora_movimiento' => now(),
                    'estado' => 1,
                ]);
            }

            Bitacora::create([
                'id_usuario' => $validated['id_usuario'],

                'accion_bitacora' => 'ANULAR_VENTA',

                'descripcion_bitacora' => "Venta {$venta->codigo_venta} anulada.".($registrarEgreso
                    ? ($turnoDevolucion ? " Devolucion en turno #{$turnoDevolucion->caja_operacion_id}." : " Egreso sin turno autorizado por administrador en caja #{$origen->id_caja}.")
                    : ' Anulacion dentro del mismo turno.'),

                'fecha_hora_bitacora' => now(),

                'estado' => 1,
            ]);

            return response()->json([
                'success' => true,

                'message' => 'Venta anulada correctamente.',
            ]);
        });
    }

    /**
     * GET /api/ventas/{id}/comprobante: entrega datos JSON y la bandera anulada.
     * Los importes proceden de la venta guardada; no se recalculan con precios actuales.
     */
    public function comprobante($id)
    {
        // Los importes y precios son los guardados al confirmar, no los del catálogo actual.
        $venta = Venta::with(['usuario', 'cliente', 'venta_detalles.producto'])->findOrFail($id);

        return response()->json([
            'comprobante' => $venta,
            'anulada' => (int) $venta->estado === 0,
        ]);
    }

    /**
     * Convierte importes no negativos, con hasta dos decimales, a centavos enteros.
     * Se usa para precios y descuentos; evita operar con flotantes en los cálculos.
     * Los egresos negativos se convierten mediante CajaService, que maneja el signo.
     */
    private function centavos($valor): int
    {
        $partes = explode('.', (string) $valor, 2);

        return ((int) $partes[0] * 100) + (int) str_pad($partes[1] ?? '', 2, '0');
    }

    /** Convierte centavos no negativos a una cadena decimal con dos posiciones. */
    private function importe(int $centavos): string
    {
        return intdiv($centavos, 100).'.'.str_pad((string) ($centavos % 100), 2, '0', STR_PAD_LEFT);
    }
}
