/**
 * Metro HMS Offline Progressive Web App Service Worker
 * Provides resilient offline caching, shell resilience, and network fallback.
 */

const CACHE_VERSION = 'v1.0.2';
const SHELL_CACHE = `hms-shell-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `hms-dynamic-${CACHE_VERSION}`;
const API_CACHE = `hms-api-${CACHE_VERSION}`;

const PRECACHE_URLS = [
  '/app',
  '/login',
  '/manifest.json',
  '/icon.svg',
  '/favicon.ico',
];

// Install: Precache application shell
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(SHELL_CACHE).then((cache) => {
      return cache.addAll(PRECACHE_URLS).catch((err) => {
        console.warn('[SW] Pre-cache partial fail (continuing):', err);
      });
    }).then(() => self.skipWaiting())
  );
});

// Activate: Clean up stale caches
self.addEventListener('activate', (event) => {
  const currentCaches = [SHELL_CACHE, DYNAMIC_CACHE, API_CACHE];
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (!currentCaches.includes(key)) {
            console.log('[SW] Purging outdated cache:', key);
            return caches.delete(key);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch: Strategy dispatcher
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // 1. Skip non-GET requests (handled by Axios Outbox Queue)
  if (request.method !== 'GET') {
    return;
  }

  // 2. Navigation requests: Network-First with cached /app shell fallback
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then((response) => {
          if (response && response.status === 200) {
            const copy = response.clone();
            caches.open(SHELL_CACHE).then((cache) => cache.put(request, copy));
          }
          return response;
        })
        .catch(async () => {
          console.log('[SW] Offline navigation intercepted, serving cached app shell');
          const cache = await caches.open(SHELL_CACHE);
          const cachedApp = await cache.match('/app');
          if (cachedApp) return cachedApp;
          const cachedLogin = await cache.match('/login');
          if (cachedLogin) return cachedLogin;
          return new Response('<h1>Metro HMS Offline</h1><p>The system is offline and waiting for network reconnection.</p>', {
            headers: { 'Content-Type': 'text/html' },
          });
        })
    );
    return;
  }

  // 3. API GET requests: Network-First, cache fallback
  if (url.pathname.startsWith('/api/')) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          if (response && response.status === 200) {
            const copy = response.clone();
            caches.open(API_CACHE).then((cache) => cache.put(request, copy));
          }
          return response;
        })
        .catch(async () => {
          const cache = await caches.open(API_CACHE);
          const cachedResponse = await cache.match(request);
          if (cachedResponse) {
            return cachedResponse;
          }
          return new Response(JSON.stringify({
            error: 'Network Unavailable',
            message: 'You are currently offline. This request will sync once connection is restored.',
            offline: true
          }), {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
          });
        })
    );
    return;
  }

  // 4. Static assets (Vite /build/*, fonts, images): Cache-First, network fallback
  if (
    url.pathname.startsWith('/build/') ||
    url.pathname.endsWith('.woff2') ||
    url.pathname.endsWith('.woff') ||
    url.pathname.endsWith('.css') ||
    url.pathname.endsWith('.js') ||
    url.pathname.endsWith('.svg') ||
    url.pathname.endsWith('.png') ||
    url.pathname.endsWith('.ico')
  ) {
    event.respondWith(
      caches.match(request).then((cached) => {
        if (cached) {
          return cached;
        }
        return fetch(request).then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200) {
            const copy = networkResponse.clone();
            caches.open(DYNAMIC_CACHE).then((cache) => cache.put(request, copy));
          }
          return networkResponse;
        });
      })
    );
    return;
  }

  // Default: Network with dynamic cache fallback
  event.respondWith(
    fetch(request).catch(() => caches.match(request))
  );
});
