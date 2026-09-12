<template>
  <div class="space-y-6">
    <!-- Action Header -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h3 class="text-lg font-black text-slate-800">Role-Based Access Control (RBAC)</h3>
        <p class="text-xs text-slate-500">Manage organizational security roles and configure granular per-module permissions.</p>
      </div>

      <div class="flex items-center gap-3">
        <button
          @click="openCreateRoleModal"
          class="px-4 py-2.5 bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer shadow-sm shadow-blue-600/20"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span>Create Security Role</span>
        </button>
      </div>
    </div>

    <!-- Roles Grid / Table -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="role in roles"
        :key="role.id"
        class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between"
      >
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="w-3 h-3 rounded-full" :class="isSystemRole(role.name) ? 'bg-indigo-500' : 'bg-emerald-500'"></span>
              <h4 class="text-base font-bold text-slate-900 capitalize">{{ role.name.replace(/_/g, ' ') }}</h4>
            </div>
            <span v-if="isSystemRole(role.name)" class="px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded text-[10px] font-bold uppercase tracking-wider">
              System Protected
            </span>
          </div>

          <div class="text-xs text-slate-500">
            Assigned Permissions: <strong class="text-slate-800">{{ role.permissions ? role.permissions.length : 0 }}</strong>
          </div>

          <!-- Permissions Badges Preview -->
          <div class="flex flex-wrap gap-1.5 max-h-24 overflow-y-auto pr-1">
            <span
              v-for="perm in (role.permissions || []).slice(0, 6)"
              :key="perm.id"
              class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded-md text-[10px] font-mono"
            >
              {{ perm.name }}
            </span>
            <span v-if="role.permissions && role.permissions.length > 6" class="px-1.5 py-0.5 text-[10px] text-slate-400 font-bold">
              +{{ role.permissions.length - 6 }} more
            </span>
          </div>
        </div>

        <div class="pt-4 mt-4 border-t border-slate-100 flex items-center justify-end gap-2">
          <button
            @click="editRole(role)"
            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition cursor-pointer"
          >
            Edit Permissions
          </button>
          <button
            v-if="!isSystemRole(role.name)"
            @click="deleteRole(role)"
            class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-xs font-semibold transition cursor-pointer"
          >
            Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Role Create / Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[90vh] flex flex-col shadow-2xl overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
          <h3 class="text-lg font-black text-slate-900">
            {{ editingRole ? `Edit Role: ${editingRole.name}` : 'Create Security Role' }}
          </h3>
          <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-6 overflow-y-auto space-y-5 flex-1">
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Role Name</label>
            <input
              v-model="form.name"
              :disabled="editingRole && isSystemRole(editingRole.name)"
              type="text"
              placeholder="e.g. lead_pharmacist"
              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
            />
          </div>

          <!-- Granular Permissions by Category -->
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Module Permissions</label>
              <button @click="toggleSelectAllPermissions" class="text-xs text-blue-600 font-bold hover:underline cursor-pointer">
                {{ form.permissions.length === allPermsList.length ? 'Deselect All' : 'Select All' }}
              </button>
            </div>

            <div v-for="(perms, moduleName) in groupedPermissions" :key="moduleName" class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/60 space-y-2">
              <div class="text-xs font-mono font-bold text-indigo-900 uppercase tracking-wider">
                {{ moduleName }} Module
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <label
                  v-for="perm in perms"
                  :key="perm.id"
                  class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer select-none"
                >
                  <input
                    type="checkbox"
                    :value="perm.name"
                    v-model="form.permissions"
                    class="rounded text-blue-600 focus:ring-blue-500"
                  />
                  <span>{{ perm.name }}</span>
                </label>
              </div>
            </div>
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
            @click="saveRole"
            :disabled="saving"
            class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer shadow-md disabled:opacity-50"
          >
            <span>{{ saving ? 'Saving...' : 'Save Role & Permissions' }}</span>
          </button>
        </div>
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
  }
});

const roles = ref([]);
const groupedPermissions = ref({});
const allPermsList = ref([]);
const showModal = ref(false);
const editingRole = ref(null);
const saving = ref(false);

const form = ref({
  name: '',
  permissions: [],
});

function isSystemRole(name) {
  return ['admin', 'super_admin', 'doctor', 'nurse', 'pharmacist', 'billing_officer', 'receptionist'].includes(name);
}

async function loadRoles() {
  try {
    const res = await fetch('/api/v1/compliance/roles', {
      headers: { 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.success) {
      roles.value = json.data;
    }
  } catch (err) {
    console.error('Failed to load roles:', err);
  }
}

async function loadPermissions() {
  try {
    const res = await fetch('/api/v1/compliance/permissions', {
      headers: { 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.success && json.data) {
      groupedPermissions.value = json.data.grouped || {};
      allPermsList.value = json.data.list || [];
    }
  } catch (err) {
    console.error('Failed to load permissions:', err);
  }
}

function openCreateRoleModal() {
  editingRole.value = null;
  form.value = {
    name: '',
    permissions: [],
  };
  showModal.value = true;
}

function editRole(role) {
  editingRole.value = role;
  form.value = {
    name: role.name,
    permissions: role.permissions ? role.permissions.map(p => p.name) : [],
  };
  showModal.value = true;
}

function toggleSelectAllPermissions() {
  if (form.value.permissions.length === allPermsList.value.length) {
    form.value.permissions = [];
  } else {
    form.value.permissions = allPermsList.value.map(p => p.name);
  }
}

async function saveRole() {
  if (!form.value.name) return;
  saving.value = true;

  try {
    const url = editingRole.value
      ? `/api/v1/compliance/roles/${editingRole.value.id}`
      : '/api/v1/compliance/roles';
    const method = editingRole.value ? 'PUT' : 'POST';

    const payload = {
      name: form.value.name,
      permissions: form.value.permissions,
      branch_id: props.branchId,
    };

    const res = await fetch(url, {
      method,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify(payload)
    });

    const json = await res.json();
    if (json.success) {
      showModal.value = false;
      await loadRoles();
    }
  } catch (err) {
    console.error('Failed to save role:', err);
  } finally {
    saving.value = false;
  }
}

async function deleteRole(role) {
  if (!confirm(`Are you sure you want to delete role "${role.name}"?`)) return;

  try {
    const res = await fetch(`/api/v1/compliance/roles/${role.id}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      }
    });
    const json = await res.json();
    if (json.success) {
      await loadRoles();
    }
  } catch (err) {
    console.error('Failed to delete role:', err);
  }
}

onMounted(() => {
  loadRoles();
  loadPermissions();
});
</script>
