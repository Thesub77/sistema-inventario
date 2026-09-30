{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Punto de Venta y Facturación (POS)
    Archivo: resources/views/ventas/ventaView.blade.php
    Propósito: Catálogo amplio y despejado de productos con panel flotante (drawer)
               para revisar la lista de productos y emitir la factura sin abarrotar la vista.
    Controlador asociado: App\Http\Controllers\VentaController
    Modelo: App\Models\Venta
    Integración: Incluido en welcome.blade.php mediante @include('ventas.ventaView') 
    =============================================================================
--}}

<div x-show="currentTab === 'pos'" x-cloak 
    x-init="$watch('showCartDrawer', v => { if (v) $nextTick(() => { if (window.lucide) window.lucide.createIcons(); }); })"
    class="flex flex-col lg:h-[calc(100vh-116px)] lg:max-h-[calc(100vh-116px)] h-auto space-y-3.5 relative">
    
    <!-- Barra Superior de Control y Botón Sutil de Facturación / Carrito -->
    <div class="glass-panel p-3.5 rounded-2xl flex flex-wrap items-center justify-between gap-3 flex-shrink-0">
        <!-- Buscador de productos -->
        <div class="relative flex-1 min-w-[220px]">
            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
            <input type="text" x-model="posSearch" placeholder="Buscar producto por nombre o código..."
                class="w-full bg-white dark:bg-dark-900 border border-slate-300 dark:border-slate-700/80 rounded-xl pl-10 pr-4 py-2 text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-brand-500 transition-colors">
        </div>

        <!-- Filtro de Categorías -->
        <div class="w-auto">
            <select x-model="posCategoryFilter" class="bg-white dark:bg-dark-900 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-sm text-slate-800 dark:text-slate-200 transition-colors">
                <option value="">Todas las categorías</option>
                <template x-for="cat in categorias" :key="cat.categoria_id">
                    <option :value="cat.categoria_id" x-text="cat.nombre_categoria"></option>
                </template>
            </select>
        </div>

        <div class="flex items-center gap-2">
            <!-- Botón sutil y elegante de Ventas en Espera (Parked Orders - RF-16) -->
            <button type="button" @click="openVentasEsperaModal()"
                class="relative inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-bold transition-all shadow-sm cursor-pointer border"
                :class="ventasEspera.length > 0 
                    ? 'bg-amber-500/10 border-amber-500/40 text-amber-600 dark:text-amber-400 hover:bg-amber-500/20 ring-2 ring-amber-500/20' 
                    : 'bg-white dark:bg-dark-900 text-slate-700 dark:text-slate-200 border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'">
                <div class="relative">
                    <i data-lucide="pause-circle" class="w-4 h-4 text-amber-500"></i>
                    <span x-show="ventasEspera.length > 0"
                        class="absolute -top-2 -right-2 w-4 h-4 rounded-full bg-amber-500 text-white text-[10px] font-black flex items-center justify-center shadow-xs"
                        x-text="ventasEspera.length"></span>
                </div>
                <span class="hidden sm:inline">En Espera</span>
                <span class="font-mono text-xs px-1.5 py-0.5 rounded-lg font-bold"
                    :class="ventasEspera.length > 0 ? 'bg-amber-500/20 text-amber-700 dark:text-amber-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-400'"
                    x-text="ventasEspera.length"></span>
            </button>

            <!-- Botón sutil y elegante de Carrito / Factura -->
            <button type="button" @click="showCartDrawer = true; $nextTick(() => typeof lucide !== 'undefined' && lucide.createIcons())"
                class="relative inline-flex items-center gap-2.5 px-4 py-2 rounded-xl text-sm font-bold transition-all shadow-sm cursor-pointer"
                :class="cart.length > 0 
                    ? 'bg-brand-600 hover:bg-brand-500 text-white shadow-brand-500/25 ring-2 ring-brand-500/30' 
                    : 'bg-white dark:bg-dark-900 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'">
                <div class="relative">
                    <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                    <span x-show="cart.length > 0"
                        class="absolute -top-2 -right-2 w-4 h-4 rounded-full bg-emerald-500 text-white text-[10px] font-black flex items-center justify-center shadow-xs"
                        x-text="cart.length"></span>
                </div>
                <span>Factura / Carrito</span>
                <span class="font-mono text-xs px-2 py-0.5 rounded-lg"
                    :class="cart.length > 0 ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400'"
                    x-text="formatCurrency(cartTotal)"></span>
            </button>
        </div>
    </div>

    <!-- Catálogo de Productos Amplio y Despejado (Sin abarrotar) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-3.5 flex-1 min-h-0 overflow-y-auto pr-1.5 pb-16 auto-rows-max content-start">
        <template x-for="p in posFilteredProducts" :key="p.producto_id">
            <div @click="addToCart(p)"
                class="glass-panel p-3.5 rounded-2xl cursor-pointer hover:border-brand-500/60 hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-all flex flex-col justify-between group relative shadow-xs hover:shadow-md min-h-[140px]">
                <div>
                    <!-- Header de la tarjeta: Código y Badge de Stock visible arriba -->
                    <div class="flex items-center justify-between gap-1.5 mb-1.5">
                        <span class="text-[10px] font-mono text-brand-600 dark:text-brand-400 font-bold uppercase tracking-wider truncate" x-text="p.codigo_producto"></span>
                        <span class="text-[11px] px-2 py-0.5 rounded-md font-bold shadow-xs whitespace-nowrap"
                            :class="p.existencia_bodega > 0 
                                ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60' 
                                : 'bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800/60'"
                            x-text="'Stock: ' + p.existencia_bodega">
                        </span>
                    </div>
                    <h5 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100 line-clamp-2 group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors" x-text="p.nombre_producto"></h5>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate" x-text="p.categoria ? p.categoria.nombre_categoria : ''"></p>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-200 dark:border-slate-800/80 flex items-center justify-between">
                    <span class="font-display font-bold text-sm sm:text-base text-emerald-600 dark:text-emerald-400" x-text="formatCurrency(p.precio_venta)"></span>
                    <button type="button" class="w-6 h-6 rounded-lg bg-brand-50 dark:bg-brand-500/10 text-brand-600 dark:text-brand-400 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity" title="Agregar al carrito">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </div>
        </template>
    </div>

    <!-- Barra flotante sutil al fondo para acceso rápido al carrito cuando hay productos -->
    <div x-show="cart.length > 0"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        class="absolute bottom-4 left-1/2 -translate-x-1/2 z-30">
        <button type="button" @click="showCartDrawer = true; $nextTick(() => typeof lucide !== 'undefined' && lucide.createIcons())"
            class="flex items-center gap-3 px-5 py-2.5 rounded-full bg-slate-900 dark:bg-slate-800 text-white shadow-xl shadow-slate-900/30 border border-slate-700 hover:scale-102 active:scale-98 transition-all cursor-pointer">
            <span class="w-6 h-6 rounded-full bg-emerald-500 text-white text-xs font-black flex items-center justify-center" x-text="cart.length"></span>
            <div class="text-left text-xs leading-tight">
                <span class="block text-slate-400 text-[10px] font-medium">Factura en curso</span>
                <span class="font-bold text-white font-mono text-xs" x-text="formatCurrency(cartTotal)"></span>
            </div>
            <span class="pl-2 border-l border-slate-700 text-xs font-bold text-brand-400 hover:text-brand-300 flex items-center gap-1">
                <span>Ver lista y Cobrar</span>
                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </span>
        </button>
    </div>

    <!-- Panel Flotante / Drawer Lateral de Facturación y Cobro -->
    <div x-show="showCartDrawer" x-cloak class="fixed inset-0 z-50 overflow-hidden" role="dialog" aria-modal="true">
        <!-- Backdrop translúcido -->
        <div x-show="showCartDrawer"
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="showCartDrawer = false"
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>

        <!-- Slide-over panel container -->
        <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
            <div x-show="showCartDrawer"
                x-transition:enter="transform transition ease-in-out duration-300"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-300"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="w-screen max-w-md sm:max-w-xl bg-white dark:bg-dark-900 shadow-2xl border-l border-slate-200 dark:border-slate-800 flex flex-col justify-between h-full">

                <!-- Drawer Header -->
                <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between flex-shrink-0">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-brand-500/10 text-brand-600 dark:text-brand-400 flex items-center justify-center">
                            <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="font-display font-bold text-sm text-slate-900 dark:text-white flex items-center gap-1.5">
                                <span>Factura y Cobro</span>
                                <span class="text-[11px] px-2 py-0.5 rounded-full bg-brand-500/15 text-brand-600 dark:text-brand-400 font-bold"
                                    x-show="cart.length > 0" x-text="cart.length + ' prod.'"></span>
                            </h3>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="clearCart()" class="text-xs text-rose-500 hover:text-rose-600 font-semibold px-2 py-1 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-500/10 transition-colors" x-show="cart.length > 0">Vaciar</button>
                        <button type="button" @click="showCartDrawer = false" class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer" title="Cerrar panel">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <!-- Drawer Body: Formulario Compacto & Lista Detallada de Productos -->
                <div class="flex-1 min-h-0 overflow-y-auto p-3 sm:p-4 space-y-3">
                    <!-- Banner de Orden en Espera activa -->
                    <div x-show="resumedVentaEsperaId" class="p-2 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-between text-xs text-amber-700 dark:text-amber-300">
                        <div class="flex items-center gap-1.5 font-bold">
                            <i data-lucide="pause-circle" class="w-4 h-4 text-amber-500"></i>
                            <span>Orden en espera activa</span>
                        </div>
                        <span class="text-[10px] text-slate-500 dark:text-slate-400">Se completará al facturar</span>
                    </div>

                    <!-- Cliente y Método de Pago en 2 columnas compactas -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <!-- Cliente Selector -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">Cliente</label>
                            <select x-model.number="posSale.id_cliente" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-brand-500 transition-colors">
                                <template x-for="c in clientes" :key="c.cliente_id">
                                    <option :value="c.cliente_id" x-text="c.nombre_apellido_cliente + ' (' + (c.codigo_cliente || 'Sin código') + ')'"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Método de Pago -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">Método de Pago</label>
                            <div class="grid grid-cols-3 gap-1">
                                <button type="button" @click="posSale.metodo_pago = 'Efectivo'"
                                    :class="posSale.metodo_pago === 'Efectivo' 
                                        ? 'bg-brand-600 text-white font-bold border-brand-500 shadow-xs' 
                                        : 'bg-slate-100 dark:bg-dark-950 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800 hover:bg-slate-200/60 dark:hover:bg-slate-800/80'"
                                    class="py-1 px-1 text-[11px] font-bold rounded-lg border transition-all text-center flex items-center justify-center gap-1 cursor-pointer">
                                    <i data-lucide="banknote" class="w-3 h-3"></i>
                                    <span>Efec.</span>
                                </button>
                                <button type="button" @click="posSale.metodo_pago = 'Tarjeta'"
                                    :class="posSale.metodo_pago === 'Tarjeta' 
                                        ? 'bg-brand-600 text-white font-bold border-brand-500 shadow-xs' 
                                        : 'bg-slate-100 dark:bg-dark-950 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800 hover:bg-slate-200/60 dark:hover:bg-slate-800/80'"
                                    class="py-1 px-1 text-[11px] font-bold rounded-lg border transition-all text-center flex items-center justify-center gap-1 cursor-pointer">
                                    <i data-lucide="credit-card" class="w-3 h-3"></i>
                                    <span>Tarj.</span>
                                </button>
                                <button type="button" @click="posSale.metodo_pago = 'Transferencia'"
                                    :class="posSale.metodo_pago === 'Transferencia' 
                                        ? 'bg-brand-600 text-white font-bold border-brand-500 shadow-xs' 
                                        : 'bg-slate-100 dark:bg-dark-950 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800 hover:bg-slate-200/60 dark:hover:bg-slate-800/80'"
                                    class="py-1 px-1 text-[11px] font-bold rounded-lg border transition-all text-center flex items-center justify-center gap-1 cursor-pointer">
                                    <i data-lucide="arrow-left-right" class="w-3 h-3"></i>
                                    <span>Transf.</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Identificador de Transferencia o Tarjeta -->
                    <div x-show="posSale.metodo_pago === 'Transferencia' || posSale.metodo_pago === 'Tarjeta'"
                        class="space-y-1 p-2 rounded-lg bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-slate-700/80 shadow-xs">
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-200">
                            <span x-text="posSale.metodo_pago === 'Transferencia' ? 'N° Referencia / Comprobante' : 'N° Voucher / Autorización Tarjeta'"></span>
                            <span class="text-rose-500 font-bold ml-0.5" x-show="posSale.metodo_pago === 'Transferencia' || posSale.metodo_pago === 'Tarjeta'">*</span>
                        </label>
                        <input type="text"
                            x-model="posSale.referencia_transferencia"
                            :placeholder="posSale.metodo_pago === 'Transferencia' ? 'Ej. TRF-BAC-987654321' : 'Ej. VOUCHER-004521'"
                            maxlength="64"
                            class="w-full bg-white dark:bg-dark-900 border border-slate-300 dark:border-slate-700 focus:border-brand-500 rounded-md px-2 py-1 text-xs text-slate-900 dark:text-white font-mono">
                    </div>

                    <!-- Lista de Productos en la Facturación -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                <span>Productos a Facturar</span>
                                <span class="text-slate-400 font-normal" x-show="cart.length > 0" x-text="'(' + cart.length + ' ítems)'"></span>
                            </label>
                        </div>

                        <div class="space-y-1.5">
                            <template x-for="(item, index) in cart" :key="item.id_producto">
                                <div class="p-2 sm:p-2.5 bg-slate-50 dark:bg-dark-950 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center justify-between gap-2 shadow-xs">
                                    <div class="overflow-hidden min-w-0 flex-1">
                                        <p class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate" x-text="item.nombre_producto"></p>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-[11px] text-slate-500 dark:text-slate-400 font-mono" x-text="formatCurrency(item.precio_unitario) + ' c/u'"></span>
                                            <span class="text-[10px] px-1.5 py-0.2 rounded font-bold"
                                                :class="item.cantidad >= (getProductStock(item.id_producto) || item.existencia_bodega || 0)
                                                    ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30'
                                                    : 'bg-slate-200/70 dark:bg-dark-900 text-slate-600 dark:text-slate-300 border border-slate-300/60 dark:border-slate-700/60'"
                                                x-text="'Stock: ' + (getProductStock(item.id_producto) ?? item.existencia_bodega ?? 0)">
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <div class="flex items-center bg-white dark:bg-dark-900 border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden shadow-xs">
                                            <button type="button" @click="decreaseCartQty(index)" class="px-2 py-0.5 text-xs text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 font-bold transition-colors cursor-pointer">-</button>
                                            <span class="px-1.5 py-0.5 text-xs font-bold text-slate-800 dark:text-white min-w-[20px] text-center font-mono" x-text="item.cantidad"></span>
                                            <button type="button" @click="increaseCartQty(index)" class="px-2 py-0.5 text-xs text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 font-bold transition-colors cursor-pointer">+</button>
                                        </div>
                                        <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 min-w-[65px] text-right font-mono" x-text="formatCurrency(item.subtotal_venta_detalle)"></span>
                                        <button type="button" @click="removeFromCart(index)" class="text-slate-400 hover:text-rose-500 transition-colors p-1 cursor-pointer" title="Eliminar producto">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <div x-show="cart.length === 0" class="py-8 flex flex-col items-center justify-center text-center text-slate-400 dark:text-slate-500 text-xs">
                                <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-dark-950 border border-slate-200 dark:border-slate-800 flex items-center justify-center mb-2 text-slate-400 shadow-inner">
                                    <i data-lucide="shopping-cart" class="w-6 h-6 opacity-60"></i>
                                </div>
                                <span class="font-bold text-slate-700 dark:text-slate-200 text-sm">El carrito está vacío</span>
                                <span class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Haz clic en los productos del catálogo para agregarlos</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Drawer Footer: Totales, Descuento, Efectivo y Cobro (Diseño idéntico a la captura pero optimizado en altura) -->
                <div class="p-3 sm:p-4 border-t border-slate-200 dark:border-slate-800 space-y-2 bg-white dark:bg-dark-900 flex-shrink-0">
                    <!-- Subtotal y Descuento en una sola fila compacta -->
                    <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                        <div class="flex items-center gap-1.5">
                            <span class="font-medium text-slate-500 dark:text-slate-400">Subtotal:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200 font-mono text-xs sm:text-sm" x-text="formatCurrency(cartSubtotal)"></span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="font-medium text-slate-500 dark:text-slate-400">Descuento:</span>
                            
                            <!-- Toggle entre Monto Fijo (C$) y Porcentaje (%) -->
                            <div class="inline-flex rounded-md p-0.5 bg-slate-100 dark:bg-dark-950 border border-slate-200 dark:border-slate-800 text-[10px] font-bold">
                                <button type="button" @click="setDiscountType('monto')"
                                    :class="posSale.tipo_descuento !== 'porcentaje' ? 'bg-white dark:bg-slate-800 text-brand-600 dark:text-brand-400 shadow-2xs' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200'"
                                    class="px-1.5 py-0.5 rounded transition-all cursor-pointer">C$</button>
                                <button type="button" @click="setDiscountType('porcentaje')"
                                    :class="posSale.tipo_descuento === 'porcentaje' ? 'bg-white dark:bg-slate-800 text-brand-600 dark:text-brand-400 shadow-2xs' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200'"
                                    class="px-1.5 py-0.5 rounded transition-all cursor-pointer">%</button>
                            </div>

                            <!-- Input en modo Porcentaje -->
                            <template x-if="posSale.tipo_descuento === 'porcentaje'">
                                <div class="flex items-center gap-1">
                                    <div class="relative">
                                        <input type="number" x-model.number="posSale.descuento_porcentaje" @input="updatePercentDiscount()" min="0" max="100" placeholder="0"
                                            class="w-14 bg-white dark:bg-dark-950 border border-slate-300 dark:border-slate-700 rounded-lg px-1.5 py-0.5 pr-4 text-center text-xs font-bold font-mono text-slate-900 dark:text-white focus:border-brand-500 focus:outline-none">
                                        <span class="absolute right-1 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400 pointer-events-none">%</span>
                                    </div>
                                    <span x-show="posSale.descuento_venta > 0" class="text-[11px] font-mono font-bold text-rose-500 dark:text-rose-400"
                                        x-text="'-' + formatCurrency(posSale.descuento_venta)"></span>
                                </div>
                            </template>

                            <!-- Input en modo Monto Fijo -->
                            <template x-if="posSale.tipo_descuento !== 'porcentaje'">
                                <div class="flex items-center gap-1">
                                    <span class="font-bold text-xs text-slate-600 dark:text-slate-400">C$</span>
                                    <input type="number" x-model.number="posSale.descuento_venta" min="0" :max="cartSubtotal" placeholder="0"
                                        class="w-16 bg-white dark:bg-dark-950 border border-slate-300 dark:border-slate-700 rounded-lg px-2 py-0.5 text-center text-xs font-bold font-mono text-slate-900 dark:text-white focus:border-brand-500 focus:outline-none">
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Botones rápidos de porcentaje cuando está en modo % -->
                    <div x-show="posSale.tipo_descuento === 'porcentaje'" class="flex items-center justify-end gap-1 -mt-1 pt-0.5">
                        <span class="text-[10px] text-slate-400">Rápido:</span>
                        <template x-for="p in [5, 10, 15, 20]" :key="p">
                            <button type="button" @click="applyQuickPercent(p)"
                                :class="posSale.descuento_porcentaje === p 
                                    ? 'bg-brand-600 text-white font-bold' 
                                    : 'bg-slate-100 dark:bg-dark-950 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'"
                                class="px-1.5 py-0.5 text-[10px] rounded font-mono transition-colors cursor-pointer"
                                x-text="p + '%'"></button>
                        </template>
                        <button type="button" @click="applyQuickPercent(0)" class="px-1.5 py-0.5 text-[10px] rounded text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors cursor-pointer" x-show="posSale.descuento_porcentaje > 0">Borrar</button>
                    </div>

                    <!-- Total a Pagar -->
                    <div class="flex items-center justify-between">
                        <span class="font-display font-bold text-sm sm:text-base text-slate-900 dark:text-white">Total a Pagar:</span>
                        <span class="text-emerald-600 dark:text-emerald-400 font-display text-2xl font-black tracking-tight" x-text="formatCurrency(cartTotal)"></span>
                    </div>

                    <!-- Tarjeta de Efectivo Recibido (Cliente entrega) - Compacta para ganar espacio -->
                    <div x-show="posSale.metodo_pago === 'Efectivo' && cart.length > 0"
                        class="p-2.5 rounded-xl bg-white dark:bg-dark-950 border border-slate-200 dark:border-slate-800 space-y-1.5 shadow-xs">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold text-slate-700 dark:text-slate-200 flex items-center gap-1.5">
                                <i data-lucide="wallet" class="w-3.5 h-3.5 text-emerald-500"></i>
                                <span>Efectivo Recibido (Cliente entrega):</span>
                            </label>
                            <button type="button" @click="setExactCash()"
                                class="px-2.5 py-0.5 text-[11px] font-bold text-indigo-600 bg-indigo-50/80 hover:bg-indigo-100 border border-indigo-200/80 dark:text-indigo-400 dark:bg-indigo-950/40 dark:border-indigo-800/80 rounded-lg transition-all cursor-pointer flex items-center gap-1">
                                <i data-lucide="check" class="w-3 h-3"></i>
                                <span>Paga Exacto</span>
                            </button>
                        </div>

                        <!-- Input del monto -->
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500 dark:text-slate-400 font-bold text-sm pointer-events-none font-mono">C$</span>
                            <input type="number" step="any" min="0" required
                                x-model.number="posSale.monto_recibido"
                                :placeholder="cartTotal ? cartTotal.toFixed(2) : '0.00'"
                                :class="{'border-amber-400 dark:border-amber-500 ring-1 ring-amber-400/40': !posSale.monto_recibido || Number(posSale.monto_recibido) < cartTotal}"
                                class="w-full bg-white dark:bg-dark-900 border border-slate-200 dark:border-slate-700 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 rounded-lg pl-9 pr-3 py-1.5 text-base font-bold text-slate-700 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 transition-colors font-mono">
                        </div>

                        <!-- Indicador de requisito previo para emitir factura -->
                        <p x-show="!posSale.monto_recibido || Number(posSale.monto_recibido) < cartTotal"
                            class="text-[10px] text-amber-500 dark:text-amber-400 font-medium flex items-center gap-1">
                            <i data-lucide="info" class="w-3 h-3 flex-shrink-0"></i>
                            <span>Ingrese el efectivo entregado para habilitar la emisión de factura.</span>
                        </p>

                        <!-- Cambio a Entregar (Mostrado en verde cuando el cliente da más efectivo) -->
                        <div x-show="posSale.monto_recibido && cashChange > 0"
                            class="pt-1 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-emerald-600 dark:text-emerald-400">
                            <span class="text-[11px] uppercase tracking-wider font-extrabold flex items-center gap-1">
                                <i data-lucide="arrow-down-left" class="w-3.5 h-3.5 text-emerald-600"></i>
                                <span>Cambio a entregar:</span>
                            </span>
                            <span class="font-display font-black text-lg font-mono tracking-tight"
                                x-text="formatCurrency(cashChange)"></span>
                        </div>

                        <!-- Alerta Falta por pagar -->
                        <div x-show="posSale.monto_recibido && cashShortage > 0"
                            class="pt-1 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-rose-600 dark:text-rose-400">
                            <span class="text-[11px] uppercase tracking-wider font-extrabold flex items-center gap-1">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5 text-rose-600"></i>
                                <span>Falta por pagar:</span>
                            </span>
                            <span class="font-display font-bold text-sm font-mono"
                                x-text="formatCurrency(cashShortage)"></span>
                        </div>
                    </div>

                    <!-- Botón Pausar Venta (Poner en Espera - RF-16) -->
                    <button type="button" @click="parkCurrentSale()"
                        :disabled="cart.length === 0 || loading"
                        class="w-full py-2 bg-amber-50 dark:bg-amber-950/30 hover:bg-amber-100 dark:hover:bg-amber-900/50 text-amber-700 dark:text-amber-400 border border-amber-300 dark:border-amber-700/60 font-bold rounded-xl flex items-center justify-center gap-2 text-xs transition-all disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer shadow-xs">
                        <i data-lucide="pause-circle" class="w-4 h-4 text-amber-500"></i>
                        <span x-text="resumedVentaEsperaId ? 'Guardar Cambios en Espera' : 'Pausar Venta (Poner en Espera)'"></span>
                    </button>

                    <!-- Botón Emitir Factura y Cobrar (Bloqueado hasta ingresar el efectivo recibido) -->
                    <button type="button" @click="processSale()"
                        :disabled="cart.length === 0 || loading || (posSale.metodo_pago === 'Efectivo' && (!posSale.monto_recibido || Number(posSale.monto_recibido) < cartTotal))"
                        :class="(posSale.metodo_pago === 'Efectivo' && (!posSale.monto_recibido || Number(posSale.monto_recibido) < cartTotal)) ? 'bg-slate-700/80 text-slate-300 shadow-none' : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-md shadow-emerald-600/20'"
                        class="w-full py-2.5 active:scale-[0.99] disabled:opacity-50 disabled:cursor-not-allowed font-bold rounded-xl flex items-center justify-center gap-2 text-sm transition-all cursor-pointer">
                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                        <span x-text="posSale.metodo_pago === 'Efectivo' && (!posSale.monto_recibido || Number(posSale.monto_recibido) < cartTotal) ? 'Ingrese el Efectivo Recibido' : 'Emitir Factura y Cobrar'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Listado de Ventas en Espera / Cuentas Pendientes (RF-16) -->
    <div x-show="showVentasEsperaModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div x-show="showVentasEsperaModal"
            x-transition:enter="transition-opacity ease-linear duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="showVentasEsperaModal = false"
            class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs"></div>

        <div class="min-h-full flex items-center justify-center p-4">
            <div x-show="showVentasEsperaModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="w-full max-w-2xl bg-white dark:bg-dark-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden relative flex flex-col max-h-[85vh]">

                <!-- Modal Header -->
                <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-dark-950/50 flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-500 flex items-center justify-center">
                            <i data-lucide="pause-circle" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="font-display font-bold text-base text-slate-900 dark:text-white flex items-center gap-2">
                                <span>Ventas en Espera</span>
                                <span class="px-2 py-0.5 text-xs rounded-full bg-amber-500/15 text-amber-600 dark:text-amber-400 font-mono font-bold"
                                    x-text="ventasEspera.length"></span>
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Órdenes pausadas temporalmente en el mostrador</p>
                        </div>
                    </div>
                    <button type="button" @click="showVentasEsperaModal = false"
                        class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <!-- Modal Body: List of parked orders -->
                <div class="p-5 flex-1 overflow-y-auto space-y-3">
                    <template x-for="item in ventasEspera" :key="item.venta_espera_id">
                        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-dark-950/60 hover:border-amber-500/40 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
                            <div class="space-y-1 flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-0.5 rounded-lg bg-amber-500/15 text-amber-700 dark:text-amber-300 font-bold text-xs font-mono"
                                        x-text="item.identificador_cuenta"></span>
                                    <span x-show="item.venta_espera_id === resumedVentaEsperaId"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 font-bold text-[10px]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span>En Carrito</span>
                                    </span>
                                    <span class="text-xs text-slate-400 font-medium"
                                        x-text="item.fecha_creacion ? formatTime(item.fecha_creacion) : ''"></span>
                                </div>

                                <div class="text-xs text-slate-600 dark:text-slate-300 flex items-center gap-2 pt-0.5">
                                    <span class="font-medium" x-show="item.cliente" x-text="item.cliente ? item.cliente.nombre_apellido_cliente : ''"></span>
                                    <span class="text-slate-400" x-show="item.cliente && item.cliente.codigo_cliente" x-text="'(' + item.cliente.codigo_cliente + ')'"></span>
                                    <span class="text-slate-400 dark:text-slate-500">•</span>
                                    <span class="text-slate-500 dark:text-slate-400" x-text="(item.detalles ? item.detalles.length : 0) + ' productos'"></span>
                                </div>

                                <template x-if="item.observaciones">
                                    <p class="text-[11px] text-slate-400 italic pt-0.5" x-text="item.observaciones"></p>
                                </template>
                            </div>

                            <div class="flex items-center justify-between sm:justify-end gap-3 flex-shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-200 dark:border-slate-800">
                                <div class="text-right">
                                    <span class="block text-[10px] text-slate-400 uppercase font-semibold">Total</span>
                                    <span class="font-mono font-bold text-base text-emerald-600 dark:text-emerald-400" x-text="formatCurrency(item.total)"></span>
                                </div>

                                <div class="flex items-center gap-1.5">
                                    <!-- Botón Descartar -->
                                    <button type="button" @click="discardVentaEspera(item)"
                                        class="p-2 text-rose-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 rounded-xl transition-colors cursor-pointer"
                                        title="Descartar orden">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>

                                    <!-- Botón Reanudar -->
                                    <button type="button" @click="resumeVentaEspera(item)"
                                        :class="item.venta_espera_id === resumedVentaEsperaId
                                            ? 'bg-emerald-600 hover:bg-emerald-500 text-white'
                                            : 'bg-amber-500 hover:bg-amber-600 text-white'"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl font-bold text-xs shadow-sm hover:shadow transition-all cursor-pointer">
                                        <i data-lucide="play" class="w-3.5 h-3.5 fill-current"></i>
                                        <span x-text="item.venta_espera_id === resumedVentaEsperaId ? 'En Carrito' : 'Reanudar'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Estado Vacío -->
                    <div x-show="ventasEspera.length === 0" class="py-12 flex flex-col items-center justify-center text-center">
                        <div class="w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center mb-3 text-amber-500">
                            <i data-lucide="pause-circle" class="w-7 h-7"></i>
                        </div>
                        <h4 class="font-bold text-slate-800 dark:text-slate-200 text-sm">No hay ventas en espera</h4>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 max-w-sm">
                            Cuando tengas una orden abierta en el carrito, pulsa el botón "Pausar Venta" para dejarla guardada y atender a otro cliente.
                        </p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-5 py-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-dark-950/50 flex items-center justify-between flex-shrink-0 text-xs text-slate-500 dark:text-slate-400">
                    <span x-text="ventasEspera.length + ' orden(es) en espera'"></span>
                    <button type="button" @click="showVentasEsperaModal = false"
                        class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 font-medium transition-colors cursor-pointer">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>