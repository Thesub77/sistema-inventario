<?php

namespace App\Models;

use App\Exceptions\CajaException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Caja_movimiento_venta extends Model
{
    // datos de la tabla caja_movimiento_venta
    protected $table = 'caja_movimiento_venta';

    protected $primaryKey = 'caja_movimiento_venta_id';

    public $timestamps = false;

    protected $fillable = [
        'id_caja',
        'id_venta',
        'id_caja_operacion',
        'monto_movimiento',
        'fecha_hora_movimiento',
        'estado',
    ];

    /**
     * El turno es la fuente de la caja para movimientos asociados.
     * Centralizarlo en saving cubre create(), save() y update() de instancias,
     * incluso importaciones que no pasen por VentaController.
     *
     * Un movimiento histórico sin turno conserva su caja original. No se busca
     * ni se asigna un turno por aproximación de fechas.
     * Las escrituras masivas o SQL directo no ejecutan eventos de Eloquent:
     * no deben usarse para modificar estas dos referencias.
     */
    protected static function booted(): void
    {
        static::saving(function (self $movimiento): void {
            if ($movimiento->id_caja_operacion === null) {
                return;
            }

            $turno = Caja_operacion::find($movimiento->id_caja_operacion);
            CajaException::exigir($turno !== null, 422, 'El turno indicado para el movimiento no existe.');
            $movimiento->id_caja = $turno->id_caja;
        });
    }

    /** Sin turno no hay responsable verificable: solo el administrador lo consulta. */
    public function scopeVisiblesPara(Builder $query, Usuario $usuario): Builder
    {
        if ($usuario->esAdmin()) {
            return $query;
        }

        return $query->whereHas('turno', fn (Builder $turno) => $turno->where('id_usuario', $usuario->usuario_id));
    }

    /** La relación es opcional porque las ventas históricas pueden no tener turno. */
    public function turno(): BelongsTo
    {
        return $this->belongsTo(Caja_operacion::class, 'id_caja_operacion', 'caja_operacion_id');
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'id_caja', 'caja_id');
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'id_venta', 'venta_id');
    }
}
