{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Listado de Clientes
    Archivo: resources/views/clientes/clienteView.blade.php
    Propósito: Permite la visualización y gestión de clientes de forma modular.
    Controlador asociado: App\Http\Controllers\ClienteController
    Modelo: App\Models\Cliente
    Integración: Incluido en welcome.blade.php mediante @include('clientes.clienteView') 
    =============================================================================
--}}

<div x-show="currentTab === 'clientes'" x-cloak class="space-y-5">
    <div class="flex items-center justify-between glass-panel p-4 rounded-2xl">
        <div class="relative w-full sm:w-80">
            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
            <input type="text" x-model="searchCliente" placeholder="Buscar cliente por nombre, teléfono..."
                class="w-full bg-dark-900 border border-slate-700/80 rounded-xl pl-10 pr-4 py-2 text-sm text-slate-100 placeholder-slate-400 focus:outline-none focus:border-brand-500">
        </div>
        <button @click="openCustomerModal()" class="flex items-center gap-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold px-4 py-2 rounded-xl transition-all">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            <span>Nuevo Cliente</span>
        </button>
    </div>

    <div class="glass-panel rounded-2xl overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="text-xs uppercase bg-dark-900/80 text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="py-3 px-4">Código</th>
                    <th class="py-3 px-4">Nombre / Razón Social</th>
                    <th class="py-3 px-4">Teléfono</th>
                    <th class="py-3 px-4 text-center">Estado</th>
                    <th class="py-3 px-4 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                <template x-for="c in filteredClientes" :key="c.cliente_id">
                    <tr class="hover:bg-slate-800/40 transition-colors">
                        <td class="py-3.5 px-4 font-mono text-xs font-semibold text-brand-300" x-text="c.codigo_cliente || 'N/A'"></td>
                        <td class="py-3.5 px-4 font-semibold text-slate-100" x-text="c.nombre_apellido_cliente"></td>
                        <td class="py-3.5 px-4 text-slate-400" x-text="c.telefono_cliente || 'Sin teléfono'"></td>
                        <td class="py-3.5 px-4 text-center">
                            <span class="px-2 py-0.5 text-xs rounded-full"
                                :class="c.estado == 1 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-700 text-slate-400'"
                                x-text="c.estado == 1 ? 'Activo' : 'Inactivo'"></span>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <button @click="openCustomerModal(c)" class="p-1.5 text-slate-400 hover:text-brand-400 hover:bg-slate-800 rounded-lg">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                </button>
                                <button @click="deleteCustomer(c)" class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-slate-800 rounded-lg">
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