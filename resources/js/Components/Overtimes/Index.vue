<script setup>
import axios from "axios";
import { computed, onMounted, ref } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { router } from "@inertiajs/vue3";
import * as xlsx from "xlsx";
import ConfirmationDialog from "../ConfirmationDialog.vue";
import { useToastService } from "@/Stores/toastService";
import { useAuthz } from "@/composables/useAuthz";

const props = defineProps({
    employees: Array,
    branchOffices: Array,
    motivos: Array,
    departments: Array,
});

const { can } = useAuthz();

const { showSuccess, showError } = useToastService();

const selected = ref([]);
const columnsDialog = ref(false);
const op = ref();
const opMostrarColumnas = ref();
const opFijarColumnas = ref();
const overtimes = ref([{}]);
const filters = ref();
const loading = ref(false);
const otherFilterDialog = ref(false);
const deleteDialog = ref(false);
const deleteId = ref();
const approveDialog = ref(false);
const approveId = ref();
const rejectDialog = ref(false);
const rejectId = ref();
const multipleRejectDialog = ref(false);
const multipleDeleteDialog = ref(false);

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

const multipleApproveDialog = ref(false);

const toggleAccionesMasivas = (event) => {
    op.value.toggle(event);
};

const toggleMostrarColumnas = (event) => {
    opMostrarColumnas.value.toggle(event);
};

const toggleFijarColumnas = (event) => {
    opFijarColumnas.value.toggle(event);
};

const exportColumns = ref({
    id: true,
    puesto: true,
    num_empleado: true,
    empleado: true,
    fecha: true,
    horario: true,
    horas_extra_dobles: true,
    horas_extra_triples: true,
    total: true,
    extemporaneo: true,
    fecha_extemporaneo: true,
    prima_dominical: true,
    motivo: true,
    semana: true,
    status: true,
    aprobado_por: true,
    fecha_aprobado: true,
});
const showColumns = ref({
    acciones: true,
    id: true,
    num_empleado: true,
    empleado: true,
    fecha: true,
    horario: true,
    horas_extra_dobles: true,
    horas_extra_triples: true,
    total: true,
    extemporaneo: true,
    fecha_extemporaneo: true,
    prima_dominical: true,
    motivo: true,
    semana: true,
    status: true,
    aprobado_por: true,
    fecha_aprobado: true,
});
const frozenColumns = ref({
    acciones: true,
    id: true,
    num_empleado: false,
    empleado: false,
    fecha: false,
    horario: false,
    horas_extra_dobles: false,
    horas_extra_triples: false,
    total: false,
    extemporaneo: false,
    fecha_extemporaneo: false,
    prima_dominical: false,
    motivo: false,
    semana: false,
    status: false,
    aprobado_por: false,
    fecha_aprobado: false,
});

const columnMap = {
    id: { field: "id", header: "ID" },
    num_empleado: { field: "employee_id", header: "Num Empleado" },
    empleado: { field: "employee_name", header: "Empleado" },
    fecha: { field: "date", header: "Fecha" },
    horario: { field: "horario", header: "Horario" },
    horas_extra_dobles: {
        field: "double_overtime",
        header: "Horas Extra Dobles",
    },
    horas_extra_triples: {
        field: "triple_overtime",
        header: "Horas Extra Triples",
    },
    total: { field: "total", header: "Total Horas Extra" },
    extemporaneo: { field: "untimely", header: "Extemporaneo" },
    fecha_extemporaneo: {
        field: "untimely_date",
        header: "Fecha Extemporaneo",
    },
    prima_dominical: { field: "sunday_premium", header: "Prima Dominical" },
    motivo: { field: "motivo", header: "Motivo" },
    semana: { field: "week_number", header: "Semana" },
    status: { field: "status", header: "Estatus" },
    aprobado_por: { field: "aprobado_por", header: "Aprobado por" },
    fecha_aprobado: { field: "approved_at", header: "Fecha aprobado" },
};

