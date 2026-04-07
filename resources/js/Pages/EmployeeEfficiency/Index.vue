<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToastService } from "@/Stores/toastService";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import { onMounted, ref, computed, reactive, watch  } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { useNotifications } from "@/composables/useNotifications";
import { useAuthz } from "@/composables/useAuthz";
const { can } = useAuthz();

/* =====================
PROPS
===================== */
const props = defineProps({
    branchOffices: Array,
    departments: Array,
    employees: Array,
    position: Array,
    authEmployeeId: Number
});

// Select para estatus
const estatus = ref([
    { name: 'Llenado', code: 'Llenado' },
    { name: 'Pendiente', code: 'Pendiente' }
]);

const canEditRow = (row) => {
    if (!row || !row.jefe_ids) return false;
    const jefesIds = row.jefe_ids.split(',').map(id => Number(id.trim()));
    return jefesIds.includes(Number(props.authEmployeeId));
};

const formatJefesTooltip = (jefes) => {
    if (!jefes) return '';

    return jefes
        .split(',')
        .map(j => `• ${j.trim()}`)
        .join('\n');
};

const employeesEfficiency = ref([]);
const empleadosValidos = ref([]);

const empleadosSodexo = ref([])
const loadingSodexo = ref(false)

const selectedFile = ref(null)
const uploading = ref(false)

const saving = ref(false)

const approveDialog = ref(false);
const loadingEliminar = ref(false);
const eficienciaSeleccionada = ref(null);

const importResult = ref(null);
const resultDialog = ref(false);

const confirmarEliminar = (data) => {
    eficienciaSeleccionada.value = data;
    approveDialog.value = true;
};

const eliminarEmpleado = async () => {
    if (!eficienciaSeleccionada.value) return;

    loadingEliminar.value = true;
    try {
        // Ejecuta la eliminación
        await axios.delete(`/employee/efficiency/${eficienciaSeleccionada.value.id}`);

        showSuccess();

        approveDialog.value = false;
        eficienciaSeleccionada.value = null;

        await loadEfficiency();

        // Si necesitas que Inertia refresque los props del servidor:
        // router.reload();

    } catch (error) {
        const mensaje = error.response?.data?.message || 'No se pudo eliminar el registro';
        showError(mensaje);
        console.error(error);
    } finally {
        loadingEliminar.value = false;
    }
};

const onCustomUploader = async (event) => {
    const files = event.files;

    if (!files || !files.length) return;

    submitted.value = true;

    const formData = new FormData();
    formData.append("file", files[0]);

    try {
        // 🔥 INICIO LOADER
        if (!visible.value) {
            toast.add({
                severity: "custom",
                summary: "Subiendo archivo...",
                group: "headless",
                styleClass: "backdrop-blur-lg rounded-2xl",
            });

            visible.value = true;
            progress.value = 0;

            if (interval.value) clearInterval(interval.value);

            interval.value = setInterval(() => {
                if (progress.value < 90) {
                    progress.value += 10;
                }
            }, 300);
        }

        const response = await axios.post(
            route("efficiency.sodexo.import"),
            formData,
            {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            }
        );

        progress.value = 100;
        clearInterval(interval.value);

        setTimeout(() => {
            toast.removeGroup("headless");

            const data = response.data;

            // 🔥 GUARDAMOS RESULTADO COMPLETO
            importResult.value = data;

            // 🔥 ABRIMOS MODAL
            resultDialog.value = true;

            // 🔥 CERRAMOS UPLOAD
            openUploadDialog.value = false;

            visible.value = false;
            submitted.value = false;

            // 🔥 LIMPIAMOS FILEUPLOAD
            if (event.options) event.options.clear();

            // 🔥 REFRESCAMOS TABLA
            loadEfficiency();

        }, 500);

    } catch (error) {
        clearInterval(interval.value);
        toast.removeGroup("headless");

        visible.value = false;
        submitted.value = false;
        progress.value = 0;

        const errorData = error.response?.data;

        // 🔥 TAMBIÉN MOSTRAR ERRORES EN MODAL
        importResult.value = {
            success: false,
            message: errorData?.message || "Error al importar",
            errors: errorData?.errors || []
        };

        resultDialog.value = true;

        console.error("Error en importación:", errorData || error);
    }
};

