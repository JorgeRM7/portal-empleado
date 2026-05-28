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
import InactivityTimer from "@/Components/InactivityTimer.vue";
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

// ============================================
// NUEVA SECCIÓN: Estados para cambio de contraseña
// ============================================
const showPasswordChangeModal = ref(false);
const loadingPasswordChange = ref(false);
const passwordForm = ref({
    current_password: "",
    password: "",
    password_confirmation: "",
});
const passwordErrors = ref({});

const getOrGenerateDeviceId = () => {
    let deviceId = localStorage.getItem("my_device_id");
    if (!deviceId) {
        // Genera un ID único basado en la fecha y un número aleatorio
        deviceId =
            "dev_" + Date.now() + "_" + Math.random().toString(36).substr(2, 9);
        localStorage.setItem("my_device_id", deviceId);
    }
    return deviceId;
};

const acceptTerms = async () => {
    const userId = page.props.auth?.user?.id;
    if (!userId) return;

    try {
        loadingTerms.value = true;

        await router.put(
            route("term-conditions.update", { term_condition: userId }),
            {},
        );

        showTermsModal.value = false;

        toast.add({
            severity: "success",
            summary: "Términos aceptados",
            detail: "Ahora puedes activar las notificaciones.",
            life: 4000,
        });

        await setupNotifications();
    } catch (error) {
        console.error(error);
    } finally {
        loadingTerms.value = false;
    }
};

const setupNotifications = async () => {
    showWarningNotifications.value = false;

    const isIOS =
        /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

    if (isIOS && !window.navigator.standalone) {
        toast.add({
            severity: "warn",
            summary: "Atención usuario de iPhone",
            detail: "Para recibir notificaciones, agrega la app a pantalla de inicio.",
            life: 10000,
        });
        return;
    }

    if (Notification.permission === "denied") {
        isPermanentlyBlocked.value = true;
        showWarningNotifications.value = true;
        return;
    }

    try {
        if (Notification.permission === "granted") {
            const tokenReal = await obtenerTokenReal();

            if (!tokenReal) return;

            await saveDeviceToken(tokenReal);

            return;
        }

        const permission = await Notification.requestPermission();

        if (permission === "granted") {
            const tokenReal = await obtenerTokenReal();

            if (tokenReal) {
                await saveDeviceToken(tokenReal);
            } else {
                toast.add({
                    severity: "error",
                    summary: "Error",
                    detail: "No se pudo generar el token.",
                    life: 4000,
                });
            }
        } else {
            isPermanentlyBlocked.value = permission === "denied";
            showWarningNotifications.value = true;
        }
    } catch (error) {
        console.error(error);
    }
};

const saveDeviceToken = async (token) => {
    try {
        const deviceIdentifier = getOrGenerateDeviceId();

        const response = await axios.post(route("device-tokens.store"), {
            token: token,
            device_id: deviceIdentifier,
        });

        console.log(response.data.message);
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail:
                error.response?.data?.message ||
                "No se pudo guardar el dispositivo.",
            life: 4000,
        });
    }
};

const logout = () => {
    router.post(
        route("logout"),
        {},
        {
            onBefore: () => {
                loadingTerms.value = true;
            },
            onSuccess: () => {
                console.log("Sesión cerrada.");
            },
        },
    );
};

const handlePasswordChange = async () => {
    try {
        loadingPasswordChange.value = true;
        passwordErrors.value = {};

        await router.put(
            route("password.update-user-employee"),
            {
                current_password: passwordForm.value.current_password,
                password: passwordForm.value.password,
                password_confirmation: passwordForm.value.password_confirmation,
            },
            {
                onSuccess: () => {
                    passwordForm.value = {
                        current_password: "",
                        password: "",
                        password_confirmation: "",
                    };

                    showPasswordChangeModal.value = false;

                    toast.add({
                        severity: "success",
                        summary: "Contraseña actualizada",
                        detail: "Tu contraseña ha sido cambiada exitosamente.",
                        life: 4000,
                    });

                    router.reload({ only: ["auth"] });
                },
                onError: (errors) => {
                    passwordErrors.value = errors;

                    toast.add({
                        severity: "error",
                        summary: "Error",
                        detail: "Verifica los campos e intenta nuevamente.",
                        life: 4000,
                    });
                },
            },
        );
    } catch (error) {
        console.error(error);
    } finally {
        loadingPasswordChange.value = false;
    }
};

watch(
    () => page.props.auth?.user?.terms_condition,
    (value) => {
        showTermsModal.value = Number(value) !== 1;
    },
    { immediate: true },
);

console.log(page.props.auth?.user);

