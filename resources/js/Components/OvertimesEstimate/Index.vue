<script setup>
import { computed, ref } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { router } from "@inertiajs/vue3";
import ConfirmationDialog from "../ConfirmationDialog.vue";
import * as xlsx from "xlsx";
import { useToast } from "primevue/usetoast";
import { useToastService } from "@/Stores/toastService";
import { useAuthz } from "@/composables/useAuthz";

const { can } = useAuthz();

const props = defineProps({
    branchOffices: Array,
    positions: Array,
});

const { showSuccess, showError } = useToastService();

const toast = useToast();

const columnsDialog = ref(false);
const selected = ref([]);
const dt = ref();
const op = ref();
const opMostrarColumnas = ref();
const opFijarColumnas = ref();

const multipleDeleteDialog = ref(false);
const multipleApproveDialog = ref(false);
const multipleRejectDialog = ref(false);

const approveDialog = ref(false);
const rejectDialog = ref(false);

const exportColumns = ref({
    id: true,
    puesto: true,
    status: true,
    num_empleados: true,
    horas_extra: true,
    horas_extra_dobles: true,
    horas_extra_triples: true,
    turno: true,
    turno_corrido: true,
    costo_max_hora: true,
    importe_pagar: true,
    motivo: true,
    semana: true,
    aprobado_por: false,
    fecha_aprobacion: false,
    rechazado_por: false,
    fecha_rechazo: false,
    comentarios: false,
});
const showColumns = ref({
    acciones: true,
    id: true,
    puesto: true,
    status: true,
    num_empleados: true,
    horas_extra: true,
    horas_extra_dobles: true,
    horas_extra_triples: true,
    total_horas: true,
    turno: true,
    turno_corrido: true,
    costo_max_hora: true,
    importe_pagar: true,
    motivo: true,
    semana: false,
    aprobado_por: false,
    fecha_aprobacion: false,
    rechazado_por: false,
    fecha_rechazo: false,
    comentarios: false,
});
const frozenColumns = ref({
    acciones: true,
    id: true,
    puesto: false,
    status: false,
    num_empleados: false,
    horas_extra: false,
    horas_extra_dobles: false,
    horas_extra_triples: false,
    total_horas: false,
    turno: false,
    turno_corrido: false,
    costo_max_hora: false,
    importe_pagar: false,
    motivo: true,
    semana: false,
    aprobado_por: false,
    fecha_aprobacion: false,
    rechazado_por: false,
    fecha_rechazo: false,
    comentarios: false,
});

const toggleAccionesMasivas = (event) => {
    op.value.toggle(event);
};

const toggleMostrarColumnas = (event) => {
    opMostrarColumnas.value.toggle(event);
};

const toggleFijarColumnas = (event) => {
    opFijarColumnas.value.toggle(event);
};

const getSafeBranchId = () => {
    try {
        const item = localStorage.getItem("selectedBranchOffice");
        if (!item) return null;
        const parsed = JSON.parse(item);
        return parsed || null;
    } catch (e) {
        console.warn("Error leyendo localStorage:", e);
        return null;
    }
};

const selectedBranchOffice = ref(getSafeBranchId());

const overtimesEstimate = ref([{}]);
const loading = ref(true);
const filters = ref();
const branch_office_id = ref(selectedBranchOffice.value.id);
const branchOfficeFilter = ref(branch_office_id.value);
const otherFilterDialog = ref(false);
const positionFilter = ref(null);
const weekFilter = ref(getISOWeek().iso);
const statusFilter = ref(null);
const deleteDialog = ref(false);
const deleteId = ref(null);
const columnsPdfDialog = ref(false);
const approveId = ref(null);
const rejectId = ref(null);

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
const transformDate = (date) => {
    if (!date) return null;
    const newDate = new Date(date);
    const year = newDate.getUTCFullYear();
    const month = newDate.getUTCMonth() + 1;
    const day = newDate.getUTCDate();

    return `${year}-${month < 10 ? "0" + month : month}-${day < 10 ? "0" + day : day}`;
};

const getStatusLabel = (approved_at, declined_at) => {
    if (approved_at) {
        return "Aprobado";
    }
    if (declined_at) {
        return "Rechazado";
    }
    return "Pendiente";
};

const getStatusSeverity = (approved_at, declined_at) => {
    if (approved_at) {
        return "success";
    }
    if (declined_at) {
        return "danger";
    }
    return "warning";
};

const year = ref(getISOWeek().year);
const weekNumber = ref(getISOWeek().week);

