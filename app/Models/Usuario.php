<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, Notifiable;

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

    public function getAuthPassword()
    {
        return $this->contrasenia_usuario;
    }

    public function tienePermiso(string $permiso): bool
    {
        if ($this->esAdmin()) {
            return true;
        }

        $permisos = $this->rol->permisos ?? [];

        return in_array('*', $permisos, true) || in_array($permiso, $permisos, true);
    }

    public function esAdmin(): bool
    {
        return $this->rol && $this->rol->nombre_rol === 'Administrador';
    }

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
