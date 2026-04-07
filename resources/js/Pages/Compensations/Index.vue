<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { computed, onMounted, ref, watch } from "vue";
import axios from "axios";
import { useToast } from "primevue";
import { useToastService } from "@/Stores/toastService";
import { router, usePage } from "@inertiajs/vue3";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import * as xlsx from "xlsx";
import ConfirmationDialog from "@/Components/ConfirmationDialog.vue";
import { useAuthz } from "@/composables/useAuthz";

const page = usePage();

const { can } = useAuthz();

const props = defineProps({
    departments: Array,
});

const { showSuccess, showError } = useToastService();

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

const filters = ref({});

const weekNumber = ref(getISOWeek().week);
const year = ref(getISOWeek().year);
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
const departmentId = ref(null);
const employeeId = ref(null);
const approved = ref(false);
// const includeEliminated = ref(false);

const deleteDialog = ref(false);
const deleteMultipleDialog = ref(false);
const compensationToDelete = ref({});

const otherFilterDialog = ref(false);
const selected = ref([]);
const op = ref(null);
const opMostrarColumnas = ref(null);
const opFijarColumnas = ref(null);

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

const openUploadDialog = ref(false);
const columnsDialog = ref(false);
const submitted = ref(false);
const visible = ref(false);
const progress = ref(0);
const interval = ref(null);
const toast = useToast();
const loading = ref(false);

const showColumns = ref({
    acciones: true,
    id: false,
    clave_empleado: true,
    nombre_empleado: true,
    puesto: true,
    departamento: true,
    compensacion_puesto: true,
    eficiencia: true,
    compensacion: true,
    destajo: true,
    extra_compensacion: true,
    apoyo_transporte: true,
    total: true,
    observaciones: false,
    aprobado_por: false,
    fecha_aprobado: false,
    fecha_creado: false,
    fecha_actualizado: false,
    fecha_eliminado: false,
    status: true,
});

const frozenColumns = ref({
    acciones: false,
    id: false,
    clave_empleado: false,
    nombre_empleado: false,
    puesto: false,
    departamento: false,
    compensacion_puesto: false,
    eficiencia: false,
    compensacion: false,
    destajo: false,
    extra_compensacion: false,
    apoyo_transporte: false,
    total: false,
    observaciones: false,
    aprobado_por: false,
    fecha_aprobado: false,
    fecha_creado: false,
    fecha_actualizado: false,
    fecha_eliminado: false,
    status: false,
});

const otherFilters = ref([
    {
        branchOfficeId: selectedBranchOffice.value.code,
        departmentId: null,
        employeeId: null,
        approved: null,
        // includeEliminated: null,
        week: `${year.value}-W${String(weekNumber.value).padStart(2, "0")}`,
    },
]);

const compensations = ref([{}]);

const multipleApproveDialog = ref(false);
const multipleRejectDialog = ref(false);
const approveDialog = ref(false);
const rejectDialog = ref(false);
const dataApprove = ref(null);
const dataReject = ref(null);
const branchOffices = ref();
const branchOfficeIdFilter = ref(selectedBranchOffice.value.id);
const departmentIdFilter = ref();
const employeeIdFilter = ref();
const approvedFilter = ref();
const weekFilter = ref(
    `${year.value}-W${String(weekNumber.value).padStart(2, "0")}`,
);
const employees = ref();
const approvedOptions = ref([
    { name: "Todos", value: null },
    { name: "Aprobado", value: true },
    { name: "No Aprobado", value: false },
]);

const showDeleted = ref(false);

const importFailures = ref([]);
const badHeaders = computed(() => {
    const attrs = importFailures.value.map((f) => f.attribute).filter(Boolean);
    return [...new Set(attrs)];
});

const applyFilters = async () => {
    console.log(branchOffices.value);
    loading.value = true;
    otherFilterDialog.value = false;
    const selectedBranchOffice = branchOffices.value.find(
        (branchOffice) => branchOffice.id === branchOfficeIdFilter.value,
    );

    const selectedDepartment = props.departments.find(
        (department) => department.id === departmentIdFilter?.value,
    );
    const selectedEmployee = employees.value.find(
        (employee) => employee.id === employeeIdFilter?.value,
    );
    otherFilters.value = [
        {
            branchOfficeId: selectedBranchOffice.code,
            departmentId: selectedDepartment?.name,
            employeeId: selectedEmployee?.full_name,
            approved: approvedFilter.value,
            // includeEliminated: showDeleted.value,
            week: weekFilter.value,
        },
    ];

    let weekf = null;
    let yearf = null;

    if (weekFilter.value) {
        [yearf, weekf] = weekFilter.value.split("-W");
    }

    const res = await axios.get("/api/compensations/filter-data", {
        params: {
            week: weekf,
            branchOfficeId: branchOfficeIdFilter.value,
            departmentId: departmentIdFilter.value,
            employeeId: employeeIdFilter.value,
            approved: approvedFilter.value,
            year: yearf,
            // includeEliminated: showDeleted.value,
        },
    });
    compensations.value = res.data;
    compensations.value = compensations.value.map((item) => {
        return { ...item, status: dataStatus(item) };
    });
    loading.value = false;
    console.log("Filtros aplicados:", otherFilters.value);
};

