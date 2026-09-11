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
                Swal.fire({
                    icon: 'success',
                    title: '¡Venta Registrada!',
                    text: `Factura ${newSale.codigo_venta} emitida por C$ ${newSale.total_venta}`,
                    background: '#1e293b',
                    color: '#fff',
                    confirmButtonColor: '#4f46e5'
                });

                this.clearCart();
                this.refreshAll();
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
                this.refreshAll();
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
