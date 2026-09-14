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
      const token = sessionStorage.getItem('hms_auth_token');
      if (token && !headers['Authorization']) {
        headers['Authorization'] = `Bearer ${token}`;
      }

      const response = await fetch(mutation.url, {
        method: mutation.method,
        headers,
        body: mutation.data ? (typeof mutation.data === 'string' ? mutation.data : JSON.stringify(mutation.data)) : undefined,
      });

      if (response.ok || response.status === 201 || response.status === 200 || response.status === 204) {
        await removeOutboxMutation(mutation.id);
        syncedCount++;
      } else if (response.status === 422 || response.status === 400 || response.status === 409) {
        // Client validation error or conflict: remove from queue to avoid infinite blockage
        console.warn('[HMS Sync] Mutation unrecoverable (HTTP ' + response.status + '):', mutation.summary);
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
