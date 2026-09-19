<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
      <div>
        <div class="flex items-center gap-2">
          <div class="w-9 h-9 rounded-xl bg-purple-600 text-white flex items-center justify-center font-black shadow-md shadow-purple-500/20">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
            </svg>
          </div>
          <div>
            <h1 class="text-xl font-bold text-slate-900">Laboratory Information System (LIS)</h1>
            <p class="text-xs text-slate-500">Specimen tracking, automated flag analysis, digital certification & instrument interfaces</p>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <button
          @click="simulateAnalyzerFeed"
          class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded-xl transition flex items-center gap-1.5 shadow-sm cursor-pointer"
        >
          <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
          Simulate Analyzer (HL7 Feed)
        </button>

        <button
          @click="fetchWorklist"
          class="p-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition cursor-pointer border border-slate-200"
          title="Refresh Worklist"
        >
          <svg :class="isLoading ? 'animate-spin' : ''" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Live Barcode Scanner & Search Command Bar -->
    <div class="p-4 bg-gradient-to-r from-slate-900 to-indigo-950 text-white rounded-2xl shadow-md flex flex-col md:flex-row items-center justify-between gap-4">
      <div class="flex items-center gap-3 w-full md:w-auto">
        <div class="w-10 h-10 rounded-xl bg-blue-500/20 border border-blue-400/40 flex items-center justify-center text-blue-300 shrink-0">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
          </svg>
        </div>
        <div>
          <div class="font-bold text-sm text-white">Optical Barcode Scanner Intake</div>
          <div class="text-[11px] text-slate-300">Point handheld scanner or type barcode and press Enter to instantly locate specimen.</div>
        </div>
      </div>

      <div class="w-full md:w-96 relative">
        <input
          v-model="barcodeScanQuery"
          @keyup.enter="handleBarcodeScan"
          type="text"
          placeholder="Scan barcode (e.g. SMP-2026-00000101)..."
          class="w-full pl-9 pr-24 py-2 text-xs bg-white/10 border border-white/20 rounded-xl text-white placeholder-slate-400 focus:bg-white focus:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-400 font-mono"
        />
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <button
          @click="handleBarcodeScan"
          class="absolute right-1.5 top-1.5 px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-[11px] font-bold transition cursor-pointer"
        >
          Scan Lookup
        </button>
      </div>
    </div>

    <!-- Metric Status Pills -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
      <div
        @click="activeTab = 'all'"
        :class="activeTab === 'all' ? 'ring-2 ring-blue-600 bg-white' : 'bg-white hover:bg-slate-50'"
        class="p-4 rounded-xl border border-slate-200 cursor-pointer shadow-sm transition"
      >
        <div class="text-slate-500 text-[11px] font-medium uppercase tracking-wider">All Worklist Orders</div>
        <div class="text-2xl font-black text-slate-900 mt-1">{{ metrics.total }}</div>
      </div>

      <div
        @click="activeTab = 'pending_collection'"
        :class="activeTab === 'pending_collection' ? 'ring-2 ring-amber-500 bg-white' : 'bg-white hover:bg-slate-50'"
        class="p-4 rounded-xl border border-slate-200 cursor-pointer shadow-sm transition"
      >
        <div class="text-amber-600 text-[11px] font-medium uppercase tracking-wider">Pending Collection</div>
        <div class="text-2xl font-black text-amber-900 mt-1">{{ metrics.pending_collection }}</div>
      </div>

      <div
        @click="activeTab = 'received'"
        :class="activeTab === 'received' ? 'ring-2 ring-indigo-500 bg-white' : 'bg-white hover:bg-slate-50'"
        class="p-4 rounded-xl border border-slate-200 cursor-pointer shadow-sm transition"
      >
        <div class="text-indigo-600 text-[11px] font-medium uppercase tracking-wider">In Laboratory / Bench</div>
        <div class="text-2xl font-black text-indigo-900 mt-1">{{ metrics.received }}</div>
      </div>

      <div
        @click="activeTab = 'signed'"
        :class="activeTab === 'signed' ? 'ring-2 ring-emerald-500 bg-white' : 'bg-white hover:bg-slate-50'"
        class="p-4 rounded-xl border border-slate-200 cursor-pointer shadow-sm transition"
      >
        <div class="text-emerald-600 text-[11px] font-medium uppercase tracking-wider">Signed & Locked</div>
        <div class="text-2xl font-black text-emerald-900 mt-1">{{ metrics.signed }}</div>
      </div>

      <div
        @click="activeTab = 'abnormal'"
        :class="activeTab === 'abnormal' ? 'ring-2 ring-red-600 bg-red-50/50' : 'bg-white hover:bg-red-50/30'"
        class="p-4 rounded-xl border border-red-200 cursor-pointer shadow-sm transition"
      >
        <div class="text-red-600 text-[11px] font-bold uppercase tracking-wider flex items-center gap-1">
          <span class="w-2 h-2 rounded-full bg-red-500 animate-ping"></span>
          Abnormal / Panics
        </div>
        <div class="text-2xl font-black text-red-900 mt-1">{{ metrics.abnormal }}</div>
      </div>
    </div>

    <!-- Worklist Table Container -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
      <!-- Search & Filters -->
      <div class="p-4 border-b border-slate-200 flex flex-col md:flex-row justify-between items-center gap-3">
        <div class="flex items-center gap-2 overflow-x-auto w-full md:w-auto pb-1 md:pb-0">
          <button
            v-for="tab in tabFilters"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="activeTab === tab.id ? 'bg-slate-900 text-white font-bold' : 'text-slate-600 hover:bg-slate-100'"
            class="px-3 py-1.5 rounded-lg text-xs transition cursor-pointer shrink-0"
          >
            {{ tab.label }}
          </button>
        </div>

        <div class="w-full md:w-64">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Filter patient, MRN, test..."
            class="w-full px-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-xs text-left">
          <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 font-semibold uppercase tracking-wider text-[11px]">
            <tr>
              <th class="py-3 px-4">Specimen Barcode</th>
              <th class="py-3 px-4">Patient Information</th>
              <th class="py-3 px-4">Test / Order</th>
              <th class="py-3 px-4">Specimen Tube</th>
              <th class="py-3 px-4">Status</th>
              <th class="py-3 px-4 text-center">Alerts</th>
              <th class="py-3 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="filteredSamples.length === 0" class="text-center py-8">
              <td colspan="7" class="py-8 text-slate-400">
                No laboratory specimens found matching active filter.
              </td>
            </tr>

            <tr
              v-for="sample in filteredSamples"
              :key="sample.id"
              class="hover:bg-slate-50/70 transition"
              :class="{
                'bg-red-50/30': sample.result?.has_critical_values,
                'bg-amber-50/20': sample.result?.has_abnormal_values && !sample.result?.has_critical_values
              }"
            >
              <!-- Barcode Column -->
              <td class="py-3.5 px-4 font-mono">
                <div class="flex items-center gap-1.5 font-bold text-slate-900">
                  <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                  </svg>
                  <span>{{ sample.barcode }}</span>
                </div>
                <div class="text-[10px] text-slate-400 mt-0.5">
                  Order: {{ sample.lab_order?.order_number || 'N/A' }}
                </div>
              </td>

              <!-- Patient Information -->
              <td class="py-3.5 px-4">
                <div class="font-bold text-slate-900">{{ sample.patient?.name }}</div>
                <div class="text-[11px] text-slate-500 font-mono">
                  MRN: {{ sample.patient?.mrn }} | {{ sample.patient?.gender === 'male' ? 'M' : 'F' }}, {{ sample.patient?.age }}y
                </div>
              </td>

              <!-- Test / Order Details -->
              <td class="py-3.5 px-4">
                <div class="font-semibold text-blue-700">
                  {{ sample.lab_order?.test_type || 'Diagnostic Test' }}
                </div>
                <div class="text-[10px] text-slate-400">
                  Dr: {{ sample.lab_order?.ordering_doctor || 'Clinician' }}
                </div>
              </td>

              <!-- Tube & Container -->
              <td class="py-3.5 px-4">
                <div class="flex items-center gap-1.5">
                  <span
                    :class="getTubeColorClass(sample.container_type || sample.sample_type)"
                    class="w-2.5 h-2.5 rounded-full inline-block shrink-0"
                  ></span>
                  <span class="font-medium text-slate-700">{{ sample.container_type || sample.sample_type || 'EDTA' }}</span>
                </div>
                <div class="text-[10px] text-slate-400">{{ sample.sample_type }}</div>
              </td>

              <!-- Workflow Status -->
              <td class="py-3.5 px-4">
                <span :class="getStatusBadgeClass(sample.status)" class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider">
                  {{ formatStatus(sample.status) }}
                </span>
                <div v-if="sample.result?.version > 1" class="text-[10px] text-amber-700 font-semibold mt-0.5">
                  v{{ sample.result.version }} (Amended)
                </div>
              </td>

              <!-- Alerts & Notification Status -->
              <td class="py-3.5 px-4 text-center">
                <div v-if="sample.result?.has_critical_values" class="inline-flex flex-col items-center">
                  <span class="px-2 py-0.5 rounded bg-red-600 text-white font-black text-[10px] uppercase tracking-wider animate-pulse flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    PANIC
                  </span>
                  <span v-if="sample.result?.doctor_notified_at" class="text-[9px] text-red-700 font-semibold mt-0.5">
                    Doctor Notified
                  </span>
                </div>
                <div v-else-if="sample.result?.has_abnormal_values" class="inline-flex flex-col items-center">
                  <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 font-bold text-[10px]">
                    ABNORMAL
                  </span>
                  <span v-if="sample.result?.doctor_notified_at" class="text-[9px] text-amber-700 font-medium mt-0.5">
                    Doctor Alerted
                  </span>
                </div>
                <span v-else-if="sample.status === 'signed'" class="text-emerald-600 font-bold text-[11px] flex items-center justify-center gap-1">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  Normal
                </span>
                <span v-else class="text-slate-300">-</span>
              </td>

              <!-- Actions Dropdown / Buttons -->
              <td class="py-3.5 px-4 text-right space-x-1.5 whitespace-nowrap">
                <!-- Print Barcode Label -->
                <button
                  type="button"
                  @click="openSampleLabel(sample)"
                  class="p-1.5 text-slate-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition cursor-pointer"
                  title="Print Tube Label"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                  </svg>
                </button>

                <!-- Workflow Step Quick Actions -->
                <button
                  v-if="sample.status === 'pending_collection'"
                  type="button"
                  @click="updateSampleStatus(sample, 'collected')"
                  class="px-2.5 py-1 bg-slate-100 hover:bg-blue-100 text-blue-700 font-semibold rounded-lg text-xs transition cursor-pointer"
                >
                  Collect
                </button>

                <button
                  v-else-if="sample.status === 'collected'"
                  type="button"
                  @click="updateSampleStatus(sample, 'received')"
                  class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold rounded-lg text-xs transition cursor-pointer"
                >
                  Receive in Lab
                </button>

                <!-- Enter / Edit Results -->
                <button
                  v-if="sample.status !== 'signed'"
                  type="button"
                  @click="openResultEntry(sample)"
                  class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs transition cursor-pointer shadow-sm"
                >
                  Enter Results
                </button>

                <!-- View Signed Report -->
                <button
                  v-if="sample.status === 'signed' || sample.result"
                  type="button"
                  @click="openReportPreview(sample.result || sample)"
                  class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold rounded-lg text-xs transition cursor-pointer border border-emerald-300"
                >
                  Report
                </button>

                <!-- Amend Signed Report -->
                <button
                  v-if="sample.status === 'signed'"
                  type="button"
                  @click="openAmendment(sample.result || sample)"
                  class="px-2 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 font-semibold rounded-lg text-xs transition cursor-pointer"
                  title="Submit Versioned Amendment"
                >
                  Amend
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modals -->
    <SampleLabelModal
      :is-open="isLabelModalOpen"
      :sample="selectedSample"
      @close="isLabelModalOpen = false"
    />

    <ResultEntryForm
      :is-open="isResultFormOpen"
      :context="selectedContext"
      :is-amending="isAmendingReport"
      @close="isResultFormOpen = false"
      @saved="handleResultSaved"
    />

    <ReportPreviewModal
      :is-open="isReportModalOpen"
      :report="selectedReport"
      :report-data="selectedReportData"
      @close="isReportModalOpen = false"
      @amend="handleInitiateAmendmentFromReport"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import SampleLabelModal from './SampleLabelModal.vue';
