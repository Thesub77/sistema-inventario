<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caja_operacion', function (Blueprint $table) {
            $table->decimal('monto_esperado', 18, 2)->nullable();
            $table->decimal('diferencia', 18, 2)->nullable();
            $table->string('observacion_cierre', 255)->nullable();
            $table->foreignId('id_usuario_cierre')->nullable()->constrained('usuario', 'usuario_id');
            $table->index(['id_caja', 'fecha_hora_cierre']);
        });
        Schema::table('caja_movimiento_venta', function (Blueprint $table) {
            // El historial previo queda sin asignar: no se adivinan turnos antiguos.
            $table->foreignId('id_caja_operacion')->nullable()->constrained('caja_operacion', 'caja_operacion_id');
        });
    }

    public function down(): void
    {
        Schema::table('caja_movimiento_venta', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_caja_operacion');
        });
        Schema::table('caja_operacion', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_usuario_cierre');
            $table->dropIndex(['id_caja', 'fecha_hora_cierre']);
            $table->dropColumn(['monto_esperado', 'diferencia', 'observacion_cierre']);
        });
    }
};
