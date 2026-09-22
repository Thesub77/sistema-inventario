<?php

namespace Tests\Feature;

use App\Models\Bitacora;
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

        // El cajero no tiene permiso 'usuarios.gestionar'
        $resUsuarios = $this->getJson('/api/usuarios');
        $resUsuarios->assertStatus(403);

        $resRoles = $this->getJson('/api/roles');
        $resRoles->assertStatus(403);
    }

    public function test_administrador_accede_a_todas_las_rutas_protegidas(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/usuarios')->assertStatus(200);
        $this->getJson('/api/roles')->assertStatus(200);
        $this->getJson('/api/ventas')->assertStatus(200);
        $this->getJson('/api/productos')->assertStatus(200);
    }

    public function test_usuario_con_permiso_accede_a_su_modulo(): void
    {
        Sanctum::actingAs($this->cajero);

        // El cajero sí tiene 'ventas.ver' y 'cajas.gestionar'
        $this->getJson('/api/ventas')->assertStatus(200);
        $this->getJson('/api/cajas')->assertStatus(200);
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
}
