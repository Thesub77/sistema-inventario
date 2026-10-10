/**
 * ==============================================================================
 * FACTURASTOCK PRO - MÓDULO DE DASHBOARD INTELIGENTE
 * Archivo: resources/js/modules/dashboard.js
 * Descripción: Métricas analíticas, alertas de stock, desglose de turno,
 *              gráficos interactivos y baja rotación.
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
        topProductosFiltro: 'unidades', // 'unidades', 'utilidad'
        techoFiscalData: null,
        loadingTechoFiscal: false,

        // Carga optimizada de métricas calculadas en el servidor
        async fetchDashboardData() {
            try {
                // Obtener fecha local en formato YYYY-MM-DD (evita desfasaje UTC)
                const localDate = new Date().toLocaleDateString('en-CA');
                await Promise.allSettled([
                    (async () => {
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
                    })(),
                    this.fetchTechoFiscal(localDate)
                ]);
            } catch (error) {
                console.error('Error cargando métricas optimizadas del Dashboard:', error);
            }
        },

        // Carga del cálculo de techo fiscal y semáforo preventivo (RF-28)
        async fetchTechoFiscal(fecha = null) {
            this.loadingTechoFiscal = true;
            try {
                const localDate = fecha || new Date().toLocaleDateString('en-CA');
                const res = await this.apiFetch(`/api/dashboard/techo-fiscal?fecha=${localDate}`);
                if (res.ok) {
                    const data = await res.json();
                    this.techoFiscalData = data;
                } else {
                    console.warn('Dashboard techo-fiscal API respondió con estado:', res.status);
                }
            } catch (error) {
                console.error('Error cargando semáforo de techo fiscal:', error);
            } finally {
                this.loadingTechoFiscal = false;
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
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

        // Consulta y métricas de ventas del turno en tiempo real (Aislamiento por turno actual)
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

        // Alertas de Stock Bajo detalladas
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

        // Top 5 de productos con mayor rotación en tiempo real (más vendidos)
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

        // Top 5 Productos por Mayor Utilidad / Ganancia Bruta (RF-29)
        get topRentabilidad() {
            const backendRent = Array.isArray(this.dashboardData?.topRentabilidad) ? this.dashboardData.topRentabilidad : [];

            if (!Array.isArray(this.ventas) || this.ventas.length === 0) {
                return backendRent;
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
                    costo: Number(p.costo_compra || 0),
                    stock: Number(p.existencia_bodega || 0),
                    cantidadVendida: 0,
                    totalRecaudado: 0,
                    utilidadTotal: 0
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
                        const costoUnit = productMap[pid].costo;
                        productMap[pid].utilidadTotal += (sub - (costoUnit * cant));
                    } else if (pid) {
                        const costoUnit = Number(d.producto?.costo_compra || 0);
                        productMap[pid] = {
                            producto_id: pid,
                            codigo: d.producto?.codigo_producto || ('PROD-' + pid),
                            nombre: d.producto?.nombre_producto || ('Producto #' + pid),
                            categoria: d.producto?.categoria?.nombre_categoria || 'General',
                            precio: Number(d.precio_unitario || 0),
                            costo: costoUnit,
                            stock: Number(d.producto?.existencia_bodega || 0),
                            cantidadVendida: cant,
                            totalRecaudado: sub,
                            utilidadTotal: sub - (costoUnit * cant)
                        };
                    }
                });
            });

            const sorted = Object.values(productMap)
                .filter(p => p.utilidadTotal > 0 || p.cantidadVendida > 0)
                .sort((a, b) => b.utilidadTotal - a.utilidadTotal);

            if (sorted.length > 0) {
                const top5 = sorted.slice(0, 5);
                const maxUtilidad = top5[0].utilidadTotal > 0 ? top5[0].utilidadTotal : 1;
                return top5.map((p, idx) => {
                    const utilidad = Number(p.utilidadTotal.toFixed(2));
                    const recaudado = Number(p.totalRecaudado.toFixed(2));
                    const margenPct = recaudado > 0 ? Number(((utilidad / recaudado) * 100).toFixed(1)) : 0;
                    return {
                        ...p,
                        posicion: idx + 1,
                        utilidadTotal: utilidad,
                        totalRecaudado: recaudado,
                        margenPct,
                        porcentajeRelativo: Math.round((Math.max(0, utilidad) / maxUtilidad) * 100)
                    };
                });
            }

            return backendRent;
        },

        // Productos con baja o nula rotación
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

        // Pérdidas por Mermas del Mes (RF-48)
        get perdidasMermasMes() {
            let sumMemoria = 0;
            let countMermas = 0;

            if (Array.isArray(this.movimientosInventario) && this.movimientosInventario.length > 0) {
                const currentYearMonth = new Date().toLocaleDateString('en-CA').slice(0, 7);
                this.movimientosInventario.forEach(m => {
                    if (Number(m.estado) === 0 || m.tipo_movimiento !== 'Salida por Merma') return;
                    const mDate = String(m.fecha_movimiento || m.created_at || '').slice(0, 7);
                    if (mDate === currentYearMonth) {
                        countMermas++;
                        sumMemoria += Number(m.costo_total_perdida || 0);
                    }
                });
            }

            const backendVal = (this.dashboardData?.stats?.totalPerdidasMermasMes !== undefined)
                ? Number(this.dashboardData.stats.totalPerdidasMermasMes || 0)
                : ((this.dashboardData?.totalPerdidasMermasMes !== undefined)
                    ? Number(this.dashboardData.totalPerdidasMermasMes || 0)
                    : 0);

            if (countMermas > 0) {
                return Number(sumMemoria.toFixed(2));
            }

            return backendVal;
        },

        // Listado de mermas registradas durante el mes actual (RF-48)
        get mermasDelMes() {
            if (!Array.isArray(this.movimientosInventario) || this.movimientosInventario.length === 0) {
                return [];
            }
            const currentYearMonth = new Date().toLocaleDateString('en-CA').slice(0, 7);
            const filtered = this.movimientosInventario.filter(m => {
                if (Number(m.estado) === 0 || m.tipo_movimiento !== 'Salida por Merma') return false;
                const mDate = String(m.fecha_movimiento || m.created_at || '').slice(0, 7);
                return mDate === currentYearMonth;
            });
            return filtered.slice().reverse();
        },

        // Últimas 10 ventas emitidas en tiempo real
        get ultimasVentas() {
            const raw = (Array.isArray(this.ventas) && this.ventas.length > 0)
                ? this.ventas
                : (Array.isArray(this.dashboardData?.ultimasVentas) ? this.dashboardData.ultimasVentas : []);

            return raw.slice(0, 10).map(v => {
                const cliente = v.cliente_nombre || 'Consumidor Final';

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

        // Utilidad Bruta del Período en tiempo real (RF-29)
        get utilidadBrutaPeriodo() {
            const backendVal = (this.dashboardData?.stats?.utilidadBrutaMes !== undefined)
                ? Number(this.dashboardData.stats.utilidadBrutaMes)
                : ((this.dashboardData?.utilidadBrutaMes !== undefined)
                    ? Number(this.dashboardData.utilidadBrutaMes)
                    : ((this.dashboardData?.stats?.utilidad_bruta_mes !== undefined)
                        ? Number(this.dashboardData.stats.utilidad_bruta_mes)
                        : null));

            if (backendVal !== null && (!Array.isArray(this.ventas) || this.ventas.length === 0)) {
                return Number(backendVal.toFixed(2));
            }

            // Cálculo en tiempo real sobre ventas locales en memoria (mes actual)
            const currentMonth = new Date().toLocaleDateString('en-CA').slice(0, 7);
            let sumUtilidad = 0;
            let hasSalesThisMonth = false;

            (this.ventas || []).forEach(v => {
                if (Number(v.estado) === 0 || !v.fecha_hora_venta) return;
                if (v.fecha_hora_venta.slice(0, 7) !== currentMonth) return;
                hasSalesThisMonth = true;
                (v.venta_detalles || []).forEach(d => {
                    const cant = Number(d.cantidad || 0);
                    const sub = Number(d.subtotal_venta_detalle || (cant * Number(d.precio_unitario || 0)));
                    const costoUnit = Number(d.producto?.costo_compra || 0);
                    sumUtilidad += (sub - (costoUnit * cant));
                });
            });

            if (hasSalesThisMonth) {
                return Number(sumUtilidad.toFixed(2));
            }

            return backendVal !== null ? Number(backendVal.toFixed(2)) : 0;
        },

        // Margen porcentual de utilidad bruta sobre ventas
        get margenUtilidadBruta() {
            const totalVentas = Number(this.dashboardData?.stats?.totalVentasMonto || (this.ventas || []).filter(v => Number(v.estado) === 1).reduce((s, v) => s + Number(v.total_venta || 0), 0));
            const utilidad = this.utilidadBrutaPeriodo;
            if (totalVentas <= 0 || utilidad <= 0) return 0;
            return Number(((utilidad / totalVentas) * 100).toFixed(1));
        },

        // Flujo y distribución de demanda por franja horaria (RF-30)
        get distribucionHoraria() {
            const backendHoras = this.dashboardData?.distribucionHoraria || this.dashboardData?.demandaHoraria;

            const horasMap = {};
            for (let h = 0; h < 24; h++) {
                const label = String(h).padStart(2, '0') + ':00';
                horasMap[h] = { hora: h, label, monto: 0, tickets: 0 };
            }

            const hoy = new Date().toLocaleDateString('en-CA');
            let hasTodaySales = false;

            (this.ventas || []).forEach(v => {
                if (Number(v.estado) === 0 || !v.fecha_hora_venta) return;
                if (v.fecha_hora_venta.slice(0, 10) === hoy) {
                    const hora = new Date(v.fecha_hora_venta.replace(' ', 'T')).getHours();
                    if (horasMap[hora] !== undefined) {
                        horasMap[hora].monto += Number(v.total_venta || 0);
                        horasMap[hora].tickets += 1;
                        hasTodaySales = true;
                    }
                }
            });

            if (hasTodaySales) {
                const list = Object.values(horasMap);
                return {
                    labels: list.map(item => item.label),
                    dataMonto: list.map(item => Number(item.monto.toFixed(2))),
                    dataTickets: list.map(item => item.tickets),
                    horas: list
                };
            }

            if (backendHoras && Array.isArray(backendHoras.labels)) {
                return backendHoras;
            }

            const list = Object.values(horasMap);
            return {
                labels: list.map(item => item.label),
                dataMonto: list.map(item => item.monto),
                dataTickets: list.map(item => item.tickets),
                horas: list
            };
        },

        // Detección automática de la hora pico de mayor venta
        get horaPicoInfo() {
            const dist = this.distribucionHoraria;
            const dataMonto = dist.dataMonto || [];
            const labels = dist.labels || [];
            const dataTickets = dist.dataTickets || [];

            let maxMonto = 0;
            let maxIdx = -1;

            dataMonto.forEach((m, idx) => {
                if (m > maxMonto) {
                    maxMonto = m;
                    maxIdx = idx;
                }
            });

            if (maxIdx >= 0 && maxMonto > 0) {
                const rawLabel = labels[maxIdx] || '12:00';
                const h = parseInt(rawLabel.slice(0, 2), 10);
                const ampm = h >= 12 ? 'PM' : 'AM';
                const h12 = h % 12 || 12;
                return {
                    hora: h,
                    rawLabel,
                    label: `${h12}:00 ${ampm}`,
                    monto: maxMonto,
                    tickets: dataTickets[maxIdx] || 0
                };
            }

            return {
                hora: 12,
                rawLabel: '12:00',
                label: '12:00 PM',
                monto: 0,
                tickets: 0
            };
        },

        // Cantidad de franjas horarias con ventas activas
        get horasConVentasCount() {
            const dataMonto = this.distribucionHoraria.dataMonto || [];
            return dataMonto.filter(m => m > 0).length;
        },

        // Cambiar filtro de top productos y refrescar gráfico
        setTopProductosFiltro(tipo) {
            this.topProductosFiltro = tipo;
            this.$nextTick(() => {
                this.renderTopChart();
            });
        },

        // Renderizado del Gráfico de Ventas (Últimos 7 Días / Semanas)
        renderVentasChart() {
            if (typeof window.Chart === 'undefined') return;
            const canvasVentas = document.getElementById('chartVentas');
            if (!canvasVentas) return;

            if (this.chartVentasInstance) {
                this.chartVentasInstance.destroy();
                this.chartVentasInstance = null;
            }

            const isDark = Boolean(this.darkMode);
            const textColor = isDark ? '#94a3b8' : '#64748b';
            const gridColor = isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.06)';

            let labels = [];
            let dataMonto = [];
            let dataTickets = [];

            if (this.dashboardVentasView === 'dias') {
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
        },

        // Renderizado del Gráfico Doughnut de Top Productos (Rotación vs Rentabilidad)
        renderTopChart() {
            if (typeof window.Chart === 'undefined') return;
            const canvasTop = document.getElementById('chartTopProductos');
            if (!canvasTop) return;

            if (this.chartTopInstance) {
                this.chartTopInstance.destroy();
                this.chartTopInstance = null;
            }

            const isDark = Boolean(this.darkMode);
            const textColor = isDark ? '#94a3b8' : '#64748b';
            const esUtilidad = this.topProductosFiltro === 'utilidad';

            let labels = [];
            let data = [];
            let colors = [];
            let hasData = false;

            if (esUtilidad) {
                const rentData = this.topRentabilidad || [];
                hasData = rentData.length > 0 && rentData.some(p => (p.utilidadTotal || p.utilidad_total || 0) > 0);
                labels = hasData
                    ? rentData.map(p => (p.nombre || p.nombre_producto || '').slice(0, 18))
                    : ['Sin datos de utilidad'];
                data = hasData
                    ? rentData.map(p => Math.max(0, Number(p.utilidadTotal || p.utilidad_total || 0)))
                    : [1];
                colors = hasData
                    ? ['#10b981', '#059669', '#14b8a6', '#0d9488', '#06b6d4']
                    : [isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.08)'];
            } else {
                const volData = this.topProductosVendidos || [];
                hasData = volData.length > 0 && volData.some(p => p.cantidadVendida > 0);
                labels = hasData
                    ? volData.map(p => (p.nombre || p.nombre_producto || '').slice(0, 18))
                    : ['Sin datos de ventas'];
                data = hasData
                    ? volData.map(p => p.cantidadVendida)
                    : [1];
                colors = hasData
                    ? ['#6366f1', '#10b981', '#f59e0b', '#06b6d4', '#ec4899']
                    : [isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.08)'];
            }

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
                                label: (ctx) => {
                                    if (esUtilidad) {
                                        const rentList = this.topRentabilidad || [];
                                        const item = rentList[ctx.dataIndex];
                                        const margen = item ? (item.margenPct || item.margen_pct || 0) : 0;
                                        return [
                                            ` ${ctx.label}`,
                                            ` Ganancia: ${this.formatCurrency(ctx.parsed)}`,
                                            ` Margen: ${margen}%`
                                        ];
                                    }
                                    return ` ${ctx.label}: ${ctx.parsed} uds. vendidas`;
                                }
                            }
                        }
                    }
                }
            });
        },

        // Renderizado general de todos los gráficos del Dashboard
        renderDashboardCharts() {
            this.renderVentasChart();
            this.renderTopChart();
        },

        // Métrica analítica reactiva del techo fiscal de Cuota Fija (Ley 822) y semáforo preventivo (RF-28)
        get techoFiscal() {
            const raw = this.techoFiscalData;
            const defaultTecho = Number(this.empresa?.techo_mensual_cuota_fija !== undefined ? this.empresa.techo_mensual_cuota_fija : 100000.00);
            const regimen = this.empresa?.regimen_tributario || raw?.regimen || 'Cuota Fija';

            if (raw) {
                const techoMensual = Number(raw.techo_mensual !== undefined ? raw.techo_mensual : defaultTecho);
                const ventasMes = Number(raw.ventas_mes || 0);
                const porcentaje = techoMensual > 0 ? (ventasMes / techoMensual) * 100 : (ventasMes > 0 ? 100 : 0);
                const saldoDisponible = Math.max(0, techoMensual - ventasMes);
                const estadoSemaforo = raw.estado_semaforo || (porcentaje < 75 ? 'normal' : (porcentaje < 100 ? 'alerta' : 'excedido'));

                return {
                    regimen: raw.regimen || regimen,
                    ventas_mes: ventasMes,
                    techo_mensual: techoMensual,
                    porcentaje_consumido: Number(porcentaje.toFixed(2)),
                    saldo_disponible: Number(saldoDisponible.toFixed(2)),
                    estado_semaforo: estadoSemaforo,
                    ventas_anual_acumulado: Number(raw.ventas_anual_acumulado || 0),
                    techo_anual: Number(raw.techo_anual || (techoMensual * 12))
                };
            }

            // Fallback reactivo local basado en this.ventas
            const now = new Date();
            const curYear = now.getFullYear();
            const curMonth = now.getMonth();
            const validVentas = (this.ventas || []).filter(v => Number(v.estado) === 1);
            
            const ventasMes = validVentas
                .filter(v => {
                    if (!v.fecha_hora_venta) return false;
                    const d = new Date(v.fecha_hora_venta.replace(' ', 'T'));
                    return d.getFullYear() === curYear && d.getMonth() === curMonth;
                })
                .reduce((sum, v) => sum + Number(v.total_venta || 0), 0);

            const ventasAnual = validVentas
                .filter(v => {
                    if (!v.fecha_hora_venta) return false;
                    const d = new Date(v.fecha_hora_venta.replace(' ', 'T'));
                    return d.getFullYear() === curYear;
                })
                .reduce((sum, v) => sum + Number(v.total_venta || 0), 0);

            const techoMensual = defaultTecho;
            const porcentaje = techoMensual > 0 ? (ventasMes / techoMensual) * 100 : (ventasMes > 0 ? 100 : 0);
            const saldo = Math.max(0, techoMensual - ventasMes);
            const estado = porcentaje < 75 ? 'normal' : (porcentaje < 100 ? 'alerta' : 'excedido');

            return {
                regimen: regimen,
                ventas_mes: Number(ventasMes.toFixed(2)),
                techo_mensual: techoMensual,
                porcentaje_consumido: Number(porcentaje.toFixed(2)),
                saldo_disponible: Number(saldo.toFixed(2)),
                estado_semaforo: estado,
                ventas_anual_acumulado: Number(ventasAnual.toFixed(2)),
                techo_anual: Number((techoMensual * 12).toFixed(2))
            };
        }
    };
}
