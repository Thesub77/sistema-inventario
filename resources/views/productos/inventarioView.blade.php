{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Kardex y Movimientos
    Archivo: resources/views/productos/inventarioView.blade.php
    Propósito: Permite la visualización de Kardex y Movimientos de productos de forma modular.
    Controlador asociado: App\Http\Controllers\ProductoController
    Modelo: App\Models\Producto
    Integración: Incluido en welcome.blade.php mediante @include('productos.inventarioView') 
    =============================================================================
--}}

<div x-show="currentTab === 'inventario'" x-cloak class="space-y-5">
    <div class="glass-panel p-5 rounded-2xl">
        <div class="flex items-center justify-between mb-4">
            <h4 class="font-display font-bold text-base text-white flex items-center gap-2">
                <i data-lucide="repeat" class="w-4 h-4 text-brand-400"></i>
                Registro de Kardex y Movimientos de Stock
            </h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase bg-dark-900/80 text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Fecha</th>
                        <th class="py-3 px-4">Producto</th>
                        <th class="py-3 px-4">Tipo de Movimiento</th>
                        <th class="py-3 px-4 text-center">Cantidad</th>
                        <th class="py-3 px-4 text-center">Stock Previo</th>
                        <th class="py-3 px-4 text-center">Stock Resultante</th>
                        <th class="py-3 px-4">Usuario</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <template x-for="m in movimientosInventario" :key="m.movimiento_inventario_id">
                        <tr class="hover:bg-slate-800/40">
                            <td class="py-3 px-4 text-xs text-slate-400" x-text="formatDate(m.fecha_movimiento)"></td>
                            <td class="py-3 px-4 font-semibold text-slate-100" x-text="m.producto ? m.producto.nombre_producto : 'Producto #' + m.id_producto"></td>
                            <td class="py-3 px-4">
                                <span class="px-2.5 py-0.5 text-xs rounded-full font-medium"
                                    :class="m.tipo_movimiento.includes('Entrada') || m.tipo_movimiento.includes('Inicial') 
                                                        ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' 
                                                        : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'"
                                    x-text="m.tipo_movimiento"></span>
                            </td>
                            <td class="py-3 px-4 text-center font-bold" x-text="m.cantidad_movimimiento + ' uds.'"></td>
                            <td class="py-3 px-4 text-center text-slate-400" x-text="m.stock_anterior_producto"></td>
                            <td class="py-3 px-4 text-center font-bold text-white" x-text="m.stock_resultante_producto"></td>
                            <td class="py-3 px-4 text-xs text-slate-400" x-text="m.usuario ? m.usuario.nombre_apellido : 'N/A'"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>