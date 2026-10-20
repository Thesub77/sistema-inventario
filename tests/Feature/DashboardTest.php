<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Usuario;
use App\Models\Venta;
use App\Models\Venta_detalle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $usuario;

    protected Categoria $categoria;

    protected Caja $caja;

    protected function setUp(): void
    {
        parent::setUp();

        $rol = Rol::create([
            'nombre_rol' => 'Administrador',
            'descripcion_rol' => 'Acceso Total',
            'permisos' => ['*'],
            'estado' => 1,
        ]);

        $this->usuario = Usuario::create([
            'id_rol' => $rol->rol_id,
            'nombre_apellido' => 'Admin Dashboard',
            'nombre_usuario' => 'admin_dash',
            'contrasenia_usuario' => bcrypt('password123'),
            'fecha_registro' => now(),
            'estado' => 1,
        ]);

        $this->categoria = Categoria::create([
            'codigo_categoria' => 'CAT-TEST',
            'nombre_categoria' => 'Bebidas',
            'estado' => 1,
        ]);

        $this->caja = Caja::create([
            'descripcion_caja' => 'Caja 1',
            'tipo_apertura' => 'Manual',
            'estado_caja' => 'Abierta',
            'estado' => 1,
        ]);

        Sanctum::actingAs($this->usuario);
    }

    public function test_dashboard_resumen_retorna_metricas_correctas_y_agregadas(): void
    {
        // 1. Crear productos con diferentes estados de stock y rotación
        $prodA = Producto::create([
            'id_categoria' => $this->categoria->categoria_id,
            'codigo_producto' => 'PROD-A',
            'nombre_producto' => 'Refresco Cola',
            'descripcion_producto' => 'Refresco de cola 500ml',
            'costo_compra' => 10,
            'precio_venta' => 15,
            'existencia_bodega' => 0, // Agotado / Crítico
            'existencia_minima' => 5,
            'estado' => 1,
        ]);

        $prodB = Producto::create([
            'id_categoria' => $this->categoria->categoria_id,
            'codigo_producto' => 'PROD-B',
            'nombre_producto' => 'Jugo Manzana',
            'descripcion_producto' => 'Jugo natural de manzana',
            'costo_compra' => 20,
            'precio_venta' => 30,
            'existencia_bodega' => 2, // Crítico (2 <= ceil(5/2))
            'existencia_minima' => 5,
            'estado' => 1,
        ]);

        $prodC = Producto::create([
            'id_categoria' => $this->categoria->categoria_id,
            'codigo_producto' => 'PROD-C',
            'nombre_producto' => 'Agua Mineral',
            'descripcion_producto' => 'Agua mineral gasificada',
            'costo_compra' => 5,
            'precio_venta' => 10,
            'existencia_bodega' => 50, // Stock óptimo
            'existencia_minima' => 10,
            'estado' => 1,
        ]);

        // 2. Crear una venta de hoy con detalles
        $ventaHoy = Venta::create([
            'id_usuario' => $this->usuario->usuario_id,
            'cliente_nombre' => 'Consumidor Final',
            'codigo_venta' => 'FAC-001',
            'metodo_pago' => 'Efectivo',
            'fecha_hora_venta' => now(),
            'subtotal_venta' => 60,
            'descuento_venta' => 0,
            'total_venta' => 60,
            'estado' => 1,
        ]);

        Venta_detalle::create([
            'id_venta' => $ventaHoy->venta_id,
            'id_producto' => $prodC->producto_id,
            'cantidad' => 6,
            'precio_unitario' => 10,
            'subtotal_venta_detalle' => 60,
            'estado' => 1,
        ]);

        // 3. Ejecutar endpoint
        $res = $this->getJson('/api/dashboard/resumen');

        $res->assertStatus(200);
        $res->assertJsonStructure([
            'success',
            'fecha',
            'ventasTurnoStats' => [
                'total', 'totalTickets', 'efectivo', 'countEfectivo', 'pctEfectivo',
                'transferencia', 'tarjeta',
            ],
            'stats' => ['totalVentasMonto', 'totalVentasCount', 'totalProductos', 'totalUnidades'],
            'stockAlerts' => ['criticos', 'urgentes', 'advertencias', 'totalAlertas', 'todos'],
            'topProductosVendidos',
            'productosBajaRotacion',
            'capitalInmovilizadoTotal',
            'chartVentas' => ['dias', 'semanas'],
            'ultimasVentas',
        ]);

        // 4. Aserciones de negocio
        $data = $res->json();

        // Ventas del turno
        $this->assertEquals(60, $data['ventasTurnoStats']['total']);
        $this->assertEquals(1, $data['ventasTurnoStats']['totalTickets']);
        $this->assertEquals(60, $data['ventasTurnoStats']['efectivo']);
        $this->assertEquals(100, $data['ventasTurnoStats']['pctEfectivo']);

        // Stock alerts: ProdA (0 <= 0 -> crítico), ProdB (2 <= 3 -> urgente)
        $this->assertEquals(2, $data['stockAlerts']['totalAlertas']);
        $this->assertCount(1, $data['stockAlerts']['criticos']);
        $this->assertEquals('PROD-A', $data['stockAlerts']['criticos'][0]['codigo_producto']);
        $this->assertCount(1, $data['stockAlerts']['urgentes']);
        $this->assertEquals('PROD-B', $data['stockAlerts']['urgentes'][0]['codigo_producto']);

        // Top productos: ProdC vendió 6 unidades
        $this->assertNotEmpty($data['topProductosVendidos']);
        $this->assertEquals('PROD-C', $data['topProductosVendidos'][0]['codigo']);
        $this->assertEquals(6, $data['topProductosVendidos'][0]['cantidadVendida']);

        // Baja rotación: ProdA (0 ventas) y ProdB (0 ventas)
        $this->assertNotEmpty($data['productosBajaRotacion']);
        $this->assertTrue(collect($data['productosBajaRotacion'])->pluck('codigo_producto')->contains('PROD-B'));

        // Gráfico últimos 7 días tiene 7 elementos
        $this->assertCount(7, $data['chartVentas']['dias']['labels']);
        $this->assertCount(4, $data['chartVentas']['semanas']['labels']);
    }

    public function test_dashboard_requiere_autenticacion(): void
    {
        Auth::forgetGuards();
        $this->getJson('/api/dashboard/resumen')->assertStatus(401);
    }

    public function test_techo_fiscal_retorna_metricas_correctas_y_semaforo_normal(): void
    {
        Empresa::create([
            'nombre_comercial' => 'Empresa Test',
            'numero_ruc' => 'J0310000000001',
            'telefono_contacto' => '2244-6688',
            'direccion_fisica' => 'Managua',
            'moneda_simbolo' => 'C$',
            'regimen_tributario' => 'Cuota Fija',
            'techo_mensual_cuota_fija' => 100000.00,
            'estado' => 1,
        ]);

        $ahora = Carbon::now();

        Venta::create([
            'id_usuario' => $this->usuario->usuario_id,
            'codigo_venta' => 'FAC-TF-01',
            'metodo_pago' => 'Efectivo',
            'fecha_hora_venta' => $ahora->copy()->startOfMonth()->addDays(2),
            'subtotal_venta' => 45200.00,
            'descuento_venta' => 0.00,
            'total_venta' => 45200.00,
            'estado' => 1,
        ]);

        $res = $this->getJson('/api/dashboard/techo-fiscal');

        $res->assertStatus(200);
        $res->assertJsonStructure([
            'regimen',
            'ventas_mes',
            'techo_mensual',
            'porcentaje_consumido',
            'saldo_disponible',
            'estado_semaforo',
            'ventas_anual_acumulado',
            'techo_anual',
        ]);

        $this->assertSame('Cuota Fija', $res->json('regimen'));
        $this->assertEquals(45200.00, $res->json('ventas_mes'));
        $this->assertEquals(100000.00, $res->json('techo_mensual'));
        $this->assertEquals(45.20, $res->json('porcentaje_consumido'));
        $this->assertEquals(54800.00, $res->json('saldo_disponible'));
        $this->assertSame('normal', $res->json('estado_semaforo'));
        $this->assertEquals(1200000.00, $res->json('techo_anual'));
    }

    public function test_techo_fiscal_excluye_estrictamente_ventas_anuladas(): void
    {
        Empresa::create([
            'nombre_comercial' => 'Empresa Test',
            'numero_ruc' => 'J0310000000001',
            'telefono_contacto' => '2244-6688',
            'direccion_fisica' => 'Managua',
            'moneda_simbolo' => 'C$',
            'regimen_tributario' => 'Cuota Fija',
            'techo_mensual_cuota_fija' => 100000.00,
            'estado' => 1,
        ]);

        $ahora = Carbon::now();

        Venta::create([
            'id_usuario' => $this->usuario->usuario_id,
            'codigo_venta' => 'FAC-ACT-01',
            'metodo_pago' => 'Efectivo',
            'fecha_hora_venta' => $ahora->copy()->startOfMonth()->addDay(),
            'subtotal_venta' => 30000.00,
            'descuento_venta' => 0.00,
            'total_venta' => 30000.00,
            'estado' => 1,
        ]);

        // Venta anulada (estado = 0)
        Venta::create([
            'id_usuario' => $this->usuario->usuario_id,
            'codigo_venta' => 'FAC-ANUL-01',
            'metodo_pago' => 'Efectivo',
            'fecha_hora_venta' => $ahora->copy()->startOfMonth()->addDays(2),
            'subtotal_venta' => 70000.00,
            'descuento_venta' => 0.00,
            'total_venta' => 70000.00,
            'estado' => 0,
        ]);

        $res = $this->getJson('/api/dashboard/techo-fiscal');

        $res->assertStatus(200);
        $this->assertEquals(30000.00, $res->json('ventas_mes'));
        $this->assertEquals(30.00, $res->json('porcentaje_consumido'));
        $this->assertEquals(70000.00, $res->json('saldo_disponible'));
        $this->assertSame('normal', $res->json('estado_semaforo'));
    }

    public function test_techo_fiscal_evalua_semaforo_alerta_y_excedido(): void
    {
        Empresa::create([
            'nombre_comercial' => 'Empresa Test',
            'numero_ruc' => 'J0310000000001',
            'telefono_contacto' => '2244-6688',
            'direccion_fisica' => 'Managua',
            'moneda_simbolo' => 'C$',
            'regimen_tributario' => 'Cuota Fija',
            'techo_mensual_cuota_fija' => 100000.00,
            'estado' => 1,
        ]);

        $ahora = Carbon::now();

        // 80% consumido -> alerta
        $venta1 = Venta::create([
            'id_usuario' => $this->usuario->usuario_id,
            'codigo_venta' => 'FAC-AL-01',
            'metodo_pago' => 'Efectivo',
            'fecha_hora_venta' => $ahora->copy()->startOfMonth()->addDay(),
            'subtotal_venta' => 80000.00,
            'descuento_venta' => 0.00,
            'total_venta' => 80000.00,
            'estado' => 1,
        ]);

        $resAlerta = $this->getJson('/api/dashboard/techo-fiscal');
        $resAlerta->assertStatus(200);
        $this->assertEquals(80.00, $resAlerta->json('porcentaje_consumido'));
        $this->assertSame('alerta', $resAlerta->json('estado_semaforo'));

        // Venta adicional que supera el techo (105,000) -> excedido
        Venta::create([
            'id_usuario' => $this->usuario->usuario_id,
            'codigo_venta' => 'FAC-EX-01',
            'metodo_pago' => 'Efectivo',
            'fecha_hora_venta' => $ahora->copy()->startOfMonth()->addDays(2),
            'subtotal_venta' => 25000.00,
            'descuento_venta' => 0.00,
            'total_venta' => 25000.00,
            'estado' => 1,
        ]);

        $resExcedido = $this->getJson('/api/dashboard/techo-fiscal');
        $resExcedido->assertStatus(200);
        $this->assertEquals(105000.00, $resExcedido->json('ventas_mes'));
        $this->assertEquals(105.00, $resExcedido->json('porcentaje_consumido'));
        $this->assertSame('excedido', $resExcedido->json('estado_semaforo'));
    }

    public function test_techo_fiscal_requiere_autenticacion(): void
    {
        Auth::forgetGuards();
        $this->getJson('/api/dashboard/techo-fiscal')->assertStatus(401);
    }
}
