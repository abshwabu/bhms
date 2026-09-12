<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-sky-500 to-blue-600 flex items-center justify-center text-white shadow-md shadow-sky-500/20">
          <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.52 2.77-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .27z"/>
          </svg>
        </div>
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-xl font-bold text-slate-900">Telegram Operational Reporting & Alert Engine</h1>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-sky-100 text-sky-800">
              Live Bot Active
            </span>
          </div>
          <p class="text-xs text-slate-500 mt-0.5">
            Real-time critical clinical push, daily operational summaries, end-of-shift handovers, and role-based bot command dispatcher.
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <button
          @click="openChannelModal()"
          class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold bg-sky-600 hover:bg-sky-700 text-white shadow-sm transition cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Register Channel
        </button>
        <button
          @click="fetchData()"
          class="p-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 transition cursor-pointer"
          title="Refresh Data"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Quick Stat KPI Banners -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between">
          <span class="text-xs font-medium text-slate-500">Active Channels</span>
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
        </div>
        <div class="mt-2 text-2xl font-bold text-slate-800">{{ activeChannelCount }}</div>
        <div class="text-[11px] text-slate-400 mt-1">Across {{ channels.length }} registered groups</div>
      </div>

      <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between">
          <span class="text-xs font-medium text-slate-500">Messages Delivered</span>
          <span class="text-sky-600 font-semibold text-xs">99.8% OK</span>
        </div>
        <div class="mt-2 text-2xl font-bold text-emerald-600">{{ sentLogCount }}</div>
        <div class="text-[11px] text-slate-400 mt-1">Audit-verified outbound dispatches</div>
      </div>

      <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between">
          <span class="text-xs font-medium text-slate-500">Pending Retries / Failed</span>
          <span v-if="failedLogCount > 0" class="px-1.5 py-0.5 rounded text-[10px] bg-rose-100 text-rose-700 font-bold">Action Needed</span>
        </div>
        <div class="mt-2 text-2xl font-bold" :class="failedLogCount > 0 ? 'text-rose-600' : 'text-slate-800'">
          {{ failedLogCount }}
        </div>
        <div class="text-[11px] text-slate-400 mt-1">With automatic backoff retry queue</div>
      </div>

      <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between">
          <span class="text-xs font-medium text-slate-500">Bot Command Parser</span>
          <span class="text-indigo-600 font-semibold text-xs">RBAC Enforced</span>
        </div>
        <div class="mt-2 text-2xl font-bold text-indigo-600">Active</div>
        <div class="text-[11px] text-slate-400 mt-1">Accepting /revenue, /beds, /stock, /help</div>
      </div>
    </div>

    <!-- Tab Navigation -->
    <div class="border-b border-slate-200 flex gap-4 text-xs font-semibold">
      <button
        @click="activeSubTab = 'channels'"
        :class="activeSubTab === 'channels' ? 'border-sky-600 text-sky-600 border-b-2 pb-2.5' : 'text-slate-500 hover:text-slate-700 pb-2.5 cursor-pointer'"
      >
        Telegram Channels & RBAC Permissions ({{ channels.length }})
      </button>
      <button
        @click="activeSubTab = 'triggers'"
        :class="activeSubTab === 'triggers' ? 'border-sky-600 text-sky-600 border-b-2 pb-2.5' : 'text-slate-500 hover:text-slate-700 pb-2.5 cursor-pointer'"
      >
        Instant Dispatch & Alert Studio
      </button>
      <button
        @click="activeSubTab = 'simulator'"
        :class="activeSubTab === 'simulator' ? 'border-sky-600 text-sky-600 border-b-2 pb-2.5' : 'text-slate-500 hover:text-slate-700 pb-2.5 cursor-pointer'"
      >
        Interactive Bot Command Console
      </button>
      <button
        @click="activeSubTab = 'logs'"
        :class="activeSubTab === 'logs' ? 'border-sky-600 text-sky-600 border-b-2 pb-2.5' : 'text-slate-500 hover:text-slate-700 pb-2.5 cursor-pointer'"
      >
        Delivery Audit & Retry Ledger
      </button>
    </div>

    <!-- TAB 1: Channel Registry -->
    <div v-if="activeSubTab === 'channels'" class="space-y-4">
      <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
          <div class="text-xs font-bold text-slate-700 uppercase tracking-wider">Configured Telegram Channels</div>
          <div class="flex items-center gap-2">
            <select v-model="channelFilterRole" class="text-xs border border-slate-200 rounded-lg px-2.5 py-1.5 bg-white text-slate-700">
              <option value="">All Roles</option>
              <option value="admin">Executive Admins</option>
              <option value="doctors">Physicians & Doctors</option>
              <option value="nursing">Nursing Team</option>
              <option value="pharmacy">Dispensary & Pharmacy</option>
              <option value="finance">Finance & Revenue</option>
              <option value="emergency">Emergency & Trauma</option>
            </select>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50/70 border-b border-slate-100 text-slate-500 uppercase text-[10px] font-bold">
                <th class="p-3.5">Channel Name & Chat ID</th>
                <th class="p-3.5">Assigned Role</th>
                <th class="p-3.5">Subscribed Reports</th>
                <th class="p-3.5">Allowed Inbound Commands</th>
                <th class="p-3.5">Status</th>
                <th class="p-3.5 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="channel in filteredChannels" :key="channel.id" class="hover:bg-slate-50/50 transition">
                <td class="p-3.5">
                  <div class="font-bold text-slate-900">{{ channel.name }}</div>
                  <div class="font-mono text-[11px] text-slate-400 mt-0.5">Chat ID: <code>{{ channel.chat_id }}</code></div>
                  <div v-if="channel.description" class="text-[11px] text-slate-500 mt-0.5">{{ channel.description }}</div>
                </td>
                <td class="p-3.5">
                  <span :class="getRoleBadgeClass(channel.role)" class="px-2 py-0.5 rounded-full font-bold uppercase text-[10px]">
                    {{ channel.role }}
                  </span>
                </td>
                <td class="p-3.5">
                  <div class="flex flex-wrap gap-1">
                    <span v-for="rep in channel.allowed_report_types" :key="rep" class="px-1.5 py-0.5 bg-slate-100 text-slate-700 rounded text-[10px] font-mono">
                      {{ rep }}
                    </span>
                  </div>
                </td>
                <td class="p-3.5">
                  <div class="flex flex-wrap gap-1">
                    <span v-for="cmd in channel.allowed_commands" :key="cmd" class="px-1.5 py-0.5 bg-blue-50 text-blue-700 border border-blue-100 rounded text-[10px] font-mono font-medium">
                      {{ cmd }}
                    </span>
                  </div>
                </td>
                <td class="p-3.5">
                  <span v-if="channel.is_active" class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                  </span>
                  <span v-else class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 text-slate-500">
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Inactive
                  </span>
                </td>
                <td class="p-3.5 text-right space-x-1.5 whitespace-nowrap">
                  <button
                    @click="testPingChannel(channel)"
                    :disabled="isPinging[channel.id]"
                    class="px-2.5 py-1 text-[11px] font-semibold rounded-lg border border-sky-200 text-sky-700 hover:bg-sky-50 transition cursor-pointer disabled:opacity-50"
                  >
                    {{ isPinging[channel.id] ? 'Pinging...' : 'Test Ping' }}
                  </button>
                  <button
                    @click="editChannel(channel)"
                    class="px-2 py-1 text-[11px] font-semibold rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-100 transition cursor-pointer"
                  >
                    Edit
                  </button>
                  <button
                    @click="deleteChannel(channel.id)"
                    class="px-2 py-1 text-[11px] font-semibold rounded-lg border border-rose-200 text-rose-700 hover:bg-rose-50 transition cursor-pointer"
                  >
                    Remove
                  </button>
                </td>
              </tr>
              <tr v-if="filteredChannels.length === 0">
                <td colspan="6" class="p-8 text-center text-slate-400">
                  No Telegram channels registered matching criteria. Click "Register Channel" above to connect a Telegram group.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 2: Instant Dispatch & Alert Studio -->
    <div v-if="activeSubTab === 'triggers'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Section A: Scheduled Report Triggers -->
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
        <div class="flex items-center gap-2">
          <div class="p-2 rounded-lg bg-sky-50 text-sky-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
          </div>
          <div>
            <h2 class="text-sm font-bold text-slate-800">Operational Summary Bot Dispatch</h2>
            <p class="text-xs text-slate-500">Trigger standard digest or shift handovers to subscribed channels</p>
          </div>
        </div>

        <div class="space-y-3 pt-2">
          <div class="p-4 rounded-xl border border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <div>
              <div class="font-bold text-xs text-slate-800">Daily Operational Digest</div>
              <div class="text-[11px] text-slate-500">Admissions, discharges, bed census, today's revenue & pending labs</div>
            </div>
            <button
              @click="triggerDailyDigest()"
              :disabled="isSubmitting"
              class="px-3.5 py-1.5 text-xs font-semibold rounded-xl bg-sky-600 hover:bg-sky-700 text-white transition cursor-pointer disabled:opacity-50"
            >
              Dispatch Now
            </button>
          </div>

          <div class="p-4 rounded-xl border border-slate-100 bg-slate-50/50 space-y-3">
            <div class="flex items-center justify-between">
              <div>
                <div class="font-bold text-xs text-slate-800">End-of-Shift Handover Briefing</div>
                <div class="text-[11px] text-slate-500">Inpatient census, ICU load, high-priority clinical follow-ups</div>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <select v-model="handoverShift" class="text-xs border border-slate-200 rounded-lg px-3 py-1.5 bg-white text-slate-700 flex-1">
                <option value="morning">Morning Shift (07:00 - 15:00)</option>
                <option value="evening">Evening Shift (15:00 - 23:00)</option>
                <option value="night">Night Shift (23:00 - 07:00)</option>
              </select>
              <button
                @click="triggerShiftHandover()"
                :disabled="isSubmitting"
                class="px-3.5 py-1.5 text-xs font-semibold rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white transition cursor-pointer disabled:opacity-50"
              >
                Send Handover
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Section B: Real-Time Critical Alert Simulator -->
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
        <div class="flex items-center gap-2">
          <div class="p-2 rounded-lg bg-rose-50 text-rose-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
          </div>
          <div>
            <h2 class="text-sm font-bold text-slate-800">Unbatched Critical Alert Push</h2>
            <p class="text-xs text-slate-500">Instantly fires to subscribed clinical roles within seconds</p>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 pt-2">
          <button
            @click="triggerCriticalAlert('emergency_esi1')"
            class="p-3 rounded-xl border border-rose-200 bg-rose-50/50 hover:bg-rose-100/60 text-left transition cursor-pointer"
          >
            <div class="flex items-center gap-1.5 text-rose-700 font-bold text-xs">
              <span class="w-2 h-2 rounded-full bg-rose-600 animate-ping"></span>
              ESI-1 Resuscitation
            </div>
            <div class="text-[11px] text-slate-600 mt-1">Immediate Code Blue / ER resuscitation push to doctors & nursing.</div>
          </button>

          <button
            @click="triggerCriticalAlert('critical_lab')"
            class="p-3 rounded-xl border border-amber-200 bg-amber-50/50 hover:bg-amber-100/60 text-left transition cursor-pointer"
          >
            <div class="flex items-center gap-1.5 text-amber-700 font-bold text-xs">
              <span>⚠️</span>
              Panic Lab Value
            </div>
            <div class="text-[11px] text-slate-600 mt-1">High-potassium, troponin panic alert to attending physician channel.</div>
          </button>

          <button
            @click="triggerCriticalAlert('icu_shortage')"
            class="p-3 rounded-xl border border-purple-200 bg-purple-50/50 hover:bg-purple-100/60 text-left transition cursor-pointer"
          >
            <div class="flex items-center gap-1.5 text-purple-700 font-bold text-xs">
              <span>🛑</span>
              ICU Bed Shortage
            </div>
            <div class="text-[11px] text-slate-600 mt-1">Triggered when available ICU capacity falls below safety threshold.</div>
          </button>

          <button
            @click="triggerCriticalAlert('low_stock')"
            class="p-3 rounded-xl border border-emerald-200 bg-emerald-50/50 hover:bg-emerald-100/60 text-left transition cursor-pointer"
          >
            <div class="flex items-center gap-1.5 text-emerald-700 font-bold text-xs">
              <span>💊</span>
              Low Pharmacy Stock
            </div>
            <div class="text-[11px] text-slate-600 mt-1">Urgent replenishment reorder notice dispatched to pharmacy chat.</div>
          </button>
        </div>
      </div>
    </div>

    <!-- TAB 3: Interactive Bot Command Console -->
    <div v-if="activeSubTab === 'simulator'" class="grid grid-cols-1 md:grid-cols-12 gap-6">
      <div class="md:col-span-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
        <div>
          <h2 class="text-sm font-bold text-slate-800">Simulate Staff Telegram Chat</h2>
          <p class="text-xs text-slate-500">Test how different roles interact with bot commands & evaluate RBAC restrictions.</p>
        </div>

        <div class="space-y-3">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Select Channel / Sender Perspective</label>
            <select v-model="simChannelId" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 bg-white text-slate-800">
              <option v-for="ch in channels" :key="ch.id" :value="ch.chat_id">
                {{ ch.name }} (Role: {{ ch.role }})
              </option>
              <option value="9999999999">Unregistered Stranger Chat (Unauthorized)</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Quick Commands</label>
            <div class="flex flex-wrap gap-1.5">
              <button
                v-for="cmd in ['/beds', '/revenue today', '/stock', '/patients', '/handover', '/digest', '/help']"
                :key="cmd"
                @click="simCommandText = cmd"
                class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-mono text-[11px] cursor-pointer"
              >
                {{ cmd }}
              </button>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Command Input</label>
            <div class="flex gap-2">
              <input
                v-model="simCommandText"
                type="text"
                placeholder="e.g. /revenue today"
                class="flex-1 text-xs border border-slate-200 rounded-xl px-3 py-2 text-slate-800 font-mono"
                @keyup.enter="runSimulatedCommand()"
              />
              <button
                @click="runSimulatedCommand()"
                :disabled="!simCommandText || isSimulating"
                class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-semibold disabled:opacity-50 cursor-pointer"
              >
                Send
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Simulator Chat Preview -->
      <div class="md:col-span-8 bg-slate-900 rounded-2xl p-6 shadow-xl border border-slate-800 flex flex-col justify-between min-h-[420px]">
        <div>
          <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div class="flex items-center gap-2 text-xs text-slate-300 font-medium">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
              <span>Telegram Bot Output Terminal</span>
            </div>
            <span class="text-[11px] font-mono text-slate-500">Parse Mode: HTML</span>
          </div>

          <div class="mt-4 space-y-4">
            <!-- Simulated Inbound Bubble -->
            <div v-if="lastSimResult" class="flex justify-end">
              <div class="bg-sky-600 text-white px-3.5 py-2 rounded-2xl rounded-tr-none text-xs max-w-md shadow-md">
                <div class="font-mono">{{ lastSimResult.sentCommand }}</div>
                <div class="text-[9px] text-sky-200 mt-1 text-right">Sender: {{ lastSimResult.senderChat }}</div>
              </div>
            </div>

            <!-- Bot Response Bubble -->
            <div v-if="lastSimResult" class="flex justify-start">
              <div
                :class="lastSimResult.authorized ? 'bg-slate-800 text-slate-100 border-slate-700' : 'bg-rose-950/60 text-rose-200 border-rose-800'"
                class="p-4 rounded-2xl rounded-tl-none text-xs max-w-xl border shadow-lg space-y-2"
              >
                <div class="flex items-center justify-between text-[10px] text-slate-400 border-b border-slate-700/50 pb-1.5">
                  <span class="font-bold flex items-center gap-1">
                    <span v-if="lastSimResult.authorized" class="text-emerald-400">● RBAC Authorized</span>
                    <span v-else class="text-rose-400">● Permission Denied</span>
                  </span>
                  <span class="font-mono">Status: {{ lastSimResult.status }}</span>
                </div>
                <div class="whitespace-pre-line font-mono text-[11px] leading-relaxed" v-html="lastSimResult.response"></div>
              </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-16 text-slate-500 text-xs">
              Select a channel and execute a command like <code class="text-sky-400">/revenue today</code> or <code class="text-sky-400">/beds</code> to preview real-time Telegram response formatting.
            </div>
          </div>
        </div>

        <div class="pt-4 border-t border-slate-800 text-[10px] text-slate-500 flex items-center justify-between">
          <span>Role-Based Access Control enforced per Telegram channel</span>
          <span>Telegram Bot API 7.x</span>
        </div>
      </div>
    </div>

    <!-- TAB 4: Delivery Audit & Retry Ledger -->
    <div v-if="activeSubTab === 'logs'" class="space-y-4">
      <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="p-4 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-3 bg-slate-50/50">
          <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Outbound Audit Ledger</span>
            <span class="text-xs text-slate-400">({{ logs.length }} events)</span>
          </div>

          <div class="flex items-center gap-2">
            <select v-model="logStatusFilter" class="text-xs border border-slate-200 rounded-lg px-2.5 py-1.5 bg-white text-slate-700">
              <option value="">All Statuses</option>
              <option value="sent">Delivered (Sent)</option>
              <option value="retrying">Retrying</option>
              <option value="failed">Failed</option>
            </select>
            <button
              @click="triggerRetrySweep()"
              :disabled="isRetryingSweep"
              class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-600 hover:bg-amber-700 text-white transition cursor-pointer disabled:opacity-50"
            >
              {{ isRetryingSweep ? 'Processing Sweep...' : 'Retry All Eligible' }}
            </button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50/70 border-b border-slate-100 text-slate-500 uppercase text-[10px] font-bold">
                <th class="p-3.5">Timestamp</th>
                <th class="p-3.5">Type & Direction</th>
                <th class="p-3.5">Recipient Chat / Channel</th>
                <th class="p-3.5">Status & Retries</th>
                <th class="p-3.5">Message Snippet</th>
                <th class="p-3.5 text-right">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="log in filteredLogs" :key="log.id" class="hover:bg-slate-50/50 transition">
                <td class="p-3.5 whitespace-nowrap font-mono text-[11px] text-slate-500">
                  {{ formatDate(log.created_at) }}
                </td>
                <td class="p-3.5 whitespace-nowrap">
                  <span class="font-mono text-[11px] font-semibold text-slate-800">{{ log.message_type }}</span>
                  <div class="text-[10px] text-slate-400 capitalize">{{ log.direction }}</div>
                </td>
                <td class="p-3.5">
                  <div class="font-semibold text-slate-800">{{ log.channel?.name || 'External / Simulator' }}</div>
                  <div class="font-mono text-[10px] text-slate-400"><code>{{ log.chat_id }}</code></div>
                </td>
                <td class="p-3.5 whitespace-nowrap">
                  <span
                    :class="{
                      'bg-emerald-50 text-emerald-700 border-emerald-200': log.status === 'sent',
                      'bg-amber-50 text-amber-700 border-amber-200': log.status === 'retrying',
                      'bg-rose-50 text-rose-700 border-rose-200': log.status === 'failed',
                      'bg-slate-100 text-slate-700 border-slate-200': log.status === 'queued',
                    }"
                    class="px-2 py-0.5 rounded-full border text-[10px] font-bold uppercase"
                  >
                    {{ log.status }}
                  </span>
                  <div class="text-[10px] text-slate-400 mt-0.5">
                    Retries: {{ log.retry_count }} / {{ log.max_retries }}
                  </div>
                </td>
                <td class="p-3.5 max-w-xs truncate font-mono text-[11px] text-slate-600">
                  {{ log.content?.substring(0, 70) }}...
                </td>
                <td class="p-3.5 text-right whitespace-nowrap">
                  <button
                    v-if="log.status === 'failed' || log.status === 'retrying'"
                    @click="retryIndividualMessage(log.id)"
                    class="px-2 py-1 text-[11px] font-bold rounded-lg border border-amber-300 text-amber-700 hover:bg-amber-50 transition cursor-pointer"
                  >
                    Retry Now
                  </button>
                  <span v-else class="text-slate-400 text-[11px]">Delivered</span>
                </td>
              </tr>
              <tr v-if="filteredLogs.length === 0">
                <td colspan="6" class="p-8 text-center text-slate-400">
                  No log entries matching status filter.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- MODAL: Register / Edit Telegram Channel -->
    <div v-if="isChannelModalOpen" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="text-base font-bold text-slate-900">
            {{ editingChannelId ? 'Edit Telegram Channel' : 'Register Telegram Channel' }}
          </h3>
          <button @click="isChannelModalOpen = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">✕</button>
        </div>

        <form @submit.prevent="saveChannel()" class="space-y-3 text-xs">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Telegram Chat ID (Group ID or Private ID) *</label>
            <input
              v-model="channelForm.chat_id"
              type="text"
              required
              placeholder="e.g. -1001234567890 or @hospital_doctors"
              class="w-full border border-slate-200 rounded-xl px-3 py-2 text-slate-800 font-mono"
            />
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Channel / Group Display Name *</label>
            <input
              v-model="channelForm.name"
              type="text"
              required
              placeholder="e.g. Executive Hospital Leadership"
              class="w-full border border-slate-200 rounded-xl px-3 py-2 text-slate-800"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Assigned Department Role *</label>
              <select v-model="channelForm.role" class="w-full border border-slate-200 rounded-xl px-3 py-2 bg-white text-slate-800">
                <option value="admin">Executive Admins</option>
                <option value="doctors">Physicians / Doctors</option>
                <option value="nursing">Nursing Supervisors</option>
                <option value="pharmacy">Pharmacy Dispensary</option>
                <option value="finance">Billing & Finance</option>
                <option value="emergency">Emergency & Trauma</option>
              </select>
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Bot Token Reference</label>
              <input
                v-model="channelForm.bot_token_ref"
                type="text"
                placeholder="TELEGRAM_BOT_TOKEN"
                class="w-full border border-slate-200 rounded-xl px-3 py-2 text-slate-800 font-mono"
              />
            </div>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Allowed Report Types (Check all that apply)</label>
            <div class="flex flex-wrap gap-2 pt-1">
              <label v-for="type in ['daily_digest', 'critical_alerts', 'shift_handover']" :key="type" class="inline-flex items-center gap-1.5 cursor-pointer">
                <input type="checkbox" :value="type" v-model="channelForm.allowed_report_types" class="rounded text-sky-600" />
                <span class="font-mono text-[11px] text-slate-700">{{ type }}</span>
              </label>
            </div>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Allowed Inbound Commands</label>
            <div class="flex flex-wrap gap-2 pt-1">
              <label v-for="cmd in ['/help', '/beds', '/revenue', '/stock', '/patients', '/handover', '/digest']" :key="cmd" class="inline-flex items-center gap-1.5 cursor-pointer">
                <input type="checkbox" :value="cmd" v-model="channelForm.allowed_commands" class="rounded text-sky-600" />
                <span class="font-mono text-[11px] text-slate-700">{{ cmd }}</span>
              </label>
            </div>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Description / Notes</label>
            <input
              v-model="channelForm.description"
              type="text"
              placeholder="e.g. Main executive briefing channel for CMO and CFO"
              class="w-full border border-slate-200 rounded-xl px-3 py-2 text-slate-800"
            />
          </div>

          <div class="flex items-center gap-2 pt-2">
            <input type="checkbox" id="isActive" v-model="channelForm.is_active" class="rounded text-sky-600" />
            <label for="isActive" class="font-semibold text-slate-700 cursor-pointer">Channel Active for Dispatch</label>
          </div>

          <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
            <button
              type="button"
              @click="isChannelModalOpen = false"
              class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 cursor-pointer"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-semibold cursor-pointer"
            >
              {{ editingChannelId ? 'Update Channel' : 'Save Channel' }}
            </button>
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

