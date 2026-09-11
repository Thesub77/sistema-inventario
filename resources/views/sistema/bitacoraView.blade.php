{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Kardex y Movimientos
    Archivo: resources/views/productos/inventarioView.blade.php
    Propósito: Permite la visualización de Kardex y Movimientos de productos de forma modular.
    Controlador asociado: App\Http\Controllers\ProductoController
    Modelo: App\Models\Producto
    Integración: Incluido en welcome.blade.php mediante @include('productos.inventarioView') 
    =============================================================================
--}}

<div x-show="currentTab === 'bitacora'" x-cloak class="space-y-5">
    <div class="glass-panel p-5 rounded-2xl">
        <h4 class="font-display font-bold text-base text-white mb-4 flex items-center gap-2">
            <i data-lucide="shield-check" class="w-4 h-4 text-brand-400"></i>
            Registro de Bitácora de Acciones
        </h4>
        <div class="space-y-3">
            <template x-for="b in bitacoras" :key="b.id_bitacora">
                <div class="p-4 rounded-xl bg-dark-900/60 border border-slate-800 flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-brand-500/10 border border-brand-500/20 text-brand-400 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i data-lucide="terminal" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-mono font-bold text-slate-200" x-text="b.accion_bitacora"></span>
                                <span class="text-[11px] text-slate-400" x-text="'por ' + (b.usuario ? b.usuario.nombre_apellido : 'Usuario #' + b.id_usuario)"></span>
                            </div>
                            <p class="text-xs text-slate-300 mt-1" x-text="b.descripcion_bitacora"></p>
                        </div>
                    </div>
                    <span class="text-xs text-slate-500 whitespace-nowrap" x-text="formatDate(b.fecha_hora_bitacora)"></span>
                </div>
            </template>
        </div>
    </div>
</div>