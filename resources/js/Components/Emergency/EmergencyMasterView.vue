<template>
  <div class="space-y-6">
    <!-- Sub-navigation Bar for Emergency & Ambulance -->
    <div class="bg-white p-2 rounded-2xl border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-2">
      <div class="flex items-center gap-1.5 overflow-x-auto">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="currentTab = tab.id"
          :class="currentTab === tab.id ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'"
          class="px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer whitespace-nowrap"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon" />
          </svg>
          <span>{{ tab.label }}</span>

          <!-- Critical / Active Badge -->
          <span
            v-if="tab.id === 'triage' && criticalTriageCount > 0"
            class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-black bg-rose-600 text-white animate-pulse"
          >
            {{ criticalTriageCount }} RESUS
          </span>

          <span
            v-if="tab.id === 'ambulance' && activeMissionCount > 0"
            class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-black bg-amber-500 text-white"
          >
            {{ activeMissionCount }} ACTIVE
          </span>
        </button>
      </div>

      <!-- Quick Operational Status Badge -->
      <div class="px-3.5 py-1.5 bg-rose-50 border border-rose-200 rounded-xl text-xs font-mono font-semibold text-rose-800 flex items-center gap-2">
        <span class="w-2.5 h-2.5 rounded-full bg-rose-600 animate-ping"></span>
        <span>LEVEL-1 TRAUMA & DISPATCH CAD ONLINE</span>
      </div>
    </div>

    <!-- Active Tab Content -->
    <div>
      <TriageIntakeScreen
        v-if="currentTab === 'triage'"
        :branch-id="branchId"
        @critical-count-updated="onCriticalCountUpdated"
      />

      <AmbulanceDispatchDashboard
        v-else-if="currentTab === 'ambulance'"
        :branch-id="branchId"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import TriageIntakeScreen from './TriageIntakeScreen.vue';
import AmbulanceDispatchDashboard from './AmbulanceDispatchDashboard.vue';

const props = defineProps({
  branchId: {
    type: String,
    default: null,
  },
});

const currentTab = ref('triage');
const criticalTriageCount = ref(1);
const activeMissionCount = ref(1);

const tabs = [
  {
    id: 'triage',
    label: 'ED Triage & Resuscitation Queue',
    icon: 'M13 10V3L4 14h7v7l9-11h-7z',
  },
  {
    id: 'ambulance',
    label: 'Ambulance Fleet & CAD Dispatch',
    icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
  },
];

function onCriticalCountUpdated(count) {
  criticalTriageCount.value = count;
}
</script>
