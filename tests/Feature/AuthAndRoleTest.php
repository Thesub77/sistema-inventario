<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthAndRoleTest extends TestCase
{
    use RefreshDatabase;

    protected Rol $rolAdmin;

    protected Rol $rolCajero;

    protected Usuario $admin;

    protected Usuario $cajero;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rolAdmin = Rol::create([
            'nombre_rol' => 'Administrador',
            'descripcion_rol' => 'Acceso total',
            'permisos' => ['*'],
            'estado' => 1,
        ]);

        $this->rolCajero = Rol::create([
            'nombre_rol' => 'Cajero',
            'descripcion_rol' => 'Acceso a cobros',
            'permisos' => ['pos.acceso', 'ventas.ver', 'cajas.gestionar'],
            'estado' => 1,
        ]);

        $this->admin = Usuario::create([
            'id_rol' => $this->rolAdmin->rol_id,
            'nombre_apellido' => 'Diego Quiroz',
            'nombre_usuario' => 'si_dquiroz',
            'contrasenia_usuario' => Hash::make('admin123'),
            'fecha_registro' => now(),
            'estado' => 1,
        ]);

        $this->cajero = Usuario::create([
            'id_rol' => $this->rolCajero->rol_id,
            'nombre_apellido' => 'Cajero Mostrador',
            'nombre_usuario' => 'cajero_test',
            'contrasenia_usuario' => Hash::make('cajero123'),
            'fecha_registro' => now(),
            'estado' => 1,
        ]);
    }

    public function test_login_con_credenciales_correctas_devuelve_token_y_permisos(): void
    {
        $res = $this->postJson('/api/auth/login', [
            'nombre_usuario' => 'si_dquiroz',
            'contrasenia_usuario' => 'admin123',
        ]);

        $res->assertStatus(200);
        $res->assertJsonPath('success', true);
        $res->assertJsonStructure([
            'success',
            'message',
            'token',
            'usuario' => [
                'usuario_id',
                'nombre_apellido',
                'nombre_usuario',
                'rol',
                'permisos',
            ],
        ]);
        $this->assertNotEmpty($res->json('token'));
        $this->assertSame('Administrador', $res->json('usuario.rol'));
        $this->assertSame(['*'], $res->json('usuario.permisos'));

        $this->assertDatabaseHas('bitacora', [
            'id_usuario' => $this->admin->usuario_id,
            'accion_bitacora' => 'LOGIN_EXITOSO',
        ]);
    }

    public function test_login_con_contrasena_incorrecta_es_rechazado_y_registra_bitacora(): void
    {
        $res = $this->postJson('/api/auth/login', [
            'nombre_usuario' => 'si_dquiroz',
            'contrasenia_usuario' => 'password_erroneo',
        ]);

        $res->assertStatus(401);
        $res->assertJsonPath('success', false);

        $this->assertDatabaseHas('bitacora', [
            'id_usuario' => $this->admin->usuario_id,
            'accion_bitacora' => 'LOGIN_FALLIDO',
        ]);
    }

    public function test_login_con_usuario_inactivo_es_bloqueado(): void
    {
        $this->admin->update(['estado' => 0]);

        $res = $this->postJson('/api/auth/login', [
            'nombre_usuario' => 'si_dquiroz',
            'contrasenia_usuario' => 'admin123',
        ]);

        $res->assertStatus(403);
        $res->assertJsonPath('success', false);

        $this->assertDatabaseHas('bitacora', [
            'id_usuario' => $this->admin->usuario_id,
            'accion_bitacora' => 'LOGIN_BLOQUEADO',
        ]);
    }

    public function test_rate_limiting_bloquea_tras_intentos_excesivos(): void
    {
        // 5 intentos fallidos permitidos
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'nombre_usuario' => 'si_dquiroz',
                'contrasenia_usuario' => 'password_incorrecto_'.$i,
            ])->assertStatus(401);
        }

        // El 6to intento debe recibir 429 Too Many Requests
        $res = $this->postJson('/api/auth/login', [
            'nombre_usuario' => 'si_dquiroz',
            'contrasenia_usuario' => 'password_incorrecto_6',
        ]);

        $res->assertStatus(429);
    }

    public function test_rutas_protegidas_rechazan_peticiones_sin_token(): void
    {
        $this->getJson('/api/usuarios')->assertStatus(401);
        $this->getJson('/api/ventas')->assertStatus(401);
        $this->getJson('/api/roles')->assertStatus(401);
    }

    public function test_usuario_sin_permiso_recibe_403_en_rutas_restringidas(): void
    {
        Sanctum::actingAs($this->cajero);

        // El cajero no tiene permiso 'usuarios.gestionar' ni 'dashboard.ver' ni 'inventario.gestionar'
        $this->getJson('/api/usuarios')->assertStatus(403);
        $this->getJson('/api/roles')->assertStatus(403);
        $this->getJson('/api/bitacoras')->assertStatus(403);
        $this->getJson('/api/dashboard/resumen')->assertStatus(403);
        $this->getJson('/api/movimientos-inventario')->assertStatus(403);
    }

    public function test_administrador_accede_a_todas_las_rutas_protegidas(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/usuarios')->assertStatus(200);
        $this->getJson('/api/roles')->assertStatus(200);
        $this->getJson('/api/bitacoras')->assertStatus(200);
        $this->getJson('/api/dashboard/resumen')->assertStatus(200);
        $this->getJson('/api/movimientos-inventario')->assertStatus(200);
        $this->getJson('/api/ventas')->assertStatus(200);
        $this->getJson('/api/productos')->assertStatus(200);
    }

    public function test_usuario_con_permiso_accede_a_su_modulo(): void
    {
        Sanctum::actingAs($this->cajero);

        // El cajero sí tiene 'ventas.ver', 'cajas.gestionar' y 'pos.acceso'
        $this->getJson('/api/ventas')->assertStatus(200);
        $this->getJson('/api/cajas')->assertStatus(200);
        $this->getJson('/api/productos')->assertStatus(200);
        $this->getJson('/api/categorias')->assertStatus(200);

        // Pero no puede crear productos sin permiso 'inventario.gestionar' o 'productos.gestionar'
        $this->postJson('/api/productos', [
            'nombre_producto' => 'Producto no autorizado',
        ])->assertStatus(403);
    }

    public function test_perfil_usuario_autenticado_me(): void
    {
        Sanctum::actingAs($this->admin);

        $res = $this->getJson('/api/auth/me');
        $res->assertStatus(200);
        $res->assertJsonPath('usuario.nombre_usuario', 'si_dquiroz');
        $res->assertJsonPath('usuario.rol', 'Administrador');
    }

    public function test_logout_invalida_token(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $res = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout');

        $res->assertStatus(200);
        $res->assertJsonPath('success', true);

        // El token fue eliminado
        $this->assertDatabaseCount('personal_access_tokens', 0);

        // La siguiente petición con el mismo token debe ser 401
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertStatus(401);
    }

    public function test_rol_administrador_no_puede_ser_eliminado(): void
    {
        Sanctum::actingAs($this->admin);

        $res = $this->deleteJson('/api/roles/'.$this->rolAdmin->rol_id);
        $res->assertStatus(403);
        $res->assertJsonPath('success', false);
    }

    public function test_rol_administrador_no_puede_ser_modificado_ni_desactivado(): void
    {
        Sanctum::actingAs($this->admin);

        // Intentar cambiar nombre o permisos al rol Administrador
        $res = $this->putJson('/api/roles/'.$this->rolAdmin->rol_id, [
            'nombre_rol' => 'Admin Modificado',
        ]);
        $res->assertStatus(403);
        $res->assertJsonPath('success', false);

        // Intentar desactivar al rol Administrador
        $res2 = $this->putJson('/api/roles/'.$this->rolAdmin->rol_id, [
            'estado' => 0,
        ]);
        $res2->assertStatus(403);
        $res2->assertJsonPath('success', false);
    }

    public function test_desactivar_un_rol_bloquea_a_todos_los_usuarios_asignados_y_revoca_sesiones(): void
    {
        Sanctum::actingAs($this->admin);

        // Cajero tiene token activo y estado 1, bloqueado 0
        $tokenCajero = $this->cajero->createToken('cajero-token')->plainTextToken;
        $this->assertEquals(1, (int) $this->cajero->estado);
        $this->assertEquals(0, (int) $this->cajero->bloqueado);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->cajero->usuario_id,
        ]);

        // Bloqueamos/desactivamos el rol Cajero usando bloqueado = 1
        $res = $this->putJson('/api/roles/'.$this->rolCajero->rol_id, [
            'bloqueado' => 1,
        ]);
        $res->assertStatus(200);
        $this->assertEquals(1, (int) $this->rolCajero->fresh()->bloqueado);
        $this->assertEquals(1, (int) $this->rolCajero->fresh()->estado);

        // Verificar que el cajero quedó bloqueado (bloqueado = 1) sin ser eliminado (estado = 1)
        $this->cajero->refresh();
        $this->assertEquals(1, (int) $this->cajero->bloqueado);
        $this->assertEquals(1, (int) $this->cajero->estado);

        // Verificar que sus tokens fueron revocados
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->cajero->usuario_id,
        ]);

        // Intentar autenticarse con ese token debe ser 401
        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$tokenCajero)
            ->getJson('/api/auth/me')
            ->assertStatus(401);

        // Intentar iniciar sesión debe devolver 403 por cuenta bloqueada
        $resLogin = $this->postJson('/api/auth/login', [
            'nombre_usuario' => 'cajero_test',
            'contrasenia_usuario' => 'cajero123',
        ]);
        $resLogin->assertStatus(403);
        $this->assertStringContainsString('inactiva o bloqueada', $resLogin->json('message'));
    }

    public function test_roles_conteo_usuarios_ignora_eliminados_logicos_y_cuenta_activos_bloqueados(): void
    {
        Sanctum::actingAs($this->admin);

        // Crear otro admin activo (total 2 admins activos)
        Usuario::create([
            'id_rol' => $this->rolAdmin->rol_id,
            'nombre_apellido' => 'Admin Dos',
            'nombre_usuario' => 'admin2',
            'contrasenia_usuario' => Hash::make('admin123'),
            'fecha_registro' => now(),
            'estado' => 1,
            'bloqueado' => 0,
        ]);

        // Crear un admin eliminado lógicamente (estado = 0) -> NO debe contarse
        Usuario::create([
            'id_rol' => $this->rolAdmin->rol_id,
            'nombre_apellido' => 'Admin Eliminado',
            'nombre_usuario' => 'admin_del',
            'contrasenia_usuario' => Hash::make('admin123'),
            'fecha_registro' => now(),
            'estado' => 0,
            'bloqueado' => 0,
        ]);

        // Crear un cajero bloqueado (estado = 1, bloqueado = 1) -> debe contarse como usuario asignado
        Usuario::create([
            'id_rol' => $this->rolCajero->rol_id,
            'nombre_apellido' => 'Cajero Bloqueado',
            'nombre_usuario' => 'cajero_bloq',
            'contrasenia_usuario' => Hash::make('cajero123'),
            'fecha_registro' => now(),
            'estado' => 1,
            'bloqueado' => 1,
        ]);

        $res = $this->getJson('/api/roles');
        $res->assertStatus(200);

        $adminRol = collect($res->json())->firstWhere('nombre_rol', 'Administrador');
        $cajeroRol = collect($res->json())->firstWhere('nombre_rol', 'Cajero');

        // Admin debe tener exactamente 2 usuarios asignados (ignora al eliminado con estado = 0)
        $this->assertEquals(2, $adminRol['usuarios_count']);

        // Cajero debe tener 2 usuarios asignados ($this->cajero activo + cajero_bloq)
        $this->assertEquals(2, $cajeroRol['usuarios_count']);
    }

    public function test_desbloquear_usuario_con_rol_bloqueado_es_rechazado(): void
    {
        Sanctum::actingAs($this->admin);

        // Bloqueamos el rol Cajero
        $this->rolCajero->update(['bloqueado' => 1]);
        $this->cajero->update(['bloqueado' => 1]);

        // Intentar desbloquear al cajero con su rol bloqueado debe ser rechazado con 422
        $res = $this->putJson("/api/usuarios/{$this->cajero->usuario_id}", [
            'bloqueado' => 0,
        ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('debe activar su rol o cambiárselo', $res->json('message'));
        $this->assertEquals(1, (int) $this->cajero->fresh()->bloqueado);

        // Si se desbloquea el rol Cajero (bloqueado = 0), ahora sí se puede desbloquear al usuario
        $this->rolCajero->update(['bloqueado' => 0]);

        $res2 = $this->putJson("/api/usuarios/{$this->cajero->usuario_id}", [
            'bloqueado' => 0,
        ]);

        $res2->assertStatus(200);
        $this->assertEquals(0, (int) $this->cajero->fresh()->bloqueado);
    }

    public function test_eliminacion_logica_de_rol_utiliza_estado_cero_y_no_se_lista(): void
    {
        Sanctum::actingAs($this->admin);

        $rolTemporal = Rol::create([
            'nombre_rol' => 'Temporal',
            'descripcion_rol' => 'Rol para probar eliminación',
            'permisos' => ['pos.acceso'],
            'estado' => 1,
            'bloqueado' => 0,
        ]);

        // Eliminar el rol
        $resDelete = $this->deleteJson("/api/roles/{$rolTemporal->rol_id}");
        $resDelete->assertStatus(200);
        $resDelete->assertJsonPath('success', true);

        // En BD sigue existiendo, pero con estado = 0
        $this->assertDatabaseHas('rol', [
            'rol_id' => $rolTemporal->rol_id,
            'estado' => 0,
        ]);

        // En /api/roles no aparece
        $resIndex = $this->getJson('/api/roles');
        $resIndex->assertStatus(200);
        $this->assertNull(collect($resIndex->json())->firstWhere('rol_id', $rolTemporal->rol_id));
    }

    public function test_crear_rol_con_mismo_nombre_que_un_rol_eliminado_logicamente_es_permitido(): void
    {
        Sanctum::actingAs($this->admin);

        // Crear un rol y luego eliminarlo lógicamente
        $rolViejo = Rol::create([
            'nombre_rol' => 'Supervisor',
            'descripcion_rol' => 'Supervisor anterior',
            'permisos' => ['pos.acceso'],
            'estado' => 0,
            'bloqueado' => 0,
        ]);

        // Crear un nuevo rol con el mismo nombre debe ser exitoso
        $res = $this->postJson('/api/roles', [
            'nombre_rol' => 'Supervisor',
            'descripcion_rol' => 'Supervisor nuevo',
            'permisos' => ['pos.acceso', 'ventas.ver'],
        ]);
        $res->assertStatus(201);
        $this->assertDatabaseCount('rol', 4); // rolAdmin, rolCajero, rolViejo, rolNuevo

        // Intentar crearlo de nuevo con el mismo nombre mientras hay uno activo (estado = 1) debe fallar con 422
        $resDuplicado = $this->postJson('/api/roles', [
            'nombre_rol' => 'Supervisor',
            'descripcion_rol' => 'Otro supervisor duplicado',
            'permisos' => ['pos.acceso'],
        ]);
        $resDuplicado->assertStatus(422);
        $resDuplicado->assertJsonValidationErrors('nombre_rol');
    }

    public function test_crear_usuario_con_mismo_nombre_usuario_que_uno_eliminado_logicamente_es_permitido(): void
    {
        Sanctum::actingAs($this->admin);

        // Crear un usuario y darlo de baja lógica
        $usuarioViejo = Usuario::create([
            'id_rol' => $this->rolCajero->rol_id,
            'nombre_apellido' => 'Antiguo Cajero',
            'nombre_usuario' => 'antiguo_cajero',
            'contrasenia_usuario' => Hash::make('claveVieja123'),
            'fecha_registro' => now(),
            'estado' => 0,
            'bloqueado' => 0,
        ]);

        // Crear un nuevo usuario activo con el mismo nombre_usuario debe ser exitoso
        $res = $this->postJson('/api/usuarios', [
            'id_rol' => $this->rolCajero->rol_id,
            'nombre_apellido' => 'Nuevo Cajero',
            'nombre_usuario' => 'antiguo_cajero',
            'contrasenia_usuario' => 'claveNueva123',
            'bloqueado' => 0,
        ]);
        $res->assertStatus(201);

        // Iniciar sesión con la nueva cuenta debe ser exitoso
        $this->app['auth']->forgetGuards();
        $resLogin = $this->postJson('/api/auth/login', [
            'nombre_usuario' => 'antiguo_cajero',
            'contrasenia_usuario' => 'claveNueva123',
        ]);
        $resLogin->assertStatus(200);
        $resLogin->assertJsonPath('success', true);

        // Intentar registrar otro con el mismo nombre_usuario mientras hay uno activo debe fallar con 422
        Sanctum::actingAs($this->admin);
        $resDuplicado = $this->postJson('/api/usuarios', [
            'id_rol' => $this->rolCajero->rol_id,
            'nombre_apellido' => 'Tercer Cajero',
            'nombre_usuario' => 'antiguo_cajero',
            'contrasenia_usuario' => 'claveOtra123',
        ]);
        $resDuplicado->assertStatus(422);
        $resDuplicado->assertJsonValidationErrors('nombre_usuario');
    }

    public function test_usuario_no_admin_no_puede_crear_editar_ni_eliminar_roles(): void
    {
        // 1. Caso Cajero básico (sin permiso usuarios.gestionar)
        Sanctum::actingAs($this->cajero);

        $resPostCajero = $this->postJson('/api/roles', [
            'nombre_rol' => 'Rol Ilegal 1',
            'permisos' => ['pos.acceso'],
        ]);
        $resPostCajero->assertStatus(403);

        $resPutCajero = $this->putJson('/api/roles/'.$this->rolCajero->rol_id, [
            'descripcion_rol' => 'Intento de edicion',
        ]);
        $resPutCajero->assertStatus(403);

        $resDeleteCajero = $this->deleteJson('/api/roles/'.$this->rolCajero->rol_id);
        $resDeleteCajero->assertStatus(403);

        // 2. Caso Usuario con permiso 'usuarios.gestionar' pero rol NO Administrador (ej. RRHH / Supervisor)
        $rolRRHH = Rol::create([
            'nombre_rol' => 'Recursos Humanos',
            'descripcion_rol' => 'Solo gestión de usuarios',
            'permisos' => ['usuarios.gestionar'],
            'estado' => 1,
            'bloqueado' => 0,
        ]);

        $usuarioRRHH = Usuario::create([
            'id_rol' => $rolRRHH->rol_id,
            'nombre_apellido' => 'Gestor Usuarios',
            'nombre_usuario' => 'rrhh_user',
            'contrasenia_usuario' => Hash::make('rrhh123'),
            'fecha_registro' => now(),
            'estado' => 1,
            'bloqueado' => 0,
        ]);

        Sanctum::actingAs($usuarioRRHH);

        // Puede listar roles para asignarlos en formulario de usuario
        $resGetRRHH = $this->getJson('/api/roles');
        $resGetRRHH->assertStatus(200);

        // Pero NO puede crear roles
        $resPostRRHH = $this->postJson('/api/roles', [
            'nombre_rol' => 'Rol Ilegal 2',
            'permisos' => ['*'],
        ]);
        $resPostRRHH->assertStatus(403);

        // Tampoco puede editar roles
        $resPutRRHH = $this->putJson('/api/roles/'.$this->rolCajero->rol_id, [
            'descripcion_rol' => 'Modificacion ilegal',
        ]);
        $resPutRRHH->assertStatus(403);

        // Tampoco puede eliminar roles
        $resDeleteRRHH = $this->deleteJson('/api/roles/'.$this->rolCajero->rol_id);
        $resDeleteRRHH->assertStatus(403);
    }
}
