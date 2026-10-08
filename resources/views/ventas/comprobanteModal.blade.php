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
                    <h3 class="font-display font-bold text-base text-white">Comprobante de Pago</h3>
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

        <!-- Área Imprimible del Comprobante (Ticket Térmico 100% Monocromático) -->
        <div x-show="!loadingReceipt && receiptData" id="printableReceiptArea"
            class="bg-white text-black rounded-2xl p-6 border border-black shadow-inner font-mono text-xs space-y-4 max-w-sm mx-auto">

            <!-- Banner de Alerta si la venta está anulada -->
            <template x-if="receiptAnulada">
                <div class="py-1.5 px-3 bg-black font-sans font-black text-center text-xs uppercase tracking-widest rounded-md shadow-sm" style="color: #ffffff !important; background-color: #000000 !important;">
                    *** COMPROBANTE ANULADO ***
                </div>
            </template>

            <!-- Encabezado del Comercio (Dinámico desde base de datos) -->
            <div class="text-center space-y-0.5 text-black">
                <h4 class="font-sans font-black text-lg text-black uppercase tracking-tight"
                    x-text="receiptEmpresa?.nombre_comercial || receiptData?.empresa?.nombre_comercial || 'SISTEMA COMERCIAL'"></h4>
                <p class="text-[11px] text-black font-sans font-bold"
                    x-show="receiptEmpresa?.razon_social || receiptData?.empresa?.razon_social"
                    x-text="receiptEmpresa?.razon_social || receiptData?.empresa?.razon_social"></p>
                <p class="text-[10px] text-black"
                    x-show="(receiptEmpresa?.numero_ruc || receiptData?.empresa?.numero_ruc) || (receiptEmpresa?.telefono_contacto || receiptData?.empresa?.telefono_contacto)">
                    <span x-show="receiptEmpresa?.numero_ruc || receiptData?.empresa?.numero_ruc" x-text="'RUC: ' + (receiptEmpresa?.numero_ruc || receiptData?.empresa?.numero_ruc)"></span>
                    <span x-show="(receiptEmpresa?.numero_ruc || receiptData?.empresa?.numero_ruc) && (receiptEmpresa?.telefono_contacto || receiptData?.empresa?.telefono_contacto)"> | </span>
                    <span x-show="receiptEmpresa?.telefono_contacto || receiptData?.empresa?.telefono_contacto" x-text="'Tel: ' + (receiptEmpresa?.telefono_contacto || receiptData?.empresa?.telefono_contacto)"></span>
                </p>
                <p class="text-[10px] text-black"
                    x-show="receiptEmpresa?.direccion_fisica || receiptData?.empresa?.direccion_fisica"
                    x-text="receiptEmpresa?.direccion_fisica || receiptData?.empresa?.direccion_fisica"></p>
                <p class="text-[9px] text-black"
                    x-show="receiptEmpresa?.correo_contacto || receiptData?.empresa?.correo_contacto"
                    x-text="receiptEmpresa?.correo_contacto || receiptData?.empresa?.correo_contacto"></p>
            </div>

            <!-- Divisor punteado de ticket -->
            <div class="border-b border-dashed border-black"></div>

            <!-- Título Fijo del Comprobante de Pago -->
            <div class="text-center py-1.5 px-2 bg-neutral-100 rounded border border-black font-sans font-black text-xs uppercase tracking-wider text-black">
                *** COMPROBANTE DE PAGO ***
            </div>

            <!-- Metadatos de la Factura -->
            <div class="space-y-1 text-[11px] text-black">
                <div class="flex justify-between">
                    <span class="font-bold">FACTURA N°:</span>
                    <span class="font-extrabold text-black" x-text="receiptData?.codigo_venta"></span>
                </div>
                <div class="flex justify-between">
                    <span>Fecha / Hora:</span>
                    <span x-text="formatDate(receiptData?.fecha_hora_venta)"></span>
                </div>
                <div class="flex justify-between">
                    <span>Cajero(a):</span>
                    <span x-text="receiptData?.usuario?.nombre_apellido || 'Caja Central'"></span>
                </div>
                <div class="flex justify-between">
                    <span>Cliente:</span>
                    <span class="font-bold text-black" x-text="receiptData?.cliente_nombre || 'Consumidor Final'"></span>
                </div>

                <div class="flex justify-between">
                    <span>Método de Pago:</span>
                    <span class="font-bold text-black" x-text="receiptData?.metodo_pago"></span>
                </div>
                <template x-if="receiptData?.referencia_transferencia || receiptData?.referencia_pago">
                    <div class="flex justify-between text-[10px]">
                        <span>Ref / Voucher:</span>
                        <span class="font-mono font-bold" x-text="receiptData?.referencia_transferencia || receiptData?.referencia_pago"></span>
                    </div>
                </template>
            </div>

            <!-- Divisor punteado de ticket -->
            <div class="border-b border-dashed border-black"></div>

            <!-- Detalle de Productos Facturados -->
            <div>
                <table class="w-full text-left text-[11px] text-black">
                    <thead>
                        <tr class="border-b border-black text-[10px] uppercase font-bold text-black">
                            <th class="py-1">Cant</th>
                            <th class="py-1">Descripción</th>
                            <th class="py-1 text-right">P.U</th>
                            <th class="py-1 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-300">
                        <template x-for="item in (receiptData?.venta_detalles || [])" :key="item.id_venta_detalle">
                            <tr class="text-black">
                                <td class="py-1.5 align-top font-bold" x-text="item.cantidad"></td>
                                <td class="py-1.5 align-top pr-1">
                                    <span class="font-bold text-black block" x-text="item.producto ? item.producto.nombre_producto : 'Producto #' + item.id_producto"></span>
                                </td>
                                <td class="py-1.5 align-top text-right" x-text="Number(item.precio_unitario).toFixed(2)"></td>
                                <td class="py-1.5 align-top text-right font-bold text-black" x-text="Number(item.subtotal_venta_detalle).toFixed(2)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Divisor punteado de ticket -->
            <div class="border-b border-dashed border-black"></div>

            <!-- Resumen de Totales y Liquidación (Total a Pagar hasta abajo y todo en negro) -->
            <div class="space-y-1.5 text-[11px] text-black">
                <div class="flex justify-between">
                    <span>Subtotal:</span>
                    <span class="font-bold font-mono" x-text="formatCurrency(receiptData?.subtotal_venta || 0)"></span>
                </div>

                <template x-if="Number(receiptData?.descuento_venta) > 0">
                    <div class="flex justify-between font-bold text-black">
                        <span>
                            <span>Descuento aplicado</span>
                            <span class="text-[10px] font-normal" x-show="Number(receiptData?.subtotal_venta) > 0"
                                x-text="'(' + Math.round((Number(receiptData?.descuento_venta) / Number(receiptData?.subtotal_venta)) * 100) + '%)'"></span>:
                        </span>
                        <span class="font-mono text-black" x-text="'- ' + formatCurrency(receiptData?.descuento_venta)"></span>
                    </div>
                </template>

                <!-- Monto Recibido y Cambio Entregado en efectivo (En color negro) -->
                <template x-if="receiptData?.metodo_pago === 'Efectivo' || receiptData?.monto_recibido">
                    <div class="pt-1 border-t border-dotted border-black space-y-1">
                        <div class="flex justify-between">
                            <span>Monto Recibido:</span>
                            <span class="font-bold font-mono text-black" x-text="formatCurrency(receiptData?.monto_recibido || receiptData?.total_venta || 0)"></span>
                        </div>
                        <div class="flex justify-between font-bold">
                            <span>Cambio Entregado:</span>
                            <span class="font-bold font-mono text-black" x-text="formatCurrency(receiptData?.cambio ?? (Math.max(0, (Number(receiptData?.monto_recibido || receiptData?.total_venta)) - Number(receiptData?.total_venta))))"></span>
                        </div>
                    </div>
                </template>

                <!-- TOTAL A PAGAR (Hasta abajo) -->
                <div class="flex justify-between text-sm font-sans font-black text-black pt-2 border-t-2 border-black">
                    <span>TOTAL A PAGAR:</span>
                    <span class="font-mono text-black" x-text="formatCurrency(receiptData?.total_venta || 0)"></span>
                </div>
            </div>

            <!-- Divisor punteado de ticket -->
            <div class="border-b border-dashed border-black"></div>

            <!-- Pie de Factura / Agradecimiento -->
            <div class="text-center text-[10px] text-black space-y-1">
                <p class="font-bold text-black" x-text="receiptEmpresa?.mensaje_pie_ticket || receiptData?.empresa?.mensaje_pie_ticket || '¡GRACIAS POR SU COMPRA!'"></p>
                <p class="text-[9px] text-black">Factura emitida electrónicamente</p>
            </div>
        </div>

        <!-- Botones de Acción Inferiores (No imprimibles) -->
        <div class="pt-3 border-t border-slate-800 flex justify-end gap-2.5 no-print">
            <button type="button" @click="showReceiptModal = false"
                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-dark-950 dark:hover:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white rounded-xl text-xs font-semibold transition-all cursor-pointer">
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
