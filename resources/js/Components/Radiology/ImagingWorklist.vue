<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-black shadow-md shadow-indigo-500/20">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <div>
          <h1 class="text-xl font-bold text-slate-900">Radiology Information System (RIS)</h1>
          <p class="text-xs text-slate-500">Diagnostic imaging intake, modality scheduling, DICOM image viewing & radiologist reporting</p>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <button
          @click="fetchOrders"
          class="p-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition cursor-pointer border border-slate-200"
          title="Refresh Worklist"
        >
          <svg :class="isLoading ? 'animate-spin' : ''" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Modality Navigation Selector Bar -->
    <div class="flex items-center gap-2 bg-slate-900 p-2 rounded-2xl shadow-md text-xs overflow-x-auto">
      <button
        v-for="mod in modalityTabs"
        :key="mod.id"
        @click="activeModality = mod.id"
        :class="activeModality === mod.id ? 'bg-blue-600 text-white font-bold shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800'"
        class="px-3.5 py-1.5 rounded-xl transition cursor-pointer shrink-0 flex items-center gap-2"
      >
        <span>{{ mod.label }}</span>
        <span class="px-1.5 py-0.2 rounded-full bg-black/30 text-[10px] font-mono">
          {{ getCountByModality(mod.id) }}
        </span>
      </button>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
      <div
        @click="activeStatusFilter = 'all'"
        :class="activeStatusFilter === 'all' ? 'ring-2 ring-blue-600 bg-white' : 'bg-white hover:bg-slate-50'"
        class="p-4 rounded-xl border border-slate-200 cursor-pointer shadow-sm transition"
      >
        <div class="text-slate-500 text-[11px] font-medium uppercase tracking-wider">Total Imaging Orders</div>
        <div class="text-2xl font-black text-slate-900 mt-1">{{ metrics.total }}</div>
      </div>

      <div
        @click="activeStatusFilter = 'scheduled'"
        :class="activeStatusFilter === 'scheduled' ? 'ring-2 ring-indigo-500 bg-white' : 'bg-white hover:bg-slate-50'"
        class="p-4 rounded-xl border border-slate-200 cursor-pointer shadow-sm transition"
      >
        <div class="text-indigo-600 text-[11px] font-medium uppercase tracking-wider">Scheduled Slots</div>
        <div class="text-2xl font-black text-indigo-900 mt-1">{{ metrics.scheduled }}</div>
      </div>

      <div
        @click="activeStatusFilter = 'in_progress'"
        :class="activeStatusFilter === 'in_progress' ? 'ring-2 ring-amber-500 bg-white' : 'bg-white hover:bg-slate-50'"
        class="p-4 rounded-xl border border-slate-200 cursor-pointer shadow-sm transition"
      >
        <div class="text-amber-600 text-[11px] font-medium uppercase tracking-wider">In Acquisition</div>
        <div class="text-2xl font-black text-amber-900 mt-1">{{ metrics.in_progress }}</div>
      </div>

      <div
        @click="activeStatusFilter = 'ready_for_report'"
        :class="activeStatusFilter === 'ready_for_report' ? 'ring-2 ring-purple-500 bg-white' : 'bg-white hover:bg-slate-50'"
        class="p-4 rounded-xl border border-slate-200 cursor-pointer shadow-sm transition"
      >
        <div class="text-purple-600 text-[11px] font-medium uppercase tracking-wider">Ready for Reading</div>
        <div class="text-2xl font-black text-purple-900 mt-1">{{ metrics.ready_for_reading }}</div>
      </div>

      <div
        @click="activeStatusFilter = 'critical'"
        :class="activeStatusFilter === 'critical' ? 'ring-2 ring-red-600 bg-red-50/50' : 'bg-white hover:bg-red-50/20'"
        class="p-4 rounded-xl border border-red-200 cursor-pointer shadow-sm transition"
      >
        <div class="text-red-600 text-[11px] font-bold uppercase tracking-wider flex items-center gap-1">
          <span class="w-2 h-2 rounded-full bg-red-500 animate-ping"></span>
          Critical Finding Alerts
        </div>
        <div class="text-2xl font-black text-red-900 mt-1">{{ metrics.critical }}</div>
      </div>
    </div>

    <!-- Imaging Worklist Bench Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
      
      <!-- Toolbar Filter & Search -->
      <div class="p-4 border-b border-slate-200 flex flex-col md:flex-row justify-between items-center gap-3">
        <div class="flex items-center gap-2 overflow-x-auto w-full md:w-auto">
          <button
            v-for="s in statusTabs"
            :key="s.id"
            @click="activeStatusFilter = s.id"
            :class="activeStatusFilter === s.id ? 'bg-slate-900 text-white font-bold' : 'text-slate-600 hover:bg-slate-100'"
            class="px-3 py-1.5 rounded-lg text-xs transition cursor-pointer shrink-0"
          >
            {{ s.label }}
          </button>
        </div>

        <div class="w-full md:w-72">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search patient, MRN, accession, procedure..."
            class="w-full px-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
      </div>

      <!-- Table Body -->
      <div class="overflow-x-auto">
        <table class="w-full text-xs text-left">
          <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 font-semibold uppercase tracking-wider text-[11px]">
            <tr>
              <th class="py-3 px-4">Accession / Study</th>
              <th class="py-3 px-4">Patient Information</th>
              <th class="py-3 px-4">Modality & Procedure</th>
              <th class="py-3 px-4">Schedule / Room</th>
              <th class="py-3 px-4">Acquisition State</th>
              <th class="py-3 px-4 text-center">Diagnostic Report</th>
              <th class="py-3 px-4 text-center">Scans</th>
              <th class="py-3 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="filteredOrders.length === 0" class="text-center py-8">
              <td colspan="8" class="py-8 text-slate-400">
                No radiology orders found matching active modality or filters.
              </td>
            </tr>

            <tr
              v-for="order in filteredOrders"
              :key="order.id"
              class="hover:bg-slate-50/70 transition"
              :class="{
                'bg-red-50/40': order.latest_report?.critical_alert,
              }"
            >
              <!-- Accession & Priority -->
              <td class="py-3.5 px-4 font-mono">
                <div class="font-bold text-slate-900 flex items-center gap-1.5">
                  <span :class="getPriorityBadgeClass(order.priority)" class="px-1.5 py-0.2 rounded text-[9px] font-sans font-black uppercase">
                    {{ order.priority }}
                  </span>
                  <span>{{ order.accession_number }}</span>
                </div>
                <div class="text-[10px] text-slate-400 font-sans mt-0.5">
                  Req by: {{ order.ordering_doctor || 'Clinician' }}
                </div>
              </td>

              <!-- Patient Demographics -->
              <td class="py-3.5 px-4">
                <div class="font-bold text-slate-900">{{ order.patient?.name }}</div>
                <div class="text-[11px] text-slate-500 font-mono">
                  MRN: {{ order.patient?.mrn }} | {{ order.patient?.gender === 'male' ? 'M' : 'F' }}, {{ order.patient?.age }}y
                </div>
              </td>

              <!-- Modality & Procedure -->
              <td class="py-3.5 px-4">
                <div class="flex items-center gap-1.5">
                  <span :class="getModalityBadgeClass(order.modality)" class="px-2 py-0.5 rounded font-black text-[10px] uppercase">
                    {{ order.modality }}
                  </span>
                  <span class="font-semibold text-slate-800">{{ order.procedure_name }}</span>
                </div>
                <div class="text-[10px] text-slate-400 mt-0.5">
                  Anatomy: <strong>{{ order.body_part }}</strong>
                  <span v-if="order.is_pregnant_or_possible" class="ml-1 text-red-600 font-bold">⚠ Pregnancy Alert</span>
                </div>
              </td>

              <!-- Schedule & Room -->
              <td class="py-3.5 px-4">
                <div class="font-medium text-slate-800">{{ formatDate(order.scheduled_at) }}</div>
                <div class="text-[10px] text-slate-500">
                  {{ order.scheduled_room || 'Unassigned Room' }}
                </div>
              </td>

              <!-- Acquisition Status -->
              <td class="py-3.5 px-4">
                <span :class="getOrderStatusBadgeClass(order.status)" class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider">
                  {{ formatStatus(order.status) }}
                </span>
                <div v-if="order.technologist" class="text-[10px] text-slate-400 mt-0.5">
                  Tech: {{ order.technologist }}
                </div>
              </td>

              <!-- Report Status -->
              <td class="py-3.5 px-4 text-center">
                <div v-if="order.latest_report?.critical_alert" class="inline-flex flex-col items-center">
                  <span class="px-2 py-0.5 rounded bg-red-600 text-white font-black text-[10px] uppercase tracking-wider animate-pulse flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    PANIC ALERT
                  </span>
                  <span class="text-[9px] text-red-700 font-semibold mt-0.5">Notified Clinician</span>
                </div>
                <span
                  v-else-if="order.latest_report?.status === 'finalized'"
                  class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px] inline-flex items-center gap-1"
                >
                  <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  Finalized (v{{ order.latest_report.version }})
                </span>
                <span
                  v-else-if="order.latest_report?.status === 'draft'"
                  class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 font-bold text-[10px]"
                >
                  Draft Note
                </span>
                <span v-else class="text-slate-300 font-medium">Unread</span>
              </td>

              <!-- Scans / Image Attachment Badge -->
              <td class="py-3.5 px-4 text-center">
                <button
                  v-if="order.files && order.files.length > 0"
                  type="button"
                  @click="openImageViewer(order)"
                  class="px-2.5 py-1 bg-slate-900 hover:bg-blue-600 text-white font-mono text-[10px] font-bold rounded-lg transition flex items-center gap-1 mx-auto cursor-pointer shadow-sm"
                  title="View Images in Interactive Medical Viewer"
                >
                  <svg class="w-3 h-3 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <span>{{ order.files.length }} Scan(s)</span>
                </button>
                <span v-else class="text-slate-400 text-[10px]">No Scans</span>
              </td>

              <!-- Actions -->
              <td class="py-3.5 px-4 text-right space-x-1.5 whitespace-nowrap">
                <!-- Upload Scans -->
                <button
                  type="button"
                  @click="openUploadModal(order)"
                  class="p-1.5 text-slate-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition cursor-pointer"
                  title="Attach Scans / DICOM Upload"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                  </svg>
                </button>

                <!-- Start Acquisition (if scheduled) -->
                <button
                  v-if="order.status === 'scheduled'"
                  type="button"
                  @click="startAcquisition(order)"
                  class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 font-semibold rounded-lg text-xs transition cursor-pointer"
                >
                  Start
                </button>

                <!-- Complete Acquisition (if in_progress) -->
                <button
                  v-else-if="order.status === 'in_progress'"
                  type="button"
                  @click="completeAcquisition(order)"
                  class="px-2.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 font-semibold rounded-lg text-xs transition cursor-pointer"
                >
                  Acquire Done
                </button>

                <!-- Enter / Edit Report -->
                <button
                  type="button"
                  @click="openReportEntry(order)"
                  :class="order.latest_report?.status === 'finalized' ? 'bg-slate-100 hover:bg-slate-200 text-slate-700' : 'bg-blue-600 hover:bg-blue-700 text-white'"
                  class="px-2.5 py-1 font-bold rounded-lg text-xs transition cursor-pointer shadow-sm"
                >
                  {{ order.latest_report?.status === 'finalized' ? 'Amend' : 'Report' }}
                </button>

                <!-- View Official Report -->
                <button
                  v-if="order.latest_report"
                  type="button"
                  @click="openPrintableReport(order.latest_report)"
                  class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold rounded-lg text-xs transition cursor-pointer border border-emerald-300"
                >
                  PDF
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modals -->
    <UploadScansModal
      :is-open="isUploadModalOpen"
      :order="selectedOrder"
      :branch-id="branchId"
      @close="isUploadModalOpen = false"
      @uploaded="fetchOrders"
    />

    <ImageViewerModal
      :is-open="isViewerModalOpen"
      :order="selectedOrder"
      :files="selectedOrder?.files || []"
      @close="isViewerModalOpen = false"
    />

    <ReportEntryModal
      :is-open="isReportEntryOpen"
      :order="selectedOrder"
      :existing-report="selectedOrder?.latest_report"
      :is-amending="isAmending"
      :branch-id="branchId"
      @close="isReportEntryOpen = false"
      @saved="fetchOrders"
    />

    <PrintableReportModal
      :is-open="isPrintModalOpen"
      :report-data="printableReportData"
      @close="isPrintModalOpen = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import UploadScansModal from './UploadScansModal.vue';
