export function clientesModule() {
    return {
        // Customers state & filter
        searchCliente: '',
        showCustomerModal: false,
        isEditingCustomer: false,
        customerForm: {
            cliente_id: null,
            codigo_cliente: '',
            nombre_apellido_cliente: '',
            telefono_cliente: '',
            estado: 1,
        },

        // Customer CRUD Methods
        openCustomerModal(customer = null) {
            if (customer) {
                this.isEditingCustomer = true;
                this.customerForm = {
                    ...customer
                };
            } else {
                this.isEditingCustomer = false;
                this.customerForm = {
                    cliente_id: null,
                    codigo_cliente: `CLI-${String(this.clientes.length + 1).padStart(3, '0')}`,
                    nombre_apellido_cliente: '',
                    telefono_cliente: '',
                    estado: 1
                };
            }
            this.showCustomerModal = true;
        },

        async saveCustomer() {
            const url = this.isEditingCustomer ?
                `/api/clientes/${this.customerForm.cliente_id}` :
                '/api/clientes';
            const method = this.isEditingCustomer ? 'PUT' : 'POST';

            try {
                const res = await this.apiFetch(url, {
                    method,
                    body: JSON.stringify(this.customerForm)
                });

                if (!res.ok) throw new Error('Error al guardar el cliente');
                this.showCustomerModal = false;
                this.notify('Cliente guardado', `"${this.customerForm.nombre_apellido_cliente}" se guardó correctamente.`, 'success');
                await this.fetchClientes();
            } catch (error) {
                this.notify('Error al guardar cliente', error.message, 'error');
            }
        },

        async deleteCustomer(c) {
            const result = await Swal.fire({
                title: '¿Eliminar cliente?',
                text: `Se eliminará "${c.nombre_apellido_cliente}"`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                background: this.darkMode ? '#1e293b' : '#ffffff',
                color: this.darkMode ? '#fff' : '#0f172a'
            });

            if (result.isConfirmed) {
                await this.apiFetch(`/api/clientes/${c.cliente_id}`, {
                    method: 'DELETE'
                });
                this.notify('Cliente Eliminado', `"${c.nombre_apellido_cliente}" fue eliminado.`, 'success');
                await this.fetchClientes();
            }
        }
    };
}
