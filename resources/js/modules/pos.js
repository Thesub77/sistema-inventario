export function posModule() {
    return {
        // POS State & Filters
        posSearch: '',
        posCategoryFilter: '',
        searchVenta: '',
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

            // Validación de identificador / referencia en transferencias
            if (this.posSale.metodo_pago === 'Transferencia' && (!this.posSale.referencia_transferencia || !this.posSale.referencia_transferencia.trim())) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Referencia Requerida',
                    text: 'Debes ingresar el número de referencia o voucher para pagos por transferencia.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
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

                // Descontar existencias localmente para respuesta visual instantánea (0ms)
                salePayload.detalles.forEach(d => {
                    const p = this.productos.find(prod => prod.producto_id === d.id_producto);
                    if (p) p.existencia_bodega = Math.max(0, p.existencia_bodega - d.cantidad);
                });

                this.clearCart();

                // Actualizar historial en memoria de inmediato (0ms)
                if (Array.isArray(this.ventas)) {
                    this.ventas.unshift(newSale);
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
        }
    };
}
