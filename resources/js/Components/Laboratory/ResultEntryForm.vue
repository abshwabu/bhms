<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full overflow-hidden border border-slate-200 animate-in fade-in zoom-in duration-200">
      
      <!-- Modal Header -->
      <div class="p-5 bg-slate-900 text-white flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div :class="isAmending ? 'bg-amber-600' : 'bg-blue-600'" class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-white shadow-md">
            <svg v-if="!isAmending" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
          </div>
          <div>
            <h3 class="font-extrabold text-base">
              {{ isAmending ? 'Create Versioned Amendment' : 'Diagnostic Result Entry & Flagging' }}
            </h3>
            <p class="text-xs text-slate-400">
              {{ isAmending ? 'Original report is legally locked. This will generate an append-only revised version.' : 'Enter parameter measurements with automated reference range evaluation.' }}
            </p>
          </div>
        </div>

        <button @click="$emit('close')" class="text-slate-400 hover:text-white transition cursor-pointer p-1">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Context Bar: Patient & Sample Metadata -->
      <div class="px-6 py-3 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-4 text-xs">
        <div class="flex items-center gap-4">
          <div>
            <span class="text-slate-400 font-semibold uppercase text-[10px]">Patient:</span>
            <div class="font-bold text-slate-900">{{ context?.patient?.name || 'N/A' }}</div>
          </div>
          <div class="border-l border-slate-200 pl-4">
            <span class="text-slate-400 font-semibold uppercase text-[10px]">MRN:</span>
            <div class="font-mono font-bold text-slate-700">{{ context?.patient?.mrn || 'N/A' }}</div>
          </div>
          <div class="border-l border-slate-200 pl-4">
            <span class="text-slate-400 font-semibold uppercase text-[10px]">Test Type:</span>
            <div class="font-semibold text-blue-700">{{ context?.lab_order?.test_type || context?.lab_test?.name || 'Diagnostic Test' }}</div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <div class="px-2.5 py-1 rounded-lg bg-white border border-slate-300 font-mono text-[11px] font-bold text-slate-800 flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
            {{ context?.sample?.barcode || context?.barcode || 'SMP-BARCODE' }}
          </div>
          <span v-if="context?.status === 'signed'" class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px]">
            SIGNED (v{{ context.version || 1 }})
          </span>
        </div>
      </div>

      <!-- Form Content -->
      <form @submit.prevent="handleSubmit" class="p-6 space-y-5 max-h-[68vh] overflow-y-auto">
        
        <!-- Amendment Reason Box (Only when amending signed report) -->
        <div v-if="isAmending" class="p-4 rounded-xl bg-amber-50 border border-amber-300 space-y-2">
          <label class="block text-xs font-bold text-amber-950 uppercase tracking-wide">
            Mandatory Amendment Justification *
          </label>
          <textarea
            v-model="amendmentReason"
            required
            rows="2"
            placeholder="Document rationale for amendment (e.g. Instrument recalibrated, sample re-analyzed due to suspected interfering substance)..."
            class="w-full text-xs p-2.5 bg-white border border-amber-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none"
          ></textarea>
          <p class="text-[11px] text-amber-800">
            This justification will be permanently affixed to Version {{ (context?.version || 1) + 1 }} of the laboratory record.
          </p>
        </div>

        <!-- Real-Time Panic / Abnormal Warning Banner -->
        <div v-if="hasCriticalValues" class="p-3.5 rounded-xl bg-red-50 border-2 border-red-500 flex items-center gap-3 animate-pulse">
          <div class="w-8 h-8 rounded-full bg-red-600 text-white flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <div>
            <div class="font-extrabold text-xs text-red-950 uppercase tracking-wide">
              CRITICAL PANIC VALUE DETECTED
            </div>
            <div class="text-[11px] text-red-800 mt-0.5">
              One or more measured parameters breach clinical panic thresholds. Immediate high-priority notification will be automatically dispatched to ordering physician <strong>{{ context?.lab_order?.ordering_doctor || 'Clinician' }}</strong>.
            </div>
          </div>
        </div>

        <div v-else-if="hasAbnormalValues" class="p-3 rounded-xl bg-amber-50 border border-amber-300 flex items-center gap-2.5 text-xs text-amber-900">
          <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>Abnormal values detected outside reference intervals. Alert will be logged for ordering clinician review.</span>
        </div>

        <!-- Parameter Entry Table -->
        <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
          <div class="bg-slate-100 px-4 py-2.5 flex justify-between items-center text-xs font-bold text-slate-700">
            <span>Diagnostic Parameter Measurements</span>
            <button
              type="button"
              @click="addCustomParameter"
              class="text-blue-600 hover:text-blue-800 text-xs font-semibold flex items-center gap-1 cursor-pointer"
            >
              + Add Custom Parameter
            </button>
          </div>

          <table class="w-full text-xs text-left">
            <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 font-semibold">
              <tr>
                <th class="py-2.5 px-3">Parameter Name</th>
                <th class="py-2.5 px-3 w-36">Measured Value</th>
                <th class="py-2.5 px-3 w-24">Unit</th>
                <th class="py-2.5 px-3 w-32">Reference Range</th>
                <th class="py-2.5 px-3 w-36 text-center">Live Auto-Flag</th>
                <th class="py-2.5 px-2 w-10"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="(item, idx) in parameters" :key="idx" class="hover:bg-slate-50/50">
                <td class="py-2 px-3">
                  <input
                    v-model="item.parameter_name"
                    required
                    type="text"
                    placeholder="e.g. Hemoglobin"
                    class="w-full text-xs p-1.5 border border-slate-300 rounded font-semibold text-slate-800 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                  />
                </td>

                <td class="py-2 px-3">
                  <input
                    v-model="item.measured_value"
                    @input="evaluateRowFlag(item)"
                    required
                    type="text"
                    placeholder="0.00"
                    class="w-full text-xs p-1.5 border border-slate-300 rounded font-mono font-bold text-slate-900 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                  />
                </td>

                <td class="py-2 px-3">
                  <input
                    v-model="item.unit"
                    type="text"
                    placeholder="mg/dL"
                    class="w-full text-xs p-1.5 border border-slate-300 rounded font-mono text-slate-600 focus:outline-none"
                  />
                </td>

                <td class="py-2 px-3 font-mono text-[11px] text-slate-500">
                  <span v-if="item.reference_low !== undefined && item.reference_high !== undefined">
                    {{ item.reference_low }} - {{ item.reference_high }}
                  </span>
                  <span v-else class="italic text-slate-400">Standard</span>
                </td>

                <!-- Live Auto-Flag Badge -->
                <td class="py-2 px-3 text-center">
                  <span
                    v-if="item.flag === 'normal'"
                    class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold"
                  >
                    NORMAL
                  </span>
                  <span
                    v-else-if="item.flag === 'low'"
                    class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-900 text-[10px] font-black"
                  >
                    &darr; LOW
                  </span>
                  <span
                    v-else-if="item.flag === 'high'"
                    class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-900 text-[10px] font-black"
                  >
                    &uarr; HIGH
                  </span>
                  <span
                    v-else-if="item.flag === 'critical_low' || item.flag === 'critical_high'"
                    class="px-2 py-0.5 rounded-full bg-red-600 text-white text-[10px] font-black uppercase tracking-wider animate-pulse inline-flex items-center gap-1"
                  >
                    ! CRITICAL
                  </span>
                  <span
                    v-else
                    class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-[10px]"
                  >
                    Pending
                  </span>
                </td>

                <td class="py-2 px-2 text-center">
                  <button
                    v-if="parameters.length > 1"
                    type="button"
                    @click="removeParameter(idx)"
                    class="text-slate-400 hover:text-red-600 p-1 transition cursor-pointer"
                  >
                    &times;
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Clinical Remarks -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Clinical Remarks / Analytical Notes
          </label>
          <textarea
            v-model="clinicalRemarks"
            rows="2"
            placeholder="Analytical notes, instrument flags, delta checks, or microscopic findings..."
            class="w-full text-xs p-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
          ></textarea>
        </div>

        <!-- Digital Signing Confirmation Checkbox (If user wants to certify directly) -->
        <div v-if="!isAmending" class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-start gap-3">
          <input
            id="sign-directly"
            v-model="signDirectly"
            type="checkbox"
            class="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer"
          />
          <label for="sign-directly" class="text-xs text-slate-700 cursor-pointer">
            <span class="font-bold text-slate-900 block">Digitally Sign & Seal Diagnostic Report upon save</span>
            <span class="text-[11px] text-slate-500 block">
              Applying pathologist digital signature will legally lock this record against direct changes (SHA-256 sealed). Subsequent modifications will require versioned amendments.
            </span>
          </label>
        </div>

        <!-- Error Alert -->
        <div v-if="errorMessage" class="p-3 rounded-lg bg-red-50 border border-red-200 text-xs text-red-700">
          {{ errorMessage }}
        </div>

        <!-- Footer Action Buttons -->
        <div class="pt-2 border-t border-slate-200 flex justify-end gap-3">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition cursor-pointer"
          >
            Cancel
          </button>

          <button
            type="submit"
            :disabled="isSubmitting"
            :class="isAmending ? 'bg-amber-600 hover:bg-amber-700' : (signDirectly ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-blue-600 hover:bg-blue-700')"
            class="px-5 py-2 text-white text-xs font-bold rounded-xl shadow-md transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
          >
            <svg v-if="isSubmitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span>{{ submitButtonText }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  isOpen: { type: Boolean, default: false },
  context: { type: Object, default: null }, // Sample or LabResult object
  catalogTests: { type: Array, default: () => [] },
  isAmending: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'saved']);

