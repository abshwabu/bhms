<template>
  <!-- Unauthenticated Staff Sign In Page -->
  <SignInView
    v-if="!currentUser"
    @login-success="handleLoginSuccess"
  />

  <!-- Authenticated Hospital Application Portal -->
  <div v-else class="h-screen bg-slate-100/70 font-sans text-slate-800 flex flex-col overflow-hidden">
    <!-- Persistent Support Impersonation Banner -->
    <div
      v-if="isImpersonating"
      class="bg-gradient-to-r from-amber-600 via-amber-700 to-amber-800 text-white px-6 py-2.5 shadow-md flex items-center justify-between gap-4 z-40 shrink-0 border-b border-amber-500/50"
    >
      <div class="flex items-center gap-3">
        <div class="w-7 h-7 rounded-lg bg-amber-500/40 flex items-center justify-center animate-pulse">
          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <div>
          <div class="text-xs font-black tracking-wide uppercase flex items-center gap-2">
            <span>Vendor Support Impersonation Active</span>
            <span class="px-2 py-0.5 bg-black/30 rounded text-[10px] font-mono font-normal">Audit Logged</span>
          </div>
          <div class="text-[11px] text-amber-100">
            Viewing tenant: <strong>{{ impersonatedHospitalName }}</strong> (Session active). All actions are recorded.
          </div>
        </div>
      </div>

      <button
        @click="exitImpersonation"
        :disabled="exitingImpersonation"
        class="px-4 py-1.5 bg-white hover:bg-amber-50 active:bg-amber-100 text-amber-900 rounded-lg text-xs font-black transition cursor-pointer shadow-sm disabled:opacity-50 flex items-center gap-1.5 shrink-0"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
        </svg>
        <span>{{ exitingImpersonation ? 'Exiting...' : 'Exit Impersonation' }}</span>
      </button>
    </div>

    <!-- Persistent Offline Clinical Mode Warning Banner -->
    <div
      v-if="!isOnline"
      class="bg-gradient-to-r from-amber-600 via-amber-700 to-amber-800 text-white px-6 py-2 shadow-md flex items-center justify-between gap-4 z-40 shrink-0 border-b border-amber-500/50"
    >
      <div class="flex items-center gap-3">
        <div class="w-7 h-7 rounded-lg bg-amber-500/40 flex items-center justify-center animate-pulse shrink-0">
          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 4.243a9 9 0 01-12.728 0m0 0l2.829-2.829m-2.829 2.829L3 21m2.829-5.657a5 5 0 010-7.072m0 0l2.829 2.829m4.243-4.243L12 3m0 0l-1.414 1.414" />
          </svg>
        </div>
        <div>
          <div class="text-xs font-black tracking-wide uppercase flex items-center gap-2">
            <span>Offline Clinical Mode Active</span>
            <span class="px-2 py-0.5 bg-black/30 rounded text-[10px] font-mono font-normal">Local Storage Enabled</span>
            <span v-if="pendingSyncCount > 0" class="px-2 py-0.5 bg-amber-900/60 rounded text-[10px] font-mono font-bold">{{ pendingSyncCount }} pending queued</span>
          </div>
          <div class="text-[11px] text-amber-100">
            Network disconnected. Clinical observations, vitals, and registrations are queued locally and will auto-sync on reconnect.
          </div>
        </div>
      </div>

      <button
        @click="showSyncDrawer = true"
        class="px-3.5 py-1.5 bg-white hover:bg-amber-50 active:bg-amber-100 text-amber-950 rounded-lg text-xs font-bold transition cursor-pointer shadow-sm shrink-0 flex items-center gap-1.5"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        <span>Sync Center ({{ pendingSyncCount }})</span>
      </button>
    </div>

    <!-- Application Workspace Wrapper -->
    <div class="flex-1 flex overflow-hidden min-h-0 relative">
      <!-- Mobile Backdrop Overlay -->
      <div
        v-if="isMobileSidebarOpen"
        @click="isMobileSidebarOpen = false"
        class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-40 lg:hidden transition-opacity"
      ></div>

      <!-- Collapsible & Responsive Sidebar Navigation -->
      <aside
        :class="[
          'fixed inset-y-0 left-0 z-50 lg:static flex flex-col justify-between shrink-0 bg-slate-900 text-white border-r border-slate-800 shadow-2xl transition-all duration-300 ease-in-out',
          isSidebarCollapsed ? 'w-20' : 'w-72',
          isMobileSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
        ]"
      >
        <!-- Brand Header (Pinned, Shrink-0) -->
        <div class="p-4 border-b border-slate-800/80 shrink-0">
          <!-- Expanded Header View -->
          <div v-if="!isSidebarCollapsed" class="space-y-3">
            <div class="flex items-center justify-between gap-2">
              <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center font-black text-lg text-white shadow-lg shadow-blue-500/25 shrink-0">
                  +
                </div>
                <div class="min-w-0">
                  <div class="font-bold text-sm tracking-tight leading-tight truncate text-white">
                    {{ currentOrganization?.name || 'Metro Health System' }}
                  </div>
                  <div class="text-[10px] text-blue-400 font-mono flex items-center gap-1.5 truncate">
                    <span>{{ currentOrganization?.code || 'MHS' }}</span>
                    <span>&bull;</span>
                    <span class="capitalize text-emerald-400">{{ currentOrganization?.plan_tier || 'Enterprise' }}</span>
                  </div>
                </div>
              </div>

              <!-- Mobile Close Button / Desktop Collapse Button -->
              <div class="flex items-center gap-1 shrink-0">
                <button
                  @click="isMobileSidebarOpen = false"
                  class="lg:hidden p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition cursor-pointer"
                  title="Close Menu"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
                <button
                  @click="toggleSidebarCollapse"
                  class="hidden lg:flex p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition cursor-pointer"
                  title="Collapse Sidebar"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                  </svg>
                </button>
              </div>
            </div>

            <!-- Active Facility Branch Selector / Badge -->
            <div class="p-2 rounded-xl bg-slate-800/70 border border-slate-700/50">
              <div class="flex items-center justify-between text-[10px] font-mono text-slate-400 mb-1">
                <span class="flex items-center gap-1.5">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                  <span>FACILITY BRANCH</span>
                </span>
                <span class="font-mono text-slate-300 font-bold">{{ currentBranch?.code || 'MAIN' }}</span>
              </div>
              <select
                v-if="accessibleBranches && accessibleBranches.length > 1"
                :value="activeBranchId"
                @change="handleBranchChange($event.target.value)"
                class="w-full mt-0.5 bg-slate-900/90 border border-slate-700 text-white rounded-lg px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none cursor-pointer"
              >
                <option v-for="br in accessibleBranches" :key="br.id" :value="br.id">
                  {{ br.name }} ({{ br.code }})
                </option>
              </select>
              <div v-else class="text-xs font-semibold text-slate-200 truncate mt-0.5">
                {{ currentBranch?.name || 'Metro General Hospital' }}
              </div>
            </div>

            <!-- Role Context Badge -->
            <div
              class="px-2.5 py-1.5 rounded-xl border flex items-center justify-between text-xs"
              :class="[roleBadgeColor.bg, roleBadgeColor.text, roleBadgeColor.border]"
            >
              <div class="flex items-center gap-1.5 text-[10px] font-mono uppercase tracking-wider">
                <span class="w-1.5 h-1.5 rounded-full" :class="roleBadgeColor.dot"></span>
                <span>ROLE</span>
              </div>
              <span class="font-bold text-[11px] truncate max-w-[130px] capitalize">
                {{ currentUser?.primary_role || 'Staff' }}
              </span>
            </div>
          </div>

          <!-- Collapsed Header View -->
          <div v-else class="flex flex-col items-center gap-3">
            <div
              class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center font-black text-xl text-white shadow-lg shadow-blue-500/25 shrink-0"
              :title="currentOrganization?.name || 'Metro Health System'"
            >
              +
            </div>
            <button
              @click="toggleSidebarCollapse"
              class="hidden lg:flex p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition cursor-pointer"
              title="Expand Sidebar"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Quick Filter Input (Expanded mode only) -->
        <div v-if="!isSidebarCollapsed" class="px-3 pt-3 pb-1 shrink-0">
          <div class="relative">
            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              v-model="searchNav"
              type="text"
              placeholder="Search modules..."
              class="w-full bg-slate-800/80 border border-slate-700/60 rounded-xl pl-8 pr-7 py-1.5 text-xs text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-blue-500/70 focus:border-blue-500/70"
            />
            <button
              v-if="searchNav"
              @click="searchNav = ''"
              class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white text-xs cursor-pointer"
              title="Clear search"
            >
              &times;
            </button>
          </div>
        </div>

        <!-- Scrollable Navigation Items -->
        <nav class="flex-1 min-h-0 overflow-y-auto px-2.5 py-2 space-y-4 custom-scrollbar-dark">
          <div
            v-for="group in filteredNavGroups"
            :key="group.id"
            class="space-y-1"
          >
            <!-- Group Heading (When Expanded) -->
            <div
              v-if="!isSidebarCollapsed"
              class="text-[10px] font-mono uppercase tracking-wider text-slate-400 px-3 py-1 font-bold flex items-center justify-between"
            >
              <span>{{ group.label }}</span>
            </div>
            <!-- Subdivider (When Collapsed) -->
            <div v-else class="h-px bg-slate-800 my-2 mx-1"></div>

            <!-- Group Item Buttons -->
            <button
              v-for="item in group.items"
              :key="item.id"
              @click="handleNavItemClick(item)"
              :title="item.label"
              :class="[
                'w-full flex items-center rounded-xl text-xs font-medium transition cursor-pointer group relative',
                isSidebarCollapsed ? 'justify-center p-2.5' : 'gap-3 px-3 py-2 text-left',
                isItemActive(item)
                  ? (item.activeClass || 'bg-blue-600 text-white shadow-md shadow-blue-600/30 font-semibold')
                  : 'text-slate-300 hover:bg-slate-800/80 hover:text-white'
              ]"
            >
              <!-- Icon -->
              <svg
                class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110"
                :class="isItemActive(item) ? 'text-white' : 'text-slate-400 group-hover:text-white'"
                :fill="item.icon === 'paper-airplane' ? 'currentColor' : 'none'"
                :stroke="item.icon === 'paper-airplane' ? 'none' : 'currentColor'"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  :d="navIcons[item.icon]"
                />
              </svg>

              <!-- Label and Badge (Expanded Mode) -->
              <template v-if="!isSidebarCollapsed">
                <span class="truncate flex-1">{{ item.label }}</span>
                <span
                  v-if="item.badge"
                  :class="['px-1.5 py-0.5 rounded text-[9px] font-mono font-bold shrink-0', item.badgeClass || 'bg-slate-800 text-slate-300']"
                >
                  {{ item.badge }}
                </span>
              </template>

              <!-- Active indicator dot for Collapsed Mode -->
              <span
                v-if="isSidebarCollapsed && isItemActive(item)"
                class="absolute right-1 top-1 w-2 h-2 rounded-full bg-white shadow-xs"
              ></span>
            </button>
          </div>

          <!-- Empty Search State -->
          <div
            v-if="filteredNavGroups.length === 0 && searchNav"
            class="text-center py-6 px-3 text-xs text-slate-400"
          >
            No modules match "<span class="text-slate-200">{{ searchNav }}</span>"
          </div>
        </nav>

        <!-- User Session Footer (Pinned, Shrink-0) -->
        <div class="p-3 border-t border-slate-800/80 bg-slate-950/60 shrink-0">
          <!-- Expanded Footer -->
          <div v-if="!isSidebarCollapsed" class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2.5 min-w-0">
              <div class="w-8 h-8 rounded-full bg-blue-600/30 border border-blue-500/40 flex items-center justify-center font-bold text-white text-xs shrink-0">
                {{ userInitials }}
              </div>
              <div class="min-w-0">
                <div class="text-white font-medium text-xs truncate">{{ currentUser?.name }}</div>
                <div class="text-[10px] text-slate-400 font-medium truncate capitalize">{{ currentUser?.primary_role }}</div>
              </div>
            </div>

            <div class="flex items-center gap-1 shrink-0">
              <button
                @click="showPersonaModal = true"
                title="Switch Role Persona"
                class="px-2 py-1 bg-slate-800 hover:bg-slate-700 active:bg-slate-600 text-slate-300 rounded-lg text-[10px] font-mono transition cursor-pointer flex items-center gap-1"
              >
                <span>⇄</span>
                <span class="hidden xl:inline">Role</span>
              </button>
              <button
                @click="handleSignOut"
                title="Sign Out"
                class="p-1.5 hover:bg-rose-950/80 hover:text-rose-400 text-slate-400 rounded-lg text-xs transition cursor-pointer"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Collapsed Footer -->
          <div v-else class="flex flex-col items-center gap-2">
            <div
              class="w-8 h-8 rounded-full bg-blue-600/30 border border-blue-500/40 flex items-center justify-center font-bold text-white text-xs shrink-0"
              :title="currentUser?.name + ' (' + currentUser?.primary_role + ')'"
            >
              {{ userInitials }}
            </div>
            <div class="flex items-center gap-1">
              <button
                @click="showPersonaModal = true"
                title="Switch Role Persona"
                class="p-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-xs transition cursor-pointer"
              >
                ⇄
              </button>
              <button
                @click="handleSignOut"
                title="Sign Out"
                class="p-1.5 hover:bg-rose-950/80 hover:text-rose-400 text-slate-400 rounded-lg text-xs transition cursor-pointer"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </aside>

      <!-- Right Main Application Container -->
      <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-slate-100/60">
        <!-- Top Application Header Bar -->
        <header class="h-16 bg-white border-b border-slate-200/80 px-4 sm:px-6 flex items-center justify-between shrink-0 z-10 shadow-xs">
          <!-- Left: Mobile trigger, Desktop collapse toggle & Breadcrumbs -->
          <div class="flex items-center gap-3 min-w-0">
            <!-- Mobile drawer button -->
            <button
              @click="isMobileSidebarOpen = true"
              class="lg:hidden p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition cursor-pointer"
              title="Open Navigation Menu"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>

            <!-- Desktop sidebar collapse button -->
            <button
              @click="toggleSidebarCollapse"
              class="hidden lg:flex p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition cursor-pointer"
              :title="isSidebarCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar'"
            >
              <svg class="w-5 h-5 transition-transform" :class="isSidebarCollapsed ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
              </svg>
            </button>

            <div class="h-5 w-px bg-slate-200 hidden sm:block"></div>

            <!-- Breadcrumbs & Active Module Title -->
            <div class="min-w-0">
              <div class="flex items-center gap-1.5 text-[11px] font-medium text-slate-400 truncate">
                <span>HMS Enterprise</span>
                <span>/</span>
                <span class="text-slate-500">{{ currentViewMeta.category }}</span>
              </div>
              <h1 class="text-sm sm:text-base font-bold text-slate-900 tracking-tight truncate leading-tight">
                {{ currentViewMeta.title }}
              </h1>
            </div>
          </div>

          <!-- Right: Facility branch pill, Role badge, Quick Register button, Role switcher -->
          <div class="flex items-center gap-2 sm:gap-3 shrink-0">
            <!-- Branch Pill -->
            <div class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs">
              <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
              <span class="font-medium text-slate-600 truncate max-w-[160px]">{{ currentBranch?.name || 'Main Campus' }}</span>
              <span class="px-1.5 py-0.5 rounded bg-slate-200 text-slate-700 font-mono text-[10px] font-bold">{{ currentBranch?.code || 'MAIN' }}</span>
            </div>

            <!-- Role Pill -->
            <div
              class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-semibold"
              :class="[roleBadgeColor.bg, roleBadgeColor.text, roleBadgeColor.border]"
            >
              <span class="w-1.5 h-1.5 rounded-full" :class="roleBadgeColor.dot"></span>
              <span class="capitalize">{{ currentUser?.primary_role || 'Staff' }}</span>
            </div>

            <!-- Quick Register Walk-In Patient Button (for receptionist, nurse, admin) -->
            <button
              v-if="isReceptionist || isNurse || isHospitalAdmin"
              @click="openRegistrationModal"
              class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-bold transition cursor-pointer shadow-sm shadow-blue-500/20"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              <span class="hidden sm:inline">New Patient</span>
            </button>

            <!-- Network / Outbox Status Badge -->
            <button
              @click="showSyncDrawer = true"
              :title="!isOnline ? 'Offline: Clinical changes queued locally' : (pendingSyncCount > 0 ? `${pendingSyncCount} pending changes queued` : 'Online: Connected to Hospital Server')"
              class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl border text-xs font-semibold transition cursor-pointer"
              :class="[
                !isOnline
                  ? 'bg-amber-500/10 border-amber-500/40 text-amber-700 hover:bg-amber-500/20'
                  : (pendingSyncCount > 0
                      ? 'bg-sky-500/10 border-sky-500/30 text-sky-700 hover:bg-sky-500/20'
                      : 'bg-emerald-500/10 border-emerald-500/20 text-emerald-700 hover:bg-emerald-500/20')
              ]"
            >
              <span
                class="w-2 h-2 rounded-full"
                :class="[
                  !isOnline
                    ? 'bg-amber-500 animate-pulse'
                    : (isSyncing
                        ? 'bg-sky-500 animate-ping'
                        : (pendingSyncCount > 0 ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500'))
                ]"
              ></span>
              <span class="text-[11px] font-mono font-bold">
                {{ !isOnline ? 'Offline' : (isSyncing ? 'Syncing...' : (pendingSyncCount > 0 ? `${pendingSyncCount} Queued` : 'Live')) }}
              </span>
            </button>

            <!-- Quick Role Persona Switcher (Header) -->
            <button
              @click="showPersonaModal = true"
              title="Switch Persona Role"
              class="p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200 transition cursor-pointer flex items-center gap-1 text-xs font-medium"
            >
              <svg class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
              </svg>
              <span class="hidden md:inline font-mono text-[11px]">Role</span>
            </button>
          </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto overflow-y-auto w-full custom-scrollbar">
      <!-- Access Restricted Alert if module is unauthorized for active role -->
      <div v-if="!canAccessView(currentView)" class="max-w-xl mx-auto my-16 bg-white border border-amber-200 rounded-3xl p-8 shadow-sm text-center space-y-4">
        <div class="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center mx-auto text-amber-600 border border-amber-200">
          <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <div>
          <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-amber-100 text-amber-800 uppercase tracking-wider">
            RBAC Access Guard
          </span>
          <h2 class="text-xl font-bold text-slate-900 mt-2">Access Restricted</h2>
          <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
            Your current persona (<strong class="text-slate-800">{{ currentUser?.primary_role }}</strong>) does not have authorization to access the <span class="font-mono font-bold text-slate-700 uppercase">{{ currentView.replace('_', ' ') }}</span> module.
          </p>
        </div>
        <div class="pt-2 flex items-center justify-center gap-3">
          <button
            @click="currentView = getDefaultViewForRole(currentUser)"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl transition cursor-pointer shadow-sm"
          >
            Return to My Workspace
          </button>
          <button
            @click="showPersonaModal = true"
            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs rounded-xl transition cursor-pointer"
          >
            Switch Persona ⇄
          </button>
        </div>
      </div>

      <template v-else>
      <!-- Search Screen -->
      <PatientSearchScreen
        v-if="currentView === 'search'"
        ref="searchScreenRef"
        :branch-id="activeBranchId"
        @open-registration="openRegistrationModal"
        @select-patient="handleSelectPatient"
      />

      <!-- Profile View -->
      <PatientProfileView
        v-else-if="currentView === 'profile' && selectedPatient"
        :patient="selectedPatient"
        :branch-id="activeBranchId"
        @back="currentView = 'search'"
      />

      <!-- Appointment Booking & Calendar -->
      <BookingCalendar
        v-else-if="currentView === 'booking'"
        :branch-id="activeBranchId"
      />

      <!-- OPD Queue & Waiting Room System -->
      <QueueDashboard
        v-else-if="currentView === 'queue'"
        :branch-id="activeBranchId"
      />

      <!-- SOAP Clinical Consultation Notes -->
      <SoapNoteEditor
        v-else-if="currentView === 'soap'"
        :branch-id="activeBranchId"
      />

      <!-- Referral Manager -->
      <ReferralManager
        v-else-if="currentView === 'referrals'"
        :branch-id="activeBranchId"
      />

      <!-- Inpatient: Bed Map & Ward Allocation -->
      <BedMapVisualView
        v-else-if="currentView === 'bed_map'"
        :branch-id="activeBranchId"
      />

      <!-- Inpatient: Nursing Station -->
      <NursingDashboard
        v-else-if="currentView === 'nursing'"
        :branch-id="activeBranchId"
      />

      <!-- Inpatient: Discharge Summary Generator -->
      <DischargeSummaryGenerator
        v-else-if="currentView === 'discharge'"
        :branch-id="activeBranchId"
      />

      <!-- Inpatient: Bed Occupancy & ALOS Analytics -->
      <IpdAnalyticsView
        v-else-if="currentView === 'ipd_analytics'"
        :branch-id="activeBranchId"
      />

      <!-- Doctor & Clinical: Personal Dashboard -->
      <DoctorDashboard
        v-else-if="currentView === 'doctor_dashboard'"
        :branch-id="activeBranchId"
        @open-patient-ehr="handleOpenPatientEhr"
        @open-prescription-writer="handleOpenPrescriptionWriter"
        @open-order-modal="handleOpenOrderModal"
      />

      <!-- Doctor & Clinical: Longitudinal EHR Records -->
      <EhrTimelineView
        v-else-if="currentView === 'ehr' && selectedPatient"
        :patient="selectedPatient"
        :branch-id="activeBranchId"
        @back="currentView = 'doctor_dashboard'"
        @open-prescription-writer="handleOpenPrescriptionWriter"
        @open-order-modal="handleOpenOrderModal"
      />

      <!-- Doctor & Clinical: E-Prescriptions & CDS Warnings -->
      <PrescriptionWriter
        v-else-if="currentView === 'prescriptions' && selectedPatient"
        :patient="selectedPatient"
        :branch-id="activeBranchId"
        @back="currentView = 'ehr'"
        @prescription-created="handlePrescriptionCreated"
      />

      <!-- Diagnostic Laboratory: Master Bench & Samples -->
      <LabWorklist
        v-else-if="currentView === 'laboratory'"
        :branch-id="activeBranchId"
      />

      <!-- Radiology Information System (RIS): Worklist, Scheduling & Viewer -->
      <ImagingWorklist
        v-else-if="currentView === 'radiology'"
        :branch-id="activeBranchId"
      />

      <!-- Pharmacy: Dispensing, Inventory & FEFO Expiry Tracking -->
      <PharmacyMasterView
        v-else-if="currentView === 'pharmacy'"
        :branch-id="activeBranchId"
      />

      <!-- Billing & Finance: Invoicing, Payments, Claims & Revenue Analytics -->
      <BillingMasterView
        v-else-if="currentView === 'billing'"
        :branch-id="activeBranchId"
      />

      <!-- Materials & Asset Management: Stock, POs & Equipment Maintenance -->
      <InventoryMasterView
        v-else-if="currentView === 'inventory'"
        :branch-id="activeBranchId"
      />

      <!-- Human Resources & Staff Management: Rosters, Attendance, Leaves & Credentials -->
      <HrMasterView
        v-else-if="currentView === 'hr'"
        :branch-id="activeBranchId"
      />

      <!-- Emergency & Ambulance: ER Triage, Resuscitation Queue & Ambulance CAD -->
      <EmergencyMasterView
        v-else-if="currentView === 'emergency'"
        :branch-id="activeBranchId"
      />

      <!-- Reports & Analytics: Executive KPIs, Departments, Doctors & Custom Builder -->
      <ReportsMasterView
        v-else-if="currentView === 'reports'"
        :branch-id="activeBranchId"
      />

      <!-- Governance & Security: HIPAA Safeguards, RBAC, Audit Trails & Consents -->
      <ComplianceMasterView
        v-else-if="currentView === 'compliance'"
        :branch-id="activeBranchId"
      />

      <!-- System Administration: Multi-Branch, Master Data, Notifications & Disaster Recovery -->
      <AdministrationMasterView
        v-else-if="currentView === 'admin'"
        :branch-id="activeBranchId"
      />

      <!-- Telegram Reporting & Alert Engine -->
      <TelegramManagementView
        v-else-if="currentView === 'telegram'"
        :branch-id="activeBranchId"
      />

      <!-- Super Admin Platform Control Plane -->
      <SuperAdminMasterView
        v-else-if="currentView === 'super_admin'"
      />

      <!-- Patient Portal Self-Service View -->
      <div v-else-if="currentView === 'portal'" class="space-y-6">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
          <h2 class="text-xl font-bold text-slate-900">Patient-Facing Portal Preview</h2>
          <p class="text-sm text-slate-500 mt-1">Simulates what patients see when logging into their personal health dashboard.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm space-y-2">
            <h3 class="font-bold text-slate-800">My Appointments</h3>
            <p class="text-xs text-slate-500">Upcoming specialist consults and queue position.</p>
            <div class="pt-4 text-xs font-semibold text-blue-600">Active Appointments &rarr;</div>
          </div>
          <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm space-y-2">
            <h3 class="font-bold text-slate-800">My Medical Records</h3>
            <p class="text-xs text-slate-500">Inpatient summaries, SOAP notes, and lab results.</p>
            <div class="pt-4 text-xs font-semibold text-blue-600">View Diagnostic History &rarr;</div>
          </div>
          <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm space-y-2">
            <h3 class="font-bold text-slate-800">Billing & Insurance</h3>
            <p class="text-xs text-slate-500">Inpatient room rates, copays, and claims.</p>
            <button @click="currentView = 'billing'" class="pt-4 text-xs font-semibold text-blue-600 hover:underline cursor-pointer block text-left">
              View Invoices &rarr;
            </button>
          </div>
        </div>
      </div>
      </template>
    </main>
    </div>

    <!-- Registration Modal -->
    <RegistrationModal
      :is-open="isRegistrationModalOpen"
      :branch-id="activeBranchId"
      @close="isRegistrationModalOpen = false"
      @patient-created="handlePatientCreated"
    />

    <!-- Diagnostic Order Entry Modal (Lab & Radiology) -->
    <OrderEntryModal
      v-if="selectedPatient"
      :is-open="isOrderModalOpen"
      :initial-type="orderModalType"
      :patient="selectedPatient"
      :branch-id="activeBranchId"
      @close="isOrderModalOpen = false"
      @order-created="handleOrderCreated"
    />

    <!-- Quick Persona Switcher Modal -->
    <div
      v-if="showPersonaModal"
      class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 overflow-y-auto"
      @click.self="showPersonaModal = false"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl relative text-slate-100">
        <div class="flex items-start justify-between mb-6 pb-4 border-b border-slate-800">
          <div>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-sky-500/10 text-sky-400 border border-sky-500/20 uppercase tracking-wider mb-2">
              Instant RBAC Persona Simulator
            </span>
            <h2 class="text-xl font-bold text-white">Switch Role Persona</h2>
            <p class="text-xs text-slate-400 mt-1">
              Test role-based access control, hospital multi-tenant isolation, and tailored clinical workspaces.
            </p>
          </div>
          <button
            @click="showPersonaModal = false"
            class="text-slate-400 hover:text-white p-2 rounded-xl hover:bg-slate-800 transition cursor-pointer"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Persona Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[60vh] overflow-y-auto pr-1 custom-scrollbar-dark">
          <button
            v-for="persona in demoPersonas"
            :key="persona.role_key"
            @click="quickSwitchPersona(persona)"
            :disabled="switchingPersona === persona.role_key"
            :class="currentUser?.roles?.includes(persona.role_key) ? 'border-sky-500/80 bg-sky-950/20' : 'border-slate-800 bg-slate-950/50 hover:border-slate-700 hover:bg-slate-800/40'"
            class="p-4 rounded-2xl border text-left transition cursor-pointer flex flex-col justify-between group relative disabled:opacity-50"
          >
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-[10px] font-mono font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-slate-800 text-slate-300">
                  {{ persona.role_label }}
                </span>
                <span
                  v-if="currentUser?.roles?.includes(persona.role_key)"
                  class="text-[10px] font-bold text-sky-400 flex items-center gap-1"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span> Active
                </span>
              </div>
              <div>
                <div class="text-sm font-bold text-white group-hover:text-sky-300 transition">{{ persona.name }}</div>
                <div class="text-[11px] text-slate-500 font-mono">{{ persona.email }}</div>
              </div>
              <div class="flex flex-wrap gap-1 pt-1">
                <span
                  v-for="(scope, idx) in (persona.accessible_scopes || []).slice(0, 3)"
                  :key="idx"
                  class="text-[9px] bg-slate-900 border border-slate-800 text-slate-400 px-1.5 py-0.5 rounded"
                >
                  {{ scope }}
                </span>
              </div>
            </div>
            <div class="mt-3 pt-2 border-t border-slate-800/60 flex items-center justify-between text-[11px] text-sky-400 font-semibold">
              <span>{{ switchingPersona === persona.role_key ? 'Switching...' : 'Select Role' }}</span>
              <span>&rarr;</span>
            </div>
          </button>
        </div>
      </div>
    </div>

    <!-- Offline Sync Center Modal -->
    <div
      v-if="showSyncDrawer"
      class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 overflow-y-auto"
      @click.self="showSyncDrawer = false"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl relative text-slate-100 flex flex-col max-h-[85vh]">
        <!-- Header -->
        <div class="flex items-start justify-between pb-4 border-b border-slate-800 shrink-0">
          <div>
            <div class="flex items-center gap-2 mb-1.5">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-sky-500/10 text-sky-400 border border-sky-500/20 uppercase tracking-wider">
                Mutation Outbox
              </span>
              <span
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-bold"
                :class="isOnline ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30'"
              >
                <span class="w-1.5 h-1.5 rounded-full" :class="isOnline ? 'bg-emerald-400' : 'bg-amber-400 animate-pulse'"></span>
                {{ isOnline ? 'Network Online' : 'Network Offline' }}
              </span>
            </div>
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
              <span>Offline Sync Center</span>
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">
              All clinical records queued offline are stored in IndexedDB and synced sequentially.
            </p>
          </div>
          <button
            @click="showSyncDrawer = false"
            class="text-slate-400 hover:text-white p-2 rounded-xl hover:bg-slate-800 transition cursor-pointer"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Status Bar & Actions -->
        <div class="py-4 border-b border-slate-800/80 flex flex-wrap items-center justify-between gap-3 shrink-0">
          <div class="space-y-0.5">
            <div class="text-xs text-slate-300 flex items-center gap-2">
              <span>Pending Outbox: <strong class="text-white font-mono">{{ pendingSyncCount }}</strong> record(s)</span>
              <span v-if="lastSyncTime" class="text-slate-500">&bull; Last synced at {{ lastSyncTime }}</span>
            </div>
            <div v-if="syncStatusMessage" class="text-[11px] text-sky-400 font-mono">
              {{ syncStatusMessage }}
            </div>
          </div>

          <div class="flex items-center gap-2">
            <button
              @click="refreshPendingCount"
              class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 active:bg-slate-600 text-slate-300 text-xs font-semibold transition cursor-pointer flex items-center gap-1.5"
              title="Refresh queue"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              <span>Refresh</span>
            </button>
            <button
              @click="syncOutbox"
              :disabled="!isOnline || isSyncing || pendingSyncCount === 0"
              class="px-4 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white text-xs font-bold transition cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-1.5 shadow-sm shadow-blue-600/30"
            >
              <svg
                v-if="isSyncing"
                class="w-3.5 h-3.5 animate-spin"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg
                v-else
                class="w-3.5 h-3.5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
              </svg>
              <span>{{ isSyncing ? 'Syncing...' : 'Sync Now' }}</span>
            </button>
          </div>
        </div>

        <!-- Pending Items List -->
        <div class="flex-1 overflow-y-auto py-4 space-y-2.5 custom-scrollbar-dark min-h-[160px]">
          <div v-if="pendingItems.length === 0" class="py-12 text-center space-y-3">
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center justify-center mx-auto">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <div>
              <div class="text-sm font-bold text-slate-200">All Changes Synchronized</div>
              <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1">
                Your local clinical workspace is up to date with the hospital database. Any new offline inputs will queue here automatically.
              </p>
            </div>
          </div>

          <div
            v-for="item in pendingItems"
            :key="item.id"
            class="p-3.5 rounded-2xl bg-slate-950/70 border border-slate-800/80 hover:border-slate-700/80 transition flex items-center justify-between gap-3"
          >
            <div class="min-w-0 flex-1 space-y-1">
              <div class="flex items-center gap-2">
                <span
                  class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold"
                  :class="{
                    'bg-blue-500/20 text-blue-400 border border-blue-500/30': item.method === 'POST',
                    'bg-amber-500/20 text-amber-400 border border-amber-500/30': item.method === 'PUT' || item.method === 'PATCH',
                    'bg-rose-500/20 text-rose-400 border border-rose-500/30': item.method === 'DELETE',
                  }"
                >
                  {{ item.method }}
                </span>
                <span class="text-xs font-semibold text-slate-200 truncate">{{ item.summary }}</span>
              </div>
              <div class="flex items-center gap-2 text-[10px] text-slate-500 font-mono truncate">
                <span>{{ formatMutationTime(item.timestamp) }}</span>
                <span>&bull;</span>
                <span class="truncate">{{ item.url }}</span>
              </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
              <span class="px-2 py-0.5 bg-amber-500/10 text-amber-400 border border-amber-500/20 rounded-full text-[10px] font-mono">
                Queued
              </span>
              <button
                @click="discardPendingItem(item.id)"
                class="p-1 text-slate-500 hover:text-rose-400 hover:bg-rose-950/40 rounded-lg transition cursor-pointer"
                title="Discard pending change"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Footer Notice -->
        <div class="pt-3 border-t border-slate-800 text-[11px] text-slate-500 flex items-center justify-between shrink-0">
          <span>Storage Engine: IndexedDB (hms_offline_db)</span>
          <span class="font-mono">Auto-sync on reconnect: ON</span>
        </div>
      </div>
    </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import SignInView from '../Auth/SignInView.vue';