const saveColumns = () => {
    const selectedKeys = Object.keys(exportColumns.value).filter(
        (key) => exportColumns.value[key] === true,
    );

    console.log(selectedKeys);

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
            : overtimes.value;

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

    console.log(dataToExport);

    const worksheet = xlsx.utils.json_to_sheet(dataToExport);
    const workbook = xlsx.utils.book_new();
    xlsx.utils.book_append_sheet(workbook, worksheet, "Horas Extra");
    xlsx.writeFile(workbook, "Horas_Extras_Empleado.xlsx");

    columnsDialog.value = false;
};

const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        id: {
            operator: FilterOperator.AND,
            constraints: [
                { value: null, matchMode: FilterMatchMode.STARTS_WITH },
            ],
        },
        employee_id: {
            operator: FilterOperator.AND,
            constraints: [
                { value: null, matchMode: FilterMatchMode.STARTS_WITH },
            ],
        },
        employee_name: {
            operator: FilterOperator.AND,
            constraints: [
                { value: null, matchMode: FilterMatchMode.STARTS_WITH },
            ],
        },
        date: {
            operator: FilterOperator.AND,
            constraints: [
                { value: null, matchMode: FilterMatchMode.STARTS_WITH },
            ],
        },
        horario: {
            operator: FilterOperator.AND,
            constraints: [
                { value: null, matchMode: FilterMatchMode.STARTS_WITH },
            ],
        },
        untimely_date: {
            operator: FilterOperator.AND,
            constraints: [
                { value: null, matchMode: FilterMatchMode.STARTS_WITH },
            ],
        },
        sunday_premium: {
            operator: FilterOperator.AND,
            constraints: [
                { value: null, matchMode: FilterMatchMode.STARTS_WITH },
            ],
        },
        untimely: {
            operator: FilterOperator.AND,
            constraints: [
                { value: null, matchMode: FilterMatchMode.STARTS_WITH },
            ],
        },
        motivo: {
            operator: FilterOperator.AND,
            constraints: [
                { value: null, matchMode: FilterMatchMode.STARTS_WITH },
            ],
        },
        semana: {
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
    };
};

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

const employeesByBranchOffice = ref(
    props.employees.filter(
        (employee) =>
            employee.branch_office_id === selectedBranchOffice.value.id,
    ),
);

const year = ref(getISOWeek().year);
const weekNumber = ref(getISOWeek().week);

const departmentFilter = ref();
const statusFilter = ref();
const weekFilter = ref(
    `${year.value}-W${String(weekNumber.value).padStart(2, "0")}`,
);
const sundayPremiumFilter = ref();
const employeeFilter = ref();
const includeEliminatedFilter = ref();
const branchOfficeFilter = ref(selectedBranchOffice.value.id);

const otherFilters = ref([
    {
        branch_office_id: selectedBranchOffice.value.code,
        employee_id: null,
        department: null,
        includeEliminated: null,
        week: `${year.value}-W${String(weekNumber.value).padStart(2, "0")}`,
        status: null,
        sundayPremium: null,
    },
]);

const disableApproveRejectButton = computed(() => {
    return (
        selected.value.length === 0 ||
        selected.value.some((item) => item.approved_at || item.declined_at)
    );
});

const getStatus = (item) => {
    if (item.declined_at) {
        return "Rechazado";
    }

    if (item.approved_at && item.txt === 1) {
        return "TXT";
    }

    if (!item.approved_at && item.txt === 1) {
        return "Pendiente - TXT";
    }

    if (item.approved_at) {
        return "Aprobado";
    }

    return "Pendiente";
};

const getStatusFilter = (item) => {
    if (item == "approved") {
        return "Aprobado";
    }

    if (item == "declined") {
        return "Rechazado";
    }

    if (item == "txt_pendiente") {
        return "Pendiente - TXT";
    }

    if (item == "txt_aprobado") {
        return "Aprobado - TXT";
    }

    return "Pendiente";
};

