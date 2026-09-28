<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Rol;
use App\Models\Usuario;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmpresaTest extends TestCase
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
            'nombre_apellido' => 'Admin Test',
            'nombre_usuario' => 'admin_test',
            'contrasenia_usuario' => Hash::make('admin123'),
            'fecha_registro' => now(),
            'estado' => 1,
        ]);

        $this->cajero = Usuario::create([
            'id_rol' => $this->rolCajero->rol_id,
            'nombre_apellido' => 'Cajero Test',
            'nombre_usuario' => 'cajero_test',
            'contrasenia_usuario' => Hash::make('cajero123'),
            'fecha_registro' => now(),
            'estado' => 1,
        ]);
    }

    public function test_obtener_empresa_requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/empresa');

        $response->assertStatus(401);
    }

    public function test_obtener_empresa_retorna_datos_del_negocio(): void
    {
        Sanctum::actingAs($this->cajero);

        $response = $this->getJson('/api/empresa');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'empresa_id',
                'nombre_comercial',
                'razon_social',
                'numero_ruc',
                'telefono_contacto',
                'correo_contacto',
                'direccion_fisica',
                'mensaje_pie_ticket',
                'moneda_simbolo',
                'estado',
            ]);
    }

    public function test_usuario_sin_permiso_no_puede_actualizar_empresa(): void
    {
        Sanctum::actingAs($this->cajero);

        $response = $this->putJson('/api/empresa', [
            'nombre_comercial' => 'Nuevo Nombre Hack',
        ]);

        $response->assertStatus(403);
    }

    public function test_administrador_puede_actualizar_datos_del_negocio(): void
    {
        Sanctum::actingAs($this->admin);

        $payload = [
            'nombre_comercial' => 'Supermercado Central',
            'razon_social' => 'Super Central S.A.',
            'numero_ruc' => 'J0310000000099',
            'telefono_contacto' => '2255-7799',
            'correo_contacto' => 'admin@central.com',
            'direccion_fisica' => 'Pista Suburbana km 5, Managua',
            'mensaje_pie_ticket' => '¡Vuelva pronto! No se aceptan devoluciones sin ticket.',
            'moneda_simbolo' => 'C$',
        ];

        $response = $this->putJson('/api/empresa', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Datos del negocio actualizados correctamente.',
            ]);

        $this->assertDatabaseHas('empresa', [
            'nombre_comercial' => 'Supermercado Central',
            'numero_ruc' => 'J0310000000099',
            'correo_contacto' => 'admin@central.com',
        ]);
    }

    public function test_caja_se_relaciona_con_empresa(): void
    {
        $empresa = Empresa::first();
        $this->assertNotNull($empresa);

        $caja = Caja::create([
            'id_empresa' => $empresa->empresa_id,
            'descripcion_caja' => 'Caja Express',
            'tipo_apertura' => 'Manual',
            'estado_caja' => 'Cerrada',
            'estado' => 1,
        ]);

        $this->assertInstanceOf(Empresa::class, $caja->empresa);
        $this->assertEquals($empresa->empresa_id, $caja->empresa->empresa_id);
        $this->assertTrue($empresa->cajas->contains($caja));
    }

    public function test_creacion_de_caja_asigna_empresa_activa_por_defecto(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/cajas', [
            'descripcion_caja' => 'Caja Sucursal 1',
            'tipo_apertura' => 'Automatica',
        ]);

        $response->assertStatus(201);
        $cajaId = $response->json('caja.caja_id');

        $caja = Caja::find($cajaId);
        $this->assertNotNull($caja->id_empresa);
        $this->assertEquals(Empresa::where('estado', 1)->value('empresa_id'), $caja->id_empresa);
    }

    public function test_comprobante_incluye_datos_de_empresa(): void
    {
        Sanctum::actingAs($this->cajero);

        $cliente = Cliente::create([
            'nombre_apellido_cliente' => 'Cliente Test',
            'codigo_cliente' => 'CLI-TEST-1',
            'telefono_cliente' => '88889999',
            'estado' => 1,
        ]);

        $venta = Venta::create([
            'id_usuario' => $this->admin->usuario_id,
            'id_cliente' => $cliente->cliente_id,
            'codigo_venta' => 'V-TEST-001',
            'metodo_pago' => 'Efectivo',
            'fecha_hora_venta' => now(),
            'subtotal_venta' => 100.00,
            'descuento_venta' => 0.00,
            'total_venta' => 100.00,
            'estado' => 1,
        ]);

        $response = $this->getJson("/api/ventas/{$venta->venta_id}/comprobante");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'comprobante',
                'empresa' => [
                    'empresa_id',
                    'nombre_comercial',
                    'numero_ruc',
                    'direccion_fisica',
                    'telefono_contacto',
                ],
                'anulada',
            ]);
    }
}
