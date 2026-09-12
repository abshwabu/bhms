<template>
  <div class="space-y-6">
    <!-- Header Action Bar -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200/80 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2">
          <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-100 text-indigo-800 uppercase">E-Prescribing Station</span>
          <span class="text-xs text-slate-400 font-mono">CDS Engine Active</span>
        </div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">Prescription Writer</h1>
        <p class="text-sm text-slate-500 mt-0.5">
          Patient: <strong class="text-slate-800">{{ activePatient?.name || 'Selected Patient' }}</strong>
          <span class="font-mono text-xs text-slate-500 ml-1">({{ activePatient?.mrn || 'No MRN' }})</span>
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          @click="$emit('back')"
          class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-4 py-2.5 rounded-xl transition cursor-pointer"
        >
          Cancel
        </button>

        <button
          @click="handleSavePrescription('draft')"
          :disabled="isSubmitting || items.length === 0"
          class="bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition cursor-pointer disabled:opacity-50"
        >
          Save Draft
        </button>

        <button
          @click="handleSavePrescription('finalized')"
          :disabled="isSubmitting || items.length === 0"
          class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-5 py-2.5 rounded-xl shadow-sm shadow-blue-500/20 transition cursor-pointer disabled:opacity-50 flex items-center gap-1.5"
        >
          <span>Finalize & Issue</span>
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Documented Allergies Alert Banner -->
    <div
      v-if="allergies && allergies.length > 0"
      class="p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-start gap-3 text-xs"
    >
      <div class="text-xl shrink-0">⚠️</div>
      <div class="flex-1 space-y-1">
        <div class="font-bold text-rose-900 uppercase tracking-wider text-[11px]">Documented Patient Allergies</div>
        <div class="flex flex-wrap gap-2 pt-1">
          <span
            v-for="al in allergies"
            :key="al.allergen"
            class="px-2.5 py-1 rounded-lg bg-rose-100/80 border border-rose-200 text-rose-800 font-semibold text-xs flex items-center gap-1.5"
          >
            <span>🛑 {{ al.allergen }}</span>
            <span class="text-[10px] font-normal text-rose-600">({{ al.reaction || 'Anaphylaxis/Rash' }} - {{ al.severity || 'Moderate' }})</span>
          </span>
        </div>
      </div>
    </div>

    <!-- REAL-TIME CLINICAL DECISION SUPPORT (CDS) WARNINGS -->
    <div v-if="cdsAlerts.length > 0" class="space-y-3">
      <div class="flex items-center justify-between">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
          <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
          Clinical Decision Support: Safety Alerts ({{ cdsAlerts.length }})
        </h2>
        <span
          class="text-[11px] font-bold px-2 py-0.5 rounded-md"
          :class="hasHighSeverityWarnings ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800'"
        >
          {{ hasHighSeverityWarnings ? 'Contraindication Detected (Override Required)' : 'Clinical Warnings Detected' }}
        </span>
      </div>

      <div class="space-y-2">
        <div
          v-for="(alert, idx) in cdsAlerts"
          :key="idx"
          class="p-4 rounded-2xl border text-xs space-y-1.5 transition"
          :class="alert.severity === 'high' ? 'bg-rose-50/80 border-rose-300 text-rose-950' : 'bg-amber-50/80 border-amber-300 text-amber-950'"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="text-sm">{{ alert.severity === 'high' ? '🚨' : '⚠️' }}</span>
              <strong class="text-sm font-black">{{ alert.title }}</strong>
            </div>
            <span
              class="px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase"
              :class="alert.severity === 'high' ? 'bg-rose-200 text-rose-900' : 'bg-amber-200 text-amber-900'"
            >
              {{ alert.severity }} Severity
            </span>
          </div>

          <p class="text-xs leading-relaxed">{{ alert.description }}</p>

          <div class="p-2 rounded-lg bg-white/70 border text-[11px] font-medium" :class="alert.severity === 'high' ? 'border-rose-200 text-rose-900' : 'border-amber-200 text-amber-900'">
            <strong>Recommendation:</strong> {{ alert.recommendation }}
          </div>
        </div>
      </div>
    </div>

    <!-- Prescription Item Entry Form -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-5">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div>
          <h2 class="font-bold text-slate-800 text-base">Medication Regimen</h2>
          <p class="text-xs text-slate-400">Add medications with complete dosage, route, frequency, and duration.</p>
        </div>

        <!-- Quick Presets -->
        <div class="flex items-center gap-1.5 text-xs">
          <span class="text-slate-400 text-[11px] font-semibold">Presets:</span>
          <button
            v-for="preset in quickPresets"
            :key="preset.name"
            @click="addPreset(preset)"
            type="button"
            class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg transition cursor-pointer text-[11px]"
          >
            + {{ preset.name }}
          </button>
        </div>
      </div>

      <!-- Prescribed Items List -->
      <div class="space-y-4">
        <div
          v-for="(item, index) in items"
          :key="index"
          class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-3 relative group"
        >
          <div class="flex items-center justify-between">
            <span class="font-mono text-xs font-bold text-slate-400">#{{ index + 1 }} Drug Item</span>
            <button
              v-if="items.length > 1"
              @click="removeItem(index)"
              type="button"
              class="text-rose-600 hover:text-rose-800 text-xs font-bold cursor-pointer"
            >
              Remove
            </button>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
            <!-- Medication Name -->
            <div class="lg:col-span-2">
              <label class="block font-bold text-slate-700 mb-1">Medication / Brand Name <span class="text-rose-500">*</span></label>
              <input
                v-model="item.medication_name"
                @input="triggerDebouncedCdsCheck"
                type="text"
                required
                placeholder="e.g. Amoxicillin, Metformin, Warfarin, Lisinopril..."
                class="w-full p-2.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 font-semibold text-slate-900"
              />
            </div>

            <!-- Generic Name -->
            <div>
              <label class="block font-bold text-slate-700 mb-1">Generic Name</label>
              <input
                v-model="item.generic_name"
                @input="triggerDebouncedCdsCheck"
                type="text"
                placeholder="e.g. Amoxicillin trihydrate"
                class="w-full p-2.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 text-slate-700"
              />
            </div>

            <!-- Form -->
            <div>
              <label class="block font-bold text-slate-700 mb-1">Form</label>
              <select v-model="item.form" class="w-full p-2.5 bg-white border border-slate-200 rounded-xl font-medium">
                <option value="tablet">Tablet</option>
                <option value="capsule">Capsule</option>
                <option value="syrup">Syrup / Liquid</option>
                <option value="injection">Injection (Vial/Ampoule)</option>
                <option value="inhaler">Inhaler</option>
                <option value="ointment">Topical Ointment</option>
                <option value="drops">Eye / Ear Drops</option>
              </select>
            </div>

            <!-- Dosage -->
            <div>
              <label class="block font-bold text-slate-700 mb-1">Dosage <span class="text-rose-500">*</span></label>
              <input
                v-model="item.dosage"
                type="text"
                required
                placeholder="e.g. 500 mg, 10 ml, 2 puffs"
                class="w-full p-2.5 bg-white border border-slate-200 rounded-xl text-slate-800"
              />
            </div>

            <!-- Route -->
            <div>
              <label class="block font-bold text-slate-700 mb-1">Route</label>
              <select v-model="item.route" class="w-full p-2.5 bg-white border border-slate-200 rounded-xl font-medium">
                <option value="oral">Oral (PO)</option>
                <option value="intravenous">Intravenous (IV)</option>
                <option value="intramuscular">Intramuscular (IM)</option>
                <option value="sublingual">Sublingual (SL)</option>
                <option value="topical">Topical</option>
                <option value="inhalation">Inhalation</option>
              </select>
            </div>

            <!-- Frequency -->
            <div>
              <label class="block font-bold text-slate-700 mb-1">Frequency <span class="text-rose-500">*</span></label>
              <select v-model="item.frequency" class="w-full p-2.5 bg-white border border-slate-200 rounded-xl font-medium">
                <option value="OD (Once daily)">OD (Once daily)</option>
                <option value="BID (Twice daily)">BID (Twice daily)</option>
                <option value="TID (Three times daily)">TID (Three times daily)</option>
                <option value="QID (Four times daily)">QID (Four times daily)</option>
                <option value="PRN (As needed)">PRN (As needed)</option>
                <option value="Stat (Immediate single dose)">Stat (Immediate single dose)</option>
              </select>
            </div>

            <!-- Duration (Days) -->
            <div>
              <label class="block font-bold text-slate-700 mb-1">Duration (Days) <span class="text-rose-500">*</span></label>
              <input
                v-model.number="item.duration_days"
                type="number"
                min="1"
                required
                placeholder="7"
                class="w-full p-2.5 bg-white border border-slate-200 rounded-xl text-slate-800"
              />
            </div>

            <!-- Quantity -->
            <div>
              <label class="block font-bold text-slate-700 mb-1">Total Quantity <span class="text-rose-500">*</span></label>
              <input
                v-model.number="item.quantity"
                type="number"
                min="1"
                required
                placeholder="21"
                class="w-full p-2.5 bg-white border border-slate-200 rounded-xl text-slate-800"
              />
            </div>

            <!-- Instructions -->
            <div class="lg:col-span-3">
              <label class="block font-bold text-slate-700 mb-1">Patient Instructions / Auxiliary Warnings</label>
              <input
                v-model="item.instructions"
                type="text"
                placeholder="e.g. Take with food. Finish all medication even if feeling better."
                class="w-full p-2.5 bg-white border border-slate-200 rounded-xl text-slate-800"
              />
            </div>
          </div>
        </div>

        <button
          @click="addNewItem"
          type="button"
          class="w-full py-3 rounded-xl border-2 border-dashed border-slate-300 hover:border-blue-400 hover:bg-blue-50/30 text-blue-600 font-bold text-xs flex items-center justify-center gap-1 transition cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Add Another Medication to Prescription
        </button>
      </div>

      <!-- Doctor Clinical Notes -->
      <div class="pt-4 border-t border-slate-100">
        <label class="block font-bold text-slate-700 text-xs mb-1">Pharmacist & Clinical Notes</label>
        <textarea
          v-model="prescriptionNotes"
          rows="2"
          placeholder="Special pharmacy compounding instructions, substitute approval notes..."
          class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs"
        ></textarea>
      </div>
    </div>

    <!-- CLINICAL OVERRIDE MODAL: MANDATORY FOR SEVERE WARNINGS FINALIZATION -->
    <div v-if="isOverrideModalOpen" class="fixed inset-0 z-50 bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-xl w-full shadow-2xl border border-rose-300 p-6 space-y-4">
        <div class="flex items-center gap-3 text-rose-600">
          <span class="text-2xl">🚨</span>
          <div>
            <h2 class="text-lg font-black text-rose-950">Clinical Override Required</h2>
            <p class="text-xs text-rose-700 font-medium">Severe contraindications or drug-allergy alerts must be clinically justified.</p>
          </div>
        </div>

        <!-- Warning Bullet Summary -->
        <div class="p-3 bg-rose-50 rounded-xl border border-rose-200 space-y-2 max-h-48 overflow-y-auto text-xs text-rose-900">
          <div v-for="(alert, i) in cdsAlerts" :key="i" class="font-medium">
            • <strong>{{ alert.title }}</strong>: {{ alert.description }}
          </div>
        </div>

        <!-- Override Justification Input -->
        <div class="space-y-1 text-xs">
          <label class="block font-bold text-slate-800">
            Physician Override Justification Reason <span class="text-rose-600">*</span>
          </label>
          <textarea
            v-model="overrideReason"
            required
            rows="3"
            placeholder="Document clinical rationale (e.g. Critical indication; alternative contraindicated; patient will be monitored inpatient with daily creatinine and telemetry)..."
            class="w-full p-3 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white text-xs font-medium"
          ></textarea>
        </div>

        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
          <button
            type="button"
            @click="isOverrideModalOpen = false"
            class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-semibold cursor-pointer text-xs"
          >
            Cancel & Edit Drugs
          </button>
          <button
            type="button"
            @click="confirmOverrideAndFinalize"
            :disabled="!overrideReason.trim() || isSubmitting"
            class="bg-rose-600 hover:bg-rose-700 disabled:opacity-50 text-white font-bold px-5 py-2.5 rounded-xl shadow-sm transition cursor-pointer text-xs flex items-center gap-1"
          >
            <span>Confirm Override & Sign</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  patient: { type: Object, required: true },
  branchId: { type: String, required: true },
});

