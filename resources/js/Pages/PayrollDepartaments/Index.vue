<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToastService } from "@/Stores/toastService";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import { onMounted, ref, computed, watch } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { useConfirm } from "primevue/useconfirm";
import { router } from '@inertiajs/vue3';
import { useAuthz } from "@/composables/useAuthz";
const { can } = useAuthz();

const props = defineProps({
    branchOffices: Array,
    payrrollTypes: Array
})

// Variables globales
const branch_offices = ref();
const employees = ref();
const deparments = ref();
const itemToDelete = ref();
const deleteDialog = ref();
const holiday = ref();
const confirm = useConfirm();
const processing = ref(false);


//Inicializar filtros globales de tabla
const filters = ref({});

//Inicializar variable de carga para mostrar esqueleto
const loading = ref(true);

//Función para mostrar toast de éxito y error
const { showSuccess, showError, processingPayroll, errorPayroll } = useToastService();

//Función para inicializar filtros
const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        id: { value: null, matchMode: FilterMatchMode.CONTAINS },
    };
};


initFilters();

//Columnas de exportación
const exportColumns = ref({
    Id : true,
    Semana: true,
    Año: true,
    Tipo_Asiento: true,
    Planta: true,
    Empleados: true,
    Debito: true,
    Credito: true,
    Totales: true,
    Folio: true,
    Estatus: true,
});

//Columnas visibles
const showColumns = ref({
    acciones : true,
    Id : true,
    Semana: true,
    Año: true,
    Tipo_Asiento: true,
    Planta: true,
    Empleados: true,
    Debito: true,
    Credito: true,
    Totales: true,
    Folio: true,
    Estatus: true,
    
})


//Diálogo de filtros adicionales
const otherFilterDialog = ref(false);

//Filtros del modal
const selectedDate = ref(false);
const selectedBranchOfficeId = ref(null);
// const selectedBranchOfficeName = ref(null);
const selectedEmployees = ref(null);
const selectedPayrrollTypes = ref(null);
const selectedBranchOffices = ref(null);


//Referencia a la tabla de datos
const dt = ref(null);
const toast = useToast();

//Columnas fijas
const frozenColumns = ref({
    Id : false,
    Semana: false,
    Año: false,
    Tipo_Asiento: false,
    Planta: false,
    Empleados: true,
    Debito: true,
    Credito: true,
    Totales: true,
    Folio: true,
    Estatus: true,
});

//Diálogo de selección de columnas
const columnsDialog = ref(false);

//Estado de envío
const submitted = ref(false);

//Referencias a los popovers
const op = ref();
const opMostrarColumnas = ref();
const opFijarColumnas = ref();

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

//Inicializacion de una fila para mostrar el esqueleto de inicio en lo que carga la tabla
const rows = ref([
    {
        numero_nomina: 0,
        empleado: '',
    },
]);


//Filas seleccionadas
const selected = ref([]);

//Función para guardar las columnas seleccionadas para exportar
const saveColumns = () => {
    columnsDialog.value = false;
    dt.value.exportCSV();
};

//Función para limpiar filtros
const clearFilter = () => {
    const current = getCurrentWeek()
    selectedWeeks.value = [
        {
            value: `${current.year}-${current.week}`,
            year: current.year,
            week: current.week
        }
    ]

    filter_data();
};

//Función para aplicar filtros adicionales
const filter_data = async () => {
    loading.value = true;
    otherFilterDialog.value = false;
    const weeks = selectedWeeks.value.map(w => w.week);
    const years = selectedWeeks.value.map(w => w.year);
    try {
        const response = await axios.get('/payroll/payroll-departaments/filter-data', {
            params: {
                branch_offices: selectedBranchOffices.value ?? null,
                weeks: weeks,
                years: years
            }
        });
        let data = response?.data        
        employees.value = data.employees;
        rows.value = data.rows;

    } catch (error) {
        console.error("Error refrescando tabla:", error);
        showError("No se pudo actualizar la tabla");
    }
    loading.value = false;
    
};

const getDocument = (urlPath, payrollId) => {
    window.open(`payroll-departaments/payroll-h/${payrollId}`, "_blank");
};

onMounted(async () => {
    const today = new Date();
    selectedDate.value = [today, today];
    

    deparments.value = props.deparments;
    branch_offices.value = props.branchOffices;
    const savedBranch = JSON.parse(localStorage.getItem("selectedBranchOffice"));
    if (savedBranch) {
        const match = branch_offices.value.find(b => b.id === savedBranch.id);
        selectedBranchOffices.value = [savedBranch.id];
        // selectedBranchOfficeName.value = match.code;
    }
    const current = getCurrentWeek()

    selectedWeeks.value = [
        {
            value: `${current.year}-${current.week}`,
            year: current.year,
            week: current.week
        }
    ]
    filter_data();

});

