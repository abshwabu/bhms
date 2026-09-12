<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200 animate-in fade-in zoom-in duration-200">
      
      <!-- Modal Header -->
      <div class="p-4 bg-slate-900 text-white flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center font-bold text-white text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
          </div>
          <div>
            <h3 class="font-bold text-sm">Attach Scans & PACS Upload</h3>
            <p class="text-[11px] text-slate-400">Order Accession: {{ order?.accession_number || 'N/A' }}</p>
          </div>
        </div>

        <button @click="$emit('close')" class="text-slate-400 hover:text-white transition cursor-pointer p-1">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Context Info -->
      <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 text-xs flex justify-between items-center text-slate-600">
        <div>
          Patient: <strong class="text-slate-900">{{ order?.patient?.name }}</strong> ({{ order?.patient?.mrn }})
        </div>
        <div>
          Modality: <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-800 font-bold uppercase text-[10px]">{{ order?.modality }}</span>
        </div>
      </div>

      <!-- Upload Form -->
      <div class="p-6 space-y-4">
        <div
          @dragover.prevent="isDragging = true"
          @dragleave.prevent="isDragging = false"
          @drop.prevent="handleFileDrop"
          :class="isDragging ? 'border-blue-500 bg-blue-50/50' : 'border-slate-300 bg-slate-50 hover:bg-slate-100/60'"
          class="border-2 border-dashed rounded-xl p-8 text-center transition cursor-pointer flex flex-col items-center justify-center"
          @click="triggerFileInput"
        >
          <input
            ref="fileInputRef"
            type="file"
            multiple
            accept=".dcm,.dicom,image/jpeg,image/png"
            class="hidden"
            @change="handleFileSelect"
          />

          <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mb-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
          </div>

          <p class="text-xs font-bold text-slate-700">Click or drag & drop radiology files here</p>
          <p class="text-[11px] text-slate-400 mt-1">Supports DICOM (.dcm), High-Resolution JPEG, and PNG</p>
          <p class="text-[10px] text-emerald-600 font-medium mt-2 flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Large studies automatically upload via chunked streaming without timing out
          </p>
        </div>

        <!-- Selected Files List -->
        <div v-if="selectedFiles.length > 0" class="space-y-2">
          <div class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">Selected Scans ({{ selectedFiles.length }}):</div>
          <div class="max-h-40 overflow-y-auto space-y-1.5 border border-slate-200 rounded-lg p-2 divide-y divide-slate-100">
            <div v-for="(f, i) in selectedFiles" :key="i" class="pt-1.5 first:pt-0 flex justify-between items-center text-xs">
              <div class="flex items-center gap-2 truncate">
                <span :class="f.name.endsWith('.dcm') ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'" class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold uppercase">
                  {{ f.name.endsWith('.dcm') ? 'DICOM' : 'IMG' }}
                </span>
                <span class="truncate text-slate-800 font-medium">{{ f.name }}</span>
                <span class="text-[11px] text-slate-400">({{ formatSize(f.size) }})</span>
              </div>
              <button
                v-if="!isUploading"
                @click="removeFile(i)"
                class="text-slate-400 hover:text-red-600 p-1 transition cursor-pointer"
              >
                &times;
              </button>
            </div>
          </div>
        </div>

        <!-- Upload Progress Indicator -->
        <div v-if="isUploading" class="space-y-1.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
          <div class="flex justify-between text-xs font-semibold text-slate-700">
            <span>{{ uploadStatusMessage }}</span>
            <span class="font-mono font-bold text-blue-600">{{ uploadProgress }}%</span>
          </div>
          <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
            <div
              class="bg-blue-600 h-2 rounded-full transition-all duration-200 ease-out"
              :style="{ width: `${uploadProgress}%` }"
            ></div>
          </div>
        </div>

        <!-- Error Message -->
        <div v-if="errorMessage" class="p-3 bg-red-50 border border-red-200 text-xs text-red-700 rounded-lg">
          {{ errorMessage }}
        </div>
      </div>

      <!-- Footer Actions -->
      <div class="p-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
        <button
          type="button"
          :disabled="isUploading"
          @click="$emit('close')"
          class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200/60 rounded-xl transition cursor-pointer disabled:opacity-50"
        >
          Cancel
        </button>
        <button
          type="button"
          :disabled="selectedFiles.length === 0 || isUploading"
          @click="startUploadProcess"
          class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-500/20 flex items-center gap-2 transition cursor-pointer disabled:opacity-50"
        >
          <svg v-if="isUploading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
          </svg>
          <span>{{ isUploading ? 'Uploading Scans...' : 'Upload & Attach Scans' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  isOpen: { type: Boolean, default: false },
  order: { type: Object, default: null },
  branchId: { type: String, default: 'b9ff561a-5396-4309-9b08-3e7b358310e9' },
});

