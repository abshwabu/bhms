<template>
  <div class="space-y-6">
    <!-- Top Action Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200/80">
      <div>
        <h1 class="text-2xl font-black text-slate-800 tracking-tight">Patient Registry & Search</h1>
        <p class="text-sm text-slate-500 mt-1">Search through hospital clinical archives by MRN, Name, Phone, or National ID.</p>
      </div>
      <div class="flex items-center gap-3">
        <button
          @click="$emit('openRegistration')"
          class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl shadow-sm shadow-blue-500/20 transition cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
          </svg>
          Register New Patient
        </button>
      </div>
    </div>

    <!-- Search Box & Filter Bar -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200/80 space-y-4">
      <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
        <input
          v-model="searchTerm"
          @input="handleSearchInput"
          type="text"
          placeholder="Search by Patient Name (e.g. John Doe), MRN (e.g. MRN-2026-MAIN-000001), Phone (+1...), or National ID..."
          class="w-full pl-11 pr-32 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
        />
        <!-- Query Latency Metric Indicator (<500ms Acceptance Verification) -->
        <div class="absolute inset-y-0 right-0 pr-4 flex items-center gap-2 pointer-events-none">
          <span v-if="searchLatencyMs !== null" class="text-xs font-mono font-medium px-2 py-0.5 rounded-md" :class="searchLatencyMs < 500 ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
            ⚡ {{ searchLatencyMs }}ms
          </span>
          <span v-if="isLoading" class="w-4 h-4 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></span>
        </div>
      </div>

      <!-- Quick Filter Pills -->
      <div class="flex flex-wrap items-center gap-2 pt-1">
        <span class="text-xs font-semibold text-slate-400 uppercase mr-1">Filter Intake:</span>
        <button
          v-for="filter in filterOptions"
          :key="filter.value"
          @click="selectFilter(filter.value)"
          :class="activeFilter === filter.value ? 'bg-slate-900 text-white font-semibold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
          class="text-xs px-3 py-1.5 rounded-lg transition cursor-pointer"
        >
          {{ filter.label }}
        </button>
      </div>
    </div>

    <!-- Patients Results Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
      <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">
          Matching Patient Records ({{ totalCount }})
        </h3>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
          <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
            <tr>
              <th class="py-3.5 px-6">Medical Record Number</th>
              <th class="py-3.5 px-6">Patient Name</th>
              <th class="py-3.5 px-6">Age / Gender</th>
              <th class="py-3.5 px-6">Blood Group</th>
              <th class="py-3.5 px-6">Intake Type</th>
              <th class="py-3.5 px-6">Contact / National ID</th>
              <th class="py-3.5 px-6 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-if="patients.length === 0 && !isLoading" class="text-center py-12">
              <td colspan="7" class="py-12 text-slate-400">
                No patients found matching your query. Try registering a new patient.
              </td>
            </tr>
            <tr
              v-for="patient in patients"
              :key="patient.id"
              class="hover:bg-blue-50/40 transition cursor-pointer"
              @click="$emit('selectPatient', patient)"
            >
              <td class="py-4 px-6 font-mono font-bold text-blue-700">
                {{ patient.mrn }}
              </td>
              <td class="py-4 px-6 font-bold text-slate-900">
                {{ patient.full_name }}
              </td>
              <td class="py-4 px-6">
                <span>{{ patient.age !== null ? patient.age + ' yrs' : 'N/A' }}</span>
                <span class="text-slate-400 text-xs capitalize ml-1.5">({{ patient.gender }})</span>
              </td>
              <td class="py-4 px-6">
                <span v-if="patient.blood_group" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                  {{ patient.blood_group }}
                </span>
                <span v-else class="text-slate-400 text-xs">Unknown</span>
              </td>
              <td class="py-4 px-6">
                <span
                  :class="{
                    'bg-blue-100 text-blue-800': patient.registration_type === 'walk_in',
                    'bg-indigo-100 text-indigo-800': patient.registration_type === 'referral',
                    'bg-red-100 text-red-800 ring-1 ring-red-300': patient.registration_type === 'emergency'
                  }"
                  class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-xs font-semibold capitalize"
                >
                  <span v-if="patient.registration_type === 'emergency'" class="w-1.5 h-1.5 rounded-full bg-red-600 animate-pulse"></span>
                  {{ patient.registration_type }}
                </span>
              </td>
              <td class="py-4 px-6 text-xs text-slate-500">
                <div>{{ patient.phone || 'No phone' }}</div>
                <div class="text-[11px] text-slate-400">{{ patient.national_id ? 'ID: ' + patient.national_id : '' }}</div>
              </td>
              <td class="py-4 px-6 text-right">
                <button
                  @click.stop="$emit('selectPatient', patient)"
                  class="text-xs font-semibold text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg transition"
                >
                  View Profile &rarr;
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  branchId: { type: String, required: true },
});

const emit = defineEmits(['openRegistration', 'selectPatient']);

const searchTerm = ref('');
const activeFilter = ref('all');
const patients = ref([]);
const totalCount = ref(0);
const isLoading = ref(false);
const searchLatencyMs = ref(null);
let searchDebounceTimer = null;

const filterOptions = [
  { label: 'All Patients', value: 'all' },
  { label: 'Walk-In', value: 'walk_in' },
  { label: 'Referral', value: 'referral' },
  { label: 'Emergency', value: 'emergency' },
];

function handleSearchInput() {
  clearTimeout(searchDebounceTimer);
  searchDebounceTimer = setTimeout(() => {
    fetchPatients();
  }, 250);
}

function selectFilter(filter) {
  activeFilter.value = filter;
  fetchPatients();
}

async function fetchPatients() {
  isLoading.value = true;
  const startTime = performance.now();

  try {
    const params = {};
    if (searchTerm.value.trim()) {
      params.search = searchTerm.value.trim();
    }
    if (activeFilter.value !== 'all') {
      params.registration_type = activeFilter.value;
    }

    const res = await axios.get('/api/v1/patients', {
      params,
      headers: {
        'X-Branch-ID': props.branchId,
        'Accept': 'application/json',
      },
    });

    patients.value = res.data.data || [];
    totalCount.value = res.data.meta?.pagination?.total || patients.value.length;
    searchLatencyMs.value = Math.round(performance.now() - startTime);
  } catch (err) {
    if (err.response?.status === 401) {
      // Handled globally by auth interceptor (hms:unauthorized redirects to clean login)
      return;
    }
    console.error('Failed to fetch patients', err);
  } finally {
    isLoading.value = false;
  }
}

onMounted(() => {
  fetchPatients();
});

defineExpose({ fetchPatients });
</script>
