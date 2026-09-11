<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <div class="flex items-center gap-3">
          <h2 class="text-xl font-black text-slate-900 tracking-tight">SOAP Clinical Consultation Notes</h2>
          <span
            v-if="currentNote"
            class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider"
            :class="{
              'bg-slate-100 text-slate-700': currentNote.notes_status === 'draft',
              'bg-emerald-100 text-emerald-800': currentNote.notes_status === 'signed_off',
              'bg-purple-100 text-purple-800': currentNote.notes_status === 'amended'
            }"
          >
            Version {{ currentNote.version }} • {{ currentNote.notes_status }}
          </span>
        </div>
        <p class="text-xs text-slate-500 mt-1">Structured medical documentation (Subjective, Objective, Assessment, Plan) with legal immutability ledger.</p>
      </div>

      <div class="flex items-center gap-3">
        <!-- New Note Button -->
        <button
          @click="initNewNote"
          class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition cursor-pointer"
        >
          + New Note
        </button>

        <!-- Save Draft (only if draft) -->
        <button
          v-if="!currentNote || !currentNote.is_signed_off"
          @click="saveDraft"
          :disabled="saving"
          class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition cursor-pointer disabled:opacity-50"
        >
          {{ saving ? 'Saving...' : 'Save Draft' }}
        </button>

        <!-- Sign Off (Seal note) -->
        <button
          v-if="currentNote && !currentNote.is_signed_off"
          @click="signOffNote"
          :disabled="signing"
          class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-sm disabled:opacity-50"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          </svg>
          {{ signing ? 'Signing...' : 'Sign Off & Seal Note' }}
        </button>

        <!-- Amend Signed Note (creates version N+1) -->
        <button
          v-if="currentNote && currentNote.is_signed_off"
          @click="showAmendModal = true"
          class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-sm"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
          </svg>
          Amend Signed Note (v{{ currentNote.version + 1 }})
        </button>
      </div>
    </div>

    <!-- Patient Selector & Context Strip -->
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs">
      <div class="flex items-center gap-3">
        <label class="font-bold text-slate-700">Patient UUID / MRN:</label>
        <input
          type="text"
          v-model="patientId"
          placeholder="Paste Patient UUID"
          class="rounded-xl border-slate-300 p-2 border font-mono text-xs w-72"
        />
        <button
          @click="fetchPatientNotes"
          class="px-3 py-1.5 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 transition"
        >
          Load Notes
        </button>
      </div>

      <!-- Immutability Warning Banner -->
      <div v-if="currentNote && currentNote.is_signed_off" class="text-amber-800 bg-amber-50 px-3 py-1.5 rounded-xl border border-amber-200 font-medium flex items-center gap-2">
        <svg class="w-4 h-4 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
        <span>This note is legally signed off. Direct modifications are locked. Use "Amend" for revisions.</span>
      </div>
    </div>

    <!-- SOAP 4-Quadrant Editor -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- 1. SUBJECTIVE (S) -->
      <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2">
            <span class="w-6 h-6 rounded-lg bg-blue-600 text-white flex items-center justify-center font-black text-xs">S</span>
            <h3 class="font-black text-slate-900 text-sm">Subjective (Patient Narrative)</h3>
          </div>
          <span class="text-[10px] text-slate-400 uppercase font-mono font-bold">History & Symptoms</span>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Chief Complaint (CC) *</label>
          <textarea
            v-model="form.chief_complaint"
            :disabled="isLocked"
            rows="2"
            placeholder="Primary reason for seeking medical care in patient's words..."
            class="w-full text-xs rounded-xl border-slate-300 p-2.5 border disabled:bg-slate-50 disabled:text-slate-500"
          ></textarea>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">History of Presenting Illness (HPI)</label>
          <textarea
            v-model="form.history_of_presenting_illness"
            :disabled="isLocked"
            rows="3"
            placeholder="Onset, duration, severity, aggravating/relieving factors..."
            class="w-full text-xs rounded-xl border-slate-300 p-2.5 border disabled:bg-slate-50 disabled:text-slate-500"
          ></textarea>
        </div>
      </div>

      <!-- 2. OBJECTIVE (O) -->
      <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2">
            <span class="w-6 h-6 rounded-lg bg-emerald-600 text-white flex items-center justify-center font-black text-xs">O</span>
            <h3 class="font-black text-slate-900 text-sm">Objective (Vitals & Exam)</h3>
          </div>
          <span class="text-[10px] text-slate-400 uppercase font-mono font-bold">Clinical Findings</span>
        </div>

        <!-- Vitals Grid -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-2">Patient Vitals</label>
          <div class="grid grid-cols-4 gap-2 text-xs">
            <div>
              <span class="text-[10px] text-slate-500">BP Systolic</span>
              <input type="number" v-model.number="form.vitals.bp_systolic" :disabled="isLocked" placeholder="120" class="w-full p-1.5 border rounded-lg text-xs font-mono disabled:bg-slate-50" />
            </div>
            <div>
              <span class="text-[10px] text-slate-500">BP Diastolic</span>
              <input type="number" v-model.number="form.vitals.bp_diastolic" :disabled="isLocked" placeholder="80" class="w-full p-1.5 border rounded-lg text-xs font-mono disabled:bg-slate-50" />
            </div>
            <div>
              <span class="text-[10px] text-slate-500">Heart Rate (bpm)</span>
              <input type="number" v-model.number="form.vitals.heart_rate" :disabled="isLocked" placeholder="72" class="w-full p-1.5 border rounded-lg text-xs font-mono disabled:bg-slate-50" />
            </div>
            <div>
              <span class="text-[10px] text-slate-500">Temp (°C)</span>
              <input type="number" step="0.1" v-model.number="form.vitals.temperature_c" :disabled="isLocked" placeholder="36.8" class="w-full p-1.5 border rounded-lg text-xs font-mono disabled:bg-slate-50" />
            </div>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Physical Examination (PE)</label>
          <textarea
            v-model="form.physical_examination"
            :disabled="isLocked"
            rows="3"
            placeholder="Head, ENT, respiratory sounds, abdominal palpation, neuro exam..."
            class="w-full text-xs rounded-xl border-slate-300 p-2.5 border disabled:bg-slate-50 disabled:text-slate-500"
          ></textarea>
        </div>
      </div>

      <!-- 3. ASSESSMENT (A) -->
      <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2">
            <span class="w-6 h-6 rounded-lg bg-amber-600 text-white flex items-center justify-center font-black text-xs">A</span>
            <h3 class="font-black text-slate-900 text-sm">Assessment (Diagnosis)</h3>
          </div>
          <span class="text-[10px] text-slate-400 uppercase font-mono font-bold">Clinical Impression</span>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Provisional Diagnosis *</label>
          <input
            type="text"
            v-model="form.provisional_diagnosis"
            :disabled="isLocked"
            placeholder="e.g. Acute Migraine without Aura / Essential Hypertension"
            class="w-full text-xs rounded-xl border-slate-300 p-2.5 border disabled:bg-slate-50 disabled:text-slate-500"
          />
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Differential Diagnoses</label>
          <textarea
            v-model="form.differential_diagnoses"
            :disabled="isLocked"
            rows="2"
            placeholder="Other plausible differentials to rule out..."
            class="w-full text-xs rounded-xl border-slate-300 p-2.5 border disabled:bg-slate-50 disabled:text-slate-500"
          ></textarea>
        </div>
      </div>

      <!-- 4. PLAN (P) -->
      <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2">
            <span class="w-6 h-6 rounded-lg bg-purple-600 text-white flex items-center justify-center font-black text-xs">P</span>
            <h3 class="font-black text-slate-900 text-sm">Plan (Therapeutics & Orders)</h3>
          </div>
          <span class="text-[10px] text-slate-400 uppercase font-mono font-bold">Care Roadmap</span>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Treatment Plan *</label>
          <textarea
            v-model="form.treatment_plan"
            :disabled="isLocked"
            rows="2"
            placeholder="Immediate clinical management and interventions..."
            class="w-full text-xs rounded-xl border-slate-300 p-2.5 border disabled:bg-slate-50 disabled:text-slate-500"
          ></textarea>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Prescriptions & Medication Advice</label>
          <textarea
            v-model="form.prescriptions_advice"
            :disabled="isLocked"
            rows="2"
            placeholder="e.g. Paracetamol 500mg PO QDS for 3 days; Amoxicillin 500mg TDS..."
            class="w-full text-xs rounded-xl border-slate-300 p-2.5 border disabled:bg-slate-50 disabled:text-slate-500"
          ></textarea>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Follow-Up Date</label>
            <input
              type="date"
              v-model="form.follow_up_recommended_date"
              :disabled="isLocked"
              class="w-full text-xs rounded-xl border-slate-300 p-2 border disabled:bg-slate-50"
            />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Diagnostic Orders</label>
            <input
              type="text"
              v-model="form.orders_requested"
              :disabled="isLocked"
              placeholder="e.g. FBC, ESR, Chest X-ray"
              class="w-full text-xs rounded-xl border-slate-300 p-2 border disabled:bg-slate-50"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Amendment Modal -->
    <div v-if="showAmendModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
      <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 border border-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
          <div>
            <h3 class="font-bold text-slate-900 text-base">Amend Signed Consultation Note</h3>
            <p class="text-xs text-slate-500">Creates legally sealed Version {{ currentNote.version + 1 }} linked to original</p>
          </div>
          <button @click="showAmendModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">&times;</button>
        </div>

        <form @submit.prevent="submitAmendment" class="space-y-4 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Reason for Legal Amendment *</label>
            <textarea
              v-model="amendmentReason"
              required
              rows="3"
              placeholder="e.g. Patient contacted 4 hours post-discharge reporting side-effect; dosage amended."
              class="w-full text-xs rounded-xl border-slate-300 p-2.5 border"
            ></textarea>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Updated Treatment Plan</label>
            <textarea
              v-model="amendedTreatmentPlan"
              rows="3"
              class="w-full text-xs rounded-xl border-slate-300 p-2.5 border"
            ></textarea>
          </div>

          <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
            <button type="button" @click="showAmendModal = false" class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-semibold cursor-pointer">Cancel</button>
            <button type="submit" :disabled="amending" class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold transition shadow-sm cursor-pointer">
              {{ amending ? 'Submitting Amendment...' : 'Sign & Lock Version ' + (currentNote.version + 1) }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  branchId: { type: String, required: true },
});

