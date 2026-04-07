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

// ... (resto de tus composables y refs) ...

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

onMounted(async () => {
    try {
        // 1. Obtener lista de plantas
        const branchOfficesDB = await axios.get("/branch-offices-user");
        branchOffices.value = branchOfficesDB.data;
        branchOfficesCopy.value = branchOfficesDB.data;

        if (branchOffices.value.length === 0) {
            console.warn("No hay sucursales disponibles para este usuario");
            return;
        }

        // 2. INTENTO 1: Leer de LocalStorage
        let storedBranch = null;
        try {
            const item = localStorage.getItem("selectedBranchOffice");
            storedBranch = item ? JSON.parse(item) : null;

            // Validar que la planta guardada siga existiendo en la lista actual
            const isValid = branchOffices.value.find(
                (b) => b.id === storedBranch?.id,
            );
            if (!isValid) storedBranch = null;
        } catch (e) {
            console.warn("LocalStorage corrupto, limpiando...");
            localStorage.removeItem("selectedBranchOffice");
        }

        // 3. INTENTO 2 (PLAN B): Si localStorage falló, pedir al Backend
        if (!storedBranch) {
            try {
                // Asumiendo que tienes una ruta que devuelve la preferencia del usuario
                // Si no la tienes, crea una rápida en tu backend: Route::get('/user/default-branch')
                const response = await axios.get("/user/default-branch");
                if (response.data) {
                    storedBranch = response.data;
                    console.log("Recuperada planta desde Backend (Plan B)");
                }
            } catch (error) {
                console.log(
                    "No se pudo recuperar preferencia del backend, usando fallback.",
                );
            }
        }

        // 4. INTENTO 3 (FALLBACK): Usar la primera disponible
        const finalBranch = storedBranch || branchOffices.value[0];

        // Aplicar selección
        select(finalBranch);

        // IMPORTANTE: Si venimos de un fallo de localStorage, a veces es mejor forzar un reload
        // para asegurar que las variables de sesión del servidor (PHP/Laravel) se actualicen
        if (!storedBranch && branchOffices.value.length > 0) {
            // Opcional: Descomentar si los errores SQL son graves al inicio
            // reloadPage();
        }

        await fetchNotifications();
        initTheme();
    } catch (error) {
        console.error("Error crítico en inicialización del Topbar:", error);
        // Aquí podrías redirigir a una página de error o mantenimiento
    }
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

            <Popover ref="opNotifications">
                <div class="flex flex-col gap-4 w-96 max-h-64 overflow-y-auto">
                    <div>
                        <ul class="list-none p-0 m-0 flex flex-col">
                            <li
                                v-for="notification in unread"
                                :key="notification.id"
                                class="p-3 border-b border-gray-200 cursor-pointer"
                                @click="markAsRead(notification.id)"
                            >
                                <Skeleton v-if="loading"></Skeleton>
                                <div v-else>
                                    <div class="font-medium">
                                        {{ notification.data.mensaje }}
                                    </div>
                                    <div
                                        class="text-sm text-gray-600 dark:text-gray-400"
                                    >
                                        {{ notification.data.modulo }}
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
            </Popover>

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
