<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    // datos dela tabla producto
    protected $table = 'producto';

    protected $primaryKey = 'producto_id';

    public $timestamps = false;

    protected $fillable = [
        'id_categoria',
        'codigo_producto',
        'nombre_producto',
        'tipo_producto',
        'descripcion_producto',
        'costo_compra',
        'precio_venta',
        'existencia_bodega',
        'existencia_minima',
        'estado',
    ];

    public function comboItems()
    {
        return $this->hasMany(ProductoComboItem::class, 'id_producto_combo', 'producto_id');
    }

    public function combosDondeEsComponente()
    {
        return $this->hasMany(ProductoComboItem::class, 'id_producto_componente', 'producto_id');
    }

    public function esCombo(): bool
    {
        return ($this->tipo_producto ?? 'estandar') === 'combo';
    }

    /**
     * Calcula la existencia virtual máxima que puede armarse según el stock físico de sus componentes.
     */
    public function calcularStockCombo(): int
    {
        if (! $this->esCombo()) {
            return (int) $this->existencia_bodega;
        }

        $items = $this->relationLoaded('comboItems') ? $this->comboItems : $this->comboItems()->with('componente')->get();

        if ($items->isEmpty()) {
            return 0;
        }

        $minCombos = PHP_INT_MAX;

        foreach ($items as $item) {
            $componente = $item->relationLoaded('componente') ? $item->componente : $item->componente()->first();
            if (! $componente || $item->cantidad <= 0) {
                return 0;
            }
            $disponibles = intdiv((int) $componente->existencia_bodega, (int) $item->cantidad);
            if ($disponibles < $minCombos) {
                $minCombos = $disponibles;
            }
        }

        return $minCombos === PHP_INT_MAX ? 0 : max(0, $minCombos);
    }

    /**
     * Suma el costo de compra de todos los componentes del combo.
     */
    public function calcularCostoCombo(): float
    {
        if (! $this->esCombo()) {
            return (float) $this->costo_compra;
        }

        $items = $this->relationLoaded('comboItems') ? $this->comboItems : $this->comboItems()->with('componente')->get();

        $costoTotal = 0.0;
        foreach ($items as $item) {
            $componente = $item->relationLoaded('componente') ? $item->componente : $item->componente()->first();
            if ($componente) {
                $costoTotal += ((float) $componente->costo_compra * (int) $item->cantidad);
            }
        }

        return round($costoTotal, 2);
    }

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

    public function venta_espera_detalles()
    {
        return $this->hasMany(Venta_espera_detalle::class, 'id_producto', 'producto_id');
    }
}
