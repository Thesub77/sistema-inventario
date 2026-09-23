/**
 * ==============================================================================
 * FACTURASTOCK PRO - MÓDULO DE AUTENTICACIÓN
 * Archivo: resources/js/modules/auth.js
 * ==============================================================================
 */

export function authModule() {
    return {
        isAuthenticated: false,
        isLoggingIn: false,
        showPassword: false,
        authToken: null,
        currentUser: {
            usuario_id: null,
            nombre_apellido: 'Invitado',
            nombre_usuario: '',
            rol: '',
            permisos: []
        },
        loginForm: {
            nombre_usuario: '',
            contrasenia_usuario: '',
        },
        loginError: '',

        initAuth() {
            const token = localStorage.getItem('auth_token');
            const savedUser = localStorage.getItem('auth_user');
            if (token && savedUser) {
                try {
                    this.authToken = token;
                    this.currentUser = JSON.parse(savedUser);
                    this.isAuthenticated = true;
                } catch (e) {
                    this.logout();
                }
            }
        },

        async login() {
            if (this.isLoggingIn) return;
            this.isLoggingIn = true;
            this.loginError = '';

            try {
                const res = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.loginForm)
                });

                const data = await res.json();

                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Credenciales incorrectas');
                }

                this.authToken = data.token;
                this.currentUser = data.usuario;
                this.isAuthenticated = true;
                localStorage.setItem('auth_token', data.token);
                localStorage.setItem('auth_user', JSON.stringify(data.usuario));

                this.loginForm.contrasenia_usuario = '';
                this.loginError = '';

                Swal.fire({
                    icon: 'success',
                    title: `¡Bienvenido, ${data.usuario.nombre_apellido}!`,
                    text: 'Has iniciado sesión correctamente.',
                    timer: 2000,
                    showConfirmButton: false,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });

                // Cargar datos del dashboard al iniciar sesión
                await this.loadTab(this.currentTab, true);
            } catch (error) {
                this.loginError = error.message;
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Autenticación',
                    text: error.message,
                    background: this.darkMode ? '#1e293b' : '#ffffff',
                    color: this.darkMode ? '#fff' : '#0f172a'
                });
            } finally {
                this.isLoggingIn = false;
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            }
        },

        async logout() {
            if (this.authToken) {
                try {
                    await fetch('/api/auth/logout', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${this.authToken}`
                        }
                    });
                } catch (e) {
                    console.error('Error al cerrar sesión:', e);
                }
            }

            this.authToken = null;
            this.currentUser = {
                usuario_id: null,
                nombre_apellido: 'Invitado',
                nombre_usuario: '',
                rol: '',
                permisos: []
            };
            this.isAuthenticated = false;
            localStorage.removeItem('auth_token');
            localStorage.removeItem('auth_user');

            Swal.fire({
                icon: 'info',
                title: 'Sesión Finalizada',
                text: 'Has cerrado sesión con éxito.',
                timer: 1500,
                showConfirmButton: false,
                background: this.darkMode ? '#1e293b' : '#ffffff',
                color: this.darkMode ? '#fff' : '#0f172a'
            });

            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        }
    };
}
