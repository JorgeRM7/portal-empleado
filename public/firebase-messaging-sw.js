

// 1. Importamos las librerías necesarias de Firebase (versión CDN)
importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js');

// 2. Configura Firebase con tus credenciales
// Estos datos los sacas de tu consola de Firebase (Configuración del proyecto)
const firebaseConfig = {
    apiKey: "AIzaSyAPYLedgFaUb1LTx8Lp_bl1EqtW9PZAamk",
    authDomain: "mi-portal-rh.firebaseapp.com",
    projectId: "mi-portal-rh",
    storageBucket: "mi-portal-rh.firebasestorage.app",
    messagingSenderId: "781235136464",
    appId: "1:781235136464:web:5e124b3ebb43c2c5ea3bd5"
};

// 3. Inicializa Firebase
firebase.initializeApp(firebaseConfig);

// 4. Define el Service Worker de mensajería
const messaging = firebase.messaging();

// 5. Lógica para manejar mensajes cuando la app está en segundo plano (cerrada)
messaging.onBackgroundMessage((payload) => {
    console.log('[firebase-messaging-sw.js] Mensaje recibido en segundo plano: ', payload);

    const notificationTitle = payload.notification.title;
    const notificationOptions = {
        body: payload.notification.body,
        icon: '/logo.png' // Ruta a tu logo en la carpeta public
    };

    self.registration.showNotification(notificationTitle, notificationOptions);
});