const emit = defineEmits(['close', 'uploaded']);

const fileInputRef = ref(null);
const selectedFiles = ref([]);
const isDragging = ref(false);
const isUploading = ref(false);
const uploadProgress = ref(0);
const uploadStatusMessage = ref('');
const errorMessage = ref('');

function triggerFileInput() {
  if (fileInputRef.value) {
    fileInputRef.value.click();
  }
}

function handleFileSelect(e) {
  const files = Array.from(e.target.files || []);
  selectedFiles.value = [...selectedFiles.value, ...files];
  e.target.value = '';
}

function handleFileDrop(e) {
  isDragging.value = false;
  const files = Array.from(e.dataTransfer.files || []);
  selectedFiles.value = [...selectedFiles.value, ...files];
}

function removeFile(index) {
  selectedFiles.value.splice(index, 1);
}

function formatSize(bytes) {
  if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
  if (bytes >= 1024) return (bytes / 1024).toFixed(0) + ' KB';
  return bytes + ' B';
}

async function startUploadProcess() {
  if (selectedFiles.value.length === 0 || !props.order) return;

  isUploading.value = true;
  uploadProgress.value = 0;
  errorMessage.value = '';

  try {
    for (let fIndex = 0; fIndex < selectedFiles.value.length; fIndex++) {
      const file = selectedFiles.value[fIndex];
      uploadStatusMessage.value = `Uploading ${file.name} (${fIndex + 1}/${selectedFiles.value.length})...`;

      const CHUNK_SIZE = 2 * 1024 * 1024; // 2MB chunk
      // If file is > 4MB or DICOM, upload in chunks to prevent timeout
      if (file.size > 4 * 1024 * 1024) {
        await uploadFileInChunks(file);
      } else {
        await uploadFileDirect(file);
      }
    }

    uploadStatusMessage.value = 'Upload completed!';
    uploadProgress.value = 100;
    setTimeout(() => {
      selectedFiles.value = [];
      emit('uploaded');
      emit('close');
    }, 600);
  } catch (err) {
    errorMessage.value = err.message || 'File upload failed.';
  } finally {
    isUploading.value = false;
  }
}

async function uploadFileDirect(file) {
  const formData = new FormData();
  formData.append('imaging_order_id', props.order.id);
  formData.append('file', file);

  const res = await fetch('/api/v1/radiology/files', {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'X-Branch-ID': props.branchId,
    },
    body: formData,
  });

  const json = await res.json();
  if (!res.ok) throw new Error(json.message || `Failed to upload ${file.name}`);
  uploadProgress.value = 100;
}

async function uploadFileInChunks(file) {
  const CHUNK_SIZE = 2 * 1024 * 1024; // 2MB chunks
  const totalChunks = Math.ceil(file.size / CHUNK_SIZE);

  // 1. Init Chunk Session
  const initRes = await fetch('/api/v1/radiology/files/chunk/init', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Branch-ID': props.branchId,
    },
    body: JSON.stringify({
      file_name: file.name,
      total_chunks: totalChunks,
      total_size_bytes: file.size,
    }),
  });

  const initJson = await initRes.json();
  if (!initRes.ok) throw new Error(initJson.message || 'Chunk session init failed.');
  const uploadId = initJson.data.upload_id;

  // 2. Transmit each chunk
  for (let c = 0; c < totalChunks; c++) {
    const start = c * CHUNK_SIZE;
    const end = Math.min(start + CHUNK_SIZE, file.size);
    const chunkBlob = file.slice(start, end);

    const chunkData = new FormData();
    chunkData.append('upload_id', uploadId);
    chunkData.append('chunk_index', c);
    chunkData.append('total_chunks', totalChunks);
    chunkData.append('chunk', chunkBlob, `${file.name}.part${c}`);

    const cRes = await fetch('/api/v1/radiology/files/chunk/upload', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Branch-ID': props.branchId,
      },
      body: chunkData,
    });

    if (!cRes.ok) throw new Error(`Chunk ${c + 1} transmission error.`);

    uploadProgress.value = Math.round(((c + 1) / totalChunks) * 90);
  }

  // 3. Finalize and Assemble
  uploadStatusMessage.value = 'Assembling chunks into diagnostic image...';
  const finRes = await fetch('/api/v1/radiology/files/chunk/finalize', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Branch-ID': props.branchId,
    },
    body: JSON.stringify({
      upload_id: uploadId,
      imaging_order_id: props.order.id,
      file_name: file.name,
    }),
  });

  const finJson = await finRes.json();
  if (!finRes.ok) throw new Error(finJson.message || 'Failed to assemble image chunks.');
  uploadProgress.value = 100;
}
</script>
