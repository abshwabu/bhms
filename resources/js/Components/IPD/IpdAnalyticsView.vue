<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h2 class="text-xl font-black text-slate-900 tracking-tight">Bed Occupancy & Inpatient Analytics</h2>
        <p class="text-xs text-slate-500 mt-1">Real-time hospital bed capacity utilization, active patient census, and Average Length of Stay (ALOS).</p>
      </div>

      <button
        @click="fetchAnalytics"
        class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        Refresh Metrics
      </button>
    </div>

    <!-- 4 High-Impact KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Occupancy Rate -->
      <div class="p-6 rounded-3xl bg-white border border-slate-200 shadow-sm flex flex-col justify-between">
        <span class="text-xs font-bold text-slate-400 uppercase font-mono tracking-wider">Bed Occupancy Rate</span>
        <div class="my-3 flex items-baseline gap-2">
          <span class="text-3xl font-black text-slate-900 font-mono">{{ analytics?.occupancy_rate_percent ?? '0.0' }}%</span>
          <span class="text-xs text-emerald-600 font-bold">Optimal &lt; 85%</span>
        </div>
        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
          <div
            class="h-full rounded-full transition-all duration-500"
            :class="(analytics?.occupancy_rate_percent ?? 0) > 85 ? 'bg-rose-500' : 'bg-blue-600'"
            :style="{ width: `${analytics?.occupancy_rate_percent ?? 0}%` }"
          ></div>
        </div>
      </div>

      <!-- Inpatient Census -->
      <div class="p-6 rounded-3xl bg-white border border-slate-200 shadow-sm flex flex-col justify-between">
        <span class="text-xs font-bold text-slate-400 uppercase font-mono tracking-wider">Inpatient Census</span>
        <div class="my-3">
          <span class="text-3xl font-black text-blue-600 font-mono">{{ analytics?.current_inpatient_census ?? 0 }}</span>
          <span class="text-xs text-slate-500 ml-2">Patients Admitted</span>
        </div>
        <span class="text-[11px] text-slate-400 font-medium">Currently receiving inpatient care</span>
      </div>

      <!-- Available Beds -->
      <div class="p-6 rounded-3xl bg-white border border-slate-200 shadow-sm flex flex-col justify-between">
        <span class="text-xs font-bold text-slate-400 uppercase font-mono tracking-wider">Free Beds Available</span>
        <div class="my-3">
          <span class="text-3xl font-black text-emerald-600 font-mono">{{ analytics?.available_beds ?? 0 }}</span>
          <span class="text-xs text-slate-500 ml-2">of {{ analytics?.total_beds ?? 0 }} Total Beds</span>
        </div>
        <div class="flex items-center gap-3 text-[10px] text-slate-400">
          <span>Cleaning: {{ analytics?.cleaning_beds ?? 0 }}</span>
          <span>•</span>
          <span>Maint: {{ analytics?.maintenance_beds ?? 0 }}</span>
        </div>
      </div>

      <!-- ALOS (Average Length of Stay) -->
      <div class="p-6 rounded-3xl bg-white border border-slate-200 shadow-sm flex flex-col justify-between">
        <span class="text-xs font-bold text-slate-400 uppercase font-mono tracking-wider">Average Length of Stay</span>
        <div class="my-3">
          <span class="text-3xl font-black text-purple-600 font-mono">{{ analytics?.average_length_of_stay_days ?? '0.0' }}</span>
          <span class="text-xs text-slate-500 ml-2">Days / Patient</span>
        </div>
        <span class="text-[11px] text-slate-400 font-medium">Based on {{ analytics?.total_discharged_patients_analyzed ?? 0 }} discharges</span>
      </div>
    </div>

    <!-- Ward Breakdown Table -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
      <div class="p-6 border-b border-slate-100 flex items-center justify-between">
        <h3 class="font-bold text-slate-900 text-sm">Ward Utilization Breakdown</h3>
        <span class="text-xs text-slate-500 font-mono">Live Inpatient Capacity</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50 border-b border-slate-100 text-slate-400 uppercase text-[10px] font-bold">
            <tr>
              <th class="py-3.5 px-6">Ward Name</th>
              <th class="py-3.5 px-6">Type</th>
              <th class="py-3.5 px-6">Total Beds</th>
              <th class="py-3.5 px-6">Occupied</th>
              <th class="py-3.5 px-6">Available</th>
              <th class="py-3.5 px-6">Occupancy %</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="!analytics?.wards_breakdown?.length">
              <td colspan="6" class="py-6 text-center text-slate-400">No ward data available.</td>
            </tr>
            <tr v-for="w in analytics?.wards_breakdown" :key="w.ward_id" class="hover:bg-slate-50/50">
              <td class="py-4 px-6 font-bold text-slate-900">
                {{ w.name }}
                <span class="text-[10px] font-mono text-slate-400 ml-1">({{ w.code }})</span>
              </td>
              <td class="py-4 px-6 uppercase text-[10px] font-bold text-slate-500">{{ w.ward_type }}</td>
              <td class="py-4 px-6 font-mono font-bold">{{ w.total_beds }}</td>
              <td class="py-4 px-6 font-mono font-bold text-blue-600">{{ w.occupied_beds }}</td>
              <td class="py-4 px-6 font-mono font-bold text-emerald-600">{{ w.available_beds }}</td>
              <td class="py-4 px-6">
                <div class="flex items-center gap-3">
                  <div class="w-24 bg-slate-100 h-2 rounded-full overflow-hidden">
                    <div
                      class="h-full rounded-full"
                      :class="w.occupancy_percent > 85 ? 'bg-rose-500' : 'bg-blue-600'"
                      :style="{ width: `${w.occupancy_percent}%` }"
                    ></div>
                  </div>
                  <span class="font-mono font-bold">{{ w.occupancy_percent }}%</span>
                </div>
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

const props = defineProps({
  branchId: { type: String, required: true },
});

const analytics = ref(null);

async function fetchAnalytics() {
  try {
    const res = await fetch('/api/v1/ipd/analytics/occupancy', {
      headers: { 'X-Branch-ID': props.branchId, 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.data) {
      analytics.value = json.data;
    }
  } catch (e) {
    console.error('Fetch analytics error', e);
  }
}

onMounted(async () => {
  await fetchAnalytics();
});
</script>
