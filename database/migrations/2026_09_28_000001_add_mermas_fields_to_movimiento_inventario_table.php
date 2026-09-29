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
        Schema::table('movimiento_inventario', function (Blueprint $table) {
            $table->string('tipo_merma', 32)->nullable()->after('tipo_movimiento');
            $table->decimal('costo_unitario', 10, 2)->nullable()->after('tipo_merma');
            $table->decimal('costo_total_perdida', 10, 2)->nullable()->after('costo_unitario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimiento_inventario', function (Blueprint $table) {
            $table->dropColumn(['tipo_merma', 'costo_unitario', 'costo_total_perdida']);
        });
    }
};
