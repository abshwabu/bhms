<template>
  <div class="space-y-6">
    <!-- Header & Controls -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h2 class="text-lg font-bold text-slate-900">Physician & Clinical Productivity Scorecard</h2>
        <p class="text-xs text-slate-500 mt-0.5">Consultation throughput, appointment completion rates, prescription counts, and revenue attribution</p>
      </div>

      <div class="flex flex-wrap items-center gap-2.5">
        <select
          v-model="selectedPreset"
          @change="fetchDoctors"
          class="px-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-white font-medium text-slate-700 cursor-pointer"
        >
          <option value="last_7_days">Last 7 Days</option>
          <option value="last_30_days">Last 30 Days</option>
          <option value="this_month">This Month</option>
          <option value="last_month">Last Month</option>
          <option value="this_year">This Year</option>
        </select>

        <input
          type="text"
          v-model="searchQuery"
          placeholder="Filter doctor or specialty..."
          class="px-3 py-1.5 text-xs rounded-xl border border-slate-200 text-slate-700 placeholder-slate-400"
        />

        <button
          @click="exportCsv"
          class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-sm"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          Export CSV
        </button>
      </div>
    </div>

    <!-- Table Content -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div v-if="loading" class="p-16 text-center text-slate-400">
        <svg class="w-8 h-8 mx-auto animate-spin text-blue-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        Loading physician performance metrics...
      </div>

      <div v-else-if="filteredDoctors.length === 0" class="p-12 text-center text-slate-400">
        No physician records match the current filter.
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50 text-slate-700 uppercase font-mono text-[11px] border-b border-slate-200">
            <tr>
              <th class="py-3.5 px-4 font-bold">Doctor / Specialist</th>
              <th class="py-3.5 px-4 text-center font-bold">Patients Seen</th>
              <th class="py-3.5 px-4 text-center font-bold">Appointments (Comp / Sched)</th>
              <th class="py-3.5 px-4 text-center font-bold">Completion Rate</th>
              <th class="py-3.5 px-4 text-center font-bold">Prescriptions</th>
              <th class="py-3.5 px-4 text-center font-bold">Diagnostics Ordered</th>
              <th class="py-3.5 px-4 text-center font-bold">Avg Duration</th>
              <th class="py-3.5 px-4 text-right font-bold">Attributed Revenue</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="doc in filteredDoctors"
              :key="doc.doctor_id"
              class="hover:bg-slate-50/80 transition"
            >
              <td class="py-3.5 px-4">
                <div class="font-bold text-slate-900">{{ doc.doctor_name }}</div>
                <div class="text-[10px] text-slate-500">{{ doc.specialty }} &bull; {{ doc.department }}</div>
              </td>
              <td class="py-3.5 px-4 text-center font-semibold text-slate-800">
                {{ formatNumber(doc.patients_seen, 0) }}
              </td>
              <td class="py-3.5 px-4 text-center">
                <span class="text-emerald-600 font-semibold">{{ doc.appointments_completed }}</span>
                <span class="text-slate-300 mx-1">/</span>
                <span class="text-slate-500">{{ doc.appointments_scheduled }}</span>
              </td>
              <td class="py-3.5 px-4 text-center">
                <span
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                  :class="doc.completion_rate_percentage >= 80 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                >
                  {{ doc.completion_rate_percentage }}%
                </span>
              </td>
              <td class="py-3.5 px-4 text-center font-mono">
                {{ doc.prescriptions_written }}
              </td>
              <td class="py-3.5 px-4 text-center">
                <span class="px-1.5 py-0.5 rounded bg-purple-50 text-purple-700 font-semibold text-[10px]">
                  {{ doc.lab_orders_placed }} Lab &bull; {{ doc.radiology_orders_placed }} Rad
                </span>
              </td>
              <td class="py-3.5 px-4 text-center font-mono text-slate-500">
                {{ doc.average_consultation_minutes }}m
              </td>
              <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900">
                ${{ formatNumber(doc.total_revenue_generated, 2) }}
              </td>
            </tr>
          </tbody>
          <tfoot class="bg-slate-50 font-bold border-t border-slate-200 text-slate-900">
            <tr>
              <td class="py-3 px-4">Total Rollup ({{ filteredDoctors.length }} Doctors)</td>
              <td class="py-3 px-4 text-center">{{ totalPatientsSeenRollup }}</td>
              <td class="py-3 px-4 text-center">-</td>
              <td class="py-3 px-4 text-center">-</td>
              <td class="py-3 px-4 text-center">{{ totalPrescriptionsRollup }}</td>
              <td class="py-3 px-4 text-center">-</td>
              <td class="py-3 px-4 text-center">-</td>
              <td class="py-3 px-4 text-right font-mono text-emerald-600">${{ formatNumber(totalRevenueRollup, 2) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
  branchId: {
    type: String,
    default: null,
  },
});

const loading = ref(false);
const selectedPreset = ref('last_30_days');
const searchQuery = ref('');
const doctors = ref([]);

const filteredDoctors = computed(() => {
  if (!searchQuery.value.trim()) return doctors.value;
  const q = searchQuery.value.toLowerCase();
  return doctors.value.filter(d =>
    d.doctor_name.toLowerCase().includes(q) ||
    d.specialty.toLowerCase().includes(q) ||
    d.department.toLowerCase().includes(q)
  );
});

const totalPatientsSeenRollup = computed(() => {
  return filteredDoctors.value.reduce((acc, d) => acc + (d.patients_seen || 0), 0);
});

const totalPrescriptionsRollup = computed(() => {
  return filteredDoctors.value.reduce((acc, d) => acc + (d.prescriptions_written || 0), 0);
});

const totalRevenueRollup = computed(() => {
  return filteredDoctors.value.reduce((acc, d) => acc + (d.total_revenue_generated || 0), 0);
});

function formatNumber(val, decimals = 2) {
  return Number(val || 0).toLocaleString(undefined, {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  });
}

async function fetchDoctors() {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (props.branchId) params.append('branch_id', props.branchId);
    params.append('preset', selectedPreset.value);

    const res = await fetch(`/api/v1/reports/doctors?${params.toString()}`, {
      headers: {
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
    });

    const json = await res.json();
    if (json.success && json.data) {
      doctors.value = json.data.doctors || [];
    }
  } catch (err) {
    console.error('Failed to load doctor performance report:', err);
  } finally {
    loading.value = false;
  }
}

function exportCsv() {
  const params = {
    report_type: 'doctors',
    branch_id: props.branchId,
    preset: selectedPreset.value,
  };

  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '/api/v1/reports/export/standard';
  form.target = '_blank';

  for (const [key, value] of Object.entries(params)) {
    if (value) {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = key;
      input.value = value;
      form.appendChild(input);
    }
  }

  document.body.appendChild(form);
  form.submit();
  document.body.removeChild(form);
}

onMounted(() => {
  fetchDoctors();
});
</script>
