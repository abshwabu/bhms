<template>
  <div class="space-y-6">
    <!-- Top Action Bar & Summary Stats -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
        </div>
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-xl font-bold text-slate-900">Ambulance Dispatch & Fleet Operations</h1>
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 animate-pulse">
              <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
              Live Telemetry Online
            </span>
          </div>
          <p class="text-xs text-slate-500 mt-0.5">Real-time GPS tracking, mission dispatching, and rapid casualty transit</p>
        </div>
      </div>

      <div class="flex items-center gap-2.5">
        <button
          @click="fetchFleetOverview"
          :disabled="loading"
          class="px-3.5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
        >
          <svg class="w-4 h-4" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Refresh
        </button>

        <button
          @click="openTelemetryModal(null)"
          class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold shadow-sm transition flex items-center gap-2 cursor-pointer"
        >
          <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          Simulate GPS Ping
        </button>

        <button
          @click="openDispatchModal"
          class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-sm transition flex items-center gap-2 cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Dispatch Mission
        </button>
      </div>
    </div>

    <!-- Fleet Status KPI Metric Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
      <!-- Total Fleet -->
      <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Fleet</span>
          <span class="w-2 h-2 rounded-full bg-slate-400"></span>
        </div>
        <div class="text-2xl font-black text-slate-900 mt-2">{{ overview.total_ambulances }}</div>
        <div class="text-[11px] text-slate-400 mt-0.5">Assigned to Branch</div>
      </div>

      <!-- Available Units -->
      <div class="bg-emerald-50/60 p-4 rounded-xl border border-emerald-200/80 shadow-sm">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold text-emerald-800 uppercase tracking-wider">Available</span>
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
        </div>
        <div class="text-2xl font-black text-emerald-700 mt-2">{{ overview.available_count }}</div>
        <div class="text-[11px] text-emerald-600 mt-0.5">Ready for dispatch</div>
      </div>

      <!-- In Mission / Active Dispatches -->
      <div class="bg-amber-50/60 p-4 rounded-xl border border-amber-200/80 shadow-sm">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold text-amber-800 uppercase tracking-wider">Active Missions</span>
          <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-ping"></span>
        </div>
        <div class="text-2xl font-black text-amber-700 mt-2">{{ overview.dispatched_count }}</div>
        <div class="text-[11px] text-amber-600 mt-0.5">En route or at scene</div>
      </div>

      <!-- Arrived Hospital -->
      <div class="bg-blue-50/60 p-4 rounded-xl border border-blue-200/80 shadow-sm">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold text-blue-800 uppercase tracking-wider">Arrived ED</span>
          <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
        </div>
        <div class="text-2xl font-black text-blue-700 mt-2">{{ overview.arrived_count }}</div>
        <div class="text-[11px] text-blue-600 mt-0.5">Patient handover active</div>
      </div>

      <!-- Maintenance / Out of Service -->
      <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Maintenance</span>
          <span class="w-2 h-2 rounded-full bg-slate-300"></span>
        </div>
        <div class="text-2xl font-black text-slate-700 mt-2">{{ overview.maintenance_count }}</div>
        <div class="text-[11px] text-slate-400 mt-0.5">Service & sterilization</div>
      </div>
    </div>

    <!-- Live GPS Tracking Radar & Unit Cards Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Live GPS Radar Map (Interactive SVG/Canvas coordinate monitor) -->
      <div class="lg:col-span-2 bg-slate-950 rounded-2xl p-5 border border-slate-800 shadow-lg text-white flex flex-col justify-between relative overflow-hidden">
        <!-- Map Header overlay -->
        <div class="flex items-center justify-between z-10">
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full bg-emerald-500 animate-ping"></div>
            <h2 class="text-sm font-bold tracking-wide uppercase font-mono text-emerald-400">Live GPS Telemetry Radar & CAD Tracking</h2>
          </div>
          <div class="flex items-center gap-3 text-xs font-mono text-slate-400">
            <span>Base: <span class="text-white font-semibold">ED Central Hospital</span> (9.0300, 38.7400)</span>
            <span class="px-2 py-0.5 rounded bg-slate-800 text-[10px] text-emerald-300">GPS ACCURACY: &plusmn;3m</span>
          </div>
        </div>

        <!-- Visual Coordinate Map View -->
        <div class="relative w-full h-[360px] my-4 rounded-xl bg-slate-900/90 border border-slate-800 overflow-hidden flex items-center justify-center select-none">
          <!-- Grid Background lines -->
          <svg class="absolute inset-0 w-full h-full opacity-20 pointer-events-none" xmlns="http://www.w3.org/2000/svg">
            <defs>
              <pattern id="radar-grid" width="40" height="40" patternUnits="userSpaceOnUse">
                <path d="M 40 0 L 0 0 0 40" fill="none" stroke="#38bdf8" stroke-width="0.5" />
              </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#radar-grid)" />
            <!-- Concentric radar circles -->
            <circle cx="50%" cy="50%" r="60" fill="none" stroke="#38bdf8" stroke-width="0.7" stroke-dasharray="3,3" />
            <circle cx="50%" cy="50%" r="120" fill="none" stroke="#38bdf8" stroke-width="0.7" stroke-dasharray="4,4" />
            <circle cx="50%" cy="50%" r="170" fill="none" stroke="#38bdf8" stroke-width="0.7" stroke-dasharray="5,5" />
            <line x1="0" y1="50%" x2="100%" y2="50%" stroke="#38bdf8" stroke-width="0.5" stroke-dasharray="2,4" />
            <line x1="50%" y1="0" x2="50%" y2="100%" stroke="#38bdf8" stroke-width="0.5" stroke-dasharray="2,4" />
          </svg>

          <!-- Central Hospital Base Station Marker -->
          <div class="absolute z-20 flex flex-col items-center pointer-events-none" style="top: 50%; left: 50%; transform: translate(-50%, -50%);">
            <div class="relative flex items-center justify-center">
              <span class="w-10 h-10 rounded-full bg-rose-500/20 animate-ping absolute"></span>
              <div class="w-8 h-8 rounded-full bg-rose-600 text-white flex items-center justify-center font-black text-xs shadow-lg border-2 border-white">
                H
              </div>
            </div>
            <span class="mt-1 px-1.5 py-0.5 rounded bg-slate-900/90 text-[10px] font-mono font-bold text-rose-300 border border-rose-500/30">
              ED Trauma Base
            </span>
          </div>

          <!-- Dynamic Ambulance Markers plotted from GPS Coordinates -->
          <template v-for="unit in fleetList" :key="unit.id">
            <div
              v-if="unit.current_latitude && unit.current_longitude"
              class="absolute z-30 cursor-pointer transition-all duration-700 hover:scale-125"
              :style="getMarkerPosition(unit.current_latitude, unit.current_longitude)"
              @click="selectedAmbulance = unit"
            >
              <div class="relative group flex flex-col items-center -translate-x-1/2 -translate-y-1/2">
                <!-- Vehicle Status Pulse Circle -->
                <span
                  v-if="unit.status !== 'available' && unit.status !== 'maintenance'"
                  class="w-8 h-8 rounded-full absolute -top-1 opacity-75 animate-ping"
                  :class="getUnitStatusColor(unit.status).pulse"
                ></span>

                <!-- Vehicle Icon Container with Heading Angle -->
                <div
                  class="w-7 h-7 rounded-lg flex items-center justify-center shadow-md border text-white transition-transform"
                  :class="getUnitStatusColor(unit.status).badge"
                  :style="{ transform: `rotate(${unit.heading || 0}deg)` }"
                >
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                  </svg>
                </div>

                <!-- Call Sign Tag -->
                <div class="mt-1 px-1.5 py-0.5 rounded bg-slate-900/95 border border-slate-700 text-[10px] font-mono font-bold whitespace-nowrap flex items-center gap-1 shadow-sm">
                  <span class="w-1.5 h-1.5 rounded-full" :class="getUnitStatusColor(unit.status).dot"></span>
                  <span class="text-white">{{ unit.call_sign }}</span>
                  <span v-if="unit.speed_kmh > 0" class="text-emerald-400 text-[9px]">{{ Math.round(unit.speed_kmh) }}km/h</span>
                </div>

                <!-- Hover Tooltip -->
                <div class="hidden group-hover:block absolute bottom-full mb-2 bg-slate-900 text-white text-xs rounded-lg p-2 shadow-2xl border border-slate-700 w-44 z-40">
                  <div class="font-bold flex items-center justify-between">
                    <span>{{ unit.call_sign }}</span>
                    <span class="text-[10px] uppercase font-mono text-emerald-400">{{ unit.status.replace('_', ' ') }}</span>
                  </div>
                  <div class="text-[10px] text-slate-400 mt-1">Type: {{ unit.ambulance_type }}</div>
                  <div class="text-[10px] text-slate-400">Driver: {{ unit.assigned_driver_name || 'Unassigned' }}</div>
                  <div class="text-[10px] text-slate-400">Paramedic: {{ unit.assigned_paramedic_name || 'Unassigned' }}</div>
                  <div class="text-[10px] text-slate-300 mt-1 font-mono">Speed: {{ unit.speed_kmh || 0 }} km/h | Fuel: {{ unit.fuel_percentage || 100 }}%</div>
                </div>
              </div>
            </div>
          </template>
        </div>

        <!-- Radar Bottom Controls -->
        <div class="flex flex-wrap items-center justify-between text-xs text-slate-400 border-t border-slate-800/80 pt-3 gap-2">
          <div class="flex items-center gap-4">
            <div class="flex items-center gap-1.5">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
              <span class="text-[11px]">Available</span>
            </div>
            <div class="flex items-center gap-1.5">
              <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
              <span class="text-[11px]">En Route / Scene</span>
            </div>
            <div class="flex items-center gap-1.5">
              <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
              <span class="text-[11px]">Arrived Hospital</span>
            </div>
            <div class="flex items-center gap-1.5">
              <span class="w-2.5 h-2.5 rounded-full bg-slate-500"></span>
              <span class="text-[11px]">Maintenance</span>
            </div>
          </div>
          <div class="text-[11px] font-mono text-slate-500">
            Coordinates: Central Hospital ED &plusmn; 5km Radius
          </div>
        </div>
      </div>

      <!-- Unit Inspector & Fleet List Sidebar -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-col justify-between space-y-4">
        <div>
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
              <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
              Fleet Unit Inspector
            </h3>
            <span class="text-xs font-mono text-slate-400">{{ fleetList.length }} Registered Units</span>
          </div>

          <!-- Unit Selector Pills -->
          <div class="grid grid-cols-2 gap-2 mt-3">
            <button
              v-for="unit in fleetList"
              :key="unit.id"
              @click="selectedAmbulance = unit"
              :class="selectedAmbulance?.id === unit.id ? 'border-blue-600 bg-blue-50/60 ring-1 ring-blue-600' : 'border-slate-200 bg-white hover:bg-slate-50'"
              class="p-2.5 rounded-xl border text-left transition cursor-pointer flex flex-col justify-between"
            >
              <div class="flex items-center justify-between">
                <span class="font-bold text-xs text-slate-900">{{ unit.call_sign }}</span>
                <span class="w-2 h-2 rounded-full" :class="getUnitStatusColor(unit.status).dot"></span>
              </div>
              <div class="text-[10px] text-slate-500 uppercase font-mono mt-1">{{ unit.ambulance_type }}</div>
              <div class="text-[10px] font-semibold mt-0.5 capitalize" :class="getUnitStatusColor(unit.status).text">
                {{ unit.status.replace(/_/g, ' ') }}
              </div>
            </button>
          </div>
        </div>

        <!-- Selected Unit Detail Sheet -->
        <div v-if="selectedAmbulance" class="bg-slate-50 rounded-xl p-3.5 border border-slate-200 space-y-3">
          <div class="flex items-center justify-between">
            <div>
              <span class="text-sm font-black text-slate-900">{{ selectedAmbulance.call_sign }}</span>
              <span class="text-xs text-slate-500 block">{{ selectedAmbulance.model }} &bull; {{ selectedAmbulance.plate_number }}</span>
            </div>
            <span
              class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
              :class="getUnitStatusColor(selectedAmbulance.status).badgeClass"
            >
              {{ selectedAmbulance.status.replace(/_/g, ' ') }}
            </span>
          </div>

          <!-- Telemetry & Crew Specs -->
          <div class="grid grid-cols-2 gap-2 text-xs">
            <div class="bg-white p-2 rounded-lg border border-slate-200">
              <span class="text-[10px] text-slate-400 uppercase font-semibold">Driver</span>
              <div class="font-bold text-slate-800 truncate">{{ selectedAmbulance.assigned_driver_name || 'Unassigned' }}</div>
            </div>
            <div class="bg-white p-2 rounded-lg border border-slate-200">
              <span class="text-[10px] text-slate-400 uppercase font-semibold">Paramedic</span>
              <div class="font-bold text-slate-800 truncate">{{ selectedAmbulance.assigned_paramedic_name || 'Unassigned' }}</div>
            </div>
            <div class="bg-white p-2 rounded-lg border border-slate-200">
              <span class="text-[10px] text-slate-400 uppercase font-semibold">Live Speed</span>
              <div class="font-bold text-slate-800">{{ selectedAmbulance.speed_kmh || 0 }} km/h</div>
            </div>
            <div class="bg-white p-2 rounded-lg border border-slate-200">
              <span class="text-[10px] text-slate-400 uppercase font-semibold">Fuel Tank</span>
              <div class="font-bold text-slate-800">{{ selectedAmbulance.fuel_percentage || 100 }}%</div>
            </div>
          </div>

          <!-- Equipment Inventory Chips -->
          <div>
            <span class="text-[10px] text-slate-500 uppercase font-bold tracking-wider">Equipped Systems:</span>
            <div class="flex flex-wrap gap-1 mt-1">
              <span
                v-for="item in (selectedAmbulance.equipment || [])"
                :key="item"
                class="px-2 py-0.5 rounded-md bg-white border border-slate-200 text-[10px] font-medium text-slate-700"
              >
                {{ item }}
              </span>
              <span v-if="!selectedAmbulance.equipment?.length" class="text-slate-400 text-[11px] italic">
                Standard Trauma Kit
              </span>
            </div>
          </div>

          <!-- Quick Telemetry Update Trigger -->
          <div class="pt-1 flex gap-2">
            <button
              @click="openTelemetryModal(selectedAmbulance)"
              class="w-full py-2 bg-white hover:bg-slate-100 border border-slate-300 rounded-xl text-xs font-bold text-slate-700 transition flex items-center justify-center gap-1.5 cursor-pointer"
            >
              <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Ping GPS Update
            </button>

            <button
              v-if="selectedAmbulance.status === 'available'"
              @click="openDispatchModalWithUnit(selectedAmbulance)"
              class="w-full py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer shadow-sm"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
              Dispatch Now
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Active Dispatch Missions Worklist -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-5 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
            <span>Active Dispatch Operations & Stepper</span>
            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700">
              {{ dispatches.length }} Missions
            </span>
          </h2>
          <p class="text-xs text-slate-500 mt-0.5">Near real-time dispatch state transitions: Dispatched &rarr; En Route &rarr; Scene &rarr; ED Handover</p>
        </div>

        <!-- Filter Controls -->
        <div class="flex items-center gap-3">
          <select
            v-model="filterStatus"
            @change="fetchDispatches"
            class="px-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-white text-slate-700 focus:ring-2 focus:ring-blue-500 cursor-pointer"
          >
            <option value="">All Mission Statuses</option>
            <option value="dispatched">Dispatched</option>
            <option value="en_route_scene">En Route to Scene</option>
            <option value="at_scene">At Scene</option>
            <option value="en_route_hospital">En Route to ED</option>
            <option value="arrived_hospital">Arrived ED</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>

          <select
            v-model="filterPriority"
            @change="fetchDispatches"
            class="px-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-white text-slate-700 focus:ring-2 focus:ring-blue-500 cursor-pointer"
          >
            <option value="">All Priorities</option>
            <option value="code_red">Code Red (Life Threat)</option>
            <option value="code_yellow">Code Yellow (Urgent)</option>
            <option value="code_green">Code Green (Standard)</option>
          </select>
        </div>
      </div>

      <!-- Dispatches Cards / List -->
      <div v-if="loadingDispatches" class="p-12 text-center text-slate-400 text-sm">
        <svg class="w-8 h-8 mx-auto animate-spin text-blue-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        Loading active missions & telemetry...
      </div>

      <div v-else-if="dispatches.length === 0" class="p-12 text-center text-slate-400">
        <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <div class="font-bold text-slate-600">No active dispatch missions found</div>
        <p class="text-xs text-slate-400 mt-1">All ambulances are currently available or idle at base station.</p>
      </div>

      <div v-else class="divide-y divide-slate-100">
        <div
          v-for="disp in dispatches"
          :key="disp.id"
          class="p-5 hover:bg-slate-50/70 transition"
        >
          <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <!-- Left Info -->
            <div class="space-y-1.5">
              <div class="flex items-center gap-2.5">
                <span class="font-mono font-bold text-sm text-slate-900">{{ disp.dispatch_number }}</span>
                
                <!-- Priority Badge -->
                <span
                  class="px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider flex items-center gap-1"
                  :class="getPriorityClass(disp.priority)"
                >
                  <span v-if="disp.priority === 'code_red'" class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
                  {{ formatPriority(disp.priority) }}
                </span>

                <!-- Unit Assigned -->
                <span class="px-2.5 py-0.5 rounded-lg bg-slate-100 border border-slate-200 text-xs font-bold text-slate-700">
                  {{ disp.ambulance?.call_sign || 'Unit ' + (disp.ambulance?.vehicle_number || 'N/A') }}
                </span>

                <span v-if="disp.response_time_minutes !== null" class="text-xs font-mono text-slate-500">
                  Elapsed: <strong class="text-slate-800">{{ disp.response_time_minutes }}m</strong>
                </span>
              </div>

              <!-- Nature & Notes -->
              <div class="text-sm font-semibold text-slate-800">
                {{ disp.nature_of_emergency }}
              </div>
              <div class="text-xs text-slate-500 flex flex-wrap items-center gap-x-4 gap-y-1">
                <span><strong>Pickup:</strong> {{ disp.pickup_address }}</span>
                <span><strong>Destination:</strong> {{ disp.destination_address || 'Main ED Base' }}</span>
                <span v-if="disp.caller_name"><strong>Caller:</strong> {{ disp.caller_name }} ({{ disp.caller_phone || 'No phone' }})</span>
              </div>
              <div v-if="disp.patient_condition_notes" class="text-xs text-amber-700 bg-amber-50 p-1.5 rounded border border-amber-200/60 inline-block">
                <strong>Vitals/Notes:</strong> {{ disp.patient_condition_notes }}
              </div>
            </div>

            <!-- Right: Interactive Lifecycle Stepper & Advance Action -->
            <div class="flex flex-col items-end gap-3 min-w-[320px]">
              <!-- Stepper Pills -->
              <div class="flex items-center gap-1 text-[10px] font-semibold">
                <span
                  class="px-2 py-1 rounded"
                  :class="isStepPassed(disp.status, 'dispatched') ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-400'"
                >
                  Dispatched
                </span>
                <span class="text-slate-300">&rarr;</span>
                <span
                  class="px-2 py-1 rounded"
                  :class="isStepPassed(disp.status, 'en_route_scene') ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-400'"
                >
                  En Route
                </span>
                <span class="text-slate-300">&rarr;</span>
                <span
                  class="px-2 py-1 rounded"
                  :class="isStepPassed(disp.status, 'at_scene') ? 'bg-amber-600 text-white' : 'bg-slate-100 text-slate-400'"
                >
                  At Scene
                </span>
                <span class="text-slate-300">&rarr;</span>
                <span
                  class="px-2 py-1 rounded"
                  :class="isStepPassed(disp.status, 'en_route_hospital') ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-400'"
                >
                  ED Transit
                </span>
                <span class="text-slate-300">&rarr;</span>
                <span
                  class="px-2 py-1 rounded"
                  :class="disp.status === 'arrived_hospital' || disp.status === 'completed' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-400'"
                >
                  Arrived ED
                </span>
              </div>

              <!-- Quick Next-Step Transition Button -->
              <div class="flex items-center gap-2">
                <button
                  v-if="getNextStatus(disp.status)"
                  @click="updateDispatchStatus(disp, getNextStatus(disp.status))"
                  :disabled="updatingStatusId === disp.id"
                  class="px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-sm disabled:opacity-50"
                >
                  <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                  </svg>
                  Advance &rarr; {{ formatStatusLabel(getNextStatus(disp.status)) }}
                </button>

                <button
                  v-if="disp.status === 'arrived_hospital'"
                  @click="updateDispatchStatus(disp, 'completed')"
                  :disabled="updatingStatusId === disp.id"
                  class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-sm disabled:opacity-50"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  Complete Mission
                </button>

                <!-- Cancel Dispatch Option -->
                <button
                  v-if="disp.is_active"
                  @click="promptCancelDispatch(disp)"
                  class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition cursor-pointer"
                  title="Cancel Dispatch Mission"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL 1: Create Ambulance Dispatch Mission -->
    <div
      v-if="isDispatchModalOpen"
      class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-slate-100 space-y-5 animate-in fade-in zoom-in duration-200">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center font-bold">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <h3 class="font-bold text-slate-900 text-base">Dispatch Ambulance Mission</h3>
          </div>
          <button @click="isDispatchModalOpen = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="submitDispatchMission" class="space-y-4">
          <!-- Ambulance Unit Selector -->
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
              Select Ambulance Unit *
            </label>
            <select
              v-model="dispatchForm.ambulance_id"
              required
              class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-white text-slate-800 focus:ring-2 focus:ring-blue-500 font-medium cursor-pointer"
            >
              <option value="" disabled>-- Select Fleet Unit --</option>
              <option
                v-for="unit in fleetList"
                :key="unit.id"
                :value="unit.id"
                :disabled="unit.status !== 'available'"
              >
                {{ unit.call_sign }} ({{ unit.ambulance_type }}) &mdash; {{ unit.status.toUpperCase() }} {{ unit.status !== 'available' ? '(BUSY)' : ' &bull; READY' }}
              </option>
            </select>
          </div>

          <!-- Priority Classification -->
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
              Dispatch Priority Tier *
            </label>
            <div class="grid grid-cols-3 gap-2">
              <label
                :class="dispatchForm.priority === 'code_red' ? 'bg-rose-50 border-rose-500 text-rose-800 ring-2 ring-rose-400' : 'bg-white border-slate-200 text-slate-700'"
                class="p-2.5 rounded-xl border flex flex-col items-center justify-center cursor-pointer transition text-center"
              >
                <input type="radio" v-model="dispatchForm.priority" value="code_red" class="sr-only" />
                <span class="text-xs font-black uppercase">Code Red</span>
                <span class="text-[10px] text-rose-600 mt-0.5">Life Threat / ALS</span>
              </label>

              <label
                :class="dispatchForm.priority === 'code_yellow' ? 'bg-amber-50 border-amber-500 text-amber-800 ring-2 ring-amber-400' : 'bg-white border-slate-200 text-slate-700'"
                class="p-2.5 rounded-xl border flex flex-col items-center justify-center cursor-pointer transition text-center"
              >
                <input type="radio" v-model="dispatchForm.priority" value="code_yellow" class="sr-only" />
                <span class="text-xs font-black uppercase">Code Yellow</span>
                <span class="text-[10px] text-amber-600 mt-0.5">Urgent Response</span>
              </label>

              <label
                :class="dispatchForm.priority === 'code_green' ? 'bg-emerald-50 border-emerald-500 text-emerald-800 ring-2 ring-emerald-400' : 'bg-white border-slate-200 text-slate-700'"
                class="p-2.5 rounded-xl border flex flex-col items-center justify-center cursor-pointer transition text-center"
              >
                <input type="radio" v-model="dispatchForm.priority" value="code_green" class="sr-only" />
                <span class="text-xs font-black uppercase">Code Green</span>
                <span class="text-[10px] text-emerald-600 mt-0.5">Standard / BLS</span>
              </label>
            </div>
          </div>

          <!-- Nature of Emergency -->
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
              Nature of Emergency / Chief Complaint *
            </label>
            <input
              type="text"
              v-model="dispatchForm.nature_of_emergency"
              required
              placeholder="e.g. 52M Severe chest pain radiating to jaw, diaphoresis"
              class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 text-slate-800 focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <!-- Pickup Location & Coordinates -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="sm:col-span-3">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                Pickup Address *
              </label>
              <input
                type="text"
                v-model="dispatchForm.pickup_address"
                required
                placeholder="Street, Landmark, District (e.g. Bole Medhanialem Mall, Gate 2)"
                class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 text-slate-800 focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Pickup Lat</label>
              <input
                type="number"
                step="0.0001"
                v-model.number="dispatchForm.pickup_latitude"
                placeholder="9.0150"
                class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200"
              />
            </div>
            <div>
              <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Pickup Lng</label>
              <input
                type="number"
                step="0.0001"
                v-model.number="dispatchForm.pickup_longitude"
                placeholder="38.7620"
                class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200"
              />
            </div>
            <div class="flex items-end">
              <button
                type="button"
                @click="populateSampleCoordinates"
                class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-[11px] font-semibold transition cursor-pointer"
              >
                Autofill City Pin
              </button>
            </div>
          </div>

          <!-- Caller Information -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Caller Name</label>
              <input
                type="text"
                v-model="dispatchForm.caller_name"
                placeholder="First & Last Name"
                class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200"
              />
            </div>
            <div>
              <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Caller Phone</label>
              <input
                type="text"
                v-model="dispatchForm.caller_phone"
                placeholder="+251 911 000 000"
                class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200"
              />
            </div>
          </div>

          <!-- Notes -->
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
              Field Vitals / Patient Notes (Optional)
            </label>
            <textarea
              rows="2"
              v-model="dispatchForm.patient_condition_notes"
              placeholder="Unconscious, bleeding controlled, oxygen mask in transit..."
              class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 text-slate-800 focus:ring-2 focus:ring-blue-500"
            ></textarea>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
            <button
              type="button"
              @click="isDispatchModalOpen = false"
              class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition cursor-pointer"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="submittingDispatch"
              class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-md transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
            >
              <svg v-if="submittingDispatch" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              Confirm & Dispatch Ambulance
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 2: Live GPS Telemetry Simulator -->
    <div
      v-if="isTelemetryModalOpen"
      class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-5 animate-in fade-in zoom-in duration-200">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>
            <div>
              <h3 class="font-bold text-slate-900 text-base">GPS Telemetry Simulator</h3>
              <p class="text-xs text-slate-500">Inject real-time coordinates, velocity, and orientation</p>
            </div>
          </div>
          <button @click="isTelemetryModalOpen = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="submitTelemetryPing" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
              Select Mobile Unit *
            </label>
            <select
              v-model="telemetryForm.ambulance_id"
              required
              @change="onTelemetryUnitSelect"
              class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-white font-medium"
            >
              <option
                v-for="unit in fleetList"
                :key="unit.id"
                :value="unit.id"
              >
                {{ unit.call_sign }} &mdash; Current: ({{ unit.current_latitude?.toFixed(4) || '9.0300' }}, {{ unit.current_longitude?.toFixed(4) || '38.7400' }})
              </option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Latitude</label>
              <input
                type="number"
                step="0.0001"
                required
                v-model.number="telemetryForm.latitude"
                class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200"
              />
            </div>
            <div>
              <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Longitude</label>
              <input
                type="number"
                step="0.0001"
                required
                v-model.number="telemetryForm.longitude"
                class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200"
              />
            </div>
            <div>
              <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Speed (km/h)</label>
              <input
                type="number"
                min="0"
                max="180"
                v-model.number="telemetryForm.speed_kmh"
                class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200"
              />
            </div>
            <div>
              <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Heading (0&deg; - 360&deg;)</label>
              <input
                type="number"
                min="0"
                max="360"
                v-model.number="telemetryForm.heading"
                class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200"
              />
            </div>
          </div>

          <!-- Quick Move Step Buttons (Simulation Presets) -->
          <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 space-y-2">
            <span class="text-[11px] font-bold text-slate-700 uppercase">One-Click Simulation Presets:</span>
            <div class="grid grid-cols-2 gap-2">
              <button
                type="button"
                @click="simulateStep('hospital')"
                class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 text-[11px] font-semibold hover:bg-slate-100 text-slate-700 transition text-left"
              >
                &rarr; Advance toward Hospital Base
              </button>
              <button
                type="button"
                @click="simulateStep('scene')"
                class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 text-[11px] font-semibold hover:bg-slate-100 text-slate-700 transition text-left"
              >
                &rarr; Advance toward Scene
              </button>
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
            <button
              type="button"
              @click="isTelemetryModalOpen = false"
              class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition cursor-pointer"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="submittingTelemetry"
              class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
            >
              <svg v-if="submittingTelemetry" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              Transmit Telemetry
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { showAlert, showPrompt } from '../../Services/modalDialog';

