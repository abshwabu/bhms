<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full overflow-hidden border border-slate-200 transition-all">
      <!-- Header -->
      <div class="px-6 py-4 bg-gradient-to-r from-blue-700 to-indigo-800 text-white flex justify-between items-center">
        <div>
          <h2 class="text-xl font-bold flex items-center gap-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
            Patient Intake Registration
          </h2>
          <p class="text-blue-100 text-xs mt-0.5">Unique Medical Record Number (MRN) will be automatically generated upon creation.</p>
        </div>
        <div class="flex items-center gap-3">
          <button
            type="button"
            @click="fillDemoData"
            class="px-3 py-1.5 bg-white/15 hover:bg-white/25 active:scale-95 text-white rounded-lg text-xs font-semibold flex items-center gap-1.5 transition border border-white/20 cursor-pointer shadow-sm"
            title="Populate complete test patient data"
          >
            <span>⚡</span>
            <span>Auto-Fill Demo</span>
          </button>
          <button @click="$emit('close')" class="text-blue-200 hover:text-white text-2xl font-bold leading-none cursor-pointer">&times;</button>
        </div>
      </div>

      <!-- Registration Flow Selector -->
      <div class="px-6 pt-5 bg-slate-50 border-b border-slate-200">
        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Select Intake Flow</label>
        <div class="grid grid-cols-3 gap-3 pb-4">
          <button
            type="button"
            @click="setRegistrationType('walk_in')"
            :class="form.registration_type === 'walk_in' ? 'border-blue-600 bg-blue-50/80 text-blue-700 ring-2 ring-blue-500/20' : 'border-slate-200 hover:bg-white text-slate-700'"
            class="flex items-center gap-3 p-3 rounded-xl border text-left font-medium transition cursor-pointer"
          >
            <span class="w-8 h-8 rounded-lg flex items-center justify-center bg-blue-100 text-blue-700 font-bold text-sm">W</span>
            <div>
              <div class="text-sm font-semibold">Walk-In Intake</div>
              <div class="text-xs text-slate-500">Standard general admission</div>
            </div>
          </button>

          <button
            type="button"
            @click="setRegistrationType('referral')"
            :class="form.registration_type === 'referral' ? 'border-indigo-600 bg-indigo-50/80 text-indigo-700 ring-2 ring-indigo-500/20' : 'border-slate-200 hover:bg-white text-slate-700'"
            class="flex items-center gap-3 p-3 rounded-xl border text-left font-medium transition cursor-pointer"
          >
            <span class="w-8 h-8 rounded-lg flex items-center justify-center bg-indigo-100 text-indigo-700 font-bold text-sm">R</span>
            <div>
              <div class="text-sm font-semibold">Referral Intake</div>
              <div class="text-xs text-slate-500">External clinic / doctor transfer</div>
            </div>
          </button>

          <button
            type="button"
            @click="setRegistrationType('emergency')"
            :class="form.registration_type === 'emergency' ? 'border-red-600 bg-red-50/80 text-red-700 ring-2 ring-red-500/20' : 'border-slate-200 hover:bg-white text-slate-700'"
            class="flex items-center gap-3 p-3 rounded-xl border text-left font-medium transition cursor-pointer"
          >
            <span class="w-8 h-8 rounded-lg flex items-center justify-center bg-red-100 text-red-700 font-bold text-sm">E</span>
            <div>
              <div class="text-sm font-semibold">Emergency / Fast-Track</div>
              <div class="text-xs text-slate-500">Immediate triage & unknown patient</div>
            </div>
          </button>
        </div>
      </div>

      <!-- Emergency Warning Banner -->
      <div v-if="form.registration_type === 'emergency'" class="px-6 py-3 bg-red-50 border-b border-red-200 flex items-center justify-between text-red-800 text-xs">
        <div class="flex items-center gap-2">
          <span class="animate-ping w-2 h-2 rounded-full bg-red-600"></span>
          <span class="font-semibold">EMERGENCY INTAKE MODE ACTIVE:</span> Only critical fields are required. Name defaults to "Unknown" if unidentified.
        </div>
        <div class="flex items-center gap-2">
          <span class="text-xs text-red-600 font-bold uppercase">Triage Level:</span>
          <select v-model="form.triage_level" class="text-xs font-bold rounded-lg border border-red-300 bg-white py-1 px-2 text-red-700 focus:ring-red-500">
            <option value="critical">Critical (Red - Immediate)</option>
            <option value="urgent">Urgent (Orange - &lt;15 mins)</option>
            <option value="standard">Standard (Yellow - &lt;60 mins)</option>
            <option value="non_urgent">Non-Urgent (Green)</option>
          </select>
        </div>
      </div>

      <!-- Referral Details Banner -->
      <div v-if="form.registration_type === 'referral'" class="px-6 py-3 bg-indigo-50 border-b border-indigo-200 flex items-center gap-3">
        <label class="text-xs font-bold text-indigo-900 whitespace-nowrap">Referral Source / Doctor:</label>
        <input
          v-model="form.referral_source"
          type="text"
          placeholder="e.g. St. Jude Clinic / Dr. Marcus Welby"
          class="w-full text-xs rounded-lg border border-indigo-300 bg-white py-1.5 px-3 focus:ring-indigo-500 text-slate-800"
        />
      </div>

      <!-- Form Body -->
      <form @submit.prevent="submitForm" class="p-6 max-h-[60vh] overflow-y-auto space-y-6">
        <!-- Error alert -->
        <div v-if="errorMessage" class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs flex items-center justify-between">
          <span>{{ errorMessage }}</span>
          <button type="button" @click="errorMessage = ''" class="font-bold">&times;</button>
        </div>

        <!-- Section 1: Demographics -->
        <div>
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">1. Patient Demographics</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-medium text-slate-700 mb-1">
                First Name <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.first_name"
                type="text"
                required
                :placeholder="form.registration_type === 'emergency' ? 'e.g. Trauma Alpha' : 'First Name'"
                class="w-full text-sm rounded-lg border border-slate-300 py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-700 mb-1">Middle Name</label>
              <input
                v-model="form.middle_name"
                type="text"
                placeholder="Middle Name"
                class="w-full text-sm rounded-lg border border-slate-300 py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-700 mb-1">
                Last Name <span v-if="form.registration_type !== 'emergency'" class="text-red-500">*</span>
              </label>
              <input
                v-model="form.last_name"
                type="text"
                :required="form.registration_type !== 'emergency'"
                :placeholder="form.registration_type === 'emergency' ? 'Unknown (Optional)' : 'Last Name'"
                class="w-full text-sm rounded-lg border border-slate-300 py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <div class="flex justify-between items-center mb-1">
                <label class="text-xs font-medium text-slate-700">
                  Date of Birth <span v-if="form.registration_type !== 'emergency'" class="text-red-500">*</span>
                </label>
                <label class="text-[11px] text-slate-500 flex items-center gap-1 cursor-pointer">
                  <input type="checkbox" v-model="form.is_dob_estimated" class="rounded text-blue-600 focus:ring-0" />
                  Estimated
                </label>
              </div>
              <div class="flex gap-2">
                <input
                  v-model="form.date_of_birth"
                  type="date"
                  :required="form.registration_type !== 'emergency'"
                  class="w-full text-sm rounded-lg border border-slate-300 py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                <input
                  v-if="form.is_dob_estimated"
                  v-model.number="estimatedAge"
                  @input="handleEstimatedAgeInput"
                  type="number"
                  min="0"
                  max="125"
                  placeholder="Age"
                  class="w-20 text-sm rounded-lg border border-blue-300 py-2 px-2 bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 text-center font-bold"
                  title="Enter estimated age in years"
                />
              </div>
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-700 mb-1">
                Gender <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.gender"
                required
                class="w-full text-sm rounded-lg border border-slate-300 py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
              >
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
                <option value="unknown">Unknown</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-700 mb-1">Blood Group</label>
              <select
                v-model="form.blood_group"
                class="w-full text-sm rounded-lg border border-slate-300 py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
              >
                <option value="">Unknown / Not Tested</option>
                <option value="A+">A+</option>
                <option value="A-">A-</option>
                <option value="B+">B+</option>
                <option value="B-">B-</option>
                <option value="AB+">AB+</option>
                <option value="AB-">AB-</option>
                <option value="O+">O+</option>
                <option value="O-">O-</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Section 2: Contact & Identification -->
        <div>
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">2. Identification & Contact Details</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-medium text-slate-700 mb-1">National ID / SSN</label>
              <input
                v-model="form.national_id"
                type="text"
                placeholder="e.g. NAT-12345678"
                class="w-full text-sm rounded-lg border border-slate-300 py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-700 mb-1">Primary Phone</label>
              <input
                v-model="form.phone"
                type="tel"
                placeholder="+1 (555) 000-0000"
                class="w-full text-sm rounded-lg border border-slate-300 py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-700 mb-1">Email Address</label>
              <input
                v-model="form.email"
                type="email"
                placeholder="patient@hospital.org"
                class="w-full text-sm rounded-lg border border-slate-300 py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div class="md:col-span-2">
              <label class="block text-xs font-medium text-slate-700 mb-1">Street Address</label>
              <input
                v-model="form.address.street"
                type="text"
                placeholder="123 Hospital Way"
                class="w-full text-sm rounded-lg border border-slate-300 py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-700 mb-1">City / State</label>
              <input
                v-model="form.address.city"
                type="text"
                placeholder="City, State"
                class="w-full text-sm rounded-lg border border-slate-300 py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>
        </div>

        <!-- Section 3: Emergency Contact -->
        <div>
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">3. Next of Kin / Emergency Contact</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-medium text-slate-700 mb-1">Contact Name</label>
              <input
                v-model="form.emergency_contact.name"
                type="text"
                placeholder="Full Name"
                class="w-full text-sm rounded-lg border border-slate-300 py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-700 mb-1">Relationship</label>
              <input
                v-model="form.emergency_contact.relationship"
                type="text"
                placeholder="e.g. Spouse, Parent"
                class="w-full text-sm rounded-lg border border-slate-300 py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-700 mb-1">Emergency Phone</label>
              <input
                v-model="form.emergency_contact.phone"
                type="tel"
                placeholder="+1 (555) 000-0000"
                class="w-full text-sm rounded-lg border border-slate-300 py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>
        </div>

        <!-- Section 4: Initial Clinical Notes -->
        <div>
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">4. Clinical Triage Notes</h3>
          <textarea
            v-model="form.notes"
            rows="2"
            placeholder="Chief complaint or intake observations..."
            class="w-full text-sm rounded-lg border border-slate-300 p-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
          ></textarea>
        </div>

        <!-- Footer Actions -->
        <div class="pt-4 border-t border-slate-200 flex justify-end gap-3">
          <button
            type="button"
            @click="$emit('close')"
            class="px-5 py-2 text-sm font-medium rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50 transition cursor-pointer"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="isSubmitting"
            class="px-6 py-2 text-sm font-semibold rounded-xl bg-blue-600 hover:bg-blue-700 text-white shadow-md shadow-blue-500/20 transition disabled:opacity-50 cursor-pointer flex items-center gap-2"
          >
            <span v-if="isSubmitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            Register Patient & Generate MRN
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';

