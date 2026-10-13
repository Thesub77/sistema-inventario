export function productosModule() {
    return {
        // Products state & filters
        searchProduct: '',
        filterCategory: '',
        filterTipoProducto: '',
        showProductModal: false,
        isEditingProduct: false,
        comboNuevoComponenteId: '',
        comboNuevaCantidad: 1,
        productForm: {
            producto_id: null,
            id_categoria: '',
            codigo_producto: '',
            nombre_producto: '',
            tipo_producto: 'estandar',
            descripcion_producto: '',
            costo_compra: 0,
            precio_venta: 0,
            existencia_bodega: 10,
            existencia_minima: 5,
            estado: 1,
            componentes: [],
        },

        // Stock adjustments state
        showStockModal: false,
        stockForm: {
            producto_id: null,
            nombre_producto: '',
            stock_anterior: 0,
            costo_compra: 0,
            cantidad: 10,
            tipo_movimiento: 'Entrada por Compra',
            tipo_merma: 'Deterioro/Vencimiento',
            justificacion: '',
            proveedor_nombre: '',
            numero_factura_recibo: '',
        },

        // Kardex modal state
        showKardexModal: false,
        kardexSelectedProduct: null,
        kardexSelectedProductId: '',
        kardexFilterType: '',
        kardexSearch: '',

        // Helper methods for combo management
        setProductTipo(tipo) {
            this.productForm.tipo_producto = tipo;
            if (tipo === 'combo') {
                this.productForm.existencia_bodega = 0;
                if (!Array.isArray(this.productForm.componentes)) {
                    this.productForm.componentes = [];
                }
            }
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        get productosDisponiblesParaCombo() {
            return (this.productos || []).filter(p => {
                if (p.tipo_producto === 'combo') return false;
                if (this.isEditingProduct && p.producto_id === this.productForm.producto_id) return false;
                if (this.productForm.componentes && this.productForm.componentes.some(c => c.id_producto_componente === p.producto_id)) return false;
                return p.estado == 1;
            });
        },

        get costoTotalComboCalculado() {
            if (!this.productForm.componentes || this.productForm.componentes.length === 0) return 0;
            return this.productForm.componentes.reduce((acc, c) => {
                const prod = (this.productos || []).find(p => p.producto_id === c.id_producto_componente);
                const costo = prod ? Number(prod.costo_compra || 0) : 0;
                return acc + (costo * Number(c.cantidad || 1));
            }, 0);
        },

        getProductoNombre(id) {
            const prod = (this.productos || []).find(p => p.producto_id === id);
            return prod ? prod.nombre_producto : 'Producto #' + id;
        },

        getProductoCosto(id) {
            const prod = (this.productos || []).find(p => p.producto_id === id);
            return prod ? Number(prod.costo_compra || 0).toFixed(2) : '0.00';
        },

        agregarComponenteCombo() {
            if (!this.comboNuevoComponenteId) {
                this.notify('Seleccione un producto', 'Debe seleccionar un producto componente.', 'warning');
                return;
            }
            const cant = Math.max(1, parseInt(this.comboNuevaCantidad || 1));
            const prod = (this.productos || []).find(p => p.producto_id == this.comboNuevoComponenteId);
            if (!prod) return;

            if (!Array.isArray(this.productForm.componentes)) {
                this.productForm.componentes = [];
            }

            this.productForm.componentes.push({
                id_producto_componente: prod.producto_id,
                nombre_producto: prod.nombre_producto,
                cantidad: cant,
            });

            this.comboNuevoComponenteId = '';
            this.comboNuevaCantidad = 1;

            if (!this.productForm.costo_compra || Number(this.productForm.costo_compra) === 0) {
                this.productForm.costo_compra = Math.round(this.costoTotalComboCalculado * 100) / 100;
            }

            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        removerComponenteCombo(index) {
            this.productForm.componentes.splice(index, 1);
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        aplicarCostoSugeridoCombo() {
            this.productForm.costo_compra = Math.round(this.costoTotalComboCalculado * 100) / 100;
            if (Number(this.productForm.precio_venta) < Number(this.productForm.costo_compra)) {
                this.productForm.precio_venta = this.productForm.costo_compra;
            }
        },

        // Product CRUD Methods
        openProductModal(product = null) {
            this.comboNuevoComponenteId = '';
            this.comboNuevaCantidad = 1;
            if (product) {
                this.isEditingProduct = true;
                const items = product.combo_items || product.comboItems || [];
                this.productForm = {
                    ...product,
                    tipo_producto: product.tipo_producto || 'estandar',
                    componentes: items.map(ci => ({
                        id_producto_componente: ci.id_producto_componente,
                        nombre_producto: ci.componente ? ci.componente.nombre_producto : this.getProductoNombre(ci.id_producto_componente),
                        cantidad: ci.cantidad,
                    })),
                };
            } else {
                this.isEditingProduct = false;
                this.productForm = {
                    producto_id: null,
                    id_categoria: this.categorias[0] ? this.categorias[0].categoria_id : '',
                    codigo_producto: `PROD-${String(this.productos.length + 1).padStart(3, '0')}`,
                    nombre_producto: '',
                    tipo_producto: 'estandar',
                    descripcion_producto: '',
                    costo_compra: 0,
                    precio_venta: 0,
                    existencia_bodega: 10,
                    existencia_minima: 5,
                    estado: 1,
                    componentes: [],
                };
            }
            this.showProductModal = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        async saveProduct() {
            if (this.productForm.tipo_producto === 'combo') {
                if (!this.productForm.componentes || this.productForm.componentes.length === 0) {
                    this.notify('Componentes Requeridos', 'Debe agregar al menos un producto componente para el combo.', 'warning');
                    return;
                }
                this.productForm.existencia_bodega = 0;
            }

            const url = this.isEditingProduct ?
                `/api/productos/${this.productForm.producto_id}` :
                '/api/productos';
            const method = this.isEditingProduct ? 'PUT' : 'POST';

            try {
                const res = await this.apiFetch(url, {
                    method,
                    body: JSON.stringify(this.productForm)
                });

                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    throw new Error(err.message || 'Error al guardar el producto');
                }
                this.showProductModal = false;
                this.notify('¡Producto Guardado!', `El producto "${this.productForm.nombre_producto}" se guardó correctamente.`, 'success');
                await this.fetchProductos();
            } catch (error) {
                this.notify('Error al guardar producto', error.message, 'error');
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
                background: this.darkMode ? '#1e293b' : '#ffffff',
                color: this.darkMode ? '#fff' : '#0f172a'
            });

            if (result.isConfirmed) {
                await this.apiFetch(`/api/productos/${product.producto_id}`, {
                    method: 'DELETE'
                });
                this.notify('Producto Eliminado', `"${product.nombre_producto}" fue eliminado.`, 'success');
                await this.fetchProductos();
            }
        },

        openStockModal(product) {
            this.stockForm = {
                producto_id: product.producto_id,
                nombre_producto: product.nombre_producto,
                stock_anterior: Number(product.existencia_bodega ?? product.stockActual ?? 0),
                costo_compra: Number(product.costo_compra || 0),
                cantidad: 10,
                tipo_movimiento: 'Entrada por Compra',
                tipo_merma: 'Deterioro/Vencimiento',
                justificacion: '',
                proveedor_nombre: '',
                numero_factura_recibo: '',
            };
            if (typeof this.fetchProveedores === 'function' && (!this.proveedores || this.proveedores.length === 0)) {
                this.fetchProveedores().catch(() => {});
            }
            this.showStockModal = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        async saveStockAdjustment() {
            if (!this.stockForm.producto_id || this.stockForm.cantidad <= 0) return;

            const isAjuste = this.stockForm.tipo_movimiento === 'Ajuste Manual';
            const isSalida = this.stockForm.tipo_movimiento === 'Salida por Merma' || this.stockForm.tipo_movimiento === 'Salida';
            const isEntrada = this.stockForm.tipo_movimiento.includes('Entrada') || this.stockForm.tipo_movimiento.includes('Compra');

            // Validación previa en cliente de justificación requerida para salidas y ajustes
            if ((isAjuste || isSalida) && (!this.stockForm.justificacion || !this.stockForm.justificacion.trim())) {
                this.notify('Justificación Requerida', 'Debe ingresar una justificación breve para registrar la salida o ajuste.', 'warning');
                return;
            }

            // Validación previa para evitar salidas que excedan las existencias actuales
            if (isSalida && Number(this.stockForm.cantidad) > this.stockForm.stock_anterior) {
                this.notify('Existencia Insuficiente', `No se puede dar salida a ${this.stockForm.cantidad} unidades porque solo hay ${this.stockForm.stock_anterior} en bodega.`, 'warning');
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

            // Inclusión opcional de proveedor y factura para movimientos de Entrada / Compra (RF-10 / Issue #46)
            if (this.stockForm.proveedor_nombre && this.stockForm.proveedor_nombre.trim()) {
                payload.proveedor_nombre = this.stockForm.proveedor_nombre.trim().slice(0, 128);
            }
            if (this.stockForm.numero_factura_recibo && this.stockForm.numero_factura_recibo.trim()) {
                payload.numero_factura_recibo = this.stockForm.numero_factura_recibo.trim().slice(0, 64);
            }

            // Inclusión de causa tipificada para salidas por merma (RF-48)
            if (this.stockForm.tipo_movimiento === 'Salida por Merma') {
                payload.tipo_merma = this.stockForm.tipo_merma || 'Deterioro/Vencimiento';
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
                    this.fetchBitacoras ? this.fetchBitacoras().catch(() => {}) : Promise.resolve(),
                    this.fetchDashboardData ? this.fetchDashboardData().catch(() => {}) : Promise.resolve()
                ]);

                // Notificación no intrusiva con temporizador automático
                this.notify('Stock Actualizado', `Existencias actualizadas a ${newStock} unidades.`, 'success');
            } catch (error) {
                this.notify('Error de Inventario', error.message, 'error');
            }
        },

        // Kardex Modal Methods
        async openKardexModal(product = null) {
            this.kardexSelectedProduct = product || null;
            this.kardexSelectedProductId = product ? String(product.producto_id) : '';
            this.kardexFilterType = '';
            this.kardexSearch = '';
            this.showKardexModal = true;
            if (typeof this.fetchInventario === 'function' && (!Array.isArray(this.movimientosInventario) || this.movimientosInventario.length === 0)) {
                await this.fetchInventario();
            }
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        closeKardexModal() {
            this.showKardexModal = false;
            this.kardexSelectedProduct = null;
            this.kardexSelectedProductId = '';
            this.kardexFilterType = '';
            this.kardexSearch = '';
        },

        onKardexProductChange() {
            if (!this.kardexSelectedProductId) {
                this.kardexSelectedProduct = null;
            } else {
                const found = (this.productos || []).find(p => String(p.producto_id) === String(this.kardexSelectedProductId));
                this.kardexSelectedProduct = found || null;
            }
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        openStockFromKardex() {
            if (this.kardexSelectedProduct && this.kardexSelectedProduct.tipo_producto !== 'combo') {
                const prod = this.kardexSelectedProduct;
                this.closeKardexModal();
                this.openStockModal(prod);
            }
        },

        get filteredKardexMovimientos() {
            let list = Array.isArray(this.movimientosInventario) ? this.movimientosInventario : [];
            if (this.kardexSelectedProduct) {
                const prodId = Number(this.kardexSelectedProduct.producto_id);
                list = list.filter(m => Number(m.id_producto) === prodId || (m.producto && Number(m.producto.producto_id) === prodId));
            }
            if (this.kardexFilterType) {
                const filter = this.kardexFilterType.toLowerCase();
                list = list.filter(m => (m.tipo_movimiento || '').toLowerCase().includes(filter));
            }
            if (this.kardexSearch && this.kardexSearch.trim()) {
                const q = this.kardexSearch.toLowerCase().trim();
                list = list.filter(m => {
                    const prodName = m.producto ? (m.producto.nombre_producto || '').toLowerCase() : '';
                    const prodCod = m.producto ? (m.producto.codigo_producto || '').toLowerCase() : '';
                    const provName = (m.proveedor_nombre || '').toLowerCase();
                    const factura = (m.numero_factura_recibo || '').toLowerCase();
                    const tipo = (m.tipo_movimiento || '').toLowerCase();
                    const usuario = m.usuario ? (m.usuario.nombre_apellido || '').toLowerCase() : '';
                    const merma = (m.tipo_merma || '').toLowerCase();
                    return prodName.includes(q) || prodCod.includes(q) || provName.includes(q) || factura.includes(q) || tipo.includes(q) || usuario.includes(q) || merma.includes(q);
                });
            }
            return list;
        },

        get kardexStats() {
            const list = this.filteredKardexMovimientos;
            let entradas = 0;
            let mermas = 0;
            let ventas = 0;
            let ajustes = 0;
            let costoMermas = 0;
            for (const m of list) {
                const tipo = (m.tipo_movimiento || '').toLowerCase();
                const cant = Number(m.cantidad_movimimiento || m.cantidad_movimiento || 0);
                if (tipo.includes('entrada') || tipo.includes('inicial')) {
                    entradas += cant;
                } else if (tipo.includes('merma')) {
                    mermas += cant;
                    if (m.costo_total_perdida) {
                        costoMermas += Number(m.costo_total_perdida);
                    }
                } else if (tipo.includes('venta')) {
                    ventas += cant;
                } else if (tipo.includes('ajuste')) {
                    ajustes += 1;
                }
            }
            return {
                totalRegistros: list.length,
                entradas,
                mermas,
                ventas,
                ajustes,
                costoMermas
            };
        }
    };
}