import PatientSearchScreen from './PatientSearchScreen.vue';
import PatientProfileView from './PatientProfileView.vue';
import RegistrationModal from './RegistrationModal.vue';
import BookingCalendar from '../OPD/BookingCalendar.vue';
import QueueDashboard from '../OPD/QueueDashboard.vue';
import SoapNoteEditor from '../OPD/SoapNoteEditor.vue';
import ReferralManager from '../OPD/ReferralManager.vue';
import BedMapVisualView from '../IPD/BedMapVisualView.vue';
import NursingDashboard from '../IPD/NursingDashboard.vue';
import DischargeSummaryGenerator from '../IPD/DischargeSummaryGenerator.vue';
import IpdAnalyticsView from '../IPD/IpdAnalyticsView.vue';
import DoctorDashboard from '../Clinical/DoctorDashboard.vue';
import EhrTimelineView from '../Clinical/EhrTimelineView.vue';
import PrescriptionWriter from '../Clinical/PrescriptionWriter.vue';
import OrderEntryModal from '../Clinical/OrderEntryModal.vue';
import LabWorklist from '../Laboratory/LabWorklist.vue';
import ImagingWorklist from '../Radiology/ImagingWorklist.vue';
import PharmacyMasterView from '../Pharmacy/PharmacyMasterView.vue';
import BillingMasterView from '../Billing/BillingMasterView.vue';
import InventoryMasterView from '../Inventory/InventoryMasterView.vue';
import HrMasterView from '../HR/HrMasterView.vue';
import EmergencyMasterView from '../Emergency/EmergencyMasterView.vue';
import ReportsMasterView from '../Reports/ReportsMasterView.vue';
import ComplianceMasterView from '../Compliance/ComplianceMasterView.vue';
import AdministrationMasterView from '../Administration/AdministrationMasterView.vue';
import TelegramManagementView from '../Telegram/TelegramManagementView.vue';
import SuperAdminMasterView from '../SuperAdmin/SuperAdminMasterView.vue';
import {
  isOnline,
  pendingSyncCount,
  isSyncing,
  lastSyncTime,
  syncStatusMessage,
  pendingItems,
  syncOutbox,
  discardPendingItem,
  refreshPendingCount,
} from '../../offline/syncManager';

