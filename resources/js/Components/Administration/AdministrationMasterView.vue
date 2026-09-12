<template>
  <div class="space-y-6">
    <!-- Sub-Navigation Bar -->
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

      <!-- Trust Badge -->
      <div class="px-3.5 py-1.5 bg-blue-50 border border-blue-200 rounded-xl text-xs font-mono font-semibold text-blue-800 flex items-center gap-2">
        <span class="w-2.5 h-2.5 rounded-full bg-blue-500 animate-pulse"></span>
        <span>ENTERPRISE SYSTEM ADMINISTRATION</span>
      </div>
    </div>

    <!-- Active View -->
    <div>
      <BranchManagementView
        v-if="currentTab === 'branches'"
      />

      <MasterDataEditorView
        v-else-if="currentTab === 'master_data'"
        :branch-id="branchId"
      />

      <NotificationTemplateManager
        v-else-if="currentTab === 'notifications'"
      />

      <DisasterRecoveryView
        v-else-if="currentTab === 'backups'"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import BranchManagementView from './BranchManagementView.vue';
import MasterDataEditorView from './MasterDataEditorView.vue';
import NotificationTemplateManager from './NotificationTemplateManager.vue';
import DisasterRecoveryView from './DisasterRecoveryView.vue';

const props = defineProps({
  branchId: {
    type: String,
    default: null,
  }
});

const currentTab = ref('branches');

const tabs = [
  {
    id: 'branches',
    label: 'Hospital Facilities & Branches',
    icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
  },
  {
    id: 'master_data',
    label: 'Master Data & Services',
    icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
  },
  {
    id: 'notifications',
    label: 'Notification Engine (SMS/Email/Push)',
    icon: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
  },
  {
    id: 'backups',
    label: 'Disaster Recovery & Backups',
    icon: 'M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7zM8 12h8m-8 4h5',
  },
];
</script>
