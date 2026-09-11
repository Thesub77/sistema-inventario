{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Listado de Usuarios
    Archivo: resources/views/usuarios/usuarioView.blade.php
    Propósito: Permite la visualización y gestión de usuarios de forma modular.
    Controlador asociado: App\Http\Controllers\UsuarioController
    Modelo: App\Models\Usuario
    Integración: Incluido en welcome.blade.php mediante @include('usuarios.usuarioView') 
    =============================================================================
--}}

<div x-show="currentTab === 'usuarios'" x-cloak class="space-y-5">
    <div class="flex items-center justify-between glass-panel p-4 rounded-2xl">
        <h3 class="text-sm font-semibold text-slate-300">Gestión de Usuarios del Sistema</h3>
        <button @click="openUserModal()" class="flex items-center gap-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold px-4 py-2 rounded-xl transition-all">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            <span>Nuevo Usuario</span>
        </button>
    </div>

    <div class="glass-panel rounded-2xl overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="text-xs uppercase bg-dark-900/80 text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="py-3 px-4">Nombre Completo</th>
                    <th class="py-3 px-4">Usuario</th>
                    <th class="py-3 px-4">Rol Asignado</th>
                    <th class="py-3 px-4">Fecha Registro</th>
                    <th class="py-3 px-4 text-center">Estado</th>
                    <th class="py-3 px-4 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                <template x-for="u in usuarios" :key="u.usuario_id">
                    <tr class="hover:bg-slate-800/40">
                        <td class="py-3.5 px-4 font-semibold text-slate-100" x-text="u.nombre_apellido"></td>
                        <td class="py-3.5 px-4 font-mono text-xs text-brand-300" x-text="u.nombre_usuario"></td>
                        <td class="py-3.5 px-4">
                            <span class="px-2.5 py-1 text-xs rounded-lg bg-brand-500/10 text-brand-400 font-semibold border border-brand-500/20"
                                x-text="u.rol ? u.rol.nombre_rol : 'Sin rol'"></span>
                        </td>
                        <td class="py-3.5 px-4 text-xs text-slate-400" x-text="u.fecha_registro"></td>
                        <td class="py-3.5 px-4 text-center">
                            <span class="px-2 py-0.5 text-xs rounded-full"
                                :class="u.estado == 1 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-700 text-slate-400'"
                                x-text="u.estado == 1 ? 'Activo' : 'Inactivo'"></span>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <button @click="openUserModal(u)" class="p-1.5 text-slate-400 hover:text-brand-400 hover:bg-slate-800 rounded-lg">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                </button>
                                <button @click="deleteUser(u)" class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-slate-800 rounded-lg">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>