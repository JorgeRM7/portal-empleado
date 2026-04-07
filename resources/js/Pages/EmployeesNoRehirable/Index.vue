<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToastService } from "@/Stores/toastService";
import axios from "axios";
import { useToast } from "primevue/usetoast";
// import { onMounted, ref } from "vue";
import { onMounted, ref, computed, reactive } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { useNotifications } from "@/composables/useNotifications";
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
const noRehirables = ref([]);
const filters = ref({});
const exportDialog = ref(false)
const fechaInicio = ref(null)
const fechaFin = ref(null)
const loadingExport = ref(false)
const loadingRecontratable = ref(false)
const loadingEmployees = ref(false);

const dateIngreso = ref(null);
const dateTermino = ref(null);

//Referencias a los popovers
const opMostrarColumnas = ref();
const opFijarColumnas = ref();

//dialog editar
const editDialog = ref(false);
const selectedRow = ref(null);
const reingresoForm = reactive({
    employee_id: null,
    branch_office_id: null,
    comentario: ''
});

/* =====================
DATATABLE
===================== */
const dt = ref(null);
const toast = useToast();
const selected = ref([]);

/* =====================
FILTROS DE TABLA
===================== */

const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },

        id: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }]
        },

        employee: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }]
        },

        branchOffice: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }]
        },

        entry_date_formatted: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }]
        },

        termination_date_formatted: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }]
        },

        termination_reason: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }]
        }
    };
};

initFilters();

const hasActiveFilters = computed(() => {
    return Array.isArray(activeFilters.branchOffice) &&
        activeFilters.branchOffice.length > 0;
});

const formatDate = (date) => {
    if (!date) return null;

    const d = new Date(date);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();

    return `${day}-${month}-${year}`; // dd-mm-yyyy
};

// Dialaog de reingreso
const openEditDialog = (row) => {
    console.log('ROW:', row);
    selectedRow.value = row;

    reingresoForm.employee_id = row.id;
    reingresoForm.branch_office_id = row.branchOffice;
    reingresoForm.comentario = '';

    editDialog.value = true;
};

const removeSingleFilter = (index) => {
    activeFilters.branchOffice.splice(index, 1);
    selectedBranchOffice.value = [...activeFilters.branchOffice];
    loadNoRehirable(activeFilters.branchOffice);
};

/* =====================
UI STATE (DIALOGS)
===================== */
const columnsDialog = ref(false);
const otherFilterDialog = ref(false);
const openUploadDialog = ref(false);
const submitted = ref(false);

const saveColumns = () => {
    columnsDialog.value = false;
    dt.value.exportCSV();
};

const updateBranches = (values) => {
    const allIds = allBranchOfficeIds.value
    const hadAllSelected =
        selectedBranchOffice.value.length === allIds.length + 1 &&
        selectedBranchOffice.value.includes('all')

    const hasAllNow = values.includes('all')

    if (hasAllNow && !hadAllSelected && values.length !== allIds.length) {
        selectedBranchOffice.value = ['all', ...allIds]
        return
    }

    if (!hasAllNow && hadAllSelected && values.length === allIds.length) {
        selectedBranchOffice.value = []
        return
    }

    if (hasAllNow && values.length < allIds.length + 1) {
        selectedBranchOffice.value = values.filter(v => v !== 'all')
        return
    }

    const onlyRealIds = values.filter(v => v !== 'all')
    if (onlyRealIds.length === allIds.length) {
        selectedBranchOffice.value = ['all', ...allIds]
        return
    }

    selectedBranchOffice.value = onlyRealIds
}

const allBranchOfficeIds = computed(() =>
    props.branchOffices.map(b => b.id)
)

const branchOfficesWithAll = computed(() => [
    { id: 'all', code: 'TODAS LAS PLANTAS' },
    ...props.branchOffices
])

const removePlant = (plantId) => {
    selectedBranchOffice.value =
        selectedBranchOffice.value.filter(id => id !== plantId)

    activeFilters.branchOffice =
        activeFilters.branchOffice.filter(id => id !== plantId)

    loadNoRehirable()
}

const getPlantName = (id) => {
    const plant = props.branchOffices.find(b => b.id === id)
    return plant ? plant.code : id
}

