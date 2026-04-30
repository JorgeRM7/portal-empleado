<script setup>
import { useLayout } from "./composables/layout";
import { computed, ref, watch, onMounted } from "vue";
import AppFooter from "./AppFooter.vue";
import AppSidebar from "./AppSideBar.vue";
import AppTopbar from "./AppTopBar.vue";
import { Head } from "@inertiajs/vue3";
import Toast from "@/Components/Toast.vue";
import Assistant from "@/Components/Assistant.vue";
import { usePage, router } from "@inertiajs/vue3";
import { useToast } from "primevue/usetoast";
import InactivityTimer from '@/Components/InactivityTimer.vue';
import { obtenerTokenReal } from "../firebase";
import { getMessaging, onMessage } from "firebase/messaging";


const page = usePage();
const loadingTerms = ref(false);
const showTermsModal = ref(false);
const toast = useToast();
const showWarningNotifications = ref(false);
const isPermanentlyBlocked = ref(false);
const messaging = getMessaging();
const deferredPrompt = ref(null);

const acceptTerms = async () => {
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

    // Si es iOS y no está "instalada" la app
    if (isIOS && !window.navigator.standalone) {
        toast.add({
            severity: 'warn',
            summary: 'Atención usuario de iPhone',
            detail: 'Para recibir notificaciones, pulsa el botón compartir y selecciona "Añadir a la pantalla de inicio".',
            life: 8000
        });
        return;
    }

    const userId = page.props.auth?.user?.id;
    if (!userId) return;

    // Revisamos el estado actual del permiso
    if (Notification.permission === 'denied') {
        isPermanentlyBlocked.value = true;
        showWarningNotifications.value = true;
        return;
    }

    const permission = await Notification.requestPermission();

    if (permission === 'granted') {
        const tokenReal = await obtenerTokenReal();
        if (tokenReal) {
            confirmSelection(tokenReal);
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error de Red',
                detail: 'No pudimos conectar con el servidor de notificaciones. Intenta de nuevo.',
                life: 4000
            });
        }
    } else {
        isPermanentlyBlocked.value = (permission === 'denied');
        showWarningNotifications.value = true;
    }
};

const confirmSelection = (token) => {
    router.put(route('term-conditions.update', { term_condition: page.props.auth.user.id }), {
        device_token: token
    }, {
        onBefore: () => { loadingTerms.value = true; },
        onSuccess: () => {
            showTermsModal.value = false;
            showWarningNotifications.value = false;
            router.reload({ only: ['auth'], preserveState: false });

            // --- LÓGICA DE ACCESO DIRECTO AQUÍ ---
            if (deferredPrompt.value) {
                // Si el navegador capturó el evento (Android / Chrome)
                deferredPrompt.value.prompt();
                deferredPrompt.value.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('Usuario aceptó instalar el acceso directo');
                    }
                    deferredPrompt.value = null; // Limpiamos el evento
                });
            }

            toast.add({
                severity: 'success',
                summary: '¡Éxito!',
                detail: 'Has aceptado los términos. Ya puedes navegar.',
                life: 4000
            });
        },
        onFinish: () => { loadingTerms.value = false; }
    });
};

const logout = () => {
    router.post(route('logout'), {}, {
        onBefore: () => {
            loadingTerms.value = true;
        },
        onSuccess: () => {
            console.log("Sesión cerrada.");
        }
    });
};

watch(
    () => page.props.auth?.user?.terms_condition,
    (value) => {
        showTermsModal.value = Number(value) !== 1;
    },
    { immediate: true }
);

const { layoutConfig, layoutState, isSidebarActive } = useLayout();

const outsideClickListener = ref(null);

const props = defineProps({
    title: String,
});

watch(isSidebarActive, (newVal) => {
    if (newVal) {
        bindOutsideClickListener();
    } else {
        unbindOutsideClickListener();
    }
});