// Synchronous Session & State Rehydration (prevents flash & preserves active module on refresh)
let initialSession = null;
try {
  const raw = localStorage.getItem('hms_portal_session');
  if (raw) {
    initialSession = JSON.parse(raw);
  }
} catch {}

let initialPatient = null;
try {
  const rawPatient = localStorage.getItem('hms_selected_patient');
  if (rawPatient) {
    initialPatient = JSON.parse(rawPatient);
  }
} catch {}

// Authentication & Tenant Context State
const initialToken = initialSession?.token || localStorage.getItem('hms_auth_token') || sessionStorage.getItem('hms_auth_token') || null;
const currentUser = ref((initialToken && initialSession?.user) ? initialSession.user : null);
const currentOrganization = ref(initialSession?.organization || null);
const currentBranch = ref(initialSession?.default_branch || null);
const accessibleBranches = ref(initialSession?.accessible_branches || []);
const activeBranchId = ref(initialSession?.default_branch?.id || initialSession?.accessible_branches?.[0]?.id || '84d7387e-7b2e-4533-b3e8-139e52aecb8b');
const selectedPatient = ref(initialPatient);
const showPersonaModal = ref(false);
const switchingPersona = ref(null);
const showSyncDrawer = ref(false);

function formatMutationTime(timestamp) {
  if (!timestamp) return '';
  const date = new Date(timestamp);
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

// Configure early axios headers if token exists in session
if (initialToken && window.axios) {
  window.axios.defaults.headers.common['Authorization'] = `Bearer ${initialToken}`;
  if (activeBranchId.value) {
    window.axios.defaults.headers.common['X-Branch-ID'] = activeBranchId.value;
  }
}

// View Metadata & Breadcrumbs Dictionary
const viewLabels = {
  search: { category: 'Patient Care', title: 'Patient Registry & Search' },
  profile: { category: 'Patient Care', title: 'Patient Medical Profile' },
  emergency: { category: 'Emergency Care', title: 'ER Triage & CAD Ambulance Dispatch' },
  doctor_dashboard: { category: 'Clinical Medicine', title: 'Doctor Clinical Dashboard' },
  ehr: { category: 'Clinical Medicine', title: 'Comprehensive EHR Timeline' },
  soap: { category: 'Clinical Medicine', title: 'SOAP Consultation Notes' },
  prescriptions: { category: 'Clinical Medicine', title: 'E-Prescriptions & Decision Support' },
  booking: { category: 'Outpatient & OPD', title: 'Doctor Booking Calendar' },
  queue: { category: 'Outpatient & OPD', title: 'OPD Queue Tokens & TV Display' },
  referrals: { category: 'Outpatient & OPD', title: 'Clinical Referral Transfers' },
  bed_map: { category: 'Inpatient & IPD', title: 'Visual Bed Map & Ward Allocation' },
  nursing: { category: 'Inpatient & IPD', title: 'Nursing Station (Vitals & MAR)' },
  discharge: { category: 'Inpatient & IPD', title: 'Discharge Summary Generator' },
  ipd_analytics: { category: 'Inpatient & IPD', title: 'Occupancy & ALOS Analytics' },
  laboratory: { category: 'Diagnostics', title: 'Diagnostic Laboratory Worklist' },
  radiology: { category: 'Diagnostics', title: 'Radiology PACS / RIS Imaging' },
  pharmacy: { category: 'Pharmacy', title: 'Pharmacy Master (FEFO Dispensing)' },
  billing: { category: 'Finance', title: 'Billing, Invoicing & Insurance Claims' },
  inventory: { category: 'Materials', title: 'Supplies & Biomedical Equipment' },
  hr: { category: 'Human Resources', title: 'Staff Directory, Shifts & Rostering' },
  reports: { category: 'Executive BI', title: 'Hospital Intelligence & Analytics' },
  telegram: { category: 'Alerts', title: 'Telegram Real-Time Notification Engine' },
  compliance: { category: 'Governance', title: 'HIPAA Compliance & Security Audits' },
  admin: { category: 'Administration', title: 'System Administration & Master Data' },
  super_admin: { category: 'Vendor Control', title: 'Super Admin Multi-Tenant Platform' },
  portal: { category: 'Digital Portals', title: 'Patient Portal Experience Preview' },
};

// Default Workspace Dashboard per Role
function getDefaultViewForRole(user) {
  if (!user) return 'search';
  const roles = (user.roles || []).map(r => (typeof r === 'string' ? r : r?.name || ''));
  if (user.primary_role && !roles.includes(user.primary_role)) {
    roles.push(user.primary_role);
  }
  if (user.is_super_admin || roles.includes('super_admin')) return 'super_admin';
  if (roles.includes('hospital_admin') || roles.includes('admin')) return 'reports';
  if (roles.includes('doctor')) return 'doctor_dashboard';
  if (roles.includes('nurse')) return 'nursing';
  if (roles.includes('pharmacist')) return 'pharmacy';
  if (roles.includes('billing_officer')) return 'billing';
  if (roles.includes('receptionist')) return 'booking';
  return 'search';
}

function getInitialView() {
  // 1. Check URL Hash (e.g. #billing, #pharmacy, #nursing)
  const hash = window.location.hash.replace(/^#\/?/, '').trim();
  if (hash && viewLabels[hash]) {
    if ((hash === 'ehr' || hash === 'profile' || hash === 'prescriptions') && !initialPatient) {
      return 'search';
    }
    return hash;
  }

  // 2. Check localStorage saved view
  const saved = localStorage.getItem('hms_current_view');
  if (saved && viewLabels[saved]) {
    if ((saved === 'ehr' || saved === 'profile' || saved === 'prescriptions') && !initialPatient) {
      return 'search';
    }
    return saved;
  }

  // 3. Saved user role default
  if (currentUser.value) {
    return getDefaultViewForRole(currentUser.value);
  }

  return 'doctor_dashboard';
}

const currentView = ref(getInitialView());

// Keep active view & URL hash synced across navigation and page refreshes
watch(currentView, (newView) => {
  if (newView) {
    localStorage.setItem('hms_current_view', newView);
    const targetHash = `#${newView}`;
    if (window.location.hash !== targetHash) {
      window.history.replaceState(null, '', targetHash);
    }
  }
}, { immediate: true });

// Keep selected patient persisted in case of refresh during EHR or Prescriptions
watch(selectedPatient, (newPatient) => {
  if (newPatient) {
    localStorage.setItem('hms_selected_patient', JSON.stringify(newPatient));
  } else {
    localStorage.removeItem('hms_selected_patient');
  }
}, { deep: true });

// Responsive Sidebar & Navigation State
const isSidebarCollapsed = ref(localStorage.getItem('hms_sidebar_collapsed') === 'true');
const isMobileSidebarOpen = ref(false);
const searchNav = ref('');

function toggleSidebarCollapse() {
  isSidebarCollapsed.value = !isSidebarCollapsed.value;
  localStorage.setItem('hms_sidebar_collapsed', isSidebarCollapsed.value ? 'true' : 'false');
}

function selectView(viewKey) {
  currentView.value = viewKey;
  isMobileSidebarOpen.value = false;
}

function openEhrView() {
  isMobileSidebarOpen.value = false;
  navigateToEhrTimeline();
}

function openPrescriptionsView() {
  isMobileSidebarOpen.value = false;
  navigateToPrescriptions();
}

function matchesNav(label, category = '') {
  if (!searchNav.value) return true;
  const q = searchNav.value.toLowerCase().trim();
  return label.toLowerCase().includes(q) || category.toLowerCase().includes(q);
}

const isRegistrationModalOpen = ref(false);
const isOrderModalOpen = ref(false);
const orderModalType = ref('lab');
const searchScreenRef = ref(null);

// Support Impersonation State
const isImpersonating = ref(false);
const impersonatedHospitalName = ref('');
const exitingImpersonation = ref(false);

// Built-in Demo Personas for Simulation
const defaultDemoPersonas = [
  {
    role_key: 'hospital_admin',
    role_label: 'Hospital Admin',
    email: 'admin@hms.local',
    password: 'password123',
    name: 'Dr. Arthur Sterling',
    accessible_scopes: ['Multi-Branch Facilities', 'Compliance & HIPAA', 'Staff & Master Data', 'BI Reports'],
  },
  {
    role_key: 'doctor',
    role_label: 'Doctor / Clinician',
    email: 'doctor@hms.local',
    password: 'password123',
    name: 'Dr. Eleanor Vance, MD',
    accessible_scopes: ['Doctor Dashboard', 'EHR Clinical History', 'SOAP Consultations', 'Prescriptions & Lab Orders'],
  },
  {
    role_key: 'nurse',
    role_label: 'Inpatient Nurse',
    email: 'nurse@hms.local',
    password: 'password123',
    name: 'Sister Clara Oswald, RN',
    accessible_scopes: ['Bed Map Visuals', 'Nursing Station (Vitals/Meds)', 'Patient Intake', 'Discharge Summaries'],
  },
  {
    role_key: 'pharmacist',
    role_label: 'Chief Pharmacist',
    email: 'pharmacist@hms.local',
    password: 'password123',
    name: 'Marcus Holloway, PharmD',
    accessible_scopes: ['Pharmacy Dispensing', 'Drug Batches & Expiry', 'Stock Reorder Alerts', 'Patient Prescriptions'],
  },
  {
    role_key: 'billing_officer',
    role_label: 'Billing Officer',
    email: 'billing@hms.local',
    password: 'password123',
    name: 'Jennifer Blake',
    accessible_scopes: ['Invoices & Payments', 'Insurance Claims', 'Approvals Queue', 'Revenue Analytics'],
  },
  {
    role_key: 'receptionist',
    role_label: 'Reception / Registrar',
    email: 'receptionist@hms.local',
    password: 'password123',
    name: 'Sarah Connor',
    accessible_scopes: ['Patient Registration', 'Doctor Booking Calendar', 'OPD Queue Tokens & TV Display'],
  },
];
const demoPersonas = ref(defaultDemoPersonas);

// RBAC Role Computations - Strictly Isolated with Managerial Fallback
const userRoles = computed(() => {
  const r = currentUser.value?.roles || [];
  const list = r.map(item => (typeof item === 'string' ? item : item?.name || ''));
  if (currentUser.value?.primary_role && !list.includes(currentUser.value.primary_role)) {
    list.push(currentUser.value.primary_role);
  }
  return list;
});
const isSuperAdmin = computed(() => !!currentUser.value?.is_super_admin || userRoles.value.includes('super_admin'));
const isHospitalAdmin = computed(() => !isSuperAdmin.value && (userRoles.value.includes('hospital_admin') || userRoles.value.includes('admin')));
const isDoctor = computed(() => isHospitalAdmin.value || userRoles.value.includes('doctor'));
const isNurse = computed(() => isHospitalAdmin.value || userRoles.value.includes('nurse'));
const isPharmacist = computed(() => isHospitalAdmin.value || userRoles.value.includes('pharmacist'));
const isBilling = computed(() => isHospitalAdmin.value || userRoles.value.includes('billing_officer'));
const isReceptionist = computed(() => isHospitalAdmin.value || userRoles.value.includes('receptionist'));
const isLab = computed(() => isHospitalAdmin.value || userRoles.value.includes('lab_technician') || userRoles.value.includes('radiologist'));

// Breadcrumbs & View Metadata
const currentViewMeta = computed(() => {
  return viewLabels[currentView.value] || {
    category: 'Hospital Management',
    title: currentView.value ? currentView.value.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'Dashboard',
  };
});

// Role Badge Color Styling
const roleBadgeColor = computed(() => {
  if (isSuperAdmin.value) return { bg: 'bg-amber-500/15', text: 'text-amber-300', dot: 'bg-amber-400', border: 'border-amber-500/30' };
  if (userRoles.value.includes('hospital_admin') || userRoles.value.includes('admin')) return { bg: 'bg-purple-500/15', text: 'text-purple-300', dot: 'bg-purple-400', border: 'border-purple-500/30' };
  if (userRoles.value.includes('doctor')) return { bg: 'bg-sky-500/15', text: 'text-sky-300', dot: 'bg-sky-400', border: 'border-sky-500/30' };
  if (userRoles.value.includes('nurse')) return { bg: 'bg-emerald-500/15', text: 'text-emerald-300', dot: 'bg-emerald-400', border: 'border-emerald-500/30' };
  if (userRoles.value.includes('pharmacist')) return { bg: 'bg-teal-500/15', text: 'text-teal-300', dot: 'bg-teal-400', border: 'border-teal-500/30' };
  if (userRoles.value.includes('billing_officer')) return { bg: 'bg-amber-500/15', text: 'text-amber-300', dot: 'bg-amber-400', border: 'border-amber-500/30' };
  return { bg: 'bg-blue-500/15', text: 'text-blue-300', dot: 'bg-blue-400', border: 'border-blue-500/30' };
});

// User Initials Display
const userInitials = computed(() => {
  if (!currentUser.value || !currentUser.value.name) return 'HMS';
  const parts = currentUser.value.name.replace(/^(Dr\.|Sister|Mr\.|Ms\.|Mrs\.)\s+/i, '').trim().split(/\s+/);
  if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
});

// Check Module Authorization strictly by Role Whitelist
function canAccessView(view) {
  if (!currentUser.value) return false;
  if (isSuperAdmin.value) return true;
  if (isHospitalAdmin.value && view !== 'super_admin') return true;

  switch (view) {
    case 'super_admin':
      return false;
    case 'admin':
    case 'compliance':
    case 'reports':
    case 'telegram':
    case 'hr':
    case 'portal':
    case 'ipd_analytics':
      return false;
    case 'billing':
      return isBilling.value;
    case 'inventory':
      return isNurse.value || isPharmacist.value;
    case 'pharmacy':
      return isPharmacist.value;
    case 'prescriptions':
      return isDoctor.value || isPharmacist.value;
    case 'doctor_dashboard':
    case 'soap':
      return isDoctor.value;
    case 'ehr':
      return isDoctor.value || isNurse.value;
    case 'nursing':
      return isNurse.value;
    case 'bed_map':
      return isDoctor.value || isNurse.value || isBilling.value || isReceptionist.value;
    case 'discharge':
    case 'emergency':
      return isDoctor.value || isNurse.value;
    case 'booking':
    case 'referrals':
      return isDoctor.value || isReceptionist.value;
    case 'queue':
      return isDoctor.value || isReceptionist.value || isNurse.value;
    case 'laboratory':
    case 'radiology':
      return isDoctor.value || isLab.value;
    case 'search':
    case 'profile':
      return true;
    default:
      return false;
  }
}

// Sidebar Navigation Icons (Clean, specialized SVGs)
const navIcons = {
  users: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
  'user-plus': 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
  bolt: 'M13 10V3L4 14h7v7l9-11h-7z',
  calendar: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
  bell: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
  'clipboard-list': 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
  'switch-horizontal': 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
  'chart-bar': 'M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
  'folder-medical': 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
  pill: 'M10.5 4.5a4.95 4.95 0 017 7L8.5 20.5a4.95 4.95 0 01-7-7l9-9zm-2 5l7 7',
  'view-grid': 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
  'heart-pulse': 'M3 12h4l3 8 4-16 3 8h4',
  'document-check': 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
  'chart-pie': 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z',
  beaker: 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z',
  camera: 'M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z M15 13a3 3 0 11-6 0 3 3 0 016 0z',
  shop: 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
  cube: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
  'credit-card': 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
  'user-group': 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
  'presentation-chart': 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
  'paper-airplane': 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.52 2.77-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .27z',
  'shield-check': 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
  cog: 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4',
  server: 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01',
  'external-link': 'M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14',
};

// Grouped Navigation Structure strictly isolated by RBAC
const navigationGroups = computed(() => [
  {
    id: 'intake',
    label: 'Patient Care',
    items: [
      {
        id: 'search',
        view: 'search',
        label: 'Patient Registry',
        icon: 'users',
        canAccess: canAccessView('search'),
      },
      {
        id: 'register',
        action: 'register',
        label: 'Register Walk-In',
        icon: 'user-plus',
        canAccess: isReceptionist.value || isNurse.value || isHospitalAdmin.value,
      },
      {
        id: 'emergency',
        view: 'emergency',
        label: 'ER Triage & Ambulance',
        icon: 'bolt',
        badge: 'ER',
        badgeClass: 'bg-rose-500/25 text-rose-300 font-bold',
        activeClass: 'bg-rose-600 text-white shadow-lg shadow-rose-600/30',
        canAccess: canAccessView('emergency'),
      },
    ],
  },
  {
    id: 'opd',
    label: 'Outpatient & OPD',
    items: [
      {
        id: 'booking',
        view: 'booking',
        label: 'Doctor Calendar',
        icon: 'calendar',
        canAccess: canAccessView('booking'),
      },
      {
        id: 'queue',
        view: 'queue',
        label: 'Queue & TV Display',
        icon: 'bell',
        canAccess: canAccessView('queue'),
      },
      {
        id: 'soap',
        view: 'soap',
        label: 'SOAP Consultation',
        icon: 'clipboard-list',
        canAccess: canAccessView('soap'),
      },
      {
        id: 'referrals',
        view: 'referrals',
        label: 'Referral Transfers',
        icon: 'switch-horizontal',
        canAccess: canAccessView('referrals'),
      },
    ],
  },
  {
    id: 'clinical',
    label: 'Clinical Medicine',
    items: [
      {
        id: 'doctor_dashboard',
        view: 'doctor_dashboard',
        label: 'Doctor Dashboard',
        icon: 'chart-bar',
        canAccess: canAccessView('doctor_dashboard'),
      },
      {
        id: 'ehr',
        action: 'ehr',
        view: 'ehr',
        label: 'EHR Clinical Records',
        icon: 'folder-medical',
        canAccess: canAccessView('ehr'),
      },
      {
        id: 'prescriptions',
        action: 'prescriptions',
        view: 'prescriptions',
        label: isPharmacist.value ? 'Prescriptions Review' : 'E-Prescriptions & CDS',
        icon: 'pill',
        canAccess: canAccessView('prescriptions'),
      },
    ],
  },
  {
    id: 'ipd',
    label: 'Inpatient & IPD',
    items: [
      {
        id: 'bed_map',
        view: 'bed_map',
        label: 'Bed Map & Wards',
        icon: 'view-grid',
        canAccess: canAccessView('bed_map'),
      },
      {
        id: 'nursing',
        view: 'nursing',
        label: 'Nursing Station (Vitals)',
        icon: 'heart-pulse',
        activeClass: 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30',
        canAccess: canAccessView('nursing'),
      },
      {
        id: 'discharge',
        view: 'discharge',
        label: 'Discharge Summaries',
        icon: 'document-check',
        canAccess: canAccessView('discharge'),
      },
      {
        id: 'ipd_analytics',
        view: 'ipd_analytics',
        label: 'Occupancy & ALOS',
        icon: 'chart-pie',
        canAccess: canAccessView('ipd_analytics'),
      },
    ],
  },
  {
    id: 'diagnostics',
    label: 'Diagnostics & Labs',
    items: [
      {
        id: 'laboratory',
        view: 'laboratory',
        label: 'Diagnostic Laboratory',
        icon: 'beaker',
        activeClass: 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30',
        canAccess: canAccessView('laboratory'),
      },
      {
        id: 'radiology',
        view: 'radiology',
        label: 'Radiology PACS / RIS',
        icon: 'camera',
        activeClass: 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30',
        canAccess: canAccessView('radiology'),
      },
    ],
  },
  {
    id: 'pharmacy_materials',
    label: 'Pharmacy & Supplies',
    items: [
      {
        id: 'pharmacy',
        view: 'pharmacy',
        label: 'Pharmacy Master (FEFO)',
        icon: 'shop',
        activeClass: 'bg-teal-600 text-white shadow-lg shadow-teal-600/30',
        canAccess: canAccessView('pharmacy'),
      },
      {
        id: 'inventory',
        view: 'inventory',
        label: isNurse.value ? 'Ward Consumables' : 'Inventory & Equipment',
        icon: 'cube',
        canAccess: canAccessView('inventory'),
      },
    ],
  },
  {
    id: 'finance',
    label: 'Finance & Claims',
    items: [
      {
        id: 'billing',
        view: 'billing',
        label: 'Billing & Invoicing',
        icon: 'credit-card',
        activeClass: 'bg-amber-600 text-white shadow-lg shadow-amber-600/30',
        canAccess: canAccessView('billing'),
      },
    ],
  },
  {
    id: 'management',
    label: 'Administration & BI',
    items: [
      {
        id: 'hr',
        view: 'hr',
        label: 'HR & Staff Rosters',
        icon: 'user-group',
        canAccess: canAccessView('hr'),
      },
      {
        id: 'reports',
        view: 'reports',
        label: 'Executive Reports & BI',
        icon: 'presentation-chart',
        activeClass: 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30',
        canAccess: canAccessView('reports'),
      },
      {
        id: 'telegram',
        view: 'telegram',
        label: 'Telegram Alert Engine',
        icon: 'paper-airplane',
        activeClass: 'bg-sky-600 text-white shadow-lg shadow-sky-600/30',
        canAccess: canAccessView('telegram'),
      },
      {
        id: 'compliance',
        view: 'compliance',
        label: 'HIPAA & Compliance',
        icon: 'shield-check',
        activeClass: 'bg-amber-600 text-white shadow-lg shadow-amber-600/30',
        canAccess: canAccessView('compliance'),
      },
      {
        id: 'admin',
        view: 'admin',
        label: 'System Administration',
        icon: 'cog',
        activeClass: 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30',
        canAccess: canAccessView('admin'),
      },
      {
        id: 'super_admin',
        view: 'super_admin',
        label: 'Super Admin Multi-Tenant',
        icon: 'server',
        badge: 'ROOT',
        badgeClass: 'bg-amber-500/25 text-amber-300 font-bold',
        activeClass: 'bg-amber-600 text-white shadow-lg shadow-amber-600/30',
        canAccess: canAccessView('super_admin'),
      },
      {
        id: 'portal',
        view: 'portal',
        label: 'Patient Portal Preview',
        icon: 'external-link',
        canAccess: canAccessView('portal'),
      },
    ],
  },
]);

const filteredNavGroups = computed(() => {
  return navigationGroups.value
    .map(group => {
      const filteredItems = group.items.filter(item => {
        if (!item.canAccess) return false;
        if (!searchNav.value) return true;
        const q = searchNav.value.toLowerCase().trim();
        return item.label.toLowerCase().includes(q) || group.label.toLowerCase().includes(q);
      });
      return {
        ...group,
        items: filteredItems,
      };
    })
    .filter(group => group.items.length > 0);
});

function isItemActive(item) {
  if (!item.view) return false;
  return currentView.value === item.view;
}

function handleNavItemClick(item) {
  if (item.action === 'register') {
    openRegistrationModal();
    isMobileSidebarOpen.value = false;
    return;
  }
  if (item.action === 'ehr') {
    openEhrView();
    return;
  }
  if (item.action === 'prescriptions') {
    openPrescriptionsView();
    return;
  }
  if (item.view) {
    selectView(item.view);
  }
}

function handleBranchChange(branchId) {
  activeBranchId.value = branchId;
  const found = accessibleBranches.value.find(b => b.id === branchId);
  if (found) {
    currentBranch.value = found;
  }
  if (window.axios) {
    window.axios.defaults.headers.common['X-Branch-ID'] = branchId;
  }
}

function handleLoginSuccess(payload) {
  currentUser.value = payload.user || payload.data?.user;
  currentOrganization.value = payload.organization || payload.data?.organization;
  currentBranch.value = payload.default_branch || payload.data?.default_branch;
  accessibleBranches.value = payload.accessible_branches || payload.data?.accessible_branches || [];

  if (currentBranch.value && currentBranch.value.id) {
    activeBranchId.value = currentBranch.value.id;
  } else if (accessibleBranches.value.length > 0) {
    activeBranchId.value = accessibleBranches.value[0].id;
    currentBranch.value = accessibleBranches.value[0];
  }

  const token = payload.token || payload.data?.token;
  const sessionData = {
    user: currentUser.value,
    organization: currentOrganization.value,
    default_branch: currentBranch.value,
    accessible_branches: accessibleBranches.value,
    token: token,
  };
  localStorage.setItem('hms_portal_session', JSON.stringify(sessionData));
  if (token) {
    sessionStorage.setItem('hms_auth_token', token);
    if (window.axios) {
      window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    }
  }
  if (activeBranchId.value && window.axios) {
    window.axios.defaults.headers.common['X-Branch-ID'] = activeBranchId.value;
  }

  currentView.value = getDefaultViewForRole(currentUser.value);
  localStorage.setItem('hms_current_view', currentView.value);
  const targetHash = `#${currentView.value}`;
  if (window.location.hash !== targetHash) {
    window.history.replaceState(null, '', targetHash);
  }

  if (window.location.pathname === '/login') {
    window.history.pushState({}, '', '/app');
  }
}

async function handleSignOut() {
  const token = sessionStorage.getItem('hms_auth_token');
  try {
    if (token) {
      await fetch('/api/v1/auth/logout', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      });
    }
  } catch (err) {
    console.warn('Sign out request failed:', err);
  } finally {
    localStorage.removeItem('hms_portal_session');
    localStorage.removeItem('hms_current_view');
    localStorage.removeItem('hms_selected_patient');
    sessionStorage.removeItem('hms_auth_token');
    if (window.axios) {
      delete window.axios.defaults.headers.common['Authorization'];
      delete window.axios.defaults.headers.common['X-Branch-ID'];
    }
    currentUser.value = null;
    currentOrganization.value = null;
    currentBranch.value = null;
    accessibleBranches.value = [];
    selectedPatient.value = null;
    window.location.hash = '';
    if (window.location.pathname !== '/login') {
      window.history.pushState({}, '', '/login');
    }
  }
}

async function quickSwitchPersona(persona) {
  switchingPersona.value = persona.role_key;
  try {
    const res = await fetch('/api/v1/auth/login', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({
        email: persona.email,
        password: persona.password || 'password123',
      }),
    });
    const data = await res.json();
    if (res.ok && (data.user || data.data?.user)) {
      handleLoginSuccess(data);
      showPersonaModal.value = false;
    }
  } catch (err) {
    console.error('Failed to switch persona:', err);
  } finally {
    switchingPersona.value = null;
  }
}

