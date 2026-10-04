{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Gestión Integral de Usuarios y Roles (RF-Admin)
    Archivo: resources/views/usuarios/usuarioView.blade.php
    Propósito: Permite al Administrador gestionar usuarios, bloquear/desbloquear,
               restablecer contraseñas de acceso y administrar roles dinámicos con permisos JSON.
    Controladores asociados: App\Http\Controllers\UsuarioController
                             App\Http\Controllers\RolController
    =============================================================================
--}}

<div x-show="currentTab === 'usuarios'" x-cloak class="space-y-6">

    <!-- 1. Encabezado Principal y Barra de Acciones -->
    <div class="glass-panel p-5 sm:p-6 rounded-3xl relative overflow-hidden border border-slate-200 dark:border-slate-800">
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-brand-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
                    <span>Seguridad & Control de Acceso</span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-display font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Usuarios & <span class="text-indigo-600 dark:text-indigo-400">Roles del Sistema</span>
                </h2>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-2xl">
                    Administración de cuentas de usuarios, asignación de perfiles, restablecimiento de credenciales y configuración granular de privilegios.
                </p>
            </div>

            <!-- Botones de Acción -->
            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Botón: Crear Usuario -->
                <button type="button" @click="openUserModal()"
                    class="px-4 py-2.5 rounded-2xl bg-brand-600 hover:bg-brand-500 text-white text-xs sm:text-sm font-bold shadow-lg shadow-brand-600/25 ring-2 ring-brand-500/20 transition-all flex items-center gap-2 cursor-pointer">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    <span>Nuevo Usuario</span>
                </button>

                <!-- Botón: Gestionar / Nuevo Rol -->
                <button type="button" @click="openRolModal()"
                    class="px-3.5 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs sm:text-sm font-bold shadow-lg shadow-indigo-600/25 ring-2 ring-indigo-500/20 transition-all flex items-center gap-2 cursor-pointer">
                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                    <span>Nuevo Rol</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 2. Tarjetas de Resumen Rápido (KPIs) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total de Usuarios -->
        <div class="glass-panel p-4 rounded-2xl border border-slate-200 dark:border-slate-800/90 relative overflow-hidden">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Usuarios</span>
                <div class="w-8 h-8 rounded-lg bg-brand-500/10 text-brand-500 flex items-center justify-center">
                    <i data-lucide="users" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="text-2xl font-display font-black text-slate-900 dark:text-white"
                x-text="usuarios.length"></div>
            <div class="mt-2 text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between">
                <span>Cuentas creadas:</span>
                <span class="font-bold text-slate-700 dark:text-slate-200" x-text="usuarios.length + ' usuarios'"></span>
            </div>
        </div>

        <!-- Usuarios Activos -->
        <div class="glass-panel p-4 rounded-2xl border border-slate-200 dark:border-slate-800/90 relative overflow-hidden">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Usuarios Habilitados</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-500 flex items-center justify-center">
                    <i data-lucide="user-check" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="text-2xl font-display font-black text-emerald-600 dark:text-emerald-400"
                x-text="usuarios.filter(u => Number(u.estado) === 1).length"></div>
            <div class="mt-2 text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between">
                <span>Con acceso al sistema:</span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400" x-text="usuarios.filter(u => Number(u.estado) === 1).length + ' activos'"></span>
            </div>
        </div>

        <!-- Usuarios Inactivos / Bloqueados -->
        <div class="glass-panel p-4 rounded-2xl border border-slate-200 dark:border-slate-800/90 relative overflow-hidden">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">Inhabilitados / Bloqueados</span>
                <div class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-500 flex items-center justify-center">
                    <i data-lucide="user-x" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="text-2xl font-display font-black text-rose-600 dark:text-rose-400"
                x-text="usuarios.filter(u => Number(u.estado) === 0).length"></div>
            <div class="mt-2 text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between">
                <span>Sin acceso:</span>
                <span class="font-bold text-rose-600 dark:text-rose-400" x-text="usuarios.filter(u => Number(u.estado) === 0).length + ' bloqueados'"></span>
            </div>
        </div>

        <!-- Roles Configurados -->
        <div class="glass-panel p-4 rounded-2xl border border-slate-200 dark:border-slate-800/90 relative overflow-hidden">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Roles Configurados</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-500 flex items-center justify-center">
                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="text-2xl font-display font-black text-indigo-600 dark:text-indigo-400"
                x-text="roles.length"></div>
            <div class="mt-2 text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between">
                <span>Perfiles de seguridad:</span>
                <span class="font-bold text-slate-700 dark:text-slate-200" x-text="roles.length + ' roles'"></span>
            </div>
        </div>
    </div>

    <!-- 3. Selector de Subpestaña: Usuarios vs Roles & Privilegios -->
    <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-2">
        <button type="button" @click="usuariosSubTab = 'usuarios'"
            :class="usuariosSubTab === 'usuarios'
                ? 'bg-brand-600 text-white font-bold shadow-sm'
                : 'bg-slate-100 dark:bg-dark-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
            class="px-4 py-2 rounded-xl text-xs sm:text-sm transition-all flex items-center gap-2 cursor-pointer">
            <i data-lucide="users" class="w-4 h-4"></i>
            <span>Cuentas de Usuarios</span>
            <span class="px-1.5 py-0.2 rounded-md text-[10px] bg-white/20" x-text="usuarios.length"></span>
        </button>

        <button type="button" @click="usuariosSubTab = 'roles'"
            :class="usuariosSubTab === 'roles'
                ? 'bg-indigo-600 text-white font-bold shadow-sm'
                : 'bg-slate-100 dark:bg-dark-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
            class="px-4 py-2 rounded-xl text-xs sm:text-sm transition-all flex items-center gap-2 cursor-pointer">
            <i data-lucide="shield" class="w-4 h-4"></i>
            <span>Roles & Privilegios Dinámicos</span>
            <span class="px-1.5 py-0.2 rounded-md text-[10px] bg-white/20" x-text="roles.length"></span>
        </button>
    </div>

    <!-- 4. SUBPESTAÑA: Listado y Gestión de Cuentas de Usuario -->
    <div x-show="usuariosSubTab === 'usuarios'" class="space-y-4">
        <!-- Filtros y Buscador de Usuarios -->
        <div class="glass-panel p-4 rounded-2xl border border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3 flex-1 min-w-[280px]">
                <!-- Buscador de usuarios -->
                <div class="relative flex-1 min-w-[220px]">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                    <input type="text" x-model="userSearchQuery" placeholder="Buscar por nombre o @usuario..."
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-slate-700/80 rounded-xl pl-9 pr-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-brand-500 transition-colors">
                </div>

                <!-- Filtro por Rol -->
                <div class="w-auto">
                    <select x-model="userRoleFilter"
                        class="bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:border-brand-500 transition-colors cursor-pointer">
                        <option value="">Todos los Roles</option>
                        <template x-for="r in roles" :key="r.rol_id">
                            <option :value="r.rol_id" x-text="r.nombre_rol"></option>
                        </template>
                    </select>
                </div>

                <!-- Filtro por Estado -->
                <div class="w-auto">
                    <select x-model="userStatusFilter"
                        class="bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:border-brand-500 transition-colors cursor-pointer">
                        <option value="">Todos los Estados</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos / Bloqueados</option>
                    </select>
                </div>
            </div>

            <div class="text-xs text-slate-500 dark:text-slate-400">
                Mostrando <span class="font-bold text-slate-800 dark:text-slate-200" x-text="filteredUsersList.length"></span> de <span x-text="usuarios.length"></span> usuarios
            </div>
        </div>

        <!-- Tabla de Usuarios -->
        <div class="glass-panel rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="text-xs uppercase bg-slate-50 dark:bg-dark-900/80 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="py-3 px-4 font-semibold">Usuario & Nombre</th>
                            <th class="py-3 px-4 font-semibold">Rol Asignado</th>
                            <th class="py-3 px-4 font-semibold">Fecha Registro</th>
                            <th class="py-3 px-4 text-center font-semibold">Estado</th>
                            <th class="py-3 px-4 text-center font-semibold">Acciones Rápidas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                        <template x-for="u in filteredUsersList" :key="u.usuario_id">
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors"
                                :class="Number(u.estado) === 0 ? 'bg-slate-50/50 dark:bg-slate-900/40 opacity-80' : ''">
                                <!-- Nombre y Usuario -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-sm shadow-xs border"
                                            :class="Number(u.estado) === 1
                                                ? 'bg-brand-500/10 text-brand-600 dark:text-brand-400 border-brand-500/20'
                                                : 'bg-slate-100 dark:bg-slate-800 text-slate-400 border-slate-200 dark:border-slate-700'">
                                            <span x-text="(u.nombre_apellido || 'U').charAt(0).toUpperCase()"></span>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="font-bold text-slate-900 dark:text-white" x-text="u.nombre_apellido"></span>
                                                <!-- Badge si es el usuario en sesión actual -->
                                                <template x-if="currentUser && currentUser.usuario_id === u.usuario_id">
                                                    <span class="px-1.5 py-0.2 text-[9px] font-extrabold uppercase rounded bg-indigo-500/20 text-indigo-700 dark:text-indigo-300">
                                                        Tú
                                                    </span>
                                                </template>
                                            </div>
                                            <div class="font-mono text-xs text-brand-600 dark:text-brand-400" x-text="'@' + u.nombre_usuario"></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Rol Asignado -->
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs rounded-lg font-semibold border"
                                        :class="(u.rol && u.rol.nombre_rol === 'Administrador')
                                            ? 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border-indigo-500/25'
                                            : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700'"
                                        x-text="u.rol ? u.rol.nombre_rol : 'Sin rol'"></span>
                                </td>

                                <!-- Fecha Registro -->
                                <td class="py-3.5 px-4 text-xs text-slate-500 dark:text-slate-400"
                                    x-text="u.fecha_registro ? formatDateOnly(u.fecha_registro) : 'Sin fecha'"></td>

                                <!-- Estado (Activo / Inactivo) -->
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-xs rounded-full font-bold border"
                                        :class="Number(u.estado) === 1
                                            ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/25'
                                            : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/25'">
                                        <span class="w-1.5 h-1.5 rounded-full"
                                            :class="Number(u.estado) === 1 ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500'"></span>
                                        <span x-text="Number(u.estado) === 1 ? 'Activo' : 'Bloqueado'"></span>
                                    </span>
                                </td>

                                <!-- Acciones Rápidas -->
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <!-- Botón: Editar Usuario -->
                                        <button type="button" @click="openUserModal(u)"
                                            class="p-2 text-slate-400 hover:text-brand-500 dark:hover:text-brand-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all cursor-pointer"
                                            title="Editar datos del usuario">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </button>

                                        <!-- Botón: Restablecer Contraseña -->
                                        <button type="button" @click="openPasswordModal(u)"
                                            class="p-2 text-slate-400 hover:text-amber-500 dark:hover:text-amber-400 hover:bg-amber-500/10 rounded-xl transition-all cursor-pointer"
                                            title="Restablecer contraseña de acceso">
                                            <i data-lucide="key-round" class="w-4 h-4 text-amber-500"></i>
                                        </button>

                                        <!-- Botón: Bloquear / Desbloquear (Toggle) -->
                                        <button type="button"
                                            @click="toggleUserStatus(u)"
                                            :disabled="currentUser && currentUser.usuario_id === u.usuario_id"
                                            class="p-2 rounded-xl transition-all cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed"
                                            :class="Number(u.estado) === 1
                                                ? 'text-slate-400 hover:text-rose-500 hover:bg-rose-500/10'
                                                : 'text-rose-500 hover:text-emerald-500 hover:bg-emerald-500/10'"
                                            :title="currentUser && currentUser.usuario_id === u.usuario_id
                                                ? 'No puedes bloquear tu propia cuenta'
                                                : (Number(u.estado) === 1 ? 'Bloquear acceso al usuario' : 'Habilitar acceso al usuario')">
                                            <i :data-lucide="Number(u.estado) === 1 ? 'lock' : 'unlock'" class="w-4 h-4"></i>
                                        </button>

                                        <!-- Botón: Eliminar Usuario -->
                                        <button type="button" @click="deleteUser(u)"
                                            :disabled="currentUser && currentUser.usuario_id === u.usuario_id"
                                            class="p-2 text-slate-400 hover:text-rose-500 hover:bg-rose-500/10 rounded-xl transition-all cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed"
                                            :title="currentUser && currentUser.usuario_id === u.usuario_id
                                                ? 'No puedes eliminar tu propia cuenta'
                                                : 'Eliminar o desactivar usuario'">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Estado Vacío -->
                        <tr x-show="filteredUsersList.length === 0">
                            <td colspan="5" class="py-12 text-center text-slate-400 space-y-2">
                                <i data-lucide="users" class="w-8 h-8 mx-auto text-slate-400"></i>
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">No se encontraron usuarios</p>
                                <p class="text-xs text-slate-400">Intenta ajustar los criterios de búsqueda o filtros aplicados.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 5. SUBPESTAÑA: Listado y Gestión de Roles y Permisos Dinámicos -->
    <div x-show="usuariosSubTab === 'roles'" class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="font-display font-bold text-base text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="shield-check" class="w-4 h-4 text-indigo-500"></i>
                    <span>Catálogo de Roles y Privilegios en el Sistema</span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Cada rol define los módulos y acciones autorizadas en la base de datos (campo JSON).
                </p>
            </div>
            <button type="button" @click="openRolModal()"
                class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs flex items-center gap-1.5 transition-all cursor-pointer">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>Nuevo Rol</span>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="r in roles" :key="r.rol_id">
                <div class="glass-panel p-5 rounded-2xl border flex flex-col justify-between transition-all"
                    :class="(r.nombre_rol === 'Administrador')
                        ? 'border-indigo-500/40 bg-indigo-500/5'
                        : (Number(r.estado) === 0
                            ? 'border-slate-200 dark:border-slate-800 opacity-70 bg-slate-50/50 dark:bg-slate-900/30'
                            : 'border-slate-200 dark:border-slate-800')">
                    <div class="space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="font-display font-bold text-base text-slate-900 dark:text-white" x-text="r.nombre_rol"></h4>
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full"
                                        :class="Number(r.estado) === 1
                                            ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20'
                                            : 'bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700'"
                                        x-text="Number(r.estado) === 1 ? 'Activo' : 'Inactivo'"></span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5" x-text="r.descripcion_rol || 'Sin descripción'"></p>
                            </div>

                            <button type="button" @click="openRolModal(r)"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                                title="Editar rol y privilegios">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                        </div>

                        <!-- Resumen de permisos asignados -->
                        <div class="space-y-1.5 pt-2 border-t border-slate-200 dark:border-slate-800">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Privilegios Concedidos:</span>
                            <div class="flex flex-wrap gap-1">
                                <template x-if="r.nombre_rol === 'Administrador' || (Array.isArray(r.permisos) && r.permisos.includes('*'))">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 border border-indigo-500/30">
                                        Acceso Total SuperAdmin (*)
                                    </span>
                                </template>
                                <template x-if="r.nombre_rol !== 'Administrador' && (!Array.isArray(r.permisos) || !r.permisos.includes('*')) && Array.isArray(r.permisos) && r.permisos.length > 0">
                                    <template x-for="p in r.permisos.slice(0, 4)" :key="p">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700"
                                            x-text="p"></span>
                                    </template>
                                </template>
                                <template x-if="r.nombre_rol !== 'Administrador' && (!Array.isArray(r.permisos) || !r.permisos.includes('*')) && Array.isArray(r.permisos) && r.permisos.length > 4">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-brand-500/10 text-brand-600 dark:text-brand-400"
                                        x-text="'+' + (r.permisos.length - 4) + ' más'"></span>
                                </template>
                                <template x-if="(!Array.isArray(r.permisos) || r.permisos.length === 0) && r.nombre_rol !== 'Administrador'">
                                    <span class="text-[11px] text-slate-400 italic">Sin permisos explícitos</span>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Pie de la tarjeta -->
                    <div class="pt-3 mt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                        <span x-text="(r.usuarios_count !== undefined ? r.usuarios_count : countUsersInRole(r.rol_id)) + ' usuarios asignados'"></span>
                        <div class="flex items-center gap-1">
                            <button type="button" @click="openRolModal(r)"
                                class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline cursor-pointer">
                                Configurar
                            </button>
                            <template x-if="r.nombre_rol !== 'Administrador'">
                                <button type="button" @click="deleteRol(r)"
                                    class="p-1 text-slate-400 hover:text-rose-500 transition-colors cursor-pointer"
                                    title="Eliminar rol">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>