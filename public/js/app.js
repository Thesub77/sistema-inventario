/**
 * ==============================================================================
 * FACTURASTOCK PRO - CAPA DE LÓGICA FRONTEND
 * Archivo: public/js/app.js
 * Descripción: Controlador principal modular para la interfaz reactiva con Alpine.js
 * ==============================================================================
 */

// 1. Inicialización Inmediata de Tema (Anti-parpadeo FOUC)
(function () {
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

// 3.5. Módulo de Dashboard Analítico (RF-13, RF-26, RF-30, RF-31, RF-32, RF-33)
function dashboardModule() {
    return {
        dashboardData: null,
        dashboardPeriodo: 'hoy',
        dashboardVentasView: 'dias',
        bajaRotacionFiltro: 'todos',
        stockAlertFiltro: 'todos',
        chartVentasInstance: null,
        chartTopInstance: null,

        // Carga optimizada de métricas calculadas en el servidor (Issue #19)
        async fetchDashboardData() {
            try {
                const localDate = new Date().toLocaleDateString('en-CA');
                const res = await this.apiFetch(`/api/dashboard/resumen?fecha=${localDate}`);
                if (res.ok) {
                    const json = await res.json();
                    if (json.success) {
                        this.dashboardData = json;
                        if (this.currentTab === 'dashboard') {
                            this.initDashboardCharts();
                        }
                    }
                } else {
                    console.warn('Dashboard resumen API respondió con estado:', res.status);
                }
            } catch (error) {
                console.error('Error cargando métricas optimizadas del Dashboard:', error);
            }
        },

        // Inicializar gráficos del Dashboard cuando se carga la pestaña (con reintento seguro para CDN)
        initDashboardCharts() {
            this.$nextTick(() => {
                let attempts = 0;
                const checkAndRender = () => {
                    const canvasVentas = document.getElementById('chartVentas');
                    const canvasTop = document.getElementById('chartTopProductos');
                    if (typeof window.Chart !== 'undefined' && (canvasVentas || canvasTop)) {
                        this.renderDashboardCharts();
                    } else if (attempts < 25) {
                        attempts++;
                        setTimeout(checkAndRender, 100);
                    }
                };
                checkAndRender();
            });
        },

        // Saludo dinámico según la hora del día
        get greetingMessage() {
            const hora = new Date().getHours();
            if (hora < 12) return 'Buenos días';
            if (hora < 18) return 'Buenas tardes';
            return 'Buenas noches';
        },

        // Turno Activo detectado desde /api/caja-operaciones (asociado al usuario en sesión)
        get turnoActivo() {
            if (!Array.isArray(this.turnos)) return null;
            const currentUserId = this.currentUser?.usuario_id;
            if (currentUserId) {
                const myShift = this.turnos.find(t => Number(t.id_usuario) === Number(currentUserId) && !t.fecha_hora_cierre && (t.estado === undefined || Number(t.estado) === 1));
                if (myShift) return myShift;
            }
            return null;
        },

        // RF-26: Consulta y métricas de ventas del turno en tiempo real (Aislamiento por turno actual)
        get ventasTurno() {
            const turno = this.turnoActivo;
            if (!turno) {
                return [];
            }
            const aperturaTime = turno.fecha_hora_apertura ? new Date(turno.fecha_hora_apertura.replace(' ', 'T')).getTime() : 0;
            return (this.ventas || []).filter(v => {
                if (!v.fecha_hora_venta || Number(v.estado) === 0) return false;
                const matchesCaja = v.id_caja ? Number(v.id_caja) === Number(turno.id_caja) : true;
                const matchesUser = Number(v.id_usuario) === Number(turno.id_usuario);
                const ventaTime = new Date(v.fecha_hora_venta.replace(' ', 'T')).getTime();
                const matchesTime = !isNaN(ventaTime) && (!aperturaTime || ventaTime >= aperturaTime);
                return matchesCaja && matchesUser && matchesTime;
            });
        },

        get ventasTurnoStats() {
            const list = this.ventasTurno;
            const turno = this.turnoActivo;
            const montoApertura = turno ? Number(turno.monto_apertura || 0) : 0;
            const ingresosExtra = this.resumenCajaMovimientos ? Number(this.resumenCajaMovimientos.ingresosExtra || 0) : 0;
            const egresosGastos = this.resumenCajaMovimientos ? Number(this.resumenCajaMovimientos.egresosGastos || 0) : 0;

            if (!turno) {
                return {
                    turnoActivo: false,
                    cajaNombre: 'Ninguna',
                    cajeroNombre: '',
                    fechaApertura: null,
                    montoApertura: 0,
                    efectivoEsperado: 0,
                    total: 0,
                    totalTickets: 0,
                    efectivo: 0,
                    countEfectivo: 0,
                    pctEfectivo: 0,
                    transferencia: 0,
                    countTransferencia: 0,
                    pctTransferencia: 0,
                    tarjeta: 0,
                    countTarjeta: 0,
                    pctTarjeta: 0
                };
            }

            const total = list.reduce((sum, v) => sum + Number(v.total_venta || 0), 0);

            const efectivo = list
                .filter(v => (v.metodo_pago || '').toLowerCase() === 'efectivo')
                .reduce((sum, v) => sum + Number(v.total_venta || 0), 0);

            const transferencia = list
                .filter(v => (v.metodo_pago || '').toLowerCase() === 'transferencia')
                .reduce((sum, v) => sum + Number(v.total_venta || 0), 0);

            const tarjeta = list
                .filter(v => ['tarjeta', 'debito', 'credito'].includes((v.metodo_pago || '').toLowerCase()))
                .reduce((sum, v) => sum + Number(v.total_venta || 0), 0);

            const countEfectivo = list.filter(v => (v.metodo_pago || '').toLowerCase() === 'efectivo').length;
            const countTransferencia = list.filter(v => (v.metodo_pago || '').toLowerCase() === 'transferencia').length;
            const countTarjeta = list.filter(v => ['tarjeta', 'debito', 'credito'].includes((v.metodo_pago || '').toLowerCase())).length;

            const safeTotal = total > 0 ? total : 1;
            const pctEfectivo = total > 0 ? Math.round((efectivo / safeTotal) * 100) : 0;
            const pctTransferencia = total > 0 ? Math.round((transferencia / safeTotal) * 100) : 0;
            const pctTarjeta = total > 0 ? Math.round((tarjeta / safeTotal) * 100) : 0;

            const efectivoEsperado = montoApertura + efectivo + ingresosExtra - egresosGastos;

            return {
                turnoActivo: true,
                cajaNombre: turno.caja?.descripcion_caja || turno.caja?.nombre_caja || `Caja #${turno.id_caja}`,
                cajeroNombre: turno.usuario?.nombre_apellido || (this.currentUser ? this.currentUser.nombre_apellido : ''),
                fechaApertura: turno.fecha_hora_apertura || null,
                montoApertura,
                efectivoEsperado,
                total,
                totalTickets: list.length,
                efectivo,
                countEfectivo,
                pctEfectivo,
                transferencia,
                countTransferencia,
                pctTransferencia,
                tarjeta,
                countTarjeta,
                pctTarjeta
            };
        },

        get stockAlerts() {
            if (this.dashboardData?.stockAlerts) {
                return this.dashboardData.stockAlerts;
            }

            const criticos = [];
            const urgentes = [];
            const advertencias = [];

            (this.productos || []).forEach(p => {
                const stock = Number(p.existencia_bodega || 0);
                const min = Number(p.existencia_minima || 0);
                const pct = min > 0 ? Math.min(100, Math.round((stock / min) * 100)) : (stock > 0 ? 100 : 0);

                const item = {
                    ...p,
                    stockActual: stock,
                    stockMinimo: min,
                    porcentaje: pct,
                    categoriaNombre: p.categoria?.nombre_categoria || 'General'
                };

                if (stock <= 0) {
                    criticos.push(item);
                } else if (stock <= Math.ceil(min / 2)) {
                    urgentes.push(item);
                } else if (stock <= min) {
                    advertencias.push(item);
                }
            });

            return {
                criticos,
                urgentes,
                advertencias,
                totalAlertas: criticos.length + urgentes.length + advertencias.length,
                todos: [...criticos, ...urgentes, ...advertencias]
            };
        },

        get filteredStockAlerts() {
            const alerts = this.stockAlerts;
            if (this.stockAlertFiltro === 'critico') return alerts.criticos;
            if (this.stockAlertFiltro === 'urgente') return alerts.urgentes;
            if (this.stockAlertFiltro === 'advertencia') return alerts.advertencias;
            return alerts.todos;
        },

        // RF-32: Top 5 de productos con mayor rotación en tiempo real (más vendidos)
        get topProductosVendidos() {
            const backendTop = Array.isArray(this.dashboardData?.topProductosVendidos) ? this.dashboardData.topProductosVendidos : [];

            // Si no hay ventas locales en memoria pero el backend devolvió el Top 5, usarlo directamente
            if (!Array.isArray(this.ventas) || this.ventas.length === 0) {
                return backendTop;
            }

            const productMap = {};

            (this.productos || []).forEach(p => {
                if (!p || !p.producto_id) return;
                productMap[p.producto_id] = {
                    producto_id: p.producto_id,
                    codigo: p.codigo_producto || '',
                    nombre: p.nombre_producto || '',
                    categoria: p.categoria?.nombre_categoria || 'General',
                    precio: Number(p.precio_venta || 0),
                    stock: Number(p.existencia_bodega || 0),
                    cantidadVendida: 0,
                    totalRecaudado: 0
                };
            });

            this.ventas.forEach(v => {
                if (Number(v.estado) === 0) return;
                const detalles = Array.isArray(v.venta_detalles) ? v.venta_detalles : [];
                detalles.forEach(d => {
                    const pid = d.id_producto;
                    const cant = Number(d.cantidad || 0);
                    const sub = Number(d.subtotal_venta_detalle || (cant * Number(d.precio_unitario || 0)));

                    if (productMap[pid]) {
                        productMap[pid].cantidadVendida += cant;
                        productMap[pid].totalRecaudado += sub;
                    } else if (pid) {
                        productMap[pid] = {
                            producto_id: pid,
                            codigo: d.producto?.codigo_producto || ('PROD-' + pid),
                            nombre: d.producto?.nombre_producto || ('Producto #' + pid),
                            categoria: d.producto?.categoria?.nombre_categoria || 'General',
                            precio: Number(d.precio_unitario || 0),
                            stock: Number(d.producto?.existencia_bodega || 0),
                            cantidadVendida: cant,
                            totalRecaudado: sub
                        };
                    }
                });
            });

            const sorted = Object.values(productMap)
                .filter(p => p.cantidadVendida > 0)
                .sort((a, b) => b.cantidadVendida - a.cantidadVendida);

            if (sorted.length > 0) {
                const top5 = sorted.slice(0, 5);
                const maxCantidad = top5[0].cantidadVendida > 0 ? top5[0].cantidadVendida : 1;
                return top5.map((p, idx) => ({
                    ...p,
                    posicion: idx + 1,
                    porcentajeRelativo: Math.round((p.cantidadVendida / maxCantidad) * 100)
                }));
            }

            return backendTop;
        },

        // RF-33: Productos con baja o nula rotación
        get productosBajaRotacion() {
            const backendBaja = Array.isArray(this.dashboardData?.productosBajaRotacion) ? this.dashboardData.productosBajaRotacion : null;

            if (backendBaja && (!Array.isArray(this.productos) || this.productos.length === 0)) {
                return backendBaja;
            }

            if (backendBaja && (!Array.isArray(this.ventas) || this.ventas.length === 0)) {
                return backendBaja;
            }

            const salesCountMap = {};

            (this.ventas || []).forEach(v => {
                if (Number(v.estado) === 0) return;
                const detalles = Array.isArray(v.venta_detalles) ? v.venta_detalles : [];
                detalles.forEach(d => {
                    const pid = d.id_producto;
                    salesCountMap[pid] = (salesCountMap[pid] || 0) + Number(d.cantidad || 0);
                });
            });

            const list = (this.productos || []).map(p => {
                const unidadesVendidas = salesCountMap[p.producto_id] || 0;
                const costo = Number(p.costo_compra || 0);
                const stock = Number(p.existencia_bodega || 0);
                const capitalInmovilizado = stock * costo;

                let tipoRotacion = 'normal';
                if (unidadesVendidas === 0) {
                    tipoRotacion = 'sin_ventas';
                } else if (unidadesVendidas <= 2) {
                    tipoRotacion = 'poca_rotacion';
                }

                return {
                    producto_id: p.producto_id,
                    codigo_producto: p.codigo_producto || '',
                    nombre_producto: p.nombre_producto || '',
                    categoriaNombre: p.categoria?.nombre_categoria || 'General',
                    existencia_bodega: stock,
                    existencia_minima: Number(p.existencia_minima || 0),
                    costo_compra: costo,
                    precio_venta: Number(p.precio_venta || 0),
                    capitalInmovilizado,
                    unidadesVendidas,
                    tipoRotacion
                };
            }).filter(p => p.tipoRotacion !== 'normal');

            if (list.length > 0) {
                return list.sort((a, b) => b.capitalInmovilizado - a.capitalInmovilizado);
            }

            return backendBaja || [];
        },

        get filteredBajaRotacion() {
            const list = this.productosBajaRotacion || [];
            if (this.bajaRotacionFiltro === 'sin_ventas') {
                return list.filter(p => p.tipoRotacion === 'sin_ventas');
            }
            if (this.bajaRotacionFiltro === 'poca_rotacion') {
                return list.filter(p => p.tipoRotacion === 'poca_rotacion');
            }
            return list;
        },

        get capitalInmovilizadoTotal() {
            if (this.dashboardData?.capitalInmovilizadoTotal !== undefined) {
                return this.dashboardData.capitalInmovilizadoTotal;
            }
            return (this.productosBajaRotacion || []).reduce((sum, p) => sum + (p.capitalInmovilizado || 0), 0);
        },

        // RF-30: Últimas 10 ventas emitidas en tiempo real
        get ultimasVentas() {
            const raw = (Array.isArray(this.ventas) && this.ventas.length > 0)
                ? this.ventas
                : (Array.isArray(this.dashboardData?.ultimasVentas) ? this.dashboardData.ultimasVentas : []);

            return raw.slice(0, 10).map(v => {
                let cliente = 'Consumidor Final';
                if (v.cliente_nombre) {
                    cliente = v.cliente_nombre;
                } else if (v.cliente && v.cliente.nombre_apellido_cliente) {
                    cliente = v.cliente.nombre_apellido_cliente;
                } else if (v.id_cliente && Array.isArray(this.clientes)) {
                    const found = this.clientes.find(c => Number(c.cliente_id) === Number(v.id_cliente));
                    if (found && found.nombre_apellido_cliente) {
                        cliente = found.nombre_apellido_cliente;
                    }
                }

                return {
                    venta_id: v.venta_id,
                    codigo_venta: v.codigo_venta || ('FAC-' + v.venta_id),
                    cliente_nombre: cliente,
                    metodo_pago: v.metodo_pago || 'Efectivo',
                    fecha_hora_venta: v.fecha_hora_venta || '',
                    total_venta: Number(v.total_venta || 0)
                };
            });
        },

        // RF-31 & RF-32: Renderizado de gráficos con Chart.js en tiempo real
        renderDashboardCharts() {
            if (typeof window.Chart === 'undefined') return;

            const isDark = Boolean(this.darkMode);
            const textColor = isDark ? '#94a3b8' : '#64748b';
            const gridColor = isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.06)';

            // 1. Gráfico de Ventas (RF-31: Indicador de ventas por días o semanas en tiempo real)
            const canvasVentas = document.getElementById('chartVentas');
            if (canvasVentas) {
                if (this.chartVentasInstance) {
                    this.chartVentasInstance.destroy();
                    this.chartVentasInstance = null;
                }

                let labels = [];
                let dataMonto = [];
                let dataTickets = [];

                if (this.dashboardVentasView === 'dias') {
                    // Cálculo dinámico en tiempo real: Últimos 7 Días
                    const diasMap = {};
                    for (let i = 6; i >= 0; i--) {
                        const d = new Date();
                        d.setDate(d.getDate() - i);
                        const key = d.toLocaleDateString('en-CA');
                        const dayName = i === 0 ? 'Hoy' : d.toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric' });
                        diasMap[key] = { label: dayName, monto: 0, tickets: 0 };
                    }

                    if (Array.isArray(this.ventas) && this.ventas.length > 0) {
                        this.ventas.forEach(v => {
                            if (!v.fecha_hora_venta || Number(v.estado) === 0) return;
                            const key = v.fecha_hora_venta.slice(0, 10);
                            if (diasMap[key]) {
                                diasMap[key].monto += Number(v.total_venta || 0);
                                diasMap[key].tickets += 1;
                            }
                        });
                        labels = Object.values(diasMap).map(d => d.label);
                        dataMonto = Object.values(diasMap).map(d => Number(d.monto.toFixed(2)));
                        dataTickets = Object.values(diasMap).map(d => d.tickets);
                    } else if (this.dashboardData?.chartVentas?.dias) {
                        labels = this.dashboardData.chartVentas.dias.labels;
                        dataMonto = this.dashboardData.chartVentas.dias.dataMonto;
                        dataTickets = this.dashboardData.chartVentas.dias.dataTickets;
                    } else {
                        labels = Object.values(diasMap).map(d => d.label);
                        dataMonto = Object.values(diasMap).map(d => d.monto);
                        dataTickets = Object.values(diasMap).map(d => d.tickets);
                    }
                } else {
                    // Cálculo dinámico en tiempo real: Últimas 4 Semanas
                    labels = ['Semana 1', 'Semana 2', 'Semana 3', 'Semana 4 (Actual)'];
                    dataMonto = [0, 0, 0, 0];
                    dataTickets = [0, 0, 0, 0];

                    if (Array.isArray(this.ventas) && this.ventas.length > 0) {
                        const now = new Date();
                        this.ventas.forEach(v => {
                            if (!v.fecha_hora_venta || Number(v.estado) === 0) return;
                            const vDate = new Date(v.fecha_hora_venta.replace(' ', 'T'));
                            const diffDays = Math.floor((now - vDate) / (1000 * 60 * 60 * 24));
                            if (diffDays >= 0 && diffDays < 28) {
                                const semIndex = 3 - Math.floor(diffDays / 7);
                                if (semIndex >= 0 && semIndex <= 3) {
                                    dataMonto[semIndex] += Number(v.total_venta || 0);
                                    dataTickets[semIndex] += 1;
                                }
                            }
                        });
                        dataMonto = dataMonto.map(m => Number(m.toFixed(2)));
                    } else if (this.dashboardData?.chartVentas?.semanas) {
                        labels = this.dashboardData.chartVentas.semanas.labels;
                        dataMonto = this.dashboardData.chartVentas.semanas.dataMonto;
                        dataTickets = this.dashboardData.chartVentas.semanas.dataTickets;
                    }
                }

                const ctx = canvasVentas.getContext('2d');
                const gradient = ctx.createLinearGradient(0, 0, 0, 260);
                gradient.addColorStop(0, 'rgba(99, 102, 241, 0.9)');
                gradient.addColorStop(1, 'rgba(79, 70, 229, 0.15)');

                this.chartVentasInstance = new window.Chart(canvasVentas, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [
                            {
                                label: 'Total Facturado',
                                data: dataMonto,
                                backgroundColor: gradient,
                                borderColor: '#6366f1',
                                borderWidth: 2,
                                borderRadius: 8,
                                borderSkipped: false,
                                maxBarThickness: 38,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 400 },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: isDark ? '#1e293b' : '#ffffff',
                                titleColor: isDark ? '#ffffff' : '#0f172a',
                                bodyColor: isDark ? '#94a3b8' : '#334155',
                                borderColor: isDark ? '#334155' : '#e2e8f0',
                                borderWidth: 1,
                                padding: 12,
                                displayColors: false,
                                callbacks: {
                                    label: (ctx) => {
                                        const tickets = dataTickets[ctx.dataIndex] || 0;
                                        return [
                                            `Total: ${this.formatCurrency(ctx.parsed.y)}`,
                                            `Transacciones: ${tickets} venta(s)`
                                        ];
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: textColor, font: { size: 11, family: 'Plus Jakarta Sans' } }
                            },
                            y: {
                                grid: { color: gridColor },
                                ticks: {
                                    color: textColor,
                                    font: { size: 10, family: 'Plus Jakarta Sans' },
                                    callback: (val) => this.formatCurrency(val)
                                }
                            }
                        }
                    }
                });
            }

            // 2. Gráfico de Top Productos Rotación (RF-32)
            const canvasTop = document.getElementById('chartTopProductos');
            if (canvasTop) {
                if (this.chartTopInstance) {
                    this.chartTopInstance.destroy();
                    this.chartTopInstance = null;
                }

                const topData = this.topProductosVendidos;
                const hasData = topData.length > 0 && topData.some(p => p.cantidadVendida > 0);

                const labels = hasData
                    ? topData.map(p => (p.nombre || p.nombre_producto || '').slice(0, 18))
                    : ['Sin datos de ventas'];
                const data = hasData
                    ? topData.map(p => p.cantidadVendida)
                    : [1];
                const colors = hasData
                    ? ['#6366f1', '#10b981', '#f59e0b', '#06b6d4', '#ec4899']
                    : [isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.08)'];

                this.chartTopInstance = new window.Chart(canvasTop, {
                    type: 'doughnut',
                    data: {
                        labels,
                        datasets: [{
                            data,
                            backgroundColor: colors,
                            borderWidth: 2,
                            borderColor: isDark ? '#0f172a' : '#ffffff',
                            hoverOffset: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '72%',
                        animation: { duration: 400 },
                        plugins: {
                            legend: {
                                display: hasData,
                                position: 'bottom',
                                labels: {
                                    color: textColor,
                                    font: { size: 11, family: 'Plus Jakarta Sans' },
                                    boxWidth: 10,
                                    padding: 8
                                }
                            },
                            tooltip: {
                                enabled: hasData,
                                backgroundColor: isDark ? '#1e293b' : '#ffffff',
                                titleColor: isDark ? '#ffffff' : '#0f172a',
                                bodyColor: isDark ? '#94a3b8' : '#334155',
                                borderColor: isDark ? '#334155' : '#e2e8f0',
                                borderWidth: 1,
                                padding: 10,
                                callbacks: {
                                    label: (ctx) => ` ${ctx.label}: ${ctx.parsed} uds. vendidas`
                                }
                            }
                        }
                    }
                });
            }
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

                this.notify(`¡Bienvenido, ${data.usuario.nombre_apellido}!`, 'Has iniciado sesión correctamente.', 'success', 2000);

                // Validar y activar la pestaña correspondiente según permisos del usuario
                if (typeof this.canAccessTab === 'function' && !this.canAccessTab(this.currentTab)) {
                    this.currentTab = (this.visibleNavItems && this.visibleNavItems.length > 0) ? this.visibleNavItems[0].id : 'productos';
                }

                // Cargar pestaña inicial y datos de la empresa
                await this.loadTab(this.currentTab, true);
                await this.fetchEmpresa();
            } catch (error) {
                this.loginError = error.message;
                this.notify('Error de Autenticación', error.message, 'error', 3000);
            } finally {
                this.isLoggingIn = false;
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            }
        },

        logout() {
            const token = this.authToken;

            // 1. Limpieza inmediata del estado local (Respuesta instantánea a 0ms)
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

            // 2. Refrescar iconos en vista de login inmediatamente
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });

            // 3. Notificación sutil no bloqueante (Toast en esquina superior)
            this.notify('Sesión Finalizada', 'Has salido correctamente.', 'info', 2000);

            // 4. Invalidar token en el backend en segundo plano (sin congelar la interfaz)
            if (token) {
                fetch('/api/auth/logout', {
                    method: 'POST',
                    keepalive: true,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    }
                }).catch(() => { });
            }
        }
    };
}

