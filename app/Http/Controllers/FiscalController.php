<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador para la Generación de Reportes Fiscales y Libros de Ventas.
 *
 * Provee los endpoints necesarios para cumplir con las directrices de inspección
 * y control tributario de la Dirección General de Ingresos (DGI) para el
 * régimen simplificado de Cuota Fija (RF-27).
 */
class FiscalController extends Controller
{
    /**
     * Genera el reporte del Libro Diario de Ventas simplificado para Cuota Fija (RF-27).
     *
     * Agrupa y consolida día por día las ventas activas del mes y año solicitados:
     * - Fecha del día.
     * - Cantidad total de transacciones / ventas realizadas.
     * - Rango de comprobantes emitidos en el día (comprobante inicial y final).
     * - Total facturado del día.
     * - Total acumulado del mes consultado.
     *
     * Excluye estrictamente ventas anuladas (estado = 0).
     */
    public function libroDiario(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'mes' => 'required|integer|between:1,12',
            'anio' => 'required|integer|digits:4|min:2000|max:2100',
        ], [
            'mes.required' => 'El mes es obligatorio.',
            'mes.integer' => 'El mes debe ser un número entero.',
            'mes.between' => 'El mes debe estar entre 1 y 12.',
            'anio.required' => 'El año es obligatorio.',
            'anio.integer' => 'El año debe ser un número entero.',
            'anio.digits' => 'El año debe tener exactamente 4 dígitos.',
            'anio.min' => 'El año no puede ser menor a 2000.',
            'anio.max' => 'El año no puede ser mayor a 2100.',
        ]);

        $mes = (int) $datos['mes'];
        $anio = (int) $datos['anio'];

        // Consulta optimizada agrupando por día únicamente ventas activas (estado = 1)
        $registros = Venta::where('estado', 1)
            ->whereYear('fecha_hora_venta', $anio)
            ->whereMonth('fecha_hora_venta', $mes)
            ->selectRaw('
                DATE(fecha_hora_venta) as fecha,
                COUNT(*) as cantidad_transacciones,
                MIN(codigo_venta) as comprobante_inicial,
                MAX(codigo_venta) as comprobante_final,
                SUM(total_venta) as total_dia
            ')
            ->groupByRaw('DATE(fecha_hora_venta)')
            ->orderByRaw('DATE(fecha_hora_venta) ASC')
            ->get();

        $totalAcumuladoMes = 0;
        $totalTransaccionesMes = 0;

        $dias = $registros->map(function ($row) use (&$totalAcumuladoMes, &$totalTransaccionesMes) {
            $totalDia = (float) $row->total_dia;
            $cantidad = (int) $row->cantidad_transacciones;

            $totalAcumuladoMes += $totalDia;
            $totalTransaccionesMes += $cantidad;

            return [
                'fecha' => (string) $row->fecha,
                'cantidad_transacciones' => $cantidad,
                'comprobante_inicial' => $row->comprobante_inicial,
                'comprobante_final' => $row->comprobante_final,
                'total_dia' => number_format($totalDia, 2, '.', ''),
            ];
        });

        return response()->json([
            'success' => true,
            'anio' => $anio,
            'mes' => $mes,
            'total_acumulado_mes' => number_format($totalAcumuladoMes, 2, '.', ''),
            'total_transacciones_mes' => $totalTransaccionesMes,
            'dias' => $dias,
        ]);
    }
}
