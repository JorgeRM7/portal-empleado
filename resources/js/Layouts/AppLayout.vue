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
const showInstallModal = ref(false);

const acceptTerms = async () => {
    showWarningNotifications.value = false;

    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

    if (isIOS && !window.navigator.standalone) {
        toast.add({
            severity: 'warn',
            summary: 'Atención usuario de iPhone',
            detail: 'Para recibir notificaciones, pulsa el botón compartir y selecciona "Añadir a la pantalla de inicio".',
            life: 10000
        });
        return;
    }

    const userId = page.props.auth?.user?.id;
    if (!userId) return;

    if (Notification.permission === 'denied') {
        isPermanentlyBlocked.value = true;
        showWarningNotifications.value = true;
        showTermsModal.value = false;
        return;
    }

    try {
        loadingTerms.value = true;
        const permission = await Notification.requestPermission();

        if (permission === 'granted') {
            const tokenReal = await obtenerTokenReal();

            if (tokenReal) {
                confirmSelection(tokenReal);
            } else {
                loadingTerms.value = false;
                toast.add({
                    severity: 'error',
                    summary: 'Error de Configuración',
                    detail: 'No pudimos generar el token. Intenta refrescar la página.',
                    life: 4000
                });
            }
        } else {
            isPermanentlyBlocked.value = (permission === 'denied');
            showWarningNotifications.value = true;
            showTermsModal.value = false;
            loadingTerms.value = false;
        }
    } catch (error) {
        loadingTerms.value = false;
        console.error("Error en el proceso de aceptación:", error);
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

            router.reload({
                only: ['auth'],
                preserveState: false,
                onFinish: () => {
                    installApp();
                }
            });

            toast.add({
                severity: 'success',
                summary: '¡Configuración Completa!',
                detail: 'Términos aceptados y notificaciones activadas.',
                life: 4000
            });
        },
        onFinish: () => { loadingTerms.value = false; },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Hubo un problema al guardar tus datos. Intenta nuevamente.',
                life: 4000
            });
        }
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
        // 1. Evitamos que Chrome muestre su propio banner feo
        e.preventDefault();
        // 2. Guardamos el evento en nuestra variable reactiva
        deferredPrompt.value = e;
        console.log("Evento de instalación capturado y listo.");
    });

    window.addEventListener('appinstalled', () => {
        deferredPrompt.value = null;
        console.log('PWA instalada con éxito');
    });
});


const installApp = async () => {
    if (!deferredPrompt.value) return;

    // Mostramos el prompt del navegador
    deferredPrompt.value.prompt();

    // Esperamos la respuesta del usuario
    const { outcome } = await deferredPrompt.value.userChoice;

    if (outcome === 'accepted') {
        console.log('Usuario aceptó la instalación');
    }

    deferredPrompt.value = null;
    showInstallModal.value = false;
};
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
            <div v-if="deferredPrompt" class="fixed bottom-8 right-8 z-[9999] animate-bounce-slow">
                <Button
                    icon="pi pi-download"
                    severity="success"
                    rounded
                    raised
                    @click="installApp"
                    v-tooltip.left="'Instalar aplicación'"
                    class="w-16 h-16 shadow-2xl !bg-emerald-500 !border-none"
                />
            </div>
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
                    Si el problema persiste, refresca la página.
                </p>
            </div>
        </div>
    </Dialog>
</template>
<style scoped>
.animate-bounce-slow {
    animation: bounce 3s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
    40% {transform: translateY(-10px);}
    60% {transform: translateY(-5px);}
}

/* Ajuste para que no tape contenido importante en móviles */
@media (max-width: 768px) {
    .fixed {
        bottom: 2rem;
        right: 1.5rem;
    }
}
</style>
