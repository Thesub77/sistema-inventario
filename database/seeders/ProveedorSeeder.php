<?php

namespace Database\Seeders;

use App\Models\Cuenta_por_pagar;
use App\Models\Proveedor;
use Illuminate\Database\Seeder;

class ProveedorSeeder extends Seeder
{
    public function run(): void
    {
        if (Proveedor::count() > 0) {
            return;
        }

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

        // Factura 1: Pendiente (a vencer en 5 días)
        Cuenta_por_pagar::create([
            'id_proveedor' => $provCocaCola->proveedor_id,
            'numero_factura' => 'FAC-FEMSA-98214',
            'descripcion' => 'Surtido semanal de gaseosas y jugos',
            'fecha_emision' => date('Y-m-d', strtotime('-5 days')),
            'fecha_vencimiento' => date('Y-m-d', strtotime('+5 days')),
            'monto_total' => 3850.00,
            'monto_pagado' => 0.00,
            'saldo_pendiente' => 3850.00,
            'estado' => 'Pendiente',
        ]);

        // Factura 2: Parcial (abonada una parte)
        Cuenta_por_pagar::create([
            'id_proveedor' => $provBimbo->proveedor_id,
            'numero_factura' => 'FAC-BIMBO-44120',
            'descripcion' => 'Pan blanco, pan dulce y repostería',
            'fecha_emision' => date('Y-m-d', strtotime('-8 days')),
            'fecha_vencimiento' => date('Y-m-d', strtotime('+2 days')),
            'monto_total' => 1800.00,
            'monto_pagado' => 800.00,
            'saldo_pendiente' => 1000.00,
            'estado' => 'Parcial',
        ]);

        // Factura 3: Vencida (deuda en mora de hace 3 días)
        Cuenta_por_pagar::create([
            'id_proveedor' => $provCargill->proveedor_id,
            'numero_factura' => 'FAC-CARGILL-77319',
            'descripcion' => 'Embutidos y pollo fresco',
            'fecha_emision' => date('Y-m-d', strtotime('-12 days')),
            'fecha_vencimiento' => date('Y-m-d', strtotime('-3 days')),
            'monto_total' => 2450.00,
            'monto_pagado' => 0.00,
            'saldo_pendiente' => 2450.00,
            'estado' => 'Pendiente',
        ]);
    }
}
