<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { onMounted, ref, computed, nextTick } from "vue";
import axios from "axios";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { router } from "@inertiajs/vue3";
import { useToastService } from "@/Stores/toastService";
import { useLayout } from "@/Layouts/composables/layout";
import { useToast } from "primevue";
import { useAuthz } from "@/composables/useAuthz";

const { showSuccess, showError, processingTurn } = useToastService();
const { can } = useAuthz();

const toast = useToast();

const props = defineProps({
    incidences: Array,
    branchOffices: Array,
    employees: Array,
});

const branch_office_id = ref(
    JSON.parse(localStorage.getItem("selectedBranchOffice")),
);

const incidences = ref([{}]);
const selected = ref([]);
const filters = ref({});
const otherFilterDialog = ref(false);
const deleteDialog = ref(false);
const approveDialog = ref(false);
const rejectDialog = ref(false);
const incidenceToDelete = ref(null);
const incidenceToApprove = ref(null);
const multipleRejectDialog = ref(false);
const multipleApproveDialog = ref(false);
const dataReject = ref(null);
const { isDark } = useLayout();
const lastWeekNumber = ref(null);

const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        id: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        employee_id: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        full_name: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        incidence_id: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        validity_from: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        validity_to: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        approved_at: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        declined_at: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        before_date: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        rest_date: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        created_at: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        days: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        week_number: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        week_year: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        branch_office_id: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        approved_by: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        declined_by: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        deleted_by: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        file_path: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        document_number: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        schedule_id: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        comment: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        hours_txt: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        incidence_name: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        status: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
    };
};

const op = ref(null);
const opMostrarColumnas = ref(null);
const opFijarColumnas = ref(null);
const deleteMultipleDialog = ref(false);
const employeesByBranchOffice = ref(null);

function getISOWeek(date = new Date()) {
    const d = new Date(
        Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()),
    );
    const dayNum = d.getUTCDay() || 7; // domingo = 7
    d.setUTCDate(d.getUTCDate() + 4 - dayNum);
    const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
    const weekNo = Math.ceil(((d - yearStart) / 86400000 + 1) / 7);
    return {
        year: d.getUTCFullYear(),
        week: weekNo,
        iso: `${d.getUTCFullYear()}-W${String(weekNo).padStart(2, "0")}`,
    };
}

const weekNumber = ref(getISOWeek().week);
const year = ref(getISOWeek().year);
const loading = ref(false);
const columnsDialog = ref(false);

const toggleAccionesMasivas = (event) => {
    op.value.toggle(event);
};

const toggleMostrarColumnas = (event) => {
    opMostrarColumnas.value.toggle(event);
};

const toggleFijarColumnas = (event) => {
    opFijarColumnas.value.toggle(event);
};

const confirmDeleteMultiple = () => {
    deleteMultipleDialog.value = true;
};

const exportColumns = ref({
    id: true,
    num_empleado: true,
    nombre_empleado: true,
    tipo_incidencia: true,
    fecha_inicio: true,
    fecha_fin: true,
    dias: true,
    semana: true,
    año: true,
    fecha_aprobado: true,
    creado_por: true,
    fecha_creado: true,
    status: true,
    numero_documento: true,
    fecha_adelanto: true,
    fecha_descanso: true,
    observaciones: true,
    fecha_rechazado: true,
    rechazado_por: true,
});

const showColumns = ref({
    acciones: true,
    // id: true,
    num_empleado: true,
    nombre_empleado: true,
    tipo_incidencia: true,
    fecha_inicio: true,
    fecha_fin: true,
    dias: true,
    semana: false,
    año: false,
    horas_txt: false,
    aprobado_por: true,
    fecha_aprobado: true,
    fecha_creado: false,
    status: true,
    numero_documento: false,
    fecha_adelanto: false,
    fecha_descanso: false,
    observaciones: false,
    fecha_rechazado: true,
    rechazado_por: true,
});

const frozenColumns = ref({
    acciones: false,
    // id: false,
    num_empleado: false,
    nombre_empleado: false,
    tipo_incidencia: false,
    fecha_inicio: false,
    fecha_fin: false,
    dias: false,
    semana: false,
    año: false,
    aprobado_por: false,
    fecha_aprobado: false,
    fecha_creado: false,
    status: false,
    numero_documento: false,
    fecha_adelanto: false,
    fecha_descanso: false,
    observaciones: false,
    fecha_rechazado: false,
    rechazado_por: false,
});

