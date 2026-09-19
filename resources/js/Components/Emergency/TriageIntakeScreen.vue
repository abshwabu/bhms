<template>
  <div class="space-y-6">
    <!-- Triage Queue KPIs -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3.5">
      <div class="bg-rose-50 border border-rose-200 p-4 rounded-2xl shadow-xs text-rose-950">
        <div class="flex items-center justify-between">
          <span class="text-[10px] font-black uppercase tracking-wider text-rose-700">ESI-1 Resuscitation</span>
          <span class="w-2.5 h-2.5 rounded-full bg-rose-600 animate-ping"></span>
        </div>
        <div class="text-2xl font-black text-rose-700 mt-1">
          {{ getCountByEsi(1) }}
        </div>
        <div class="text-[10px] text-rose-600 font-semibold mt-0.5">Immediate life threat</div>
      </div>

      <div class="bg-orange-50 border border-orange-200 p-4 rounded-2xl shadow-xs text-orange-950">
        <span class="text-[10px] font-black uppercase tracking-wider text-orange-700 block">ESI-2 Emergent</span>
        <div class="text-2xl font-black text-orange-700 mt-1">
          {{ getCountByEsi(2) }}
        </div>
        <div class="text-[10px] text-orange-600 font-semibold mt-0.5">High risk / danger vitals</div>
      </div>

      <div class="bg-amber-50 border border-amber-200 p-4 rounded-2xl shadow-xs text-amber-950">
        <span class="text-[10px] font-black uppercase tracking-wider text-amber-700 block">ESI-3 Urgent</span>
        <div class="text-2xl font-black text-amber-700 mt-1">
          {{ getCountByEsi(3) }}
        </div>
        <div class="text-[10px] text-amber-600 font-semibold mt-0.5">Multi-resource stable</div>
      </div>

      <div class="bg-emerald-50 border border-emerald-200 p-4 rounded-2xl shadow-xs text-emerald-950">
        <span class="text-[10px] font-black uppercase tracking-wider text-emerald-700 block">ESI-4 Less Urgent</span>
        <div class="text-2xl font-black text-emerald-700 mt-1">
          {{ getCountByEsi(4) }}
        </div>
        <div class="text-[10px] text-emerald-600 font-semibold mt-0.5">Single resource</div>
      </div>

      <div class="bg-blue-50 border border-blue-200 p-4 rounded-2xl shadow-xs text-blue-950">
        <span class="text-[10px] font-black uppercase tracking-wider text-blue-700 block">ESI-5 Non-Urgent</span>
        <div class="text-2xl font-black text-blue-700 mt-1">
          {{ getCountByEsi(5) }}
        </div>
        <div class="text-[10px] text-blue-600 font-semibold mt-0.5">No resources needed</div>
      </div>
    </div>

    <!-- Active Queue Toolbar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
      <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
        <!-- ESI Filter -->
        <select
          v-model="esiFilter"
          @change="fetchCases"
          class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-blue-500 focus:outline-none cursor-pointer"
        >
          <option value="">All Triage Levels (ESI 1-5)</option>
          <option value="1">ESI 1 - Resuscitation Only</option>
          <option value="2">ESI 2 - Emergent Only</option>
          <option value="3">ESI 3 - Urgent Only</option>
          <option value="4">ESI 4 - Less Urgent Only</option>
          <option value="5">ESI 5 - Non-Urgent Only</option>
        </select>

        <!-- Status Filter -->
        <select
          v-model="statusFilter"
          @change="fetchCases"
          class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-blue-500 focus:outline-none cursor-pointer"
        >
          <option value="">Active ER Cases</option>
          <option value="registered">Registered / Pending Triage</option>
          <option value="triaged">Triaged (Waiting Bed)</option>
          <option value="bed_assigned">Bed Assigned</option>
          <option value="in_treatment">In Treatment</option>
        </select>
      </div>

      <div class="flex items-center gap-2 w-full md:w-auto justify-end">
        <button
          @click="fetchCases"
          class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition cursor-pointer"
        >
          Refresh Queue
        </button>

        <button
          @click="openCaseIntakeModal"
          class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span>Emergency Intake</span>
        </button>
      </div>
    </div>

    <!-- Prioritized Emergency Queue Board -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-4 border-b border-slate-200 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <h3 class="font-bold text-sm text-slate-900">Emergency Case Prioritization Queue</h3>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-mono bg-slate-100 text-slate-600 font-bold">
            Sorted by ESI Severity & Arrival Time
          </span>
        </div>
        <div class="text-[11px] text-slate-400 font-mono">
          {{ emergencyCases.length }} Patients In Emergency Department
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
              <th class="py-3 px-4">Priority / ESI</th>
              <th class="py-3 px-4">Patient / Case #</th>
              <th class="py-3 px-4">Chief Complaint & Vitals</th>
              <th class="py-3 px-4">Arrival Mode</th>
              <th class="py-3 px-4">Wait Time</th>
              <th class="py-3 px-4">Assigned Bed</th>
              <th class="py-3 px-4 text-right">Clinical Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="loadingQueue">
              <td colspan="7" class="py-12 text-center text-slate-400">Loading emergency queue...</td>
            </tr>
            <tr v-else-if="emergencyCases.length === 0">
              <td colspan="7" class="py-12 text-center text-slate-400 italic">No emergency patients currently queued.</td>
            </tr>
            <tr
              v-for="c in emergencyCases"
              :key="c.id"
              class="hover:bg-slate-50/70 transition"
              :class="{ 'bg-rose-50/30': c.current_esi_level === 1, 'bg-orange-50/20': c.current_esi_level === 2 }"
            >
              <!-- Priority / ESI Badge -->
              <td class="py-3 px-4">
                <div class="flex items-center gap-2">
                  <span
                    class="px-2.5 py-1 rounded-xl text-[11px] font-black uppercase text-white shadow-xs flex items-center gap-1.5"
                    :class="getEsiBadgeClass(c.current_esi_level)"
                  >
                    <span v-if="c.current_esi_level === 1" class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>
                    <span>ESI {{ c.current_esi_level }}</span>
                  </span>
                </div>
                <div class="text-[10px] text-slate-400 font-mono mt-1">Score: {{ c.priority_score }}</div>
              </td>

              <!-- Patient & Case ID -->
              <td class="py-3 px-4">
                <div class="font-bold text-slate-900 text-sm">{{ c.display_patient_name }}</div>
                <div class="text-[11px] font-mono text-slate-400 mt-0.5">{{ c.case_number }}</div>
                <div class="text-[10px] text-slate-500 capitalize" v-if="c.patient_gender || c.patient_estimated_age">
                  {{ c.patient_gender || '' }} &bull; ~{{ c.patient_estimated_age ? `${c.patient_estimated_age} yrs` : 'Age Unk' }}
                </div>
              </td>

              <!-- Complaint & Vitals Summary -->
              <td class="py-3 px-4 max-w-xs">
                <div class="font-semibold text-slate-800 line-clamp-1" :title="c.chief_complaint">
                  {{ c.chief_complaint }}
                </div>
                <div v-if="c.latest_triage_record" class="mt-1 flex flex-wrap gap-1 text-[10px] font-mono">
                  <span v-if="c.latest_triage_record.vital_signs?.heart_rate" class="px-1.5 py-0.5 rounded bg-slate-100 font-bold" :class="c.latest_triage_record.vital_signs.heart_rate > 120 ? 'text-rose-600' : 'text-slate-700'">
                    HR: {{ c.latest_triage_record.vital_signs.heart_rate }}
                  </span>
                  <span v-if="c.latest_triage_record.vital_signs?.spo2" class="px-1.5 py-0.5 rounded bg-slate-100 font-bold" :class="c.latest_triage_record.vital_signs.spo2 < 92 ? 'text-rose-600' : 'text-slate-700'">
                    SpO2: {{ c.latest_triage_record.vital_signs.spo2 }}%
                  </span>
                  <span v-if="c.latest_triage_record.vital_signs?.bp_systolic" class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-bold">
                    BP: {{ c.latest_triage_record.vital_signs.bp_systolic }}/{{ c.latest_triage_record.vital_signs.bp_diastolic }}
                  </span>
                </div>
              </td>

              <!-- Arrival Mode -->
              <td class="py-3 px-4">
                <span class="capitalize px-2 py-0.5 rounded-md text-[10px] font-bold border border-slate-200 bg-slate-50 text-slate-700">
                  {{ c.arrival_mode }}
                </span>
                <div class="text-[10px] font-mono text-slate-400 mt-0.5">
                  {{ formatTime(c.arrival_datetime) }}
                </div>
              </td>

              <!-- Wait Time -->
              <td class="py-3 px-4 font-mono font-bold" :class="c.wait_time_minutes > 30 ? 'text-rose-600' : 'text-slate-700'">
                {{ c.wait_time_minutes }}m elapsed
              </td>

              <!-- Bed Allocation -->
              <td class="py-3 px-4">
                <div v-if="c.assigned_bed_id" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-[11px] font-bold">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                  <span>{{ c.bed_number }}</span>
                </div>
                <div v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-amber-50 text-amber-800 border border-amber-200 text-[10px] font-semibold">
                  <span>Pending Bay</span>
                </div>
              </td>

              <!-- Action Buttons -->
              <td class="py-3 px-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <!-- Triage Assessment Button -->
                  <button
                    @click="openTriageModal(c)"
                    class="px-2.5 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-[11px] font-bold transition cursor-pointer"
                    title="Enter clinical triage vitals & ESI"
                  >
                    Triage
                  </button>

                  <!-- Bed Allocation / Override Button -->
                  <button
                    @click="openBedModal(c)"
                    class="px-2.5 py-1 rounded-xl text-[11px] font-bold transition cursor-pointer"
                    :class="c.assigned_bed_id ? 'bg-slate-100 hover:bg-slate-200 text-slate-700' : 'bg-blue-600 hover:bg-blue-700 text-white'"
                  >
                    {{ c.assigned_bed_id ? 'Reassign Bed' : 'Allocate Bed' }}
                  </button>

                  <!-- Disposition Button -->
                  <button
                    @click="openDispositionModal(c)"
                    class="px-2 py-1 text-slate-400 hover:text-slate-700 text-xs font-semibold cursor-pointer"
                    title="Discharge / Admit to IPD / Transfer"
                  >
                    Disposition &rarr;
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal: Case Intake Registration -->
    <div v-if="isCaseIntakeOpen" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-start justify-between pb-4 border-b border-slate-100">
          <div>
            <h3 class="font-bold text-base text-slate-900">Emergency Patient Intake</h3>
            <p class="text-xs text-slate-500 mt-0.5">Rapid registration for walk-in, ambulance, or trauma arrivals.</p>
          </div>
          <button @click="isCaseIntakeOpen = false" class="p-1 text-slate-400 hover:text-slate-600 cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="submitCaseIntake" class="mt-4 space-y-3.5">
          <div>
            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Patient Identification</label>
            <div class="grid grid-cols-2 gap-2">
              <button
                type="button"
                @click="caseForm.is_known_patient = false"
                :class="!caseForm.is_known_patient ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700'"
                class="py-2 rounded-xl text-xs font-bold transition cursor-pointer"
              >
                Unidentified (John/Jane Doe)
              </button>
              <button
                type="button"
                @click="caseForm.is_known_patient = true"
                :class="caseForm.is_known_patient ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700'"
                class="py-2 rounded-xl text-xs font-bold transition cursor-pointer"
              >
                Registered Patient Lookup
              </button>
            </div>
          </div>

          <div v-if="!caseForm.is_known_patient" class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-semibold text-slate-600 mb-1">Temporary Trauma Name *</label>
              <input
                v-model="caseForm.patient_temp_name"
                type="text"
                placeholder="e.g. Trauma John Doe 04"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                required
              />
            </div>
            <div>
              <label class="block text-[11px] font-semibold text-slate-600 mb-1">Estimated Age</label>
              <input
                v-model.number="caseForm.patient_estimated_age"
                type="number"
                placeholder="e.g. 40"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
              />
            </div>
          </div>

          <div v-else>
            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Select Patient *</label>
            <select
              v-model="caseForm.patient_id"
              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
              required
            >
              <option value="" disabled>Choose patient...</option>
              <option v-for="p in patientList" :key="p.id" :value="p.id">
                {{ p.full_name }} (MRN: {{ p.medical_record_number }})
              </option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-semibold text-slate-600 mb-1">Arrival Mode *</label>
              <select
                v-model="caseForm.arrival_mode"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                required
              >
                <option value="ambulance">Ambulance EMS</option>
                <option value="walk_in">Walk-in</option>
                <option value="helicopter">Helicopter Medevac</option>
                <option value="police">Police Transport</option>
              </select>
            </div>
            <div>
              <label class="block text-[11px] font-semibold text-slate-600 mb-1">Initial ESI Estimate *</label>
              <select
                v-model.number="caseForm.initial_triage_esi"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-blue-500 focus:outline-none"
                required
              >
                <option :value="1">Level 1 - Resuscitation (Immediate)</option>
                <option :value="2">Level 2 - Emergent</option>
                <option :value="3">Level 3 - Urgent</option>
                <option :value="4">Level 4 - Less Urgent</option>
                <option :value="5">Level 5 - Non-Urgent</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Chief Complaint *</label>
            <textarea
              v-model="caseForm.chief_complaint"
              rows="2"
              placeholder="Presenting injury, symptoms, or acute distress..."
              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
              required
            ></textarea>
          </div>

          <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
            <button
              type="button"
              @click="isCaseIntakeOpen = false"
              class="px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 cursor-pointer"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="submittingCase"
              class="px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white cursor-pointer disabled:opacity-50"
            >
              {{ submittingCase ? 'Registering...' : 'Register ER Case' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Structured Clinical Triage Assessment -->
    <div v-if="isTriageModalOpen && activeCaseForTriage" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-3xl max-w-xl w-full p-6 shadow-2xl border border-slate-200 max-h-[92vh] overflow-y-auto">
        <div class="flex items-start justify-between pb-4 border-b border-slate-100">
          <div>
            <h3 class="font-bold text-base text-slate-900">Clinical Triage & ESI Scoring</h3>
            <p class="text-xs text-slate-500 mt-0.5">
              {{ activeCaseForTriage.display_patient_name }} &bull; Case #{{ activeCaseForTriage.case_number }}
            </p>
          </div>
          <button @click="isTriageModalOpen = false" class="p-1 text-slate-400 hover:text-slate-600 cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="submitTriage" class="mt-4 space-y-4">
          <!-- Quantitative Vitals -->
          <div>
            <label class="block text-xs font-bold text-slate-800 mb-2">Patient Vital Signs</label>
            <div class="grid grid-cols-3 gap-2.5 text-xs">
              <div>
                <label class="block text-[10px] text-slate-500 font-semibold mb-0.5">Heart Rate (bpm)</label>
                <input
                  v-model.number="triageForm.vital_signs.heart_rate"
                  type="number"
                  placeholder="75"
                  class="w-full px-3 py-1.5 bg-slate-50 border rounded-xl"
                  :class="triageForm.vital_signs.heart_rate > 120 || triageForm.vital_signs.heart_rate < 45 ? 'border-rose-400 bg-rose-50 text-rose-800 font-bold' : 'border-slate-200'"
                />
              </div>
              <div>
                <label class="block text-[10px] text-slate-500 font-semibold mb-0.5">BP Systolic</label>
                <input
                  v-model.number="triageForm.vital_signs.bp_systolic"
                  type="number"
                  placeholder="120"
                  class="w-full px-3 py-1.5 bg-slate-50 border rounded-xl"
                  :class="triageForm.vital_signs.bp_systolic < 90 ? 'border-rose-400 bg-rose-50 text-rose-800 font-bold' : 'border-slate-200'"
                />
              </div>
              <div>
                <label class="block text-[10px] text-slate-500 font-semibold mb-0.5">BP Diastolic</label>
                <input
                  v-model.number="triageForm.vital_signs.bp_diastolic"
                  type="number"
                  placeholder="80"
                  class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl"
                />
              </div>
              <div>
                <label class="block text-[10px] text-slate-500 font-semibold mb-0.5">Resp Rate (/min)</label>
                <input
                  v-model.number="triageForm.vital_signs.respiratory_rate"
                  type="number"
                  placeholder="16"
                  class="w-full px-3 py-1.5 bg-slate-50 border rounded-xl"
                  :class="triageForm.vital_signs.respiratory_rate > 30 || triageForm.vital_signs.respiratory_rate < 10 ? 'border-rose-400 bg-rose-50 text-rose-800 font-bold' : 'border-slate-200'"
                />
              </div>
              <div>
                <label class="block text-[10px] text-slate-500 font-semibold mb-0.5">SpO2 (%)</label>
                <input
                  v-model.number="triageForm.vital_signs.spo2"
                  type="number"
                  placeholder="98"
                  class="w-full px-3 py-1.5 bg-slate-50 border rounded-xl"
                  :class="triageForm.vital_signs.spo2 < 90 ? 'border-rose-400 bg-rose-50 text-rose-800 font-bold' : 'border-slate-200'"
                />
              </div>
              <div>
                <label class="block text-[10px] text-slate-500 font-semibold mb-0.5">GCS (3-15)</label>
                <input
                  v-model.number="triageForm.vital_signs.gcs"
                  type="number"
                  min="3"
                  max="15"
                  placeholder="15"
                  class="w-full px-3 py-1.5 bg-slate-50 border rounded-xl"
                  :class="triageForm.vital_signs.gcs < 13 ? 'border-rose-400 bg-rose-50 text-rose-800 font-bold' : 'border-slate-200'"
                />
              </div>
            </div>
          </div>

          <!-- Red Flags Checklist -->
          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1.5">Critical Red Flags</label>
            <div class="grid grid-cols-2 gap-2 text-xs">
              <label class="flex items-center gap-2 p-2 bg-slate-50 rounded-xl border border-slate-200 cursor-pointer">
                <input type="checkbox" value="cardiac_arrest" v-model="triageForm.red_flags" class="rounded text-rose-600" />
                <span class="font-medium text-slate-700">Cardiac Arrest / Unresponsive</span>
              </label>
              <label class="flex items-center gap-2 p-2 bg-slate-50 rounded-xl border border-slate-200 cursor-pointer">
                <input type="checkbox" value="severe_respiratory_distress" v-model="triageForm.red_flags" class="rounded text-rose-600" />
                <span class="font-medium text-slate-700">Severe Respiratory Distress</span>
              </label>
              <label class="flex items-center gap-2 p-2 bg-slate-50 rounded-xl border border-slate-200 cursor-pointer">
                <input type="checkbox" value="stemi_suspected" v-model="triageForm.red_flags" class="rounded text-rose-600" />
                <span class="font-medium text-slate-700">Suspected STEMI / Acute Coronary</span>
              </label>
              <label class="flex items-center gap-2 p-2 bg-slate-50 rounded-xl border border-slate-200 cursor-pointer">
                <input type="checkbox" value="active_hemorrhage" v-model="triageForm.red_flags" class="rounded text-rose-600" />
                <span class="font-medium text-slate-700">Active Massive Hemorrhage</span>
              </label>
            </div>
          </div>

          <!-- ESI Severity Choice & Category -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-semibold text-slate-600 mb-1">ESI Severity Level *</label>
              <select
                v-model.number="triageForm.esi_level"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-blue-500 focus:outline-none"
                required
              >
                <option :value="1">Level 1 - Immediate Resuscitation</option>
                <option :value="2">Level 2 - Emergent</option>
                <option :value="3">Level 3 - Urgent</option>
                <option :value="4">Level 4 - Less Urgent</option>
                <option :value="5">Level 5 - Non-Urgent</option>
              </select>
            </div>
            <div>
              <label class="block text-[11px] font-semibold text-slate-600 mb-1">Triage Category</label>
              <select
                v-model="triageForm.triage_category"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
              >
                <option value="cardiac">Cardiac</option>
                <option value="trauma">Trauma</option>
                <option value="respiratory">Respiratory</option>
                <option value="neurological">Neurological</option>
                <option value="pediatric">Pediatric</option>
                <option value="general">General Medicine</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Clinical Assessment Notes *</label>
            <textarea
              v-model="triageForm.assessment_notes"
              rows="2"
              placeholder="Clinical reasoning, mental status, immediate bedside interventions..."
              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
              required
            ></textarea>
          </div>

          <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
            <button
              type="button"
              @click="isTriageModalOpen = false"
              class="px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 cursor-pointer"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="submittingTriage"
              class="px-4 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white cursor-pointer disabled:opacity-50"
            >
              {{ submittingTriage ? 'Classifying...' : 'Save Triage & Re-sort Queue' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Bed Allocation Modal -->
    <EmergencyBedAllocationModal
      :is-open="isBedModalOpen"
      :emergency-case="activeCaseForBed"
      :branch-id="branchId"
      @close="isBedModalOpen = false"
      @bed-allocated="handleBedAllocated"
      @bed-released="handleBedReleased"
    />

    <!-- Modal: Clinical Disposition -->
    <div v-if="isDispositionModalOpen && activeCaseForDisposition" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <h3 class="font-bold text-base text-slate-900">Emergency Case Disposition</h3>
        <p class="text-xs text-slate-500 mt-1">Finalize emergency encounter outcome for {{ activeCaseForDisposition.display_patient_name }}.</p>

        <form @submit.prevent="submitDisposition" class="mt-4 space-y-3">
          <div>
            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Disposition Decision *</label>
            <select
              v-model="dispositionForm.disposition"
              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none"
              required
            >
              <option value="admit_ipd">Admit to Inpatient Hospital (IPD)</option>
              <option value="discharge_home">Discharge Home</option>
              <option value="transfer_tertiary">Transfer to Tertiary Trauma Center</option>
              <option value="ama">Left Against Medical Advice (AMA)</option>
              <option value="morgue">Deceased</option>
            </select>
          </div>

          <div>
            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Disposition Notes</label>
            <textarea
              v-model="dispositionForm.disposition_notes"
              rows="2"
              placeholder="Clinical summary and discharge instructions..."
              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none"
            ></textarea>
          </div>

          <div class="pt-3 flex justify-end gap-2">
            <button
              type="button"
              @click="isDispositionModalOpen = false"
              class="px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 cursor-pointer"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-900 text-white cursor-pointer"
            >
              Confirm Disposition
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { showAlert } from '../../Services/modalDialog';
import EmergencyBedAllocationModal from './EmergencyBedAllocationModal.vue';

const props = defineProps({
  branchId: {
    type: String,
    default: null,
  },
});

const emit = defineEmits(['critical-count-updated']);

const emergencyCases = ref([]);
const loadingQueue = ref(false);
const esiFilter = ref('');
const statusFilter = ref('');
const patientList = ref([]);

// Modals
const isCaseIntakeOpen = ref(false);
const submittingCase = ref(false);
const isTriageModalOpen = ref(false);
const activeCaseForTriage = ref(null);
const submittingTriage = ref(false);

const isBedModalOpen = ref(false);
const activeCaseForBed = ref(null);

const isDispositionModalOpen = ref(false);
const activeCaseForDisposition = ref(null);

const caseForm = ref({
  is_known_patient: false,
  patient_id: '',
  patient_temp_name: 'Trauma John Doe',
  patient_estimated_age: 35,
  arrival_mode: 'ambulance',
  initial_triage_esi: 2,
  chief_complaint: '',
});

const triageForm = ref({
  esi_level: 2,
  triage_category: 'general',
  vital_signs: {
    heart_rate: 80,
    bp_systolic: 120,
    bp_diastolic: 80,
    respiratory_rate: 16,
    spo2: 98,
    gcs: 15,
  },
  red_flags: [],
  assessment_notes: '',
});

const dispositionForm = ref({
  disposition: 'admit_ipd',
  disposition_notes: '',
});

const getCountByEsi = (level) => {
  return emergencyCases.value.filter((c) => c.current_esi_level === level).length;
};

const getEsiBadgeClass = (level) => {
  if (level === 1) return 'bg-rose-600';
  if (level === 2) return 'bg-orange-500';
  if (level === 3) return 'bg-amber-500';
  if (level === 4) return 'bg-emerald-600';
  return 'bg-blue-600';
};

const formatTime = (dt) => {
  if (!dt) return '—';
  return new Date(dt).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
};

const fetchCases = async () => {
  loadingQueue.value = true;
  try {
    const params = new URLSearchParams();
    if (props.branchId) params.append('branch_id', props.branchId);
    if (esiFilter.value) params.append('esi_level', esiFilter.value);
    if (statusFilter.value) params.append('status', statusFilter.value);

    const res = await fetch(`/api/v1/emergency/cases?${params.toString()}`, {
      headers: {
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
    });
    const json = await res.json();
    if (json.success) {
      emergencyCases.value = json.data?.data || json.data || [];
      const criticalCount = emergencyCases.value.filter((c) => c.current_esi_level === 1 || c.current_esi_level === 2).length;
      emit('critical-count-updated', criticalCount);
    }
  } catch (err) {
    console.error('Failed to load emergency queue', err);
  } finally {
    loadingQueue.value = false;
  }
};

const fetchPatients = async () => {
  try {
    const res = await fetch('/api/v1/patients?per_page=50', {
      headers: {
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
    });
    const json = await res.json();
    if (json.success) {
      patientList.value = json.data?.data || json.data || [];
    }
  } catch (err) {
    console.error('Failed to load patients', err);
  }
};

const openCaseIntakeModal = () => {
  caseForm.value = {
    is_known_patient: false,
    patient_id: '',
    patient_temp_name: 'Trauma John Doe',
    patient_estimated_age: 35,
    arrival_mode: 'ambulance',
    initial_triage_esi: 2,
    chief_complaint: '',
  };
  isCaseIntakeOpen.value = true;
};

const submitCaseIntake = async () => {
  submittingCase.value = true;
  try {
    const payload = {
      organization_id: '93da9d9c-cece-44f8-ac4e-5f788c1af982',
      branch_id: props.branchId || '84d7387e-7b2e-4533-b3e8-139e52aecb8b',
      patient_id: caseForm.value.is_known_patient ? caseForm.value.patient_id : null,
      patient_temp_name: !caseForm.value.is_known_patient ? caseForm.value.patient_temp_name : null,
      patient_estimated_age: caseForm.value.patient_estimated_age,
      arrival_mode: caseForm.value.arrival_mode,
      initial_triage_esi: caseForm.value.initial_triage_esi,
      chief_complaint: caseForm.value.chief_complaint,
    };

    const res = await fetch('/api/v1/emergency/cases', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
      body: JSON.stringify(payload),
    });
    const json = await res.json();
    if (json.success) {
      isCaseIntakeOpen.value = false;
      await fetchCases();
    } else {
      await showAlert(json.message || 'Error registering case', { status: 'error' });
    }
  } catch (err) {
    console.error('Failed to submit intake', err);
  } finally {
    submittingCase.value = false;
  }
};

const openTriageModal = (c) => {
  activeCaseForTriage.value = c;
  triageForm.value = {
    esi_level: c.current_esi_level,
    triage_category: 'general',
    vital_signs: {
      heart_rate: 80,
      bp_systolic: 120,
      bp_diastolic: 80,
      respiratory_rate: 16,
      spo2: 98,
      gcs: 15,
    },
    red_flags: [],
    assessment_notes: 'Initial clinical assessment completed.',
  };
  isTriageModalOpen.value = true;
};

const submitTriage = async () => {
  if (!activeCaseForTriage.value) return;
  submittingTriage.value = true;
  try {
    const res = await fetch(`/api/v1/emergency/cases/${activeCaseForTriage.value.id}/triage`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
      body: JSON.stringify(triageForm.value),
    });
    const json = await res.json();
    if (json.success) {
      isTriageModalOpen.value = false;
      await fetchCases();
    } else {
      await showAlert(json.message || 'Error updating triage', { status: 'error' });
    }
  } catch (err) {
    console.error('Failed to submit triage', err);
  } finally {
    submittingTriage.value = false;
  }
};

const openBedModal = (c) => {
  activeCaseForBed.value = c;
  isBedModalOpen.value = true;
};

const handleBedAllocated = () => {
  fetchCases();
};

const handleBedReleased = () => {
  fetchCases();
};

const openDispositionModal = (c) => {
  activeCaseForDisposition.value = c;
  dispositionForm.value = {
    disposition: 'admit_ipd',
    disposition_notes: '',
  };
  isDispositionModalOpen.value = true;
};

const submitDisposition = async () => {
  if (!activeCaseForDisposition.value) return;
  try {
    const res = await fetch(`/api/v1/emergency/cases/${activeCaseForDisposition.value.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(props.branchId ? { 'X-Branch-ID': props.branchId } : {}),
      },
      body: JSON.stringify({
        status: dispositionForm.value.disposition === 'admit_ipd' ? 'admitted_ipd' : 'discharged',
        disposition: dispositionForm.value.disposition,
        disposition_notes: dispositionForm.value.disposition_notes,
      }),
    });
    const json = await res.json();
    if (json.success) {
      isDispositionModalOpen.value = false;
      await fetchCases();
    }
  } catch (err) {
    console.error('Failed to update disposition', err);
  }
};

onMounted(() => {
  fetchCases();
  fetchPatients();
});
</script>
