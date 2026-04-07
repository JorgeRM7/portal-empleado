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
            {
                label: "Registro H.E",
                icon: "pi pi-fw pi-clock",
                to: "/assistences/overtime",
                permission: "overtime.index",
            },
            {
                label: "Asistencia Semanal",
                icon: "pi pi-fw pi-calendar",
                to: "/assistences/weekly-assistences",
                permission: "weekly-assistences.index",
            },
            {
                label: "Asistencia Diaria",
                icon: "pi pi-fw pi-calendar",
                to: "/assistences/assistences-daily",
                permission: "assistences-daily.index",
            },
            {
                label: "Eventos",
                icon: "pi pi-fw pi-star",
                to: "/assistences/events",
                permission: "events.index",
            },
        ],
    },
    {
        label: "Nomina",
        icon: "pi pi-fw pi-money-bill",
        items: [
            {
                label: "Clases de Empleado",
                icon: "pi pi-fw pi-address-book",
                to: "/payroll/employee-classes",
                permission: "employee-classes.index",
            },
            {
                label: "Percepciones y Deducciones Por Empleado",
                icon: "pi pi-fw pi-money-bill",
                to: "/payroll/payroll-earnings-deductions",
                permission: "payroll-earnings-deductions.index",
            },
            {
                label: "Tipos de Asiento",
                icon: "pi pi-fw pi-tags",
                to: "/payroll/payroll-types",
                permission: "payroll-types.index",
            },
            {
                label: "Cuentas",
                icon: "pi pi-fw pi-book",
                to: "/payroll/payroll-accounts",
                permission: "payroll-accounts.index",
            },
            {
                label: "Tipo Recibo",
                icon: "pi pi-fw pi-ticket",
                to: "/payroll/payroll-invoice-types",
                permission: "payroll-invoice-types.index",
            },
            {
                label: "Nomina Empleados",
                icon: "pi pi-fw pi-wallet",
                to: "/payroll/payroll-departaments",
                permission: "payroll-departaments.index",
            },
            {
                label: "Recibos de Nómina",
                icon: "pi pi-fw pi-ticket",
                to: "/payroll/payroll-invoices",
                permission: "payroll-invoices.index",
            },
            {
                label: "Correos",
                icon: "pi pi-fw pi-envelope",
                to: "/payroll/payroll-emails",
                permission: "payroll-emails.index",
            },
        ],
    },
    {
        label: "Catálogos",
        icon: "pi pi-fw pi-book",
        items: [
            {
                label: "Plantas",
                icon: "pi pi-fw pi-building",
                to: "/catalogs/branch-offices",
                permission: "branch-offices.index",
            },
            {
                label: "Razones de estado",
                icon: "pi pi-fw pi-list",
                to: "/catalogs/status-reasons",
                permission: "status-reasons.index",
            },
            {
                label: "Percepciones y Deducciones",
                icon: "pi pi-fw pi-money-bill",
                to: "/catalogs/earnings-deductions",
                permission: "earnings-deductions.index",
            },
            {
                label: "Horarios",
                icon: "pi pi-calendar-clock",
                to: "/catalogs/schedules",
                permission: "schedules.index",
            },
            {
                label: "Rol De Turno",
                icon: "pi pi-fw pi-clock",
                to: "/catalogs/shift-roles",
                permission: "shift-roles.index",
            },
            {
                label: "Tipos De Movimientos",
                icon: "pi pi-fw pi-objects-column",
                to: "/catalogs/movement-types",
                permission: "movement-types.index",
            },
            {
                label: "Motivos",
                icon: "pi pi-fw pi-book",
                to: "/catalogs/reasons",
                permission: "reasons.index",
            },
            {
                label: "Prestaciones",
                icon: "pi pi-wallet",
                to: "/catalogs/benefits",
                permission: "benefits.index",
            },
            {
                label: "Empresas",
                icon: "pi pi-building",
                to: "/catalogs/companies",
                permission: "companies.index",
            },
            {
                label: "Reglas",
                icon: "pi pi-clipboard",
                to: "/catalogs/policies",
                permission: "policies.index",
            },
            {
                label: "Departamentos",
                icon: "pi pi-briefcase",
                to: "/catalogs/departments",
                permission: "departments.index",
            },
            {
                label: "Incidencias",
                icon: "pi pi-exclamation-circle",
                to: "/catalogs/incidences",
                permission: "incidences.index",
            },
            {
                label: "Categorías de Incidencias",
                icon: "pi pi-tags",
                to: "/catalogs/category-incidences",
                permission: "category-incidences.index",
            },
            {
                label: "Puestos",
                icon: "pi pi-briefcase",
                to: "/catalogs/positions",
                permission: "positions.index",
            },
            {
                label: "Clasificaciones",
                icon: "pi pi-tags",
                to: "/catalogs/classifications",
                permission: "classifications.index",
            },
            {
                label: "Direcciones",
                icon: "pi pi-address-book",
                to: "/catalogs/locations",
                permission: "locations.index",
            },
        ],
    },
    {
        label: "Empleados",
        icon: "pi pi-fw pi-users",
        items: [
            {
                label: "Vacaciones",
                icon: "pi pi-sun",
                to: "/employee/vacations",
                permission: "vacations.index",
            },
            {
                label: "Compensaciones",
                icon: "pi pi-book",
                to: "/employee/compensations",
                permission: "compensations.index",
            },
            {
                label: "No Recontratables",
                icon: "pi pi-user-minus",
                to: "/employee/no-rehirable",
                permission: "no-rehirable.index",
            },
            {
                label: "Dias de vacaciones por empleado",
                icon: "pi pi-sun",
                to: "/employee/vacations-per-employee",
                permission: "vacations-per-employee.index",
            },
            {
                label: "Incidencias por empleado",
                icon: "pi pi-exclamation-circle",
                to: "/employee/incidences-employee",
                permission: "incidences-employee.index",
            },
            {
                label: "Bloquear Incidencias",
                icon: "pi pi-lock",
                to: "/employee/block-incidents",
                permission: "block-incidents.index",
            },
            {
                label: "Catálogo Empleado",
                icon: "pi pi-id-card",
                to: "/employee/catalog",
                permission: "catalog.index",
            },
            {
                label: "TXT",
                icon: "pi pi-file",
                to: "/employee/txt",
                permission: "txt.index",
            },
            {
                label: "Historial de TXT",
                icon: "pi pi-history",
                to: "/employee/txt-history",
                permission: "txt-history.index",
            },
            {
                label: "Eficiencias",
                icon: "pi pi-bolt",
                to: "/employee/efficiency",
                permission: "efficiency.index",
            },
            {
                label: "Historial de Estados",
                icon: "pi pi-history",
                to: "/employee/state-history",
                permission: "state-history.index",
            },
            {
                label: "Ciclo de turno por empleado",
                icon: "pi pi-clock",
                to: "/employee/shift-role-cycles",
                permission: "shift-role-cycles.index",
            },
            {
                label: "Rol de turno por empleado",
                icon: "pi pi-user",
                to: "/employee/employee-shift-roles",
                permission: "employee-shift-roles.index",
            },
            {
                label: "Tiempos Extra: Estimaciones y pagos",
                icon: "pi pi-clock",
                to: "/employee/employee-overtimes",
                permission: "employee-overtimes.index",
            },
            {
                label: "Ajustes, promociones y cambios de puesto",
                icon: "pi pi-arrow-right-arrow-left",
                to: "/employee/employee-salary-adjustments",
                permission: "employee-salary-adjustments.index",
            },
            {
                label: "Busqueda de Empleado",
                icon: "pi pi-search",
                to: "/employee/search-employee",
                permission: "search-employee.index",
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
                label: "Permisos",
                icon: "pi pi-sliders-h",
                to: "/permissions",
                permission: "permissions.index",
            },
            {
                label: "Vistas",
                icon: "pi pi-desktop",
                to: "/views",
                permission: "views.index",
            },
            {
                label: "Roles",
                icon: "pi pi-user",
                to: "/roles",
                permission: "roles.index",
            },
            {
                label: "H2H",
                icon: "pi pi-wallet",
                to: "/h2h",
                permission: "h2h.index",
            },
            {
                label: "Logs",
                icon: "pi pi-inbox",
                to: "/logs",
                permission: "logs.index",
            },
            {
                label: "Errores",
                icon: "pi pi-exclamation-triangle",
                to: "/laravel-logs",
                permission: "laravel-logs.index",
            },
        ],
    },
    {
        label: "Facturación",
        icon: "pi pi-file",
        items: [
            {
                label: "XML",
                icon: "pi pi-file-excel",
                to: "/billing/xml",
                permission: "xml.index",
            },
            {
                label: "Facturas",
                icon: "pi pi-file-excel",
                to: "/billing/invoice-sat",
                permission: "invoice-sat.index",
            },
        ],
    },
]);

