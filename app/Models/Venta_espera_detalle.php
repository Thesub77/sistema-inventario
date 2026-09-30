<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta_espera_detalle extends Model
{
    protected $table = 'venta_espera_detalle';

    protected $primaryKey = 'venta_espera_detalle_id';

    public $timestamps = false;

    protected $fillable = [
        'id_venta_espera',
        'id_producto',
        'cantidad',
        'precio_unitario',
        'subtotal',
        'estado',
    ];

    public function venta_espera()
    {
        return $this->belongsTo(Venta_espera::class, 'id_venta_espera', 'venta_espera_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'producto_id');
    }
}
