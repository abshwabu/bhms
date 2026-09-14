<template>
  <div class="space-y-6">
    <!-- Top Bar & Patient Header -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200/80 space-y-4">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <button
            @click="$emit('back')"
            class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 transition cursor-pointer"
            title="Back to previous view"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </button>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-2xl font-black text-slate-900 tracking-tight">{{ patientData.name || 'Patient Chart' }}</h1>
              <span class="font-mono text-xs bg-slate-100 px-2 py-0.5 rounded-md font-bold text-slate-600">{{ patientData.mrn }}</span>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 uppercase">Longitudinal EHR</span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">
              {{ patientData.gender || 'Unknown' }} • {{ patientData.date_of_birth || 'DOB N/A' }} • Blood: {{ patientData.blood_group || 'Unknown' }}
            </p>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap items-center gap-2">
          <button
            @click="isNewNoteModalOpen = true"
            class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm shadow-blue-500/20 transition cursor-pointer flex items-center gap-1.5"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>+ Clinical Note (SOAP)</span>
          </button>

          <button
            @click="$emit('openPrescriptionWriter', patientData)"
            class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm shadow-indigo-500/20 transition cursor-pointer flex items-center gap-1.5"
          >
            <span>💊 E-Prescribe</span>
          </button>

          <button
            @click="$emit('openOrderModal', { type: 'lab', patient: patientData })"
            class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-3.5 py-2.5 rounded-xl transition cursor-pointer"
          >
            🧪 Order Lab
          </button>

          <button
            @click="$emit('openOrderModal', { type: 'radiology', patient: patientData })"
            class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-3.5 py-2.5 rounded-xl transition cursor-pointer"
          >
            🩻 Order Imaging
          </button>
        </div>
      </div>

      <!-- Allergy Warning Banner -->
      <div v-if="patientData.allergies && patientData.allergies.length > 0" class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl flex items-center justify-between">
        <div class="flex items-center gap-2.5 text-xs text-rose-800">
          <span class="text-base">🚨</span>
          <div>
            <span class="font-bold">Documented Patient Allergies:</span>
            <span v-for="allergy in patientData.allergies" :key="allergy.allergen" class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-rose-100 text-rose-800 font-medium">
              {{ allergy.allergen }} ({{ allergy.reaction || 'Hypersensitivity' }})
            </span>
          </div>
        </div>
        <span class="text-[10px] font-bold uppercase tracking-wider text-rose-600 bg-rose-200/60 px-2 py-0.5 rounded">CDS Alert Active</span>
      </div>

      <!-- Quick Summary Badges -->
      <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 pt-2 text-center text-xs">
        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100">
          <div class="font-black text-base text-slate-800">{{ summary.total_ehr_notes ?? 0 }}</div>
          <div class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Clinical Notes</div>
        </div>
        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100">
          <div class="font-black text-base text-blue-600">{{ summary.active_diagnoses_count ?? 0 }}</div>
          <div class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Active Diagnoses</div>
        </div>
        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100">
          <div class="font-black text-base text-indigo-600">{{ summary.prescriptions_count ?? 0 }}</div>
          <div class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Prescriptions</div>
        </div>
        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100">
          <div class="font-black text-base text-emerald-600">{{ summary.lab_orders_count ?? 0 }}</div>
          <div class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Lab Orders</div>
        </div>
        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100">
          <div class="font-black text-base text-purple-600">{{ summary.radiology_orders_count ?? 0 }}</div>
          <div class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Radiology Studies</div>
        </div>
      </div>
    </div>

    <!-- Filter Filter Pills -->
    <div class="flex items-center justify-between gap-4">
      <div class="flex flex-wrap items-center gap-2">
        <button
          v-for="tab in filterTabs"
          :key="tab.value"
          @click="activeFilter = tab.value"
          :class="activeFilter === tab.value ? 'bg-slate-900 text-white font-bold' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
          class="text-xs px-3.5 py-1.5 rounded-xl transition cursor-pointer"
        >
          {{ tab.label }}
        </button>
      </div>
      <div class="text-xs text-slate-400 font-medium">
        Showing {{ filteredTimeline.length }} longitudinal entries
      </div>
    </div>

    <!-- Longitudinal Chronological Timeline -->
    <div v-if="isLoading" class="p-16 text-center text-slate-400">
      <div class="w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
      Loading clinical timeline...
    </div>

    <div v-else-if="filteredTimeline.length === 0" class="bg-white p-12 rounded-2xl border border-slate-200/80 text-center">
      <p class="text-sm font-semibold text-slate-700">No clinical entries found for this filter.</p>
      <p class="text-xs text-slate-400 mt-1">Add a consultation note, diagnosis, or prescription to start this patient's EHR record.</p>
    </div>

    <div v-else class="relative border-l-2 border-slate-200 ml-4 pl-6 space-y-6 pb-8">
      <div
        v-for="event in filteredTimeline"
        :key="event.type + '-' + event.id"
        class="relative group"
      >
        <!-- Timeline Marker Node -->
        <div
          class="absolute -left-[35px] top-1.5 w-6 h-6 rounded-full border-2 bg-white flex items-center justify-center text-[10px]"
          :class="getTimelineNodeClass(event.type)"
        >
          <span>{{ getEventEmoji(event.type) }}</span>
        </div>

        <!-- Event Card Container -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-3 hover:border-slate-300 transition">
          <!-- Card Header -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2">
              <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider" :class="getEventTypeBadgeClass(event.type)">
                {{ formatEventType(event.type) }}
              </span>
              <h3 class="font-bold text-slate-900 text-sm">
                {{ getEventTitle(event) }}
              </h3>

              <!-- Version / Amendment Badge for EHR Notes -->
              <span
                v-if="event.type === 'ehr_record'"
                class="font-mono text-[10px] px-2 py-0.5 rounded font-bold"
                :class="event.is_amended ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700'"
              >
                v{{ event.version }} {{ event.is_amended ? '(Amended)' : (event.status === 'draft' ? 'Draft' : 'Final') }}
              </span>
            </div>

            <div class="text-xs text-slate-400 font-mono">
              {{ formatDateTime(event.date) }}
            </div>
          </div>

          <!-- EVENT BODY: 1. EHR Consultation Note -->
          <div v-if="event.type === 'ehr_record'" class="space-y-3 text-xs">
            <!-- Vitals Bar -->
            <div v-if="event.vitals && Object.keys(event.vitals).length > 0" class="flex flex-wrap gap-2 p-2.5 bg-slate-50 rounded-xl border border-slate-100 font-mono">
              <span v-if="event.vitals.bp_systolic" class="text-slate-700"><strong>BP:</strong> {{ event.vitals.bp_systolic }}/{{ event.vitals.bp_diastolic }} mmHg</span>
              <span v-if="event.vitals.heart_rate" class="text-slate-700"><strong>HR:</strong> {{ event.vitals.heart_rate }} bpm</span>
              <span v-if="event.vitals.temperature_c" class="text-slate-700"><strong>Temp:</strong> {{ event.vitals.temperature_c }}°C</span>
              <span v-if="event.vitals.spo2" class="text-slate-700"><strong>SpO2:</strong> {{ event.vitals.spo2 }}%</span>
              <span v-if="event.vitals.bmi" class="text-slate-700"><strong>BMI:</strong> {{ event.vitals.bmi }}</span>
            </div>

            <!-- Structured SOAP Notes -->
            <div v-if="event.clinical_notes" class="grid grid-cols-1 md:grid-cols-2 gap-3 text-slate-700">
              <div v-if="event.clinical_notes.chief_complaint" class="col-span-full bg-blue-50/50 p-2.5 rounded-lg border border-blue-100">
                <span class="font-bold text-blue-900 uppercase tracking-wider text-[10px] block">Chief Complaint</span>
                <p class="mt-0.5 text-xs text-slate-800">{{ event.clinical_notes.chief_complaint }}</p>
              </div>

              <div v-if="event.clinical_notes.subjective" class="p-2.5 bg-slate-50/80 rounded-lg">
                <span class="font-bold text-slate-900 uppercase tracking-wider text-[10px] block">Subjective (S)</span>
                <p class="mt-0.5 text-xs whitespace-pre-wrap">{{ event.clinical_notes.subjective }}</p>
              </div>

              <div v-if="event.clinical_notes.objective" class="p-2.5 bg-slate-50/80 rounded-lg">
                <span class="font-bold text-slate-900 uppercase tracking-wider text-[10px] block">Objective (O)</span>
                <p class="mt-0.5 text-xs whitespace-pre-wrap">{{ event.clinical_notes.objective }}</p>
              </div>

              <div v-if="event.clinical_notes.assessment" class="p-2.5 bg-slate-50/80 rounded-lg">
                <span class="font-bold text-slate-900 uppercase tracking-wider text-[10px] block">Assessment (A)</span>
                <p class="mt-0.5 text-xs whitespace-pre-wrap font-medium">{{ event.clinical_notes.assessment }}</p>
              </div>

              <div v-if="event.clinical_notes.plan" class="p-2.5 bg-slate-50/80 rounded-lg">
                <span class="font-bold text-slate-900 uppercase tracking-wider text-[10px] block">Plan (P)</span>
                <p class="mt-0.5 text-xs whitespace-pre-wrap">{{ event.clinical_notes.plan }}</p>
              </div>
            </div>

            <!-- Amendment Notice / Reason -->
            <div v-if="event.amendment_reason" class="p-2.5 bg-amber-50 border border-amber-200 rounded-lg text-amber-900 text-xs">
              <span class="font-bold">Amendment Justification:</span> {{ event.amendment_reason }}
            </div>

            <!-- Author / Footer Controls -->
            <div class="flex items-center justify-between text-slate-400 pt-2 border-t border-slate-100 text-[11px]">
              <span>Author: <strong class="text-slate-700">{{ event.author?.name || 'Clinician' }}</strong></span>

              <div class="flex items-center gap-2">
                <!-- If Finalized, allow amending with audit reason -->
                <button
                  v-if="event.status === 'finalized'"
                  @click="openAmendModal(event)"
                  class="text-blue-600 hover:text-blue-800 font-bold hover:underline cursor-pointer"
                >
                  Amend Record (v{{ event.version + 1 }}) &rarr;
                </button>

                <!-- If Draft, allow finalizing -->
                <button
                  v-if="event.status === 'draft'"
                  @click="finalizeEhrRecord(event)"
                  class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-2.5 py-1 rounded-md cursor-pointer"
                >
                  Finalize & Lock
                </button>
              </div>
            </div>
          </div>

          <!-- EVENT BODY: 2. Diagnosis (ICD-10) -->
          <div v-else-if="event.type === 'diagnosis'" class="text-xs space-y-2">
            <div class="flex items-center gap-3">
              <span class="px-2.5 py-1 rounded-lg bg-indigo-50 border border-indigo-200 font-mono font-black text-indigo-700 text-sm">
                {{ event.icd10_code }}
              </span>
              <div>
                <div class="font-bold text-slate-900 text-sm">{{ event.icd10_title }}</div>
                <div class="text-slate-500 text-[11px]">Type: {{ event.diagnosis_type }} • Severity: {{ event.severity }} • Status: {{ event.clinical_status }}</div>
              </div>
            </div>
            <p v-if="event.notes" class="text-slate-600 italic bg-slate-50 p-2 rounded-lg">{{ event.notes }}</p>
          </div>

          <!-- EVENT BODY: 3. Prescription -->
          <div v-else-if="event.type === 'prescription'" class="text-xs space-y-3">
            <div class="flex items-center justify-between font-mono text-[11px] text-slate-500">
              <span>Rx Number: <strong>{{ event.prescription_number }}</strong></span>
              <span class="px-2 py-0.5 rounded uppercase font-bold" :class="event.status === 'finalized' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'">
                {{ event.status }}
              </span>
            </div>

            <!-- Items Table -->
            <div class="overflow-x-auto rounded-xl border border-slate-100">
              <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-400 font-semibold uppercase text-[10px]">
                  <tr>
                    <th class="p-2">Medication</th>
                    <th class="p-2">Dosage</th>
                    <th class="p-2">Frequency</th>
                    <th class="p-2">Duration</th>
                    <th class="p-2">Instructions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                  <tr v-for="item in event.items" :key="item.id">
                    <td class="p-2 font-bold text-slate-900">{{ item.medication_name }}</td>
                    <td class="p-2">{{ item.dosage }}</td>
                    <td class="p-2">{{ item.frequency }}</td>
                    <td class="p-2">{{ item.duration_days }} days (Qty: {{ item.quantity }})</td>
                    <td class="p-2 text-slate-500 italic">{{ item.instructions || 'As directed' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-if="event.override_reason" class="p-2 bg-amber-50 border border-amber-200 rounded-lg text-amber-900 text-[11px]">
              <strong>Doctor Safety Override:</strong> {{ event.override_reason }}
            </div>
          </div>

          <!-- EVENT BODY: 4. Lab Order -->
          <div v-else-if="event.type === 'lab_order'" class="text-xs space-y-2">
            <div class="flex items-center justify-between">
              <span class="font-bold text-sm text-slate-800">{{ event.test_type }}</span>
              <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase" :class="event.status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700'">
                {{ event.status }}
              </span>
            </div>
            <div v-if="event.results_summary" class="p-2.5 bg-slate-50 rounded-lg border border-slate-100">
              <span class="font-bold text-[10px] uppercase text-slate-400 block">Reported Results</span>
              <p class="text-slate-800 font-mono mt-0.5">{{ event.results_summary }}</p>
            </div>
          </div>

          <!-- EVENT BODY: 5. Radiology Order -->
          <div v-else-if="event.type === 'radiology_order'" class="text-xs space-y-2">
            <div class="flex items-center justify-between">
              <span class="font-bold text-sm text-slate-800">{{ event.modality }} - {{ event.procedure_name }}</span>
              <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase" :class="event.status === 'reported' ? 'bg-purple-100 text-purple-800' : 'bg-slate-100 text-slate-700'">
                {{ event.status }}
              </span>
            </div>
            <div v-if="event.findings || event.impression" class="p-2.5 bg-slate-50 rounded-lg border border-slate-100 space-y-1">
              <div v-if="event.findings"><strong class="text-slate-600">Findings:</strong> {{ event.findings }}</div>
              <div v-if="event.impression"><strong class="text-slate-900">Impression:</strong> {{ event.impression }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: Add New Clinical Note (SOAP) -->
    <div v-if="isNewNoteModalOpen" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-200 p-6 space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h2 class="text-lg font-black text-slate-900">Record Clinical Note (SOAP)</h2>
            <p class="text-xs text-slate-400">Append a longitudinal medical note to the patient's EHR file.</p>
          </div>
          <button @click="isNewNoteModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 text-xl font-bold cursor-pointer">&times;</button>
        </div>

        <form @submit.prevent="submitNewNote" class="space-y-4 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Encounter Title</label>
            <input
              v-model="newNoteForm.title"
              type="text"
              required
              placeholder="e.g. Routine Follow-Up & Diabetes Reassessment"
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white text-xs font-medium"
            />
          </div>

          <!-- Chief Complaint -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">Chief Complaint</label>
            <input
              v-model="newNoteForm.clinical_notes.chief_complaint"
              type="text"
              placeholder="e.g. Headache and dizziness for 2 days"
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white text-xs"
            />
          </div>

          <!-- SOAP Grid -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Subjective (S)</label>
              <textarea
                v-model="newNoteForm.clinical_notes.subjective"
                rows="3"
                placeholder="Patient's reported symptoms and history..."
                class="w-full p-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white text-xs"
              ></textarea>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Objective (O)</label>
              <textarea
                v-model="newNoteForm.clinical_notes.objective"
                rows="3"
                placeholder="Physical exam findings, observation..."
                class="w-full p-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white text-xs"
              ></textarea>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Assessment (A)</label>
              <textarea
                v-model="newNoteForm.clinical_notes.assessment"
                rows="3"
                placeholder="Clinical impressions, working diagnoses..."
                class="w-full p-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white text-xs"
              ></textarea>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Plan (P)</label>
              <textarea
                v-model="newNoteForm.clinical_notes.plan"
                rows="3"
                placeholder="Therapy, prescriptions, follow-up..."
                class="w-full p-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white text-xs"
              ></textarea>
            </div>
          </div>

          <!-- Vitals -->
          <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 space-y-2">
            <span class="font-bold text-slate-800 text-[11px] uppercase tracking-wider block">Clinical Vitals</span>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
              <div>
                <label class="text-[10px] text-slate-500 font-semibold">BP Systolic</label>
                <input v-model.number="newNoteForm.vitals.bp_systolic" type="number" placeholder="120" class="w-full p-1.5 bg-white border border-slate-200 rounded-lg text-xs" />
              </div>
              <div>
                <label class="text-[10px] text-slate-500 font-semibold">BP Diastolic</label>
                <input v-model.number="newNoteForm.vitals.bp_diastolic" type="number" placeholder="80" class="w-full p-1.5 bg-white border border-slate-200 rounded-lg text-xs" />
              </div>
              <div>
                <label class="text-[10px] text-slate-500 font-semibold">Heart Rate</label>
                <input v-model.number="newNoteForm.vitals.heart_rate" type="number" placeholder="72" class="w-full p-1.5 bg-white border border-slate-200 rounded-lg text-xs" />
              </div>
              <div>
                <label class="text-[10px] text-slate-500 font-semibold">SpO2 (%)</label>
                <input v-model.number="newNoteForm.vitals.spo2" type="number" placeholder="98" class="w-full p-1.5 bg-white border border-slate-200 rounded-lg text-xs" />
              </div>
            </div>
          </div>

          <!-- Status & Submission -->
          <div class="flex items-center justify-between pt-3 border-t border-slate-100">
            <div class="flex items-center gap-2">
              <label class="text-xs text-slate-600 font-medium">Save Status:</label>
              <select v-model="newNoteForm.status" class="p-1.5 bg-slate-100 border border-slate-200 rounded-lg text-xs font-bold">
                <option value="finalized">Finalized & Locked</option>
                <option value="draft">Draft (Pending Review)</option>
              </select>
            </div>

            <div class="flex items-center gap-2">
              <button
                type="button"
                @click="isNewNoteModalOpen = false"
                class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-semibold cursor-pointer"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="isSubmitting"
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-5 py-2 rounded-xl shadow-sm transition cursor-pointer"
              >
                {{ isSubmitting ? 'Saving...' : 'Save Note' }}
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL: Amend Finalized EHR Note (Append-Only Audit Trail) -->
    <div v-if="isAmendModalOpen" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-xl w-full shadow-2xl border border-slate-200 p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h2 class="text-lg font-black text-slate-900">Amend Finalized Medical Record</h2>
            <p class="text-xs text-slate-400">Append-only audit trail: previous record remains immutable.</p>
          </div>
          <button @click="isAmendModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 text-xl font-bold cursor-pointer">&times;</button>
        </div>

        <form @submit.prevent="submitAmendment" class="space-y-4 text-xs">
          <!-- Mandatory Amendment Reason -->
          <div>
            <label class="block font-bold text-amber-900 mb-1">
              Clinical Amendment Justification <span class="text-red-500">*</span>
            </label>
            <textarea
              v-model="amendForm.amendment_reason"
              required
              rows="2"
              placeholder="e.g. Correcting physical exam notes based on repeated vitals assessment and review with attending."
              class="w-full p-2.5 bg-amber-50/60 border border-amber-200 rounded-xl focus:bg-white text-xs"
            ></textarea>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Updated Assessment & Plan</label>
            <textarea
              v-model="amendForm.clinical_notes.assessment"
              rows="3"
              placeholder="Amended clinical assessment..."
              class="w-full p-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white text-xs"
            ></textarea>
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
            <button
              type="button"
              @click="isAmendModalOpen = false"
              class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-semibold cursor-pointer"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="isSubmitting"
              class="bg-amber-600 hover:bg-amber-700 text-white font-bold px-5 py-2 rounded-xl shadow-sm transition cursor-pointer"
            >
              {{ isSubmitting ? 'Recording...' : 'Commit Versioned Amendment' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  patient: { type: Object, required: true },
  branchId: { type: String, required: true },
});

const emit = defineEmits(['back', 'openPrescriptionWriter', 'openOrderModal']);

const patientData = ref(props.patient || {});
const summary = ref({});
const rawTimeline = ref([]);
const activeFilter = ref('all');
const isLoading = ref(false);
const isSubmitting = ref(false);

const isNewNoteModalOpen = ref(false);
const isAmendModalOpen = ref(false);
const targetAmendingRecord = ref(null);

const filterTabs = [
  { label: 'All Entries', value: 'all' },
  { label: 'Notes (EHR)', value: 'ehr_record' },
  { label: 'Diagnoses', value: 'diagnosis' },
  { label: 'Prescriptions', value: 'prescription' },
  { label: 'Lab Orders', value: 'lab_order' },
  { label: 'Radiology', value: 'radiology_order' },
];

const newNoteForm = ref({
  patient_id: props.patient.id,
  title: '',
  record_type: 'consultation_note',
  category: 'general',
  status: 'finalized',
  clinical_notes: {
    chief_complaint: '',
    subjective: '',
    objective: '',
    assessment: '',
    plan: '',
  },
  vitals: {
    bp_systolic: null,
    bp_diastolic: null,
    heart_rate: null,
    spo2: null,
    temperature_c: null,
  },
});

const amendForm = ref({
  amendment_reason: '',
  title: '',
  clinical_notes: {},
  vitals: {},
});

const filteredTimeline = computed(() => {
  if (activeFilter.value === 'all') {
    return rawTimeline.value;
  }
  return rawTimeline.value.filter((e) => e.type === activeFilter.value);
});

function formatDateTime(isoStr) {
  if (!isoStr) return '';
  const d = new Date(isoStr);
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function formatEventType(type) {
  switch (type) {
    case 'ehr_record': return 'Consultation Note';
    case 'diagnosis': return 'Diagnosis';
    case 'prescription': return 'E-Prescription';
    case 'lab_order': return 'Lab Order';
    case 'radiology_order': return 'Imaging Study';
    default: return type;
  }
}

function getEventEmoji(type) {
  switch (type) {
    case 'ehr_record': return '📝';
    case 'diagnosis': return '🏷️';
    case 'prescription': return '💊';
    case 'lab_order': return '🧪';
    case 'radiology_order': return '🩻';
    default: return '📄';
  }
}

function getTimelineNodeClass(type) {
  switch (type) {
    case 'ehr_record': return 'border-blue-500 text-blue-700';
    case 'diagnosis': return 'border-indigo-500 text-indigo-700';
    case 'prescription': return 'border-emerald-500 text-emerald-700';
    case 'lab_order': return 'border-amber-500 text-amber-700';
    case 'radiology_order': return 'border-purple-500 text-purple-700';
    default: return 'border-slate-400 text-slate-700';
  }
}

function getEventTypeBadgeClass(type) {
  switch (type) {
    case 'ehr_record': return 'bg-blue-100 text-blue-800';
    case 'diagnosis': return 'bg-indigo-100 text-indigo-800';
    case 'prescription': return 'bg-emerald-100 text-emerald-800';
    case 'lab_order': return 'bg-amber-100 text-amber-800';
    case 'radiology_order': return 'bg-purple-100 text-purple-800';
    default: return 'bg-slate-100 text-slate-700';
  }
}

function getEventTitle(event) {
  switch (event.type) {
    case 'ehr_record': return event.title;
    case 'diagnosis': return `${event.icd10_code} - ${event.icd10_title}`;
    case 'prescription': return `Rx #${event.prescription_number} (${event.items?.length || 0} items)`;
    case 'lab_order': return event.test_type;
    case 'radiology_order': return `${event.modality} (${event.procedure_name})`;
    default: return 'Clinical Event';
  }
}

async function fetchTimeline() {
  if (!props.patient?.id || props.patient.id === 'undefined') {
    return;
  }
  isLoading.value = true;
  try {
    const res = await axios.get(`/api/v1/clinical/patients/${props.patient.id}/ehr-timeline`, {
      headers: {
        'X-Branch-ID': props.branchId,
        'Accept': 'application/json',
      },
    });

    const data = res.data.data || {};
    patientData.value = data.patient || props.patient;
    summary.value = data.summary || {};
    rawTimeline.value = data.timeline || [];
  } catch (err) {
    if (typeof navigator === 'undefined' || navigator.onLine) {
      console.error('Failed to load EHR timeline', err);
    }
  } finally {
    isLoading.value = false;
  }
}

async function submitNewNote() {
  isSubmitting.value = true;
  try {
    await axios.post('/api/v1/clinical/ehr', newNoteForm.value, {
      headers: {
        'X-Branch-ID': props.branchId,
        'Accept': 'application/json',
      },
    });

    isNewNoteModalOpen.value = false;
    newNoteForm.value.title = '';
    newNoteForm.value.clinical_notes = { chief_complaint: '', subjective: '', objective: '', assessment: '', plan: '' };
    fetchTimeline();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to save clinical note.');
  } finally {
    isSubmitting.value = false;
  }
}

function openAmendModal(event) {
  targetAmendingRecord.value = event;
  amendForm.value = {
    amendment_reason: '',
    title: event.title,
    clinical_notes: JSON.parse(JSON.stringify(event.clinical_notes || {})),
    vitals: JSON.parse(JSON.stringify(event.vitals || {})),
  };
  isAmendModalOpen.value = true;
}

async function submitAmendment() {
  if (!targetAmendingRecord.value) return;
  isSubmitting.value = true;

  try {
    await axios.post(`/api/v1/clinical/ehr/${targetAmendingRecord.value.id}/amend`, amendForm.value, {
      headers: {
        'X-Branch-ID': props.branchId,
        'Accept': 'application/json',
      },
    });

    isAmendModalOpen.value = false;
    fetchTimeline();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to amend record.');
  } finally {
    isSubmitting.value = false;
  }
}

async function finalizeEhrRecord(event) {
  try {
    await axios.post(`/api/v1/clinical/ehr/${event.id}/finalize`, {}, {
      headers: {
        'X-Branch-ID': props.branchId,
        'Accept': 'application/json',
      },
    });
    fetchTimeline();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to finalize EHR record.');
  }
}

onMounted(() => {
  fetchTimeline();
});

defineExpose({ fetchTimeline });
</script>
