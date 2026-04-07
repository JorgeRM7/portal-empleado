<script setup>
import { computed, ref } from "vue";
import AppMenuItem from "./AppMenuItem.vue";
import { useAuthz } from "@/composables/useAuthz";

const { can } = useAuthz();

const rawMenu = ref([
    {
        label: "Panel",
        icon: "pi pi-fw pi-home",
        items: [
            {
                label: "Dashboard",
                icon: "pi pi-fw pi-home",
                to: "/dashboard",
                permission: "dashboard.index",
            },
        ],
    },
    {
        label: "Asistencias",
        icon: "pi pi-fw pi-check-circle",
        items: [
            {
                label: "Reporte de ausentismos",
                icon: "pi pi-fw pi-check-circle",
                to: "/assistences/absenteeism",
                permission: "absenteeism.index",
            },
            {
                label: "Registro de asistencias",
                icon: "pi pi-fw pi-id-card",
                to: "/assistences/assistences",
                permission: "assistences.index",
            },
        ],
    },
    {
        label: "Administración",
        icon: "pi pi-fw pi-cog",
        items: [
            {
                label: "Usuarios",
                icon: "pi pi-user",
                to: "/users",
                permission: "users.index",
            },
            {
                label: "Roles",
                icon: "pi pi-user",
                to: "/roles",
                permission: "roles.index",
            },
        ],
    },
]);

const flatMenu = computed(() => {
    return rawMenu.value.flatMap((section) =>
        (section.items || [])
            .filter((item) => !item.permission || can(item.permission))
            .map((item) => ({
                ...item,
                section: section.label,
            }))
    );
});
</script>

<template>
    <ul class="layout-menu">
        <AppMenuItem
            v-for="(item, i) in flatMenu"
            :key="item.label + '-' + i"
            :item="item"
            :index="i"
        />

        <li
            v-if="flatMenu.length === 0"
            class="p-4 text-center text-gray-500"
        >
            No tienes acceso a ninguna sección
        </li>
    </ul>
</template>