<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Caja_movimiento_venta;
use App\Models\Caja_operacion;
use App\Models\Cuenta_por_pagar;
use App\Models\Pago_cuenta_por_pagar;
use App\Models\Proveedor;
use App\Services\CajaService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CuentaPorPagarController extends Controller
{
    /**
     * Valida que el usuario sea Administrador o posea el permiso explícito.
     */
    private function authorizeAdmin(Request $request): void
    {
        $user = $request->user();
        $esAdmin = $user && method_exists($user, 'esAdmin') ? $user->esAdmin() : (strtolower($user->rol->nombre_rol ?? '') === 'administrador');
        if (! $esAdmin && ! ($user && method_exists($user, 'tienePermiso') && $user->tienePermiso('proveedores.gestionar'))) {
            abort(response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Solo administradores pueden gestionar proveedores y cuentas por pagar.',
            ], 403));
        }
    }

    /**
     * Listado con filtros avanzados de cuentas por pagar.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $query = Cuenta_por_pagar::with(['proveedor', 'pagos.usuario'])
            ->orderBy('fecha_vencimiento');

        if ($request->filled('id_proveedor')) {
            $query->where('id_proveedor', $request->input('id_proveedor'));
        }

        if ($request->filled('estado')) {
            $estado = $request->input('estado');
            if ($estado === 'Vencida') {
                $today = Carbon::today()->toDateString();
                $query->whereIn('estado', ['Pendiente', 'Parcial'])
                    ->whereDate('fecha_vencimiento', '<', $today);
            } else {
                $query->where('estado', $estado);
            }
        }

        if ($request->filled('buscar')) {
            $term = trim($request->input('buscar'));
            $query->where(function ($q) use ($term) {
                $q->where('numero_factura', 'like', "%{$term}%")
                    ->orWhere('descripcion', 'like', "%{$term}%")
                    ->orWhereHas('proveedor', function ($prov) use ($term) {
                        $prov->where('nombre_comercial', 'like', "%{$term}%");
                    });
            });
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_emision', '>=', $request->input('fecha_desde'));
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_emision', '<=', $request->input('fecha_hasta'));
        }

        $cuentas = $query->get()->map(function ($c) {
            $c->es_vencida = in_array($c->estado, ['Pendiente', 'Parcial']) && Carbon::parse($c->fecha_vencimiento)->isPast();

            return $c;
        });

        return response()->json($cuentas);
    }

    /**
     * Resumen de métricas clave (KPIs) de cuentas por pagar.
     */
    public function resumenKPIs(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $today = Carbon::today();
        $todayStr = $today->toDateString();
        $inSevenDays = $today->copy()->addDays(7)->toDateString();

        // Total deuda activa y facturas pendientes
        $pendientesQuery = Cuenta_por_pagar::whereIn('estado', ['Pendiente', 'Parcial']);
        $deudaActiva = (float) ($pendientesQuery->sum('saldo_pendiente') ?? 0.00);
        $facturasPendientesCount = (int) $pendientesQuery->count();

        // Facturas vencidas y monto vencido
        $vencidasQuery = Cuenta_por_pagar::whereIn('estado', ['Pendiente', 'Parcial'])
            ->whereDate('fecha_vencimiento', '<', $todayStr);
        $totalVencidas = (int) $vencidasQuery->count();
        $montoVencido = (float) ($vencidasQuery->sum('saldo_pendiente') ?? 0.00);

        // Próximos vencimientos (en los próximos 7 días)
        $proximosVencimientos = (int) Cuenta_por_pagar::whereIn('estado', ['Pendiente', 'Parcial'])
            ->whereDate('fecha_vencimiento', '>=', $todayStr)
            ->whereDate('fecha_vencimiento', '<=', $inSevenDays)
            ->count();

        // Total pagado en el mes corriente
        $startOfMonth = $today->copy()->startOfMonth()->toDateTimeString();
        $endOfMonth = $today->copy()->endOfMonth()->toDateTimeString();
        $totalPagadoMes = (float) (Pago_cuenta_por_pagar::whereBetween('fecha_pago', [$startOfMonth, $endOfMonth])->sum('monto_pago') ?? 0.00);

        $kpis = [
            'total_pendiente' => $deudaActiva,
            'facturas_pendientes_count' => $facturasPendientesCount,
            'total_vencido' => $montoVencido,
            'facturas_vencidas_count' => $totalVencidas,
            'proximos_vencimientos' => $proximosVencimientos,
            'total_pagado_mes' => $totalPagadoMes,
            // Aliases de retrocompatibilidad
            'total_deuda_activa' => $deudaActiva,
            'total_vencidas' => $totalVencidas,
            'monto_vencido' => $montoVencido,
        ];

        return response()->json(array_merge([
            'success' => true,
            'kpis' => $kpis,
        ], $kpis));
    }

    /**
     * Registro de una nueva cuenta por pagar (Factura de Proveedor).
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'id_proveedor' => ['required', 'integer', Rule::exists('proveedor', 'proveedor_id')->where('estado', 1)],
            'numero_factura' => [
                'required',
                'string',
                'min:1',
                'max:64',
                Rule::unique('cuenta_por_pagar', 'numero_factura')->where(function ($query) use ($request) {
                    return $query->where('id_proveedor', $request->input('id_proveedor'));
                }),
            ],
            'descripcion' => 'nullable|string|max:255',
            'fecha_emision' => 'required|date',
            'fecha_vencimiento' => 'required|date|after_or_equal:fecha_emision',
            'monto_total' => 'required|numeric|min:0.01|max:999999999.99',
        ], [
            'id_proveedor.required' => 'Debe seleccionar un proveedor activo.',
            'numero_factura.required' => 'El número de factura o documento del proveedor es obligatorio.',
            'numero_factura.unique' => 'Ya existe una factura registrada con este número para el proveedor seleccionado.',
            'fecha_emision.required' => 'La fecha de emisión es obligatoria.',
            'fecha_vencimiento.required' => 'La fecha de vencimiento es obligatoria.',
            'fecha_vencimiento.after_or_equal' => 'La fecha de vencimiento no puede ser anterior a la de emisión.',
            'monto_total.required' => 'El monto total de la factura es obligatorio.',
            'monto_total.min' => 'El monto de la factura debe ser mayor a 0.',
        ]);

        $montoTotal = round((float) $validated['monto_total'], 2);

        $cuenta = Cuenta_por_pagar::create([
            'id_proveedor' => $validated['id_proveedor'],
            'numero_factura' => trim($validated['numero_factura']),
            'descripcion' => ! empty($validated['descripcion']) ? trim($validated['descripcion']) : null,
            'fecha_emision' => $validated['fecha_emision'],
            'fecha_vencimiento' => $validated['fecha_vencimiento'],
            'monto_total' => $montoTotal,
            'monto_pagado' => 0.00,
            'saldo_pendiente' => $montoTotal,
            'estado' => 'Pendiente',
        ]);

        $cuenta->load('proveedor');

        Bitacora::create([
            'id_usuario' => $request->user()->usuario_id,
            'accion_bitacora' => 'CUENTA_POR_PAGAR_CREADA',
            'descripcion_bitacora' => "Se registró la factura #{$cuenta->numero_factura} del proveedor '{$cuenta->proveedor->nombre_comercial}' por C$ ".number_format($montoTotal, 2).'.',
            'fecha_hora_bitacora' => now(),
            'estado' => 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta por pagar registrada exitosamente.',
            'cuenta' => $cuenta,
        ], 201);
    }

    /**
     * Detalle de una cuenta por pagar individual junto a sus pagos.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        $cuenta = Cuenta_por_pagar::with(['proveedor', 'pagos.usuario', 'pagos.cajaMovimiento'])->findOrFail($id);

        return response()->json($cuenta);
    }

    /**
     * Actualización de datos descriptivos de una cuenta por pagar.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        $cuenta = Cuenta_por_pagar::findOrFail($id);

        $validated = $request->validate([
            'numero_factura' => [
                'required',
                'string',
                'min:1',
                'max:64',
                Rule::unique('cuenta_por_pagar', 'numero_factura')
                    ->where('id_proveedor', $cuenta->id_proveedor)
                    ->ignore($cuenta->cuenta_por_pagar_id, 'cuenta_por_pagar_id'),
            ],
            'descripcion' => 'nullable|string|max:255',
            'fecha_emision' => 'required|date',
            'fecha_vencimiento' => 'required|date|after_or_equal:fecha_emision',
        ], [
            'numero_factura.required' => 'El número de factura o documento del proveedor es obligatorio.',
            'numero_factura.unique' => 'Ya existe una factura registrada con este número para el proveedor seleccionado.',
            'fecha_emision.required' => 'La fecha de emisión es obligatoria.',
            'fecha_vencimiento.required' => 'La fecha de vencimiento es obligatoria.',
            'fecha_vencimiento.after_or_equal' => 'La fecha de vencimiento no puede ser anterior a la de emisión.',
        ]);

        $cuenta->update([
            'numero_factura' => trim($validated['numero_factura']),
            'descripcion' => array_key_exists('descripcion', $validated) ? trim((string) $validated['descripcion']) : $cuenta->descripcion,
            'fecha_emision' => $validated['fecha_emision'],
            'fecha_vencimiento' => $validated['fecha_vencimiento'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta por pagar actualizada exitosamente.',
            'cuenta' => $cuenta->load('proveedor'),
        ]);
    }

    /**
     * Anulación o eliminación de una cuenta por pagar si no tiene pagos registrados.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        $cuenta = Cuenta_por_pagar::withCount('pagos')->findOrFail($id);

        if ($cuenta->pagos_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar esta cuenta por pagar porque ya cuenta con abonos o pagos registrados.',
            ], 422);
        }

        $cuenta->delete();

        Bitacora::create([
            'id_usuario' => $request->user()->usuario_id,
            'accion_bitacora' => 'CUENTA_PAGAR_ELIMINADA',
            'descripcion_bitacora' => "Se eliminó la cuenta por pagar #{$cuenta->numero_factura}.",
            'fecha_hora_bitacora' => now(),
            'estado' => 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta por pagar eliminada correctamente.',
        ]);
    }

    /**
     * Registro de pago o abono a una cuenta por pagar con opción de débito directo en caja.
     */
    public function registrarPago(Request $request, $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'monto_pago' => 'required|numeric|min:0.01|max:999999999.99',
            'metodo_pago' => ['required', 'string', Rule::in(['Efectivo', 'Transferencia', 'Cheque', 'Otro'])],
            'debitar_de_caja' => 'sometimes|boolean',
            'registrar_en_caja' => 'sometimes|boolean',
            'id_caja_operacion' => 'nullable|integer|exists:caja_operacion,caja_operacion_id',
            'notas' => 'nullable|string|max:255',
            'nota' => 'nullable|string|max:255',
            'referencia_pago' => 'nullable|string|max:255',
        ], [
            'monto_pago.required' => 'El monto del abono o pago es obligatorio.',
            'monto_pago.min' => 'El monto del abono debe ser mayor a 0.',
            'metodo_pago.required' => 'Debe indicar el método de pago.',
        ]);

        $debitarDeCaja = $request->boolean('debitar_de_caja', $request->boolean('registrar_en_caja', false));
        $validated['debitar_de_caja'] = $debitarDeCaja;

        if (empty($validated['notas'])) {
            $partesNota = [];
            if (! empty($validated['referencia_pago'])) {
                $partesNota[] = 'Ref: '.trim($validated['referencia_pago']);
            }
            if (! empty($validated['nota'])) {
                $partesNota[] = trim($validated['nota']);
            }
            $validated['notas'] = ! empty($partesNota) ? implode(' - ', $partesNota) : null;
        }

        return DB::transaction(function () use ($validated, $request, $id) {
            $cuenta = Cuenta_por_pagar::lockForUpdate()->with('proveedor')->findOrFail($id);

            if ($cuenta->estado === 'Pagada' || $cuenta->saldo_pendiente <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta cuenta por pagar ya se encuentra completamente liquidada.',
                ], 422);
            }

            $montoAbono = round((float) $validated['monto_pago'], 2);
            if ($montoAbono > $cuenta->saldo_pendiente) {
                return response()->json([
                    'success' => false,
                    'message' => "El monto del abono (C$ {$montoAbono}) no puede superar el saldo pendiente (C$ {$cuenta->saldo_pendiente}).",
                ], 422);
            }

            $idCajaMovimiento = null;
            $usuario = $request->user();

            // Si se debita de caja (dinero en efectivo del turno)
            if ($validated['debitar_de_caja']) {
                $turno = null;
                if (! empty($validated['id_caja_operacion'])) {
                    $turno = Caja_operacion::lockForUpdate()->findOrFail($validated['id_caja_operacion']);
                } else {
                    $turno = Caja_operacion::where('id_usuario', $usuario->usuario_id)
                        ->where('estado', 1)
                        ->whereNull('fecha_hora_cierre')
                        ->lockForUpdate()
                        ->first();
                }

                if (! $turno || $turno->fecha_hora_cierre !== null || (int) $turno->estado !== 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No hay un turno de caja abierto para registrar la salida de efectivo. Inicie un turno de caja o desmarque la opción de debitar de caja.',
                    ], 422);
                }

                $cajaService = app(CajaService::class);
                $montoCentavos = $cajaService->centavos($montoAbono);
                $montoNegativo = $cajaService->importe(-$montoCentavos);

                $movimiento = Caja_movimiento_venta::create([
                    'id_caja' => $turno->id_caja,
                    'id_caja_operacion' => $turno->caja_operacion_id,
                    'id_venta' => null,
                    'monto_movimiento' => $montoNegativo, // Egreso
                    'fecha_hora_movimiento' => now(),
                    'estado' => 1,
                ]);

                $idCajaMovimiento = $movimiento->caja_movimiento_venta_id;

                Bitacora::create([
                    'id_usuario' => $usuario->usuario_id,
                    'accion_bitacora' => 'CAJA_PAGO_PROVEEDOR',
                    'descripcion_bitacora' => "Egreso de caja #{$movimiento->caja_movimiento_venta_id}: Pago a proveedor {$cuenta->proveedor->nombre_comercial} (Factura #{$cuenta->numero_factura}) por C$ ".number_format($montoAbono, 2),
                    'fecha_hora_bitacora' => now(),
                    'estado' => 1,
                ]);
            }

            // Registrar el pago
            $pago = Pago_cuenta_por_pagar::create([
                'id_cuenta_por_pagar' => $cuenta->cuenta_por_pagar_id,
                'id_caja_movimiento_venta' => $idCajaMovimiento,
                'id_usuario' => $usuario->usuario_id,
                'monto_pago' => $montoAbono,
                'fecha_pago' => now(),
                'metodo_pago' => $validated['metodo_pago'],
                'notas' => ! empty($validated['notas']) ? trim($validated['notas']) : null,
            ]);

            // Actualizar saldos de la cuenta por pagar
            $nuevoPagado = round((float) $cuenta->monto_pagado + $montoAbono, 2);
            $nuevoSaldo = max(0, round((float) $cuenta->monto_total - $nuevoPagado, 2));
            $nuevoEstado = ($nuevoSaldo <= 0.001) ? 'Pagada' : 'Parcial';

            $cuenta->update([
                'monto_pagado' => $nuevoPagado,
                'saldo_pendiente' => $nuevoSaldo,
                'estado' => $nuevoEstado,
            ]);

            Bitacora::create([
                'id_usuario' => $usuario->usuario_id,
                'accion_bitacora' => 'ABONO_CUENTA_POR_PAGAR',
                'descripcion_bitacora' => 'Se registró un abono de C$ '.number_format($montoAbono, 2)." a la factura #{$cuenta->numero_factura} ({$cuenta->proveedor->nombre_comercial}). Saldo restante: C$ ".number_format($nuevoSaldo, 2),
                'fecha_hora_bitacora' => now(),
                'estado' => 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => $nuevoEstado === 'Pagada' ? '¡Factura liquidada en su totalidad!' : 'Abono registrado exitosamente.',
                'cuenta' => $cuenta->fresh(['proveedor']),
                'pago' => $pago->load('usuario'),
            ], 201);
        });
    }

    /**
     * Listado de pagos asociados a una factura.
     */
    public function listarPagos(Request $request, $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        $pagos = Pago_cuenta_por_pagar::with(['usuario', 'cajaMovimiento.caja'])
            ->where('id_cuenta_por_pagar', $id)
            ->orderByDesc('fecha_pago')
            ->get();

        return response()->json($pagos);
    }
}
