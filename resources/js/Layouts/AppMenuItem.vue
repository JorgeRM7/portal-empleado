<script setup>
import { useLayout } from "./composables/layout";
import { onBeforeMount, ref, watch, computed } from "vue";
import { Link } from "@inertiajs/vue3";

const { layoutState, setActiveMenuItem, toggleMenu } = useLayout();

const props = defineProps({
    item: { type: Object, default: () => ({}) },
    index: { type: Number, default: 0 },
    root: { type: Boolean, default: true },
    parentItemKey: { type: String, default: null },
});

const isActiveMenu = ref(false);
const itemKey = ref(null);
const submenuRef = ref(null);

const hasActiveChild = computed(() => {
    if (!props.item.items || !props.item.items.length) return false;
    return props.item.items.some((child) => {
        if (!child.to) return false;
        const currentPath = window.location.pathname.replace(/\/$/, "");
        const itemPath = child.to.replace(/\/$/, "");
        return (
            currentPath === itemPath || currentPath.startsWith(itemPath + "/")
        );
    });
});

onBeforeMount(() => {
    itemKey.value = props.parentItemKey
        ? props.parentItemKey + "-" + props.index
        : String(props.index);

    const activeItem = layoutState.activeMenuItem;

    if (hasActiveChild.value) {
        isActiveMenu.value = true;
    } else {
        isActiveMenu.value =
            activeItem === itemKey.value ||
            (activeItem && activeItem.startsWith(itemKey.value + "-"));
    }
});

watch(
    () => layoutState.activeMenuItem,
    (newVal) => {
        if (hasActiveChild.value) {
            isActiveMenu.value = true;
        } else {
            isActiveMenu.value =
                newVal === itemKey.value ||
                newVal.startsWith(itemKey.value + "-");
        }
    },
);

watch(
    () => window.location.pathname,
    () => {
        if (hasActiveChild.value) {
            isActiveMenu.value = true;
        }
    },
);

function itemClick(event, item) {
    if (item.disabled) {
        event.preventDefault();
        return;
    }

    // ✅ Siempre permitir toggle en root, incluso si hay hijo activo
    if (props.root && item.items?.length) {
        isActiveMenu.value = !isActiveMenu.value;
        if (isActiveMenu.value) {
            setActiveMenuItem(itemKey.value);
        }
        return;
    }

    if (item.to) {
        if (
            layoutState.staticMenuMobileActive ||
            layoutState.overlayMenuActive
        ) {
            toggleMenu();
        }
        setActiveMenuItem(itemKey.value);
    }

    if (item.command) {
        item.command({ originalEvent: event, item: item });
    }
}

function checkActiveRoute(item) {
    if (!item.to) return false;
    const currentPath = window.location.pathname.replace(/\/$/, "");
    const itemPath = item.to.replace(/\/$/, "");
    return currentPath === itemPath || currentPath.startsWith(itemPath + "/");
}

// Animación suave
function beforeEnter(el) {
    el.style.height = "0";
    el.style.overflow = "hidden";
    el.style.opacity = "0";
}

function enter(el, done) {
    requestAnimationFrame(() => {
        const height = el.scrollHeight;
        el.style.transition = "all 0.5s cubic-bezier(0.4, 0, 0.2, 1)";
        el.style.height = height + "px";
        el.style.opacity = "1";
        setTimeout(done, 500);
    });
}

function leave(el, done) {
    el.style.transition = "all 0.4s cubic-bezier(0.4, 0, 0.2, 1)";
    el.style.height = "0";
    el.style.opacity = "0";
    setTimeout(done, 400);
}
</script>

<template>
    <li
        :class="{
            'layout-root-menuitem': root,
            'active-menuitem': isActiveMenu,
        }"
    >
        <!-- CASO 1: Item ROOT con submenú -->
        <template v-if="root && item.items?.length">
            <div
                class="layout-menuitem-root-text"
                @click="itemClick($event, item)"
                style="
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    padding: 1rem;
                    font-weight: 600;
                    gap: 0.75rem;
                    font-size: 0.9rem;
                "
            >
                <!-- ✅ Icono del módulo root -->
                <i
                    v-if="item.icon"
                    :class="item.icon"
                    class="layout-menuitem-icon text-2xl"
                ></i>
                <span>{{ item.label }}</span>
                <i
                    class="pi pi-angle-down layout-submenu-toggler"
                    :class="{ rotated: isActiveMenu }"
                    style="margin-left: auto"
                ></i>
            </div>

            <transition name="layout-submenu">
                <ul v-show="isActiveMenu" class="layout-submenu">
                    <app-menu-item
                        v-for="(child, i) in item.items"
                        :key="i"
                        :item="child"
                        :index="i"
                        :parentItemKey="itemKey"
                        :root="false"
                    />
                </ul>
            </transition>
        </template>

        <!-- CASO 2: Item HIJO con enlace -->
        <template v-else-if="!root && item.to">
            <Link
                @click="itemClick($event, item)"
                :class="[
                    'menu-item-link',
                    { 'active-route text-primary': checkActiveRoute(item) },
                ]"
                :href="item.to"
                style="
                    display: flex;
                    align-items: center;
                    padding: 0.75rem 1rem 0.75rem 2.5rem;
                    cursor: pointer;
                    text-decoration: none;
                    color: inherit;
                    gap: 0.5rem;
                "
            >
                <i
                    v-if="item.icon"
                    :class="item.icon"
                    style="font-size: 0.9rem"
                ></i>
                <span>{{ item.label }}</span>
            </Link>
        </template>

        <!-- CASO 3: Item HIJO sin enlace -->
        <template v-else-if="!root">
            <div
                style="
                    display: flex;
                    align-items: center;
                    padding: 0.75rem 1rem 0.75rem 2.5rem;
                    gap: 0.5rem;
                "
            >
                <i
                    v-if="item.icon"
                    :class="item.icon"
                    style="font-size: 0.9rem"
                ></i>
                <span>{{ item.label }}</span>
            </div>
        </template>
    </li>
</template>

<style scoped>
.layout-submenu-toggler {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.layout-submenu-toggler.rotated {
    transform: rotate(180deg);
}

.layout-submenu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.layout-submenu-enter-active,
.layout-submenu-leave-active {
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    max-height: 3000px; /* ✅ Altura máxima grande */
    opacity: 1;
    overflow: hidden;
}

.layout-submenu-enter-from,
.layout-submenu-leave-to {
    max-height: 0;
    opacity: 0;
}

/* ✅ Después de la animación, permitir overflow visible */
.layout-submenu-enter-to,
.layout-submenu-leave-from {
    overflow: visible;
}

.menu-item-link {
    transition: all 0.2s ease;
    border-radius: 0.375rem;
    min-height: max-content;
}

.menu-item-link:hover {
    background-color: rgba(0, 0, 0, 0.05);
    padding-left: 2.25rem !important;
}

.active-route {
    font-weight: 600;
}

/* Indicador visual de módulo activo */
.layout-menuitem-root-text:has(.active-route) {
    border-left: 3px solid var(--primary-color, #3b82f6);
}
</style>
