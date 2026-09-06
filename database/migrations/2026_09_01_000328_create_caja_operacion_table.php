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
        Schema::create('caja_operacion', function (Blueprint $table) {
            $table->id('caja_operacion_id');
            $table->unsignedBigInteger('id_caja');
            $table->unsignedBigInteger('id_usuario');
            $table->dateTime('fecha_hora_apertura')->nullable();
            $table->decimal('monto_apertura', 18, 2)->nullable();
            $table->decimal('monto_cierre', 18, 2)->nullable();
            $table->dateTime('fecha_hora_cierre')->nullable();
            $table->tinyInteger('estado');

            $table->foreign('id_caja')->references('caja_id')->on('caja');
            $table->foreign('id_usuario')->references('usuario_id')->on('usuario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caja_operacion');
    }
};