/* =====================
LOAD
===================== */
const loadEfficiency = async () => {
    loading.value = true;

    try {
        const params = {
            branch_office_id: activeFilters.branchOffice,
            department_id: activeFilters.department,
            position_id: activeFilters.position,
            estatus: activeFilters.estatus,
            month: activeFilters.month,
            employee_ids: activeFilters.employees,
            include_deleted: activeFilters.includeDeleted
        };

        console.log("PARAMS:", params);

        const { data } = await axios.get('/employee/efficiency/filter', { params });

        employeesEfficiency.value = data.data ?? [];
        console.log(employeesEfficiency.value)
    } catch (error) {
        employeesEfficiency.value = [];
        console.error(error);
    } finally {
        loading.value = false;
    }
};

/* =====================
APLICAR FILTROS
===================== */
const applyFilters = () => {

    const branchIds = selectedBranchOffice.value
    .filter(v => v !== 'all')
    .map(v => typeof v === 'object' ? Number(v.id) : Number(v));

    const employeeIds = selectedEmployees.value.map(v =>
        typeof v === 'object' ? Number(v.id) : Number(v)
    );

    const departmentIds = selectedDepartment.value.map(v =>
        typeof v === 'object' ? Number(v.id) : Number(v)
    );



    const now = new Date();
    const selectedDate = date.value ?? now;

    const formattedMonth = `${selectedDate.getFullYear()}-${String(
        selectedDate.getMonth() + 1
    ).padStart(2, "0")}`;

    activeFilters.branchOffice = branchIds;
    activeFilters.department = departmentIds;
    activeFilters.position = selectedPosition.value;
    activeFilters.estatus = selectedEstatus.value;
    activeFilters.month = formattedMonth;
    activeFilters.employees = employeeIds;
    // activeFilters.includeDeleted = checked.value;

    localStorage.setItem("selectedBranchOffice", JSON.stringify(branchIds));

    loadEfficiency();
    otherFilterDialog.value = false;
};

const activeFilters = reactive({
    branchOffice: [],
    department: null,
    position: null,
    estatus: null,
    month: null,
    employees: [],
    includeDeleted: false
});

const departmentNames = computed(() => {
    if (!Array.isArray(activeFilters.department)) return [];

    return activeFilters.department.map(id => {
        const dep = props.departments.find(d => d.id === id);
        return dep?.name ?? `ID ${id}`;
    });
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

const estatusName = computed(() => {
    if (!activeFilters.estatus) return null;

    const est = estatus.value.find(
        e => e.code === activeFilters.estatus
    );

    return est?.name ?? null;
});


const positionName = computed(() => {
    if (!activeFilters.position) return null;

    const pos = position.value.find(
        p => p.id === activeFilters.position
    );

    return pos?.name ?? null;
});

const employeeNames = computed(() => {
    if (!Array.isArray(activeFilters.employees)) return [];

    return activeFilters.employees
        .map(id => {
            const emp = props.employees.find(e => e.id === id);
            return emp?.full_name ?? `ID ${id}`;
        });
});

const monthLabel = computed(() => {
    if (!activeFilters.month) return null;

    const [year, month] = activeFilters.month.split('-');

    return `${month}/${year}`;
});

const dateRangeLabel = computed(() => {
    if (!activeFilters.dateFrom && !activeFilters.dateTo) return null;

    const from = formatDate(activeFilters.dateFrom);
    const to = formatDate(activeFilters.dateTo);

    return `${from ?? '...'} - ${to ?? '...'}`;
});


/* =====================
FILTROS UI
===================== */
const selectedBranchOffice = ref([]);
const selectedDepartment = ref([]);
const selectedPosition = ref(null);
const selectedEstatus = ref(null);
const selectedEmployees = ref([]);
const date = ref(null);
const date_sodexo = ref(null);
// const checked = ref(false);
const filteredBranchOffices = ref([]);

/* =====================
REMOVE FILTER
===================== */
const removeFilter = (type) => {
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

        case 'estatus':
            selectedEstatus.value = null;
            activeFilters.estatus = null;
            break;

        case 'department':
            selectedDepartment.value = [];
            activeFilters.department = [];
            break;

        case 'position':
            selectedPosition.value = null;
            activeFilters.position = null;
            break;

        case 'month':
            date.value = null;
            activeFilters.month = null;
            break;

        case 'employees':
            selectedEmployees.value = [];
            activeFilters.employees = [];
            break;
    }

    loadEfficiency();
};

const removeDepartment = (index) => {
    const id = activeFilters.department[index];

    activeFilters.department.splice(index, 1);

    selectedDepartment.value = selectedDepartment.value.filter(
        v => Number(v.id ?? v) !== Number(id)
    );

    loadEfficiency();
};

const removePlant = (plantId) => {
    selectedBranchOffice.value =
        selectedBranchOffice.value.filter(id => id !== plantId)

    activeFilters.branchOffice =
        activeFilters.branchOffice.filter(id => id !== plantId)

    loadEfficiency()
}

const getPlantName = (id) => {
    const plant = props.branchOffices.find(b => b.id === id)
    return plant ? plant.code : id
}

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

const branchOfficesWithAll = computed(() => [
    { id: 'all', code: 'TODAS LAS PLANTAS' },
    ...props.branchOffices
])

const allBranchOfficeIds = computed(() =>
    props.branchOffices.map(b => b.id)
)

const removeEmployee = (index) => {
    const id = activeFilters.employees[index];

    activeFilters.employees.splice(index, 1);

    selectedEmployees.value = selectedEmployees.value.filter(
        v => Number(v.id ?? v) !== Number(id)
    );

    loadEfficiency();
};

const clearFilter = () => {

    selectedDepartment.value = [];
    selectedPosition.value = null;
    selectedEstatus.value = null;
    selectedEmployees.value = [];

    Object.assign(activeFilters, {
        branchOffice: activeFilters.branchOffice,
        department: null,
        position: null,
        estatus: null,
        month: activeFilters.month,
        employees: [],
    });

    loadEfficiency();
};

const formatDate = (value) => {
    if (!value) return '';

    return value.split(' ')[0];
};

//Inicializar filtros globales de tabla
const filters = ref({});

//Inicializar variable de carga para mostrar esqueleto
const loading = ref(true);

//Función para mostrar toast de éxito y error
const { showSuccess, showError, showValidationError } = useToastService();

//Función para inicializar filtros
const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },

        employee_id: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }]
        },

        full_name: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }]
        },

        department_name: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }]
        },

        position_name: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }]
        },

        parent_full_name: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }]
        },

        efficiency: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }]
        },

        amount: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }]
        },

        created_at: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }]
        }
    };
};

