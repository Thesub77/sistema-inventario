{{-- 
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Formulario Modal de Venta
    Archivo: resources/views/ventas/ventaForm.blade.php
    Propósito: Permite la creación y edición de usuarios de forma modular.
    Controlador asociado: App\Http\Controllers\VentaController
    Modelo: App\Models\Venta
    Integración: Incluido en welcome.blade.php mediante @include('ventas.ventaForm') 
    =============================================================================
--}}

<div x-show="showSaleDetailModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div @click.away="showSaleDetailModal = false" class="bg-dark-900 border border-slate-700 rounded-2xl w-full max-w-lg p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div>
                    <h3 class="font-display font-bold text-lg text-white" x-text="'Detalle de Venta: ' + (selectedSale?.codigo_venta || '')"></h3>
                    <p class="text-xs text-slate-400" x-text="selectedSale ? formatDate(selectedSale.fecha_hora_venta) : ''"></p>
                </div>
                <button @click="showSaleDetailModal = false" class="text-slate-400 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="space-y-3" x-show="selectedSale">
                <div class="grid grid-cols-2 gap-2 text-xs bg-dark-950 p-3 rounded-xl border border-slate-800">
                    <div>
                        <span class="text-slate-400">Cliente:</span>
                        <p class="font-bold text-slate-200" x-text="selectedSale?.cliente?.nombre_apellido_cliente || 'Consumidor Final'"></p>
                    </div>
                    <div>
                        <span class="text-slate-400">Método de Pago:</span>
                        <p class="font-bold text-brand-300" x-text="selectedSale?.metodo_pago"></p>
                    </div>
                </div>

                <div class="overflow-x-auto max-h-60 overflow-y-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-dark-950 uppercase text-slate-400 border-b border-slate-800">
                            <tr>
                                <th class="py-2 px-3">Producto</th>
                                <th class="py-2 px-3 text-center">Cant.</th>
                                <th class="py-2 px-3 text-right">Precio</th>
                                <th class="py-2 px-3 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            <template x-for="item in (selectedSale?.venta_detalles || [])" :key="item.id_venta_detalle">
                                <tr>
                                    <td class="py-2.5 px-3 font-medium text-slate-200" x-text="item.producto ? item.producto.nombre_producto : 'Producto #' + item.id_producto"></td>
                                    <td class="py-2.5 px-3 text-center font-bold text-white" x-text="item.cantidad"></td>
                                    <td class="py-2.5 px-3 text-right text-slate-400" x-text="formatCurrency(item.precio_unitario)"></td>
                                    <td class="py-2.5 px-3 text-right font-bold text-emerald-400" x-text="formatCurrency(item.subtotal_venta_detalle)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="pt-3 border-t border-slate-800 flex justify-between items-center text-sm font-bold">
                    <span class="text-slate-400">Total Facturado:</span>
                    <span class="text-emerald-400 font-display text-lg" x-text="formatCurrency(selectedSale?.total_venta || 0)"></span>
                </div>
            </div>
        </div>
    </div>