const props = defineProps({
  branchId: {
    type: String,
    default: null,
  },
});

const loading = ref(false);
const loadingDispatches = ref(false);
const updatingStatusId = ref(null);

const overview = ref({
  total_ambulances: 0,
  available_count: 0,
  dispatched_count: 0,
  arrived_count: 0,
  maintenance_count: 0,
});

const fleetList = ref([]);
const dispatches = ref([]);
const selectedAmbulance = ref(null);

const filterStatus = ref('');
const filterPriority = ref('');

// Modals
const isDispatchModalOpen = ref(false);
const submittingDispatch = ref(false);
const dispatchForm = ref({
  ambulance_id: '',
  priority: 'code_red',
  nature_of_emergency: '',
  pickup_address: '',
  pickup_latitude: 9.015,
  pickup_longitude: 38.762,
  caller_name: '',
  caller_phone: '',
  patient_condition_notes: '',
});

const isTelemetryModalOpen = ref(false);
const submittingTelemetry = ref(false);
const telemetryForm = ref({
  ambulance_id: '',
  latitude: 9.0300,
  longitude: 38.7400,
  speed_kmh: 45,
  heading: 90,
  fuel_percentage: 95,
});

let refreshTimer = null;

// Map Coordinates Range Reference (Centered around ED 9.0300, 38.7400)
const CENTER_LAT = 9.0300;
const CENTER_LNG = 38.7400;
const LAT_DELTA = 0.0400; // ~4.4km north/south range
const LNG_DELTA = 0.0500; // ~5.5km east/west range

