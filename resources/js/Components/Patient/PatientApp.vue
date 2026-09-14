<template>
  <!-- Unauthenticated Staff Sign In Page -->
  <SignInView
    v-if="!currentUser"
    @login-success="handleLoginSuccess"
  />

  <!-- Authenticated Hospital Application Portal -->
  <div v-else class="min-h-screen bg-slate-100/70 font-sans text-slate-800 flex flex-col">
    <!-- Persistent Support Impersonation Banner -->
    <div
      v-if="isImpersonating"
      class="bg-gradient-to-r from-amber-600 via-amber-700 to-amber-800 text-white px-6 py-2.5 shadow-md flex items-center justify-between gap-4 z-40 shrink-0 border-b border-amber-500/50"
    >
      <div class="flex items-center gap-3">
        <div class="w-7 h-7 rounded-lg bg-amber-500/40 flex items-center justify-center animate-pulse">
          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <div>
          <div class="text-xs font-black tracking-wide uppercase flex items-center gap-2">
            <span>Vendor Support Impersonation Active</span>
            <span class="px-2 py-0.5 bg-black/30 rounded text-[10px] font-mono font-normal">Audit Logged</span>
          </div>
          <div class="text-[11px] text-amber-100">
            Viewing tenant: <strong>{{ impersonatedHospitalName }}</strong> (Session active). All actions are recorded.
          </div>
        </div>
      </div>

      <button
        @click="exitImpersonation"
        :disabled="exitingImpersonation"
        class="px-4 py-1.5 bg-white hover:bg-amber-50 active:bg-amber-100 text-amber-900 rounded-lg text-xs font-black transition cursor-pointer shadow-sm disabled:opacity-50 flex items-center gap-1.5 shrink-0"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
        </svg>
        <span>{{ exitingImpersonation ? 'Exiting...' : 'Exit Impersonation' }}</span>
      </button>
    </div>

    <div class="flex-1 flex overflow-hidden">
      <!-- Sidebar Navigation -->
      <aside class="w-64 bg-slate-900 text-white flex flex-col justify-between shrink-0 shadow-xl">
      <div>
        <!-- Brand Header with Tenant Isolation -->
        <div class="p-6 border-b border-slate-800">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center font-black text-xl text-white shadow-lg shadow-blue-500/30 shrink-0">
              +
            </div>
            <div class="min-w-0">
              <div class="font-extrabold text-base tracking-tight leading-tight truncate text-white">
                {{ currentOrganization?.name || 'Metro Health System' }}
              </div>
              <div class="text-[11px] text-blue-400 font-medium flex items-center gap-1.5 truncate">
                <span>{{ currentOrganization?.code || 'MHS' }}</span>
                <span>&bull;</span>
                <span class="capitalize text-emerald-400">{{ currentOrganization?.plan_tier || 'Enterprise' }}</span>
              </div>
            </div>
          </div>

          <!-- Active Branch Selector / Multi-Tenant Badge -->
          <div class="mt-4 p-2.5 rounded-xl bg-slate-800/80 border border-slate-700/60">
            <div class="flex items-center justify-between text-[10px] font-mono text-slate-400 mb-1">
              <span class="flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>FACILITY BRANCH</span>
              </span>
              <span class="font-mono text-slate-300 font-bold">{{ currentBranch?.code || 'MAIN' }}</span>
            </div>
            <select
              v-if="accessibleBranches && accessibleBranches.length > 1"
              :value="activeBranchId"
              @change="handleBranchChange($event.target.value)"
              class="w-full mt-1 bg-slate-900 border border-slate-700 text-white rounded-lg px-2.5 py-1.5 text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none cursor-pointer"
            >
              <option v-for="br in accessibleBranches" :key="br.id" :value="br.id">
                {{ br.name }} ({{ br.code }})
              </option>
            </select>
            <div v-else class="text-xs font-semibold text-slate-200 truncate mt-0.5">
              {{ currentBranch?.name || 'Metro General Hospital (Main Campus)' }}
            </div>
          </div>

          <!-- Active Role Persona Badge -->
          <div class="mt-2.5 px-2.5 py-1.5 rounded-xl bg-slate-800/80 border border-slate-700/60 flex items-center justify-between text-xs">
            <div class="flex items-center gap-1.5 text-slate-400 text-[10px] font-mono uppercase tracking-wider">
              <span class="w-1.5 h-1.5 rounded-full bg-sky-400 animate-pulse"></span>
              <span>ROLE CONTEXT</span>
            </div>
            <span class="font-bold text-sky-300 text-[11px] truncate max-w-[130px]">{{ currentUser?.primary_role || 'Staff' }}</span>
          </div>
        </div>

        <!-- Navigation Links (Filtered strictly by RBAC) -->
        <nav class="p-4 space-y-1.5 overflow-y-auto max-h-[calc(100vh-210px)]">

          <!-- 1. Patient Intake & Registry (All authenticated staff) -->
          <div class="text-[10px] font-mono uppercase tracking-wider text-slate-500 px-3 py-1 font-bold">
            Patient Intake & Records
          </div>

          <button
            @click="currentView = 'search'"
            :class="currentView === 'search' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            Patient Registry & Search
          </button>

          <button
            v-if="isReceptionist || isNurse || isHospitalAdmin"
            @click="openRegistrationModal"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium text-slate-400 hover:bg-slate-800 hover:text-white transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Register Walk-In / Referral
          </button>

          <!-- 2. Emergency & Trauma (Doctor, Nurse) -->
          <div
            v-if="isDoctor || isNurse"
            class="text-[10px] font-mono uppercase tracking-wider text-rose-500 px-3 py-1 mt-3 font-bold flex items-center justify-between"
          >
            <span>Emergency & Trauma</span>
            <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
          </div>

          <button
            v-if="isDoctor || isNurse"
            @click="currentView = 'emergency'"
            :class="currentView === 'emergency' ? 'bg-rose-600 text-white font-semibold shadow-sm' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            ER Triage & Ambulance CAD
          </button>

          <!-- 3. Outpatient & Consultations (Doctor, Receptionist, Nurse) -->
          <div
            v-if="isDoctor || isReceptionist || isNurse"
            class="text-[10px] font-mono uppercase tracking-wider text-slate-500 px-3 py-1 mt-3 font-bold"
          >
            Outpatient & Consultations
          </div>

          <button
            v-if="isDoctor || isReceptionist"
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
            v-if="isDoctor || isReceptionist || isNurse"
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
            v-if="isDoctor"
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
            v-if="isDoctor || isReceptionist"
            @click="currentView = 'referrals'"
            :class="currentView === 'referrals' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
            </svg>
            Referral Transfers
          </button>

          <!-- 4. Doctor & Clinical Medicine (Doctor, Nurse, Pharmacist) -->
          <div
            v-if="isDoctor || isNurse || isPharmacist"
            class="text-[10px] font-mono uppercase tracking-wider text-slate-500 px-3 py-1 mt-3 font-bold"
          >
            Doctor & Clinical Medicine
          </div>

          <button
            v-if="isDoctor"
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
            v-if="isDoctor || isNurse"
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
            v-if="isDoctor || isPharmacist"
            @click="navigateToPrescriptions"
            :class="currentView === 'prescriptions' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
            </svg>
            {{ isPharmacist ? 'Prescriptions Review' : 'E-Prescribe & CDS' }}
          </button>

          <!-- 5. Pharmacy & Dispensing (Pharmacist ONLY) -->
          <div
            v-if="isPharmacist"
            class="text-[10px] font-mono uppercase tracking-wider text-teal-400 px-3 py-1 mt-3 font-bold"
          >
            Pharmacy & Dispensing
          </div>

          <button
            v-if="isPharmacist"
            @click="currentView = 'pharmacy'"
            :class="currentView === 'pharmacy' ? 'bg-teal-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
            </svg>
            Pharmacy Master (FEFO)
          </button>

          <!-- 6. Inpatient & IPD Management (Hospital Admin, Doctor, Nurse, Billing, Receptionist) -->
          <div
            v-if="isHospitalAdmin || isDoctor || isNurse || isBilling || isReceptionist"
            class="text-[10px] font-mono uppercase tracking-wider text-slate-500 px-3 py-1 mt-3 font-bold"
          >
            Inpatient & IPD Care
          </div>

          <button
            v-if="isHospitalAdmin || isDoctor || isNurse || isBilling || isReceptionist"
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
            v-if="isNurse"
            @click="currentView = 'nursing'"
            :class="currentView === 'nursing' ? 'bg-emerald-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
            </svg>
            Nursing Station (Vitals/MAR)
          </button>

          <button
            v-if="isDoctor || isNurse"
            @click="currentView = 'discharge'"
            :class="currentView === 'discharge' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Discharge Summaries
          </button>

          <button
            v-if="isHospitalAdmin"
            @click="currentView = 'ipd_analytics'"
            :class="currentView === 'ipd_analytics' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Occupancy & ALOS Analytics
          </button>

          <!-- 7. Billing & Finance (Hospital Admin, Billing Officer) -->
          <div
            v-if="isHospitalAdmin || isBilling"
            class="text-[10px] font-mono uppercase tracking-wider text-slate-500 px-3 py-1 mt-3 font-bold"
          >
            Billing & Finance
          </div>

          <button
            v-if="isHospitalAdmin || isBilling"
            @click="currentView = 'billing'"
            :class="currentView === 'billing' ? 'bg-amber-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            Billing, Invoicing & Claims
          </button>

          <!-- 8. Materials & Inventory (Hospital Admin, Nurse, Pharmacist) -->
          <div
            v-if="isHospitalAdmin || isNurse || isPharmacist"
            class="text-[10px] font-mono uppercase tracking-wider text-slate-500 px-3 py-1 mt-3 font-bold"
          >
            Materials & Assets
          </div>

          <button
            v-if="isHospitalAdmin || isNurse || isPharmacist"
            @click="currentView = 'inventory'"
            :class="currentView === 'inventory' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            {{ isNurse ? 'Ward Consumables & Stock' : 'Inventory & Equipment' }}
          </button>

          <!-- 9. Staff & Rostering (Hospital Admin ONLY) -->
          <div
            v-if="isHospitalAdmin"
            class="text-[10px] font-mono uppercase tracking-wider text-slate-500 px-3 py-1 mt-3 font-bold"
          >
            Staff & Rostering
          </div>

          <button
            v-if="isHospitalAdmin"
            @click="currentView = 'hr'"
            :class="currentView === 'hr' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            HR & Staff Management
          </button>

          <!-- 10. Executive Intelligence & BI (Hospital Admin, Super Admin) -->
          <div
            v-if="isHospitalAdmin || isSuperAdmin"
            class="text-[10px] font-mono uppercase tracking-wider text-indigo-400 px-3 py-1 mt-3 font-bold"
          >
            Intelligence & BI
          </div>

          <button
            v-if="isHospitalAdmin || isSuperAdmin"
            @click="currentView = 'reports'"
            :class="currentView === 'reports' ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Reports & Analytics
          </button>

          <button
            v-if="isHospitalAdmin"
            @click="currentView = 'telegram'"
            :class="currentView === 'telegram' ? 'bg-sky-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.52 2.77-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .27z"/>
            </svg>
            Telegram Alerts Engine
          </button>

          <!-- 11. Governance & Compliance (Hospital Admin, Super Admin) -->
          <div
            v-if="isHospitalAdmin || isSuperAdmin"
            class="text-[10px] font-mono uppercase tracking-wider text-slate-500 px-3 py-1 mt-3 font-bold"
          >
            Governance & Compliance
          </div>

          <button
            v-if="isHospitalAdmin || isSuperAdmin"
            @click="currentView = 'compliance'"
            :class="currentView === 'compliance' ? 'bg-amber-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            Compliance & Security (HIPAA)
          </button>

          <button
            v-if="isHospitalAdmin || isSuperAdmin"
            @click="currentView = 'admin'"
            :class="currentView === 'admin' ? 'bg-cyan-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            System Administration
          </button>

          <!-- 12. Platform Vendor Control Plane (Super Admin ONLY) -->
          <div
            v-if="isSuperAdmin"
            class="text-[10px] font-mono uppercase tracking-wider text-amber-400 px-3 py-1 mt-3 font-bold flex items-center justify-between"
          >
            <span>Platform Vendor</span>
            <span class="px-1.5 py-0.2 bg-amber-500/20 text-amber-300 rounded text-[9px]">ROOT</span>
          </div>

          <button
            v-if="isSuperAdmin"
            @click="currentView = 'super_admin'"
            :class="currentView === 'super_admin' ? 'bg-amber-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            Super Admin Platform
          </button>

          <!-- 13. Digital Portals Preview (Hospital Admin ONLY) -->
          <div
            v-if="isHospitalAdmin"
            class="text-[10px] font-mono uppercase tracking-wider text-slate-500 px-3 py-1 mt-3 font-bold"
          >
            Digital Portals
          </div>

          <button
            v-if="isHospitalAdmin"
            @click="currentView = 'portal'"
            :class="currentView === 'portal' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            Patient Portal Preview
          </button>

        </nav>
      </div>

      <!-- User Session Footer with Quick Persona Switcher & Sign Out -->
      <div class="p-3.5 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400 bg-slate-950/40">
        <div class="flex items-center gap-2.5 overflow-hidden">
          <div class="w-8 h-8 rounded-full bg-blue-600/30 border border-blue-500/40 flex items-center justify-center font-bold text-white text-xs shrink-0">
            {{ userInitials }}
          </div>
          <div class="truncate">
            <div class="text-white font-medium text-xs truncate">{{ currentUser?.name }}</div>
            <div class="text-[10px] text-blue-400 font-medium truncate">{{ currentUser?.primary_role }}</div>
          </div>
        </div>

        <div class="flex items-center gap-1 shrink-0">
          <button
            @click="showPersonaModal = true"
            title="Switch RBAC Persona"
            class="px-2 py-1 bg-slate-800 hover:bg-slate-700 active:bg-slate-600 text-slate-300 rounded-lg text-[10px] font-mono transition cursor-pointer flex items-center gap-1"
          >
            <span>⇄</span>
            <span class="hidden sm:inline">Role</span>
          </button>
          <button
            @click="handleSignOut"
            title="Sign Out"
            class="p-1.5 hover:bg-rose-950 hover:text-rose-400 text-slate-400 rounded-lg text-xs transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
          </button>
        </div>
      </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 p-8 max-w-7xl mx-auto overflow-y-auto w-full">
      <!-- Access Restricted Alert if module is unauthorized for active role -->
      <div v-if="!canAccessView(currentView)" class="max-w-xl mx-auto my-16 bg-white border border-amber-200 rounded-3xl p-8 shadow-sm text-center space-y-4">
        <div class="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center mx-auto text-amber-600 border border-amber-200">
          <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <div>
          <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-amber-100 text-amber-800 uppercase tracking-wider">
            RBAC Access Guard
          </span>
          <h2 class="text-xl font-bold text-slate-900 mt-2">Access Restricted</h2>
          <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
            Your current persona (<strong class="text-slate-800">{{ currentUser?.primary_role }}</strong>) does not have authorization to access the <span class="font-mono font-bold text-slate-700 uppercase">{{ currentView.replace('_', ' ') }}</span> module.
          </p>
        </div>
        <div class="pt-2 flex items-center justify-center gap-3">
          <button
            @click="currentView = getDefaultViewForRole(currentUser)"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl transition cursor-pointer shadow-sm"
          >
            Return to My Workspace
          </button>
          <button
            @click="showPersonaModal = true"
            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs rounded-xl transition cursor-pointer"
          >
            Switch Persona ⇄
          </button>
        </div>
      </div>

      <template v-else>
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

      <!-- Radiology Information System (RIS): Worklist, Scheduling & Viewer -->
      <ImagingWorklist
        v-else-if="currentView === 'radiology'"
        :branch-id="activeBranchId"
      />

      <!-- Pharmacy: Dispensing, Inventory & FEFO Expiry Tracking -->
      <PharmacyMasterView
        v-else-if="currentView === 'pharmacy'"
        :branch-id="activeBranchId"
      />

      <!-- Billing & Finance: Invoicing, Payments, Claims & Revenue Analytics -->
      <BillingMasterView
        v-else-if="currentView === 'billing'"
        :branch-id="activeBranchId"
      />

      <!-- Materials & Asset Management: Stock, POs & Equipment Maintenance -->
      <InventoryMasterView
        v-else-if="currentView === 'inventory'"
        :branch-id="activeBranchId"
      />

      <!-- Human Resources & Staff Management: Rosters, Attendance, Leaves & Credentials -->
      <HrMasterView
        v-else-if="currentView === 'hr'"
        :branch-id="activeBranchId"
      />

      <!-- Emergency & Ambulance: ER Triage, Resuscitation Queue & Ambulance CAD -->
      <EmergencyMasterView
        v-else-if="currentView === 'emergency'"
        :branch-id="activeBranchId"
      />

      <!-- Reports & Analytics: Executive KPIs, Departments, Doctors & Custom Builder -->
      <ReportsMasterView
        v-else-if="currentView === 'reports'"
        :branch-id="activeBranchId"
      />

      <!-- Governance & Security: HIPAA Safeguards, RBAC, Audit Trails & Consents -->
      <ComplianceMasterView
        v-else-if="currentView === 'compliance'"
        :branch-id="activeBranchId"
      />

      <!-- System Administration: Multi-Branch, Master Data, Notifications & Disaster Recovery -->
      <AdministrationMasterView
        v-else-if="currentView === 'admin'"
        :branch-id="activeBranchId"
      />

      <!-- Telegram Reporting & Alert Engine -->
      <TelegramManagementView
        v-else-if="currentView === 'telegram'"
        :branch-id="activeBranchId"
      />

      <!-- Super Admin Platform Control Plane -->
      <SuperAdminMasterView
        v-else-if="currentView === 'super_admin'"
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
            <button @click="currentView = 'billing'" class="pt-4 text-xs font-semibold text-blue-600 hover:underline cursor-pointer block text-left">
              View Invoices &rarr;
            </button>
          </div>
        </div>
      </div>
      </template>
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

    <!-- Quick Persona Switcher Modal -->
    <div
      v-if="showPersonaModal"
      class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 overflow-y-auto"
      @click.self="showPersonaModal = false"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl relative text-slate-100">
        <div class="flex items-start justify-between mb-6 pb-4 border-b border-slate-800">
          <div>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-sky-500/10 text-sky-400 border border-sky-500/20 uppercase tracking-wider mb-2">
              Instant RBAC Persona Simulator
            </span>
            <h2 class="text-xl font-bold text-white">Switch Role Persona</h2>
            <p class="text-xs text-slate-400 mt-1">
              Test role-based access control, hospital multi-tenant isolation, and tailored clinical workspaces.
            </p>
          </div>
          <button
            @click="showPersonaModal = false"
            class="text-slate-400 hover:text-white p-2 rounded-xl hover:bg-slate-800 transition cursor-pointer"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Persona Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[60vh] overflow-y-auto pr-1">
          <button
            v-for="persona in demoPersonas"
            :key="persona.role_key"
            @click="quickSwitchPersona(persona)"
            :disabled="switchingPersona === persona.role_key"
            :class="currentUser?.roles?.includes(persona.role_key) ? 'border-sky-500/80 bg-sky-950/20' : 'border-slate-800 bg-slate-950/50 hover:border-slate-700 hover:bg-slate-800/40'"
            class="p-4 rounded-2xl border text-left transition cursor-pointer flex flex-col justify-between group relative disabled:opacity-50"
          >
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-[10px] font-mono font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-slate-800 text-slate-300">
                  {{ persona.role_label }}
                </span>
                <span
                  v-if="currentUser?.roles?.includes(persona.role_key)"
                  class="text-[10px] font-bold text-sky-400 flex items-center gap-1"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span> Active
                </span>
              </div>
              <div>
                <div class="text-sm font-bold text-white group-hover:text-sky-300 transition">{{ persona.name }}</div>
                <div class="text-[11px] text-slate-500 font-mono">{{ persona.email }}</div>
              </div>
              <div class="flex flex-wrap gap-1 pt-1">
                <span
                  v-for="(scope, idx) in (persona.accessible_scopes || []).slice(0, 3)"
                  :key="idx"
                  class="text-[9px] bg-slate-900 border border-slate-800 text-slate-400 px-1.5 py-0.5 rounded"
                >
                  {{ scope }}
                </span>
              </div>
            </div>
            <div class="mt-3 pt-2 border-t border-slate-800/60 flex items-center justify-between text-[11px] text-sky-400 font-semibold">
              <span>{{ switchingPersona === persona.role_key ? 'Switching...' : 'Select Role' }}</span>
              <span>&rarr;</span>
            </div>
          </button>
        </div>
      </div>
    </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import SignInView from '../Auth/SignInView.vue';
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
import ImagingWorklist from '../Radiology/ImagingWorklist.vue';
import PharmacyMasterView from '../Pharmacy/PharmacyMasterView.vue';
import BillingMasterView from '../Billing/BillingMasterView.vue';
import InventoryMasterView from '../Inventory/InventoryMasterView.vue';
import HrMasterView from '../HR/HrMasterView.vue';
import EmergencyMasterView from '../Emergency/EmergencyMasterView.vue';
import ReportsMasterView from '../Reports/ReportsMasterView.vue';
import ComplianceMasterView from '../Compliance/ComplianceMasterView.vue';
import AdministrationMasterView from '../Administration/AdministrationMasterView.vue';
import TelegramManagementView from '../Telegram/TelegramManagementView.vue';
import SuperAdminMasterView from '../SuperAdmin/SuperAdminMasterView.vue';

