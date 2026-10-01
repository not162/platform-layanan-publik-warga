const CACHE_NAME = 'lpw-static-v1';
const OFFLINE_URL = '/offline';

const PRECACHE_ASSETS = [
    '/',
    OFFLINE_URL,
    '/images/favicon.svg',
    '/images/logo-horizontal.svg',
    '/images/logo-symbol.svg',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRECACHE_ASSETS);
        }).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // 1. NEVER cache private API or administrative mutation routes
    if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/admin') || url.pathname.startsWith('/sanctum')) {
        return; // Normal network request
    }

    // 2. Navigation requests: Network first, fall back to offline page
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => {
                return caches.match(OFFLINE_URL);
            })
        );
        return;
    }

    // 3. Static assets: Cache First, fallback to Network
    if (
        url.pathname.startsWith('/images/') ||
        url.pathname.endsWith('.css') ||
        url.pathname.endsWith('.js') ||
        url.pathname.endsWith('.svg') ||
        url.pathname.endsWith('.woff2')
    ) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseToCache = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseToCache);
                        });
                    }
                    return networkResponse;
                });
            })
        );
    }
});