function getMarkerPosition(lat, lng) {
  // Convert lat/lng to percentage offset from center
  const yPercent = 50 - ((lat - CENTER_LAT) / LAT_DELTA) * 50;
  const xPercent = 50 + ((lng - CENTER_LNG) / LNG_DELTA) * 50;

  // Clamp within 5% to 95% of container
  const clampedX = Math.max(5, Math.min(95, xPercent));
  const clampedY = Math.max(8, Math.min(92, yPercent));

  return {
    left: `${clampedX}%`,
    top: `${clampedY}%`,
  };
}

function getUnitStatusColor(status) {
  switch (status) {
    case 'available':
      return {
        dot: 'bg-emerald-500',
        text: 'text-emerald-700',
        badge: 'bg-emerald-600 border-emerald-400',
        badgeClass: 'bg-emerald-100 text-emerald-800',
        pulse: 'bg-emerald-400',
      };
    case 'dispatched':
    case 'en_route_scene':
    case 'en_route_hospital':
      return {
        dot: 'bg-amber-500',
        text: 'text-amber-700',
        badge: 'bg-amber-600 border-amber-400',
        badgeClass: 'bg-amber-100 text-amber-800',
        pulse: 'bg-amber-400',
      };
    case 'at_scene':
      return {
        dot: 'bg-rose-500',
        text: 'text-rose-700',
        badge: 'bg-rose-600 border-rose-400',
        badgeClass: 'bg-rose-100 text-rose-800',
        pulse: 'bg-rose-400',
      };
    case 'arrived_hospital':
      return {
        dot: 'bg-blue-500',
        text: 'text-blue-700',
        badge: 'bg-blue-600 border-blue-400',
        badgeClass: 'bg-blue-100 text-blue-800',
        pulse: 'bg-blue-400',
      };
    default:
      return {
        dot: 'bg-slate-400',
        text: 'text-slate-600',
        badge: 'bg-slate-600 border-slate-400',
        badgeClass: 'bg-slate-100 text-slate-800',
        pulse: 'bg-slate-400',
      };
  }
}

