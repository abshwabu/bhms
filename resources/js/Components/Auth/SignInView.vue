<template>
  <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-between selection:bg-sky-500 selection:text-white">
    <!-- Top Navigation -->
    <header class="border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-md px-6 py-4 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-500 to-indigo-600 flex items-center justify-center font-black text-xl text-white shadow-lg shadow-sky-500/20">
          +
        </div>
        <div>
          <div class="font-extrabold text-base tracking-tight leading-tight text-white">Metro HMS</div>
          <div class="text-[11px] text-sky-400 font-mono">Hospital Operating System &bull; Portal Auth</div>
        </div>
      </div>

      <a
        href="/"
        class="text-xs font-semibold text-slate-400 hover:text-white transition flex items-center gap-1.5"
      >
        <span>&larr; Back to Public Website</span>
      </a>
    </header>

    <!-- Center Login Form Area -->
    <main class="flex-1 flex items-center justify-center p-4 sm:p-6 lg:p-8">
      <div class="max-w-4xl w-full grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
        <!-- Left: Sign In Form Box -->
        <div class="lg:col-span-5 bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl flex flex-col justify-between">
          <div>
            <div class="mb-6">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-sky-500/10 text-sky-400 border border-sky-500/20 uppercase tracking-wider mb-2">
                RBAC & Hospital Isolated
              </span>
              <h1 class="text-2xl font-black text-white tracking-tight">Staff Portal Sign In</h1>
              <p class="text-xs text-slate-400 mt-1">Authenticate to access clinical records, patient registry, or hospital operations.</p>
            </div>

            <!-- Error Banner -->
            <div
              v-if="errorMessage"
              class="mb-4 p-3.5 rounded-xl bg-rose-950/80 border border-rose-800 text-rose-200 text-xs flex items-start gap-2.5"
            >
              <svg class="w-4 h-4 text-rose-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>{{ errorMessage }}</span>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitLogin" class="space-y-4">
              <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">Work Email</label>
                <input
                  v-model="form.email"
                  type="email"
                  required
                  placeholder="doctor@hms.local"
                  class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-600 text-xs focus:ring-2 focus:ring-sky-500 focus:outline-none"
                />
              </div>

              <div>
                <div class="flex items-center justify-between mb-1">
                  <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider">Password</label>
                  <span class="text-[10px] text-slate-500 font-mono">Demo: password123</span>
                </div>
                <input
                  v-model="form.password"
                  type="password"
                  required
                  placeholder="••••••••"
                  class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-600 text-xs focus:ring-2 focus:ring-sky-500 focus:outline-none"
                />
              </div>

              <div class="flex items-center justify-between text-xs pt-1">
                <label class="flex items-center gap-2 text-slate-400 cursor-pointer">
                  <input type="checkbox" v-model="form.remember" class="rounded bg-slate-950 border-slate-800 text-sky-500 focus:ring-sky-500" />
                  <span>Remember Session</span>
                </label>
                <span class="text-[11px] text-sky-400">AES-256 TLS 1.3</span>
              </div>

              <button
                type="submit"
                :disabled="submitting"
                class="w-full py-3 bg-sky-500 hover:bg-sky-400 active:bg-sky-600 text-slate-950 font-bold rounded-xl text-xs transition flex items-center justify-center gap-2 shadow-lg shadow-sky-500/20 cursor-pointer disabled:opacity-50 mt-2"
              >
                <span v-if="submitting" class="inline-block w-4 h-4 border-2 border-slate-950 border-t-transparent rounded-full animate-spin"></span>
                <span>{{ submitting ? 'Authenticating...' : 'Sign In to Portal' }}</span>
              </button>
            </form>
          </div>

          <div class="mt-6 pt-4 border-t border-slate-800 text-[11px] text-slate-500 flex items-center justify-between">
            <span>HIPAA Safeguards Enforced</span>
            <span class="font-mono">v2.4.0</span>
          </div>
        </div>

        <!-- Right: One-Click Demo Role Switcher -->
        <div class="lg:col-span-7 bg-slate-900/60 border border-slate-800/80 rounded-3xl p-6 sm:p-8 flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="text-base font-black text-white">Instant Role-Based Personas (RBAC Demo)</h3>
                <p class="text-xs text-slate-400">Click any persona below to test hospital isolation, scoped permissions, and role-specific views:</p>
              </div>
              <span class="px-2.5 py-1 bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 text-[10px] font-mono font-bold rounded-lg uppercase">
                1-Click Sign In
              </span>
            </div>

            <!-- Demo Account Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4">
              <button
                v-for="acc in demoAccounts"
                :key="acc.role_key"
                @click="quickLogin(acc)"
                class="p-3.5 rounded-2xl bg-slate-950 border border-slate-800 hover:border-sky-500/50 hover:bg-slate-900/90 transition text-left cursor-pointer group flex flex-col justify-between space-y-2 relative overflow-hidden"
              >
                <div class="flex items-start justify-between gap-2">
                  <div class="flex items-center gap-2">
                    <span
                      class="w-2 h-2 rounded-full"
                      :class="{
                        'bg-indigo-400': acc.color === 'indigo',
                        'bg-cyan-400': acc.color === 'cyan',
                        'bg-blue-400': acc.color === 'blue',
                        'bg-emerald-400': acc.color === 'emerald',
                        'bg-teal-400': acc.color === 'teal',
                        'bg-amber-400': acc.color === 'amber',
                        'bg-purple-400': acc.color === 'purple',
                      }"
                    ></span>
                    <span class="font-bold text-xs text-white group-hover:text-sky-300 transition">{{ acc.role_label }}</span>
                  </div>
                  <span class="text-[9px] font-mono uppercase px-2 py-0.5 rounded bg-slate-800 text-slate-300">
                    {{ acc.tag }}
                  </span>
                </div>

                <div>
                  <div class="text-[11px] font-medium text-slate-300">{{ acc.name }}</div>
                  <div class="text-[10px] font-mono text-slate-500 truncate">{{ acc.email }}</div>
                </div>

                <div class="border-t border-slate-800/80 pt-1.5 flex items-center justify-between text-[10px] text-slate-400">
                  <span class="truncate">{{ acc.accessible_scopes[0] }}</span>
                  <span class="text-sky-400 font-bold group-hover:translate-x-0.5 transition">&rarr;</span>
                </div>
              </button>
            </div>
          </div>

          <div class="mt-6 pt-4 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
            <span class="text-[11px]">All demo accounts use password: <code class="text-slate-200 font-mono font-bold">password123</code></span>
            <span class="text-[11px] text-slate-500">Auto-provisions roles upon login</span>
          </div>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/60 py-4 px-6 text-center text-[11px] text-slate-500">
      &copy; 2026 Metro HMS Platform. Multi-Tenant Enterprise Hospital Operating System.
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const emit = defineEmits(['login-success']);

