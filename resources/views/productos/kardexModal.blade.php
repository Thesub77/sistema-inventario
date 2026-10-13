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
        class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-5xl max-h-[92vh] flex flex-col shadow-2xl overflow-hidden transition-all"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95">

        <!-- Header del Modal -->
        <div class="flex items-center justify-between p-4 sm:p-5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-dark-950/70">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center border border-indigo-500/20 flex-shrink-0">
                    <i data-lucide="history" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-white"
                            x-text="kardexSelectedProduct ? 'Movimientos: ' + kardexSelectedProduct.nombre_producto : 'Historial de Movimientos de Stock'">
                        </h3>
                        <template x-if="kardexSelectedProduct">
                            <span class="font-mono text-xs font-semibold px-2 py-0.5 rounded-md bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20"
                                x-text="kardexSelectedProduct.codigo_producto"></span>
                        </template>
                        <template x-if="kardexSelectedProduct && kardexSelectedProduct.tipo_producto === 'combo'">
                            <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md bg-purple-500/10 text-purple-400 border border-purple-500/30">
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
                class="p-2 text-slate-400 hover:text-slate-700 dark:hover:text-white rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                title="Cerrar Historial">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Barra de Filtros y Búsqueda -->
        <div class="p-4 sm:p-5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/40 dark:bg-dark-950/40 space-y-3">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                <!-- Selector de Producto (permite cambiar o ver todos) -->
                <div class="md:col-span-5">
                    <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                        Producto Seleccionado
                    </label>
                    <select x-model="kardexSelectedProductId" @change="onKardexProductChange()"
                        class="w-full bg-white dark:bg-dark-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors">
                        <option value="">Todos los productos (Historial General)</option>
                        <template x-for="p in productos" :key="p.producto_id">
                            <option :value="p.producto_id" x-text="p.nombre_producto + ' [' + p.codigo_producto + ']'"></option>
                        </template>
                    </select>
                </div>

                <!-- Buscador rápido dentro del historial -->
                <div class="md:col-span-7">
                    <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                        Buscar en Movimientos
                    </label>
                    <div class="relative">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input type="text" x-model="kardexSearch" placeholder="Filtrar por factura, proveedor, merma, responsable..."
                            class="w-full bg-white dark:bg-dark-900 border border-slate-300 dark:border-slate-700 rounded-xl pl-9 pr-3 py-2 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors">
                    </div>
                </div>
            </div>

            <!-- Botones de Filtro por Tipo de Movimiento -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs">
                <span class="text-slate-400 dark:text-slate-500 text-[11px] font-semibold uppercase mr-1">Tipo:</span>
                <button type="button" @click="kardexFilterType = ''"
                    :class="kardexFilterType === '' ? 'bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-bold' : 'bg-slate-100 dark:bg-dark-900 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-800'"
                    class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer">
                    Todos
                </button>
                <button type="button" @click="kardexFilterType = 'Entrada'"
                    :class="kardexFilterType === 'Entrada' ? 'bg-emerald-600 text-white font-bold' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20'"
                    class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer">
                    Entradas / Compras
                </button>
                <button type="button" @click="kardexFilterType = 'Venta'"
                    :class="kardexFilterType === 'Venta' ? 'bg-indigo-600 text-white font-bold' : 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 hover:bg-indigo-500/20'"
                    class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer">
                    Salidas por Venta
                </button>
                <button type="button" @click="kardexFilterType = 'Merma'"
                    :class="kardexFilterType === 'Merma' ? 'bg-rose-600 text-white font-bold' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20 hover:bg-rose-500/20'"
                    class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer">
                    Mermas / Pérdidas
                </button>
                <button type="button" @click="kardexFilterType = 'Ajuste'"
                    :class="kardexFilterType === 'Ajuste' ? 'bg-amber-600 text-white font-bold' : 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 hover:bg-amber-500/20'"
                    class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer">
                    Ajustes Manuales
                </button>
            </div>
        </div>

        <!-- Banner de Métricas Físicas del Inventario -->
        <div class="px-4 sm:px-5 py-2.5 bg-slate-100/70 dark:bg-dark-950/90 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between flex-wrap gap-2 text-xs">
            <div class="flex items-center gap-4 flex-wrap">
                <span class="text-slate-600 dark:text-slate-300 font-medium">
                    Total registros: <strong class="text-slate-900 dark:text-white" x-text="kardexStats.totalRegistros"></strong>
                </span>
                <span class="text-emerald-600 dark:text-emerald-400 font-medium">
                    Entradas: <strong x-text="'+ ' + kardexStats.entradas + ' uds.'"></strong>
                </span>
                <span class="text-indigo-600 dark:text-indigo-400 font-medium">
                    Ventas: <strong x-text="'- ' + kardexStats.ventas + ' uds.'"></strong>
                </span>
                <span class="text-rose-600 dark:text-rose-400 font-medium">
                    Mermas: <strong x-text="'- ' + kardexStats.mermas + ' uds.'"></strong>
                </span>
            </div>

            <!-- Aviso para combos si aplica -->
            <template x-if="kardexSelectedProduct && kardexSelectedProduct.tipo_producto === 'combo'">
                <span class="text-[11px] text-purple-400 font-medium bg-purple-500/10 px-2 py-0.5 rounded border border-purple-500/20">
                    Combo: Los movimientos físicos se deducen en sus productos componentes.
                </span>
            </template>
        </div>

        <!-- Tabla Desplazable de Movimientos (Scroll vertical y horizontal) -->
        <div class="overflow-x-auto overflow-y-auto max-h-[50vh] p-0 flex-1">
            <table class="w-full min-w-[800px] text-left text-sm">
                <thead class="text-xs uppercase bg-slate-100/90 dark:bg-dark-900/90 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-10 backdrop-blur-sm">
                    <tr>
                        <th class="py-3 px-4 whitespace-nowrap">Fecha & Hora</th>
                        <th class="py-3 px-4 whitespace-nowrap" x-show="!kardexSelectedProduct">Producto</th>
                        <th class="py-3 px-4 whitespace-nowrap">Tipo</th>
                        <th class="py-3 px-4 min-w-[190px]">Motivo / Referencia</th>
                        <th class="py-3 px-4 text-center whitespace-nowrap">Cantidad</th>
                        <th class="py-3 px-4 text-center whitespace-nowrap">Stock Previo</th>
                        <th class="py-3 px-4 text-center whitespace-nowrap">Stock Resultante</th>
                        <th class="py-3 px-4 whitespace-nowrap">Responsable</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                    <template x-for="m in filteredKardexMovimientos" :key="m.movimiento_inventario_id">
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                            <!-- Fecha -->
                            <td class="py-3 px-4 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap" x-text="formatDate(m.fecha_movimiento)"></td>
                            
                            <!-- Producto (cuando es vista general o selector en todos) -->
                            <td class="py-3 px-4 font-semibold text-slate-900 dark:text-slate-100 whitespace-nowrap" x-show="!kardexSelectedProduct">
                                <span class="cursor-pointer hover:text-brand-500 hover:underline"
                                    @click="openKardexModal(m.producto)"
                                    x-text="m.producto ? m.producto.nombre_producto : 'Producto #' + m.id_producto"></span>
                            </td>

                            <!-- Tipo de Movimiento (Solo Badge) -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 text-xs rounded-full font-medium inline-block whitespace-nowrap"
                                    :class="m.tipo_movimiento.includes('Entrada') || m.tipo_movimiento.includes('Inicial') 
                                        ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' 
                                        : (m.tipo_movimiento.includes('Ajuste')
                                            ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20'
                                            : (m.tipo_movimiento.includes('Merma')
                                                ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20'
                                                : 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20'))"
                                    x-text="m.tipo_movimiento"></span>
                            </td>

                            <!-- Motivo / Referencia (Texto normal de Proveedor/Factura o Causa de Merma) -->
                            <td class="py-3 px-4">
                                <!-- Caso 1: Causa de Merma -->
                                <template x-if="m.tipo_merma">
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 px-2 py-0.5 rounded-md border border-rose-200 dark:border-rose-900/60"
                                        :title="'Causa de Merma: ' + m.tipo_merma">
                                        <i data-lucide="alert-triangle" class="w-3 h-3 flex-shrink-0"></i>
                                        <span x-text="m.tipo_merma"></span>
                                    </span>
                                </template>

                                <!-- Caso 2: Referencia de Compra (Proveedor y N° Documento en texto legible) -->
                                <template x-if="!m.tipo_merma && (m.proveedor_nombre || m.numero_factura_recibo)">
                                    <div class="text-xs text-slate-700 dark:text-slate-300 space-y-0.5">
                                        <p x-show="m.proveedor_nombre" class="font-medium flex items-center gap-1.5" :title="m.proveedor_nombre">
                                            <span class="text-[11px] text-slate-400 dark:text-slate-500 uppercase font-semibold">Prov:</span>
                                            <span class="truncate max-w-[170px]" x-text="m.proveedor_nombre"></span>
                                        </p>
                                        <p x-show="m.numero_factura_recibo" class="font-mono text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                                            <span class="text-slate-400 dark:text-slate-500 uppercase font-semibold">Doc:</span>
                                            <span x-text="m.numero_factura_recibo"></span>
                                        </p>
                                    </div>
                                </template>

                                <!-- Caso 3: Sin detalles adicionales (Ventas POS o Ajustes) -->
                                <template x-if="!m.tipo_merma && !m.proveedor_nombre && !m.numero_factura_recibo">
                                    <span class="text-xs text-slate-400 dark:text-slate-500"
                                        x-text="m.tipo_movimiento.includes('Venta') ? 'Venta POS' : (m.tipo_movimiento.includes('Ajuste') ? 'Ajuste de inventario' : '—')">
                                    </span>
                                </template>
                            </td>

                            <!-- Cantidad -->
                            <td class="py-3 px-4 text-center font-bold whitespace-nowrap"
                                :class="m.tipo_movimiento.includes('Entrada') || m.tipo_movimiento.includes('Inicial') 
                                    ? 'text-emerald-600 dark:text-emerald-400' 
                                    : (m.tipo_movimiento.includes('Ajuste') ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white')"
                                x-text="(m.tipo_movimiento.includes('Entrada') || m.tipo_movimiento.includes('Inicial') ? '+' : (m.tipo_movimiento.includes('Ajuste') ? '' : '-')) + (m.cantidad_movimimiento || m.cantidad_movimiento) + ' uds.'">
                            </td>

                            <!-- Stock Previo -->
                            <td class="py-3 px-4 text-center text-slate-500 dark:text-slate-400 font-mono text-xs whitespace-nowrap" x-text="m.stock_anterior_producto"></td>

                            <!-- Stock Resultante -->
                            <td class="py-3 px-4 text-center font-bold text-emerald-600 dark:text-emerald-400 font-mono text-xs whitespace-nowrap" x-text="m.stock_resultante_producto"></td>

                            <!-- Responsable -->
                            <td class="py-3 px-4 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap" x-text="m.usuario ? m.usuario.nombre_apellido : 'N/A'"></td>
                        </tr>
                    </template>

                    <!-- Estado Vacío -->
                    <tr x-show="filteredKardexMovimientos.length === 0">
                        <td colspan="8" class="py-12 text-center text-slate-400">
                            <i data-lucide="package-search" class="w-8 h-8 mx-auto mb-2 text-slate-500"></i>
                            <p class="font-medium text-slate-700 dark:text-slate-300">No se encontraron movimientos registrados para este filtro</p>
                            <p class="text-xs text-slate-400 mt-1">Verifique la búsqueda o seleccione otro producto / tipo de movimiento.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer del Modal -->
        <div class="p-4 sm:p-5 border-t border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-dark-950/70 flex items-center justify-between gap-3 flex-wrap">
            <div>
                <template x-if="kardexSelectedProduct && kardexSelectedProduct.tipo_producto !== 'combo'">
                    <button type="button" @click="openStockFromKardex()"
                        class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20 transition-all cursor-pointer">
                        <i data-lucide="package-plus" class="w-4 h-4"></i>
                        <span>Ajustar Stock de este Producto</span>
                    </button>
                </template>
            </div>

            <button type="button" @click="closeKardexModal()"
                class="px-5 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl transition-all cursor-pointer">
                Cerrar
            </button>
        </div>
    </div>
</div>
