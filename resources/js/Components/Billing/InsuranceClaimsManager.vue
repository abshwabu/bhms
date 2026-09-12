<template>
  <div class="space-y-6">
    <!-- Header & Action Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
      <div>
        <div class="flex items-center gap-2 text-xs font-mono uppercase tracking-wider text-indigo-600 font-bold">
          <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
          Third-Party Administrator & Claims Workbench
        </div>
        <h1 class="text-xl font-black text-slate-900 mt-1">Insurance & TPA Claims Management</h1>
        <p class="text-xs text-slate-500 mt-0.5">Submit claims, track adjudication status, and reconcile payer settlement payments.</p>
      </div>

      <div class="flex items-center gap-2">
        <button
          @click="openNewClaimModal"
          class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-lg shadow-indigo-500/20 cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          + Submit Insurance Claim
        </button>
      </div>
    </div>

    <!-- Status Tabs & Search Filters -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
      <div class="flex items-center bg-slate-100 p-1 rounded-xl text-xs font-semibold overflow-x-auto">
        <button
          v-for="st in ['all', 'submitted', 'under_review', 'approved', 'rejected', 'reconciled']"
          :key="st"
          @click="statusFilter = st; fetchClaims()"
          :class="statusFilter === st ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
          class="px-3 py-1.5 rounded-lg capitalize transition cursor-pointer whitespace-nowrap"
        >
          {{ st.replace('_', ' ') }}
        </button>
      </div>

      <div class="relative w-full sm:w-80">
        <input
          v-model="searchQuery"
          @input="fetchClaims"
          type="text"
          placeholder="Search Claim #, Policy #, Provider..."
          class="w-full text-xs pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500"
        />
        <svg class="w-3.5 h-3.5 absolute left-2.5 top-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>
    </div>

    <!-- Claims List Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div v-if="loading" class="p-12 text-center text-xs text-slate-400">
        Loading insurance claims...
      </div>
      <div v-else-if="claims.length === 0" class="p-12 text-center text-xs text-slate-400">
        No claims found matching current filter.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-600 font-mono uppercase text-[11px] border-b border-slate-200">
            <tr>
              <th class="p-4">Claim #</th>
              <th class="p-4">Patient</th>
              <th class="p-4">Insurance Provider / Policy</th>
              <th class="p-4 text-right">Claimed Amt</th>
              <th class="p-4 text-right">Approved Amt</th>
              <th class="p-4 text-center">Status</th>
              <th class="p-4 text-center">Date</th>
              <th class="p-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-700">
            <tr v-for="c in claims" :key="c.id" class="hover:bg-slate-50/60 transition">
              <td class="p-4 font-mono font-bold text-slate-900">
                <div>{{ c.claim_number }}</div>
                <div v-if="c.pre_auth_number" class="text-[10px] text-indigo-600 font-mono">
                  Auth: {{ c.pre_auth_number }}
                </div>
              </td>
              <td class="p-4">
                <div class="font-bold text-slate-900">{{ c.patient_name || 'Patient' }}</div>
                <div class="text-[10px] text-slate-400 font-mono">MRN: {{ c.patient_mrn }}</div>
              </td>
              <td class="p-4">
                <div class="font-semibold text-slate-900">{{ c.provider_name }}</div>
                <div class="text-[10px] text-slate-400 font-mono">Policy: {{ c.policy_number }}</div>
              </td>
              <td class="p-4 text-right font-mono font-bold text-slate-900">
                ${{ Number(c.claimed_amount).toFixed(2) }}
              </td>
              <td class="p-4 text-right font-mono font-bold text-emerald-700">
                <span v-if="c.approved_amount !== null && c.approved_amount !== undefined">
                  ${{ Number(c.approved_amount).toFixed(2) }}
                </span>
                <span v-else class="text-slate-300 font-normal">&mdash;</span>
              </td>
              <td class="p-4 text-center">
                <span
                  :class="{
                    'bg-amber-50 text-amber-700 border-amber-200': c.status === 'submitted' || c.status === 'under_review',
                    'bg-emerald-50 text-emerald-700 border-emerald-200': c.status === 'approved',
                    'bg-purple-50 text-purple-700 border-purple-200': c.status === 'reconciled',
                    'bg-rose-50 text-rose-700 border-rose-200': c.status === 'rejected'
                  }"
                  class="px-2.5 py-1 rounded-full text-[10px] font-mono uppercase font-bold border"
                >
                  {{ c.status.replace('_', ' ') }}
                </span>
              </td>
              <td class="p-4 text-center font-mono text-[11px] text-slate-500">
                {{ c.submission_date || c.created_at?.slice(0, 10) }}
              </td>
              <td class="p-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <!-- Adjudicate (if submitted or under_review) -->
                  <button
                    v-if="c.status === 'submitted' || c.status === 'under_review'"
                    @click="openAdjudicateModal(c)"
                    class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-[11px] font-bold transition cursor-pointer"
                  >
                    Adjudicate
                  </button>

                  <!-- Reconcile Settlement (if approved) -->
                  <button
                    v-if="c.status === 'approved'"
                    @click="reconcileClaim(c)"
                    class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[11px] font-bold transition shadow-sm cursor-pointer"
                  >
                    Reconcile Payout
                  </button>

                  <!-- View details button -->
                  <button
                    @click="openDetailsModal(c)"
                    class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-semibold transition cursor-pointer"
                  >
                    Details
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal: Submit Insurance Claim -->
    <div
      v-if="showNewClaimModal"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto"
    >
      <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4 my-8">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3 class="font-bold text-slate-900 text-base">Submit Insurance / TPA Claim</h3>
            <p class="text-xs text-slate-400">File patient encounter charges with health insurance carrier.</p>
          </div>
          <button @click="showNewClaimModal = false" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="space-y-3 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Select Unsettled Invoice *</label>
            <select
              v-model="claimForm.invoice_id"
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 font-mono"
            >
              <option value="" disabled>-- Select Patient Invoice --</option>
              <option v-for="inv in eligibleInvoices" :key="inv.id" :value="inv.id">
                {{ inv.invoice_number }} - {{ inv.patient?.full_name || (inv.patient?.first_name + ' ' + inv.patient?.last_name) }} (Due: ${{ inv.balance.toFixed(2) }})
              </option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Pre-Authorization #</label>
              <input
                v-model="claimForm.pre_auth_number"
                type="text"
                placeholder="e.g. AUTH-2026-99"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 font-mono"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Claimed Amount ($) *</label>
              <input
                v-model.number="claimForm.claimed_amount"
                type="number"
                step="0.01"
                min="0.01"
                placeholder="0.00"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 font-mono"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Copay Amount ($)</label>
              <input
                v-model.number="claimForm.copay_amount"
                type="number"
                step="0.01"
                min="0"
                placeholder="0.00"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 font-mono"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Deductible Amount ($)</label>
              <input
                v-model.number="claimForm.deductible_amount"
                type="number"
                step="0.01"
                min="0"
                placeholder="0.00"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 font-mono"
              />
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Clinical / Adjudication Notes</label>
            <textarea
              v-model="claimForm.notes"
              rows="3"
              placeholder="Diagnosis code, procedure details, or pre-approval reference notes..."
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500"
            ></textarea>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
          <button
            @click="showNewClaimModal = false"
            class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer"
          >
            Cancel
          </button>
          <button
            @click="submitClaim"
            :disabled="isSubmitting || !claimForm.invoice_id"
            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
          >
            <svg v-if="isSubmitting" class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Submit Claim
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Adjudicate Claim -->
    <div
      v-if="showAdjudicateModal && activeClaim"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto"
    >
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3 class="font-bold text-slate-900 text-base">Adjudicate Insurance Claim</h3>
            <p class="text-xs text-slate-400">Claim #{{ activeClaim.claim_number }} - {{ activeClaim.provider_name }}</p>
          </div>
          <button @click="showAdjudicateModal = false" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-3 bg-slate-50 rounded-xl space-y-1 text-xs font-mono">
          <div class="flex justify-between">
            <span class="text-slate-400">Claimed Amount:</span>
            <span class="font-bold text-slate-900">${{ Number(activeClaim.claimed_amount).toFixed(2) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-400">Patient Copay:</span>
            <span class="text-slate-700">${{ Number(activeClaim.copay_amount || 0).toFixed(2) }}</span>
          </div>
        </div>

        <div class="space-y-3 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Adjudication Decision *</label>
            <div class="grid grid-cols-3 gap-2">
              <button
                v-for="st in ['approved', 'rejected', 'under_review']"
                :key="st"
                type="button"
                @click="adjudicationForm.status = st"
                :class="adjudicationForm.status === st ? 'bg-indigo-600 text-white font-bold' : 'bg-slate-100 text-slate-700 font-semibold'"
                class="py-2 rounded-xl capitalize transition cursor-pointer text-center text-xs"
              >
                {{ st.replace('_', ' ') }}
              </button>
            </div>
          </div>

          <div v-if="adjudicationForm.status === 'approved'">
            <label class="block font-bold text-slate-700 mb-1">Approved Settlement Amount ($) *</label>
            <input
              v-model.number="adjudicationForm.approved_amount"
              type="number"
              step="0.01"
              min="0"
              placeholder="0.00"
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 font-mono"
            />
          </div>

          <div v-if="adjudicationForm.status === 'rejected'">
            <label class="block font-bold text-slate-700 mb-1">Rejection Reason *</label>
            <input
              v-model="adjudicationForm.rejection_reason"
              type="text"
              placeholder="e.g. Non-covered procedure, policy expired, lack of prior auth"
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Adjudication Notes</label>
            <textarea
              v-model="adjudicationForm.adjudication_notes"
              rows="2"
              placeholder="Carrier determination notes or audit commentary..."
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500"
            ></textarea>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
          <button
            @click="showAdjudicateModal = false"
            class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer"
          >
            Cancel
          </button>
          <button
            @click="submitAdjudication"
            :disabled="isSubmitting"
            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
          >
            Confirm Decision
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Claim Details View -->
    <div
      v-if="showDetailsModal && activeClaim"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto"
    >
      <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3 class="font-bold text-slate-900 text-base">Claim Details: {{ activeClaim.claim_number }}</h3>
            <p class="text-xs text-slate-400">Carrier: {{ activeClaim.provider_name }}</p>
          </div>
          <button @click="showDetailsModal = false" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="grid grid-cols-2 gap-4 text-xs">
          <div>
            <div class="text-slate-400 font-mono uppercase text-[10px]">Patient</div>
            <div class="font-bold text-slate-800">{{ activeClaim.patient_name }}</div>
            <div class="text-[11px] text-slate-500 font-mono">MRN: {{ activeClaim.patient_mrn }}</div>
          </div>
          <div>
            <div class="text-slate-400 font-mono uppercase text-[10px]">Policy #</div>
            <div class="font-bold text-slate-800 font-mono">{{ activeClaim.policy_number }}</div>
            <div v-if="activeClaim.pre_auth_number" class="text-[11px] text-indigo-600 font-mono">
              Pre-Auth: {{ activeClaim.pre_auth_number }}
            </div>
          </div>
        </div>

        <div class="p-3 bg-slate-50 rounded-xl space-y-2 text-xs font-mono">
          <div class="flex justify-between">
            <span class="text-slate-500">Claimed Amount:</span>
            <span class="font-bold text-slate-900">${{ Number(activeClaim.claimed_amount).toFixed(2) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Approved Amount:</span>
            <span class="font-bold text-emerald-700">
              ${{ activeClaim.approved_amount !== null ? Number(activeClaim.approved_amount).toFixed(2) : '0.00' }}
            </span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Patient Copay:</span>
            <span class="text-slate-700">${{ Number(activeClaim.copay_amount || 0).toFixed(2) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Status:</span>
            <span class="font-bold uppercase">{{ activeClaim.status }}</span>
          </div>
        </div>

        <div v-if="activeClaim.rejection_reason" class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800">
          <div class="font-bold">Rejection Reason:</div>
          <div>{{ activeClaim.rejection_reason }}</div>
        </div>

        <div v-if="activeClaim.adjudication_notes" class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700">
          <div class="font-bold text-slate-900">Notes / Audit Trail:</div>
          <div>{{ activeClaim.adjudication_notes }}</div>
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
import { ref, onMounted } from 'vue';
import axios from 'axios';

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
const claims = ref([]);
const eligibleInvoices = ref([]);

const showNewClaimModal = ref(false);
const showAdjudicateModal = ref(false);
const showDetailsModal = ref(false);
const activeClaim = ref(null);

const claimForm = ref({
  invoice_id: '',
  pre_auth_number: '',
  claimed_amount: 0,
  copay_amount: 0,
  deductible_amount: 0,
  notes: '',
});

const adjudicationForm = ref({
  status: 'approved',
  approved_amount: 0,
  rejection_reason: '',
  adjudication_notes: '',
});

async function fetchClaims() {
  loading.value = true;
  try {
    const params = {
      branch_id: props.branchId || undefined,
      status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
      search: searchQuery.value || undefined,
    };
    const res = await axios.get('/api/v1/billing/claims', { params });
    claims.value = res.data?.data || [];
  } catch (err) {
    console.error('Failed to load claims:', err);
  } finally {
    loading.value = false;
  }
}

async function openNewClaimModal() {
  try {
    const res = await axios.get('/api/v1/billing/invoices', {
      params: { branch_id: props.branchId || undefined, status: 'unpaid' }
    });
    eligibleInvoices.value = res.data?.data || [];
  } catch (e) {
    eligibleInvoices.value = [];
  }
  claimForm.value = {
    invoice_id: '',
    pre_auth_number: '',
    claimed_amount: 0,
    copay_amount: 0,
    deductible_amount: 0,
    notes: '',
  };
  showNewClaimModal.value = true;
}

async function submitClaim() {
  if (!claimForm.value.invoice_id) return;
  isSubmitting.value = true;
  try {
    const payload = {
      invoice_id: claimForm.value.invoice_id,
      pre_auth_number: claimForm.value.pre_auth_number || undefined,
      claimed_amount_cents: Math.round(claimForm.value.claimed_amount * 100),
      copay_amount_cents: Math.round(claimForm.value.copay_amount * 100),
      deductible_amount_cents: Math.round(claimForm.value.deductible_amount * 100),
      notes: claimForm.value.notes || undefined,
    };
    await axios.post('/api/v1/billing/claims', payload);
    showNewClaimModal.value = false;
    await fetchClaims();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to submit claim.');
  } finally {
    isSubmitting.value = false;
  }
}

function openAdjudicateModal(claim) {
  activeClaim.value = claim;
  adjudicationForm.value = {
    status: 'approved',
    approved_amount: claim.claimed_amount,
    rejection_reason: '',
    adjudication_notes: '',
  };
  showAdjudicateModal.value = true;
}

async function submitAdjudication() {
  if (!activeClaim.value) return;
  isSubmitting.value = true;
  try {
    const payload = {
      status: adjudicationForm.value.status,
      approved_amount_cents: adjudicationForm.value.status === 'approved'
        ? Math.round(adjudicationForm.value.approved_amount * 100)
        : 0,
      rejection_reason: adjudicationForm.value.status === 'rejected'
        ? adjudicationForm.value.rejection_reason
        : undefined,
      adjudication_notes: adjudicationForm.value.adjudication_notes || undefined,
    };
    await axios.post(`/api/v1/billing/claims/${activeClaim.value.id}/adjudicate`, payload);
    showAdjudicateModal.value = false;
    await fetchClaims();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to adjudicate claim.');
  } finally {
    isSubmitting.value = false;
  }
}

async function reconcileClaim(claim) {
  if (!confirm(`Reconcile settlement for Claim #${claim.claim_number} ($${Number(claim.approved_amount).toFixed(2)})? This will credit the invoice.`)) {
    return;
  }
  try {
    await axios.post(`/api/v1/billing/claims/${claim.id}/reconcile`);
    await fetchClaims();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to reconcile claim payout.');
  }
}

function openDetailsModal(claim) {
  activeClaim.value = claim;
  showDetailsModal.value = true;
}

onMounted(() => {
  fetchClaims();
});
</script>
