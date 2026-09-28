<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('empresa', function (Blueprint $table) {
            $table->id('empresa_id');
            $table->string('nombre_comercial', 128);
            $table->string('razon_social', 128)->nullable();
            $table->string('numero_ruc', 32);
            $table->string('telefono_contacto', 32);
            $table->string('correo_contacto', 128)->nullable();
            $table->string('direccion_fisica', 255);
            $table->string('mensaje_pie_ticket', 255)->nullable();
            $table->string('moneda_simbolo', 8)->default('C$');
            $table->tinyInteger('estado')->default(1);
        });

        // Insertar datos iniciales por defecto para el negocio
        DB::table('empresa')->insert([
            'nombre_comercial' => 'Sistema Inventario & POS',
            'razon_social' => 'Comercial S.A.',
            'numero_ruc' => 'J0310000000001',
            'telefono_contacto' => '2244-6688',
            'correo_contacto' => 'contacto@sistema-inventario.local',
            'direccion_fisica' => 'Managua, Nicaragua',
            'mensaje_pie_ticket' => '¡Gracias por su compra! Revise su mercadería antes de salir.',
            'moneda_simbolo' => 'C$',
            'estado' => 1,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresa');
    }
};
