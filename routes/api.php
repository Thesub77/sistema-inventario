<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RolController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\CajaOperacionController;
use App\Http\Controllers\MovimientoInventarioController;
use App\Http\Controllers\VentaDetalleController;
use App\Http\Controllers\CajaMovimientoVentaController;

Route::apiResource('roles', RolController::class);
Route::apiResource('categorias', CategoriaController::class);
Route::apiResource('cajas', CajaController::class);
Route::apiResource('clientes', ClienteController::class);
Route::apiResource('usuarios', UsuarioController::class);
Route::apiResource('productos', ProductoController::class);
Route::apiResource('ventas', VentaController::class);
Route::apiResource('bitacoras', BitacoraController::class);
Route::apiResource('caja-operaciones', CajaOperacionController::class);
Route::apiResource('movimientos-inventario', MovimientoInventarioController::class);
Route::apiResource('venta-detalles', VentaDetalleController::class);
Route::apiResource('caja-movimientos-venta', CajaMovimientoVentaController::class);
