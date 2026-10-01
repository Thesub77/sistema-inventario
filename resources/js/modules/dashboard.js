/**
 * ==============================================================================
 * FACTURASTOCK PRO - MÓDULO DE DASHBOARD INTELIGENTE
 * Archivo: resources/js/modules/dashboard.js
 * Descripción: Métricas analíticas, alertas de stock, desglose de turno (RF-26),
 *              gráficos interactivos (RF-31, RF-32) y baja rotación (RF-33).
 *              Optimizado mediante agregaciones directas desde el Backend (Issue #19).
 * ==============================================================================
 */

export function dashboardModule() {
    return {
        dashboardData: null,
        dashboardPeriodo: 'hoy', // 'hoy', '7dias', 'mes', 'todo'
        dashboardVentasView: 'dias', // 'dias', 'semanas'
        bajaRotacionFiltro: 'todos', // 'todos', 'sin_ventas', 'poca_rotacion'
        stockAlertFiltro: 'todos', // 'todos', 'critico', 'urgente', 'advertencia'
        chartVentasInstance: null,
        chartTopInstance: null,

        // Carga optimizada de métricas calculadas en el servidor
        async fetchDashboardData() {
            try {
                // Obtener fecha local en formato YYYY-MM-DD (evita desfasaje UTC)
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

        // RF-26: Consulta y métricas de ventas del turno en tiempo real
        get ventasTurno() {
            const turno = this.turnoActivo;
            if (turno && turno.fecha_hora_apertura) {
                const aperturaTime = new Date(turno.fecha_hora_apertura.replace(' ', 'T')).getTime();
                const turnoVentas = (this.ventas || []).filter(v => {
                    if (!v.fecha_hora_venta || Number(v.estado) === 0) return false;
                    const ventaTime = new Date(v.fecha_hora_venta.replace(' ', 'T')).getTime();
                    return !isNaN(ventaTime) && ventaTime >= aperturaTime;
                });
                if (turnoVentas.length > 0) return turnoVentas;
            }

            // Fallback: Ventas con fecha de hoy (comparando fecha local y fecha UTC)
            const hoyLocal = new Date().toLocaleDateString('en-CA');
            const hoyUTC = new Date().toISOString().slice(0, 10);

            return (this.ventas || []).filter(v => {
                if (!v.fecha_hora_venta || Number(v.estado) === 0) return false;
                const vDate = v.fecha_hora_venta.slice(0, 10);
                const vLocalDate = this.getVentaLocalDate ? this.getVentaLocalDate(v.fecha_hora_venta) : vDate;
                return vDate === hoyLocal || vDate === hoyUTC || vLocalDate === hoyLocal;
            });
        },

        get ventasTurnoStats() {
            const list = this.ventasTurno;
            const turno = this.turnoActivo;
            const montoApertura = turno ? Number(turno.monto_apertura || 0) : 0;

            // Si no hay ventas en memoria pero el endpoint /api/dashboard/resumen devolvió estadísticas del turno/día
            if (list.length === 0 && this.dashboardData?.ventasTurnoStats && Number(this.dashboardData.ventasTurnoStats.total || 0) > 0) {
                const dbStats = this.dashboardData.ventasTurnoStats;
                const total = Number(dbStats.total || 0);
                const efectivo = Number(dbStats.efectivo || 0);
                return {
                    turnoActivo: Boolean(turno),
                    cajaNombre: turno?.caja?.nombre_caja || (turno ? `Caja #${turno.id_caja}` : 'Caja Principal'),
                    cajeroNombre: turno?.usuario?.nombre_apellido || '',
                    fechaApertura: turno?.fecha_hora_apertura || null,
                    montoApertura,
                    efectivoEsperado: montoApertura + efectivo,
                    total,
                    totalTickets: Number(dbStats.totalTickets || 0),
                    efectivo,
                    countEfectivo: Number(dbStats.countEfectivo || 0),
                    pctEfectivo: Number(dbStats.pctEfectivo || 0),
                    transferencia: Number(dbStats.transferencia || 0),
                    countTransferencia: Number(dbStats.countTransferencia || 0),
                    pctTransferencia: Number(dbStats.pctTransferencia || 0),
                    tarjeta: Number(dbStats.tarjeta || 0),
                    countTarjeta: Number(dbStats.countTarjeta || 0),
                    pctTarjeta: Number(dbStats.pctTarjeta || 0)
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

            return {
                turnoActivo: Boolean(turno),
                cajaNombre: turno?.caja?.nombre_caja || (turno ? `Caja #${turno.id_caja}` : 'Caja Principal'),
                cajeroNombre: turno?.usuario?.nombre_apellido || '',
                fechaApertura: turno?.fecha_hora_apertura || null,
                montoApertura,
                efectivoEsperado: montoApertura + efectivo,
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

        // RF-13: Alertas de Stock Bajo detalladas
        get stockAlerts() {
            if (this.dashboardData?.stockAlerts) {
                return this.dashboardData.stockAlerts;
            }

            const criticos = [];    // stock <= 0 (Agotado)
            const urgentes = [];    // 0 < stock <= stock_minimo / 2
            const advertencias = []; // stock <= stock_minimo

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