import ResultEntryForm from './ResultEntryForm.vue';
import ReportPreviewModal from './ReportPreviewModal.vue';
import { showAlert } from '../../Services/modalDialog';

const props = defineProps({
  branchId: { type: String, default: 'b9ff561a-5396-4309-9b08-3e7b358310e9' },
});

const samples = ref([]);
const isLoading = ref(false);
const searchQuery = ref('');
const barcodeScanQuery = ref('');
const activeTab = ref('all');

// Modals State
const isLabelModalOpen = ref(false);
const selectedSample = ref(null);

const isResultFormOpen = ref(false);
const selectedContext = ref(null);
const isAmendingReport = ref(false);

const isReportModalOpen = ref(false);
const selectedReport = ref(null);
const selectedReportData = ref(null);

const tabFilters = [
  { id: 'all', label: 'All Bench Orders' },
  { id: 'pending_collection', label: 'Pending Collection' },
  { id: 'received', label: 'In Lab / Received' },
  { id: 'processing', label: 'Processing' },
  { id: 'signed', label: 'Signed & Certified' },
  { id: 'abnormal', label: 'Abnormal / Panics' },
];

onMounted(() => {
  fetchWorklist();
});

async function fetchWorklist() {
  isLoading.value = true;
  try {
    const res = await fetch('/api/v1/laboratory/samples?per_page=50', {
      headers: {
        'Accept': 'application/json',
        'X-Branch-ID': props.branchId,
      },
    });

    const json = await res.json();
    if (res.ok && json.data) {
      samples.value = json.data;
    }
  } catch (e) {
    console.error('Failed to load lab worklist', e);
  } finally {
    isLoading.value = false;
  }
}

