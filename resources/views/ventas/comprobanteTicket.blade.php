{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Comprobante de Venta (Ticket)
    Archivo: resources/views/ventas/comprobanteTicket.blade.php
    Propósito: Estructura HTML optimizada para impresión física (papel / impresora térmica)
               o guardado directo como documento PDF.
    =============================================================================
--}}

<div id="print-area" class="hidden">
    <style>
        @media print {
            body * {
                visibility: hidden !important;
            }
            #print-area, #print-area * {
                visibility: visible !important;
            }
            #print-area {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                max-width: 80mm !important;
                margin: 0 auto !important;
                padding: 10px !important;
                background: #ffffff !important;
                color: #000000 !important;
                font-family: 'Courier New', Courier, monospace !important;
                font-size: 11px !important;
                line-height: 1.3 !important;
                display: block !important;
            }
            .ticket-header {
                text-align: center;
                border-bottom: 1px dashed #000;
                padding-bottom: 8px;
                margin-bottom: 8px;
            }
            .ticket-header h2 {
                margin: 0;
                font-size: 15px;
                font-weight: bold;
                text-transform: uppercase;
            }
            .ticket-header p {
                margin: 2px 0;
                font-size: 10px;
            }
            .ticket-info {
                margin-bottom: 8px;
                border-bottom: 1px dashed #000;
                padding-bottom: 6px;
            }
            .ticket-info-row {
                display: flex;
                justify-content: space-between;
                margin-bottom: 2px;
            }
            .ticket-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 8px;
            }
            .ticket-table th {
                border-bottom: 1px solid #000;
                text-align: left;
                font-size: 10px;
                padding: 3px 0;
            }
            .ticket-table td {
                padding: 3px 0;
                vertical-align: top;
            }
            .ticket-totals {
                border-top: 1px dashed #000;
                border-bottom: 1px dashed #000;
                padding: 6px 0;
                margin-bottom: 8px;
            }
            .ticket-totals-row {
                display: flex;
                justify-content: space-between;
                margin-bottom: 3px;
            }
            .ticket-total-final {
                font-weight: bold;
                font-size: 13px;
            }
            .ticket-footer {
                text-align: center;
                font-size: 10px;
                margin-top: 10px;
            }
        }
    </style>

    <div class="ticket-header">
        <h2>FacturaStock Pro</h2>
        <p>Sistema de Facturación e Inventario</p>
        <p>Tel: (505) 2222-3344</p>
        <p>Managua, Nicaragua</p>
    </div>

    <div class="ticket-info">
        <div class="ticket-info-row">
            <span><strong>COMPROBANTE:</strong></span>
            <span id="print-codigo-venta"></span>
        </div>
        <div class="ticket-info-row">
            <span><strong>FECHA:</strong></span>
            <span id="print-fecha-venta"></span>
        </div>
        <div class="ticket-info-row">
            <span><strong>CLIENTE:</strong></span>
            <span id="print-cliente-venta"></span>
        </div>
        <div class="ticket-info-row">
            <span><strong>VENDEDOR:</strong></span>
            <span id="print-usuario-venta"></span>
        </div>
        <div class="ticket-info-row">
            <span><strong>PAGO:</strong></span>
            <span id="print-metodo-pago"></span>
        </div>
    </div>

    <table class="ticket-table">
        <thead>
            <tr>
                <th style="width: 15%;">CANT</th>
                <th style="width: 55%;">DESCRIPCIÓN</th>
                <th style="width: 30%; text-align: right;">TOTAL</th>
            </tr>
        </thead>
        <tbody id="print-items-tbody">
            <!-- Rellenado dinámicamente -->
        </tbody>
    </table>

    <div class="ticket-totals">
        <div class="ticket-totals-row">
            <span>SUBTOTAL:</span>
            <span id="print-subtotal-venta"></span>
        </div>
        <div class="ticket-totals-row">
            <span>DESCUENTO:</span>
            <span id="print-descuento-venta"></span>
        </div>
        <div class="ticket-totals-row ticket-total-final">
            <span>TOTAL A PAGAR:</span>
            <span id="print-total-venta"></span>
        </div>
    </div>

    <div class="ticket-footer">
        <p>*** GRACIAS POR SU COMPRA ***</p>
        <p>Conserve este comprobante para cualquier reclamo o garantía.</p>
        <p>Emitido electrónicamente</p>
    </div>
</div>

