/**
 * ==============================================================================
 * FACTURASTOCK PRO - CAPA DE LÓGICA FRONTEND
 * Archivo: public/js/app.js
 * Descripción: Controlador principal modular para la interfaz reactiva con Alpine.js
 * ==============================================================================
 */

// 1. Inicialización Inmediata de Tema (Anti-parpadeo FOUC)
(function() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'light') {
        document.documentElement.classList.remove('dark');
    } else {
        document.documentElement.classList.add('dark');
    }
})();

// 2. Configuración de Tailwind CSS
window.tailwind = window.tailwind || {};
window.tailwind.config = {
    darkMode: 'class',
    theme: {
        extend: {
            fontFamily: {
                sans: ['Plus Jakarta Sans', 'sans-serif'],
                display: ['Outfit', 'sans-serif'],
            },
            colors: {
                brand: {
                    50: '#eef2ff',
                    100: '#e0e7ff',
                    400: '#818cf8',
                    500: '#6366f1',
                    600: '#4f46e5',
                    700: '#4338ca',
                },
                dark: {
                    800: '#1e293b',
                    850: '#172033',
                    900: '#0f172a',
                    950: '#090d16',
                }
            }
        }
    }
};

// 3. Módulo de Tema (Modo Claro / Modo Oscuro)
function themeModule() {
    return {
        darkMode: true,

        initTheme() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'light') {
                this.darkMode = false;
                document.documentElement.classList.remove('dark');
            } else if (savedTheme === 'dark') {
                this.darkMode = true;
                document.documentElement.classList.add('dark');
            } else {
                this.darkMode = document.documentElement.classList.contains('dark');
            }
        },

        toggleTheme() {
            this.darkMode = !this.darkMode;
            if (this.darkMode) {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            }
            this.$nextTick(() => {
                if (window.lucide) {
                    window.lucide.createIcons();
                }
            });
        }
    };
}

