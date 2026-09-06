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
        Schema::create('producto', function (Blueprint $table) {
            $table->id('producto_id');
            $table->unsignedBigInteger('id_categoria');
            $table->string('codigo_producto', 32);
            $table->string('nombre_producto', 128);
            $table->string('descripcion_producto', 128);
            $table->decimal('costo_compra', 10, 2);
            $table->decimal('precio_venta', 10, 2);
            $table->integer('existencia_bodega');
            $table->integer('existencia_minima');
            $table->tinyInteger('estado');

            $table->foreign('id_categoria')->references('categoria_id')->on('categoria');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producto');
    }
};
