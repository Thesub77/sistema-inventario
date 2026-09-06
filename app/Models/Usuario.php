<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $table = 'usuario';
    protected $primaryKey = 'usuario_id';
    public $timestamps = false;

    protected $fillable = [
        'id_rol',
        'nombre_apellido',
        'nombre_usuario',
        'contrasenia_usuario',
        'fecha_registro',
        'estado',
    ];

    protected $hidden = [
        'contrasenia_usuario',
    ];

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'rol_id');
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class, 'id_usuario', 'usuario_id');
    }

    public function bitacoras()
    {
        return $this->hasMany(Bitacora::class, 'id_usuario', 'usuario_id');
    }

    public function caja_operaciones()
    {
        return $this->hasMany(Caja_operacion::class, 'id_usuario', 'usuario_id');
    }

    public function movimiento_inventarios()
    {
        return $this->hasMany(Movimiento_inventario::class, 'id_usuario', 'usuario_id');
    }
}
