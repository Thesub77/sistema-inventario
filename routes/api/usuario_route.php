<?php

use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::apiResource('usuarios', UsuarioController::class);
Route::apiResource('bitacoras', BitacoraController::class)->only(['index', 'show']);