initFilters();

//Columnas de exportación
const exportColumns = ref({
    clave_empleado: true,
    empleado: true,
    departamento: true,
    puesto: true,
    fecha_antiguedad: true,
    porcentaje: true,
    importe: true,
    jefe_inmediato: true,
});

//Columnas visibles
const showColumns = ref({
    acciones: true,
    clave_empleado: true,
    empleado: true,
    departamento: true,
    puesto: true,
    fecha_antiguedad: true,
    porcentaje: true,
    importe: true,
    jefe_inmediato: true,
});

//Filtros adicionales
const otherFilters = ref([
    {
        startDate: null,
        endDate: null,
    },
]);

//Diálogo de filtros adicionales
const otherFilterDialog = ref(false);
const showCreateEfficiency = ref(false);

// Editar Eficiencia
const showEditEfficiency = ref(false);
const saving_edit = ref(false);
const date_sodexo_edit = ref(null);

// Objeto reactivo para el formulario
const formEdit = ref({
    id: null,
    employee_name: '',
    percentage: 0,
});

// Función para abrir el diálogo y cargar datos de la fila
const openEdit = (data) => {
    formEdit.value.id = data.id;
    formEdit.value.employee_name = `(${data.employee_id}) - ${data.full_name}`;
    formEdit.value.percentage = data.efficiency;
    date_sodexo_edit.value = new Date(data.year, data.month - 1, 1);

    showEditEfficiency.value = true;
};

// Petición al controlador
const editarEficiencias = async () => {
    if (formEdit.value.percentage < 0 || formEdit.value.percentage > 100) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Porcentaje inválido', life: 3000 });
        return;
    }

    saving_edit.value = true;
    try {
        const payload = {
            month: date_sodexo_edit.value,
            efficiency: formEdit.value.percentage
        };

        const response = await axios.put(route('efficiency.update', formEdit.value.id), payload);

        if (response.data.success) {
            toast.add({ severity: 'success', summary: 'Éxito', detail: response.data.message, life: 3000 });
            showEditEfficiency.value = false;
            filter_data_efficiency();
        }
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Error al actualizar',
            life: 4000
        });
    } finally {
        saving_edit.value = false;
    }
};