const containerClass = computed(() => {
    return {
        "layout-overlay": layoutConfig.menuMode === "overlay",
        "layout-static": layoutConfig.menuMode === "static",
        "layout-static-inactive":
            layoutState.staticMenuDesktopInactive &&
            layoutConfig.menuMode === "static",
        "layout-overlay-active": layoutState.overlayMenuActive,
        "layout-mobile-active": layoutState.staticMenuMobileActive,
    };
});

function bindOutsideClickListener() {
    if (!outsideClickListener.value) {
        outsideClickListener.value = (event) => {
            if (isOutsideClicked(event)) {
                layoutState.overlayMenuActive = false;
                layoutState.staticMenuMobileActive = false;
                layoutState.menuHoverActive = false;
            }
        };
        document.addEventListener("click", outsideClickListener.value);
    }
}

function unbindOutsideClickListener() {
    if (outsideClickListener.value) {
        document.removeEventListener("click", outsideClickListener.value);
        outsideClickListener.value = null;
    }
}

function isOutsideClicked(event) {
    const sidebarEl = document.querySelector(".layout-sidebar");
    const topbarEl = document.querySelector(".layout-menu-button");

    if (!sidebarEl || !topbarEl) return false;

    try {
        return !(
            sidebarEl.isSameNode(event.target) ||
            sidebarEl.contains(event.target) ||
            topbarEl.isSameNode(event.target) ||
            topbarEl.contains(event.target)
        );
    } catch (e) {
        return false;
    }
}

onMounted(() => {
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt.value = e;
    });
    // onMessage(messaging, (payload) => {
    //     console.log('Mensaje recibido en primer plano:', payload);

    //     if (Notification.permission === "granted") {
    //         new Notification(payload.notification.title, {
    //             body: payload.notification.body,
    //             icon: '/logo.png',
    //         });
    //     }

    //     toast.add({
    //         severity: 'info',
    //         summary: payload.notification.title || 'Aviso de Portal RH',
    //         detail: payload.notification.body || 'Tienes una nueva actualización.',
    //         life: 6000
    //     });
    // });
});
</script>

