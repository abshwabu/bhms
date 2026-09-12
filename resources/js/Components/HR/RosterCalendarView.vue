<template>
  <div class="space-y-6">
    <!-- Calendar Controls & Roster Publishing Toolbar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
      <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
        <!-- Week Navigator -->
        <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-200 rounded-xl p-1">
          <button
            @click="navigateWeek(-1)"
            class="p-1.5 hover:bg-white rounded-lg transition text-slate-600 cursor-pointer"
            title="Previous Week"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <span class="px-3 text-xs font-bold text-slate-800">
            {{ weekStartFormatted }} &mdash; {{ weekEndFormatted }}
          </span>
          <button
            @click="navigateWeek(1)"
            class="p-1.5 hover:bg-white rounded-lg transition text-slate-600 cursor-pointer"
            title="Next Week"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>

        <button
          @click="jumpToCurrentWeek"
          class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-600 transition cursor-pointer"
        >
          Today
        </button>

        <!-- Department Filter -->
        <select
          v-model="departmentFilter"
          @change="fetchShifts"
          class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-700 focus:ring-2 focus:ring-blue-500 focus:outline-none cursor-pointer"
        >
          <option value="">All Departments</option>
          <option value="Internal Medicine">Internal Medicine</option>
          <option value="Nursing">Nursing</option>
          <option value="Radiology">Radiology</option>
          <option value="Pharmacy">Pharmacy</option>
        </select>
      </div>

      <div class="flex items-center gap-2 w-full md:w-auto justify-end">
        <!-- Publish Roster Button -->
        <button
          @click="publishCurrentWeekRoster"
          :disabled="publishing"
          class="px-3.5 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white transition flex items-center gap-1.5 shadow-sm cursor-pointer disabled:opacity-50"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <span>{{ publishing ? 'Publishing...' : 'Publish Week Roster' }}</span>
        </button>

        <!-- New Shift Button -->
        <button
          @click="openNewShiftModal"
          class="px-4 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white transition flex items-center gap-1.5 shadow-sm cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span>Schedule Shift</span>
        </button>
      </div>
    </div>

    <!-- Weekly Grid View -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
      <!-- Days of Week Header -->
      <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50/70 text-slate-700">
        <div
          v-for="day in weekDays"
          :key="day.dateStr"
          class="p-3 text-center border-r last:border-r-0 border-slate-200"
          :class="{ 'bg-blue-50/60 font-bold': isToday(day.dateStr) }"
        >
          <div class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">{{ day.dayName }}</div>
          <div class="text-sm font-extrabold mt-0.5" :class="isToday(day.dateStr) ? 'text-blue-600' : 'text-slate-800'">
            {{ day.dayNumber }}
          </div>
          <span v-if="isToday(day.dateStr)" class="inline-block w-1.5 h-1.5 rounded-full bg-blue-600 mt-1"></span>
        </div>
      </div>

      <!-- Weekly Shifts Columns -->
      <div class="grid grid-cols-7 min-h-[480px] divide-x divide-slate-200 bg-slate-50/20">
        <div
          v-for="day in weekDays"
          :key="day.dateStr"
          class="p-2 space-y-2.5 min-h-[160px]"
          :class="{ 'bg-blue-50/20': isToday(day.dateStr) }"
        >
          <div
            v-if="getShiftsForDate(day.dateStr).length === 0"
            class="h-full flex items-center justify-center text-slate-300 text-[11px] italic py-8"
          >
            No duty shifts
          </div>

          <!-- Shift Card -->
          <div
            v-for="shift in getShiftsForDate(day.dateStr)"
            :key="shift.id"
            class="p-2.5 rounded-xl border transition shadow-xs flex flex-col justify-between"
            :class="getShiftStyleClass(shift)"
          >
            <div>
              <div class="flex items-start justify-between gap-1">
                <span class="font-bold text-slate-900 text-xs truncate leading-snug">
                  {{ shift.staff?.full_name || 'Staff' }}
                </span>
                <span
                  v-if="shift.is_published"
                  class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"
                  title="Published to Staff Roster"
                ></span>
                <span
                  v-else
                  class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-200 text-amber-900 uppercase shrink-0"
                >
                  Draft
                </span>
              </div>
              <div class="text-[10px] text-slate-500 truncate mt-0.5">
                {{ shift.staff?.designation || shift.department }}
              </div>
              <div class="text-[11px] font-mono font-bold text-slate-700 mt-1 flex items-center gap-1">
                <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ shift.start_time?.slice(0, 5) }} - {{ shift.end_time?.slice(0, 5) }}</span>
              </div>
              <div class="text-[10px] font-medium text-slate-500 mt-0.5">
                {{ shift.shift_name }} ({{ shift.duration_hours }}h)
              </div>
            </div>

            <div class="mt-2 pt-2 border-t border-slate-200/60 flex items-center justify-between text-[10px]">
              <span class="capitalize px-1.5 py-0.5 rounded bg-slate-100 font-semibold text-slate-600">
                {{ shift.shift_type }}
              </span>
              <button
                v-if="shift.status !== 'cancelled'"
                @click="cancelShift(shift)"
                class="text-rose-600 hover:text-rose-800 font-medium hover:underline cursor-pointer"
              >
                Cancel
              </button>
              <span v-else class="text-slate-400 italic">Cancelled</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Schedule Shift with Conflict & Double-Booking Check -->
    <div v-if="isNewShiftModalOpen" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 max-h-[92vh] overflow-y-auto">
        <div class="flex items-start justify-between pb-4 border-b border-slate-100">
          <div>
            <h3 class="font-bold text-base text-slate-900">Schedule Staff Shift</h3>
            <p class="text-xs text-slate-500 mt-0.5">Automatic pre-flight conflict detection prevents double-booking clinicians.</p>
          </div>
          <button @click="isNewShiftModalOpen = false" class="p-1 text-slate-400 hover:text-slate-600 cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="submitShift" class="mt-4 space-y-3.5">
          <!-- Staff Selection -->
          <div>
            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Staff Member *</label>
            <select
              v-model="shiftForm.staff_id"
              @change="runConflictCheck"
              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
              required
            >
              <option value="" disabled>Select staff member...</option>
              <option v-for="s in staffMembers" :key="s.id" :value="s.id">
                {{ s.full_name }} &mdash; {{ s.designation }} ({{ s.department }})
              </option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-semibold text-slate-600 mb-1">Shift Name *</label>
              <input
                v-model="shiftForm.shift_name"
                type="text"
                placeholder="e.g. ICU Morning Ward Lead"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                required
              />
            </div>
            <div>
              <label class="block text-[11px] font-semibold text-slate-600 mb-1">Shift Type *</label>
              <select
                v-model="shiftForm.shift_type"
                @change="applyTypePreset"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                required
              >
                <option value="morning">Morning (08:00 - 16:00)</option>
                <option value="evening">Evening (14:00 - 22:00)</option>
                <option value="night">Night (20:00 - 08:00)</option>
                <option value="on_call">On-Call Coverage</option>
                <option value="custom">Custom Hours</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block text-[11px] font-semibold text-slate-600 mb-1">Shift Date *</label>
              <input
                v-model="shiftForm.shift_date"
                @change="runConflictCheck"
                type="date"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                required
              />
            </div>
            <div>
              <label class="block text-[11px] font-semibold text-slate-600 mb-1">Start Time *</label>
              <input
                v-model="shiftForm.start_time"
                @change="runConflictCheck"
                type="time"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                required
              />
            </div>
            <div>
              <label class="block text-[11px] font-semibold text-slate-600 mb-1">End Time *</label>
              <input
                v-model="shiftForm.end_time"
                @change="runConflictCheck"
                type="time"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                required
              />
            </div>
          </div>

          <div>
            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Department</label>
            <input
              v-model="shiftForm.department"
              type="text"
              placeholder="e.g. Internal Medicine"
              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
            />
          </div>

          <!-- Conflict & Double-Booking Warning Alert Banner -->
          <div
            v-if="conflictAlert"
            class="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs space-y-1.5"
          >
            <div class="flex items-center gap-2 font-bold text-rose-950">
              <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
              <span>Schedule Conflict Detected! Staff member is already double-booked:</span>
            </div>
            <ul class="list-disc list-inside space-y-0.5 text-[11px] text-rose-800">
              <li v-for="c in conflictAlert.conflicts" :key="c.id">
                <strong>{{ c.shift_name }}</strong> ({{ c.shift_date }} {{ c.start_time }}-{{ c.end_time }})
              </li>
            </ul>
            <div class="pt-1 flex items-center gap-2">
              <input
                id="allow_overlap"
                v-model="shiftForm.allow_overlap"
                type="checkbox"
                class="rounded border-rose-300 text-rose-600 focus:ring-rose-500 cursor-pointer"
              />
              <label for="allow_overlap" class="text-[11px] font-semibold text-rose-900 cursor-pointer">
                Supervisor Override: Allow overlapping duty shifts
              </label>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <input
                id="publish_now"
                v-model="shiftForm.is_published"
                type="checkbox"
                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
              />
              <label for="publish_now" class="text-xs font-medium text-slate-700 cursor-pointer">
                Publish immediately to roster
              </label>
            </div>

            <div class="flex gap-2">
              <button
                type="button"
                @click="isNewShiftModalOpen = false"
                class="px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 cursor-pointer"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="savingShift || (conflictAlert && !shiftForm.allow_overlap)"
                class="px-4 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white cursor-pointer disabled:opacity-50"
              >
                {{ savingShift ? 'Scheduling...' : 'Confirm Shift' }}
              </button>
            </div>
          </div>
        </form>
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
  },
});