const props = defineProps({
  isOpen: { type: Boolean, default: false },
  branchId: { type: String, required: true },
});

const emit = defineEmits(['close', 'patientCreated']);

const isSubmitting = ref(false);
const errorMessage = ref('');

const form = reactive({
  registration_type: 'walk_in',
  triage_level: 'standard',
  referral_source: '',
  first_name: '',
  middle_name: '',
  last_name: '',
  date_of_birth: '',
  is_dob_estimated: false,
  gender: 'male',
  blood_group: '',
  national_id: '',
  phone: '',
  email: '',
  address: { street: '', city: '', state: '', postal_code: '', country: 'USA' },
  emergency_contact: { name: '', relationship: '', phone: '' },
  notes: '',
});

const estimatedAge = ref('');

function handleEstimatedAgeInput() {
  if (estimatedAge.value !== '' && !isNaN(estimatedAge.value)) {
    const birthYear = new Date().getFullYear() - parseInt(estimatedAge.value, 10);
    form.date_of_birth = `${birthYear}-01-01`;
  }
}

function setRegistrationType(type) {
  form.registration_type = type;
  if (type === 'emergency') {
    form.triage_level = 'critical';
    if (!form.first_name) form.first_name = 'Trauma Unknown';
    form.is_dob_estimated = true;
    if (!form.date_of_birth) {
      form.date_of_birth = '1990-01-01';
    }
  }
}

