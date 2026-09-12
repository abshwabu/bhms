<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
      <div>
        <div class="flex items-center gap-2 text-xs font-mono uppercase tracking-wider text-amber-600 font-bold">
          <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
          Supervisor Authorization Queue
        </div>
        <h1 class="text-xl font-black text-slate-900 mt-1">Pending Approvals & Authorizations</h1>
        <p class="text-xs text-slate-500 mt-0.5">Two-tier supervisor sign-off for invoice discounts and refunds exceeding threshold ($50.00).</p>
      </div>

      <button
        @click="fetchPending"
        :disabled="loading"
        class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
      >
        <svg class="w-3.5 h-3.5" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        Refresh Queue
      </button>
    </div>

    <!-- Tab Selector -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
      <button
        @click="activeSubTab = 'discounts'"
        :class="activeSubTab === 'discounts' ? 'bg-amber-500 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100'"
        class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer border border-slate-200"
      >
        <span>Pending Discounts</span>
        <span
          v-if="pendingDiscounts.length > 0"
          class="px-1.5 py-0.5 rounded-full text-[10px] bg-white text-amber-600 font-black"
        >
          {{ pendingDiscounts.length }}
        </span>
      </button>

      <button
        @click="activeSubTab = 'refunds'"
        :class="activeSubTab === 'refunds' ? 'bg-purple-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100'"
        class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer border border-slate-200"
      >
        <span>Pending Refunds</span>
        <span
          v-if="pendingRefunds.length > 0"
          class="px-1.5 py-0.5 rounded-full text-[10px] bg-white text-purple-600 font-black"
        >
          {{ pendingRefunds.length }}
        </span>
      </button>
    </div>

    <!-- Discounts List -->
    <div v-if="activeSubTab === 'discounts'" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div v-if="loading" class="p-12 text-center text-xs text-slate-400">
        Loading pending discount requests...
      </div>
      <div v-else-if="pendingDiscounts.length === 0" class="p-12 text-center text-xs text-slate-400">
        No discounts currently awaiting supervisor approval.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-600 font-mono uppercase text-[11px] border-b border-slate-200">
            <tr>
              <th class="p-4">Invoice / Patient</th>
              <th class="p-4">Type</th>
              <th class="p-4 text-right">Discount Amount</th>
              <th class="p-4">Reason</th>
              <th class="p-4">Requested By</th>
              <th class="p-4 text-center">Timestamp</th>
              <th class="p-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-700">
            <tr v-for="d in pendingDiscounts" :key="d.id" class="hover:bg-slate-50/60 transition">
              <td class="p-4">
                <div class="font-mono font-bold text-slate-900">Inv #{{ d.invoice_id?.slice(0, 8) }}</div>
              </td>
              <td class="p-4">
                <span class="font-mono uppercase text-[10px] bg-amber-50 text-amber-700 px-2 py-0.5 rounded font-bold border border-amber-200">
                  {{ d.discount_type }}
                </span>
                <span v-if="d.discount_type === 'percentage'" class="text-xs font-mono ml-1 text-slate-500">
                  ({{ d.percentage }}%)
                </span>
              </td>
              <td class="p-4 text-right font-mono font-black text-amber-600 text-sm">
                ${{ Number(d.amount).toFixed(2) }}
              </td>
              <td class="p-4 font-medium text-slate-800 max-w-xs truncate" :title="d.reason">
                {{ d.reason }}
              </td>
              <td class="p-4 text-slate-600">
                {{ d.requested_by?.name || 'Staff User' }}
              </td>
              <td class="p-4 text-center font-mono text-[11px] text-slate-400">
                {{ d.created_at ? new Date(d.created_at).toLocaleDateString() : '' }}
              </td>
              <td class="p-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    @click="approveDiscount(d)"
                    class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[11px] font-bold transition shadow-xs cursor-pointer"
                  >
                    Approve
                  </button>
                  <button
                    @click="openRejectModal('discount', d)"
                    class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-[11px] font-bold transition cursor-pointer"
                  >
                    Reject
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Refunds List -->
    <div v-else-if="activeSubTab === 'refunds'" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div v-if="loading" class="p-12 text-center text-xs text-slate-400">
        Loading pending refund requests...
      </div>
      <div v-else-if="pendingRefunds.length === 0" class="p-12 text-center text-xs text-slate-400">
        No refunds currently awaiting supervisor approval.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-600 font-mono uppercase text-[11px] border-b border-slate-200">
            <tr>
              <th class="p-4">Refund #</th>
              <th class="p-4">Invoice</th>
              <th class="p-4">Refund Mode</th>
              <th class="p-4 text-right">Amount</th>
              <th class="p-4">Reason</th>
              <th class="p-4">Requested By</th>
              <th class="p-4 text-center">Timestamp</th>
              <th class="p-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-700">
            <tr v-for="r in pendingRefunds" :key="r.id" class="hover:bg-slate-50/60 transition">
              <td class="p-4 font-mono font-bold text-slate-900">
                {{ r.refund_number }}
              </td>
              <td class="p-4 font-mono text-slate-600">
                #{{ r.invoice_id?.slice(0, 8) }}
              </td>
              <td class="p-4">
                <span class="font-mono uppercase text-[10px] bg-purple-50 text-purple-700 px-2 py-0.5 rounded font-bold border border-purple-200">
                  {{ r.refund_mode?.replace('_', ' ') }}
                </span>
              </td>
              <td class="p-4 text-right font-mono font-black text-rose-600 text-sm">
                ${{ Number(r.amount).toFixed(2) }}
              </td>
              <td class="p-4 font-medium text-slate-800 max-w-xs truncate" :title="r.reason">
                {{ r.reason }}
              </td>
              <td class="p-4 text-slate-600">
                {{ r.requested_by?.name || 'Cashier' }}
              </td>
              <td class="p-4 text-center font-mono text-[11px] text-slate-400">
                {{ r.created_at ? new Date(r.created_at).toLocaleDateString() : '' }}
              </td>
              <td class="p-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    @click="approveRefund(r)"
                    class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[11px] font-bold transition shadow-xs cursor-pointer"
                  >
                    Approve
                  </button>
                  <button
                    @click="openRejectModal('refund', r)"
                    class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-[11px] font-bold transition cursor-pointer"
                  >
                    Reject
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Rejection Reason Modal -->
    <div
      v-if="showRejectModal"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <h3 class="font-bold text-slate-900 text-base">Reject {{ rejectType === 'discount' ? 'Discount' : 'Refund' }} Request</h3>
        <p class="text-xs text-slate-500">Please supply a formal audit justification for declining this concession.</p>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Rejection Reason *</label>
          <textarea
            v-model="rejectionReason"
            rows="3"
            placeholder="Explain reason for rejection..."
            class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500"
          ></textarea>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
          <button
            @click="showRejectModal = false"
            class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer"
          >
            Cancel
          </button>
          <button
            @click="confirmReject"
            :disabled="!rejectionReason.trim()"
            class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition cursor-pointer disabled:opacity-50"
          >
            Confirm Rejection
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  branchId: {
    type: String,
    default: null,
  },
});

