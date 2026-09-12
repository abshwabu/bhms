<template>
  <div class="space-y-6">
    <!-- Header with Tabs and Search -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
      <div>
        <div class="flex items-center gap-2 text-xs font-mono uppercase tracking-wider text-emerald-600 font-bold">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          Clinical Dispensing Unit
        </div>
        <h1 class="text-xl font-black text-slate-900 mt-1">Prescription Dispensing Station</h1>
        <p class="text-xs text-slate-500 mt-0.5">FEFO batch allocation, inventory decrementing, and secondary drug safety checks.</p>
      </div>

      <!-- Station Navigation Tabs -->
      <div class="flex items-center bg-slate-100 p-1 rounded-xl text-xs font-semibold">
        <button
          @click="activeSubTab = 'queue'"
          :class="activeSubTab === 'queue' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
          class="px-4 py-2 rounded-lg transition flex items-center gap-2 cursor-pointer"
        >
          <span>Pending Queue</span>
          <span v-if="queueList.length > 0" class="px-1.5 py-0.5 rounded-full bg-blue-100 text-blue-700 text-[10px] font-bold">
            {{ queueList.length }}
          </span>
        </button>
        <button
          @click="fetchHistory(); activeSubTab = 'history'"
          :class="activeSubTab === 'history' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
          class="px-4 py-2 rounded-lg transition flex items-center gap-2 cursor-pointer"
        >
          <span>Dispensation History</span>
        </button>
      </div>
    </div>

    <!-- TAB 1: PENDING QUEUE & DISPENSING WORKBENCH -->
    <div v-if="activeSubTab === 'queue'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- Left Column: Pending Prescriptions Queue -->
      <div class="lg:col-span-5 space-y-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
              <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
              Prescriptions to Dispense
            </h2>
            <button @click="fetchQueue" class="text-xs text-blue-600 hover:text-blue-800 font-medium">
              Refresh
            </button>
          </div>

          <!-- Search Input -->
          <div class="relative">
            <input
              v-model="searchQuery"
              @input="filterQueue"
              type="text"
              placeholder="Search by Rx #, Patient MRN or Name..."
              class="w-full text-xs pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <svg class="w-3.5 h-3.5 absolute left-2.5 top-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
        </div>

        <!-- Prescriptions List -->
        <div v-if="loadingQueue" class="p-8 text-center bg-white rounded-2xl border border-slate-200 text-xs text-slate-400">
          Loading dispensing queue...
        </div>
        <div v-else-if="filteredQueue.length === 0" class="p-8 text-center bg-white rounded-2xl border border-slate-200 text-xs text-slate-400">
          No prescriptions currently awaiting dispensing.
        </div>
        <div v-else class="space-y-3 max-h-[calc(100vh-280px)] overflow-y-auto pr-1">
          <div
            v-for="rx in filteredQueue"
            :key="rx.id"
            @click="selectPrescription(rx)"
            :class="selectedRx?.id === rx.id ? 'border-blue-600 ring-2 ring-blue-50 bg-blue-50/20' : 'border-slate-200 hover:border-slate-300 bg-white'"
            class="p-4 rounded-2xl border transition cursor-pointer shadow-sm relative group"
          >
            <div class="flex items-start justify-between">
              <div>
                <div class="flex items-center gap-2">
                  <span class="font-mono font-bold text-xs text-slate-900">{{ rx.prescription_number }}</span>
                  <span
                    :class="rx.status === 'finalized' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800'"
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                  >
                    {{ rx.status }}
                  </span>
                </div>
                <div class="font-bold text-sm text-slate-800 mt-1">
                  {{ rx.patient?.name || (rx.patient?.first_name + ' ' + rx.patient?.last_name) }}
                </div>
                <div class="text-[11px] text-slate-500 font-mono">
                  MRN: {{ rx.patient?.mrn }} &bull; Dr: {{ rx.doctor?.name || 'Attending Physician' }}
                </div>
              </div>
              <div class="text-right text-[10px] text-slate-400 font-mono">
                {{ formatDate(rx.prescribed_at) }}
              </div>
            </div>

            <!-- Items summary chips -->
            <div class="mt-3 flex flex-wrap gap-1.5 pt-2 border-t border-slate-100">
              <span
                v-for="item in rx.items"
                :key="item.id"
                class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[11px] font-medium"
              >
                {{ item.medication_name }} ({{ item.quantity }})
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Dispensing Workbench & Safety Preview -->
      <div class="lg:col-span-7">
        <div v-if="!selectedRx" class="bg-white p-12 rounded-2xl border border-dashed border-slate-300 text-center flex flex-col items-center justify-center min-h-[400px]">
          <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mb-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
            </svg>
          </div>
          <h3 class="font-bold text-slate-800 text-sm">No Prescription Selected</h3>
          <p class="text-xs text-slate-500 max-w-sm mt-1">Select a prescription from the queue on the left to inspect batch availability, verify drug safety, and dispense.</p>
        </div>

        <div v-else class="space-y-6">
          <!-- Prescription Details Header -->
          <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
              <div>
                <div class="flex items-center gap-2 font-mono text-xs text-slate-500">
                  <span>DISPENSING ORDER</span>
                  <span>&bull;</span>
                  <span class="font-bold text-slate-900">{{ selectedRx.prescription_number }}</span>
                </div>
                <h2 class="text-lg font-black text-slate-900 mt-1">
                  {{ previewData?.prescription?.patient?.full_name || selectedRx.patient?.name }}
                </h2>
                <div class="flex items-center gap-3 text-xs text-slate-500 mt-1">
                  <span>MRN: <strong class="font-mono text-slate-700">{{ previewData?.prescription?.patient?.mrn || selectedRx.patient?.mrn }}</strong></span>
                  <span>&bull;</span>
                  <span>Prescribing Doctor: <strong class="text-slate-700">{{ selectedRx.doctor?.name }}</strong></span>
                </div>
              </div>

              <!-- Known Allergies Badge -->
              <div v-if="previewData?.prescription?.patient?.allergies?.length" class="bg-rose-50 border border-rose-200 p-2.5 rounded-xl">
                <div class="text-[10px] uppercase font-bold text-rose-700 font-mono tracking-wider flex items-center gap-1">
                  <svg class="w-3 h-3 text-rose-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                  </svg>
                  Patient Allergies
                </div>
                <div class="text-xs font-semibold text-rose-900 mt-0.5">
                  {{ previewData.prescription.patient.allergies.map(a => a.allergen).join(', ') }}
                </div>
              </div>
            </div>
          </div>

          <!-- Drug Safety & Interaction Alerts Banner -->
          <div v-if="previewData?.has_interaction_warnings" class="bg-amber-50 border border-amber-200 p-5 rounded-2xl space-y-3">
            <div class="flex items-center gap-2 text-amber-800 font-bold text-xs uppercase tracking-wider font-mono">
              <svg class="w-4 h-4 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
              Secondary Safety & Drug-Drug Interaction Warnings (CDS)
            </div>
            <div
              v-for="(alert, idx) in previewData.interaction_alerts"
              :key="idx"
              :class="alert.severity === 'high' ? 'bg-rose-100/70 border-rose-200 text-rose-900' : 'bg-amber-100/70 border-amber-200 text-amber-900'"
              class="p-3.5 rounded-xl border text-xs space-y-1"
            >
              <div class="font-bold flex items-center justify-between">
                <span>{{ alert.title }}</span>
                <span class="uppercase text-[10px] font-mono px-2 py-0.5 rounded bg-white font-bold">
                  {{ alert.severity }}
                </span>
              </div>
              <p class="text-[11px] leading-relaxed opacity-90">{{ alert.description }}</p>
              <div class="text-[11px] font-semibold text-slate-700 pt-1">
                Recommendation: {{ alert.recommendation }}
              </div>
            </div>
          </div>

          <!-- Items & FEFO Batch Allocation Plan -->
          <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                FEFO Batch Decrementing Plan
              </h3>
              <span class="text-xs text-slate-400 font-mono">First-Expiry-First-Out</span>
            </div>

            <div v-if="loadingPreview" class="p-6 text-center text-xs text-slate-400 font-mono">
              Computing FEFO batch breakdown...
            </div>

            <div v-else class="space-y-4">
              <div
                v-for="item in previewData?.items"
                :key="item.prescription_item_id"
                class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-3"
              >
                <div class="flex items-start justify-between">
                  <div>
                    <div class="font-bold text-sm text-slate-900">{{ item.medication_name }}</div>
                    <div class="text-xs text-slate-500 font-mono">
                      {{ item.dosage }} &bull; {{ item.frequency }} &bull; {{ item.instructions }}
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="text-xs text-slate-400">Prescribed:</div>
                    <div class="text-sm font-black text-slate-900 font-mono">{{ item.prescribed_quantity }} units</div>
                  </div>
                </div>

                <!-- Catalog Drug Mapping & Stock Availability -->
                <div class="p-2.5 rounded-lg bg-white border border-slate-200 text-xs flex items-center justify-between">
                  <div v-if="item.drug">
                    <span class="text-slate-500">Mapped SKU:</span>
                    <strong class="font-mono text-slate-800 ml-1">{{ item.drug.sku }}</strong>
                    <span class="text-slate-400 mx-1">({{ item.drug.brand_name }})</span>
                  </div>
                  <div v-else class="text-rose-600 font-semibold flex items-center gap-1">
                    <span>Unmapped drug SKU!</span>
                  </div>

                  <div class="flex items-center gap-2">
                    <span
                      :class="item.is_in_stock ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase font-mono"
                    >
                      {{ item.is_in_stock ? 'In Stock' : 'Stock Shortage' }}
                    </span>
                    <span class="font-mono text-slate-500 text-[11px]">
                      Avail: {{ item.available_stock }}
                    </span>
                  </div>
                </div>

                <!-- FEFO Lots to be deducted -->
                <div v-if="item.fefo_batch_allocations?.length > 0" class="space-y-1.5">
                  <div class="text-[10px] font-mono text-slate-400 uppercase tracking-wider font-bold">
                    Allocated Lots (Sorted by earliest expiry):
                  </div>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div
                      v-for="alloc in item.fefo_batch_allocations"
                      :key="alloc.batch_id"
                      class="p-2.5 rounded-lg bg-emerald-50/60 border border-emerald-200 text-xs flex items-center justify-between"
                    >
                      <div>
                        <div class="font-mono font-bold text-slate-800">{{ alloc.batch_number }}</div>
                        <div class="text-[10px] text-emerald-700 font-mono">
                          Expires: {{ alloc.expiry_date }} (in {{ alloc.days_until_expiry }}d)
                        </div>
                      </div>
                      <div class="text-right">
                        <span class="text-[10px] text-slate-400 block font-mono">Deduct</span>
                        <strong class="font-mono text-emerald-800 text-xs">-{{ alloc.quantity_to_deduct }} units</strong>
                      </div>
                    </div>
                  </div>
                </div>
                <div v-else-if="!item.is_in_stock" class="text-xs text-rose-600 bg-rose-50 p-2.5 rounded-lg font-medium">
                  Insufficient non-expired active stock available to dispense this item.
                </div>
              </div>
            </div>
          </div>

          <!-- Pharmacist Notes & Counseling -->
          <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-slate-900">Dispensing & Patient Counseling Documentation</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Pharmacist Clinical Notes</label>
                <textarea
                  v-model="pharmacistNotes"
                  rows="3"
                  placeholder="Verification details, safety overrides, dosage confirmation..."
                  class="w-full text-xs p-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
                ></textarea>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Patient Counseling Given</label>
                <textarea
                  v-model="counselingNotes"
                  rows="3"
                  placeholder="Administration instructions, food interactions, adherence counseling..."
                  class="w-full text-xs p-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500"
                ></textarea>
              </div>
            </div>

            <!-- Dispense Action Bar -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
              <div class="text-xs text-slate-500">
                <span v-if="previewData?.has_stock_shortage" class="text-rose-600 font-bold">
                  &bull; Cannot dispense: One or more medications are out of stock.
                </span>
                <span v-else class="text-emerald-600 font-medium">
                  &bull; All medications verified and batch allocation confirmed.
                </span>
              </div>

              <button
                @click="executeDispensation"
                :disabled="isDispensing || previewData?.has_stock_shortage"
                :class="isDispensing || previewData?.has_stock_shortage ? 'opacity-50 cursor-not-allowed bg-slate-400' : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-lg shadow-emerald-500/20 cursor-pointer'"
                class="px-6 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2"
              >
                <svg v-if="!isDispensing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ isDispensing ? 'Processing Dispensation...' : 'Confirm & Complete Dispensation' }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 2: DISPENSATION HISTORY -->
    <div v-else-if="activeSubTab === 'history'" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-6 border-b border-slate-100 flex items-center justify-between">
        <div>
          <h2 class="text-base font-bold text-slate-900">Dispensation Audit History</h2>
          <p class="text-xs text-slate-500">Log of all completed pharmacy dispensations, batch allocations, and warnings.</p>
        </div>
        <button @click="fetchHistory" class="text-xs text-blue-600 font-semibold hover:text-blue-800">
          Refresh History
        </button>
      </div>

      <div v-if="loadingHistory" class="p-12 text-center text-xs text-slate-400">
        Loading historical records...
      </div>
      <div v-else-if="historyList.length === 0" class="p-12 text-center text-xs text-slate-400">
        No completed dispensations found.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-600 font-mono uppercase text-[11px] border-b border-slate-200">
            <tr>
              <th class="p-4">Dispensation #</th>
              <th class="p-4">Patient</th>
              <th class="p-4">Prescription</th>
              <th class="p-4">Items Dispensed</th>
              <th class="p-4">Pharmacist</th>
              <th class="p-4">Timestamp</th>
              <th class="p-4 text-center">Safety Warnings</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-700">
            <tr v-for="rec in historyList" :key="rec.id" class="hover:bg-slate-50/60 transition">
              <td class="p-4 font-mono font-bold text-slate-900">{{ rec.dispensation_number }}</td>
              <td class="p-4">
                <div class="font-bold text-slate-900">{{ rec.patient?.full_name || (rec.patient?.first_name + ' ' + rec.patient?.last_name) }}</div>
                <div class="text-[10px] text-slate-400 font-mono">MRN: {{ rec.patient?.mrn }}</div>
              </td>
              <td class="p-4 font-mono text-slate-600">{{ rec.prescription?.prescription_number }}</td>
              <td class="p-4">
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="item in rec.items"
                    :key="item.id"
                    class="px-2 py-0.5 rounded bg-slate-100 text-slate-800 text-[10px] font-medium"
                  >
                    {{ item.drug?.brand_name || 'Drug' }} ({{ item.quantity_dispensed }}x)
                  </span>
                </div>
              </td>
              <td class="p-4 font-medium">{{ rec.pharmacist?.name || 'Staff Pharmacist' }}</td>
              <td class="p-4 font-mono text-slate-400">{{ formatDate(rec.dispensed_at) }}</td>
              <td class="p-4 text-center">
                <span
                  v-if="rec.has_interaction_warnings"
                  class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-bold font-mono"
                >
                  Alerts Archived
                </span>
                <span v-else class="text-emerald-600 font-bold text-xs">&check; Clean</span>
              </td>
            </tr>
          </tbody>
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
    required: true,
  },
});