// Authentication & Tenant Context State
const currentUser = ref(null);
const currentOrganization = ref(null);
const currentBranch = ref(null);
const accessibleBranches = ref([]);
const showPersonaModal = ref(false);
const switchingPersona = ref(null);

const activeBranchId = ref('b9ff561a-5396-4309-9b08-3e7b358310e9');
const currentView = ref('doctor_dashboard');
const selectedPatient = ref(null);
const isRegistrationModalOpen = ref(false);
const isOrderModalOpen = ref(false);
const orderModalType = ref('lab');
const searchScreenRef = ref(null);

// Support Impersonation State
const isImpersonating = ref(false);
const impersonatedHospitalName = ref('');
const exitingImpersonation = ref(false);

// Built-in Demo Personas for Simulation
const defaultDemoPersonas = [
  {
    role_key: 'hospital_admin',
    role_label: 'Hospital Admin',
    email: 'admin@hms.local',
    password: 'password123',
    name: 'Dr. Arthur Sterling',
    accessible_scopes: ['Multi-Branch Facilities', 'Compliance & HIPAA', 'Staff & Master Data', 'BI Reports'],
  },
  {
    role_key: 'doctor',
    role_label: 'Doctor / Clinician',
    email: 'doctor@hms.local',
    password: 'password123',
    name: 'Dr. Eleanor Vance, MD',
    accessible_scopes: ['Doctor Dashboard', 'EHR Clinical History', 'SOAP Consultations', 'Prescriptions & Lab Orders'],
  },
  {
    role_key: 'nurse',
    role_label: 'Inpatient Nurse',
    email: 'nurse@hms.local',
    password: 'password123',
    name: 'Sister Clara Oswald, RN',
    accessible_scopes: ['Bed Map Visuals', 'Nursing Station (Vitals/Meds)', 'Patient Intake', 'Discharge Summaries'],
  },
  {
    role_key: 'pharmacist',
    role_label: 'Chief Pharmacist',
    email: 'pharmacist@hms.local',
    password: 'password123',
    name: 'Marcus Holloway, PharmD',
    accessible_scopes: ['Pharmacy Dispensing', 'Drug Batches & Expiry', 'Stock Reorder Alerts', 'Patient Prescriptions'],
  },
  {
    role_key: 'billing_officer',
    role_label: 'Billing Officer',
    email: 'billing@hms.local',
    password: 'password123',
    name: 'Jennifer Blake',
    accessible_scopes: ['Invoices & Payments', 'Insurance Claims', 'Approvals Queue', 'Revenue Analytics'],
  },
  {
    role_key: 'receptionist',
    role_label: 'Reception / Registrar',
    email: 'receptionist@hms.local',
    password: 'password123',
    name: 'Sarah Connor',
    accessible_scopes: ['Patient Registration', 'Doctor Booking Calendar', 'OPD Queue Tokens & TV Display'],
  },
];
const demoPersonas = ref(defaultDemoPersonas);

