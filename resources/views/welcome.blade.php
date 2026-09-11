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

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Vite JS -->
    @vite(['resources/js/app.js'])

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

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

            </main>
        </div>
    </div>
</body>

</html>