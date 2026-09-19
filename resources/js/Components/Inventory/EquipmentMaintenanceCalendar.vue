<template>
  <div class="space-y-6">
    <!-- Header & Action Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
      <div>
        <div class="flex items-center gap-2 text-xs font-mono uppercase tracking-wider text-rose-600 font-bold">
          <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
          Clinical Engineering & Asset Lifecycle
        </div>
        <h1 class="text-xl font-black text-slate-900 mt-1">Biomedical & Facility Equipment</h1>
        <p class="text-xs text-slate-500 mt-0.5">Asset register, preventive maintenance schedules, breakdown work orders, and calibration logs.</p>
      </div>

      <div class="flex items-center gap-2">
        <button
          @click="openRegisterEquipmentModal"
          class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-lg shadow-slate-900/10 cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          + Register New Asset
        </button>
      </div>
    </div>

    <!-- Proactive Alert Cards (Ahead of Due Dates & Overdue) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <!-- Overdue Maintenance Card -->
      <div
        :class="maintenanceAlerts.overdue_equipment_count > 0 ? 'bg-rose-50 border-rose-200' : 'bg-white border-slate-200'"
        class="p-5 rounded-2xl border shadow-sm transition"
      >
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-mono uppercase font-bold text-rose-700">Overdue Service</span>
          <span
            v-if="maintenanceAlerts.overdue_equipment_count > 0"
            class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-ping"
          ></span>
        </div>
        <div class="text-2xl font-black text-rose-700 mt-1">
          {{ maintenanceAlerts.overdue_equipment_count }} Assets
        </div>
        <p class="text-[11px] text-rose-700/80 mt-1">
          Past preventive maintenance deadline. Immediate inspection required.
        </p>
      </div>

      <!-- Maintenance Due Soon Card -->
      <div
        :class="maintenanceAlerts.upcoming_equipment_count > 0 ? 'bg-amber-50 border-amber-200' : 'bg-white border-slate-200'"
        class="p-5 rounded-2xl border shadow-sm transition"
      >
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-mono uppercase font-bold text-amber-700">Upcoming (14 Days)</span>
          <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <div class="text-2xl font-black text-amber-700 mt-1">
          {{ maintenanceAlerts.upcoming_equipment_count }} Scheduled
        </div>
        <p class="text-[11px] text-amber-700/80 mt-1">
          Routine PM cycle due in the next two weeks.
        </p>
      </div>

      <!-- Expiring Warranty Card -->
      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-mono uppercase font-bold text-blue-700">Warranties Expiring</span>
          <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          </svg>
        </div>
        <div class="text-2xl font-black text-slate-900 mt-1">
          {{ maintenanceAlerts.expiring_warranties_count }}
        </div>
        <p class="text-[11px] text-slate-500 mt-1">
          Contracts expiring within 30 days. AMC renewal recommended.
        </p>
      </div>
    </div>

    <!-- Navigation Tabs: Assets Directory vs Service Tickets -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
      <button
        @click="activeView = 'assets'"
        :class="activeView === 'assets' ? 'bg-slate-900 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100'"
        class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer border border-slate-200"
      >
        <span>Equipment Asset Register</span>
        <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-slate-800 text-slate-300 font-mono">{{ equipments.length }}</span>
      </button>

      <button
        @click="activeView = 'tickets'; fetchMaintenanceLogs()"
        :class="activeView === 'tickets' ? 'bg-rose-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100'"
        class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer border border-slate-200"
      >
        <span>Maintenance Work Orders & Logs</span>
        <span
          v-if="maintenanceAlerts.pending_maintenance_logs_count > 0"
          class="px-1.5 py-0.5 rounded-full text-[10px] bg-white text-rose-600 font-black"
        >
          {{ maintenanceAlerts.pending_maintenance_logs_count }}
        </span>
      </button>
    </div>

    <!-- VIEW 1: Equipment Assets Directory -->
    <div v-if="activeView === 'assets'" class="space-y-4">
      <!-- Filter Toolbar -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
          <div class="flex items-center bg-slate-100 p-1 rounded-xl">
            <button
              v-for="cat in ['all', 'biomedical', 'laboratory', 'radiology', 'surgical', 'facility']"
              :key="cat"
              @click="categoryFilter = cat; fetchEquipments()"
              :class="categoryFilter === cat ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
              class="px-3 py-1.5 rounded-lg capitalize transition cursor-pointer"
            >
              {{ cat }}
            </button>
          </div>

          <button
            @click="overdueOnly = !overdueOnly; fetchEquipments()"
            :class="overdueOnly ? 'bg-rose-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
            class="px-3.5 py-2 rounded-xl text-xs transition cursor-pointer flex items-center gap-1.5"
          >
            <span class="w-2 h-2 rounded-full" :class="overdueOnly ? 'bg-white' : 'bg-rose-500'"></span>
            Overdue Only
          </button>
        </div>

        <div class="relative w-full sm:w-80">
          <input
            v-model="searchQuery"
            @input="fetchEquipments"
            type="text"
            placeholder="Search Tag, Equipment Name, Serial..."
            class="w-full text-xs pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500"
          />
          <svg class="w-3.5 h-3.5 absolute left-2.5 top-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
      </div>

      <!-- Equipment Table -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div v-if="loading" class="p-12 text-center text-xs text-slate-400">
          Loading equipment assets...
        </div>
        <div v-else-if="equipments.length === 0" class="p-12 text-center text-xs text-slate-400">
          No equipment found matching criteria.
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-slate-600 font-mono uppercase text-[11px] border-b border-slate-200">
              <tr>
                <th class="p-4">Asset Tag & Name</th>
                <th class="p-4">Department & Room</th>
                <th class="p-4">Category</th>
                <th class="p-4 text-center">Status</th>
                <th class="p-4">Next PM Date</th>
                <th class="p-4 text-center">Maintenance Due</th>
                <th class="p-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="eq in equipments" :key="eq.id" class="hover:bg-slate-50/60 transition">
                <td class="p-4">
                  <div class="font-bold text-slate-900">{{ eq.name }}</div>
                  <div class="text-[10px] text-slate-400 font-mono">{{ eq.asset_tag }} &bull; S/N: {{ eq.serial_number || 'N/A' }}</div>
                </td>
                <td class="p-4">
                  <div class="font-semibold text-slate-800 uppercase">{{ eq.department }}</div>
                  <div class="text-[10px] text-slate-400">{{ eq.room_location || 'General' }}</div>
                </td>
                <td class="p-4">
                  <span class="font-mono text-[10px] uppercase px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-bold">
                    {{ eq.category }}
                  </span>
                </td>
                <td class="p-4 text-center">
                  <span
                    :class="{
                      'bg-emerald-50 text-emerald-700 border-emerald-200': eq.status === 'operational',
                      'bg-amber-50 text-amber-700 border-amber-200': eq.status === 'under_maintenance',
                      'bg-rose-50 text-rose-700 border-rose-200': eq.status === 'out_of_order',
                      'bg-slate-100 text-slate-500 border-slate-200': eq.status === 'decommissioned'
                    }"
                    class="px-2.5 py-1 rounded-full text-[10px] font-mono uppercase font-bold border"
                  >
                    {{ eq.status.replace('_', ' ') }}
                  </span>
                </td>
                <td class="p-4 font-mono font-bold text-slate-900">
                  {{ eq.next_maintenance_date || 'None scheduled' }}
                </td>
                <td class="p-4 text-center">
                  <span
                    v-if="eq.is_maintenance_overdue"
                    class="px-2 py-0.5 rounded-md bg-rose-100 text-rose-800 font-mono text-[10px] font-black border border-rose-200"
                  >
                    OVERDUE ({{ Math.abs(eq.days_until_next_maintenance) }}d ago)
                  </span>
                  <span
                    v-else-if="eq.is_maintenance_due_soon"
                    class="px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 font-mono text-[10px] font-black border border-amber-200"
                  >
                    DUE IN {{ eq.days_until_next_maintenance }}d
                  </span>
                  <span
                    v-else-if="eq.days_until_next_maintenance"
                    class="text-[11px] font-mono text-slate-500 font-semibold"
                  >
                    {{ eq.days_until_next_maintenance }} days
                  </span>
                  <span v-else class="text-slate-300">&mdash;</span>
                </td>
                <td class="p-4 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <button
                      @click="openScheduleMaintenanceModal(eq)"
                      class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-[11px] font-bold transition cursor-pointer"
                    >
                      Schedule PM
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- VIEW 2: Maintenance Work Orders & Execution Logs -->
    <div v-else-if="activeView === 'tickets'" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-4 border-b border-slate-100 flex items-center justify-between">
        <div>
          <h3 class="font-bold text-slate-900 text-sm">Active & Historic Service Logs</h3>
          <p class="text-xs text-slate-400">Preventive maintenance runs, electrical safety inspections, and breakdown repair records.</p>
        </div>
      </div>

      <div v-if="maintenanceLogs.length === 0" class="p-12 text-center text-xs text-slate-400">
        No maintenance logs recorded.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-600 font-mono uppercase text-[11px] border-b border-slate-200">
            <tr>
              <th class="p-4">Ticket #</th>
              <th class="p-4">Asset Name & Location</th>
              <th class="p-4">Type</th>
              <th class="p-4">Scheduled Date</th>
              <th class="p-4">Technician / Vendor</th>
              <th class="p-4 text-center">Status</th>
              <th class="p-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-700">
            <tr v-for="log in maintenanceLogs" :key="log.id" class="hover:bg-slate-50/60 transition">
              <td class="p-4 font-mono font-bold text-slate-900">
                {{ log.log_number }}
              </td>
              <td class="p-4">
                <div class="font-bold text-slate-900">{{ log.equipment?.name }}</div>
                <div class="text-[10px] text-slate-400 font-mono">{{ log.equipment?.asset_tag }} &bull; {{ log.equipment?.department }}</div>
              </td>
              <td class="p-4">
                <span class="font-mono text-[10px] uppercase font-bold text-slate-700">
                  {{ log.maintenance_type.replace('_', ' ') }}
                </span>
              </td>
              <td class="p-4 font-mono">
                <span :class="log.is_overdue ? 'text-rose-600 font-bold' : 'text-slate-600'">
                  {{ log.scheduled_date }}
                </span>
              </td>
              <td class="p-4 text-slate-700">
                <div>{{ log.technician_name || 'Biomedical Engineering' }}</div>
                <div v-if="log.vendor" class="text-[10px] text-slate-400 font-mono">{{ log.vendor.name }}</div>
              </td>
              <td class="p-4 text-center">
                <span
                  :class="{
                    'bg-amber-50 text-amber-700 border-amber-200': log.status === 'scheduled',
                    'bg-blue-50 text-blue-700 border-blue-200': log.status === 'in_progress',
                    'bg-emerald-50 text-emerald-700 border-emerald-200': log.status === 'completed'
                  }"
                  class="px-2.5 py-1 rounded-full text-[10px] font-mono uppercase font-bold border"
                >
                  {{ log.status }}
                </span>
              </td>
              <td class="p-4 text-right">
                <button
                  v-if="log.status !== 'completed'"
                  @click="openCompleteMaintenanceModal(log)"
                  class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[11px] font-bold transition shadow-xs cursor-pointer"
                >
                  Complete Service
                </button>
                <span v-else class="text-[11px] font-mono text-slate-400 font-semibold">Done</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal: Register New Equipment Asset -->
    <div
      v-if="showRegisterModal"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto"
    >
      <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4 my-8">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3 class="font-bold text-slate-900 text-base">Register Hospital Asset</h3>
            <p class="text-xs text-slate-400">Record a biomedical device, imaging machine, or facility asset.</p>
          </div>
          <button @click="showRegisterModal = false" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="space-y-3 text-xs">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Asset Tag *</label>
              <input
                v-model="equipmentForm.asset_tag"
                type="text"
                placeholder="e.g. EQ-BIO-005"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 font-mono"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Category *</label>
              <select
                v-model="equipmentForm.category"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 font-semibold"
              >
                <option value="biomedical">Biomedical Device</option>
                <option value="laboratory">Laboratory Equipment</option>
                <option value="radiology">Radiology & Imaging</option>
                <option value="surgical">Surgical Instruments</option>
                <option value="facility">Facility & HVAC</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Equipment Name *</label>
            <input
              v-model="equipmentForm.name"
              type="text"
              placeholder="e.g. Mindray SV300 ICU Ventilator"
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 font-bold"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Model #</label>
              <input
                v-model="equipmentForm.model_number"
                type="text"
                placeholder="e.g. SV300-PRO"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Serial #</label>
              <input
                v-model="equipmentForm.serial_number"
                type="text"
                placeholder="e.g. SN-8891002"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 font-mono"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Department *</label>
              <input
                v-model="equipmentForm.department"
                type="text"
                placeholder="e.g. icu, ot, emergency, lab"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">PM Frequency (Days) *</label>
              <input
                v-model.number="equipmentForm.maintenance_frequency_days"
                type="number"
                min="30"
                placeholder="e.g. 90"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 font-mono"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Purchase Cost ($)</label>
              <input
                v-model.number="equipmentForm.purchase_cost"
                type="number"
                step="0.01"
                min="0"
                placeholder="0.00"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 font-mono"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Warranty Expiry Date</label>
              <input
                v-model="equipmentForm.warranty_expiry_date"
                type="date"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900"
              />
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
          <button
            @click="showRegisterModal = false"
            class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer"
          >
            Cancel
          </button>
          <button
            @click="submitRegisterEquipment"
            :disabled="isSubmitting || !equipmentForm.asset_tag || !equipmentForm.name"
            class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
          >
            Register Asset
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Schedule Maintenance -->
    <div
      v-if="showScheduleModal && selectedEquipment"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3 class="font-bold text-slate-900 text-base">Schedule Maintenance Ticket</h3>
            <p class="text-xs text-slate-400">{{ selectedEquipment.name }} ({{ selectedEquipment.asset_tag }})</p>
          </div>
          <button @click="showScheduleModal = false" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="space-y-3 text-xs">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Scheduled Date *</label>
              <input
                v-model="scheduleForm.scheduled_date"
                type="date"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500 font-mono"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Priority *</label>
              <select
                v-model="scheduleForm.priority"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500 font-semibold"
              >
                <option value="low">Low Priority</option>
                <option value="medium">Medium Priority</option>
                <option value="high">High Priority</option>
                <option value="urgent">Urgent / Life Support</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Maintenance Type *</label>
            <select
              v-model="scheduleForm.maintenance_type"
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500 font-semibold"
            >
              <option value="preventive">Routine Preventive Maintenance (PM)</option>
              <option value="corrective_repair">Corrective Breakdown Repair</option>
              <option value="calibration">Transducer / Flow Calibration</option>
              <option value="safety_inspection">Electrical Safety & Leakage Inspection</option>
            </select>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Assigned Biomedical Technician / Engineer</label>
            <input
              v-model="scheduleForm.technician_name"
              type="text"
              placeholder="e.g. Marcus Vance, CBMET"
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Initial Issue / Diagnostic Observation</label>
            <textarea
              v-model="scheduleForm.findings"
              rows="2"
              placeholder="e.g. Periodic quarterly sensor calibration and battery inspection..."
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500"
            ></textarea>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
          <button
            @click="showScheduleModal = false"
            class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer"
          >
            Cancel
          </button>
          <button
            @click="submitScheduleMaintenance"
            :disabled="isSubmitting || !scheduleForm.scheduled_date"
            class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
          >
            Confirm Schedule
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Complete Maintenance -->
    <div
      v-if="showCompleteModal && selectedLog"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3 class="font-bold text-slate-900 text-base">Complete Maintenance Ticket</h3>
            <p class="text-xs text-slate-400">Log #{{ selectedLog.log_number }} &bull; {{ selectedLog.equipment?.name }}</p>
          </div>
          <button @click="showCompleteModal = false" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="space-y-3 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Actions Taken & Work Performed *</label>
            <textarea
              v-model="completeForm.actions_taken"
              rows="3"
              placeholder="Detailed description of calibration, battery replacement, safety checks..."
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500"
            ></textarea>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Service Cost ($)</label>
              <input
                v-model.number="completeForm.cost"
                type="number"
                step="0.01"
                min="0"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 font-mono"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Next PM Date</label>
              <input
                v-model="completeForm.next_recommended_date"
                type="date"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 font-mono"
              />
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
          <button
            @click="showCompleteModal = false"
            class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer"
          >
            Cancel
          </button>
          <button
            @click="submitCompleteMaintenance"
            :disabled="isSubmitting || !completeForm.actions_taken"
            class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
          >
            Sign-Off & Certify
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { showAlert } from '../../Services/modalDialog';

