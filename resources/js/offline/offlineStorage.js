/**
 * Metro HMS Client Offline Storage Engine
 * IndexedDB backed storage with localStorage fallback for:
 * 1. API GET response caching
 * 2. Outbox mutation queue (POST/PUT/PATCH/DELETE)
 * 3. Clinical state caching (patients, vitals, queues)
 */

const DB_NAME = 'hms_offline_db';
const DB_VERSION = 1;

let dbPromise = null;

function openDb() {
  if (dbPromise) return dbPromise;

  dbPromise = new Promise((resolve) => {
    if (typeof window === 'undefined' || !window.indexedDB) {
      console.warn('[HMS Offline] IndexedDB unavailable, using localStorage fallback');
      resolve(null);
      return;
    }

    const request = indexedDB.open(DB_NAME, DB_VERSION);

    request.onupgradeneeded = (event) => {
      const db = event.target.result;

      // 1. API GET response cache
      if (!db.objectStoreNames.contains('api_cache')) {
        db.createObjectStore('api_cache', { keyPath: 'url' });
      }

      // 2. Outbox mutations queue (POST/PUT/DELETE)
      if (!db.objectStoreNames.contains('outbox')) {
        const outboxStore = db.createObjectStore('outbox', { keyPath: 'id' });
        outboxStore.createIndex('status', 'status', { unique: false });
        outboxStore.createIndex('timestamp', 'timestamp', { unique: false });
      }

      // 3. Generic key-value store
      if (!db.objectStoreNames.contains('keyval')) {
        db.createObjectStore('keyval', { keyPath: 'key' });
      }
    };

    request.onsuccess = (event) => {
      resolve(event.target.result);
    };

    request.onerror = (event) => {
      console.error('[HMS Offline] IndexedDB open error:', event.target.error);
      resolve(null);
    };
  });

  return dbPromise;
}

// ---------------------------------------------------------------------------
// 1. API GET Response Caching
// ---------------------------------------------------------------------------
export async function saveApiResponse(url, data) {
  if (!url) return;
  // Normalize url by removing query params that are just timestamps
  const cleanUrl = url.replace(/([?&])_t=\d+/, '');

  try {
    const db = await openDb();
    if (!db) {
      localStorage.setItem(`hms_cache_${cleanUrl}`, JSON.stringify({ data, timestamp: Date.now() }));
      return;
    }
    const tx = db.transaction('api_cache', 'readwrite');
    const store = tx.objectStore('api_cache');
    store.put({
      url: cleanUrl,
      data,
      timestamp: Date.now(),
    });
  } catch (err) {
    console.warn('[HMS Offline] Error caching API response:', cleanUrl, err);
  }
}

export async function getApiResponse(url) {
  if (!url) return null;
  const cleanUrl = url.replace(/([?&])_t=\d+/, '');

  try {
    const db = await openDb();
    if (!db) {
      const raw = localStorage.getItem(`hms_cache_${cleanUrl}`);
      return raw ? JSON.parse(raw).data : null;
    }
    return new Promise((resolve) => {
      const tx = db.transaction('api_cache', 'readonly');
      const store = tx.objectStore('api_cache');
      const req = store.get(cleanUrl);
      req.onsuccess = () => resolve(req.result ? req.result.data : null);
      req.onerror = () => resolve(null);
    });
  } catch {
    return null;
  }
}

// ---------------------------------------------------------------------------
// 2. Outbox Mutation Queue (Offline Writes)
// ---------------------------------------------------------------------------
export async function addOutboxMutation({ url, method, data, headers = {}, summary = '' }) {
  const id = 'mut_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
  const mutation = {
    id,
    url,
    method: (method || 'POST').toUpperCase(),
    data: data || {},
    headers: headers || {},
    summary: summary || `${(method || 'POST').toUpperCase()} ${url}`,
    timestamp: Date.now(),
    status: 'pending',
    retryCount: 0,
  };

  try {
    const db = await openDb();
    if (!db) {
      const list = JSON.parse(localStorage.getItem('hms_outbox_fallback') || '[]');
      list.push(mutation);
      localStorage.setItem('hms_outbox_fallback', JSON.stringify(list));
      if (typeof window !== 'undefined') {
        window.dispatchEvent(new CustomEvent('hms:outbox-updated'));
      }
      return mutation;
    }
    const tx = db.transaction('outbox', 'readwrite');
    tx.objectStore('outbox').add(mutation);
    if (typeof window !== 'undefined') {
      window.dispatchEvent(new CustomEvent('hms:outbox-updated'));
    }
    return mutation;
  } catch (err) {
    console.error('[HMS Offline] Failed to queue outbox mutation:', err);
    return mutation;
  }
}

export async function getOutboxMutations() {
  try {
    const db = await openDb();
    if (!db) {
      return JSON.parse(localStorage.getItem('hms_outbox_fallback') || '[]');
    }
    return new Promise((resolve) => {
      const tx = db.transaction('outbox', 'readonly');
      const store = tx.objectStore('outbox');
      const req = store.getAll();
      req.onsuccess = () => {
        const items = req.result || [];
        items.sort((a, b) => a.timestamp - b.timestamp);
        resolve(items);
      };
      req.onerror = () => resolve([]);
    });
  } catch {
    return [];
  }
}

export async function removeOutboxMutation(id) {
  try {
    const db = await openDb();
    if (!db) {
      let list = JSON.parse(localStorage.getItem('hms_outbox_fallback') || '[]');
      list = list.filter((m) => m.id !== id);
      localStorage.setItem('hms_outbox_fallback', JSON.stringify(list));
      if (typeof window !== 'undefined') {
        window.dispatchEvent(new CustomEvent('hms:outbox-updated'));
      }
      return;
    }
    const tx = db.transaction('outbox', 'readwrite');
    tx.objectStore('outbox').delete(id);
    if (typeof window !== 'undefined') {
      window.dispatchEvent(new CustomEvent('hms:outbox-updated'));
    }
  } catch (err) {
    console.warn('[HMS Offline] Failed to remove mutation:', err);
  }
}

export async function clearOutbox() {
  try {
    const db = await openDb();
    if (!db) {
      localStorage.removeItem('hms_outbox_fallback');
      if (typeof window !== 'undefined') {
        window.dispatchEvent(new CustomEvent('hms:outbox-updated'));
      }
      return;
    }
    const tx = db.transaction('outbox', 'readwrite');
    tx.objectStore('outbox').clear();
    if (typeof window !== 'undefined') {
      window.dispatchEvent(new CustomEvent('hms:outbox-updated'));
    }
  } catch (err) {
    console.warn('[HMS Offline] Failed to clear outbox:', err);
  }
}
