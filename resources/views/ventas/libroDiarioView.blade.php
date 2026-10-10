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
    x-init="$watch('currentTab', v => { if (v === 'libro-diario') { fetchLibroDiario(); $nextTick(() => { if (window.lucide) window.lucide.createIcons(); }); } }); $watch('libroDiarioData', () => $nextTick(() => { if (window.lucide) window.lucide.createIcons(); }))"
    class="space-y-6">

    <!-- 1. PANEL PRINCIPAL DE CABECERA Y FILTROS -->
    <div class="glass-panel p-5 sm:p-6 rounded-3xl space-y-5 border border-slate-200 dark:border-slate-800 shadow-sm no-print">

        <!-- Fila Superior: Título, Insignia y Botones de Acción -->
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 pb-4 border-b border-slate-200 dark:border-slate-800/80">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-brand-600 to-indigo-500 text-white flex items-center justify-center shadow-md shadow-brand-500/20 flex-shrink-0">
                    <i data-lucide="book-open" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-display font-black text-lg sm:text-xl text-slate-900 dark:text-white">
                            Libro Mensual de Ventas
                        </h2>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20">
                            Fiscal DGI (Cuota Fija)
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Registro oficial de ventas diarias agrupadas por fecha, cantidad de tickets y rango de comprobantes.
                    </p>
                </div>
            </div>

            <!-- Botones de Acción: Imprimir, Exportar y Accesos -->
            <div class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto justify-start lg:justify-end">
                <!-- Botón: Imprimir Libro Diario Formal -->
                <button type="button" @click="printLibroDiario()"
                    class="px-4 py-2.5 bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 text-white rounded-xl text-xs sm:text-sm font-bold shadow-md shadow-brand-600/30 flex items-center gap-2 transition-all cursor-pointer active:scale-95">
                    <i data-lucide="printer" class="w-4 h-4 text-white"></i>
                    <span>Imprimir Libro Diario</span>
                </button>
            </div>
        </div>

        <!-- Fila de Controles: Selector de Mes, Año y Datos de Membrete -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4 items-end">

            <!-- 1. Selector de Mes -->
            <div class="lg:col-span-3 space-y-1.5">
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
            <div class="lg:col-span-3 space-y-1.5">
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

            <!-- 3. Atajos Rápidos de Período -->
            <div class="lg:col-span-3 space-y-1.5">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                    <span>Atajos Rápidos:</span>
                </label>
                <div class="flex items-center gap-2">
                    <button type="button" @click="setLibroDiarioMesActual()"
                        class="flex-1 px-3 py-2 rounded-xl text-xs font-bold transition-all border cursor-pointer text-center"
                        :class="(libroDiarioMes === (new Date().getMonth() + 1) && libroDiarioAnio === new Date().getFullYear())
                            ? 'bg-brand-600 text-white border-brand-500 shadow-xs'
                            : 'bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700'">
                        Mes Actual
                    </button>
                    <button type="button" @click="setLibroDiarioMesAnterior()"
                        class="flex-1 px-3 py-2 rounded-xl text-xs font-bold transition-all border cursor-pointer text-center bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700">
                        Mes Anterior
                    </button>
                </div>
            </div>

            <!-- 4. Membrete / Datos Fiscales del Negocio -->
            <div class="lg:col-span-3 p-2.5 rounded-xl bg-slate-50 dark:bg-dark-900/60 border border-slate-200 dark:border-slate-800 text-[11px] space-y-0.5">
                <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                    <span class="font-bold truncate" x-text="empresa?.nombre_comercial || 'Mi Negocio'"></span>
                    <span class="font-mono text-[10px] text-brand-600 dark:text-brand-400 font-bold" x-text="empresa?.numero_ruc ? 'RUC: ' + empresa.numero_ruc : 'RUC: Pendiente'"></span>
                </div>
                <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate" x-text="empresa?.razon_social || 'Contribuyente Régimen Cuota Fija'"></p>
                <p class="text-[10px] text-slate-400 font-mono" x-show="libroDiarioEmisionFecha" x-text="'Emitido: ' + libroDiarioEmisionFecha"></p>
            </div>
        </div>
    </div>

    <!-- 2. TARJETAS DE MÉTRICAS / KPIS FISCALES -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 no-print">

        <!-- KPI 1: Total Acumulado del Mes -->
        <div class="glass-panel p-5 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between shadow-xs">
            <div class="space-y-1">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Acumulado del Mes</p>
                <div class="text-2xl sm:text-3xl font-display font-black text-emerald-600 dark:text-emerald-400 font-mono tracking-tight"
                    x-text="formatCurrency(libroDiarioData?.total_acumulado_mes || 0)"></div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400"
                    x-text="'Período: ' + nombreMesSeleccionado + ' ' + libroDiarioAnio"></p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center border border-emerald-500/20 flex-shrink-0">
                <i data-lucide="banknote" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- KPI 2: Total de Tickets Emitidos -->
        <div class="glass-panel p-5 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between shadow-xs">
            <div class="space-y-1">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Tickets / Facturas</p>
                <div class="text-2xl sm:text-3xl font-display font-black text-brand-600 dark:text-brand-400 font-mono tracking-tight"
                    x-text="(libroDiarioData?.total_transacciones_mes || 0) + ' transacciones'"></div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">
                    Comprobantes válidos del régimen simplificado
                </p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-brand-500/10 text-brand-600 dark:text-brand-400 flex items-center justify-center border border-brand-500/20 flex-shrink-0">
                <i data-lucide="receipt" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- KPI 3: Días con Actividad y Promedio Diario -->
        <div class="glass-panel p-5 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between shadow-xs sm:col-span-2 lg:col-span-1">
            <div class="space-y-1">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Días Operados</p>
                <div class="text-2xl sm:text-3xl font-display font-black text-indigo-600 dark:text-indigo-400 font-mono tracking-tight"
                    x-text="(libroDiarioData?.dias?.length || 0) + ' días con ventas'"></div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400"
                    x-text="(libroDiarioData?.dias?.length || 0) > 0 ? 'Promedio diario: ' + formatCurrency((Number(libroDiarioData?.total_acumulado_mes || 0) / (libroDiarioData?.dias?.length || 1))) : 'Sin movimientos registrados'"></p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center border border-indigo-500/20 flex-shrink-0">
                <i data-lucide="calendar-check" class="w-6 h-6"></i>
            </div>
        </div>
    </div>

    <!-- 3. TABLA PRINCIPAL DEL LIBRO DIARIO DE VENTAS (PANTALLA) -->
    <div class="glass-panel rounded-3xl overflow-hidden border border-slate-200 dark:border-slate-800 shadow-sm no-print">
        <div class="p-4 sm:p-5 border-b border-slate-200 dark:border-slate-800/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-slate-50/50 dark:bg-dark-900/50">
            <div>
                <h3 class="font-display font-bold text-base text-slate-900 dark:text-white flex items-center gap-2">
                    <span>Detalle Mensual de Ventas</span>
                    <span class="text-xs font-mono font-bold px-2 py-0.5 rounded-full bg-brand-500/10 text-brand-600 dark:text-brand-400 border border-brand-500/20"
                        x-text="nombreMesSeleccionado + ' ' + libroDiarioAnio"></span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Días en los que se emitieron facturas comerciales o tickets de venta
                </p>
            </div>

            <!-- Botón para alternar desglose de facturas individuales -->
            <button type="button" @click="showLibroComprobantesDetalle = !showLibroComprobantesDetalle; $nextTick(() => { if (window.lucide) window.lucide.createIcons(); })"
                class="px-3 py-1.5 rounded-xl border text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer"
                :class="showLibroComprobantesDetalle 
                    ? 'bg-brand-50 dark:bg-brand-950/40 text-brand-700 dark:text-brand-300 border-brand-300 dark:border-brand-700' 
                    : 'bg-white dark:bg-dark-900 text-slate-600 dark:text-slate-300 border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800'">
                <i data-lucide="list" class="w-3.5 h-3.5"></i>
                <span x-text="showLibroComprobantesDetalle ? 'Ocultar Facturas Individuales' : 'Ver Facturas Individuales (' + libroDiarioVentasDetalle.length + ')'"></span>
            </button>
        </div>

        <!-- Indicador de carga -->
        <div x-show="libroDiarioLoading" class="py-16 text-center text-slate-400 space-y-2">
            <span class="inline-block animate-spin w-7 h-7 border-3 border-brand-500 border-t-transparent rounded-full"></span>
            <p class="text-xs font-semibold">Consolidando libro diario del período fiscal...</p>
        </div>

        <!-- Contenedor con Scroll Horizontal de la Tabla -->
        <div x-show="!libroDiarioLoading" class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="text-xs uppercase bg-slate-100/80 dark:bg-dark-900 text-slate-600 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 font-bold">
                    <tr>
                        <th class="py-3 px-4">Fecha</th>
                        <th class="py-3 px-4 text-center">Cantidad de Tickets</th>
                        <th class="py-3 px-4">Rango de Comprobantes</th>
                        <th class="py-3 px-4 text-right">
                            <span x-text="'Total Diario (' + (empresa?.moneda_simbolo || 'C$') + ')'">Total Diario (C$)</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 font-mono text-xs">
                    <template x-for="dia in (libroDiarioData?.dias || [])" :key="dia.fecha">
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                            <!-- Columna 1: Fecha -->
                            <td class="py-3 px-4 font-sans font-semibold text-slate-900 dark:text-white">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-lg bg-slate-200/60 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 text-xs">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                                    </div>
                                    <div>
                                        <span class="font-bold block" x-text="formatDate(dia.fecha)"></span>
                                        <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono" x-text="dia.fecha"></span>
                                    </div>
                                </div>
                            </td>

                            <!-- Columna 2: Cantidad de Tickets -->
                            <td class="py-3 px-4 text-center">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-brand-50 dark:bg-brand-500/10 text-brand-700 dark:text-brand-300 border border-brand-200 dark:border-brand-500/20">
                                    <i data-lucide="receipt" class="w-3 h-3"></i>
                                    <span x-text="dia.cantidad_transacciones + ' ticket(s)'"></span>
                                </span>
                            </td>

                            <!-- Columna 3: Rango de Comprobantes (Inicial y Final) -->
                            <td class="py-3 px-4">
                                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-dark-900/80 border border-slate-200 dark:border-slate-800">
                                    <span class="font-bold text-slate-800 dark:text-slate-200" x-text="dia.comprobante_inicial"></span>
                                    <span class="text-slate-400 text-[10px]" x-show="dia.comprobante_inicial !== dia.comprobante_final">al</span>
                                    <span class="font-bold text-slate-800 dark:text-slate-200" x-show="dia.comprobante_inicial !== dia.comprobante_final" x-text="dia.comprobante_final"></span>
                                </div>
                            </td>

                            <!-- Columna 4: Total Diario (C$) -->
                            <td class="py-3 px-4 text-right font-display font-black text-sm text-slate-900 dark:text-emerald-400">
                                <span x-text="formatCurrency(dia.total_dia)"></span>
                            </td>
                        </tr>
                    </template>

                    <!-- Estado Vacío cuando no hay ventas en el período -->
                    <tr x-show="!libroDiarioData?.dias || libroDiarioData.dias.length === 0">
                        <td colspan="4" class="py-12 text-center text-slate-500 dark:text-slate-400 font-sans">
                            <i data-lucide="calendar-x" class="w-10 h-10 mx-auto mb-2 text-slate-400 opacity-60"></i>
                            <p class="font-bold text-sm text-slate-700 dark:text-slate-300">No se registraron ventas en este período</p>
                            <p class="text-xs text-slate-500 mt-0.5">Seleccione otro mes o año para consultar los registros fiscales correspondientes.</p>
                        </td>
                    </tr>
                </tbody>

                <!-- FILA DE TOTALES AL PIE DE LA TABLA -->
                <tfoot class="bg-slate-100/90 dark:bg-dark-900/95 border-t-2 border-slate-300 dark:border-slate-700 font-bold">
                    <tr>
                        <td class="py-3.5 px-4 font-sans text-xs uppercase tracking-wider text-slate-900 dark:text-white">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                <span>Total Acumulado del Mes</span>
                                <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400"
                                    x-text="'(' + (empresa?.moneda_simbolo || 'C$') + ')'">(C$)</span>
                            </div>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-brand-600 text-white shadow-xs"
                                x-text="(libroDiarioData?.total_transacciones_mes || 0) + ' tickets'"></span>
                        </td>
                        <td class="py-3.5 px-4 text-xs font-mono text-slate-600 dark:text-slate-400">
                            <template x-if="libroDiarioData?.dias && libroDiarioData.dias.length > 0">
                                <span x-text="'Desde ' + libroDiarioData.dias[0].comprobante_inicial + ' hasta ' + libroDiarioData.dias[libroDiarioData.dias.length - 1].comprobante_final"></span>
                            </template>
                            <template x-if="!libroDiarioData?.dias || libroDiarioData.dias.length === 0">
                                <span>---</span>
                            </template>
                        </td>
                        <td class="py-3.5 px-4 text-right font-display font-black text-base sm:text-lg text-emerald-600 dark:text-emerald-400 font-mono">
                            <span x-text="formatCurrency(libroDiarioData?.total_acumulado_mes || 0)"></span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- 4. SECCIÓN DESPLEGABLE: FACTURAS INDIVIDUALES EMITIDAS EN EL MES -->
    <div x-show="showLibroComprobantesDetalle" x-transition.duration.300ms class="glass-panel p-5 rounded-3xl border border-slate-200 dark:border-slate-800 space-y-4 no-print shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-brand-500/10 text-brand-500 flex items-center justify-center">
                    <i data-lucide="list-checks" class="w-4 h-4"></i>
                </div>
                <div>
                    <h4 class="font-display font-bold text-sm text-slate-900 dark:text-white">
                        Comprobantes / Facturas Emitidas en el Período
                    </h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Inspección detallada de cada número de factura con fecha y hora de emisión para auditoría
                    </p>
                </div>
            </div>
            <span class="text-xs font-mono font-bold text-slate-500 dark:text-slate-400"
                x-text="libroDiarioVentasDetalle.length + ' comprobantes'"></span>
        </div>

        <div class="overflow-x-auto max-h-80 overflow-y-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="text-[11px] uppercase bg-slate-50 dark:bg-dark-900 text-slate-500 sticky top-0 border-b border-slate-200 dark:border-slate-800 font-bold">
                    <tr>
                        <th class="py-2.5 px-3">N° Factura</th>
                        <th class="py-2.5 px-3">Fecha y Hora</th>
                        <th class="py-2.5 px-3">Cliente</th>
                        <th class="py-2.5 px-3">Método de Pago</th>
                        <th class="py-2.5 px-3 text-right">Total Facturado</th>
                        <th class="py-2.5 px-3 text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800 font-mono">
                    <template x-for="v in libroDiarioVentasDetalle" :key="v.venta_id">
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-2 px-3 font-bold text-brand-600 dark:text-brand-400" x-text="v.codigo_venta"></td>
                            <td class="py-2 px-3 text-slate-600 dark:text-slate-300" x-text="formatDate(v.fecha_hora_venta)"></td>
                            <td class="py-2 px-3 font-sans text-slate-800 dark:text-slate-200" x-text="v.cliente_nombre || 'Consumidor Final'"></td>
                            <td class="py-2 px-3 capitalize font-sans text-slate-600 dark:text-slate-400" x-text="v.metodo_pago || 'Efectivo'"></td>
                            <td class="py-2 px-3 text-right font-bold text-slate-900 dark:text-emerald-400" x-text="formatCurrency(v.total_venta)"></td>
                            <td class="py-2 px-3 text-center">
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
                        <td colspan="6" class="py-6 text-center text-slate-500 font-sans">
                            No hay facturas registradas en este mes.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- =========================================================================
         5. ÁREA IMPRIMIBLE FORMAL DEL LIBRO DIARIO (@media print / AUDITORÍA FISCAL DGI)
         ========================================================================= -->
    <div id="printableLibroDiarioArea" class="hidden print:block bg-white text-black font-sans p-6">

        <!-- MEMBRETE DEL COMERCIO Y DATOS FISCALES -->
        <div class="border-b-2 border-black pb-4 mb-4">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-xl font-black uppercase tracking-tight text-black"
                        x-text="empresa?.nombre_comercial || 'SISTEMA COMERCIAL'"></h1>
                    <h2 class="text-sm font-bold text-black"
                        x-show="empresa?.razon_social"
                        x-text="empresa?.razon_social"></h2>
                    <p class="text-xs font-bold text-black mt-0.5"
                        x-show="empresa?.numero_ruc"
                        x-text="'RUC: ' + empresa?.numero_ruc"></p>
                    <p class="text-xs text-black"
                        x-show="empresa?.direccion_fisica"
                        x-text="empresa?.direccion_fisica"></p>
                    <p class="text-xs text-black"
                        x-show="empresa?.telefono_contacto || empresa?.correo_contacto">
                        <span x-show="empresa?.telefono_contacto" x-text="'Tel: ' + empresa.telefono_contacto"></span>
                        <span x-show="empresa?.telefono_contacto && empresa?.correo_contacto"> | </span>
                        <span x-show="empresa?.correo_contacto" x-text="empresa.correo_contacto"></span>
                    </p>
                </div>

                <div class="text-right space-y-1">
                    <div class="inline-block border border-black px-3 py-1 rounded bg-neutral-100 font-bold text-xs uppercase">
                        LIBRO DIARIO DE VENTAS
                    </div>
                    <p class="text-[11px] font-bold text-black">
                        RÉGIMEN SIMPLIFICADO CUOTA FIJA
                    </p>
                    <p class="text-[10px] text-black">
                        LEY N° 822 / DGI NICARAGUA
                    </p>
                </div>
            </div>
        </div>

        <!-- METADATOS DEL REPORTE FISCAL -->
        <div class="grid grid-cols-2 gap-4 text-xs mb-4 p-3 bg-neutral-50 border border-black rounded">
            <div>
                <p><strong>Período Fiscal Auditado:</strong> <span class="font-bold uppercase" x-text="nombreMesSeleccionado + ' ' + libroDiarioAnio"></span></p>
                <p><strong>Fecha de Emisión del Libro:</strong> <span x-text="libroDiarioEmisionFecha"></span></p>
                <p><strong>Total de Días Operados:</strong> <span x-text="(libroDiarioData?.dias?.length || 0) + ' días'"></span></p>
            </div>
            <div class="text-right">
                <p><strong>Moneda Oficial:</strong> <span x-text="(empresa?.moneda_simbolo === '$' || empresa?.moneda_simbolo === 'USD') ? 'Dólares ($)' : 'Córdobas (C$)'">Córdobas (C$)</span></p>
                <p><strong>Total de Tickets Emitidos:</strong> <span class="font-bold" x-text="(libroDiarioData?.total_transacciones_mes || 0) + ' comprobantes'"></span></p>
                <p><strong>Rango Mensual:</strong> 
                    <span class="font-mono" x-show="libroDiarioData?.dias && libroDiarioData.dias.length > 0"
                        x-text="libroDiarioData.dias[0].comprobante_inicial + ' al ' + libroDiarioData.dias[libroDiarioData.dias.length - 1].comprobante_final"></span>
                </p>
            </div>
        </div>

        <!-- TABLA IMPRESA OFICIAL -->
        <table class="w-full text-left text-xs border border-black mb-6">
            <thead>
                <tr class="bg-neutral-100 border-b border-black text-black font-bold uppercase text-[10px]">
                    <th class="py-2 px-3 border-r border-black">Fecha</th>
                    <th class="py-2 px-3 border-r border-black text-center">Cantidad Tickets</th>
                    <th class="py-2 px-3 border-r border-black">Rango de Comprobantes</th>
                    <th class="py-2 px-3 text-right" x-text="'Total Diario (' + (empresa?.moneda_simbolo || 'C$') + ')'">Total Diario (C$)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-black font-mono text-[11px]">
                <template x-for="d in (libroDiarioData?.dias || [])" :key="d.fecha">
                    <tr class="border-b border-black">
                        <td class="py-1.5 px-3 border-r border-black font-sans font-medium" x-text="formatDate(d.fecha) + ' (' + d.fecha + ')'"></td>
                        <td class="py-1.5 px-3 border-r border-black text-center" x-text="d.cantidad_transacciones"></td>
                        <td class="py-1.5 px-3 border-r border-black" x-text="d.comprobante_inicial === d.comprobante_final ? d.comprobante_inicial : d.comprobante_inicial + ' al ' + d.comprobante_final"></td>
                        <td class="py-1.5 px-3 text-right font-bold" x-text="formatCurrency(d.total_dia)"></td>
                    </tr>
                </template>
            </tbody>
            <tfoot>
                <tr class="bg-neutral-100 border-t-2 border-black font-bold text-xs">
                    <td class="py-2.5 px-3 border-r border-black uppercase font-black"
                        x-text="'TOTAL ACUMULADO DEL MES (' + (empresa?.moneda_simbolo || 'C$') + ')'">
                        TOTAL ACUMULADO DEL MES
                    </td>
                    <td class="py-2.5 px-3 border-r border-black text-center font-black"
                        x-text="(libroDiarioData?.total_transacciones_mes || 0) + ' tickets'"></td>
                    <td class="py-2.5 px-3 border-r border-black text-[10px] font-sans">
                        Registro cerrado mensual
                    </td>
                    <td class="py-2.5 px-3 text-right font-black text-sm font-mono"
                        x-text="formatCurrency(libroDiarioData?.total_acumulado_mes || 0)"></td>
                </tr>
            </tfoot>
        </table>

        <!-- DESGLOSE DE COMPROBANTES CON NÚMERO Y FECHA PARA AUDITORÍA -->
        <template x-if="libroDiarioVentasDetalle.length > 0">
            <div class="mb-6">
                <h3 class="text-xs font-bold uppercase tracking-wider border-b border-black pb-1 mb-2">
                    Relación de Facturas y Comprobantes Emitidos en el Mes
                </h3>
                <table class="w-full text-left text-[10px] font-mono border border-black">
                    <thead>
                        <tr class="bg-neutral-100 border-b border-black font-bold uppercase">
                            <th class="py-1 px-2 border-r border-black">Factura N°</th>
                            <th class="py-1 px-2 border-r border-black">Fecha y Hora</th>
                            <th class="py-1 px-2 border-r border-black">Cliente</th>
                            <th class="py-1 px-2 border-r border-black">Forma de Pago</th>
                            <th class="py-1 px-2 text-right">Monto (C$)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-300">
                        <template x-for="v in libroDiarioVentasDetalle" :key="v.venta_id">
                            <tr>
                                <td class="py-1 px-2 border-r border-neutral-300 font-bold" x-text="v.codigo_venta"></td>
                                <td class="py-1 px-2 border-r border-neutral-300" x-text="formatDate(v.fecha_hora_venta)"></td>
                                <td class="py-1 px-2 border-r border-neutral-300 font-sans truncate max-w-[150px]" x-text="v.cliente_nombre || 'Consumidor Final'"></td>
                                <td class="py-1 px-2 border-r border-neutral-300 font-sans capitalize" x-text="v.metodo_pago || 'Efectivo'"></td>
                                <td class="py-1 px-2 text-right font-bold" x-text="formatCurrency(v.total_venta)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>

        <!-- PIE FORMAL Y FIRMAS DE RESPONSABILIDAD -->
        <div class="flex justify-center items-center pt-14 mt-10 border-t border-dashed border-black text-xs text-center">
            <div class="space-y-1.5 w-64">
                <div class="border-b border-black w-56 mx-auto mb-2"></div>
                <p class="font-bold text-black" x-text="currentUser?.nombre_apellido || 'Responsable de Caja / Contador'"></p>
                <p class="text-[10px] text-neutral-600">Elaborado por (Responsable)</p>
            </div>
        </div>

        <div class="mt-6 text-center text-[9px] text-neutral-500 font-mono">
            Documento emitido mediante FacturaStock Pro • Fecha y Hora de Impresión: <span x-text="libroDiarioEmisionFecha"></span>
        </div>
    </div>

</div>
