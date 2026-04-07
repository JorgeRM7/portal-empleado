<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToastService } from "@/Stores/toastService";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import { onMounted, ref } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { router } from '@inertiajs/vue3';


const props = defineProps({
    data: Array,
    branch_offices: Array,
    deparments: Array,
})

// Variables globales
const employees = ref(null);
const deparments = ref(null);



const modalDetails = ref(false);
const details = ref(null);
const modalTurns = ref(false)

//Inicializar filtros globales de tabla
const filters = ref({});

//Inicializar variable de carga para mostrar esqueleto
const loading = ref(true);

//Función para mostrar toast de éxito y error
const { showSuccess, showError } = useToastService();

//Función para inicializar filtros
const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        employee_id: { value: null, matchMode: FilterMatchMode.CONTAINS },
        document_imss: { value: null, matchMode: FilterMatchMode.CONTAINS },
        date: { value: null, matchMode: FilterMatchMode.CONTAINS },
        number_type_incapacity: { value: null, matchMode: FilterMatchMode.CONTAINS },
        observation_date: { value: null, matchMode: FilterMatchMode.CONTAINS }
    };
};


initFilters();

//Columnas de exportación
const exportColumns = ref({
    "Clave empleado": true,
    "Tipo de falta": true,
    "Porcentaje de falta": true,
    "Certificado IMSS": true,
    "Porcentaje pagado IMSS": true,
    "Fecha de inicio": true,
    "Fecha de fin": true,
    "Clave Tipo Incapacidad": true,
    Observaciones: true,
});

//Columnas visibles
const showColumns = ref({
    "Clave empleado": true,
    "Tipo de falta": true,
    "Porcentaje de falta": true,
    "Certificado IMSS": true,
    "Porcentaje pagado IMSS": true,
    "Fecha de inicio": true,
    "Fecha de fin": true,
    "Clave Tipo Incapacidad": true,
    Observaciones: true,
});

//Columnas fijas
const frozenColumns = ref({
    "Clave empleado": true,
    "Tipo de falta": false,
    "Porcentaje de falta": false,
    "Certificado IMSS": false,
    "Porcentaje pagado IMSS": false,
    "Fecha de inicio": false,
    "Fecha de fin": false,
    "Clave Tipo Incapacidad": false,
    Observaciones: false,
});



//Diálogo de filtros adicionales
const otherFilterDialog = ref(false);
const selectedBranchOfficeId = ref(null);
const selectedBranchOfficeName = ref(null);
const branch_offices = ref();
const selectedWeek = ref(null);
const selectedYear = ref(null);
const selectedDeparments = ref(null);
const selectedEmployees = ref(null);
const selectedTipo = ref(null);

//Referencia a la tabla de datos
const dt = ref(null);
const toast = useToast();


//Diálogo de selección de columnas
const columnsDialog = ref(false);

const opMostrarColumnas = ref();
const opFijarColumnas = ref();

//Estado de diálogo de subida de archivos
const openUploadDialog = ref(false);

//Estado de progreso de subida
const progress = ref(0);

//Estado de visibilidad del toast
const visible = ref(false);

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
const dates = ref();

//Filas seleccionadas
const selected = ref([]);

//Función para guardar las columnas seleccionadas para exportar
const saveColumns = () => {
    columnsDialog.value = false;
    dt.value.exportCSV();
};

//Función para limpiar filtros
const clearFilter = () => {
    selectedEmployees.value = null;
    selectedDeparments.value = null;
    selectedTipo.value = null;
    filter_data();
};

//Función para aplicar filtros adicionales
const filter_data = async () => {
    loading.value = true;
    const [anio, semana] = selectedWeek.value.split("-W");
    otherFilterDialog.value = false;
    selectedYear.value = anio;
    selectedBranchOfficeName.value = selectedBranchOfficeId.value.code
    try {
        const response = await axios.get('/assistences/absenteeism/filter-data', {
            params: {
                planta: selectedBranchOfficeId.value.id,
                semana: semana,
                anio: anio,
                empleados: selectedEmployees.value,
                departamento: selectedDeparments.value,
                tipo_falta: selectedTipo.value,
            }
        });
        rows.value = response.data.data;
        employees.value = response.data.employees

    } catch (error) {
        console.error("Error refrescando tabla:", error);
        showError("No se pudo actualizar la tabla");
    }

    loading.value = false;
};