const otherFilters = ref([
    {
        branch_office_id: selectedBranchOffice.value.code,
        employee_id: null,
        department: null,
        includeEliminated: null,
        week: `${year.value}-W${String(weekNumber.value).padStart(2, "0")}`,
        status: null,
    },
]);

const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        id: {
            operator: FilterOperator.AND,
            constraints: [
                { value: null, matchMode: FilterMatchMode.STARTS_WITH },
            ],
        },
        position: {
            operator: FilterOperator.AND,
            constraints: [
                { value: null, matchMode: FilterMatchMode.STARTS_WITH },
            ],
        },
        status: {
            operator: FilterOperator.AND,
            constraints: [
                { value: null, matchMode: FilterMatchMode.STARTS_WITH },
            ],
        },
        nombremotivo: {
            operator: FilterOperator.AND,
            constraints: [
                { value: null, matchMode: FilterMatchMode.STARTS_WITH },
            ],
        },
        week: {
            operator: FilterOperator.AND,
            constraints: [
                { value: null, matchMode: FilterMatchMode.STARTS_WITH },
            ],
        },
        aprobado_por: {
            operator: FilterOperator.AND,
            constraints: [
                { value: null, matchMode: FilterMatchMode.STARTS_WITH },
            ],
        },
        approved_at: {
            operator: FilterOperator.AND,
            constraints: [
                { value: null, matchMode: FilterMatchMode.STARTS_WITH },
            ],
        },
        declined_by: {
            operator: FilterOperator.AND,
            constraints: [
                { value: null, matchMode: FilterMatchMode.STARTS_WITH },
            ],
        },
        declined_at: {
            operator: FilterOperator.AND,
            constraints: [
                { value: null, matchMode: FilterMatchMode.STARTS_WITH },
            ],
        },
    };
};

const calculateImportePagar = (
    daily_salary,
    double_overtime,
    triple_overtime,
) => {
    const costo_max = costMaxHora(daily_salary);
    const $horas_extras_dobles = double_overtime * costo_max * 2;
    const $horas_extras_triples = triple_overtime * costo_max * 3;
    return Math.round($horas_extras_dobles + $horas_extras_triples * 100) / 100;
};

const costMaxHora = (daily_salary) => {
    return Math.round((daily_salary / 8) * 100) / 100;
};

initFilters();

