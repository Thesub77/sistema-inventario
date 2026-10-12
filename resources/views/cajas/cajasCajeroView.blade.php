{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Gestión de Cajas y Arqueo - VISTA CAJERO / OPERATIVO
    Archivo: resources/views/cajas/cajasCajeroView.blade.php
    Propósito: Interfaz directa y unificada para cajeros y personal de mostrador.
               Muestra el banner del turno operativo, las cajas físicas,
               el balance en vivo y la tabla de movimientos de efectivo directamente
               debajo de las cajas, sin subpestañas de navegación.
    Integración: Incluido en cajasView.blade.php cuando el usuario no es Administrador.
    =============================================================================
--}}

<div class="space-y-6">



    <!-- 1. Banner de Estado Operativo de Caja (Dinámico) -->
    <template x-if="!turnoActivo">
        <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-amber-900 dark:text-amber-200">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-amber-900 dark:text-amber-200">Caja Cerrada - No hay turno activo</h4>
                    <p class="text-xs text-amber-700 dark:text-amber-300/80">
                        La emisión de facturas en el POS está bloqueada hasta aperturar formalmente un turno con su fondo de efectivo.
                    </p>
                </div>
            </div>
            <button type="button" @click="openCajaAperturaModal()"
                class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer flex-shrink-0">
                <i data-lucide="unlock" class="w-3.5 h-3.5"></i>
                <span>Abrir Turno Ahora</span>
            </button>
        </div>
    </template>

    <template x-if="turnoActivo">
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/25 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-emerald-900 dark:text-emerald-200">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h4 class="font-bold text-sm text-emerald-900 dark:text-emerald-200 flex items-center gap-1.5">
                            <span>Turno Operativo #<span x-text="turnoActivo.caja_operacion_id"></span></span>
                            <template x-if="turnoActivo.fecha_hora_apertura">
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-800 dark:text-emerald-200" x-text="'Jornada ' + formatDateOnly(turnoActivo.fecha_hora_apertura)"></span>
                            </template>
                        </h4>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-700 dark:text-emerald-300"
                            x-text="turnoActivo.caja ? turnoActivo.caja.descripcion_caja : ('Caja #' + turnoActivo.id_caja)"></span>
                    </div>
                    <p class="text-xs text-emerald-700 dark:text-emerald-300/80 mt-0.5">
                        <span>Fondo inicial: </span>
                        <span class="font-bold font-mono text-emerald-800 dark:text-emerald-200" x-text="formatCurrency(turnoActivo.monto_apertura || 0)"></span>
                        <template x-if="turnoActivo.fecha_hora_apertura">
                            <span x-text="' • Apertura: ' + formatDate(turnoActivo.fecha_hora_apertura)"></span>
                        </template>
                    </p>
                </div>
            </div>
        </div>
    </template>

    <!-- 2. Cajas Físicas del Establecimiento -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="font-display font-bold text-base text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="monitor" class="w-4 h-4 text-brand-400"></i>
                <span>Cajas Físicas del Establecimiento</span>
            </h3>
        </div>

        <!-- Grilla de Tarjetas de Cajas Físicas -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="c in cajas" :key="c.caja_id">
                <div class="glass-panel p-5 rounded-2xl border-l-4 transition-all flex flex-col justify-between"
                    :class="Number(c.estado) === 0
                        ? 'border-l-rose-500/70 bg-slate-100/60 dark:bg-slate-900/40 opacity-80 border-slate-200 dark:border-slate-800'
                        : (c.estado_caja === 'Abierta'
                            ? (isCajaMine(c.caja_id)
                                ? 'border-l-emerald-500 bg-emerald-500/5 border-slate-200 dark:border-slate-800'
                                : 'border-l-amber-500 bg-amber-500/5 border-slate-200 dark:border-slate-800')
                            : 'border-l-slate-400 dark:border-l-slate-700 border-slate-200 dark:border-slate-800')">
                    <div>
                        <div class="flex justify-between items-start gap-2">
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs text-slate-400 uppercase font-semibold font-mono">Caja #<span x-text="c.caja_id"></span></span>
                                    
                                    <!-- Badge de Estado según Pertenencia de Turno y Estado Activo/Inactivo -->
                                    <template x-if="Number(c.estado) === 0">
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20 flex items-center gap-1">
                                            <i data-lucide="ban" class="w-3 h-3"></i>
                                            Inhabilitada
                                        </span>
                                    </template>
                                    <template x-if="Number(c.estado) !== 0 && c.estado_caja === 'Cerrada'">
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500">
                                            Cerrada / Disponible
                                        </span>
                                    </template>
                                    <template x-if="Number(c.estado) !== 0 && c.estado_caja === 'Abierta' && isCajaMine(c.caja_id)">
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Mi Turno Activo
                                        </span>
                                    </template>
                                    <template x-if="Number(c.estado) !== 0 && c.estado_caja === 'Abierta' && !isCajaMine(c.caja_id)">
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30 flex items-center gap-1">
                                            <i data-lucide="user-check" class="w-3 h-3"></i>
                                            <span x-text="'En uso: ' + (getCajaCashierName(c.caja_id) || 'Cajero')"></span>
                                        </span>
                                    </template>
                                </div>
                                <h4 class="font-display font-bold text-base text-slate-900 dark:text-white mt-1.5" x-text="c.descripcion_caja"></h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"
                                    x-text="Number(c.estado) === 0 ? 'Estado: Deshabilitada por administración' : ('Modalidad: ' + (c.tipo_apertura || 'Manual'))"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Barra de Acciones por Caja -->
                    <div class="pt-4 mt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                        <!-- Caso 0: Caja Inhabilitada / Desactivada -->
                        <template x-if="Number(c.estado) === 0">
                            <button type="button" disabled
                                class="w-full py-1.5 px-3 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800/80 text-slate-400 dark:text-slate-500 border border-slate-200 dark:border-slate-700/60 cursor-not-allowed opacity-75 flex items-center justify-center gap-1.5"
                                title="Esta caja se encuentra inhabilitada por administración">
                                <i data-lucide="ban" class="w-3.5 h-3.5 text-slate-400"></i>
                                <span>Caja Inhabilitada</span>
                            </button>
                        </template>

                        <!-- Caso 1: Caja Activa y Cerrada -> Botón para Apertura (Deshabilitado si ya tiene turno activo) -->
                        <template x-if="Number(c.estado) !== 0 && c.estado_caja === 'Cerrada'">
                            <button type="button"
                                @click="!turnoActivo && openCajaAperturaModal(c.caja_id)"
                                :disabled="!!turnoActivo"
                                :class="turnoActivo
                                    ? 'bg-slate-100 dark:bg-slate-800/80 text-slate-400 dark:text-slate-500 border border-slate-200 dark:border-slate-700/60 cursor-not-allowed opacity-75'
                                    : 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-xs cursor-pointer'"
                                class="w-full py-1.5 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5"
                                :title="turnoActivo ? 'Ya cuentas con un turno activo en otra caja. Ciérralo primero.' : 'Abrir turno en esta caja'">
                                <i :data-lucide="turnoActivo ? 'lock' : 'unlock'" class="w-3.5 h-3.5"></i>
                                <span x-text="turnoActivo ? 'Abrir Turno (Bloqueado)' : 'Abrir Turno'"></span>
                            </button>
                        </template>

                        <!-- Caso 2: Caja Abierta por el Usuario Actual (Mi Turno) -->
                        <template x-if="Number(c.estado) !== 0 && c.estado_caja === 'Abierta' && isCajaMine(c.caja_id)">
                            <div class="w-full flex items-center gap-1.5">
                                <button type="button" @click="openCajaCierreModal(getTurnoIdForCaja(c.caja_id))"
                                    class="flex-1 py-1.5 px-2.5 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-500 text-white shadow-xs transition-all flex items-center justify-center gap-1 cursor-pointer"
                                    title="Cerrar turno y realizar arqueo">
                                    <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                                    <span>Arqueo / Cierre</span>
                                </button>
                                <button type="button" @click="openCajaMovimientoModal('Ingreso', c.caja_id)"
                                    class="py-1.5 px-2 rounded-xl text-xs font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500 hover:text-white border border-emerald-500/20 transition-all flex items-center gap-1 cursor-pointer"
                                    title="Registrar ingreso de sencillo">
                                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                    <span>Ingreso</span>
                                </button>
                                <button type="button" @click="openCajaMovimientoModal('Egreso', c.caja_id)"
                                    class="py-1.5 px-2 rounded-xl text-xs font-bold bg-rose-500/10 text-rose-600 dark:text-rose-400 hover:bg-rose-500 hover:text-white border border-rose-500/20 transition-all flex items-center gap-1 cursor-pointer"
                                    title="Registrar egreso o gasto menor">
                                    <i data-lucide="minus" class="w-3.5 h-3.5"></i>
                                    <span>Egreso</span>
                                </button>
                            </div>
                        </template>

                        <!-- Caso 3: Caja Abierta por OTRO usuario (Cajero Distinto) -->
                        <template x-if="Number(c.estado) !== 0 && c.estado_caja === 'Abierta' && !isCajaMine(c.caja_id)">
                            <div class="w-full py-1.5 px-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-center text-[11px] font-medium flex items-center justify-center gap-1.5">
                                <i data-lucide="lock" class="w-3 h-3 text-slate-400"></i>
                                <span>En uso por otro cajero</span>
                            </div>
                        </template>
                    </div>

                    <!-- Información de turnos registrados en esta caja -->
                    <div class="mt-2.5 pt-2 border-t border-slate-100 dark:border-slate-800/60 flex items-center justify-between text-xs text-slate-400">
                        <span class="flex items-center gap-1">
                            <i data-lucide="history" class="w-3.5 h-3.5 text-slate-400"></i>
                            <span>Turnos registrados</span>
                        </span>
                        <span class="text-[11px] text-slate-400 font-mono" x-text="(turnos.filter(t => Number(t.id_caja) === Number(c.caja_id)).length) + ' turnos'"></span>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- 3. Balance & Estadísticas del Turno Activo -->
    <template x-if="turnoActivo">
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="font-display font-bold text-base text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="bar-chart-2" class="w-4 h-4 text-emerald-500"></i>
                    <span>Balance & Estadísticas del Turno Activo</span>
                </h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Tarjeta: Efectivo Esperado en Caja -->
                <div class="glass-panel p-4 rounded-2xl border border-slate-200 dark:border-slate-800/90 relative overflow-hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Efectivo en Caja</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-500 flex items-center justify-center">
                            <i data-lucide="banknote" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-display font-black text-emerald-600 dark:text-emerald-400"
                        x-text="formatCurrency(ventasTurnoStats ? ventasTurnoStats.efectivoEsperado : 0)"></div>
                    <div class="mt-2 text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between">
                        <span>Fondo de Apertura:</span>
                        <span class="font-bold text-slate-700 dark:text-slate-200 font-mono"
                            x-text="formatCurrency(ventasTurnoStats ? ventasTurnoStats.montoApertura : 0)"></span>
                    </div>
                </div>

                <!-- Tarjeta: Ventas en Efectivo -->
                <div class="glass-panel p-4 rounded-2xl border border-slate-200 dark:border-slate-800/90 relative overflow-hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Ventas Efectivo</span>
                        <div class="w-8 h-8 rounded-lg bg-brand-500/10 text-brand-500 flex items-center justify-center">
                            <i data-lucide="receipt" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-display font-black text-slate-900 dark:text-white"
                        x-text="formatCurrency(ventasTurnoStats ? ventasTurnoStats.efectivo : 0)"></div>
                    <div class="mt-2 text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between">
                        <span>Transacciones:</span>
                        <span class="font-bold text-slate-700 dark:text-slate-200"
                            x-text="(ventasTurnoStats ? ventasTurnoStats.countEfectivo : 0) + ' facturas'"></span>
                    </div>
                </div>

                <!-- Tarjeta: Ingresos Extraordinarios (+) -->
                <div class="glass-panel p-4 rounded-2xl border border-slate-200 dark:border-slate-800/90 relative overflow-hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Ingresos Extra (+)</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-500 flex items-center justify-center">
                            <i data-lucide="arrow-down-left" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-display font-black text-emerald-600 dark:text-emerald-400"
                        x-text="formatCurrency(resumenCajaMovimientos.ingresosExtra)"></div>
                    <div class="mt-2 text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between">
                        <span>Registros:</span>
                        <span class="font-bold text-slate-700 dark:text-slate-200"
                            x-text="resumenCajaMovimientos.countIngresos + ' entradas'"></span>
                    </div>
                </div>

                <!-- Tarjeta: Gastos Menores / Egresos (-) -->
                <div class="glass-panel p-4 rounded-2xl border border-slate-200 dark:border-slate-800/90 relative overflow-hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">Gastos / Egresos (-)</span>
                        <div class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-500 flex items-center justify-center">
                            <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-display font-black text-rose-600 dark:text-rose-400"
                        x-text="formatCurrency(resumenCajaMovimientos.egresosGastos)"></div>
                    <div class="mt-2 text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between">
                        <span>Registros:</span>
                        <span class="font-bold text-slate-700 dark:text-slate-200"
                            x-text="resumenCajaMovimientos.countEgresos + ' salidas'"></span>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- 4. TABLA DE MOVIMIENTOS DE EFECTIVO DIRECTAMENTE POR DEBAJO DE LAS CAJAS Y BALANCE -->
    <div class="glass-panel p-5 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-4">
        <!-- Encabezado de la Sección de Movimientos -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-3">
            <div>
                <h4 class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="arrow-left-right" class="w-5 h-5 text-emerald-500"></i>
                    <span>Movimientos de Efectivo del Turno Activo</span>
                </h4>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Registro continuo de ventas, ingresos extras y gastos menores de la jornada
                </p>
            </div>

            <!-- Botones de filtro por tipo de movimiento -->
            <div class="inline-flex p-1 rounded-xl bg-slate-100 dark:bg-dark-950 border border-slate-200 dark:border-slate-800 text-xs self-start sm:self-auto">
                <button type="button" @click="cajaMovimientoFiltro = 'todos'"
                    :class="cajaMovimientoFiltro === 'todos' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold shadow-xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                    class="px-2.5 py-1 rounded-lg transition-all cursor-pointer">
                    Todos (<span x-text="filteredCajaMovimientos.length"></span>)
                </button>
                <button type="button" @click="cajaMovimientoFiltro = 'ingreso'"
                    :class="cajaMovimientoFiltro === 'ingreso' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-slate-500 dark:text-slate-400 hover:text-emerald-500'"
                    class="px-2.5 py-1 rounded-lg transition-all cursor-pointer">
                    Ingresos (+)
                </button>
                <button type="button" @click="cajaMovimientoFiltro = 'egreso'"
                    :class="cajaMovimientoFiltro === 'egreso' ? 'bg-rose-600 text-white font-bold shadow-xs' : 'text-slate-500 dark:text-slate-400 hover:text-rose-500'"
                    class="px-2.5 py-1 rounded-lg transition-all cursor-pointer">
                    Egresos (-)
                </button>
                <button type="button" @click="cajaMovimientoFiltro = 'venta'"
                    :class="cajaMovimientoFiltro === 'venta' ? 'bg-brand-600 text-white font-bold shadow-xs' : 'text-slate-500 dark:text-slate-400 hover:text-brand-500'"
                    class="px-2.5 py-1 rounded-lg transition-all cursor-pointer">
                    Ventas
                </button>
            </div>
        </div>

        <!-- Filtros Secundarios: Método de Pago y Buscador -->
        <div class="flex flex-wrap items-center gap-3 pt-1">
            <!-- Filtro de Método de Pago -->
            <div class="relative min-w-[160px]">
                <select x-model="cajaMetodoPagoFiltro"
                    class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white font-medium focus:outline-none focus:border-brand-500 transition-colors cursor-pointer shadow-2xs">
                    <option value="">Todos los Métodos</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="transferencia">Transferencia</option>
                </select>
            </div>

            <!-- Buscador de movimientos -->
            <div class="relative flex-1 min-w-[240px]">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input type="text" x-model="cajaSearch" placeholder="Buscar movimiento por concepto, motivo, ID o código..."
                    class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-slate-700/80 rounded-xl pl-9 pr-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-brand-500 transition-colors shadow-2xs">
            </div>
        </div>

        <!-- Tabla de Movimientos del Turno -->
        <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800/80">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="text-xs uppercase bg-slate-50 dark:bg-dark-900/80 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="py-2.5 px-3 font-semibold">Código</th>
                        <th class="py-2.5 px-3 font-semibold">Tipo</th>
                        <th class="py-2.5 px-3 font-semibold">Método de Pago</th>
                        <th class="py-2.5 px-3 font-semibold">Caja & Turno</th>
                        <th class="py-2.5 px-3 font-semibold">Concepto / Motivo</th>
                        <th class="py-2.5 px-3 font-semibold">Fecha y Hora</th>
                        <th class="py-2.5 px-3 text-right font-semibold">Importe</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                    <template x-for="mov in filteredCajaMovimientos" :key="mov.caja_movimiento_venta_id">
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                            <!-- Código Estructurado -->
                            <td class="py-2.5 px-3 text-xs font-medium text-slate-700 dark:text-slate-300"
                                x-text="getMovimientoCodigo(mov)">
                            </td>

                            <!-- Tipo de Movimiento con Badge -->
                            <td class="py-2.5 px-3">
                                <template x-if="mov.id_venta === null && Number(mov.monto_movimiento) > 0">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                        <i data-lucide="arrow-down-left" class="w-3 h-3"></i>
                                        <span>Ingreso Extra</span>
                                    </span>
                                </template>
                                <template x-if="mov.id_venta === null && Number(mov.monto_movimiento) < 0">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20">
                                        <i data-lucide="arrow-up-right" class="w-3 h-3"></i>
                                        <span>Gasto / Egreso</span>
                                    </span>
                                </template>
                                <template x-if="mov.id_venta !== null && Number(mov.monto_movimiento) >= 0">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-brand-500/10 text-brand-600 dark:text-brand-400 border border-brand-500/20">
                                        <i data-lucide="receipt" class="w-3 h-3"></i>
                                        <span>Venta Directa</span>
                                    </span>
                                </template>
                                <template x-if="mov.id_venta !== null && Number(mov.monto_movimiento) < 0">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                                        <i data-lucide="rotate-ccw" class="w-3 h-3"></i>
                                        <span>Devolución</span>
                                    </span>
                                </template>
                            </td>

                            <!-- Método de Pago -->
                            <td class="py-2.5 px-3 text-xs">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-semibold text-[11px]"
                                    :class="{
                                        'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20': getMovimientoMetodoPago(mov).toLowerCase() === 'efectivo',
                                        'bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20': getMovimientoMetodoPago(mov).toLowerCase() === 'transferencia',
                                        'bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20': ['tarjeta', 'debito', 'credito'].includes(getMovimientoMetodoPago(mov).toLowerCase())
                                    }"
                                    x-text="getMovimientoMetodoPago(mov)">
                                </span>
                            </td>

                            <!-- Caja y Turno con Jornada -->
                            <td class="py-2.5 px-3 text-xs">
                                <div class="font-bold text-slate-800 dark:text-slate-200" x-text="mov.caja ? mov.caja.descripcion_caja : ('Caja #' + mov.id_caja)"></div>
                                <div class="text-[11px] text-slate-400" x-text="mov.id_caja_operacion ? ('Turno #' + mov.id_caja_operacion + (mov.turno?.fecha_hora_apertura ? ' (Jornada ' + formatDateOnly(mov.turno.fecha_hora_apertura) + ')' : (mov.fecha_hora_movimiento ? ' (Jornada ' + formatDateOnly(mov.fecha_hora_movimiento) + ')' : ''))) : 'Sin turno'"></div>
                            </td>

                            <!-- Concepto o Justificación -->
                            <td class="py-2.5 px-3 text-xs text-slate-700 dark:text-slate-300">
                                <span class="font-medium" x-text="getMovimientoConcepto(mov)"></span>
                            </td>

                            <!-- Fecha y Hora -->
                            <td class="py-2.5 px-3 text-xs text-slate-500 dark:text-slate-400" x-text="formatDate(mov.fecha_hora_movimiento)"></td>

                            <!-- Importe -->
                            <td class="py-2.5 px-3 text-right font-mono font-bold"
                                :class="Number(mov.monto_movimiento) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                <span x-text="(Number(mov.monto_movimiento) >= 0 ? '+ ' : '- ') + formatCurrency(Math.abs(Number(mov.monto_movimiento)))"></span>
                            </td>
                        </tr>
                    </template>

                    <!-- Estado Vacío -->
                    <tr x-show="filteredCajaMovimientos.length === 0">
                        <td colspan="7" class="py-10 text-center text-slate-400 space-y-2">
                            <i data-lucide="inbox" class="w-8 h-8 mx-auto text-slate-500"></i>
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-300">
                                <span x-text="turnoActivo ? 'No hay movimientos registrados en este turno' : 'No hay turno activo actualmente'"></span>
                            </p>
                            <p class="text-xs text-slate-400 max-w-sm mx-auto">
                                <span x-text="turnoActivo ? 'Las ventas en efectivo, ingresos extraordinarios y egresos aparecerán listados aquí en tiempo real.' : 'Debe aperturar un turno operativo para comenzar a emitir ventas y registrar movimientos de caja.'"></span>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
