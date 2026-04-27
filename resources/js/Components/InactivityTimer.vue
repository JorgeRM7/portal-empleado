<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';

const props = defineProps({
    onLogout: Function
});

// const TOTAL_TIMEOUT = .5 * 60 * 1000;
// const WARNING_BEFORE = 10 * 1000;

const TOTAL_TIMEOUT = 2.5 * 60 * 1000;
const WARNING_BEFORE = 30 * 1000;

const showDialog = ref(false);
const countdown = ref(30);
let idleTimer = null;
let warningTimer = null;
let countdownInterval = null;
let lastActivity = Date.now();

const executeLogout = () => {
    stopAllTimers();
    showDialog.value = false; // Cerramos el diálogo antes de disparar el logout
    if (props.onLogout) {
        props.onLogout();
    }
};

const stopAllTimers = () => {
    clearTimeout(idleTimer);
    clearTimeout(warningTimer);
    clearInterval(countdownInterval);
};

const startCountdown = () => {
    countdown.value = 30;
    clearInterval(countdownInterval);
    countdownInterval = setInterval(() => {
        if (countdown.value > 0) {
            countdown.value--;
        } else {
            clearInterval(countdownInterval);
            executeLogout();
        }
    }, 1000);
};

// Esta función se activa con el mouse, pero NO quita el diálogo si ya está visible
const handleUserActivity = () => {
    if (showDialog.value) return; // SI EL DIÁLOGO ESTÁ ABIERTO, NO HACEMOS NADA

    // Solo reiniciamos timers si ha pasado más de 2 segundos (optimización)
    if (Date.now() - lastActivity < 2000) return;

    lastActivity = Date.now();
    startIdleTimer();
};

// Esta función es EXCLUSIVA para el botón "Seguir trabajando"
const keepAlive = () => {
    showDialog.value = false;
    stopAllTimers();
    startIdleTimer();
};

const startIdleTimer = () => {
    stopAllTimers();
    // Timer para mostrar el aviso
    warningTimer = setTimeout(() => {
        showDialog.value = true;
        startCountdown();
    }, TOTAL_TIMEOUT - WARNING_BEFORE);

    // Timer de respaldo
    idleTimer = setTimeout(executeLogout, TOTAL_TIMEOUT);
};

onMounted(() => {
    const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'];
    // Usamos handleUserActivity para los eventos de ventana
    events.forEach(event => window.addEventListener(event, handleUserActivity));
    startIdleTimer();
});

onUnmounted(() => {
    const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'];
    events.forEach(event => window.removeEventListener(event, handleUserActivity));
    stopAllTimers();
});
</script>

<template>
    <Dialog
        v-model:visible="showDialog"
        header="Sesión por expirar"
        :modal="true"
        :closable="false"
        :draggable="false"
        class="mx-4 w-full md:w-[400px]"
    >
        <div class="flex flex-col gap-4">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-4xl"></i>
                <div class="flex flex-col text-left">
                    <span class="font-bold text-lg">¿Sigues ahí?</span>
                    <p class="text-sm leading-tight">
                        Tu sesión se cerrará automáticamente por inactividad.
                    </p>
                </div>
            </div>

            <div class="p-4 rounded-xl border text-center">
                <p class="text-xs uppercase tracking-wider mb-1 font-semibold italic">Cierre de sesión en</p>
                <span class="text-3xl font-mono font-bold">
                    00:{{ countdown < 10 ? '0' + countdown : countdown }}
                </span>
            </div>

            <div class="flex justify-end gap-2 mt-2">
                <Button
                    label="Cerrar sesión"
                    icon="pi pi-sign-out"
                    severity="danger"
                    text
                    @click="executeLogout"
                />
                <Button
                    label="Seguir trabajando"
                    icon="pi pi-check"
                    severity="success"
                    @click="keepAlive"
                    raised
                />
            </div>
        </div>
    </Dialog>
</template>