// 5. Módulo de Utilidades
function utilsModule() {
    return {
        notify(title, text = '', icon = 'success', timer = 2800) {
            let container = document.getElementById('fs-toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'fs-toast-container';
                container.className = 'fixed top-4 right-4 z-[999999] flex flex-col gap-2.5 max-w-sm w-full pointer-events-none px-3 sm:px-0';
                document.body.appendChild(container);
            }

            const isDark = (this.darkMode !== undefined) ? this.darkMode : document.documentElement.classList.contains('dark');

            const iconConfig = {
                success: {
                    bg: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30',
                    svg: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>'
                },
                error: {
                    bg: 'bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/30',
                    svg: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>'
                },
                warning: {
                    bg: 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/30',
                    svg: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>'
                },
                info: {
                    bg: 'bg-blue-500/15 text-blue-600 dark:text-blue-400 border-blue-500/30',
                    svg: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
                }
            }[icon] || {
                bg: 'bg-brand-500/15 text-brand-600 dark:text-brand-400 border-brand-500/30',
                svg: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
            };

            const toast = document.createElement('div');
            toast.className = `pointer-events-auto flex items-start gap-3 p-3.5 rounded-2xl shadow-xl border backdrop-blur-md transition-all duration-300 transform translate-x-8 opacity-0 ${
                isDark
                    ? 'bg-slate-900/95 border-slate-700/80 text-white shadow-slate-950/50'
                    : 'bg-white/95 border-slate-200 text-slate-900 shadow-slate-200/80'
            }`;

            toast.innerHTML = `
                <div class="w-7 h-7 rounded-xl flex items-center justify-center flex-shrink-0 border ${iconConfig.bg}">
                    ${iconConfig.svg}
                </div>
                <div class="flex-1 min-w-0 pr-1">
                    <h5 class="text-xs font-bold leading-snug truncate">${title}</h5>
                    ${text ? `<p class="text-[11px] ${isDark ? 'text-slate-400' : 'text-slate-500'} mt-0.5 leading-relaxed break-words">${text}</p>` : ''}
                </div>
                <button type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-0.5 -mr-1 -mt-1 flex-shrink-0 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            `;

            const closeBtn = toast.querySelector('button');
            const removeToast = () => {
                toast.classList.add('translate-x-8', 'opacity-0');
                setTimeout(() => {
                    if (toast.parentNode) toast.parentNode.removeChild(toast);
                }, 300);
            };

            closeBtn.addEventListener('click', removeToast);
            container.appendChild(toast);

            requestAnimationFrame(() => {
                toast.classList.remove('translate-x-8', 'opacity-0');
                toast.classList.add('translate-x-0', 'opacity-100');
            });

            if (timer > 0) {
                setTimeout(removeToast, timer);
            }
        },

        formatCurrency(amount) {
            const sym = (this.empresa && this.empresa.moneda_simbolo) ? this.empresa.moneda_simbolo : 'C$';
            return sym + ' ' + Number(amount || 0).toLocaleString('es-NI', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        formatDate(dateStr) {
            if (!dateStr) return 'N/A';
            let normalized = String(dateStr).trim();
            // Si la fecha viene en formato UTC sin sufijo de zona horaria (ej: "2026-09-27 02:35:00")
            if (/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?(\.\d+)?$/.test(normalized)) {
                normalized = normalized.replace(' ', 'T') + 'Z';
            }
            const d = new Date(normalized);
            const target = isNaN(d.getTime()) ? new Date(dateStr) : d;
            if (isNaN(target.getTime())) return dateStr;

            const pad = n => String(n).padStart(2, '0');
            const day = pad(target.getDate());
            const month = pad(target.getMonth() + 1);
            const year = target.getFullYear();

            let hours = target.getHours();
            const minutes = pad(target.getMinutes());
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? pad(hours) : '12';

            return `${day}/${month}/${year} ${hours}:${minutes} ${ampm}`;
        },

        formatDateOnly(dateStr) {
            if (!dateStr) return '';
            let normalized = String(dateStr).trim();
            if (/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?(\.\d+)?$/.test(normalized)) {
                normalized = normalized.replace(' ', 'T') + 'Z';
            }
            const d = new Date(normalized);
            const target = isNaN(d.getTime()) ? new Date(dateStr) : d;
            if (isNaN(target.getTime())) return dateStr;

            const pad = n => String(n).padStart(2, '0');
            const day = pad(target.getDate());
            const month = pad(target.getMonth() + 1);
            const year = target.getFullYear();

            return `${day}/${month}/${year}`;
        },

        formatTime(dateStr) {
            if (!dateStr) return '';
            let normalized = String(dateStr).trim();
            // Si la fecha viene en formato UTC sin sufijo de zona horaria (ej: "2026-09-27 02:35:00")
            if (/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?(\.\d+)?$/.test(normalized)) {
                normalized = normalized.replace(' ', 'T') + 'Z';
            }
            const d = new Date(normalized);
            if (isNaN(d.getTime())) {
                const fallback = new Date(dateStr);
                if (isNaN(fallback.getTime())) return dateStr;
                return fallback.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
            }
            return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
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
                this.notify('¡Categoría guardada!', `La categoría "${this.categoryForm.nombre_categoria}" fue guardada.`, 'success');
                await this.fetchCategorias();
            } catch (error) {
                this.notify('Error al guardar categoría', error.message, 'error');
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
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                background: this.darkMode ? '#1e293b' : '#ffffff',
                color: this.darkMode ? '#fff' : '#0f172a'
            });

            if (result.isConfirmed) {
                await this.apiFetch(`/api/categorias/${cat.categoria_id}`, {
                    method: 'DELETE'
                });
                this.notify('Categoría Eliminada', `"${cat.nombre_categoria}" fue eliminada.`, 'success');
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
            justificacion: '',
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
                this.notify('Stock Actualizado', `Existencias actualizadas a ${newStock} unidades.`, 'success');
            } catch (error) {
                this.notify('Error de Inventario', error.message, 'error');
            }
        }
    };
}

