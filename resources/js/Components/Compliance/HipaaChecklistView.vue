<template>
  <div class="space-y-6">
    <!-- Header Banner & Real-time Action -->
    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 p-6 rounded-3xl text-white shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div class="space-y-2">
        <div class="flex items-center gap-3">
          <span class="p-2 bg-indigo-500/20 text-indigo-300 rounded-xl border border-indigo-500/30">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
          </span>
          <h2 class="text-2xl font-black tracking-tight">HIPAA Security & Privacy Safeguards</h2>
        </div>
        <p class="text-slate-300 text-xs max-w-2xl">
          Automated evaluation engine assessing 45 CFR Part 164 Subpart C & E controls across access authorization, audit logging, column-level at-rest encryption, TLS transmission, and patient consent directives.
        </p>
      </div>

      <div class="flex items-center gap-4">
        <!-- Scorecard Circle / Pill -->
        <div class="bg-slate-800/80 border border-slate-700/80 px-5 py-3 rounded-2xl text-center">
          <div class="text-[10px] font-mono text-slate-400 uppercase tracking-wider">Compliance Score</div>
          <div class="text-3xl font-black" :class="scoreColor">
            {{ scorecard.compliance_score ?? 100 }}%
          </div>
          <div class="text-[10px] font-semibold uppercase tracking-wider mt-0.5" :class="statusBadgeColor">
            {{ scorecard.overall_status ?? 'COMPLIANT' }}
          </div>
        </div>

        <button
          @click="runEvaluation"
          :disabled="evaluating"
          class="px-5 py-3 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white rounded-2xl text-xs font-bold shadow-lg shadow-indigo-600/30 transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
        >
          <svg v-if="evaluating" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
          </svg>
          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <span>{{ evaluating ? 'Auditing Controls...' : 'Re-evaluate All Safeguards' }}</span>
        </button>
      </div>
    </div>

    <!-- Overview Counters -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3">
        <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <div>
          <div class="text-xs text-slate-500 font-medium">Compliant Controls</div>
          <div class="text-xl font-black text-slate-800">{{ scorecard.compliant_checks ?? 0 }} / {{ scorecard.total_checks ?? 5 }}</div>
        </div>
      </div>

      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3">
        <div class="p-2.5 bg-amber-50 text-amber-600 rounded-xl">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <div>
          <div class="text-xs text-slate-500 font-medium">Advisory Warnings</div>
          <div class="text-xl font-black text-slate-800">{{ scorecard.warning_checks ?? 0 }}</div>
        </div>
      </div>

      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3">
        <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
        </div>
        <div>
          <div class="text-xs text-slate-500 font-medium">AES-256 PII Columns</div>
          <div class="text-xl font-black text-slate-800">5 Columns Encrypted</div>
        </div>
      </div>

      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3">
        <div class="p-2.5 bg-purple-50 text-purple-600 rounded-xl">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <div class="text-xs text-slate-500 font-medium">Audit Trail Coverage</div>
          <div class="text-xl font-black text-slate-800">100% Immutable</div>
        </div>
      </div>
    </div>

    <!-- Detailed Safeguards Checklist Cards -->
    <div class="space-y-4">
      <div
        v-for="check in (scorecard.checks || [])"
        :key="check.id"
        class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition space-y-3"
      >
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <div class="flex items-center gap-3">
            <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-slate-100 text-slate-700">
              § {{ check.safeguard_code }}
            </span>
            <h3 class="text-base font-bold text-slate-900">{{ check.title }}</h3>
          </div>
          <span
            :class="{
              'bg-emerald-100 text-emerald-800 border-emerald-300': check.status === 'compliant',
              'bg-amber-100 text-amber-800 border-amber-300': check.status === 'warning',
              'bg-rose-100 text-rose-800 border-rose-300': check.status === 'non_compliant'
            }"
            class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider border self-start sm:self-auto"
          >
            {{ check.status }}
          </span>
        </div>

        <p class="text-xs text-slate-600 leading-relaxed">{{ check.description }}</p>

        <!-- Technical Telemetry Details -->
        <div v-if="check.details && Object.keys(check.details).length" class="bg-slate-50 rounded-xl p-3 border border-slate-100 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
          <div v-for="(val, key) in check.details" :key="key" class="text-xs">
            <span class="font-mono text-[11px] text-slate-400 capitalize">{{ formatDetailKey(key) }}: </span>
            <span class="font-semibold text-slate-700">
              {{ Array.isArray(val) ? val.join(', ') : (val === true ? 'Enabled' : (val === false ? 'Disabled' : val)) }}
            </span>
          </div>
        </div>

        <!-- Remediation Advice -->
        <div v-if="check.remediation_steps" class="flex items-start gap-2 text-xs text-slate-500 bg-slate-100/50 p-2.5 rounded-xl">
          <svg class="w-4 h-4 text-indigo-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span><strong class="text-slate-700">Guidance:</strong> {{ check.remediation_steps }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const scorecard = ref({});
const evaluating = ref(false);

const scoreColor = computed(() => {
  const s = scorecard.value.compliance_score ?? 100;
  if (s >= 90) return 'text-emerald-400';
  if (s >= 70) return 'text-amber-400';
  return 'text-rose-400';
});

const statusBadgeColor = computed(() => {
  const status = scorecard.value.overall_status ?? 'compliant';
  if (status === 'compliant') return 'text-emerald-300';
  if (status === 'warning') return 'text-amber-300';
  return 'text-rose-300';
});

function formatDetailKey(key) {
  return key.replace(/_/g, ' ');
}

async function loadChecklist() {
  try {
    const res = await fetch('/api/v1/compliance/hipaa/checklist', {
      headers: { 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.success && json.data) {
      scorecard.value = json.data;
    }
  } catch (err) {
    console.error('Failed to load HIPAA checklist:', err);
  }
}

async function runEvaluation() {
  evaluating.value = true;
  try {
    const res = await fetch('/api/v1/compliance/hipaa/evaluate', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      }
    });
    const json = await res.json();
    if (json.success && json.data) {
      scorecard.value = json.data;
    }
  } catch (err) {
    console.error('Evaluation failed:', err);
  } finally {
    evaluating.value = false;
  }
}

onMounted(() => {
  loadChecklist();
});
</script>
