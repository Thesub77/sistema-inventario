<?php

namespace App\Services;

use App\Exceptions\CajaException;
use App\Models\Bitacora;
use App\Models\Caja;
use App\Models\Caja_movimiento_venta;
use App\Models\Caja_operacion;
use App\Models\Usuario;

class CajaService
{
    /**
     * Gestionar una caja requiere permiso de cajas/POS; gestionar un turno ajeno
     * además requiere ser administrador. Los permisos de venta se evalúan aparte.
     */
    public function autorizar(Usuario $usuario, ?Caja_operacion $operacion = null): void
    {
        CajaException::exigir((int) $usuario->estado === 1, 403, 'El usuario está inactivo.');
        CajaException::exigir($usuario->esAdmin() || $usuario->tienePermiso('cajas.gestionar') || $usuario->tienePermiso('pos.acceso'), 403, 'Sin acceso a cajas.');
        if ($operacion) {
            CajaException::exigir($usuario->esAdmin() || (int) $operacion->id_usuario === (int) $usuario->usuario_id, 403, 'El turno pertenece a otro usuario.');
        }
    }

    /**
     * Obtiene un turno para operaciones de su responsable o del administrador.
     * El llamador debe iniciar la transacción y bloquear primero la caja:
     * mantener ese orden serializa ventas, arqueos y cierres de la misma caja.
     */
    public function turno(Caja $caja, Usuario $usuario): Caja_operacion
    {
        $this->autorizar($usuario);
        $turno = $this->turnoAbierto($caja);
        $this->autorizar($usuario, $turno);

        return $turno;
    }

    /**
     * Exige una apertura registrada con fecha y monto real.
     * Si permitirFallback es true y la caja está activa y abierta pero no tiene un turno registrado,
     * inicializa transparentemente un turno operativo para evitar bloqueos en ventas directas.
     */
    private function turnoAbierto(Caja $caja, ?Usuario $usuario = null, bool $permitirFallback = false): Caja_operacion
    {
        CajaException::exigir((int) $caja->estado === 1 && $caja->estado_caja === 'Abierta', 409, 'La caja no está activa y abierta.');
        $turnos = Caja_operacion::where('id_caja', $caja->caja_id)->whereNull('fecha_hora_cierre')->lockForUpdate()->get();

        if ($turnos->isEmpty()) {
            if ($permitirFallback && $usuario) {
                return Caja_operacion::create([
                    'id_caja' => $caja->caja_id,
                    'id_usuario' => $usuario->usuario_id,
                    'fecha_hora_apertura' => now(),
                    'monto_apertura' => '0.00',
                    'estado' => 1,
                ]);
            }
            throw new CajaException(409, 'Registre el monto real de apertura de la caja para iniciar el turno antes de vender.');
        }

        CajaException::exigir($turnos->count() === 1 && (int) $turnos->first()->estado === 1, 409, 'La caja debe tener exactamente un turno abierto válido.');
        $turno = $turnos->first();
        if ($permitirFallback) {
            if ($turno->fecha_hora_apertura === null || $turno->monto_apertura === null) {
                $turno->fecha_hora_apertura ??= now();
                $turno->monto_apertura ??= '0.00';
                $turno->save();
            }
        } else {
            CajaException::rechazarSi($turno->fecha_hora_apertura === null || $turno->monto_apertura === null, 409, 'El turno no tiene fecha o monto real de apertura registrado.');
        }

        return $turno;
    }

    /**
     * Selecciona la caja y vincula la venta a un turno existente con apertura real o fallback operativo.
     * Requiere una transacción del llamador para conservar el bloqueo hasta
     * terminar la venta o revertirla si falla la validación de stock.
     */
    public function paraVenta(?int $idCaja, Usuario $usuario): Caja_operacion
    {
        CajaException::exigir((int) $usuario->estado === 1, 403, 'El usuario está inactivo.');
        CajaException::exigir($usuario->esAdmin() || $usuario->tienePermiso('ventas.crear') || $usuario->tienePermiso('pos.acceso'), 403, 'Sin permiso para registrar ventas.');
        if (! $idCaja) {
            $candidatas = Caja_operacion::where('id_usuario', $usuario->usuario_id)
                ->where('estado', 1)->whereNull('fecha_hora_cierre')
                ->whereHas('caja', fn ($q) => $q->where('estado', 1)->where('estado_caja', 'Abierta'))
                ->pluck('id_caja');
            // Conserva la selección del turno propio; si no tiene uno, busca una única caja abierta.
            if ($candidatas->isEmpty()) {
                $candidatas = Caja::where('estado', 1)->where('estado_caja', 'Abierta')->pluck('caja_id');
            }
            CajaException::exigir($candidatas->count() === 1, 409, 'Indique la caja: debe existir un único turno propio o una única caja activa y abierta.');
            $idCaja = (int) $candidatas->first();
        }

        // Vender no concede permiso para consultar o cerrar el turno de otro responsable.
        return $this->turnoAbierto(Caja::lockForUpdate()->findOrFail($idCaja), $usuario, true);
    }

