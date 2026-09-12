<template>
  <div class="space-y-6">
    <!-- Header & Date Range Filter -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
      <div>
        <div class="flex items-center gap-2 text-xs font-mono uppercase tracking-wider text-emerald-600 font-bold">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          Hospital Financial Intelligence
        </div>
        <h1 class="text-xl font-black text-slate-900 mt-1">Revenue & Collection Analytics</h1>
        <p class="text-xs text-slate-500 mt-0.5">Real-time revenue itemization by clinical department, doctor, and payment mode.</p>
      </div>

      <!-- Date Filters & Actions -->
      <div class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-xl text-xs font-semibold">
          <button
            v-for="range in dateQuickRanges"
            :key="range.label"
            @click="setQuickRange(range)"
            :class="selectedRangeLabel === range.label ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
            class="px-2.5 py-1.5 rounded-lg transition cursor-pointer"
          >
            {{ range.label }}
          </button>
        </div>

        <div class="flex items-center gap-2">
          <input
            v-model="filters.startDate"
            @change="fetchReports"
            type="date"
            class="text-xs px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500"
          />
          <span class="text-xs text-slate-400">to</span>
          <input
            v-model="filters.endDate"
            @change="fetchReports"
            type="date"
            class="text-xs px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <button
          @click="fetchReports"
          :disabled="loading"
          class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
        >
          <svg class="w-3.5 h-3.5" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Refresh
        </button>
      </div>
    </div>

    <!-- Executive KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-[11px] font-mono uppercase tracking-wider text-slate-400 font-bold">Total Invoiced</div>
        <div class="text-2xl font-black text-slate-900 mt-1">
          ${{ formatMoney(summary.total_invoiced) }}
        </div>
        <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
          <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
          Gross billed charges
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-[11px] font-mono uppercase tracking-wider text-emerald-600 font-bold">Total Collected (Net)</div>
        <div class="text-2xl font-black text-emerald-600 mt-1">
          ${{ formatMoney(summary.total_collected) }}
        </div>
        <div class="text-[11px] text-emerald-700/80 mt-1 flex items-center gap-1">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
          {{ summary.collection_rate_percentage }}% collection rate
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-[11px] font-mono uppercase tracking-wider text-rose-600 font-bold">Outstanding Balance</div>
        <div class="text-2xl font-black text-rose-600 mt-1">
          ${{ formatMoney(summary.total_outstanding) }}
        </div>
        <div class="text-[11px] text-rose-700/80 mt-1 flex items-center gap-1">
          <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
          Pending cashier settlement
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-[11px] font-mono uppercase tracking-wider text-amber-600 font-bold">Total Discounts</div>
        <div class="text-2xl font-black text-amber-600 mt-1">
          ${{ formatMoney(summary.total_discounts) }}
        </div>
        <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
          <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
          Authorized concessions
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-[11px] font-mono uppercase tracking-wider text-purple-600 font-bold">Total Refunds</div>
        <div class="text-2xl font-black text-purple-600 mt-1">
          ${{ formatMoney(summary.total_refunds) }}
        </div>
        <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
          <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
          Disbursed reversals
        </div>
      </div>
    </div>

    <!-- Analytics Breakdown Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
      <button
        v-for="tab in [
          { id: 'departments', label: 'Department Revenue', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
          { id: 'doctors', label: 'Physician / Doctor Revenue', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
          { id: 'payment_modes', label: 'Payment Method Breakdown', icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z' }
        ]"
        :key="tab.id"
        @click="activeTab = tab.id"
        :class="activeTab === tab.id ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100'"
        class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer border border-slate-200"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon" />
        </svg>
        {{ tab.label }}
      </button>
    </div>

    <!-- 1. Department Revenue View -->
    <div v-if="activeTab === 'departments'" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-4 border-b border-slate-100 flex items-center justify-between">
        <div>
          <h3 class="font-bold text-slate-900 text-sm">Revenue Generated By Department</h3>
          <p class="text-xs text-slate-400">Total billings and discounts itemized by clinical unit.</p>
        </div>
        <span class="text-xs font-mono font-bold text-slate-500">{{ departments.length }} Departments</span>
      </div>

      <div v-if="departments.length === 0" class="p-12 text-center text-xs text-slate-400">
        No department revenue records for this period.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-500 font-mono uppercase text-[11px] border-b border-slate-200">
            <tr>
              <th class="p-4">Department</th>
              <th class="p-4 text-center">Items Billed</th>
              <th class="p-4 text-right">Gross Subtotal</th>
              <th class="p-4 text-right">Discounts</th>
              <th class="p-4 text-right">Net Revenue</th>
              <th class="p-4 text-right w-48">Share of Revenue</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-700">
            <tr v-for="dept in departments" :key="dept.department" class="hover:bg-slate-50/70 transition">
              <td class="p-4">
                <div class="font-bold text-slate-900 capitalize">{{ dept.department_name }}</div>
                <div class="text-[10px] text-slate-400 font-mono uppercase">{{ dept.department }}</div>
              </td>
              <td class="p-4 text-center font-mono font-semibold text-slate-700">
                {{ dept.items_count }}
              </td>
              <td class="p-4 text-right font-mono text-slate-600">
                ${{ formatMoney(dept.subtotal) }}
              </td>
              <td class="p-4 text-right font-mono text-amber-600 font-medium">
                ${{ formatMoney(dept.discount) }}
              </td>
              <td class="p-4 text-right font-mono font-bold text-emerald-700 text-sm">
                ${{ formatMoney(dept.net_total) }}
              </td>
              <td class="p-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  <div class="w-24 bg-slate-100 rounded-full h-2 overflow-hidden">
                    <div
                      class="bg-emerald-500 h-2 rounded-full"
                      :style="{ width: calculateShare(dept.net_total, summary.total_invoiced) + '%' }"
                    ></div>
                  </div>
                  <span class="text-[11px] font-mono text-slate-500 font-semibold w-10 text-right">
                    {{ calculateShare(dept.net_total, summary.total_invoiced) }}%
                  </span>
                </div>
              </td>
            </tr>
          </tbody>
          <tfoot class="bg-slate-50 font-bold text-slate-900 text-xs border-t border-slate-200">
            <tr>
              <td class="p-4 font-mono uppercase">Totals</td>
              <td class="p-4 text-center font-mono">{{ totalDepartmentItems }}</td>
              <td class="p-4 text-right font-mono">${{ formatMoney(totalDepartmentSubtotal) }}</td>
              <td class="p-4 text-right font-mono text-amber-600">${{ formatMoney(totalDepartmentDiscount) }}</td>
              <td class="p-4 text-right font-mono text-emerald-700">${{ formatMoney(totalDepartmentNet) }}</td>
              <td class="p-4 text-right font-mono">100%</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- 2. Physician / Doctor Revenue View -->
    <div v-else-if="activeTab === 'doctors'" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-4 border-b border-slate-100 flex items-center justify-between">
        <div>
          <h3 class="font-bold text-slate-900 text-sm">Revenue Generated By Doctor</h3>
          <p class="text-xs text-slate-400">Itemized billing per attending and ordering physician.</p>
        </div>
        <span class="text-xs font-mono font-bold text-slate-500">{{ doctors.length }} Physicians</span>
      </div>

      <div v-if="doctors.length === 0" class="p-12 text-center text-xs text-slate-400">
        No doctor revenue records found for this period.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-500 font-mono uppercase text-[11px] border-b border-slate-200">
            <tr>
              <th class="p-4">Physician Name</th>
              <th class="p-4 text-center">Services Provided</th>
              <th class="p-4 text-right">Subtotal</th>
              <th class="p-4 text-right">Discounts</th>
              <th class="p-4 text-right">Net Revenue</th>
              <th class="p-4 text-right w-48">Share of Revenue</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-700">
            <tr v-for="doc in doctors" :key="doc.doctor_id || 'unassigned'" class="hover:bg-slate-50/70 transition">
              <td class="p-4">
                <div class="font-bold text-slate-900 flex items-center gap-2">
                  <span class="w-7 h-7 rounded-lg bg-blue-50 text-blue-700 font-bold flex items-center justify-center text-xs">
                    Dr
                  </span>
                  <div>
                    <div>{{ doc.doctor_name }}</div>
                    <div class="text-[10px] text-slate-400 font-mono">{{ doc.doctor_email || 'Staff Physician' }}</div>
                  </div>
                </div>
              </td>
              <td class="p-4 text-center font-mono font-semibold text-slate-700">
                {{ doc.services_count }}
              </td>
              <td class="p-4 text-right font-mono text-slate-600">
                ${{ formatMoney(doc.subtotal) }}
              </td>
              <td class="p-4 text-right font-mono text-amber-600 font-medium">
                ${{ formatMoney(doc.discount) }}
              </td>
              <td class="p-4 text-right font-mono font-bold text-blue-700 text-sm">
                ${{ formatMoney(doc.net_total) }}
              </td>
              <td class="p-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  <div class="w-24 bg-slate-100 rounded-full h-2 overflow-hidden">
                    <div
                      class="bg-blue-600 h-2 rounded-full"
                      :style="{ width: calculateShare(doc.net_total, summary.total_invoiced) + '%' }"
                    ></div>
                  </div>
                  <span class="text-[11px] font-mono text-slate-500 font-semibold w-10 text-right">
                    {{ calculateShare(doc.net_total, summary.total_invoiced) }}%
                  </span>
                </div>
              </td>
            </tr>
          </tbody>
          <tfoot class="bg-slate-50 font-bold text-slate-900 text-xs border-t border-slate-200">
            <tr>
              <td class="p-4 font-mono uppercase">Totals</td>
              <td class="p-4 text-center font-mono">{{ totalDoctorServices }}</td>
              <td class="p-4 text-right font-mono">${{ formatMoney(totalDoctorSubtotal) }}</td>
              <td class="p-4 text-right font-mono text-amber-600">${{ formatMoney(totalDoctorDiscount) }}</td>
              <td class="p-4 text-right font-mono text-blue-700">${{ formatMoney(totalDoctorNet) }}</td>
              <td class="p-4 text-right font-mono">100%</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- 3. Payment Method Breakdown View -->
    <div v-else-if="activeTab === 'payment_modes'" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div
          v-for="pm in paymentModes"
          :key="pm.payment_mode"
          class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden"
        >
          <div class="flex items-center justify-between text-slate-400">
            <span class="text-xs font-mono font-bold uppercase tracking-wider">{{ pm.mode_name }}</span>
            <span class="p-1.5 bg-slate-50 rounded-lg text-slate-600">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </span>
          </div>
          <div class="text-xl font-black text-slate-900 mt-2">
            ${{ formatMoney(pm.total_collected) }}
          </div>
          <div class="text-[11px] text-slate-500 mt-1 flex items-center justify-between">
            <span>{{ pm.transaction_count }} receipts</span>
            <span class="font-mono font-bold text-emerald-600">
              {{ calculateShare(pm.total_collected, summary.total_collected) }}%
            </span>
          </div>
        </div>
      </div>

      <!-- Payment Modes Table -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
          <div>
            <h3 class="font-bold text-slate-900 text-sm">Collections By Settlement Channel</h3>
            <p class="text-xs text-slate-400">Reconciled cashier receipts across cash, cards, mobile payments, and insurance payouts.</p>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-slate-500 font-mono uppercase text-[11px] border-b border-slate-200">
              <tr>
                <th class="p-4">Payment Method</th>
                <th class="p-4 text-center">Transactions</th>
                <th class="p-4 text-right">Total Collected</th>
                <th class="p-4 text-right w-56">Collection Ratio</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="pm in paymentModes" :key="pm.payment_mode" class="hover:bg-slate-50/70 transition">
                <td class="p-4">
                  <div class="font-bold text-slate-900">{{ pm.mode_name }}</div>
                  <div class="text-[10px] font-mono text-slate-400 uppercase">{{ pm.payment_mode }}</div>
                </td>
                <td class="p-4 text-center font-mono font-bold text-slate-700">
                  {{ pm.transaction_count }}
                </td>
                <td class="p-4 text-right font-mono font-bold text-emerald-700 text-sm">
                  ${{ formatMoney(pm.total_collected) }}
                </td>
                <td class="p-4 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <div class="w-28 bg-slate-100 rounded-full h-2 overflow-hidden">
                      <div
                        class="bg-indigo-600 h-2 rounded-full"
                        :style="{ width: calculateShare(pm.total_collected, summary.total_collected) + '%' }"
                      ></div>
                    </div>
                    <span class="text-[11px] font-mono text-slate-600 font-semibold w-12 text-right">
                      {{ calculateShare(pm.total_collected, summary.total_collected) }}%
                    </span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  branchId: {
    type: String,
    default: null,
  },
});

