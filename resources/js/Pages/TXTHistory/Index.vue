<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { onMounted, ref } from "vue";
import * as xlsx from "xlsx";
import { useToast } from "primevue/usetoast";
import { useAuthz } from "@/composables/useAuthz";
import { router } from "@inertiajs/vue3";
import ConfirmationDialog from "@/Components/ConfirmationDialog.vue";
import { useToastService } from "@/Stores/toastService";

const toast = useToast();
const { showSuccess, showError } = useToastService();

const { can } = useAuthz();

const props = defineProps({
    employees: {
        type: Array,
        required: true,
    },
    departments: {
        type: Array,
        required: true,
    },
    branchOffices: {
        type: Array,
        required: true,
    },
});

const showColumns = ref({
    acciones: true,
    num_empleado: true,
    nombre_empleado: true,
    fecha: true,
    horas: true,
    estatus: true,
});

const exportColumns = ref({
    num_empleado: true,
    nombre_empleado: true,
    fecha: true,
    horas: true,
    estatus: true,
});

const frozenColumns = ref({
    acciones: true,
    num_empleado: true,
    nombre_empleado: false,
    fecha: false,
    horas: false,
    estatus: false,
});

const op = ref();
const opMostrarColumnas = ref();
const opFijarColumnas = ref();
const txts = ref([{}]);
const filters = ref();
const selected = ref([]);
const otherFilterDialog = ref(false);
const loading = ref(false);
const weekNumber = ref(getISOWeek().week);
const year = ref(getISOWeek().year);
const eliminatedFilter = ref(false);
const columnsDialog = ref(false);
const submitted = ref(false);
const dt = ref();
const txtToDelete = ref(null);
const deleteDialog = ref(false);

const departmentFilter = ref();
const employeeFilter = ref();
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
const weekFilter = ref(
    `${year.value}-W${weekNumber.value < 10 ? "0" + weekNumber.value : weekNumber.value}`,
);

const branch_office_id = ref(selectedBranchOffice.value.id);
const branchOfficeFilter = ref(selectedBranchOffice.value.id);

const employeesByBranchOffice = ref(
    props.employees.filter(
        (employee) =>
            employee.branch_office_id === selectedBranchOffice.value.id,
    ),
);

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

const otherFilters = ref([
    {
        employee_id: null,
        week: `${year.value}-W${String(weekNumber.value).padStart(2, "0")}`,
        includeEliminated: false,
        branch_office_id: selectedBranchOffice.value.code,
        department: null,
    },
]);

const getStatusLabel = (approved_at, declined_at, validated_at, hours) => {
    console.log(hours);
    if (parseFloat(hours) < 0) {
        return "Horas descontadas";
    }
    if (approved_at) {
        return "Aprobado";
    }
    if (validated_at) {
        return "Validado";
    }
    if (declined_at) {
        return "Rechazado";
    }
    return "Pendiente";
};

const getStatusSeverity = (approved_at, declined_at, validated_at, hours) => {
    if (parseFloat(hours) < 0) {
        return "info";
    }
    if (approved_at) {
        return "success";
    }
    if (validated_at) {
        return "info";
    }
    if (declined_at) {
        return "danger";
    }
    return "warning";
};

const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        employee_id: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        employee_name: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        status: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        date: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        hours: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
    };
};

const toggleAccionesMasivas = (event) => {
    op.value.toggle(event);
};

const toggleMostrarColumnas = (event) => {
    opMostrarColumnas.value.toggle(event);
};

const toggleFijarColumnas = (event) => {
    opFijarColumnas.value.toggle(event);
};

const clearFilter = () => {
    initFilters();
};

initFilters();

const applyFilters = async () => {
    loading.value = true;
    otherFilterDialog.value = false;
    const selectedBranchOffice = props.branchOffices.find(
        (branchOffice) => branchOffice.id === branchOfficeFilter.value,
    );

    const departmentName = props.departments.find(
        (department) => department.id === departmentFilter.value,
    );

    otherFilters.value[0].employee_id = employeeFilter.value;
    otherFilters.value[0].week = weekFilter.value;
    otherFilters.value[0].includeEliminated = eliminatedFilter.value;
    otherFilters.value[0].branch_office_id = selectedBranchOffice?.code;
    otherFilters.value[0].department = departmentName?.name;

    let year = null;
    let week = null;

    if (otherFilters.value[0].week != null) {
        [year, week] = otherFilters.value[0].week.split("-W");
    }

    // console.log(
    //     selectedBranchOffice?.id,
    //     week,
    //     year,
    //     employeeFilter.value,
    //     departmentFilter.value,
    //     eliminatedFilter.value,
    // );

    await axios
        .get("/api/getTxtHistory", {
            params: {
                branch_office_id: selectedBranchOffice?.id,
                week: week,
                year: year,
                employee_id: employeeFilter.value,
                department_id: departmentFilter.value,
                eliminated: eliminatedFilter.value,
            },

        })
        .then((response) => {
            console.log(response);
            txts.value = response.data;
            txts.value = response.data.map((txt) => ({
                ...txt,
                status: getStatusLabel(
                    txt.approved_at,
                    txt.declined_at,
                    txt.validated_at,
                    txt.hours,
                ),
            }));
        })
        .catch((error) => {
            console.error(error);
        })
        .finally(() => {
            loading.value = false;
        });
};

