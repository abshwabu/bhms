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
        <nav class="p-4 space-y-1.5 overflow-y-auto max-h-[calc(100vh-160px)]">
          <!-- Patient Records -->
          <div class="text-[10px] font-mono uppercase tracking-wider text-slate-500 px-3 py-1 font-bold">Patient Records</div>
          
          <button
            @click="currentView = 'search'"
            :class="currentView === 'search' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            Patient Registry
          </button>

          <button
            @click="openRegistrationModal"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium text-slate-400 hover:bg-slate-800 hover:text-white transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Register Walk-In / Referral
          </button>

          <!-- Appointment & OPD -->
          <div class="text-[10px] font-mono uppercase tracking-wider text-slate-500 px-3 py-1 mt-3 font-bold">Appointment & OPD</div>

          <button
            @click="currentView = 'booking'"
            :class="currentView === 'booking' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            Booking & Doctor Calendar
          </button>

          <button
            @click="currentView = 'queue'"
            :class="currentView === 'queue' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            Queue & TV Waiting Display
          </button>

          <button
            @click="currentView = 'soap'"
            :class="currentView === 'soap' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            SOAP Consultation Notes
          </button>

          <button
            @click="currentView = 'referrals'"
            :class="currentView === 'referrals' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
            </svg>
            Referral Transfers
          </button>

          <!-- Doctor & Clinical -->
          <div class="text-[10px] font-mono uppercase tracking-wider text-slate-500 px-3 py-1 mt-3 font-bold">Doctor & Clinical</div>

          <button
            @click="currentView = 'doctor_dashboard'"
            :class="currentView === 'doctor_dashboard' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            Doctor Dashboard
          </button>

          <button
            @click="navigateToEhrTimeline"
            :class="currentView === 'ehr' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            EHR Clinical Records
          </button>

          <button
            @click="navigateToPrescriptions"
            :class="currentView === 'prescriptions' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
            </svg>
            E-Prescribe & CDS
          </button>

          <!-- Diagnostic Laboratory (LIS) -->
          <div class="text-[10px] font-mono uppercase tracking-wider text-slate-500 px-3 py-1 mt-3 font-bold">Diagnostic Laboratory (LIS)</div>

          <button
            @click="currentView = 'laboratory'"
            :class="currentView === 'laboratory' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
            </svg>
            Laboratory Worklist
          </button>

          <!-- Inpatient & IPD Management -->
          <div class="text-[10px] font-mono uppercase tracking-wider text-slate-500 px-3 py-1 mt-3 font-bold">Inpatient & IPD</div>

          <button
            @click="currentView = 'bed_map'"
            :class="currentView === 'bed_map' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
            </svg>
            Bed Map & Ward View
          </button>

          <button
            @click="currentView = 'nursing'"
            :class="currentView === 'nursing' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
            </svg>
            Nursing Station (Vitals/Meds)
          </button>

          <button
            @click="currentView = 'discharge'"
            :class="currentView === 'discharge' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Discharge Summary
          </button>

          <button
            @click="currentView = 'ipd_analytics'"
            :class="currentView === 'ipd_analytics' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Occupancy & ALOS Analytics
          </button>

          <!-- Portals -->
          <div class="text-[10px] font-mono uppercase tracking-wider text-slate-500 px-3 py-1 mt-3 font-bold">Portals</div>

          <button
            @click="currentView = 'portal'"
            :class="currentView === 'portal' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
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

      <!-- Appointment Booking & Calendar -->
      <BookingCalendar
        v-else-if="currentView === 'booking'"
        :branch-id="activeBranchId"
      />

      <!-- OPD Queue & Waiting Room System -->
      <QueueDashboard
        v-else-if="currentView === 'queue'"
        :branch-id="activeBranchId"
      />

      <!-- SOAP Clinical Consultation Notes -->
      <SoapNoteEditor
        v-else-if="currentView === 'soap'"
        :branch-id="activeBranchId"
      />

      <!-- Referral Manager -->
      <ReferralManager
        v-else-if="currentView === 'referrals'"
        :branch-id="activeBranchId"
      />

      <!-- Inpatient: Bed Map & Ward Allocation -->
      <BedMapVisualView
        v-else-if="currentView === 'bed_map'"
        :branch-id="activeBranchId"
      />

      <!-- Inpatient: Nursing Station -->
      <NursingDashboard
        v-else-if="currentView === 'nursing'"
        :branch-id="activeBranchId"
      />

      <!-- Inpatient: Discharge Summary Generator -->
      <DischargeSummaryGenerator
        v-else-if="currentView === 'discharge'"
        :branch-id="activeBranchId"
      />

      <!-- Inpatient: Bed Occupancy & ALOS Analytics -->
      <IpdAnalyticsView
        v-else-if="currentView === 'ipd_analytics'"
        :branch-id="activeBranchId"
      />

      <!-- Doctor & Clinical: Personal Dashboard -->
      <DoctorDashboard
        v-else-if="currentView === 'doctor_dashboard'"
        :branch-id="activeBranchId"
        @open-patient-ehr="handleOpenPatientEhr"
        @open-prescription-writer="handleOpenPrescriptionWriter"
        @open-order-modal="handleOpenOrderModal"
      />

      <!-- Doctor & Clinical: Longitudinal EHR Records -->
      <EhrTimelineView
        v-else-if="currentView === 'ehr' && selectedPatient"
        :patient="selectedPatient"
        :branch-id="activeBranchId"
        @back="currentView = 'doctor_dashboard'"
        @open-prescription-writer="handleOpenPrescriptionWriter"
        @open-order-modal="handleOpenOrderModal"
      />

      <!-- Doctor & Clinical: E-Prescriptions & CDS Warnings -->
      <PrescriptionWriter
        v-else-if="currentView === 'prescriptions' && selectedPatient"
        :patient="selectedPatient"
        :branch-id="activeBranchId"
        @back="currentView = 'ehr'"
        @prescription-created="handlePrescriptionCreated"
      />

      <!-- Diagnostic Laboratory: Master Bench & Samples -->
      <LabWorklist
        v-else-if="currentView === 'laboratory'"
        :branch-id="activeBranchId"
      />

      <!-- Patient Portal Self-Service View -->
      <div v-else-if="currentView === 'portal'" class="space-y-6">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
          <h2 class="text-xl font-bold text-slate-900">Patient-Facing Portal Preview</h2>
          <p class="text-sm text-slate-500 mt-1">Simulates what patients see when logging into their personal health dashboard.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm space-y-2">
            <h3 class="font-bold text-slate-800">My Appointments</h3>
            <p class="text-xs text-slate-500">Upcoming specialist consults and queue position.</p>
            <div class="pt-4 text-xs font-semibold text-blue-600">Active Appointments &rarr;</div>
          </div>
          <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm space-y-2">
            <h3 class="font-bold text-slate-800">My Medical Records</h3>
            <p class="text-xs text-slate-500">Inpatient summaries, SOAP notes, and lab results.</p>
            <div class="pt-4 text-xs font-semibold text-blue-600">View Diagnostic History &rarr;</div>
          </div>
          <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm space-y-2">
            <h3 class="font-bold text-slate-800">Billing & Insurance</h3>
            <p class="text-xs text-slate-500">Inpatient room rates, copays, and claims.</p>
            <div class="pt-4 text-xs font-semibold text-blue-600">View Invoices &rarr;</div>
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

    <!-- Diagnostic Order Entry Modal (Lab & Radiology) -->
    <OrderEntryModal
      v-if="selectedPatient"
      :is-open="isOrderModalOpen"
      :initial-type="orderModalType"
      :patient="selectedPatient"
      :branch-id="activeBranchId"
      @close="isOrderModalOpen = false"
      @order-created="handleOrderCreated"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import PatientSearchScreen from './PatientSearchScreen.vue';