const deleteItem = async () => {
    loading.value = true;
    try {
        const response = await axios.delete(
            route("payroll-departaments.destroy", itemToDelete.value.id)
        );
        deleteDialog.value = false;
        loading.value = false;

        filter_data();
        showSuccess(response.data.message);

    } catch (error) {
        console.log(error.response?.data);
        loading.value = false;
        showError();
    }
};

const formatDate = (date) => {
    if (!date) return null;
    const year  = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day   = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};


const allBranchOfficeIds = computed(() =>
    props.branchOffices.map(b => b.id)
)

const branchOfficesWithAll = computed(() => [
    { id: 'all', code: 'TODAS LAS PLANTAS' },
    ...props.branchOffices
])

const updateBranches = (values) => {
    const allIds = allBranchOfficeIds.value
    const hadAllSelected =
        selectedBranchOffices.value.length === allIds.length + 1 &&
        selectedBranchOffices.value.includes('all')

    const hasAllNow = values.includes('all')

    if (hasAllNow && !hadAllSelected && values.length !== allIds.length) {
        selectedBranchOffices.value = ['all', ...allIds]
        return
    }

    if (!hasAllNow && hadAllSelected && values.length === allIds.length) {
        selectedBranchOffices.value = []
        return
    }

    if (hasAllNow && values.length < allIds.length + 1) {
        selectedBranchOffices.value = values.filter(v => v !== 'all')
        return
    }

    const onlyRealIds = values.filter(v => v !== 'all')
    if (onlyRealIds.length === allIds.length) {
        selectedBranchOffices.value = ['all', ...allIds]
        return
    }

    selectedBranchOffices.value = onlyRealIds
}

const selectedBranchOfficeName = computed(() => {
    if (!selectedBranchOffices.value?.length) return []

    return props.branchOffices
        .filter(b => selectedBranchOffices.value.includes(b.id))
        .map(b => b.code)
})

const removePlant = (plantId) => {
    selectedBranchOffices.value =
    selectedBranchOffices.value.filter(id => id !== plantId)
    filter_data()
}

const getPlantName = (id) => {
    const plant = props.branchOffices.find(b => b.id === id)
    return plant ? plant.code : id
}

const weekInput = ref(null)
const selectedWeeks = ref([])

const addWeek = () => {

    if (!weekInput.value) return

    const [year, week] = weekInput.value.split('-W')

    const value = `${year}-${week}`

    const exists = selectedWeeks.value.find(w => w.value === value)

    if (!exists) {
        selectedWeeks.value.push({
            value,
            year,
            week
        })
    }

    weekInput.value = null

    // filter_data()
}

const removeWeek = (value) => {

    selectedWeeks.value =
    selectedWeeks.value.filter(w => w.value !== value)

    filter_data()
}

const getCurrentWeek = () => {
    const date = new Date()
    const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()))
    d.setUTCDate(d.getUTCDate() + 4 - (d.getUTCDay() || 7))
    const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1))
    const week = Math.ceil((((d - yearStart) / 86400000) + 1) / 7)
    return {
        year: d.getUTCFullYear(),
        week: week
    }
}

const responseNetsuite = ref(null)

const sendToNetSuite = (id) => {
    confirm.require({
        message: '¿Estás seguro(a) de enviar la nómina a NetSuite?',
        header: 'Confirmación',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Sí, enviar',
        rejectLabel: 'Cancelar',

        accept: () => {
            processing.value = true;
            processingPayroll();
            $.ajax({
                url: `https://grupo-ortiz.site/apis/Controllers/payrollController.php?op=payroll`,
                type: "POST",
                data: { id_nomina: id },    
            })
            .done(function (response) {

                console.log('RESPUESTA 1:', response);

                if (!response) {
                    console.error("La primera operación falló");
                    showError();
                    return;
                }

                $.ajax({
                    url: `https://grupo-ortiz.site/apis/Controllers/payrollController.php?op=send-payroll`,
                    type: "POST",
                    data: { id_nomina: id },
                    dataType: "json",
                })
                .done(function (response2) {
                    console.log('RESPUESTA 2:', response2);

                    if (!response2.ok) {

                        const errorMsg = response2.debug?.error ?? 'Error desconocido en NetSuite';
                        const debit  = response2.stats?.debitTotal ?? 0;
                        const credit = response2.stats?.creditTotal ?? 0;
                        const diff   = (debit - credit).toFixed(2);
                        let data = {
                            credito: credit,
                            debito: debit,
                            diferencia: diff
                        }

                        errorPayroll(data);
                        toast.removeGroup('processing');
                        return;
                    }
                    processing.value = false;
                    toast.removeGroup('processing');
                    showSuccess();
                    filter_data();

                })
                .fail(function (xhr, status, error) {

                    console.error("No se pudo comunicar con NetSuite:", error);
                    showError();

                });

            })
            .fail(function (xhr, status, error) {

                console.error("No se pudo registrar la nómina:", error);
                showError();

            }); 
        }
    })

};