const metrics = computed(() => {
  const total = samples.value.length;
  const pending_collection = samples.value.filter((s) => s.status === 'pending_collection').length;
  const received = samples.value.filter((s) => s.status === 'received' || s.status === 'processing').length;
  const signed = samples.value.filter((s) => s.status === 'signed' || s.result?.status === 'signed').length;
  const abnormal = samples.value.filter((s) => s.result?.has_abnormal_values || s.result?.has_critical_values).length;

  return { total, pending_collection, received, signed, abnormal };
});

const filteredSamples = computed(() => {
  let list = samples.value;

  // Filter by Tab
  if (activeTab.value === 'pending_collection') {
    list = list.filter((s) => s.status === 'pending_collection');
  } else if (activeTab.value === 'received') {
    list = list.filter((s) => s.status === 'received' || s.status === 'collected');
  } else if (activeTab.value === 'processing') {
    list = list.filter((s) => s.status === 'processing');
  } else if (activeTab.value === 'signed') {
    list = list.filter((s) => s.status === 'signed' || s.result?.status === 'signed');
  } else if (activeTab.value === 'abnormal') {
    list = list.filter((s) => s.result?.has_abnormal_values || s.result?.has_critical_values);
  }

  // Filter by Search Query
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter((s) => {
      const pName = s.patient?.name?.toLowerCase() || '';
      const mrn = s.patient?.mrn?.toLowerCase() || '';
      const barcode = s.barcode?.toLowerCase() || '';
      const test = s.lab_order?.test_type?.toLowerCase() || '';
      return pName.includes(q) || mrn.includes(q) || barcode.includes(q) || test.includes(q);
    });
  }

  return list;
});

