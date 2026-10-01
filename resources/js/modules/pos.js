export function posModule() {
    return {
        // POS State & Filters
        posSearch: '',
        posCategoryFilter: '',
        searchVenta: '',
        ventaFechaDesde: '',
        ventaFechaHasta: '',
        ventaUsuarioFilter: '',
        ventaQuickRange: '',
        cart: [],
        posSale: {
            id_cliente: null,
            metodo_pago: 'Efectivo',
            referencia_transferencia: '',
            monto_recibido: null,
            descuento_venta: 0,
            tipo_descuento: 'monto',
            descuento_porcentaje: 0,
        },
        showSaleDetailModal: false,
        selectedSale: null,
        showCartDrawer: false,

        // Estado del comprobante imprimible
        showReceiptModal: false,
        receiptData: null,
        receiptEmpresa: null,
        receiptAnulada: false,
        loadingReceipt: false,

        // Estado y control de ventas en espera (Parked Orders / RF-16)
        ventasEspera: [],
        showVentasEsperaModal: false,
        loadingVentasEspera: false,
        resumedVentaEsperaId: null,

        // POS Cart Operations
        addToCart(product) {
            if (product.existencia_bodega <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sin Stock',
                    text: 'El producto no cuenta con existencias disponibles en bodega.',
                    background: '#1e293b',
                    color: '#fff'
                });
                return;
            }

            const existing = this.cart.find(i => i.id_producto === product.producto_id);
            if (existing) {
                if (existing.cantidad + 1 > product.existencia_bodega) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Límite de Stock',
                        text: `Solo hay ${product.existencia_bodega} unidades disponibles.`,
                        background: '#1e293b',
                        color: '#fff'
                    });
                    return;
                }
                existing.cantidad++;
                existing.subtotal_venta_detalle = existing.cantidad * existing.precio_unitario;
            } else {
                this.cart.push({
                    id_producto: product.producto_id,
                    nombre_producto: product.nombre_producto,
                    cantidad: 1,
                    precio_unitario: Number(product.precio_venta),
                    subtotal_venta_detalle: Number(product.precio_venta),
                    existencia_bodega: Number(product.existencia_bodega || 0),
                });
            }
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        getProductStock(productId) {
            const p = this.productos.find(prod => prod.producto_id === productId);
            return p ? (p.existencia_bodega ?? 0) : 0;
        },

        increaseCartQty(index) {
            const item = this.cart[index];
            const product = this.productos.find(p => p.producto_id === item.id_producto);
            if (product && item.cantidad + 1 > product.existencia_bodega) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Límite de Stock',
                    text: `Solo hay ${product.existencia_bodega} unidades disponibles.`,
                    background: '#1e293b',
                    color: '#fff'
                });
                return;
            }
            item.cantidad++;
            item.subtotal_venta_detalle = item.cantidad * item.precio_unitario;
        },

        decreaseCartQty(index) {
            const item = this.cart[index];
            if (item.cantidad > 1) {
                item.cantidad--;
                item.subtotal_venta_detalle = item.cantidad * item.precio_unitario;
            } else {
                this.removeFromCart(index);
            }
        },

        removeFromCart(index) {
            this.cart.splice(index, 1);
        },

        clearCart() {
            this.cart = [];
            this.resumedVentaEsperaId = null;
            this.posSale.descuento_venta = 0;
            this.posSale.descuento_porcentaje = 0;
            this.posSale.tipo_descuento = 'monto';
            this.posSale.referencia_transferencia = '';
            this.posSale.monto_recibido = null;
        },

        setDiscountType(type) {
            this.posSale.tipo_descuento = type;
            if (type === 'porcentaje') {
                if (this.cartSubtotal > 0 && this.posSale.descuento_venta > 0 && !this.posSale.descuento_porcentaje) {
                    this.posSale.descuento_porcentaje = Number(((this.posSale.descuento_venta / this.cartSubtotal) * 100).toFixed(1));
                }
                this.updatePercentDiscount();
            } else {
                this.posSale.descuento_porcentaje = 0;
            }
        },

        applyQuickPercent(pct) {
            this.posSale.tipo_descuento = 'porcentaje';
            this.posSale.descuento_porcentaje = pct;
            this.updatePercentDiscount();
        },

        updatePercentDiscount() {
            const pct = Math.min(100, Math.max(0, Number(this.posSale.descuento_porcentaje) || 0));
            this.posSale.descuento_venta = Number(((this.cartSubtotal * pct) / 100).toFixed(2));
        },

        async processSale() {
            if (this.cart.length === 0) return;

            // Bloqueo estricto del POS: No se puede facturar sin turno activo
            if (!this.turnoActivo) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Caja Cerrada',
                    text: 'No se puede generar la factura porque la caja no está abierta. Debe abrir un turno operativo primero.',
                    showCancelButton: true,
                    confirmButtonText: 'Abrir Caja Ahora',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#10b981',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.openCajaAperturaModal();
                    }
                });
                return;
            }

            // Validación de voucher (Tarjeta) y referencia (Transferencia) con longitud mínima
            if (this.posSale.metodo_pago === 'Transferencia' || this.posSale.metodo_pago === 'Tarjeta') {
                const ref = (this.posSale.referencia_transferencia || '').trim();
                const isTarjeta = this.posSale.metodo_pago === 'Tarjeta';
                const nombreCampo = isTarjeta ? 'número de voucher / autorización' : 'número de transferencia / referencia';
                const minLength = 4;

                if (!ref) {
                    Swal.fire({
                        icon: 'warning',
                        title: isTarjeta ? 'Voucher Requerido' : 'Referencia Requerida',
                        text: `Debes ingresar el ${nombreCampo} para procesar el pago con ${this.posSale.metodo_pago.toLowerCase()}.`,
                        background: this.darkMode ? '#1e293b' : '#ffffff',
                        color: this.darkMode ? '#fff' : '#0f172a'
                    });
                    return;
                }

                if (ref.length < minLength) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Mínimo Requerido No Alcanzado',
                        text: `El ${nombreCampo} debe contener al menos ${minLength} caracteres.`,
                        background: this.darkMode ? '#1e293b' : '#ffffff',
                        color: this.darkMode ? '#fff' : '#0f172a'
                    });
                    return;
                }
            }

            // Validación obligatoria de efectivo recibido para evitar errores humanos de cálculo
            if (this.posSale.metodo_pago === 'Efectivo') {
                const montoRecibidoNum = Number(this.posSale.monto_recibido);
                if (!this.posSale.monto_recibido || isNaN(montoRecibidoNum) || montoRecibidoNum <= 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Efectivo Recibido Requerido',
                        text: 'Debes ingresar el monto de efectivo entregado por el cliente (o pulsar "Paga Exacto") para calcular el cambio.',
                        background: this.darkMode ? '#1e293b' : '#ffffff',
                        color: this.darkMode ? '#fff' : '#0f172a'
                    });
                    return;
                }

                if (montoRecibidoNum < this.cartTotal) {
                    const faltante = this.cartTotal - montoRecibidoNum;
                    Swal.fire({
                        icon: 'warning',
                        title: 'Efectivo Insuficiente',
                        text: `El cliente entregó ${this.formatCurrency(montoRecibidoNum)}, pero el total es ${this.formatCurrency(this.cartTotal)}. Faltan ${this.formatCurrency(faltante)}.`,
                        background: this.darkMode ? '#1e293b' : '#ffffff',
                        color: this.darkMode ? '#fff' : '#0f172a'
                    });
                    return;
                }
            }

            // Cálculo del efectivo entregado y cambio correspondiente
            const efectivoRecibido = this.posSale.metodo_pago === 'Efectivo'
                ? Number(this.posSale.monto_recibido)
                : null;
            const cambioCalculado = this.posSale.metodo_pago === 'Efectivo'
                ? Math.max(0, (efectivoRecibido || 0) - this.cartTotal)
                : 0;

            // Generar código de factura de inmediato en 0ms sin bloquear con peticiones de red
            let candidateCode = '';
            if (this.ventas && this.ventas.length > 0) {
                const nums = this.ventas.map(v => {
                    const match = (v.codigo_venta || '').match(/(\d+)$/);
                    return match ? parseInt(match[1], 10) : 0;
                });
                let nextNum = Math.max(0, ...nums) + 1;
                candidateCode = `FAC-2026-${String(nextNum).padStart(4, '0')}`;
                while (this.ventas.some(v => v.codigo_venta === candidateCode)) {
                    nextNum++;
                    candidateCode = `FAC-2026-${String(nextNum).padStart(4, '0')}`;
                }
            } else {
                candidateCode = `FAC-2026-${Date.now().toString().slice(-6)}`;
            }

            const salePayload = {
                id_usuario: this.currentUser?.usuario_id || (this.usuarios[0] ? this.usuarios[0].usuario_id : 1),
                id_cliente: this.posSale.id_cliente || (this.clientes[0] ? this.clientes[0].cliente_id : null),
                codigo_venta: candidateCode,
                metodo_pago: this.posSale.metodo_pago,
                referencia_transferencia: (this.posSale.metodo_pago === 'Transferencia' || this.posSale.metodo_pago === 'Tarjeta')
                    ? (this.posSale.referencia_transferencia ? this.posSale.referencia_transferencia.trim() : null)
                    : null,
                fecha_hora_venta: new Date().toISOString().slice(0, 19).replace('T', ' '),
                subtotal_venta: this.cartSubtotal,
                descuento_venta: this.posSale.descuento_venta || 0,
                total_venta: this.cartTotal,
                estado: 1,
                detalles: this.cart.map(i => ({
                    id_producto: i.id_producto,
                    cantidad: i.cantidad,
                    precio_unitario: i.precio_unitario,
                    subtotal_venta_detalle: i.subtotal_venta_detalle,
                }))
            };

            this.loading = true;

            // Animación centrada en pantalla con barra de progreso mientras el backend emite la factura
            let progressInterval = null;
            Swal.fire({
                title: 'Emitiendo Factura',
                html: `
                    <div class="py-3 px-1 space-y-4">
                        <div class="relative w-16 h-16 mx-auto flex items-center justify-center">
                            <div class="absolute inset-0 rounded-full bg-brand-500/20 animate-ping"></div>
                            <div class="w-14 h-14 rounded-full bg-gradient-to-tr from-brand-600 to-indigo-600 flex items-center justify-center text-white shadow-lg shadow-brand-500/25">
                                <svg class="w-7 h-7 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <p id="pos-billing-status" class="text-sm font-semibold ${this.darkMode ? 'text-slate-200' : 'text-slate-700'} transition-all">
                                Procesando transacción...
                            </p>
                            <p class="text-xs ${this.darkMode ? 'text-slate-400' : 'text-slate-500'} font-mono">
                                Código: ${salePayload.codigo_venta}
                            </p>
                        </div>

                        <div class="w-full ${this.darkMode ? 'bg-slate-700/60 border-slate-600/50' : 'bg-slate-200 border-slate-300'} rounded-full h-3 overflow-hidden p-0.5 border shadow-inner">
                            <div id="pos-billing-bar" class="bg-gradient-to-r from-brand-500 via-indigo-500 to-emerald-500 h-full rounded-full transition-all duration-300 ease-out" style="width: 15%"></div>
                        </div>

                        <p class="text-[11px] ${this.darkMode ? 'text-slate-400' : 'text-slate-500'}">
                            Generando comprobante fiscal y deduciendo inventario...
                        </p>
                    </div>
                `,
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                background: this.darkMode ? '#1e293b' : '#ffffff',
                color: this.darkMode ? '#fff' : '#0f172a',
                didOpen: () => {
                    const bar = document.getElementById('pos-billing-bar');
                    const statusText = document.getElementById('pos-billing-status');
                    const steps = [
                        { pct: 35, text: 'Verificando existencias...' },
                        { pct: 60, text: 'Registrando pago...' },
                        { pct: 80, text: 'Generando comprobante...' },
                        { pct: 92, text: 'Finalizando emisión...' }
                    ];
                    let stepIdx = 0;
                    progressInterval = setInterval(() => {
                        if (stepIdx < steps.length) {
                            if (bar) bar.style.width = steps[stepIdx].pct + '%';
                            if (statusText) statusText.textContent = steps[stepIdx].text;
                            stepIdx++;
                        }
                    }, 220);
                },
                willClose: () => {
                    if (progressInterval) clearInterval(progressInterval);
                }
            });

            try {
                let res = await this.apiFetch('/api/ventas', {
                    method: 'POST',
                    body: JSON.stringify(salePayload)
                });

                // Si ocurriese una colisión inesperada de código por concurrencia, reintentar con sufijo único
                if (!res.ok) {
                    const clonedRes = res.clone();
                    const errData = await clonedRes.json().catch(() => ({}));
                    const codeCollision = (errData.errors && errData.errors.codigo_venta) ||
                                          (typeof errData.message === 'string' && errData.message.includes('codigo venta'));
                    if (codeCollision) {
                        salePayload.codigo_venta = `FAC-2026-${Date.now().toString().slice(-6)}`;
                        res = await this.apiFetch('/api/ventas', {
                            method: 'POST',
                            body: JSON.stringify(salePayload)
                        });
                    }
                }

                if (!res.ok) {
                    const err = await res.json();
                    throw new Error(err.message || 'Error al procesar la venta');
                }

                const responseData = await res.json();
                const newSale = responseData.venta;

                // Completar la barra de progreso al 100% con feedback positivo
                if (progressInterval) clearInterval(progressInterval);
                const bar = document.getElementById('pos-billing-bar');
                const statusText = document.getElementById('pos-billing-status');
                if (bar) bar.style.width = '100%';
                if (statusText) statusText.textContent = '¡Factura emitida exitosamente!';
                await new Promise(r => setTimeout(r, 260));

                // Descontar existencias localmente para respuesta visual instantánea (0ms)
                salePayload.detalles.forEach(d => {
                    const p = this.productos.find(prod => prod.producto_id === d.id_producto);
                    if (p) p.existencia_bodega = Math.max(0, p.existencia_bodega - d.cantidad);
                });

                // Si la venta provenía de una orden en espera reanudada, se cierra ahora que fue facturada
                const prevResumedId = this.resumedVentaEsperaId;
                this.clearCart();
                if (prevResumedId) {
                    this.apiFetch(`/api/ventas-espera/${prevResumedId}`, { method: 'DELETE' })
                        .then(() => this.fetchVentasEspera())
                        .catch(err => console.warn('Error al cerrar orden en espera procesada:', err));
                }

                // Actualizar historial en memoria de inmediato (0ms)
                if (Array.isArray(this.ventas)) {
                    this.ventas = [newSale, ...this.ventas];
                    if (typeof this.renderDashboardCharts === 'function') {
                        this.renderDashboardCharts();
                    }
                }

                // Precargar datos del comprobante con efectivo y cambio para apertura inmediata (0ms)
                newSale.monto_recibido = efectivoRecibido;
                newSale.cambio = cambioCalculado;
                this.receiptData = newSale;
                this.receiptAnulada = Boolean(newSale.estado === 0);

                // Guardar en caché de sesión para recordar el monto entregado en el voucher
                if (salePayload.metodo_pago === 'Efectivo' && efectivoRecibido) {
                    try {
                        const cashMap = JSON.parse(sessionStorage.getItem('pos_cash_records') || '{}');
                        cashMap[newSale.venta_id] = { monto_recibido: efectivoRecibido, cambio: cambioCalculado };
                        cashMap[newSale.codigo_venta] = { monto_recibido: efectivoRecibido, cambio: cambioCalculado };
                        sessionStorage.setItem('pos_cash_records', JSON.stringify(cashMap));
                    } catch (e) {}
                }

                // Sincronizaciones secundarias en background sin bloquear la interfaz
                setTimeout(() => {
                    this.fetchInventario();
                    this.fetchBitacoras();
                }, 100);

                // Notificación clara y prominente para el cajero
                let alertHtml = `<p class="text-sm">Factura <strong>${newSale.codigo_venta}</strong> emitida por <strong>C$ ${newSale.total_venta}</strong></p>`;
                if (salePayload.metodo_pago === 'Efectivo' && efectivoRecibido) {
                    alertHtml += `<div class="mt-3 p-3 rounded-xl bg-slate-800/80 border border-slate-700 text-xs text-left space-y-1.5 font-mono">
                        <div class="flex justify-between text-slate-300"><span>Monto Recibido:</span> <span>${this.formatCurrency(efectivoRecibido)}</span></div>
                        <div class="flex justify-between font-bold text-emerald-400 text-sm border-t border-slate-700/80 pt-1"><span>Cambio a Entregar:</span> <span>${this.formatCurrency(cambioCalculado)}</span></div>
                    </div>`;
                }

                const alertResult = await Swal.fire({
                    icon: 'success',
                    title: '¡Venta Registrada!',
                    html: alertHtml,
                    showCancelButton: true,
                    confirmButtonText: 'Imprimir Comprobante',
                    cancelButtonText: 'Continuar Vendiendo',
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#4f46e5',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });

                if (alertResult.isConfirmed) {
                    await this.openReceiptModal(newSale.venta_id, newSale);
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
            } finally {
                this.loading = false;
            }
        },

        // Carga y apertura del modal de comprobante (con soporte para datos precargados a 0ms)
        async openReceiptModal(ventaId, preloadedData = null) {
            // Asegurar datos de la empresa para el encabezado oficial
            if (!this.receiptEmpresa && typeof this.fetchEmpresa === 'function') {
                await this.fetchEmpresa();
            }

            // Si ya se tienen los datos precargados en memoria, abrir sin peticiones adicionales (0ms)
            if (preloadedData && preloadedData.venta_detalles) {
                this.receiptData = preloadedData;
                if (preloadedData.empresa) {
                    this.receiptEmpresa = preloadedData.empresa;
                }
                if (!this.receiptData.monto_recibido) {
                    try {
                        const cashMap = JSON.parse(sessionStorage.getItem('pos_cash_records') || '{}');
                        const rec = cashMap[this.receiptData.venta_id] || cashMap[this.receiptData.codigo_venta];
                        if (rec) {
                            this.receiptData.monto_recibido = rec.monto_recibido;
                            this.receiptData.cambio = rec.cambio;
                        }
                    } catch (e) {}
                }
                this.receiptAnulada = Boolean(preloadedData.estado === 0);
                this.showReceiptModal = true;
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
                return;
            }

            if (this.receiptData && (this.receiptData.venta_id == ventaId || this.receiptData.codigo_venta == ventaId)) {
                this.showReceiptModal = true;
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
                return;
            }

            if (!ventaId) return;
            this.loadingReceipt = true;
            this.showReceiptModal = true;
            this.receiptData = null;
            try {
                const res = await this.apiFetch(`/api/ventas/${ventaId}/comprobante`);
                if (!res.ok) {
                    throw new Error('No se pudo obtener el comprobante de la venta.');
                }
                const data = await res.json();
                this.receiptData = data.comprobante;
                if (data.empresa) {
                    this.receiptEmpresa = data.empresa;
                }
                try {
                    const cashMap = JSON.parse(sessionStorage.getItem('pos_cash_records') || '{}');
                    const rec = cashMap[this.receiptData.venta_id] || cashMap[this.receiptData.codigo_venta];
                    if (rec) {
                        this.receiptData.monto_recibido = rec.monto_recibido;
                        this.receiptData.cambio = rec.cambio;
                    }
                } catch (e) {}
                this.receiptAnulada = Boolean(data.anulada);
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            } catch (error) {
                this.showReceiptModal = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Comprobante',
                    text: error.message,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
            } finally {
                this.loadingReceipt = false;
            }
        },

        // Retorna el título descriptivo oficial de la operación para el comprobante / ticket
        getReceiptOperationTitle(receipt) {
            if (!receipt) return 'VENTA AL CONTADO';
            if (receipt.tipo_operacion) return receipt.tipo_operacion.toUpperCase();
            const metodo = (receipt.metodo_pago || '').trim().toLowerCase();
            if (metodo === 'transferencia') {
                return 'DEPÓSITO A CUENTA';
            } else if (metodo === 'tarjeta') {
                return 'PAGO CON TARJETA';
            } else if (metodo === 'efectivo') {
                return 'VENTA AL CONTADO';
            }
            return 'COMPROBANTE DE VENTA';
        },

        // Disparador de impresión nativa del sistema
        printReceipt() {
            window.print();
        },

        viewSaleDetails(sale) {
            this.selectedSale = sale;
            this.showSaleDetailModal = true;
        },

        async deleteSale(sale) {
            const usuariosActivos = (this.usuarios || []).filter(usuario => Number(usuario.estado) === 1);
            if (usuariosActivos.length === 0) {
                await Swal.fire({
                    icon: 'error',
                    title: 'No se puede anular',
                    text: 'No hay usuarios activos disponibles para registrar al responsable.'
                });
                return;
            }

            const result = await Swal.fire({
                title: '¿Anular venta?',
                text: `Se anulará la factura ${sale.codigo_venta}`,
                input: 'select',
                inputLabel: 'Usuario responsable de la anulación',
                inputOptions: Object.fromEntries(usuariosActivos.map(usuario => [
                    String(usuario.usuario_id), usuario.nombre_apellido
                ])),
                inputPlaceholder: 'Seleccioná al responsable',
                inputValidator: value => !value ? 'Seleccioná al usuario responsable.' : undefined,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#334155',
                confirmButtonText: 'Sí, anular',
                cancelButtonText: 'Cancelar',
                background: '#1e293b',
                color: '#fff'
            });

            if (!result.isConfirmed) return;

            try {
                const idUsuario = Number(result.value);
                if (!usuariosActivos.some(usuario => Number(usuario.usuario_id) === idUsuario)) {
                    throw new Error('Seleccioná un usuario activo para registrar la anulación.');
                }

                const res = await this.apiFetch(`/api/ventas/${sale.venta_id}`, {
                    method: 'DELETE',
                    body: JSON.stringify({ id_usuario: idUsuario })
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || data.success !== true) {
                    throw new Error(data.message || 'No se pudo anular la venta.');
                }
            } catch (error) {
                await Swal.fire({
                    title: 'No se pudo anular la venta',
                    text: error.message,
                    icon: 'error',
                    background: '#1e293b',
                    color: '#fff'
                });
                return;
            }

            try {
                await Promise.all([
                    this.fetchVentas(),
                    this.fetchProductos(),
                    this.fetchInventario(),
                    this.fetchBitacoras()
                ]);
            } catch {
                await Swal.fire({
                    title: 'Venta anulada',
                    text: 'La anulación se guardó, pero no se pudo actualizar la pantalla. Recargá la página.',
                    icon: 'warning'
                });
                return;
            }

            await Swal.fire({
                title: 'Venta anulada',
                icon: 'success',
                background: '#1e293b',
                color: '#fff'
            });
        },

        // Métodos de control y filtrado del Historial de Ventas (RF-21)
        clearVentaFilters() {
            this.searchVenta = '';
            this.ventaFechaDesde = '';
            this.ventaFechaHasta = '';
            this.ventaUsuarioFilter = '';
            this.ventaQuickRange = '';
        },

        getVentaLocalDate(dateStr) {
            if (!dateStr) return '';
            let normalized = String(dateStr).trim();
            // Normalizar fechas UTC sin sufijo Z (ej: "2026-09-30 01:35:00") a ISO UTC para interpretar en hora local
            if (/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?$/.test(normalized)) {
                normalized = normalized.replace(' ', 'T') + 'Z';
            }
            const d = new Date(normalized);
            if (isNaN(d.getTime())) {
                return normalized.split('T')[0].split(' ')[0];
            }
            const pad = n => String(n).padStart(2, '0');
            return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
        },

        isVentaQuickActive(range) {
            const now = new Date();
            const pad = n => String(n).padStart(2, '0');
            const toYmd = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
            const today = toYmd(now);

            if (range === 'hoy') {
                return Boolean(this.ventaFechaDesde && this.ventaFechaDesde === today && this.ventaFechaHasta === today);
            }
            if (range === '7dias') {
                const past = new Date();
                past.setDate(past.getDate() - 6);
                return Boolean(this.ventaFechaDesde === toYmd(past) && this.ventaFechaHasta === today);
            }
            if (range === 'mes') {
                const firstDay = toYmd(new Date(now.getFullYear(), now.getMonth(), 1));
                return Boolean(this.ventaFechaDesde === firstDay && this.ventaFechaHasta === today);
            }
            return false;
        },

        setVentaQuickDate(range) {
            const now = new Date();
            const pad = n => String(n).padStart(2, '0');
            const toYmd = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

            this.ventaQuickRange = range;

            if (range === 'hoy') {
                const todayStr = toYmd(now);
                this.ventaFechaDesde = todayStr;
                this.ventaFechaHasta = todayStr;
            } else if (range === '7dias') {
                const past = new Date();
                past.setDate(past.getDate() - 6);
                this.ventaFechaDesde = toYmd(past);
                this.ventaFechaHasta = toYmd(now);
            } else if (range === 'mes') {
                const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                this.ventaFechaDesde = toYmd(firstDay);
                this.ventaFechaHasta = toYmd(now);
            } else if (range === 'todos') {
                this.ventaFechaDesde = '';
                this.ventaFechaHasta = '';
                this.ventaQuickRange = '';
            }
        },

        // Métodos de Ventas en Espera / Cuentas Pendientes (Parked Orders - RF-16)
        async fetchVentasEspera() {
            try {
                this.loadingVentasEspera = true;
                const res = await this.apiFetch('/api/ventas-espera');
                if (res.ok) {
                    const json = await res.json();
                    this.ventasEspera = Array.isArray(json.data) ? json.data : [];
                }
            } catch (e) {
                console.error('Error cargando ventas en espera:', e);
            } finally {
                this.loadingVentasEspera = false;
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            }
        },

        openVentasEsperaModal() {
            this.fetchVentasEspera();
            this.showVentasEsperaModal = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        async parkCurrentSale() {
            if (this.cart.length === 0) return;

            const clienteId = this.posSale.id_cliente || (this.clientes[0] ? this.clientes[0].cliente_id : null);

            const payload = {
                id_usuario: this.currentUser?.usuario_id || (this.usuarios[0] ? this.usuarios[0].usuario_id : 1),
                id_cliente: clienteId,
                descuento: this.posSale.descuento_venta || 0,
                detalles: this.cart.map(i => ({
                    id_producto: i.id_producto,
                    cantidad: i.cantidad,
                    precio_unitario: i.precio_unitario,
                }))
            };

            this.loading = true;
            try {
                let res;
                // Si ya estábamos editando una orden en espera reanudada, actualizar en vez de duplicar
                if (this.resumedVentaEsperaId) {
                    res = await this.apiFetch(`/api/ventas-espera/${this.resumedVentaEsperaId}`, {
                        method: 'PUT',
                        body: JSON.stringify(payload)
                    });
                } else {
                    res = await this.apiFetch('/api/ventas-espera', {
                        method: 'POST',
                        body: JSON.stringify(payload)
                    });
                }

                const data = await res.json();
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Error al poner la venta en espera');
                }

                const nombreAsignado = data.data?.identificador_cuenta || 'Venta en Espera';

                this.resumedVentaEsperaId = null;
                this.clearCart();
                this.showCartDrawer = false;
                await this.fetchVentasEspera();

                Swal.fire({
                    icon: 'success',
                    title: '¡Venta en Espera!',
                    text: `La venta fue guardada exitosamente como "${nombreAsignado}".`,
                    timer: 2500,
                    showConfirmButton: false,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al pausar venta',
                    text: error.message,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
            } finally {
                this.loading = false;
            }
        },

        async resumeVentaEspera(venta) {
            if (this.cart.length > 0 && this.resumedVentaEsperaId !== venta.venta_espera_id) {
                const confirm = await Swal.fire({
                    icon: 'question',
                    title: '¿Reemplazar carrito actual?',
                    text: 'Tienes productos en el carrito. Si continúas, se reemplazarán por los productos de esta orden pausada.',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, reanudar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#f59e0b',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                if (!confirm.isConfirmed) return;
            }

            try {
                this.loading = true;
                const res = await this.apiFetch(`/api/ventas-espera/${venta.venta_espera_id}`);
                if (!res.ok) {
                    throw new Error('No se pudo cargar la venta en espera.');
                }
                const json = await res.json();
                const v = json.data;

                this.cart = (v.detalles || []).map(d => ({
                    id_producto: d.id_producto,
                    nombre_producto: d.producto?.nombre_producto || `Producto #${d.id_producto}`,
                    cantidad: Number(d.cantidad),
                    precio_unitario: Number(d.precio_unitario),
                    subtotal_venta_detalle: Number(d.subtotal),
                    existencia_bodega: Number(d.producto?.existencia_bodega || 0),
                }));

                if (v.id_cliente) {
                    this.posSale.id_cliente = v.id_cliente;
                }
                this.posSale.descuento_venta = Number(v.descuento || 0);
                this.posSale.tipo_descuento = 'monto';
                this.posSale.descuento_porcentaje = 0;
                this.posSale.monto_recibido = null;

                // Marcar como orden activa en el carrito sin borrar de la base de datos
                this.resumedVentaEsperaId = v.venta_espera_id;

                this.showVentasEsperaModal = false;
                this.showCartDrawer = true;

                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `Venta "${v.identificador_cuenta}" cargada al carrito`,
                    showConfirmButton: false,
                    timer: 2000,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
            } finally {
                this.loading = false;
            }
        },

        async discardVentaEspera(venta) {
            const confirm = await Swal.fire({
                icon: 'warning',
                title: '¿Descartar orden en espera?',
                text: `¿Estás seguro de descartar la venta "${venta.identificador_cuenta}"? Esta acción no se puede deshacer.`,
                showCancelButton: true,
                confirmButtonText: 'Sí, descartar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#ef4444',
                background: this.darkMode ? '#1e293b' : '#ffffff',
                color: this.darkMode ? '#fff' : '#0f172a'
            });

            if (!confirm.isConfirmed) return;

            try {
                this.loading = true;
                const res = await this.apiFetch(`/api/ventas-espera/${venta.venta_espera_id}`, {
                    method: 'DELETE'
                });
                if (!res.ok) {
                    throw new Error('Error al descartar la venta en espera.');
                }

                if (this.resumedVentaEsperaId === venta.venta_espera_id) {
                    this.resumedVentaEsperaId = null;
                }

                await this.fetchVentasEspera();

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'info',
                    title: `Venta "${venta.identificador_cuenta}" descartada`,
                    showConfirmButton: false,
                    timer: 2000,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
            } finally {
                this.loading = false;
            }
        }
    };
}
