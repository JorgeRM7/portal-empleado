<script setup>
import { computed, ref } from "vue";
import AppMenuItem from "./AppMenuItem.vue";
import { useAuthz } from "@/composables/useAuthz";

const { can } = useAuthz();

const rawMenu = ref([
    {
        label: "Inicio",
        icon: "pi pi-fw pi-home",
        to: "/dashboard",
    },
    {
        label: "Incidencias",
        icon: "pi pi-fw pi-book",
        to: "/incidences-employee",
    },
    {
        label: "Recibos de Nómina",
        icon: "pi pi-fw pi-file",
        to: "/payroll/payroll-invoices",
    },
    {
        label: "Tikets",
        icon: "pi pi-ticket",
        to: "/complaints",
    },
]);

// const flatMenu = computed(() => {
//     return rawMenu.value.flatMap((section) =>
//         (section.items || [])
//             .filter((item) => !item.permission || can(item.permission))
//             .map((item) => ({
//                 ...item,
//                 section: section.label,
//             })),
//     );
// });
</script>

<template>
    <ul class="layout-menu">
        <AppMenuItem
            v-for="(item, i) in rawMenu"
            :key="item.label + '-' + i"
            :item="item"
            :index="i"
        />

        <li v-if="rawMenu.length === 0" class="p-4 text-center text-gray-500">
            No tienes acceso a ninguna sección
        </li>
    </ul>
</template>

<style scoped>
.layout-menu {
    margin-top: 1rem;
}
</style>

