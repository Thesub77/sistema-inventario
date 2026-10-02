{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Modal de Apertura de Turno de Caja (RF-28)
    Archivo: resources/views/cajas/cajaAperturaModal.blade.php
    Propósito: Permite iniciar formalmente un turno de caja indicando el fondo
               inicial de sencillo, activando la caja para facturar en el POS.
    Controlador asociado: App\Http\Controllers\CajaOperacionController
    Endpoint: POST /api/caja-operaciones
    =============================================================================
--}}

<div x-show="showCajaAperturaModal" x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/75 backdrop-blur-sm overflow-y-auto">

    <!-- Contenedor del Modal -->
    <div @click.away="showCajaAperturaModal = false"
        class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col my-auto transition-all">

        <!-- 1. Cabecera del Modal -->
        <div class="px-5 sm:px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between flex-shrink-0 bg-white dark:bg-dark-900">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center border shadow-xs bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20">
                    <i data-lucide="unlock" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-white flex items-center gap-2">
                        <span>Apertura de Turno</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20">
                            RF-28
                        </span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Inicio de operaciones de caja y facturación</p>
                </div>
            </div>
            <button type="button" @click="showCajaAperturaModal = false"
                class="p-1.5 text-slate-400 hover:text-slate-700 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                title="Cerrar ventana">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- 2. Formulario de Apertura -->
        <form @submit.prevent="saveCajaApertura()" class="p-5 sm:p-6 space-y-4">

            <!-- Selección de Caja Física -->
            <div class="space-y-1.5">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Caja Física a Abrir <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <select x-model.number="cajaAperturaForm.id_caja" required
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all cursor-pointer">
                        <option value="" disabled>Seleccione una caja</option>
                        <template x-for="c in (cajas || []).filter(item => Number(item.estado) !== 0)" :key="c.caja_id">
                            <option :value="c.caja_id"
                                :disabled="c.estado_caja === 'Abierta'"
                                x-text="c.descripcion_caja + (c.estado_caja === 'Abierta' ? ' (Ya abierta)' : ' (Cerrada)')">
                            </option>
                        </template>
                    </select>
                </div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">
                    Solo pueden aperturarse cajas físicas registradas que se encuentren cerradas.
                </p>
            </div>

            <!-- Fondo de Apertura (Monto Inicial) -->
            <div class="space-y-1.5">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Fondo Inicial de Efectivo (C$) <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 font-bold text-sm pointer-events-none font-mono">
                        C$
                    </span>
                    <input type="number" step="0.01" min="0" required
                        x-model="cajaAperturaForm.monto_apertura"
                        placeholder="0.00"
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl pl-10 pr-3.5 py-2.5 text-base font-bold font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all">
                </div>

                <!-- Chips de Monto Rápido -->
                <div class="flex flex-wrap gap-1.5 pt-1">
                    <button type="button" @click="cajaAperturaForm.monto_apertura = '0.00'"
                        class="px-2.5 py-1 rounded-lg text-xs font-mono font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-emerald-500/10 hover:text-emerald-600 dark:hover:text-emerald-400 border border-slate-200 dark:border-slate-700 transition-all cursor-pointer">
                        C$ 0.00
                    </button>
                    <button type="button" @click="cajaAperturaForm.monto_apertura = '500.00'"
                        class="px-2.5 py-1 rounded-lg text-xs font-mono font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-emerald-500/10 hover:text-emerald-600 dark:hover:text-emerald-400 border border-slate-200 dark:border-slate-700 transition-all cursor-pointer">
                        C$ 500
                    </button>
                    <button type="button" @click="cajaAperturaForm.monto_apertura = '1000.00'"
                        class="px-2.5 py-1 rounded-lg text-xs font-mono font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-emerald-500/10 hover:text-emerald-600 dark:hover:text-emerald-400 border border-slate-200 dark:border-slate-700 transition-all cursor-pointer">
                        C$ 1,000
                    </button>
                    <button type="button" @click="cajaAperturaForm.monto_apertura = '2000.00'"
                        class="px-2.5 py-1 rounded-lg text-xs font-mono font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-emerald-500/10 hover:text-emerald-600 dark:hover:text-emerald-400 border border-slate-200 dark:border-slate-700 transition-all cursor-pointer">
                        C$ 2,000
                    </button>
                </div>
            </div>

            <!-- Banner Informativo -->
            <div class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-start gap-2.5">
                <i data-lucide="info" class="w-4 h-4 text-emerald-500 flex-shrink-0 mt-0.5"></i>
                <div class="text-xs text-emerald-800 dark:text-emerald-300 leading-relaxed">
                    Al confirmar la apertura, el turno quedará activo y se habilitará la facturación inmediata en el Punto de Venta (POS).
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                <button type="button" @click="showCajaAperturaModal = false"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">
                    Cancelar
                </button>
                <button type="submit" :disabled="isSavingCajaApertura"
                    class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-600/25 ring-2 ring-emerald-500/20 transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!isSavingCajaApertura" class="flex items-center gap-2">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Abrir Turno</span>
                    </span>
                    <span x-show="isSavingCajaApertura" class="flex items-center gap-2">
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                        <span>Aperturando...</span>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>
