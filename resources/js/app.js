import './bootstrap';
import './tailwind.config';
import '../css/custom.css';
import { posModule } from './modules/pos';
import { productosModule } from './modules/productos';
import { categoriasModule } from './modules/categorias';
import { proveedoresModule } from './modules/proveedores';
import { usuariosModule } from './modules/usuarios';
import { utilsModule } from './modules/utils';
import { themeModule } from './modules/theme';
import { authModule } from './modules/auth';
import { dashboardModule } from './modules/dashboard';
import { fiscalModule } from './modules/fiscal';

// Función auxiliar para combinar módulos preservando getters y setters reactivos de Alpine.js
function mergeModules(target, ...sources) {
    for (const source of sources) {
        if (!source) continue;
        Object.defineProperties(target, Object.getOwnPropertyDescriptors(source));
    }
    return target;
}

export function app() {
    const appObj = {
        // Main State
        currentTab: 'dashboard',
        loading: false,

        // Entities Data
        productos: [],
        categorias: [],
        proveedores: [],
        cuentasPorPagar: [],
        usuarios: [],
        roles: [],
        ventas: [],
        ventasEspera: [],
        cajas: [],
        turnos: [],
        cajaMovimientos: [],
        cajaSearch: '',
        cajaMovimientoFiltro: 'todos',
        cajaHistorialAlcance: 'todas',
        cajaMetodoPagoFiltro: '',
        cajaUsuarioFiltro: '',
        showCajaMovimientoModal: false,
        isSavingCajaMovimiento: false,
        cajaMovimientoForm: {
            id_caja: '',
            id_caja_operacion: '',
            tipo_movimiento: 'Egreso',
            monto: '',
            justificacion: ''
        },

        // Estado de Apertura de Turno
        showCajaAperturaModal: false,
        isSavingCajaApertura: false,
        cajaAperturaForm: {
            id_caja: '',
            monto_apertura: '0.00'
        },

        // Estado de Arqueo y Cierre de Turno
        showCajaCierreModal: false,
        isSavingCajaCierre: false,
        loadingArqueo: false,
        cajaCierreData: null,
        cajaCierreForm: {
            monto_cierre: '',
            observacion_cierre: ''
        },

        // Estado de Gestión de Cajas Físicas
        showCajaFormModal: false,
        isSavingCaja: false,
        cajaForm: {
            caja_id: null,
            descripcion_caja: '',
            tipo_apertura: 'Manual',
            estado: 1
        },
        movimientosInventario: [],
        bitacoras: [],
        empresa: null,
        showEmpresaModal: false,
        isSavingEmpresa: false,
        empresaForm: {
            nombre_comercial: '',
            razon_social: '',
            numero_ruc: '',
            telefono_contacto: '',
            correo_contacto: '',
            direccion_fisica: '',
            mensaje_pie_ticket: '',
            moneda_simbolo: 'C$',
            regimen_tributario: 'Cuota Fija',
            techo_mensual_cuota_fija: 100000.00
        },

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
                icon: 'package'
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
                id: 'libro-diario',
                label: 'Libro Fiscal',
                icon: 'book-open'
            },
            {
                id: 'proveedores',
                label: 'Proveedores',
                icon: 'truck'
            },
            {
                id: 'cuentas-por-pagar',
                label: 'Cuentas por Pagar',
                icon: 'credit-card'
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

        getNavBadge(tabId) {
            if (tabId === 'pos') {
                return (this.ventasEspera && Array.isArray(this.ventasEspera)) ? this.ventasEspera.length : 0;
            }
            if (tabId === 'productos') {
                return (this.lowStockProducts && Array.isArray(this.lowStockProducts)) ? this.lowStockProducts.length : 0;
            }
            if (tabId === 'cuentas-por-pagar') {
                return this.cxpKPIs ? Number(this.cxpKPIs.facturas_pendientes_count || 0) : 0;
            }
            return 0;
        },

        // Computed Properties / Getters
        get currentTabTitle() {
            const found = this.navItems.find(i => i.id === this.currentTab);
            return found ? found.label : this.currentTab;
        },

        get currentTabIcon() {
            const found = this.navItems.find(i => i.id === this.currentTab);
            return found ? found.icon : 'layers';
        },

        get isAdmin() {
            if (!this.currentUser) return false;
            const rolRaw = this.currentUser.rol;
            const rolNombre = typeof rolRaw === 'object' && rolRaw !== null
                ? String(rolRaw.nombre_rol || '')
                : String(rolRaw || '');
            const permisos = Array.isArray(this.currentUser.permisos)
                ? this.currentUser.permisos
                : (Array.isArray(rolRaw?.permisos) ? rolRaw.permisos : []);
            const idRol = Number(this.currentUser.id_rol || rolRaw?.rol_id || 0);

            return idRol === 1
                || rolNombre.toLowerCase().includes('admin')
                || permisos.includes('*')
                || permisos.includes('usuarios.gestionar');
        },

        get canAccessBusinessInfo() {
            if (!this.currentUser) return false;
            return this.isAdmin || this.hasPermission('empresa.gestionar');
        },

        get canAccessPOS() {
            return this.canAccessTab('pos');
        },

        hasPermission(permiso) {
            if (!this.currentUser) return false;
            if (this.isAdmin) return true;
            const permisos = Array.isArray(this.currentUser.permisos)
                ? this.currentUser.permisos
                : (Array.isArray(this.currentUser.rol?.permisos) ? this.currentUser.rol.permisos : []);
            if (permisos.includes('*')) return true;
            return permisos.includes(permiso);
        },

        canAccessTab(tabId) {
            if (!this.currentUser) return false;
            if (this.isAdmin) return true;
            const rolRaw = this.currentUser.rol;
            const rolNombre = typeof rolRaw === 'object' && rolRaw !== null
                ? String(rolRaw.nombre_rol || '').toLowerCase()
                : String(rolRaw || '').toLowerCase();
            switch (tabId) {
                case 'dashboard':
                    return this.hasPermission('dashboard.ver') || this.hasPermission('usuarios.gestionar');
                case 'pos':
                    return this.hasPermission('pos.acceso') || this.hasPermission('ventas.crear') || rolNombre.includes('cajer') || rolNombre.includes('ventas');
                case 'productos':
                    return this.hasPermission('productos.ver') || this.hasPermission('productos.gestionar') || this.hasPermission('inventario.gestionar');
                case 'categorias':
                    return this.hasPermission('categorias.ver') || this.hasPermission('categorias.gestionar') || this.hasPermission('inventario.gestionar');
                case 'ventas':
                case 'libro-diario':
                    return this.hasPermission('ventas.ver') || this.hasPermission('pos.acceso') || this.hasPermission('ventas.crear');
                case 'proveedores':
                case 'cuentas-por-pagar':
                    return this.isAdmin || this.hasPermission('proveedores.gestionar');
                case 'caja':
                    return this.hasPermission('cajas.gestionar') || this.hasPermission('pos.acceso') || rolNombre.includes('cajer');
                case 'inventario':
                    return this.hasPermission('inventario.gestionar') || this.hasPermission('productos.gestionar');
                case 'usuarios':
                    return this.hasPermission('usuarios.gestionar');
                case 'bitacora':
                    return this.hasPermission('usuarios.gestionar') || this.hasPermission('bitacoras.ver');
                default:
                    return true;
            }
        },

        get visibleNavItems() {
            return this.navItems.filter(item => this.canAccessTab(item.id));
        },

        get lowStockProducts() {
            return this.productos.filter(p => Number(p.existencia_bodega) <= Number(p.existencia_minima));
        },

        get stats() {
            const totalVentasMonto = (this.ventas || []).reduce((sum, v) => sum + (Number(v.estado) !== 0 ? Number(v.total_venta || 0) : 0), 0);
            const totalUnidades = (this.productos || []).reduce((sum, p) => sum + Number(p.existencia_bodega || 0), 0);
            return {
                totalVentasMonto: totalVentasMonto > 0 ? totalVentasMonto : (this.dashboardData?.stats?.totalVentasMonto || 0),
                totalVentasCount: (this.ventas || []).length || (this.dashboardData?.stats?.totalVentasCount || 0),
                totalProductos: (this.productos || []).length || (this.dashboardData?.stats?.totalProductos || 0),
                totalUnidades: totalUnidades > 0 ? totalUnidades : (this.dashboardData?.stats?.totalUnidades || 0),
                totalPerdidasMermasMes: this.perdidasMermasMes || 0
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

        getVentaLocalDate(dateStr) {
            if (!dateStr) return '';
            let normalized = String(dateStr).trim();
            if (/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?$/.test(normalized)) {
                normalized = normalized.replace(' ', 'T') + 'Z';
            }
            const d = new Date(normalized);
            if (isNaN(d.getTime())) {
                return normalized.split('T')[0].split(' ')[0];
            }
            const pad = n => String(n).padStart(2, '0');
            return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
        },

        get filteredVentas() {
            return this.ventas.filter(v => {
                // 1. Filtro por número de comprobante, cliente o referencia
                if (this.searchVenta) {
                    const term = this.searchVenta.toLowerCase().trim();
                    const codeMatch = v.codigo_venta && v.codigo_venta.toLowerCase().includes(term);
                    const clientMatch = (v.cliente_nombre && v.cliente_nombre.toLowerCase().includes(term)) ||
                        (v.cliente && v.cliente.nombre_apellido_cliente && v.cliente.nombre_apellido_cliente.toLowerCase().includes(term));
                    const refMatch = v.referencia_transferencia && v.referencia_transferencia.toLowerCase().includes(term);
                    if (!codeMatch && !clientMatch && !refMatch) {
                        return false;
                    }
                }

                // 2. Filtro por Usuario / Cajero
                if (this.ventaUsuarioFilter) {
                    if (Number(v.id_usuario) !== Number(this.ventaUsuarioFilter)) {
                        return false;
                    }
                }

                // 3. Filtro por Rango de Fechas (Desde y Hasta) ajustado a fecha local del comprobante
                if (this.ventaFechaDesde || this.ventaFechaHasta) {
                    const dateOnly = this.getVentaLocalDate ? this.getVentaLocalDate(v.fecha_hora_venta) : (v.fecha_hora_venta ? String(v.fecha_hora_venta).slice(0, 10) : '');

                    if (this.ventaFechaDesde && dateOnly < this.ventaFechaDesde) {
                        return false;
                    }
                    if (this.ventaFechaHasta && dateOnly > this.ventaFechaHasta) {
                        return false;
                    }
                }

                return true;
            });
        },

        get filteredVentasTotal() {
            return this.filteredVentas
                .filter(v => Number(v.estado) !== 0)
                .reduce((sum, v) => sum + Number(v.total_venta || 0), 0);
        },

        get hasActiveVentaFilters() {
            return Boolean(this.searchVenta || this.ventaFechaDesde || this.ventaFechaHasta || this.ventaUsuarioFilter);
        },

        get ventasUsuarios() {
            const map = new Map();
            if (Array.isArray(this.usuarios)) {
                this.usuarios.forEach(u => {
                    if (u && u.usuario_id) {
                        map.set(Number(u.usuario_id), {
                            usuario_id: Number(u.usuario_id),
                            nombre_apellido: u.nombre_apellido || ('Usuario #' + u.usuario_id)
                        });
                    }
                });
            }
            if (Array.isArray(this.ventas)) {
                this.ventas.forEach(v => {
                    const uid = Number(v.id_usuario);
                    if (uid && !map.has(uid)) {
                        map.set(uid, {
                            usuario_id: uid,
                            nombre_apellido: (v.usuario && v.usuario.nombre_apellido) ? v.usuario.nombre_apellido : ('Usuario #' + uid)
                        });
                    }
                });
            }
            return Array.from(map.values()).sort((a, b) => a.nombre_apellido.localeCompare(b.nombre_apellido));
        },

        get cartSubtotal() {
            return this.cart.reduce((sum, item) => sum + item.subtotal_venta_detalle, 0);
        },

        get cartTotal() {
            if (this.posSale && this.posSale.tipo_descuento === 'porcentaje') {
                const pct = Math.min(100, Math.max(0, Number(this.posSale.descuento_porcentaje) || 0));
                this.posSale.descuento_venta = Number(((this.cartSubtotal * pct) / 100).toFixed(2));
            }
            const total = this.cartSubtotal - (this.posSale.descuento_venta || 0);
            return Math.max(0, total);
        },

        setDiscountType(type) {
            this.posSale.tipo_descuento = type;
            if (type === 'porcentaje') {
                if (this.cartSubtotal > 0 && this.posSale.descuento_venta > 0 && !this.posSale.descuento_porcentaje) {
                    this.posSale.descuento_porcentaje = Number(((this.posSale.descuento_venta / this.cartSubtotal) * 100).toFixed(1));
                }
                this.updatePercentDiscount();
            } else {
                this.posSale.descuento_porcentaje = 0;
            }
        },

        applyQuickPercent(pct) {
            this.posSale.tipo_descuento = 'porcentaje';
            this.posSale.descuento_porcentaje = pct;
            this.updatePercentDiscount();
        },

        updatePercentDiscount() {
            const pct = Math.min(100, Math.max(0, Number(this.posSale.descuento_porcentaje) || 0));
            this.posSale.descuento_venta = Number(((this.cartSubtotal * pct) / 100).toFixed(2));
        },

        // Retorna el título descriptivo oficial de la operación para el comprobante / ticket
        getReceiptOperationTitle(receipt) {
            if (!receipt) return 'VENTA AL CONTADO';
            if (receipt.tipo_operacion) return receipt.tipo_operacion.toUpperCase();
            const metodo = (receipt.metodo_pago || '').trim().toLowerCase();
            if (metodo === 'transferencia') {
                return 'DEPÓSITO A CUENTA';
            } else if (metodo === 'tarjeta') {
                return 'PAGO CON TARJETA';
            } else if (metodo === 'efectivo') {
                return 'VENTA AL CONTADO';
            }
            return 'COMPROBANTE DE VENTA';
        },

        // Cálculo reactivo del cambio en efectivo
        get cashChange() {
            if (this.posSale.metodo_pago !== 'Efectivo') return 0;
            const recibido = Number(this.posSale.monto_recibido);
            if (!this.posSale.monto_recibido || isNaN(recibido)) return 0;
            return Math.max(0, recibido - this.cartTotal);
        },

        get cashShortage() {
            if (this.posSale.metodo_pago !== 'Efectivo') return 0;
            const recibido = Number(this.posSale.monto_recibido);
            if (!this.posSale.monto_recibido || isNaN(recibido)) return 0;
            return Math.max(0, this.cartTotal - recibido);
        },

        setExactCash() {
            this.posSale.monto_recibido = Number(this.cartTotal.toFixed(2));
        },

        setCashReceived(amount) {
            this.posSale.monto_recibido = Number(amount);
        },

        // --- Lazy Loading de Pestañas ---
        loadedTabs: [],

        async loadTab(tab, force = false) {
            if (!this.canAccessTab(tab)) {
                const firstAllowed = (this.visibleNavItems && this.visibleNavItems.length > 0) ? this.visibleNavItems[0].id : 'pos';
                if (tab !== firstAllowed) {
                    this.currentTab = firstAllowed;
                    return;
                }
            }

            if (!force && this.loadedTabs.includes(tab)) {
                return;
            }

            this.loading = true;
            try {
                switch (tab) {
                    case 'dashboard':
                        await Promise.allSettled([
                            this.fetchDashboardData(),
                            this.fetchVentas(),
                            this.fetchProductos(),
                            this.fetchInventario()
                        ]);
                        this.initDashboardCharts();
                        break;
                    case 'pos':
                        await Promise.all([
                            this.fetchProductos(),
                            this.fetchCategorias(),
                            this.fetchVentas(),
                            this.fetchEmpresa(),
                            this.fetchVentasEspera()
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
                        await Promise.all([
                            this.fetchVentas(),
                            this.fetchUsuarios().catch(() => {})
                        ]);
                        break;
                    case 'libro-diario':
                        await Promise.all([
                            this.fetchLibroDiario(),
                            this.fetchEmpresa(),
                            this.fetchVentas().catch(() => {})
                        ]);
                        break;
                    case 'caja':
                        await Promise.all([
                            this.fetchVentas(),
                            this.fetchBitacoras().catch(() => {})
                        ]);
                        break;
                    case 'proveedores':
                    case 'cuentas-por-pagar':
                        await Promise.all([
                            this.fetchProveedores(),
                            this.fetchCuentasPorPagar(),
                            this.fetchCxPKPIs()
                        ]);
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

            // Si está autenticado, validar que currentTab sea accesible y cargar datos
            if (this.isAuthenticated) {
                if (!this.canAccessTab(this.currentTab)) {
                    this.currentTab = (this.visibleNavItems && this.visibleNavItems.length > 0) ? this.visibleNavItems[0].id : 'productos';
                }
                if (this.currentTab === 'cuentas-por-pagar') {
                    this.cxpActiveSubTab = 'cuentas';
                } else if (this.currentTab === 'proveedores') {
                    this.cxpActiveSubTab = 'proveedores';
                }
                this.loadTab(this.currentTab);
                this.fetchEmpresa();
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

            // Observar ventas para actualizar gráficos del dashboard en tiempo real
            this.$watch('ventas', () => {
                if (this.currentTab === 'dashboard') {
                    this.renderDashboardCharts();
                }
            });

            // Observar cambios de pestaña para cargar datos bajo demanda
            this.$watch('currentTab', (newTab) => {
                if (this.isAuthenticated) {
                    if (!this.canAccessTab(newTab)) {
                        this.currentTab = (this.visibleNavItems && this.visibleNavItems.length > 0) ? this.visibleNavItems[0].id : 'productos';
                        return;
                    }
                    if (newTab === 'cuentas-por-pagar') {
                        this.cxpActiveSubTab = 'cuentas';
                    } else if (newTab === 'proveedores') {
                        this.cxpActiveSubTab = 'proveedores';
                    }
                    this.loadTab(newTab);
                    if (newTab === 'dashboard') {
                        if (this.fetchDashboardData) this.fetchDashboardData();
                        if (this.fetchInventario) this.fetchInventario();
                        this.initDashboardCharts();
                    }
                    this.$nextTick(() => {
                        if (window.lucide) {
                            window.lucide.createIcons();
                        }
                    });
                }
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
                const [venRes, cajRes, movCajRes, turnosRes] = await Promise.all([
                    this.apiFetch('/api/ventas').catch(() => ({ ok: false })),
                    this.apiFetch('/api/cajas').catch(() => ({ ok: false })),
                    this.apiFetch('/api/caja-movimientos-venta').catch(() => ({ ok: false })),
                    this.apiFetch('/api/caja-operaciones').catch(() => ({ ok: false }))
                ]);
                if (venRes && venRes.ok) {
                    const venData = await venRes.json();
                    this.ventas = Array.isArray(venData) ? venData : [];
                }
                if (cajRes && cajRes.ok) {
                    const cajData = await cajRes.json();
                    this.cajas = Array.isArray(cajData) ? cajData.sort((a, b) => Number(a.caja_id) - Number(b.caja_id)) : [];
                }
                if (movCajRes && movCajRes.ok) {
                    const movData = await movCajRes.json();
                    this.cajaMovimientos = Array.isArray(movData) ? movData : [];
                }
                if (turnosRes && turnosRes.ok) {
                    const turnosData = await turnosRes.json();
                    this.turnos = Array.isArray(turnosData) ? turnosData : [];
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

        openEmpresaModal() {
            if (this.empresa) {
                this.empresaForm = {
                    nombre_comercial: this.empresa.nombre_comercial || '',
                    razon_social: this.empresa.razon_social || '',
                    numero_ruc: this.empresa.numero_ruc || '',
                    telefono_contacto: this.empresa.telefono_contacto || '',
                    correo_contacto: this.empresa.correo_contacto || '',
                    direccion_fisica: this.empresa.direccion_fisica || '',
                    mensaje_pie_ticket: this.empresa.mensaje_pie_ticket || '',
                    moneda_simbolo: this.empresa.moneda_simbolo || 'C$',
                    regimen_tributario: this.empresa.regimen_tributario || 'Cuota Fija',
                    techo_mensual_cuota_fija: this.empresa.techo_mensual_cuota_fija !== undefined ? Number(this.empresa.techo_mensual_cuota_fija) : 100000.00
                };
            }
            this.showEmpresaModal = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        async saveEmpresa() {
            if (!this.empresaForm.nombre_comercial?.trim()) {
                this.notify('Campo Requerido', 'El nombre comercial de la empresa es obligatorio.', 'warning');
                return;
            }
            if (!this.empresaForm.telefono_contacto?.trim()) {
                this.notify('Campo Requerido', 'El teléfono de contacto es obligatorio.', 'warning');
                return;
            }
            if (!this.empresaForm.direccion_fisica?.trim()) {
                this.notify('Campo Requerido', 'La dirección física del establecimiento es obligatoria.', 'warning');
                return;
            }
            if (!this.empresaForm.moneda_simbolo?.trim()) {
                this.empresaForm.moneda_simbolo = 'C$';
            }

            this.isSavingEmpresa = true;
            try {
                const payload = { ...this.empresaForm };
                if (!payload.numero_ruc || !payload.numero_ruc.trim()) {
                    delete payload.numero_ruc;
                }

                const res = await this.apiFetch('/api/empresa', {
                    method: 'PUT',
                    body: JSON.stringify(payload)
                });
                const data = await res.json().catch(() => ({}));

                if (!res.ok || data.success === false) {
                    let errMsg = data.message || 'No se pudieron actualizar los datos del negocio.';
                    if (data.errors) {
                        const errorList = Object.values(data.errors).flat().join('<br>');
                        errMsg = errorList || errMsg;
                    }
                    throw new Error(errMsg);
                }

                this.empresa = data.empresa || { ...this.empresaForm };
                if (!this.empresaForm.numero_ruc?.trim()) {
                    this.empresa.numero_ruc = '';
                }
                this.receiptEmpresa = this.empresa;
                this.showEmpresaModal = false;

                if (typeof this.fetchTechoFiscal === 'function') {
                    this.fetchTechoFiscal();
                }

                this.notify('¡Datos Guardados!', data.message || 'Los datos del negocio han sido actualizados con éxito.', 'success');
            } catch (error) {
                this.notify('Error al Guardar', error.message, 'error');
            } finally {
                this.isSavingEmpresa = false;
            }
        },

        async fetchEmpresa() {
            try {
                const res = await this.apiFetch('/api/empresa');
                if (res.ok) {
                    const data = await res.json();
                    this.empresa = data;
                    this.receiptEmpresa = data;
                    if (data && data.nombre_comercial && !this.empresaForm.nombre_comercial) {
                        this.empresaForm = {
                            nombre_comercial: data.nombre_comercial || '',
                            razon_social: data.razon_social || '',
                            numero_ruc: data.numero_ruc || '',
                            telefono_contacto: data.telefono_contacto || '',
                            correo_contacto: data.correo_contacto || '',
                            direccion_fisica: data.direccion_fisica || '',
                            mensaje_pie_ticket: data.mensaje_pie_ticket || '',
                            moneda_simbolo: data.moneda_simbolo || 'C$',
                            regimen_tributario: data.regimen_tributario || 'Cuota Fija',
                            techo_mensual_cuota_fija: data.techo_mensual_cuota_fija !== undefined ? Number(data.techo_mensual_cuota_fija) : 100000.00
                        };
                    }
                }
            } catch (e) {
                console.error('Error cargando datos de empresa:', e);
            }
        },

        // Getters de Movimientos de Caja
        get filteredCajaMovimientos() {
            let list = Array.isArray(this.cajaMovimientos) ? this.cajaMovimientos : [];
            const term = (this.cajaSearch || '').trim().toLowerCase();
            const filtroTipo = this.cajaMovimientoFiltro || 'todos';
            const filtroMetodo = (this.cajaMetodoPagoFiltro || '').toLowerCase();
            const filtroUsuario = this.cajaUsuarioFiltro || '';

            // 1. Filtrado de alcance por rol (Cajeros solo ven su turno activo)
            if (!this.isAdmin) {
                if (!this.turnoActivo) {
                    return [];
                }
                const turnoId = Number(this.turnoActivo.caja_operacion_id);
                list = list.filter(m => Number(m.id_caja_operacion) === turnoId);
            } else {
                // Administrador: puede filtrar por su turno activo o ver historial general
                if (this.cajaHistorialAlcance === 'mi_turno' && this.turnoActivo) {
                    const turnoId = Number(this.turnoActivo.caja_operacion_id);
                    list = list.filter(m => Number(m.id_caja_operacion) === turnoId);
                }
            }

            // 2. Filtro por Cajero / Usuario (Solo Administrador)
            if (this.isAdmin && filtroUsuario) {
                list = list.filter(m => {
                    const idUserVenta = m.venta?.id_usuario;
                    const idUserTurno = m.turno?.id_usuario;
                    return Number(idUserVenta) === Number(filtroUsuario) || Number(idUserTurno) === Number(filtroUsuario);
                });
            }

            // 3. Filtro por Método de Pago
            if (filtroMetodo) {
                list = list.filter(m => {
                    const metodo = (this.getMovimientoMetodoPago(m) || 'efectivo').toLowerCase();
                    if (filtroMetodo === 'efectivo') return metodo === 'efectivo';
                    if (filtroMetodo === 'transferencia') return metodo === 'transferencia';
                    if (filtroMetodo === 'tarjeta') return ['tarjeta', 'debito', 'credito'].includes(metodo);
                    return metodo.includes(filtroMetodo);
                });
            }

            // 4. Filtro por tipo de movimiento y buscador
            return list.filter(m => {
                if (filtroTipo === 'ingreso') {
                    if (m.id_venta !== null || Number(m.monto_movimiento) <= 0) return false;
                } else if (filtroTipo === 'egreso') {
                    if (m.id_venta !== null || Number(m.monto_movimiento) >= 0) return false;
                } else if (filtroTipo === 'venta') {
                    if (m.id_venta === null) return false;
                }

                if (term) {
                    const idStr = String(m.caja_movimiento_venta_id || '');
                    const codigoMov = this.getMovimientoCodigo(m).toLowerCase();
                    const codigoClean = codigoMov.replace(/-/g, '');
                    const cajaDesc = m.caja && m.caja.descripcion_caja ? m.caja.descripcion_caja.toLowerCase() : '';
                    const ventaCod = m.venta && m.venta.codigo_venta ? m.venta.codigo_venta.toLowerCase() : '';
                    const concepto = this.getMovimientoConcepto(m).toLowerCase();
                    const usuarioNom = this.getMovimientoUsuario(m).toLowerCase();
                    const metodoNom = this.getMovimientoMetodoPago(m).toLowerCase();
                    return idStr.includes(term) || codigoMov.includes(term) || codigoClean.includes(term) || cajaDesc.includes(term) || ventaCod.includes(term) || concepto.includes(term) || usuarioNom.includes(term) || metodoNom.includes(term);
                }

                return true;
            });
        },

        get resumenCajaMovimientos() {
            const turno = this.turnoActivo;
            const list = Array.isArray(this.cajaMovimientos) ? this.cajaMovimientos : [];
            let ingresosExtra = 0;
            let egresosGastos = 0;
            let countIngresos = 0;
            let countEgresos = 0;

            if (!turno) {
                return {
                    ingresosExtra: 0,
                    egresosGastos: 0,
                    countIngresos: 0,
                    countEgresos: 0
                };
            }

            list.forEach(m => {
                // Sumar estrictamente los movimientos extraordinarios del turno activo actual
                if (Number(m.id_caja_operacion) === Number(turno.caja_operacion_id) && m.id_venta === null) {
                    const monto = Number(m.monto_movimiento || 0);
                    if (monto > 0) {
                        ingresosExtra += monto;
                        countIngresos++;
                    } else if (monto < 0) {
                        egresosGastos += Math.abs(monto);
                        countEgresos++;
                    }
                }
            });

            return {
                ingresosExtra,
                egresosGastos,
                countIngresos,
                countEgresos
            };
        },

        getMovimientoConcepto(mov) {
            if (!mov) return 'Movimiento';
            if (mov.venta && mov.venta.codigo_venta) {
                return (Number(mov.monto_movimiento) < 0 ? 'Devolución Factura ' : 'Cobro Factura ') + mov.venta.codigo_venta;
            }
            if (mov.id_venta) {
                return (Number(mov.monto_movimiento) < 0 ? 'Devolución Venta #' : 'Venta #') + mov.id_venta;
            }

            if (Array.isArray(this.bitacoras)) {
                const needle = `Movimiento extraordinario #${mov.caja_movimiento_venta_id}`;
                const b = this.bitacoras.find(bit => bit.descripcion_bitacora && bit.descripcion_bitacora.includes(needle));
                if (b) {
                    const motivoIndex = b.descripcion_bitacora.indexOf('Motivo:');
                    if (motivoIndex !== -1) {
                        return b.descripcion_bitacora.substring(motivoIndex + 7).trim();
                    }
                    return b.descripcion_bitacora;
                }
            }

            return Number(mov.monto_movimiento) >= 0 ? 'Ingreso de Efectivo / Sencillo' : 'Egreso / Gasto Menor';
        },

        getMovimientoCodigo(mov) {
            if (!mov) return '';
            if (mov.codigo_movimiento) return mov.codigo_movimiento;
            let normalized = String(mov.fecha_hora_movimiento || '').trim();
            if (/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?(\.\d+)?$/.test(normalized)) {
                normalized = normalized.replace(' ', 'T') + 'Z';
            }
            const d = new Date(normalized);
            const target = isNaN(d.getTime()) ? new Date() : d;
            const pad = (n, len = 2) => String(n).padStart(len, '0');
            const day = pad(target.getDate(), 2);
            const month = pad(target.getMonth() + 1, 2);
            const year = String(target.getFullYear()).slice(-2);
            const caja = pad(mov.id_caja || 1, 3);
            const id = pad(mov.caja_movimiento_venta_id || 0, 5);

            return `${day}${month}${year}-${caja}-${id}`;
        },

        getMovimientoMetodoPago(mov) {
            if (!mov) return 'Efectivo';
            if (mov.venta && mov.venta.metodo_pago) {
                return mov.venta.metodo_pago;
            }
            return 'Efectivo';
        },

        getMovimientoUsuario(mov) {
            if (!mov) return 'N/A';
            if (mov.venta && mov.venta.usuario && mov.venta.usuario.nombre_apellido) {
                return mov.venta.usuario.nombre_apellido;
            }
            if (mov.turno && mov.turno.usuario && mov.turno.usuario.nombre_apellido) {
                return mov.turno.usuario.nombre_apellido;
            }
            if (Array.isArray(this.usuarios) && this.usuarios.length > 0) {
                const uid = mov.venta?.id_usuario || mov.turno?.id_usuario;
                if (uid) {
                    const found = this.usuarios.find(u => Number(u.usuario_id) === Number(uid));
                    if (found) return found.nombre_apellido;
                }
            }
            return (this.currentUser && this.currentUser.nombre_apellido) ? this.currentUser.nombre_apellido : 'Usuario';
        },

        openCajaMovimientoModal(tipo = 'Egreso') {
            const turno = this.turnoActivo;
            if (!turno) {
                this.notify('Sin Turno Activo', 'Debes tener un turno abierto para registrar movimientos de caja.', 'warning');
                return;
            }

            this.cajaMovimientoForm = {
                id_caja: turno.id_caja,
                id_caja_operacion: turno.caja_operacion_id,
                tipo_movimiento: tipo,
                monto: '',
                justificacion: ''
            };

            this.showCajaMovimientoModal = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        setCajaMovimientoJustificacion(texto) {
            this.cajaMovimientoForm.justificacion = texto;
        },

        async saveCajaMovimiento() {
            if (this.isSavingCajaMovimiento) return;

            const form = this.cajaMovimientoForm;
            const montoNum = Number(form.monto);

            if (isNaN(montoNum) || montoNum <= 0) {
                this.notify('Monto requerido', 'El monto del movimiento debe ser un importe mayor a cero.', 'warning');
                return;
            }

            const just = (form.justificacion || '').trim();
            if (just.length < 3) {
                this.notify('Motivo obligatorio', 'Debe ingresar una justificación o motivo de al menos 3 caracteres.', 'warning');
                return;
            }

            this.isSavingCajaMovimiento = true;

            try {
                const payload = {
                    tipo_movimiento: form.tipo_movimiento,
                    monto: montoNum,
                    justificacion: just
                };

                if (form.id_caja) {
                    payload.id_caja = Number(form.id_caja);
                }
                if (form.id_caja_operacion) {
                    payload.id_caja_operacion = Number(form.id_caja_operacion);
                }

                const res = await this.apiFetch('/api/caja-movimientos-venta', {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });

                const data = await res.json();

                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Error al registrar el movimiento en caja.');
                }

                this.notify('¡Movimiento Registrado!', `${form.tipo_movimiento === 'Ingreso' ? 'Ingreso' : 'Egreso'} de C$ ${montoNum.toFixed(2)} registrado exitosamente.`, 'success');

                this.showCajaMovimientoModal = false;

                await Promise.all([
                    this.fetchVentas(),
                    this.fetchBitacoras().catch(() => {}),
                    this.fetchDashboardData ? this.fetchDashboardData() : Promise.resolve()
                ]);

            } catch (error) {
                console.error('Error registrando movimiento de caja:', error);
                this.notify('No se pudo registrar', error.message || 'Ocurrió un error al procesar la operación.', 'error');
            } finally {
                this.isSavingCajaMovimiento = false;
            }
        },

        getTurnoIdForCaja(cajaId) {
            const t = this.getTurnoForCaja(cajaId);
            return t ? t.caja_operacion_id : null;
        },

        getTurnoForCaja(cajaId) {
            if (!Array.isArray(this.turnos)) return null;
            return this.turnos.find(turno => Number(turno.id_caja) === Number(cajaId) && !turno.fecha_hora_cierre && (turno.estado === undefined || Number(turno.estado) === 1)) || null;
        },

        isCajaMine(cajaId) {
            const t = this.getTurnoForCaja(cajaId);
            if (!t) return false;
            const currentUserId = this.currentUser?.usuario_id;
            return Number(t.id_usuario) === Number(currentUserId);
        },

        getCajaCashierName(cajaId) {
            const t = this.getTurnoForCaja(cajaId);
            if (!t) return null;
            if (t.usuario?.nombre_usuario) return t.usuario.nombre_usuario;
            if (t.usuario?.nombre_apellido) return t.usuario.nombre_apellido;
            const u = (this.usuarios || []).find(usr => Number(usr.usuario_id) === Number(t.id_usuario));
            return u ? (u.nombre_usuario || u.nombre_apellido) : `Usuario #${t.id_usuario}`;
        },

        get cajaCierreDiferencia() {
            if (!this.cajaCierreData || this.cajaCierreForm.monto_cierre === '' || this.cajaCierreForm.monto_cierre === null) {
                return null;
            }
            const contado = Number(this.cajaCierreForm.monto_cierre);
            if (isNaN(contado)) return null;
            const esperado = Number(this.cajaCierreData.arqueo?.monto_esperado ?? this.cajaCierreData.monto_esperado ?? 0);
            return Number((contado - esperado).toFixed(2));
        },

        openCajaAperturaModal(cajaId = null) {
            if (this.turnoActivo) {
                this.notify('Turno ya activo', `Ya tienes un turno activo en la caja #${this.turnoActivo.id_caja}. Debes cerrarlo antes de abrir uno nuevo.`, 'warning');
                return;
            }

            let targetCaja = cajaId;
            if (!targetCaja) {
                const cerrada = (this.cajas || []).find(c => c.estado_caja !== 'Abierta' && Number(c.estado) === 1);
                targetCaja = cerrada ? cerrada.caja_id : ((this.cajas && this.cajas[0]) ? this.cajas[0].caja_id : '');
            }

            this.cajaAperturaForm = {
                id_caja: targetCaja || '',
                monto_apertura: '0.00'
            };
            this.showCajaAperturaModal = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        async saveCajaApertura() {
            if (this.isSavingCajaApertura) return;
            if (!this.cajaAperturaForm.id_caja) {
                this.notify('Seleccione una Caja', 'Debe seleccionar una caja física para aperturar el turno.', 'warning');
                return;
            }

            const monto = Number(this.cajaAperturaForm.monto_apertura);
            if (isNaN(monto) || monto < 0) {
                this.notify('Monto Inválido', 'El fondo inicial de apertura debe ser un número mayor o igual a 0.', 'warning');
                return;
            }

            this.isSavingCajaApertura = true;
            try {
                const res = await this.apiFetch('/api/caja-operaciones', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id_caja: Number(this.cajaAperturaForm.id_caja),
                        monto_apertura: Number(monto.toFixed(2))
                    })
                });

                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.message || 'Error al aperturar el turno.');
                }

                this.showCajaAperturaModal = false;
                await Promise.all([
                    this.fetchVentas(),
                    this.fetchBitacoras ? this.fetchBitacoras().catch(() => {}) : Promise.resolve(),
                    this.fetchDashboardData ? this.fetchDashboardData() : Promise.resolve()
                ]);

                this.notify('¡Turno Aperturado!', 'La caja ha sido abierta exitosamente.', 'success', 2500);

            } catch (err) {
                console.error('Error al aperturar turno:', err);
                this.notify('No se pudo abrir el turno', err.message || 'Ocurrió un error inesperado.', 'error');
            } finally {
                this.isSavingCajaApertura = false;
            }
        },

        async openCajaCierreModal(turnoId = null) {
            let targetTurnoId = turnoId;
            if (!targetTurnoId && this.turnoActivo) {
                targetTurnoId = this.turnoActivo.caja_operacion_id;
            }

            if (!targetTurnoId) {
                this.notify('Sin Turno Abierto', 'No se encontró ningún turno activo para cerrar.', 'info');
                return;
            }

            this.loadingArqueo = true;
            this.cajaCierreData = null;
            this.cajaCierreForm = {
                monto_cierre: '',
                observacion_cierre: ''
            };
            this.showCajaCierreModal = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });

            try {
                const res = await this.apiFetch(`/api/caja-operaciones/${targetTurnoId}`);
                if (!res.ok) {
                    throw new Error('No se pudo obtener el arqueo del turno.');
                }
                const data = await res.json();
                this.cajaCierreData = data;
            } catch (err) {
                console.error('Error cargando arqueo:', err);
                this.notify('Error al consultar arqueo', err.message || 'No se pudieron calcular los datos del arqueo.', 'error');
                this.showCajaCierreModal = false;
            } finally {
                this.loadingArqueo = false;
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            }
        },

        async saveCajaCierre() {
            if (this.isSavingCajaCierre || !this.cajaCierreData) return;

            const contado = Number(this.cajaCierreForm.monto_cierre);
            if (this.cajaCierreForm.monto_cierre === '' || isNaN(contado) || contado < 0) {
                this.notify('Efectivo Contado Requerido', 'Debe ingresar el monto total de efectivo físico contado en caja.', 'warning');
                return;
            }

            const diff = this.cajaCierreDiferencia;
            if (diff !== null && diff !== 0 && (!this.cajaCierreForm.observacion_cierre || !this.cajaCierreForm.observacion_cierre.trim())) {
                this.notify('Justificación Obligatoria', 'Existe un descuadre en el arqueo (' + (diff > 0 ? '+' : '') + this.formatCurrency(diff) + '). Debe ingresar una justificación.', 'warning');
                return;
            }

            // Validar si existen ventas en espera pendientes asociadas a la caja del turno a cerrar
            const targetCajaId = this.cajaCierreData?.id_caja;
            let ventasPendientes = [];
            try {
                const resEspera = await this.apiFetch(targetCajaId ? `/api/ventas-espera?id_caja=${targetCajaId}` : '/api/ventas-espera');
                if (resEspera.ok) {
                    const dataEspera = await resEspera.json();
                    ventasPendientes = Array.isArray(dataEspera.data) ? dataEspera.data : [];
                }
            } catch (e) {
                console.error('Error verificando ventas en espera:', e);
            }

            let descartarVentasEspera = false;
            if (ventasPendientes.length > 0) {
                if (this.isAdmin) {
                    const nombres = ventasPendientes.map(v => v.identificador_cuenta || `Venta #${v.venta_espera_id}`).slice(0, 3).join(', ') + (ventasPendientes.length > 3 ? '...' : '');
                    const adminConfirm = await Swal.fire({
                        icon: 'warning',
                        title: '¿Descartar ventas en espera?',
                        html: `Esta caja tiene <b>${ventasPendientes.length} venta(s) en espera</b> activa(s) (${nombres}).<br><br>Como Administrador, ¿deseas <b>descartarlas automáticamente</b> para continuar con la liquidación del turno?`,
                        showCancelButton: true,
                        confirmButtonText: 'Sí, Descartar y Continuar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#f59e0b',
                        background: this.darkMode ? '#1e293b' : '#ffffff',
                        color: this.darkMode ? '#fff' : '#0f172a'
                    });
                    if (!adminConfirm.isConfirmed) return;
                    descartarVentasEspera = true;
                    this.notify('Ventas en Espera Descartadas', `Se descartaron ${ventasPendientes.length} venta(s) en espera de esta caja.`, 'info', 3000);
                } else {
                    this.notify('Ventas en Espera Pendientes', `No puedes cerrar el turno porque tienes ${ventasPendientes.length} venta(s) en espera activas. Debes reanudarlas o descartarlas en el POS antes de liquidar.`, 'warning', 5000);
                    return;
                }
            }

            const confirmResult = await Swal.fire({
                icon: 'question',
                title: '¿Confirmar Cierre de Turno?',
                html: `Se registrará el cierre con un efectivo contado de <b>${this.formatCurrency(contado)}</b>.<br>${diff !== 0 ? `<span class="text-amber-500 font-bold">Diferencia: ${diff > 0 ? '+' : ''}${this.formatCurrency(diff)}</span>` : '<span class="text-emerald-500 font-bold">Cuadre exacto</span>'}<br><br>Esta acción bloqueará las operaciones del turno de forma auditada.`,
                showCancelButton: true,
                confirmButtonText: 'Sí, Cerrar Turno',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#e11d48',
                background: this.darkMode ? '#1e293b' : '#ffffff',
                color: this.darkMode ? '#fff' : '#0f172a'
            });

            if (!confirmResult.isConfirmed) return;

            this.isSavingCajaCierre = true;
            try {
                const turnoId = this.cajaCierreData.caja_operacion_id;
                const payload = {
                    monto_cierre: Number(contado.toFixed(2)),
                    observacion_cierre: this.cajaCierreForm.observacion_cierre ? this.cajaCierreForm.observacion_cierre.trim() : null,
                    descartar_ventas_espera: descartarVentasEspera
                };

                const res = await this.apiFetch(`/api/caja-operaciones/${turnoId}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.message || 'Error al cerrar el turno de caja.');
                }

                this.showCajaCierreModal = false;
                if (typeof this.fetchVentasEspera === 'function') {
                    this.fetchVentasEspera().catch(() => {});
                }
                await Promise.all([
                    this.fetchVentas(),
                    this.fetchBitacoras ? this.fetchBitacoras().catch(() => {}) : Promise.resolve(),
                    this.fetchDashboardData ? this.fetchDashboardData() : Promise.resolve()
                ]);

                this.notify('¡Turno Cerrado Exitosamente!', 'El arqueo y cierre del turno han quedado registrados.', 'success', 2800);

            } catch (err) {
                console.error('Error cerrando turno:', err);
                this.notify('Error al cerrar turno', err.message || 'Ocurrió un error inesperado al procesar el cierre.', 'error');
            } finally {
                this.isSavingCajaCierre = false;
            }
        },

        openCajaFormModal(caja = null) {
            if (caja) {
                this.cajaForm = {
                    caja_id: caja.caja_id,
                    descripcion_caja: caja.descripcion_caja || '',
                    tipo_apertura: caja.tipo_apertura || 'Manual',
                    estado: caja.estado !== undefined ? Number(caja.estado) : 1
                };
            } else {
                this.cajaForm = {
                    caja_id: null,
                    descripcion_caja: '',
                    tipo_apertura: 'Manual',
                    estado: 1
                };
            }
            this.showCajaFormModal = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        async saveCaja() {
            if (this.isSavingCaja) return;

            const desc = (this.cajaForm.descripcion_caja || '').trim();
            if (!desc) {
                this.notify('Campo Obligatorio', 'Debe ingresar el nombre o descripción de la caja.', 'warning');
                return;
            }

            this.isSavingCaja = true;
            try {
                const isEdit = Boolean(this.cajaForm.caja_id);
                const url = isEdit ? `/api/cajas/${this.cajaForm.caja_id}` : '/api/cajas';
                const method = isEdit ? 'PUT' : 'POST';
                const payload = {
                    descripcion_caja: desc,
                    tipo_apertura: this.cajaForm.tipo_apertura
                };
                if (isEdit) {
                    payload.estado = this.cajaForm.estado;
                }

                const res = await this.apiFetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.message || (data.errors ? Object.values(data.errors).flat().join('<br>') : 'Error al guardar la caja.'));
                }

                this.showCajaFormModal = false;
                await this.fetchVentas();

                this.notify(isEdit ? '¡Caja Actualizada!' : '¡Caja Creada!', data.message || 'La caja física ha sido guardada exitosamente.', 'success', 2000);

            } catch (err) {
                console.error('Error guardando caja:', err);
                this.notify('No se pudo guardar la caja', err.message || 'Ocurrió un error inesperado.', 'error');
            } finally {
                this.isSavingCaja = false;
            }
        },

        // Recarga completa bajo demanda si el usuario la solicita
        async refreshAll() {
            this.loadedTabs = [];
            await this.loadTab(this.currentTab, true);
        }
    };

    return mergeModules(
        appObj,
        posModule(),
        productosModule(),
        categoriasModule(),
        proveedoresModule(),
        usuariosModule(),
        utilsModule(),
        themeModule(),
        authModule(),
        dashboardModule(),
        fiscalModule()
    );
}

// Exponer la función app globalmente para Alpine.js
window.app = app;
