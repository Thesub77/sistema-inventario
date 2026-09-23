<?php

use App\Http\Controllers\CajaController;
use App\Http\Controllers\CajaMovimientoVentaController;
use App\Http\Controllers\CajaOperacionController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:cajas.gestionar,pos.acceso')->group(function () {
    Route::apiResource('cajas', CajaController::class)->only(['index', 'show']);
    Route::apiResource('caja-operaciones', CajaOperacionController::class);
    Route::apiResource('caja-movimientos-venta', CajaMovimientoVentaController::class);
});
Route::middleware('permission:cajas.gestionar')->group(function () {
    Route::apiResource('cajas', CajaController::class)->only(['store', 'update', 'destroy']);
});
