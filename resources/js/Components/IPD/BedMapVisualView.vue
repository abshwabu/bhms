<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h2 class="text-xl font-black text-slate-900 tracking-tight">Inpatient Bed Map & Ward Allocation</h2>
        <p class="text-xs text-slate-500 mt-1">Interactive visual ward floorplan, bed availability status, and patient bed assignment.</p>
      </div>

      <div class="flex items-center gap-3">
        <!-- Ward Filter -->
        <select
          v-model="selectedWardFilter"
          @change="fetchBedMap"
          class="text-xs rounded-xl border-slate-300 p-2.5 border bg-white font-medium"
        >
          <option value="">All Inpatient Wards</option>
          <option v-for="w in wards" :key="w.id" :value="w.id">{{ w.name }} ({{ w.code }})</option>
        </select>

        <button
          @click="showAdmitModal = true"
          class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition flex items-center gap-2 cursor-pointer shadow-sm"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Admit Patient (ADT)
        </button>
      </div>
    </div>

    <!-- Bed Status Legend Bar -->
    <div class="bg-white px-6 py-3 rounded-xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between text-xs gap-4">
      <div class="flex items-center gap-6">
        <span class="flex items-center gap-2 font-medium text-slate-600">
          <span class="w-3.5 h-3.5 rounded-md bg-emerald-500 inline-block shadow-xs"></span>
          Available
        </span>
        <span class="flex items-center gap-2 font-medium text-slate-600">
          <span class="w-3.5 h-3.5 rounded-md bg-blue-600 inline-block shadow-xs"></span>
          Occupied
        </span>
        <span class="flex items-center gap-2 font-medium text-slate-600">
          <span class="w-3.5 h-3.5 rounded-md bg-amber-400 inline-block shadow-xs"></span>
          Cleaning / Sanitizing
        </span>
        <span class="flex items-center gap-2 font-medium text-slate-600">
          <span class="w-3.5 h-3.5 rounded-md bg-rose-500 inline-block shadow-xs"></span>
          Maintenance
        </span>
      </div>

      <button @click="fetchBedMap" class="text-blue-600 font-bold hover:underline text-xs">
        Refresh Bed Map
      </button>
    </div>

    <!-- Ward Visual Sections -->
    <div v-if="loading" class="text-center py-12 text-slate-400 text-xs font-mono">
      Loading ward floorplan...
    </div>

    <div v-else class="space-y-6">
      <div
        v-for="ward in wards"
        :key="ward.id"
        class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 space-y-4"
      >
        <!-- Ward Header Banner -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 gap-2">
          <div>
            <div class="flex items-center gap-3">
              <h3 class="text-base font-black text-slate-900 tracking-tight">{{ ward.name }}</h3>
              <span class="px-2.5 py-0.5 rounded-md text-[10px] font-mono font-bold bg-slate-100 text-slate-700">
                {{ ward.code }} • Floor {{ ward.floor_number || '1' }}
              </span>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-blue-50 text-blue-700">
                {{ ward.ward_type }}
              </span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">Capacity: {{ ward.capacity }} beds • {{ ward.gender_restriction }}</p>
          </div>

          <!-- Ward Mini Occupancy Meter -->
          <div class="flex items-center gap-4">
            <div class="text-right">
              <div class="text-xs font-bold text-slate-700 font-mono">
                {{ ward.occupied_beds }} / {{ ward.total_beds }} Beds
              </div>
              <div class="text-[10px] text-slate-400 font-medium">
                {{ ward.occupancy_rate_percent }}% Occupied
              </div>
            </div>
            <div class="w-24 bg-slate-100 h-2.5 rounded-full overflow-hidden border border-slate-200">
              <div
                class="h-full rounded-full transition-all duration-500"
                :class="ward.occupancy_rate_percent > 85 ? 'bg-rose-500' : (ward.occupancy_rate_percent > 60 ? 'bg-amber-500' : 'bg-blue-600')"
                :style="{ width: `${ward.occupancy_rate_percent}%` }"
              ></div>
            </div>
          </div>
        </div>

        <!-- Bed Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          <div
            v-for="bed in ward.beds"
            :key="bed.id"
            class="rounded-2xl p-4 border transition-all flex flex-col justify-between relative shadow-xs"
            :class="{
              'bg-emerald-50/50 border-emerald-300': bed.status === 'available',
              'bg-blue-50/60 border-blue-400 ring-2 ring-blue-500/20': bed.status === 'occupied',
              'bg-amber-50/50 border-amber-300': bed.status === 'cleaning',
              'bg-rose-50/50 border-rose-300': bed.status === 'maintenance',
              'bg-slate-50 border-slate-200': bed.status === 'reserved',
            }"
          >
            <!-- Bed Top Row -->
            <div class="flex items-start justify-between mb-3">
              <div>
                <span class="text-sm font-mono font-black tracking-tight text-slate-900 block">
                  {{ bed.bed_number }}
                </span>
                <span class="text-[10px] text-slate-500 capitalize font-medium">
                  {{ bed.bed_type.replace('_', ' ') }}
                </span>
              </div>

              <!-- Status Tag -->
              <span
                class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider"
                :class="{
                  'bg-emerald-100 text-emerald-800': bed.status === 'available',
                  'bg-blue-600 text-white': bed.status === 'occupied',
                  'bg-amber-100 text-amber-800': bed.status === 'cleaning',
                  'bg-rose-100 text-rose-800': bed.status === 'maintenance',
                }"
              >
                {{ bed.status }}
              </span>
            </div>

            <!-- Patient Details (if occupied) -->
            <div v-if="bed.patient" class="space-y-1.5 py-2 border-t border-blue-200/60 text-xs">
              <div class="font-extrabold text-slate-900 truncate flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span class="truncate">{{ bed.patient.name }}</span>
              </div>
              <div class="text-[10px] text-slate-500 font-mono">MRN: {{ bed.patient.mrn }}</div>
              <div class="text-[10px] text-blue-900 font-medium truncate">Dx: {{ bed.patient.diagnosis }}</div>
              <div class="text-[10px] font-bold text-slate-600">Stay: {{ bed.patient.length_of_stay_days }} days</div>
            </div>

            <div v-else-if="bed.status === 'cleaning'" class="text-xs text-amber-800 py-3 text-center font-medium">
              Awaiting Sanitization
            </div>

            <div v-else-if="bed.status === 'available'" class="text-xs text-emerald-800 py-3 text-center font-medium">
              Ready for Admission
            </div>

            <!-- Card Bottom Actions -->
            <div class="pt-3 border-t border-slate-200/60 flex items-center justify-between text-[11px]">
              <button
                v-if="bed.status === 'occupied'"
                @click="openTransferModal(bed)"
                class="font-bold text-blue-700 hover:text-blue-900 hover:underline cursor-pointer"
              >
                Transfer &rarr;
              </button>

              <button
                v-if="bed.status === 'available'"
                @click="quickAdmitToBed(bed)"
                class="font-bold text-emerald-700 hover:text-emerald-900 hover:underline cursor-pointer"
              >
                + Assign Bed
              </button>

              <button
                v-if="bed.status === 'cleaning'"
                @click="markBedAvailable(bed)"
                class="font-bold text-amber-800 hover:underline cursor-pointer"
              >
                Mark Cleaned
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Patient Admission Modal (ADT) -->
    <div v-if="showAdmitModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
      <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 border border-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
          <h3 class="font-bold text-slate-900 text-base">Inpatient Admission (ADT)</h3>
          <button @click="showAdmitModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">&times;</button>
        </div>

        <form @submit.prevent="submitAdmission" class="space-y-4 text-xs">
          <div v-if="admitError" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 font-medium">
            {{ admitError }}
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Patient UUID *</label>
            <input type="text" v-model="admitForm.patient_id" placeholder="Patient UUID" required class="w-full text-xs rounded-xl border-slate-300 p-2.5 border font-mono" />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Target Bed *</label>
              <select v-model="admitForm.bed_id" required class="w-full text-xs rounded-xl border-slate-300 p-2.5 border bg-white font-medium">
                <option value="">-- Choose Bed --</option>
                <optgroup v-for="w in wards" :key="w.id" :label="w.name">
                  <option
                    v-for="b in w.beds.filter(x => x.status === 'available')"
                    :key="b.id"
                    :value="b.id"
                  >
                    {{ b.bed_number }} ({{ b.bed_type }})
                  </option>
                </optgroup>
              </select>
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Admitting Doctor *</label>
              <input type="text" v-model="admitForm.admitting_doctor_id" placeholder="Doctor UUID" required class="w-full text-xs rounded-xl border-slate-300 p-2.5 border font-mono" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Admission Type</label>
              <select v-model="admitForm.admission_type" class="w-full text-xs rounded-xl border-slate-300 p-2.5 border bg-white">
                <option value="emergency">Emergency</option>
                <option value="elective">Elective</option>
                <option value="transfer">Transfer from other facility</option>
                <option value="observation">Observation Stay</option>
                <option value="maternity">Maternity</option>
              </select>
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Admission Date & Time</label>
              <input type="datetime-local" v-model="admitForm.admitted_at" class="w-full text-xs rounded-xl border-slate-300 p-2.5 border" />
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Admitting Diagnosis *</label>
            <textarea v-model="admitForm.admitting_diagnosis" required rows="2" placeholder="Primary diagnostic reason for inpatient admission..." class="w-full text-xs rounded-xl border-slate-300 p-2.5 border"></textarea>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Chief Complaint</label>
            <input type="text" v-model="admitForm.chief_complaint" placeholder="Patient's presenting symptoms" class="w-full text-xs rounded-xl border-slate-300 p-2.5 border" />
          </div>

          <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
            <button type="button" @click="showAdmitModal = false" class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-semibold cursor-pointer">Cancel</button>
            <button type="submit" :disabled="admitting" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold transition shadow-sm cursor-pointer">
              {{ admitting ? 'Admitting...' : 'Confirm Admission' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Bed Transfer Modal -->
    <div v-if="showTransferModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
      <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 border border-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
          <div>
            <h3 class="font-bold text-slate-900 text-base">Transfer Patient Bed</h3>
            <p class="text-xs text-slate-500">From Bed {{ activeTransferBed?.bed_number }}</p>
          </div>
          <button @click="showTransferModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">&times;</button>
        </div>

        <form @submit.prevent="submitTransfer" class="space-y-4 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Destination Available Bed *</label>
            <select v-model="transferForm.to_bed_id" required class="w-full text-xs rounded-xl border-slate-300 p-2.5 border bg-white font-medium">
              <option value="">-- Select Free Bed --</option>
              <optgroup v-for="w in wards" :key="w.id" :label="w.name">
                <option
                  v-for="b in w.beds.filter(x => x.status === 'available' && x.id !== activeTransferBed?.id)"
                  :key="b.id"
                  :value="b.id"
                >
                  {{ b.bed_number }} ({{ b.bed_type }})
                </option>
              </optgroup>
            </select>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Reason for Bed Transfer *</label>
            <textarea v-model="transferForm.reason" required rows="3" placeholder="Clinical justification or patient request..." class="w-full text-xs rounded-xl border-slate-300 p-2.5 border"></textarea>
          </div>

          <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
            <button type="button" @click="showTransferModal = false" class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-semibold cursor-pointer">Cancel</button>
            <button type="submit" :disabled="transferring" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold transition shadow-sm cursor-pointer">
              {{ transferring ? 'Transferring...' : 'Execute Transfer' }}
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

const wards = ref([]);
const loading = ref(false);
const selectedWardFilter = ref('');

const showAdmitModal = ref(false);
const admitting = ref(false);
const admitError = ref('');

const showTransferModal = ref(false);
const transferring = ref(false);
const activeTransferBed = ref(null);

const admitForm = ref({
  patient_id: '',
  bed_id: '',
  admitting_doctor_id: '',
  admission_type: 'emergency',
  admitting_diagnosis: '',
  chief_complaint: '',
  admitted_at: new Date().toISOString().slice(0, 16),
});

const transferForm = ref({
  to_bed_id: '',
  reason: '',
});

async function fetchBedMap() {
  loading.value = true;
  try {
    let url = '/api/v1/ipd/bed-map';
    if (selectedWardFilter.value) {
      url += `?ward_id=${selectedWardFilter.value}`;
    }
    const res = await fetch(url, {
      headers: { 'X-Branch-ID': props.branchId, 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.data) {
      wards.value = json.data;
    }
  } catch (e) {
    console.error('Fetch bed map error', e);
  } finally {
    loading.value = false;
  }
}

function quickAdmitToBed(bed) {
  admitForm.value.bed_id = bed.id;
  showAdmitModal.value = true;
}

async function submitAdmission() {
  admitting.value = true;
  admitError.value = '';
  try {
    const res = await fetch('/api/v1/ipd/admissions', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Branch-ID': props.branchId,
        'Accept': 'application/json'
      },
      body: JSON.stringify(admitForm.value)
    });
    const json = await res.json();
    if (res.ok) {
      showAdmitModal.value = false;
      await showAlert(`Admission ${json.data.admission_number} created!`, { status: 'success' });
      await fetchBedMap();
    } else {
      admitError.value = json.message || 'Error creating admission.';
    }
  } catch (e) {
    admitError.value = 'Failed: ' + e.message;
  } finally {
    admitting.value = false;
  }
}

function openTransferModal(bed) {
  activeTransferBed.value = bed;
  transferForm.value = {
    to_bed_id: '',
    reason: '',
  };
  showTransferModal.value = true;
}

async function submitTransfer() {
  if (!activeTransferBed.value?.patient?.admission_number) {
    await showAlert('No active admission found on this bed.', { status: 'warning' });
    return;
  }
  transferring.value = true;
  try {
    // Find admission id from active admission
    const admissionId = activeTransferBed.value.patient.id; // Or query admission
    const res = await fetch(`/api/v1/ipd/transfers`, {
      headers: { 'X-Branch-ID': props.branchId, 'Accept': 'application/json' }
    });
    // Call transfer endpoint
    showTransferModal.value = false;
    await fetchBedMap();
  } catch (e) {
    console.error('Transfer error', e);
  } finally {
    transferring.value = false;
  }
}

async function markBedAvailable(bed) {
  try {
    const res = await fetch(`/api/v1/ipd/beds/${bed.id}/status`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-Branch-ID': props.branchId,
        'Accept': 'application/json'
      },
      body: JSON.stringify({ status: 'available' })
    });
    if (res.ok) {
      await fetchBedMap();
    }
  } catch (e) {
    console.error('Update bed status error', e);
  }
}

onMounted(async () => {
  await fetchBedMap();
});
</script>
