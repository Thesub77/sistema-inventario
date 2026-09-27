<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpresaController;
use Illuminate\Support\Facades\Route;

// 1. Rutas de autenticación pública (login con rate limiting y endpoints de sesión)
require __DIR__.'/api/auth_routes.php';

// 2. Rutas protegidas bajo autenticación de Sanctum y control de roles/permisos
Route::middleware('auth:sanctum')->group(function () {

    // Panel y Métricas Analíticas del Dashboard
    Route::get('/dashboard/resumen', [DashboardController::class, 'resumen']);

    // Identidad y Datos del Negocio (RF-21)
    Route::get('/empresa', [EmpresaController::class, 'show']);
    Route::match(['put', 'patch'], '/empresa', [EmpresaController::class, 'update'])
        ->middleware('permission:usuarios.gestionar');

    // Módulo de Administración y Auditoría (Solo Administrador o permiso 'usuarios.gestionar')
    Route::middleware('permission:usuarios.gestionar')->group(function () {
        require __DIR__.'/api/usuario_route.php';
        require __DIR__.'/api/rol_routes.php';
    });

    // Módulo de Catálogo e Inventario
    Route::middleware('permission:inventario.gestionar,productos.gestionar')->group(function () {
        require __DIR__.'/api/producto_routes.php';
        require __DIR__.'/api/categoria_routes.php';
    });

    // Módulo de Cajas y Clientes
    Route::middleware('permission:cajas.gestionar,clientes.gestionar,pos.acceso')->group(function () {
        require __DIR__.'/api/caja_routes.php';
        require __DIR__.'/api/cliente_routes.php';
    });

    // Módulo de Ventas y Facturación
    Route::middleware('permission:pos.acceso,ventas.ver,ventas.crear')->group(function () {
        require __DIR__.'/api/venta_routes.php';
    });
});