const loadOvertimesEstimate = async () => {
    loading.value = true;
    try {
        const response = await axios.get("/api/employee-overtime-estimates", {
            params: {
                branch_office_id: branchOfficeFilter.value,
                position: null,
                week: getISOWeek().iso,
                status: null,
            },
        });
        console.log(response.data);
        overtimesEstimate.value = response.data;
        overtimesEstimate.value = overtimesEstimate.value.map((item) => {
            return {
                ...item,
                status: getStatusLabel(item.approved_at, item.declined_at),
                amount_to_pay: calculateImportePagar(
                    item.daily_salary,
                    item.double_overtime,
                    item.triple_overtime,
                ),
                cost_max_per_hour: costMaxHora(item.daily_salary),
            };
        });
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
};

const removeFilter = (filter) => {
    otherFilters.value[0][filter] = null;
    switch (filter) {
        case "branch_office_id":
            branchOfficeFilter.value = null;
            break;
        case "position":
            positionFilter.value = null;
            break;
        case "week":
            weekFilter.value = null;
            break;
        case "status":
            statusFilter.value = null;
            break;
    }
    applyFilters();
};

const applyFilters = async () => {
    loading.value = true;
    otherFilterDialog.value = false;
    console.log(branchOfficeFilter.value);
    console.log(positionFilter.value);
    console.log(weekFilter.value);
    console.log(statusFilter.value);
    const selectedBranchOffice = props.branchOffices.find(
        (branchOffice) => branchOffice.id === branchOfficeFilter.value,
    );

    console.log(selectedBranchOffice);

    const selectedPosition = props.positions.find(
        (position) => position.id === positionFilter.value,
    );

    otherFilters.value[0].branch_office_id = selectedBranchOffice.code;
    otherFilters.value[0].position = selectedPosition?.name;
    otherFilters.value[0].week = weekFilter.value;
    otherFilters.value[0].status = statusFilter.value;

    try {
        const response = await axios.get("/api/employee-overtime-estimates", {
            params: {
                branch_office_id: branchOfficeFilter.value,
                position: positionFilter.value,
                week: weekFilter.value,
                status: statusFilter.value,
            },
        });
        console.log(response.data);
        overtimesEstimate.value = response.data;
        overtimesEstimate.value = overtimesEstimate.value.map((item) => {
            return {
                ...item,
                status: getStatusLabel(item.approved_at, item.declined_at),
                amount_to_pay: calculateImportePagar(
                    item.daily_salary,
                    item.double_overtime,
                    item.triple_overtime,
                ),
                cost_max_per_hour: costMaxHora(item.daily_salary),
            };
        });
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
};

const edit = (id) => {
    router.get(`/employee/employee-overtimes/estimates/${id}/edit`);
};

const deleteEmployeeOvertimeEstimate = () => {
    loading.value = true;
    router.delete(`/employee/employee-overtimes/estimates/${deleteId.value}`, {
        onSuccess: () => {
            showSuccess();
            deleteDialog.value = false;
            applyFilters();
        },
        onError: () => {
            showError();
            deleteDialog.value = false;
        },
    });
};

const columnMap = {
    id: { field: "id", header: "ID" },
    puesto: { field: "posicion", header: "Puesto" },
    status: { field: "status", header: "Estado" },
    num_empleados: { field: "number_employees", header: "N° Empleados" },
    horas_extra: { field: "overtime", header: "Horas Extra" },
    horas_extra_dobles: {
        field: "double_overtime",
        header: "Horas Extra Dobles",
    },
    horas_extra_triples: {
        field: "triple_overtime",
        header: "Horas Extra Triples",
    },
    turno: { field: "turno", header: "Turno" },
    turno_corrido: { field: "current_turn", header: "Turno Corrido" },
    costo_max_hora: {
        field: "cost_max_per_hour",
        header: "Costo Max Por Hora",
    },
    importe_pagar: { field: "amount_to_pay", header: "Importe a Pagar" },
    motivo: { field: "nombremotivo", header: "Motivo" },
    semana: { field: "week", header: "Semana" },
    aprobado_por: { field: "aprobado_por", header: "Aprobado por" }, // o 'approved_by'
    fecha_aprobacion: { field: "approved_at", header: "Fecha de Aprobación" },
    rechazado_por: { field: "declinado_por", header: "Rechazado por" },
    fecha_rechazo: { field: "declined_at", header: "Fecha de Rechazo" },
    comentarios: { field: "coment", header: "Comentarios" }, // Asegúrate del nombre real
};

const saveColumns = () => {
    const selectedKeys = Object.keys(exportColumns.value).filter(
        (key) => exportColumns.value[key] === true,
    );

    if (selectedKeys.length === 0) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Por favor selecciona al menos una columna para exportar.",
            life: 3000,
        });
        return;
    }

    const sourceData =
        selected.value && selected.value.length > 0
            ? selected.value
            : overtimesEstimate.value;

    if (!sourceData || sourceData.length === 0) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No hay datos para exportar.",
            life: 3000,
        });
        return;
    }

    const dataToExport = sourceData.map((row) => {
        const rowToExport = {};

        selectedKeys.forEach((key) => {
            const colConfig = columnMap[key];

            if (colConfig) {
                let value = row[colConfig.field];

                if (key === "fecha_aprobacion" || key === "fecha_rechazo") {
                    value = value ? transformDate(value) : "";
                }

                rowToExport[colConfig.header] =
                    value !== undefined && value !== null ? value : "";
            }
        });

        return rowToExport;
    });

    const worksheet = xlsx.utils.json_to_sheet(dataToExport);
    const workbook = xlsx.utils.book_new();
    xlsx.utils.book_append_sheet(workbook, worksheet, "Estimaciones");
    xlsx.writeFile(workbook, "Estimacion_Horas_Extras.xlsx");

    columnsDialog.value = false;
};

const exportToPDF = async () => {
    loading.value = true;
    const selectedKeys = Object.keys(exportColumns.value).filter(
        (key) => exportColumns.value[key] === true,
    );

    if (selectedKeys.length === 0) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Por favor selecciona al menos una columna para exportar.",
            life: 3000,
        });
        return;
    }

    const sourceData =
        selected.value && selected.value.length > 0
            ? selected.value
            : overtimesEstimate.value;

    if (!sourceData || sourceData.length === 0) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No hay datos para exportar.",
            life: 3000,
        });
        return;
    }

    const headers = selectedKeys.map((key) => columnMap[key].header);

    const rows = sourceData.map((row) => {
        return selectedKeys.map((key) => {
            const colConfig = columnMap[key];
            let value = row[colConfig.field];

            if (key === "fecha_aprobacion" || key === "fecha_rechazo") {
                value = value ? transformDate(value) : "";
            }

            return value !== undefined && value !== null ? value : "";
        });
    });

    try {
        const response = await axios.post(
            "/employee/employee-overtime-estimates/generate-pdf",
            {
                headers: headers,
                rows: rows,
                name_branch_office: selectedBranchOffice.value.name,
            },
            {
                responseType: "blob",
            },
        );

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement("a");
        link.href = url;
        const fileName =
            selected.value?.length > 0
                ? "Estimaciones_Seleccionadas.pdf"
                : "Estimaciones_Completas.pdf";
        link.setAttribute("download", fileName);
        document.body.appendChild(link);
        link.click();
        link.remove();

        columnsDialog.value = false;
        loading.value = false;
    } catch (error) {
        console.error("Error al generar el PDF:", error);
        alert("Hubo un problema al generar el PDF.");
    }
};

