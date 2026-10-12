/**
 * ==============================================================================
 * FACTURASTOCK PRO - MÓDULO FISCAL (LIBRO DIARIO DE VENTAS - DGI CUOTA FIJA)
 * Archivo: resources/js/modules/fiscal.js
 * Propósito: Administra la consulta y cálculo del Libro Diario de Ventas por mes/año,
 *            permitiendo su visualización interactiva, exportación en CSV e impresión
 *            formal para auditorías tributarias bajo la Ley 822 (RF-27).
 * Endpoint asociado: GET /api/fiscal/libro-diario
 * ==============================================================================
 */

export function fiscalModule() {
    const now = new Date();
    return {
        // Estado del Libro Diario Fiscal
        libroDiarioMes: now.getMonth() + 1,
        libroDiarioAnio: now.getFullYear(),
        libroDiarioLoading: false,
        libroDiarioEmisionFecha: '',
        showLibroComprobantesDetalle: false,
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

        // Facturas individuales pertenecientes al mes y año seleccionado (para auditoría y descarga detallada)
        get libroDiarioVentasDetalle() {
            const mesSel = Number(this.libroDiarioMes);
            const anioSel = Number(this.libroDiarioAnio);

            return (this.ventas || [])
                .filter(v => {
                    if (Number(v.estado) === 0) return false; // Excluir ventas anuladas
                    if (!v.fecha_hora_venta) return false;
                    const d = new Date(v.fecha_hora_venta);
                    return d.getFullYear() === anioSel && (d.getMonth() + 1) === mesSel;
                })
                .sort((a, b) => new Date(a.fecha_hora_venta) - new Date(b.fecha_hora_venta));
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
                const d = new Date(v.fecha_hora_venta);
                return d.getFullYear() === anioSel && (d.getMonth() + 1) === mesSel;
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

        // Disparador de impresión formal del Libro Diario con nombre oficial de guardado
        printLibroDiario() {
            this.libroDiarioEmisionFecha = this.formatDateTime(new Date());
            const originalTitle = document.title;
            const mesNombre = this.nombreMesSeleccionado || `Mes-${this.libroDiarioMes}`;
            const comercioNombre = (this.empresa?.nombre_comercial || 'Comercio')
                .replace(/[/\\?%*:|"<>]/g, '-').trim();
            const fileName = `Libro Fiscal ${mesNombre} ${comercioNombre}`;

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

        // Exportación a CSV oficial para auditoría fiscal y apertura en Excel
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
            csvContent += `"LIBRO DIARIO DE VENTAS - RÉGIMEN SIMPLIFICADO CUOTA FIJA"\r\n`;
            csvContent += `"Período Fiscal: ${mesNombre} ${anio}"\r\n`;
            csvContent += `"Fecha de Emisión del Libro: ${this.libroDiarioEmisionFecha}"\r\n\r\n`;

            // Encabezados
            csvContent += `"Fecha","Cantidad de Tickets","Comprobante Inicial","Comprobante Final","Rango de Comprobantes","Total Diario (C$)"\r\n`;

            // Filas diarias
            (this.libroDiarioData?.dias || []).forEach(d => {
                const rango = d.comprobante_inicial === d.comprobante_final 
                    ? d.comprobante_inicial 
                    : `${d.comprobante_inicial} al ${d.comprobante_final}`;
                csvContent += `"${d.fecha}","${d.cantidad_transacciones}","${d.comprobante_inicial}","${d.comprobante_final}","${rango}","${d.total_dia}"\r\n`;
            });

            // Fila de Total Acumulado del Mes
            csvContent += `"TOTAL ACUMULADO DEL MES","${this.libroDiarioData?.total_transacciones_mes || 0}","","","","${this.libroDiarioData?.total_acumulado_mes || '0.00'}"\r\n\r\n`;

            // Desglose individual de facturas del mes
            const ventasDetalle = this.libroDiarioVentasDetalle;
            if (ventasDetalle.length > 0) {
                csvContent += `"DETALLE INDIVIDUAL DE COMPROBANTES EMITIDOS EN EL PERÍODO"\r\n`;
                csvContent += `"N° Factura / Ticket","Fecha y Hora","Cliente","Método de Pago","Total Facturado (C$)"\r\n`;
                ventasDetalle.forEach(v => {
                    csvContent += `"${v.codigo_venta}","${v.fecha_hora_venta}","${v.cliente_nombre || 'Consumidor Final'}","${v.metodo_pago || 'Efectivo'}","${Number(v.total_venta || 0).toFixed(2)}"\r\n`;
                });
            }

            // Descarga con el formato oficial: "Libro Fiscal + mes + comercio-nombre.csv"
            const downloadFileName = `Libro Fiscal ${mesNombre} ${comercioNombre}.csv`;
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', downloadFileName);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);

            this.notify('Libro Diario Exportado', `Se descargó "${downloadFileName}".`, 'success');
        }
    };
}
