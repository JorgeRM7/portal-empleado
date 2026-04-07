<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToastService } from "@/Stores/toastService";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import { ref, reactive, onMounted } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { useNotifications } from "@/composables/useNotifications";
import { computed } from 'vue'
import { useAuthz } from "@/composables/useAuthz";
const { can } = useAuthz();

/* =====================
PROPS
===================== */
const props = defineProps({
    branchOffices: Array,
});

/* =====================
ESTADO GENERAL
===================== */
const loading = ref(false);
const movimientos = ref([]);
const employees = ref([]);
// const dias_disfrute = ref([]);
const dias_disfrute = ref(0);
const showConfirmDelete = ref(false);
const rowToDelete = ref(null);
const deleting = ref(false);


//Referencias a los popovers
const opMostrarColumnas = ref();
const opFijarColumnas = ref();

// Variables de la paginación de la tabla
const first = ref(0);
const rows = ref(10);
const onPage = (event) => {
    first.value = event.first;
    rows.value = event.rows;
};

// Variables para crear dias x empleado
const empleadoId = ref(null);
const dias = ref(null);
const antiguedad = ref(null);
const fecha = ref(null);

// variables para editar dias x empleado
const editarDiasVacaciones = ref(false);
const registroEditando = ref(null);

/* =====================
DATATABLE
===================== */
const dt = ref(null);
const selected = ref([]);

/* =====================
FILTROS DE TABLA
===================== */
const filters = ref({});

const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        clave_empleado: { value: null, matchMode: FilterMatchMode.CONTAINS },
        nombre_empleado: { value: null, matchMode: FilterMatchMode.CONTAINS },
        dias_disfrute: { value: null, matchMode: FilterMatchMode.CONTAINS },
        antiguedad: { value: null, matchMode: FilterMatchMode.CONTAINS },
        fecha: { value: null, matchMode: FilterMatchMode.CONTAINS },
        // eliminado: { value: null, matchMode: FilterMatchMode.CONTAINS },
    };
};

initFilters();

const hasActiveFilters = computed(() => {
    return (
        !!activeFilters.branchOffice ||
        activeFilters.employee.length > 0
        // activeFilters.includeDeleted
    )
})

/* =====================
UI STATE (DIALOGS)
===================== */
const columnsDialog = ref(false);
const otherFilterDialog = ref(false);
const openUploadDialog = ref(false);
const submitted = ref(false);
const crearDiasVacaciones = ref(false);

/* =====================
COLUMNAS
===================== */
const showColumns = reactive({
    acciones: true,
    clave_empleado: true,
    nombre_empleado: true,
    dias_disfrute: true,
    antiguedad: true,
    fecha: true,
    // eliminado: true,
});

const frozenColumns = reactive({
    clave_empleado: false,
    nombre_empleado: false,
    dias_disfrute: false,
    antiguedad: false,
    fecha: false,
    // eliminado: false,
});

const exportColumns = reactive({
    clave_empleado: true,
    nombre_empleado: true,
    dias_disfrute: true,
    antiguedad: true,
    fecha: true,
    // eliminado: true,
});

//Funciones para alternar los popovers
const toggleMostrarColumnas = (event) => {
    opMostrarColumnas.value.toggle(event);
};

const toggleFijarColumnas = (event) => {
    opFijarColumnas.value.toggle(event);
};

//Función para mostrar toast de éxito y error
const { showSuccess, showError } = useToastService();

//Función para guardar las columnas seleccionadas para exportar
const saveColumns = () => {
    columnsDialog.value = false;

    if (!selected.value.length) {
        showError('Selecciona al menos un registro');
        return;
    }

    dt.value.exportCSV({
        selectionOnly: true,
        fileName: 'vacations_x_employees'
    });
};

/* =====================
FILTROS UI
===================== */
const selectedBranchOffice = ref(null);
const selectedEmployees = ref([]);
// const includeDeleted = ref(false);
const filteredBranchOffices = ref([]);

/* =====================
FILTROS ACTIVOS (CHIPS)
===================== */
const activeFilters = reactive({
    branchOffice: null,
    employee: [],
    // includeDeleted: false,
});