const otherFilters = ref([
    {
        branch_office_id: branch_office_id.value?.code,
        employee_id: null,
        approved: null,
        incidenceId: null,
        includeEliminated: null,
        week: `${year.value}-W${String(weekNumber.value).padStart(2, "0")}`,
    },
]);

const branchOfficeFilter = ref(branch_office_id.value?.id);
const userBranchOffice = ref(null);
const employeeFilter = ref(null);
const weekFilter = ref(
    `${year.value}-W${String(weekNumber.value).padStart(2, "0")}`,
);
const includeEliminatedFilter = ref(null);
const incidenceFilter = ref(null);
const multipleDeleteDialog = ref(false);

const submitted = ref(false);
const startDate = ref();
const endDate = ref();

const employees = ref([]);
const dt = ref(null);

initFilters();

const clearFilter = () => {
    initFilters();
    applyFilters();
};

const applyFilters = () => {
    loading.value = true;
    otherFilterDialog.value = false;

    const selectedIncidence = props.incidences.find(
        (incidence) => incidence.id === incidenceFilter.value,
    );

    otherFilters.value[0].employee_id = employeeFilter.value;
    otherFilters.value[0].week = weekFilter.value;
    otherFilters.value[0].includeEliminated = includeEliminatedFilter.value;
    otherFilters.value[0].incidenceId = selectedIncidence?.name;

    let year = null;
    let week = null;

    if (otherFilters.value[0].week != null) {
        [year, week] = otherFilters.value[0].week.split("-W");
    }

    console.log(
        week,
        year,
        employeeFilter.value,
        includeEliminatedFilter.value,
        selectedIncidence?.id,
    );

    console.log(otherFilters.value);

    axios
        .get("/incidences/getIncidences", {
            params: {
                week: week,
                year: year,
                incidence_id: selectedIncidence?.id,
            },
        })
        .then((response) => {
            incidences.value = response.data.incidences;
            lastWeekNumber.value = response.data.lastWeekNumber[0].week;
            incidences.value = incidences.value.map((incidence) => {
                return {
                    ...incidence,
                    status: status(incidence).text,
                    status_color: status(incidence).color,
                };
            });
            loading.value = false;
        });

    console.log(otherFilters.value);
};

const removeFilter = (filter) => {
    otherFilters.value[0][filter] = null;
    switch (filter) {
        case "incidenceId":
            incidenceFilter.value = null;
            break;
        case "employeeId":
            employeeFilter.value = null;
            break;
        case "week":
            weekFilter.value = null;
            break;
        case "includeEliminated":
            includeEliminatedFilter.value = null;
            break;
    }
    applyFilters();
};

const descargar = (id) => {
    window.open(route("incidences.pdf", { id_incidence: id }), "_blank");
};

const descargarTxt = (id) => {
    window.open(route("incidences.txt", { id_incidence: id }), "_blank");
};

const status = (data) => {
    if (
        data.approved_at == null &&
        data.declined_at == null &&
        data.deleted_by == null
    ) {
        return {
            color: "primary",
            text: "Pendiente",
        };
    } else if (data.approved_at != null) {
        return {
            color: "success",
            text: "Aprobado",
        };
    } else if (data.declined_at != null) {
        return {
            color: "danger",
            text: "Rechazado",
        };
    } else if (data.deleted_by != null) {
        return {
            color: "gray",
            text: "Eliminado",
        };
    }
};

