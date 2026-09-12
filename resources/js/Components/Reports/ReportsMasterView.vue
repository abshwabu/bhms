<template>
  <div class="space-y-6">
    <!-- Sub-navigation Bar for Reports & Analytics -->
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

      <!-- SLA Indicator Badge -->
      <div class="px-3.5 py-1.5 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-mono font-semibold text-emerald-800 flex items-center gap-2">
        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
        <span>PRE-AGGREGATION ACTIVE (&lt; 2s SLA)</span>
      </div>
    </div>

    <!-- Active Tab Component -->
    <div>
      <AdminKpiDashboard
        v-if="currentTab === 'dashboard'"
        :branch-id="branchId"
      />

      <DepartmentReportView
        v-else-if="currentTab === 'departments'"
        :branch-id="branchId"
      />

      <DoctorPerformanceView
        v-else-if="currentTab === 'doctors'"
        :branch-id="branchId"
      />

      <CustomReportBuilderView
        v-else-if="currentTab === 'builder'"
        :branch-id="branchId"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import AdminKpiDashboard from './AdminKpiDashboard.vue';
import DepartmentReportView from './DepartmentReportView.vue';
import DoctorPerformanceView from './DoctorPerformanceView.vue';
import CustomReportBuilderView from './CustomReportBuilderView.vue';

const props = defineProps({
  branchId: {
    type: String,
    default: null,
  },
});

const currentTab = ref('dashboard');

const tabs = [
  {
    id: 'dashboard',
    label: 'Executive KPI Dashboard',
    icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
  },
  {
    id: 'departments',
    label: 'Department Analytics',
    icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
  },
  {
    id: 'doctors',
    label: 'Physician Performance',
    icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
  },
  {
    id: 'builder',
    label: 'Custom Report Builder',
    icon: 'M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
  },
];
</script>
