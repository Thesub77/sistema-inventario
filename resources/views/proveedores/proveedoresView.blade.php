{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Gestión de Proveedores y Cuentas por Pagar (CxP)
    Archivo: resources/views/proveedores/proveedoresView.blade.php
    Propósito: Control integral de proveedores comerciales, facturas por pagar y 
               abonos con trazabilidad directa a los egresos de caja.
    Controladores asociados: App\Http\Controllers\ProveedorController
                             App\Http\Controllers\CuentaPorPagarController
    Modelos: App\Models\Proveedor, App\Models\Cuenta_por_pagar, App\Models\Pago_cuenta_por_pagar
    Integración: Incluido en welcome.blade.php mediante @include('proveedores.proveedoresView')
    =============================================================================
--}}

<div x-show="currentTab === 'proveedores' || currentTab === 'cuentas-por-pagar'" x-cloak class="space-y-6">

    <!-- 1. Encabezado Principal y Resumen -->
    <div class="glass-panel p-5 sm:p-6 rounded-3xl relative overflow-hidden border border-slate-200 dark:border-slate-800">
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-brand-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    <span>Gestión de Pasivos & Proveedores</span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-display font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Proveedores & <span class="text-amber-600 dark:text-amber-400">Cuentas por Pagar</span>
                </h2>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-2xl">
                    Registro de facturas a crédito recibidas, control de vencimientos y abonos monetarios con impacto automático en caja.
                </p>
            </div>

            <!-- Botones de Acción Global -->
            <div class="flex items-center gap-2.5 flex-wrap">
                <button type="button" @click="openProveedorModal()"
                    class="px-3.5 py-2 rounded-xl bg-white dark:bg-dark-900 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 font-bold text-xs border border-slate-200 dark:border-slate-700 shadow-xs transition-all flex items-center gap-2 cursor-pointer">
                    <i data-lucide="user-plus" class="w-4 h-4 text-brand-500"></i>
                    <span>Nuevo Proveedor</span>
                </button>
                <button type="button" @click="openCuentaPorPagarModal()"
                    class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-md shadow-amber-500/20 transition-all flex items-center gap-2 cursor-pointer">
                    <i data-lucide="file-plus" class="w-4 h-4"></i>
                    <span>Registrar Factura / CxP</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 2. Tarjetas de Métricas / KPIs Financieros -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Saldo Total Pendiente -->
        <div class="glass-panel p-4 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center gap-3.5 shadow-xs">
            <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center flex-shrink-0">
                <i data-lucide="coins" class="w-6 h-6"></i>
            </div>
            <div class="min-w-0">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Deuda Pendiente</span>
                <span class="text-xl font-display font-black text-slate-900 dark:text-white font-mono"
                    x-text="formatCurrency(cxpKPIs.total_pendiente || 0)"></span>
                <span class="text-[10px] text-slate-400 block mt-0.5">
                    <span class="font-bold text-amber-600 dark:text-amber-400" x-text="cxpKPIs.facturas_pendientes_count || 0"></span> facturas por liquidar
                </span>
            </div>
        </div>

        <!-- Deuda Vencida -->
        <div class="glass-panel p-4 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center gap-3.5 shadow-xs">
            <div class="w-12 h-12 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center flex-shrink-0">
                <i data-lucide="alert-octagon" class="w-6 h-6"></i>
            </div>
            <div class="min-w-0">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Vencido a la Fecha</span>
                <span class="text-xl font-display font-black text-rose-600 dark:text-rose-400 font-mono"
                    x-text="formatCurrency(cxpKPIs.total_vencido || 0)"></span>
                <span class="text-[10px] text-rose-500 font-semibold block mt-0.5" x-show="(cxpKPIs.facturas_vencidas_count || 0) > 0">
                    <span class="font-bold" x-text="cxpKPIs.facturas_vencidas_count"></span> facturas vencidas
                </span>
                <span class="text-[10px] text-emerald-500 font-semibold block mt-0.5" x-show="(cxpKPIs.facturas_vencidas_count || 0) === 0">
                    Al día sin moras
                </span>
            </div>
        </div>

        <!-- Pagado este Mes -->
        <div class="glass-panel p-4 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center gap-3.5 shadow-xs">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                <i data-lucide="check-check" class="w-6 h-6"></i>
            </div>
            <div class="min-w-0">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Abonado este Mes</span>
                <span class="text-xl font-display font-black text-emerald-600 dark:text-emerald-400 font-mono"
                    x-text="formatCurrency(cxpKPIs.total_pagado_mes || 0)"></span>
                <span class="text-[10px] text-slate-400 block mt-0.5">Pagos y liquidaciones</span>
            </div>
        </div>

        <!-- Directorio de Proveedores -->
        <div class="glass-panel p-4 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center gap-3.5 shadow-xs">
            <div class="w-12 h-12 rounded-xl bg-brand-500/10 text-brand-600 dark:text-brand-400 flex items-center justify-center flex-shrink-0">
                <i data-lucide="truck" class="w-6 h-6"></i>
            </div>
            <div class="min-w-0">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Proveedores</span>
                <span class="text-xl font-display font-black text-slate-900 dark:text-white"
                    x-text="proveedores.length"></span>
                <span class="text-[10px] text-slate-400 block mt-0.5">Contactos comerciales activos</span>
            </div>
        </div>
    </div>

    <!-- 3. Selector de Subpestañas (Cuentas por Pagar vs Directorio de Proveedores) -->
    <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-2">
        <button type="button" @click="cxpActiveSubTab = 'cuentas'; currentTab = 'cuentas-por-pagar'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer"
            :class="cxpActiveSubTab === 'cuentas'
                ? 'bg-amber-500 text-white shadow-sm'
                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60'">
            <i data-lucide="receipt" class="w-4 h-4"></i>
            <span>Facturas por Pagar</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                :class="cxpActiveSubTab === 'cuentas' ? 'bg-amber-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300'"
                x-text="cuentasPorPagar.length"></span>
        </button>

        <button type="button" @click="cxpActiveSubTab = 'proveedores'; currentTab = 'proveedores'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer"
            :class="cxpActiveSubTab === 'proveedores'
                ? 'bg-amber-500 text-white shadow-sm'
                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60'">
            <i data-lucide="building-2" class="w-4 h-4"></i>
            <span>Directorio de Proveedores</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                :class="cxpActiveSubTab === 'proveedores' ? 'bg-amber-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300'"
                x-text="proveedores.length"></span>
        </button>
    </div>

    <!-- ========================================================================= -->
    <!-- SUBPESTAÑA 1: FACTURAS Y CUENTAS POR PAGAR -->
    <!-- ========================================================================= -->
    <div x-show="cxpActiveSubTab === 'cuentas'" class="space-y-4">
        <!-- Barra de Búsqueda y Filtros de Cuentas por Pagar -->
        <div class="glass-panel p-4 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                <!-- Buscador de texto -->
                <div class="sm:col-span-2 relative">
                    <input type="text"
                        x-model="cxpSearch"
                        placeholder="Buscar por N° factura, concepto o proveedor..."
                        class="w-full bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 rounded-xl pl-9 pr-3 py-2 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-amber-500 transition-colors">
                    <div class="absolute left-3 top-2.5 text-slate-400 pointer-events-none">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </div>
                </div>

                <!-- Filtro por Estado -->
                <div>
                    <select x-model="cxpEstadoFilter"
                        class="w-full bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-amber-500 transition-colors">
                        <option value="">Todos los Estados</option>
                        <option value="Pendiente">Pendiente</option>
                        <option value="Parcial">Pago Parcial</option>
                        <option value="Pagada">Pagada</option>
                    </select>
                </div>

                <!-- Filtro por Proveedor -->
                <div>
                    <select x-model="cxpProveedorFilter"
                        class="w-full bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-amber-500 transition-colors">
                        <option value="">Todos los Proveedores</option>
                        <template x-for="p in proveedores" :key="p.proveedor_id">
                            <option :value="p.proveedor_id" x-text="p.nombre_comercial"></option>
                        </template>
                    </select>
                </div>
            </div>
        </div>

        <!-- Tabla de Cuentas por Pagar -->
        <div class="glass-panel rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs whitespace-nowrap min-w-[950px]">
                    <thead class="text-[11px] uppercase bg-slate-50 dark:bg-dark-900 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 font-bold">
                        <tr>
                            <th class="py-3 px-4">Proveedor</th>
                            <th class="py-3 px-4">Factura & Concepto</th>
                            <th class="py-3 px-4">Vencimiento</th>
                            <th class="py-3 px-4 text-right">Monto Total</th>
                            <th class="py-3 px-4 text-right">Pagado</th>
                            <th class="py-3 px-4 text-right">Saldo Pendiente</th>
                            <th class="py-3 px-4 text-center">Estado</th>
                            <th class="py-3 px-4 text-center min-w-[170px]">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/70">
                        <template x-for="cuenta in filteredCuentasPorPagar" :key="cuenta.cuenta_por_pagar_id">
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors"
                                :class="{'bg-rose-500/5': isCuentaVencida(cuenta)}">
                                
                                <!-- Proveedor -->
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                        <i data-lucide="building-2" class="w-3.5 h-3.5 text-amber-500 flex-shrink-0"></i>
                                        <span x-text="cuenta.proveedor ? cuenta.proveedor.nombre_comercial : 'Proveedor #' + cuenta.id_proveedor"></span>
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-0.5" x-show="cuenta.proveedor && (cuenta.proveedor.contacto_vendedor || cuenta.proveedor.telefono)">
                                        <span x-text="cuenta.proveedor.contacto_vendedor || ''"></span>
                                        <span x-show="cuenta.proveedor.contacto_vendedor && cuenta.proveedor.telefono"> • </span>
                                        <span x-text="cuenta.proveedor.telefono || ''"></span>
                                    </div>
                                </td>

                                <!-- Factura & Concepto -->
                                <td class="py-3 px-4">
                                    <span class="font-mono font-bold text-slate-800 dark:text-slate-200" x-text="cuenta.numero_factura"></span>
                                    <p class="text-[11px] text-slate-400 mt-0.5 truncate max-w-xs" x-text="cuenta.descripcion || 'Sin descripción adicional'"></p>
                                </td>

                                <!-- Vencimiento & Alerta -->
                                <td class="py-3 px-4 font-mono">
                                    <div class="flex items-center gap-1.5">
                                        <span :class="isCuentaVencida(cuenta) ? 'text-rose-600 dark:text-rose-400 font-bold' : 'text-slate-700 dark:text-slate-300'"
                                            x-text="formatDateOnly(cuenta.fecha_vencimiento)"></span>
                                        <template x-if="isCuentaVencida(cuenta)">
                                            <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30">
                                                Vencida
                                            </span>
                                        </template>
                                    </div>
                                    <span class="text-[10px] text-slate-400 block mt-0.5" x-text="'Emitida: ' + formatDateOnly(cuenta.fecha_emision)"></span>
                                </td>

                                <!-- Monto Total -->
                                <td class="py-3 px-4 text-right font-mono font-bold text-slate-700 dark:text-slate-300"
                                    x-text="formatCurrency(cuenta.monto_total)"></td>

                                <!-- Monto Pagado -->
                                <td class="py-3 px-4 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400"
                                    x-text="formatCurrency(cuenta.monto_pagado)"></td>

                                <!-- Saldo Pendiente -->
                                <td class="py-3 px-4 text-right font-mono font-extrabold text-xs"
                                    :class="Number(cuenta.saldo_pendiente) > 0 ? (isCuentaVencida(cuenta) ? 'text-rose-600 dark:text-rose-400' : 'text-amber-600 dark:text-amber-400') : 'text-slate-400'"
                                    x-text="formatCurrency(cuenta.saldo_pendiente)"></td>

                                <!-- Estado Badge -->
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="{
                                            'bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30': cuenta.estado === 'Pendiente',
                                            'bg-blue-500/15 text-blue-600 dark:text-blue-400 border border-blue-500/30': cuenta.estado === 'Parcial',
                                            'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30': cuenta.estado === 'Pagada',
                                            'bg-slate-500/15 text-slate-500 border border-slate-500/30': cuenta.estado === 'Anulada'
                                        }"
                                        x-text="cuenta.estado"></span>
                                </td>

                                <!-- Acciones -->
                                <td class="py-3 px-4 text-center min-w-[170px]">
                                    <div class="flex items-center justify-center gap-1.5 flex-nowrap">
                                        <!-- Botón Abonar / Liquidada -->
                                        <button type="button"
                                            x-show="Number(cuenta.saldo_pendiente) > 0"
                                            @click="openAbonoModal(cuenta)"
                                            class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] shadow-xs transition-all inline-flex items-center gap-1 cursor-pointer flex-shrink-0"
                                            title="Registrar Abono o Pago">
                                            <i data-lucide="hand-coins" class="w-3.5 h-3.5"></i>
                                            <span>Abonar</span>
                                        </button>
                                        <span x-show="Number(cuenta.saldo_pendiente) <= 0"
                                            class="px-2 py-0.5 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold text-[10px] inline-flex items-center gap-1 flex-shrink-0"
                                            title="Factura pagada en su totalidad">
                                            <i data-lucide="check-circle-2" class="w-3 h-3"></i>
                                            <span>Liquidada</span>
                                        </span>

                                        <!-- Botón Ver Historial de Pagos -->
                                        <button type="button"
                                            @click="openHistorialAbonos(cuenta)"
                                            class="p-1.5 text-slate-400 hover:text-amber-500 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer flex-shrink-0 relative"
                                            :title="Number(cuenta.monto_pagado) > 0 ? 'Ver historial de pagos (' + formatCurrency(cuenta.monto_pagado) + ' pagados)' : 'Ver historial de pagos'">
                                            <i data-lucide="history" class="w-4 h-4"></i>
                                            <span x-show="Number(cuenta.monto_pagado) > 0"
                                                class="absolute top-0.5 right-0.5 w-2 h-2 rounded-full bg-amber-500 ring-2 ring-white dark:ring-dark-900"></span>
                                        </button>

                                        <!-- Botón Editar (datos descriptivos de la factura) -->
                                        <button type="button"
                                            x-show="Number(cuenta.monto_pagado) === 0"
                                            @click="openCuentaPorPagarModal(cuenta)"
                                            class="p-1.5 text-slate-400 hover:text-brand-500 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer flex-shrink-0"
                                            title="Editar factura">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </button>
                                        <span x-show="Number(cuenta.monto_pagado) > 0"
                                            class="p-1.5 text-slate-300 dark:text-slate-600 rounded-lg cursor-not-allowed opacity-40 flex-shrink-0 inline-flex"
                                            title="No se puede editar porque cuenta con pagos registrados">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </span>

                                        <!-- Botón Eliminar (solo si no tiene abonos registrados) -->
                                        <button type="button"
                                            x-show="Number(cuenta.monto_pagado) === 0"
                                            @click="deleteCuentaPorPagar(cuenta)"
                                            class="p-1.5 text-slate-400 hover:text-rose-500 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer flex-shrink-0"
                                            title="Eliminar factura">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                        <span x-show="Number(cuenta.monto_pagado) > 0"
                                            class="p-1.5 text-slate-300 dark:text-slate-600 rounded-lg cursor-not-allowed opacity-40 flex-shrink-0 inline-flex"
                                            title="No se puede eliminar porque cuenta con pagos registrados">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <!-- Empty State Cuentas por Pagar -->
                <div x-show="filteredCuentasPorPagar.length === 0" class="py-12 px-4 text-center space-y-3">
                    <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/80 flex items-center justify-center mx-auto text-slate-400">
                        <i data-lucide="receipt" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-800 dark:text-slate-200 text-sm">No hay cuentas por pagar registradas</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto mt-0.5">
                            Comienza registrando las facturas o recibos a crédito emitidos por tus proveedores.
                        </p>
                    </div>
                    <div>
                        <button type="button" @click="openCuentaPorPagarModal()"
                            class="px-3.5 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold shadow-sm transition-all inline-flex items-center gap-1.5 cursor-pointer">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                            <span>Registrar Primera Factura</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- SUBPESTAÑA 2: DIRECTORIO DE PROVEEDORES -->
    <!-- ========================================================================= -->
    <div x-show="cxpActiveSubTab === 'proveedores'" class="space-y-4">
        <!-- Barra de Búsqueda y Botón Nuevo Proveedor -->
        <div class="glass-panel p-4 rounded-2xl border border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="relative flex-1 max-w-md">
                <input type="text"
                    x-model="proveedorSearch"
                    placeholder="Buscar proveedor por nombre, contacto o teléfono..."
                    class="w-full bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 rounded-xl pl-9 pr-3 py-2 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-amber-500 transition-colors">
                <div class="absolute left-3 top-2.5 text-slate-400 pointer-events-none">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </div>
            </div>

            <button type="button" @click="openProveedorModal()"
                class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-md shadow-amber-500/20 transition-all flex items-center gap-2 cursor-pointer flex-shrink-0">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                <span>Nuevo Proveedor</span>
            </button>
        </div>

        <!-- Listado de Proveedores en Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="prov in filteredProveedores" :key="prov.proveedor_id">
                <div class="glass-panel p-5 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-4 hover:border-amber-500/40 transition-all shadow-xs flex flex-col justify-between">
                    <div class="space-y-3">
                        <!-- Header de la Card -->
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2.5">
                                <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold font-display text-base flex-shrink-0">
                                    <span x-text="prov.nombre_comercial ? prov.nombre_comercial.charAt(0).toUpperCase() : 'P'"></span>
                                </div>
                                <div>
                                    <h4 class="font-bold text-sm text-slate-900 dark:text-white" x-text="prov.nombre_comercial"></h4>
                                    <div class="flex items-center gap-2 text-xs text-slate-400 mt-0.5">
                                        <span class="font-medium" x-text="prov.contacto_vendedor || 'Sin contacto directo'"></span>
                                    </div>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                :class="prov.estado === 1 ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : 'bg-slate-500/15 text-slate-500'"
                                x-text="prov.estado === 1 ? 'Activo' : 'Inactivo'"></span>
                        </div>

                        <!-- Detalles Comerciales -->
                        <div class="grid grid-cols-2 gap-2 text-xs bg-slate-50 dark:bg-dark-950 p-2.5 rounded-xl border border-slate-200/60 dark:border-slate-800/80">
                            <div>
                                <span class="text-[10px] text-slate-400 block uppercase font-bold">Teléfono</span>
                                <span class="font-medium text-slate-700 dark:text-slate-300 font-mono" x-text="prov.telefono || 'N/D'"></span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 block uppercase font-bold">Plazo Crédito</span>
                                <span class="font-medium text-slate-700 dark:text-slate-300"
                                    x-text="prov.plazo_credito_dias > 0 ? (prov.plazo_credito_dias + ' días') : 'Contado'"></span>
                            </div>
                        </div>

                        <!-- Saldo Pendiente Acumulado -->
                        <div class="flex items-center justify-between pt-1">
                            <span class="text-xs text-slate-500 dark:text-slate-400">Deuda Pendiente:</span>
                            <span class="font-mono font-bold text-sm"
                                :class="(prov.saldo_total_pendiente || 0) > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'"
                                x-text="formatCurrency(prov.saldo_total_pendiente || 0)"></span>
                        </div>
                    </div>

                    <!-- Footer de la Card / Acciones -->
                    <div class="flex items-center justify-between pt-3 border-t border-slate-200 dark:border-slate-800">
                        <span class="text-[11px] text-slate-400" x-text="(prov.total_facturas || 0) + ' facturas emitidas'"></span>
                        
                        <div class="flex items-center gap-1.5">
                            <button type="button" @click="openProveedorModal(prov)"
                                class="p-1.5 text-slate-400 hover:text-brand-500 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                                title="Editar Proveedor">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                            <button type="button" @click="deleteProveedor(prov)"
                                class="p-1.5 text-slate-400 hover:text-rose-500 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                                title="Desactivar Proveedor">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty State Directorio de Proveedores -->
        <div x-show="filteredProveedores.length === 0" class="py-12 px-4 text-center space-y-3">
            <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/80 flex items-center justify-center mx-auto text-slate-400">
                <i data-lucide="building-2" class="w-6 h-6"></i>
            </div>
            <div>
                <h4 class="font-bold text-slate-800 dark:text-slate-200 text-sm">No hay proveedores encontrados</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto mt-0.5">
                    Registra a tus distribuidores o proveedores habituales para asociarles sus facturas a crédito.
                </p>
            </div>
            <div>
                <button type="button" @click="openProveedorModal()"
                    class="px-3.5 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold shadow-sm transition-all inline-flex items-center gap-1.5 cursor-pointer">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>Registrar Proveedor</span>
                </button>
            </div>
        </div>
    </div>

</div>
