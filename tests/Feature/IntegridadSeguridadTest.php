<?php

namespace Tests\Feature;

use App\Models\Bitacora;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Movimiento_inventario;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IntegridadSeguridadTest extends TestCase
{
    use RefreshDatabase;

    protected Rol $rolAdmin;

    protected Rol $rolCajero;

    protected Usuario $adminPrincipal;

    protected Usuario $adminSecundario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rolAdmin = Rol::create([
            'nombre_rol' => 'Administrador',
            'descripcion_rol' => 'Admin total',
            'permisos' => ['*'],
            'estado' => 1,
        ]);

        $this->rolCajero = Rol::create([
            'nombre_rol' => 'Cajero',
            'descripcion_rol' => 'Cajero ventas',
            'permisos' => ['pos.acceso'],
            'estado' => 1,
        ]);

        $this->adminPrincipal = Usuario::create([
            'id_rol' => $this->rolAdmin->rol_id,
            'nombre_apellido' => 'Diego Quiroz',
            'nombre_usuario' => 'si_dquiroz',
            'contrasenia_usuario' => bcrypt('admin123'),
            'fecha_registro' => now(),
            'estado' => 1,
        ]);

        $this->adminSecundario = Usuario::create([
            'id_rol' => $this->rolAdmin->rol_id,
            'nombre_apellido' => 'Admin Dos',
            'nombre_usuario' => 'admin_dos',
            'contrasenia_usuario' => bcrypt('admin123'),
            'fecha_registro' => now(),
            'estado' => 1,
        ]);

        Sanctum::actingAs($this->adminPrincipal);
    }

    public function test_categoria_se_desactiva_logicamente_y_rechaza_si_tiene_productos_activos(): void
    {
        $cat = Categoria::create([
            'codigo_categoria' => 'CAT-BEB',
            'nombre_categoria' => 'Bebidas',
            'descripcion_categoria' => 'Refrescos y jugos',
            'estado' => 1,
        ]);

        $prod = Producto::create([
            'id_categoria' => $cat->categoria_id,
            'codigo_producto' => 'PROD-01',
            'nombre_producto' => 'Jugo Naranja',
            'descripcion_producto' => 'Jugo natural',
            'costo_compra' => 10,
            'precio_venta' => 15,
            'existencia_bodega' => 5,
            'existencia_minima' => 1,
            'estado' => 1,
        ]);

        // 1. Debe rechazar desactivación si tiene productos activos
        $resConflicto = $this->deleteJson("/api/categorias/{$cat->categoria_id}");
        $resConflicto->assertStatus(409);
        $resConflicto->assertJsonPath('success', false);

        // 2. Desactivamos el producto primero
        $prod->update(['estado' => 0]);

        // 3. Ahora la categoría debe desactivarse lógicamente (estado = 0)
        $resOk = $this->deleteJson("/api/categorias/{$cat->categoria_id}");
        $resOk->assertStatus(200);
        $resOk->assertJsonPath('success', true);

        // Verificar en BD que no fue borrada físicamente, sino que su estado es 0
        $this->assertDatabaseHas('categoria', [
            'categoria_id' => $cat->categoria_id,
            'estado' => 0,
        ]);
    }

    public function test_categoria_rechaza_nombres_o_codigos_duplicados(): void
    {
        Categoria::create([
            'codigo_categoria' => 'CAT-01',
            'nombre_categoria' => 'Lácteos',
            'estado' => 1,
        ]);

        // Intento con mismo nombre
        $res = $this->postJson('/api/categorias', [
            'codigo_categoria' => 'CAT-02',
            'nombre_categoria' => 'Lácteos',
            'estado' => 1,
        ]);
        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['nombre_categoria']);

        // Intento con mismo código
        $res2 = $this->postJson('/api/categorias', [
            'codigo_categoria' => 'CAT-01',
            'nombre_categoria' => 'Panadería',
            'estado' => 1,
        ]);
        $res2->assertStatus(422);
        $res2->assertJsonValidationErrors(['codigo_categoria']);
    }

    public function test_cliente_se_desactiva_logicamente(): void
    {
        $cliente = Cliente::create([
            'codigo_cliente' => 'CLI-100',
            'nombre_apellido_cliente' => 'Cliente Corporativo S.A.',
            'telefono_cliente' => '88889999',
            'estado' => 1,
        ]);

        $res = $this->deleteJson("/api/clientes/{$cliente->cliente_id}");
        $res->assertStatus(200);
        $res->assertJsonPath('success', true);

        // Verificar que el registro persiste pero con estado 0
        $this->assertDatabaseHas('cliente', [
            'cliente_id' => $cliente->cliente_id,
            'estado' => 0,
        ]);
    }

    public function test_cliente_rechaza_codigo_duplicado(): void
    {
        Cliente::create([
            'codigo_cliente' => 'CLI-200',
            'nombre_apellido_cliente' => 'Primer Cliente',
            'estado' => 1,
        ]);

        $res = $this->postJson('/api/clientes', [
            'codigo_cliente' => 'CLI-200',
            'nombre_apellido_cliente' => 'Segundo Cliente',
            'estado' => 1,
        ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['codigo_cliente']);
    }

    public function test_usuario_se_desactiva_logicamente_y_revoca_tokens(): void
    {
        $cajero = Usuario::create([
            'id_rol' => $this->rolCajero->rol_id,
            'nombre_apellido' => 'Cajero Uno',
            'nombre_usuario' => 'cajero_uno',
            'contrasenia_usuario' => bcrypt('123456'),
            'fecha_registro' => now(),
            'estado' => 1,
        ]);

        $tokenCajero = $cajero->createToken('cajero-token')->plainTextToken;

        // Desactivar el cajero
        $res = $this->deleteJson("/api/usuarios/{$cajero->usuario_id}");
        $res->assertStatus(200);
        $res->assertJsonPath('success', true);

        // Verificar baja lógica
        $this->assertDatabaseHas('usuario', [
            'usuario_id' => $cajero->usuario_id,
            'estado' => 0,
        ]);

        // Verificar que sus tokens fueron revocados
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $cajero->usuario_id,
        ]);

        // Intentar usar su token debe ser 401
        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', "Bearer {$tokenCajero}")
            ->getJson('/api/auth/me')
            ->assertStatus(401);
    }

    public function test_usuario_no_puede_desactivarse_a_si_mismo(): void
    {
        // El adminPrincipal intenta desactivarse a sí mismo
        $res = $this->deleteJson("/api/usuarios/{$this->adminPrincipal->usuario_id}");
        $res->assertStatus(403);
        $res->assertJsonPath('message', 'No puedes desactivar tu propio usuario en sesión.');

        // Tampoco mediante PUT
        $resPut = $this->putJson("/api/usuarios/{$this->adminPrincipal->usuario_id}", [
            'estado' => 0,
        ]);
        $resPut->assertStatus(403);
    }

    public function test_usuario_no_puede_cambiar_su_propio_rol(): void
    {
        // El adminPrincipal intenta cambiarse a sí mismo el rol a Cajero
        $res = $this->putJson("/api/usuarios/{$this->adminPrincipal->usuario_id}", [
            'id_rol' => $this->rolCajero->rol_id,
        ]);
        $res->assertStatus(403);
        $res->assertJsonPath('message', 'No puedes modificar tu propio rol de usuario.');
    }

    public function test_admin_puede_editar_su_nombre_y_usuario_sin_alterar_rol(): void
    {
        $res = $this->putJson("/api/usuarios/{$this->adminPrincipal->usuario_id}", [
            'nombre_apellido' => 'Admin Renombrado',
            'nombre_usuario' => 'admin_nuevo',
        ]);
        $res->assertStatus(200);
        $this->assertDatabaseHas('usuario', [
            'usuario_id' => $this->adminPrincipal->usuario_id,
            'nombre_apellido' => 'Admin Renombrado',
            'nombre_usuario' => 'admin_nuevo',
            'id_rol' => $this->rolAdmin->rol_id,
            'estado' => 1,
        ]);
    }

    public function test_no_se_puede_desactivar_al_unico_administrador_activo(): void
    {
        // Desactivamos al adminSecundario
        $this->adminSecundario->update(['estado' => 0]);

        // Ahora solo queda adminPrincipal como administrador activo
        // Actuamos como un tercer admin para no chocar con la regla de auto-eliminación
        $tercerUsuario = Usuario::create([
            'id_rol' => $this->rolAdmin->rol_id,
            'nombre_apellido' => 'Admin Tres',
            'nombre_usuario' => 'admin_tres',
            'contrasenia_usuario' => bcrypt('123456'),
            'fecha_registro' => now(),
            'estado' => 1,
        ]);
        Sanctum::actingAs($tercerUsuario);

        // Desactivamos al adminPrincipal (ahora adminTres es el único)
        $this->deleteJson("/api/usuarios/{$this->adminPrincipal->usuario_id}")->assertStatus(200);

        // Ahora intentamos desactivar a adminTres siendo el único activo
        $res = $this->deleteJson("/api/usuarios/{$tercerUsuario->usuario_id}");
        // Falla por auto-desactivación (403)
        $res->assertStatus(403);

        // Si creamos un supervisor con permiso pero no admin para intentar desactivar al único admin activo:
        $rolGerente = Rol::create([
            'nombre_rol' => 'Gerente',
            'permisos' => ['usuarios.gestionar'],
            'estado' => 1,
        ]);
        $gerente = Usuario::create([
            'id_rol' => $rolGerente->rol_id,
            'nombre_apellido' => 'Gerente General',
            'nombre_usuario' => 'gerente',
            'contrasenia_usuario' => bcrypt('123456'),
            'fecha_registro' => now(),
            'estado' => 1,
        ]);
        Sanctum::actingAs($gerente);

        $resUnico = $this->deleteJson("/api/usuarios/{$tercerUsuario->usuario_id}");
        $resUnico->assertStatus(403);
        $resUnico->assertJsonPath('message', 'No se puede desactivar al único Administrador activo del sistema.');
    }

    public function test_bloqueo_automatico_tras_3_intentos_fallidos_y_desbloqueo_por_admin(): void
    {
        $this->app['auth']->forgetGuards();

        $cajeroTest = Usuario::create([
            'id_rol' => $this->rolCajero->rol_id,
            'nombre_apellido' => 'Cajero Bloqueo',
            'nombre_usuario' => 'cajero_bloqueo',
            'contrasenia_usuario' => bcrypt('claveCorrecta123'),
            'fecha_registro' => now(),
            'estado' => 1,
        ]);

        // Intento 1: Fallido (Quedan 2)
        $res1 = $this->postJson('/api/auth/login', [
            'nombre_usuario' => 'cajero_bloqueo',
            'contrasenia_usuario' => 'claveErronea1',
        ]);
        $res1->assertStatus(401);
        $res1->assertJsonPath('intentos_restantes', 2);

        // Intento 2: Fallido (Queda 1)
        $res2 = $this->postJson('/api/auth/login', [
            'nombre_usuario' => 'cajero_bloqueo',
            'contrasenia_usuario' => 'claveErronea2',
        ]);
        $res2->assertStatus(401);
        $res2->assertJsonPath('intentos_restantes', 1);

        // Intento 3: Fallido -> Bloqueo automático (403)
        $res3 = $this->postJson('/api/auth/login', [
            'nombre_usuario' => 'cajero_bloqueo',
            'contrasenia_usuario' => 'claveErronea3',
        ]);
        $res3->assertStatus(403);
        $res3->assertJsonPath('success', false);
        $this->assertStringContainsString('bloqueada por exceder el límite de 3 intentos', $res3->json('message'));

        // Verificar que en base de datos el usuario quedó en estado 0 (Inactivo / Bloqueado)
        $cajeroTest->refresh();
        $this->assertEquals(0, (int) $cajeroTest->estado);

        // Intento 4: Intentar con clave correcta mientras está bloqueado debe dar 403
        $res4 = $this->postJson('/api/auth/login', [
            'nombre_usuario' => 'cajero_bloqueo',
            'contrasenia_usuario' => 'claveCorrecta123',
        ]);
        $res4->assertStatus(403);
        $this->assertStringContainsString('inactiva o bloqueada', $res4->json('message'));

        // Administrador reactiva la cuenta del usuario
        Sanctum::actingAs($this->adminPrincipal);
        $resUpdate = $this->putJson("/api/usuarios/{$cajeroTest->usuario_id}", [
            'estado' => 1,
        ]);
        $resUpdate->assertStatus(200);
        $cajeroTest->refresh();
        $this->assertEquals(1, (int) $cajeroTest->estado);

        // Intento 5: Ahora el usuario puede iniciar sesión correctamente
        $this->app['auth']->forgetGuards();
        $resLogin = $this->postJson('/api/auth/login', [
            'nombre_usuario' => 'cajero_bloqueo',
            'contrasenia_usuario' => 'claveCorrecta123',
        ]);
        $resLogin->assertStatus(200);
        $resLogin->assertJsonPath('success', true);
    }

    public function test_bitacora_es_inmutable_y_rechaza_creacion_modificacion_o_eliminacion_directa(): void
    {
        $bitacora = Bitacora::create([
            'id_usuario' => $this->adminPrincipal->usuario_id,
            'accion_bitacora' => 'ACCESO_SISTEMA',
            'descripcion_bitacora' => 'Inicio de sesión registrado',
            'fecha_hora_bitacora' => now(),
            'estado' => 1,
        ]);

        // 1. GET index y show están permitidos
        $this->getJson('/api/bitacoras')->assertStatus(200);
        $this->getJson("/api/bitacoras/{$bitacora->id_bitacora}")->assertStatus(200);

        // 2. POST (Creación manual) debe ser rechazada (405 Method Not Allowed)
        $this->postJson('/api/bitacoras', [
            'id_usuario' => $this->adminPrincipal->usuario_id,
            'accion_bitacora' => 'FALSA_ALARMA',
            'descripcion_bitacora' => 'Auditoría inyectada',
            'fecha_hora_bitacora' => now(),
            'estado' => 1,
        ])->assertStatus(405);

        // 3. PUT (Modificación) debe ser rechazada (405)
        $this->putJson("/api/bitacoras/{$bitacora->id_bitacora}", [
            'descripcion_bitacora' => 'Modificado maliciosamente',
        ])->assertStatus(405);

        // 4. DELETE (Eliminación de rastro) debe ser rechazada (405)
        $this->deleteJson("/api/bitacoras/{$bitacora->id_bitacora}")->assertStatus(405);
    }

    public function test_producto_rechaza_precio_venta_menor_que_costo_compra_en_creacion_y_actualizacion(): void
    {
        $cat = Categoria::create([
            'codigo_categoria' => 'CAT-MRG',
            'nombre_categoria' => 'Margen Categoria',
            'estado' => 1,
        ]);

        // 1. Rechaza store si precio_venta < costo_compra
        $resStoreFail = $this->postJson('/api/productos', [
            'id_categoria' => $cat->categoria_id,
            'codigo_producto' => 'PROD-MRG-1',
            'nombre_producto' => 'Producto Pérdida',
            'descripcion_producto' => 'Vendido a pérdida',
            'costo_compra' => 50,
            'precio_venta' => 40,
            'existencia_bodega' => 10,
            'existencia_minima' => 2,
            'estado' => 1,
        ]);
        $resStoreFail->assertStatus(422);
        $resStoreFail->assertJsonValidationErrors(['precio_venta']);

        // 2. Permite store válido
        $resStoreOk = $this->postJson('/api/productos', [
            'id_categoria' => $cat->categoria_id,
            'codigo_producto' => 'PROD-MRG-1',
            'nombre_producto' => 'Producto Rentable',
            'descripcion_producto' => 'Con margen',
            'costo_compra' => 50,
            'precio_venta' => 75,
            'existencia_bodega' => 10,
            'existencia_minima' => 2,
            'estado' => 1,
        ]);
        $resStoreOk->assertStatus(201);
        $prodId = $resStoreOk->json('producto_id');

        // 3. Rechaza update parcial de precio_venta inferior al costo existente
        $resUpdPrecioFail = $this->putJson("/api/productos/{$prodId}", [
            'precio_venta' => 45,
        ]);
        $resUpdPrecioFail->assertStatus(422);
        $resUpdPrecioFail->assertJsonValidationErrors(['precio_venta']);

        // 4. Rechaza update parcial de costo_compra superior al precio existente
        $resUpdCostoFail = $this->putJson("/api/productos/{$prodId}", [
            'costo_compra' => 80,
        ]);
        $resUpdCostoFail->assertStatus(422);
        $resUpdCostoFail->assertJsonValidationErrors(['costo_compra']);

        // 5. Permite update válido conjunto o parcial respetando costo <= precio
        $resUpdOk = $this->putJson("/api/productos/{$prodId}", [
            'costo_compra' => 60,
            'precio_venta' => 90,
        ]);
        $resUpdOk->assertStatus(200);
        $this->assertEquals(60, (float) $resUpdOk->json('costo_compra'));
        $this->assertEquals(90, (float) $resUpdOk->json('precio_venta'));
    }

    public function test_cliente_valida_formato_telefono_y_unicidad_codigo(): void
    {
        // 1. Rechaza teléfono con formato inválido
        $resTelInvalido = $this->postJson('/api/clientes', [
            'codigo_cliente' => 'CLI-001',
            'nombre_apellido_cliente' => 'Juan Perez',
            'telefono_cliente' => 'abc-invalido',
            'estado' => 1,
        ]);
        $resTelInvalido->assertStatus(422);
        $resTelInvalido->assertJsonValidationErrors(['telefono_cliente']);

        // 2. Permite teléfono válido (formato internacional o local)
        $resTelOk = $this->postJson('/api/clientes', [
            'codigo_cliente' => 'CLI-001',
            'nombre_apellido_cliente' => 'Juan Perez',
            'telefono_cliente' => '+504 9988-7766',
            'estado' => 1,
        ]);
        $resTelOk->assertStatus(201);

        // 3. Rechaza duplicado de codigo_cliente
        $resDup = $this->postJson('/api/clientes', [
            'codigo_cliente' => 'CLI-001',
            'nombre_apellido_cliente' => 'Maria Lopez',
            'telefono_cliente' => '88776655',
            'estado' => 1,
        ]);
        $resDup->assertStatus(422);
        $resDup->assertJsonValidationErrors(['codigo_cliente']);
    }

    public function test_categoria_cliente_y_bitacora_soportan_paginacion_y_busqueda(): void
    {
        // Setup categorías
        Categoria::create(['codigo_categoria' => 'CAT-PAG1', 'nombre_categoria' => 'Bebidas Frias', 'estado' => 1]);
        Categoria::create(['codigo_categoria' => 'CAT-PAG2', 'nombre_categoria' => 'Snacks Dulces', 'estado' => 1]);
        Categoria::create(['codigo_categoria' => 'CAT-PAG3', 'nombre_categoria' => 'Bebidas Calientes', 'estado' => 0]);

        // Paginación de categorías
        $resCatPag = $this->getJson('/api/categorias?por_pagina=2&page=1');
        $resCatPag->assertStatus(200);
        $resCatPag->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);
        $this->assertCount(2, $resCatPag->json('data'));

        // Búsqueda de categorías
        $resCatSearch = $this->getJson('/api/categorias?buscar=Bebidas');
        $resCatSearch->assertStatus(200);
        $this->assertCount(2, $resCatSearch->json());

        // Setup clientes
        Cliente::create(['codigo_cliente' => 'CLI-A1', 'nombre_apellido_cliente' => 'Carlos Mendoza', 'telefono_cliente' => '99001122', 'estado' => 1]);
        Cliente::create(['codigo_cliente' => 'CLI-A2', 'nombre_apellido_cliente' => 'Lucia Fernandez', 'telefono_cliente' => '88001122', 'estado' => 1]);

        // Paginación y búsqueda de clientes
        $resCliPag = $this->getJson('/api/clientes?por_pagina=1&page=1');
        $resCliPag->assertStatus(200);
        $resCliPag->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);
        $this->assertCount(1, $resCliPag->json('data'));

        $resCliSearch = $this->getJson('/api/clientes?buscar=Mendoza');
        $resCliSearch->assertStatus(200);
        $this->assertCount(1, $resCliSearch->json());

        // Paginación de bitácoras
        $resBitPag = $this->getJson('/api/bitacoras?por_pagina=1&page=1');
        $resBitPag->assertStatus(200);
        $resBitPag->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);
    }

    public function test_merma_requiere_tipo_merma_valido_y_justificacion(): void
    {
        $cat = Categoria::create([
            'codigo_categoria' => 'CAT-MERM-1',
            'nombre_categoria' => 'Categoría Mermas',
            'estado' => 1,
        ]);

        $prod = Producto::create([
            'id_categoria' => $cat->categoria_id,
            'codigo_producto' => 'PROD-M1',
            'nombre_producto' => 'Leche Entera 1L',
            'descripcion_producto' => 'Leche pasteurizada 1L',
            'costo_compra' => 20.00,
            'precio_venta' => 28.00,
            'existencia_bodega' => 15,
            'existencia_minima' => 2,
            'estado' => 1,
        ]);

        // 1. Falla si falta tipo_merma en Salida por Merma
        $resSinTipo = $this->postJson('/api/movimientos-inventario', [
            'id_producto' => $prod->producto_id,
            'id_usuario' => $this->adminPrincipal->usuario_id,
            'tipo_movimiento' => 'Salida por Merma',
            'cantidad_movimiento' => 2,
            'fecha_movimiento' => now()->toDateString(),
            'justificacion' => 'Bolsas rotas',
        ]);
        $resSinTipo->assertStatus(422);
        $resSinTipo->assertJsonValidationErrors(['tipo_merma']);

        // 2. Falla si tipo_merma es un valor no permitido
        $resTipoInvalido = $this->postJson('/api/movimientos-inventario', [
            'id_producto' => $prod->producto_id,
            'id_usuario' => $this->adminPrincipal->usuario_id,
            'tipo_movimiento' => 'Salida por Merma',
            'tipo_merma' => 'Robo o Hurto Invalido',
            'cantidad_movimiento' => 2,
            'fecha_movimiento' => now()->toDateString(),
            'justificacion' => 'Bolsas rotas',
        ]);
        $resTipoInvalido->assertStatus(422);
        $resTipoInvalido->assertJsonValidationErrors(['tipo_merma']);

        // 3. Falla si falta justificación en Salida por Merma
        $resSinJust = $this->postJson('/api/movimientos-inventario', [
            'id_producto' => $prod->producto_id,
            'id_usuario' => $this->adminPrincipal->usuario_id,
            'tipo_movimiento' => 'Salida por Merma',
            'tipo_merma' => 'Deterioro/Vencimiento',
            'cantidad_movimiento' => 2,
            'fecha_movimiento' => now()->toDateString(),
        ]);
        $resSinJust->assertStatus(422);
        $resSinJust->assertJsonValidationErrors(['justificacion']);
    }

    public function test_merma_cuantifica_costo_unitario_y_total_de_perdidas_correctamente(): void
    {
        $cat = Categoria::create([
            'codigo_categoria' => 'CAT-MERM-2',
            'nombre_categoria' => 'Lácteos y Derivados',
            'estado' => 1,
        ]);

        $prod = Producto::create([
            'id_categoria' => $cat->categoria_id,
            'codigo_producto' => 'PROD-M2',
            'nombre_producto' => 'Yogurt Fresa 500ml',
            'descripcion_producto' => 'Yogurt de fresa 500ml',
            'costo_compra' => 15.50,
            'precio_venta' => 22.00,
            'existencia_bodega' => 20,
            'existencia_minima' => 5,
            'estado' => 1,
        ]);

        // Registrar merma de 4 unidades
        $res = $this->postJson('/api/movimientos-inventario', [
            'id_producto' => $prod->producto_id,
            'id_usuario' => $this->adminPrincipal->usuario_id,
            'tipo_movimiento' => 'Salida por Merma',
            'tipo_merma' => 'Rotura/Accidente',
            'cantidad_movimiento' => 4,
            'fecha_movimiento' => now()->toDateTimeString(),
            'justificacion' => 'Frasco quebrado al descargar mercadería',
        ]);

        $res->assertStatus(201);
        $res->assertJsonPath('success', true);

        // Verificar cuantificación económica en la respuesta
        $mov = $res->json('movimiento');
        $this->assertEquals('Salida por Merma', $mov['tipo_movimiento']);
        $this->assertEquals('Rotura/Accidente', $mov['tipo_merma']);
        $this->assertEquals(15.50, (float) $mov['costo_unitario']);
        $this->assertEquals(62.00, (float) $mov['costo_total_perdida']); // 4 * 15.50 = 62.00
        $this->assertEquals(4, $mov['cantidad_movimimiento']);
        $this->assertEquals(20, $mov['stock_anterior_producto']);
        $this->assertEquals(16, $mov['stock_resultante_producto']);

        // Verificar en base de datos tabla movimiento_inventario
        $this->assertDatabaseHas('movimiento_inventario', [
            'movimiento_inventario_id' => $mov['movimiento_inventario_id'],
            'tipo_movimiento' => 'Salida por Merma',
            'tipo_merma' => 'Rotura/Accidente',
            'costo_unitario' => 15.50,
            'costo_total_perdida' => 62.00,
            'cantidad_movimimiento' => 4,
            'stock_resultante_producto' => 16,
        ]);

        // Verificar que el stock físico del producto se redujo
        $this->assertEquals(16, $prod->fresh()->existencia_bodega);

        // Verificar registro de auditoría en bitácora
        $this->assertDatabaseHas('bitacora', [
            'id_usuario' => $this->adminPrincipal->usuario_id,
            'accion_bitacora' => 'MOVIMIENTO_INVENTARIO',
        ]);
    }

    public function test_movimiento_no_merma_no_exige_ni_almacena_tipo_ni_costo_perdida(): void
    {
        $cat = Categoria::create([
            'codigo_categoria' => 'CAT-MERM-3',
            'nombre_categoria' => 'Abarrotes',
            'estado' => 1,
        ]);

        $prod = Producto::create([
            'id_categoria' => $cat->categoria_id,
            'codigo_producto' => 'PROD-M3',
            'nombre_producto' => 'Arroz 1lb',
            'descripcion_producto' => 'Arroz blanco 1lb',
            'costo_compra' => 12.00,
            'precio_venta' => 18.00,
            'existencia_bodega' => 10,
            'existencia_minima' => 2,
            'estado' => 1,
        ]);

        // Entrada normal sin tipo_merma
        $resEntrada = $this->postJson('/api/movimientos-inventario', [
            'id_producto' => $prod->producto_id,
            'id_usuario' => $this->adminPrincipal->usuario_id,
            'tipo_movimiento' => 'Entrada',
            'cantidad_movimiento' => 5,
            'fecha_movimiento' => now()->toDateString(),
        ]);
        $resEntrada->assertStatus(201);
        $movEntrada = $resEntrada->json('movimiento');
        $this->assertNull($movEntrada['tipo_merma']);
        $this->assertNull($movEntrada['costo_unitario']);
        $this->assertNull($movEntrada['proveedor_nombre']);
        $this->assertNull($movEntrada['numero_factura_recibo']);

        $this->assertDatabaseHas('movimiento_inventario', [
            'movimiento_inventario_id' => $movEntrada['movimiento_inventario_id'],
            'tipo_movimiento' => 'Entrada',
            'tipo_merma' => null,
            'costo_unitario' => null,
            'costo_total_perdida' => null,
            'proveedor_nombre' => null,
            'numero_factura_recibo' => null,
            'stock_resultante_producto' => 15,
        ]);
    }

    public function test_entrada_inventario_permite_guardar_proveedor_y_factura_opcionalmente(): void
    {
        $cat = Categoria::create([
            'codigo_categoria' => 'CAT-PROV-1',
            'nombre_categoria' => 'Lácteos y Derivados',
            'estado' => 1,
        ]);

        $prod = Producto::create([
            'id_categoria' => $cat->categoria_id,
            'codigo_producto' => 'PROD-P1',
            'nombre_producto' => 'Queso Seco 1lb',
            'descripcion_producto' => 'Queso seco artesanal',
            'costo_compra' => 60.00,
            'precio_venta' => 85.00,
            'existencia_bodega' => 10,
            'existencia_minima' => 2,
            'estado' => 1,
        ]);

        // 1. Entrada de inventario CON proveedor y número de factura/recibo
        $resConDatos = $this->postJson('/api/movimientos-inventario', [
            'id_producto' => $prod->producto_id,
            'id_usuario' => $this->adminPrincipal->usuario_id,
            'tipo_movimiento' => 'Entrada por Compra',
            'cantidad_movimiento' => 10,
            'fecha_movimiento' => now()->toDateString(),
            'proveedor_nombre' => 'Lácteos El Carmen S.A.',
            'numero_factura_recibo' => 'FAC-2026-00458',
        ]);

        $resConDatos->assertStatus(201);
        $movCon = $resConDatos->json('movimiento');
        $this->assertEquals('Lácteos El Carmen S.A.', $movCon['proveedor_nombre']);
        $this->assertEquals('FAC-2026-00458', $movCon['numero_factura_recibo']);

        $this->assertDatabaseHas('movimiento_inventario', [
            'movimiento_inventario_id' => $movCon['movimiento_inventario_id'],
            'tipo_movimiento' => 'Entrada por Compra',
            'proveedor_nombre' => 'Lácteos El Carmen S.A.',
            'numero_factura_recibo' => 'FAC-2026-00458',
            'stock_resultante_producto' => 20,
        ]);
        $this->assertEquals(20, $prod->fresh()->existencia_bodega);

        // 2. Entrada de inventario SIN proveedor ni número de factura/recibo (opcional)
        $resSinDatos = $this->postJson('/api/movimientos-inventario', [
            'id_producto' => $prod->producto_id,
            'id_usuario' => $this->adminPrincipal->usuario_id,
            'tipo_movimiento' => 'Entrada',
            'cantidad_movimiento' => 5,
            'fecha_movimiento' => now()->toDateString(),
        ]);

        $resSinDatos->assertStatus(201);
        $movSin = $resSinDatos->json('movimiento');
        $this->assertNull($movSin['proveedor_nombre']);
        $this->assertNull($movSin['numero_factura_recibo']);

        $this->assertDatabaseHas('movimiento_inventario', [
            'movimiento_inventario_id' => $movSin['movimiento_inventario_id'],
            'tipo_movimiento' => 'Entrada',
            'proveedor_nombre' => null,
            'numero_factura_recibo' => null,
            'stock_resultante_producto' => 25,
        ]);
        $this->assertEquals(25, $prod->fresh()->existencia_bodega);

        // 3. Validar longitud máxima de proveedor_nombre (máx 128)
        $resProveedorLargo = $this->postJson('/api/movimientos-inventario', [
            'id_producto' => $prod->producto_id,
            'id_usuario' => $this->adminPrincipal->usuario_id,
            'tipo_movimiento' => 'Entrada',
            'cantidad_movimiento' => 1,
            'fecha_movimiento' => now()->toDateString(),
            'proveedor_nombre' => str_repeat('P', 129),
        ]);
        $resProveedorLargo->assertStatus(422);
        $resProveedorLargo->assertJsonValidationErrors(['proveedor_nombre']);

        // 4. Validar longitud máxima de numero_factura_recibo (máx 64)
        $resFacturaLarga = $this->postJson('/api/movimientos-inventario', [
            'id_producto' => $prod->producto_id,
            'id_usuario' => $this->adminPrincipal->usuario_id,
            'tipo_movimiento' => 'Entrada',
            'cantidad_movimiento' => 1,
            'fecha_movimiento' => now()->toDateString(),
            'numero_factura_recibo' => str_repeat('F', 65),
        ]);
        $resFacturaLarga->assertStatus(422);
        $resFacturaLarga->assertJsonValidationErrors(['numero_factura_recibo']);

        // 5. Movimientos que no son entradas (ej. Salida por Merma) no almacenan proveedor ni factura
        $resSalida = $this->postJson('/api/movimientos-inventario', [
            'id_producto' => $prod->producto_id,
            'id_usuario' => $this->adminPrincipal->usuario_id,
            'tipo_movimiento' => 'Salida por Merma',
            'tipo_merma' => 'Deterioro/Vencimiento',
            'cantidad_movimiento' => 1,
            'fecha_movimiento' => now()->toDateString(),
            'justificacion' => 'Bolsa rota',
            'proveedor_nombre' => 'Proveedor Ignorado',
            'numero_factura_recibo' => 'FAC-IGNORADA',
        ]);
        $resSalida->assertStatus(201);
        $movSalida = $resSalida->json('movimiento');
        $this->assertNull($movSalida['proveedor_nombre']);
        $this->assertNull($movSalida['numero_factura_recibo']);
    }

    public function test_dashboard_resumen_incluye_total_perdidas_mermas_del_mes_en_curso(): void
    {
        $cat = Categoria::create([
            'codigo_categoria' => 'CAT-DASH-M',
            'nombre_categoria' => 'Frutas y Verduras',
            'estado' => 1,
        ]);

        $prod = Producto::create([
            'id_categoria' => $cat->categoria_id,
            'codigo_producto' => 'PROD-FRUT',
            'nombre_producto' => 'Manzana Roja',
            'descripcion_producto' => 'Manzana roja fresca',
            'costo_compra' => 10.00,
            'precio_venta' => 15.00,
            'existencia_bodega' => 50,
            'existencia_minima' => 5,
            'estado' => 1,
        ]);

        // 1. Merma 1 del mes actual: 3 manzanas vencidas (3 * 10 = 30)
        Movimiento_inventario::create([
            'id_producto' => $prod->producto_id,
            'id_usuario' => $this->adminPrincipal->usuario_id,
            'tipo_movimiento' => 'Salida por Merma',
            'tipo_merma' => 'Deterioro/Vencimiento',
            'costo_unitario' => 10.00,
            'costo_total_perdida' => 30.00,
            'cantidad_movimimiento' => 3,
            'stock_anterior_producto' => 50,
            'stock_resultante_producto' => 47,
            'fecha_movimiento' => now()->startOfMonth()->addDays(2),
            'estado' => 1,
        ]);

        // 2. Merma 2 del mes actual: 2 manzanas descartadas (2 * 10 = 20)
        Movimiento_inventario::create([
            'id_producto' => $prod->producto_id,
            'id_usuario' => $this->adminPrincipal->usuario_id,
            'tipo_movimiento' => 'Salida por Merma',
            'tipo_merma' => 'Descarte Tecnico',
            'costo_unitario' => 10.00,
            'costo_total_perdida' => 20.00,
            'cantidad_movimimiento' => 2,
            'stock_anterior_producto' => 47,
            'stock_resultante_producto' => 45,
            'fecha_movimiento' => now()->startOfMonth()->addDays(5),
            'estado' => 1,
        ]);

        // 3. Merma de otro mes (hace 2 meses): NO debe contarse (5 * 10 = 50)
        Movimiento_inventario::create([
            'id_producto' => $prod->producto_id,
            'id_usuario' => $this->adminPrincipal->usuario_id,
            'tipo_movimiento' => 'Salida por Merma',
            'tipo_merma' => 'Rotura/Accidente',
            'costo_unitario' => 10.00,
            'costo_total_perdida' => 50.00,
            'cantidad_movimimiento' => 5,
            'stock_anterior_producto' => 55,
            'stock_resultante_producto' => 50,
            'fecha_movimiento' => now()->subMonths(2)->startOfMonth(),
            'estado' => 1,
        ]);

        // Consultar Dashboard
        $res = $this->getJson('/api/dashboard/resumen');
        $res->assertStatus(200);

        // Validar que totalPerdidasMermasMes es exactamente 50 (30 + 20) y no 100
        $data = $res->json();
        $this->assertEquals(50.00, (float) $data['totalPerdidasMermasMes']);
        $this->assertEquals(50.00, (float) $data['stats']['totalPerdidasMermasMes']);
    }
}
