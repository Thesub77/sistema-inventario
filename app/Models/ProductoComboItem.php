<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoComboItem extends Model
{
    protected $table = 'producto_combo_item';

    protected $fillable = [
        'id_producto_combo',
        'id_producto_componente',
        'cantidad',
    ];

    public function combo()
    {
        return $this->belongsTo(Producto::class, 'id_producto_combo', 'producto_id');
    }

    public function componente()
    {
        return $this->belongsTo(Producto::class, 'id_producto_componente', 'producto_id');
    }
}
