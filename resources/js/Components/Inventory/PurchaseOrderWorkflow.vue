<template>
  <div class="space-y-6">
    <!-- Header & Action Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
      <div>
        <div class="flex items-center gap-2 text-xs font-mono uppercase tracking-wider text-blue-600 font-bold">
          <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
          Procurement & Vendor Management
        </div>
        <h1 class="text-xl font-black text-slate-900 mt-1">Purchase Orders & Receiving</h1>
        <p class="text-xs text-slate-500 mt-0.5">Two-tier approval workflow for supplier purchase orders and warehouse stock receipts.</p>
      </div>

      <div class="flex items-center gap-2">
        <button
          @click="openNewPoModal"
          class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-lg shadow-blue-500/20 cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          + Create Purchase Order
        </button>
      </div>
    </div>

    <!-- Status Tabs & Search Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
      <div class="flex items-center bg-slate-100 p-1 rounded-xl text-xs font-semibold overflow-x-auto">
        <button
          v-for="st in ['all', 'draft', 'submitted', 'approved', 'partially_received', 'received', 'rejected']"
          :key="st"
          @click="statusFilter = st; fetchPurchaseOrders()"
          :class="statusFilter === st ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
          class="px-3 py-1.5 rounded-lg capitalize transition cursor-pointer whitespace-nowrap"
        >
          {{ st.replace('_', ' ') }}
        </button>
      </div>

      <div class="relative w-full sm:w-80">
        <input
          v-model="searchQuery"
          @input="fetchPurchaseOrders"
          type="text"
          placeholder="Search by PO #, Vendor..."
          class="w-full text-xs pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
        <svg class="w-3.5 h-3.5 absolute left-2.5 top-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>
    </div>

    <!-- Purchase Orders Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div v-if="loading" class="p-12 text-center text-xs text-slate-400">
        Loading purchase orders...
      </div>
      <div v-else-if="purchaseOrders.length === 0" class="p-12 text-center text-xs text-slate-400">
        No purchase orders found matching current filter.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-600 font-mono uppercase text-[11px] border-b border-slate-200">
            <tr>
              <th class="p-4">PO Number</th>
              <th class="p-4">Vendor / Supplier</th>
              <th class="p-4">Order Date</th>
              <th class="p-4 text-center">Items</th>
              <th class="p-4 text-right">Total Amount</th>
              <th class="p-4 text-center">Approval State</th>
              <th class="p-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-700">
            <tr v-for="po in purchaseOrders" :key="po.id" class="hover:bg-slate-50/60 transition">
              <td class="p-4 font-mono font-bold text-slate-900">
                <a @click.prevent="openPoDetails(po)" href="#" class="text-blue-600 hover:underline">
                  {{ po.po_number }}
                </a>
              </td>
              <td class="p-4">
                <div class="font-bold text-slate-900">{{ po.vendor?.name }}</div>
                <div class="text-[10px] text-slate-400 font-mono">{{ po.vendor?.vendor_code }} &bull; {{ po.vendor?.email }}</div>
              </td>
              <td class="p-4 font-mono text-slate-600">
                {{ po.order_date }}
              </td>
              <td class="p-4 text-center font-mono font-semibold">
                {{ po.items_count }} SKU line(s)
              </td>
              <td class="p-4 text-right font-mono font-bold text-slate-900 text-sm">
                ${{ po.total.toFixed(2) }}
              </td>
              <td class="p-4 text-center">
                <span
                  :class="{
                    'bg-slate-100 text-slate-700 border-slate-200': po.status === 'draft',
                    'bg-amber-50 text-amber-700 border-amber-200': po.status === 'submitted',
                    'bg-blue-50 text-blue-700 border-blue-200': po.status === 'approved',
                    'bg-purple-50 text-purple-700 border-purple-200': po.status === 'partially_received',
                    'bg-emerald-50 text-emerald-700 border-emerald-200': po.status === 'received',
                    'bg-rose-50 text-rose-700 border-rose-200': po.status === 'rejected'
                  }"
                  class="px-2.5 py-1 rounded-full text-[10px] font-mono uppercase font-bold border"
                >
                  {{ po.status.replace('_', ' ') }}
                </span>
              </td>
              <td class="p-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <!-- Submit Draft -->
                  <button
                    v-if="po.status === 'draft'"
                    @click="submitPo(po)"
                    class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 rounded-lg text-[11px] font-bold transition cursor-pointer"
                  >
                    Submit
                  </button>

                  <!-- Approve or Reject -->
                  <button
                    v-if="po.status === 'submitted'"
                    @click="approvePo(po)"
                    class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[11px] font-bold transition shadow-xs cursor-pointer"
                  >
                    Approve
                  </button>
                  <button
                    v-if="po.status === 'submitted'"
                    @click="openRejectPoModal(po)"
                    class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-[11px] font-bold transition cursor-pointer"
                  >
                    Reject
                  </button>

                  <!-- Receive Stock -->
                  <button
                    v-if="po.can_receive"
                    @click="openReceiveModal(po)"
                    class="px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-[11px] font-bold transition shadow-xs cursor-pointer"
                  >
                    Receive Stock
                  </button>

                  <!-- Details View -->
                  <button
                    @click="openPoDetails(po)"
                    class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-semibold transition cursor-pointer"
                  >
                    View
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal: Create Purchase Order -->
    <div
      v-if="showNewPoModal"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto"
    >
      <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl space-y-4 my-8">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3 class="font-bold text-slate-900 text-base">Create Purchase Order (Draft)</h3>
            <p class="text-xs text-slate-400">Draft an order to initiate the procurement approval chain.</p>
          </div>
          <button @click="showNewPoModal = false" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="space-y-3 text-xs">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Select Vendor *</label>
              <select
                v-model="newPoForm.vendor_id"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 font-semibold"
              >
                <option value="" disabled>-- Select Supplier --</option>
                <option v-for="v in vendors" :key="v.id" :value="v.id">
                  {{ v.name }} ({{ v.payment_terms }})
                </option>
              </select>
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Expected Delivery Date</label>
              <input
                v-model="newPoForm.expected_delivery_date"
                type="date"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <!-- Line Items Section -->
          <div class="border-t border-slate-100 pt-3">
            <div class="flex items-center justify-between mb-2">
              <span class="font-bold text-slate-800">Order Line Items</span>
              <button
                type="button"
                @click="addLineItem"
                class="text-blue-600 hover:underline font-bold text-xs cursor-pointer"
              >
                + Add Item
              </button>
            </div>

            <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
              <div
                v-for="(line, idx) in newPoForm.items"
                :key="idx"
                class="grid grid-cols-12 gap-2 p-2.5 bg-slate-50 rounded-xl border border-slate-200/70 items-center"
              >
                <div class="col-span-5">
                  <select
                    v-model="line.inventory_item_id"
                    @change="onPoItemSelect(line)"
                    class="w-full p-1.5 bg-white border border-slate-200 rounded-lg text-xs"
                  >
                    <option value="" disabled>-- Item --</option>
                    <option v-for="catItem in catalogItems" :key="catItem.id" :value="catItem.id">
                      {{ catItem.name }} ({{ catItem.unit_of_measure }})
                    </option>
                  </select>
                </div>
                <div class="col-span-3">
                  <input
                    v-model.number="line.quantity_ordered"
                    type="number"
                    min="1"
                    placeholder="Qty"
                    class="w-full p-1.5 bg-white border border-slate-200 rounded-lg text-xs font-mono"
                  />
                </div>
                <div class="col-span-3">
                  <input
                    v-model.number="line.unit_cost"
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="Unit $"
                    class="w-full p-1.5 bg-white border border-slate-200 rounded-lg text-xs font-mono"
                  />
                </div>
                <div class="col-span-1 text-center">
                  <button
                    v-if="newPoForm.items.length > 1"
                    @click="removeLineItem(idx)"
                    class="text-rose-500 hover:text-rose-700"
                  >
                    &times;
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Total Calculation Preview -->
          <div class="p-3 bg-blue-50/60 rounded-xl flex items-center justify-between text-xs font-mono">
            <span class="text-blue-900 font-bold">Estimated PO Total:</span>
            <span class="text-blue-900 font-black text-sm">${{ calculatedPoSubtotal.toFixed(2) }}</span>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Procurement Notes / Justification</label>
            <textarea
              v-model="newPoForm.notes"
              rows="2"
              placeholder="e.g. Monthly consumable stock replenishment approved in budget..."
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500"
            ></textarea>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
          <button
            @click="showNewPoModal = false"
            class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer"
          >
            Cancel
          </button>
          <button
            @click="submitCreatePo"
            :disabled="isSubmitting || !newPoForm.vendor_id || newPoForm.items.length === 0"
            class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
          >
            Create Draft PO
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Receive Stock Into Warehouse -->
    <div
      v-if="showReceiveModal && selectedPo"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto"
    >
      <div class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl space-y-4 my-8">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3 class="font-bold text-slate-900 text-base">Receive Goods & Increment Stock</h3>
            <p class="text-xs text-slate-400">PO #{{ selectedPo.po_number }} &bull; Vendor: {{ selectedPo.vendor?.name }}</p>
          </div>
          <button @click="showReceiveModal = false" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="space-y-3 text-xs">
          <p class="text-slate-600">
            Enter the verified quantities received at the warehouse loading dock. Stock ledger will automatically increment and log a <strong>purchase_receipt</strong> movement.
          </p>

          <div class="space-y-2 max-h-60 overflow-y-auto">
            <div
              v-for="item in receiveItemsForm"
              :key="item.purchase_order_item_id"
              class="p-3 bg-slate-50 rounded-xl border border-slate-200/70 flex items-center justify-between gap-4"
            >
              <div>
                <div class="font-bold text-slate-900">{{ item.name }}</div>
                <div class="text-[11px] text-slate-400 font-mono">
                  Ordered: {{ item.quantity_ordered }} | Previously Received: {{ item.quantity_received }} | Remaining: {{ item.remaining_quantity }}
                </div>
              </div>

              <div class="w-32">
                <label class="block text-[10px] uppercase font-bold text-slate-500 mb-0.5">Qty Delivered</label>
                <input
                  v-model.number="item.to_receive"
                  type="number"
                  min="0"
                  :max="item.remaining_quantity"
                  class="w-full p-2 bg-white border border-slate-200 rounded-lg text-xs font-mono font-bold text-indigo-700"
                />
              </div>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
          <button
            @click="showReceiveModal = false"
            class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer"
          >
            Cancel
          </button>
          <button
            @click="submitReceiveGoods"
            :disabled="isSubmitting"
            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
          >
            Confirm Receipt Into Stock
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: PO Details & Approval Timeline -->
    <div
      v-if="showDetailsModal && selectedPo"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto"
    >
      <div class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl space-y-4 my-8">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3 class="font-bold text-slate-900 text-base">Purchase Order #{{ selectedPo.po_number }}</h3>
            <p class="text-xs text-slate-400">Vendor: {{ selectedPo.vendor?.name }} ({{ selectedPo.order_date }})</p>
          </div>
          <button @click="showDetailsModal = false" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Approval Chain Timeline -->
        <div class="p-3 bg-slate-50 rounded-xl space-y-2 text-xs">
          <div class="text-[10px] font-mono uppercase font-bold text-slate-400 tracking-wider">Procurement Approval Chain</div>
          <div class="flex items-center justify-between font-mono text-[11px]">
            <div class="flex items-center gap-1.5 text-slate-700">
              <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
              <span>Draft Created ({{ selectedPo.created_by?.name }})</span>
            </div>
            <div v-if="selectedPo.submitted_at" class="flex items-center gap-1.5 text-amber-700">
              <span class="w-2 h-2 rounded-full bg-amber-500"></span>
              <span>Submitted</span>
            </div>
            <div v-if="selectedPo.approved_at" class="flex items-center gap-1.5 text-blue-700">
              <span class="w-2 h-2 rounded-full bg-blue-500"></span>
              <span>Approved</span>
            </div>
            <div v-if="selectedPo.status === 'received'" class="flex items-center gap-1.5 text-emerald-700">
              <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
              <span>Fully Received</span>
            </div>
          </div>
        </div>

        <!-- Items Breakdown -->
        <div class="space-y-2 text-xs">
          <h4 class="font-bold text-slate-800">Ordered Items</h4>
          <div class="space-y-1.5 max-h-48 overflow-y-auto">
            <div
              v-for="item in selectedPo.items"
              :key="item.id"
              class="p-2.5 bg-slate-50 rounded-xl flex items-center justify-between text-xs font-mono"
            >
              <div>
                <div class="font-bold text-slate-900">{{ item.item_name }}</div>
                <div class="text-[10px] text-slate-400">Ordered: {{ item.quantity_ordered }} | Received: {{ item.quantity_received }}</div>
              </div>
              <div class="text-right">
                <div class="font-bold text-slate-900">${{ item.total_cost.toFixed(2) }}</div>
                <div class="text-[10px] text-slate-400">${{ item.unit_cost.toFixed(2) }} / unit</div>
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-end pt-2 border-t border-slate-100">
          <button
            @click="showDetailsModal = false"
            class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold cursor-pointer"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { showAlert, showConfirm, showPrompt } from '../../Services/modalDialog';