</script>

<template>
    <AppLayout :title="'Nominas Empleados'">
        <div class="card">
            <ConfirmDialog />
            <Toast group="processing" />
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
                        type="button"
                        icon="pi pi-upload"
                        label="Exportar"
                        severity="secondary"
                        class="mt-2 ml-2"
                        @click="columnsDialog = true"
                        v-if="(can('payroll-departaments.export'))"
                    />
                </template>
                <template #end>
                    
                    <Link :href="route('payroll-departaments.create')" v-if="(can('payroll-departaments.create'))">
                        <Button
                            label="Crear"
                            icon="pi pi-plus-circle"
                            severity="success"
                            class="ml-2"
                        />
                    </Link>
                </template>
            </Toolbar>
            <!-- <pre>{{ responseNetsuite }}</pre> -->
            
            <DataTable
                ref="dt"
                v-model:selection="selected"
                :value="rows"
                dataKey="id"
                :paginator="true"
                :rows="50"
                scrollable
                scrollHeight="600px"
                tableStyle="min-width: 120rem"
                v-model:filters="filters"
                filterDisplay="menu"
                exportFilename="Nominas Empleados"
                :globalFilterFields="[
                    'id',
                    'payroll_branch_office',
                    'external_id'
                ]"

                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} datos de Nominas Empleados"
            >
                <template #header>
                    <div class="flex flex-wrap gap-2 items-end justify-between mb-6">
                        <div>
                            <h4 class="m-0">Nominas Empleados</h4>
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
                        <template v-if="selectedBranchOffices?.includes('all')">
                            <Chip
                                label="Todas las plantas"
                            />
                        </template>

                        <template v-else>
                            <Chip
                                v-for="plant in selectedBranchOffices"
                                :key="plant"
                                removable
                                @remove="removePlant(plant)"
                                :label="getPlantName(plant)"
                            />
                        </template>

                        <Chip
                            v-for="item in selectedWeeks"
                            :key="item.value"
                            removable
                            @remove="
                                () => {
                                    removeWeek(item.value)
                                    filter_data();
                                }
                            "
                            :label="'Año ' + item.year + ' - Semana ' + item.week"
                        />
                    </div>
                </template>
                <Column
                    :exportable="false"
                    columnKey="acciones"
                    :style="{
                        width: '15rem',
                        display: showColumns.acciones ? '' : 'none',
                        minWidth: '15rem',
                    }"
                    header="Acciones"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <div v-else>
                            <Button
                                icon="pi pi-download"
                                class="mr-2"
                                rounded
                                severity="success"
                                v-tooltip.top="'Descargar Documentos'"
                                :loading="processing"
                               @click="getDocument(data.pdf_path, data.id)"
                            />
                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                v-tooltip.top="'Eliminar'"
                                class="mr-2"
                                :loading="processing"
                                rounded
                                @click="
                                    () => {
                                        itemToDelete = data;
                                        deleteDialog = true;
                                    }
                                "
                                v-if="(can('payroll-departaments.delete'))"
                            />

                            <Button
                            v-if="data.external_id == null"
                                icon="pi pi-send"
                                severity="success"
                                v-tooltip.top="'Enviar a NetSuite'"
                                :loading="processing"
                                rounded
                                @click="sendToNetSuite(data.id)"
                            />
                        </div>
                    </template>
                </Column>
                <Column
                    field="id"
                    header="#"
                    :filter="true"
                    :frozen="frozenColumns.Id"
                    :style="{
                        width: '7rem',
                        display: showColumns.Id ? '' : 'none',
                    }"
                    :exportable="exportColumns.Id"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.id }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por titulo"
                        />
                    </template>
                </Column>
                
                <Column
                    field="week"
                    header="Semana"
                    :filter="true"
                    :frozen="frozenColumns.Semana"
                    :style="{
                        width: '10rem',
                        display: showColumns.Semana ? '' : 'none',
                    }"
                    :exportable="exportColumns.Semana"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.week }}</span>
                    </template>
                </Column>
                <Column
                    field="year"
                    header="Año"
                    :filter="true"
                    :frozen="frozenColumns.Año"
                    :style="{
                        width: '10rem',
                        display: showColumns.Año ? '' : 'none',
                    }"
                    :exportable="exportColumns.Año"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.year }}</span>
                    </template>
                </Column>
                
                <Column
                    field="payroll_type"
                    header="Tipo de Asiento"
                    :filter="true"
                    :frozen="frozenColumns.Tipo_Asiento"
                    :style="{
                        width: '10rem',
                        display: showColumns.Tipo_Asiento ? '' : 'none',
                    }"
                    :exportable="exportColumns.Tipo_Asiento"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.payroll_type.name }}</span>
                    </template>
                </Column>

                <Column
                    field="payroll_branch_office"
                    header="Planta"
                    :filter="true"
                    :frozen="frozenColumns.Planta"
                    :style="{
                        width: '10rem',
                        display: showColumns.Planta ? '' : 'none',
                    }"
                    :exportable="exportColumns.Planta"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.payroll_branch_office.code }}</span>
                    </template>
                </Column>

                <Column
                    field="employees"
                    header="Empleados"
                    :filter="true"
                    :frozen="frozenColumns.Empleados"
                    :style="{
                        width: '10rem',
                        display: showColumns.Empleados ? '' : 'none',
                    }"
                    :exportable="exportColumns.Empleados"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.employees }}</span>
                    </template>
                </Column>

                <Column
                    field="debit"
                    header="Dedito"
                    :filter="true"
                    :frozen="frozenColumns.Debito"
                    :style="{
                        width: '10rem',
                        display: showColumns.Debito ? '' : 'none',
                    }"
                    :exportable="exportColumns.Debito"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.debit }}</span>
                    </template>
                </Column>

                <Column
                    field="credit"
                    header="Credito"
                    :filter="true"
                    :frozen="frozenColumns.Credito"
                    :style="{
                        width: '10rem',
                        display: showColumns.Credito ? '' : 'none',
                    }"
                    :exportable="exportColumns.Credito"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.credit }}</span>
                    </template>
                </Column>

                <Column
                    field="total"
                    header="Total"
                    :filter="true"
                    :frozen="frozenColumns.Totales"
                    :style="{
                        width: '10rem',
                        display: showColumns.Totales ? '' : 'none',
                    }"
                    :exportable="exportColumns.Totales"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.total }}</span>
                    </template>
                </Column>

                <Column
                    v-if="showColumns.Folio"
                    columnKey="folio"
                    field="external_id"
                    header="Folio"
                    :filter="true"
                    :frozen="frozenColumns.Folio"
                    :style="{ width: '10rem', minWidth:'10rem' }"
                    :exportable="exportColumns.Folio"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.external_id }}</span>
                    </template>
                </Column>

                <Column
                    v-if="showColumns.Estatus"
                    columnKey="estatus"
                    field="status"
                    header="Estatus"
                    :filter="true"
                    :frozen="frozenColumns.Estatus"
                    :style="{ width: '10rem', minWidth:'10rem' }"
                    :exportable="exportColumns.Estatus"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>

                        <Tag 
                            v-else
                            :value="data.external_id ? 'ENVIADO' : 'SIN ENVIAR'"
                            :severity="data.external_id ? 'success' : 'warning'"
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
                header="Filtros"
                :modal="true"
            > 
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Planta
                    </label>
                    <Multiselect
                        :modelValue="selectedBranchOffices"
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
                
                <div class="flex flex-col gap-2">

                    <label class="font-semibold text-sm text-gray-600 dark:text-gray-300">
                        Semana
                    </label>

                    <InputText
                        type="week"
                        v-model="weekInput"
                        class="w-full"
                        @change="addWeek"
                    />
                    <div class="flex flex-wrap gap-3">

                        <Chip
                            v-for="item in selectedWeeks"
                            :key="item.value"
                            removable
                            @remove="removeWeek(item.value)"
                            :label="'Año ' + item.year + ' - Semana ' + item.week"
                        />

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
                        @click="filter_data"
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
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                    <div class="flex items-center">
                        <i
                            class="pi pi-exclamation-triangle text-red-600 text-3xl mr-3"
                        ></i>
                        <div>
                            <h3 class="font-bold text-red-800">
                                ¿Estas seguro(a) de eliminar la nomina ({{itemToDelete.id }}) de la empresa {{itemToDelete.payroll_branch_office.code }}  ?
                            </h3>
                            <p class="text-sm text-red-700">
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