const removeFilter = (filter) => {
    otherFilters.value[0][filter] = null;
    switch (filter) {
        case "departmentId":
            departmentIdFilter.value = null;
            break;
        case "employeeId":
            employeeIdFilter.value = null;
            break;
        case "approved":
            approvedFilter.value = null;
            break;
        case "week":
            weekFilter.value = null;
            break;
        // case "includeEliminated":
        //     showDeleted.value = false;
        //     break;
    }
    applyFilters();
};

const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        id: { value: null, matchMode: FilterMatchMode.CONTAINS },
        eficiencia: { value: null, matchMode: FilterMatchMode.CONTAINS },
        compensacion_puesto: {
            value: null,
            matchMode: FilterMatchMode.CONTAINS,
        },
        compensacion: { value: null, matchMode: FilterMatchMode.CONTAINS },
        nombre_empleado: { value: null, matchMode: FilterMatchMode.CONTAINS },
        aprobado_por: { value: null, matchMode: FilterMatchMode.CONTAINS },
        fecha_aprobado: { value: null, matchMode: FilterMatchMode.CONTAINS },
        fecha_rechazado: { value: null, matchMode: FilterMatchMode.CONTAINS },
        creado: { value: null, matchMode: FilterMatchMode.CONTAINS },
        actualizado: { value: null, matchMode: FilterMatchMode.CONTAINS },
        clave_empleado: { value: null, matchMode: FilterMatchMode.CONTAINS },
        departamento: { value: null, matchMode: FilterMatchMode.CONTAINS },
        eficiencia: { value: null, matchMode: FilterMatchMode.CONTAINS },
        extra_compensacion: {
            value: null,
            matchMode: FilterMatchMode.CONTAINS,
        },
        observaciones: { value: null, matchMode: FilterMatchMode.CONTAINS },
        piece_work: { value: null, matchMode: FilterMatchMode.CONTAINS },
        tiempo_extra: { value: null, matchMode: FilterMatchMode.CONTAINS },
        total: { value: null, matchMode: FilterMatchMode.CONTAINS },
        apoyo_transporte: { value: null, matchMode: FilterMatchMode.CONTAINS },
        // deleted_at: { value: null, matchMode: FilterMatchMode.CONTAINS },
        status: { value: null, matchMode: FilterMatchMode.CONTAINS },
    };
};

initFilters();

watch(
    () => page.props.flash?.import_failures,
    (val) => {
        importFailures.value = Array.isArray(val) ? val : [];
    },
    { immediate: true },
);

const onCustomUploader = async ({ files, options }) => {
    console.log("custom upload", files);

    importFailures.value = [];

    submitted.value = true;

    if (!visible.value) {
        toast.add({
            severity: "custom",
            summary: "Subiendo archivos.",
            group: "headless",
            styleClass: "backdrop-blur-lg rounded-2xl",
        });
        visible.value = true;
        progress.value = 0;

        if (interval.value) {
            clearInterval(interval.value);
        }

        try {
            const file = files?.[0];
            if (!file) throw new Error("No se recibió archivo");

            const formData = new FormData();
            formData.append("file", file);
            formData.append("branch_office_id", branchOfficeId.value.id);

            interval.value = setInterval(() => {
                if (progress.value <= 100) {
                    progress.value = progress.value + 20;
                }

                if (progress.value >= 100) {
                    progress.value = 100;
                    clearInterval(interval.value);
                    visible.value = false;
                }
            }, 1000);

            router.post("/employee/compensations/import", formData, {
                onSuccess: () => {
                    toast.removeGroup("headless");
                    openUploadDialog.value = false;
                    submitted.value = false;
                    showSuccess();
                },
                onError: () => {
                    submitted.value = false;
                    openUploadDialog.value = false;
                },
                preserveScroll: true,
                preserveState: true,
            });
        } catch (err) {
            console.log(err);
        } finally {
            visible.value = false;
            progress.value = 0;
            applyFilters();
        }
    }
};

const fileIcon = (file) => {
    const ext = file.name.split(".").pop()?.toLowerCase();
    if (ext === "csv") return "pi-file"; // ícono genérico de archivo
    if (["xls", "xlsx"].includes(ext)) return "pi-file-excel"; // ícono de Excel
    return "pi-file";
};

