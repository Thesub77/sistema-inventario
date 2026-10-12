{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Modal de Auditoría Integral y Detalle Histórico de Turno
    Archivo: resources/views/cajas/turnoAuditoriaModal.blade.php
    Propósito: Permite la inspección exhaustiva de cualquier turno de caja (activo o cerrado)
               para fines de auditoría, control financiero y toma de decisiones.
               Presenta:
               1. Ficha técnica del turno (Caja, cajero, supervisor de cierre, duración).
               2. Balance financiero de arqueo (monto inicial, ventas efectivo, esperado, contado, diferencia).
               3. Desglose detallado por métodos de pago (Efectivo, Tarjeta, Transferencia).
               4. Facturas y comprobantes emitidos en el período del turno.
               5. Entradas y salidas extraordinarias de efectivo con justificación.
               6. Cuantificación de pérdidas, devoluciones y mermas de inventario.
               7. Plantilla imprimible formal para respaldo de auditoría física.
    Controlador asociado: App\Http\Controllers\CajaOperacionController
    Endpoint: GET /api/caja-operaciones/{id}
    =============================================================================
--}}

<div x-show="showTurnoAuditoriaModal" x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 bg-black/80 backdrop-blur-sm overflow-y-auto">

    <!-- Contenedor Principal del Modal -->
    <div @click.away="showTurnoAuditoriaModal = false"
        class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl w-full max-w-5xl shadow-2xl overflow-hidden flex flex-col my-auto transition-all max-h-[94vh]">

        <!-- 1. Cabecera del Modal -->
        <div class="px-5 sm:px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between flex-shrink-0 bg-slate-50/80 dark:bg-dark-900/90 backdrop-blur-md">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center border shadow-xs bg-brand-500/10 text-brand-600 dark:text-brand-400 border-brand-500/20">
                    <i data-lucide="file-check-2" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-white flex items-center gap-1.5">
                            <span>Auditoría de Turno #<span x-text="selectedTurnoAuditoria?.caja_operacion_id || ''"></span></span>
                        </h3>
                        <template x-if="selectedTurnoAuditoria">
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold"
                                :class="selectedTurnoAuditoria.fecha_hora_cierre === null || (selectedTurnoAuditoria.estado !== undefined && Number(selectedTurnoAuditoria.estado) === 1 && !selectedTurnoAuditoria.fecha_hora_cierre)
                                    ? 'bg-blue-500/15 text-blue-600 dark:text-blue-400 border border-blue-500/30'
                                    : (Number(selectedTurnoAuditoria.diferencia || 0) === 0
                                        ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30'
                                        : (Number(selectedTurnoAuditoria.diferencia || 0) < 0
                                            ? 'bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30'
                                            : 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30'))"
                                x-text="selectedTurnoAuditoria.fecha_hora_cierre === null || (selectedTurnoAuditoria.estado !== undefined && Number(selectedTurnoAuditoria.estado) === 1 && !selectedTurnoAuditoria.fecha_hora_cierre)
                                    ? 'En Curso (Operativo)'
                                    : (Number(selectedTurnoAuditoria.diferencia || 0) === 0
                                        ? 'Cerrado - Cuadre Exacto'
                                        : (Number(selectedTurnoAuditoria.diferencia || 0) < 0 ? 'Cerrado con Faltante' : 'Cerrado con Sobrante'))">
                            </span>
                        </template>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        <span x-text="selectedTurnoAuditoria?.caja?.descripcion_caja || ('Caja #' + (selectedTurnoAuditoria?.id_caja || ''))"></span>
                        <template x-if="selectedTurnoAuditoria?.fecha_hora_apertura">
                            <span x-text="' • Jornada del ' + formatDateOnly(selectedTurnoAuditoria.fecha_hora_apertura)"></span>
                        </template>
                    </p>
                </div>
            </div>

            <!-- Botones de Acción de Cabecera -->
            <div class="flex items-center gap-2">
                <button type="button" @click="printTurnoAuditoria()"
                    :disabled="loadingTurnoAuditoria || !selectedTurnoAuditoria"
                    class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all cursor-pointer disabled:opacity-50"
                    title="Imprimir informe de arqueo y auditoría">
                    <i data-lucide="printer" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
                    <span class="hidden sm:inline">Imprimir Informe</span>
                </button>
                <button type="button" @click="showTurnoAuditoriaModal = false"
                    class="p-1.5 text-slate-400 hover:text-slate-700 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                    title="Cerrar ventana">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        </div>

        <!-- 2. Estado de Carga -->
        <div x-show="loadingTurnoAuditoria" class="p-12 flex flex-col items-center justify-center space-y-3">
            <i data-lucide="loader-2" class="w-9 h-9 text-brand-500 animate-spin"></i>
            <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">Consultando métricas y arqueo del turno...</p>
        </div>

        <!-- 3. Contenido Principal del Detalle de Auditoría -->
        <div x-show="!loadingTurnoAuditoria && selectedTurnoAuditoria" class="overflow-y-auto p-5 sm:p-6 space-y-5 flex-1">

            <!-- Ficha Técnica de Auditoría (Metadatos del Turno) -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-4 rounded-2xl bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-slate-800 text-xs">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Cajero Apertura</span>
                    <div class="font-bold text-slate-800 dark:text-slate-100 mt-0.5 flex items-center gap-1.5">
                        <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span x-text="selectedTurnoAuditoria?.usuario?.nombre_apellido || 'No especificado'"></span>
                    </div>
                    <span class="text-[11px] text-slate-400 block mt-0.5" x-text="formatDate(selectedTurnoAuditoria?.fecha_hora_apertura)"></span>
                </div>

                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Cierre / Supervisor</span>
                    <div class="font-bold text-slate-800 dark:text-slate-100 mt-0.5 flex items-center gap-1.5">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5 text-brand-400"></i>
                        <span x-text="selectedTurnoAuditoria?.usuario_cierre?.nombre_apellido || (selectedTurnoAuditoria?.fecha_hora_cierre ? selectedTurnoAuditoria?.usuario?.nombre_apellido : 'En curso')"></span>
                    </div>
                    <span class="text-[11px] text-slate-400 block mt-0.5"
                        x-text="selectedTurnoAuditoria?.fecha_hora_cierre ? formatDate(selectedTurnoAuditoria.fecha_hora_cierre) : 'Turno aún abierto'"></span>
                </div>

                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Duración de Turno</span>
                    <div class="font-bold text-slate-800 dark:text-slate-100 mt-0.5 flex items-center gap-1.5">
                        <i data-lucide="clock" class="w-3.5 h-3.5 text-amber-500"></i>
                        <span x-text="selectedTurnoAuditoria?.estadisticas?.duracion_texto || 'En curso'"></span>
                    </div>
                    <span class="text-[11px] text-slate-400 block mt-0.5"
                        x-text="selectedTurnoAuditoria?.caja?.descripcion_caja || ('Caja #' + selectedTurnoAuditoria?.id_caja)"></span>
                </div>

                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Ticket Promedio</span>
                    <div class="font-bold font-mono text-brand-600 dark:text-brand-400 text-sm mt-0.5"
                        x-text="formatCurrency(selectedTurnoAuditoria?.estadisticas?.ticket_promedio || 0)"></div>
                    <span class="text-[11px] text-slate-400 block mt-0.5"
                        x-text="(selectedTurnoAuditoria?.estadisticas?.total_tickets || 0) + ' facturas procesadas'"></span>
                </div>
            </div>

            <!-- Justificación de Cierre si existió descuadre o se registró nota -->
            <template x-if="selectedTurnoAuditoria?.observacion_cierre">
                <div class="p-3.5 rounded-2xl border flex items-start gap-3"
                    :class="Number(selectedTurnoAuditoria?.diferencia || 0) === 0
                        ? 'bg-slate-50 dark:bg-dark-950 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300'
                        : 'bg-amber-500/10 border-amber-500/30 text-amber-900 dark:text-amber-200'">
                    <div class="p-1.5 rounded-xl bg-amber-500/20 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5">
                        <i data-lucide="message-square" class="w-4 h-4"></i>
                    </div>
                    <div class="space-y-0.5 text-xs">
                        <span class="font-bold uppercase tracking-wider text-[10px] opacity-75">Observación / Justificación registrada al cierre:</span>
                        <p class="font-medium" x-text="selectedTurnoAuditoria.observacion_cierre"></p>
                    </div>
                </div>
            </template>

            <!-- 4. Cuadrícula de KPIs de Balance Financiero del Turno -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                <!-- KPI 1: Fondo Inicial de Apertura -->
                <div class="glass-panel p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800">
                    <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Fondo Inicial</span>
                    <div class="font-mono font-black text-lg text-slate-900 dark:text-white mt-1"
                        x-text="formatCurrency(selectedTurnoAuditoria?.monto_apertura || 0)"></div>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 block mt-0.5">Efectivo asignado</span>
                </div>

                <!-- KPI 2: Ventas Brutas Totales -->
                <div class="glass-panel p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800">
                    <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Venta Bruta Total</span>
                    <div class="font-mono font-black text-lg text-brand-600 dark:text-brand-400 mt-1"
                        x-text="formatCurrency(selectedTurnoAuditoria?.estadisticas?.total_ventas_brutas || 0)"></div>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 block mt-0.5"
                        x-text="(selectedTurnoAuditoria?.estadisticas?.total_tickets || 0) + ' transacciones'"></span>
                </div>

                <!-- KPI 3: Efectivo Esperado en Caja -->
                <div class="glass-panel p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800">
                    <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Efectivo Esperado</span>
                    <div class="font-mono font-black text-lg text-slate-900 dark:text-white mt-1"
                        x-text="formatCurrency(selectedTurnoAuditoria?.arqueo?.monto_esperado ?? selectedTurnoAuditoria?.monto_esperado ?? 0)"></div>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 block mt-0.5">Saldo teórico del sistema</span>
                </div>

                <!-- KPI 4: Efectivo Físico Contado -->
                <div class="glass-panel p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800">
                    <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Efectivo Contado</span>
                    <div class="font-mono font-black text-lg text-slate-900 dark:text-white mt-1"
                        x-text="selectedTurnoAuditoria?.monto_cierre !== null && selectedTurnoAuditoria?.monto_cierre !== undefined ? formatCurrency(selectedTurnoAuditoria.monto_cierre) : 'Sin Cerrar'"></div>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 block mt-0.5">Conteo físico liquidado</span>
                </div>

                <!-- KPI 5: Diferencia de Cuadre -->
                <div class="glass-panel p-3.5 rounded-2xl border col-span-2 sm:col-span-1 border-slate-200 dark:border-slate-800"
                    :class="selectedTurnoAuditoria?.monto_cierre === null || selectedTurnoAuditoria?.monto_cierre === undefined
                        ? 'bg-blue-500/5'
                        : (Number(selectedTurnoAuditoria?.diferencia || 0) === 0
                            ? 'bg-emerald-500/5 border-emerald-500/20'
                            : (Number(selectedTurnoAuditoria?.diferencia || 0) < 0
                                ? 'bg-rose-500/5 border-rose-500/20'
                                : 'bg-amber-500/5 border-amber-500/20'))">
                    <span class="text-[10px] uppercase font-bold tracking-wider block"
                        :class="selectedTurnoAuditoria?.monto_cierre === null || selectedTurnoAuditoria?.monto_cierre === undefined
                            ? 'text-blue-500'
                            : (Number(selectedTurnoAuditoria?.diferencia || 0) === 0
                                ? 'text-emerald-500'
                                : (Number(selectedTurnoAuditoria?.diferencia || 0) < 0 ? 'text-rose-500' : 'text-amber-500'))">
                        Diferencia / Cuadre
                    </span>
                    <div class="font-mono font-black text-lg mt-1"
                        :class="selectedTurnoAuditoria?.monto_cierre === null || selectedTurnoAuditoria?.monto_cierre === undefined
                            ? 'text-blue-600 dark:text-blue-400'
                            : (Number(selectedTurnoAuditoria?.diferencia || 0) === 0
                                ? 'text-emerald-600 dark:text-emerald-400'
                                : (Number(selectedTurnoAuditoria?.diferencia || 0) < 0
                                    ? 'text-rose-600 dark:text-rose-400'
                                    : 'text-amber-600 dark:text-amber-400'))"
                        x-text="selectedTurnoAuditoria?.monto_cierre === null || selectedTurnoAuditoria?.monto_cierre === undefined
                            ? 'En Operación'
                            : (Number(selectedTurnoAuditoria?.diferencia || 0) === 0
                                ? 'Exacto C$0.00'
                                : (Number(selectedTurnoAuditoria?.diferencia || 0) > 0 ? '+ ' : '') + formatCurrency(selectedTurnoAuditoria?.diferencia || 0))"></div>
                    <span class="text-[10px] block mt-0.5"
                        :class="Number(selectedTurnoAuditoria?.diferencia || 0) === 0 ? 'text-emerald-600 dark:text-emerald-400' : (Number(selectedTurnoAuditoria?.diferencia || 0) < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-amber-600 dark:text-amber-400')"
                        x-text="selectedTurnoAuditoria?.monto_cierre === null || selectedTurnoAuditoria?.monto_cierre === undefined ? 'Turno en curso' : (Number(selectedTurnoAuditoria?.diferencia || 0) === 0 ? 'Sin discrepancia' : (Number(selectedTurnoAuditoria?.diferencia || 0) < 0 ? 'Faltante de dinero' : 'Sobrante en caja'))"></span>
                </div>
            </div>

            <!-- 5. Selector de Subpestañas del Detalle de Auditoría -->
            <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-2 overflow-x-auto text-xs">
                <button type="button" @click="turnoAuditoriaSubTab = 'resumen'"
                    class="px-3.5 py-1.5 rounded-xl font-bold transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap"
                    :class="turnoAuditoriaSubTab === 'resumen'
                        ? 'bg-brand-600 text-white shadow-xs'
                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'">
                    <i data-lucide="calculator" class="w-3.5 h-3.5"></i>
                    <span>Arqueo & Métricas</span>
                </button>

                <button type="button" @click="turnoAuditoriaSubTab = 'facturas'"
                    class="px-3.5 py-1.5 rounded-xl font-bold transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap"
                    :class="turnoAuditoriaSubTab === 'facturas'
                        ? 'bg-brand-600 text-white shadow-xs'
                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'">
                    <i data-lucide="receipt" class="w-3.5 h-3.5"></i>
                    <span>Facturas Emitidas</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                        :class="turnoAuditoriaSubTab === 'facturas' ? 'bg-brand-700 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300'"
                        x-text="selectedTurnoAuditoria?.ventas?.length || 0"></span>
                </button>

                <button type="button" @click="turnoAuditoriaSubTab = 'movimientos'"
                    class="px-3.5 py-1.5 rounded-xl font-bold transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap"
                    :class="turnoAuditoriaSubTab === 'movimientos'
                        ? 'bg-brand-600 text-white shadow-xs'
                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'">
                    <i data-lucide="arrow-left-right" class="w-3.5 h-3.5"></i>
                    <span>Entradas / Salidas de Efectivo</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                        :class="turnoAuditoriaSubTab === 'movimientos' ? 'bg-brand-700 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300'"
                        x-text="(selectedTurnoAuditoria?.movimientos || []).filter(m => m.id_venta === null).length"></span>
                </button>

                <button type="button" @click="turnoAuditoriaSubTab = 'perdidas'"
                    class="px-3.5 py-1.5 rounded-xl font-bold transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap"
                    :class="turnoAuditoriaSubTab === 'perdidas'
                        ? 'bg-brand-600 text-white shadow-xs'
                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'">
                    <i data-lucide="alert-octagon" class="w-3.5 h-3.5"></i>
                    <span>Pérdidas, Devoluciones & Mermas</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                        :class="turnoAuditoriaSubTab === 'perdidas' ? 'bg-brand-700 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300'"
                        x-text="(selectedTurnoAuditoria?.mermas?.length || 0) + (selectedTurnoAuditoria?.estadisticas?.total_tickets_anulados || 0)"></span>
                </button>
            </div>

            <!-- ================================================================= -->
            <!-- SUBPESTAÑA A: ARQUEO DE EFECTIVO Y MÉTODOS DE PAGO               -->
            <!-- ================================================================= -->
            <div x-show="turnoAuditoriaSubTab === 'resumen'" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Tarjeta 1: Desglose de Efectivo Auditado -->
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-slate-800 space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                            <h4 class="font-display font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                                <i data-lucide="banknote" class="w-4 h-4 text-emerald-500"></i>
                                <span>Flujo Teórico de Efectivo (Cajón)</span>
                            </h4>
                            <span class="text-[10px] text-slate-400">Arqueo matemático</span>
                        </div>

                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between items-center py-1 border-b border-slate-200/60 dark:border-slate-800/60">
                                <span class="text-slate-600 dark:text-slate-400 font-medium">Fondo Inicial de Apertura (+)</span>
                                <span class="font-mono font-bold text-slate-800 dark:text-slate-200"
                                    x-text="formatCurrency(selectedTurnoAuditoria?.arqueo?.monto_apertura || selectedTurnoAuditoria?.monto_apertura || 0)"></span>
                            </div>

                            <div class="flex justify-between items-center py-1 border-b border-slate-200/60 dark:border-slate-800/60">
                                <span class="text-slate-600 dark:text-slate-400 font-medium">Ventas en Efectivo (+)</span>
                                <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400"
                                    x-text="'+ ' + formatCurrency(selectedTurnoAuditoria?.arqueo?.ventas_efectivo || 0)"></span>
                            </div>

                            <div class="flex justify-between items-center py-1 border-b border-slate-200/60 dark:border-slate-800/60">
                                <span class="text-slate-600 dark:text-slate-400 font-medium">Ingresos Extraordinarios Sencillo (+)</span>
                                <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400"
                                    x-text="'+ ' + formatCurrency(selectedTurnoAuditoria?.arqueo?.ingresos_extraordinarios || 0)"></span>
                            </div>

                            <div class="flex justify-between items-center py-1 border-b border-slate-200/60 dark:border-slate-800/60">
                                <span class="text-slate-600 dark:text-slate-400 font-medium">Gastos y Egresos Menores (-)</span>
                                <span class="font-mono font-bold text-rose-600 dark:text-rose-400"
                                    x-text="'- ' + formatCurrency(selectedTurnoAuditoria?.arqueo?.gastos_menores || 0)"></span>
                            </div>

                            <div class="flex justify-between items-center py-1 border-b border-slate-200/60 dark:border-slate-800/60">
                                <span class="text-slate-600 dark:text-slate-400 font-medium">Devoluciones en Efectivo (-)</span>
                                <span class="font-mono font-bold text-rose-600 dark:text-rose-400"
                                    x-text="'- ' + formatCurrency(selectedTurnoAuditoria?.arqueo?.devoluciones_efectivo || 0)"></span>
                            </div>

                            <div class="flex justify-between items-center py-2 bg-slate-100 dark:bg-slate-900 rounded-xl px-2.5 font-bold">
                                <span class="text-slate-900 dark:text-white">= Efectivo Esperado Teórico</span>
                                <span class="font-mono text-slate-900 dark:text-white"
                                    x-text="formatCurrency(selectedTurnoAuditoria?.arqueo?.monto_esperado ?? selectedTurnoAuditoria?.monto_esperado ?? 0)"></span>
                            </div>

                            <div class="flex justify-between items-center py-2 bg-emerald-500/10 dark:bg-emerald-500/15 text-emerald-900 dark:text-emerald-200 rounded-xl px-2.5 font-bold">
                                <span>Conteo Físico Real Declarado</span>
                                <span class="font-mono"
                                    x-text="selectedTurnoAuditoria?.monto_cierre !== null && selectedTurnoAuditoria?.monto_cierre !== undefined ? formatCurrency(selectedTurnoAuditoria.monto_cierre) : 'Pendiente'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta 2: Desglose por Método de Pago & KPIs de Turno -->
                    <div class="space-y-4">
                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-slate-800 space-y-3">
                            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                                <h4 class="font-display font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                                    <i data-lucide="credit-card" class="w-4 h-4 text-brand-500"></i>
                                    <span>Recaudación por Método de Pago</span>
                                </h4>
                                <span class="text-[10px] text-slate-400">Canales de cobro</span>
                            </div>

                            <div class="space-y-2 text-xs">
                                <div class="flex items-center justify-between p-2 rounded-xl bg-slate-100/70 dark:bg-slate-900/60">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div>
                                        <span class="font-medium text-slate-700 dark:text-slate-300">Efectivo</span>
                                    </div>
                                    <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400"
                                        x-text="formatCurrency(selectedTurnoAuditoria?.arqueo?.ventas_efectivo || 0)"></span>
                                </div>

                                <div class="flex items-center justify-between p-2 rounded-xl bg-slate-100/70 dark:bg-slate-900/60">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2.5 h-2.5 rounded-full bg-purple-500"></div>
                                        <span class="font-medium text-slate-700 dark:text-slate-300">Tarjeta (Débito/Crédito)</span>
                                    </div>
                                    <span class="font-mono font-bold text-purple-600 dark:text-purple-400"
                                        x-text="formatCurrency(selectedTurnoAuditoria?.arqueo?.ventas_tarjeta || 0)"></span>
                                </div>

                                <div class="flex items-center justify-between p-2 rounded-xl bg-slate-100/70 dark:bg-slate-900/60">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2.5 h-2.5 rounded-full bg-blue-500"></div>
                                        <span class="font-medium text-slate-700 dark:text-slate-300">Transferencia Bancaria</span>
                                    </div>
                                    <span class="font-mono font-bold text-blue-600 dark:text-blue-400"
                                        x-text="formatCurrency(selectedTurnoAuditoria?.arqueo?.ventas_transferencia || 0)"></span>
                                </div>

                                <div class="flex justify-between items-center pt-2 border-t border-slate-200 dark:border-slate-800 font-bold">
                                    <span class="text-slate-900 dark:text-white">Total Facturado en Turno</span>
                                    <span class="font-mono text-brand-600 dark:text-brand-400 text-sm"
                                        x-text="formatCurrency(selectedTurnoAuditoria?.estadisticas?.total_ventas_brutas || 0)"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Resumen Rápido de Cuantificación de Pérdidas -->
                        <div class="p-3.5 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-900 dark:text-rose-200 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2.5">
                                <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-500 shrink-0"></i>
                                <div>
                                    <span class="font-bold block">Pérdidas Totales del Período</span>
                                    <span class="text-[11px] text-rose-700 dark:text-rose-300/80">Devoluciones + Mermas de inventario</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="font-mono font-black text-sm text-rose-600 dark:text-rose-400 block"
                                    x-text="formatCurrency((Number(selectedTurnoAuditoria?.estadisticas?.total_devoluciones_monto || 0) + Number(selectedTurnoAuditoria?.estadisticas?.total_mermas_costo || 0)))"></span>
                                <span class="text-[10px] text-rose-600/80 dark:text-rose-400/80"
                                    x-text="(selectedTurnoAuditoria?.estadisticas?.total_tickets_anulados || 0) + ' anulaciones • ' + (selectedTurnoAuditoria?.mermas?.length || 0) + ' mermas'"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================================================================= -->
            <!-- SUBPESTAÑA B: FACTURAS EMITIDAS EN EL TURNO                      -->
            <!-- ================================================================= -->
            <div x-show="turnoAuditoriaSubTab === 'facturas'" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="font-display font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="receipt" class="w-4 h-4 text-brand-500"></i>
                        <span>Comprobantes y Facturas Emitidas (<span x-text="selectedTurnoAuditoria?.ventas?.length || 0"></span>)</span>
                    </h4>
                    <span class="text-xs text-slate-400">Total facturado: <strong class="font-mono text-slate-900 dark:text-white" x-text="formatCurrency(selectedTurnoAuditoria?.estadisticas?.total_ventas_brutas || 0)"></strong></span>
                </div>

                <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead class="bg-slate-50 dark:bg-dark-950 uppercase text-[10px] font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="py-2 px-3">Código</th>
                                <th class="py-2 px-3">Cliente</th>
                                <th class="py-2 px-3">Vendedor</th>
                                <th class="py-2 px-3">Método Pago</th>
                                <th class="py-2 px-3">Fecha y Hora</th>
                                <th class="py-2 px-3 text-center">Estado</th>
                                <th class="py-2 px-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            <template x-for="v in (selectedTurnoAuditoria?.ventas || [])" :key="v.venta_id">
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="py-2 px-3 font-mono font-bold text-brand-600 dark:text-brand-400" x-text="v.codigo_venta"></td>
                                    <td class="py-2 px-3 font-medium text-slate-800 dark:text-slate-200" x-text="v.cliente_nombre || 'Consumidor Final'"></td>
                                    <td class="py-2 px-3 text-slate-500 dark:text-slate-400" x-text="v.usuario?.nombre_apellido || 'Cajero'"></td>
                                    <td class="py-2 px-3">
                                        <span class="px-2 py-0.5 rounded-md font-semibold text-[10px]"
                                            :class="{
                                                'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20': String(v.metodo_pago).toLowerCase() === 'efectivo',
                                                'bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20': String(v.metodo_pago).toLowerCase() === 'transferencia',
                                                'bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20': ['tarjeta', 'debito', 'credito'].includes(String(v.metodo_pago).toLowerCase())
                                            }"
                                            x-text="v.metodo_pago"></span>
                                    </td>
                                    <td class="py-2 px-3 text-slate-500 dark:text-slate-400" x-text="formatDate(v.fecha_hora_venta)"></td>
                                    <td class="py-2 px-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                            :class="Number(v.estado) === 1 ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20'"
                                            x-text="Number(v.estado) === 1 ? 'Completada' : 'Anulada'"></span>
                                    </td>
                                    <td class="py-2 px-3 text-right font-mono font-bold"
                                        :class="Number(v.estado) === 1 ? 'text-slate-900 dark:text-white' : 'text-slate-400 line-through'"
                                        x-text="formatCurrency(v.total_venta)"></td>
                                </tr>
                            </template>

                            <tr x-show="!selectedTurnoAuditoria?.ventas || selectedTurnoAuditoria.ventas.length === 0">
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    No se registraron ventas en este turno de caja.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ================================================================= -->
            <!-- SUBPESTAÑA C: ENTRADAS Y SALIDAS DE EFECTIVO                     -->
            <!-- ================================================================= -->
            <div x-show="turnoAuditoriaSubTab === 'movimientos'" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="font-display font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="arrow-left-right" class="w-4 h-4 text-emerald-500"></i>
                        <span>Movimientos Extraordinarios de Caja</span>
                    </h4>
                    <span class="text-xs text-slate-400">Ingresos de sencillo y gastos menores</span>
                </div>

                <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead class="bg-slate-50 dark:bg-dark-950 uppercase text-[10px] font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="py-2 px-3">Código</th>
                                <th class="py-2 px-3">Tipo</th>
                                <th class="py-2 px-3">Concepto / Motivo</th>
                                <th class="py-2 px-3">Fecha y Hora</th>
                                <th class="py-2 px-3 text-right">Importe</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            <template x-for="mov in (selectedTurnoAuditoria?.movimientos || []).filter(m => m.id_venta === null)" :key="mov.caja_movimiento_venta_id">
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="py-2 px-3 font-mono font-medium text-slate-500" x-text="getMovimientoCodigo(mov)"></td>
                                    <td class="py-2 px-3">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold"
                                            :class="Number(mov.monto_movimiento) > 0 ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20'">
                                            <i :data-lucide="Number(mov.monto_movimiento) > 0 ? 'arrow-down-left' : 'arrow-up-right'" class="w-3 h-3"></i>
                                            <span x-text="Number(mov.monto_movimiento) > 0 ? 'Ingreso Sencillo (+)' : 'Gasto / Egreso (-)'"></span>
                                        </span>
                                    </td>
                                    <td class="py-2 px-3 font-medium text-slate-800 dark:text-slate-200" x-text="getMovimientoConcepto(mov)"></td>
                                    <td class="py-2 px-3 text-slate-500 dark:text-slate-400" x-text="formatDate(mov.fecha_hora_movimiento)"></td>
                                    <td class="py-2 px-3 text-right font-mono font-bold"
                                        :class="Number(mov.monto_movimiento) > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                        <span x-text="(Number(mov.monto_movimiento) > 0 ? '+ ' : '- ') + formatCurrency(Math.abs(Number(mov.monto_movimiento)))"></span>
                                    </td>
                                </tr>
                            </template>

                            <tr x-show="!selectedTurnoAuditoria?.movimientos || selectedTurnoAuditoria.movimientos.filter(m => m.id_venta === null).length === 0">
                                <td colspan="5" class="py-8 text-center text-slate-400">
                                    No se registraron movimientos extraordinarios (ingresos manuales o egresos) en este turno.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ================================================================= -->
            <!-- SUBPESTAÑA D: CUANTIFICACIÓN DE PÉRDIDAS, DEVOLUCIONES Y MERMAS   -->
            <!-- ================================================================= -->
            <div x-show="turnoAuditoriaSubTab === 'perdidas'" class="space-y-4">
                <!-- Sección 1: Facturas Anuladas y Devoluciones -->
                <div class="space-y-2">
                    <h5 class="font-display font-bold text-xs uppercase tracking-wider text-rose-600 dark:text-rose-400 flex items-center gap-1.5">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        <span>Facturas Anuladas y Devoluciones del Período</span>
                    </h5>

                    <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800">
                        <table class="w-full text-left text-xs whitespace-nowrap">
                            <thead class="bg-slate-50 dark:bg-dark-950 uppercase text-[10px] font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                                <tr>
                                    <th class="py-2 px-3">Código Factura</th>
                                    <th class="py-2 px-3">Cliente</th>
                                    <th class="py-2 px-3">Método de Reembolso</th>
                                    <th class="py-2 px-3">Fecha Anulación</th>
                                    <th class="py-2 px-3 text-right">Monto Devuelto</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                <template x-for="v in (selectedTurnoAuditoria?.ventas || []).filter(v => Number(v.estado) === 0)" :key="'anulada-' + v.venta_id">
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                        <td class="py-2 px-3 font-mono font-bold text-rose-600 dark:text-rose-400" x-text="v.codigo_venta"></td>
                                        <td class="py-2 px-3 text-slate-700 dark:text-slate-300" x-text="v.cliente_nombre || 'Consumidor Final'"></td>
                                        <td class="py-2 px-3" x-text="v.metodo_pago"></td>
                                        <td class="py-2 px-3 text-slate-500 dark:text-slate-400" x-text="formatDate(v.fecha_hora_venta)"></td>
                                        <td class="py-2 px-3 text-right font-mono font-bold text-rose-600 dark:text-rose-400" x-text="formatCurrency(v.total_venta)"></td>
                                    </tr>
                                </template>
                                <tr x-show="!selectedTurnoAuditoria?.ventas || selectedTurnoAuditoria.ventas.filter(v => Number(v.estado) === 0).length === 0">
                                    <td colspan="5" class="py-4 text-center text-slate-400 text-xs">
                                        Excelente: No se produjeron ventas anuladas ni devoluciones en este turno.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Sección 2: Mermas de Inventario registradas en el turno -->
                <div class="space-y-2 pt-2">
                    <div class="flex items-center justify-between">
                        <h5 class="font-display font-bold text-xs uppercase tracking-wider text-rose-600 dark:text-rose-400 flex items-center gap-1.5">
                            <i data-lucide="package-x" class="w-3.5 h-3.5"></i>
                            <span>Mermas y Daños de Stock Registrados en la Jornada</span>
                        </h5>
                        <span class="text-xs text-rose-600 dark:text-rose-400 font-bold">
                            Costo Total Pérdida: <span class="font-mono" x-text="formatCurrency(selectedTurnoAuditoria?.estadisticas?.total_mermas_costo || 0)"></span>
                        </span>
                    </div>

                    <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800">
                        <table class="w-full text-left text-xs whitespace-nowrap">
                            <thead class="bg-slate-50 dark:bg-dark-950 uppercase text-[10px] font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                                <tr>
                                    <th class="py-2 px-3">Producto</th>
                                    <th class="py-2 px-3 text-center">Cantidad Mermada</th>
                                    <th class="py-2 px-3">Motivo / Justificación</th>
                                    <th class="py-2 px-3">Registrado por</th>
                                    <th class="py-2 px-3">Fecha y Hora</th>
                                    <th class="py-2 px-3 text-right">Costo Pérdida</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                <template x-for="m in (selectedTurnoAuditoria?.mermas || [])" :key="m.movimiento_inventario_id">
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                        <td class="py-2 px-3">
                                            <div class="font-bold text-slate-800 dark:text-slate-200" x-text="m.producto?.nombre_producto || ('Producto #' + m.id_producto)"></div>
                                            <div class="text-[10px] font-mono text-slate-400" x-text="m.producto?.codigo_barra || ''"></div>
                                        </td>
                                        <td class="py-2 px-3 text-center font-bold text-rose-600 dark:text-rose-400 font-mono" x-text="m.cantidad_movimimiento + ' uds'"></td>
                                        <td class="py-2 px-3 text-slate-700 dark:text-slate-300 max-w-xs truncate" x-text="m.motivo || 'Pérdida por merma de stock'"></td>
                                        <td class="py-2 px-3 text-slate-500 dark:text-slate-400" x-text="m.usuario?.nombre_apellido || 'Usuario'"></td>
                                        <td class="py-2 px-3 text-slate-500 dark:text-slate-400" x-text="formatDate(m.fecha_movimiento)"></td>
                                        <td class="py-2 px-3 text-right font-mono font-bold text-rose-600 dark:text-rose-400"
                                            x-text="formatCurrency(m.costo_total_perdida || 0)"></td>
                                    </tr>
                                </template>
                                <tr x-show="!selectedTurnoAuditoria?.mermas || selectedTurnoAuditoria.mermas.length === 0">
                                    <td colspan="6" class="py-4 text-center text-slate-400 text-xs">
                                        No se registraron mermas ni deterioros de inventario durante el horario de este turno.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <!-- 6. Pie del Modal con Acciones de Cierre -->
        <div class="px-5 sm:px-6 py-3.5 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between flex-shrink-0 bg-slate-50/80 dark:bg-dark-900/90">
            <span class="text-xs text-slate-400">
                Auditoría certificada por el sistema financiero FacturaStock Pro
            </span>
            <button type="button" @click="showTurnoAuditoriaModal = false"
                class="px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs transition-colors cursor-pointer">
                Cerrar Ventana
            </button>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- PLANTILLA IMPRIMIBLE FORMAL DE AUDITORÍA Y ARQUEO (Carta / A4 Formal)     -->
    <!-- Visible únicamente durante la orden de impresión (print)                  -->
    <!-- ========================================================================= -->
    <div id="printableTurnoAuditoriaArea" class="hidden">
        <template x-if="selectedTurnoAuditoria">
            <div class="p-6 text-black bg-white space-y-4 font-sans text-xs">
                <!-- Membrete de la Empresa -->
                <div class="border-b-2 border-black pb-3 text-center space-y-1">
                    <h2 class="text-xl font-black uppercase tracking-wider" x-text="empresa?.nombre_comercial || 'FACTURASTOCK PRO'"></h2>
                    <p class="text-xs font-semibold" x-text="empresa?.razon_social ? 'Razón Social: ' + empresa.razon_social : ''"></p>
                    <p class="text-xs font-mono font-bold" x-text="empresa?.numero_ruc ? 'RUC: ' + empresa.numero_ruc : ''"></p>
                    <p class="text-[11px]" x-text="empresa?.direccion_fisica || ''"></p>
                    <h3 class="text-sm font-extrabold uppercase mt-2 underline">ACTA FORMAL DE AUDITORÍA Y ARQUEO DE CAJA</h3>
                </div>

                <!-- Datos del Turno Auditado -->
                <div class="grid grid-cols-2 gap-2 text-xs border border-black p-3">
                    <div><strong>Turno #:</strong> <span x-text="selectedTurnoAuditoria.caja_operacion_id"></span></div>
                    <div><strong>Caja Física:</strong> <span x-text="selectedTurnoAuditoria.caja?.descripcion_caja || ('Caja #' + selectedTurnoAuditoria.id_caja)"></span></div>
                    <div><strong>Cajero Responsable:</strong> <span x-text="selectedTurnoAuditoria.usuario?.nombre_apellido || ''"></span></div>
                    <div><strong>Supervisor de Cierre:</strong> <span x-text="selectedTurnoAuditoria.usuario_cierre?.nombre_apellido || selectedTurnoAuditoria.usuario?.nombre_apellido || 'N/A'"></span></div>
                    <div><strong>Fecha Apertura:</strong> <span x-text="formatDate(selectedTurnoAuditoria.fecha_hora_apertura)"></span></div>
                    <div><strong>Fecha Cierre:</strong> <span x-text="selectedTurnoAuditoria.fecha_hora_cierre ? formatDate(selectedTurnoAuditoria.fecha_hora_cierre) : 'Turno en curso'"></span></div>
                    <div><strong>Duración Total:</strong> <span x-text="selectedTurnoAuditoria.estadisticas?.duracion_texto || 'En curso'"></span></div>
                    <div><strong>Estado Cuadre:</strong> <span x-text="Number(selectedTurnoAuditoria.diferencia || 0) === 0 ? 'CUADRE EXACTO' : (Number(selectedTurnoAuditoria.diferencia || 0) < 0 ? 'DESCUADRE (FALTANTE)' : 'DESCUADRE (SOBRANTE)')"></span></div>
                </div>

                <!-- Tabla de Balance y Arqueo Físico -->
                <div>
                    <h4 class="font-bold uppercase text-[11px] mb-1">1. Liquidación y Cuadre de Efectivo</h4>
                    <table class="w-full border border-black text-xs">
                        <tbody>
                            <tr>
                                <td class="p-1 border border-black font-semibold">Fondo Inicial de Apertura (+)</td>
                                <td class="p-1 border border-black text-right font-mono" x-text="formatCurrency(selectedTurnoAuditoria.monto_apertura || 0)"></td>
                            </tr>
                            <tr>
                                <td class="p-1 border border-black font-semibold">Ventas en Efectivo (+)</td>
                                <td class="p-1 border border-black text-right font-mono" x-text="formatCurrency(selectedTurnoAuditoria.arqueo?.ventas_efectivo || 0)"></td>
                            </tr>
                            <tr>
                                <td class="p-1 border border-black font-semibold">Ingresos Extraordinarios de Efectivo (+)</td>
                                <td class="p-1 border border-black text-right font-mono" x-text="formatCurrency(selectedTurnoAuditoria.arqueo?.ingresos_extraordinarios || 0)"></td>
                            </tr>
                            <tr>
                                <td class="p-1 border border-black font-semibold">Gastos Menores / Egresos de Efectivo (-)</td>
                                <td class="p-1 border border-black text-right font-mono" x-text="'- ' + formatCurrency(selectedTurnoAuditoria.arqueo?.gastos_menores || 0)"></td>
                            </tr>
                            <tr>
                                <td class="p-1 border border-black font-semibold">Devoluciones en Efectivo (-)</td>
                                <td class="p-1 border border-black text-right font-mono" x-text="'- ' + formatCurrency(selectedTurnoAuditoria.arqueo?.devoluciones_efectivo || 0)"></td>
                            </tr>
                            <tr class="font-bold bg-slate-100">
                                <td class="p-1 border border-black uppercase">Efectivo Total Esperado por Sistema</td>
                                <td class="p-1 border border-black text-right font-mono" x-text="formatCurrency(selectedTurnoAuditoria.arqueo?.monto_esperado ?? selectedTurnoAuditoria.monto_esperado ?? 0)"></td>
                            </tr>
                            <tr class="font-bold">
                                <td class="p-1 border border-black uppercase">Efectivo Físico Contado Real</td>
                                <td class="p-1 border border-black text-right font-mono" x-text="selectedTurnoAuditoria.monto_cierre !== null ? formatCurrency(selectedTurnoAuditoria.monto_cierre) : 'Sin Cerrar'"></td>
                            </tr>
                            <tr class="font-black bg-slate-200">
                                <td class="p-1 border border-black uppercase">Diferencia Final (Descuadre)</td>
                                <td class="p-1 border border-black text-right font-mono" x-text="(Number(selectedTurnoAuditoria.diferencia || 0) > 0 ? '+ ' : '') + formatCurrency(selectedTurnoAuditoria.diferencia || 0)"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Desglose por Método de Pago -->
                <div>
                    <h4 class="font-bold uppercase text-[11px] mb-1">2. Facturación Total por Canales de Cobro</h4>
                    <table class="w-full border border-black text-xs">
                        <thead>
                            <tr class="bg-slate-100 font-bold">
                                <th class="p-1 border border-black text-left">Método de Pago</th>
                                <th class="p-1 border border-black text-right">Monto Facturado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="p-1 border border-black">Efectivo</td>
                                <td class="p-1 border border-black text-right font-mono" x-text="formatCurrency(selectedTurnoAuditoria.arqueo?.ventas_efectivo || 0)"></td>
                            </tr>
                            <tr>
                                <td class="p-1 border border-black">Tarjeta Débito / Crédito</td>
                                <td class="p-1 border border-black text-right font-mono" x-text="formatCurrency(selectedTurnoAuditoria.arqueo?.ventas_tarjeta || 0)"></td>
                            </tr>
                            <tr>
                                <td class="p-1 border border-black">Transferencia Bancaria</td>
                                <td class="p-1 border border-black text-right font-mono" x-text="formatCurrency(selectedTurnoAuditoria.arqueo?.ventas_transferencia || 0)"></td>
                            </tr>
                            <tr class="font-bold bg-slate-100">
                                <td class="p-1 border border-black uppercase">Ventas Brutas Totales</td>
                                <td class="p-1 border border-black text-right font-mono" x-text="formatCurrency(selectedTurnoAuditoria.estadisticas?.total_ventas_brutas || 0)"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Resumen de Pérdidas y Justificación -->
                <div class="border border-black p-2 space-y-1">
                    <div class="flex justify-between font-bold">
                        <span>Pérdidas por Mermas de Inventario:</span>
                        <span class="font-mono" x-text="formatCurrency(selectedTurnoAuditoria.estadisticas?.total_mermas_costo || 0)"></span>
                    </div>
                    <div class="flex justify-between font-bold">
                        <span>Devoluciones / Ventas Anuladas:</span>
                        <span class="font-mono" x-text="formatCurrency(selectedTurnoAuditoria.estadisticas?.total_devoluciones_monto || 0)"></span>
                    </div>
                    <template x-if="selectedTurnoAuditoria.observacion_cierre">
                        <div class="pt-1 text-xs border-t border-black mt-1">
                            <strong>Justificación de Cierre:</strong>
                            <p x-text="selectedTurnoAuditoria.observacion_cierre"></p>
                        </div>
                    </template>
                </div>

                <!-- Firmas de Auditoría -->
                <div class="pt-12 grid grid-cols-2 gap-12 text-center text-xs">
                    <div>
                        <div class="border-t border-black pt-1 font-bold" x-text="selectedTurnoAuditoria.usuario?.nombre_apellido || 'Cajero Responsable'"></div>
                        <div class="text-[10px] text-gray-600">Cajero / Operador de Turno</div>
                    </div>
                    <div>
                        <div class="border-t border-black pt-1 font-bold" x-text="selectedTurnoAuditoria.usuario_cierre?.nombre_apellido || 'Supervisor / Administrador'"></div>
                        <div class="text-[10px] text-gray-600">Auditor / Supervisor de Caja</div>
                    </div>
                </div>
            </div>
        </template>
    </div>

</div>
