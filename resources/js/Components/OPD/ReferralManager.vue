<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h2 class="text-xl font-black text-slate-900 tracking-tight">Referral Management</h2>
        <p class="text-xs text-slate-500 mt-1">Inter-department specialist transfers and external tertiary care hospital referrals.</p>
      </div>

      <button
        @click="showCreateModal = true"
        class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition flex items-center gap-2 cursor-pointer shadow-sm"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        New Patient Referral
      </button>
    </div>

    <!-- Referrals Table -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
      <div class="p-6 border-b border-slate-100 flex items-center justify-between">
        <h3 class="font-bold text-slate-900 text-sm">Active & Outgoing Referrals</h3>
        <button @click="fetchReferrals" class="text-xs text-blue-600 font-bold hover:underline">Refresh</button>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50 border-b border-slate-100 text-slate-400 uppercase text-[10px] font-bold">
            <tr>
              <th class="py-3.5 px-6">Patient</th>
              <th class="py-3.5 px-6">Type</th>
              <th class="py-3.5 px-6">Destination</th>
              <th class="py-3.5 px-6">Priority</th>
              <th class="py-3.5 px-6">Reason</th>
              <th class="py-3.5 px-6">Status</th>
              <th class="py-3.5 px-6 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="referrals.length === 0">
              <td colspan="7" class="py-8 text-center text-slate-400">No referrals found.</td>
            </tr>
            <tr v-for="ref in referrals" :key="ref.id" class="hover:bg-slate-50/50">
              <td class="py-4 px-6">
                <div class="font-bold text-slate-800">{{ ref.patient ? ref.patient.full_name : 'Patient' }}</div>
                <div class="text-[10px] text-slate-400 font-mono">{{ ref.patient ? ref.patient.mrn : '' }}</div>
              </td>
              <td class="py-4 px-6">
                <span class="px-2 py-0.5 rounded-full font-bold text-[10px] capitalize"
                  :class="ref.referral_type === 'internal_department' ? 'bg-indigo-100 text-indigo-700' : 'bg-amber-100 text-amber-800'">
                  {{ ref.referral_type === 'internal_department' ? 'Internal Dept' : 'External Facility' }}
                </span>
              </td>
              <td class="py-4 px-6 font-medium text-slate-800">
                <div v-if="ref.referral_type === 'internal_department'">
                  {{ ref.to_department ? ref.to_department.name : 'Target Department' }}
                </div>
                <div v-else>
                  <div class="font-bold text-slate-800">{{ ref.external_facility_name }}</div>
                  <div class="text-[10px] text-slate-500">{{ ref.external_specialist_name || 'Specialist' }} • {{ ref.external_contact || 'N/A' }}</div>
                </div>
              </td>
              <td class="py-4 px-6">
                <span class="px-2 py-0.5 rounded-md font-black text-[10px] uppercase"
                  :class="{
                    'bg-slate-100 text-slate-700': ref.priority === 'routine',
                    'bg-amber-100 text-amber-800': ref.priority === 'urgent',
                    'bg-rose-100 text-rose-800': ref.priority === 'emergency'
                  }">
                  {{ ref.priority }}
                </span>
              </td>
              <td class="py-4 px-6 max-w-xs truncate text-slate-600">
                {{ ref.reason_for_referral }}
              </td>
              <td class="py-4 px-6">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider"
                  :class="{
                    'bg-amber-100 text-amber-800': ref.status === 'pending',
                    'bg-blue-100 text-blue-800': ref.status === 'accepted',
                    'bg-emerald-100 text-emerald-800': ref.status === 'completed',
                    'bg-rose-100 text-rose-800': ['rejected', 'cancelled'].includes(ref.status)
                  }">
                  {{ ref.status }}
                </span>
              </td>
              <td class="py-4 px-6 text-right space-x-1.5">
                <button
                  v-if="ref.status === 'pending'"
                  @click="updateStatus(ref, 'accepted')"
                  class="px-2.5 py-1 rounded-lg bg-blue-600 text-white font-bold text-[11px] hover:bg-blue-700"
                >
                  Accept
                </button>
                <button
                  v-if="ref.status === 'accepted'"
                  @click="updateStatus(ref, 'completed')"
                  class="px-2.5 py-1 rounded-lg bg-emerald-600 text-white font-bold text-[11px] hover:bg-emerald-700"
                >
                  Complete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create Referral Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
      <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 border border-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
          <h3 class="font-bold text-slate-900 text-base">Create Patient Referral</h3>
          <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">&times;</button>
        </div>

        <form @submit.prevent="submitReferral" class="space-y-4 text-xs">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Patient UUID *</label>
            <input type="text" v-model="form.patient_id" placeholder="Patient UUID" required class="w-full text-xs rounded-xl border-slate-300 p-2.5 border font-mono" />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Referral Type *</label>
              <select v-model="form.referral_type" required class="w-full text-xs rounded-xl border-slate-300 p-2.5 border bg-white">
                <option value="internal_department">Internal Department</option>
                <option value="external_facility">External Facility</option>
              </select>
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Priority</label>
              <select v-model="form.priority" class="w-full text-xs rounded-xl border-slate-300 p-2.5 border bg-white">
                <option value="routine">Routine</option>
                <option value="urgent">Urgent</option>
                <option value="emergency">Emergency</option>
              </select>
            </div>
          </div>

          <!-- Internal dept target -->
          <div v-if="form.referral_type === 'internal_department'">
            <label class="block font-semibold text-slate-700 mb-1">Target Department *</label>
            <select v-model="form.to_department_id" :required="form.referral_type === 'internal_department'" class="w-full text-xs rounded-xl border-slate-300 p-2.5 border bg-white">
              <option value="">Select Department</option>
              <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
            </select>
          </div>

          <!-- External facility details -->
          <div v-else class="space-y-3">
            <div>
              <label class="block font-semibold text-slate-700 mb-1">External Hospital / Clinic Name *</label>
              <input type="text" v-model="form.external_facility_name" placeholder="e.g. St. Jude Heart Institute" :required="form.referral_type === 'external_facility'" class="w-full text-xs rounded-xl border-slate-300 p-2.5 border" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-semibold text-slate-700 mb-1">Specialist Name</label>
                <input type="text" v-model="form.external_specialist_name" placeholder="Dr. John Doe" class="w-full text-xs rounded-xl border-slate-300 p-2.5 border" />
              </div>
              <div>
                <label class="block font-semibold text-slate-700 mb-1">Contact Phone</label>
                <input type="text" v-model="form.external_contact" placeholder="+1 800 555..." class="w-full text-xs rounded-xl border-slate-300 p-2.5 border" />
              </div>
            </div>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Reason for Referral *</label>
            <textarea v-model="form.reason_for_referral" required rows="2" placeholder="Clinical reason or question for specialist..." class="w-full text-xs rounded-xl border-slate-300 p-2.5 border"></textarea>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Clinical Summary</label>
            <textarea v-model="form.clinical_summary" rows="2" placeholder="Brief summary of patient diagnosis and current therapies..." class="w-full text-xs rounded-xl border-slate-300 p-2.5 border"></textarea>
          </div>

          <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
            <button type="button" @click="showCreateModal = false" class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-semibold cursor-pointer">Cancel</button>
            <button type="submit" :disabled="submitting" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold transition shadow-sm cursor-pointer">
              {{ submitting ? 'Submitting...' : 'Issue Referral' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { showAlert } from '../../Services/modalDialog';

const props = defineProps({
  branchId: { type: String, required: true },
});

const referrals = ref([]);
const departments = ref([]);
const showCreateModal = ref(false);
const submitting = ref(false);

const form = ref({
  patient_id: '',
  referral_type: 'internal_department',
  to_department_id: '',
  external_facility_name: '',
  external_specialist_name: '',
  external_contact: '',
  priority: 'routine',
  reason_for_referral: '',
  clinical_summary: '',
});

async function fetchDepartments() {
  try {
    const res = await fetch('/api/v1/opd/departments', {
      headers: { 'X-Branch-ID': props.branchId, 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.data) departments.value = json.data;
  } catch (e) {
    console.error('Fetch departments error', e);
  }
}

async function fetchReferrals() {
  try {
    const res = await fetch('/api/v1/opd/referrals', {
      headers: { 'X-Branch-ID': props.branchId, 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.data) {
      referrals.value = json.data;
    }
  } catch (e) {
    console.error('Fetch referrals error', e);
  }
}

async function submitReferral() {
  submitting.value = true;
  try {
    const res = await fetch('/api/v1/opd/referrals', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Branch-ID': props.branchId,
        'Accept': 'application/json'
      },
      body: JSON.stringify(form.value)
    });
    const json = await res.json();
    if (res.ok) {
      showCreateModal.value = false;
      await fetchReferrals();
      await showAlert('Referral created successfully.');
    } else {
      await showAlert(json.message || 'Error creating referral');
    }
  } catch (e) {
    console.error('Submit referral error', e);
  } finally {
    submitting.value = false;
  }
}

async function updateStatus(referral, status) {
  try {
    const res = await fetch(`/api/v1/opd/referrals/${referral.id}/status`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-Branch-ID': props.branchId,
        'Accept': 'application/json'
      },
      body: JSON.stringify({ status })
    });
    if (res.ok) {
      await fetchReferrals();
    }
  } catch (e) {
    console.error('Update referral status error', e);
  }
}

onMounted(async () => {
  await fetchDepartments();
  await fetchReferrals();
});
</script>
