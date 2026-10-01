<?php

namespace Database\Seeders;

use App\Models\Caja;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Rol Administrador
        $rolAdmin = Rol::create([
            'nombre_rol' => 'Administrador',
            'descripcion_rol' => 'Acceso total y configuración del sistema',
            'permisos' => ['*'],
            'estado' => 1,
        ]);

        // 2. Rol Cajero (Permisos dinámicos operativos)
        $rolCajero = Rol::create([
            'nombre_rol' => 'Cajero',
            'descripcion_rol' => 'Operaciones de cobro POS, ventas, cajas y clientes',
            'permisos' => ['pos.acceso', 'ventas.ver', 'ventas.crear', 'cajas.gestionar', 'clientes.gestionar', 'productos.ver', 'categorias.ver'],
            'estado' => 1,
        ]);

        // 3. Usuario Administrador Maestro
        Usuario::create([
            'id_rol' => $rolAdmin->rol_id,
            'nombre_apellido' => 'Diego Quiroz',
            'nombre_usuario' => 'si_dquiroz',
            'contrasenia_usuario' => Hash::make('admin123'),
            'fecha_registro' => date('Y-m-d'),
            'estado' => 1,
        ]);

        // 4. Usuarios Cajeros para pruebas multi-caja
        Usuario::create([
            'id_rol' => $rolCajero->rol_id,
            'nombre_apellido' => 'Juan Pérez',
            'nombre_usuario' => 'cajero1',
            'contrasenia_usuario' => Hash::make('cajero123'),
            'fecha_registro' => date('Y-m-d'),
            'estado' => 1,
        ]);

        Usuario::create([
            'id_rol' => $rolCajero->rol_id,
            'nombre_apellido' => 'María López',
            'nombre_usuario' => 'cajero2',
            'contrasenia_usuario' => Hash::make('cajero123'),
            'fecha_registro' => date('Y-m-d'),
            'estado' => 1,
        ]);

        // 5. Cliente Consumidor Final
        Cliente::create([
            'codigo_cliente' => 'CLI-0000',
            'nombre_apellido_cliente' => 'Consumidor Final',
            'telefono_cliente' => '00000000',
            'estado' => 1,
        ]);

        // 6. Cajas Físicas del Establecimiento
        $empresaId = Empresa::where('estado', 1)->value('empresa_id');

        Caja::create([
            'id_empresa' => $empresaId,
            'descripcion_caja' => 'Caja Principal - Mostrador 1',
            'tipo_apertura' => 'Manual',
            'estado_caja' => 'Cerrada',
            'estado' => 1,
        ]);

        Caja::create([
            'id_empresa' => $empresaId,
            'descripcion_caja' => 'Caja Secundaria - Mostrador 2',
            'tipo_apertura' => 'Manual',
            'estado_caja' => 'Cerrada',
            'estado' => 1,
        ]);
    }
}