function fillDemoData() {
  const firstNames = ['Alexander', 'Eleanor', 'Marcus', 'Sophia', 'Liam', 'Olivia', 'David', 'Emma', 'Ethan', 'Isabella'];
  const lastNames = ['Sterling', 'Vance', 'Chen', 'Rodriguez', 'Patel', 'Kim', 'O\'Connor', 'Nakamura', 'Williams', 'Davis'];
  const randomFirst = firstNames[Math.floor(Math.random() * firstNames.length)];
  const randomLast = lastNames[Math.floor(Math.random() * lastNames.length)];
  const randomNum = Math.floor(100000 + Math.random() * 900000);
  const birthYear = 1970 + Math.floor(Math.random() * 35);
  const birthMonth = String(1 + Math.floor(Math.random() * 12)).padStart(2, '0');
  const birthDay = String(1 + Math.floor(Math.random() * 28)).padStart(2, '0');

  form.first_name = randomFirst;
  form.last_name = randomLast;
  form.middle_name = 'J.';
  form.date_of_birth = `${birthYear}-${birthMonth}-${birthDay}`;
  form.is_dob_estimated = false;
  estimatedAge.value = '';
  form.gender = Math.random() > 0.5 ? 'male' : 'female';
  form.blood_group = ['A+', 'O+', 'B+', 'AB+'][Math.floor(Math.random() * 4)];
  form.national_id = `NAT-${randomNum}`;
  form.phone = `+1555${randomNum}`;
  form.email = `${randomFirst.toLowerCase()}.${randomLast.toLowerCase()}@hospital.local`;
  form.address = {
    street: `${Math.floor(100 + Math.random() * 900)} Metro Blvd`,
    city: 'Metropolis',
    state: 'NY',
    postal_code: '10001',
    country: 'USA'
  };
  form.emergency_contact = {
    name: `Dr. ${randomLast}`,
    relationship: 'Spouse',
    phone: `+1555${randomNum + 1}`
  };
  form.notes = 'Routine checkup. No acute distress observed.';
  errorMessage.value = '';
}