// RBAC Role Computations - Strictly Isolated (Zero Cross-Role Leakage)
const userRoles = computed(() => {
  const r = currentUser.value?.roles || [];
  return r.map(item => (typeof item === 'string' ? item : item?.name || ''));
});
const isSuperAdmin = computed(() => !!currentUser.value?.is_super_admin || userRoles.value.includes('super_admin'));
const isHospitalAdmin = computed(() => !isSuperAdmin.value && (userRoles.value.includes('hospital_admin') || userRoles.value.includes('admin')));
const isDoctor = computed(() => userRoles.value.includes('doctor'));
const isNurse = computed(() => userRoles.value.includes('nurse'));
const isPharmacist = computed(() => userRoles.value.includes('pharmacist'));
const isBilling = computed(() => userRoles.value.includes('billing_officer'));
const isReceptionist = computed(() => userRoles.value.includes('receptionist'));
const isLab = computed(() => userRoles.value.includes('lab_technician') || userRoles.value.includes('radiologist'));

// User Initials Display
const userInitials = computed(() => {
  if (!currentUser.value || !currentUser.value.name) return 'HMS';
  const parts = currentUser.value.name.replace(/^(Dr\.|Sister|Mr\.|Ms\.|Mrs\.)\s+/i, '').trim().split(/\s+/);
  if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
});

