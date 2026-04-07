<script setup>
import { onMounted, ref, computed, watch, reactive } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { router, useForm } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { useLayout } from "@/Layouts/composables/layout";
import { useToastService } from "@/Stores/toastService";
import { Dialog } from "primevue";
import axios from "axios";
import { useAuthz } from "@/composables/useAuthz";

const { can } = useAuthz();
const { isDark } = useLayout();
const { showValidationError, showErrorCustom } = useToastService();

const props = defineProps({
    Plantas: Array,
    Departamentos: Array,
    Empleados: Array,
});
// console.log(props.Empleados);

//Inicializar filtros globales de tabla
const filters = ref({});

// ----- ESTADO GENERAL -----
const rows = ref([
    {
        id: null
    },
]);
const loading = ref(true);

//Función para inicializar filtros locales
const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        id: {
            operator: FilterOperator.OR,
            constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }],
        },
        new_position_name: {
            operator: FilterOperator.OR,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        start_training: {
            operator: FilterOperator.OR,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        end_training: {
            operator: FilterOperator.OR,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        evaluacion_res: {
            operator: FilterOperator.OR,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        adjust_state: {
            operator: FilterOperator.OR,
            constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }],
        },
        name_validator: {
            operator: FilterOperator.OR,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
    };
};

initFilters();

//Columnas visibles
const showColumns = ref({
    acciones: true,
    id: true,
    employee_id: true,
    employee_name: true,
    status: true,
    new_position_name: true,
    new_department_name: true,
    start_training: true,
    end_training: true,
    evaluacion_res: true,
    adjust_state: true,
    name_validator: true,
    planta: true,
});

//Columnas fijas
const frozenColumns = ref({
    acciones: true,
    id: false,
    employee_id: false,
    employee_name: false,
    status: false,
    new_position_name: false,
    new_department_name: false,
    start_training: false,
    end_training: false,
    evaluacion_res: false,
    adjust_state: false,
    name_validator: false,
    planta: false,
});

// Traducciones
const columnLabels = {
    acciones: "Acciones",
    id: "ID",
    employee_id: "Clave empleado",
    employee_name: "Nombre empleado",
    status: "Estado",
    new_position_name: "Nuevo puesto",
    new_department_name: "Nuevo departamento",
    start_training: "Fecha inicial",
    end_training: "Fecha final",
    evaluacion_res: "Evaluación",
    adjust_state: "Estado ajuste",
    name_validator: "Quien Válido",
    planta: "Planta",
};

//Diálogo de filtros adicionales
const otherFilterDialog = ref(false);

// ----- FILTROS UI -----
const selectedBranchOffice = ref(null);
const selectedWeek = ref(null);
const selectedDepartments = ref([]);
const selectedStatus = ref(null);
const selectedEmployees = ref([]);
const includeDeleted = ref(false);

// ----- FILTROS ACTIVOS (CHIPS) -----
const activeFilters = reactive({
    branchOffice: null,
    week: null,
    department: [],
    status: null,
    employee: [],
    includeDeleted: false,
});

// ----- APLICAR FILTROS ----- 
const applyFilters = () => {
    const depIds = selectedDepartments.value.map(d =>
        (d && typeof d === 'object') ? d.id : d
    );
    const empIds = selectedEmployees.value.map(e =>
        (e && typeof e === 'object') ? e.id : e
    );

    // Actualizar CHIPS
    activeFilters.branchOffice = selectedBranchOffice.value;
    activeFilters.week = selectedWeek.value;
    activeFilters.department = [...selectedDepartments.value];
    activeFilters.status = selectedStatus.value;
    activeFilters.employee = [...selectedEmployees.value];
    activeFilters.includeDeleted = includeDeleted.value;

    // console.log(activeFilters);
    loadData({
        branchOfficeId: selectedBranchOffice.value?.id ?? null,
        week: selectedWeek.value,
        departmentIds: depIds,
        status: selectedStatus.value?.value ?? null,
        employeeIds: empIds,
        includeDeleted: includeDeleted.value,
    });

    otherFilterDialog.value = false;
};

// ----- REMOVE FILTER -----
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
            selectedDepartments.value = [];
            activeFilters.department = [];
            break
        
        case 'status':
            selectedStatus.value = null;
            activeFilters.status = null;
            break

        case 'employee':
            selectedEmployees.value = [];
            activeFilters.employee = [];
            break

        case 'includeDeleted':
            includeDeleted.value = false;
            activeFilters.includeDeleted = false;
            break
    }

    applyFilters();
}

// ----- SEMANA ACTUAL -----
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

// ----- LIMPIAR FILTROS -----
const clearFilter = () => {
    initFilters();
    defaultFiltered();
};

