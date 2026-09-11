{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Listado de Cajas
    Archivo: resources/views/cajas/cajasView.blade.php
    Propósito: Permite la visualización y gestión de productos de forma modular.
    Controlador asociado: App\Http\Controllers\CajaController
    Modelo: App\Models\Caja
    Integración: Incluido en welcome.blade.php mediante @include('cajas.cajasView') 
    =============================================================================
--}}

<div x-show="currentTab === 'caja'" x-cloak class="space-y-6">
    <!-- Cajas Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <template x-for="c in cajas" :key="c.caja_id">
            <div class="glass-panel p-5 rounded-2xl border-l-4"
                :class="c.estado_caja === 'Abierta' ? 'border-l-emerald-500' : 'border-l-slate-700'">
                <div class="flex justify-between items-start">
                    <div>
                        <span class="text-xs text-slate-400 uppercase font-semibold">Caja #<span x-text="c.caja_id"></span></span>
                        <h4 class="font-display font-bold text-lg text-white mt-0.5" x-text="c.descripcion_caja"></h4>
                        <p class="text-xs text-slate-400 mt-1" x-text="'Tipo: ' + c.tipo_apertura"></p>
                    </div>
                    <span class="px-2.5 py-1 text-xs font-bold rounded-lg"
                        :class="c.estado_caja === 'Abierta' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400'"
                        x-text="c.estado_caja"></span>
                </div>
            </div>
        </template>
    </div>

    <!-- Caja Movements -->
    <div class="glass-panel p-5 rounded-2xl">
        <h4 class="font-display font-bold text-base text-white mb-4 flex items-center gap-2">
            <i data-lucide="history" class="w-4 h-4 text-brand-400"></i>
            Historial de Movimientos de Caja
        </h4>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase bg-dark-900/80 text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="py-3 px-4">ID</th>
                        <th class="py-3 px-4">Caja</th>
                        <th class="py-3 px-4">Venta Asociada</th>
                        <th class="py-3 px-4">Fecha y Hora</th>
                        <th class="py-3 px-4 text-right">Monto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <template x-for="mov in cajaMovimientos" :key="mov.caja_movimiento_venta_id">
                        <tr class="hover:bg-slate-800/40">
                            <td class="py-3 px-4 font-mono text-xs text-slate-400" x-text="'#' + mov.caja_movimiento_venta_id"></td>
                            <td class="py-3 px-4 text-slate-200" x-text="mov.caja ? mov.caja.descripcion_caja : 'Caja ' + mov.id_caja"></td>
                            <td class="py-3 px-4 font-semibold text-brand-300" x-text="mov.venta ? mov.venta.codigo_venta : 'Movimiento Manual'"></td>
                            <td class="py-3 px-4 text-xs text-slate-400" x-text="formatDate(mov.fecha_hora_movimiento)"></td>
                            <td class="py-3 px-4 text-right font-bold text-emerald-400" x-text="formatCurrency(mov.monto_movimiento)"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>