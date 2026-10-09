{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Formulario Modal de Usuario (Creación y Edición)
    Archivo: resources/views/usuarios/usuarioForm.blade.php
    Propósito: Permite crear y modificar usuarios, asignando roles, nombres y contraseñas.
    Controlador asociado: App\Http\Controllers\UsuarioController
    =============================================================================
--}}

<div x-show="showUserModal" x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/75 backdrop-blur-sm overflow-y-auto">

    <div @click.away="showUserModal = false"
        class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden flex flex-col my-auto transition-all">

        <!-- Encabezado del Modal -->
        <div class="px-5 sm:px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between flex-shrink-0 bg-white dark:bg-dark-900">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center border shadow-xs bg-brand-500/10 text-brand-600 dark:text-brand-400 border-brand-500/20">
                    <i data-lucide="user-cog" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-white"
                        x-text="isEditingUser ? 'Editar Cuenta de Usuario' : 'Nueva Cuenta de Usuario'"></h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Credenciales de acceso y perfil operativo</p>
                </div>
            </div>
            <button type="button" @click="showUserModal = false"
                class="p-1.5 text-slate-400 hover:text-slate-700 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                title="Cerrar ventana">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Formulario -->
        <form @submit.prevent="saveUser()" class="p-5 sm:p-6 space-y-4">
            <!-- Selección de Rol y Botón para Administrar Roles -->
            <template x-if="canChangeUserRole">
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Rol & Perfil de Acceso <span class="text-rose-500">*</span>
                        </label>
                        <button type="button" @click="openRolModal()"
                            class="text-xs text-brand-500 hover:text-brand-600 font-semibold hover:underline flex items-center gap-1 cursor-pointer">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                            <span>Crear Nuevo Rol</span>
                        </button>
                    </div>
                    <select x-model.number="userForm.id_rol" required
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all cursor-pointer">
                        <option value="" disabled>Selecciona un rol</option>
                        <template x-for="r in roles" :key="r.rol_id">
                            <option :value="r.rol_id"
                                :disabled="Number(r.estado) === 0"
                                x-text="r.nombre_rol + (Number(r.estado) === 0 ? ' (Inactivo)' : '')"></option>
                        </template>
                    </select>
                    <!-- Resumen de permisos del rol seleccionado -->
                    <template x-if="selectedUserRoleDescription">
                        <div class="p-2.5 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-700 dark:text-indigo-300 text-xs flex items-start gap-2">
                            <i data-lucide="info" class="w-4 h-4 text-indigo-500 shrink-0 mt-0.5"></i>
                            <span x-text="selectedUserRoleDescription"></span>
                        </div>
                    </template>
                </div>
            </template>

            <!-- Banner Informativo de Rol Protegido (Cuenta Propia o Último Administrador) -->
            <template x-if="!canChangeUserRole">
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Rol & Perfil de Acceso
                    </label>
                    <div class="p-3.5 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                <i data-lucide="shield-check" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-900 dark:text-white"
                                    x-text="isEditingPrincipal ? 'Administrador Principal (Propietario)' : (isEditingSelf ? 'Tu Cuenta en Sesión' : 'Último Administrador del Sistema')"></p>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400"
                                    x-text="isEditingPrincipal ? 'El Administrador Principal posee privilegios inmutables en el sistema.' : (isEditingSelf ? 'No puedes modificar tu propio rol de acceso para evitar pérdida de permisos.' : 'No se puede modificar el rol al único Administrador activo del sistema.')"></p>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-lg bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 border border-indigo-500/30">
                            Rol Protegido
                        </span>
                    </div>
                </div>
            </template>

            <!-- Nombre Completo -->
            <div class="space-y-1.5">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Nombre Completo <span class="text-rose-500">*</span>
                </label>
                <input type="text" x-model="userForm.nombre_apellido" required minlength="3" maxlength="128"
                    placeholder="Ej. Carlos Mendoza"
                    class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all">
            </div>

            <!-- Nombre de Usuario (Identificador) -->
            <div class="space-y-1.5">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Nombre de Usuario (@usuario) <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 font-bold text-sm pointer-events-none font-mono">
                        @
                    </span>
                    <input type="text" x-model="userForm.nombre_usuario" required minlength="3" maxlength="24"
                        placeholder="cmendoza"
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl pl-8 pr-3.5 py-2.5 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all font-mono">
                </div>
            </div>

            <!-- Contraseña de Acceso -->
            <div class="space-y-1.5" x-show="!isEditingUser">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Contraseña Inicial <span class="text-rose-500">*</span>
                    </label>
                    <button type="button" @click="generateUserFormPassword()"
                        class="text-xs text-brand-500 hover:text-brand-600 font-semibold hover:underline flex items-center gap-1 cursor-pointer">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                        <span>Generar Aleatoria</span>
                    </button>
                </div>
                <div class="relative">
                    <input :type="showUserFormPassword ? 'text' : 'password'"
                        x-model="userForm.contrasenia_usuario" :required="!isEditingUser" minlength="6" maxlength="256"
                        placeholder="Mínimo 6 caracteres"
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl pl-3.5 pr-10 py-2.5 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all font-mono">
                    <button type="button" @click="showUserFormPassword = !showUserFormPassword"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors cursor-pointer"
                        title="Mostrar u ocultar contraseña">
                        <i :data-lucide="showUserFormPassword ? 'eye-off' : 'eye'" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <!-- Estado de la Cuenta (Acceso Habilitado / Bloqueado) -->
            <template x-if="canChangeUserStatus">
                <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-slate-800">
                    <div>
                        <span class="text-xs font-bold text-slate-900 dark:text-white">Acceso a la Cuenta</span>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                            <span :class="Number(userForm.bloqueado) === 0 ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-rose-500 font-semibold'"
                                x-text="Number(userForm.bloqueado) === 0 ? '✓ Acceso Habilitado (Desbloqueado)' : '🔒 Acceso Denegado (Bloqueado)'"></span>
                            - Permite al usuario iniciar sesión en el sistema
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" :checked="Number(userForm.bloqueado) === 0"
                            @change="userForm.bloqueado = $event.target.checked ? 0 : 1"
                            class="sr-only peer">
                        <div class="w-10 h-6 bg-rose-500 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>
            </template>

            <!-- Banner Informativo de Estado Protegido -->
            <template x-if="!canChangeUserStatus">
                <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-slate-800">
                    <div>
                        <span class="text-xs font-bold text-slate-900 dark:text-white">Acceso a la Cuenta</span>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400"
                            x-text="isEditingPrincipal ? 'La cuenta del Administrador Principal no puede ser bloqueada.' : (isEditingSelf ? 'No puedes bloquear tu propia cuenta en sesión.' : 'No se puede bloquear al único Administrador activo del sistema.')"></p>
                    </div>
                    <span class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                        Desbloqueado (Protegido)
                    </span>
                </div>
            </template>

            <!-- Botones de Acción -->
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                <button type="button" @click="showUserModal = false"
                    class="px-4 py-2 text-xs sm:text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors cursor-pointer">
                    Cancelar
                </button>
                <button type="submit" :disabled="isSavingUser"
                    class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold bg-brand-600 hover:bg-brand-500 text-white shadow-lg shadow-brand-600/25 ring-2 ring-brand-500/20 transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!isSavingUser" class="flex items-center gap-2">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span x-text="isEditingUser ? 'Guardar Cambios' : 'Crear Usuario'"></span>
                    </span>
                    <span x-show="isSavingUser" class="flex items-center gap-2">
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                        <span>Guardando...</span>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>