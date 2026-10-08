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
            $table->dropColumn(['proveedor_nombre', 'numero_factura_recibo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimiento_inventario', function (Blueprint $table) {
            $table->string('proveedor_nombre', 128)->nullable()->after('costo_total_perdida');
            $table->string('numero_factura_recibo', 64)->nullable()->after('proveedor_nombre');
        });
    }
};
