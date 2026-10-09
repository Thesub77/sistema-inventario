{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Formulario Modal de Ajuste de Stock
    Archivo: resources/views/productos/ajusteStockView.blade.php
    Propósito: Permite la creación y edición de productos de forma modular.
    Controlador asociado: App\Http\Controllers\ProductoController
    Modelo: App\Models\Producto
    Integración: Incluido en welcome.blade.php mediante @include('productos.ajusteStockView') 
    =============================================================================
--}}

<div x-show="showStockModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
    <div @click.away="showStockModal = false" class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-lg p-5 sm:p-6 shadow-2xl space-y-4 transition-all">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center border border-emerald-500/20">
                    <i data-lucide="package-plus" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white">Ajustar Inventario</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Entradas, salidas por merma y ajustes de existencias</p>
                </div>
            </div>
            <button type="button" @click="showStockModal = false" class="p-1.5 text-slate-400 hover:text-slate-700 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer" title="Cerrar modal">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form @submit.prevent="saveStockAdjustment()" class="space-y-4">
            <div class="p-3.5 bg-slate-50 dark:bg-dark-950 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3">
                <div>
                    <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Producto</p>
                    <p class="text-sm font-bold text-slate-900 dark:text-white" x-text="stockForm.nombre_producto"></p>
                </div>
                <div class="text-right">
                    <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Stock Actual</p>
                    <p class="text-sm font-bold text-emerald-600 dark:text-emerald-400" x-text="stockForm.stock_anterior + ' unidades'"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tipo de Operación</label>
                    <select x-model="stockForm.tipo_movimiento" class="w-full bg-slate-50/50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors">
                        <option value="Entrada por Compra">Entrada / Compra de Stock (+)</option>
                        <option value="Salida por Merma">Salida / Merma / Daño (-)</option>
                        <option value="Ajuste Manual">Ajuste Manual</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1" x-text="stockForm.tipo_movimiento === 'Ajuste Manual' ? 'Nuevo Stock Total' : 'Cantidad a Registrar'"></label>
                    <input type="number" min="1" x-model.number="stockForm.cantidad" required
                        class="w-full bg-slate-50/50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors">
                </div>
            </div>

            <!-- Campos opcionales para Entrada o Entrada por Compra (RF-10) -->
            <div x-show="stockForm.tipo_movimiento === 'Entrada' || stockForm.tipo_movimiento === 'Entrada por Compra'"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="p-3.5 bg-slate-50 dark:bg-dark-950/70 rounded-xl border border-slate-200 dark:border-slate-800 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Referencia de Compra</span>
                    </span>
                    <span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-slate-200/80 dark:bg-slate-800 text-slate-600 dark:text-slate-400">Opcional</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end">
                    <div class="flex flex-col">
                        <label class="h-5 flex items-center text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5 truncate" title="Proveedor / Lugar de Compra">
                            Proveedor / Lugar de Compra
                        </label>
                        <div class="relative">
                            <input type="text" maxlength="128" x-model="stockForm.proveedor_nombre"
                                placeholder="Ej. Maxi Palí, Distribuidora"
                                class="w-full bg-white dark:bg-dark-950 border border-slate-300 dark:border-slate-700 rounded-xl pl-8 pr-3 py-2 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors">
                            <div class="absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-1.1 0-2 .9-2 2v7c0 .6.4 1 1 1h2m10 0a2 2 0 100 4 2 2 0 000-4zm-8 0a2 2 0 100 4 2 2 0 000-4z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col">
                        <label class="h-5 flex items-center text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5 truncate" title="N° de Factura / Recibo">
                            N° de Factura / Recibo
                        </label>
                        <div class="relative">
                            <input type="text" maxlength="64" x-model="stockForm.numero_factura_recibo"
                                placeholder="Ej. FAC-1024 o REC-098"
                                class="w-full bg-white dark:bg-dark-950 border border-slate-300 dark:border-slate-700 rounded-xl pl-8 pr-3 py-2 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors font-mono">
                            <div class="absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="text-[10px] text-slate-500 dark:text-slate-400">Campos opcionales para trazabilidad en Kardex sin frenar la captura rápida.</p>
            </div>

            <!-- Tipificación y Costeo Financiero de Merma (RF-48) -->
            <div x-show="stockForm.tipo_movimiento === 'Salida por Merma'"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="p-3.5 bg-rose-50/60 dark:bg-rose-950/20 rounded-xl border border-rose-200 dark:border-rose-900/50 space-y-3">
                
                <div>
                    <label class="block text-xs font-semibold text-rose-700 dark:text-rose-300 mb-1">
                        Causa de la Merma *
                    </label>
                    <select x-model="stockForm.tipo_merma"
                        :required="stockForm.tipo_movimiento === 'Salida por Merma'"
                        class="w-full bg-white dark:bg-dark-950 border border-rose-300 dark:border-rose-800 rounded-xl px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-colors">
                        <option value="Deterioro/Vencimiento">Deterioro / Vencimiento</option>
                        <option value="Rotura/Accidente">Rotura / Accidente en manipulación</option>
                        <option value="Consumo Interno">Consumo Interno / Cortesía operativa</option>
                        <option value="Descarte Tecnico">Descarte Técnico / Daño de embalaje</option>
                    </select>
                </div>

                <!-- Indicador visual del costo estimado de la pérdida (reactivo: cantidad * costo) -->
                <div class="p-2.5 bg-rose-100/70 dark:bg-rose-900/30 rounded-lg border border-rose-200 dark:border-rose-800/60 flex items-center justify-between text-xs">
                    <span class="text-rose-700 dark:text-rose-300 font-medium flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-rose-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>Costo estimado de la pérdida:</span>
                    </span>
                    <span class="font-mono font-bold text-sm text-rose-600 dark:text-rose-400"
                        x-text="formatCurrency((Number(stockForm.cantidad) || 0) * (Number(stockForm.costo_compra) || 0))"></span>
                </div>
            </div>

            <!-- Campo de justificación obligatorio para salidas o ajustes manuales -->
            <div x-show="stockForm.tipo_movimiento === 'Salida por Merma' || stockForm.tipo_movimiento === 'Ajuste Manual'"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0">
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Motivo / Justificación *</label>
                <input type="text" maxlength="90" x-model="stockForm.justificacion"
                    :required="stockForm.tipo_movimiento === 'Salida por Merma' || stockForm.tipo_movimiento === 'Ajuste Manual'"
                    placeholder="Ej. Producto dañado o diferencia en conteo físico"
                    class="w-full bg-slate-50/50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors">
                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">Requerido para salidas y ajustes (máximo 90 caracteres).</p>
            </div>

            <div class="pt-4 flex justify-end gap-2 border-t border-slate-200 dark:border-slate-800">
                <button type="button" @click="showStockModal = false" class="px-4 py-2 text-sm text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-colors cursor-pointer">Cancelar</button>
                <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold rounded-xl shadow-lg shadow-emerald-600/20 cursor-pointer">Aplicar Ajuste</button>
            </div>
        </form>
    </div>
</div>