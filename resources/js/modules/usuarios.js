export function usuariosModule() {
    return {
        // Pestaña activa dentro del módulo: 'usuarios' | 'roles'
        usuariosSubTab: 'usuarios',

        // Filtros y Búsqueda de usuarios
        userSearchQuery: '',
        userRoleFilter: '',
        userStatusFilter: '',

        // Estado del Modal de Usuario
        showUserModal: false,
        isEditingUser: false,
        isSavingUser: false,
        showUserFormPassword: false,
        userForm: {
            usuario_id: null,
            id_rol: '',
            nombre_apellido: '',
            nombre_usuario: '',
            contrasenia_usuario: '',
            fecha_registro: '',
            estado: 1,
        },

        // Estado del Modal de Restablecimiento de Contraseña
        showPasswordModal: false,
        passwordUser: null,
        newPasswordValue: '',
        showPasswordPlainText: false,
        isSavingPassword: false,

        // Estado del Modal de Roles y Privilegios
        showRolModal: false,
        isEditingRol: false,
        isSavingRol: false,
        rolForm: {
            rol_id: null,
            nombre_rol: '',
            descripcion_rol: '',
            permisos: [],
            estado: 1
        },

        // Definición de grupos de permisos para la matriz dinámica
        availablePermissionsGroups: [
            {
                nombre: 'Punto de Venta & Cobro',
                icono: 'shopping-cart',
                permisos: [
                    { clave: 'pos.acceso', etiqueta: 'Acceso al POS', descripcion: 'Permite abrir el módulo de facturación' },
                    { clave: 'ventas.crear', etiqueta: 'Emitir Ventas', descripcion: 'Cobrar y emitir comprobantes de venta' }
                ]
            },
            {
                nombre: 'Catálogo & Inventario',
                icono: 'package',
                permisos: [
                    { clave: 'productos.ver', etiqueta: 'Ver Catálogo', descripcion: 'Consultar lista y stock de productos' },
                    { clave: 'productos.gestionar', etiqueta: 'Crear / Editar Productos', descripcion: 'Gestionar precios, costos y fichas' },
                    { clave: 'inventario.gestionar', etiqueta: 'Ajustes & Kardex', descripcion: 'Movimientos de stock y ajustes de inventario' },
                    { clave: 'categorias.ver', etiqueta: 'Ver Categorías', descripcion: 'Listar categorías del catálogo' },
                    { clave: 'categorias.gestionar', etiqueta: 'Crear / Editar Categorías', descripcion: 'Gestionar categorías de productos' }
                ]
            },
            {
                nombre: 'Cajas & Arqueos',
                icono: 'wallet',
                permisos: [
                    { clave: 'cajas.gestionar', etiqueta: 'Control de Cajas', descripcion: 'Apertura, arqueos, cierres y movimientos monetarios' }
                ]
            },
            {
                nombre: 'Ventas & Facturas',
                icono: 'receipt',
                permisos: [
                    { clave: 'ventas.ver', etiqueta: 'Historial de Facturación', descripcion: 'Consultar ventas pasadas y reimprimir comprobantes' }
                ]
            },
            {
                nombre: 'Proveedores & CxP',
                icono: 'truck',
                permisos: [
                    { clave: 'proveedores.gestionar', etiqueta: 'Proveedores & Cuentas por Pagar', descripcion: 'Gestión de proveedores, facturas de crédito y abonos' }
                ]
            },
            {
                nombre: 'Dashboard & Administración',
                icono: 'shield-check',
                permisos: [
                    { clave: 'dashboard.ver', etiqueta: 'Panel Analítico', descripcion: 'Métricas, KPIs y gráficos de facturación' },
                    { clave: 'empresa.gestionar', etiqueta: 'Datos de la Empresa', descripcion: 'Ajustes de facturación, logo y datos del negocio' },
                    { clave: 'usuarios.gestionar', etiqueta: 'Control de Usuarios & Roles', descripcion: 'Alta, baja, contraseñas y permisos del sistema' },
                    { clave: 'bitacoras.ver', etiqueta: 'Auditoría & Bitácora', descripcion: 'Visualización de registros de actividades' }
                ]
            }
        ],

        // Usuarios filtrados de forma reactiva
        get filteredUsersList() {
            const list = Array.isArray(this.usuarios) ? this.usuarios : [];
            const query = (this.userSearchQuery || '').trim().toLowerCase();
            const roleFilter = this.userRoleFilter;
            const statusFilter = this.userStatusFilter;

            return list.filter(u => {
                if (roleFilter && String(u.id_rol) !== String(roleFilter)) {
                    return false;
                }
                if (statusFilter !== '' && String(u.estado) !== String(statusFilter)) {
                    return false;
                }
                if (query) {
                    const name = (u.nombre_apellido || '').toLowerCase();
                    const username = (u.nombre_usuario || '').toLowerCase();
                    return name.includes(query) || username.includes(query);
                }
                return true;
            });
        },

        // Descripción dinámica del rol en el formulario de usuario
        get selectedUserRoleDescription() {
            if (!this.userForm.id_rol || !Array.isArray(this.roles)) return '';
            const r = this.roles.find(item => Number(item.rol_id) === Number(this.userForm.id_rol));
            if (!r) return '';
            if (r.nombre_rol === 'Administrador' || (Array.isArray(r.permisos) && r.permisos.includes('*'))) {
                return 'Este rol posee privilegios totales sobre todos los módulos y ajustes del sistema.';
            }
            if (Array.isArray(r.permisos) && r.permisos.length > 0) {
                return `Acceso concedido a ${r.permisos.length} permiso(s) específico(s): ${r.permisos.slice(0, 3).join(', ')}${r.permisos.length > 3 ? '...' : ''}`;
            }
            return 'Este rol actualmente no tiene permisos configurados en la base de datos.';
        },

        // Conteo de administradores activos en el sistema
        get activeAdminsCount() {
            if (!Array.isArray(this.usuarios)) return 0;
            return this.usuarios.filter(u =>
                Number(u.estado) === 1 &&
                u.rol &&
                (u.rol.nombre_rol === 'Administrador' || (Array.isArray(u.rol.permisos) && u.rol.permisos.includes('*')))
            ).length;
        },

        // Determina si un usuario dado es el Administrador Principal (Super Admin / Propietario)
        isUserPrincipal(u) {
            if (!u) return false;
            return Boolean(
                u.es_principal ||
                Number(u.usuario_id) === 1 ||
                u.nombre_usuario === 'si_dquiroz'
            );
        },

        // Determina si el usuario que se está editando en el modal es el usuario en sesión
        get isEditingSelf() {
            return Boolean(
                this.isEditingUser &&
                this.currentUser &&
                this.userForm &&
                Number(this.currentUser.usuario_id) === Number(this.userForm.usuario_id)
            );
        },

        // Determina si el usuario que se está editando en el modal es el Administrador Principal
        get isEditingPrincipal() {
            if (!this.isEditingUser || !this.userForm || !this.userForm.usuario_id) return false;
            const targetUser = (this.usuarios || []).find(u => Number(u.usuario_id) === Number(this.userForm.usuario_id));
            if (targetUser) return this.isUserPrincipal(targetUser);
            return Number(this.userForm.usuario_id) === 1 || this.userForm.nombre_usuario === 'si_dquiroz';
        },

        // Determina si el usuario que se está editando en el modal es el único administrador activo disponible
        get isEditingLastAdmin() {
            if (!this.isEditingUser || !this.userForm || !this.userForm.usuario_id) return false;
            const targetUser = (this.usuarios || []).find(u => Number(u.usuario_id) === Number(this.userForm.usuario_id));
            if (!targetUser) return false;
            const isTargetAdmin = targetUser.rol && (targetUser.rol.nombre_rol === 'Administrador' || (Array.isArray(targetUser.rol.permisos) && targetUser.rol.permisos.includes('*')));
            if (!isTargetAdmin || Number(targetUser.estado) !== 1) return false;

            return this.activeAdminsCount <= 1;
        },

        // Determina si está permitido modificar el rol del usuario en el formulario
        get canChangeUserRole() {
            if (!this.isEditingUser) return true;
            if (this.isEditingSelf) return false;
            if (this.isEditingPrincipal) return false;
            if (this.isEditingLastAdmin) return false;
            return true;
        },

        // Determina si está permitido modificar el estado (activo/inactivo) del usuario en el formulario
        get canChangeUserStatus() {
            if (!this.isEditingUser) return true;
            if (this.isEditingSelf) return false;
            if (this.isEditingPrincipal) return false;
            if (this.isEditingLastAdmin) return false;
            return true;
        },

        // Retrocompatibilidad para cualquier referencia previa
        get isEditingAdminUser() {
            return !this.canChangeUserRole;
        },

        // Determina si se puede editar un usuario desde la tabla
        canEditUser(u) {
            if (!u) return false;
            const isTargetPrincipal = this.isUserPrincipal(u);
            const isSelf = this.currentUser && Number(this.currentUser.usuario_id) === Number(u.usuario_id);
            if (isTargetPrincipal && !isSelf) return false;
            return true;
        },

        // Determina si se puede restablecer la contraseña de un usuario desde la tabla
        canResetPassword(u) {
            if (!u) return false;
            const isTargetPrincipal = this.isUserPrincipal(u);
            const isSelf = this.currentUser && Number(this.currentUser.usuario_id) === Number(u.usuario_id);
            if (isTargetPrincipal && !isSelf) return false;
            return true;
        },

        // Determina si se puede bloquear/desbloquear un usuario de la lista
        canToggleUser(u) {
            if (!u) return false;
            if (this.currentUser && Number(this.currentUser.usuario_id) === Number(u.usuario_id)) return false;
            if (this.isUserPrincipal(u)) return false;
            const esAdmin = u.rol && (u.rol.nombre_rol === 'Administrador' || (Array.isArray(u.rol.permisos) && u.rol.permisos.includes('*')));
            if (esAdmin && Number(u.estado) === 1 && this.activeAdminsCount <= 1) {
                return false;
            }
            return true;
        },

        // Determina si se puede eliminar un usuario de la lista
        canDeleteUser(u) {
            if (!u) return false;
            if (this.currentUser && Number(this.currentUser.usuario_id) === Number(u.usuario_id)) return false;
            if (this.isUserPrincipal(u)) return false;
            const esAdmin = u.rol && (u.rol.nombre_rol === 'Administrador' || (Array.isArray(u.rol.permisos) && u.rol.permisos.includes('*')));
            if (esAdmin && Number(u.estado) === 1 && this.activeAdminsCount <= 1) {
                return false;
            }
            return true;
        },

        getUserActionTooltip(u, action) {
            if (!u) return '';
            const isSelf = this.currentUser && Number(this.currentUser.usuario_id) === Number(u.usuario_id);
            const isTargetPrincipal = this.isUserPrincipal(u);
            const esAdmin = u.rol && (u.rol.nombre_rol === 'Administrador' || (Array.isArray(u.rol.permisos) && u.rol.permisos.includes('*')));
            const isLastAdmin = esAdmin && Number(u.estado) === 1 && this.activeAdminsCount <= 1;

            if (action === 'edit') {
                if (isTargetPrincipal && !isSelf) return 'El Administrador Principal solo puede ser editado por sí mismo';
                return 'Editar datos del usuario';
            }

            if (action === 'password') {
                if (isTargetPrincipal && !isSelf) return 'No puedes restablecer la contraseña del Administrador Principal';
                return 'Restablecer contraseña de acceso';
            }

            if (action === 'toggle') {
                if (isSelf) return 'No puedes bloquear tu propia cuenta en sesión';
                if (isTargetPrincipal) return 'No se puede bloquear al Administrador Principal del sistema';
                if (isLastAdmin) return 'No se puede bloquear al único Administrador activo del sistema';
                return Number(u.estado) === 1 ? 'Bloquear acceso al usuario' : 'Habilitar acceso al usuario';
            }

            if (action === 'delete') {
                if (isSelf) return 'No puedes eliminar tu propia cuenta en sesión';
                if (isTargetPrincipal) return 'No se puede eliminar al Administrador Principal del sistema';
                if (isLastAdmin) return 'No se puede eliminar al único Administrador activo del sistema';
                return 'Eliminar usuario';
            }

            return '';
        },

        // Contador auxiliar de usuarios por rol
        countUsersInRole(rolId) {
            if (!Array.isArray(this.usuarios)) return 0;
            return this.usuarios.filter(u => Number(u.id_rol) === Number(rolId)).length;
        },

        // --- MÉTODOS DE USUARIOS ---
        openUserModal(user = null) {
            if (user && !this.canEditUser(user)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Acción No Permitida',
                    text: 'El Administrador Principal del sistema solo puede ser editado por sí mismo.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
            }
            if (user) {
                this.isEditingUser = true;
                this.userForm = {
                    usuario_id: user.usuario_id,
                    id_rol: user.id_rol || (this.roles[0] ? this.roles[0].rol_id : ''),
                    nombre_apellido: user.nombre_apellido || '',
                    nombre_usuario: user.nombre_usuario || '',
                    contrasenia_usuario: '',
                    fecha_registro: user.fecha_registro || '',
                    estado: user.estado !== undefined ? Number(user.estado) : 1
                };
            } else {
                this.isEditingUser = false;
                this.userForm = {
                    usuario_id: null,
                    id_rol: (this.roles && this.roles[0]) ? this.roles[0].rol_id : '',
                    nombre_apellido: '',
                    nombre_usuario: '',
                    contrasenia_usuario: '',
                    fecha_registro: new Date().toISOString().slice(0, 10),
                    estado: 1
                };
            }
            this.showUserFormPassword = false;
            this.showUserModal = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        generateUserFormPassword() {
            const chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#$%&*';
            let pass = '';
            for (let i = 0; i < 10; i++) {
                pass += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            this.userForm.contrasenia_usuario = pass;
            this.showUserFormPassword = true;
        },

        async saveUser() {
            if (this.isSavingUser) return;

            const url = this.isEditingUser ?
                `/api/usuarios/${this.userForm.usuario_id}` :
                '/api/usuarios';
            const method = this.isEditingUser ? 'PUT' : 'POST';

            const payload = {
                nombre_apellido: (this.userForm.nombre_apellido || '').trim(),
                nombre_usuario: (this.userForm.nombre_usuario || '').trim(),
            };

            // Solo enviar id_rol si está permitido modificarlo
            if (this.canChangeUserRole) {
                payload.id_rol = Number(this.userForm.id_rol);
            }

            // Solo enviar estado si está permitido modificarlo; si está protegido, se mantiene en 1
            if (this.canChangeUserStatus) {
                payload.estado = Number(this.userForm.estado);
            } else {
                payload.estado = 1;
            }

            if (this.userForm.fecha_registro) {
                payload.fecha_registro = this.userForm.fecha_registro;
            }

            if (!this.isEditingUser || this.userForm.contrasenia_usuario) {
                payload.contrasenia_usuario = this.userForm.contrasenia_usuario;
            }

            this.isSavingUser = true;
            try {
                const res = await this.apiFetch(url, {
                    method,
                    body: JSON.stringify(payload)
                });

                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    const errDetail = data.errors ? Object.values(data.errors).flat().join('<br>') : (data.message || 'Error al guardar el usuario.');
                    throw new Error(errDetail);
                }

                this.showUserModal = false;
                await this.fetchUsuarios();

                Swal.fire({
                    icon: 'success',
                    title: this.isEditingUser ? '¡Usuario Actualizado!' : '¡Usuario Creado!',
                    text: `El usuario "${payload.nombre_usuario}" ha sido guardado exitosamente.`,
                    timer: 2000,
                    showConfirmButton: false,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo guardar',
                    html: error.message,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
            } finally {
                this.isSavingUser = false;
            }
        },

        // Bloquear / Desbloquear usuario con confirmación rápida
        async toggleUserStatus(u) {
            if (this.currentUser && Number(this.currentUser.usuario_id) === Number(u.usuario_id)) {
                Swal.fire({
                    icon: 'info',
                    title: 'Acción No Permitida',
                    text: 'No puedes bloquear o desactivar tu propia cuenta en sesión.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
            }

            if (this.isUserPrincipal(u)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Acción No Permitida',
                    text: 'No se puede desactivar o bloquear al Administrador Principal del sistema.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
            }

            const esAdmin = u.rol && (u.rol.nombre_rol === 'Administrador' || (Array.isArray(u.rol.permisos) && u.rol.permisos.includes('*')));
            if (esAdmin && Number(u.estado) === 1 && this.activeAdminsCount <= 1) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Acción No Permitida',
                    text: 'No se puede desactivar o bloquear al único Administrador activo del sistema.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
            }

            const nuevoEstado = Number(u.estado) === 1 ? 0 : 1;
            const accion = nuevoEstado === 1 ? 'habilitar' : 'bloquear';

            const confirm = await Swal.fire({
                title: `¿${accion.charAt(0).toUpperCase() + accion.slice(1)} usuario?`,
                html: `¿Estás seguro de que deseas <b>${accion}</b> a <b>${u.nombre_apellido}</b> (@${u.nombre_usuario})?<br><small class="text-slate-400">${nuevoEstado === 0 ? 'El usuario no podrá acceder al sistema hasta ser rehabilitado.' : 'El usuario podrá volver a iniciar sesión de inmediato.'}</small>`,
                icon: nuevoEstado === 0 ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonColor: nuevoEstado === 0 ? '#e11d48' : '#10b981',
                confirmButtonText: nuevoEstado === 0 ? 'Sí, Bloquear' : 'Sí, Habilitar',
                cancelButtonText: 'Cancelar',
                background: this.darkMode ? '#1e293b' : '#ffffff',
                color: this.darkMode ? '#fff' : '#0f172a'
            });

            if (!confirm.isConfirmed) return;

            try {
                const res = await this.apiFetch(`/api/usuarios/${u.usuario_id}`, {
                    method: 'PUT',
                    body: JSON.stringify({ estado: nuevoEstado })
                });

                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || 'No se pudo actualizar el estado del usuario.');
                }

                u.estado = nuevoEstado;
                await this.fetchUsuarios();

                this.notify(
                    nuevoEstado === 1 ? 'Usuario Habilitado' : 'Usuario Bloqueado',
                    `"${u.nombre_usuario}" ahora está ${nuevoEstado === 1 ? 'activo' : 'bloqueado'}.`,
                    nuevoEstado === 1 ? 'success' : 'warning',
                    2500
                );
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al cambiar estado',
                    text: err.message,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
            }
        },

        // --- MÉTODOS DE RESTABLECIMIENTO DE CONTRASEÑA ---
        openPasswordModal(u) {
            if (u && !this.canResetPassword(u)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Acción No Permitida',
                    text: 'No puedes restablecer la contraseña del Administrador Principal del sistema.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
            }
            this.passwordUser = u;
            this.newPasswordValue = '';
            this.showPasswordPlainText = false;
            this.showPasswordModal = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        generateRandomPassword() {
            const chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#$%&*';
            let pass = '';
            for (let i = 0; i < 10; i++) {
                pass += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            this.newPasswordValue = pass;
            this.showPasswordPlainText = true;
        },

        copyGeneratedPassword() {
            if (!this.newPasswordValue) return;
            navigator.clipboard.writeText(this.newPasswordValue)
                .then(() => {
                    this.notify('Copiado', 'Contraseña copiada al portapapeles.', 'success', 2000);
                })
                .catch(() => {});
        },

        async saveResetPassword() {
            if (this.isSavingPassword || !this.passwordUser) return;

            if (!this.newPasswordValue || this.newPasswordValue.length < 6) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Contraseña Muy Corta',
                    text: 'La contraseña debe tener al menos 6 caracteres.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
            }

            this.isSavingPassword = true;
            try {
                const res = await this.apiFetch(`/api/usuarios/${this.passwordUser.usuario_id}`, {
                    method: 'PUT',
                    body: JSON.stringify({
                        contrasenia_usuario: this.newPasswordValue
                    })
                });

                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || 'No se pudo actualizar la contraseña.');
                }

                this.showPasswordModal = false;

                await Swal.fire({
                    icon: 'success',
                    title: '¡Contraseña Restablecida!',
                    html: `Se ha asignado la nueva contraseña para <b>@${this.passwordUser.nombre_usuario}</b>.<br><br>Asegúrate de entregársela al usuario para que pueda acceder.`,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al cambiar contraseña',
                    text: err.message,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
            } finally {
                this.isSavingPassword = false;
            }
        },

        async deleteUser(u) {
            if (this.currentUser && Number(this.currentUser.usuario_id) === Number(u.usuario_id)) {
                Swal.fire({
                    icon: 'info',
                    title: 'Acción No Permitida',
                    text: 'No puedes eliminar tu propia cuenta en sesión.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
            }

            if (this.isUserPrincipal(u)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Acción No Permitida',
                    text: 'No se puede eliminar o desactivar al Administrador Principal del sistema.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
            }

            const esAdmin = u.rol && (u.rol.nombre_rol === 'Administrador' || (Array.isArray(u.rol.permisos) && u.rol.permisos.includes('*')));
            if (esAdmin && Number(u.estado) === 1 && this.activeAdminsCount <= 1) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Acción No Permitida',
                    text: 'No se puede eliminar o desactivar al único Administrador activo del sistema.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
            }

            const result = await Swal.fire({
                title: '¿Eliminar usuario?',
                html: `¿Estás seguro de que deseas eliminar permanentemente a <b>${u.nombre_apellido}</b> (@${u.nombre_usuario})?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                background: this.darkMode ? '#1e293b' : '#ffffff',
                color: this.darkMode ? '#fff' : '#0f172a'
            });

            if (result.isConfirmed) {
                try {
                    const res = await this.apiFetch(`/api/usuarios/${u.usuario_id}`, {
                        method: 'DELETE'
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        throw new Error(data.message || 'No se pudo eliminar el usuario.');
                    }
                    this.notify('Usuario Eliminado', `"${u.nombre_usuario}" fue eliminado con éxito.`, 'success');
                    await this.fetchUsuarios();
                } catch (err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'No se pudo eliminar',
                        text: err.message,
                        background: this.darkMode ? '#1e293b' : '#ffffff',
                        color: this.darkMode ? '#fff' : '#0f172a'
                    });
                }
            }
        },

        // --- MÉTODOS DE ROLES & PERMISOS DINÁMICOS (JSON) ---
        openRolModal(rol = null) {
            if (rol && (rol.nombre_rol === 'Administrador' || (Array.isArray(rol.permisos) && rol.permisos.includes('*')))) {
                Swal.fire({
                    icon: 'info',
                    title: 'Rol Protegido',
                    text: 'El rol Administrador es fundamental para el sistema y no puede ser modificado.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
            }

            if (rol) {
                this.isEditingRol = true;
                this.rolForm = {
                    rol_id: rol.rol_id,
                    nombre_rol: rol.nombre_rol || '',
                    descripcion_rol: rol.descripcion_rol || '',
                    permisos: Array.isArray(rol.permisos) ? [...rol.permisos] : [],
                    estado: rol.estado !== undefined ? Number(rol.estado) : 1
                };
            } else {
                this.isEditingRol = false;
                this.rolForm = {
                    rol_id: null,
                    nombre_rol: '',
                    descripcion_rol: '',
                    permisos: ['pos.acceso', 'ventas.crear'],
                    estado: 1
                };
            }
            this.showRolModal = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        hasRolPermission(clave) {
            if (!Array.isArray(this.rolForm.permisos)) return false;
            return this.rolForm.permisos.includes('*') || this.rolForm.permisos.includes(clave);
        },

        toggleRolPermission(clave) {
            if (!Array.isArray(this.rolForm.permisos)) {
                this.rolForm.permisos = [];
            }
            const idx = this.rolForm.permisos.indexOf(clave);
            if (idx === -1) {
                this.rolForm.permisos.push(clave);
            } else {
                this.rolForm.permisos.splice(idx, 1);
            }
        },

        selectAllPermissions() {
            const allKeys = [];
            this.availablePermissionsGroups.forEach(g => {
                g.permisos.forEach(p => {
                    if (!allKeys.includes(p.clave)) allKeys.push(p.clave);
                });
            });
            this.rolForm.permisos = allKeys;
        },

        deselectAllPermissions() {
            this.rolForm.permisos = [];
        },

        toggleGroupPermissions(grupo) {
            if (!Array.isArray(this.rolForm.permisos)) this.rolForm.permisos = [];
            const groupKeys = grupo.permisos.map(p => p.clave);
            const allSelected = groupKeys.every(k => this.rolForm.permisos.includes(k));

            if (allSelected) {
                this.rolForm.permisos = this.rolForm.permisos.filter(k => !groupKeys.includes(k));
            } else {
                groupKeys.forEach(k => {
                    if (!this.rolForm.permisos.includes(k)) this.rolForm.permisos.push(k);
                });
            }
        },

        async saveRol() {
            if (this.isSavingRol) return;

            if (this.isEditingRol && (this.rolForm.nombre_rol === 'Administrador' || (this.roles.find(r => r.rol_id === this.rolForm.rol_id)?.nombre_rol === 'Administrador'))) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Operación Denegada',
                    text: 'El rol Administrador es inmutable y no puede ser modificado.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
            }

            const nombre = (this.rolForm.nombre_rol || '').trim();
            if (!nombre) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Nombre Requerido',
                    text: 'Debe ingresar un nombre identificador para el rol.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
            }

            const url = this.isEditingRol ?
                `/api/roles/${this.rolForm.rol_id}` :
                '/api/roles';
            const method = this.isEditingRol ? 'PUT' : 'POST';

            const payload = {
                nombre_rol: nombre,
                descripcion_rol: (this.rolForm.descripcion_rol || '').trim(),
                permisos: Array.isArray(this.rolForm.permisos) ? this.rolForm.permisos : [],
                estado: Number(this.rolForm.estado)
            };

            this.isSavingRol = true;
            try {
                const res = await this.apiFetch(url, {
                    method,
                    body: JSON.stringify(payload)
                });

                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    const errDetail = data.errors ? Object.values(data.errors).flat().join('<br>') : (data.message || 'Error al guardar el rol.');
                    throw new Error(errDetail);
                }

                this.showRolModal = false;
                await this.fetchUsuarios();

                Swal.fire({
                    icon: 'success',
                    title: this.isEditingRol ? '¡Rol Actualizado!' : '¡Rol Creado!',
                    text: `El rol "${payload.nombre_rol}" ha sido configurado con éxito.`,
                    timer: 2000,
                    showConfirmButton: false,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo guardar el rol',
                    html: error.message,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
            } finally {
                this.isSavingRol = false;
            }
        },

        async deleteRol(rol) {
            if (rol.nombre_rol === 'Administrador') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Operación Denegada',
                    text: 'El rol Administrador es fundamental para el sistema y no puede eliminarse.',
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
                return;
            }

            const result = await Swal.fire({
                title: '¿Eliminar rol?',
                html: `¿Estás seguro de que deseas eliminar el rol <b>${rol.nombre_rol}</b>?<br><small class="text-slate-400">Solo es posible si no tiene usuarios activos asignados.</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                background: this.darkMode ? '#1e293b' : '#ffffff',
                color: this.darkMode ? '#fff' : '#0f172a'
            });

            if (result.isConfirmed) {
                try {
                    const res = await this.apiFetch(`/api/roles/${rol.rol_id}`, {
                        method: 'DELETE'
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        throw new Error(data.message || 'No se pudo eliminar el rol.');
                    }

                    this.notify('Rol Eliminado', `"${rol.nombre_rol}" fue eliminado.`, 'success');
                    await this.fetchUsuarios();
                } catch (err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'No se pudo eliminar el rol',
                        text: err.message,
                        background: this.darkMode ? '#1e293b' : '#ffffff',
                        color: this.darkMode ? '#fff' : '#0f172a'
                    });
                }
            }
        }
    };
}