const parameters = ref([]);
const clinicalRemarks = ref('');
const amendmentReason = ref('');
const signDirectly = ref(false);
const isSubmitting = ref(false);
const errorMessage = ref('');

// Watch context opening to pre-fill parameters
watch(
  () => props.isOpen,
  (open) => {
    if (!open) return;
    errorMessage.value = '';
    signDirectly.value = false;
    amendmentReason.value = '';

    if (props.context) {
      clinicalRemarks.value = props.context.clinical_remarks || '';

      // If context already has items (e.g. existing result or amendment)
      if (props.context.items && props.context.items.length > 0) {
        parameters.value = props.context.items.map((i) => ({
          parameter_name: i.parameter_name,
          measured_value: i.measured_value,
          unit: i.unit,
          reference_low: i.reference_low,
          reference_high: i.reference_high,
          flag: i.flag || 'normal',
        }));
      } else {
        // Populate default template parameters based on test code
        parameters.value = getDefaultParametersForTest(props.context);
      }
    }
  },
  { immediate: true }
);

function getDefaultParametersForTest(ctx) {
  const code = (ctx?.lab_test?.code || ctx?.lab_order?.test_type || '').toUpperCase();

  if (code.includes('CBC') || code.includes('BLOOD COUNT')) {
    return [
      { parameter_name: 'WBC', measured_value: '6.8', unit: '10^3/uL', reference_low: 4.5, reference_high: 11.0, flag: 'normal' },
      { parameter_name: 'RBC', measured_value: '4.9', unit: '10^6/uL', reference_low: 4.5, reference_high: 5.9, flag: 'normal' },
      { parameter_name: 'Hemoglobin', measured_value: '14.5', unit: 'g/dL', reference_low: 13.5, reference_high: 17.5, flag: 'normal' },
      { parameter_name: 'Hematocrit', measured_value: '43.2', unit: '%', reference_low: 41.0, reference_high: 50.0, flag: 'normal' },
      { parameter_name: 'Platelets', measured_value: '260', unit: '10^3/uL', reference_low: 150.0, reference_high: 450.0, flag: 'normal' },
    ];
  }

  if (code.includes('BMP') || code.includes('METABOLIC')) {
    return [
      { parameter_name: 'Glucose', measured_value: '92', unit: 'mg/dL', reference_low: 70.0, reference_high: 99.0, flag: 'normal' },
      { parameter_name: 'BUN', measured_value: '14', unit: 'mg/dL', reference_low: 7.0, reference_high: 20.0, flag: 'normal' },
      { parameter_name: 'Creatinine', measured_value: '0.9', unit: 'mg/dL', reference_low: 0.7, reference_high: 1.3, flag: 'normal' },
      { parameter_name: 'Sodium', measured_value: '140', unit: 'mEq/L', reference_low: 135.0, reference_high: 145.0, flag: 'normal' },
      { parameter_name: 'Potassium', measured_value: '4.2', unit: 'mEq/L', reference_low: 3.5, reference_high: 5.0, flag: 'normal' },
      { parameter_name: 'Chloride', measured_value: '101', unit: 'mEq/L', reference_low: 96.0, reference_high: 106.0, flag: 'normal' },
    ];
  }

  if (code.includes('HBA1C')) {
    return [
      { parameter_name: 'HbA1c', measured_value: '5.4', unit: '%', reference_low: 4.0, reference_high: 5.6, flag: 'normal' },
    ];
  }

  return [
    { parameter_name: 'Measurement', measured_value: '', unit: '', reference_low: 0, reference_high: 100, flag: 'normal' },
  ];
}

