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
        // 1. Tabla de Proveedores
        Schema::create('proveedor', function (Blueprint $table) {
            $table->id('proveedor_id');
            $table->string('nombre_comercial', 128);
            $table->string('contacto_vendedor', 128)->nullable();
            $table->string('telefono', 32)->nullable();
            $table->unsignedInteger('plazo_credito_dias')->default(0); // 0 = Contado, 7, 15, 30 días
            $table->tinyInteger('estado')->default(1); // 1 = Activo, 0 = Inactivo
            $table->timestamps();
        });

        // 2. Tabla de Cuentas por Pagar (Facturas emitidas por Proveedores)
        Schema::create('cuenta_por_pagar', function (Blueprint $table) {
            $table->id('cuenta_por_pagar_id');
            $table->foreignId('id_proveedor')->constrained('proveedor', 'proveedor_id')->onDelete('restrict');
            $table->string('numero_factura', 64);
            $table->string('descripcion', 255)->nullable();
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento');
            $table->decimal('monto_total', 18, 2);
            $table->decimal('monto_pagado', 18, 2)->default(0.00);
            $table->decimal('saldo_pendiente', 18, 2);
            $table->string('estado', 20)->default('Pendiente'); // Pendiente, Parcial, Pagada, Anulada
            $table->timestamps();
        });

        // 3. Tabla de Pagos / Abonos a Cuentas por Pagar (vinculado a Caja)
        Schema::create('pago_cuenta_por_pagar', function (Blueprint $table) {
            $table->id('pago_cuenta_por_pagar_id');
            $table->foreignId('id_cuenta_por_pagar')->constrained('cuenta_por_pagar', 'cuenta_por_pagar_id')->onDelete('cascade');
            $table->foreignId('id_caja_movimiento_venta')->nullable()->constrained('caja_movimiento_venta', 'caja_movimiento_venta_id')->nullOnDelete();
            $table->foreignId('id_usuario')->constrained('usuario', 'usuario_id')->onDelete('restrict');
            $table->decimal('monto_pago', 18, 2);
            $table->dateTime('fecha_pago');
            $table->string('metodo_pago', 32)->default('Efectivo'); // Efectivo, Transferencia
            $table->string('notas', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pago_cuenta_por_pagar');
        Schema::dropIfExists('cuenta_por_pagar');
        Schema::dropIfExists('proveedor');
    }
};
