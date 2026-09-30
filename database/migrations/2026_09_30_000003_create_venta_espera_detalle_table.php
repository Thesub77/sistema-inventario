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
        Schema::create('venta_espera_detalle', function (Blueprint $table) {
            $table->id('venta_espera_detalle_id');
            $table->unsignedBigInteger('id_venta_espera');
            $table->unsignedBigInteger('id_producto');
            $table->decimal('cantidad', 10, 2);
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->tinyInteger('estado')->default(1);

            $table->foreign('id_venta_espera')->references('venta_espera_id')->on('venta_espera')->onDelete('cascade');
            $table->foreign('id_producto')->references('producto_id')->on('producto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venta_espera_detalle');
    }
};
