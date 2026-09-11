{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Formulario Modal de Ajuste de Stock
    Archivo: resources/views/productos/ajusteStockView.blade.php
    Propósito: Permite la creación y edición de productos de forma modular.
    Controlador asociado: App\Http\Controllers\ProductoController
    Modelo: App\Models\Producto
    Integración: Incluido en welcome.blade.php mediante @include('productos.ajusteStockView') 
    =============================================================================
--}}

<div x-show="showStockModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
    <div @click.away="showStockModal = false" class="bg-dark-900 border border-slate-700 rounded-2xl w-full max-w-md p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-display font-bold text-lg text-white">Ajustar Inventario</h3>
            <button @click="showStockModal = false" class="text-slate-400 hover:text-white">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form @submit.prevent="saveStockAdjustment()" class="space-y-3">
            <div class="p-3 bg-dark-950 rounded-xl border border-slate-800">
                <p class="text-xs text-slate-400">Producto:</p>
                <p class="text-sm font-bold text-white" x-text="stockForm.nombre_producto"></p>
                <p class="text-xs text-emerald-400 mt-1" x-text="'Stock Actual: ' + stockForm.stock_anterior + ' unidades'"></p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Tipo de Operación</label>
                <select x-model="stockForm.tipo_movimiento" class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white">
                    <option value="Entrada por Compra">Entrada / Compra de Stock (+)</option>
                    <option value="Salida por Merma">Salida / Merma / Daño (-)</option>
                    <option value="Ajuste Manual">Ajuste Manual</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Cantidad a Agregar / Restar</label>
                <input type="number" min="1" x-model.number="stockForm.cantidad" required
                    class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white">
            </div>

            <div class="pt-4 flex justify-end gap-2 border-t border-slate-800">
                <button type="button" @click="showStockModal = false" class="px-4 py-2 text-sm text-slate-400 hover:text-white">Cancelar</button>
                <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold rounded-xl">Aplicar Ajuste</button>
            </div>
        </form>
    </div>
</div>