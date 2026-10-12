<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Caja_movimiento_venta;
use App\Models\Caja_operacion;
use App\Models\Categoria;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Usuario;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FiscalAndAuditTurnoTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $admin;

    protected Usuario $cajero1;

    protected Usuario $cajero2;

    protected Caja $caja;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin = Rol::create([
            'nombre_rol' => 'Administrador',
            'permisos' => ['*'],
            'estado' => 1,
        ]);

        $rolCajero = Rol::create([
            'nombre_rol' => 'Cajero',
            'permisos' => ['pos.acceso', 'ventas.ver', 'cajas.gestionar'],
            'estado' => 1,
        ]);

        $this->admin = Usuario::create([
            'id_rol' => $rolAdmin->rol_id,
            'nombre_apellido' => 'Diego Quiroz',
            'nombre_usuario' => 'si_dquiroz',
            'contrasenia_usuario' => bcrypt('password123'),
            'fecha_registro' => now(),
            'estado' => 1,
            'bloqueado' => 0,
        ]);

        $this->cajero1 = Usuario::create([
            'id_rol' => $rolCajero->rol_id,
            'nombre_apellido' => 'Cajero Uno',
            'nombre_usuario' => 'cajero1',
            'contrasenia_usuario' => bcrypt('password123'),
            'fecha_registro' => now(),
            'estado' => 1,
            'bloqueado' => 0,
        ]);

        $this->cajero2 = Usuario::create([
            'id_rol' => $rolCajero->rol_id,
            'nombre_apellido' => 'Cajero Dos',
            'nombre_usuario' => 'cajero2',
            'contrasenia_usuario' => bcrypt('password123'),
            'fecha_registro' => now(),
            'estado' => 1,
            'bloqueado' => 0,
        ]);

        $this->caja = Caja::create([
            'descripcion_caja' => 'Caja Principal',
            'tipo_apertura' => 'Manual',
            'estado_caja' => 'Abierta',
            'estado' => 1,
        ]);

        Empresa::create([
            'nombre_comercial' => 'Comercial La Bendición',
            'razon_social' => 'Comercial La Bendición S.A.',
            'numero_ruc' => 'J0310000000001',
            'telefono_contacto' => '2244-8899',
            'direccion_fisica' => 'Mercado Oriental, Managua',
            'moneda_simbolo' => 'C$',
            'regimen_tributario' => 'Cuota Fija',
            'techo_mensual_cuota_fija' => 100000.00,
            'estado' => 1,
        ]);
    }

    public function test_libro_diario_fiscal_agrupa_ventas_y_excluye_anuladas(): void
    {
        Sanctum::actingAs($this->admin);

        $cat = Categoria::create([
            'codigo_categoria' => 'CAT-1',
            'nombre_categoria' => 'Abarrotes',
            'estado' => 1,
        ]);

        $prod = Producto::create([
            'id_categoria' => $cat->categoria_id,
            'codigo_producto' => 'P1',
            'nombre_producto' => 'Arroz 1lb',
            'costo_compra' => 15.00,
            'precio_venta' => 20.00,
            'descripcion_producto' => 'Arroz de primera calidad',
            'existencia_bodega' => 100,
            'existencia_minima' => 5,
            'estado' => 1,
        ]);

        $fecha1 = '2026-10-05 10:00:00';
        $fecha2 = '2026-10-05 14:30:00';
        $fecha3 = '2026-10-06 11:00:00';

        // Venta 1 activa
        Venta::create([
            'id_usuario' => $this->admin->usuario_id,
            'codigo_venta' => 'FAC-0001',
            'subtotal_venta' => 100.00,
            'total_venta' => 100.00,
            'monto_recibido' => 100.00,
            'cambio' => 0.00,
            'metodo_pago' => 'Efectivo',
            'fecha_hora_venta' => $fecha1,
            'estado' => 1,
        ]);

        // Venta 2 activa (mismo día que Venta 1)
        Venta::create([
            'id_usuario' => $this->admin->usuario_id,
            'codigo_venta' => 'FAC-0002',
            'subtotal_venta' => 150.00,
            'total_venta' => 150.00,
            'monto_recibido' => 150.00,
            'cambio' => 0.00,
            'metodo_pago' => 'Efectivo',
            'fecha_hora_venta' => $fecha2,
            'estado' => 1,
        ]);

        // Venta 3 anulada (debe ser excluida del libro diario fiscal)
        Venta::create([
            'id_usuario' => $this->admin->usuario_id,
            'codigo_venta' => 'FAC-0003',
            'subtotal_venta' => 500.00,
            'total_venta' => 500.00,
            'monto_recibido' => 500.00,
            'cambio' => 0.00,
            'metodo_pago' => 'Efectivo',
            'fecha_hora_venta' => $fecha1,
            'estado' => 0,
        ]);

        // Venta 4 activa (otro día)
        Venta::create([
            'id_usuario' => $this->admin->usuario_id,
            'codigo_venta' => 'FAC-0004',
            'subtotal_venta' => 200.00,
            'total_venta' => 200.00,
            'monto_recibido' => 200.00,
            'cambio' => 0.00,
            'metodo_pago' => 'Transferencia',
            'fecha_hora_venta' => $fecha3,
            'estado' => 1,
        ]);

        $res = $this->getJson('/api/fiscal/libro-diario?mes=10&anio=2026');
        $res->assertStatus(200);
        $res->assertJsonPath('success', true);
        $res->assertJsonPath('mes', 10);
        $res->assertJsonPath('anio', 2026);
        $res->assertJsonPath('total_transacciones_mes', 3);
        $this->assertEquals(450.00, (float) $res->json('total_acumulado_mes'));

        $dias = $res->json('dias');
        $this->assertCount(2, $dias);

        // Día 2026-10-05: 2 transacciones, FAC-0001 al FAC-0002, total 250.00
        $dia5 = collect($dias)->firstWhere('fecha', '2026-10-05');
        $this->assertNotNull($dia5);
        $this->assertEquals(2, $dia5['cantidad_transacciones']);
        $this->assertEquals('FAC-0001', $dia5['comprobante_inicial']);
        $this->assertEquals('FAC-0002', $dia5['comprobante_final']);
        $this->assertEquals(250.00, (float) $dia5['total_dia']);

        // Día 2026-10-06: 1 transacción, FAC-0004 al FAC-0004, total 200.00
        $dia6 = collect($dias)->firstWhere('fecha', '2026-10-06');
        $this->assertNotNull($dia6);
        $this->assertEquals(1, $dia6['cantidad_transacciones']);
        $this->assertEquals('FAC-0004', $dia6['comprobante_inicial']);
        $this->assertEquals('FAC-0004', $dia6['comprobante_final']);
        $this->assertEquals(200.00, (float) $dia6['total_dia']);
    }

    public function test_techo_fiscal_cuota_fija_retorna_metricas_y_semaforo(): void
    {
        Sanctum::actingAs($this->admin);

        // Sin ventas: semáforo normal (<75%)
        $res1 = $this->getJson('/api/dashboard/techo-fiscal?fecha=2026-10-05');
        $res1->assertStatus(200);
        $res1->assertJsonPath('regimen', 'Cuota Fija');
        $this->assertEquals(100000.00, (float) $res1->json('techo_mensual'));
        $this->assertEquals(1200000.00, (float) $res1->json('techo_anual'));
        $res1->assertJsonPath('estado_semaforo', 'normal');
        $this->assertEquals(0.00, (float) $res1->json('ventas_mes'));
        $this->assertEquals(100000.00, (float) $res1->json('saldo_disponible'));

        // Venta al 80% (Alerta preventiva 75%-99.99%)
        Venta::create([
            'id_usuario' => $this->admin->usuario_id,
            'codigo_venta' => 'FAC-ALERTA',
            'subtotal_venta' => 80000.00,
            'total_venta' => 80000.00,
            'monto_recibido' => 80000.00,
            'cambio' => 0.00,
            'metodo_pago' => 'Efectivo',
            'fecha_hora_venta' => '2026-10-05 10:00:00',
            'estado' => 1,
        ]);

        $res2 = $this->getJson('/api/dashboard/techo-fiscal?fecha=2026-10-05');
        $res2->assertStatus(200);
        $this->assertEquals(80000.00, (float) $res2->json('ventas_mes'));
        $this->assertEquals(80.00, (float) $res2->json('porcentaje_consumido'));
        $this->assertEquals(20000.00, (float) $res2->json('saldo_disponible'));
        $this->assertEquals('alerta', $res2->json('estado_semaforo'));

        // Venta adicional que supera el 100% (Excedido >=100%)
        Venta::create([
            'id_usuario' => $this->admin->usuario_id,
            'codigo_venta' => 'FAC-EXCEDIDO',
            'subtotal_venta' => 30000.00,
            'total_venta' => 30000.00,
            'monto_recibido' => 30000.00,
            'cambio' => 0.00,
            'metodo_pago' => 'Efectivo',
            'fecha_hora_venta' => '2026-10-05 11:00:00',
            'estado' => 1,
        ]);

        $res3 = $this->getJson('/api/dashboard/techo-fiscal?fecha=2026-10-05');
        $res3->assertStatus(200);
        $this->assertEquals(110000.00, (float) $res3->json('ventas_mes'));
        $this->assertEquals(110.00, (float) $res3->json('porcentaje_consumido'));
        $this->assertEquals(0.00, (float) $res3->json('saldo_disponible'));
        $this->assertEquals('excedido', $res3->json('estado_semaforo'));
    }

    public function test_caja_operacion_show_auditoria_integral_y_seguridad_roles(): void
    {
        // 1. Cajero 1 abre un turno
        $turnoCajero1 = Caja_operacion::create([
            'id_caja' => $this->caja->caja_id,
            'id_usuario' => $this->cajero1->usuario_id,
            'fecha_hora_apertura' => now()->subHours(2),
            'monto_apertura' => 500.00,
            'estado' => 1,
        ]);

        // Registrar una venta en este turno
        $venta = Venta::create([
            'id_usuario' => $this->cajero1->usuario_id,
            'codigo_venta' => 'FAC-TURNO-1',
            'subtotal_venta' => 120.00,
            'total_venta' => 120.00,
            'monto_recibido' => 120.00,
            'cambio' => 0.00,
            'metodo_pago' => 'Efectivo',
            'fecha_hora_venta' => now()->subHour(),
            'estado' => 1,
        ]);

        Caja_movimiento_venta::create([
            'id_caja' => $this->caja->caja_id,
            'id_venta' => $venta->venta_id,
            'id_caja_operacion' => $turnoCajero1->caja_operacion_id,
            'monto_movimiento' => 120.00,
            'fecha_hora_movimiento' => now()->subHour(),
            'estado' => 1,
        ]);

        // 2. Cajero 2 intenta inspeccionar el turno de Cajero 1 -> Debe recibir 403
        Sanctum::actingAs($this->cajero2);
        $resForbidden = $this->getJson("/api/caja-operaciones/{$turnoCajero1->caja_operacion_id}");
        $resForbidden->assertStatus(403);

        // 3. Cajero 1 consulta su propio turno -> Debe recibir 200 con auditoría
        Sanctum::actingAs($this->cajero1);
        $resCajero1 = $this->getJson("/api/caja-operaciones/{$turnoCajero1->caja_operacion_id}");
        $resCajero1->assertStatus(200);
        $resCajero1->assertJsonPath('caja_operacion_id', $turnoCajero1->caja_operacion_id);
        $this->assertEquals(120.00, (float) $resCajero1->json('estadisticas.total_ventas_brutas'));
        $this->assertEquals(1, $resCajero1->json('estadisticas.total_tickets'));

        // 4. Administrador consulta el turno de Cajero 1 -> Debe recibir 200 con todas las métricas
        Sanctum::actingAs($this->admin);
        $resAdmin = $this->getJson("/api/caja-operaciones/{$turnoCajero1->caja_operacion_id}");
        $resAdmin->assertStatus(200);
        $this->assertArrayHasKey('movimientos', $resAdmin->json());
        $this->assertArrayHasKey('ventas', $resAdmin->json());
        $this->assertArrayHasKey('mermas', $resAdmin->json());
        $this->assertArrayHasKey('estadisticas', $resAdmin->json());
        $this->assertArrayHasKey('duracion_texto', $resAdmin->json('estadisticas'));
    }
}