const loading = ref(false);
const activeTab = ref('departments');
const selectedRangeLabel = ref('This Month');

const filters = ref({
  startDate: '',
  endDate: '',
});

const summary = ref({
  total_invoiced: 0,
  total_collected: 0,
  total_outstanding: 0,
  total_discounts: 0,
  total_refunds: 0,
  collection_rate_percentage: 0,
});

const departments = ref([]);
const doctors = ref([]);
const paymentModes = ref([]);

const dateQuickRanges = [
  { label: 'Today', days: 0 },
  { label: 'Last 7 Days', days: 7 },
  { label: 'This Month', days: 30 },
  { label: 'All Time', days: null },
];

function setQuickRange(range) {
  selectedRangeLabel.value = range.label;
  const now = new Date();
  if (range.days === 0) {
    const todayStr = now.toISOString().split('T')[0];
    filters.value.startDate = todayStr;
    filters.value.endDate = todayStr;
  } else if (range.days === null) {
    filters.value.startDate = '';
    filters.value.endDate = '';
  } else {
    const start = new Date();
    start.setDate(now.getDate() - range.days);
    filters.value.startDate = start.toISOString().split('T')[0];
    filters.value.endDate = now.toISOString().split('T')[0];
  }
  fetchReports();
}

async function fetchReports() {
  loading.value = true;
  try {
    const params = {
      branch_id: props.branchId || undefined,
      start_date: filters.value.startDate || undefined,
      end_date: filters.value.endDate || undefined,
    };

    const [sumRes, deptRes, docRes, payRes] = await Promise.all([
      axios.get('/api/v1/billing/reports/summary', { params }),
      axios.get('/api/v1/billing/reports/departments', { params }),
      axios.get('/api/v1/billing/reports/doctors', { params }),
      axios.get('/api/v1/billing/reports/payment-modes', { params }),
    ]);

    if (sumRes.data?.data) {
      summary.value = sumRes.data.data;
    }
    if (deptRes.data?.data) {
      departments.value = deptRes.data.data;
    }
    if (docRes.data?.data) {
      doctors.value = docRes.data.data;
    }
    if (payRes.data?.data) {
      paymentModes.value = payRes.data.data;
    }
  } catch (error) {
    console.error('Failed to load revenue reports:', error);
  } finally {
    loading.value = false;
  }
}

