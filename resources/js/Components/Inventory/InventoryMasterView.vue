<template>
  <div class="space-y-6">
    <!-- Sub-navigation Bar -->
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
        </button>
      </div>

      <!-- Quick Branch Indicator -->
      <div class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-[11px] font-mono text-slate-500 hidden sm:flex items-center gap-1.5">
        <span class="w-2 h-2 rounded-full bg-teal-500"></span>
        <span>Warehouse: Central Store</span>
      </div>
    </div>

    <!-- Active Tab Component -->
    <div>
      <InventoryDashboard
        v-if="currentTab === 'stock'"
        :branch-id="branchId"
      />

      <PurchaseOrderWorkflow
        v-else-if="currentTab === 'orders'"
        :branch-id="branchId"
      />

      <EquipmentMaintenanceCalendar
        v-else-if="currentTab === 'equipment'"
        :branch-id="branchId"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import InventoryDashboard from './InventoryDashboard.vue';
import PurchaseOrderWorkflow from './PurchaseOrderWorkflow.vue';
import EquipmentMaintenanceCalendar from './EquipmentMaintenanceCalendar.vue';

const props = defineProps({
  branchId: {
    type: String,
    default: null,
  },
});

const currentTab = ref('stock');

const tabs = [
  {
    id: 'stock',
    label: 'Medical & General Inventory',
    icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
  },
  {
    id: 'orders',
    label: 'Purchase Orders & Receiving',
    icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
  },
  {
    id: 'equipment',
    label: 'Equipment & Maintenance',
    icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
  },
];
</script>
