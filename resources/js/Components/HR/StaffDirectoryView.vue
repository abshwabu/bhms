<template>
  <div class="space-y-6">
    <!-- Header & Credential Alert Banner -->
    <div v-if="alertSummary && alertSummary.total_alerts_count > 0" class="p-4 rounded-2xl border bg-amber-50/80 border-amber-200 text-amber-900 shadow-sm">
      <div class="flex items-start justify-between gap-4">
        <div class="flex items-start gap-3">
          <div class="p-2 rounded-xl bg-amber-200/60 text-amber-800 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <div>
            <h4 class="font-bold text-sm text-amber-950 flex items-center gap-2">
              <span>HR Compliance Alert: {{ alertSummary.total_alerts_count }} Medical Credential(s) Require Attention</span>
              <span v-if="alertSummary.expired_count > 0" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-600 text-white animate-pulse">
                {{ alertSummary.expired_count }} Expired
              </span>
              <span v-if="alertSummary.expiring_soon_count > 0" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500 text-white">
                {{ alertSummary.expiring_soon_count }} Expiring Soon (30d)
              </span>
            </h4>
            <p class="text-xs text-amber-800 mt-1">
              Physicians and nurses with expired or near-expiry licenses must submit renewal documentation before roster allocation.
            </p>
            <!-- Detailed Alert Chips -->
            <div class="mt-3 flex flex-wrap gap-2">
              <div
                v-for="exp in alertSummary.expired_credentials"
                :key="exp.id"
                class="px-2.5 py-1 rounded-lg bg-rose-100 text-rose-800 border border-rose-200 text-[11px] font-medium flex items-center gap-1.5"
              >
                <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                <strong>{{ exp.staff?.full_name }}:</strong> {{ exp.title }} (Expired {{ exp.expiry_date }})
              </div>
              <div
                v-for="soon in alertSummary.expiring_credentials"
                :key="soon.id"
                class="px-2.5 py-1 rounded-lg bg-amber-100 text-amber-800 border border-amber-200 text-[11px] font-medium flex items-center gap-1.5"
              >
                <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                <strong>{{ soon.staff?.full_name }}:</strong> {{ soon.title }} (Expires in {{ soon.days_until_expiry }}d)
              </div>
            </div>
          </div>
        </div>
        <button
          @click="fetchAlerts"
          class="text-xs font-semibold text-amber-800 hover:text-amber-950 underline shrink-0 cursor-pointer"
        >
          Refresh Alerts
        </button>
      </div>
    </div>

    <!-- Actions & Filter Toolbar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
      <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
        <!-- Search -->
        <div class="relative w-full sm:w-64">
          <input
            v-model="searchQuery"
            @input="debounceSearch"
            type="text"
            placeholder="Search staff, ID, email..."
            class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
          />
          <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>

        <!-- Department Filter -->
        <select
          v-model="departmentFilter"
          @change="fetchStaff"
          class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-700 focus:ring-2 focus:ring-blue-500 focus:outline-none cursor-pointer"
        >
          <option value="">All Departments</option>
          <option value="Internal Medicine">Internal Medicine</option>
          <option value="Nursing">Nursing</option>
          <option value="Radiology">Radiology</option>
          <option value="Pharmacy">Pharmacy</option>
          <option value="Executive & Clinical Leadership">Executive Leadership</option>
          <option value="Human Resources">Human Resources</option>
        </select>

        <!-- Status Filter -->
        <select
          v-model="statusFilter"
          @change="fetchStaff"
          class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-700 focus:ring-2 focus:ring-blue-500 focus:outline-none cursor-pointer"
        >
          <option value="">All Statuses</option>
          <option value="active">Active</option>
          <option value="on_leave">On Leave</option>
          <option value="suspended">Suspended</option>
          <option value="resigned">Resigned</option>
        </select>
      </div>

      <div class="flex items-center gap-2 w-full md:w-auto justify-end">
        <button
          @click="openNewStaffModal"
          class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-sm cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span>Onboard New Staff</span>
        </button>
      </div>
    </div>

    <!-- Staff Directory Grid -->
    <div v-if="loading" class="p-12 text-center text-slate-400">
      <div class="inline-block animate-spin w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full mb-2"></div>
      <p class="text-xs">Loading staff profiles...</p>
    </div>

    <div v-else-if="staffList.length === 0" class="bg-white p-12 rounded-2xl border border-slate-200 text-center text-slate-500">
      <p class="font-medium text-sm">No staff members found matching criteria.</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div
        v-for="staff in staffList"
        :key="staff.id"
        class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition p-5 flex flex-col justify-between"
      >
        <div>
          <!-- Header: Name, Employee ID & Medical Badge -->
          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="flex items-center gap-2">
                <span class="font-bold text-slate-900 text-base leading-snug">{{ staff.full_name }}</span>
                <span
                  v-if="staff.role?.is_medical"
                  class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-teal-50 text-teal-700 border border-teal-200"
                >
                  Clinical
                </span>
                <span
                  v-else
                  class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200"
                >
                  Admin
                </span>
              </div>
              <div class="text-xs text-blue-600 font-semibold mt-0.5">{{ staff.designation }}</div>
              <div class="text-[11px] font-mono text-slate-400 mt-0.5">{{ staff.employee_id }} &bull; {{ staff.department }}</div>
            </div>
            <span
              :class="staff.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200'"
              class="px-2 py-0.5 rounded-full text-[10px] font-bold border uppercase"
            >
              {{ staff.status }}
            </span>
          </div>

          <!-- Contact Details -->
          <div class="mt-4 space-y-1 text-xs text-slate-600">
            <div class="flex items-center gap-2">
              <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
              <span class="truncate">{{ staff.email }}</span>
            </div>
            <div class="flex items-center gap-2">
              <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
              </svg>
              <span>{{ staff.phone || 'No phone recorded' }}</span>
            </div>
          </div>

          <!-- Payroll Rates & Joining Date -->
          <div class="mt-3.5 pt-3.5 border-t border-slate-100 grid grid-cols-2 gap-2 text-[11px]">
            <div>
              <span class="text-slate-400 block font-medium">Hourly / Base</span>
              <span class="font-bold text-slate-700">${{ (staff.hourly_rate_cents / 100).toFixed(2) }}/hr</span>
            </div>
            <div>
              <span class="text-slate-400 block font-medium">Monthly Comp</span>
              <span class="font-bold text-slate-700">${{ (staff.monthly_salary_cents / 100).toLocaleString() }}</span>
            </div>
          </div>

          <!-- License Expiry Indicators -->
          <div v-if="staff.has_expiring_credentials" class="mt-3 p-2 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-[11px] flex items-center gap-2">
            <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span class="font-semibold">Contains license nearing expiration or expired!</span>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between gap-2">
          <button
            @click="openCredentialModal(staff)"
            class="text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1 cursor-pointer"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>Credentials ({{ staff.credentials?.length || 0 }})</span>
          </button>

          <button
            @click="openPerformanceModal(staff)"
            class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition cursor-pointer"
          >
            Performance & KPIs
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Add Credential -->
    <div v-if="isCredentialModalOpen && activeStaffForCredentials" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between pb-4 border-b border-slate-100">
          <div>
            <h3 class="font-bold text-base text-slate-900">Professional Credentials & Licenses</h3>
            <p class="text-xs text-slate-500 mt-0.5">{{ activeStaffForCredentials.full_name }} ({{ activeStaffForCredentials.employee_id }})</p>
          </div>
          <button @click="isCredentialModalOpen = false" class="p-1 text-slate-400 hover:text-slate-600 cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Existing Credentials List -->
        <div class="mt-4 space-y-3">
          <div class="text-xs font-bold text-slate-800">Current Licenses & Certifications</div>
          <div v-if="!activeStaffForCredentials.credentials || activeStaffForCredentials.credentials.length === 0" class="p-3 bg-slate-50 rounded-xl text-center text-xs text-slate-400">
            No credentials logged yet for this employee.
          </div>
          <div
            v-for="c in activeStaffForCredentials.credentials"
            :key="c.id"
            class="p-3 rounded-xl border flex items-start justify-between gap-3 text-xs"
            :class="c.is_expired ? 'bg-rose-50/70 border-rose-200 text-rose-900' : (c.is_expiring_soon ? 'bg-amber-50/70 border-amber-200 text-amber-900' : 'bg-slate-50 border-slate-200 text-slate-700')"
          >
            <div>
              <div class="font-bold">{{ c.title }}</div>
              <div class="text-[11px] font-mono mt-0.5">License #: {{ c.license_number }} &bull; {{ c.issuing_authority }}</div>
              <div class="text-[11px] text-slate-500 mt-1">
                Valid: {{ c.issue_date }} &rarr; <strong>{{ c.expiry_date }}</strong>
                <span v-if="c.is_expired" class="text-rose-600 font-bold ml-1">(EXPIRED)</span>
                <span v-else-if="c.is_expiring_soon" class="text-amber-600 font-bold ml-1">(Expires in {{ c.days_until_expiry }} days)</span>
              </div>
            </div>
            <span
              :class="c.is_expired ? 'bg-rose-600 text-white' : (c.is_expiring_soon ? 'bg-amber-500 text-white' : 'bg-emerald-600 text-white')"
              class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase shrink-0"
            >
              {{ c.verification_status }}
            </span>
          </div>
        </div>

        <!-- Add New Credential Form -->
        <form @submit.prevent="submitCredential" class="mt-6 pt-4 border-t border-slate-100 space-y-3">
          <div class="text-xs font-bold text-slate-800">Log New Credential / License Renewal</div>
          
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-semibold text-slate-600 mb-1">Credential Type</label>
              <select v-model="credentialForm.credential_type" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                <option value="medical_license">Medical License (MD/DO)</option>
                <option value="board_certification">Board Certification</option>
                <option value="nursing_council">Nursing Council Registration</option>
                <option value="dea_registration">DEA Controlled Substance</option>
                <option value="certification">BLS / ACLS / ATLS Certification</option>
              </select>
            </div>
            <div>
              <label class="block text-[11px] font-semibold text-slate-600 mb-1">License / Reg Number</label>
              <input v-model="credentialForm.license_number" type="text" placeholder="e.g. MD-992384" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none" required />
            </div>
          </div>

          <div>
            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Credential Title</label>
            <input v-model="credentialForm.title" type="text" placeholder="e.g. State Medical Board Physician License" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none" required />
          </div>

          <div>
            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Issuing Authority</label>
            <input v-model="credentialForm.issuing_authority" type="text" placeholder="e.g. State Medical Licensing Board" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none" required />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-semibold text-slate-600 mb-1">Issue Date</label>
              <input v-model="credentialForm.issue_date" type="date" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none" required />
            </div>
            <div>
              <label class="block text-[11px] font-semibold text-slate-600 mb-1">Expiry Date (HR Alert)</label>
              <input v-model="credentialForm.expiry_date" type="date" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none" required />
            </div>
          </div>

          <div class="pt-2 flex justify-end gap-2">
            <button type="button" @click="isCredentialModalOpen = false" class="px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 cursor-pointer">Cancel</button>
            <button type="submit" :disabled="savingCredential" class="px-4 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white cursor-pointer disabled:opacity-50">
              {{ savingCredential ? 'Saving...' : 'Record Credential' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Performance & KPIs -->
    <div v-if="isPerformanceModalOpen && activePerformance" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-start justify-between pb-4 border-b border-slate-100">
          <div>
            <h3 class="font-bold text-base text-slate-900">Staff Performance & KPIs</h3>
            <p class="text-xs text-slate-500 mt-0.5">{{ activePerformance.staff_name }} &bull; {{ activePerformance.designation }}</p>
          </div>
          <button @click="isPerformanceModalOpen = false" class="p-1 text-slate-400 hover:text-slate-600 cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="mt-4 space-y-4 text-xs">
          <div class="grid grid-cols-2 gap-3">
            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
              <span class="text-slate-400 block text-[11px]">Punctuality Rate</span>
              <span class="text-xl font-extrabold text-emerald-600">{{ activePerformance.punctuality_rate }}%</span>
              <span class="text-[10px] text-slate-400 block mt-0.5">{{ activePerformance.punctual_days }} on-time / {{ activePerformance.total_days_logged }} shifts</span>
            </div>
            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
              <span class="text-slate-400 block text-[11px]">Patients Consulted</span>
              <span class="text-xl font-extrabold text-blue-600">{{ activePerformance.patients_seen_count }}</span>
              <span class="text-[10px] text-slate-400 block mt-0.5">Clinical encounters</span>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
              <span class="text-slate-400 block text-[11px]">Hours Worked</span>
              <span class="text-base font-bold text-slate-800">{{ activePerformance.total_hours_worked }} hrs</span>
            </div>
            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
              <span class="text-slate-400 block text-[11px]">Total Lateness</span>
              <span class="text-base font-bold text-rose-600">{{ activePerformance.total_minutes_late }} mins</span>
            </div>
          </div>

          <!-- Payroll Calculation Preview -->
          <div class="p-4 rounded-2xl bg-indigo-50/70 border border-indigo-100 text-indigo-950">
            <h4 class="font-bold text-xs text-indigo-900 mb-1">Estimated Payroll Accrual</h4>
            <div class="flex items-baseline justify-between">
              <span class="text-xs text-indigo-700">Gross Hours Basis:</span>
              <span class="text-lg font-black text-indigo-900">${{ activePerformance.payroll_preview?.calculated_gross_hourly || '0.00' }}</span>
            </div>
            <div class="flex items-baseline justify-between mt-1 pt-1 border-t border-indigo-200/50 text-[11px] text-indigo-800">
              <span>Standard Monthly Base:</span>
              <span>${{ activePerformance.payroll_preview?.monthly_base_salary || '0.00' }}</span>
            </div>
          </div>
        </div>

        <div class="mt-6 flex justify-end">
          <button @click="isPerformanceModalOpen = false" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold cursor-pointer">
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
  branchId: {
    type: String,
    default: null,
  },
});