const getEmployeeName = (id) => {
    const emp = employeesFilterBranchOffice.value.find(e => e.id === id);
    return emp ? emp.full_name : `ID: ${id}`;
};

const removeIndividualEmployee = (empId) => {
    activeFilters.employee = activeFilters.employee.filter(id => id !== empId);
    selectedEmployees.value = selectedEmployees.value.filter(v => {
        const id = typeof v === 'object' ? v.id : v;
        return Number(id) !== Number(empId);
    });
    loadNoRehirable();
};

const getRangeLabel = (range) => {
    if (!range || !range[0]) return '';
    const start = range[0].toLocaleDateString();
    const end = range[1] ? range[1].toLocaleDateString() : start;
    return `${start} - ${end}`;
};

const { sendNotification } = useNotifications();

//Función para mostrar toast de éxito y error
const { showSuccess, showError } = useToastService();

//Columnas visibles
const showColumns = ref({
    acciones: true,
    clave_empleado: true,
    nombre_completo: true,
    planta: true,
    fecha_ingreso: true,
    fecha_termino: true,
    motivo_baja: true,
    comentarios: true,
});

//Columnas fijas
const frozenColumns = ref({
    acciones: true,
    clave_empleado: true,
    nombre_completo: true,
    planta: false,
    fecha_ingreso: false,
    fecha_termino: false,
    motivo_baja: false,
    comentarios: false,
});

//Columnas de exportación
const exportColumns = ref({
    clave_empleado: true,
    nombre_completo: true,
    planta: true,
    fecha_ingreso: true,
    fecha_termino: true,
    motivo_baja: true,
    comentarios: true,
});

//Funciones para alternar los popovers
const toggleMostrarColumnas = (event) => {
    opMostrarColumnas.value.toggle(event);
};

const toggleFijarColumnas = (event) => {
    opFijarColumnas.value.toggle(event);
};

/* =====================
FILTROS UI
===================== */
const selectedBranchOffice = ref([]);
const filteredBranchOffices = ref([]);

const selectedEmployees = ref([]);
const employeesFilterBranchOffice = ref([]);

/* =====================
FILTROS ACTIVOS (CHIPS)
===================== */
const activeFilters = reactive({
    branchOffice: [],
    employee: [],
    ingreso: null,
    termino: null
});

/* =====================
REMOVE FILTER
===================== */

const removeFilter = (type, id = null) => {
    switch (type) {

        case 'branchOffice':
            if (id !== null) {
                selectedBranchOffice.value.filter(b => b !== id);
                activeFilters.branchOffice = activeFilters.branchOffice.filter(branchId => branchId !== id);
            } else {
                selectedBranchOffice.value = [];
                activeFilters.branchOffice = [];
            }
            localStorage.setItem("selectedBranchOffice", JSON.stringify(activeFilters.branchOffice));
            break;

        case 'employees':
            selectedEmployees.value = [];
            activeFilters.employee = [];
            break;ilters.sinRol = false;
            break;
    }

    loadNoRehirable();
};

/* =====================
LOAD
===================== */
const loadNoRehirable = async (branchOfficeIds = []) => {
    loading.value = true;
    loadingEmployees.value = true;

    const ingresoInicio = activeFilters.ingreso?.[0] ? formatApiDate(activeFilters.ingreso[0]) : null;
    const ingresoFin = activeFilters.ingreso?.[1] ? formatApiDate(activeFilters.ingreso[1]) : null;

    const terminoInicio = activeFilters.termino?.[0] ? formatApiDate(activeFilters.termino[0]) : null;
    const terminoFin = activeFilters.termino?.[1] ? formatApiDate(activeFilters.termino[1]) : null;

    const queryParams = {
        branch_office_id: Array.isArray(branchOfficeIds)
            ? branchOfficeIds
            : [branchOfficeIds],
        employees: activeFilters.employee,
        ingreso_desde: ingresoInicio,
        ingreso_hasta: ingresoFin,
        termino_desde: terminoInicio,
        termino_hasta: terminoFin
    };

    console.log("Parámetros enviados a la API:", queryParams);

    try {
        const { data } = await axios.get('/employee/no-rehirable/data', {
            params: queryParams
        });

        noRehirables.value = data.data ?? [];
        employeesFilterBranchOffice.value = data.employees ?? [];
        console.log(noRehirables.value);
    } catch (error) {
        noRehirables.value = [];
        console.error(error);
        loading.value = false;
    } finally {
        loading.value = false;
        loadingEmployees.value = false;
    }
};

