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
        Schema::table('producto', function (Blueprint $table) {
            $table->string('tipo_producto', 16)->default('estandar')->after('nombre_producto');
        });

        Schema::create('producto_combo_item', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_producto_combo');
            $table->unsignedBigInteger('id_producto_componente');
            $table->unsignedInteger('cantidad')->default(1);
            $table->timestamps();

            $table->foreign('id_producto_combo')->references('producto_id')->on('producto')->onDelete('cascade');
            $table->foreign('id_producto_componente')->references('producto_id')->on('producto')->onDelete('restrict');
            $table->unique(['id_producto_combo', 'id_producto_componente'], 'combo_componente_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producto_combo_item');

        Schema::table('producto', function (Blueprint $table) {
            $table->dropColumn('tipo_producto');
        });
    }
};