onMounted(async () => {
  window.addEventListener('hms:unauthorized', () => {
    handleSignOut();
  });

  const impId = sessionStorage.getItem('hms_impersonation_id');
  const impHosp = sessionStorage.getItem('hms_impersonated_hospital');
  if (impId) {
    isImpersonating.value = true;
    if (impHosp) {
      try {
        const h = JSON.parse(impHosp);
        impersonatedHospitalName.value = h.name || 'Hospital Client';
      } catch {
        impersonatedHospitalName.value = 'Hospital Client';
      }
    }
  }

  // Load session from localStorage only if token exists
  const rawSession = localStorage.getItem('hms_portal_session');
  if (rawSession) {
    try {
      const parsed = JSON.parse(rawSession);
      if (parsed && parsed.user && parsed.token) {
        currentUser.value = parsed.user;
        currentOrganization.value = parsed.organization;
        currentBranch.value = parsed.default_branch;
        accessibleBranches.value = parsed.accessible_branches || [];
        if (parsed.default_branch?.id) {
          activeBranchId.value = parsed.default_branch.id;
        } else if (accessibleBranches.value.length > 0) {
          activeBranchId.value = accessibleBranches.value[0].id;
        }

        sessionStorage.setItem('hms_auth_token', parsed.token);
        if (window.axios) {
          window.axios.defaults.headers.common['Authorization'] = `Bearer ${parsed.token}`;
          if (activeBranchId.value) {
            window.axios.defaults.headers.common['X-Branch-ID'] = activeBranchId.value;
          }
        }

        // Verify session with backend
        fetch('/api/v1/auth/me', {
          headers: {
            'Accept': 'application/json',
            'Authorization': `Bearer ${parsed.token}`,
            'X-Branch-ID': activeBranchId.value || '',
          }
        }).then(res => {
          if (!res.ok || res.status === 401) {
            handleSignOut();
            return null;
          }
          return res.json();
        }).then(data => {
          if (data && data.authenticated && data.user) {
            // Ensure roles array is not cleared if backend returns empty
            if ((!data.user.roles || data.user.roles.length === 0) && parsed.user?.roles?.length > 0) {
              data.user.roles = parsed.user.roles;
              data.user.primary_role = parsed.user.primary_role;
            }
            currentUser.value = data.user;
            if (data.organization) currentOrganization.value = data.organization;
            if (data.default_branch) currentBranch.value = data.default_branch;
            if (data.accessible_branches) accessibleBranches.value = data.accessible_branches;

            // Keep updated session in storage
            const sessionData = {
              user: data.user,
              organization: currentOrganization.value,
              default_branch: currentBranch.value,
              accessible_branches: accessibleBranches.value,
              token: parsed.token,
            };
            localStorage.setItem('hms_portal_session', JSON.stringify(sessionData));
          } else if (data && data.authenticated === false) {
            handleSignOut();
          }
        }).catch(() => {
          // Keep local cached session on connection error
        });
      } else {
        localStorage.removeItem('hms_portal_session');
        sessionStorage.removeItem('hms_auth_token');
      }
    } catch {
      localStorage.removeItem('hms_portal_session');
      sessionStorage.removeItem('hms_auth_token');
    }
  }

  // Load demo personas for modal
  try {
    const res = await fetch('/api/v1/auth/demo-accounts');
    const data = await res.json();
    if (data.demo_accounts) {
      demoPersonas.value = data.demo_accounts;
    }
  } catch (err) {
    console.error('Failed to load demo accounts:', err);
  }

  // Sync view when browser Back/Forward navigation changes URL hash
  window.addEventListener('hashchange', () => {
    const hash = window.location.hash.replace(/^#\/?/, '').trim();
    if (hash && viewLabels[hash] && canAccessView(hash) && currentView.value !== hash) {
      currentView.value = hash;
    }
  });

  // Ensure currentView is allowed for active role
  if (currentUser.value && !canAccessView(currentView.value)) {
    currentView.value = getDefaultViewForRole(currentUser.value);
  }
});

watch(currentUser, (newUser) => {
  if (newUser && !canAccessView(currentView.value)) {
    currentView.value = getDefaultViewForRole(newUser);
  }
});

async function exitImpersonation() {
  const impId = sessionStorage.getItem('hms_impersonation_id');
  exitingImpersonation.value = true;
  try {
    if (impId) {
      await fetch('/api/v1/super-admin/impersonation/stop', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({ impersonation_id: impId })
      });
    }
  } catch (err) {
    console.error('Failed to gracefully exit impersonation:', err);
  } finally {
    sessionStorage.removeItem('hms_impersonation_id');
    sessionStorage.removeItem('hms_impersonation_token');
    sessionStorage.removeItem('hms_impersonated_hospital');
    sessionStorage.removeItem('hms_impersonation_expires');
    isImpersonating.value = false;
    exitingImpersonation.value = false;
    window.location.reload();
  }
}

