<template>
  <div class="space-y-6">
    <!-- Top Sub-Navigation Bar -->
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

      <!-- Security Trust Badge -->
      <div class="px-3.5 py-1.5 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-mono font-semibold text-emerald-800 flex items-center gap-2">
        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
        <span>HIPAA TECHNICAL SAFEGUARDS ACTIVE</span>
      </div>
    </div>

    <!-- Active Compliance View -->
    <div>
      <HipaaChecklistView
        v-if="currentTab === 'hipaa'"
        :branch-id="branchId"
      />

      <RolePermissionManager
        v-else-if="currentTab === 'rbac'"
        :branch-id="branchId"
      />

      <AuditLogViewer
        v-else-if="currentTab === 'audit'"
        :branch-id="branchId"
      />

      <ConsentLedgerView
        v-else-if="currentTab === 'consents'"
        :branch-id="branchId"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import HipaaChecklistView from './HipaaChecklistView.vue';
import RolePermissionManager from './RolePermissionManager.vue';
import AuditLogViewer from './AuditLogViewer.vue';
import ConsentLedgerView from './ConsentLedgerView.vue';

const props = defineProps({
  branchId: {
    type: String,
    default: null,
  }
});

const currentTab = ref('hipaa');

const tabs = [
  {
    id: 'hipaa',
    label: 'HIPAA Safeguards & Audit',
    icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
  },
  {
    id: 'rbac',
    label: 'RBAC Roles & Permissions',
    icon: 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z',
  },
  {
    id: 'audit',
    label: 'Immutable Audit Trail',
    icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
  },
  {
    id: 'consents',
    label: 'Patient Consent Directives',
    icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
  },
];
</script>
