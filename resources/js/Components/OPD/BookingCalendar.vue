<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h2 class="text-xl font-black text-slate-900 tracking-tight">Appointment Booking & Doctor Calendar</h2>
        <p class="text-xs text-slate-500 mt-1">Schedule outpatient visits, detect slot conflicts, and manage appointments.</p>
      </div>
      <div class="flex items-center gap-3">
        <button
          @click="showBookingModal = true"
          class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition shadow-sm flex items-center gap-2 cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Book Appointment
        </button>
      </div>
    </div>

    <!-- Doctor Slot Availability Checker Card -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
      <h3 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2">
        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        Check Doctor Real-Time Availability
      </h3>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Select Doctor</label>
          <select
            v-model="selectedDoctorId"
            @change="fetchAvailability"
            class="w-full text-xs rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500 p-2.5 border bg-white"
          >
            <option value="">-- Choose Doctor --</option>
            <option v-for="doc in doctors" :key="doc.id" :value="doc.id">
              {{ doc.name }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Select Date</label>
          <input
            type="date"
            v-model="selectedDate"
            @change="fetchAvailability"
            class="w-full text-xs rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500 p-2.5 border"
          />
        </div>

        <div class="flex items-end">
          <button
            @click="fetchAvailability"
            :disabled="!selectedDoctorId || !selectedDate || loadingAvailability"
            class="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-900 disabled:opacity-50 text-white text-xs font-bold transition"
          >
            {{ loadingAvailability ? 'Checking Availability...' : 'Refresh Slots' }}
          </button>
        </div>
      </div>

      <!-- Slot Availability Grid -->
      <div v-if="availabilityData">
        <div v-if="!availabilityData.is_available" class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs">
          {{ availabilityData.reason || 'Doctor is not scheduled or unavailable for this day.' }}
        </div>

        <div v-else>
          <div class="flex items-center justify-between text-xs text-slate-500 mb-3">
            <span class="font-semibold text-slate-700">Available Slots for {{ availabilityData.date }}</span>
            <div class="flex items-center gap-4">
              <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-emerald-500 inline-block"></span> Free</span>
              <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-rose-400 inline-block"></span> Booked</span>
            </div>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-2.5">
            <button
              v-for="slot in availabilityData.slots"
              :key="slot.start_time"
              :disabled="slot.is_booked"
              @click="quickBookSlot(slot)"
              :class="slot.is_booked 
                ? 'bg-rose-50 border-rose-200 text-rose-400 cursor-not-allowed line-through' 
                : 'bg-emerald-50 border-emerald-300 text-emerald-800 hover:bg-emerald-100 hover:border-emerald-400 cursor-pointer'"
              class="p-2.5 rounded-xl border text-center transition flex flex-col items-center justify-center text-xs font-medium"
            >
              <span class="font-bold">{{ slot.start_time }}</span>
              <span class="text-[10px] opacity-75">{{ slot.is_booked ? 'Booked' : 'Available' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Upcoming Appointments Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
      <div class="p-6 border-b border-slate-100 flex items-center justify-between">
        <div>
          <h3 class="font-bold text-slate-900 text-sm">Scheduled Appointments</h3>
          <p class="text-xs text-slate-500">Upcoming consultations and check-ins</p>
        </div>
        <button
          @click="fetchAppointments"
          class="text-xs text-blue-600 font-semibold hover:underline"
        >
          Refresh List
        </button>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50 border-b border-slate-100 text-slate-400 uppercase text-[10px] font-bold">
            <tr>
              <th class="py-3.5 px-6">Appt #</th>
              <th class="py-3.5 px-6">Patient</th>
              <th class="py-3.5 px-6">Doctor</th>
              <th class="py-3.5 px-6">Date & Time</th>
              <th class="py-3.5 px-6">Type</th>
              <th class="py-3.5 px-6">Status</th>
              <th class="py-3.5 px-6 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="appointments.length === 0">
              <td colspan="7" class="py-8 text-center text-slate-400">No scheduled appointments found.</td>
            </tr>
            <tr v-for="appt in appointments" :key="appt.id" class="hover:bg-slate-50/50 transition">
              <td class="py-4 px-6 font-mono font-bold text-slate-800">{{ appt.appointment_number }}</td>
              <td class="py-4 px-6">
                <div class="font-bold text-slate-800">{{ appt.patient ? appt.patient.full_name : 'N/A' }}</div>
                <div class="text-[10px] font-mono text-slate-400">{{ appt.patient ? appt.patient.mrn : '' }}</div>
              </td>
              <td class="py-4 px-6 text-slate-700 font-medium">{{ appt.doctor ? appt.doctor.name : 'Unassigned' }}</td>
              <td class="py-4 px-6">
                <div class="font-semibold text-slate-800">{{ appt.appointment_date }}</div>
                <div class="text-[10px] text-slate-500 font-mono">{{ appt.time_range || appt.start_time }}</div>
              </td>
              <td class="py-4 px-6">
                <span class="px-2 py-0.5 rounded-full font-bold text-[10px] capitalize"
                  :class="{
                    'bg-blue-100 text-blue-700': appt.type === 'in_person',
                    'bg-purple-100 text-purple-700': appt.type === 'follow_up',
                    'bg-emerald-100 text-emerald-700': appt.type === 'telemedicine',
                    'bg-amber-100 text-amber-700': appt.type === 'walk_in'
                  }">
                  {{ appt.type ? appt.type.replace('_', ' ') : 'in person' }}
                </span>
              </td>
              <td class="py-4 px-6">
                <span class="px-2 py-0.5 rounded-full font-bold text-[10px] uppercase tracking-wider"
                  :class="{
                    'bg-amber-100 text-amber-800': appt.status === 'scheduled',
                    'bg-blue-100 text-blue-800': appt.status === 'checked_in',
                    'bg-emerald-100 text-emerald-800': appt.status === 'completed',
                    'bg-rose-100 text-rose-800': appt.status === 'cancelled',
                  }">
                  {{ appt.status }}
                </span>
              </td>
              <td class="py-4 px-6 text-right space-x-2">
                <button
                  v-if="appt.status === 'scheduled'"
                  @click="checkInPatient(appt)"
                  class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] transition"
                  title="Check in patient and issue queue token"
                >
                  Check In
                </button>
                <button
                  v-if="appt.status === 'scheduled'"
                  @click="openCancelModal(appt)"
                  class="px-2.5 py-1 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 font-semibold text-[11px] transition"
                >
                  Cancel
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Booking Modal -->
    <div v-if="showBookingModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
      <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 border border-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
          <h3 class="font-bold text-slate-900 text-base">Book Appointment</h3>
          <button @click="showBookingModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">&times;</button>
        </div>

        <form @submit.prevent="submitBooking" class="space-y-4 text-xs">
          <div v-if="bookingError" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs">
            {{ bookingError }}
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Patient MRN / ID *</label>
            <input
              type="text"
              v-model="bookingForm.patient_id"
              placeholder="Paste Patient UUID"
              required
              class="w-full text-xs rounded-xl border-slate-300 p-2.5 border"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Doctor *</label>
              <select v-model="bookingForm.doctor_id" required class="w-full text-xs rounded-xl border-slate-300 p-2.5 border bg-white">
                <option value="">Select Doctor</option>
                <option v-for="doc in doctors" :key="doc.id" :value="doc.id">{{ doc.name }}</option>
              </select>
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Department</label>
              <select v-model="bookingForm.department_id" class="w-full text-xs rounded-xl border-slate-300 p-2.5 border bg-white">
                <option value="">Select Department</option>
                <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Date *</label>
              <input type="date" v-model="bookingForm.appointment_date" required class="w-full text-xs rounded-xl border-slate-300 p-2.5 border" />
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Start Time *</label>
              <input type="time" v-model="bookingForm.start_time" required class="w-full text-xs rounded-xl border-slate-300 p-2.5 border" />
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">End Time</label>
              <input type="time" v-model="bookingForm.end_time" class="w-full text-xs rounded-xl border-slate-300 p-2.5 border" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Visit Type</label>
              <select v-model="bookingForm.type" class="w-full text-xs rounded-xl border-slate-300 p-2.5 border bg-white">
                <option value="in_person">In-Person Consultation</option>
                <option value="telemedicine">Telemedicine</option>
                <option value="follow_up">Follow-Up Visit</option>
                <option value="walk_in">Walk-in</option>
              </select>
            </div>
            <div v-if="bookingForm.type === 'follow_up'">
              <label class="block font-semibold text-slate-700 mb-1">Parent Appt ID</label>
              <input type="text" v-model="bookingForm.parent_appointment_id" placeholder="Previous Appt UUID" class="w-full text-xs rounded-xl border-slate-300 p-2.5 border" />
            </div>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Reason for Visit</label>
            <textarea v-model="bookingForm.reason_for_visit" rows="2" placeholder="Clinical reason or primary complaint" class="w-full text-xs rounded-xl border-slate-300 p-2.5 border"></textarea>
          </div>

          <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
            <button type="button" @click="showBookingModal = false" class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-semibold cursor-pointer">Cancel</button>
            <button type="submit" :disabled="submitting" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold transition shadow-sm cursor-pointer">
              {{ submitting ? 'Booking...' : 'Confirm Appointment' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
  branchId: { type: String, required: true },
});

