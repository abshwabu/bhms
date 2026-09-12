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

          <!-- Compliance Alert Badge for Staff / Credentials tab -->
          <span
            v-if="tab.id === 'directory' && alertCount > 0"
            class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-black bg-rose-600 text-white"
          >
            {{ alertCount }}
          </span>
        </button>
      </div>

      <!-- Quick Status Indicator -->
      <div class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-[11px] font-mono text-slate-500 hidden sm:flex items-center gap-1.5">
        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
        <span>HR Division: Clinical Governance</span>
      </div>
    </div>

    <!-- Active Tab Component -->
    <div>
      <RosterCalendarView
        v-if="currentTab === 'roster'"
        :branch-id="branchId"
      />

      <AttendanceDashboard
        v-else-if="currentTab === 'attendance'"
        :branch-id="branchId"
      />

      <LeaveWorkflowView
        v-else-if="currentTab === 'leave'"
        :branch-id="branchId"
      />

      <StaffDirectoryView
        v-else-if="currentTab === 'directory'"
        :branch-id="branchId"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import RosterCalendarView from './RosterCalendarView.vue';
import AttendanceDashboard from './AttendanceDashboard.vue';
import LeaveWorkflowView from './LeaveWorkflowView.vue';
import StaffDirectoryView from './StaffDirectoryView.vue';

const props = defineProps({
  branchId: {
    type: String,
    default: null,
  },
});

const currentTab = ref('roster');
const alertCount = ref(0);

const tabs = [
  {
    id: 'roster',
    label: 'Duty Roster & Scheduling',
    icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
  },
  {
    id: 'attendance',
    label: 'Attendance & Punch Station',
    icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
  },
  {
    id: 'leave',
    label: 'Leave & Quotas',
    icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
  },
  {
    id: 'directory',
    label: 'Staff & Medical Credentials',
    icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
  },
];

const checkAlertCount = async () => {
  try {
    const res = await fetch('/api/v1/hr/credentials/alerts?upcoming_days=30', {
      headers: {
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
    });
    const json = await res.json();
    if (json.success && json.data) {
      alertCount.value = json.data.total_alerts_count || 0;
    }
  } catch (err) {
    console.error('Failed to query compliance alerts', err);
  }
};

onMounted(() => {
  checkAlertCount();
});
</script>
