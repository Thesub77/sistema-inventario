{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Formulario Modal de Cliente
    Archivo: resources/views/clientes/clienteForm.blade.php
    Propósito: Permite la creación y edición de clientes de forma modular.
    Controlador asociado: App\Http\Controllers\ClienteController
    Modelo: App\Models\Cliente
    Integración: Incluido en welcome.blade.php mediante @include('clientes.clienteForm') 
    =============================================================================
--}}

<div x-show="showCustomerModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
    <div @click.away="showCustomerModal = false" class="bg-dark-900 border border-slate-700 rounded-2xl w-full max-w-md p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-display font-bold text-lg text-white" x-text="isEditingCustomer ? 'Editar Cliente' : 'Nuevo Cliente'"></h3>
            <button @click="showCustomerModal = false" class="text-slate-400 hover:text-white">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form @submit.prevent="saveCustomer()" class="space-y-3">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Código</label>
                <input type="text" x-model="customerForm.codigo_cliente" placeholder="CLI-001"
                    class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Nombre y Apellido / Empresa</label>
                <input type="text" x-model="customerForm.nombre_apellido_cliente" required placeholder="Ej. Juan Pérez"
                    class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Teléfono</label>
                <input type="text" x-model="customerForm.telefono_cliente" placeholder="8888-8888"
                    class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white">
            </div>

            <div class="pt-4 flex justify-end gap-2 border-t border-slate-800">
                <button type="button" @click="showCustomerModal = false" class="px-4 py-2 text-sm text-slate-400 hover:text-white">Cancelar</button>
                <button type="submit" class="px-5 py-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold rounded-xl">Guardar Cliente</button>
            </div>
        </form>
    </div>
</div>