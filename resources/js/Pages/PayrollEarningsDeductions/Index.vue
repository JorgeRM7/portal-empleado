<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToastService } from "@/Stores/toastService";
import { onMounted, ref, computed, watch, reactive, nextTick } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { router, usePage } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import axios from "axios";
import { useToast } from 'primevue/usetoast'
import { useLayout } from "@/Layouts/composables/layout";
import { useAuthz } from "@/composables/useAuthz";

const { can } = useAuthz();

const toast = useToast()
const { isDark } = useLayout();

const props = defineProps({
    Plantas: Array,
    Empleados: Array,
    PersepcionesDeducciones: Array,
});
// console.log(props.Empleados);

//Inicializar filtros globales de tabla
const filters = ref({});

// ----- ESTADO GENERAL -----
//Inicializacion de una fila para mostrar el esqueleto de inicio en lo que carga la tabla
const rows = ref([
    {
        employee_id: null,
        type: '',
    },
]);
const loading = ref(true);

//Función para mostrar toast de éxito y error
const { showErrorCustom, showSuccessCustom } = useToastService();

const page = usePage();

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            showSuccessCustom(flash.success);
        }

        if (flash?.error) {
            showErrorCustom(flash.error);
        }
    },
    { immediate: true }
);

//Función para inicializar filtros locales
const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        type: {
            operator: FilterOperator.OR,
            constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }],
        },
        start_date: {
            operator: FilterOperator.OR,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        end_date: {
            operator: FilterOperator.OR,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        }
    };
};

initFilters();

//Columnas visibles
const showColumns = ref({
    acciones: true,
    employee_id: true,
    type: true,
    code: true,
    credit_number: true,
    apply_piecework: true,
    apply: true,
    start_date: true,
    end_date: true,
    amount: true,
    value: true,
    amount_limit: true,
    aggregate_amount: true,
    amount_credit_monthly: true,
});

//Columnas fijas
const frozenColumns = ref({
    acciones: true,
    employee_id: false,
    type: false,
    code: false,
    credit_number: false,
    apply_piecework: false,
    apply: false,
    start_date: false,
    end_date: false,
    amount: false,
    value: false,
    amount_limit: false,
    aggregate_amount: false,
    amount_credit_monthly: false,
});

const exportColumns = ref({
    employee_id: true,
    type: true,
    code: true,
    credit_number: true,
    apply_piecework: true,
    apply: true,
    start_date: true,
    end_date: true,
    amount: true,
    value: true,
    amount_limit: true,
    aggregate_amount: true,
    amount_credit_monthly: true,
});

// Traducciones
const columnLabels = {
    acciones: "Acciones",
    employee_id: "CLAVE",
    type: "PER/DED",
    code: "NUM PER/DED",
    credit_number: "NUM CREDITO",
    apply_piecework: "APLICA DESTAJO",
    apply: "APLICACIÓN",
    start_date: "FECHA DE INICIO",
    end_date: "FECHA DE FIN",
    amount: "MONTO O FORMULA",
    value: "VALOR DESCUENTO",
    amount_limit: "MONTO LIMITE",
    aggregate_amount: "MONTO ACUMULADO",
    amount_credit_monthly: "RET_MEN_FON",
};

//Diálogo de filtros adicionales
const otherFilterDialog = ref(false);
const syncDialog = ref(false);

// ----- FILTROS UI -----
const selectedBranchOffice = ref(null);
const selectedEmployees = ref([]);
const selectedWeek = ref(null);
const selectedEarningDeductions = ref(null);

// sync
const syncBranchOffice = ref(null)
const syncEmployees = ref([])
const syncWeek = ref(null)

// ----- FILTROS ACTIVOS (CHIPS) -----
const activeFilters = reactive({
    branchOffice: null,
    employee: [],
    week: null,
    earningDeduction: null,
});

