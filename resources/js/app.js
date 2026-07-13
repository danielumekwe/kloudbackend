import Alpine from 'alpinejs';
import axios from 'axios';
import { Chart, LineController, LineElement, PointElement, LinearScale, CategoryScale, Filler, Tooltip } from 'chart.js';

Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale, Filler, Tooltip);
window.Chart = Chart;

// -----------------------------------------------------------------------
// Axios global defaults
// -----------------------------------------------------------------------
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';

// Set CSRF token for all requests
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
if (csrfToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}

// -----------------------------------------------------------------------
// Alpine.js store: toast notifications
// -----------------------------------------------------------------------
document.addEventListener('alpine:init', () => {
    Alpine.store('toast', {
        items: [],

        add(message, type = 'success') {
            const id = Date.now() + Math.random();
            this.items.push({ id, message, type });
            setTimeout(() => {
                this.remove(id);
            }, 5000);
        },

        remove(id) {
            this.items = this.items.filter(t => t.id !== id);
        },
    });
});

// -----------------------------------------------------------------------
// Strong password generator (used by VPS/Quick Server order + password forms)
// -----------------------------------------------------------------------
window.generateStrongPassword = function (length = 16) {
    const lower = 'abcdefghijklmnopqrstuvwxyz';
    const upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const numbers = '0123456789';
    const symbols = '!@#$%^&*()-_=+';
    const all = lower + upper + numbers + symbols;

    const pick = (chars) => chars[Math.floor(Math.random() * chars.length)];

    const required = [pick(lower), pick(upper), pick(numbers), pick(symbols)];
    const rest = Array.from({ length: Math.max(0, length - required.length) }, () => pick(all));

    return required.concat(rest)
        .sort(() => Math.random() - 0.5)
        .join('');
};

// -----------------------------------------------------------------------
// Alpine.js component: main layout (sidebar + dark mode)
// -----------------------------------------------------------------------
window.appLayout = function () {
    return {
        darkMode: document.documentElement.classList.contains('dark'),
        isDesktop: window.innerWidth >= 1024,
        sidebarOpen: window.innerWidth >= 1024,

        init() {
            // Only flip sidebar state when actually crossing the lg breakpoint —
            // otherwise a manual toggle on mobile would get fought by the next
            // unrelated resize (e.g. a scrollbar appearing). Without this, shrinking
            // an already-loaded desktop window below 1024px left the sidebar stuck
            // in its desktop "open" state: `sidebarOpen` was only ever forced true on
            // the way back up past 1024px, never false on the way down.
            window.addEventListener('resize', () => {
                const desktopNow = window.innerWidth >= 1024;
                if (desktopNow !== this.isDesktop) {
                    this.isDesktop = desktopNow;
                    this.sidebarOpen = desktopNow;
                }
            });
        },

        toggleDark() {
            this.darkMode = !this.darkMode;
            if (this.darkMode) {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            }
        },
    };
};

// -----------------------------------------------------------------------
// Alpine.js component: server actions (AJAX power/reboot/etc)
// -----------------------------------------------------------------------
window.serverActions = function (serviceId) {
    return {
        loading: null,
        showPasswordModal: false,
        newPassword: '',

        async perform(command, extra = {}) {
            this.loading = command;
            try {
                const res = await window.axios.post(`/servers/${serviceId}/action`, {
                    command,
                    ...extra,
                });
                Alpine.store('toast').add(res.data.message, 'success');
            } catch (err) {
                const msg = err.response?.data?.message ?? 'Action failed. Please try again.';
                Alpine.store('toast').add(msg, 'error');
            } finally {
                this.loading = null;
            }
        },

        async changePassword() {
            if (!this.newPassword || this.newPassword.length < 8) {
                Alpine.store('toast').add('Password must be at least 8 characters.', 'error');
                return;
            }
            await this.perform('changepassword', { password: this.newPassword });
            this.showPasswordModal = false;
            this.newPassword = '';
        },
    };
};

// -----------------------------------------------------------------------
// Start Alpine
// -----------------------------------------------------------------------
window.Alpine = Alpine;
Alpine.start();
