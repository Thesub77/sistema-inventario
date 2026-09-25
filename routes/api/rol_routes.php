<?php

use App\Http\Controllers\RolController;
use Illuminate\Support\Facades\Route;

Route::apiResource('roles', RolController::class);
