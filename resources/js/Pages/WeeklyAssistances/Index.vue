<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToastService } from "@/Stores/toastService";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import { onMounted, ref } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { router } from "@inertiajs/vue3";
import { useDownloadConfirm } from "@/composables/useDownloadConfirm";

const props = defineProps({
    data: Array,
    dates: Array,
    branch_offices: Array,
    deparments: Array,
    employees: Array,
    incidences: Array,
});


const modalDetails = ref(false);
const details = ref(null);

const modalTurns = ref(false);

//Inicializar filtros globales de tabla
const filters = ref({});

//Inicializar variable de carga para mostrar esqueleto
const loading = ref(true);

//Función para mostrar toast de éxito y error
const { showSuccess, showError, processingTurn, showValidationError } =
    useToastService();
const { openDownloadConfirm } = useDownloadConfirm();

//Función para inicializar filtros
const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        employee_id: { value: null, matchMode: FilterMatchMode.CONTAINS },
        employee_name: { value: null, matchMode: FilterMatchMode.CONTAINS },
        department_name: { value: null, matchMode: FilterMatchMode.CONTAINS },
        position_name: { value: null, matchMode: FilterMatchMode.CONTAINS },
        entry_date: { value: null, matchMode: FilterMatchMode.CONTAINS },
        monday_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
        friday_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
        wednesday_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
        tuesday_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
        saturday_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
        sunday_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
        thursday_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
        thursday_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
    };
};

initFilters();

//Columnas de exportación
const exportColumns = ref({
    Clave: true,
    Empleado: true,
    Departamento: true,
    Puesto: true,
    "Fecha Ingreso": true,
    Lunes: true,
    "Horario Lunes": true,
    Martes: true,
    "Horario Martes": true,
    Miercoles: true,
    "Horario Miercoles": true,
    Jueves: true,
    "Horario Jueves": true,
    Viernes: true,
    "Horario Viernes": true,
    Sabado: true,
    "Horario Sabado": true,
    Domingo: true,
    "Horario Domingo": true,
    "Prima Dominical": true,
    Dobles: true,
    Triples: true,
    Faltas: true,
    Planta: true,
    Semana: true,
    Año: true,
});

//Columnas visibles
const showColumns = ref({
    Clave: true,
    Empleado: true,
    Departamento: true,
    Puesto: true,
    Fecha_ingreso: true,
    Lunes: true,
    Horario_Lunes: false,
    Martes: true,
    Horario_Martes: false,
    Miercoles: true,
    Horario_Miercoles: false,
    Jueves: true,
    Horario_Jueves: false,
    Viernes: true,
    Horario_Viernes: false,
    Sabado: true,
    Horario_Sabado: false,
    Domingo: true,
    Horario_Domingo: false,
    Prima_Domical: true,
    Dobles: true,
    Triples: true,
    Faltas: true,
    Planta: false,
    Semana: true,
    Año: true,
});



//Diálogo de filtros adicionales
const otherFilterDialog = ref(false);

//Filtros del modal
const selectedWeek = ref(null);
const selectedYear = ref(null);
const yearOptions = ref([]);

//Referencia a la tabla de datos
const dt = ref(null);
const toast = useToast();

//Columnas fijas
const frozenColumns = ref({
    Clave: true,
    Empleado: true,
    Departamento: false,
    Puesto: false,
    Fecha_ingreso: false,
    Lunes: false,
    Horario_Lunes: false,
    Martes: false,
    Horario_Martes: false,
    Miercoles: false,
    Horario_Miercoles: false,
    Jueves: false,
    Horario_Jueves: false,
    Viernes: false,
    Horario_Viernes: false,
    Sabado: false,
    Horario_Sabado: false,
    Domingo: false,
    Horario_Domingo: false,
    Prima_Domical: false,
    Dobles: false,
    Triples: false,
    Faltas: false,
    Planta: false,
    Semana: true,
    Año: true,
});

//Diálogo de selección de columnas
const columnsDialog = ref(false);

//Estado de envío
const submitted = ref(false);

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

//Inicializacion de una fila para mostrar el esqueleto de inicio en lo que carga la tabla
const rows = ref([
    {
        numero_nomina: 0,
        empleado: "",
        departamento: "",
        puesto: "",
        fecha_ingreso: "",
        lunes: "",
        martes: "",
        miercoles: "",
        jueves: "",
        viernes: "",
        sabado: "",
        domingo: "",
    },
]);


const saveColumns = async () => {
    const campos = getSelectedFields();
    columnsDialog.value = false;
    const [anio, semana] = selectedWeek.value.split("-W");
    try {
        toast.add({
            severity: "info",
            summary: "Descargando",
            detail: "Espera en lo que se descarga tu archivo...",
            group: "download",
            closable: false,
            life: 0,
        });

        const response = await axios.post(
            "/assistences/weekly-assistences/downloadAll",
            {
                campos: campos,
                empleados: selected.value.map((e) => e.employee_id),
                anio: parseInt(anio),
                semana: parseInt(semana, 10),
            },
            {
                responseType: "blob",
            },
        );
        const contentDisposition = response.headers["content-disposition"];

        let fileName = "reporte";

        if (contentDisposition) {
            const match = contentDisposition.match(/filename="?([^"]+)"?/);
            if (match?.[1]) {
                fileName = match[1];
            }
        }

        const blob = new Blob([response.data], {
            type: response.data.type,
        });

        const url = window.URL.createObjectURL(blob);

        const link = document.createElement("a");
        link.href = url;
        link.setAttribute("download", fileName);
        document.body.appendChild(link);
        link.click();
        link.remove();

        window.URL.revokeObjectURL(url);
        toast.removeGroup("download");

        showSuccess();
    } catch (error) {
        toast.removeGroup("download");

        console.error(error);
    }
};

