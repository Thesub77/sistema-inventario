/**
 * ==============================================================================
 * FACTURASTOCK PRO - MÓDULO DE DASHBOARD INTELIGENTE
 * Archivo: resources/js/modules/dashboard.js
 * Descripción: Métricas analíticas, alertas de stock, desglose de turno (RF-26),
 *              gráficos interactivos (RF-31, RF-32) y baja rotación (RF-33).
 * ==============================================================================
 */

export function dashboardModule() {
    return {
        dashboardPeriodo: 'hoy', // 'hoy', '7dias', 'mes', 'todo'
        dashboardVentasView: 'dias', // 'dias', 'semanas'
        bajaRotacionFiltro: 'todos', // 'todos', 'sin_ventas', 'poca_rotacion'
        stockAlertFiltro: 'todos', // 'todos', 'critico', 'urgente', 'advertencia'
        chartVentasInstance: null,
        chartTopInstance: null,

        // Inicializar gráficos del Dashboard cuando se carga la pestaña
        initDashboardCharts() {
            this.$nextTick(() => {
                setTimeout(() => {
                    this.renderDashboardCharts();
                }, 80);
            });
        },

        // Saludo dinámico según la hora del día
        get greetingMessage() {
            const hora = new Date().getHours();
            if (hora < 12) return 'Buenos días';
            if (hora < 18) return 'Buenas tardes';
            return 'Buenas noches';
        },

        // RF-26: Consulta y métricas de ventas del turno (Hoy)
        get ventasTurno() {
            const hoy = new Date().toISOString().slice(0, 10);
            return (this.ventas || []).filter(v => {
                if (!v.fecha_hora_venta) return false;
                return v.fecha_hora_venta.startsWith(hoy);
            });
        },

        get ventasTurnoStats() {
            const list = this.ventasTurno;
            const total = list.reduce((sum, v) => sum + Number(v.total_venta || 0), 0);

            const efectivo = list
                .filter(v => (v.metodo_pago || '').toLowerCase() === 'efectivo')
                .reduce((sum, v) => sum + Number(v.total_venta || 0), 0);

            const transferencia = list
                .filter(v => (v.metodo_pago || '').toLowerCase() === 'transferencia')
                .reduce((sum, v) => sum + Number(v.total_venta || 0), 0);

            const tarjeta = list
                .filter(v => (v.metodo_pago || '').toLowerCase() === 'tarjeta')
                .reduce((sum, v) => sum + Number(v.total_venta || 0), 0);

            const countEfectivo = list.filter(v => (v.metodo_pago || '').toLowerCase() === 'efectivo').length;
            const countTransferencia = list.filter(v => (v.metodo_pago || '').toLowerCase() === 'transferencia').length;
            const countTarjeta = list.filter(v => (v.metodo_pago || '').toLowerCase() === 'tarjeta').length;

            const safeTotal = total > 0 ? total : 1;
            const pctEfectivo = total > 0 ? Math.round((efectivo / safeTotal) * 100) : 0;
            const pctTransferencia = total > 0 ? Math.round((transferencia / safeTotal) * 100) : 0;
            const pctTarjeta = total > 0 ? Math.round((tarjeta / safeTotal) * 100) : 0;

            return {
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

        // RF-32: Top 5 de productos con mayor rotación (más vendidos)
        get topProductosVendidos() {
            const productMap = {};

            // Inicializar mapa de productos
            (this.productos || []).forEach(p => {
                productMap[p.producto_id] = {
                    producto_id: p.producto_id,
                    codigo: p.codigo_producto,
                    nombre: p.nombre_producto,
                    categoria: p.categoria?.nombre_categoria || 'General',
                    precio: Number(p.precio_venta || 0),
                    stock: Number(p.existencia_bodega || 0),
                    cantidadVendida: 0,
                    totalRecaudado: 0
                };
            });

            // Acumular desde ventas y sus detalles
            (this.ventas || []).forEach(v => {
                if (Array.isArray(v.venta_detalles)) {
                    v.venta_detalles.forEach(d => {
                        const pid = d.id_producto;
                        const cant = Number(d.cantidad || 0);
                        const sub = Number(d.subtotal_venta_detalle || (cant * Number(d.precio_unitario || 0)));

                        if (productMap[pid]) {
                            productMap[pid].cantidadVendida += cant;
                            productMap[pid].totalRecaudado += sub;
                        } else {
                            productMap[pid] = {
                                producto_id: pid,
                                codigo: d.producto?.codigo_producto || 'PROD-' + pid,
                                nombre: d.producto?.nombre_producto || 'Producto #' + pid,
                                categoria: 'General',
                                precio: Number(d.precio_unitario || 0),
                                stock: Number(d.producto?.existencia_bodega || 0),
                                cantidadVendida: cant,
                                totalRecaudado: sub
                            };
                        }
                    });
                }
            });

            const sorted = Object.values(productMap)
                .filter(p => p.cantidadVendida > 0)
                .sort((a, b) => b.cantidadVendida - a.cantidadVendida);

            const top5 = sorted.slice(0, 5);
            const maxCantidad = top5.length > 0 ? top5[0].cantidadVendida : 1;

            return top5.map((p, idx) => ({
                ...p,
                posicion: idx + 1,
                porcentajeRelativo: Math.round((p.cantidadVendida / maxCantidad) * 100)
            }));
        },

        // RF-33: Productos con baja o nula rotación
        get productosBajaRotacion() {
            const salesCountMap = {};

            (this.ventas || []).forEach(v => {
                if (Array.isArray(v.venta_detalles)) {
                    v.venta_detalles.forEach(d => {
                        salesCountMap[d.id_producto] = (salesCountMap[d.id_producto] || 0) + Number(d.cantidad || 0);
                    });
                }
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
                    codigo_producto: p.codigo_producto,
                    nombre_producto: p.nombre_producto,
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

            // Ordenar por capital inmovilizado descendente
            return list.sort((a, b) => b.capitalInmovilizado - a.capitalInmovilizado);
        },

        get filteredBajaRotacion() {
            const list = this.productosBajaRotacion;
            if (this.bajaRotacionFiltro === 'sin_ventas') {
                return list.filter(p => p.tipoRotacion === 'sin_ventas');
            }
            if (this.bajaRotacionFiltro === 'poca_rotacion') {
                return list.filter(p => p.tipoRotacion === 'poca_rotacion');
            }
            return list;
        },

        get capitalInmovilizadoTotal() {
            return this.productosBajaRotacion.reduce((sum, p) => sum + p.capitalInmovilizado, 0);
        },

        // RF-31 & RF-32: Renderizado de gráficos con Chart.js
        renderDashboardCharts() {
            if (typeof window.Chart === 'undefined') return;

            const isDark = Boolean(this.darkMode);
            const textColor = isDark ? '#94a3b8' : '#64748b';
            const gridColor = isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.06)';

            // 1. Gráfico de Ventas (RF-31: Indicador de ventas por días o semanas)
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
                    // Últimos 7 días
                    const diasMap = {};
                    for (let i = 6; i >= 0; i--) {
                        const d = new Date();
                        d.setDate(d.getDate() - i);
                        const key = d.toISOString().slice(0, 10);
                        const dayName = d.toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric' });
                        diasMap[key] = { label: dayName, monto: 0, tickets: 0 };
                    }

                    (this.ventas || []).forEach(v => {
                        if (!v.fecha_hora_venta) return;
                        const key = v.fecha_hora_venta.slice(0, 10);
                        if (diasMap[key]) {
                            diasMap[key].monto += Number(v.total_venta || 0);
                            diasMap[key].tickets += 1;
                        }
                    });

                    labels = Object.values(diasMap).map(d => d.label);
                    dataMonto = Object.values(diasMap).map(d => d.monto);
                    dataTickets = Object.values(diasMap).map(d => d.tickets);
                } else {
                    // Últimas 4 Semanas
                    labels = ['Semana 1', 'Semana 2', 'Semana 3', 'Semana 4 (Actual)'];
                    dataMonto = [0, 0, 0, 0];
                    dataTickets = [0, 0, 0, 0];

                    const now = new Date();
                    (this.ventas || []).forEach(v => {
                        if (!v.fecha_hora_venta) return;
                        const vDate = new Date(v.fecha_hora_venta);
                        const diffDays = Math.floor((now - vDate) / (1000 * 60 * 60 * 24));
                        if (diffDays >= 0 && diffDays < 28) {
                            const semIndex = 3 - Math.floor(diffDays / 7);
                            if (semIndex >= 0 && semIndex <= 3) {
                                dataMonto[semIndex] += Number(v.total_venta || 0);
                                dataTickets[semIndex] += 1;
                            }
                        }
                    });
                }

                const ctx = canvasVentas.getContext('2d');
                const gradient = ctx.createLinearGradient(0, 0, 0, 240);
                gradient.addColorStop(0, 'rgba(99, 102, 241, 0.85)');
                gradient.addColorStop(1, 'rgba(129, 140, 248, 0.25)');

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
                                maxBarThickness: 36,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 600 },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: isDark ? '#1e293b' : '#ffffff',
                                titleColor: isDark ? '#ffffff' : '#0f172a',
                                bodyColor: isDark ? '#94a3b8' : '#334155',
                                borderColor: isDark ? '#334155' : '#e2e8f0',
                                borderWidth: 1,
                                padding: 10,
                                displayColors: false,
                                callbacks: {
                                    label: (ctx) => `Ventas: ${this.formatCurrency(ctx.parsed.y)}`
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
                const labels = topData.length > 0 ? topData.map(p => p.nombre.slice(0, 16)) : ['Sin datos de ventas'];
                const data = topData.length > 0 ? topData.map(p => p.cantidadVendida) : [1];
                const colors = topData.length > 0
                    ? ['#6366f1', '#10b981', '#f59e0b', '#0ea5e9', '#8b5cf6']
                    : [isDark ? '#334155' : '#cbd5e1'];

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
                        animation: { duration: 600 },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    color: textColor,
                                    font: { size: 11, family: 'Plus Jakarta Sans' },
                                    boxWidth: 12,
                                    padding: 10
                                }
                            },
                            tooltip: {
                                backgroundColor: isDark ? '#1e293b' : '#ffffff',
                                titleColor: isDark ? '#ffffff' : '#0f172a',
                                bodyColor: isDark ? '#94a3b8' : '#334155',
                                borderColor: isDark ? '#334155' : '#e2e8f0',
                                borderWidth: 1,
                                padding: 10,
                                callbacks: {
                                    label: (ctx) => topData.length > 0 ? ` ${ctx.label}: ${ctx.parsed} uds. vendidas` : ' Sin ventas registradas'
                                }
                            }
                        }
                    }
                });
            }
        }
    };
}