import ImageViewerModal from './ImageViewerModal.vue';
import ReportEntryModal from './ReportEntryModal.vue';
import PrintableReportModal from './PrintableReportModal.vue';

const props = defineProps({
  branchId: { type: String, default: 'b9ff561a-5396-4309-9b08-3e7b358310e9' },
});

const orders = ref([]);
const isLoading = ref(false);
const searchQuery = ref('');
const activeModality = ref('ALL');
const activeStatusFilter = ref('all');

// Modals
const isUploadModalOpen = ref(false);
const isViewerModalOpen = ref(false);
const isReportEntryOpen = ref(false);
const isPrintModalOpen = ref(false);

const selectedOrder = ref(null);
const isAmending = ref(false);
const printableReportData = ref(null);

const modalityTabs = [
  { id: 'ALL', label: 'All Modalities' },
  { id: 'X-Ray', label: 'X-Ray (CR/DX)' },
  { id: 'CT', label: 'Computed Tomography (CT)' },
  { id: 'MRI', label: 'Magnetic Resonance (MRI)' },
  { id: 'Ultrasound', label: 'Ultrasound (US)' },
  { id: 'Mammography', label: 'Mammography (MG)' },
];

const statusTabs = [
  { id: 'all', label: 'All Studies' },
  { id: 'ordered', label: 'Ordered / Pending' },
  { id: 'scheduled', label: 'Scheduled' },
  { id: 'in_progress', label: 'Acquiring' },
  { id: 'completed', label: 'Completed' },
  { id: 'ready_for_report', label: 'Ready for Reading' },
  { id: 'critical', label: 'Critical Alerts' },
];

