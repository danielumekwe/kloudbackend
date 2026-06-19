import Alpine from 'alpinejs';
import axios from 'axios';

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
// Alpine.js component: main layout (sidebar + dark mode)
// -----------------------------------------------------------------------
window.appLayout = function () {
    return {
        darkMode: document.documentElement.classList.contains('dark'),
        sidebarOpen: window.innerWidth >= 1024,

        init() {
            window.addEventListener('resize', () => {
                if (window.innerWidth >= 1024) {
                    this.sidebarOpen = true;
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