async function submitForm() {
  isSubmitting.value = true;
  errorMessage.value = '';

  // Safe client-side defaults so registration never fails validation
  if (!form.first_name || !form.first_name.trim()) {
    form.first_name = form.registration_type === 'emergency' ? 'Trauma Unknown' : 'Walk-In Patient';
  }
  if (!form.last_name || !form.last_name.trim()) {
    form.last_name = form.registration_type === 'emergency' ? 'Unknown' : 'Walk-In';
  }
  if (!form.date_of_birth) {
    form.date_of_birth = '1995-01-01';
    form.is_dob_estimated = true;
  }
  if (!form.gender) {
    form.gender = 'unknown';
  }
  if (!form.registration_type) {
    form.registration_type = 'walk_in';
  }

  // Clean empty strings to null/undefined before POST
  const payload = { ...form };
  payload.first_name = payload.first_name.trim();
  payload.last_name = payload.last_name.trim();
  payload.middle_name = payload.middle_name?.trim() || null;
  payload.blood_group = payload.blood_group || null;
  payload.national_id = payload.national_id?.trim() || null;
  payload.phone = payload.phone?.trim() || null;
  payload.email = payload.email?.trim() || null;
  payload.referral_source = payload.referral_source?.trim() || null;
  payload.notes = payload.notes?.trim() || null;
  payload.date_of_birth = payload.date_of_birth;

  // Clean emergency contact
  if (!payload.emergency_contact?.name?.trim() && !payload.emergency_contact?.phone?.trim()) {
    delete payload.emergency_contact;
  }

  // Clean address
  if (!payload.address?.street?.trim() && !payload.address?.city?.trim()) {
    delete payload.address;
  }

  try {
    const res = await axios.post('/api/v1/patients', payload, {
      headers: {
        'X-Branch-ID': props.branchId,
        'Accept': 'application/json',
      },
    });

    emit('patientCreated', res.data.data);
    emit('close');
  } catch (err) {
    if (err.response?.data?.errors) {
      const fieldErrors = Object.values(err.response.data.errors).flat();
      errorMessage.value = fieldErrors.join(' ');
    } else if (err.response?.data?.message) {
      errorMessage.value = err.response.data.message;
    } else {
      errorMessage.value = 'Failed to register patient. Please check required fields.';
    }
    console.error('[Registration] Error Details:', err.response?.data || err);
  } finally {
    isSubmitting.value = false;
  }
}
</script>
