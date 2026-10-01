<?php

namespace App\Http\Controllers;

use App\Models\Movimiento_inventario;
use App\Models\Producto;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controlador del Panel de Control Analítico y Operativo (Dashboard).
 *
 * Procesa y consolida métricas financieras, operativas y de inventario del negocio:
 * - RF-26: Métricas de ventas del turno y desglose por método de pago.
 * - RF-29: Análisis de rentabilidad real (utilidad bruta mensual y top productos por margen).
 * - RF-30: Flujo de demanda horaria (patrones de consumo por hora del día) y últimas transacciones.
 * - RF-31: Gráficos de tendencias de ventas (últimos 7 días y últimas 4 semanas).
 * - RF-32: Ranking de los 5 productos más vendidos por volumen.
 * - RF-33: Detección de productos de baja/nula rotación y cuantificación de capital inmovilizado.
 * - RF-13: Alertas de stock bajo (críticos, urgentes y advertencias).
 */
class DashboardController extends Controller
{
    /**
     * Retorna todas las métricas analíticas y operativas del Dashboard
     * procesadas directamente en el motor de base de datos para máximo rendimiento.
     */
    public function resumen(Request $request): JsonResponse
    {
        $fechaHoy = $request->query('fecha', Carbon::today()->toDateString());
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHoy)) {
            $fechaHoy = Carbon::today()->toDateString();
        }

        // 1. Métricas de Ventas del Turno (Hoy) - RF-26
        $ventasHoyQuery = Venta::query()
            ->where('estado', 1)
            ->whereDate('fecha_hora_venta', $fechaHoy);

        $metodosVentas = (clone $ventasHoyQuery)
            ->selectRaw('LOWER(metodo_pago) as metodo, COUNT(*) as cantidad, SUM(total_venta) as total')
            ->groupBy(DB::raw('LOWER(metodo_pago)'))
            ->get()
            ->keyBy('metodo');

        $efectivo = (float) ($metodosVentas->get('efectivo')?->total ?? 0);
        $countEfectivo = (int) ($metodosVentas->get('efectivo')?->cantidad ?? 0);

        $transferencia = (float) ($metodosVentas->get('transferencia')?->total ?? 0);
        $countTransferencia = (int) ($metodosVentas->get('transferencia')?->cantidad ?? 0);

        $tarjeta = (float) ($metodosVentas->get('tarjeta')?->total ?? 0);
        $countTarjeta = (int) ($metodosVentas->get('tarjeta')?->cantidad ?? 0);

        $totalTurno = $efectivo + $transferencia + $tarjeta;
        $totalTickets = $countEfectivo + $countTransferencia + $countTarjeta;

        $safeTotal = $totalTurno > 0 ? $totalTurno : 1;
        $pctEfectivo = $totalTurno > 0 ? (int) round(($efectivo / $safeTotal) * 100) : 0;
        $pctTransferencia = $totalTurno > 0 ? (int) round(($transferencia / $safeTotal) * 100) : 0;
        $pctTarjeta = $totalTurno > 0 ? (int) round(($tarjeta / $safeTotal) * 100) : 0;

        $ventasTurnoStats = [
            'total' => $totalTurno,
            'totalTickets' => $totalTickets,
            'efectivo' => $efectivo,
            'countEfectivo' => $countEfectivo,
            'pctEfectivo' => $pctEfectivo,
            'transferencia' => $transferencia,
            'countTransferencia' => $countTransferencia,
            'pctTransferencia' => $pctTransferencia,
            'tarjeta' => $tarjeta,
            'countTarjeta' => $countTarjeta,
            'pctTarjeta' => $pctTarjeta,
        ];

        // 2. Estadísticas Generales (KPIs) - RF-29, RF-30 y RF-32
        $carbonHoy = Carbon::parse($fechaHoy);
        $inicioMes = (clone $carbonHoy)->startOfMonth()->toDateString();
        $finMes = (clone $carbonHoy)->endOfMonth()->toDateString();

        $totalPerdidasMermasMes = (float) (Movimiento_inventario::query()
            ->where('estado', 1)
            ->where('tipo_movimiento', 'Salida por Merma')
            ->whereDate('fecha_movimiento', '>=', $inicioMes)
            ->whereDate('fecha_movimiento', '<=', $finMes)
            ->sum('costo_total_perdida') ?? 0);

        // Ganancia líquida real en córdobas del mes (RF-29)
        $utilidadBrutaMes = (float) (DB::table('venta_detalle as vd')
            ->join('venta as v', 'vd.id_venta', '=', 'v.venta_id')
            ->join('producto as p', 'vd.id_producto', '=', 'p.producto_id')
            ->where('v.estado', 1)
            ->whereDate('v.fecha_hora_venta', '>=', $inicioMes)
            ->whereDate('v.fecha_hora_venta', '<=', $finMes)
            ->sum(DB::raw('(vd.subtotal_venta_detalle) - (p.costo_compra * vd.cantidad)')) ?? 0);

        $stats = [
            'totalVentasMonto' => (float) (Venta::where('estado', 1)->sum('total_venta') ?? 0),
            'totalVentasCount' => (int) Venta::where('estado', 1)->count(),
            'totalProductos' => (int) Producto::where('estado', 1)->count(),
            'totalUnidades' => (int) (Producto::where('estado', 1)->sum('existencia_bodega') ?? 0),
            'totalPerdidasMermasMes' => round($totalPerdidasMermasMes, 2),
            'utilidad_bruta_mes' => round($utilidadBrutaMes, 2),
            'utilidadBrutaMes' => round($utilidadBrutaMes, 2),
        ];

        // 3. Alertas de Stock Bajo - RF-13
        $productosAlerta = Producto::with('categoria:categoria_id,nombre_categoria')
            ->where('estado', 1)
            ->whereColumn('existencia_bodega', '<=', 'existencia_minima')
            ->orderBy('existencia_bodega', 'asc')
            ->get();

        $criticos = [];
        $urgentes = [];
        $advertencias = [];
        $todosAlertas = [];

        foreach ($productosAlerta as $p) {
            $stock = (int) $p->existencia_bodega;
            $min = (int) $p->existencia_minima;
            $pct = $min > 0 ? min(100, (int) round(($stock / $min) * 100)) : ($stock > 0 ? 100 : 0);

            $item = [
                'producto_id' => $p->producto_id,
                'codigo_producto' => $p->codigo_producto,
                'nombre_producto' => $p->nombre_producto,
                'stockActual' => $stock,
                'stockMinimo' => $min,
                'porcentaje' => $pct,
                'categoriaNombre' => $p->categoria?->nombre_categoria ?? 'General',
                'existencia_bodega' => $stock,
                'existencia_minima' => $min,
                'costo_compra' => (float) $p->costo_compra,
                'precio_venta' => (float) $p->precio_venta,
            ];

            if ($stock <= 0) {
                $criticos[] = $item;
            } elseif ($stock <= (int) ceil($min / 2.0)) {
                $urgentes[] = $item;
            } else {
                $advertencias[] = $item;
            }
            $todosAlertas[] = $item;
        }

        $stockAlerts = [
            'criticos' => $criticos,
            'urgentes' => $urgentes,
            'advertencias' => $advertencias,
            'totalAlertas' => count($todosAlertas),
            'todos' => $todosAlertas,
        ];

        // 4. Top 5 Productos más Vendidos - RF-32
        $topQuery = DB::table('venta_detalle as vd')
            ->join('venta as v', 'vd.id_venta', '=', 'v.venta_id')
            ->join('producto as p', 'vd.id_producto', '=', 'p.producto_id')
            ->leftJoin('categoria as c', 'p.id_categoria', '=', 'c.categoria_id')
            ->where('v.estado', 1)
            ->select(
                'p.producto_id',
                'p.codigo_producto',
                'p.nombre_producto',
                'p.precio_venta',
                'p.existencia_bodega',
                DB::raw('COALESCE(c.nombre_categoria, \'General\') as categoria_nombre'),
                DB::raw('SUM(vd.cantidad) as cantidad_vendida'),
                DB::raw('SUM(vd.subtotal_venta_detalle) as total_recaudado')
            )
            ->groupBy(
                'p.producto_id',
                'p.codigo_producto',
                'p.nombre_producto',
                'p.precio_venta',
                'p.existencia_bodega',
                'c.nombre_categoria'
            )
            ->orderByDesc('cantidad_vendida')
            ->limit(5)
            ->get();

        $maxCantidad = $topQuery->isNotEmpty() ? (int) $topQuery->first()->cantidad_vendida : 1;
        if ($maxCantidad <= 0) {
            $maxCantidad = 1;
        }

        $topProductosVendidos = [];
        foreach ($topQuery as $idx => $p) {
            $cant = (int) $p->cantidad_vendida;
            $topProductosVendidos[] = [
                'producto_id' => $p->producto_id,
                'posicion' => $idx + 1,
                'codigo' => $p->codigo_producto,
                'nombre' => $p->nombre_producto,
                'categoria' => $p->categoria_nombre,
                'precio' => (float) $p->precio_venta,
                'stock' => (int) $p->existencia_bodega,
                'cantidadVendida' => $cant,
                'totalRecaudado' => (float) $p->total_recaudado,
                'porcentajeRelativo' => (int) round(($cant / $maxCantidad) * 100),
            ];
        }

        // 5. Top Productos por Rentabilidad y Margen Real (RF-29)
        $topRentabilidadQuery = DB::table('venta_detalle as vd')
            ->join('venta as v', 'vd.id_venta', '=', 'v.venta_id')
            ->join('producto as p', 'vd.id_producto', '=', 'p.producto_id')
            ->leftJoin('categoria as c', 'p.id_categoria', '=', 'c.categoria_id')
            ->where('v.estado', 1)
            ->select(
                'p.producto_id',
                'p.codigo_producto',
                'p.nombre_producto',
                'p.precio_venta',
                'p.costo_compra',
                DB::raw('COALESCE(c.nombre_categoria, \'General\') as categoria_nombre'),
                DB::raw('SUM(vd.cantidad) as cantidad_vendida'),
                DB::raw('SUM(vd.subtotal_venta_detalle) as total_recaudado'),
                DB::raw('SUM((vd.subtotal_venta_detalle) - (p.costo_compra * vd.cantidad)) as utilidad_total')
            )
            ->groupBy(
                'p.producto_id',
                'p.codigo_producto',
                'p.nombre_producto',
                'p.precio_venta',
                'p.costo_compra',
                'c.nombre_categoria'
            )
            ->orderByDesc('utilidad_total')
            ->limit(5)
            ->get();

        $topRentabilidad = [];
        foreach ($topRentabilidadQuery as $idx => $p) {
            $utilidadTotal = (float) $p->utilidad_total;
            $totalRecaudado = (float) $p->total_recaudado;
            $margenPct = $totalRecaudado > 0 ? round(($utilidadTotal / $totalRecaudado) * 100, 2) : 0.0;

            $topRentabilidad[] = [
                'producto_id' => $p->producto_id,
                'posicion' => $idx + 1,
                'codigo' => $p->codigo_producto,
                'nombre' => $p->nombre_producto,
                'categoria' => $p->categoria_nombre,
                'precio' => (float) $p->precio_venta,
                'costo' => (float) $p->costo_compra,
                'cantidadVendida' => (int) $p->cantidad_vendida,
                'totalRecaudado' => $totalRecaudado,
                'utilidad_total' => round($utilidadTotal, 2),
                'utilidadTotal' => round($utilidadTotal, 2),
                'margen_pct' => $margenPct,
                'margenPct' => $margenPct,
            ];
        }

        // 6. Productos con Baja o Nula Rotación y Capital Inmovilizado - RF-33
        $subqueryVentas = DB::table('venta_detalle as vd')
            ->join('venta as v', 'vd.id_venta', '=', 'v.venta_id')
            ->where('v.estado', 1)
            ->select('vd.id_producto', DB::raw('SUM(vd.cantidad) as total_vendido'))
            ->groupBy('vd.id_producto');

        $bajaRotacionQuery = DB::table('producto as p')
            ->leftJoinSub($subqueryVentas, 'sv', function ($join) {
                $join->on('p.producto_id', '=', 'sv.id_producto');
            })
            ->leftJoin('categoria as c', 'p.id_categoria', '=', 'c.categoria_id')
            ->where('p.estado', 1)
            ->where(function ($query) {
                $query->whereNull('sv.total_vendido')
                    ->orWhere('sv.total_vendido', '<=', 2);
            })
            ->select(
                'p.producto_id',
                'p.codigo_producto',
                'p.nombre_producto',
                'p.existencia_bodega',
                'p.existencia_minima',
                'p.costo_compra',
                'p.precio_venta',
                DB::raw('COALESCE(c.nombre_categoria, \'General\') as categoria_nombre'),
                DB::raw('COALESCE(sv.total_vendido, 0) as unidades_vendidas'),
                DB::raw('(p.existencia_bodega * p.costo_compra) as capital_inmovilizado')
            )
            ->orderByDesc('capital_inmovilizado')
            ->get();

        $productosBajaRotacion = [];
        $capitalInmovilizadoTotal = 0.0;
        foreach ($bajaRotacionQuery as $p) {
            $unidades = (int) $p->unidades_vendidas;
            $capital = (float) $p->capital_inmovilizado;
            $capitalInmovilizadoTotal += $capital;

            $productosBajaRotacion[] = [
                'producto_id' => $p->producto_id,
                'codigo_producto' => $p->codigo_producto,
                'nombre_producto' => $p->nombre_producto,
                'categoriaNombre' => $p->categoria_nombre,
                'existencia_bodega' => (int) $p->existencia_bodega,
                'existencia_minima' => (int) $p->existencia_minima,
                'costo_compra' => (float) $p->costo_compra,
                'precio_venta' => (float) $p->precio_venta,
                'capitalInmovilizado' => $capital,
                'unidadesVendidas' => $unidades,
                'tipoRotacion' => $unidades === 0 ? 'sin_ventas' : 'poca_rotacion',
            ];
        }

        // 7. Gráficos de Ventas por Días y Semanas - RF-31
        // Últimos 7 Días
        $diasMap = [];
        $carbonHoy = Carbon::parse($fechaHoy);
        for ($i = 6; $i >= 0; $i--) {
            $d = (clone $carbonHoy)->subDays($i);
            $key = $d->toDateString();
            $diasMap[$key] = [
                'label' => $d->format('d/m'),
                'monto' => 0.0,
                'tickets' => 0,
            ];
        }

        $fechaInicio7 = (clone $carbonHoy)->subDays(6)->toDateString();
        $ventas7Dias = Venta::query()
            ->where('estado', 1)
            ->whereDate('fecha_hora_venta', '>=', $fechaInicio7)
            ->whereDate('fecha_hora_venta', '<=', $fechaHoy)
            ->selectRaw('DATE(fecha_hora_venta) as fecha, COUNT(*) as tickets, SUM(total_venta) as monto')
            ->groupBy(DB::raw('DATE(fecha_hora_venta)'))
            ->get();

        foreach ($ventas7Dias as $row) {
            $f = substr((string) $row->fecha, 0, 10);
            if (isset($diasMap[$f])) {
                $diasMap[$f]['monto'] = (float) $row->monto;
                $diasMap[$f]['tickets'] = (int) $row->tickets;
            }
        }

        // Últimas 4 Semanas
        $semanasLabels = ['Semana 1', 'Semana 2', 'Semana 3', 'Semana 4 (Actual)'];
        $semanasMonto = [0.0, 0.0, 0.0, 0.0];
        $semanasTickets = [0, 0, 0, 0];

        $fechaInicio28 = (clone $carbonHoy)->subDays(27)->toDateString();
        $ventas28Dias = Venta::query()
            ->where('estado', 1)
            ->whereDate('fecha_hora_venta', '>=', $fechaInicio28)
            ->whereDate('fecha_hora_venta', '<=', $fechaHoy)
            ->get(['fecha_hora_venta', 'total_venta']);

        foreach ($ventas28Dias as $v) {
            $vDate = Carbon::parse($v->fecha_hora_venta);
            $diffDays = $carbonHoy->diffInDays($vDate);
            if ($diffDays >= 0 && $diffDays < 28) {
                $semIndex = 3 - (int) floor($diffDays / 7);
                if ($semIndex >= 0 && $semIndex <= 3) {
                    $semanasMonto[$semIndex] += (float) $v->total_venta;
                    $semanasTickets[$semIndex] += 1;
                }
            }
        }

        $chartVentas = [
            'dias' => [
                'labels' => array_values(array_column($diasMap, 'label')),
                'dataMonto' => array_values(array_column($diasMap, 'monto')),
                'dataTickets' => array_values(array_column($diasMap, 'tickets')),
            ],
            'semanas' => [
                'labels' => $semanasLabels,
                'dataMonto' => $semanasMonto,
                'dataTickets' => $semanasTickets,
            ],
        ];

        // 8. Flujo de Demanda Horaria y Patrones de Consumo 
        $horasMap = [];
        for ($h = 0; $h < 24; $h++) {
            $horaKey = str_pad((string) $h, 2, '0', STR_PAD_LEFT).':00';
            $horasMap[$h] = [
                'hora' => $h,
                'label' => $horaKey,
                'monto' => 0.0,
                'tickets' => 0,
            ];
        }

        $ventasHorarias = (clone $ventasHoyQuery)->get(['fecha_hora_venta', 'total_venta']);
        foreach ($ventasHorarias as $v) {
            $hora = (int) Carbon::parse($v->fecha_hora_venta)->format('G');
            if (isset($horasMap[$hora])) {
                $horasMap[$hora]['monto'] += (float) $v->total_venta;
                $horasMap[$hora]['tickets'] += 1;
            }
        }

        $distribucionHoraria = [
            'labels' => array_values(array_column($horasMap, 'label')),
            'dataMonto' => array_values(array_map(fn ($item) => round($item['monto'], 2), $horasMap)),
            'dataTickets' => array_values(array_column($horasMap, 'tickets')),
            'horas' => array_values($horasMap),
        ];

        // 9. Últimas 6 Ventas Emitidas - RF-30
        $ultimasVentas = Venta::with('cliente:cliente_id,nombre_apellido_cliente')
            ->where('estado', 1)
            ->orderByDesc('venta_id')
            ->limit(6)
            ->get()
            ->map(function ($v) {
                return [
                    'venta_id' => $v->venta_id,
                    'codigo_venta' => $v->codigo_venta,
                    'cliente_nombre' => $v->cliente?->nombre_apellido_cliente ?? 'Consumidor Final',
                    'metodo_pago' => $v->metodo_pago,
                    'fecha_hora_venta' => $v->fecha_hora_venta,
                    'total_venta' => (float) $v->total_venta,
                ];
            });

        return response()->json([
            'success' => true,
            'fecha' => $fechaHoy,
            'ventasTurnoStats' => $ventasTurnoStats,
            'stats' => $stats,
            'utilidad_bruta_mes' => round($utilidadBrutaMes, 2),
            'utilidadBrutaMes' => round($utilidadBrutaMes, 2),
            'totalPerdidasMermasMes' => round($totalPerdidasMermasMes, 2),
            'stockAlerts' => $stockAlerts,
            'topProductosVendidos' => $topProductosVendidos,
            'topRentabilidad' => $topRentabilidad,
            'productosBajaRotacion' => $productosBajaRotacion,
            'capitalInmovilizadoTotal' => $capitalInmovilizadoTotal,
            'chartVentas' => $chartVentas,
            'distribucionHoraria' => $distribucionHoraria,
            'demandaHoraria' => $distribucionHoraria,
            'ultimasVentas' => $ultimasVentas,
        ]);
    }
}
