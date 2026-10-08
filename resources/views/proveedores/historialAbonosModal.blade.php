{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Modal Historial de Abonos / Pagos
    Archivo: resources/views/proveedores/historialAbonosModal.blade.php
    Propósito: Visualización detallada de todos los pagos realizados a una factura de proveedor.
    =============================================================================
--}}

<div x-show="showHistorialAbonosModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <!-- Backdrop con desenfoque -->
    <div x-show="showHistorialAbonosModal"
        x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="showHistorialAbonosModal = false"
        class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs"></div>

    <div class="min-h-full flex items-center justify-center p-4">
        <div x-show="showHistorialAbonosModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-xl bg-white dark:bg-dark-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden relative">

            <!-- Modal Header -->
            <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-dark-950/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-500 flex items-center justify-center shadow-xs">
                        <i data-lucide="history" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-display font-bold text-base text-slate-900 dark:text-white flex items-center gap-2">
                            <span>Historial de Abonos</span>
                            <span class="font-mono text-xs px-2 py-0.5 rounded-md bg-brand-500/10 text-brand-600 dark:text-brand-400 font-bold"
                                x-text="selectedCuentaHistorial?.numero_factura"></span>
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Proveedor: <span class="font-bold text-slate-700 dark:text-slate-200" x-text="selectedCuentaHistorial?.proveedor?.nombre_comercial"></span>
                        </p>
                    </div>
                </div>
                <button type="button" @click="showHistorialAbonosModal = false"
                    class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Modal Body: Resumen y Lista de Pagos -->
            <div class="p-6 space-y-4">
                <!-- Resumen de Totales -->
                <div class="grid grid-cols-3 gap-2 p-3 bg-slate-50 dark:bg-dark-950 rounded-2xl border border-slate-200 dark:border-slate-800 text-xs">
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Total Factura</span>
                        <span class="font-mono font-bold text-slate-800 dark:text-slate-200"
                            x-text="formatCurrency(selectedCuentaHistorial?.monto_total || 0)"></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Total Abonado</span>
                        <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400"
                            x-text="formatCurrency(selectedCuentaHistorial?.monto_pagado || 0)"></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Saldo Pendiente</span>
                        <span class="font-mono font-bold text-amber-600 dark:text-amber-400"
                            x-text="formatCurrency(selectedCuentaHistorial?.saldo_pendiente || 0)"></span>
                    </div>
                </div>

                <!-- Lista de Abonos Realizados -->
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    <template x-for="pago in (selectedCuentaHistorial?.pagos || [])" :key="pago.pago_cuenta_por_pagar_id">
                        <div class="p-3 rounded-xl bg-white dark:bg-dark-950/70 border border-slate-200 dark:border-slate-800/80 flex items-center justify-between gap-3 text-xs shadow-xs">
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-black text-sm text-emerald-600 dark:text-emerald-400"
                                        x-text="formatCurrency(pago.monto_pago)"></span>
                                    <span class="px-2 py-0.2 rounded-full text-[10px] font-bold"
                                        :class="pago.metodo_pago === 'Efectivo' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-brand-500/10 text-brand-600 dark:text-brand-400'"
                                        x-text="pago.metodo_pago"></span>
                                    <template x-if="pago.id_caja_movimiento_venta">
                                        <span class="text-[10px] text-amber-600 dark:text-amber-400 font-semibold flex items-center gap-1" title="Registrado en Arqueo de Caja">
                                            <i data-lucide="wallet" class="w-3 h-3"></i>
                                            <span>Egreso Caja</span>
                                        </span>
                                    </template>
                                </div>
                                <div class="text-[11px] text-slate-400 flex items-center gap-2">
                                    <span x-text="formatDate(pago.fecha_pago)"></span>
                                    <span>•</span>
                                    <span x-text="pago.usuario ? pago.usuario.nombre_apellido : 'Usuario'"></span>
                                </div>
                                <template x-if="pago.referencia_pago">
                                    <div class="text-[10px] text-slate-400 font-mono" x-text="'Ref: ' + pago.referencia_pago"></div>
                                </template>
                                <template x-if="pago.nota">
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 italic" x-text="pago.nota"></p>
                                </template>
                            </div>
                        </div>
                    </template>

                    <div x-show="!selectedCuentaHistorial?.pagos || selectedCuentaHistorial?.pagos?.length === 0"
                        class="py-8 text-center text-slate-400 text-xs">
                        No se registran pagos previos para esta factura.
                    </div>
                </div>

                <!-- Footer -->
                <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                    <button type="button" @click="showHistorialAbonosModal = false"
                        class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