const exportColumns = ref({
    id: true,
    clave_empleado: true,
    nombre_empleado: true,
    posicion: true,
    departamento: true,
    compensacion_puesto: true,
    eficiencia: true,
    compensacion: true,
    destajo: true,
    extra_compensacion: true,
    apoyo_transporte: true,
    total: true,
    observaciones: true,
    aprobado_por: true,
    fecha_aprobado: true,
    fecha_creado: true,
    fecha_actualizado: true,
    status: true,
});

const columnMap = {
    id: { field: "id", header: "ID" },
    clave_empleado: { field: "clave_empleado", header: "Num Empleado" },
    nombre_empleado: { field: "nombre_empleado", header: "Empleado" },
    posicion: { field: "posicion", header: "Posicion" },
    departamento: { field: "departamento", header: "Departamento" },
    compensacion_puesto: {
        field: "compensacion_puesto",
        header: "Compensacion del Puesto",
    },
    eficiencia: {
        field: "eficiencia",
        header: "Eficiencia",
    },
    compensacion: { field: "compensacion", header: "Compensacion" },
    destajo: { field: "piece_work", header: "Destajo" },
    extra_compensacion: {
        field: "extra_compensacion",
        header: "Compensacion Extra",
    },
    apoyo_transporte: { field: "apoyo_transporte", header: "Apoyo Transporte" },
    total: { field: "total", header: "Total" },
    status: { field: "status", header: "Estatus" },
    observaciones: { field: "observaciones", header: "Observaciones" },
    aprobado_por: { field: "aprobado_por", header: "Aprobado Por" },
    fecha_aprobado: { field: "fecha_aprobado", header: "Fecha Aprobado" },
    fecha_creado: { field: "fecha_creado", header: "Fecha de Creación" },
    fecha_actualizado: {
        field: "fecha_actualizado",
        header: "Fecha de Actualización",
    },
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
            : compensations.value;

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
    xlsx.utils.book_append_sheet(
        workbook,
        worksheet,
        "Compensaciones por Empleado",
    );
    xlsx.writeFile(workbook, "Compensaciones por Empleado.xlsx");

    columnsDialog.value = false;
};

const disableApproveRejectButton = computed(() => {
    console.log(selected.value);
    return (
        selected.value.length === 0 ||
        selected.value.some(
            (item) => item.fecha_aprobado || item.fecha_rechazado,
        )
    );
});

const deleteCompensation = () => {
    loading.value = true;
    console.log(compensationToDelete.value.value.id);
    router.delete(
        `/employee/compensations/${compensationToDelete.value.value.id}`,
        {
            onSuccess: () => {
                deleteDialog.value = false;
                compensationToDelete.value = {};
                showSuccess();
                loading.value = false;
                applyFilters();
            },
            onError: () => {
                deleteDialog.value = false;
                showError();
                loading.value = false;
            },
        },
    );
};

const approve = () => {
    loading.value = true;
    router.post(
        `/employee/compensations/approve`,
        {
            id: dataApprove.value.id,
        },
        {
            onSuccess: () => {
                showSuccess();
                loading.value = false;
                approveDialog.value = false;
                applyFilters();
            },
            onError: () => {
                showError();
                loading.value = false;
                approveDialog.value = false;
            },
        },
    );
};

const reject = () => {
    loading.value = true;
    router.post(
        `/employee/compensations/reject`,
        {
            id: dataReject.value.id,
        },
        {
            onSuccess: () => {
                showSuccess();
                loading.value = false;
                rejectDialog.value = false;
                applyFilters();
            },
            onError: () => {
                showError();
                loading.value = false;
                rejectDialog.value = false;
            },
        },
    );
};

const deleteMultipleCompensations = () => {
    loading.value = true;
    const ids = selected.value.map((item) => item.id);
    router.post(
        `/employee/compensations/multiple-delete`,
        {
            ids: ids,
        },
        {
            onSuccess: () => {
                deleteMultipleDialog.value = false;
                showSuccess();
                loading.value = false;
                selected.value = [];
                applyFilters();
            },
            onError: () => {
                deleteMultipleDialog.value = false;
                showError();
                loading.value = false;
            },
        },
    );
};

const approveMultipleCompensations = () => {
    loading.value = true;
    const ids = selected.value.map((item) => item.id);
    router.post(
        `/employee/compensations/multiple-approve`,
        {
            ids: ids,
        },
        {
            onSuccess: () => {
                multipleApproveDialog.value = false;
                showSuccess();
                loading.value = false;
                selected.value = [];
                applyFilters();
            },
            onError: () => {
                multipleApproveDialog.value = false;
                showError();
                loading.value = false;
            },
        },
    );
};

