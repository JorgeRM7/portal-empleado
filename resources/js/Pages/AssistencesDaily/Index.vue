<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToastService } from "@/Stores/toastService";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import { onMounted, ref } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { router } from '@inertiajs/vue3';


const props = defineProps({
    branchOffices: Array,
    incidences: Array,
    schedules: Array
})

// Variables globales
const branch_offices = ref();
const employees = ref();
const deparments = ref();
const itemToDelete = ref();
const deleteDialog = ref();
const holiday = ref();


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
        title: { value: null, matchMode: FilterMatchMode.CONTAINS },
    };
};


initFilters();

//Columnas de exportación
const exportColumns = ref({
    Clave_empleado : true,
    Nombre: true,
    Fecha: true,
    Semana: true,
    Año: true,
    Hora_entrada: true,
    Hora_salida: true,
    Incidencia: true,
    Turno: true,
});

//Columnas visibles
const showColumns = ref({
    Clave_empleado : true,
    Nombre: true,
    Fecha: true,
    Año: true,
    Semana: true,
    Hora_entrada: true,
    Hora_salida: true,
    Incidencia: true,
    Turno: true,
})


//Diálogo de filtros adicionales
const otherFilterDialog = ref(false);

//Filtros del modal
const selectedDate = ref(false);
const selectedBranchOfficeId = ref(null);
const selectedBranchOfficeName = ref(null);
const selectedEmployees = ref(null);
const selectedSchedules = ref(null);
const selectedIncidences = ref(null);


//Referencia a la tabla de datos
const dt = ref(null);
const toast = useToast();

