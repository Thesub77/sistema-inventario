{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Listado de Ventas
    Archivo: resources/views/ventas/ventaHistorialView.blade.php
    Propósito: Permite la visualización y gestión de ventas de forma modular.
    Controlador asociado: App\Http\Controllers\VentaController
    Modelo: App\Models\Venta
    Integración: Incluido en welcome.blade.php mediante @include('ventas.ventaHistorialView') 
    =============================================================================
--}}

<div x-show="currentTab === 'ventas'" x-cloak class="space-y-5">
    <div class="glass-panel p-4 rounded-2xl flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="relative w-full sm:w-80">
            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
            <input type="text" x-model="searchVenta" placeholder="Buscar por código de factura o cliente..."
                class="w-full bg-dark-900 border border-slate-700/80 rounded-xl pl-10 pr-4 py-2 text-sm text-slate-100 placeholder-slate-400 focus:outline-none focus:border-brand-500">
        </div>
        <button @click="currentTab = 'pos'" class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold px-4 py-2 rounded-xl transition-all">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>Nueva Venta</span>
        </button>
    </div>

    <div class="glass-panel rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase bg-dark-900/80 text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Factura</th>
                        <th class="py-3 px-4">Cliente</th>
                        <th class="py-3 px-4">Vendedor / Usuario</th>
                        <th class="py-3 px-4">Método</th>
                        <th class="py-3 px-4">Fecha y Hora</th>
                        <th class="py-3 px-4 text-right">Subtotal</th>
                        <th class="py-3 px-4 text-right">Total</th>
                        <th class="py-3 px-4 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <template x-for="v in filteredVentas" :key="v.venta_id">
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="py-3.5 px-4 font-mono font-bold text-brand-300" x-text="v.codigo_venta"></td>
                            <td class="py-3.5 px-4 font-medium text-slate-200" x-text="v.cliente ? v.cliente.nombre_apellido_cliente : 'Consumidor Final'"></td>
                            <td class="py-3.5 px-4 text-xs text-slate-400" x-text="v.usuario ? v.usuario.nombre_apellido : 'N/A'"></td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 text-xs rounded-md font-medium"
                                    :class="v.metodo_pago === 'Efectivo' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-brand-500/10 text-brand-400'"
                                    x-text="v.metodo_pago"></span>
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-400" x-text="formatDate(v.fecha_hora_venta)"></td>
                            <td class="py-3.5 px-4 text-right text-slate-400" x-text="formatCurrency(v.subtotal_venta)"></td>
                            <td class="py-3.5 px-4 text-right font-bold text-white" x-text="formatCurrency(v.total_venta)"></td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button @click="viewSaleDetails(v)" class="p-1.5 text-slate-400 hover:text-brand-400 hover:bg-slate-800 rounded-lg transition-colors" title="Ver Detalles de Factura">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </button>
                                    <button @click="deleteSale(v)" class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-slate-800 rounded-lg transition-colors" title="Anular / Eliminar Venta">
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
</div>