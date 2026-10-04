{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Modal de Restablecimiento de Contraseñas
    Archivo: resources/views/usuarios/usuarioPasswordModal.blade.php
    Propósito: Permite al Administrador restablecer de forma directa y segura
               la contraseña de cualquier usuario sin necesidad de editar todo el perfil.
    Controlador asociado: App\Http\Controllers\UsuarioController
    =============================================================================
--}}

<div x-show="showPasswordModal" x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/75 backdrop-blur-sm overflow-y-auto">

    <div @click.away="showPasswordModal = false"
        class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col my-auto transition-all">

        <!-- Encabezado del Modal -->
        <div class="px-5 sm:px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between flex-shrink-0 bg-white dark:bg-dark-900">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center border shadow-xs bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20">
                    <i data-lucide="key-round" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-white flex items-center gap-2">
                        <span>Restablecer Contraseña</span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Asigna una nueva credencial de acceso para el usuario</p>
                </div>
            </div>
            <button type="button" @click="showPasswordModal = false"
                class="p-1.5 text-slate-400 hover:text-slate-700 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                title="Cerrar ventana">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Formulario -->
        <form @submit.prevent="saveResetPassword()" class="p-5 sm:p-6 space-y-4">
            <!-- Datos del Usuario Destino -->
            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400">Usuario a actualizar:</span>
                    <div class="font-bold text-slate-900 dark:text-white text-sm" x-text="passwordUser?.nombre_apellido"></div>
                    <div class="text-xs text-brand-500 font-mono" x-text="'@' + (passwordUser?.nombre_usuario || '')"></div>
                </div>
                <div class="px-2.5 py-1 text-xs rounded-lg font-semibold border"
                    :class="passwordUser?.estado == 1 ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 border-slate-200 dark:border-slate-700'"
                    x-text="passwordUser?.estado == 1 ? 'Activo' : 'Inactivo'"></div>
            </div>

            <!-- Entrada de la Nueva Contraseña -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Nueva Contraseña <span class="text-rose-500">*</span>
                    </label>
                    <button type="button" @click="generateRandomPassword()"
                        class="text-xs text-brand-500 hover:text-brand-600 font-semibold hover:underline flex items-center gap-1 cursor-pointer">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                        <span>Generar Aleatoria</span>
                    </button>
                </div>
                <div class="relative">
                    <input :type="showPasswordPlainText ? 'text' : 'password'"
                        x-model="newPasswordValue" required minlength="6" maxlength="256"
                        placeholder="Mínimo 6 caracteres"
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl pl-3.5 pr-10 py-2.5 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all font-mono">
                    <button type="button" @click="showPasswordPlainText = !showPasswordPlainText"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors cursor-pointer"
                        title="Mostrar u ocultar contraseña">
                        <i :data-lucide="showPasswordPlainText ? 'eye-off' : 'eye'" class="w-4 h-4"></i>
                    </button>
                </div>
                <p class="text-[11px] text-slate-400">
                    Requisito de seguridad: Mínimo 6 caracteres.
                </p>
            </div>

            <!-- Botón para copiar contraseña generada -->
            <div x-show="newPasswordValue && newPasswordValue.length >= 6" class="flex justify-end">
                <button type="button" @click="copyGeneratedPassword()"
                    class="text-xs font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 flex items-center gap-1 cursor-pointer">
                    <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                    <span>Copiar contraseña al portapapeles</span>
                </button>
            </div>

            <!-- Botones de Acción -->
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                <button type="button" @click="showPasswordModal = false"
                    class="px-4 py-2 text-xs sm:text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors cursor-pointer">
                    Cancelar
                </button>
                <button type="submit" :disabled="isSavingPassword || !newPasswordValue || newPasswordValue.length < 6"
                    class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold bg-amber-600 hover:bg-amber-500 text-white shadow-lg shadow-amber-600/25 ring-2 ring-amber-500/20 transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!isSavingPassword" class="flex items-center gap-2">
                        <i data-lucide="key" class="w-4 h-4"></i>
                        <span>Actualizar Contraseña</span>
                    </span>
                    <span x-show="isSavingPassword" class="flex items-center gap-2">
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                        <span>Guardando...</span>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>
