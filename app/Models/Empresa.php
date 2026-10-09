<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $table = 'empresa';

    protected $primaryKey = 'empresa_id';

    public $timestamps = false;

    protected $fillable = [
        'nombre_comercial',
        'razon_social',
        'numero_ruc',
        'telefono_contacto',
        'correo_contacto',
        'direccion_fisica',
        'mensaje_pie_ticket',
        'moneda_simbolo',
        'estado',
    ];

    public function cajas()
    {
        return $this->hasMany(Caja::class, 'id_empresa', 'empresa_id');
    }
}