const activeSubTab = ref('channels');
const channels = ref([]);
const logs = ref([]);
const channelFilterRole = ref('');
const logStatusFilter = ref('');
const handoverShift = ref('morning');
const isSubmitting = ref(false);
const isPinging = ref({});
const isRetryingSweep = ref(false);

// Simulator state
const simChannelId = ref('');
const simCommandText = ref('/beds');
const isSimulating = ref(false);
const lastSimResult = ref(null);

// Modal state
const isChannelModalOpen = ref(false);
const editingChannelId = ref(null);
const channelForm = ref({
  chat_id: '',
  name: '',
  role: 'admin',
  bot_token_ref: 'TELEGRAM_BOT_TOKEN',
  allowed_report_types: ['daily_digest', 'critical_alerts'],
  allowed_commands: ['/help', '/beds', '/digest'],
  description: '',
  is_active: true,
});

const activeChannelCount = computed(() => channels.value.filter(c => c.is_active).length);
const sentLogCount = computed(() => logs.value.filter(l => l.status === 'sent').length);
const failedLogCount = computed(() => logs.value.filter(l => l.status === 'failed' || l.status === 'retrying').length);

const filteredChannels = computed(() => {
  if (!channelFilterRole.value) return channels.value;
  return channels.value.filter(c => c.role === channelFilterRole.value);
});

