{{--
    =============================================================================
    DOCUMENTACIÓN DE VISTA: Login / Inicio de Sesión
    Archivo: resources/views/auth/loginView.blade.php
    Propósito: Interfaz visual para la autenticación de usuarios en el sistema.
    Controlador asociado: App\Http\Controllers\AuthController (/api/auth/login)
    Integración: Incluido en welcome.blade.php cuando !isAuthenticated
    =============================================================================
--}}

<div x-show="!isAuthenticated" x-cloak class="min-h-screen flex items-center justify-center p-4 bg-dark-950 font-sans relative overflow-hidden">
    
    {{-- Efectos de Fondo Luminoso / Blur decorativos --}}
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-brand-600/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>

    {{-- Switch Modo Claro / Modo Oscuro en esquina superior derecha --}}
    <div class="absolute top-6 right-6 z-20">
        <button type="button"
            @click="toggleTheme()"
            :title="darkMode ? 'Cambiar a Modo Claro' : 'Cambiar a Modo Oscuro'"
            class="relative inline-flex h-8 w-16 items-center rounded-full p-1 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 border shadow-inner"
            :class="darkMode ? 'bg-slate-800 border-slate-700 hover:bg-slate-750' : 'bg-amber-50 border-amber-200 hover:bg-amber-100'"
            role="switch"
            :aria-checked="darkMode">
            <span class="sr-only">Cambiar tema</span>

            <!-- Background Sun & Moon Indicators -->
            <span class="absolute left-1.5 flex items-center justify-center text-amber-500 pointer-events-none transition-opacity duration-200"
                :class="!darkMode ? 'opacity-100' : 'opacity-30'">
                <i data-lucide="sun" class="w-3.5 h-3.5"></i>
            </span>
            <span class="absolute right-1.5 flex items-center justify-center text-indigo-400 pointer-events-none transition-opacity duration-200"
                :class="darkMode ? 'opacity-100' : 'opacity-30'">
                <i data-lucide="moon" class="w-3.5 h-3.5"></i>
            </span>

            <!-- Sliding Thumb -->
            <span class="inline-flex h-6 w-6 transform items-center justify-center rounded-full shadow-md transition-transform duration-300 ease-in-out z-10"
                :class="darkMode ? 'translate-x-7 bg-slate-900 border border-slate-700 text-indigo-300' : 'translate-x-0 bg-white border border-amber-200 text-amber-500'">
                <i data-lucide="sun" x-show="!darkMode" class="w-3.5 h-3.5 text-amber-500" x-cloak></i>
                <i data-lucide="moon" x-show="darkMode" class="w-3.5 h-3.5 text-indigo-400" x-cloak></i>
            </span>
        </button>
    </div>

    {{-- Tarjeta Principal de Login --}}
    <div class="glass-panel w-full max-w-md p-8 rounded-3xl shadow-2xl relative z-10 border border-slate-800 space-y-6">
        
        {{-- Logo y Cabecera --}}
        <div class="text-center space-y-2">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-tr from-brand-600 to-indigo-400 flex items-center justify-center text-white shadow-xl shadow-brand-500/30">
                <i data-lucide="boxes" class="w-7 h-7"></i>
            </div>
            <h1 class="font-display font-bold text-2xl text-white tracking-tight">
                Factura<span class="text-brand-400">Stock</span> <span class="text-xs uppercase px-2 py-0.5 rounded-full bg-brand-500/20 text-brand-300 border border-brand-500/30 font-semibold tracking-wider align-middle">Pro</span>
            </h1>
            <p class="text-xs text-slate-400">Ingresa tus credenciales para acceder al sistema</p>
        </div>

        {{-- Alerta de Error si falla el Login --}}
        <div x-show="loginError" x-cloak class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs flex items-center gap-2.5">
            <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
            <span x-text="loginError"></span>
        </div>

        {{-- Formulario de Inicio de Sesión --}}
        <form @submit.prevent="login()" class="space-y-4">
            
            {{-- Campo: Nombre de Usuario --}}
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nombre de Usuario</label>
                <div class="relative">
                    <i data-lucide="user" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input type="text"
                        x-model="loginForm.nombre_usuario"
                        required
                        autocomplete="username"
                        placeholder="Ej. si_dquiroz"
                        class="w-full bg-dark-900 border border-slate-700 rounded-xl pl-10 pr-4 py-2.5 text-sm text-white placeholder-slate-400 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all">
                </div>
            </div>

            {{-- Campo: Contraseña con botón Ver / Ocultar --}}
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Contraseña</label>
                <div class="relative">
                    <i data-lucide="lock" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input :type="showPassword ? 'text' : 'password'"
                        x-model="loginForm.contrasenia_usuario"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="w-full bg-dark-900 border border-slate-700 rounded-xl pl-10 pr-11 py-2.5 text-sm text-white placeholder-slate-400 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all">
                    <button type="button"
                        @click="showPassword = !showPassword"
                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition-colors">
                        <i :data-lucide="showPassword ? 'eye-off' : 'eye'" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            {{-- Botón de Inicio de Sesión con Spinner --}}
            <div class="pt-2">
                <button type="submit"
                    :disabled="isLoggingIn"
                    :class="isLoggingIn ? 'opacity-70 cursor-not-allowed' : ''"
                    class="w-full py-3 bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 text-white font-semibold text-sm rounded-xl shadow-lg shadow-brand-600/25 transition-all flex items-center justify-center gap-2">
                    <span x-show="isLoggingIn" class="inline-block animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full" x-cloak></span>
                    <span x-text="isLoggingIn ? 'Iniciando Sesión...' : 'Ingresar al Sistema'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
