<template>
  <div v-if="patient" class="space-y-6">
    <!-- Back Button -->
    <div class="flex items-center justify-between">
      <button
        @click="$emit('back')"
        class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-900 bg-white px-4 py-2 rounded-xl border border-slate-200 shadow-sm transition cursor-pointer"
      >
        &larr; Back to Patient Search
      </button>

      <div class="flex items-center gap-2">
        <span class="text-xs font-mono px-3 py-1 bg-slate-100 border border-slate-200 rounded-lg text-slate-600 font-semibold">
          UUID: {{ patient.id }}
        </span>
      </div>
    </div>

    <!-- Critical Allergy Warning Ribbon (Medical Safety) -->
    <div
      v-if="hasSevereAllergies"
      class="p-4 bg-gradient-to-r from-red-600 to-rose-700 text-white rounded-2xl shadow-lg shadow-red-500/10 flex items-center justify-between animate-pulse"
    >
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center font-black text-xl">⚠️</div>
        <div>
          <h4 class="font-extrabold text-sm uppercase tracking-wider">CRITICAL ALLERGY ALERT</h4>
          <p class="text-xs text-red-100">Patient has confirmed life-threatening / severe adverse drug or food reactions.</p>
        </div>
      </div>
      <div class="flex flex-wrap gap-2">
        <span
          v-for="allergy in severeAllergies"
          :key="allergy.id"
          class="px-3 py-1 bg-white text-red-700 font-black rounded-lg text-xs tracking-wide shadow-sm"
        >
          {{ allergy.allergen }}: {{ allergy.reaction }} ({{ allergy.severity }})
        </span>
      </div>
    </div>

    <!-- Patient Header Card -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/80">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 pb-6 border-b border-slate-100">
        <div class="flex items-start gap-4">
          <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white flex items-center justify-center text-2xl font-black shadow-md shadow-blue-500/20">
            {{ patient.first_name ? patient.first_name[0] : 'P' }}
          </div>
          <div>
            <div class="flex items-center gap-3">
              <h2 class="text-2xl font-black text-slate-900 tracking-tight">{{ patient.full_name }}</h2>
              <span class="font-mono text-xs font-bold px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-200">
                {{ patient.mrn }}
              </span>
            </div>
            <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500 mt-2 font-medium">
              <span><strong>Age:</strong> {{ patient.age !== null ? patient.age + ' years' : 'N/A' }}</span>
              <span>&bull;</span>
              <span><strong>DOB:</strong> {{ patient.date_of_birth || 'Estimated' }}</span>
              <span>&bull;</span>
              <span class="capitalize"><strong>Gender:</strong> {{ patient.gender }}</span>
              <span>&bull;</span>
              <span><strong>Blood:</strong> <strong class="text-rose-600 font-black">{{ patient.blood_group || 'Unknown' }}</strong></span>
              <span>&bull;</span>
              <span><strong>Phone:</strong> {{ patient.phone || 'None' }}</span>
              <span>&bull;</span>
              <span><strong>National ID:</strong> {{ patient.national_id || 'None' }}</span>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <span
            :class="{
              'bg-blue-50 text-blue-700 border-blue-200': patient.registration_type === 'walk_in',
              'bg-indigo-50 text-indigo-700 border-indigo-200': patient.registration_type === 'referral',
              'bg-red-50 text-red-700 border-red-200': patient.registration_type === 'emergency',
            }"
            class="px-3 py-1.5 rounded-xl border text-xs font-bold uppercase tracking-wider"
          >
            Intake: {{ patient.registration_type }}
          </span>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <div class="flex flex-wrap gap-2 pt-4">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="activeTab === tab.id ? 'bg-blue-600 text-white shadow-sm shadow-blue-500/20' : 'text-slate-600 hover:bg-slate-100'"
          class="px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer"
        >
          {{ tab.label }}
        </button>
      </div>
    </div>

    <!-- TAB 1: Clinical Medical History -->
    <div v-if="activeTab === 'history'" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/80 space-y-4">
      <div class="flex justify-between items-center">
        <div>
          <h3 class="text-base font-bold text-slate-800">Chronic Conditions & Past Medical History</h3>
          <p class="text-xs text-slate-400">Strictly restricted to licensed clinical staff (Doctors, Nurses, Admins).</p>
        </div>
        <button
          @click="showAddHistoryModal = true"
          class="text-xs font-bold px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-xl transition cursor-pointer"
        >
          + Add Condition / Surgery
        </button>
      </div>

      <div v-if="!patient.medical_history || patient.medical_history.length === 0" class="text-center py-8 text-slate-400 text-sm">
        No past medical history or chronic conditions recorded.
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div
          v-for="item in patient.medical_history"
          :key="item.id"
          class="p-4 rounded-xl border border-slate-200 hover:border-blue-300 transition bg-slate-50/50"
        >
          <div class="flex justify-between items-start">
            <div>
              <span class="text-[10px] uppercase font-black tracking-wider px-2 py-0.5 rounded bg-slate-200 text-slate-700">
                {{ item.category.replace('_', ' ') }}
              </span>
              <h4 class="font-bold text-slate-900 text-sm mt-1.5">{{ item.condition_or_procedure }}</h4>
              <p v-if="item.icd10_code" class="text-xs font-mono text-slate-500">ICD-10: {{ item.icd10_code }}</p>
            </div>
            <span class="text-xs font-semibold px-2 py-0.5 rounded-full capitalize bg-emerald-100 text-emerald-800">
              {{ item.status }}
            </span>
          </div>
          <div class="mt-3 text-xs text-slate-500 flex justify-between border-t border-slate-200/60 pt-2">
            <span>Diagnosed: {{ item.diagnosed_date || 'Unknown' }}</span>
            <span v-if="item.recorded_by">Recorded by: {{ item.recorded_by.name }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 2: Allergies -->
    <div v-if="activeTab === 'allergies'" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/80 space-y-4">
      <div class="flex justify-between items-center">
        <div>
          <h3 class="text-base font-bold text-slate-800">Known Allergies & Adverse Reactions</h3>
          <p class="text-xs text-slate-400">Clinical cross-check for prescribing and medication dispensing.</p>
        </div>
        <button
          @click="showAddAllergyModal = true"
          class="text-xs font-bold px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-xl transition cursor-pointer"
        >
          + Record New Allergy
        </button>
      </div>

      <div v-if="!patient.allergies || patient.allergies.length === 0" class="text-center py-8 text-slate-400 text-sm">
        No known drug or food allergies recorded.
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div
          v-for="allergy in patient.allergies"
          :key="allergy.id"
          class="p-4 rounded-xl border border-slate-200 bg-slate-50/50"
        >
          <div class="flex justify-between items-start">
            <div>
              <span class="text-[10px] uppercase font-black px-2 py-0.5 rounded bg-amber-100 text-amber-800">
                {{ allergy.allergen_type }}
              </span>
              <h4 class="font-bold text-slate-900 text-sm mt-1.5">{{ allergy.allergen }}</h4>
              <p class="text-xs text-slate-600 mt-1">Reaction: <strong>{{ allergy.reaction }}</strong></p>
            </div>
            <span
              :class="{
                'bg-red-100 text-red-800 ring-1 ring-red-300 font-black': allergy.severity === 'life_threatening' || allergy.severity === 'severe',
                'bg-amber-100 text-amber-800': allergy.severity === 'moderate',
                'bg-slate-100 text-slate-700': allergy.severity === 'mild',
              }"
              class="text-xs px-2.5 py-0.5 rounded-full capitalize"
            >
              {{ allergy.severity.replace('_', ' ') }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 3: Insurance -->
    <div v-if="activeTab === 'insurance'" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/80 space-y-4">
      <div class="flex justify-between items-center">
        <div>
          <h3 class="text-base font-bold text-slate-800">Insurance Policies & HMO Coverage</h3>
          <p class="text-xs text-slate-400">Payer details, coverage percentage, and copay terms.</p>
        </div>
        <button
          @click="showAddInsuranceModal = true"
          class="text-xs font-bold px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-xl transition cursor-pointer"
        >
          + Add Insurance Policy
        </button>
      </div>

      <div v-if="!patient.insurance || patient.insurance.length === 0" class="text-center py-8 text-slate-400 text-sm">
        No active insurance policy on file. Patient is self-paying.
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div
          v-for="policy in patient.insurance"
          :key="policy.id"
          class="p-5 rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 to-blue-50/30 space-y-3"
        >
          <div class="flex justify-between items-start">
            <div>
              <h4 class="font-extrabold text-slate-900 text-sm">{{ policy.provider_name }}</h4>
              <p class="text-xs font-mono text-slate-500 mt-0.5">Policy #: {{ policy.policy_number }}</p>
            </div>
            <span class="text-xs font-bold px-2 py-0.5 rounded-md uppercase bg-blue-100 text-blue-800">
              {{ policy.coverage_type }}
            </span>
          </div>

          <div class="grid grid-cols-2 gap-2 text-xs border-t border-slate-200 pt-3">
            <div>
              <span class="text-slate-400 block text-[10px] uppercase">Coverage</span>
              <span class="font-bold text-slate-800">{{ policy.coverage_percentage }}%</span>
            </div>
            <div>
              <span class="text-slate-400 block text-[10px] uppercase">Copay Amount</span>
              <span class="font-bold text-slate-800">${{ policy.copay_amount_formatted }}</span>
            </div>
            <div>
              <span class="text-slate-400 block text-[10px] uppercase">Valid Period</span>
              <span class="text-slate-700">{{ policy.valid_from }} to {{ policy.valid_until || 'Ongoing' }}</span>
            </div>
            <div>
              <span class="text-slate-400 block text-[10px] uppercase">Verification</span>
              <span class="text-emerald-700 font-bold" v-if="policy.verified_at">✓ Verified</span>
              <span class="text-amber-700 font-bold" v-else>Pending</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 4: Family & Dependents Linking -->
    <div v-if="activeTab === 'family'" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/80 space-y-4">
      <div class="flex justify-between items-center">
        <div>
          <h3 class="text-base font-bold text-slate-800">Family & Dependent Relationships</h3>
          <p class="text-xs text-slate-400">Link child dependents, spouses, parents, and billing guarantors.</p>
        </div>
        <button
          @click="showAddFamilyModal = true"
          class="text-xs font-bold px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-xl transition cursor-pointer"
        >
          + Link Family Member / Dependent
        </button>
      </div>

      <div v-if="!patient.relationships || patient.relationships.length === 0" class="text-center py-8 text-slate-400 text-sm">
        No family or dependent links recorded.
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div
          v-for="rel in patient.relationships"
          :key="rel.id"
          class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-2"
        >
          <div class="flex justify-between items-start">
            <div>
              <h4 class="font-bold text-slate-900 text-sm">{{ rel.display_name }}</h4>
              <p class="text-xs text-slate-500 capitalize">Relationship: <strong>{{ rel.relationship_type }}</strong></p>
            </div>
            <div class="flex gap-1">
              <span v-if="rel.is_guardian" class="text-[10px] font-bold px-2 py-0.5 rounded bg-purple-100 text-purple-800">
                Guardian
              </span>
              <span v-if="rel.is_billing_guarantor" class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800">
                Guarantor
              </span>
            </div>
          </div>
          <div class="text-xs text-slate-500 border-t border-slate-200 pt-2 flex justify-between">
            <span>Phone: {{ rel.phone || 'N/A' }}</span>
            <span v-if="rel.related_patient" class="text-blue-600 font-mono text-[11px]">
              Linked MRN: {{ rel.related_patient.mrn }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  patient: { type: Object, required: true },
  branchId: { type: String, required: true },
});

const emit = defineEmits(['back', 'refresh']);

const activeTab = ref('history');
const showAddHistoryModal = ref(false);
const showAddAllergyModal = ref(false);
const showAddInsuranceModal = ref(false);
const showAddFamilyModal = ref(false);

const tabs = [
  { id: 'history', label: 'Medical History' },
  { id: 'allergies', label: 'Allergies' },
  { id: 'insurance', label: 'Insurance / HMO' },
  { id: 'family', label: 'Family & Dependents' },
];

const severeAllergies = computed(() => {
  if (!props.patient?.allergies) return [];
  return props.patient.allergies.filter(
    (a) => a.severity === 'life_threatening' || a.severity === 'severe'
  );
});

const hasSevereAllergies = computed(() => severeAllergies.value.length > 0);
</script>
