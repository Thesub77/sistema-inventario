import './bootstrap';
import './tailwind.config';
import '../css/custom.css';
import { posModule } from './modules/pos';
import { productosModule } from './modules/productos';
import { categoriasModule } from './modules/categorias';
import { clientesModule } from './modules/clientes';
import { usuariosModule } from './modules/usuarios';
import { utilsModule } from './modules/utils';
import { themeModule } from './modules/theme';
import { authModule } from './modules/auth';
import { dashboardModule } from './modules/dashboard';

export function app() {
    return {
        // Main State
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

        // Navigation Sidebar Configuration
        navItems: [
            {
                id: 'dashboard',
                label: 'Dashboard',
                icon: 'layout-dashboard'
            },
            {
                id: 'pos',
                label: 'Punto de Venta (POS)',
                icon: 'shopping-cart'
            },
            {
                id: 'productos',
                label: 'Productos & Stock',
                icon: 'package',
                badge: () => this.lowStockProducts.length
            },
            {
                id: 'categorias',
                label: 'Categorías',
                icon: 'tags'
            },
            {
                id: 'ventas',
                label: 'Historial de Ventas',
                icon: 'receipt'
            },
            {
                id: 'clientes',
                label: 'Clientes',
                icon: 'users'
            },
            {
                id: 'caja',
                label: 'Cajas & Arqueos',
                icon: 'wallet'
            },
            {
                id: 'inventario',
                label: 'Kardex / Movimientos',
                icon: 'arrow-left-right'
            },
            {
                id: 'usuarios',
                label: 'Usuarios & Roles',
                icon: 'user-cog'
            },
            {
                id: 'bitacora',
                label: 'Bitácora Auditoría',
                icon: 'shield-check'
            },
        ],

        // Computed Properties / Getters
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
            if (this.dashboardData?.stats) {
                return this.dashboardData.stats;
            }
            const totalVentasMonto = this.ventas.reduce((sum, v) => sum + Number(v.total_venta || 0), 0);
            const totalUnidades = this.productos.reduce((sum, p) => sum + Number(p.existencia_bodega || 0), 0);
            return {
                totalVentasMonto,
                totalUnidades
            };
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

        // --- Lazy Loading de Pestañas ---
        loadedTabs: [],

        async loadTab(tab, force = false) {
            if (!force && this.loadedTabs.includes(tab)) {
                return;
            }

            this.loading = true;
            try {
                switch (tab) {
                    case 'dashboard':
                        await this.fetchDashboardData();
                        this.initDashboardCharts();
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

        // App Initialization
        initApp() {
            // Inicializar tema claro / oscuro
            this.initTheme();

            // Inicializar autenticación y verificar sesión
            this.initAuth();

            // Si está autenticado, cargar pestaña activa inicial
            if (this.isAuthenticated) {
                this.loadTab(this.currentTab);
                if (this.currentTab === 'dashboard') {
                    this.initDashboardCharts();
                }
            }

            // Observar cambios de tema para redibujar gráficos
            this.$watch('darkMode', () => {
                if (this.currentTab === 'dashboard') {
                    this.renderDashboardCharts();
                }
            });

            this.$watch('dashboardVentasView', () => {
                if (this.currentTab === 'dashboard') {
                    this.renderDashboardCharts();
                }
            });

            // Observar cambios de pestaña para cargar datos bajo demanda
            this.$watch('currentTab', (newTab) => {
                this.loadTab(newTab);
                if (newTab === 'dashboard') {
                    this.initDashboardCharts();
                }
                this.$nextTick(() => {
                    if (window.lucide) {
                        window.lucide.createIcons();
                    }
                });
            });
        },

        // --- Data Fetching Específico y Optimizado ---
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

        // Recarga completa bajo demanda si el usuario la solicita
        async refreshAll() {
            this.loadedTabs = [];
            await this.loadTab(this.currentTab, true);
        },

        // Modulos desacoplados
        ...posModule(),
        ...productosModule(),
        ...categoriasModule(),
        ...clientesModule(),
        ...usuariosModule(),
        ...utilsModule(),
        ...themeModule(),
        ...authModule(),
        ...dashboardModule(),
    };
}

// Exponer la función app globalmente para Alpine.js
window.app = app;