const rejectMultipleCompensations = () => {
    loading.value = true;
    const ids = selected.value.map((item) => item.id);
    router.post(
        `/employee/compensations/multiple-reject`,
        {
            ids: ids,
        },
        {
            onSuccess: () => {
                rejectMultipleDialog.value = false;
                showSuccess();
                loading.value = false;
                selected.value = [];
                applyFilters();
            },
            onError: () => {
                rejectMultipleDialog.value = false;
                showError();
                loading.value = false;
            },
        },
    );
};

const dataStatus = (data) => {
    console.log(data);
    if (data.fecha_aprobado) {
        return "Aprobado";
    }
    if (data.fecha_rechazado) {
        return "Rechazado";
    }
    return "Pendiente";
};

const loadingBranches = ref(false);

const loadData = async () => {
    console.log(year.value, weekNumber.value);
    try {
        loading.value = true;
        loadingBranches.value = true;
        const res = await axios.get("/api/compensations/filter-data", {
            params: {
                week: weekNumber.value,
                year: year.value,
                branchOfficeId: selectedBranchOffice.value.id,
                departmentId: departmentId.value,
                employeeId: employeeId.value,
                approved: approved.value,
                // includeEliminated: includeEliminated.value,
            },
        });
        compensations.value = res.data;
        compensations.value = compensations.value.map((item) => {
            return { ...item, status: dataStatus(item) };
        });

        loading.value = false;

        console.log(compensations.value);

        const branchOfficesDB = await axios.get("/branch-offices-user");

        const employeesDB = await axios.get(
            `/api/employee-branchOffices?branchOfficeId=${selectedBranchOffice.value.id}`,
        );
        branchOffices.value = branchOfficesDB.data;
        employees.value = employeesDB.data;
        loadingBranches.value = false;
    } catch (e) {
        console.log(e);
    }
};