    /**
     * Selecciona el turno que recibe la devolucion. Solo un administrador puede
     * autorizar un egreso sin turno; admite fallback operativo en caja abierta.
     * El llamador mantiene la transaccion hasta terminar la anulacion.
     */
    public function paraAnular(int $idVenta, Usuario $usuario, ?int $idCaja = null): ?Caja_operacion
    {
        $this->autorizar($usuario);
        $movimientos = Caja_movimiento_venta::where('id_venta', $idVenta)->get();
        CajaException::exigir($movimientos->count() === 1, 409, 'La venta debe tener un unico movimiento de origen.');
        $origen = $movimientos->first();
        if ($idCaja === null) {
            $candidatas = Caja_operacion::where('estado', 1)->whereNull('fecha_hora_cierre')
                ->whereHas('caja', fn ($q) => $q->where('estado', 1)->where('estado_caja', 'Abierta'));
            if (! $usuario->esAdmin()) {
                $candidatas->where('id_usuario', $usuario->usuario_id);
            }
            $ids = $candidatas->pluck('id_caja')->unique();
            if ($ids->contains($origen->id_caja)) {
                $idCaja = (int) $origen->id_caja;
            } elseif ($ids->count() === 1) {
                $idCaja = (int) $ids->first();
            } else {
                $cajasAbiertas = Caja::where('estado', 1)->where('estado_caja', 'Abierta')->pluck('caja_id');
                if ($cajasAbiertas->contains($origen->id_caja)) {
                    $idCaja = (int) $origen->id_caja;
                } elseif ($cajasAbiertas->count() === 1) {
                    $idCaja = (int) $cajasAbiertas->first();
                } else {
                    $idCaja = null;
                }
            }
        }

        if ($idCaja === null) {
            CajaException::exigir($usuario->esAdmin(), 409, 'Debe abrir una caja con monto real antes de registrar la devolucion.');

            return null;
        }

        // Orden estable entre cajas para devoluciones cruzadas concurrentes.
        $cajas = Caja::whereIn('caja_id', array_filter([$origen->id_caja, $idCaja]))
            ->orderBy('caja_id')->lockForUpdate()->get()->keyBy('caja_id');
        CajaException::exigir($cajas->has($origen->id_caja), 409, 'La caja de origen no existe.');
        CajaException::exigir($cajas->has($idCaja), 409, 'La caja de devolucion no existe.');

        $cajaDevolucion = $cajas->get($idCaja);
        $turno = $this->turnoAbierto($cajaDevolucion, $usuario, true);
        $this->autorizar($usuario, $turno);

        return $turno;
    }

