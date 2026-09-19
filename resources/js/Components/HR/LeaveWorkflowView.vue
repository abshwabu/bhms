<template>
  <div class="space-y-6">
    <!-- Top Bar & Quota Selector -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
      <div class="flex items-center gap-3 w-full md:w-auto">
        <label class="text-xs font-bold text-slate-700 whitespace-nowrap">Employee Quota:</label>
        <select
          v-model="selectedStaffId"
          @change="fetchBalances"
          class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-blue-500 focus:outline-none cursor-pointer max-w-xs truncate"
        >
          <option v-for="s in staffMembers" :key="s.id" :value="s.id">
            {{ s.full_name }} ({{ s.department }})
          </option>
        </select>
      </div>

      <div class="flex items-center gap-2 w-full md:w-auto justify-end">
        <button
          @click="openNewLeaveModal"
          class="px-4 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white transition flex items-center gap-1.5 shadow-sm cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span>Submit Time-Off Request</span>
        </button>
      </div>
    </div>

    <!-- Annual Quota Cards for Selected Staff -->
    <div v-if="quotaBalances.length > 0" class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div
        v-for="q in quotaBalances"
        :key="q.id"
        class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm"
      >
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold uppercase tracking-wider capitalize" :class="getTypeColorClass(q.leave_type)">
            {{ q.leave_type }} Leave
          </span>
          <span class="text-[11px] font-mono text-slate-400 font-semibold">{{ q.year }}</span>
        </div>
        <div class="mt-2 flex items-baseline gap-1">
          <span class="text-2xl font-black text-slate-900">{{ q.remaining_days }}</span>
          <span class="text-xs text-slate-400 font-medium">/ {{ q.allocated_days }} days left</span>
        </div>
        <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-500">
          <span>Used: <strong>{{ q.used_days }}d</strong></span>
          <span>Pending: <strong class="text-amber-600">{{ q.pending_days }}d</strong></span>
        </div>
      </div>
    </div>

    <!-- Supervisor Pending Approvals Queue -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-5 border-b border-slate-200 flex items-center justify-between">
        <div>
          <h4 class="font-bold text-sm text-slate-900 flex items-center gap-2">
            <span>Pending Supervisor Approvals</span>
            <span
              v-if="pendingRequests.length > 0"
              class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500 text-white animate-pulse"
            >
              {{ pendingRequests.length }} Action Needed
            </span>
          </h4>
          <p class="text-xs text-slate-500 mt-0.5">
            Submitted time-off requests pending departmental manager sign-off.
          </p>
        </div>
        <button
          @click="fetchLeaveRequests"
          class="text-xs font-semibold text-blue-600 hover:text-blue-800 underline cursor-pointer"
        >
          Refresh Queue
        </button>
      </div>

      <div class="divide-y divide-slate-100">
        <div v-if="pendingRequests.length === 0" class="p-8 text-center text-slate-400 text-xs italic">
          No pending leave requests requiring approval at this time.
        </div>

        <div
          v-for="req in pendingRequests"
          :key="req.id"
          class="p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-slate-50/50 transition"
        >
          <div class="space-y-1">
            <div class="flex items-center gap-2">
              <span class="font-bold text-slate-900 text-sm">{{ req.staff?.full_name }}</span>
              <span class="text-xs font-mono text-slate-400">({{ req.staff?.employee_id }})</span>
              <span class="capitalize px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                {{ req.leave_type }}
              </span>
            </div>
            <div class="text-xs text-slate-600">
              Duration: <strong>{{ req.start_date }}</strong> &rarr; <strong>{{ req.end_date }}</strong>
              <span class="text-slate-400 ml-1">({{ req.total_days }} days requested)</span>
            </div>
            <div class="text-xs text-slate-700 bg-slate-50 p-2 rounded-xl border border-slate-200 mt-1 max-w-xl">
              <span class="font-semibold text-slate-500">Reason:</span> {{ req.reason }}
            </div>
          </div>

          <div class="flex items-center gap-2 shrink-0">
            <button
              @click="approveRequest(req)"
              :disabled="actionInProgress"
              class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-sm cursor-pointer disabled:opacity-50"
            >
              Approve Leave
            </button>
            <button
              @click="openRejectModal(req)"
              :disabled="actionInProgress"
              class="px-3 py-2 rounded-xl text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 transition cursor-pointer disabled:opacity-50"
            >
              Reject
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Historical Leave Audit Trail -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-4 border-b border-slate-200 flex items-center justify-between">
        <h4 class="font-bold text-sm text-slate-800">Leave History & Decision Audit Log</h4>
        <span class="text-xs text-slate-400 font-mono">{{ historicalRequests.length }} entries</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
              <th class="py-3 px-4">Staff Member</th>
              <th class="py-3 px-4">Type</th>
              <th class="py-3 px-4">From - To</th>
              <th class="py-3 px-4">Days</th>
              <th class="py-3 px-4">Reason</th>
              <th class="py-3 px-4">Decision Status</th>
              <th class="py-3 px-4">Supervisor</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="historicalRequests.length === 0">
              <td colspan="7" class="py-6 text-center text-slate-400 italic">No processed leave records yet.</td>
            </tr>
            <tr
              v-for="hist in historicalRequests"
              :key="hist.id"
              class="hover:bg-slate-50/70 transition"
            >
              <td class="py-3 px-4 font-bold text-slate-900">{{ hist.staff?.full_name }}</td>
              <td class="py-3 px-4 capitalize font-semibold text-slate-700">{{ hist.leave_type }}</td>
              <td class="py-3 px-4 font-mono">{{ hist.start_date }} &rarr; {{ hist.end_date }}</td>
              <td class="py-3 px-4 font-bold">{{ hist.total_days }}d</td>
              <td class="py-3 px-4 text-slate-600 max-w-xs truncate">{{ hist.reason }}</td>
              <td class="py-3 px-4">
                <span
                  v-if="hist.status === 'approved'"
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800"
                >
                  Approved
                </span>
                <span
                  v-else-if="hist.status === 'rejected'"
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800"
                  :title="hist.rejection_reason"
                >
                  Rejected
                </span>
                <span v-else class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                  {{ hist.status }}
                </span>
              </td>
              <td class="py-3 px-4 text-slate-500 text-[11px]">
                {{ hist.approver?.name || 'Authorized Supervisor' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal: Submit Leave Request -->
    <div v-if="isNewLeaveModalOpen" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-start justify-between pb-4 border-b border-slate-100">
          <div>
            <h3 class="font-bold text-base text-slate-900">Submit Leave Request</h3>
            <p class="text-xs text-slate-500 mt-0.5">Quota verification with pending balance reservation.</p>
          </div>
          <button @click="isNewLeaveModalOpen = false" class="p-1 text-slate-400 hover:text-slate-600 cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="submitLeave" class="mt-4 space-y-3">
          <div>
            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Employee *</label>
            <select
              v-model="leaveForm.staff_id"
              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
              required
            >
              <option v-for="s in staffMembers" :key="s.id" :value="s.id">
                {{ s.full_name }} &mdash; {{ s.designation }}
              </option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-semibold text-slate-600 mb-1">Leave Category *</label>
              <select
                v-model="leaveForm.leave_type"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                required
              >
                <option value="annual">Annual Leave</option>
                <option value="sick">Sick Leave</option>
                <option value="casual">Casual Leave</option>
                <option value="study">Study / CME Leave</option>
                <option value="maternity">Maternity Leave</option>
                <option value="paternity">Paternity Leave</option>
              </select>
            </div>
            <div>
              <label class="block text-[11px] font-semibold text-slate-600 mb-1">Total Days *</label>
              <input
                v-model.number="leaveForm.total_days"
                type="number"
                min="1"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                required
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-semibold text-slate-600 mb-1">Start Date *</label>
              <input
                v-model="leaveForm.start_date"
                type="date"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                required
              />
            </div>
            <div>
              <label class="block text-[11px] font-semibold text-slate-600 mb-1">End Date *</label>
              <input
                v-model="leaveForm.end_date"
                type="date"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                required
              />
            </div>
          </div>

          <div>
            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Emergency Contact Phone</label>
            <input
              v-model="leaveForm.emergency_contact_phone"
              type="text"
              placeholder="+1 (555) 000-0000"
              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
            />
          </div>

          <div>
            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Reason / Notes *</label>
            <textarea
              v-model="leaveForm.reason"
              rows="2"
              placeholder="State purpose of requested time-off..."
              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
              required
            ></textarea>
          </div>

          <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
            <button
              type="button"
              @click="isNewLeaveModalOpen = false"
              class="px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 cursor-pointer"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="submittingLeave"
              class="px-4 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white cursor-pointer disabled:opacity-50"
            >
              {{ submittingLeave ? 'Submitting...' : 'Submit Request' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Rejection Reason -->
    <div v-if="isRejectModalOpen && requestToReject" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <h3 class="font-bold text-base text-slate-900">Decline Time-Off Request</h3>
        <p class="text-xs text-slate-500 mt-1">
          Provide a mandatory reason for declining {{ requestToReject.staff?.full_name }}'s request. This will release the {{ requestToReject.total_days }} reserved days back to their quota.
        </p>

        <div class="mt-4">
          <label class="block text-[11px] font-semibold text-slate-600 mb-1">Rejection Reason *</label>
          <textarea
            v-model="rejectionReason"
            rows="3"
            placeholder="e.g. Critical clinical staffing shortage during requested week."
            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none"
            required
          ></textarea>
        </div>

        <div class="mt-5 flex justify-end gap-2">
          <button
            type="button"
            @click="isRejectModalOpen = false"
            class="px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 cursor-pointer"
          >
            Cancel
          </button>
          <button
            @click="confirmReject"
            :disabled="!rejectionReason.trim() || actionInProgress"
            class="px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white cursor-pointer disabled:opacity-50"
          >
            Confirm Rejection
          </button>
        </div>
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
    default: null,
  },
});

const staffMembers = ref([]);
const selectedStaffId = ref('');
const quotaBalances = ref([]);
const allRequests = ref([]);
const actionInProgress = ref(false);

const isNewLeaveModalOpen = ref(false);
const submittingLeave = ref(false);

const isRejectModalOpen = ref(false);
const requestToReject = ref(null);
const rejectionReason = ref('');

const leaveForm = ref({
  staff_id: '',
  leave_type: 'annual',
  start_date: '',
  end_date: '',
  total_days: 1,
  emergency_contact_phone: '',
  reason: '',
});

const pendingRequests = computed(() => {
  return allRequests.value.filter((r) => r.status === 'pending');
});

const historicalRequests = computed(() => {
  return allRequests.value.filter((r) => r.status !== 'pending');
});

const getTypeColorClass = (type) => {
  if (type === 'annual') return 'text-blue-600';
  if (type === 'sick') return 'text-rose-600';
  if (type === 'casual') return 'text-amber-600';
  return 'text-indigo-600';
};

const fetchStaffMembers = async () => {
  try {
    const res = await fetch('/api/v1/hr/staff?per_page=100', {
      headers: {
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
    });
    const json = await res.json();
    if (json.success) {
      staffMembers.value = json.data?.data || json.data || [];
      if (staffMembers.value.length > 0 && !selectedStaffId.value) {
        selectedStaffId.value = staffMembers.value[0].id;
        await fetchBalances();
      }
    }
  } catch (err) {
    console.error('Failed to load staff list', err);
  }
};

const fetchBalances = async () => {
  if (!selectedStaffId.value) return;
  try {
    const res = await fetch(`/api/v1/hr/staff/${selectedStaffId.value}/leave-balances`, {
      headers: {
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
    });
    const json = await res.json();
    if (json.success) {
      quotaBalances.value = json.data || [];
    }
  } catch (err) {
    console.error('Failed to load balances', err);
  }
};

const fetchLeaveRequests = async () => {
  try {
    const res = await fetch('/api/v1/hr/leave-requests?per_page=50', {
      headers: {
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
    });
    const json = await res.json();
    if (json.success) {
      allRequests.value = json.data?.data || json.data || [];
    }
  } catch (err) {
    console.error('Failed to load leave requests', err);
  }
};

const openNewLeaveModal = () => {
  leaveForm.value = {
    staff_id: selectedStaffId.value || staffMembers.value[0]?.id || '',
    leave_type: 'annual',
    start_date: new Date().toISOString().split('T')[0],
    end_date: new Date().toISOString().split('T')[0],
    total_days: 1,
    emergency_contact_phone: '',
    reason: '',
  };
  isNewLeaveModalOpen.value = true;
};

const submitLeave = async () => {
  submittingLeave.value = true;
  try {
    const res = await fetch('/api/v1/hr/leave-requests', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
      body: JSON.stringify(leaveForm.value),
    });
    const json = await res.json();
    if (json.success) {
      isNewLeaveModalOpen.value = false;
      await fetchBalances();
      await fetchLeaveRequests();
    } else {
      await showAlert(json.message || 'Error submitting leave request', { status: 'error' });
    }
  } catch (err) {
    console.error('Failed to submit leave', err);
  } finally {
    submittingLeave.value = false;
  }
};

const approveRequest = async (req) => {
  actionInProgress.value = true;
  try {
    const res = await fetch(`/api/v1/hr/leave-requests/${req.id}/approve`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
    });
    const json = await res.json();
    if (json.success) {
      await fetchBalances();
      await fetchLeaveRequests();
    } else {
      await showAlert(json.message || 'Error approving leave', { status: 'error' });
    }
  } catch (err) {
    console.error('Failed to approve leave', err);
  } finally {
    actionInProgress.value = false;
  }
};

const openRejectModal = (req) => {
  requestToReject.value = req;
  rejectionReason.value = '';
  isRejectModalOpen.value = true;
};

const confirmReject = async () => {
  if (!requestToReject.value || !rejectionReason.value.trim()) return;
  actionInProgress.value = true;
  try {
    const res = await fetch(`/api/v1/hr/leave-requests/${requestToReject.value.id}/reject`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
      body: JSON.stringify({ reason: rejectionReason.value }),
    });
    const json = await res.json();
    if (json.success) {
      isRejectModalOpen.value = false;
      await fetchBalances();
      await fetchLeaveRequests();
    } else {
      await showAlert(json.message || 'Error rejecting leave', { status: 'error' });
    }
  } catch (err) {
    console.error('Failed to reject leave', err);
  } finally {
    actionInProgress.value = false;
  }
};

onMounted(() => {
  fetchStaffMembers();
  fetchLeaveRequests();
});
</script>