const staffList = ref([]);
const loading = ref(false);
const searchQuery = ref('');
const departmentFilter = ref('');
const statusFilter = ref('');
const alertSummary = ref(null);

// Modal states
const isCredentialModalOpen = ref(false);
const activeStaffForCredentials = ref(null);
const savingCredential = ref(false);

const credentialForm = ref({
  credential_type: 'medical_license',
  license_number: '',
  title: '',
  issuing_authority: '',
  issue_date: '',
  expiry_date: '',
});

const isPerformanceModalOpen = ref(false);
const activePerformance = ref(null);

let debounceTimer = null;
const debounceSearch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    fetchStaff();
  }, 300);
};

const fetchStaff = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (props.branchId) params.append('branch_id', props.branchId);
    if (searchQuery.value) params.append('search', searchQuery.value);
    if (departmentFilter.value) params.append('department', departmentFilter.value);
    if (statusFilter.value) params.append('status', statusFilter.value);

    const res = await fetch(`/api/v1/hr/staff?${params.toString()}`, {
      headers: {
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
    });
    const json = await res.json();
    if (json.success) {
      staffList.value = json.data?.data || json.data || [];
    }
  } catch (err) {
    console.error('Failed to load staff directory', err);
  } finally {
    loading.value = false;
  }
};