const removeFilter = (filter) => {
    otherFilters.value[0][filter] = null;
    switch (filter) {
        case "branch_office_id":
            branchOfficeFilter.value = null;
            break;
        case "employee_id":
            employeeFilter.value = null;
            break;
        case "week":
            weekFilter.value = null;
            break;
        case "includeEliminated":
            eliminatedFilter.value = null;
            break;
        case "department":
            departmentFilter.value = null;
            break;
    }
    applyFilters();
};

const userBranchOffices = ref([]);
const getTxtHistory = async () => {
    loading.value = true;
    await axios
        .get("/api/getTxtHistory", {
            params: {
                branch_office_id: branch_office_id.value,
                week: weekNumber.value,
                year: year.value,
                eliminated: false,
            },
        })
        .then((response) => {
            txts.value = response.data;
            txts.value = response.data.map((txt) => ({
                ...txt,
                status: getStatusLabel(
                    txt.approved_at,
                    txt.declined_at,
                    txt.validated_at,
                    txt.hours,
                ),
            }));
        });

    await axios
        .get("/branch-offices-user")
        .then((response) => {
            userBranchOffices.value = response.data;
        })
        .finally(() => {
            loading.value = false;
        });
};

const columnMap = {
    num_empleado: { field: "employee_id", header: "ID Empleado" },
    estatus: { field: "status", header: "Estado" },
    fecha: { field: "date", header: "Fecha" },
    horas: { field: "hours", header: "Horas TXT" },
    nombre_empleado: { field: "employee_name", header: "Nombre Empleado" },
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
            : txts.value;

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
    xlsx.utils.book_append_sheet(workbook, worksheet, "Historial TXT");
    xlsx.writeFile(workbook, "Historial_TXT.xlsx");

    columnsDialog.value = false;
};

const exportHistoryExcel = () => {
    loading.value = true;
    axios
        .get("/api/getTxtHistoryExcel", {
            params: {
                branch_office_id: branch_office_id.value,
            },
        })
        .then((response) => {
            const txts = response.data;
            const dataToExport = txts.map((txt) => ({
                num_empleado: txt.employee_id,
                nombre_empleado: txt.employee,
                horas_positivas: txt.horas_positivas,
                horas_negativas: txt.horas_negativas,
                resultado: txt.resultado,
            }));
            const worksheet = xlsx.utils.json_to_sheet(dataToExport);
            const workbook = xlsx.utils.book_new();
            xlsx.utils.book_append_sheet(workbook, worksheet, "Historico TXT");
            xlsx.writeFile(workbook, "Historico_TXT.xlsx");
        })
        .finally(() => {
            loading.value = false;
        });

    columnsDialog.value = false;
};

const editTXT = (id) => {
    router.get(route("txt-history.edit", id));
};

const createTXT = () => {
    router.get(route("txt-history.create"));
};

const deleteTXT = () => {
    loading.value = true;
    console.log(txtToDelete.value);
    router.delete(route("txt-history.delete", txtToDelete.value), {
        onSuccess: () => {
            showSuccess();
            getTxtHistory();
        },
        onError: () => {
            showError();
        },
        onFinish: () => {
            loading.value = false;
            deleteDialog.value = false;
        },
    });
};

onMounted(() => {
    getTxtHistory();
});
</script>

