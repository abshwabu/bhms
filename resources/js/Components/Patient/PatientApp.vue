<template>
  <div class="min-h-screen bg-slate-100/70 font-sans text-slate-800 flex">
    <!-- Sidebar Navigation -->
    <aside class="w-64 bg-slate-900 text-white flex flex-col justify-between shrink-0 shadow-xl">
      <div>
        <!-- Brand Header -->
        <div class="p-6 border-b border-slate-800">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center font-black text-xl text-white shadow-lg shadow-blue-500/30">
              +
            </div>
            <div>
              <div class="font-extrabold text-base tracking-tight leading-tight">Metro HMS</div>
              <div class="text-[11px] text-blue-400 font-medium">Hospital Management</div>
            </div>
          </div>

          <!-- Active Branch Badge -->
          <div class="mt-4 p-2.5 rounded-xl bg-slate-800/80 border border-slate-700/60 flex items-center justify-between text-xs">
            <div class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
              <span class="font-medium text-slate-300">Metro General (Main)</span>
            </div>
            <span class="font-mono text-[10px] text-slate-400">MAIN</span>
          </div>
        </div>

        <!-- Navigation Links -->
        <nav class="p-4 space-y-1.5">
          <button
            @click="currentView = 'search'"
            :class="currentView === 'search' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            Patient Registry
          </button>

          <button
            @click="openRegistrationModal"
            class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-medium text-slate-400 hover:bg-slate-800 hover:text-white transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Register Patient
          </button>

          <button
            @click="currentView = 'portal'"
            :class="currentView === 'portal' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            Patient Portal View
          </button>
        </nav>
      </div>

      <!-- User Session Footer -->
      <div class="p-4 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center font-bold text-white text-xs">
            EV
          </div>
          <div>
            <div class="text-white font-medium text-xs">Dr. Eleanor Vance</div>
            <div class="text-[10px] text-blue-400 capitalize">Doctor / Clinician</div>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 p-8 max-w-7xl mx-auto overflow-y-auto">
      <!-- Search Screen -->
      <PatientSearchScreen
        v-if="currentView === 'search'"
        ref="searchScreenRef"
        :branch-id="activeBranchId"
        @open-registration="openRegistrationModal"
        @select-patient="handleSelectPatient"
      />

      <!-- Profile View -->
      <PatientProfileView
        v-else-if="currentView === 'profile' && selectedPatient"
        :patient="selectedPatient"
        :branch-id="activeBranchId"
        @back="currentView = 'search'"
      />

      <!-- Patient Portal Self-Service View -->
      <div v-else-if="currentView === 'portal'" class="space-y-6">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
          <h2 class="text-xl font-bold text-slate-900">Patient-Facing Portal Preview</h2>
          <p class="text-sm text-slate-500 mt-1">This view simulates what patients see when logging into their personal health dashboard.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm space-y-2">
            <h3 class="font-bold text-slate-800">My Appointments</h3>
            <p class="text-xs text-slate-500">Upcoming specialist consults and queue position.</p>
            <div class="pt-4 text-xs font-semibold text-blue-600">0 Active Appointments &rarr;</div>
          </div>
          <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm space-y-2">
            <h3 class="font-bold text-slate-800">My Medical Records</h3>
            <p class="text-xs text-slate-500">Clinical notes, lab results, and diagnosed allergies.</p>
            <div class="pt-4 text-xs font-semibold text-blue-600">View Diagnostic History &rarr;</div>
          </div>
          <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm space-y-2">
            <h3 class="font-bold text-slate-800">Billing & Insurance</h3>
            <p class="text-xs text-slate-500">Active copays, HMO claims, and payment receipts.</p>
            <div class="pt-4 text-xs font-semibold text-blue-600">View Invoices ($0.00 Due) &rarr;</div>
          </div>
        </div>
      </div>
    </main>

    <!-- Registration Modal -->
    <RegistrationModal
      :is-open="isRegistrationModalOpen"
      :branch-id="activeBranchId"
      @close="isRegistrationModalOpen = false"
      @patient-created="handlePatientCreated"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import PatientSearchScreen from './PatientSearchScreen.vue';
import PatientProfileView from './PatientProfileView.vue';
import RegistrationModal from './RegistrationModal.vue';

const activeBranchId = ref('b9ff561a-5396-4309-9b08-3e7b358310e9'); // Fallback or dynamic branch ID
const currentView = ref('search');
const selectedPatient = ref(null);
const isRegistrationModalOpen = ref(false);
const searchScreenRef = ref(null);

function openRegistrationModal() {
  isRegistrationModalOpen.value = true;
}

function handleSelectPatient(patient) {
  selectedPatient.value = patient;
  currentView.value = 'profile';
}

function handlePatientCreated(patient) {
  selectedPatient.value = patient;
  currentView.value = 'profile';
  if (searchScreenRef.value) {
    searchScreenRef.value.fetchPatients();
  }
}
</script>
