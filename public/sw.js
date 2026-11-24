const CACHE = 'jbtb-static-v3';

const PRECACHE_URLS = [
    '/manifest.webmanifest',
    '/favicon.ico',
    '/images/brand/icon-192.png',
    '/images/brand/icon-512.png',
    '/images/brand/icon-192-maskable.png',
    '/images/brand/icon-512-maskable.png',
];

const NEVER_CACHE_PREFIXES = ['/media', '/pembayaran', '/kontrak', '/invoice'];

const STATIC_ASSET_RE = /\.(css|woff2?|ttf|otf|png|jpe?g|svg|ico|webmanifest)$/i;

const OFFLINE_HTML = `<!doctype html>
<html lang="id"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Offline - JBTB Casting</title>
<style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:24px;text-align:center;background:#F7F4EE;color:#1A1714}</style>
</head><body><p>Kamu sedang offline. Coba lagi setelah tersambung internet.</p></body></html>`;

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((cache) => cache.addAll(PRECACHE_URLS)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))
        )).then(() => self.clients.claim())
    );
});

function isNeverCache(pathname) {
    return NEVER_CACHE_PREFIXES.some((prefix) => pathname.startsWith(prefix));
}

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    const sameOrigin = url.origin === self.location.origin;

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => new Response(OFFLINE_HTML, {
                headers: { 'Content-Type': 'text/html; charset=utf-8' },
            }))
        );
        return;
    }

    if (!sameOrigin || isNeverCache(url.pathname)) return;

    if (STATIC_ASSET_RE.test(url.pathname)) {
        event.respondWith(
            caches.match(request).then((cached) => cached || fetch(request).then((response) => {
                if (response.ok) {
                    const clone = response.clone();
                    caches.open(CACHE).then((cache) => cache.put(request, clone));
                }
                return response;
            }))
        );
    }
});
