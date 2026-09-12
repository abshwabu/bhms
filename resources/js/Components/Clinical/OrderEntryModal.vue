<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-200 p-6 space-y-5">
      <!-- Modal Header -->
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div>
          <h2 class="text-lg font-black text-slate-900">Diagnostic Order Entry</h2>
          <p class="text-xs text-slate-400">Order laboratory investigations or diagnostic radiology for patient.</p>
        </div>
        <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 p-1 text-xl font-bold cursor-pointer">&times;</button>
      </div>

      <!-- Tab Switcher: Lab vs Radiology -->
      <div class="flex rounded-xl bg-slate-100 p-1 text-xs font-bold">
        <button
          type="button"
          @click="activeOrderType = 'lab'"
          :class="activeOrderType === 'lab' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
          class="flex-1 py-2 rounded-lg transition cursor-pointer text-center"
        >
          🧪 Laboratory Test
        </button>
        <button
          type="button"
          @click="activeOrderType = 'radiology'"
          :class="activeOrderType === 'radiology' ? 'bg-white text-purple-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
          class="flex-1 py-2 rounded-lg transition cursor-pointer text-center"
        >
          🩻 Radiology & Imaging
        </button>
      </div>

      <!-- FORM 1: Laboratory Order -->
      <form v-if="activeOrderType === 'lab'" @submit.prevent="submitLabOrder" class="space-y-4 text-xs">
        <div>
          <label class="block font-bold text-slate-700 mb-1">Laboratory Test <span class="text-red-500">*</span></label>
          <select v-model="labForm.test_type" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium">
            <option value="Complete Blood Count (CBC)">Complete Blood Count (CBC)</option>
            <option value="Basic Metabolic Panel (BMP)">Basic Metabolic Panel (BMP)</option>
            <option value="Comprehensive Metabolic Panel (CMP)">Comprehensive Metabolic Panel (CMP)</option>
            <option value="Lipid Profile">Lipid Profile</option>
            <option value="Hemoglobin A1c (HbA1c)">Hemoglobin A1c (HbA1c)</option>
            <option value="Liver Function Tests (LFT)">Liver Function Tests (LFT)</option>
            <option value="Renal Function Panel">Renal Function Panel</option>
            <option value="Urinalysis Routine">Urinalysis Routine</option>
            <option value="Thyroid Stimulating Hormone (TSH)">Thyroid Stimulating Hormone (TSH)</option>
            <option value="Serum Electrolytes (Na, K, Cl)">Serum Electrolytes (Na, K, Cl)</option>
            <option value="Coagulation Profile (PT/INR, PTT)">Coagulation Profile (PT/INR, PTT)</option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Priority</label>
            <select v-model="labForm.priority" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium">
              <option value="routine">Routine</option>
              <option value="urgent">Urgent</option>
              <option value="stat">STAT (Immediate)</option>
            </select>
          </div>
          <div>
            <label class="block font-bold text-slate-700 mb-1">LOINC / Lab Code</label>
            <input v-model="labForm.test_code" type="text" placeholder="e.g. 58410-2" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl" />
          </div>
        </div>

        <div>
          <label class="block font-bold text-slate-700 mb-1">Clinical Indication / Diagnostic Reason</label>
          <textarea
            v-model="labForm.clinical_indication"
            rows="2"
            placeholder="e.g. Fatigue, elevated blood glucose, baseline assessment..."
            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl"
          ></textarea>
        </div>

        <div>
          <label class="block font-bold text-slate-700 mb-1">Phlebotomy / Special Instructions</label>
          <input
            v-model="labForm.special_instructions"
            type="text"
            placeholder="e.g. 12-hour fasting required; draw from left arm"
            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl"
          />
        </div>

        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
          <button type="button" @click="$emit('close')" class="px-4 py-2 rounded-xl text-slate-600 font-semibold cursor-pointer">
            Cancel
          </button>
          <button
            type="submit"
            :disabled="isSubmitting"
            class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-5 py-2.5 rounded-xl shadow-sm transition cursor-pointer"
          >
            {{ isSubmitting ? 'Ordering...' : 'Confirm Lab Order' }}
          </button>
        </div>
      </form>

      <!-- FORM 2: Radiology Order -->
      <form v-else @submit.prevent="submitRadiologyOrder" class="space-y-4 text-xs">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Modality <span class="text-red-500">*</span></label>
            <select v-model="radForm.modality" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium">
              <option value="X-Ray">X-Ray (Plain Radiography)</option>
              <option value="CT Scan">CT Scan (Computed Tomography)</option>
              <option value="MRI">MRI (Magnetic Resonance)</option>
              <option value="Ultrasound">Ultrasound (Sonography)</option>
              <option value="Mammography">Mammography</option>
            </select>
          </div>
          <div>
            <label class="block font-bold text-slate-700 mb-1">Body Region <span class="text-red-500">*</span></label>
            <select v-model="radForm.body_part" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium">
              <option value="Chest">Chest</option>
              <option value="Brain / Head">Brain / Head</option>
              <option value="Lumbar Spine">Lumbar Spine</option>
              <option value="Abdomen & Pelvis">Abdomen & Pelvis</option>
              <option value="Knee (Right/Left)">Knee (Right/Left)</option>
              <option value="Cervical Spine">Cervical Spine</option>
              <option value="Extremity">Extremity</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block font-bold text-slate-700 mb-1">Procedure Name <span class="text-red-500">*</span></label>
          <input
            v-model="radForm.procedure_name"
            type="text"
            required
            placeholder="e.g. Chest X-Ray 2 Views PA & Lateral"
            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl"
          />
        </div>

        <div>
          <label class="block font-bold text-slate-700 mb-1">Priority</label>
          <select v-model="radForm.priority" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium">
            <option value="routine">Routine</option>
            <option value="urgent">Urgent</option>
            <option value="stat">STAT</option>
          </select>
        </div>

        <!-- Clinical Safety Checkboxes -->
        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
          <div class="font-bold text-slate-700 text-[11px] uppercase tracking-wider">Patient Clinical Safety</div>
          <div class="flex items-center gap-4">
            <label class="flex items-center gap-2 cursor-pointer font-medium text-slate-800">
              <input v-model="radForm.is_pregnant_or_possible" type="checkbox" class="rounded text-purple-600" />
              <span>Pregnancy Screening Positive / Possible</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer font-medium text-slate-800">
              <input v-model="radForm.transport_required" type="checkbox" class="rounded text-purple-600" />
              <span>Wheelchair / Stretcher Transport Required</span>
            </label>
          </div>
        </div>

        <div>
          <label class="block font-bold text-slate-700 mb-1">Clinical Indication</label>
          <textarea
            v-model="radForm.clinical_indication"
            rows="2"
            placeholder="e.g. Rule out pneumonia, evaluate chronic cough, trauma..."
            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl"
          ></textarea>
        </div>

        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
          <button type="button" @click="$emit('close')" class="px-4 py-2 rounded-xl text-slate-600 font-semibold cursor-pointer">
            Cancel
          </button>
          <button
            type="submit"
            :disabled="isSubmitting"
            class="bg-purple-600 hover:bg-purple-700 text-white font-bold px-5 py-2.5 rounded-xl shadow-sm transition cursor-pointer"
          >
            {{ isSubmitting ? 'Ordering...' : 'Confirm Radiology Order' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  isOpen: { type: Boolean, default: false },
  initialType: { type: String, default: 'lab' },
  patient: { type: Object, required: true },
  branchId: { type: String, required: true },
});