// 4. Módulo de Autenticación
function authModule() {
    return {
        isAuthenticated: false,
        isLoggingIn: false,
        showPassword: false,
        authToken: null,
        currentUser: {
            usuario_id: null,
            nombre_apellido: 'Invitado',
            nombre_usuario: '',
            rol: '',
            permisos: []
        },
        loginForm: {
            nombre_usuario: '',
            contrasenia_usuario: '',
        },
        loginError: '',

        initAuth() {
            const token = localStorage.getItem('auth_token');
            const savedUser = localStorage.getItem('auth_user');
            if (token && savedUser) {
                try {
                    this.authToken = token;
                    this.currentUser = JSON.parse(savedUser);
                    this.isAuthenticated = true;
                } catch (e) {
                    this.logout();
                }
            }
        },

        async login() {
            if (this.isLoggingIn) return;
            this.isLoggingIn = true;
            this.loginError = '';

            try {
                const res = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.loginForm)
                });

                const data = await res.json();

                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Credenciales incorrectas');
                }

                this.authToken = data.token;
                this.currentUser = data.usuario;
                this.isAuthenticated = true;
                localStorage.setItem('auth_token', data.token);
                localStorage.setItem('auth_user', JSON.stringify(data.usuario));

                this.loginForm.contrasenia_usuario = '';
                this.loginError = '';

                Swal.fire({
                    icon: 'success',
                    title: `¡Bienvenido, ${data.usuario.nombre_apellido}!`,
                    text: 'Has iniciado sesión correctamente.',
                    timer: 2000,
                    showConfirmButton: false,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });

                // Cargar pestaña inicial
                await this.loadTab(this.currentTab, true);
            } catch (error) {
                this.loginError = error.message;
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Autenticación',
                    text: error.message,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
            } finally {
                this.isLoggingIn = false;
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            }
        },

        async logout() {
            if (this.authToken) {
                try {
                    await fetch('/api/auth/logout', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${this.authToken}`
                        }
                    });
                } catch (e) {
                    console.error('Error al cerrar sesión:', e);
                }
            }

            this.authToken = null;
            this.currentUser = {
                usuario_id: null,
                nombre_apellido: 'Invitado',
                nombre_usuario: '',
                rol: '',
                permisos: []
            };
            this.isAuthenticated = false;
            localStorage.removeItem('auth_token');
            localStorage.removeItem('auth_user');

            Swal.fire({
                icon: 'info',
                title: 'Sesión Finalizada',
                text: 'Has cerrado sesión con éxito.',
                timer: 1500,
                showConfirmButton: false,
                background: this.darkMode ? '#1e293b' : '#ffffff',
                color: this.darkMode ? '#fff' : '#0f172a'
            });

            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        }
    };
}

// 5. Módulo de Utilidades
function utilsModule() {
    return {
        formatCurrency(amount) {
            return 'C$ ' + Number(amount || 0).toLocaleString('es-NI', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        formatDate(dateStr) {
            if (!dateStr) return 'N/A';
            const d = new Date(dateStr);
            return d.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        async apiFetch(url, options = {}) {
            const opts = { ...options };
            opts.headers = opts.headers || {};
            if (!opts.headers['Accept']) {
                opts.headers['Accept'] = 'application/json';
            }
            if (!opts.headers['Content-Type'] && !(opts.body instanceof FormData)) {
                opts.headers['Content-Type'] = 'application/json';
            }
            const token = localStorage.getItem('auth_token');
            if (token) {
                opts.headers['Authorization'] = `Bearer ${token}`;
            }
            const res = await fetch(url, opts);
            if (res.status === 401 && this.isAuthenticated) {
                console.warn('Sesión expirada o no autorizada (401). Cerrando sesión...');
                this.logout();
            }
            return res;
        }
    };
}

// 6. Módulo de Categorías
function categoriasModule() {
    return {
        showCategoryModal: false,
        isEditingCategory: false,
        isSavingCategory: false,
        categoryForm: {
            categoria_id: null,
            codigo_categoria: '',
            nombre_categoria: '',
            descripcion_categoria: '',
            estado: 1,
        },

        openCategoryModal(cat = null) {
            if (cat) {
                this.isEditingCategory = true;
                this.categoryForm = { ...cat };
            } else {
                this.isEditingCategory = false;
                this.categoryForm = {
                    categoria_id: null,
                    codigo_categoria: `CAT-${String(this.categorias.length + 1).padStart(2, '0')}`,
                    nombre_categoria: '',
                    descripcion_categoria: '',
                    estado: 1
                };
            }
            this.showCategoryModal = true;
        },

        async saveCategory() {
            if (this.isSavingCategory) return;
            this.isSavingCategory = true;

            const url = this.isEditingCategory ?
                `/api/categorias/${this.categoryForm.categoria_id}` :
                '/api/categorias';
            const method = this.isEditingCategory ? 'PUT' : 'POST';

            try {
                const res = await this.apiFetch(url, {
                    method,
                    body: JSON.stringify(this.categoryForm)
                });

                if (!res.ok) {
                    const errData = await res.json().catch(() => ({}));
                    let errorMsg = errData.message || 'Error al procesar la categoría';
                    if (errData.errors) {
                        errorMsg = Object.values(errData.errors).flat().join('<br>');
                    }
                    throw new Error(errorMsg);
                }

                this.showCategoryModal = false;
                Swal.fire({
                    icon: 'success',
                    title: '¡Categoría guardada!',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                await this.fetchCategorias();
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al guardar',
                    html: error.message,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
            } finally {
                this.isSavingCategory = false;
            }
        },

        async deleteCategory(cat) {
            const result = await Swal.fire({
                title: '¿Eliminar categoría?',
                text: `Se eliminará "${cat.nombre_categoria}"`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                background: this.darkMode ? '#1e293b' : '#ffffff',
                color: this.darkMode ? '#fff' : '#0f172a'
            });

            if (result.isConfirmed) {
                await this.apiFetch(`/api/categorias/${cat.categoria_id}`, {
                    method: 'DELETE'
                });
                await this.fetchCategorias();
            }
        }
    };
}

// 7. Módulo de Productos
function productosModule() {
    return {
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

        showStockModal: false,
        stockForm: {
            producto_id: null,
            nombre_producto: '',
            stock_anterior: 0,
            cantidad: 10,
            tipo_movimiento: 'Entrada por Compra',
        },

        openProductModal(product = null) {
            if (product) {
                this.isEditingProduct = true;
                this.productForm = { ...product };
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
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                await this.fetchProductos();
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
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
                background: this.darkMode ? '#1e293b' : '#ffffff',
                color: this.darkMode ? '#fff' : '#0f172a'
            });

            if (result.isConfirmed) {
                await this.apiFetch(`/api/productos/${product.producto_id}`, {
                    method: 'DELETE'
                });
                Swal.fire({
                    title: 'Eliminado',
                    icon: 'success',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
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
                await this.apiFetch(`/api/productos/${this.stockForm.producto_id}`, {
                    method: 'PUT',
                    body: JSON.stringify({ existencia_bodega: newStock })
                });

                await this.apiFetch('/api/movimientos-inventario', {
                    method: 'POST',
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
            }
        }
    };
}

// 8. Módulo de Clientes
function clientesModule() {
    return {
        searchCliente: '',
        showCustomerModal: false,
        isEditingCustomer: false,
        customerForm: {
            cliente_id: null,
            codigo_cliente: '',
            nombre_apellido_cliente: '',
            telefono_cliente: '',
            estado: 1,
        },

        openCustomerModal(customer = null) {
            if (customer) {
                this.isEditingCustomer = true;
                this.customerForm = { ...customer };
            } else {
                this.isEditingCustomer = false;
                this.customerForm = {
                    cliente_id: null,
                    codigo_cliente: `CLI-${String(this.clientes.length + 1).padStart(3, '0')}`,
                    nombre_apellido_cliente: '',
                    telefono_cliente: '',
                    estado: 1
                };
            }
            this.showCustomerModal = true;
        },

        async saveCustomer() {
            const url = this.isEditingCustomer ?
                `/api/clientes/${this.customerForm.cliente_id}` :
                '/api/clientes';
            const method = this.isEditingCustomer ? 'PUT' : 'POST';

            try {
                const res = await this.apiFetch(url, {
                    method,
                    body: JSON.stringify(this.customerForm)
                });

                if (!res.ok) throw new Error('Error al guardar el cliente');
                this.showCustomerModal = false;
                Swal.fire({
                    icon: 'success',
                    title: 'Cliente guardado',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                await this.fetchClientes();
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
            }
        },

        async deleteCustomer(c) {
            const result = await Swal.fire({
                title: '¿Eliminar cliente?',
                text: `Se eliminará "${c.nombre_apellido_cliente}"`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                background: this.darkMode ? '#1e293b' : '#ffffff',
                color: this.darkMode ? '#fff' : '#0f172a'
            });

            if (result.isConfirmed) {
                await this.apiFetch(`/api/clientes/${c.cliente_id}`, {
                    method: 'DELETE'
                });
                await this.fetchClientes();
            }
        }
    };
}

// 9. Módulo de Usuarios
function usuariosModule() {
    return {
        showUserModal: false,
        isEditingUser: false,
        userForm: {
            usuario_id: null,
            id_rol: '',
            nombre_apellido: '',
            nombre_usuario: '',
            contrasenia_usuario: '',
            estado: 1,
        },

        openUserModal(user = null) {
            if (user) {
                this.isEditingUser = true;
                this.userForm = {
                    ...user,
                    contrasenia_usuario: ''
                };
            } else {
                this.isEditingUser = false;
                this.userForm = {
                    usuario_id: null,
                    id_rol: this.roles[0] ? this.roles[0].rol_id : '',
                    nombre_apellido: '',
                    nombre_usuario: '',
                    contrasenia_usuario: '',
                    fecha_registro: new Date().toISOString().slice(0, 10),
                    estado: 1
                };
            }
            this.showUserModal = true;
        },

        async saveUser() {
            const url = this.isEditingUser ?
                `/api/usuarios/${this.userForm.usuario_id}` :
                '/api/usuarios';
            const method = this.isEditingUser ? 'PUT' : 'POST';

            const payload = { ...this.userForm };
            if (this.isEditingUser && !payload.contrasenia_usuario) {
                delete payload.contrasenia_usuario;
            }

            try {
                const res = await this.apiFetch(url, {
                    method,
                    body: JSON.stringify(payload)
                });

                if (!res.ok) throw new Error('Error al guardar el usuario');
                this.showUserModal = false;
                Swal.fire({
                    icon: 'success',
                    title: 'Usuario guardado',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                await this.fetchUsuarios();
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
            }
        },

        async deleteUser(u) {
            const result = await Swal.fire({
                title: '¿Eliminar usuario?',
                text: `Se eliminará "${u.nombre_usuario}"`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                background: this.darkMode ? '#1e293b' : '#ffffff',
                color: this.darkMode ? '#fff' : '#0f172a'
            });

            if (result.isConfirmed) {
                await this.apiFetch(`/api/usuarios/${u.usuario_id}`, {
                    method: 'DELETE'
                });
                await this.fetchUsuarios();
            }
        }
    };
}

// 10. Módulo de Punto de Venta (POS)
function posModule() {
    return {
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

        addToCart(product) {
            if (product.existencia_bodega <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sin Stock',
                    text: 'El producto no cuenta con existencias disponibles en bodega.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
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
                        background: this.darkMode ? '#1e293b' : '#ffffff',
                        color: this.darkMode ? '#fff' : '#0f172a'
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
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
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
                id_usuario: this.currentUser.usuario_id || (this.usuarios[0] ? this.usuarios[0].usuario_id : 1),
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
                const res = await this.apiFetch('/api/ventas', {
                    method: 'POST',
                    body: JSON.stringify(salePayload)
                });

                if (!res.ok) {
                    const err = await res.json();
                    throw new Error(err.message || 'Error al procesar la venta');
                }

                const responseData = await res.json();
                const newSale = responseData.venta;
                Swal.fire({
                    icon: 'success',
                    title: '¡Venta Registrada!',
                    text: `Factura ${newSale.codigo_venta} emitida por C$ ${newSale.total_venta}`,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a',
                    confirmButtonColor: '#4f46e5'
                });

                salePayload.detalles.forEach(d => {
                    const p = this.productos.find(prod => prod.producto_id === d.id_producto);
                    if (p) p.existencia_bodega = Math.max(0, p.existencia_bodega - d.cantidad);
                });

                this.clearCart();

                await Promise.all([
                    this.fetchVentas(),
                    this.fetchInventario(),
                    this.fetchBitacoras()
                ]);
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
                    text: 'No hay usuarios activos disponibles para registrar al responsable.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
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
                await Swal.fire({
                    title: 'No se pudo anular la venta',
                    text: error.message,
                    icon: 'error',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
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
                    icon: 'warning',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
            }

            await Swal.fire({
                title: 'Venta anulada',
                icon: 'success',
                background: this.darkMode ? '#1e293b' : '#ffffff',
                color: this.darkMode ? '#fff' : '#0f172a'
            });
        }
    };
}

// 11. Función Principal del Aplicativo
function app() {
    return {
        // Estado General
        currentTab: 'dashboard',
        loading: false,

        // Datos de Entidades
        productos: [],
        categorias: [],
        clientes: [],
        usuarios: [],
        roles: [],
        ventas: [],
        cajas: [],
        cajaMovimientos: [],
        movimientosInventario: [],
        bitacoras: [],

        // Navegación
        navItems: [
            { id: 'dashboard', label: 'Dashboard', icon: 'layout-dashboard' },
            { id: 'pos', label: 'Punto de Venta (POS)', icon: 'shopping-cart' },
            { id: 'productos', label: 'Productos & Stock', icon: 'package', badge: () => this.lowStockProducts.length },
            { id: 'categorias', label: 'Categorías', icon: 'tags' },
            { id: 'ventas', label: 'Historial de Ventas', icon: 'receipt' },
            { id: 'clientes', label: 'Clientes', icon: 'users' },
            { id: 'caja', label: 'Cajas & Arqueos', icon: 'wallet' },
            { id: 'inventario', label: 'Kardex / Movimientos', icon: 'arrow-left-right' },
            { id: 'usuarios', label: 'Usuarios & Roles', icon: 'user-cog' },
            { id: 'bitacora', label: 'Bitácora Auditoría', icon: 'shield-check' },
        ],

        // Propiedades Computadas
        get currentTabTitle() {
            const found = this.navItems.find(i => i.id === this.currentTab);
            return found ? found.label : this.currentTab;
        },

        get currentTabIcon() {
            const found = this.navItems.find(i => i.id === this.currentTab);
            return found ? found.icon : 'layers';
        },

        get lowStockProducts() {
            return this.productos.filter(p => Number(p.existencia_bodega) <= Number(p.existencia_minima));
        },

        get stats() {
            const totalVentasMonto = this.ventas.reduce((sum, v) => sum + Number(v.total_venta || 0), 0);
            const totalUnidades = this.productos.reduce((sum, p) => sum + Number(p.existencia_bodega || 0), 0);
            return { totalVentasMonto, totalUnidades };
        },

        get filteredProducts() {
            return this.productos.filter(p => {
                const matchesSearch = !this.searchProduct ||
                    p.nombre_producto.toLowerCase().includes(this.searchProduct.toLowerCase()) ||
                    p.codigo_producto.toLowerCase().includes(this.searchProduct.toLowerCase());
                const matchesCat = !this.filterCategory || p.id_categoria == this.filterCategory;
                return matchesSearch && matchesCat;
            });
        },

        get posFilteredProducts() {
            return this.productos.filter(p => {
                const matchesSearch = !this.posSearch ||
                    p.nombre_producto.toLowerCase().includes(this.posSearch.toLowerCase()) ||
                    p.codigo_producto.toLowerCase().includes(this.posSearch.toLowerCase());
                const matchesCat = !this.posCategoryFilter || p.id_categoria == this.posCategoryFilter;
                return matchesSearch && matchesCat;
            });
        },

        get filteredVentas() {
            return this.ventas.filter(v => {
                if (!this.searchVenta) return true;
                const term = this.searchVenta.toLowerCase();
                const codeMatch = v.codigo_venta.toLowerCase().includes(term);
                const clientMatch = v.cliente && v.cliente.nombre_apellido_cliente.toLowerCase().includes(term);
                return codeMatch || clientMatch;
            });
        },

        get filteredClientes() {
            return this.clientes.filter(c => {
                if (!this.searchCliente) return true;
                const term = this.searchCliente.toLowerCase();
                const nameMatch = c.nombre_apellido_cliente.toLowerCase().includes(term);
                const codeMatch = c.codigo_cliente && c.codigo_cliente.toLowerCase().includes(term);
                return nameMatch || codeMatch;
            });
        },

        get cartSubtotal() {
            return this.cart.reduce((sum, item) => sum + item.subtotal_venta_detalle, 0);
        },

        get cartTotal() {
            const total = this.cartSubtotal - (this.posSale.descuento_venta || 0);
            return Math.max(0, total);
        },

        // Lazy Loading de Pestañas
        loadedTabs: [],

        async loadTab(tab, force = false) {
            if (!this.isAuthenticated) return;
            if (!force && this.loadedTabs.includes(tab)) {
                return;
            }

            this.loading = true;
            try {
                switch (tab) {
                    case 'dashboard':
                        await Promise.all([
                            this.fetchProductos(),
                            this.fetchVentas(),
                            this.fetchBitacoras()
                        ]);
                        break;
                    case 'pos':
                        await Promise.all([
                            this.fetchProductos(),
                            this.fetchCategorias(),
                            this.fetchClientes()
                        ]);
                        break;
                    case 'productos':
                        await Promise.all([
                            this.fetchProductos(),
                            this.fetchCategorias()
                        ]);
                        break;
                    case 'categorias':
                        await this.fetchCategorias();
                        break;
                    case 'ventas':
                    case 'caja':
                        await this.fetchVentas();
                        break;
                    case 'clientes':
                        await this.fetchClientes();
                        break;
                    case 'inventario':
                        await Promise.all([
                            this.fetchInventario(),
                            this.fetchProductos()
                        ]);
                        break;
                    case 'usuarios':
                        await this.fetchUsuarios();
                        break;
                    case 'bitacora':
                        await this.fetchBitacoras();
                        break;
                }

                if (!this.loadedTabs.includes(tab)) {
                    this.loadedTabs.push(tab);
                }
            } catch (error) {
                console.error(`Error cargando la pestaña ${tab}:`, error);
            } finally {
                this.loading = false;
                this.$nextTick(() => {
                    if (window.lucide) {
                        window.lucide.createIcons();
                    }
                });
            }
        },

        refreshCurrentTab() {
            return this.loadTab(this.currentTab, true);
        },

        // Inicialización
        initApp() {
            this.initTheme();
            this.initAuth();

            if (this.isAuthenticated) {
                this.loadTab(this.currentTab);
            }

            this.$watch('currentTab', (newTab) => {
                if (this.isAuthenticated) {
                    this.loadTab(newTab);
                    this.$nextTick(() => {
                        if (window.lucide) {
                            window.lucide.createIcons();
                        }
                    });
                }
            });
        },

        // Cargas de Datos Asíncronas
        async fetchProductos() {
            try {
                const res = await this.apiFetch('/api/productos');
                if (res.ok) {
                    const data = await res.json();
                    this.productos = Array.isArray(data) ? data : [];
                }
            } catch (e) {
                console.error('Error cargando productos:', e);
            }
        },

        async fetchCategorias() {
            try {
                const res = await this.apiFetch('/api/categorias');
                if (res.ok) {
                    const data = await res.json();
                    this.categorias = Array.isArray(data) ? data : [];
                }
            } catch (e) {
                console.error('Error cargando categorías:', e);
            }
        },

        async fetchClientes() {
            try {
                const res = await this.apiFetch('/api/clientes');
                if (res.ok) {
                    const data = await res.json();
                    this.clientes = Array.isArray(data) ? data : [];
                    if (this.clientes.length > 0 && (!this.posSale.id_cliente || !this.clientes.some(c => c.cliente_id == this.posSale.id_cliente))) {
                        this.posSale.id_cliente = this.clientes[0].cliente_id;
                    }
                }
            } catch (e) {
                console.error('Error cargando clientes:', e);
            }
        },

        async fetchUsuarios() {
            try {
                const [usrRes, rolRes] = await Promise.all([
                    this.apiFetch('/api/usuarios'),
                    this.apiFetch('/api/roles')
                ]);
                if (usrRes.ok) {
                    const usrData = await usrRes.json();
                    this.usuarios = Array.isArray(usrData) ? usrData : [];
                }
                if (rolRes.ok) {
                    const rolData = await rolRes.json();
                    this.roles = Array.isArray(rolData) ? rolData : [];
                }
            } catch (e) {
                console.error('Error cargando usuarios:', e);
            }
        },

        async fetchVentas() {
            try {
                const [venRes, cajRes, movCajRes] = await Promise.all([
                    this.apiFetch('/api/ventas'),
                    this.apiFetch('/api/cajas'),
                    this.apiFetch('/api/caja-movimientos-venta')
                ]);
                if (venRes.ok) {
                    const venData = await venRes.json();
                    this.ventas = Array.isArray(venData) ? venData : [];
                }
                if (cajRes.ok) {
                    const cajData = await cajRes.json();
                    this.cajas = Array.isArray(cajData) ? cajData : [];
                }
                if (movCajRes.ok) {
                    const movData = await movCajRes.json();
                    this.cajaMovimientos = Array.isArray(movData) ? movData : [];
                }
            } catch (e) {
                console.error('Error cargando ventas y cajas:', e);
            }
        },

        async fetchInventario() {
            try {
                const res = await this.apiFetch('/api/movimientos-inventario');
                if (res.ok) {
                    const data = await res.json();
                    this.movimientosInventario = Array.isArray(data) ? data : [];
                }
            } catch (e) {
                console.error('Error cargando movimientos de inventario:', e);
            }
        },

        async fetchBitacoras() {
            try {
                const res = await this.apiFetch('/api/bitacoras');
                if (res.ok) {
                    const data = await res.json();
                    this.bitacoras = Array.isArray(data) ? data : [];
                }
            } catch (e) {
                console.error('Error cargando bitácora:', e);
            }
        },

        async refreshAll() {
            this.loadedTabs = [];
            await this.loadTab(this.currentTab, true);
        },

        // Modulos Desacoplados
        ...posModule(),
        ...productosModule(),
        ...categoriasModule(),
        ...clientesModule(),
        ...usuariosModule(),
        ...utilsModule(),
        ...themeModule(),
        ...authModule(),
    };
}

// Exponer la función app globalmente para Alpine.js
window.app = app;
