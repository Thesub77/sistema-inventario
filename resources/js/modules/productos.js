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
            justificacion: '',
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
                const res = await this.apiFetch(url, {
                    method,
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
                await this.apiFetch(`/api/productos/${product.producto_id}`, {
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
                stock_anterior: Number(product.existencia_bodega ?? product.stockActual ?? 0),
                cantidad: 10,
                tipo_movimiento: 'Entrada por Compra',
                justificacion: '',
            };
            this.showStockModal = true;
        },

        async saveStockAdjustment() {
            if (!this.stockForm.producto_id || this.stockForm.cantidad <= 0) return;

            const isAjuste = this.stockForm.tipo_movimiento === 'Ajuste Manual';
            const isSalida = this.stockForm.tipo_movimiento === 'Salida por Merma' || this.stockForm.tipo_movimiento === 'Salida';
            const isEntrada = this.stockForm.tipo_movimiento.includes('Entrada') || this.stockForm.tipo_movimiento.includes('Compra');

            // Validación previa en cliente de justificación requerida para salidas y ajustes
            if ((isAjuste || isSalida) && (!this.stockForm.justificacion || !this.stockForm.justificacion.trim())) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Justificación Requerida',
                    text: 'Debe ingresar una justificación breve para registrar la salida o ajuste (máximo 90 caracteres).',
                    background: '#1e293b',
                    color: '#fff'
                });
                return;
            }

            // Validación previa para evitar salidas que excedan las existencias actuales
            if (isSalida && Number(this.stockForm.cantidad) > this.stockForm.stock_anterior) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Existencia Insuficiente',
                    text: `No se puede dar salida a ${this.stockForm.cantidad} unidades porque solo hay ${this.stockForm.stock_anterior} en bodega.`,
                    background: '#1e293b',
                    color: '#fff'
                });
                return;
            }

            // Cálculo del nuevo stock previsto
            let newStock = this.stockForm.stock_anterior;
            if (isAjuste) {
                newStock = Number(this.stockForm.cantidad);
            } else if (isEntrada) {
                newStock = this.stockForm.stock_anterior + Number(this.stockForm.cantidad);
            } else {
                newStock = Math.max(0, this.stockForm.stock_anterior - Number(this.stockForm.cantidad));
            }

            // Construcción del payload para el registro transaccional en Kardex
            const payload = {
                id_producto: this.stockForm.producto_id,
                id_usuario: this.currentUser?.usuario_id || (this.usuarios[0] ? this.usuarios[0].usuario_id : 1),
                tipo_movimiento: this.stockForm.tipo_movimiento,
                cantidad_movimiento: Number(this.stockForm.cantidad),
                fecha_movimiento: new Date().toISOString().slice(0, 19).replace('T', ' '),
            };

            // Campo obligatorio específico para el tipo de movimiento Ajuste Manual
            if (isAjuste) {
                payload.stock_resultante_producto = newStock;
            }

            // Asignación de la justificación si fue provista
            if (this.stockForm.justificacion && this.stockForm.justificacion.trim()) {
                payload.justificacion = this.stockForm.justificacion.trim().slice(0, 90);
            }

            // Cierre inmediato del modal para agilizar la interacción visual (0ms)
            this.showStockModal = false;

            // Actualización optimista del estado local en memoria
            const prod = this.productos.find(p => p.producto_id === this.stockForm.producto_id);
            const stockRespaldo = prod ? prod.existencia_bodega : this.stockForm.stock_anterior;
            if (prod) prod.existencia_bodega = newStock;

            try {
                // Envío directo al endpoint de movimientos de inventario que ejecuta el lockForUpdate
                const res = await this.apiFetch('/api/movimientos-inventario', {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });

                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    // En caso de error se revierte el stock optimista al valor original
                    if (prod) prod.existencia_bodega = stockRespaldo;
                    throw new Error(err.message || 'Error al procesar el ajuste de inventario.');
                }

                // Sincronización concurrente de entidades para actualizar existencias y vistas
                await Promise.all([
                    this.fetchProductos(),
                    this.fetchInventario(),
                    this.fetchBitacoras()
                ]);

                // Notificación no intrusiva con temporizador automático
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Stock Actualizado',
                    text: `Existencias actualizadas a ${newStock} unidades.`,
                    timer: 2500,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    background: '#1e293b',
                    color: '#fff'
                });
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Inventario',
                    text: error.message,
                    background: '#1e293b',
                    color: '#fff'
                });
            }
        }
    };
}
