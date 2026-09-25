<?php

use App\Http\Controllers\MovimientoInventarioController;
use App\Http\Controllers\ProductoController;
use Illuminate\Support\Facades\Route;

Route::apiResource('productos', ProductoController::class);
Route::apiResource('movimientos-inventario', MovimientoInventarioController::class);