function formatMoney(amount) {
  const val = Number(amount) || 0;
  return val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function calculateShare(part, total) {
  const p = Number(part) || 0;
  const t = Number(total) || 0;
  if (t <= 0) return '0.0';
  return ((p / t) * 100).toFixed(1);
}

// Department computed totals
const totalDepartmentItems = computed(() => departments.value.reduce((acc, d) => acc + (d.items_count || 0), 0));
const totalDepartmentSubtotal = computed(() => departments.value.reduce((acc, d) => acc + (d.subtotal || 0), 0));
const totalDepartmentDiscount = computed(() => departments.value.reduce((acc, d) => acc + (d.discount || 0), 0));
const totalDepartmentNet = computed(() => departments.value.reduce((acc, d) => acc + (d.net_total || 0), 0));

// Doctor computed totals
const totalDoctorServices = computed(() => doctors.value.reduce((acc, d) => acc + (d.services_count || 0), 0));
const totalDoctorSubtotal = computed(() => doctors.value.reduce((acc, d) => acc + (d.subtotal || 0), 0));
const totalDoctorDiscount = computed(() => doctors.value.reduce((acc, d) => acc + (d.discount || 0), 0));
const totalDoctorNet = computed(() => doctors.value.reduce((acc, d) => acc + (d.net_total || 0), 0));

onMounted(() => {
  setQuickRange(dateQuickRanges[2]); // Default 'This Month'
});
</script>
