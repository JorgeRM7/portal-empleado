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
    // branchOffices: Array,
    // departments: Array,
    // class_netsuite: Array,

    BranchOffices: Array,
    Departments: Array,
    NetsuiteClass: Array,
    NetsuiteExpenseCategory: Array,
    InvoiceTerm: Array,
    InvoiceAccountingList: Array,
    InvoiceArticle: Array,
    InvoiceExclusionCategory: Array,
    InvoiceOperationType: Array,
    InvoiceLocation: Array
});

/* =====================
ESTADO GENERAL
==================== */
const loading = ref(false);
const facturacion = ref([]);
const selectedEstados = ref([]);
const selectedEstatus = ref([]);
const selectedClassNetsuite = ref([]);
const checkExcluidos = ref(false);
const checkConXML = ref(false);
const checkConPDF = ref(false);

const estados = ref([
    { name: 'Pago', code: 'Pago' },
    { name: 'Ingreso', code: 'Ingreso' },
    { name: 'Egreso', code: 'Egreso' },
    { name: 'Nomina', code: 'Nomina' }
]);

const estatus = ref([
    { name: 'Vigente', code: 'Vigente' },
    { name: 'Cancelado', code: 'Cancelado' }
]);


const invoiceGeneric = ref([
    { name: 'Caja chica', code: '8345' },
    { name: 'Varios', code: '8244' },
    { name: 'Viaticos', code: '12185' }
]);

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

        send_status: { value: null, matchMode: FilterMatchMode.CONTAINS },
        uuid: { value: null, matchMode: FilterMatchMode.CONTAINS },
        subsidiary: { value: null, matchMode: FilterMatchMode.CONTAINS },
        emisor_rfc: { value: null, matchMode: FilterMatchMode.CONTAINS },
        emisor_name: { value: null, matchMode: FilterMatchMode.CONTAINS },
        trandate: { value: null, matchMode: FilterMatchMode.CONTAINS },
        efecto_comprobante: { value: null, matchMode: FilterMatchMode.CONTAINS },
        status: { value: null, matchMode: FilterMatchMode.CONTAINS }
    };
};

initFilters();

