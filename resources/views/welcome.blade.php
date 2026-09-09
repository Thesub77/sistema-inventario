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
                <div x-show="currentTab === 'dashboard'" x-cloak class="space-y-6">
                    <!-- KPI Cards Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Stat 1: Total Ventas -->
                        <div class="glass-panel p-5 rounded-2xl relative overflow-hidden group hover:border-brand-500/40 transition-all">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Total Facturado</p>
                                    <h3 class="text-2xl font-bold font-display text-white mt-1" x-text="formatCurrency(stats.totalVentasMonto)"></h3>
                                    <p class="text-xs text-emerald-400 mt-2 flex items-center gap-1 font-medium">
                                        <i data-lucide="trending-up" class="w-3.5 h-3.5"></i>
                                        <span x-text="ventas.length + ' facturas emitidas'"></span>
                                    </p>
                                </div>
                                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center justify-center">
                                    <i data-lucide="dollar-sign" class="w-6 h-6"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Stat 2: Total Productos -->
                        <div class="glass-panel p-5 rounded-2xl relative overflow-hidden group hover:border-brand-500/40 transition-all">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Productos en Catálogo</p>
                                    <h3 class="text-2xl font-bold font-display text-white mt-1" x-text="productos.length"></h3>
                                    <p class="text-xs text-brand-400 mt-2 flex items-center gap-1 font-medium">
                                        <i data-lucide="package" class="w-3.5 h-3.5"></i>
                                        <span x-text="stats.totalUnidades + ' unidades en stock'"></span>
                                    </p>
                                </div>
                                <div class="w-12 h-12 rounded-xl bg-brand-500/10 text-brand-400 border border-brand-500/20 flex items-center justify-center">
                                    <i data-lucide="box" class="w-6 h-6"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Stat 3: Alertas Stock Bajo -->
                        <div class="glass-panel p-5 rounded-2xl relative overflow-hidden group hover:border-brand-500/40 transition-all">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Stock Bajo / Crítico</p>
                                    <h3 class="text-2xl font-bold font-display text-white mt-1" x-text="lowStockProducts.length"></h3>
                                    <p class="text-xs text-amber-400 mt-2 flex items-center gap-1 font-medium">
                                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                                        <span>Requieren reposición</span>
                                    </p>
                                </div>
                                <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center justify-center">
                                    <i data-lucide="alert-circle" class="w-6 h-6"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Stat 4: Clientes -->
                        <div class="glass-panel p-5 rounded-2xl relative overflow-hidden group hover:border-brand-500/40 transition-all">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Clientes Registrados</p>
                                    <h3 class="text-2xl font-bold font-display text-white mt-1" x-text="clientes.length"></h3>
                                    <p class="text-xs text-sky-400 mt-2 flex items-center gap-1 font-medium">
                                        <i data-lucide="users" class="w-3.5 h-3.5"></i>
                                        <span>Base de clientes activa</span>
                                    </p>
                                </div>
                                <div class="w-12 h-12 rounded-xl bg-sky-500/10 text-sky-400 border border-sky-500/20 flex items-center justify-center">
                                    <i data-lucide="user-check" class="w-6 h-6"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dashboard Sections Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Recent Sales List -->
                        <div class="lg:col-span-2 glass-panel p-5 rounded-2xl">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="font-display font-bold text-base text-white flex items-center gap-2">
                                    <i data-lucide="receipt" class="w-4 h-4 text-brand-400"></i>
                                    Últimas Ventas Realizadas
                                </h4>
                                <button @click="currentTab = 'ventas'" class="text-xs text-brand-400 hover:text-brand-300 font-semibold">Ver todas &rarr;</button>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead class="text-xs uppercase bg-dark-900/60 text-slate-400 border-b border-slate-800">
                                        <tr>
                                            <th class="py-2.5 px-3">Código</th>
                                            <th class="py-2.5 px-3">Cliente</th>
                                            <th class="py-2.5 px-3">Método</th>
                                            <th class="py-2.5 px-3">Fecha</th>
                                            <th class="py-2.5 px-3 text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800/60">
                                        <template x-for="v in ventas.slice(0, 5)" :key="v.venta_id">
                                            <tr class="hover:bg-slate-800/40 transition-colors">
                                                <td class="py-3 px-3 font-semibold text-brand-300" x-text="v.codigo_venta"></td>
                                                <td class="py-3 px-3 text-slate-200" x-text="v.cliente ? v.cliente.nombre_apellido_cliente : 'Consumidor Final'"></td>
                                                <td class="py-3 px-3">
                                                    <span class="px-2 py-0.5 text-xs rounded-md font-medium"
                                                          :class="v.metodo_pago === 'Efectivo' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-brand-500/10 text-brand-400 border border-brand-500/20'" 
                                                          x-text="v.metodo_pago"></span>
                                                </td>
                                                <td class="py-3 px-3 text-xs text-slate-400" x-text="formatDate(v.fecha_hora_venta)"></td>
                                                <td class="py-3 px-3 text-right font-bold text-white" x-text="formatCurrency(v.total_venta)"></td>
                                            </tr>
                                        </template>
                                        <tr x-show="ventas.length === 0">
                                            <td colspan="5" class="py-6 text-center text-slate-500">No hay ventas registradas aún</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Low Stock & Quick Actions -->
                        <div class="space-y-6">
                            <!-- Low Stock Box -->
                            <div class="glass-panel p-5 rounded-2xl">
                                <h4 class="font-display font-bold text-base text-white mb-3 flex items-center gap-2">
                                    <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-400"></i>
                                    Alertas de Stock Mínimo
                                </h4>
                                <div class="space-y-3">
                                    <template x-for="p in lowStockProducts.slice(0, 4)" :key="p.producto_id">
                                        <div class="flex items-center justify-between p-3 rounded-xl bg-dark-900/60 border border-slate-800">
                                            <div class="overflow-hidden pr-2">
                                                <p class="text-sm font-semibold text-slate-200 truncate" x-text="p.nombre_producto"></p>
                                                <p class="text-xs text-slate-400" x-text="'Mínimo: ' + p.existencia_minima + ' uds.'"></p>
                                            </div>
                                            <div class="text-right">
                                                <span class="px-2 py-1 text-xs font-bold rounded-lg bg-rose-500/20 text-rose-400 border border-rose-500/30" x-text="p.existencia_bodega + ' uds.'"></span>
                                            </div>
                                        </div>
                                    </template>
                                    <div x-show="lowStockProducts.length === 0" class="text-xs text-emerald-400 flex items-center gap-2 p-3 bg-emerald-500/10 rounded-xl border border-emerald-500/20">
                                        <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                                        <span>Todos los productos tienen stock saludable.</span>
                                    </div>
                                </div>
                            </div>

                            <!-- System Status Box -->
                            <div class="glass-panel p-5 rounded-2xl">
                                <h4 class="font-display font-bold text-base text-white mb-3 flex items-center gap-2">
                                    <i data-lucide="activity" class="w-4 h-4 text-emerald-400"></i>
                                    Estado de Cajas
                                </h4>
                                <div class="space-y-2">
                                    <template x-for="c in cajas" :key="c.caja_id">
                                        <div class="flex items-center justify-between p-3 rounded-xl bg-dark-900/60 border border-slate-800">
                                            <div>
                                                <p class="text-xs font-semibold text-slate-200" x-text="c.descripcion_caja"></p>
                                                <p class="text-[11px] text-slate-400" x-text="'Tipo: ' + c.tipo_apertura"></p>
                                            </div>
                                            <span class="px-2 py-0.5 text-xs font-bold rounded-full"
                                                  :class="c.estado_caja === 'Abierta' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-700 text-slate-400'"
                                                  x-text="c.estado_caja"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. TAB: PRODUCTOS E INVENTARIO -->
                <div x-show="currentTab === 'productos'" x-cloak class="space-y-5">
                    <!-- Filters & Actions Header -->
                    <div class="flex flex-col md:flex-row items-center justify-between gap-4 glass-panel p-4 rounded-2xl">
                        <div class="flex items-center gap-3 w-full md:w-auto">
                            <!-- Search -->
                            <div class="relative flex-1 md:w-80">
                                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                                <input type="text" x-model="searchProduct" placeholder="Buscar por código, nombre..." 
                                       class="w-full bg-dark-900 border border-slate-700/80 rounded-xl pl-10 pr-4 py-2 text-sm text-slate-100 placeholder-slate-400 focus:outline-none focus:border-brand-500">
                            </div>

                            <!-- Category Filter -->
                            <select x-model="filterCategory" class="bg-dark-900 border border-slate-700/80 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-brand-500">
                                <option value="">Todas las categorías</option>
                                <template x-for="cat in categorias" :key="cat.categoria_id">
                                    <option :value="cat.categoria_id" x-text="cat.nombre_categoria"></option>
                                </template>
                            </select>
                        </div>

                        <button @click="openProductModal()" class="w-full md:w-auto flex items-center justify-center gap-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-lg shadow-brand-600/20 transition-all">
                            <i data-lucide="plus-circle" class="w-4 h-4"></i>
                            <span>Agregar Producto</span>
                        </button>
                    </div>

                    <!-- Products Table -->
                    <div class="glass-panel rounded-2xl overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="text-xs uppercase bg-dark-900/80 text-slate-400 border-b border-slate-800">
                                    <tr>
                                        <th class="py-3 px-4">Código</th>
                                        <th class="py-3 px-4">Producto</th>
                                        <th class="py-3 px-4">Categoría</th>
                                        <th class="py-3 px-4 text-right">Costo</th>
                                        <th class="py-3 px-4 text-right">Precio Venta</th>
                                        <th class="py-3 px-4 text-center">Stock</th>
                                        <th class="py-3 px-4 text-center">Estado</th>
                                        <th class="py-3 px-4 text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/60">
                                    <template x-for="p in filteredProducts" :key="p.producto_id">
                                        <tr class="hover:bg-slate-800/40 transition-colors">
                                            <td class="py-3.5 px-4 font-mono text-xs font-semibold text-brand-300" x-text="p.codigo_producto"></td>
                                            <td class="py-3.5 px-4">
                                                <div class="font-semibold text-slate-100" x-text="p.nombre_producto"></div>
                                                <div class="text-xs text-slate-400 truncate max-w-xs" x-text="p.descripcion_producto"></div>
                                            </td>
                                            <td class="py-3.5 px-4">
                                                <span class="px-2.5 py-1 text-xs rounded-lg bg-dark-900 border border-slate-700 text-slate-300 font-medium" 
                                                      x-text="p.categoria ? p.categoria.nombre_categoria : 'Sin categoría'"></span>
                                            </td>
                                            <td class="py-3.5 px-4 text-right text-slate-400 font-medium" x-text="formatCurrency(p.costo_compra)"></td>
                                            <td class="py-3.5 px-4 text-right font-bold text-emerald-400" x-text="formatCurrency(p.precio_venta)"></td>
                                            <td class="py-3.5 px-4 text-center">
                                                <span class="px-2.5 py-1 text-xs font-bold rounded-lg inline-flex items-center gap-1"
                                                      :class="p.existencia_bodega <= p.existencia_minima 
                                                        ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30' 
                                                        : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'">
                                                    <span x-text="p.existencia_bodega + ' uds.'"></span>
                                                    <i x-show="p.existencia_bodega <= p.existencia_minima" data-lucide="alert-circle" class="w-3 h-3"></i>
                                                </span>
                                            </td>
                                            <td class="py-3.5 px-4 text-center">
                                                <span class="px-2 py-0.5 text-xs rounded-full"
                                                      :class="p.estado == 1 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-700 text-slate-400'"
                                                      x-text="p.estado == 1 ? 'Activo' : 'Inactivo'"></span>
                                            </td>
                                            <td class="py-3.5 px-4 text-center">
                                                <div class="flex items-center justify-center gap-1.5">
                                                    <!-- Edit Button -->
                                                    <button @click="openProductModal(p)" class="p-1.5 text-slate-400 hover:text-brand-400 hover:bg-slate-800 rounded-lg transition-colors" title="Editar Producto">
                                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                                    </button>
                                                    <!-- Quick Stock Adjustment -->
                                                    <button @click="openStockModal(p)" class="p-1.5 text-slate-400 hover:text-emerald-400 hover:bg-slate-800 rounded-lg transition-colors" title="Ajustar Inventario">
                                                        <i data-lucide="package-plus" class="w-4 h-4"></i>
                                                    </button>
                                                    <!-- Delete Button -->
                                                    <button @click="deleteProduct(p)" class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-slate-800 rounded-lg transition-colors" title="Eliminar Producto">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="filteredProducts.length === 0">
                                        <td colspan="8" class="py-12 text-center text-slate-400">
                                            <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 text-slate-600"></i>
                                            <p class="font-medium">No se encontraron productos coincidentes</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 3. TAB: PUNTO DE VENTA (POS & FACTURACIÓN) -->
                <div x-show="currentTab === 'pos'" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Products Catalog -->
                    <div class="lg:col-span-2 space-y-4">
                        <div class="glass-panel p-4 rounded-2xl flex items-center justify-between gap-4">
                            <div class="relative flex-1">
                                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                                <input type="text" x-model="posSearch" placeholder="Buscar producto para agregar a la venta..." 
                                       class="w-full bg-dark-900 border border-slate-700/80 rounded-xl pl-10 pr-4 py-2 text-sm text-slate-100 placeholder-slate-400 focus:outline-none focus:border-brand-500">
                            </div>
                            <select x-model="posCategoryFilter" class="bg-dark-900 border border-slate-700/80 rounded-xl px-3 py-2 text-sm text-slate-200">
                                <option value="">Todas las categorías</option>
                                <template x-for="cat in categorias" :key="cat.categoria_id">
                                    <option :value="cat.categoria_id" x-text="cat.nombre_categoria"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Product Cards Grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3.5 max-h-[calc(100vh-250px)] overflow-y-auto pr-1">
                            <template x-for="p in posFilteredProducts" :key="p.producto_id">
                                <div @click="addToCart(p)" 
                                     class="glass-panel p-4 rounded-2xl cursor-pointer hover:border-brand-500/60 hover:bg-slate-800/60 transition-all flex flex-col justify-between group relative overflow-hidden">
                                    <div>
                                        <span class="text-[10px] font-mono text-brand-400 font-semibold uppercase" x-text="p.codigo_producto"></span>
                                        <h5 class="text-sm font-bold text-slate-100 mt-1 line-clamp-2" x-text="p.nombre_producto"></h5>
                                        <p class="text-xs text-slate-400 mt-0.5 truncate" x-text="p.categoria ? p.categoria.nombre_categoria : ''"></p>
                                    </div>
                                    <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between">
                                        <span class="font-display font-bold text-base text-emerald-400" x-text="formatCurrency(p.precio_venta)"></span>
                                        <span class="text-[11px] px-2 py-0.5 rounded-md font-bold"
                                              :class="p.existencia_bodega > 0 ? 'bg-slate-800 text-slate-300' : 'bg-rose-500/20 text-rose-400'"
                                              x-text="'Stock: ' + p.existencia_bodega"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Cart / Checkout Panel -->
                    <div class="glass-panel p-5 rounded-2xl flex flex-col justify-between h-[calc(100vh-160px)]">
                        <div class="space-y-4 overflow-y-auto pr-1">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                                <h4 class="font-display font-bold text-base text-white flex items-center gap-2">
                                    <i data-lucide="shopping-bag" class="w-4 h-4 text-brand-400"></i>
                                    Factura / Carrito
                                </h4>
                                <button @click="clearCart()" class="text-xs text-rose-400 hover:text-rose-300 font-semibold" x-show="cart.length > 0">Vaciar</button>
                            </div>

                            <!-- Customer Selector -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Cliente</label>
                                <select x-model="posSale.id_cliente" class="w-full bg-dark-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-brand-500">
                                    <template x-for="c in clientes" :key="c.cliente_id">
                                        <option :value="c.cliente_id" x-text="c.nombre_apellido_cliente + ' (' + (c.codigo_cliente || 'Sin código') + ')'"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Payment Method -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Método de Pago</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <button @click="posSale.metodo_pago = 'Efectivo'" 
                                            :class="posSale.metodo_pago === 'Efectivo' ? 'bg-brand-600 text-white font-bold border-brand-500' : 'bg-dark-900 text-slate-400 border-slate-800'"
                                            class="py-2 text-xs rounded-xl border transition-all text-center">Efectivo</button>
                                    <button @click="posSale.metodo_pago = 'Tarjeta'" 
                                            :class="posSale.metodo_pago === 'Tarjeta' ? 'bg-brand-600 text-white font-bold border-brand-500' : 'bg-dark-900 text-slate-400 border-slate-800'"
                                            class="py-2 text-xs rounded-xl border transition-all text-center">Tarjeta</button>
                                    <button @click="posSale.metodo_pago = 'Transferencia'" 
                                            :class="posSale.metodo_pago === 'Transferencia' ? 'bg-brand-600 text-white font-bold border-brand-500' : 'bg-dark-900 text-slate-400 border-slate-800'"
                                            class="py-2 text-xs rounded-xl border transition-all text-center">Transferencia</button>
                                </div>
                            </div>

                            <!-- Cart Items List -->
                            <div class="space-y-2 mt-3">
                                <template x-for="(item, index) in cart" :key="item.id_producto">
                                    <div class="p-3 bg-dark-900/80 rounded-xl border border-slate-800/80 flex items-center justify-between gap-2">
                                        <div class="overflow-hidden">
                                            <p class="text-xs font-bold text-slate-200 truncate" x-text="item.nombre_producto"></p>
                                            <p class="text-[11px] text-slate-400" x-text="formatCurrency(item.precio_unitario) + ' c/u'"></p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="flex items-center bg-dark-950 border border-slate-700 rounded-lg overflow-hidden">
                                                <button @click="decreaseCartQty(index)" class="px-2 py-0.5 text-xs text-slate-400 hover:text-white">-</button>
                                                <span class="px-2 py-0.5 text-xs font-bold text-white" x-text="item.cantidad"></span>
                                                <button @click="increaseCartQty(index)" class="px-2 py-0.5 text-xs text-slate-400 hover:text-white">+</button>
                                            </div>
                                            <span class="text-xs font-bold text-emerald-400 w-16 text-right" x-text="formatCurrency(item.subtotal_venta_detalle)"></span>
                                            <button @click="removeFromCart(index)" class="text-slate-500 hover:text-rose-400">
                                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                <div x-show="cart.length === 0" class="py-8 text-center text-slate-500 text-xs">
                                    <i data-lucide="shopping-cart" class="w-6 h-6 mx-auto mb-1 opacity-50"></i>
                                    <span>Haz clic en un producto para agregarlo</span>
                                </div>
                            </div>
                        </div>

                        <!-- Invoice Totals & Checkout Button -->
                        <div class="pt-4 border-t border-slate-800 space-y-3">
                            <div class="space-y-1.5 text-xs">
                                <div class="flex justify-between text-slate-400">
                                    <span>Subtotal:</span>
                                    <span class="font-semibold text-slate-200" x-text="formatCurrency(cartSubtotal)"></span>
                                </div>
                                <div class="flex justify-between items-center text-slate-400">
                                    <span>Descuento:</span>
                                    <div class="flex items-center gap-1">
                                        <span>C$</span>
                                        <input type="number" x-model.number="posSale.descuento_venta" min="0" 
                                               class="w-20 bg-dark-900 border border-slate-700 rounded px-1.5 py-0.5 text-right text-xs text-white">
                                    </div>
                                </div>
                                <div class="flex justify-between text-base font-bold text-white pt-2 border-t border-slate-800">
                                    <span>Total a Pagar:</span>
                                    <span class="text-emerald-400 font-display text-lg" x-text="formatCurrency(cartTotal)"></span>
                                </div>
                            </div>

                            <button @click="processSale()" 
                                    :disabled="cart.length === 0 || loading" 
                                    class="w-full py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold rounded-xl shadow-lg shadow-emerald-600/25 flex items-center justify-center gap-2 transition-all">
                                <i data-lucide="check-circle" class="w-5 h-5"></i>
                                <span>Emitir Factura y Cobrar</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 4. TAB: HISTORIAL DE VENTAS -->
                <div x-show="currentTab === 'ventas'" x-cloak class="space-y-5">
                    <div class="glass-panel p-4 rounded-2xl flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="relative w-full sm:w-80">
                            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                            <input type="text" x-model="searchVenta" placeholder="Buscar por código de factura o cliente..." 
                                   class="w-full bg-dark-900 border border-slate-700/80 rounded-xl pl-10 pr-4 py-2 text-sm text-slate-100 placeholder-slate-400 focus:outline-none focus:border-brand-500">
                        </div>
                        <button @click="currentTab = 'pos'" class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold px-4 py-2 rounded-xl transition-all">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Nueva Venta</span>
                        </button>
                    </div>

                    <div class="glass-panel rounded-2xl overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="text-xs uppercase bg-dark-900/80 text-slate-400 border-b border-slate-800">
                                    <tr>
                                        <th class="py-3 px-4">Factura</th>
                                        <th class="py-3 px-4">Cliente</th>
                                        <th class="py-3 px-4">Vendedor / Usuario</th>
                                        <th class="py-3 px-4">Método</th>
                                        <th class="py-3 px-4">Fecha y Hora</th>
                                        <th class="py-3 px-4 text-right">Subtotal</th>
                                        <th class="py-3 px-4 text-right">Total</th>
                                        <th class="py-3 px-4 text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/60">
                                    <template x-for="v in filteredVentas" :key="v.venta_id">
                                        <tr class="hover:bg-slate-800/40 transition-colors">
                                            <td class="py-3.5 px-4 font-mono font-bold text-brand-300" x-text="v.codigo_venta"></td>
                                            <td class="py-3.5 px-4 font-medium text-slate-200" x-text="v.cliente ? v.cliente.nombre_apellido_cliente : 'Consumidor Final'"></td>
                                            <td class="py-3.5 px-4 text-xs text-slate-400" x-text="v.usuario ? v.usuario.nombre_apellido : 'N/A'"></td>
                                            <td class="py-3.5 px-4">
                                                <span class="px-2 py-0.5 text-xs rounded-md font-medium"
                                                      :class="v.metodo_pago === 'Efectivo' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-brand-500/10 text-brand-400'"
                                                      x-text="v.metodo_pago"></span>
                                            </td>
                                            <td class="py-3.5 px-4 text-xs text-slate-400" x-text="formatDate(v.fecha_hora_venta)"></td>
                                            <td class="py-3.5 px-4 text-right text-slate-400" x-text="formatCurrency(v.subtotal_venta)"></td>
                                            <td class="py-3.5 px-4 text-right font-bold text-white" x-text="formatCurrency(v.total_venta)"></td>
                                            <td class="py-3.5 px-4 text-center">
                                                <div class="flex items-center justify-center gap-1.5">
                                                    <button @click="viewSaleDetails(v)" class="p-1.5 text-slate-400 hover:text-brand-400 hover:bg-slate-800 rounded-lg transition-colors" title="Ver Detalles de Factura">
                                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                                    </button>
                                                    <button @click="deleteSale(v)" class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-slate-800 rounded-lg transition-colors" title="Anular / Eliminar Venta">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 5. TAB: CATEGORÍAS -->
                <div x-show="currentTab === 'categorias'" x-cloak class="space-y-5">
                    {{-- 
                        [CAMBIO ARQUITECTURA MVC]: 
                        El formulario modal fue extraído a 'resources/views/categorias/form.blade.php'.
                        Se incluye aquí para mantener la modularidad sin romper la reactividad de Alpine.js.
                    --}}
                    @include('categorias.form')

                    {{-- Barra superior: Título y botón para abrir el modal de creación --}}
                    <div class="flex items-center justify-between glass-panel p-4 rounded-2xl">
                        <h3 class="text-sm font-semibold text-slate-300">Gestión de Categorías de Productos</h3>
                        <button @click="openCategoryModal()" class="flex items-center gap-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold px-4 py-2 rounded-xl transition-all">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Nueva Categoría</span>
                        </button>
                    </div>

                    {{-- [CAMBIO]: Grid que itera y muestra las tarjetas solo cuando existen categorías registradas --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" x-show="categorias.length > 0">
                        <template x-for="cat in categorias" :key="cat.categoria_id">
                            <div class="glass-panel p-5 rounded-2xl flex flex-col justify-between hover:border-brand-500/40 transition-all">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-mono font-bold text-brand-400" x-text="cat.codigo_categoria || 'SIN CÓDIGO'"></span>
                                        <span class="px-2 py-0.5 text-[10px] rounded-full"
                                              :class="cat.estado == 1 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-700 text-slate-400'"
                                              x-text="cat.estado == 1 ? 'Activa' : 'Inactiva'"></span>
                                    </div>
                                    <h4 class="font-display font-bold text-lg text-white" x-text="cat.nombre_categoria"></h4>
                                    <p class="text-xs text-slate-400 mt-1" x-text="cat.descripcion_categoria || 'Sin descripción'"></p>
                                </div>

                                <div class="mt-4 pt-3 border-t border-slate-800 flex items-center justify-between">
                                    <span class="text-xs text-slate-400 font-medium" 
                                          x-text="(productos.filter(p => p.id_categoria == cat.categoria_id).length) + ' productos'"></span>
                                    <div class="flex items-center gap-1">
                                        {{-- Botón para editar: pasa el objeto categoría a openCategoryModal --}}
                                        <button @click="openCategoryModal(cat)" class="p-1.5 text-slate-400 hover:text-brand-400 hover:bg-slate-800 rounded-lg transition-colors">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </button>
                                        {{-- Botón para eliminar: llama a deleteCategory con confirmación SweetAlert --}}
                                        <button @click="deleteCategory(cat)" class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-slate-800 rounded-lg transition-colors">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- [CAMBIO]: Estado vacío amigable cuando la base de datos no tiene categorías registradas --}}
                    <div x-show="categorias.length === 0" x-cloak class="glass-panel p-10 text-center rounded-2xl border border-slate-800">
                        <div class="w-12 h-12 rounded-2xl bg-brand-500/10 text-brand-400 flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="tags" class="w-6 h-6"></i>
                        </div>
                        <h4 class="text-white font-bold text-base">No hay categorías registradas</h4>
                        <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Comienza agregando tu primera categoría para organizar los productos.</p>
                        <button @click="openCategoryModal()" class="mt-4 inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-500 text-white text-xs font-semibold px-4 py-2 rounded-xl transition-all">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Crear Primera Categoría</span>
                        </button>
                    </div>
                </div>

                <!-- 6. TAB: CLIENTES -->
                <div x-show="currentTab === 'clientes'" x-cloak class="space-y-5">
                    <div class="flex items-center justify-between glass-panel p-4 rounded-2xl">
                        <div class="relative w-full sm:w-80">
                            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                            <input type="text" x-model="searchCliente" placeholder="Buscar cliente por nombre, teléfono..." 
                                   class="w-full bg-dark-900 border border-slate-700/80 rounded-xl pl-10 pr-4 py-2 text-sm text-slate-100 placeholder-slate-400 focus:outline-none focus:border-brand-500">
                        </div>
                        <button @click="openCustomerModal()" class="flex items-center gap-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold px-4 py-2 rounded-xl transition-all">
                            <i data-lucide="user-plus" class="w-4 h-4"></i>
                            <span>Nuevo Cliente</span>
                        </button>
                    </div>

                    <div class="glass-panel rounded-2xl overflow-hidden">
                        <table class="w-full text-left text-sm">
                            <thead class="text-xs uppercase bg-dark-900/80 text-slate-400 border-b border-slate-800">
                                <tr>
                                    <th class="py-3 px-4">Código</th>
                                    <th class="py-3 px-4">Nombre / Razón Social</th>
                                    <th class="py-3 px-4">Teléfono</th>
                                    <th class="py-3 px-4 text-center">Estado</th>
                                    <th class="py-3 px-4 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                <template x-for="c in filteredClientes" :key="c.cliente_id">
                                    <tr class="hover:bg-slate-800/40 transition-colors">
                                        <td class="py-3.5 px-4 font-mono text-xs font-semibold text-brand-300" x-text="c.codigo_cliente || 'N/A'"></td>
                                        <td class="py-3.5 px-4 font-semibold text-slate-100" x-text="c.nombre_apellido_cliente"></td>
                                        <td class="py-3.5 px-4 text-slate-400" x-text="c.telefono_cliente || 'Sin teléfono'"></td>
                                        <td class="py-3.5 px-4 text-center">
                                            <span class="px-2 py-0.5 text-xs rounded-full"
                                                  :class="c.estado == 1 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-700 text-slate-400'"
                                                  x-text="c.estado == 1 ? 'Activo' : 'Inactivo'"></span>
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button @click="openCustomerModal(c)" class="p-1.5 text-slate-400 hover:text-brand-400 hover:bg-slate-800 rounded-lg">
                                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                                </button>
                                                <button @click="deleteCustomer(c)" class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-slate-800 rounded-lg">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 7. TAB: CAJA Y MOVIMIENTOS -->
                <div x-show="currentTab === 'caja'" x-cloak class="space-y-6">
                    <!-- Cajas Status Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <template x-for="c in cajas" :key="c.caja_id">
                            <div class="glass-panel p-5 rounded-2xl border-l-4" 
                                 :class="c.estado_caja === 'Abierta' ? 'border-l-emerald-500' : 'border-l-slate-700'">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <span class="text-xs text-slate-400 uppercase font-semibold">Caja #<span x-text="c.caja_id"></span></span>
                                        <h4 class="font-display font-bold text-lg text-white mt-0.5" x-text="c.descripcion_caja"></h4>
                                        <p class="text-xs text-slate-400 mt-1" x-text="'Tipo: ' + c.tipo_apertura"></p>
                                    </div>
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-lg"
                                          :class="c.estado_caja === 'Abierta' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400'"
                                          x-text="c.estado_caja"></span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Caja Movements -->
                    <div class="glass-panel p-5 rounded-2xl">
                        <h4 class="font-display font-bold text-base text-white mb-4 flex items-center gap-2">
                            <i data-lucide="history" class="w-4 h-4 text-brand-400"></i>
                            Historial de Movimientos de Caja
                        </h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="text-xs uppercase bg-dark-900/80 text-slate-400 border-b border-slate-800">
                                    <tr>
                                        <th class="py-3 px-4">ID</th>
                                        <th class="py-3 px-4">Caja</th>
                                        <th class="py-3 px-4">Venta Asociada</th>
                                        <th class="py-3 px-4">Fecha y Hora</th>
                                        <th class="py-3 px-4 text-right">Monto</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/60">
                                    <template x-for="mov in cajaMovimientos" :key="mov.caja_movimiento_venta_id">
                                        <tr class="hover:bg-slate-800/40">
                                            <td class="py-3 px-4 font-mono text-xs text-slate-400" x-text="'#' + mov.caja_movimiento_venta_id"></td>
                                            <td class="py-3 px-4 text-slate-200" x-text="mov.caja ? mov.caja.descripcion_caja : 'Caja ' + mov.id_caja"></td>
                                            <td class="py-3 px-4 font-semibold text-brand-300" x-text="mov.venta ? mov.venta.codigo_venta : 'Movimiento Manual'"></td>
                                            <td class="py-3 px-4 text-xs text-slate-400" x-text="formatDate(mov.fecha_hora_movimiento)"></td>
                                            <td class="py-3 px-4 text-right font-bold text-emerald-400" x-text="formatCurrency(mov.monto_movimiento)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 8. TAB: MOVIMIENTOS DE INVENTARIO -->
                <div x-show="currentTab === 'inventario'" x-cloak class="space-y-5">
                    <div class="glass-panel p-5 rounded-2xl">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-display font-bold text-base text-white flex items-center gap-2">
                                <i data-lucide="repeat" class="w-4 h-4 text-brand-400"></i>
                                Registro de Kardex y Movimientos de Stock
                            </h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="text-xs uppercase bg-dark-900/80 text-slate-400 border-b border-slate-800">
                                    <tr>
                                        <th class="py-3 px-4">Fecha</th>
                                        <th class="py-3 px-4">Producto</th>
                                        <th class="py-3 px-4">Tipo de Movimiento</th>
                                        <th class="py-3 px-4 text-center">Cantidad</th>
                                        <th class="py-3 px-4 text-center">Stock Previo</th>
                                        <th class="py-3 px-4 text-center">Stock Resultante</th>
                                        <th class="py-3 px-4">Usuario</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/60">
                                    <template x-for="m in movimientosInventario" :key="m.movimiento_inventario_id">
                                        <tr class="hover:bg-slate-800/40">
                                            <td class="py-3 px-4 text-xs text-slate-400" x-text="formatDate(m.fecha_movimiento)"></td>
                                            <td class="py-3 px-4 font-semibold text-slate-100" x-text="m.producto ? m.producto.nombre_producto : 'Producto #' + m.id_producto"></td>
                                            <td class="py-3 px-4">
                                                <span class="px-2.5 py-0.5 text-xs rounded-full font-medium"
                                                      :class="m.tipo_movimiento.includes('Entrada') || m.tipo_movimiento.includes('Inicial') 
                                                        ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' 
                                                        : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'"
                                                      x-text="m.tipo_movimiento"></span>
                                            </td>
                                            <td class="py-3 px-4 text-center font-bold" x-text="m.cantidad_movimimiento + ' uds.'"></td>
                                            <td class="py-3 px-4 text-center text-slate-400" x-text="m.stock_anterior_producto"></td>
                                            <td class="py-3 px-4 text-center font-bold text-white" x-text="m.stock_resultante_producto"></td>
                                            <td class="py-3 px-4 text-xs text-slate-400" x-text="m.usuario ? m.usuario.nombre_apellido : 'N/A'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 9. TAB: USUARIOS Y ROLES -->
                <div x-show="currentTab === 'usuarios'" x-cloak class="space-y-5">
                    <div class="flex items-center justify-between glass-panel p-4 rounded-2xl">
                        <h3 class="text-sm font-semibold text-slate-300">Gestión de Usuarios del Sistema</h3>
                        <button @click="openUserModal()" class="flex items-center gap-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold px-4 py-2 rounded-xl transition-all">
                            <i data-lucide="user-plus" class="w-4 h-4"></i>
                            <span>Nuevo Usuario</span>
                        </button>
                    </div>

                    <div class="glass-panel rounded-2xl overflow-hidden">
                        <table class="w-full text-left text-sm">
                            <thead class="text-xs uppercase bg-dark-900/80 text-slate-400 border-b border-slate-800">
                                <tr>
                                    <th class="py-3 px-4">Nombre Completo</th>
                                    <th class="py-3 px-4">Usuario</th>
                                    <th class="py-3 px-4">Rol Asignado</th>
                                    <th class="py-3 px-4">Fecha Registro</th>
                                    <th class="py-3 px-4 text-center">Estado</th>
                                    <th class="py-3 px-4 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                <template x-for="u in usuarios" :key="u.usuario_id">
                                    <tr class="hover:bg-slate-800/40">
                                        <td class="py-3.5 px-4 font-semibold text-slate-100" x-text="u.nombre_apellido"></td>
                                        <td class="py-3.5 px-4 font-mono text-xs text-brand-300" x-text="u.nombre_usuario"></td>
                                        <td class="py-3.5 px-4">
                                            <span class="px-2.5 py-1 text-xs rounded-lg bg-brand-500/10 text-brand-400 font-semibold border border-brand-500/20"
                                                  x-text="u.rol ? u.rol.nombre_rol : 'Sin rol'"></span>
                                        </td>
                                        <td class="py-3.5 px-4 text-xs text-slate-400" x-text="u.fecha_registro"></td>
                                        <td class="py-3.5 px-4 text-center">
                                            <span class="px-2 py-0.5 text-xs rounded-full"
                                                  :class="u.estado == 1 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-700 text-slate-400'"
                                                  x-text="u.estado == 1 ? 'Activo' : 'Inactivo'"></span>
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button @click="openUserModal(u)" class="p-1.5 text-slate-400 hover:text-brand-400 hover:bg-slate-800 rounded-lg">
                                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                                </button>
                                                <button @click="deleteUser(u)" class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-slate-800 rounded-lg">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 10. TAB: BITÁCORA DE AUDITORÍA -->
                <div x-show="currentTab === 'bitacora'" x-cloak class="space-y-5">
                    <div class="glass-panel p-5 rounded-2xl">
                        <h4 class="font-display font-bold text-base text-white mb-4 flex items-center gap-2">
                            <i data-lucide="shield-check" class="w-4 h-4 text-brand-400"></i>
                            Registro de Bitácora de Acciones
                        </h4>
                        <div class="space-y-3">
                            <template x-for="b in bitacoras" :key="b.id_bitacora">
                                <div class="p-4 rounded-xl bg-dark-900/60 border border-slate-800 flex items-start justify-between gap-4">
                                    <div class="flex items-start gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-brand-500/10 border border-brand-500/20 text-brand-400 flex items-center justify-center flex-shrink-0 mt-0.5">
                                            <i data-lucide="terminal" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-mono font-bold text-slate-200" x-text="b.accion_bitacora"></span>
                                                <span class="text-[11px] text-slate-400" x-text="'por ' + (b.usuario ? b.usuario.nombre_apellido : 'Usuario #' + b.id_usuario)"></span>
                                            </div>
                                            <p class="text-xs text-slate-300 mt-1" x-text="b.descripcion_bitacora"></p>
                                        </div>
                                    </div>
                                    <span class="text-xs text-slate-500 whitespace-nowrap" x-text="formatDate(b.fecha_hora_bitacora)"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- 11. TAB: TESTER API (POSTMAN INTEGRADO) -->
                <div x-show="currentTab === 'api'" x-cloak class="space-y-5">
                    <div class="glass-panel p-5 rounded-2xl space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-display font-bold text-base text-white">Consola de Pruebas API RESTful</h4>
                                <p class="text-xs text-slate-400">Prueba los endpoints en vivo y observa la respuesta JSON formateada.</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button @click="testApi('/api/productos')" class="px-3 py-1.5 text-xs rounded-lg bg-brand-600/20 text-brand-300 border border-brand-500/30 hover:bg-brand-600 hover:text-white transition-all font-mono">GET /api/productos</button>
                            <button @click="testApi('/api/categorias')" class="px-3 py-1.5 text-xs rounded-lg bg-brand-600/20 text-brand-300 border border-brand-500/30 hover:bg-brand-600 hover:text-white transition-all font-mono">GET /api/categorias</button>
                            <button @click="testApi('/api/ventas')" class="px-3 py-1.5 text-xs rounded-lg bg-brand-600/20 text-brand-300 border border-brand-500/30 hover:bg-brand-600 hover:text-white transition-all font-mono">GET /api/ventas</button>
                            <button @click="testApi('/api/clientes')" class="px-3 py-1.5 text-xs rounded-lg bg-brand-600/20 text-brand-300 border border-brand-500/30 hover:bg-brand-600 hover:text-white transition-all font-mono">GET /api/clientes</button>
                            <button @click="testApi('/api/usuarios')" class="px-3 py-1.5 text-xs rounded-lg bg-brand-600/20 text-brand-300 border border-brand-500/30 hover:bg-brand-600 hover:text-white transition-all font-mono">GET /api/usuarios</button>
                            <button @click="testApi('/api/cajas')" class="px-3 py-1.5 text-xs rounded-lg bg-brand-600/20 text-brand-300 border border-brand-500/30 hover:bg-brand-600 hover:text-white transition-all font-mono">GET /api/cajas</button>
                            <button @click="testApi('/api/bitacoras')" class="px-3 py-1.5 text-xs rounded-lg bg-brand-600/20 text-brand-300 border border-brand-500/30 hover:bg-brand-600 hover:text-white transition-all font-mono">GET /api/bitacoras</button>
                        </div>

                        <!-- Response Viewer -->
                        <div class="mt-4">
                            <div class="flex items-center justify-between text-xs text-slate-400 mb-1">
                                <span x-text="'Endpoint probado: ' + (testedEndpoint || 'Ninguno')"></span>
                                <span class="text-emerald-400 font-mono" x-show="testedStatus" x-text="'Status: ' + testedStatus"></span>
                            </div>
                            <pre class="p-4 bg-dark-950 rounded-xl border border-slate-800 text-xs font-mono text-emerald-300 overflow-x-auto max-h-96" x-text="apiResponseText || '// Haz clic en un botón de endpoint arriba para ver la respuesta JSON en tiempo real'"></pre>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- MODAL: PRODUCTO (CREAR / EDITAR) -->
    <div x-show="showProductModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div @click.away="showProductModal = false" class="bg-dark-900 border border-slate-700 rounded-2xl w-full max-w-lg p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="font-display font-bold text-lg text-white" x-text="isEditingProduct ? 'Editar Producto' : 'Nuevo Producto'"></h3>
                <button @click="showProductModal = false" class="text-slate-400 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form @submit.prevent="saveProduct()" class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Código</label>
                        <input type="text" x-model="productForm.codigo_producto" required placeholder="PROD-001" 
                               class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Categoría</label>
                        <select x-model="productForm.id_categoria" required 
                                class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500">
                            <option value="">Selecciona categoría</option>
                            <template x-for="cat in categorias" :key="cat.categoria_id">
                                <option :value="cat.categoria_id" x-text="cat.nombre_categoria"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Nombre del Producto</label>
                    <input type="text" x-model="productForm.nombre_producto" required placeholder="Ej. Coca-Cola 2L" 
                           class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Descripción</label>
                    <input type="text" x-model="productForm.descripcion_producto" required placeholder="Detalles o especificaciones" 
                           class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Costo de Compra (C$)</label>
                        <input type="number" step="0.01" x-model.number="productForm.costo_compra" required 
                               class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Precio de Venta (C$)</label>
                        <input type="number" step="0.01" x-model.number="productForm.precio_venta" required 
                               class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Stock Inicial</label>
                        <input type="number" x-model.number="productForm.existencia_bodega" required 
                               class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Stock Mínimo</label>
                        <input type="number" x-model.number="productForm.existencia_minima" required 
                               class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500">
                    </div>
                </div>

                <div class="pt-4 flex justify-end gap-2 border-t border-slate-800">
                    <button type="button" @click="showProductModal = false" class="px-4 py-2 text-sm text-slate-400 hover:text-white">Cancelar</button>
                    <button type="submit" class="px-5 py-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold rounded-xl shadow-lg shadow-brand-600/20">Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: AJUSTE DE STOCK -->
    <div x-show="showStockModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div @click.away="showStockModal = false" class="bg-dark-900 border border-slate-700 rounded-2xl w-full max-w-md p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="font-display font-bold text-lg text-white">Ajustar Inventario</h3>
                <button @click="showStockModal = false" class="text-slate-400 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form @submit.prevent="saveStockAdjustment()" class="space-y-3">
                <div class="p-3 bg-dark-950 rounded-xl border border-slate-800">
                    <p class="text-xs text-slate-400">Producto:</p>
                    <p class="text-sm font-bold text-white" x-text="stockForm.nombre_producto"></p>
                    <p class="text-xs text-emerald-400 mt-1" x-text="'Stock Actual: ' + stockForm.stock_anterior + ' unidades'"></p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Tipo de Operación</label>
                    <select x-model="stockForm.tipo_movimiento" class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white">
                        <option value="Entrada por Compra">Entrada / Compra de Stock (+)</option>
                        <option value="Salida por Merma">Salida / Merma / Daño (-)</option>
                        <option value="Ajuste Manual">Ajuste Manual</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Cantidad a Agregar / Restar</label>
                    <input type="number" min="1" x-model.number="stockForm.cantidad" required 
                           class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white">
                </div>

                <div class="pt-4 flex justify-end gap-2 border-t border-slate-800">
                    <button type="button" @click="showStockModal = false" class="px-4 py-2 text-sm text-slate-400 hover:text-white">Cancelar</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold rounded-xl">Aplicar Ajuste</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: CATEGORÍA (CREAR / EDITAR) - MOVIDO DENTRO DE TAB: CATEGORÍAS -->

    <!--
    <div x-show="showCategoryModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div @click.away="showCategoryModal = false" class="bg-dark-900 border border-slate-700 rounded-2xl w-full max-w-md p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="font-display font-bold text-lg text-white" x-text="isEditingCategory ? 'Editar Categoría' : 'Nueva Categoría'"></h3>
                <button @click="showCategoryModal = false" class="text-slate-400 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form @submit.prevent="saveCategory()" class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Código</label>
                    <input type="text" x-model="categoryForm.codigo_categoria" placeholder="CAT-001" 
                           class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Nombre de la Categoría</label>
                    <input type="text" x-model="categoryForm.nombre_categoria" required placeholder="Ej. Lácteos" 
                           class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Descripción</label>
                    <input type="text" x-model="categoryForm.descripcion_categoria" placeholder="Breve descripción" 
                           class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white">
                </div>

                <div class="pt-4 flex justify-end gap-2 border-t border-slate-800">
                    <button type="button" @click="showCategoryModal = false" class="px-4 py-2 text-sm text-slate-400 hover:text-white">Cancelar</button>
                    <button type="submit" class="px-5 py-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold rounded-xl">Guardar</button>
                </div>
            </form>
        </div>
    </div>
    -->

    <!-- MODAL: CLIENTE (CREAR / EDITAR) -->
    <div x-show="showCustomerModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div @click.away="showCustomerModal = false" class="bg-dark-900 border border-slate-700 rounded-2xl w-full max-w-md p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="font-display font-bold text-lg text-white" x-text="isEditingCustomer ? 'Editar Cliente' : 'Nuevo Cliente'"></h3>
                <button @click="showCustomerModal = false" class="text-slate-400 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form @submit.prevent="saveCustomer()" class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Código</label>
                    <input type="text" x-model="customerForm.codigo_cliente" placeholder="CLI-001" 
                           class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Nombre y Apellido / Empresa</label>
                    <input type="text" x-model="customerForm.nombre_apellido_cliente" required placeholder="Ej. Juan Pérez" 
                           class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Teléfono</label>
                    <input type="text" x-model="customerForm.telefono_cliente" placeholder="8888-8888" 
                           class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white">
                </div>

                <div class="pt-4 flex justify-end gap-2 border-t border-slate-800">
                    <button type="button" @click="showCustomerModal = false" class="px-4 py-2 text-sm text-slate-400 hover:text-white">Cancelar</button>
                    <button type="submit" class="px-5 py-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold rounded-xl">Guardar Cliente</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: USUARIO (CREAR / EDITAR) -->
    <div x-show="showUserModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div @click.away="showUserModal = false" class="bg-dark-900 border border-slate-700 rounded-2xl w-full max-w-md p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="font-display font-bold text-lg text-white" x-text="isEditingUser ? 'Editar Usuario' : 'Nuevo Usuario'"></h3>
                <button @click="showUserModal = false" class="text-slate-400 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form @submit.prevent="saveUser()" class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Rol</label>
                    <select x-model="userForm.id_rol" required class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white">
                        <option value="">Selecciona un rol</option>
                        <template x-for="r in roles" :key="r.rol_id">
                            <option :value="r.rol_id" x-text="r.nombre_rol"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Nombre Completo</label>
                    <input type="text" x-model="userForm.nombre_apellido" required placeholder="Nombre y Apellido" 
                           class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Nombre de Usuario</label>
                    <input type="text" x-model="userForm.nombre_usuario" required placeholder="usuario123" 
                           class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1" x-text="isEditingUser ? 'Contraseña (dejar en blanco para no cambiar)' : 'Contraseña'"></label>
                    <input type="password" x-model="userForm.contrasenia_usuario" :required="!isEditingUser" placeholder="••••••••" 
                           class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white">
                </div>

                <div class="pt-4 flex justify-end gap-2 border-t border-slate-800">
                    <button type="button" @click="showUserModal = false" class="px-4 py-2 text-sm text-slate-400 hover:text-white">Cancelar</button>
                    <button type="submit" class="px-5 py-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold rounded-xl">Guardar Usuario</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: DETALLE DE FACTURA / VENTA -->
    <div x-show="showSaleDetailModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div @click.away="showSaleDetailModal = false" class="bg-dark-900 border border-slate-700 rounded-2xl w-full max-w-lg p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div>
                    <h3 class="font-display font-bold text-lg text-white" x-text="'Detalle de Venta: ' + (selectedSale?.codigo_venta || '')"></h3>
                    <p class="text-xs text-slate-400" x-text="selectedSale ? formatDate(selectedSale.fecha_hora_venta) : ''"></p>
                </div>
                <button @click="showSaleDetailModal = false" class="text-slate-400 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="space-y-3" x-show="selectedSale">
                <div class="grid grid-cols-2 gap-2 text-xs bg-dark-950 p-3 rounded-xl border border-slate-800">
                    <div>
                        <span class="text-slate-400">Cliente:</span>
                        <p class="font-bold text-slate-200" x-text="selectedSale?.cliente?.nombre_apellido_cliente || 'Consumidor Final'"></p>
                    </div>
                    <div>
                        <span class="text-slate-400">Método de Pago:</span>
                        <p class="font-bold text-brand-300" x-text="selectedSale?.metodo_pago"></p>
                    </div>
                </div>

                <div class="overflow-x-auto max-h-60 overflow-y-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-dark-950 uppercase text-slate-400 border-b border-slate-800">
                            <tr>
                                <th class="py-2 px-3">Producto</th>
                                <th class="py-2 px-3 text-center">Cant.</th>
                                <th class="py-2 px-3 text-right">Precio</th>
                                <th class="py-2 px-3 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            <template x-for="item in (selectedSale?.venta_detalles || [])" :key="item.id_venta_detalle">
                                <tr>
                                    <td class="py-2.5 px-3 font-medium text-slate-200" x-text="item.producto ? item.producto.nombre_producto : 'Producto #' + item.id_producto"></td>
                                    <td class="py-2.5 px-3 text-center font-bold text-white" x-text="item.cantidad"></td>
                                    <td class="py-2.5 px-3 text-right text-slate-400" x-text="formatCurrency(item.precio_unitario)"></td>
                                    <td class="py-2.5 px-3 text-right font-bold text-emerald-400" x-text="formatCurrency(item.subtotal_venta_detalle)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="pt-3 border-t border-slate-800 flex justify-between items-center text-sm font-bold">
                    <span class="text-slate-400">Total Facturado:</span>
                    <span class="text-emerald-400 font-display text-lg" x-text="formatCurrency(selectedSale?.total_venta || 0)"></span>
                </div>
            </div>
        </div>
    </div>

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
                    id_cliente: 1,
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

                // API Tester State
                testedEndpoint: '',
                testedStatus: '',
                apiResponseText: '',

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
                    { id: 'api', label: 'Consola API (Postman)', icon: 'terminal' },
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

                // =========================================================================
                // FUNCIÓN: refreshAll()
                // [CAMBIO]: Se implementó la utilidad safeFetch() con cabecera Accept: application/json.
                // Evita que un error 404/500 en una tabla secundaria rompa la carga de los demás módulos.
                // =========================================================================
                async refreshAll() {
                    this.loading = true;
                    try {
                        const safeFetch = async (url) => {
                            try {
                                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                                if (!res.ok) return [];
                                return await res.json();
                            } catch (e) {
                                console.warn(`Error cargando ${url}:`, e);
                                return [];
                            }
                        };

                        const [prodRes, catRes, cliRes, usrRes, rolRes, venRes, cajRes, movCajRes, movInvRes, bitRes] = await Promise.all([
                            safeFetch('/api/productos'),
                            safeFetch('/api/categorias'),
                            safeFetch('/api/clientes'),
                            safeFetch('/api/usuarios'),
                            safeFetch('/api/roles'),
                            safeFetch('/api/ventas'),
                            safeFetch('/api/cajas'),
                            safeFetch('/api/caja-movimientos-venta'),
                            safeFetch('/api/movimientos-inventario'),
                            safeFetch('/api/bitacoras')
                        ]);

                        this.productos = Array.isArray(prodRes) ? prodRes : [];
                        this.categorias = Array.isArray(catRes) ? catRes : [];
                        this.clientes = Array.isArray(cliRes) ? cliRes : [];
                        this.usuarios = Array.isArray(usrRes) ? usrRes : [];
                        this.roles = Array.isArray(rolRes) ? rolRes : [];
                        this.ventas = Array.isArray(venRes) ? venRes : [];
                        this.cajas = Array.isArray(cajRes) ? cajRes : [];
                        this.cajaMovimientos = Array.isArray(movCajRes) ? movCajRes : [];
                        this.movimientosInventario = Array.isArray(movInvRes) ? movInvRes : [];
                        this.bitacoras = Array.isArray(bitRes) ? bitRes : [];

                        if (this.clientes.length > 0 && !this.posSale.id_cliente) {
                            this.posSale.id_cliente = this.clientes[0].cliente_id;
                        }
                    } catch (error) {
                        console.error('Error general cargando datos:', error);
                    } finally {
                        this.loading = false;
                        this.$nextTick(() => lucide.createIcons());
                    }
                },

                // --- POS Cart Operations ---
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
                        id_cliente: this.posSale.id_cliente,
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

                // =========================================================================
                // OPERACIONES CRUD: CATEGORÍAS
                // =========================================================================

                /**
                 * Abre el modal de Categoría inicializando los datos.
                 * @param {Object|null} cat - Si se pasa un objeto, se entra en modo edición; de lo contrario, se inicializa para creación.
                 */
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

                /**
                 * Envía la petición POST (crear) o PUT (actualizar) hacia el endpoint /api/categorias.
                 * [CAMBIO]: Incluye cabecera 'Accept': 'application/json' y captura detallada de errores
                 * de validación devueltos por el backend (Laravel / PostgreSQL).
                 */
                async saveCategory() {
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

                        // Validación de respuesta HTTP
                        if (!res.ok) {
                            const errData = await res.json().catch(() => ({}));
                            let errorMsg = errData.message || 'Error al procesar la categoría';
                            if (errData.errors) {
                                errorMsg = Object.values(errData.errors).flat().join('<br>');
                            }
                            throw new Error(errorMsg);
                        }

                        // Cierre de modal y actualización del listado
                        this.showCategoryModal = false;
                        await this.refreshAll();
                        Swal.fire({ icon: 'success', title: '¡Categoría guardada con éxito!', background: '#1e293b', color: '#fff' });
                    } catch (error) {
                        // Muestra alerta con el error exacto (ej. driver faltante, validación, etc.)
                        Swal.fire({ 
                            icon: 'error', 
                            title: 'Error al guardar', 
                            html: error.message, 
                            background: '#1e293b', 
                            color: '#fff' 
                        });
                    }
                },

                /**
                 * Elimina una categoría mediante DELETE /api/categorias/{id}.
                 * [CAMBIO]: Solicita confirmación previa y captura posibles errores de integridad referencial.
                 */
                async deleteCategory(cat) {
                    const result = await Swal.fire({
                        title: '¿Eliminar categoría?',
                        text: `Se eliminará "${cat.nombre_categoria}"`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#e11d48',
                        cancelButtonText: 'Cancelar',
                        confirmButtonText: 'Sí, eliminar',
                        background: '#1e293b',
                        color: '#fff'
                    });

                    if (result.isConfirmed) {
                        try {
                            const res = await fetch(`/api/categorias/${cat.categoria_id}`, { 
                                method: 'DELETE',
                                headers: { 'Accept': 'application/json' }
                            });
                            if (!res.ok) throw new Error('No se pudo eliminar la categoría');
                            await this.refreshAll();
                            Swal.fire({ icon: 'success', title: 'Eliminado', background: '#1e293b', color: '#fff' });
                        } catch (error) {
                            Swal.fire({ icon: 'error', title: 'Error', text: error.message, background: '#1e293b', color: '#fff' });
                        }
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

                // --- API Tester ---
                async testApi(endpoint) {
                    this.testedEndpoint = endpoint;
                    try {
                        const res = await fetch(endpoint);
                        this.testedStatus = `${res.status} ${res.statusText}`;
                        const data = await res.json();
                        this.apiResponseText = JSON.stringify(data, null, 2);
                    } catch (err) {
                        this.testedStatus = 'ERROR';
                        this.apiResponseText = err.message;
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
