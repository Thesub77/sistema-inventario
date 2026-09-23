<?php

namespace Tests\Feature;

use App\Models\Bitacora;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Usuario;
use App\Models\Venta;
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
        $this->getJson("/api/bitacoras/{$bitacora->bitacora_id}")->assertStatus(200);

        // 2. POST (Creación manual) debe ser rechazada (405 Method Not Allowed)
        $this->postJson('/api/bitacoras', [
            'id_usuario' => $this->adminPrincipal->usuario_id,
            'accion_bitacora' => 'FALSA_ALARMA',
            'descripcion_bitacora' => 'Auditoría inyectada',
            'fecha_hora_bitacora' => now(),
            'estado' => 1,
        ])->assertStatus(405);

        // 3. PUT (Modificación) debe ser rechazada (405)
        $this->putJson("/api/bitacoras/{$bitacora->bitacora_id}", [
            'descripcion_bitacora' => 'Modificado maliciosamente',
        ])->assertStatus(405);

        // 4. DELETE (Eliminación de rastro) debe ser rechazada (405)
        $this->deleteJson("/api/bitacoras/{$bitacora->bitacora_id}")->assertStatus(405);
    }
}
