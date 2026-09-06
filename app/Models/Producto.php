<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    //datos dela tabla producto
    protected $table = 'producto';
    protected $primaryKey = 'producto_id';
    public $timestamps = false;

    protected $fillable = [
        'id_categoria',
        'codigo_producto',
        'nombre_producto',
        'descripcion_producto',
        'costo_compra',
        'precio_venta',
        'existencia_bodega',
        'existencia_minima',
        'estado',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'id_categoria', 'categoria_id');
    }

    public function movimiento_inventarios()
    {
        return $this->hasMany(Movimiento_inventario::class, 'id_producto', 'producto_id');
    }

    public function venta_detalles()
    {
        return $this->hasMany(Venta_detalle::class, 'id_producto', 'producto_id');
    }
}