const emit = defineEmits(['close', 'orderCreated']);

const activeOrderType = ref(props.initialType || 'lab');
const isSubmitting = ref(false);

watch(() => props.initialType, (newVal) => {
  if (newVal) activeOrderType.value = newVal;
});

const labForm = ref({
  test_type: 'Complete Blood Count (CBC)',
  test_code: '',
  priority: 'routine',
  clinical_indication: '',
  special_instructions: '',
});

const radForm = ref({
  modality: 'X-Ray',
  body_part: 'Chest',
  procedure_name: 'Chest X-Ray PA & Lateral',
  priority: 'routine',
  clinical_indication: '',
  transport_required: false,
  is_pregnant_or_possible: false,
});

async function submitLabOrder() {
  isSubmitting.value = true;
  try {
    const payload = {
      patient_id: props.patient.id,
      ...labForm.value,
    };

    const res = await axios.post('/api/v1/clinical/lab-orders', payload, {
      headers: { 'X-Branch-ID': props.branchId, 'Accept': 'application/json' },
    });

    emit('orderCreated', { type: 'lab', data: res.data.data });
    emit('close');
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to submit lab order.');
  } finally {
    isSubmitting.value = false;
  }
}

async function submitRadiologyOrder() {
  isSubmitting.value = true;
  try {
    const payload = {
      patient_id: props.patient.id,
      ...radForm.value,
    };

    const res = await axios.post('/api/v1/clinical/radiology-orders', payload, {
      headers: { 'X-Branch-ID': props.branchId, 'Accept': 'application/json' },
    });

    emit('orderCreated', { type: 'radiology', data: res.data.data });
    emit('close');
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to submit radiology order.');
  } finally {
    isSubmitting.value = false;
  }
}
</script>