/* =====================
REMOVE FILTER
===================== */
const removeFilter = (type, { reload = false } = {}) => {
    switch (type) {
        case 'branchOffice':
            selectedBranchOffice.value = null;
            activeFilters.branchOffice = null;
            break;

        case 'employee':
            selectedEmployees.value = []
            activeFilters.employee = []
            break

        // case 'includeDeleted':
        //     includeDeleted.value = false
        //     activeFilters.includeDeleted = false
        //     break
    }

    loadVacationsxEmployee({
        branchOfficeId: selectedBranchOffice.value?.id ?? null,
        employeeIds: selectedEmployees.value.map(e => e.id),
        // includeDeleted: includeDeleted.value
    })
}

/* =====================
LOAD SALDOS
===================== */
const loadVacationsxEmployee = async (params = {}) => {

    const storedBranchId = localStorage.getItem("selectedBranchOfficeId");
    const branchId = params.branchOfficeId || storedBranchId || null;
    loading.value = true;
    // console.log(branchId);
    try {

        const response = await axios.get("/employee/vacations-per-employee/filter-vacationsperEmployee", {
            params: {
                branch_office_id: branchId,
                employees: params.employeeIds ?? [],
            },
        });

        movimientos.value = response.data.data ?? [];
        console.log(movimientos.value);
        employees.value = response.data.employees ?? [];

    } finally {
        loading.value = false;
    }
};
/* =====================
APLICAR FILTROS
===================== */
const applyFilters = () => {

    const empIds = selectedEmployees.value.map(e =>
        (e && typeof e === 'object') ? e.id : e
    );
    activeFilters.branchOffice = selectedBranchOffice.value;
    activeFilters.employee = [...selectedEmployees.value];
    // activeFilters.includeDeleted = includeDeleted.value;

    if (selectedBranchOffice.value) {
        localStorage.setItem(
            "selectedBranchOffice",
            JSON.stringify(selectedBranchOffice.value)
        );
    }

    loadVacationsxEmployee({
        branchOfficeId: selectedBranchOffice.value?.id ?? null,
        employeeIds: empIds,
        // includeDeleted: includeDeleted.value,
    });

    otherFilterDialog.value = false;
};


/* =====================
CREAR DIAS VACACIONES
===================== */
const createDays = async () => {
    submitted.value = true;

    try {
        const response = await axios.post("/employee/vacations-per-employee", {
            employee_id: empleadoId.value,
            amount: dias.value,
            seniority: antiguedad.value,
            date: fecha.value
                ? fecha.value.toISOString().slice(0, 10)
                : null,
        });

        showSuccess("Días agregados correctamente");

        crearDiasVacaciones.value = false;

        empleadoId.value = null;
        dias.value = null;
        antiguedad.value = null;
        fecha.value = null;

        applyFilters();

    } catch (error) {
        console.error(error.response?.data);
        showError("Revisa los campos obligatorios");
    } finally {
        submitted.value = false;
    }
};

/* =====================
EDITAR DIAS VACACIONES
===================== */
const editarRegistro = (row) => {
    registroEditando.value = { ...row };

    empleadoId.value = Number(row.clave_empleado);
    dias.value = row.dias_disfrute;
    antiguedad.value = row.antiguedad;
    fecha.value = row.fecha ? new Date(row.fecha) : null;

    editarDiasVacaciones.value = true;
    // console.log(registroEditando.value);
};

/* =====================
EDITAR DIAS VACACIONES
===================== */
const updateDays = async () => {
    submitted.value = true;

    try {
        const response = axios.put(`/employee/vacations-per-employee/${registroEditando.value.id_movimiento}`,
            {
                employee_id: empleadoId.value,
                amount: dias.value,
                seniority: antiguedad.value,
                date: fecha.value
                    ? fecha.value.toISOString().slice(0, 10)
                    : null,
            }
        );

        showSuccess("Registro actualizado correctamente");
        editarDiasVacaciones.value = false;
        applyFilters();

    } catch (error) {
        console.error(error);
        showError("Error al actualizar el registro");
    } finally {
        submitted.value = false;
    }
};

/* =====================
ELIMINAR REGISTRO
===================== */
const eliminarRegistro = (row) => {
    rowToDelete.value = row;
    showConfirmDelete.value = true;
};

const confirmDelete = async () => {
    deleting.value = true;

    try {
        await axios.delete(
            `/employee/vacations-per-employee/${rowToDelete.value.id_movimiento}`
        );

        showSuccess("Registro eliminado correctamente");
        showConfirmDelete.value = false;
        applyFilters();
    } catch (e) {
        showError("Error al eliminar el registro");
    } finally {
        deleting.value = false;
    }
};