/* =====================
REINGRESAR EMPLEADOS
===================== */
const saveRehiring = async () => {
    loadingRecontratable.value = true

    try {
        await axios.put(`/employee/no-rehirable/${reingresoForm.employee_id}`);

        showSuccess('Empleado reingresado correctamente');
        editDialog.value = false;

        loadNoRehirable(activeFilters.branchOffice);

    } catch (error) {
        console.error(error);
        showError('Error al reingresar empleado');
    } finally {
        loadingRecontratable.value = false;
    }
};

/* =====================
APLICAR FILTROS
===================== */
const applyFilters = () => {
    const ids = selectedBranchOffice.value.map(v =>
        typeof v === 'object' ? Number(v.id) : Number(v)
    );

    const employeesIds = selectedEmployees.value.map(v =>
        typeof v === 'object' ? Number(v.id) : Number(v)
    );

    activeFilters.branchOffice = ids;
    activeFilters.employee = employeesIds;

    activeFilters.ingreso = dateIngreso.value;
    activeFilters.termino = dateTermino.value;

    localStorage.setItem(
        "selectedBranchOffice",
        JSON.stringify(ids)
    );

    loadNoRehirable(ids);
    otherFilterDialog.value = false;
};

const formatApiDate = (date) => {
    if (!date) return null;
    return date.toISOString().split('T')[0];
};

/* =====================
LIMPIAR FILTROS
===================== */
const clearFilter = () => {
    initFilters();

    // selectedBranchOffice.value = [];
    selectedEmployees.value = [];

    // activeFilters.branchOffice = null;
    activeFilters.employee = [];

    loadNoRehirable([]);
};


//Estado de progreso de subida
const progress = ref(0);

//Estado de visibilidad del toast
const visible = ref(false);

//Referencia al servicio de toast personalizado
const interval = ref();

/* =====================
TOTAL DATOS ENLISTADOS
===================== */
const totalDiasGeneral = computed(() => {
    return noRehirables.value.length;
});

const branchOfficeNames = computed(() => {
    if (!Array.isArray(activeFilters.branchOffice)) return [];

    return activeFilters.branchOffice
        .map(id => Number(id))
        .filter(id => !Number.isNaN(id))
        .map(id => {
            const plant = props.branchOffices.find(b => b.id === id);
            return plant?.code ?? `ID ${id}`;
        });
});


/* =====================
MOUNTED
===================== */
onMounted(() => {
    filteredBranchOffices.value = props.branchOffices ?? [];

    const raw = localStorage.getItem("selectedBranchOffice");
    console.log(raw)
    let stored = [];

    try {
        const parsed = JSON.parse(raw);

        if (Array.isArray(parsed)) {
            stored = parsed.map(v =>
                typeof v === 'object' ? Number(v.id) : Number(v)
            );
        } else if (parsed && typeof parsed === 'object') {
            stored = [Number(parsed.id)];
        }
    } catch (e) {
        stored = [];
    }

    if (stored.length) {
        selectedBranchOffice.value = stored;
        activeFilters.branchOffice = stored;
        loadNoRehirable(stored);
        return;
    }

    loadNoRehirable([]);
});

</script>