function formatPriority(p) {
  if (p === 'code_red') return 'Code Red';
  if (p === 'code_yellow') return 'Code Yellow';
  if (p === 'code_green') return 'Code Green';
  return p;
}

function getPriorityClass(p) {
  if (p === 'code_red') return 'bg-rose-100 text-rose-800 border border-rose-300';
  if (p === 'code_yellow') return 'bg-amber-100 text-amber-800 border border-amber-300';
  return 'bg-emerald-100 text-emerald-800 border border-emerald-300';
}

function isStepPassed(current, step) {
  const steps = ['dispatched', 'en_route_scene', 'at_scene', 'en_route_hospital', 'arrived_hospital', 'completed'];
  const currentIndex = steps.indexOf(current);
  const stepIndex = steps.indexOf(step);
  return currentIndex >= stepIndex;
}

function getNextStatus(current) {
  const map = {
    dispatched: 'en_route_scene',
    en_route_scene: 'at_scene',
    at_scene: 'en_route_hospital',
    en_route_hospital: 'arrived_hospital',
  };
  return map[current] || null;
}

function formatStatusLabel(st) {
  if (!st) return '';
  const map = {
    en_route_scene: 'En Route to Scene',
    at_scene: 'Arrived at Scene',
    en_route_hospital: 'En Route to ED',
    arrived_hospital: 'Arrived at ED',
    completed: 'Completed',
  };
  return map[st] || st.replace(/_/g, ' ');
}

