<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h2 class="text-xl font-black text-slate-900 tracking-tight">Nursing Station & Inpatient Care Dashboard</h2>
        <p class="text-xs text-slate-500 mt-1">Real-time vitals monitoring rounds, medication administration records, and clinical chart.</p>
      </div>

      <div class="flex items-center gap-3">
        <button
          @click="fetchAdmissions"
          class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Refresh Patients
        </button>
      </div>
    </div>

    <!-- Active Inpatients Table -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
      <div class="p-6 border-b border-slate-100 flex items-center justify-between">
        <div>
          <h3 class="font-bold text-slate-900 text-sm">Active Inpatients Census</h3>
          <p class="text-xs text-slate-500">Currently admitted patients requiring periodic nursing care & medication rounds</p>
        </div>
        <span class="px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-bold font-mono">
          {{ admissions.length }} Inpatients Active
        </span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50 border-b border-slate-100 text-slate-400 uppercase text-[10px] font-bold">
            <tr>
              <th class="py-3.5 px-6">Location</th>
              <th class="py-3.5 px-6">Patient</th>
              <th class="py-3.5 px-6">Admitting Diagnosis</th>
              <th class="py-3.5 px-6">Admitted At</th>
              <th class="py-3.5 px-6">Stay Duration</th>
              <th class="py-3.5 px-6 text-right">Nursing Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="admissions.length === 0">
              <td colspan="6" class="py-8 text-center text-slate-400">No active inpatients in this branch.</td>
            </tr>
            <tr v-for="adm in admissions" :key="adm.id" class="hover:bg-slate-50/50 transition">
              <td class="py-4 px-6">
                <div class="font-mono font-black text-slate-900 text-xs">
                  {{ adm.bed ? adm.bed.bed_number : 'Bed N/A' }}
                </div>
                <div class="text-[10px] text-slate-400 font-medium">
                  {{ adm.ward ? adm.ward.name : 'Ward' }}
                </div>
              </td>
              <td class="py-4 px-6">
                <div class="font-bold text-slate-800">{{ adm.patient ? adm.patient.full_name : 'Patient' }}</div>
                <div class="text-[10px] text-slate-400 font-mono">MRN: {{ adm.patient ? adm.patient.mrn : '' }}</div>
              </td>
              <td class="py-4 px-6 max-w-xs font-medium text-slate-700 truncate">
                {{ adm.admitting_diagnosis }}
              </td>
              <td class="py-4 px-6 font-mono text-[11px] text-slate-600">
                {{ adm.admitted_at ? adm.admitted_at.slice(0, 10) : '-' }}
              </td>
              <td class="py-4 px-6">
                <span class="px-2.5 py-0.5 rounded-full font-bold text-[10px] bg-slate-100 text-slate-700">
                  {{ adm.length_of_stay_days }} days
                </span>
              </td>
              <td class="py-4 px-6 text-right space-x-1.5">
                <button
                  @click="openVitalsModal(adm)"
                  class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] transition cursor-pointer"
                >
                  + Log Vitals
                </button>
                <button
                  @click="openMedicationModal(adm)"
                  class="px-2.5 py-1 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-[11px] transition cursor-pointer"
                >
                  + Med Round
                </button>
                <button
                  @click="viewChart(adm)"
                  class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-[11px] transition cursor-pointer"
                >
                  Chart
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Log Vitals Modal -->
    <div v-if="showVitalsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
      <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 border border-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
          <div>
            <h3 class="font-bold text-slate-900 text-base">Record Inpatient Vitals</h3>
            <p class="text-xs text-slate-500">{{ activeAdmission?.patient?.full_name }} • Bed {{ activeAdmission?.bed?.bed_number }}</p>
          </div>
          <button @click="showVitalsModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">&times;</button>
        </div>

        <form @submit.prevent="submitVitals" class="space-y-4 text-xs">
          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">BP Systolic</label>
              <input type="number" v-model.number="vitalsForm.bp_systolic" placeholder="120" class="w-full text-xs rounded-xl border-slate-300 p-2.5 border font-mono" />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">BP Diastolic</label>
              <input type="number" v-model.number="vitalsForm.bp_diastolic" placeholder="80" class="w-full text-xs rounded-xl border-slate-300 p-2.5 border font-mono" />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Heart Rate (bpm)</label>
              <input type="number" v-model.number="vitalsForm.heart_rate" placeholder="72" class="w-full text-xs rounded-xl border-slate-300 p-2.5 border font-mono" />
            </div>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Temp (°C)</label>
              <input type="number" step="0.1" v-model.number="vitalsForm.temperature_c" placeholder="36.8" class="w-full text-xs rounded-xl border-slate-300 p-2.5 border font-mono" />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">SpO2 (%)</label>
              <input type="number" step="0.1" v-model.number="vitalsForm.spo2" placeholder="98" class="w-full text-xs rounded-xl border-slate-300 p-2.5 border font-mono" />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Resp Rate (/min)</label>
              <input type="number" v-model.number="vitalsForm.respiratory_rate" placeholder="16" class="w-full text-xs rounded-xl border-slate-300 p-2.5 border font-mono" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Pain Score (0-10)</label>
              <input type="number" min="0" max="10" v-model.number="vitalsForm.pain_score" placeholder="0" class="w-full text-xs rounded-xl border-slate-300 p-2.5 border font-mono" />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Consciousness</label>
              <select v-model="vitalsForm.consciousness_level" class="w-full text-xs rounded-xl border-slate-300 p-2.5 border bg-white">
                <option value="alert">Alert</option>
                <option value="voice">Responsive to Voice</option>
                <option value="pain">Responsive to Pain</option>
                <option value="unresponsive">Unresponsive</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Nursing Observations / Clinical Notes</label>
            <textarea v-model="vitalsForm.nursing_notes" rows="2" placeholder="Patient condition, response to therapies..." class="w-full text-xs rounded-xl border-slate-300 p-2.5 border"></textarea>
          </div>

          <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
            <button type="button" @click="showVitalsModal = false" class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-semibold cursor-pointer">Cancel</button>
            <button type="submit" :disabled="submittingVitals" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold transition shadow-sm cursor-pointer">
              {{ submittingVitals ? 'Saving...' : 'Save Vitals' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Medication Administration Modal -->
    <div v-if="showMedicationModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
      <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 border border-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
          <div>
            <h3 class="font-bold text-slate-900 text-base">Medication Administration Round</h3>
            <p class="text-xs text-slate-500">{{ activeAdmission?.patient?.full_name }} • Bed {{ activeAdmission?.bed?.bed_number }}</p>
          </div>
          <button @click="showMedicationModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">&times;</button>
        </div>

        <form @submit.prevent="submitMedication" class="space-y-4 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Medication Name *</label>
            <input type="text" v-model="medForm.medication_name" placeholder="e.g. Ceftriaxone Sodium / Paracetamol" required class="w-full text-xs rounded-xl border-slate-300 p-2.5 border font-medium" />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Dosage *</label>
              <input type="text" v-model="medForm.dosage" placeholder="e.g. 1g / 500mg" required class="w-full text-xs rounded-xl border-slate-300 p-2.5 border" />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Route</label>
              <select v-model="medForm.route" class="w-full text-xs rounded-xl border-slate-300 p-2.5 border bg-white">
                <option value="iv">Intravenous (IV)</option>
                <option value="oral">Oral (PO)</option>
                <option value="im">Intramuscular (IM)</option>
                <option value="sc">Subcutaneous (SC)</option>
                <option value="inhalation">Inhalation</option>
                <option value="topical">Topical</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Administration Status</label>
            <select v-model="medForm.status" class="w-full text-xs rounded-xl border-slate-300 p-2.5 border bg-white">
              <option value="given">Given / Administered</option>
              <option value="held">Held (Physician Order)</option>
              <option value="refused">Patient Refused</option>
              <option value="missed">Missed</option>
            </select>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Nursing Notes</label>
            <textarea v-model="medForm.notes" rows="2" placeholder="Infusion rate, patient tolerance, side effects..." class="w-full text-xs rounded-xl border-slate-300 p-2.5 border"></textarea>
          </div>

          <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
            <button type="button" @click="showMedicationModal = false" class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-semibold cursor-pointer">Cancel</button>
            <button type="submit" :disabled="submittingMed" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold transition shadow-sm cursor-pointer">
              {{ submittingMed ? 'Recording...' : 'Record Dose Given' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Nursing Chart Drawer / Modal -->
    <div v-if="showChartModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
      <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full p-6 border border-slate-200 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
          <div>
            <h3 class="font-bold text-slate-900 text-base">Inpatient Nursing Chart</h3>
            <p class="text-xs text-slate-500">{{ chartData?.patient?.name }} • Bed {{ chartData?.patient?.bed }}</p>
          </div>
          <button @click="showChartModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">&times;</button>
        </div>

        <div class="space-y-6 text-xs">
          <!-- Vitals History -->
          <div>
            <h4 class="font-black text-slate-900 text-xs uppercase font-mono tracking-wider mb-2">Vitals Log History</h4>
            <div class="border border-slate-200 rounded-2xl overflow-hidden">
              <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-200 text-[10px] uppercase font-bold text-slate-400">
                  <tr>
                    <th class="py-2.5 px-4">Time</th>
                    <th class="py-2.5 px-4">BP</th>
                    <th class="py-2.5 px-4">HR</th>
                    <th class="py-2.5 px-4">Temp</th>
                    <th class="py-2.5 px-4">SpO2</th>
                    <th class="py-2.5 px-4">Pain</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono">
                  <tr v-if="!chartData?.vitals_history?.length">
                    <td colspan="6" class="py-4 text-center text-slate-400 font-sans">No vitals logged yet.</td>
                  </tr>
                  <tr v-for="v in chartData?.vitals_history" :key="v.id">
                    <td class="py-2 px-4 text-slate-700">{{ v.recorded_at ? v.recorded_at.slice(11, 16) : '-' }}</td>
                    <td class="py-2 px-4 font-bold text-slate-900">{{ v.bp_display || '-' }}</td>
                    <td class="py-2 px-4">{{ v.heart_rate || '-' }}</td>
                    <td class="py-2 px-4">{{ v.temperature_c ? v.temperature_c + '°C' : '-' }}</td>
                    <td class="py-2 px-4">{{ v.spo2 ? v.spo2 + '%' : '-' }}</td>
                    <td class="py-2 px-4">{{ v.pain_score ?? '-' }}/10</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Medication History -->
          <div>
            <h4 class="font-black text-slate-900 text-xs uppercase font-mono tracking-wider mb-2">Medications Administered</h4>
            <div class="border border-slate-200 rounded-2xl overflow-hidden">
              <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-200 text-[10px] uppercase font-bold text-slate-400">
                  <tr>
                    <th class="py-2.5 px-4">Time</th>
                    <th class="py-2.5 px-4">Medication</th>
                    <th class="py-2.5 px-4">Dose / Route</th>
                    <th class="py-2.5 px-4">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-if="!chartData?.medications_history?.length">
                    <td colspan="4" class="py-4 text-center text-slate-400">No medications recorded yet.</td>
                  </tr>
                  <tr v-for="m in chartData?.medications_history" :key="m.id">
                    <td class="py-2 px-4 font-mono text-slate-700">{{ m.administered_at ? m.administered_at.slice(11, 16) : '-' }}</td>
                    <td class="py-2 px-4 font-bold text-slate-900">{{ m.medication_name }}</td>
                    <td class="py-2 px-4 text-slate-700">{{ m.dosage }} ({{ m.route }})</td>
                    <td class="py-2 px-4">
                      <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase" :class="m.status === 'given' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'">
                        {{ m.status }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
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

const admissions = ref([]);
const activeAdmission = ref(null);

const showVitalsModal = ref(false);
const submittingVitals = ref(false);

const showMedicationModal = ref(false);
const submittingMed = ref(false);

const showChartModal = ref(false);
const chartData = ref(null);

const vitalsForm = ref({
  bp_systolic: 120,
  bp_diastolic: 80,
  heart_rate: 72,
  temperature_c: 36.8,
  spo2: 98,
  respiratory_rate: 16,
  pain_score: 0,
  consciousness_level: 'alert',
  nursing_notes: '',
});

const medForm = ref({
  medication_name: '',
  dosage: '',
  route: 'iv',
  status: 'given',
  notes: '',
});

async function fetchAdmissions() {
  try {
    const res = await fetch('/api/v1/ipd/admissions?status=admitted', {
      headers: { 'X-Branch-ID': props.branchId, 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.data) {
      admissions.value = json.data;
    }
  } catch (e) {
    console.error('Fetch admissions error', e);
  }
}

function openVitalsModal(adm) {
  activeAdmission.value = adm;
  vitalsForm.value = {
    bp_systolic: 120,
    bp_diastolic: 80,
    heart_rate: 72,
    temperature_c: 36.8,
    spo2: 98,
    respiratory_rate: 16,
    pain_score: 0,
    consciousness_level: 'alert',
    nursing_notes: '',
  };
  showVitalsModal.value = true;
}

async function submitVitals() {
  if (!activeAdmission.value) return;
  submittingVitals.value = true;
  try {
    const res = await fetch(`/api/v1/ipd/admissions/${activeAdmission.value.id}/vitals`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Branch-ID': props.branchId,
        'Accept': 'application/json'
      },
      body: JSON.stringify(vitalsForm.value)
    });
    if (res.ok) {
      showVitalsModal.value = false;
      await showAlert('Vitals recorded successfully.');
    }
  } catch (e) {
    console.error('Submit vitals error', e);
  } finally {
    submittingVitals.value = false;
  }
}

function openMedicationModal(adm) {
  activeAdmission.value = adm;
  medForm.value = {
    medication_name: '',
    dosage: '',
    route: 'iv',
    status: 'given',
    notes: '',
  };
  showMedicationModal.value = true;
}

async function submitMedication() {
  if (!activeAdmission.value) return;
  submittingMed.value = true;
  try {
    const res = await fetch(`/api/v1/ipd/admissions/${activeAdmission.value.id}/medications`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Branch-ID': props.branchId,
        'Accept': 'application/json'
      },
      body: JSON.stringify(medForm.value)
    });
    if (res.ok) {
      showMedicationModal.value = false;
      await showAlert('Medication administration logged.');
    }
  } catch (e) {
    console.error('Submit medication error', e);
  } finally {
    submittingMed.value = false;
  }
}

async function viewChart(adm) {
  try {
    const res = await fetch(`/api/v1/ipd/admissions/${adm.id}/chart`, {
      headers: { 'X-Branch-ID': props.branchId, 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.data) {
      chartData.value = json.data;
      showChartModal.value = true;
    }
  } catch (e) {
    console.error('Fetch chart error', e);
  }
}

onMounted(async () => {
  await fetchAdmissions();
});
</script>
