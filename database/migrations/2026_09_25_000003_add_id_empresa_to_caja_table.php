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
        Schema::table('caja', function (Blueprint $table) {
            $table->unsignedBigInteger('id_empresa')->nullable()->after('caja_id');
            $table->foreign('id_empresa')->references('empresa_id')->on('empresa')->nullOnDelete();
        });

        // Vincular cajas existentes a la empresa por defecto si existe
        $empresaId = DB::table('empresa')->value('empresa_id');
        if ($empresaId) {
            DB::table('caja')->whereNull('id_empresa')->update(['id_empresa' => $empresaId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caja', function (Blueprint $table) {
            $table->dropForeign(['id_empresa']);
            $table->dropColumn('id_empresa');
        });
    }
};
