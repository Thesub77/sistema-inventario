{{--
    =============================================================================
    FACTURASTOCK PRO - DASHBOARD ANALÍTICO & OPERATIVO (RF-13, RF-26, RF-30, RF-31, RF-32, RF-33)
    Archivo: resources/views/sistema/dashboardView.blade.php
    Propósito: Panel de control integral con métricas en tiempo real, alertas de stock bajo,
               cuadre de turno desglosado (efectivo/transferencia), gráficos de ventas interactivos,
               ranking de mayor rotación y detección de productos de baja rotación.
    =============================================================================
--}}

<div x-show="currentTab === 'dashboard'" x-cloak class="space-y-6">

    <!-- 1. ENCABEZADO DE BIENVENIDA & ACCIONES RÁPIDAS (RF-30) -->
    <div class="glass-panel p-5 sm:p-6 rounded-3xl relative overflow-hidden border border-slate-800">
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-brand-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-xs font-semibold text-brand-400 uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span x-text="greetingMessage + ', ' + (currentUser.nombre_apellido || 'Administrador')"></span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-display font-extrabold text-white tracking-tight">
                    Panel de Control <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-400 to-indigo-300">Inteligente</span>
                </h2>
                <p class="text-xs sm:text-sm text-slate-400">
                    Visión global del negocio: ventas del turno, rotación de inventario y estado operativo.
                </p>
            </div>
        </div>
    </div>

    <!-- 2. RF-26: CONSULTA DE VENTAS DEL TURNO / DEL DÍA (DESGLOSE EFECTIVO Y TRANSFERENCIA) -->
    <div class="glass-panel p-5 sm:p-6 rounded-3xl border border-slate-800 relative overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-5 border-b border-slate-800/80 pb-4">
            <div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center border border-emerald-500/20">
                        <i data-lucide="calendar-check" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-display font-bold text-base sm:text-lg text-white">Ventas del Turno</h3>
                            
                            <!-- Indicador de Turno Activo -->
                            <template x-if="ventasTurnoStats.turnoActivo">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                    <span x-text="ventasTurnoStats.cajaNombre + ' • Turno Abierto'"></span>
                                </span>
                            </template>
                            <template x-if="!ventasTurnoStats.turnoActivo">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-800 text-slate-400 border border-slate-700">
                                    <span class="w-2 h-2 rounded-full bg-slate-500"></span>
                                    <span>Turno General (Día Actual)</span>
                                </span>
                            </template>
                        </div>
                        <p class="text-xs text-slate-400 mt-1" x-text="ventasTurnoStats.turnoActivo && ventasTurnoStats.cajeroNombre ? ('Cajero en turno: ' + ventasTurnoStats.cajeroNombre + (ventasTurnoStats.fechaApertura ? ' • Apertura: ' + formatDate(ventasTurnoStats.fechaApertura) : '')) : 'Desglose en tiempo real por método de pago para cuadre de caja'"></p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 self-start md:self-center">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                    <span x-text="ventasTurnoStats.totalTickets + ' tickets registrados'"></span>
                </span>
            </div>
        </div>

        <!-- Banner Informativo del Turno Activo: Fondo de Apertura y Efectivo Esperado -->
        <template x-if="ventasTurnoStats.turnoActivo">
            <div class="mb-5 p-3.5 rounded-2xl bg-dark-900/90 border border-emerald-500/20 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2.5 text-slate-300">
                    <div class="w-6 h-6 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="info" class="w-3.5 h-3.5"></i>
                    </div>
                    <span>Fondo Inicial de Apertura en Caja: <strong class="text-white font-mono text-sm ml-1" x-text="formatCurrency(ventasTurnoStats.montoApertura)"></strong></span>
                </div>
                <div class="flex items-center gap-2 text-slate-300 bg-dark-950/60 px-3 py-1.5 rounded-xl border border-slate-800">
                    <span class="text-slate-400">Efectivo Esperado (Apertura + Ventas):</span>
                    <strong class="text-emerald-400 font-mono text-sm" x-text="formatCurrency(ventasTurnoStats.efectivoEsperado)"></strong>
                </div>
            </div>
        </template>

        <!-- Métricas del Turno: Total + Efectivo + Transferencia + Tarjeta -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- Turno Total -->
            <div class="p-4 rounded-2xl bg-dark-900/80 border border-slate-800/90 relative overflow-hidden">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total del Turno</span>
                    <div class="w-8 h-8 rounded-lg bg-brand-500/10 text-brand-400 flex items-center justify-center">
                        <i data-lucide="badge-dollar-sign" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="text-2xl font-display font-black text-white" x-text="formatCurrency(ventasTurnoStats.total)"></div>
                <div class="mt-2 text-xs text-slate-400 flex items-center justify-between">
                    <span>Transacciones:</span>
                    <span class="font-bold text-slate-200" x-text="ventasTurnoStats.totalTickets"></span>
                </div>
            </div>

            <!-- Desglose Efectivo -->
            <div class="p-4 rounded-2xl bg-dark-900/80 border border-emerald-500/20 relative overflow-hidden">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Efectivo</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center">
                        <i data-lucide="banknote" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="text-2xl font-display font-black text-emerald-400" x-text="formatCurrency(ventasTurnoStats.efectivo)"></div>
                <div class="mt-2 space-y-1.5">
                    <div class="flex justify-between text-xs text-slate-400">
                        <span x-text="ventasTurnoStats.countEfectivo + ' facturas'"></span>
                        <span class="font-bold text-emerald-400" x-text="ventasTurnoStats.pctEfectivo + '%'"></span>
                    </div>
                    <div class="w-full h-1.5 bg-dark-950 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-500 rounded-full transition-all duration-500" :style="`width: ${ventasTurnoStats.pctEfectivo}%`"></div>
                    </div>
                </div>
            </div>

            <!-- Desglose Transferencia -->
            <div class="p-4 rounded-2xl bg-dark-900/80 border border-sky-500/20 relative overflow-hidden">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-sky-400">Transferencia</span>
                    <div class="w-8 h-8 rounded-lg bg-sky-500/10 text-sky-400 flex items-center justify-center">
                        <i data-lucide="arrow-left-right" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="text-2xl font-display font-black text-sky-400" x-text="formatCurrency(ventasTurnoStats.transferencia)"></div>
                <div class="mt-2 space-y-1.5">
                    <div class="flex justify-between text-xs text-slate-400">
                        <span x-text="ventasTurnoStats.countTransferencia + ' transferencias'"></span>
                        <span class="font-bold text-sky-400" x-text="ventasTurnoStats.pctTransferencia + '%'"></span>
                    </div>
                    <div class="w-full h-1.5 bg-dark-950 rounded-full overflow-hidden">
                        <div class="h-full bg-sky-500 rounded-full transition-all duration-500" :style="`width: ${ventasTurnoStats.pctTransferencia}%`"></div>
                    </div>
                </div>
            </div>

            <!-- Desglose Tarjeta / Otros -->
            <div class="p-4 rounded-2xl bg-dark-900/80 border border-indigo-500/20 relative overflow-hidden">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-400">Tarjeta / Otros</span>
                    <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center">
                        <i data-lucide="credit-card" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="text-2xl font-display font-black text-indigo-400" x-text="formatCurrency(ventasTurnoStats.tarjeta)"></div>
                <div class="mt-2 space-y-1.5">
                    <div class="flex justify-between text-xs text-slate-400">
                        <span x-text="ventasTurnoStats.countTarjeta + ' tarjetas'"></span>
                        <span class="font-bold text-indigo-400" x-text="ventasTurnoStats.pctTarjeta + '%'"></span>
                    </div>
                    <div class="w-full h-1.5 bg-dark-950 rounded-full overflow-hidden">
                        <div class="h-full bg-indigo-500 rounded-full transition-all duration-500" :style="`width: ${ventasTurnoStats.pctTarjeta}%`"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. SECCIÓN DE GRÁFICOS INTERACTIVOS (RF-31: INDICADOR DE VENTAS & RF-32: TOP 5 ROTACIÓN) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- GRÁFICO 1: RF-31 - Indicador de Ventas por Días / Semanas -->
        <div class="lg:col-span-8 glass-panel p-5 sm:p-6 rounded-3xl border border-slate-800 flex flex-col justify-between space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-brand-500/10 text-brand-400 flex items-center justify-center border border-brand-500/20">
                            <i data-lucide="bar-chart-3" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="font-display font-bold text-base sm:text-lg text-white">Indicador de Ventas</h3>
                            <p class="text-xs text-slate-400">Evolución de facturación por días y semanas</p>
                        </div>
                    </div>
                </div>

                <!-- Selector Días / Semanas -->
                <div class="inline-flex p-1 rounded-xl bg-dark-900 border border-slate-800 text-xs">
                    <button type="button" @click="dashboardVentasView = 'dias'; renderDashboardCharts()"
                        :class="dashboardVentasView === 'dias' ? 'bg-brand-600 text-white font-bold shadow-md shadow-brand-600/30' : 'text-slate-400 hover:text-white'"
                        class="px-3 py-1.5 rounded-lg transition-all cursor-pointer">
                        Últimos 7 Días
                    </button>
                    <button type="button" @click="dashboardVentasView = 'semanas'; renderDashboardCharts()"
                        :class="dashboardVentasView === 'semanas' ? 'bg-brand-600 text-white font-bold shadow-md shadow-brand-600/30' : 'text-slate-400 hover:text-white'"
                        class="px-3 py-1.5 rounded-lg transition-all cursor-pointer">
                        Por Semanas
                    </button>
                </div>
            </div>

            <!-- Contenedor del Canvas Chart.js -->
            <div class="relative w-full h-72 sm:h-80">
                <canvas id="chartVentas"></canvas>
            </div>

            <!-- Resumen Inferior del Gráfico -->
            <div class="pt-3 border-t border-slate-800/80 grid grid-cols-2 sm:grid-cols-3 gap-3 text-center">
                <div class="p-2 rounded-xl bg-dark-900/60">
                    <p class="text-[11px] text-slate-400">Total Filtrado</p>
                    <p class="text-sm font-bold text-white mt-0.5" x-text="formatCurrency(stats.totalVentasMonto)"></p>
                </div>
                <div class="p-2 rounded-xl bg-dark-900/60">
                    <p class="text-[11px] text-slate-400">Promedio / Transacción</p>
                    <p class="text-sm font-bold text-emerald-400 mt-0.5"
                        x-text="(stats.totalVentasCount || ventas.length) > 0 ? formatCurrency(stats.totalVentasMonto / (stats.totalVentasCount || ventas.length)) : 'C$ 0.00'"></p>
                </div>
                <div class="p-2 rounded-xl bg-dark-900/60 col-span-2 sm:col-span-1">
                    <p class="text-[11px] text-slate-400">Visualización</p>
                    <p class="text-sm font-bold text-brand-300 mt-0.5" x-text="dashboardVentasView === 'dias' ? 'Diaria (7 días)' : 'Semanal (4 semanas)'"></p>
                </div>
            </div>
        </div>

        <!-- GRÁFICO 2: RF-32 - Top 5 Productos de Mayor Rotación (Más Vendidos) -->
        <div class="lg:col-span-4 glass-panel p-5 sm:p-6 rounded-3xl border border-slate-800 flex flex-col justify-between space-y-4">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center border border-indigo-500/20">
                            <i data-lucide="flame" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="font-display font-bold text-base sm:text-lg text-white">Top 5 Rotación</h3>
                            <p class="text-xs text-slate-400">Productos con mayor volumen vendido</p>
                        </div>
                    </div>
                </div>

                <!-- Canvas Gráfico Doughnut -->
                <div class="relative w-full h-48 sm:h-52 mb-4">
                    <canvas id="chartTopProductos"></canvas>
                </div>

                <!-- Lista Visual del Ranking Top 5 -->
                <div class="space-y-2.5">
                    <template x-for="p in topProductosVendidos" :key="p.producto_id">
                        <div class="p-2.5 rounded-xl bg-dark-900/60 border border-slate-800 flex items-center justify-between gap-2 text-xs">
                            <div class="flex items-center gap-2 min-w-0">
                                <!-- Medalla de Posición -->
                                <span class="w-5 h-5 rounded-full flex items-center justify-center font-bold text-[10px] flex-shrink-0"
                                    :class="{
                                        'bg-amber-400 text-slate-900': p.posicion === 1,
                                        'bg-slate-300 text-slate-900': p.posicion === 2,
                                        'bg-amber-600 text-white': p.posicion === 3,
                                        'bg-slate-800 text-slate-300': p.posicion > 3
                                    }"
                                    x-text="p.posicion"></span>
                                <div class="truncate">
                                    <p class="font-semibold text-slate-200 truncate" x-text="p.nombre || p.nombre_producto"></p>
                                    <p class="text-[10px] text-slate-400 font-mono" x-text="p.codigo || p.codigo_producto"></p>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="font-bold text-emerald-400" x-text="p.cantidadVendida + ' uds.'"></span>
                                <p class="text-[10px] text-slate-400" x-text="formatCurrency(p.totalRecaudado)"></p>
                            </div>
                        </div>
                    </template>

                    <div x-show="topProductosVendidos.length === 0" class="text-center py-6 text-xs text-slate-500">
                        <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-1 text-slate-600"></i>
                        Aún no se registran ventas para generar el ranking
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. SECCIÓN INFERIOR: RF-13 (ALERTAS DE STOCK BAJO) Y RF-33 (BAJA ROTACIÓN) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- PANEL RF-13: ALERTAS DE STOCK BAJO Y CRÍTICO -->
        <div class="glass-panel p-5 sm:p-6 rounded-3xl border border-slate-800 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800/80 pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-400 flex items-center justify-center border border-rose-500/20">
                        <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="font-display font-bold text-base sm:text-lg text-white">Alertas de Stock Bajo</h3>
                        <p class="text-xs text-slate-400">Productos con existencias &le; stock mínimo</p>
                    </div>
                </div>

                <!-- Filtro de Alertas -->
                <div class="inline-flex p-1 rounded-xl bg-dark-900 border border-slate-800 text-[11px]">
                    <button type="button" @click="stockAlertFiltro = 'todos'"
                        :class="stockAlertFiltro === 'todos' ? 'bg-brand-600 text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white'"
                        class="px-2.5 py-1 rounded-lg transition-all cursor-pointer">
                        Todos (<span x-text="stockAlerts.totalAlertas"></span>)
                    </button>
                    <button type="button" @click="stockAlertFiltro = 'critico'"
                        :class="stockAlertFiltro === 'critico' ? 'bg-rose-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                        class="px-2.5 py-1 rounded-lg transition-all cursor-pointer">
                        Agotados (<span x-text="stockAlerts.criticos.length"></span>)
                    </button>
                    <button type="button" @click="stockAlertFiltro = 'urgente'"
                        :class="stockAlertFiltro === 'urgente' ? 'bg-amber-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                        class="px-2.5 py-1 rounded-lg transition-all cursor-pointer">
                        Críticos (<span x-text="stockAlerts.urgentes.length"></span>)
                    </button>
                </div>
            </div>

            <!-- Lista de Productos en Alerta -->
            <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
                <template x-for="p in filteredStockAlerts" :key="p.producto_id">
                    <div class="p-3.5 rounded-2xl bg-dark-900/70 border border-slate-800 hover:border-slate-700 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <!-- Badge de severidad -->
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                    :class="{
                                        'bg-rose-500/20 text-rose-400 border border-rose-500/30 animate-pulse': p.stockActual <= 0,
                                        'bg-amber-500/20 text-amber-400 border border-amber-500/30': p.stockActual > 0 && p.stockActual <= Math.ceil(p.stockMinimo / 2),
                                        'bg-yellow-500/20 text-yellow-300 border border-yellow-500/30': p.stockActual > Math.ceil(p.stockMinimo / 2)
                                    }"
                                    x-text="p.stockActual <= 0 ? 'Agotado' : (p.stockActual <= Math.ceil(p.stockMinimo / 2) ? 'Crítico' : 'Bajo')"></span>
                                <span class="text-xs text-slate-400 font-mono" x-text="p.codigo_producto"></span>
                            </div>
                            <h5 class="text-sm font-bold text-white mt-1 truncate" x-text="p.nombre_producto"></h5>
                            
                            <!-- Barra de Nivel de Existencia vs Mínimo -->
                            <div class="mt-2 space-y-1">
                                <div class="flex justify-between text-[11px] text-slate-400">
                                    <span>Existencia: <strong class="text-white" x-text="p.stockActual + ' uds.'"></strong></span>
                                    <span>Mínimo: <strong class="text-slate-300" x-text="p.stockMinimo + ' uds.'"></strong></span>
                                </div>
                                <div class="w-full h-1.5 bg-dark-950 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-300"
                                        :class="p.stockActual <= 0 ? 'bg-rose-600' : (p.stockActual <= Math.ceil(p.stockMinimo / 2) ? 'bg-amber-500' : 'bg-yellow-400')"
                                        :style="`width: ${Math.min(100, (p.stockActual / (p.stockMinimo || 1)) * 100)}%`"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Botón de Reposición Directa (abre modal de ajuste de stock) -->
                        <div class="flex items-center gap-2 self-end sm:self-center flex-shrink-0">
                            <button type="button" @click="openStockModal(p)"
                                class="px-3 py-1.5 rounded-xl bg-brand-600/20 hover:bg-brand-600 text-brand-300 hover:text-white border border-brand-500/30 text-xs font-semibold transition-all flex items-center gap-1.5 cursor-pointer active:scale-95">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                <span>Ajustar Stock</span>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- Estado Vacío Saludable -->
                <div x-show="filteredStockAlerts.length === 0"
                    class="p-6 text-center rounded-2xl bg-emerald-500/5 border border-emerald-500/20 text-emerald-400 space-y-2">
                    <i data-lucide="check-circle-2" class="w-8 h-8 mx-auto text-emerald-400"></i>
                    <p class="text-sm font-bold">¡Inventario en estado óptimo!</p>
                    <p class="text-xs text-slate-400">No hay productos que requieran reposición en esta categoría.</p>
                </div>
            </div>
        </div>

        <!-- PANEL RF-33: PRODUCTOS CON BAJA O NULA ROTACIÓN -->
        <div class="glass-panel p-5 sm:p-6 rounded-3xl border border-slate-800 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800/80 pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center border border-amber-500/20">
                        <i data-lucide="hourglass" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="font-display font-bold text-base sm:text-lg text-white">Baja Rotación Comercial</h3>
                        <p class="text-xs text-slate-400">Productos con nulo o poco movimiento de ventas</p>
                    </div>
                </div>

                <!-- Filtro de Baja Rotación -->
                <div class="inline-flex p-1 rounded-xl bg-dark-900 border border-slate-800 text-[11px]">
                    <button type="button" @click="bajaRotacionFiltro = 'todos'"
                        :class="bajaRotacionFiltro === 'todos' ? 'bg-brand-600 text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white'"
                        class="px-2.5 py-1 rounded-lg transition-all cursor-pointer">
                        Todos (<span x-text="productosBajaRotacion.length"></span>)
                    </button>
                    <button type="button" @click="bajaRotacionFiltro = 'sin_ventas'"
                        :class="bajaRotacionFiltro === 'sin_ventas' ? 'bg-rose-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                        class="px-2.5 py-1 rounded-lg transition-all cursor-pointer">
                        0 Ventas
                    </button>
                    <button type="button" @click="bajaRotacionFiltro = 'poca_rotacion'"
                        :class="bajaRotacionFiltro === 'poca_rotacion' ? 'bg-amber-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                        class="px-2.5 py-1 rounded-lg transition-all cursor-pointer">
                        Poca Rotación
                    </button>
                </div>
            </div>

            <!-- Lista de Productos Estancados -->
            <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
                <template x-for="p in filteredBajaRotacion" :key="p.producto_id">
                    <div class="p-3.5 rounded-2xl bg-dark-900/70 border border-slate-800 hover:border-slate-700 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                    :class="p.tipoRotacion === 'sin_ventas' ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30'"
                                    x-text="p.tipoRotacion === 'sin_ventas' ? 'Sin Ventas Registradas' : (p.unidadesVendidas + ' uds. vendidas')"></span>
                                <span class="text-xs text-slate-400 font-mono" x-text="p.codigo_producto"></span>
                            </div>
                            <h5 class="text-sm font-bold text-white mt-1 truncate" x-text="p.nombre_producto"></h5>
                            <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-400">
                                <span>Stock Estancado: <strong class="text-slate-200" x-text="p.existencia_bodega + ' uds.'"></strong></span>
                                <span>Capital Retenido: <strong class="text-amber-400" x-text="formatCurrency(p.capitalInmovilizado)"></strong></span>
                            </div>
                        </div>

                        <!-- Sugerencia comercial o acción -->
                        <div class="flex items-center gap-2 self-end sm:self-center flex-shrink-0">
                            <button type="button" @click="currentTab = 'pos'"
                                class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition-all flex items-center gap-1.5 cursor-pointer">
                                <i data-lucide="tag" class="w-3.5 h-3.5 text-amber-400"></i>
                                <span>Promocionar</span>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- Estado Vacío -->
                <div x-show="filteredBajaRotacion.length === 0"
                    class="p-6 text-center rounded-2xl bg-emerald-500/5 border border-emerald-500/20 text-emerald-400 space-y-2">
                    <i data-lucide="check-circle-2" class="w-8 h-8 mx-auto text-emerald-400"></i>
                    <p class="text-sm font-bold">¡Excelente dinamismo de catálogo!</p>
                    <p class="text-xs text-slate-400">Todos los productos presentan un ritmo de ventas saludable.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 6. ÚLTIMAS VENTAS EN TIEMPO REAL (RF-30) -->
    <div class="glass-panel p-5 sm:p-6 rounded-3xl border border-slate-800 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800/80 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-brand-500/10 text-brand-400 flex items-center justify-center border border-brand-500/20">
                    <i data-lucide="receipt" class="w-4 h-4"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-display font-bold text-base sm:text-lg text-white">Últimas Ventas Emitidas</h3>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-brand-500/15 text-brand-300 border border-brand-500/30">
                            Últimas 10 (Tiempo Real)
                        </span>
                    </div>
                    <p class="text-xs text-slate-400">Listado actualizado automáticamente al registrar ventas en el POS</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="currentTab = 'ventas'" class="text-xs text-brand-400 hover:text-brand-300 font-semibold flex items-center gap-1.5 cursor-pointer px-3 py-1.5 rounded-xl bg-dark-900 border border-slate-800 hover:border-brand-500/30 transition-all">
                    <span>Ver historial completo</span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </button>
            </div>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-800/60">
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase bg-dark-900/80 text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="py-3 px-4 font-semibold">Código</th>
                        <th class="py-3 px-4 font-semibold">Cliente</th>
                        <th class="py-3 px-4 font-semibold">Método de Pago</th>
                        <th class="py-3 px-4 font-semibold">Fecha y Hora</th>
                        <th class="py-3 px-4 text-right font-semibold">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <template x-for="v in ultimasVentas" :key="v.venta_id || v.codigo_venta">
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="py-3.5 px-4 font-semibold text-brand-300 dark:text-indigo-400 font-mono" x-text="v.codigo_venta"></td>
                            <td class="py-3.5 px-4 text-slate-200" x-text="v.cliente_nombre"></td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-1 text-xs rounded-lg font-medium inline-flex items-center gap-1"
                                    :class="{
                                        'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20': (v.metodo_pago || '').toLowerCase() === 'efectivo',
                                        'bg-sky-500/10 text-sky-400 border border-sky-500/20': (v.metodo_pago || '').toLowerCase() === 'transferencia',
                                        'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20': ['tarjeta', 'debito', 'credito'].includes((v.metodo_pago || '').toLowerCase())
                                    }">
                                    <i data-lucide="circle-dot" class="w-2.5 h-2.5"></i>
                                    <span x-text="v.metodo_pago"></span>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-400" x-text="formatDate(v.fecha_hora_venta)"></td>
                            <td class="py-3.5 px-4 text-right font-bold text-white font-mono" x-text="formatCurrency(v.total_venta)"></td>
                        </tr>
                    </template>
                    <tr x-show="ultimasVentas.length === 0">
                        <td colspan="5" class="py-8 text-center text-slate-500">
                            <i data-lucide="receipt" class="w-8 h-8 mx-auto mb-1 text-slate-600"></i>
                            No hay ventas registradas aún. Abre el POS para registrar la primera venta.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>