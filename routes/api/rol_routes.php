<?php

use App\Http\Controllers\RolController;
use Illuminate\Support\Facades\Route;

Route::get('roles', [RolController::class, 'index']);
Route::get('roles/{rol}', [RolController::class, 'show']);

Route::middleware('permission:Administrador')->group(function () {
    Route::post('roles', [RolController::class, 'store']);
    Route::match(['put', 'patch'], 'roles/{rol}', [RolController::class, 'update']);
    Route::delete('roles/{rol}', [RolController::class, 'destroy']);
});
