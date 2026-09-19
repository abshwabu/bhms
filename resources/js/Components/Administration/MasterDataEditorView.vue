<template>
  <div class="space-y-6">
    <!-- Sub-Tab Header -->
    <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-2">
        <button
          v-for="tab in ['services', 'departments', 'pricelists']"
          :key="tab"
          @click="activeSubTab = tab"
          :class="activeSubTab === tab ? 'bg-slate-900 text-white font-bold' : 'text-slate-600 hover:bg-slate-100'"
          class="px-4 py-2 rounded-xl text-xs transition cursor-pointer capitalize"
        >
          {{ tab === 'services' ? 'Services Catalog' : (tab === 'departments' ? 'Clinical Departments' : 'Price Lists & Packages') }}
        </button>
      </div>

      <button
        @click="openCreateModal"
        class="px-4 py-2 bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-sm shadow-blue-600/20"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Add {{ activeSubTab === 'services' ? 'Service' : (activeSubTab === 'departments' ? 'Department' : 'Price Item') }}</span>
      </button>
    </div>

    <!-- 1. Services Catalog Table -->
    <div v-if="activeSubTab === 'services'" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200/80 uppercase tracking-wider text-[10px]">
            <tr>
              <th class="p-3.5">Code</th>
              <th class="p-3.5">Service Name</th>
              <th class="p-3.5">Category</th>
              <th class="p-3.5">Department</th>
              <th class="p-3.5">Duration</th>
              <th class="p-3.5">Base Price</th>
              <th class="p-3.5">Status</th>
              <th class="p-3.5 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="!services.length" class="text-center">
              <td colspan="8" class="p-8 text-slate-400">No services cataloged for this facility.</td>
            </tr>
            <tr v-for="srv in services" :key="srv.id" class="hover:bg-slate-50/80 transition">
              <td class="p-3.5 font-mono font-bold text-slate-700">{{ srv.code }}</td>
              <td class="p-3.5">
                <div class="font-bold text-slate-900">{{ srv.name }}</div>
                <div class="text-[10px] text-slate-400 truncate max-w-[200px]">{{ srv.description || 'No description' }}</div>
              </td>
              <td class="p-3.5 capitalize">
                <span class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded text-[10px] font-bold font-mono">
                  {{ srv.category }}
                </span>
              </td>
              <td class="p-3.5 text-slate-600">{{ srv.department ? srv.department.name : 'General Hospital' }}</td>
              <td class="p-3.5 font-mono text-slate-600">{{ srv.duration_minutes }} mins</td>
              <td class="p-3.5 font-mono font-bold text-slate-900">${{ (srv.base_price_cents / 100).toFixed(2) }}</td>
              <td class="p-3.5">
                <span :class="srv.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'" class="px-2 py-0.5 rounded text-[10px] font-bold">
                  {{ srv.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="p-3.5 text-right">
                <button @click="deleteItem('services', srv.id)" class="text-rose-600 hover:text-rose-800 font-bold text-xs cursor-pointer">
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- 2. Departments Table -->
    <div v-else-if="activeSubTab === 'departments'" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200/80 uppercase tracking-wider text-[10px]">
            <tr>
              <th class="p-3.5">Code</th>
              <th class="p-3.5">Department Name</th>
              <th class="p-3.5">Description</th>
              <th class="p-3.5">Status</th>
              <th class="p-3.5 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="!departments.length" class="text-center">
              <td colspan="5" class="p-8 text-slate-400">No clinical departments registered.</td>
            </tr>
            <tr v-for="dept in departments" :key="dept.id" class="hover:bg-slate-50/80 transition">
              <td class="p-3.5 font-mono font-bold text-slate-700">{{ dept.code }}</td>
              <td class="p-3.5 font-bold text-slate-900">{{ dept.name }}</td>
              <td class="p-3.5 text-slate-500">{{ dept.description || '—' }}</td>
              <td class="p-3.5">
                <span :class="dept.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'" class="px-2 py-0.5 rounded text-[10px] font-bold">
                  {{ dept.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="p-3.5 text-right">
                <button @click="deleteItem('departments', dept.id)" class="text-rose-600 hover:text-rose-800 font-bold text-xs cursor-pointer">
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- 3. Price Lists Table -->
    <div v-else-if="activeSubTab === 'pricelists'" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200/80 uppercase tracking-wider text-[10px]">
            <tr>
              <th class="p-3.5">Code</th>
              <th class="p-3.5">Item / Package Name</th>
              <th class="p-3.5">Category</th>
              <th class="p-3.5">Department</th>
              <th class="p-3.5">Unit Price</th>
              <th class="p-3.5">Package</th>
              <th class="p-3.5 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="!priceLists.length" class="text-center">
              <td colspan="7" class="p-8 text-slate-400">No price list items configured.</td>
            </tr>
            <tr v-for="prc in priceLists" :key="prc.id" class="hover:bg-slate-50/80 transition">
              <td class="p-3.5 font-mono font-bold text-slate-700">{{ prc.code }}</td>
              <td class="p-3.5 font-bold text-slate-900">{{ prc.name }}</td>
              <td class="p-3.5 capitalize text-slate-600">{{ prc.category }}</td>
              <td class="p-3.5 text-slate-600">{{ prc.department }}</td>
              <td class="p-3.5 font-mono font-bold text-slate-900">${{ (prc.unit_price_cents / 100).toFixed(2) }}</td>
              <td class="p-3.5">
                <span v-if="prc.is_package" class="px-2 py-0.5 bg-purple-50 text-purple-700 rounded text-[10px] font-bold">Package</span>
                <span v-else class="text-slate-400 text-[10px]">Single</span>
              </td>
              <td class="p-3.5 text-right">
                <button @click="deleteItem('price-lists', prc.id)" class="text-rose-600 hover:text-rose-800 font-bold text-xs cursor-pointer">
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create Item Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-lg w-full shadow-2xl overflow-hidden p-6 space-y-4">
        <h3 class="text-base font-black text-slate-900 capitalize">
          Add New {{ activeSubTab === 'services' ? 'Hospital Service' : (activeSubTab === 'departments' ? 'Department' : 'Price Item') }}
        </h3>

        <!-- Form for Services -->
        <div v-if="activeSubTab === 'services'" class="space-y-3">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Code</label>
              <input v-model="serviceForm.code" type="text" placeholder="SRV-CONS" class="w-full px-3 py-2 border rounded-xl text-xs font-mono" />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Name</label>
              <input v-model="serviceForm.name" type="text" placeholder="Specialist Consult" class="w-full px-3 py-2 border rounded-xl text-xs" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Category</label>
              <select v-model="serviceForm.category" class="w-full px-3 py-2 border rounded-xl text-xs bg-white">
                <option value="clinical">Clinical</option>
                <option value="surgical">Surgical</option>
                <option value="diagnostic">Diagnostic</option>
                <option value="nursing">Nursing</option>
                <option value="emergency">Emergency</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Base Price ($)</label>
              <input v-model="serviceForm.price" type="number" step="0.01" placeholder="50.00" class="w-full px-3 py-2 border rounded-xl text-xs font-mono" />
            </div>
          </div>
        </div>

        <!-- Form for Departments -->
        <div v-else-if="activeSubTab === 'departments'" class="space-y-3">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Dept Code</label>
              <input v-model="deptForm.code" type="text" placeholder="CARD" class="w-full px-3 py-2 border rounded-xl text-xs font-mono" />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Dept Name</label>
              <input v-model="deptForm.name" type="text" placeholder="Cardiology" class="w-full px-3 py-2 border rounded-xl text-xs" />
            </div>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Description</label>
            <textarea v-model="deptForm.description" rows="2" class="w-full px-3 py-2 border rounded-xl text-xs"></textarea>
          </div>
        </div>

        <!-- Form for Price Lists -->
        <div v-else-if="activeSubTab === 'pricelists'" class="space-y-3">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Item Code</label>
              <input v-model="priceForm.code" type="text" placeholder="PRC-CBC" class="w-full px-3 py-2 border rounded-xl text-xs font-mono" />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Item Name</label>
              <input v-model="priceForm.name" type="text" placeholder="Complete Blood Count" class="w-full px-3 py-2 border rounded-xl text-xs" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Category</label>
              <select v-model="priceForm.category" class="w-full px-3 py-2 border rounded-xl text-xs bg-white">
                <option value="procedure">Procedure</option>
                <option value="laboratory">Laboratory</option>
                <option value="radiology">Radiology</option>
                <option value="consultation">Consultation</option>
                <option value="bed">Bed & Room</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Unit Price ($)</label>
              <input v-model="priceForm.price" type="number" step="0.01" placeholder="25.00" class="w-full px-3 py-2 border rounded-xl text-xs font-mono" />
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
          <button @click="showModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800 cursor-pointer">
            Cancel
          </button>
          <button @click="saveItem" :disabled="saving" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold shadow cursor-pointer disabled:opacity-50">
            <span>{{ saving ? 'Saving...' : 'Save Record' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { showConfirm } from '../../Services/modalDialog';

const props = defineProps({
  branchId: {
    type: String,
    required: true,
  }
});

const activeSubTab = ref('services');
const services = ref([]);
const departments = ref([]);
const priceLists = ref([]);
const showModal = ref(false);
const saving = ref(false);

const serviceForm = ref({ code: '', name: '', category: 'clinical', price: 50.0 });
const deptForm = ref({ code: '', name: '', description: '' });
const priceForm = ref({ code: '', name: '', category: 'procedure', price: 25.0 });

async function loadData() {
  if (!props.branchId) return;

  try {
    const [resSrv, resDept, resPrc] = await Promise.all([
      fetch(`/api/v1/admin/branches/${props.branchId}/services`, { headers: { 'Accept': 'application/json' } }),
      fetch(`/api/v1/admin/branches/${props.branchId}/departments`, { headers: { 'Accept': 'application/json' } }),
      fetch(`/api/v1/admin/branches/${props.branchId}/price-lists`, { headers: { 'Accept': 'application/json' } }),
    ]);

    const [jsonSrv, jsonDept, jsonPrc] = await Promise.all([resSrv.json(), resDept.json(), resPrc.json()]);

    if (jsonSrv.success) services.value = jsonSrv.data;
    if (jsonDept.success) departments.value = jsonDept.data;
    if (jsonPrc.success) priceLists.value = jsonPrc.data;
  } catch (err) {
    console.error('Failed to load master data:', err);
  }
}

function openCreateModal() {
  showModal.value = true;
}

async function saveItem() {
  saving.value = true;
  try {
    let url = '';
    let payload = {};

    if (activeSubTab.value === 'services') {
      url = '/api/v1/admin/services';
      payload = {
        branch_id: props.branchId,
        code: serviceForm.value.code,
        name: serviceForm.value.name,
        category: serviceForm.value.category,
        base_price_cents: Math.round(parseFloat(serviceForm.value.price) * 100),
      };
    } else if (activeSubTab.value === 'departments') {
      url = '/api/v1/admin/departments';
      payload = {
        branch_id: props.branchId,
        code: deptForm.value.code,
        name: deptForm.value.name,
        description: deptForm.value.description,
      };
    } else if (activeSubTab.value === 'pricelists') {
      url = '/api/v1/admin/price-lists';
      payload = {
        branch_id: props.branchId,
        code: priceForm.value.code,
        name: priceForm.value.name,
        category: priceForm.value.category,
        unit_price_cents: Math.round(parseFloat(priceForm.value.price) * 100),
      };
    }

    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify(payload),
    });

    const json = await res.json();
    if (json.success) {
      showModal.value = false;
      await loadData();
    }
  } catch (err) {
    console.error('Failed to save master item:', err);
  } finally {
    saving.value = false;
  }
}

async function deleteItem(resource, id) {
  if (!await showConfirm('Are you sure you want to delete this master data item?', { isDestructive: true })) return;
  try {
    const res = await fetch(`/api/v1/admin/${resource}/${id}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      }
    });
    const json = await res.json();
    if (json.success) {
      await loadData();
    }
  } catch (err) {
    console.error('Delete failed:', err);
  }
}

watch(() => props.branchId, () => {
  loadData();
});

onMounted(() => {
  loadData();
});
</script>