const shifts = ref([]);
const staffMembers = ref([]);
const currentWeekStart = ref(getMonday(new Date()));
const departmentFilter = ref('');
const isNewShiftModalOpen = ref(false);
const savingShift = ref(false);
const publishing = ref(false);
const conflictAlert = ref(null);

const shiftForm = ref({
  staff_id: '',
  shift_name: 'Regular Clinical Duty',
  shift_type: 'morning',
  department: '',
  shift_date: new Date().toISOString().split('T')[0],
  start_time: '08:00',
  end_time: '16:00',
  is_published: false,
  allow_overlap: false,
});

function getMonday(d) {
  d = new Date(d);
  const day = d.getDay();
  const diff = d.getDate() - day + (day === 0 ? -6 : 1);
  return new Date(d.setDate(diff));
}

const weekDays = computed(() => {
  const days = [];
  const start = new Date(currentWeekStart.value);
  for (let i = 0; i < 7; i++) {
    const d = new Date(start);
    d.setDate(start.getDate() + i);
    const dateStr = d.toISOString().split('T')[0];
    days.push({
      date: d,
      dateStr,
      dayName: d.toLocaleDateString('en-US', { weekday: 'short' }),
      dayNumber: d.getDate(),
    });
  }
  return days;
});

const weekStartFormatted = computed(() => {
  return currentWeekStart.value.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
});

