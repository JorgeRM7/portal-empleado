<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useToastService } from "@/Stores/toastService";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import { onMounted, ref } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { useNotifications } from "@/composables/useNotifications";


const props = defineProps({
    employee: Object,
    data: Array,
});

//Función para mostrar toast de éxito y error
const { showSuccess, showError } = useToastService();
const toast = useToast();
//Inicializar variable de carga para mostrar esqueleto
const loading = ref(true);
const redirecting = ref(false);

const rowsIncidence = ref(props.data.incidencias || []);
const rowsCompensation = ref(props.data.compensaciones || []);
const rowsEstados = ref(props.data.historial_estados || []);
const rowsTiempoExtra = ref(props.data.tiempo_extra || []);

const rolActivo = ref(props.data.roles_turnos.find(r => r.active === 1) || {});
const cicloActivo = computed(() => {
    return props.data.ciclos_turno && props.data.ciclos_turno.length > 0
        ? props.data.ciclos_turno[0]
        : null;
});

// ===============================================================
// =================INICIO DE INCIDENCIAS=========================
// ===============================================================

//Inicializar filtros globales de tabla
const filtersIncidence = ref({});

//Diálogo de selección de columnas
const columnsDialogIncidence = ref(false);

//Referencias a los popovers
const opIncidence = ref();
const opMostrarColumnasIncidence = ref();
const opFijarColumnasIncidence = ref();

//Estado de visibilidad del toast
const visibleIncidence = ref(false);

//Referencia al servicio de toast personalizado
const intervalIncidence = ref();

//Filas seleccionadas
const selectedIncidence = ref([]);

const dt = ref(null);

//Funciones para alternar los popovers
const toggleMostrarColumnasIncidence = (event) => {
    opMostrarColumnasIncidence.value.toggle(event);
};

const toggleFijarColumnasIncidence = (event) => {
    opFijarColumnasIncidence.value.toggle(event);
};

//Función para inicializar filtros
const initFiltersIncidence = () => {
    filtersIncidence.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        rol: { value: null, matchMode: FilterMatchMode.CONTAINS },
        correo: { value: null, matchMode: FilterMatchMode.CONTAINS },
        nombre: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        id: { value: null, matchMode: FilterMatchMode.CONTAINS },
    };
};

initFiltersIncidence();

//Diálogo de filtros adicionales
const otherFilterDialogIncidence = ref(false);

//Columnas de exportación
const exportColumnsIncidence = ref({
    empleado: true,
    incidencia: true,
    desde: true,
    hasta: true,
    documento: true,
    comentario: true,
    aprobadoPor: true,
});

//Columnas visibles
const showColumnsIncidence = ref({
    //acciones: true,
    empleado: true,
    incidencia: true,
    desde: true,
    hasta: true,
    documento: true,
    comentario: true,
    aprobadoPor: true,
});
//Columnas fijas
const frozenColumnsIncidence = ref({
    //acciones: false,
    empleado: false,
    incidencia: false,
    desde: false,
    hasta: false,
    documento: false,
    comentario: false,
    aprobadoPor: false,
});

//Función para guardar las columnas seleccionadas para exportar
const saveColumnsIncidence = () => {
    columnsDialogIncidence.value = false;
    dt.value.exportCSV();
};

//Función para limpiar filtros
const clearFilterIncidence = () => {
    initFiltersIncidence();
    otherFiltersIncidence.value = [
        {
            startDate: null,
            endDate: null,
        },
    ];
};

//Función para aplicar filtros adicionales
const applyFiltersIncidence = () => {
    otherFilterDialogIncidence.value = false;

    // Formatear fechas a AAAA-MM-DD
    const formatDate = (date) => {
        const d = new Date(date);
        const month = "" + (d.getMonth() + 1);
        const day = "" + d.getDate();
        const year = d.getFullYear();

        return [year, month.padStart(2, "0"), day.padStart(2, "0")].join("-");
    };
    otherFiltersIncidence.value = [
        {
            startDate: formatDate(startDate.value),
            endDate: formatDate(endDate.value),
        },
    ];
    console.log("Filtros aplicados:", otherFiltersIncidence.value);
};
// ===============================================================
// =================FIN DE INCIDENCIAS============================
// ===============================================================

// ===============================================================
// =================INICIO DE COMPENSACIONES======================
// ===============================================================

//Inicializar filtros globales de tabla
const filtersCompensation = ref({});

//Inicializar variable de carga para mostrar esqueleto
// const loading = ref(true);

//Diálogo de selección de columnas
const columnsDialogCompensation = ref(false);

//Referencias a los popovers
const opCompensation = ref();
const opMostrarColumnasCompensation = ref();
const opFijarColumnasCompensation = ref();

//Estado de visibilidad del toast
const visibleCompensation = ref(false);

//Referencia al servicio de toast personalizado
const intervalCompensation = ref();

//Filas seleccionadas
const selectedCompensation = ref([]);

//Funciones para alternar los popovers
const toggleMostrarColumnasCompensation = (event) => {
    opMostrarColumnasCompensation.value.toggle(event);
};

const toggleFijarColumnasCompensation = (event) => {
    opFijarColumnasCompensation.value.toggle(event);
};

//Función para inicializar filtros
const initFiltersCompensation = () => {
    filtersCompensation.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        rol: { value: null, matchMode: FilterMatchMode.CONTAINS },
        correo: { value: null, matchMode: FilterMatchMode.CONTAINS },
        nombre: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        id: { value: null, matchMode: FilterMatchMode.CONTAINS },
    };
};

initFiltersCompensation();

//Diálogo de filtros adicionales
const otherFilterDialogCompensation = ref(false);

//Columnas de exportación
const exportColumnsCompensation = ref({
    id: true,
    empleado: true,
    departamento: true,
    puesto: true,
    compensacionPuesto: true,
    compensacionn: true,
    destajo: true,
    compensacionExtra: true,
    apoyoTransporte: true,
    semana: true,
    aprobadoPor: true,
    fechaAprovacion: true,
});

//Columnas visibles
const showColumnsCompensation = ref({
    //acciones: true,
    id: true,
    empleado: true,
    departamento: true,
    puesto: true,
    compensacionPuesto: true,
    compensacionn: true,
    destajo: true,
    compensacionExtra: true,
    apoyoTransporte: true,
    semana: true,
    aprobadoPor: true,
    fechaAprovacion: true,
});
//Columnas fijas
const frozenColumnsCompensation = ref({
    id: false,
    empleado: false,
    departamento: false,
    puesto: false,
    compensacionPuesto: false,
    compensacionn: false,
    destajo: false,
    compensacionExtra: false,
    apoyoTransporte: false,
    semana: false,
    aprobadoPor: false,
    fechaAprovacion: false,
});

//Función para guardar las columnas seleccionadas para exportar
const saveColumnsCompensation = () => {
    columnsDialogCompensation.value = false;
    dt.value.exportCSV();
};

//Función para limpiar filtros
const clearFilterCompensation = () => {
    initFiltersCompensation();
    otherFiltersCompensation.value = [
        {
            startDate: null,
            endDate: null,
        },
    ];
};

//Función para aplicar filtros adicionales
const applyFiltersCompensation = () => {
    otherFilterDialogCompensation.value = false;

    // Formatear fechas a AAAA-MM-DD
    const formatDate = (date) => {
        const d = new Date(date);
        const month = "" + (d.getMonth() + 1);
        const day = "" + d.getDate();
        const year = d.getFullYear();

        return [year, month.padStart(2, "0"), day.padStart(2, "0")].join("-");
    };
    otherFiltersCompensation.value = [
        {
            startDate: formatDate(startDate.value),
            endDate: formatDate(endDate.value),
        },
    ];
    console.log("Filtros aplicados:", otherFiltersCompensation.value);
};
// ===============================================================
// =================FIN DE COMPENSACIONES=========================
// ===============================================================

// ===============================================================
// =================INICIO DE HISTORIAL DE ESTADOS================
// ===============================================================

//Inicializar filtros globales de tabla
const filtersEstados = ref({});

//Diálogo de selección de columnas
const columnsDialogEstados = ref(false);

//Referencias a los popovers
const opEstados = ref();
const opMostrarColumnasEstados = ref();
const opFijarColumnasEstados = ref();

//Estado de visibilidad del toast
const visibleEstados = ref(false);

//Referencia al servicio de toast personalizado
const intervalEstados = ref();

//Filas seleccionadas
const selectedEstados = ref([]);

