const CACHE_NAME = 'domiciliario-v1';
const urlsToCache = [
    '/Restaurante/domiciliario.php',
    '/Restaurante/css/style.css', // Assumed path, might need adjustment if actual CSS paths differ
    '/Restaurante/manifest.json',
    '/Restaurante/assets/icon-192.png',
    '/Restaurante/assets/icon-512.png'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Opened cache');
                return cache.addAll(urlsToCache).catch(err => {
                    console.warn('Algunos archivos no se pudieron cachear:', err);
                    // No fallamos la instalación si falta un archivo, pero logueamos
                });
            })
    );
});

self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Cache hit - return response
                if (response) {
                    return response;
                }
                return fetch(event.request);
            })
    );
});
