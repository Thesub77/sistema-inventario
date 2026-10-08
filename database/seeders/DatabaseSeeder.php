<?php

namespace Database\Seeders;

use App\Models\Caja;
use App\Models\Cuenta_por_pagar;
use App\Models\Empresa;
use App\Models\Proveedor;
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

        // 5. Proveedores Iniciales y Cuentas por Pagar de Prueba
        $provCocaCola = Proveedor::create([
            'nombre_comercial' => 'Coca-Cola FEMSA Nicaragua',
            'contacto_vendedor' => 'Manuel Estrada (Ruta 4)',
            'telefono' => '8845-1290',
            'plazo_credito_dias' => 15,
            'estado' => 1,
        ]);

        $provBimbo = Proveedor::create([
            'nombre_comercial' => 'Distribuidora Bimbo',
            'contacto_vendedor' => 'Roberto Sánchez',
            'telefono' => '8765-4321',
            'plazo_credito_dias' => 7,
            'estado' => 1,
        ]);

        $provCargill = Proveedor::create([
            'nombre_comercial' => 'Cargill / Tip-Top Pollo',
            'contacto_vendedor' => 'Javier Mendoza',
            'telefono' => '8901-2345',
            'plazo_credito_dias' => 7,
            'estado' => 1,
        ]);

        // Factura de prueba pendiente
        Cuenta_por_pagar::create([
            'id_proveedor' => $provCocaCola->proveedor_id,
            'numero_factura' => 'FAC-FEMSA-98214',
            'descripcion' => 'Surtido semanal de bebidas gaseosas y jugos',
            'fecha_emision' => date('Y-m-d', strtotime('-5 days')),
            'fecha_vencimiento' => date('Y-m-d', strtotime('+10 days')),
            'monto_total' => 3850.00,
            'monto_pagado' => 0.00,
            'saldo_pendiente' => 3850.00,
            'estado' => 'Pendiente',
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
