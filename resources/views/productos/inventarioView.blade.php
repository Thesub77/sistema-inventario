{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Kardex y Movimientos
    Archivo: resources/views/productos/inventarioView.blade.php
    Propósito: Permite la visualización de Kardex y Movimientos de productos de forma modular.
    Controlador asociado: App\Http\Controllers\ProductoController
    Modelo: App\Models\Producto
    Integración: Incluido en welcome.blade.php mediante @include('productos.inventarioView') 
    =============================================================================
--}}

<div x-show="currentTab === 'inventario'" x-cloak class="space-y-5">
    <div class="glass-panel p-5 rounded-2xl border border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between mb-4">
            <h4 class="font-display font-bold text-base text-slate-900 dark:text-white flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-brand-500/10 text-brand-600 dark:text-brand-400 flex items-center justify-center border border-brand-500/20">
                    <i data-lucide="repeat" class="w-4 h-4"></i>
                </div>
                <span>Registro de Kardex y Movimientos de Stock</span>
                <span class="text-xs font-mono font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700"
                    x-text="movimientosInventario.length + (movimientosInventario.length === 1 ? ' registro' : ' registros')"></span>
            </h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase bg-slate-100/80 dark:bg-dark-900/80 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Fecha</th>
                        <th class="py-3 px-4">Producto</th>
                        <th class="py-3 px-4">Tipo de Movimiento</th>
                        <th class="py-3 px-4">Causa / Costo Pérdida</th>
                        <th class="py-3 px-4 text-center">Cantidad</th>
                        <th class="py-3 px-4 text-center">Stock Previo</th>
                        <th class="py-3 px-4 text-center">Stock Resultante</th>
                        <th class="py-3 px-4">Usuario</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                    <template x-for="m in movimientosInventario" :key="m.movimiento_inventario_id">
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-3 px-4 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap" x-text="formatDate(m.fecha_movimiento)"></td>
                            <td class="py-3 px-4 font-semibold text-slate-900 dark:text-slate-100" x-text="m.producto ? m.producto.nombre_producto : 'Producto #' + m.id_producto"></td>
                            <td class="py-3 px-4">
                                <div class="flex flex-col items-start gap-1">
                                    <span class="px-2.5 py-0.5 text-xs rounded-full font-medium"
                                        :class="m.tipo_movimiento.includes('Entrada') || m.tipo_movimiento.includes('Inicial') 
                                                            ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' 
                                                            : (m.tipo_movimiento.includes('Ajuste')
                                                                ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20'
                                                                : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20')"
                                        x-text="m.tipo_movimiento"></span>

                                    <!-- Referencia opcional de compra: Proveedor y N° Factura/Recibo (RF-10) -->
                                    <div class="flex flex-wrap items-center gap-1.5 mt-0.5" x-show="m.proveedor_nombre || m.numero_factura_recibo">
                                        <!-- Badge: Proveedor / Lugar de Compra -->
                                        <template x-if="m.proveedor_nombre">
                                            <span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800/90 px-2 py-0.5 rounded-md border border-slate-200 dark:border-slate-700/80 transition-colors"
                                                :title="'Proveedor / Lugar de Compra: ' + m.proveedor_nombre">
                                                <svg class="w-3 h-3 text-brand-600 dark:text-brand-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-1.1 0-2 .9-2 2v7c0 .6.4 1 1 1h2m10 0a2 2 0 100 4 2 2 0 000-4zm-8 0a2 2 0 100 4 2 2 0 000-4z"/>
                                                </svg>
                                                <span class="truncate max-w-[140px]" x-text="m.proveedor_nombre"></span>
                                            </span>
                                        </template>

                                        <!-- Badge: N° Factura / Recibo -->
                                        <template x-if="m.numero_factura_recibo">
                                            <span class="inline-flex items-center gap-1 text-[11px] font-mono font-semibold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/50 px-2 py-0.5 rounded-md border border-indigo-200 dark:border-indigo-800/50 transition-colors"
                                                :title="'N° Factura / Recibo: ' + m.numero_factura_recibo">
                                                <svg class="w-3 h-3 text-indigo-600 dark:text-indigo-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                <span class="truncate max-w-[130px]" x-text="m.numero_factura_recibo"></span>
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </td>
                            <!-- Causa tipificada y costo monetario de merma (RF-48) -->
                            <td class="py-3 px-4">
                                <template x-if="m.tipo_merma || (m.costo_total_perdida !== null && m.costo_total_perdida !== undefined && Number(m.costo_total_perdida) > 0)">
                                    <div class="flex flex-col items-start gap-1">
                                        <template x-if="m.tipo_merma">
                                            <span class="inline-flex items-center gap-1 text-[11px] font-medium text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-950/50 px-2 py-0.5 rounded-md border border-rose-200 dark:border-rose-900/60"
                                                :title="'Causa de Merma: ' + m.tipo_merma">
                                                <svg class="w-3 h-3 text-rose-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                </svg>
                                                <span class="truncate max-w-[160px]" x-text="m.tipo_merma"></span>
                                            </span>
                                        </template>
                                        <template x-if="m.costo_total_perdida !== null && m.costo_total_perdida !== undefined && Number(m.costo_total_perdida) > 0">
                                            <span class="text-xs font-mono font-bold text-rose-600 dark:text-rose-400"
                                                :title="'Pérdida valorizada: C$ ' + Number(m.costo_total_perdida).toFixed(2)"
                                                x-text="'- ' + formatCurrency(m.costo_total_perdida)"></span>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!m.tipo_merma && (m.costo_total_perdida === null || m.costo_total_perdida === undefined || Number(m.costo_total_perdida) === 0)">
                                    <span class="text-xs text-slate-400 dark:text-slate-600 font-mono">—</span>
                                </template>
                            </td>
                            <td class="py-3 px-4 text-center font-bold text-slate-900 dark:text-white" x-text="m.cantidad_movimimiento + ' uds.'"></td>
                            <td class="py-3 px-4 text-center text-slate-500 dark:text-slate-400" x-text="m.stock_anterior_producto"></td>
                            <td class="py-3 px-4 text-center font-bold text-emerald-600 dark:text-emerald-400" x-text="m.stock_resultante_producto"></td>
                            <td class="py-3 px-4 text-xs text-slate-500 dark:text-slate-400" x-text="m.usuario ? m.usuario.nombre_apellido : 'N/A'"></td>
                        </tr>
                    </template>
                    <tr x-show="movimientosInventario.length === 0">
                        <td colspan="8" class="py-12 text-center text-slate-400">
                            <i data-lucide="package-search" class="w-8 h-8 mx-auto mb-2 text-slate-500"></i>
                            <p class="font-medium text-slate-700 dark:text-slate-300">No se han registrado movimientos de inventario todavía</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>