const patientId = ref('');
const currentNote = ref(null);
const saving = ref(false);
const signing = ref(false);
const amending = ref(false);
const showAmendModal = ref(false);
const amendmentReason = ref('');
const amendedTreatmentPlan = ref('');

const isLocked = computed(() => {
  return currentNote.value && currentNote.value.is_signed_off;
});

const form = ref({
  patient_id: '',
  chief_complaint: '',
  history_of_presenting_illness: '',
  vitals: {
    bp_systolic: null,
    bp_diastolic: null,
    heart_rate: null,
    temperature_c: null,
  },
  physical_examination: '',
  provisional_diagnosis: '',
  differential_diagnoses: '',
  treatment_plan: '',
  prescriptions_advice: '',
  orders_requested: '',
  follow_up_recommended_date: '',
});

function initNewNote() {
  currentNote.value = null;
  form.value = {
    patient_id: patientId.value,
    chief_complaint: '',
    history_of_presenting_illness: '',
    vitals: {
      bp_systolic: null,
      bp_diastolic: null,
      heart_rate: null,
      temperature_c: null,
    },
    physical_examination: '',
    provisional_diagnosis: '',
    differential_diagnoses: '',
    treatment_plan: '',
    prescriptions_advice: '',
    orders_requested: '',
    follow_up_recommended_date: '',
  };
}

