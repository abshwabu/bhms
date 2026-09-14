import { ref } from 'vue';
import { getOutboxMutations, removeOutboxMutation } from './offlineStorage';

export const isOnline = ref(typeof navigator !== 'undefined' ? navigator.onLine : true);
export const pendingSyncCount = ref(0);
export const isSyncing = ref(false);
export const lastSyncTime = ref(typeof localStorage !== 'undefined' ? localStorage.getItem('hms_last_sync_time') || null : null);
export const syncStatusMessage = ref('');
export const pendingItems = ref([]);

// Refresh pending count & list
export async function refreshPendingCount() {
  const list = await getOutboxMutations();
  pendingSyncCount.value = list.length;
  pendingItems.value = list;
}

// Replay pending outbox mutations sequentially to hospital backend
export async function syncOutbox() {
  if (isSyncing.value) return;
  if (!navigator.onLine) {
    syncStatusMessage.value = 'Cannot sync while offline';
    return;
  }

  const mutations = await getOutboxMutations();
  if (mutations.length === 0) {
    pendingSyncCount.value = 0;
    pendingItems.value = [];
    return;
  }

  isSyncing.value = true;
  syncStatusMessage.value = `Syncing ${mutations.length} pending offline record(s)...`;
  let syncedCount = 0;
  let failedCount = 0;

  for (const mutation of mutations) {
    try {
      const headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-HMS-Offline-Replay': '1',
        ...(mutation.headers || {}),
      };

      // Retrieve freshest token from storage
      const token = sessionStorage.getItem('hms_auth_token') || localStorage.getItem('hms_auth_token');
      if (token && !headers['Authorization']) {
        headers['Authorization'] = `Bearer ${token}`;
      }

      // Ensure branch ID is present
      if (!headers['X-Branch-ID']) {
        try {
          const sessionRaw = localStorage.getItem('hms_portal_session');
          if (sessionRaw) {
            const parsed = JSON.parse(sessionRaw);
            if (parsed?.default_branch?.id) {
              headers['X-Branch-ID'] = parsed.default_branch.id;
            }
          }
        } catch {}
      }

      // Ensure CSRF token is present
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      if (csrfToken && !headers['X-CSRF-TOKEN']) {
        headers['X-CSRF-TOKEN'] = csrfToken;
      }

      // Strip client-side offline temporary fields before sending to API
      let bodyData = mutation.data;
      if (bodyData && typeof bodyData === 'object') {
        const cleanData = { ...bodyData };
        if (cleanData.id && typeof cleanData.id === 'string' && cleanData.id.startsWith('offline-')) {
          delete cleanData.id;
        }
        if (cleanData.mrn && typeof cleanData.mrn === 'string' && cleanData.mrn.startsWith('OFFLINE-')) {
          delete cleanData.mrn;
        }
        delete cleanData.is_offline_queued;
        delete cleanData.is_offline;
        delete cleanData.full_name;
        delete cleanData.age;
        bodyData = cleanData;
      }

      const response = await fetch(mutation.url, {
        method: mutation.method,
        headers,
        body: bodyData ? (typeof bodyData === 'string' ? bodyData : JSON.stringify(bodyData)) : undefined,
      });

      if (response.ok || response.status === 201 || response.status === 200 || response.status === 204) {
        await removeOutboxMutation(mutation.id);
        syncedCount++;

        // If patient registration synced, update any local offline references
        try {
          const resData = await response.json().catch(() => null);
          if (resData?.data?.id && mutation.data?.id) {
            const cached = await getApiResponse('/api/v1/patients');
            if (cached && Array.isArray(cached.data)) {
              const idx = cached.data.findIndex(p => p.id === mutation.data.id);
              if (idx !== -1) {
                cached.data[idx] = resData.data;
                await saveApiResponse('/api/v1/patients', cached);
              }
            }
          }
        } catch {}
      } else if (response.status === 422 || response.status === 400 || response.status === 409) {
        const errJson = await response.json().catch(() => null);
        console.warn('[HMS Sync] Mutation unrecoverable (HTTP ' + response.status + '):', mutation.summary, errJson);
        await removeOutboxMutation(mutation.id);
        failedCount++;
      } else if (response.status === 401) {
        // Unauthorized: token might be expired, stop sync until re-auth
        syncStatusMessage.value = 'Sync paused: session expired. Please sign in.';
        break;
      } else {
        // Server 5xx error: keep in outbox for next retry
        console.warn('[HMS Sync] Server error, will retry later:', response.status);
        break;
      }
    } catch (err) {
      console.warn('[HMS Sync] Network failure during replay:', err);
      // Connection lost again during sync
      isOnline.value = false;
      break;
    }
  }

  isSyncing.value = false;
  await refreshPendingCount();
  
  if (syncedCount > 0) {
    lastSyncTime.value = new Date().toLocaleTimeString();
    localStorage.setItem('hms_last_sync_time', lastSyncTime.value);
    syncStatusMessage.value = `Successfully synced ${syncedCount} record(s) with hospital server.`;
    window.dispatchEvent(new CustomEvent('hms:sync-completed', { detail: { syncedCount } }));
  } else if (failedCount > 0) {
    syncStatusMessage.value = `Finished sync (${failedCount} invalid records cleared).`;
  }
}

// Discard an item from outbox manually
export async function discardPendingItem(id) {
  await removeOutboxMutation(id);
  await refreshPendingCount();
}

// Initialize Sync Manager
export function initSyncManager() {
  refreshPendingCount();

  window.addEventListener('online', () => {
    isOnline.value = true;
    syncStatusMessage.value = 'Connection restored. Syncing pending data...';
    syncOutbox();
  });

  window.addEventListener('offline', () => {
    isOnline.value = false;
    syncStatusMessage.value = 'Offline mode active. All entries are queued locally.';
  });

  window.addEventListener('hms:outbox-updated', () => {
    refreshPendingCount();
  });

  // Periodically verify real connectivity every 30s
  setInterval(async () => {
    if (navigator.onLine) {
      try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 3500);
        const res = await fetch('/robots.txt?_ping=' + Date.now(), { method: 'HEAD', signal: controller.signal });
        clearTimeout(timeoutId);
        if (res.ok) {
          if (!isOnline.value) {
            isOnline.value = true;
            syncOutbox();
          }
        }
      } catch {
        isOnline.value = false;
      }
    } else {
      isOnline.value = false;
    }
  }, 30000);
}
