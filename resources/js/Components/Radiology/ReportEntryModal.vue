<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full overflow-hidden border border-slate-200 animate-in fade-in zoom-in duration-200">
      
      <!-- Modal Header -->
      <div class="p-5 bg-slate-900 text-white flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div :class="isAmending ? 'bg-amber-600' : 'bg-blue-600'" class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-white shadow-md">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <div>
            <h3 class="font-extrabold text-base">
              {{ isAmending ? 'Create Versioned Radiology Amendment' : 'Diagnostic Radiology Report Entry' }}
            </h3>
            <p class="text-xs text-slate-400">
              {{ isAmending ? 'Finalized reports are locked. Amendments generate an append-only revised edition.' : 'Comprehensive radiologist interpretation with draft and finalized signing states.' }}
            </p>
          </div>
        </div>

        <button @click="$emit('close')" class="text-slate-400 hover:text-white transition cursor-pointer p-1">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Unambiguous Patient & Order Linkage Header -->
      <div class="px-6 py-3.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-4 text-xs">
        <div class="flex items-center gap-4">
          <div>
            <span class="text-slate-400 font-semibold uppercase text-[10px]">Patient:</span>
            <div class="font-bold text-slate-900">{{ order?.patient?.name }}</div>
          </div>
          <div class="border-l border-slate-200 pl-4">
            <span class="text-slate-400 font-semibold uppercase text-[10px]">MRN:</span>
            <div class="font-mono font-bold text-slate-700">{{ order?.patient?.mrn }}</div>
          </div>
          <div class="border-l border-slate-200 pl-4">
            <span class="text-slate-400 font-semibold uppercase text-[10px]">Modality & Procedure:</span>
            <div class="font-semibold text-blue-700">{{ order?.modality }} - {{ order?.procedure_name }}</div>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <span class="px-2.5 py-1 rounded-lg bg-white border border-slate-300 font-mono text-[11px] font-bold text-slate-800">
            {{ order?.accession_number }}
          </span>
          <span v-if="existingReport?.status === 'finalized'" class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[10px]">
            FINALIZED (v{{ existingReport.version || 1 }})
          </span>
          <span v-else class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 font-bold text-[10px]">
            DRAFT
          </span>
        </div>
      </div>

      <!-- Report Form Body -->
      <form @submit.prevent="handleSubmit" class="p-6 space-y-4 max-h-[70vh] overflow-y-auto text-xs">
        
        <!-- Amendment Justification (If amending) -->
        <div v-if="isAmending" class="p-4 rounded-xl bg-amber-50 border border-amber-300 space-y-2">
          <label class="block font-bold text-amber-950 uppercase tracking-wide">
            Mandatory Amendment Justification *
          </label>
          <textarea
            v-model="amendmentReason"
            required
            rows="2"
            placeholder="Document reasons for amending finalized report (e.g. Additional clinical history provided, re-examination with 3D reconstructions)..."
            class="w-full text-xs p-2.5 bg-white border border-amber-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none"
          ></textarea>
        </div>

        <!-- Clinical Indication & Comparison -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Clinical Indication / History</label>
            <textarea
              v-model="form.clinical_indication"
              rows="2"
              placeholder="Reason for study..."
              class="w-full p-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
            ></textarea>
          </div>
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Prior Comparison Studies</label>
            <textarea
              v-model="form.comparison"
              rows="2"
              placeholder="None available or compared with prior CT dated..."
              class="w-full p-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
            ></textarea>
          </div>
        </div>

        <!-- Scanning Technique / Protocol -->
        <div>
          <label class="block font-semibold text-slate-700 mb-1">Scanning Technique & Protocol</label>
          <input
            v-model="form.technique"
            type="text"
            placeholder="e.g. Axial contiguous slices obtained from lung apices to bases without intravenous contrast."
            class="w-full p-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
          />
        </div>

        <!-- Detailed Findings -->
        <div>
          <label class="block font-semibold text-slate-700 mb-1">Detailed Findings</label>
          <textarea
            v-model="form.findings"
            rows="5"
            placeholder="Systematic breakdown: Lungs/Pleura, Mediastinum/Heart, Abdominal structures, Bones/Soft tissues..."
            class="w-full p-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none font-mono"
          ></textarea>
        </div>

        <!-- Diagnostic Impression (Mandatory for Finalization) -->
        <div>
          <label class="block font-bold text-slate-900 mb-1">
            Diagnostic Impression (Conclusion) *
          </label>
          <textarea
            v-model="form.impression"
            required
            rows="3"
            placeholder="Key diagnostic findings, conclusions, BI-RADS or Lung-RADS assessment..."
            class="w-full p-2.5 border-2 border-slate-300 focus:border-blue-600 rounded-lg focus:outline-none font-medium text-slate-900"
          ></textarea>
        </div>

        <!-- Recommendations -->
        <div>
          <label class="block font-semibold text-slate-700 mb-1">Recommendations & Follow-up</label>
          <input
            v-model="form.recommendations"
            type="text"
            placeholder="e.g. Recommend follow-up high-resolution chest CT in 3 months or clinical correlation."
            class="w-full p-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
          />
        </div>

        <!-- Critical Findings Panic Alert -->
        <div class="p-3.5 rounded-xl border border-red-200 bg-red-50/50 space-y-2">
          <div class="flex items-center gap-2">
            <input
              id="critical-alert-check"
              v-model="form.critical_alert"
              type="checkbox"
              class="h-4 w-4 rounded text-red-600 focus:ring-red-500 border-slate-300 cursor-pointer"
            />
            <label for="critical-alert-check" class="font-bold text-red-900 cursor-pointer">
              Critical Panic Finding Alert (Immediate Clinician Notification Required)
            </label>
          </div>
          <div v-if="form.critical_alert" class="pt-1 pl-6">
            <input
              v-model="form.critical_alert_communicated_to"
              type="text"
              placeholder="Communicated to attending doctor (e.g. Dr. Vance phoned at 10:45 AM)..."
              class="w-full p-1.5 border border-red-300 bg-white rounded text-xs focus:outline-none"
            />
          </div>
        </div>

        <!-- Error Alert -->
        <div v-if="errorMessage" class="p-3 bg-red-50 border border-red-200 text-xs text-red-700 rounded-lg">
          {{ errorMessage }}
        </div>

        <!-- Footer Actions: Draft vs Finalized -->
        <div class="pt-3 border-t border-slate-200 flex justify-end gap-3">
          <button
            type="button"
            :disabled="isSubmitting"
            @click="$emit('close')"
            class="px-4 py-2 font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition cursor-pointer"
          >
            Cancel
          </button>

          <!-- Save Draft -->
          <button
            v-if="!isAmending"
            type="button"
            :disabled="isSubmitting"
            @click="submitReport(false)"
            class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl transition cursor-pointer disabled:opacity-50"
          >
            Save as Draft
          </button>

          <!-- Finalize & Sign -->
          <button
            type="button"
            :disabled="isSubmitting"
            @click="submitReport(true)"
            :class="isAmending ? 'bg-amber-600 hover:bg-amber-700' : 'bg-blue-600 hover:bg-blue-700'"
            class="px-5 py-2 text-white font-bold rounded-xl shadow-md transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
          >
            <svg v-if="isSubmitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span>{{ isAmending ? 'Finalize Amendment (Version ' + ((existingReport?.version || 1) + 1) + ')' : 'Finalize & Sign Report' }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  isOpen: { type: Boolean, default: false },
  order: { type: Object, default: null },
  existingReport: { type: Object, default: null },
  isAmending: { type: Boolean, default: false },
  branchId: { type: String, default: 'b9ff561a-5396-4309-9b08-3e7b358310e9' },
});

