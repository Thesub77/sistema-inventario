import './bootstrap';
import './tailwind.config';
import '../css/custom.css';
import { posModule } from './modules/pos';
import { productosModule } from './modules/productos';
import { categoriasModule } from './modules/categorias';
import { clientesModule } from './modules/clientes';
import { usuariosModule } from './modules/usuarios';
import { utilsModule } from './modules/utils';

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

        // App Initialization & Data Fetching
        initApp() {
            this.refreshAll();
            this.$watch('currentTab', () => {
                this.$nextTick(() => {
                    if (window.lucide) {
                        window.lucide.createIcons();
                    }
                });
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
                this.$nextTick(() => {
                    if (window.lucide) {
                        window.lucide.createIcons();
                    }
                });
            }
        },

        // Modulos desacoplados
        ...posModule(),
        ...productosModule(),
        ...categoriasModule(),
        ...clientesModule(),
        ...usuariosModule(),
        ...utilsModule(),
    };
}

// Exponer la función app globalmente para Alpine.js
window.app = app;