<template>
    <AppLayout :title="'No Recontratables'">
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
                        type="button"
                        icon="pi pi-upload"
                        label="Exportar"
                        severity="secondary"
                        class="mt-2 ml-2"
                        @click="columnsDialog = true"
                    />
                </template>
            </Toolbar>
            <DataTable
                ref="dt"
                v-model:selection="selected"
                :value="noRehirables"
                dataKey="clave_empleado"
                :paginator="true"
                :rows="10"
                scrollable
                scrollHeight="400px"
                tableStyle="min-width: 110rem"
                v-model:filters="filters"
                filterDisplay="menu"
                exportFilename="Employees_NoRehirable"
                :globalFilterFields="[
                    'id',
                    'employee',
                    'branchOffice',
                    'entry_date_formatted',
                    'termination_date_formatted',
                    'termination_reason'
                ]"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} datos de no recontratables"
            >
                <template #header>
                    <div
                        class="flex flex-wrap gap-2 items-end justify-between mb-6"
                    >
                        <div>
                            <h4 class="m-0">No Recontratables</h4>
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
                    <template v-if="selectedBranchOffice?.includes('all')">
                        <Chip label="Todas las plantas" />
                    </template>
                    <template v-else>
                        <Chip
                            v-for="plant in selectedBranchOffice"
                            :key="plant"
                            @remove="removePlant(plant)"
                            :label="getPlantName(plant)"
                        />
                    </template>
                    <template v-for="empId in activeFilters.employee" :key="empId">
                        <Chip
                            :label="getEmployeeName(empId)"
                            removable
                            @remove="removeIndividualEmployee(empId)"
                        />
                    </template>
                    <template v-if="activeFilters.ingreso && activeFilters.ingreso[0]">
                        <Chip
                            :label="'Ingreso: ' + getRangeLabel(activeFilters.ingreso)"
                            removable
                            @remove="activeFilters.ingreso = null; dateIngreso = null; loadNoRehirable(activeFilters.branchOffice)"
                        />
                    </template>

                    <template v-if="activeFilters.termino && activeFilters.termino[0]">
                        <Chip
                            :label="'Término: ' + getRangeLabel(activeFilters.termino)"
                            removable
                            @remove="activeFilters.termino = null; dateTermino = null; loadNoRehirable(activeFilters.branchOffice)"
                        />
                    </template>
                </template>
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
                                icon="pi pi-sync"
                                class="mr-2"
                                rounded
                                v-tooltip.top="'Recontratar'"
                                severity="warn"
                                @click="openEditDialog(slotProps.data)"
                            />
                        </div>
                    </template>
                </Column>
                <Column
                    field="id"
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
                        <span v-else>{{ data.id }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por clave empleado"
                        />
                    </template>
                </Column>
                <Column
                    field="employee"
                    header="Nombre completo"
                    :frozen="frozenColumns.nombre_completo"
                    sortable
                    :style="{
                        width: '20rem',
                        display: showColumns.nombre_completo ? '' : 'none',
                    }"
                    :exportable="exportColumns.nombre_completo"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.employee }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por nombre completo"
                        />
                    </template>
                </Column>

                <Column
                    field="branchOffice"
                    header="Planta"
                    sortable
                    :frozen="frozenColumns.planta"
                    :style="{
                        width: '20rem',
                        display: showColumns.planta ? '' : 'none',
                    }"
                    :exportable="exportColumns.planta"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.branchOffice }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por planta"
                        /> </template
                ></Column>
                <Column
                    field="entry_date_formatted"
                    header="Fecha Ingreso"
                    sortable
                    :frozen="frozenColumns.fecha_ingreso"
                    :style="{
                        width: '20rem',
                        display: showColumns.fecha_ingreso ? '' : 'none',
                    }"
                    :exportable="exportColumns.fecha_ingreso"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.entry_date_formatted }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por fecha de igreso"
                        /> </template
                ></Column>
                <Column
                    field="termination_date_formatted"
                    header="Fecha Termino"
                    sortable
                    :frozen="frozenColumns.fecha_termino"
                    :style="{
                        width: '20rem',
                        display: showColumns.fecha_termino ? '' : 'none',
                    }"
                    :exportable="exportColumns.fecha_termino"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.termination_date_formatted }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por fecha de termino"
                        /> </template
                ></Column>
                <Column
                    field="termination_reason"
                    header="Motivo Baja"
                    sortable
                    :frozen="frozenColumns.motivo_baja"
                    :style="{
                        width: '20rem',
                        display: showColumns.motivo_baja ? '' : 'none',
                    }"
                    :exportable="exportColumns.motivo_baja"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.termination_reason }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por motivo de baja"
                        /> </template
                ></Column>
                <Column
                    field="content"
                    header="Comentarios"
                    sortable
                    :frozen="frozenColumns.comentarios"
                    :style="{
                        width: '20rem',
                        display: showColumns.comentarios ? '' : 'none',
                    }"
                    :exportable="exportColumns.comentarios"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.content }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por comentarios"
                        /> </template
                ></Column>
            </DataTable>
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
                v-model:visible="otherFilterDialog"
                :style="{ width: '450px' }"
                header="Seleccionar filtros adicionales"
                :modal="true"
            >
                <div class="flex flex-col gap-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Planta
                    </label>
                    <Multiselect
                        :modelValue="selectedBranchOffice"
                        @update:modelValue="updateBranches"
                        display="chip"
                        :options="branchOfficesWithAll"
                        optionLabel="code"
                        optionValue="id"
                        filter
                        placeholder="Selecciona una planta"
                        class="w-full"
                    >
                        <template #value="slotProps">
                            <span v-if="!slotProps.value || !slotProps.value.length">
                                Selecciona una planta
                            </span>

                            <span
                                v-else-if="slotProps.value.includes('all') || slotProps.value.length > 5"
                            >
                                {{
                                    slotProps.value.includes('all')
                                        ? props.branchOffices.length
                                        : slotProps.value.length
                                }}
                                plantas seleccionadas
                            </span>
                        </template>
                    </Multiselect>
                </div>

                <div class="flex flex-col gap-5">
                    <FloatLabel variant="on" class="flex-1 mt-4">

                        <Multiselect
                            v-model="selectedEmployees"
                            :options="employeesFilterBranchOffice"
                            optionLabel="full_name"
                            optionValue="id"
                            :loading="loadingEmployees"
                            filter
                            :filterFields="['full_name', 'id']"
                            placeholder="Selecciona un empleado"
                            class="w-full"
                            display="chip"
                            :virtualScrollerOptions="{ itemSize: 30 }"
                        >
                            <template #loadingicon>
                                <i class="pi pi-spin pi-spinner text-primary" style="font-size: 1.2rem"></i>
                            </template>

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
                </div>

                <div class="flex flex-col md:flex-row gap-6 mt-4">
                    <FloatLabel variant="on" class="w-full md:w-1/2">
                        <DatePicker
                            v-model="dateIngreso"
                            selectionMode="range"
                            inputId="fecha"
                            showIcon
                            iconDisplay="input"
                            class="w-full"
                        />
                        <label for="fecha">Fecha Ingreso</label>
                    </FloatLabel>
                    <FloatLabel variant="on" class="w-full md:w-1/2">
                        <DatePicker
                            v-model="dateTermino"
                            selectionMode="range"
                            inputId="fecha"
                            showIcon
                            iconDisplay="input"
                            class="w-full"
                        />
                        <label for="fecha">Fecha Termino</label>
                    </FloatLabel>
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
                v-model:visible="editDialog"
                header="Recontratar"
                modal
                :style="{ width: '500px' }"
            >
                <div v-if="selectedRow">
                    <div class="flex flex-column gap-3 mb-5">
                        <Message severity="info">
                            <div style="display: flex; flex-direction: column; gap: 6px;">

                                <span style="display: flex; align-items: center; gap: 6px;">
                                <i class="pi pi-user"></i>
                                ({{ selectedRow.id }}) - {{ selectedRow.employee }}
                                </span>

                                <span style="display: flex; align-items: center; gap: 6px;">
                                <i class="pi pi-building"></i>
                                Planta: {{ selectedRow.branchOffice }}
                                </span>

                                <span style="display: flex; align-items: center; gap: 6px;">
                                <i class="pi pi-exclamation-triangle"></i>
                                Motivo: {{ selectedRow.termination_reason }}
                                </span>

                                <span
                                style="display: flex; align-items: center; gap: 6px;"
                                >
                                <i :class="selectedRow.content ? 'pi pi-comment' : 'pi pi-eye-slash'"></i>
                                Comentarios: {{ selectedRow.content || 'SIN COMENTARIOS' }}
                                </span>

                            </div>
                        </Message>
                    </div>
                    <Message severity="warn" icon="pi pi-exclamation-triangle">
                        Cuando des click en aplicar cambios este empleado pasara a ser recontratable, ten en cuenta que
                        este empleado pudo haber tenido problemas graves con la empresa, y esta bajo la responsabilidad
                        de quien lo recontrate.
                    </Message>
                </div>

                <template #footer>
                    <Button
                        label="Cancelar"
                        icon="pi pi-times"
                        severity="secondary"
                        @click="editDialog = false"
                    />
                    <Button
                        :label="loadingRecontratable ? 'Aplicando Cambios...' : 'Aplicar cambios'"
                        icon="pi pi-check"
                        severity="warn"
                        :loading="loadingRecontratable"
                        @click="saveRehiring"
                    />
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>