function openRegistrationModal() {
  isRegistrationModalOpen.value = true;
}

function handleSelectPatient(patient) {
  selectedPatient.value = patient;
  currentView.value = 'ehr';
}

function handlePatientCreated(patient) {
  selectedPatient.value = patient;
  currentView.value = 'ehr';
  if (searchScreenRef.value) {
    searchScreenRef.value.fetchPatients();
  }
}

function handleOpenPatientEhr(patient) {
  selectedPatient.value = patient;
  currentView.value = 'ehr';
}

function handleOpenPrescriptionWriter(patient) {
  if (patient) selectedPatient.value = patient;
  if (!selectedPatient.value) {
    currentView.value = 'search';
    return;
  }
  currentView.value = 'prescriptions';
}

function handleOpenOrderModal(payload) {
  if (typeof payload === 'string') {
    orderModalType.value = payload;
  } else if (payload && payload.type) {
    orderModalType.value = payload.type;
    if (payload.patient) selectedPatient.value = payload.patient;
  }
  if (!selectedPatient.value) {
    currentView.value = 'search';
    return;
  }
  isOrderModalOpen.value = true;
}

function handleOrderCreated() {
  // Can trigger refresh if timeline is active
}

function handlePrescriptionCreated() {
  currentView.value = 'ehr';
}

function navigateToEhrTimeline() {
  if (!selectedPatient.value) {
    currentView.value = 'search';
  } else {
    currentView.value = 'ehr';
  }
}

function navigateToPrescriptions() {
  if (!selectedPatient.value) {
    currentView.value = 'search';
  } else {
    currentView.value = 'prescriptions';
  }
}
</script>