const weekEndFormatted = computed(() => {
  const end = new Date(currentWeekStart.value);
  end.setDate(end.getDate() + 6);
  return end.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
});

const isToday = (dateStr) => {
  return dateStr === new Date().toISOString().split('T')[0];
};

const navigateWeek = (direction) => {
  const next = new Date(currentWeekStart.value);
  next.setDate(next.getDate() + direction * 7);
  currentWeekStart.value = next;
  fetchShifts();
};

const jumpToCurrentWeek = () => {
  currentWeekStart.value = getMonday(new Date());
  fetchShifts();
};

const fetchShifts = async () => {
  try {
    const startStr = weekDays.value[0].dateStr;
    const endStr = weekDays.value[6].dateStr;

    const params = new URLSearchParams();
    if (props.branchId) params.append('branch_id', props.branchId);
    params.append('start_date', startStr);
    params.append('end_date', endStr);
    if (departmentFilter.value) params.append('department', departmentFilter.value);

    const res = await fetch(`/api/v1/hr/shifts?${params.toString()}`, {
      headers: {
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
    });
    const json = await res.json();
    if (json.success) {
      shifts.value = json.data?.data || json.data || [];
    }
  } catch (err) {
    console.error('Failed to load roster shifts', err);
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
    }
  } catch (err) {
    console.error('Failed to load staff for roster assignment', err);
  }
};