const emit = defineEmits(['close', 'saved']);

const form = ref({
  clinical_indication: '',
  technique: '',
  comparison: '',
  findings: '',
  impression: '',
  recommendations: '',
  critical_alert: false,
  critical_alert_communicated_to: '',
});

const amendmentReason = ref('');
const isSubmitting = ref(false);
const errorMessage = ref('');

watch(
  () => props.isOpen,
  (open) => {
    if (!open) return;
    errorMessage.value = '';
    amendmentReason.value = '';

    if (props.existingReport) {
      form.value = {
        clinical_indication: props.existingReport.clinical_indication || props.order?.clinical_indication || '',
        technique: props.existingReport.technique || getDefaultTechnique(props.order?.modality),
        comparison: props.existingReport.comparison || 'No prior comparisons available.',
        findings: props.existingReport.findings || '',
        impression: props.existingReport.impression || '',
        recommendations: props.existingReport.recommendations || '',
        critical_alert: props.existingReport.critical_alert || false,
        critical_alert_communicated_to: props.existingReport.critical_alert_communicated_to || '',
      };
    } else {
      form.value = {
        clinical_indication: props.order?.clinical_indication || '',
        technique: getDefaultTechnique(props.order?.modality),
        comparison: 'No prior comparisons available.',
        findings: '',
        impression: '',
        recommendations: '',
        critical_alert: false,
        critical_alert_communicated_to: '',
      };
    }
  },
  { immediate: true }
);

function getDefaultTechnique(modality) {
  const m = (modality || '').toUpperCase();
  if (m.includes('CT')) return 'Axial helical multislice CT imaging obtained with anatomical reconstructions.';
  if (m.includes('MR')) return 'Multiplanar multisequence MRI examination of the targeted region.';
  if (m.includes('US') || m.includes('ULTRASOUND')) return 'Real-time gray scale and color Doppler ultrasound examination.';
  return 'Standard radiographic projections obtained in inspiratory and lateral views.';
}

async function submitReport(finalize) {
  if (!form.value.impression || !form.value.impression.trim()) {
    errorMessage.value = 'Diagnostic Impression is required.';
    return;
  }

  isSubmitting.value = true;
  errorMessage.value = '';

  try {
    if (props.isAmending && props.existingReport) {
      if (!amendmentReason.value || amendmentReason.value.length < 5) {
        throw new Error('Please provide a valid amendment justification.');
      }

      const res = await fetch(`/api/v1/radiology/reports/${props.existingReport.id}/amend`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Branch-ID': props.branchId,
        },
        body: JSON.stringify({
          ...form.value,
          amendment_reason: amendmentReason.value,
          finalize: finalize,
        }),
      });

      const json = await res.json();
      if (!res.ok) throw new Error(json.message || 'Failed to submit amendment.');

      emit('saved', json.data);
      emit('close');
    } else {
      const payload = {
        imaging_order_id: props.order.id,
        ...form.value,
        finalize: finalize,
      };

      const res = await fetch('/api/v1/radiology/reports', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Branch-ID': props.branchId,
        },
        body: JSON.stringify(payload),
      });

      const json = await res.json();
      if (!res.ok) throw new Error(json.message || 'Failed to save imaging report.');

      emit('saved', json.data);
      emit('close');
    }
  } catch (err) {
    errorMessage.value = err.message || 'An error occurred while saving the report.';
  } finally {
    isSubmitting.value = false;
  }
}
</script>