// 8. Módulo de Proveedores y Cuentas por Pagar (CxP)
function proveedoresModule() {
    return {
        // Estado de Proveedores y Cuentas por Pagar
        proveedores: [],
        cuentasPorPagar: [],
        cxpKPIs: {
            total_pendiente: 0,
            total_vencido: 0,
            total_pagado_mes: 0,
            facturas_pendientes_count: 0,
            facturas_vencidas_count: 0
        },
        cxpActiveSubTab: 'cuentas', // 'cuentas' | 'proveedores'
        cxpSearch: '',
        cxpEstadoFilter: '',
        cxpProveedorFilter: '',
        proveedorSearch: '',
        loadingProveedores: false,
        loadingCuentas: false,

        // Modal y Formulario de Proveedor
        showProveedorModal: false,
        isEditingProveedor: false,
        isSavingProveedor: false,
        proveedorForm: {
            proveedor_id: null,
            nombre_comercial: '',
            contacto_vendedor: '',
            telefono: '',
            plazo_credito_dias: 0,
            estado: 1
        },

        // Modal y Formulario de Cuenta por Pagar
        showCuentaPorPagarModal: false,
        isEditingCuentaPorPagar: false,
        isSavingCuentaPorPagar: false,
        cuentaPorPagarForm: {
            cuenta_por_pagar_id: null,
            id_proveedor: '',
            numero_factura: '',
            descripcion: '',
            fecha_emision: '',
            fecha_vencimiento: '',
            monto_total: ''
        },

        // Modal y Formulario de Abono / Pago
        showAbonoModal: false,
        isSavingAbono: false,
        selectedCuentaParaAbono: null,
        abonoForm: {
            monto_pago: '',
            metodo_pago: 'Efectivo',
            referencia_pago: '',
            nota: '',
            registrar_en_caja: true,
            debitar_de_caja: true
        },

        // Modal de Historial de Pagos / Abonos
        showHistorialAbonosModal: false,
        selectedCuentaHistorial: null,

        // Computed Getters
        get filteredCuentasPorPagar() {
            return (this.cuentasPorPagar || []).filter(c => {
                if (this.cxpEstadoFilter && c.estado !== this.cxpEstadoFilter) {
                    return false;
                }
                if (this.cxpProveedorFilter && Number(c.id_proveedor) !== Number(this.cxpProveedorFilter)) {
                    return false;
                }
                if (this.cxpSearch) {
                    const term = this.cxpSearch.toLowerCase().trim();
                    const numMatch = (c.numero_factura || '').toLowerCase().includes(term);
                    const descMatch = (c.descripcion || '').toLowerCase().includes(term);
                    const provMatch = (c.proveedor?.nombre_comercial || '').toLowerCase().includes(term);
                    return numMatch || descMatch || provMatch;
                }
                return true;
            });
        },

        get filteredProveedores() {
            return (this.proveedores || []).filter(p => {
                if (!this.proveedorSearch) return true;
                const term = this.proveedorSearch.toLowerCase().trim();
                const nameMatch = (p.nombre_comercial || '').toLowerCase().includes(term);
                const contactMatch = (p.contacto_vendedor || '').toLowerCase().includes(term);
                const phoneMatch = (p.telefono || '').toLowerCase().includes(term);
                return nameMatch || contactMatch || phoneMatch;
            });
        },

        // Métodos de Carga de Datos
        async fetchProveedores() {
            if (!this.isAdmin && !this.hasPermission('proveedores.gestionar')) return;
            try {
                this.loadingProveedores = true;
                const res = await this.apiFetch('/api/proveedores');
                if (res.ok) {
                    const data = await res.json();
                    this.proveedores = Array.isArray(data) ? data : (data.data || []);
                }
            } catch (err) {
                console.error('Error cargando proveedores:', err);
            } finally {
                this.loadingProveedores = false;
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            }
        },

        async fetchCuentasPorPagar() {
            if (!this.isAdmin && !this.hasPermission('proveedores.gestionar')) return;
            try {
                this.loadingCuentas = true;
                const res = await this.apiFetch('/api/cuentas-por-pagar');
                if (res.ok) {
                    const data = await res.json();
                    this.cuentasPorPagar = Array.isArray(data) ? data : (data.data || []);
                }
            } catch (err) {
                console.error('Error cargando cuentas por pagar:', err);
            } finally {
                this.loadingCuentas = false;
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            }
        },

        async fetchCxPKPIs() {
            if (!this.isAdmin && !this.hasPermission('proveedores.gestionar')) return;
            try {
                const res = await this.apiFetch('/api/cuentas-por-pagar/resumen-kpis');
                if (res.ok) {
                    const data = await res.json();
                    const kpis = (data && typeof data === 'object' && data.kpis) ? data.kpis : data;
                    if (kpis && typeof kpis === 'object') {
                        this.cxpKPIs = {
                            total_pendiente: Number(kpis.total_pendiente ?? kpis.total_deuda_activa ?? 0),
                            facturas_pendientes_count: Number(kpis.facturas_pendientes_count ?? 0),
                            total_vencido: Number(kpis.total_vencido ?? kpis.monto_vencido ?? 0),
                            facturas_vencidas_count: Number(kpis.facturas_vencidas_count ?? kpis.total_vencidas ?? 0),
                            proximos_vencimientos: Number(kpis.proximos_vencimientos ?? 0),
                            total_pagado_mes: Number(kpis.total_pagado_mes ?? 0)
                        };
                    }
                }
            } catch (err) {
                console.error('Error cargando KPIs de cuentas por pagar:', err);
            }
        },

        // Control de Proveedores (CRUD)
        openProveedorModal(prov = null) {
            if (prov) {
                this.isEditingProveedor = true;
                this.proveedorForm = {
                    proveedor_id: prov.proveedor_id,
                    nombre_comercial: prov.nombre_comercial || '',
                    contacto_vendedor: prov.contacto_vendedor || '',
                    telefono: prov.telefono || '',
                    plazo_credito_dias: prov.plazo_credito_dias ?? 0,
                    estado: prov.estado ?? 1
                };
            } else {
                this.isEditingProveedor = false;
                this.proveedorForm = {
                    proveedor_id: null,
                    nombre_comercial: '',
                    contacto_vendedor: '',
                    telefono: '',
                    plazo_credito_dias: 0,
                    estado: 1
                };
            }
            this.showProveedorModal = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        async saveProveedor() {
            if (this.isSavingProveedor) return;
            if (!this.proveedorForm.nombre_comercial.trim()) {
                this.notify('Campo Requerido', 'El nombre comercial del proveedor es obligatorio.', 'warning');
                return;
            }

            this.isSavingProveedor = true;
            const isEdit = this.isEditingProveedor;
            const url = isEdit ? `/api/proveedores/${this.proveedorForm.proveedor_id}` : '/api/proveedores';
            const method = isEdit ? 'PUT' : 'POST';

            try {
                const res = await this.apiFetch(url, {
                    method,
                    body: JSON.stringify(this.proveedorForm)
                });

                const data = await res.json();
                if (!res.ok) {
                    let msg = data.message || 'Error al guardar el proveedor';
                    if (data.errors) {
                        msg = Object.values(data.errors).flat().join('<br>');
                    }
                    throw new Error(msg);
                }

                this.showProveedorModal = false;
                await this.fetchProveedores();
                this.notify(
                    isEdit ? '¡Proveedor Actualizado!' : '¡Proveedor Registrado!',
                    data.message || `El proveedor "${this.proveedorForm.nombre_comercial}" se guardó exitosamente.`,
                    'success'
                );
            } catch (err) {
                this.notify('Error al guardar proveedor', err.message, 'error');
            } finally {
                this.isSavingProveedor = false;
            }
        },

        async deleteProveedor(prov) {
            const confirm = await Swal.fire({
                icon: 'warning',
                title: '¿Desactivar Proveedor?',
                html: `¿Estás seguro de desactivar al proveedor <b>"${prov.nombre_comercial}"</b>?<br><small class="text-slate-400">Si tiene cuentas por pagar con saldo pendiente, el sistema impedirá su desactivación.</small>`,
                showCancelButton: true,
                confirmButtonText: 'Sí, desactivar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#ef4444',
                background: this.darkMode ? '#1e293b' : '#ffffff',
                color: this.darkMode ? '#fff' : '#0f172a'
            });

            if (!confirm.isConfirmed) return;

            try {
                const res = await this.apiFetch(`/api/proveedores/${prov.proveedor_id}`, {
                    method: 'DELETE'
                });

                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.message || 'Error al desactivar el proveedor');
                }

                await Promise.all([this.fetchProveedores(), this.fetchCuentasPorPagar(), this.fetchCxPKPIs()]);
                this.notify('Proveedor Desactivado', data.message || 'El proveedor fue desactivado exitosamente.', 'success');
            } catch (err) {
                this.notify('No se pudo desactivar', err.message, 'error');
            }
        },

        // Control de Cuentas por Pagar (CRUD)
        openCuentaPorPagarModal(cuenta = null) {
            const today = new Date().toISOString().split('T')[0];
            if (cuenta) {
                this.isEditingCuentaPorPagar = true;
                this.cuentaPorPagarForm = {
                    cuenta_por_pagar_id: cuenta.cuenta_por_pagar_id,
                    id_proveedor: cuenta.id_proveedor,
                    numero_factura: cuenta.numero_factura || '',
                    descripcion: cuenta.descripcion || '',
                    fecha_emision: cuenta.fecha_emision || today,
                    fecha_vencimiento: cuenta.fecha_vencimiento || today,
                    monto_total: cuenta.monto_total
                };
            } else {
                this.isEditingCuentaPorPagar = false;
                const defaultProvId = this.proveedores.length > 0 ? this.proveedores[0].proveedor_id : '';
                this.cuentaPorPagarForm = {
                    cuenta_por_pagar_id: null,
                    id_proveedor: defaultProvId,
                    numero_factura: '',
                    descripcion: '',
                    fecha_emision: today,
                    fecha_vencimiento: today,
                    monto_total: ''
                };
                if (defaultProvId) {
                    this.onProveedorSelectInCxP();
                }
            }
            this.showCuentaPorPagarModal = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        onProveedorSelectInCxP() {
            if (!this.cuentaPorPagarForm.id_proveedor) return;
            const prov = this.proveedores.find(p => Number(p.proveedor_id) === Number(this.cuentaPorPagarForm.id_proveedor));
            if (!prov) return;

            const emisionStr = this.cuentaPorPagarForm.fecha_emision || new Date().toISOString().split('T')[0];
            const emision = new Date(emisionStr + 'T00:00:00');
            const diasCredito = Number(prov.plazo_credito_dias || 0);

            if (!isNaN(emision.getTime())) {
                emision.setDate(emision.getDate() + diasCredito);
                const pad = n => String(n).padStart(2, '0');
                this.cuentaPorPagarForm.fecha_vencimiento = `${emision.getFullYear()}-${pad(emision.getMonth() + 1)}-${pad(emision.getDate())}`;
            }
        },

        async saveCuentaPorPagar() {
            if (this.isSavingCuentaPorPagar) return;
            if (!this.cuentaPorPagarForm.id_proveedor) {
                this.notify('Campo Requerido', 'Debes seleccionar un proveedor.', 'warning');
                return;
            }
            if (!this.cuentaPorPagarForm.numero_factura.trim()) {
                this.notify('Campo Requerido', 'El número de factura es obligatorio.', 'warning');
                return;
            }
            if (!this.cuentaPorPagarForm.monto_total || Number(this.cuentaPorPagarForm.monto_total) <= 0) {
                this.notify('Monto Inválido', 'El monto total debe ser mayor a 0.', 'warning');
                return;
            }

            this.isSavingCuentaPorPagar = true;
            const isEdit = this.isEditingCuentaPorPagar;
            const url = isEdit ? `/api/cuentas-por-pagar/${this.cuentaPorPagarForm.cuenta_por_pagar_id}` : '/api/cuentas-por-pagar';
            const method = isEdit ? 'PUT' : 'POST';

            try {
                const res = await this.apiFetch(url, {
                    method,
                    body: JSON.stringify(this.cuentaPorPagarForm)
                });

                const data = await res.json();
                if (!res.ok) {
                    let msg = data.message || 'Error al guardar la factura de proveedor';
                    if (data.errors) {
                        msg = Object.values(data.errors).flat().join('<br>');
                    }
                    throw new Error(msg);
                }

                this.showCuentaPorPagarModal = false;
                await Promise.all([this.fetchCuentasPorPagar(), this.fetchCxPKPIs(), this.fetchProveedores()]);
                this.notify(
                    isEdit ? '¡Factura Actualizada!' : '¡Factura Registrada!',
                    data.message || 'La cuenta por pagar se guardó exitosamente.',
                    'success'
                );
            } catch (err) {
                this.notify('Error al guardar factura', err.message, 'error');
            } finally {
                this.isSavingCuentaPorPagar = false;
            }
        },

        async deleteCuentaPorPagar(cuenta) {
            const confirm = await Swal.fire({
                icon: 'warning',
                title: '¿Eliminar Factura de Proveedor?',
                html: `¿Estás seguro de eliminar la factura <b>"${cuenta.numero_factura}"</b>?<br><small class="text-slate-400">Si ya cuenta con pagos registrados no podrá eliminarse.</small>`,
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#ef4444',
                background: this.darkMode ? '#1e293b' : '#ffffff',
                color: this.darkMode ? '#fff' : '#0f172a'
            });

            if (!confirm.isConfirmed) return;

            try {
                const res = await this.apiFetch(`/api/cuentas-por-pagar/${cuenta.cuenta_por_pagar_id}`, {
                    method: 'DELETE'
                });

                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.message || 'Error al eliminar la cuenta por pagar');
                }

                await Promise.all([this.fetchCuentasPorPagar(), this.fetchCxPKPIs(), this.fetchProveedores()]);
                this.notify('Factura Eliminada', data.message || 'La factura fue eliminada correctamente.', 'success');
            } catch (err) {
                this.notify('No se pudo eliminar', err.message, 'error');
            }
        },

        // Control de Pagos y Abonos
        openAbonoModal(cuenta) {
            this.selectedCuentaParaAbono = cuenta;
            const tieneTurno = Boolean(this.turnoActivo);
            this.abonoForm = {
                monto_pago: Number(cuenta.saldo_pendiente || 0).toFixed(2),
                metodo_pago: 'Efectivo',
                referencia_pago: '',
                nota: '',
                registrar_en_caja: tieneTurno,
                debitar_de_caja: tieneTurno
            };
            this.showAbonoModal = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        setPagarTotalidadAbono() {
            if (this.selectedCuentaParaAbono) {
                this.abonoForm.monto_pago = Number(this.selectedCuentaParaAbono.saldo_pendiente || 0).toFixed(2);
            }
        },

        async saveAbono() {
            if (this.isSavingAbono) return;
            if (!this.selectedCuentaParaAbono) return;

            const montoNum = Number(this.abonoForm.monto_pago);
            if (isNaN(montoNum) || montoNum <= 0) {
                this.notify('Monto Inválido', 'El monto del abono debe ser mayor a 0.', 'warning');
                return;
            }

            if (montoNum > Number(this.selectedCuentaParaAbono.saldo_pendiente) + 0.001) {
                this.notify('Monto Excede el Saldo', `El abono máximo permitido es de ${this.formatCurrency(this.selectedCuentaParaAbono.saldo_pendiente)}.`, 'warning');
                return;
            }

            const debeDebitar = this.abonoForm.metodo_pago === 'Efectivo' && Boolean(this.abonoForm.registrar_en_caja || this.abonoForm.debitar_de_caja);

            if (debeDebitar && !this.turnoActivo) {
                this.notify('Caja Cerrada', 'Para registrar la salida de efectivo de caja, debes tener un turno activo abierto o desmarcar la casilla de descontar de caja.', 'warning');
                return;
            }

            this.isSavingAbono = true;
            try {
                const payload = {
                    monto_pago: montoNum,
                    metodo_pago: this.abonoForm.metodo_pago,
                    debitar_de_caja: debeDebitar,
                    registrar_en_caja: debeDebitar,
                    referencia_pago: this.abonoForm.referencia_pago ? this.abonoForm.referencia_pago.trim() : null,
                    nota: this.abonoForm.nota ? this.abonoForm.nota.trim() : null,
                    notas: [
                        this.abonoForm.referencia_pago ? `Ref: ${this.abonoForm.referencia_pago.trim()}` : '',
                        this.abonoForm.nota ? this.abonoForm.nota.trim() : ''
                    ].filter(Boolean).join(' - ') || null
                };

                const res = await this.apiFetch(`/api/cuentas-por-pagar/${this.selectedCuentaParaAbono.cuenta_por_pagar_id}/pagos`, {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                if (!res.ok) {
                    let msg = data.message || 'Error al registrar el pago';
                    if (data.errors) {
                        msg = Object.values(data.errors).flat().join('<br>');
                    }
                    throw new Error(msg);
                }

                this.showAbonoModal = false;
                await Promise.all([
                    this.fetchCuentasPorPagar(),
                    this.fetchCxPKPIs(),
                    this.fetchProveedores(),
                    this.fetchBitacoras()
                ]);

                // Si se registró egreso en caja, refrescar movimientos de caja
                if (data.caja_movimiento && typeof this.fetchVentas === 'function') {
                    this.fetchVentas();
                }

                this.notify(
                    '¡Abono Registrado!',
                    data.message || `Se abonó ${this.formatCurrency(montoNum)} a la factura ${this.selectedCuentaParaAbono.numero_factura}.`,
                    'success'
                );
            } catch (err) {
                this.notify('Error al registrar abono', err.message, 'error');
            } finally {
                this.isSavingAbono = false;
            }
        },

        async openHistorialAbonos(cuenta) {
            this.selectedCuentaHistorial = { ...cuenta };
            this.showHistorialAbonosModal = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });

            try {
                const res = await this.apiFetch(`/api/cuentas-por-pagar/${cuenta.cuenta_por_pagar_id}/pagos`);
                if (res.ok) {
                    const pagos = await res.json();
                    if (this.selectedCuentaHistorial && this.selectedCuentaHistorial.cuenta_por_pagar_id === cuenta.cuenta_por_pagar_id) {
                        this.selectedCuentaHistorial.pagos = pagos;
                        this.$nextTick(() => {
                            if (window.lucide) window.lucide.createIcons();
                        });
                    }
                }
            } catch (e) {
                console.error('Error al cargar historial de pagos:', e);
            }
        },

        // Utilidades de Fechas y Estados
        isCuentaVencida(cuenta) {
            if (!cuenta || Number(cuenta.saldo_pendiente) <= 0 || cuenta.estado === 'Pagada' || cuenta.estado === 'Anulada') {
                return false;
            }
            if (!cuenta.fecha_vencimiento) return false;
            const today = new Date().toISOString().split('T')[0];
            return cuenta.fecha_vencimiento < today;
        },

        diasHastaVencimiento(cuenta) {
            if (!cuenta || !cuenta.fecha_vencimiento) return 0;
            const today = new Date(new Date().toISOString().split('T')[0]);
            const due = new Date(cuenta.fecha_vencimiento);
            const diffTime = due.getTime() - today.getTime();
            return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        }
    };
}

