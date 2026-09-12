<template>
  <div class="space-y-6">
    <!-- Action Header & Filters -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
        </div>
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-xl font-bold text-slate-900">Hospital Executive KPI Dashboard</h1>
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
              Pre-Aggregated (&lt; 2s SLA)
            </span>
          </div>
          <p class="text-xs text-slate-500 mt-0.5">Real-time occupancy, revenue reconciliation, and patient throughput analytics</p>
        </div>
      </div>

      <!-- Controls: Date Range & Export -->
      <div class="flex flex-wrap items-center gap-2.5">
        <select
          v-model="selectedPreset"
          @change="onPresetChange"
          class="px-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-white text-slate-700 font-medium focus:ring-2 focus:ring-blue-500 cursor-pointer"
        >
          <option value="today">Today</option>
          <option value="last_7_days">Last 7 Days</option>
          <option value="last_30_days">Last 30 Days</option>
          <option value="this_month">This Month</option>
          <option value="last_month">Last Month</option>
          <option value="this_year">This Year</option>
          <option value="custom">Custom Range...</option>
        </select>

        <div v-if="selectedPreset === 'custom'" class="flex items-center gap-1.5">
          <input
            type="date"
            v-model="customStartDate"
            class="px-2.5 py-1.5 text-xs rounded-xl border border-slate-200 text-slate-700"
          />
          <span class="text-xs text-slate-400">to</span>
          <input
            type="date"
            v-model="customEndDate"
            class="px-2.5 py-1.5 text-xs rounded-xl border border-slate-200 text-slate-700"
          />
          <button
            @click="fetchKpis"
            class="px-3 py-1.5 rounded-xl bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800 transition cursor-pointer"
          >
            Apply
          </button>
        </div>

        <button
          @click="refreshLiveAggregation"
          :disabled="refreshing"
          class="px-3.5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
          title="Recalculate and update aggregation tables"
        >
          <svg class="w-3.5 h-3.5" :class="{ 'animate-spin': refreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Sync
        </button>

        <button
          @click="exportKpiCsv"
          class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-sm"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          Export CSV
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="p-16 text-center text-slate-400 bg-white rounded-2xl border border-slate-200">
      <svg class="w-8 h-8 mx-auto animate-spin text-blue-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
      </svg>
      Loading aggregated hospital analytics...
    </div>

    <div v-else class="space-y-6">
      <!-- 4 Core Executive Metric Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Bed Occupancy KPI Card -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Bed Occupancy</span>
            <span
              class="px-2 py-0.5 rounded-full text-[10px] font-bold"
              :class="kpis.current_occupancy?.rate_percentage > 85 ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800'"
            >
              {{ kpis.current_occupancy?.rate_percentage > 85 ? 'High Demand' : 'Nominal' }}
            </span>
          </div>

          <div class="mt-3">
            <div class="flex items-baseline gap-2">
              <span class="text-3xl font-black text-slate-900">{{ kpis.current_occupancy?.rate_percentage || 0 }}%</span>
              <span class="text-xs text-slate-500 font-medium">Avg: {{ kpis.current_occupancy?.average_rate_period || 0 }}%</span>
            </div>

            <!-- Progress Bar -->
            <div class="w-full bg-slate-100 h-2 rounded-full mt-3 overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-500"
                :class="kpis.current_occupancy?.rate_percentage > 85 ? 'bg-rose-500' : 'bg-indigo-600'"
                :style="{ width: `${Math.min(100, kpis.current_occupancy?.rate_percentage || 0)}%` }"
              ></div>
            </div>

            <div class="flex items-center justify-between text-[11px] text-slate-500 mt-2">
              <span>Occupied: <strong>{{ kpis.current_occupancy?.occupied_beds || 0 }}</strong> / {{ kpis.current_occupancy?.total_beds || 0 }} Beds</span>
              <span>ALOS: <strong>{{ kpis.current_occupancy?.average_length_of_stay_days || 0 }}d</strong></span>
            </div>
          </div>
        </div>

        <!-- 2. Revenue & Financial KPI Card -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Revenue Collected</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
              {{ kpis.revenue?.collection_rate_percentage || 0 }}% Recov.
            </span>
          </div>

          <div class="mt-3">
            <div class="text-3xl font-black text-emerald-600">
              ${{ formatNumber(kpis.revenue?.total_collected || 0) }}
            </div>

            <!-- Progress Bar -->
            <div class="w-full bg-slate-100 h-2 rounded-full mt-3 overflow-hidden">
              <div
                class="bg-emerald-500 h-full rounded-full transition-all duration-500"
                :style="{ width: `${Math.min(100, kpis.revenue?.collection_rate_percentage || 0)}%` }"
              ></div>
            </div>

            <div class="flex items-center justify-between text-[11px] text-slate-500 mt-2">
              <span>Billed: <strong>${{ formatNumber(kpis.revenue?.total_invoiced || 0) }}</strong></span>
              <span>Due: <strong class="text-amber-600">${{ formatNumber(kpis.revenue?.total_outstanding || 0) }}</strong></span>
            </div>
          </div>
        </div>

        <!-- 3. Patient Flow & Encounters KPI Card -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Encounters</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800">
              {{ period.days_count || 1 }} Days
            </span>
          </div>

          <div class="mt-3">
            <div class="text-3xl font-black text-slate-900">
              {{ formatNumber(kpis.patient_flow?.total_encounters || 0) }}
            </div>

            <div class="grid grid-cols-3 gap-1 mt-3 pt-2 border-t border-slate-100 text-[11px]">
              <div>
                <span class="text-slate-400 block text-[10px]">OPD</span>
                <strong class="text-slate-800">{{ kpis.patient_flow?.total_opd_visits || 0 }}</strong>
              </div>
              <div>
                <span class="text-slate-400 block text-[10px]">Admissions</span>
                <strong class="text-slate-800">{{ kpis.patient_flow?.total_admissions || 0 }}</strong>
              </div>
              <div>
                <span class="text-slate-400 block text-[10px]">Emergency</span>
                <strong class="text-rose-600">{{ kpis.patient_flow?.total_emergency_cases || 0 }}</strong>
              </div>
            </div>
          </div>
        </div>

        <!-- 4. Clinical Diagnostics Throughput -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Diagnostics & Rx</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
              Orders
            </span>
          </div>

          <div class="mt-3">
            <div class="text-3xl font-black text-slate-900">
              {{ (kpis.clinical_throughput?.total_prescriptions || 0) + (kpis.clinical_throughput?.total_lab_orders || 0) + (kpis.clinical_throughput?.total_radiology_orders || 0) }}
            </div>

            <div class="grid grid-cols-3 gap-1 mt-3 pt-2 border-t border-slate-100 text-[11px]">
              <div>
                <span class="text-slate-400 block text-[10px]">Rx Written</span>
                <strong class="text-slate-800">{{ kpis.clinical_throughput?.total_prescriptions || 0 }}</strong>
              </div>
              <div>
                <span class="text-slate-400 block text-[10px]">Lab Tests</span>
                <strong class="text-slate-800">{{ kpis.clinical_throughput?.total_lab_orders || 0 }}</strong>
              </div>
              <div>
                <span class="text-slate-400 block text-[10px]">Scans</span>
                <strong class="text-slate-800">{{ kpis.clinical_throughput?.total_radiology_orders || 0 }}</strong>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Trend Visualizer Charts -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Chart 1: Daily Patient Flow Trend -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
              <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
              </svg>
              Daily Patient Flow Volume
            </h3>
            <div class="flex items-center gap-3 text-[11px]">
              <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 bg-blue-500 rounded-sm"></span> OPD</span>
              <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 bg-indigo-500 rounded-sm"></span> Admissions</span>
              <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 bg-rose-500 rounded-sm"></span> ER Cases</span>
            </div>
          </div>

          <!-- SVG Bar Visualizer -->
          <div class="h-48 flex items-end gap-2 pt-6 pb-2 px-2 border-b border-slate-100 overflow-x-auto">
            <div
              v-for="item in trends"
              :key="item.date"
              class="flex-1 min-w-[32px] flex flex-col items-center gap-1 group relative h-full justify-end"
            >
              <!-- Stacked visual bars -->
              <div class="w-full flex flex-col justify-end items-center gap-0.5 h-full">
                <div
                  class="w-full bg-rose-500 rounded-t-sm transition-all duration-300"
                  :style="{ height: `${calculateHeight(item.emergency_cases, maxEncounters)}%` }"
                ></div>
                <div
                  class="w-full bg-indigo-500 transition-all duration-300"
                  :style="{ height: `${calculateHeight(item.admissions, maxEncounters)}%` }"
                ></div>
                <div
                  class="w-full bg-blue-500 rounded-b-sm transition-all duration-300"
                  :style="{ height: `${calculateHeight(item.opd_visits, maxEncounters)}%` }"
                ></div>
              </div>
              <span class="text-[9px] text-slate-400 font-mono">{{ item.label }}</span>

              <!-- Hover Tooltip -->
              <div class="hidden group-hover:block absolute bottom-full mb-1 z-20 bg-slate-900 text-white text-[10px] p-2 rounded-lg whitespace-nowrap shadow-xl">
                <div class="font-bold">{{ item.date }}</div>
                <div>OPD: {{ item.opd_visits }}</div>
                <div>Admissions: {{ item.admissions }}</div>
                <div>Emergency: {{ item.emergency_cases }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Chart 2: Cash Flow & Collections -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
              <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Daily Invoiced vs Collected
            </h3>
            <div class="flex items-center gap-3 text-[11px]">
              <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 bg-slate-400 rounded-sm"></span> Invoiced</span>
              <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 bg-emerald-500 rounded-sm"></span> Collected</span>
            </div>
          </div>

          <!-- SVG Bar Visualizer -->
          <div class="h-48 flex items-end gap-2 pt-6 pb-2 px-2 border-b border-slate-100 overflow-x-auto">
            <div
              v-for="item in trends"
              :key="item.date"
              class="flex-1 min-w-[32px] flex flex-col items-center gap-1 group relative h-full justify-end"
            >
              <div class="w-full flex items-end justify-center gap-1 h-full">
                <div
                  class="w-1/2 bg-slate-300 rounded-t-sm transition-all duration-300"
                  :style="{ height: `${calculateHeight(item.revenue_invoiced, maxRevenue)}%` }"
                ></div>
                <div
                  class="w-1/2 bg-emerald-500 rounded-t-sm transition-all duration-300"
                  :style="{ height: `${calculateHeight(item.revenue_collected, maxRevenue)}%` }"
                ></div>
              </div>
              <span class="text-[9px] text-slate-400 font-mono">{{ item.label }}</span>

              <!-- Hover Tooltip -->
              <div class="hidden group-hover:block absolute bottom-full mb-1 z-20 bg-slate-900 text-white text-[10px] p-2 rounded-lg whitespace-nowrap shadow-xl">
                <div class="font-bold">{{ item.date }}</div>
                <div>Billed: ${{ formatNumber(item.revenue_invoiced) }}</div>
                <div>Collected: ${{ formatNumber(item.revenue_collected) }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom Grids: Department Revenue & Payment Modes -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Department Revenue Distribution -->
        <div class="lg:col-span-2 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="font-bold text-slate-900 text-sm">Revenue Generated by Department</h3>
            <span class="text-xs text-slate-400 font-mono">{{ departmentRevenue.length }} Departments</span>
          </div>

          <div v-if="departmentRevenue.length === 0" class="text-center py-8 text-xs text-slate-400">
            No departmental billings recorded for this period.
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="dept in departmentRevenue"
              :key="dept.department"
              class="space-y-1"
            >
              <div class="flex items-center justify-between text-xs">
                <span class="font-semibold text-slate-800 capitalize">{{ dept.department }}</span>
                <span class="font-bold text-slate-900 font-mono">${{ formatNumber(dept.revenue) }}</span>
              </div>
              <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                <div
                  class="bg-blue-600 h-full rounded-full"
                  :style="{ width: `${calculatePercentage(dept.revenue, totalDeptRevenue)}%` }"
                ></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Payment Modes Distribution -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="font-bold text-slate-900 text-sm">Payment Methods</h3>
            <span class="text-xs text-slate-400 font-mono">Collections</span>
          </div>

          <div v-if="paymentModes.length === 0" class="text-center py-8 text-xs text-slate-400">
            No payment receipts recorded for this period.
          </div>

          <div v-else class="space-y-2.5">
            <div
              v-for="mode in paymentModes"
              :key="mode.mode"
              class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between"
            >
              <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs">
                  $
                </div>
                <div>
                  <span class="text-xs font-bold text-slate-800 capitalize">{{ mode.mode.replace('_', ' ') }}</span>
                  <span class="text-[10px] text-slate-400 block">{{ mode.count }} transactions</span>
                </div>
              </div>
              <span class="font-mono font-black text-sm text-slate-900">${{ formatNumber(mode.amount) }}</span>
            </div>
          </div>
        </div>
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
const refreshing = ref(false);
const selectedPreset = ref('last_30_days');
const customStartDate = ref('');
const customEndDate = ref('');

const period = ref({});
const kpis = ref({});
const trends = ref([]);
const departmentRevenue = ref([]);
const paymentModes = ref([]);

const totalDeptRevenue = computed(() => {
  return departmentRevenue.value.reduce((acc, curr) => acc + curr.revenue, 0) || 1;
});

const maxEncounters = computed(() => {
  if (trends.value.length === 0) return 10;
  return Math.max(...trends.value.map(t => (t.opd_visits + t.admissions + t.emergency_cases))) || 10;
});

const maxRevenue = computed(() => {
  if (trends.value.length === 0) return 1000;
  return Math.max(...trends.value.map(t => Math.max(t.revenue_invoiced, t.revenue_collected))) || 1000;
});

function calculateHeight(val, max) {
  if (!max || max <= 0) return 0;
  return Math.min(100, Math.max(4, Math.round((val / max) * 100)));
}

function calculatePercentage(val, total) {
  if (!total || total <= 0) return 0;
  return Math.min(100, Math.round((val / total) * 100));
}

function formatNumber(num) {
  return Number(num || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function onPresetChange() {
  if (selectedPreset.value !== 'custom') {
    fetchKpis();
  }
}

async function fetchKpis() {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (props.branchId) params.append('branch_id', props.branchId);
    params.append('preset', selectedPreset.value);

    if (selectedPreset.value === 'custom' && customStartDate.value && customEndDate.value) {
      params.append('start_date', customStartDate.value);
      params.append('end_date', customEndDate.value);
    }

    const res = await fetch(`/api/v1/reports/kpis?${params.toString()}`, {
      headers: {
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
    });

    const json = await res.json();
    if (json.success && json.data) {
      period.value = json.data.period || {};
      kpis.value = json.data.kpis || {};
      trends.value = json.data.trends || [];
      departmentRevenue.value = json.data.department_revenue || [];
      paymentModes.value = json.data.payment_modes || [];
    }
  } catch (err) {
    console.error('Failed to load KPIs:', err);
  } finally {
    loading.value = false;
  }
}

async function refreshLiveAggregation() {
  refreshing.value = true;
  try {
    const res = await fetch('/api/v1/reports/kpis/refresh', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
      body: JSON.stringify({ branch_id: props.branchId }),
    });

    const json = await res.json();
    if (json.success) {
      await fetchKpis();
    }
  } catch (err) {
    console.error('Refresh error:', err);
  } finally {
    refreshing.value = false;
  }
}

async function exportKpiCsv() {
  const params = {
    report_type: 'kpis',
    branch_id: props.branchId,
    preset: selectedPreset.value,
    start_date: customStartDate.value,
    end_date: customEndDate.value,
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
  fetchKpis();
});
</script>
