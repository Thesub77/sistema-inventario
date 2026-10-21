/**
 * ==============================================================================
 * FACTURASTOCK PRO - MÓDULO FISCAL & CONTROL FINANCIERO (LEY 822 CUOTA FIJA)
 * Archivo: resources/js/modules/fiscal.js
 * Propósito: Administra el Cuaderno Fiscal mensual intuitivo para MiPyMEs de Nicaragua:
 *            1. Ingresos por facturación mensual (única fuente de ingresos del mes).
 *            2. Gastos y egresos consumidos dinámicamente de Cuentas por Pagar.
 *            3. Declaración de Impuesto DGI Cuota Fija (Monto reglamentario C$ 700.00).
 *            4. Liquidación simple de Rentabilidad Neta y rumbo para toma de decisiones.
 * ==============================================================================
 */

export function fiscalModule() {
    const now = new Date();

    // Helper para extraer { year, month } seguro contra variaciones de formato y zonas horarias
    function extractYearMonth(dateVal) {
        if (!dateVal) return null;
        let s = String(dateVal).trim();

        // 1. Si es solo fecha YYYY-MM-DD sin componente de hora, año y mes exactos de calendario
        const dateOnlyMatch = s.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (dateOnlyMatch) {
            return { year: parseInt(dateOnlyMatch[1], 10), month: parseInt(dateOnlyMatch[2], 10) };
        }

        // 2. Si viene formateada como DD/MM/YYYY
        const latinMatch = s.match(/^(\d{2})\/(\d{2})\/(\d{4})/);
        if (latinMatch) {
            return { year: parseInt(latinMatch[3], 10), month: parseInt(latinMatch[2], 10) };
        }

        // 3. Si viene con fecha y hora (ej: "2026-10-01 01:30:00" o ISO), normalizar con 'Z'
        // para interpretar en la zona horaria local del navegador, 100% coherente con formatDate().
        if (/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?(\.\d+)?$/.test(s)) {
            s = s.replace(' ', 'T') + 'Z';
        }
        const d = new Date(s);
        const target = isNaN(d.getTime()) ? new Date(dateVal) : d;
        if (!isNaN(target.getTime())) {
            return { year: target.getFullYear(), month: target.getMonth() + 1 };
        }

        const match = s.match(/^(\d{4})-(\d{2})/);
        if (match) {
            return { year: parseInt(match[1], 10), month: parseInt(match[2], 10) };
        }
        return null;
    }

    // Helper para obtener YYYY-MM-DD local exacto para agrupar ventas diarias
    function getLocalDateStr(dateVal) {
        if (!dateVal) return '';
        let s = String(dateVal).trim();
        const dateOnlyMatch = s.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (dateOnlyMatch) {
            return s;
        }
        if (/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?(\.\d+)?$/.test(s)) {
            s = s.replace(' ', 'T') + 'Z';
        }
        const d = new Date(s);
        const target = isNaN(d.getTime()) ? new Date(dateVal) : d;
        if (isNaN(target.getTime())) {
            return s.split('T')[0].split(' ')[0];
        }
        const pad = n => String(n).padStart(2, '0');
        return `${target.getFullYear()}-${pad(target.getMonth() + 1)}-${pad(target.getDate())}`;
    }

    return {
        // Estado del Libro Diario Fiscal
        libroDiarioMes: now.getMonth() + 1,
        libroDiarioAnio: now.getFullYear(),
        libroDiarioLoading: false,
        libroDiarioEmisionFecha: '',
        showLibroComprobantesDetalle: false,
        searchLibroComprobante: '',
        incluirAnuladasLibro: false,
        libroDiarioData: {
            success: true,
            anio: now.getFullYear(),
            mes: now.getMonth() + 1,
            total_acumulado_mes: '0.00',
            total_transacciones_mes: 0,
        },

        getDayNumber(dateStr) {
            if (!dateStr) return '';
            const match = String(dateStr).trim().match(/^(\d{4})-(\d{2})-(\d{2})/);
            if (match) return match[3];
            const d = new Date(dateStr);
            return isNaN(d.getTime()) ? '' : String(d.getDate()).padStart(2, '0');
        },

        getDayName(dateStr) {
            if (!dateStr) return '';
            const match = String(dateStr).trim().match(/^(\d{4})-(\d{2})-(\d{2})/);
            if (match) {
                const d = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]), 12, 0, 0);
                const name = d.toLocaleDateString('es-NI', { weekday: 'short' }).replace('.', '');
                return name.charAt(0).toUpperCase() + name.slice(1);
            }
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return '';
            const name = d.toLocaleDateString('es-NI', { weekday: 'short' }).replace('.', '');
            return name.charAt(0).toUpperCase() + name.slice(1);
        },

        getFullDayName(dateStr) {
            if (!dateStr) return '';
            const match = String(dateStr).trim().match(/^(\d{4})-(\d{2})-(\d{2})/);
            if (match) {
                const d = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]), 12, 0, 0);
                const name = d.toLocaleDateString('es-NI', { weekday: 'long' });
                return name.charAt(0).toUpperCase() + name.slice(1);
            }
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return '';
            const name = d.toLocaleDateString('es-NI', { weekday: 'long' });
            return name.charAt(0).toUpperCase() + name.slice(1);
        },

        // Lista de meses para el selector interactivo
        mesesList: [
            { id: 1, label: 'Enero' },
            { id: 2, label: 'Febrero' },
            { id: 3, label: 'Marzo' },
            { id: 4, label: 'Abril' },
            { id: 5, label: 'Mayo' },
            { id: 6, label: 'Junio' },
            { id: 7, label: 'Julio' },
            { id: 8, label: 'Agosto' },
            { id: 9, label: 'Septiembre' },
            { id: 10, label: 'Octubre' },
            { id: 11, label: 'Noviembre' },
            { id: 12, label: 'Diciembre' }
        ],

        // Lista de años disponibles para auditoría fiscal
        get aniosList() {
            const currentYear = new Date().getFullYear();
            const years = [];
            for (let y = currentYear - 2; y <= currentYear + 1; y++) {
                years.push(y);
            }
            return years;
        },

        get nombreMesSeleccionado() {
            const m = this.mesesList.find(item => Number(item.id) === Number(this.libroDiarioMes));
            return m ? m.label : 'Mes ' + this.libroDiarioMes;
        },

        // Facturas individuales de venta pertenecientes al mes y año seleccionado
        get libroDiarioVentasDetalle() {
            const mesSel = Number(this.libroDiarioMes);
            const anioSel = Number(this.libroDiarioAnio);
            const term = (this.searchLibroComprobante || '').toLowerCase().trim();

            return (this.ventas || [])
                .filter(v => {
                    if (!this.incluirAnuladasLibro && Number(v.estado) === 0) return false;
                    if (!v.fecha_hora_venta) return false;
                    const ym = extractYearMonth(v.fecha_hora_venta);
                    if (!ym || ym.year !== anioSel || ym.month !== mesSel) return false;

                    if (term) {
                        const codeMatch = v.codigo_venta && v.codigo_venta.toLowerCase().includes(term);
                        const clientMatch = (v.cliente_nombre && v.cliente_nombre.toLowerCase().includes(term)) ||
                            (v.cliente && v.cliente.nombre_apellido_cliente && v.cliente.nombre_apellido_cliente.toLowerCase().includes(term));
                        const refMatch = v.referencia_transferencia && v.referencia_transferencia.toLowerCase().includes(term);
                        const userMatch = v.usuario && v.usuario.nombre_apellido && v.usuario.nombre_apellido.toLowerCase().includes(term);
                        if (!codeMatch && !clientMatch && !refMatch && !userMatch) return false;
                    }

                    return true;
                })
                .sort((a, b) => new Date(String(b.fecha_hora_venta).replace(' ', 'T')) - new Date(String(a.fecha_hora_venta).replace(' ', 'T')));
        },

        // Clasificador financiero de Deudas con Proveedores: Gastos Operativos vs. Inversión en Inventario
        isGastoOperativo(c) {
            if (!c) return false;
            const txt = `${c.descripcion || ''} ${c.proveedor?.nombre_comercial || ''} ${c.proveedor?.razon_social || ''} ${c.numero_factura || ''}`.toLowerCase();
            const operationalKeywords = [
                'luz', 'electricidad', 'energia', 'disnorte', 'dissur',
                'agua', 'enacal', 'potable',
                'internet', 'claro', 'tigo', 'telecom', 'telefono', 'wifi',
                'alquiler', 'renta', 'local', 'arriendo',
                'dgi', 'impuesto', 'cuota fija', 'tributo', 'alcaldia', 'matricula',
                'inss', 'seguro', 'seguridad social', 'facultativo',
                'salario', 'sueldo', 'nomina', 'planilla', 'personal', 'empleado',
                'empaque', 'bolsas', 'bolsa', 'limpieza', 'suministro', 'papeleria',
                'mantenimiento', 'reparacion', 'transporte', 'flete', 'gasolina', 'combustible',
                'publicidad', 'marketing', 'seguridad', 'vigilancia'
            ];
            return operationalKeywords.some(kw => txt.includes(kw));
        },

        // Deudas con Proveedores pertenecientes estrictamente al mes y año seleccionado
        get libroFiscalCuentasMes() {
            const mesSel = Number(this.libroDiarioMes);
            const anioSel = Number(this.libroDiarioAnio);
            const list = Array.isArray(this.cuentasPorPagar) ? this.cuentasPorPagar : [];

            return list.filter(c => {
                if (c.estado && String(c.estado).toLowerCase() === 'anulada') return false;

                // Coincidencia estricta por fecha de emisión o creación dentro del mes
                const rawDate = c.fecha_emision || c.created_at;
                const ym = extractYearMonth(rawDate);
                if (ym && ym.year === anioSel && ym.month === mesSel) return true;

                // O coincidencia si registra abonos o pagos en este mes específico
                if (Array.isArray(c.pagos) && c.pagos.length > 0) {
                    const hasPago = c.pagos.some(p => {
                        const pYm = extractYearMonth(p.fecha_pago || p.created_at);
                        return pYm && pYm.year === anioSel && pYm.month === mesSel;
                    });
                    if (hasPago) return true;
                }

                return false;
            });
        },

        // 1. GASTOS OPERATIVOS: Deudas operativas (servicios, alquiler, DGI, empaques, personal)
        get libroFiscalGastosOperativosCuentas() {
            return this.libroFiscalCuentasMes.filter(c => this.isGastoOperativo(c));
        },
        get libroFiscalTotalGastosOperativosCxP() {
            return Number(this.libroFiscalGastosOperativosCuentas.reduce((sum, c) => sum + Number(c.monto_total || 0), 0).toFixed(2));
        },

        // Gastos Diarios en Efectivo (Salidas operativas de Caja Chica en el mes)
        get libroFiscalCajaEgresosMes() {
            const mesSel = Number(this.libroDiarioMes);
            const anioSel = Number(this.libroDiarioAnio);
            const movs = Array.isArray(this.cajaMovimientos) ? this.cajaMovimientos : [];

            // Conjunto de IDs de movimientos de caja generados por abonos/pagos a facturas de proveedores
            const movimientosPagosCxP = new Set();
            (this.cuentasPorPagar || []).forEach(c => {
                (c.pagos || []).forEach(p => {
                    if (p.id_caja_movimiento_venta) {
                        movimientosPagosCxP.add(Number(p.id_caja_movimiento_venta));
                    }
                });
            });

            return movs.filter(m => {
                if (Number(m.monto_movimiento || 0) >= 0) return false;
                if (m.id_venta) return false;

                const mId = Number(m.caja_movimiento_venta_id);

                // Excluir si el egreso corresponde a un pago de cuenta por pagar de proveedor
                if (movimientosPagosCxP.has(mId)) return false;

                // Excluir si la bitácora lo registra formalmente como pago a proveedor
                if (Array.isArray(this.bitacoras) && this.bitacoras.length > 0) {
                    const esPagoProv = this.bitacoras.some(bit => {
                        if (bit.accion_bitacora === 'CAJA_PAGO_PROVEEDOR') {
                            const desc = bit.descripcion_bitacora || '';
                            const match = desc.match(/(?:Egreso de caja|Mov\.?)\s*#(\d+)/i);
                            if (match && Number(match[1]) === mId) return true;
                            if (desc.includes(`#${mId}`)) return true;
                        }
                        return false;
                    });
                    if (esPagoProv) return false;
                }

                const ym = extractYearMonth(m.fecha_hora_movimiento || m.created_at);
                return ym && ym.year === anioSel && ym.month === mesSel;
            });
        },
        get libroFiscalTotalCajaEgresos() {
            return Number(this.libroFiscalCajaEgresosMes.reduce((sum, m) => sum + Math.abs(Number(m.monto_movimiento || 0)), 0).toFixed(2));
        },

        // Total Consolidado de Gastos Operativos (CxP Operativa + Gastos Diarios en Efectivo)
        get libroFiscalTotalGastosOperativos() {
            return Number((this.libroFiscalTotalGastosOperativosCxP + this.libroFiscalTotalCajaEgresos).toFixed(2));
        },

        // 2. INVERSIÓN EN INVENTARIO: Compras de Stock a Proveedores (Mercadería para reventa)
        get libroFiscalInventarioCuentas() {
            return this.libroFiscalCuentasMes.filter(c => !this.isGastoOperativo(c));
        },
        get libroFiscalTotalInventario() {
            return Number(this.libroFiscalInventarioCuentas.reduce((sum, c) => sum + Number(c.monto_total || 0), 0).toFixed(2));
        },
        get libroFiscalInventarioPagado() {
            return Number(this.libroFiscalInventarioCuentas.reduce((sum, c) => sum + Number(c.monto_pagado || 0), 0).toFixed(2));
        },
        get libroFiscalInventarioPendiente() {
            return Number(this.libroFiscalInventarioCuentas.reduce((sum, c) => {
                const pend = (c.saldo_pendiente !== undefined && c.saldo_pendiente !== null)
                    ? Number(c.saldo_pendiente)
                    : (Number(c.monto_total || 0) - Number(c.monto_pagado || 0));
                return sum + Math.max(0, pend);
            }, 0).toFixed(2));
        },

        // 3. MÓDULO DE MERMAS (Pérdidas reales por productos dañados, vencidos o deteriorados)
        get libroFiscalMermasMes() {
            const mesSel = Number(this.libroDiarioMes);
            const anioSel = Number(this.libroDiarioAnio);
            const movs = Array.isArray(this.movimientosInventario) ? this.movimientosInventario : [];

            return movs.filter(m => {
                if (Number(m.estado) === 0) return false;
                if (m.tipo_movimiento !== 'Salida por Merma') return false;
                const ym = extractYearMonth(m.fecha_movimiento || m.created_at);
                return ym && ym.year === anioSel && ym.month === mesSel;
            });
        },
        get libroFiscalTotalMermas() {
            return Number(this.libroFiscalMermasMes.reduce((sum, m) => {
                const cant = Number(m.cantidad_movimimiento || m.cantidad_movimiento || m.cantidad || 0);
                const costoU = Number(m.costo_unitario || m.producto?.costo_compra || 0);
                const total = Number(m.costo_total_perdida) || (cant * costoU);
                return sum + total;
            }, 0).toFixed(2));
        },

        // Total Ingresos Facturados del Mes (100% reactivo al mes y año seleccionado)
        get libroFiscalTotalIngresos() {
            const mesSel = Number(this.libroDiarioMes);
            const anioSel = Number(this.libroDiarioAnio);

            // 1. Si this.ventas está en memoria, calcular inmediatamente sobre las ventas activas del mes
            if (Array.isArray(this.ventas) && this.ventas.length > 0) {
                const total = this.ventas
                    .filter(v => {
                        if (Number(v.estado) === 0) return false;
                        if (!v.fecha_hora_venta) return false;
                        const ym = extractYearMonth(v.fecha_hora_venta);
                        return ym && ym.year === anioSel && ym.month === mesSel;
                    })
                    .reduce((sum, v) => sum + Number(v.total_venta || 0), 0);
                return Number(total.toFixed(2));
            }

            // 2. Fallback a libroDiarioData solo si coincide exactamente con el mes y año consultado
            if (this.libroDiarioData &&
                Number(this.libroDiarioData.mes) === mesSel &&
                Number(this.libroDiarioData.anio) === anioSel) {
                return Number(this.libroDiarioData.total_acumulado_mes || 0);
            }

            return 0;
        },

        // Cantidad de facturas/tickets del mes seleccionado
        get libroFiscalTotalTransacciones() {
            const mesSel = Number(this.libroDiarioMes);
            const anioSel = Number(this.libroDiarioAnio);

            if (Array.isArray(this.ventas) && this.ventas.length > 0) {
                return this.ventas.filter(v => {
                    if (Number(v.estado) === 0) return false;
                    if (!v.fecha_hora_venta) return false;
                    const ym = extractYearMonth(v.fecha_hora_venta);
                    return ym && ym.year === anioSel && ym.month === mesSel;
                }).length;
            }

            if (this.libroDiarioData &&
                Number(this.libroDiarioData.mes) === mesSel &&
                Number(this.libroDiarioData.anio) === anioSel) {
                return Number(this.libroDiarioData.total_transacciones_mes || 0);
            }

            return 0;
        },

        // Límite fiscal DGI y semáforo reactivo específico para el mes y año consultado en el Balance
        get libroFiscalTecho() {
            const defaultTecho = Number(this.empresa?.techo_mensual_cuota_fija !== undefined ? this.empresa.techo_mensual_cuota_fija : 100000.00);
            const regimen = this.empresa?.regimen_tributario || 'Cuota Fija';
            const anioSel = Number(this.libroDiarioAnio);

            // Ventas del mes seleccionado (exacto, reactivo)
            const ventasMes = Number(this.libroFiscalTotalIngresos || 0);
            const techoMensual = defaultTecho;
            const porcentaje = techoMensual > 0 ? (ventasMes / techoMensual) * 100 : (ventasMes > 0 ? 100 : 0);
            const saldoDisponible = Math.max(0, techoMensual - ventasMes);
            const estadoSemaforo = porcentaje < 75 ? 'normal' : (porcentaje < 100 ? 'alerta' : 'excedido');

            // Acumulado del año fiscal consultado (estrictamente dentro del anioSel)
            let ventasAnual = 0;
            if (Array.isArray(this.ventas)) {
                ventasAnual = this.ventas
                    .filter(v => {
                        if (Number(v.estado) === 0) return false;
                        if (!v.fecha_hora_venta) return false;
                        const ym = extractYearMonth(v.fecha_hora_venta);
                        return ym && ym.year === anioSel;
                    })
                    .reduce((sum, v) => sum + Number(v.total_venta || 0), 0);
            } else if (this.techoFiscalData?.ventas_anual_acumulado) {
                ventasAnual = Number(this.techoFiscalData.ventas_anual_acumulado);
            }

            return {
                regimen: regimen,
                ventas_mes: Number(ventasMes.toFixed(2)),
                techo_mensual: techoMensual,
                porcentaje_consumido: Number(porcentaje.toFixed(2)),
                saldo_disponible: Number(saldoDisponible.toFixed(2)),
                estado_semaforo: estadoSemaforo,
                ventas_anual_acumulado: Number(ventasAnual.toFixed(2)),
                techo_anual: Number((techoMensual * 12).toFixed(2))
            };
        },

        // RESULTADO DEL MES: Flujo Neto de Dinero = (+) Ventas (-) Gastos Operativos (-) Pagos a Proveedores
        get libroFiscalResultadoMes() {
            return Number((this.libroFiscalTotalIngresos - this.libroFiscalTotalGastosOperativos - this.libroFiscalInventarioPagado).toFixed(2));
        },
        get libroFiscalRentabilidad() {
            return this.libroFiscalResultadoMes;
        },

        // Margen de Flujo Neto (%)
        get libroFiscalMargen() {
            const ing = this.libroFiscalTotalIngresos;
            if (ing <= 0) return 0;
            return Number(((this.libroFiscalResultadoMes / ing) * 100).toFixed(1));
        },

        // Total de salidas combinadas (para auditoría total si se requiere)
        get libroFiscalTotalEgresos() {
            return Number((this.libroFiscalTotalGastosOperativos + this.libroFiscalTotalInventario).toFixed(2));
        },

        // Helper para formatear fecha y hora completa
        formatDateTime(dateInput) {
            if (!dateInput) return '';
            const d = (dateInput instanceof Date) ? dateInput : new Date(dateInput);
            if (isNaN(d.getTime())) return String(dateInput);
            return d.toLocaleString('es-NI', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });
        },

        // Consulta de datos de Libro Diario conectada al backend con fallback local reactivo
        async fetchLibroDiario() {
            this.libroDiarioEmisionFecha = this.formatDateTime(new Date());

            const mes = Number(this.libroDiarioMes);
            const anio = Number(this.libroDiarioAnio);

            // 1. Si ya tenemos ventas en memoria, renderizar inmediatamente en 0ms
            if (Array.isArray(this.ventas) && this.ventas.length > 0) {
                this.calcularLibroDiarioLocal();
                this.libroDiarioLoading = false;
            } else {
                this.libroDiarioLoading = true;
            }

            // 2. Disparar en segundo plano (no bloqueante) cargas auxiliares de gastos e inventario
            if (typeof this.fetchCuentasPorPagar === 'function' && (!this.cuentasPorPagar || this.cuentasPorPagar.length === 0)) {
                this.fetchCuentasPorPagar().catch(() => { });
            }
            if (typeof this.fetchVentas === 'function' && (!this.ventas || this.ventas.length === 0)) {
                this.fetchVentas().catch(() => { });
            }
            if (typeof this.fetchInventario === 'function' && (!this.movimientosInventario || this.movimientosInventario.length === 0)) {
                this.fetchInventario().catch(() => { });
            }

            // 3. Consultar el endpoint oficial rápido de agregación del Libro Diario
            try {
                const res = await this.apiFetch(`/api/fiscal/libro-diario?mes=${mes}&anio=${anio}`);
                if (res.ok) {
                    const data = await res.json();
                    if (data && data.success && Number(data.mes) === Number(this.libroDiarioMes) && Number(data.anio) === Number(this.libroDiarioAnio)) {
                        this.libroDiarioData = data;
                        this.libroDiarioLoading = false;
                        this.$nextTick(() => {
                            if (window.lucide) window.lucide.createIcons();
                        });
                        return;
                    }
                }
            } catch (err) {
                console.warn('Fallo al consultar /api/fiscal/libro-diario, calculando desde ventas locales:', err);
            }

            // 4. Fallback reactivo local
            this.calcularLibroDiarioLocal();
            this.libroDiarioLoading = false;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        // Cálculo reactivo local en caso de desconexión o para actualizar en tiempo real
        calcularLibroDiarioLocal() {
            const mesSel = Number(this.libroDiarioMes);
            const anioSel = Number(this.libroDiarioAnio);

            const ventasMes = (this.ventas || []).filter(v => {
                if (Number(v.estado) === 0) return false;
                if (!v.fecha_hora_venta) return false;
                const ym = extractYearMonth(v.fecha_hora_venta);
                return ym && ym.year === anioSel && ym.month === mesSel;
            });

            // Agrupar por día (YYYY-MM-DD)
            const diasMap = {};
            let totalAcumulado = 0;
            let totalTransacciones = 0;

            ventasMes.forEach(v => {
                const fechaStr = getLocalDateStr(v.fecha_hora_venta);
                if (!diasMap[fechaStr]) {
                    diasMap[fechaStr] = {
                        fecha: fechaStr,
                        cantidad_transacciones: 0,
                        codigos: [],
                        total_dia: 0
                    };
                }
                diasMap[fechaStr].cantidad_transacciones += 1;
                diasMap[fechaStr].codigos.push(v.codigo_venta || '');
                diasMap[fechaStr].total_dia += Number(v.total_venta || 0);

                totalAcumulado += Number(v.total_venta || 0);
                totalTransacciones += 1;
            });

            const diasArray = Object.values(diasMap)
                .sort((a, b) => a.fecha.localeCompare(b.fecha))
                .map(d => {
                    const codigosValidos = d.codigos.filter(Boolean).sort();
                    return {
                        fecha: d.fecha,
                        cantidad_transacciones: d.cantidad_transacciones,
                        comprobante_inicial: codigosValidos[0] || '---',
                        comprobante_final: codigosValidos[codigosValidos.length - 1] || '---',
                        total_dia: d.total_dia.toFixed(2)
                    };
                });

            this.libroDiarioData = {
                success: true,
                anio: anioSel,
                mes: mesSel,
                total_acumulado_mes: totalAcumulado.toFixed(2),
                total_transacciones_mes: totalTransacciones,
                dias: diasArray
            };
        },

        // Disparador de impresión formal del Cuaderno Fiscal con nombre oficial de guardado
        async printLibroDiario() {
            this.libroDiarioEmisionFecha = this.formatDateTime(new Date());

            // Asegurar que las cuentas por pagar estén actualizadas antes de imprimir
            if (typeof this.fetchCuentasPorPagar === 'function' && (!this.cuentasPorPagar || this.cuentasPorPagar.length === 0)) {
                try {
                    await this.fetchCuentasPorPagar();
                } catch (e) {
                    console.warn('No se pudieron recargar cuentas por pagar al imprimir:', e);
                }
            }

            const originalTitle = document.title;
            const mesNombre = this.nombreMesSeleccionado || `Mes-${this.libroDiarioMes}`;
            const comercioNombre = (this.empresa?.nombre_comercial || 'Comercio')
                .replace(/[/\\?%*:|"<>]/g, '-').trim();
            const fileName = `Balance Mensual ${mesNombre} ${comercioNombre}`;

            document.title = fileName;

            window.print();

            // Restaurar título de pestaña
            setTimeout(() => {
                document.title = originalTitle;
            }, 1000);
            window.addEventListener('afterprint', () => {
                document.title = originalTitle;
            }, { once: true });
        },

        // Exportación a CSV oficial para auditoría fiscal y cuaderno contable en Excel
        exportLibroDiarioCSV() {
            this.libroDiarioEmisionFecha = this.formatDateTime(new Date());
            const empNombre = this.empresa?.nombre_comercial || 'EMPRESA';
            const empRuc = this.empresa?.numero_ruc || 'N/A';
            const mesNombre = this.nombreMesSeleccionado;
            const anio = this.libroDiarioAnio;
            const comercioNombre = (this.empresa?.nombre_comercial || 'Comercio')
                .replace(/[/\\?%*:|"<>]/g, '-').trim();

            let csvContent = '\uFEFF'; // BOM UTF-8 para apertura correcta en Microsoft Excel

            // Membrete en CSV
            csvContent += `"${empNombre}"\r\n`;
            csvContent += `"RUC: ${empRuc}"\r\n`;
            csvContent += `"CUADERNO FISCAL Y DE CONTROL FINANCIERO MENSUAL - RÉGIMEN CUOTA FIJA"\r\n`;
            csvContent += `"Período Fiscal: ${mesNombre} ${anio}"\r\n`;
            csvContent += `"Fecha de Emisión del Libro: ${this.libroDiarioEmisionFecha}"\r\n\r\n`;

            // Resumen de Flujo de Dinero y Saldo del Mes
            csvContent += `"RESUMEN DE FLUJO DE DINERO Y SALDO DEL MES"\r\n`;
            csvContent += `"Métrica / Concepto","Monto (C$)","Detalle Explicativo"\r\n`;
            csvContent += `"1. Total Facturado (Ventas)","${this.libroFiscalTotalIngresos.toFixed(2)}","${this.libroFiscalTotalTransacciones} tickets emitidos"\r\n`;
            csvContent += `"2. Gastos Operativos","${this.libroFiscalTotalGastosOperativos.toFixed(2)}","Servicios, alquiler, DGI y gastos diarios en efectivo"\r\n`;
            csvContent += `"3. Pagos a Proveedores de Mercadería","${this.libroFiscalInventarioPagado.toFixed(2)}","Pagado: C$ ${this.libroFiscalInventarioPagado.toFixed(2)} | Pendiente (Crédito): C$ ${this.libroFiscalInventarioPendiente.toFixed(2)}"\r\n`;
            csvContent += `"4. Mermas Registradas","${this.libroFiscalTotalMermas.toFixed(2)}","${this.libroFiscalMermasMes.length} productos dañados/vencidos"\r\n`;
            csvContent += `"SALDO NETO DE DINERO EN CAJA/BANCO","${this.libroFiscalResultadoMes.toFixed(2)}","Flujo Neto: Ventas - Gastos Operativos - Pagos a Proveedores"\r\n\r\n`;

            // SECCIÓN I: Ventas Diarias
            csvContent += `"I. CUADERNO DE INGRESOS - VENTAS DIARIAS FACTURADAS"\r\n`;
            csvContent += `"Fecha","Cantidad de Tickets","Total Diario (C$)"\r\n`;

            (this.libroDiarioData?.dias || []).forEach(d => {
                csvContent += `"${d.fecha}","${d.cantidad_transacciones}","${d.total_dia}"\r\n`;
            });
            csvContent += `"TOTAL VENTAS MES","${this.libroFiscalTotalTransacciones} tickets","${this.libroFiscalTotalIngresos.toFixed(2)}"\r\n\r\n`;

            // SECCIÓN II: Gastos Operativos y Gastos Diarios en Efectivo
            csvContent += `"II. GASTOS OPERATIVOS Y GASTOS DIARIOS EN EFECTIVO"\r\n`;
            csvContent += `"Fecha","Proveedor / Origen","Concepto","Tipo","Monto (C$)"\r\n`;

            (this.libroFiscalGastosOperativosCuentas || []).forEach(c => {
                const fecha = c.fecha_emision || (c.created_at ? c.created_at.slice(0, 10) : '---');
                csvContent += `"${fecha}","${c.proveedor?.nombre_comercial || 'Proveedor'}","${c.descripcion || 'Gasto operativo'}","Deuda Operativa","${Number(c.monto_total || 0).toFixed(2)}"\r\n`;
            });
            (this.libroFiscalCajaEgresosMes || []).forEach(eg => {
                const fecha = eg.fecha_hora_movimiento ? String(eg.fecha_hora_movimiento).slice(0, 10) : '---';
                const concepto = typeof this.getMovimientoConcepto === 'function' ? this.getMovimientoConcepto(eg) : 'Egreso de Caja';
                csvContent += `"${fecha}","Control Diario","${concepto}","Efectivo","${Math.abs(Number(eg.monto_movimiento || 0)).toFixed(2)}"\r\n`;
            });
            csvContent += `"TOTAL GASTOS OPERATIVOS","","","","${this.libroFiscalTotalGastosOperativos.toFixed(2)}"\r\n\r\n`;

            // SECCIÓN III: Pagos a Proveedores de Mercadería
            csvContent += `"III. PAGOS A PROVEEDORES DE MERCADERÍA"\r\n`;
            csvContent += `"Fecha","Proveedor","N° Factura","Detalle Mercadería","Total Factura (C$)","Monto Pagado (C$)","Saldo Pendiente (Crédito C$)"\r\n`;
            (this.libroFiscalInventarioCuentas || []).forEach(c => {
                const fecha = c.fecha_emision || (c.created_at ? c.created_at.slice(0, 10) : '---');
                const pend = (c.saldo_pendiente !== undefined && c.saldo_pendiente !== null)
                    ? Number(c.saldo_pendiente)
                    : (Number(c.monto_total || 0) - Number(c.monto_pagado || 0));
                csvContent += `"${fecha}","${c.proveedor?.nombre_comercial || 'Proveedor'}","${c.numero_factura || 'S/N'}","${c.descripcion || 'Compra de mercadería'}","${Number(c.monto_total || 0).toFixed(2)}","${Number(c.monto_pagado || 0).toFixed(2)}","${Math.max(0, pend).toFixed(2)}"\r\n`;
            });
            csvContent += `"TOTAL SALIDAS A PROVEEDORES","","","","${this.libroFiscalTotalInventario.toFixed(2)}","${this.libroFiscalInventarioPagado.toFixed(2)}","${this.libroFiscalInventarioPendiente.toFixed(2)}"\r\n\r\n`;

            // SECCIÓN IV: Mermas y Pérdidas Reales
            csvContent += `"IV. MERMAS Y PÉRDIDAS REALES (PRODUCTOS DAÑADOS / VENCIDOS)"\r\n`;
            csvContent += `"Fecha","Producto","Motivo de Merma","Cantidad","Costo Pérdida (C$)"\r\n`;
            (this.libroFiscalMermasMes || []).forEach(m => {
                const fecha = m.fecha_movimiento || (m.created_at ? m.created_at.slice(0, 10) : '---');
                csvContent += `"${fecha}","${m.producto?.nombre_producto || 'Producto #' + m.id_producto}","${m.tipo_merma || 'Merma'}","${m.cantidad_movimimiento || m.cantidad || 1}","${Number(m.costo_total_perdida || 0).toFixed(2)}"\r\n`;
            });
            csvContent += `"TOTAL MERMAS Y PÉRDIDAS","","","","${this.libroFiscalTotalMermas.toFixed(2)}"\r\n\r\n`;

            // SECCIÓN III: Detalle Individual de Comprobantes de Venta
            const ventasDetalle = this.libroDiarioVentasDetalle;
            if (ventasDetalle.length > 0) {
                csvContent += `"III. DETALLE INDIVIDUAL DE COMPROBANTES EMITIDOS EN EL PERÍODO"\r\n`;
                csvContent += `"N° Factura / Ticket","Fecha y Hora","Cliente","Método de Pago","Total Facturado (C$)"\r\n`;
                ventasDetalle.forEach(v => {
                    const fechaFormateada = typeof this.formatDate === 'function' ? this.formatDate(v.fecha_hora_venta) : v.fecha_hora_venta;
                    csvContent += `"${v.codigo_venta}","${fechaFormateada}","${v.cliente_nombre || 'Consumidor Final'}","${v.metodo_pago || 'Efectivo'}","${Number(v.total_venta || 0).toFixed(2)}"\r\n`;
                });
            }

            // Descarga con el formato oficial: "Balance Mensual + mes + comercio-nombre.csv"
            const downloadFileName = `Balance Mensual ${mesNombre} ${comercioNombre}.csv`;
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', downloadFileName);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);

            this.notify('Balance Mensual Exportado', `Se descargó "${downloadFileName}".`, 'success');
        }
    };
}
