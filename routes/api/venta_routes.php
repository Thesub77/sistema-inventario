<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\VentaDetalleController;

Route::get('ventas/{id}/comprobante', [VentaController::class, 'comprobante']);
Route::apiResource('ventas', VentaController::class);
Route::apiResource('venta-detalles', VentaDetalleController::class);
