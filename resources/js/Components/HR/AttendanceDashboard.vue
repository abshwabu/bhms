<template>
  <div class="space-y-6">
    <!-- Today's Roll-Call KPIs -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
        <div>
          <span class="text-xs font-semibold text-slate-400 block uppercase tracking-wider">Present & On Time</span>
          <span class="text-2xl font-black text-emerald-600 mt-1 block">
            {{ todaySummary?.present_count || 0 }}
          </span>
          <span class="text-[11px] text-slate-500 font-medium">Punctual arrival</span>
        </div>
        <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
        <div>
          <span class="text-xs font-semibold text-slate-400 block uppercase tracking-wider">Late Arrivals</span>
          <span class="text-2xl font-black text-amber-500 mt-1 block">
            {{ todaySummary?.late_count || 0 }}
          </span>
          <span class="text-[11px] text-slate-500 font-medium">&gt; 10 mins post-shift</span>
        </div>
        <div class="w-11 h-11 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
        <div>
          <span class="text-xs font-semibold text-slate-400 block uppercase tracking-wider">Pending Clock-In</span>
          <span class="text-2xl font-black text-indigo-600 mt-1 block">
            {{ todaySummary?.not_clocked_in_count || 0 }}
          </span>
          <span class="text-[11px] text-slate-500 font-medium">Scheduled shifts</span>
        </div>
        <div class="w-11 h-11 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
        <div>
          <span class="text-xs font-semibold text-slate-400 block uppercase tracking-wider">On Approved Leave</span>
          <span class="text-2xl font-black text-slate-700 mt-1 block">
            {{ todaySummary?.on_leave_count || 0 }}
          </span>
          <span class="text-[11px] text-slate-500 font-medium">Annual / Sick leave</span>
        </div>
        <div class="w-11 h-11 rounded-2xl bg-slate-100 text-slate-600 flex items-center justify-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
        </div>
      </div>
    </div>

    <!-- Quick Clock-In / Clock-Out Punch Station -->
    <div class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 p-6 rounded-3xl text-white shadow-xl">
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div>
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
            <span class="text-xs font-mono uppercase tracking-widest text-indigo-300">Staff Duty Punch Station</span>
          </div>
          <h3 class="text-xl font-black mt-1">Daily Roll-Call & Clock Station</h3>
          <p class="text-xs text-slate-300 mt-1 max-w-md">
            Biometric and digital attendance punch terminal. Automatically cross-references scheduled shift start time for punctuality tracking.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-3 bg-white/10 p-3 rounded-2xl backdrop-blur-md border border-white/10">
          <div class="w-64">
            <label class="block text-[10px] uppercase font-mono tracking-wider text-indigo-200 mb-1">Select Employee</label>
            <select
              v-model="punchStaffId"
              class="w-full px-3 py-2 bg-slate-900/80 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-400 cursor-pointer"
            >
              <option value="" disabled>Choose staff member...</option>
              <option v-for="s in staffMembers" :key="s.id" :value="s.id">
                {{ s.full_name }} ({{ s.employee_id }})
              </option>
            </select>
          </div>

          <div class="flex items-end gap-2 pt-4">
            <button
              @click="clockInPunch"
              :disabled="!punchStaffId || punching"
              class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold rounded-xl text-xs transition flex items-center gap-1.5 shadow-lg shadow-emerald-500/20 cursor-pointer disabled:opacity-40"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
              </svg>
              <span>Punch Clock-In</span>
            </button>

            <button
              @click="clockOutPunch"
              :disabled="!punchStaffId || punching"
              class="px-4 py-2 bg-rose-500 hover:bg-rose-600 text-white font-bold rounded-xl text-xs transition flex items-center gap-1.5 shadow-lg shadow-rose-500/20 cursor-pointer disabled:opacity-40"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
              <span>Punch Clock-Out</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Feedback Alert -->
      <div v-if="punchFeedback" class="mt-4 p-3 rounded-xl text-xs font-semibold flex items-center gap-2" :class="punchFeedback.success ? 'bg-emerald-500/20 border border-emerald-400 text-emerald-200' : 'bg-rose-500/20 border border-rose-400 text-rose-200'">
        <span>{{ punchFeedback.message }}</span>
      </div>
    </div>

    <!-- Attendance Logs Filter & Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-4 border-b border-slate-200 flex flex-col md:flex-row items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <h4 class="font-bold text-sm text-slate-800">Duty Attendance Audit Logs</h4>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-mono bg-slate-100 text-slate-600 font-semibold">
            {{ attendanceLogs.length }} Records
          </span>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full md:w-auto justify-end">
          <input
            v-model="filterDate"
            @change="fetchAttendance"
            type="date"
            class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-700 focus:outline-none"
          />

          <select
            v-model="filterStatus"
            @change="fetchAttendance"
            class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-700 focus:outline-none cursor-pointer"
          >
            <option value="">All Statuses</option>
            <option value="present">Present (On Time)</option>
            <option value="late">Late Arrival</option>
            <option value="absent">Absent</option>
          </select>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
              <th class="py-3 px-4">Staff Member</th>
              <th class="py-3 px-4">Role & Dept</th>
              <th class="py-3 px-4">Shift</th>
              <th class="py-3 px-4">Check-In</th>
              <th class="py-3 px-4">Check-Out</th>
              <th class="py-3 px-4">Duration</th>
              <th class="py-3 px-4">Punctuality Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="attendanceLogs.length === 0">
              <td colspan="7" class="py-8 text-center text-slate-400 italic">
                No attendance punches logged for the selected date.
              </td>
            </tr>
            <tr
              v-for="log in attendanceLogs"
              :key="log.id"
              class="hover:bg-slate-50/70 transition"
            >
              <td class="py-3 px-4">
                <div class="font-bold text-slate-900">{{ log.staff?.full_name || 'Staff' }}</div>
                <div class="text-[10px] font-mono text-slate-400">{{ log.staff?.employee_id }}</div>
              </td>
              <td class="py-3 px-4">
                <div class="text-slate-700 font-medium">{{ log.staff?.designation }}</div>
                <div class="text-[10px] text-slate-400">{{ log.staff?.department }}</div>
              </td>
              <td class="py-3 px-4">
                <span class="font-mono text-slate-600">{{ log.shift?.shift_name || 'Scheduled Duty' }}</span>
              </td>
              <td class="py-3 px-4 font-mono font-bold text-slate-800">
                {{ log.check_in_time ? formatTime(log.check_in_time) : '&mdash;' }}
              </td>
              <td class="py-3 px-4 font-mono font-bold text-slate-800">
                {{ log.check_out_time ? formatTime(log.check_out_time) : '&mdash;' }}
              </td>
              <td class="py-3 px-4 font-mono font-semibold text-slate-700">
                {{ log.hours_worked ? `${log.hours_worked} hrs` : 'In Progress' }}
              </td>
              <td class="py-3 px-4">
                <div v-if="log.is_punctual" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                  <span>On Time</span>
                </div>
                <div v-else class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                  <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                  <span>Late (+{{ log.minutes_late }}m)</span>
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
  branchId: {
    type: String,
    default: null,
  },
});