const getStatusLabel = (status) => {
    const labels = {
        'entry': 'Ingreso',
        'reentry': 'Reingreso',
        'termination': 'Terminación',
        'change': 'Traspaso'
    };
    return labels[status] || status;
};

const getStatusSeverity = (status) => {
    const severities = {
        'entry': 'success',
        'reentry': 'info',
        'termination': 'danger',
        'change': 'warning'
    };
    return severities[status] || 'secondary';
};

//Funciones para alternar los popovers
const toggleMostrarColumnasEstados = (event) => {
    opMostrarColumnasEstados.value.toggle(event);
};

const toggleFijarColumnasEstados = (event) => {
    opFijarColumnasEstados.value.toggle(event);
};

//Función para inicializar filtros
const initFiltersEstados = () => {
    filtersEstados.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        rol: { value: null, matchMode: FilterMatchMode.CONTAINS },
        correo: { value: null, matchMode: FilterMatchMode.CONTAINS },
        nombre: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        id: { value: null, matchMode: FilterMatchMode.CONTAINS },
    };
};

initFiltersEstados();

//Diálogo de filtros adicionales
const otherFilterDialogEstados = ref(false);

//Columnas de exportación
const exportColumnsEstados = ref({
    id: true,
    claveEmpleado: true,
    nombreEmpleado: true,
    razon: true,
    estatus: true,
    fecha: true,
    observaciones: true,
    creadoPor: true,
    valores: true,
});

//Columnas visibles
const showColumnsEstados = ref({
    //acciones: true,
    id: true,
    claveEmpleado: true,
    nombreEmpleado: true,
    razon: true,
    estatus: true,
    fecha: true,
    observaciones: true,
    creadoPor: true,
    valores: true,
});
//Columnas fijas
const frozenColumnsEstados = ref({
    id: false,
    claveEmpleado: false,
    nombreEmpleado: false,
    razon: false,
    estatus: false,
    fecha: false,
    observaciones: false,
    creadoPor: false,
    valores: false,
});

//Función para guardar las columnas seleccionadas para exportar
const saveColumnsEstados = () => {
    columnsDialogEstados.value = false;
    dt.value.exportCSV();
};

//Función para limpiar filtros
const clearFilterEstados = () => {
    initFiltersEstados();
    otherFiltersEstados.value = [
        {
            startDate: null,
            endDate: null,
        },
    ];
};

//Función para aplicar filtros adicionales
const applyFiltersEstados = () => {
    otherFilterDialogEstados.value = false;

    // Formatear fechas a AAAA-MM-DD
    const formatDate = (date) => {
        const d = new Date(date);
        const month = "" + (d.getMonth() + 1);
        const day = "" + d.getDate();
        const year = d.getFullYear();

        return [year, month.padStart(2, "0"), day.padStart(2, "0")].join("-");
    };
    otherFiltersEstados.value = [
        {
            startDate: formatDate(startDate.value),
            endDate: formatDate(endDate.value),
        },
    ];
    console.log("Filtros aplicados:", otherFiltersEstados.value);
};
// ===============================================================
// =================FIN DE HISTORIAL DE ESTADOS===================
// ===============================================================

// ===============================================================
// =================INICIO DE TIEMPO EXTRA========================
// ===============================================================

//Inicializar filtros globales de tabla
const filtersTiempoExtra = ref({});

//Inicializar variable de carga para mostrar esqueleto
// const loading = ref(true);

//Diálogo de selección de columnas
const columnsDialogTiempoExtra = ref(false);

//Referencias a los popovers
const opTiempoExtra = ref();
const opMostrarColumnasTiempoExtra = ref();
const opFijarColumnasTiempoExtra = ref();

//Estado de visibilidad del toast
const visibleTiempoExtra = ref(false);

//Referencia al servicio de toast personalizado
const intervalTiempoExtra = ref();

//Filas seleccionadas
const selectedTiempoExtra = ref([]);

//Funciones para alternar los popovers
const toggleMostrarColumnasTiempoExtra = (event) => {
    opMostrarColumnasTiempoExtra.value.toggle(event);
};

const toggleFijarColumnasTiempoExtra = (event) => {
    opFijarColumnasTiempoExtra.value.toggle(event);
};

//Función para inicializar filtros
const initFiltersTiempoExtra = () => {
    filtersTiempoExtra.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        rol: { value: null, matchMode: FilterMatchMode.CONTAINS },
        correo: { value: null, matchMode: FilterMatchMode.CONTAINS },
        nombre: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        id: { value: null, matchMode: FilterMatchMode.CONTAINS },
    };
};

initFiltersTiempoExtra();

//Diálogo de filtros adicionales
const otherFilterDialogTiempoExtra = ref(false);

//Columnas de exportación
const exportColumnsTiempoExtra = ref({
    claveEmpleado: true,
    empleado: true,
    fecha: true,
    horario: true,
    horasExtrasDobles: true,
    horasExtrasTriples: true,
    totalHoras: true,
    extemporaneo: true,
    primaDominical: true,
    estado: true,
});

//Columnas visibles
const showColumnsTiempoExtra = ref({
    claveEmpleado: true,
    empleado: true,
    fecha: true,
    horario: true,
    horasExtrasDobles: true,
    horasExtrasTriples: true,
    totalHoras: true,
    extemporaneo: true,
    primaDominical: true,
    estado: true,
});
//Columnas fijas
const frozenColumnsTiempoExtra = ref({
    claveEmpleado: false,
    empleado: false,
    fecha: false,
    horario: false,
    horasExtrasDobles: false,
    horasExtrasTriples: false,
    totalHoras: false,
    extemporaneo: false,
    primaDominical: false,
    estado: false,
});

//Función para guardar las columnas seleccionadas para exportar
const saveColumnsTiempoExtra = () => {
    columnsDialogTiempoExtra.value = false;
    dt.value.exportCSV();
};

//Función para limpiar filtros
const clearFilterTiempoExtra = () => {
    initFiltersTiempoExtra();
    otherFiltersTiempoExtra.value = [
        {
            startDate: null,
            endDate: null,
        },
    ];
};

//Función para aplicar filtros adicionales
const applyFiltersTiempoExtra = () => {
    otherFilterDialogTiempoExtra.value = false;

    // Formatear fechas a AAAA-MM-DD
    const formatDate = (date) => {
        const d = new Date(date);
        const month = "" + (d.getMonth() + 1);
        const day = "" + d.getDate();
        const year = d.getFullYear();

        return [year, month.padStart(2, "0"), day.padStart(2, "0")].join("-");
    };
    otherFiltersEstados.value = [
        {
            startDate: formatDate(startDate.value),
            endDate: formatDate(endDate.value),
        },
    ];
    console.log("Filtros aplicados:", otherFiltersEstados.value);
};

// ===============================================================
// =================FIN DE TIEMPO EXTRA===========================
// ===============================================================

const goToEdit = () => {
    redirecting.value = true;
    router.visit(route('catalog.edit', props.employee.id));
};

