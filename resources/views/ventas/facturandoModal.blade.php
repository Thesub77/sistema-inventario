{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Modal de Emisión de Factura en Tiempo Real
    Archivo: resources/views/ventas/facturandoModal.blade.php
    Propósito: Muestra el estado de la transacción y progreso de facturación
               durante la emisión de factura en el POS con reactividad de Alpine.js.
    =============================================================================
--}}

<div x-show="isProcessingBilling" x-cloak
    class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm overflow-hidden"
    role="dialog" aria-modal="true"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95">

    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-slate-800 rounded-3xl w-full max-w-sm shadow-2xl p-6 text-center space-y-5 relative overflow-hidden">
        <!-- Resplandor decorativo -->
        <div class="absolute -right-12 -top-12 w-32 h-32 bg-brand-500/15 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute -left-12 -bottom-12 w-32 h-32 bg-emerald-500/15 rounded-full blur-2xl pointer-events-none"></div>

        <!-- Icono con animación de pulso -->
        <div class="relative w-16 h-16 mx-auto flex items-center justify-center">
            <div class="absolute inset-0 rounded-full bg-brand-500/20 animate-ping"></div>
            <div class="w-14 h-14 rounded-full bg-gradient-to-tr from-brand-600 to-indigo-600 flex items-center justify-center text-white shadow-lg shadow-brand-500/25">
                <i data-lucide="file-check" class="w-7 h-7"></i>
            </div>
        </div>

        <!-- Títulos y Estado dinámico -->
        <div class="space-y-1.5">
            <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white">
                Emitiendo Factura
            </h3>
            <p class="text-sm font-semibold text-brand-600 dark:text-brand-400" x-text="billingStatusText"></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-mono" x-show="billingCandidateCode">
                Código: <span x-text="billingCandidateCode" class="font-bold text-slate-700 dark:text-slate-200"></span>
            </p>
        </div>

        <!-- Barra de Progreso Dinámica -->
        <div class="space-y-1.5">
            <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-3 overflow-hidden p-0.5 border border-slate-200 dark:border-slate-700 shadow-inner">
                <div class="bg-gradient-to-r from-brand-500 via-indigo-500 to-emerald-500 h-full rounded-full transition-all duration-300 ease-out"
                    :style="'width: ' + billingProgress + '%'"></div>
            </div>
            <div class="flex items-center justify-between text-[11px] text-slate-400 font-medium px-1">
                <span>Deduciendo inventario...</span>
                <span x-text="billingProgress + '%'" class="font-bold text-slate-600 dark:text-slate-300"></span>
            </div>
        </div>
    </div>
</div>
