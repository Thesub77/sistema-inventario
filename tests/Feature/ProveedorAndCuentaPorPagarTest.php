<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Caja_movimiento_venta;
use App\Models\Caja_operacion;
use App\Models\Cuenta_por_pagar;
use App\Models\Proveedor;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProveedorAndCuentaPorPagarTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $adminUser;

    protected Usuario $cajeroUser;

    protected Caja $caja;

    protected Caja_operacion $turno;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin = Rol::create([
            'nombre_rol' => 'Administrador',
            'descripcion_rol' => 'Acceso Total',
            'permisos' => ['*'],
            'estado' => 1,
        ]);

        $rolCajero = Rol::create([
            'nombre_rol' => 'Cajero',
            'descripcion_rol' => 'Cajero POS',
            'permisos' => ['pos.acceso', 'ventas.crear'],
            'estado' => 1,
        ]);

        $this->adminUser = Usuario::create([
            'id_rol' => $rolAdmin->rol_id,
            'nombre_apellido' => 'Admin Test',
            'nombre_usuario' => 'admin_test',
            'contrasenia_usuario' => bcrypt('secret123'),
            'fecha_registro' => now(),
            'estado' => 1,
        ]);

        $this->cajeroUser = Usuario::create([
            'id_rol' => $rolCajero->rol_id,
            'nombre_apellido' => 'Cajero Test',
            'nombre_usuario' => 'cajero_test',
            'contrasenia_usuario' => bcrypt('secret123'),
            'fecha_registro' => now(),
            'estado' => 1,
        ]);

        $this->caja = Caja::create([
            'descripcion_caja' => 'Caja 1',
            'tipo_apertura' => 'Manual',
            'estado_caja' => 'Abierta',
            'estado' => 1,
        ]);

        $this->turno = Caja_operacion::create([
            'id_caja' => $this->caja->caja_id,
            'id_usuario' => $this->adminUser->usuario_id,
            'monto_apertura' => 500.00,
            'fecha_hora_apertura' => now(),
            'estado' => 1,
        ]);
    }

    public function test_usuario_no_administrador_recibe_403_en_proveedores_y_cxp(): void
    {
        Sanctum::actingAs($this->cajeroUser);

        // Proveedores
        $this->getJson('/api/proveedores')->assertStatus(403);
        $this->postJson('/api/proveedores', ['nombre_comercial' => 'Prov Test'])->assertStatus(403);

        // Cuentas por Pagar
        $this->getJson('/api/cuentas-por-pagar')->assertStatus(403);
        $this->getJson('/api/cuentas-por-pagar/resumen-kpis')->assertStatus(403);
    }

    public function test_admin_puede_crear_listar_y_actualizar_proveedor(): void
    {
        Sanctum::actingAs($this->adminUser);

        $res = $this->postJson('/api/proveedores', [
            'nombre_comercial' => 'Distribuidora Panificadora',
            'contacto_vendedor' => 'Don Manuel',
            'telefono' => '8888-9999',
            'plazo_credito_dias' => 7,
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('proveedor.nombre_comercial', 'Distribuidora Panificadora');

        $provId = $res->json('proveedor.proveedor_id');

        $list = $this->getJson('/api/proveedores');
        $list->assertStatus(200)
            ->assertJsonFragment(['nombre_comercial' => 'Distribuidora Panificadora']);

        $update = $this->putJson("/api/proveedores/{$provId}", [
            'nombre_comercial' => 'Distribuidora Panificadora S.A.',
            'contacto_vendedor' => 'Don Manuel Estrada',
            'telefono' => '8888-0000',
            'plazo_credito_dias' => 15,
        ]);
        $update->assertStatus(200)
            ->assertJsonPath('proveedor.nombre_comercial', 'Distribuidora Panificadora S.A.')
            ->assertJsonPath('proveedor.plazo_credito_dias', 15);
    }

    public function test_no_se_puede_desactivar_proveedor_con_deuda_activa(): void
    {
        Sanctum::actingAs($this->adminUser);

        $prov = Proveedor::create([
            'nombre_comercial' => 'Bebidas del Valle',
            'estado' => 1,
        ]);

        Cuenta_por_pagar::create([
            'id_proveedor' => $prov->proveedor_id,
            'numero_factura' => 'FAC-991',
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(7)->toDateString(),
            'monto_total' => 1500.00,
            'monto_pagado' => 0.00,
            'saldo_pendiente' => 1500.00,
            'estado' => 'Pendiente',
        ]);

        $res = $this->deleteJson("/api/proveedores/{$prov->proveedor_id}");
        $res->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertEquals(1, $prov->fresh()->estado);
    }

    public function test_crear_cuenta_por_pagar_y_calcular_kpis(): void
    {
        Sanctum::actingAs($this->adminUser);

        $prov = Proveedor::create([
            'nombre_comercial' => 'Distribuidora Huevos del Campo',
            'estado' => 1,
        ]);

        // Factura 1: Pendiente a vencer en 3 días
        $this->postJson('/api/cuentas-por-pagar', [
            'id_proveedor' => $prov->proveedor_id,
            'numero_factura' => 'FAC-00124',
            'descripcion' => '10 cajillas de huevos',
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(3)->toDateString(),
            'monto_total' => 1400.00,
        ])->assertStatus(201)
            ->assertJsonPath('cuenta.saldo_pendiente', 1400);

        // Factura 2: En mora (vencida)
        Cuenta_por_pagar::create([
            'id_proveedor' => $prov->proveedor_id,
            'numero_factura' => 'FAC-00100',
            'descripcion' => 'Factura anterior',
            'fecha_emision' => now()->subDays(10)->toDateString(),
            'fecha_vencimiento' => now()->subDays(2)->toDateString(),
            'monto_total' => 600.00,
            'monto_pagado' => 0.00,
            'saldo_pendiente' => 600.00,
            'estado' => 'Pendiente',
        ]);

        $kpis = $this->getJson('/api/cuentas-por-pagar/resumen-kpis');
        $kpis->assertStatus(200)
            ->assertJsonPath('total_deuda_activa', 2000)
            ->assertJsonPath('total_vencidas', 1)
            ->assertJsonPath('monto_vencido', 600)
            ->assertJsonPath('proximos_vencimientos', 1);
    }

    public function test_abono_desde_caja_descuenta_saldo_y_genera_egreso_en_turno(): void
    {
        Sanctum::actingAs($this->adminUser);

        $prov = Proveedor::create([
            'nombre_comercial' => 'Avícola La Estrella',
            'estado' => 1,
        ]);

        $cuenta = Cuenta_por_pagar::create([
            'id_proveedor' => $prov->proveedor_id,
            'numero_factura' => 'FAC-POLLO-889',
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(7)->toDateString(),
            'monto_total' => 2000.00,
            'monto_pagado' => 0.00,
            'saldo_pendiente' => 2000.00,
            'estado' => 'Pendiente',
        ]);

        // Registrar abono parcial de C$ 800 debitando de la caja activa
        $pagoRes = $this->postJson("/api/cuentas-por-pagar/{$cuenta->cuenta_por_pagar_id}/pagos", [
            'monto_pago' => 800.00,
            'metodo_pago' => 'Efectivo',
            'debitar_de_caja' => true,
            'id_caja_operacion' => $this->turno->caja_operacion_id,
            'notas' => 'Abono en efectivo al repartidor de pollo',
        ]);

        $pagoRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('cuenta.monto_pagado', 800)
            ->assertJsonPath('cuenta.saldo_pendiente', 1200)
            ->assertJsonPath('cuenta.estado', 'Parcial');

        // Verificar que se creó el Egreso en caja_movimiento_venta con monto negativo
        $this->assertDatabaseHas('caja_movimiento_venta', [
            'id_caja' => $this->caja->caja_id,
            'id_caja_operacion' => $this->turno->caja_operacion_id,
            'monto_movimiento' => -800.00,
        ]);

        // Liquidar el saldo restante de C$ 1,200
        $pagoFinalRes = $this->postJson("/api/cuentas-por-pagar/{$cuenta->cuenta_por_pagar_id}/pagos", [
            'monto_pago' => 1200.00,
            'metodo_pago' => 'Transferencia',
            'debitar_de_caja' => false,
            'notas' => 'Transferencia bancaria final',
        ]);

        $pagoFinalRes->assertStatus(201)
            ->assertJsonPath('cuenta.monto_pagado', 2000)
            ->assertJsonPath('cuenta.saldo_pendiente', 0)
            ->assertJsonPath('cuenta.estado', 'Pagada');

        $this->assertDatabaseHas('bitacora', [
            'accion_bitacora' => 'CAJA_PAGO_PROVEEDOR',
        ]);
    }

    public function test_abono_acepta_alias_registrar_en_caja_o_campo_opcional(): void
    {
        Sanctum::actingAs($this->adminUser);

        $prov = Proveedor::create(['nombre_comercial' => 'Distribuidora Central', 'estado' => 1]);

        $cuenta = Cuenta_por_pagar::create([
            'id_proveedor' => $prov->proveedor_id,
            'numero_factura' => 'FAC-ALIAS-123',
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(15)->toDateString(),
            'monto_total' => 1000.00,
            'monto_pagado' => 0.00,
            'saldo_pendiente' => 1000.00,
            'estado' => 'Pendiente',
        ]);

        // Registrar abono enviando registrar_en_caja (nombre de campo del formulario frontend)
        $res = $this->postJson("/api/cuentas-por-pagar/{$cuenta->cuenta_por_pagar_id}/pagos", [
            'monto_pago' => 300.00,
            'metodo_pago' => 'Transferencia',
            'registrar_en_caja' => false,
            'referencia_pago' => 'TRF-09923',
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('cuenta.monto_pagado', 300)
            ->assertJsonPath('cuenta.saldo_pendiente', 700);
    }

    public function test_eliminar_cuenta_por_pagar_registra_bitacora_valida(): void
    {
        Sanctum::actingAs($this->adminUser);

        $prov = Proveedor::create(['nombre_comercial' => 'Proveedor Eliminable', 'estado' => 1]);

        $cuenta = Cuenta_por_pagar::create([
            'id_proveedor' => $prov->proveedor_id,
            'numero_factura' => 'FAC-DEL-999',
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(5)->toDateString(),
            'monto_total' => 450.00,
            'monto_pagado' => 0.00,
            'saldo_pendiente' => 450.00,
            'estado' => 'Pendiente',
        ]);

        $res = $this->deleteJson("/api/cuentas-por-pagar/{$cuenta->cuenta_por_pagar_id}");

        $res->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('cuenta_por_pagar', [
            'cuenta_por_pagar_id' => $cuenta->cuenta_por_pagar_id,
        ]);

        $this->assertDatabaseHas('bitacora', [
            'accion_bitacora' => 'CUENTA_PAGAR_ELIMINADA',
        ]);
    }

    public function test_rechaza_abono_superior_al_saldo_pendiente(): void
    {
        Sanctum::actingAs($this->adminUser);

        $prov = Proveedor::create(['nombre_comercial' => 'Proveedor X', 'estado' => 1]);

        $cuenta = Cuenta_por_pagar::create([
            'id_proveedor' => $prov->proveedor_id,
            'numero_factura' => 'FAC-001',
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(7)->toDateString(),
            'monto_total' => 500.00,
            'monto_pagado' => 0.00,
            'saldo_pendiente' => 500.00,
            'estado' => 'Pendiente',
        ]);

        $res = $this->postJson("/api/cuentas-por-pagar/{$cuenta->cuenta_por_pagar_id}/pagos", [
            'monto_pago' => 600.00, // Superior a 500
            'metodo_pago' => 'Efectivo',
            'debitar_de_caja' => false,
        ]);

        $res->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
