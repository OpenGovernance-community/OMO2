const OMO_CACHE_VERSION = 'omo-shell-v7';
const OMO_STATIC_ASSETS = [
    '/omo/manifest.php',
    '/omo/manifest_icon.php?size=192',
    '/omo/manifest_icon.php?size=512',
    '/omo/manifest_icon.php?size=512&purpose=maskable',
    '/omo/offline.html',
    '/omo/icons/icon-192.png',
    '/omo/icons/icon-512.png',
    '/omo/icons/icon-maskable-512.png',
];

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(OMO_CACHE_VERSION).then(function (cache) {
            return cache.addAll(OMO_STATIC_ASSETS);
        }).catch(function (error) {
            console.warn('OMO static cache installation failed.', error);
        })
    );

    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys.map(function (key) {
                    if (key.startsWith('omo-shell-') && key !== OMO_CACHE_VERSION) {
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

self.addEventListener('push', function (event) {
    let payload = {};
    try {
        payload = event.data ? event.data.json() : {};
    } catch (error) {
        payload = {};
    }

    const title = typeof payload.title === 'string' && payload.title.trim() !== ''
        ? payload.title.trim()
        : 'OMO';
    const body = typeof payload.body === 'string' ? payload.body.trim() : '';
    const url = typeof payload.url === 'string' && payload.url.startsWith('/omo/')
        ? payload.url
        : '/omo/';
    const tag = typeof payload.tag === 'string' && payload.tag.trim() !== ''
        ? payload.tag.trim()
        : undefined;

    event.waitUntil(
        self.registration.showNotification(title, {
            body: body,
            icon: '/omo/manifest_icon.php?size=192',
            badge: '/omo/manifest_icon.php?size=192',
            tag: tag,
            data: {url: url}
        })
    );
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    const url = event.notification && event.notification.data && typeof event.notification.data.url === 'string'
        ? event.notification.data.url
        : '/omo/';

    event.waitUntil(
        self.clients.matchAll({type: 'window', includeUncontrolled: true}).then(function (clients) {
            for (const client of clients) {
                if (client.url === new URL(url, self.location.origin).href && 'focus' in client) {
                    return client.focus();
                }
            }
            return self.clients.openWindow(url);
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
        || requestUrl.pathname === '/omo/manifest.php'
        || requestUrl.pathname === '/omo/manifest_icon.php'
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
