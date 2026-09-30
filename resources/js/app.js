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
        clientes: [],
        usuarios: [],
        roles: [],
        ventas: [],
        ventasEspera: [],
        cajas: [],
        turnos: [],
        cajaMovimientos: [],
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
            moneda_simbolo: 'C$'
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
                icon: 'shopping-cart',
                badge: () => (this.ventasEspera ? this.ventasEspera.length : 0)
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

        get isAdmin() {
            if (!this.currentUser) return false;
            const rol = String(this.currentUser.rol || '').toLowerCase();
            const permisos = Array.isArray(this.currentUser.permisos) ? this.currentUser.permisos : [];
            return rol.includes('admin') || permisos.includes('*') || permisos.includes('usuarios.gestionar');
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
                totalUnidades: totalUnidades > 0 ? totalUnidades : (this.dashboardData?.stats?.totalUnidades || 0)
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
                // 1. Filtro por número de comprobante, cliente o referencia (RF-21)
                if (this.searchVenta) {
                    const term = this.searchVenta.toLowerCase().trim();
                    const codeMatch = v.codigo_venta && v.codigo_venta.toLowerCase().includes(term);
                    const clientMatch = v.cliente && v.cliente.nombre_apellido_cliente && v.cliente.nombre_apellido_cliente.toLowerCase().includes(term);
                    const refMatch = v.referencia_transferencia && v.referencia_transferencia.toLowerCase().includes(term);
                    if (!codeMatch && !clientMatch && !refMatch) {
                        return false;
                    }
                }

                // 2. Filtro por Usuario / Cajero (RF-21)
                if (this.ventaUsuarioFilter) {
                    if (Number(v.id_usuario) !== Number(this.ventaUsuarioFilter)) {
                        return false;
                    }
                }

                // 3. Filtro por Rango de Fechas (Desde y Hasta) ajustado a fecha local del comprobante (RF-21)
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
                            this.fetchProductos()
                        ]);
                        this.initDashboardCharts();
                        break;
                    case 'pos':
                        await Promise.all([
                            this.fetchProductos(),
                            this.fetchCategorias(),
                            this.fetchClientes(),
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
                    this.cajas = Array.isArray(cajData) ? cajData : [];
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
                    moneda_simbolo: this.empresa.moneda_simbolo || 'C$'
                };
            }
            this.showEmpresaModal = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        async saveEmpresa() {
            if (!this.empresaForm.nombre_comercial?.trim()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo Requerido',
                    text: 'El nombre comercial de la empresa es obligatorio.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
            }
            if (!this.empresaForm.telefono_contacto?.trim()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo Requerido',
                    text: 'El teléfono de contacto es obligatorio.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
            }
            if (!this.empresaForm.direccion_fisica?.trim()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo Requerido',
                    text: 'La dirección física del establecimiento es obligatoria.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
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

                await Swal.fire({
                    icon: 'success',
                    title: '¡Datos Guardados!',
                    text: data.message || 'Los datos del negocio han sido actualizados con éxito.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al Guardar',
                    html: error.message,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
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
                            moneda_simbolo: data.moneda_simbolo || 'C$'
                        };
                    }
                }
            } catch (e) {
                console.error('Error cargando datos de empresa:', e);
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
        clientesModule(),
        usuariosModule(),
        utilsModule(),
        themeModule(),
        authModule(),
        dashboardModule()
    );
}

// Exponer la función app globalmente para Alpine.js
window.app = app;
