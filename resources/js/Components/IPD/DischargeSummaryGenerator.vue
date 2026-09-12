<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <div class="flex items-center gap-3">
          <h2 class="text-xl font-black text-slate-900 tracking-tight">Discharge Summary Generator</h2>
          <span
            v-if="summary"
            class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider"
            :class="summary.is_finalized ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
          >
            {{ summary.is_finalized ? 'Finalized & Sealed' : 'Draft In Review' }}
          </span>
        </div>
        <p class="text-xs text-slate-500 mt-1">Automatically aggregates inpatient stay records: dates, diagnoses, procedures, and administered medications.</p>
      </div>

      <div class="flex items-center gap-3">
        <button
          v-if="summary && !summary.is_finalized"
          @click="saveDraft"
          :disabled="saving"
          class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition cursor-pointer disabled:opacity-50"
        >
          {{ saving ? 'Saving...' : 'Save Draft' }}
        </button>

        <button
          v-if="summary && !summary.is_finalized"
          @click="finalizeSummary"
          :disabled="finalizing"
          class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-sm disabled:opacity-50"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          </svg>
          {{ finalizing ? 'Finalizing...' : 'Finalize & Seal Summary' }}
        </button>
      </div>
    </div>

    <!-- Admission Selector -->
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs">
      <div class="flex items-center gap-3">
        <label class="font-bold text-slate-700">Admission Reference / UUID:</label>
        <input
          type="text"
          v-model="admissionId"
          placeholder="Paste Admission UUID"
          class="rounded-xl border-slate-300 p-2 border font-mono text-xs w-72"
        />
        <button
          @click="fetchSummary"
          class="px-3 py-1.5 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 transition cursor-pointer"
        >
          Generate Summary
        </button>
      </div>

      <div v-if="summary?.is_finalized" class="text-emerald-800 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-200 font-medium flex items-center gap-2">
        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <span>This discharge summary is signed and legally sealed.</span>
      </div>
    </div>

    <!-- Clinical Discharge Document Layout -->
    <div v-if="summary" class="bg-white rounded-3xl p-8 shadow-sm border border-slate-200 space-y-6">
      <!-- Document Header -->
      <div class="border-b-2 border-slate-900 pb-4 flex items-center justify-between">
        <div>
          <div class="text-xl font-black text-slate-900 tracking-tight">HOSPITAL DISCHARGE SUMMARY</div>
          <div class="text-xs text-slate-500 font-medium">METRO INPATIENT CARE FACILITY</div>
        </div>
        <div class="text-right text-xs">
          <div class="font-mono font-bold text-slate-900">REF: {{ summary.admission_id ? summary.admission_id.slice(0, 8) : '' }}</div>
          <div class="text-slate-400">Date: {{ new Date().toLocaleDateString() }}</div>
        </div>
      </div>

      <!-- Section 1: Auto-Populated Inpatient Dates & Patient Demographics -->
      <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
        <div>
          <span class="text-[10px] text-slate-400 uppercase font-bold block">Admission Date</span>
          <span class="font-mono font-bold text-slate-800">{{ summary.admission_date ? summary.admission_date.slice(0, 16).replace('T', ' ') : '-' }}</span>
        </div>
        <div>
          <span class="text-[10px] text-slate-400 uppercase font-bold block">Discharge Date</span>
          <span class="font-mono font-bold text-slate-800">{{ summary.discharge_date ? summary.discharge_date.slice(0, 16).replace('T', ' ') : '-' }}</span>
        </div>
        <div>
          <span class="text-[10px] text-slate-400 uppercase font-bold block">Discharge Type</span>
          <span class="font-bold text-slate-800 capitalize">{{ summary.discharge_type }}</span>
        </div>
        <div>
          <span class="text-[10px] text-slate-400 uppercase font-bold block">Condition at Discharge</span>
          <span class="font-bold text-emerald-700 capitalize">{{ summary.discharge_condition }}</span>
        </div>
      </div>

      <!-- Section 2: Diagnoses (Auto-Populated) -->
      <div class="space-y-3">
        <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider font-mono border-b pb-1">1. Diagnoses & Clinical Findings</h3>
        <div class="space-y-2">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Primary Final Diagnosis (Auto-populated from stay)</label>
            <input
              type="text"
              v-model="summary.primary_diagnosis"
              :disabled="summary.is_finalized"
              class="w-full text-xs rounded-xl border-slate-300 p-2.5 border font-semibold text-slate-800 disabled:bg-slate-50"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Secondary Co-Morbidities & Diagnoses</label>
            <div class="flex flex-wrap gap-2 py-1">
              <span
                v-for="(diag, idx) in summary.secondary_diagnoses"
                :key="idx"
                class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 font-medium text-xs"
              >
                {{ diag }}
              </span>
              <span v-if="!summary.secondary_diagnoses?.length" class="text-xs text-slate-400">None recorded.</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Section 3: Procedures Performed (Auto-Populated) -->
      <div class="space-y-3">
        <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider font-mono border-b pb-1">2. Procedures & Interventions Performed</h3>
        <div class="flex flex-wrap gap-2">
          <span
            v-for="(proc, idx) in summary.procedures_performed"
            :key="idx"
            class="px-3 py-1.5 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 font-bold text-xs flex items-center gap-1.5"
          >
            ✓ {{ proc }}
          </span>
          <span v-if="!summary.procedures_performed?.length" class="text-xs text-slate-400">No invasive procedures performed.</span>
        </div>
      </div>

      <!-- Section 4: Administered Medications at Discharge (Auto-Populated from Nursing Rounds) -->
      <div class="space-y-3">
        <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider font-mono border-b pb-1">3. Medications at Discharge (Auto-Pulled from Inpatient Stay)</h3>
        <div class="border border-slate-200 rounded-2xl overflow-hidden">
          <table class="w-full text-left text-xs text-slate-600">
            <thead class="bg-slate-50 border-b border-slate-200 text-[10px] uppercase font-bold text-slate-400">
              <tr>
                <th class="py-2.5 px-4">Medication Name</th>
                <th class="py-2.5 px-4">Dosage</th>
                <th class="py-2.5 px-4">Route</th>
                <th class="py-2.5 px-4">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="!summary.medications_at_discharge?.length">
                <td colspan="4" class="py-4 text-center text-slate-400">No inpatient medications logged.</td>
              </tr>
              <tr v-for="(med, idx) in summary.medications_at_discharge" :key="idx">
                <td class="py-2 px-4 font-bold text-slate-900">{{ med.medication_name }}</td>
                <td class="py-2 px-4">{{ med.dosage }}</td>
                <td class="py-2 px-4 uppercase text-[11px]">{{ med.route }}</td>
                <td class="py-2 px-4 text-emerald-700 font-semibold">Administered during stay</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Section 5: Hospital Course & Follow-up Instructions -->
      <div class="space-y-4">
        <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider font-mono border-b pb-1">4. Hospital Course & Follow-Up Plan</h3>
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Clinical Course Summary</label>
          <textarea
            v-model="summary.hospital_course_summary"
            :disabled="summary.is_finalized"
            rows="3"
            class="w-full text-xs rounded-xl border-slate-300 p-2.5 border disabled:bg-slate-50"
          ></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Follow-Up Instructions</label>
            <textarea
              v-model="summary.follow_up_instructions"
              :disabled="summary.is_finalized"
              rows="2"
              class="w-full text-xs rounded-xl border-slate-300 p-2.5 border disabled:bg-slate-50"
            ></textarea>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Follow-Up Clinic Date</label>
            <input
              type="date"
              v-model="summary.follow_up_date"
              :disabled="summary.is_finalized"
              class="w-full text-xs rounded-xl border-slate-300 p-2.5 border disabled:bg-slate-50"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  branchId: { type: String, required: true },
});

