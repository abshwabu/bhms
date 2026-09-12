<template>
  <div class="space-y-6">
    <!-- Billing Sub-Navigation Bar -->
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
        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
        <span>Branch: {{ branchId ? branchId.slice(0, 8) : 'Default Main' }}</span>
      </div>
    </div>

    <!-- Active Tab Component -->
    <div>
      <BillingCounter
        v-if="currentTab === 'counter'"
        :branch-id="branchId"
      />

      <InsuranceClaimsManager
        v-else-if="currentTab === 'claims'"
        :branch-id="branchId"
      />

      <RevenueReportsDashboard
        v-else-if="currentTab === 'reports'"
        :branch-id="branchId"
      />

      <PendingApprovalsQueue
        v-else-if="currentTab === 'approvals'"
        :branch-id="branchId"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import BillingCounter from './BillingCounter.vue';
import InsuranceClaimsManager from './InsuranceClaimsManager.vue';
import RevenueReportsDashboard from './RevenueReportsDashboard.vue';
import PendingApprovalsQueue from './PendingApprovalsQueue.vue';

const props = defineProps({
  branchId: {
    type: String,
    default: null,
  },
});

const currentTab = ref('counter');

const tabs = [
  {
    id: 'counter',
    label: 'Billing Counter & Invoices',
    icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
  },
  {
    id: 'claims',
    label: 'Insurance & Claims (TPA)',
    icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
  },
  {
    id: 'reports',
    label: 'Revenue Analytics',
    icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
  },
  {
    id: 'approvals',
    label: 'Approvals Queue',
    icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
  },
];
</script>