const fetchAlerts = async () => {
  try {
    const params = new URLSearchParams();
    if (props.branchId) params.append('branch_id', props.branchId);
    params.append('upcoming_days', '30');

    const res = await fetch(`/api/v1/hr/credentials/alerts?${params.toString()}`, {
      headers: {
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
    });
    const json = await res.json();
    if (json.success) {
      alertSummary.value = json.data;
    }
  } catch (err) {
    console.error('Failed to load credential alerts', err);
  }
};

const openCredentialModal = (staff) => {
  activeStaffForCredentials.value = staff;
  credentialForm.value = {
    credential_type: 'medical_license',
    license_number: '',
    title: '',
    issuing_authority: '',
    issue_date: new Date().toISOString().split('T')[0],
    expiry_date: '',
  };
  isCredentialModalOpen.value = true;
};

const submitCredential = async () => {
  if (!activeStaffForCredentials.value) return;
  savingCredential.value = true;
  try {
    const res = await fetch(`/api/v1/hr/staff/${activeStaffForCredentials.value.id}/credentials`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
      body: JSON.stringify(credentialForm.value),
    });
    const json = await res.json();
    if (json.success) {
      isCredentialModalOpen.value = false;
      await fetchStaff();
      await fetchAlerts();
    } else {
      alert(json.message || 'Error recording credential');
    }
  } catch (err) {
    console.error('Failed to submit credential', err);
  } finally {
    savingCredential.value = false;
  }
};

const openPerformanceModal = async (staff) => {
  try {
    const res = await fetch(`/api/v1/hr/staff/${staff.id}/performance`, {
      headers: {
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
    });
    const json = await res.json();
    if (json.success) {
      activePerformance.value = json.data;
      isPerformanceModalOpen.value = true;
    }
  } catch (err) {
    console.error('Failed to load performance metrics', err);
  }
};

onMounted(() => {
  fetchStaff();
  fetchAlerts();
});
</script>
