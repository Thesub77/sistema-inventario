export function productosModule() {
    return {
        // Products state & filters
        searchProduct: '',
        filterCategory: '',
        filterEstado: '', // RF-05: Filtro por estado ('' = todos, 1 = activos, 0 = inactivos)
        showProductModal: false,
        isEditingProduct: false,
        productForm: {
            producto_id: null,
            id_categoria: '',
            codigo_producto: '',
            nombre_producto: '',
            descripcion_producto: '',
            costo_compra: 0,
            precio_venta: 0,
            existencia_bodega: 10,
            existencia_minima: 5,
            estado: 1,
        },

        // Stock adjustments state
        showStockModal: false,
        stockForm: {
            producto_id: null,
            nombre_producto: '',
            stock_anterior: 0,
            cantidad: 10,
            tipo_movimiento: 'Entrada por Compra',
        },

        // Product CRUD Methods
        openProductModal(product = null) {
            if (product) {
                this.isEditingProduct = true;
                this.productForm = {
                    ...product
                };
            } else {
                this.isEditingProduct = false;
                this.productForm = {
                    producto_id: null,
                    id_categoria: this.categorias[0] ? this.categorias[0].categoria_id : '',
                    codigo_producto: `PROD-${String(this.productos.length + 1).padStart(3, '0')}`,
                    nombre_producto: '',
                    descripcion_producto: '',
                    costo_compra: 0,
                    precio_venta: 0,
                    existencia_bodega: 10,
                    existencia_minima: 5,
                    estado: 1,
                };
            }
            this.showProductModal = true;
        },

        async saveProduct() {
            const url = this.isEditingProduct ?
                `/api/productos/${this.productForm.producto_id}` :
                '/api/productos';
            const method = this.isEditingProduct ? 'PUT' : 'POST';

            try {
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.productForm)
                });

                if (!res.ok) throw new Error('Error al guardar el producto');
                this.showProductModal = false;
                Swal.fire({
                    icon: 'success',
                    title: '¡Producto Guardado!',
                    background: '#1e293b',
                    color: '#fff'
                });
                await this.fetchProductos();
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message,
                    background: '#1e293b',
                    color: '#fff'
                });
            }
        },

        async deleteProduct(product) {
            const result = await Swal.fire({
                title: '¿Desactivar producto?',
                text: `El producto "${product.nombre_producto}" será desactivado del catálogo. Podrá reactivarse desde la lista de productos.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                confirmButtonText: 'Sí, desactivar',
                cancelButtonText: 'Cancelar',
                background: '#1e293b',
                color: '#fff'
            });

            if (result.isConfirmed) {
                await fetch(`/api/productos/${product.producto_id}/toggle-estado`, {
                    method: 'PATCH',
                    headers: { 'Accept': 'application/json' }
                });
                Swal.fire({
                    title: 'Producto desactivado',
                    text: 'El producto ya no será visible en el punto de venta.',
                    icon: 'success',
                    background: '#1e293b',
                    color: '#fff'
                });
                await this.fetchProductos();
            }
        },

        /**
         * RF-05: Alterna el estado de un producto entre Activo e Inactivo.
         */
        async toggleProductStatus(product) {
            const isActive = product.estado == 1;
            const action = isActive ? 'desactivar' : 'activar';
            const result = await Swal.fire({
                title: `¿${action.charAt(0).toUpperCase() + action.slice(1)} producto?`,
                html: isActive
                    ? `<p class="text-slate-300">El producto "<strong>${product.nombre_producto}</strong>" será <strong>ocultado</strong> del punto de venta.</p>`
                    : `<p class="text-slate-300">El producto "<strong>${product.nombre_producto}</strong>" volverá a estar <strong>disponible</strong> en ventas.</p>`,
                icon: isActive ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonColor: isActive ? '#f59e0b' : '#10b981',
                confirmButtonText: isActive ? 'Sí, desactivar' : 'Sí, activar',
                cancelButtonText: 'Cancelar',
                background: '#1e293b',
                color: '#fff'
            });

            if (result.isConfirmed) {
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    const res = await fetch(`/api/productos/${product.producto_id}/toggle-estado`, {
                        method: 'PATCH',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    });

                    if (!res.ok) {
                        const errData = await res.json().catch(() => ({}));
                        throw new Error(errData.message || 'Error al cambiar el estado del producto');
                    }

                    const data = await res.json();
                    
                    // Actualizar el estado en el array local de productos de inmediato
                    product.estado = data.producto ? data.producto.estado : (isActive ? 0 : 1);

                    Swal.fire({
                        icon: 'success',
                        title: product.estado == 1 ? '¡Producto Activado!' : 'Producto Desactivado',
                        text: product.estado == 1
                            ? 'El producto está disponible en el punto de venta.'
                            : 'El producto ha sido ocultado del punto de venta.',
                        background: '#1e293b',
                        color: '#fff'
                    });
                    await this.fetchProductos();
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message,
                        background: '#1e293b',
                        color: '#fff'
                    });
                }
            }
        },

        openStockModal(product) {
            this.stockForm = {
                producto_id: product.producto_id,
                nombre_producto: product.nombre_producto,
                stock_anterior: product.existencia_bodega,
                cantidad: 10,
                tipo_movimiento: 'Entrada por Compra'
            };
            this.showStockModal = true;
        },

        async saveStockAdjustment() {
            const isAdd = this.stockForm.tipo_movimiento.includes('Entrada') || this.stockForm.tipo_movimiento.includes('Compra');
            const newStock = isAdd ?
                this.stockForm.stock_anterior + this.stockForm.cantidad :
                Math.max(0, this.stockForm.stock_anterior - this.stockForm.cantidad);

            try {
                // Actualizar stock del producto
                await fetch(`/api/productos/${this.stockForm.producto_id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        existencia_bodega: newStock
                    })
                });

                // Registrar movimiento de inventario en bitácora/kardex
                await fetch('/api/movimientos-inventario', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id_producto: this.stockForm.producto_id,
                        id_usuario: this.usuarios[0] ? this.usuarios[0].usuario_id : 1,
                        tipo_movimiento: this.stockForm.tipo_movimiento,
                        cantidad_movimimiento: this.stockForm.cantidad,
                        stock_anterior_producto: this.stockForm.stock_anterior,
                        stock_resultante_producto: newStock,
                        fecha_movimiento: new Date().toISOString().slice(0, 19).replace('T', ' '),
                        estado: 1
                    })
                });

                // Actualizar stock localmente de inmediato
                const prod = this.productos.find(p => p.producto_id === this.stockForm.producto_id);
                if (prod) prod.existencia_bodega = newStock;

                this.showStockModal = false;
                await Promise.all([
                    this.fetchInventario(),
                    this.fetchBitacoras()
                ]);
                Swal.fire({
                    icon: 'success',
                    title: 'Inventario Actualizado',
                    background: '#1e293b',
                    color: '#fff'
                });
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message,
                    background: '#1e293b',
                    color: '#fff'
                });
            }
        }
    };
}