const props = defineProps({
  branchId: {
    type: String,
    default: null,
  },
});

const loading = ref(false);
const isSubmitting = ref(false);
const activeView = ref('assets');

const categoryFilter = ref('all');
const overdueOnly = ref(false);
const searchQuery = ref('');

const equipments = ref([]);
const maintenanceLogs = ref([]);
const maintenanceAlerts = ref({
  overdue_equipment_count: 0,
  upcoming_equipment_count: 0,
  expiring_warranties_count: 0,
  pending_maintenance_logs_count: 0,
});

const showRegisterModal = ref(false);
const showScheduleModal = ref(false);
const showCompleteModal = ref(false);
const selectedEquipment = ref(null);
const selectedLog = ref(null);

const equipmentForm = ref({
  asset_tag: '',
  name: '',
  category: 'biomedical',
  model_number: '',
  serial_number: '',
  department: 'icu',
  maintenance_frequency_days: 90,
  purchase_cost: 0,
  warranty_expiry_date: '',
});

const scheduleForm = ref({
  scheduled_date: '',
  priority: 'medium',
  maintenance_type: 'preventive',
  technician_name: '',
  findings: '',
});

const completeForm = ref({
  actions_taken: '',
  cost: 0,
  next_recommended_date: '',
});

async function fetchAlerts() {
  try {
    const res = await axios.get('/api/v1/inventory/equipment/maintenance-alerts', {
      params: { branch_id: props.branchId || undefined, upcoming_days: 14 }
    });
    if (res.data?.data) {
      maintenanceAlerts.value = res.data.data;
    }
  } catch (err) {
    console.error('Failed to fetch maintenance alerts:', err);
  }
}

