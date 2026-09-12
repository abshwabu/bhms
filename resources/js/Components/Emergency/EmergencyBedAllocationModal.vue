<template>
  <div v-if="isOpen && emergencyCase" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-3xl max-w-xl w-full p-6 shadow-2xl border border-slate-200 max-h-[92vh] overflow-y-auto">
      <!-- Modal Header -->
      <div class="flex items-start justify-between pb-4 border-b border-slate-100">
        <div>
          <div class="flex items-center gap-2">
            <span class="font-bold text-base text-slate-900">Emergency Bed Allocation</span>
            <span
              class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase text-white"
              :class="getEsiBadgeClass(emergencyCase.current_esi_level)"
            >
              ESI Level {{ emergencyCase.current_esi_level }}
            </span>
          </div>
          <p class="text-xs text-slate-500 mt-0.5">
            {{ emergencyCase.display_patient_name }} &bull; Case #{{ emergencyCase.case_number }}
          </p>
        </div>
        <button @click="$emit('close')" class="p-1 text-slate-400 hover:text-slate-600 cursor-pointer">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Current Bed Status -->
      <div v-if="emergencyCase.assigned_bed_id" class="mt-4 p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-950 flex items-center justify-between text-xs">
        <div>
          <div class="font-bold">Currently Assigned: Bed {{ emergencyCase.bed_number }}</div>
          <div class="text-[11px] text-emerald-800 mt-0.5">{{ emergencyCase.ward_name }} &bull; Assigned at {{ formatTime(emergencyCase.bed_assigned_at) }}</div>
        </div>
        <button
          @click="releaseCurrentBed"
          :disabled="allocating"
          class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition cursor-pointer disabled:opacity-50"
        >
          Release Bed
        </button>
      </div>

      <!-- Available & Occupied Beds Grid -->
      <div class="mt-4 space-y-3">
        <div class="flex items-center justify-between">
          <label class="block text-xs font-bold text-slate-800">Select Emergency Bay / Acute Care Bed</label>
          <span class="text-[11px] text-slate-400">Click any bed to select</span>
        </div>

        <div v-if="loadingBeds" class="py-8 text-center text-slate-400 text-xs">
          Loading acute care beds...
        </div>

        <div v-else class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 max-h-60 overflow-y-auto p-1">
          <button
            v-for="b in beds"
            :key="b.id"
            type="button"
            @click="selectBed(b)"
            class="p-3 rounded-2xl border text-left transition relative cursor-pointer"
            :class="selectedBedId === b.id
              ? 'border-blue-600 bg-blue-50/50 ring-2 ring-blue-500/20'
              : (b.is_available ? 'border-slate-200 bg-white hover:border-slate-300' : 'border-amber-200 bg-amber-50/40 hover:border-amber-300')"
          >
            <div class="flex items-center justify-between">
              <span class="font-black text-xs text-slate-900">{{ b.bed_number }}</span>
              <span
                class="w-2 h-2 rounded-full"
                :class="b.is_available ? 'bg-emerald-500' : 'bg-amber-500'"
                :title="b.status"
              ></span>
            </div>
            <div class="text-[10px] text-slate-500 truncate mt-0.5">{{ b.ward_name }}</div>
            <div class="mt-1 flex items-center gap-1 text-[9px] font-semibold uppercase">
              <span :class="b.is_available ? 'text-emerald-700' : 'text-amber-700'">
                {{ b.status }}
              </span>
            </div>
          </button>
        </div>
      </div>

      <!-- Priority Override Alert & Justification Box -->
      <div
        v-if="selectedBed && !selectedBed.is_available"
        class="mt-5 p-4 rounded-2xl bg-amber-50/90 border border-amber-300 text-amber-950 text-xs space-y-2"
      >
        <div class="flex items-center gap-2 font-bold text-amber-950">
          <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <span>Priority Bed Override Required</span>
        </div>
        <p class="text-[11px] text-amber-900 leading-relaxed">
          Bed <strong>{{ selectedBed.bed_number }}</strong> is currently marked as <strong>{{ selectedBed.status }}</strong>. To assign this bed to this emergency patient, an emergency priority override with a logged clinical justification is legally mandated.
        </p>

        <div>
          <label class="block text-[11px] font-bold text-amber-950 mb-1">
            Logged Clinical Justification * (Mandatory for Audit Trail)
          </label>
          <textarea
            v-model="overrideReason"
            rows="2"
            placeholder="e.g. Critical ESI-1 acute STEMI / trauma resuscitation; patient hemodynamically unstable, overriding standard elective reservation."
            class="w-full px-3 py-2 bg-white border border-amber-300 rounded-xl text-xs text-slate-800 focus:ring-2 focus:ring-amber-500 focus:outline-none"
            required
          ></textarea>
        </div>
      </div>

      <!-- Modal Actions -->
      <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
        <span class="text-xs text-slate-400">
          {{ selectedBed ? `Selected: ${selectedBed.bed_number}` : 'Select a bed above' }}
        </span>

        <div class="flex gap-2">
          <button
            type="button"
            @click="$emit('close')"
            class="px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 cursor-pointer"
          >
            Cancel
          </button>
          <button
            type="button"
            @click="confirmAllocation"
            :disabled="!selectedBedId || allocating || (selectedBed && !selectedBed.is_available && !overrideReason.trim())"
            class="px-4 py-2 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer disabled:opacity-50"
            :class="selectedBed && !selectedBed.is_available ? 'bg-amber-600 hover:bg-amber-700 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white'"
          >
            {{ allocating ? 'Assigning...' : (selectedBed && !selectedBed.is_available ? 'Execute Priority Override' : 'Allocate Bed') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
  emergencyCase: {
    type: Object,
    default: null,
  },
  branchId: {
    type: String,
    default: null,
  },
});

