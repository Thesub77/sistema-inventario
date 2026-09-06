<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    //datos de la tabla cliente
    protected $table = 'cliente';
    protected $primaryKey = 'cliente_id';
    public $timestamps = false;

    protected $fillable = [
        'codigo_cliente',
        'nombre_apellido_cliente',
        'telefono_cliente',
        'estado',
    ];

    public function ventas()
    {
        return $this->hasMany(Venta::class, 'id_cliente', 'cliente_id');
    }
}
