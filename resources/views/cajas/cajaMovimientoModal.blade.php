{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Modal de Registro de Movimientos de Caja (RF-25)
    Archivo: resources/views/cajas/cajaMovimientoModal.blade.php
    Propósito: Permite a los usuarios a cargo del turno registrar ingresos y egresos
               extraordinarios de efectivo (gastos menores y sencillo) con motivo
               obligatorio para cuadre de arqueo y cierre (RF-25 y RF-28).
    Controlador asociado: App\Http\Controllers\CajaMovimientoVentaController
    Endpoint: POST /api/caja-movimientos-venta
    Integración: Incluido en welcome.blade.php mediante @include('cajas.cajaMovimientoModal')
    =============================================================================
--}}

<div x-show="showCajaMovimientoModal" x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/75 backdrop-blur-sm overflow-y-auto">

    <!-- Contenedor del Modal -->
    <div @click.away="showCajaMovimientoModal = false"
        class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden flex flex-col my-auto transition-all">

        <!-- 1. Cabecera del Modal -->
        <div class="px-5 sm:px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between flex-shrink-0 bg-white dark:bg-dark-900">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center border shadow-xs"
                    :class="cajaMovimientoForm.tipo_movimiento === 'Ingreso' 
                        ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' 
                        : 'bg-rose-500/10 text-rose-500 border-rose-500/20'">
                    <i :data-lucide="cajaMovimientoForm.tipo_movimiento === 'Ingreso' ? 'arrow-down-left' : 'arrow-up-right'" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-white flex items-center gap-2">
                        <span>Movimiento de Efectivo</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border"
                            :class="cajaMovimientoForm.tipo_movimiento === 'Ingreso'
                                ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20'
                                : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20'">
                            RF-25
                        </span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Registro auditado de entradas y salidas durante el turno</p>
                </div>
            </div>
            <button type="button" @click="showCajaMovimientoModal = false"
                class="p-1.5 text-slate-400 hover:text-slate-700 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                title="Cerrar ventana">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- 2. Formulario de Movimiento -->
        <form @submit.prevent="saveCajaMovimiento()" class="p-5 sm:p-6 space-y-4">

            <!-- Selector de Tipo de Movimiento: Ingreso vs Egreso -->
            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                    <span>Tipo de Operación</span>
                    <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-2 gap-2.5">
                    <!-- Opción: Ingreso -->
                    <button type="button" @click="cajaMovimientoForm.tipo_movimiento = 'Ingreso'"
                        class="p-3 rounded-2xl border-2 flex items-center gap-3 transition-all cursor-pointer text-left"
                        :class="cajaMovimientoForm.tipo_movimiento === 'Ingreso'
                            ? 'border-emerald-500 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 shadow-sm shadow-emerald-500/10 ring-2 ring-emerald-500/20'
                            : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50 dark:bg-dark-950 text-slate-600 dark:text-slate-400'">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
                            :class="cajaMovimientoForm.tipo_movimiento === 'Ingreso' ? 'bg-emerald-500 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-500'">
                            <i data-lucide="arrow-down-left" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <div class="font-bold text-xs sm:text-sm">Ingreso (+)</div>
                            <div class="text-[10px] opacity-80">Sencillo, fondo extra</div>
                        </div>
                    </button>

                    <!-- Opción: Egreso -->
                    <button type="button" @click="cajaMovimientoForm.tipo_movimiento = 'Egreso'"
                        class="p-3 rounded-2xl border-2 flex items-center gap-3 transition-all cursor-pointer text-left"
                        :class="cajaMovimientoForm.tipo_movimiento === 'Egreso'
                            ? 'border-rose-500 bg-rose-500/10 text-rose-600 dark:text-rose-400 shadow-sm shadow-rose-500/10 ring-2 ring-rose-500/20'
                            : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50 dark:bg-dark-950 text-slate-600 dark:text-slate-400'">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
                            :class="cajaMovimientoForm.tipo_movimiento === 'Egreso' ? 'bg-rose-500 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-500'">
                            <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <div class="font-bold text-xs sm:text-sm">Egreso (-)</div>
                            <div class="text-[10px] opacity-80">Gasto menor, retiro</div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Selección de Caja Destino -->
            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                    <span>Caja Física / Turno</span>
                    <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="wallet" class="w-4 h-4"></i>
                    </div>
                    <select x-model="cajaMovimientoForm.id_caja" required
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl pl-10 pr-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
                        <option value="">Seleccione una caja abierta...</option>
                        <template x-for="c in cajas" :key="c.caja_id">
                            <option :value="c.caja_id"
                                x-text="'Caja #' + c.caja_id + ' - ' + (c.descripcion_caja || 'Principal') + ' (' + c.estado_caja + ')'"></option>
                        </template>
                    </select>
                </div>

                <!-- Detalle de turno activo asociado si existe -->
                <template x-if="turnoActivo">
                    <p class="text-[11px] text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5 mt-1 font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span x-text="'Turno activo #' + turnoActivo.caja_operacion_id + ' en ' + (turnoActivo.caja?.descripcion_caja || ('Caja #' + turnoActivo.id_caja)) + ' por ' + (turnoActivo.usuario?.nombre_apellido || currentUser.nombre_apellido)"></span>
                    </p>
                </template>
            </div>

            <!-- Importe / Monto del Movimiento -->
            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                    <span>Monto del Movimiento</span>
                    <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none font-bold text-sm"
                        :class="cajaMovimientoForm.tipo_movimiento === 'Ingreso' ? 'text-emerald-500' : 'text-rose-500'"
                        x-text="empresa?.moneda_simbolo || 'C$'"></div>
                    <input type="number" step="0.01" min="0.01" max="999999999.99"
                        x-model="cajaMovimientoForm.monto" required placeholder="0.00"
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl pl-12 pr-4 py-2.5 text-base font-bold text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 font-mono transition-colors">
                </div>

                <!-- Botones de montos rápidos -->
                <div class="flex items-center gap-1.5 pt-1 overflow-x-auto pb-0.5">
                    <span class="text-[10px] text-slate-400 font-medium">Sugeridos:</span>
                    <button type="button" @click="cajaMovimientoForm.monto = '50.00'"
                        class="px-2 py-0.5 rounded-lg text-[11px] font-mono font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-brand-500 hover:text-white transition-colors cursor-pointer border border-slate-200 dark:border-slate-700">+50</button>
                    <button type="button" @click="cajaMovimientoForm.monto = '100.00'"
                        class="px-2 py-0.5 rounded-lg text-[11px] font-mono font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-brand-500 hover:text-white transition-colors cursor-pointer border border-slate-200 dark:border-slate-700">+100</button>
                    <button type="button" @click="cajaMovimientoForm.monto = '200.00'"
                        class="px-2 py-0.5 rounded-lg text-[11px] font-mono font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-brand-500 hover:text-white transition-colors cursor-pointer border border-slate-200 dark:border-slate-700">+200</button>
                    <button type="button" @click="cajaMovimientoForm.monto = '500.00'"
                        class="px-2 py-0.5 rounded-lg text-[11px] font-mono font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-brand-500 hover:text-white transition-colors cursor-pointer border border-slate-200 dark:border-slate-700">+500</button>
                    <button type="button" @click="cajaMovimientoForm.monto = '1000.00'"
                        class="px-2 py-0.5 rounded-lg text-[11px] font-mono font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-brand-500 hover:text-white transition-colors cursor-pointer border border-slate-200 dark:border-slate-700">+1,000</button>
                </div>
            </div>

            <!-- Motivo / Justificación Obligatoria (RF-25) -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        <span>Motivo o Justificación</span>
                        <span class="text-rose-500">*</span>
                    </label>
                    <span class="text-[10px] text-slate-400"
                        x-text="(cajaMovimientoForm.justificacion ? cajaMovimientoForm.justificacion.length : 0) + ' / 255'"></span>
                </div>
                <input type="text" maxlength="255" minlength="3" required
                    x-model="cajaMovimientoForm.justificacion"
                    :placeholder="cajaMovimientoForm.tipo_movimiento === 'Ingreso' 
                        ? 'Ej. Sencillo adicional para cambio en caja' 
                        : 'Ej. Compra urgente de artículos de limpieza o pago de mensajería'"
                    class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">

                <!-- Etiquetas de motivos frecuentes -->
                <div class="space-y-1 pt-1">
                    <div class="text-[10px] text-slate-400 font-medium">Motivos frecuentes:</div>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-if="cajaMovimientoForm.tipo_movimiento === 'Egreso'">
                            <div class="flex flex-wrap gap-1.5">
                                <button type="button" @click="setCajaMovimientoJustificacion('Compra de artículos de limpieza')"
                                    class="px-2 py-1 rounded-lg text-[11px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-rose-500 hover:text-white transition-colors cursor-pointer">Artículos de limpieza</button>
                                <button type="button" @click="setCajaMovimientoJustificacion('Pago de servicio de mensajería / flete')"
                                    class="px-2 py-1 rounded-lg text-[11px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-rose-500 hover:text-white transition-colors cursor-pointer">Mensajería / Flete</button>
                                <button type="button" @click="setCajaMovimientoJustificacion('Compra de papelería y útiles')"
                                    class="px-2 py-1 rounded-lg text-[11px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-rose-500 hover:text-white transition-colors cursor-pointer">Papelería</button>
                                <button type="button" @click="setCajaMovimientoJustificacion('Pago de gasto menor imprevisto')"
                                    class="px-2 py-1 rounded-lg text-[11px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-rose-500 hover:text-white transition-colors cursor-pointer">Gasto menor</button>
                            </div>
                        </template>

                        <template x-if="cajaMovimientoForm.tipo_movimiento === 'Ingreso'">
                            <div class="flex flex-wrap gap-1.5">
                                <button type="button" @click="setCajaMovimientoJustificacion('Aporte adicional de sencillo para cambio')"
                                    class="px-2 py-1 rounded-lg text-[11px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-emerald-500 hover:text-white transition-colors cursor-pointer">Sencillo extra</button>
                                <button type="button" @click="setCajaMovimientoJustificacion('Reposición de fondo de caja chica')"
                                    class="px-2 py-1 rounded-lg text-[11px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-emerald-500 hover:text-white transition-colors cursor-pointer">Reposición de fondo</button>
                                <button type="button" @click="setCajaMovimientoJustificacion('Ingreso extraordinario autorizado')"
                                    class="px-2 py-1 rounded-lg text-[11px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-emerald-500 hover:text-white transition-colors cursor-pointer">Aporte extraordinario</button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Alerta Informativa de Impacto en Arqueo (RF-28) -->
            <div class="p-3 rounded-xl bg-slate-50 dark:bg-dark-950/80 border border-slate-200 dark:border-slate-800 flex items-start gap-2.5 text-xs text-slate-500 dark:text-slate-400">
                <i data-lucide="info" class="w-4 h-4 text-brand-400 flex-shrink-0 mt-0.5"></i>
                <p>
                    Este movimiento se aplicará al arqueo del turno en tiempo real y quedará auditado con tu usuario en la bitácora del sistema (RF-25 / RF-28).
                </p>
            </div>

            <!-- Botones de Acción -->
            <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2.5">
                <button type="button" @click="showCajaMovimientoModal = false"
                    class="px-4 py-2 text-xs sm:text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">
                    Cancelar
                </button>
                <button type="submit" :disabled="isSavingCajaMovimiento"
                    class="px-5 py-2.5 text-xs sm:text-sm font-bold text-white rounded-xl shadow-md transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                    :class="cajaMovimientoForm.tipo_movimiento === 'Ingreso' 
                        ? 'bg-emerald-600 hover:bg-emerald-500 shadow-emerald-600/25 ring-2 ring-emerald-500/20' 
                        : 'bg-rose-600 hover:bg-rose-500 shadow-rose-600/25 ring-2 ring-rose-500/20'">
                    <i x-show="!isSavingCajaMovimiento" :data-lucide="cajaMovimientoForm.tipo_movimiento === 'Ingreso' ? 'arrow-down-left' : 'arrow-up-right'" class="w-4 h-4"></i>
                    <i x-show="isSavingCajaMovimiento" data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                    <span x-text="isSavingCajaMovimiento ? 'Registrando...' : (cajaMovimientoForm.tipo_movimiento === 'Ingreso' ? 'Registrar Ingreso' : 'Registrar Egreso')"></span>
                </button>
            </div>
        </form>
    </div>
</div>
