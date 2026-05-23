<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useLayout } from "./composables/layout";
import { Link, router } from "@inertiajs/vue3";
import axios from "axios";
import { useBranchOfficeStore } from "@/Stores/planta";
import { useNotifications } from "@/composables/useNotifications";

const { initTheme } = useLayout();
const branchOfficeStore = useBranchOfficeStore();

const op = ref();
const opProfile = ref();
const opNotifications = ref();
const branchOffices = ref([]);
const branchOfficesCopy = ref([]);
const { toggleMenu, toggleDarkMode, isDarkTheme, readTheme } = useLayout();
const value = ref("");

const resolvedNotifications = computed(() => {
    return unread.value.filter(
        (n) => n.data?.notification_type === "RESOLVED"
    );
});

const toggle = (event) => {
    op.value.toggle(event);
};

const toggleProfile = (event) => {
    opProfile.value.toggle(event);
};

const {
    unreadCount,
    all,
    unread,
    loading,
    fetchNotifications,
    markAsRead,
    markAllAsRead,
    newNot,
} = useNotifications();

const bellAnimClass = computed(() =>
    unreadCount.value > 0 ? "bell-ring" : "",
);

const filterBranchOffices = (searchTerm) => {
    branchOffices.value = [...branchOfficesCopy.value];
    if (!searchTerm) return;
    branchOffices.value = branchOffices.value.filter((branchOffice) =>
        branchOffice.code.toLowerCase().includes(searchTerm.toLowerCase()),
    );
};

// FUNCIÓN CLAVE: Selección robusta
async function select(branchOffice, forceReload = false) {
    // 1. Actualizar Store (Estado local)
    branchOfficeStore.select(branchOffice);

    // 2. Guardar en LocalStorage (Caché rápido)
    try {
        localStorage.setItem(
            "selectedBranchOffice",
            JSON.stringify(branchOffice),
        );
    } catch (e) {
        console.warn(
            "No se pudo guardar en localStorage (modo incógnito o lleno):",
            e,
        );
    }

    // 3. PLAN B: Guardar en Backend (Persistencia real)
    // Esto evita que se pierda la selección si limpian el navegador
    try {
        await axios.post("/user/set-default-branch", {
            branch_id: branchOffice.id,
        });
    } catch (error) {
        console.error("Error guardando preferencia en servidor:", error);
        // No detenemos la ejecución, es un fallo no crítico para la UI inmediata
    }

    // 4. Recargar si es necesario para actualizar sesiones/cookies del servidor
    if (forceReload) {
        reloadPage();
    }
}

function reloadPage() {
    // Usamos Inertia para recargar manteniendo el estado
    router.get(
        window.location.pathname,
        {},
        {
            preserveState: false,
            preserveScroll: true,
        },
    );
}

const toggleNotifications = (event) => {
    fetchNotifications();
    opNotifications.value.toggle(event);
};

function openNotification(notification) {
    markAsRead(notification.id);
    const data = notification.data;
    if (!data.complain_id) return;
    router.get(`/complaints/${data.complain_id}`);
}

watch(unread, (value) => {
    console.log('NOTIFICACIONES:', value);
}, { deep: true });

onMounted(async () => {
    await fetchNotifications();
});
</script>

