{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Modal Registro de Abono / Pago a Cuenta por Pagar
    Archivo: resources/views/proveedores/abonoModal.blade.php
    Propósito: Registro de pagos parciales o totales a facturas de proveedores,
               integrado directamente con el egreso monetario de caja.
    =============================================================================
--}}

<div x-show="showAbonoModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <!-- Backdrop con desenfoque -->
    <div x-show="showAbonoModal"
        x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="showAbonoModal = false"
        class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs"></div>

    <div class="min-h-full flex items-center justify-center p-4">
        <div x-show="showAbonoModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-md bg-white dark:bg-dark-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden relative">

            <!-- Modal Header -->
            <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-dark-950/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 flex items-center justify-center shadow-xs">
                        <i data-lucide="hand-coins" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-display font-bold text-base text-slate-900 dark:text-white">Registrar Abono a Factura</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Salida y amortización de pasivo comercial</p>
                    </div>
                </div>
                <button type="button" @click="showAbonoModal = false"
                    class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Modal Body Form -->
            <form @submit.prevent="saveAbono()" class="p-6 space-y-4">
                <!-- Tarjeta Resumen de la Factura -->
                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-slate-800 space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 font-medium">Proveedor:</span>
                        <span class="font-bold text-slate-900 dark:text-white"
                            x-text="selectedCuentaParaAbono?.proveedor?.nombre_comercial || 'Proveedor'"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 font-medium">N° Factura:</span>
                        <span class="font-mono font-bold text-brand-600 dark:text-brand-400"
                            x-text="selectedCuentaParaAbono?.numero_factura"></span>
                    </div>
                    <div class="flex items-center justify-between pt-1 border-t border-slate-200/60 dark:border-slate-800/80">
                        <span class="text-slate-400 font-medium">Saldo Actual Pendiente:</span>
                        <span class="font-mono font-black text-sm text-amber-600 dark:text-amber-400"
                            x-text="formatCurrency(selectedCuentaParaAbono?.saldo_pendiente || 0)"></span>
                    </div>
                </div>

                <!-- Monto a Abonar -->
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                            <span>Monto a Abonar</span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <button type="button" @click="setPagarTotalidadAbono()"
                            class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 hover:underline cursor-pointer">
                            Pagar Saldo Total
                        </button>
                    </div>
                    <div class="relative">
                        <input type="number"
                            x-model="abonoForm.monto_pago"
                            required
                            step="0.01"
                            min="0.01"
                            :max="Number(selectedCuentaParaAbono?.saldo_pendiente || 0)"
                            placeholder="0.00"
                            class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700 rounded-xl pl-8 pr-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 transition-colors font-mono font-bold text-sm">
                        <div class="absolute left-3 top-2 text-xs font-bold text-slate-400 pointer-events-none">
                            C$
                        </div>
                    </div>
                </div>

                <!-- Método de Pago -->
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                        <span>Forma de Pago</span>
                    </label>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" @click="abonoForm.metodo_pago = 'Efectivo'; abonoForm.registrar_en_caja = true"
                            :class="abonoForm.metodo_pago === 'Efectivo'
                                ? 'bg-emerald-600 text-white font-bold border-emerald-500 shadow-xs'
                                : 'bg-slate-50 dark:bg-dark-950 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-800'"
                            class="py-2 px-2 rounded-xl text-xs border transition-all text-center flex items-center justify-center gap-1 cursor-pointer">
                            <i data-lucide="banknote" class="w-3.5 h-3.5"></i>
                            <span>Efectivo</span>
                        </button>
                        <button type="button" @click="abonoForm.metodo_pago = 'Transferencia'; abonoForm.registrar_en_caja = false"
                            :class="abonoForm.metodo_pago === 'Transferencia'
                                ? 'bg-emerald-600 text-white font-bold border-emerald-500 shadow-xs'
                                : 'bg-slate-50 dark:bg-dark-950 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-800'"
                            class="py-2 px-2 rounded-xl text-xs border transition-all text-center flex items-center justify-center gap-1 cursor-pointer">
                            <i data-lucide="arrow-left-right" class="w-3.5 h-3.5"></i>
                            <span>Transf.</span>
                        </button>
                        <button type="button" @click="abonoForm.metodo_pago = 'Cheque'; abonoForm.registrar_en_caja = false"
                            :class="abonoForm.metodo_pago === 'Cheque'
                                ? 'bg-emerald-600 text-white font-bold border-emerald-500 shadow-xs'
                                : 'bg-slate-50 dark:bg-dark-950 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-800'"
                            class="py-2 px-2 rounded-xl text-xs border transition-all text-center flex items-center justify-center gap-1 cursor-pointer">
                            <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                            <span>Cheque</span>
                        </button>
                    </div>
                </div>

                <!-- Referencia de Pago (Comprobante / Cheque) -->
                <div x-show="abonoForm.metodo_pago !== 'Efectivo'" class="space-y-1">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                        <span x-text="abonoForm.metodo_pago === 'Transferencia' ? 'N° Referencia Bancaria' : 'N° de Cheque'"></span>
                    </label>
                    <input type="text"
                        x-model="abonoForm.referencia_pago"
                        maxlength="64"
                        placeholder="Ej. TRF-BAC-489234"
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 transition-colors font-mono">
                </div>

                <!-- Toggle para impacto directo en Caja física -->
                <div x-show="abonoForm.metodo_pago === 'Efectivo'" class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/25 space-y-1.5">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox"
                            x-model="abonoForm.registrar_en_caja"
                            class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300 dark:border-slate-700 dark:bg-dark-950">
                        <span class="text-xs font-bold text-amber-900 dark:text-amber-200">
                            Descontar de la Caja Activa (Egreso)
                        </span>
                    </label>
                    <p class="text-[11px] text-amber-800/80 dark:text-amber-300/80 pl-6" x-show="turnoActivo">
                        Registrará automáticamente una salida de efectivo en el turno 
                        <span class="font-bold font-mono">#<span x-text="turnoActivo?.caja_operacion_id"></span></span> para que el arqueo de cierre cuadre exactamente.
                    </p>
                    <p class="text-[11px] text-rose-600 dark:text-rose-400 pl-6 font-semibold" x-show="!turnoActivo && abonoForm.registrar_en_caja">
                        ⚠️ No hay caja abierta. Debes abrir un turno para registrar salidas de efectivo.
                    </p>
                </div>

                <!-- Nota adicional -->
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                        <span>Nota u Observación (Opcional)</span>
                    </label>
                    <input type="text"
                        x-model="abonoForm.nota"
                        maxlength="255"
                        placeholder="Ej. Pago parcial acordado con el agente de ventas"
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 transition-colors">
                </div>

                <!-- Footer Acciones -->
                <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2">
                    <button type="button" @click="showAbonoModal = false"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit"
                        :disabled="isSavingAbono || (abonoForm.metodo_pago === 'Efectivo' && abonoForm.registrar_en_caja && !turnoActivo)"
                        class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-md shadow-emerald-600/20 transition-all flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <i data-lucide="check" class="w-4 h-4" x-show="!isSavingAbono"></i>
                        <span x-text="isSavingAbono ? 'Registrando...' : 'Confirmar Abono'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