onMounted(() => {
  fetchOrders();
});

async function fetchOrders() {
  isLoading.value = true;
  try {
    const res = await fetch('/api/v1/radiology/orders?per_page=50', {
      headers: {
        'Accept': 'application/json',
        'X-Branch-ID': props.branchId,
      },
    });

    const json = await res.json();
    if (res.ok && json.data) {
      orders.value = json.data;
    }
  } catch (e) {
    console.error('Failed to fetch imaging worklist', e);
  } finally {
    isLoading.value = false;
  }
}

function getCountByModality(modalityId) {
  if (modalityId === 'ALL') return orders.value.length;
  return orders.value.filter((o) => (o.modality || '').toLowerCase() === modalityId.toLowerCase()).length;
}

const metrics = computed(() => {
  const total = orders.value.length;
  const scheduled = orders.value.filter((o) => o.status === 'scheduled').length;
  const in_progress = orders.value.filter((o) => o.status === 'in_progress').length;
  const ready_for_reading = orders.value.filter((o) => o.status === 'completed' && (!o.latest_report || o.latest_report.status === 'draft')).length;
  const critical = orders.value.filter((o) => o.latest_report?.critical_alert).length;

  return { total, scheduled, in_progress, ready_for_reading, critical };
});

const filteredOrders = computed(() => {
  let list = orders.value;

  // Filter by Modality
  if (activeModality.value !== 'ALL') {
    list = list.filter((o) => (o.modality || '').toLowerCase() === activeModality.value.toLowerCase());
  }

  // Filter by Status Tab
  if (activeStatusFilter.value === 'ordered') {
    list = list.filter((o) => o.status === 'ordered');
  } else if (activeStatusFilter.value === 'scheduled') {
    list = list.filter((o) => o.status === 'scheduled');
  } else if (activeStatusFilter.value === 'in_progress') {
    list = list.filter((o) => o.status === 'in_progress');
  } else if (activeStatusFilter.value === 'completed') {
    list = list.filter((o) => o.status === 'completed');
  } else if (activeStatusFilter.value === 'ready_for_report') {
    list = list.filter((o) => o.status === 'completed' && (!o.latest_report || o.latest_report.status === 'draft'));
  } else if (activeStatusFilter.value === 'critical') {
    list = list.filter((o) => o.latest_report?.critical_alert);
  }

  // Search filter
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter((o) => {
      const pName = o.patient?.name?.toLowerCase() || '';
      const mrn = o.patient?.mrn?.toLowerCase() || '';
      const acc = o.accession_number?.toLowerCase() || '';
      const proc = o.procedure_name?.toLowerCase() || '';
      const doc = o.ordering_doctor?.toLowerCase() || '';
      return pName.includes(q) || mrn.includes(q) || acc.includes(q) || proc.includes(q) || doc.includes(q);
    });
  }

  return list;
});

