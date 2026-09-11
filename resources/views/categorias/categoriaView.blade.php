{{-- 
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Listado de Categorías
    Archivo: resources/views/categorias/categoriaView.blade.php
    Propósito: Permite la visualización y gestión de categorías de forma modular.
    Controlador asociado: App\Http\Controllers\CategoriaController
    Modelo: App\Models\Categoria
    Integración: Incluido en welcome.blade.php mediante @include('categorias.categoriaView') 
    =============================================================================
--}}

<div x-show="currentTab === 'categorias'" x-cloak class="space-y-5">
                    <div class="flex items-center justify-between glass-panel p-4 rounded-2xl">
                        <h3 class="text-sm font-semibold text-slate-300">Gestión de Categorías de Productos</h3>
                        <button @click="openCategoryModal()" class="flex items-center gap-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold px-4 py-2 rounded-xl transition-all">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Nueva Categoría</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <template x-for="cat in categorias" :key="cat.categoria_id">
                            <div class="glass-panel p-5 rounded-2xl flex flex-col justify-between hover:border-brand-500/40 transition-all">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-mono font-bold text-brand-400" x-text="cat.codigo_categoria || 'SIN CÓDIGO'"></span>
                                        <span class="px-2 py-0.5 text-[10px] rounded-full"
                                              :class="cat.estado == 1 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-700 text-slate-400'"
                                              x-text="cat.estado == 1 ? 'Activa' : 'Inactiva'"></span>
                                    </div>
                                    <h4 class="font-display font-bold text-lg text-white" x-text="cat.nombre_categoria"></h4>
                                    <p class="text-xs text-slate-400 mt-1" x-text="cat.descripcion_categoria || 'Sin descripción'"></p>
                                </div>

                                <div class="mt-4 pt-3 border-t border-slate-800 flex items-center justify-between">
                                    <span class="text-xs text-slate-400 font-medium" 
                                          x-text="(productos.filter(p => p.id_categoria == cat.categoria_id).length) + ' productos'"></span>
                                    <div class="flex items-center gap-1">
                                        <button @click="openCategoryModal(cat)" class="p-1.5 text-slate-400 hover:text-brand-400 hover:bg-slate-800 rounded-lg">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </button>
                                        <button @click="deleteCategory(cat)" class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-slate-800 rounded-lg">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>