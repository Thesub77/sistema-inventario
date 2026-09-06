<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Caja_movimiento_venta extends Model
{
    //datos de la tabla caja_movimiento_venta
    protected $table = 'caja_movimiento_venta';
    protected $primaryKey = 'caja_movimiento_venta_id';
    public $timestamps = false;

    protected $fillable = [
        'id_caja',
        'id_venta',
        'monto_movimiento',
        'fecha_hora_movimiento',
        'estado',
    ];

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'id_caja', 'caja_id');
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'id_venta', 'venta_id');
    }
}