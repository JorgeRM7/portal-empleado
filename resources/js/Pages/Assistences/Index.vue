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
  employees: Array,
  branch_offices: Array
})

// Variables globales
const branch_offices = ref();
const employees = ref();
const deparments = ref();

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
        nombre_empleado: { value: null, matchMode: FilterMatchMode.CONTAINS },
        date: { value: null, matchMode: FilterMatchMode.CONTAINS },
        access_time: { value: null, matchMode: FilterMatchMode.CONTAINS },
        rol: { value: null, matchMode: FilterMatchMode.CONTAINS },
        device_name: { value: null, matchMode: FilterMatchMode.CONTAINS },
        dia_es: { value: null, matchMode: FilterMatchMode.CONTAINS },
    };
};



initFilters();

//Columnas de exportación
const exportColumns = ref({
    numero_nomina: true,
    foto: true,
    empleado: true,
    fecha: true,
    hora: true,
    dia: true,
    turno: true,
    dispositivo: true,
});

//Columnas visibles
const showColumns = ref({
    numero_nomina: true,
    foto: true,
    empleado: true,
    fecha: true,
    hora: true,
    dia: true,
    turno: true,
    dispositivo: true,
})


//Diálogo de filtros adicionales
const otherFilterDialog = ref(false);

//Filtros del modal
const selectedWeek = ref(false);
const selectedBranchOfficeId = ref(null);
const selectedBranchOfficeName = ref(null);
const selectedEmployees = ref(null);
const selectedDeparments = ref(null);
const selectedIncidences = ref(null);
const selectedDate =  ref(null);


//Referencia a la tabla de datos
const dt = ref(null);
const toast = useToast();