// Default Workspace Dashboard per Role
function getDefaultViewForRole(user) {
  if (!user) return 'search';
  const roles = (user.roles || []).map(r => (typeof r === 'string' ? r : r?.name || ''));
  if (user.is_super_admin || roles.includes('super_admin')) return 'super_admin';
  if (roles.includes('hospital_admin') || roles.includes('admin')) return 'reports';
  if (roles.includes('doctor')) return 'doctor_dashboard';
  if (roles.includes('nurse')) return 'nursing';
  if (roles.includes('pharmacist')) return 'pharmacy';
  if (roles.includes('billing_officer')) return 'billing';
  if (roles.includes('receptionist')) return 'booking';
  return 'search';
}

// Check Module Authorization strictly by Role Whitelist
function canAccessView(view) {
  if (!currentUser.value) return false;
  switch (view) {
    case 'super_admin':
      return isSuperAdmin.value;
    case 'admin':
    case 'compliance':
    case 'reports':
      return isHospitalAdmin.value || isSuperAdmin.value;
    case 'telegram':
    case 'hr':
    case 'portal':
    case 'ipd_analytics':
      return isHospitalAdmin.value;
    case 'billing':
      return isHospitalAdmin.value || isBilling.value;
    case 'inventory':
      return isHospitalAdmin.value || isNurse.value || isPharmacist.value;
    case 'pharmacy':
      return isPharmacist.value;
    case 'prescriptions':
      return isDoctor.value || isPharmacist.value;
    case 'doctor_dashboard':
    case 'soap':
      return isDoctor.value;
    case 'ehr':
      return isDoctor.value || isNurse.value;
    case 'nursing':
      return isNurse.value;
    case 'bed_map':
      return isHospitalAdmin.value || isDoctor.value || isNurse.value || isBilling.value || isReceptionist.value;
    case 'discharge':
    case 'emergency':
      return isDoctor.value || isNurse.value;
    case 'booking':
    case 'referrals':
      return isDoctor.value || isReceptionist.value;
    case 'queue':
      return isDoctor.value || isReceptionist.value || isNurse.value;
    case 'laboratory':
    case 'radiology':
      return isDoctor.value || isLab.value;
    case 'search':
    case 'profile':
      return true;
    default:
      return false;
  }
}

