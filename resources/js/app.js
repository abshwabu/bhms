import './bootstrap';
import { createApp } from 'vue';
import PatientApp from './Components/Patient/PatientApp.vue';

const app = createApp(PatientApp);
const mountEl = document.getElementById('patient-app');

if (mountEl) {
    app.mount('#patient-app');
}
