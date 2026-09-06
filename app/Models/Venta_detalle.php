<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta_detalle extends Model
{
    protected $table = 'venta_detalle';
    protected $primaryKey = 'id_venta_detalle';
    public $timestamps = false;

    protected $fillable = [
        'id_venta',
        'id_producto',
        'cantidad',
        'subtotal_venta_detalle',
        'precio_unitario',
        'estado',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'id_venta', 'venta_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'producto_id');
    }
}