function handleBranchChange(branchId) {
  activeBranchId.value = branchId;
  const found = accessibleBranches.value.find(b => b.id === branchId);
  if (found) {
    currentBranch.value = found;
  }
  if (window.axios) {
    window.axios.defaults.headers.common['X-Branch-ID'] = branchId;
  }
}

function handleLoginSuccess(payload) {
  currentUser.value = payload.user || payload.data?.user;
  currentOrganization.value = payload.organization || payload.data?.organization;
  currentBranch.value = payload.default_branch || payload.data?.default_branch;
  accessibleBranches.value = payload.accessible_branches || payload.data?.accessible_branches || [];

  if (currentBranch.value && currentBranch.value.id) {
    activeBranchId.value = currentBranch.value.id;
  } else if (accessibleBranches.value.length > 0) {
    activeBranchId.value = accessibleBranches.value[0].id;
    currentBranch.value = accessibleBranches.value[0];
  }

  const token = payload.token || payload.data?.token;
  const sessionData = {
    user: currentUser.value,
    organization: currentOrganization.value,
    default_branch: currentBranch.value,
    accessible_branches: accessibleBranches.value,
    token: token,
  };
  localStorage.setItem('hms_portal_session', JSON.stringify(sessionData));
  if (token) {
    sessionStorage.setItem('hms_auth_token', token);
    if (window.axios) {
      window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    }
  }
  if (activeBranchId.value && window.axios) {
    window.axios.defaults.headers.common['X-Branch-ID'] = activeBranchId.value;
  }

  currentView.value = getDefaultViewForRole(currentUser.value);

  if (window.location.pathname === '/login') {
    window.history.pushState({}, '', '/app');
  }
}