// ============================================
// NUEVO WATCHER: Verificar cambio de contraseña obligatorio
// ============================================
watch(
    () => page.props.auth?.user?.password_change,
    (value) => {
        showPasswordChangeModal.value = Boolean(value);
    },
    { immediate: true },
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
    window.addEventListener("beforeinstallprompt", (e) => {
        e.preventDefault();
        deferredPrompt.value = e;
        console.log("Evento de instalación capturado y listo.");
    });

    window.addEventListener("appinstalled", () => {
        deferredPrompt.value = null;
        console.log("PWA instalada con éxito");
    });

    const user = page.props.auth?.user;

    if (!user) return;

    if (Number(user.terms_condition) !== 1) {
        showTermsModal.value = true;
    } else {
        setupNotifications();
    }
});

const installApp = async () => {
    if (!deferredPrompt.value) return;

    deferredPrompt.value.prompt();

    const { outcome } = await deferredPrompt.value.userChoice;

    if (outcome === "accepted") {
        console.log("Usuario aceptó la instalación");
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
            <div class="layout-main">
                <div
                    :class="{
                        'pointer-events-none opacity-50':
                            showTermsModal || showPasswordChangeModal,
                    }"
                >
                    <slot />
                </div>
            </div>
            <app-footer></app-footer>
            <div
                v-if="deferredPrompt"
                class="fixed bottom-8 right-8 z-[9999] animate-bounce-slow"
            >
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
        <InactivityTimer :on-logout="logout" />
        <div class="layout-mask animate-fadein"></div>
    </div>
    <Toast />

    <!-- Modal de Términos y Condiciones (original) -->
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
                <InlineMessage severity="info"
                    >Para continuar utilizando el <strong>Mi Portal RH</strong>,
                    es necesario que leas y aceptes los términos de
                    confidencialidad y uso de datos.</InlineMessage
                >
            </div>

            <div
                class="p-6 rounded-xl border border-gray-100 dark:border-gray-700 max-h-[350px] overflow-y-auto text-sm leading-relaxed text-gray-600 dark:text-gray-300"
            >
                <h3
                    class="font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2"
                >
                    <i class="pi pi-shield text-emerald-500"></i>
                    Aviso de Privacidad y Confidencialidad
                </h3>

                <ul class="flex flex-col gap-4 list-none p-0">
                    <li class="flex gap-3">
                        <i class="pi pi-user-focus mt-1 text-blue-500"></i>
                        <span
                            >En cumplimiento con la Ley Federal de Protección de
                            Datos Personales en Posesión de los Particulares, se
                            informa que los datos personales recabados a través
                            de Mi Portal RH serán utilizados para la
                            administración de la relación laboral, incluyendo la
                            gestión de incidencias, vacaciones, permisos,
                            tiempos extra, nómina y cumplimiento de obligaciones
                            legales</span
                        >
                    </li>

                    <li class="flex gap-3">
                        Se podrán tratar datos de identificación, laborales, de
                        contacto y, en su caso, datos sensibles como información
                        médica derivada de incapacidades.
                    </li>

                    <li class="flex gap-3">
                        <i class="pi pi-comments mt-1 text-purple-500"></i>
                        <span
                            >El uso del portal implica la aceptación del
                            tratamiento de sus datos personales conforme a este
                            aviso. El Aviso de Privacidad Integral se encuentra
                            disponible para su consulta dentro del
                            portal.|</span
                        >
                    </li>
                </ul>

                <div
                    class="mt-6 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-xs border border-blue-100 dark:border-blue-800 italic"
                >
                    Al hacer clic en "Aceptar y continuar", confirmas que has
                    leído y estás de acuerdo con el manejo de tus datos bajo los
                    lineamientos anteriormente descritos.
                </div>
            </div>

            <div
                class="flex flex-col sm:flex-row items-center justify-end gap-3 mt-2"
            >
                <Button
                    label="No acepto"
                    icon="pi pi-sign-out"
                    :icon="
                        loadingTerms
                            ? 'pi pi-spin pi-spinner'
                            : 'pi pi-sign-out'
                    "
                    :loading="loadingTerms"
                    severity="danger"
                    text
                    @click="logout"
                    class="w-full sm:w-auto"
                />
                <Button
                    label="Aceptar y continuar"
                    icon="pi pi-check"
                    :icon="
                        loadingTerms ? 'pi pi-spin pi-spinner' : 'pi pi-check'
                    "
                    :loading="loadingTerms"
                    severity="success"
                    @click="acceptTerms"
                    class="w-full sm:w-auto shadow-lg shadow-emerald-500/20"
                    raised
                />
            </div>
        </div>
    </Dialog>

    <!-- Modal de Notificaciones (original) -->
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
                Para continuar en <strong>Mi Portal RH</strong>, debes permitir
                las notificaciones. Esto nos permite enviarte tus recibos y
                avisos importantes.
            </p>

            <div
                v-if="isPermanentlyBlocked"
                class="p-4 rounded-lg border border-blue-200"
            >
                <p class="text-xs font-bold mb-2 flex items-center gap-2">
                    <i class="pi pi-info-circle"></i> CÓMO ACTIVARLAS:
                </p>
                <ol class="text-xs pl-4 list-decimal flex flex-col gap-2">
                    <li>
                        Haz clic en el icono del <strong>candado (🔒)</strong> o
                        de <strong>ajustes o controles (⊶)</strong> situado a la
                        izquierda de la dirección web en la parte superior.
                    </li>
                    <li>
                        Si ya habías bloqueado los permisos anteriormente, ahí
                        verás la opción de
                        <strong>Notificaciones</strong> desactivada.
                    </li>
                    <li>
                        Activa el interruptor de
                        <strong>Notificaciones</strong> para permitir que el
                        portal te envíe avisos.
                    </li>
                    <li>
                        Cierra ese pequeño menú, regresa aquí y pulsa el botón
                        de abajo.
                    </li>
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

    <!-- NUEVO: Modal de Cambio de Contraseña Obligatorio -->
    <Dialog
        v-model:visible="showPasswordChangeModal"
        header="Cambio de Contraseña Obligatorio"
        :modal="true"
        :closable="false"
        :closeOnEscape="false"
        :draggable="false"
        class="mx-4 w-full md:w-[550px]"
    >
        <div class="flex flex-col gap-4">
            <!-- Mensaje de alerta -->
            <div class="p-4 rounded-2xl">
                <InlineMessage severity="warn"
                    >Por razones de seguridad,
                    <strong>debes cambiar tu contraseña</strong> antes de
                    continuar usando el portal.</InlineMessage
                >
            </div>

            <!-- Formulario -->
            <form
                @submit.prevent="handlePasswordChange"
                class="flex flex-col gap-4"
            >
                <!-- Campo: Contraseña Actual -->
                <div class="flex flex-col gap-2">
                    <label for="current_password" class="font-semibold text-sm"
                        >Contraseña Actual
                        <span class="text-red-500">*</span></label
                    >
                    <Password
                        id="current_password"
                        v-model="passwordForm.current_password"
                        class="w-full"
                        inputClass="w-full"
                        toggleMask
                        placeholder="Ingresa tu contraseña actual"
                        autocomplete="false"
                        :feedback="false"
                    />
                    <small
                        v-if="passwordErrors.current_password"
                        class="text-red-500"
                    >
                        {{ passwordErrors.current_password }}
                    </small>
                </div>

                <!-- Campo: Nueva Contraseña -->
                <div class="flex flex-col gap-2">
                    <label for="password" class="font-semibold text-sm"
                        >Nueva Contraseña
                        <span class="text-red-500">*</span></label
                    >
                    <Password
                        id="password"
                        v-model="passwordForm.password"
                        placeholder="Ingresa una nueva contraseña"
                        class="w-full"
                        inputClass="w-full"
                        toggleMask
                        autocomplete="false"
                    />
                    <small v-if="passwordErrors.password" class="text-red-500">
                        {{ passwordErrors.password }}
                    </small>
                    <small class="text-gray-500 text-xs">
                        Mínimo 8 caracteres, debe incluir mayúsculas, minúsculas
                        y números.
                    </small>
                </div>

                <!-- Campo: Confirmar Contraseña -->
                <div class="flex flex-col gap-2">
                    <label
                        for="password_confirmation"
                        class="font-semibold text-sm"
                        >Confirmar Contraseña
                        <span class="text-red-500">*</span></label
                    >
                    <Password
                        id="password_confirmation"
                        v-model="passwordForm.password_confirmation"
                        placeholder="Confirma tu nueva contraseña"
                        class="w-full"
                        inputClass="w-full"
                        toggleMask
                        autocomplete="false"
                    />
                    <small
                        v-if="passwordErrors.password_confirmation"
                        class="text-red-500"
                    >
                        {{ passwordErrors.password_confirmation }}
                    </small>
                </div>

                <!-- Botón de envío -->
                <div
                    class="flex flex-col sm:flex-row items-center justify-end gap-3 mt-4"
                >
                    <Button
                        type="submit"
                        label="Cambiar Contraseña"
                        icon="pi pi-lock"
                        :loading="loadingPasswordChange"
                        :disabled="loadingPasswordChange"
                        severity="success"
                        class="w-full sm:w-auto shadow-lg shadow-emerald-500/20"
                        raised
                    />
                </div>
            </form>
        </div>
    </Dialog>
</template>

<style scoped>
.animate-bounce-slow {
    animation: bounce 3s infinite;
}

@keyframes bounce {
    0%,
    20%,
    50%,
    80%,
    100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-10px);
    }
    60% {
        transform: translateY(-5px);
    }
}

/* Ajuste para que no tape contenido importante en móviles */
@media (max-width: 768px) {
    .fixed {
        bottom: 2rem;
        right: 1.5rem;
    }
}
</style>