const getSeverity = (item) => {
    if (item == "Aprobado") {
        return "success";
    }

    if (item == "Rechazado") {
        return "danger";
    }

    if (item == "TXT") {
        return "info";
    }

    return "primary";
};

const getOvertimes = async () => {
    loading.value = true;
    const response = await axios.get("/api/employees-overtimes", {
        params: {
            semana: `${year.value}-W${String(weekNumber.value).padStart(2, "0")}`,
            planta: selectedBranchOffice.value.id,
        },
    });

    console.log(response);

    overtimes.value = response.data;
    overtimes.value = overtimes.value.map((item) => {
        return {
            ...item,
            double_overtime: JSON.parse(item.overtimes).double_overtime,
            triple_overtime: JSON.parse(item.overtimes).triple_overtime,
            total: JSON.parse(item.overtimes).total,
            motivo: `(${item.idMotivo}) ${item.nombremotivo}`,
            status: getStatus(item),
        };
    });
    loading.value = false;
};

const transformDate = (date) => {
    if (!date) return null;
    const newDate = new Date(date);
    const year = newDate.getUTCFullYear();
    const month = newDate.getUTCMonth() + 1;
    const day = newDate.getUTCDate();

    return `${year}-${month < 10 ? "0" + month : month}-${day < 10 ? "0" + day : day}`;
};

const edit = (id) => {
    router.get(`/employee/employee-overtimes/overtimes/${id}/edit`);
};

const removeFilter = (filter) => {
    otherFilters.value[0][filter] = null;
    switch (filter) {
        case "branch_office_id":
            branchOfficeFilter.value = null;
            break;
        case "department":
            departmentFilter.value = null;
            break;
        case "employee_id":
            employeeFilter.value = null;
            break;
        case "includeEliminated":
            includeEliminated.value = null;
            break;
        case "status":
            statusFilter.value = null;
            break;
        case "sundayPremium":
            sundayPremiumFilter.value = null;
            break;
        case "week":
            weekFilter.value = null;
            break;
    }
    applyFilters();
};

const applyFilters = async () => {
    loading.value = true;
    const selectedBranchOffice = props.branchOffices.find(
        (branchOffice) => branchOffice.id === branchOfficeFilter.value,
    );
    const selectedEmployee = props.employees.find(
        (employee) => employee.id === employeeFilter.value,
    );
    const selectedDepartment = props.departments.find(
        (department) => department.id === departmentFilter.value,
    );

    console.log(selectedEmployee);
    otherFilters.value[0].branch_office_id = selectedBranchOffice?.code;
    otherFilters.value[0].department = selectedDepartment?.name;
    otherFilters.value[0].employee_id = selectedEmployee?.full_name;
    otherFilters.value[0].includeEliminated = includeEliminatedFilter.value;
    otherFilters.value[0].status = statusFilter.value;
    otherFilters.value[0].sundayPremium = sundayPremiumFilter.value;
    otherFilters.value[0].week = weekFilter.value;

    const response = await axios.get("/api/employees-overtimes", {
        params: {
            semana: weekFilter.value,
            planta: branchOfficeFilter.value,
            departamento: departmentFilter.value,
            estatus: statusFilter.value,
            empleado: employeeFilter.value,
            primaDominical: sundayPremiumFilter.value,
        },
    });

    overtimes.value = response.data;
    overtimes.value = overtimes.value.map((item) => {
        return {
            ...item,
            double_overtime: JSON.parse(item.overtimes).double_overtime,
            triple_overtime: JSON.parse(item.overtimes).triple_overtime,
            total: JSON.parse(item.overtimes).total,
            motivo: `(${item.idMotivo}) ${item.nombremotivo}`,
            status: getStatus(item),
        };
    });

    loading.value = false;
    otherFilterDialog.value = false;
};

