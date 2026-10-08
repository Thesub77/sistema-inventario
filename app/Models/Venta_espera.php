<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta_espera extends Model
{
    protected $table = 'venta_espera';

    protected $primaryKey = 'venta_espera_id';

    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'id_caja',
        'cliente_nombre',
        'identificador_cuenta',
        'observaciones',
        'subtotal',
        'descuento',
        'total',
        'fecha_creacion',
        'estado',
    ];

    protected $casts = [
        'fecha_creacion' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'usuario_id');
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'id_caja', 'caja_id');
    }

    public function detalles()
    {
        return $this->hasMany(Venta_espera_detalle::class, 'id_venta_espera', 'venta_espera_id');
    }
}