const loading = ref(false);
const activeSubTab = ref('discounts');
const pendingDiscounts = ref([]);
const pendingRefunds = ref([]);

const showRejectModal = ref(false);
const rejectType = ref('discount');
const itemToReject = ref(null);
const rejectionReason = ref('');

async function fetchPending() {
  loading.value = true;
  try {
    const params = { branch_id: props.branchId || undefined };
    const [discRes, refRes] = await Promise.all([
      axios.get('/api/v1/billing/discounts/pending', { params }),
      axios.get('/api/v1/billing/refunds/pending', { params }),
    ]);
    pendingDiscounts.value = discRes.data?.data || [];
    pendingRefunds.value = refRes.data?.data || [];
  } catch (e) {
    console.error('Failed to load pending approvals:', e);
  } finally {
    loading.value = false;
  }
}

async function approveDiscount(discount) {
  if (!confirm(`Approve discount of $${Number(discount.amount).toFixed(2)} on Invoice #${discount.invoice_id?.slice(0, 8)}?`)) {
    return;
  }
  try {
    await axios.post(`/api/v1/billing/discounts/${discount.id}/approve`);
    await fetchPending();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to approve discount.');
  }
}

async function approveRefund(refund) {
  if (!confirm(`Approve refund #${refund.refund_number} of $${Number(refund.amount).toFixed(2)}?`)) {
    return;
  }
  try {
    await axios.post(`/api/v1/billing/refunds/${refund.id}/approve`);
    await fetchPending();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to approve refund.');
  }
}

function openRejectModal(type, item) {
  rejectType.value = type;
  itemToReject.value = item;
  rejectionReason.value = '';
  showRejectModal.value = true;
}

async function confirmReject() {
  if (!itemToReject.value || !rejectionReason.value.trim()) return;
  try {
    if (rejectType.value === 'discount') {
      await axios.post(`/api/v1/billing/discounts/${itemToReject.value.id}/reject`, {
        reason: rejectionReason.value.trim(),
      });
    } else {
      await axios.post(`/api/v1/billing/refunds/${itemToReject.value.id}/reject`, {
        reason: rejectionReason.value.trim(),
      });
    }
    showRejectModal.value = false;
    await fetchPending();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to reject.');
  }
}

onMounted(() => {
  fetchPending();
});
</script>