//Filtros del modal
const startDate = ref();
const endDate = ref();

//Referencia a la tabla de datos
const dt = ref(null);
const toast = useToast();

//Columnas fijas
const frozenColumns = ref({
    acciones: false,
    clave_empleado: false,
    empleado: false,
    departamento: false,
    puesto: false,
    fecha_antiguedad: false,
    porcentaje: false,
    importe: false,
    jefe_inmediato: false,
});

//Diálogo de selección de columnas
const columnsDialog = ref(false);

//Estado de envío
const submitted = ref(false);

//Referencias a los popovers
const op = ref();
const opMostrarColumnas = ref();
const opFijarColumnas = ref();

//Estado de diálogo de subida de archivos
const openUploadDialog = ref(false);

//Estado de progreso de subida
const progress = ref(0);

//Estado de visibilidad del toast
const visible = ref(false);

//Referencia al servicio de toast personalizado
const interval = ref();

//Funciones para alternar los popovers
const toggleAccionesMasivas = (event) => {
    op.value.toggle(event);
};

const toggleMostrarColumnas = (event) => {
    opMostrarColumnas.value.toggle(event);
};

const toggleFijarColumnas = (event) => {
    opFijarColumnas.value.toggle(event);
};

//Filas seleccionadas
const selected = ref([]);

//Función para guardar las columnas seleccionadas para exportar
const saveColumns = () => {
    columnsDialog.value = false;
    dt.value.exportCSV();
};


//Función para remover la fecha de inicio
function removeStartDate() {
    loading.value = true;
    otherFilters.value[0].startDate = null;

    setTimeout(() => {
        loading.value = false;
    }, 500);
}

//Función para obtener el ícono del archivo según su extensión
const fileIcon = (file) => {
    const ext = file.name.split(".").pop()?.toLowerCase();
    if (ext === "csv") return "pi-file"; // ícono genérico de archivo
    if (["xls", "xlsx"].includes(ext)) return "pi-file-excel"; // ícono de Excel
    return "pi-file";
};

watch(showCreateEfficiency, (val) => {
    if (!val) {
        empleadosSodexo.value = [];
        date_sodexo.value = null;
        loadingSodexo.value = false;
    }
});

watch(date_sodexo, async (newDate) => {

    empleadosSodexo.value = []

    if (!newDate) {
        return
    }

    loadingSodexo.value = true

    try {

        const selectedDate = new Date(newDate)

        const formattedMonth = `${selectedDate.getFullYear()}-${String(
            selectedDate.getMonth() + 1
        ).padStart(2, "0")}`

        const response = await axios.post(route('efficiency.sodexo'), {
            month: formattedMonth
        })

        empleadosSodexo.value = response.data.map(emp => ({
            id: emp.id,
            full_name: emp.full_name,
            percentage: 0
        }))

        if (empleadosSodexo.value.length === 0) {
            showValidationError('No hay registros con prestación de SODEXO.')
        }

    } catch (error) {
        console.error(error)
        showError()
    }

    loadingSodexo.value = false
})

const downloadTemplate = () => {
    window.open('/files/Efficiencies_template.csv', '_blank')
}

const guardarEficiencias = async () => {

    saving.value = true;

    try {
        const date = new Date(date_sodexo.value);
        const formattedMonth = `${date.getFullYear()}-${String(
            date.getMonth() + 1
        ).padStart(2, '0')}-01`;

        const payload = {
            month: formattedMonth,
            efficiencies: empleadosSodexo.value.map(emp => ({
                employee_id: emp.id,
                percentage: emp.percentage ?? 0
            }))
        };

        // Guardamos la respuesta de la petición
        const response = await axios.post(route('efficiency.store'), payload);

        // Validamos si Laravel respondió con éxito real
        if (response.data.success) {
            showSuccess()

            // Solo limpiamos y cerramos si realmente se guardó
            empleadosSodexo.value = [];
            date_sodexo.value = null;
            loadingSodexo.value = false;
            showCreateEfficiency.value = false;
            loadEfficiency();
        } else {
            // Si el servidor dice success: false (ej: el empleado ya tiene registro)

            showValidationError(response.data.message)
        }

    } catch (error) {
        // Errores de validación (422) o caídas del servidor (500)
        const errorMessage = error.response?.data?.message || "Ocurrió un problema al guardar";

        showValidationError(errorMessage)

        console.error(error);
    } finally {
        saving.value = false;
    }
};