onMounted(() => {
    console.log(props)

    if (props.data && props.data.tiempo_extra) {

        rowsTiempoExtra.value = props.data.tiempo_extra.map(row => {
            let extra = { total: 0, double_overtime: 0, triple_overtime: 0, sunday_premium: false };
            if (row.TiemposExtra) {
                try {
                    extra = JSON.parse(row.TiemposExtra);
                } catch (e) {
                    console.error("Error al parsear fila ID:", row.ID);
                }
            }
            return {
                ...row,
                extra_total: Number(extra.total) || 0,
                extra_double: Number(extra.double_overtime) || 0,
                extra_triple: Number(extra.triple_overtime) || 0,
                sunday_premium_valor: extra.sunday_premium ? 1 : 0
            };
        });
    }

    loading.value = false;
});
</script>
<template>
    <AppLayout title="Detalles Empleado">
        <!-- <pre>
            {{ props }}
        </pre> -->

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <Card class="overflow-hidden border border-gray-100 shadow-sm">
                <template #content>
                    <div class="flex items-center gap-4">
                        <div class="p-3 rounded-lg flex items-center justify-center">
                            <i class="pi pi-clock" style="font-size: 2.5rem"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex justify-between items-center mb-1">
                                <h3 class="font-bold m-0">Ciclo de turno</h3>
                                <Tag :value="cicloActivo ? 'ACTIVO' : 'SIN ASIGNAR'"
                                    :severity="cicloActivo ? 'success' : 'secondary'"
                                    class="text-[10px] px-2" />
                            </div>

                            <p class="font-bold text-sm mb-2" style="font-size: 12px;">
                                {{ cicloActivo ? cicloActivo.name : 'No hay ciclo activo' }}
                                <span v-if="cicloActivo">| {{ cicloActivo.entry_time }} - {{ cicloActivo.leave_time }}</span>
                            </p>

                            <div class="grid grid-cols-2 gap-2 text-[11px]">
                                <span class="flex items-center gap-1">
                                    <i class="pi pi-calendar-plus text-[10px]"></i>
                                    Inicio: {{ cicloActivo ? cicloActivo.started_at.split(' ')[0] : 'N/A' }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <i class="pi pi-calendar-minus text-[10px]"></i>
                                    Fin: {{ cicloActivo ? (cicloActivo.ends_at || 'Indefinido') : 'N/A' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <Card class="overflow-hidden border border-gray-100 shadow-sm">
                <template #content>
                    <div class="flex items-center gap-4">
                        <div class="p-3 rounded-lg flex items-center justify-center">
                            <i class="pi pi-id-card" style="font-size: 2.5rem"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex justify-between items-center mb-1">
                                <h3 class="font-bold m-0">Rol de turno</h3>
                                <Tag :value="rolActivo.active ? 'ACTIVO' : 'INACTIVO'"
                                    :severity="rolActivo.active ? 'info' : 'danger'"
                                    class="text-[10px] px-2" />
                            </div>
                            <p class="font-bold text-sm mb-2" style="font-size: 12px;">
                                {{ rolActivo.name || 'Sin rol asignado' }}
                            </p>
                            <div class="grid grid-cols-2 gap-2 text-[11px]">
                                <span class="flex items-center gap-1">
                                    <i class="pi pi-calendar-plus text-[10px]"></i>
                                    Inicio: {{ rolActivo.start_date || 'N/A' }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <i class="pi pi-calendar-minus text-[10px]"></i>
                                    Fin: {{ rolActivo.end_date || 'Indefinido' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>
        </div>
        <Card class="shadow-sm border border-gray-200 overflow-hidden">
            <template #content>
                <Tabs value="0">
                    <TabList class="px-2">
                        <Tab value="4" class="flex items-center gap-2 cursor-pointer" @click="goToEdit">
                            <i
                                v-if="!redirecting"
                                class="pi pi-arrow-left"
                            ></i>

                            <i
                                v-else
                                class="pi pi-spin pi-spinner"
                            ></i>

                            {{ redirecting ? 'Redireccionando...' : 'Editar Empleado' }}
                        </Tab>
                        <Tab value="0" class="flex items-center gap-2">
                            <i class="pi pi-exclamation-triangle"></i> Incidencias
                        </Tab>
                        <Tab value="1" class="flex items-center gap-2">
                            <i class="pi pi-wallet"></i> Compensaciones
                        </Tab>
                        <Tab value="2" class="flex items-center gap-2">
                            <i class="pi pi-history"></i> Historial de Estados
                        </Tab>
                        <Tab value="3" class="flex items-center gap-2">
                            <i class="pi pi-stopwatch"></i> Tiempo Extra
                        </Tab>
                    </TabList>

                    <TabPanels>
                        <TabPanel value="0" class="p-0">
                            <div v-if="rowsIncidence.length > 0" class="card">
                                <Toolbar>
                                    <template #start>
                                        <Button
                                            type="button"
                                            icon="pi pi-upload"
                                            label="Exportar"
                                            severity="secondary"
                                            class="mt-2 ml-2"
                                            @click="columnsDialogIncidence = true"
                                        />
                                    </template>

                                    <template #end>
                                        <!-- <Button
                                            label="Crear"
                                            icon="pi pi-plus-circle"
                                            severity="success"
                                            class="ml-2"
                                            @click="crearNotificacion"
                                        /> -->
                                    </template>
                                </Toolbar>
                                <!-- <pre>
                                {{ rowsIncidence }}
                                </pre> -->
                                <DataTable
                                    ref="dt"
                                    v-model:selection="selectedIncidence"
                                    :value="rowsIncidence"
                                    dataKey="id"
                                    :paginator="true"
                                    :rows="10"
                                    scrollable
                                    scrollHeight="400px"
                                    tableStyle="min-width: 110rem"
                                    v-model:filters="filtersIncidence"
                                    filterDisplay="menu"
                                    exportFilename="Incidencias"
                                    :globalFilterFields="['id', 'full_name', 'incidencia', 'document_number']"
                                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                                    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} datos de Incidencias"
                                >
                                    <template #header>
                                        <div
                                            class="flex flex-wrap gap-2 items-end justify-between mb-6"
                                        >
                                            <div>
                                                <h4 class="m-0">Tabla de Incidencias</h4>
                                                <!-- <Button
                                                    icon="pi pi-filter"
                                                    rounded
                                                    v-tooltip.top="'Mostrar mas filtros'"
                                                    @click="otherFilterDialogIncidence = true"
                                                />
                                                <Button
                                                    type="button"
                                                    icon="pi pi-filter-slash"
                                                    rounded
                                                    severity="secondary"
                                                    class="mt-5 ml-2 mr-2"
                                                    v-tooltip.top="'Limpiar filtros'"
                                                    @click="clearFilterIncidence()"
                                                /> -->
                                                <Tag
                                                    v-if="selectedIncidence.length > 0"
                                                    severity="info"
                                                    :value="'Seleccionados: ' + selectedIncidence.length"
                                                ></Tag>
                                            </div>
                                            <div class="flex">
                                                <IconField>
                                                    <InputIcon>
                                                        <i class="pi pi-search" />
                                                    </InputIcon>
                                                    <InputText
                                                        v-model="filtersIncidence['global'].value"
                                                        placeholder="Buscar..."
                                                    />
                                                </IconField>
                                                <Button
                                                    type="button"
                                                    rounded
                                                    class="ml-2"
                                                    icon="pi pi-sliders-v"
                                                    severity="secondary"
                                                    @click="toggleMostrarColumnasIncidence($event)"
                                                />
                                                <Button
                                                    icon="pi pi-lock"
                                                    rounded
                                                    v-tooltip.top="'Alternar columnas fijas'"
                                                    class="ml-2"
                                                    severity="secondary"
                                                    @click="toggleFijarColumnasIncidence($event)"
                                                />

                                                <Popover ref="opMostrarColumnasIncidence">
                                                    <div class="flex flex-col gap-4">
                                                        <div>
                                                            <span class="font-medium block mb-2"
                                                                >Mostrar/Ocultar Columnas</span
                                                            >
                                                            <ul
                                                                class="list-none p-0 m-0 flex flex-col"
                                                            >
                                                                <li
                                                                    v-for="(
                                                                        value, key
                                                                    ) in showColumnsIncidence"
                                                                    :key="key"
                                                                    class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                                                                    :binary="true"
                                                                >
                                                                    <Checkbox
                                                                        v-model="showColumnsIncidence[key]"
                                                                        :inputId="key"
                                                                        :binary="true"
                                                                    />
                                                                    <label
                                                                        :for="key"
                                                                        class="font-medium text-base"
                                                                    >
                                                                        {{
                                                                            key
                                                                                .charAt(0)
                                                                                .toUpperCase() +
                                                                            key.slice(1)
                                                                        }}
                                                                    </label>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </Popover>
                                                <Popover ref="opFijarColumnasIncidence">
                                                    <div class="flex flex-col gap-4">
                                                        <div>
                                                            <span class="font-medium block mb-2"
                                                                >Fijar Columnas</span
                                                            >
                                                            <ul
                                                                class="list-none p-0 m-0 flex flex-col"
                                                            >
                                                                <li
                                                                    v-for="(
                                                                        value, key
                                                                    ) in frozenColumnsIncidence"
                                                                    :key="key"
                                                                    class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                                                                    :binary="true"
                                                                >
                                                                    <Checkbox
                                                                        v-model="frozenColumnsIncidence[key]"
                                                                        :inputId="key"
                                                                        :binary="true"
                                                                    />
                                                                    <label
                                                                        :for="key"
                                                                        class="font-medium text-base"
                                                                    >
                                                                        {{
                                                                            key
                                                                                .charAt(0)
                                                                                .toUpperCase() +
                                                                            key.slice(1)
                                                                        }}
                                                                    </label>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </Popover>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <!-- <Chip
                                                :label="
                                                    'Fecha de inicio: ' + otherFiltersIncidence[0].startDate
                                                "
                                                removable
                                                v-if="otherFiltersIncidence[0].startDate != null"
                                                @remove="removeStartDate"
                                            /> -->
                                        </div>
                                    </template>

                                    <!-- <Column
                                        selectionMode="multiple"
                                        style="width: 5rem"
                                        :exportable="false"
                                    ></Column> -->
                                    <Column
                                        field="full_name"
                                        header="Empleado"
                                        :filter="true"
                                        :frozen="frozenColumnsIncidence.empleado"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsIncidence.empleado ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsIncidence.empleado"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else>{{ data.full_name }}</span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por empleado"
                                            />
                                        </template>
                                    </Column>
                                    <Column
                                        field="incidencia"
                                        header="Incidencia"
                                        :filter="true"
                                        :frozen="frozenColumnsIncidence.incidencia"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsIncidence.incidencia ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsIncidence.incidencia"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else>{{ data.incidencia }}</span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por incidencia"
                                            />
                                        </template>
                                    </Column>
                                    <Column
                                        field="validity_from"
                                        header="Desde"
                                        :frozen="frozenColumnsIncidence.desde"
                                        sortable
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsIncidence.desde ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsIncidence.desde"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else>{{ data.validity_from }}</span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Desde"
                                            />
                                        </template>
                                    </Column>

                                    <Column
                                        field="validity_to"
                                        header="Hasta"
                                        sortable
                                        :frozen="frozenColumnsIncidence.hasta"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsIncidence.hasta ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsIncidence.hasta"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else>{{ data.validity_to }}</span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Hasta"
                                            /> </template
                                    ></Column>
                                    <Column
                                        field="document_number"
                                        header="Documento"
                                        sortable
                                        :frozen="frozenColumnsIncidence.documento"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsIncidence.documento ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsIncidence.documento"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else :class="{'italic text-gray-400': !data.document_number}">
                                                {{ (data.document_number !== null && data.document_number !== undefined)
                                                    ? data.document_number
                                                    : 'Sin Documentos' }}
                                            </span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Documento"
                                            /> </template
                                    ></Column>
                                    <Column
                                        field="comment"
                                        header="Comentario"
                                        sortable
                                        :frozen="frozenColumnsIncidence.comentario"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsIncidence.comentario ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsIncidence.comentario"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else :class="{'italic text-gray-400': !data.comment}">
                                                {{ data.comment || 'Sin comentarios' }}
                                            </span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Comentario"
                                            /> </template
                                    ></Column>
                                    <Column
                                        field="approved_by"
                                        header="Aprobado Por"
                                        sortable
                                        :frozen="frozenColumnsIncidence.aprobadoPor"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsIncidence.aprobadoPor ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsIncidence.aprobadoPor"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else>
                                                {{ data.approved_by || 'Pendiente de aprobación' }}
                                            </span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Aprobado"
                                            /> </template
                                    ></Column>
                                </DataTable>
                                <Dialog
                                    v-model:visible="columnsDialogIncidence"
                                    :style="{ width: '450px' }"
                                    header="Seleccionar columnas a exportar"
                                    :modal="true"
                                >
                                    <div class="flex flex-col gap-6">
                                        <div
                                            v-for="(value, key) in exportColumnsIncidence"
                                            :key="key"
                                            class="flex align-items-center gap-3"
                                        >
                                            <Checkbox
                                                v-model="exportColumnsIncidence[key]"
                                                :inputId="key"
                                                :binary="true"
                                            />
                                            <label :for="key" class="font-medium text-base">{{
                                                key.charAt(0).toUpperCase() + key.slice(1)
                                            }}</label>
                                        </div>
                                    </div>

                                    <template #footer>
                                        <Button
                                            label="Cancelar"
                                            icon="pi pi-times"
                                            severity="danger"
                                            @click="columnsDialogIncidence = false"
                                        />
                                        <Button
                                            label="Exportar"
                                            icon="pi pi-save"
                                            severity="success"
                                            @click="saveColumnsIncidence"
                                            :loading="submitted"
                                        />
                                    </template>
                                </Dialog>
                                <Dialog
                                    v-model:visible="otherFilterDialogIncidence"
                                    :style="{ width: '450px' }"
                                    header="Seleccionar filtros adicionales"
                                    :modal="true"
                                >
                                    <div class="flex flex-col gap-6">
                                        <DatePicker v-model="startDate" dateFormat="yy-mm-dd" />
                                        <DatePicker v-model="endDate" dateFormat="yy-mm-dd" />
                                    </div>

                                    <template #footer>
                                        <Button
                                            label="Cancelar"
                                            icon="pi pi-times"
                                            severity="danger"
                                            @click="otherFilterDialogIncidence = false"
                                        />
                                        <Button
                                            label="Filtrar"
                                            icon="pi pi-filter"
                                            severity="warn"
                                            @click="applyFiltersIncidence"
                                            :loading="submitted"
                                        />
                                    </template>
                                </Dialog>
                            </div>
                            <div v-else class="flex justify-center items-center p-8 border-2 border-dashed border-gray-200 rounded-lg">
                                <div class="flex flex-col items-center gap-3">
                                    <i class="pi pi-info-circle text-gray-400" style="font-size: 2rem"></i>
                                    <Tag severity="secondary" value="No hay información de Incidencias para mostrar" class="p-2 px-4 text-sm" />
                                </div>
                            </div>
                        </TabPanel>

                        <TabPanel value="1" class="p-0">
                            <div v-if="rowsCompensation.length > 0" class="card">
                                <Toolbar>
                                    <template #start>
                                        <Button
                                            type="button"
                                            icon="pi pi-upload"
                                            label="Exportar"
                                            severity="secondary"
                                            class="mt-2 ml-2"
                                            @click="columnsDialogCompensation = true"
                                        />
                                    </template>

                                    <template #end>
                                        <!-- <Button
                                            label="Crear"
                                            icon="pi pi-plus-circle"
                                            severity="success"
                                            class="ml-2"
                                            @click="crearNotificacion"
                                        /> -->
                                    </template>
                                </Toolbar>
                                <DataTable
                                    ref="dt"
                                    v-model:selection="selectedCompensation"
                                    :value="rowsCompensation"
                                    dataKey="id"
                                    :paginator="true"
                                    :rows="10"
                                    scrollable
                                    scrollHeight="400px"
                                    tableStyle="min-width: 110rem"
                                    v-model:filters="filtersCompensation"
                                    filterDisplay="menu"
                                    exportFilename="Compensaciones"
                                    :globalFilterFields="['id', 'rol', 'correo', 'nombre']"
                                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                                    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} datos de Compensaciones"
                                >
                                    <template #header>
                                        <div
                                            class="flex flex-wrap gap-2 items-end justify-between mb-6"
                                        >
                                            <div>
                                                <h4 class="m-0">Tabla de Compensaciones</h4>
                                                <!-- <Button
                                                    icon="pi pi-filter"
                                                    rounded
                                                    v-tooltip.top="'Mostrar mas filtros'"
                                                    @click="otherFilterDialogCompensation = true"
                                                />
                                                <Button
                                                    type="button"
                                                    icon="pi pi-filter-slash"
                                                    rounded
                                                    severity="secondary"
                                                    class="mt-5 ml-2 mr-2"
                                                    v-tooltip.top="'Limpiar filtros'"
                                                    @click="clearFilterCompensation()"
                                                /> -->
                                                <Tag
                                                    v-if="selectedCompensation.length > 0"
                                                    severity="info"
                                                    :value="'Seleccionados: ' + selectedCompensation.length"
                                                ></Tag>
                                            </div>
                                            <div class="flex">
                                                <IconField>
                                                    <InputIcon>
                                                        <i class="pi pi-search" />
                                                    </InputIcon>
                                                    <InputText
                                                        v-model="filtersCompensation['global'].value"
                                                        placeholder="Buscar..."
                                                    />
                                                </IconField>
                                                <Button
                                                    type="button"
                                                    rounded
                                                    class="ml-2"
                                                    icon="pi pi-sliders-v"
                                                    severity="secondary"
                                                    @click="toggleMostrarColumnasCompensation($event)"
                                                />
                                                <Button
                                                    icon="pi pi-lock"
                                                    rounded
                                                    v-tooltip.top="'Alternar columnas fijas'"
                                                    class="ml-2"
                                                    severity="secondary"
                                                    @click="toggleFijarColumnasCompensation($event)"
                                                />

                                                <Popover ref="opMostrarColumnasCompensation">
                                                    <div class="flex flex-col gap-4">
                                                        <div>
                                                            <span class="font-medium block mb-2"
                                                                >Mostrar/Ocultar Columnas</span
                                                            >
                                                            <ul
                                                                class="list-none p-0 m-0 flex flex-col"
                                                            >
                                                                <li
                                                                    v-for="(
                                                                        value, key
                                                                    ) in showColumnsCompensation"
                                                                    :key="key"
                                                                    class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                                                                    :binary="true"
                                                                >
                                                                    <Checkbox
                                                                        v-model="showColumnsCompensation[key]"
                                                                        :inputId="key"
                                                                        :binary="true"
                                                                    />
                                                                    <label
                                                                        :for="key"
                                                                        class="font-medium text-base"
                                                                    >
                                                                        {{
                                                                            key
                                                                                .charAt(0)
                                                                                .toUpperCase() +
                                                                            key.slice(1)
                                                                        }}
                                                                    </label>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </Popover>
                                                <Popover ref="opFijarColumnasCompensation">
                                                    <div class="flex flex-col gap-4">
                                                        <div>
                                                            <span class="font-medium block mb-2"
                                                                >Fijar Columnas</span
                                                            >
                                                            <ul
                                                                class="list-none p-0 m-0 flex flex-col"
                                                            >
                                                                <li
                                                                    v-for="(
                                                                        value, key
                                                                    ) in frozenColumnsCompensation"
                                                                    :key="key"
                                                                    class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                                                                    :binary="true"
                                                                >
                                                                    <Checkbox
                                                                        v-model="frozenColumnsCompensation[key]"
                                                                        :inputId="key"
                                                                        :binary="true"
                                                                    />
                                                                    <label
                                                                        :for="key"
                                                                        class="font-medium text-base"
                                                                    >
                                                                        {{
                                                                            key
                                                                                .charAt(0)
                                                                                .toUpperCase() +
                                                                            key.slice(1)
                                                                        }}
                                                                    </label>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </Popover>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <!-- <Chip
                                                :label="
                                                    'Fecha de inicio: ' + otherFiltersCompensation[0].startDate
                                                "
                                                removable
                                                v-if="otherFiltersCompensation[0].startDate != null"
                                                @remove="removeStartDate"
                                            /> -->
                                        </div>
                                    </template>

                                    <Column
                                        selectionMode="multiple"
                                        style="width: 5rem"
                                        :exportable="false"
                                    ></Column>
                                    <Column
                                        field="ID"
                                        header="ID"
                                        :filter="true"
                                        :frozen="frozenColumnsCompensation.id"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsCompensation.id ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsCompensation.id"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else>{{ data.ID }}</span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por ID"
                                            />
                                        </template>
                                    </Column>
                                    <Column
                                        field="Empleado"
                                        header="Empleado"
                                        :filter="true"
                                        :frozen="frozenColumnsCompensation.empleado"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsCompensation.empleado ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsCompensation.empleado"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else>{{ data.Empleado }}</span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por empleado"
                                            />
                                        </template>
                                    </Column>
                                    <Column
                                        field="Departamento"
                                        header="Departamento"
                                        :frozen="frozenColumnsCompensation.departamento"
                                        sortable
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsCompensation.departamento ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsCompensation.departamento"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else>{{ data.Departamento }}</span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por departamento"
                                            />
                                        </template>
                                    </Column>
                                    <Column
                                        field="Posicion"
                                        header="Puesto"
                                        :frozen="frozenColumnsCompensation.puesto"
                                        sortable
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsCompensation.puesto ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsCompensation.puesto"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else>{{ data.Posicion }}</span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por puesto"
                                            />
                                        </template>
                                    </Column>

                                    <Column
                                        field="CompePuesto"
                                        header="Compensación del Puesto"
                                        sortable
                                        :frozen="frozenColumnsCompensation.compensacionPuesto"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsCompensation.compensacionPuesto ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsCompensation.compensacionPuesto"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else>{{ data.CompePuesto }}</span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Compensacion Puesto"
                                            /> </template
                                    ></Column>
                                    <Column
                                        field="Compensacion"
                                        header="Compensación"
                                        sortable
                                        :frozen="frozenColumnsCompensation.compensacionn"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsCompensation.compensacionn ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsCompensation.compensacionn"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>

                                            <span v-else :class="{'italic text-gray-400': !data.Compensacion}">
                                                {{ data.Compensacion || 'Sin Compensación' }}
                                            </span>

                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Compensacion"
                                            /> </template
                                    ></Column>
                                    <Column
                                        field="Destajo"
                                        header="Destajo"
                                        sortable
                                        :frozen="frozenColumnsCompensation.destajo"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsCompensation.destajo ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsCompensation.destajo"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else :class="{'italic text-gray-400': !data.Destajo}">
                                                {{ data.Destajo || 'Sin Destajo' }}
                                            </span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Destajo"
                                            /> </template
                                    ></Column>
                                    <Column
                                        field="ExtraCompensacion"
                                        header="Compensación Extra"
                                        sortable
                                        :frozen="frozenColumnsCompensation.compensacionExtra"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsCompensation.compensacionExtra ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsCompensation.compensacionExtra"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else :class="{'italic text-gray-400': !data.ExtraCompensacion}">
                                                {{ data.ExtraCompensacion || 'Sin Compensación Extra' }}
                                            </span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Compensacion Extra"
                                            /> </template
                                    ></Column>

                                    <Column
                                        field="Transporte"
                                        header="Apoyo Transporte"
                                        sortable
                                        :frozen="frozenColumnsCompensation.apoyoTransporte"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsCompensation.apoyoTransporte ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsCompensation.apoyoTransporte"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else :class="{'italic text-gray-400': !data.Transporte}">
                                                {{ data.Transporte || 'Sin Apoyo Transporte' }}
                                            </span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Transporte"
                                            /> </template
                                    ></Column>
                                    <Column
                                        field="Semana"
                                        header="Semana"
                                        sortable
                                        :frozen="frozenColumnsCompensation.semana"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsCompensation.semana ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsCompensation.semana"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else :class="{'italic text-gray-400': !data.Semana}">
                                                {{ data.Semana || '---' }}
                                            </span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Semana"
                                            /> </template
                                    ></Column>
                                    <Column
                                        field="Aprovado"
                                        header="Aprobado por"
                                        sortable
                                        :frozen="frozenColumnsCompensation.aprobadoPor"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsCompensation.aprobadoPor ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsCompensation.aprobadoPor"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else :class="{'italic text-gray-400': !data.Aprovado}">
                                                {{ data.Aprovado || '---' }}
                                            </span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Aprovado"
                                            /> </template
                                    ></Column>
                                    <Column
                                        field="FechaAprovado"
                                        header="Fecha aprobación"
                                        sortable
                                        :frozen="frozenColumnsCompensation.fechaAprovacion"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsCompensation.fechaAprovacion ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsCompensation.fechaAprovacion"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else :class="{'italic text-gray-400': !data.FechaAprovado}">
                                                {{ data.FechaAprovado || '---' }}
                                            </span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Fecha Aprovado"
                                            /> </template
                                    ></Column>
                                </DataTable>
                                <Dialog
                                    v-model:visible="columnsDialogCompensation"
                                    :style="{ width: '450px' }"
                                    header="Seleccionar columnas a exportar"
                                    :modal="true"
                                >
                                    <div class="flex flex-col gap-6">
                                        <div
                                            v-for="(value, key) in exportColumnsCompensation"
                                            :key="key"
                                            class="flex align-items-center gap-3"
                                        >
                                            <Checkbox
                                                v-model="exportColumnsCompensation[key]"
                                                :inputId="key"
                                                :binary="true"
                                            />
                                            <label :for="key" class="font-medium text-base">{{
                                                key.charAt(0).toUpperCase() + key.slice(1)
                                            }}</label>
                                        </div>
                                    </div>

                                    <template #footer>
                                        <Button
                                            label="Cancelar"
                                            icon="pi pi-times"
                                            severity="danger"
                                            @click="columnsDialogCompensation = false"
                                        />
                                        <Button
                                            label="Exportar"
                                            icon="pi pi-save"
                                            severity="success"
                                            @click="saveColumnsCompensation"
                                            :loading="submitted"
                                        />
                                    </template>
                                </Dialog>
                                <Dialog
                                    v-model:visible="otherFilterDialogCompensation"
                                    :style="{ width: '450px' }"
                                    header="Seleccionar filtros adicionales"
                                    :modal="true"
                                >
                                    <div class="flex flex-col gap-6">
                                        <DatePicker v-model="startDate" dateFormat="yy-mm-dd" />
                                        <DatePicker v-model="endDate" dateFormat="yy-mm-dd" />
                                    </div>

                                    <template #footer>
                                        <Button
                                            label="Cancelar"
                                            icon="pi pi-times"
                                            severity="danger"
                                            @click="otherFilterDialogCompensation = false"
                                        />
                                        <Button
                                            label="Filtrar"
                                            icon="pi pi-filter"
                                            severity="warn"
                                            @click="applyFiltersCompensation"
                                            :loading="submitted"
                                        />
                                    </template>
                                </Dialog>
                            </div>
                            <div v-else class="flex justify-center items-center p-8 border-2 border-dashed border-gray-200 rounded-lg">
                                <div class="flex flex-col items-center gap-3">
                                    <i class="pi pi-info-circle text-gray-400" style="font-size: 2rem"></i>
                                    <Tag severity="secondary" value="No hay información de Compensación para mostrar" class="p-2 px-4 text-sm" />
                                </div>
                            </div>
                        </TabPanel>

                        <TabPanel value="2" class="p-0">
                            <div v-if="rowsEstados.length > 0" class="card">
                                <Toolbar>
                                    <template #start>
                                        <Button
                                            type="button"
                                            icon="pi pi-upload"
                                            label="Exportar"
                                            severity="secondary"
                                            class="mt-2 ml-2"
                                            @click="columnsDialogEstados = true"
                                        />
                                    </template>

                                    <template #end>
                                        <!-- <Button
                                            label="Crear"
                                            icon="pi pi-plus-circle"
                                            severity="success"
                                            class="ml-2"
                                            @click="crearNotificacion"
                                        /> -->
                                    </template>
                                </Toolbar>
                                <DataTable
                                    ref="dt"
                                    v-model:selection="selectedEstados"
                                    :value="rowsEstados"
                                    dataKey="id"
                                    :paginator="true"
                                    :rows="10"
                                    scrollable
                                    scrollHeight="400px"
                                    tableStyle="min-width: 110rem"
                                    v-model:filters="filtersEstados"
                                    filterDisplay="menu"
                                    exportFilename="Historial de Estados"
                                    :globalFilterFields="['id', 'rol', 'correo', 'nombre']"
                                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                                    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} datos de Historial de Estados"
                                >
                                    <template #header>
                                        <div
                                            class="flex flex-wrap gap-2 items-end justify-between mb-6"
                                        >
                                            <div>
                                                <h4 class="m-0">Tabla de Historial de Estados</h4>
                                                <!-- <Button
                                                    icon="pi pi-filter"
                                                    rounded
                                                    v-tooltip.top="'Mostrar mas filtros'"
                                                    @click="otherFilterDialogEstados = true"
                                                />
                                                <Button
                                                    type="button"
                                                    icon="pi pi-filter-slash"
                                                    rounded
                                                    severity="secondary"
                                                    class="mt-5 ml-2 mr-2"
                                                    v-tooltip.top="'Limpiar filtros'"
                                                    @click="clearFilterEstados()"
                                                /> -->
                                                <Tag
                                                    v-if="selectedEstados.length > 0"
                                                    severity="info"
                                                    :value="'Seleccionados: ' + selectedEstados.length"
                                                ></Tag>
                                            </div>
                                            <div class="flex">
                                                <IconField>
                                                    <InputIcon>
                                                        <i class="pi pi-search" />
                                                    </InputIcon>
                                                    <InputText
                                                        v-model="filtersEstados['global'].value"
                                                        placeholder="Buscar..."
                                                    />
                                                </IconField>
                                                <Button
                                                    type="button"
                                                    rounded
                                                    class="ml-2"
                                                    icon="pi pi-sliders-v"
                                                    severity="secondary"
                                                    @click="toggleMostrarColumnasEstados($event)"
                                                />
                                                <Button
                                                    icon="pi pi-lock"
                                                    rounded
                                                    v-tooltip.top="'Alternar columnas fijas'"
                                                    class="ml-2"
                                                    severity="secondary"
                                                    @click="toggleFijarColumnasEstados($event)"
                                                />

                                                <Popover ref="opMostrarColumnasEstados">
                                                    <div class="flex flex-col gap-4">
                                                        <div>
                                                            <span class="font-medium block mb-2"
                                                                >Mostrar/Ocultar Columnas</span
                                                            >
                                                            <ul
                                                                class="list-none p-0 m-0 flex flex-col"
                                                            >
                                                                <li
                                                                    v-for="(
                                                                        value, key
                                                                    ) in showColumnsEstados"
                                                                    :key="key"
                                                                    class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                                                                    :binary="true"
                                                                >
                                                                    <Checkbox
                                                                        v-model="showColumnsEstados[key]"
                                                                        :inputId="key"
                                                                        :binary="true"
                                                                    />
                                                                    <label
                                                                        :for="key"
                                                                        class="font-medium text-base"
                                                                    >
                                                                        {{
                                                                            key
                                                                                .charAt(0)
                                                                                .toUpperCase() +
                                                                            key.slice(1)
                                                                        }}
                                                                    </label>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </Popover>
                                                <Popover ref="opFijarColumnasEstados">
                                                    <div class="flex flex-col gap-4">
                                                        <div>
                                                            <span class="font-medium block mb-2"
                                                                >Fijar Columnas</span
                                                            >
                                                            <ul
                                                                class="list-none p-0 m-0 flex flex-col"
                                                            >
                                                                <li
                                                                    v-for="(
                                                                        value, key
                                                                    ) in frozenColumnsEstados"
                                                                    :key="key"
                                                                    class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                                                                    :binary="true"
                                                                >
                                                                    <Checkbox
                                                                        v-model="frozenColumnsEstados[key]"
                                                                        :inputId="key"
                                                                        :binary="true"
                                                                    />
                                                                    <label
                                                                        :for="key"
                                                                        class="font-medium text-base"
                                                                    >
                                                                        {{
                                                                            key
                                                                                .charAt(0)
                                                                                .toUpperCase() +
                                                                            key.slice(1)
                                                                        }}
                                                                    </label>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </Popover>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <!-- <Chip
                                                :label="
                                                    'Fecha de inicio: ' + otherFiltersEstados[0].startDate
                                                "
                                                removable
                                                v-if="otherFiltersEstados[0].startDate != null"
                                                @remove="removeStartDate"
                                            /> -->
                                        </div>
                                    </template>

                                    <Column
                                        selectionMode="multiple"
                                        style="width: 5rem"
                                        :exportable="false"
                                    ></Column>
                                    <Column
                                        field="id"
                                        header="ID"
                                        :filter="true"
                                        :frozen="frozenColumnsEstados.id"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsEstados.id ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsEstados.id"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else>{{ data.id }}</span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por ID"
                                            />
                                        </template>
                                    </Column>
                                    <Column
                                        field="employee_id"
                                        header="Clave empleado"
                                        :filter="true"
                                        :frozen="frozenColumnsEstados.claveEmpleado"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsEstados.claveEmpleado ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsEstados.claveEmpleado"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else>{{ data.employee_id }}</span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Clave empleado"
                                            />
                                        </template>
                                    </Column>
                                    <Column
                                        field="employee_name"
                                        header="Nombre empleado"
                                        :frozen="frozenColumnsEstados.nombreEmpleado"
                                        sortable
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsEstados.nombreEmpleado ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsEstados.nombreEmpleado"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else>{{ data.employee_name }}</span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Nombre empleado"
                                            />
                                        </template>
                                    </Column>
                                    <Column
                                        field="reason_name"
                                        header="Razón"
                                        :frozen="frozenColumnsEstados.razon"
                                        sortable
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsEstados.razon ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsEstados.razon"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else>{{ data.reason_name }}</span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Razón"
                                            />
                                        </template>
                                    </Column>

                                    <Column
                                        field="status"
                                        header="Estatus"
                                        sortable
                                        :frozen="frozenColumnsEstados.estatus"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsEstados.estatus ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsEstados.estatus"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <Tag
                                                v-else
                                                :value="getStatusLabel(data.status)"
                                                :severity="getStatusSeverity(data.status)"
                                            />
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Estatus"
                                            /> </template
                                    ></Column>
                                    <Column
                                        field="date"
                                        header="Fecha"
                                        sortable
                                        :frozen="frozenColumnsEstados.fecha"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsEstados.fecha ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsEstados.fecha"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else>{{ data.date }}</span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Fecha"
                                            /> </template
                                    ></Column>
                                    <Column
                                        field="content"
                                        header="Observaciones"
                                        sortable
                                        :frozen="frozenColumnsEstados.observaciones"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsEstados.observaciones ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsEstados.observaciones"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else :class="{'italic text-gray-400': !data.content}">
                                                {{ data.content || '---' }}
                                            </span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Observaciones"
                                            /> </template
                                    ></Column>
                                    <Column
                                        field="user_name"
                                        header="Creado por"
                                        sortable
                                        :frozen="frozenColumnsEstados.creadoPor"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsEstados.creadoPor ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsEstados.creadoPor"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else :class="{'italic text-gray-400': !data.user_name}">
                                                {{ data.user_name || '---' }}
                                            </span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Creado por"
                                            /> </template
                                    ></Column>

                                    <Column
                                        field="user_name"
                                        header="Valores"
                                        sortable
                                        :frozen="frozenColumnsEstados.valores"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsEstados.valores ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsEstados.valores"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else :class="{'italic text-gray-400': !data.user_name}">
                                                {{ data.user_name || '---' }}
                                            </span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Valores"
                                            /> </template
                                    ></Column>

                                </DataTable>
                                <Dialog
                                    v-model:visible="columnsDialogEstados"
                                    :style="{ width: '450px' }"
                                    header="Seleccionar columnas a exportar"
                                    :modal="true"
                                >
                                    <div class="flex flex-col gap-6">
                                        <div
                                            v-for="(value, key) in exportColumnsEstados"
                                            :key="key"
                                            class="flex align-items-center gap-3"
                                        >
                                            <Checkbox
                                                v-model="exportColumnsEstados[key]"
                                                :inputId="key"
                                                :binary="true"
                                            />
                                            <label :for="key" class="font-medium text-base">{{
                                                key.charAt(0).toUpperCase() + key.slice(1)
                                            }}</label>
                                        </div>
                                    </div>

                                    <template #footer>
                                        <Button
                                            label="Cancelar"
                                            icon="pi pi-times"
                                            severity="danger"
                                            @click="columnsDialogEstados = false"
                                        />
                                        <Button
                                            label="Exportar"
                                            icon="pi pi-save"
                                            severity="success"
                                            @click="saveColumnsEstados"
                                            :loading="submitted"
                                        />
                                    </template>
                                </Dialog>
                                <Dialog
                                    v-model:visible="otherFilterDialogEstados"
                                    :style="{ width: '450px' }"
                                    header="Seleccionar filtros adicionales"
                                    :modal="true"
                                >
                                    <div class="flex flex-col gap-6">
                                        <DatePicker v-model="startDate" dateFormat="yy-mm-dd" />
                                        <DatePicker v-model="endDate" dateFormat="yy-mm-dd" />
                                    </div>

                                    <template #footer>
                                        <Button
                                            label="Cancelar"
                                            icon="pi pi-times"
                                            severity="danger"
                                            @click="otherFilterDialogEstados = false"
                                        />
                                        <Button
                                            label="Filtrar"
                                            icon="pi pi-filter"
                                            severity="warn"
                                            @click="applyFiltersEstados"
                                            :loading="submitted"
                                        />
                                    </template>
                                </Dialog>
                            </div>

                            <div v-else class="flex justify-center items-center p-8 border-2 border-dashed border-gray-200 rounded-lg">
                                <div class="flex flex-col items-center gap-3">
                                    <i class="pi pi-info-circle text-gray-400" style="font-size: 2rem"></i>
                                    <Tag severity="secondary" value="No hay información de Historial de Estados para mostrar" class="p-2 px-4 text-sm" />
                                </div>
                            </div>
                        </TabPanel>

                        <TabPanel value="3" class="p-0">
                            <div v-if="rowsTiempoExtra.length > 0" class="card">
                                <Toolbar>
                                    <template #start>
                                        <Button
                                            type="button"
                                            icon="pi pi-upload"
                                            label="Exportar"
                                            severity="secondary"
                                            class="mt-2 ml-2"
                                            @click="columnsDialogTiempoExtra = true"
                                        />
                                    </template>

                                    <template #end>
                                        <!-- <Button
                                            label="Crear"
                                            icon="pi pi-plus-circle"
                                            severity="success"
                                            class="ml-2"
                                            @click="crearNotificacion"
                                        /> -->
                                    </template>
                                </Toolbar>
                                <DataTable
                                    ref="dt"
                                    v-model:selection="selectedTiempoExtra"
                                    :value="rowsTiempoExtra"
                                    dataKey="id"
                                    :paginator="true"
                                    :rows="10"
                                    scrollable
                                    scrollHeight="400px"
                                    tableStyle="min-width: 110rem"
                                    v-model:filters="filtersTiempoExtra"
                                    filterDisplay="menu"
                                    exportFilename="Tiempo Extra"
                                    :globalFilterFields="['id', 'rol', 'correo', 'nombre']"
                                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                                    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} datos de Tiempo Extra"
                                >
                                    <template #header>
                                        <div
                                            class="flex flex-wrap gap-2 items-end justify-between mb-6"
                                        >
                                            <div>
                                                <h4 class="m-0">Tabla de Tiempo Extra</h4>
                                                <!-- <Button
                                                    icon="pi pi-filter"
                                                    rounded
                                                    v-tooltip.top="'Mostrar mas filtros'"
                                                    @click="otherFilterDialogTiempoExtra = true"
                                                />
                                                <Button
                                                    type="button"
                                                    icon="pi pi-filter-slash"
                                                    rounded
                                                    severity="secondary"
                                                    class="mt-5 ml-2 mr-2"
                                                    v-tooltip.top="'Limpiar filtros'"
                                                    @click="clearFilterTiempoExtra()"
                                                /> -->
                                                <Tag
                                                    v-if="selectedTiempoExtra.length > 0"
                                                    severity="info"
                                                    :value="'Seleccionados: ' + selectedTiempoExtra.length"
                                                ></Tag>
                                            </div>
                                            <div class="flex">
                                                <IconField>
                                                    <InputIcon>
                                                        <i class="pi pi-search" />
                                                    </InputIcon>
                                                    <InputText
                                                        v-model="filtersTiempoExtra['global'].value"
                                                        placeholder="Buscar..."
                                                    />
                                                </IconField>
                                                <Button
                                                    type="button"
                                                    rounded
                                                    class="ml-2"
                                                    icon="pi pi-sliders-v"
                                                    severity="secondary"
                                                    @click="toggleMostrarColumnasTiempoExtra($event)"
                                                />
                                                <Button
                                                    icon="pi pi-lock"
                                                    rounded
                                                    v-tooltip.top="'Alternar columnas fijas'"
                                                    class="ml-2"
                                                    severity="secondary"
                                                    @click="toggleFijarColumnasTiempoExtra($event)"
                                                />

                                                <Popover ref="opMostrarColumnasTiempoExtra">
                                                    <div class="flex flex-col gap-4">
                                                        <div>
                                                            <span class="font-medium block mb-2"
                                                                >Mostrar/Ocultar Columnas</span
                                                            >
                                                            <ul
                                                                class="list-none p-0 m-0 flex flex-col"
                                                            >
                                                                <li
                                                                    v-for="(
                                                                        value, key
                                                                    ) in showColumnsTiempoExtra"
                                                                    :key="key"
                                                                    class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                                                                    :binary="true"
                                                                >
                                                                    <Checkbox
                                                                        v-model="showColumnsTiempoExtra[key]"
                                                                        :inputId="key"
                                                                        :binary="true"
                                                                    />
                                                                    <label
                                                                        :for="key"
                                                                        class="font-medium text-base"
                                                                    >
                                                                        {{
                                                                            key
                                                                                .charAt(0)
                                                                                .toUpperCase() +
                                                                            key.slice(1)
                                                                        }}
                                                                    </label>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </Popover>
                                                <Popover ref="opFijarColumnasTiempoExtra">
                                                    <div class="flex flex-col gap-4">
                                                        <div>
                                                            <span class="font-medium block mb-2"
                                                                >Fijar Columnas</span
                                                            >
                                                            <ul
                                                                class="list-none p-0 m-0 flex flex-col"
                                                            >
                                                                <li
                                                                    v-for="(
                                                                        value, key
                                                                    ) in frozenColumnsTiempoExtra"
                                                                    :key="key"
                                                                    class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                                                                    :binary="true"
                                                                >
                                                                    <Checkbox
                                                                        v-model="frozenColumnsTiempoExtra[key]"
                                                                        :inputId="key"
                                                                        :binary="true"
                                                                    />
                                                                    <label
                                                                        :for="key"
                                                                        class="font-medium text-base"
                                                                    >
                                                                        {{
                                                                            key
                                                                                .charAt(0)
                                                                                .toUpperCase() +
                                                                            key.slice(1)
                                                                        }}
                                                                    </label>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </Popover>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <!-- <Chip
                                                :label="
                                                    'Fecha de inicio: ' + otherFiltersTiempoExtra[0].startDate
                                                "
                                                removable
                                                v-if="otherFiltersTiempoExtra[0].startDate != null"
                                                @remove="removeStartDate"
                                            /> -->
                                        </div>
                                    </template>

                                    <!-- <Column
                                        selectionMode="multiple"
                                        style="width: 5rem"
                                        :exportable="false"
                                    ></Column> -->
                                    <Column
                                        field="EmpleadoID"
                                        header="Clave Empleado"
                                        :filter="true"
                                        :frozen="frozenColumnsTiempoExtra.claveEmpleado"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsTiempoExtra.claveEmpleado ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsTiempoExtra.claveEmpleado"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else>{{ data.EmpleadoID }}</span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Clave Empleado"
                                            />
                                        </template>
                                    </Column>
                                    <Column
                                        field="Empleado"
                                        header="Empleado"
                                        :filter="true"
                                        :frozen="frozenColumnsTiempoExtra.empleado"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsTiempoExtra.empleado ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsTiempoExtra.empleado"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else>{{ data.Empleado }}</span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Empleado"
                                            />
                                        </template>
                                    </Column>
                                    <Column
                                        field="Fecha"
                                        header="Fecha"
                                        :frozen="frozenColumnsTiempoExtra.fecha"
                                        sortable
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsTiempoExtra.fecha ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsTiempoExtra.fecha"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else>{{ data.Fecha }}</span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Fecha"
                                            />
                                        </template>
                                    </Column>
                                    <Column
                                        field="Horario"
                                        header="Horario"
                                        :frozen="frozenColumnsTiempoExtra.horario"
                                        sortable
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsTiempoExtra.horario ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsTiempoExtra.horario"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else :class="{'italic text-gray-400': !data.Horario}">
                                                {{ data.Horario || '---' }}
                                            </span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Horario"
                                            />
                                        </template>
                                    </Column>

                                    <Column
                                        field="extra_double"
                                        header="Horas extra dobles"
                                        sortable
                                        :frozen="frozenColumnsTiempoExtra.horasExtrasDobles"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsTiempoExtra.horasExtrasDobles ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsTiempoExtra.horasExtrasDobles"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <Tag
                                                v-else
                                                :value="data.extra_double"
                                                :severity="data.extra_double > 0 ? 'info' : 'danger'"
                                            />
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Horas extra dobles"
                                            /> </template
                                    ></Column>
                                    <Column
                                        field="extra_triple"
                                        header="Horas extra triples"
                                        sortable
                                        :frozen="frozenColumnsTiempoExtra.horasExtrasTriples"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsTiempoExtra.horasExtrasTriples ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsTiempoExtra.horasExtrasTriples"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <Tag
                                                v-else
                                                :value="data.extra_triple"
                                                :severity="data.extra_triple > 0 ? 'warn' : 'danger'"
                                            />
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Horas extra triples"
                                            /> </template
                                    ></Column>
                                    <Column
                                        field="extra_total"
                                        header="Total horas"
                                        sortable
                                        :frozen="frozenColumnsTiempoExtra.totalHoras"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsTiempoExtra.totalHoras ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsTiempoExtra.totalHoras"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <Tag
                                                v-else
                                                :value="data.extra_total"
                                                :severity="data.extra_total > 0 ? 'success' : 'danger'"
                                            />
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Total horas"
                                            /> </template
                                    ></Column>
                                    <Column
                                        field="Extemporaneo"
                                        header="Extemporaneo"
                                        sortable
                                        :frozen="frozenColumnsTiempoExtra.extemporaneo"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsTiempoExtra.extemporaneo ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsTiempoExtra.extemporaneo"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <span v-else>{{ data.Extemporaneo }}</span>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Extemporaneo"
                                            /> </template
                                    ></Column>

                                    <Column
                                        field="nombre"
                                        header="Prima dominical"
                                        sortable
                                        :frozen="frozenColumnsTiempoExtra.primaDominical"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsTiempoExtra.primaDominical ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsTiempoExtra.primaDominical"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <Tag
                                                v-else
                                                :value="data.sunday_premium_valor === 1 ? '1' : '0'"
                                                :severity="data.sunday_premium_valor === 1 ? 'success' : 'secondary'"
                                            />
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Nombre"
                                            /> </template
                                    ></Column>
                                    <Column
                                        field="estado"
                                        header="Estado"
                                        sortable
                                        :frozen="frozenColumnsTiempoExtra.estado"
                                        :style="{
                                            width: '20rem',
                                            display: showColumnsTiempoExtra.estado ? '' : 'none',
                                        }"
                                        :exportable="exportColumnsTiempoExtra.estado"
                                    >
                                        <template #body="{ data }">
                                            <Skeleton v-if="loading"></Skeleton>
                                            <template v-else>
                                                <Tag
                                                    v-if="data.Aprovado"
                                                    value="Aprobado"
                                                    severity="success"
                                                />
                                                <Tag
                                                    v-else-if="data.Declinado"
                                                    value="Declinado"
                                                    severity="danger"
                                                />
                                                <Tag
                                                    v-else
                                                    value="Pendiente"
                                                    severity="info"
                                                />
                                            </template>
                                        </template>
                                        <template #filter="{ filterModel }">
                                            <InputText
                                                v-model="filterModel.value"
                                                type="text"
                                                placeholder="Buscar por Estado"
                                            />
                                        </template>
                                    </Column>

                                </DataTable>
                                <Dialog
                                    v-model:visible="columnsDialogTiempoExtra"
                                    :style="{ width: '450px' }"
                                    header="Seleccionar columnas a exportar"
                                    :modal="true"
                                >
                                    <div class="flex flex-col gap-6">
                                        <div
                                            v-for="(value, key) in exportColumnsTiempoExtra"
                                            :key="key"
                                            class="flex align-items-center gap-3"
                                        >
                                            <Checkbox
                                                v-model="exportColumnsTiempoExtra[key]"
                                                :inputId="key"
                                                :binary="true"
                                            />
                                            <label :for="key" class="font-medium text-base">{{
                                                key.charAt(0).toUpperCase() + key.slice(1)
                                            }}</label>
                                        </div>
                                    </div>

                                    <template #footer>
                                        <Button
                                            label="Cancelar"
                                            icon="pi pi-times"
                                            severity="danger"
                                            @click="columnsDialogTiempoExtra = false"
                                        />
                                        <Button
                                            label="Exportar"
                                            icon="pi pi-save"
                                            severity="success"
                                            @click="saveColumnsTiempoExtra"
                                            :loading="submitted"
                                        />
                                    </template>
                                </Dialog>
                                <Dialog
                                    v-model:visible="otherFilterDialogTiempoExtra"
                                    :style="{ width: '450px' }"
                                    header="Seleccionar filtros adicionales"
                                    :modal="true"
                                >
                                    <div class="flex flex-col gap-6">
                                        <DatePicker v-model="startDate" dateFormat="yy-mm-dd" />
                                        <DatePicker v-model="endDate" dateFormat="yy-mm-dd" />
                                    </div>

                                    <template #footer>
                                        <Button
                                            label="Cancelar"
                                            icon="pi pi-times"
                                            severity="danger"
                                            @click="otherFilterDialogTiempoExtra = false"
                                        />
                                        <Button
                                            label="Filtrar"
                                            icon="pi pi-filter"
                                            severity="warn"
                                            @click="applyFiltersTiempoExtra"
                                            :loading="submitted"
                                        />
                                    </template>
                                </Dialog>
                            </div>
                            <div v-else class="flex justify-center items-center p-8 border-2 border-dashed border-gray-200 rounded-lg">
                                <div class="flex flex-col items-center gap-3">
                                    <i class="pi pi-info-circle text-gray-400" style="font-size: 2rem"></i>
                                    <Tag severity="secondary" value="No hay información de tiempo extra para mostrar" class="p-2 px-4 text-sm" />
                                </div>
                            </div>
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </template>
        </Card>
    </AppLayout>
</template>