const disableApproveRejectButton = computed(() => {
    console.log(selected.value);
    return (
        selected.value.length === 0 ||
        selected.value.some((item) => item.approved_at || item.declined_at)
    );
});

const approveEmployeeOvertimeEstimate = () => {
    loading.value = true;
    router.post(
        "/employee/employee-overtime-estimates/approve",
        {
            id: approveId.value,
        },
        {
            onSuccess: () => {
                showSuccess();
                applyFilters();
                approveDialog.value = false;
            },
            onError: () => {
                showError();
                approveDialog.value = false;
            },
        },
    );
};

const rejectEmployeeOvertimeEstimate = () => {
    loading.value = true;
    router.post(
        "/employee/employee-overtime-estimates/decline",
        {
            id: rejectId.value,
        },
        {
            onSuccess: () => {
                showSuccess();
                applyFilters();
                rejectDialog.value = false;
            },
            onError: () => {
                showError();
                rejectDialog.value = false;
            },
        },
    );
};

const multiApproveEmployeeOvertimeEstimate = () => {
    loading.value = true;
    router.post(
        "/employee/employee-overtime-estimates/multi-approve",
        {
            ids: selected.value.map((item) => item.id),
        },
        {
            onSuccess: () => {
                showSuccess();
                multipleApproveDialog.value = false;
                applyFilters();
                op.value.hide();
            },
            onError: () => {
                showError();
                multipleApproveDialog.value = false;
                op.value.hide();
            },
        },
    );
};

const multiDeclineEmployeeOvertimeEstimate = () => {
    loading.value = true;
    router.post(
        "/employee/employee-overtime-estimates/multi-decline",
        {
            ids: selected.value.map((item) => item.id),
        },
        {
            onSuccess: () => {
                showSuccess();
                multipleRejectDialog.value = false;
                applyFilters();
                op.value.hide();
            },
            onError: () => {
                showError();
                multipleRejectDialog.value = false;
                op.value.hide();
            },
        },
    );
};

const multiDeleteEmployeeOvertimeEstimate = () => {
    loading.value = true;
    router.post(
        "/employee/employee-overtime-estimates/multi-delete",
        {
            ids: selected.value.map((item) => item.id),
        },
        {
            onSuccess: () => {
                showSuccess();
                multipleDeleteDialog.value = false;
                applyFilters();
                op.value.hide();
            },
            onError: () => {
                showError();
                multipleDeleteDialog.value = false;
                op.value.hide();
            },
        },
    );
};

const clearFilter = () => {
    initFilters();
};

loadOvertimesEstimate();
</script>

