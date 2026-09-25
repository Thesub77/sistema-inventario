<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Usuario;
use App\Models\Venta;
use App\Models\Venta_detalle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VentaTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $usuario;

    protected Cliente $cliente;

    protected Categoria $categoria;

    protected Producto $producto;

    protected Caja $cajaAbierta;

    protected function setUp(): void
    {
        parent::setUp();

        $rol = Rol::create([
            'nombre_rol' => 'Administrador',
            'descripcion_rol' => 'Admin total',
            'permisos' => ['*'],
            'estado' => 1,
        ]);

        $this->usuario = Usuario::create([
            'id_rol' => $rol->rol_id,
            'nombre_apellido' => 'Vendedor Test',
            'nombre_usuario' => 'vendedor',
            'contrasenia_usuario' => bcrypt('secret123'),
            'fecha_registro' => now(),
            'estado' => 1,
        ]);

        $this->cliente = Cliente::create([
            'nombre_apellido_cliente' => 'Cliente Fiel',
            'codigo_cliente' => 'CLI-001',
            'telefono_cliente' => '12345678',
            'direccion_cliente' => 'Managua',
            'estado' => 1,
        ]);

        $this->categoria = Categoria::create([
            'nombre_categoria' => 'Lácteos',
            'descripcion_categoria' => 'Leche y derivados',
            'estado' => 1,
        ]);

        $this->producto = Producto::create([
            'id_categoria' => $this->categoria->categoria_id,
            'codigo_producto' => 'PROD-001',
            'nombre_producto' => 'Leche Entera 1L',
            'descripcion_producto' => 'Leche pasteurizada',
            'costo_compra' => 20,
            'precio_venta' => 30,
            'existencia_bodega' => 10,
            'existencia_minima' => 2,
            'estado' => 1,
        ]);

        $this->cajaAbierta = Caja::create([
            'descripcion_caja' => 'Caja 1',
            'tipo_apertura' => 'Manual',
            'estado_caja' => 'Abierta',
            'estado' => 1,
        ]);

        Sanctum::actingAs($this->usuario);
    }

    public function test_rechaza_venta_si_no_hay_caja_abierta(): void
    {
        // Cerrar la caja
        $this->cajaAbierta->update(['estado_caja' => 'Cerrada']);

        $payload = [
            'id_usuario' => $this->usuario->usuario_id,
            'id_cliente' => $this->cliente->cliente_id,
            'codigo_venta' => 'FAC-001',
            'metodo_pago' => 'Efectivo',
            'fecha_hora_venta' => now()->toDateTimeString(),
            'detalles' => [
                [
                    'id_producto' => $this->producto->producto_id,
                    'cantidad' => 2,
                ],
            ],
        ];

        $res = $this->postJson('/api/ventas', $payload);
        $res->assertStatus(409);
        $res->assertJsonFragment(['success' => false]);
    }

    public function test_rechaza_venta_si_stock_es_insuficiente(): void
    {
        $payload = [
            'id_usuario' => $this->usuario->usuario_id,
            'id_cliente' => $this->cliente->cliente_id,
            'codigo_venta' => 'FAC-002',
            'metodo_pago' => 'Efectivo',
            'fecha_hora_venta' => now()->toDateTimeString(),
            'detalles' => [
                [
                    'id_producto' => $this->producto->producto_id,
                    'cantidad' => 50, // Stock es 10
                ],
            ],
        ];

        $res = $this->postJson('/api/ventas', $payload);
        $res->assertStatus(422);
    }

    public function test_registra_venta_exitosamente_y_actualiza_todo(): void
    {
        $payload = [
            'id_usuario' => $this->usuario->usuario_id,
            'id_cliente' => $this->cliente->cliente_id,
            'codigo_venta' => 'FAC-003',
            'metodo_pago' => 'Efectivo',
            'fecha_hora_venta' => now()->toDateTimeString(),
            'descuento_venta' => 5,
            'detalles' => [
                [
                    'id_producto' => $this->producto->producto_id,
                    'cantidad' => 2,
                ],
            ],
        ];

        $res = $this->postJson('/api/ventas', $payload);

        // Debería responder 201 Created
        $res->assertStatus(201);

        // Verificar cálculo: 2 * 30 = 60 subtotal; 60 - 5 = 55 total
        $this->assertDatabaseHas('venta', [
            'codigo_venta' => 'FAC-003',
            'subtotal_venta' => 60,
            'descuento_venta' => 5,
            'total_venta' => 55,
            'estado' => 1,
        ]);

        // Verificar venta_detalle con precio_unitario
        $this->assertDatabaseHas('venta_detalle', [
            'id_producto' => $this->producto->producto_id,
            'cantidad' => 2,
            'subtotal_venta_detalle' => 60,
        ]);

        // Verificar inventario descontado: 10 - 2 = 8
        $this->assertEquals(8, $this->producto->fresh()->existencia_bodega);

        // Verificar movimiento inventario
        $this->assertDatabaseHas('movimiento_inventario', [
            'id_producto' => $this->producto->producto_id,
            'tipo_movimiento' => 'Salida por Venta',
            'cantidad_movimimiento' => 2,
            'stock_anterior_producto' => 10,
            'stock_resultante_producto' => 8,
        ]);

        // Verificar movimiento caja
        $this->assertDatabaseHas('caja_movimiento_venta', [
            'id_caja' => $this->cajaAbierta->caja_id,
            'monto_movimiento' => 55,
        ]);

        // Verificar bitácora
        $this->assertDatabaseHas('bitacora', [
            'accion_bitacora' => 'NUEVA_VENTA',
            'id_usuario' => $this->usuario->usuario_id,
        ]);
    }

    public function test_anulacion_logica_de_venta(): void
    {
        // Registrar venta
        $payload = [
            'id_usuario' => $this->usuario->usuario_id,
            'id_cliente' => $this->cliente->cliente_id,
            'codigo_venta' => 'FAC-004',
            'metodo_pago' => 'Efectivo',
            'fecha_hora_venta' => now()->toDateTimeString(),
            'detalles' => [
                [
                    'id_producto' => $this->producto->producto_id,
                    'cantidad' => 3,
                ],
            ],
        ];

        $resVenta = $this->postJson('/api/ventas', $payload);
        $resVenta->assertStatus(201);
        $ventaId = Venta::where('codigo_venta', 'FAC-004')->first()->venta_id;

        // Anular venta enviando id_usuario
        $resAnular = $this->deleteJson("/api/ventas/{$ventaId}", [
            'id_usuario' => $this->usuario->usuario_id,
        ]);
        $resAnular->assertStatus(200);

        // Verificar venta anulada (estado 0)
        $this->assertDatabaseHas('venta', [
            'venta_id' => $ventaId,
            'estado' => 0,
        ]);

        // Verificar stock restaurado (10 - 3 + 3 = 10)
        $this->assertEquals(10, $this->producto->fresh()->existencia_bodega);

        // Verificar movimiento por anulación
        $this->assertDatabaseHas('movimiento_inventario', [
            'id_producto' => $this->producto->producto_id,
            'tipo_movimiento' => 'Anulación de Venta',
            'cantidad_movimimiento' => 3,
            'stock_anterior_producto' => 7,
            'stock_resultante_producto' => 10,
        ]);

        // Reintento de anulación debe responder 409
        $resAnularRepetido = $this->deleteJson("/api/ventas/{$ventaId}", [
            'id_usuario' => $this->usuario->usuario_id,
        ]);
        $resAnularRepetido->assertStatus(409);
    }

    public function test_rechaza_venta_con_detalles_vacios(): void
    {
        $payload = [
            'id_usuario' => $this->usuario->usuario_id,
            'id_cliente' => $this->cliente->cliente_id,
            'codigo_venta' => 'FAC-005',
            'metodo_pago' => 'Efectivo',
            'fecha_hora_venta' => now()->toDateTimeString(),
            'detalles' => [],
        ];

        $res = $this->postJson('/api/ventas', $payload);
        $res->assertStatus(422);
    }

    public function test_rechaza_venta_por_transferencia_sin_referencia_de_transaccion(): void
    {
        $payload = [
            'id_usuario' => $this->usuario->usuario_id,
            'id_cliente' => $this->cliente->cliente_id,
            'codigo_venta' => 'FAC-TRF-001',
            'metodo_pago' => 'Transferencia',
            'fecha_hora_venta' => now()->toDateTimeString(),
            'detalles' => [
                [
                    'id_producto' => $this->producto->producto_id,
                    'cantidad' => 1,
                ],
            ],
        ];

        $res = $this->postJson('/api/ventas', $payload);
        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['referencia_transferencia']);
    }

    public function test_registra_venta_por_transferencia_con_referencia_exitosamente(): void
    {
        $payload = [
            'id_usuario' => $this->usuario->usuario_id,
            'id_cliente' => $this->cliente->cliente_id,
            'codigo_venta' => 'FAC-TRF-002',
            'metodo_pago' => 'Transferencia',
            'referencia_transferencia' => 'TRF-BAC-987654321',
            'fecha_hora_venta' => now()->toDateTimeString(),
            'detalles' => [
                [
                    'id_producto' => $this->producto->producto_id,
                    'cantidad' => 1,
                ],
            ],
        ];

        $res = $this->postJson('/api/ventas', $payload);
        $res->assertStatus(201);

        $this->assertDatabaseHas('venta', [
            'codigo_venta' => 'FAC-TRF-002',
            'metodo_pago' => 'Transferencia',
            'referencia_transferencia' => 'TRF-BAC-987654321',
            'estado' => 1,
        ]);
    }
}
