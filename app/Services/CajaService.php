<?php

namespace App\Services;

use App\Models\Bitacora;
use App\Models\Caja;
use App\Models\Caja_movimiento_venta;
use App\Models\Caja_operacion;
use App\Models\Usuario;

class CajaService
{
    public function autorizar(Usuario $usuario, ?Caja_operacion $operacion = null): void
    {
        abort_unless((int) $usuario->estado === 1, 403, 'El usuario está inactivo.');
        abort_unless($usuario->esAdmin() || $usuario->tienePermiso('cajas.gestionar') || $usuario->tienePermiso('pos.acceso'), 403, 'Sin acceso a cajas.');
        if ($operacion) {
            abort_unless($usuario->esAdmin() || (int) $operacion->id_usuario === (int) $usuario->usuario_id, 403, 'El turno pertenece a otro usuario.');
        }
    }

    // Todas las escrituras del turno bloquean primero la caja, dentro de una transacción.
    public function turno(Caja $caja, Usuario $usuario): Caja_operacion
    {
        $this->autorizar($usuario);
        abort_unless((int) $caja->estado === 1 && $caja->estado_caja === 'Abierta', 409, 'La caja no está activa y abierta.');
        $turnos = Caja_operacion::where('id_caja', $caja->caja_id)->whereNull('fecha_hora_cierre')->lockForUpdate()->get();
        abort_unless($turnos->count() === 1 && (int) $turnos->first()->estado === 1, 409, 'La caja debe tener exactamente un turno abierto válido.');
        $turno = $turnos->first();
        abort_if($turno->fecha_hora_apertura === null || $turno->monto_apertura === null, 409, 'El turno histórico no tiene fecha o monto de apertura; requiere conciliación.');
        $this->autorizar($usuario, $turno);

        return $turno;
    }

    public function paraVenta(?int $idCaja, Usuario $usuario): Caja_operacion
    {
        $this->autorizar($usuario);
        if (! $idCaja) {
            $candidatas = Caja_operacion::where('id_usuario', $usuario->usuario_id)
                ->where('estado', 1)->whereNull('fecha_hora_cierre')
                ->whereHas('caja', fn ($q) => $q->where('estado', 1)->where('estado_caja', 'Abierta'))
                ->pluck('id_caja');
            abort_unless($candidatas->count() === 1, 409, 'Indique la caja: debe existir un único turno abierto del usuario.');
            $idCaja = (int) $candidatas->first();
        }

        return $this->turno(Caja::lockForUpdate()->findOrFail($idCaja), $usuario);
    }

    public function paraAnular(int $idVenta, Usuario $usuario): void
    {
        $movimientos = Caja_movimiento_venta::where('id_venta', $idVenta)->get();
        abort_unless($movimientos->count() === 1 && $movimientos->first()->id_caja_operacion, 409, 'La venta no tiene un turno único identificado; requiere conciliación histórica.');
        $movimiento = $movimientos->first();
        $turno = $this->turno(Caja::lockForUpdate()->findOrFail($movimiento->id_caja), $usuario);
        abort_unless((int) $turno->caja_operacion_id === (int) $movimiento->id_caja_operacion, 409, 'No se puede anular una venta de un turno cerrado.');
    }

    public function arqueo(Caja_operacion $turno): array
    {
        $totales = ['Efectivo' => 0, 'Tarjeta' => 0, 'Transferencia' => 0];
        $movimientos = Caja_movimiento_venta::with(['venta' => fn ($query) => $query->lockForUpdate()])->where('id_caja_operacion', $turno->caja_operacion_id)->where('estado', 1)->lockForUpdate()->get();
        foreach ($movimientos as $movimiento) {
            $venta = $movimiento->venta;
            abort_unless($venta && (int) $venta->estado === 1 && array_key_exists($venta->metodo_pago, $totales), 409, 'Existe un movimiento inconsistente en el turno.');
            abort_unless((int) $movimiento->id_caja === (int) $turno->id_caja && $this->centavos($movimiento->monto_movimiento) === $this->centavos($venta->total_venta), 409, 'El movimiento no coincide con la caja o el total de la venta.');
            $totales[$venta->metodo_pago] += $this->centavos($movimiento->monto_movimiento);
            abort_if($totales[$venta->metodo_pago] > 999999999999999999, 409, 'El total excede la capacidad del arqueo.');
        }
        $esperado = $this->centavos($turno->monto_apertura) + $totales['Efectivo'];
        abort_if($esperado > 999999999999999999, 409, 'El efectivo excede la capacidad del arqueo.');

        return [
            'monto_apertura' => $this->importe($this->centavos($turno->monto_apertura)),
            'ventas_efectivo' => $this->importe($totales['Efectivo']),
            'ventas_tarjeta' => $this->importe($totales['Tarjeta']),
            'ventas_transferencia' => $this->importe($totales['Transferencia']),
            'monto_esperado' => $turno->monto_esperado ?? $this->importe($esperado),
            'monto_cierre' => $turno->monto_cierre,
            'diferencia' => $turno->diferencia,
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
        $partes = explode('.', (string) $valor, 2);

        return (int) $partes[0] * 100 + (int) str_pad($partes[1] ?? '', 2, '0');
    }

    public function importe(int $centavos): string
    {
        return ($centavos < 0 ? '-' : '').intdiv(abs($centavos), 100).'.'.str_pad((string) (abs($centavos) % 100), 2, '0', STR_PAD_LEFT);
    }
}