const props = defineProps({
  branchId: {
    type: String,
    default: null,
  },
});

const loading = ref(false);
const isSubmitting = ref(false);
const statusFilter = ref('all');
const searchQuery = ref('');

const purchaseOrders = ref([]);
const vendors = ref([]);
const catalogItems = ref([]);

const showNewPoModal = ref(false);
const showReceiveModal = ref(false);
const showDetailsModal = ref(false);
const selectedPo = ref(null);

const newPoForm = ref({
  vendor_id: '',
  expected_delivery_date: '',
  notes: '',
  items: [],
});

const receiveItemsForm = ref([]);

async function fetchPurchaseOrders() {
  loading.value = true;
  try {
    const params = {
      branch_id: props.branchId || undefined,
      status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
      search: searchQuery.value || undefined,
    };
    const res = await axios.get('/api/v1/inventory/purchase-orders', { params });
    purchaseOrders.value = res.data?.data || [];
  } catch (err) {
    console.error('Failed to load purchase orders:', err);
  } finally {
    loading.value = false;
  }
}

async function openNewPoModal() {
  try {
    const [vRes, iRes] = await Promise.all([
      axios.get('/api/v1/inventory/vendors', { params: { branch_id: props.branchId || undefined, per_page: 50 } }),
      axios.get('/api/v1/inventory/items', { params: { branch_id: props.branchId || undefined, per_page: 100 } }),
    ]);
    vendors.value = vRes.data?.data || [];
    catalogItems.value = iRes.data?.data || [];
  } catch (e) {
    console.error(e);
  }

  newPoForm.value = {
    vendor_id: '',
    expected_delivery_date: '',
    notes: '',
    items: [
      { inventory_item_id: '', quantity_ordered: 10, unit_cost: 0 },
    ],
  };
  showNewPoModal.value = true;
}

