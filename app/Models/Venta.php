<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    //datos de la tabla venta
    protected $table = 'venta';
    protected $primaryKey = 'venta_id';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'id_cliente',
        'codigo_venta',
        'metodo_pago',
        'fecha_hora_venta',
        'subtotal_venta',
        'descuento_venta',
        'total_venta',
        'estado',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'usuario_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'cliente_id');
    }

    public function venta_detalles()
    {
        return $this->hasMany(Venta_detalle::class, 'id_venta', 'venta_id');
    }

    public function caja_movimiento_ventas()
    {
        return $this->hasMany(Caja_movimiento_venta::class, 'id_venta', 'venta_id');
    }
}