const clearFilter = () => {
    selectedEmployees.value = null;
    selectedDeparments.value = null;
    selectedIncidences.value = null;
    selected.value = [];
    filter_data();
};

//Función para aplicar filtros adicionales
const filter_data = async () => {
    loading.value = true;
    otherFilterDialog.value = false;

    let year = '';
    let week = '';

    if (selectedWeek.value) {
        [year, week] = selectedWeek.value.split('-W');
    }

    try {
        const response = await axios.get("/weekly-assistences/filter-data", {
            params: {
                year: selectedYear.value || year || '',
                week: week || '',
            }
        });

        rows.value = response.data.data;
    } catch (error) {
        console.error("Error refrescando tabla:", error);
        showError("No se pudo actualizar la tabla");
    } finally {
        loading.value = false;
    }
};

onMounted(async () => {
    rows.value = props.data;
    loadYears();
    filter_data();
});

const show_details = (data, day) => {
    const checadasDelDia = data.checadas?.[day] ?? [];
    const raw = data[`${day}_data`];
    let horarioParsed = null;

    if (raw) {
        if (typeof raw === "object") {
            horarioParsed = raw;
        } else {
            try {
                const cleaned = raw.replace(/[\t\n\r]/g, "").trim();

                horarioParsed = JSON.parse(cleaned);
            } catch (e) {
                console.error(
                    "Error parseando horario:",
                    e,
                    "\nRaw recibido:",
                    raw,
                );
                horarioParsed = null;
            }
        }
    }
    details.value = {
        employee_id: data.employee_id,
        employee_name: data.employee_name,
        department_name: data.department_name,
        dia: day,
        horario: horarioParsed,
        incidencia: data[`${day}_incidence`] ?? "Sin incidencia",
        descripcion_incidencia: data[`${day}_description`] ?? "Sin descripción",
        color: data[`${day}_color`],
        week_number: data.week_number,
        week_year: data.week_year,
        checadas: checadasDelDia,
    };

    modalDetails.value = true;
}


const parseJSON = (val) => {
    try {
        return typeof val === "string" ? JSON.parse(val) : val;
    } catch (e) {
        return null;
    }
};

function loadYears(range = 5) {
    const currentYear = new Date().getFullYear();

    yearOptions.value = [];

    for (let i = currentYear - range; i <= currentYear + range; i++) {
        yearOptions.value.push({
            label: String(i),
            value: String(i)
        });
    }

    selectedYear.value = String(currentYear);
}
</script>

