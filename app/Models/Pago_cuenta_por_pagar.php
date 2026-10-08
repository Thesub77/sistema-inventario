<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago_cuenta_por_pagar extends Model
{
    protected $table = 'pago_cuenta_por_pagar';

    protected $primaryKey = 'pago_cuenta_por_pagar_id';

    protected $fillable = [
        'id_cuenta_por_pagar',
        'id_caja_movimiento_venta',
        'id_usuario',
        'monto_pago',
        'fecha_pago',
        'metodo_pago',
        'notas',
    ];

    protected $casts = [
        'fecha_pago' => 'datetime',
        'monto_pago' => 'float',
    ];

    /**
     * Cuenta por pagar a la que se abona.
     */
    public function cuentaPorPagar(): BelongsTo
    {
        return $this->belongsTo(Cuenta_por_pagar::class, 'id_cuenta_por_pagar', 'cuenta_por_pagar_id');
    }

    /**
     * Movimiento de caja asociado si el pago se realizó en efectivo desde turno.
     */
    public function cajaMovimiento(): BelongsTo
    {
        return $this->belongsTo(Caja_movimiento_venta::class, 'id_caja_movimiento_venta', 'caja_movimiento_venta_id');
    }

    /**
     * Usuario que registró el pago.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'usuario_id');
    }
}
