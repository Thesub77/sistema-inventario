{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Formulario Modal de Producto (con soporte para Combos)
    Archivo: resources/views/productos/productoForm.blade.php
    Propósito: Permite la creación y edición de productos estándar y combos compuestos.
    Controlador asociado: App\Http\Controllers\ProductoController
    Modelo: App\Models\Producto
    Integración: Incluido en welcome.blade.php mediante @include('productos.productoForm') 
    =============================================================================
--}}

<div x-show="showProductModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
    <div @click.away="showProductModal = false" class="bg-dark-900 border border-slate-700 rounded-2xl w-full max-w-lg p-6 shadow-2xl space-y-4 max-h-[92vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-display font-bold text-lg text-white" x-text="isEditingProduct ? 'Editar Producto' : 'Nuevo Producto'"></h3>
            <button @click="showProductModal = false" class="text-slate-400 hover:text-white">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form @submit.prevent="saveProduct()" class="space-y-3.5">
            <!-- Selector de Tipo de Producto -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Tipo de Producto</label>
                <div class="grid grid-cols-2 gap-2 p-1 bg-dark-950 border border-slate-800 rounded-xl">
                    <button type="button" 
                        @click="typeof setProductTipo === 'function' ? setProductTipo('estandar') : (productForm.tipo_producto = 'estandar')"
                        :class="productForm.tipo_producto !== 'combo' ? 'bg-brand-600 text-white shadow-md shadow-brand-600/30' : 'text-slate-400 hover:text-white'"
                        class="py-2 px-3 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <i data-lucide="package" class="w-4 h-4"></i>
                        <span>Individual / Estándar</span>
                    </button>
                    <button type="button" 
                        @click="typeof setProductTipo === 'function' ? setProductTipo('combo') : (productForm.tipo_producto = 'combo', productForm.existencia_bodega = 0, productForm.componentes = (productForm.componentes || []))"
                        :class="productForm.tipo_producto === 'combo' ? 'bg-purple-600 text-white shadow-md shadow-purple-600/30' : 'text-slate-400 hover:text-white'"
                        class="py-2 px-3 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <i data-lucide="layers" class="w-4 h-4"></i>
                        <span>Combo / Compuesto</span>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Código</label>
                    <input type="text" x-model="productForm.codigo_producto" required
                        @input="productForm.codigo_producto = $event.target.value.replace(/[^a-zA-Z0-9\-]/g, '')"
                        :disabled="!isEditingProduct && (productForm.tipo_producto === 'combo' ? (empresa?.configuracion?.productos?.modo_codigo_combo === 'automatico') : (empresa?.configuracion?.productos?.modo_codigo === 'automatico'))"
                        :placeholder="productForm.tipo_producto === 'combo'
                            ? (empresa?.configuracion?.productos?.modo_codigo_combo === 'manual' ? 'Código manual para combo...' : 'Ej. ' + ((empresa?.configuracion?.productos?.prefijo_codigo_combo || 'COMBO').replace(/-+$/, '')) + '-001')
                            : (empresa?.configuracion?.productos?.modo_codigo === 'manual' ? 'Código manual o de barras...' : 'Ej. ' + ((empresa?.configuracion?.productos?.prefijo_codigo || 'PROD').replace(/-+$/, '')) + '-001')"
                        class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500 font-mono disabled:opacity-60 disabled:cursor-not-allowed disabled:bg-slate-900/60">
                    <span x-show="!isEditingProduct && (productForm.tipo_producto === 'combo' ? (empresa?.configuracion?.productos?.modo_codigo_combo === 'automatico') : (empresa?.configuracion?.productos?.modo_codigo === 'automatico'))"
                        class="text-[10px] text-slate-400 mt-0.5 block">Autogenerado según configuración</span>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Categoría</label>
                    <select x-model="productForm.id_categoria" required
                        class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500">
                        <option value="">Selecciona categoría</option>
                        <template x-for="cat in categorias" :key="cat.categoria_id">
                            <option :value="cat.categoria_id" x-text="cat.nombre_categoria"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Nombre del Producto</label>
                <input type="text" x-model="productForm.nombre_producto" required :placeholder="productForm.tipo_producto === 'combo' ? 'Ej. Cubetazo Toña (6 cervezas)' : 'Ej. Coca-Cola 2L'"
                    class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Descripción</label>
                <input type="text" x-model="productForm.descripcion_producto" required placeholder="Detalles o especificaciones"
                    class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500">
            </div>

            <!-- Panel de Componentes (Solo para Combos) -->
            <div x-show="productForm.tipo_producto === 'combo'" class="p-3.5 bg-dark-950/80 border border-purple-500/30 rounded-xl space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-purple-300 flex items-center gap-1.5">
                        <i data-lucide="boxes" class="w-4 h-4"></i>
                        Componentes del Combo
                    </span>
                    <span class="text-[11px] text-slate-400 font-mono" x-text="(productForm.componentes ? productForm.componentes.length : 0) + ' producto(s)'"></span>
                </div>

                <!-- Lista de componentes agregados -->
                <div class="space-y-1.5 max-h-36 overflow-y-auto pr-1">
                    <template x-for="(comp, idx) in (productForm.componentes || [])" :key="comp.id_producto_componente">
                        <div class="flex items-center justify-between gap-2 p-2 rounded-lg bg-dark-900 border border-slate-800 text-xs text-slate-200">
                            <div class="flex-1 truncate">
                                <span class="font-semibold text-white" x-text="comp.nombre_producto || getProductoNombre(comp.id_producto_componente)"></span>
                                <span class="text-[11px] text-slate-400 block" x-text="'Costo unitario: C$ ' + getProductoCosto(comp.id_producto_componente)"></span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-slate-400 text-[11px]">Cant:</span>
                                <input type="number" min="1" x-model.number="comp.cantidad"
                                    class="w-14 bg-dark-950 border border-slate-700 rounded px-1.5 py-0.5 text-center text-xs text-white">
                                <button type="button" @click="removerComponenteCombo(idx)" class="p-1 text-slate-400 hover:text-rose-400 rounded transition-colors" title="Quitar">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                    <div x-show="!productForm.componentes || productForm.componentes.length === 0" class="text-center py-2.5 text-xs text-slate-400">
                        Agrega al menos un producto componente que conforme el combo.
                    </div>
                </div>

                <!-- Selector para agregar componentes -->
                <div class="pt-2 border-t border-slate-800/80 flex items-center gap-2">
                    <select x-model="comboNuevoComponenteId" class="flex-1 bg-dark-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-white focus:outline-none focus:border-purple-500">
                        <option value="">Selecciona componente...</option>
                        <template x-for="p in productosDisponiblesParaCombo" :key="p.producto_id">
                            <option :value="p.producto_id" x-text="p.nombre_producto + ' (Stock: ' + p.existencia_bodega + ')'"></option>
                        </template>
                    </select>
                    <input type="number" min="1" x-model.number="comboNuevaCantidad" placeholder="Cant"
                        class="w-16 bg-dark-900 border border-slate-700 rounded-lg px-2 py-1.5 text-xs text-white text-center">
                    <button type="button" @click="agregarComponenteCombo()"
                        class="px-3 py-1.5 bg-purple-600 hover:bg-purple-500 text-white rounded-lg text-xs font-semibold transition-colors flex items-center gap-1 cursor-pointer">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>Añadir</span>
                    </button>
                </div>

                <!-- Costo sugerido automático -->
                <div x-show="costoTotalComboCalculado > 0" class="flex items-center justify-between text-[11px] text-slate-300 bg-slate-800/40 px-2.5 py-1.5 rounded-lg border border-slate-800">
                    <span>Costo acumulado: <strong class="text-purple-300" x-text="'C$ ' + costoTotalComboCalculado.toFixed(2)"></strong></span>
                    <button type="button" @click="aplicarCostoSugeridoCombo()" class="text-purple-400 hover:text-purple-300 underline font-semibold cursor-pointer">
                        Usar como Costo de Compra
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Costo de Compra (C$)</label>
                    <input type="number" step="0.01" x-model.number="productForm.costo_compra" required
                        class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Precio de Venta (C$)</label>
                    <input type="number" step="0.01" x-model.number="productForm.precio_venta" required
                        class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <!-- Campos de Stock para Producto Estándar -->
            <div x-show="productForm.tipo_producto !== 'combo'" class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Stock Inicial</label>
                    <input type="number" x-model.number="productForm.existencia_bodega" :required="productForm.tipo_producto !== 'combo'"
                        class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Stock Mínimo</label>
                    <input type="number" x-model.number="productForm.existencia_minima" required
                        class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <!-- Información de Stock para Combos -->
            <div x-show="productForm.tipo_producto === 'combo'" class="p-3 bg-slate-800/30 border border-slate-800 rounded-xl flex items-start gap-2.5">
                <i data-lucide="info" class="w-4 h-4 text-purple-400 shrink-0 mt-0.5"></i>
                <div class="text-[11px] text-slate-300 space-y-1">
                    <p class="font-semibold text-white">Inventario Automatizado</p>
                    <p class="text-slate-400">Los combos no manejan inventario físico directo. Las existencias disponibles se deducen directamente de sus componentes en tiempo real al concretar ventas.</p>
                </div>
            </div>

            <div class="pt-4 flex justify-end gap-2 border-t border-slate-800">
                <button type="button" @click="showProductModal = false" class="px-4 py-2 text-sm text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-colors cursor-pointer">Cancelar</button>
                <button type="submit" class="px-5 py-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold rounded-xl shadow-lg shadow-brand-600/20 cursor-pointer">Guardar Producto</button>
            </div>
        </form>
    </div>
</div>