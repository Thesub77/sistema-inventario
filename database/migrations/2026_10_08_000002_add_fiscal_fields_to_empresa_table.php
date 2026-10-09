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
        Schema::table('empresa', function (Blueprint $table) {
            $table->string('regimen_tributario', 64)->default('Cuota Fija')->after('moneda_simbolo');
            $table->decimal('techo_mensual_cuota_fija', 12, 2)->default(100000.00)->after('regimen_tributario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            $table->dropColumn(['regimen_tributario', 'techo_mensual_cuota_fija']);
        });
    }
};