async function handleBarcodeScan() {
  const code = barcodeScanQuery.value.trim();
  if (!code) return;

  try {
    const res = await fetch(`/api/v1/laboratory/samples/scan/${encodeURIComponent(code)}`, {
      headers: {
        'Accept': 'application/json',
        'X-Branch-ID': props.branchId,
      },
    });

    const json = await res.json();
    if (res.ok && json.data) {
      // Find or add sample and open results/preview
      const matched = json.data;
      openResultEntry(matched);
      barcodeScanQuery.value = '';
    } else {
      await showAlert(`Barcode ${code} not found in active laboratory orders.`);
    }
  } catch (err) {
    await showAlert('Barcode scan error: ' + err.message);
  }
}

async function updateSampleStatus(sample, newStatus) {
  try {
    const res = await fetch(`/api/v1/laboratory/samples/${sample.id}/status`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Branch-ID': props.branchId,
      },
      body: JSON.stringify({ status: newStatus }),
    });

    const json = await res.json();
    if (res.ok && json.data) {
      const idx = samples.value.findIndex((s) => s.id === sample.id);
      if (idx !== -1) {
        samples.value[idx] = json.data;
      }
    }
  } catch (err) {
    console.error('Failed to update sample status', err);
  }
}

function openSampleLabel(sample) {
  selectedSample.value = sample;
  isLabelModalOpen.value = true;
}

