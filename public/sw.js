// const CACHE_NAME = 'mi-portal-rh-v1';
// const OFFLINE_URL = '/offline'; // Opcional: una ruta simple para errores de red

// self.addEventListener('install', (event) => {
//     self.skipWaiting();
//     // Pre-cachear recursos críticos si lo deseas
// });

// self.addEventListener('activate', (event) => {
//     event.waitUntil(self.clients.claim());
// });

// self.addEventListener('fetch', (event) => {
//     // Solo cacheamos peticiones GET (no las de Firebase o API dinámicas)
//     if (event.request.method !== 'GET') return;

//     event.respondWith(
//         fetch(event.request)
//             .catch(() => {
//                 return caches.match(event.request);
//             })
//     );
// });

// // Mantén aquí tu lógica de Firebase Push que ya tenías
// self.addEventListener('push', function(event) {
//     // ... tu código de notificaciones actual
// });


const CACHE_NAME = "portal-rh-v1";

self.addEventListener("install", (event) => {
    self.skipWaiting();
});

self.addEventListener("activate", (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
    // Solo cacheamos peticiones GET (no las de Firebase o API dinámicas)
    if (event.request.method !== 'GET') return;

    event.respondWith(
        fetch(event.request)
            .catch(() => {
                return caches.match(event.request);
            })
    );
});

// Mantén aquí tu lógica de Firebase Push que ya tenías
self.addEventListener('push', function(event) {
    // ... tu código de notificaciones actual
});