// Client-side instant evaluation as user types
function evaluateRowFlag(item) {
  if (!item.measured_value || isNaN(item.measured_value)) {
    item.flag = 'normal';
    return;
  }

  const val = parseFloat(item.measured_value);
  const low = item.reference_low !== undefined ? parseFloat(item.reference_low) : null;
  const high = item.reference_high !== undefined ? parseFloat(item.reference_high) : null;

  if (low !== null && val < low) {
    if (low > 0 && val < low * 0.6) {
      item.flag = 'critical_low';
    } else {
      item.flag = 'low';
    }
  } else if (high !== null && val > high) {
    if (val > high * 1.5) {
      item.flag = 'critical_high';
    } else {
      item.flag = 'high';
    }
  } else {
    item.flag = 'normal';
  }
}

const hasAbnormalValues = computed(() => {
  return parameters.value.some((p) => ['low', 'high', 'critical_low', 'critical_high', 'abnormal'].includes(p.flag));
});

const hasCriticalValues = computed(() => {
  return parameters.value.some((p) => ['critical_low', 'critical_high'].includes(p.flag));
});

const submitButtonText = computed(() => {
  if (props.isAmending) return 'Submit Official Amendment (Append-Only)';
  if (signDirectly.value) return 'Save & Digitally Sign (Pathologist Seal)';
  return 'Save Laboratory Result';
});