onMounted(async () => {
    loadData();
});
</script>
<template>
    <AppLayout :title="'Compensaciones'">
        <Toast position="top-center" group="headless" @close="visible = false">
            <template #container="{ message, closeCallback }">
                <section
                    class="flex flex-col p-4 gap-4 w-full bg-gray-800 rounded-xl"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <i
                                class="pi pi-exclamation-triangle text-2xl text-rose-500"
                                v-if="badHeaders.length > 0"
                            ></i>
                            <span class="font-bold text-base text-white">
                                {{
                                    badHeaders.length > 0
                                        ? "Error al importar"
                                        : message.summary
                                }}
                            </span>
                        </div>

                        <Button
                            icon="pi pi-times"
                            text
                            rounded
                            @click="closeCallback"
                        />
                    </div>

                    <!-- ✅ Cabeceras/campos con error -->
                    <div
                        v-if="badHeaders.length > 0"
                        class="flex flex-col gap-2"
                    >
                        <span
                            class="text-sm font-semibold text-gray-700 dark:text-gray-200"
                        >
                            Campos con error en el archivo:
                        </span>

                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="h in badHeaders"
                                :key="h"
                                class="text-xs font-semibold px-2 py-1 rounded bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200"
                            >
                                {{ h }}
                            </span>
                        </div>

                        <span class="text-xs text-gray-600 dark:text-gray-300">
                            Se encontraron {{ importFailures.length }} errores.
                        </span>
                    </div>
                    <div
                        v-if="
                            progress !== null &&
                            progress !== undefined &&
                            badHeaders.length === 0
                        "
                        class="flex flex-col gap-2"
                    >
                        <ProgressBar
                            :value="progress"
                            :showValue="false"
                            :style="{ height: '4px' }"
                            pt:value:class="!bg-primary-50 dark:!bg-primary-900"
                            class="!bg-primary/80"
                        />
                        <label
                            class="text-sm font-bold text-gray-700 dark:text-white"
                        >
                            {{ progress }}% subido
                        </label>
                    </div>
                </section>
            </template>
        </Toast>
        <div class="card">
            <Toolbar>
                <template #start>
                    <Button
                        type="button"
                        icon="pi pi-download"
                        label="Importar"
                        severity="secondary"
                        class="mt-2 ml-2"
                        @click="openUploadDialog = true"
                        v-if="can('compensations.import')"
                    />
                    <Button
                        type="button"
                        icon="pi pi-upload"
                        label="Exportar"
                        severity="secondary"
                        class="mt-2 ml-2"
                        @click="columnsDialog = true"
                        v-if="can('compensations.export')"
                    />
                </template>

                <template #end>
                    <Button
                        type="button"
                        label="Acciones Masivas"
                        class="min-w-48"
                        icon="pi pi-wrench"
                        @click="toggleAccionesMasivas($event)"
                        :disabled="selected.length === 0"
                        v-if="
                            can('compensations.multiple-approve') ||
                            can('compensations.multiple-reject') ||
                            can('compensations.multiple-delete')
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
                                            @click="
                                                multipleApproveDialog = true
                                            "
                                            :disabled="
                                                disableApproveRejectButton
                                            "
                                            v-if="
                                                can(
                                                    'compensations.multiple-approve',
                                                )
                                            "
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
                                            :disabled="
                                                disableApproveRejectButton
                                            "
                                            v-if="
                                                can(
                                                    'compensations.multiple-reject',
                                                )
                                            "
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
                                            @click="deleteMultipleDialog = true"
                                            v-if="
                                                can(
                                                    'compensations.multiple-delete',
                                                )
                                            "
                                        />
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </Popover>
                    <Button
                        label="Crear"
                        icon="pi pi-plus-circle"
                        severity="success"
                        class="ml-2"
                        @click="
                            router.get(
                                route('compensations.create', {
                                    branchOfficeId: selectedBranchOffice.id,
                                }),
                            )
                        "
                        v-if="can('compensations.create')"
                    />
                </template>
            </Toolbar>
            <DataTable
                ref="dt"
                v-model:selection="selected"
                :value="compensations"
                dataKey="id"
                :paginator="true"
                :rows="10"
                scrollable
                reorderableColumns
                scrollHeight="400px"
                :filterDelay="500"
                v-model:filters="filters"
                filterDisplay="menu"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} compensaciones"
            >
                <template #header>
                    <div
                        class="flex flex-wrap gap-2 items-end justify-between mb-6"
                    >
                        <div>
                            <h4 class="m-0">Compensaciones</h4>
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
                        <div class="mb-2">
                            <Chip
                                :label="
                                    'Planta: ' + otherFilters[0].branchOfficeId
                                "
                                v-if="otherFilters[0].branchOfficeId != null"
                            />
                        </div>
                        <div class="mb-2">
                            <Chip
                                :label="
                                    'Departamento: ' +
                                    otherFilters[0].departmentId
                                "
                                v-if="otherFilters[0].departmentId != null"
                                removable
                                @remove="removeFilter('departmentId')"
                            />
                        </div>
                        <div class="mb-2">
                            <Chip
                                :label="
                                    'Empleado: ' + otherFilters[0].employeeId
                                "
                                v-if="otherFilters[0].employeeId != null"
                                removable
                                @remove="removeFilter('employeeId')"
                            />
                        </div>
                        <div class="mb-2">
                            <Chip
                                :label="`Aprobado: ${
                                    otherFilters[0].approved ? 'Si' : 'No'
                                }`"
                                v-if="otherFilters[0].approved != null"
                                removable
                                @remove="removeFilter('approved')"
                            />
                        </div>
                        <div class="mb-2">
                            <Chip
                                :label="'Semana: ' + otherFilters[0].week"
                                v-if="otherFilters[0].week != null"
                                @remove="removeFilter('week')"
                                :removable="!loading"
                            />
                        </div>
                        <!-- <div class="mb-2">
                            <Chip
                                :label="`Incluir eliminados: ${
                                    otherFilters[0].includeEliminated
                                        ? 'Si'
                                        : 'No'
                                }`"
                                v-if="
                                    otherFilters[0].includeEliminated != null &&
                                    otherFilters[0].includeEliminated == true
                                "
                                removable
                                @remove="removeFilter('includeEliminated')"
                            />
                        </div> -->
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
                    header="Acciones"
                    :frozen="frozenColumns.acciones"
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
                                            `/employee/compensations/${data.id}/edit?branchOfficeId=${branchOfficeId.id}`,
                                        );
                                    }
                                "
                                v-if="
                                    data.aprobado_por == null &&
                                    data.fecha_rechazado == null &&
                                    can('compensations.edit')
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
                                        compensationToDelete.value = data;
                                        deleteDialog = true;
                                        console.log(
                                            compensationToDelete.value.id,
                                        );
                                    }
                                "
                                v-if="can('compensations.delete')"
                            />
                            <Button
                                icon="pi pi-check"
                                severity="success"
                                v-tooltip.top="'Aprobar'"
                                class="mr-2"
                                rounded
                                v-if="
                                    data.aprobado_por == null &&
                                    data.fecha_rechazado == null &&
                                    can('compensations.approve')
                                "
                                @click="
                                    dataApprove = data;
                                    approveDialog = true;
                                "
                            />
                            <Button
                                icon="pi pi-times"
                                severity="danger"
                                v-tooltip.top="'Rechazar'"
                                class="mr-2"
                                rounded
                                v-if="
                                    data.fecha_rechazado == null &&
                                    data.aprobado_por == null &&
                                    can('compensations.reject')
                                "
                                @click="
                                    dataReject = data;
                                    rejectDialog = true;
                                "
                            />
                        </div>
                    </template>
                </Column>
                <Column
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
                <Column
                    field="clave_empleado"
                    header="Clave Empleado"
                    :filter="true"
                    columnKey="clave_empleado"
                    :frozen="frozenColumns.clave_empleado"
                    :style="{
                        width: '5rem',
                        display: showColumns.clave_empleado ? '' : 'none',
                    }"
                    :exportable="exportColumns.clave_empleado"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.clave_empleado }}</span>
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
                    field="nombre_empleado"
                    header="Nombre"
                    :filter="true"
                    columnKey="nombre_empleado"
                    :frozen="frozenColumns.nombre_empleado"
                    :style="{
                        width: '20rem',
                        display: showColumns.nombre_empleado ? '' : 'none',
                    }"
                    :exportable="exportColumns.nombre_empleado"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.nombre_empleado }}</span>
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
                    field="posicion"
                    header="Posición"
                    :frozen="frozenColumns.posicion"
                    columnKey="posicion"
                    :style="{
                        width: '5rem',
                        display: showColumns.posicion ? '' : 'none',
                    }"
                    :exportable="exportColumns.posicion"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.posicion }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Posición"
                        />
                    </template>
                </Column>
                <Column
                    field="departamento"
                    header="Departamento"
                    :frozen="frozenColumns.departamento"
                    columnKey="departamento"
                    :style="{
                        width: '5rem',
                        display: showColumns.departamento ? '' : 'none',
                    }"
                    :exportable="exportColumns.departamento"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.departamento }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Departamento"
                        />
                    </template>
                </Column>
                <Column
                    field="compensacion_puesto"
                    header="Compensacion del puesto"
                    :frozen="frozenColumns.compensacion_puesto"
                    columnKey="compensacion_puesto"
                    :style="{
                        width: '5rem',
                        display: showColumns.compensacion_puesto ? '' : 'none',
                    }"
                    :exportable="exportColumns.compensacion_puesto"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.compensacion_puesto }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Compensacion del Puesto"
                        />
                    </template>
                </Column>
                <Column
                    field="status"
                    header="Estatus"
                    :frozen="frozenColumns.status"
                    columnKey="status"
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
                            :severity="
                                dataStatus(data) === 'Aprobado'
                                    ? 'success'
                                    : dataStatus(data) === 'Rechazado'
                                      ? 'danger'
                                      : 'warning'
                            "
                        />
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Estatus"
                        />
                    </template>
                </Column>
                <Column
                    field="eficiencia"
                    header="Eficiencia"
                    :frozen="frozenColumns.eficiencia"
                    columnKey="eficiencia"
                    :style="{
                        width: '5rem',
                        display: showColumns.eficiencia ? '' : 'none',
                    }"
                    :exportable="exportColumns.eficiencia"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.eficiencia }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Eficiencia"
                        />
                    </template>
                </Column>
                <Column
                    field="compensacion"
                    header="Compensacion"
                    :frozen="frozenColumns.compensacion"
                    columnKey="compensacion"
                    :style="{
                        width: '5rem',
                        display: showColumns.compensacion ? '' : 'none',
                    }"
                    :exportable="exportColumns.compensacion"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.compensacion }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Compensacion"
                        />
                    </template>
                </Column>
                <Column
                    field="piece_work"
                    header="Destajo"
                    :frozen="frozenColumns.destajo"
                    columnKey="destajo"
                    :style="{
                        width: '5rem',
                        display: showColumns.destajo ? '' : 'none',
                    }"
                    :exportable="exportColumns.destajo"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.piece_work }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Destajo"
                        />
                    </template>
                </Column>
                <Column
                    field="extra_compensacion"
                    header="Compensacion Extra"
                    :frozen="frozenColumns.extra_compensacion"
                    columnKey="extra_compensacion"
                    :style="{
                        width: '5rem',
                        display: showColumns.extra_compensacion ? '' : 'none',
                    }"
                    :exportable="exportColumns.extra_compensacion"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.extra_compensacion }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Compensacion Extra"
                        />
                    </template>
                </Column>
                <Column
                    field="apoyo_transporte"
                    header="Apoyo Transporte"
                    :frozen="frozenColumns.apoyo_transporte"
                    columnKey="apoyo_transporte"
                    :style="{
                        width: '5rem',
                        display: showColumns.apoyo_transporte ? '' : 'none',
                    }"
                    :exportable="exportColumns.apoyo_transporte"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.apoyo_transporte }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Apoyo Transporte"
                        />
                    </template>
                </Column>
                <Column
                    field="total"
                    header="Total"
                    :frozen="frozenColumns.total"
                    columnKey="total"
                    :style="{
                        width: '5rem',
                        display: showColumns.total ? '' : 'none',
                    }"
                    :exportable="exportColumns.total"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.total }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Total"
                        />
                    </template>
                </Column>
                <Column
                    field="observaciones"
                    header="Observaciones"
                    :frozen="frozenColumns.observaciones"
                    columnKey="observaciones"
                    :style="{
                        width: '5rem',
                        display: showColumns.observaciones ? '' : 'none',
                    }"
                    :exportable="exportColumns.observaciones"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.observaciones }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Observaciones"
                        />
                    </template>
                </Column>
                <Column
                    field="aprobado_por"
                    header="Aprobado Por"
                    :frozen="frozenColumns.aprobado_por"
                    columnKey="aprobado_por"
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
                            placeholder="Buscar por Aprobado Por"
                        />
                    </template>
                </Column>
                <Column
                    field="fecha_aprobado"
                    header="Fecha Aprobado"
                    :frozen="frozenColumns.fecha_aprobado"
                    columnKey="fecha_aprobado"
                    :style="{
                        width: '5rem',
                        display: showColumns.fecha_aprobado ? '' : 'none',
                    }"
                    :exportable="exportColumns.fecha_aprobado"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.fecha_aprobado }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Fecha Aprobado"
                        />
                    </template>
                </Column>
                <Column
                    field="creado"
                    header="Fecha Creado"
                    :frozen="frozenColumns.fecha_creado"
                    columnKey="fecha_creado"
                    :style="{
                        width: '5rem',
                        display: showColumns.fecha_creado ? '' : 'none',
                    }"
                    :exportable="exportColumns.fecha_creado"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.creado }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Fecha Creado"
                        />
                    </template>
                </Column>
                <Column
                    field="actualizado"
                    header="Fecha Actualizado"
                    :frozen="frozenColumns.fecha_actualizado"
                    columnKey="fecha_actualizado"
                    :style="{
                        width: '5rem',
                        display: showColumns.fecha_actualizado ? '' : 'none',
                    }"
                    :exportable="exportColumns.fecha_actualizado"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.actualizado }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Fecha Actualizado"
                        />
                    </template>
                </Column>
                <!-- <Column
                    field="deleted_at"
                    header="Fecha Eliminado"
                    :frozen="frozenColumns.fecha_eliminado"
                    columnKey="fecha_eliminado"
                    :style="{
                        width: '5rem',
                        display: showColumns.fecha_eliminado ? '' : 'none',
                    }"
                    :exportable="exportColumns.fecha_eliminado"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.deleted_at }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Fecha Eliminado"
                        />
                    </template>
                </Column> -->
            </DataTable>
        </div>
        <Dialog
            v-model:visible="openUploadDialog"
            :style="{ width: '450px' }"
            header="Subir Archivo "
            :modal="true"
        >
            <div class="flex flex-col gap-6">
                <FileUpload
                    name="files[]"
                    accept=".csv,.xlsx,.xls"
                    :maxFileSize="1000000"
                    :customUpload="true"
                    @uploader="onCustomUploader"
                >
                    <template
                        #content="{
                            files,
                            uploadedFiles,
                            removeUploadedFileCallback,
                            removeFileCallback,
                        }"
                    >
                        <div v-if="files.length">
                            <h5>Pendientes</h5>
                            <div class="flex flex-wrap gap-4">
                                <div
                                    v-for="(file, i) in files"
                                    :key="file.name + file.size"
                                    class="p-4 border rounded-md flex flex-col items-center gap-2 w-52"
                                >
                                    <!-- Ícono en lugar de imagen -->
                                    <i
                                        :class="fileIcon(file)"
                                        class="pi text-5xl"
                                    />
                                    <span
                                        class="font-semibold text-ellipsis max-w-44 whitespace-nowrap overflow-hidden"
                                    >
                                        {{ file.name }}
                                    </span>
                                    <small
                                        >{{
                                            (file.size / 1024).toFixed(0)
                                        }}
                                        KB</small
                                    >

                                    <Button
                                        icon="pi pi-times"
                                        @click="removeFileCallback(i)"
                                        rounded
                                        severity="danger"
                                        variant="outlined"
                                    />
                                </div>
                            </div>
                        </div>

                        <div v-if="uploadedFiles.length" class="mt-6">
                            <h5>Completados</h5>
                            <div class="flex flex-wrap gap-4">
                                <div
                                    v-for="(file, i) in uploadedFiles"
                                    :key="file.name + file.size"
                                    class="p-4 border rounded-md flex flex-col items-center gap-2 w-52"
                                >
                                    <i
                                        :class="fileIcon(file)"
                                        class="pi text-5xl"
                                    />
                                    <span
                                        class="font-semibold text-ellipsis max-w-44 whitespace-nowrap overflow-hidden"
                                    >
                                        {{ file.name }}
                                    </span>
                                    <Badge value="OK" severity="success" />
                                    <Button
                                        icon="pi pi-times"
                                        @click="removeUploadedFileCallback(i)"
                                        rounded
                                        severity="danger"
                                        variant="outlined"
                                    />
                                </div>
                            </div>
                        </div>
                    </template>

                    <template #empty>
                        <span>Arrastra y suelta tus CSV o Excel aquí.</span>
                    </template>
                </FileUpload>
            </div>
        </Dialog>
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
                    :loading="submitted"
                />
            </template>
        </Dialog>
        <ConfirmationDialog
            :visible="deleteDialog"
            @confirm="deleteCompensation"
            @cancel="deleteDialog = false"
            :loading="loading"
            header="¿Estas seguro de eliminar esta compensación?"
            confirmOrDelete="delete"
        />
        <ConfirmationDialog
            :visible="approveDialog"
            @confirm="approve"
            @cancel="approveDialog = false"
            :loading="loading"
            header="¿Estas seguro de aprobar esta compensación?"
            confirmOrDelete="confirm"
        />
        <ConfirmationDialog
            :visible="rejectDialog"
            @confirm="reject"
            @cancel="rejectDialog = false"
            :loading="loading"
            header="¿Estas seguro de rechazar esta compensación?"
            confirmOrDelete="delete"
        />
        <ConfirmationDialog
            :visible="rejectDialog"
            @confirm="reject"
            @cancel="rejectDialog = false"
            :loading="loading"
            header="¿Estas seguro de rechazar esta compensación?"
            confirmOrDelete="delete"
        />
        <ConfirmationDialog
            :visible="deleteMultipleDialog"
            @confirm="deleteMultipleCompensations"
            @cancel="deleteMultipleDialog = false"
            :loading="loading"
            header="¿Estas seguro de eliminar las compensaciones
                    seleccionadas?"
            confirmOrDelete="delete"
        />
        <ConfirmationDialog
            :visible="multipleApproveDialog"
            @confirm="approveMultipleCompensations"
            @cancel="multipleApproveDialog = false"
            :loading="loading"
            header="¿Estas seguro de aprobar las compensaciones
                    seleccionadas?"
            confirmOrDelete="confirm"
        />
        <ConfirmationDialog
            :visible="multipleRejectDialog"
            @confirm="rejectMultipleCompensations"
            @cancel="multipleRejectDialog = false"
            :loading="loading"
            header="¿Estas seguro de declinar las compensaciones
                    seleccionadas?"
            confirmOrDelete="delete"
        />
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
                    <Dropdown
                        v-model="branchOfficeIdFilter"
                        :options="branchOffices"
                        optionLabel="code"
                        optionValue="id"
                        showClear
                        placeholder="Selecciona una planta"
                        class="w-full"
                        :loading="loadingBranches"
                    />
                </div>

                <!-- Departamento -->
                <div class="w-full md:w-1/2 px-2 mb-4">
                    <label class="block mb-2 font-medium">Departamento</label>
                    <Dropdown
                        v-model="departmentIdFilter"
                        :options="props.departments"
                        optionLabel="name"
                        optionValue="id"
                        showClear
                        placeholder="Selecciona un departamento"
                        class="w-full"
                    />
                </div>

                <!-- Semana (type=week => AAAA-WSS) -->
                <div class="w-full md:w-1/2 px-2 mb-4">
                    <label class="block mb-2 font-medium">Semana</label>
                    <InputText
                        v-model="weekFilter"
                        type="week"
                        class="w-full"
                    />
                </div>

                <!-- Aprobado (Sí/No) -->
                <div class="w-full md:w-1/2 px-2 mb-4">
                    <label class="block mb-2 font-medium">Aprobado</label>
                    <Dropdown
                        v-model="approvedFilter"
                        :options="approvedOptions"
                        optionLabel="name"
                        optionValue="value"
                        showClear
                        placeholder="Todos"
                        class="w-full"
                    />
                </div>

                <!-- Empleado (buscable) -->
                <div class="w-full md:w-1/2 px-2 mb-4">
                    <label class="block mb-2 font-medium">Empleado</label>
                    <Select
                        v-model="employeeIdFilter"
                        :options="employees"
                        optionValue="id"
                        optionLabel="full_name"
                        filter
                        :filterFields="['id', 'full_name']"
                        showClear
                        placeholder="Selecciona un empleado"
                        class="w-full"
                        :loading="loadingBranches"
                    >
                        <template #option="{ option }">
                            ({{ option.id }}) {{ option.full_name }}
                        </template>
                    </Select>
                </div>

                <!-- Ver registros eliminados -->
                <!-- <div class="w-full md:w-1/2 px-2 mb-4 flex items-end">
                    <div class="flex items-center gap-2">
                        <Checkbox
                            v-model="showDeleted"
                            :binary="true"
                            inputId="show_deleted"
                        />
                        <label for="show_deleted" class="font-medium"
                            >Ver registros eliminados</label
                        >
                    </div>
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
    </AppLayout>
</template>
