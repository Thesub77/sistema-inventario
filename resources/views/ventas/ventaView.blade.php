{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Punto de Venta y Facturación
    Archivo: resources/views/ventas/ventaView.blade.php
    Propósito: Permite la visualización y gestión de productos de forma modular.
    Controlador asociado: App\Http\Controllers\VentaController
    Modelo: App\Models\Venta
    Integración: Incluido en welcome.blade.php mediante @include('ventas.ventaView') 
    =============================================================================
--}}

<div x-show="currentTab === 'pos'" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Products Catalog -->
    <div class="lg:col-span-2 space-y-4">
        <div class="glass-panel p-4 rounded-2xl flex items-center justify-between gap-4">
            <div class="relative flex-1">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input type="text" x-model="posSearch" placeholder="Buscar producto para agregar a la venta..."
                    class="w-full bg-dark-900 border border-slate-700/80 rounded-xl pl-10 pr-4 py-2 text-sm text-slate-100 placeholder-slate-400 focus:outline-none focus:border-brand-500">
            </div>
            <select x-model="posCategoryFilter" class="bg-dark-900 border border-slate-700/80 rounded-xl px-3 py-2 text-sm text-slate-200">
                <option value="">Todas las categorías</option>
                <template x-for="cat in categorias" :key="cat.categoria_id">
                    <option :value="cat.categoria_id" x-text="cat.nombre_categoria"></option>
                </template>
            </select>
        </div>

        <!-- Product Cards Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3.5 max-h-[calc(100vh-250px)] overflow-y-auto pr-1">
            <template x-for="p in posFilteredProducts" :key="p.producto_id">
                <div @click="addToCart(p)"
                    class="glass-panel p-4 rounded-2xl cursor-pointer hover:border-brand-500/60 hover:bg-slate-800/60 transition-all flex flex-col justify-between group relative overflow-hidden">
                    <div>
                        <span class="text-[10px] font-mono text-brand-400 font-semibold uppercase" x-text="p.codigo_producto"></span>
                        <h5 class="text-sm font-bold text-slate-100 mt-1 line-clamp-2" x-text="p.nombre_producto"></h5>
                        <p class="text-xs text-slate-400 mt-0.5 truncate" x-text="p.categoria ? p.categoria.nombre_categoria : ''"></p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between">
                        <span class="font-display font-bold text-base text-emerald-400" x-text="formatCurrency(p.precio_venta)"></span>
                        <span class="text-[11px] px-2 py-0.5 rounded-md font-bold"
                            :class="p.existencia_bodega > 0 ? 'bg-slate-800 text-slate-300' : 'bg-rose-500/20 text-rose-400'"
                            x-text="'Stock: ' + p.existencia_bodega"></span>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Cart / Checkout Panel -->
    <div class="glass-panel p-5 rounded-2xl flex flex-col justify-between h-[calc(100vh-160px)]">
        <div class="space-y-4 overflow-y-auto pr-1">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h4 class="font-display font-bold text-base text-white flex items-center gap-2">
                    <i data-lucide="shopping-bag" class="w-4 h-4 text-brand-400"></i>
                    Factura / Carrito
                </h4>
                <button @click="clearCart()" class="text-xs text-rose-400 hover:text-rose-300 font-semibold" x-show="cart.length > 0">Vaciar</button>
            </div>

            <!-- Customer Selector -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Cliente</label>
                <select x-model.number="posSale.id_cliente" class="w-full bg-dark-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-brand-500">
                    <template x-for="c in clientes" :key="c.cliente_id">
                        <option :value="c.cliente_id" x-text="c.nombre_apellido_cliente + ' (' + (c.codigo_cliente || 'Sin código') + ')'"></option>
                    </template>
                </select>
            </div>

            <!-- Payment Method -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Método de Pago</label>
                <div class="grid grid-cols-3 gap-2">
                    <button @click="posSale.metodo_pago = 'Efectivo'"
                        :class="posSale.metodo_pago === 'Efectivo' ? 'bg-brand-600 text-white font-bold border-brand-500' : 'bg-dark-900 text-slate-400 border-slate-800'"
                        class="py-2 text-xs rounded-xl border transition-all text-center">Efectivo</button>
                    <button @click="posSale.metodo_pago = 'Tarjeta'"
                        :class="posSale.metodo_pago === 'Tarjeta' ? 'bg-brand-600 text-white font-bold border-brand-500' : 'bg-dark-900 text-slate-400 border-slate-800'"
                        class="py-2 text-xs rounded-xl border transition-all text-center">Tarjeta</button>
                    <button @click="posSale.metodo_pago = 'Transferencia'"
                        :class="posSale.metodo_pago === 'Transferencia' ? 'bg-brand-600 text-white font-bold border-brand-500' : 'bg-dark-900 text-slate-400 border-slate-800'"
                        class="py-2 text-xs rounded-xl border transition-all text-center">Transferencia</button>
                </div>
            </div>

            <!-- Cart Items List -->
            <div class="space-y-2 mt-3">
                <template x-for="(item, index) in cart" :key="item.id_producto">
                    <div class="p-3 bg-dark-900/80 rounded-xl border border-slate-800/80 flex items-center justify-between gap-2">
                        <div class="overflow-hidden">
                            <p class="text-xs font-bold text-slate-200 truncate" x-text="item.nombre_producto"></p>
                            <p class="text-[11px] text-slate-400" x-text="formatCurrency(item.precio_unitario) + ' c/u'"></p>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex items-center bg-dark-950 border border-slate-700 rounded-lg overflow-hidden">
                                <button @click="decreaseCartQty(index)" class="px-2 py-0.5 text-xs text-slate-400 hover:text-white">-</button>
                                <span class="px-2 py-0.5 text-xs font-bold text-white" x-text="item.cantidad"></span>
                                <button @click="increaseCartQty(index)" class="px-2 py-0.5 text-xs text-slate-400 hover:text-white">+</button>
                            </div>
                            <span class="text-xs font-bold text-emerald-400 w-16 text-right" x-text="formatCurrency(item.subtotal_venta_detalle)"></span>
                            <button @click="removeFromCart(index)" class="text-slate-500 hover:text-rose-400">
                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </div>
                </template>

                <div x-show="cart.length === 0" class="py-8 text-center text-slate-500 text-xs">
                    <i data-lucide="shopping-cart" class="w-6 h-6 mx-auto mb-1 opacity-50"></i>
                    <span>Haz clic en un producto para agregarlo</span>
                </div>
            </div>
        </div>

        <!-- Invoice Totals & Checkout Button -->
        <div class="pt-4 border-t border-slate-800 space-y-3">
            <div class="space-y-1.5 text-xs">
                <div class="flex justify-between text-slate-400">
                    <span>Subtotal:</span>
                    <span class="font-semibold text-slate-200" x-text="formatCurrency(cartSubtotal)"></span>
                </div>
                <div class="flex justify-between items-center text-slate-400">
                    <span>Descuento:</span>
                    <div class="flex items-center gap-1">
                        <span>C$</span>
                        <input type="number" x-model.number="posSale.descuento_venta" min="0"
                            class="w-20 bg-dark-900 border border-slate-700 rounded px-1.5 py-0.5 text-right text-xs text-white">
                    </div>
                </div>
                <div class="flex justify-between text-base font-bold text-white pt-2 border-t border-slate-800">
                    <span>Total a Pagar:</span>
                    <span class="text-emerald-400 font-display text-lg" x-text="formatCurrency(cartTotal)"></span>
                </div>
            </div>

            <button @click="processSale()"
                :disabled="cart.length === 0 || loading"
                class="w-full py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold rounded-xl shadow-lg shadow-emerald-600/25 flex items-center justify-center gap-2 transition-all">
                <i data-lucide="check-circle" class="w-5 h-5"></i>
                <span>Emitir Factura y Cobrar</span>
            </button>
        </div>
    </div>
</div>