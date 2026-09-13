import axios from 'axios';

window.axios = axios;

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

// Inject CSRF Token from meta tag
const tokenMeta = document.querySelector('meta[name="csrf-token"]');
if (tokenMeta) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = tokenMeta.getAttribute('content');
}

// Request Interceptor: Automatically attach Sanctum Bearer Token and Branch ID
axios.interceptors.request.use((config) => {
    let token = sessionStorage.getItem('hms_auth_token');
    let branchId = null;

    try {
        const sessionRaw = localStorage.getItem('hms_portal_session');
        if (sessionRaw) {
            const parsed = JSON.parse(sessionRaw);
            if (!token && parsed?.token) {
                token = parsed.token;
                sessionStorage.setItem('hms_auth_token', token);
            }
            if (parsed?.default_branch?.id) {
                branchId = parsed.default_branch.id;
            }
        }
    } catch {
        // ignore parse error
    }

    if (token && !config.headers.Authorization) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    if (branchId && !config.headers['X-Branch-ID']) {
        config.headers['X-Branch-ID'] = branchId;
    }

    return config;
}, (error) => {
    return Promise.reject(error);
});

// Response Interceptor: Automatically handle 401 Unauthorized
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response && error.response.status === 401) {
            const url = error.config?.url || '';
            // Ignore 401 on login endpoint (wrong password)
            if (!url.includes('/api/v1/auth/login')) {
                console.warn('[HMS Auth] Session unauthorized (401). Clearing stale credentials.');
                sessionStorage.removeItem('hms_auth_token');
                localStorage.removeItem('hms_portal_session');
                delete axios.defaults.headers.common['Authorization'];
                window.dispatchEvent(new CustomEvent('hms:unauthorized'));
            }
        }
        return Promise.reject(error);
    }
);
