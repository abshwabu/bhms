<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full overflow-hidden border border-slate-200 animate-in fade-in zoom-in duration-200">
      <!-- Modal Toolbar (Non-printable) -->
      <div class="p-4 bg-slate-900 text-white flex items-center justify-between print:hidden">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-emerald-600 flex items-center justify-center font-bold text-white text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <div>
            <h3 class="font-bold text-sm">Official Diagnostic Laboratory Report</h3>
            <p class="text-[11px] text-slate-400">Report No: {{ reportData?.report_number || report?.report_number }} (v{{ reportData?.version || report?.version || 1 }})</p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="printReport"
            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-sm flex items-center gap-1.5 transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Print / Save PDF
          </button>
          <button
            v-if="report?.status === 'signed' && !report?.is_amended"
            type="button"
            @click="$emit('amend', report)"
            class="px-3.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl shadow-sm flex items-center gap-1.5 transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Create Amendment
          </button>
          <button @click="$emit('close')" class="text-slate-400 hover:text-white transition cursor-pointer p-1">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Printable Report Layout (A4 format) -->
      <div id="diagnostic-report-document" class="p-8 bg-white max-h-[80vh] overflow-y-auto print:max-h-none print:overflow-visible text-slate-800 font-sans">
        
        <!-- Hospital Letterhead -->
        <div class="border-b-2 border-slate-900 pb-4 mb-6 flex justify-between items-start">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-blue-700 to-indigo-600 flex items-center justify-center font-black text-2xl text-white">
              +
            </div>
            <div>
              <h1 class="text-xl font-extrabold text-slate-900 tracking-tight leading-tight">
                {{ reportData?.facility?.name || 'METRO GENERAL HOSPITAL' }}
              </h1>
              <p class="text-xs text-slate-600 font-medium">
                {{ reportData?.facility?.department || 'Department of Pathology & Clinical Laboratory Sciences' }}
              </p>
              <p class="text-[11px] text-slate-400">Accredited Clinical Reference Laboratory | CLIA & ISO-15189 Compliant</p>
            </div>
          </div>

          <div class="text-right">
            <div class="text-xs font-mono font-bold text-slate-900">
              REPORT: {{ reportData?.report_number || report?.report_number }}
            </div>
            <div class="text-[11px] text-slate-500">
              Version: <span class="font-bold">{{ reportData?.version || report?.version || 1 }}</span>
              <span v-if="reportData?.is_amended || report?.is_amended" class="ml-1 px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 font-bold uppercase text-[10px]">
                Amended
              </span>
            </div>
            <div class="text-[11px] text-slate-500">Date: {{ reportData?.signed_at || formatCurrentDate() }}</div>
          </div>
        </div>

        <!-- Amendment Notice Banner (if amended) -->
        <div v-if="reportData?.is_amended || report?.is_amended || reportData?.amendment_reason" class="mb-5 p-3 rounded-xl bg-amber-50 border border-amber-300 text-amber-900 text-xs">
          <div class="font-bold flex items-center gap-1.5">
            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            OFFICIAL AMENDMENT RECORD (REV {{ reportData?.version || report?.version || 2 }})
          </div>
          <div class="mt-0.5 text-[11px] text-amber-800">
            <strong>Reason for Revision:</strong> {{ reportData?.amendment_reason || report?.amendment_reason || 'Re-analyzed and recalibrated parameter values.' }}
          </div>
        </div>

        <!-- Demographics & Clinical Information Grid -->
        <div class="grid grid-cols-2 gap-4 p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs mb-6">
          <div class="space-y-1">
            <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Patient Demographics</div>
            <div class="text-sm font-bold text-slate-900">{{ reportData?.patient?.name || report?.patient?.name || 'Unknown Patient' }}</div>
            <div class="text-slate-600">
              MRN: <span class="font-mono font-bold">{{ reportData?.patient?.mrn || report?.patient?.mrn || 'N/A' }}</span>
              | Gender: <span class="capitalize">{{ reportData?.patient?.gender || report?.patient?.gender }}</span>
              | Age: {{ reportData?.patient?.age || report?.patient?.age }} yrs
            </div>
            <div class="text-slate-600">DOB: {{ reportData?.patient?.dob || 'N/A' }}</div>
          </div>

          <div class="space-y-1 border-l border-slate-200 pl-4">
            <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Order & Accession Data</div>
            <div>Order No: <span class="font-mono font-bold">{{ reportData?.order?.order_number || report?.lab_order?.order_number || 'N/A' }}</span></div>
            <div>Ordering Clinician: <span class="font-semibold">{{ reportData?.order?.ordering_doctor || report?.lab_order?.ordering_doctor || 'Attending Physician' }}</span></div>
            <div>Specimen Barcode: <span class="font-mono font-bold text-blue-700">{{ reportData?.order?.sample_barcode || report?.sample?.barcode || 'N/A' }}</span></div>
            <div>Specimen Type: {{ reportData?.order?.sample_type || report?.sample?.sample_type || 'Whole Blood' }}</div>
          </div>
        </div>

        <!-- Test Name Banner -->
        <div class="mb-4 flex items-center justify-between bg-slate-800 text-white px-4 py-2 rounded-lg">
          <div>
            <span class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Test / Diagnostic Panel:</span>
            <span class="ml-2 font-bold text-sm">{{ reportData?.test?.name || report?.lab_test?.name || report?.lab_order?.test_type || 'Diagnostic Investigation' }}</span>
          </div>
          <div class="text-xs text-slate-300 font-mono">
            Method: {{ reportData?.test?.methodology || report?.methodology || 'Automated Clinical Analyzer' }}
          </div>
        </div>

        <!-- Results Table -->
        <table class="w-full text-xs text-left border border-slate-200 mb-6 rounded-lg overflow-hidden">
          <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
            <tr>
              <th class="py-2.5 px-3">Diagnostic Parameter</th>
              <th class="py-2.5 px-3 text-right">Measured Value</th>
              <th class="py-2.5 px-3">Unit</th>
              <th class="py-2.5 px-3">Reference Interval</th>
              <th class="py-2.5 px-3 text-center">Clinical Flag</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="item in (reportData?.parameters || report?.items || [])"
              :key="item.id || item.parameter_name"
              :class="{
                'bg-red-50/60 font-semibold text-red-950': item.flag === 'critical_low' || item.flag === 'critical_high',
                'bg-amber-50/40 text-amber-950 font-medium': item.flag === 'low' || item.flag === 'high' || item.flag === 'abnormal',
              }"
            >
              <td class="py-2.5 px-3 font-semibold text-slate-900">
                {{ item.parameter_name }}
              </td>
              <td class="py-2.5 px-3 text-right font-mono font-bold text-sm">
                {{ item.measured_value }}
              </td>
              <td class="py-2.5 px-3 text-slate-500 font-mono">
                {{ item.unit || '-' }}
              </td>
              <td class="py-2.5 px-3 text-slate-600 font-mono">
                {{ item.reference_range || (item.reference_low && item.reference_high ? `${item.reference_low} - ${item.reference_high}` : 'Normal') }}
              </td>
              <td class="py-2.5 px-3 text-center">
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
                  class="px-2.5 py-1 rounded-full bg-red-600 text-white text-[10px] font-black uppercase tracking-wider animate-pulse inline-flex items-center gap-1"
                >
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                  PANIC CRITICAL
                </span>
                <span
                  v-else
                  class="px-2 py-0.5 rounded-full bg-purple-100 text-purple-800 text-[10px] font-bold"
                >
                  {{ item.flag?.toUpperCase() || 'NORMAL' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Clinical Remarks -->
        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs mb-6">
          <div class="font-bold text-slate-700 text-[11px] uppercase tracking-wider mb-1">Clinical Remarks / Pathologist Impression:</div>
          <div class="text-slate-800 italic">
            {{ reportData?.clinical_remarks || report?.clinical_remarks || 'Findings reported in accordance with standardized analytical methods. Correlation with patient clinical status recommended.' }}
          </div>
        </div>

        <!-- Digital Signature & Legal Immortality Box -->
        <div class="border-2 border-emerald-600/60 rounded-xl p-4 bg-emerald-50/40 flex flex-col md:flex-row justify-between items-center gap-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-md">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </div>
            <div>
              <div class="font-black text-xs text-emerald-950 uppercase tracking-wide flex items-center gap-1.5">
                <span>DIGITALLY SIGNED & VERIFIED REPORT</span>
                <span class="text-[10px] font-normal text-emerald-800 bg-emerald-200/80 px-1.5 py-0.5 rounded font-mono">LOCKED</span>
              </div>
              <div class="text-[11px] text-emerald-900 mt-0.5">
                Certified by: <strong class="font-semibold">{{ reportData?.signatories?.pathologist || report?.pathologist || 'Consultant Pathologist, MD' }}</strong>
              </div>
              <div class="text-[10px] text-slate-500 font-mono mt-0.5 select-all">
                Hash: {{ reportData?.digital_signature_hash || report?.digital_signature_hash || 'SIG-SHA256-PENDING' }}
              </div>
            </div>
          </div>

          <div class="text-right text-[11px] text-slate-600">
            <div>Technologist: <strong class="text-slate-800">{{ reportData?.signatories?.technician || report?.technician || 'Certified Med Tech' }}</strong></div>
            <div>Signed Timestamp: <strong class="text-slate-800">{{ reportData?.signed_at || formatCurrentDate() }}</strong></div>
          </div>
        </div>

        <!-- Disclaimer -->
        <div class="text-[10px] text-slate-400 text-center mt-6 pt-3 border-t border-slate-200">
          Electronic medical diagnostic report authenticated via SHA-256 cryptographic verification. Legally binding document under CLIA/HIPAA standards. Unauthorized alteration strictly prohibited.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  isOpen: { type: Boolean, default: false },
  report: { type: Object, default: null },
  reportData: { type: Object, default: null },
});

defineEmits(['close', 'amend']);

function formatCurrentDate() {
  return new Date().toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function printReport() {
  window.print();
}
</script>