const getShiftsForDate = (dateStr) => {
  return shifts.value.filter((s) => s.shift_date === dateStr);
};

const getShiftStyleClass = (shift) => {
  if (shift.status === 'cancelled') return 'bg-slate-100 border-slate-200 text-slate-400 opacity-60';
  if (shift.shift_type === 'night') return 'bg-purple-50/80 border-purple-200 text-purple-950';
  if (shift.shift_type === 'evening') return 'bg-amber-50/80 border-amber-200 text-amber-950';
  return 'bg-blue-50/80 border-blue-200 text-blue-950';
};

const openNewShiftModal = () => {
  conflictAlert.value = null;
  shiftForm.value = {
    staff_id: staffMembers.value[0]?.id || '',
    shift_name: 'Clinical Duty Shift',
    shift_type: 'morning',
    department: staffMembers.value[0]?.department || '',
    shift_date: new Date().toISOString().split('T')[0],
    start_time: '08:00',
    end_time: '16:00',
    is_published: false,
    allow_overlap: false,
  };
  isNewShiftModalOpen.value = true;
};

const applyTypePreset = () => {
  if (shiftForm.value.shift_type === 'morning') {
    shiftForm.value.start_time = '08:00';
    shiftForm.value.end_time = '16:00';
  } else if (shiftForm.value.shift_type === 'evening') {
    shiftForm.value.start_time = '14:00';
    shiftForm.value.end_time = '22:00';
  } else if (shiftForm.value.shift_type === 'night') {
    shiftForm.value.start_time = '20:00';
    shiftForm.value.end_time = '08:00';
  }
  runConflictCheck();
};

const runConflictCheck = async () => {
  if (!shiftForm.value.staff_id || !shiftForm.value.shift_date || !shiftForm.value.start_time || !shiftForm.value.end_time) {
    conflictAlert.value = null;
    return;
  }

  try {
    const res = await fetch('/api/v1/hr/shifts/check-conflicts', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
      body: JSON.stringify({
        staff_id: shiftForm.value.staff_id,
        shift_date: shiftForm.value.shift_date,
        start_time: shiftForm.value.start_time,
        end_time: shiftForm.value.end_time,
      }),
    });
    const json = await res.json();
    if (json.success && json.data?.has_conflict) {
      conflictAlert.value = json.data;
    } else {
      conflictAlert.value = null;
    }
  } catch (err) {
    console.error('Failed to run pre-flight conflict check', err);
  }
};

const submitShift = async () => {
  savingShift.value = true;
  try {
    const res = await fetch('/api/v1/hr/shifts', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
      body: JSON.stringify(shiftForm.value),
    });
    const json = await res.json();
    if (json.success) {
      isNewShiftModalOpen.value = false;
      await fetchShifts();
    } else {
      alert(json.message || 'Error creating shift');
    }
  } catch (err) {
    console.error('Failed to create shift', err);
  } finally {
    savingShift.value = false;
  }
};

const cancelShift = async (shift) => {
  if (!confirm(`Cancel shift '${shift.shift_name}' for ${shift.staff?.full_name}?`)) return;
  try {
    const res = await fetch(`/api/v1/hr/shifts/${shift.id}/cancel`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
    });
    const json = await res.json();
    if (json.success) {
      await fetchShifts();
    }
  } catch (err) {
    console.error('Failed to cancel shift', err);
  }
};

const publishCurrentWeekRoster = async () => {
  publishing.value = true;
  try {
    const res = await fetch('/api/v1/hr/shifts/publish', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
      body: JSON.stringify({
        start_date: weekDays.value[0].dateStr,
        end_date: weekDays.value[6].dateStr,
      }),
    });
    const json = await res.json();
    if (json.success) {
      alert(json.message || 'Weekly roster published successfully!');
      await fetchShifts();
    }
  } catch (err) {
    console.error('Failed to publish weekly roster', err);
  } finally {
    publishing.value = false;
  }
};

onMounted(() => {
  fetchShifts();
  fetchStaffMembers();
});
</script>