function openUploadModal(order) {
  selectedOrder.value = order;
  isUploadModalOpen.value = true;
}

function openImageViewer(order) {
  selectedOrder.value = order;
  isViewerModalOpen.value = true;
}

function openReportEntry(order) {
  selectedOrder.value = order;
  isAmending.value = order.latest_report?.status === 'finalized';
  isReportEntryOpen.value = true;
}

async function openPrintableReport(report) {
  try {
    const res = await fetch(`/api/v1/radiology/reports/${report.id}/print`, {
      headers: {
        'Accept': 'application/json',
        'X-Branch-ID': props.branchId,
      },
    });

    const json = await res.json();
    if (res.ok && json.data) {
      printableReportData.value = json.data;
      isPrintModalOpen.value = true;
    }
  } catch (e) {
    console.error(e);
  }
}

async function startAcquisition(order) {
  try {
    const res = await fetch(`/api/v1/radiology/orders/${order.id}/start`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Branch-ID': props.branchId,
      },
    });
    if (res.ok) fetchOrders();
  } catch (e) {
    console.error(e);
  }
}

async function completeAcquisition(order) {
  try {
    const res = await fetch(`/api/v1/radiology/orders/${order.id}/complete`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Branch-ID': props.branchId,
      },
    });
    if (res.ok) fetchOrders();
  } catch (e) {
    console.error(e);
  }
}

