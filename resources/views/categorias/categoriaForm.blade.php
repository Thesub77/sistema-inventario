{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Formulario Modal de Categoría
    Archivo: resources/views/categorias/categoriaForm.blade.php
    Propósito: Permite la creación y edición de categorías de productos de forma modular.
    Controlador asociado: App\Http\Controllers\CategoriaController
    Modelo: App\Models\Categoria
    Integración: Incluido en welcome.blade.php mediante @include('categorias.categoriaForm') 
    =============================================================================
--}}

<!-- MODAL: CATEGORÍA (CREAR / EDITAR) -->
<!-- MODAL: CATEGORÍA (CREAR / EDITAR) -->
<div x-show="showCategoryModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
    {{-- Contenedor principal del modal con cierre al hacer clic fuera --}}
    <div @click.away="showCategoryModal = false" class="bg-dark-900 border border-slate-700 rounded-2xl w-full max-w-md p-6 shadow-2xl space-y-4">

        {{-- Cabecera del modal: Título dinámico según si es creación o edición --}}
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-display font-bold text-lg text-white" x-text="isEditingCategory ? 'Editar Categoría' : 'Nueva Categoría'"></h3>
            <button type="button" @click="showCategoryModal = false" class="text-slate-400 hover:text-white transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        {{-- Formulario con interceptor Alpine.js @submit.prevent para guardar sin recargar página --}}
        <form @submit.prevent="saveCategory()" class="space-y-3">

            {{-- Campo: Código de Categoría (BD: codigo_categoria VARCHAR(16) NULLABLE) --}}
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Código</label>
                <input type="text"
                    x-model="categoryForm.codigo_categoria"
                    maxlength="16"
                    placeholder="CAT-001"
                    class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500">
            </div>

            {{-- Campo: Nombre de Categoría (BD: nombre_categoria VARCHAR(24) NOT NULL) --}}
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Nombre de la Categoría <span class="text-rose-400">*</span></label>
                <input type="text"
                    x-model="categoryForm.nombre_categoria"
                    required
                    maxlength="24"
                    placeholder="Ej. Lácteos"
                    class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500">
            </div>

            {{-- Campo: Descripción de Categoría (BD: descripcion_categoria VARCHAR(64) NULLABLE) --}}
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Descripción</label>
                <input type="text"
                    x-model="categoryForm.descripcion_categoria"
                    maxlength="64"
                    placeholder="Breve descripción"
                    class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500">
            </div>

            {{-- Campo: Estado (BD: estado TINYINT NOT NULL - 1: Activa, 0: Inactiva) --}}
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Estado</label>
                <select x-model.number="categoryForm.estado"
                    class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500">
                    <option :value="1">Activa</option>
                    <option :value="0">Inactiva</option>
                </select>
            </div>

            {{-- Botones de Acción del Formulario (con bloqueo anti-doble clic) --}}
            <div class="pt-4 flex justify-end gap-2 border-t border-slate-800">
                <button type="button"
                    @click="showCategoryModal = false"
                    :disabled="isSavingCategory"
                    class="px-4 py-2 text-sm text-slate-400 hover:text-white transition-colors disabled:opacity-50">
                    Cancelar
                </button>
                <button type="submit"
                    :disabled="isSavingCategory"
                    :class="isSavingCategory ? 'opacity-60 cursor-not-allowed' : ''"
                    class="px-5 py-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold rounded-xl transition-all inline-flex items-center gap-2">
                    <span x-show="isSavingCategory" class="inline-block animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full" x-cloak></span>
                    <span x-text="isSavingCategory ? 'Guardando...' : 'Guardar'"></span>
                </button>
            </div>
        </form>
    </div>
</div>