<template>
    <Toolbar>
        <template #start>
            <Button
                type="button"
                icon="pi pi-file-excel"
                label="Exportar XLSX"
                severity="secondary"
                class="mt-2 ml-2"
                @click="columnsDialog = true"
                v-if="can('estimates.export')"
            />
            <Button
                type="button"
                icon="pi pi-file-pdf"
                label="Exportar PDF"
                severity="contrast"
                class="mt-2 ml-2"
                @click="columnsPdfDialog = true"
                v-if="can('estimates.export')"
            />
        </template>
        <template #end>
            <Button
                type="button"
                label="Acciones Masivas"
                class="min-w-48"
                icon="pi pi-wrench"
                @click="toggleAccionesMasivas"
                :disabled="selected.length === 0"
                v-if="
                    can('estimates.multiple-delete') ||
                    can('estimates.multiple-approve') ||
                    can('estimates.multiple-reject')
                "
            />
            <Popover ref="op">
                <div class="flex flex-col gap-4">
                    <div>
                        <span class="font-medium block mb-2"
                            >Acciones Masivas</span
                        >
                        <ul class="list-none p-0 m-0 flex flex-col">
                            <li
                                class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                            >
                                <Button
                                    type="button"
                                    icon="pi pi-check-square"
                                    label="Aprobar seleccionados"
                                    severity="success"
                                    text
                                    class="mt-2 ml-2"
                                    @click="multipleApproveDialog = true"
                                    :disabled="disableApproveRejectButton"
                                    v-if="can('estimates.multiple-approve')"
                                />
                            </li>
                            <li
                                class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                            >
                                <Button
                                    type="button"
                                    icon="pi pi-times-circle"
                                    label="Rechazar seleccionados"
                                    severity="warn"
                                    text
                                    class="mt-2 ml-2"
                                    @click="multipleRejectDialog = true"
                                    :disabled="disableApproveRejectButton"
                                    v-if="can('estimates.multiple-reject')"
                                />
                            </li>
                            <li
                                class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                            >
                                <Button
                                    type="button"
                                    icon="pi pi-trash"
                                    label="Eliminar seleccionados"
                                    severity="danger"
                                    text
                                    class="mt-2 ml-2"
                                    @click="multipleDeleteDialog = true"
                                    :disabled="disableApproveRejectButton"
                                    v-if="can('estimates.multiple-delete')"
                                />
                            </li>
                        </ul>
                    </div>
                </div>
            </Popover>
            <Link :href="route('estimates.create')">
                <Button
                    label="Crear"
                    icon="pi pi-plus-circle"
                    severity="success"
                    class="ml-2"
                    v-if="can('estimates.create')"
                />
            </Link>
        </template>
    </Toolbar>
    <DataTable
        ref="dt"
        v-model:selection="selected"
        :value="overtimesEstimate"
        dataKey="id"
        :paginator="true"
        :rows="10"
        scrollable
        reorderableColumns
        scrollHeight="400px"
        :filterDelay="500"
        v-model:filters="filters"
        filterDisplay="menu"
        exportFilename="estimacion_horas_extras"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} estimaciones"
    >
        <template #header>
            <div class="flex flex-wrap gap-2 items-end justify-between mb-6">
                <div>
                    <h4 class="m-0">Estimación de horas extras</h4>
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
                                <ul class="list-none p-0 m-0 flex flex-col">
                                    <li
                                        v-for="(value, key) in showColumns"
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
                                                key.charAt(0).toUpperCase() +
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
                                <ul class="list-none p-0 m-0 flex flex-col">
                                    <li
                                        v-for="(value, key) in frozenColumns"
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
                                                key.charAt(0).toUpperCase() +
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
                <div class="mb-2">
                    <Chip
                        :label="'Planta: ' + otherFilters[0].branch_office_id"
                        v-if="otherFilters[0].branch_office_id != null"
                    />
                </div>
                <div class="mb-2">
                    <Chip
                        :label="'Empleado: ' + otherFilters[0].employee_id"
                        v-if="otherFilters[0].employee_id != null"
                        removable
                        @remove="removeFilter('employee_id')"
                        :removable="!loading"
                    />
                </div>
                <div class="mb-2">
                    <Chip
                        :label="'Semana: ' + otherFilters[0].week"
                        v-if="otherFilters[0].week != null"
                        removable
                        @remove="removeFilter('week')"
                        :removable="!loading"
                    />
                </div>
                <div class="mb-2">
                    <Chip
                        :label="`Incluir eliminados: ${
                            otherFilters[0].includeEliminated ? 'Si' : 'No'
                        }`"
                        v-if="otherFilters[0].includeEliminated"
                        removable
                        @remove="removeFilter('includeEliminated')"
                        :removable="!loading"
                    />
                </div>
                <div class="mb-2">
                    <Chip
                        :label="`Departamento: ${otherFilters[0].department}`"
                        v-if="otherFilters[0].department != null"
                        removable
                        @remove="removeFilter('department')"
                        :removable="!loading"
                    />
                </div>
                <div class="mb-2">
                    <Chip
                        :label="`Estado: ${otherFilters[0].status}`"
                        v-if="otherFilters[0].status != null"
                        removable
                        @remove="removeFilter('status')"
                        :removable="!loading"
                    />
                </div>
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
                width: '15rem',
                display: showColumns.acciones ? '' : 'none',
                minWidth: '15rem',
            }"
            :frozen="frozenColumns.acciones"
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
                        v-if="
                            data.status === 'Pendiente' && can('estimates.edit')
                        "
                        @click="edit(data.id)"
                    />
                    <Button
                        icon="pi pi-trash"
                        severity="danger"
                        v-tooltip.top="'Eliminar'"
                        class="mr-2"
                        rounded
                        v-if="
                            data.status === 'Pendiente' &&
                            can('estimates.delete')
                        "
                        @click="
                            deleteDialog = true;
                            deleteId = data.id;
                        "
                    />
                    <Button
                        icon="pi pi-check"
                        severity="success"
                        v-tooltip.top="'Aprobar'"
                        class="mr-2"
                        rounded
                        v-if="
                            data.status === 'Pendiente' &&
                            can('estimates.approve')
                        "
                        @click="
                            approveDialog = true;
                            approveId = data.id;
                        "
                    />
                    <Button
                        icon="pi pi-times"
                        severity="danger"
                        v-tooltip.top="'Rechazar'"
                        class="mr-2"
                        rounded
                        v-if="
                            data.status === 'Pendiente' &&
                            can('estimates.reject')
                        "
                        @click="
                            rejectDialog = true;
                            rejectId = data.id;
                        "
                    />
                </div>
            </template>
        </Column>
        <Column
            field="id"
            header="#"
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
                    placeholder="Buscar por Id"
                />
            </template>
        </Column>
        <Column
            field="position"
            header="Puesto"
            :filter="true"
            columnKey="puesto"
            :frozen="frozenColumns.puesto"
            :style="{
                width: '5rem',
                display: showColumns.puesto ? '' : 'none',
            }"
            :exportable="exportColumns.puesto"
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>{{ data.posicion }}</span>
            </template>
            <template #filter="{ filterModel }">
                <InputText
                    v-model="filterModel.value"
                    type="text"
                    placeholder="Buscar por Puesto"
                />
            </template>
        </Column>
        <Column
            field="number_employees"
            header="N° Empleados"
            :filter="true"
            columnKey="num_empleados"
            :frozen="frozenColumns.num_empleados"
            :style="{
                width: '5rem',
                display: showColumns.num_empleados ? '' : 'none',
            }"
            :exportable="exportColumns.num_empleados"
            sortable
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>{{ data.number_employees }}</span>
            </template>
        </Column>
        <Column
            field="overtime"
            header="Horas Extra"
            :filter="true"
            columnKey="horas_extra"
            :frozen="frozenColumns.horas_extra"
            :style="{
                width: '5rem',
                display: showColumns.horas_extra ? '' : 'none',
            }"
            :exportable="exportColumns.horas_extra"
            sortable
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>{{ data.overtime }}</span>
            </template>
        </Column>
        <Column
            field="double_overtime"
            header="Horas Extra Dobles"
            :filter="true"
            columnKey="horas_extra_dobles"
            :frozen="frozenColumns.horas_extra_dobles"
            :style="{
                width: '5rem',
                display: showColumns.horas_extra_dobles ? '' : 'none',
            }"
            :exportable="exportColumns.horas_extra_dobles"
            sortable
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>{{ data.double_overtime }}</span>
            </template>
        </Column>
        <Column
            field="triple_overtime"
            header="Horas Extra Triples"
            :filter="true"
            columnKey="horas_extra_triples"
            :frozen="frozenColumns.horas_extra_triples"
            :style="{
                width: '5rem',
                display: showColumns.horas_extra_triples ? '' : 'none',
            }"
            :exportable="exportColumns.horas_extra_triples"
            sortable
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>{{ data.triple_overtime }}</span>
            </template>
        </Column>
        <Column
            field="turno"
            header="Turno"
            :filter="true"
            columnKey="turno"
            :frozen="frozenColumns.turno"
            :style="{
                width: '5rem',
                display: showColumns.turno ? '' : 'none',
            }"
            :exportable="exportColumns.turno"
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>{{ data.turno }}</span>
            </template>
            <template #filter="{ filterModel }">
                <InputText
                    v-model="filterModel.value"
                    type="text"
                    placeholder="Buscar por Turno"
                />
            </template>
        </Column>
        <Column
            field="current_turn"
            header="Turno Corrido"
            :filter="true"
            columnKey="turno_corrido"
            :frozen="frozenColumns.turno_corrido"
            :style="{
                width: '5rem',
                display: showColumns.turno_corrido ? '' : 'none',
            }"
            :exportable="exportColumns.turno_corrido"
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>{{ data.current_turn }}</span>
            </template>
        </Column>
        <Column
            field="cost_max_per_hour"
            header="Costo Max Por Hora"
            :filter="true"
            columnKey="costo_max_por_hora"
            :frozen="frozenColumns.costo_max_hora"
            :style="{
                width: '5rem',
                display: showColumns.costo_max_hora ? '' : 'none',
            }"
            :exportable="exportColumns.costo_max_hora"
            sortable
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>{{ data.cost_max_per_hour }}</span>
            </template>
        </Column>
        <Column
            field="amount_to_pay"
            header="Importe a Pagar"
            :filter="true"
            columnKey="importe_a_pagar"
            :frozen="frozenColumns.importe_pagar"
            :style="{
                width: '5rem',
                display: showColumns.importe_pagar ? '' : 'none',
            }"
            :exportable="exportColumns.importe_pagar"
            sortable
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>{{ data.amount_to_pay }}</span>
            </template>
        </Column>
        <Column
            field="nombremotivo"
            header="Motivo"
            :filter="true"
            columnKey="motivo"
            :frozen="frozenColumns.motivo"
            :style="{
                width: '5rem',
                display: showColumns.motivo ? '' : 'none',
            }"
            :exportable="exportColumns.motivo"
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>{{ data.nombremotivo }}</span>
            </template>
            <template #filter="{ filterModel }">
                <InputText
                    v-model="filterModel.value"
                    type="text"
                    placeholder="Buscar por Motivo"
                />
            </template>
        </Column>
        <Column
            field="week"
            header="Semana"
            :filter="true"
            columnKey="semana"
            :frozen="frozenColumns.semana"
            :style="{
                width: '5rem',
                display: showColumns.semana ? '' : 'none',
            }"
            :exportable="exportColumns.semana"
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>{{ data.week }}</span>
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
            field="status"
            header="Estado"
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
                <span v-else>
                    <Tag
                        :value="data.status"
                        :severity="
                            getStatusSeverity(
                                data.approved_at,
                                data.declined_at,
                                data.validated_at,
                            )
                        "
                    />
                </span>
            </template>
            <template #filter="{ filterModel, filterCallback }">
                <Select
                    v-model="filterModel.value"
                    :options="[
                        { label: 'Pendiente', value: 'Pendiente' },
                        { label: 'Aprobado', value: 'Aprobado' },
                        { label: 'Rechazado', value: 'Rechazado' },
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Selecciona un estado"
                    :filter="true"
                    filterBy="label"
                    showClear
                />
            </template>
        </Column>
        <Column
            field="aprobado_por"
            header="Aprobado por"
            :filter="true"
            columnKey="aprobado_por"
            :frozen="frozenColumns.aprobado_por"
            :style="{
                width: '5rem',
                display: showColumns.aprobado_por ? '' : 'none',
            }"
            :exportable="exportColumns.aprobado_por"
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>{{ data.aprobado_por }}</span>
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
            field="approved_at"
            header="Fecha de aprobación"
            :filter="true"
            columnKey="fecha_aprobacion"
            :frozen="frozenColumns.fecha_aprobacion"
            :style="{
                width: '5rem',
                display: showColumns.fecha_aprobacion ? '' : 'none',
            }"
            :exportable="exportColumns.fecha_aprobacion"
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>{{ transformDate(data.approved_at) }}</span>
            </template>
            <template #filter="{ filterModel }">
                <DatePicker
                    v-model="filterModel.value"
                    type="date"
                    placeholder="Buscar fecha"
                    class="bg-none border-none"
                    @update:modelValue="
                        (val) => (filterModel.value = transformDate(val))
                    "
                />
            </template>
        </Column>
        <Column
            field="declined_at"
            header="Fecha de rechazo"
            :filter="true"
            columnKey="fecha_rechazo"
            :frozen="frozenColumns.fecha_rechazo"
            :style="{
                width: '5rem',
                display: showColumns.fecha_rechazo ? '' : 'none',
            }"
            :exportable="exportColumns.fecha_rechazo"
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>{{ transformDate(data.declined_at) }}</span>
            </template>
            <template #filter="{ filterModel }">
                <DatePicker
                    v-model="filterModel.value"
                    type="date"
                    placeholder="Buscar fecha"
                    class="border-none w-full"
                    @update:modelValue="
                        (val) => (filterModel.value = transformDate(val))
                    "
                />
            </template>
        </Column>
        <Column
            field="declined_by"
            header="Rechazado por"
            :filter="true"
            columnKey="rechazado_por"
            :frozen="frozenColumns.rechazado_por"
            :style="{
                width: '5rem',
                display: showColumns.rechazado_por ? '' : 'none',
            }"
            :exportable="exportColumns.rechazado_por"
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>{{ data.declinado_por }}</span>
            </template>
            <template #filter="{ filterModel }">
                <InputText
                    v-model="filterModel.value"
                    type="text"
                    placeholder="Buscar usuario"
                    class="border-none w-full"
                />
            </template>
        </Column>
        <Column
            field="comments"
            header="Comentarios"
            :filter="true"
            columnKey="comentarios"
            :frozen="frozenColumns.comentarios"
            :style="{
                width: '5rem',
                display: showColumns.comentarios ? '' : 'none',
            }"
            :exportable="exportColumns.comentarios"
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>{{ data.coment }}</span>
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
            <div class="w-full md:w-1/2 px-2 mb-4">
                <label class="block mb-2 font-medium">Planta</label>
                <Select
                    v-model="branchOfficeFilter"
                    :options="props.branchOffices"
                    optionLabel="code"
                    optionValue="id"
                    placeholder="Selecciona una planta"
                    class="w-full"
                    filter
                    filterBy="code"
                />
            </div>

            <!-- Departamento -->
            <div class="w-full md:w-1/2 px-2 mb-4">
                <label class="block mb-2 font-medium">Puesto</label>
                <Select
                    v-model="positionFilter"
                    :options="props.positions"
                    optionLabel="name"
                    optionValue="id"
                    showClear
                    filter
                    placeholder="Selecciona un puesto"
                    class="w-full"
                />
            </div>

            <!-- Semana (type=week => AAAA-WSS) -->
            <div class="w-full md:w-1/2 px-2 mb-4">
                <label class="block mb-2 font-medium">Semana</label>
                <InputText v-model="weekFilter" type="week" class="w-full" />
            </div>

            <div class="w-full md:w-1/2 px-2 mb-4">
                <label class="block mb-2 font-medium">Estado</label>
                <Select
                    v-model="statusFilter"
                    :options="[
                        { label: 'Pendiente', value: 'pendiente' },
                        { label: 'Aprobado', value: 'aprobado' },
                        { label: 'Rechazado', value: 'rechazado' },
                    ]"
                    optionValue="value"
                    optionLabel="label"
                    showClear
                    placeholder="Selecciona un estado"
                    class="w-full"
                >
                    <template #option="{ option }">
                        {{ option.label }}
                    </template>
                </Select>
            </div>
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
                :loading="loading"
            />
        </template>
    </Dialog>
    <ConfirmationDialog
        :visible="deleteDialog"
        @confirm="deleteEmployeeOvertimeEstimate"
        @cancel="deleteDialog = false"
        :loading="loading"
        header="¿Estás seguro de eliminar la estimación?"
        confirmOrDelete="delete"
    />
    <ConfirmationDialog
        :visible="approveDialog"
        @confirm="approveEmployeeOvertimeEstimate"
        @cancel="approveDialog = false"
        :loading="loading"
        header="¿Estás seguro de aprobar la estimación?"
        confirmOrDelete="confirm"
    />
    <ConfirmationDialog
        :visible="rejectDialog"
        @confirm="rejectEmployeeOvertimeEstimate"
        @cancel="rejectDialog = false"
        :loading="loading"
        header="¿Estás seguro de rechazar la estimación?"
        confirmOrDelete="delete"
    />
    <ConfirmationDialog
        :visible="multipleRejectDialog"
        @confirm="multiDeclineEmployeeOvertimeEstimate"
        @cancel="multipleRejectDialog = false"
        :loading="loading"
        header="¿Estás seguro de rechazar las estimaciones seleccionadas?"
        confirmOrDelete="delete"
    />
    <ConfirmationDialog
        :visible="multipleApproveDialog"
        @confirm="multiApproveEmployeeOvertimeEstimate"
        @cancel="multipleApproveDialog = false"
        :loading="loading"
        header="¿Estás seguro de aprobar las estimaciones seleccionadas?"
        confirmOrDelete="confirm"
    />
    <ConfirmationDialog
        :visible="multipleDeleteDialog"
        @confirm="multiDeleteEmployeeOvertimeEstimate"
        @cancel="multipleDeleteDialog = false"
        :loading="loading"
        header="¿Estás seguro de eliminar las estimaciones seleccionadas?"
        confirmOrDelete="delete"
    />
    <Dialog
        v-model:visible="columnsDialog"
        :style="{ width: '450px' }"
        header="Seleccionar columnas a exportar"
        :modal="true"
    >
        <div class="flex flex-col gap-6">
            <div
                v-for="(value, key) in exportColumns"
                :key="key"
                class="flex align-items-center gap-3"
            >
                <Checkbox
                    v-model="exportColumns[key]"
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
                @click="columnsDialog = false"
            />
            <Button
                label="Exportar"
                icon="pi pi-save"
                severity="success"
                @click="saveColumns"
            />
        </template>
    </Dialog>
    <Dialog
        v-model:visible="columnsPdfDialog"
        :style="{ width: '450px' }"
        header="Seleccionar columnas a exportar PDF"
        :modal="true"
    >
        <div class="flex flex-col gap-6">
            <div
                v-for="(value, key) in exportColumns"
                :key="key"
                class="flex align-items-center gap-3"
            >
                <Checkbox
                    v-model="exportColumns[key]"
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
                @click="columnsPdfDialog = false"
            />
            <Button
                label="Exportar"
                icon="pi pi-save"
                severity="success"
                @click="exportToPDF"
                :loading="loading"
            />
        </template>
    </Dialog>
</template>
