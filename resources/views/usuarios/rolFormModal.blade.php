{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Modal de Gestión de Roles y Permisos Dinámicos
    Archivo: resources/views/usuarios/rolFormModal.blade.php
    Propósito: Permite al Administrador crear y editar roles con permisos
               granulares almacenados en formato JSON en la base de datos.
    Controlador asociado: App\Http\Controllers\RolController
    =============================================================================
--}}

<div x-show="showRolModal" x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/75 backdrop-blur-sm overflow-y-auto">

    <div @click.away="showRolModal = false"
        class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl w-full max-w-2xl shadow-2xl overflow-hidden flex flex-col my-auto max-h-[92vh]">

        <!-- Encabezado del Modal -->
        <div class="px-5 sm:px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between flex-shrink-0 bg-white dark:bg-dark-900">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center border shadow-xs bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20">
                    <i data-lucide="shield-check" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-white flex items-center gap-2">
                        <span x-text="isEditingRol ? 'Editar Rol & Privilegios' : 'Nuevo Rol & Privilegios'"></span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Configura módulos permitidos para este perfil de usuario</p>
                </div>
            </div>
            <button type="button" @click="showRolModal = false"
                class="p-1.5 text-slate-400 hover:text-slate-700 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                title="Cerrar ventana">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Formulario -->
        <form @submit.prevent="saveRol()" class="overflow-y-auto p-5 sm:p-6 space-y-5">
            <!-- Datos Básicos del Rol -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Nombre del Rol <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" x-model="rolForm.nombre_rol" required maxlength="64"
                        :disabled="isEditingRol && rolForm.nombre_rol === 'Administrador'"
                        placeholder="Ej. Supervisor, Cajero, Bodeguero"
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3.5 py-2 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all disabled:opacity-60 disabled:cursor-not-allowed">
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Descripción
                    </label>
                    <input type="text" x-model="rolForm.descripcion_rol" maxlength="64"
                        placeholder="Breve propósito del rol"
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3.5 py-2 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all">
                </div>
            </div>

            <!-- Estado Activo / Inactivo -->
            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-slate-800">
                <div>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Estado del Rol</span>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Los roles inactivos no pueden asignarse a nuevos usuarios</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" :checked="Number(rolForm.estado) === 1"
                        :disabled="isEditingRol && rolForm.nombre_rol === 'Administrador'"
                        @change="rolForm.estado = $event.target.checked ? 1 : 0"
                        class="sr-only peer">
                    <div class="w-10 h-6 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                </label>
            </div>

            <!-- Matriz de Permisos / Módulos -->
            <div class="space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-800 pb-2">
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                            <i data-lucide="key" class="w-3.5 h-3.5 text-indigo-500"></i>
                            <span>Permisos y Módulos Autorizados</span>
                        </h4>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Selecciona a qué partes del sistema tiene acceso este rol</p>
                    </div>

                    <!-- Atajos de selección masiva -->
                    <div class="flex items-center gap-2">
                        <button type="button" @click="selectAllPermissions()"
                            class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-500/20 transition-all cursor-pointer">
                            Marcar Todos
                        </button>
                        <button type="button" @click="deselectAllPermissions()"
                            class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all cursor-pointer">
                            Desmarcar
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <template x-for="grupo in availablePermissionsGroups" :key="grupo.nombre">
                        <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-dark-950/70 space-y-2">
                            <div class="flex items-center justify-between border-b border-slate-200/80 dark:border-slate-800 pb-1.5">
                                <span class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                    <i :data-lucide="grupo.icono" class="w-3.5 h-3.5 text-indigo-500"></i>
                                    <span x-text="grupo.nombre"></span>
                                </span>
                                <button type="button" @click="toggleGroupPermissions(grupo)"
                                    class="text-[10px] text-indigo-500 hover:underline cursor-pointer">
                                    Alternar
                                </button>
                            </div>

                            <div class="space-y-1.5 pt-0.5">
                                <template x-for="permiso in grupo.permisos" :key="permiso.clave">
                                    <label class="flex items-start gap-2 cursor-pointer text-xs group">
                                        <input type="checkbox" :value="permiso.clave"
                                            :checked="hasRolPermission(permiso.clave)"
                                            @change="toggleRolPermission(permiso.clave)"
                                            class="mt-0.5 rounded border-slate-300 dark:border-slate-700 text-brand-600 focus:ring-brand-500">
                                        <div>
                                            <span class="font-medium text-slate-800 dark:text-slate-200 group-hover:text-brand-500 dark:group-hover:text-brand-400 transition-colors" x-text="permiso.etiqueta"></span>
                                            <p class="text-[10px] text-slate-400 leading-tight" x-text="permiso.descripcion"></p>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                <button type="button" @click="showRolModal = false"
                    class="px-4 py-2 text-xs sm:text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors cursor-pointer">
                    Cancelar
                </button>
                <button type="submit" :disabled="isSavingRol"
                    class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/25 ring-2 ring-indigo-500/20 transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!isSavingRol" class="flex items-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        <span x-text="isEditingRol ? 'Actualizar Rol' : 'Guardar Rol'"></span>
                    </span>
                    <span x-show="isSavingRol" class="flex items-center gap-2">
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                        <span>Guardando...</span>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>
