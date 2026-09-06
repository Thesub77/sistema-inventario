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
        Schema::create('usuario', function (Blueprint $table) {
            $table->id('usuario_id');
            $table->unsignedBigInteger('id_rol');
            $table->string('nombre_apellido', 128);
            $table->string('nombre_usuario', 24);
            $table->string('contrasenia_usuario', 256);
            $table->date('fecha_registro');
            $table->tinyInteger('estado');

            $table->foreign('id_rol')->references('rol_id')->on('rol');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuario');
    }
};
