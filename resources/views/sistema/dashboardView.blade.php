{{--
    =============================================================================
    FACTURASTOCK PRO - DASHBOARD ANALÍTICO & OPERATIVO
    Archivo: resources/views/sistema/dashboardView.blade.php
    Propósito: Panel de control integral con métricas en tiempo real, alertas de stock bajo,
               cuadre de turno desglosado (efectivo/transferencia), gráficos de ventas interactivos,
               ranking de mayor rotación y detección de productos de baja rotación.
    =============================================================================
--}}

<div x-show="currentTab === 'dashboard'" x-cloak
    x-init="$watch('currentTab', v => { if (v === 'dashboard') { if (fetchDashboardData) fetchDashboardData(); $nextTick(() => { if (window.lucide) window.lucide.createIcons(); }); } }); $watch('techoFiscalData', () => $nextTick(() => { if (window.lucide) window.lucide.createIcons(); }))"
    class="space-y-6">

    <!-- 1. ENCABEZADO DE BIENVENIDA & ACCIONES RÁPIDAS -->
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

            <!-- TARJETA DE KPI SUPERIOR: Utilidad Bruta del Período (C$) -->
            <div class="p-4 rounded-2xl bg-dark-900/90 border border-emerald-500/30 flex items-center gap-3.5 shadow-lg shadow-emerald-950/20 sm:min-w-[280px]">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/15 text-emerald-400 flex items-center justify-center border border-emerald-500/30 flex-shrink-0">
                    <i data-lucide="trending-up" class="w-6 h-6"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-400 truncate">
                            Utilidad Bruta del Período
                        </span>
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 flex-shrink-0"
                            x-text="empresa?.moneda_simbolo || 'C$'"></span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-display font-black text-white font-mono tracking-tight mt-0.5"
                        x-text="formatCurrency(utilidadBrutaPeriodo)"></div>
                    <div class="flex items-center gap-2 text-[11px] text-slate-400 mt-1">
                        <span class="font-bold text-emerald-400 flex items-center gap-0.5">
                            <i data-lucide="arrow-up-right" class="w-3 h-3"></i>
                            <span x-text="margenUtilidadBruta + '% margen'"></span>
                        </span>
                        <span class="text-slate-500">•</span>
                        <span class="truncate">Ganancia líquida real</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. CONSULTA DE VENTAS DEL TURNO / DEL DÍA (DESGLOSE EFECTIVO Y TRANSFERENCIA) -->
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
                        <p class="text-xs text-slate-400 mt-1" x-text="ventasTurnoStats.turnoActivo && ventasTurnoStats.cajeroNombre ? ((ventasTurnoStats.fechaApertura ? ' Apertura: ' + formatDate(ventasTurnoStats.fechaApertura) : '')) : 'Desglose en tiempo real por método de pago para cuadre de caja'"></p>
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

        <!-- Métricas del Turno y KPIs: Total + Efectivo + Transferencia + Tarjeta -->
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

    <!-- 3. MONITOR DE INGRESOS BRUTOS Y SEMÁFORO DE ALERTA PREVENTIVA DEL TECHO DE CUOTA FIJA (LEY 822) -->
    <div class="glass-panel p-5 sm:p-6 rounded-3xl border border-slate-200 dark:border-slate-800 relative overflow-hidden space-y-5 shadow-sm">
        
        <!-- Efecto de resplandor ambiental adaptativo según el semáforo -->
        <div class="absolute -right-20 -top-20 w-72 h-72 rounded-full blur-3xl pointer-events-none transition-all duration-700"
            :class="{
                'bg-emerald-500/10 dark:bg-emerald-500/15': techoFiscal.estado_semaforo === 'normal',
                'bg-amber-500/15 dark:bg-amber-500/20': techoFiscal.estado_semaforo === 'alerta',
                'bg-rose-500/15 dark:bg-rose-500/20': techoFiscal.estado_semaforo === 'excedido'
            }"></div>

        <!-- Encabezado de la Sección con Semáforo Visual de 3 Focos y Acciones -->
        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800/80 pb-4">
            
            <!-- Título y Descripción -->
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center border shadow-xs flex-shrink-0 transition-colors"
                    :class="{
                        'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30': techoFiscal.estado_semaforo === 'normal',
                        'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/30': techoFiscal.estado_semaforo === 'alerta',
                        'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/30': techoFiscal.estado_semaforo === 'excedido'
                    }">
                    <i data-lucide="shield-alert" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-white">
                            Monitor Fiscal & Semáforo Preventivo
                        </h3>
                        <!-- Insignia de Régimen -->
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500"></span>
                            <span x-text="techoFiscal.regimen + ' • Ley 822'"></span>
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Control preventivo de ingresos brutos mensuales frente al techo fiscal del régimen simplificado de Cuota Fija.
                    </p>
                </div>
            </div>

            <!-- Foco del Semáforo Analítico (3 luces) + Acciones Rápidas -->
            <div class="flex flex-wrap items-center gap-3 self-start lg:self-center">
                
                <!-- Cápsula Visual del Semáforo (Verde, Amarillo, Rojo) -->
                <div class="inline-flex items-center gap-2.5 px-3 py-1.5 rounded-2xl bg-slate-50 dark:bg-dark-950/80 border border-slate-200 dark:border-slate-800 shadow-xs">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mr-1">Semáforo:</span>
                    
                    <!-- Foco Verde: Seguro (< 75%) -->
                    <div class="flex items-center gap-1.5 group cursor-default" title="Normal: Ventas menores al 75% del techo mensual">
                        <span class="w-3.5 h-3.5 rounded-full transition-all duration-300"
                            :class="techoFiscal.estado_semaforo === 'normal' 
                                ? 'bg-emerald-500 shadow-md shadow-emerald-500/50 ring-2 ring-emerald-400/40 animate-pulse' 
                                : 'bg-slate-300 dark:bg-slate-700 opacity-30'"></span>
                        <span class="text-xs font-bold"
                            :class="techoFiscal.estado_semaforo === 'normal' ? 'text-emerald-600 dark:text-emerald-400' : 'hidden sm:inline text-slate-400 opacity-40'">
                            Seguro
                        </span>
                    </div>

                    <!-- Foco Amarillo: Alerta (75% - 99.99%) -->
                    <div class="flex items-center gap-1.5 group cursor-default" title="Alerta preventiva: 75% a 99.99% del techo mensual">
                        <span class="w-3.5 h-3.5 rounded-full transition-all duration-300"
                            :class="techoFiscal.estado_semaforo === 'alerta' 
                                ? 'bg-amber-500 shadow-md shadow-amber-500/50 ring-2 ring-amber-400/40 animate-pulse' 
                                : 'bg-slate-300 dark:bg-slate-700 opacity-30'"></span>
                        <span class="text-xs font-bold"
                            :class="techoFiscal.estado_semaforo === 'alerta' ? 'text-amber-600 dark:text-amber-400' : 'hidden sm:inline text-slate-400 opacity-40'">
                            Alerta
                        </span>
                    </div>

                    <!-- Foco Rojo: Excedido (>= 100%) -->
                    <div class="flex items-center gap-1.5 group cursor-default" title="Excedido: 100% o más del techo mensual">
                        <span class="w-3.5 h-3.5 rounded-full transition-all duration-300"
                            :class="techoFiscal.estado_semaforo === 'excedido' 
                                ? 'bg-rose-500 shadow-md shadow-rose-500/50 ring-2 ring-rose-400/40 animate-pulse' 
                                : 'bg-slate-300 dark:bg-slate-700 opacity-30'"></span>
                        <span class="text-xs font-bold"
                            :class="techoFiscal.estado_semaforo === 'excedido' ? 'text-rose-600 dark:text-rose-400' : 'hidden sm:inline text-slate-400 opacity-40'">
                            Excedido
                        </span>
                    </div>
                </div>

                <!-- Botón: Ir al Libro Diario Fiscal -->
                <button type="button" @click="currentTab = 'libro-diario'"
                    class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-brand-500/10 hover:bg-brand-500/20 text-brand-600 dark:text-brand-400 border border-brand-500/30 flex items-center gap-1.5 transition-all cursor-pointer">
                    <i data-lucide="book-open" class="w-3.5 h-3.5"></i>
                    <span>Libro Fiscal</span>
                </button>
            </div>
        </div>

        <!-- Banner Contextual Reactivo según Estado del Semáforo -->
        <div class="relative z-10 p-4 rounded-2xl border transition-all duration-300"
            :class="{
                'bg-emerald-50/70 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-500/30 text-emerald-900 dark:text-emerald-200': techoFiscal.estado_semaforo === 'normal',
                'bg-amber-50/70 dark:bg-amber-950/20 border-amber-200 dark:border-amber-500/30 text-amber-900 dark:text-amber-200': techoFiscal.estado_semaforo === 'alerta',
                'bg-rose-50/70 dark:bg-rose-950/20 border-rose-200 dark:border-rose-500/30 text-rose-900 dark:text-rose-200': techoFiscal.estado_semaforo === 'excedido'
            }">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
                <div class="flex items-start sm:items-center gap-3">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
                        :class="{
                            'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400': techoFiscal.estado_semaforo === 'normal',
                            'bg-amber-500/20 text-amber-600 dark:text-amber-400': techoFiscal.estado_semaforo === 'alerta',
                            'bg-rose-500/20 text-rose-600 dark:text-rose-400': techoFiscal.estado_semaforo === 'excedido'
                        }">
                        <template x-if="techoFiscal.estado_semaforo === 'normal'">
                            <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                        </template>
                        <template x-if="techoFiscal.estado_semaforo === 'alerta'">
                            <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                        </template>
                        <template x-if="techoFiscal.estado_semaforo === 'excedido'">
                            <i data-lucide="alert-octagon" class="w-4 h-4"></i>
                        </template>
                    </div>
                    <div>
                        <div class="font-bold text-xs sm:text-sm flex items-center gap-2">
                            <span x-show="techoFiscal.estado_semaforo === 'normal'">Nivel Seguro: Margen fiscal disponible</span>
                            <span x-show="techoFiscal.estado_semaforo === 'alerta'">Alerta Preventiva: Consumo superior al 75% del techo mensual</span>
                            <span x-show="techoFiscal.estado_semaforo === 'excedido'">Límite Fiscal Superado: Se ha superado el 100% de la cuota fija mensual</span>
                        </div>
                        <p class="text-[11px] opacity-90 mt-0.5"
                            x-text="techoFiscal.estado_semaforo === 'normal' 
                                ? 'Sus ventas brutas acumuladas del mes están dentro del límite legal establecido por Ley 822. Continúe facturando normalmente.'
                                : (techoFiscal.estado_semaforo === 'alerta'
                                    ? 'Ha alcanzado el ' + techoFiscal.porcentaje_consumido + '% del techo mensual (' + formatCurrency(techoFiscal.techo_mensual) + '). Planifique sus operaciones antes del cierre del mes.'
                                    : 'Ha facturado un ' + techoFiscal.porcentaje_consumido + '% del límite mensual. Consulte con su contador sobre la transición ordenada al Régimen General.')"></p>
                    </div>
                </div>

                <!-- Insignia de NO BLOQUEO DEL POS (Garantía operativa fundamental) -->
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white/80 dark:bg-dark-950/70 border border-slate-200 dark:border-slate-800 text-[11px] font-semibold text-slate-700 dark:text-slate-300 flex-shrink-0 self-start md:self-center shadow-xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>POS 100% Operativo (Informativo, sin bloqueos)</span>
                </div>
            </div>
        </div>

        <!-- Barra de Progreso Analítica con Marcadores de Umbrales (75% y 100%) -->
        <div class="relative z-10 p-4 rounded-2xl bg-slate-50/60 dark:bg-dark-900/60 border border-slate-200 dark:border-slate-800/90 space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 text-xs">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-700 dark:text-slate-300">Consumo Acumulado del Mes:</span>
                    <span class="font-mono font-black text-sm"
                        :class="{
                            'text-emerald-600 dark:text-emerald-400': techoFiscal.estado_semaforo === 'normal',
                            'text-amber-600 dark:text-amber-400': techoFiscal.estado_semaforo === 'alerta',
                            'text-rose-600 dark:text-rose-400': techoFiscal.estado_semaforo === 'excedido'
                        }"
                        x-text="techoFiscal.porcentaje_consumido + '%'"></span>
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                    <span>Base legal: Ley 822 de Concertación Tributaria (Nicaragua)</span>
                </div>
            </div>

            <!-- Contenedor de la Barra de Progreso y Marcadores -->
            <div class="relative pt-1 pb-4">
                <!-- Barra de Fondo -->
                <div class="w-full h-3.5 bg-slate-200 dark:bg-dark-950 rounded-full overflow-hidden relative shadow-inner">
                    <!-- Relleno Dinámico -->
                    <div class="h-full rounded-full transition-all duration-700 ease-out"
                        :class="{
                            'bg-gradient-to-r from-emerald-500 to-emerald-400': techoFiscal.estado_semaforo === 'normal',
                            'bg-gradient-to-r from-amber-500 to-amber-400': techoFiscal.estado_semaforo === 'alerta',
                            'bg-gradient-to-r from-rose-600 to-rose-500': techoFiscal.estado_semaforo === 'excedido'
                        }"
                        :style="`width: ${Math.min(100, techoFiscal.porcentaje_consumido)}%`">
                    </div>
                </div>

                <!-- Línea Marcador 75% (Umbral Preventivo) -->
                <div class="absolute top-0 bottom-0 left-[75%] -translate-x-1/2 flex flex-col items-center pointer-events-none">
                    <div class="w-0.5 h-5 bg-amber-500 dark:bg-amber-400 z-10 shadow-xs"></div>
                    <span class="text-[9px] font-bold text-amber-600 dark:text-amber-400 mt-0.5 tracking-tight whitespace-nowrap">
                        75% Alerta
                    </span>
                </div>

                <!-- Línea Marcador 100% (Límite Máximo) -->
                <div class="absolute top-0 bottom-0 left-[100%] -translate-x-full flex flex-col items-end pointer-events-none pr-0.5">
                    <div class="w-0.5 h-5 bg-rose-500 dark:bg-rose-400 z-10 shadow-xs"></div>
                    <span class="text-[9px] font-bold text-rose-600 dark:text-rose-400 mt-0.5 tracking-tight whitespace-nowrap">
                        100% Techo
                    </span>
                </div>
            </div>
        </div>

        <!-- 4 Tarjetas KPI: Ventas Mes, Techo Mensual, Saldo Disponible, Acumulado Anual -->
        <div class="relative z-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

            <!-- KPI 1: Ventas Facturadas del Mes -->
            <div class="p-4 rounded-2xl bg-white dark:bg-dark-900/80 border border-slate-200 dark:border-slate-800 shadow-xs hover:border-brand-500/30 transition-all">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Ventas del Mes</span>
                    <div class="w-8 h-8 rounded-lg bg-brand-500/10 text-brand-600 dark:text-brand-400 flex items-center justify-center">
                        <i data-lucide="receipt" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="text-xl sm:text-2xl font-display font-black text-slate-900 dark:text-white font-mono tracking-tight"
                    x-text="formatCurrency(techoFiscal.ventas_mes)"></div>
                <div class="mt-2 text-[11px] text-slate-500 dark:text-slate-400 flex items-center justify-between">
                    <span>Acumulado del mes actual</span>
                    <span class="font-bold text-slate-700 dark:text-slate-300" x-text="techoFiscal.porcentaje_consumido + '%'"></span>
                </div>
            </div>

            <!-- KPI 2: Techo Mensual Autorizado -->
            <div class="p-4 rounded-2xl bg-white dark:bg-dark-900/80 border border-slate-200 dark:border-slate-800 shadow-xs hover:border-slate-400/30 transition-all">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Techo Mensual</span>
                    <div class="w-8 h-8 rounded-lg bg-slate-500/10 text-slate-600 dark:text-slate-400 flex items-center justify-center">
                        <i data-lucide="scale" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="text-xl sm:text-2xl font-display font-black text-slate-900 dark:text-white font-mono tracking-tight"
                    x-text="formatCurrency(techoFiscal.techo_mensual)"></div>
                <div class="mt-2 text-[11px] text-slate-500 dark:text-slate-400 flex items-center justify-between">
                    <span>Límite Cuota Fija</span>
                    <span class="font-bold text-brand-600 dark:text-brand-400">Ley 822</span>
                </div>
            </div>

            <!-- KPI 3: Saldo / Margen Disponible -->
            <div class="p-4 rounded-2xl bg-white dark:bg-dark-900/80 border shadow-xs transition-all"
                :class="{
                    'border-emerald-200 dark:border-emerald-500/30 hover:border-emerald-500/50': techoFiscal.estado_semaforo === 'normal',
                    'border-amber-200 dark:border-amber-500/30 hover:border-amber-500/50': techoFiscal.estado_semaforo === 'alerta',
                    'border-rose-200 dark:border-rose-500/30 hover:border-rose-500/50': techoFiscal.estado_semaforo === 'excedido'
                }">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider"
                        :class="{
                            'text-emerald-700 dark:text-emerald-400': techoFiscal.estado_semaforo === 'normal',
                            'text-amber-700 dark:text-amber-400': techoFiscal.estado_semaforo === 'alerta',
                            'text-rose-700 dark:text-rose-400': techoFiscal.estado_semaforo === 'excedido'
                        }">
                        <span x-text="techoFiscal.estado_semaforo === 'excedido' ? 'Monto Excedido' : 'Margen Disponible'"></span>
                    </span>
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                        :class="{
                            'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400': techoFiscal.estado_semaforo === 'normal',
                            'bg-amber-500/10 text-amber-600 dark:text-amber-400': techoFiscal.estado_semaforo === 'alerta',
                            'bg-rose-500/10 text-rose-600 dark:text-rose-400': techoFiscal.estado_semaforo === 'excedido'
                        }">
                        <i data-lucide="wallet" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="text-xl sm:text-2xl font-display font-black font-mono tracking-tight"
                    :class="{
                        'text-emerald-600 dark:text-emerald-400': techoFiscal.estado_semaforo === 'normal',
                        'text-amber-600 dark:text-amber-400': techoFiscal.estado_semaforo === 'alerta',
                        'text-rose-600 dark:text-rose-400': techoFiscal.estado_semaforo === 'excedido'
                    }"
                    x-text="formatCurrency(techoFiscal.saldo_disponible)"></div>
                <div class="mt-2 text-[11px] text-slate-500 dark:text-slate-400 flex items-center justify-between">
                    <span x-text="techoFiscal.estado_semaforo === 'excedido' ? 'Superó el techo' : 'Capacidad restante'"></span>
                    <span class="font-bold text-slate-700 dark:text-slate-300"
                        x-text="techoFiscal.techo_mensual > 0 ? (100 - Math.min(100, techoFiscal.porcentaje_consumido)).toFixed(1) + '% libre' : '0% libre'"></span>
                </div>
            </div>

            <!-- KPI 4: Acumulado Anual -->
            <div class="p-4 rounded-2xl bg-white dark:bg-dark-900/80 border border-slate-200 dark:border-slate-800 shadow-xs hover:border-indigo-500/30 transition-all">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Acumulado Anual</span>
                    <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <i data-lucide="calendar-range" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="text-xl sm:text-2xl font-display font-black text-indigo-600 dark:text-indigo-400 font-mono tracking-tight"
                    x-text="formatCurrency(techoFiscal.ventas_anual_acumulado)"></div>
                <div class="mt-2 text-[11px] text-slate-500 dark:text-slate-400 flex items-center justify-between">
                    <span>Techo Anual (x12):</span>
                    <span class="font-bold text-slate-700 dark:text-slate-300 font-mono" x-text="formatCurrency(techoFiscal.techo_anual)"></span>
                </div>
            </div>

        </div>
    </div>

    <!-- 4. SECCIÓN DE GRÁFICOS INTERACTIVOS (INDICADOR DE VENTAS & TOP 5 ROTACIÓN) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- GRÁFICO 1: Indicador de Ventas por Días / Semanas -->
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

        <!-- GRÁFICO 2: Top 5 Productos (Toggle: Unidades Vendidas vs Mayor Utilidad) -->
        <div class="lg:col-span-4 glass-panel p-5 sm:p-6 rounded-3xl border border-slate-800 flex flex-col justify-between space-y-4">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center border"
                            :class="topProductosFiltro === 'utilidad' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20'">
                            <i x-show="topProductosFiltro === 'unidades'" data-lucide="flame" class="w-4 h-4"></i>
                            <i x-show="topProductosFiltro === 'utilidad'" data-lucide="trending-up" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="font-display font-bold text-base sm:text-lg text-white"
                                x-text="topProductosFiltro === 'utilidad' ? 'Top 5 Rentabilidad' : 'Top 5 Rotación'"></h3>
                            <p class="text-xs text-slate-400"
                                x-text="topProductosFiltro === 'utilidad' ? 'Por mayor utilidad / ganancia neta' : 'Por volumen de unidades vendidas'"></p>
                        </div>
                    </div>
                </div>

                <!-- Selector / Toggle: Por Unidades Vendidas vs Por Mayor Utilidad / Ganancia (C$) -->
                <div class="inline-flex p-1 rounded-xl bg-dark-900 border border-slate-800 text-xs w-full mb-3 shadow-xs">
                    <button type="button" @click="setTopProductosFiltro('unidades')"
                        :class="topProductosFiltro === 'unidades' ? 'bg-brand-600 text-white font-bold shadow-md shadow-brand-600/30' : 'text-slate-400 hover:text-white'"
                        class="flex-1 py-1.5 px-2 rounded-lg transition-all cursor-pointer text-center text-[11px] sm:text-xs">
                        Por Unidades Vendidas
                    </button>
                    <button type="button" @click="setTopProductosFiltro('utilidad')"
                        :class="topProductosFiltro === 'utilidad' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/30' : 'text-slate-400 hover:text-white'"
                        class="flex-1 py-1.5 px-2 rounded-lg transition-all cursor-pointer text-center text-[11px] sm:text-xs">
                        <span>Por Mayor Utilidad (<span x-text="empresa?.moneda_simbolo || 'C$'"></span>)</span>
                    </button>
                </div>

                <!-- Canvas Gráfico Doughnut -->
                <div class="relative w-full h-48 sm:h-52 mb-4">
                    <canvas id="chartTopProductos"></canvas>
                </div>

                <!-- Lista Visual del Ranking Top 5 (Alterna según selector) -->
                <div class="space-y-2.5">
                    <!-- 1. Vista por Unidades Vendidas -->
                    <template x-if="topProductosFiltro === 'unidades'">
                        <div class="space-y-2.5">
                            <template x-for="p in topProductosVendidos" :key="'vol-' + p.producto_id">
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
                    </template>

                    <!-- 2. Vista por Mayor Utilidad / Ganancia -->
                    <template x-if="topProductosFiltro === 'utilidad'">
                        <div class="space-y-2.5">
                            <template x-for="p in topRentabilidad" :key="'rent-' + p.producto_id">
                                <div class="p-2.5 rounded-xl bg-dark-900/60 border border-emerald-500/20 flex items-center justify-between gap-2 text-xs">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <!-- Medalla de Posición -->
                                        <span class="w-5 h-5 rounded-full flex items-center justify-center font-bold text-[10px] flex-shrink-0"
                                            :class="{
                                                'bg-emerald-400 text-slate-900 font-black': p.posicion === 1,
                                                'bg-teal-300 text-slate-900 font-bold': p.posicion === 2,
                                                'bg-cyan-600 text-white font-bold': p.posicion === 3,
                                                'bg-slate-800 text-slate-300': p.posicion > 3
                                            }"
                                            x-text="p.posicion"></span>
                                        <div class="truncate">
                                            <p class="font-semibold text-slate-200 truncate" x-text="p.nombre || p.nombre_producto"></p>
                                            <div class="flex items-center gap-1.5 text-[10px] text-slate-400">
                                                <span class="font-mono" x-text="p.codigo || p.codigo_producto"></span>
                                                <span>•</span>
                                                <span class="text-emerald-400 font-semibold" x-text="(p.margenPct || p.margen_pct) + '% margen'"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <span class="font-bold text-emerald-400 font-mono" x-text="'+ ' + formatCurrency(p.utilidadTotal || p.utilidad_total)"></span>
                                        <p class="text-[10px] text-slate-400" x-text="(p.cantidadVendida || 0) + ' uds. vendidas'"></p>
                                    </div>
                                </div>
                            </template>

                            <div x-show="topRentabilidad.length === 0" class="text-center py-6 text-xs text-slate-500">
                                <i data-lucide="trending-up" class="w-8 h-8 mx-auto mb-1 text-slate-600"></i>
                                Aún no se registran utilidades para generar el ranking
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. SECCIÓN INFERIOR: ALERTAS DE STOCK BAJO Y BAJA ROTACIÓN  -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- PANEL: ALERTAS DE STOCK BAJO Y CRÍTICO -->
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

                        <!-- Botón de Reposición Directa (Optimizado para alta visibilidad en Modo Oscuro y Claro) -->
                        <div class="flex items-center gap-2 self-end sm:self-center flex-shrink-0">
                            <button type="button" @click="openStockModal(p)"
                                class="px-3.5 py-1.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-semibold text-xs transition-all flex items-center gap-1.5 cursor-pointer shadow-md shadow-brand-600/30 border border-brand-500/40 active:scale-95 hover:scale-102">
                                <i data-lucide="plus" class="w-3.5 h-3.5 text-white"></i>
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

        <!-- PANEL: PRODUCTOS CON BAJA O NULA ROTACIÓN -->
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
                            <button x-show="canAccessPOS" x-cloak type="button" @click="currentTab = 'pos'"
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

    <!-- 6. SECCIÓN: ADVERTENCIA DE PÉRDIDAS POR MERMAS DEL MES (UBICACIÓN INFERIOR DEDICADA) -->
    <div class="glass-panel p-5 sm:p-6 rounded-3xl border border-slate-800 space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800/80 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center border border-rose-500/20 shadow-xs flex-shrink-0">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-white">Pérdidas por Mermas del Mes</h3>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border transition-all"
                            :class="perdidasMermasMes > 0 
                                ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800/60' 
                                : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/60'">
                            <span class="w-2 h-2 rounded-full" :class="perdidasMermasMes > 0 ? 'bg-rose-500 animate-pulse' : 'bg-emerald-500'"></span>
                            <span x-text="perdidasMermasMes > 0 ? 'Alerta Activa' : 'Sin Pérdidas en el Período'"></span>
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Monitoreo financiero del capital descartado por deterioro, vencimiento, rotura o descarte
                    </p>
                </div>
            </div>
        </div>

        <!-- Contenido en Grid Balanceado: KPI a la izquierda y Listado a la derecha -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- TARJETA RESUMEN FINANCIERO (KPI) - Alto Contraste en Modo Claro y Oscuro -->
            <div class="lg:col-span-5 xl:col-span-4 rounded-2xl p-5 bg-rose-50/90 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900/60 flex flex-col justify-between space-y-4">
                <div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-rose-800 dark:text-rose-300 flex items-center gap-1.5">
                            <i data-lucide="trending-down" class="w-4 h-4 text-rose-600 dark:text-rose-400"></i>
                            Costo Total Descartado
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 dark:bg-rose-900/50 text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-800"
                            x-text="mermasDelMes.length + ' registro(s)'"></span>
                    </div>

                    <!-- Monto Gran Total de Pérdida -->
                    <div class="mt-3">
                        <div class="text-3xl sm:text-4xl font-display font-black text-rose-600 dark:text-rose-400 font-mono tracking-tight"
                            x-text="formatCurrency(perdidasMermasMes)"></div>
                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">
                            Dinero no recuperable acumulado en el mes en curso
                        </p>
                    </div>
                </div>

                <!-- Barra de Severidad y Nivel de Impacto -->
                <div class="pt-3 border-t border-rose-200/80 dark:border-rose-900/40 space-y-2">
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-600 dark:text-slate-400 font-medium">Nivel de Impacto:</span>
                        <strong :class="perdidasMermasMes > 0 ? 'text-rose-700 dark:text-rose-400 font-bold' : 'text-emerald-700 dark:text-emerald-400 font-bold'"
                            x-text="perdidasMermasMes > 0 ? 'Pérdida Financiera en Curso' : 'Inventario Íntegro'"></strong>
                    </div>
                    <div class="w-full h-2 bg-rose-200/60 dark:bg-dark-950 rounded-full overflow-hidden">
                        <div class="h-full bg-rose-500 rounded-full transition-all duration-500"
                            :style="`width: ${perdidasMermasMes > 0 ? '100' : '0'}%`"></div>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400"
                        x-text="perdidasMermasMes > 0 ? 'Se recomienda auditar los lotes y procesos de manipulación.' : 'No se han registrado bajas por descarte en el período.'"></p>
                </div>
            </div>

            <!-- DESGLOSE Y LISTADO DE MERMAS -->
            <div class="lg:col-span-7 xl:col-span-8 space-y-3 flex flex-col justify-between">
                <div class="flex items-center justify-between text-xs font-semibold text-slate-700 dark:text-slate-300 pb-1">
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="list" class="w-3.5 h-3.5 text-rose-500"></i>
                        Detalle de Salidas por Merma del Mes
                    </span>
                    <span class="text-slate-500 dark:text-slate-400 font-mono text-[11px]" x-show="mermasDelMes.length > 0"
                        x-text="'Mostrando ' + mermasDelMes.length + ' descarga(s)'"></span>
                </div>

                <div class="space-y-2.5 max-h-72 overflow-y-auto pr-1">
                    <template x-for="m in mermasDelMes" :key="m.movimiento_inventario_id || m.movimiento_id || (m.id_movimiento + '-' + m.created_at)">
                        <div class="p-3.5 rounded-2xl bg-slate-50/70 dark:bg-dark-900/70 border border-slate-200 dark:border-slate-800 hover:border-rose-300 dark:hover:border-rose-500/40 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                   <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border bg-sky-50 dark:bg-sky-500/20 text-sky-800 dark:text-white border-sky-200 dark:border-sky-500/30"
                                         x-text="m.tipo_merma || 'Salida por Merma'">
                                    </span>
                                   <span class="text-xs text-slate-500 dark:text-slate-400 font-mono" x-text="m.producto?.codigo_producto || m.codigo_producto || ''"></span>
                                </div>
                                <h5 class="text-sm font-bold text-slate-900 dark:text-white mt-1.5 truncate" x-text="m.producto?.nombre_producto || m.nombre_producto || 'Producto #' + m.id_producto"></h5>
                                <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-0.5 text-xs text-slate-600 dark:text-slate-400">
                                    <span>Descargado: <strong class="text-slate-900 dark:text-slate-200 font-semibold" x-text="(m.cantidad_movimiento || m.cantidad_movimimiento || m.cantidad || 0) + ' uds.'"></strong></span>
                                    <span>Costo Unit.: <span class="font-mono text-slate-700 dark:text-slate-300" x-text="formatCurrency(m.costo_unitario)"></span></span>
                                    <span x-show="m.fecha_movimiento || m.created_at">Fecha: <span x-text="formatDate(m.fecha_movimiento || m.created_at)"></span></span>
                                </div>
                            </div>

                            <div class="text-right flex-shrink-0 self-end sm:self-center pl-2 sm:border-l sm:border-slate-200 sm:dark:border-slate-800">
                                <span class="text-[10px] text-slate-500 dark:text-slate-400 uppercase tracking-wider block font-semibold">Costo Pérdida</span>
                                <span class="font-display font-black text-base text-rose-600 dark:text-rose-400 font-mono"
                                    x-text="'- ' + formatCurrency(m.costo_total_perdida)"></span>
                            </div>
                        </div>
                    </template>

                    <!-- Estado cuando hay pérdidas en el backend pero no hay lista en memoria -->
                    <div x-show="mermasDelMes.length === 0 && perdidasMermasMes > 0"
                        class="p-6 text-center rounded-2xl bg-rose-50/50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900/40 text-slate-600 dark:text-slate-300 space-y-2">
                        <i data-lucide="alert-triangle" class="w-8 h-8 mx-auto text-rose-500"></i>
                        <p class="text-xs font-semibold text-rose-700 dark:text-rose-400">Pérdidas acumuladas registradas en el sistema este mes</p>
                        <button type="button" @click="currentTab = 'inventario'" class="text-xs text-brand-600 dark:text-brand-400 hover:underline font-semibold cursor-pointer">
                            Consultar detalle de movimientos en el Kardex &rarr;
                        </button>
                    </div>

                    <!-- Estado Vacío Saludable: Cuando no hay mermas este mes -->
                    <div x-show="mermasDelMes.length === 0 && (!perdidasMermasMes || perdidasMermasMes <= 0)"
                        class="p-8 text-center rounded-2xl bg-emerald-50/60 dark:bg-emerald-500/5 border border-emerald-200 dark:border-emerald-500/20 text-emerald-800 dark:text-emerald-400 space-y-2">
                        <i data-lucide="shield-check" class="w-9 h-9 mx-auto text-emerald-600 dark:text-emerald-400"></i>
                        <p class="text-sm font-bold">¡Sin pérdidas por mermas este mes!</p>
                        <p class="text-xs text-slate-600 dark:text-slate-400 max-w-md mx-auto">No se registran salidas por deterioro, rotura o descarte en el inventario durante el período actual.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 6. ÚLTIMAS VENTAS EN TIEMPO REAL -->
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
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="text-xs uppercase bg-dark-900/80 text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="py-2.5 px-3 font-semibold">Código</th>
                        <th class="py-2.5 px-3 font-semibold">Cliente</th>
                        <th class="py-2.5 px-3 font-semibold">Método de Pago</th>
                        <th class="py-2.5 px-3 font-semibold">Fecha y Hora</th>
                        <th class="py-2.5 px-3 text-right font-semibold">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <template x-for="v in ultimasVentas" :key="v.venta_id || v.codigo_venta">
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="py-2.5 px-3 font-semibold text-brand-300 dark:text-indigo-400 font-mono" x-text="v.codigo_venta"></td>
                            <td class="py-2.5 px-3 text-slate-200" x-text="v.cliente_nombre"></td>
                            <td class="py-2.5 px-3">
                                <span class="px-2 py-0.5 text-xs rounded-md font-medium inline-flex items-center gap-1"
                                    :class="{
                                        'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20': (v.metodo_pago || '').toLowerCase() === 'efectivo',
                                        'bg-sky-500/10 text-sky-400 border border-sky-500/20': (v.metodo_pago || '').toLowerCase() === 'transferencia',
                                        'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20': ['tarjeta', 'debito', 'credito'].includes((v.metodo_pago || '').toLowerCase())
                                    }">
                                    <i data-lucide="circle-dot" class="w-2.5 h-2.5"></i>
                                    <span x-text="v.metodo_pago"></span>
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-xs text-slate-400" x-text="formatDate(v.fecha_hora_venta)"></td>
                            <td class="py-2.5 px-3 text-right font-bold text-white font-mono" x-text="formatCurrency(v.total_venta)"></td>
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