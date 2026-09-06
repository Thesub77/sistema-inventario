<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Caja_operacion extends Model
{
    protected $table = 'caja_operacion';
    protected $primaryKey = 'caja_operacion_id';
    public $timestamps = false;

    protected $fillable = [
        'id_caja',
        'id_usuario',
        'fecha_hora_apertura',
        'monto_apertura',
        'monto_cierre',
        'fecha_hora_cierre',
        'estado',
    ];

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'id_caja', 'caja_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'usuario_id');
    }
}