    /**
     * Agrupa ventas por medio de pago; solo el efectivo incrementa el dinero
     * esperado en caja. Los cálculos usan centavos para evitar redondeos flotantes.
     * En turnos cerrados se conserva el monto esperado guardado durante el cierre.
     */
    public function arqueo(Caja_operacion $turno): array
    {
        $totales = ['Efectivo' => 0, 'Tarjeta' => 0, 'Transferencia' => 0];
        $devoluciones = $totales;
        $movimientos = Caja_movimiento_venta::with(['venta' => fn ($query) => $query->lockForUpdate()])->where('id_caja_operacion', $turno->caja_operacion_id)->where('estado', 1)->lockForUpdate()->get();
        foreach ($movimientos as $movimiento) {
            $venta = $movimiento->venta;
            CajaException::exigir($venta && array_key_exists($venta->metodo_pago, $totales), 409, 'Existe un movimiento inconsistente en el turno.');
            $monto = $this->centavos($movimiento->monto_movimiento);
            $totalVenta = $this->centavos($venta->total_venta);
            $esDevolucion = (int) $venta->estado === 0 && $monto <= 0;
            // Un ingreso compensado en otra caja o turno conserva su importe original.
            $ingresoCompensado = (int) $venta->estado === 0 && $monto >= 0
                && Caja_movimiento_venta::where('id_venta', $venta->venta_id)->where('estado', 1)
                    ->where('monto_movimiento', $this->importe(-$totalVenta))
                    ->where('caja_movimiento_venta_id', '!=', $movimiento->caja_movimiento_venta_id)
                    ->where(fn ($q) => $q->whereNull('id_caja_operacion')->orWhere('id_caja_operacion', '!=', $turno->caja_operacion_id))->exists();
            if ($ingresoCompensado) {
                $esDevolucion = false;
            }

            CajaException::exigir(($esDevolucion || $ingresoCompensado || ((int) $venta->estado === 1 && $monto >= 0)) && (int) $movimiento->id_caja === (int) $turno->id_caja && $monto === ($esDevolucion ? -$totalVenta : $totalVenta), 409, 'El movimiento no coincide con la caja o el total de la venta.');
            if ($esDevolucion) {
                $devoluciones[$venta->metodo_pago] += -$monto;
            } else {
                $totales[$venta->metodo_pago] += $monto;
            }
            CajaException::rechazarSi(max($totales[$venta->metodo_pago], $devoluciones[$venta->metodo_pago]) > 999999999999999999, 409, 'El total excede la capacidad del arqueo.');
        }
        $esperado = $this->centavos($turno->monto_apertura) + $totales['Efectivo'] - $devoluciones['Efectivo'];
        CajaException::rechazarSi(abs($esperado) > 999999999999999999, 409, 'El efectivo excede la capacidad del arqueo.');

        return [
            'monto_apertura' => $this->importe($this->centavos($turno->monto_apertura)),
            'ventas_efectivo' => $this->importe($totales['Efectivo']),
            'ventas_tarjeta' => $this->importe($totales['Tarjeta']),
            'ventas_transferencia' => $this->importe($totales['Transferencia']),
            'devoluciones_efectivo' => $this->importe($devoluciones['Efectivo']),
            'devoluciones_tarjeta' => $this->importe($devoluciones['Tarjeta']),
            'devoluciones_transferencia' => $this->importe($devoluciones['Transferencia']),
            'monto_esperado' => $turno->monto_esperado ?? $this->importe($esperado),
            'monto_cierre' => $turno->monto_cierre,
            'diferencia' => $turno->diferencia,
            // El saldo inicial real ya fue declarado al abrir. No se atribuyen ingresos
            // antiguos a este turno por sus fechas ni se altera el historial al consultar.
            'historial_sin_turno' => [
                'cantidad_movimientos' => Caja_movimiento_venta::where('id_caja', $turno->id_caja)
                    ->whereNull('id_caja_operacion')->count(),
                'incluido_en_monto_esperado' => false,
                'url_consulta' => '/api/caja-movimientos-venta?id_caja='.$turno->id_caja.'&sin_turno=1',
            ],
        ];
    }

    public function bitacora(Usuario $usuario, string $accion, Caja_operacion $turno): void
    {
        Bitacora::create([
            'id_usuario' => $usuario->usuario_id,
            'accion_bitacora' => $accion,
            'descripcion_bitacora' => "Caja #{$turno->id_caja}, turno #{$turno->caja_operacion_id}",
            'fecha_hora_bitacora' => now(),
            'estado' => 1,
        ]);
    }

    public function centavos($valor): int
    {
        $texto = (string) $valor;
        $signo = str_starts_with($texto, '-') ? -1 : 1;
        $partes = explode('.', ltrim($texto, '-+'), 2);

        return $signo * ((int) $partes[0] * 100 + (int) str_pad($partes[1] ?? '', 2, '0'));
    }

    public function importe(int $centavos): string
    {
        return ($centavos < 0 ? '-' : '').intdiv(abs($centavos), 100).'.'.str_pad((string) (abs($centavos) % 100), 2, '0', STR_PAD_LEFT);
    }
}
