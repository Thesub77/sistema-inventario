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
        // En SQLite, ALTER TABLE DROP COLUMN falla si la columna forma parte de una restricción FK.
        // Por ello, en SQLite se realiza una recreación segura con PRAGMA foreign_keys=OFF.
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF');

            // 1. Recrear tabla venta sin id_cliente y con cliente_nombre
            Schema::create('venta_temp', function (Blueprint $table) {
                $table->id('venta_id');
                $table->unsignedBigInteger('id_usuario');
                $table->string('cliente_nombre', 128)->default('Consumidor Final');
                $table->string('codigo_venta', 32);
                $table->string('metodo_pago', 16);
                $table->string('referencia_transferencia', 64)->nullable();
                $table->dateTime('fecha_hora_venta');
                $table->decimal('subtotal_venta', 18, 2);
                $table->decimal('descuento_venta', 18, 2)->default(0);
                $table->decimal('total_venta', 18, 2);
                $table->tinyInteger('estado');
                $table->foreign('id_usuario')->references('usuario_id')->on('usuario');
            });

            $hasReferencia = Schema::hasColumn('venta', 'referencia_transferencia');
            $refCol = $hasReferencia ? 'referencia_transferencia' : 'NULL';
            $hasDesc = Schema::hasColumn('venta', 'descuento_venta');
            $descCol = $hasDesc ? 'descuento_venta' : '0';

            DB::statement("INSERT INTO venta_temp (venta_id, id_usuario, cliente_nombre, codigo_venta, metodo_pago, referencia_transferencia, fecha_hora_venta, subtotal_venta, descuento_venta, total_venta, estado)
                SELECT venta_id, id_usuario, 'Consumidor Final', codigo_venta, metodo_pago, {$refCol}, fecha_hora_venta, subtotal_venta, {$descCol}, total_venta, estado FROM venta");

            Schema::drop('venta');
            DB::statement('ALTER TABLE venta_temp RENAME TO venta');

            // 2. Recrear tabla venta_espera sin id_cliente y con cliente_nombre
            if (Schema::hasTable('venta_espera')) {
                Schema::create('venta_espera_temp', function (Blueprint $table) {
                    $table->id('venta_espera_id');
                    $table->unsignedBigInteger('id_usuario')->nullable();
                    $table->unsignedBigInteger('id_caja')->nullable();
                    $table->string('cliente_nombre', 128)->default('Consumidor Final');
                    $table->string('identificador_cuenta', 64)->nullable();
                    $table->string('observaciones', 255)->nullable();
                    $table->decimal('subtotal', 18, 2);
                    $table->decimal('descuento', 18, 2)->default(0);
                    $table->decimal('total', 18, 2);
                    $table->dateTime('fecha_creacion');
                    $table->tinyInteger('estado')->default(1);
                    $table->foreign('id_usuario')->references('usuario_id')->on('usuario');
                    $table->foreign('id_caja')->references('caja_id')->on('caja');
                });

                DB::statement("INSERT INTO venta_espera_temp (venta_espera_id, id_usuario, id_caja, cliente_nombre, identificador_cuenta, observaciones, subtotal, descuento, total, fecha_creacion, estado)
                    SELECT venta_espera_id, id_usuario, id_caja, 'Consumidor Final', identificador_cuenta, observaciones, subtotal, descuento, total, fecha_creacion, estado FROM venta_espera");

                Schema::drop('venta_espera');
                DB::statement('ALTER TABLE venta_espera_temp RENAME TO venta_espera');
            }

            Schema::dropIfExists('cliente');
            DB::statement('PRAGMA foreign_keys=ON');

            return;
        }

        // En PostgreSQL / MySQL:
        Schema::table('venta', function (Blueprint $table) {
            $table->string('cliente_nombre', 128)->default('Consumidor Final')->after('id_usuario');
        });

        if (Schema::hasTable('cliente')) {
            try {
                DB::statement('UPDATE venta SET cliente_nombre = (SELECT nombre_apellido_cliente FROM cliente WHERE cliente.cliente_id = venta.id_cliente) WHERE id_cliente IS NOT NULL AND EXISTS (SELECT 1 FROM cliente WHERE cliente.cliente_id = venta.id_cliente)');
            } catch (Throwable $e) {
            }
        }

        Schema::table('venta', function (Blueprint $table) {
            $table->dropForeign(['id_cliente']);
            $table->dropColumn('id_cliente');
        });

        if (Schema::hasTable('venta_espera')) {
            Schema::table('venta_espera', function (Blueprint $table) {
                $table->string('cliente_nombre', 128)->default('Consumidor Final')->after('id_caja');
                $table->dropForeign(['id_cliente']);
                $table->dropColumn('id_cliente');
            });
        }

        Schema::dropIfExists('cliente');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('cliente', function (Blueprint $table) {
            $table->id('cliente_id');
            $table->string('codigo_cliente', 32)->nullable();
            $table->string('nombre_apellido_cliente', 128);
            $table->string('telefono_cliente', 16)->nullable();
            $table->tinyInteger('estado')->default(1);
            $table->timestamps();
        });
    }
};
