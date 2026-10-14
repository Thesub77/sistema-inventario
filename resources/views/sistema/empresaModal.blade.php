{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Centro de Configuración General del Sistema
    Archivo: resources/views/sistema/empresaModal.blade.php
    Propósito: Modal con navegación por pestañas (Vertical Tabs) para la gestión
               integral de la empresa, catálogo, punto de venta y preferencias del sistema.
    Regla UX: No se cierra al hacer clic fuera; solo mediante Cancelar, X o Guardar.
    =============================================================================
--}}

<div x-show="showEmpresaModal" x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-black/75 backdrop-blur-sm overflow-y-auto">

    <!-- Contenedor Principal (Tamaño estable y fijo para evitar saltos entre pestañas) -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-slate-800 rounded-3xl w-full max-w-4xl shadow-2xl overflow-hidden flex flex-col h-[660px] max-h-[92vh] my-auto transition-all">

        <!-- 1. Cabecera Fija -->
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between flex-shrink-0 bg-white dark:bg-dark-900 z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-brand-500/10 text-brand-600 dark:text-brand-400 flex items-center justify-center border border-brand-500/20 shadow-xs">
                    <i data-lucide="settings" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-white">
                        Configuración del Sistema
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Identidad del negocio, catálogo, punto de venta y preferencias</p>
                </div>
            </div>
            <button type="button" @click="showEmpresaModal = false; if (typeof closeEmpresaModal === 'function') closeEmpresaModal();"
                class="p-2 text-slate-400 hover:text-slate-700 dark:hover:text-white rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                title="Cerrar ventana">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- 2. Cuerpo Principal: Menú Lateral + Contenido de Pestañas -->
        <form @submit.prevent="saveEmpresa()" id="empresaConfigForm" class="flex-1 flex flex-col overflow-hidden">
            <div class="flex-1 flex flex-col md:flex-row overflow-hidden">

                <!-- Navegación Lateral de Pestañas -->
                <div class="w-full md:w-60 bg-slate-50 dark:bg-dark-950/60 border-b md:border-b-0 md:border-r border-slate-200 dark:border-slate-800/80 p-3 sm:p-4 flex md:flex-col gap-1.5 overflow-x-auto md:overflow-y-auto flex-shrink-0">
                    
                    <!-- Pestaña 1: Negocio -->
                    <button type="button" @click="configTab = 'negocio'"
                        class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-bold transition-all text-left whitespace-nowrap cursor-pointer w-full"
                        :class="configTab === 'negocio' 
                            ? 'bg-brand-600 text-white shadow-sm shadow-brand-500/20' 
                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/70 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-white'">
                        <i data-lucide="store" class="w-4 h-4 flex-shrink-0"></i>
                        <span>Datos del Negocio</span>
                    </button>

                    <!-- Pestaña 2: Catálogo y Productos -->
                    <button type="button" @click="configTab = 'productos'"
                        class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-bold transition-all text-left whitespace-nowrap cursor-pointer w-full"
                        :class="configTab === 'productos' 
                            ? 'bg-brand-600 text-white shadow-sm shadow-brand-500/20' 
                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/70 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-white'">
                        <i data-lucide="package" class="w-4 h-4 flex-shrink-0"></i>
                        <span>Catálogo & Productos</span>
                    </button>

                    <!-- Pestaña 3: Sistema -->
                    <button type="button" @click="configTab = 'sistema'"
                        class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-bold transition-all text-left whitespace-nowrap cursor-pointer w-full"
                        :class="configTab === 'sistema' 
                            ? 'bg-brand-600 text-white shadow-sm shadow-brand-500/20' 
                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/70 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-white'">
                        <i data-lucide="sliders" class="w-4 h-4 flex-shrink-0"></i>
                        <span>Sistema</span>
                    </button>
                </div>

                <!-- Contenido de las Pestañas -->
                <div class="flex-1 p-5 sm:p-6 overflow-y-auto">

                    <!-- ========================================== -->
                    <!-- PESTAÑA 1: DATOS DEL NEGOCIO              -->
                    <!-- ========================================== -->
                    <div x-show="configTab === 'negocio'" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            
                            <!-- Nombre Comercial -->
                            <div class="space-y-1 sm:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                    Nombre Comercial <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" x-model="empresaForm.nombre_comercial" required maxlength="128"
                                    placeholder="Ej. Comercial Don Diego"
                                    class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:border-brand-500 font-medium">
                            </div>

                            <!-- Razón Social -->
                            <div class="space-y-1">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                    Razón Social
                                </label>
                                <input type="text" x-model="empresaForm.razon_social" maxlength="128"
                                    placeholder="Ej. Distribuidora del Norte S.A."
                                    class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:border-brand-500">
                            </div>

                            <!-- RUC / Cédula -->
                            <div class="space-y-1">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                    RUC / Cédula Fiscal
                                </label>
                                <input type="text"
                                    x-model="empresaForm.numero_ruc"
                                    @input="empresaForm.numero_ruc = $event.target.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase()"
                                    maxlength="14"
                                    placeholder="Ej. J0310000000001 (opcional)"
                                    class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-900 dark:text-slate-100 font-mono focus:outline-none focus:border-brand-500">
                            </div>

                            <!-- Teléfono -->
                            <div class="space-y-1">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                    Teléfono de Contacto <span class="text-rose-500">*</span>
                                </label>
                                <input type="text"
                                    x-model="empresaForm.telefono_contacto"
                                    @input="empresaForm.telefono_contacto = formatTelefono($event.target.value)"
                                    required
                                    maxlength="9"
                                    placeholder="0000-0000"
                                    class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-900 dark:text-slate-100 font-mono focus:outline-none focus:border-brand-500">
                            </div>

                            <!-- Correo -->
                            <div class="space-y-1">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                    Correo Electrónico
                                </label>
                                <input type="email" x-model="empresaForm.correo_contacto" maxlength="128"
                                    placeholder="contacto@comercial.com"
                                    class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:border-brand-500">
                            </div>

                            <!-- Dirección Física -->
                            <div class="space-y-1 sm:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                    Dirección del Negocio <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" x-model="empresaForm.direccion_fisica" required maxlength="255"
                                    placeholder="Ej. De la rotonda 2c al sur, Managua"
                                    class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:border-brand-500">
                            </div>

                            <!-- Régimen Tributario -->
                            <div class="space-y-1">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                    Régimen Tributario
                                </label>
                                <select x-model="empresaForm.regimen_tributario" disabled
                                    class="w-full bg-slate-100 dark:bg-dark-950/70 border border-slate-300 dark:border-slate-800 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-500 dark:text-slate-400 cursor-not-allowed opacity-80 focus:outline-none">
                                    <option value="Cuota Fija">Cuota Fija</option>
                                </select>
                            </div>

                            <!-- Moneda del Sistema -->
                            <div class="space-y-1">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                    Moneda del Sistema
                                </label>
                                <select x-model="empresaForm.moneda_simbolo"
                                    class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-900 dark:text-slate-100 font-bold focus:outline-none focus:border-brand-500">
                                    <option value="C$">Córdobas (C$)</option>
                                    <option value="$">Dólares ($)</option>
                                </select>
                            </div>

                            <!-- Mensaje Pie de Ticket -->
                            <div class="space-y-1 sm:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                    Mensaje al pie del comprobante
                                </label>
                                <input type="text" x-model="empresaForm.mensaje_pie_ticket" maxlength="255"
                                    placeholder="Ej. ¡Gracias por su compra! Revise su mercadería."
                                    class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:border-brand-500">
                            </div>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- PESTAÑA 2: CATÁLOGO Y PRODUCTOS           -->
                    <!-- ========================================== -->
                    <div x-show="configTab === 'productos'" class="space-y-5">
                        
                        <!-- 1. Códigos de Producto Estándar -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                Códigos de Producto Estándar
                            </label>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- Opción 1: Manual -->
                                <div @click="empresaForm.configuracion.productos.modo_codigo = 'manual'"
                                    class="p-3 rounded-2xl border transition-all cursor-pointer flex items-start gap-3"
                                    :class="empresaForm.configuracion.productos.modo_codigo === 'manual'
                                        ? 'bg-brand-500/10 border-brand-500 text-brand-600 dark:text-brand-400 shadow-xs'
                                        : 'bg-slate-50 dark:bg-dark-950 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:border-slate-300'">
                                    <input type="radio" value="manual" x-model="empresaForm.configuracion.productos.modo_codigo" class="mt-0.5 text-brand-600 focus:ring-brand-500">
                                    <div>
                                        <div class="font-bold text-xs sm:text-sm">Manual / Código de Barras</div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">El campo queda libre para escanear con lector óptico o escribir códigos propios.</div>
                                    </div>
                                </div>

                                <!-- Opción 2: Automático -->
                                <div @click="empresaForm.configuracion.productos.modo_codigo = 'automatico'"
                                    class="p-3 rounded-2xl border transition-all cursor-pointer flex items-start gap-3"
                                    :class="empresaForm.configuracion.productos.modo_codigo === 'automatico'
                                        ? 'bg-brand-500/10 border-brand-500 text-brand-600 dark:text-brand-400 shadow-xs'
                                        : 'bg-slate-50 dark:bg-dark-950 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:border-slate-300'">
                                    <input type="radio" value="automatico" x-model="empresaForm.configuracion.productos.modo_codigo" class="mt-0.5 text-brand-600 focus:ring-brand-500">
                                    <div>
                                        <div class="font-bold text-xs sm:text-sm">Automático correlativo</div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Autogenera un código consecutivo con el prefijo elegido.</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Prefijo Estándar y Stock Mínimo -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                <div class="space-y-1">
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                        Prefijo para Productos
                                    </label>
                                    <input type="text"
                                        x-model="empresaForm.configuracion.productos.prefijo_codigo"
                                        @input="empresaForm.configuracion.productos.prefijo_codigo = $event.target.value.replace(/[^a-zA-Z0-9\-]/g, '')"
                                        :disabled="empresaForm.configuracion.productos.modo_codigo === 'manual'"
                                        maxlength="10"
                                        placeholder="PROD"
                                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-900 dark:text-slate-100 font-mono disabled:opacity-40 focus:outline-none focus:border-brand-500">
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400">Ejemplo: <code class="font-mono text-brand-600 dark:text-brand-400" x-text="((empresaForm.configuracion?.productos?.prefijo_codigo || 'PROD').replace(/-+$/, '')) + '-001'"></code></span>
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                        Stock Mínimo Predeterminado
                                    </label>
                                    <input type="number"
                                        x-model="empresaForm.configuracion.productos.stock_minimo_defecto"
                                        min="0"
                                        max="9999"
                                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:border-brand-500">
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400">Cantidad base de alerta de existencias</span>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Códigos para Productos Combo -->
                        <div class="space-y-2 pt-3 border-t border-slate-200 dark:border-slate-800">
                            <label class="block text-xs font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400">
                                Generación de Códigos para Combos
                            </label>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- Manual Combo -->
                                <div @click="empresaForm.configuracion.productos.modo_codigo_combo = 'manual'"
                                    class="p-3 rounded-2xl border transition-all cursor-pointer flex items-start gap-3"
                                    :class="empresaForm.configuracion.productos.modo_codigo_combo === 'manual'
                                        ? 'bg-purple-500/10 border-purple-500 text-purple-600 dark:text-purple-400 shadow-xs'
                                        : 'bg-slate-50 dark:bg-dark-950 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:border-slate-300'">
                                    <input type="radio" value="manual" x-model="empresaForm.configuracion.productos.modo_codigo_combo" class="mt-0.5 text-purple-600 focus:ring-purple-500">
                                    <div>
                                        <div class="font-bold text-xs sm:text-sm">Manual / Código Propio</div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">El campo queda libre para escribir códigos propios para el combo.</div>
                                    </div>
                                </div>

                                <!-- Automático Combo -->
                                <div @click="empresaForm.configuracion.productos.modo_codigo_combo = 'automatico'"
                                    class="p-3 rounded-2xl border transition-all cursor-pointer flex items-start gap-3"
                                    :class="empresaForm.configuracion.productos.modo_codigo_combo === 'automatico'
                                        ? 'bg-purple-500/10 border-purple-500 text-purple-600 dark:text-purple-400 shadow-xs'
                                        : 'bg-slate-50 dark:bg-dark-950 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:border-slate-300'">
                                    <input type="radio" value="automatico" x-model="empresaForm.configuracion.productos.modo_codigo_combo" class="mt-0.5 text-purple-600 focus:ring-purple-500">
                                    <div>
                                        <div class="font-bold text-xs sm:text-sm">Automático correlativo</div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Autogenera un código consecutivo con el prefijo de combos.</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Prefijo Combo -->
                            <div class="space-y-1 pt-1">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                    Prefijo para Combos
                                </label>
                                <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                                    <input type="text"
                                        x-model="empresaForm.configuracion.productos.prefijo_codigo_combo"
                                        @input="empresaForm.configuracion.productos.prefijo_codigo_combo = $event.target.value.replace(/[^a-zA-Z0-9\-]/g, '')"
                                        :disabled="empresaForm.configuracion.productos.modo_codigo_combo === 'manual'"
                                        maxlength="10"
                                        placeholder="COMBO"
                                        class="w-full sm:w-56 bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-900 dark:text-slate-100 font-mono disabled:opacity-40 focus:outline-none focus:border-purple-500">
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400">Ejemplo: <code class="font-mono text-purple-600 dark:text-purple-400" x-text="((empresaForm.configuracion?.productos?.prefijo_codigo_combo || 'COMBO').replace(/-+$/, '')) + '-001'"></code></span>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Códigos de Categoría con Prefijo Opcional -->
                        <div class="pt-3 border-t border-slate-200 dark:border-slate-800 space-y-3">
                            <label class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-50 dark:bg-dark-950/60 border border-slate-200 dark:border-slate-800 cursor-pointer">
                                <div>
                                    <div class="font-bold text-xs sm:text-sm text-slate-800 dark:text-slate-200">Autogenerar prefijo en categorías</div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400">Permite al negocio definir un prefijo predeterminado para nuevas categorías (por defecto CAT).</div>
                                </div>
                                <input type="checkbox"
                                    x-model="empresaForm.configuracion.productos.categoria_sugerir_prefijo"
                                    class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 h-4 w-4">
                            </label>

                            <!-- Mini sección desplegable solo si está activo el check -->
                            <div x-show="empresaForm.configuracion.productos.categoria_sugerir_prefijo" x-cloak
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 -translate-y-2"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                class="p-3.5 rounded-2xl bg-brand-500/5 border border-brand-500/20 space-y-2">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                    Prefijo para Categorías
                                </label>
                                <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                                    <input type="text"
                                        x-model="empresaForm.configuracion.productos.prefijo_categoria"
                                        @input="empresaForm.configuracion.productos.prefijo_categoria = $event.target.value.replace(/[^a-zA-Z0-9\-]/g, '')"
                                        maxlength="10"
                                        placeholder="CAT"
                                        class="w-full sm:w-56 bg-white dark:bg-dark-950 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-900 dark:text-slate-100 font-mono focus:outline-none focus:border-brand-500">
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400">Ejemplo: <code class="font-mono text-brand-600 dark:text-brand-400" x-text="((empresaForm.configuracion?.productos?.prefijo_categoria || 'CAT').replace(/-+$/, '')) + '-01'"></code></span>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- ========================================== -->
                    <!-- PESTAÑA 3: SISTEMA                         -->
                    <!-- ========================================== -->
                    <div x-show="configTab === 'sistema'" class="space-y-4">
                        
                        <!-- Toggle Módulo Bitácora en el Sidebar -->
                        <label class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-50 dark:bg-dark-950/60 border border-slate-200 dark:border-slate-800 cursor-pointer">
                            <div>
                                <div class="font-bold text-xs sm:text-sm text-slate-800 dark:text-slate-200">Mostrar módulo de Bitácora en el menú</div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400">Permite ver la pestaña de auditoría en la barra lateral izquierda</div>
                            </div>
                            <input type="checkbox"
                                x-model="empresaForm.mostrar_bitacora"
                                class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 h-4 w-4">
                        </label>

                        <!-- Nivel de Registro de Bitácora -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-dark-950/60 border border-slate-200 dark:border-slate-800 space-y-2">
                            <div class="font-bold text-xs sm:text-sm text-slate-800 dark:text-slate-200">Nivel de auditoría</div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                                    <input type="radio" value="completa" x-model="empresaForm.configuracion.sistema.nivel_bitacora" class="text-brand-600 focus:ring-brand-500">
                                    <span>Completa (Todas las acciones)</span>
                                </label>
                                <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                                    <input type="radio" value="esencial" x-model="empresaForm.configuracion.sistema.nivel_bitacora" class="text-brand-600 focus:ring-brand-500">
                                    <span>Esencial (Cajas y seguridad)</span>
                                </label>
                            </div>
                        </div>

                    </div>

                </div>
            </div>

            <!-- 3. Pie de Acciones Fijo -->
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3 bg-slate-50 dark:bg-dark-950/80 flex-shrink-0">
                <button type="button" @click="showEmpresaModal = false; if (typeof closeEmpresaModal === 'function') closeEmpresaModal();"
                    class="px-4 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-800 rounded-xl transition-colors cursor-pointer">
                    Cancelar
                </button>
                <button type="submit" :disabled="isSavingEmpresa"
                    class="px-5 py-2 text-xs font-bold text-white bg-brand-600 hover:bg-brand-500 disabled:opacity-50 rounded-xl shadow-md shadow-brand-500/20 transition-all flex items-center gap-2 cursor-pointer">
                    <i data-lucide="check" class="w-4 h-4" x-show="!isSavingEmpresa"></i>
                    <i data-lucide="loader-2" class="w-4 h-4 animate-spin" x-show="isSavingEmpresa"></i>
                    <span x-text="isSavingEmpresa ? 'Guardando...' : 'Guardar Configuración'"></span>
                </button>
            </div>
        </form>

    </div>
</div>
