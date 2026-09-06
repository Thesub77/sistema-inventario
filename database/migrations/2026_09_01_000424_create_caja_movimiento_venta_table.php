<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('caja_movimiento_venta', function (Blueprint $table) {
            $table->id('caja_movimiento_venta_id');
            $table->unsignedBigInteger('id_caja');
            $table->unsignedBigInteger('id_venta')->nullable();
            $table->decimal('monto_movimiento', 18, 2);
            $table->dateTime('fecha_hora_movimiento');
            $table->tinyInteger('estado');

            $table->foreign('id_caja')->references('caja_id')->on('caja');
            $table->foreign('id_venta')->references('venta_id')->on('venta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caja_movimiento_venta');
    }
};
