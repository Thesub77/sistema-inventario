{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Vista Dashboard
    Archivo: resources/views/sistema/dashboardView.blade.php
    Propósito: Permite la visualización de las métricas del sistema.
    Controlador asociado: App\Http\Controllers\SistemaController
    Integración: Incluido en welcome.blade.php mediante @include('sistema.dashboardView') 
    =============================================================================
--}}

<div x-show="currentTab === 'dashboard'" x-cloak class="space-y-6">
    <!-- KPI Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Stat 1: Total Ventas -->
        <div class="glass-panel p-5 rounded-2xl relative overflow-hidden group hover:border-brand-500/40 transition-all">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Total Facturado</p>
                    <h3 class="text-2xl font-bold font-display text-white mt-1" x-text="formatCurrency(stats.totalVentasMonto)"></h3>
                    <p class="text-xs text-emerald-400 mt-2 flex items-center gap-1 font-medium">
                        <i data-lucide="trending-up" class="w-3.5 h-3.5"></i>
                        <span x-text="ventas.length + ' facturas emitidas'"></span>
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center justify-center">
                    <i data-lucide="dollar-sign" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <!-- Stat 2: Total Productos -->
        <div class="glass-panel p-5 rounded-2xl relative overflow-hidden group hover:border-brand-500/40 transition-all">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Productos en Catálogo</p>
                    <h3 class="text-2xl font-bold font-display text-white mt-1" x-text="productos.length"></h3>
                    <p class="text-xs text-brand-400 mt-2 flex items-center gap-1 font-medium">
                        <i data-lucide="package" class="w-3.5 h-3.5"></i>
                        <span x-text="stats.totalUnidades + ' unidades en stock'"></span>
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-brand-500/10 text-brand-400 border border-brand-500/20 flex items-center justify-center">
                    <i data-lucide="box" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <!-- Stat 3: Alertas Stock Bajo -->
        <div class="glass-panel p-5 rounded-2xl relative overflow-hidden group hover:border-brand-500/40 transition-all">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Stock Bajo / Crítico</p>
                    <h3 class="text-2xl font-bold font-display text-white mt-1" x-text="lowStockProducts.length"></h3>
                    <p class="text-xs text-amber-400 mt-2 flex items-center gap-1 font-medium">
                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                        <span>Requieren reposición</span>
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center justify-center">
                    <i data-lucide="alert-circle" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <!-- Stat 4: Clientes -->
        <div class="glass-panel p-5 rounded-2xl relative overflow-hidden group hover:border-brand-500/40 transition-all">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Clientes Registrados</p>
                    <h3 class="text-2xl font-bold font-display text-white mt-1" x-text="clientes.length"></h3>
                    <p class="text-xs text-sky-400 mt-2 flex items-center gap-1 font-medium">
                        <i data-lucide="users" class="w-3.5 h-3.5"></i>
                        <span>Base de clientes activa</span>
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-sky-500/10 text-sky-400 border border-sky-500/20 flex items-center justify-center">
                    <i data-lucide="user-check" class="w-6 h-6"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Sections Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Sales List -->
        <div class="lg:col-span-2 glass-panel p-5 rounded-2xl">
            <div class="flex items-center justify-between mb-4">
                <h4 class="font-display font-bold text-base text-white flex items-center gap-2">
                    <i data-lucide="receipt" class="w-4 h-4 text-brand-400"></i>
                    Últimas Ventas Realizadas
                </h4>
                <button @click="currentTab = 'ventas'" class="text-xs text-brand-400 hover:text-brand-300 font-semibold">Ver todas &rarr;</button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs uppercase bg-dark-900/60 text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="py-2.5 px-3">Código</th>
                            <th class="py-2.5 px-3">Cliente</th>
                            <th class="py-2.5 px-3">Método</th>
                            <th class="py-2.5 px-3">Fecha</th>
                            <th class="py-2.5 px-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <template x-for="v in ventas.slice(0, 5)" :key="v.venta_id">
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="py-3 px-3 font-semibold text-brand-300" x-text="v.codigo_venta"></td>
                                <td class="py-3 px-3 text-slate-200" x-text="v.cliente ? v.cliente.nombre_apellido_cliente : 'Consumidor Final'"></td>
                                <td class="py-3 px-3">
                                    <span class="px-2 py-0.5 text-xs rounded-md font-medium"
                                        :class="v.metodo_pago === 'Efectivo' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-brand-500/10 text-brand-400 border border-brand-500/20'"
                                        x-text="v.metodo_pago"></span>
                                </td>
                                <td class="py-3 px-3 text-xs text-slate-400" x-text="formatDate(v.fecha_hora_venta)"></td>
                                <td class="py-3 px-3 text-right font-bold text-white" x-text="formatCurrency(v.total_venta)"></td>
                            </tr>
                        </template>
                        <tr x-show="ventas.length === 0">
                            <td colspan="5" class="py-6 text-center text-slate-500">No hay ventas registradas aún</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Low Stock & Quick Actions -->
        <div class="space-y-6">
            <!-- Low Stock Box -->
            <div class="glass-panel p-5 rounded-2xl">
                <h4 class="font-display font-bold text-base text-white mb-3 flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-400"></i>
                    Alertas de Stock Mínimo
                </h4>
                <div class="space-y-3">
                    <template x-for="p in lowStockProducts.slice(0, 4)" :key="p.producto_id">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-dark-900/60 border border-slate-800">
                            <div class="overflow-hidden pr-2">
                                <p class="text-sm font-semibold text-slate-200 truncate" x-text="p.nombre_producto"></p>
                                <p class="text-xs text-slate-400" x-text="'Mínimo: ' + p.existencia_minima + ' uds.'"></p>
                            </div>
                            <div class="text-right">
                                <span class="px-2 py-1 text-xs font-bold rounded-lg bg-rose-500/20 text-rose-400 border border-rose-500/30" x-text="p.existencia_bodega + ' uds.'"></span>
                            </div>
                        </div>
                    </template>
                    <div x-show="lowStockProducts.length === 0" class="text-xs text-emerald-400 flex items-center gap-2 p-3 bg-emerald-500/10 rounded-xl border border-emerald-500/20">
                        <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                        <span>Todos los productos tienen stock saludable.</span>
                    </div>
                </div>
            </div>

            <!-- System Status Box -->
            <div class="glass-panel p-5 rounded-2xl">
                <h4 class="font-display font-bold text-base text-white mb-3 flex items-center gap-2">
                    <i data-lucide="activity" class="w-4 h-4 text-emerald-400"></i>
                    Estado de Cajas
                </h4>
                <div class="space-y-2">
                    <template x-for="c in cajas" :key="c.caja_id">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-dark-900/60 border border-slate-800">
                            <div>
                                <p class="text-xs font-semibold text-slate-200" x-text="c.descripcion_caja"></p>
                                <p class="text-[11px] text-slate-400" x-text="'Tipo: ' + c.tipo_apertura"></p>
                            </div>
                            <span class="px-2 py-0.5 text-xs font-bold rounded-full"
                                :class="c.estado_caja === 'Abierta' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-700 text-slate-400'"
                                x-text="c.estado_caja"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>