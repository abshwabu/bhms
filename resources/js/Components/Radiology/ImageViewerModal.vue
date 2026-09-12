<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-hidden bg-slate-950 flex flex-col select-none animate-in fade-in duration-200">
    
    <!-- Top Toolbar -->
    <div class="h-14 bg-slate-900 border-b border-slate-800 px-4 flex items-center justify-between text-white shrink-0">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center font-bold text-sm">
          {{ order?.modality || 'RAD' }}
        </div>
        <div>
          <div class="font-bold text-xs flex items-center gap-2">
            <span>{{ order?.patient?.name || 'Unknown Patient' }}</span>
            <span class="text-slate-400 font-mono text-[11px] font-normal">MRN: {{ order?.patient?.mrn }}</span>
          </div>
          <div class="text-[10px] text-slate-400 flex items-center gap-2">
            <span>{{ order?.procedure_name }}</span>
            <span class="text-slate-500">|</span>
            <span class="font-mono text-blue-400">ACC: {{ order?.accession_number }}</span>
          </div>
        </div>
      </div>

      <!-- Center Viewer Controls (Zoom, Pan, Windowing, Invert, Rotate) -->
      <div class="flex items-center gap-1.5 bg-slate-800/80 p-1 rounded-xl border border-slate-700/60 text-xs">
        <!-- Zoom Out -->
        <button
          @click="zoomOut"
          class="p-1.5 text-slate-300 hover:text-white hover:bg-slate-700 rounded-lg transition cursor-pointer"
          title="Zoom Out (-)"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
          </svg>
        </button>

        <!-- Zoom Level Display -->
        <span class="px-2 font-mono text-[11px] text-blue-300 font-semibold w-14 text-center">
          {{ Math.round(zoom * 100) }}%
        </span>

        <!-- Zoom In -->
        <button
          @click="zoomIn"
          class="p-1.5 text-slate-300 hover:text-white hover:bg-slate-700 rounded-lg transition cursor-pointer"
          title="Zoom In (+)"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
        </button>

        <div class="w-px h-4 bg-slate-700 mx-0.5"></div>

        <!-- Reset Fit -->
        <button
          @click="resetView"
          class="px-2.5 py-1 text-[11px] font-medium text-slate-300 hover:text-white hover:bg-slate-700 rounded-lg transition cursor-pointer"
          title="Reset Zoom & Pan"
        >
          Fit Screen
        </button>

        <div class="w-px h-4 bg-slate-700 mx-0.5"></div>

        <!-- Rotate 90 Deg -->
        <button
          @click="rotateClockwise"
          class="p-1.5 text-slate-300 hover:text-white hover:bg-slate-700 rounded-lg transition cursor-pointer"
          title="Rotate 90° Clockwise"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
        </button>

        <!-- Invert Negative / Positive -->
        <button
          @click="isInverted = !isInverted"
          :class="isInverted ? 'bg-blue-600 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-700'"
          class="px-2 py-1 text-[11px] font-medium rounded-lg transition cursor-pointer flex items-center gap-1"
          title="Invert Colors (Bone / Soft Tissue Window)"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
          Invert
        </button>

        <!-- Window Presets -->
        <select
          v-model="windowPreset"
          @change="applyPreset"
          class="bg-slate-900 text-slate-300 text-[11px] px-2 py-1 rounded-lg border border-slate-700 focus:outline-none"
        >
          <option value="default">Normal Preset</option>
          <option value="bone">Bone Window</option>
          <option value="lung">Lung Window</option>
          <option value="soft">Soft Tissue</option>
        </select>
      </div>

      <!-- Right Tools: PACS Hook & Close -->
      <div class="flex items-center gap-2">
        <a
          v-if="activeFile?.pacs_preview_url"
          :href="activeFile.pacs_preview_url"
          target="_blank"
          class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold transition flex items-center gap-1.5"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
          </svg>
          Open in PACS Web Viewer
        </a>

        <button
          @click="$emit('close')"
          class="p-1.5 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition cursor-pointer"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Main Viewport Area -->
    <div class="flex-1 flex overflow-hidden relative bg-black">
      
      <!-- Interactive Canvas / Image Display with Pan & Zoom -->
      <div
        class="flex-1 h-full flex items-center justify-center overflow-hidden cursor-grab active:cursor-grabbing relative"
        @mousedown="startPan"
        @mousemove="onPan"
        @mouseup="stopPan"
        @mouseleave="stopPan"
        @wheel.prevent="handleWheelZoom"
      >
        <!-- Diagnostic Overlay (Top-Left) -->
        <div class="absolute top-4 left-4 z-10 text-[11px] font-mono text-emerald-400 pointer-events-none drop-shadow-md space-y-0.5">
          <div class="font-bold text-white text-xs">{{ order?.patient?.name }}</div>
          <div>MRN: {{ order?.patient?.mrn }} | {{ order?.patient?.gender === 'male' ? 'M' : 'F' }} {{ order?.patient?.age }}y</div>
          <div>MOD: {{ order?.modality }} | {{ activeFile?.series_description || order?.procedure_name }}</div>
          <div>ACC: {{ order?.accession_number }}</div>
        </div>

        <!-- Diagnostic Overlay (Bottom-Right DICOM parameters) -->
        <div class="absolute bottom-4 right-4 z-10 text-[11px] font-mono text-slate-400 pointer-events-none text-right drop-shadow-md space-y-0.5">
          <div>WL: {{ windowLevel.contrast }}% / WW: {{ windowLevel.brightness }}%</div>
          <div>Zoom: {{ Math.round(zoom * 100) }}% | Rot: {{ rotation }}°</div>
          <div v-if="activeFile?.is_dicom" class="text-blue-400">DICOM SOP: {{ activeFile?.dicom_sop_instance_uid ? activeFile.dicom_sop_instance_uid.slice(-12) : 'DCM' }}</div>
        </div>

        <!-- Rendered Image -->
        <div
          v-if="activeFile"
          :style="{
            transform: `translate(${panX}px, ${panY}px) scale(${zoom}) rotate(${rotation}deg)`,
            filter: `contrast(${windowLevel.contrast}%) brightness(${windowLevel.brightness}%) ${isInverted ? 'invert(1)' : ''}`,
            transition: isPanning ? 'none' : 'transform 0.15s ease-out',
          }"
          class="origin-center max-w-full max-h-full flex items-center justify-center select-none"
        >
          <img
            :src="activeFile.file_url || '/placeholder-rad.jpg'"
            :alt="activeFile.original_file_name"
            class="max-w-[85vw] max-h-[80vh] object-contain rounded pointer-events-none shadow-2xl"
            @dragstart.prevent
          />
        </div>

        <!-- Fallback if no file attached -->
        <div v-else class="text-center text-slate-500 space-y-2">
          <svg class="w-12 h-12 mx-auto text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <div class="text-xs">No image scans uploaded for this order yet.</div>
        </div>
      </div>

      <!-- Right Thumbnail Strip (Multi-slice / Multi-image selector) -->
      <div v-if="files.length > 1" class="w-48 bg-slate-900 border-l border-slate-800 p-3 overflow-y-auto space-y-3 shrink-0">
        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Series Images ({{ files.length }})</div>
        <div
          v-for="(f, i) in files"
          :key="f.id"
          @click="selectActiveFile(f)"
          :class="activeFile?.id === f.id ? 'ring-2 ring-blue-500 bg-slate-800' : 'bg-slate-950/60 hover:bg-slate-800'"
          class="p-2 rounded-xl border border-slate-800 cursor-pointer transition flex flex-col gap-1.5"
        >
          <div class="h-24 bg-black rounded-lg overflow-hidden flex items-center justify-center">
            <img :src="f.file_url" class="max-h-full max-w-full object-cover" />
          </div>
          <div class="text-[10px] text-slate-300 font-mono truncate">{{ f.original_file_name }}</div>
          <div class="text-[9px] text-slate-500">Image {{ i + 1 }} of {{ files.length }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';

const props = defineProps({
  isOpen: { type: Boolean, default: false },
  order: { type: Object, default: null },
  files: { type: Array, default: () => [] },
});

defineEmits(['close']);

const activeFile = ref(null);
const zoom = ref(1.0);
const panX = ref(0);
const panY = ref(0);
const rotation = ref(0);
const isInverted = ref(false);
const windowPreset = ref('default');
const windowLevel = ref({ contrast: 100, brightness: 100 });

// Dragging state
const isPanning = ref(false);
const startMouseX = ref(0);
const startMouseY = ref(0);

watch(
  () => props.files,
  (newFiles) => {
    if (newFiles && newFiles.length > 0) {
      activeFile.value = newFiles[0];
    } else {
      activeFile.value = null;
    }
    resetView();
  },
  { immediate: true }
);

function selectActiveFile(f) {
  activeFile.value = f;
  resetView();
}

function zoomIn() {
  zoom.value = Math.min(zoom.value + 0.25, 4.0);
}

function zoomOut() {
  zoom.value = Math.max(zoom.value - 0.25, 0.25);
}

function handleWheelZoom(e) {
  if (e.deltaY < 0) {
    zoomIn();
  } else {
    zoomOut();
  }
}

function rotateClockwise() {
  rotation.value = (rotation.value + 90) % 360;
}

function resetView() {
  zoom.value = 1.0;
  panX.value = 0;
  panY.value = 0;
  rotation.value = 0;
  isInverted.value = false;
  windowPreset.value = 'default';
  windowLevel.value = { contrast: 100, brightness: 100 };
}

function applyPreset() {
  switch (windowPreset.value) {
    case 'bone':
      windowLevel.value = { contrast: 180, brightness: 90 };
      break;
    case 'lung':
      windowLevel.value = { contrast: 160, brightness: 130 };
      break;
    case 'soft':
      windowLevel.value = { contrast: 120, brightness: 105 };
      break;
    default:
      windowLevel.value = { contrast: 100, brightness: 100 };
      break;
  }
}

function startPan(e) {
  isPanning.value = true;
  startMouseX.value = e.clientX - panX.value;
  startMouseY.value = e.clientY - panY.value;
}

function onPan(e) {
  if (!isPanning.value) return;
  panX.value = e.clientX - startMouseX.value;
  panY.value = e.clientY - startMouseY.value;
}

function stopPan() {
  isPanning.value = false;
}
</script>
