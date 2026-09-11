{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Formulario Modal de Producto
    Archivo: resources/views/productos/productoForm.blade.php
    Propósito: Permite la creación y edición de productos de forma modular.
    Controlador asociado: App\Http\Controllers\ProductoController
    Modelo: App\Models\Producto
    Integración: Incluido en welcome.blade.php mediante @include('productos.productoForm') 
    =============================================================================
--}}

<div x-show="showProductModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
    <div @click.away="showProductModal = false" class="bg-dark-900 border border-slate-700 rounded-2xl w-full max-w-lg p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-display font-bold text-lg text-white" x-text="isEditingProduct ? 'Editar Producto' : 'Nuevo Producto'"></h3>
            <button @click="showProductModal = false" class="text-slate-400 hover:text-white">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form @submit.prevent="saveProduct()" class="space-y-3">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Código</label>
                    <input type="text" x-model="productForm.codigo_producto" required placeholder="PROD-001"
                        class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500">
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
                <input type="text" x-model="productForm.nombre_producto" required placeholder="Ej. Coca-Cola 2L"
                    class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Descripción</label>
                <input type="text" x-model="productForm.descripcion_producto" required placeholder="Detalles o especificaciones"
                    class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500">
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

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Stock Inicial</label>
                    <input type="number" x-model.number="productForm.existencia_bodega" required
                        class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Stock Mínimo</label>
                    <input type="number" x-model.number="productForm.existencia_minima" required
                        class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <div class="pt-4 flex justify-end gap-2 border-t border-slate-800">
                <button type="button" @click="showProductModal = false" class="px-4 py-2 text-sm text-slate-400 hover:text-white">Cancelar</button>
                <button type="submit" class="px-5 py-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold rounded-xl shadow-lg shadow-brand-600/20">Guardar Producto</button>
            </div>
        </form>
    </div>
</div>