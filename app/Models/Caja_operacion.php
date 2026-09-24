<?php

namespace App\Models;

use App\Exceptions\CajaException;
use Illuminate\Database\Eloquent\Model;

class Caja_operacion extends Model
{
    protected $table = 'caja_operacion';

    protected $primaryKey = 'caja_operacion_id';

    public $timestamps = false;

    protected $fillable = [
        'id_caja',
        'id_usuario',
        'fecha_hora_apertura',
        'monto_apertura',
        'monto_cierre',
        'fecha_hora_cierre',
        'estado',
        'monto_esperado',
        'diferencia',
        'observacion_cierre',
        'id_usuario_cierre',
    ];

    protected $casts = [
        'monto_apertura' => 'decimal:2',
        'monto_cierre' => 'decimal:2',
        'monto_esperado' => 'decimal:2',
        'diferencia' => 'decimal:2',
    ];

    /**
     * Trasladar un turno a otra caja invalidaría las referencias de sus movimientos.
     * La caja se elige al abrir; los cambios posteriores solo afectan al cierre.
     * Las actualizaciones masivas de la FK también deben evitarse.
     */
    protected static function booted(): void
    {
        static::updating(function (self $turno): void {
            CajaException::rechazarSi($turno->isDirty('id_caja'), 409, 'La caja de un turno registrado no puede cambiarse.');
        });
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'id_caja', 'caja_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'usuario_id');
    }
}