/* =====================
MOUNTED
===================== */
onMounted(() => {
    filteredBranchOffices.value = props.branchOffices ?? [];

    const raw = localStorage.getItem("selectedBranchOffice");
    let stored = [];

    const now = new Date();
    const currentMonth = `${now.getFullYear()}-${String(
        now.getMonth() + 1
    ).padStart(2, "0")}`;

    date.value = now;
    activeFilters.month = currentMonth;

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
        loadEfficiency();
        return;
    }

    loadEfficiency();
});
</script>

<template>
    <AppLayout :title="'Eficiencias'">
        <!-- <pre>
            {{ branchOffices }}
        </pre> -->
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
                    <!-- <Button
                        type="button"
                        icon="pi pi-download"
                        label="Importar"
                        severity="contrast"
                        class="mt-2 ml-2"
                        @click="openUploadDialog = true"
                    /> -->
                    <Button
                        v-if="can('efficiency.export')"
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
                        v-if="can('efficiency.create')"
                        label="Crear"
                        icon="pi pi-plus-circle"
                        severity="success"
                        class="ml-2"
                        @click="showCreateEfficiency = true"
                    />
                </template>
            </Toolbar>
            <!-- {{ employeesEfficiency }} -->
            <DataTable
                ref="dt"
                v-model:selection="selected"
                :value="employeesEfficiency"
                dataKey="id"
                :paginator="true"
                :rows="10"
                scrollable
                scrollHeight="400px"
                tableStyle="min-width: 110rem"
                v-model:filters="filters"
                filterDisplay="menu"
                exportFilename="Eficiencias_V2"
                :globalFilterFields="[
                    'employee_id',
                    'full_name',
                    'department_name',
                    'position_name',
                    'parent_full_name',
                    'efficiency',
                    'amount',
                    'created_at'
                ]"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} datos de Eficiencias"
            >
                <template #header>
                    <div
                        class="flex flex-wrap gap-2 items-end justify-between mb-6"
                    >
                        <div>
                            <h4 class="m-0">Tabla de Eficiencias</h4>
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
                        <!-- Plantas -->
                        <!-- <Chip
                            v-for="(plant, index) in branchOfficeNames"
                            :key="'plant-' + index"
                            :label="`Planta: ${plant}`"
                            removable
                            @remove="removeBranchOffice(index)"
                        /> -->

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

                        <!-- Estatus -->
                        <Chip
                            v-if="estatusName"
                            :label="`Estatus: ${estatusName}`"
                            removable
                            @remove="removeFilter('estatus')"
                        />

                        <!-- Departamento -->
                        <Chip
                            v-for="(dep, index) in departmentNames"
                            :key="'dep-' + index"
                            :label="`Depto: ${dep}`"
                            removable
                            @remove="removeDepartment(index)"
                        />

                        <!-- Puesto -->
                        <Chip
                            v-if="positionName"
                            :label="`Puesto: ${positionName}`"
                            removable
                            @remove="removeFilter('position')"
                        />

                        <!-- Mes -->
                        <Chip
                            v-if="monthLabel"
                            :label="`Mes: ${monthLabel}`"
                            removable
                            @remove="removeFilter('month')"
                        />

                        <!-- Empleados -->
                        <Chip
                            v-for="(emp, index) in employeeNames"
                            :key="'emp-' + index"
                            :label="emp"
                            removable
                            @remove="removeEmployee(index)"
                        />

                        <!-- Incluye eliminados -->
                        <!-- <Chip
                            v-if="includeDeletedLabel"
                            :label="includeDeletedLabel"
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
                        <Skeleton v-if="loading" width="100px" height="2rem" />
                        <div v-else-if="canEditRow(slotProps.data)" class="flex gap-2">
                            <Button
                                v-if="can('efficiency.edit')"
                                icon="pi pi-pencil"
                                class="mr-2"
                                rounded
                                v-tooltip.top="'Editar'"
                                severity="warn"
                                @click="openEdit(slotProps.data)"
                            />
                            <Button
                                v-if="can('efficiency.delete')"
                                icon="pi pi-trash"
                                severity="danger"
                                v-tooltip.top="'Eliminar'"
                                class="mr-2"
                                rounded
                                @click="confirmarEliminar(slotProps.data)"
                            />
                        </div>
                        <Tag
                            v-else
                            value="Sin permiso"
                            severity="danger"
                            icon="pi pi-lock"
                        />
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
                            placeholder="Buscar por Clave"
                        />
                    </template>
                </Column>
                <Column
                    field="full_name"
                    header="Empleado"
                    :filter="true"
                    :frozen="frozenColumns.empleado"
                    :style="{
                        width: '20rem',
                        display: showColumns.empleado ? '' : 'none',
                    }"
                    :exportable="exportColumns.empleado"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.full_name }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Rol"
                        />
                    </template>
                </Column>
                <Column
                    field="department_name"
                    header="Departamento"
                    :filter="true"
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
                        <span v-else>{{ data.department_name }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Correo"
                        />
                    </template>
                </Column>

                <Column
                    field="position_name"
                    header="Puesto"
                    :filter="true"
                    sortable
                    :frozen="frozenColumns.puesto"
                    :style="{
                        width: '20rem',
                        display: showColumns.puesto ? '' : 'none',
                    }"
                    :exportable="exportColumns.puesto"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.position_name }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Nombre"
                        /> </template
                ></Column>
                <Column
                    field="created_at"
                    header="Fecha Antiguedad"
                    :filter="true"
                    sortable
                    :frozen="frozenColumns.fecha_antiguedad"
                    :style="{
                        width: '20rem',
                        display: showColumns.fecha_antiguedad ? '' : 'none',
                    }"
                    :exportable="exportColumns.fecha_antiguedad"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ formatDate(data.created_at) }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Telefono"
                        /> </template
                ></Column>
                <Column
                    field="efficiency"
                    header="Porcentaje"
                    :filter="true"
                    sortable
                    :frozen="frozenColumns.porcentaje"
                    :style="{
                        width: '20rem',
                        display: showColumns.porcentaje ? '' : 'none',
                    }"
                    :exportable="exportColumns.porcentaje"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.efficiency }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Dirección"
                        /> </template
                ></Column>
                <Column
                    field="amount"
                    header="Importe"
                    :filter="true"
                    sortable
                    :frozen="frozenColumns.importe"
                    :style="{
                        width: '20rem',
                        display: showColumns.importe ? '' : 'none',
                    }"
                    :exportable="exportColumns.importe"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.amount }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Ciudad"
                        /> </template
                ></Column>
                <Column
                    field="parent_full_names"
                    header="Jefe Inmediato"
                    :filter="true"
                    sortable
                    :frozen="frozenColumns.jefe_inmediato"
                    :style="{
                        width: '20rem',
                        display: showColumns.jefe_inmediato ? '' : 'none',
                    }"
                    :exportable="exportColumns.jefe_inmediato"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>

                        <div v-else class="flex gap-1 flex-wrap items-center">
                            <template v-if="data.parent_full_names">

                                <!-- Mostrar máximo 2 -->
                                <template
                                    v-for="(jefe, index) in data.parent_full_names.split(',').slice(0, 1)"
                                    :key="index"
                                >
                                    <Tag :value="jefe.trim()" severity="info" />
                                </template>

                                <!-- Si hay más de 1 -->
                                <Tag
                                    v-if="data.parent_full_names.split(',').length > 1"
                                    :value="'+' + (data.parent_full_names.split(',').length - 1)"
                                    severity="info"
                                    v-tooltip.top="formatJefesTooltip(data.parent_full_names)"
                                />
                            </template>
                        </div>
                    </template>

                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Jefe"
                        />
                    </template>
                </Column>
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
                :draggable="false"
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
                            v-model="selectedDepartment"
                            :options="departments"
                            optionLabel="name"
                            optionValue="id"
                            filter
                            :filterFields="['name', 'id']"
                            class="w-full"
                            display="chip"
                        >


                            <template #value="slotProps">
                                <span v-if="!slotProps.value || slotProps.value.length === 0">
                                    Selecciona un departamento
                                </span>

                                <span v-else-if="slotProps.value.length > 5">
                                    {{ slotProps.value.length }} departamentos seleccionados
                                </span>


                            </template>

                            <template #option="{ option }">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-gray-700">({{ option.id }})</span>
                                    <span>{{ option.name }}</span>
                                </div>
                            </template>

                        </Multiselect>
                        <label for="department">Departamento</label>
                    </FloatLabel>
                </div>

                <div class="flex flex-col gap-5">
                    <FloatLabel variant="on" class="flex-1 mt-4">
                        <Select
                            v-model="selectedPosition"
                            :options="position"
                            optionLabel="name"
                            optionValue="id"
                            inputId="position"
                            filter
                            class="w-full"
                        />
                        <label for="position">Puesto</label>
                    </FloatLabel>
                </div>

                <div class="flex gap-5">
                    <FloatLabel variant="on" class="flex-1 mt-4">
                        <Select
                            v-model="selectedEstatus"
                            :options="estatus"
                            optionLabel="name"
                            optionValue="code"
                            inputId="estatus"
                            filter
                            class="w-full"
                        />
                        <label for="estatus">Estatus</label>
                    </FloatLabel>

                    <FloatLabel variant="on" class="flex-1 mt-4">
                        <DatePicker
                            v-model="date"
                            inputId="mes"
                            showIcon
                            view="month"
                            dateFormat="mm/yy"
                            class="w-full"
                        />
                        <label for="fecha_hasta">Mes</label>
                    </FloatLabel>
                </div>

                <div class="flex flex-col gap-5">
                    <FloatLabel variant="on" class="flex-1 mt-4">

                        <Multiselect
                            v-model="selectedEmployees"
                            :options="props.employees"
                            optionLabel="full_name"
                            optionValue="id"
                            filter
                            :filterFields="['full_name', 'id']"
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
                </div>
                <!-- <div class="flex gap-5">
                    <ToggleSwitch v-model="checked" class="mt-4">
                        <template #handle="{ checked }">
                            <i :class="['!text-xs pi', { 'pi-check': checked, 'pi-times': !checked }]" />
                        </template>
                    </ToggleSwitch>
                    <label for="sinRol" class="mt-4">
                        Incluir registros eliminados
                    </label>
                </div> -->

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
                v-model:visible="openUploadDialog"
                :style="{ width: '450px' }"
                header="Subir Archivo "
                :modal="true"
            >
                <div class="flex flex-col gap-6">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">
                            Descarga la plantilla antes de importar
                        </span>

                        <Button
                            type="button"
                            icon="pi pi-download"
                            label="Descargar Plantilla"
                            severity="info"
                            outlined
                            @click="downloadTemplate"
                        />
                    </div>
                    <FileUpload
                        name="files[]"
                        accept=".csv,.xlsx,.xls"
                        :maxFileSize="1000000"
                        :customUpload="true"
                        :multiple="false"
                        :maxFiles="1"
                        :fileLimit="1"
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
                                            @click="
                                                removeUploadedFileCallback(i)
                                            "
                                            rounded
                                            severity="danger"
                                            variant="outlined"
                                        />
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- <template #empty>
                            <span>Arrastra y suelta tus CSV o Excel aquí.</span>
                        </template> -->
                    </FileUpload>
                </div>

                <!-- <template #footer>
                    <Button
                        label="Cancelar"
                        icon="pi pi-times"
                        text
                        @click="openUploadDialog = false"
                    />

                    <Button
                        label="Subir Archivo"
                        icon="pi pi-upload"
                        severity="success"
                        :disabled="!selectedFile"
                        :loading="uploading"
                        @click="uploadFile"
                    />
                </template> -->
            </Dialog>
            <Dialog
                v-model:visible="showCreateEfficiency"
                :style="{ width: '40rem' }"
                header="Eficiencias"
                :modal="true"
            >

                <!-- Selección de Mes -->
                <div class="flex items-end gap-3">

                    <FloatLabel variant="on" class="flex-1 mt-4">
                        <DatePicker
                            v-model="date_sodexo"
                            inputId="mes_sodexo"
                            showIcon
                            view="month"
                            dateFormat="mm/yy"
                            class="w-1/2"
                        />
                        <label for="mes_sodexo">Mes</label>
                    </FloatLabel>

                    <Button
                        v-if="empleadosSodexo.length"
                        type="button"
                        icon="pi pi-download"
                        label="Importar"
                        severity="contrast"
                        class="mt-2 ml-2"
                        @click="openUploadDialog = true"
                    />
                </div>

                <!-- Loading -->
                <div v-if="loadingSodexo" class="mt-4">
                    <p>Validando empleados con SODEXO...</p>
                </div>

                <!-- Lista dinámica -->
                <div v-if="empleadosSodexo.length" class="mt-4 space-y-4">
                    <div
                        v-for="emp in empleadosSodexo"
                        :key="emp.id"
                        class="border-b pb-3"
                    >
                        <div class="grid grid-cols-2 gap-4">

                            <div class="flex flex-col gap-2">
                                <label for="employee_name">Empleado</label>
                                <InputText id="employee_name" :value="`(${emp.id}) - ${emp.full_name}`"/>
                            </div>

                            <div class="flex flex-col gap-2">
                                <label for="percent_employee">Porcentaje</label>
                                <InputNumber v-model="emp.percentage" inputId="percent_employee" prefix="%" fluid />
                            </div>

                        </div>
                    </div>

                </div>

                <template #footer>
                    <Button
                        label="Cancelar"
                        severity="danger"
                        @click="showCreateEfficiency = false"
                    />
                    <Button
                        :label="saving ? 'Guardando...' : 'Guardar'"
                        severity="success"
                        :loading="saving"
                        :disabled="!empleadosSodexo.length || saving"
                        @click="guardarEficiencias"
                    />
                </template>

            </Dialog>
            <Dialog v-model:visible="showEditEfficiency" :style="{ width: '40rem' }" header="Editar Eficiencia" :modal="true">
                <div class="grid grid-cols-2 gap-4 mt-4">
                    <div class="flex flex-col gap-2">
                        <label>Mes/Año</label>
                        <DatePicker
                            v-model="date_sodexo_edit"
                            view="month"
                            dateFormat="mm/yy"
                            showIcon
                            fluid
                        />
                    </div>

                    <div class="flex flex-col gap-2">
                        <label for="employee_name">Empleado</label>
                        <InputText id="employee_name_edit" v-model="formEdit.employee_name" disabled />
                    </div>

                    <div class="flex flex-col gap-2">
                        <label for="percent_employee">Porcentaje de Eficiencia</label>
                        <InputNumber
                            v-model="formEdit.percentage"
                            inputId="percent_employee"
                            suffix="%"
                            :min="0"
                            :max="100"
                            fluid
                        />
                    </div>
                </div>
                <template #footer>
                    <Button
                        label="Cancelar"
                        severity="danger"
                        @click="showEditEfficiency = false"
                    />
                    <Button
                        :label="saving ? 'Guardando Cambios' : 'Guardar'"
                        severity="success"
                        :loading="saving"
                        @click="editarEficiencias"
                    />
                </template>
            </Dialog>
            <Dialog
                v-model:visible="approveDialog"
                :style="{ width: '450px' }"
                header="Confirmar Eliminación"
                :modal="true"
            >
                <div class="border-l-4 p-4 rounded bg-red-50 border-red-500">
                    <div class="flex items-center">
                        <i class="pi pi-exclamation-triangle text-red-600 text-2xl mr-3"></i>
                        <div>
                            <h3 class="font-bold text-red-800">
                                ¿Estás seguro de eliminar este empleado?
                            </h3>
                            <p class="text-sm text-red-700">
                                Esta acción no se puede deshacer.
                            </p>
                        </div>
                    </div>
                </div>

                <template #footer>
                    <Button
                        label="Cancelar"
                        icon="pi pi-times"
                        text
                        severity="secondary"
                        :disabled="loadingEliminar"
                        @click="approveDialog = false"
                    />

                    <Button
                        label="Eliminar"
                        icon="pi pi-trash"
                        severity="danger"
                        @click="eliminarEmpleado"
                        :loading="loadingEliminar"
                        :disabled="loadingEliminar"
                    />
                </template>
            </Dialog>
            <Dialog v-model:visible="resultDialog" header="Resultado de Importación" :modal="true" :style="{ width: '500px' }">

                <div v-if="importResult">

                    <!-- Mensaje principal -->
                    <p v-html="importResult.message"></p>

                    <!-- Éxito -->
                    <li v-for="s in importResult.successes" :key="s.line">
                        ✔ {{ s.employee_id }} - {{ s.employee_name }}
                    </li>

                    <!-- Errores -->
                    <div v-if="importResult.errors && importResult.errors.length" class="mt-4">
                        <h5 class="text-red-600">Errores:</h5>

                        <ul class="text-sm max-h-60 overflow-auto">
                            <li v-for="e in importResult.errors" :key="e.line">
                                ✖ {{ e.employee_id ?? 'N/A' }} - {{ e.employee_name ?? 'N/A' }} → {{ e.error }}
                            </li>
                        </ul>
                    </div>

                </div>

            </Dialog>
        </div>
    </AppLayout>
</template>
