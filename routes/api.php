<?php

use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\FiscalController;
use App\Http\Controllers\MovimientoInventarioController;
use App\Http\Controllers\ProductoController;
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

    // Módulo de Catálogo e Inventario (Consulta / Lectura para POS, Ventas y Gestión)
    Route::middleware('permission:inventario.gestionar,productos.gestionar,pos.acceso,ventas.ver,productos.ver,categorias.ver')->group(function () {
        Route::get('/productos', [ProductoController::class, 'index']);
        Route::get('/productos/{producto}', [ProductoController::class, 'show']);
        Route::get('/categorias', [CategoriaController::class, 'index']);
        Route::get('/categorias/{categoria}', [CategoriaController::class, 'show']);
    });

    // Módulo de Catálogo e Inventario (Creación, Modificación, Eliminación y Kardex)
    Route::middleware('permission:inventario.gestionar,productos.gestionar')->group(function () {
        Route::post('/productos', [ProductoController::class, 'store']);
        Route::match(['put', 'patch'], '/productos/{producto}', [ProductoController::class, 'update']);
        Route::delete('/productos/{producto}', [ProductoController::class, 'destroy']);
        Route::post('/categorias', [CategoriaController::class, 'store']);
        Route::match(['put', 'patch'], '/categorias/{categoria}', [CategoriaController::class, 'update']);
        Route::delete('/categorias/{categoria}', [CategoriaController::class, 'destroy']);
        Route::apiResource('movimientos-inventario', MovimientoInventarioController::class);
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

    // Módulo de Reportes Fiscales (RF-27 - DGI Cuota Fija)
    Route::middleware('permission:ventas.ver,pos.acceso')->group(function () {
        Route::get('/fiscal/libro-diario', [FiscalController::class, 'libroDiario']);
    });
});
