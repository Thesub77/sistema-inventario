<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    //datos de la tabla categoria
    protected $table = 'categoria';
    protected $primaryKey = 'categoria_id';
    public $timestamps = false;

    protected $fillable = [
        'codigo_categoria',
        'nombre_categoria',
        'descripcion_categoria',
        'estado',
    ];

    public function productos()
    {
        return $this->hasMany(Producto::class, 'id_categoria', 'categoria_id');
    }
}
