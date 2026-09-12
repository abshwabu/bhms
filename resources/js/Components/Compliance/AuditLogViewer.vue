<template>
  <div class="space-y-6">
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-xs text-slate-500 font-medium">Total Audit Entries</div>
        <div class="text-2xl font-black text-slate-900 mt-1">{{ stats.total_logs ?? 0 }}</div>
        <div class="text-[10px] text-emerald-600 font-semibold mt-0.5">Immutable storage</div>
      </div>

      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-xs text-slate-500 font-medium">Today's Transactions</div>
        <div class="text-2xl font-black text-blue-600 mt-1">{{ stats.today_logs ?? 0 }}</div>
        <div class="text-[10px] text-slate-400 font-medium mt-0.5">Real-time captured</div>
      </div>

      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-xs text-slate-500 font-medium">Past 7 Days Records</div>
        <div class="text-2xl font-black text-indigo-600 mt-1">{{ stats.past_7_days_logs ?? 0 }}</div>
        <div class="text-[10px] text-slate-400 font-medium mt-0.5">Weekly velocity</div>
      </div>

      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-xs text-slate-500 font-medium">Audited Domain Models</div>
        <div class="text-2xl font-black text-purple-600 mt-1">{{ (stats.by_model || []).length }} Active</div>
        <div class="text-[10px] text-purple-600 font-medium mt-0.5">Patients, Rx, Consents, Bills</div>
      </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div class="flex-1 flex flex-wrap items-center gap-3">
        <div class="relative min-w-[240px] flex-1">
          <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            v-model="filters.search"
            @input="debounceSearch"
            type="text"
            placeholder="Search by user, event, or IP..."
            class="w-full pl-9 pr-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
          />
        </div>

        <select
          v-model="filters.event"
          @change="loadLogs"
          class="px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white text-slate-700 focus:ring-2 focus:ring-blue-500 focus:outline-none"
        >
          <option value="">All Lifecycle Events</option>
          <option value="created">Created</option>
          <option value="updated">Updated</option>
          <option value="deleted">Deleted</option>
        </select>
      </div>

      <button
        @click="loadLogs"
        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        <span>Refresh</span>
      </button>
    </div>

    <!-- Audit Logs Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200/80 uppercase tracking-wider text-[10px]">
            <tr>
              <th class="p-3.5">Timestamp</th>
              <th class="p-3.5">Actor (User)</th>
              <th class="p-3.5">Action</th>
              <th class="p-3.5">Target Entity</th>
              <th class="p-3.5">Client IP</th>
              <th class="p-3.5 text-right">State Diff</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="loading" class="text-center">
              <td colspan="6" class="p-8 text-slate-400">Loading audit records...</td>
            </tr>
            <tr v-else-if="!logs.length" class="text-center">
              <td colspan="6" class="p-8 text-slate-400">No audit logs matching current criteria.</td>
            </tr>
            <tr
              v-for="log in logs"
              :key="log.id"
              class="hover:bg-slate-50/80 transition"
            >
              <td class="p-3.5 font-mono text-slate-600 whitespace-nowrap">
                {{ formatDateTime(log.created_at) }}
              </td>
              <td class="p-3.5">
                <div class="font-bold text-slate-800">{{ log.user ? log.user.name : 'System Automation' }}</div>
                <div class="text-[10px] text-slate-400 font-mono">{{ log.user ? log.user.email : 'CRON / Observer' }}</div>
              </td>
              <td class="p-3.5">
                <span
                  :class="{
                    'bg-emerald-100 text-emerald-800 border-emerald-300': log.event === 'created',
                    'bg-amber-100 text-amber-800 border-amber-300': log.event === 'updated',
                    'bg-rose-100 text-rose-800 border-rose-300': log.event === 'deleted'
                  }"
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border font-mono"
                >
                  {{ log.event }}
                </span>
              </td>
              <td class="p-3.5 font-mono">
                <div class="font-bold text-slate-700">{{ getModelBasename(log.auditable_type) }}</div>
                <div class="text-[10px] text-slate-400 truncate max-w-[140px]">{{ log.auditable_id }}</div>
              </td>
              <td class="p-3.5 font-mono text-slate-500">
                {{ log.ip_address || '127.0.0.1' }}
              </td>
              <td class="p-3.5 text-right">
                <button
                  @click="inspectLog(log)"
                  class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-semibold transition cursor-pointer"
                >
                  View Diff
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Before / After Diff Inspection Modal -->
    <div v-if="selectedLog" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-4xl w-full max-h-[90vh] flex flex-col shadow-2xl overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
          <div>
            <h3 class="text-base font-black text-slate-900">
              Audit Record Inspection: {{ getModelBasename(selectedLog.auditable_type) }} ({{ selectedLog.event }})
            </h3>
            <div class="text-xs text-slate-500 font-mono mt-0.5">
              ID: {{ selectedLog.id }} | Time: {{ formatDateTime(selectedLog.created_at) }}
            </div>
          </div>
          <button @click="selectedLog = null" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-6 overflow-y-auto grid grid-cols-1 md:grid-cols-2 gap-4 flex-1">
          <!-- Before State (Old Values) -->
          <div class="space-y-2">
            <div class="text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
              <span>Before State (Old Values)</span>
            </div>
            <pre class="bg-slate-900 text-slate-200 p-4 rounded-2xl text-[11px] font-mono overflow-x-auto max-h-96">{{ JSON.stringify(selectedLog.old_values || {}, null, 2) }}</pre>
          </div>

          <!-- After State (New Values) -->
          <div class="space-y-2">
            <div class="text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
              <span>After State (New Values)</span>
            </div>
            <pre class="bg-slate-900 text-slate-200 p-4 rounded-2xl text-[11px] font-mono overflow-x-auto max-h-96">{{ JSON.stringify(selectedLog.new_values || {}, null, 2) }}</pre>
          </div>
        </div>

        <div class="p-4 border-t border-slate-100 flex justify-end bg-slate-50">
          <button
            @click="selectedLog = null"
            class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-bold transition cursor-pointer"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const logs = ref([]);
const stats = ref({});
const loading = ref(false);
const selectedLog = ref(null);

const filters = ref({
  search: '',
  event: '',
});

let searchTimeout = null;

function formatDateTime(dt) {
  if (!dt) return '—';
  return new Date(dt).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
}

function getModelBasename(type) {
  if (!type) return 'Resource';
  const parts = type.split('\\');
  return parts[parts.length - 1];
}

function debounceSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    loadLogs();
  }, 350);
}

async function loadStats() {
  try {
    const res = await fetch('/api/v1/compliance/audit-logs/stats', {
      headers: { 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.success) {
      stats.value = json.data;
    }
  } catch (err) {
    console.error('Failed to load audit stats:', err);
  }
}

async function loadLogs() {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (filters.value.search) params.append('search', filters.value.search);
    if (filters.value.event) params.append('event', filters.value.event);

    const res = await fetch(`/api/v1/compliance/audit-logs?${params.toString()}`, {
      headers: { 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.success) {
      logs.value = json.data;
    }
  } catch (err) {
    console.error('Failed to load audit logs:', err);
  } finally {
    loading.value = false;
  }
}

function inspectLog(log) {
  selectedLog.value = log;
}

onMounted(() => {
  loadStats();
  loadLogs();
});
</script>
