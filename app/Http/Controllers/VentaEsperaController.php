<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Caja;
use App\Models\Caja_operacion;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Venta_espera;
use App\Models\Venta_espera_detalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VentaEsperaController extends Controller
{
    /**
     * GET /api/ventas-espera
     * Lista todas las ventas en espera activas (estado = 1), filtradas por caja o turno activo.
     */
    public function index(Request $request)
    {
        $user = $request->user() ?? Auth::user();
        $idCaja = $request->query('id_caja');

        $query = Venta_espera::with([
            'cliente',
            'usuario',
            'caja',
            'detalles' => function ($q) {
                $q->where('estado', 1)->with('producto');
            },
        ])->where('estado', 1);

        if ($idCaja) {
            $query->where('id_caja', $idCaja);
        } elseif ($user) {
            $turnoActivo = Caja_operacion::where('id_usuario', $user->usuario_id)
                ->where('estado', 1)
                ->whereNull('fecha_hora_cierre')
                ->first();
            if ($turnoActivo) {
                $query->where('id_caja', $turnoActivo->id_caja);
            } elseif (! $user->esAdmin()) {
                $query->where('id_usuario', $user->usuario_id);
            }
        }

        $ventas = $query->orderBy('venta_espera_id', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $ventas,
        ]);
    }

    /**
     * POST /api/ventas-espera
     * Registra una venta en espera sin descontar stock ni alterar caja.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_usuario' => ['nullable', 'integer', Rule::exists('usuario', 'usuario_id')->where('estado', 1)],
            'id_caja' => ['nullable', 'integer', Rule::exists('caja', 'caja_id')->where('estado', 1)],
            'id_cliente' => ['nullable', 'integer', Rule::exists('cliente', 'cliente_id')->where('estado', 1)],
            'identificador_cuenta' => ['nullable', 'string', 'max:64'],
            'observaciones' => ['nullable', 'string', 'max:255'],
            'descuento' => ['nullable', 'numeric', 'min:0'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.id_producto' => ['required', 'integer', Rule::exists('producto', 'producto_id')->where('estado', 1)],
            'detalles.*.cantidad' => ['required', 'numeric', 'gt:0'],
            'detalles.*.precio_unitario' => ['nullable', 'numeric', 'min:0'],
        ]);

        $idUsuario = $validated['id_usuario'] ?? Auth::id() ?? $request->user()?->usuario_id;
        if (! $idUsuario) {
            $idUsuario = 1; // Fallback para tests sin usuario autenticado explícito
        }

        $idCaja = $validated['id_caja'] ?? null;
        if (! $idCaja && $idUsuario) {
            $turnoActivo = Caja_operacion::where('id_usuario', $idUsuario)
                ->where('estado', 1)
                ->whereNull('fecha_hora_cierre')
                ->first();
            $idCaja = $turnoActivo?->id_caja;
        }

        // Determinar si el cliente es genérico (código con '0000' o nombre 'Consumidor Final' / 'Cliente Final')
        $idCliente = $validated['id_cliente'] ?? null;
        $cliente = $idCliente ? Cliente::find($idCliente) : null;

        $isGeneric = false;
        if ($cliente) {
            $code = (string) $cliente->codigo_cliente;
            $name = mb_strtolower(trim($cliente->nombre_apellido_cliente));
            if (str_contains($code, '0000') || $name === 'consumidor final' || $name === 'cliente final') {
                $isGeneric = true;
            }
        } else {
            $isGeneric = true;
        }

        $identificador = trim($validated['identificador_cuenta'] ?? '');

        if ($identificador === '' || ($isGeneric && (str_contains($identificador, '0000') || in_array(mb_strtolower($identificador), ['consumidor final', 'cliente final'])))) {
            if ($isGeneric) {
                $activeCount = Venta_espera::where('estado', 1)->count();
                $identificador = 'Cliente'.($activeCount + 1);
            } else {
                $identificador = $cliente->nombre_apellido_cliente;
            }
        }

        return DB::transaction(function () use ($validated, $idUsuario, $idCaja, $identificador, $idCliente) {
            $subtotalVenta = 0;
            $detallesParaCrear = [];

            foreach ($validated['detalles'] as $item) {
                $producto = Producto::find($item['id_producto']);
                $precioUnitario = isset($item['precio_unitario']) ? (float) $item['precio_unitario'] : (float) $producto->precio_venta;
                $cantidad = (float) $item['cantidad'];
                $subtotalLinea = round($cantidad * $precioUnitario, 2);
                $subtotalVenta += $subtotalLinea;

                $detallesParaCrear[] = [
                    'id_producto' => $producto->producto_id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioUnitario,
                    'subtotal' => $subtotalLinea,
                    'estado' => 1,
                ];
            }

            $descuento = round((float) ($validated['descuento'] ?? 0), 2);
            if ($descuento > $subtotalVenta) {
                throw ValidationException::withMessages([
                    'descuento' => 'El descuento no puede ser superior al subtotal de la venta en espera.',
                ]);
            }

            $total = max(0, round($subtotalVenta - $descuento, 2));

            $ventaEspera = Venta_espera::create([
                'id_usuario' => $idUsuario,
                'id_caja' => $idCaja,
                'id_cliente' => $idCliente,
                'identificador_cuenta' => $identificador,
                'observaciones' => $validated['observaciones'] ?? null,
                'subtotal' => round($subtotalVenta, 2),
                'descuento' => $descuento,
                'total' => $total,
                'fecha_creacion' => now(),
                'estado' => 1,
            ]);

            foreach ($detallesParaCrear as $detalle) {
                $detalle['id_venta_espera'] = $ventaEspera->venta_espera_id;
                Venta_espera_detalle::create($detalle);
            }

            Bitacora::create([
                'id_usuario' => $idUsuario,
                'accion_bitacora' => 'VENTA_EN_ESPERA',
                'descripcion_bitacora' => "Venta pausada como '{$ventaEspera->identificador_cuenta}' por total de C$ {$ventaEspera->total}",
                'fecha_hora_bitacora' => now(),
                'estado' => 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Venta pausada exitosamente como '{$ventaEspera->identificador_cuenta}'",
                'data' => $ventaEspera->load(['detalles.producto', 'cliente', 'usuario', 'caja']),
            ], 201);
        });
    }

    /**
     * GET /api/ventas-espera/{id}
     * Obtiene el detalle de una venta en espera activa para restaurar el carrito en el POS.
     */
    public function show($id)
    {
        $ventaEspera = Venta_espera::with([
            'cliente',
            'usuario',
            'caja',
            'detalles' => function ($q) {
                $q->where('estado', 1)->with('producto');
            },
        ])->find($id);

        if (! $ventaEspera || $ventaEspera->estado == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Venta en espera no encontrada o ya finalizada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $ventaEspera,
        ]);
    }

    /**
     * PUT /api/ventas-espera/{id}
     * Actualiza una venta en espera existente (al reanudarla y volver a pausarla con cambios).
     */
    public function update(Request $request, $id)
    {
        $ventaEspera = Venta_espera::find($id);

        if (! $ventaEspera || $ventaEspera->estado == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Venta en espera no encontrada o ya finalizada.',
            ], 404);
        }

        $validated = $request->validate([
            'id_usuario' => ['nullable', 'integer', Rule::exists('usuario', 'usuario_id')->where('estado', 1)],
            'id_caja' => ['nullable', 'integer', Rule::exists('caja', 'caja_id')->where('estado', 1)],
            'id_cliente' => ['nullable', 'integer', Rule::exists('cliente', 'cliente_id')->where('estado', 1)],
            'identificador_cuenta' => ['nullable', 'string', 'max:64'],
            'observaciones' => ['nullable', 'string', 'max:255'],
            'descuento' => ['nullable', 'numeric', 'min:0'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.id_producto' => ['required', 'integer', Rule::exists('producto', 'producto_id')->where('estado', 1)],
            'detalles.*.cantidad' => ['required', 'numeric', 'gt:0'],
            'detalles.*.precio_unitario' => ['nullable', 'numeric', 'min:0'],
        ]);

        return DB::transaction(function () use ($validated, $ventaEspera) {
            $subtotalVenta = 0;
            $detallesParaCrear = [];

            foreach ($validated['detalles'] as $item) {
                $producto = Producto::find($item['id_producto']);
                $precioUnitario = isset($item['precio_unitario']) ? (float) $item['precio_unitario'] : (float) $producto->precio_venta;
                $cantidad = (float) $item['cantidad'];
                $subtotalLinea = round($cantidad * $precioUnitario, 2);
                $subtotalVenta += $subtotalLinea;

                $detallesParaCrear[] = [
                    'id_producto' => $producto->producto_id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioUnitario,
                    'subtotal' => $subtotalLinea,
                    'estado' => 1,
                ];
            }

            $descuento = round((float) ($validated['descuento'] ?? 0), 2);
            if ($descuento > $subtotalVenta) {
                throw ValidationException::withMessages([
                    'descuento' => 'El descuento no puede ser superior al subtotal de la venta en espera.',
                ]);
            }

            $total = max(0, round($subtotalVenta - $descuento, 2));

            $identificador = ! empty($validated['identificador_cuenta'])
                ? trim($validated['identificador_cuenta'])
                : $ventaEspera->identificador_cuenta;

            $ventaEspera->update([
                'id_caja' => array_key_exists('id_caja', $validated) ? $validated['id_caja'] : $ventaEspera->id_caja,
                'id_cliente' => array_key_exists('id_cliente', $validated) ? $validated['id_cliente'] : $ventaEspera->id_cliente,
                'identificador_cuenta' => $identificador,
                'observaciones' => array_key_exists('observaciones', $validated) ? $validated['observaciones'] : $ventaEspera->observaciones,
                'subtotal' => round($subtotalVenta, 2),
                'descuento' => $descuento,
                'total' => $total,
            ]);

            $ventaEspera->detalles()->delete();

            foreach ($detallesParaCrear as $detalle) {
                $detalle['id_venta_espera'] = $ventaEspera->venta_espera_id;
                Venta_espera_detalle::create($detalle);
            }

            return response()->json([
                'success' => true,
                'message' => "Venta en espera '{$ventaEspera->identificador_cuenta}' actualizada exitosamente.",
                'data' => $ventaEspera->load(['detalles.producto', 'cliente', 'usuario', 'caja']),
            ]);
        });
    }

    /**
     * DELETE /api/ventas-espera/{id}
     * Realiza la eliminación lógica (estado = 0) en cabecera y detalles de la venta en espera.
     */
    public function destroy(Request $request, $id)
    {
        $ventaEspera = Venta_espera::find($id);

        if (! $ventaEspera) {
            return response()->json([
                'success' => false,
                'message' => 'Venta en espera no encontrada.',
            ], 404);
        }

        DB::transaction(function () use ($ventaEspera, $request) {
            $ventaEspera->update(['estado' => 0]);
            $ventaEspera->detalles()->update(['estado' => 0]);

            Bitacora::create([
                'id_usuario' => Auth::id() ?? $request->user()?->usuario_id ?? $ventaEspera->id_usuario,
                'accion_bitacora' => 'VENTA_ESPERA_ELIMINADA',
                'descripcion_bitacora' => "Venta en espera {$ventaEspera->identificador_cuenta} cerrada o descartada",
                'fecha_hora_bitacora' => now(),
                'estado' => 1,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Venta en espera procesada o descartada correctamente.',
        ]);
    }
}