const form = ref({
  email: 'doctor@hms.local',
  password: 'password123',
  remember: true,
});

const submitting = ref(false);
const errorMessage = ref('');
const demoAccounts = ref([
  {
    role_key: 'hospital_admin',
    role_label: 'Hospital Admin',
    email: 'admin@hms.local',
    password: 'password123',
    name: 'Dr. Arthur Sterling',
    tag: 'Hospital Director',
    color: 'cyan',
    accessible_scopes: ['Multi-Branch Facilities', 'Compliance & HIPAA', 'Staff & Master Data', 'BI Reports'],
  },
  {
    role_key: 'doctor',
    role_label: 'Doctor / Clinician',
    email: 'doctor@hms.local',
    password: 'password123',
    name: 'Dr. Eleanor Vance, MD',
    tag: 'Clinical Medicine',
    color: 'blue',
    accessible_scopes: ['Doctor Dashboard', 'EHR Clinical History', 'SOAP Notes', 'Prescriptions & Lab Orders'],
  },
  {
    role_key: 'nurse',
    role_label: 'Inpatient Nurse',
    email: 'nurse@hms.local',
    password: 'password123',
    name: 'Sister Clara Oswald, RN',
    tag: 'Ward & ICU Care',
    color: 'emerald',
    accessible_scopes: ['Bed Map Visuals', 'Nursing Station (Vitals/Meds)', 'Patient Intake', 'Discharge Summaries'],
  },
  {
    role_key: 'pharmacist',
    role_label: 'Chief Pharmacist',
    email: 'pharmacist@hms.local',
    password: 'password123',
    name: 'Marcus Holloway, PharmD',
    tag: 'Pharmacy & FEFO',
    color: 'teal',
    accessible_scopes: ['Pharmacy Dispensing', 'Drug Batches & Expiry', 'Stock Reorder Alerts', 'Prescriptions'],
  },
  {
    role_key: 'billing_officer',
    role_label: 'Billing Officer',
    email: 'billing@hms.local',
    password: 'password123',
    name: 'Jennifer Blake',
    tag: 'Finance & Insurance',
    color: 'amber',
    accessible_scopes: ['Invoices & Payments', 'Insurance Claims', 'Approvals Queue', 'Revenue Analytics'],
  },
  {
    role_key: 'receptionist',
    role_label: 'Reception / Registrar',
    email: 'receptionist@hms.local',
    password: 'password123',
    name: 'Sarah Connor',
    tag: 'Front Desk & OPD',
    color: 'purple',
    accessible_scopes: ['Patient Registration', 'Doctor Booking Calendar', 'OPD Queue Tokens & TV Display'],
  },
]);

async function loadDemoAccounts() {
  try {
    const res = await fetch('/api/v1/auth/demo-accounts', {
      headers: { 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.data && json.data.length) {
      demoAccounts.value = json.data;
    }
  } catch (err) {
    console.warn('Using embedded demo account presets:', err);
  }
}

async function executeLogin(email, password) {
  errorMessage.value = '';
  submitting.value = true;

  try {
    const res = await fetch('/api/v1/auth/login', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({ email, password }),
    });

    const json = await res.json();

    if (!res.ok) {
      if (json.errors && json.errors.email) {
        errorMessage.value = json.errors.email[0];
      } else {
        errorMessage.value = json.message || 'Invalid email or password.';
      }
      return;
    }

    if (json.data) {
      // Store user session in localStorage
      localStorage.setItem('hms_auth_user', JSON.stringify(json.data.user));
      localStorage.setItem('hms_auth_organization', JSON.stringify(json.data.organization));
      localStorage.setItem('hms_auth_branch', JSON.stringify(json.data.default_branch));
      if (json.data.token) {
        localStorage.setItem('hms_auth_token', json.data.token);
      }

      emit('login-success', json.data);
    }
  } catch (err) {
    console.error('Sign in failed:', err);
    errorMessage.value = 'Failed to connect to authentication server. Please check your network.';
  } finally {
    submitting.value = false;
  }
}

function submitLogin() {
  executeLogin(form.value.email, form.value.password);
}

function quickLogin(acc) {
  form.value.email = acc.email;
  form.value.password = acc.password;
  executeLogin(acc.email, acc.password);
}

onMounted(() => {
  loadDemoAccounts();
});
</script>