/* =====================
LIMPIAR FILTROS
===================== */
const clearFilter = () => {
    initFilters();

    const currentBranch = selectedBranchOffice.value;

    selectedEmployees.value = [];
    // includeDeleted.value = false;

    activeFilters.employee = [];
    // activeFilters.includeDeleted = false;

    selectedBranchOffice.value = currentBranch;
    activeFilters.branchOffice = currentBranch;

    loadVacationsxEmployee({
        branchOfficeId: currentBranch?.id ?? null
    });
};

/* =====================
TOTAL DATOS ENLISTADOS
===================== */

const filteredData = ref([]);

const onFilter = (event) => {
    filteredData.value = event.filteredValue;
};

// const totalDiasPagina = computed(() => {
//     // Usamos los datos filtrados si existen, si no, los originales
//     const dataToSum = dt.value?.processedData || movimientos.value;

//     const start = first.value;
//     const end = start + rows.value;

//     return dataToSum
//         .slice(start, end)
//         .reduce((sum, item) => sum + Number(item.dias_disfrute || 0), 0);
// });

const totalDiasPagina = computed(() => {

    const data = dt.value?.processedData || movimientos.value;

    const start = first.value;
    const end = start + rows.value;

    return data
        .slice(start, end)
        .reduce((sum, item) => sum + Number(item.dias_disfrute || 0), 0);
});

const totalRegistros = computed(() => {
    return dt.value?.processedData?.length || movimientos.value.length;
});

const dias_disfrute_total = computed(() => {

    const data = dt.value?.processedData || movimientos.value;

    return data.reduce((total, row) => {
        return total + Number(row.dias_disfrute || 0);
    }, 0);

});

const totalAmount = computed(() => {

    const data = dt.value?.processedData || movimientos.value;

    return data.reduce((total, row) => {
        return total + Number(row.amount || 0);
    }, 0);

});

const totalAmountPagina = computed(() => {

    const data = dt.value?.processedData || movimientos.value;

    const start = first.value;
    const end = start + rows.value;

    return data
        .slice(start, end)
        .reduce((sum, item) => sum + Number(item.amount || 0), 0);

});

/* =====================
MOUNTED
===================== */
onMounted(() => {
    filteredBranchOffices.value = props.branchOffices ?? [];

    const storedBranch = localStorage.getItem("selectedBranchOffice");

    if (storedBranch) {

        const stored = JSON.parse(storedBranch);

        const branch = props.branchOffices.find(
            b => b.id === stored.id
        );

        if (branch) {

            selectedBranchOffice.value = branch;
            activeFilters.branchOffice = branch;

            loadVacationsxEmployee({
                branchOfficeId: branch.id
            });

            return;
        }
    }

    loadVacationsxEmployee();
});

</script>

