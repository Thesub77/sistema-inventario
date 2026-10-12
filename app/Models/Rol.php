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
        'bloqueado',
    ];

    protected $casts = [
        'permisos' => 'array',
        'estado' => 'integer',
        'bloqueado' => 'integer',
    ];

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'id_rol', 'rol_id');
    }
}
