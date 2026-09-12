<template>
  <div class="space-y-6">
    <!-- Action Header & Quick Verify Bar -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <!-- Quick Verification Box -->
      <div class="lg:col-span-2 bg-gradient-to-r from-blue-900 to-indigo-900 p-5 rounded-3xl text-white shadow-lg space-y-3">
        <div class="flex items-center gap-2">
          <svg class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <h3 class="text-sm font-bold tracking-tight">Instant Clinical Consent Verification</h3>
        </div>
        <p class="text-xs text-blue-200">Verify whether a patient has active, non-expired, and non-revoked consent before performing surgical procedures or sharing ePHI.</p>

        <div class="flex flex-wrap items-center gap-2 pt-1">
          <input
            v-model="verifyPatientId"
            type="text"
            placeholder="Enter Patient UUID..."
            class="px-3.5 py-2 rounded-xl bg-white/10 border border-white/20 text-xs text-white placeholder-blue-300 focus:outline-none focus:ring-2 focus:ring-white/40 flex-1 min-w-[200px]"
          />
          <select
            v-model="verifyConsentType"
            class="px-3.5 py-2 rounded-xl bg-white/10 border border-white/20 text-xs text-white focus:outline-none focus:ring-2 focus:ring-white/40"
          >
            <option class="text-slate-800" value="treatment_general">General Treatment</option>
            <option class="text-slate-800" value="surgical_procedure">Surgical Procedure</option>
            <option class="text-slate-800" value="data_sharing">EHR Data Sharing</option>
            <option class="text-slate-800" value="telehealth">Telehealth Session</option>
            <option class="text-slate-800" value="hipaa_notice">HIPAA Privacy Notice</option>
          </select>
          <button
            @click="verifyConsent"
            class="px-4 py-2 bg-emerald-500 hover:bg-emerald-400 active:bg-emerald-600 text-white rounded-xl text-xs font-bold transition shadow cursor-pointer"
          >
            Verify Status
          </button>
        </div>

        <!-- Verification Result Banner -->
        <div v-if="verificationResult !== null" class="mt-2 p-3 rounded-xl border text-xs flex items-center justify-between"
          :class="verificationResult.has_valid_consent ? 'bg-emerald-500/20 border-emerald-400/30 text-emerald-100' : 'bg-rose-500/20 border-rose-400/30 text-rose-100'"
        >
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full" :class="verificationResult.has_valid_consent ? 'bg-emerald-400' : 'bg-rose-400'"></span>
            <span>
              <strong>{{ verificationResult.has_valid_consent ? 'VALID CONSENT ACTIVE' : 'NO VALID CONSENT FOUND' }}:</strong>
              {{ verificationResult.consent ? verificationResult.consent.title : 'Procedure blocked until consent is granted.' }}
            </span>
          </div>
          <span class="font-mono text-[10px] opacity-75">Checked at {{ new Date().toLocaleTimeString() }}</span>
        </div>
      </div>

      <!-- New Consent Action Card -->
      <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-between space-y-4">
        <div>
          <h4 class="text-base font-black text-slate-800">Patient Consent Ledger</h4>
          <p class="text-xs text-slate-500 mt-1">Legally binding electronic signature capture with column-level AES-256 PII protection.</p>
        </div>

        <button
          @click="openConsentModal"
          class="w-full py-3 bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white rounded-2xl text-xs font-bold transition flex items-center justify-center gap-2 cursor-pointer shadow-md shadow-blue-600/20"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span>Record New Patient Consent</span>
        </button>
      </div>
    </div>

    <!-- Consent Records Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200/80 uppercase tracking-wider text-[10px]">
            <tr>
              <th class="p-3.5">Patient</th>
              <th class="p-3.5">Type & Title</th>
              <th class="p-3.5">Status</th>
              <th class="p-3.5">Granted Date</th>
              <th class="p-3.5">Expires</th>
              <th class="p-3.5">Witness</th>
              <th class="p-3.5 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="loading" class="text-center">
              <td colspan="7" class="p-8 text-slate-400">Loading patient consent directives...</td>
            </tr>
            <tr v-else-if="!consents.length" class="text-center">
              <td colspan="7" class="p-8 text-slate-400">No consent records found.</td>
            </tr>
            <tr
              v-for="c in consents"
              :key="c.id"
              class="hover:bg-slate-50/80 transition"
            >
              <td class="p-3.5">
                <div class="font-bold text-slate-900">
                  {{ c.patient ? `${c.patient.first_name} ${c.patient.last_name}` : 'Unknown Patient' }}
                </div>
                <div class="text-[10px] font-mono text-slate-400">{{ c.patient ? c.patient.mrn : c.patient_id }}</div>
              </td>
              <td class="p-3.5">
                <div class="font-bold text-slate-800">{{ c.title }}</div>
                <div class="text-[10px] font-mono text-slate-500 capitalize">{{ c.consent_type.replace(/_/g, ' ') }}</div>
              </td>
              <td class="p-3.5">
                <span
                  :class="{
                    'bg-emerald-100 text-emerald-800 border-emerald-300': c.status === 'granted',
                    'bg-rose-100 text-rose-800 border-rose-300': c.status === 'revoked',
                    'bg-slate-100 text-slate-600 border-slate-300': c.status === 'expired'
                  }"
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border font-mono"
                >
                  {{ c.status }}
                </span>
              </td>
              <td class="p-3.5 font-mono text-slate-600">
                {{ formatDateTime(c.granted_at) }}
              </td>
              <td class="p-3.5 font-mono text-slate-600">
                {{ c.expires_at ? formatDateTime(c.expires_at) : 'No Expiry' }}
              </td>
              <td class="p-3.5 text-slate-600">
                {{ c.witness_name || (c.witness ? c.witness.name : '—') }}
              </td>
              <td class="p-3.5 text-right">
                <button
                  v-if="c.status === 'granted'"
                  @click="openRevokeModal(c)"
                  class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-xs font-semibold transition cursor-pointer"
                >
                  Revoke Consent
                </button>
                <span v-else class="text-[11px] text-slate-400 italic">
                  Revoked
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Capture Consent Modal -->
    <div v-if="showConsentModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-xl w-full max-h-[90vh] flex flex-col shadow-2xl overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
          <h3 class="text-base font-black text-slate-900">Record Patient Legal Consent</h3>
          <button @click="showConsentModal = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-6 overflow-y-auto space-y-4 flex-1">
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Patient UUID</label>
            <input
              v-model="consentForm.patient_id"
              type="text"
              placeholder="Paste patient UUID..."
              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Consent Type</label>
              <select
                v-model="consentForm.consent_type"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
              >
                <option value="treatment_general">General Treatment</option>
                <option value="surgical_procedure">Surgical Procedure</option>
                <option value="data_sharing">EHR Data Sharing</option>
                <option value="telehealth">Telehealth Session</option>
                <option value="research_trial">Clinical Research</option>
                <option value="hipaa_notice">HIPAA Privacy Notice</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Document Title</label>
              <input
                v-model="consentForm.title"
                type="text"
                placeholder="e.g. Consent for Appendectomy"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Purpose & Legal Scope</label>
            <textarea
              v-model="consentForm.purpose"
              rows="3"
              placeholder="Describe clinical procedure and data authorization scope..."
              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
            ></textarea>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Witness Name</label>
              <input
                v-model="consentForm.witness_name"
                type="text"
                placeholder="Dr. Eleanor Vance"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Digital Signature Hash</label>
              <input
                v-model="consentForm.signature_data"
                type="text"
                placeholder="e.g. sig_hash_sha256_xxx"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-mono focus:ring-2 focus:ring-blue-500 focus:outline-none"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Encrypted Clinical Notes (PII Protected)</label>
            <textarea
              v-model="consentForm.sensitive_notes"
              rows="2"
              placeholder="Confidential notes encrypted at rest via AES-256..."
              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
            ></textarea>
          </div>
        </div>

        <div class="p-4 border-t border-slate-100 flex items-center justify-end gap-3 bg-slate-50">
          <button
            @click="showConsentModal = false"
            class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800 transition cursor-pointer"
          >
            Cancel
          </button>
          <button
            @click="saveConsent"
            :disabled="savingConsent"
            class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer shadow-md disabled:opacity-50"
          >
            <span>{{ savingConsent ? 'Recording...' : 'Grant & Record Consent' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Revoke Consent Modal -->
    <div v-if="revokingConsent" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-md w-full shadow-2xl overflow-hidden p-6 space-y-4">
        <h3 class="text-base font-black text-slate-900">Revoke Patient Consent</h3>
        <p class="text-xs text-slate-500">
          Revoking consent will legally terminate active authorizations for:
          <strong class="text-slate-800">{{ revokingConsent.title }}</strong>
        </p>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Revocation Reason</label>
          <textarea
            v-model="revocationReason"
            rows="3"
            placeholder="Document patient request or clinical cancellation justification..."
            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none"
          ></textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
          <button
            @click="revokingConsent = null"
            class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800 transition cursor-pointer"
          >
            Cancel
          </button>
          <button
            @click="confirmRevoke"
            class="px-5 py-2.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold transition cursor-pointer shadow-md"
          >
            Confirm Legal Revocation
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const consents = ref([]);
const loading = ref(false);
const showConsentModal = ref(false);
const savingConsent = ref(false);
const revokingConsent = ref(null);
const revocationReason = ref('');

const verifyPatientId = ref('');
const verifyConsentType = ref('treatment_general');
const verificationResult = ref(null);

const consentForm = ref({
  patient_id: '',
  consent_type: 'treatment_general',
  title: '',
  purpose: '',
  witness_name: '',
  signature_data: '',
  sensitive_notes: '',
});

function formatDateTime(dt) {
  if (!dt) return '—';
  return new Date(dt).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

async function loadConsents() {
  loading.value = true;
  try {
    const res = await fetch('/api/v1/compliance/consents', {
      headers: { 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.success) {
      consents.value = json.data;
    }
  } catch (err) {
    console.error('Failed to load consents:', err);
  } finally {
    loading.value = false;
  }
}

async function verifyConsent() {
  if (!verifyPatientId.value) return;
  try {
    const res = await fetch(`/api/v1/compliance/patients/${verifyPatientId.value}/verify?consent_type=${verifyConsentType.value}`, {
      headers: { 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.success) {
      verificationResult.value = json.data;
    }
  } catch (err) {
    console.error('Verification failed:', err);
  }
}

function openConsentModal() {
  consentForm.value = {
    patient_id: verifyPatientId.value || '',
    consent_type: 'treatment_general',
    title: '',
    purpose: '',
    witness_name: '',
    signature_data: 'sig_captured_' + Math.random().toString(36).substring(7),
    sensitive_notes: '',
  };
  showConsentModal.value = true;
}

async function saveConsent() {
  if (!consentForm.value.patient_id || !consentForm.value.title || !consentForm.value.purpose) return;
  savingConsent.value = true;

  try {
    const res = await fetch('/api/v1/compliance/consents', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify(consentForm.value)
    });

    const json = await res.json();
    if (json.success) {
      showConsentModal.value = false;
      await loadConsents();
      if (verifyPatientId.value === consentForm.value.patient_id) {
        await verifyConsent();
      }
    }
  } catch (err) {
    console.error('Failed to save consent:', err);
  } finally {
    savingConsent.value = false;
  }
}

function openRevokeModal(consent) {
  revokingConsent.value = consent;
  revocationReason.value = '';
}

async function confirmRevoke() {
  if (!revokingConsent.value || !revocationReason.value) return;

  try {
    const res = await fetch(`/api/v1/compliance/consents/${revokingConsent.value.id}/revoke`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({ reason: revocationReason.value })
    });

    const json = await res.json();
    if (json.success) {
      revokingConsent.value = null;
      await loadConsents();
      if (verifyPatientId.value) {
        await verifyConsent();
      }
    }
  } catch (err) {
    console.error('Failed to revoke consent:', err);
  }
}

onMounted(() => {
  loadConsents();
});
</script>
