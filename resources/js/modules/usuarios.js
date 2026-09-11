export function usuariosModule() {
    return {
        // Users state
        showUserModal: false,
        isEditingUser: false,
        userForm: {
            usuario_id: null,
            id_rol: '',
            nombre_apellido: '',
            nombre_usuario: '',
            contrasenia_usuario: '',
            estado: 1,
        },

        // User CRUD Methods
        openUserModal(user = null) {
            if (user) {
                this.isEditingUser = true;
                this.userForm = {
                    ...user,
                    contrasenia_usuario: ''
                };
            } else {
                this.isEditingUser = false;
                this.userForm = {
                    usuario_id: null,
                    id_rol: this.roles[0] ? this.roles[0].rol_id : '',
                    nombre_apellido: '',
                    nombre_usuario: '',
                    contrasenia_usuario: '',
                    fecha_registro: new Date().toISOString().slice(0, 10),
                    estado: 1
                };
            }
            this.showUserModal = true;
        },

        async saveUser() {
            const url = this.isEditingUser ?
                `/api/usuarios/${this.userForm.usuario_id}` :
                '/api/usuarios';
            const method = this.isEditingUser ? 'PUT' : 'POST';

            const payload = {
                ...this.userForm
            };
            if (this.isEditingUser && !payload.contrasenia_usuario) {
                delete payload.contrasenia_usuario;
            }

            try {
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                if (!res.ok) throw new Error('Error al guardar el usuario');
                this.showUserModal = false;
                Swal.fire({
                    icon: 'success',
                    title: 'Usuario guardado',
                    background: '#1e293b',
                    color: '#fff'
                });
                await this.fetchUsuarios();
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

        async deleteUser(u) {
            const result = await Swal.fire({
                title: '¿Eliminar usuario?',
                text: `Se eliminará "${u.nombre_usuario}"`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                background: '#1e293b',
                color: '#fff'
            });

            if (result.isConfirmed) {
                await fetch(`/api/usuarios/${u.usuario_id}`, {
                    method: 'DELETE'
                });
                await this.fetchUsuarios();
            }
        }
    };
}