const revisarIncidencias = async (params = {}) => {
    toast.add({
        severity: "info",
        summary: "Procesando",
        detail: "Revisión de turnos en proceso...",
        group: "processing",
        life: 0,
        icon: "pi pi-spin pi-spinner",
    });

    await nextTick();

    const fecha_inicial =
        params.fecha_inicial || incidenceToApprove.value?.validity_from;
    const fecha_final =
        params.fecha_final || incidenceToApprove.value?.validity_to;

    let empleados = params.empleados;

    if (!empleados || empleados.length === 0) {
        if (selected.value && selected.value.length > 0) {
            empleados = selected.value
                .map((e) => e.employee_id)
                .filter(Boolean);
        } else if (incidenceToApprove.value?.employee_id) {
            empleados = [incidenceToApprove.value.employee_id];
        } else {
            empleados = [];
        }
    }

    if (!fecha_inicial || !fecha_final) {
        showError();
        return;
    }

    if (!empleados || empleados.length < 1) {
        console.log("❌ Error: No se pudieron determinar los empleados", {
            params_empleados: params.empleados,
            selected_length: selected.value?.length,
            incidence_employee: incidenceToApprove.value?.employee_id,
        });
        showError();
        return;
    }

    const fechas = [];
    let fInicio = new Date(fecha_inicial);
    const fFin = new Date(fecha_final);

    while (fInicio <= fFin) {
        fechas.push(fInicio.toISOString().split("T")[0]);
        fInicio.setDate(fInicio.getDate() + 1);
    }

    loading.value = true;

    let peticiones = [];

    empleados.forEach((id) => {
        fechas.forEach((fecha) => {
            let promesa = $.ajax({
                url: "https://grupo-ortiz.site/apis/Controllers/weeklyAsistenceController.php?op=revisar-turno",
                method: "POST",
                data: { id: id, validity_from: fecha },
            })
                .then(function (response) {
                    let res =
                        typeof response === "string"
                            ? JSON.parse(response)
                            : response;
                    console.log(response);
                    res.empleadoId = id;
                    res.fechaError = fecha;
                    return res;
                })
                .catch(function () {
                    return {
                        estatus: "error",
                        message: "No se encontró un rol de turno activo",
                        empleadoId: id,
                        fechaError: fecha,
                    };
                });

            peticiones.push(promesa);
        });
    });

    Promise.all(peticiones)
        .then((resultados) => {
            const errores = resultados.filter(
                (res) => res && res.estatus === "error",
            );

            toast.removeGroup("processing");

            if (errores.length > 0) {
                toast.add({
                    severity: "warn",
                    summary: "Proceso completado con advertencias",
                    detail: `${errores.length} registro(s) presentaron inconvenientes`,
                });
            }

            showSuccess();
        })
        .catch((err) => {
            showError();
            console.error("Error crítico:", err);
        });
};

const deleteIncidence = async () => {
    loading.value = true;
    router.delete(
        `/incidences-employee/${incidenceToDelete.value.id}`,

        {
            onSuccess: async () => {
                deleteDialog.value = false;
                await revisarIncidencias();
                applyFilters();
            },
        },
        {
            onError: async () => {
                loading.value = false;
                showError();
            },
        },
    );
};

const approveIncidence = async () => {
    loading.value = true;
    router.put(
        route("incidences.approve", { incidence: incidenceToApprove.value.id }),
        {},
        {
            onSuccess: async () => {
                approveDialog.value = false;
                loading.value = false;
                applyFilters();

                await revisarIncidencias();
            },
            onError: () => {
                loading.value = false;
                showError();
            },
        },
    );
};