const todaySummary = ref(null);
const attendanceLogs = ref([]);
const staffMembers = ref([]);
const punchStaffId = ref('');
const punching = ref(false);
const punchFeedback = ref(null);
const filterDate = ref(new Date().toISOString().split('T')[0]);
const filterStatus = ref('');

const formatTime = (dtStr) => {
  if (!dtStr) return '—';
  const d = new Date(dtStr);
  return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
};

const fetchTodayStatus = async () => {
  try {
    const res = await fetch('/api/v1/hr/attendance/today-status', {
      headers: {
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
    });
    const json = await res.json();
    if (json.success) {
      todaySummary.value = json.data;
    }
  } catch (err) {
    console.error('Failed to load today attendance summary', err);
  }
};

const fetchAttendance = async () => {
  try {
    const params = new URLSearchParams();
    if (props.branchId) params.append('branch_id', props.branchId);
    if (filterDate.value) params.append('date', filterDate.value);
    if (filterStatus.value) params.append('status', filterStatus.value);

    const res = await fetch(`/api/v1/hr/attendance?${params.toString()}`, {
      headers: {
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
    });
    const json = await res.json();
    if (json.success) {
      attendanceLogs.value = json.data?.data || json.data || [];
    }
  } catch (err) {
    console.error('Failed to load attendance logs', err);
  }
};

const fetchStaffMembers = async () => {
  try {
    const res = await fetch('/api/v1/hr/staff?per_page=100', {
      headers: {
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
    });
    const json = await res.json();
    if (json.success) {
      staffMembers.value = json.data?.data || json.data || [];
      if (staffMembers.value.length > 0 && !punchStaffId.value) {
        punchStaffId.value = staffMembers.value[0].id;
      }
    }
  } catch (err) {
    console.error('Failed to load staff list', err);
  }
};

const clockInPunch = async () => {
  if (!punchStaffId.value) return;
  punching.value = true;
  punchFeedback.value = null;
  try {
    const res = await fetch('/api/v1/hr/attendance/check-in', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
      body: JSON.stringify({ staff_id: punchStaffId.value }),
    });
    const json = await res.json();
    if (json.success) {
      punchFeedback.value = { success: true, message: json.message || 'Clock-in successfully recorded.' };
      await fetchTodayStatus();
      await fetchAttendance();
    } else {
      punchFeedback.value = { success: false, message: json.message || 'Clock-in error' };
    }
  } catch (err) {
    punchFeedback.value = { success: false, message: 'Server communication error during punch.' };
  } finally {
    punching.value = false;
  }
};

const clockOutPunch = async () => {
  if (!punchStaffId.value) return;
  punching.value = true;
  punchFeedback.value = null;
  try {
    const res = await fetch('/api/v1/hr/attendance/check-out', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
      body: JSON.stringify({ staff_id: punchStaffId.value }),
    });
    const json = await res.json();
    if (json.success) {
      punchFeedback.value = { success: true, message: json.message || 'Clock-out successfully recorded.' };
      await fetchTodayStatus();
      await fetchAttendance();
    } else {
      punchFeedback.value = { success: false, message: json.message || 'Clock-out error' };
    }
  } catch (err) {
    punchFeedback.value = { success: false, message: 'Server communication error during punch.' };
  } finally {
    punching.value = false;
  }
};

onMounted(() => {
  fetchTodayStatus();
  fetchAttendance();
  fetchStaffMembers();
});
</script>
