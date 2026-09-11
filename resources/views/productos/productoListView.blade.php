{{-- 
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Listado de Productos
    Archivo: resources/views/productos/productoListView.blade.php
    Propósito: Permite la visualización y gestión de productos de forma modular.
    Controlador asociado: App\Http\Controllers\ProductoController
    Modelo: App\Models\Producto
    Integración: Incluido en welcome.blade.php mediante @include('productos.productoListView') 
    =============================================================================
--}}

<div x-show="currentTab === 'productos'" x-cloak class="space-y-5">
                    <!-- Filters & Actions Header -->
                    <div class="flex flex-col md:flex-row items-center justify-between gap-4 glass-panel p-4 rounded-2xl">
                        <div class="flex items-center gap-3 w-full md:w-auto">
                            <!-- Search -->
                            <div class="relative flex-1 md:w-80">
                                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                                <input type="text" x-model="searchProduct" placeholder="Buscar por código, nombre..." 
                                       class="w-full bg-dark-900 border border-slate-700/80 rounded-xl pl-10 pr-4 py-2 text-sm text-slate-100 placeholder-slate-400 focus:outline-none focus:border-brand-500">
                            </div>

                            <!-- Category Filter -->
                            <select x-model="filterCategory" class="bg-dark-900 border border-slate-700/80 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-brand-500">
                                <option value="">Todas las categorías</option>
                                <template x-for="cat in categorias" :key="cat.categoria_id">
                                    <option :value="cat.categoria_id" x-text="cat.nombre_categoria"></option>
                                </template>
                            </select>
                        </div>

                        <button @click="openProductModal()" class="w-full md:w-auto flex items-center justify-center gap-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-lg shadow-brand-600/20 transition-all">
                            <i data-lucide="plus-circle" class="w-4 h-4"></i>
                            <span>Agregar Producto</span>
                        </button>
                    </div>

                    <!-- Products Table -->
                    <div class="glass-panel rounded-2xl overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="text-xs uppercase bg-dark-900/80 text-slate-400 border-b border-slate-800">
                                    <tr>
                                        <th class="py-3 px-4">Código</th>
                                        <th class="py-3 px-4">Producto</th>
                                        <th class="py-3 px-4">Categoría</th>
                                        <th class="py-3 px-4 text-right">Costo</th>
                                        <th class="py-3 px-4 text-right">Precio Venta</th>
                                        <th class="py-3 px-4 text-center">Stock</th>
                                        <th class="py-3 px-4 text-center">Estado</th>
                                        <th class="py-3 px-4 text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/60">
                                    <template x-for="p in filteredProducts" :key="p.producto_id">
                                        <tr class="hover:bg-slate-800/40 transition-colors">
                                            <td class="py-3.5 px-4 font-mono text-xs font-semibold text-brand-300" x-text="p.codigo_producto"></td>
                                            <td class="py-3.5 px-4">
                                                <div class="font-semibold text-slate-100" x-text="p.nombre_producto"></div>
                                                <div class="text-xs text-slate-400 truncate max-w-xs" x-text="p.descripcion_producto"></div>
                                            </td>
                                            <td class="py-3.5 px-4">
                                                <span class="px-2.5 py-1 text-xs rounded-lg bg-dark-900 border border-slate-700 text-slate-300 font-medium" 
                                                      x-text="p.categoria ? p.categoria.nombre_categoria : 'Sin categoría'"></span>
                                            </td>
                                            <td class="py-3.5 px-4 text-right text-slate-400 font-medium" x-text="formatCurrency(p.costo_compra)"></td>
                                            <td class="py-3.5 px-4 text-right font-bold text-emerald-400" x-text="formatCurrency(p.precio_venta)"></td>
                                            <td class="py-3.5 px-4 text-center">
                                                <span class="px-2.5 py-1 text-xs font-bold rounded-lg inline-flex items-center gap-1"
                                                      :class="p.existencia_bodega <= p.existencia_minima 
                                                        ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30' 
                                                        : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'">
                                                    <span x-text="p.existencia_bodega + ' uds.'"></span>
                                                    <i x-show="p.existencia_bodega <= p.existencia_minima" data-lucide="alert-circle" class="w-3 h-3"></i>
                                                </span>
                                            </td>
                                            <td class="py-3.5 px-4 text-center">
                                                <span class="px-2 py-0.5 text-xs rounded-full"
                                                      :class="p.estado == 1 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-700 text-slate-400'"
                                                      x-text="p.estado == 1 ? 'Activo' : 'Inactivo'"></span>
                                            </td>
                                            <td class="py-3.5 px-4 text-center">
                                                <div class="flex items-center justify-center gap-1.5">
                                                    <!-- Edit Button -->
                                                    <button @click="openProductModal(p)" class="p-1.5 text-slate-400 hover:text-brand-400 hover:bg-slate-800 rounded-lg transition-colors" title="Editar Producto">
                                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                                    </button>
                                                    <!-- Quick Stock Adjustment -->
                                                    <button @click="openStockModal(p)" class="p-1.5 text-slate-400 hover:text-emerald-400 hover:bg-slate-800 rounded-lg transition-colors" title="Ajustar Inventario">
                                                        <i data-lucide="package-plus" class="w-4 h-4"></i>
                                                    </button>
                                                    <!-- Delete Button -->
                                                    <button @click="deleteProduct(p)" class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-slate-800 rounded-lg transition-colors" title="Eliminar Producto">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="filteredProducts.length === 0">
                                        <td colspan="8" class="py-12 text-center text-slate-400">
                                            <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 text-slate-600"></i>
                                            <p class="font-medium">No se encontraron productos coincidentes</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>