<template>
    <AppLayout title="Historial de TXT">
        <div class="card">
            <Toolbar>
                <template #start>
                    <Button
                        type="button"
                        icon="pi pi-upload"
                        label="Exportar"
                        severity="secondary"
                        class="mt-2 ml-2"
                        @click="columnsDialog = true"
                        v-if="can('txt-history.export')"
                    />
                    <Button
                        type="button"
                        icon="pi pi-file-excel"
                        label="Exportar Historico"
                        severity="contrast"
                        class="mt-2 ml-2"
                        @click="exportHistoryExcel()"
                        :loading="loading"
                        :disabled="loading"
                        v-if="can('txt-history.export')"
                    />
                </template>
                <template #end>
                    <Button
                        type="button"
                        icon="pi pi-plus-circle"
                        label="Crear"
                        severity="success"
                        class="mt-2 ml-2"
                        @click="createTXT()"
                        v-if="can('txt-history.create')"
                    />
                </template>
            </Toolbar>
            <DataTable
                ref="dt"
                v-model:selection="selected"
                :value="txts"
                dataKey="id"
                :paginator="true"
                :rows="10"
                scrollable
                reorderableColumns
                scrollHeight="400px"
                :filterDelay="500"
                v-model:filters="filters"
                filterDisplay="menu"
                exportFilename="historial_txt"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} tiempos por tiempo"
            >
                <template #header>
                    <div
                        class="flex flex-wrap gap-2 items-end justify-between mb-6"
                    >
                        <div>
                            <h4 class="m-0">Historial de Tiempo por Tiempo</h4>
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
                                    'Planta: ' +
                                    otherFilters[0].branch_office_id
                                "
                                v-if="otherFilters[0].branch_office_id != null"
                            />
                        </div>
                        <div class="mb-2">
                            <Chip
                                :label="
                                    'Empleado: ' + otherFilters[0].employee_id
                                "
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
                                    otherFilters[0].includeEliminated
                                        ? 'Si'
                                        : 'No'
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
                    </div>
                </template>
                <Column
                    :exportable="false"
                    columnKey="acciones"
                    :style="{
                        width: '10rem',
                        display: showColumns.acciones ? '' : 'none',
                        minWidth: '10rem',
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
                                @click="editTXT(data.id)"
                                v-if="can('txt-history.edit')"
                            />
                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                v-tooltip.top="'Eliminar'"
                                class="mr-2"
                                rounded
                                @click="
                                    () => {
                                        txtToDelete = data.id;
                                        deleteDialog = true;
                                    }
                                "
                                v-if="
                                    data.deleted_by == null &&
                                    can('txt-history.delete')
                                "
                            />
                        </div>
                    </template>
                </Column>
                <Column
                    field="employee_id"
                    header="Clave Empleado"
                    :filter="true"
                    columnKey="num_empleado"
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
                            placeholder="Buscar por Clave Empleado"
                        />
                    </template>
                </Column>
                <Column
                    field="employee_name"
                    header="Nombre Empleado"
                    :filter="true"
                    columnKey="nombre_empleado"
                    :frozen="frozenColumns.nombre_empleado"
                    :style="{
                        width: '5rem',
                        display: showColumns.nombre_empleado ? '' : 'none',
                    }"
                    :exportable="exportColumns.nombre_empleado"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.employee_name }}</span>
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
                    header="Estado"
                    :filter="true"
                    columnKey="estatus"
                    :frozen="frozenColumns.estatus"
                    :style="{
                        width: '5rem',
                        display: showColumns.estatus ? '' : 'none',
                    }"
                    :exportable="exportColumns.estatus"
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
                                        data.hours,
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
                                { label: 'Validado', value: 'Validado' },
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
                    field="date"
                    header="Fecha"
                    :filter="true"
                    columnKey="fecha"
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
                            class="border-none w-full"
                            @update:modelValue="
                                (val) =>
                                    (filterModel.value = transformDate(val))
                            "
                        />
                    </template>
                </Column>

                <Column
                    field="hours"
                    header="Horas TXT"
                    :filter="true"
                    columnKey="horas"
                    :frozen="frozenColumns.horas"
                    :style="{
                        width: '5rem',
                        display: showColumns.horas ? '' : 'none',
                    }"
                    :exportable="exportColumns.horas"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.hours }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Horas TXT"
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
                            :options="userBranchOffices"
                            optionLabel="code"
                            optionValue="id"
                            placeholder="Selecciona una planta"
                            class="w-full"
                            filter
                            filterBy="code"
                            :loading="loading"
                        />
                    </div>

                    <!-- Departamento -->
                    <div class="w-full md:w-1/2 px-2 mb-4">
                        <label class="block mb-2 font-medium"
                            >Departamento</label
                        >
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
                        <InputText
                            v-model="weekFilter"
                            type="week"
                            class="w-full"
                        />
                    </div>

                    <!-- Empleado (buscable)-->
                    <div class="w-full md:w-1/2 px-2 mb-4">
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
            <ConfirmationDialog
                :visible="deleteDialog"
                :header="'Eliminar registro'"
                :loading="loading"
                @confirm="deleteTXT"
                @cancel="deleteDialog = false"
                :confirmOrDelete="'delete'"
            />
        </div>
    </AppLayout>
</template>