function addLineItem() {
  newPoForm.value.items.push({
    inventory_item_id: '',
    quantity_ordered: 10,
    unit_cost: 0,
  });
}

function removeLineItem(idx) {
  newPoForm.value.items.splice(idx, 1);
}

function onPoItemSelect(line) {
  const found = catalogItems.value.find(i => i.id === line.inventory_item_id);
  if (found) {
    line.unit_cost = found.unit_cost;
  }
}

const calculatedPoSubtotal = computed(() => {
  return newPoForm.value.items.reduce((acc, line) => {
    return acc + ((line.quantity_ordered || 0) * (line.unit_cost || 0));
  }, 0);
});

async function submitCreatePo() {
  isSubmitting.value = true;
  try {
    const payload = {
      organization_id: '01a09429-0097-71b5-9f5b-6ff38a7cba8b',
      branch_id: props.branchId || 'b9ff561a-5396-4309-9b08-3e7b358310e9',
      vendor_id: newPoForm.value.vendor_id,
      expected_delivery_date: newPoForm.value.expected_delivery_date || undefined,
      notes: newPoForm.value.notes || undefined,
      items: newPoForm.value.items.map(l => ({
        inventory_item_id: l.inventory_item_id,
        quantity_ordered: l.quantity_ordered,
        unit_cost_cents: Math.round(l.unit_cost * 100),
      })),
    };

    await axios.post('/api/v1/inventory/purchase-orders', payload);
    showNewPoModal.value = false;
    await fetchPurchaseOrders();
  } catch (err) {
    await showAlert(err.response?.data?.message || 'Failed to create purchase order.');
  } finally {
    isSubmitting.value = false;
  }
}

