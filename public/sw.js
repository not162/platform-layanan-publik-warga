/* PRISMA RT 04 — service worker
 * - Aset statis: cache-first (stale-while-revalidate)
 * - Navigasi: network-first, fallback ke /offline
 * - /api, /login, /logout, dan semua request non-GET TIDAK pernah di-cache (data harus real-time & privat)
 */
const VERSION = 'prisma-v4';
const STATIC_CACHE = `${VERSION}-static`;
const OFFLINE_URL = '/offline';
const PRECACHE = [OFFLINE_URL, '/css/prisma.css', '/js/prisma.js', '/images/prisma-icon.svg', '/images/prisma-mark.svg', '/icons/icon-192.png'];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(STATIC_CACHE).then((c) => c.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => !k.startsWith(VERSION)).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const { request } = event;
  if (request.method !== 'GET') return;
  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;
  if (/^\/(api|sanctum|login|logout|register|dashboard\/finance)/.test(url.pathname)) return;

  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
    return;
  }

  if (/\.(css|js|svg|png|jpg|webp|woff2?)$/.test(url.pathname)) {
    event.respondWith(
      caches.open(STATIC_CACHE).then(async (cache) => {
        const cached = await cache.match(request);
        const network = fetch(request).then((res) => { if (res && res.status === 200) cache.put(request, res.clone()); return res; }).catch(() => cached);
        return cached || network;
      })
    );
  }
});

self.addEventListener('push', (event) => {
  let data = {};
  try { data = event.data ? event.data.json() : {}; } catch (e) { /* abaikan */ }
  event.waitUntil(self.registration.showNotification(data.title || 'PRISMA RT 04', { body: data.body || '', icon: '/icons/icon-192.png', badge: '/icons/icon-192.png', data: { url: data.url || '/dashboard' } }));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  event.waitUntil(self.clients.openWindow((event.notification.data && event.notification.data.url) || '/dashboard'));
});