async function fetchFleetOverview() {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (props.branchId) params.append('branch_id', props.branchId);

    const res = await fetch(`/api/v1/emergency/ambulances/overview?${params.toString()}`);
    const json = await res.json();
    if (json.success && json.data) {
      overview.value = {
        total_ambulances: json.data.total_ambulances || 0,
        available_count: json.data.available_count || 0,
        dispatched_count: json.data.dispatched_count || 0,
        arrived_count: json.data.arrived_count || 0,
        maintenance_count: json.data.maintenance_count || 0,
      };
      fleetList.value = json.data.fleet || [];

      if (!selectedAmbulance.value && fleetList.value.length > 0) {
        selectedAmbulance.value = fleetList.value[0];
      } else if (selectedAmbulance.value) {
        // Refresh selected unit object
        const updated = fleetList.value.find(u => u.id === selectedAmbulance.value.id);
        if (updated) selectedAmbulance.value = updated;
      }
    }
  } catch (err) {
    console.error('Failed to fetch ambulance fleet overview:', err);
  } finally {
    loading.value = false;
  }
}

async function fetchDispatches() {
  loadingDispatches.value = true;
  try {
    const params = new URLSearchParams();
    if (props.branchId) params.append('branch_id', props.branchId);
    if (filterStatus.value) params.append('status', filterStatus.value);
    if (filterPriority.value) params.append('priority', filterPriority.value);

    const res = await fetch(`/api/v1/emergency/dispatches?${params.toString()}`);
    const json = await res.json();
    if (json.success && json.data) {
      dispatches.value = json.data;
    }
  } catch (err) {
    console.error('Failed to fetch ambulance dispatches:', err);
  } finally {
    loadingDispatches.value = false;
  }
}