// ----- APLICAR FILTROS ----- 
const applyFilters = () => {
    const empIds = selectedEmployees.value.map(e =>
        (e && typeof e === 'object') ? e.id : e
    );

    // Actualizar CHIPS
    activeFilters.branchOffice = selectedBranchOffice.value;
    activeFilters.employee = [...selectedEmployees.value];
    activeFilters.week = selectedWeek.value;
    activeFilters.earningDeduction = selectedEarningDeductions.value;

    // console.log(activeFilters);
    loadData({
        branchOfficeId: selectedBranchOffice.value?.id ?? null,
        employeeIds: empIds,
        week: selectedWeek.value,
        salaryPaymentId: selectedEarningDeductions.value?.id ?? null,
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
        
        case 'employee':
            selectedEmployees.value = [];
            activeFilters.employee = [];
            break

        case 'week':
            selectedWeek.value = null;
            activeFilters.week = null;
            break

        case 'earningDeduction':
            selectedEarningDeductions.value = null;
            activeFilters.earningDeduction = null;
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
        const response = await axios.get("/payroll/payroll-earnings-deductions/filter-data", {
            params: {
                branch_office_id: params.branchOfficeId ?? null,
                employee_id: params.employeeIds ?? [],
                week: params.week ?? null,
                salary_payment_id: params.salaryPaymentId ?? null,
            },
        });

        // console.log(response.data);
        rows.value = response.data ?? [];

    } catch (error) {
        console.error("Error cargando la informacion de la tabla:", error);
    } finally {
        loading.value = false;
    }
};

const defaultFiltered = () => {

    // Reset base
    selectedEmployees.value = [];
    selectedEarningDeductions.value = null;

    activeFilters.employee = [];
    activeFilters.earningDeduction = null;

    // Semana actual
    selectedWeek.value = getCurrentISOWeek();
    syncWeek.value = getCurrentISOWeek();
    activeFilters.week = selectedWeek.value;

    let branch = null;

    const storedBranch = localStorage.getItem("selectedBranchOffice");

    if (storedBranch && props.Plantas?.length) {
        const stored = JSON.parse(storedBranch);

        branch = props.Plantas.find(b => b.id === stored.id) ?? null;

        if (branch) {
            selectedBranchOffice.value = branch;
            syncBranchOffice.value = branch;
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

const editItem = (id) => {
    if (processingRows.value[id]) return;

    processingRows.value[id] = true;

    router.get(`/payroll/payroll-earnings-deductions/${id}/edit`, {
        onFinish: () => {
            processingRows.value[id] = false;
        }
    });
};

const viewItem = (id) => {
    if (processingRows.value[id]) return;

    processingRows.value[id] = true;

    router.get(`/payroll/payroll-earnings-deductions/${id}`, {
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

    router.delete(`/payroll/payroll-earnings-deductions/${id}`, {
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

//Filas seleccionadas
const selected = ref([]);

const deleteSelectedItems = () => {
    confirm.require({
        message: `¿Deseas eliminar ${selected.value.length} registros?`,
        header: 'Confirmar eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Sí, eliminar',
        rejectLabel: 'Cancelar',
        acceptClass: 'p-button-danger',

        accept: () => {
            selected.value.forEach(row => {
                processingRows.value[row.id] = true;
            });

            const ids = selected.value.map(row => row.id);

            router.post('/payroll/payroll-earnings-deductions/delete-multiple', {
                ids
            }, {
                preserveScroll: true,
                
                onSuccess: () => {
                    selected.value = []

                    loadData({
                        branchOfficeId: activeFilters.branchOffice?.id ?? null,
                        employeeIds: activeFilters.employee.map(e => e.id),
                        week: activeFilters.week ?? null,
                        salaryPaymentId: activeFilters.earningDeduction?.id ?? null
                    });
                },
                
                onFinish: () => {
                    ids.forEach(id => {
                        processingRows.value[id] = false;
                    });
                }
            });
        },
    });
};

const massProcessing = ref(false);

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

// sync
const filteredEmployeesSync = computed(() => {
    
    if (!syncBranchOffice.value) {
        return props.Empleados
    }
    
    return props.Empleados.filter(emp =>
        emp.branch_office_id === syncBranchOffice.value.id
    )
})

// Asociar la planta a los empleados del select en filtros
watch(syncBranchOffice, () => {
    syncEmployees.value = []
})

const loadingSync = ref(false)
const syncData = async () => {
    if (!validateSync()) return

    const formData = new FormData()

    formData.append('planta', syncBranchOffice.value?.id || '')
    formData.append('semana', syncWeek.value || '')
    formData.append(
        'empleado',
        JSON.stringify(syncEmployees.value.map(e => e.id))
    )
    
    // console.log('--- FORMDATA ---')

    // for (const [key, value] of formData.entries()) {
    //     console.log(`${key}:`, value)
    // }

    try {
        loadingSync.value = true

        toast.add({
            severity: 'info',
            summary: 'Procesando',
            detail: 'Sincronizando datos...',
            life: 5000
        })

        const response = await fetch('https://grupo-ortiz.site/apis/Controllers/percepcionesDeduccionesController.php?op=sincronizar', {
            method: 'POST',
            body: formData
        })

        if (!response.ok) {
            throw new Error('Error HTTP: ' + response.status)
        }

        const data = await response.json()

        console.log('✅ Respuesta:', data)
        showSuccessCustom("Sincronización finalizada correctamente");

        syncDialog.value = false;

    } catch (error) {
        console.error('❌ Error:', error)
        showErrorCustom("Ocurrió un error en la sincronización");

    } finally {
        loadingSync.value = false
    }
}

const validateSync = () => {
    if (!syncBranchOffice.value?.id) {
        toast.add({
            severity: 'warn',
            summary: 'Validación',
            detail: 'La planta es obligatoria',
            life: 3000
        })
        return false
    }

    if (!syncWeek.value) {
        toast.add({
            severity: 'warn',
            summary: 'Validación',
            detail: 'La semana es obligatoria',
            life: 3000
        })
        return false
    }

    return true
}


const submitted = ref(false);
const columnsDialog = ref(false);

const textFields = ['code', 'credit_number', 'apply', 'amount']

const exportData = computed(() => {
    return rows.value.map(row => {
        const newRow = { ...row }

        textFields.forEach(field => {
            if (newRow[field]) {
                newRow[field] = `="${newRow[field]}"`
            }
        })

        return newRow
    })
})

const saveColumns = async () => {
    columnsDialog.value = false

    const original = rows.value

    // 👇 metes los datos formateados
    rows.value = exportData.value

    await nextTick()

    dt.value.exportCSV()

    // 👇 restauras los originales
    rows.value = original
}








</script>

<template>
    <AppLayout :title="'Percepciones y Deducciones Por Empleado'">
    <div class="card">

    <ConfirmDialog />
    
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

        <template #end>
            <Button label="Sincronizar" icon="pi pi-sync" severity="help" class="ml-2" @click="syncDialog = true"/>
            <Button type="button" label="Acciones Masivas" class="min-w-48 ml-2" icon="pi pi-wrench"
                @click="toggleAccionesMasivas($event)" :disabled="selected.length === 0" />
            <Popover ref="op">
                <div class="flex flex-col gap-4">
                    <div>
                        <span class="font-medium block mb-2">Acciones Masivas</span>
                        <ul class="list-none p-0 m-0 flex flex-col">
                            <li
                                class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border">
                                <Button icon="pi pi-trash" label="Eliminar seleccionados" severity="danger" text
                                    @click="deleteSelectedItems" />
                            </li>
                        </ul>
                    </div>
                </div>
            </Popover>
            <Link href="/payroll/payroll-earnings-deductions/create">
            <Button v-if="(can('payroll-earnings-deductions.create'))"
                label="Crear" icon="pi pi-plus-circle" severity="success" class="ml-2" />
            </Link>
        </template>
    </Toolbar>
    <DataTable ref="dt" v-model:selection="selected" :value="rows" dataKey="id" :paginator="true" :rows="10"
        scrollable scrollHeight=800px v-model:filters="filters" filterDisplay="menu"
        exportFilename="Percepciones y Deducciones Por Empleado" 
        :globalFilterFields="[
            'employee_id',
            'type',
            'code',
            'credit_number',
            'apply_piecework',
            'apply',
            'start_date',
            'end_date',
            'amount',
            'value',
            'amount_limit',
            'aggregate_amount',
            'amount_credit_monthly',
        ]"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} datos de prueba" removableSort
        :rowsPerPageOptions="[10, 20, 50, 100]" :loading="massProcessing">
        <template #header>
            <div class="flex flex-wrap gap-2 items-end justify-between mb-6">
                <div>
                    <h4 class="m-0">Percepciones y Deducciones Por Empleado</h4>
                    <Button icon="pi pi-filter" rounded v-tooltip.top="'Mostrar mas filtros'"
                        @click="otherFilterDialog = true" />
                    <Button type="button" icon="pi pi-filter-slash" rounded severity="secondary"
                        class="mt-5 ml-2 mr-2" v-tooltip.top="'Limpiar filtros'" @click="clearFilter()" />
                    <Tag v-if="selected.length > 0" severity="info"
                        :value="'Seleccionados: ' + selected.length"></Tag>
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
                    v-if="activeFilters.employee?.length"
                    :label="`Empleados: ${activeFilters.employee.map(emp => emp.id).join(', ')}`"
                    class="me-2"
                    @remove="removeFilter('employee')"
                    removable
                    :removable="!loading"
                />
                <Chip
                    v-if="activeFilters.earningDeduction"
                    :label="`Percepción / deducción: ${activeFilters.earningDeduction.name}`"
                    class="me-2"
                    @remove="removeFilter('earningDeduction')"
                    removable
                    :removable="!loading"
                />
            </div>
        </template>

        <!-- Selección -->
        <Column selectionMode="multiple" style="width: 4rem" :exportable="false" />

        <!-- acciones - "Acciones" -->
        <Column header="Acciones" style="min-width: 14rem" :exportable="false" :frozen="frozenColumns.acciones"
            :style="{
                display: showColumns.acciones ? '' : 'none',
            }">
            <template #body="slotProps">
                <Skeleton v-if="loading" />
                <div v-else>
                    <Button v-if="(can('payroll-earnings-deductions.edit'))" 
                        icon="pi pi-pencil" class="mr-2" severity="warn" v-tooltip.top="'Editar'" rounded
                        :disabled="processingRows[slotProps.data.id]"
                        :loading="processingRows[slotProps.data.id]" @click="editItem(slotProps.data.id)" />
                    <!-- <Button icon="pi pi-trash" class="mr-2" severity="danger" v-tooltip.top="'Eliminar'" rounded
                        :disabled="processingRows[slotProps.data.id]"
                        :loading="processingRows[slotProps.data.id]" @click="deleteItem(slotProps.data)" /> -->
                    <Button v-if="(can('payroll-earnings-deductions.delete'))"
                        icon="pi pi-trash"
                        severity="danger"
                        v-tooltip.top="'Eliminar'"
                        class="mr-2"
                        rounded
                        :disabled="processingRows[slotProps.data.id]"
                        :loading="processingRows[slotProps.data.id]"
                        @click="
                            () => {
                                itemToDelete = slotProps.data;
                                deleteDialog = true;
                            }
                        "
                    />
                    <Button
                        icon="pi pi-eye" class="mr-2" severity="help" v-tooltip.top="'Ver detalles'" rounded
                        :disabled="processingRows[slotProps.data.id]"
                        :loading="processingRows[slotProps.data.id]" @click="viewItem(slotProps.data.id)" />
                </div>
            </template>
        </Column>

        <!-- CLAVE -->
        <Column :exportable="exportColumns.employee_id" field="employee_id" header="CLAVE" sortable filter :frozen="frozenColumns.employee_id" :style="{
            display: showColumns.employee_id ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <span v-else>{{ data.employee_id }}</span>
            </template>
        </Column>

        <!-- PER/DED -->
        <Column :exportable="exportColumns.type" field="type" header="PER/DED" sortable filter :frozen="frozenColumns.type" :style="{
            display: showColumns.type ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <span v-else>{{ data.type }}</span>
            </template>
            <template #filter="{ filterModel }">
                <InputText v-model="filterModel.value" placeholder="PER/DED" />
            </template>
        </Column>

        <!-- NUM PER/DED -->
        <Column :exportable="exportColumns.code" field="code" header="NUM PER/DED" sortable filter :frozen="frozenColumns.code" :style="{
            display: showColumns.code ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <span v-else>{{ data.code }}</span>
            </template>
        </Column>

        <!-- NUM CREDITO -->
        <Column :exportable="exportColumns.credit_number" field="credit_number" header="NUM CREDITO" sortable filter :frozen="frozenColumns.credit_number" :style="{
            display: showColumns.credit_number ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <span v-else>{{ data.credit_number }}</span>
            </template>
        </Column>

        <!-- APLICA DESTAJO -->
        <Column :exportable="exportColumns.apply_piecework" field="apply_piecework" header="APLICA DESTAJO" sortable filter :frozen="frozenColumns.apply_piecework" :style="{
            display: showColumns.apply_piecework ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <span v-else>{{ data.apply_piecework }}</span>
            </template>
        </Column>

        <!-- APLICACIÓN -->
        <Column :exportable="exportColumns.apply" field="apply" header="APLICACIÓN" sortable filter :frozen="frozenColumns.apply" :style="{
            display: showColumns.apply ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <span v-else>{{ data.apply }}</span>
            </template>
        </Column>

        <!-- FECHA DE INICIO -->
        <Column :exportable="exportColumns.start_date" field="start_date" header="FECHA DE INICIO" sortable filter :frozen="frozenColumns.start_date" :style="{
            display: showColumns.start_date ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <span v-else>{{ data.start_date }}</span>
            </template>
            <template #filter="{ filterModel }">
                <InputText v-model="filterModel.value" placeholder="FECHA DE INICIO" />
            </template>
        </Column>

        <!-- FECHA DE FIN -->
        <Column :exportable="exportColumns.end_date" field="end_date" header="FECHA DE FIN" sortable filter :frozen="frozenColumns.end_date" :style="{
            display: showColumns.end_date ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <span v-else>{{ data.end_date }}</span>
            </template>
            <template #filter="{ filterModel }">
                <InputText v-model="filterModel.value" placeholder="FECHA DE FIN" />
            </template>
        </Column>

        <!-- MONTO O FORMULA -->
        <Column :exportable="exportColumns.amount" field="amount" header="MONTO O FORMULA" sortable filter :frozen="frozenColumns.amount" :style="{
            display: showColumns.amount ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <span v-else>{{ data.amount }}</span>
            </template>
        </Column>

        <!-- VALOR DESCUENTO -->
        <Column :exportable="exportColumns.value" field="value" header="VALOR DESCUENTO" sortable filter :frozen="frozenColumns.value" :style="{
            display: showColumns.value ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <span v-else>{{ data.value }}</span>
            </template>
        </Column>

        <!-- MONTO LIMITE -->
        <Column :exportable="exportColumns.amount_limit" field="amount_limit" header="MONTO LIMITE" sortable filter :frozen="frozenColumns.amount_limit" :style="{
            display: showColumns.amount_limit ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <span v-else>{{ data.amount_limit }}</span>
            </template>
        </Column>

        <!-- MONTO ACUMULADO -->
        <Column :exportable="exportColumns.aggregate_amount" field="aggregate_amount" header="MONTO ACUMULADO" sortable filter :frozen="frozenColumns.aggregate_amount" :style="{
            display: showColumns.aggregate_amount ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <span v-else>{{ data.aggregate_amount }}</span>
            </template>
        </Column>

        <!-- RET_MEN_FON -->
        <Column :exportable="exportColumns.amount_credit_monthly" field="amount_credit_monthly" header="RET_MEN_FON" sortable filter :frozen="frozenColumns.amount_credit_monthly" :style="{
            display: showColumns.amount_credit_monthly ? '' : 'none',
        }" :showFilterMatchModes="false" :showFilterOperator="false">
            <template #body="{ data }">
                <Skeleton v-if="loading" />
                <span v-else>{{ data.amount_credit_monthly }}</span>
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

                <div class="field md:col-span-2">
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
                        Percepcion y/o Deduccion
                    </label>
                    <Select
                        v-model="selectedEarningDeductions"
                        :options="props.PersepcionesDeducciones"
                        optionLabel="name"
                        filter
                        display="chip"
                        class="w-full"
                        placeholder="Percepcion y/o Deduccion"
                        showClear
                    />
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
    
    <Dialog
        v-model:visible="syncDialog"
        :style="{ width: '450px' }"
        header="Sincronizar datos"
        :modal="true"
    >
        <div class="flex flex-col gap-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div class="field">
                    <label class="block font-bold mb-2">
                        Planta
                    </label>
                    <Select
                        v-model="syncBranchOffice"
                        :options="props.Plantas"
                        optionLabel="code"
                        filter
                        placeholder="Selecciona planta"
                        class="w-full"
                    />
                </div>

                <div class="field">
                    <label class="block font-bold mb-2">
                        Semana
                    </label>
                    <InputText
                        type="week"
                        v-model="syncWeek"
                        class="w-full"
                    />
                </div>

                <div class="field md:col-span-2">
                    <label class="block font-bold mb-2">
                        Empleados
                    </label>
                    <Multiselect
                        v-model="syncEmployees"
                        :options="filteredEmployeesSync"
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
                @click="syncDialog = false"
            />
            <Button 
                label="Sincronizar" 
                icon="pi pi-sync" 
                :loading="loadingSync"
                :disabled="loadingSync"
                @click="syncData"/>
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
                <label :for="key" class="font-medium text-base">
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
                        ¿Estas seguro de eliminar el registro del empleado ({{itemToDelete.employee_id }})  ?
                    </h3>
                    <p :class="{
                        'text-sm text-red-700': !isDark,
                        'text-sm text-red-300': isDark,
                    }">
                        Si realizas esta acción se borraran los registros y no se podran recuperar
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
    
    </div>
    </AppLayout>
</template>
