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
        const s = String(dateVal).trim();
        const match = s.match(/^(\d{4})-(\d{2})/);
        if (match) {
            return { year: parseInt(match[1], 10), month: parseInt(match[2], 10) };
        }
        const d = new Date(dateVal);
        if (!isNaN(d.getTime())) {
            return { year: d.getFullYear(), month: d.getMonth() + 1 };
        }
        return null;
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
            dias: []
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
                .sort((a, b) => new Date(b.fecha_hora_venta) - new Date(a.fecha_hora_venta));
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

            return movs.filter(m => {
                if (Number(m.monto_movimiento || 0) >= 0) return false;
                if (m.id_venta) return false;
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
            return Number(this.libroFiscalMermasMes.reduce((sum, m) => sum + Number(m.costo_total_perdida || 0), 0).toFixed(2));
        },

        // Total Ingresos Facturados del Mes
        get libroFiscalTotalIngresos() {
            return Number(this.libroDiarioData?.total_acumulado_mes || 0);
        },

        // RESULTADO DEL MES: Ganancia Operativa Real = (+) Ventas (-) Gastos Operativos (-) Mermas
        // Nota: NO se restan compras masivas de stock a crédito como pérdida directa del mes
        get libroFiscalResultadoMes() {
            return Number((this.libroFiscalTotalIngresos - this.libroFiscalTotalGastosOperativos - this.libroFiscalTotalMermas).toFixed(2));
        },
        get libroFiscalRentabilidad() {
            return this.libroFiscalResultadoMes;
        },

        // Margen Operativo Real (%)
        get libroFiscalMargen() {
            const ing = this.libroFiscalTotalIngresos;
            if (ing <= 0) return 0;
            return Number(((this.libroFiscalResultadoMes / ing) * 100).toFixed(1));
        },

        // Total de salidas combinadas (para auditoría total si se requiere)
        get libroFiscalTotalEgresos() {
            return Number((this.libroFiscalTotalGastosOperativos + this.libroFiscalTotalInventario).toFixed(2));
        },

        // ALERTAS EDUCATIVAS AUTOMÁTICAS PARA EL DUEÑO DE NEGOCIO
        get libroFiscalAlertaStock() {
            if (this.libroFiscalTotalInventario > this.libroFiscalTotalIngresos && this.libroFiscalTotalIngresos > 0) {
                return `💡 Compraste C$ ${this.libroFiscalTotalInventario.toLocaleString('es-NI', { minimumFractionDigits: 2 })} en mercadería. Es inventario para vender, no una pérdida directa. Tu ganancia operativa real del mes es de C$ ${this.libroFiscalResultadoMes.toLocaleString('es-NI', { minimumFractionDigits: 2 })}.`;
            }
            return null;
        },
        get libroFiscalAlertaMermas() {
            if (this.libroFiscalTotalMermas > 0) {
                return `🚨 Registraste C$ ${this.libroFiscalTotalMermas.toLocaleString('es-NI', { minimumFractionDigits: 2 })} en mermas por productos dañados o vencidos. Revisa desperdicios y rotación de stock para evitar fugas de dinero.`;
            }
            return null;
        },

        // Diagnóstico Financiero y Rumbo del Negocio intuitivo y amigable para el dueño
        get libroFiscalDiagnostico() {
            const ventas = this.libroFiscalTotalIngresos;
            const operativos = this.libroFiscalTotalGastosOperativos;
            const stock = this.libroFiscalTotalInventario;
            const mermas = this.libroFiscalTotalMermas;
            const resultado = this.libroFiscalResultadoMes;
            const margen = this.libroFiscalMargen;

            if (ventas === 0 && operativos === 0 && stock === 0 && mermas === 0) {
                return {
                    estado: 'Sin Operaciones en el Período',
                    badge: 'neutral',
                    mensaje: 'No se registran ventas, deudas con proveedores ni mermas en este mes.',
                    rumbo: 'Inicia registrando tus ventas diarias en el POS y las compras o gastos en el sistema para calcular el resultado del mes.'
                };
            }

            if (resultado > 0) {
                let msg = `¡Excelente resultado! Tu negocio generó una ganancia operativa real de C$ ${resultado.toLocaleString('es-NI', { minimumFractionDigits: 2 })} (margen del ${margen}% sobre ventas).`;
                if (stock > 0) {
                    msg += ` Además, invertiste C$ ${stock.toLocaleString('es-NI', { minimumFractionDigits: 2 })} en mercadería disponible para la venta.`;
                }
                if (mermas > 0) {
                    msg += ` Se absorbieron C$ ${mermas.toLocaleString('es-NI', { minimumFractionDigits: 2 })} en pérdidas por mermas.`;
                }
                return {
                    estado: 'Ganancia Operativa Positiva',
                    badge: 'rentable',
                    mensaje: msg,
                    rumbo: 'Vas por excelente camino. Mantén controlados los gastos operativos y vigila los productos con riesgo de vencimiento para que no mermen tu ganancia.'
                };
            } else if (resultado === 0) {
                return {
                    estado: 'Punto de Equilibrio Operativo',
                    badge: 'alerta',
                    mensaje: `Tus ventas del mes cubrieron exactamente los gastos operativos y mermas registradas.`,
                    rumbo: 'Intenta impulsar tus ventas diarias en el POS o revisar si puedes reducir gastos operativos para comenzar a generar ganancias libres.'
                };
            } else {
                return {
                    estado: 'Gastos Operativos Superaron las Ventas',
                    badge: 'deficit',
                    mensaje: `En este período los gastos operativos (C$ ${operativos.toLocaleString('es-NI', { minimumFractionDigits: 2 })}) y mermas (C$ ${mermas.toLocaleString('es-NI', { minimumFractionDigits: 2 })}) superaron a las ventas por C$ ${Math.abs(resultado).toLocaleString('es-NI', { minimumFractionDigits: 2 })}. (Nota: Las compras de stock no se consideran pérdida).`,
                    rumbo: 'Revisa qué gastos operativos puedes optimizar y reduce las mermas de inventario para volver a tener números positivos.'
                };
            }
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

        // Selector rápido: Mes Actual
        setLibroDiarioMesActual() {
            const n = new Date();
            this.libroDiarioMes = n.getMonth() + 1;
            this.libroDiarioAnio = n.getFullYear();
            this.fetchLibroDiario();
        },

        // Selector rápido: Mes Anterior
        setLibroDiarioMesAnterior() {
            let m = Number(this.libroDiarioMes) - 1;
            let a = Number(this.libroDiarioAnio);
            if (m < 1) {
                m = 12;
                a -= 1;
            }
            this.libroDiarioMes = m;
            this.libroDiarioAnio = a;
            this.fetchLibroDiario();
        },

        // Consulta de datos de Libro Diario conectada al backend con fallback local reactivo
        async fetchLibroDiario() {
            this.libroDiarioLoading = true;
            this.libroDiarioEmisionFecha = this.formatDateTime(new Date());

            // Garantizar que las cuentas por pagar y ventas estén en memoria
            if (typeof this.fetchCuentasPorPagar === 'function') {
                this.fetchCuentasPorPagar().catch(() => {});
            }
            if (typeof this.fetchVentas === 'function' && (!this.ventas || this.ventas.length === 0)) {
                this.fetchVentas().catch(() => {});
            }

            try {
                const mes = Number(this.libroDiarioMes);
                const anio = Number(this.libroDiarioAnio);

                const res = await this.apiFetch(`/api/fiscal/libro-diario?mes=${mes}&anio=${anio}`);
                if (res.ok) {
                    const data = await res.json();
                    if (data && data.success) {
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

            // Fallback de cálculo reactivo local basado en this.ventas
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
                const fechaStr = v.fecha_hora_venta.substring(0, 10);
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

            // Configurar el título del documento para que "Guardar como PDF" sugiera el nombre oficial requerido
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

            // Resumen Financiero y Resultado del Mes
            csvContent += `"RESUMEN FINANCIERO Y RESULTADO DEL MES"\r\n`;
            csvContent += `"Métrica / Concepto","Monto (C$)","Detalle Explicativo"\r\n`;
            csvContent += `"1. Total Ventas Facturadas","${this.libroFiscalTotalIngresos.toFixed(2)}","${this.libroDiarioData?.total_transacciones_mes || 0} tickets emitidos"\r\n`;
            csvContent += `"2. Gastos Operativos","${this.libroFiscalTotalGastosOperativos.toFixed(2)}","Servicios, DGI, empaques y gastos diarios en efectivo"\r\n`;
            csvContent += `"3. Compras de Stock (Inventario)","${this.libroFiscalTotalInventario.toFixed(2)}","Pagado: C$ ${this.libroFiscalInventarioPagado.toFixed(2)} | Pendiente (Crédito): C$ ${this.libroFiscalInventarioPendiente.toFixed(2)}"\r\n`;
            csvContent += `"4. Mermas y Pérdidas Reales","${this.libroFiscalTotalMermas.toFixed(2)}","${this.libroFiscalMermasMes.length} productos dañados/vencidos"\r\n`;
            csvContent += `"RESULTADO DEL MES (Ganancia Operativa Real)","${this.libroFiscalResultadoMes.toFixed(2)}","Margen Operativo: ${this.libroFiscalMargen}% (Ventas - Gastos Operativos - Mermas)"\r\n`;
            csvContent += `"Diagnóstico y Rumbo","${this.libroFiscalDiagnostico.estado}","${this.libroFiscalDiagnostico.rumbo}"\r\n\r\n`;

            // SECCIÓN I: Ventas Diarias
            csvContent += `"I. CUADERNO DE INGRESOS - VENTAS DIARIAS FACTURADAS"\r\n`;
            csvContent += `"Fecha","Cantidad de Tickets","Comprobante Inicial","Comprobante Final","Rango de Comprobantes","Total Diario (C$)"\r\n`;

            (this.libroDiarioData?.dias || []).forEach(d => {
                const rango = d.comprobante_inicial === d.comprobante_final
                    ? d.comprobante_inicial
                    : `${d.comprobante_inicial} al ${d.comprobante_final}`;
                csvContent += `"${d.fecha}","${d.cantidad_transacciones}","${d.comprobante_inicial}","${d.comprobante_final}","${rango}","${d.total_dia}"\r\n`;
            });
            csvContent += `"TOTAL VENTAS MES","${this.libroDiarioData?.total_transacciones_mes || 0} tickets","","","","${this.libroFiscalTotalIngresos.toFixed(2)}"\r\n\r\n`;

            // SECCIÓN II: Gastos Operativos y Gastos Diarios en Efectivo
            csvContent += `"II. GASTOS OPERATIVOS Y GASTOS DIARIOS EN EFECTIVO"\r\n`;
            csvContent += `"Fecha","Acreedor / Proveedor","Concepto / Detalle","N° Factura / Doc","Tipo","Monto (C$)"\r\n`;

            (this.libroFiscalGastosOperativosCuentas || []).forEach(c => {
                const fecha = c.fecha_emision || (c.created_at ? c.created_at.slice(0, 10) : '---');
                csvContent += `"${fecha}","${c.proveedor?.nombre_comercial || 'Proveedor'}","${c.descripcion || 'Gasto operativo'}","${c.numero_factura || 'S/N'}","Deuda Operativa","${Number(c.monto_total || 0).toFixed(2)}"\r\n`;
            });
            if (this.libroFiscalTotalCajaEgresos > 0) {
                csvContent += `"Mes","Caja Chica","Gastos diarios y suministros en efectivo","Caja","Efectivo","${this.libroFiscalTotalCajaEgresos.toFixed(2)}"\r\n`;
            }
            csvContent += `"TOTAL GASTOS OPERATIVOS","","","","","${this.libroFiscalTotalGastosOperativos.toFixed(2)}"\r\n\r\n`;

            // SECCIÓN III: Compras de Stock e Inversión en Inventario
            csvContent += `"III. COMPRAS DE STOCK E INVERSIÓN EN INVENTARIO (MERCADERÍA)"\r\n`;
            csvContent += `"Fecha","Proveedor","N° Factura","Detalle Mercadería","Total Factura (C$)","Monto Pagado (C$)","Saldo Pendiente (Crédito C$)"\r\n`;
            (this.libroFiscalInventarioCuentas || []).forEach(c => {
                const fecha = c.fecha_emision || (c.created_at ? c.created_at.slice(0, 10) : '---');
                const pend = (c.saldo_pendiente !== undefined && c.saldo_pendiente !== null)
                    ? Number(c.saldo_pendiente)
                    : (Number(c.monto_total || 0) - Number(c.monto_pagado || 0));
                csvContent += `"${fecha}","${c.proveedor?.nombre_comercial || 'Proveedor'}","${c.numero_factura || 'S/N'}","${c.descripcion || 'Compra de mercadería'}","${Number(c.monto_total || 0).toFixed(2)}","${Number(c.monto_pagado || 0).toFixed(2)}","${Math.max(0, pend).toFixed(2)}"\r\n`;
            });
            csvContent += `"TOTAL COMPRAS STOCK","","","","${this.libroFiscalTotalInventario.toFixed(2)}","${this.libroFiscalInventarioPagado.toFixed(2)}","${this.libroFiscalInventarioPendiente.toFixed(2)}"\r\n\r\n`;

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
                    csvContent += `"${v.codigo_venta}","${v.fecha_hora_venta}","${v.cliente_nombre || 'Consumidor Final'}","${v.metodo_pago || 'Efectivo'}","${Number(v.total_venta || 0).toFixed(2)}"\r\n`;
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
