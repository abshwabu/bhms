<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full overflow-hidden border border-slate-200 animate-in fade-in zoom-in duration-200">
      
      <!-- Top Action Bar (hidden on print) -->
      <div class="p-4 bg-slate-900 text-white flex items-center justify-between print:hidden">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center font-bold text-white text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <div>
            <h3 class="font-bold text-sm">Diagnostic Radiology Report</h3>
            <p class="text-[11px] text-slate-400">Report No: {{ reportData?.report_number }} (v{{ reportData?.version || 1 }})</p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="printReport"
            class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-sm flex items-center gap-1.5 transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Print / Save PDF
          </button>
          <button @click="$emit('close')" class="text-slate-400 hover:text-white transition cursor-pointer p-1">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Printable Report Document Body -->
      <div id="radiology-print-report" class="p-8 bg-white max-h-[82vh] overflow-y-auto print:max-h-none print:overflow-visible text-slate-800 font-sans text-xs">
        
        <!-- Header / Hospital Letterhead -->
        <div class="border-b-2 border-slate-900 pb-4 mb-6 flex justify-between items-start">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-blue-700 to-indigo-600 flex items-center justify-center font-black text-2xl text-white">
              +
            </div>
            <div>
              <h1 class="text-xl font-extrabold text-slate-900 tracking-tight leading-tight">
                {{ reportData?.facility?.name || 'METRO HEALTH SYSTEM' }}
              </h1>
              <p class="text-xs text-slate-600 font-medium">
                {{ reportData?.facility?.department || 'Department of Diagnostic & Interventional Radiology' }}
              </p>
              <p class="text-[11px] text-slate-400">American College of Radiology (ACR) Accredited Facility</p>
            </div>
          </div>

          <div class="text-right">
            <div class="text-xs font-mono font-bold text-slate-900">
              REPORT: {{ reportData?.report_number }}
            </div>
            <div class="text-[11px] text-slate-500">
              Version: <span class="font-bold">{{ reportData?.version || 1 }}</span>
              <span v-if="reportData?.is_amended" class="ml-1 px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 font-bold uppercase text-[10px]">
                Amended
              </span>
            </div>
            <div class="text-[11px] text-slate-500">Finalized: {{ reportData?.finalized_at || 'Draft' }}</div>
          </div>
        </div>

        <!-- Amendment Notice Banner (if amended) -->
        <div v-if="reportData?.is_amended || reportData?.amendment_reason" class="mb-5 p-3 rounded-xl bg-amber-50 border border-amber-300 text-amber-900 text-xs">
          <div class="font-bold flex items-center gap-1.5">
            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            OFFICIAL RADIOLOGY AMENDMENT (REV {{ reportData?.version }})
          </div>
          <div class="mt-0.5 text-[11px] text-amber-800">
            <strong>Reason for Revision:</strong> {{ reportData?.amendment_reason }}
          </div>
        </div>

        <!-- Critical Findings Red Banner -->
        <div v-if="reportData?.critical_alert" class="mb-5 p-3 rounded-xl bg-red-600 text-white text-xs flex items-center justify-between">
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-white animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span class="font-black uppercase tracking-wide">CRITICAL DIAGNOSTIC FINDING ALERT</span>
          </div>
          <div class="text-[11px] text-red-100">
            Communicated To: <strong>{{ reportData?.critical_alert_communicated_to }}</strong> at {{ reportData?.critical_alert_communicated_at }}
          </div>
        </div>

        <!-- Patient Demographics & Order Metadata -->
        <div class="grid grid-cols-2 gap-4 p-4 rounded-xl bg-slate-50 border border-slate-200 mb-6">
          <div class="space-y-1">
            <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Patient Demographics</div>
            <div class="text-sm font-bold text-slate-900">{{ reportData?.patient?.name }}</div>
            <div class="text-slate-600">
              MRN: <span class="font-mono font-bold">{{ reportData?.patient?.mrn }}</span>
              | Gender: <span class="capitalize">{{ reportData?.patient?.gender }}</span>
              | Age: {{ reportData?.patient?.age }} yrs
            </div>
            <div class="text-slate-600">DOB: {{ reportData?.patient?.dob }}</div>
          </div>

          <div class="space-y-1 border-l border-slate-200 pl-4">
            <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Examination Metadata</div>
            <div>Accession No: <span class="font-mono font-bold text-blue-800">{{ reportData?.order?.accession_number }}</span></div>
            <div>Modality: <strong class="uppercase text-slate-800">{{ reportData?.order?.modality }}</strong></div>
            <div>Procedure: <span class="font-semibold">{{ reportData?.order?.procedure_name }}</span></div>
            <div>Ordering Doctor: <span class="text-slate-700">{{ reportData?.order?.ordering_doctor }}</span></div>
            <div>Exam Date: {{ reportData?.order?.completed_at || reportData?.order?.scheduled_at }}</div>
          </div>
        </div>

        <!-- Clinical Narrative Sections -->
        <div class="space-y-4 mb-6 text-slate-800">
          <div>
            <h4 class="font-bold text-[11px] uppercase tracking-wider text-slate-500 mb-0.5">Clinical Indication</h4>
            <p class="text-slate-900">{{ reportData?.clinical_indication || 'Not provided.' }}</p>
          </div>

          <div>
            <h4 class="font-bold text-[11px] uppercase tracking-wider text-slate-500 mb-0.5">Technique</h4>
            <p class="text-slate-900">{{ reportData?.technique || 'Standard imaging protocol.' }}</p>
          </div>

          <div v-if="reportData?.comparison">
            <h4 class="font-bold text-[11px] uppercase tracking-wider text-slate-500 mb-0.5">Comparison</h4>
            <p class="text-slate-900">{{ reportData?.comparison }}</p>
          </div>

          <div>
            <h4 class="font-bold text-[11px] uppercase tracking-wider text-slate-500 mb-0.5">Findings</h4>
            <div class="bg-slate-50 p-3 rounded-lg border border-slate-200 font-mono whitespace-pre-wrap leading-relaxed text-slate-900">
              {{ reportData?.findings || 'No acute findings recorded.' }}
            </div>
          </div>

          <div>
            <h4 class="font-bold text-sm uppercase tracking-wider text-slate-900 mb-1 border-b border-slate-200 pb-1">
              Diagnostic Impression
            </h4>
            <div class="p-3 bg-blue-50/50 rounded-lg border border-blue-200 font-semibold text-slate-900 text-xs leading-relaxed">
              {{ reportData?.impression }}
            </div>
          </div>

          <div v-if="reportData?.recommendations">
            <h4 class="font-bold text-[11px] uppercase tracking-wider text-slate-500 mb-0.5">Recommendations</h4>
            <p class="text-slate-800 italic">{{ reportData?.recommendations }}</p>
          </div>
        </div>

        <!-- Digital Signature Seal -->
        <div class="border-2 border-emerald-600/60 rounded-xl p-4 bg-emerald-50/30 flex flex-col md:flex-row justify-between items-center gap-4 mt-6">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-emerald-600 text-white flex items-center justify-center shrink-0">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </div>
            <div>
              <div class="font-black text-xs text-emerald-950 uppercase tracking-wide">
                DIGITALLY AUTHENTICATED RADIOLOGY REPORT
              </div>
              <div class="text-[11px] text-emerald-900 mt-0.5">
                Interpreted and Signed by: <strong class="font-semibold">{{ reportData?.radiologist?.name }}</strong>
              </div>
              <div class="text-[10px] text-slate-500 font-mono mt-0.5 select-all">
                Hash: {{ reportData?.digital_signature_hash || 'SHA-256 PENDING' }}
              </div>
            </div>
          </div>

          <div class="text-right text-[11px] text-slate-600">
            <div>Technologist: <strong class="text-slate-800">{{ reportData?.order?.technologist }}</strong></div>
            <div>Signed At: <strong class="text-slate-800">{{ reportData?.finalized_at }}</strong></div>
          </div>
        </div>

        <!-- Legal Disclaimer Footer -->
        <div class="text-[10px] text-slate-400 text-center mt-6 pt-3 border-t border-slate-200">
          This radiological report was electronically authenticated via SHA-256 digital signature. Conforms to HIPAA, ACR, and RSNA clinical documentation requirements.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  isOpen: { type: Boolean, default: false },
  reportData: { type: Object, default: null },
});

defineEmits(['close']);

function printReport() {
  window.print();
}
</script>
