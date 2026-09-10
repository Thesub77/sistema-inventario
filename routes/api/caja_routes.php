<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\CajaOperacionController;
use App\Http\Controllers\CajaMovimientoVentaController;

Route::apiResource('cajas', CajaController::class);
Route::apiResource('caja-operaciones', CajaOperacionController::class);
Route::apiResource('caja-movimientos-venta', CajaMovimientoVentaController::class);