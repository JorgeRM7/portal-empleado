<script setup>
import { useLayout } from "./composables/layout";
import { computed, ref, watch } from "vue";
import AppFooter from "./AppFooter.vue";
import AppSidebar from "./AppSideBar.vue";
import AppTopbar from "./AppTopBar.vue";
import { Head } from "@inertiajs/vue3";
import Toast from "@/Components/Toast.vue";
import Assistant from "@/Components/Assistant.vue";

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
            <div class="layout-main">
                <slot />
            </div>
            <app-footer></app-footer>
        </div>
        <!-- <Assistant use-backend="true" endpoint="/chat" /> -->
        <div class="layout-mask animate-fadein"></div>
    </div>
    <Toast />
</template>