function addCustomParameter() {
  parameters.value.push({
    parameter_name: '',
    measured_value: '',
    unit: '',
    reference_low: null,
    reference_high: null,
    flag: 'normal',
  });
}

function removeParameter(idx) {
  parameters.value.splice(idx, 1);
}

async function handleSubmit() {
  isSubmitting.value = true;
  errorMessage.value = '';

  try {
    const payload = {
      parameters: parameters.value.map((p) => ({
        parameter_name: p.parameter_name,
        measured_value: p.measured_value,
        unit: p.unit,
      })),
      clinical_remarks: clinicalRemarks.value,
    };

    if (props.isAmending) {
      payload.amendment_reason = amendmentReason.value;
      const resultId = props.context?.id;
      const res = await fetch(`/api/v1/laboratory/results/${resultId}/amend`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Branch-ID': props.context?.branch_id || 'b9ff561a-5396-4309-9b08-3e7b358310e9',
        },
        body: JSON.stringify(payload),
      });

      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Failed to amend laboratory report.');

      emit('saved', data.data);
      emit('close');
    } else {
      // Regular store or update
      payload.lab_order_id = props.context?.lab_order_id || props.context?.lab_order?.id || props.context?.id;
      payload.lab_sample_id = props.context?.lab_sample_id || props.context?.id;
      payload.lab_test_id = props.context?.lab_test_id || props.context?.lab_test?.id;
      payload.status = signDirectly.value ? 'preliminary' : 'preliminary';

      const res = await fetch('/api/v1/laboratory/results', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Branch-ID': props.context?.branch_id || 'b9ff561a-5396-4309-9b08-3e7b358310e9',
        },
        body: JSON.stringify(payload),
      });

      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Failed to save laboratory results.');

      let resultObj = data.data;

      // If user checked digitally sign
      if (signDirectly.value && resultObj?.id) {
        const signRes = await fetch(`/api/v1/laboratory/results/${resultObj.id}/sign`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Branch-ID': props.context?.branch_id || 'b9ff561a-5396-4309-9b08-3e7b358310e9',
          },
          body: JSON.stringify({
            clinical_remarks: clinicalRemarks.value,
          }),
        });

        const signData = await signRes.json();
        if (signRes.ok) {
          resultObj = signData.data;
        }
      }

      emit('saved', resultObj);
      emit('close');
    }
  } catch (err) {
    errorMessage.value = err.message || 'An error occurred during submission.';
  } finally {
    isSubmitting.value = false;
  }
}
</script>
