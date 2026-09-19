import axios from 'axios';
import { saveApiResponse, getApiResponse, addOutboxMutation } from './offline/offlineStorage';
import './Services/modalDialog';

window.axios = axios;

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

// Inject CSRF Token from meta tag
const tokenMeta = document.querySelector('meta[name="csrf-token"]');
if (tokenMeta) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = tokenMeta.getAttribute('content');
}

// Request Interceptor: Attach credentials and handle offline bypass
axios.interceptors.request.use(async (config) => {
    let token = sessionStorage.getItem('hms_auth_token') || localStorage.getItem('hms_auth_token');
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

    if (token) {
        if (config.headers && typeof config.headers.set === 'function') {
            if (!config.headers.has('Authorization')) {
                config.headers.set('Authorization', `Bearer ${token}`);
            }
        } else if (config.headers && !config.headers.Authorization) {
            config.headers.Authorization = `Bearer ${token}`;
        }
    }

    if (branchId) {
        if (config.headers && typeof config.headers.set === 'function') {
            if (!config.headers.has('X-Branch-ID')) {
                config.headers.set('X-Branch-ID', branchId);
            }
        } else if (config.headers && !config.headers['X-Branch-ID']) {
            config.headers['X-Branch-ID'] = branchId;
        }
    }

    // Offline interceptor: if navigator is explicitly offline, serve from cache or outbox
    if (typeof navigator !== 'undefined' && !navigator.onLine && !config.headers['X-HMS-Offline-Replay']) {
        const method = (config.method || 'get').toLowerCase();
        
        if (method === 'get') {
            const cached = await getApiResponse(config.url);
            if (cached) {
                config.adapter = () => Promise.resolve({
                    data: cached,
                    status: 200,
                    statusText: 'OK (Offline Cache)',
                    headers: {},
                    config,
                    fromOfflineCache: true,
                });
                return config;
            } else {
                // Graceful fallback for uncached offline GET
                config.adapter = () => Promise.resolve({
                    data: {
                        success: true,
                        data: [],
                        message: 'Offline: Data not cached yet.',
                        is_offline_fallback: true,
                    },
                    status: 200,
                    statusText: 'OK (Offline Fallback)',
                    headers: {},
                    config,
                    fromOfflineCache: true,
                });
                return config;
            }
        } else {
            // Outbox mutation
            let payload = config.data;
            if (typeof payload === 'string') {
                try { payload = JSON.parse(payload); } catch {}
            }

            // If registering a patient offline, populate local offline patient identity
            if (config.url.includes('/patients') && method === 'post' && payload) {
                if (!payload.first_name || !String(payload.first_name).trim()) {
                    payload.first_name = payload.registration_type === 'emergency' ? 'Trauma Unknown' : 'Walk-In Patient';
                }
                if (!payload.last_name || !String(payload.last_name).trim()) {
                    payload.last_name = payload.registration_type === 'emergency' ? 'Unknown' : 'Walk-In';
                }
                if (!payload.date_of_birth) {
                    payload.date_of_birth = '1995-01-01';
                    payload.is_dob_estimated = true;
                }
                if (!payload.gender) payload.gender = 'unknown';
                if (!payload.registration_type) payload.registration_type = 'walk_in';

                const tempId = payload.id || ('offline-' + (typeof crypto !== 'undefined' && crypto.randomUUID ? crypto.randomUUID() : Date.now().toString(36)));
                const tempMrn = payload.mrn || ('OFFLINE-' + Math.floor(100000 + Math.random() * 900000));
                const fullName = `${payload.first_name} ${payload.last_name}`.trim();

                payload.id = tempId;
                payload.mrn = tempMrn;
                payload.name = fullName;
                payload.full_name = fullName;
                payload.created_at = new Date().toISOString();
                payload.is_offline_queued = true;

                // Cache patient locally in api_cache so GET /api/v1/patients displays the patient!
                (async () => {
                    try {
                        const cached = await getApiResponse('/api/v1/patients');
                        if (cached) {
                            if (Array.isArray(cached.data)) {
                                cached.data.unshift(payload);
                            } else if (Array.isArray(cached)) {
                                cached.unshift(payload);
                            }
                            await saveApiResponse('/api/v1/patients', cached);
                        }
                        await saveApiResponse(`/api/v1/patients/${tempId}`, { success: true, data: payload });
                        await saveApiResponse(`/api/v1/clinical/patients/${tempId}/ehr-timeline`, {
                            success: true,
                            data: {
                                patient: payload,
                                summary: { active_problems_count: 0, pending_orders_count: 0, active_prescriptions_count: 0 },
                                timeline: []
                            }
                        });
                    } catch (err) {
                        console.warn('[HMS Offline] Failed to cache newly registered patient:', err);
                    }
                })();
            }

            const summary = generateSummary(config.url, method, payload);
            const mutation = await addOutboxMutation({
                url: config.url,
                method,
                data: payload,
                headers: config.headers,
                summary,
            });

            config.adapter = () => Promise.resolve({
                data: {
                    success: true,
                    message: 'Saved locally in offline queue. Will sync automatically once online.',
                    id: mutation.id,
                    data: payload,
                    is_offline_queued: true,
                },
                status: 200,
                statusText: 'OK (Offline Queued)',
                config,
            });
            return config;
        }
    }

    return config;
}, (error) => {
    return Promise.reject(error);
});

