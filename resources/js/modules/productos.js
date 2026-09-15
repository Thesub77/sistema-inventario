export function productosModule() {
    return {
        // Products state & filters
        searchProduct: '',
        filterCategory: '',
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
                title: '¿Eliminar producto?',
                text: `Se eliminará "${product.nombre_producto}" del catálogo.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                background: '#1e293b',
                color: '#fff'
            });

            if (result.isConfirmed) {
                await fetch(`/api/productos/${product.producto_id}`, {
                    method: 'DELETE'
                });
                Swal.fire({
                    title: 'Eliminado',
                    icon: 'success',
                    background: '#1e293b',
                    color: '#fff'
                });
                await this.fetchProductos();
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