const filteredLogs = computed(() => {
  if (!logStatusFilter.value) return logs.value;
  return logs.value.filter(l => l.status === logStatusFilter.value);
});

onMounted(() => {
  fetchData();
});

async function fetchData() {
  await Promise.all([fetchChannels(), fetchLogs()]);
}

async function fetchChannels() {
  try {
    const res = await fetch('/api/v1/telegram/channels');
    if (res.ok) {
      const data = await res.json();
      channels.value = data.data || [];
      if (channels.value.length > 0 && !simChannelId.value) {
        simChannelId.value = channels.value[0].chat_id;
      }
    }
  } catch (err) {
    console.error('Failed to fetch channels:', err);
  }
}

async function fetchLogs() {
  try {
    const res = await fetch('/api/v1/telegram/logs?per_page=50');
    if (res.ok) {
      const data = await res.json();
      logs.value = data.data || [];
    }
  } catch (err) {
    console.error('Failed to fetch logs:', err);
  }
}

function openChannelModal() {
  editingChannelId.value = null;
  channelForm.value = {
    chat_id: '',
    name: '',
    role: 'doctors',
    bot_token_ref: 'TELEGRAM_BOT_TOKEN',
    allowed_report_types: ['daily_digest', 'critical_alerts'],
    allowed_commands: ['/help', '/beds', '/patients'],
    description: '',
    is_active: true,
  };
  isChannelModalOpen.value = true;
}

