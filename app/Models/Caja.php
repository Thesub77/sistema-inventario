<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Caja extends Model
{
    // datos de la tabla caja
    protected $table = 'caja';

    protected $primaryKey = 'caja_id';

    public $timestamps = false;

    protected $fillable = [
        'id_empresa',
        'descripcion_caja',
        'tipo_apertura',
        'estado_caja',
        'estado',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa', 'empresa_id');
    }

    public function caja_operaciones()
    {
        return $this->hasMany(Caja_operacion::class, 'id_caja', 'caja_id');
    }

    public function caja_movimiento_ventas()
    {
        return $this->hasMany(Caja_movimiento_venta::class, 'id_caja', 'caja_id');
    }
}
