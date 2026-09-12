<template>
  <div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2 text-xs font-mono uppercase tracking-wider text-purple-600 font-bold">
          <span class="w-2 h-2 rounded-full bg-purple-500"></span>
          First-Expiry-First-Out (FEFO) Surveillance
        </div>
        <h1 class="text-xl font-black text-slate-900 mt-1">Batch & Expiry Surveillance Console</h1>
        <p class="text-xs text-slate-500 mt-0.5">Strict monitoring of drug lots, expiration countdowns, and automated dispensing prioritization.</p>
      </div>

      <div class="flex items-center gap-2">
        <select
          v-model="filterDays"
          @change="fetchAlerts"
          class="text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 font-semibold"
        >
          <option :value="30">Within 30 Days (Critical)</option>
          <option :value="60">Within 60 Days (Standard)</option>
          <option :value="90">Within 90 Days (Extended)</option>
          <option :value="180">Within 180 Days (Half Year)</option>
        </select>
        <button
          @click="fetchAlerts"
          class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition cursor-pointer"
        >
          Refresh
        </button>
      </div>
    </div>

    <!-- Expiry Severity Quick Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="p-5 bg-rose-50 border border-rose-200 rounded-2xl">
        <div class="flex items-center justify-between">
          <span class="text-xs font-mono uppercase font-bold text-rose-800">Expired Lots (Blocked)</span>
          <span class="px-2 py-0.5 rounded-full bg-rose-200 text-rose-900 text-[10px] font-bold">STRICT BLOCK</span>
        </div>
        <div class="text-2xl font-black text-rose-700 mt-2 font-mono">{{ expiredCount }} Lots</div>
        <p class="text-[11px] text-rose-600 mt-1">Automatically excluded from dispensing algorithms.</p>
      </div>

      <div class="p-5 bg-orange-50 border border-orange-200 rounded-2xl">
        <div class="flex items-center justify-between">
          <span class="text-xs font-mono uppercase font-bold text-orange-800">Expiring &le; 30 Days</span>
          <span class="px-2 py-0.5 rounded-full bg-orange-200 text-orange-900 text-[10px] font-bold">FEFO PRIORITY</span>
        </div>
        <div class="text-2xl font-black text-orange-700 mt-2 font-mono">{{ criticalCount }} Lots</div>
        <p class="text-[11px] text-orange-600 mt-1">Dispensing algorithms allocate these lots first.</p>
      </div>

      <div class="p-5 bg-amber-50 border border-amber-200 rounded-2xl">
        <div class="flex items-center justify-between">
          <span class="text-xs font-mono uppercase font-bold text-amber-800">Expiring 31 - {{ filterDays }} Days</span>
          <span class="px-2 py-0.5 rounded-full bg-amber-200 text-amber-900 text-[10px] font-bold">WARNING</span>
        </div>
        <div class="text-2xl font-black text-amber-700 mt-2 font-mono">{{ warningCount }} Lots</div>
        <p class="text-[11px] text-amber-600 mt-1">Under routine surveillance for inventory turnover.</p>
      </div>
    </div>

    <!-- Batches Tracking Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="text-sm font-bold text-slate-900">Tracked Lots & FEFO Disposition</h2>
        <span class="text-xs text-slate-400 font-mono">Total Lots Flagged: {{ batchAlerts.length }}</span>
      </div>

      <div v-if="loading" class="p-12 text-center text-xs text-slate-400">
        Scanning active drug batches...
      </div>
      <div v-else-if="batchAlerts.length === 0" class="p-12 text-center text-xs text-slate-400">
        &check; No lots expiring within the selected {{ filterDays }}-day window. All current batches are optimal.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-600 font-mono uppercase text-[11px] border-b border-slate-200">
            <tr>
              <th class="p-4">Lot / Batch #</th>
              <th class="p-4">Medication & SKU</th>
              <th class="p-4 text-center">Stock Remaining</th>
              <th class="p-4">Expiry Date</th>
              <th class="p-4">Countdown</th>
              <th class="p-4 text-center">Status & FEFO Priority</th>
              <th class="p-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-700">
            <tr v-for="lot in batchAlerts" :key="lot.batch_id" class="hover:bg-slate-50/60 transition">
              <td class="p-4 font-mono font-bold text-slate-900">{{ lot.batch_number }}</td>
              <td class="p-4">
                <div class="font-bold text-slate-900">{{ lot.drug_name }}</div>
                <div class="text-[11px] text-slate-400 font-mono">{{ lot.generic_name }} ({{ lot.drug_sku }})</div>
              </td>
              <td class="p-4 text-center font-mono font-bold text-slate-800">
                {{ lot.quantity_on_hand }} units
              </td>
              <td class="p-4 font-mono">{{ lot.expiry_date }}</td>
              <td class="p-4 font-mono font-bold">
                <span v-if="lot.status_level === 'expired'" class="text-rose-600">
                  EXPIRED ({{ Math.abs(lot.days_remaining) }}d ago)
                </span>
                <span v-else-if="lot.days_remaining <= 30" class="text-orange-600">
                  {{ lot.days_remaining }} days left
                </span>
                <span v-else class="text-amber-600">
                  {{ lot.days_remaining }} days left
                </span>
              </td>
              <td class="p-4 text-center">
                <span
                  v-if="lot.status_level === 'expired'"
                  class="px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-800 text-[10px] font-bold font-mono uppercase"
                >
                  Blocked from Dispensing
                </span>
                <span
                  v-else-if="lot.status_level === 'critical_near_expiry'"
                  class="px-2.5 py-0.5 rounded-full bg-orange-100 text-orange-800 text-[10px] font-bold font-mono uppercase"
                >
                  Priority FEFO Lot
                </span>
                <span
                  v-else
                  class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-bold font-mono uppercase"
                >
                  Near Expiry Notice
                </span>
              </td>
              <td class="p-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    @click="quarantineBatch(lot)"
                    class="px-2.5 py-1 rounded-lg border border-amber-300 text-amber-800 hover:bg-amber-50 text-[11px] font-semibold transition"
                    title="Isolate batch so it cannot be dispensed"
                  >
                    Quarantine
                  </button>
                  <button
                    @click="openWriteOffModal(lot)"
                    class="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 text-[11px] font-semibold transition"
                    title="Write off expired or damaged units"
                  >
                    Write-Off Waste
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- WRITE-OFF MODAL -->
    <div v-if="isWriteOffModalOpen && writeOffTarget" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
          <div>
            <h3 class="text-base font-bold text-slate-900">Waste Stock Write-Off</h3>
            <p class="text-xs text-slate-500">Batch {{ writeOffTarget.batch_number }} ({{ writeOffTarget.drug_name }})</p>
          </div>
          <button @click="isWriteOffModalOpen = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
        </div>

        <form @submit.prevent="submitWriteOff" class="p-6 space-y-4 text-xs">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Quantity to Deduct (Max: {{ writeOffTarget.quantity_on_hand }})</label>
            <input
              v-model.number="writeOffForm.quantity"
              type="number"
              min="1"
              :max="writeOffTarget.quantity_on_hand"
              required
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500 font-mono"
            />
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Disposal Reason</label>
            <select
              v-model="writeOffForm.reason"
              required
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500"
            >
              <option value="Expired Lot disposal per hazardous medical waste protocol">Expired Lot disposal</option>
              <option value="Damaged seal / contamination during storage">Damaged seal / contamination</option>
              <option value="Manufacturer voluntary recall">Manufacturer voluntary recall</option>
              <option value="Inventory audit reconciliation discrepancy">Inventory audit discrepancy</option>
            </select>
          </div>

          <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
            <button
              type="button"
              @click="isWriteOffModalOpen = false"
              class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-medium"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold transition shadow-md shadow-rose-500/20"
            >
              Confirm Write-Off
            </button>
          </div>
        </form>
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