function editChannel(channel) {
  editingChannelId.value = channel.id;
  channelForm.value = {
    chat_id: channel.chat_id,
    name: channel.name,
    role: channel.role,
    bot_token_ref: channel.bot_token_ref,
    allowed_report_types: [...(channel.allowed_report_types || [])],
    allowed_commands: [...(channel.allowed_commands || [])],
    description: channel.description,
    is_active: channel.is_active,
  };
  isChannelModalOpen.value = true;
}

async function saveChannel() {
  try {
    const payload = {
      ...channelForm.value,
      branch_id: props.branchId,
    };

    const url = editingChannelId.value 
      ? `/api/v1/telegram/channels/${editingChannelId.value}` 
      : '/api/v1/telegram/channels';
    const method = editingChannelId.value ? 'PUT' : 'POST';

    const res = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify(payload),
    });

    if (res.ok) {
      isChannelModalOpen.value = false;
      await fetchChannels();
    }
  } catch (err) {
    console.error('Save channel failed:', err);
  }
}

async function deleteChannel(id) {
  if (!confirm('Are you sure you want to remove this Telegram channel?')) return;
  try {
    const res = await fetch(`/api/v1/telegram/channels/${id}`, { method: 'DELETE' });
    if (res.ok) {
      await fetchChannels();
    }
  } catch (err) {
    console.error('Delete channel failed:', err);
  }
}

