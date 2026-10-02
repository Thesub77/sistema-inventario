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
        if (Schema::hasTable('venta_espera') && ! Schema::hasColumn('venta_espera', 'id_caja')) {
            Schema::table('venta_espera', function (Blueprint $table) {
                $table->unsignedBigInteger('id_caja')->nullable()->after('id_usuario');
                $table->foreign('id_caja')->references('caja_id')->on('caja')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('venta_espera') && Schema::hasColumn('venta_espera', 'id_caja')) {
            Schema::table('venta_espera', function (Blueprint $table) {
                $table->dropForeign(['id_caja']);
                $table->dropColumn('id_caja');
            });
        }
    }
};
