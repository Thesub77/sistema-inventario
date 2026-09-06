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
        Schema::create('movimiento_inventario', function (Blueprint $table) {
            $table->id('movimiento_inventario_id');
            $table->unsignedBigInteger('id_producto');
            $table->unsignedBigInteger('id_usuario');
            $table->string('tipo_movimiento', 24);
            $table->integer('cantidad_movimimiento'); // respeta el nombre original (con typo)
            $table->integer('stock_anterior_producto');
            $table->integer('stock_resultante_producto');
            $table->dateTime('fecha_movimiento');
            $table->tinyInteger('estado');

            $table->foreign('id_producto')->references('producto_id')->on('producto');
            $table->foreign('id_usuario')->references('usuario_id')->on('usuario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimiento_inventario');
    }
};