async function handleSignOut() {
  const token = sessionStorage.getItem('hms_auth_token');
  try {
    if (token) {
      await fetch('/api/v1/auth/logout', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      });
    }
  } catch (err) {
    console.warn('Sign out request failed:', err);
  } finally {
    localStorage.removeItem('hms_portal_session');
    sessionStorage.removeItem('hms_auth_token');
    if (window.axios) {
      delete window.axios.defaults.headers.common['Authorization'];
      delete window.axios.defaults.headers.common['X-Branch-ID'];
    }
    currentUser.value = null;
    currentOrganization.value = null;
    currentBranch.value = null;
    accessibleBranches.value = [];
    if (window.location.pathname !== '/login') {
      window.history.pushState({}, '', '/login');
    }
  }
}

async function quickSwitchPersona(persona) {
  switchingPersona.value = persona.role_key;
  try {
    const res = await fetch('/api/v1/auth/login', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({
        email: persona.email,
        password: persona.password || 'password123',
      }),
    });
    const data = await res.json();
    if (res.ok && (data.user || data.data?.user)) {
      handleLoginSuccess(data);
      showPersonaModal.value = false;
    }
  } catch (err) {
    console.error('Failed to switch persona:', err);
  } finally {
    switchingPersona.value = null;
  }
}