const activeSubTab = ref('queue');
const queueList = ref([]);
const filteredQueue = ref([]);
const historyList = ref([]);
const searchQuery = ref('');
const loadingQueue = ref(false);
const loadingPreview = ref(false);
const loadingHistory = ref(false);
const isDispensing = ref(false);

const selectedRx = ref(null);
const previewData = ref(null);
const pharmacistNotes = ref('');
const counselingNotes = ref('');

onMounted(() => {
  fetchQueue();
});

async function fetchQueue() {
  loadingQueue.value = true;
  try {
    const res = await fetch(`/api/v1/pharmacy/dispensing/queue`, {
      headers: {
        'Accept': 'application/json',
        'X-Branch-ID': props.branchId,
      },
    });
    const json = await res.json();
    if (json.success) {
      queueList.value = json.data;
      filterQueue();
    }
  } catch (e) {
    console.error('Failed to load queue:', e);
  } finally {
    loadingQueue.value = false;
  }
}

function filterQueue() {
  if (!searchQuery.value.trim()) {
    filteredQueue.value = queueList.value;
    return;
  }
  const q = searchQuery.value.toLowerCase();
  filteredQueue.value = queueList.value.filter(rx => {
    const rxNo = rx.prescription_number?.toLowerCase() || '';
    const mrn = rx.patient?.mrn?.toLowerCase() || '';
    const name = (rx.patient?.first_name + ' ' + rx.patient?.last_name).toLowerCase();
    return rxNo.includes(q) || mrn.includes(q) || name.includes(q);
  });
}