const deleteEmployeeOvertime = () => {
    loading.value = true;
    router.delete(`/employee/employee-overtimes/overtimes/${deleteId.value}`, {
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

const approveEmployeeOvertime = () => {
    loading.value = true;
    router.post(
        "/employee/employee-overtimes/overtimes/approve",
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

const rejectEmployeeOvertime = () => {
    loading.value = true;
    router.post(
        "/employee/employee-overtimes/overtimes/decline",
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

const multiApproveEmployeeOvertime = () => {
    loading.value = true;
    router.post(
        "/employee/employee-overtimes/overtimes/multi-approve",
        {
            ids: selected.value.map((item) => item.id),
        },
        {
            onSuccess: () => {
                showSuccess();
                multipleApproveDialog.value = false;
                applyFilters();
                op.value.hide();
                selected.value = [];
            },
            onError: () => {
                showError();
                multipleApproveDialog.value = false;
                op.value.hide();
            },
        },
    );
};

const multiDeclineEmployeeOvertime = () => {
    loading.value = true;
    router.post(
        "/employee/employee-overtimes/overtimes/multi-decline",
        {
            ids: selected.value.map((item) => item.id),
        },
        {
            onSuccess: () => {
                showSuccess();
                multipleRejectDialog.value = false;
                applyFilters();
                op.value.hide();
                selected.value = [];
            },
            onError: () => {
                showError();
                multipleRejectDialog.value = false;
                op.value.hide();
            },
        },
    );
};

const multiDeleteEmployeeOvertime = () => {
    loading.value = true;
    router.post(
        "/employee/employee-overtimes/overtimes/multi-delete",
        {
            ids: selected.value.map((item) => item.id),
        },
        {
            onSuccess: () => {
                showSuccess();
                multipleDeleteDialog.value = false;
                applyFilters();
                op.value.hide();
                selected.value = [];
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

initFilters();

getOvertimes();
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
                v-if="can('overtimes.export')"
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
                    can('overtimes.multiple-approve') ||
                    can('overtimes.multiple-reject') ||
                    can('overtimes.multiple-delete')
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
                                    v-if="can('overtimes.multiple-approve')"
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
                                    v-if="can('overtimes.multiple-reject')"
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
                                    v-if="can('overtimes.multiple-delete')"
                                />
                            </li>
                        </ul>
                    </div>
                </div>
            </Popover>
            <Link
                v-if="can('overtimes.create')"
                :href="route('overtimes.create')"
            >
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
        ref="dt"
        v-model:selection="selected"
        :value="overtimes"
        dataKey="id"
        :paginator="true"
        :rows="10"
        scrollable
        reorderableColumns
        scrollHeight="400px"
        :filterDelay="500"
        v-model:filters="filters"
        filterDisplay="menu"
        exportFilename="horas_extras"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} horas extra"
    >
        <template #header>
            <div class="flex flex-wrap gap-2 items-end justify-between mb-6">
                <div>
                    <h4 class="m-0">Horas Extra por Empleado</h4>
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
                        :label="`Estado: ${getStatusFilter(otherFilters[0].status)}`"
                        v-if="otherFilters[0].status != null"
                        removable
                        @remove="removeFilter('status')"
                        :removable="!loading"
                    />
                </div>
                <div class="mb-2">
                    <Chip
                        :label="`Prima dominical: ${
                            otherFilters[0].sundayPremium === 'si' ? 'Si' : 'No'
                        }`"
                        v-if="otherFilters[0].sundayPremium"
                        removable
                        @remove="removeFilter('sundayPremium')"
                        :removable="!loading"
                    />
                </div>
            </div>
        </template>
        <template #empty></template>
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
                            data.status === 'Pendiente' && can('overtimes.edit')
                        "
                        @click="edit(data.id)"
                    />
                    <Button
                        icon="pi pi-trash"
                        severity="danger"
                        v-tooltip.top="'Eliminar'"
                        class="mr-2"
                        rounded
                        @click="
                            deleteDialog = true;
                            deleteId = data.id;
                        "
                        v-if="can('overtimes.delete')"
                    />
                    <Button
                        icon="pi pi-check"
                        :severity="data.has_error == 1 ? 'warn' : 'success'"
                        v-tooltip.top="
                            data.has_error == 1 ? 'Validar' : 'Aprobar'
                        "
                        class="mr-2"
                        rounded
                        v-if="
                            (data.status === 'Rechazado' ||
                                data.status === 'Pendiente') &&
                            can('overtimes.approve')
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
                            (data.status === 'Aprobado' ||
                                data.status === 'Pendiente') &&
                            can('overtimes.reject')
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
            field="status"
            header="Estatus"
            :filter="true"
            columnKey="total"
            :frozen="frozenColumns.status"
            :style="{
                width: '5rem',
                display: showColumns.status ? '' : 'none',
            }"
            :exportable="exportColumns.status"
            :show-filter-match-modes="false"
            :show-filter-operator="false"
            :show-add-button="false"
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>
                    <Tag
                        :severity="getSeverity(data.status)"
                        :value="data.status"
                    />
                </span>
            </template>
            <template #filter="{ filterModel }">
                <Select
                    v-model="filterModel.value"
                    :options="[
                        { label: 'Pendiente', value: 'Pendiente' },
                        { label: 'Aprobado', value: 'Aprobado' },
                        { label: 'Declinado', value: 'Declinado' },
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Selecciona..."
                    class="w-full"
                />
            </template>
        </Column>
        <Column
            field="employee_id"
            header="Numero de Nómina"
            :filter="true"
            columnKey="employee_id"
            :frozen="frozenColumns.num_empleado"
            :style="{
                width: '5rem',
                display: showColumns.num_empleado ? '' : 'none',
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
                    placeholder="Buscar por Numero de Nomina"
                />
            </template>
        </Column>
        <Column
            field="employee_name"
            header="Empleado"
            :filter="true"
            columnKey="employee_name"
            :frozen="frozenColumns.empleado"
            :style="{
                width: '5rem',
                display: showColumns.empleado ? '' : 'none',
            }"
            :exportable="exportColumns.empleado"
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>{{ data.employee_name }}</span>
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
            field="date"
            header="Fecha"
            :filter="true"
            columnKey="date"
            :frozen="frozenColumns.fecha"
            :style="{
                width: '5rem',
                display: showColumns.fecha ? '' : 'none',
            }"
            :exportable="exportColumns.fecha"
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>{{ data.date }}</span>
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
            field="horario"
            header="Horario"
            :filter="true"
            columnKey="horario"
            :frozen="frozenColumns.horario"
            :style="{
                width: '5rem',
                display: showColumns.horario ? '' : 'none',
            }"
            :exportable="exportColumns.horario"
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>{{ data.complete == 1 ? data.horario : "" }}</span>
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
            field="double_overtime"
            header="Horas Extras Dobles"
            :filter="true"
            columnKey="double_overtime"
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
                <span v-else>
                    <div class="flex justify-center">
                        {{ data.double_overtime }}
                    </div>
                </span>
            </template>
        </Column>
        <Column
            field="triple_overtime"
            header="Horas Extras Triples"
            :filter="true"
            columnKey="triple_overtime"
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
                <span v-else>
                    <div class="flex justify-center">
                        {{ data.triple_overtime }}
                    </div></span
                >
            </template>
        </Column>
        <Column
            field="total"
            header="Total Horas Extras"
            :filter="true"
            columnKey="total"
            :frozen="frozenColumns.total"
            :style="{
                width: '5rem',
                display: showColumns.total ? '' : 'none',
            }"
            :exportable="exportColumns.total"
            sortable
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>
                    <div class="flex justify-center">
                        {{ data.total }}
                    </div>
                </span>
            </template>
        </Column>
        <Column
            field="untimely"
            header="Extemporaneo"
            :filter="true"
            columnKey="total"
            :frozen="frozenColumns.extemporaneo"
            :style="{
                width: '5rem',
                display: showColumns.extemporaneo ? '' : 'none',
            }"
            :exportable="exportColumns.extemporaneo"
            :show-filter-match-modes="false"
            :show-filter-operator="false"
            :show-add-button="false"
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>
                    <Tag
                        :icon="
                            data.untimely == 0 ? 'pi pi-times' : 'pi pi-check'
                        "
                        :severity="data.untimely == 0 ? 'danger' : 'success'"
                        :value="data.untimely == 0 ? 'No' : 'Si'"
                    />
                </span>
            </template>
            <template #filter="{ filterModel }">
                <Select
                    v-model="filterModel.value"
                    :options="[
                        {
                            label: 'Si',
                            value: 1,
                        },
                        { label: 'No', value: 0 },
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Selecciona..."
                    class="w-full"
                />
            </template>
        </Column>
        <Column
            field="untimely_date"
            header="Fecha Extemporaneo"
            :filter="true"
            columnKey="total"
            :frozen="frozenColumns.fecha_extemporaneo"
            :style="{
                width: '5rem',
                display: showColumns.fecha_extemporaneo ? '' : 'none',
            }"
            :exportable="exportColumns.fecha_extemporaneo"
            sortable
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>
                    {{ data.untimely_date }}
                </span>
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
            field="sunday_premium"
            header="Prima Domincal"
            :filter="true"
            columnKey="total"
            :frozen="frozenColumns.prima_dominical"
            :style="{
                width: '5rem',
                display: showColumns.prima_dominical ? '' : 'none',
            }"
            :exportable="exportColumns.prima_dominical"
            :show-filter-match-modes="false"
            :show-filter-operator="false"
            :show-add-button="false"
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>
                    <Tag
                        :icon="
                            data.sunday_premium == true
                                ? 'pi pi-check'
                                : 'pi pi-times'
                        "
                        :severity="
                            data.sunday_premium == true ? 'success' : 'danger'
                        "
                        :value="data.sunday_premium == true ? 'Si' : 'No'"
                    />
                </span>
            </template>
            <template #filter="{ filterModel }">
                <Select
                    v-model="filterModel.value"
                    :options="[
                        {
                            label: 'Si',
                            value: true,
                        },
                        { label: 'No', value: false },
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Selecciona..."
                    class="w-full"
                />
            </template>
        </Column>
        <Column
            field="motivo"
            header="Motivo"
            :filter="true"
            columnKey="total"
            :frozen="frozenColumns.motivo"
            :style="{
                width: '12rem',
                display: showColumns.motivo ? '' : 'none',
                minWidth: '12rem',
            }"
            :exportable="exportColumns.motivo"
            :show-filter-match-modes="false"
            :show-filter-operator="false"
            :show-add-button="false"
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else class="flex items-center justify-between">
                    {{ data.motivo }}
                    <Tag
                        icon="pi pi-question-circle"
                        severity="info"
                        class="rounded-full"
                        v-tooltip.top="data.descripcionmotivo"
                    />
                </span>
            </template>
            <template #filter="{ filterModel }">
                <Select
                    v-model="filterModel.value"
                    :options="props.motivos"
                    :optionLabel="
                        (option) => {
                            return `(${option.id}) ${option.name}`;
                        }
                    "
                    :optionValue="
                        (option) => {
                            return `(${option.id}) ${option.name}`;
                        }
                    "
                    placeholder="Selecciona..."
                    class="w-full"
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
                <span v-else>
                    {{ data.week_number }}
                </span>
            </template>
            <template #filter="{ filterModel }">
                <InputText
                    v-model="filterModel.value"
                    type="text"
                    placeholder="Buscar por Numero de Semana"
                />
            </template>
        </Column>

        <Column
            field="aprobado_por"
            header="Aprobado por"
            :filter="true"
            columnKey="total"
            :frozen="frozenColumns.aprobado_por"
            :style="{
                width: '5rem',
                display: showColumns.aprobado_por ? '' : 'none',
            }"
            :exportable="exportColumns.aprobado_por"
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>
                    {{ data.aprobado_por }}
                </span>
            </template>
            <template #filter="{ filterModel }">
                <InputText
                    v-model="filterModel.value"
                    type="text"
                    placeholder="Buscar por Nombre"
                />
            </template>
        </Column>
        <Column
            field="approved_at"
            header="Fecha Aprobacion"
            :filter="true"
            columnKey="total"
            :frozen="frozenColumns.fecha_aprobado"
            :style="{
                width: '5rem',
                display: showColumns.fecha_aprobado ? '' : 'none',
            }"
            :exportable="exportColumns.fecha_aprobado"
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>
                    {{ data.approved_at }}
                </span>
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

            <div class="w-full md:w-1/2 px-2 mb-4">
                <label class="block mb-2 font-medium">Empleado</label>
                <Select
                    v-model="employeeFilter"
                    :options="employeesByBranchOffice"
                    optionValue="id"
                    optionLabel="full_name"
                    showClear
                    filter
                    placeholder="Selecciona un empleado"
                    class="w-full"
                >
                    <template #option="{ option }">
                        ({{ option.id }}) {{ option.full_name }}
                    </template>
                </Select>
            </div>

            <!-- Departamento -->
            <div class="w-full md:w-1/2 px-2 mb-4">
                <label class="block mb-2 font-medium">Departamento</label>
                <Select
                    v-model="departmentFilter"
                    :options="props.departments"
                    optionLabel="name"
                    optionValue="id"
                    showClear
                    filter
                    placeholder="Selecciona un departamento"
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
                        { label: 'Pendiente', value: 'pending' },
                        { label: 'Aprobado', value: 'approved' },
                        { label: 'Rechazado', value: 'declined' },
                        { label: 'Pendiente - TXT', value: 'txt_pendiente' },
                        { label: 'Aprobado - TXT', value: 'txt_aprobado' },
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

            <div class="w-full md:w-1/2 px-2 mb-4">
                <label class="block mb-2 font-medium">Prima Dominical</label>
                <Select
                    v-model="sundayPremiumFilter"
                    :options="[
                        { label: 'Si', value: 'si' },
                        { label: 'No', value: 'no' },
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
        @confirm="deleteEmployeeOvertime"
        @cancel="deleteDialog = false"
        :loading="loading"
        header="¿Estás seguro de eliminar el registro de horas extra?"
        confirmOrDelete="delete"
    />
    <ConfirmationDialog
        :visible="approveDialog"
        @confirm="approveEmployeeOvertime"
        @cancel="approveDialog = false"
        :loading="loading"
        header="¿Estás seguro de aprobar las horas extra?"
        confirmOrDelete="confirm"
    />
    <ConfirmationDialog
        :visible="rejectDialog"
        @confirm="rejectEmployeeOvertime"
        @cancel="rejectDialog = false"
        :loading="loading"
        header="¿Estás seguro de rechazar las horas extra?"
        confirmOrDelete="delete"
    />
    <ConfirmationDialog
        :visible="multipleRejectDialog"
        @confirm="multiDeclineEmployeeOvertime"
        @cancel="multipleRejectDialog = false"
        :loading="loading"
        header="¿Estás seguro de rechazar las horas extra seleccionadas?"
        confirmOrDelete="delete"
    />
    <ConfirmationDialog
        :visible="multipleApproveDialog"
        @confirm="multiApproveEmployeeOvertime"
        @cancel="multipleApproveDialog = false"
        :loading="loading"
        header="¿Estás seguro de aprobar las horas extra seleccionadas?"
        confirmOrDelete="confirm"
    />
    <ConfirmationDialog
        :visible="multipleDeleteDialog"
        @confirm="multiDeleteEmployeeOvertime"
        @cancel="multipleDeleteDialog = false"
        :loading="loading"
        header="¿Estás seguro de eliminar las horas extra seleccionadas?"
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
</template>
