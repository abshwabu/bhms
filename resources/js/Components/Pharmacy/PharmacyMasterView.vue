<template>
  <div class="space-y-6">
    <!-- Top Module Navigation Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-black text-lg shadow-md shadow-emerald-500/20">
          Rx
        </div>
        <div>
          <h1 class="text-base font-extrabold text-slate-900 leading-tight">Pharmacy & Dispensing System</h1>
          <p class="text-xs text-slate-500">FEFO Lot Allocation &bull; Automated Safety Verification &bull; Inventory Audits</p>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <div class="flex items-center bg-slate-100 p-1.5 rounded-xl text-xs font-semibold">
        <button
          @click="currentTab = 'dispensing'"
          :class="currentTab === 'dispensing' ? 'bg-white text-emerald-700 shadow-sm font-bold' : 'text-slate-500 hover:text-slate-800'"
          class="px-4 py-2 rounded-lg transition flex items-center gap-2 cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
          </svg>
          Prescription Dispensing
        </button>

        <button
          @click="currentTab = 'inventory'"
          :class="currentTab === 'inventory' ? 'bg-white text-emerald-700 shadow-sm font-bold' : 'text-slate-500 hover:text-slate-800'"
          class="px-4 py-2 rounded-lg transition flex items-center gap-2 cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
          </svg>
          Drug Inventory & Catalog
        </button>

        <button
          @click="currentTab = 'expiry'"
          :class="currentTab === 'expiry' ? 'bg-white text-emerald-700 shadow-sm font-bold' : 'text-slate-500 hover:text-slate-800'"
          class="px-4 py-2 rounded-lg transition flex items-center gap-2 cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          Batch & Expiry Tracker (FEFO)
        </button>
      </div>
    </div>

    <!-- Active Tab Component -->
    <DispensingStation
      v-if="currentTab === 'dispensing'"
      :branch-id="branchId"
    />

    <InventoryDashboard
      v-else-if="currentTab === 'inventory'"
      :branch-id="branchId"
      @switch-tab="tab => currentTab = tab"
    />

    <BatchExpiryTracker
      v-else-if="currentTab === 'expiry'"
      :branch-id="branchId"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import DispensingStation from './DispensingStation.vue';
import InventoryDashboard from './InventoryDashboard.vue';
import BatchExpiryTracker from './BatchExpiryTracker.vue';

const props = defineProps({
  branchId: {
    type: String,
    required: true,
  },
});

const currentTab = ref('dispensing');
</script>