const admissionId = ref('');
const summary = ref(null);
const saving = ref(false);
const finalizing = ref(false);

async function fetchSummary() {
  if (!admissionId.value) return;
  try {
    const res = await fetch(`/api/v1/ipd/admissions/${admissionId.value}/discharge-summary`, {
      headers: { 'X-Branch-ID': props.branchId, 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.data) {
      summary.value = json.data;
    }
  } catch (e) {
    console.error('Fetch summary error', e);
  }
}

async function saveDraft() {
  if (!admissionId.value) return;
  saving.value = true;
  try {
    const res = await fetch(`/api/v1/ipd/admissions/${admissionId.value}/discharge-summary`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-Branch-ID': props.branchId,
        'Accept': 'application/json'
      },
      body: JSON.stringify(summary.value)
    });
    const json = await res.json();
    if (res.ok) {
      summary.value = json.data;
      alert('Discharge summary draft saved.');
    }
  } catch (e) {
    console.error('Save summary error', e);
  } finally {
    saving.value = false;
  }
}

async function finalizeSummary() {
  if (!admissionId.value) return;
  if (!confirm('Once finalized, this summary is legally sealed. Continue?')) return;
  finalizing.value = true;
  try {
    const res = await fetch(`/api/v1/ipd/admissions/${admissionId.value}/discharge-summary/finalize`, {
      method: 'POST',
      headers: {
        'X-Branch-ID': props.branchId,
        'Accept': 'application/json'
      }
    });
    const json = await res.json();
    if (res.ok) {
      summary.value = json.data;
      alert('Discharge summary finalized and locked!');
    }
  } catch (e) {
    console.error('Finalize error', e);
  } finally {
    finalizing.value = false;
  }
}
</script>
