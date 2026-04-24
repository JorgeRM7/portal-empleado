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


const page = usePage();
const loadingTerms = ref(false);
const showTermsModal = ref(false);
const toast = useToast();

const acceptTerms = () => {
    const userId = page.props.auth?.user?.id;
    console.log(userId);
    if (!userId) {
        console.error("No se encontró el ID del usuario");
        return;
    }

    router.put(route('term-conditions.update', { term_condition: userId }), {}, {
        onBefore: () => {
            loadingTerms.value = true;
        },
        onSuccess: () => {
            showTermsModal.value = false;

            // 🔥 actualizar el usuario en frontend
            page.props.auth.user.terms_condition = 1;

            toast.add({
                severity: 'success',
                summary: '¡Éxito!',
                detail: 'Has aceptado los términos. Ya puedes navegar.',
                life: 4000
            });
        },
        onError: (errors) => {
            console.error(errors);
        },
        onFinish: () => {
            loadingTerms.value = false;
        },
        preserveScroll: true
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

onMounted(() => {
    const user = usePage().props.auth?.user;

    if (user && !user.terms_condition) {
        showTermsModal.value = true;
    }
});

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
        document.removeEventListener("click", outsideClickListener);
        outsideClickListener.value = null;
    }
}

function isOutsideClicked(event) {
    const sidebarEl = document.querySelector(".layout-sidebar");
    const topbarEl = document.querySelector(".layout-menu-button");

    return !(
        sidebarEl.isSameNode(event.target) ||
        sidebarEl.contains(event.target) ||
        topbarEl.isSameNode(event.target) ||
        topbarEl.contains(event.target)
    );
}
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

            <!-- <div class="flex-grow w-full bg-gray-200 dark:bg-gray-900">
                <iframe
                    src="/path-to-your-pdf.pdf#toolbar=0"
                    class="w-full h-full border-none"
                    type="application/pdf"
                >
                    <div class="p-10 text-center">
                        <p>Tu navegador no puede mostrar el PDF directamente.</p>
                        <a href="/path-to-your-pdf.pdf" target="_blank" class="text-blue-500 underline">Haz clic aquí para descargar el documento.</a>
                    </div>
                </iframe>
            </div> -->

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
</template>
