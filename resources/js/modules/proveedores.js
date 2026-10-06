export function proveedoresModule() {
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
            registrar_en_caja: true
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
                    if (data.success && data.kpis) {
                        this.cxpKPIs = data.kpis;
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
            this.abonoForm = {
                monto_pago: Number(cuenta.saldo_pendiente || 0).toFixed(2),
                metodo_pago: 'Efectivo',
                referencia_pago: '',
                nota: '',
                registrar_en_caja: Boolean(this.turnoActivo)
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

            if (this.abonoForm.metodo_pago === 'Efectivo' && this.abonoForm.registrar_en_caja && !this.turnoActivo) {
                this.notify('Caja Cerrada', 'Para registrar la salida de efectivo de caja, debes tener un turno activo abierto.', 'warning');
                return;
            }

            this.isSavingAbono = true;
            try {
                const res = await this.apiFetch(`/api/cuentas-por-pagar/${this.selectedCuentaParaAbono.cuenta_por_pagar_id}/pagos`, {
                    method: 'POST',
                    body: JSON.stringify(this.abonoForm)
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

        openHistorialAbonos(cuenta) {
            this.selectedCuentaHistorial = cuenta;
            this.showHistorialAbonosModal = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
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
