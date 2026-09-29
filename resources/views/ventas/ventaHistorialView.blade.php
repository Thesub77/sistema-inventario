{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Listado e Historial de Ventas (RF-21)
    Archivo: resources/views/ventas/ventaHistorialView.blade.php
    Propósito: Permite la consulta y filtrado de ventas realizadas según criterios
               de fecha (rango Desde / Hasta), cajero/usuario y número de comprobante/cliente.
    Controlador asociado: App\Http\Controllers\VentaController
    Modelo: App\Models\Venta
    Integración: Incluido en welcome.blade.php mediante @include('ventas.ventaHistorialView') 
    =============================================================================
--}}

<div x-show="currentTab === 'ventas'" x-cloak
    x-init="$watch('currentTab', v => { if (v === 'ventas') $nextTick(() => { if (window.lucide) window.lucide.createIcons(); }); })"
    class="space-y-4">

    <!-- Panel Principal de Filtros de Consulta (RF-21) -->
    <div class="glass-panel p-4 sm:p-5 rounded-2xl space-y-4 shadow-sm border border-slate-200 dark:border-slate-800">
        <!-- Fila Superior: Título y Botón Nueva Venta -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800/80">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-brand-500/10 text-brand-600 dark:text-brand-400 flex items-center justify-center border border-brand-500/20">
                    <i data-lucide="receipt" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-display font-bold text-base text-slate-900 dark:text-white flex items-center gap-2">
                        <span>Historial y Consulta de Ventas</span>
                        <span class="text-xs font-mono font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700"
                            x-text="filteredVentas.length + (filteredVentas.length === 1 ? ' venta' : ' ventas')"></span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Consulte comprobantes por fecha, cajero o número de factura (RF-21)</p>
                </div>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                <button type="button" @click="fetchVentas()" :disabled="loading"
                    class="p-2 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white bg-slate-100 dark:bg-dark-900 hover:bg-slate-200 dark:hover:bg-slate-800 border border-slate-300 dark:border-slate-700/80 rounded-xl transition-all cursor-pointer"
                    title="Actualizar listado">
                    <i data-lucide="refresh-cw" class="w-4 h-4" :class="{'animate-spin': loading}"></i>
                </button>
                <button type="button" @click="currentTab = 'pos'"
                    class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs sm:text-sm font-bold px-4 py-2 rounded-xl shadow-md shadow-emerald-600/20 transition-all cursor-pointer">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Nueva Venta</span>
                </button>
            </div>
        </div>

        <!-- Fila de Controles de Filtrado: Comprobante/Cliente, Rango de Fechas, y Cajero/Usuario -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3">
            <!-- 1. Búsqueda por N° Comprobante / Factura / Cliente / Ref -->
            <div class="lg:col-span-4 space-y-1">
                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300">
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-brand-500"></i>
                        <span>N° Comprobante o Cliente</span>
                    </span>
                </label>
                <div class="relative">
                    <input type="text" x-model="searchVenta"
                        placeholder="Ej: FAC-2026-..., Consumidor..."
                        class="w-full bg-white dark:bg-dark-900 border border-slate-300 dark:border-slate-700/80 rounded-xl pl-3 pr-8 py-1.5 text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-brand-500 transition-colors font-mono">
                    <button type="button" x-show="searchVenta" @click="searchVenta = ''"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </div>

            <!-- 2. Rango de Fechas: Desde -->
            <div class="lg:col-span-3 space-y-1">
                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300">
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-brand-500"></i>
                        <span>Fecha Desde</span>
                    </span>
                </label>
                <input type="date" x-model="ventaFechaDesde"
                    class="w-full bg-white dark:bg-dark-900 border border-slate-300 dark:border-slate-700/80 rounded-xl px-2.5 py-1.5 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-brand-500 transition-colors font-mono">
            </div>

            <!-- 3. Rango de Fechas: Hasta -->
            <div class="lg:col-span-3 space-y-1">
                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300">
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-brand-500"></i>
                        <span>Fecha Hasta</span>
                    </span>
                </label>
                <input type="date" x-model="ventaFechaHasta"
                    class="w-full bg-white dark:bg-dark-900 border border-slate-300 dark:border-slate-700/80 rounded-xl px-2.5 py-1.5 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-brand-500 transition-colors font-mono">
            </div>

            <!-- 4. Filtro por Cajero / Usuario -->
            <div class="lg:col-span-2 space-y-1">
                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300">
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="user" class="w-3.5 h-3.5 text-brand-500"></i>
                        <span>Cajero / Usuario</span>
                    </span>
                </label>
                <select x-model="ventaUsuarioFilter"
                    class="w-full bg-white dark:bg-dark-900 border border-slate-300 dark:border-slate-700/80 rounded-xl px-2.5 py-1.5 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-brand-500 transition-colors">
                    <option value="">Todos</option>
                    <template x-for="u in ventasUsuarios" :key="u.usuario_id">
                        <option :value="u.usuario_id" x-text="u.nombre_apellido"></option>
                    </template>
                </select>
            </div>
        </div>

        <!-- Fila Inferior: Atajos Rápidos de Fechas, Resumen y Botón Limpiar -->
        <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100 dark:border-slate-800/60 text-xs">
            <!-- Atajos rápidos -->
            <div class="flex items-center gap-1.5">
                <span class="text-[11px] font-bold text-slate-400">Atajos:</span>
                <button type="button" @click="setVentaQuickDate('hoy')"
                    class="px-2 py-0.5 rounded-lg text-[11px] font-semibold transition-all border cursor-pointer"
                    :class="(ventaFechaDesde && ventaFechaDesde === ventaFechaHasta && ventaFechaDesde === new Date().toISOString().slice(0, 10))
                        ? 'bg-brand-600 text-white border-brand-500'
                        : 'bg-slate-100 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700'">
                    Hoy
                </button>
                <button type="button" @click="setVentaQuickDate('7dias')"
                    class="px-2 py-0.5 rounded-lg text-[11px] font-semibold bg-slate-100 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all cursor-pointer">
                    Últimos 7 días
                </button>
                <button type="button" @click="setVentaQuickDate('mes')"
                    class="px-2 py-0.5 rounded-lg text-[11px] font-semibold bg-slate-100 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all cursor-pointer">
                    Este mes
                </button>
                <button type="button" x-show="hasActiveVentaFilters" @click="clearVentaFilters()"
                    class="px-2.5 py-0.5 rounded-lg text-[11px] font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 border border-rose-200 dark:border-rose-900/50 flex items-center gap-1 transition-all cursor-pointer">
                    <i data-lucide="rotate-ccw" class="w-3 h-3"></i>
                    <span>Limpiar filtros</span>
                </button>
            </div>

            <!-- Resumen Total Facturado con los Filtros -->
            <div class="flex items-center gap-3 ml-auto">
                <div class="text-[11px] text-slate-500 dark:text-slate-400">
                    <span>Total Facturado (Filtro): </span>
                    <span class="font-bold font-mono text-emerald-600 dark:text-emerald-400 text-xs sm:text-sm"
                        x-text="formatCurrency(filteredVentasTotal)"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Ventas -->
    <div class="glass-panel rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase bg-slate-50 dark:bg-dark-900/80 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Factura / Ticket</th>
                        <th class="py-3 px-4">Cliente</th>
                        <th class="py-3 px-4">Cajero / Vendedor</th>
                        <th class="py-3 px-4">Método de Pago</th>
                        <th class="py-3 px-4">Fecha y Hora</th>
                        <th class="py-3 px-4 text-right">Subtotal</th>
                        <th class="py-3 px-4 text-right">Total</th>
                        <th class="py-3 px-4 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                    <template x-for="v in filteredVentas" :key="v.venta_id">
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors"
                            :class="{'opacity-60 bg-rose-50/30 dark:bg-rose-950/10': Number(v.estado) === 0}">
                            <td class="py-3.5 px-4 font-mono font-bold text-brand-600 dark:text-brand-300">
                                <div class="flex items-center gap-1.5">
                                    <span x-text="v.codigo_venta"></span>
                                    <span x-show="Number(v.estado) === 0"
                                        class="text-[9px] font-sans font-bold px-1.5 py-0.2 rounded bg-rose-500/10 text-rose-500 border border-rose-500/20">Anulada</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-medium text-slate-800 dark:text-slate-200"
                                x-text="v.cliente ? v.cliente.nombre_apellido_cliente : 'Consumidor Final'"></td>
                            <td class="py-3.5 px-4 text-xs text-slate-600 dark:text-slate-400">
                                <span class="inline-flex items-center gap-1">
                                    <i data-lucide="user" class="w-3 h-3 text-slate-400"></i>
                                    <span x-text="v.usuario ? v.usuario.nombre_apellido : 'N/A'"></span>
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 text-xs rounded-md font-medium"
                                    :class="v.metodo_pago === 'Efectivo' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-brand-500/10 text-brand-600 dark:text-brand-400'"
                                    x-text="v.metodo_pago"></span>
                                <span x-show="v.referencia_transferencia" class="block font-mono text-[10px] text-slate-400 mt-0.5 truncate max-w-[130px]" :title="v.referencia_transferencia" x-text="v.referencia_transferencia"></span>
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-600 dark:text-slate-400 font-mono" x-text="formatDate(v.fecha_hora_venta)"></td>
                            <td class="py-3.5 px-4 text-right text-slate-600 dark:text-slate-400 font-mono" x-text="formatCurrency(v.subtotal_venta)"></td>
                            <td class="py-3.5 px-4 text-right font-bold font-mono text-slate-900 dark:text-white"
                                :class="{'line-through text-rose-500': Number(v.estado) === 0}"
                                x-text="formatCurrency(v.total_venta)"></td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button @click="openReceiptModal(v.venta_id, v)" class="p-1.5 text-slate-400 hover:text-emerald-500 dark:hover:text-emerald-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors cursor-pointer" title="Imprimir Comprobante (Ticket)">
                                        <i data-lucide="printer" class="w-4 h-4"></i>
                                    </button>
                                    <button @click="viewSaleDetails(v)" class="p-1.5 text-slate-400 hover:text-brand-500 dark:hover:text-brand-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors cursor-pointer" title="Ver Detalles de Factura">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </button>
                                    <button x-show="Number(v.estado) !== 0" @click="deleteSale(v)" class="p-1.5 text-slate-400 hover:text-rose-500 dark:hover:text-rose-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors cursor-pointer" title="Anular Venta">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <!-- Empty State cuando no hay resultados de búsqueda/filtro -->
            <div x-show="filteredVentas.length === 0" class="py-12 px-4 text-center space-y-3">
                <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/80 flex items-center justify-center mx-auto text-slate-400">
                    <i data-lucide="search-x" class="w-6 h-6"></i>
                </div>
                <div>
                    <h4 class="font-bold text-slate-800 dark:text-slate-200 text-sm">No se encontraron ventas</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto mt-0.5">
                        No hay ventas que coincidan con los filtros aplicados (rango de fechas, cajero o número de comprobante).
                    </p>
                </div>
                <div x-show="hasActiveVentaFilters">
                    <button type="button" @click="clearVentaFilters()"
                        class="px-3 py-1.5 bg-brand-600 hover:bg-brand-500 text-white rounded-xl text-xs font-semibold shadow-sm transition-all cursor-pointer inline-flex items-center gap-1.5">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        <span>Restablecer Filtros</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>