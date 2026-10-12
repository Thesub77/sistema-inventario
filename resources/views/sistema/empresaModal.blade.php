{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Modal de Ajustes y Datos del Negocio (Empresa)
    Archivo: resources/views/sistema/empresaModal.blade.php
    Propósito: Permite al Administrador consultar y actualizar los datos fiscales y
               comerciales de la empresa para tickets y comprobantes (Issue #36 / PR #43).
    Controlador asociado: App\Http\Controllers\EmpresaController
    Modelo: App\Models\Empresa
    Endpoints: GET /api/empresa, PUT /api/empresa
    Integración: Incluido en welcome.blade.php mediante @include('sistema.empresaModal')
    =============================================================================
--}}

<div x-show="showEmpresaModal" x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-black/70 backdrop-blur-sm overflow-y-auto">

    <!-- Contenedor Principal del Modal con Altura Controlada y Flex Column -->
    <div @click.away="showEmpresaModal = false"
        class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl w-full max-w-5xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh] my-auto transition-all">

        <!-- 1. Cabecera Fija del Modal (Siempre visible arriba) -->
        <div class="px-5 sm:px-7 py-3.5 sm:py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between flex-shrink-0 bg-white dark:bg-dark-900 z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-500/10 text-brand-600 dark:text-brand-400 flex items-center justify-center border border-brand-500/20 shadow-xs">
                    <i data-lucide="store" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-white flex items-center gap-2">
                        <span>Datos del Negocio y Facturación</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">Fiscal</span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Configure la identidad comercial que se imprime en tickets y comprobantes</p>
                </div>
            </div>
            <button type="button" @click="showEmpresaModal = false"
                class="p-1.5 text-slate-400 hover:text-slate-700 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                title="Cerrar ventana">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- 2. Formulario y Cuerpo con Scroll Interno si es necesario -->
        <form @submit.prevent="saveEmpresa()" id="empresaFormElement" class="flex-1 overflow-y-auto flex flex-col justify-between">
            <div class="p-5 sm:p-6 grid grid-cols-1 lg:grid-cols-12 gap-5 sm:gap-6">

                <!-- Columna Izquierda: Formulario de Campos (7 columnas en desktop) -->
                <div class="lg:col-span-7 space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                        <!-- Nombre Comercial -->
                        <div class="space-y-1 sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                <span>Nombre Comercial</span>
                                <span class="text-rose-500 font-bold">*</span>
                            </label>
                            <input type="text"
                                x-model="empresaForm.nombre_comercial"
                                required
                                maxlength="128"
                                placeholder="Ej. Sistema Inventario & POS"
                                class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors font-medium">
                        </div>

                        <!-- Razón Social -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                <span>Razón Social / Denominación Legal</span>
                            </label>
                            <input type="text"
                                x-model="empresaForm.razon_social"
                                maxlength="128"
                                placeholder="Ej. Comercial S.A."
                                class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
                        </div>

                        <!-- Número RUC -->
                        <div class="space-y-1">
                            <label class="flex items-center justify-between text-xs font-bold text-slate-700 dark:text-slate-300">
                                <span>Número RUC / Cédula</span>
                                <span class="text-[10px] font-normal text-slate-400 font-sans">Opcional</span>
                            </label>
                            <input type="text"
                                x-model="empresaForm.numero_ruc"
                                maxlength="32"
                                placeholder="Ej. J0310000000001 (opcional)"
                                class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors font-mono">
                        </div>

                        <!-- Teléfono de Contacto -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                <span>Teléfono de Contacto</span>
                                <span class="text-rose-500 font-bold">*</span>
                            </label>
                            <input type="text"
                                x-model="empresaForm.telefono_contacto"
                                required
                                maxlength="32"
                                placeholder="Ej. 2244-6688"
                                class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors font-mono">
                        </div>

                        <!-- Correo Electrónico -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                <span>Correo Electrónico</span>
                            </label>
                            <input type="email"
                                x-model="empresaForm.correo_contacto"
                                maxlength="128"
                                placeholder="Ej. sistemapos@gmail.com"
                                class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
                        </div>

                        <!-- Dirección Física -->
                        <div class="space-y-1 sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                <span>Dirección Física del Establecimiento</span>
                                <span class="text-rose-500 font-bold">*</span>
                            </label>
                            <input type="text"
                                x-model="empresaForm.direccion_fisica"
                                required
                                maxlength="255"
                                placeholder="Ej. Managua, Nicaragua"
                                class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
                        </div>

                        <!-- Símbolo de Moneda -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                <span>Símbolo de Moneda</span>
                                <span class="text-rose-500 font-bold">*</span>
                            </label>
                            <div class="flex items-center gap-1.5">
                                <input type="text"
                                    x-model="empresaForm.moneda_simbolo"
                                    required
                                    maxlength="8"
                                    placeholder="C$"
                                    class="w-20 bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-2.5 py-2 text-xs sm:text-sm text-center font-black font-mono text-slate-900 dark:text-slate-100 focus:outline-none focus:border-brand-500 transition-colors">
                                <button type="button" @click="empresaForm.moneda_simbolo = 'C$'"
                                    :class="empresaForm.moneda_simbolo === 'C$' ? 'bg-brand-600 text-white font-bold border-brand-600 shadow-xs' : 'bg-slate-100 dark:bg-dark-900 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-200'"
                                    class="px-2.5 py-2 text-xs font-mono font-bold rounded-xl border transition-all cursor-pointer">
                                    C$
                                </button>
                                <button type="button" @click="empresaForm.moneda_simbolo = '$'"
                                    :class="empresaForm.moneda_simbolo === '$' ? 'bg-brand-600 text-white font-bold border-brand-600 shadow-xs' : 'bg-slate-100 dark:bg-dark-900 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-200'"
                                    class="px-2.5 py-2 text-xs font-mono font-bold rounded-xl border transition-all cursor-pointer">
                                    $
                                </button>
                            </div>
                        </div>

                        <!-- Régimen Tributario (Ley 822) -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                <span>Régimen Fiscal (Ley 822)</span>
                                <span class="text-rose-500 font-bold">*</span>
                            </label>
                            <select x-model="empresaForm.regimen_tributario"
                                required
                                class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
                                <option value="Cuota Fija">Cuota Fija (Régimen Simplificado)</option>
                                <option value="Régimen General">Régimen General</option>
                            </select>
                        </div>

                        <!-- Techo Mensual Cuota Fija (C$) -->
                        <div class="space-y-1 sm:col-span-2">
                            <label class="flex items-center justify-between text-xs font-bold text-slate-700 dark:text-slate-300">
                                <span>Techo Mensual Cuota Fija (Parámetro Fiscal)</span>
                                <span class="text-[10px] font-mono text-emerald-600 dark:text-emerald-400 font-bold" x-text="empresaForm.moneda_simbolo || 'C$'"></span>
                            </label>
                            <input type="number"
                                step="1000"
                                min="0"
                                x-model.number="empresaForm.techo_mensual_cuota_fija"
                                required
                                placeholder="100000.00"
                                class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors font-mono">
                            <p class="text-[10px] text-slate-400">Límite legal mensual Ley 822 (Por defecto C$ 100,000.00 / C$ 1,200,000 anual)</p>
                        </div>

                        <!-- Mensaje Pie de Ticket -->
                        <div class="space-y-1 sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                <span>Mensaje al Pie del Ticket</span>
                            </label>
                            <input type="text"
                                x-model="empresaForm.mensaje_pie_ticket"
                                maxlength="255"
                                placeholder="Ej. ¡Gracias por su compra!"
                                class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Vista Previa Realista de Ticket Térmico (5 columnas en desktop) -->
                <div class="lg:col-span-5 flex flex-col justify-start">
                    <div class="p-4 rounded-2xl bg-slate-100 dark:bg-dark-950/80 border border-slate-200 dark:border-slate-800 space-y-3">
                        <div class="flex items-center justify-between text-xs font-bold text-slate-600 dark:text-slate-300">
                            <span class="flex items-center gap-1.5">
                                <i data-lucide="eye" class="w-4 h-4 text-brand-500"></i>
                                <span>Vista Previa de Comprobante</span>
                            </span>
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded-md bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700">80mm Térmico</span>
                        </div>

                        <!-- Ticket Papel Realista -->
                        <div class="bg-white text-black p-4 sm:p-5 rounded-xl border border-dashed border-slate-300 shadow-sm font-mono text-[11px] leading-relaxed space-y-2.5 max-w-sm mx-auto w-full">
                            <!-- Encabezado de Ticket -->
                            <div class="text-center space-y-0.5">
                                <h4 class="font-sans font-black text-sm uppercase text-black tracking-tight"
                                    x-text="empresaForm.nombre_comercial || 'SISTEMA INVENTARIO & POS'"></h4>
                                <p class="text-[10px] font-bold text-black"
                                    x-show="empresaForm.razon_social"
                                    x-text="empresaForm.razon_social"></p>
                                <p class="text-[10px] text-black" x-show="empresaForm.numero_ruc || empresaForm.telefono_contacto">
                                    <span x-show="empresaForm.numero_ruc" x-text="'RUC: ' + empresaForm.numero_ruc"></span>
                                    <span x-show="empresaForm.numero_ruc && empresaForm.telefono_contacto"> | </span>
                                    <span x-show="empresaForm.telefono_contacto" x-text="'Tel: ' + (empresaForm.telefono_contacto || '2244-6688')"></span>
                                </p>
                                <p class="text-[10px] text-black"
                                    x-text="empresaForm.direccion_fisica || 'Managua, Nicaragua'"></p>
                                <p class="text-[9px] text-black"
                                    x-show="empresaForm.correo_contacto"
                                    x-text="empresaForm.correo_contacto"></p>
                            </div>

                            <div class="border-b border-dashed border-black"></div>

                            <div class="text-center py-1 bg-neutral-100 rounded border border-black font-sans font-black text-[10px] uppercase tracking-wider text-black">
                                *** COMPROBANTE DE PAGO ***
                            </div>

                            <div class="text-[10px] space-y-0.5 text-black">
                                <div class="flex justify-between">
                                    <span>FACTURA N°:</span>
                                    <span class="font-bold">FAC-2026-0001</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Moneda:</span>
                                    <span class="font-bold" x-text="empresaForm.moneda_simbolo || 'C$'"></span>
                                </div>
                                <div class="flex justify-between" x-show="empresaForm.regimen_tributario">
                                    <span>Régimen:</span>
                                    <span class="font-bold" x-text="empresaForm.regimen_tributario"></span>
                                </div>
                            </div>

                            <div class="border-b border-dashed border-black"></div>

                            <!-- Pie de Ticket -->
                            <div class="text-center text-[10px] text-black pt-1">
                                <p class="font-bold text-black" x-text="empresaForm.mensaje_pie_ticket || '¡Gracias por su compra!'"></p>
                                <p class="text-[9px] text-black">Factura emitida electrónicamente</p>
                            </div>
                        </div>

                        <p class="text-[10px] text-center text-slate-400">Actualización reactiva en tickets y comprobantes.</p>
                    </div>
                </div>
            </div>

            <!-- 3. Botones de Acción Fijos al Pie del Modal (Siempre visibles) -->
            <div class="px-5 sm:px-7 py-3 sm:py-3.5 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2.5 bg-slate-50/80 dark:bg-dark-950/60 flex-shrink-0 z-10">
                <button type="button"
                    @click="showEmpresaModal = false"
                    :disabled="isSavingEmpresa"
                    class="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-700 rounded-xl transition-all cursor-pointer disabled:opacity-50">
                    Cancelar
                </button>
                <button type="submit"
                    :disabled="isSavingEmpresa"
                    :class="isSavingEmpresa ? 'opacity-60 cursor-not-allowed' : ''"
                    class="px-5 py-2 text-xs font-bold text-white bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 shadow-md shadow-brand-500/25 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer active:scale-98">
                    <span x-show="isSavingEmpresa" class="inline-block animate-spin w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full"></span>
                    <i data-lucide="check-circle" x-show="!isSavingEmpresa" class="w-4 h-4"></i>
                    <span x-text="isSavingEmpresa ? 'Guardando...' : 'Guardar Datos'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