onMounted(async () => {
  window.addEventListener('hms:unauthorized', () => {
    handleSignOut();
  });

  const impId = sessionStorage.getItem('hms_impersonation_id');
  const impHosp = sessionStorage.getItem('hms_impersonated_hospital');
  if (impId) {
    isImpersonating.value = true;
    if (impHosp) {
      try {
        const h = JSON.parse(impHosp);
        impersonatedHospitalName.value = h.name || 'Hospital Client';
      } catch {
        impersonatedHospitalName.value = 'Hospital Client';
      }
    }
  }

  // Load session from localStorage only if token exists
  const rawSession = localStorage.getItem('hms_portal_session');
  if (rawSession) {
    try {
      const parsed = JSON.parse(rawSession);
      if (parsed && parsed.user && parsed.token) {
        currentUser.value = parsed.user;
        currentOrganization.value = parsed.organization;
        currentBranch.value = parsed.default_branch;
        accessibleBranches.value = parsed.accessible_branches || [];
        if (parsed.default_branch?.id) {
          activeBranchId.value = parsed.default_branch.id;
        } else if (accessibleBranches.value.length > 0) {
          activeBranchId.value = accessibleBranches.value[0].id;
        }

        sessionStorage.setItem('hms_auth_token', parsed.token);
        if (window.axios) {
          window.axios.defaults.headers.common['Authorization'] = `Bearer ${parsed.token}`;
          if (activeBranchId.value) {
            window.axios.defaults.headers.common['X-Branch-ID'] = activeBranchId.value;
          }
        }

        // Verify session with backend
        fetch('/api/v1/auth/me', {
          headers: {
            'Accept': 'application/json',
            'Authorization': `Bearer ${parsed.token}`,
            'X-Branch-ID': activeBranchId.value || '',
          }
        }).then(res => {
          if (!res.ok || res.status === 401) {
            handleSignOut();
            return null;
          }
          return res.json();
        }).then(data => {
          if (data && data.authenticated && data.user) {
            // Ensure roles array is not cleared if backend returns empty
            if ((!data.user.roles || data.user.roles.length === 0) && parsed.user?.roles?.length > 0) {
              data.user.roles = parsed.user.roles;
              data.user.primary_role = parsed.user.primary_role;
            }
            currentUser.value = data.user;
            if (data.organization) currentOrganization.value = data.organization;
            if (data.default_branch) currentBranch.value = data.default_branch;
            if (data.accessible_branches) accessibleBranches.value = data.accessible_branches;

            // Keep updated session in storage
            const sessionData = {
              user: data.user,
              organization: currentOrganization.value,
              default_branch: currentBranch.value,
              accessible_branches: accessibleBranches.value,
              token: parsed.token,
            };
            localStorage.setItem('hms_portal_session', JSON.stringify(sessionData));
          } else if (data && data.authenticated === false) {
            handleSignOut();
          }
        }).catch(() => {
          // Keep local cached session on connection error
        });
      } else {
        localStorage.removeItem('hms_portal_session');
        sessionStorage.removeItem('hms_auth_token');
      }
    } catch {
      localStorage.removeItem('hms_portal_session');
      sessionStorage.removeItem('hms_auth_token');
    }
  }

  // Load demo personas for modal
  try {
    const res = await fetch('/api/v1/auth/demo-accounts');
    const data = await res.json();
    if (data.demo_accounts) {
      demoPersonas.value = data.demo_accounts;
    }
  } catch (err) {
    console.error('Failed to load demo accounts:', err);
  }

  // Ensure currentView is allowed for active role
  if (currentUser.value && !canAccessView(currentView.value)) {
    currentView.value = getDefaultViewForRole(currentUser.value);
  }
});

watch(currentUser, (newUser) => {
  if (newUser && !canAccessView(currentView.value)) {
    currentView.value = getDefaultViewForRole(newUser);
  }
});

async function exitImpersonation() {
  const impId = sessionStorage.getItem('hms_impersonation_id');
  exitingImpersonation.value = true;
  try {
    if (impId) {
      await fetch('/api/v1/super-admin/impersonation/stop', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({ impersonation_id: impId })
      });
    }
  } catch (err) {
    console.error('Failed to gracefully exit impersonation:', err);
  } finally {
    sessionStorage.removeItem('hms_impersonation_id');
    sessionStorage.removeItem('hms_impersonation_token');
    sessionStorage.removeItem('hms_impersonated_hospital');
    sessionStorage.removeItem('hms_impersonation_expires');
    isImpersonating.value = false;
    exitingImpersonation.value = false;
    window.location.reload();
  }
}

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
