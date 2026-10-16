<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Caja_operacion;
use App\Models\Categoria;
use App\Models\Movimiento_inventario;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Usuario;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductoComboTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    private Categoria $categoria;

    private Caja $caja;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin = Rol::create([
            'nombre_rol' => 'Administrador',
            'descripcion_rol' => 'Acceso Total',
            'permisos' => ['*'],
            'estado' => 1,
        ]);

        $this->admin = Usuario::create([
            'id_rol' => $rolAdmin->rol_id,
            'nombre_apellido' => 'Admin Principal',
            'nombre_usuario' => 'admin_test',
            'contrasenia_usuario' => bcrypt('password123'),
            'fecha_registro' => now(),
            'estado' => 1,
            'bloqueado' => 0,
        ]);

        $this->categoria = Categoria::create([
            'nombre_categoria' => 'Bebidas y Licores',
            'codigo_categoria' => 'BEB-01',
            'descripcion_categoria' => 'Bebidas del bar',
            'estado' => 1,
        ]);

        $this->caja = Caja::create([
            'descripcion_caja' => 'Caja Principal Bar',
            'tipo_apertura' => 'Manual',
            'estado_caja' => 'Abierta',
            'estado' => 1,
        ]);

        Sanctum::actingAs($this->admin);
    }

    public function test_crear_producto_combo_con_componentes_y_calcular_stock_virtual()
    {
        // 1. Crear producto estándar (Cerveza Toña con 24 unidades)
        $cerveza = Producto::create([
            'id_categoria' => $this->categoria->categoria_id,
            'codigo_producto' => 'TONA-BOT',
            'nombre_producto' => 'Cerveza Toña Botella',
            'tipo_producto' => 'estandar',
            'descripcion_producto' => 'Botella de vidrio 350ml',
            'costo_compra' => 30.00,
            'precio_venta' => 45.00,
            'existencia_bodega' => 24,
            'existencia_minima' => 6,
            'estado' => 1,
        ]);

        // 2. Crear combo Cubetazo (6 cervezas)
        $response = $this->actingAs($this->admin)->postJson('/api/productos', [
            'id_categoria' => $this->categoria->categoria_id,
            'codigo_producto' => 'CUBE-TONA',
            'nombre_producto' => 'Cubetazo Toña (6 cervezas)',
            'tipo_producto' => 'combo',
            'descripcion_producto' => 'Cubeta con 6 cervezas Toña bien frías',
            'costo_compra' => 180.00,
            'precio_venta' => 220.00,
            'existencia_minima' => 2,
            'estado' => 1,
            'componentes' => [
                [
                    'id_producto_componente' => $cerveza->producto_id,
                    'cantidad' => 6,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $data = $response->json();
        $this->assertEquals('combo', $data['tipo_producto']);
        // El stock virtual disponible debe ser 24 / 6 = 4 cubetazos
        $this->assertEquals(4, $data['existencia_bodega']);

        // En la base de datos la columna física es 0
        $this->assertDatabaseHas('producto', [
            'codigo_producto' => 'CUBE-TONA',
            'tipo_producto' => 'combo',
            'existencia_bodega' => 0,
        ]);

        $this->assertDatabaseHas('producto_combo_item', [
            'id_producto_componente' => $cerveza->producto_id,
            'cantidad' => 6,
        ]);

        // Listar productos debe retornar el stock virtual calculado
        $listResponse = $this->actingAs($this->admin)->getJson('/api/productos');
        $listResponse->assertStatus(200);
        $comboInList = collect($listResponse->json())->firstWhere('codigo_producto', 'CUBE-TONA');
        $this->assertNotNull($comboInList);
        $this->assertEquals(4, $comboInList['existencia_bodega']);
    }

    public function test_validaciones_al_crear_un_combo()
    {
        $prodEstandar = Producto::create([
            'id_categoria' => $this->categoria->categoria_id,
            'codigo_producto' => 'PROD-BASE',
            'nombre_producto' => 'Producto Base',
            'tipo_producto' => 'estandar',
            'descripcion_producto' => 'Desc',
            'costo_compra' => 10.00,
            'precio_venta' => 20.00,
            'existencia_bodega' => 10,
            'existencia_minima' => 2,
            'estado' => 1,
        ]);

        // 1. Rechaza combo sin componentes
        $resSinComp = $this->actingAs($this->admin)->postJson('/api/productos', [
            'id_categoria' => $this->categoria->categoria_id,
            'codigo_producto' => 'COMBO-VACIO',
            'nombre_producto' => 'Combo Sin Componentes',
            'tipo_producto' => 'combo',
            'descripcion_producto' => 'Desc',
            'costo_compra' => 10.00,
            'precio_venta' => 20.00,
            'existencia_minima' => 1,
            'estado' => 1,
            'componentes' => [],
        ]);
        $resSinComp->assertStatus(422)
            ->assertJsonValidationErrors(['componentes']);

        // 2. Rechaza componentes duplicados en el mismo combo
        $resDuplicados = $this->actingAs($this->admin)->postJson('/api/productos', [
            'id_categoria' => $this->categoria->categoria_id,
            'codigo_producto' => 'COMBO-DUP',
            'nombre_producto' => 'Combo Con Duplicados',
            'tipo_producto' => 'combo',
            'descripcion_producto' => 'Desc',
            'costo_compra' => 20.00,
            'precio_venta' => 30.00,
            'existencia_minima' => 1,
            'estado' => 1,
            'componentes' => [
                ['id_producto_componente' => $prodEstandar->producto_id, 'cantidad' => 1],
                ['id_producto_componente' => $prodEstandar->producto_id, 'cantidad' => 2],
            ],
        ]);
        $resDuplicados->assertStatus(422);

        // 3. Crear un combo válido para verificar que no se pueda usar como componente
        $comboValido = Producto::create([
            'id_categoria' => $this->categoria->categoria_id,
            'codigo_producto' => 'COMBO-PADRE',
            'nombre_producto' => 'Combo Padre',
            'tipo_producto' => 'combo',
            'descripcion_producto' => 'Desc',
            'costo_compra' => 10.00,
            'precio_venta' => 20.00,
            'existencia_bodega' => 0,
            'existencia_minima' => 1,
            'estado' => 1,
        ]);

        // Rechaza que un componente sea otro combo
        $resComboAnidado = $this->actingAs($this->admin)->postJson('/api/productos', [
            'id_categoria' => $this->categoria->categoria_id,
            'codigo_producto' => 'COMBO-HIJO',
            'nombre_producto' => 'Combo Anidado',
            'tipo_producto' => 'combo',
            'descripcion_producto' => 'Desc',
            'costo_compra' => 20.00,
            'precio_venta' => 30.00,
            'existencia_minima' => 1,
            'estado' => 1,
            'componentes' => [
                ['id_producto_componente' => $comboValido->producto_id, 'cantidad' => 1],
            ],
        ]);
        $resComboAnidado->assertStatus(422);
    }

    public function test_no_permite_movimientos_manuales_a_un_combo()
    {
        $combo = Producto::create([
            'id_categoria' => $this->categoria->categoria_id,
            'codigo_producto' => 'COMBO-TEST',
            'nombre_producto' => 'Combo Test',
            'tipo_producto' => 'combo',
            'descripcion_producto' => 'Desc',
            'costo_compra' => 50.00,
            'precio_venta' => 80.00,
            'existencia_bodega' => 0,
            'existencia_minima' => 1,
            'estado' => 1,
        ]);

        $res = $this->actingAs($this->admin)->postJson('/api/movimientos-inventario', [
            'id_producto' => $combo->producto_id,
            'id_usuario' => $this->admin->usuario_id,
            'tipo_movimiento' => 'Entrada por Compra',
            'cantidad_movimiento' => 10,
            'fecha_movimiento' => now()->toDateTimeString(),
        ]);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['id_producto']);
    }

    public function test_venta_de_combo_descuenta_stock_fisico_de_componentes_y_genera_kardex()
    {
        // 1. Abrir turno en caja
        $turno = Caja_operacion::create([
            'id_caja' => $this->caja->caja_id,
            'id_usuario' => $this->admin->usuario_id,
            'monto_apertura' => 1000.00,
            'fecha_hora_apertura' => now(),
            'estado' => 1,
        ]);

        // 2. Componente: Cerveza Toña (20 unidades)
        $cerveza = Producto::create([
            'id_categoria' => $this->categoria->categoria_id,
            'codigo_producto' => 'TONA-B1',
            'nombre_producto' => 'Cerveza Toña 350ml',
            'tipo_producto' => 'estandar',
            'descripcion_producto' => 'Botella',
            'costo_compra' => 30.00,
            'precio_venta' => 45.00,
            'existencia_bodega' => 20,
            'existencia_minima' => 5,
            'estado' => 1,
        ]);

        // 3. Combo: Cubetazo de 6 cervezas
        $combo = Producto::create([
            'id_categoria' => $this->categoria->categoria_id,
            'codigo_producto' => 'CUBETAZO-6',
            'nombre_producto' => 'Cubetazo Toña x6',
            'tipo_producto' => 'combo',
            'descripcion_producto' => 'Cubeta con 6 Toñas',
            'costo_compra' => 180.00,
            'precio_venta' => 220.00,
            'existencia_bodega' => 0,
            'existencia_minima' => 2,
            'estado' => 1,
        ]);

        $combo->comboItems()->create([
            'id_producto_componente' => $cerveza->producto_id,
            'cantidad' => 6,
        ]);

        // 4. Vender 2 Cubetazos (requiere 2 * 6 = 12 cervezas)
        $resVenta = $this->actingAs($this->admin)->postJson('/api/ventas', [
            'id_caja' => $this->caja->caja_id,
            'id_usuario' => $this->admin->usuario_id,
            'cliente_nombre' => 'Mesa 4',
            'codigo_venta' => 'TICK-COMBO-01',
            'fecha_hora_venta' => now()->toDateTimeString(),
            'metodo_pago' => 'Efectivo',
            'detalles' => [
                [
                    'id_producto' => $combo->producto_id,
                    'cantidad' => 2,
                ],
            ],
        ]);

        $resVenta->assertStatus(201);

        // 5. Verificar que el componente físico disminuyó de 20 a 8 (20 - 12 = 8)
        $cerveza->refresh();
        $this->assertEquals(8, $cerveza->existencia_bodega);

        // 6. Verificar el movimiento de Kardex generado para el componente
        $mov = Movimiento_inventario::where('id_producto', $cerveza->producto_id)->first();
        $this->assertNotNull($mov);
        $this->assertEquals('Salida por Venta', $mov->tipo_movimiento);
        $this->assertEquals(12, $mov->cantidad_movimimiento);
        $this->assertEquals(20, $mov->stock_anterior_producto);
        $this->assertEquals(8, $mov->stock_resultante_producto);

        // 7. Verificar que el detalle de venta conserva la línea comercial del combo
        $venta = Venta::where('codigo_venta', 'TICK-COMBO-01')->first();
        $this->assertNotNull($venta);
        $this->assertEquals(440.00, (float) $venta->total_venta);
        $this->assertDatabaseHas('venta_detalle', [
            'id_venta' => $venta->venta_id,
            'id_producto' => $combo->producto_id,
            'cantidad' => 2,
            'precio_unitario' => 220.00,
        ]);
    }

    public function test_venta_combinada_combo_y_producto_individual_valida_stock_total()
    {
        Caja_operacion::create([
            'id_caja' => $this->caja->caja_id,
            'id_usuario' => $this->admin->usuario_id,
            'monto_apertura' => 500.00,
            'fecha_hora_apertura' => now(),
            'estado' => 1,
        ]);

        // Cerveza con solo 7 unidades en bodega
        $cerveza = Producto::create([
            'id_categoria' => $this->categoria->categoria_id,
            'codigo_producto' => 'TONA-STOCK-7',
            'nombre_producto' => 'Cerveza Toña',
            'tipo_producto' => 'estandar',
            'descripcion_producto' => 'Botella',
            'costo_compra' => 30.00,
            'precio_venta' => 45.00,
            'existencia_bodega' => 7,
            'existencia_minima' => 2,
            'estado' => 1,
        ]);

        $combo = Producto::create([
            'id_categoria' => $this->categoria->categoria_id,
            'codigo_producto' => 'CUBETAZO-7',
            'nombre_producto' => 'Cubetazo Toña',
            'tipo_producto' => 'combo',
            'descripcion_producto' => 'Cubeta',
            'costo_compra' => 180.00,
            'precio_venta' => 220.00,
            'existencia_bodega' => 0,
            'existencia_minima' => 1,
            'estado' => 1,
        ]);

        $combo->comboItems()->create([
            'id_producto_componente' => $cerveza->producto_id,
            'cantidad' => 6,
        ]);

        // Intenta vender 1 Cubetazo (6) + 2 individuales (2) = 8 unidades necesarias, pero solo hay 7
        $resVenta = $this->actingAs($this->admin)->postJson('/api/ventas', [
            'id_caja' => $this->caja->caja_id,
            'id_usuario' => $this->admin->usuario_id,
            'codigo_venta' => 'TICK-FAIL-01',
            'fecha_hora_venta' => now()->toDateTimeString(),
            'metodo_pago' => 'Efectivo',
            'detalles' => [
                ['id_producto' => $combo->producto_id, 'cantidad' => 1],
                ['id_producto' => $cerveza->producto_id, 'cantidad' => 2],
            ],
        ]);

        $resVenta->assertStatus(422);
        // El stock físico de cerveza no debe haberse alterado
        $cerveza->refresh();
        $this->assertEquals(7, $cerveza->existencia_bodega);
    }

    public function test_anulacion_de_venta_con_combo_restaura_stock_de_componentes()
    {
        Caja_operacion::create([
            'id_caja' => $this->caja->caja_id,
            'id_usuario' => $this->admin->usuario_id,
            'monto_apertura' => 500.00,
            'fecha_hora_apertura' => now(),
            'estado' => 1,
        ]);

        $cerveza = Producto::create([
            'id_categoria' => $this->categoria->categoria_id,
            'codigo_producto' => 'TONA-ANULAR',
            'nombre_producto' => 'Cerveza Toña Anulable',
            'tipo_producto' => 'estandar',
            'descripcion_producto' => 'Botella',
            'costo_compra' => 30.00,
            'precio_venta' => 45.00,
            'existencia_bodega' => 12,
            'existencia_minima' => 2,
            'estado' => 1,
        ]);

        $combo = Producto::create([
            'id_categoria' => $this->categoria->categoria_id,
            'codigo_producto' => 'CUBETAZO-ANULAR',
            'nombre_producto' => 'Cubetazo Anulable',
            'tipo_producto' => 'combo',
            'descripcion_producto' => 'Cubeta',
            'costo_compra' => 180.00,
            'precio_venta' => 220.00,
            'existencia_bodega' => 0,
            'existencia_minima' => 1,
            'estado' => 1,
        ]);

        $combo->comboItems()->create([
            'id_producto_componente' => $cerveza->producto_id,
            'cantidad' => 6,
        ]);

        // Registrar venta de 2 cubetazos (12 cervezas)
        $resVenta = $this->actingAs($this->admin)->postJson('/api/ventas', [
            'id_caja' => $this->caja->caja_id,
            'id_usuario' => $this->admin->usuario_id,
            'codigo_venta' => 'TICK-ANULAR-01',
            'fecha_hora_venta' => now()->toDateTimeString(),
            'metodo_pago' => 'Efectivo',
            'detalles' => [
                ['id_producto' => $combo->producto_id, 'cantidad' => 2],
            ],
        ]);
        $resVenta->assertStatus(201);
        $ventaId = $resVenta->json('venta.venta_id');

        // Stock bajó a 0
        $cerveza->refresh();
        $this->assertEquals(0, $cerveza->existencia_bodega);

        // Anular la venta
        $resAnular = $this->actingAs($this->admin)->deleteJson("/api/ventas/{$ventaId}", [
            'id_usuario' => $this->admin->usuario_id,
        ]);
        $resAnular->assertStatus(200);

        // Stock físico debe estar restaurado a 12
        $cerveza->refresh();
        $this->assertEquals(12, $cerveza->existencia_bodega);

        // Movimiento de reposición en Kardex
        $movAnulacion = Movimiento_inventario::where('id_producto', $cerveza->producto_id)
            ->where('tipo_movimiento', 'Anulación de Venta')
            ->first();
        $this->assertNotNull($movAnulacion);
        $this->assertEquals(12, $movAnulacion->cantidad_movimimiento);
        $this->assertEquals(0, $movAnulacion->stock_anterior_producto);
        $this->assertEquals(12, $movAnulacion->stock_resultante_producto);
    }

    public function test_no_permite_desactivar_componente_usado_en_combo_activo()
    {
        $cerveza = Producto::create([
            'id_categoria' => $this->categoria->categoria_id,
            'codigo_producto' => 'TONA-ACT',
            'nombre_producto' => 'Cerveza Activa',
            'tipo_producto' => 'estandar',
            'descripcion_producto' => 'Botella',
            'costo_compra' => 30.00,
            'precio_venta' => 45.00,
            'existencia_bodega' => 0,
            'existencia_minima' => 2,
            'estado' => 1,
        ]);

        $combo = Producto::create([
            'id_categoria' => $this->categoria->categoria_id,
            'codigo_producto' => 'CUBETAZO-ACT',
            'nombre_producto' => 'Cubetazo Activo',
            'tipo_producto' => 'combo',
            'descripcion_producto' => 'Cubeta',
            'costo_compra' => 180.00,
            'precio_venta' => 220.00,
            'existencia_bodega' => 0,
            'existencia_minima' => 1,
            'estado' => 1,
        ]);

        $combo->comboItems()->create([
            'id_producto_componente' => $cerveza->producto_id,
            'cantidad' => 6,
        ]);

        // Intento de desactivar la cerveza mediante DELETE
        $resDelete = $this->actingAs($this->admin)->deleteJson("/api/productos/{$cerveza->producto_id}");
        $resDelete->assertStatus(409)
            ->assertJson([
                'success' => false,
            ]);

        // Intento de desactivar mediante PUT estado = 0
        $resPut = $this->actingAs($this->admin)->putJson("/api/productos/{$cerveza->producto_id}", [
            'estado' => 0,
        ]);
        $resPut->assertStatus(422)
            ->assertJsonValidationErrors(['estado']);
    }

    public function test_registro_de_producto_estandar_permite_crear_sin_exigir_componentes()
    {
        // Enviar producto estándar con componentes: [] (como lo envía el formulario base)
        $res = $this->actingAs($this->admin)->postJson('/api/productos', [
            'id_categoria' => $this->categoria->categoria_id,
            'codigo_producto' => 'STD-TEST-001',
            'nombre_producto' => 'Producto Estandar Prueba',
            'tipo_producto' => 'estandar',
            'descripcion_producto' => 'Sin componentes',
            'costo_compra' => 20.00,
            'precio_venta' => 30.00,
            'existencia_bodega' => 15,
            'existencia_minima' => 5,
            'estado' => 1,
            'componentes' => [],
        ]);

        $res->assertStatus(201);
        $this->assertDatabaseHas('producto', [
            'codigo_producto' => 'STD-TEST-001',
            'tipo_producto' => 'estandar',
            'existencia_bodega' => 15,
        ]);
    }
}