async function updateDispatchStatus(dispatch, newStatus) {
  updatingStatusId.value = dispatch.id;
  try {
    const res = await fetch(`/api/v1/emergency/dispatches/${dispatch.id}/status`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        status: newStatus,
        notes: `Status advanced to ${newStatus} via CAD console.`,
      }),
    });

    const json = await res.json();
    if (json.success) {
      await fetchFleetOverview();
      await fetchDispatches();
    } else {
      await showAlert(json.message || 'Failed to update dispatch status.', { status: 'error' });
    }
  } catch (err) {
    console.error('Status update error:', err);
    await showAlert('Error communicating with dispatch service.', { status: 'error' });
  } finally {
    updatingStatusId.value = null;
  }
}

async function promptCancelDispatch(dispatch) {
  const reason = await showPrompt('Please enter cancellation reason for this mission:', '', {
    title: 'Cancel Dispatch Mission',
    placeholder: 'Cancellation reason...',
  });
  if (reason === null) return; // user pressed cancel

  try {
    const res = await fetch(`/api/v1/emergency/dispatches/${dispatch.id}/status`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        status: 'cancelled',
        notes: reason || 'Cancelled by dispatcher',
      }),
    });
    const json = await res.json();
    if (json.success) {
      await fetchFleetOverview();
      await fetchDispatches();
    }
  } catch (err) {
    console.error('Cancel dispatch error:', err);
  }
}

