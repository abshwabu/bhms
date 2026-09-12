<template>
  <div class="space-y-6">
    <!-- Top Action Bar -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2">
          <h2 class="text-lg font-bold text-slate-900">Custom Clinical & Operational Report Builder</h2>
          <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
            <svg class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            Query Cost Protected
          </span>
        </div>
        <p class="text-xs text-slate-500 mt-0.5">Define custom cross-domain queries with field selection, dynamic filters, and exact-match CSV export</p>
      </div>

      <div class="flex items-center gap-2">
        <button
          @click="openSaveModal"
          :disabled="!results.data || results.data.length === 0"
          class="px-3.5 py-2 rounded-xl border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-50 transition cursor-pointer disabled:opacity-50"
        >
          Save Template
        </button>

        <button
          @click="printReport"
          :disabled="!results.data || results.data.length === 0"
          class="px-3.5 py-2 rounded-xl border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-50 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
          </svg>
          Print / PDF
        </button>

        <button
          @click="exportCustomCsv"
          :disabled="!results.data || results.data.length === 0"
          class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-sm disabled:opacity-50"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          Export CSV (Matches View)
        </button>
      </div>
    </div>

    <!-- Configuration Builder Box -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-5">
      <!-- Row 1: Entity & Date Range -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Entity Selector -->
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
            1. Select Data Entity *
          </label>
          <select
            v-model="selectedEntity"
            @change="onEntityChange"
            class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-white text-slate-800 font-semibold focus:ring-2 focus:ring-blue-500 cursor-pointer"
          >
            <option v-for="(cfg, key) in schema.entities" :key="key" :value="key">
              {{ cfg.label }} ({{ key }})
            </option>
          </select>
        </div>

        <!-- Date Range: From -->
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
            Date Range: From
          </label>
          <input
            type="date"
            v-model="dateFrom"
            class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 text-slate-800 focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <!-- Date Range: To -->
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
            Date Range: To
          </label>
          <input
            type="date"
            v-model="dateTo"
            class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 text-slate-800 focus:ring-2 focus:ring-blue-500"
          />
        </div>
      </div>

      <!-- Row 2: Field Selection (Checkboxes / Pills) -->
      <div v-if="currentEntityFields" class="space-y-2">
        <div class="flex items-center justify-between">
          <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">
            2. Selected Columns ({{ selectedFields.length }} / 15 Max Limit)
          </label>
          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="selectAllFields"
              class="text-[11px] text-blue-600 hover:underline font-semibold cursor-pointer"
            >
              Select All
            </button>
            <span class="text-slate-300">&bull;</span>
            <button
              type="button"
              @click="clearFields"
              class="text-[11px] text-slate-500 hover:underline cursor-pointer"
            >
              Reset
            </button>
          </div>
        </div>

        <div class="flex flex-wrap gap-2 p-3 bg-slate-50 rounded-xl border border-slate-200">
          <label
            v-for="(fInfo, fKey) in currentEntityFields"
            :key="fKey"
            :class="selectedFields.includes(fKey) ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-100'"
            class="px-2.5 py-1.5 rounded-lg border text-xs font-medium cursor-pointer transition flex items-center gap-1.5"
          >
            <input
              type="checkbox"
              :value="fKey"
              v-model="selectedFields"
              class="sr-only"
            />
            <span>{{ fInfo.label }}</span>
          </label>
        </div>
      </div>

      <!-- Row 3: Dynamic Filters -->
      <div class="space-y-2">
        <div class="flex items-center justify-between">
          <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">
            3. Dynamic Filter Conditions (Max 10)
          </label>
          <button
            type="button"
            @click="addFilterRow"
            class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition flex items-center gap-1 cursor-pointer"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add Condition
          </button>
        </div>

        <div v-if="filters.length === 0" class="text-xs text-slate-400 italic p-3 bg-slate-50 rounded-xl border border-slate-200">
          No active filter conditions. All records within the date boundary will be queried.
        </div>

        <div v-else class="space-y-2">
          <div
            v-for="(fil, idx) in filters"
            :key="idx"
            class="flex flex-wrap items-center gap-2 p-2 bg-slate-50 rounded-xl border border-slate-200"
          >
            <!-- Field Dropdown -->
            <select
              v-model="fil.field"
              class="px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-white font-medium text-slate-800"
            >
              <option v-for="(fInfo, fKey) in currentEntityFields" :key="fKey" :value="fKey">
                {{ fInfo.label }}
              </option>
            </select>

            <!-- Operator Dropdown -->
            <select
              v-model="fil.operator"
              class="px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-white font-semibold text-slate-800"
            >
              <option v-for="(opLabel, opKey) in schema.operators" :key="opKey" :value="opKey">
                {{ opLabel }}
              </option>
            </select>

            <!-- Value Input -->
            <input
              type="text"
              v-model="fil.value"
              placeholder="Value to compare..."
              class="flex-1 min-w-[150px] px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-white text-slate-800"
            />

            <!-- Remove Button -->
            <button
              type="button"
              @click="removeFilterRow(idx)"
              class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition cursor-pointer"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Execution & Guardrail Bar -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-slate-100">
        <div class="flex items-center gap-2 text-xs text-slate-500">
          <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
          <span>Max Bound Limit: <strong>1,000 Rows</strong> &bull; Protected Parameterized Execution</span>
        </div>

        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="runQuery(1)"
            :disabled="executing"
            class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition flex items-center gap-2 cursor-pointer shadow-md disabled:opacity-50"
          >
            <svg v-if="executing" class="w-4 h-4 animate-spin text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span>Run Custom Query</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Error Alert -->
    <div v-if="errorMessage" class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-800 font-semibold flex items-center gap-2">
      <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <span>{{ errorMessage }}</span>
    </div>

    <!-- Query Results Preview Table -->
    <div v-if="results.data" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden" id="printable-report-area">
      <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div class="flex items-center gap-2">
          <h3 class="font-bold text-slate-900 text-sm">
            Query Results: {{ results.entity_label }}
          </h3>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
            {{ results.meta?.total_records || 0 }} Matching Records
          </span>
          <span v-if="results.meta?.limit_applied" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
            Capped at {{ results.meta?.hard_cost_limit }}
          </span>
        </div>

        <div class="text-xs font-mono text-slate-400">
          Query Time: <strong class="text-slate-700">{{ results.meta?.query_duration_ms }} ms</strong>
        </div>
      </div>

      <div v-if="results.data.length === 0" class="p-12 text-center text-slate-400 text-xs">
        No records found matching the configured filters and date range.
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50 text-slate-700 uppercase font-mono text-[11px] border-b border-slate-200">
            <tr>
              <th
                v-for="col in results.columns"
                :key="col"
                class="py-3 px-4 font-bold whitespace-nowrap"
              >
                {{ results.column_definitions?.[col]?.label || col }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="(row, rIdx) in results.data"
              :key="rIdx"
              class="hover:bg-slate-50/70 transition"
            >
              <td
                v-for="col in results.columns"
                :key="col"
                class="py-3 px-4 whitespace-nowrap font-mono"
              >
                {{ formatTableCell(row[col] ?? row, col) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div v-if="results.meta && results.meta.total_pages > 1" class="p-3.5 border-t border-slate-100 bg-slate-50 flex items-center justify-between text-xs text-slate-600">
        <div>
          Showing page <strong>{{ results.meta.current_page }}</strong> of <strong>{{ results.meta.total_pages }}</strong>
        </div>
        <div class="flex items-center gap-2">
          <button
            :disabled="results.meta.current_page <= 1"
            @click="runQuery(results.meta.current_page - 1)"
            class="px-3 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 disabled:opacity-50 cursor-pointer"
          >
            &larr; Prev
          </button>
          <button
            :disabled="results.meta.current_page >= results.meta.total_pages"
            @click="runQuery(results.meta.current_page + 1)"
            class="px-3 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 disabled:opacity-50 cursor-pointer"
          >
            Next &rarr;
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Save Custom Report Template -->
    <div
      v-if="isSaveModalOpen"
      class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="font-bold text-slate-900 text-base">Save Custom Report Template</h3>
          <button @click="isSaveModalOpen = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="saveTemplate" class="space-y-3">
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Template Name *</label>
            <input
              type="text"
              required
              v-model="templateName"
              placeholder="e.g. Monthly IPD Invoiced Unpaid Balances"
              class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Description (Optional)</label>
            <textarea
              rows="2"
              v-model="templateDescription"
              placeholder="Clinical or finance audit notes..."
              class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500"
            ></textarea>
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
            <button
              type="button"
              @click="isSaveModalOpen = false"
              class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 cursor-pointer"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md cursor-pointer"
            >
              Save Template
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
  branchId: {
    type: String,
    default: null,
  },
});

const schema = ref({ entities: {}, operators: {} });
const selectedEntity = ref('invoices');
const selectedFields = ref(['invoice_number', 'billing_type', 'status', 'total_cents', 'paid_cents', 'created_at']);
const dateFrom = ref('');
const dateTo = ref('');
const filters = ref([]);
const executing = ref(false);
const errorMessage = ref('');
const results = ref({});

// Modal
const isSaveModalOpen = ref(false);
const templateName = ref('');
const templateDescription = ref('');

const currentEntityFields = computed(() => {
  return schema.value.entities?.[selectedEntity.value]?.fields || {};
});

function onEntityChange() {
  const fields = Object.keys(currentEntityFields.value);
  selectedFields.value = fields.slice(0, 6);
  filters.value = [];
  results.value = {};
}

function selectAllFields() {
  selectedFields.value = Object.keys(currentEntityFields.value).slice(0, 15);
}

function clearFields() {
  selectedFields.value = Object.keys(currentEntityFields.value).slice(0, 3);
}

function addFilterRow() {
  if (filters.value.length >= 10) return;
  const firstField = Object.keys(currentEntityFields.value)[0] || '';
  filters.value.push({
    field: firstField,
    operator: '=',
    value: '',
  });
}

function removeFilterRow(idx) {
  filters.value.splice(idx, 1);
}

function formatTableCell(val, col) {
  if (val === null || val === undefined) return '-';
  if (typeof val === 'object') return JSON.stringify(val);
  if (strEndsWith(col, '_cents') && !isNaN(val)) {
    return '$' + (Number(val) / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
  return String(val);
}

function strEndsWith(str, suffix) {
  return typeof str === 'string' && str.endsWith(suffix);
}

async function fetchSchema() {
  try {
    const res = await fetch('/api/v1/reports/builder/schema');
    const json = await res.json();
    if (json.success && json.data) {
      schema.value = json.data;
      if (schema.value.entities?.[selectedEntity.value]) {
        onEntityChange();
      }
    }
  } catch (err) {
    console.error('Failed to load schema:', err);
  }
}

async function runQuery(page = 1) {
  executing.value = true;
  errorMessage.value = '';

  const payload = {
    entity: selectedEntity.value,
    branch_id: props.branchId,
    fields: selectedFields.value,
    filters: filters.value.filter(f => f.field && f.operator),
    date_from: dateFrom.value || null,
    date_to: dateTo.value || null,
    page: page,
    per_page: 25,
  };

  try {
    const res = await fetch('/api/v1/reports/builder/query', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
      body: JSON.stringify(payload),
    });

    const json = await res.json();
    if (json.success && json.data) {
      results.value = json.data;
    } else {
      errorMessage.value = json.message || 'Error executing query.';
    }
  } catch (err) {
    errorMessage.value = 'Failed to communicate with report builder service.';
  } finally {
    executing.value = false;
  }
}

function exportCustomCsv() {
  const payload = {
    entity: selectedEntity.value,
    branch_id: props.branchId,
    fields: selectedFields.value,
    filters: filters.value.filter(f => f.field && f.operator),
    date_from: dateFrom.value || null,
    date_to: dateTo.value || null,
  };

  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '/api/v1/reports/export/custom';
  form.target = '_blank';

  // Append fields as inputs
  for (const [key, value] of Object.entries(payload)) {
    if (value !== null && value !== undefined) {
      if (Array.isArray(value)) {
        value.forEach((v, i) => {
          if (typeof v === 'object') {
            for (const [subK, subV] of Object.entries(v)) {
              const inp = document.createElement('input');
              inp.type = 'hidden';
              inp.name = `${key}[${i}][${subK}]`;
              inp.value = subV;
              form.appendChild(inp);
            }
          } else {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = `${key}[]`;
            inp.value = v;
            form.appendChild(inp);
          }
        });
      } else {
        const inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = key;
        inp.value = value;
        form.appendChild(inp);
      }
    }
  }

  document.body.appendChild(form);
  form.submit();
  document.body.removeChild(form);
}

function printReport() {
  window.print();
}

function openSaveModal() {
  templateName.value = `${schema.value.entities?.[selectedEntity.value]?.label || selectedEntity.value} Custom Report`;
  templateDescription.value = '';
  isSaveModalOpen.value = true;
}

async function saveTemplate() {
  try {
    const res = await fetch('/api/v1/reports/builder/saved', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
      body: JSON.stringify({
        name: templateName.value,
        description: templateDescription.value,
        entity: selectedEntity.value,
        branch_id: props.branchId,
        selected_fields: selectedFields.value,
        filters: filters.value,
      }),
    });
    const json = await res.json();
    if (json.success) {
      alert(`Report template '${templateName.value}' saved successfully.`);
      isSaveModalOpen.value = false;
    }
  } catch (err) {
    console.error('Failed to save template:', err);
  }
}

onMounted(() => {
  fetchSchema();
});
</script>
