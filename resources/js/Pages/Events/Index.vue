<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToastService } from "@/Stores/toastService";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import { onMounted, ref } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { router } from '@inertiajs/vue3';
import { useAuthz } from "@/composables/useAuthz";
const { can } = useAuthz();

const props = defineProps({
    branchOffices: Array,
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
    Titulo : true,
    Fecha_Inicio: true,
    Fecha_Fin: true,
    Feriado: true,
    Cumpleaños: true,
    Todo_el_dia: true,
    Color: true,
});

//Columnas visibles
const showColumns = ref({
    acciones : true,
    Titulo : true,
    Fecha_Inicio: true,
    Fecha_Fin: true,
    Feriado: true,
    Cumpleaños: true,
    Todo_el_dia: true,
    Color: true,
})


//Diálogo de filtros adicionales
const otherFilterDialog = ref(false);

//Filtros del modal
const selectedDate = ref(false);
const selectedBranchOfficeId = ref(null);
const selectedBranchOfficeName = ref(null);
const selectedEmployees = ref(null);


//Referencia a la tabla de datos
const dt = ref(null);
const toast = useToast();

//Columnas fijas
const frozenColumns = ref({
    Titulo: true,
    Fecha_Inicio: true,
    Fecha_Fin: true,
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
    selectedBranchOfficeId.value = null;
    holiday.value=null;
    filter_data();
};

//Función para aplicar filtros adicionales
const filter_data = async () => {
    loading.value = true;
    otherFilterDialog.value = false;
    selectedBranchOfficeName.value = selectedBranchOfficeId.value?.code;
    const dateRange = selectedDate.value ?? [];
    const dateStart = formatDate(dateRange[0]);
    const dateEnd   = formatDate(dateRange[1]);
    try {
        const response = await axios.get('/assistences/events/filter-data', {
            params: {
                date_start: dateStart ?? null,
                date_end: dateEnd ?? null,
                holiday: holiday.value,
                selectedBranchOfficeId: selectedBranchOfficeId.value?.id
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
    filter_data();

    deparments.value = props.deparments;
    branch_offices.value = props.branchOffices;
    const savedBranch = JSON.parse(localStorage.getItem("selectedBranchOffice"));
    if (savedBranch) {
        const match = branch_offices.value.find(b => b.id === savedBranch.id);
        selectedBranchOfficeId.value = match ?? null;
        selectedBranchOfficeName.value = selectedBranchOfficeId.value.code;
    }
});

const deleteItem = async () => {
    loading.value = true;
    try {
        const response = await axios.delete(
            route("events.destroy", itemToDelete.value.id)
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
</script>

<template>
    <AppLayout :title="'Eventos'">
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
                <template #end>
                    
                    <Link :href="route('events.create')" v-if="(can('events.create'))">
                        <Button
                            label="Crear"
                            icon="pi pi-plus-circle"
                            severity="success"
                            class="ml-2"
                        />
                    </Link>
                </template>
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
                exportFilename="Eventos"
                :globalFilterFields="[
                    'title',
                ]"

                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} datos de eventos"
            >
                <template #header>
                    <div class="flex flex-wrap gap-2 items-end justify-between mb-6">
                        <div>
                            <h4 class="m-0">Eventos</h4>
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
                            v-if="selectedDate != null"
                            :label="formatDate(selectedDate[0])+ ' a '+ formatDate(selectedDate[1])"
                        />
                        <Chip
                            v-if="selectedBranchOfficeName"
                            :label="'Planta: ' + selectedBranchOfficeName"
                            removable
                            @remove="
                                () => {
                                    selectedBranchOfficeName = null;
                                    selectedBranchOfficeId = null;
                                    filter_data();
                                }
                            "
                        />
                        <Chip
                            v-if="holiday == true"
                            :label="'Feriado: ' + holiday == true ?  'No':'Sí'"
                            removable
                            @remove="
                                () => {
                                    holiday = null;
                                    filter_data();
                                }
                            "
                        />
                    </div>
                </template>
                <Column
                    :exportable="false"
                    columnKey="acciones"
                    :style="{
                        width: '10rem',
                        display: showColumns.acciones ? '' : 'none',
                        minWidth: '7rem',
                    }"
                    header="Acciones"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <div v-else>
                            <Button
                                icon="pi pi-pencil"
                                class="mr-2"
                                rounded
                                v-tooltip.top="'Editar'"
                                severity="warn"
                                @click="
                                    () => {
                                        router.get(
                                            `/assistences/events/${data.id}/edit`,
                                        );
                                    }
                                "
                                v-if="(can('events.edit'))"
                            />
                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                v-tooltip.top="'Eliminar'"
                                class="mr-2"
                                rounded
                                @click="
                                    () => {
                                        itemToDelete = data;
                                        deleteDialog = true;
                                    }
                                "
                                v-if="(can('events.delete'))"
                            />

                            <Button
                                icon="pi pi-eye"
                                severity="help"
                                v-tooltip.top="'Ver detalles'"
                                rounded
                                @click="() => {
                                    router.get(route('events.show', data.id));
                                }"
                                v-if="(can('events.index'))"
                            />
                        </div>
                    </template>
                </Column>
                <Column
                    field="title"
                    header="Titulo"
                    :filter="true"
                    :frozen="frozenColumns.Titulo"
                    :style="{
                        width: '10rem',
                        display: showColumns.Titulo ? '' : 'none',
                    }"
                    :exportable="exportColumns.Titulo"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.title }}</span>
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
                    field="start_date"
                    header="Fecha Incio"
                    :filter="true"
                    :frozen="frozenColumns.Fecha_Inicio"
                    :style="{
                        width: '10rem',
                        display: showColumns.Fecha_Inicio ? '' : 'none',
                    }"
                    :exportable="exportColumns.Fecha_Inicio"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.start_date }}</span>
                    </template>
                </Column>
                <Column
                    field="end_date"
                    header="Fecha Fin"
                    :filter="true"
                    :frozen="frozenColumns.Fecha_Fin"
                    :style="{
                        width: '10rem',
                        display: showColumns.Fecha_Fin ? '' : 'none',
                    }"
                    :exportable="exportColumns.Fecha_Fin"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.end_date }}</span>
                    </template>
                </Column>
                <Column
                    field="holiday"
                    header="Feriado"
                    :filter="true"
                    :frozen="frozenColumns.Feriado"
                    :style="{
                        width: '10rem',
                        display: showColumns.Feriado ? '' : 'none',
                    }"
                    :exportable="exportColumns.Feriado"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <Tag
                            v-else
                            :value="data.holiday == 1 ? 'Sí' : 'No'"
                            :severity="data.holiday == 1 ? 'success' : 'danger'"
                        />
                    </template>
                    
                </Column>
                <Column
                    field="birthday"
                    header="Cumpleaños"
                    :filter="true"
                    :frozen="frozenColumns.Cumpleaños"
                    :style="{
                        width: '10rem',
                        display: showColumns.Cumpleaños ? '' : 'none',
                    }"
                    :exportable="exportColumns.Cumpleaños"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <Tag
                            v-else
                            :value="data.birthday == 1 ? 'Sí' : 'No'"
                            :severity="data.birthday == 1 ? 'success' : 'danger'"
                        />
                    </template>
                </Column>
                <Column
                    field="all_day"
                    header="Todo el dia"
                    :filter="true"
                    :frozen="frozenColumns.Todo_el_dia"
                    :style="{
                        width: '10rem',
                        display: showColumns.Todo_el_dia ? '' : 'none',
                    }"
                    :exportable="exportColumns.Todo_el_dia"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>

                        <Tag
                            v-else
                            :value="data.all_day == 1 ? 'Sí' : 'No'"
                            :severity="data.all_day == 1 ? 'success' : 'danger'"
                        />
                    </template>

                </Column>
                <Column
                    field="color"
                    header="Color"
                    :filter="true"
                    :frozen="frozenColumns.Color"
                    :style="{
                        width: '10rem',
                        display: showColumns.Color ? '' : 'none',
                    }"
                    :exportable="exportColumns.Color"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <div v-else class="flex items-center gap-2">
                            <span
                                :style="{
                                    backgroundColor: data.color,
                                    width: '20px',
                                    height: '20px',
                                    display: 'inline-block',
                                    borderRadius: '4px',
                                    border: '1px solid #ccc'
                                }"
                            ></span>
                            <span>{{ data.color }}</span>
                        </div>
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
                    <Select v-model="selectedBranchOfficeId" display="chip"
                        :options="branch_offices" optionLabel="code" filter
                        placeholder="Selecciona una planta" class="w-full" />
                </div>

                <div class="mt-2">
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
                <div class="mt-2">
                    <label class="block mb-1 font-medium">Feriado</label>
                    <ToggleSwitch v-model="holiday">
                        <template #handle="{ checked }">
                            <i :class="['!text-xs pi', { 'pi-check': checked, 'pi-times': !checked }]" />
                        </template>
                    </ToggleSwitch>
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