<template>
    <div class="layout-topbar">
        <div class="layout-topbar-logo-container">
            <button
                class="layout-menu-button layout-topbar-action"
                @click="toggleMenu"
            >
                <i class="pi pi-bars"></i>
            </button>
            <Link href="/" class="layout-topbar-logo">
                <img
                    src="/assets/media/logos/logo.png"
                    alt="logo"
                    width="30"
                    height="30"
                />
                <span class="">Mi Portal RH</span>
            </Link>
        </div>

        <div class="layout-topbar-actions">
            <div class="layout-config-menu">
                <button
                    type="button"
                    class="layout-topbar-action"
                    @click="
                        toggleDarkMode();
                        readTheme();
                    "
                >
                    <i
                        :class="[
                            'pi',
                            { 'pi-moon': isDarkTheme, 'pi-sun': !isDarkTheme },
                        ]"
                    ></i>
                </button>
            </div>

            <button
                class="layout-topbar-menu-button layout-topbar-action"
                v-styleclass="{
                    selector: '@next',
                    enterFromClass: 'hidden',
                    enterActiveClass: 'animate-scalein',
                    leaveToClass: 'hidden',
                    leaveActiveClass: 'animate-fadeout',
                    hideOnOutsideClick: true,
                }"
                @click="toggleProfile"
            >
                <i class="pi pi-ellipsis-v"></i>
            </button>

            <Popover ref="opProfile">
                <Button
                    icon="pi pi-user"
                    label="Perfil"
                    outlined
                    severity="secondary"
                    @click="
                        () => {
                            router.get('/user/profile');
                        }
                    "
                ></Button>
            </Popover>

            <div class="relative">
                <Button
                    type="button"
                    text
                    :severity="unreadCount > 0 ? 'warn' : 'secondary'"
                    :class="bellAnimClass"
                    @click="toggleNotifications"
                >
                    <i
                        class="pi pi-bell"
                        style="font-size: 1.2rem; line-height: 1"
                    ></i>
                </Button>

                <span
                    v-if="unreadCount > 0"
                    class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full min-w-5 h-5 flex items-center justify-center px-1"
                >
                    {{ unreadCount }}
                </span>
            </div>

            <Popover ref="opNotifications">
            <div
                class="w-[390px] overflow-hidden rounded-[24px] border border-slate-200 shadow-[0_20px_50px_rgba(15,23,42,0.15)]"
            >
                <!-- Header -->
                <div
                    class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-500 text-white shadow"
                        >
                            <i class="pi pi-bell text-base"></i>
                        </div>

                        <div>
                            <h3 class="text-sm font-extrabold">
                                Notificaciones
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ unread.length }} pendientes
                            </p>
                        </div>
                    </div>

                    <Button
                        v-if="unread.length > 0"
                        text
                        size="small"
                        label="Marcar todas como leídas"
                        class="!text-blue-500 hover:!text-blue-600"
                        :class="loading && 'opacity-40 pointer-events-none'"
                        :disabled="loading"
                        @click="markAllAsRead"
                    />
                </div>

                <!-- Body -->
                <div class="max-h-[380px] overflow-y-auto">

                    <!-- Loading -->
                    <div v-if="loading" class="p-4 space-y-3">
                        <div
                            v-for="n in 4"
                            :key="n"
                            class="rounded-2xl border border-slate-200 p-4"
                        >
                            <Skeleton height="1rem" class="mb-2" />
                            <Skeleton height=".8rem" width="70%" />
                        </div>
                    </div>

                    <!-- Lista -->
                    <div
                        v-else-if="unread.length > 0"
                        class="p-3 space-y-3"
                    >
                        <div
                            v-for="notification in unread"
                            :key="notification.id"
                            class="group rounded-[20px] border border-slate-200 p-4 shadow-sm hover:shadow-md hover:border-blue-200 transition-all duration-300 cursor-pointer"
                            @click="openNotification(notification)"
                            v-tooltip.top="'Abrir notificación'"
                        >
                            <div class="flex items-start gap-3">

                                <!-- Icon -->
                                <div
                                    class="mt-0.5 flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-500 text-white shadow-[0_10px_25px_rgba(59,130,246,0.25)]"
                                >
                                    <i class="pi pi-bell text-sm"></i>
                                </div>

                                <!-- Content -->
                                <div class="min-w-0 flex-1">

                                    <div class="flex items-start justify-between gap-2">

                                        <div>
                                            <div class="font-bold">
                                                {{ notification.data.titulo }}
                                            </div>

                                            <div class="text-sm text-gray-500">
                                                {{ notification.data.descripcion }}
                                            </div>
                                        </div>

                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider"
                                            :class="
                                                notification.status
                                                    ? 'bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-300'
                                                    : 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300'
                                            "
                                        >
                                            Nueva
                                        </span>

                                    </div>

                                    <!-- Module -->
                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <span
                                            class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-500/15 px-2.5 py-1 text-xs font-semibold text-blue-700 dark:text-blue-300"
                                        >
                                            <i class="pi pi-folder mr-1 text-[10px]" />
                                            {{
                                                notification.data.notification_module ||
                                                "Sin módulo"
                                            }}
                                        </span>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty -->
                    <div
                        v-else
                        class="flex flex-col items-center justify-center px-6 py-10 text-center"
                    >
                        <div
                            class="mb-4 flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500"
                        >
                            <i class="pi pi-inbox text-2xl"></i>
                        </div>

                        <h4 class="text-sm font-extrabold dark:text-slate-100">
                            No tienes notificaciones nuevas
                        </h4>

                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 max-w-[220px]">
                            Cuando tengas actividad pendiente, aparecerá aquí.
                        </p>
                    </div>

                </div>
            </div>
        </Popover>

            <!-- <Popover ref="opNotifications">
                <div class="flex flex-col gap-4 w-96 max-h-64 overflow-y-auto">
                    <div>
                        <ul class="list-none p-0 m-0 flex flex-col">
                            <li
                                v-for="notification in resolvedNotifications"
                                :key="notification.id"
                                class="p-3 border-b border-gray-200 cursor-pointer"
                                @click="openNotification(notification)"
                            >
                                <Skeleton v-if="loading"></Skeleton>
                                <div v-else>
                                    <div class="font-medium">
                                        {{ notification.data.titulo }}
                                    </div>

                                    <div
                                        class="text-sm text-gray-600 dark:text-gray-400"
                                    >
                                        {{ notification.data.descripcion }}
                                    </div>

                                    <div
                                        class="text-xs text-blue-500 mt-1"
                                    >
                                        {{ notification.data.notification_module }}
                                    </div>
                                </div>
                            </li>
                            <li
                                v-if="unread.length === 0"
                                class="p-3 text-center text-gray-600 dark:text-gray-400"
                            >
                                No tienes notificaciones nuevas.
                            </li>
                            <li
                                v-if="unread.length > 0"
                                class="p-3 text-center"
                            >
                                <Button
                                    text
                                    label="Marcar todas como leídas"
                                    :class="loading && 'opacity-40'"
                                    :disabled="loading"
                                    @click="markAllAsRead"
                                />
                            </li>
                        </ul>
                    </div>
                </div>
            </Popover> -->

            <div class="layout-topbar-menu hidden lg:block">
                <div class="layout-topbar-menu-content">
                    <Link
                        href="/user/profile"
                        type="button"
                        class="layout-topbar-action"
                    >
                        <i class="pi pi-user"></i>
                        <span>Profile</span>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Opción 1: “ring” suave (se mece como campana) */
.bell-ring {
    animation: bell-ring 1.2s ease-in-out infinite;
    transform-origin: top center;
}

@keyframes bell-ring {
    0% {
        transform: rotate(0deg);
    }

    10% {
        transform: rotate(14deg);
    }

    20% {
        transform: rotate(-12deg);
    }

    30% {
        transform: rotate(10deg);
    }

    40% {
        transform: rotate(-8deg);
    }

    50% {
        transform: rotate(6deg);
    }

    60% {
        transform: rotate(-4deg);
    }

    70% {
        transform: rotate(2deg);
    }

    80%,
    100% {
        transform: rotate(0deg);
    }
}

/* (Alternativa) Opción 2: “pulse” */
.bell-pulse {
    animation: bell-pulse 1.1s ease-in-out infinite;
}

@keyframes bell-pulse {
    0%,
    100% {
        transform: scale(1);
    }

    50% {
        transform: scale(1.12);
    }
}

/* Respeto a usuarios con reducción de movimiento */
@media (prefers-reduced-motion: reduce) {
    .bell-ring,
    .bell-pulse {
        animation: none !important;
    }
}
</style>
