<script setup>
import axios from "axios";
import { ref, reactive, onMounted } from "vue";
import { FilterMatchMode } from "@primevue/core/api";
import { useToastService } from "@/Stores/toastService";
import { computed } from 'vue'
import { useAuthz } from "@/composables/useAuthz";
const { can } = useAuthz();

/* =====================
PROPS
===================== */
const props = defineProps({
    branchOffices: Array,
    departments: Array,
});

/* =====================
ESTADO GENERAL
===================== */
const loading = ref(false);
const saldos = ref([]);
const employees = ref([]);
//Referencias a los popovers
const opMostrarColumnas = ref();
const opFijarColumnas = ref();

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
        departamento: { value: null, matchMode: FilterMatchMode.CONTAINS },
        fecha_ingreso: { value: null, matchMode: FilterMatchMode.CONTAINS },
        antiguedad: { value: null, matchMode: FilterMatchMode.CONTAINS },
        correspondientes: { value: null, matchMode: FilterMatchMode.CONTAINS },
        disfrutados: { value: null, matchMode: FilterMatchMode.CONTAINS },
        disponibles: { value: null, matchMode: FilterMatchMode.CONTAINS },
        prima_vacacional: { value: null, matchMode: FilterMatchMode.CONTAINS },
        // eliminado: { value: null, matchMode: FilterMatchMode.CONTAINS },
    };
};

initFilters();

const hasActiveFilters = computed(() => {
    return (
        !!activeFilters.branchOffice ||
        activeFilters.department.length > 0 ||
        activeFilters.employee?.length > 0
    )
})


/* =====================
UI STATE (DIALOGS)
===================== */
const columnsDialog = ref(false);
const otherFilterDialog = ref(false);
const openUploadDialog = ref(false);
const submitted = ref(false);

/* =====================
COLUMNAS
===================== */
const showColumns = reactive({
    clave_empleado: true,
    trabajador: true,
    departamento: true,
    fecha_ingreso: true,
    antiguedad: true,
    correspondientes: true,
    disfrutados: true,
    disponibles: true,
    prima_vacacional: true,
    // eliminado: true,
});

const frozenColumns = reactive({
    clave_empleado: false,
    trabajador: false,
    departamento: false,
    fecha_ingreso: false,
    antiguedad: false,
    correspondientes: false,
    disfrutados: false,
    disponibles: false,
    prima_vacacional: false,
    // eliminado: false,
});