const filterDays = ref(60);
const batchAlerts = ref([]);
const loading = ref(false);

const isWriteOffModalOpen = ref(false);
const writeOffTarget = ref(null);
const writeOffForm = ref({
  quantity: 1,
  reason: 'Expired Lot disposal per hazardous medical waste protocol',
});

const expiredCount = computed(() =>
  batchAlerts.value.filter(b => b.status_level === 'expired').length
);

const criticalCount = computed(() =>
  batchAlerts.value.filter(b => b.status_level === 'critical_near_expiry').length
);

const warningCount = computed(() =>
  batchAlerts.value.filter(b => b.status_level === 'warning_near_expiry').length
);

onMounted(() => {
  fetchAlerts();
});

async function fetchAlerts() {
  loading.value = true;
  try {
    const res = await fetch(`/api/v1/pharmacy/drugs/alerts/expiring?days=${filterDays.value}`, {
      headers: {
        'Accept': 'application/json',
        'X-Branch-ID': props.branchId,
      },
    });
    const json = await res.json();
    if (json.success) {
      batchAlerts.value = json.data;
    }
  } catch (e) {
    console.error('Expiry alerts fetch error:', e);
  } finally {
    loading.value = false;
  }
}

async function quarantineBatch(lot) {
  if (!confirm(`Are you sure you want to quarantine batch '${lot.batch_number}'? It will immediately be excluded from all prescription dispensing.`)) {
    return;
  }

  try {
    const res = await fetch('/api/v1/pharmacy/stock/quarantine', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Branch-ID': props.branchId,
      },
      body: JSON.stringify({
        drug_batch_id: lot.batch_id,
        reason: 'Quarantined via FEFO Expiry Surveillance Console',
        performed_by: '00000000-0000-0000-0000-000000000000',
      }),
    });

    const json = await res.json();
    if (json.success) {
      alert(`Batch '${lot.batch_number}' has been quarantined.`);
      await fetchAlerts();
    } else {
      alert(json.message || 'Quarantine failed.');
    }
  } catch (e) {
    alert('Failed to quarantine batch.');
  }
}

function openWriteOffModal(lot) {
  writeOffTarget.value = lot;
  writeOffForm.value = {
    quantity: lot.quantity_on_hand,
    reason: lot.status_level === 'expired'
      ? 'Expired Lot disposal per hazardous medical waste protocol'
      : 'Damaged seal / contamination during storage',
  };
  isWriteOffModalOpen.value = true;
}

async function submitWriteOff() {
  if (!writeOffTarget.value) return;

  try {
    const res = await fetch('/api/v1/pharmacy/stock/write-off', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Branch-ID': props.branchId,
      },
      body: JSON.stringify({
        drug_batch_id: writeOffTarget.value.batch_id,
        quantity: writeOffForm.value.quantity,
        reason: writeOffForm.value.reason,
        performed_by: '00000000-0000-0000-0000-000000000000',
      }),
    });

    const json = await res.json();
    if (json.success) {
      alert(`Wasted units written off successfully.`);
      isWriteOffModalOpen.value = false;
      await fetchAlerts();
    } else {
      alert(json.message || 'Write off failed.');
    }
  } catch (e) {
    alert('Failed to process write-off.');
  }
}
</script>