// ----- CARGAR TABLA -----
const loadData = async (params = {}) => {
    loading.value = true;

    try {
        const response = await axios.get("/employee/employee-salary-adjustments/filter-data", {
            params: {
                branch_office_id: params.branchOfficeId ?? null,
                week: params.week ?? null,
                department_id: params.departmentIds ?? [],
                status: params.status ?? null,
                employee_id: params.employeeIds ?? [],
                eliminated: params.includeDeleted ? 1 : 0,
            },
        });

        // console.log(response.data);
        rows.value = response.data ?? [];

    } catch (error) {
        console.error("Error cargando la informacion de la tabla:", error);
    } finally {
        loading.value = false;
        massProcessing.value = false;
    }
};

const defaultFiltered = () => {

    // Reset base
    selectedDepartments.value = [];
    selectedEmployees.value = [];
    selectedStatus.value = null;
    includeDeleted.value = false;

    activeFilters.department = [];
    activeFilters.employee = [];
    activeFilters.status = null;
    activeFilters.includeDeleted = false;

    // Semana actual
    selectedWeek.value = getCurrentISOWeek();
    activeFilters.week = selectedWeek.value;

    let branch = null;

    const storedBranch = localStorage.getItem("selectedBranchOffice");

    if (storedBranch && props.Plantas?.length) {
        const stored = JSON.parse(storedBranch);

        branch = props.Plantas.find(b => b.id === stored.id) ?? null;

        if (branch) {
            selectedBranchOffice.value = branch;
            activeFilters.branchOffice = branch;
        }
    }

    loadData({
        branchOfficeId: branch?.id ?? null,
        week: selectedWeek.value ?? null
    });
};

onMounted(() => {

    defaultFiltered();
});



//Referencia a la tabla de datos
const dt = ref(null);
const confirm = useConfirm();

//Referencias a los popovers
const op = ref();
const opMostrarColumnas = ref();
const opFijarColumnas = ref();

const toggleMostrarColumnas = (event) => {
    opMostrarColumnas.value.toggle(event);
};

const toggleFijarColumnas = (event) => {
    opFijarColumnas.value.toggle(event);
};

const editItem = (id) => {
    if (processingRows.value[id]) return;

    processingRows.value[id] = true;

    router.get(`/employee/employee-salary-adjustments/${id}/edit`, {
        onFinish: () => {
            processingRows.value[id] = false;
        }
    });
};

const processingRows = ref({});

const itemToDelete = ref();
const deleteDialog = ref();

const deleteItem = () => {
    
    const id = itemToDelete.value.id;

    processingRows.value[id] = true;
    loading.value = true;

    router.delete(`/employee/employee-salary-adjustments/${id}`, {
        preserveScroll: true,

        onSuccess: () => {
            rows.value = rows.value.filter(row => row.id !== id);
            loadData({
                branchOfficeId: activeFilters.branchOffice?.id ?? null,
                employeeIds: activeFilters.employee.map(e => e.id),
                week: activeFilters.week ?? null,
                salaryPaymentId: activeFilters.earningDeduction?.id ?? null
            });
        },

        onFinish: () => {
            processingRows.value[id] = false;
            loading.value = false;
            deleteDialog.value = false;
        }
    });
};

const massProcessing = ref();

const adjustmentStatuses = [
    { value: 1, label: 'Nuevo ingreso' },
    { value: 2, label: 'Promoción' },
    { value: 3, label: 'Cambio de puesto' },
    { value: 4, label: 'Prórroga' },
    { value: 5, label: 'Desistido' },
    { value: 6, label: 'Rechazado' },
];

// Tags y labels de la tabla
// ----- Estado -----
const statusMap = {
    1: { label: 'NUEVO INGRESO', severity: 'info' },      // azul
    2: { label: 'PROMOCIÓN', severity: 'success' },       // verde
    3: { label: 'CAMBIO DE PUESTO', severity: 'info' },   // celeste
    4: { label: 'PRÓRROGA', severity: 'secondary' },      // oscuro
    5: { label: 'DESISTIDO', severity: 'warning' },       // amarillo
    6: { label: 'RECHAZADO', severity: 'danger' }         // rojo
}

const adjustStates = [
    { value: 'approved', label: 'APROBADO'},
    { value: 'desisted', label: 'DESISTIDO'},
    { value: 'in_process', label: 'EN PROCESO'},
];

const adjustStatesTags = {
    approved: {
        label: 'APROBADO',
        severity: 'success'
    },
    desisted: {
        label: 'DESISTIDO',
        severity: 'danger'
    },
    in_process: {
        label: 'EN PROCESO',
        severity: 'info'
    }
};

const getStatusLabel = (status) => {
    return statusMap[status]?.label ?? 'SIN ESTADO'
}

const getStatusSeverity = (status) => {
    return statusMap[status]?.severity ?? 'secondary'
}

