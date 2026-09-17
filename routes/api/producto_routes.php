<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\MovimientoInventarioController;

// RF-05: Rutas para activar/desactivar productos (antes del apiResource para evitar colisión)
Route::get('productos/activos', [ProductoController::class, 'activos']);
Route::patch('productos/{producto}/toggle-estado', [ProductoController::class, 'toggleEstado']);

Route::apiResource('productos', ProductoController::class);
Route::apiResource('movimientos-inventario', MovimientoInventarioController::class);
