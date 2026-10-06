<?php

use App\Http\Controllers\CuentaPorPagarController;
use App\Http\Controllers\ProveedorController;
use Illuminate\Support\Facades\Route;

// Módulo de Proveedores y Cuentas por Pagar (Blindado para Administrador)
Route::get('cuentas-por-pagar/resumen-kpis', [CuentaPorPagarController::class, 'resumenKPIs']);
Route::post('cuentas-por-pagar/{id}/pagos', [CuentaPorPagarController::class, 'registrarPago']);
Route::get('cuentas-por-pagar/{id}/pagos', [CuentaPorPagarController::class, 'listarPagos']);

Route::apiResource('proveedores', ProveedorController::class);
Route::apiResource('cuentas-por-pagar', CuentaPorPagarController::class);