const emit = defineEmits(['back', 'prescriptionCreated']);

const activePatient = ref(props.patient || {});
const allergies = ref([]);
const prescriptionNotes = ref('');
const overrideReason = ref('');
const isOverrideModalOpen = ref(false);
const isSubmitting = ref(false);
const cdsAlerts = ref([]);
let cdsDebounceTimer = null;

const items = ref([
  {
    medication_name: '',
    generic_name: '',
    form: 'tablet',
    dosage: '500 mg',
    route: 'oral',
    frequency: 'BID (Twice daily)',
    duration_days: 7,
    quantity: 14,
    instructions: 'Take with food and a full glass of water',
    is_substitution_allowed: true,
  },
]);

const quickPresets = [
  { name: 'Amoxicillin 500mg (Antibiotic)', med: 'Amoxicillin', generic: 'Amoxicillin', form: 'capsule', dosage: '500 mg', freq: 'TID (Three times daily)', dur: 7, qty: 21 },
  { name: 'Warfarin 5mg (Anticoagulant)', med: 'Warfarin', generic: 'Warfarin sodium', form: 'tablet', dosage: '5 mg', freq: 'OD (Once daily)', dur: 30, qty: 30 },
  { name: 'Aspirin 81mg (Antiplatelet)', med: 'Aspirin', generic: 'Acetylsalicylic acid', form: 'tablet', dosage: '81 mg', freq: 'OD (Once daily)', dur: 30, qty: 30 },
  { name: 'Metformin 500mg (Antidiabetic)', med: 'Metformin', generic: 'Metformin HCl', form: 'tablet', dosage: '500 mg', freq: 'BID (Twice daily)', dur: 30, qty: 60 },
  { name: 'Lisinopril 10mg (ACEI)', med: 'Lisinopril', generic: 'Lisinopril', form: 'tablet', dosage: '10 mg', freq: 'OD (Once daily)', dur: 30, qty: 30 },
  { name: 'Ibuprofen 400mg (NSAID)', med: 'Ibuprofen', generic: 'Ibuprofen', form: 'tablet', dosage: '400 mg', freq: 'TID (Three times daily)', dur: 5, qty: 15 },
];

