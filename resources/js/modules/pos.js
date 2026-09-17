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
            descuento_venta: 0,
        },
        showSaleDetailModal: false,
        selectedSale: null,

        // POS Cart Operations
        addToCart(product) {
            // RF-05: Verificar que el producto esté activo
            if (product.estado != 1) {
                Swal.fire({
                    icon: 'error',
                    title: 'Producto Inactivo',
                    text: 'Este producto está desactivado y no puede agregarse a la venta.',
                    background: '#1e293b',
                    color: '#fff'
                });
                return;
            }

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
                });
            }
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
        },

        async processSale() {
            if (this.cart.length === 0) return;

            const codeNum = String(this.ventas.length + 1).padStart(4, '0');
            const salePayload = {
                id_usuario: this.usuarios[0] ? this.usuarios[0].usuario_id : 1,
                id_cliente: this.posSale.id_cliente || (this.clientes[0] ? this.clientes[0].cliente_id : null),
                codigo_venta: `FAC-2026-${codeNum}`,
                metodo_pago: this.posSale.metodo_pago,
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
                const res = await fetch('/api/ventas', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(salePayload)
                });

                if (!res.ok) {
                    const err = await res.json();
                    throw new Error(err.message || 'Error al procesar la venta');
                }

                const newSale = await res.json();

                // Descontar existencias localmente para respuesta visual instantánea (0ms)
                salePayload.detalles.forEach(d => {
                    const p = this.productos.find(prod => prod.producto_id === d.id_producto);
                    if (p) p.existencia_bodega = Math.max(0, p.existencia_bodega - d.cantidad);
                });

                this.clearCart();

                // Sincronizar en segundo plano solo ventas, inventario y bitácora
                await Promise.all([
                    this.fetchVentas(),
                    this.fetchInventario(),
                    this.fetchBitacoras()
                ]);

                // RF-21: Mensaje de éxito con opción inmediata de imprimir comprobante
                const result = await Swal.fire({
                    icon: 'success',
                    title: '¡Venta Registrada!',
                    html: `<p class="text-slate-300">Factura <strong>${newSale.codigo_venta}</strong> emitida con éxito por <strong>${this.formatCurrency(newSale.total_venta)}</strong>.</p><p class="text-xs text-slate-400 mt-2">¿Desea generar e imprimir el comprobante de venta?</p>`,
                    background: '#1e293b',
                    color: '#fff',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#475569',
                    confirmButtonText: '🖨️ Imprimir Comprobante',
                    cancelButtonText: 'Cerrar'
                });

                if (result.isConfirmed) {
                    this.printSale(newSale);
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message,
                    background: '#1e293b',
                    color: '#fff'
                });
            } finally {
                this.loading = false;
            }
        },

        viewSaleDetails(sale) {
            this.selectedSale = sale;
            this.showSaleDetailModal = true;
        },

        /**
         * RF-21: Generar e imprimir comprobante de venta (formato ticket / PDF)
         */
        printSale(sale) {
            if (!sale) return;

            // Rellenar cabecera e información del ticket
            const codElem = document.getElementById('print-codigo-venta');
            const fechaElem = document.getElementById('print-fecha-venta');
            const clienteElem = document.getElementById('print-cliente-venta');
            const usuarioElem = document.getElementById('print-usuario-venta');
            const metodoElem = document.getElementById('print-metodo-pago');
            const subtotalElem = document.getElementById('print-subtotal-venta');
            const descElem = document.getElementById('print-descuento-venta');
            const totalElem = document.getElementById('print-total-venta');
            const tbodyElem = document.getElementById('print-items-tbody');

            if (codElem) codElem.textContent = sale.codigo_venta || 'N/A';
            if (fechaElem) fechaElem.textContent = this.formatDate(sale.fecha_hora_venta);
            if (clienteElem) clienteElem.textContent = sale.cliente ? sale.cliente.nombre_apellido_cliente : 'Consumidor Final';
            if (usuarioElem) usuarioElem.textContent = sale.usuario ? sale.usuario.nombre_apellido : 'Cajero / Vendedor';
            if (metodoElem) metodoElem.textContent = sale.metodo_pago || 'Efectivo';
            if (subtotalElem) subtotalElem.textContent = this.formatCurrency(sale.subtotal_venta);
            if (descElem) descElem.textContent = this.formatCurrency(sale.descuento_venta || 0);
            if (totalElem) totalElem.textContent = this.formatCurrency(sale.total_venta);

            // Rellenar filas de productos
            if (tbodyElem) {
                tbodyElem.innerHTML = '';
                const detalles = sale.venta_detalles || [];
                detalles.forEach(item => {
                    const row = document.createElement('tr');
                    const nombre = item.producto ? item.producto.nombre_producto : `Producto #${item.id_producto}`;
                    const precio = this.formatCurrency(item.precio_unitario);
                    const subtotal = this.formatCurrency(item.subtotal_venta_detalle);

                    row.innerHTML = `
                        <td>${item.cantidad}</td>
                        <td>
                            <div>${nombre}</div>
                            <div style="font-size: 9px; color: #555;">${precio} c/u</div>
                        </td>
                        <td style="text-align: right; font-weight: bold;">${subtotal}</td>
                    `;
                    tbodyElem.appendChild(row);
                });
            }

            // Lanzar el diálogo de impresión nativo del navegador (impresora física o Guardar como PDF)
            setTimeout(() => {
                window.print();
            }, 150);
        },

        async deleteSale(sale) {
            const result = await Swal.fire({
                title: '¿Anular venta?',
                text: `Se anulará la factura ${sale.codigo_venta}`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#334155',
                confirmButtonText: 'Sí, anular',
                cancelButtonText: 'Cancelar',
                background: '#1e293b',
                color: '#fff'
            });

            if (result.isConfirmed) {
                await fetch(`/api/ventas/${sale.venta_id}`, {
                    method: 'DELETE'
                });
                await Promise.all([
                    this.fetchVentas(),
                    this.fetchProductos(),
                    this.fetchInventario(),
                    this.fetchBitacoras()
                ]);
                Swal.fire({
                    title: 'Venta anulada',
                    icon: 'success',
                    background: '#1e293b',
                    color: '#fff'
                });
            }
        }
    };
}
