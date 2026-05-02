importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey: "AIzaSyAPYLedgFaUb1LTx8Lp_bl1EqtW9PZAamk",
    authDomain: "mi-portal-rh.firebaseapp.com",
    projectId: "mi-portal-rh",
    storageBucket: "mi-portal-rh.firebasestorage.app",
    messagingSenderId: "781235136464",
    appId: "1:781235136464:web:5e124b3ebb43c2c5ea3bd5"
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
    console.log('[firebase-messaging-sw.js] Mensaje recibido: ', payload);

    const notificationTitle =
        payload.notification?.title || payload.data?.title || "Nuevo mensaje de Mi Portal RH";

    const notificationOptions = {
        body: payload.notification?.body || payload.data?.body || "Tienes una nueva actualización.",
        icon: '/icons/logo.png',
        badge: '/logo-small.png',
        vibrate: [200, 100, 200],
        tag: 'notificacion-rh-' + (payload.data?.news_id || Date.now()),
        renotify: true,
        requireInteraction: true,
        data: {
            url: payload.data?.url || 'https://sandbox.miportalrh.com/dashboard'
        }
    };

    self.registration.showNotification(notificationTitle, notificationOptions);
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();

    const url = event.notification.data?.url || 'https://sandbox.miportalrh.com/dashboard';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if (client.url.includes('sandbox.miportalrh.com') && 'focus' in client) {
                    client.focus();
                    client.navigate(url);
                    return;
                }
            }

            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});
