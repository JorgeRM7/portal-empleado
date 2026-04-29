import { initializeApp } from "firebase/app";
import { getMessaging, getToken } from "firebase/messaging";

const firebaseConfig = {
    apiKey: "AIzaSyAPYLedgFaUb1LTx8Lp_bl1EqtW9PZAamk",
    authDomain: "mi-portal-rh.firebaseapp.com",
    projectId: "mi-portal-rh",
    storageBucket: "mi-portal-rh.firebasestorage.app",
    messagingSenderId: "781235136464",
    appId: "1:781235136464:web:5e124b3ebb43c2c5ea3bd5"
};

const app = initializeApp(firebaseConfig);
const messaging = getMessaging(app);

export const obtenerTokenReal = async () => {
    try {
        const token = await getToken(messaging, {
            vapidKey: "BPfIHHZvG0mx-GahtKONESG9O2av4yhua3kroTYDxgYI5UsfpZQrZSdQqyiTw88QENhb3aBgo-bo3YTK_BSlbNc"
        });
        return token;
    } catch (error) {
        console.error("Error al obtener token:", error);
        return null;
    }
};
