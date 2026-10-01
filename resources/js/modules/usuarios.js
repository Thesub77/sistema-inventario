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
                const res = await this.apiFetch(url, {
                    method,
                    body: JSON.stringify(payload)
                });

                if (!res.ok) throw new Error('Error al guardar el usuario');
                this.showUserModal = false;
                this.notify('Usuario guardado', `"${this.userForm.nombre_usuario}" se guardó correctamente.`, 'success');
                await this.fetchUsuarios();
            } catch (error) {
                this.notify('Error al guardar usuario', error.message, 'error');
            }
        },

        async deleteUser(u) {
            const result = await Swal.fire({
                title: '¿Eliminar usuario?',
                text: `Se eliminará "${u.nombre_usuario}"`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                background: this.darkMode ? '#1e293b' : '#ffffff',
                color: this.darkMode ? '#fff' : '#0f172a'
            });

            if (result.isConfirmed) {
                await this.apiFetch(`/api/usuarios/${u.usuario_id}`, {
                    method: 'DELETE'
                });
                this.notify('Usuario Eliminado', `"${u.nombre_usuario}" fue eliminado.`, 'success');
                await this.fetchUsuarios();
            }
        }
    };
}
