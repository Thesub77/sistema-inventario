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

        // Estado del modal de animación de facturación en curso
        isProcessingBilling: false,
        billingProgress: 15,
        billingStatusText: 'Procesando transacción...',
        billingCandidateCode: '',

        // Estado del comprobante imprimible
        showReceiptModal: false,
        receiptData: null,
        receiptEmpresa: null,
        receiptAnulada: false,
        loadingReceipt: false,

        // Estado y control de ventas en espera (Parked Orders)
        ventasEspera: [],
        showVentasEsperaModal: false,
        loadingVentasEspera: false,
        resumedVentaEsperaId: null,

        // POS Cart Operations
        addToCart(product) {
            if (product.existencia_bodega <= 0) {
                this.notify('Sin Stock', 'El producto no cuenta con existencias disponibles en bodega.', 'warning');
                return;
            }

            const existing = this.cart.find(i => i.id_producto === product.producto_id);
            if (existing) {
                if (existing.cantidad + 1 > product.existencia_bodega) {
                    this.notify('Límite de Stock', `Solo hay ${product.existencia_bodega} unidades disponibles.`, 'warning');
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
                this.notify('Límite de Stock', `Solo hay ${product.existencia_bodega} unidades disponibles.`, 'warning');
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
                this.notify('Caja Cerrada', 'No se puede facturar sin turno activo. Abre una caja primero.', 'warning', 3000);
                return;
            }

            // Validación de voucher (Tarjeta) y referencia (Transferencia) con longitud mínima
            if (this.posSale.metodo_pago === 'Transferencia' || this.posSale.metodo_pago === 'Tarjeta') {
                const ref = (this.posSale.referencia_transferencia || '').trim();
                const isTarjeta = this.posSale.metodo_pago === 'Tarjeta';
                const nombreCampo = isTarjeta ? 'número de voucher / autorización' : 'número de transferencia / referencia';
                const minLength = 4;

                if (!ref) {
                    this.notify(isTarjeta ? 'Voucher Requerido' : 'Referencia Requerida', `Debes ingresar el ${nombreCampo} para procesar el pago con ${this.posSale.metodo_pago.toLowerCase()}.`, 'warning');
                    return;
                }

                if (ref.length < minLength) {
                    this.notify('Mínimo Requerido No Alcanzado', `El ${nombreCampo} debe contener al menos ${minLength} caracteres.`, 'warning');
                    return;
                }
            }

            // Validación obligatoria de efectivo recibido para evitar errores humanos de cálculo
            if (this.posSale.metodo_pago === 'Efectivo') {
                const montoRecibidoNum = Number(this.posSale.monto_recibido);
                if (!this.posSale.monto_recibido || isNaN(montoRecibidoNum) || montoRecibidoNum <= 0) {
                    this.notify('Efectivo Requerido', 'Debes ingresar el monto entregado por el cliente (o pulsar "Paga Exacto").', 'warning');
                    return;
                }

                if (montoRecibidoNum < this.cartTotal) {
                    const faltante = this.cartTotal - montoRecibidoNum;
                    this.notify('Efectivo Insuficiente', `El cliente entregó ${this.formatCurrency(montoRecibidoNum)}, pero el total es ${this.formatCurrency(this.cartTotal)}. Faltan ${this.formatCurrency(faltante)}.`, 'warning');
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
                id_caja: this.turnoActivo ? this.turnoActivo.id_caja : null,
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

            this.billingCandidateCode = candidateCode;
            this.billingProgress = 15;
            this.billingStatusText = 'Procesando transacción...';
            this.isProcessingBilling = true;
            this.loading = true;

            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });

            // Progreso dinámico y reactivo en pantalla mientras el backend emite la factura
            let progressInterval = setInterval(() => {
                if (this.billingProgress < 90) {
                    this.billingProgress += 15;
                    if (this.billingProgress >= 30 && this.billingProgress < 55) {
                        this.billingStatusText = 'Verificando existencias...';
                    } else if (this.billingProgress >= 55 && this.billingProgress < 75) {
                        this.billingStatusText = 'Registrando pago...';
                    } else if (this.billingProgress >= 75) {
                        this.billingStatusText = 'Generando comprobante fiscal...';
                    }
                }
            }, 200);

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
                clearInterval(progressInterval);
                this.billingProgress = 100;
                this.billingStatusText = '¡Factura emitida exitosamente!';
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

                // Notificación sutil tipo tarjeta en esquina superior derecha
                const cambioInfo = (salePayload.metodo_pago === 'Efectivo' && cambioCalculado > 0)
                    ? ` | Cambio: ${this.formatCurrency(cambioCalculado)}`
                    : '';
                this.notify('¡Venta Registrada!', `Factura ${newSale.codigo_venta} por ${this.formatCurrency(newSale.total_venta)}${cambioInfo}`, 'success', 3500);

                // Cerrar modal de animación de emisión y abrir automáticamente la ventana del comprobante/factura
                this.isProcessingBilling = false;
                await this.openReceiptModal(newSale.venta_id, newSale);

            } catch (error) {
                clearInterval(progressInterval);
                this.isProcessingBilling = false;
                this.notify('Error al procesar venta', error.message, 'error', 4000);
            } finally {
                clearInterval(progressInterval);
                this.isProcessingBilling = false;
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
                this.notify('Error de Comprobante', error.message, 'error');
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
                this.notify('No se puede anular', 'No hay usuarios activos disponibles para registrar al responsable.', 'error');
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
                background: this.darkMode ? '#1e293b' : '#ffffff',
                color: this.darkMode ? '#fff' : '#0f172a'
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
                this.notify('No se pudo anular la venta', error.message, 'error');
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
                this.notify('Venta Anulada', 'La anulación se guardó, pero no se pudo actualizar la pantalla.', 'warning');
                return;
            }

            this.notify('Venta Anulada', `La factura ${sale.codigo_venta} fue anulada correctamente.`, 'success');
        },

        // Métodos de control y filtrado del Historial de Ventas
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

        // Métodos de Ventas en Espera / Cuentas Pendientes (Parked Orders)
        async fetchVentasEspera() {
            try {
                this.loadingVentasEspera = true;
                const cajaId = this.turnoActivo?.id_caja || '';
                const url = cajaId ? `/api/ventas-espera?id_caja=${cajaId}` : '/api/ventas-espera';
                const res = await this.apiFetch(url);
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
            if (!this.turnoActivo) {
                this.notify('Caja Cerrada', 'Debes tener un turno de caja abierto para poner ventas en espera.', 'warning');
                return;
            }
            if (this.cart.length === 0) return;

            const clienteId = this.posSale.id_cliente || (this.clientes[0] ? this.clientes[0].cliente_id : null);

            const payload = {
                id_usuario: this.currentUser?.usuario_id || (this.usuarios[0] ? this.usuarios[0].usuario_id : 1),
                id_caja: this.turnoActivo?.id_caja || null,
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

                this.notify('¡Venta en Espera!', `Guardada exitosamente como "${nombreAsignado}".`, 'success', 2500);
            } catch (error) {
                this.notify('Error al pausar venta', error.message, 'error');
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

                this.notify('Venta Cargada', `"${v.identificador_cuenta}" cargada al carrito`, 'success', 2000);
            } catch (error) {
                this.notify('Error al cargar orden', error.message, 'error');
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

                this.notify('Venta Descartada', `"${venta.identificador_cuenta}" fue descartada.`, 'info', 2000);
            } catch (error) {
                this.notify('Error al descartar', error.message, 'error');
            } finally {
                this.loading = false;
            }
        }
    };
}
