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
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.customerForm)
                });

                if (!res.ok) throw new Error('Error al guardar el cliente');

                this.showCustomerModal = false;
                this.refreshAll();
                Swal.fire({
                    icon: 'success',
                    title: 'Cliente guardado',
                    background: '#1e293b',
                    color: '#fff'
                });
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message,
                    background: '#1e293b',
                    color: '#fff'
                });
            }
        },

        async deleteCustomer(c) {
            const result = await Swal.fire({
                title: '¿Eliminar cliente?',
                text: `Se eliminará "${c.nombre_apellido_cliente}"`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                background: '#1e293b',
                color: '#fff'
            });

            if (result.isConfirmed) {
                await fetch(`/api/clientes/${c.cliente_id}`, {
                    method: 'DELETE'
                });
                this.refreshAll();
            }
        }
    };
}
