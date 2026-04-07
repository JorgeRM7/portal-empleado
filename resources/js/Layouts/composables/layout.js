import { computed, reactive, ref } from "vue";
import { watchEffect } from "vue";

const layoutConfig = reactive({
    preset: "Aura",
    primary: "emerald",
    surface: null,
    darkTheme: localStorage.getItem("theme") === "dark",
    menuMode: "static",
});

const layoutState = reactive({
    staticMenuDesktopInactive: false,
    overlayMenuActive: false,
    profileSidebarVisible: false,
    configSidebarVisible: false,
    staticMenuMobileActive: false,
    menuHoverActive: false,
    activeMenuItem: null,
});

export function useLayout() {
    const setActiveMenuItem = (item) => {
        layoutState.activeMenuItem = item.value || item;
    };

    const toggleDarkMode = () => {
        if (!document.startViewTransition) {
            executeDarkModeToggle();

            return;
        }

        document.startViewTransition(() => executeDarkModeToggle(event));
    };

    const executeDarkModeToggle = () => {
        layoutConfig.darkTheme = !layoutConfig.darkTheme;
        document.documentElement.classList.toggle("app-dark");
        document.documentElement.classList.toggle("mode-dark");

        // persistir preferencia
        localStorage.setItem(
            "theme",
            layoutConfig.darkTheme ? "dark" : "light",
        );
        localStorage.setItem(
            "data-bs-theme",
            layoutConfig.darkTheme ? "dark" : "light",
        );
        localStorage.setItem(
            "data-bs-theme-mode",
            layoutConfig.darkTheme ? "dark" : "light",
        );
    };

    const toggleMenu = () => {
        if (layoutConfig.menuMode === "overlay") {
            layoutState.overlayMenuActive = !layoutState.overlayMenuActive;
        }

        if (window.innerWidth > 991) {
            layoutState.staticMenuDesktopInactive =
                !layoutState.staticMenuDesktopInactive;
        } else {
            layoutState.staticMenuMobileActive =
                !layoutState.staticMenuMobileActive;
        }
    };

    const isSidebarActive = computed(
        () =>
            layoutState.overlayMenuActive || layoutState.staticMenuMobileActive,
    );

    const isDarkTheme = computed(() => layoutConfig.darkTheme);

    const getPrimary = computed(() => layoutConfig.primary);

    const getSurface = computed(() => layoutConfig.surface);

    const initTheme = () => {
        const saved = localStorage.getItem("theme");
        const currentIsDark = layoutConfig.darkTheme;

        if (saved) {
            if (saved === "dark" && !currentIsDark) {
                executeDarkModeToggle(true);
            } else if (saved === "light" && currentIsDark) {
                executeDarkModeToggle(false);
            }
        }
    };

    const isDark = ref(false);

    const readTheme = () => {
        const root = document.documentElement;

        const darkByClass = root.classList.contains("dark");

        const darkByDataTheme = root.getAttribute("data-theme") === "dark";
        const darkByBsTheme = root.getAttribute("data-bs-theme") === "dark";

        isDark.value = darkByClass || darkByDataTheme || darkByBsTheme;
    };

    watchEffect(() => {
        const isDarkTheme = layoutConfig.darkTheme;

        document.documentElement.classList.toggle("app-dark", isDarkTheme);
        document.documentElement.classList.toggle("mode-dark", isDarkTheme);
        document.documentElement.setAttribute(
            "data-bs-theme",
            isDarkTheme ? "dark" : "light",
        );
        document.documentElement.setAttribute(
            "data-bs-theme-mode",
            isDarkTheme ? "dark" : "light",
        );
        document.documentElement.setAttribute(
            "themePreference",
            isDarkTheme ? "dark" : "light",
        );

        readTheme();
    });

    return {
        layoutConfig,
        layoutState,
        toggleMenu,
        isSidebarActive,
        isDarkTheme,
        getPrimary,
        getSurface,
        setActiveMenuItem,
        toggleDarkMode,
        initTheme,
        readTheme,
        isDark,
    };
}