<template>
    <div class="layout-wrapper" :class="containerClass">
        <Head :title="props.title" />
        <app-topbar></app-topbar>
        <app-sidebar></app-sidebar>
        <div class="layout-main-container">
            <!-- <div class="layout-main">
                <slot />
            </div> -->
            <div class="layout-main">
                <div :class="{ 'pointer-events-none opacity-50': showTermsModal }">
                    <slot />
                </div>
            </div>
            <app-footer></app-footer>
        </div>
        <!-- <Assistant use-backend="true" endpoint="/chat" /> -->
        <InactivityTimer :on-logout="logout" />
        <div class="layout-mask animate-fadein"></div>
    </div>
    <Toast />
    <Dialog
        v-model:visible="showTermsModal"
        header="Términos y Condiciones de Uso"
        :modal="true"
        :closable="false"
        :closeOnEscape="false"
        :draggable="false"
        class="mx-4 w-full md:w-[650px]"
    >
        <div class="flex flex-col gap-4">
            <div class="p-4 rounded-2xl flex items-start gap-3">
                <InlineMessage severity="info">Para continuar utilizando el <strong>Mi Portal RH</strong>, es necesario que leas y aceptes los términos de confidencialidad y uso de datos.</InlineMessage>
            </div>

            <div class="p-6 rounded-xl border border-gray-100 dark:border-gray-700 max-h-[350px] overflow-y-auto text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                <h3 class="font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2">
                    <i class="pi pi-shield text-emerald-500"></i>
                    Aviso de Privacidad y Confidencialidad
                </h3>

                <ul class="flex flex-col gap-4 list-none p-0">
                    <li class="flex gap-3">
                        <i class="pi pi-user-focus mt-1 text-blue-500"></i>
                        <span><strong>Propiedad de la Información:</strong> Reconoces que los datos personales y laborales mostrados en este portal te pertenecen. La plataforma actúa únicamente como un medio informativo para facilitar tu acceso a ellos.</span>
                    </li>

                    <li class="flex gap-3">
                        <i class="pi pi-eye-slash mt-1 text-orange-500"></i>
                        <span><strong>Finalidad No Lucrativa:</strong> Este portal tiene fines estrictamente informativos y administrativos. Se prohíbe el uso de la información aquí contenida para cualquier fin comercial o de lucro ajeno a la relación laboral.</span>
                    </li>

                    <li class="flex gap-3">
                        <i class="pi pi-comments mt-1 text-purple-500"></i>
                        <span><strong>Canales de Quejas:</strong> Contamos con un sistema de quejas y sugerencias. Te garantizamos que el proceso de reporte es <strong>completamente anónimo</strong>, diseñado para proteger tu integridad y fomentar un ambiente laboral seguro.</span>
                    </li>

                    <li class="flex gap-3">
                        <i class="pi pi-lock mt-1 text-emerald-500"></i>
                        <span><strong>Protección de Datos:</strong> Nos comprometemos a no compartir, vender ni utilizar tu información personal para fines distintos a los informativos internos de la organización.</span>
                    </li>
                </ul>

                <div class="mt-6 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-xs border border-blue-100 dark:border-blue-800 italic">
                    Al hacer clic en "Aceptar y continuar", confirmas que has leído y estás de acuerdo con el manejo de tus datos bajo los lineamientos anteriormente descritos.
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-end gap-3 mt-2">
                <Button
                    label="No acepto"
                    icon="pi pi-sign-out"
                    :icon="loadingTerms ? 'pi pi-spin pi-spinner' : 'pi pi-sign-out'"
                    :loading="loadingTerms"
                    severity="danger"
                    text
                    @click="logout"
                    class="w-full sm:w-auto"
                />
                <Button
                    label="Aceptar y continuar"
                    icon="pi pi-check"
                    :icon="loadingTerms ? 'pi pi-spin pi-spinner' : 'pi pi-check'"
                    :loading="loadingTerms"
                    severity="success"
                    @click="acceptTerms"
                    class="w-full sm:w-auto shadow-lg shadow-emerald-500/20"
                    raised
                />
            </div>
        </div>
    </Dialog>
    <Dialog
        v-model:visible="showWarningNotifications"
        header="Configuración Requerida"
        :modal="true"
        :closable="false"
        class="mx-4 w-full md:w-[500px]"
    >
        <div class="flex flex-col gap-4">
            <div class="flex items-center gap-3 text-red-600">
                <i class="pi pi-bell-slash text-4xl"></i>
                <p class="font-bold text-xl">Notificaciones Desactivadas</p>
            </div>

            <p class="text-sm text-gray-700 dark:text-gray-300">
                Para continuar en <strong>Mi Portal RH</strong>, debes permitir las notificaciones. Esto nos permite enviarte tus recibos y avisos importantes.
            </p>

            <div v-if="isPermanentlyBlocked" class=" p-4 rounded-lg border border-blue-200">
                <p class="text-xs font-bold mb-2 flex items-center gap-2">
                    <i class="pi pi-info-circle"></i> CÓMO ACTIVARLAS:
                </p>
                <ol class="text-xs pl-4 list-decimal flex flex-col gap-2">
                    <li>Haz clic en el icono del <strong>candado (🔒)</strong> o de <strong>ajustes o controles (⊶)</strong> situado a la izquierda de la dirección web en la parte superior.</li>
                    <li>Si ya habías bloqueado los permisos anteriormente, ahí verás la opción de <strong>Notificaciones</strong> desactivada.</li>
                    <li>Activa el interruptor de <strong>Notificaciones</strong> para permitir que el portal te envíe avisos.</li>
                    <li>Cierra ese pequeño menú, regresa aquí y pulsa el botón de abajo.</li>
                </ol>
            </div>

            <div class="flex flex-col gap-2 mt-4">
                <Button
                    label="Ya las activé, intentar de nuevo"
                    severity="success"
                    icon="pi pi-refresh"
                    @click="acceptTerms"
                    class="w-full"
                />
                <p class="text-[10px] text-center">
                    Si el problema persiste, refresca la página ó presiona F5 en tu teclado.
                </p>
            </div>
        </div>
    </Dialog>
</template>
