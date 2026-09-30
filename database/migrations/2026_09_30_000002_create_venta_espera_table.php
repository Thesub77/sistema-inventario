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
        Schema::create('venta_espera', function (Blueprint $table) {
            $table->id('venta_espera_id');
            $table->unsignedBigInteger('id_usuario');
            $table->unsignedBigInteger('id_cliente')->nullable();
            $table->string('identificador_cuenta', 64);
            $table->string('observaciones', 255)->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->dateTime('fecha_creacion');
            $table->tinyInteger('estado')->default(1);

            $table->foreign('id_usuario')->references('usuario_id')->on('usuario');
            $table->foreign('id_cliente')->references('cliente_id')->on('cliente')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venta_espera');
    }
};