import PatientProfileView from './PatientProfileView.vue';
import RegistrationModal from './RegistrationModal.vue';
import BookingCalendar from '../OPD/BookingCalendar.vue';
import QueueDashboard from '../OPD/QueueDashboard.vue';
import SoapNoteEditor from '../OPD/SoapNoteEditor.vue';
import ReferralManager from '../OPD/ReferralManager.vue';
import BedMapVisualView from '../IPD/BedMapVisualView.vue';
import NursingDashboard from '../IPD/NursingDashboard.vue';
import DischargeSummaryGenerator from '../IPD/DischargeSummaryGenerator.vue';
import IpdAnalyticsView from '../IPD/IpdAnalyticsView.vue';
import DoctorDashboard from '../Clinical/DoctorDashboard.vue';
import EhrTimelineView from '../Clinical/EhrTimelineView.vue';
import PrescriptionWriter from '../Clinical/PrescriptionWriter.vue';
import OrderEntryModal from '../Clinical/OrderEntryModal.vue';
import LabWorklist from '../Laboratory/LabWorklist.vue';

const activeBranchId = ref('b9ff561a-5396-4309-9b08-3e7b358310e9');
const currentView = ref('doctor_dashboard');
const selectedPatient = ref(null);
const isRegistrationModalOpen = ref(false);
const isOrderModalOpen = ref(false);
const orderModalType = ref('lab');
const searchScreenRef = ref(null);

function openRegistrationModal() {
  isRegistrationModalOpen.value = true;
}

function handleSelectPatient(patient) {
  selectedPatient.value = patient;
  currentView.value = 'ehr';
}

function handlePatientCreated(patient) {
  selectedPatient.value = patient;
  currentView.value = 'ehr';
  if (searchScreenRef.value) {
    searchScreenRef.value.fetchPatients();
  }
}

function handleOpenPatientEhr(patient) {
  selectedPatient.value = patient;
  currentView.value = 'ehr';
}

function handleOpenPrescriptionWriter(patient) {
  if (patient) selectedPatient.value = patient;
  if (!selectedPatient.value) {
    currentView.value = 'search';
    return;
  }
  currentView.value = 'prescriptions';
}

function handleOpenOrderModal(payload) {
  if (typeof payload === 'string') {
    orderModalType.value = payload;
  } else if (payload && payload.type) {
    orderModalType.value = payload.type;
    if (payload.patient) selectedPatient.value = payload.patient;
  }
  if (!selectedPatient.value) {
    currentView.value = 'search';
    return;
  }
  isOrderModalOpen.value = true;
}

function handleOrderCreated() {
  // Can trigger refresh if timeline is active
}

function handlePrescriptionCreated() {
  currentView.value = 'ehr';
}

function navigateToEhrTimeline() {
  if (!selectedPatient.value) {
    currentView.value = 'search';
  } else {
    currentView.value = 'ehr';
  }
}

function navigateToPrescriptions() {
  if (!selectedPatient.value) {
    currentView.value = 'search';
  } else {
    currentView.value = 'prescriptions';
  }
}
</script>