//Columnas fijas
const frozenColumns = ref({
    numero_nomina: true,
    foto: true,
    empleado: true,
    fecha: false,
    hora: false,
    dia: false,
    turno: false,
    dispositivo: false,
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
    selectedIncidences.value = null;
    filter_data();
};

//Función para aplicar filtros adicionales
const filter_data = async () => {
    loading.value = true;
    otherFilterDialog.value = false;
    selectedBranchOfficeName.value = selectedBranchOfficeId.value.code;
    try {
        let fecha_inicio = null;
        let fecha_fin = null;

        if (selectedWeek.value && selectedWeek.value.length === 2) {
            fecha_inicio = selectedWeek.value[0]
                ? selectedWeek.value[0].toISOString().split('T')[0]
                : null;

            fecha_fin = selectedWeek.value[1]
                ? selectedWeek.value[1].toISOString().split('T')[0]
                : null;
        }
        const response = await axios.get('/assistences/assistences/filter-data', {
            params: {
                planta: selectedBranchOfficeId.value.id ?? null,
                fecha_inicio,
                fecha_fin,
                empleados: selectedEmployees.value ?? [],
                departamento: selectedDeparments.value ?? [],
            }
        });
        rows.value = response.data.data;
        employees.value = response.data.employees;
        deparments.value = response.data.deparments;
        selectedDate.value = `${selectedWeek.value[0].toISOString().split('T')[0]} - ${selectedWeek.value[1].toISOString().split('T')[0]}`
        

    } catch (error) {
        console.error("Error refrescando tabla:", error);
        showError("No se pudo actualizar la tabla");
    }

    loading.value = false;
};

onMounted(async () => {
    const today = new Date();
    selectedWeek.value = [today, today];
    branch_offices.value = props.branch_offices;
    const savedBranch = JSON.parse(localStorage.getItem("selectedBranchOffice"));
    if (savedBranch) {
        const match = branch_offices.value.find(b => b.id === savedBranch.id);
        selectedBranchOfficeId.value = match ?? null;
        selectedBranchOfficeName.value = selectedBranchOfficeId.value.code;
    }
    
    filter_data();
});

</script>

<template>
    
    
    <AppLayout :title="'Registro de asistencias'">
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
                exportFilename="Registro de asistencias"
                :globalFilterFields="[
                    'employee_id',
                    'nombre_empleado',
                    'date',        
                ]"

                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} datos de asistencia"
            >
                <template #header>
                    <div
                        class="flex flex-wrap gap-2 items-end justify-between mb-6"
                    >
                        <div>
                            <h4 class="m-0">Registro de asistencias</h4>
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
                            <!-- <Chip
                                
                                :label="'Semana: ' + selectedWeek"
                                removable
                                @remove="
                                    () => {
                                        selected = [];
                                        selectedWeek = null;
                                        filter_data();
                                    }
                                "
                            /> -->

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
                            v-if="selectedBranchOfficeName != null"
                            :label="'Planta: ' + selectedBranchOfficeName"
                        />
                        <Chip
                            v-if="selectedDate"
                            :label="'Fecha: ' + selectedDate"
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
                    sortable
                    :filter="true"
                    :frozen="frozenColumns.numero_nomina"
                    :style="{
                        width: '10rem',
                        display: showColumns.numero_nomina ? '' : 'none',
                    }"
                    :exportable="exportColumns.numero_nomina"
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
                    header="Foto"
                    :style="{
                        width: '6rem',
                        textAlign: 'center',
                        display: showColumns.foto ? '' : 'none'
                    }"
                    :exportable="false"
                >
                    <template #body="{ data }">
                        <!-- Skeleton mientras carga -->
                        <Skeleton
                            v-if="loading"
                            shape="circle"
                            size="3rem"
                            class="mx-auto"
                        />

                        <!-- Avatar cuando ya cargó -->
                        <Avatar
                            v-else
                            :image="`https://nominas.grupo-ortiz.site/Librerias/img/Fotos/${data.employee_id}.jpg`"
                            shape="circle"
                            size="large"
                            class="p-avatar-info mx-auto"
                        />
                    </template>
                </Column>

                <Column
                    field="nombre_empleado"
                    header="Empleado"
                    sortable
                    :filter="true"
                    :frozen="frozenColumns.empleado"
                    :style="{
                        width: '10rem',
                        display: showColumns.empleado ? '' : 'none',
                    }"
                    :exportable="exportColumns.empleado"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.nombre_empleado }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por nombre"
                        />
                    </template>
                </Column>
                <Column
                    field="date"
                    header="Fecha"
                    sortable
                    :filter="true"
                    :frozen="frozenColumns.fecha"
                    :style="{
                        width: '10rem',
                        display: showColumns.fecha ? '' : 'none',
                    }"
                    :exportable="exportColumns.fecha"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.date }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por fecha"
                        />
                    </template>
                </Column>
                <Column
                    field="access_time"
                    header="Hora"
                    :filter="true"
                    :frozen="frozenColumns.hora"
                    :style="{
                        width: '10rem',
                        display: showColumns.hora ? '' : 'none',
                    }"
                    :exportable="exportColumns.hora"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.access_time }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por hora"
                        />
                    </template>
                </Column>
                <Column
                    field="dia_es"
                    header="Dia"
                    sortable
                    :filter="true"
                    :frozen="frozenColumns.dia"
                    :style="{
                        width: '10rem',
                        display: showColumns.dia ? '' : 'none',
                    }"
                    :exportable="exportColumns.dia"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.dia_es }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por dia"
                        />
                    </template>
                </Column>
                <Column
                    field="turno"
                    header="Turno"
                    sortable
                    :filter="true"
                    :frozen="frozenColumns.turno"
                    :style="{
                        width: '10rem',
                        display: showColumns.turno ? '' : 'none',
                    }"
                    :exportable="exportColumns.turno"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.turno }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por turno"
                        />
                    </template>
                </Column>
                <Column
                    field="dispositivo"
                    header="Dispositivo"
                    sortable
                    :filter="true"
                    :frozen="frozenColumns.dispositivo"
                    :style="{
                        width: '10rem',
                        display: showColumns.dispositivo ? '' : 'none',
                    }"
                    :exportable="exportColumns.dispositivo"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.dispositivo }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por dispositivo"
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
                <div class="grid grid-cols-1 md:grid-cols-1 xl:grid-cols-2 gap-4">

                    <!-- Planta -->
                    <div class="flex flex-col gap-1">
                        <label class="block text-sm font-medium text-gray-700">
                            Planta
                        </label>
                        <Select
                            v-model="selectedBranchOfficeId"
                            :options="branch_offices"
                            optionLabel="code"
                            filter
                            placeholder="Selecciona una planta"
                            class="w-full"
                        />
                    </div>

                    <!-- Fechas -->
                    <div class="flex flex-col gap-1">
                        <label class="block text-sm font-medium text-gray-700">
                            Fechas
                        </label>
                        <DatePicker
                            v-model="selectedWeek"
                            selectionMode="range"
                            :manualInput="false"
                            class="w-full"
                        />
                    </div>

                    <!-- Departamentos -->
                    <div class="flex flex-col gap-1">
                        <label class="block text-sm font-medium text-gray-700">
                            Departamentos
                        </label>
                        <Multiselect
                            v-model="selectedDeparments"
                            :options="deparments"
                            optionLabel="name"
                            optionValue="id"
                            filter
                            display="chip"
                            placeholder="Selecciona un departamento"
                            class="w-full"
                        >
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

                   
                    <div class="flex flex-col gap-1">
                        <label class="block text-sm font-medium text-gray-700">
                            Empleados
                        </label>
                        <Multiselect
                            v-model="selectedEmployees"
                            :options="employees"
                            optionLabel="full_name"
                            optionValue="id"
                            filter
                            :filterFields="['full_name', 'id']"
                            display="chip"
                            placeholder="Selecciona un empleado"
                            class="w-full"
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

           
        </div>
    </AppLayout>
</template>
