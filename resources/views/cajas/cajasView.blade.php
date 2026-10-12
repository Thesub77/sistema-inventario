{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Módulo de Cajas, Turnos y Movimientos
    Archivo: resources/views/cajas/cajasView.blade.php
    Propósito: Orquestador maestro que divide la experiencia en dos vistas separadas:
               1. Vista Administrador (@include('cajas.cajasAdminView'))
                  - Cajas físicas, balance en vivo, tabla de movimientos por debajo
                    de las tarjetas de balance e historial de turnos y arqueos.
               2. Vista Cajeros y Operadores (@include('cajas.cajasCajeroView'))
                  - Vista operativa especializada y directa: Cajas físicas, balance
                    y movimientos del turno sin barra de subpestañas.
    Integración: Incluido en welcome.blade.php mediante @include('cajas.cajasView')
    =============================================================================
--}}

<div x-show="currentTab === 'caja'" x-cloak class="space-y-6"
    x-init="$watch('currentTab', v => { if (v === 'caja') $nextTick(() => { if (window.lucide) window.lucide.createIcons(); }); })">

    <!-- 1. VISTA DE ADMINISTRADOR -->
    <div x-show="isAdmin">
        @include('cajas.cajasAdminView')
    </div>

    <!-- 2. VISTA DE CAJEROS -->
    <div x-show="!isAdmin">
        @include('cajas.cajasCajeroView')
    </div>

</div>