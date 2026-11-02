const CACHE_VERSION = 'v4.1';
const STATIC_CACHE = `lpm-static-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `lpm-dynamic-${CACHE_VERSION}`;

// Aset yang di-cache saat install
const STATIC_ASSETS = [
  '/',
  '/index.php',
  '/assets/css/style.css',
  '/assets/js/main.js',
  '/manifest.json',
  '/assets/icons/icon-192.png',
  '/assets/icons/icon-512.png'
];

// Install: cache aset static
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => cache.addAll(STATIC_ASSETS))
      .then(() => self.skipWaiting())
  );
});

// Activate: hapus cache lama
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then(keys => 
      Promise.all(
        keys.filter(key => key !== STATIC_CACHE && key !== DYNAMIC_CACHE)
            .map(key => caches.delete(key))
      )
    ).then(() => self.clients.claim())
  );
});

// Fetch: strategi hybrid
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Skip cross-origin & non-GET
  if (url.origin !== location.origin || request.method !== 'GET') return;
  
  // Skip API & admin (selalu online)
  if (url.pathname.startsWith('/sim/api/') || 
      url.pathname.startsWith('/sim/admin/') ||
      url.pathname.startsWith('/sim/prodi/') ||
      url.pathname.startsWith('/login.php') ||
      url.pathname.startsWith('/2fa-verify.php')) {
    return;
  }

  // Strategi: Stale-while-revalidate untuk static, Network-first untuk page
  if (request.destination === 'document') {
    // Network-first untuk HTML (selalu coba online dulu)
    event.respondWith(
      fetch(request)
        .then(response => {
          const clone = response.clone();
          caches.open(DYNAMIC_CACHE).then(cache => cache.put(request, clone));
          return response;
        })
        .catch(() => caches.match(request).then(cached => 
          cached || caches.match('/offline.html')
        ))
    );
  } else {
    // Cache-first untuk static (CSS, JS, image)
    event.respondWith(
      caches.match(request).then(cached => 
        cached || fetch(request).then(response => {
          if (response.ok) {
            const clone = response.clone();
            caches.open(DYNAMIC_CACHE).then(cache => cache.put(request, clone));
          }
          return response;
        }).catch(() => {
          // Fallback untuk gambar
          if (request.destination === 'image') {
            return new Response('', { status: 404 });
          }
        })
      )
    );
  }
});

// Background sync (untuk form submission saat offline)
self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-edp') {
    event.waitUntil(syncEdpData());
  }
});

async function syncEdpData() {
  // Implementasi custom jika diperlukan
  console.log('Background sync: EDP data');
}

// Push notifications (siap untuk future use)
self.addEventListener('push', (event) => {
  const data = event.data ? event.data.json() : { title: 'SIM-Mutu', body: 'Ada notifikasi baru' };
  event.waitUntil(
    self.registration.showNotification(data.title, {
      body: data.body,
      icon: '/assets/icons/icon-192.png',
      badge: '/assets/icons/icon-72.png',
      vibrate: [200, 100, 200],
      data: { url: data.url || '/sim/index.php' }
    })
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  event.waitUntil(
    clients.openWindow(event.notification.data.url)
  );
});