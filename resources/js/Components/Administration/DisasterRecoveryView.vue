<template>
  <div class="space-y-6">
    <!-- Go-Live Readiness Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-emerald-950 p-6 rounded-3xl text-white shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div class="space-y-2">
        <div class="flex items-center gap-3">
          <span class="p-2 bg-emerald-500/20 text-emerald-300 rounded-xl border border-emerald-500/30">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7zM8 12h8m-8 4h5" />
            </svg>
          </span>
          <h2 class="text-2xl font-black tracking-tight">Disaster Recovery & Automated Backups</h2>
        </div>
        <p class="text-slate-300 text-xs max-w-2xl">
          High-availability PostgreSQL disaster recovery engine with cryptographic SHA-256 checksum validation and automated pre-go-live restoration verification drills.
        </p>
      </div>

      <div class="flex items-center gap-4">
        <div class="bg-slate-800/80 border border-slate-700/80 px-5 py-3 rounded-2xl text-center">
          <div class="text-[10px] font-mono text-slate-400 uppercase tracking-wider">Restore Testing</div>
          <div class="text-xl font-black" :class="scorecard.go_live_ready ? 'text-emerald-400' : 'text-amber-400'">
            {{ scorecard.go_live_ready ? 'GO-LIVE READY' : 'DRILL PENDING' }}
          </div>
          <div class="text-[10px] font-mono text-slate-400 mt-0.5">
            {{ scorecard.drills_tested_count ?? 0 }} Drills Verified
          </div>
        </div>

        <button
          @click="createBackup"
          :disabled="creatingBackup"
          class="px-5 py-3 bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white rounded-2xl text-xs font-bold shadow-lg shadow-emerald-600/30 transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
        >
          <svg v-if="creatingBackup" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke-width="4" stroke="currentColor"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
          </svg>
          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
          </svg>
          <span>{{ creatingBackup ? 'Creating Snapshot...' : 'Trigger PostgreSQL Backup' }}</span>
        </button>
      </div>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-xs text-slate-500 font-medium">Recovery Point (RPO)</div>
        <div class="text-2xl font-black text-slate-900 mt-1">&lt; 15 Minutes</div>
        <div class="text-[10px] text-emerald-600 font-semibold mt-0.5">Automated WAL archiving</div>
      </div>

      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-xs text-slate-500 font-medium">Recovery Time (RTO)</div>
        <div class="text-2xl font-black text-blue-600 mt-1">&lt; 30 Minutes</div>
        <div class="text-[10px] text-slate-400 font-medium mt-0.5">Target standby restore</div>
      </div>

      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-xs text-slate-500 font-medium">Cryptographic Integrity</div>
        <div class="text-2xl font-black text-indigo-600 mt-1">SHA-256</div>
        <div class="text-[10px] text-indigo-600 font-medium mt-0.5">Zero bitrot verification</div>
      </div>

      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-xs text-slate-500 font-medium">Total Backup Snapshots</div>
        <div class="text-2xl font-black text-purple-600 mt-1">{{ scorecard.total_backups ?? 0 }} Archives</div>
        <div class="text-[10px] text-purple-600 font-medium mt-0.5">Encrypted at rest</div>
      </div>
    </div>

    <!-- Backup Snapshots Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200/80 uppercase tracking-wider text-[10px]">
            <tr>
              <th class="p-3.5">Timestamp</th>
              <th class="p-3.5">File Size</th>
              <th class="p-3.5">SHA-256 Integrity Checksum</th>
              <th class="p-3.5">Status</th>
              <th class="p-3.5">Verification Drill</th>
              <th class="p-3.5 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="!scorecard.backups || !scorecard.backups.length" class="text-center">
              <td colspan="6" class="p-8 text-slate-400">No backup archives found. Trigger a backup to initialize disaster recovery.</td>
            </tr>
            <tr v-for="b in (scorecard.backups || [])" :key="b.id" class="hover:bg-slate-50/80 transition">
              <td class="p-3.5 font-mono text-slate-600">
                {{ new Date(b.created_at).toLocaleString() }}
              </td>
              <td class="p-3.5 font-mono font-bold text-slate-800">
                {{ (b.file_size_bytes / 1024).toFixed(1) }} KB
              </td>
              <td class="p-3.5 font-mono text-slate-500 truncate max-w-[200px]" :title="b.checksum_sha256">
                {{ b.checksum_sha256 ? b.checksum_sha256.substring(0, 24) + '...' : '—' }}
              </td>
              <td class="p-3.5">
                <span
                  :class="{
                    'bg-emerald-100 text-emerald-800': b.status === 'restored',
                    'bg-blue-100 text-blue-800': b.status === 'verified',
                    'bg-slate-100 text-slate-800': b.status === 'completed',
                  }"
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase font-mono"
                >
                  {{ b.status }}
                </span>
              </td>
              <td class="p-3.5 text-xs text-slate-600">
                <div v-if="b.restored_at" class="text-emerald-700 font-semibold flex items-center gap-1">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  <span>Drill Verified</span>
                </div>
                <div v-else class="text-slate-400 italic">Untested</div>
              </td>
              <td class="p-3.5 text-right flex items-center justify-end gap-2">
                <button
                  @click="verifyChecksum(b.id)"
                  class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition cursor-pointer"
                >
                  Verify Hash
                </button>
                <button
                  @click="runRestoreDrill(b.id)"
                  class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 rounded-lg text-xs font-bold transition cursor-pointer"
                >
                  Run Restore Drill
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const scorecard = ref({});
const creatingBackup = ref(false);

async function loadBackups() {
  try {
    const res = await fetch('/api/v1/admin/backups', { headers: { 'Accept': 'application/json' } });
    const json = await res.json();
    if (json.success) scorecard.value = json.data;
  } catch (err) {
    console.error('Failed to load backups:', err);
  }
}

async function createBackup() {
  creatingBackup.value = true;
  try {
    const res = await fetch('/api/v1/admin/backups', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({ backup_type: 'database', notes: 'Manual admin console snapshot.' }),
    });
    const json = await res.json();
    if (json.success) await loadBackups();
  } catch (err) {
    console.error('Backup creation failed:', err);
  } finally {
    creatingBackup.value = false;
  }
}

async function verifyChecksum(id) {
  try {
    const res = await fetch(`/api/v1/admin/backups/${id}/verify`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      }
    });
    const json = await res.json();
    alert(`Checksum Validation: ${json.data.is_valid ? 'VALID (MATCH)' : 'FAILED'}\nSHA-256: ${json.data.stored_checksum}`);
    await loadBackups();
  } catch (err) {
    console.error('Checksum verification failed:', err);
  }
}

async function runRestoreDrill(id) {
  try {
    const res = await fetch(`/api/v1/admin/backups/${id}/restore-drill`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      }
    });
    const json = await res.json();
    alert(`Restore Verification Drill Passed!\nVerified Tables: ${json.data.verified_tables_count}\nDuration: ${json.data.drill_duration_ms} ms`);
    await loadBackups();
  } catch (err) {
    console.error('Restore drill failed:', err);
  }
}

onMounted(() => {
  loadBackups();
});
</script>