async function fetchEquipments() {
  loading.value = true;
  try {
    const params = {
      branch_id: props.branchId || undefined,
      category: categoryFilter.value !== 'all' ? categoryFilter.value : undefined,
      overdue: overdueOnly.value ? true : undefined,
      search: searchQuery.value || undefined,
    };
    const res = await axios.get('/api/v1/inventory/equipment', { params });
    equipments.value = res.data?.data || [];
  } catch (err) {
    console.error('Failed to fetch equipment:', err);
  } finally {
    loading.value = false;
  }
}

async function fetchMaintenanceLogs() {
  try {
    const res = await axios.get('/api/v1/inventory/maintenance-logs', {
      params: { branch_id: props.branchId || undefined }
    });
    maintenanceLogs.value = res.data?.data || [];
  } catch (err) {
    console.error('Failed to fetch maintenance logs:', err);
  }
}

function openRegisterEquipmentModal() {
  equipmentForm.value = {
    asset_tag: '',
    name: '',
    category: 'biomedical',
    model_number: '',
    serial_number: '',
    department: 'icu',
    maintenance_frequency_days: 90,
    purchase_cost: 0,
    warranty_expiry_date: '',
  };
  showRegisterModal.value = true;
}

async function submitRegisterEquipment() {
  isSubmitting.value = true;
  try {
    const payload = {
      organization_id: '01a09429-0097-71b5-9f5b-6ff38a7cba8b',
      branch_id: props.branchId || 'b9ff561a-5396-4309-9b08-3e7b358310e9',
      asset_tag: equipmentForm.value.asset_tag,
      name: equipmentForm.value.name,
      category: equipmentForm.value.category,
      model_number: equipmentForm.value.model_number || undefined,
      serial_number: equipmentForm.value.serial_number || undefined,
      department: equipmentForm.value.department,
      maintenance_frequency_days: equipmentForm.value.maintenance_frequency_days || 90,
      purchase_cost_cents: Math.round((equipmentForm.value.purchase_cost || 0) * 100),
      warranty_expiry_date: equipmentForm.value.warranty_expiry_date || undefined,
    };
    await axios.post('/api/v1/inventory/equipment', payload);
    showRegisterModal.value = false;
    await Promise.all([fetchEquipments(), fetchAlerts()]);
  } catch (err) {
    await showAlert(err.response?.data?.message || 'Failed to register equipment.');
  } finally {
    isSubmitting.value = false;
  }
}