const doctors = ref([
  { id: '01a092ae-dac6-703d-909b-677d83d0eeb1', name: 'Dr. Stephen Strange' },
  { id: '01a092ae-dac7-71b5-9ec6-368731383792', name: 'Dr. Beverly Crusher' }
]);
const departments = ref([]);
const appointments = ref([]);

const selectedDoctorId = ref('');
const selectedDate = ref(new Date().toISOString().split('T')[0]);
const availabilityData = ref(null);
const loadingAvailability = ref(false);

const showBookingModal = ref(false);
const submitting = ref(false);
const bookingError = ref('');

const bookingForm = ref({
  patient_id: '',
  doctor_id: '',
  department_id: '',
  appointment_date: new Date().toISOString().split('T')[0],
  start_time: '09:00',
  end_time: '09:30',
  type: 'in_person',
  parent_appointment_id: '',
  reason_for_visit: '',
});

async function fetchDepartments() {
  try {
    const res = await fetch('/api/v1/opd/departments', {
      headers: { 'X-Branch-ID': props.branchId, 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.data) departments.value = json.data;
  } catch (e) {
    console.error('Failed to fetch departments', e);
  }
}

async function fetchAppointments() {
  try {
    const res = await fetch('/api/v1/opd/appointments', {
      headers: { 'X-Branch-ID': props.branchId, 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.data) {
      appointments.value = json.data;
    }
  } catch (e) {
    console.error('Failed to fetch appointments', e);
  }
}

async function fetchAvailability() {
  if (!selectedDoctorId.value || !selectedDate.value) return;
  loadingAvailability.value = true;
  try {
    const res = await fetch(`/api/v1/opd/schedules/availability?doctor_id=${selectedDoctorId.value}&date=${selectedDate.value}`, {
      headers: { 'X-Branch-ID': props.branchId, 'Accept': 'application/json' }
    });
    const json = await res.json();
    availabilityData.value = json.data;
  } catch (e) {
    console.error('Failed to fetch availability', e);
  } finally {
    loadingAvailability.value = false;
  }
}

function quickBookSlot(slot) {
  bookingForm.value.doctor_id = selectedDoctorId.value;
  bookingForm.value.appointment_date = selectedDate.value;
  bookingForm.value.start_time = slot.start_time;
  bookingForm.value.end_time = slot.end_time;
  showBookingModal.value = true;
}

async function submitBooking() {
  submitting.value = true;
  bookingError.value = '';
  try {
    const res = await fetch('/api/v1/opd/appointments', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Branch-ID': props.branchId,
        'Accept': 'application/json'
      },
      body: JSON.stringify(bookingForm.value)
    });
    const json = await res.json();

    if (!res.ok) {
      bookingError.value = json.message || 'Slot conflict or booking error.';
      return;
    }

    showBookingModal.value = false;
    await fetchAppointments();
    if (selectedDoctorId.value && selectedDate.value) {
      await fetchAvailability();
    }
  } catch (e) {
    bookingError.value = 'Failed to submit booking: ' + e.message;
  } finally {
    submitting.value = false;
  }
}

async function checkInPatient(appt) {
  if (!confirm(`Check in ${appt.patient ? appt.patient.full_name : 'patient'} and issue queue token?`)) return;
  try {
    const res = await fetch(`/api/v1/opd/appointments/${appt.id}/check-in`, {
      method: 'POST',
      headers: {
        'X-Branch-ID': props.branchId,
        'Accept': 'application/json'
      }
    });
    const json = await res.json();
    if (res.ok) {
      alert(`Patient checked in! Queue Token: ${json.data.queue_token}`);
      await fetchAppointments();
    }
  } catch (e) {
    console.error('Check-in error', e);
  }
}

async function openCancelModal(appt) {
  const reason = prompt('Please enter cancellation reason:');
  if (!reason) return;
  try {
    const res = await fetch(`/api/v1/opd/appointments/${appt.id}/cancel`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Branch-ID': props.branchId,
        'Accept': 'application/json'
      },
      body: JSON.stringify({ cancellation_reason: reason })
    });
    if (res.ok) {
      await fetchAppointments();
    }
  } catch (e) {
    console.error('Cancel error', e);
  }
}

onMounted(async () => {
  await fetchDepartments();
  await fetchAppointments();
});
</script>
