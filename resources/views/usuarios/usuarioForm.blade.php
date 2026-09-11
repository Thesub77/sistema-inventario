{{-- 
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Formulario Modal de Usuario
    Archivo: resources/views/usuarios/usuarioForm.blade.php
    Propósito: Permite la creación y edición de usuarios de forma modular.
    Controlador asociado: App\Http\Controllers\UsuarioController
    Modelo: App\Models\Usuario
    Integración: Incluido en welcome.blade.php mediante @include('usuarios.usuarioForm') 
    =============================================================================
--}}

<div x-show="showUserModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div @click.away="showUserModal = false" class="bg-dark-900 border border-slate-700 rounded-2xl w-full max-w-md p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="font-display font-bold text-lg text-white" x-text="isEditingUser ? 'Editar Usuario' : 'Nuevo Usuario'"></h3>
                <button @click="showUserModal = false" class="text-slate-400 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form @submit.prevent="saveUser()" class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Rol</label>
                    <select x-model="userForm.id_rol" required class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white">
                        <option value="">Selecciona un rol</option>
                        <template x-for="r in roles" :key="r.rol_id">
                            <option :value="r.rol_id" x-text="r.nombre_rol"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Nombre Completo</label>
                    <input type="text" x-model="userForm.nombre_apellido" required placeholder="Nombre y Apellido" 
                           class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Nombre de Usuario</label>
                    <input type="text" x-model="userForm.nombre_usuario" required placeholder="usuario123" 
                           class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1" x-text="isEditingUser ? 'Contraseña (dejar en blanco para no cambiar)' : 'Contraseña'"></label>
                    <input type="password" x-model="userForm.contrasenia_usuario" :required="!isEditingUser" placeholder="••••••••" 
                           class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white">
                </div>

                <div class="pt-4 flex justify-end gap-2 border-t border-slate-800">
                    <button type="button" @click="showUserModal = false" class="px-4 py-2 text-sm text-slate-400 hover:text-white">Cancelar</button>
                    <button type="submit" class="px-5 py-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold rounded-xl">Guardar Usuario</button>
                </div>
            </form>
        </div>
    </div>