function openDispatchModal() {
  const availableUnit = fleetList.value.find(u => u.status === 'available');
  dispatchForm.value = {
    ambulance_id: availableUnit ? availableUnit.id : (fleetList.value[0]?.id || ''),
    priority: 'code_red',
    nature_of_emergency: '',
    pickup_address: '',
    pickup_latitude: 9.0150,
    pickup_longitude: 38.7620,
    caller_name: '',
    caller_phone: '',
    patient_condition_notes: '',
  };
  isDispatchModalOpen.value = true;
}

function openDispatchModalWithUnit(unit) {
  dispatchForm.value.ambulance_id = unit.id;
  dispatchForm.value.priority = 'code_red';
  isDispatchModalOpen.value = true;
}

function populateSampleCoordinates() {
  dispatchForm.value.pickup_address = 'Bole International Airport, Terminal 2 Gate 4';
  dispatchForm.value.pickup_latitude = 8.9835;
  dispatchForm.value.pickup_longitude = 38.7995;
  dispatchForm.value.caller_name = 'Airport Operations Dispatch';
  dispatchForm.value.caller_phone = '+251 116 650 500';
  dispatchForm.value.nature_of_emergency = 'In-flight collapse, passenger unarousable';
}

async function submitDispatchMission() {
  submittingDispatch.value = true;
  try {
    const res = await fetch('/api/v1/emergency/dispatches', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(dispatchForm.value),
    });

    const json = await res.json();
    if (json.success) {
      isDispatchModalOpen.value = false;
      await fetchFleetOverview();
      await fetchDispatches();
    } else {
      await showAlert(json.message || 'Failed to dispatch ambulance.', { status: 'error' });
    }
  } catch (err) {
    console.error('Dispatch mission submit error:', err);
    await showAlert('Error connecting to ambulance dispatch service.', { status: 'error' });
  } finally {
    submittingDispatch.value = false;
  }
}

