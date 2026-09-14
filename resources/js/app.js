import './bootstrap';
import { createApp } from 'vue';
import PatientApp from './Components/Patient/PatientApp.vue';
import { initSyncManager } from './offline/syncManager';

const app = createApp(PatientApp);
const mountEl = document.getElementById('patient-app');

if (mountEl) {
    app.mount('#patient-app');
}

// Initialize Offline Outbox & Network Event Listeners
initSyncManager();

// Register Progressive Web App (PWA) Service Worker
if (typeof window !== 'undefined' && 'serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' })
            .then((registration) => {
                console.log('[HMS ServiceWorker] Registered with scope:', registration.scope);
                registration.update().catch(() => {});
            })
            .catch((error) => {
                console.warn('[HMS ServiceWorker] Registration failed:', error);
            });
    });
}
