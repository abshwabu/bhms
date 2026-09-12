<template>
  <div class="space-y-6">
    <!-- Header & Controls -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h2 class="text-lg font-bold text-slate-900">Department-Wise Clinical & Financial Analytics</h2>
        <p class="text-xs text-slate-500 mt-0.5">Patient encounters, admission ALOS, bed utilization, and revenue generation per service line</p>
      </div>

      <div class="flex flex-wrap items-center gap-2.5">
        <select
          v-model="selectedPreset"
          @change="fetchDepartments"
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
          placeholder="Filter department name..."
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
        Loading department metrics...
      </div>

      <div v-else-if="filteredDepartments.length === 0" class="p-12 text-center text-slate-400">
        No department records match the current filter.
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50 text-slate-700 uppercase font-mono text-[11px] border-b border-slate-200">
            <tr>
              <th class="py-3.5 px-4 font-bold">Department</th>
              <th class="py-3.5 px-4 text-center font-bold">Total Patients</th>
              <th class="py-3.5 px-4 text-center font-bold">OPD Consults</th>
              <th class="py-3.5 px-4 text-center font-bold">Admissions / Discharges</th>
              <th class="py-3.5 px-4 text-center font-bold">Occupancy Rate</th>
              <th class="py-3.5 px-4 text-center font-bold">ALOS (Days)</th>
              <th class="py-3.5 px-4 text-center font-bold">Diagnostic Orders</th>
              <th class="py-3.5 px-4 text-right font-bold">Revenue</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="dept in filteredDepartments"
              :key="dept.department_name"
              class="hover:bg-slate-50/80 transition"
            >
              <td class="py-3.5 px-4">
                <div class="font-bold text-slate-900 capitalize">{{ dept.department_name }}</div>
                <div class="text-[10px] text-slate-400">Beds: {{ dept.occupied_beds }} / {{ dept.total_beds }}</div>
              </td>
              <td class="py-3.5 px-4 text-center font-semibold text-slate-800">
                {{ formatNumber(dept.total_patients, 0) }}
              </td>
              <td class="py-3.5 px-4 text-center">
                {{ dept.opd_consultations }}
              </td>
              <td class="py-3.5 px-4 text-center">
                <span class="text-indigo-600 font-semibold">{{ dept.admissions }} in</span>
                <span class="text-slate-300 mx-1">/</span>
                <span class="text-slate-500">{{ dept.discharges }} out</span>
              </td>
              <td class="py-3.5 px-4 text-center">
                <span
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                  :class="dept.average_occupancy_rate > 80 ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-700'"
                >
                  {{ dept.average_occupancy_rate }}%
                </span>
              </td>
              <td class="py-3.5 px-4 text-center font-mono">
                {{ dept.average_alos_days }}d
              </td>
              <td class="py-3.5 px-4 text-center">
                <span class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 font-semibold text-[10px]">
                  {{ dept.lab_orders }} Lab &bull; {{ dept.radiology_orders }} Rad
                </span>
              </td>
              <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900">
                ${{ formatNumber(dept.total_revenue, 2) }}
              </td>
            </tr>
          </tbody>
          <tfoot class="bg-slate-50 font-bold border-t border-slate-200 text-slate-900">
            <tr>
              <td class="py-3 px-4">Total Rollup ({{ filteredDepartments.length }} Depts)</td>
              <td class="py-3 px-4 text-center">{{ totalPatientsRollup }}</td>
              <td class="py-3 px-4 text-center">{{ totalOpdRollup }}</td>
              <td class="py-3 px-4 text-center">{{ totalAdmissionsRollup }}</td>
              <td class="py-3 px-4 text-center">-</td>
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
const departments = ref([]);

const filteredDepartments = computed(() => {
  if (!searchQuery.value.trim()) return departments.value;
  const q = searchQuery.value.toLowerCase();
  return departments.value.filter(d => d.department_name.toLowerCase().includes(q));
});

const totalPatientsRollup = computed(() => {
  return filteredDepartments.value.reduce((acc, d) => acc + (d.total_patients || 0), 0);
});

const totalOpdRollup = computed(() => {
  return filteredDepartments.value.reduce((acc, d) => acc + (d.opd_consultations || 0), 0);
});

const totalAdmissionsRollup = computed(() => {
  return filteredDepartments.value.reduce((acc, d) => acc + (d.admissions || 0), 0);
});

const totalRevenueRollup = computed(() => {
  return filteredDepartments.value.reduce((acc, d) => acc + (d.total_revenue || 0), 0);
});

function formatNumber(val, decimals = 2) {
  return Number(val || 0).toLocaleString(undefined, {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  });
}

async function fetchDepartments() {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (props.branchId) params.append('branch_id', props.branchId);
    params.append('preset', selectedPreset.value);

    const res = await fetch(`/api/v1/reports/departments?${params.toString()}`, {
      headers: {
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
    });

    const json = await res.json();
    if (json.success && json.data) {
      departments.value = json.data.departments || [];
    }
  } catch (err) {
    console.error('Failed to load department report:', err);
  } finally {
    loading.value = false;
  }
}

function exportCsv() {
  const params = {
    report_type: 'departments',
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
  fetchDepartments();
});
</script>
