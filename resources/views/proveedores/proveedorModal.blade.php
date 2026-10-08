{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Modal Formulario de Proveedor
    Archivo: resources/views/proveedores/proveedorModal.blade.php
    Propósito: Creación y edición reactiva de proveedores comerciales.
    =============================================================================
--}}

<div x-show="showProveedorModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <!-- Backdrop con desenfoque -->
    <div x-show="showProveedorModal"
        x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="showProveedorModal = false"
        class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs"></div>

    <div class="min-h-full flex items-center justify-center p-4">
        <div x-show="showProveedorModal"
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
                    <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-500 flex items-center justify-center shadow-xs">
                        <i data-lucide="building-2" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-display font-bold text-base text-slate-900 dark:text-white"
                            x-text="isEditingProveedor ? 'Editar Proveedor' : 'Nuevo Proveedor'"></h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Directorio de distribuidores comerciales</p>
                    </div>
                </div>
                <button type="button" @click="showProveedorModal = false"
                    class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Modal Body Form -->
            <form @submit.prevent="saveProveedor()" class="p-6 space-y-4">
                <!-- Nombre Comercial -->
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                        <span>Nombre Comercial de la Empresa</span>
                        <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="text"
                            x-model="proveedorForm.nombre_comercial"
                            required
                            maxlength="128"
                            placeholder="Ej. Distribuidora Polar, S.A."
                            class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700 rounded-xl pl-9 pr-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition-colors">
                        <div class="absolute left-3 top-2.5 text-slate-400 pointer-events-none">
                            <i data-lucide="building" class="w-4 h-4"></i>
                        </div>
                    </div>
                </div>

                <!-- Contacto Vendedor / Representante -->
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                        <span>Vendedor o Contacto</span>
                    </label>
                    <div class="relative">
                        <input type="text"
                            x-model="proveedorForm.contacto_vendedor"
                            maxlength="128"
                            placeholder="Ej. Juan Pérez (Agente de ventas)"
                            class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700 rounded-xl pl-9 pr-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition-colors">
                        <div class="absolute left-3 top-2.5 text-slate-400 pointer-events-none">
                            <i data-lucide="user" class="w-4 h-4"></i>
                        </div>
                    </div>
                </div>

                <!-- Teléfono de Contacto -->
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                        <span>Teléfono / WhatsApp</span>
                    </label>
                    <div class="relative">
                        <input type="text"
                            x-model="proveedorForm.telefono"
                            maxlength="32"
                            placeholder="Ej. 8888-9999 / 2255-0000"
                            class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700 rounded-xl pl-9 pr-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition-colors font-mono">
                        <div class="absolute left-3 top-2.5 text-slate-400 pointer-events-none">
                            <i data-lucide="phone" class="w-4 h-4"></i>
                        </div>
                    </div>
                </div>

                <!-- Plazo de Crédito Otorgado (Días) -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                        <span>Plazo de Crédito Habitual</span>
                    </label>
                    
                    <div class="grid grid-cols-4 gap-1.5 mb-2">
                        <button type="button" @click="proveedorForm.plazo_credito_dias = 0"
                            :class="proveedorForm.plazo_credito_dias === 0 ? 'bg-amber-500 text-white font-bold' : 'bg-slate-100 dark:bg-dark-950 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-800'"
                            class="py-1.5 px-2 rounded-lg text-xs transition-colors cursor-pointer text-center">
                            Contado (0d)
                        </button>
                        <button type="button" @click="proveedorForm.plazo_credito_dias = 15"
                            :class="proveedorForm.plazo_credito_dias === 15 ? 'bg-amber-500 text-white font-bold' : 'bg-slate-100 dark:bg-dark-950 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-800'"
                            class="py-1.5 px-2 rounded-lg text-xs transition-colors cursor-pointer text-center">
                            15 días
                        </button>
                        <button type="button" @click="proveedorForm.plazo_credito_dias = 30"
                            :class="proveedorForm.plazo_credito_dias === 30 ? 'bg-amber-500 text-white font-bold' : 'bg-slate-100 dark:bg-dark-950 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-800'"
                            class="py-1.5 px-2 rounded-lg text-xs transition-colors cursor-pointer text-center">
                            30 días
                        </button>
                        <button type="button" @click="proveedorForm.plazo_credito_dias = 45"
                            :class="proveedorForm.plazo_credito_dias === 45 ? 'bg-amber-500 text-white font-bold' : 'bg-slate-100 dark:bg-dark-950 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-800'"
                            class="py-1.5 px-2 rounded-lg text-xs transition-colors cursor-pointer text-center">
                            45 días
                        </button>
                    </div>

                    <div class="relative">
                        <input type="number"
                            x-model.number="proveedorForm.plazo_credito_dias"
                            min="0"
                            max="365"
                            placeholder="Días de crédito"
                            class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700 rounded-xl pl-9 pr-12 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 transition-colors font-mono">
                        <div class="absolute left-3 top-2.5 text-slate-400 pointer-events-none">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                        </div>
                        <span class="absolute right-3 top-2 text-xs text-slate-400 font-medium">días</span>
                    </div>
                </div>

                <!-- Estado del Proveedor -->
                <div x-show="isEditingProveedor" class="pt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox"
                            :checked="proveedorForm.estado === 1"
                            @change="proveedorForm.estado = $event.target.checked ? 1 : 0"
                            class="w-4 h-4 rounded text-amber-500 focus:ring-amber-500 border-slate-300 dark:border-slate-700 dark:bg-dark-950">
                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Proveedor Activo</span>
                    </label>
                </div>

                <!-- Footer Acciones -->
                <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2">
                    <button type="button" @click="showProveedorModal = false"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit"
                        :disabled="isSavingProveedor"
                        class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold shadow-md shadow-amber-500/20 transition-all flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <i data-lucide="check" class="w-4 h-4" x-show="!isSavingProveedor"></i>
                        <span x-text="isSavingProveedor ? 'Guardando...' : (isEditingProveedor ? 'Guardar Cambios' : 'Registrar Proveedor')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
