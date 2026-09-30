<?php

namespace Database\Seeders;

use App\Models\Caja;
use App\Models\Caja_operacion;
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

        // 2. Usuario Administrador Maestro
        $admin = Usuario::create([
            'id_rol' => $rolAdmin->rol_id,
            'nombre_apellido' => 'Diego Quiroz',
            'nombre_usuario' => 'si_dquiroz',
            'contrasenia_usuario' => Hash::make('admin123'),
            'fecha_registro' => date('Y-m-d'),
            'estado' => 1,
        ]);

        // 3. Cliente Consumidor Final
        Cliente::create([
            'codigo_cliente' => 'CLI-0000',
            'nombre_apellido_cliente' => 'Consumidor Final',
            'telefono_cliente' => '00000000',
            'estado' => 1,
        ]);

        // 4. Caja Predeterminada (Abierta con Turno Activo)
        $empresaId = Empresa::where('estado', 1)->value('empresa_id');

        $caja = Caja::create([
            'id_empresa' => $empresaId,
            'descripcion_caja' => 'Caja Principal - Mostrador 1',
            'tipo_apertura' => 'Manual',
            'estado_caja' => 'Abierta',
            'estado' => 1,
        ]);

        Caja_operacion::create([
            'id_caja' => $caja->caja_id,
            'id_usuario' => $admin->usuario_id,
            'fecha_hora_apertura' => now(),
            'monto_apertura' => 500.00,
            'monto_cierre' => null,
            'fecha_hora_cierre' => null,
            'estado' => 1,
        ]);
    }
}