const formatDate = (date) => {
    if (!date) return null;
    if (typeof date === "string") return date;

    const d = new Date(date);
    // Forzar hora a mediodía para evitar que el timezone cambie el día
    d.setHours(12, 0, 0, 0);

    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${y}-${m}-${day}`;
};

const filterByDate = (value, filter) => {
    console.log(value, filter);
    if (!filter) return true;
    if (!value) return false;

    const valueDate = typeof value === "string" ? value : formatDate(value);
    const filterDate = filter instanceof Date ? formatDate(filter) : filter;

    console.log(valueDate, filterDate);

    return valueDate === filterDate;
};

const disableApproveRejectButton = computed(() => {
    console.log(selected.value);
    return (
        selected.value.length === 0 ||
        selected.value.some((item) => item.approved_at || item.declined_at)
    );
});

const disableDeleteButton = computed(() => {
    console.log(selected.value);
    return (
        selected.value.length === 0 ||
        selected.value.some(
            (item) => parseInt(item.week_number) < lastWeekNumber.value,
        )
    );
});

const saveColumns = () => {
    columnsDialog.value = false;
    dt.value.exportCSV();
};

const rejectMultipleIncidences = () => {
    loading.value = true;
    const ids = selected.value.map((item) => item.id);
    router.post(
        `/employee/incidences/multiple-reject`,
        {
            ids: ids,
        },
        {
            onSuccess: async () => {
                multipleRejectDialog.value = false;
                showSuccess();
                loading.value = false;
                selected.value = [];
                applyFilters();
            },
            onError: () => {
                multipleRejectDialog.value = false;
                showError();
                loading.value = false;
            },
        },
    );
};

const approveMultipleIncidences = () => {
    loading.value = true;
    const ids = selected.value.map((item) => item.id);

    const empleados = [
        ...new Set(
            selected.value.map((item) => item.employee_id).filter(Boolean),
        ),
    ];

    const validFroms = selected.value
        .map((item) => item.validity_from)
        .filter(Boolean);
    const validTos = selected.value
        .map((item) => item.validity_to)
        .filter(Boolean);

    const fecha_inicial =
        validFroms.length > 0
            ? validFroms.reduce((min, f) => (f < min ? f : min))
            : null;

    const fecha_final =
        validTos.length > 0
            ? validTos.reduce((max, f) => (f > max ? f : max))
            : null;

    router.post(
        `/employee/incidences/multiple-approve`,
        { ids },
        {
            onSuccess: async () => {
                multipleApproveDialog.value = false;
                loading.value = false;
                selected.value = [];
                applyFilters();

                if (empleados.length > 0 && fecha_inicial && fecha_final) {
                    await revisarIncidencias({
                        fecha_inicial,
                        fecha_final,
                        empleados,
                    });
                }
            },
            onError: () => {
                multipleApproveDialog.value = false;
                showError();
                loading.value = false;
            },
        },
    );
};

const deleteMultipleIncidences = () => {
    loading.value = true;
    const ids = selected.value.map((item) => item.id);
    const empleados = [
        ...new Set(
            selected.value.map((item) => item.employee_id).filter(Boolean),
        ),
    ];

    const validFroms = selected.value
        .map((item) => item.validity_from)
        .filter(Boolean);
    const validTos = selected.value
        .map((item) => item.validity_to)
        .filter(Boolean);

    const fecha_inicial =
        validFroms.length > 0
            ? validFroms.reduce((min, f) => (f < min ? f : min))
            : null;

    const fecha_final =
        validTos.length > 0
            ? validTos.reduce((max, f) => (f > max ? f : max))
            : null;
    router.post(
        `/employee/incidences/multiple-delete`,
        {
            ids: ids,
        },
        {
            onSuccess: async () => {
                multipleDeleteDialog.value = false;
                selected.value = [];
                applyFilters();
                if (empleados.length > 0 && fecha_inicial && fecha_final) {
                    await revisarIncidencias({
                        fecha_inicial,
                        fecha_final,
                        empleados,
                    });
                }
            },
            onError: () => {
                multipleDeleteDialog.value = false;
                showError();
                loading.value = false;
            },
        },
    );
};

const fetchIncidences = async () => {
    loading.value = true;
    const [year, week] = otherFilters.value[0].week.split("-W");

    const { data } = await axios.get("/incidences/getIncidences");

    console.log(data);

    // const { data: branchOffices } = await axios.get("/branch-offices-user");

    // userBranchOffice.value = branchOffices;

    incidences.value = data.incidences;
    incidences.value = incidences.value.map((incidence) => {
        return {
            ...incidence,
            status: status(incidence).text,
            status_color: status(incidence).color,
        };
    });
    lastWeekNumber.value = data.lastWeekNumber[0].week;
    loading.value = false;
};

const rejectIncidence = () => {
    loading.value = true;
    router.put(
        `/employee/incidences/reject/${dataReject.value.id}`,
        {},
        {
            onSuccess: async () => {
                rejectDialog.value = false;
                applyFilters();
            },
            onError: () => {
                rejectDialog.value = false;
                showError();
            },
        },
    );
};

const openInNewTab = (url) => {
    if (typeof window !== "undefined") {
        window.open(url, "_blank", "noopener,noreferrer");
    } else {
        console.warn("window.open no está disponible en SSR");
    }
};

const getEmployeesByBranchOffice = async () => {
    await axios
        .get("/api/employee-branchOffices", {
            params: {
                branchOfficeId: branch_office_id.value.id,
            },
        })
        .then((res) => {
            employeesByBranchOffice.value = res.data;
        });
};

onMounted(() => {
    fetchIncidences();
});
</script>

<template>
    <AppLayout :title="'Incidencias'">
        <Toast group="processing" />
        <div class="card">
            <Toolbar>
                <template #end>
                    <Link :href="route('incidences-employee.create')">
                        <Button
                            label="Crear"
                            icon="pi pi-plus-circle"
                            severity="success"
                            class="ml-2"
                        />
                    </Link>
                </template>
            </Toolbar>
            <DataTable
                class="max-sm:px-0"
                ref="dt"
                v-model:selection="selected"
                :value="incidences"
                dataKey="id"
                :paginator="true"
                :rows="10"
                scrollable
                reorderableColumns
                scrollHeight="400px"
                :filterDelay="500"
                v-model:filters="filters"
                filterDisplay="menu"
                exportFilename="incidencias"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} incidencias"
            >
                <template #header>
                    <div
                        class="flex gap-2 items-end justify-between mb-6 overflow-auto"
                    >
                        <div>
                            <h4 class="m-0">Incidencias</h4>
                            <Button
                                icon="pi pi-filter"
                                rounded
                                v-tooltip.top="'Mostrar mas filtros'"
                                @click="otherFilterDialog = true"
                            />
                            <Button
                                type="button"
                                icon="pi pi-filter-slash"
                                rounded
                                severity="secondary"
                                class="mt-5 ml-2 mr-2"
                                v-tooltip.top="'Limpiar filtros'"
                                @click="clearFilter()"
                            />
                            <Tag
                                v-if="selected.length > 0"
                                severity="info"
                                :value="'Seleccionados: ' + selected.length"
                            ></Tag>
                        </div>
                        <div class="flex">
                            <IconField>
                                <InputIcon>
                                    <i class="pi pi-search" />
                                </InputIcon>
                                <InputText
                                    v-model="filters.global.value"
                                    placeholder="Buscar..."
                                />
                            </IconField>

                            <Button
                                type="button"
                                rounded
                                class="ml-2"
                                icon="pi pi-sliders-v"
                                severity="secondary"
                                @click="toggleMostrarColumnas($event)"
                            />
                            <Button
                                icon="pi pi-lock"
                                rounded
                                v-tooltip.top="'Alternar columnas fijas'"
                                class="ml-2"
                                severity="secondary"
                                @click="toggleFijarColumnas($event)"
                            />

                            <Popover ref="opMostrarColumnas">
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
                                                ) in showColumns"
                                                :key="key"
                                                class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                                                :binary="true"
                                            >
                                                <Checkbox
                                                    v-model="showColumns[key]"
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
                            <Popover ref="opFijarColumnas">
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
                                                ) in frozenColumns"
                                                :key="key"
                                                class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                                                :binary="true"
                                            >
                                                <Checkbox
                                                    v-model="frozenColumns[key]"
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
                    <div class="flex gap-1">
                        <!-- <div class="mb-2">
                            <Chip
                                :label="'Semana: ' + otherFilters[0].week"
                                v-if="
                                    otherFilters[0].week != null ||
                                    otherFilters[0].week != undefined ||
                                    otherFilters[0].week != ''
                                "
                                removable
                                @remove="removeFilter('week')"
                                :removable="!loading"
                            />
                        </div> -->
                        <div class="mb-2">
                            <Chip
                                :label="`Incidencia: ${otherFilters[0].incidenceId}`"
                                v-if="otherFilters[0].incidenceId != null"
                                removable
                                @remove="removeFilter('incidenceId')"
                                :removable="!loading"
                            />
                        </div>
                    </div>
                </template>
                <template #empty>
                    <div class="flex items-center justify-center">
                        <span>No se encontraron incidencias</span>
                    </div>
                </template>

                <Column
                    selectionMode="multiple"
                    style="width: 1rem"
                    :exportable="false"
                    columnKey="selection"
                ></Column>
                <Column
                    :exportable="false"
                    columnKey="acciones"
                    :style="{
                        width: '8rem',
                        display: showColumns.acciones ? '' : 'none',
                        minWidth: '8rem',
                    }"
                    header="Acciones"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <div v-else>
                            <Button
                                icon="pi pi-pencil"
                                class="mr-2"
                                rounded
                                v-tooltip.top="'Editar'"
                                severity="warn"
                                @click="
                                    () => {
                                        router.get(
                                            `/incidences-employee/${data.id}/edit`,
                                        );
                                    }
                                "
                                v-if="
                                    data.approved_at == null &&
                                    data.declined_at == null &&
                                    data.deleted_by == null
                                "
                            />
                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                v-tooltip.top="'Eliminar'"
                                class="mr-2"
                                rounded
                                @click="
                                    () => {
                                        incidenceToDelete = data;
                                        incidenceToApprove = data;
                                        deleteDialog = true;
                                    }
                                "
                                v-if="
                                    data.deleted_by == null &&
                                    data.approved_at == null &&
                                    data.declined_at == null
                                "
                                :disabled="
                                    parseInt(data.week_number) < lastWeekNumber
                                "
                            />

                            <Button
                                icon="pi pi-eye"
                                severity="help"
                                v-tooltip.top="'Ver'"
                                class="mr-2"
                                rounded
                                @click="
                                    openInNewTab(
                                        `/incidences-employee/${data.id}`,
                                    )
                                "
                            />
                            <Button
                                icon="pi pi-file-pdf"
                                severity="contrast"
                                v-tooltip.top="'Reporte'"
                                class="mr-2"
                                rounded
                                v-if="data.incidence_name == 'VACACIONES'"
                                @click="descargar(data.id)"
                            />
                            <Button
                                icon="pi pi-file-pdf"
                                severity="contrast"
                                v-tooltip.top="'Reporte TXT'"
                                class="mr-2"
                                rounded
                                v-if="
                                    data.incidence_name == 'TIEMPO POR TIEMPO'
                                "
                                @click="descargarTxt(data.id)"
                            />
                        </div>
                    </template>
                </Column>
                <!-- <Column
                    field="id"
                    header="ID"
                    :filter="true"
                    columnKey="id"
                    :frozen="frozenColumns.id"
                    :style="{
                        width: '5rem',
                        display: showColumns.id ? '' : 'none',
                    }"
                    :exportable="exportColumns.id"
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
                -->
                <Column
                    field="employee_id"
                    header="Clave Empleado"
                    :filter="true"
                    columnKey="employee_id"
                    :frozen="frozenColumns.num_empleado"
                    :style="{
                        width: '2rem',
                        display: showColumns.num_empleado ? '' : 'none',
                        minWidth: '2rem',
                    }"
                    :exportable="exportColumns.num_empleado"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.employee_id }}</span>
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
                    field="full_name"
                    header="Nombre Empleado"
                    :filter="true"
                    columnKey="full_name"
                    :frozen="frozenColumns.nombre_empleado"
                    :style="{
                        width: '5rem',
                        display: showColumns.nombre_empleado ? '' : 'none',
                    }"
                    :exportable="exportColumns.nombre_empleado"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.full_name }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Nombre Empleado"
                        />
                    </template>
                </Column>
                <Column
                    field="status"
                    header="Estatus"
                    :filter="true"
                    columnKey="status"
                    :frozen="frozenColumns.status"
                    :style="{
                        width: '5rem',
                        display: showColumns.status ? '' : 'none',
                    }"
                    :exportable="exportColumns.status"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <Tag
                            v-else
                            :value="data.status"
                            :severity="data.status_color"
                        />
                    </template>
                    <template #filter="{ filterModel }">
                        <Select
                            v-model="filterModel.value"
                            :options="[
                                { label: 'Pendiente', value: 'Pendiente' },
                                { label: 'Aprobado', value: 'Aprobado' },
                                { label: 'Rechazado', value: 'Rechazado' },
                            ]"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Buscar por Estatus"
                        />
                    </template>
                </Column>
                <Column
                    field="incidence_name"
                    header="Incidencia"
                    :filter="true"
                    columnKey="incidence_name"
                    :frozen="frozenColumns.tipo_incidencia"
                    :style="{
                        width: '5rem',
                        display: showColumns.tipo_incidencia ? '' : 'none',
                    }"
                    :exportable="exportColumns.tipo_incidencia"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.incidence_name }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Incidencia"
                        />
                    </template>
                </Column>

                <Column
                    field="validity_from"
                    header="Fecha de inicio"
                    :filter="true"
                    filterMatchMode="equals"
                    columnKey="validity_from"
                    :frozen="frozenColumns.fecha_inicio"
                    :style="{
                        width: '5rem',
                        display: showColumns.fecha_inicio ? '' : 'none',
                    }"
                    :exportable="exportColumns.fecha_inicio"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.validity_from }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Fecha de Inicio"
                        />
                    </template>
                </Column>
                <Column
                    field="validity_to"
                    header="Fecha de fin"
                    :filter="true"
                    columnKey="validity_to"
                    :frozen="frozenColumns.fecha_fin"
                    :style="{
                        width: '5rem',
                        display: showColumns.fecha_fin ? '' : 'none',
                    }"
                    :exportable="exportColumns.fecha_fin"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.validity_to }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Fecha de Fin"
                        />
                    </template>
                </Column>
                <Column
                    field="days"
                    header="Días"
                    :filter="true"
                    columnKey="days"
                    :frozen="frozenColumns.dias"
                    :style="{
                        width: '5rem',
                        display: showColumns.dias ? '' : 'none',
                    }"
                    :exportable="exportColumns.dias"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.days }}</span>
                    </template>
                </Column>
                <Column
                    field="approved_at"
                    header="Fecha de aprobación"
                    :filter="true"
                    columnKey="approved_at"
                    :frozen="frozenColumns.fecha_aprobado"
                    :style="{
                        width: '5rem',
                        display: showColumns.fecha_aprobado ? '' : 'none',
                    }"
                    :exportable="exportColumns.fecha_aprobado"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ formatDate(data.approved_at) }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Fecha de aprobación"
                        />
                    </template>
                </Column>
                <Column
                    field="approved_by"
                    header="Aprobado por"
                    :filter="true"
                    columnKey="approved_by"
                    :frozen="frozenColumns.aprobado_por"
                    :style="{
                        width: '5rem',
                        display: showColumns.aprobado_por ? '' : 'none',
                    }"
                    :exportable="exportColumns.aprobado_por"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.approved_by }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Aprobado por"
                        />
                    </template>
                </Column>
                <Column
                    field="declined_at"
                    header="Fecha de rechazo"
                    :filter="true"
                    columnKey="declined_at"
                    :frozen="frozenColumns.fecha_rechazado"
                    :style="{
                        width: '5rem',
                        display: showColumns.fecha_rechazado ? '' : 'none',
                    }"
                    :exportable="exportColumns.fecha_rechazado"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ formatDate(data.declined_at) }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Fecha de rechazo"
                        />
                    </template>
                </Column>
                <Column
                    field="declined_by"
                    header="Rechazado por"
                    :filter="true"
                    columnKey="declined_by"
                    :frozen="frozenColumns.rechazado_por"
                    :style="{
                        width: '5rem',
                        display: showColumns.rechazado_por ? '' : 'none',
                    }"
                    :exportable="exportColumns.rechazado_por"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.declined_by }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Rechazado por"
                        />
                    </template>
                </Column>

                <Column
                    field="document_number"
                    header="Número de documento"
                    :filter="true"
                    columnKey="document_number"
                    :frozen="frozenColumns.numero_documento"
                    :style="{
                        width: '5rem',
                        display: showColumns.numero_documento ? '' : 'none',
                    }"
                    :exportable="exportColumns.numero_documento"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.document_number }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Número de documento"
                        />
                    </template>
                </Column>
                <Column
                    field="before_date"
                    header="Fecha de adelanto"
                    :filter="true"
                    columnKey="before_date"
                    :frozen="frozenColumns.fecha_adelanto"
                    :style="{
                        width: '5rem',
                        display: showColumns.fecha_adelanto ? '' : 'none',
                    }"
                    :exportable="exportColumns.fecha_adelanto"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.before_date }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Fecha de adelanto"
                        />
                    </template>
                </Column>
                <Column
                    field="rest_date"
                    header="Fecha de descanso"
                    :filter="true"
                    columnKey="rest_date"
                    :frozen="frozenColumns.fecha_descanso"
                    :style="{
                        width: '5rem',
                        display: showColumns.fecha_descanso ? '' : 'none',
                    }"
                    :exportable="exportColumns.fecha_descanso"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.rest_date }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Fecha de descanso"
                        />
                    </template>
                </Column>
                <Column
                    field="created_at"
                    header="Fecha de creación"
                    :filter="true"
                    columnKey="created_at"
                    :frozen="frozenColumns.fecha_creado"
                    :style="{
                        width: '5rem',
                        display: showColumns.fecha_creado ? '' : 'none',
                    }"
                    :exportable="exportColumns.fecha_creado"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.created_at }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Fecha de creación"
                        />
                    </template>
                </Column>
                <Column
                    field="hours_txt"
                    header="Horas TXT"
                    :filter="true"
                    columnKey="hours_txt"
                    :frozen="frozenColumns.horas_txt"
                    :style="{
                        width: '5rem',
                        display: showColumns.horas_txt ? '' : 'none',
                    }"
                    :exportable="exportColumns.horas_txt"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.hours_txt }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Horas TXT"
                        />
                    </template>
                </Column>
                <Column
                    field="week_year"
                    header="Año"
                    :filter="true"
                    columnKey="week_year"
                    :frozen="frozenColumns.año"
                    :style="{
                        width: '5rem',
                        display: showColumns.año ? '' : 'none',
                    }"
                    :exportable="exportColumns.año"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.week_year }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Año"
                        />
                    </template>
                </Column>
                <Column
                    field="week_number"
                    header="Semana"
                    :filter="true"
                    columnKey="week_number"
                    :frozen="frozenColumns.semana"
                    :style="{
                        width: '5rem',
                        display: showColumns.semana ? '' : 'none',
                    }"
                    :exportable="exportColumns.semana"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.week_number }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Semana"
                        />
                    </template>
                </Column>
                <Column
                    field="deleted_by"
                    header="Eliminado por"
                    :filter="true"
                    columnKey="comment"
                    :frozen="frozenColumns.eliminado_por"
                    :style="{
                        width: '5rem',
                        display: showColumns.eliminado_por ? '' : 'none',
                    }"
                    :exportable="exportColumns.eliminado_por"
                    sortable
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.deleted_by }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Eliminado por"
                        />
                    </template>
                </Column>
                <Column
                    field="comment"
                    header="Observaciones"
                    :filter="true"
                    columnKey="comment"
                    :frozen="frozenColumns.observaciones"
                    :style="{
                        width: '5rem',
                        display: showColumns.observaciones ? '' : 'none',
                    }"
                    :exportable="exportColumns.observaciones"
                    sortable
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.comment }}</span>
                    </template>
                </Column>
            </DataTable>
            <Dialog
                v-model:visible="otherFilterDialog"
                :style="{ width: '450px' }"
                header="Seleccionar filtros adicionales"
                :modal="true"
            >
                <div class="flex flex-wrap -mx-2">
                    <!-- Planta -->
                    <!-- <div class="w-full md:w-1/2 px-2 mb-4">
                        <label class="block mb-2 font-medium">Planta</label>
                        <Select
                            v-model="branchOfficeFilter"
                            :options="userBranchOffice"
                            optionLabel="code"
                            optionValue="id"
                            placeholder="Selecciona una planta"
                            class="w-full"
                            filter
                            filterBy="code"
                            @change="getEmployeesByBranchOffice()"
                        />
                    </div> -->

                    <!-- Departamento -->
                    <div class="w-full md:w-full px-2 mb-4">
                        <label class="block mb-2 font-medium"
                            >Incidencias</label
                        >
                        <Select
                            v-model="incidenceFilter"
                            :options="props.incidences"
                            optionLabel="name"
                            optionValue="id"
                            filter
                            filterBy="name"
                            showClear
                            placeholder="Selecciona una incidencia"
                            class="w-full"
                        />
                    </div>

                    <!-- Semana (type=week => AAAA-WSS) -->
                    <!-- <div class="w-full md:w-1/2 px-2 mb-4">
                        <label class="block mb-2 font-medium">Semana</label>
                        <InputText
                            v-model="weekFilter"
                            type="week"
                            class="w-full"
                        />
                    </div> -->

                    <!-- Empleado (buscable)-->
                    <!-- <div class="w-full md:w-1/2 px-2 mb-4">
                        <label class="block mb-2 font-medium">Empleado</label>
                        <Select
                            v-model="employeeFilter"
                            :options="employeesByBranchOffice"
                            optionValue="id"
                            optionLabel="full_name"
                            filter
                            :filterFields="['id', 'full_name']"
                            showClear
                            placeholder="Selecciona un empleado"
                            class="w-full"
                        >
                            <template #option="{ option }">
                                ({{ option.id }}) {{ option.full_name }}
                            </template>
                        </Select>
                    </div> -->
                </div>

                <template #footer>
                    <Button
                        label="Cancelar"
                        icon="pi pi-times"
                        severity="danger"
                        @click="otherFilterDialog = false"
                    />
                    <Button
                        label="Filtrar"
                        icon="pi pi-filter"
                        severity="warn"
                        @click="applyFilters"
                        :loading="submitted"
                    />
                </template>
            </Dialog>
            <Dialog
                v-model:visible="deleteDialog"
                :style="{ width: '600px' }"
                header="Confirmar eliminación"
                :modal="true"
            >
                <div
                    :class="{
                        'bg-red-50 border-l-4 border-red-500 p-4 rounded':
                            !isDark,
                        'bg-red-950 border-l-4 border-red-500 p-4 rounded':
                            isDark,
                    }"
                >
                    <div class="flex items-center">
                        <i
                            class="pi pi-exclamation-triangle text-red-600 text-3xl mr-3"
                        ></i>
                        <div>
                            <h3
                                :class="{
                                    'font-bold text-red-800': !isDark,
                                    'font-bold text-red-200': isDark,
                                }"
                            >
                                ¿Estas seguro de eliminar esta incidencia?
                            </h3>
                            <p
                                :class="{
                                    'text-sm text-red-700': !isDark,
                                    'text-sm text-red-300': isDark,
                                }"
                            >
                                Esta acción eliminará la incidencia y no podrá
                                ser deshecha.
                            </p>
                        </div>
                    </div>
                </div>
                <template #footer>
                    <Button
                        label="No"
                        icon="pi pi-times"
                        text
                        @click="deleteDialog = false"
                        severity="secondary"
                        variant="text"
                        :loading="loading"
                    />
                    <Button
                        label="Si"
                        icon="pi pi-check"
                        @click="deleteIncidence"
                        severity="danger"
                        :loading="loading"
                    />
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>