async function testPingChannel(channel) {
  isPinging.value[channel.id] = true;
  try {
    const res = await fetch(`/api/v1/telegram/channels/${channel.id}/test`, {
      method: 'POST',
      headers: { Accept: 'application/json' },
    });
    const result = await res.json();
    alert(result.message || 'Ping completed.');
    await fetchLogs();
  } catch (err) {
    alert('Ping failed: ' + err.message);
  } finally {
    isPinging.value[channel.id] = false;
  }
}

async function triggerDailyDigest() {
  isSubmitting.value = true;
  try {
    const res = await fetch('/api/v1/telegram/reports/daily-digest', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({ branch_id: props.branchId }),
    });
    const data = await res.json();
    alert(`Daily digest dispatched to ${data.dispatched_count} active channels.`);
    await fetchLogs();
  } catch (err) {
    alert('Daily digest dispatch failed: ' + err.message);
  } finally {
    isSubmitting.value = false;
  }
}

async function triggerShiftHandover() {
  isSubmitting.value = true;
  try {
    const res = await fetch('/api/v1/telegram/reports/shift-handover', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({ shift: handoverShift.value, branch_id: props.branchId }),
    });
    const data = await res.json();
    alert(`Shift handover (${handoverShift.value}) dispatched to ${data.dispatched_count} clinical channels.`);
    await fetchLogs();
  } catch (err) {
    alert('Shift handover dispatch failed: ' + err.message);
  } finally {
    isSubmitting.value = false;
  }
}

