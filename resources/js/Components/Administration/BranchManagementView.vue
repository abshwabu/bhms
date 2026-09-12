<template>
  <div class="space-y-6">
    <!-- Action Header -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h3 class="text-lg font-black text-slate-900">Hospital Facilities & Branches</h3>
        <p class="text-xs text-slate-500">Multi-tenant facility isolation, branch settings, currency, and staff assignments.</p>
      </div>

      <div class="flex items-center gap-3">
        <button
          @click="openCreateBranchModal"
          class="px-4 py-2.5 bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer shadow-sm shadow-blue-600/20"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span>Register New Branch</span>
        </button>
      </div>
    </div>

    <!-- Branches Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="branch in branches"
        :key="branch.id"
        class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between"
      >
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="w-3 h-3 rounded-full" :class="branch.is_active ? 'bg-emerald-500' : 'bg-slate-300'"></span>
              <h4 class="text-base font-bold text-slate-900">{{ branch.name }}</h4>
            </div>
            <span v-if="branch.is_main_branch" class="px-2 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 rounded text-[10px] font-bold uppercase tracking-wider">
              Main Campus
            </span>
          </div>

          <div class="grid grid-cols-2 gap-2 text-xs text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-100">
            <div>
              <span class="text-slate-400 font-mono text-[10px] block">Branch Code:</span>
              <strong class="font-mono font-bold text-slate-800">{{ branch.code }}</strong>
            </div>
            <div>
              <span class="text-slate-400 font-mono text-[10px] block">Timezone:</span>
              <span class="font-semibold text-slate-700">{{ branch.timezone || 'UTC' }}</span>
            </div>
            <div>
              <span class="text-slate-400 font-mono text-[10px] block">Currency:</span>
              <span class="font-semibold text-slate-700">{{ branch.currency || 'USD' }}</span>
            </div>
            <div>
              <span class="text-slate-400 font-mono text-[10px] block">Contact:</span>
              <span class="truncate block text-slate-700">{{ branch.phone || branch.email || '—' }}</span>
            </div>
          </div>
        </div>

        <div class="pt-4 mt-4 border-t border-slate-100 flex items-center justify-end gap-2">
          <button
            @click="editBranch(branch)"
            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition cursor-pointer"
          >
            Configure
          </button>
          <button
            v-if="!branch.is_main_branch"
            @click="deleteBranch(branch)"
            class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-xs font-semibold transition cursor-pointer"
          >
            Deactivate
          </button>
        </div>
      </div>
    </div>

    <!-- Branch Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-xl w-full max-h-[90vh] flex flex-col shadow-2xl overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
          <h3 class="text-base font-black text-slate-900">
            {{ editingBranch ? `Edit Facility: ${editingBranch.name}` : 'Register Hospital Branch' }}
          </h3>
          <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-6 overflow-y-auto space-y-4 flex-1">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Facility Name</label>
              <input
                v-model="form.name"
                type="text"
                placeholder="e.g. Metro Memorial Campus"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Facility Code</label>
              <input
                v-model="form.code"
                :disabled="!!editingBranch"
                type="text"
                placeholder="e.g. MMC-02"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-mono uppercase focus:ring-2 focus:ring-blue-500 focus:outline-none"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Phone Number</label>
              <input
                v-model="form.phone"
                type="text"
                placeholder="+1-555-0199"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Email Address</label>
              <input
                v-model="form.email"
                type="email"
                placeholder="branch@metrohealth.org"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Timezone</label>
              <select
                v-model="form.timezone"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
              >
                <option value="UTC">UTC (Universal Time)</option>
                <option value="America/New_York">America/New_York (EST)</option>
                <option value="America/Chicago">America/Chicago (CST)</option>
                <option value="America/Los_Angeles">America/Los_Angeles (PST)</option>
                <option value="Africa/Nairobi">Africa/Nairobi (EAT)</option>
                <option value="Asia/Dubai">Asia/Dubai (GST)</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Default Currency</label>
              <input
                v-model="form.currency"
                type="text"
                placeholder="USD"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs uppercase focus:ring-2 focus:ring-blue-500 focus:outline-none"
              />
            </div>
          </div>

          <div class="flex items-center gap-4 pt-2">
            <label class="flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
              <input
                type="checkbox"
                v-model="form.is_active"
                class="rounded text-blue-600 focus:ring-blue-500"
              />
              <span>Facility is Active</span>
            </label>

            <label class="flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
              <input
                type="checkbox"
                v-model="form.is_main_branch"
                class="rounded text-amber-600 focus:ring-amber-500"
              />
              <span>Primary Group Campus</span>
            </label>
          </div>
        </div>

        <div class="p-4 border-t border-slate-100 flex items-center justify-end gap-3 bg-slate-50">
          <button
            @click="showModal = false"
            class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800 transition cursor-pointer"
          >
            Cancel
          </button>
          <button
            @click="saveBranch"
            :disabled="saving"
            class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer shadow-md disabled:opacity-50"
          >
            <span>{{ saving ? 'Saving...' : 'Save Facility Details' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const branches = ref([]);
const showModal = ref(false);
const editingBranch = ref(null);
const saving = ref(false);

const form = ref({
  name: '',
  code: '',
  phone: '',
  email: '',
  timezone: 'UTC',
  currency: 'USD',
  is_active: true,
  is_main_branch: false,
});

async function loadBranches() {
  try {
    const res = await fetch('/api/v1/admin/branches', {
      headers: { 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.success) {
      branches.value = json.data;
    }
  } catch (err) {
    console.error('Failed to load branches:', err);
  }
}

function openCreateBranchModal() {
  editingBranch.value = null;
  form.value = {
    name: '',
    code: '',
    phone: '',
    email: '',
    timezone: 'UTC',
    currency: 'USD',
    is_active: true,
    is_main_branch: false,
  };
  showModal.value = true;
}

function editBranch(b) {
  editingBranch.value = b;
  form.value = {
    name: b.name,
    code: b.code,
    phone: b.phone || '',
    email: b.email || '',
    timezone: b.timezone || 'UTC',
    currency: b.currency || 'USD',
    is_active: b.is_active,
    is_main_branch: b.is_main_branch,
  };
  showModal.value = true;
}

async function saveBranch() {
  if (!form.value.name || !form.value.code) return;
  saving.value = true;

  try {
    const url = editingBranch.value
      ? `/api/v1/admin/branches/${editingBranch.value.id}`
      : '/api/v1/admin/branches';
    const method = editingBranch.value ? 'PUT' : 'POST';

    const res = await fetch(url, {
      method,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify(form.value)
    });

    const json = await res.json();
    if (json.success) {
      showModal.value = false;
      await loadBranches();
    }
  } catch (err) {
    console.error('Failed to save branch:', err);
  } finally {
    saving.value = false;
  }
}

async function deleteBranch(b) {
  if (!confirm(`Are you sure you want to deactivate branch "${b.name}"?`)) return;

  try {
    const res = await fetch(`/api/v1/admin/branches/${b.id}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      }
    });
    const json = await res.json();
    if (json.success) {
      await loadBranches();
    }
  } catch (err) {
    console.error('Failed to delete branch:', err);
  }
}

onMounted(() => {
  loadBranches();
});
</script>
