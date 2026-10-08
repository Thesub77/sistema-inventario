<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    protected $table = 'proveedor';

    protected $primaryKey = 'proveedor_id';

    protected $fillable = [
        'nombre_comercial',
        'contacto_vendedor',
        'telefono',
        'plazo_credito_dias',
        'estado',
    ];

    protected $casts = [
        'plazo_credito_dias' => 'integer',
        'estado' => 'integer',
    ];

    /**
     * Facturas y deudas emitidas por este proveedor.
     */
    public function cuentasPorPagar(): HasMany
    {
        return $this->hasMany(Cuenta_por_pagar::class, 'id_proveedor', 'proveedor_id');
    }
}
