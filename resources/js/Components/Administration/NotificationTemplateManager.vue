<template>
  <div class="space-y-6">
    <!-- Header with Quick Test Console -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <!-- Quick Test Dispatch Console -->
      <div class="lg:col-span-2 bg-gradient-to-r from-slate-900 to-indigo-950 p-5 rounded-3xl text-white shadow-lg space-y-3">
        <div class="flex items-center gap-2">
          <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
          </svg>
          <h3 class="text-sm font-bold tracking-tight">Notification Engine Test Console</h3>
        </div>
        <p class="text-xs text-slate-300">Test live transmission through pluggable providers (Twilio SMS, SMTP Mail, FCM Push) with zero silent drops.</p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-1">
          <select v-model="testForm.channel" class="px-3 py-2 rounded-xl bg-white/10 border border-white/20 text-xs text-white focus:outline-none">
            <option class="text-slate-800" value="sms">SMS Gateway</option>
            <option class="text-slate-800" value="email">Email / SMTP</option>
            <option class="text-slate-800" value="push">Push (FCM)</option>
          </select>
          <input
            v-model="testForm.recipient"
            type="text"
            placeholder="Recipient (+1-555..., email, token)"
            class="px-3.5 py-2 rounded-xl bg-white/10 border border-white/20 text-xs text-white placeholder-slate-400 focus:outline-none col-span-2"
          />
        </div>

        <div class="flex items-center gap-2">
          <input
            v-model="testForm.body"
            type="text"
            placeholder="Notification message body..."
            class="px-3.5 py-2 rounded-xl bg-white/10 border border-white/20 text-xs text-white placeholder-slate-400 focus:outline-none flex-1"
          />
          <button
            @click="sendTestNotification"
            :disabled="sendingTest"
            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow cursor-pointer disabled:opacity-50"
          >
            {{ sendingTest ? 'Sending...' : 'Transmit Test' }}
          </button>
        </div>

        <div v-if="testResult" class="mt-2 p-3 rounded-xl border text-xs flex items-center justify-between"
          :class="testResult.status === 'sent' ? 'bg-emerald-500/20 border-emerald-400/30 text-emerald-100' : 'bg-amber-500/20 border-amber-400/30 text-amber-100'"
        >
          <div>
            <strong>{{ testResult.status === 'sent' ? 'DELIVERED:' : 'RETRYING / LOGGED:' }}</strong>
            Provider [{{ testResult.provider }}] | Message ID: {{ testResult.id }}
            <span v-if="testResult.error_message" class="block text-[11px] text-rose-300 mt-0.5">{{ testResult.error_message }}</span>
          </div>
          <span class="font-mono text-[10px] opacity-75">{{ new Date().toLocaleTimeString() }}</span>
        </div>
      </div>

      <!-- Action Card -->
      <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-between space-y-4">
        <div>
          <h4 class="text-base font-black text-slate-800">Notification Engine</h4>
          <p class="text-xs text-slate-500 mt-1">Multi-channel message templates with automated variable interpolation and failure retries.</p>
        </div>

        <button
          @click="openCreateTemplateModal"
          class="w-full py-3 bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white rounded-2xl text-xs font-bold transition flex items-center justify-center gap-2 cursor-pointer shadow-md shadow-blue-600/20"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span>Create New Template</span>
        </button>
      </div>
    </div>

    <!-- Tab View: Templates vs Delivery Logs -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
      <button
        @click="viewTab = 'templates'"
        :class="viewTab === 'templates' ? 'border-b-2 border-blue-600 text-blue-600 font-bold' : 'text-slate-500 hover:text-slate-800'"
        class="px-4 py-2 text-xs transition cursor-pointer"
      >
        Message Templates Library ({{ templates.length }})
      </button>
      <button
        @click="viewTab = 'logs'"
        :class="viewTab === 'logs' ? 'border-b-2 border-blue-600 text-blue-600 font-bold' : 'text-slate-500 hover:text-slate-800'"
        class="px-4 py-2 text-xs transition cursor-pointer"
      >
        Delivery & Retry Audit Trail ({{ logs.length }})
      </button>
    </div>

    <!-- 1. Templates Table -->
    <div v-if="viewTab === 'templates'" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200/80 uppercase tracking-wider text-[10px]">
            <tr>
              <th class="p-3.5">Template Code</th>
              <th class="p-3.5">Name & Description</th>
              <th class="p-3.5">Channel</th>
              <th class="p-3.5">Message Body Preview</th>
              <th class="p-3.5">Status</th>
              <th class="p-3.5 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="!templates.length" class="text-center">
              <td colspan="6" class="p-8 text-slate-400">Loading templates...</td>
            </tr>
            <tr v-for="t in templates" :key="t.id" class="hover:bg-slate-50/80 transition">
              <td class="p-3.5 font-mono font-bold text-slate-800">{{ t.code }}</td>
              <td class="p-3.5">
                <div class="font-bold text-slate-900">{{ t.name }}</div>
                <div v-if="t.subject" class="text-[10px] text-indigo-600 font-medium">Subj: {{ t.subject }}</div>
              </td>
              <td class="p-3.5">
                <span
                  :class="{
                    'bg-emerald-100 text-emerald-800': t.channel === 'sms',
                    'bg-blue-100 text-blue-800': t.channel === 'email',
                    'bg-purple-100 text-purple-800': t.channel === 'push',
                  }"
                  class="px-2 py-0.5 rounded text-[10px] font-bold uppercase font-mono"
                >
                  {{ t.channel }}
                </span>
              </td>
              <td class="p-3.5 text-slate-600 max-w-[280px] truncate">{{ t.body }}</td>
              <td class="p-3.5">
                <span :class="t.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'" class="px-2 py-0.5 rounded text-[10px] font-bold">
                  {{ t.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="p-3.5 text-right">
                <button @click="deleteTemplate(t.id)" class="text-rose-600 hover:text-rose-800 font-bold text-xs cursor-pointer">
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- 2. Delivery & Retry Audit Trail Table -->
    <div v-else class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200/80 uppercase tracking-wider text-[10px]">
            <tr>
              <th class="p-3.5">Timestamp</th>
              <th class="p-3.5">Channel</th>
              <th class="p-3.5">Recipient</th>
              <th class="p-3.5">Provider</th>
              <th class="p-3.5">Status</th>
              <th class="p-3.5">Retries</th>
              <th class="p-3.5 text-right">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="!logs.length" class="text-center">
              <td colspan="7" class="p-8 text-slate-400">No notification logs recorded.</td>
            </tr>
            <tr v-for="log in logs" :key="log.id" class="hover:bg-slate-50/80 transition">
              <td class="p-3.5 font-mono text-slate-500">{{ new Date(log.created_at).toLocaleTimeString() }}</td>
              <td class="p-3.5 font-mono font-bold uppercase text-[10px]">{{ log.channel }}</td>
              <td class="p-3.5 font-mono text-slate-800">{{ log.recipient }}</td>
              <td class="p-3.5 font-mono text-slate-600">{{ log.provider }}</td>
              <td class="p-3.5">
                <span
                  :class="{
                    'bg-emerald-100 text-emerald-800': log.status === 'sent',
                    'bg-amber-100 text-amber-800': log.status === 'retrying',
                    'bg-rose-100 text-rose-800': log.status === 'failed',
                    'bg-slate-100 text-slate-800': log.status === 'queued',
                  }"
                  class="px-2 py-0.5 rounded text-[10px] font-bold uppercase font-mono"
                >
                  {{ log.status }}
                </span>
                <span v-if="log.error_message" class="block text-[10px] text-rose-600 truncate max-w-[180px] mt-0.5">{{ log.error_message }}</span>
              </td>
              <td class="p-3.5 font-mono text-slate-600">{{ log.retry_count }} / {{ log.max_retries }}</td>
              <td class="p-3.5 text-right">
                <button
                  v-if="log.status === 'retrying' || log.status === 'failed'"
                  @click="retryLog(log.id)"
                  class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 rounded font-bold text-xs transition cursor-pointer"
                >
                  Retry Now
                </button>
                <span v-else class="text-[10px] text-emerald-600 font-bold">Delivered</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create Template Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-lg w-full shadow-2xl overflow-hidden p-6 space-y-4">
        <h3 class="text-base font-black text-slate-900">Create Notification Template</h3>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Code</label>
            <input v-model="templateForm.code" type="text" placeholder="e.g. lab_ready" class="w-full px-3 py-2 border rounded-xl text-xs font-mono" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Channel</label>
            <select v-model="templateForm.channel" class="w-full px-3 py-2 border rounded-xl text-xs bg-white">
              <option value="sms">SMS</option>
              <option value="email">Email</option>
              <option value="push">Push Notification</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Template Name</label>
          <input v-model="templateForm.name" type="text" placeholder="Lab Results Ready Alert" class="w-full px-3 py-2 border rounded-xl text-xs" />
        </div>

        <div v-if="templateForm.channel !== 'sms'">
          <label class="block text-xs font-bold text-slate-700 mb-1">Subject</label>
          <input v-model="templateForm.subject" type="text" placeholder="Your Medical Test Results Are Ready" class="w-full px-3 py-2 border rounded-xl text-xs" />
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Body Text (Supports Tokens)</label>
          <textarea v-model="templateForm.body" rows="3" placeholder="Hello {{patient_name}}, your test is ready at {{hospital_name}}." class="w-full px-3 py-2 border rounded-xl text-xs"></textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
          <button @click="showCreateModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 cursor-pointer">
            Cancel
          </button>
          <button @click="saveTemplate" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold shadow cursor-pointer">
            Save Template
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const viewTab = ref('templates');
const templates = ref([]);
const logs = ref([]);
const showCreateModal = ref(false);
const sendingTest = ref(false);
const testResult = ref(null);

const testForm = ref({
  channel: 'sms',
  recipient: '+1-555-0199',
  body: 'Hello, your appointment is confirmed at Metro Health System.',
});

const templateForm = ref({
  code: '',
  name: '',
  channel: 'sms',
  subject: '',
  body: '',
});

async function loadTemplates() {
  try {
    const res = await fetch('/api/v1/admin/notification-templates', { headers: { 'Accept': 'application/json' } });
    const json = await res.json();
    if (json.success) templates.value = json.data;
  } catch (err) {
    console.error('Failed to load templates:', err);
  }
}

async function loadLogs() {
  try {
    const res = await fetch('/api/v1/admin/notifications/logs', { headers: { 'Accept': 'application/json' } });
    const json = await res.json();
    if (json.success) logs.value = json.data;
  } catch (err) {
    console.error('Failed to load logs:', err);
  }
}

function openCreateTemplateModal() {
  templateForm.value = { code: '', name: '', channel: 'sms', subject: '', body: '' };
  showCreateModal.value = true;
}

async function saveTemplate() {
  if (!templateForm.value.code || !templateForm.value.body) return;

  try {
    const res = await fetch('/api/v1/admin/notification-templates', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify(templateForm.value),
    });

    const json = await res.json();
    if (json.success) {
      showCreateModal.value = false;
      await loadTemplates();
    }
  } catch (err) {
    console.error('Failed to save template:', err);
  }
}

async function deleteTemplate(id) {
  if (!confirm('Are you sure you want to delete this template?')) return;
  try {
    const res = await fetch(`/api/v1/admin/notification-templates/${id}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      }
    });
    const json = await res.json();
    if (json.success) await loadTemplates();
  } catch (err) {
    console.error('Delete failed:', err);
  }
}

async function sendTestNotification() {
  sendingTest.value = true;
  testResult.value = null;

  try {
    const res = await fetch('/api/v1/admin/notifications/send', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify(testForm.value),
    });

    const json = await res.json();
    testResult.value = json.data;
    await loadLogs();
  } catch (err) {
    console.error('Test notification failed:', err);
  } finally {
    sendingTest.value = false;
  }
}

async function retryLog(id) {
  try {
    const res = await fetch(`/api/v1/admin/notifications/logs/${id}/retry`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      }
    });
    const json = await res.json();
    if (json.success) await loadLogs();
  } catch (err) {
    console.error('Retry failed:', err);
  }
}

onMounted(() => {
  loadTemplates();
  loadLogs();
});
</script>