onMounted(async () => {
    rows.value = props.data;
    branch_offices.value = props.branch_offices;
    deparments.value = props.deparments;
    loading.value = false;
    selectedWeek.value = getCurrentWeek();
    const savedBranch = JSON.parse(localStorage.getItem("selectedBranchOffice"));
    if (savedBranch) {
        const match = branch_offices.value.find(b => b.id === savedBranch.id);
        selectedBranchOfficeId.value = match ?? null;
        selectedBranchOfficeName.value = selectedBranchOfficeId.value.code;
    }
    filter_data();
});

const getCurrentWeek = () => {
    const now = new Date();
    const year = now.getFullYear();

    const firstDay = new Date(year, 0, 1);
    const pastDaysOfYear = (now - firstDay) / 86400000;
    const week = Math.ceil((pastDaysOfYear + firstDay.getDay() + 1) / 7);

    return `${year}-W${week.toString().padStart(2, "0")}`;
}

</script>

<template>
    <AppLayout :title="'Reporte de ausentismos'">
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
                :value="rows"
                dataKey="employee_id"
                :paginator="true"
                :rows="50"
                scrollable
                scrollHeight="600px"
                tableStyle="min-width: 110rem"
                v-model:filters="filters"
                filterDisplay="menu"
                exportFilename="Reporte de ausentismos"
                :globalFilterFields="[
                    'employee_id',
                    'observation_date',
                    'date',
                    'document_imss',
                ]"

                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} datos de asistencia"
            >
                <template #header>
                    <div
                        class="flex flex-wrap gap-2 items-end justify-between mb-6"
                    >
                        <div>
                            <h4 class="m-0">Reporte de ausentismos</h4>
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

                            :label="'Semana: ' + selectedWeek"
                        />
                        <Chip
                            v-if="selectedBranchOfficeName != null"
                            :label="'Planta: ' + selectedBranchOfficeName"
                        />
                        <Chip
                            v-if="selectedEmployees"
                            :label="'Empleados: ' + selectedEmployees"
                            removable
                            @remove="
                                () => {
                                    selected = [];
                                    selectedEmployees = null;
                                    filter_data();
                                }
                            "
                        />
                        <Chip
                            v-if="selectedDeparments"
                            :label="'Departamentos: ' + selectedDeparments"
                            removable
                            @remove="
                                () => {
                                    selected = [];
                                    selectedDeparments = null;
                                    filter_data();
                                }
                            "
                        />
                    </div>
                </template>
                <Column
                    field="employee_id"
                    header="Clave empleado"
                    :filter="true"
                    :frozen="frozenColumns['Clave empleado']"
                    :style="{
                        width: '10rem',
                        display: showColumns['Clave empleado'] ? '' : 'none',
                    }"
                    :exportable="exportColumns['Clave empleado']"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.employee_id }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Numero"
                        />
                    </template>
                </Column>
                <Column
                    field="type"
                    header="Tipo de falta"
                    :filter="true"
                    :frozen="frozenColumns['Tipo de falta']"
                    :style="{
                        width: '10rem',
                        display: showColumns['Tipo de falta'] ? '' : 'none',
                    }"
                    :exportable="exportColumns['Tipo de falta']"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.type }}</span>
                    </template>
                </Column>
                <Column
                    field="percentaje"
                    header="Porcentaje de falta"
                    :filter="true"
                    :frozen="frozenColumns['Porcentaje de falta']"
                    :style="{
                        width: '10rem',
                        display: showColumns['Porcentaje de falta'] ? '' : 'none',
                    }"
                    :exportable="exportColumns['Porcentaje de falta']"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ 100 }}</span>
                    </template>
                </Column>
                <Column
                    field="document_imss"
                    header="Certificado IMSS"
                    :filter="true"
                    :frozen="frozenColumns['Certificado IMSS']"
                    :style="{
                        width: '10rem',
                        display: showColumns['Certificado IMSS'] ? '' : 'none',
                    }"
                    :exportable="exportColumns['Certificado IMSS']"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.document_imss }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por certificado"
                        />
                    </template>
                </Column>
                <Column
                    field="percentaje_imss"
                    header="Porcentaje pagado IMSS"
                    :filter="true"
                    :frozen="frozenColumns['Porcentaje pagado IMSS']"
                    :style="{
                        width: '10rem',
                        display: showColumns['Porcentaje pagado IMSS'] ? '' : 'none',
                    }"
                    :exportable="exportColumns['Porcentaje pagado IMSS']"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ "" }}</span>
                    </template>
                </Column>
                <Column
                    field="date"
                    header="Fecha inicio"
                    :filter="true"
                    :frozen="frozenColumns['Fecha de inicio']"
                    :style="{
                        width: '10rem',
                        display: showColumns['Fecha de inicio'] ? '' : 'none',
                    }"
                    :exportable="exportColumns['Fecha de inicio']"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.date }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar..."
                        />
                    </template>
                </Column>
                <Column
                    field="date_end"
                    header="Fecha de fin"
                    :filter="true"
                    :frozen="frozenColumns['Fecha de fin']"
                    :style="{
                        width: '10rem',
                        display: showColumns['Fecha de fin'] ? '' : 'none',
                    }"
                    :exportable="exportColumns['Fecha de fin']"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ "" }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Numero"
                        />
                    </template>
                </Column>
                <Column
                    field="number_type_incapacity"
                    header="Clave Tipo Incapacidad"
                    :filter="true"
                    :frozen="frozenColumns['Clave Tipo Incapacidad']"
                    :style="{
                        width: '10rem',
                        display: showColumns['Clave Tipo Incapacidad'] ? '' : 'none',
                    }"
                    :exportable="exportColumns['Clave Tipo Incapacidad']"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.number_type_incapacity }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Numero"
                        />
                    </template>
                </Column>
                <Column
                    field="observation_date"
                    header="Observaciones"
                    :filter="true"
                    :frozen="frozenColumns.Observaciones"
                    :style="{
                        width: '10rem',
                        display: showColumns.Observaciones ? '' : 'none',
                    }"
                    :exportable="exportColumns.Observaciones"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.observation_date }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar..."
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
                    <Select v-model="selectedBranchOfficeId" display="chip"
                        :options="branch_offices" optionLabel="code" filter
                        placeholder="Selecciona una planta" class="w-full" />
                </div>
                <div>
                    <label class="font-semibold text-sm text-gray-600 dark:text-gray-300">
                        Semana
                    </label>
                    <InputText
                        type="week"
                        v-model="selectedWeek"
                        class="w-full"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Departamentos
                    </label>
                    <Multiselect v-model="selectedDeparments" display="chip"
                        :options="deparments" optionLabel="name" filter optionValue="id"
                        placeholder="Selecciona un departamento" class="w-full">

                        <template #value="slotProps">
                            <span v-if="!slotProps.value || !slotProps.value.length">
                                Selecciona un departamento
                            </span>

                            <span v-else-if="slotProps.value.length > 5">
                                {{ slotProps.value.length }} departamentos seleccionados
                            </span>
                        </template>
                    </Multiselect>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Empleados
                    </label>

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

                        <!-- Texto cuando no hay selección -->
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
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Tipo
                    </label>

                    <Select
                        v-model="selectedTipo"
                        :options="[
                            { label: 'TODOS...'},
                            { label: 'FALTA', value: '1' },
                            { label: 'INCAPACIDAD', value: '2' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Selecciona una opción"
                        class="w-full"
                    />
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
                    />
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>
