<?php

namespace Tests\Feature;

use App\Models\Bitacora;
use App\Models\Categoria;
use App\Models\Movimiento_inventario;
use App\Models\Producto;
use App\Models\Proveedor;
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

    public function test_proveedor_se_desactiva_logicamente(): void
    {
        $proveedor = Proveedor::create([
            'nombre_comercial' => 'Distribuidora Central S.A.',
            'contacto_nombre' => 'Juan Perez',
            'telefono' => '88889999',
            'estado' => 1,
        ]);

        $res = $this->deleteJson("/api/proveedores/{$proveedor->proveedor_id}");
        $res->assertStatus(200);
        $res->assertJsonPath('success', true);

        // Verificar que el registro persiste pero con estado 0
        $this->assertDatabaseHas('proveedor', [
            'proveedor_id' => $proveedor->proveedor_id,
            'estado' => 0,
        ]);
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

        // Verificar que el usuario eliminado lógicamente (estado=0) ya NO aparece en la lista de usuarios
        Sanctum::actingAs($this->adminPrincipal);
        $resList = $this->getJson('/api/usuarios');
        $resList->assertStatus(200);
        $usuariosIds = collect($resList->json('data') ?? $resList->json())->pluck('usuario_id')->all();
        $this->assertNotContains($cajero->usuario_id, $usuariosIds);
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

    public function test_admin_secundario_no_puede_modificar_ni_eliminar_al_administrador_principal(): void
    {
        // Actuamos como adminSecundario
        Sanctum::actingAs($this->adminSecundario);

        // 1. Intentar modificar nombre / usuario del Admin Principal -> 403
        $resMod = $this->putJson("/api/usuarios/{$this->adminPrincipal->usuario_id}", [
            'nombre_apellido' => 'Intento Hack',
            'nombre_usuario' => 'hacked_admin',
        ]);
        $resMod->assertStatus(403);
        $resMod->assertJsonPath('message', 'No se puede modificar la cuenta del Administrador Principal del sistema.');

        // 2. Intentar cambiar rol del Admin Principal -> 403
        $resRol = $this->putJson("/api/usuarios/{$this->adminPrincipal->usuario_id}", [
            'id_rol' => $this->rolCajero->rol_id,
        ]);
        $resRol->assertStatus(403);
        $resRol->assertJsonPath('message', 'No se puede modificar la cuenta del Administrador Principal del sistema.');

        // 3. Intentar desactivar vía PUT estado=0 -> 403
        $resEstado = $this->putJson("/api/usuarios/{$this->adminPrincipal->usuario_id}", [
            'estado' => 0,
        ]);
        $resEstado->assertStatus(403);
        $resEstado->assertJsonPath('message', 'No se puede modificar la cuenta del Administrador Principal del sistema.');

        // 4. Intentar eliminar vía DELETE -> 403
        $resDel = $this->deleteJson("/api/usuarios/{$this->adminPrincipal->usuario_id}");
        $resDel->assertStatus(403);
        $resDel->assertJsonPath('message', 'No se puede desactivar al Administrador Principal del sistema.');

        // 5. El Administrador Principal sí puede editar sus propios datos (nombre, username)
        Sanctum::actingAs($this->adminPrincipal);
        $resSelf = $this->putJson("/api/usuarios/{$this->adminPrincipal->usuario_id}", [
            'nombre_apellido' => 'Diego Q.',
            'nombre_usuario' => 'si_dquiroz_updated',
        ]);
        $resSelf->assertStatus(200);

        // Pero el Administrador Principal NO puede cambiar su propio rol ni desactivarse
        $resSelfRol = $this->putJson("/api/usuarios/{$this->adminPrincipal->usuario_id}", [
            'id_rol' => $this->rolCajero->rol_id,
        ]);
        $resSelfRol->assertStatus(403);

        $resSelfDel = $this->deleteJson("/api/usuarios/{$this->adminPrincipal->usuario_id}");
        $resSelfDel->assertStatus(403);
    }

    public function test_no_se_puede_desactivar_al_unico_administrador_activo(): void
    {
        // Desactivamos directamente a adminPrincipal para probar la protección sobre el último admin
        Usuario::where('usuario_id', $this->adminPrincipal->usuario_id)->update(['estado' => 0]);

        // Ahora solo queda adminSecundario como administrador activo
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

        // Intentar desactivar al único admin activo vía DELETE -> 403
        $resUnico = $this->deleteJson("/api/usuarios/{$this->adminSecundario->usuario_id}");
        $resUnico->assertStatus(403);
        $resUnico->assertJsonPath('message', 'No se puede desactivar al único Administrador activo del sistema.');

        // Intentar desactivar al único admin activo vía PUT estado=0 -> 403
        $resPut = $this->putJson("/api/usuarios/{$this->adminSecundario->usuario_id}", [
            'estado' => 0,
        ]);
        $resPut->assertStatus(403);
        $resPut->assertJsonPath('message', 'No se puede desactivar o bloquear al único Administrador activo del sistema.');

        // Intentar cambiar rol al único admin activo vía PUT id_rol -> 403
        $resRol = $this->putJson("/api/usuarios/{$this->adminSecundario->usuario_id}", [
            'id_rol' => $this->rolCajero->rol_id,
        ]);
        $resRol->assertStatus(403);
        $resRol->assertJsonPath('message', 'No puedes cambiar el rol al único Administrador activo del sistema.');
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

        // Verificar que en base de datos el usuario quedó bloqueado (bloqueado = 1, pero estado = 1 para no considerarse eliminado)
        $cajeroTest->refresh();
        $this->assertEquals(1, (int) $cajeroTest->bloqueado);
        $this->assertEquals(1, (int) $cajeroTest->estado);

        // Administrador consulta la lista de usuarios: el usuario bloqueado SÍ debe aparecer en la lista para poder ser gestionado
        Sanctum::actingAs($this->adminPrincipal);
        $resListBloqueado = $this->getJson('/api/usuarios');
        $resListBloqueado->assertStatus(200);
        $usuariosIdsBloqueado = collect($resListBloqueado->json('data') ?? $resListBloqueado->json())->pluck('usuario_id')->all();
        $this->assertContains($cajeroTest->usuario_id, $usuariosIdsBloqueado);

        // Intento 4: Intentar con clave correcta mientras está bloqueado debe dar 403
        $this->app['auth']->forgetGuards();
        $res4 = $this->postJson('/api/auth/login', [
            'nombre_usuario' => 'cajero_bloqueo',
            'contrasenia_usuario' => 'claveCorrecta123',
        ]);
        $res4->assertStatus(403);
        $this->assertStringContainsString('inactiva o bloqueada', $res4->json('message'));

        // Administrador reactiva/desbloquea la cuenta del usuario cambiando bloqueado a 0
        Sanctum::actingAs($this->adminPrincipal);
        $resUpdate = $this->putJson("/api/usuarios/{$cajeroTest->usuario_id}", [
            'bloqueado' => 0,
        ]);
        $resUpdate->assertStatus(200);
        $cajeroTest->refresh();
        $this->assertEquals(0, (int) $cajeroTest->bloqueado);
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

    public function test_usuario_bloqueado_puede_ser_eliminado_logicamente_y_desaparece_del_sistema(): void
    {
        Sanctum::actingAs($this->adminPrincipal);

        $usuario = Usuario::create([
            'id_rol' => $this->rolCajero->rol_id,
            'nombre_apellido' => 'Usuario Para Eliminar',
            'nombre_usuario' => 'user_bloq_elim',
            'contrasenia_usuario' => bcrypt('password123'),
            'fecha_registro' => now(),
            'estado' => 1,
            'bloqueado' => 1,
        ]);

        // 1. Estando bloqueado (bloqueado=1, estado=1), aparece en la lista de usuarios
        $resList = $this->getJson('/api/usuarios');
        $resList->assertStatus(200);
        $ids = collect($resList->json('data') ?? $resList->json())->pluck('usuario_id')->all();
        $this->assertContains($usuario->usuario_id, $ids);

        // 2. Administrador decide eliminarlo lógicamente (DELETE /api/usuarios/{id})
        $resDelete = $this->deleteJson("/api/usuarios/{$usuario->usuario_id}");
        $resDelete->assertStatus(200);
        $resDelete->assertJsonPath('success', true);

        // 3. En BD: estado queda en 0 (baja lógica tiene mayor peso)
        $usuario->refresh();
        $this->assertEquals(0, (int) $usuario->estado);
        $this->assertEquals(1, (int) $usuario->bloqueado);
        $this->assertFalse($usuario->estaActivo());

        // 4. Ya NO aparece en la lista de usuarios del sistema
        $resListPostDelete = $this->getJson('/api/usuarios');
        $resListPostDelete->assertStatus(200);
        $idsPostDelete = collect($resListPostDelete->json('data') ?? $resListPostDelete->json())->pluck('usuario_id')->all();
        $this->assertNotContains($usuario->usuario_id, $idsPostDelete);

        // 5. Al intentar iniciar sesión, la eliminación lógica prevalece sobre el bloqueo
        $this->app['auth']->forgetGuards();
        $resLogin = $this->postJson('/api/auth/login', [
            'nombre_usuario' => 'user_bloq_elim',
            'contrasenia_usuario' => 'password123',
        ]);
        $resLogin->assertStatus(403);
        $this->assertStringContainsString('dada de baja', $resLogin->json('message'));
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

    public function test_proveedor_valida_campos_requeridos_y_plazo_credito(): void
    {
        // 1. Rechaza si falta nombre_comercial
        $resSinNombre = $this->postJson('/api/proveedores', [
            'contacto_vendedor' => 'Juan Vendedor',
            'telefono' => '88889999',
            'estado' => 1,
        ]);
        $resSinNombre->assertStatus(422);
        $resSinNombre->assertJsonValidationErrors(['nombre_comercial']);

        // 2. Rechaza plazo de crédito inválido (ej. negativo o string)
        $resPlazoInvalido = $this->postJson('/api/proveedores', [
            'nombre_comercial' => 'Proveedor Invalido',
            'plazo_credito_dias' => -5,
            'estado' => 1,
        ]);
        $resPlazoInvalido->assertStatus(422);
        $resPlazoInvalido->assertJsonValidationErrors(['plazo_credito_dias']);

        // 3. Permite proveedor válido
        $resOk = $this->postJson('/api/proveedores', [
            'nombre_comercial' => 'Distribuidora Global',
            'contacto_vendedor' => 'Carlos Gomez',
            'telefono' => '88776655',
            'plazo_credito_dias' => 15,
            'estado' => 1,
        ]);
        $resOk->assertStatus(201);
    }

    public function test_categoria_proveedor_y_bitacora_soportan_paginacion_y_busqueda(): void
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

        // Setup proveedores
        Proveedor::create(['nombre_comercial' => 'Carlos Mendoza Distribuidora', 'telefono' => '99001122', 'estado' => 1]);
        Proveedor::create(['nombre_comercial' => 'Lucia Fernandez Comercial', 'telefono' => '88001122', 'estado' => 1]);

        // Paginación y búsqueda de proveedores
        $resProvPag = $this->getJson('/api/proveedores?por_pagina=1&page=1');
        $resProvPag->assertStatus(200);
        $resProvPag->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);
        $this->assertCount(1, $resProvPag->json('data'));

        $resProvSearch = $this->getJson('/api/proveedores?buscar=Mendoza');
        $resProvSearch->assertStatus(200);
        $this->assertCount(1, $resProvSearch->json());

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

        $this->assertDatabaseHas('movimiento_inventario', [
            'movimiento_inventario_id' => $movEntrada['movimiento_inventario_id'],
            'tipo_movimiento' => 'Entrada',
            'tipo_merma' => null,
            'costo_unitario' => null,
            'costo_total_perdida' => null,
            'stock_resultante_producto' => 15,
        ]);
    }

    public function test_entrada_inventario_actualiza_stock_correctamente(): void
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

        // Entrada de inventario estándar
        $res = $this->postJson('/api/movimientos-inventario', [
            'id_producto' => $prod->producto_id,
            'id_usuario' => $this->adminPrincipal->usuario_id,
            'tipo_movimiento' => 'Entrada por Compra',
            'cantidad_movimiento' => 10,
            'fecha_movimiento' => now()->toDateString(),
        ]);

        $res->assertStatus(201);
        $mov = $res->json('movimiento');

        $this->assertDatabaseHas('movimiento_inventario', [
            'movimiento_inventario_id' => $mov['movimiento_inventario_id'],
            'tipo_movimiento' => 'Entrada por Compra',
            'stock_resultante_producto' => 20,
        ]);
        $this->assertEquals(20, $prod->fresh()->existencia_bodega);
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
