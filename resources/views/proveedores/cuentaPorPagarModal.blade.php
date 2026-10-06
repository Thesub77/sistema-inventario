{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Modal Formulario de Cuenta por Pagar (Factura Recibida)
    Archivo: resources/views/proveedores/cuentaPorPagarModal.blade.php
    Propósito: Registro y edición de facturas a crédito emitidas por proveedores.
    =============================================================================
--}}

<div x-show="showCuentaPorPagarModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <!-- Backdrop con desenfoque -->
    <div x-show="showCuentaPorPagarModal"
        x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="showCuentaPorPagarModal = false"
        class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs"></div>

    <div class="min-h-full flex items-center justify-center p-4">
        <div x-show="showCuentaPorPagarModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-lg bg-white dark:bg-dark-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden relative">

            <!-- Modal Header -->
            <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-dark-950/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-500 flex items-center justify-center shadow-xs">
                        <i data-lucide="receipt" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-display font-bold text-base text-slate-900 dark:text-white"
                            x-text="isEditingCuentaPorPagar ? 'Editar Factura / CxP' : 'Registrar Factura por Pagar'"></h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Control de créditos comerciales y compromisos de pago</p>
                    </div>
                </div>
                <button type="button" @click="showCuentaPorPagarModal = false"
                    class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Modal Body Form -->
            <form @submit.prevent="saveCuentaPorPagar()" class="p-6 space-y-4">
                <!-- Selección de Proveedor -->
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                            <span>Proveedor Emisor</span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <button type="button" @click="showCuentaPorPagarModal = false; openProveedorModal()"
                            class="text-[11px] text-amber-600 dark:text-amber-400 hover:underline font-semibold cursor-pointer">
                            + Crear nuevo proveedor
                        </button>
                    </div>
                    <div class="relative">
                        <select x-model.number="cuentaPorPagarForm.id_proveedor"
                            @change="onProveedorSelectInCxP()"
                            required
                            class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700 rounded-xl pl-9 pr-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition-colors">
                            <option value="">-- Seleccionar Proveedor --</option>
                            <template x-for="p in proveedores" :key="p.proveedor_id">
                                <option :value="p.proveedor_id" x-text="p.nombre_comercial + ' (' + (p.plazo_credito_dias > 0 ? p.plazo_credito_dias + ' días' : 'Contado') + ')'"></option>
                            </template>
                        </select>
                        <div class="absolute left-3 top-2.5 text-slate-400 pointer-events-none">
                            <i data-lucide="building-2" class="w-4 h-4"></i>
                        </div>
                    </div>
                </div>

                <!-- N° de Factura / Documento & Monto Total en 2 columnas -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                            <span>N° de Factura / Recibo</span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="text"
                                x-model="cuentaPorPagarForm.numero_factura"
                                required
                                maxlength="64"
                                placeholder="Ej. FACT-98234"
                                class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700 rounded-xl pl-9 pr-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition-colors font-mono font-bold">
                            <div class="absolute left-3 top-2.5 text-slate-400 pointer-events-none">
                                <i data-lucide="hash" class="w-4 h-4"></i>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                            <span>Monto Total de la Factura</span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="number"
                                x-model="cuentaPorPagarForm.monto_total"
                                required
                                step="0.01"
                                min="0.01"
                                placeholder="0.00"
                                class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700 rounded-xl pl-8 pr-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition-colors font-mono font-bold">
                            <div class="absolute left-3 top-2 text-xs font-bold text-slate-400 pointer-events-none">
                                C$
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fechas: Emisión y Vencimiento -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                            <span>Fecha de Emisión</span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="date"
                                x-model="cuentaPorPagarForm.fecha_emision"
                                @change="onProveedorSelectInCxP()"
                                required
                                class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700 rounded-xl pl-9 pr-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition-colors font-mono">
                            <div class="absolute left-3 top-2.5 text-slate-400 pointer-events-none">
                                <i data-lucide="calendar" class="w-4 h-4"></i>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                            <span>Fecha de Vencimiento</span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="date"
                                x-model="cuentaPorPagarForm.fecha_vencimiento"
                                required
                                class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700 rounded-xl pl-9 pr-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition-colors font-mono">
                            <div class="absolute left-3 top-2.5 text-slate-400 pointer-events-none">
                                <i data-lucide="clock" class="w-4 h-4"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Descripción / Concepto General -->
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                        <span>Concepto / Mercadería Recibida</span>
                    </label>
                    <textarea x-model="cuentaPorPagarForm.descripcion"
                        rows="2"
                        maxlength="255"
                        placeholder="Ej. Mercadería semanal de abarrotes, lácteos y bebidas recibida a crédito..."
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700 rounded-xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition-colors resize-none"></textarea>
                </div>

                <!-- Footer Acciones -->
                <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2">
                    <button type="button" @click="showCuentaPorPagarModal = false"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit"
                        :disabled="isSavingCuentaPorPagar"
                        class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold shadow-md shadow-amber-500/20 transition-all flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <i data-lucide="check" class="w-4 h-4" x-show="!isSavingCuentaPorPagar"></i>
                        <span x-text="isSavingCuentaPorPagar ? 'Guardando...' : (isEditingCuentaPorPagar ? 'Guardar Cambios' : 'Registrar Factura')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
