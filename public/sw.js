// Este evento se dispara cuando el Service Worker se instala por primera vez
self.addEventListener('install', (event) => {
    console.log('Service Worker: Instalado');
    // Fuerza al Service Worker que está en espera a convertirse en el activo
    self.skipWaiting();
});

// Este evento se activa cuando el Service Worker toma el control de la página
self.addEventListener('activate', (event) => {
    console.log('Service Worker: Activado');
    return self.clients.claim();
});

// El evento fetch es obligatorio para que el navegador considere que es una PWA
self.addEventListener('fetch', (event) => {
    // Por ahora, simplemente deja que las peticiones sigan su curso normal
    // Aquí es donde en el futuro podrías programar el modo offline (Cache)
    event.respondWith(fetch(event.request));
});