function openScheduleMaintenanceModal(eq) {
  selectedEquipment.value = eq;
  scheduleForm.value = {
    scheduled_date: eq.next_maintenance_date || new Date().toISOString().split('T')[0],
    priority: 'medium',
    maintenance_type: 'preventive',
    technician_name: '',
    findings: '',
  };
  showScheduleModal.value = true;
}

async function submitScheduleMaintenance() {
  if (!selectedEquipment.value) return;
  isSubmitting.value = true;
  try {
    await axios.post(`/api/v1/inventory/equipment/${selectedEquipment.value.id}/schedule-maintenance`, {
      scheduled_date: scheduleForm.value.scheduled_date,
      priority: scheduleForm.value.priority,
      maintenance_type: scheduleForm.value.maintenance_type,
      technician_name: scheduleForm.value.technician_name || undefined,
      findings: scheduleForm.value.findings || undefined,
    });
    showScheduleModal.value = false;
    await Promise.all([fetchEquipments(), fetchAlerts(), fetchMaintenanceLogs()]);
  } catch (err) {
    await showAlert(err.response?.data?.message || 'Failed to schedule maintenance.');
  } finally {
    isSubmitting.value = false;
  }
}

function openCompleteMaintenanceModal(log) {
  selectedLog.value = log;
  completeForm.value = {
    actions_taken: '',
    cost: log.cost || 0,
    next_recommended_date: '',
  };
  showCompleteModal.value = true;
}

async function submitCompleteMaintenance() {
  if (!selectedLog.value) return;
  isSubmitting.value = true;
  try {
    await axios.post(`/api/v1/inventory/maintenance-logs/${selectedLog.value.id}/complete`, {
      actions_taken: completeForm.value.actions_taken,
      cost_cents: Math.round((completeForm.value.cost || 0) * 100),
      next_recommended_date: completeForm.value.next_recommended_date || undefined,
    });
    showCompleteModal.value = false;
    await Promise.all([fetchEquipments(), fetchAlerts(), fetchMaintenanceLogs()]);
  } catch (err) {
    await showAlert(err.response?.data?.message || 'Failed to complete maintenance.');
  } finally {
    isSubmitting.value = false;
  }
}

onMounted(() => {
  fetchAlerts();
  fetchEquipments();
});
</script>
