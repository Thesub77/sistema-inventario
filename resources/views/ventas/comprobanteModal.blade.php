{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Modal de Comprobante / Ticket de Venta Imprimible
    Archivo: resources/views/ventas/comprobanteModal.blade.php
    Propósito: Renderiza el comprobante de venta oficial en formato ticket térmico (80mm)
               y factura comercial con soporte nativo de impresión (window.print()).
    Endpoint asociado: GET /api/ventas/{id}/comprobante
    =============================================================================
--}}

<div x-show="showReceiptModal" x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm overflow-y-auto">

    <!-- Contenedor del Modal -->
    <div @click.away="showReceiptModal = false"
        class="bg-dark-900 border border-slate-700 rounded-3xl w-full max-w-lg p-6 shadow-2xl space-y-4 my-8 relative">

        <!-- Cabecera no imprimible con botones de acción rápida -->
        <div class="flex items-center justify-between border-b border-slate-800 pb-3 no-print">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center border border-emerald-500/20">
                    <i data-lucide="receipt" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="font-display font-bold text-base text-white">Comprobante de Venta</h3>
                    <p class="text-xs text-slate-400">Vista previa de impresión de ticket oficial</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="printReceipt()"
                    class="px-3.5 py-1.5 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white rounded-xl text-xs font-bold shadow-lg shadow-emerald-500/20 flex items-center gap-1.5 transition-all cursor-pointer">
                    <i data-lucide="printer" class="w-4 h-4"></i>
                    <span>Imprimir</span>
                </button>
                <button type="button" @click="showReceiptModal = false"
                    class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        </div>

        <!-- Indicador de carga -->
        <div x-show="loadingReceipt" class="py-12 text-center text-slate-400 space-y-2">
            <span class="inline-block animate-spin w-6 h-6 border-2 border-brand-500 border-t-transparent rounded-full"></span>
            <p class="text-xs">Cargando datos del comprobante...</p>
        </div>

        <!-- Área Imprimible del Comprobante (Ticket Térmico) -->
        <div x-show="!loadingReceipt && receiptData" id="printableReceiptArea"
            class="bg-white text-slate-900 rounded-2xl p-6 border border-slate-200 shadow-inner font-mono text-xs space-y-4 max-w-sm mx-auto">

            <!-- Banner de Alerta si la venta está anulada -->
            <template x-if="receiptAnulada">
                <div class="py-1.5 px-3 bg-rose-600 text-white font-sans font-black text-center text-xs uppercase tracking-widest rounded-md shadow-sm">
                    *** COMPROBANTE ANULADO ***
                </div>
            </template>

            <!-- Encabezado del Comercio -->
            <div class="text-center space-y-1">
                <h4 class="font-sans font-black text-lg text-slate-950 uppercase tracking-tight">FacturaStock Pro</h4>
                <p class="text-[11px] text-slate-600 font-sans">Sistema de Gestión & Facturación Comercial</p>
                <p class="text-[10px] text-slate-500">RUC: J0310000293847 | Tel: +505 2244-6688</p>
                <p class="text-[10px] text-slate-500">Managua, Nicaragua</p>
            </div>

            <!-- Divisor punteado de ticket -->
            <div class="border-b border-dashed border-slate-400"></div>

            <!-- Metadatos de la Factura -->
            <div class="space-y-1 text-[11px]">
                <div class="flex justify-between">
                    <span class="font-bold">FACTURA N°:</span>
                    <span class="font-extrabold text-slate-950" x-text="receiptData?.codigo_venta"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-600">Fecha / Hora:</span>
                    <span x-text="formatDate(receiptData?.fecha_hora_venta)"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-600">Cajero(a):</span>
                    <span x-text="receiptData?.usuario?.nombre_apellido || 'Caja Central'"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-600">Cliente:</span>
                    <span class="font-semibold text-slate-950" x-text="receiptData?.cliente?.nombre_apellido_cliente || 'Consumidor Final'"></span>
                </div>
                <template x-if="receiptData?.cliente?.codigo_cliente">
                    <div class="flex justify-between text-[10px] text-slate-500">
                        <span>Código Cliente:</span>
                        <span x-text="receiptData?.cliente?.codigo_cliente"></span>
                    </div>
                </template>
                <div class="flex justify-between">
                    <span class="text-slate-600">Método de Pago:</span>
                    <span class="font-bold text-slate-950" x-text="receiptData?.metodo_pago"></span>
                </div>
                <template x-if="receiptData?.referencia_transferencia || receiptData?.referencia_pago">
                    <div class="flex justify-between text-[10px] text-slate-600">
                        <span>Ref / Voucher:</span>
                        <span class="font-mono font-bold" x-text="receiptData?.referencia_transferencia || receiptData?.referencia_pago"></span>
                    </div>
                </template>
            </div>

            <!-- Divisor punteado de ticket -->
            <div class="border-b border-dashed border-slate-400"></div>

            <!-- Detalle de Productos Facturados -->
            <div>
                <table class="w-full text-left text-[11px]">
                    <thead>
                        <tr class="border-b border-slate-300 text-[10px] uppercase text-slate-600">
                            <th class="py-1">Cant</th>
                            <th class="py-1">Descripción</th>
                            <th class="py-1 text-right">P.U</th>
                            <th class="py-1 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="item in (receiptData?.venta_detalles || [])" :key="item.id_venta_detalle">
                            <tr>
                                <td class="py-1.5 align-top font-bold" x-text="item.cantidad"></td>
                                <td class="py-1.5 align-top pr-1">
                                    <span class="font-medium text-slate-900 block" x-text="item.producto ? item.producto.nombre_producto : 'Producto #' + item.id_producto"></span>
                                    <span class="text-[9px] text-slate-500 block" x-text="item.producto?.codigo_producto"></span>
                                </td>
                                <td class="py-1.5 align-top text-right text-slate-600" x-text="Number(item.precio_unitario).toFixed(2)"></td>
                                <td class="py-1.5 align-top text-right font-bold text-slate-950" x-text="Number(item.subtotal_venta_detalle).toFixed(2)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Divisor punteado de ticket -->
            <div class="border-b border-dashed border-slate-400"></div>

            <!-- Resumen de Totales -->
            <div class="space-y-1 text-[11px]">
                <div class="flex justify-between text-slate-600">
                    <span>Subtotal:</span>
                    <span x-text="formatCurrency(receiptData?.subtotal_venta || 0)"></span>
                </div>
                <template x-if="Number(receiptData?.descuento_venta) > 0">
                    <div class="flex justify-between text-rose-600 font-semibold">
                        <span>Descuento aplicado:</span>
                        <span x-text="'- ' + formatCurrency(receiptData?.descuento_venta)"></span>
                    </div>
                </template>
                <div class="flex justify-between text-sm font-sans font-black text-slate-950 pt-1.5 border-t border-slate-900">
                    <span>TOTAL A PAGAR:</span>
                    <span x-text="formatCurrency(receiptData?.total_venta || 0)"></span>
                </div>
            </div>

            <!-- Divisor punteado de ticket -->
            <div class="border-b border-dashed border-slate-400"></div>

            <!-- Pie de Factura / Agradecimiento -->
            <div class="text-center text-[10px] text-slate-500 space-y-1">
                <p class="font-bold text-slate-700">¡GRACIAS POR SU COMPRA!</p>
                <p>Conserve este comprobante como garantía oficial.</p>
                <p class="text-[9px] text-slate-400">Factura emitida electrónicamente</p>
            </div>
        </div>

        <!-- Botones de Acción Inferiores (No imprimibles) -->
        <div class="pt-3 border-t border-slate-800 flex justify-end gap-2.5 no-print">
            <button type="button" @click="showReceiptModal = false"
                class="px-4 py-2 bg-dark-950 hover:bg-slate-800 border border-slate-700 text-slate-300 hover:text-white rounded-xl text-xs font-semibold transition-all">
                Cerrar
            </button>
            <button type="button" @click="printReceipt()"
                class="px-5 py-2 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white rounded-xl text-xs font-bold shadow-lg shadow-emerald-500/25 flex items-center gap-1.5 transition-all cursor-pointer">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Imprimir Ticket</span>
            </button>
        </div>
    </div>
</div>