function openResultEntry(sample) {
  selectedContext.value = sample;
  isAmendingReport.value = false;
  isResultFormOpen.value = true;
}

function openAmendment(item) {
  selectedContext.value = item.result || item;
  isAmendingReport.value = true;
  isResultFormOpen.value = true;
}

async function openReportPreview(resultOrSample) {
  const resultObj = resultOrSample.result || resultOrSample;
  selectedReport.value = resultObj;

  // Fetch printable payload
  if (resultObj.id) {
    try {
      const res = await fetch(`/api/v1/laboratory/results/${resultObj.id}/print`, {
        headers: {
          'Accept': 'application/json',
          'X-Branch-ID': props.branchId,
        },
      });
      const json = await res.json();
      if (res.ok && json.data) {
        selectedReportData.value = json.data;
      }
    } catch (e) {
      console.error(e);
    }
  }

  isReportModalOpen.value = true;
}

function handleInitiateAmendmentFromReport(rep) {
  isReportModalOpen.value = false;
  openAmendment(rep);
}

function handleResultSaved() {
  fetchWorklist();
}

async function simulateAnalyzerFeed() {
  const firstSample = samples.value[0];
  if (!firstSample) {
    await showAlert('No samples currently in worklist to simulate equipment feed against.');
    return;
  }

  const hl7Msg = [
    `MSH|^~\\&|SYSCLINIC_XN1000|LAB|HMS|HOSPITAL|${new Date().toISOString()}||ORU^R01|MSG999|P|2.5`,
    `PID|1||${firstSample.patient?.mrn || 'MRN-001'}||${firstSample.patient?.name || 'Patient'}||19850101|M`,
    `OBR|1|${firstSample.lab_order?.order_number || 'ORD'}|${firstSample.barcode}|CBC^Complete Blood Count|||${new Date().toISOString()}`,
    `OBX|1|NM|Hemoglobin|1|16.2|g/dL|13.5-17.5|N|||F`,
    `OBX|2|NM|Platelets|1|280|10^3/uL|150-450|N|||F`,
  ].join('\r\n');

  try {
    const res = await fetch('/api/v1/laboratory/equipment/hl7', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Branch-ID': props.branchId,
      },
      body: JSON.stringify({
        hl7_message: hl7Msg,
        device_id: 'SYSCLINIC_XN1000',
      }),
    });

    const json = await res.json();
    if (res.ok) {
      await showAlert(`Analyzer HL7 Feed processed successfully for sample ${firstSample.barcode}!`);
      fetchWorklist();
    } else {
      await showAlert(`HL7 ingestion error: ${json.message}`);
    }
  } catch (err) {
    await showAlert(`Analyzer integration simulation error: ${err.message}`);
  }
}

function getTubeColorClass(container) {
  const c = (container || '').toLowerCase();
  if (c.includes('edta') || c.includes('lavender') || c.includes('purple')) return 'bg-purple-500';
  if (c.includes('sst') || c.includes('gold') || c.includes('yellow')) return 'bg-amber-400';
  if (c.includes('citrate') || c.includes('blue')) return 'bg-blue-400';
  if (c.includes('urine')) return 'bg-amber-200 border border-amber-400';
  return 'bg-slate-400';
}

function getStatusBadgeClass(status) {
  switch (status) {
    case 'pending_collection': return 'bg-slate-100 text-slate-700';
    case 'collected': return 'bg-blue-100 text-blue-800';
    case 'received': return 'bg-indigo-100 text-indigo-800';
    case 'processing': return 'bg-purple-100 text-purple-800';
    case 'signed': return 'bg-emerald-100 text-emerald-800';
    case 'amended': return 'bg-amber-100 text-amber-800';
    case 'rejected': return 'bg-red-100 text-red-800';
    default: return 'bg-slate-100 text-slate-700';
  }
}

function formatStatus(status) {
  if (!status) return 'Pending';
  return status.replace(/_/g, ' ');
}
</script>