const hasHighSeverityWarnings = computed(() => {
  return cdsAlerts.value.some((a) => a.severity === 'high');
});

function addNewItem() {
  items.value.push({
    medication_name: '',
    generic_name: '',
    form: 'tablet',
    dosage: '500 mg',
    route: 'oral',
    frequency: 'OD (Once daily)',
    duration_days: 7,
    quantity: 7,
    instructions: '',
    is_substitution_allowed: true,
  });
}

function removeItem(index) {
  items.value.splice(index, 1);
  triggerDebouncedCdsCheck();
}

function addPreset(preset) {
  items.value.push({
    medication_name: preset.med,
    generic_name: preset.generic,
    form: preset.form,
    dosage: preset.dosage,
    route: 'oral',
    frequency: preset.freq,
    duration_days: preset.dur,
    quantity: preset.qty,
    instructions: 'As directed by physician',
    is_substitution_allowed: true,
  });
  triggerDebouncedCdsCheck();
}

function triggerDebouncedCdsCheck() {
  clearTimeout(cdsDebounceTimer);
  cdsDebounceTimer = setTimeout(() => {
    checkCdsInteractions();
  }, 300);
}

async function checkCdsInteractions() {
  const validItems = items.value.filter((i) => i.medication_name.trim().length > 0);
  if (validItems.length === 0) {
    cdsAlerts.value = [];
    return;
  }

  try {
    const res = await axios.post('/api/v1/clinical/prescriptions/check-interactions', {
      patient_id: activePatient.value.id,
      items: validItems,
    }, {
      headers: { 'X-Branch-ID': props.branchId, 'Accept': 'application/json' },
    });

    cdsAlerts.value = res.data.data?.alerts || [];
  } catch (err) {
    console.error('CDS check error', err);
  }
}