<template>
    <!-- {{ branchOffices }} -->
    <AppLayout :title="'Vacaciones por empleados'">
        <Toast position="top-center" group="headless" @close="visible = false">
            <template #container="{ message, closeCallback }">
                <section
                    class="flex flex-col p-4 gap-4 w-full bg-gray-100 dark:bg-gray-800 rounded-xl"
                >
                    <div class="flex items-center gap-5">
                        <i class="pi pi-cloud-upload text-2xl text-white"></i>
                        <span class="font-bold text-base text-white">{{
                            message.summary
                        }}</span>
                    </div>
                    <div class="flex flex-col gap-2">
                        <ProgressBar
                            :value="progress"
                            :showValue="false"
                            :style="{ height: '4px' }"
                            pt:value:class="!bg-primary-50 dark:!bg-primary-900"
                            class="!bg-primary/80"
                        ></ProgressBar>
                        <label class="text-sm font-bold text-white"
                            >{{ progress }}% subido</label
                        >
                    </div>
                </section>
            </template>
        </Toast>
        <div class="card">
            <Toolbar>
                <template #start>
                    <Button
                        v-if="can('vacations-per-employee.export')"
                        type="button"
                        icon="pi pi-upload"
                        label="Exportar"
                        severity="secondary"
                        class="mt-2 ml-2"
                        @click="columnsDialog = true"
                    />
                </template>

                <template #end>
                    <Button
                        v-if="can('vacations-per-employee.create')"
                        label="Crear"
                        icon="pi pi-plus-circle"
                        severity="success"
                        class="ml-2"
                        @click="crearDiasVacaciones = true"
                    />
                </template>
            </Toolbar>
            <DataTable
                ref="dt"
                @filter="onFilter"
                v-model:selection="selected"
                :value="movimientos"
                dataKey="id"
                :paginator="true"
                :rows="rows"
                :first="first"
                @page="onPage"
                scrollable
                scrollHeight="400px"
                tableStyle="min-width: 110rem"
                v-model:filters="filters"
                filterDisplay="menu"
                exportFilename="vacations_x_employees"
                :globalFilterFields="[
                    'employee_id',
                    'full_name',
                    'amount',
                    'seniority',
                    'date'
                ]"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} datos de vacaciones por empleados"

            >
                <template #header>
                    <div
                        class="flex flex-wrap gap-2 items-end justify-between mb-6"
                    >
                        <div>
                            <h4 class="m-0">Vacaciones por Empleado</h4>
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
                                    v-model="filters['global'].value"
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
                    <div class="mb-2">
                        <Chip
                            v-if="activeFilters.branchOffice"
                            :label="`Planta: ${activeFilters.branchOffice.code}`"
                            @remove="removeFilter('branchOffice')"
                        />

                        <Chip
                            v-if="activeFilters.employee?.length"
                            :label="`Empleados: ${activeFilters.employee.map(id => {
                                const emp = employees.find(e => e.id === id);
                                return emp ? emp.full_name : 'Cargando...';
                            }).join(', ')}`"
                            removable
                            @remove="removeFilter('employee')"
                        />

                        <!-- <Chip
                            v-if="activeFilters.includeDeleted"
                            label="Incluir eliminados"
                            removable
                            @remove="removeFilter('includeDeleted')"
                        /> -->
                    </div>
                </template>

                <Column
                    selectionMode="multiple"
                    style="width: 5rem"
                    :exportable="false"
                ></Column>
                <Column
                    :exportable="false"
                    :style="{
                        width: '22rem',
                        display: showColumns.acciones ? '' : 'none',
                    }"
                    header="Acciones"
                    :frozen="frozenColumns.acciones"
                >
                    <template #body="slotProps">
                        <Skeleton v-if="loading"></Skeleton>
                        <div v-else>
                            <Button
                                v-if="can('vacations-per-employee.edit')"
                                icon="pi pi-pencil"
                                class="mr-2"
                                rounded
                                severity="warn"
                                v-tooltip.top="'Editar'"
                                @click="editarRegistro(slotProps.data)"
                            />
                            <Button
                                v-if="can('vacations-per-employee.delete')"
                                icon="pi pi-trash"
                                severity="danger"
                                v-tooltip.top="'Eliminar'"
                                class="mr-2"
                                rounded
                                @click="eliminarRegistro(slotProps.data)"
                            />
                        </div>
                    </template>
                </Column>
                <Column
                    field="employee_id"
                    header="Clave Empleado"
                    :filter="true"
                    :frozen="frozenColumns.clave_empleado"
                    :style="{
                        width: '20rem',
                        display: showColumns.clave_empleado ? '' : 'none',
                    }"
                    :exportable="exportColumns.clave_empleado"
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
                    :frozen="frozenColumns.nombre_empleado"
                    :style="{
                        width: '20rem',
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
                    field="amount"
                    header="Días Disfrute"
                    :frozen="frozenColumns.dias_disfrute"
                    sortable
                    :style="{
                        width: '20rem',
                        display: showColumns.dias_disfrute ? '' : 'none',
                    }"
                    :exportable="exportColumns.dias_disfrute"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.amount }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Días Disfrute"
                        />
                    </template>
                </Column>

                <Column
                    field="seniority"
                    header="Antiguedad"
                    sortable
                    :frozen="frozenColumns.antiguedad"
                    :style="{
                        width: '20rem',
                        display: showColumns.antiguedad ? '' : 'none',
                    }"
                    :exportable="exportColumns.antiguedad"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.seniority }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Antiguedad"
                        /> </template
                ></Column>
                <Column
                    field="date"
                    header="Fecha"
                    sortable
                    :frozen="frozenColumns.fecha"
                    :style="{
                        width: '20rem',
                        display: showColumns.fecha ? '' : 'none',
                    }"
                    :exportable="exportColumns.fecha"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ new Date(data.date).toLocaleDateString('es-ES') }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Fecha"
                        /> </template
                ></Column>
                <!-- <Column
                    field="eliminado"
                    header="Eliminado"
                    sortable
                    :frozen="frozenColumns.eliminado"
                    :style="{
                        width: '20rem',
                        display: showColumns.eliminado ? '' : 'none',
                    }"
                    :exportable="exportColumns.eliminado"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.eliminado }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Eliminado"
                        /> </template
                ></Column> -->
            </DataTable>
            <!-- <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="p-4 rounded-lg bg-surface-100 dark:bg-surface-800 text-center">
                    <span class="block text-lg font-medium text-gray-500">Días de disfrute</span>
                    <span class="text-2xl font-bold">{{ dias_disfrute_total  }}</span>
                </div>

                <div class="p-4 rounded-lg bg-surface-100 dark:bg-surface-800 text-center">
                    <span class="block text-lg font-medium text-gray-500">Total por página</span>
                    <span class="text-2xl font-bold">{{ totalDiasPagina }}</span>
                </div>
            </div> -->

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">

                <div class="p-4 rounded-lg bg-surface-100 dark:bg-surface-800 text-center">
                    <span class="block text-lg font-medium text-gray-500">
                        Total registros
                    </span>
                    <span class="text-2xl font-bold">
                        {{ totalRegistros }}
                    </span>
                </div>

                <div class="p-4 rounded-lg bg-surface-100 dark:bg-surface-800 text-center">
                    <span class="block text-lg font-medium text-gray-500">
                        Total días
                    </span>
                    <span class="text-2xl font-bold">
                        {{ totalAmount }}
                    </span>
                </div>

                <div class="p-4 rounded-lg bg-surface-100 dark:bg-surface-800 text-center">
                    <span class="block text-lg font-medium text-gray-500">
                        Total por página
                    </span>
                    <span class="text-2xl font-bold">
                        {{ totalAmountPagina }}
                    </span>
                </div>

            </div>
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
            <Dialog
                v-model:visible="otherFilterDialog"
                :style="{ width: '450px' }"
                header="Seleccionar filtros adicionales"
                :modal="true"
            >
                <div class="flex flex-col gap-5">

                    <!-- FILTROS ACTIVOS -->
                    <div v-show="hasActiveFilters"
                        class="p-3 rounded-lg bg-surface-100 dark:bg-surface-800">

                        <span class="text-sm font-semibold block mb-2"></span>
                    </div>

                    <!-- PLANTA -->
                    <FloatLabel variant="on" class="flex-1">
                        <Dropdown
                            v-model="selectedBranchOffice"
                            :options="props.branchOffices"
                            optionLabel="code"
                            class="w-full"
                        />
                        <label>Plantas</label>
                    </FloatLabel>

                    <!-- EMPLEADOS -->
                    <FloatLabel variant="on" class="flex-1">

                        <Multiselect
                            v-model="selectedEmployees"
                            :options="employees"
                            optionLabel="full_name"
                            optionValue="id"
                            filter
                            :filterFields="['full_name', 'id']"
                            placeholder="Selecciona un empleado"
                            class="w-full"
                            display="chip"
                        >

                            <template #value="slotProps">
                                <span v-if="!slotProps.value || slotProps.value.length === 0">
                                    Selecciona un empleado
                                </span>

                                <span v-else-if="slotProps.value.length > 5">
                                    {{ slotProps.value.length }} empleados seleccionados
                                </span>


                            </template>

                            <template #option="{ option }">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-gray-700">({{ option.id }})</span>
                                    <span>{{ option.full_name }}</span>
                                </div>
                            </template>

                        </Multiselect>
                        <label>Empleados</label>
                    </FloatLabel>

                    <!-- <div class="flex items-center gap-2">
                        <Checkbox v-model="includeDeleted" binary />
                        <label>Incluir eliminados</label>
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
                v-model:visible="crearDiasVacaciones"
                :style="{ width: '450px' }"
                header="Días de Vacaciones por Empleado"
                :modal="true"
            >
                <div class="flex flex-col gap-5">

                    <!-- FILTROS ACTIVOS -->
                    <div v-show="hasActiveFilters"
                        class="p-3 rounded-lg bg-surface-100 dark:bg-surface-800">

                        <span class="text-sm font-semibold block mb-2"></span>
                    </div>

                    <!-- EMPLEADOS -->
                    <Select
                        v-model="empleadoId"
                        :options="employees"
                        optionLabel="full_name"
                        optionValue="id"
                        filter
                        :filterFields="['full_name', 'id']"
                        placeholder="Selecciona un empleado"
                        class="w-full"
                    >
                        <template #value="slotProps">
                            <div v-if="slotProps.value" class="flex items-center gap-2">
                                <span>{{ employees.find(e => e.id === slotProps.value)?.full_name }}</span>
                            </div>
                            <span v-else>
                                {{ slotProps.placeholder }}
                            </span>
                        </template>

                        <template #option="{ option }">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-gray-700">({{ option.id }})</span>
                                <span>{{ option.full_name }}</span>
                            </div>
                        </template>
                    </Select>
                    <DatePicker v-model="fecha" showIcon fluid iconDisplay="input" placeholder="Seleccione una fecha"/>
                    <div class="flex gap-3">
                        <InputNumber
                            v-model="dias"
                            inputId="minmax-buttons-1"
                            mode="decimal"
                            showButtons
                            :min="0"
                            :max="100"
                            fluid
                            placeholder="Monto"
                        />
                        <InputNumber
                            v-model="antiguedad"
                            inputId="minmax-buttons-2"
                            mode="decimal"
                            showButtons
                            :min="0"
                            :max="100"
                            fluid
                            placeholder="Antiguedad"
                        />
                    </div>
                </div>

                <template #footer>
                    <Button
                        label="Cancelar"
                        icon="pi pi-times"
                        severity="danger"
                        @click="crearDiasVacaciones = false"
                    />
                    <Button
                        label="Guardar"
                        icon="pi pi-save"
                        severity="success"
                        @click="createDays"
                        :loading="submitted"
                    />
                </template>
            </Dialog>
            <Dialog
                v-model:visible="editarDiasVacaciones"
                :style="{ width: '450px' }"
                header="Editar días de vacaciones"
                modal
            >
                <div class="flex flex-col gap-5">

                    <!-- FILTROS ACTIVOS -->
                    <div v-show="hasActiveFilters"
                        class="p-3 rounded-lg bg-surface-100 dark:bg-surface-800">

                        <span class="text-sm font-semibold block mb-2"></span>
                    </div>

                    <!-- EMPLEADOS -->
                    <Select
                        v-model="empleadoId"
                        :options="employees"
                        optionLabel="full_name"
                        optionValue="id"
                        filter
                        :filterFields="['full_name', 'id']"
                        placeholder="Selecciona un empleado"
                        class="w-full"
                    >
                        <template #value="slotProps">
                            <div v-if="slotProps.value" class="flex items-center gap-2">
                                <span>{{ employees.find(e => e.id === slotProps.value)?.full_name }}</span>
                            </div>
                            <span v-else>
                                {{ slotProps.placeholder }}
                            </span>
                        </template>

                        <template #option="{ option }">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-gray-700">({{ option.id }})</span>
                                <span>{{ option.full_name }}</span>
                            </div>
                        </template>
                    </Select>
                    <DatePicker v-model="fecha" showIcon fluid iconDisplay="input" placeholder="Seleccione una fecha"/>
                    <div class="flex gap-3">
                        <InputNumber
                            v-model="dias"
                            inputId="minmax-buttons-1"
                            mode="decimal"
                            showButtons
                            :min="0"
                            :max="100"
                            fluid
                            placeholder="Monto"
                        />
                        <InputNumber
                            v-model="antiguedad"
                            inputId="minmax-buttons-2"
                            mode="decimal"
                            showButtons
                            :min="0"
                            :max="100"
                            fluid
                            placeholder="Antiguedad"
                        />
                    </div>
                </div>
                <template #footer>
                    <Button
                        label="Cancelar"
                        icon="pi pi-times"
                        severity="danger"
                        @click="editarDiasVacaciones = false"
                    />
                    <Button
                        label="Actualizar"
                        icon="pi pi-save"
                        severity="success"
                        @click="updateDays"
                        :loading="submitted"
                    />
                </template>
            </Dialog>
            <Dialog
                v-model:visible="showConfirmDelete"
                header="Confirmar eliminación"
                modal
                style="width: 30rem"
            >
                <div class="flex align-items-center gap-3">
                    <i class="pi pi-exclamation-triangle text-orange-500 text-3xl"></i>

                    <span>
                        ¿Eliminar los días de vacaciones de
                        <strong>{{ rowToDelete?.nombre_empleado }}</strong>?
                    </span>
                </div>

                <template #footer>
                    <Button
                        label="Cancelar"
                        icon="pi pi-times"
                        @click="showConfirmDelete = false"
                        :disabled="deleting"
                        class="p-button-text"
                    />

                    <Button
                        label="Eliminar"
                        icon="pi pi-trash"
                        severity="danger"
                        :loading="deleting"
                        @click="confirmDelete"
                    />
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>
