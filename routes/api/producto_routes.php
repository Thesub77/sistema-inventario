<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\MovimientoInventarioController;

Route::apiResource('productos', ProductoController::class);
Route::apiResource('movimientos-inventario', MovimientoInventarioController::class);