async function handleSavePrescription(targetStatus) {
  // If attempting to finalize and severe warnings exist, prompt for override reason
  if (targetStatus === 'finalized' && hasHighSeverityWarnings.value) {
    isOverrideModalOpen.value = true;
    return;
  }

  await executePrescriptionCreation(targetStatus);
}

async function confirmOverrideAndFinalize() {
  if (!overrideReason.value.trim()) return;
  isOverrideModalOpen.value = false;
  await executePrescriptionCreation('finalized', overrideReason.value);
}

async function executePrescriptionCreation(targetStatus, overrideJustification = null) {
  isSubmitting.value = true;

  try {
    const payload = {
      patient_id: activePatient.value.id,
      status: targetStatus,
      notes: prescriptionNotes.value,
      override_reason: overrideJustification,
      items: items.value,
    };

    const res = await axios.post('/api/v1/clinical/prescriptions', payload, {
      headers: { 'X-Branch-ID': props.branchId, 'Accept': 'application/json' },
    });

    emit('prescriptionCreated', res.data.data);
  } catch (err) {
    if (err.response?.data?.code === 'SAFETY_WARNINGS_DETECTED') {
      isOverrideModalOpen.value = true;
    } else {
      alert(err.response?.data?.message || 'Failed to save prescription.');
    }
  } finally {
    isSubmitting.value = false;
  }
}

async function fetchPatientAllergies() {
  try {
    const res = await axios.get(`/api/v1/patients/${activePatient.value.id}/allergies`, {
      headers: { 'X-Branch-ID': props.branchId, 'Accept': 'application/json' },
    });
    allergies.value = res.data.data || [];
  } catch (err) {
    // Fallback if direct patient allergies route not seeded
    allergies.value = activePatient.value.allergies || [];
  }
}

onMounted(() => {
  fetchPatientAllergies();
  if (items.value[0].medication_name) {
    checkCdsInteractions();
  }
});
</script>
