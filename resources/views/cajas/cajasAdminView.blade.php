{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Gestión de Cajas y Arqueo - VISTA ADMINISTRADOR
    Archivo: resources/views/cajas/cajasAdminView.blade.php
    Propósito: Interfaz administrativa integral con supervisión global de todas
               las cajas, balance del turno activo, tabla de movimientos de efectivo
               cargada por debajo de las tarjetas de balance, e historial de turnos y arqueos.
    Integración: Incluido en cajasView.blade.php cuando el usuario es Administrador.
    =============================================================================
--}}

<div class="space-y-6">

    <!-- 1. Encabezado Principal y Barra de Acciones (Administración) -->
    <div class="glass-panel p-5 sm:p-6 rounded-3xl relative overflow-hidden border border-slate-200 dark:border-slate-800">
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-brand-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Control Financiero & Arqueo</span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-display font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Cajas & <span class="text-emerald-600 dark:text-emerald-400">Movimientos</span>
                </h2>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-2xl">
                    Supervisión de turnos de caja, balance en tiempo real y registro obligatorio de ingresos y egresos de efectivo para cuadre exacto.
                </p>
            </div>
        </div>
    </div>

    <!-- Banner de Estado Operativo de Caja (Dinámico) -->
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
                    <div class="flex items-center gap-2">
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
                        <span class="font-bold font-mono" x-text="formatCurrency(turnoActivo.monto_apertura || 0)"></span>
                        <template x-if="turnoActivo.fecha_hora_apertura">
                            <span x-text="' • Apertura: ' + formatDate(turnoActivo.fecha_hora_apertura)"></span>
                        </template>
                    </p>
                </div>
            </div>
        </div>
    </template>

    <!-- Selector de Subpestañas del Módulo de Cajas (Solo Cajas & Turno Activo e Historial de Turnos) -->
    <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-2 overflow-x-auto text-xs">
        <button type="button" @click="cajaActiveSubTab = 'cajas'"
            class="px-4 py-2 rounded-xl font-bold transition-all flex items-center gap-2 cursor-pointer whitespace-nowrap"
            :class="cajaActiveSubTab === 'cajas'
                ? 'bg-brand-600 text-white shadow-xs'
                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60'">
            <i data-lucide="monitor" class="w-4 h-4"></i>
            <span>Cajas & Turno Activo</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                :class="cajaActiveSubTab === 'cajas' ? 'bg-brand-700 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300'"
                x-text="cajas.length"></span>
        </button>

        <button type="button" @click="cajaActiveSubTab = 'turnos'"
            class="px-4 py-2 rounded-xl font-bold transition-all flex items-center gap-2 cursor-pointer whitespace-nowrap"
            :class="cajaActiveSubTab === 'turnos'
                ? 'bg-brand-600 text-white shadow-xs'
                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60'">
            <i data-lucide="history" class="w-4 h-4"></i>
            <span>Historial de Turnos & Arqueos</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                :class="cajaActiveSubTab === 'turnos' ? 'bg-brand-700 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300'"
                x-text="turnos.length"></span>
        </button>
    </div>

    <!-- ========================================================================= -->
    <!-- SUBPESTAÑA 1: CAJAS FÍSICAS, BALANCE Y MOVIMIENTOS POR DEBAJO              -->
    <!-- ========================================================================= -->
    <div x-show="cajaActiveSubTab === 'cajas'" class="space-y-6">

        <!-- 2. Estado de Cajas Físicas Registradas -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="font-display font-bold text-base text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="monitor" class="w-4 h-4 text-brand-400"></i>
                    <span>Cajas Físicas del Establecimiento</span>
                </h3>
                <div class="flex items-center gap-2">
                    <button type="button" @click="openCajaFormModal()"
                        class="px-2.5 py-1 rounded-xl text-xs font-bold bg-brand-500/10 text-brand-600 dark:text-brand-400 hover:bg-brand-500 hover:text-white border border-brand-500/20 transition-all flex items-center gap-1 cursor-pointer">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>Nueva Caja</span>
                    </button>
                    <span class="text-xs text-slate-500 dark:text-slate-400" x-text="cajas.length + ' cajas configuradas'"></span>
                </div>
            </div>

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

                                <!-- Botón Editar Caja (Solo Admin) -->
                                <button type="button" @click="openCajaFormModal(c)"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                                    title="Editar configuración de caja">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                </button>
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
                                        <span class="hidden xl:inline">Ingreso</span>
                                    </button>
                                    <button type="button" @click="openCajaMovimientoModal('Egreso', c.caja_id)"
                                        class="py-1.5 px-2 rounded-xl text-xs font-bold bg-rose-500/10 text-rose-600 dark:text-rose-400 hover:bg-rose-500 hover:text-white border border-rose-500/20 transition-all flex items-center gap-1 cursor-pointer"
                                        title="Registrar egreso o gasto menor">
                                        <i data-lucide="minus" class="w-3.5 h-3.5"></i>
                                        <span class="hidden xl:inline">Egreso</span>
                                    </button>
                                </div>
                            </template>

                            <!-- Caso 3: Caja Abierta por OTRO usuario (Cajero Distinto) -> Cierre Supervisado Admin -->
                            <template x-if="Number(c.estado) !== 0 && c.estado_caja === 'Abierta' && !isCajaMine(c.caja_id)">
                                <div class="w-full">
                                    <button type="button" @click="openCajaCierreModal(getTurnoIdForCaja(c.caja_id))"
                                        class="w-full py-1.5 px-3 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-500 text-white shadow-xs transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                                        title="Cerrar turno supervisado como Administrador">
                                        <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i>
                                        <span>Cierre Supervisado (Admin)</span>
                                    </button>
                                </div>
                            </template>
                        </div>

                        <!-- Enlace directo a turnos históricos de esta caja -->
                        <div class="mt-2.5 pt-2 border-t border-slate-100 dark:border-slate-800/60 flex items-center justify-between text-xs">
                            <button type="button" @click="verTurnosDeCaja(c.caja_id)"
                                class="text-xs font-semibold text-brand-600 dark:text-brand-400 hover:text-brand-500 hover:underline flex items-center gap-1 cursor-pointer">
                                <i data-lucide="history" class="w-3.5 h-3.5"></i>
                                <span>Ver Turnos Históricos</span>
                            </button>
                            <span class="text-[11px] text-slate-400 font-mono" x-text="(turnos.filter(t => Number(t.id_caja) === Number(c.caja_id)).length) + ' turnos'"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- 3. Tarjetas de Resumen Financiero del Turno Activo (Solo visible cuando hay turno abierto) -->
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

        <!-- 4. Historial Detallado de Movimientos de Caja (Cargado por debajo de las Cards del Balance) -->
        <div class="glass-panel p-5 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-4">
            <!-- Encabezado de Sección: Título a la izquierda y Filtros de Tipo a la derecha -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-3">
                <h4 class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="arrow-left-right" class="w-5 h-5 text-emerald-500"></i>
                    <span>Historial de Movimientos de Caja</span>
                </h4>

                <!-- Botones de filtro por tipo de movimiento (Lado derecho a la altura del título) -->
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

            <!-- Barra de Filtros Secundarios -->
            <div class="flex flex-wrap items-center gap-3 pt-1">
                <!-- Selector de Alcance para Administrador -->
                <div class="relative min-w-[200px]">
                    <select x-model="cajaHistorialAlcance"
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white font-medium focus:outline-none focus:border-brand-500 transition-colors cursor-pointer shadow-2xs">
                        <option value="todas">Todas las Cajas (Historial Global)</option>
                        <template x-if="turnoActivo">
                            <option value="mi_turno" x-text="'Mi Turno Activo (Turno #' + turnoActivo.caja_operacion_id + (turnoActivo.fecha_hora_apertura ? ' • ' + formatDateOnly(turnoActivo.fecha_hora_apertura) : '') + ')'"></option>
                        </template>
                    </select>
                </div>

                <!-- Filtro de Método de Pago -->
                <div class="relative min-w-[150px]">
                    <select x-model="cajaMetodoPagoFiltro"
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white font-medium focus:outline-none focus:border-brand-500 transition-colors cursor-pointer shadow-2xs">
                        <option value="">Todos los Métodos</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="transferencia">Transferencia</option>
                    </select>
                </div>

                <!-- Filtro de Usuario / Cajero (Solo accesible por Administrador) -->
                <div class="relative min-w-[160px]">
                    <select x-model="cajaUsuarioFiltro"
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white font-medium focus:outline-none focus:border-brand-500 transition-colors cursor-pointer shadow-2xs">
                        <option value="">Todos los Cajeros</option>
                        <template x-for="u in usuarios" :key="u.usuario_id">
                            <option :value="u.usuario_id" x-text="u.nombre_apellido"></option>
                        </template>
                    </select>
                </div>

                <!-- Buscador de movimientos -->
                <div class="relative flex-1 min-w-[240px]">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                    <input type="text" x-model="cajaSearch" placeholder="Buscar movimiento por concepto, motivo, ID o código de factura..."
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-slate-700/80 rounded-xl pl-9 pr-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-brand-500 transition-colors shadow-2xs">
                </div>
            </div>

            <!-- Tabla de Movimientos -->
            <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800/80">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="text-xs uppercase bg-slate-50 dark:bg-dark-900/80 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="py-2.5 px-3 font-semibold">Código</th>
                            <th class="py-2.5 px-3 font-semibold">Tipo</th>
                            <th class="py-2.5 px-3 font-semibold">Método de Pago</th>
                            <th class="py-2.5 px-3 font-semibold">Cajero / Usuario</th>
                            <th class="py-2.5 px-3 font-semibold">Caja & Turno</th>
                            <th class="py-2.5 px-3 font-semibold">Concepto / Motivo</th>
                            <th class="py-2.5 px-3 font-semibold">Fecha y Hora</th>
                            <th class="py-2.5 px-3 text-right font-semibold">Importe</th>
                            <th class="py-2.5 px-3 text-center font-semibold">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                        <template x-for="mov in filteredCajaMovimientos" :key="mov.caja_movimiento_venta_id">
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                <!-- Código Estructurado como texto normal -->
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

                                <!-- Cajero / Usuario (Solo visible para Admin) -->
                                <td class="py-2.5 px-3 text-xs font-medium text-slate-800 dark:text-slate-200">
                                    <div class="flex items-center gap-1.5">
                                        <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                                        <span x-text="getMovimientoUsuario(mov)"></span>
                                    </div>
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
                                    :class="Number(mov.monto_movimiento) >= 0 ? (isMovimientoVentaAnulada(mov) ? 'text-slate-400 line-through' : 'text-emerald-600 dark:text-emerald-400') : 'text-rose-600 dark:text-rose-400'">
                                    <span x-text="(Number(mov.monto_movimiento) >= 0 ? '+ ' : '- ') + formatCurrency(Math.abs(Number(mov.monto_movimiento)))"></span>
                                </td>

                                <!-- Acciones Rápidas -->
                                <td class="py-2.5 px-3 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Ver Comprobante de la Venta -->
                                        <template x-if="mov.id_venta !== null">
                                            <button type="button" @click="openReceiptModal(mov.id_venta)"
                                                class="p-1 rounded-lg text-slate-400 hover:text-brand-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                                title="Ver Comprobante">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                                </svg>
                                            </button>
                                        </template>

                                        <!-- Botón Anular Venta (Directo en Caja) -->
                                        <template x-if="canAnularMovimientoVenta(mov)">
                                            <button type="button" @click="anularVentaFromMovimiento(mov)"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold text-rose-600 hover:text-white bg-rose-500/10 hover:bg-rose-600 border border-rose-500/20 transition-all cursor-pointer active:scale-95 shadow-2xs"
                                                title="Anular venta y reponer inventario">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                </svg>
                                                <span>Anular</span>
                                            </button>
                                        </template>

                                        <!-- Indicador Venta ya Anulada -->
                                        <template x-if="isMovimientoVentaAnulada(mov)">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20">
                                                Anulada
                                            </span>
                                        </template>

                                        <!-- Movimientos sin venta vinculada -->
                                        <template x-if="mov.id_venta === null">
                                            <span class="text-slate-300 dark:text-slate-600 text-xs">-</span>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Estado Vacío -->
                        <tr x-show="filteredCajaMovimientos.length === 0">
                            <td colspan="9" class="py-10 text-center text-slate-400 space-y-2">
                                <i data-lucide="inbox" class="w-8 h-8 mx-auto text-slate-500"></i>
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">No hay movimientos de caja registrados</p>
                                <p class="text-xs text-slate-400 max-w-sm mx-auto">
                                    Los movimientos registrados manualmente o mediante ventas en el POS aparecerán automáticamente en esta lista.
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- SUBPESTAÑA 2: HISTORIAL DE TURNOS Y AUDITORÍA DE CAJA                      -->
    <!-- ========================================================================= -->
    <div x-show="cajaActiveSubTab === 'turnos'" class="space-y-4">
        <!-- 1. Tarjetas de Resumen KPI de Turnos -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <!-- KPI: Total Turnos Registrados -->
            <div class="glass-panel p-4 rounded-2xl border border-slate-200 dark:border-slate-800">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Turnos</span>
                    <div class="w-7 h-7 rounded-lg bg-brand-500/10 text-brand-500 flex items-center justify-center">
                        <i data-lucide="history" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="text-2xl font-display font-black text-slate-900 dark:text-white" x-text="turnos.length"></div>
                <span class="text-[10px] text-slate-400 block mt-0.5">Historial acumulado</span>
            </div>

            <!-- KPI: Turnos Cuadre Exacto -->
            <div class="glass-panel p-4 rounded-2xl border border-slate-200 dark:border-slate-800">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Cuadre Exacto</span>
                    <div class="w-7 h-7 rounded-lg bg-emerald-500/10 text-emerald-500 flex items-center justify-center">
                        <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="text-2xl font-display font-black text-emerald-600 dark:text-emerald-400" x-text="turnosStats.exactos"></div>
                <span class="text-[10px] text-emerald-600/80 dark:text-emerald-400/80 block mt-0.5">0.00 diferencia</span>
            </div>

            <!-- KPI: Turnos con Descuadre -->
            <div class="glass-panel p-4 rounded-2xl border border-slate-200 dark:border-slate-800">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-xs font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">Con Descuadre</span>
                    <div class="w-7 h-7 rounded-lg bg-rose-500/10 text-rose-500 flex items-center justify-center">
                        <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="text-2xl font-display font-black text-rose-600 dark:text-rose-400" x-text="turnosStats.descuadres"></div>
                <span class="text-[10px] text-rose-600/80 dark:text-rose-400/80 block mt-0.5">Faltantes o sobrantes</span>
            </div>

            <!-- KPI: Turnos en Curso -->
            <div class="glass-panel p-4 rounded-2xl border border-slate-200 dark:border-slate-800">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">En Operación</span>
                    <div class="w-7 h-7 rounded-lg bg-blue-500/10 text-blue-500 flex items-center justify-center">
                        <i data-lucide="activity" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="text-2xl font-display font-black text-blue-600 dark:text-blue-400" x-text="turnosStats.abiertos"></div>
                <span class="text-[10px] text-blue-600/80 dark:text-blue-400/80 block mt-0.5">Cajas abiertas ahora</span>
            </div>
        </div>

        <!-- 2. Filtros y Barra de Búsqueda de Turnos -->
        <div class="glass-panel p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-3">
                <div>
                    <h4 class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="history" class="w-5 h-5 text-brand-500"></i>
                        <span>Historial de Turnos de Caja & Arqueos</span>
                    </h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Inspección de turnos cerrados, auditorías de efectivo, diferencias y ventas de cada jornada
                    </p>
                </div>

                <!-- Botón Limpiar Filtros -->
                <button type="button" @click="turnoFiltroCaja = ''; turnoFiltroEstado = ''; turnoFiltroUsuario = ''; turnoSearch = ''"
                    x-show="turnoFiltroCaja || turnoFiltroEstado || turnoFiltroUsuario || turnoSearch"
                    class="text-xs font-semibold text-rose-500 hover:text-rose-600 hover:underline flex items-center gap-1 cursor-pointer self-start sm:self-auto">
                    <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                    <span>Restablecer Filtros</span>
                </button>
            </div>

            <!-- Filtros Selectores -->
            <div class="flex flex-wrap items-center gap-3 pt-1">
                <!-- Filtro por Caja -->
                <div class="relative min-w-[180px]">
                    <select x-model="turnoFiltroCaja"
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white font-medium focus:outline-none focus:border-brand-500 transition-colors cursor-pointer shadow-2xs">
                        <option value="">Todas las Cajas</option>
                        <template x-for="c in cajas" :key="c.caja_id">
                            <option :value="String(c.caja_id)" x-text="c.descripcion_caja + ' (#' + c.caja_id + ')'"></option>
                        </template>
                    </select>
                </div>

                <!-- Filtro por Usuario / Cajero (Solo visible para Admin) -->
                <div class="relative min-w-[180px]">
                    <select x-model="turnoFiltroUsuario"
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white font-medium focus:outline-none focus:border-brand-500 transition-colors cursor-pointer shadow-2xs">
                        <option value="">Todos los Cajeros</option>
                        <template x-for="u in usuarios" :key="u.usuario_id">
                            <option :value="String(u.usuario_id)" x-text="u.nombre_apellido"></option>
                        </template>
                    </select>
                </div>

                <!-- Filtro por Estado de Arqueo -->
                <div class="relative min-w-[160px]">
                    <select x-model="turnoFiltroEstado"
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white font-medium focus:outline-none focus:border-brand-500 transition-colors cursor-pointer shadow-2xs">
                        <option value="">Todos los Estados</option>
                        <option value="abierto">Turnos Abiertos (En Curso)</option>
                        <option value="exacto">Cuadre Exacto (0.00)</option>
                        <option value="faltante">Con Faltante</option>
                        <option value="sobrante">Con Sobrante</option>
                    </select>
                </div>

                <!-- Buscador rápido de turnos -->
                <div class="relative flex-1 min-w-[200px]">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                    <input type="text" x-model="turnoSearch" placeholder="Buscar por ID de turno, cajero u observaciones..."
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-slate-700/80 rounded-xl pl-9 pr-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-brand-500 transition-colors shadow-2xs">
                </div>
            </div>
        </div>

        <!-- 3. Tabla Completa de Turnos y Auditorías -->
        <div class="glass-panel rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="text-xs uppercase bg-slate-50 dark:bg-dark-900/80 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="py-2.5 px-3 font-semibold">Turno</th>
                            <th class="py-2.5 px-3 font-semibold">Caja</th>
                            <th class="py-2.5 px-3 font-semibold">Cajero</th>
                            <th class="py-2.5 px-3 font-semibold">Apertura</th>
                            <th class="py-2.5 px-3 font-semibold">Cierre</th>
                            <th class="py-2.5 px-3 text-right font-semibold">Fondo Inicial</th>
                            <th class="py-2.5 px-3 text-right font-semibold">Esperado</th>
                            <th class="py-2.5 px-3 text-right font-semibold">Contado</th>
                            <th class="py-2.5 px-3 text-center font-semibold">Estado Arqueo</th>
                            <th class="py-2.5 px-3 text-center font-semibold">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                        <template x-for="t in filteredTurnos" :key="t.caja_operacion_id">
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                <!-- ID de Turno -->
                                <td class="py-2.5 px-3 font-mono font-bold text-xs text-slate-800 dark:text-slate-200">
                                    <span class="px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800" x-text="'#' + t.caja_operacion_id"></span>
                                </td>

                                <!-- Caja Física -->
                                <td class="py-2.5 px-3 text-xs font-semibold text-slate-800 dark:text-slate-200"
                                    x-text="t.caja ? t.caja.descripcion_caja : ('Caja #' + t.id_caja)"></td>

                                <!-- Cajero / Usuario -->
                                <td class="py-2.5 px-3 text-xs">
                                    <div class="flex items-center gap-1.5 font-medium text-slate-700 dark:text-slate-300">
                                        <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400"></i>
                                        <span x-text="t.usuario ? t.usuario.nombre_apellido : 'Usuario #' + t.id_usuario"></span>
                                    </div>
                                    <template x-if="t.id_usuario_cierre && Number(t.id_usuario_cierre) !== Number(t.id_usuario)">
                                        <div class="text-[10px] text-amber-600 dark:text-amber-400 flex items-center gap-1 mt-0.5" title="Turno cerrado por un usuario distinto">
                                            <i data-lucide="shield-alert" class="w-3 h-3"></i>
                                            <span>Cerrado por: <span x-text="t.usuario_cierre ? t.usuario_cierre.nombre_apellido : ('Admin #' + t.id_usuario_cierre)"></span></span>
                                        </div>
                                    </template>
                                </td>

                                <!-- Fecha Apertura y Jornada -->
                                <td class="py-2.5 px-3 text-xs">
                                    <div class="font-medium text-slate-800 dark:text-slate-200" x-text="formatDate(t.fecha_hora_apertura)"></div>
                                    <div class="text-[11px] text-slate-400" x-text="'Jornada: ' + formatDateOnly(t.fecha_hora_apertura)"></div>
                                </td>

                                <!-- Fecha Cierre -->
                                <td class="py-2.5 px-3 text-xs">
                                    <div class="font-medium text-slate-800 dark:text-slate-200" x-text="t.fecha_hora_cierre ? formatDate(t.fecha_hora_cierre) : 'En Curso'"></div>
                                </td>

                                <!-- Fondo Inicial -->
                                <td class="py-2.5 px-3 text-right font-mono text-xs font-bold text-slate-700 dark:text-slate-300"
                                    x-text="formatCurrency(t.monto_apertura || 0)"></td>

                                <!-- Esperado -->
                                <td class="py-2.5 px-3 text-right font-mono text-xs font-bold text-slate-700 dark:text-slate-300"
                                    x-text="t.monto_esperado !== null && t.monto_esperado !== undefined ? formatCurrency(t.monto_esperado) : '—'"></td>

                                <!-- Contado -->
                                <td class="py-2.5 px-3 text-right font-mono text-xs font-bold text-slate-900 dark:text-white"
                                    x-text="t.monto_cierre !== null && t.monto_cierre !== undefined ? formatCurrency(t.monto_cierre) : '—'"></td>

                                <!-- Estado Arqueo / Diferencia -->
                                <td class="py-2.5 px-3 text-center">
                                    <template x-if="t.fecha_hora_cierre === null || (t.estado !== undefined && Number(t.estado) === 1 && !t.fecha_hora_cierre)">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                                            <span>En Operación</span>
                                        </span>
                                    </template>
                                    <template x-if="t.fecha_hora_cierre !== null && (t.estado === undefined || Number(t.estado) === 0 || t.fecha_hora_cierre)">
                                        <div>
                                            <template x-if="Number(t.diferencia || 0) === 0">
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                                    <i data-lucide="check" class="w-3 h-3"></i>
                                                    <span>Cuadre Exacto</span>
                                                </span>
                                            </template>
                                            <template x-if="Number(t.diferencia || 0) < 0">
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20"
                                                    :title="'Faltante de dinero: ' + formatCurrency(t.diferencia)">
                                                    <i data-lucide="trending-down" class="w-3 h-3"></i>
                                                    <span x-text="'Faltante ' + formatCurrency(t.diferencia)"></span>
                                                </span>
                                            </template>
                                            <template x-if="Number(t.diferencia || 0) > 0">
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20"
                                                    :title="'Sobrante de dinero: +' + formatCurrency(t.diferencia)">
                                                    <i data-lucide="trending-up" class="w-3 h-3"></i>
                                                    <span x-text="'Sobrante +' + formatCurrency(t.diferencia)"></span>
                                                </span>
                                            </template>
                                        </div>
                                    </template>
                                </td>

                                <!-- Acciones -->
                                <td class="py-2.5 px-3 text-center">
                                    <button type="button" @click="openTurnoAuditoriaModal(t.caja_operacion_id)"
                                        class="px-2.5 py-1.5 rounded-xl text-xs font-bold bg-brand-500/10 text-brand-600 dark:text-brand-400 hover:bg-brand-500 hover:text-white border border-brand-500/20 transition-all inline-flex items-center gap-1.5 cursor-pointer shadow-2xs"
                                        title="Ver auditoría integral, facturas y desglose de arqueo">
                                        <i data-lucide="file-check-2" class="w-3.5 h-3.5"></i>
                                        <span>Auditoría & Arqueo</span>
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <!-- Estado Vacío -->
                        <tr x-show="filteredTurnos.length === 0">
                            <td colspan="10" class="py-12 text-center text-slate-400 space-y-2">
                                <i data-lucide="calendar-x" class="w-9 h-9 mx-auto text-slate-500"></i>
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">No se encontraron turnos con los filtros aplicados</p>
                                <p class="text-xs text-slate-400 max-w-sm mx-auto">
                                    Intente cambiar los criterios de búsqueda o verifique la caja física seleccionada.
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
