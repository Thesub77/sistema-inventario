export function utilsModule() {
    return {
        notify(title, text = '', icon = 'success', timer = 2800) {
            let container = document.getElementById('fs-toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'fs-toast-container';
                container.className = 'fixed top-4 right-4 z-[999999] flex flex-col gap-2.5 max-w-sm w-full pointer-events-none px-3 sm:px-0';
                document.body.appendChild(container);
            }

            const isDark = (this.darkMode !== undefined) ? this.darkMode : document.documentElement.classList.contains('dark');

            const iconConfig = {
                success: {
                    bg: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30',
                    svg: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>'
                },
                error: {
                    bg: 'bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/30',
                    svg: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>'
                },
                warning: {
                    bg: 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/30',
                    svg: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>'
                },
                info: {
                    bg: 'bg-blue-500/15 text-blue-600 dark:text-blue-400 border-blue-500/30',
                    svg: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
                }
            }[icon] || {
                bg: 'bg-brand-500/15 text-brand-600 dark:text-brand-400 border-brand-500/30',
                svg: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
            };

            const toast = document.createElement('div');
            toast.className = `pointer-events-auto flex items-start gap-3 p-3.5 rounded-2xl shadow-xl border backdrop-blur-md transition-all duration-300 transform translate-x-8 opacity-0 ${
                isDark
                    ? 'bg-slate-900/95 border-slate-700/80 text-white shadow-slate-950/50'
                    : 'bg-white/95 border-slate-200 text-slate-900 shadow-slate-200/80'
            }`;

            toast.innerHTML = `
                <div class="w-7 h-7 rounded-xl flex items-center justify-center flex-shrink-0 border ${iconConfig.bg}">
                    ${iconConfig.svg}
                </div>
                <div class="flex-1 min-w-0 pr-1">
                    <h5 class="text-xs font-bold leading-snug truncate">${title}</h5>
                    ${text ? `<p class="text-[11px] ${isDark ? 'text-slate-400' : 'text-slate-500'} mt-0.5 leading-relaxed break-words">${text}</p>` : ''}
                </div>
                <button type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-0.5 -mr-1 -mt-1 flex-shrink-0 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            `;

            const closeBtn = toast.querySelector('button');
            const removeToast = () => {
                toast.classList.add('translate-x-8', 'opacity-0');
                setTimeout(() => {
                    if (toast.parentNode) toast.parentNode.removeChild(toast);
                }, 300);
            };

            closeBtn.addEventListener('click', removeToast);
            container.appendChild(toast);

            requestAnimationFrame(() => {
                toast.classList.remove('translate-x-8', 'opacity-0');
                toast.classList.add('translate-x-0', 'opacity-100');
            });

            if (timer > 0) {
                setTimeout(removeToast, timer);
            }
        },

        formatCurrency(amount) {
            const sym = (this.empresa && this.empresa.moneda_simbolo) ? this.empresa.moneda_simbolo : 'C$';
            return sym + ' ' + Number(amount || 0).toLocaleString('es-NI', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        formatDate(dateStr) {
            if (!dateStr) return 'N/A';
            let normalized = String(dateStr).trim();
            // Si la fecha viene en formato UTC sin sufijo de zona horaria (ej: "2026-09-27 02:35:00")
            if (/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?(\.\d+)?$/.test(normalized)) {
                normalized = normalized.replace(' ', 'T') + 'Z';
            }
            const d = new Date(normalized);
            const target = isNaN(d.getTime()) ? new Date(dateStr) : d;
            if (isNaN(target.getTime())) return dateStr;

            const pad = n => String(n).padStart(2, '0');
            const day = pad(target.getDate());
            const month = pad(target.getMonth() + 1);
            const year = target.getFullYear();

            let hours = target.getHours();
            const minutes = pad(target.getMinutes());
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? pad(hours) : '12';

            return `${day}/${month}/${year} ${hours}:${minutes} ${ampm}`;
        },

        formatDateOnly(dateStr) {
            if (!dateStr) return '';
            let normalized = String(dateStr).trim();
            if (/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?(\.\d+)?$/.test(normalized)) {
                normalized = normalized.replace(' ', 'T') + 'Z';
            }
            const d = new Date(normalized);
            const target = isNaN(d.getTime()) ? new Date(dateStr) : d;
            if (isNaN(target.getTime())) return dateStr;

            const pad = n => String(n).padStart(2, '0');
            const day = pad(target.getDate());
            const month = pad(target.getMonth() + 1);
            const year = target.getFullYear();

            return `${day}/${month}/${year}`;
        },

        getMovimientoCodigo(mov) {
            if (!mov) return '';
            if (mov.codigo_movimiento) return mov.codigo_movimiento;
            let normalized = String(mov.fecha_hora_movimiento || '').trim();
            if (/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?(\.\d+)?$/.test(normalized)) {
                normalized = normalized.replace(' ', 'T') + 'Z';
            }
            const d = new Date(normalized);
            const target = isNaN(d.getTime()) ? new Date() : d;
            const pad = (n, len = 2) => String(n).padStart(len, '0');
            const day = pad(target.getDate(), 2);
            const month = pad(target.getMonth() + 1, 2);
            const year = String(target.getFullYear()).slice(-2);
            const caja = pad(mov.id_caja || 1, 3);
            const id = pad(mov.caja_movimiento_venta_id || 0, 5);

            return `${day}${month}${year}-${caja}-${id}`;
        },

        formatTime(dateStr) {
            if (!dateStr) return '';
            let normalized = String(dateStr).trim();
            // Si la fecha viene en formato UTC sin sufijo de zona horaria (ej: "2026-09-27 02:35:00")
            if (/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?(\.\d+)?$/.test(normalized)) {
                normalized = normalized.replace(' ', 'T') + 'Z';
            }
            const d = new Date(normalized);
            if (isNaN(d.getTime())) {
                const fallback = new Date(dateStr);
                if (isNaN(fallback.getTime())) return dateStr;
                return fallback.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
            }
            return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
        },

        async apiFetch(url, options = {}) {
            const opts = { ...options };
            opts.headers = opts.headers || {};
            if (!opts.headers['Accept']) {
                opts.headers['Accept'] = 'application/json';
            }
            if (!opts.headers['Content-Type'] && !(opts.body instanceof FormData)) {
                opts.headers['Content-Type'] = 'application/json';
            }
            opts.credentials = 'include';
            const token = localStorage.getItem('auth_token');
            if (token) {
                opts.headers['Authorization'] = `Bearer ${token}`;
            }
            const res = await fetch(url, opts);
            if (res.status === 401 && this.isAuthenticated) {
                console.warn('Sesión expirada o no autorizada (401). Cerrando sesión...');
                this.logout();
            }
            return res;
        }
    };
}
