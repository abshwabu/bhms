<template>
  <div class="space-y-6">
    <!-- Header Banner -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200/80">
      <div>
        <div class="flex items-center gap-2">
          <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 text-blue-800 uppercase tracking-wide">Physician Workspace</span>
          <span class="text-xs text-slate-400 font-mono">{{ todayFormatted }}</span>
        </div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">Doctor & Clinical Dashboard</h1>
        <p class="text-sm text-slate-500 mt-0.5">Welcome back, <span class="font-semibold text-slate-700">{{ dashboardData.doctor?.name || 'Dr. Clinician' }}</span>. Overview of patients seen today and pending reviews.</p>
      </div>

      <div class="flex items-center gap-3">
        <button
          @click="fetchDashboard"
          class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold px-4 py-2.5 rounded-xl transition cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Refresh
        </button>
      </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
      <!-- Patients Seen Today -->
      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Patients Seen Today</div>
          <div class="text-2xl font-black text-slate-900 mt-0.5">
            {{ dashboardData.metrics?.patients_seen_today ?? 0 }}
            <span class="text-xs font-normal text-slate-400">/ {{ dashboardData.metrics?.scheduled_today ?? 0 }} scheduled</span>
          </div>
        </div>
      </div>

      <!-- Pending Chart Reviews -->
      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
          </svg>
        </div>
        <div>
          <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pending Charts</div>
          <div class="text-2xl font-black text-amber-600 mt-0.5">
            {{ dashboardData.metrics?.pending_chart_reviews ?? 0 }}
          </div>
        </div>
      </div>

      <!-- Pending Diagnostic Reviews -->
      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
        </div>
        <div>
          <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Lab/Rad Sign-Offs</div>
          <div class="text-2xl font-black text-purple-600 mt-0.5">
            {{ dashboardData.metrics?.pending_diagnostic_reviews ?? 0 }}
          </div>
        </div>
      </div>

      <!-- Active Prescriptions -->
      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
          </svg>
        </div>
        <div>
          <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Prescriptions Written</div>
          <div class="text-2xl font-black text-blue-600 mt-0.5">
            {{ dashboardData.metrics?.active_prescriptions_today ?? 0 }}
          </div>
        </div>
      </div>

      <!-- Quick Action: New Prescription -->
      <div class="bg-gradient-to-tr from-blue-600 to-indigo-600 p-5 rounded-2xl text-white shadow-sm flex flex-col justify-between">
        <div class="text-xs font-medium text-blue-100">Quick Workflow</div>
        <div class="flex items-center justify-between mt-2">
          <button
            @click="$emit('openPrescriptionWriter')"
            class="w-full bg-white text-blue-700 hover:bg-blue-50 text-xs font-bold py-2 px-3 rounded-xl transition cursor-pointer text-center"
          >
            + E-Prescribe
          </button>
        </div>
      </div>
    </div>

    <!-- Main Dashboard Two-Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Left 2 Cols: Today's Scheduled Patients Roster -->
      <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
          <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <h2 class="font-bold text-slate-800 text-base">Today's Patient Schedule & Queue</h2>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                {{ dashboardData.patient_roster?.length ?? 0 }} patients
              </span>
            </div>
            <div class="text-xs text-slate-400 font-medium">Click patient to open EHR</div>
          </div>

          <div v-if="isLoading" class="p-12 text-center text-slate-400">
            <div class="w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
            Loading doctor schedule...
          </div>

          <div v-else-if="!dashboardData.patient_roster || dashboardData.patient_roster.length === 0" class="p-12 text-center">
            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto text-slate-400 mb-3">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <p class="text-sm font-semibold text-slate-700">No scheduled patients on today's roster</p>
            <p class="text-xs text-slate-400 mt-1">Walk-in consultations or newly booked appointments will appear here in real-time.</p>
          </div>

          <div v-else class="divide-y divide-slate-100">
            <div
              v-for="patient in dashboardData.patient_roster"
              :key="patient.appointment_id"
              class="p-4 hover:bg-slate-50/80 transition flex items-center justify-between gap-4"
            >
              <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-700 flex flex-col items-center justify-center font-mono font-bold text-xs shrink-0">
                  <span>{{ formatTime(patient.start_time) }}</span>
                </div>
                <div class="min-w-0">
                  <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-900 text-sm truncate">{{ patient.patient_name }}</span>
                    <span class="font-mono text-[10px] bg-slate-100 px-1.5 py-0.5 rounded text-slate-600">{{ patient.mrn }}</span>
                    <span
                      v-if="patient.allergies_count > 0"
                      class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 flex items-center gap-1"
                      title="Documented Allergies"
                    >
                      ⚠️ {{ patient.allergies_count }} Allergies
                    </span>
                  </div>
                  <div class="text-xs text-slate-500 mt-0.5 flex items-center gap-2 truncate">
                    <span>{{ patient.gender }}, {{ calculateAge(patient.date_of_birth) }} yrs</span>
                    <span>•</span>
                    <span class="text-slate-600 italic">"{{ patient.reason_for_visit || 'Routine Consultation' }}"</span>
                  </div>
                </div>
              </div>

              <div class="flex items-center gap-2 shrink-0">
                <span
                  class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider"
                  :class="getStatusBadgeClass(patient.status)"
                >
                  {{ patient.status }}
                </span>

                <button
                  @click="openPatientClinicalFile(patient)"
                  class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition cursor-pointer flex items-center gap-1"
                >
                  <span>Chart EHR</span>
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Pending Diagnostic Results (Labs & Radiology) -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
          <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <h2 class="font-bold text-slate-800 text-base">Diagnostic Results Awaiting Review</h2>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-700">
                {{ dashboardData.pending_diagnostic_reviews?.length ?? 0 }} pending sign-off
              </span>
            </div>
          </div>

          <div v-if="!dashboardData.pending_diagnostic_reviews || dashboardData.pending_diagnostic_reviews.length === 0" class="p-8 text-center text-xs text-slate-400">
            All diagnostic test results and imaging reports are signed off.
          </div>

          <div v-else class="divide-y divide-slate-100">
            <div
              v-for="diag in dashboardData.pending_diagnostic_reviews"
              :key="diag.id"
              class="p-4 hover:bg-slate-50 transition flex items-center justify-between gap-4"
            >
              <div>
                <div class="flex items-center gap-2">
                  <span
                    class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                    :class="diag.order_type === 'lab' ? 'bg-indigo-100 text-indigo-700' : 'bg-amber-100 text-amber-700'"
                  >
                    {{ diag.order_type }}
                  </span>
                  <span class="font-bold text-slate-800 text-sm">{{ diag.test_or_modality }}</span>
                  <span v-if="diag.abnormal_flags" class="px-1.5 py-0.5 rounded bg-red-100 text-red-700 text-[10px] font-black animate-pulse">
                    ABNORMAL RESULT
                  </span>
                </div>
                <div class="text-xs text-slate-500 mt-1">
                  Patient: <span class="font-semibold text-slate-700">{{ diag.patient_name }}</span> • Ref: {{ diag.order_number }}
                </div>
                <div class="text-xs text-slate-600 mt-1 bg-slate-50 p-2 rounded-lg font-mono">
                  {{ diag.results_summary || 'No narrative findings recorded' }}
                </div>
              </div>

              <div class="shrink-0 flex items-center gap-2">
                <button
                  @click="signOffDiagnostic(diag)"
                  class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition cursor-pointer flex items-center gap-1"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  Sign Off
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right 1 Col: Pending Chart Reviews (Draft EHR notes) -->
      <div class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
          <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-bold text-slate-800 text-base">Pending Chart Reviews</h2>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
              {{ dashboardData.pending_chart_reviews?.length ?? 0 }} drafts
            </span>
          </div>

          <div v-if="!dashboardData.pending_chart_reviews || dashboardData.pending_chart_reviews.length === 0" class="p-8 text-center text-xs text-slate-400">
            No unfinalized charts. All medical notes are finalized and signed.
          </div>

          <div v-else class="divide-y divide-slate-100">
            <div
              v-for="chart in dashboardData.pending_chart_reviews"
              :key="chart.id"
              class="p-4 hover:bg-slate-50 transition space-y-2"
            >
              <div class="flex items-center justify-between">
                <span class="font-bold text-xs text-slate-900">{{ chart.patient_name }}</span>
                <span class="text-[10px] font-mono bg-amber-50 text-amber-700 border border-amber-200 px-1.5 py-0.5 rounded font-bold">
                  v{{ chart.version }} Draft
                </span>
              </div>
              <div class="text-xs text-slate-600 font-medium">{{ chart.title }}</div>
              <div class="text-[11px] text-slate-400 flex items-center justify-between pt-1">
                <span>{{ formatDate(chart.created_at) }}</span>
                <button
                  @click="finalizeChart(chart)"
                  class="text-blue-600 hover:text-blue-800 font-bold hover:underline cursor-pointer"
                >
                  Finalize & Sign &rarr;
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Access Diagnostic Preset Order -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5 space-y-3">
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Clinical Order Actions</h3>
          <div class="grid grid-cols-2 gap-2">
            <button
              @click="$emit('openOrderModal', 'lab')"
              class="p-3 rounded-xl border border-slate-200 hover:border-blue-400 hover:bg-blue-50/50 transition text-left cursor-pointer group"
            >
              <div class="text-blue-600 font-bold text-xs flex items-center gap-1 group-hover:translate-x-0.5 transition">
                <span>🧪 Lab Order</span>
              </div>
              <div class="text-[10px] text-slate-400 mt-1">CBC, BMP, LFT, HbA1c</div>
            </button>

            <button
              @click="$emit('openOrderModal', 'radiology')"
              class="p-3 rounded-xl border border-slate-200 hover:border-purple-400 hover:bg-purple-50/50 transition text-left cursor-pointer group"
            >
              <div class="text-purple-600 font-bold text-xs flex items-center gap-1 group-hover:translate-x-0.5 transition">
                <span>🩻 Radiology</span>
              </div>
              <div class="text-[10px] text-slate-400 mt-1">X-Ray, CT, MRI, US</div>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { showAlert } from '../../Services/modalDialog';