function openTelemetryModal(unit) {
  const target = unit || selectedAmbulance.value || fleetList.value[0];
  if (target) {
    telemetryForm.value = {
      ambulance_id: target.id,
      latitude: target.current_latitude || 9.0300,
      longitude: target.current_longitude || 38.7400,
      speed_kmh: target.speed_kmh || 45,
      fuel_level_percent: target.fuel_level_percent || 80,
      oxygen_tank_level_percent: target.oxygen_tank_level_percent || 95,
      battery_voltage: target.battery_voltage || 12.8,
      status: target.current_status || 'en_route',
      notes: 'Telemetry ping updated from simulation CAD dashboard.',
    };
    isTelemetryModalOpen.value = true;
  }
}

async function submitTelemetry() {
  submittingTelemetry.value = true;
  try {
    const res = await fetch('/api/v1/emergency/telemetry', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        ...telemetryForm.value,
        timestamp: new Date().toISOString(),
      }),
    });

    const json = await res.json();
    if (json.success) {
      isTelemetryModalOpen.value = false;
      await fetchFleetOverview();
    } else {
      await showAlert(json.message || 'Failed to transmit telemetry ping.', { status: 'error' });
    }
  } catch (err) {
    console.error('Telemetry submit error:', err);
    await showAlert('Error transmitting telemetry.', { status: 'error' });
  } finally {
    submittingTelemetry.value = false;
  }
}

function onTelemetryUnitSelect() {
  const target = fleetList.value.find(u => u.id === telemetryForm.value.ambulance_id);
  if (target) {
    telemetryForm.value.latitude = target.current_latitude || 9.0300;
    telemetryForm.value.longitude = target.current_longitude || 38.7400;
    telemetryForm.value.speed_kmh = target.speed_kmh || 50;
    telemetryForm.value.heading = target.heading || 45;
  }
}

function simulateStep(direction) {
  if (direction === 'hospital') {
    // Step toward 9.0300, 38.7400
    telemetryForm.value.latitude += (9.0300 - telemetryForm.value.latitude) * 0.35;
    telemetryForm.value.longitude += (38.7400 - telemetryForm.value.longitude) * 0.35;
    telemetryForm.value.speed_kmh = 58;
    telemetryForm.value.heading = 270;
  } else {
    // Step away toward scene
    telemetryForm.value.latitude += (9.0150 - telemetryForm.value.latitude) * 0.35;
    telemetryForm.value.longitude += (38.7620 - telemetryForm.value.longitude) * 0.35;
    telemetryForm.value.speed_kmh = 64;
    telemetryForm.value.heading = 135;
  }
}

async function submitTelemetryPing() {
  submittingTelemetry.value = true;
  try {
    const res = await fetch(`/api/v1/emergency/ambulances/${telemetryForm.value.ambulance_id}/telemetry`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        latitude: telemetryForm.value.latitude,
        longitude: telemetryForm.value.longitude,
        speed_kmh: telemetryForm.value.speed_kmh,
        heading: telemetryForm.value.heading,
        fuel_percentage: telemetryForm.value.fuel_percentage,
      }),
    });

    const json = await res.json();
    if (json.success) {
      isTelemetryModalOpen.value = false;
      await fetchFleetOverview();
    } else {
      await showAlert(json.message || 'Failed to transmit telemetry ping.', { status: 'error' });
    }
  } catch (err) {
    console.error('Telemetry submit error:', err);
    await showAlert('Error transmitting telemetry.', { status: 'error' });
  } finally {
    submittingTelemetry.value = false;
  }
}

onMounted(() => {
  fetchFleetOverview();
  fetchDispatches();

  // Near real-time auto-refresh interval (every 10s)
  refreshTimer = setInterval(() => {
    fetchFleetOverview();
    fetchDispatches();
  }, 10000);
});

onUnmounted(() => {
  if (refreshTimer) clearInterval(refreshTimer);
});
</script>