const exportColumns = reactive({
    clave_empleado: true,
    trabajador: true,
    departamento: true,
    fecha_ingreso: true,
    antiguedad: true,
    correspondientes: true,
    disfrutados: true,
    disponibles: true,
    prima_vacacional: true,
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
const { showError, showSuccess, showValidationError } = useToastService();

//Función para guardar las columnas seleccionadas para exportar
const saveColumns = () => {
    columnsDialog.value = false;

    if (!selected.value.length) {
        showValidationError('Selecciona al menos un registro');
        return;
    }

    dt.value.exportCSV({
        selectionOnly: true,
        fileName: 'employee_vacations_saldos_empleado'
    });
};

/* =====================
FILTROS UI
===================== */
const selectedBranchOffice = ref(null);
const selectedDepartments = ref([]);
const selectedEmployees = ref([]);
// const includeDeleted = ref(false);

const filteredBranchOffices = ref([]);
const filteredDepartments = ref([]);

/* =====================
FILTROS ACTIVOS (CHIPS)
===================== */
const activeFilters = reactive({
    branchOffice: null,
    department: [],
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

        case 'department':
            selectedDepartments.value = []
            activeFilters.department = []
            break

        case 'employee':
            selectedEmployees.value = []
            activeFilters.employee = []
            break

        // case 'includeDeleted':
        //     includeDeleted.value = false
        //     activeFilters.includeDeleted = false
        //     break
    }

    loadSaldos({
        branchOfficeId: selectedBranchOffice.value?.id ?? null,
        departmentIds: selectedDepartments.value.map(d => d.id),
        employeeIds: selectedEmployees.value.map(e => e.id),
        // includeDeleted: includeDeleted.value
    })
}

/* =====================
LOAD SALDOS
===================== */
const loadSaldos = async (params = {}) => {
    loading.value = true;

    try {
        const response = await axios.get("/employee/vacations/filter-saldos", {
            params: {
                branch_office_id: params.branchOfficeId ?? null,
                department_id: params.departmentIds ?? [],
                'employees[]': params.employeeIds ?? [],
                // include_deleted: params.includeDeleted ? 1 : 0,
            },
        });

        saldos.value = response.data.data ?? [];
        employees.value = response.data.employees ?? [];

        console.log(employees.value);
    } catch (error) {
        console.error("Error cargando saldos:", error);
    } finally {
        loading.value = false;
    }
};

/* =====================
APLICAR FILTROS
===================== */
const applyFilters = () => {
    const depIds = selectedDepartments.value.map(d =>
        (d && typeof d === 'object') ? d.id : d
    );

    const empIds = selectedEmployees.value.map(e =>
        (e && typeof e === 'object') ? e.id : e
    );
    activeFilters.branchOffice = selectedBranchOffice.value;
    activeFilters.department = [...selectedDepartments.value];
    activeFilters.employee = [...selectedEmployees.value];
    // activeFilters.includeDeleted = includeDeleted.value;

    if (selectedBranchOffice.value) {
        localStorage.setItem(
            "selectedBranchOffice",
            JSON.stringify(selectedBranchOffice.value)
        );
    }

    loadSaldos({
        branchOfficeId: selectedBranchOffice.value?.id ?? null,
        departmentIds: depIds,
        employeeIds: empIds,
        // includeDeleted: includeDeleted.value,
    });

    otherFilterDialog.value = false;
};

/* =====================
LIMPIAR FILTROS
===================== */
const clearFilter = () => {
    initFilters();

    const currentBranch = selectedBranchOffice.value;

    selectedDepartments.value = [];
    selectedEmployees.value = [];
    // includeDeleted.value = false;

    activeFilters.department = [];
    activeFilters.employee = [];
    // activeFilters.includeDeleted = false;

    selectedBranchOffice.value = currentBranch;
    activeFilters.branchOffice = currentBranch;

    loadSaldos({
        branchOfficeId: currentBranch?.id ?? null
    });
};

/* =====================
MOUNTED
===================== */
onMounted(() => {
    // console.log(props.data);
    filteredBranchOffices.value = props.branchOffices ?? [];
    filteredDepartments.value = props.departments ?? [];

    const storedBranch = localStorage.getItem("selectedBranchOffice");

    if (storedBranch && props.branchOffices?.length) {
        const stored = JSON.parse(storedBranch);

        const branch = props.branchOffices.find(
            b => b.id === stored.id
        );

        if (branch) {
            selectedBranchOffice.value = branch;

            activeFilters.branchOffice = branch;

            loadSaldos({
                branchOfficeId: branch.id,
            });
            return;
        }
    }

    loadSaldos();
});
</script>

<template>
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
                    v-if="can('vacations.export')"
                    type="button"
                    icon="pi pi-upload"
                    label="Exportar"
                    severity="secondary"
                    class="mt-2 ml-2"
                    @click="columnsDialog = true"
                />
            </template>
        </Toolbar>
        <!-- <pre>{{ employee }}</pre> -->
        <DataTable
            ref="dt"
            v-model:selection="selected"
            :value="saldos"
            dataKey="clave_empleado"
            :paginator="true"
            :rows="10"
            scrollable
            scrollHeight="400px"
            tableStyle="min-width: 110rem"
            v-model:filters="filters"
            filterDisplay="menu"
            exportFilename="employee_vacations"
            :globalFilterFields="['clave_empleado', 'nombre_empleado', 'departamento', 'fecha_ingreso', 'antiguedad', 'correspondientes', 'disfrutados', 'disponibles']"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} datos de saldos por empleado"
        >
            <template #header>
                <div
                    class="flex flex-wrap gap-2 items-end justify-between mb-6"
                >
                    <div>
                        <h4 class="m-0">Saldos por Empleado</h4>
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
                <div class="mb-2 flex flex-wrap gap-2">
                    <Chip
                        v-if="activeFilters.branchOffice"
                        :label="`Planta: ${activeFilters.branchOffice.code}`"
                        @remove="removeFilter('department')"
                    />

                    <Chip
                        v-if="activeFilters.department.length"
                        :label="`Depto: ${activeFilters.department.map(d => d.name).join(', ')}`"
                        removable
                        @remove="removeFilter('department')"
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
                        @remove="removeFilter('department')"
                    /> -->
                </div>
            </template>

            <Column
                selectionMode="multiple"
                style="width: 5rem"
                :exportable="false"
            ></Column>
            <Column
                field="clave_empleado"
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
                    <span v-else>{{ data.clave_empleado }}</span>
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
                field="nombre_empleado"
                header="Trabajador"
                :filter="true"
                :frozen="frozenColumns.trabajador"
                :style="{
                    width: '20rem',
                    display: showColumns.trabajador ? '' : 'none',
                }"
                :exportable="exportColumns.trabajador"
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
                field="departamento"
                header="Departamento"
                :frozen="frozenColumns.departamento"
                sortable
                :style="{
                    width: '20rem',
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
                field="fecha_ingreso"
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
                    <span v-else>{{ data.fecha_ingreso }}</span>
                </template>
                <template #filter="{ filterModel }">
                    <InputText
                        v-model="filterModel.value"
                        type="text"
                        placeholder="Buscar por Fecha Ingreso"
                    /> </template
            ></Column>
            <Column
                field="antiguedad"
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
                    <span v-else>{{ data.antiguedad }}</span>
                </template>
                <template #filter="{ filterModel }">
                    <InputText
                        v-model="filterModel.value"
                        type="text"
                        placeholder="Buscar por Antiguedad"
                    /> </template
            ></Column>
            <Column
                field="correspondientes"
                header="Correspondientes"
                sortable
                :frozen="frozenColumns.correspondientes"
                :style="{
                    width: '20rem',
                    display: showColumns.correspondientes ? '' : 'none',
                }"
                :exportable="exportColumns.correspondientes"
            >
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <span v-else>{{ data.correspondientes }}</span>
                </template>
                <template #filter="{ filterModel }">
                    <InputText
                        v-model="filterModel.value"
                        type="text"
                        placeholder="Buscar por Correspondientes"
                    /> </template
            ></Column>
            <Column
                field="disfrutados"
                header="Disfrutados"
                sortable
                :frozen="frozenColumns.disfrutados"
                :style="{
                    width: '20rem',
                    display: showColumns.disfrutados ? '' : 'none',
                }"
                :exportable="exportColumns.disfrutados"
            >
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <span v-else>{{ data.disfrutados }}</span>
                </template>
                <template #filter="{ filterModel }">
                    <InputText
                        v-model="filterModel.value"
                        type="text"
                        placeholder="Buscar por Disfrutados"
                    /> </template
            ></Column>
            <Column
                field="disponibles"
                header="Disponibles"
                sortable
                :frozen="frozenColumns.disponibles"
                :style="{
                    width: '20rem',
                    display: showColumns.disponibles ? '' : 'none',
                }"
                :exportable="exportColumns.disponibles"
            >
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <span v-else>{{ data.disponibles }}</span>
                </template>
                <template #filter="{ filterModel }">
                    <InputText
                        v-model="filterModel.value"
                        type="text"
                        placeholder="Buscar por Disponibles"
                    /> </template
            ></Column>
            <Column
                field="prima_vacacional"
                header="Prima Vacacional"
                sortable
                :frozen="frozenColumns.prima_vacacional"
                :style="{
                    width: '20rem',
                    display: showColumns.prima_vacacional ? '' : 'none',
                }"
                :exportable="exportColumns.prima_vacacional"
            >
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <span v-else>{{ data.prima_vacacional }}</span>
                </template>
                <template #filter="{ filterModel }">
                    <InputText
                        v-model="filterModel.value"
                        type="text"
                        placeholder="Buscar por Ciudad"
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
                    <span v-else>{{ data.include_deleted }}</span>
                </template>
                <template #filter="{ filterModel }">
                    <InputText
                        v-model="filterModel.value"
                        type="text"
                        placeholder="Buscar por Eliminado"
                    /> </template
            ></Column> -->
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
                    :loading="submitted"
                />
            </template>
        </Dialog>
        <Dialog
            v-model:visible="otherFilterDialog"
            header="Seleccionar filtros adicionales"
            modal
            :style="{ width: '450px' }"
        >
            <div class="flex flex-col gap-5">

                <!-- FILTROS ACTIVOS -->
                <div v-show="hasActiveFilters"
                    class="p-3 rounded-lg bg-surface-100 dark:bg-surface-800">

                    <span class="text-sm font-semibold block mb-2"></span>
                    <!-- <pre>{{ activeFilters.employee }}</pre> -->
                    <!-- <div class="flex flex-wrap gap-2">
                        <div v-if="hasActiveFilters" class="flex flex-wrap gap-2">
                            <Chip
                                v-if="activeFilters.branchOffice"
                                :label="`Planta: ${activeFilters.branchOffice.code}`"
                                removable
                                @remove="removeFilterDialog('branchOffice')"
                            />
                            <Chip
                                v-if="activeFilters.department.length"
                                :label="`Deptos: ${activeFilters.department.map(d => d.name).join(', ')}`"
                                removable
                                @remove="removeFilterDialog('department')"
                            />
                            <Chip
                                v-if="activeFilters.employee.length"
                                :label="activeFilters.employee
                                    .map(id => {
                                        const emp = employees.find(e => e.id === id);
                                        return emp ? `(${emp.id}) ${emp.full_name}` : id;
                                    })
                                    .join(', ')
                                "
                                removable
                                @remove="removeFilterDialog('employee')"
                            />
                            <Chip
                                v-if="activeFilters.includeDeleted"
                                label="Incluir eliminados"
                                removable
                                @remove="removeFilterDialog('includeDeleted')"
                            />
                        </div>
                    </div> -->
                </div>

                <!-- PLANTA -->
                <FloatLabel variant="on" class="flex-1">
                    <Dropdown
                        v-model="selectedBranchOffice"
                        :options="props.branchOffices"
                        optionLabel="code"
                        placeholder="Selecciona planta"
                        class="w-full"
                    />
                    <label>Plantas</label>
                </FloatLabel>

                <!-- DEPARTAMENTOS -->
                <FloatLabel variant="on" class="flex-1">
                    <MultiSelect
                        v-model="selectedDepartments"
                        :options="props.departments"
                        optionLabel="name"
                        filter
                        display="chip"
                        class="w-full"
                        placeholder="Departamentos"
                    />
                    <label>Departamentos</label>
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
                    severity="danger"
                    @click="otherFilterDialog = false"
                />
                <Button
                    label="Filtrar"
                    severity="success"
                    @click="applyFilters"
                    :loading="submitted"
                />
            </template>
        </Dialog>

    </div>
</template>