<template>
    <AppLayout :title="'Asistencia Semanal'">
        <Toast group="download" position="top-right" />
        <Toast group="processing" />
        
        <div class="card">
            <DataTable
                ref="dt"
                :value="rows"
                dataKey="employee_id"
                :paginator="true"
                :rows="50"
                scrollable
                scrollHeight="600px"
                tableStyle="min-width: 110rem"
                v-model:filters="filters"
                filterDisplay="menu"
                exportFilename="Asistencia Semanal"
                :globalFilterFields="[
                    'employee_id',
                    'employee_name',
                    'department_name',
                    'position_name',
                    'entry_date',
                    'monday_code',
                    'tuesday_code',
                    'wednesday_code',
                    'thursday_code',
                    'friday_code',
                    'saturday_code',
                    'sunday_code',
                ]"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} datos de asistencia"
            >
                <template #header>
                    <div
                        class="flex flex-wrap gap-2 items-end justify-between mb-6"
                    >
                        <div>
                            <h4 class="m-0">Asistencia Semanal</h4>
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
                            v-if="selectedWeek"
                            :label="'Semana: ' + (selectedWeek ? selectedWeek.split('-W')[1] : '')"
                            removable
                            @remove="
                                () => {
                                    selectedWeek = null;
                                    filter_data();
                                }
                            "
                        />

                        <Chip
                            v-if="selectedYear"
                            :label="'Año: ' + selectedYear"
                            removable
                            @remove="
                                () => {
                                    selectedYear = null;
                                    filter_data();
                                }
                            "
                        />
                    </div>
                </template>
                <Column
                    field="employee_id"
                    header="Clave"
                    sortable
                    :filter="true"
                    :frozen="frozenColumns.Clave"
                    :style="{
                        width: '1rem',
                        display: showColumns.Clave ? '' : 'none',
                    }"
                    :exportable="exportColumns.Clave"
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
                    field="employee_name"
                    header="Empeado"
                    sortable
                    :filter="true"
                    :frozen="frozenColumns.Empleado"
                    :style="{
                        width: '20rem',
                        display: showColumns.Empleado ? '' : 'none',
                    }"
                    :exportable="exportColumns.Empleado"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.employee_name }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Nombre"
                        />
                    </template>
                </Column>
                <Column
                    field="planta"
                    header="Planta"
                    sortable
                    :filter="true"
                    :frozen="frozenColumns.Planta"
                    :style="{
                        width: '20rem',
                        display: showColumns.Planta ? '' : 'none',
                    }"
                    :exportable="exportColumns.Planta"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.planta }}</span>
                    </template>
                </Column>
                <Column
                    field="department_name"
                    header="Departamento"
                    :frozen="frozenColumns.Departamento"
                    sortable
                    :style="{
                        width: '20rem',
                        display: showColumns.Departamento ? '' : 'none',
                    }"
                    :exportable="exportColumns.Departamento"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.department_name }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Departamento"
                        />
                    </template>
                </Column>
                <Column
                    field="position_name"
                    header="Puesto"
                    sortable
                    :frozen="frozenColumns.Puesto"
                    :style="{
                        width: '20rem',
                        display: showColumns.Puesto ? '' : 'none',
                    }"
                    :exportable="exportColumns.Puesto"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.position_name }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Puesto"
                        /> </template
                ></Column>
                <Column
                    field="week_number"
                    header="Semana"
                    sortable
                    :frozen="frozenColumns.Semana"
                    :style="{
                        width: '20rem',
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
                    sortable
                    :frozen="frozenColumns.Año"
                    :style="{
                        width: '20rem',
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
                    field="entry_date"
                    header="Fecha Ingreso"
                    sortable
                    :frozen="frozenColumns.Fecha_ingreso"
                    :style="{
                        width: '20rem',
                        display: showColumns.Fecha_ingreso ? '' : 'none',
                    }"
                    :exportable="exportColumns.Fecha_ingreso"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.entry_date }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar"
                        /> </template
                ></Column>
                <Column
                    field="monday_code"
                    :header="'Lunes'"
                    sortable
                    :frozen="frozenColumns.Lunes"
                    :style="{
                        width: '20rem',
                        display: showColumns.Lunes ? '' : 'none',
                    }"
                    :exportable="exportColumns.Lunes"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <Badge
                            :value="data.monday_code"
                            size="xlarge"
                            severity="success"
                            v-tooltip.top="data.monday_incidence"
                            v-else
                            :style="{
                                backgroundColor: data.monday_color,
                                color: '#fff',
                                cursor: 'pointer',
                            }"
                            v-if="data.monday_code"
                            @click="show_details(data, 'monday', )"
                        >
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
                    field="monday_data"
                    header="Horario Lunes"
                    sortable
                    :frozen="frozenColumns.Horario_Lunes"
                    :style="{
                        width: '10rem',
                        display: showColumns.Horario_Lunes ? '' : 'none',
                    }"
                    :exportable="exportColumns.Horario_Lunes"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading" width="100%" height="4rem" />
                        <div
                            v-else
                            class="p-2 rounded-md border border-gray-200 text-xs text-gray-700 leading-tight"
                        >
                            <template v-if="parseJSON(data?.monday_data)">
                                <div>
                                    <b>Turno:</b>
                                    {{ parseJSON(data?.monday_data)?.Turno }}
                                </div>
                                <div>
                                    <b>Horario:</b>
                                    {{ parseJSON(data?.monday_data)?.Horario }}
                                </div>
                                <div>
                                    <b>Entrada:</b>
                                    {{ parseJSON(data?.monday_data)?.Entrada }}
                                </div>
                                <div>
                                    <b>Salida:</b>
                                    {{ parseJSON(data?.monday_data)?.Salida }}
                                </div>
                                <div>
                                    <b>Dobles:</b>
                                    {{
                                        parseJSON(data?.monday_data)?.[
                                            "Horas dobles"
                                        ]
                                    }}
                                </div>
                                <div>
                                    <b>Triples:</b>
                                    {{
                                        parseJSON(data?.monday_data)?.[
                                            "Horas triples"
                                        ]
                                    }}
                                </div>
                                <div
                                    v-if="
                                        parseJSON(data?.monday_data)?.Checadas
                                            ?.length
                                    "
                                >
                                    <b>Checadas:</b>
                                    <span>
                                        {{
                                            parseJSON(data?.monday_data)
                                                .Checadas.map(
                                                    (c) => c?.access_time,
                                                )
                                                .join(", ")
                                        }}
                                    </span>
                                </div>
                                <div
                                    v-else
                                    class="text-gray-400 italic text-center py-2"
                                >
                                    Sin checadas
                                </div>
                            </template>
                            <template v-else>
                                <div
                                    class="text-center text-gray-400 italic py-2"
                                >
                                    Sin datos
                                </div>
                            </template>
                        </div>
                    </template>

                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar"
                            class="p-inputtext-sm w-full"
                        />
                    </template>
                </Column>
                <Column
                    field="tuesday_code"
                    :header="'Martes'"
                    sortable
                    :frozen="frozenColumns.Martes"
                    :style="{
                        width: '20rem',
                        display: showColumns.Martes ? '' : 'none',
                    }"
                    :exportable="exportColumns.Martes"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <Badge
                            :value="data.tuesday_code"
                            size="xlarge"
                            severity="success"
                            v-tooltip.top="data.tuesday_incidence"
                            v-else
                            :style="{
                                backgroundColor: data.tuesday_color,
                                color: '#fff',
                                cursor: 'pointer',
                            }"
                            v-if="data.tuesday_code"
                            @click="show_details(data, 'tuesday')"
                        >
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
                    field="tuesday_data"
                    header="Horario Martes"
                    sortable
                    :frozen="frozenColumns.Horario_Martes"
                    :style="{
                        width: '10rem',
                        display: showColumns.Horario_Martes ? '' : 'none',
                    }"
                    :exportable="exportColumns.Horario_Martes"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading" width="100%" height="4rem" />
                        <div
                            v-else
                            class="p-2 rounded-md border border-gray-200 text-xs text-gray-700 leading-tight"
                        >
                            <template v-if="parseJSON(data?.tuesday_data)">
                                <div>
                                    <b>Turno:</b>
                                    {{ parseJSON(data?.tuesday_data)?.Turno }}
                                </div>
                                <div>
                                    <b>Horario:</b>
                                    {{ parseJSON(data?.tuesday_data)?.Horario }}
                                </div>
                                <div>
                                    <b>Entrada:</b>
                                    {{ parseJSON(data?.tuesday_data)?.Entrada }}
                                </div>
                                <div>
                                    <b>Salida:</b>
                                    {{ parseJSON(data?.tuesday_data)?.Salida }}
                                </div>
                                <div>
                                    <b>Dobles:</b>
                                    {{
                                        parseJSON(data?.tuesday_data)?.[
                                            "Horas dobles"
                                        ]
                                    }}
                                </div>
                                <div>
                                    <b>Triples:</b>
                                    {{
                                        parseJSON(data?.tuesday_data)?.[
                                            "Horas triples"
                                        ]
                                    }}
                                </div>
                                <div
                                    v-if="
                                        parseJSON(data?.tuesday_data)?.Checadas
                                            ?.length
                                    "
                                >
                                    <b>Checadas:</b>
                                    <span>
                                        {{
                                            parseJSON(data?.tuesday_data)
                                                ?.Checadas.map(
                                                    (c) => c.access_time,
                                                )
                                                .join(", ")
                                        }}
                                    </span>
                                </div>
                                <div
                                    v-else
                                    class="text-gray-400 italic text-center py-2"
                                >
                                    Sin checadas
                                </div>
                            </template>
                            <template v-else>
                                <div
                                    class="text-center text-gray-400 italic py-2"
                                >
                                    Sin datos
                                </div>
                            </template>
                        </div>
                    </template>

                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar"
                            class="p-inputtext-sm w-full"
                        />
                    </template>
                </Column>
                <Column
                    field="wednesday_code"
                    :header="'Miercoles'"
                    sortable
                    :frozen="frozenColumns.Miercoles"
                    :style="{
                        width: '20rem',
                        display: showColumns.Miercoles ? '' : 'none',
                    }"
                    :exportable="exportColumns.Miercoles"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <Badge
                            :value="data.wednesday_code"
                            size="xlarge"
                            severity="success"
                            v-tooltip.top="data.wednesday_incidence"
                            v-else
                            :style="{
                                backgroundColor: data.wednesday_color,
                                color: '#fff',
                                cursor: 'pointer',
                            }"
                            v-if="data.wednesday_code"
                            @click="show_details(data, 'wednesday')"
                        >
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
                    field="wednesday_data"
                    header="Horario Miercoles"
                    sortable
                    :frozen="frozenColumns.Horario_Miercoles"
                    :style="{
                        width: '10rem',
                        display: showColumns.Horario_Miercoles ? '' : 'none',
                    }"
                    :exportable="exportColumns.Horario_Miercoles"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading" width="100%" height="4rem" />
                        <div
                            v-else
                            class="p-2 rounded-md border border-gray-200 text-xs text-gray-700 leading-tight"
                        >
                            <template v-if="parseJSON(data?.wednesday_data)">
                                <div>
                                    <b>Turno:</b>
                                    {{ parseJSON(data?.wednesday_data)?.Turno }}
                                </div>
                                <div>
                                    <b>Horario:</b>
                                    {{
                                        parseJSON(data?.wednesday_data)?.Horario
                                    }}
                                </div>
                                <div>
                                    <b>Entrada:</b>
                                    {{
                                        parseJSON(data?.wednesday_data)?.Entrada
                                    }}
                                </div>
                                <div>
                                    <b>Salida:</b>
                                    {{
                                        parseJSON(data?.wednesday_data)?.Salida
                                    }}
                                </div>
                                <div>
                                    <b>Dobles:</b>
                                    {{
                                        parseJSON(data?.wednesday_data)?.[
                                            "Horas dobles"
                                        ]
                                    }}
                                </div>
                                <div>
                                    <b>Triples:</b>
                                    {{
                                        parseJSON(data?.wednesday_data)?.[
                                            "Horas triples"
                                        ]
                                    }}
                                </div>
                                <div
                                    v-if="
                                        parseJSON(data?.wednesday_data)
                                            ?.Checadas?.length
                                    "
                                >
                                    <b>Checadas:</b>
                                    <span>
                                        {{
                                            parseJSON(data?.wednesday_data)
                                                .Checadas.map(
                                                    (c) => c?.access_time,
                                                )
                                                .join(", ")
                                        }}
                                    </span>
                                </div>
                                <div
                                    v-else
                                    class="text-gray-400 italic text-center py-2"
                                >
                                    Sin checadas
                                </div>
                            </template>
                            <template v-else>
                                <div
                                    class="text-center text-gray-400 italic py-2"
                                >
                                    Sin datos
                                </div>
                            </template>
                        </div>
                    </template>

                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar"
                            class="p-inputtext-sm w-full"
                        />
                    </template>
                </Column>
                <Column
                    field="thursday_code"
                    :header="'Jueves'"
                    sortable
                    :frozen="frozenColumns.Jueves"
                    :style="{
                        width: '20rem',
                        display: showColumns.Jueves ? '' : 'none',
                    }"
                    :exportable="exportColumns.Jueves"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <Badge
                            :value="data.thursday_code"
                            size="xlarge"
                            severity="success"
                            v-tooltip.top="data.thursday_incidence"
                            v-else
                            :style="{
                                backgroundColor: data.thursday_color,
                                color: '#fff',
                                cursor: 'pointer',
                            }"
                            v-if="data.thursday_code"
                            @click="show_details(data, 'thursday')"
                        >
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
                    field="thursday_data"
                    header="Horario Jueves"
                    sortable
                    :frozen="frozenColumns.Horario_Jueves"
                    :style="{
                        width: '10rem',
                        display: showColumns.Horario_Jueves ? '' : 'none',
                    }"
                    :exportable="exportColumns.Horario_Jueves"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading" width="100%" height="4rem" />
                        <div
                            v-else
                            class="p-2 rounded-md border border-gray-200 text-xs text-gray-700 leading-tight"
                        >
                            <template v-if="parseJSON(data?.thursday_data)">
                                <div>
                                    <b>Turno:</b>
                                    {{ parseJSON(data?.thursday_data)?.Turno }}
                                </div>
                                <div>
                                    <b>Horario:</b>
                                    {{
                                        parseJSON(data?.thursday_data)?.Horario
                                    }}
                                </div>
                                <div>
                                    <b>Entrada:</b>
                                    {{
                                        parseJSON(data?.thursday_data)?.Entrada
                                    }}
                                </div>
                                <div>
                                    <b>Salida:</b>
                                    {{ parseJSON(data?.thursday_data)?.Salida }}
                                </div>
                                <div>
                                    <b>Dobles:</b>
                                    {{
                                        parseJSON(data?.thursday_data)?.[
                                            "Horas dobles"
                                        ]
                                    }}
                                </div>
                                <div>
                                    <b>Triples:</b>
                                    {{
                                        parseJSON(data?.thursday_data)?.[
                                            "Horas triples"
                                        ]
                                    }}
                                </div>
                                <div
                                    v-if="
                                        parseJSON(data?.thursday_data)?.Checadas
                                            ?.length
                                    "
                                >
                                    <b>Checadas:</b>
                                    <span>
                                        {{
                                            parseJSON(data?.thursday_data)
                                                .Checadas.map(
                                                    (c) => c?.access_time,
                                                )
                                                .join(", ")
                                        }}
                                    </span>
                                </div>
                                <div
                                    v-else
                                    class="text-gray-400 italic text-center py-2"
                                >
                                    Sin checadas
                                </div>
                            </template>
                            <template v-else>
                                <div
                                    class="text-center text-gray-400 italic py-2"
                                >
                                    Sin datos
                                </div>
                            </template>
                        </div>
                    </template>

                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar"
                            class="p-inputtext-sm w-full"
                        />
                    </template>
                </Column>
                <Column
                    field="friday_code"
                    :header="'Viernes'"
                    sortable
                    :frozen="frozenColumns.Viernes"
                    :style="{
                        width: '20rem',
                        display: showColumns.Viernes ? '' : 'none',
                    }"
                    :exportable="exportColumns.Viernes"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <Badge
                            :value="data.friday_code"
                            size="xlarge"
                            severity="success"
                            v-tooltip.top="data.friday_incidence"
                            v-else
                            :style="{
                                backgroundColor: data.friday_color,
                                color: '#fff',
                                cursor: 'pointer',
                            }"
                            v-if="data.friday_code"
                            @click="show_details(data, 'friday')"
                        >
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
                    field="friday_data"
                    header="Horario Viernes"
                    sortable
                    :frozen="frozenColumns.Horario_Viernes"
                    :style="{
                        width: '10rem',
                        display: showColumns.Horario_Viernes ? '' : 'none',
                    }"
                    :exportable="exportColumns.Horario_Viernes"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading" width="100%" height="4rem" />
                        <div
                            v-else
                            class="p-2 rounded-md border border-gray-200 text-xs text-gray-700 leading-tight"
                        >
                            <template v-if="parseJSON(data?.friday_data)">
                                <div>
                                    <b>Turno:</b>
                                    {{ parseJSON(data?.friday_data)?.Turno }}
                                </div>
                                <div>
                                    <b>Horario:</b>
                                    {{ parseJSON(data?.friday_data)?.Horario }}
                                </div>
                                <div>
                                    <b>Entrada:</b>
                                    {{ parseJSON(data?.friday_data)?.Entrada }}
                                </div>
                                <div>
                                    <b>Salida:</b>
                                    {{ parseJSON(data?.friday_data)?.Salida }}
                                </div>
                                <div>
                                    <b>Dobles:</b>
                                    {{
                                        parseJSON(data?.friday_data)?.[
                                            "Horas dobles"
                                        ]
                                    }}
                                </div>
                                <div>
                                    <b>Triples:</b>
                                    {{
                                        parseJSON(data?.thursday_data)?.[
                                            "Horas triples"
                                        ]
                                    }}
                                </div>
                                <div
                                    v-if="
                                        parseJSON(data?.friday_data)?.Checadas
                                            ?.length
                                    "
                                >
                                    <b>Checadas:</b>
                                    <span>
                                        {{
                                            parseJSON(data?.friday_data)
                                                .Checadas.map(
                                                    (c) => c?.access_time,
                                                )
                                                .join(", ")
                                        }}
                                    </span>
                                </div>
                                <div
                                    v-else
                                    class="text-gray-400 italic text-center py-2"
                                >
                                    Sin checadas
                                </div>
                            </template>
                            <template v-else>
                                <div
                                    class="text-center text-gray-400 italic py-2"
                                >
                                    Sin datos
                                </div>
                            </template>
                        </div>
                    </template>

                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar"
                            class="p-inputtext-sm w-full"
                        />
                    </template>
                </Column>
                <Column
                    field="saturday_code"
                    :header="'Sabado'"
                    sortable
                    :frozen="frozenColumns.Sabado"
                    :style="{
                        width: '20rem',
                        display: showColumns.Sabado ? '' : 'none',
                    }"
                    :exportable="exportColumns.Sabado"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <Badge
                            :value="data.saturday_code"
                            size="xlarge"
                            severity="success"
                            v-tooltip.top="data.saturday_incidence"
                            v-else
                            :style="{
                                backgroundColor: data.saturday_color,
                                color: '#fff',
                                cursor: 'pointer',
                            }"
                            v-if="data.saturday_code"
                            @click="show_details(data, 'saturday')"
                        >
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
                    field="saturday_data"
                    header="Horario Sabado"
                    sortable
                    :frozen="frozenColumns.Horario_Sabado"
                    :style="{
                        width: '10rem',
                        display: showColumns.Horario_Sabado ? '' : 'none',
                    }"
                    :exportable="exportColumns['Horario Sabado']"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading" width="100%" height="4rem" />
                        <div
                            v-else
                            class="p-2 rounded-md border border-gray-200 text-xs text-gray-700 leading-tight"
                        >
                            <template v-if="parseJSON(data?.saturday_data)">
                                <div>
                                    <b>Turno:</b>
                                    {{ parseJSON(data?.saturday_data)?.Turno }}
                                </div>
                                <div>
                                    <b>Horario:</b>
                                    {{
                                        parseJSON(data?.saturday_data)?.Horario
                                    }}
                                </div>
                                <div>
                                    <b>Entrada:</b>
                                    {{
                                        parseJSON(data?.saturday_data)?.Entrada
                                    }}
                                </div>
                                <div>
                                    <b>Salida:</b>
                                    {{ parseJSON(data?.saturday_data)?.Salida }}
                                </div>
                                <div>
                                    <b>Dobles:</b>
                                    {{
                                        parseJSON(data?.saturday_data)?.[
                                            "Horas dobles"
                                        ]
                                    }}
                                </div>
                                <div>
                                    <b>Triples:</b>
                                    {{
                                        parseJSON(data?.saturday_data)?.[
                                            "Horas triples"
                                        ]
                                    }}
                                </div>
                                <div
                                    v-if="
                                        parseJSON(data?.saturday_data)?.Checadas
                                            ?.length
                                    "
                                >
                                    <b>Checadas:</b>
                                    <span>
                                        {{
                                            parseJSON(data?.saturday_data)
                                                .Checadas.map(
                                                    (c) => c?.access_time,
                                                )
                                                .join(", ")
                                        }}
                                    </span>
                                </div>
                                <div
                                    v-else
                                    class="text-gray-400 italic text-center py-2"
                                >
                                    Sin checadas
                                </div>
                            </template>
                            <template v-else>
                                <div
                                    class="text-center text-gray-400 italic py-2"
                                >
                                    Sin datos
                                </div>
                            </template>
                        </div>
                    </template>

                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar"
                            class="p-inputtext-sm w-full"
                        />
                    </template>
                </Column>
                <Column
                    field="sunday_code"
                    :header="'Domingo'"
                    sortable
                    :frozen="frozenColumns.Domingo"
                    :style="{
                        width: '20rem',
                        display: showColumns.Domingo ? '' : 'none',
                    }"
                    :exportable="exportColumns.Domingo"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <Badge
                            :value="data.sunday_code"
                            size="xlarge"
                            severity="success"
                            v-tooltip.top="data.sunday_incidence"
                            v-else
                            :style="{
                                backgroundColor: data.sunday_color,
                                color: '#fff',
                                cursor: 'pointer',
                            }"
                            v-if="data.sunday_code"
                            @click="show_details(data, 'sunday')"
                        >
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
                    field="sunday_data"
                    header="Horario Domingo"
                    sortable
                    :frozen="frozenColumns.Horario_Domingo"
                    :style="{
                        width: '10rem',
                        display: showColumns.Horario_Domingo ? '' : 'none',
                    }"
                    :exportable="exportColumns.Horario_Domingo"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading" width="100%" height="4rem" />
                        <div
                            v-else
                            class="p-2 rounded-md border border-gray-200 text-xs text-gray-700 leading-tight"
                        >
                            <template v-if="parseJSON(data?.sunday_data)">
                                <div>
                                    <b>Turno:</b>
                                    {{ parseJSON(data?.sunday_data)?.Turno }}
                                </div>
                                <div>
                                    <b>Horario:</b>
                                    {{ parseJSON(data?.sunday_data)?.Horario }}
                                </div>
                                <div>
                                    <b>Entrada:</b>
                                    {{ parseJSON(data?.sunday_data)?.Entrada }}
                                </div>
                                <div>
                                    <b>Salida:</b>
                                    {{ parseJSON(data?.saturday_data)?.Salida }}
                                </div>
                                <div>
                                    <b>Dobles:</b>
                                    {{
                                        parseJSON(data?.saturday_data)?.[
                                            "Horas dobles"
                                        ]
                                    }}
                                </div>
                                <div>
                                    <b>Triples:</b>
                                    {{
                                        parseJSON(data?.saturday_data)?.[
                                            "Horas triples"
                                        ]
                                    }}
                                </div>
                                <div
                                    v-if="
                                        parseJSON(data?.saturday_data)?.Checadas
                                            ?.length
                                    "
                                >
                                    <b>Checadas:</b>
                                    <span>
                                        {{
                                            parseJSON(data?.saturday_data)
                                                .Checadas.map(
                                                    (c) => c?.access_time,
                                                )
                                                .join(", ")
                                        }}
                                    </span>
                                </div>
                                <div
                                    v-else
                                    class="text-gray-400 italic text-center py-2"
                                >
                                    Sin checadas
                                </div>
                            </template>
                            <template v-else>
                                <div
                                    class="text-center text-gray-400 italic py-2"
                                >
                                    Sin datos
                                </div>
                            </template>
                        </div>
                    </template>

                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar"
                            class="p-inputtext-sm w-full"
                        />
                    </template>
                </Column>
                <Column
                    field="total_horas_dobles"
                    header="Horas Dobles"
                    :filter="true"
                    :frozen="frozenColumns.Dobles"
                    :style="{
                        width: '1rem',
                        display: showColumns.Dobles ? '' : 'none',
                    }"
                    :exportable="exportColumns.Dobles"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.total_horas_dobles }}</span>
                    </template>
                </Column>
                <Column
                    field="total_horas_triples"
                    header="Horas Triples"
                    :filter="true"
                    :frozen="frozenColumns.Triples"
                    :style="{
                        width: '1rem',
                        display: showColumns.Triples ? '' : 'none',
                    }"
                    :exportable="exportColumns.Triples"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.total_horas_triples }}</span>
                    </template>
                </Column>
                <Column
                    field="faltas"
                    header="Faltas"
                    sortable
                    :frozen="frozenColumns.Faltas"
                    :style="{
                        width: '20rem',
                        display: showColumns.Faltas ? '' : 'none',
                    }"
                    :exportable="exportColumns.Faltas"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.faltas }}</span>
                    </template>

                    ></Column
                >
                <Column
                    field="prima_dominical"
                    header="Prima Dominical"
                    sortable
                    :frozen="frozenColumns.Prima_Domical"
                    :style="{
                        width: '20rem',
                        display: showColumns.Prima_Domical ? '' : 'none',
                    }"
                    :exportable="exportColumns.Prima_Domical"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.sunday_premium }}</span>
                    </template>

                    ></Column
                >
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
                    <label class="font-semibold text-sm text-gray-600 dark:text-gray-300">
                        Año
                    </label>
                    <Select
                        v-model="selectedYear"
                        :options="yearOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Selecciona un año"
                        class="w-full"
                    />
                </div>

                <div>
                    <label
                        class="font-semibold text-sm text-gray-600 dark:text-gray-300"
                    >
                        Semana
                    </label>
                    <InputText
                        type="week"
                        v-model="selectedWeek"
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
                        :loading="submitted"
                    />
                </template>
            </Dialog>

            <Dialog
                v-model:visible="modalDetails"
                modal
                header="Detalle de asistencia"
                :style="{ width: '70rem', maxWidth: '95vw' }"
                dismissableMask
                class="p-dialog-custom"
            >
                <template v-if="details">
                    <div class="grid grid-cols-2 gap-4 text-gray-700">
                        <div class="flex flex-col gap-4">
                            <div class="rounded-xl shadow-sm border p-4">
                                <div
                                    class="flex flex-col items-center text-center gap-3"
                                >
                                    <img
                                        :src="`https://nominas.grupo-ortiz.site/Librerias/img/Fotos/${details?.employee_id}.jpg`"
                                        alt="Foto"
                                        class="w-32 h-32 rounded-full object-cover border-4 border-white shadow-xl"
                                    />

                                    <div>
                                        <h3
                                            class="text-lg font-bold text-gray-900 uppercase tracking-wide"
                                        >
                                            ({{ details?.employee_id }})
                                            {{ details?.employee_name }}
                                        </h3>
                                        <p class="text-sm text-gray-500">
                                            {{ details?.department_name }}
                                        </p>
                                    </div>
                                </div>

                                <div
                                    class="grid grid-cols-3 text-sm text-gray-600 mt-3"
                                >
                                    <div>
                                        <p
                                            class="text-xs font-semibold text-gray-400"
                                        >
                                            Fecha
                                        </p>
                                        <p>
                                            {{
                                                details?.horario?.Checadas?.[0]?.access_date ?? "SIN REGISTRO"
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p
                                            class="text-xs font-semibold text-gray-400"
                                        >
                                            Semana
                                        </p>
                                        <p>
                                            {{
                                                details?.week_number ??
                                                "SIN REGISTRO"
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p
                                            class="text-xs font-semibold text-gray-400"
                                        >
                                            Año
                                        </p>
                                        <p>
                                            {{
                                                details?.week_year ??
                                                "SIN REGISTRO"
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-xl shadow-sm border p-4">
                                <div class="grid grid-cols-2 text-sm">
                                    <div>
                                        <p
                                            class="font-semibold text-gray-500 mb-1"
                                        >
                                            Turno
                                        </p>
                                        <p class="text-gray-800">
                                            {{
                                                details?.horario?.Turno || "N/A"
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p
                                            class="font-semibold text-gray-500 mb-1"
                                        >
                                            Horario
                                        </p>
                                        <p class="text-gray-800">
                                            {{
                                                details?.horario?.Horario ||
                                                "N/A"
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p
                                            class="font-semibold text-gray-500 mb-1"
                                        >
                                            Entrada
                                        </p>
                                        <p class="text-green-500 font-semibold">
                                            {{
                                                details?.horario?.Entrada ||
                                                "N/A"
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p
                                            class="font-semibold text-gray-500 mb-1"
                                        >
                                            Salida
                                        </p>
                                        <p class="text-red-500 font-semibold">
                                            {{
                                                details.horario?.Salida || "N/A"
                                            }}
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <Badge
                                        value="Turno diurno"
                                        severity="secondary"
                                        style="
                                            background: #ccc;
                                            color: #333;
                                            font-size: 0.7rem;
                                        "
                                    />
                                </div>
                            </div>
                            <div class="rounded-xl shadow-sm border p-4">
                                <div
                                    class="flex items-center justify-between mb-2"
                                >
                                    <h4
                                        class="font-semibold text-gray-700 text-sm"
                                    >
                                        Incidencia
                                    </h4>
                                    <Badge
                                        :value="details?.incidencia"
                                        :style="{
                                            backgroundColor: details?.color,
                                            color: '#fff',
                                        }"
                                    />
                                </div>
                                <p class="text-sm text-gray-600 leading-snug">
                                    {{ details?.descripcion_incidencia }}
                                </p>
                            </div>
                        </div>
                        <div class="rounded-xl shadow-sm border p-4">
                            <div
                                class="rounded-t-lg px-4 py-2 border-b text-gray-600 font-semibold"
                            >
                                Historial de checadas
                            </div>

                            <div class="p-4 rounded-lg shadow-sm">
                                <div class="space-y-3">
                                    <div v-for="(item, index) in details?.horario?.Checadas || []" :key="index" class="flex items-start gap-3 relative">
                                        <div
                                            class="absolute left-4 top-6 bottom-0 w-px"
                                            v-if="
                                                index <
                                                details.horario.length - 1
                                            "
                                        ></div>
                                        <div
                                            class="w-8 h-8 flex items-center justify-center rounded-full border-purple-200 z-10"
                                        >
                                            <i
                                                class="pi pi-clock text-purple-500 text-xs"
                                            ></i>
                                        </div>
                                        <div
                                            class="flex-1 rounded-md p-3 border border-gray-200"
                                        >
                                            <div
                                                class="flex justify-between items-center mb-1"
                                            >
                                                <span
                                                    class="text-xs text-gray-400"
                                                >
                                                    Checada {{ index + 1 }}
                                                </span>
                                            </div>

                                            <div
                                                class="flex gap-4 text-sm text-gray-600"
                                            >
                                                <div>
                                                    📅
                                                    <b>{{
                                                        item?.access_date
                                                    }}</b>
                                                </div>
                                                <div>
                                                    ⏰
                                                    <b>{{
                                                        item?.access_time
                                                    }}</b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <template #footer>
                    <Button
                        label="Cerrar"
                        icon="pi pi-times"
                        class="p-button-sm p-button-secondary"
                        @click="modalDetails = false"
                    />
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>
