<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    protected $table = 'rol';

    protected $primaryKey = 'rol_id';

    public $timestamps = false;

    protected $fillable = [
        'nombre_rol',
        'descripcion_rol',
        'permisos',
        'estado',
    ];

    protected $casts = [
        'permisos' => 'array',
    ];

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'id_rol', 'rol_id');
    }
}
