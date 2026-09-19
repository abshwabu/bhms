<template>
  <div class="space-y-6">
    <!-- Top Metrics Overview -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
      <div class="p-5 bg-white rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-[10px] font-mono uppercase tracking-wider text-slate-400 font-bold">Active SKUs</div>
        <div class="text-2xl font-black text-slate-900 mt-1">{{ metrics.total_active_drugs || 0 }}</div>
        <div class="text-[11px] text-slate-500 mt-0.5">Catalog Items</div>
      </div>

      <div class="p-5 bg-white rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-[10px] font-mono uppercase tracking-wider text-slate-400 font-bold">Stock on Hand</div>
        <div class="text-2xl font-black text-blue-600 mt-1 font-mono">{{ metrics.total_units_in_stock || 0 }}</div>
        <div class="text-[11px] text-slate-500 mt-0.5">Total Dosage Units</div>
      </div>

      <div class="p-5 bg-white rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-[10px] font-mono uppercase tracking-wider text-slate-400 font-bold">Cost Valuation</div>
        <div class="text-2xl font-black text-slate-900 mt-1 font-mono">
          ${{ ((metrics.total_inventory_cost_value_cents || 0) / 100).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
        </div>
        <div class="text-[11px] text-slate-500 mt-0.5">Inventory Asset Value</div>
      </div>

      <div
        @click="toggleLowStockFilter"
        :class="lowStockOnly ? 'ring-2 ring-amber-500 bg-amber-50/50' : ''"
        class="p-5 bg-white rounded-2xl border border-slate-200 shadow-sm cursor-pointer hover:border-amber-300 transition"
      >
        <div class="text-[10px] font-mono uppercase tracking-wider text-amber-600 font-bold flex items-center justify-between">
          <span>Low Stock Alerts</span>
          <span v-if="metrics.low_stock_count > 0" class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
        </div>
        <div class="text-2xl font-black text-amber-600 mt-1">{{ metrics.low_stock_count || 0 }}</div>
        <div class="text-[11px] text-slate-500 mt-0.5">
          {{ metrics.out_of_stock_count || 0 }} completely depleted
        </div>
      </div>

      <div
        @click="$emit('switch-tab', 'expiry')"
        class="p-5 bg-white rounded-2xl border border-slate-200 shadow-sm cursor-pointer hover:border-rose-300 transition"
      >
        <div class="text-[10px] font-mono uppercase tracking-wider text-rose-600 font-bold flex items-center justify-between">
          <span>Expiring Lots</span>
          <span v-if="metrics.expired_batches_count > 0" class="px-1 py-0.2 rounded bg-rose-100 text-rose-700 text-[9px] font-bold">
            {{ metrics.expired_batches_count }} EXPIRED
          </span>
        </div>
        <div class="text-2xl font-black text-rose-600 mt-1">{{ metrics.near_expiry_batches_count || 0 }}</div>
        <div class="text-[11px] text-slate-500 mt-0.5">Lots &le; 60 days to expiry</div>
      </div>
    </div>

    <!-- Actions & Filter Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
      <div class="flex items-center gap-3 flex-1 max-w-md">
        <div class="relative w-full">
          <input
            v-model="searchQuery"
            @input="filterDrugs"
            type="text"
            placeholder="Filter by drug name, generic name, or SKU..."
            class="w-full text-xs pl-8 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <svg class="w-3.5 h-3.5 absolute left-2.5 top-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>

        <button
          @click="toggleLowStockFilter"
          :class="lowStockOnly ? 'bg-amber-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
          class="px-3.5 py-2.5 rounded-xl text-xs font-semibold whitespace-nowrap transition cursor-pointer"
        >
          Low Stock Only
        </button>
      </div>

      <div class="flex items-center gap-2">
        <button
          @click="isRegisterModalOpen = true"
          class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
        >
          <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Add Drug SKU
        </button>

        <button
          @click="openIntakeModal()"
          class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-lg shadow-blue-500/20 cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
          </svg>
          Intake Stock Lot
        </button>
      </div>
    </div>

    <!-- Drugs Catalog Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div v-if="loading" class="p-12 text-center text-xs text-slate-400">
        Loading inventory catalog...
      </div>
      <div v-else-if="filteredDrugs.length === 0" class="p-12 text-center text-xs text-slate-400">
        No drugs found matching your search.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-600 font-mono uppercase text-[11px] border-b border-slate-200">
            <tr>
              <th class="p-4">SKU / Code</th>
              <th class="p-4">Medication Name</th>
              <th class="p-4">Form & Strength</th>
              <th class="p-4 text-right">Price</th>
              <th class="p-4 text-center">Stock on Hand</th>
              <th class="p-4 text-center">Reorder Threshold</th>
              <th class="p-4 text-center">Status</th>
              <th class="p-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-700">
            <tr v-for="drug in filteredDrugs" :key="drug.id" class="hover:bg-slate-50/60 transition">
              <td class="p-4 font-mono font-bold text-slate-900">{{ drug.sku }}</td>
              <td class="p-4">
                <div class="font-bold text-slate-900">{{ drug.brand_name }}</div>
                <div class="text-[11px] text-slate-400 font-medium">{{ drug.generic_name }}</div>
              </td>
              <td class="p-4">
                <span class="capitalize">{{ drug.form }}</span> &bull;
                <span class="font-mono">{{ drug.strength }}</span>
              </td>
              <td class="p-4 text-right font-mono font-medium">${{ drug.unit_price.toFixed(2) }}</td>
              <td class="p-4 text-center font-mono font-bold text-sm">
                <span :class="drug.total_stock_on_hand === 0 ? 'text-rose-600' : (drug.is_low_stock ? 'text-amber-600' : 'text-slate-800')">
                  {{ drug.total_stock_on_hand }}
                </span>
                <span class="text-[10px] text-slate-400 ml-1 font-normal">{{ drug.unit_of_measure }}s</span>
              </td>
              <td class="p-4 text-center font-mono text-slate-600">
                <span class="px-2 py-0.5 rounded bg-slate-100 font-bold">{{ drug.reorder_threshold }}</span>
              </td>
              <td class="p-4 text-center">
                <span
                  v-if="drug.total_stock_on_hand === 0"
                  class="px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-800 text-[10px] font-bold font-mono uppercase"
                >
                  Out of Stock
                </span>
                <span
                  v-else-if="drug.is_low_stock"
                  class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-bold font-mono uppercase"
                >
                  Low Stock Alert
                </span>
                <span
                  v-else
                  class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold font-mono uppercase"
                >
                  Optimal
                </span>
              </td>
              <td class="p-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    @click="openIntakeModal(drug)"
                    class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-[11px] font-semibold transition"
                  >
                    + Intake Lot
                  </button>
                  <button
                    @click="openEditThresholdModal(drug)"
                    class="px-2 py-1 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 text-[11px] font-medium transition"
                    title="Configure thresholds and pricing"
                  >
                    Edit
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MODAL 1: INTAKE NEW STOCK LOT -->
    <div v-if="isIntakeModalOpen" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
          <div>
            <h3 class="text-base font-bold text-slate-900">Receive Stock Lot (Intake)</h3>
            <p class="text-xs text-slate-500">Record incoming shipment lot for FEFO inventory management.</p>
          </div>
          <button @click="isIntakeModalOpen = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
        </div>

        <form @submit.prevent="submitIntake" class="p-6 space-y-4 text-xs">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Medication SKU</label>
            <select
              v-model="intakeForm.drug_id"
              required
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500"
            >
              <option value="" disabled>Select medication</option>
              <option v-for="d in drugsList" :key="d.id" :value="d.id">
                {{ d.brand_name }} ({{ d.generic_name }} - {{ d.strength }}) [{{ d.sku }}]
              </option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Lot / Batch Number</label>
              <input
                v-model="intakeForm.batch_number"
                type="text"
                required
                placeholder="e.g. LOT-2026-09A"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 font-mono uppercase"
              />
            </div>

            <div>
              <label class="block font-semibold text-slate-700 mb-1">Expiration Date (FEFO)</label>
              <input
                v-model="intakeForm.expiry_date"
                type="date"
                required
                :min="tomorrowDate"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Quantity Received</label>
              <input
                v-model.number="intakeForm.quantity_received"
                type="number"
                min="1"
                required
                placeholder="Units count"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 font-mono"
              />
            </div>

            <div>
              <label class="block font-semibold text-slate-700 mb-1">Unit Cost ($ USD)</label>
              <input
                v-model.number="intakeForm.unit_cost"
                type="number"
                step="0.01"
                min="0"
                placeholder="e.g. 0.15"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 font-mono"
              />
            </div>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Supplier / Distributor</label>
            <input
              v-model="intakeForm.supplier_name"
              type="text"
              placeholder="e.g. McKesson / Cardinal Health"
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
            <button
              type="button"
              @click="isIntakeModalOpen = false"
              class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-medium"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="submittingIntake"
              class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold transition shadow-md shadow-blue-500/20"
            >
              {{ submittingIntake ? 'Recording...' : 'Complete Intake' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 2: EDIT REORDER THRESHOLD & PRICING -->
    <div v-if="isEditThresholdModalOpen && editingDrug" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
          <div>
            <h3 class="text-base font-bold text-slate-900">Configure Stock Settings</h3>
            <p class="text-xs text-slate-500">{{ editingDrug.brand_name }} ({{ editingDrug.sku }})</p>
          </div>
          <button @click="isEditThresholdModalOpen = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
        </div>

        <form @submit.prevent="submitThresholdEdit" class="p-6 space-y-4 text-xs">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Reorder Alert Threshold (Units)</label>
            <input
              v-model.number="thresholdForm.reorder_threshold"
              type="number"
              min="0"
              required
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 font-mono"
            />
            <p class="text-[10px] text-slate-500 mt-1">When stock drops to or below this amount, low-stock warnings fire.</p>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Target Stock Level</label>
            <input
              v-model.number="thresholdForm.target_stock_level"
              type="number"
              min="1"
              required
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 font-mono"
            />
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Unit Selling Price ($ USD)</label>
            <input
              v-model.number="thresholdForm.unit_price"
              type="number"
              step="0.01"
              min="0"
              required
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 font-mono"
            />
          </div>

          <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
            <button
              type="button"
              @click="isEditThresholdModalOpen = false"
              class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-medium"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold transition shadow-md shadow-blue-500/20"
            >
              Save Configuration
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { showAlert } from '../../Services/modalDialog';

const props = defineProps({
  branchId: {
    type: String,
    required: true,
  },
});

const emit = defineEmits(['switch-tab']);

const drugsList = ref([]);
const filteredDrugs = ref([]);
const metrics = ref({});
const searchQuery = ref('');
const lowStockOnly = ref(false);
const loading = ref(false);

const isIntakeModalOpen = ref(false);
const submittingIntake = ref(false);
const intakeForm = ref({
  drug_id: '',
  batch_number: '',
  expiry_date: '',
  quantity_received: 100,
  unit_cost: 0.15,
  supplier_name: '',
});

const isEditThresholdModalOpen = ref(false);
const editingDrug = ref(null);
const thresholdForm = ref({
  reorder_threshold: 50,
  target_stock_level: 200,
  unit_price: 0.5,
});

const tomorrowDate = computed(() => {
  const d = new Date();
  d.setDate(d.getDate() + 1);
  return d.toISOString().split('T')[0];
});

onMounted(() => {
  loadData();
});

async function loadData() {
  loading.value = true;
  await Promise.all([fetchDrugs(), fetchMetrics()]);
  loading.value = false;
}

async function fetchDrugs() {
  try {
    const res = await fetch(`/api/v1/pharmacy/drugs`, {
      headers: {
        'Accept': 'application/json',
        'X-Branch-ID': props.branchId,
      },
    });
    const json = await res.json();
    if (json.success) {
      drugsList.value = json.data;
      filterDrugs();
    }
  } catch (e) {
    console.error('Drugs fetch error:', e);
  }
}

async function fetchMetrics() {
  try {
    const res = await fetch(`/api/v1/pharmacy/drugs/metrics`, {
      headers: {
        'Accept': 'application/json',
        'X-Branch-ID': props.branchId,
      },
    });
    const json = await res.json();
    if (json.success) {
      metrics.value = json.data;
    }
  } catch (e) {
    console.error('Metrics fetch error:', e);
  }
}

function filterDrugs() {
  let list = drugsList.value;

  if (lowStockOnly.value) {
    list = list.filter(d => d.is_low_stock);
  }

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter(d =>
      d.brand_name.toLowerCase().includes(q) ||
      d.generic_name.toLowerCase().includes(q) ||
      d.sku.toLowerCase().includes(q)
    );
  }

  filteredDrugs.value = list;
}

function toggleLowStockFilter() {
  lowStockOnly.value = !lowStockOnly.value;
  filterDrugs();
}

function openIntakeModal(drug = null) {
  intakeForm.value = {
    drug_id: drug ? drug.id : (drugsList.value[0]?.id || ''),
    batch_number: 'LOT-' + new Date().getFullYear() + '-' + Math.random().toString(36).substring(2, 7).toUpperCase(),
    expiry_date: '',
    quantity_received: 100,
    unit_cost: drug ? (drug.unit_cost_cents / 100) : 0.20,
    supplier_name: 'Primary Pharma Wholesaler',
  };
  isIntakeModalOpen.value = true;
}

async function submitIntake() {
  submittingIntake.value = true;
  try {
    const res = await fetch('/api/v1/pharmacy/stock/intake', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Branch-ID': props.branchId,
      },
      body: JSON.stringify({
        drug_id: intakeForm.value.drug_id,
        batch_number: intakeForm.value.batch_number,
        expiry_date: intakeForm.value.expiry_date,
        quantity_received: intakeForm.value.quantity_received,
        unit_cost_cents: Math.round((intakeForm.value.unit_cost || 0) * 100),
        supplier_name: intakeForm.value.supplier_name,
        performed_by: '00000000-0000-0000-0000-000000000000', // fallback if non-auth demo
      }),
    });

    const json = await res.json();
    if (json.success) {
      await showAlert(`Intake recorded for Lot #${json.data.batch_number}`);
      isIntakeModalOpen.value = false;
      await loadData();
    } else {
      await showAlert(json.message || 'Intake failed.');
    }
  } catch (e) {
    await showAlert('Failed to submit intake.');
  } finally {
    submittingIntake.value = false;
  }
}

function openEditThresholdModal(drug) {
  editingDrug.value = drug;
  thresholdForm.value = {
    reorder_threshold: drug.reorder_threshold,
    target_stock_level: drug.target_stock_level,
    unit_price: drug.unit_price,
  };
  isEditThresholdModalOpen.value = true;
}

async function submitThresholdEdit() {
  if (!editingDrug.value) return;

  try {
    const res = await fetch(`/api/v1/pharmacy/drugs/${editingDrug.value.id}`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Branch-ID': props.branchId,
      },
      body: JSON.stringify({
        reorder_threshold: thresholdForm.value.reorder_threshold,
        target_stock_level: thresholdForm.value.target_stock_level,
        unit_price_cents: Math.round(thresholdForm.value.unit_price * 100),
      }),
    });

    const json = await res.json();
    if (json.success) {
      isEditThresholdModalOpen.value = false;
      await loadData();
    } else {
      await showAlert(json.message || 'Update failed.');
    }
  } catch (e) {
    await showAlert('Failed to update drug settings.');
  }
}
</script>
