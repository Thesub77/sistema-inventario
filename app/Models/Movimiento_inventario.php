<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movimiento_inventario extends Model
{
    protected $table = 'movimiento_inventario';
    protected $primaryKey = 'movimiento_inventario_id';
    public $timestamps = false;

    protected $fillable = [
        'id_producto',
        'id_usuario',
        'tipo_movimiento',
        'cantidad_movimimiento',
        'stock_anterior_producto',
        'stock_resultante_producto',
        'fecha_movimiento',
        'estado',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'producto_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'usuario_id');
    }
}
