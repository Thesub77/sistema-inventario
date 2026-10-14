{{--
    =============================================================================
    FACTURASTOCK PRO - DASHBOARD ANALÍTICO & OPERATIVO
    Archivo: resources/views/sistema/dashboardView.blade.php
    Propósito: Panel de control integral con métricas en tiempo real, alertas de stock bajo,
               cuadre de turno desglosado (efectivo/transferencia), monitoreo fiscal Ley 822
               y ranking interactivo Top 5 de rotación y rentabilidad.
    =============================================================================
--}}

<div x-show="currentTab === 'dashboard'" x-cloak
    x-init="$watch('currentTab', v => { if (v === 'dashboard') { if (fetchDashboardData) fetchDashboardData(); $nextTick(() => { if (window.lucide) window.lucide.createIcons(); }); } })"
    class="space-y-6">

    <!-- 1. ENCABEZADO DE BIENVENIDA & ESTADO OPERATIVO -->
    <div class="glass-panel p-6 sm:p-7 rounded-3xl relative overflow-hidden border border-slate-200/90 dark:border-slate-800/90 shadow-sm bg-gradient-to-br from-white/90 via-slate-50/80 to-indigo-50/30 dark:from-dark-900/90 dark:via-dark-900/80 dark:to-dark-950/90">
        
        <!-- Orbes de luz decorativa para profundidad en Modo Claro y Oscuro -->
        <div class="absolute -right-20 -top-20 w-72 h-72 bg-gradient-to-tr from-brand-500/15 to-indigo-500/15 dark:from-brand-500/10 dark:to-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-20 -bottom-20 w-72 h-72 bg-gradient-to-tr from-emerald-500/15 to-teal-500/15 dark:from-emerald-500/10 dark:to-teal-500/10 rounded-full blur-3xl pointer-events-none"></div>
        
        <!-- Patrón sutil de puntos de fondo -->
        <div class="absolute inset-0 bg-[radial-gradient(#6366f1_1px,transparent_1px)] dark:bg-[radial-gradient(#818cf8_1px,transparent_1px)] [background-size:24px_24px] opacity-[0.06] dark:opacity-[0.04] pointer-events-none"></div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            
            <!-- Lado Izquierdo: Saludo dinámico, Título y Descripción -->
            <div class="space-y-2">
                <!-- Badge de Estado de Sesión y Saludo -->
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-100/80 dark:bg-dark-950/70 border border-slate-200/80 dark:border-slate-800 text-xs font-semibold backdrop-blur-md">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span class="text-slate-700 dark:text-slate-300 font-bold" x-text="greetingMessage + ', ' + (currentUser.nombre_apellido || 'Administrador')"></span>
                    <span class="text-slate-300 dark:text-slate-700">•</span>
                    <span class="text-[10px] uppercase tracking-wider font-mono font-bold text-brand-600 dark:text-brand-400" x-text="currentUser.rol || 'Administrador'"></span>
                </div>

                <!-- Título Principal Impactante -->
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-display font-black text-slate-900 dark:text-white tracking-tight leading-tight">
                    Panel de Control <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-600 via-indigo-600 to-violet-600 dark:from-brand-400 dark:via-indigo-300 dark:to-teal-300">Inteligente</span>
                </h2>

                <!-- Subtítulo -->
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-xl font-normal leading-relaxed">
                    Monitoreo en tiempo real del cuadre de turnos, rotación de productos y alertas del inventario.
                </p>
            </div>

            <!-- Lado Derecho: Cápsulas Informativas en Vivo (Fecha y Estado Operativo) -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 self-stretch lg:self-center">
                
                <!-- Cápsula 1: Fecha del Sistema & Estado de Conexión -->
                <div class="p-3.5 sm:p-4 rounded-2xl bg-white/80 dark:bg-dark-900/70 border border-slate-200/90 dark:border-slate-800/80 shadow-2xs backdrop-blur-md flex items-center gap-3.5 hover:border-brand-500/30 transition-all">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-500/10 to-indigo-500/20 text-brand-600 dark:text-brand-400 flex items-center justify-center border border-brand-500/20 flex-shrink-0">
                        <i data-lucide="calendar" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 block">
                            Fecha del Sistema
                        </span>
                        <div class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white capitalize truncate"
                            x-text="new Date().toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' })"></div>
                        <div class="flex items-center gap-1.5 text-[10px] text-emerald-600 dark:text-emerald-400 font-medium mt-0.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Sistema en Línea</span>
                        </div>
                    </div>