// ----- Evaluación -----
const getEvaluacionSeverity = (value) => {
    switch (value) {
        case 'APROBATORIO':
        case 'APROBATORIO, CON PLAN DE MEJORA':
            return 'success'
        case 'PRORROGA':
            return 'warning'
        case 'REPROBATORIO':
            return 'danger'
        default:
            return 'secondary'
    }
}

const getEvaluacionLabel = (value) => {
    return value ?? 'SIN EVALUAR'
}

// ----- Estado ajuste -----
const getAdjustStateLabel = (state) => adjustStatesTags[state]?.label ?? state;

const getAdjustStateSeverity = (state) => adjustStatesTags[state]?.severity ?? 'secondary';

const filteredEmployees = computed(() => {

    if (!selectedBranchOffice.value) {
        return props.Empleados
    }

    return props.Empleados.filter(emp =>
        emp.branch_office_id === selectedBranchOffice.value.id
    )
})

// Asociar la planta a los empleados del select en filtros
watch(selectedBranchOffice, () => {
    selectedEmployees.value = []
})

// Aprobar sueldo
const itemValidarSueldo = ref();
const ValidarSueldoDialog = ref(false);

const formatCurrency = (value) => {
    if (value === undefined || value === null || isNaN(value)) {
        return '$0.00'
    }

    return Number(value).toLocaleString('es-MX', {
        style: 'currency',
        currency: 'MXN'
    })
}

const form = useForm({
    id: null,
    salarioDiarioNuevo: null,
    totalPercepciones: null,
    compensacionNuevo: null,
    netoSemanalNuevo: null,
    observaciones: '',
    acuerdo: true
})

const validarSueldoDefault = (data) =>{
    // console.log(data);
    
    itemValidarSueldo.value = data;
    form.id = data.id;
    form.salarioDiarioNuevo = null;
    form.totalPercepciones = null;
    form.compensacionNuevo = null;
    form.netoSemanalNuevo = null;
    form.observaciones = '';
    form.acuerdo = true;
}

const guardar = () => {
    // console.log(form.data());

    if (form.acuerdo === false) {
        if (!validateForm()) {
            showValidationError('Hay campos obligatorios sin completar');
            return;
        }
    }

    confirm.require({
        message: '¿Estás seguro de confirmar el sueldo?',
        header: 'Confirmar acción',
        icon: 'pi pi-exclamation-triangle',

        acceptLabel: 'Sí, confirmar',
        rejectLabel: 'Cancelar',

        acceptClass: 'p-button-success',
        rejectClass: 'p-button-secondary',

        accept: () => {

            loading.value = true;

            form.post('/employee/employee-salary-adjustments/validate-salary', {
                preserveScroll: true,
                onSuccess: () => {
                    // actualizar el registro en el front para evitar el delay de los filtros
                    const row = rows.value.find(r => r.id === form.id);
                    if (row) {
                        row.adjust_salary_confirmation = true;
                    }

                    ValidarSueldoDialog.value = false;
                    applyFilters();
                },
                onError: () => {
                    Object.values(form.errors).forEach(err => showErrorCustom(err));
                },
                onFinish: () => {
                    loading.value = false;
                }
            });
        },
    });
    
};

const frontErrors = reactive({});
const validateForm = () => {

    // Limpiar errores previos
    Object.keys(frontErrors).forEach(k => delete frontErrors[k]);

    // salarioDiarioNuevo
    if (!form.salarioDiarioNuevo && form.salarioDiarioNuevo < 0) {
        frontErrors.salarioDiarioNuevo = 'El campo es obligatorio';
    } else if (isNaN(parseFloat(form.salarioDiarioNuevo))) {
        frontErrors.salarioDiarioNuevo = 'El campo ser un número válido';
    }
    
    // totalPercepciones
    if (!form.totalPercepciones && form.totalPercepciones < 0) {
        frontErrors.totalPercepciones = 'El campo es obligatorio';
    } else if (isNaN(parseFloat(form.totalPercepciones))) {
        frontErrors.totalPercepciones = 'El campo ser un número válido';
    }
    
    // compensacionNuevo
    if (!form.compensacionNuevo && form.compensacionNuevo < 0) {
        frontErrors.compensacionNuevo = 'El campo es obligatorio';
    } else if (isNaN(parseFloat(form.compensacionNuevo))) {
        frontErrors.compensacionNuevo = 'El campo ser un número válido';
    }
    
    // netoSemanalNuevo
    if (!form.netoSemanalNuevo && form.netoSemanalNuevo < 0) {
        frontErrors.netoSemanalNuevo = 'El campo es obligatorio';
    } else if (isNaN(parseFloat(form.netoSemanalNuevo))) {
        frontErrors.netoSemanalNuevo = 'El campo ser un número válido';
    }
    
    // observaciones
    if (form.observaciones && String(form.observaciones).length > 1000) {
        frontErrors.observaciones = 'El campo debe ser maximo 1000 caracteres';
    }

    // Retorna true si no hay errores
    return Object.keys(frontErrors).length === 0;
};

