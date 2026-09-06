<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Rol;
use App\Models\Categoria;
use App\Models\Caja;
use App\Models\Cliente;
use App\Models\Usuario;
use App\Models\Producto;
use App\Models\Caja_operacion;
use App\Models\Venta;
use App\Models\Venta_detalle;
use App\Models\Caja_movimiento_venta;
use App\Models\Movimiento_inventario;
use App\Models\Bitacora;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles
        $rolAdmin = Rol::create([
            'nombre_rol' => 'Administrador',
            'descripcion_rol' => 'Acceso total al sistema',
            'estado' => 1,
        ]);

        $rolCajero = Rol::create([
            'nombre_rol' => 'Cajero',
            'descripcion_rol' => 'Gestión de cobros y caja',
            'estado' => 1,
        ]);

        $rolVendedor = Rol::create([
            'nombre_rol' => 'Vendedor',
            'descripcion_rol' => 'Atención al cliente y ventas',
            'estado' => 1,
        ]);

        // 2. Categorías
        $catBebidas = Categoria::create([
            'codigo_categoria' => 'CAT-BEB',
            'nombre_categoria' => 'Bebidas',
            'descripcion_categoria' => 'Refrescos, jugos, aguas y gaseosas',
            'estado' => 1,
        ]);

        $catAbarrotes = Categoria::create([
            'codigo_categoria' => 'CAT-ABA',
            'nombre_categoria' => 'Abarrotes',
            'descripcion_categoria' => 'Granos básicos, aceites y alimentos secos',
            'estado' => 1,
        ]);

        $catLacteos = Categoria::create([
            'codigo_categoria' => 'CAT-LAC',
            'nombre_categoria' => 'Lácteos',
            'descripcion_categoria' => 'Leches, quesos, cremas y yogures',
            'estado' => 1,
        ]);

        $catLimpieza = Categoria::create([
            'codigo_categoria' => 'CAT-LIM',
            'nombre_categoria' => 'Limpieza',
            'descripcion_categoria' => 'Artículos de higiene y desinfección para el hogar',
            'estado' => 1,
        ]);

        $catSnacks = Categoria::create([
            'codigo_categoria' => 'CAT-SNA',
            'nombre_categoria' => 'Snacks',
            'descripcion_categoria' => 'Frituras, galletas y golosinas',
            'estado' => 1,
        ]);

        // 3. Cajas
        $caja1 = Caja::create([
            'descripcion_caja' => 'Caja Principal - Mostrador 1',
            'tipo_apertura' => 'Manual',
            'estado_caja' => 'Abierta',
            'estado' => 1,
        ]);

        $caja2 = Caja::create([
            'descripcion_caja' => 'Caja Secundaria - Mostrador 2',
            'tipo_apertura' => 'Manual',
            'estado_caja' => 'Cerrada',
            'estado' => 1,
        ]);

        // 4. Clientes
        $clienteCF = Cliente::create([
            'codigo_cliente' => 'CLI-0000',
            'nombre_apellido_cliente' => 'Consumidor Final',
            'telefono_cliente' => '00000000',
            'estado' => 1,
        ]);

        $cliente1 = Cliente::create([
            'codigo_cliente' => 'CLI-0001',
            'nombre_apellido_cliente' => 'Juan Carlos Pérez Gómez',
            'telefono_cliente' => '78901234',
            'estado' => 1,
        ]);

        $cliente2 = Cliente::create([
            'codigo_cliente' => 'CLI-0002',
            'nombre_apellido_cliente' => 'María Fernanda López Ruiz',
            'telefono_cliente' => '89123456',
            'estado' => 1,
        ]);

        $cliente3 = Cliente::create([
            'codigo_cliente' => 'CLI-0003',
            'nombre_apellido_cliente' => 'Distribuidora Central S.A.',
            'telefono_cliente' => '22446688',
            'estado' => 1,
        ]);

        // 5. Usuarios
        $admin = Usuario::create([
            'id_rol' => $rolAdmin->rol_id,
            'nombre_apellido' => 'Diego Quiroz',
            'nombre_usuario' => 'si_dquiroz',
            'contrasenia_usuario' => Hash::make('admin123'),
            'fecha_registro' => date('Y-m-d'),
            'estado' => 1,
        ]);

        $cajero = Usuario::create([
            'id_rol' => $rolCajero->rol_id,
            'nombre_apellido' => 'Carlos Mendoza',
            'nombre_usuario' => 'carlos_cajero',
            'contrasenia_usuario' => Hash::make('cajero123'),
            'fecha_registro' => date('Y-m-d'),
            'estado' => 1,
        ]);

        $vendedor = Usuario::create([
            'id_rol' => $rolVendedor->rol_id,
            'nombre_apellido' => 'Ana Morales',
            'nombre_usuario' => 'ana_ventas',
            'contrasenia_usuario' => Hash::make('vendedor123'),
            'fecha_registro' => date('Y-m-d'),
            'estado' => 1,
        ]);

        // 6. Productos
        $prod1 = Producto::create([
            'id_categoria' => $catBebidas->categoria_id,
            'codigo_producto' => 'PROD-BEB-01',
            'nombre_producto' => 'Gaseosa Coca Cola 2L',
            'descripcion_producto' => 'Botella retornable de 2 litros',
            'costo_compra' => 30.00,
            'precio_venta' => 45.00,
            'existencia_bodega' => 95,
            'existencia_minima' => 20,
            'estado' => 1,
        ]);

        $prod2 = Producto::create([
            'id_categoria' => $catBebidas->categoria_id,
            'codigo_producto' => 'PROD-BEB-02',
            'nombre_producto' => 'Agua Purificada 1L',
            'descripcion_producto' => 'Botella con tapa rosca',
            'costo_compra' => 10.00,
            'precio_venta' => 18.00,
            'existencia_bodega' => 140,
            'existencia_minima' => 30,
            'estado' => 1,
        ]);

        $prod3 = Producto::create([
            'id_categoria' => $catAbarrotes->categoria_id,
            'codigo_producto' => 'PROD-ABA-01',
            'nombre_producto' => 'Arroz Blanco 1kg',
            'descripcion_producto' => 'Bolsa sellada de grano largo',
            'costo_compra' => 18.00,
            'precio_venta' => 26.00,
            'existencia_bodega' => 78,
            'existencia_minima' => 25,
            'estado' => 1,
        ]);

        $prod4 = Producto::create([
            'id_categoria' => $catLacteos->categoria_id,
            'codigo_producto' => 'PROD-LAC-01',
            'nombre_producto' => 'Leche Entera 1L',
            'descripcion_producto' => 'Tetrapack ultrapasteurizada',
            'costo_compra' => 25.00,
            'precio_venta' => 35.00,
            'existencia_bodega' => 48,
            'existencia_minima' => 15,
            'estado' => 1,
        ]);

        $prod5 = Producto::create([
            'id_categoria' => $catLimpieza->categoria_id,
            'codigo_producto' => 'PROD-LIM-01',
            'nombre_producto' => 'Detergente en Polvo 1kg',
            'descripcion_producto' => 'Fórmula con fragancia floral',
            'costo_compra' => 32.00,
            'precio_venta' => 48.00,
            'existencia_bodega' => 60,
            'existencia_minima' => 10,
            'estado' => 1,
        ]);

        // 7. Apertura de Caja
        $operacionCaja = Caja_operacion::create([
            'id_caja' => $caja1->caja_id,
            'id_usuario' => $cajero->usuario_id,
            'fecha_hora_apertura' => now(),
            'monto_apertura' => 500.00,
            'monto_cierre' => null,
            'fecha_hora_cierre' => null,
            'estado' => 1,
        ]);

        // 8. Ventas
        // Venta 1
        $venta1 = Venta::create([
            'id_usuario' => $cajero->usuario_id,
            'id_cliente' => $clienteCF->cliente_id,
            'codigo_venta' => 'FAC-2026-0001',
            'metodo_pago' => 'Efectivo',
            'fecha_hora_venta' => now(),
            'subtotal_venta' => 90.00,
            'descuento_venta' => 0.00,
            'total_venta' => 90.00,
            'estado' => 1,
        ]);

        // Venta Detalle 1
        Venta_detalle::create([
            'id_venta' => $venta1->venta_id,
            'id_producto' => $prod1->producto_id,
            'cantidad' => 2,
            'subtotal_venta_detalle' => 90.00,
            'precio_unitario' => 45.00,
            'estado' => 1,
        ]);

        // Movimiento de Caja Venta 1
        Caja_movimiento_venta::create([
            'id_caja' => $caja1->caja_id,
            'id_venta' => $venta1->venta_id,
            'monto_movimiento' => 90.00,
            'fecha_hora_movimiento' => now(),
            'estado' => 1,
        ]);

        // Venta 2
        $venta2 = Venta::create([
            'id_usuario' => $vendedor->usuario_id,
            'id_cliente' => $cliente1->cliente_id,
            'codigo_venta' => 'FAC-2026-0002',
            'metodo_pago' => 'Tarjeta',
            'fecha_hora_venta' => now(),
            'subtotal_venta' => 88.00,
            'descuento_venta' => 0.00,
            'total_venta' => 88.00,
            'estado' => 1,
        ]);

        // Venta Detalle 2
        Venta_detalle::create([
            'id_venta' => $venta2->venta_id,
            'id_producto' => $prod2->producto_id,
            'cantidad' => 2,
            'subtotal_venta_detalle' => 36.00,
            'precio_unitario' => 18.00,
            'estado' => 1,
        ]);

        Venta_detalle::create([
            'id_venta' => $venta2->venta_id,
            'id_producto' => $prod3->producto_id,
            'cantidad' => 2,
            'subtotal_venta_detalle' => 52.00,
            'precio_unitario' => 26.00,
            'estado' => 1,
        ]);

        // Movimiento de Caja Venta 2
        Caja_movimiento_venta::create([
            'id_caja' => $caja1->caja_id,
            'id_venta' => $venta2->venta_id,
            'monto_movimiento' => 88.00,
            'fecha_hora_movimiento' => now(),
            'estado' => 1,
        ]);

        // 9. Movimientos de Inventario
        Movimiento_inventario::create([
            'id_producto' => $prod1->producto_id,
            'id_usuario' => $admin->usuario_id,
            'tipo_movimiento' => 'Entrada Inicial',
            'cantidad_movimimiento' => 100,
            'stock_anterior_producto' => 0,
            'stock_resultante_producto' => 100,
            'fecha_movimiento' => now()->subDay(),
            'estado' => 1,
        ]);

        Movimiento_inventario::create([
            'id_producto' => $prod1->producto_id,
            'id_usuario' => $cajero->usuario_id,
            'tipo_movimiento' => 'Salida por Venta',
            'cantidad_movimimiento' => 2,
            'stock_anterior_producto' => 100,
            'stock_resultante_producto' => 98,
            'fecha_movimiento' => now(),
            'estado' => 1,
        ]);

        // 10. Bitácora
        Bitacora::create([
            'id_usuario' => $admin->usuario_id,
            'accion_bitacora' => 'INICIO_SESION',
            'descripcion_bitacora' => 'El usuario administrador inició sesión en el sistema',
            'fecha_hora_bitacora' => now()->subHours(2),
            'estado' => 1,
        ]);

        Bitacora::create([
            'id_usuario' => $cajero->usuario_id,
            'accion_bitacora' => 'APERTURA_CAJA',
            'descripcion_bitacora' => 'Apertura de Caja Principal con monto inicial de C$ 500.00',
            'fecha_hora_bitacora' => now()->subHour(),
            'estado' => 1,
        ]);

        Bitacora::create([
            'id_usuario' => $cajero->usuario_id,
            'accion_bitacora' => 'CREAR_VENTA',
            'descripcion_bitacora' => 'Venta registrada con factura FAC-2026-0001 por C$ 90.00',
            'fecha_hora_bitacora' => now(),
            'estado' => 1,
        ]);
    }
}
