/**
 * Metro HMS Offline Progressive Web App Service Worker
 * Provides resilient offline caching, shell resilience, and network fallback.
 */

const CACHE_VERSION = 'v1.0.4';
const SHELL_CACHE = `hms-shell-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `hms-dynamic-${CACHE_VERSION}`;
const API_CACHE = `hms-api-${CACHE_VERSION}`;

const BASE_PRECACHE_URLS = [
  '/app',
  '/login',
  '/manifest.json',
  '/icon.svg',
  '/favicon.ico',
  '/build/manifest.json',
];

// Embedded fallback web app manifest for guaranteed offline availability
const FALLBACK_MANIFEST = {
  name: 'Metro HMS - Enterprise Hospital Management System',
  short_name: 'Metro HMS',
  description: 'Unified, HIPAA-compliant hospital operating system with offline triage, clinical EHR, and outbox sync.',
  start_url: '/app',
  scope: '/',
  display: 'standalone',
  orientation: 'any',
  background_color: '#0f172a',
  theme_color: '#0f172a',
  icons: [
    {
      src: '/icon.svg',
      sizes: '192x192 512x512',
      type: 'image/svg+xml',
      purpose: 'any maskable'
    }
  ],
  categories: ['medical', 'productivity', 'business']
};

// Install: Precache application shell and all Vite build assets
self.addEventListener('install', (event) => {
  event.waitUntil(
    (async () => {
      const cache = await caches.open(SHELL_CACHE);

      // 1. Precache standard shell pages and manifest
      try {
        await cache.addAll(BASE_PRECACHE_URLS);
      } catch (err) {
        console.warn('[SW] Base shell precache notice:', err);
      }

      // Store fallback manifest directly into cache in case fetch failed
      try {
        await cache.put(
          '/manifest.json',
          new Response(JSON.stringify(FALLBACK_MANIFEST), {
            headers: { 'Content-Type': 'application/manifest+json' }
          })
        );
      } catch (err) {
        // ignore
      }

      // 2. Discover and precache all active Vite build bundles from manifest.json
      try {
        const manifestRes = await fetch('/build/manifest.json');
        if (manifestRes.ok) {
          const manifest = await manifestRes.json();
          const assetUrls = Object.values(manifest)
            .filter((entry) => entry && entry.file)
            .map((entry) => `/build/${entry.file}`);

          await Promise.all(
            assetUrls.map((url) =>
              fetch(url)
                .then((res) => {
                  if (res.ok) return cache.put(url, res);
                })
                .catch((e) => console.warn('[SW] Asset precache skip:', url, e))
            )
          );
          console.log('[SW] Pre-cached Vite build assets successfully');
        }
      } catch (err) {
        console.warn('[SW] Could not pre-cache Vite assets from manifest:', err);
      }

      return self.skipWaiting();
    })()
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

  // 3. API GET requests: Network-First, cache fallback, graceful offline 200 response
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
          // Return graceful empty 200 response so offline UI does not crash or display 503
          return new Response(JSON.stringify({
            success: true,
            data: [],
            message: 'You are currently offline. This request will sync once connection is restored.',
            offline: true,
            is_offline_fallback: true
          }), {
            status: 200,
            headers: { 'Content-Type': 'application/json' }
          });
        })
    );
    return;
  }

  // 4. Static assets (Vite /build/*, manifest.json, fonts, images): Cache-First, network fallback
  if (
    url.pathname.startsWith('/build/') ||
    url.pathname.endsWith('.woff2') ||
    url.pathname.endsWith('.woff') ||
    url.pathname.endsWith('.css') ||
    url.pathname.endsWith('.js') ||
    url.pathname.endsWith('.svg') ||
    url.pathname.endsWith('.png') ||
    url.pathname.endsWith('.ico') ||
    url.pathname.endsWith('.json') ||
    url.pathname === '/manifest.json'
  ) {
    event.respondWith(
      (async () => {
        // Check cache across all active caches
        const cached = await caches.match(request);
        if (cached) {
          return cached;
        }

        // Try network
        try {
          const networkResponse = await fetch(request);
          if (networkResponse && networkResponse.status === 200) {
            const copy = networkResponse.clone();
            const dynCache = await caches.open(DYNAMIC_CACHE);
            dynCache.put(request, copy);
          }
          return networkResponse;
        } catch (fetchErr) {
          console.warn('[SW] Offline asset fallback for:', request.url);

          // Manifest fallback
          if (url.pathname === '/manifest.json') {
            return new Response(JSON.stringify(FALLBACK_MANIFEST), {
              headers: { 'Content-Type': 'application/manifest+json' },
            });
          }

          // If offline and exact hash not found, look for any matching bundle in active caches
          const activeCaches = [SHELL_CACHE, DYNAMIC_CACHE];
          for (const cacheName of activeCaches) {
            const c = await caches.open(cacheName);
            const keys = await c.keys();
            const matchKey = keys.find((k) => {
              const u = new URL(k.url);
              if (url.pathname.endsWith('.js') && u.pathname.endsWith('.js') && u.pathname.includes('/app-')) return true;
              if (url.pathname.endsWith('.css') && u.pathname.endsWith('.css') && u.pathname.includes('/app-')) return true;
              return false;
            });
            if (matchKey) {
              const matched = await c.match(matchKey);
              if (matched) return matched;
            }
          }

          // Return graceful fallback response to avoid unhandled promise rejection
          if (url.pathname.endsWith('.css')) {
            return new Response('/* offline css */', {
              headers: { 'Content-Type': 'text/css' },
            });
          }
          if (url.pathname.endsWith('.js')) {
            return new Response('/* offline js */', {
              headers: { 'Content-Type': 'application/javascript' },
            });
          }
          if (url.pathname.endsWith('.json')) {
            return new Response('{}', {
              headers: { 'Content-Type': 'application/json' },
            });
          }

          return new Response(null, { status: 404, statusText: 'Offline Asset Unavailable' });
        }
      })()
    );
    return;
  }

  // 5. Default: Network with dynamic cache fallback - guaranteed to NEVER resolve to undefined
  event.respondWith(
    (async () => {
      try {
        const networkResponse = await fetch(request);
        if (networkResponse && networkResponse.status === 200) {
          const copy = networkResponse.clone();
          const dynCache = await caches.open(DYNAMIC_CACHE);
          dynCache.put(request, copy);
        }
        return networkResponse;
      } catch (err) {
        const cached = await caches.match(request);
        if (cached) {
          return cached;
        }

        if (url.pathname === '/manifest.json') {
          return new Response(JSON.stringify(FALLBACK_MANIFEST), {
            headers: { 'Content-Type': 'application/manifest+json' },
          });
        }

        if (url.pathname.endsWith('.json')) {
          return new Response('{}', {
            headers: { 'Content-Type': 'application/json' },
          });
        }

        return new Response(null, { status: 404, statusText: 'Offline Resource Unavailable' });
      }
    })()
  );
});
