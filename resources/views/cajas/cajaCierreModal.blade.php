{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Modal de Arqueo y Cierre de Turno de Caja
    Archivo: resources/views/cajas/cajaCierreModal.blade.php
    Propósito: Permite realizar el arqueo en vivo comparando el efectivo esperado
               contra el efectivo contado, exigiendo justificación si existe descuadre
               para finalizar el turno operativo de caja.
    Controlador asociado: App\Http\Controllers\CajaOperacionController
    Endpoints: GET /api/caja-operaciones/{id} (Arqueo en tiempo real)
               PUT /api/caja-operaciones/{id} (Cierre formal de turno)
    =============================================================================
--}}

<div x-show="showCajaCierreModal" x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/75 backdrop-blur-sm overflow-y-auto">

    <!-- Contenedor del Modal -->
    <div @click.away="showCajaCierreModal = false"
        class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl w-full max-w-xl shadow-2xl overflow-hidden flex flex-col my-auto transition-all max-h-[92vh]">

        <!-- 1. Cabecera del Modal -->
        <div class="px-5 sm:px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between flex-shrink-0 bg-white dark:bg-dark-900">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center border shadow-xs bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20">
                    <i data-lucide="lock" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-white flex items-center gap-2">
                        <span>Arqueo & Cierre de Caja</span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Verificación de efectivo físico y liquidación del turno</p>
                </div>
            </div>
            <button type="button" @click="showCajaCierreModal = false"
                class="p-1.5 text-slate-400 hover:text-slate-700 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                title="Cerrar ventana">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- 2. Estado de Carga del Arqueo -->
        <div x-show="loadingArqueo" class="p-10 flex flex-col items-center justify-center space-y-3">
            <i data-lucide="loader-2" class="w-8 h-8 text-brand-500 animate-spin"></i>
            <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">Consultando balance y arqueo financiero...</p>
        </div>

        <!-- 3. Formulario y Desglose de Arqueo -->
        <form x-show="!loadingArqueo" @submit.prevent="saveCajaCierre()" class="overflow-y-auto p-5 sm:p-6 space-y-4">

            <!-- Ficha Resumen del Turno Activo -->
            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-slate-800 space-y-2.5 text-xs">
                <div>
                    <span class="text-slate-400 uppercase font-semibold text-[10px]">Turno a Liquidar:</span>
                    <div class="font-bold text-slate-800 dark:text-slate-100 text-sm flex items-center gap-1.5 mt-0.5">
                        <span x-text="'Turno #' + (cajaCierreData?.caja_operacion_id || '') + (cajaCierreData?.fecha_hora_apertura ? ' (Jornada ' + formatDateOnly(cajaCierreData.fecha_hora_apertura) + ')' : '')"></span>
                        <span class="text-slate-400">•</span>
                        <span class="text-brand-500" x-text="cajaCierreData?.caja?.descripcion_caja || ('Caja #' + (cajaCierreData?.id_caja || ''))"></span>
                    </div>
                </div>
                <div>
                    <span class="text-slate-400 uppercase font-semibold text-[10px]">Cajero Responsable:</span>
                    <div class="font-bold text-slate-800 dark:text-slate-100 text-sm flex items-center gap-1.5 mt-0.5"
                        x-text="cajaCierreData?.usuario?.nombre_apellido || currentUser?.nombre_apellido || 'Usuario'"></div>
                </div>
            </div>

            <!-- Desglose Financiero del Arqueo en Tiempo Real -->
            <div class="space-y-2">
                <div class="flex items-center justify-between text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="calculator" class="w-3.5 h-3.5 text-brand-400"></i>
                        <span>Desglose de Efectivo del Turno</span>
                    </span>
                    <span class="text-[11px] text-slate-400 normal-case font-normal">Cálculo dinámico auditado</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
                    <!-- Apertura (+) -->
                    <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-slate-800">
                        <span class="text-[10px] text-slate-400 uppercase font-semibold">Fondo Apertura</span>
                        <div class="font-mono font-bold text-slate-800 dark:text-slate-200 mt-0.5"
                            x-text="formatCurrency(cajaCierreData?.arqueo?.monto_apertura || 0)"></div>
                    </div>

                    <!-- Ventas Efectivo (+) -->
                    <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-slate-800">
                        <span class="text-[10px] text-slate-400 uppercase font-semibold">Ventas Efectivo</span>
                        <div class="font-mono font-bold text-emerald-600 dark:text-emerald-400 mt-0.5"
                            x-text="'+ ' + formatCurrency(cajaCierreData?.arqueo?.ventas_efectivo || 0)"></div>
                    </div>

                    <!-- Ingresos Extra (+) -->
                    <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-slate-800">
                        <span class="text-[10px] text-slate-400 uppercase font-semibold">Ingresos Extra</span>
                        <div class="font-mono font-bold text-emerald-600 dark:text-emerald-400 mt-0.5"
                            x-text="'+ ' + formatCurrency(cajaCierreData?.arqueo?.ingresos_extraordinarios || 0)"></div>
                    </div>

                    <!-- Gastos Menores (-) -->
                    <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-slate-800">
                        <span class="text-[10px] text-slate-400 uppercase font-semibold">Gastos / Egresos</span>
                        <div class="font-mono font-bold text-rose-600 dark:text-rose-400 mt-0.5"
                            x-text="'- ' + formatCurrency(cajaCierreData?.arqueo?.gastos_menores || 0)"></div>
                    </div>

                    <!-- Devoluciones Efectivo (-) -->
                    <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-slate-800">
                        <span class="text-[10px] text-slate-400 uppercase font-semibold">Devoluciones</span>
                        <div class="font-mono font-bold text-rose-600 dark:text-rose-400 mt-0.5"
                            x-text="'- ' + formatCurrency(cajaCierreData?.arqueo?.devoluciones_efectivo || 0)"></div>
                    </div>

                    <!-- Ventas Electrónicas (Informativo) -->
                    <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-slate-800">
                        <span class="text-[10px] text-slate-400 uppercase font-semibold">Tarjeta / Transf.</span>
                        <div class="font-mono font-bold text-indigo-600 dark:text-indigo-400 mt-0.5"
                            x-text="formatCurrency((Number(cajaCierreData?.arqueo?.ventas_tarjeta || 0) + Number(cajaCierreData?.arqueo?.ventas_transferencia || 0)))"></div>
                    </div>
                </div>

                <!-- Efectivo Total Esperado -->
                <div class="p-4 rounded-2xl bg-brand-500/10 border border-brand-500/25 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-brand-600 dark:text-brand-400">
                            Efectivo Esperado en Caja (Sistema)
                        </span>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Saldo teórico que debe existir físicamente en el cajón</p>
                    </div>
                    <div class="text-2xl font-display font-black font-mono text-slate-900 dark:text-white"
                        x-text="formatCurrency(cajaCierreData?.arqueo?.monto_esperado || 0)"></div>
                </div>
            </div>

            <!-- Entrada de Efectivo Físico Contado -->
            <div class="space-y-1.5 pt-1">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Efectivo Físico Contado (C$) <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 font-bold text-sm pointer-events-none font-mono">
                        C$
                    </span>
                    <input type="number" step="0.01" min="0" required
                        x-model="cajaCierreForm.monto_cierre"
                        placeholder="Ingrese el monto total contado"
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl pl-10 pr-3.5 py-3 text-lg font-bold font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all">
                </div>
                <div class="flex justify-end">
                    <button type="button"
                        @click="cajaCierreForm.monto_cierre = Number(cajaCierreData?.arqueo?.monto_esperado || 0).toFixed(2)"
                        class="text-xs font-semibold text-brand-500 hover:text-brand-400 hover:underline cursor-pointer">
                        Copiar saldo esperado exacto
                    </button>
                </div>
            </div>

            <!-- Indicador de Cuadre / Diferencia en Tiempo Real -->
            <template x-if="cajaCierreDiferencia !== null">
                <div class="p-3.5 rounded-xl border transition-all"
                    :class="{
                        'bg-emerald-500/10 border-emerald-500/30 text-emerald-800 dark:text-emerald-300': cajaCierreDiferencia === 0,
                        'bg-rose-500/10 border-rose-500/30 text-rose-800 dark:text-rose-300': cajaCierreDiferencia < 0,
                        'bg-amber-500/10 border-amber-500/30 text-amber-800 dark:text-amber-300': cajaCierreDiferencia > 0
                    }">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 font-bold text-xs">
                            <i :data-lucide="cajaCierreDiferencia === 0 ? 'check-circle' : 'alert-triangle'" class="w-4 h-4"></i>
                            <span x-text="cajaCierreDiferencia === 0 ? '¡Caja Cuadrada Perfectamente!' : (cajaCierreDiferencia < 0 ? 'Faltante de Efectivo' : 'Sobrante de Efectivo')"></span>
                        </div>
                        <div class="font-mono font-black text-sm"
                            x-text="(cajaCierreDiferencia > 0 ? '+ ' : '') + formatCurrency(cajaCierreDiferencia)"></div>
                    </div>
                    <p class="text-[11px] mt-1 opacity-90"
                        x-text="cajaCierreDiferencia === 0 
                            ? 'El efectivo físico contado coincide exactamente con el total registrado.' 
                            : 'Es obligatorio justificar la discrepancia antes de confirmar el cierre del turno.'">
                    </p>
                </div>
            </template>

            <!-- Justificación Obligatoria si hay descuadre -->
            <div class="space-y-1.5" x-show="cajaCierreDiferencia !== null && cajaCierreDiferencia !== 0">
                <label class="block text-xs font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">
                    Observación / Justificación del Descuadre <span class="text-rose-500">*</span>
                </label>
                <textarea x-model="cajaCierreForm.observacion_cierre" rows="2"
                    placeholder="Explique el motivo del faltante o sobrante detectado en el arqueo..."
                    class="w-full bg-slate-50 dark:bg-dark-950 border border-rose-300 dark:border-rose-700/80 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500 transition-all"></textarea>
                
                <!-- Chips de justificación rápida -->
                <div class="flex flex-wrap gap-1.5 pt-0.5">
                    <button type="button" @click="cajaCierreForm.observacion_cierre = 'Diferencia menor por redondeo de cambio'"
                        class="px-2 py-0.5 rounded-lg text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                        Redondeo de cambio
                    </button>
                    <button type="button" @click="cajaCierreForm.observacion_cierre = 'Error en entrega de vuelto al cliente'"
                        class="px-2 py-0.5 rounded-lg text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                        Error en vuelto
                    </button>
                    <button type="button" @click="cajaCierreForm.observacion_cierre = 'Gasto menor no registrado durante el turno'"
                        class="px-2 py-0.5 rounded-lg text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                        Gasto no registrado
                    </button>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                <button type="button" @click="showCajaCierreModal = false"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">
                    Cancelar
                </button>
                <button type="submit" :disabled="isSavingCajaCierre"
                    class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold bg-rose-600 hover:bg-rose-500 text-white shadow-lg shadow-rose-600/25 ring-2 ring-rose-500/20 transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!isSavingCajaCierre" class="flex items-center gap-2">
                        <i data-lucide="lock" class="w-4 h-4"></i>
                        <span>Confirmar Cierre de Turno</span>
                    </span>
                    <span x-show="isSavingCajaCierre" class="flex items-center gap-2">
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                        <span>Cerrando Turno...</span>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>