// 9. Módulo de Usuarios
function usuariosModule() {
    return {
        // Pestaña activa dentro del módulo: 'usuarios' | 'roles'
        usuariosSubTab: 'usuarios',

        // Filtros y Búsqueda de usuarios
        userSearchQuery: '',
        userRoleFilter: '',
        userStatusFilter: '',

        // Estado del Modal de Usuario
        showUserModal: false,
        isEditingUser: false,
        isSavingUser: false,
        showUserFormPassword: false,
        userForm: {
            usuario_id: null,
            id_rol: '',
            nombre_apellido: '',
            nombre_usuario: '',
            contrasenia_usuario: '',
            fecha_registro: '',
            estado: 1,
            bloqueado: 0,
        },

        // Estado del Modal de Restablecimiento de Contraseña
        showPasswordModal: false,
        passwordUser: null,
        newPasswordValue: '',
        showPasswordPlainText: false,
        isSavingPassword: false,

        // Estado del Modal de Roles y Privilegios
        showRolModal: false,
        isEditingRol: false,
        isSavingRol: false,
        rolForm: {
            rol_id: null,
            nombre_rol: '',
            descripcion_rol: '',
            permisos: [],
            estado: 1
        },

        // Definición de grupos de permisos para la matriz dinámica
        availablePermissionsGroups: [
            {
                nombre: 'Punto de Venta & Cobro',
                icono: 'shopping-cart',
                permisos: [
                    { clave: 'pos.acceso', etiqueta: 'Acceso al POS', descripcion: 'Permite abrir el módulo de facturación' },
                    { clave: 'ventas.crear', etiqueta: 'Emitir Ventas', descripcion: 'Cobrar y emitir comprobantes de venta' }
                ]
            },
            {
                nombre: 'Catálogo & Inventario',
                icono: 'package',
                permisos: [
                    { clave: 'productos.ver', etiqueta: 'Ver Catálogo', descripcion: 'Consultar lista y stock de productos' },
                    { clave: 'productos.gestionar', etiqueta: 'Crear / Editar Productos', descripcion: 'Gestionar precios, costos y fichas' },
                    { clave: 'inventario.gestionar', etiqueta: 'Ajustes & Kardex', descripcion: 'Movimientos de stock y ajustes de inventario' },
                    { clave: 'categorias.ver', etiqueta: 'Ver Categorías', descripcion: 'Listar categorías del catálogo' },
                    { clave: 'categorias.gestionar', etiqueta: 'Crear / Editar Categorías', descripcion: 'Gestionar categorías de productos' }
                ]
            },
            {
                nombre: 'Cajas & Arqueos',
                icono: 'wallet',
                permisos: [
                    { clave: 'cajas.gestionar', etiqueta: 'Control de Cajas', descripcion: 'Apertura, arqueos, cierres y movimientos monetarios' }
                ]
            },
            {
                nombre: 'Ventas & Facturas',
                icono: 'receipt',
                permisos: [
                    { clave: 'ventas.ver', etiqueta: 'Historial de Facturación', descripcion: 'Consultar ventas pasadas y reimprimir comprobantes' }
                ]
            },
            {
                nombre: 'Proveedores & CxP',
                icono: 'truck',
                permisos: [
                    { clave: 'proveedores.gestionar', etiqueta: 'Proveedores & Cuentas por Pagar', descripcion: 'Gestión de proveedores, facturas de crédito y abonos' }
                ]
            },
            {
                nombre: 'Dashboard & Administración',
                icono: 'shield-check',
                permisos: [
                    { clave: 'dashboard.ver', etiqueta: 'Panel Analítico', descripcion: 'Métricas, KPIs y gráficos de facturación' },
                    { clave: 'empresa.gestionar', etiqueta: 'Datos de la Empresa', descripcion: 'Ajustes de facturación, logo y datos del negocio' },
                    { clave: 'usuarios.gestionar', etiqueta: 'Control de Usuarios & Roles', descripcion: 'Alta, baja, contraseñas y permisos del sistema' },
                    { clave: 'bitacoras.ver', etiqueta: 'Auditoría & Bitácora', descripcion: 'Visualización de registros de actividades' }
                ]
            }
        ],

        // Usuarios filtrados de forma reactiva
        get filteredUsersList() {
            const list = Array.isArray(this.usuarios) ? this.usuarios : [];
            const query = (this.userSearchQuery || '').trim().toLowerCase();
            const roleFilter = this.userRoleFilter;
            const statusFilter = this.userStatusFilter;

            return list.filter(u => {
                if (roleFilter && String(u.id_rol) !== String(roleFilter)) {
                    return false;
                }
                if (statusFilter === 'activos' || statusFilter === '1') {
                    if (Number(u.bloqueado) === 1) return false;
                } else if (statusFilter === 'bloqueados' || statusFilter === '0') {
                    if (Number(u.bloqueado) !== 1) return false;
                }
                if (query) {
                    const name = (u.nombre_apellido || '').toLowerCase();
                    const username = (u.nombre_usuario || '').toLowerCase();
                    return name.includes(query) || username.includes(query);
                }
                return true;
            });
        },

        // Descripción dinámica del rol en el formulario de usuario
        get selectedUserRoleDescription() {
            if (!this.userForm.id_rol || !Array.isArray(this.roles)) return '';
            const r = this.roles.find(item => Number(item.rol_id) === Number(this.userForm.id_rol));
            if (!r) return '';
            if (r.nombre_rol === 'Administrador' || (Array.isArray(r.permisos) && r.permisos.includes('*'))) {
                return 'Este rol posee privilegios totales sobre todos los módulos y ajustes del sistema.';
            }
            if (Array.isArray(r.permisos) && r.permisos.length > 0) {
                return `Acceso concedido a ${r.permisos.length} permiso(s) específico(s): ${r.permisos.slice(0, 3).join(', ')}${r.permisos.length > 3 ? '...' : ''}`;
            }
            return 'Este rol actualmente no tiene permisos configurados en la base de datos.';
        },

        // Conteo de administradores activos y desbloqueados en el sistema
        get activeAdminsCount() {
            if (!Array.isArray(this.usuarios)) return 0;
            return this.usuarios.filter(u =>
                Number(u.estado) === 1 &&
                Number(u.bloqueado) === 0 &&
                u.rol &&
                (u.rol.nombre_rol === 'Administrador' || (Array.isArray(u.rol.permisos) && u.rol.permisos.includes('*')))
            ).length;
        },

        // Determina si un usuario dado es el Administrador Principal (Super Admin / Propietario)
        isUserPrincipal(u) {
            if (!u) return false;
            return Boolean(
                u.es_principal ||
                Number(u.usuario_id) === 1 ||
                u.nombre_usuario === 'si_dquiroz'
            );
        },

        // Determina si el usuario que se está editando en el modal es el usuario en sesión
        get isEditingSelf() {
            return Boolean(
                this.isEditingUser &&
                this.currentUser &&
                this.userForm &&
                Number(this.currentUser.usuario_id) === Number(this.userForm.usuario_id)
            );
        },

        // Determina si el usuario que se está editando en el modal es el Administrador Principal
        get isEditingPrincipal() {
            if (!this.isEditingUser || !this.userForm || !this.userForm.usuario_id) return false;
            const targetUser = (this.usuarios || []).find(u => Number(u.usuario_id) === Number(this.userForm.usuario_id));
            if (targetUser) return this.isUserPrincipal(targetUser);
            return Number(this.userForm.usuario_id) === 1 || this.userForm.nombre_usuario === 'si_dquiroz';
        },

        // Determina si el usuario que se está editando en el modal es el único administrador activo disponible
        get isEditingLastAdmin() {
            if (!this.isEditingUser || !this.userForm || !this.userForm.usuario_id) return false;
            const targetUser = (this.usuarios || []).find(u => Number(u.usuario_id) === Number(this.userForm.usuario_id));
            if (!targetUser) return false;
            const isTargetAdmin = targetUser.rol && (targetUser.rol.nombre_rol === 'Administrador' || (Array.isArray(targetUser.rol.permisos) && targetUser.rol.permisos.includes('*')));
            if (!isTargetAdmin || Number(targetUser.estado) !== 1 || Number(targetUser.bloqueado) === 1) return false;

            return this.activeAdminsCount <= 1;
        },

        // Determina si está permitido modificar el rol del usuario en el formulario
        get canChangeUserRole() {
            if (!this.isEditingUser) return true;
            if (this.isEditingSelf) return false;
            if (this.isEditingPrincipal) return false;
            if (this.isEditingLastAdmin) return false;
            return true;
        },

        // Determina si está permitido modificar el estado (activo/inactivo) del usuario en el formulario
        get canChangeUserStatus() {
            if (!this.isEditingUser) return true;
            if (this.isEditingSelf) return false;
            if (this.isEditingPrincipal) return false;
            if (this.isEditingLastAdmin) return false;
            return true;
        },

        // Retrocompatibilidad para cualquier referencia previa
        get isEditingAdminUser() {
            return !this.canChangeUserRole;
        },

        // Determina si se puede editar un usuario desde la tabla
        canEditUser(u) {
            if (!u) return false;
            const isTargetPrincipal = this.isUserPrincipal(u);
            const isSelf = this.currentUser && Number(this.currentUser.usuario_id) === Number(u.usuario_id);
            if (isTargetPrincipal && !isSelf) return false;
            return true;
        },

        // Determina si se puede restablecer la contraseña de un usuario desde la tabla
        canResetPassword(u) {
            if (!u) return false;
            const isTargetPrincipal = this.isUserPrincipal(u);
            const isSelf = this.currentUser && Number(this.currentUser.usuario_id) === Number(u.usuario_id);
            if (isTargetPrincipal && !isSelf) return false;
            return true;
        },

        // Determina si se puede bloquear/desbloquear un usuario de la lista
        canToggleUser(u) {
            if (!u) return false;
            if (this.currentUser && Number(this.currentUser.usuario_id) === Number(u.usuario_id)) return false;
            if (this.isUserPrincipal(u)) return false;
            const esAdmin = u.rol && (u.rol.nombre_rol === 'Administrador' || (Array.isArray(u.rol.permisos) && u.rol.permisos.includes('*')));
            if (esAdmin && Number(u.bloqueado) === 0 && this.activeAdminsCount <= 1) {
                return false;
            }
            return true;
        },

        // Determina si se puede eliminar un usuario de la lista
        canDeleteUser(u) {
            if (!u) return false;
            if (this.currentUser && Number(this.currentUser.usuario_id) === Number(u.usuario_id)) return false;
            if (this.isUserPrincipal(u)) return false;
            const esAdmin = u.rol && (u.rol.nombre_rol === 'Administrador' || (Array.isArray(u.rol.permisos) && u.rol.permisos.includes('*')));
            if (esAdmin && Number(u.estado) === 1 && this.activeAdminsCount <= 1) {
                return false;
            }
            return true;
        },

        getUserActionTooltip(u, action) {
            if (!u) return '';
            const isSelf = this.currentUser && Number(this.currentUser.usuario_id) === Number(u.usuario_id);
            const isTargetPrincipal = this.isUserPrincipal(u);
            const esAdmin = u.rol && (u.rol.nombre_rol === 'Administrador' || (Array.isArray(u.rol.permisos) && u.rol.permisos.includes('*')));
            const isLastAdmin = esAdmin && Number(u.estado) === 1 && this.activeAdminsCount <= 1;

            if (action === 'edit') {
                if (isTargetPrincipal && !isSelf) return 'El Administrador Principal solo puede ser editado por sí mismo';
                return 'Editar datos del usuario';
            }

            if (action === 'password') {
                if (isTargetPrincipal && !isSelf) return 'No puedes restablecer la contraseña del Administrador Principal';
                return 'Restablecer contraseña de acceso';
            }

            if (action === 'toggle') {
                if (isSelf) return 'No puedes bloquear tu propia cuenta en sesión';
                if (isTargetPrincipal) return 'No se puede bloquear al Administrador Principal del sistema';
                if (isLastAdmin) return 'No se puede bloquear al único Administrador activo del sistema';
                return Number(u.bloqueado) === 1 ? 'Desbloquear acceso al usuario' : 'Bloquear acceso al usuario';
            }

            if (action === 'delete') {
                if (isSelf) return 'No puedes eliminar tu propia cuenta en sesión';
                if (isTargetPrincipal) return 'No se puede eliminar al Administrador Principal del sistema';
                if (isLastAdmin) return 'No se puede eliminar al único Administrador activo del sistema';
                return 'Eliminar usuario';
            }

            return '';
        },

        // Contador auxiliar de usuarios por rol
        countUsersInRole(rolId) {
            if (!Array.isArray(this.usuarios)) return 0;
            return this.usuarios.filter(u => Number(u.id_rol) === Number(rolId)).length;
        },

        // --- MÉTODOS DE USUARIOS ---
        openUserModal(user = null) {
            if (user && !this.canEditUser(user)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Acción No Permitida',
                    text: 'El Administrador Principal del sistema solo puede ser editado por sí mismo.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
            }
            if (user) {
                this.isEditingUser = true;
                this.userForm = {
                    usuario_id: user.usuario_id,
                    id_rol: user.id_rol || (this.roles[0] ? this.roles[0].rol_id : ''),
                    nombre_apellido: user.nombre_apellido || '',
                    nombre_usuario: user.nombre_usuario || '',
                    contrasenia_usuario: '',
                    fecha_registro: user.fecha_registro || '',
                    estado: user.estado !== undefined ? Number(user.estado) : 1,
                    bloqueado: user.bloqueado !== undefined ? Number(user.bloqueado) : 0
                };
            } else {
                this.isEditingUser = false;
                this.userForm = {
                    usuario_id: null,
                    id_rol: (this.roles && this.roles[0]) ? this.roles[0].rol_id : '',
                    nombre_apellido: '',
                    nombre_usuario: '',
                    contrasenia_usuario: '',
                    fecha_registro: new Date().toISOString().slice(0, 10),
                    estado: 1,
                    bloqueado: 0
                };
            }
            this.showUserFormPassword = false;
            this.showUserModal = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        generateUserFormPassword() {
            const chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#$%&*';
            let pass = '';
            for (let i = 0; i < 10; i++) {
                pass += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            this.userForm.contrasenia_usuario = pass;
            this.showUserFormPassword = true;
        },

        async saveUser() {
            if (this.isSavingUser) return;

            const url = this.isEditingUser ?
                `/api/usuarios/${this.userForm.usuario_id}` :
                '/api/usuarios';
            const method = this.isEditingUser ? 'PUT' : 'POST';

            const payload = {
                nombre_apellido: (this.userForm.nombre_apellido || '').trim(),
                nombre_usuario: (this.userForm.nombre_usuario || '').trim(),
                estado: 1
            };

            // Solo enviar id_rol si está permitido modificarlo
            if (this.canChangeUserRole) {
                payload.id_rol = Number(this.userForm.id_rol);
            }

            // Solo enviar bloqueado si está permitido modificarlo; si está protegido, se mantiene en 0 (desbloqueado)
            if (this.canChangeUserStatus) {
                payload.bloqueado = Number(this.userForm.bloqueado);
            } else {
                payload.bloqueado = 0;
            }

            if (this.userForm.fecha_registro) {
                payload.fecha_registro = this.userForm.fecha_registro;
            }

            if (!this.isEditingUser || this.userForm.contrasenia_usuario) {
                payload.contrasenia_usuario = this.userForm.contrasenia_usuario;
            }

            this.isSavingUser = true;
            try {
                const res = await this.apiFetch(url, {
                    method,
                    body: JSON.stringify(payload)
                });

                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    const errDetail = data.errors ? Object.values(data.errors).flat().join('<br>') : (data.message || 'Error al guardar el usuario.');
                    throw new Error(errDetail);
                }

                this.showUserModal = false;
                await this.fetchUsuarios();

                Swal.fire({
                    icon: 'success',
                    title: this.isEditingUser ? '¡Usuario Actualizado!' : '¡Usuario Creado!',
                    text: `El usuario "${payload.nombre_usuario}" ha sido guardado exitosamente.`,
                    timer: 2000,
                    showConfirmButton: false,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo guardar',
                    html: error.message,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
            } finally {
                this.isSavingUser = false;
            }
        },

        // Bloquear / Desbloquear usuario con confirmación rápida
        async toggleUserStatus(u) {
            if (this.currentUser && Number(this.currentUser.usuario_id) === Number(u.usuario_id)) {
                Swal.fire({
                    icon: 'info',
                    title: 'Acción No Permitida',
                    text: 'No puedes bloquear o desactivar tu propia cuenta en sesión.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
            }

            if (this.isUserPrincipal(u)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Acción No Permitida',
                    text: 'No se puede desactivar o bloquear al Administrador Principal del sistema.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
            }

            const esAdmin = u.rol && (u.rol.nombre_rol === 'Administrador' || (Array.isArray(u.rol.permisos) && u.rol.permisos.includes('*')));
            if (esAdmin && Number(u.bloqueado) === 0 && this.activeAdminsCount <= 1) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Acción No Permitida',
                    text: 'No se puede desactivar o bloquear al único Administrador activo del sistema.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
            }

            const nuevoBloqueado = Number(u.bloqueado) === 1 ? 0 : 1;
            const accion = nuevoBloqueado === 1 ? 'bloquear' : 'desbloquear';

            const confirm = await Swal.fire({
                title: `¿${accion.charAt(0).toUpperCase() + accion.slice(1)} usuario?`,
                html: `¿Estás seguro de que deseas <b>${accion}</b> a <b>${u.nombre_apellido}</b> (@${u.nombre_usuario})?<br><small class="text-slate-400">${nuevoBloqueado === 1 ? 'El usuario no podrá acceder al sistema hasta ser rehabilitado.' : 'El usuario podrá volver a iniciar sesión de inmediato.'}</small>`,
                icon: nuevoBloqueado === 1 ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonColor: nuevoBloqueado === 1 ? '#e11d48' : '#10b981',
                confirmButtonText: nuevoBloqueado === 1 ? 'Sí, Bloquear' : 'Sí, Desbloquear',
                cancelButtonText: 'Cancelar',
                background: this.darkMode ? '#1e293b' : '#ffffff',
                color: this.darkMode ? '#fff' : '#0f172a'
            });

            if (!confirm.isConfirmed) return;

            try {
                const res = await this.apiFetch(`/api/usuarios/${u.usuario_id}`, {
                    method: 'PUT',
                    body: JSON.stringify({ bloqueado: nuevoBloqueado })
                });

                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || 'No se pudo actualizar el estado de acceso del usuario.');
                }

                u.bloqueado = nuevoBloqueado;
                await this.fetchUsuarios();

                this.notify(
                    nuevoBloqueado === 0 ? 'Usuario Desbloqueado' : 'Usuario Bloqueado',
                    `"${u.nombre_usuario}" ahora está ${nuevoBloqueado === 0 ? 'activo' : 'bloqueado'}.`,
                    nuevoBloqueado === 0 ? 'success' : 'warning',
                    2500
                );
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al cambiar estado',
                    text: err.message,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
            }
        },

        // --- MÉTODOS DE RESTABLECIMIENTO DE CONTRASEÑA ---
        openPasswordModal(u) {
            if (u && !this.canResetPassword(u)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Acción No Permitida',
                    text: 'No puedes restablecer la contraseña del Administrador Principal del sistema.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
            }
            this.passwordUser = u;
            this.newPasswordValue = '';
            this.showPasswordPlainText = false;
            this.showPasswordModal = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        generateRandomPassword() {
            const chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#$%&*';
            let pass = '';
            for (let i = 0; i < 10; i++) {
                pass += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            this.newPasswordValue = pass;
            this.showPasswordPlainText = true;
        },

        copyGeneratedPassword() {
            if (!this.newPasswordValue) return;
            navigator.clipboard.writeText(this.newPasswordValue)
                .then(() => {
                    this.notify('Copiado', 'Contraseña copiada al portapapeles.', 'success', 2000);
                })
                .catch(() => {});
        },

        async saveResetPassword() {
            if (this.isSavingPassword || !this.passwordUser) return;

            if (!this.newPasswordValue || this.newPasswordValue.length < 6) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Contraseña Muy Corta',
                    text: 'La contraseña debe tener al menos 6 caracteres.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
            }

            this.isSavingPassword = true;
            try {
                const res = await this.apiFetch(`/api/usuarios/${this.passwordUser.usuario_id}`, {
                    method: 'PUT',
                    body: JSON.stringify({
                        contrasenia_usuario: this.newPasswordValue
                    })
                });

                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || 'No se pudo actualizar la contraseña.');
                }

                this.showPasswordModal = false;

                await Swal.fire({
                    icon: 'success',
                    title: '¡Contraseña Restablecida!',
                    html: `Se ha asignado la nueva contraseña para <b>@${this.passwordUser.nombre_usuario}</b>.<br><br>Asegúrate de entregársela al usuario para que pueda acceder.`,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al cambiar contraseña',
                    text: err.message,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
            } finally {
                this.isSavingPassword = false;
            }
        },

        async deleteUser(u) {
            if (this.currentUser && Number(this.currentUser.usuario_id) === Number(u.usuario_id)) {
                Swal.fire({
                    icon: 'info',
                    title: 'Acción No Permitida',
                    text: 'No puedes eliminar tu propia cuenta en sesión.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
            }

            if (this.isUserPrincipal(u)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Acción No Permitida',
                    text: 'No se puede eliminar o desactivar al Administrador Principal del sistema.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
            }

            const esAdmin = u.rol && (u.rol.nombre_rol === 'Administrador' || (Array.isArray(u.rol.permisos) && u.rol.permisos.includes('*')));
            if (esAdmin && Number(u.estado) === 1 && this.activeAdminsCount <= 1) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Acción No Permitida',
                    text: 'No se puede eliminar o desactivar al único Administrador activo del sistema.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
            }

            const result = await Swal.fire({
                title: '¿Eliminar usuario?',
                html: `¿Estás seguro de que deseas eliminar permanentemente a <b>${u.nombre_apellido}</b> (@${u.nombre_usuario})?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                background: this.darkMode ? '#1e293b' : '#ffffff',
                color: this.darkMode ? '#fff' : '#0f172a'
            });

            if (result.isConfirmed) {
                try {
                    const res = await this.apiFetch(`/api/usuarios/${u.usuario_id}`, {
                        method: 'DELETE'
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        throw new Error(data.message || 'No se pudo eliminar el usuario.');
                    }
                    this.notify('Usuario Eliminado', `"${u.nombre_usuario}" fue eliminado con éxito.`, 'success');
                    await this.fetchUsuarios();
                } catch (err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'No se pudo eliminar',
                        text: err.message,
                        background: this.darkMode ? '#1e293b' : '#ffffff',
                        color: this.darkMode ? '#fff' : '#0f172a'
                    });
                }
            }
        },

        // --- MÉTODOS DE ROLES & PERMISOS DINÁMICOS (JSON) ---
        openRolModal(rol = null) {
            if (rol && (rol.nombre_rol === 'Administrador' || (Array.isArray(rol.permisos) && rol.permisos.includes('*')))) {
                Swal.fire({
                    icon: 'info',
                    title: 'Rol Protegido',
                    text: 'El rol Administrador es fundamental para el sistema y no puede ser modificado.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
            }

            if (rol) {
                this.isEditingRol = true;
                this.rolForm = {
                    rol_id: rol.rol_id,
                    nombre_rol: rol.nombre_rol || '',
                    descripcion_rol: rol.descripcion_rol || '',
                    permisos: Array.isArray(rol.permisos) ? [...rol.permisos] : [],
                    estado: rol.estado !== undefined ? Number(rol.estado) : 1
                };
            } else {
                this.isEditingRol = false;
                this.rolForm = {
                    rol_id: null,
                    nombre_rol: '',
                    descripcion_rol: '',
                    permisos: ['pos.acceso', 'ventas.crear'],
                    estado: 1
                };
            }
            this.showRolModal = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        hasRolPermission(clave) {
            if (!Array.isArray(this.rolForm.permisos)) return false;
            return this.rolForm.permisos.includes('*') || this.rolForm.permisos.includes(clave);
        },

        toggleRolPermission(clave) {
            if (!Array.isArray(this.rolForm.permisos)) {
                this.rolForm.permisos = [];
            }
            const idx = this.rolForm.permisos.indexOf(clave);
            if (idx === -1) {
                this.rolForm.permisos.push(clave);
            } else {
                this.rolForm.permisos.splice(idx, 1);
            }
        },

        selectAllPermissions() {
            const allKeys = [];
            this.availablePermissionsGroups.forEach(g => {
                g.permisos.forEach(p => {
                    if (!allKeys.includes(p.clave)) allKeys.push(p.clave);
                });
            });
            this.rolForm.permisos = allKeys;
        },

        deselectAllPermissions() {
            this.rolForm.permisos = [];
        },

        toggleGroupPermissions(grupo) {
            if (!Array.isArray(this.rolForm.permisos)) this.rolForm.permisos = [];
            const groupKeys = grupo.permisos.map(p => p.clave);
            const allSelected = groupKeys.every(k => this.rolForm.permisos.includes(k));

            if (allSelected) {
                this.rolForm.permisos = this.rolForm.permisos.filter(k => !groupKeys.includes(k));
            } else {
                groupKeys.forEach(k => {
                    if (!this.rolForm.permisos.includes(k)) this.rolForm.permisos.push(k);
                });
            }
        },

        async saveRol() {
            if (this.isSavingRol) return;

            if (this.isEditingRol && (this.rolForm.nombre_rol === 'Administrador' || (this.roles.find(r => r.rol_id === this.rolForm.rol_id)?.nombre_rol === 'Administrador'))) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Operación No Permitida',
                    text: 'El rol Administrador es inmutable y no puede ser modificado.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
            }

            const nombre = (this.rolForm.nombre_rol || '').trim();
            if (!nombre) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Nombre Requerido',
                    text: 'Debe ingresar un nombre identificador para el rol.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
            }

            const url = this.isEditingRol ?
                `/api/roles/${this.rolForm.rol_id}` :
                '/api/roles';
            const method = this.isEditingRol ? 'PUT' : 'POST';

            const payload = {
                nombre_rol: nombre,
                descripcion_rol: (this.rolForm.descripcion_rol || '').trim(),
                permisos: Array.isArray(this.rolForm.permisos) ? this.rolForm.permisos : [],
                estado: Number(this.rolForm.estado)
            };

            this.isSavingRol = true;
            try {
                const res = await this.apiFetch(url, {
                    method,
                    body: JSON.stringify(payload)
                });

                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    const errDetail = data.errors ? Object.values(data.errors).flat().join('<br>') : (data.message || 'Error al guardar el rol.');
                    throw new Error(errDetail);
                }

                this.showRolModal = false;
                await this.fetchUsuarios();

                Swal.fire({
                    icon: 'success',
                    title: this.isEditingRol ? '¡Rol Actualizado!' : '¡Rol Creado!',
                    text: `El rol "${payload.nombre_rol}" ha sido configurado con éxito.`,
                    timer: 2000,
                    showConfirmButton: false,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo guardar el rol',
                    html: error.message,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
            } finally {
                this.isSavingRol = false;
            }
        },

        async deleteRol(rol) {
            if (rol.nombre_rol === 'Administrador') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Operación Denegada',
                    text: 'El rol Administrador es fundamental para el sistema y no puede eliminarse.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
            }

            const result = await Swal.fire({
                title: '¿Eliminar rol?',
                html: `¿Estás seguro de que deseas eliminar el rol <b>${rol.nombre_rol}</b>?<br><small class="text-slate-400">Solo es posible si no tiene usuarios activos asignados.</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                background: this.darkMode ? '#1e293b' : '#ffffff',
                color: this.darkMode ? '#fff' : '#0f172a'
            });

            if (result.isConfirmed) {
                try {
                    const res = await this.apiFetch(`/api/roles/${rol.rol_id}`, {
                        method: 'DELETE'
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        throw new Error(data.message || 'No se pudo eliminar el rol.');
                    }

                    this.notify('Rol Eliminado', `"${rol.nombre_rol}" fue eliminado.`, 'success');
                    await this.fetchUsuarios();
                } catch (err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'No se pudo eliminar el rol',
                        text: err.message,
                        background: this.darkMode ? '#1e293b' : '#ffffff',
                        color: this.darkMode ? '#fff' : '#0f172a'
                    });
                }
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
        ventaFechaDesde: '',
        ventaFechaHasta: '',
        ventaUsuarioFilter: '',
        ventaQuickRange: '',
        cart: [],
        posSale: {
            cliente_nombre: 'Consumidor Final',
            metodo_pago: 'Efectivo',
            referencia_transferencia: '',
            monto_recibido: null,
            descuento_venta: 0,
            tipo_descuento: 'monto',
            descuento_porcentaje: 0,
        },
        showSaleDetailModal: false,
        selectedSale: null,
        showCartDrawer: false,

        // Estado del modal de animación de facturación en curso
        isProcessingBilling: false,
        billingProgress: 15,
        billingStatusText: 'Procesando transacción...',
        billingCandidateCode: '',

        // Estado del comprobante imprimible
        showReceiptModal: false,
        receiptData: null,
        receiptEmpresa: null,
        receiptAnulada: false,
        loadingReceipt: false,

        // Estado y control de ventas en espera (Parked Orders / RF-16)
        ventasEspera: [],
        showVentasEsperaModal: false,
        loadingVentasEspera: false,
        resumedVentaEsperaId: null,

        addToCart(product) {
            if (product.existencia_bodega <= 0) {
                this.notify('Sin Stock', 'El producto no cuenta con existencias disponibles en bodega.', 'warning');
                return;
            }

            const existing = this.cart.find(i => i.id_producto === product.producto_id);
            if (existing) {
                if (existing.cantidad + 1 > product.existencia_bodega) {
                    this.notify('Límite de Stock', `Solo hay ${product.existencia_bodega} unidades disponibles.`, 'warning');
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
                    existencia_bodega: Number(product.existencia_bodega || 0),
                });
            }
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        getProductStock(productId) {
            const p = this.productos.find(prod => prod.producto_id === productId);
            return p ? (p.existencia_bodega ?? 0) : 0;
        },

        increaseCartQty(index) {
            const item = this.cart[index];
            const product = this.productos.find(p => p.producto_id === item.id_producto);
            if (product && item.cantidad + 1 > product.existencia_bodega) {
                this.notify('Límite de Stock', `Solo hay ${product.existencia_bodega} unidades disponibles.`, 'warning');
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
            this.resumedVentaEsperaId = null;
            this.posSale.cliente_nombre = 'Consumidor Final';
            this.posSale.descuento_venta = 0;
            this.posSale.descuento_porcentaje = 0;
            this.posSale.tipo_descuento = 'monto';
            this.posSale.referencia_transferencia = '';
            this.posSale.monto_recibido = null;
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

        async processSale() {
            if (this.cart.length === 0) return;

            // Bloqueo estricto del POS: No se puede facturar sin turno activo
            if (!this.turnoActivo) {
                this.notify('Caja Cerrada', 'No se puede facturar sin turno activo. Abre una caja primero.', 'warning', 3000);
                return;
            }

            // Validación de voucher (Tarjeta) y referencia (Transferencia) con longitud mínima
            if (this.posSale.metodo_pago === 'Transferencia' || this.posSale.metodo_pago === 'Tarjeta') {
                const ref = (this.posSale.referencia_transferencia || '').trim();
                const isTarjeta = this.posSale.metodo_pago === 'Tarjeta';
                const nombreCampo = isTarjeta ? 'número de voucher / autorización' : 'número de transferencia / referencia';
                const minLength = 4;

                if (!ref) {
                    this.notify(isTarjeta ? 'Voucher Requerido' : 'Referencia Requerida', `Debes ingresar el ${nombreCampo} para procesar el pago con ${this.posSale.metodo_pago.toLowerCase()}.`, 'warning');
                    return;
                }

                if (ref.length < minLength) {
                    this.notify('Mínimo Requerido No Alcanzado', `El ${nombreCampo} debe contener al menos ${minLength} caracteres.`, 'warning');
                    return;
                }
            }

            // Validación obligatoria de efectivo recibido para evitar errores humanos de cálculo
            if (this.posSale.metodo_pago === 'Efectivo') {
                const montoRecibidoNum = Number(this.posSale.monto_recibido);
                if (!this.posSale.monto_recibido || isNaN(montoRecibidoNum) || montoRecibidoNum <= 0) {
                    this.notify('Efectivo Requerido', 'Debes ingresar el monto entregado por el cliente (o pulsar "Paga Exacto").', 'warning');
                    return;
                }

                if (montoRecibidoNum < this.cartTotal) {
                    const faltante = this.cartTotal - montoRecibidoNum;
                    this.notify('Efectivo Insuficiente', `El cliente entregó ${this.formatCurrency(montoRecibidoNum)}, pero el total es ${this.formatCurrency(this.cartTotal)}. Faltan ${this.formatCurrency(faltante)}.`, 'warning');
                    return;
                }
            }

            // Cálculo del efectivo entregado y cambio correspondiente
            const efectivoRecibido = this.posSale.metodo_pago === 'Efectivo'
                ? Number(this.posSale.monto_recibido)
                : null;
            const cambioCalculado = this.posSale.metodo_pago === 'Efectivo'
                ? Math.max(0, (efectivoRecibido || 0) - this.cartTotal)
                : 0;

            // Generar código de factura de inmediato en 0ms sin bloquear con peticiones de red
            let candidateCode = '';
            if (this.ventas && this.ventas.length > 0) {
                const nums = this.ventas.map(v => {
                    const match = (v.codigo_venta || '').match(/(\d+)$/);
                    return match ? parseInt(match[1], 10) : 0;
                });
                let nextNum = Math.max(0, ...nums) + 1;
                candidateCode = `FAC-2026-${String(nextNum).padStart(4, '0')}`;
                while (this.ventas.some(v => v.codigo_venta === candidateCode)) {
                    nextNum++;
                    candidateCode = `FAC-2026-${String(nextNum).padStart(4, '0')}`;
                }
            } else {
                candidateCode = `FAC-2026-${Date.now().toString().slice(-6)}`;
            }

            const salePayload = {
                id_usuario: this.currentUser?.usuario_id || (this.usuarios[0] ? this.usuarios[0].usuario_id : 1),
                id_caja: this.turnoActivo ? this.turnoActivo.id_caja : null,
                cliente_nombre: (this.posSale.cliente_nombre && this.posSale.cliente_nombre.trim()) ? this.posSale.cliente_nombre.trim() : 'Consumidor Final',
                codigo_venta: candidateCode,
                metodo_pago: this.posSale.metodo_pago,
                referencia_transferencia: (this.posSale.metodo_pago === 'Transferencia' || this.posSale.metodo_pago === 'Tarjeta')
                    ? (this.posSale.referencia_transferencia ? this.posSale.referencia_transferencia.trim() : null)
                    : null,
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

            this.billingCandidateCode = candidateCode;
            this.billingProgress = 15;
            this.billingStatusText = 'Procesando transacción...';
            this.isProcessingBilling = true;
            this.loading = true;

            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });

            // Progreso dinámico y reactivo en pantalla mientras el backend emite la factura
            let progressInterval = setInterval(() => {
                if (this.billingProgress < 90) {
                    this.billingProgress += 15;
                    if (this.billingProgress >= 30 && this.billingProgress < 55) {
                        this.billingStatusText = 'Verificando existencias...';
                    } else if (this.billingProgress >= 55 && this.billingProgress < 75) {
                        this.billingStatusText = 'Registrando pago...';
                    } else if (this.billingProgress >= 75) {
                        this.billingStatusText = 'Generando comprobante fiscal...';
                    }
                }
            }, 200);

            try {
                let res = await this.apiFetch('/api/ventas', {
                    method: 'POST',
                    body: JSON.stringify(salePayload)
                });

                // Si ocurriese una colisión inesperada de código por concurrencia, reintentar con sufijo único
                if (!res.ok) {
                    const clonedRes = res.clone();
                    const errData = await clonedRes.json().catch(() => ({}));
                    const codeCollision = (errData.errors && errData.errors.codigo_venta) ||
                                          (typeof errData.message === 'string' && errData.message.includes('codigo venta'));
                    if (codeCollision) {
                        salePayload.codigo_venta = `FAC-2026-${Date.now().toString().slice(-6)}`;
                        res = await this.apiFetch('/api/ventas', {
                            method: 'POST',
                            body: JSON.stringify(salePayload)
                        });
                    }
                }

                if (!res.ok) {
                    const err = await res.json();
                    throw new Error(err.message || 'Error al procesar la venta');
                }

                const responseData = await res.json();
                const newSale = responseData.venta;

                // Completar la barra de progreso al 100% con feedback positivo
                clearInterval(progressInterval);
                this.billingProgress = 100;
                this.billingStatusText = '¡Factura emitida exitosamente!';
                await new Promise(r => setTimeout(r, 260));

                // Descontar existencias localmente para respuesta visual instantánea (0ms)
                salePayload.detalles.forEach(d => {
                    const p = this.productos.find(prod => prod.producto_id === d.id_producto);
                    if (p) p.existencia_bodega = Math.max(0, p.existencia_bodega - d.cantidad);
                });

                // Si la venta provenía de una orden en espera reanudada, se cierra ahora que fue facturada
                const prevResumedId = this.resumedVentaEsperaId;
                this.clearCart();
                if (prevResumedId) {
                    this.apiFetch(`/api/ventas-espera/${prevResumedId}`, { method: 'DELETE' })
                        .then(() => this.fetchVentasEspera())
                        .catch(err => console.warn('Error al cerrar orden en espera procesada:', err));
                }

                // Actualizar historial en memoria de inmediato (0ms)
                if (Array.isArray(this.ventas)) {
                    this.ventas = [newSale, ...this.ventas];
                    if (typeof this.renderDashboardCharts === 'function') {
                        this.renderDashboardCharts();
                    }
                }

                // Precargar datos del comprobante con efectivo y cambio para apertura inmediata (0ms)
                newSale.monto_recibido = efectivoRecibido;
                newSale.cambio = cambioCalculado;
                this.receiptData = newSale;
                this.receiptAnulada = Boolean(newSale.estado === 0);

                // Guardar en caché de sesión para recordar el monto entregado en el voucher
                if (salePayload.metodo_pago === 'Efectivo' && efectivoRecibido) {
                    try {
                        const cashMap = JSON.parse(sessionStorage.getItem('pos_cash_records') || '{}');
                        cashMap[newSale.venta_id] = { monto_recibido: efectivoRecibido, cambio: cambioCalculado };
                        cashMap[newSale.codigo_venta] = { monto_recibido: efectivoRecibido, cambio: cambioCalculado };
                        sessionStorage.setItem('pos_cash_records', JSON.stringify(cashMap));
                    } catch (e) {}
                }

                // Sincronizaciones secundarias en background sin bloquear la interfaz
                setTimeout(() => {
                    this.fetchInventario();
                    this.fetchBitacoras();
                }, 100);

                // Cerrar modal de facturación y abrir comprobante
                this.isProcessingBilling = false;
                await this.openReceiptModal(newSale.venta_id, newSale);

                // Notificación sutil tipo tarjeta en esquina superior derecha
                const cambioInfo = (salePayload.metodo_pago === 'Efectivo' && cambioCalculado > 0)
                    ? ` | Cambio: ${this.formatCurrency(cambioCalculado)}`
                    : '';
                this.notify('¡Venta Registrada!', `Factura ${newSale.codigo_venta} por ${this.formatCurrency(newSale.total_venta)}${cambioInfo}`, 'success', 3500);

            } catch (error) {
                clearInterval(progressInterval);
                this.isProcessingBilling = false;
                this.notify('Error al procesar venta', error.message, 'error', 4000);
            } finally {
                this.loading = false;
            }
        },

        // Carga y apertura del modal de comprobante (con soporte para datos precargados a 0ms)
        async openReceiptModal(ventaId, preloadedData = null) {
            // Asegurar que los datos del negocio estén cargados para el encabezado del comprobante
            if (!this.receiptEmpresa && typeof this.fetchEmpresa === 'function') {
                await this.fetchEmpresa();
            }

            // Si ya se tienen los datos precargados en memoria, abrir sin peticiones adicionales (0ms)
            if (preloadedData && preloadedData.venta_detalles) {
                this.receiptData = preloadedData;
                if (preloadedData.empresa) {
                    this.receiptEmpresa = preloadedData.empresa;
                }
                if (!this.receiptData.monto_recibido) {
                    try {
                        const cashMap = JSON.parse(sessionStorage.getItem('pos_cash_records') || '{}');
                        const rec = cashMap[this.receiptData.venta_id] || cashMap[this.receiptData.codigo_venta];
                        if (rec) {
                            this.receiptData.monto_recibido = rec.monto_recibido;
                            this.receiptData.cambio = rec.cambio;
                        }
                    } catch (e) {}
                }
                this.receiptAnulada = Boolean(preloadedData.estado === 0);
                this.showReceiptModal = true;
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
                return;
            }

            if (this.receiptData && (this.receiptData.venta_id == ventaId || this.receiptData.codigo_venta == ventaId)) {
                this.showReceiptModal = true;
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
                return;
            }

            if (!ventaId) return;
            this.loadingReceipt = true;
            this.showReceiptModal = true;
            this.receiptData = null;
            try {
                const res = await this.apiFetch(`/api/ventas/${ventaId}/comprobante`);
                if (!res.ok) {
                    throw new Error('No se pudo obtener el comprobante de la venta.');
                }
                const data = await res.json();
                this.receiptData = data.comprobante;
                if (data.empresa) {
                    this.receiptEmpresa = data.empresa;
                }
                try {
                    const cashMap = JSON.parse(sessionStorage.getItem('pos_cash_records') || '{}');
                    const rec = cashMap[this.receiptData.venta_id] || cashMap[this.receiptData.codigo_venta];
                    if (rec) {
                        this.receiptData.monto_recibido = rec.monto_recibido;
                        this.receiptData.cambio = rec.cambio;
                    }
                } catch (e) {}
                this.receiptAnulada = Boolean(data.anulada);
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            } catch (error) {
                this.showReceiptModal = false;
                this.notify('Error de Comprobante', error.message, 'error');
            } finally {
                this.loadingReceipt = false;
            }
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

        // Disparador de impresión nativa del sistema
        printReceipt() {
            window.print();
        },

        viewSaleDetails(sale) {
            this.selectedSale = sale;
            this.showSaleDetailModal = true;
        },

        async deleteSale(sale) {
            const usuariosActivos = (this.usuarios || []).filter(usuario => Number(usuario.estado) === 1);
            if (usuariosActivos.length === 0) {
                this.notify('No se puede anular', 'No hay usuarios activos disponibles para registrar al responsable.', 'error');
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
                this.notify('No se pudo anular la venta', error.message, 'error');
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
                this.notify('Venta Anulada', 'La anulación se guardó, pero no se pudo actualizar la pantalla.', 'warning');
                return;
            }

            this.notify('Venta Anulada', `La factura ${sale.codigo_venta} fue anulada correctamente.`, 'success');
        },

        // Métodos de control y filtrado del Historial de Ventas (RF-21)
        clearVentaFilters() {
            this.searchVenta = '';
            this.ventaFechaDesde = '';
            this.ventaFechaHasta = '';
            this.ventaUsuarioFilter = '';
            this.ventaQuickRange = '';
        },

        getVentaLocalDate(dateStr) {
            if (!dateStr) return '';
            let normalized = String(dateStr).trim();
            // Normalizar fechas UTC sin sufijo Z (ej: "2026-09-30 01:35:00") a ISO UTC para interpretar en hora local
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

        isVentaQuickActive(range) {
            const now = new Date();
            const pad = n => String(n).padStart(2, '0');
            const toYmd = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
            const today = toYmd(now);

            if (range === 'hoy') {
                return Boolean(this.ventaFechaDesde && this.ventaFechaDesde === today && this.ventaFechaHasta === today);
            }
            if (range === '7dias') {
                const past = new Date();
                past.setDate(past.getDate() - 6);
                return Boolean(this.ventaFechaDesde === toYmd(past) && this.ventaFechaHasta === today);
            }
            if (range === 'mes') {
                const firstDay = toYmd(new Date(now.getFullYear(), now.getMonth(), 1));
                return Boolean(this.ventaFechaDesde === firstDay && this.ventaFechaHasta === today);
            }
            return false;
        },

        setVentaQuickDate(range) {
            const now = new Date();
            const pad = n => String(n).padStart(2, '0');
            const toYmd = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

            this.ventaQuickRange = range;

            if (range === 'hoy') {
                const todayStr = toYmd(now);
                this.ventaFechaDesde = todayStr;
                this.ventaFechaHasta = todayStr;
            } else if (range === '7dias') {
                const past = new Date();
                past.setDate(past.getDate() - 6);
                this.ventaFechaDesde = toYmd(past);
                this.ventaFechaHasta = toYmd(now);
            } else if (range === 'mes') {
                const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                this.ventaFechaDesde = toYmd(firstDay);
                this.ventaFechaHasta = toYmd(now);
            } else if (range === 'todos') {
                this.ventaFechaDesde = '';
                this.ventaFechaHasta = '';
                this.ventaQuickRange = '';
            }
        },

        // Métodos de Ventas en Espera / Cuentas Pendientes (Parked Orders - RF-16)
        async fetchVentasEspera() {
            try {
                this.loadingVentasEspera = true;
                const cajaId = this.turnoActivo?.id_caja || '';
                const url = cajaId ? `/api/ventas-espera?id_caja=${cajaId}` : '/api/ventas-espera';
                const res = await this.apiFetch(url);
                if (res.ok) {
                    const json = await res.json();
                    this.ventasEspera = Array.isArray(json.data) ? json.data : [];
                }
            } catch (e) {
                console.error('Error cargando ventas en espera:', e);
            } finally {
                this.loadingVentasEspera = false;
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            }
        },

        openVentasEsperaModal() {
            this.fetchVentasEspera();
            this.showVentasEsperaModal = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        async parkCurrentSale() {
            if (!this.turnoActivo) {
                this.notify('Caja Cerrada', 'Debes tener un turno de caja abierto para poner ventas en espera.', 'warning');
                return;
            }
            if (this.cart.length === 0) return;

            const clienteNombre = (this.posSale.cliente_nombre && this.posSale.cliente_nombre.trim()) ? this.posSale.cliente_nombre.trim() : 'Consumidor Final';

            const payload = {
                id_usuario: this.currentUser?.usuario_id || (this.usuarios[0] ? this.usuarios[0].usuario_id : 1),
                id_caja: this.turnoActivo?.id_caja || null,
                cliente_nombre: clienteNombre,
                descuento: this.posSale.descuento_venta || 0,
                detalles: this.cart.map(i => ({
                    id_producto: i.id_producto,
                    cantidad: i.cantidad,
                    precio_unitario: i.precio_unitario,
                }))
            };

            this.loading = true;
            try {
                let res;
                // Si ya estábamos editando una orden en espera reanudada, actualizar en vez de duplicar
                if (this.resumedVentaEsperaId) {
                    res = await this.apiFetch(`/api/ventas-espera/${this.resumedVentaEsperaId}`, {
                        method: 'PUT',
                        body: JSON.stringify(payload)
                    });
                } else {
                    res = await this.apiFetch('/api/ventas-espera', {
                        method: 'POST',
                        body: JSON.stringify(payload)
                    });
                }

                const data = await res.json();
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Error al poner la venta en espera');
                }

                const nombreAsignado = data.data?.identificador_cuenta || 'Venta en Espera';

                this.resumedVentaEsperaId = null;
                this.clearCart();
                this.showCartDrawer = false;
                await this.fetchVentasEspera();

                this.notify('¡Venta en Espera!', `Guardada exitosamente como "${nombreAsignado}".`, 'success', 2500);
            } catch (error) {
                this.notify('Error al pausar venta', error.message, 'error');
            } finally {
                this.loading = false;
            }
        },

        async resumeVentaEspera(venta) {
            if (this.cart.length > 0 && this.resumedVentaEsperaId !== venta.venta_espera_id) {
                const confirm = await Swal.fire({
                    icon: 'question',
                    title: '¿Reemplazar carrito actual?',
                    text: 'Tienes productos en el carrito. Si continúas, se reemplazarán por los productos de esta orden pausada.',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, reanudar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#f59e0b',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                if (!confirm.isConfirmed) return;
            }

            try {
                this.loading = true;
                const res = await this.apiFetch(`/api/ventas-espera/${venta.venta_espera_id}`);
                if (!res.ok) {
                    throw new Error('No se pudo cargar la venta en espera.');
                }
                const json = await res.json();
                const v = json.data;

                this.cart = (v.detalles || []).map(d => ({
                    id_producto: d.id_producto,
                    nombre_producto: d.producto?.nombre_producto || `Producto #${d.id_producto}`,
                    cantidad: Number(d.cantidad),
                    precio_unitario: Number(d.precio_unitario),
                    subtotal_venta_detalle: Number(d.subtotal),
                    existencia_bodega: Number(d.producto?.existencia_bodega || 0),
                }));

                this.posSale.cliente_nombre = v.cliente_nombre || 'Consumidor Final';
                this.posSale.descuento_venta = Number(v.descuento || 0);
                this.posSale.tipo_descuento = 'monto';
                this.posSale.descuento_porcentaje = 0;
                this.posSale.monto_recibido = null;

                // Marcar como orden activa en el carrito sin borrar de la base de datos
                this.resumedVentaEsperaId = v.venta_espera_id;

                this.showVentasEsperaModal = false;
                this.showCartDrawer = true;

                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });

                this.notify('Venta Cargada', `"${v.identificador_cuenta}" cargada al carrito`, 'success', 2000);
            } catch (error) {
                this.notify('Error al cargar orden', error.message, 'error');
            } finally {
                this.loading = false;
            }
        },

        async discardVentaEspera(venta) {
            const confirm = await Swal.fire({
                icon: 'warning',
                title: '¿Descartar orden en espera?',
                text: `¿Estás seguro de descartar la venta "${venta.identificador_cuenta}"? Esta acción no se puede deshacer.`,
                showCancelButton: true,
                confirmButtonText: 'Sí, descartar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#ef4444',
                background: this.darkMode ? '#1e293b' : '#ffffff',
                color: this.darkMode ? '#fff' : '#0f172a'
            });

            if (!confirm.isConfirmed) return;

            try {
                this.loading = true;
                const res = await this.apiFetch(`/api/ventas-espera/${venta.venta_espera_id}`, {
                    method: 'DELETE'
                });
                if (!res.ok) {
                    throw new Error('Error al descartar la venta en espera.');
                }

                if (this.resumedVentaEsperaId === venta.venta_espera_id) {
                    this.resumedVentaEsperaId = null;
                }

                await this.fetchVentasEspera();

                this.notify('Venta Descartada', `"${venta.identificador_cuenta}" fue descartada.`, 'info', 2000);
            } catch (error) {
                this.notify('Error al descartar', error.message, 'error');
            } finally {
                this.loading = false;
            }
        }
    };
}

// Función auxiliar para combinar módulos preservando getters y setters reactivos de Alpine.js
function mergeModules(target, ...sources) {
    for (const source of sources) {
        if (!source) continue;
        Object.defineProperties(target, Object.getOwnPropertyDescriptors(source));
    }
    return target;
}

// 11. Función Principal del Aplicativo
function app() {
    const appObj = {
        // Estado General
        currentTab: 'dashboard',
        loading: false,

        // Datos de Entidades
        productos: [],
        categorias: [],
        usuarios: [],
        roles: [],
        ventas: [],
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

        // Estado de Apertura de Turno (RF-28)
        showCajaAperturaModal: false,
        isSavingCajaApertura: false,
        cajaAperturaForm: {
            id_caja: '',
            monto_apertura: '0.00'
        },

        // Estado de Arqueo y Cierre de Turno (RF-28)
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
            moneda_simbolo: 'C$'
        },

        // Navegación
        navItems: [
            { id: 'dashboard', label: 'Dashboard', icon: 'layout-dashboard' },
            { id: 'pos', label: 'Punto de Venta (POS)', icon: 'shopping-cart' },
            { id: 'productos', label: 'Productos & Stock', icon: 'package' },
            { id: 'categorias', label: 'Categorías', icon: 'tags' },
            { id: 'ventas', label: 'Historial de Ventas', icon: 'receipt' },
            { id: 'proveedores', label: 'Proveedores', icon: 'truck' },
            { id: 'cuentas-por-pagar', label: 'Cuentas por Pagar', icon: 'credit-card' },
            { id: 'caja', label: 'Cajas & Arqueos', icon: 'wallet' },
            { id: 'inventario', label: 'Kardex / Movimientos', icon: 'arrow-left-right' },
            { id: 'usuarios', label: 'Usuarios & Roles', icon: 'user-cog' },
            { id: 'bitacora', label: 'Bitácora Auditoría', icon: 'shield-check' },
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

        // Propiedades Computadas
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
                    return this.hasPermission('ventas.ver') || this.hasPermission('ventas.crear');
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
                    const clientMatch = (v.cliente_nombre && v.cliente_nombre.toLowerCase().includes(term)) ||
                        (v.cliente && v.cliente.nombre_apellido_cliente && v.cliente.nombre_apellido_cliente.toLowerCase().includes(term));
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

        // Lazy Loading de Pestañas
        loadedTabs: [],

        async loadTab(tab, force = false) {
            if (!this.isAuthenticated) return;
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
                            this.fetchProductos()
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
                    case 'proveedores':
                    case 'cuentas-por-pagar':
                        await Promise.all([
                            this.fetchProveedores(),
                            this.fetchCuentasPorPagar(),
                            this.fetchCxPKPIs()
                        ]);
                        break;
                    case 'caja':
                        await Promise.all([
                            this.fetchVentas(),
                            this.fetchBitacoras().catch(() => {})
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

        // Inicialización
        initApp() {
            this.initTheme();
            this.initAuth();

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

            this.$watch('ventas', () => {
                if (this.currentTab === 'dashboard') {
                    this.renderDashboardCharts();
                }
            });

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
                            moneda_simbolo: data.moneda_simbolo || 'C$'
                        };
                    }
                }
            } catch (e) {
                console.error('Error cargando datos de empresa:', e);
            }
        },

        // Getters de Movimientos de Caja (RF-25 / RF-28)
        get filteredCajaMovimientos() {
            let list = Array.isArray(this.cajaMovimientos) ? this.cajaMovimientos : [];
            const term = (this.cajaSearch || '').trim().toLowerCase();
            const filtroTipo = this.cajaMovimientoFiltro || 'todos';
            const filtroMetodo = (this.cajaMetodoPagoFiltro || '').toLowerCase();
            const filtroUsuario = this.cajaUsuarioFiltro;

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

            // 2. Filtro por Usuario / Cajero (Solo admin)
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
            if (mov.turno && mov.turno.id_usuario) {
                const u = Array.isArray(this.usuarios) ? this.usuarios.find(user => Number(user.usuario_id) === Number(mov.turno.id_usuario)) : null;
                if (u && u.nombre_apellido) return u.nombre_apellido;
            }
            return 'Usuario #' + (mov.venta?.id_usuario || mov.turno?.id_usuario || '1');
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
                this.notify('Monto Requerido', 'El monto del movimiento debe ser un importe mayor a cero.', 'warning');
                return;
            }

            const just = (form.justificacion || '').trim();
            if (just.length < 3) {
                this.notify('Motivo Obligatorio', 'Debe ingresar una justificación o motivo de al menos 3 caracteres (RF-25).', 'warning');
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

                this.notify('¡Movimiento Registrado!', `${form.tipo_movimiento === 'Ingreso' ? 'Ingreso' : 'Egreso'} de C$ ${montoNum.toFixed(2)} registrado exitosamente en caja.`, 'success', 2500);

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
                this.notify('Turno ya Activo', `Ya tienes un turno activo en la caja #${this.turnoActivo.id_caja}. Debes cerrarlo antes de abrir uno nuevo.`, 'warning');
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

                this.notify('¡Turno Aperturado!', 'La caja ha sido abierta exitosamente. El POS ya está listo para facturar.', 'success');

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
                this.notify('Efectivo Requerido', 'Debe ingresar el monto total de efectivo físico contado en caja.', 'warning');
                return;
            }

            const diff = this.cajaCierreDiferencia;
            if (diff !== null && diff !== 0 && (!this.cajaCierreForm.observacion_cierre || !this.cajaCierreForm.observacion_cierre.trim())) {
                this.notify('Justificación Obligatoria', 'Existe un descuadre en el arqueo (' + (diff > 0 ? '+' : '') + this.formatCurrency(diff) + '). Ingrese una justificación.', 'warning');
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

                this.notify(isEdit ? '¡Caja Actualizada!' : '¡Caja Creada!', data.message || 'La caja física ha sido guardada exitosamente.', 'success');

            } catch (err) {
                console.error('Error guardando caja:', err);
                this.notify('No se pudo guardar la caja', err.message || 'Ocurrió un error inesperado.', 'error');
            } finally {
                this.isSavingCaja = false;
            }
        },

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
        dashboardModule()
    );
}

// Exponer la función app globalmente para Alpine.js
window.app = app;