async function submitPo(po) {
  if (!await showConfirm(`Submit PO #${po.po_number} for supervisor approval?`)) return;
  try {
    await axios.post(`/api/v1/inventory/purchase-orders/${po.id}/submit`);
    await fetchPurchaseOrders();
  } catch (err) {
    await showAlert(err.response?.data?.message || 'Failed to submit PO.');
  }
}

async function approvePo(po) {
  if (!await showConfirm(`Authorize and approve PO #${po.po_number} for $${po.total.toFixed(2)}?`)) return;
  try {
    await axios.post(`/api/v1/inventory/purchase-orders/${po.id}/approve`);
    await fetchPurchaseOrders();
  } catch (err) {
    await showAlert(err.response?.data?.message || 'Failed to approve PO.');
  }
}

async function openRejectPoModal(po) {
  const reason = await showPrompt(`Reason for rejecting PO #${po.po_number}:`);
  if (!reason) return;
  try {
    await axios.post(`/api/v1/inventory/purchase-orders/${po.id}/reject`, { reason });
    await fetchPurchaseOrders();
  } catch (err) {
    await showAlert(err.response?.data?.message || 'Failed to reject PO.');
  }
}

function openReceiveModal(po) {
  selectedPo.value = po;
  receiveItemsForm.value = po.items.map(i => ({
    purchase_order_item_id: i.id,
    name: i.item_name,
    quantity_ordered: i.quantity_ordered,
    quantity_received: i.quantity_received,
    remaining_quantity: i.remaining_quantity,
    to_receive: i.remaining_quantity,
  }));
  showReceiveModal.value = true;
}

async function submitReceiveGoods() {
  if (!selectedPo.value) return;
  isSubmitting.value = true;
  try {
    const itemsToReceive = receiveItemsForm.value
      .filter(i => i.to_receive > 0)
      .map(i => ({
        purchase_order_item_id: i.purchase_order_item_id,
        quantity_received: i.to_receive,
      }));

    if (itemsToReceive.length === 0) {
      await showAlert('Please specify at least 1 item quantity to receive.');
      isSubmitting.value = false;
      return;
    }

    await axios.post(`/api/v1/inventory/purchase-orders/${selectedPo.value.id}/receive`, {
      items: itemsToReceive,
    });
    showReceiveModal.value = false;
    await fetchPurchaseOrders();
  } catch (err) {
    await showAlert(err.response?.data?.message || 'Failed to receive goods.');
  } finally {
    isSubmitting.value = false;
  }
}

function openPoDetails(po) {
  selectedPo.value = po;
  showDetailsModal.value = true;
}

onMounted(() => {
  fetchPurchaseOrders();
});
</script>
