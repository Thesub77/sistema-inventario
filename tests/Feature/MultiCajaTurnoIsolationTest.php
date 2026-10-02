<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MultiCajaTurnoIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Empresa $empresa;

    protected Rol $adminRol;

    protected Rol $cajeroRol;

    protected Usuario $admin;

    protected Usuario $cajero1;

    protected Usuario $cajero2;

    protected Caja $caja1;

    protected Caja $caja2;

    protected Producto $producto;

    protected Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRol = Rol::create([
            'nombre_rol' => 'Administrador',
            'descripcion_rol' => 'Acceso total',
            'permisos' => ['*'],
            'estado' => 1,
        ]);

        $this->cajeroRol = Rol::create([
            'nombre_rol' => 'Cajero',
            'descripcion_rol' => 'Operaciones de caja y ventas',
            'permisos' => ['pos.acceso', 'ventas.ver', 'cajas.gestionar', 'ventas.crear'],
            'estado' => 1,
        ]);

        $this->admin = Usuario::create([
            'id_rol' => $this->adminRol->rol_id,
            'nombre_usuario' => 'admin_test',
            'nombre_apellido' => 'Administrador General',
            'contrasenia_usuario' => bcrypt('password123'),
            'fecha_registro' => now(),
            'estado' => 1,
        ]);

        $this->cajero1 = Usuario::create([
            'id_rol' => $this->cajeroRol->rol_id,
            'nombre_usuario' => 'cajero_juan',
            'nombre_apellido' => 'Juan Perez',
            'contrasenia_usuario' => bcrypt('password123'),
            'fecha_registro' => now(),
            'estado' => 1,
        ]);

        $this->cajero2 = Usuario::create([
            'id_rol' => $this->cajeroRol->rol_id,
            'nombre_usuario' => 'cajero_maria',
            'nombre_apellido' => 'Maria Lopez',
            'contrasenia_usuario' => bcrypt('password123'),
            'fecha_registro' => now(),
            'estado' => 1,
        ]);

        $this->caja1 = Caja::create([
            'descripcion_caja' => 'Caja Mostrador 1',
            'tipo_apertura' => 'Manual',
            'estado_caja' => 'Cerrada',
            'estado' => 1,
        ]);

        $this->caja2 = Caja::create([
            'descripcion_caja' => 'Caja Mostrador 2',
            'tipo_apertura' => 'Manual',
            'estado_caja' => 'Cerrada',
            'estado' => 1,
        ]);

        $categoria = Categoria::create([
            'nombre_categoria' => 'Bebidas',
            'descripcion_categoria' => 'Refrescos',
            'estado' => 1,
        ]);

        $this->producto = Producto::create([
            'id_categoria' => $categoria->categoria_id,
            'codigo_producto' => 'PROD-001',
            'nombre_producto' => 'Jugo Natural',
            'descripcion_producto' => 'Jugo',
            'existencia_bodega' => 50,
            'existencia_minima' => 5,
            'costo_compra' => 15.00,
            'precio_venta' => 25.00,
            'estado' => 1,
        ]);

        $this->cliente = Cliente::create([
            'nombre_apellido_cliente' => 'Consumidor Final',
            'codigo_cliente' => 'CLI-0000',
            'telefono_cliente' => '00000000',
            'direccion_cliente' => 'Ciudad',
            'estado' => 1,
        ]);
    }

    public function test_un_usuario_no_puede_abrir_dos_turnos_simultaneos(): void
    {
        Sanctum::actingAs($this->cajero1);

        // Abrir primer turno en caja 1
        $res1 = $this->postJson('/api/caja-operaciones', [
            'id_caja' => $this->caja1->caja_id,
            'monto_apertura' => 500.00,
        ]);
        $res1->assertStatus(201);

        // Intentar abrir segundo turno en caja 2 con el mismo usuario
        $res2 = $this->postJson('/api/caja-operaciones', [
            'id_caja' => $this->caja2->caja_id,
            'monto_apertura' => 300.00,
        ]);
        $res2->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_un_cajero_no_puede_abrir_una_caja_fisica_ya_ocupada_por_otro_cajero(): void
    {
        // Cajero 1 abre caja 1
        Sanctum::actingAs($this->cajero1);
        $this->postJson('/api/caja-operaciones', [
            'id_caja' => $this->caja1->caja_id,
            'monto_apertura' => 500.00,
        ])->assertStatus(201);

        // Cajero 2 intenta abrir la misma caja 1
        Sanctum::actingAs($this->cajero2);
        $res = $this->postJson('/api/caja-operaciones', [
            'id_caja' => $this->caja1->caja_id,
            'monto_apertura' => 500.00,
        ]);

        $res->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_cajero_no_puede_vender_en_caja_de_otro_cajero(): void
    {
        // Cajero 1 abre caja 1
        Sanctum::actingAs($this->cajero1);
        $this->postJson('/api/caja-operaciones', [
            'id_caja' => $this->caja1->caja_id,
            'monto_apertura' => 500.00,
        ])->assertStatus(201);

        // Cajero 2 NO ha abierto caja e intenta vender especificando caja 1
        Sanctum::actingAs($this->cajero2);
        $res = $this->postJson('/api/ventas', [
            'id_caja' => $this->caja1->caja_id,
            'id_cliente' => $this->cliente->cliente_id,
            'codigo_venta' => 'FAC-TEST-0001',
            'metodo_pago' => 'Efectivo',
            'fecha_hora_venta' => now()->toDateTimeString(),
            'subtotal_venta' => 25.00,
            'descuento_venta' => 0.00,
            'total_venta' => 25.00,
            'estado' => 1,
            'detalles' => [
                [
                    'id_producto' => $this->producto->producto_id,
                    'cantidad' => 1,
                    'precio_unitario' => 25.00,
                    'subtotal_venta_detalle' => 25.00,
                ],
            ],
        ]);

        $res->assertStatus(409);
    }

    public function test_cajero_vende_en_su_propia_caja_correctamente_cuando_multiples_cajas_estan_abiertas(): void
    {
        // Cajero 1 abre caja 1
        Sanctum::actingAs($this->cajero1);
        $this->postJson('/api/caja-operaciones', [
            'id_caja' => $this->caja1->caja_id,
            'monto_apertura' => 500.00,
        ])->assertStatus(201);

        // Cajero 2 abre caja 2
        Sanctum::actingAs($this->cajero2);
        $this->postJson('/api/caja-operaciones', [
            'id_caja' => $this->caja2->caja_id,
            'monto_apertura' => 500.00,
        ])->assertStatus(201);

        // Cajero 2 realiza venta en caja 2
        $res = $this->postJson('/api/ventas', [
            'id_caja' => $this->caja2->caja_id,
            'id_cliente' => $this->cliente->cliente_id,
            'codigo_venta' => 'FAC-TEST-0002',
            'metodo_pago' => 'Efectivo',
            'fecha_hora_venta' => now()->toDateTimeString(),
            'subtotal_venta' => 25.00,
            'descuento_venta' => 0.00,
            'total_venta' => 25.00,
            'estado' => 1,
            'detalles' => [
                [
                    'id_producto' => $this->producto->producto_id,
                    'cantidad' => 1,
                    'precio_unitario' => 25.00,
                    'subtotal_venta_detalle' => 25.00,
                ],
            ],
        ]);

        $res->assertStatus(201);
        $this->assertDatabaseHas('venta', [
            'codigo_venta' => 'FAC-TEST-0002',
            'id_usuario' => $this->cajero2->usuario_id,
        ]);
    }

    public function test_cajero_no_puede_cerrar_turno_de_otro_cajero_pero_admin_si_puede_cierre_supervisado(): void
    {
        // Cajero 1 abre caja 1
        Sanctum::actingAs($this->cajero1);
        $resOp = $this->postJson('/api/caja-operaciones', [
            'id_caja' => $this->caja1->caja_id,
            'monto_apertura' => 500.00,
        ]);
        $opId = $resOp->json('caja_operacion_id');

        // Cajero 2 intenta cerrar el turno de Cajero 1 -> 403 Prohibido
        Sanctum::actingAs($this->cajero2);
        $resCierreCajero2 = $this->putJson("/api/caja-operaciones/{$opId}", [
            'monto_cierre' => 500.00,
            'observacion_cierre' => 'Intento indebido',
        ]);
        $resCierreCajero2->assertStatus(403);

        // Administrador realiza Cierre Supervisado -> 200 Exitoso
        Sanctum::actingAs($this->admin);
        $resCierreAdmin = $this->putJson("/api/caja-operaciones/{$opId}", [
            'monto_cierre' => 500.00,
            'observacion_cierre' => 'Cierre supervisado por fin de jornada',
        ]);
        $resCierreAdmin->assertStatus(200);

        // Verificar que en base de datos quedó registrado el id_usuario_cierre del admin
        $this->assertDatabaseHas('caja_operacion', [
            'caja_operacion_id' => $opId,
            'id_usuario' => $this->cajero1->usuario_id,
            'id_usuario_cierre' => $this->admin->usuario_id,
        ]);
    }
}
