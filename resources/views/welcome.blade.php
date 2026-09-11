<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-900 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>FacturaStock Pro - Sistema de Inventario y Facturación</title>
    
    <!-- Google Fonts: Inter & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        tailwind.config = {
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
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }
        .glass-panel {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.07);
        }
        .glow-brand {
            box-shadow: 0 0 25px -5px rgba(99, 102, 241, 0.35);
        }
    </style>
</head>
<body class="h-full bg-dark-950 font-sans antialiased selection:bg-brand-500 selection:text-white" x-data="app()" x-init="initApp()">

    <!-- Main Container -->
    <div class="flex h-screen overflow-hidden">

        <!-- Sidebar -->
        <aside class="w-64 flex-shrink-0 bg-dark-900 border-r border-slate-800/80 flex flex-col justify-between transition-all duration-300 z-30">
            <div>
                <!-- Brand Logo -->
                <div class="h-16 flex items-center px-6 gap-3 border-b border-slate-800">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 to-indigo-400 flex items-center justify-center text-white shadow-lg shadow-brand-500/30">
                        <i data-lucide="boxes" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h1 class="font-display font-bold text-base tracking-wide text-white leading-none">Factura<span class="text-brand-400">Stock</span></h1>
                        <span class="text-[11px] text-slate-400 font-medium">Gestión & Facturación</span>
                    </div>
                </div>

                <!-- Navigation Links -->
                <nav class="p-3 space-y-1">
                    <template x-for="item in navItems" :key="item.id">
                        <button @click="currentTab = item.id" 
                                :class="currentTab === item.id 
                                    ? 'bg-gradient-to-r from-brand-600/90 to-brand-700 text-white shadow-md shadow-brand-600/20 font-semibold' 
                                    : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 font-medium'"
                                class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm transition-all duration-150">
                            <i :data-lucide="item.icon" class="w-4 h-4"></i>
                            <span x-text="item.label"></span>
                            <span x-show="item.badge && item.badge() > 0" 
                                  x-text="item.badge ? item.badge() : ''" 
                                  class="ml-auto px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-rose-500 text-white"></span>
                        </button>
                    </template>
                </nav>
            </div>

            <!-- User Info / Footer -->
            <div class="p-4 border-t border-slate-800/80 bg-dark-950/40">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center text-brand-400 font-bold text-sm">
                        DQ
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-xs font-semibold text-slate-200 truncate">Diego Quiroz</p>
                        <p class="text-[11px] text-emerald-400 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            Administrador
                        </p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- Top Header -->
            <header class="h-16 bg-dark-900/80 backdrop-blur border-b border-slate-800 flex items-center justify-between px-6 z-20">
                <div class="flex items-center gap-4">
                    <h2 class="font-display font-bold text-xl text-white capitalize flex items-center gap-2">
                        <i :data-lucide="currentTabIcon" class="w-5 h-5 text-brand-400"></i>
                        <span x-text="currentTabTitle"></span>
                    </h2>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Quick New Sale Action -->
                    <button @click="currentTab = 'pos'" class="flex items-center gap-2 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white text-xs font-semibold px-3.5 py-2 rounded-lg shadow-md shadow-emerald-600/20 transition-all">
                        <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                        <span>Punto de Venta (POS)</span>
                    </button>

                    <!-- Quick New Product -->
                    <button @click="openProductModal()" class="flex items-center gap-2 bg-brand-600 hover:bg-brand-500 text-white text-xs font-semibold px-3.5 py-2 rounded-lg shadow-md shadow-brand-600/20 transition-all">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span>Nuevo Producto</span>
                    </button>

                    <!-- Refresh Data -->
                    <button @click="refreshAll()" title="Recargar Datos" class="p-2 text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 rounded-lg transition-all">
                        <i data-lucide="refresh-cw" class="w-4 h-4" :class="{'animate-spin': loading}"></i>
                    </button>
                </div>
            </header>

            <!-- Dynamic Views Body -->
            <main class="flex-1 overflow-y-auto p-6 bg-dark-950/90">

                <!-- 1. TAB: DASHBOARD -->
                @include('sistema.dashboardView')

                <!-- 2. TAB: PRODUCTOS E INVENTARIO -->
                @include('productos.productoListView')

                <!-- 3. TAB: PUNTO DE VENTA (POS & FACTURACIÓN) -->
                @include('ventas.ventaView')

                <!-- 4. TAB: HISTORIAL DE VENTAS -->
                @include('ventas.ventaHistorialView')

                <!-- 5. TAB: CATEGORÍAS -->
                @include('categorias.categoriaView')

                <!-- 6. TAB: CLIENTES -->
                @include('clientes.clienteView')

                <!-- 7. TAB: CAJA Y MOVIMIENTOS -->
                @include('cajas.cajasView')

                <!-- 8. TAB: MOVIMIENTOS DE INVENTARIO -->
                @include('productos.inventarioView')

                <!-- 9. TAB: USUARIOS Y ROLES -->
                @include('usuarios.usuarioView')

                <!-- 10. TAB: BITÁCORA DE AUDITORÍA -->
                @include('sistema.bitacoraView')

                

    <!-- MODAL: PRODUCTO (CREAR / EDITAR) -->
    @include('productos.productoForm')

    <!-- MODAL: AJUSTE DE STOCK -->
    @include('productos.ajusteStockView')

    <!-- MODAL: CATEGORÍA (CREAR / EDITAR) -->
    @include('categorias.categoriaForm')

    <!-- MODAL: CLIENTE (CREAR / EDITAR) -->
    @include('clientes.clienteForm')

    <!-- MODAL: USUARIO (CREAR / EDITAR) -->
    @include('usuarios.usuarioForm')

    <!-- MODAL: DETALLE DE FACTURA / VENTA -->
    @include('ventas.ventaForm')

    <!-- Alpine.js Application Logic -->
    <script>
        function app() {
            return {
                currentTab: 'dashboard',
                loading: false,

                // Entities Data
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

                // Filters & Search
                searchProduct: '',
                filterCategory: '',
                searchVenta: '',
                searchCliente: '',
                posSearch: '',
                posCategoryFilter: '',

                // POS State
                cart: [],
                posSale: {
                    id_cliente: null,
                    metodo_pago: 'Efectivo',
                    descuento_venta: 0,
                },

                // Modals
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
                    existencia_bodega: 0,
                    existencia_minima: 10,
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

                showCustomerModal: false,
                isEditingCustomer: false,
                customerForm: {
                    cliente_id: null,
                    codigo_cliente: '',
                    nombre_apellido_cliente: '',
                    telefono_cliente: '',
                    estado: 1,
                },

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

                showSaleDetailModal: false,
                selectedSale: null,

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

                initApp() {
                    this.refreshAll();
                    this.$watch('currentTab', () => {
                        this.$nextTick(() => lucide.createIcons());
                    });
                },

                async refreshAll() {
                    this.loading = true;
                    try {
                        const [prodRes, catRes, cliRes, usrRes, rolRes, venRes, cajRes, movCajRes, movInvRes, bitRes] = await Promise.all([
                            fetch('/api/productos').then(r => r.json()),
                            fetch('/api/categorias').then(r => r.json()),
                            fetch('/api/clientes').then(r => r.json()),
                            fetch('/api/usuarios').then(r => r.json()),
                            fetch('/api/roles').then(r => r.json()),
                            fetch('/api/ventas').then(r => r.json()),
                            fetch('/api/cajas').then(r => r.json()),
                            fetch('/api/caja-movimientos-venta').then(r => r.json()),
                            fetch('/api/movimientos-inventario').then(r => r.json()),
                            fetch('/api/bitacoras').then(r => r.json())
                        ]);

                        this.productos = prodRes;
                        this.categorias = catRes;
                        this.clientes = cliRes;
                        this.usuarios = usrRes;
                        this.roles = rolRes;
                        this.ventas = venRes;
                        this.cajas = cajRes;
                        this.cajaMovimientos = movCajRes;
                        this.movimientosInventario = movInvRes;
                        this.bitacoras = bitRes;

                        if (this.clientes.length > 0) {
                            if (!this.posSale.id_cliente || !this.clientes.some(c => c.cliente_id == this.posSale.id_cliente)) {
                                this.posSale.id_cliente = this.clientes[0].cliente_id;
                            }
                        }
                    } catch (error) {
                        console.error('Error cargando datos:', error);
                    } finally {
                        this.loading = false;
                        this.$nextTick(() => lucide.createIcons());
                    }
                },

                // --- POS Cart Operations -------
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
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
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
                        await fetch(`/api/ventas/${sale.venta_id}`, { method: 'DELETE' });
                        this.refreshAll();
                        Swal.fire({ title: 'Venta anulada', icon: 'success', background: '#1e293b', color: '#fff' });
                    }
                },

                // --- Product CRUD ---
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
                    const url = this.isEditingProduct 
                        ? `/api/productos/${this.productForm.producto_id}` 
                        : '/api/productos';
                    const method = this.isEditingProduct ? 'PUT' : 'POST';

                    try {
                        const res = await fetch(url, {
                            method,
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify(this.productForm)
                        });

                        if (!res.ok) throw new Error('Error al guardar el producto');

                        this.showProductModal = false;
                        this.refreshAll();
                        Swal.fire({
                            icon: 'success',
                            title: '¡Producto Guardado!',
                            background: '#1e293b',
                            color: '#fff'
                        });
                    } catch (error) {
                        Swal.fire({ icon: 'error', title: 'Error', text: error.message, background: '#1e293b', color: '#fff' });
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
                        await fetch(`/api/productos/${product.producto_id}`, { method: 'DELETE' });
                        this.refreshAll();
                        Swal.fire({ title: 'Eliminado', icon: 'success', background: '#1e293b', color: '#fff' });
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
                    const newStock = isAdd 
                        ? this.stockForm.stock_anterior + this.stockForm.cantidad 
                        : Math.max(0, this.stockForm.stock_anterior - this.stockForm.cantidad);

                    try {
                        // Update product stock
                        await fetch(`/api/productos/${this.stockForm.producto_id}`, {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ existencia_bodega: newStock })
                        });

                        // Create inventory movement
                        await fetch('/api/movimientos-inventario', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
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

                        this.showStockModal = false;
                        this.refreshAll();
                        Swal.fire({ icon: 'success', title: 'Inventario Actualizado', background: '#1e293b', color: '#fff' });
                    } catch (error) {
                        Swal.fire({ icon: 'error', title: 'Error', text: error.message, background: '#1e293b', color: '#fff' });
                    }
                },

                // --- Category CRUD ---
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
                    // Evita envíos duplicados si ya hay una petición en curso (doble clic o red lenta)
                    if (this.isSavingCategory) return;
                    this.isSavingCategory = true;

                    const url = this.isEditingCategory 
                        ? `/api/categorias/${this.categoryForm.categoria_id}` 
                        : '/api/categorias';
                    const method = this.isEditingCategory ? 'PUT' : 'POST';

                    try {
                        const res = await fetch(url, {
                            method,
                            headers: { 
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
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
                        await this.refreshAll();
                        Swal.fire({ icon: 'success', title: '¡Categoría guardada!', background: '#1e293b', color: '#fff' });
                    } catch (error) {
                        Swal.fire({ 
                            icon: 'error', 
                            title: 'Error al guardar', 
                            html: error.message, 
                            background: '#1e293b', 
                            color: '#fff' 
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
                        background: '#1e293b',
                        color: '#fff'
                    });

                    if (result.isConfirmed) {
                        await fetch(`/api/categorias/${cat.categoria_id}`, { method: 'DELETE' });
                        this.refreshAll();
                    }
                },

                // --- Customer CRUD ---
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
                    const url = this.isEditingCustomer 
                        ? `/api/clientes/${this.customerForm.cliente_id}` 
                        : '/api/clientes';
                    const method = this.isEditingCustomer ? 'PUT' : 'POST';

                    await fetch(url, {
                        method,
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.customerForm)
                    });

                    this.showCustomerModal = false;
                    this.refreshAll();
                    Swal.fire({ icon: 'success', title: 'Cliente guardado', background: '#1e293b', color: '#fff' });
                },

                async deleteCustomer(c) {
                    const result = await Swal.fire({
                        title: '¿Eliminar cliente?',
                        text: `Se eliminará "${c.nombre_apellido_cliente}"`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#e11d48',
                        background: '#1e293b',
                        color: '#fff'
                    });

                    if (result.isConfirmed) {
                        await fetch(`/api/clientes/${c.cliente_id}`, { method: 'DELETE' });
                        this.refreshAll();
                    }
                },

                // --- User CRUD ---
                openUserModal(user = null) {
                    if (user) {
                        this.isEditingUser = true;
                        this.userForm = { ...user, contrasenia_usuario: '' };
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
                    const url = this.isEditingUser 
                        ? `/api/usuarios/${this.userForm.usuario_id}` 
                        : '/api/usuarios';
                    const method = this.isEditingUser ? 'PUT' : 'POST';

                    const payload = { ...this.userForm };
                    if (this.isEditingUser && !payload.contrasenia_usuario) {
                        delete payload.contrasenia_usuario;
                    }

                    await fetch(url, {
                        method,
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });

                    this.showUserModal = false;
                    this.refreshAll();
                    Swal.fire({ icon: 'success', title: 'Usuario guardado', background: '#1e293b', color: '#fff' });
                },

                async deleteUser(u) {
                    const result = await Swal.fire({
                        title: '¿Eliminar usuario?',
                        text: `Se eliminará "${u.nombre_usuario}"`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#e11d48',
                        background: '#1e293b',
                        color: '#fff'
                    });

                    if (result.isConfirmed) {
                        await fetch(`/api/usuarios/${u.usuario_id}`, { method: 'DELETE' });
                        this.refreshAll();
                    }
                },

                // Utilities
                formatCurrency(amount) {
                    return 'C$ ' + Number(amount || 0).toLocaleString('es-NI', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                formatDate(dateStr) {
                    if (!dateStr) return 'N/A';
                    const d = new Date(dateStr);
                    return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                }
            }
        }
    </script>
</body>
</html>