//Columnas fijas
const frozenColumns = ref({
    Clave_empleado : true,
    Nombre: true,
    Fecha: true,
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
    selectedEmployees.value = null;
    selectedDeparments.value = null;
    selectedIncidences.value = null;
    filter_data();
};

//Función para aplicar filtros adicionales
const filter_data = async () => {
    loading.value = true;
    otherFilterDialog.value = false;
    const dateRange = selectedDate.value ?? [];
    const dateStart = formatDate(dateRange[0]);
    const dateEnd   = formatDate(dateRange[1]);

    try {
        const response = await axios.get('/assistences/assistences-daily/filter-data', {
            params: {
                branch_office_id: selectedBranchOfficeId.value?.id,
                date_start: dateStart ?? null,
                date_end: dateEnd ?? null,
                employees: selectedEmployees.value ?? null,
                schedules: selectedSchedules.value ?? null,
                incidences: selectedIncidences.value ?? null,

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

onMounted(async () => {
    const today = new Date();
    selectedDate.value = [today, today];
    deparments.value = props.deparments;
    branch_offices.value = props.branchOffices;
    const savedBranch = JSON.parse(localStorage.getItem("selectedBranchOffice"));
    if (savedBranch) {
        const match = branch_offices.value.find(b => b.id === savedBranch.id);
        selectedBranchOfficeId.value = match ?? null;
        selectedBranchOfficeName.value = selectedBranchOfficeId.value.code;
    }

    filter_data();
});


const formatDate = (date) => {
    if (!date) return null;
    const year  = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day   = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};
</script>

<template>
    <AppLayout :title="'Asistencias Diarias'">
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
                <!-- <template #end>
                    
                    <Link :href="route('events.create')">
                        <Button
                            label="Crear"
                            icon="pi pi-plus-circle"
                            severity="success"
                            class="ml-2"
                        />
                    </Link>
                </template> -->
            </Toolbar>
            <DataTable
                ref="dt"
                v-model:selection="selected"
                :value="rows"
                dataKey="id"
                :paginator="true"
                :rows="50"
                scrollable
                scrollHeight="600px"
                tableStyle="min-width: 110rem"
                v-model:filters="filters"
                filterDisplay="menu"
                exportFilename="Asistencia Diaria"
                :globalFilterFields="[
                    'employee_id',
                    'employee_name'
                ]"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} datos de asistencia"
            >
                <template #header>
                    <div class="flex flex-wrap gap-2 items-end justify-between mb-6">
                        <div>
                            <h4 class="m-0">Asistencias Diarias</h4>
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
                            v-if="selectedBranchOfficeName"
                            :label="'Planta: ' + selectedBranchOfficeName"
                        />
                        <Chip 
                            v-if="selectedDate != null"
                            :label="formatDate(selectedDate[0])+ ' a '+ formatDate(selectedDate[1])"
                        />
                    </div>
                </template>
                
                <Column
                    field="employee_id"
                    header="Clave Empleado"
                    :filter="true"
                    :frozen="frozenColumns.Clave_empleado"
                    :style="{
                        width: '10rem',
                        display: showColumns.Clave_empleado ? '' : 'none',
                    }"
                    :exportable="exportColumns.Clave_empleado"
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
                    field="employee_name"
                    header="Empleado"
                    :filter="true"
                    :frozen="frozenColumns.Nombre"
                    :style="{
                        width: '10rem',
                        display: showColumns.Nombre ? '' : 'none',
                    }"
                    :exportable="exportColumns.Nombre"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.employee_name }}</span>
                    </template>
                </Column>

                <Column
                    field="date"
                    header="Fecha"
                    :filter="true"
                    :frozen="frozenColumns.Fecha"
                    :style="{
                        width: '10rem',
                        display: showColumns.Fecha ? '' : 'none',
                    }"
                    :exportable="exportColumns.Fecha"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.date }}</span>
                    </template>
                </Column>
                <Column
                    field="week_number"
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
                        <span v-else>{{ data.week_number }}</span>
                    </template>
                </Column>
                <Column
                    field="week_year"
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
                        <span v-else>{{ data.week_year }}</span>
                    </template>
                </Column>

                <Column
                    field="entrance_time"
                    header="Hora de entrada"
                    :filter="true"
                    :frozen="frozenColumns.Hora_entrada"
                    :style="{
                        width: '10rem',
                        display: showColumns.Hora_entrada ? '' : 'none',
                    }"
                    :exportable="exportColumns.Hora_entrada"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.entrance_time }}</span>
                    </template>
                </Column>

                <Column
                    field="entrance_time"
                    header="Hora de Salida"
                    :filter="true"
                    :frozen="frozenColumns.Hora_salida"
                    :style="{
                        width: '10rem',
                        display: showColumns.Hora_salida ? '' : 'none',
                    }"
                    :exportable="exportColumns.Hora_salida"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.leave_time }}</span>
                    </template>
                </Column>

                <Column
                    field="incidence_code"
                    header="Incidencia"
                    sortable
                    :frozen="frozenColumns.Incidencia"
                    :style="{
                        width: '10rem',
                        display: showColumns.Incidencia ? '' : 'none',
                    }"
                    :exportable="exportColumns.Incidencia"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <Badge
                            :value="data.incidence_code"
                            size="xlarge" severity="success"
                            v-tooltip.top="data.incidence_name" v-else
                            :style="{
                                backgroundColor: data.incidence_color,
                                color: '#fff',
                                cursor: 'pointer'
                            }"
                            v-if="data.incidence_code">
                        </Badge>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar"
                        /> </template
                ></Column>

                <Column
                    field="shift_role"
                    header="Turno"
                    :filter="true"
                    :frozen="frozenColumns.Turno"
                    :style="{
                        width: '10rem',
                        display: showColumns.Turno ? '' : 'none',
                    }"
                    :exportable="exportColumns.Turno"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.shift_role }}</span>
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
                
                <div class="grid grid-cols-1 md:grid-cols-1 xl:grid-cols-1 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Planta
                        </label>
                        <Select v-model="selectedBranchOfficeId" display="chip"
                            :options="branch_offices" optionLabel="code" filter
                            placeholder="Selecciona una planta" class="w-full" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="block text-sm font-medium text-gray-700">
                            Fechas
                        </label>
                        <DatePicker
                            v-model="selectedDate"
                            selectionMode="range"
                            :manualInput="false"
                            class="w-full"
                        />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Turno
                        </label>
                        <Multiselect v-model="selectedSchedules" display="chip"
                            :options="props.schedules" optionLabel="name" filter optionValue="id"
                            placeholder="Selecciona una incidencia" class="w-full">

                            <template #value="slotProps">
                                <span v-if="!slotProps.value || !slotProps.value.length">
                                    Selecciona un Turno
                                </span>

                                <span v-else-if="slotProps.value.length > 5">
                                    {{ slotProps.value.length }} Turnos seleccionados
                                </span>
                            </template>
                        </Multiselect>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Incidencias
                        </label>
                        <Multiselect v-model="selectedIncidences" display="chip"
                            :options="incidences" optionLabel="name" filter optionValue="id"
                            placeholder="Selecciona una incidencia" class="w-full">

                            <template #value="slotProps">
                                <span v-if="!slotProps.value || !slotProps.value.length">
                                    Selecciona una incidencia
                                </span>

                                <span v-else-if="slotProps.value.length > 5">
                                    {{ slotProps.value.length }} incidencias seleccionadas
                                </span>
                            </template>
                        </Multiselect>
                    </div>
                    <div class="flex flex-col gap-1">
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
                                ¿Estas seguro de eliminar el evento de ({{itemToDelete.title }})  ?
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