const props = defineProps({
  branchId: { type: String, required: true },
});

const emit = defineEmits(['openPatientEhr', 'openPrescriptionWriter', 'openOrderModal']);

const dashboardData = ref({});
const isLoading = ref(false);

const todayFormatted = computed(() => {
  return new Date().toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
});

function formatTime(timeStr) {
  if (!timeStr) return '--:--';
  return timeStr.slice(0, 5);
}

function formatDate(isoStr) {
  if (!isoStr) return '';
  return new Date(isoStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function calculateAge(dobStr) {
  if (!dobStr) return '??';
  const dob = new Date(dobStr);
  const diffMs = Date.now() - dob.getTime();
  const ageDate = new Date(diffMs);
  return Math.abs(ageDate.getUTCFullYear() - 1970);
}

function getStatusBadgeClass(status) {
  switch (status) {
    case 'completed': return 'bg-emerald-100 text-emerald-800';
    case 'in_consultation': return 'bg-blue-100 text-blue-800 animate-pulse';
    case 'checked_in': return 'bg-amber-100 text-amber-800';
    default: return 'bg-slate-100 text-slate-700';
  }
}

async function fetchDashboard() {
  isLoading.value = true;
  try {
    const res = await axios.get('/api/v1/clinical/doctor/dashboard', {
      headers: {
        'X-Branch-ID': props.branchId,
        'Accept': 'application/json',
      },
    });
    dashboardData.value = res.data.data || {};
  } catch (err) {
    console.error('Failed to fetch doctor dashboard', err);
  } finally {
    isLoading.value = false;
  }
}

function openPatientClinicalFile(patient) {
  emit('openPatientEhr', {
    id: patient.patient_id,
    name: patient.patient_name,
    mrn: patient.mrn,
    gender: patient.gender,
    date_of_birth: patient.date_of_birth,
  });
}

async function finalizeChart(chart) {
  try {
    await axios.post(`/api/v1/clinical/ehr/${chart.id}/finalize`, {}, {
      headers: { 'X-Branch-ID': props.branchId, 'Accept': 'application/json' },
    });
    fetchDashboard();
  } catch (err) {
    await showAlert(err.response?.data?.message || 'Failed to finalize chart.', { status: 'error' });
  }
}

async function signOffDiagnostic(diag) {
  try {
    const endpoint = diag.order_type === 'lab'
      ? `/api/v1/clinical/lab-orders/${diag.id}/review`
      : `/api/v1/clinical/radiology-orders/${diag.id}/review`;

    await axios.post(endpoint, { notes: 'Reviewed and noted normal/abnormal findings.' }, {
      headers: { 'X-Branch-ID': props.branchId, 'Accept': 'application/json' },
    });
    fetchDashboard();
  } catch (err) {
    await showAlert(err.response?.data?.message || 'Failed to sign off diagnostic report.', { status: 'error' });
  }
}

onMounted(() => {
  fetchDashboard();
});

defineExpose({ fetchDashboard });
</script>
