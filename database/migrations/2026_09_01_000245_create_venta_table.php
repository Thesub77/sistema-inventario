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
        Schema::create('venta', function (Blueprint $table) {
            $table->id('venta_id');
            $table->unsignedBigInteger('id_usuario');
            $table->unsignedBigInteger('id_cliente');
            $table->string('codigo_venta', 32);
            $table->string('metodo_pago', 16);
            $table->dateTime('fecha_hora_venta');
            $table->decimal('subtotal_venta', 18, 2);
            $table->decimal('descuento_venta', 5, 2);
            $table->decimal('total_venta', 18, 2);
            $table->tinyInteger('estado');

            $table->foreign('id_usuario')->references('usuario_id')->on('usuario');
            $table->foreign('id_cliente')->references('cliente_id')->on('cliente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venta');
    }
};
