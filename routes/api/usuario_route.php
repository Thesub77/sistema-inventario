<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\BitacoraController;

Route::apiResource('usuarios', UsuarioController::class);
Route::apiResource('bitacoras', BitacoraController::class);

?>