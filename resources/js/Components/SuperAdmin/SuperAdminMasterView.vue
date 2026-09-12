<template>
  <div class="space-y-6">
    <!-- Top Platform Vendor Header & Sub-Navigation -->
    <div class="bg-slate-900 text-white p-4 sm:p-5 rounded-2xl shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4 border border-slate-800">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-indigo-500 to-cyan-400 flex items-center justify-center shadow-md shadow-indigo-500/30">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
          </svg>
        </div>
        <div>
          <div class="flex items-center gap-2">
            <h2 class="text-xl font-black tracking-tight text-white">Super Admin Control Plane</h2>
            <span class="px-2 py-0.5 bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-[10px] font-mono font-black uppercase rounded tracking-wider">
              Vendor Root
            </span>
          </div>
          <p class="text-xs text-slate-400">Multi-tenant hospital fleet orchestration, feature flag overrides, and system health.</p>
        </div>
      </div>

      <!-- Quick Navigation Tabs -->
      <div class="flex items-center gap-1.5 overflow-x-auto bg-slate-800/80 p-1.5 rounded-xl border border-slate-700/60">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="currentTab = tab.id"
          :class="currentTab === tab.id ? 'bg-indigo-600 text-white font-bold shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-700/60'"
          class="px-3 py-2 rounded-lg text-xs transition flex items-center gap-2 cursor-pointer whitespace-nowrap"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon" />
          </svg>
          <span>{{ tab.label }}</span>
        </button>
      </div>
    </div>

    <!-- Active View Area -->
    <div v-if="loading" class="p-12 text-center bg-white rounded-2xl border border-slate-200 shadow-sm">
      <div class="inline-block animate-spin w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full mb-3"></div>
      <p class="text-xs text-slate-500 font-mono">Synchronizing global platform telemetry...</p>
    </div>

    <div v-else>
      <!-- TAB 1: Tenants & Hospitals -->
      <div v-if="currentTab === 'tenants'" class="space-y-6">
        <!-- Global Usage Analytics Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
          <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-[10px] font-mono font-bold uppercase text-slate-400 block mb-1">Total Hospitals</span>
            <div class="text-2xl font-black text-slate-900">{{ analytics.total_tenants || 0 }}</div>
            <span class="text-[10px] text-emerald-600 font-semibold">{{ analytics.active_tenants || 0 }} Active</span>
          </div>
          <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-[10px] font-mono font-bold uppercase text-slate-400 block mb-1">Suspended</span>
            <div class="text-2xl font-black text-rose-600">{{ analytics.suspended_tenants || 0 }}</div>
            <span class="text-[10px] text-slate-400">Lockout enabled</span>
          </div>
          <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-[10px] font-mono font-bold uppercase text-slate-400 block mb-1">Global Users</span>
            <div class="text-2xl font-black text-slate-900">{{ analytics.total_users || 0 }}</div>
            <span class="text-[10px] text-slate-400">Staff & Clinicians</span>
          </div>
          <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-[10px] font-mono font-bold uppercase text-slate-400 block mb-1">Total Patients</span>
            <div class="text-2xl font-black text-indigo-600">{{ analytics.total_patients || 0 }}</div>
            <span class="text-[10px] text-slate-400">Cross-tenant</span>
          </div>
          <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-[10px] font-mono font-bold uppercase text-slate-400 block mb-1">Admissions</span>
            <div class="text-2xl font-black text-slate-900">{{ analytics.total_admissions || 0 }}</div>
            <span class="text-[10px] text-slate-400">IPD admissions</span>
          </div>
          <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-[10px] font-mono font-bold uppercase text-slate-400 block mb-1">Total Invoices</span>
            <div class="text-2xl font-black text-emerald-600">{{ analytics.total_invoices || 0 }}</div>
            <span class="text-[10px] text-slate-400">Billing records</span>
          </div>
          <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-[10px] font-mono font-bold uppercase text-slate-400 block mb-1">Est. Storage</span>
            <div class="text-2xl font-black text-slate-900">{{ analytics.estimated_storage_mb || 0 }} <span class="text-xs font-normal">MB</span></div>
            <span class="text-[10px] text-slate-400">DB & Media</span>
          </div>
        </div>

        <!-- Tenant Management Section -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <h3 class="text-base font-bold text-slate-900">Hospital Tenants & Client Accounts</h3>
              <p class="text-xs text-slate-500">Live operational status, plan tier enforcement, and support login actions.</p>
            </div>
            <button
              @click="showOnboardModal = true"
              class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer shadow-sm shadow-indigo-600/20"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              <span>Onboard New Hospital</span>
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
              <thead class="bg-slate-50 text-[10px] font-mono uppercase text-slate-500 border-b border-slate-100">
                <tr>
                  <th class="px-5 py-3">Hospital / Client</th>
                  <th class="px-4 py-3">Plan Tier</th>
                  <th class="px-4 py-3">Subscription</th>
                  <th class="px-4 py-3">Branches</th>
                  <th class="px-4 py-3">Users</th>
                  <th class="px-4 py-3">Onboarded</th>
                  <th class="px-5 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="tenant in tenants" :key="tenant.id" class="hover:bg-slate-50/70 transition">
                  <td class="px-5 py-3.5">
                    <div class="font-bold text-slate-900 text-sm">{{ tenant.name }}</div>
                    <div class="font-mono text-[10px] text-slate-400">Code: {{ tenant.code }} &bull; ID: {{ tenant.id.slice(0, 8) }}...</div>
                  </td>
                  <td class="px-4 py-3.5">
                    <span
                      class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider font-mono"
                      :class="{
                        'bg-purple-100 text-purple-800 border border-purple-200': tenant.plan_tier === 'enterprise',
                        'bg-blue-100 text-blue-800 border border-blue-200': tenant.plan_tier === 'regional',
                        'bg-emerald-100 text-emerald-800 border border-emerald-200': tenant.plan_tier === 'community' || !tenant.plan_tier,
                      }"
                    >
                      {{ tenant.plan_tier || 'community' }}
                    </span>
                  </td>
                  <td class="px-4 py-3.5">
                    <span
                      class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider inline-flex items-center gap-1.5"
                      :class="{
                        'bg-emerald-100 text-emerald-800': tenant.subscription_status === 'active',
                        'bg-rose-100 text-rose-800': tenant.subscription_status === 'suspended',
                        'bg-amber-100 text-amber-800': tenant.subscription_status === 'past_due',
                      }"
                    >
                      <span class="w-1.5 h-1.5 rounded-full" :class="tenant.subscription_status === 'active' ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                      {{ tenant.subscription_status || 'active' }}
                    </span>
                    <div v-if="tenant.suspension_reason" class="text-[10px] text-rose-600 mt-1 max-w-[200px] truncate" :title="tenant.suspension_reason">
                      {{ tenant.suspension_reason }}
                    </div>
                  </td>
                  <td class="px-4 py-3.5 font-bold text-slate-800">
                    {{ tenant.branches_count || (tenant.branches ? tenant.branches.length : 1) }}
                  </td>
                  <td class="px-4 py-3.5 font-bold text-slate-800">
                    {{ tenant.users_count || (tenant.users ? tenant.users.length : 0) }}
                  </td>
                  <td class="px-4 py-3.5 text-slate-500 font-mono text-[11px]">
                    {{ formatDate(tenant.created_at) }}
                  </td>
                  <td class="px-5 py-3.5 text-right whitespace-nowrap">
                    <div class="flex items-center justify-end gap-1.5">
                      <!-- Impersonate Support Button -->
                      <button
                        @click="openImpersonateModal(tenant)"
                        :disabled="tenant.subscription_status === 'suspended'"
                        class="px-2.5 py-1.5 bg-amber-500 hover:bg-amber-600 disabled:opacity-40 text-white rounded-lg text-xs font-semibold transition cursor-pointer flex items-center gap-1 shadow-sm"
                        title="Log in as Hospital Admin (Time-Limited Scoped Session)"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                        <span>Impersonate</span>
                      </button>

                      <!-- Feature Flags matrix shortcut -->
                      <button
                        @click="selectTenantForFlags(tenant.id)"
                        class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition cursor-pointer flex items-center gap-1"
                        title="Manage Feature Flags for this Tenant"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                        </svg>
                        <span>Flags</span>
                      </button>

                      <!-- Suspend / Reactivate Button -->
                      <button
                        v-if="tenant.subscription_status !== 'suspended'"
                        @click="openSuspendModal(tenant)"
                        class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-semibold transition cursor-pointer"
                      >
                        Suspend
                      </button>
                      <button
                        v-else
                        @click="reactivateTenant(tenant)"
                        class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-semibold transition cursor-pointer"
                      >
                        Reactivate
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB 2: Feature Flags Matrix -->
      <div v-else-if="currentTab === 'flags'" class="space-y-6">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <h3 class="text-base font-bold text-slate-900">Tenant Module & Feature Matrix</h3>
            <p class="text-xs text-slate-500">Toggle modules per hospital on/off without redeployment. Immediate route-level enforcement.</p>
          </div>

          <!-- Tenant Selector -->
          <div class="flex items-center gap-3">
            <label class="text-xs font-bold text-slate-600 uppercase font-mono">Select Hospital:</label>
            <select
              v-model="selectedTenantId"
              @change="loadTenantFlags"
              class="px-3.5 py-2 rounded-xl border border-slate-300 text-xs font-semibold text-slate-800 bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            >
              <option v-for="t in tenants" :key="t.id" :value="t.id">
                {{ t.name }} ({{ t.plan_tier || 'community' }})
              </option>
            </select>
          </div>
        </div>

        <div v-if="flagsLoading" class="p-8 text-center bg-white rounded-2xl border border-slate-200">
          <p class="text-xs text-slate-500">Loading module configuration...</p>
        </div>

        <div v-else class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <span class="text-xs font-bold text-slate-700">
              Module Flags for: <strong class="text-indigo-600 font-black">{{ currentSelectedTenantName }}</strong> (Plan: <span class="uppercase font-mono">{{ currentSelectedTenantPlan }}</span>)
            </span>
            <span class="text-[11px] text-slate-500">Toggles apply immediately via API middleware</span>
          </div>

          <div class="divide-y divide-slate-100">
            <div
              v-for="flag in tenantFlags"
              :key="flag.key"
              class="p-4.5 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/50 transition"
            >
              <div class="space-y-1 max-w-2xl">
                <div class="flex items-center gap-2.5">
                  <span class="font-bold text-slate-900 text-sm">{{ flag.name }}</span>
                  <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase font-bold"
                    :class="{
                      'bg-indigo-100 text-indigo-800': flag.category === 'clinical',
                      'bg-sky-100 text-sky-800': flag.category === 'integrations',
                      'bg-emerald-100 text-emerald-800': flag.category === 'intelligence',
                      'bg-slate-100 text-slate-700': flag.category === 'operations',
                    }"
                  >
                    {{ flag.category }}
                  </span>
                  <span class="font-mono text-[10px] text-slate-400">key: {{ flag.key }}</span>
                </div>
                <p class="text-xs text-slate-500">{{ flag.description }}</p>
                <div class="text-[11px] text-slate-400 flex items-center gap-2">
                  <span>Plan Defaults: <strong class="text-slate-600 uppercase">{{ flag.default_enabled_plans.join(', ') }}</strong></span>
                  <span>&bull;</span>
                  <span v-if="flag.has_tenant_override" class="text-amber-600 font-semibold">
                    Explicit Tenant Override: {{ flag.is_enabled ? 'ENABLED' : 'DISABLED' }}
                  </span>
                  <span v-else class="text-slate-400">
                    Inherited from Plan
                  </span>
                </div>
              </div>

              <div class="flex items-center gap-3">
                <span
                  class="px-2.5 py-1 rounded-full text-xs font-bold font-mono uppercase"
                  :class="flag.is_enabled ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500'"
                >
                  {{ flag.is_enabled ? 'Active' : 'Disabled' }}
                </span>

                <!-- Fast Toggle Switch -->
                <button
                  @click="toggleFeatureFlag(flag)"
                  :disabled="flagToggling === flag.key"
                  class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2"
                  :class="flag.is_enabled ? 'bg-indigo-600' : 'bg-slate-300'"
                >
                  <span
                    aria-hidden="true"
                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"
                    :class="flag.is_enabled ? 'translate-x-5' : 'translate-x-0'"
                  />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 3: Subscriptions & Billing Oversight -->
      <div v-else-if="currentTab === 'subscriptions'" class="space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
            <div>
              <h3 class="text-base font-bold text-slate-900">Hospital Subscriptions & Recurring Revenue</h3>
              <p class="text-xs text-slate-500">Plan tiers, contract cycles, past-due tracking, and account billing limits.</p>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
              <thead class="bg-slate-50 text-[10px] font-mono uppercase text-slate-500 border-b border-slate-100">
                <tr>
                  <th class="px-5 py-3">Tenant / Organization</th>
                  <th class="px-4 py-3">Plan Tier</th>
                  <th class="px-4 py-3">Status</th>
                  <th class="px-4 py-3">Rate</th>
                  <th class="px-4 py-3">Cycle</th>
                  <th class="px-4 py-3">Current Period</th>
                  <th class="px-5 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="sub in subscriptions" :key="sub.id" class="hover:bg-slate-50/70 transition">
                  <td class="px-5 py-3.5">
                    <div class="font-bold text-slate-900 text-sm">{{ sub.organization?.name || 'Unknown' }}</div>
                    <div class="text-[10px] text-slate-400 font-mono">ID: {{ sub.organization_id.slice(0, 8) }}...</div>
                  </td>
                  <td class="px-4 py-3.5">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase bg-purple-50 text-purple-700 border border-purple-200">
                      {{ sub.plan_tier }}
                    </span>
                  </td>
                  <td class="px-4 py-3.5">
                    <span
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase font-mono"
                      :class="{
                        'bg-emerald-100 text-emerald-800': sub.status === 'active',
                        'bg-amber-100 text-amber-800': sub.status === 'past_due',
                        'bg-rose-100 text-rose-800': sub.status === 'suspended' || sub.status === 'cancelled',
                      }"
                    >
                      {{ sub.status }}
                    </span>
                  </td>
                  <td class="px-4 py-3.5 font-bold text-slate-900 font-mono">
                    ${{ (sub.amount_cents / 100).toFixed(2) }} {{ sub.currency }}
                  </td>
                  <td class="px-4 py-3.5 uppercase font-mono text-slate-600">
                    {{ sub.billing_cycle }}
                  </td>
                  <td class="px-4 py-3.5 text-[11px] text-slate-500 font-mono">
                    {{ formatDate(sub.current_period_start) }} &rarr; {{ formatDate(sub.current_period_end) }}
                  </td>
                  <td class="px-5 py-3.5 text-right">
                    <button
                      class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition cursor-pointer"
                    >
                      Active Contract
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB 4: System Health & Queues -->
      <div v-else-if="currentTab === 'health'" class="space-y-6">
        <!-- Live Status Metric Badges -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
          <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
              <span class="text-[10px] font-mono font-bold uppercase text-slate-400 block">PostgreSQL Health</span>
              <div class="text-xl font-black text-slate-900 flex items-center gap-2 mt-1">
                <span class="w-3 h-3 rounded-full" :class="health.database?.connected ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500'"></span>
                {{ health.database?.connected ? 'Connected' : 'Disconnected' }}
              </div>
              <span class="text-xs text-slate-500 font-mono mt-1 block">Latency: {{ health.database?.latency_ms ?? '—' }} ms</span>
            </div>
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7zM4 11h16M4 15h16" />
              </svg>
            </div>
          </div>

          <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
              <span class="text-[10px] font-mono font-bold uppercase text-slate-400 block">Failed Queue Jobs</span>
              <div class="text-xl font-black mt-1" :class="(health.queues?.failed_jobs_count || 0) > 0 ? 'text-rose-600' : 'text-slate-900'">
                {{ health.queues?.failed_jobs_count || 0 }}
              </div>
              <span class="text-xs text-slate-500 font-mono mt-1 block">Background Workers</span>
            </div>
            <div class="p-3 bg-rose-50 text-rose-600 rounded-xl">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>

          <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
              <span class="text-[10px] font-mono font-bold uppercase text-slate-400 block">Failed Telegram Pushes</span>
              <div class="text-xl font-black mt-1" :class="(health.telegram_failures_count || 0) > 0 ? 'text-amber-600' : 'text-slate-900'">
                {{ health.telegram_failures_count || 0 }}
              </div>
              <span class="text-xs text-slate-500 font-mono mt-1 block">Bot Dispatches</span>
            </div>
            <div class="p-3 bg-sky-50 text-sky-600 rounded-xl">
              <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.52 2.77-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .27z"/>
              </svg>
            </div>
          </div>

          <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
              <span class="text-[10px] font-mono font-bold uppercase text-slate-400 block">Core Runtime</span>
              <div class="text-sm font-black text-slate-900 mt-1">PHP {{ health.php_version }}</div>
              <span class="text-xs text-slate-500 font-mono mt-1 block">Laravel {{ health.laravel_version }}</span>
            </div>
            <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Failed Queue Jobs Inspector -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
              <h4 class="text-sm font-bold text-slate-900">Failed Queue Jobs Inspector</h4>
              <p class="text-xs text-slate-500">Examine asynchronous processing crashes and trigger manual retries.</p>
            </div>
            <button
              @click="loadHealth"
              class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition cursor-pointer flex items-center gap-1.5"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              <span>Refresh Health</span>
            </button>
          </div>

          <div v-if="failedJobs.length === 0" class="p-8 text-center text-slate-400 text-xs">
            <svg class="w-8 h-8 text-emerald-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>All background queues are operating healthy. Zero failed jobs.</span>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
              <thead class="bg-slate-50 text-[10px] font-mono uppercase text-slate-500 border-b border-slate-100">
                <tr>
                  <th class="px-5 py-3">Job ID</th>
                  <th class="px-4 py-3">Queue</th>
                  <th class="px-4 py-3">Exception Details</th>
                  <th class="px-4 py-3">Failed At</th>
                  <th class="px-5 py-3 text-right">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="job in failedJobs" :key="job.id" class="hover:bg-slate-50 transition">
                  <td class="px-5 py-3 font-mono font-bold text-slate-900 text-[11px]">{{ job.id }}</td>
                  <td class="px-4 py-3 font-mono text-slate-700">{{ job.queue }}</td>
                  <td class="px-4 py-3 max-w-md">
                    <div class="text-[11px] font-mono text-rose-600 truncate" :title="job.exception">{{ job.exception }}</div>
                  </td>
                  <td class="px-4 py-3 text-slate-400 font-mono text-[11px]">{{ formatDate(job.failed_at) }}</td>
                  <td class="px-5 py-3 text-right whitespace-nowrap">
                    <button
                      @click="retryJob(job.id)"
                      :disabled="retryingJobId === job.id"
                      class="px-3 py-1 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-semibold transition cursor-pointer disabled:opacity-50"
                    >
                      {{ retryingJobId === job.id ? 'Retrying...' : 'Retry' }}
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB 5: Support Desk & Tickets -->
      <div v-else-if="currentTab === 'tickets'" class="space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
              <h3 class="text-base font-bold text-slate-900">Hospital Support Tickets</h3>
              <p class="text-xs text-slate-500">Cross-tenant issue logging, resolution tracking, and client escalations.</p>
            </div>
          </div>

          <div v-if="tickets.length === 0" class="p-8 text-center text-slate-400 text-xs">
            No support tickets logged.
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
              <thead class="bg-slate-50 text-[10px] font-mono uppercase text-slate-500 border-b border-slate-100">
                <tr>
                  <th class="px-5 py-3">Ticket #</th>
                  <th class="px-4 py-3">Hospital</th>
                  <th class="px-4 py-3">Subject</th>
                  <th class="px-4 py-3">Priority</th>
                  <th class="px-4 py-3">Status</th>
                  <th class="px-4 py-3">Submitted</th>
                  <th class="px-5 py-3 text-right">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="ticket in tickets" :key="ticket.id" class="hover:bg-slate-50 transition">
                  <td class="px-5 py-3 font-mono font-bold text-slate-900">#{{ ticket.ticket_number || ticket.id.slice(0, 8) }}</td>
                  <td class="px-4 py-3 font-semibold text-slate-800">{{ ticket.organization?.name || '—' }}</td>
                  <td class="px-4 py-3 max-w-sm">
                    <div class="font-bold text-slate-900">{{ ticket.title }}</div>
                    <div class="text-[11px] text-slate-500 truncate">{{ ticket.description }}</div>
                  </td>
                  <td class="px-4 py-3">
                    <span
                      class="px-2 py-0.5 rounded text-[10px] font-bold uppercase font-mono"
                      :class="{
                        'bg-rose-100 text-rose-800': ticket.priority === 'urgent' || ticket.priority === 'high',
                        'bg-blue-100 text-blue-800': ticket.priority === 'normal',
                        'bg-slate-100 text-slate-600': ticket.priority === 'low',
                      }"
                    >
                      {{ ticket.priority }}
                    </span>
                  </td>
                  <td class="px-4 py-3">
                    <span
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase font-mono"
                      :class="{
                        'bg-emerald-100 text-emerald-800': ticket.status === 'resolved' || ticket.status === 'closed',
                        'bg-blue-100 text-blue-800': ticket.status === 'in_progress',
                        'bg-amber-100 text-amber-800': ticket.status === 'open',
                      }"
                    >
                      {{ ticket.status }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-slate-400 font-mono text-[11px]">{{ formatDate(ticket.created_at) }}</td>
                  <td class="px-5 py-3 text-right">
                    <button
                      v-if="ticket.status !== 'resolved' && ticket.status !== 'closed'"
                      @click="resolveTicket(ticket)"
                      class="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold transition cursor-pointer"
                    >
                      Resolve
                    </button>
                    <span v-else class="text-[11px] text-slate-400 italic">Resolved</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB 6: Platform Broadcasts & Global Audit -->
      <div v-else-if="currentTab === 'broadcasts'" class="space-y-6">
        <!-- Broadcast Announcer -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-base font-bold text-slate-900">Broadcast Platform Announcement</h3>
              <p class="text-xs text-slate-500">Push high-priority maintenance windows, upgrades, or alerts to hospital admin dashboards.</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2 space-y-3">
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Headline</label>
                <input
                  v-model="announcementForm.title"
                  type="text"
                  placeholder="e.g. Scheduled Infrastructure Maintenance Window"
                  class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                />
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Notice Body</label>
                <textarea
                  v-model="announcementForm.content"
                  rows="3"
                  placeholder="Detailed maintenance timings, expected downtime, and failover instructions..."
                  class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                ></textarea>
              </div>
            </div>

            <div class="space-y-3">
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Severity</label>
                <select
                  v-model="announcementForm.severity"
                  class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                >
                  <option value="info">Info (Blue)</option>
                  <option value="warning">Warning (Amber)</option>
                  <option value="maintenance">Maintenance (Purple)</option>
                  <option value="critical">Critical (Red)</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Target Plan Tiers</label>
                <select
                  v-model="announcementForm.target_tier"
                  class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                >
                  <option value="*">All Plans (*)</option>
                  <option value="enterprise">Enterprise Only</option>
                  <option value="regional">Regional & Enterprise</option>
                  <option value="community">Community Only</option>
                </select>
              </div>
              <div class="pt-2">
                <button
                  @click="publishAnnouncement"
                  :disabled="publishingAnnouncement || !announcementForm.title"
                  class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 cursor-pointer shadow-md disabled:opacity-50"
                >
                  <span>{{ publishingAnnouncement ? 'Broadcasting...' : 'Publish Announcement' }}</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Global Cross-Tenant Audit Logs Stream -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
              <h4 class="text-sm font-bold text-slate-900">Cross-Tenant Audit Ledger</h4>
              <p class="text-xs text-slate-500">Vendor operator and hospital administrative action trail.</p>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
              <thead class="bg-slate-50 text-[10px] font-mono uppercase text-slate-500 border-b border-slate-100">
                <tr>
                  <th class="px-5 py-3">Timestamp</th>
                  <th class="px-4 py-3">Hospital</th>
                  <th class="px-4 py-3">User / Actor</th>
                  <th class="px-4 py-3">Action</th>
                  <th class="px-4 py-3">IP Address</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="log in auditLogs" :key="log.id" class="hover:bg-slate-50 transition">
                  <td class="px-5 py-3 font-mono text-[11px] text-slate-500">{{ formatDate(log.created_at) }}</td>
                  <td class="px-4 py-3 font-semibold text-slate-800">{{ log.organization?.name || 'Platform-Wide' }}</td>
                  <td class="px-4 py-3 font-mono text-slate-700">{{ log.user?.email || 'System' }}</td>
                  <td class="px-4 py-3">
                    <span class="px-2 py-0.5 rounded font-mono text-[10px] font-bold bg-slate-100 text-slate-800">
                      {{ log.action }}
                    </span>
                  </td>
                  <td class="px-4 py-3 font-mono text-slate-400 text-[11px]">{{ log.ip_address || '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL 1: Onboard Hospital Client -->
    <div v-if="showOnboardModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
      <div class="bg-white rounded-2xl shadow-2xl max-w-xl w-full border border-slate-200 overflow-hidden my-8">
        <div class="p-5 bg-gradient-to-r from-indigo-700 to-indigo-900 text-white flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
            <div>
              <h3 class="text-base font-black">Onboard Hospital Client</h3>
              <p class="text-xs text-indigo-200">Provisions isolated organization, primary campus, and client admin user.</p>
            </div>
          </div>
          <button @click="showOnboardModal = false" class="text-indigo-200 hover:text-white cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2 sm:col-span-1">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Hospital Name *</label>
              <input
                v-model="onboardForm.name"
                type="text"
                placeholder="e.g. Hope Valley Regional Hospital"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              />
            </div>
            <div class="col-span-2 sm:col-span-1">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tenant Code *</label>
              <input
                v-model="onboardForm.code"
                type="text"
                placeholder="HVRH"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs uppercase font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Subscription Plan Tier</label>
              <select
                v-model="onboardForm.plan_tier"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              >
                <option value="community">Community ($499/mo)</option>
                <option value="regional">Regional ($1,299/mo)</option>
                <option value="enterprise">Enterprise ($2,999/mo)</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Billing Cycle</label>
              <select
                v-model="onboardForm.billing_cycle"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              >
                <option value="monthly">Monthly</option>
                <option value="annual">Annual (15% Discount)</option>
              </select>
            </div>
          </div>

          <div class="border-t border-slate-100 pt-3">
            <h5 class="text-xs font-black uppercase tracking-wider text-slate-800 mb-2">Hospital Administrator Account</h5>
            <div class="space-y-3">
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Admin Full Name *</label>
                <input
                  v-model="onboardForm.admin_name"
                  type="text"
                  placeholder="e.g. Dr. Arthur Vance"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                />
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Admin Email *</label>
                  <input
                    v-model="onboardForm.admin_email"
                    type="email"
                    placeholder="admin@hopevalley.org"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                  />
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Temporary Password</label>
                  <input
                    v-model="onboardForm.admin_password"
                    type="password"
                    placeholder="WelcomeHospital2026!"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                  />
                </div>
              </div>
            </div>
          </div>

          <div class="border-t border-slate-100 pt-3">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Primary Campus / Branch Name</label>
            <input
              v-model="onboardForm.branch_name"
              type="text"
              placeholder="e.g. Hope Valley Main Campus"
              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            />
          </div>
        </div>

        <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
          <button
            @click="showOnboardModal = false"
            class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800 transition cursor-pointer"
          >
            Cancel
          </button>
          <button
            @click="submitOnboard"
            :disabled="onboarding || !onboardForm.name || !onboardForm.code || !onboardForm.admin_email"
            class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer shadow-md disabled:opacity-50"
          >
            <span>{{ onboarding ? 'Provisioning...' : 'Provision Tenant Account' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL 2: Support User Impersonation Confirmation -->
    <div v-if="showImpersonateModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
        <div class="p-5 bg-amber-600 text-white flex items-center justify-between">
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <h4 class="text-sm font-black">Support Impersonation Login</h4>
          </div>
          <button @click="showImpersonateModal = false" class="text-amber-200 hover:text-white cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-5 space-y-3 text-xs text-slate-600">
          <p>
            You are initiating a <strong>2-Hour Scoped Impersonation Session</strong> into
            <span class="font-bold text-slate-900">{{ impersonatingTenant?.name }}</span>.
          </p>
          <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-[11px] leading-relaxed">
            <strong>Security Notice:</strong> An immutable audit record will be logged with your super admin ID, target hospital, timestamp, and IP address. All actions taken while impersonating are logged.
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Reason / Support Ticket Reference *</label>
            <textarea
              v-model="impersonationReason"
              rows="2"
              placeholder="e.g. Diagnosing billing claim reconciliation error reported in ticket #1042."
              class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none"
            ></textarea>
          </div>
        </div>

        <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
          <button
            @click="showImpersonateModal = false"
            class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800 transition cursor-pointer"
          >
            Cancel
          </button>
          <button
            @click="confirmImpersonation"
            :disabled="impersonating || !impersonationReason"
            class="px-5 py-2.5 bg-amber-600 hover:bg-amber-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer shadow-md disabled:opacity-50"
          >
            <span>{{ impersonating ? 'Connecting...' : 'Authorize & Log In' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL 3: Suspend Tenant -->
    <div v-if="showSuspendModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
        <div class="p-5 bg-rose-600 text-white flex items-center justify-between">
          <h4 class="text-sm font-black">Suspend Hospital Tenant</h4>
          <button @click="showSuspendModal = false" class="text-rose-200 hover:text-white cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-5 space-y-3 text-xs text-slate-600">
          <p>
            Are you sure you want to suspend <span class="font-bold text-slate-900">{{ suspendingTenant?.name }}</span>?
          </p>
          <p class="text-rose-600 font-semibold text-[11px]">
            This will immediately revoke all active staff session tokens and block access to API endpoints and patient portals.
          </p>

          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Suspension Reason *</label>
            <textarea
              v-model="suspensionReason"
              rows="2"
              placeholder="e.g. Account overdue by 45+ days. Delinquent invoice #INV-9201."
              class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none"
            ></textarea>
          </div>
        </div>

        <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
          <button @click="showSuspendModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 cursor-pointer">Cancel</button>
          <button
            @click="confirmSuspension"
            :disabled="suspending || !suspensionReason"
            class="px-5 py-2.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold transition cursor-pointer shadow-md disabled:opacity-50"
          >
            <span>{{ suspending ? 'Suspending...' : 'Confirm Suspension' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const emit = defineEmits(['impersonation-started']);

const currentTab = ref('tenants');
const loading = ref(true);

const tabs = [
  { id: 'tenants', label: 'Tenants & Hospitals', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
  { id: 'flags', label: 'Feature Flags Matrix', icon: 'M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9' },
  { id: 'subscriptions', label: 'Subscriptions & Billing', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
  { id: 'health', label: 'System Health & Queues', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
  { id: 'tickets', label: 'Support Desk', icon: 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z' },
  { id: 'broadcasts', label: 'Platform Broadcasts & Audit', icon: 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z' },
];

const tenants = ref([]);
const analytics = ref({});
const subscriptions = ref([]);
const health = ref({});
const failedJobs = ref([]);
const tickets = ref([]);
const auditLogs = ref([]);

// Feature Flag Matrix State
const selectedTenantId = ref('');
const tenantFlags = ref([]);
const flagsLoading = ref(false);
const flagToggling = ref('');

// Modals State
const showOnboardModal = ref(false);
const onboarding = ref(false);
const onboardForm = ref({
  name: '',
  code: '',
  plan_tier: 'regional',
  billing_cycle: 'monthly',
  admin_name: '',
  admin_email: '',
  admin_password: 'WelcomeHospital2026!',
  branch_name: '',
  currency: 'USD',
});

const showImpersonateModal = ref(false);
const impersonatingTenant = ref(null);
const impersonationReason = ref('');
const impersonating = ref(false);

const showSuspendModal = ref(false);
const suspendingTenant = ref(null);
const suspensionReason = ref('');
const suspending = ref(false);

const retryingJobId = ref(null);

const announcementForm = ref({
  title: '',
  content: '',
  severity: 'maintenance',
  target_tier: '*',
});
const publishingAnnouncement = ref(false);

const currentSelectedTenantName = computed(() => {
  const t = tenants.value.find(x => x.id === selectedTenantId.value);
  return t ? t.name : 'Selected Hospital';
});

const currentSelectedTenantPlan = computed(() => {
  const t = tenants.value.find(x => x.id === selectedTenantId.value);
  return t ? (t.plan_tier || 'community') : 'community';
});

function formatDate(val) {
  if (!val) return '—';
  try {
    return new Date(val).toLocaleDateString(undefined, {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
    });
  } catch {
    return val;
  }
}

async function loadTenants() {
  try {
    const res = await fetch('/api/v1/super-admin/tenants', {
      headers: { 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.data) {
      tenants.value = json.data;
      analytics.value = json.analytics || {};
      if (tenants.value.length > 0 && !selectedTenantId.value) {
        selectedTenantId.value = tenants.value[0].id;
      }
    }
  } catch (err) {
    console.error('Failed to load tenants:', err);
  }
}

async function loadSubscriptions() {
  try {
    const res = await fetch('/api/v1/super-admin/subscriptions', {
      headers: { 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.data) {
      subscriptions.value = json.data;
    }
  } catch (err) {
    console.error('Failed to load subscriptions:', err);
  }
}

async function loadHealth() {
  try {
    const [hRes, jRes] = await Promise.all([
      fetch('/api/v1/super-admin/health', { headers: { 'Accept': 'application/json' } }),
      fetch('/api/v1/super-admin/health/failed-jobs', { headers: { 'Accept': 'application/json' } }),
    ]);
    const hJson = await hRes.json();
    const jJson = await jRes.json();
    health.value = hJson;
    failedJobs.value = jJson.data || [];
  } catch (err) {
    console.error('Failed to load system health:', err);
  }
}

async function loadTickets() {
  try {
    const res = await fetch('/api/v1/super-admin/tickets', {
      headers: { 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.data) {
      tickets.value = json.data;
    }
  } catch (err) {
    console.error('Failed to load tickets:', err);
  }
}

async function resolveTicket(ticket) {
  const notes = prompt('Enter resolution summary for this ticket:', 'Issue investigated and resolved by vendor platform engineering.');
  if (!notes) return;

  try {
    const res = await fetch(`/api/v1/super-admin/tickets/${ticket.id}`, {
      method: 'PUT',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({
        status: 'resolved',
        resolution_notes: notes,
      }),
    });
    if (res.ok) {
      await loadTickets();
    }
  } catch (err) {
    console.error('Failed to resolve ticket:', err);
  }
}

async function loadAuditLogs() {
  try {
    const res = await fetch('/api/v1/super-admin/audit-logs', {
      headers: { 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.data) {
      auditLogs.value = json.data;
    }
  } catch (err) {
    console.error('Failed to load audit logs:', err);
  }
}

async function loadTenantFlags() {
  if (!selectedTenantId.value) return;
  flagsLoading.value = true;
  try {
    const res = await fetch(`/api/v1/super-admin/tenants/${selectedTenantId.value}/feature-flags`, {
      headers: { 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.flags) {
      tenantFlags.value = json.flags;
    }
  } catch (err) {
    console.error('Failed to load tenant flags:', err);
  } finally {
    flagsLoading.value = false;
  }
}

function selectTenantForFlags(tenantId) {
  selectedTenantId.value = tenantId;
  currentTab.value = 'flags';
  loadTenantFlags();
}

async function toggleFeatureFlag(flag) {
  if (!selectedTenantId.value) return;
  flagToggling.value = flag.key;
  const nextState = !flag.is_enabled;

  try {
    const res = await fetch(`/api/v1/super-admin/tenants/${selectedTenantId.value}/feature-flags`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({
        feature_key: flag.key,
        is_enabled: nextState,
      })
    });
    if (res.ok) {
      await loadTenantFlags();
    }
  } catch (err) {
    console.error('Failed to toggle feature flag:', err);
  } finally {
    flagToggling.value = '';
  }
}

async function submitOnboard() {
  if (!onboardForm.value.name || !onboardForm.value.code || !onboardForm.value.admin_email) return;
  onboarding.value = true;

  try {
    const res = await fetch('/api/v1/super-admin/tenants', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify(onboardForm.value),
    });

    const json = await res.json();
    if (res.status === 201 && json.data) {
      showOnboardModal.value = false;
      await loadTenants();
      alert(`Tenant "${json.data.organization.name}" successfully onboarded!`);
    } else {
      alert(json.message || 'Onboarding error.');
    }
  } catch (err) {
    console.error('Failed to onboard tenant:', err);
  } finally {
    onboarding.value = false;
  }
}

function openImpersonateModal(tenant) {
  impersonatingTenant.value = tenant;
  impersonationReason.value = '';
  showImpersonateModal.value = true;
}

async function confirmImpersonation() {
  if (!impersonatingTenant.value || !impersonationReason.value) return;
  impersonating.value = true;

  try {
    const res = await fetch(`/api/v1/super-admin/tenants/${impersonatingTenant.value.id}/impersonate`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({
        reason: impersonationReason.value,
      }),
    });

    const json = await res.json();
    if (res.ok && json.data) {
      showImpersonateModal.value = false;

      // Store impersonation credentials and notify root application
      sessionStorage.setItem('hms_impersonation_id', json.data.impersonation_id);
      sessionStorage.setItem('hms_impersonation_token', json.data.token);
      sessionStorage.setItem('hms_impersonated_hospital', JSON.stringify(json.data.hospital));
      sessionStorage.setItem('hms_impersonation_expires', json.data.expires_at);

      emit('impersonation-started', json.data);
      alert(`Impersonation active for ${json.data.hospital.name}. You are now viewing as support.`);
      window.location.reload();
    } else {
      alert(json.message || 'Could not initiate impersonation.');
    }
  } catch (err) {
    console.error('Impersonation error:', err);
  } finally {
    impersonating.value = false;
  }
}

function openSuspendModal(tenant) {
  suspendingTenant.value = tenant;
  suspensionReason.value = '';
  showSuspendModal.value = true;
}

async function confirmSuspension() {
  if (!suspendingTenant.value || !suspensionReason.value) return;
  suspending.value = true;

  try {
    const res = await fetch(`/api/v1/super-admin/tenants/${suspendingTenant.value.id}/suspend`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({
        reason: suspensionReason.value,
      }),
    });

    if (res.ok) {
      showSuspendModal.value = false;
      await loadTenants();
    }
  } catch (err) {
    console.error('Suspension error:', err);
  } finally {
    suspending.value = false;
  }
}

async function reactivateTenant(tenant) {
  if (!confirm(`Reactivate tenant "${tenant.name}" and restore account access?`)) return;

  try {
    const res = await fetch(`/api/v1/super-admin/tenants/${tenant.id}/reactivate`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    });

    if (res.ok) {
      await loadTenants();
    }
  } catch (err) {
    console.error('Reactivation error:', err);
  }
}

async function retryJob(jobId) {
  retryingJobId.value = jobId;
  try {
    const res = await fetch(`/api/v1/super-admin/health/failed-jobs/${jobId}/retry`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    });

    if (res.ok) {
      await loadHealth();
    }
  } catch (err) {
    console.error('Failed to retry job:', err);
  } finally {
    retryingJobId.value = null;
  }
}

async function publishAnnouncement() {
  if (!announcementForm.value.title) return;
  publishingAnnouncement.value = true;

  try {
    const targetPlans = announcementForm.value.target_tier === '*' ? ['*'] : [announcementForm.value.target_tier];

    const res = await fetch('/api/v1/super-admin/announcements', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({
        title: announcementForm.value.title,
        content: announcementForm.value.content,
        severity: announcementForm.value.severity,
        target_plans: targetPlans,
        is_active: true,
      }),
    });

    if (res.ok) {
      announcementForm.value.title = '';
      announcementForm.value.content = '';
      alert('Platform announcement broadcasted successfully!');
    }
  } catch (err) {
    console.error('Broadcast failed:', err);
  } finally {
    publishingAnnouncement.value = false;
  }
}

onMounted(async () => {
  loading.value = true;
  await Promise.all([
    loadTenants(),
    loadSubscriptions(),
    loadHealth(),
    loadTickets(),
    loadAuditLogs(),
  ]);
  if (selectedTenantId.value) {
    await loadTenantFlags();
  }
  loading.value = false;
});
</script>
