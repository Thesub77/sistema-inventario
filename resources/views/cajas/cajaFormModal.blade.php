{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Modal de Creación y Edición de Caja Física
    Archivo: resources/views/cajas/cajaFormModal.blade.php
    Propósito: Permite a los administradores registrar nuevas cajas en el establecimiento
               o actualizar la descripción y modo de apertura de cajas existentes.
    Controlador asociado: App\Http\Controllers\CajaController
    Endpoints: POST /api/cajas (Crear)
               PUT /api/cajas/{id} (Actualizar)
    =============================================================================
--}}

<div x-show="showCajaFormModal" x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/75 backdrop-blur-sm overflow-y-auto">

    <!-- Contenedor del Modal -->
    <div @click.away="showCajaFormModal = false"
        class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col my-auto transition-all">

        <!-- 1. Cabecera del Modal -->
        <div class="px-5 sm:px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between flex-shrink-0 bg-white dark:bg-dark-900">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center border shadow-xs bg-brand-500/10 text-brand-600 dark:text-brand-400 border-brand-500/20">
                    <i data-lucide="monitor" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-white"
                        x-text="cajaForm.caja_id ? 'Editar Caja Registradora' : 'Nueva Caja Registradora'">
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Punto de venta y terminal físico</p>
                </div>
            </div>
            <button type="button" @click="showCajaFormModal = false"
                class="p-1.5 text-slate-400 hover:text-slate-700 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                title="Cerrar ventana">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- 2. Formulario -->
        <form @submit.prevent="saveCaja()" class="p-5 sm:p-6 space-y-4">

            <!-- Nombre o Descripción de la Caja -->
            <div class="space-y-1.5">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Nombre o Descripción de la Caja <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <input type="text" required maxlength="128"
                        x-model="cajaForm.descripcion_caja"
                        placeholder="Ej. Caja Principal, Terminal Mostrador 2..."
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all">
                </div>
            </div>

            <!-- Tipo de Apertura -->
            <div class="space-y-1.5">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Modalidad de Apertura <span class="text-rose-500">*</span>
                </label>
                <select x-model="cajaForm.tipo_apertura" required
                    class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all cursor-pointer">
                    <option value="Manual">Manual</option>
                </select>
            </div>

            <!-- Estado Activa / Inactiva (Solo visible en modo edición) -->
            <div class="space-y-1.5" x-show="cajaForm.caja_id">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Estado Operativo
                </label>
                <select x-model.number="cajaForm.estado"
                    class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all cursor-pointer">
                    <option :value="1">Activa (Habilitada para turnos y ventas)</option>
                    <option :value="0">Inactiva (Deshabilitada)</option>
                </select>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">
                    Nota: No se puede desactivar una caja si tiene un turno actualmente abierto.
                </p>
            </div>

            <!-- Botones de Acción -->
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                <button type="button" @click="showCajaFormModal = false"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">
                    Cancelar
                </button>
                <button type="submit" :disabled="isSavingCaja"
                    class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold bg-brand-600 hover:bg-brand-500 text-white shadow-lg shadow-brand-600/25 ring-2 ring-brand-500/20 transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!isSavingCaja" class="flex items-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        <span x-text="cajaForm.caja_id ? 'Actualizar Caja' : 'Crear Caja'"></span>
                    </span>
                    <span x-show="isSavingCaja" class="flex items-center gap-2">
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                        <span>Guardando...</span>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>
