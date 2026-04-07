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
const vacaciones = ref([]);
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
        fecha_disfrute: { value: null, matchMode: FilterMatchMode.CONTAINS },
        dias_disfrute: { value: null, matchMode: FilterMatchMode.CONTAINS },
        antiguedad: { value: null, matchMode: FilterMatchMode.CONTAINS },
        // deleted_at_formatted: { value: null, matchMode: FilterMatchMode.CONTAINS },
    };
};

initFilters();

const hasActiveFilters = computed(() => {
    return (
        !!activeFilters.branchOffice ||
        activeFilters.department?.length > 0 ||
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
    nombre_empleado: true,
    fecha_disfrute: true,
    dias_disfrute: true,
    antiguedad: true,
    fechaPago: true,
    // eliminado: true,
});

const frozenColumns = reactive({
    clave_empleado: false,
    nombre_empleado: false,
    fecha_disfrute: false,
    dias_disfrute: false,
    antiguedad: false,
    fechaPago: false,
    // eliminado: false,
});

const exportColumns = reactive({
    clave_empleado: true,
    nombre_empleado: true,
    fecha_disfrute: true,
    dias_disfrute: true,
    antiguedad: true,
    fechaPago: true,
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
    console.log(selected.value);
    columnsDialog.value = false;

    if (!selected.value.length) {
        showValidationError('Selecciona al menos un registro');
        return;
    }

    dt.value.exportCSV({
        selectionOnly: true,
        fileName: 'employee_vacations'
    });
};

const getEmployeeName = (id) => {
    const emp = employees.value.find(e => e.id === id);
    return emp ? emp.full_name : 'Cargando...';
};

const removeEmployee = (id) => {
    selectedEmployees.value = selectedEmployees.value.filter(e => e !== id);
    activeFilters.employee = activeFilters.employee.filter(e => e !== id);

    loadVacaciones({
        branchOfficeId: selectedBranchOffice.value?.id ?? null,
        week: selectedWeek.value,
        departmentIds: selectedDepartments.value.map(d => d.id),
        employeeIds: selectedEmployees.value,
    });
};

const removeDepartment = (id) => {
    selectedDepartments.value = selectedDepartments.value.filter(d => d.id !== id);
    activeFilters.department = activeFilters.department.filter(d => d.id !== id);

    loadVacaciones({
        branchOfficeId: selectedBranchOffice.value?.id ?? null,
        week: selectedWeek.value,
        departmentIds: selectedDepartments.value.map(d => d.id),
        employeeIds: selectedEmployees.value.map(e =>
            (e && typeof e === 'object') ? e.id : e
        ),
    });
};

/* =====================
FILTROS UI
===================== */
const selectedBranchOffice = ref(null);
const selectedDepartments = ref([]);
const selectedEmployees = ref([]);
// const includeDeleted = ref(false);
const selectedWeek = ref(null);

const filteredBranchOffices = ref([]);
const filteredDepartments = ref([]);

/* =====================
FILTROS ACTIVOS (CHIPS)
===================== */
const activeFilters = reactive({
    branchOffice: null,
    week: null,
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

        case 'week':
            selectedWeek.value = null;
            activeFilters.week = null;
            break

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

    loadVacaciones({
        branchOfficeId: selectedBranchOffice.value?.id ?? null,
        week: selectedWeek.value,
        departmentIds: selectedDepartments.value.map(d => d.id),
        employeeIds: selectedEmployees.value.map(e => e.id),
        // includeDeleted: includeDeleted.value
    })
}

/* =====================
LOAD VACACIONES
===================== */
const loadVacaciones = async (params = {}) => {
    loading.value = true;
    console.log(params.week );
    try {
        const response = await axios.get("/employee/vacations/filter-data", {
            params: {
                branch_office_id: params.branchOfficeId ?? null,
                semana: params.week ?? null,
                department_id: params.departmentIds ?? [],
                'employees[]': params.employeeIds ?? [],
                // include_deleted: params.includeDeleted ? 1 : 0,
            },
        });

        vacaciones.value = response.data.data ?? [];
        employees.value = response.data.employees ?? [];
        // console.log(employees);
        console.log(vacaciones.value);
    } catch (error) {
        console.error("Error cargando vacaciones:", error);
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

    activeFilters.week = selectedWeek.value;
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

    loadVacaciones({
        branchOfficeId: selectedBranchOffice.value?.id ?? null,
        week: selectedWeek.value,
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

    loadVacaciones({
        branchOfficeId: currentBranch?.id ?? null
    });
};

/* =====================
NO SEMANA
===================== */
const getCurrentISOWeek = () => {
    const now = new Date();

    const date = new Date(Date.UTC(
        now.getFullYear(),
        now.getMonth(),
        now.getDate()
    ));

    const day = date.getUTCDay() || 7;
    date.setUTCDate(date.getUTCDate() + 4 - day);

    const yearStart = new Date(Date.UTC(date.getUTCFullYear(), 0, 1));
    const weekNo = Math.ceil((((date - yearStart) / 86400000) + 1) / 7);

    return `${date.getUTCFullYear()}-W${String(weekNo).padStart(2, '0')}`;
};

/* =====================
MOUNTED
===================== */
onMounted(() => {
    filteredBranchOffices.value = props.branchOffices ?? [];
    filteredDepartments.value = props.departments ?? [];

    selectedWeek.value = getCurrentISOWeek();
    activeFilters.week = selectedWeek.value;

    const storedBranch = localStorage.getItem("selectedBranchOffice");

    if (storedBranch && props.branchOffices?.length) {
        const stored = JSON.parse(storedBranch);
        const branch = props.branchOffices.find(b => b.id === stored.id);

        if (branch) {
            selectedBranchOffice.value = branch;
            activeFilters.branchOffice = branch;

            loadVacaciones({
                branchOfficeId: branch.id,
                week: selectedWeek.value
            });
            return;
        }
    }
    loadVacaciones({
        week: selectedWeek.value
    });
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
        <DataTable
            ref="dt"
            v-model:selection="selected"
            :value="vacaciones"
            dataKey="clave_empleado"
            :paginator="true"
            :rows="10"
            scrollable
            scrollHeight="400px"
            tableStyle="min-width: 110rem"
            v-model:filters="filters"
            filterDisplay="menu"
            exportFilename="employee_vacations"
            :globalFilterFields="['clave_empleado', 'nombre_empleado', 'fecha_disfrute', 'dias_disfrute', 'antiguedad', 'eliminado']"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} datos de vacaciones"
        >
            <template #header>
                <div
                    class="flex flex-wrap gap-2 items-end justify-between mb-6"
                >
                    <div>
                        <h4 class="m-0">Vacaciones</h4>
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
                        @remove="removeFilter('branchOffice')"
                    />

                    <Chip
                        v-if="activeFilters.week"
                        :label="`Semana: ${activeFilters.week}`"
                        @remove="removeFilter('week')"
                    />

                    <Chip
                        v-for="dep in activeFilters.department"
                        :key="dep.id"
                        :label="dep.name"
                        removable
                        @remove="removeDepartment(dep.id)"
                    />

                    <Chip
                        v-for="empId in activeFilters.employee"
                        :key="empId"
                        :label="getEmployeeName(empId)"
                        removable
                        @remove="removeEmployee(empId)"
                    />

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
                    <span v-else>{{ data.nombre_empleado }}</span>
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
                        placeholder="Buscar por Telefono"
                    /> </template
            ></Column>
            <Column
                field="fecha_calculada"
                header="Fecha Disfrute"
                :frozen="frozenColumns.fecha_disfrute"
                sortable
                :style="{
                    width: '20rem',
                    display: showColumns.fecha_disfrute ? '' : 'none',
                }"
                :exportable="exportColumns.fecha_disfrute"
            >
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <span v-else>
                        {{data.fecha_calculada}}
                    </span>
                </template>
                <template #filter="{ filterModel }">
                    <InputText
                        v-model="filterModel.value"
                        type="text"
                        placeholder="Buscar por Fecha Disfrute"
                    />
                </template>
            </Column>

            <Column
                field="dias_disfrute"
                header="Dias Disfrute"
                sortable
                :frozen="frozenColumns.dias_disfrute"
                :style="{
                    width: '20rem',
                    display: showColumns.dias_disfrute ? '' : 'none',
                }"
                :exportable="exportColumns.dias_disfrute"
            >
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <span v-else>{{ data.dias_disfrute }}</span>
                </template>
                <template #filter="{ filterModel }">
                    <InputText
                        v-model="filterModel.value"
                        type="text"
                        placeholder="Buscar por Dias disfrute"
                    /> </template
            ></Column>

            <Column
                field="fecha_pago"
                header="Fecha Pago"
                sortable
                :frozen="frozenColumns.fechaPago"
                :style="{
                    width: '20rem',
                    display: showColumns.fechaPago ? '' : 'none',
                }"
                :exportable="exportColumns.fechaPago"
            >
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <span v-else>{{ data.fecha_pago }}</span>
                </template>
                <template #filter="{ filterModel }">
                    <InputText
                        v-model="filterModel.value"
                        type="text"
                        placeholder="Buscar por Fecha de Pago"
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
                <div class="flex flex-col md:flex-row gap-6 mt-4">
                    <FloatLabel variant="on" class="w-full md:w-1/2">
                        <Dropdown
                            v-model="selectedBranchOffice"
                            :options="props.branchOffices"
                            optionLabel="code"
                            placeholder="Selecciona planta"
                            class="w-full"
                        />
                        <label>Plantas</label>
                    </FloatLabel>
                    <FloatLabel variant="on" class="w-full md:w-1/2">
                        <InputText
                            type="week"
                            v-model="selectedWeek"
                            class="w-full"
                        />
                        <label>Semana</label>
                    </FloatLabel>
                </div>

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
