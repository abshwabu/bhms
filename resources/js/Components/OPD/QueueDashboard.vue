<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h2 class="text-xl font-black text-slate-900 tracking-tight">OPD Queue & Waiting Room System</h2>
        <p class="text-xs text-slate-500 mt-1">Daily department token sequencing, doctor calling console, and waiting room TV screen board.</p>
      </div>

      <div class="flex items-center gap-3">
        <!-- Waiting Room TV Display Toggle -->
        <button
          @click="showTvScreen = !showTvScreen"
          :class="showTvScreen ? 'bg-amber-600 text-white' : 'bg-slate-800 text-white hover:bg-slate-900'"
          class="px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer shadow-sm"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
          {{ showTvScreen ? 'Exit TV Mode' : 'Open Waiting Room TV Screen' }}
        </button>

        <button
          @click="showIssueModal = true"
          class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition flex items-center gap-2 cursor-pointer shadow-sm"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Issue Walk-In Token
        </button>
      </div>
    </div>

    <!-- Doctor Calling Station Console -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-gradient-to-br from-indigo-900 to-slate-900 text-white p-6 rounded-3xl shadow-xl space-y-4 md:col-span-1">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-mono uppercase tracking-widest text-indigo-300 font-bold">Calling Station</span>
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">Department</label>
          <select
            v-model="activeDepartmentId"
            @change="fetchQueue"
            class="w-full text-xs rounded-xl bg-slate-800/80 border border-slate-700 text-white p-2.5 focus:border-indigo-400"
          >
            <option value="">All Departments</option>
            <option v-for="dept in departments" :key="dept.id" :value="dept.id">
              {{ dept.name }} ({{ dept.code }})
            </option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">Consultation Room / Counter</label>
          <input
            type="text"
            v-model="callingRoom"
            placeholder="e.g. Room 102 / Desk 3"
            class="w-full text-xs rounded-xl bg-slate-800/80 border border-slate-700 text-white p-2.5 focus:border-indigo-400"
          />
        </div>

        <button
          @click="callNextPatient"
          :disabled="calling"
          class="w-full py-3 rounded-2xl bg-indigo-500 hover:bg-indigo-600 active:scale-[0.98] disabled:opacity-50 text-white font-extrabold text-sm transition shadow-lg shadow-indigo-500/30 flex items-center justify-center gap-2 cursor-pointer"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
          {{ calling ? 'Calling...' : 'Call Next Waiting Patient' }}
        </button>

        <div v-if="lastCalledToken" class="pt-2 border-t border-slate-800 text-xs">
          <div class="text-slate-400 text-[11px]">Currently Calling:</div>
          <div class="text-lg font-black text-emerald-400 font-mono mt-0.5">{{ lastCalledToken.token_code }}</div>
          <div class="text-xs text-slate-300">{{ lastCalledToken.patient ? lastCalledToken.patient.full_name : 'Patient' }} &rarr; {{ lastCalledToken.counter_room }}</div>
        </div>
      </div>

      <!-- Live Queue Statistics -->
      <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 md:col-span-2 flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-slate-900 text-sm">Department Queue Summary</h3>
            <div class="flex items-center gap-2">
              <label class="text-[11px] text-slate-500 font-medium flex items-center gap-1.5 cursor-pointer">
                <input type="checkbox" v-model="autoPolling" class="rounded text-blue-600 focus:ring-0" />
                Live Auto-Refresh (5s)
              </label>
            </div>
          </div>

          <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="p-4 rounded-2xl bg-blue-50 border border-blue-100">
              <div class="text-2xl font-black text-blue-800 font-mono">{{ waitingTokens.length }}</div>
              <div class="text-xs font-bold text-blue-600 mt-1">Waiting in Bay</div>
            </div>
            <div class="p-4 rounded-2xl bg-amber-50 border border-amber-100">
              <div class="text-2xl font-black text-amber-800 font-mono">{{ callingTokens.length }}</div>
              <div class="text-xs font-bold text-amber-600 mt-1">Called / In Consult</div>
            </div>
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-100">
              <div class="text-2xl font-black text-emerald-800 font-mono">{{ completedTokens.length }}</div>
              <div class="text-xs font-bold text-emerald-600 mt-1">Finished Today</div>
            </div>
          </div>
        </div>

        <!-- Token list for selected department -->
        <div class="space-y-2">
          <div class="text-xs font-semibold text-slate-700">Upcoming in Line</div>
          <div class="flex flex-wrap gap-2">
            <span
              v-for="t in waitingTokens.slice(0, 8)"
              :key="t.id"
              class="px-3 py-1.5 rounded-xl border text-xs font-mono font-bold flex items-center gap-1.5"
              :class="t.priority === 'emergency' ? 'bg-rose-50 border-rose-300 text-rose-700' : (t.priority === 'urgent' ? 'bg-amber-50 border-amber-300 text-amber-800' : 'bg-slate-100 border-slate-200 text-slate-700')"
            >
              {{ t.token_code }}
              <span v-if="t.priority !== 'normal'" class="text-[9px] uppercase font-sans font-black">({{ t.priority }})</span>
            </span>
            <span v-if="waitingTokens.length === 0" class="text-xs text-slate-400 py-1">Queue is clear.</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Active Tokens Management Table -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
      <div class="p-6 border-b border-slate-100 flex items-center justify-between">
        <div>
          <h3 class="font-bold text-slate-900 text-sm">Today's Token Ledger</h3>
          <p class="text-xs text-slate-500">Resets daily per department to ensure consistent numbered sequences</p>
        </div>
        <button @click="fetchQueue" class="text-xs text-blue-600 font-bold hover:underline">Refresh</button>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50 border-b border-slate-100 text-slate-400 uppercase text-[10px] font-bold">
            <tr>
              <th class="py-3 px-6">Token</th>
              <th class="py-3 px-6">Dept</th>
              <th class="py-3 px-6">Patient</th>
              <th class="py-3 px-6">Priority</th>
              <th class="py-3 px-6">Status</th>
              <th class="py-3 px-6">Room / Counter</th>
              <th class="py-3 px-6 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="allTokens.length === 0">
              <td colspan="7" class="py-8 text-center text-slate-400">No tokens issued today.</td>
            </tr>
            <tr v-for="token in allTokens" :key="token.id" class="hover:bg-slate-50/50">
              <td class="py-3.5 px-6 font-mono font-black text-sm text-slate-900">{{ token.token_code }}</td>
              <td class="py-3.5 px-6 font-semibold text-slate-700">{{ token.department ? token.department.code : 'OPD' }}</td>
              <td class="py-3.5 px-6">
                <div class="font-bold text-slate-800">{{ token.patient ? token.patient.full_name : 'Walk-In Patient' }}</div>
                <div class="text-[10px] text-slate-400 font-mono">{{ token.patient ? token.patient.mrn : '' }}</div>
              </td>
              <td class="py-3.5 px-6">
                <span
                  class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase"
                  :class="{
                    'bg-slate-100 text-slate-700': token.priority === 'normal',
                    'bg-amber-100 text-amber-800': token.priority === 'urgent',
                    'bg-rose-100 text-rose-800': token.priority === 'emergency'
                  }"
                >
                  {{ token.priority }}
                </span>
              </td>
              <td class="py-3.5 px-6">
                <span
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider"
                  :class="{
                    'bg-slate-100 text-slate-600': token.status === 'waiting',
                    'bg-indigo-100 text-indigo-700 animate-pulse': token.status === 'called',
                    'bg-amber-100 text-amber-800': token.status === 'in_consultation',
                    'bg-emerald-100 text-emerald-800': token.status === 'completed',
                    'bg-slate-200 text-slate-500': token.status === 'skipped'
                  }"
                >
                  {{ token.status.replace('_', ' ') }}
                </span>
              </td>
              <td class="py-3.5 px-6 font-medium text-slate-800">{{ token.counter_room || '-' }}</td>
              <td class="py-3.5 px-6 text-right space-x-2">
                <button
                  v-if="token.status === 'called'"
                  @click="updateTokenStatus(token, 'in_consultation')"
                  class="px-2.5 py-1 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-bold text-[11px]"
                >
                  Start Consult
                </button>
                <button
                  v-if="['called', 'in_consultation'].includes(token.status)"
                  @click="updateTokenStatus(token, 'completed')"
                  class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px]"
                >
                  Done
                </button>
                <button
                  v-if="token.status === 'waiting'"
                  @click="updateTokenStatus(token, 'skipped')"
                  class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold text-[11px]"
                >
                  Skip
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Waiting Room TV Screen Board Overlay -->
    <div v-if="showTvScreen" class="fixed inset-0 z-50 bg-slate-950 text-white p-8 flex flex-col justify-between overflow-hidden">
      <!-- TV Header -->
      <div class="flex items-center justify-between border-b border-slate-800 pb-6">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center font-black text-2xl shadow-lg shadow-blue-500/50">
            +
          </div>
          <div>
            <div class="text-2xl font-black tracking-tight">METRO GENERAL HOSPITAL</div>
            <div class="text-xs text-blue-400 font-mono tracking-wider uppercase">OUTPATIENT WAITING LOUNGE DISPLAY</div>
          </div>
        </div>

        <div class="flex items-center gap-6">
          <div class="text-right">
            <div class="text-2xl font-mono font-black text-slate-200">{{ currentTime }}</div>
            <div class="text-xs text-slate-400">{{ currentDate }}</div>
          </div>
          <button @click="showTvScreen = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-slate-300 cursor-pointer">
            Close Screen
          </button>
        </div>
      </div>

      <!-- TV Main Display Area -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8 my-8 flex-1">
        <!-- Now Calling Hero Banner -->
        <div class="bg-gradient-to-br from-blue-900/60 to-indigo-950/80 border-2 border-blue-500/40 rounded-3xl p-10 flex flex-col justify-center items-center text-center shadow-2xl relative overflow-hidden">
          <div class="absolute top-4 right-4 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-400 text-xs font-bold tracking-widest uppercase flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
            Now Calling
          </div>

          <div v-if="displayData && displayData.calling && displayData.calling.length > 0">
            <div class="text-7xl font-mono font-black text-amber-300 tracking-wider mb-4 drop-shadow-md">
              {{ displayData.calling[0].token_code }}
            </div>
            <div class="text-2xl font-bold text-white mb-2">
              {{ displayData.calling[0].patient_name }}
            </div>
            <div class="text-3xl font-extrabold text-blue-300 bg-blue-900/40 px-6 py-2.5 rounded-2xl inline-block border border-blue-400/30">
              Proceed to: {{ displayData.calling[0].counter_room }}
            </div>
          </div>

          <div v-else class="text-slate-500 text-lg">
            Please wait for your token to be announced.
          </div>
        </div>

        <!-- Upcoming Queue Grid on TV -->
        <div class="bg-slate-900/80 rounded-3xl p-6 border border-slate-800 flex flex-col justify-between">
          <div>
            <h3 class="text-xs uppercase font-mono font-bold text-slate-400 tracking-wider mb-4 flex items-center justify-between">
              <span>Next In Line</span>
              <span>Waiting: {{ displayData ? displayData.waiting_count : 0 }}</span>
            </h3>

            <div class="space-y-3">
              <div
                v-for="item in (displayData ? displayData.upcoming_queue : [])"
                :key="item.token_code"
                class="flex items-center justify-between p-4 rounded-2xl bg-slate-800/60 border border-slate-700/60"
              >
                <div class="flex items-center gap-4">
                  <span class="text-2xl font-mono font-black text-white">{{ item.token_code }}</span>
                  <span class="text-xs text-slate-400">{{ item.department_name }}</span>
                </div>
                <span
                  class="px-2.5 py-1 rounded-lg text-xs font-black uppercase"
                  :class="item.priority === 'urgent' ? 'bg-amber-500/20 text-amber-300' : (item.priority === 'emergency' ? 'bg-rose-500/20 text-rose-300' : 'bg-slate-700 text-slate-300')"
                >
                  {{ item.priority }}
                </span>
              </div>
              <div v-if="!displayData || !displayData.upcoming_queue || displayData.upcoming_queue.length === 0" class="text-slate-500 text-center py-12">
                No waiting tokens.
              </div>
            </div>
          </div>

          <div class="text-center text-xs text-slate-500 font-mono pt-4 border-t border-slate-800">
            Please have your ID and insurance card ready when your number is called.
          </div>
        </div>
      </div>

      <!-- TV Footer Ticker -->
      <div class="bg-slate-900 px-6 py-3 rounded-2xl flex items-center justify-between text-xs text-slate-400">
        <div>Emergency cases receive priority attention at all triage stations.</div>
        <div class="font-mono text-emerald-400">System Live • WebSocket / Real-Time Sync Active</div>
      </div>
    </div>

    <!-- Walk-In Token Issuance Modal -->
    <div v-if="showIssueModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
      <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 border border-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
          <h3 class="font-bold text-slate-900 text-base">Issue Walk-In Queue Token</h3>
          <button @click="showIssueModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">&times;</button>
        </div>

        <form @submit.prevent="issueToken" class="space-y-4 text-xs">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Department *</label>
            <select v-model="tokenForm.department_id" required class="w-full text-xs rounded-xl border-slate-300 p-2.5 border bg-white">
              <option value="">Select Department</option>
              <option v-for="d in departments" :key="d.id" :value="d.id">
                {{ d.name }} ({{ d.code }})
              </option>
            </select>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Patient UUID *</label>
            <input type="text" v-model="tokenForm.patient_id" placeholder="Patient UUID" required class="w-full text-xs rounded-xl border-slate-300 p-2.5 border" />
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Triage Priority</label>
            <select v-model="tokenForm.priority" class="w-full text-xs rounded-xl border-slate-300 p-2.5 border bg-white">
              <option value="normal">Normal (Routine)</option>
              <option value="urgent">Urgent</option>
              <option value="emergency">Emergency (Stat)</option>
            </select>
          </div>

          <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
            <button type="button" @click="showIssueModal = false" class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-semibold cursor-pointer">Cancel</button>
            <button type="submit" :disabled="issuing" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold transition shadow-sm cursor-pointer">
              {{ issuing ? 'Issuing...' : 'Generate Token' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { showAlert } from '../../Services/modalDialog';

const props = defineProps({
  branchId: { type: String, required: true },
});

const departments = ref([]);
const activeDepartmentId = ref('');
const callingRoom = ref('Room 101');
const allTokens = ref([]);
const calling = ref(false);
const issuing = ref(false);
const lastCalledToken = ref(null);

const showIssueModal = ref(false);
const showTvScreen = ref(false);
const autoPolling = ref(true);
let pollInterval = null;

const displayData = ref(null);
const currentTime = ref('');
const currentDate = ref('');

const tokenForm = ref({
  department_id: '',
  patient_id: '',
  priority: 'normal',
});

const waitingTokens = computed(() => allTokens.value.filter(t => t.status === 'waiting'));
const callingTokens = computed(() => allTokens.value.filter(t => ['called', 'in_consultation'].includes(t.status)));
const completedTokens = computed(() => allTokens.value.filter(t => t.status === 'completed'));

async function fetchDepartments() {
  try {
    const res = await fetch('/api/v1/opd/departments', {
      headers: { 'X-Branch-ID': props.branchId, 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.data) {
      departments.value = json.data;
      if (!activeDepartmentId.value && json.data.length > 0) {
        activeDepartmentId.value = json.data[0].id;
        tokenForm.value.department_id = json.data[0].id;
      }
    }
  } catch (e) {
    console.error('Failed to fetch departments', e);
  }
}

async function fetchQueue() {
  try {
    let url = '/api/v1/opd/queue/tokens';
    if (activeDepartmentId.value) {
      url += `?department_id=${activeDepartmentId.value}`;
    }
    const res = await fetch(url, {
      headers: { 'X-Branch-ID': props.branchId, 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.data) {
      allTokens.value = json.data;
    }
  } catch (e) {
    console.error('Failed to fetch queue', e);
  }
}

async function fetchDisplayFeed() {
  try {
    let url = '/api/v1/opd/queue/display';
    if (activeDepartmentId.value) {
      url += `?department_id=${activeDepartmentId.value}`;
    }
    const res = await fetch(url, {
      headers: { 'X-Branch-ID': props.branchId, 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.data) {
      displayData.value = json.data;
    }
  } catch (e) {
    console.error('Failed to fetch display feed', e);
  }
}

async function callNextPatient() {
  if (!activeDepartmentId.value) {
    await showAlert('Please choose a department first.');
    return;
  }
  calling.value = true;
  try {
    const res = await fetch('/api/v1/opd/queue/call-next', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Branch-ID': props.branchId,
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        department_id: activeDepartmentId.value,
        counter_room: callingRoom.value,
      })
    });
    const json = await res.json();
    if (json.data) {
      lastCalledToken.value = json.data;
    } else {
      await showAlert(json.message || 'No waiting patients.');
    }
    await fetchQueue();
    await fetchDisplayFeed();
  } catch (e) {
    console.error('Call next error', e);
  } finally {
    calling.value = false;
  }
}

async function updateTokenStatus(token, status) {
  try {
    const res = await fetch(`/api/v1/opd/queue/tokens/${token.id}/status`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-Branch-ID': props.branchId,
        'Accept': 'application/json'
      },
      body: JSON.stringify({ status })
    });
    if (res.ok) {
      await fetchQueue();
      await fetchDisplayFeed();
    }
  } catch (e) {
    console.error('Status update error', e);
  }
}

async function issueToken() {
  issuing.value = true;
  try {
    const res = await fetch('/api/v1/opd/queue/tokens', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Branch-ID': props.branchId,
        'Accept': 'application/json'
      },
      body: JSON.stringify(tokenForm.value)
    });
    const json = await res.json();
    if (res.ok) {
      await showAlert(`Token Issued: ${json.data.token_code}`);
      showIssueModal.value = false;
      await fetchQueue();
      await fetchDisplayFeed();
    } else {
      await showAlert(json.message || 'Error issuing token');
    }
  } catch (e) {
    console.error('Issue token error', e);
  } finally {
    issuing.value = false;
  }
}

function updateClock() {
  const now = new Date();
  currentTime.value = now.toLocaleTimeString();
  currentDate.value = now.toLocaleDateString(undefined, { weekday: 'long', month: 'short', day: 'numeric', year: 'numeric' });
}

onMounted(async () => {
  updateClock();
  setInterval(updateClock, 1000);
  await fetchDepartments();
  await fetchQueue();
  await fetchDisplayFeed();

  pollInterval = setInterval(() => {
    if (autoPolling.value) {
      fetchQueue();
      fetchDisplayFeed();
    }
  }, 5000);
});

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval);
});
</script>