const emit = defineEmits(['close', 'bed-allocated', 'bed-released']);

const beds = ref([]);
const loadingBeds = ref(false);
const selectedBedId = ref('');
const selectedBed = ref(null);
const overrideReason = ref('');
const allocating = ref(false);

const getEsiBadgeClass = (level) => {
  if (level === 1) return 'bg-rose-600';
  if (level === 2) return 'bg-orange-500';
  if (level === 3) return 'bg-amber-500';
  if (level === 4) return 'bg-emerald-600';
  return 'bg-blue-600';
};

const formatTime = (dt) => {
  if (!dt) return '—';
  return new Date(dt).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
};

const fetchBeds = async () => {
  loadingBeds.value = true;
  try {
    const params = new URLSearchParams();
    if (props.branchId) params.append('branch_id', props.branchId);

    const res = await fetch(`/api/v1/emergency/beds/available?${params.toString()}`, {
      headers: {
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
    });
    const json = await res.json();
    if (json.success) {
      beds.value = json.data || [];
    }
  } catch (err) {
    console.error('Failed to load beds', err);
  } finally {
    loadingBeds.value = false;
  }
};

const selectBed = (b) => {
  selectedBedId.value = b.id;
  selectedBed.value = b;
  if (b.is_available) {
    overrideReason.value = '';
  } else if (!overrideReason.value) {
    overrideReason.value = `Immediate acute resuscitation protocol for Case ${props.emergencyCase?.case_number}; unstable patient overrides standard queue.`;
  }
};

const confirmAllocation = async () => {
  if (!props.emergencyCase || !selectedBedId.value) return;
  allocating.value = true;

  const isOverride = selectedBed.value && !selectedBed.value.is_available;

  try {
    const res = await fetch(`/api/v1/emergency/cases/${props.emergencyCase.id}/allocate-bed`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
      body: JSON.stringify({
        bed_id: selectedBedId.value,
        is_override: isOverride,
        override_reason: isOverride ? overrideReason.value.trim() : null,
      }),
    });
    const json = await res.json();
    if (json.success) {
      emit('bed-allocated', json.data);
      emit('close');
    } else {
      alert(json.message || 'Error allocating bed');
    }
  } catch (err) {
    console.error('Failed to allocate bed', err);
  } finally {
    allocating.value = false;
  }
};

const releaseCurrentBed = async () => {
  if (!props.emergencyCase) return;
  if (!confirm(`Release bed for ${props.emergencyCase.display_patient_name}?`)) return;
  allocating.value = true;
  try {
    const res = await fetch(`/api/v1/emergency/cases/${props.emergencyCase.id}/release-bed`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
      body: JSON.stringify({ notes: 'Bed released by clinician.' }),
    });
    const json = await res.json();
    if (json.success) {
      emit('bed-released', json.data);
      emit('close');
    }
  } catch (err) {
    console.error('Failed to release bed', err);
  } finally {
    allocating.value = false;
  }
};

watch(
  () => props.isOpen,
  (val) => {
    if (val) {
      selectedBedId.value = props.emergencyCase?.assigned_bed_id || '';
      overrideReason.value = '';
      fetchBeds();
    }
  }
);

onMounted(() => {
  if (props.isOpen) {
    fetchBeds();
  }
});
</script>
