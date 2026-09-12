<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-200 animate-in fade-in zoom-in duration-200">
      <!-- Header -->
      <div class="p-4 bg-slate-900 text-white flex items-center justify-between">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center font-bold text-white text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
          </div>
          <div>
            <h3 class="font-bold text-sm">Specimen Tube Barcode Label</h3>
            <p class="text-[11px] text-slate-400">Printable 2" x 1" Laboratory Accession Tag</p>
          </div>
        </div>
        <button @click="$emit('close')" class="text-slate-400 hover:text-white transition cursor-pointer p-1">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Printable Label Box -->
      <div class="p-6 bg-slate-100 flex flex-col items-center">
        <div id="printable-specimen-label" class="w-80 bg-white border-2 border-dashed border-slate-400 rounded-lg p-3.5 shadow-sm text-slate-900 font-mono text-xs select-all">
          <div class="flex justify-between items-start border-b border-slate-300 pb-1.5 mb-2">
            <div>
              <div class="font-black text-sm tracking-tight text-slate-900 leading-none">
                {{ sample?.patient?.name || 'PATIENT NAME' }}
              </div>
              <div class="text-[10px] text-slate-600 mt-0.5">
                MRN: <span class="font-bold text-slate-800">{{ sample?.patient?.mrn || 'N/A' }}</span>
                | {{ sample?.patient?.gender === 'male' ? 'M' : 'F' }}
                | Age: {{ sample?.patient?.age || 'N/A' }}
              </div>
            </div>
            <span class="px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 text-[10px] font-bold uppercase">
              {{ sample?.sample_type || 'BLOOD' }}
            </span>
          </div>

          <!-- Barcode Graphic (SVG or generated bars) -->
          <div class="my-2 flex flex-col items-center justify-center bg-slate-50 p-2 rounded border border-slate-200">
            <div v-if="barcodeSvg" v-html="barcodeSvg" class="w-full flex justify-center overflow-hidden"></div>
            <!-- Fallback Barcode Visualization -->
            <div v-else class="flex items-center justify-center gap-[3px] h-12 w-full px-4">
              <span v-for="n in 36" :key="n" :class="[n % 3 === 0 ? 'w-1 bg-black' : (n % 2 === 0 ? 'w-0.5 bg-black' : 'w-[2px] bg-black')]" class="h-10 inline-block"></span>
            </div>
            <div class="font-bold text-xs tracking-widest text-slate-800 mt-1 font-mono">
              {{ sample?.barcode || 'SMP-2026-00000000' }}
            </div>
          </div>

          <!-- Order & Collection Details -->
          <div class="text-[10px] space-y-0.5 text-slate-600 pt-1 border-t border-slate-200">
            <div class="flex justify-between">
              <span>Order: <strong>{{ sample?.lab_order?.order_number || 'N/A' }}</strong></span>
              <span>Tube: <strong>{{ sample?.container_type || 'EDTA' }}</strong></span>
            </div>
            <div class="flex justify-between">
              <span>Collected: {{ formatDate(sample?.collected_at) }}</span>
              <span>Site: {{ sample?.collection_site || 'Venipuncture' }}</span>
            </div>
          </div>
        </div>

        <div class="text-xs text-slate-500 mt-3 flex items-center gap-1.5">
          <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          Scannable by all standard 1D/2D optical laboratory barcode scanners.
        </div>
      </div>

      <!-- Footer Buttons -->
      <div class="p-4 bg-white border-t border-slate-200 flex justify-end gap-3">
        <button
          type="button"
          @click="$emit('close')"
          class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition cursor-pointer"
        >
          Close
        </button>
        <button
          type="button"
          @click="printLabel"
          class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-500/20 flex items-center gap-2 transition cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
          </svg>
          Print Label (Direct Thermal)
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  isOpen: { type: Boolean, default: false },
  sample: { type: Object, default: null },
  barcodeSvg: { type: String, default: null },
});

defineEmits(['close']);

function formatDate(isoStr) {
  if (!isoStr) return 'Pending Collection';
  const d = new Date(isoStr);
  return d.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function printLabel() {
  window.print();
}
</script>
