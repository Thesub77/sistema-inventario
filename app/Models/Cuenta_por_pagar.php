<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cuenta_por_pagar extends Model
{
    protected $table = 'cuenta_por_pagar';

    protected $primaryKey = 'cuenta_por_pagar_id';

    protected $fillable = [
        'id_proveedor',
        'numero_factura',
        'descripcion',
        'fecha_emision',
        'fecha_vencimiento',
        'monto_total',
        'monto_pagado',
        'saldo_pendiente',
        'estado',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'monto_total' => 'float',
        'monto_pagado' => 'float',
        'saldo_pendiente' => 'float',
    ];

    /**
     * Proveedor emisor de la factura.
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor', 'proveedor_id');
    }

    /**
     * Historial de pagos y abonos realizados.
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago_cuenta_por_pagar::class, 'id_cuenta_por_pagar', 'cuenta_por_pagar_id');
    }
}
