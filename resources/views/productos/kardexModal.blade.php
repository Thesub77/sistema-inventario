{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Modal de Historial de Movimientos de Stock
    Archivo: resources/views/productos/kardexModal.blade.php
    Propósito: Permite auditar y visualizar el historial de movimientos (entradas,
               mermas, ventas, ajustes) de un producto específico o de forma general.
    Controlador asociado: App\Http\Controllers\MovimientoInventarioController
    =============================================================================
--}}

<div x-show="showKardexModal" x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-black/75 backdrop-blur-sm"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0">

    <div @click.away="closeKardexModal()"
        class="bg-white dark:bg-dark-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl w-full max-w-[95vw] 2xl:max-w-7xl h-[92vh] flex flex-col shadow-2xl overflow-hidden transition-all"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95">

        <!-- Header del Modal -->
        <div class="flex items-center justify-between px-5 py-3.5 sm:px-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-dark-950/70 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-500/10 text-brand-600 dark:text-brand-400 flex items-center justify-center border border-brand-500/20 flex-shrink-0">
                    <i data-lucide="history" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-white"
                            x-text="kardexSelectedProduct ? 'Movimientos: ' + kardexSelectedProduct.nombre_producto : 'Historial de Movimientos de Stock'">
                        </h3>
                        <template x-if="kardexSelectedProduct">
                            <span class="font-mono text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700"
                                x-text="kardexSelectedProduct.codigo_producto"></span>
                        </template>
                        <template x-if="kardexSelectedProduct && kardexSelectedProduct.tipo_producto === 'combo'">
                            <span class="px-2.5 py-0.5 text-[10px] font-bold uppercase rounded-full bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-500/15 dark:text-purple-300 dark:border-purple-500/30">
                                Combo
                            </span>
                        </template>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"
                        x-text="kardexSelectedProduct 
                            ? 'Stock actual: ' + (kardexSelectedProduct.existencia_bodega || 0) + ' unidades | Mínimo: ' + (kardexSelectedProduct.existencia_minima || 0)
                            : 'Historial cronológico de entradas por compra, salidas por venta, mermas y ajustes de inventario'">
                    </p>
                </div>
            </div>

            <button type="button" @click="closeKardexModal()"
                class="p-2 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                title="Cerrar Historial">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Barra de Filtros y Búsqueda Optimizada -->
        <div class="px-5 py-3 sm:px-6 border-b border-slate-200 dark:border-slate-800/80 bg-slate-50/80 dark:bg-slate-900/50 flex-shrink-0 space-y-2.5">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 items-end">
                <!-- Selector de Producto -->
                <div class="lg:col-span-4">
                    <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                        Producto Seleccionado
                    </label>
                    <select x-model="kardexSelectedProductId" @change="onKardexProductChange()"
                        class="w-full bg-white dark:bg-dark-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all shadow-xs">
                        <option value="">Todos los productos (Historial General)</option>
                        <template x-for="p in productos" :key="p.producto_id">
                            <option :value="p.producto_id" x-text="p.nombre_producto + ' [' + p.codigo_producto + ']'"></option>
                        </template>
                    </select>
                </div>

                <!-- Buscador rápido dentro del historial -->
                <div class="lg:col-span-4">
                    <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                        Buscar en Movimientos
                    </label>
                    <div class="relative">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input type="text" x-model="kardexSearch" placeholder="Filtrar por factura, proveedor, merma, responsable..."
                            class="w-full bg-white dark:bg-dark-900 border border-slate-300 dark:border-slate-700 rounded-xl pl-9 pr-3 py-2 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all shadow-xs">
                    </div>
                </div>

                <!-- Botones de Filtro por Tipo de Movimiento -->
                <div class="lg:col-span-4 flex items-center justify-start gap-1.5 overflow-x-auto pb-0.5 text-xs">
                    <!-- Todos -->
                    <button type="button" @click="kardexFilterType = ''"
                        :class="kardexFilterType === '' 
                            ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 font-semibold shadow-xs' 
                            : 'bg-white dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700/80 hover:bg-slate-100 dark:hover:bg-slate-800'"
                        class="px-2.5 py-1.5 rounded-full text-xs transition-all duration-150 cursor-pointer whitespace-nowrap">
                        Todos
                    </button>

                    <!-- Entradas / Compras (Verde) -->
                    <button type="button" @click="kardexFilterType = 'Entrada'"
                        :class="kardexFilterType === 'Entrada' 
                            ? 'bg-emerald-600 text-white font-semibold shadow-xs' 
                            : 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-300 dark:border-emerald-500/20 dark:hover:bg-emerald-500/20'"
                        class="px-2.5 py-1.5 rounded-full text-xs transition-all duration-150 cursor-pointer whitespace-nowrap">
                        Entradas
                    </button>

                    <!-- Salidas por Venta (Azul) -->
                    <button type="button" @click="kardexFilterType = 'Venta'"
                        :class="kardexFilterType === 'Venta' 
                            ? 'bg-blue-600 text-white font-semibold shadow-xs' 
                            : 'bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-300 dark:border-blue-500/20 dark:hover:bg-blue-500/20'"
                        class="px-2.5 py-1.5 rounded-full text-xs transition-all duration-150 cursor-pointer whitespace-nowrap">
                        Ventas
                    </button>

                    <!-- Mermas / Pérdidas (Rojo) -->
                    <button type="button" @click="kardexFilterType = 'Merma'"
                        :class="kardexFilterType === 'Merma' 
                            ? 'bg-rose-600 text-white font-semibold shadow-xs' 
                            : 'bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-300 dark:border-rose-500/20 dark:hover:bg-rose-500/20'"
                        class="px-2.5 py-1.5 rounded-full text-xs transition-all duration-150 cursor-pointer whitespace-nowrap">
                        Mermas
                    </button>

                    <!-- Ajustes Manuales (Ámbar / Naranja) -->
                    <button type="button" @click="kardexFilterType = 'Ajuste'"
                        :class="kardexFilterType === 'Ajuste' 
                            ? 'bg-amber-600 text-white font-semibold shadow-xs' 
                            : 'bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-300 dark:border-amber-500/20 dark:hover:bg-amber-500/20'"
                        class="px-2.5 py-1.5 rounded-full text-xs transition-all duration-150 cursor-pointer whitespace-nowrap">
                        Ajustes
                    </button>
                </div>
            </div>
        </div>

        <!-- Banner de Métricas Físicas del Inventario (Cards Dinámicas según el Filtro) -->
        <div class="px-5 py-2.5 sm:px-6 bg-slate-100/80 dark:bg-dark-950/90 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between flex-wrap gap-2.5 text-xs flex-shrink-0">
            <div class="flex items-center gap-2 sm:gap-3 flex-wrap">
                <!-- Total Registros (Siempre visible y clickeable para volver a 'Todos') -->
                <button type="button" @click="kardexFilterType = ''"
                    title="Ver todos los movimientos"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/70 text-slate-600 dark:text-slate-300 font-medium shadow-xs hover:border-slate-400 dark:hover:border-slate-500 transition-all cursor-pointer">
                    Total registros:
                    <strong class="text-slate-900 dark:text-white font-bold" x-text="kardexStats.totalRegistros"></strong>
                </button>

                <!-- Card Entradas: Visible solo en 'Todos' o al filtrar por 'Entrada' -->
                <button type="button" @click="kardexFilterType = 'Entrada'"
                    x-show="!kardexFilterType || kardexFilterType === 'Entrada'"
                    title="Filtrar por Entradas / Compras"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200/80 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-300 font-medium shadow-xs hover:border-emerald-400 dark:hover:border-emerald-500 transition-all cursor-pointer">
                    Entradas:
                    <strong class="font-bold" x-text="'+ ' + kardexStats.entradas + ' uds.'"></strong>
                </button>

                <!-- Card Ventas: Visible solo en 'Todos' o al filtrar por 'Venta' -->
                <button type="button" @click="kardexFilterType = 'Venta'"
                    x-show="!kardexFilterType || kardexFilterType === 'Venta'"
                    title="Filtrar por Salidas de Venta"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 dark:bg-blue-500/10 border border-blue-200/80 dark:border-blue-500/20 text-blue-700 dark:text-blue-300 font-medium shadow-xs hover:border-blue-400 dark:hover:border-blue-500 transition-all cursor-pointer">
                    Ventas:
                    <strong class="font-bold" x-text="'- ' + kardexStats.ventas + ' uds.'"></strong>
                </button>

                <!-- Card Mermas: Visible solo en 'Todos' o al filtrar por 'Merma' -->
                <button type="button" @click="kardexFilterType = 'Merma'"
                    x-show="!kardexFilterType || kardexFilterType === 'Merma'"
                    title="Filtrar por Mermas / Pérdidas"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-50 dark:bg-rose-500/10 border border-rose-200/80 dark:border-rose-500/20 text-rose-700 dark:text-rose-300 font-medium shadow-xs hover:border-rose-400 dark:hover:border-rose-500 transition-all cursor-pointer">
                    Mermas:
                    <strong class="font-bold" x-text="'- ' + kardexStats.mermas + ' uds.'"></strong>
                </button>

                <!-- Card Ajustes: Visible solo en 'Todos' o al filtrar por 'Ajuste' -->
                <button type="button" @click="kardexFilterType = 'Ajuste'"
                    x-show="!kardexFilterType || kardexFilterType === 'Ajuste'"
                    title="Filtrar por Ajustes de Inventario"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 dark:bg-amber-500/10 border border-amber-200/80 dark:border-amber-500/20 text-amber-700 dark:text-amber-300 font-medium shadow-xs hover:border-amber-400 dark:hover:border-amber-500 transition-all cursor-pointer">
                    Ajustes:
                    <strong class="font-bold" x-text="(kardexStats.ajustes || 0) + ' mov.'"></strong>
                </button>
            </div>

            <!-- Aviso para combos si aplica -->
            <template x-if="kardexSelectedProduct && kardexSelectedProduct.tipo_producto === 'combo'">
                <span class="text-[11px] text-purple-700 dark:text-purple-300 font-medium bg-purple-50 dark:bg-purple-500/10 px-2.5 py-1 rounded-full border border-purple-200 dark:border-purple-500/20">
                    Combo: Los movimientos físicos se deducen en sus componentes.
                </span>
            </template>
        </div>

        <!-- Tabla Desplazable de Movimientos: Ocupa todo el alto y ancho libre -->
        <div class="overflow-x-auto overflow-y-auto flex-1 min-h-0 p-0">
            <table class="w-full text-left text-sm">
                <thead class="text-xs font-semibold uppercase tracking-wider bg-slate-100/90 dark:bg-dark-900/95 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-10 backdrop-blur-sm">
                    <tr>
                        <th scope="col" class="py-3 px-4 w-44 whitespace-nowrap">Fecha &amp; Hora</th>
                        <th scope="col" class="py-3 px-4 min-w-[200px] whitespace-nowrap" x-show="!kardexSelectedProduct">Producto</th>
                        <th scope="col" class="py-3 px-4 w-32 text-left whitespace-nowrap">Tipo</th>
                        <th scope="col" class="py-3 px-4 min-w-[220px]">Motivo / Referencia</th>
                        <th scope="col" class="py-3 px-4 w-28 text-right whitespace-nowrap">Cantidad</th>
                        <th scope="col" class="py-3 px-4 w-28 text-right whitespace-nowrap">Stock Previo</th>
                        <th scope="col" class="py-3 px-4 w-32 text-right whitespace-nowrap">Stock Resultante</th>
                        <th scope="col" class="py-3 px-4 w-36 whitespace-nowrap">Responsable</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 bg-white dark:bg-dark-900">
                    <template x-for="m in filteredKardexMovimientos" :key="m.movimiento_inventario_id">
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors duration-150">
                            <!-- Fecha -->
                            <td class="py-3 px-4 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap tabular-nums" x-text="formatDate(m.fecha_movimiento)"></td>
                            
                            <!-- Producto -->
                            <td class="py-3 px-4 font-semibold text-slate-900 dark:text-slate-100 whitespace-nowrap" x-show="!kardexSelectedProduct">
                                <span class="cursor-pointer hover:text-brand-600 dark:hover:text-brand-400 hover:underline transition-colors"
                                    @click="openKardexModal(m.producto)"
                                    x-text="m.producto ? m.producto.nombre_producto : 'Producto #' + m.id_producto"></span>
                            </td>

                            <!-- Tipo de Movimiento (Badge / Chip alineado a la izquierda) -->
                            <td class="py-3 px-4 text-left whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-semibold rounded-full border shadow-2xs whitespace-nowrap"
                                    :class="isKardexMerma(m)
                                        ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-500/15 dark:text-rose-300 dark:border-rose-500/30'
                                        : ((m.tipo_movimiento || '').toLowerCase().includes('entrada') || (m.tipo_movimiento || '').toLowerCase().includes('inicial') || (m.tipo_movimiento || '').toLowerCase().includes('compra')
                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:border-emerald-500/30' 
                                            : ((m.tipo_movimiento || '').toLowerCase().includes('ajuste')
                                                ? 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:border-amber-500/30'
                                                : 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-500/15 dark:text-blue-300 dark:border-blue-500/30'))"
                                    x-text="getKardexTipoLabel(m)"></span>
                            </td>

                            <!-- Motivo / Referencia (Diseño Uniforme por Tipo de Motivo) -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <!-- 1. Caso Merma / Rotura / Accidente: Chip Rojo con Ícono de Advertencia -->
                                <template x-if="isKardexMerma(m)">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-medium rounded-full bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-500/15 dark:text-rose-300 dark:border-rose-500/30 shadow-2xs"
                                        :title="'Causa de Merma: ' + getKardexMotivoLabel(m)">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0 text-rose-500 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                        <span x-text="getKardexMotivoLabel(m)"></span>
                                    </span>
                                </template>

                                <!-- 2. Caso Venta: Chip Azul con Ícono de Carrito POS -->
                                <template x-if="!isKardexMerma(m) && (m.tipo_movimiento || '').toLowerCase().includes('venta')">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-medium rounded-full bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-500/15 dark:text-blue-300 dark:border-blue-500/30 shadow-2xs"
                                        title="Salida directa desde Punto de Venta">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        <span>Venta POS</span>
                                    </span>
                                </template>

                                <!-- 3. Caso Entrada / Compra: Chip Verde con Proveedor o Documento -->
                                <template x-if="!isKardexMerma(m) && !( (m.tipo_movimiento || '').toLowerCase().includes('venta') ) && ( (m.tipo_movimiento || '').toLowerCase().includes('entrada') || (m.tipo_movimiento || '').toLowerCase().includes('compra') || (m.tipo_movimiento || '').toLowerCase().includes('inicial') || m.proveedor_nombre || m.numero_factura_recibo )">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-medium rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:border-emerald-500/30 shadow-2xs"
                                        :title="m.proveedor_nombre ? 'Proveedor: ' + m.proveedor_nombre + (m.numero_factura_recibo ? ' | Doc: ' + m.numero_factura_recibo : '') : 'Entrada por Compra'">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0 text-emerald-500 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h2"/>
                                        </svg>
                                        <span class="truncate max-w-[200px]" x-text="m.proveedor_nombre ? m.proveedor_nombre + (m.numero_factura_recibo ? ' · ' + m.numero_factura_recibo : '') : (m.numero_factura_recibo ? 'Doc: ' + m.numero_factura_recibo : 'Entrada / Compra')"></span>
                                    </span>
                                </template>

                                <!-- 4. Caso Ajuste Manual: Chip Ámbar con Ícono de Ajuste -->
                                <template x-if="!isKardexMerma(m) && !( (m.tipo_movimiento || '').toLowerCase().includes('venta') ) && !( (m.tipo_movimiento || '').toLowerCase().includes('entrada') || (m.tipo_movimiento || '').toLowerCase().includes('compra') || (m.tipo_movimiento || '').toLowerCase().includes('inicial') || m.proveedor_nombre || m.numero_factura_recibo ) && (m.tipo_movimiento || '').toLowerCase().includes('ajuste')">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-medium rounded-full bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:border-amber-500/30 shadow-2xs"
                                        title="Ajuste manual de existencias">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0 text-amber-500 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                                        </svg>
                                        <span>Ajuste de inventario</span>
                                    </span>
                                </template>

                                <!-- 5. Fallback Neutral -->
                                <template x-if="!isKardexMerma(m) && !( (m.tipo_movimiento || '').toLowerCase().includes('venta') ) && !( (m.tipo_movimiento || '').toLowerCase().includes('entrada') || (m.tipo_movimiento || '').toLowerCase().includes('compra') || (m.tipo_movimiento || '').toLowerCase().includes('inicial') || m.proveedor_nombre || m.numero_factura_recibo ) && !( (m.tipo_movimiento || '').toLowerCase().includes('ajuste') )">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-medium rounded-full bg-slate-100 text-slate-600 border border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 shadow-2xs">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                        <span>Movimiento General</span>
                                    </span>
                                </template>
                            </td>

                            <!-- Cantidad (Alineación Tabular Derecha y Color Correcto) -->
                            <td class="py-3 px-4 text-right font-bold tabular-nums whitespace-nowrap"
                                :class="isKardexMerma(m)
                                    ? 'text-rose-600 dark:text-rose-400' 
                                    : ((m.tipo_movimiento || '').toLowerCase().includes('entrada') || (m.tipo_movimiento || '').toLowerCase().includes('inicial') || (m.tipo_movimiento || '').toLowerCase().includes('compra')
                                        ? 'text-emerald-600 dark:text-emerald-400' 
                                        : ((m.tipo_movimiento || '').toLowerCase().includes('ajuste') 
                                            ? 'text-amber-600 dark:text-amber-400' 
                                            : 'text-blue-600 dark:text-blue-400'))"
                                x-text="((m.tipo_movimiento || '').toLowerCase().includes('entrada') || (m.tipo_movimiento || '').toLowerCase().includes('inicial') || (m.tipo_movimiento || '').toLowerCase().includes('compra') ? '+' : ((m.tipo_movimiento || '').toLowerCase().includes('ajuste') ? '' : '-')) + (m.cantidad_movimimiento || m.cantidad_movimiento) + ' uds.'">
                            </td>

                            <!-- Stock Previo -->
                            <td class="py-3 px-4 text-right text-slate-500 dark:text-slate-400 font-mono text-xs tabular-nums whitespace-nowrap" x-text="m.stock_anterior_producto"></td>

                            <!-- Stock Resultante -->
                            <td class="py-3 px-4 text-right font-bold text-slate-900 dark:text-slate-100 font-mono text-xs tabular-nums whitespace-nowrap" x-text="m.stock_resultante_producto"></td>

                            <!-- Responsable -->
                            <td class="py-3 px-4 text-xs text-slate-600 dark:text-slate-400 whitespace-nowrap" x-text="m.usuario ? m.usuario.nombre_apellido : 'N/A'"></td>
                        </tr>
                    </template>

                    <!-- Estado Vacío -->
                    <tr x-show="filteredKardexMovimientos.length === 0">
                        <td colspan="8" class="py-16 text-center text-slate-400 dark:text-slate-500">
                            <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-slate-100 dark:bg-slate-800/80 flex items-center justify-center text-slate-400 dark:text-slate-500">
                                <i data-lucide="package-search" class="w-6 h-6"></i>
                            </div>
                            <p class="font-medium text-slate-700 dark:text-slate-300">No se encontraron movimientos registrados para este filtro</p>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Verifique la búsqueda o seleccione otro producto / tipo de movimiento.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer del Modal -->
        <div class="px-5 py-3 sm:px-6 border-t border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-dark-950/70 flex items-center justify-between gap-3 flex-wrap flex-shrink-0">
            <div>
                <template x-if="kardexSelectedProduct && kardexSelectedProduct.tipo_producto !== 'combo'">
                    <button type="button" @click="openStockFromKardex()"
                        class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-300 dark:border-emerald-500/20 dark:hover:bg-emerald-500/20 transition-all cursor-pointer">
                        <i data-lucide="package-plus" class="w-4 h-4"></i>
                        <span>Ajustar Stock de este Producto</span>
                    </button>
                </template>
            </div>

            <button type="button" @click="closeKardexModal()"
                class="px-5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 dark:border-slate-700 text-sm font-semibold rounded-xl transition-all cursor-pointer shadow-xs">
                Cerrar
            </button>
        </div>
    </div>
</div>

<script>
    /**
     * Helpers de UI para normalizar de forma uniforme el diseño del Kardex
     */
    window.isKardexMerma = function(m) {
        if (!m) return false;
        var tipo = (m.tipo_movimiento || '').toLowerCase();
        var merma = (m.tipo_merma || '').toLowerCase();
        var doc = ((m.proveedor_nombre || '') + ' ' + (m.numero_factura_recibo || '') + ' ' + (m.justificacion || '')).toLowerCase();
        return tipo.includes('merma') || tipo.includes('perdida') || tipo.includes('pérdida') || Boolean(m.tipo_merma) || doc.includes('rotura') || doc.includes('accidente') || doc.includes('vencimiento');
    };

    window.getKardexMotivoLabel = function(m) {
        if (!m) return 'Merma Registrada';
        var txt = [
            m.tipo_merma,
            m.justificacion,
            m.proveedor_nombre,
            m.numero_factura_recibo,
            m.tipo_movimiento
        ].filter(Boolean).join(' ').toLowerCase();

        if (txt.includes('rotura') || txt.includes('accidente') || txt.includes('quebrado') || txt.includes('dañ') || txt.includes('dan')) {
            return 'Rotura / Accidente';
        }
        if (txt.includes('venc') || txt.includes('deterior') || txt.includes('caduc')) {
            return 'Deterioro / Vencimiento';
        }
        if (txt.includes('consumo') || txt.includes('interno')) {
            return 'Consumo Interno';
        }
        if (txt.includes('descarte') || txt.includes('tecnic') || txt.includes('técnic')) {
            return 'Descarte Técnico';
        }
        if (m.tipo_merma) {
            var clean = m.tipo_merma.replace(/_/g, ' ').replace(/\s*\/\s*/g, ' / ').trim();
            if (clean === clean.toUpperCase()) {
                clean = clean.split(' ').map(function(w) { return w.charAt(0).toUpperCase() + w.slice(1).toLowerCase(); }).join(' ');
            }
            return clean;
        }
        return 'Merma Registrada';
    };

    window.getKardexTipoLabel = function(m) {
        if (!m) return 'Movimiento';
        if (window.isKardexMerma(m)) return 'Merma';
        var tipo = (m.tipo_movimiento || '').toLowerCase();
        if (tipo.includes('venta')) return 'Salida por Venta';
        if (tipo.includes('entrada') || tipo.includes('compra') || tipo.includes('inicial')) return 'Entrada';
        if (tipo.includes('ajuste')) return 'Ajuste Manual';
        return m.tipo_movimiento || 'Movimiento';
    };
</script>
