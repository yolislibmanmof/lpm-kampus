/* ============================================================
   LPM KAMPUS — SERVICE WORKER v10 (AMAN & PREMIUM)
   Prinsip: JANGAN pernah cache halaman ber-sesi / data sensitif.
   - Navigasi (HTML): network-first, fallback offline.html. Tidak di-cache.
   - Aset statis (/assets/, manifest, offline): stale-while-revalidate.
   - /sim/*, /uploads/, /download.php, login/logout/2fa: TIDAK disentuh.
============================================================ */
'use strict';

var VERSION = 'lpm-v10';
var STATIC_CACHE = VERSION + '-static';
var PRECACHE = [
    '/offline.html',
    '/assets/css/style.css',
    '/assets/js/main.js',
    '/manifest.json',
    '/assets/icons/icon-192.png',
    '/assets/icons/icon-512.png'
];

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(STATIC_CACHE).then(function (cache) {
            return Promise.all(PRECACHE.map(function (url) {
                return cache.add(url).catch(function () {});
            }));
        }).then(function () { return self.skipWaiting(); })
    );
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(keys.map(function (k) {
                if (k.indexOf(VERSION) !== 0) return caches.delete(k);
            }));
        }).then(function () { return self.clients.claim(); })
    );
});

function isPrivatePath(pathname) {
    return (
        pathname.indexOf('/sim/') === 0 ||
        pathname.indexOf('/config/') === 0 ||
        pathname.indexOf('/core/') === 0 ||
        pathname.indexOf('/includes/') === 0 ||
        pathname.indexOf('/tools/') === 0 ||
        pathname.indexOf('/uploads/') === 0 ||
        pathname === '/download.php' ||
        pathname === '/login.php' ||
        pathname === '/logout.php' ||
        pathname === '/2fa-verify.php'
    );
}

function isStaticAsset(pathname) {
    return (
        pathname.indexOf('/assets/') === 0 ||
        pathname === '/manifest.json' ||
        pathname === '/offline.html'
    );
}

self.addEventListener('fetch', function (event) {
    var req = event.request;
    if (req.method !== 'GET') return;

    var url = new URL(req.url);
    if (url.origin !== self.location.origin) return;
    if (isPrivatePath(url.pathname)) return;

    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req).catch(function () {
                return caches.match('/offline.html').then(function (r) {
                    return r || new Response('<h1>Offline</h1>', { headers: { 'Content-Type': 'text/html' } });
                });
            })
        );
        return;
    }

    if (isStaticAsset(url.pathname)) {
        event.respondWith(
            caches.match(req).then(function (cached) {
                var network = fetch(req).then(function (res) {
                    if (res && res.ok && res.type === 'basic' && !res.headers.get('Set-Cookie')) {
                        var copy = res.clone();
                        caches.open(STATIC_CACHE).then(function (c) { c.put(req, copy); });
                    }
                    return res;
                }).catch(function () { return cached; });
                return cached || network;
            })
        );
    }
});

self.addEventListener('push', function (event) {
    var data = {};
    try { data = event.data ? event.data.json() : {}; } catch (e) {}
    var title = data.title || 'SIM-Mutu LPM';
    event.waitUntil(self.registration.showNotification(title, {
        body: data.body || 'Ada notifikasi baru untuk Anda.',
        icon: '/assets/icons/icon-192.png',
        badge: '/assets/icons/icon-72.png',
        data: { url: data.url || '/sim/index.php' },
        vibrate: [100, 50, 100]
    }));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    var target = (event.notification.data && event.notification.data.url) || '/sim/index.php';
    if (typeof target !== 'string' || target.charAt(0) !== '/' || target.indexOf('//') === 0) target = '/sim/index.php';
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (list) {
            for (var i = 0; i < list.length; i++) {
                if ('focus' in list[i]) { list[i].navigate(target); return list[i].focus(); }
            }
            return self.clients.openWindow(target);
        })
    );
});

self.addEventListener('message', function (event) {
    if (event.data === 'SKIP_WAITING') self.skipWaiting();
});