function formatDate(isoStr) {
  if (!isoStr) return 'Unscheduled';
  const d = new Date(isoStr);
  return d.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function formatStatus(status) {
  if (!status) return 'Ordered';
  return status.replace(/_/g, ' ');
}

function getPriorityBadgeClass(priority) {
  switch (priority) {
    case 'stat': return 'bg-red-600 text-white';
    case 'urgent': return 'bg-amber-500 text-white';
    default: return 'bg-slate-200 text-slate-700';
  }
}

function getModalityBadgeClass(modality) {
  const m = (modality || '').toUpperCase();
  if (m.includes('CT')) return 'bg-purple-100 text-purple-800';
  if (m.includes('MR')) return 'bg-indigo-100 text-indigo-800';
  if (m.includes('US')) return 'bg-emerald-100 text-emerald-800';
  if (m.includes('MG')) return 'bg-pink-100 text-pink-800';
  return 'bg-blue-100 text-blue-800';
}

function getOrderStatusBadgeClass(status) {
  switch (status) {
    case 'ordered': return 'bg-slate-100 text-slate-700';
    case 'scheduled': return 'bg-indigo-100 text-indigo-800';
    case 'in_progress': return 'bg-amber-100 text-amber-800';
    case 'completed': return 'bg-emerald-100 text-emerald-800';
    case 'cancelled': return 'bg-red-100 text-red-800';
    default: return 'bg-slate-100 text-slate-700';
  }
}
</script>
