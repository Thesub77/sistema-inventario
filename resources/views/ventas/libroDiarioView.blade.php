{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Libro Diario de Ventas Fiscal (Régimen Cuota Fija)
    Archivo: resources/views/ventas/libroDiarioView.blade.php
    Propósito: Permite la consulta mensual y anual consolidada de las ventas
               diarias para cumplimiento tributario ante la Dirección General de
               Ingresos (DGI) bajo la Ley 822 (RF-27). Incluye impresión formal
               con membrete, RUC, fecha de emisión y número de facturas emitidas.
    Endpoint asociado: GET /api/fiscal/libro-diario
    Integración: Incluido en welcome.blade.php mediante @include('ventas.libroDiarioView')
    =============================================================================
--}}

<div x-show="currentTab === 'libro-diario'" x-cloak
    x-init="if (currentTab === 'libro-diario') { fetchLibroDiario(); if (typeof fetchTechoFiscal === 'function') fetchTechoFiscal(); if (typeof fetchCuentasPorPagar === 'function') fetchCuentasPorPagar(); if (typeof fetchVentas === 'function') fetchVentas(); if (typeof fetchInventario === 'function') fetchInventario(); if (typeof fetchBitacoras === 'function') fetchBitacoras(); $nextTick(() => { if (window.lucide) window.lucide.createIcons(); }); } $watch('currentTab', v => { if (v === 'libro-diario') { fetchLibroDiario(); if (typeof fetchTechoFiscal === 'function') fetchTechoFiscal(); if (typeof fetchCuentasPorPagar === 'function') fetchCuentasPorPagar(); if (typeof fetchVentas === 'function') fetchVentas(); if (typeof fetchInventario === 'function') fetchInventario(); if (typeof fetchBitacoras === 'function') fetchBitacoras(); $nextTick(() => { if (window.lucide) window.lucide.createIcons(); }); } }); $watch('libroDiarioData', () => $nextTick(() => { if (window.lucide) window.lucide.createIcons(); })); $watch('techoFiscalData', () => $nextTick(() => { if (window.lucide) window.lucide.createIcons(); }))"
    class="space-y-6">

    <!-- 1. PANEL PRINCIPAL DE CABECERA Y FILTROS -->
    <div class="glass-panel p-5 sm:p-6 rounded-3xl space-y-5 border border-slate-200 dark:border-slate-800 shadow-sm no-print">

        <!-- Fila Superior: Título, Insignia y Botones de Acción -->
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 pb-4 border-b border-slate-200 dark:border-slate-800/80">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-brand-600 to-indigo-500 text-white flex items-center justify-center shadow-md shadow-brand-500/20 flex-shrink-0">
                    <i data-lucide="scale" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-display font-black text-lg sm:text-xl text-slate-900 dark:text-white">
                            Balance Mensual
                        </h2>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Balance financiero mensual con ventas diarias, gastos operativos, compras de stock y resultado del período.
                    </p>
                </div>
            </div>

            <!-- Botones de Acción: Imprimir, Exportar y Accesos -->
            <div class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto justify-start lg:justify-end">
                <!-- Botón: Imprimir Balance Mensual Formal -->
                <button type="button" @click="printLibroDiario()"
                    class="px-4 py-2.5 bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 text-white rounded-xl text-xs sm:text-sm font-bold shadow-md shadow-brand-600/30 flex items-center gap-2 transition-all cursor-pointer active:scale-95">
                    <i data-lucide="printer" class="w-4 h-4 text-white"></i>
                    <span>Imprimir Balance Mensual</span>
                </button>
            </div>
        </div>

        <!-- Fila de Controles: Selector de Mes, Año y Datos de Membrete -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4 items-end">

            <!-- 1. Selector de Mes -->
            <div class="sm:col-span-1 lg:col-span-4 space-y-1.5">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-brand-500"></i>
                        <span>Mes de Consulta:</span>
                    </span>
                </label>
                <select x-model.number="libroDiarioMes" @change="fetchLibroDiario()"
                    class="w-full bg-white dark:bg-dark-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-800 dark:text-slate-100 font-semibold focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors">
                    <template x-for="m in mesesList" :key="m.id">
                        <option :value="m.id" x-text="m.label" :selected="m.id === libroDiarioMes"></option>
                    </template>
                </select>
            </div>

            <!-- 2. Selector de Año Fiscal -->
            <div class="sm:col-span-1 lg:col-span-4 space-y-1.5">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="calendar-days" class="w-3.5 h-3.5 text-brand-500"></i>
                        <span>Año Fiscal:</span>
                    </span>
                </label>
                <select x-model.number="libroDiarioAnio" @change="fetchLibroDiario()"
                    class="w-full bg-white dark:bg-dark-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-800 dark:text-slate-100 font-semibold focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors">
                    <template x-for="y in aniosList" :key="y">
                        <option :value="y" x-text="y" :selected="y === libroDiarioAnio"></option>
                    </template>
                </select>
            </div>

            <!-- 3. Membrete / Datos Fiscales del Negocio -->
            <div class="sm:col-span-2 lg:col-span-4 p-2.5 rounded-xl bg-slate-50 dark:bg-dark-900/60 border border-slate-200 dark:border-slate-800 text-[11px] space-y-0.5">
                <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                    <span class="font-bold truncate" x-text="empresa?.nombre_comercial || 'Mi Negocio'"></span>
                    <span class="font-mono text-[10px] text-brand-600 dark:text-brand-400 font-bold" x-text="empresa?.numero_ruc ? 'RUC: ' + empresa.numero_ruc : 'RUC: Pendiente'"></span>
                </div>
                <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate" x-text="empresa?.razon_social || 'Contribuyente Régimen Cuota Fija'"></p>
                <p class="text-[10px] text-slate-400 font-mono" x-show="libroDiarioEmisionFecha" x-text="'Emitido: ' + libroDiarioEmisionFecha"></p>
            </div>
        </div>
    </div>

    <!-- 2. CONTROL DE LÍMITE FISCAL DGI (RÉGIMEN CUOTA FIJA - LEY 822) -->
    <div class="glass-panel p-4 sm:p-5 rounded-3xl border border-slate-200 dark:border-slate-800 relative overflow-hidden space-y-3.5 shadow-sm no-print">
        
        <!-- Resplandor sutil adaptativo de semáforo -->
        <div class="absolute -right-20 -top-20 w-56 h-56 rounded-full blur-3xl pointer-events-none transition-all duration-700"
            :class="{
                'bg-emerald-500/10': libroFiscalTecho.estado_semaforo === 'normal',
                'bg-amber-500/10': libroFiscalTecho.estado_semaforo === 'alerta',
                'bg-rose-500/10': libroFiscalTecho.estado_semaforo === 'excedido'
            }"></div>

        <!-- Encabezado y Métricas DGI en Línea -->
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            
            <!-- Identificador y Estado del Semáforo -->
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl flex items-center justify-center border shadow-xs flex-shrink-0 transition-colors"
                    :class="{
                        'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30': libroFiscalTecho.estado_semaforo === 'normal',
                        'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/30': libroFiscalTecho.estado_semaforo === 'alerta',
                        'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/30': libroFiscalTecho.estado_semaforo === 'excedido'
                    }">
                    <i data-lucide="shield-check" x-show="libroFiscalTecho.estado_semaforo === 'normal'" class="w-5 h-5"></i>
                    <i data-lucide="alert-triangle" x-show="libroFiscalTecho.estado_semaforo === 'alerta'" class="w-5 h-5"></i>
                    <i data-lucide="alert-octagon" x-show="libroFiscalTecho.estado_semaforo === 'excedido'" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="font-display font-bold text-sm sm:text-base text-slate-900 dark:text-white">
                            Límite Fiscal DGI (Cuota Fija)
                        </h3>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border transition-colors"
                            :class="{
                                'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/60': libroFiscalTecho.estado_semaforo === 'normal',
                                'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/60': libroFiscalTecho.estado_semaforo === 'alerta',
                                'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800/60': libroFiscalTecho.estado_semaforo === 'excedido'
                            }">
                            <span class="w-2 h-2 rounded-full"
                                :class="{
                                    'bg-emerald-500': libroFiscalTecho.estado_semaforo === 'normal',
                                    'bg-amber-500 animate-pulse': libroFiscalTecho.estado_semaforo === 'alerta',
                                    'bg-rose-500 animate-pulse': libroFiscalTecho.estado_semaforo === 'excedido'
                                }"></span>
                            <span x-text="libroFiscalTecho.estado_semaforo === 'normal' ? 'En Rango Seguro' : (libroFiscalTecho.estado_semaforo === 'alerta' ? 'Alerta Preventiva' : 'Límite Superado')"></span>
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Monitoreo de facturación frente al límite autorizado (Ley 822)
                    </p>
                </div>
            </div>

            <!-- Métricas Clave DGI (Sin duplicar ventas ni tickets) -->
            <div class="flex flex-wrap items-center gap-2.5 text-xs">
                <!-- Margen Disponible -->
                <div class="px-3 py-2 rounded-xl border flex items-center gap-2"
                    :class="{
                        'bg-emerald-50/50 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-800/50': libroFiscalTecho.estado_semaforo === 'normal',
                        'bg-amber-50/50 dark:bg-amber-950/20 border-amber-200 dark:border-amber-800/50': libroFiscalTecho.estado_semaforo === 'alerta',
                        'bg-rose-50/50 dark:bg-rose-950/20 border-rose-200 dark:border-rose-800/50': libroFiscalTecho.estado_semaforo === 'excedido'
                    }">
                    <span class="text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400">Margen Libre DGI:</span>
                    <strong class="font-mono text-sm font-black"
                        :class="{
                            'text-emerald-700 dark:text-emerald-400': libroFiscalTecho.estado_semaforo === 'normal',
                            'text-amber-700 dark:text-amber-400': libroFiscalTecho.estado_semaforo === 'alerta',
                            'text-rose-700 dark:text-rose-400': libroFiscalTecho.estado_semaforo === 'excedido'
                        }"
                        x-text="formatCurrency(libroFiscalTecho.saldo_disponible)"></strong>
                </div>

                <!-- Tope Mensual -->
                <div class="px-3 py-2 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-200 dark:border-slate-800 flex items-center gap-2">
                    <span class="text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400">Tope Mensual:</span>
                    <strong class="font-mono text-xs text-slate-700 dark:text-slate-300" x-text="formatCurrency(libroFiscalTecho.techo_mensual)"></strong>
                </div>

                <!-- Acumulado Anual -->
                <div class="px-3 py-2 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-200 dark:border-slate-800 flex items-center gap-2">
                    <span class="text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400">Acumulado Anual:</span>
                    <strong class="font-mono text-xs text-indigo-600 dark:text-indigo-400" x-text="formatCurrency(libroFiscalTecho.ventas_anual_acumulado)"></strong>
                    <span class="text-[10px] text-slate-400 font-mono" x-text="'/ ' + formatCurrency(libroFiscalTecho.techo_anual)"></span>
                </div>
            </div>

        </div>

        <!-- Barra de Progreso DGI -->
        <div class="relative z-10 space-y-1 pt-1">
            <div class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-400">
                <span>
                    Consumo mensual: <strong class="font-mono font-bold"
                        :class="{
                            'text-emerald-600 dark:text-emerald-400': libroFiscalTecho.estado_semaforo === 'normal',
                            'text-amber-600 dark:text-amber-400': libroFiscalTecho.estado_semaforo === 'alerta',
                            'text-rose-600 dark:text-rose-400': libroFiscalTecho.estado_semaforo === 'excedido'
                        }"
                        x-text="libroFiscalTecho.porcentaje_consumido + '%'"></strong> del tope legal
                </span>
                <span class="text-[11px] text-slate-500 dark:text-slate-400"
                    x-text="libroFiscalTecho.techo_mensual > 0 ? (100 - Math.min(100, libroFiscalTecho.porcentaje_consumido)).toFixed(1) + '% margen libre' : ''"></span>
            </div>
            
            <div class="w-full h-2 bg-slate-100 dark:bg-dark-950 rounded-full overflow-hidden p-0.5 border border-slate-200/80 dark:border-slate-800">
                <div class="h-full rounded-full transition-all duration-700 ease-out"
                    :class="{
                        'bg-gradient-to-r from-emerald-500 to-emerald-400': libroFiscalTecho.estado_semaforo === 'normal',
                        'bg-gradient-to-r from-amber-500 to-amber-400': libroFiscalTecho.estado_semaforo === 'alerta',
                        'bg-gradient-to-r from-rose-600 to-rose-500': libroFiscalTecho.estado_semaforo === 'excedido'
                    }"
                    :style="`width: ${Math.min(100, libroFiscalTecho.porcentaje_consumido)}%`">
                </div>
            </div>
        </div>

    </div>

    <!-- 3. PANEL DE CONTROL FINANCIERO Y RESULTADO DEL MES (GESTIÓN INTUITIVA MiPyME) -->
    <div class="glass-panel p-5 sm:p-6 rounded-3xl border border-slate-200 dark:border-slate-800 space-y-4 shadow-sm no-print">
        
        <!-- Encabezado del Panel Financiero -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800/80 pb-3.5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 flex items-center justify-center shadow-xs flex-shrink-0">
                    <i data-lucide="calculator" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-white">
                            Resultados del Mes
                        </h3>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Separación clara entre gastos operativos, inversión en mercadería y pérdidas reales por mermas.
                    </p>
                </div>
            </div>

            <!-- Banner Resumen de Resultado del Mes (Ganancia Operativa Real) -->
            <div class="flex items-center gap-2.5 px-4 py-2 rounded-2xl border transition-all"
                :class="libroFiscalResultadoMes >= 0 
                    ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-700 dark:text-emerald-300' 
                    : 'bg-rose-500/10 border-rose-500/30 text-rose-700 dark:text-rose-300'">
                <div class="text-right">
                    <span class="block text-[10px] uppercase font-bold tracking-wider text-slate-500 dark:text-slate-400">Resultado del Mes</span>
                    <span class="font-display font-black text-base sm:text-lg font-mono tracking-tight"
                        x-text="formatCurrency(libroFiscalResultadoMes)"></span>
                </div>
                <div class="h-8 w-px bg-slate-300 dark:bg-slate-700"></div>
                <div class="text-left">
                    <span class="block text-[10px] uppercase font-bold tracking-wider text-slate-500 dark:text-slate-400">Margen</span>
                    <span class="font-bold text-xs font-mono" x-text="libroFiscalMargen + '%'"></span>
                </div>
            </div>
        </div>

        <!-- LAS 4 TARJETAS DE CABECERA (UI) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 pt-1">
            
            <!-- Tarjeta 1 (Verde): Total Facturado (Ventas) -->
            <div class="p-4 rounded-2xl bg-white dark:bg-dark-900/80 border border-emerald-500/30 dark:border-emerald-500/20 bg-emerald-50/10 dark:bg-emerald-950/10 shadow-2xs transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">1. Total Facturado (Ventas)</span>
                    <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <i data-lucide="trending-up" class="w-4 h-4"></i>
                    </div>
                </div>
                <div>
                    <div class="text-xl sm:text-2xl font-display font-black text-slate-900 dark:text-white font-mono tracking-tight"
                        x-text="formatCurrency(libroFiscalTotalIngresos)"></div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full" :class="libroFiscalTotalIngresos > 0 ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                        <span x-text="libroFiscalTotalTransacciones + ' facturas/tickets'"></span>
                    </div>
                </div>
            </div>

            <!-- Tarjeta 2 (Gris): Gastos Operativos -->
            <div class="p-4 rounded-2xl bg-white dark:bg-dark-900/80 border border-slate-300 dark:border-slate-700 bg-slate-50/40 dark:bg-slate-800/20 shadow-2xs transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">2. Gastos Operativos</span>
                    <div class="w-8 h-8 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center">
                        <i data-lucide="receipt" class="w-4 h-4"></i>
                    </div>
                </div>
                <div>
                    <div class="text-xl sm:text-2xl font-display font-black text-slate-900 dark:text-slate-200 font-mono tracking-tight"
                        x-text="formatCurrency(libroFiscalTotalGastosOperativos)"></div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 truncate" title="Servicios, DGI, empaques y gastos diarios en efectivo">
                        <span>Servicios</span>
                    </div>
                </div>
            </div>

            <!-- Tarjeta 3 (Naranja): Compras de Stock (Pagado / Pendiente) -->
            <div class="p-4 rounded-2xl bg-white dark:bg-dark-900/80 border border-amber-500/30 dark:border-amber-500/20 bg-amber-50/10 dark:bg-amber-950/10 shadow-2xs transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">3. Compras Stock</span>
                        <span class="text-[9px] px-1.5 py-0.2 rounded font-bold bg-amber-500/20 text-amber-700 dark:text-amber-300">Mercadería</span>
                    </div>
                    <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <i data-lucide="boxes" class="w-4 h-4"></i>
                    </div>
                </div>
                <div>
                    <div class="text-xl sm:text-2xl font-display font-black text-amber-700 dark:text-amber-400 font-mono tracking-tight"
                        x-text="formatCurrency(libroFiscalTotalInventario)"></div>
                    <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold" x-text="'Pagado: ' + formatCurrency(libroFiscalInventarioPagado)"></span>
                        <span class="text-slate-300 dark:text-slate-700">|</span>
                        <span class="text-amber-600 dark:text-amber-400 font-bold" x-text="'Pendiente: ' + formatCurrency(libroFiscalInventarioPendiente)"></span>
                    </div>
                </div>
            </div>

            <!-- Tarjeta 4 (Rojo): Mermas y Pérdidas -->
            <div class="p-4 rounded-2xl bg-white dark:bg-dark-900/80 border border-rose-500/30 dark:border-rose-500/20 bg-rose-50/10 dark:bg-rose-950/10 shadow-2xs transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-rose-700 dark:text-rose-400">4. Mermas y Pérdidas</span>
                        <span class="text-[9px] px-1.5 py-0.2 rounded font-bold bg-rose-500/20 text-rose-700 dark:text-rose-300">Pérdida Real</span>
                    </div>
                    <div class="w-8 h-8 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </div>
                </div>
                <div>
                    <div class="text-xl sm:text-2xl font-display font-black text-rose-600 dark:text-rose-400 font-mono tracking-tight"
                        x-text="formatCurrency(libroFiscalTotalMermas)"></div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full" :class="libroFiscalTotalMermas > 0 ? 'bg-rose-500' : 'bg-slate-400'"></span>
                        <span x-text="libroFiscalMermasMes.length + ' producto(s) afectado(s)'"></span>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- 4. DETALLE DIARIO DE VENTAS (PANTALLA - CUADRÍCULA PROPORCIONADA & LISTA COMPACTA) -->
    <div class="glass-panel rounded-3xl overflow-hidden border border-slate-200 dark:border-slate-800 shadow-sm no-print">
        <div class="p-4 sm:p-5 border-b border-slate-200 dark:border-slate-800/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-slate-50/50 dark:bg-dark-900/50">
            <div>
                <h3 class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-white flex items-center gap-2">
                    <span>Detalle Diario de Ventas</span>
                    <span class="text-xs font-mono font-bold px-2.5 py-0.5 rounded-full bg-brand-500/10 text-brand-600 dark:text-brand-400 border border-brand-500/20"
                        x-text="nombreMesSeleccionado + ' ' + libroDiarioAnio"></span>
                    <span x-show="libroDiarioData?.dias && libroDiarioData.dias.length > 0"
                        class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-dark-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700"
                        x-text="libroDiarioData.dias.length + (libroDiarioData.dias.length === 1 ? ' día activo' : ' días activos')"></span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Consolidado diario de ventas por fecha de facturación
                </p>
            </div>

            <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                <!-- Botón para alternar desglose de facturas individuales -->
                <button type="button" @click="showLibroComprobantesDetalle = !showLibroComprobantesDetalle; $nextTick(() => { if (window.lucide) window.lucide.createIcons(); })"
                    class="px-3.5 py-2 rounded-xl border text-xs font-bold flex items-center gap-2 transition-all cursor-pointer shadow-xs active:scale-95"
                    :class="showLibroComprobantesDetalle 
                        ? 'bg-brand-50 dark:bg-brand-950/40 text-brand-700 dark:text-brand-300 border-brand-300 dark:border-brand-700 shadow-brand-500/10' 
                        : 'bg-white dark:bg-dark-900 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'">
                    <i data-lucide="list" class="w-4 h-4"></i>
                    <span x-text="showLibroComprobantesDetalle ? 'Ocultar Facturas' : 'Ver Facturas (' + libroDiarioVentasDetalle.length + ')'"></span>
                </button>
            </div>
        </div>

        <!-- Indicador de carga -->
        <div x-show="libroDiarioLoading" class="py-16 text-center text-slate-400 space-y-2">
            <span class="inline-block animate-spin w-7 h-7 border-3 border-brand-500 border-t-transparent rounded-full"></span>
            <p class="text-xs font-semibold">Consolidando balance del período mensual...</p>
        </div>

        <!-- Estado Vacío cuando no hay ventas en el período -->
        <div x-show="!libroDiarioLoading && (!libroDiarioData?.dias || libroDiarioData.dias.length === 0)" class="py-14 text-center text-slate-500 dark:text-slate-400 font-sans">
            <div class="w-12 h-12 mx-auto mb-3 rounded-2xl bg-slate-100 dark:bg-dark-800 flex items-center justify-center text-slate-400">
                <i data-lucide="calendar-x" class="w-6 h-6"></i>
            </div>
            <p class="font-bold text-sm text-slate-800 dark:text-slate-200">No se registraron ventas en este período</p>
            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Seleccione otro mes o año fiscal para consultar los registros correspondientes.</p>
        </div>

        <!-- Cuadrícula de Tarjetas Diarias (Proporcionada, sin espacios vacíos gigantes) -->
        <div x-show="!libroDiarioLoading && libroDiarioData?.dias && libroDiarioData.dias.length > 0"
            class="p-4 sm:p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
            <template x-for="dia in (libroDiarioData?.dias || [])" :key="dia.fecha">
                <div class="p-3.5 rounded-2xl bg-white dark:bg-dark-900 border border-slate-200 dark:border-slate-800 hover:border-brand-500/40 hover:shadow-xs transition-all duration-150 flex items-center justify-between gap-3 group">
                    <!-- Fecha y Calendario -->
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-brand-500/10 text-brand-600 dark:text-brand-400 border border-brand-500/15 flex flex-col items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
                            <span class="text-[9px] font-black uppercase tracking-wider leading-none" x-text="getDayName(dia.fecha)"></span>
                            <span class="text-xs font-black font-mono leading-none mt-1" x-text="getDayNumber(dia.fecha)"></span>
                        </div>
                        <div class="min-w-0">
                            <span class="font-bold text-xs sm:text-sm text-slate-800 dark:text-slate-100 block truncate" x-text="formatDate(dia.fecha)"></span>
                            <span class="inline-flex items-center gap-1 text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                                <i data-lucide="receipt" class="w-3 h-3 text-slate-400"></i>
                                <span x-text="dia.cantidad_transacciones + (Number(dia.cantidad_transacciones) === 1 ? ' ticket' : ' tickets')"></span>
                            </span>
                        </div>
                    </div>

                    <!-- Total del Día -->
                    <div class="text-right flex-shrink-0">
                        <span class="block text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500 tracking-wider">Total</span>
                        <span class="font-display font-black text-sm sm:text-base text-emerald-600 dark:text-emerald-400 font-mono" x-text="formatCurrency(dia.total_dia)"></span>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- 4. SECCIÓN DESPLEGABLE: FACTURAS INDIVIDUALES EMITIDAS EN EL MES -->
    <div x-show="showLibroComprobantesDetalle" x-transition.duration.300ms class="glass-panel p-5 rounded-3xl border border-slate-200 dark:border-slate-800 space-y-4 no-print shadow-sm">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-brand-500/10 text-brand-500 flex items-center justify-center">
                    <i data-lucide="list-checks" class="w-4 h-4"></i>
                </div>
                <div>
                    <h4 class="font-display font-bold text-sm text-slate-900 dark:text-white">
                       Facturas Emitidas en el Período
                    </h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Inspección detallada de cada número de factura con fecha y hora de emisión para auditoría
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto">
                <!-- Buscador de comprobante o cliente -->
                <div class="relative flex-1 sm:w-56">
                    <input type="text" x-model="searchLibroComprobante"
                        placeholder="Buscar factura o cliente..."
                        class="w-full bg-white dark:bg-dark-900 border border-slate-300 dark:border-slate-700 rounded-xl pl-8 pr-7 py-1.5 text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-brand-500">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2"></i>
                    <button type="button" x-show="searchLibroComprobante" @click="searchLibroComprobante = ''"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <i data-lucide="x" class="w-3 h-3"></i>
                    </button>
                </div>

                <!-- Toggle ver anuladas -->
                <label class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400 cursor-pointer select-none">
                    <input type="checkbox" x-model="incluirAnuladasLibro"
                        class="rounded border-slate-300 dark:border-slate-700 text-brand-600 focus:ring-brand-500">
                    <span>Ver anuladas</span>
                </label>

                <span class="text-xs font-mono font-bold text-slate-500 dark:text-slate-400 ml-auto sm:ml-0"
                    x-text="libroDiarioVentasDetalle.length + ' comprobantes'"></span>
            </div>
        </div>

        <div class="overflow-x-auto max-h-80 overflow-y-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="text-[11px] uppercase bg-slate-50 dark:bg-dark-900 text-slate-500 sticky top-0 border-b border-slate-200 dark:border-slate-800 font-bold">
                    <tr>
                        <th class="py-2.5 px-3">N° Factura</th>
                        <th class="py-2.5 px-3">Fecha y Hora</th>
                        <th class="py-2.5 px-3">Cliente</th>
                        <th class="py-2.5 px-3">Cajero / Vendedor</th>
                        <th class="py-2.5 px-3">Método de Pago</th>
                        <th class="py-2.5 px-3 text-right">Total Facturado</th>
                        <th class="py-2.5 px-3 text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800 font-mono">
                    <template x-for="v in libroDiarioVentasDetalle" :key="v.venta_id">
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors"
                            :class="{'bg-rose-50/40 dark:bg-rose-950/20': Number(v.estado) === 0}">
                            <td class="py-2.5 px-3 font-bold text-brand-600 dark:text-brand-400">
                                <div class="flex items-center gap-1.5">
                                    <span x-text="v.codigo_venta"></span>
                                    <span x-show="Number(v.estado) === 0"
                                        class="text-[9px] font-sans font-bold px-1.5 py-0.2 rounded bg-rose-500/10 text-rose-500 border border-rose-500/20">Anulada</span>
                                </div>
                            </td>
                            <td class="py-2.5 px-3 text-slate-600 dark:text-slate-300" x-text="formatDate(v.fecha_hora_venta)"></td>
                            <td class="py-2.5 px-3 font-sans text-slate-800 dark:text-slate-200" x-text="v.cliente_nombre || 'Consumidor Final'"></td>
                            <td class="py-2.5 px-3 font-sans text-slate-600 dark:text-slate-400" x-text="v.usuario?.nombre_apellido || 'N/A'"></td>
                            <td class="py-2.5 px-3 capitalize font-sans">
                                <span class="px-2 py-0.5 text-[11px] rounded-md font-medium"
                                    :class="v.metodo_pago === 'Efectivo' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-brand-500/10 text-brand-600 dark:text-brand-400'"
                                    x-text="v.metodo_pago || 'Efectivo'"></span>
                                <span x-show="v.referencia_transferencia" class="block font-mono text-[10px] text-slate-400 mt-0.5 truncate max-w-[130px]" :title="v.referencia_transferencia" x-text="v.referencia_transferencia"></span>
                            </td>
                            <td class="py-2.5 px-3 text-right font-bold"
                                :class="Number(v.estado) === 0 ? 'line-through text-rose-500' : 'text-slate-900 dark:text-emerald-400'"
                                x-text="formatCurrency(v.total_venta)"></td>
                            <td class="py-2.5 px-3 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button" @click="openReceiptModal(v.venta_id)"
                                        class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-[10px] font-sans font-semibold transition-all cursor-pointer flex items-center gap-1 border border-slate-200 dark:border-slate-700/60"
                                        title="Ver Comprobante Oficial">
                                        <i data-lucide="receipt" class="w-3 h-3 text-brand-500"></i>
                                        <span>Ver Ticket</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="libroDiarioVentasDetalle.length === 0">
                        <td colspan="7" class="py-6 text-center text-slate-500 font-sans">
                            No hay facturas registradas en este mes.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- =========================================================================
         5. ÁREA IMPRIMIBLE FORMAL DEL BALANCE MENSUAL & CUADERNO FINANCIERO (@media print / AUDITORÍA DGI LEY 822)
         ========================================================================= -->
    <div id="printableLibroDiarioArea" class="hidden print:block bg-white text-black font-sans p-6 text-xs">

        <!-- MEMBRETE DEL COMERCIO Y ENCABEZADO FISCAL -->
        <div class="border-b-2 border-black pb-4 mb-4">
            <div class="flex justify-between items-start">
                <div class="space-y-0.5">
                    <h1 class="text-xl font-black uppercase tracking-tight text-black"
                        x-text="empresa?.nombre_comercial || 'SISTEMA COMERCIAL'"></h1>
                    <h2 class="text-sm font-bold text-black"
                        x-show="empresa?.razon_social"
                        x-text="empresa?.razon_social"></h2>
                    <p class="text-xs font-bold text-black"
                        x-show="empresa?.numero_ruc"
                        x-text="'RUC: ' + empresa?.numero_ruc"></p>
                    <p class="text-xs text-black"
                        x-show="empresa?.direccion_fisica"
                        x-text="empresa?.direccion_fisica"></p>
                    <p class="text-xs text-black"
                        x-show="empresa?.telefono_contacto || empresa?.correo_contacto">
                        <span x-show="empresa?.telefono_contacto" x-text="'Tel: ' + formatTelefono(empresa.telefono_contacto)"></span>
                        <span x-show="empresa?.telefono_contacto && empresa?.correo_contacto"> | </span>
                        <span x-show="empresa?.correo_contacto" x-text="empresa.correo_contacto"></span>
                    </p>
                </div>

                <div class="text-right space-y-1">
                    <div class="inline-block border-2 border-black px-3 py-1 rounded bg-neutral-100 font-black text-xs uppercase tracking-wide">
                        BALANCE MENSUAL Y CONTROL FINANCIERO
                    </div>
                    <p class="text-[11px] font-bold text-black">
                        RÉGIMEN SIMPLIFICADO CUOTA FIJA
                    </p>
                    <p class="text-[10px] font-mono font-bold text-black pt-1">
                        Período: <span class="uppercase" x-text="nombreMesSeleccionado + ' ' + libroDiarioAnio"></span>
                    </p>
                </div>
            </div>
        </div>

        <!-- METADATOS DEL REPORTE -->
        <div class="grid grid-cols-2 gap-4 text-xs mb-4 p-3 bg-neutral-50 border border-black rounded">
            <div class="space-y-0.5">
                <p><strong>Período Mensual Auditado:</strong> <span class="font-bold uppercase" x-text="nombreMesSeleccionado + ' ' + libroDiarioAnio"></span></p>
                <p><strong>Fecha y Hora de Emisión:</strong> <span x-text="libroDiarioEmisionFecha"></span></p>
                <p><strong>Días con Operación:</strong> <span x-text="(libroDiarioData?.dias?.length || 0) + ' días activos'"></span></p>
            </div>
            <div class="text-right space-y-0.5">
                <p><strong>Moneda Oficial:</strong> <span x-text="(empresa?.moneda_simbolo === '$' || empresa?.moneda_simbolo === 'USD') ? 'Dólares ($)' : 'Córdobas (C$)'">Córdobas (C$)</span></p>
                <p><strong>Comprobantes Emitidos:</strong> <span class="font-bold" x-text="libroFiscalTotalTransacciones + ' facturas/tickets'"></span></p>
            </div>
        </div>

        <!-- RESUMEN EJECUTIVO: CONSOLIDADO FINANCIERO DEL PERÍODO -->
        <div class="grid grid-cols-4 gap-2 mb-3">
            <!-- 1. Total Facturado (Ventas) -->
            <div class="border border-black p-2 bg-neutral-100 text-center rounded">
                <p class="text-[9px] uppercase font-bold text-black">Total Facturado (Ventas)</p>
                <p class="text-sm font-black font-mono text-black mt-0.5" x-text="formatCurrency(libroFiscalTotalIngresos)"></p>
                <p class="text-[8px] text-neutral-600 font-sans" x-text="libroFiscalTotalTransacciones + ' facturas/tickets'"></p>
            </div>
            <!-- 2. Gastos Operativos -->
            <div class="border border-black p-2 bg-neutral-100 text-center rounded">
                <p class="text-[9px] uppercase font-bold text-black">Gastos Operativos</p>
                <p class="text-sm font-black font-mono text-black mt-0.5" x-text="formatCurrency(libroFiscalTotalGastosOperativos)"></p>
                <p class="text-[8px] text-neutral-600 font-sans">Servicios</p>
            </div>
            <!-- 3. Compras de Stock -->
            <div class="border border-black p-2 bg-neutral-100 text-center rounded">
                <p class="text-[9px] uppercase font-bold text-black">Compras de Stock</p>
                <p class="text-sm font-black font-mono text-black mt-0.5" x-text="formatCurrency(libroFiscalTotalInventario)"></p>
                <p class="text-[8px] text-neutral-600 font-sans font-medium" x-text="'Pag: ' + formatCurrency(libroFiscalInventarioPagado) + ' | Pend: ' + formatCurrency(libroFiscalInventarioPendiente)"></p>
            </div>
            <!-- 4. Mermas Registradas -->
            <div class="border border-black p-2 bg-neutral-100 text-center rounded">
                <p class="text-[9px] uppercase font-bold text-black">Mermas Registradas</p>
                <p class="text-sm font-black font-mono text-black mt-0.5" x-text="formatCurrency(libroFiscalTotalMermas)"></p>
                <p class="text-[8px] text-neutral-600 font-sans font-bold">Pérdida Registrada</p>
            </div>
        </div>

        <!-- BANNER DESTACADO: RESULTADO DEL MES (FLUJO NETO DE DINERO) -->
        <div class="border-2 border-black p-2.5 mb-3 rounded flex items-center justify-between bg-neutral-50">
            <div class="space-y-0.5">
                <div class="flex items-center gap-2">
                    <span class="text-xs uppercase font-black tracking-wide text-black">
                        RESULTADO DEL MES (FLUJO NETO DE DINERO)
                    </span>
                    <span class="border border-black px-1.5 py-0.2 text-[9px] font-bold uppercase rounded bg-white text-black"
                        x-text="libroFiscalResultadoMes >= 0 ? 'Superávit Neto' : 'Déficit Neto'"></span>
                </div>
                <p class="text-[9px] text-neutral-600 font-sans">
                    Cálculo de Flujo: (+) Total Ventas (-) Gastos Operativos (-) Pagos a Proveedores.
                </p>
            </div>
            <div class="text-right flex-shrink-0">
                <p class="text-base font-black font-mono text-black"
                    x-text="formatCurrency(libroFiscalResultadoMes)"></p>
                <p class="text-[9px] font-bold text-neutral-700" x-text="'Margen: ' + libroFiscalMargen + '%'"></p>
            </div>
        </div>

        <!-- SECCIÓN I: CUADERNO MENSUAL DE INGRESOS (VENTAS FACTURADAS) -->
        <div class="mb-5">
            <div class="border-b-2 border-black pb-1 mb-1.5">
                <h3 class="text-xs font-black uppercase tracking-wider">
                    I. Cuaderno de Ingresos - Ventas Diarias Facturadas
                </h3>
            </div>
            <p class="text-[10px] text-neutral-600 mb-2">
                Registro consolidado por día de emisión de tickets y facturas. No se contemplan otros ingresos en el mes distintos a la facturación comercial.
            </p>

            <table class="w-full text-left text-xs border border-black">
                <thead>
                    <tr class="bg-neutral-100 border-b border-black font-bold uppercase text-[10px]">
                        <th class="py-1 px-3 border-r border-black">Fecha</th>
                        <th class="py-1 px-3 border-r border-black text-center">Cantidad Tickets</th>
                        <th class="py-1 px-3 text-right" x-text="'Total Diario (' + (empresa?.moneda_simbolo || 'C$') + ')'">Total Diario (C$)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black font-mono text-[11px]">
                    <template x-for="d in (libroDiarioData?.dias || [])" :key="d.fecha">
                        <tr class="border-b border-black">
                            <td class="py-1 px-3 border-r border-black font-sans font-medium" x-text="formatDate(d.fecha)"></td>
                            <td class="py-1 px-3 border-r border-black text-center" x-text="d.cantidad_transacciones"></td>
                            <td class="py-1 px-3 text-right font-bold" x-text="formatCurrency(d.total_dia)"></td>
                        </tr>
                    </template>
                    <tr x-show="!libroDiarioData?.dias || libroDiarioData.dias.length === 0">
                        <td colspan="3" class="py-3 px-3 text-center text-neutral-500 font-sans">
                            No se registraron ventas facturadas en este período fiscal.
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="bg-neutral-100 border-t-2 border-black font-bold text-xs">
                        <td class="py-1.5 px-3 border-r border-black uppercase font-black"
                            x-text="'TOTAL ACUMULADO VENTAS (' + (empresa?.moneda_simbolo || 'C$') + ')'">
                            TOTAL ACUMULADO VENTAS
                        </td>
                        <td class="py-1.5 px-3 border-r border-black text-center font-black"
                            x-text="libroFiscalTotalTransacciones + ' tickets'"></td>
                        <td class="py-1.5 px-3 text-right font-black text-sm font-mono"
                            x-text="formatCurrency(libroFiscalTotalIngresos)"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- SECCIÓN II: GASTOS OPERATIVOS Y GASTOS DIARIOS EN EFECTIVO -->
        <div class="mb-5" style="page-break-inside: avoid;">
            <div class="flex items-center justify-between border-b-2 border-black pb-1 mb-1.5">
                <h3 class="text-xs font-black uppercase tracking-wider">
                    II. Gastos Operativos y Gastos Diarios en Efectivo
                </h3>
            </div>
            <p class="text-[10px] text-neutral-600 mb-2">
                Gastos indispensables para mantener operando el negocio: recibos de servicios, alquiler, cuota fija DGI y egresos menores de caja chica.
            </p>

            <table class="w-full text-left text-xs border border-black">
                <thead>
                    <tr class="bg-neutral-100 border-b border-black font-bold uppercase text-[10px]">
                        <th class="py-1 px-3 border-r border-black">Fecha</th>
                        <th class="py-1 px-3 border-r border-black">Proveedor</th>
                        <th class="py-1 px-3 border-r border-black">Concepto</th>
                        <th class="py-1 px-3 border-r border-black text-center">Tipo</th>
                        <th class="py-1 px-3 text-right">Monto (C$)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black text-[11px]">

                    <!-- 1. Gastos operativos registrados en Deudas con Proveedores -->
                    <template x-for="c in libroFiscalGastosOperativosCuentas" :key="c.cuenta_por_pagar_id">
                        <tr class="border-b border-neutral-300">
                            <td class="py-1 px-3 border-r border-black font-sans font-medium text-[10px]" x-text="formatDate(c.fecha_emision || c.created_at)"></td>
                            <td class="py-1 px-3 border-r border-black font-bold font-sans" x-text="c.proveedor ? (c.proveedor.nombre_comercial || c.proveedor.razon_social) : ('Proveedor #' + (c.id_proveedor || ''))"></td>
                            <td class="py-1 px-3 border-r border-black truncate max-w-[260px]" x-text="c.descripcion || 'Gasto Operativo'"></td>
                            <td class="py-1 px-3 border-r border-black text-center text-[10px] capitalize font-sans" x-text="c.estado || 'Pendiente'"></td>
                            <td class="py-1 px-3 text-right font-mono font-bold" x-text="formatCurrency(c.monto_total)"></td>
                        </tr>
                    </template>

                    <!-- 2. Gastos Diarios en Efectivo de Caja (Listados uno a uno) -->
                    <template x-for="eg in libroFiscalCajaEgresosMes" :key="eg.caja_movimiento_venta_id">
                        <tr class="border-b border-neutral-300">
                            <td class="py-1 px-3 border-r border-black font-sans font-medium text-[10px]" x-text="formatDate(eg.fecha_hora_movimiento || eg.created_at)"></td>
                            <td class="py-1 px-3 border-r border-black font-bold font-sans" x-text="'Control Diario'"></td>
                            <td class="py-1 px-3 border-r border-black truncate max-w-[260px]" x-text="typeof getMovimientoConcepto === 'function' ? getMovimientoConcepto(eg) : (eg.justificacion || 'Egreso de Caja')"></td>
                            <td class="py-1 px-3 border-r border-black text-center text-[10px] font-sans font-bold">Efectivo</td>
                            <td class="py-1 px-3 text-right font-mono font-bold" x-text="formatCurrency(Math.abs(Number(eg.monto_movimiento)))"></td>
                        </tr>
                    </template>

                    <!-- Fila si no hay gastos operativos ni egresos de caja registrados -->
                    <tr x-show="libroFiscalGastosOperativosCuentas.length === 0 && libroFiscalCajaEgresosMes.length === 0" class="border-b border-neutral-300 text-neutral-500 italic">
                        <td colspan="4" class="py-1.5 px-3 border-r border-black text-[10px]">
                            No se registraron gastos operativos ni egresos diarios de caja en este período mensual.
                        </td>
                        <td class="py-1.5 px-3 text-right font-mono font-bold text-neutral-400 not-italic">C$ 0.00</td>
                    </tr>

                </tbody>
                <tfoot>
                    <tr class="bg-neutral-200 border-t-2 border-black font-black text-xs">
                        <td class="py-1.5 px-3 border-r border-black uppercase" colspan="2">
                            TOTAL GASTOS OPERATIVOS DEL MES
                        </td>
                        <td class="py-1.5 px-3 border-r border-black text-[10px] font-sans text-neutral-700" colspan="2">
                            Gastos Operativos + Egresos Diarios de Caja
                        </td>
                        <td class="py-1.5 px-3 text-right text-sm font-mono font-black"
                            x-text="formatCurrency(libroFiscalTotalGastosOperativos)"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- SECCIÓN III: PAGOS A PROVEEDORES DE MERCADERÍA (SALIDAS DE DINERO) -->
        <div class="mb-5" style="page-break-inside: avoid;">
            <div class="flex items-center justify-between border-b-2 border-black pb-1 mb-1.5">
                <h3 class="text-xs font-black uppercase tracking-wider">
                    III. Pagos a Proveedores de Mercadería
                </h3>
            </div>
            <p class="text-[10px] text-neutral-600 mb-2">
                Dinero pagado y abonado a proveedores de mercadería en el período. El saldo pendiente corresponde a crédito comercial pendiente de liquidar.
            </p>

            <table class="w-full text-left text-xs border border-black">
                <thead>
                    <tr class="bg-neutral-100 border-b border-black font-bold uppercase text-[10px]">
                        <th class="py-1 px-3 border-r border-black">Fecha</th>
                        <th class="py-1 px-3 border-r border-black">Proveedor</th>
                        <th class="py-1 px-3 border-r border-black">N° Factura / Ref.</th>
                        <th class="py-1 px-3 border-r border-black">Descripción</th>
                        <th class="py-1 px-3 border-r border-black text-right">Total Factura (C$)</th>
                        <th class="py-1 px-3 border-r border-black text-right">Monto Pagado (C$)</th>
                        <th class="py-1 px-3 text-right font-bold text-neutral-900">Saldo Pendiente (Crédito)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black text-[11px]">
                    <template x-for="c in libroFiscalInventarioCuentas" :key="c.cuenta_por_pagar_id">
                        <tr class="border-b border-neutral-300">
                            <td class="py-1 px-3 border-r border-black font-sans font-medium text-[10px]" x-text="formatDate(c.fecha_emision || c.created_at)"></td>
                            <td class="py-1 px-3 border-r border-black font-bold font-sans" x-text="c.proveedor ? (c.proveedor.nombre_comercial || c.proveedor.razon_social) : ('Proveedor #' + (c.id_proveedor || ''))"></td>
                            <td class="py-1 px-3 border-r border-black font-mono text-[10px]" x-text="c.numero_factura || 'S/N'"></td>
                            <td class="py-1 px-3 border-r border-black truncate max-w-[200px]" x-text="c.descripcion || 'Compra de mercadería en stock'"></td>
                            <td class="py-1 px-3 border-r border-black text-right font-mono font-bold" x-text="formatCurrency(c.monto_total)"></td>
                            <td class="py-1 px-3 border-r border-black text-right font-mono text-emerald-800 font-bold" x-text="formatCurrency(c.monto_pagado || 0)"></td>
                            <td class="py-1 px-3 text-right font-mono font-bold text-amber-900"
                                x-text="formatCurrency((c.saldo_pendiente !== undefined && c.saldo_pendiente !== null) ? Number(c.saldo_pendiente) : (Number(c.monto_total || 0) - Number(c.monto_pagado || 0)))"></td>
                        </tr>
                    </template>

                    <tr x-show="libroFiscalInventarioCuentas.length === 0" class="border-b border-neutral-300 text-neutral-500 italic">
                        <td colspan="6" class="py-1.5 px-3 border-r border-black text-[10px]">
                            No se registraron pagos ni facturas de proveedores en este período mensual.
                        </td>
                        <td class="py-1.5 px-3 text-right font-mono font-bold text-neutral-400 not-italic">C$ 0.00</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="bg-neutral-200 border-t-2 border-black font-black text-xs">
                        <td class="py-1.5 px-3 border-r border-black uppercase" colspan="4">
                            TOTAL SALIDAS A PROVEEDORES (DINERO PAGADO)
                        </td>
                        <td class="py-1.5 px-3 border-r border-black text-right font-mono font-black"
                            x-text="formatCurrency(libroFiscalTotalInventario)"></td>
                        <td class="py-1.5 px-3 border-r border-black text-right font-mono font-bold text-black text-[11px]"
                            x-text="formatCurrency(libroFiscalInventarioPagado)"></td>
                        <td class="py-1.5 px-3 text-right font-mono font-bold text-black text-[11px]"
                            x-text="formatCurrency(libroFiscalInventarioPendiente)"></td>
                    </tr>
                </tfoot>
            </table>
            <p class="text-[9px] text-neutral-500 italic mt-1">
                * El Monto Pagado representa la salida real de dinero hacia proveedores en el mes. El saldo pendiente corresponde a crédito comercial pendiente de liquidar.
            </p>
        </div>

        <!-- SECCIÓN IV: MÓDULO DE MERMAS Y PÉRDIDAS REALES (VALOR DESTRUIDO) -->
        <div class="mb-5" style="page-break-inside: avoid;">
            <div class="flex items-center justify-between border-b-2 border-black pb-1 mb-1.5">
                <h3 class="text-xs font-black uppercase tracking-wider">
                    IV. Módulo de Mermas y Pérdidas Registradas
                </h3>
            </div>
            <p class="text-[10px] text-neutral-600 mb-2">
                Productos dañados, vencidos, deteriorados o con descarte técnico. Deducen directamente en el resultado neto del período.
            </p>

            <table class="w-full text-left text-xs border border-black">
                <thead>
                    <tr class="bg-neutral-100 border-b border-black font-bold uppercase text-[10px]">
                        <th class="py-1 px-3 border-r border-black">Fecha</th>
                        <th class="py-1 px-3 border-r border-black">Producto </th>
                        <th class="py-1 px-3 border-r border-black">Causa / Tipo de Merma</th>
                        <th class="py-1 px-3 border-r border-black text-center">Cantidad Afectada</th>
                        <th class="py-1 px-3 border-r border-black text-right">Costo Unitario (C$)</th>
                        <th class="py-1 px-3 text-right font-bold text-black">Pérdida Total (C$)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black text-[11px]">
                    <template x-for="m in libroFiscalMermasMes" :key="m.movimiento_id || m.created_at">
                        <tr class="border-b border-neutral-300">
                            <td class="py-1 px-3 border-r border-black font-sans font-medium text-[10px]" x-text="formatDate(m.fecha_movimiento || m.created_at)"></td>
                            <td class="py-1 px-3 border-r border-black font-bold font-sans" x-text="m.producto ? ((m.producto.codigo_barra ? m.producto.codigo_barra + ' - ' : '') + m.producto.nombre_producto) : ('Producto #' + (m.id_producto || ''))"></td>
                            <td class="py-1 px-3 border-r border-black text-[10px] capitalize" x-text="m.tipo_merma || m.motivo || 'Salida por Merma / Deterioro'"></td>
                            <td class="py-1 px-3 border-r border-black text-center font-mono font-bold" x-text="(m.cantidad_movimiento || 0) + ' un.'"></td>
                            <td class="py-1 px-3 border-r border-black text-right font-mono" x-text="formatCurrency(m.costo_unitario || 0)"></td>
                            <td class="py-1 px-3 text-right font-mono font-bold text-black" x-text="formatCurrency(m.costo_total_perdida || 0)"></td>
                        </tr>
                    </template>

                    <tr x-show="libroFiscalMermasMes.length === 0" class="border-b border-neutral-300 text-neutral-500 italic">
                        <td colspan="5" class="py-1.5 px-3 border-r border-black text-[10px]">
                            No se registraron mermas ni productos vencidos o dañados en este período.
                        </td>
                        <td class="py-1.5 px-3 text-right font-mono font-bold text-neutral-400 not-italic">C$ 0.00</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="bg-neutral-200 border-t-2 border-black font-black text-xs">
                        <td class="py-1.5 px-3 border-r border-black uppercase" colspan="3">
                            TOTAL PÉRDIDAS POR MERMAS DEL MES
                        </td>
                        <td class="py-1.5 px-3 border-r border-black text-center font-bold text-[10px]"
                            x-text="libroFiscalMermasMes.length + ' producto(s)'"></td>
                        <td class="py-1.5 px-3 border-r border-black text-[10px] font-sans text-neutral-700">
                            Costo de Pérdida
                        </td>
                        <td class="py-1.5 px-3 text-right text-sm font-mono font-black text-black"
                            x-text="formatCurrency(libroFiscalTotalMermas)"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- SECCIÓN V: LIQUIDACIÓN Y FLUJO NETO DE DINERO DEL MES -->
        <div class="mb-5" style="page-break-inside: avoid;">
            <div class="flex items-center justify-between border-b-2 border-black pb-1 mb-1.5">
                <h3 class="text-xs font-black uppercase tracking-wider">
                    V. Liquidación y Flujo Neto de Dinero
                </h3>
            </div>
            <p class="text-[10px] text-neutral-600 mb-2">
                Consolidado de dinero entrante y saliente del período mensual para control de caja y liquidez.
            </p>

            <table class="w-full text-left text-xs border border-black font-mono">
                <thead>
                    <tr class="bg-neutral-100 border-b border-black font-bold uppercase text-[10px] font-sans">
                        <th class="py-1 px-3 border-r border-black">Concepto Financiero</th>
                        <th class="py-1 px-3 border-r border-black text-center">Tipo de Flujo</th>
                        <th class="py-1 px-3 border-r border-black text-center">% s/ Ventas</th>
                        <th class="py-1 px-3 text-right">Monto (C$)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black text-[11px]">
                    <tr>
                        <td class="py-1 px-3 border-r border-black font-sans font-bold">(+) Total Entradas por Ventas Facturadas</td>
                        <td class="py-1 px-3 border-r border-black text-center font-sans font-bold text-emerald-800">Entrada</td>
                        <td class="py-1 px-3 border-r border-black text-center font-bold">100.0%</td>
                        <td class="py-1 px-3 text-right font-black" x-text="formatCurrency(libroFiscalTotalIngresos)"></td>
                    </tr>
                    <tr>
                        <td class="py-1 px-3 border-r border-black font-sans font-medium">(-) Salidas por Gastos Operativos (Servicios, Alquiler, DGI, Caja Chica)</td>
                        <td class="py-1 px-3 border-r border-black text-center font-sans text-neutral-600">Salida</td>
                        <td class="py-1 px-3 border-r border-black text-center" x-text="libroFiscalTotalIngresos > 0 ? ((libroFiscalTotalGastosOperativos / libroFiscalTotalIngresos) * 100).toFixed(1) + '%' : '0.0%'"></td>
                        <td class="py-1 px-3 text-right font-medium" x-text="'- ' + formatCurrency(libroFiscalTotalGastosOperativos)"></td>
                    </tr>
                    <tr>
                        <td class="py-1 px-3 border-r border-black font-sans font-medium">(-) Salidas por Pagos a Proveedores de Mercadería</td>
                        <td class="py-1 px-3 border-r border-black text-center font-sans text-neutral-600">Salida</td>
                        <td class="py-1 px-3 border-r border-black text-center" x-text="libroFiscalTotalIngresos > 0 ? ((libroFiscalInventarioPagado / libroFiscalTotalIngresos) * 100).toFixed(1) + '%' : '0.0%'"></td>
                        <td class="py-1 px-3 text-right font-medium" x-text="'- ' + formatCurrency(libroFiscalInventarioPagado)"></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="bg-neutral-200 border-t-2 border-black font-black text-xs">
                        <td class="py-2 px-3 border-r border-black uppercase font-sans font-black" colspan="2">
                            (=) SALDO NETO DE DINERO EN CAJA/BANCO DEL MES
                        </td>
                        <td class="py-2 px-3 border-r border-black text-center text-sm font-black"
                            x-text="libroFiscalMargen + '%'"></td>
                        <td class="py-2 px-3 text-right text-base font-black text-black"
                            x-text="formatCurrency(libroFiscalResultadoMes)"></td>
                    </tr>
                </tfoot>
            </table>

            <!-- Referencia de Crédito Comercial -->
            <div class="mt-2 p-2 bg-neutral-50 border border-neutral-300 rounded text-[9px] font-sans text-neutral-700 leading-tight">
                <strong>Referencia de Crédito Comercial:</strong>
                <span x-text="'Total pagado a proveedores en el mes: C$ ' + libroFiscalInventarioPagado.toLocaleString('es-NI', {minimumFractionDigits: 2}) + ' | Deuda comercial pendiente de pago: C$ ' + libroFiscalInventarioPendiente.toLocaleString('es-NI', {minimumFractionDigits: 2}) + '.'"></span>
            </div>
        </div>

        <!-- SECCIÓN VI: RELACIÓN INDIVIDUAL DE FACTURAS EMITIDAS EN EL PERÍODO (AUDITORÍA FISCAL) -->
        <template x-if="libroDiarioVentasDetalle.length > 0">
            <div class="mb-5" style="page-break-inside: avoid;">
                <div class="flex items-center justify-between border-b-2 border-black pb-1 mb-1.5">
                    <h3 class="text-xs font-black uppercase tracking-wider">
                        VI. Relación de Facturas y Comprobantes Emitidos en el Mes
                    </h3>
                    <span class="text-[10px] text-neutral-600 font-mono" x-text="libroDiarioVentasDetalle.length + ' comprobantes'"></span>
                </div>
                <table class="w-full text-left text-[10px] font-mono border border-black">
                    <thead>
                        <tr class="bg-neutral-100 border-b border-black font-bold uppercase font-sans">
                            <th class="py-1 px-2 border-r border-black">Factura N°</th>
                            <th class="py-1 px-2 border-r border-black">Fecha y Hora</th>
                            <th class="py-1 px-2 border-r border-black">Forma de Pago</th>
                            <th class="py-1 px-2 text-right">Monto (C$)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-300">
                        <template x-for="v in libroDiarioVentasDetalle" :key="v.venta_id">
                            <tr>
                                <td class="py-1 px-2 border-r border-neutral-300 font-bold" x-text="v.codigo_venta"></td>
                                <td class="py-1 px-2 border-r border-neutral-300" x-text="formatDate(v.fecha_hora_venta)"></td>
                                <td class="py-1 px-2 border-r border-neutral-300 font-sans capitalize" x-text="v.metodo_pago || 'Efectivo'"></td>
                                <td class="py-1 px-2 text-right font-bold" x-text="formatCurrency(v.total_venta)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>

        <!-- PIE DE PÁGINA FORMAL -->
        <div class="mt-6 pt-2 border-t border-dashed border-neutral-400 text-center text-[9px] text-neutral-500 font-mono">
            Documento emitido mediante FacturaStock Pro • Balance Mensual y Control Contable MiPyME • Impreso el: <span x-text="libroDiarioEmisionFecha"></span>
        </div>
    </div>

</div>