// Response Interceptor: Cache GETs & Catch Network Drops
axios.interceptors.response.use(
    (response) => {
        // Automatically cache successful GET API responses for offline access
        if (
            response.config?.method?.toLowerCase() === 'get' &&
            response.status === 200 &&
            response.data &&
            !response.fromOfflineCache
        ) {
            saveApiResponse(response.config.url, response.data);
        }
        return response;
    },
    async (error) => {
        const config = error.config;

        // 1. Session unauthorized (401)
        if (error.response && error.response.status === 401) {
            const url = config?.url || '';
            if (!url.includes('/api/v1/auth/login')) {
                console.warn('[HMS Auth] Session unauthorized (401). Clearing stale credentials.');
                sessionStorage.removeItem('hms_auth_token');
                localStorage.removeItem('hms_auth_token');
                localStorage.removeItem('hms_portal_session');
                delete axios.defaults.headers.common['Authorization'];
                delete axios.defaults.headers.common['X-Branch-ID'];
                window.dispatchEvent(new CustomEvent('hms:unauthorized'));
            }
            return Promise.reject(error);
        }

        // 2. Network Failure / Offline Fallback (when request attempted but dropped or 503 offline)
        const isOfflineNetworkError = !error.response || (error.response && error.response.status === 503 && error.response.data?.offline);
        if (isOfflineNetworkError && config && !config.headers?.['X-HMS-Offline-Replay']) {
            const method = (config.method || 'get').toLowerCase();

            if (method === 'get') {
                const cached = await getApiResponse(config.url);
                if (cached) {
                    console.log('[HMS Offline] Serving fallback cached data for:', config.url);
                    return Promise.resolve({
                        data: cached,
                        status: 200,
                        statusText: 'OK (Offline Fallback)',
                        headers: {},
                        config,
                        fromOfflineCache: true,
                    });
                } else {
                    return Promise.resolve({
                        data: {
                            success: true,
                            data: [],
                            message: 'Offline: Data not cached yet.',
                            is_offline_fallback: true,
                        },
                        status: 200,
                        statusText: 'OK (Offline Fallback)',
                        headers: {},
                        config,
                        fromOfflineCache: true,
                    });
                }
            } else {
                let payload = config.data;
                if (typeof payload === 'string') {
                    try { payload = JSON.parse(payload); } catch {}
                }

                if (config.url.includes('/patients') && method === 'post' && payload) {
                    const tempId = payload.id || ('offline-' + (typeof crypto !== 'undefined' && crypto.randomUUID ? crypto.randomUUID() : Date.now().toString(36)));
                    const tempMrn = payload.mrn || ('OFFLINE-' + Math.floor(100000 + Math.random() * 900000));
                    const fullName = `${payload.first_name || ''} ${payload.last_name || ''}`.trim() || 'Walk-In Patient';

                    payload.id = tempId;
                    payload.mrn = tempMrn;
                    payload.name = fullName;
                    payload.full_name = fullName;
                    payload.created_at = new Date().toISOString();
                    payload.is_offline_queued = true;

                    (async () => {
                        try {
                            const cached = await getApiResponse('/api/v1/patients');
                            if (cached) {
                                if (Array.isArray(cached.data)) {
                                    cached.data.unshift(payload);
                                } else if (Array.isArray(cached)) {
                                    cached.unshift(payload);
                                }
                                await saveApiResponse('/api/v1/patients', cached);
                            }
                            await saveApiResponse(`/api/v1/patients/${tempId}`, { success: true, data: payload });
                            await saveApiResponse(`/api/v1/clinical/patients/${tempId}/ehr-timeline`, {
                                success: true,
                                data: {
                                    patient: payload,
                                    summary: { active_problems_count: 0, pending_orders_count: 0, active_prescriptions_count: 0 },
                                    timeline: []
                                }
                            });
                        } catch {}
                    })();
                }

                const summary = generateSummary(config.url, method, payload);
                const mutation = await addOutboxMutation({
                    url: config.url,
                    method,
                    data: payload,
                    headers: config.headers,
                    summary,
                });

                console.log('[HMS Offline] Network dropped; mutation queued:', summary);

                return Promise.resolve({
                    data: {
                        success: true,
                        message: 'Saved locally in offline queue. Will sync automatically once online.',
                        id: mutation.id,
                        data: payload,
                        is_offline_queued: true,
                    },
                    status: 200,
                    statusText: 'OK (Offline Queued)',
                    config,
                });
            }
        }

        return Promise.reject(error);
    }
);

function generateSummary(url, method, data) {
    const upperMethod = method.toUpperCase();
    if (url.includes('/patients') && upperMethod === 'POST') {
        const name = `${data?.first_name || ''} ${data?.last_name || 'Walk-In Patient'}`.trim();
        return `Register Patient: ${name}`;
    }
    if (url.includes('/vitals') || url.includes('/nursing')) {
        return 'Record Patient Vitals / Medication Admin';
    }
    if (url.includes('/prescriptions')) {
        return 'E-Prescription Order';
    }
    if (url.includes('/diagnostic-orders') || url.includes('/lab')) {
        return 'Diagnostic Laboratory / Radiology Order';
    }
    if (url.includes('/queue')) {
        return 'OPD Queue Ticket';
    }
    return `${upperMethod} ${url}`;
}