async function selectPrescription(rx) {
  selectedRx.value = rx;
  loadingPreview.value = true;
  previewData.value = null;
  pharmacistNotes.value = '';
  counselingNotes.value = '';

  try {
    const res = await fetch(`/api/v1/pharmacy/dispensing/preview/${rx.id}`, {
      headers: {
        'Accept': 'application/json',
        'X-Branch-ID': props.branchId,
      },
    });
    const json = await res.json();
    if (json.success) {
      previewData.value = json.data;
    } else {
      alert(json.message || 'Error loading preview');
    }
  } catch (e) {
    console.error('Preview error:', e);
  } finally {
    loadingPreview.value = false;
  }
}

async function executeDispensation() {
  if (!selectedRx.value) return;

  isDispensing.value = true;
  try {
    const res = await fetch('/api/v1/pharmacy/dispensing', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Branch-ID': props.branchId,
      },
      body: JSON.stringify({
        prescription_id: selectedRx.value.id,
        pharmacist_notes: pharmacistNotes.value,
        counseling_notes: counselingNotes.value,
      }),
    });

    const json = await res.json();
    if (json.success) {
      alert(`Success! Prescription dispensed under #${json.data.dispensation_number}`);
      selectedRx.value = null;
      previewData.value = null;
      await fetchQueue();
    } else {
      alert(json.message || 'Dispensing failed.');
    }
  } catch (e) {
    alert('Dispensing execution failed. Please check network and inventory.');
  } finally {
    isDispensing.value = false;
  }
}

async function fetchHistory() {
  loadingHistory.value = true;
  try {
    const res = await fetch(`/api/v1/pharmacy/dispensing/history`, {
      headers: {
        'Accept': 'application/json',
        'X-Branch-ID': props.branchId,
      },
    });
    const json = await res.json();
    if (json.success) {
      historyList.value = json.data;
    }
  } catch (e) {
    console.error('Failed to load history:', e);
  } finally {
    loadingHistory.value = false;
  }
}

function formatDate(isoStr) {
  if (!isoStr) return '--';
  return new Date(isoStr).toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}
</script>