const clearError = (path) => {
    if (frontErrors[path]) {
        delete frontErrors[path];
    }
};

// permisos de botones
const rowCan = {
    validarSueldo: (row) => 
        row.adjust_salary_confirmation === null,

    editar: (row) => 
        row.adjust_salary_confirmation === null
        && row.tipo_ajuste != 2,

    eliminar: (row) => 
        row.adjust_salary_confirmation === null,

    descargarFormato: (row) => 
        row.adjust_salary_confirmation 
        && !row.adjust_doc_date,

    descargarEvaluacion: (row) => 
        row.evaluacion_res,
};

const descargarFormato = (row) => {
    window.open(`/employee/employee-salary-adjustments/${row.id}/get-format`, '_blank');
};

const descargarEvaluacion = (row) => {
    window.open(`/employee/employee-salary-adjustments/${row.id}/get-evaluation`, '_blank');
};








</script>

<template>
    <ConfirmDialog />
    
    <Toolbar>
        <template #start>
            
        </template>

        <template #end>
            <Link href="/employee/employee-salary-adjustments/create">
            <Button v-if="(can('employee-salary-adjustments.create'))"
                label="Crear" icon="pi pi-plus-circle" severity="success" class="ml-2" />
            </Link>
        </template>
    </Toolbar>
    <DataTable ref="dt" :value="rows" dataKey="id" :paginator="true" :rows="10"
        scrollable scrollHeight=800px v-model:filters="filters" filterDisplay="menu"
        exportFilename="Ajustes salariales" :globalFilterFields="[
            'id',
            'employee_id',
            'employee_name',
            'status',
            'new_position_name',
            'new_department_name',
            'start_training',
            'end_training',
            'evaluacion_res',
            'adjust_state',
            'name_validator',
            'planta',
        ]"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} datos de prueba" removableSort
        :rowsPerPageOptions="[10, 20, 50, 100]" :loading="massProcessing">
        <template #header>
            <div class="flex flex-wrap gap-2 items-end justify-between mb-6">
                <div>
                    <h4 class="m-0">Ajustes salariales</h4>
                    <Button icon="pi pi-filter" rounded v-tooltip.top="'Mostrar mas filtros'"
                        @click="otherFilterDialog = true" />
                    <Button type="button" icon="pi pi-filter-slash" rounded severity="secondary"
                        class="mt-5 ml-2 mr-2" v-tooltip.top="'Limpiar filtros'" @click="clearFilter()" />
                </div>
                <div class="flex">
                    <IconField>
                        <InputIcon>
                            <i class="pi pi-search" />
                        </InputIcon>
                        <InputText v-model="filters['global'].value" placeholder="Buscar..." />
                    </IconField>
                    <Button type="button" rounded class="ml-2" icon="pi pi-sliders-v" severity="secondary"
                        @click="toggleMostrarColumnas($event)" />
                    <Button icon="pi pi-lock" rounded v-tooltip.top="'Alternar columnas fijas'" class="ml-2"
                        severity="secondary" @click="toggleFijarColumnas($event)" />

                    <Popover ref="opMostrarColumnas">
                        <div class="flex flex-col gap-4">
                            <div>
                                <span class="font-medium block mb-2">Mostrar/Ocultar Columnas</span>
                                <ul class="list-none p-0 m-0 flex flex-col">
                                    <li v-for="( value, key ) in showColumns" :key="key"
                                        class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                                        :binary="true">
                                        <Checkbox v-model="showColumns[key]" :inputId="key" :binary="true" />
                                        <label :for="key" class="font-medium text-base">
                                            {{ columnLabels[key] ?? key }}
                                        </label>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </Popover>
                    <Popover ref="opFijarColumnas">
                        <div class="flex flex-col gap-4">
                            <div>
                                <span class="font-medium block mb-2">Fijar Columnas</span>
                                <ul class="list-none p-0 m-0 flex flex-col">
                                    <li v-for="(value, key ) in frozenColumns" :key="key"
                                        class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                                        :binary="true">
                                        <Checkbox v-model="frozenColumns[key]" :inputId="key" :binary="true" />
                                        <label :for="key" class="font-medium text-base">
                                            {{ columnLabels[key] ?? key }}
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
                    class="me-2"
                    @remove="removeFilter('branchOffice')"
                    removable
                    :removable="!loading"
                />
                <Chip
                    v-if="activeFilters.week"
                    :label="`Semana: ${activeFilters.week}`"
                    class="me-2"
                    @remove="removeFilter('week')"
                    removable
                    :removable="!loading"
                />
                <Chip
                    v-if="activeFilters.department.length"
                    :label="`Depto: ${activeFilters.department.map(d => d.name).join(', ')}`"
                    class="me-2"
                    @remove="removeFilter('department')"
                    removable
                    :removable="!loading"
                />
                <Chip
                    v-if="activeFilters.status"
                    :label="`Estado: ${activeFilters.status.label}`"
                    class="me-2"
                    @remove="removeFilter('status')"
                    removable
                    :removable="!loading"
                />
                <Chip
                    v-if="activeFilters.employee?.length"
                    :label="`Empleados: ${activeFilters.employee.map(emp => emp.id).join(', ')}`"
                    class="me-2"
                    @remove="removeFilter('employee')"
                    removable
                    :removable="!loading"
                />
                <Chip
                    v-if="activeFilters.includeDeleted"
                    label="Incluir eliminados"
                    class="me-2"
                    @remove="removeFilter('includeDeleted')"
                    removable
                    :removable="!loading"
                />
            </div>
        </template>

        <!-- acciones - "Acciones" -->
        <Column header="Acciones" style="min-width: 14rem" :exportable="false" :frozen="frozenColumns.acciones"
            :style="{
                display: showColumns.acciones ? '' : 'none',
            }">
            <template #body="slotProps">
                <Skeleton v-if="loading" />
                <div v-else>
                    <Button v-if="can('employee-salary-adjustments.edit') && rowCan.editar(slotProps.data)" 
                        icon="pi pi-pencil" class="mr-2" severity="warn" v-tooltip.top="'Editar'" rounded
                        :disabled="processingRows[slotProps.data.id]"
                        :loading="processingRows[slotProps.data.id]" @click="editItem(slotProps.data.id)" />
                    <Button v-if="can('employee-salary-adjustments.delete') && rowCan.eliminar(slotProps.data)"
                        icon="pi pi-trash" severity="danger" v-tooltip.top="'Eliminar'" class="mr-2" rounded
                        :disabled="processingRows[slotProps.data.id]"
                        :loading="processingRows[slotProps.data.id]"
                        @click="() => {
                            itemToDelete = slotProps.data;
                            deleteDialog = true;
                        }"
                    />
                    <Button v-if="rowCan.validarSueldo(slotProps.data)"
                        icon="pi pi-money-bill" class="mr-2" severity="info" v-tooltip.top="'Validar Sueldo'" rounded
                        :disabled="processingRows[slotProps.data.id]"
                        :loading="processingRows[slotProps.data.id]"
                        @click="() => {
                            validarSueldoDefault(slotProps.data);
                            ValidarSueldoDialog = true;
                        }" 
                    />
                    <Button v-if="rowCan.descargarFormato(slotProps.data)"
                        icon="pi pi-file-pdf" class="mr-2" severity="contrast" v-tooltip.top="'Descargar Formato'" rounded
                        :disabled="processingRows[slotProps.data.id]"
                        :loading="processingRows[slotProps.data.id]"
                        @click="() => {
                            descargarFormato(slotProps.data);
                        }" 
                    />
                    <Button v-if="rowCan.descargarEvaluacion(slotProps.data)"
                        icon="pi pi-file-check" class="mr-2" severity="secondary" v-tooltip.top="'Descargar Evaluación'" rounded
                        :disabled="processingRows[slotProps.data.id]"
                        :loading="processingRows[slotProps.data.id]"
                        @click="() => {
                            descargarEvaluacion(slotProps.data);
                        }" 
                    />
                </div>
            </template>
        </Column>

        <!-- id - "ID" -->
        <Column field="id" header="ID" sortable filter :frozen="frozenColumns.id" :style="{
            display: showColumns.id ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <span v-else>{{ data.id }}</span>
            </template>
            <template #filter="{ filterModel }">
                <InputText v-model="filterModel.value" placeholder="Buscar por id" />
            </template>
        </Column>
        
        <!-- employee_id - "Clave empleado" -->
        <Column field="employee_id" header="Clave empleado" sortable filter :frozen="frozenColumns.employee_id" :style="{
            display: showColumns.employee_id ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <span v-else>{{ data.employee_id }}</span>
            </template>
        </Column>
        
        <!-- employee_name - "Nombre empleado" -->
        <Column field="employee_name" header="Nombre empleado" sortable filter :frozen="frozenColumns.employee_name" :style="{
            display: showColumns.employee_name ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <span v-else>{{ data.employee_name }}</span>
            </template>
        </Column>

        <!-- status - "Estado" -->
        <Column field="status" header="Estado" sortable filter :frozen="frozenColumns.status" :style="{
            display: showColumns.status ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <Tag
                    v-else
                    :value="getStatusLabel(data.status)"
                    :severity="getStatusSeverity(data.status)"
                />
            </template>
        </Column>

        <!-- new_position_name - "Nuevo puesto" -->
        <Column field="new_position_name" header="Nuevo puesto" sortable filter :frozen="frozenColumns.new_position_name" :style="{
            display: showColumns.new_position_name ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <span v-else>{{ data.new_position_name }}</span>
            </template>
            <template #filter="{ filterModel }">
                <InputText v-model="filterModel.value" placeholder="Buscar por nuevo puesto" />
            </template>
        </Column>
        
        <!-- new_department_name - "Nuevo departamento" -->
        <Column field="new_department_name" header="Nuevo departamento" sortable filter :frozen="frozenColumns.new_department_name" :style="{
            display: showColumns.new_department_name ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <span v-else>{{ data.new_department_name }}</span>
            </template>
        </Column>
        
        <!-- start_training - "Fecha inicial" -->
        <Column field="start_training" header="Fecha inicial" sortable filter :frozen="frozenColumns.start_training" :style="{
            display: showColumns.start_training ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <span v-else>{{ data.start_training }}</span>
            </template>
            <template #filter="{ filterModel }">
                <InputText v-model="filterModel.value" placeholder="Buscar por fecha inicial" />
            </template>
        </Column>
        
        <!-- end_training - "Fecha final" -->
        <Column field="end_training" header="Fecha final" sortable filter :frozen="frozenColumns.end_training" :style="{
            display: showColumns.end_training ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <span v-else>{{ data.end_training }}</span>
            </template>
            <template #filter="{ filterModel }">
                <InputText v-model="filterModel.value" placeholder="Buscar por fecha final" />
            </template>
        </Column>

        <!-- evaluacion_res - "Evaluación" -->
        <Column field="evaluacion_res" header="Evaluación" sortable filter :frozen="frozenColumns.evaluacion_res" :style="{
            display: showColumns.evaluacion_res ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                
                <Tag
                    v-else
                    :value="getEvaluacionLabel(data.evaluacion_res)"
                    :severity="getEvaluacionSeverity(data.evaluacion_res)"
                />
            </template>
            <template #filter="{ filterModel }">
                <InputText v-model="filterModel.value" placeholder="Buscar evaluación" />
            </template>
        </Column>

        <!-- adjust_state - "Estado ajuste" -->
        <Column field="adjust_state" header="Estado ajuste" sortable filter :frozen="frozenColumns.adjust_state" :style="{
            display: showColumns.adjust_state ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false">
            <template #body="{ data }">
                <Skeleton v-if="loading" />

                <Tag
                    v-else
                    :value="getAdjustStateLabel(data.adjust_state)"
                    :severity="getAdjustStateSeverity(data.adjust_state)"
                />
            </template>
            <template #filter="{ filterModel }">
                <Select
                    v-model="filterModel.value"
                    :options="adjustStates"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Estado ajuste"
                />
            </template>
        </Column>

        <!-- name_validator - "Quien Válido" -->
        <Column field="name_validator" header="Quien Válido" sortable filter :frozen="frozenColumns.name_validator" :style="{
            display: showColumns.name_validator ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false">
            <template #body="{ data }">
                <Skeleton v-if="loading" />

                <span v-else>
                    {{ data.name_validator }}
                </span>
            </template>
            <template #filter="{ filterModel }">
                <InputText v-model="filterModel.value" placeholder="Buscar quien válido" />
            </template>
        </Column>

        <!-- planta - "Planta" -->
        <Column field="planta" header="Planta" sortable filter :frozen="frozenColumns.planta" :style="{
            display: showColumns.planta ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <span v-else>{{ data.planta }}</span>
            </template>
        </Column>

    </DataTable>

    <!-- Otros filtros -->
    <Dialog
        v-model:visible="otherFilterDialog"
        :style="{ width: '450px' }"
        header="Seleccionar filtros adicionales"
        :modal="true"
    >
        <div class="flex flex-col gap-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div class="field">
                    <label class="block font-bold mb-2">
                        Planta
                    </label>
                    <Select
                        v-model="selectedBranchOffice"
                        :options="props.Plantas"
                        optionLabel="code"
                        filter
                        placeholder="Selecciona planta"
                        class="w-full"
                        showClear
                    />
                </div>

                <div class="field">
                    <label class="block font-bold mb-2">
                        Semana
                    </label>
                    <InputText
                        type="week"
                        v-model="selectedWeek"
                        class="w-full"
                    />
                </div>

                <div class="field">
                    <label class="block font-bold mb-2">
                        Departamentos
                    </label>
                    <MultiSelect
                        v-model="selectedDepartments"
                        :options="props.Departamentos"
                        optionLabel="name"
                        filter
                        display="chip"
                        class="w-full"
                        placeholder="Departamentos"
                        :maxSelectedLabels="2"
                        showClear
                    />
                </div>
                
                <div class="field">
                    <label class="block font-bold mb-2">
                        Estado
                    </label>
                    <Select
                        v-model="selectedStatus"
                        :options="adjustmentStatuses"
                        optionLabel="label"
                        filter
                        display="chip"
                        class="w-full"
                        placeholder="Estado"
                        showClear
                    />
                </div>
                
                <div class="field md:col-span-2">
                    <label class="block font-bold mb-2">
                        Empleados
                    </label>
                    <Multiselect
                        v-model="selectedEmployees"
                        :options="filteredEmployees"
                        optionLabel="full_name"
                        filter
                        :filterFields="['full_name', 'id']"
                        placeholder="Selecciona un empleado"
                        class="w-full"
                        display="chip"
                        showClear
                        :maxSelectedLabels="2"
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
                </div>

            </div>

        </div>

        <template #footer>
            <Button
                label="Cancelar"
                severity="danger"
                @click="otherFilterDialog = false"
            />
            <Button
                icon="pi pi-filter"
                label="Filtrar"
                @click="applyFilters"
            />
        </template>
    </Dialog>

    <!-- Borrar -->
    <Dialog
        v-model:visible="deleteDialog"
        :style="{ width: '600px' }"
        header="Confirmar eliminación"
        :modal="true"
    >    
        <div :class="{
            'bg-red-50 border-l-4 border-red-500 p-4 rounded': !isDark,
            'bg-red-950 border-l-4 border-red-500 p-4 rounded': isDark,
        }">
            
            <div class="flex items-center">
                <i
                    class="pi pi-exclamation-triangle text-red-600 text-3xl mr-3"
                ></i>
                <div>
                    <h3 :class="{
                        'font-bold text-red-800': !isDark,
                        'font-bold text-red-200': isDark,
                    }">
                        ¿Estás seguro de eliminar el registro con el identificador ({{itemToDelete.id }}) ?
                    </h3>
                    <p :class="{
                        'text-sm text-red-700': !isDark,
                        'text-sm text-red-300': isDark,
                    }">
                        Si realizas esta acción se borrará el registro y no se podrá recuperar
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
                @click="deleteItem"
                severity="danger"
                :loading="loading"
            />
        </template>
    </Dialog>

    <!-- Validar sueldo -->
    <Dialog
        v-model:visible="ValidarSueldoDialog"
        :style="{ width: '700px' }"
        header="Confirmar sueldo"
        :modal="true"
    >
        <div v-if="itemValidarSueldo"
            :class="{
                'bg-blue-50 border-l-4 border-blue-500 p-4 rounded': !isDark,
                'bg-blue-950 border-l-4 border-blue-500 p-4 rounded': isDark,
            }"
        >

            <!-- HEADER -->
            <div class="flex items-start mb-4">
                <i class="pi pi-dollar text-blue-600 text-3xl mr-3"></i>
                <div>
                    <h3 :class="{
                        'font-bold text-blue-800': !isDark,
                        'font-bold text-blue-200': isDark,
                    }">
                        Confirmación de sueldo
                    </h3>
                    <p :class="{
                        'text-base text-blue-700': !isDark,
                        'text-base text-blue-300': isDark,
                    }">
                        Revisa la información antes de confirmar o modificar
                    </p>
                </div>
            </div>

            <!-- INFO EMPLEADO -->
            <div class="grid grid-cols-2 gap-4 mb-4 text-xl">
                <div>
                    <p><strong>Clave:</strong> {{ itemValidarSueldo.employee_id }}</p>
                    <p><strong>Nombre:</strong> {{ itemValidarSueldo.employee_name }}</p>
                </div>
                <div>
                    <p><strong>Puesto:</strong> {{ itemValidarSueldo.new_position_name }}</p>
                    <p><strong>Departamento:</strong> {{ itemValidarSueldo.new_department_name }}</p>
                </div>
            </div>

            <!-- DETALLES ECONÓMICOS -->
            <div class="grid grid-cols-3 gap-4 mb-4 text-xl">
                <div>
                    <p class="font-semibold">Salario diario</p>
                    <p>{{ formatCurrency(itemValidarSueldo.daily_salary ?? 0) }}</p>
                </div>
                <div>
                    <p class="font-semibold">Neto semanal</p>
                    <p>{{ formatCurrency(itemValidarSueldo.net_in_adjust ?? 0) }}</p>
                </div>
                <div>
                    <p class="font-semibold">Compensación</p>
                    <p>{{ formatCurrency(itemValidarSueldo.compensation ?? 0) }}</p>
                </div>
            </div>

            <!-- FECHA -->
            <div class="mb-4 text-xl">
                <strong>Fin capacitación:</strong> {{ itemValidarSueldo.end_training }}
            </div>

            <!-- DECISIÓN -->
            <div class="mb-4">
                <p class="font-semibold mb-2 text-xl">¿Está de acuerdo con el sueldo?</p>

                <div class="flex gap-4">
                    <div class="flex items-center gap-2">
                        <RadioButton v-model="form.acuerdo" inputId="acuerdo-si" :value="true" />
                        <label for="acuerdo-si">Sí</label>
                    </div>

                    <div class="flex items-center gap-2">
                        <RadioButton v-model="form.acuerdo" inputId="acuerdo-no" :value="false" />
                        <label for="acuerdo-no">No</label>
                    </div>
                </div>
            </div>

            <!-- FORMULARIO -->
            <div v-if="form.acuerdo === false" class="grid grid-cols-2 gap-3 mt-3">

                <div class="mb-4">
                    <label class="block font-bold mb-2">
                        Salario diario <span class="text-red-500">*</span>
                    </label>
                    <InputNumber v-model="form.salarioDiarioNuevo"
                        placeholder="Salario diario"
                        mode="currency" currency="MXN" locale="es-MX"
                        class="w-full"
                        :min="0"
                        :inputClass="{ 'p-invalid': frontErrors.salarioDiarioNuevo }" 
                        @blur="clearError('salarioDiarioNuevo')"
                    />
                    <Message
                        v-if="frontErrors.salarioDiarioNuevo"
                        severity="error"
                        size="medium"
                        variant="simple"
                    >
                        {{ frontErrors.salarioDiarioNuevo }}
                    </Message>
                </div>

                <div class="mb-4">
                    <label class="block font-bold mb-2">
                        Total percepciones <span class="text-red-500">*</span>
                    </label>
                    <InputNumber v-model="form.totalPercepciones"
                        placeholder="Total percepciones"
                        mode="currency" currency="MXN" locale="es-MX"
                        class="w-full"
                        :min="0" 
                        :inputClass="{ 'p-invalid': frontErrors.totalPercepciones }" 
                        @blur="clearError('totalPercepciones')"
                    />
                    <Message
                        v-if="frontErrors.totalPercepciones"
                        severity="error"
                        size="medium"
                        variant="simple"
                    >
                        {{ frontErrors.totalPercepciones }}
                    </Message>
                </div>

                <div class="mb-4">
                    <label class="block font-bold mb-2">
                        Compensación <span class="text-red-500">*</span>
                    </label>
                    <InputNumber v-model="form.compensacionNuevo"
                        placeholder="Compensación"
                        mode="currency" currency="MXN" locale="es-MX" 
                        class="w-full"
                        :min="0"
                        :inputClass="{ 'p-invalid': frontErrors.compensacionNuevo }" 
                        @blur="clearError('compensacionNuevo')"
                    />
                    <Message
                        v-if="frontErrors.compensacionNuevo"
                        severity="error"
                        size="medium"
                        variant="simple"
                    >
                        {{ frontErrors.compensacionNuevo }}
                    </Message>
                </div>

                <div class="mb-4">
                    <label class="block font-bold mb-2">
                        Neto semanal <span class="text-red-500">*</span>
                    </label>
                    <InputNumber v-model="form.netoSemanalNuevo"
                        placeholder="Neto semanal"
                        mode="currency" currency="MXN" locale="es-MX" 
                        class="w-full"
                        :min="0"
                        :inputClass="{ 'p-invalid': frontErrors.netoSemanalNuevo }" 
                        @blur="clearError('netoSemanalNuevo')"
                    />
                    <Message
                        v-if="frontErrors.netoSemanalNuevo"
                        severity="error"
                        size="medium"
                        variant="simple"
                    >
                        {{ frontErrors.netoSemanalNuevo }}
                    </Message>
                </div>

                <div class="col-span-2 mb-4">
                    <label class="block font-bold mb-2">
                        Observaciones 
                    </label>
                    <Textarea v-model="form.observaciones"
                        rows="2"
                        autoResize
                        placeholder="Observaciones..."
                        class="w-full" 
                        :maxlength="1000"
                        :inputClass="{ 'p-invalid': frontErrors.observaciones }" 
                        @input="clearError('observaciones')"
                    />
                    <small class="text-gray-500">{{ form.observaciones?.length || 0 }}/1000</small>
                    <Message
                        v-if="frontErrors.observaciones"
                        severity="error"
                        size="medium"
                        variant="simple"
                    >
                        {{ frontErrors.observaciones }}
                    </Message>
                </div>
            </div>

            <!-- MENSAJE -->
            <div v-else class="mt-3">
                <Message severity="success" :closable="false">
                    Se mantendrá el sueldo del tabulador
                </Message>
            </div>

        </div>

        <!-- FOOTER -->
        <template #footer>
            <Button
                label="Cancelar"
                icon="pi pi-times"
                text
                severity="secondary"
                :loading="loading"
                @click="ValidarSueldoDialog = false"
            />
            <Button
                label="Guardar"
                icon="pi pi-check"
                severity="success"
                :loading="loading"
                @click="guardar"
            />
        </template>
    </Dialog>
    
</template>
