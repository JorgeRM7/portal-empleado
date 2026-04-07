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
import * as XLSX from "xlsx";

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
    };
};

initFilters();

// Acciones
// ID - id
// Clave empleado - employee_id
// Nombre empleado - employee_name
// Puesto nuevo - new_position_name
// Departamento nuevo - new_department_name
// Estado - status
// Fecha inicial - start_training
// Fecha final - end_training
// Evaluación - evaluacion_res

//Columnas visibles
const showColumns = ref({
    acciones: true,
    id: true,
    employee_id: true,
    employee_name: true,
    new_position_name: true,
    new_department_name: true,
    status: true,
    start_training: true,
    end_training: true,
    evaluacion_res: true,
});

//Columnas fijas
const frozenColumns = ref({
    acciones: true,
    id: false,
    employee_id: false,
    employee_name: false,
    new_position_name: false,
    new_department_name: false,
    status: false,
    start_training: false,
    end_training: false,
    evaluacion_res: false,
});

const exportColumns = ref({
    acciones: true,
    id: true,
    employee_id: true,
    employee_name: true,
    new_position_name: true,
    new_department_name: true,
    status: true,
    start_training: true,
    end_training: true,
    evaluacion_res: true,
});

// Traducciones
const columnLabels = {
    acciones: "Acciones",
    id: "ID",
    employee_id: "Clave empleado",
    employee_name: "Nombre empleado",
    new_position_name: "Puesto nuevo",
    new_department_name: "Departamento nuevo",
    status: "Estado",
    start_training: "Fecha inicial",
    end_training: "Fecha final",
    evaluacion_res: "Evaluación",
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
        const response = await axios.get("/employee/employee-salary-adjustments/filter-data-weekly", {
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
const isActive = (row) => row.estado_empleado !== 'termination';
const isPending = (row) => !row.approved_at && !row.declined_at;
const score = (row) => Number(row.evaluacion_puntos || 0);

const rowCan = {
    hacerEvaluacion: (row) =>
        row.adjust_salary_confirmation &&
        !row.fecha_contrato &&
        isActive(row),

    descargarEvaluacion: (row) =>
        row.fecha_contrato &&
        isActive(row),

    subirDocumentos: (row) =>
        isPending(row) &&
        isActive(row) &&
        !row.adjust_doc_date &&
        row.adjust_salary_confirmation,

    aprobar: (row) =>
        isPending(row) &&
        isActive(row) &&
        row.adjust_salary_confirmation &&
        row.adjust_doc_date &&
        score(row) >= 50,

    rechazar: (row) =>
        isPending(row) &&
        isActive(row) &&
        score(row) <= 39 &&
        row.evaluacion_puntos,

    reaplicar: (row) =>
        isPending(row) &&
        isActive(row) &&
        score(row) >= 40 &&
        score(row) <= 49,
};

const hacerEvaluacion = (row) => {
    window.open(`/employee/employee-salary-adjustments/${row.id}/evaluation`, '_blank');
};

const descargarEvaluacion = (row) => {
    window.open(`/employee/employee-salary-adjustments/${row.id}/get-evaluation`, '_blank');
};

const subirDocumentos = (row) => {};
const aprobar = (row) => {};
const rechazar = (row) => {};
const reaplicar = (row) => {};

const saveColumns = () => {
    columnsDialog.value = false;

    // 1️⃣ Tomar datos filtrados
    const filteredData = dt.value?.processedData || rows.value;

    // 2️⃣ Obtener columnas visibles
    const visibleColumns = Object.keys(exportColumns.value)
        .filter(key => exportColumns.value[key] && key !== "acciones");

    // 3️⃣ Construir datos exportables con headers personalizados
    const exportData = filteredData.map(row => {
        const newRow = {};

        visibleColumns.forEach(col => {
            let value = row[col];

            // valores no directos
            if (col === "status") {
                value = getStatusLabel(row.status);
            }

            if (typeof value === "string") {
                value = value
                    .replace(/[\r\n]+/g, " ")
                    .replace(/\s+/g, " ")
                    .trim();
            }

            // Conversión opcional de booleanos
            if (typeof value === "boolean") {
                value = value ? 1 : 0;
            }

            newRow[columnLabels[col]] = value;
        });

        return newRow;
    });

    const worksheet = XLSX.utils.json_to_sheet(exportData);

    // 4️⃣ Auto ancho con máximo
    const colWidths = Object.keys(exportData[0] || {}).map(key => ({
        wch: Math.min(
            50,
            Math.max(
                key.length,
                ...exportData.map(r => (r[key]?.toString().length || 0))
            )
        )
    }));

    worksheet["!cols"] = colWidths;

    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Semanal_ajustes_promociones");

    XLSX.writeFile(workbook, "Semanal_ajustes_promociones.xlsx");
};

//Diálogo de selección de columnas
const columnsDialog = ref(false);
const submitted = ref(false);








</script>

<template>
    <ConfirmDialog />
    
    <Toolbar>
        <template #start>
            <Button
                type="button"
                icon="pi pi-upload"
                label="Exportar XLSX"
                severity="secondary"
                class="mt-2 ml-2"
                @click="columnsDialog = true"
            />
        </template>

        <template #end>
        </template>
    </Toolbar>
    <DataTable ref="dt" :value="rows" dataKey="id" :paginator="true" :rows="10"
        scrollable scrollHeight=800px v-model:filters="filters" filterDisplay="menu"
        exportFilename="Semanal" :globalFilterFields="[
            'id',
            'employee_id',
            'employee_name',
            'new_position_name',
            'new_department_name',
            'status_label',
            'start_training',
            'end_training',
            'evaluacion_res',
        ]"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} datos de prueba" removableSort
        :rowsPerPageOptions="[10, 20, 50, 100]" :loading="massProcessing">
        <template #header>
            <div class="flex flex-wrap gap-2 items-end justify-between mb-6">
                <div>
                    <h4 class="m-0">Semanal</h4>
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

        <!-- Acciones -->
        <Column header="Acciones" style="min-width: 14rem" :exportable="false" :frozen="frozenColumns.acciones"
            :style="{
                display: showColumns.acciones ? '' : 'none',
            }">
            <template #body="slotProps">
                <Skeleton v-if="loading" />
                <div v-else>
                    <!-- evaluación -->
                    <Button v-if="rowCan.hacerEvaluacion(slotProps.data)"
                        icon="pi pi-list-check" class="mr-2" severity="contrast" v-tooltip.top="'Hacer Evaluación'" rounded
                        :disabled="processingRows[slotProps.data.id]"
                        :loading="processingRows[slotProps.data.id]"
                        @click="() => {
                            hacerEvaluacion(slotProps.data);
                        }" 
                    />

                    <!-- descargar evaluación -->
                    <Button v-if="rowCan.descargarEvaluacion(slotProps.data)"
                        icon="pi pi-file-check" class="mr-2" severity="contrast" v-tooltip.top="'Descargar Evaluación'" rounded
                        :disabled="processingRows[slotProps.data.id]"
                        :loading="processingRows[slotProps.data.id]"
                        @click="() => {
                            descargarEvaluacion(slotProps.data);
                        }" 
                    />

                    <!-- Subir documentos -->
                    <Button v-if="rowCan.subirDocumentos(slotProps.data)"
                        icon="pi pi-file-import" class="mr-2" severity="secondary" v-tooltip.top="'Subir firmas'" rounded
                        :disabled="processingRows[slotProps.data.id]"
                        :loading="processingRows[slotProps.data.id]"
                        @click="() => {
                            subirDocumentos(slotProps.data);
                        }" 
                    />

                    <!-- Aprobar -->
                    <Button
                        v-if="rowCan.aprobar(slotProps.data)"
                        icon="pi pi-check" class="mr-2" severity="success" v-tooltip.top="'Aprobar'" rounded
                        :disabled="processingRows[slotProps.data.id]"
                        :loading="processingRows[slotProps.data.id]"
                        @click="aprobar(slotProps.data)"
                    />

                    <!-- Rechazar -->
                    <Button
                        v-if="rowCan.rechazar(slotProps.data)"
                        icon="pi pi-times" class="mr-2" severity="danger" v-tooltip.top="'Rechazar'" rounded
                        :disabled="processingRows[slotProps.data.id]"
                        :loading="processingRows[slotProps.data.id]"
                        @click="rechazar(slotProps.data)"
                    />

                    <!-- Reaplicar -->
                    <Button
                        v-if="rowCan.reaplicar(slotProps.data)"
                        icon="pi pi-refresh" class="mr-2" severity="warning" v-tooltip.top="'Reaplicar'" rounded
                        :disabled="processingRows[slotProps.data.id]"
                        :loading="processingRows[slotProps.data.id]"
                        @click="reaplicar(slotProps.data)"
                    />
                </div>
            </template>
        </Column>

        <!-- ID - id -->
        <Column field="id" header="ID" sortable filter :frozen="frozenColumns.id" :style="{
            display: showColumns.id ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false" :exportable="exportColumns.id">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <span v-else>{{ data.id }}</span>
            </template>
            <template #filter="{ filterModel }">
                <InputText v-model="filterModel.value" placeholder="Buscar por id" />
            </template>
        </Column>
        
        <!-- Clave empleado - employee_id -->
        <Column field="employee_id" header="Clave empleado" sortable filter :frozen="frozenColumns.employee_id" :style="{
            display: showColumns.employee_id ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false" :exportable="exportColumns.employee_id">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <span v-else>{{ data.employee_id }}</span>
            </template>
        </Column>
        
        <!-- Nombre empleado - employee_name -->
        <Column field="employee_name" header="Nombre empleado" sortable filter :frozen="frozenColumns.employee_name" :style="{
            display: showColumns.employee_name ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false" :exportable="exportColumns.employee_name">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <span v-else>{{ data.employee_name }}</span>
            </template>
        </Column>

        <!-- Estado - status -->
        <Column field="status" header="Estado" sortable filter :frozen="frozenColumns.status" :style="{
            display: showColumns.status ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false" :exportable="exportColumns.status">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <Tag
                    v-else
                    :value="getStatusLabel(data.status)"
                    :severity="getStatusSeverity(data.status)"
                />
            </template>
        </Column>

        <!-- Puesto nuevo - new_position_name -->
        <Column field="new_position_name" header="Nuevo puesto" sortable filter :frozen="frozenColumns.new_position_name" :style="{
            display: showColumns.new_position_name ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false" :exportable="exportColumns.new_position_name">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <span v-else>{{ data.new_position_name }}</span>
            </template>
            <template #filter="{ filterModel }">
                <InputText v-model="filterModel.value" placeholder="Buscar por nuevo puesto" />
            </template>
        </Column>
        
        <!-- Departamento nuevo - new_department_name -->
        <Column field="new_department_name" header="Nuevo departamento" sortable filter :frozen="frozenColumns.new_department_name" :style="{
            display: showColumns.new_department_name ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false" :exportable="exportColumns.new_department_name">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <span v-else>{{ data.new_department_name }}</span>
            </template>
        </Column>
        
        <!-- Fecha inicial - start_training -->
        <Column field="start_training" header="Fecha inicial" sortable filter :frozen="frozenColumns.start_training" :style="{
            display: showColumns.start_training ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false" :exportable="exportColumns.start_training">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <span v-else>{{ data.start_training }}</span>
            </template>
            <template #filter="{ filterModel }">
                <InputText v-model="filterModel.value" placeholder="Buscar por fecha inicial" />
            </template>
        </Column>
        
        <!-- Fecha final - end_training -->
        <Column field="end_training" header="Fecha final" sortable filter :frozen="frozenColumns.end_training" :style="{
            display: showColumns.end_training ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false" :exportable="exportColumns.end_training">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <span v-else>{{ data.end_training }}</span>
            </template>
            <template #filter="{ filterModel }">
                <InputText v-model="filterModel.value" placeholder="Buscar por fecha final" />
            </template>
        </Column>

        <!-- Evaluación - evaluacion_res -->
        <Column field="evaluacion_res" header="Evaluación" sortable filter :frozen="frozenColumns.evaluacion_res" :style="{
            display: showColumns.evaluacion_res ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false" :exportable="exportColumns.evaluacion_res">
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

    </DataTable>

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

    <!-- columnas a exportar -->
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
                <label :for="key" class="font-medium text-base">
                    <!-- {{ key.charAt(0).toUpperCase() + key.slice(1) }} -->
                    {{ columnLabels[key] ?? key }}
                </label>
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
    
</template>
