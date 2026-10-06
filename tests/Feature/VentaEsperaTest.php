<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VentaEsperaTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $usuario;

    protected Categoria $categoria;

    protected Producto $producto;

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
            'nombre_apellido' => 'Cajero POS',
            'nombre_usuario' => 'cajero1',
            'contrasenia_usuario' => bcrypt('secret123'),
            'fecha_registro' => now(),
            'estado' => 1,
        ]);

        $this->categoria = Categoria::create([
            'nombre_categoria' => 'Bebidas',
            'descripcion_categoria' => 'Gaseosas y jugos',
            'estado' => 1,
        ]);

        $this->producto = Producto::create([
            'id_categoria' => $this->categoria->categoria_id,
            'codigo_producto' => 'PROD-001',
            'nombre_producto' => 'Jugo de Naranja 500ml',
            'descripcion_producto' => 'Jugo natural',
            'costo_compra' => 15,
            'precio_venta' => 25,
            'existencia_bodega' => 50,
            'existencia_minima' => 5,
            'estado' => 1,
        ]);

        Sanctum::actingAs($this->usuario);
    }

    public function test_guarda_venta_en_espera_con_nomenclatura_automatica_consumidor_final(): void
    {
        // 1ra venta en espera con cliente Consumidor Final
        $response1 = $this->postJson('/api/ventas-espera', [
            'cliente_nombre' => 'Consumidor Final',
            'detalles' => [
                [
                    'id_producto' => $this->producto->producto_id,
                    'cantidad' => 2,
                    'precio_unitario' => 25,
                ],
            ],
        ]);

        $response1->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.identificador_cuenta', 'Cliente1');

        $this->assertEquals(50.00, (float) $response1->json('data.total'));

        $this->assertDatabaseHas('venta_espera', [
            'identificador_cuenta' => 'Cliente1',
            'cliente_nombre' => 'Consumidor Final',
            'total' => 50.00,
            'estado' => 1,
        ]);

        // 2da venta en espera simultánea con otro cliente genérico
        $response2 = $this->postJson('/api/ventas-espera', [
            'cliente_nombre' => 'Consumidor Final',
            'detalles' => [
                [
                    'id_producto' => $this->producto->producto_id,
                    'cantidad' => 1,
                    'precio_unitario' => 25,
                ],
            ],
        ]);

        $response2->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.identificador_cuenta', 'Cliente2');
    }

    public function test_guarda_venta_en_espera_con_nombre_de_cliente_registrado(): void
    {
        $response = $this->postJson('/api/ventas-espera', [
            'cliente_nombre' => 'Carlos Mendoza',
            'detalles' => [
                [
                    'id_producto' => $this->producto->producto_id,
                    'cantidad' => 3,
                    'precio_unitario' => 25,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.identificador_cuenta', 'Carlos Mendoza')
            ->assertJsonPath('data.cliente_nombre', 'Carlos Mendoza');

        $this->assertDatabaseHas('venta_espera', [
            'identificador_cuenta' => 'Carlos Mendoza',
            'cliente_nombre' => 'Carlos Mendoza',
            'total' => 75.00,
        ]);
    }

    public function test_venta_en_espera_no_descuenta_stock_ni_altera_caja(): void
    {
        $stockInicial = $this->producto->fresh()->existencia_bodega;

        $response = $this->postJson('/api/ventas-espera', [
            'cliente_nombre' => 'Carlos Mendoza',
            'detalles' => [
                [
                    'id_producto' => $this->producto->producto_id,
                    'cantidad' => 10,
                    'precio_unitario' => 25,
                ],
            ],
        ]);

        $response->assertStatus(201);

        // Verificar que el inventario no se modificó
        $this->assertEquals($stockInicial, $this->producto->fresh()->existencia_bodega);

        // Verificar que no se crearon movimientos de inventario ni de caja
        $this->assertDatabaseCount('movimiento_inventario', 0);
        $this->assertDatabaseCount('caja_movimiento_venta', 0);
        $this->assertDatabaseCount('venta', 0);
    }

    public function test_listar_y_obtener_venta_en_espera_con_detalles(): void
    {
        $postRes = $this->postJson('/api/ventas-espera', [
            'cliente_nombre' => 'Carlos Mendoza',
            'detalles' => [
                [
                    'id_producto' => $this->producto->producto_id,
                    'cantidad' => 2,
                    'precio_unitario' => 25,
                ],
            ],
        ]);

        $id = $postRes->json('data.venta_espera_id');

        // Listar
        $listRes = $this->getJson('/api/ventas-espera');
        $listRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        // Obtener detalle
        $showRes = $this->getJson("/api/ventas-espera/{$id}");
        $showRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.venta_espera_id', $id)
            ->assertJsonPath('data.detalles.0.id_producto', $this->producto->producto_id)
            ->assertJsonPath('data.detalles.0.producto.nombre_producto', 'Jugo de Naranja 500ml');
    }

    public function test_eliminacion_logica_de_venta_en_espera_y_sus_detalles(): void
    {
        $postRes = $this->postJson('/api/ventas-espera', [
            'cliente_nombre' => 'Carlos Mendoza',
            'detalles' => [
                [
                    'id_producto' => $this->producto->producto_id,
                    'cantidad' => 2,
                    'precio_unitario' => 25,
                ],
            ],
        ]);

        $id = $postRes->json('data.venta_espera_id');

        // Descartar / Reanudar (eliminación lógica)
        $deleteRes = $this->deleteJson("/api/ventas-espera/{$id}");
        $deleteRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // Comprobar estado 0 en BD
        $this->assertDatabaseHas('venta_espera', [
            'venta_espera_id' => $id,
            'estado' => 0,
        ]);

        $this->assertDatabaseHas('venta_espera_detalle', [
            'id_venta_espera' => $id,
            'estado' => 0,
        ]);

        // Ya no aparece en el listado activo
        $listRes = $this->getJson('/api/ventas-espera');
        $listRes->assertStatus(200)
            ->assertJsonCount(0, 'data');

        // Y show devuelve 404
        $showRes = $this->getJson("/api/ventas-espera/{$id}");
        $showRes->assertStatus(404);
    }

    public function test_validacion_de_descuento_mayor_al_subtotal(): void
    {
        $response = $this->postJson('/api/ventas-espera', [
            'cliente_nombre' => 'Carlos Mendoza',
            'descuento' => 100, // Subtotal será 25, descuento 100 es inválido
            'detalles' => [
                [
                    'id_producto' => $this->producto->producto_id,
                    'cantidad' => 1,
                    'precio_unitario' => 25,
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['descuento']);
    }

    public function test_actualizar_venta_en_espera_existente(): void
    {
        $postRes = $this->postJson('/api/ventas-espera', [
            'cliente_nombre' => 'Consumidor Final',
            'detalles' => [
                [
                    'id_producto' => $this->producto->producto_id,
                    'cantidad' => 1,
                    'precio_unitario' => 25,
                ],
            ],
        ]);

        $id = $postRes->json('data.venta_espera_id');
        $this->assertEquals('Cliente1', $postRes->json('data.identificador_cuenta'));

        // Modificar agregando más cantidad
        $updateRes = $this->putJson("/api/ventas-espera/{$id}", [
            'cliente_nombre' => 'Consumidor Final',
            'detalles' => [
                [
                    'id_producto' => $this->producto->producto_id,
                    'cantidad' => 4,
                    'precio_unitario' => 25,
                ],
            ],
        ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.identificador_cuenta', 'Cliente1');

        $this->assertEquals(100.00, (float) $updateRes->json('data.total'));
        $this->assertDatabaseHas('venta_espera', [
            'venta_espera_id' => $id,
            'identificador_cuenta' => 'Cliente1',
            'total' => 100.00,
        ]);
    }
}