async function fetchPatientNotes() {
  if (!patientId.value) return;
  try {
    const res = await fetch(`/api/v1/opd/soap-notes?patient_id=${patientId.value}`, {
      headers: { 'X-Branch-ID': props.branchId, 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.data && json.data.length > 0) {
      loadNote(json.data[0]);
    } else {
      initNewNote();
      alert('No previous consultation notes found for this patient. Ready for new entry.');
    }
  } catch (e) {
    console.error('Fetch notes error', e);
  }
}

function loadNote(note) {
  currentNote.value = note;
  form.value = {
    patient_id: note.patient_id,
    chief_complaint: note.chief_complaint || '',
    history_of_presenting_illness: note.history_of_presenting_illness || '',
    vitals: note.vitals || {},
    physical_examination: note.physical_examination || '',
    provisional_diagnosis: note.provisional_diagnosis || '',
    differential_diagnoses: note.differential_diagnoses || '',
    treatment_plan: note.treatment_plan || '',
    prescriptions_advice: note.prescriptions_advice || '',
    orders_requested: note.orders_requested || '',
    follow_up_recommended_date: note.follow_up_recommended_date || '',
  };
  amendedTreatmentPlan.value = note.treatment_plan || '';
}

async function saveDraft() {
  saving.value = true;
  form.value.patient_id = patientId.value;
  try {
    let url = '/api/v1/opd/soap-notes';
    let method = 'POST';

    if (currentNote.value && currentNote.value.id && !currentNote.value.is_signed_off) {
      url = `/api/v1/opd/soap-notes/${currentNote.value.id}`;
      method = 'PUT';
    }

    const res = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-Branch-ID': props.branchId,
        'Accept': 'application/json'
      },
      body: JSON.stringify(form.value)
    });
    const json = await res.json();
    if (res.ok) {
      currentNote.value = json.data;
      alert(json.message || 'Draft saved.');
    } else {
      alert(json.message || 'Error saving note.');
    }
  } catch (e) {
    console.error('Save draft error', e);
  } finally {
    saving.value = false;
  }
}

async function signOffNote() {
  if (!currentNote.value || !currentNote.value.id) return;
  if (!confirm('Once signed off, this clinical note is legally sealed and cannot be modified. Continue?')) return;
  signing.value = true;
  try {
    const res = await fetch(`/api/v1/opd/soap-notes/${currentNote.value.id}/sign-off`, {
      method: 'POST',
      headers: {
        'X-Branch-ID': props.branchId,
        'Accept': 'application/json'
      }
    });
    const json = await res.json();
    if (res.ok) {
      currentNote.value = json.data;
      alert('Consultation note successfully signed off and sealed!');
    }
  } catch (e) {
    console.error('Sign off error', e);
  } finally {
    signing.value = false;
  }
}

async function submitAmendment() {
  if (!currentNote.value || !amendmentReason.value) return;
  amending.value = true;
  try {
    const res = await fetch(`/api/v1/opd/soap-notes/${currentNote.value.id}/amend`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Branch-ID': props.branchId,
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        treatment_plan: amendedTreatmentPlan.value,
        amendment_reason: amendmentReason.value,
      })
    });
    const json = await res.json();
    if (res.ok) {
      showAmendModal.value = false;
      loadNote(json.data);
      alert(`Note amended successfully! Version ${json.data.version} created.`);
    } else {
      alert(json.message || 'Error amending note');
    }
  } catch (e) {
    console.error('Amendment error', e);
  } finally {
    amending.value = false;
  }
}
</script>