const hasActiveFilters = computed(() => {
    return (
        !!activeFilters.branchOffice ||
        activeFilters.department?.length > 0
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
    acciones: true,
    estatus_icono: true,
    uid: true,
    empresa: true,
    emisor_RFC: true,
    emisor_nombre: true,
    fecha_emision: true,
    importe: true,
    no_orden: true,
    categoria: true,
    ubicacion: true,
    departamento: true,
    clase: true,
    notas: true,
    termino: true,
    importacion: true,
    articulo: true,
    exclusion: true,
    proveedor_generico: true,
    tipo_operacion: true,
    tipo: true,
    estatus: true,
});

const frozenColumns = reactive({
    acciones: false,
    estatus_icono: false,
    uid: false,
    empresa: false,
    emisor_RFC: false,
    emisor_nombre: false,
    fecha_emision: false,
    importe: false,
    no_orden: false,
    categoria: false,
    ubicacion: false,
    departamento: false,
    clase: false,
    notas: false,
    termino: false,
    importacion: false,
    articulo: false,
    exclusion: false,
    proveedor_generico: false,
    tipo_operacion: false,
    tipo: false,
    estatus: false,
});

const exportColumns = reactive({
    uid: true,
    empresa: true,
    emisor_RFC: true,
    emisor_nombre: true,
    fecha_emision: true,
    importe: true,
    no_orden: true,
    categoria: true,
    ubicacion: true,
    departamento: true,
    clase: true,
    notas: true,
    termino: true,
    importacion: true,
    articulo: true,
    exclusion: true,
    proveedor_generico: true,
    tipo_operacion: true,
    tipo: true,
    estatus: true,
});

const enviarANetsuite = async (row) => {
    const payload = {
        recordID: row.id,
        invoice_category_id: row.invoice_category_id,
        invoice_department_id: row.invoice_department_id,
        invoice_class_id: row.invoice_class_id,
        invoice_location_id: row.invoice_location_id,
        invoice_term_id: row.invoice_term_id,
        invoice_accounting_id: row.invoice_accounting_id,
        invoice_exclusion_category_id: row.invoice_exclusion_category_id,
        invoice_article_id: row.invoice_article_id,
        invoice_operation_type_id: row.invoice_operation_type_id,
        invoice_provider_type: row.provider_id,
        order_id: row.order_id,
        notes: row.notes
    };

    console.log('📦 Payload que se envía:', payload);
    console.log('🧾 Row completo:', row);

    try {
        const { data } = await axios.post(`/billing/xml/send`, payload);

        console.log('📩 Respuesta completa:', data);

        if (data.response?.mensaje === 'Error') {
            console.error('❌ Error NetSuite:', data.response.detalle);

            showError(data.response.detalle || 'Error en NetSuite');
            return;
        }

        console.log('✅ Enviado correctamente');
        showSuccess('Factura enviada a NetSuite');

    } catch (error) {
        console.error('❌ Error HTTP:', error.response?.data || error);

        showError('Error al conectar con el servidor');
    }
};

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

const removeDepartment = (id) => {
    selectedDepartments.value = selectedDepartments.value.filter(d => d.id !== id);
    activeFilters.department = activeFilters.department.filter(d => d.id !== id);

    loadFacturacion({
        branchOfficeId: selectedBranchOffice.value?.id ?? null,
        departmentIds: selectedDepartments.value.map(d => d.id),
    });

};

/* =====================
FILTROS UI
===================== */
const selectedBranchOffice = ref([]);
const selectedDepartments = ref([]);
const valueFrom = ref(null);

const filteredBranchOffices = ref([]);
const filteredDepartments = ref([]);

/* =====================
FILTROS ACTIVOS (CHIPS)
===================== */
const activeFilters = reactive({
    branchOffice: null,
    department: [],
    classNetsuite: [],
    excluidos: false,
    conXML: false,
    conPDF: false
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
    }

    loadFacturacion({
        branchOfficeId: selectedBranchOffice.value?.id ?? null,
        departmentIds: selectedDepartments.value.map(d => d.id),
    })

}

const baseURL = 'https://portal-go.sfo3.cdn.digitaloceanspaces.com/sat_xml/';

const verPDF = (row) => {
    if (!row.pdf_path) {
        console.warn('No hay PDF');
        return;
    }

    const url = baseURL + row.pdf_path;
    window.open(url, '_blank');
};


const verXML = (row) => {
    if (!row.xml_path) {
        console.warn('No hay XML');
        return;
    }
    const url = baseURL + row.xml_path;
    window.open(url, '_blank');
};

/* =====================
LOAD FACTURACION
===================== */
const loadFacturacion = async (params = {}) => {
    loading.value = true;
    try {
        const queryParams = {};

        if (params.excluidos !== undefined) {
            queryParams.excluidos = params.excluidos;
        }

        if (params.conXML !== undefined) {
            queryParams.con_xml = params.conXML;
        }

        if (params.conPDF !== undefined) {
            queryParams.con_pdf = params.conPDF;
        }

        if (params.branchOfficeId && (!Array.isArray(params.branchOfficeId) || params.branchOfficeId.length > 0)) {
            const cleanBranches = Array.isArray(params.branchOfficeId)
                ? params.branchOfficeId.filter(id => id !== 'all')
                : params.branchOfficeId;

            if (cleanBranches.length > 0) {
                queryParams.branch_office_id = cleanBranches;
            }
        }

        if (params.departmentIds?.length) {
            queryParams.department_id = params.departmentIds;
        }

        if (params.dateRange && params.dateRange.length === 2) {
            const [start, end] = params.dateRange;
            if (start && end) {
                queryParams.start_date = new Date(start).toISOString().split('T')[0];
                queryParams.end_date = new Date(end).toISOString().split('T')[0];
            }
        }

        if (params.classIds?.length) {
            queryParams.class_ids = params.classIds;
        }

        console.log(params)

        const response = await axios.get("/billing/xml/filter-data", {
            params: queryParams,
        });

        facturacion.value = response.data.data ?? [];
        console.log(response.data);

        facturacion.value = (response.data.data ?? []).map(item => ({
            ...item,
            send_status_text:
                item.send_status === 'correct' ? 'Correcto' :
                item.send_status === 'pending' ? 'Pendiente' :
                item.send_status === 'error' ? 'Error' : '',

            efecto_comprobante_text:
                item.efecto_comprobante === 'I' ? 'Ingreso' :
                item.efecto_comprobante === 'P' ? 'Pago' :
                item.efecto_comprobante === 'E' ? 'Egreso' :
                item.efecto_comprobante === 'N' ? 'Nomina' : '',

            status_text:
                item.status === '1' ? 'Vigente' :
                item.status === '0' ? 'Cancelado' :
                item.status === '2' ? 'En Proceso' : ''
        }));

    } catch (error) {
        console.error("Error cargando facturacion:", error);
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

    activeFilters.branchOffice = [...selectedBranchOffice.value];
    activeFilters.department = [...selectedDepartments.value];
    activeFilters.classNetsuite = [...selectedClassNetsuite.value];
    activeFilters.fecha = valueFrom.value ? [...valueFrom.value] : null;

    activeFilters.excluidos = checkExcluidos.value;
    activeFilters.conXML = checkConXML.value;
    activeFilters.conPDF = checkConPDF.value;

    localStorage.setItem(
        "selectedBranchOffice",
        JSON.stringify(selectedBranchOffice.value)
    );

    loadFacturacion({
        branchOfficeId: selectedBranchOffice.value,
        departmentIds: depIds,
        classIds: selectedClassNetsuite.value,
        dateRange: activeFilters.fecha,
        excluidos: checkExcluidos.value,
        conXML: checkConXML.value,
        conPDF: checkConPDF.value
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
    activeFilters.department = [];
    activeFilters.employee = [];

    selectedBranchOffice.value = currentBranch;
    activeFilters.branchOffice = currentBranch;

    loadFacturacion({
        branchOfficeId: currentBranch?.id ?? null
    });

};

const removeToggle = (type) => {
    if (type === 'excluidos') {
        checkExcluidos.value = false;
        activeFilters.excluidos = false;
    }

    if (type === 'xml') {
        checkConXML.value = false;
        activeFilters.conXML = false;
    }

    if (type === 'pdf') {
        checkConPDF.value = false;
        activeFilters.conPDF = false;
    }

    applyFilters();
};

/* =====================
NO SEMANA
===================== */
const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('es-MX', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
};

// Función para limpiar el filtro de fecha
const clearDateFilter = () => {
    activeFilters.fecha = null;
    valueFrom.value = null;
    applyFilters();
};

const removePlant = (plantId) => {
    selectedBranchOffice.value = selectedBranchOffice.value.filter(id => id !== plantId);

    activeFilters.branchOffice = [...selectedBranchOffice.value];

    localStorage.setItem(
        "selectedBranchOffice",
        JSON.stringify(selectedBranchOffice.value)
    );

    loadFacturacion({
        branchOfficeId: selectedBranchOffice.value,
        departmentIds: selectedDepartments.value.map(d => d.id),
    });

};

/* =====================
FUNCIONES PARA CLASES (Añadir estas dos)
===================== */
const getClassName = (id) => {
    const clase = props.NetsuiteClass.find(c => c.id === id);
    return clase ? clase.name : id;
};

const removeClass = (classId) => {
    selectedClassNetsuite.value = selectedClassNetsuite.value.filter(id => id !== classId);
    activeFilters.classNetsuite = [...selectedClassNetsuite.value];

    loadFacturacion({
        branchOfficeId: selectedBranchOffice.value,
        departmentIds: selectedDepartments.value.map(d => d.id),
        classIds: selectedClassNetsuite.value,
    });
};

const getPlantName = (id) => {
    const plant = props.BranchOffices.find(b => b.id === id)
    return plant ? plant.code : id
}

const allBranchOfficeIds = computed(() =>
    props.BranchOffices.map(b => b.id)
)

const branchOfficesWithAll = computed(() => [
    { id: 'all', code: 'TODAS LAS PLANTAS' },
    ...props.BranchOffices
])

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

const getTodayRange = () => {
    const today = new Date();
    return [today, today];
};

/* =====================
MOUNTED
===================== */
onMounted(() => {
    filteredBranchOffices.value = props.BranchOffices ?? [];
    filteredDepartments.value = props.Departments ?? [];

    const storedBranch = localStorage.getItem("selectedBranchOffice");

    if (storedBranch) {
        try {
            const stored = JSON.parse(storedBranch);
            if (Array.isArray(stored)) {
                selectedBranchOffice.value = stored;
                activeFilters.branchOffice = stored;
            } else if (stored && stored.id) {
                selectedBranchOffice.value = [stored.id];
                activeFilters.branchOffice = [stored.id];
            }
        } catch (e) {
            console.error("Error al parsear branch del storage", e);
            selectedBranchOffice.value = [];
        }
    }

    // 🔥 AQUI SETEAS LA FECHA DE HOY
    const todayRange = getTodayRange();
    valueFrom.value = todayRange;
    activeFilters.fecha = todayRange;

    loadFacturacion({
        branchOfficeId: selectedBranchOffice.value,
        dateRange: todayRange
    });
});

</script>

<template>
    <!-- <pre>
        {{ props }}
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
            <template #end>
                <Button
                    type="button"
                    label="Acciones Masivas"
                    class="min-w-48"
                    icon="pi pi-wrench"
                    @click="toggleAccionesMasivas($event)"
                    :disabled="selected.length === 0"
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
                                    />
                                </li>
                            </ul>
                        </div>
                    </div>
                </Popover>
            </template>
        </Toolbar>
        <DataTable
            ref="dt"
            v-model:selection="selected"
            :value="facturacion"
            dataKey="id"
            :paginator="true"
            :rows="10"
            scrollable
            scrollHeight="400px"
            tableStyle="min-width: 110rem"
            v-model:filters="filters"
            filterDisplay="menu"
            exportFilename="employee_vacations"
            :globalFilterFields="[
                'send_status_text',
                'uuid',
                'subsidiary',
                'emisor_rfc',
                'emisor_name',
                'trandate',
                'efecto_comprobante_text',
                'status_text'
            ]"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} datos de facturacion"
        >
            <template #header>
                <div
                    class="flex flex-wrap gap-2 items-end justify-between mb-6"
                >
                    <div>
                        <h4 class="m-0">facturacion</h4>
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
                <div class="flex flex-wrap gap-2 items-center mt-3">
                    <template v-if="selectedBranchOffice?.includes('all')">
                        <Chip label="Todas las plantas" icon="pi pi-map-marker" />
                    </template>
                    <template v-else>
                        <Chip
                            v-for="plant in selectedBranchOffice"
                            :key="plant"
                            removable
                            @remove="removePlant(plant)"
                            :label="getPlantName(plant)"
                        />
                    </template>

                    <Chip
                        v-if="activeFilters.fecha && activeFilters.fecha[0] && activeFilters.fecha[1]"
                        :label="`Fecha: ${formatDate(activeFilters.fecha[0])} - ${formatDate(activeFilters.fecha[1])}`"
                        removable
                        @remove="clearDateFilter"
                    />

                    <Chip
                        v-for="dep in activeFilters.department"
                        :key="dep.id || dep"
                        :label="dep.name || dep"
                        removable
                        @remove="removeDepartment(dep.id || dep)"
                    />

                    <Chip
                        v-for="clase in activeFilters.classNetsuite"
                        :key="clase.id || clase"
                        :label="getClassName(clase)"
                        removable
                        @remove="removeClass(clase.id || clase)"
                    />

                    <Chip
                        v-for="est in activeFilters.estados"
                        :key="est"
                        :label="`Efecto: ${est}`"
                    />

                    <Chip
                        v-if="activeFilters.excluidos"
                        label="Excluidos"
                        removable
                        @remove="removeToggle('excluidos')"
                    />

                    <Chip
                        v-if="activeFilters.conXML"
                        label="Con XML"
                        removable
                        @remove="removeToggle('xml')"
                    />

                    <Chip
                        v-if="activeFilters.conPDF"
                        label="Con PDF"
                        removable
                        @remove="removeToggle('pdf')"
                    />
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
                    <div v-else class="flex flex-row flex-nowrap" style="min-width: 12rem;">
                        <Button
                            icon="pi pi-file-excel"
                            class="mr-2"
                            rounded
                            v-tooltip.top="'XML'"
                            severity="success"
                            @click="() => verXML(slotProps.data)"
                        />

                        <Button
                            icon="pi pi-file-pdf"
                            severity="danger"
                            v-tooltip.top="'PDF'"
                            class="mr-2"
                            rounded
                            @click="() => verPDF(slotProps.data)"
                        />

                        <Button
                            v-if="slotProps.data.send_status === 'pending'"
                            icon="pi pi-send"
                            severity="help"
                            v-tooltip.top="'SUBIR A NETSUITE'"
                            rounded
                            @click="() => enviarANetsuite(slotProps.data)"
                        />
                    </div>
                </template>
            </Column>
            <Column
                field="send_status"
                header="Estatus"
                :filter="true"
                :frozen="frozenColumns.estatus_icono"
                :style="{
                    width: '20rem',
                    display: showColumns.estatus_icono ? '' : 'none',
                }"
                :exportable="exportColumns.estatus_icono"
            >
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <div v-else>
                        <Tag
                            v-if="data.send_status === 'correct' || data.send_status === '0'"
                            severity="success"
                            value="Correcto"
                            icon="pi pi-check-circle"
                        />

                        <Tag
                            v-else-if="data.send_status === 'pending' || data.send_status === '2'"
                            severity="warn"
                            value="Pendiente"
                            icon="pi pi-clock"
                        />

                        <Tag
                            v-else-if="data.send_status === 'error' || data.send_status === '1'"
                            severity="danger"
                            value="Error"
                            icon="pi pi-exclamation-triangle"
                        />

                        <Tag
                            v-else
                            severity="secondary"
                            :value="data.send_status || 'Sin Estado'"
                            icon="pi pi-question-circle"
                        />
                    </div>
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
                field="uuid"
                header="UID"
                sortable
                :frozen="frozenColumns.uid"
                :style="{
                    width: '20rem',
                    display: showColumns.uid ? '' : 'none',
                }"
                :exportable="exportColumns.uid"
            >
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <span v-else>{{ data.uuid }}</span>
                </template>
                <template #filter="{ filterModel }">
                    <InputText
                        v-model="filterModel.value"
                        type="text"
                        placeholder="Buscar por UID"
                    /> </template
            ></Column>

            <Column
                field="subsidiary"
                header="Empresa"
                sortable
                :frozen="frozenColumns.empresa"
                :style="{
                    width: '20rem',
                    display: showColumns.empresa ? '' : 'none',
                }"
                :exportable="exportColumns.empresa"
            >
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <span v-else>{{ data.subsidiary }}</span>
                </template>
                <template #filter="{ filterModel }">
                    <InputText
                        v-model="filterModel.value"
                        type="text"
                        placeholder="Buscar por Telefono"
                    /> </template
            ></Column>
            <Column
                field="emisor_rfc"
                header="Emisor RFC"
                :frozen="frozenColumns.emisor_RFC"
                sortable
                :style="{
                    width: '20rem',
                    display: showColumns.emisor_RFC ? '' : 'none',
                }"
                :exportable="exportColumns.emisor_RFC"
            >
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <span v-else>
                        {{data.emisor_rfc}}
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
                field="emisor_name"
                header="Emisor Nombre"
                sortable
                :frozen="frozenColumns.emisor_nombre"
                :style="{
                    width: '20rem',
                    display: showColumns.emisor_nombre ? '' : 'none',
                }"
                :exportable="exportColumns.emisor_nombre"
            >
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <span v-else>{{ data.emisor_name }}</span>
                </template>
                <template #filter="{ filterModel }">
                    <InputText
                        v-model="filterModel.value"
                        type="text"
                        placeholder="Buscar por Dias disfrute"
                    /> </template
            ></Column>

            <Column
                field="trandate"
                header="Fecha Emision"
                sortable
                :frozen="frozenColumns.fecha_emision"
                :style="{
                    width: '20rem',
                    display: showColumns.fecha_emision ? '' : 'none',
                }"
                :exportable="exportColumns.fecha_emision"
            >
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <span v-else>{{ data.trandate }}</span>
                </template>
                <template #filter="{ filterModel }">
                    <InputText
                        v-model="filterModel.value"
                        type="text"
                        placeholder="Buscar por Fecha de Pago"
                    /> </template
            ></Column>

            <Column
                field="total"
                header="Importe"
                :filter="false"
                :frozen="frozenColumns.importe"
                :style="{
                    width: '20rem',
                    display: showColumns.importe ? '' : 'none',
                }"
                :exportable="exportColumns.importe"
            >
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <span v-else>{{ data.total }}</span>
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
                field="order_id"
                header="No. Orden"
                :filter="false"
                :frozen="frozenColumns.no_orden"
                :style="{
                    width: '20rem',
                    display: showColumns.no_orden ? '' : 'none',
                }"
                :exportable="exportColumns.no_orden"
            >

                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <div v-else style="min-width: 6rem;">
                        <InputText
                            v-model="data.order_id"
                            type="text"
                            placeholder="Sin orden"
                            class="w-full p-column-filter"
                        />
                    </div>
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
                field="invoice_category_id"
                header="Categoria"
                sortable
                :frozen="frozenColumns?.categoria"
                :style="{
                    width: '20rem',
                    display: showColumns?.categoria ? '' : 'none',
                }"
                :exportable="exportColumns?.categoria"
            >
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <div v-else>
                        <Select
                            v-model="data.invoice_category_id"
                            :options="props.NetsuiteExpenseCategory"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Selecciona Categoría"
                            class="w-full"
                            filter
                        />
                    </div>

                </template>
            </Column>

            <Column
                field="invoice_location_id"
                header="Ubicación"
                :frozen="frozenColumns.ubicacion"
                sortable
                :style="{
                    width: '20rem',
                    display: showColumns.ubicacion ? '' : 'none',
                }"
                :exportable="exportColumns.ubicacion"
            >
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <div v-else>
                        <Select
                            v-model="data.invoice_location_id"
                            :options="props.InvoiceLocation"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Selecciona Ubicacion"
                            class="w-full"
                            filter
                        />
                    </div>
                </template>
            </Column>

            <Column
                field="invoice_department_id"
                header="Departamento"
                sortable
                :frozen="frozenColumns.departamento"
                :style="{
                    width: '20rem',
                    display: showColumns.departamento ? '' : 'none',
                }"
                :exportable="exportColumns.departamento"
            >
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <div v-else>
                        <Select
                            v-model="data.invoice_department_id"
                            :options="props.Departments"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Selecciona departamento"
                            class="w-full"
                            filter
                        />
                    </div>

                </template>
            ></Column>

            <Column
                field="invoice_class_id"
                header="Clase"
                sortable
                :frozen="frozenColumns.clase"
                :style="{
                    width: '20rem',
                    display: showColumns.clase ? '' : 'none',
                }"
                :exportable="exportColumns.clase"
            >
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <div v-else>
                        <Select
                            v-model="data.invoice_class_id"
                            :options="props.NetsuiteClass"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Selecciona Clase"
                            class="w-full"
                            filter
                        />
                    </div>

                </template>
            ></Column>
            <Column
                field="notes"
                header="Notas"
                :filter="false"
                :frozen="frozenColumns.notas"
                :style="{
                    width: '20rem',
                    display: showColumns.notas ? '' : 'none',
                }"
                :exportable="exportColumns.notas"
            >
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <div v-else style="min-width: 6rem;">
                        <InputText
                            v-model="data.notes"
                            type="text"
                            placeholder="Sin nota"
                            class="w-full p-column-filter"
                        />
                    </div>
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
                field="invoice_term_id"
                header="Termino"
                :filter="true"
                :frozen="frozenColumns.termino"
                :style="{
                    width: '20rem',
                    display: showColumns.termino ? '' : 'none',
                }"
                :exportable="exportColumns.termino"
            >
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <div v-else>
                        <Select
                            v-model="data.invoice_term_id"
                            :options="props.InvoiceTerm"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Selecciona Termino"
                            class="w-full"
                            filter
                        />
                    </div>

                </template>
            </Column>

            <Column
                field="invoice_accounting_id"
                header="Importación"
                sortable
                :frozen="frozenColumns.importacion"
                :style="{
                    width: '20rem',
                    display: showColumns.importacion ? '' : 'none',
                }"
                :exportable="exportColumns.importacion"
            >
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <div v-else>
                        <Select
                            v-model="data.invoice_accounting_id"
                            :options="props.InvoiceAccountingList"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Selecciona Importacion"
                            class="w-full"
                            filter
                        />
                    </div>

                </template>
            ></Column>
            <Column
                field="invoice_accounting_id"
                header="Articulo"
                :frozen="frozenColumns.articulo"
                sortable
                :style="{
                    width: '20rem',
                    display: showColumns.articulo ? '' : 'none',
                }"
                :exportable="exportColumns.articulo"
            >
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <Select
                        v-else
                        v-model="data.invoice_article_id"
                        :options="props.InvoiceArticle"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Selecciona Articulo"
                        class="w-full"
                        filter
                    />
                </template>
            </Column>

            <Column
                field="invoice_exclusion_category_id"
                header="Exclusión"
                sortable
                :frozen="frozenColumns.exclusion"
                :style="{
                    width: '20rem',
                    display: showColumns.exclusion ? '' : 'none',
                }"
                :exportable="exportColumns.exclusion"
            >
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <Select
                        v-else
                        v-model="data.invoice_exclusion_category_id"
                        :options="props.InvoiceExclusionCategory"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Selecciona Exclusion"
                        class="w-full"
                        filter
                    />
                </template>
            ></Column>

            <Column
                field="provider_id"
                header="Proveedor Generico"
                :filter="true"
                :frozen="frozenColumns.proveedor_generico"
                :style="{
                    width: '20rem',
                    display: showColumns.proveedor_generico ? '' : 'none',
                }"
                :exportable="exportColumns.proveedor_generico"
            >
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>

                    <Select
                        v-else
                        v-model="data.provider_id"
                        :options="invoiceGeneric"
                        optionLabel="name"
                        optionValue="code"
                        placeholder="Selecciona Proveedor Genérico"
                        class="w-full"
                        filter
                    />
                </template>
            </Column>
            <Column
                field="invoice_operation_type_id"
                header="Tipo de Operación"
                :filter="true"
                :frozen="frozenColumns.tipo_operacion"
                :style="{
                    width: '20rem',
                    display: showColumns.tipo_operacion ? '' : 'none',
                }"
                :exportable="exportColumns.tipo_operacion"
            >
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <Select
                        v-else
                        v-model="data.invoice_operation_type_id"
                        :options="props.InvoiceOperationType"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Selecciona Tipo Operacion"
                        class="w-full"
                        filter
                    />
                </template>
            </Column>

            <Column
                field="efecto_comprobante"
                header="Tipo"
                sortable
                :frozen="frozenColumns.tipo"
                :style="{
                    width: '20rem',
                    display: showColumns.tipo ? '' : 'none',
                }"
                :exportable="exportColumns.tipo"
            >
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <div v-else>
                        <Tag
                            v-if="data.efecto_comprobante === 'I'"
                            severity="success"
                            value="Ingreso"
                            icon="pi pi-arrow-down-left"
                        />

                        <Tag
                            v-else-if="data.efecto_comprobante === 'P'"
                            severity="info"
                            value="Pago"
                            icon="pi pi-wallet"
                        />

                        <Tag
                            v-else-if="data.efecto_comprobante === 'E'"
                            severity="danger"
                            value="Egreso"
                            icon="pi pi-arrow-up-right"
                        />

                        <Tag
                            v-else-if="data.efecto_comprobante === 'N'"
                            severity="info"
                            value="Nomina"
                            icon="pi pi-arrow-up-right"
                        />

                        <span v-else class="text-sm text-muted">{{ data.efecto_comprobante }}</span>
                    </div>
                </template>
                <template #filter="{ filterModel }">
                    <InputText
                        v-model="filterModel.value"
                        type="text"
                        placeholder="Buscar por Tipo"
                    /> </template
            ></Column>
            <Column
                field="status"
                header="Estatus"
                :frozen="frozenColumns.estatus"
                sortable
                :style="{
                    width: '20rem',
                    display: showColumns.estatus ? '' : 'none',
                }"
                :exportable="exportColumns.estatus"
            >
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <div v-else>
                        <Tag
                            v-if="data.status === '0'"
                            severity="danger"
                            value="Cancelado"
                            icon="pi pi-times-circle"
                        />

                        <Tag
                            v-else-if="data.status === '1'"
                            severity="success"
                            value="Vigente"
                            icon="pi pi-check-circle"
                        />

                        <Tag
                            v-else-if="data.status === '2'"
                            severity="warn"
                            value="En Proceso"
                            icon="pi pi-spinner pi-spin"
                        />

                        <span v-else class="text-sm text-muted">
                            {{ data.status || 'Sin Estatus' }}
                        </span>
                    </div>
                </template>
                <template #filter="{ filterModel }">
                    <InputText
                        v-model="filterModel.value"
                        type="text"
                        placeholder="Buscar por Fecha Disfrute"
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
        >
            <div class="flex flex-col gap-5">
                <div class="flex flex-col md:flex-row gap-6 mt-4">
                    <FloatLabel variant="on" class="w-full md:w-1/2">
                        <Multiselect
                            v-model="selectedBranchOffice"
                            @update:modelValue="updateBranches"
                            :options="branchOfficesWithAll"
                            optionLabel="code"
                            optionValue="id"
                            filter
                            class="w-full"
                        />
                        <label>Plantas</label>
                    </FloatLabel>
                    <FloatLabel variant="on" class="w-full md:w-1/2">
                        <DatePicker
                            v-model="valueFrom"
                            selectionMode="range"
                            inputId="fecha"
                            showIcon
                            iconDisplay="input"
                            class="w-full"
                        />
                        <label for="fecha">Fecha</label>
                    </FloatLabel>
                </div>

                <div class="flex flex-col md:flex-row gap-6">
                    <FloatLabel variant="on" class="flex-1 mt-2">
                        <Multiselect v-model="selectedEstados" :options="estados" optionLabel="name" optionValue="code" display="chip" class="w-full" />
                        <label>Efecto Comprobante</label>
                    </FloatLabel>
                    <FloatLabel variant="on" class="flex-1 mt-2">
                        <Multiselect v-model="selectedEstatus" :options="estatus" optionLabel="name" optionValue="code" display="chip" class="w-full" />
                        <label>Estatus</label>
                    </FloatLabel>
                </div>

                <div class="flex flex-col md:flex-row gap-6">
                    <FloatLabel variant="on" class="flex-1 mt-2">
                        <MultiSelect v-model="selectedDepartments" :options="props.Departments" optionLabel="name" display="chip" class="w-full" />
                        <label>Departamentos</label>
                    </FloatLabel>
                    <FloatLabel variant="on" class="flex-1 mt-2">
                        <MultiSelect v-model="selectedClassNetsuite" :options="props.NetsuiteClass" optionLabel="name" display="chip" class="w-full" />
                        <label>Clases</label>
                    </FloatLabel>
                </div>

                <div class="flex gap-5">
                    <div class="flex items-center gap-2">
                        <ToggleSwitch v-model="checkExcluidos" />
                        <label>Excluidos</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <ToggleSwitch v-model="checkConXML" />
                        <label>Con XML</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <ToggleSwitch v-model="checkConPDF" />
                        <label>Con PDF</label>
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Cancelar" severity="danger" @click="otherFilterDialog = false" />
                <Button label="Filtrar" severity="success" @click="applyFilters" />
            </template>
        </Dialog>
    </div>
</template>