const filteredMenu = computed(() => {
    return rawMenu.value
        .map((section) => {
            // Filtrar items hijos que el usuario puede ver
            const visibleItems = (section.items || []).filter((item) => {
                // Si no tiene propiedad 'permission', se muestra siempre
                if (!item.permission) return true;
                // Si tiene permiso, verificar con useAuthz
                return can(item.permission);
            });

            // Retornar la sección solo si tiene al menos un item visible
            if (visibleItems.length > 0) {
                return {
                    ...section,
                    items: visibleItems,
                };
            }
            return null;
        })
        .filter((section) => section !== null); // Eliminar secciones vacías
});
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in filteredMenu" :key="item.label + '-' + i">
            <app-menu-item
                v-if="!item.separator"
                :item="item"
                :index="i"
            ></app-menu-item>
            <li
                v-if="item.separator"
                class="menu-separator border-gray-500"
            ></li>
        </template>

        <li
            v-if="filteredMenu.length === 0"
            class="p-4 text-center text-gray-500"
        >
            <i class="pi pi-info-circle mr-2"></i>
            No tienes acceso a ninguna sección
        </li>
    </ul>
</template>

<style lang="scss" scoped>
.active-item {
    font-weight: bold;
    color: var(--primary-color) !important;
}

/* ✅ FORZAR QUE NO SE CORTE EL TEXTO */
:deep(.layout-menu) {
    overflow-y: auto;
    overflow-x: hidden;
    padding-bottom: 3rem;
}

:deep(.layout-menu li) {
    overflow: visible;
    white-space: normal;
    text-overflow: clip;
}

:deep(.menu-item-link),
:deep(.layout-menuitem-root-text),
:deep(.layout-menuitem-child) {
    white-space: normal;
    word-wrap: break-word;
    word-break: break-word;
    overflow: visible;
    text-overflow: clip;
    line-height: 1.5;
    padding: 0.875rem 1rem;
    min-height: auto;
}

:deep(.layout-menuitem-icon) {
    flex-shrink: 0;
    min-width: 1.5rem;
}

:deep(.layout-submenu) {
    overflow: visible;
}
</style>
