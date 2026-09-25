<?php

use App\Http\Controllers\VentaController;
use App\Http\Controllers\VentaDetalleController;
use Illuminate\Support\Facades\Route;

Route::get('ventas/{id}/comprobante', [VentaController::class, 'comprobante']);
Route::apiResource('ventas', VentaController::class);
Route::apiResource('venta-detalles', VentaDetalleController::class);
