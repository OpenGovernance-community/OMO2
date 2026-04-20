const OMO_CACHE_VERSION = 'omo-shell-v3';
const OMO_STATIC_ASSETS = [
    '/omo/manifest.json',
    '/omo/offline.html',
    '/omo/icons/icon-192.png',
    '/omo/icons/icon-512.png',
    '/omo/icons/icon-maskable-512.png',
];

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(OMO_CACHE_VERSION).then(function (cache) {
            return cache.addAll(OMO_STATIC_ASSETS);
        })
    );

    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys.map(function (key) {
                    if (key !== OMO_CACHE_VERSION) {
                        return caches.delete(key);
                    }

                    return Promise.resolve(false);
                })
            );
        }).then(function () {
            return self.clients.claim();
        })
    );
});

self.addEventListener('fetch', function (event) {
    if (event.request.method !== 'GET') {
        return;
    }

    const requestUrl = new URL(event.request.url);

    if (requestUrl.origin !== self.location.origin || !requestUrl.pathname.startsWith('/omo/')) {
        return;
    }

    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(function () {
                return caches.match('/omo/offline.html');
            })
        );
        return;
    }

    const isStaticAsset = (
        requestUrl.pathname.startsWith('/omo/assets/')
        || requestUrl.pathname.startsWith('/omo/icons/')
        || requestUrl.pathname === '/omo/manifest.json'
    );

    if (!isStaticAsset) {
        return;
    }

    event.respondWith(
        fetch(event.request).then(function (networkResponse) {
            const responseToCache = networkResponse.clone();

            caches.open(OMO_CACHE_VERSION).then(function (cache) {
                cache.put(event.request, responseToCache);
            });

            return networkResponse;
        }).catch(function () {
            return caches.match(event.request);
        })
    );
});