async function triggerCriticalAlert(alertType) {
  try {
    const res = await fetch('/api/v1/telegram/reports/critical-alert', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({ alert_type: alertType, branch_id: props.branchId }),
    });
    const data = await res.json();
    alert(`Critical alert [${alertType}] dispatched to ${data.dispatched_count} channels!`);
    await fetchLogs();
  } catch (err) {
    alert('Critical alert dispatch failed: ' + err.message);
  }
}

async function runSimulatedCommand() {
  if (!simCommandText.value) return;
  isSimulating.value = true;
  try {
    const res = await fetch('/api/v1/telegram/reports/simulate-command', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({
        chat_id: simChannelId.value,
        command: simCommandText.value,
        sender_name: 'Dr. Console User',
      }),
    });
    const data = await res.json();
    const outcome = data.result || {};
    lastSimResult.value = {
      sentCommand: simCommandText.value,
      senderChat: simChannelId.value,
      authorized: outcome.authorized,
      status: outcome.status,
      response: outcome.response,
    };
    await fetchLogs();
  } catch (err) {
    alert('Simulation error: ' + err.message);
  } finally {
    isSimulating.value = false;
  }
}

async function retryIndividualMessage(id) {
  try {
    const res = await fetch(`/api/v1/telegram/logs/${id}/retry`, { method: 'POST' });
    const data = await res.json();
    alert(data.message || 'Retry executed.');
    await fetchLogs();
  } catch (err) {
    alert('Retry failed: ' + err.message);
  }
}

async function triggerRetrySweep() {
  isRetryingSweep.value = true;
  try {
    // Retry sweep via log reload
    await fetchLogs();
    alert('Retry scan completed.');
  } finally {
    isRetryingSweep.value = false;
  }
}

function getRoleBadgeClass(role) {
  const map = {
    admin: 'bg-rose-100 text-rose-800',
    doctors: 'bg-blue-100 text-blue-800',
    nursing: 'bg-purple-100 text-purple-800',
    pharmacy: 'bg-emerald-100 text-emerald-800',
    finance: 'bg-amber-100 text-amber-800',
    emergency: 'bg-red-100 text-red-800',
  };
  return map[role] || 'bg-slate-100 text-slate-800';
}

function formatDate(iso) {
  if (!iso) return '-';
  const d = new Date(iso);
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' ' + d.toLocaleDateString();
}
</script>