</div>
            </div>
        </div>
    </div>

    <!-- 2. CONSULTA DE VENTAS DEL TURNO / DEL DÍA (DESGLOSE EFECTIVO Y TRANSFERENCIA) -->
    <div class="glass-panel p-5 sm:p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800/80 pb-3.5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center border border-emerald-500/20 flex-shrink-0">
                    <i data-lucide="calendar-check" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-white">Ventas del Turno</h3>
                        
                        <!-- Indicador de Turno Activo -->
                        <template x-if="ventasTurnoStats.turnoActivo">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span x-text="ventasTurnoStats.cajaNombre + ' • Turno Abierto'"></span>
                            </span>
                        </template>
                        <template x-if="!ventasTurnoStats.turnoActivo">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                <span>Turno General (Día Actual)</span>
                            </span>
                        </template>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5" x-text="ventasTurnoStats.turnoActivo && ventasTurnoStats.cajeroNombre ? ((ventasTurnoStats.fechaApertura ? 'Apertura: ' + formatDate(ventasTurnoStats.fechaApertura) : '')) : 'Desglose en tiempo real por método de pago'"></p>
                </div>
            </div>

            <div class="flex items-center gap-2 self-start md:self-center">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                    <span x-text="ventasTurnoStats.totalTickets + ' tickets registrados'"></span>
                </span>
            </div>
        </div>

        <!-- Banner Informativo del Turno Activo: Fondo de Apertura y Efectivo Esperado -->
        <template x-if="ventasTurnoStats.turnoActivo">
            <div class="p-3.5 rounded-2xl bg-slate-50/90 dark:bg-dark-900/90 border border-slate-200 dark:border-emerald-500/20 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2.5 text-slate-600 dark:text-slate-300">
                    <div class="w-6 h-6 rounded-lg bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="info" class="w-3.5 h-3.5"></i>
                    </div>
                    <span>Fondo Inicial en Caja: <strong class="text-slate-900 dark:text-white font-mono text-sm ml-1" x-text="formatCurrency(ventasTurnoStats.montoApertura)"></strong></span>
                </div>
                <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300 bg-white/80 dark:bg-dark-950/60 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800">
                    <span class="text-slate-500 dark:text-slate-400">Efectivo Esperado (Fondo + Ventas):</span>
                    <strong class="text-emerald-600 dark:text-emerald-400 font-mono text-sm" x-text="formatCurrency(ventasTurnoStats.efectivoEsperado)"></strong>
                </div>
            </div>
        </template>

        <!-- Métricas del Turno y KPIs: Total + Efectivo + Transferencia + Tarjeta -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- Turno Total -->
            <div class="p-4 rounded-2xl bg-white dark:bg-dark-900/80 border border-slate-200 dark:border-slate-800/90 shadow-2xs">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Facturado</span>
                    <div class="w-8 h-8 rounded-lg bg-brand-500/10 text-brand-600 dark:text-brand-400 flex items-center justify-center">
                        <i data-lucide="badge-dollar-sign" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="text-2xl font-display font-black text-slate-900 dark:text-white" x-text="formatCurrency(ventasTurnoStats.total)"></div>
                <div class="mt-2 text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between">
                    <span>Transacciones:</span>
                    <span class="font-bold text-slate-700 dark:text-slate-200" x-text="ventasTurnoStats.totalTickets"></span>
                </div>
            </div>

            <!-- Desglose Efectivo -->
            <div class="p-4 rounded-2xl bg-white dark:bg-dark-900/80 border border-emerald-500/20 shadow-2xs">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Efectivo</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <i data-lucide="banknote" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="text-2xl font-display font-black text-emerald-600 dark:text-emerald-400" x-text="formatCurrency(ventasTurnoStats.efectivo)"></div>
                <div class="mt-2 space-y-1.5">
                    <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400">
                        <span x-text="ventasTurnoStats.countEfectivo + ' facturas'"></span>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400" x-text="ventasTurnoStats.pctEfectivo + '%'"></span>
                    </div>
                    <div class="w-full h-1.5 bg-slate-100 dark:bg-dark-950 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-500 rounded-full transition-all duration-500" :style="`width: ${ventasTurnoStats.pctEfectivo}%`"></div>
                    </div>
                </div>
            </div>

            <!-- Desglose Transferencia -->
            <div class="p-4 rounded-2xl bg-white dark:bg-dark-900/80 border border-sky-500/20 shadow-2xs">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-sky-700 dark:text-sky-400">Transferencia</span>
                    <div class="w-8 h-8 rounded-lg bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                        <i data-lucide="arrow-left-right" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="text-2xl font-display font-black text-sky-600 dark:text-sky-400" x-text="formatCurrency(ventasTurnoStats.transferencia)"></div>
                <div class="mt-2 space-y-1.5">
                    <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400">
                        <span x-text="ventasTurnoStats.countTransferencia + ' transf.'"></span>
                        <span class="font-bold text-sky-600 dark:text-sky-400" x-text="ventasTurnoStats.pctTransferencia + '%'"></span>
                    </div>
                    <div class="w-full h-1.5 bg-slate-100 dark:bg-dark-950 rounded-full overflow-hidden">
                        <div class="h-full bg-sky-500 rounded-full transition-all duration-500" :style="`width: ${ventasTurnoStats.pctTransferencia}%`"></div>
                    </div>
                </div>
            </div>

            <!-- Desglose Tarjeta / Otros -->
            <div class="p-4 rounded-2xl bg-white dark:bg-dark-900/80 border border-indigo-500/20 shadow-2xs">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-400">Tarjeta / Otros</span>
                    <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <i data-lucide="credit-card" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="text-2xl font-display font-black text-indigo-600 dark:text-indigo-400" x-text="formatCurrency(ventasTurnoStats.tarjeta)"></div>
                <div class="mt-2 space-y-1.5">
                    <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400">
                        <span x-text="ventasTurnoStats.countTarjeta + ' tarjetas'"></span>
                        <span class="font-bold text-indigo-600 dark:text-indigo-400" x-text="ventasTurnoStats.pctTarjeta + '%'"></span>
                    </div>
                    <div class="w-full h-1.5 bg-slate-100 dark:bg-dark-950 rounded-full overflow-hidden">
                        <div class="h-full bg-indigo-500 rounded-full transition-all duration-500" :style="`width: ${ventasTurnoStats.pctTarjeta}%`"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. SECCIÓN ANALÍTICA: TOP 5 PRODUCTOS (ROTACIÓN Y RENTABILIDAD) -->
    <div class="glass-panel p-5 sm:p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
        <!-- Encabezado con Selector Toggle -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800/80 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl flex items-center justify-center border shadow-xs flex-shrink-0 transition-colors"
                    :class="topProductosFiltro === 'utilidad' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20' : 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20'">
                    <i x-show="topProductosFiltro === 'unidades'" data-lucide="flame" class="w-5 h-5"></i>
                    <i x-show="topProductosFiltro === 'utilidad'" data-lucide="trending-up" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-white"
                        x-text="topProductosFiltro === 'utilidad' ? 'Top 5 Productos de Mayor Rentabilidad' : 'Top 5 Productos Más Vendidos'"></h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"
                        x-text="topProductosFiltro === 'utilidad' ? 'Ranking por mayor ganancia líquida' : 'Ranking por volumen de unidades vendidas'"></p>
                </div>
            </div>

            <!-- Selector / Toggle: Más Vendidos vs Mayor Ganancia -->
            <div class="inline-flex p-1 rounded-xl bg-slate-100 dark:bg-dark-900 border border-slate-200 dark:border-slate-800 text-xs shadow-xs self-start sm:self-center">
                <button type="button" @click="setTopProductosFiltro('unidades')"
                    :class="topProductosFiltro === 'unidades' ? 'bg-brand-600 text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                    class="py-1.5 px-3 rounded-lg transition-all cursor-pointer text-center text-xs">
                    Más Vendidos
                </button>
                <button type="button" @click="setTopProductosFiltro('utilidad')"
                    :class="topProductosFiltro === 'utilidad' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                    class="py-1.5 px-3 rounded-lg transition-all cursor-pointer text-center text-xs">
                    Mayor Ganancia
                </button>
            </div>
        </div>

        <!-- Grid Interno: Gráfico Doughnut a la Izquierda y Lista de Ranking a la Derecha -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center pt-1">
            
            <!-- Columna Izquierda: Gráfico Doughnut -->
            <div class="lg:col-span-5 xl:col-span-5 flex flex-col items-center justify-center">
                <div class="relative w-full h-56 sm:h-64 max-w-sm mx-auto">
                    <canvas id="chartTopProductos"></canvas>
                </div>
            </div>

            <!-- Columna Derecha: Lista Visual del Ranking Top 5 -->
            <div class="lg:col-span-7 xl:col-span-7 space-y-2.5">
                <!-- 1. Vista por Unidades Vendidas -->
                <template x-if="topProductosFiltro === 'unidades'">
                    <div class="space-y-2.5">
                        <template x-for="p in topProductosVendidos" :key="'vol-' + p.producto_id">
                            <div class="p-2.5 sm:p-3 rounded-2xl bg-slate-50/80 dark:bg-dark-900/60 border border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3 text-xs hover:border-brand-500/30 hover:shadow-xs transition-all">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <!-- Medalla de Posición -->
                                    <span class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-[11px] flex-shrink-0 shadow-xs"
                                        :class="{
                                            'bg-amber-400 text-slate-900': p.posicion === 1,
                                            'bg-slate-300 text-slate-900': p.posicion === 2,
                                            'bg-amber-600 text-white': p.posicion === 3,
                                            'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300': p.posicion > 3
                                        }"
                                        x-text="p.posicion"></span>
                                    <div class="truncate">
                                        <p class="font-bold text-slate-900 dark:text-slate-100 truncate text-xs sm:text-sm" x-text="p.nombre || p.nombre_producto"></p>
                                        <p class="text-[10px] text-slate-500 dark:text-slate-400 font-mono mt-0.5" x-text="p.codigo || p.codigo_producto"></p>
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <span class="font-extrabold text-emerald-600 dark:text-emerald-400 text-xs sm:text-sm" x-text="p.cantidadVendida + ' uds.'"></span>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-mono mt-0.5" x-text="formatCurrency(p.totalRecaudado)"></p>
                                </div>
                            </div>
                        </template>

                        <div x-show="topProductosVendidos.length === 0" class="text-center py-8 text-xs text-slate-500">
                            <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-1 text-slate-400 dark:text-slate-600"></i>
                            Aún no se registran ventas para generar el ranking
                        </div>
                    </div>
                </template>

                <!-- 2. Vista por Mayor Utilidad / Ganancia -->
                <template x-if="topProductosFiltro === 'utilidad'">
                    <div class="space-y-2.5">
                        <template x-for="p in topRentabilidad" :key="'rent-' + p.producto_id">
                            <div class="p-2.5 sm:p-3 rounded-2xl bg-slate-50/80 dark:bg-dark-900/60 border border-slate-200 dark:border-emerald-500/20 flex items-center justify-between gap-3 text-xs hover:border-emerald-500/40 hover:shadow-xs transition-all">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <!-- Medalla de Posición -->
                                    <span class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-[11px] flex-shrink-0 shadow-xs"
                                        :class="{
                                            'bg-emerald-400 text-slate-900 font-black': p.posicion === 1,
                                            'bg-teal-300 text-slate-900 font-bold': p.posicion === 2,
                                            'bg-cyan-600 text-white font-bold': p.posicion === 3,
                                            'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300': p.posicion > 3
                                        }"
                                        x-text="p.posicion"></span>
                                    <div class="truncate">
                                        <p class="font-bold text-slate-900 dark:text-slate-100 truncate text-xs sm:text-sm" x-text="p.nombre || p.nombre_producto"></p>
                                        <div class="flex items-center gap-1.5 text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">
                                            <span class="font-mono" x-text="p.codigo || p.codigo_producto"></span>
                                            <span>•</span>
                                            <span class="text-emerald-600 dark:text-emerald-400 font-semibold" x-text="(p.margenPct || p.margen_pct) + '% margen'"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <span class="font-extrabold text-emerald-600 dark:text-emerald-400 font-mono text-xs sm:text-sm" x-text="'+ ' + formatCurrency(p.utilidadTotal || p.utilidad_total)"></span>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5" x-text="(p.cantidadVendida || 0) + ' uds. vendidas'"></p>
                                </div>
                            </div>
                        </template>

                        <div x-show="topRentabilidad.length === 0" class="text-center py-8 text-xs text-slate-500">
                            <i data-lucide="trending-up" class="w-8 h-8 mx-auto mb-1 text-slate-400 dark:text-slate-600"></i>
                            Aún no se registran utilidades para generar el ranking
                        </div>
                    </div>
                </template>
            </div>

        </div>
    </div>

    <!-- 4. SECCIÓN OPERATIVA: ALERTAS DE STOCK BAJO & PÉRDIDAS POR MERMAS -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">

        <!-- PANEL 1: ALERTAS DE STOCK BAJO Y CRÍTICO -->
        <div class="glass-panel p-5 sm:p-6 rounded-3xl border border-slate-200 dark:border-slate-800 space-y-4 flex flex-col justify-between shadow-sm">
            <div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800/80 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-2xl bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center border border-rose-500/20 flex-shrink-0 shadow-xs">
                            <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-white">Alertas de Stock Bajo</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Productos que requieren reposición</p>
                        </div>
                    </div>

                    <!-- Filtro de Alertas -->
                    <div class="inline-flex p-1 rounded-xl bg-slate-100 dark:bg-dark-900 border border-slate-200 dark:border-slate-800 text-[11px] self-start sm:self-center shadow-xs">
                        <button type="button" @click="stockAlertFiltro = 'todos'"
                            :class="stockAlertFiltro === 'todos' ? 'bg-brand-600 text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                            class="px-2.5 py-1 rounded-lg transition-all cursor-pointer">
                            Todos (<span x-text="stockAlerts.totalAlertas"></span>)
                        </button>
                        <button type="button" @click="stockAlertFiltro = 'critico'"
                            :class="stockAlertFiltro === 'critico' ? 'bg-rose-600 text-white font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                            class="px-2.5 py-1 rounded-lg transition-all cursor-pointer">
                            Agotados (<span x-text="stockAlerts.criticos.length"></span>)
                        </button>
                        <button type="button" @click="stockAlertFiltro = 'urgente'"
                            :class="stockAlertFiltro === 'urgente' ? 'bg-amber-600 text-white font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                            class="px-2.5 py-1 rounded-lg transition-all cursor-pointer">
                            Críticos (<span x-text="stockAlerts.urgentes.length"></span>)
                        </button>
                    </div>
                </div>

                <!-- Lista de Productos en Alerta -->
                <div class="space-y-3 max-h-[380px] overflow-y-auto pr-1 mt-4">
                    <template x-for="p in filteredStockAlerts" :key="p.producto_id">
                        <div class="p-3.5 rounded-2xl bg-slate-50/80 dark:bg-dark-900/70 border border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                        :class="{
                                            'bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/30 animate-pulse': p.stockActual <= 0,
                                            'bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30': p.stockActual > 0 && p.stockActual <= Math.ceil(p.stockMinimo / 2),
                                            'bg-yellow-500/20 text-yellow-700 dark:text-yellow-300 border border-yellow-500/30': p.stockActual > Math.ceil(p.stockMinimo / 2)
                                        }"
                                        x-text="p.stockActual <= 0 ? 'Agotado' : (p.stockActual <= Math.ceil(p.stockMinimo / 2) ? 'Crítico' : 'Bajo')"></span>
                                    <span class="text-xs text-slate-500 dark:text-slate-400 font-mono" x-text="p.codigo_producto"></span>
                                </div>
                                <h5 class="text-sm font-bold text-slate-900 dark:text-white mt-1 truncate" x-text="p.nombre_producto"></h5>
                                
                                <div class="mt-2 space-y-1">
                                    <div class="flex justify-between text-[11px] text-slate-500 dark:text-slate-400">
                                        <span>Existencia: <strong class="text-slate-900 dark:text-white" x-text="p.stockActual + ' uds.'"></strong></span>
                                        <span>Mínimo: <strong class="text-slate-700 dark:text-slate-300" x-text="p.stockMinimo + ' uds.'"></strong></span>
                                    </div>
                                    <div class="w-full h-1.5 bg-slate-200 dark:bg-dark-950 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-300"
                                            :class="p.stockActual <= 0 ? 'bg-rose-600' : (p.stockActual <= Math.ceil(p.stockMinimo / 2) ? 'bg-amber-500' : 'bg-yellow-400')"
                                            :style="`width: ${Math.min(100, (p.stockActual / (p.stockMinimo || 1)) * 100)}%`"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 self-end sm:self-center flex-shrink-0">
                                <button type="button" @click="openStockModal(p)"
                                    class="px-3.5 py-1.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-semibold text-xs transition-all flex items-center gap-1.5 cursor-pointer shadow-md shadow-brand-600/30 border border-brand-500/40 active:scale-95 hover:scale-102">
                                    <i data-lucide="plus" class="w-3.5 h-3.5 text-white"></i>
                                    <span>Ajustar Stock</span>
                                </button>
                            </div>
                        </div>
                    </template>

                    <div x-show="filteredStockAlerts.length === 0"
                        class="p-6 text-center rounded-2xl bg-emerald-50/60 dark:bg-emerald-500/5 border border-emerald-200 dark:border-emerald-500/20 text-emerald-800 dark:text-emerald-400 space-y-2">
                        <i data-lucide="check-circle-2" class="w-8 h-8 mx-auto text-emerald-600 dark:text-emerald-400"></i>
                        <p class="text-sm font-bold">¡Inventario en estado óptimo!</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">No hay productos que requieran reposición en esta categoría.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- PANEL 2: ADVERTENCIA DE PÉRDIDAS POR MERMAS DEL MES -->
        <div class="glass-panel p-5 sm:p-6 rounded-3xl border border-slate-200 dark:border-slate-800 space-y-4 flex flex-col justify-between shadow-sm">
            <div>
                <!-- Encabezado con Estado -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800/80 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-2xl bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center border border-rose-500/20 shadow-xs flex-shrink-0">
                            <i data-lucide="trending-down" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-white">Pérdidas por Mermas</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Costo por deterioro o descarte</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border transition-all self-start sm:self-center"
                        :class="perdidasMermasMes > 0 
                            ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800/60' 
                            : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/60'">
                        <span class="w-2 h-2 rounded-full" :class="perdidasMermasMes > 0 ? 'bg-rose-500 animate-pulse' : 'bg-emerald-500'"></span>
                        <span x-text="perdidasMermasMes > 0 ? 'Alerta Activa' : 'Sin Pérdidas'"></span>
                    </span>
                </div>

                <!-- Tarjeta Resumen Financiero Rápido (KPI de Mermas) -->
                <div class="mt-4 p-3.5 rounded-2xl bg-rose-50/80 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900/60 flex items-center justify-between gap-3">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-rose-800 dark:text-rose-300">Costo Total Descartado</span>
                        <div class="text-2xl sm:text-3xl font-display font-black text-rose-600 dark:text-rose-400 font-mono tracking-tight mt-0.5"
                            x-text="formatCurrency(perdidasMermasMes)"></div>
                    </div>
                    <div class="text-right flex flex-col items-end">
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 dark:bg-rose-900/50 text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-800 font-mono"
                            x-text="mermasDelMes.length + ' registro(s)'"></span>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1"
                            x-text="perdidasMermasMes > 0 ? 'Pérdida en curso' : 'Inventario íntegro'"></p>
                    </div>
                </div>

                <!-- Desglose de Salidas por Merma -->
                <div class="mt-4 space-y-2.5 max-h-[260px] overflow-y-auto pr-1">
                    <template x-for="m in mermasDelMes" :key="m.movimiento_inventario_id || m.movimiento_id || (m.id_movimiento + '-' + m.created_at)">
                        <div class="p-3 rounded-2xl bg-slate-50/80 dark:bg-dark-900/70 border border-slate-200 dark:border-slate-800 hover:border-rose-300 dark:hover:border-rose-500/40 transition-all flex items-center justify-between gap-3 shadow-xs">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider border bg-sky-50 dark:bg-sky-500/20 text-sky-800 dark:text-white border-sky-200 dark:border-sky-500/30"
                                        x-text="m.tipo_merma || 'Merma'"></span>
                                    <span class="text-[11px] text-slate-500 dark:text-slate-400 font-mono" x-text="m.producto?.codigo_producto || m.codigo_producto || ''"></span>
                                </div>
                                <h5 class="text-xs font-bold text-slate-900 dark:text-white mt-1 truncate" x-text="m.producto?.nombre_producto || m.nombre_producto || 'Producto #' + m.id_producto"></h5>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    <span>Descarga: <strong class="text-slate-800 dark:text-slate-200 font-semibold" x-text="(m.cantidad_movimiento || m.cantidad_movimimiento || m.cantidad || 0) + ' uds.'"></strong></span>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="font-display font-black text-sm text-rose-600 dark:text-rose-400 font-mono"
                                    x-text="'- ' + formatCurrency(m.costo_total_perdida)"></span>
                            </div>
                        </div>
                    </template>

                    <div x-show="mermasDelMes.length === 0 && (!perdidasMermasMes || perdidasMermasMes <= 0)"
                        class="p-6 text-center rounded-2xl bg-emerald-50/60 dark:bg-emerald-500/5 border border-emerald-200 dark:border-emerald-500/20 text-emerald-800 dark:text-emerald-400 space-y-2">
                        <i data-lucide="shield-check" class="w-8 h-8 mx-auto text-emerald-600 dark:text-emerald-400"></i>
                        <p class="text-sm font-bold">¡Sin pérdidas por mermas este mes!</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">No se registran salidas por deterioro o descarte.</p>
                    </div>

                    <div x-show="mermasDelMes.length === 0 && perdidasMermasMes > 0"
                        class="p-6 text-center rounded-2xl bg-rose-50/50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900/40 text-slate-600 dark:text-slate-300 space-y-2">
                        <i data-lucide="alert-triangle" class="w-8 h-8 mx-auto text-rose-500"></i>
                        <p class="text-xs font-semibold text-rose-700 dark:text-rose-400">Pérdidas acumuladas registradas en el sistema este mes</p>
                        <button type="button" @click="currentTab = 'productos'; openKardexModal(null)" class="text-xs text-brand-600 dark:text-brand-400 hover:underline font-semibold cursor-pointer">
                            Consultar historial de movimientos de stock &rarr;
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>