<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToastService } from "@/Stores/toastService";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import { onMounted, ref, computed, reactive, nextTick } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { useNotifications } from "@/composables/useNotifications";
import { useAuthz } from "@/composables/useAuthz";
const { can } = useAuthz();

/* =====================
PROPS
===================== */
const props = defineProps({
    data: Array,
    branchOffices: Array
});

/* =====================
ESTADO GENERAL
===================== */
const filters = ref({});
const selectedWeek = ref(null);
const selectedBranchOffice = ref([]);
const estatus = ref(null);

const rows = ref([]);
const loading = ref(true);

//Referencias a los popovers
const opMostrarColumnas = ref();
const opFijarColumnas = ref();
const selectedAnio = ref(null);
const currentYear = new Date().getFullYear();


const anios = Array.from(
    { length: currentYear - 2020 + 1 },
    (_, i) => {
        const year = currentYear - i;
        return {
            label: year.toString(),
            value: year
        };
    }
);

/* =====================
DATATABLE
===================== */
const toast = useToast();
const selected = ref([]);

/* =====================
FILTROS DE TABLA
===================== */

const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        clave_empleado: { value: null, matchMode: FilterMatchMode.CONTAINS },
        nombre_completo: { value: null, matchMode: FilterMatchMode.CONTAINS },
        planta: { value: null, matchMode: FilterMatchMode.CONTAINS },
        fecha_ingreso: { value: null, matchMode: FilterMatchMode.CONTAINS },
        fecha_termino: { value: null, matchMode: FilterMatchMode.CONTAINS },
        motivo_baja: { value: null, matchMode: FilterMatchMode.CONTAINS },
    };
};

initFilters();

const hasActiveFilters = computed(() => {
    return Array.isArray(activeFilters.branchOffice) &&
        activeFilters.branchOffice.length > 0;
});

/* =====================
FILTROS UI
===================== */
// const rows = ref([]);
const filteredBranchOffices = ref([]);

/* =====================
FILTROS ACTIVOS (CHIPS)
===================== */
const activeFilters = reactive({
    branchOffice: [],
    week: null,
    estatus: null
});

/* =====================
REMOVE FILTER
===================== */
const removeFilter = async (type) => {
    if (type === 'branchOffice') {
        selectedBranchOffice.value = [];
        activeFilters.branchOffice = [];
        localStorage.removeItem("selectedBranchOffice");
    } else if (type === 'week') {
        selectedWeek.value = null;
        activeFilters.week = null;
    } else if (type === 'estatus') {
        estatus.value = null;
        activeFilters.estatus = null;
    }

    // Volvemos a cargar con lo que quede activo
    await loadSaldos(activeFilters.branchOffice, activeFilters.week, activeFilters.estatus);
};


/* =====================
LOAD SALDOS
===================== */
const loadSaldos = async (branchOfficeIds = [], week = null, status = null) => {
    loading.value = true;
    console.log(week)
    try {
        const response = await axios.get('/employee/block-incidents/filter-blockIncidents', {
            params: {
                plantas: Array.isArray(branchOfficeIds)
                    ? branchOfficeIds
                    : [branchOfficeIds],
                semana: week,
                estatus: status
            }

        });

        console.log(response);

        if (response.data && response.data.data) {
            rows.value = response.data.data;
        }
    } catch (error) {
        rows.value = [];
        console.error(error);
        loading.value = false;
    } finally {
        loading.value = false;
    }
};

const branchOfficeNames = computed(() => {
    if (!Array.isArray(activeFilters.branchOffice)) return [];

    return activeFilters.branchOffice.map(id => {
        const plant = props.branchOffices.find(b => b.id === id);
        return plant?.code ?? `ID ${id}`;
    });
});

/* =========================
CREAR INCIDENCIAS BLOQUEAR
=========================== */
const createBlockIncidencesDialog = ref(false);

const createBlock = async () => {
    if (!selectedAnio.value || selectedBranchOffice.value.length === 0) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Debes seleccionar el año y al menos una planta',
            life: 3000
        });
        return;
    }

    submitted.value = true;

    try {
        const payload = {
            anio: selectedAnio.value,
            branch_office_ids: selectedBranchOffice.value
        };

        console.log(payload);

        const response = await axios.post(route('block-incidents.store'), payload);

        if (response.data.success) {
            toast.add({
                severity: 'success',
                summary: 'Éxito',
                detail: 'Registros generados correctamente para el año ' + selectedAnio.value,
                life: 3000
            });

            createBlockIncidencesDialog.value = false;

            selectedBranchOffice.value = [];
            selectedAnio.value = null;

            await loadSaldos(activeFilters.branchOffice, activeFilters.week, activeFilters.estatus);
        }
    } catch (error) {

        if (error.response && error.response.status === 422) {
            const data = error.response.data;

            const listaPlantas = data.duplicated_plants ? data.duplicated_plants.join(', ') : data.message;

            toast.add({
                severity: 'warn',
                summary: 'Atención: Plantas ya bloqueadas',
                detail: `Las plantas ${listaPlantas} ya cuentan con registros para el año seleccionado.`,
                life: 6000
            });
        } else {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Hubo un problema en el servidor', life: 3000 });
        }
    }finally {
        submitted.value = false;
    }
};

/* =========================
CAMBIAR ESTATUS
=========================== */
const approveDialog = ref(false);
const selectedRow = ref(null);

const confirmarCambioEstatus = (data) => {
    selectedRow.value = data;
    approveDialog.value = true;
};

const accionesBloqueo = async () => {
    if (!selectedRow.value) return;

    const rowData = selectedRow.value;
    const proximoEstatus = rowData.estatus === 1 ? 0 : 1;

    loading.value = true;
    try {
        const response = await axios.put(route('block-incidents.update', rowData.id), {
            estatus: proximoEstatus
        });

        if (response.data.success) {
            rowData.estatus = proximoEstatus;
            showSuccess("Estatus actualizado correctamente");
            approveDialog.value = false; // Cerrar el diálogo
        }
    } catch (error) {
        showError("Error al actualizar el registro");
    } finally {
        loading.value = false;
        selectedRow.value = null;
    }
};

const cambiarEstatus = async (rowData) => {
    const proximoEstatus = rowData.estatus === 1 ? 0 : 1;
    console.log(proximoEstatus, rowData.id);
    try {
        const response = await axios.put(route('block-incidents.update', rowData.id), {
            estatus: proximoEstatus
        });

        if (response.data.success) {
            rowData.estatus = proximoEstatus;
            showSuccess("Estatus actualizado");
        }
    } catch (error) {
        showError("Error al actualizar el registro");
    }
};

/* =====================
APLICAR FILTROS
===================== */
const otherFilterDialog = ref(false);

const applyFilters = async () => {
    const ids = selectedBranchOffice.value.map(v =>
        typeof v === 'object' ? Number(v.id) : Number(v)
    );

    activeFilters.branchOffice = ids;
    activeFilters.week = selectedWeek.value;
    activeFilters.estatus = estatus.value;

    localStorage.setItem(
        "selectedBranchOffice",
        JSON.stringify(ids)
    );

    loadSaldos(ids, activeFilters.week, activeFilters.estatus);
    otherFilterDialog.value = false;
};

const { sendNotification } = useNotifications();


/* =====================
LIMPIAR FILTROS
===================== */
const clearFilter = async () => {
    initFilters();

    selectedWeek.value = null;
    activeFilters.week = null;

    estatus.value = null;
    activeFilters.estatus = null;
    await loadSaldos(activeFilters.branchOffice, null, null);
};


//Función para mostrar toast de éxito y error
const { showSuccess, showError } = useToastService();

initFilters();

//Columnas de exportación
const exportColumns = ref({
    semana: true,
    anio: true,
    fecha_inicio: true,
    fecha_fin: true,
    planta: true,
    estatus: true,
});

//Columnas visibles
const showColumns = ref({
    acciones: true,
    semana: true,
    anio: true,
    fecha_inicio: true,
    fecha_fin: true,
    planta: true,
    estatus: true,
});

//Columnas fijas
const frozenColumns = ref({
    acciones: false,
    semana: false,
    anio: false,
    fecha_inicio: false,
    fecha_fin: false,
    planta: false,
    estatus: false,
});

//Estado de envío
const submitted = ref(false);

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

const toggleAll = () => {
    if (selectedBranchOffice.value.length === props.branchOffices.length) {
        selectedBranchOffice.value = [];
    } else {
        selectedBranchOffice.value = props.branchOffices.map(office => office.id);
    }
};

onMounted(async () => {
    filteredBranchOffices.value = props.branchOffices ?? [];

    const raw = localStorage.getItem("selectedBranchOffice");
    console.log(raw)
    let stored = [];

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
        loadSaldos(stored);
        return;
    }

    loadSaldos([]);
});

</script>

<template>
    <AppLayout :title="'Bloquear Incidencias'">
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

                <template #end>
                    <Button
                        v-if="can('block-incidents.create')"
                        label="Crear"
                        icon="pi pi-plus-circle"
                        severity="success"
                        class="ml-2"
                        @click="createBlockIncidencesDialog = true"
                    />
                </template>
            </Toolbar>
            <DataTable
                ref="dt"
                v-model:selection="selected"
                :value="rows"
                dataKey="id"
                :paginator="true"
                :rows="10"
                scrollable
                scrollHeight="400px"
                tableStyle="min-width: 110rem"
                v-model:filters="filters"
                filterDisplay="menu"
                exportFilename="prueba"
                :globalFilterFields="['id', 'rol', 'correo', 'nombre']"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} datos de prueba"
            >
                <template #header>
                    <div
                        class="flex flex-wrap gap-2 items-end justify-between mb-6"
                    >
                        <div>
                            <h4 class="m-0">Bloquear Incidencias</h4>
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
                            <!-- <Tag
                                v-if="selected.length > 0"
                                severity="info"
                                :value="'Seleccionados: ' + selected.length"
                            ></Tag> -->
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
                            v-if="branchOfficeNames.length"
                            :label="'Plantas: ' + branchOfficeNames.join(', ')"
                            removable
                            @remove="removeFilter('branchOffice')"
                        />

                        <Chip
                            v-if="activeFilters.week"
                            :label="'Semana: ' + activeFilters.week"
                            removable
                            @remove="removeFilter('week')"
                        />

                        <Chip
                            v-if="activeFilters.estatus !== null && activeFilters.estatus !== ''"
                            :label="'Estatus: ' + (activeFilters.estatus == 1 ? 'Activo' : 'Inactivo')"
                            removable
                            @remove="removeFilter('estatus')"
                        />
                    </div>
                </template>
                <Column
                    :exportable="false"
                    :style="{
                        width: '22rem',
                        display: showColumns.acciones ? '' : 'none',
                    }"
                    header="Acciones"
                    :frozen="frozenColumns.acciones"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>

                        <div v-else>
                            <Button
                                v-if="can('block-incidents.edit')"
                                :icon="data.estatus === 0 ? 'pi pi-verified' : 'pi pi-times-circle'"
                                :severity="data.estatus === 0 ? 'success' : 'danger'"
                                v-tooltip.right="data.estatus === 0 ? 'Activar' : 'Desactivar'"
                                rounded
                                @click="confirmarCambioEstatus(data)"
                            />
                        </div>
                    </template>
                </Column>
                <Column
                    field="week"
                    header="Semana"
                    :filter="true"
                    :frozen="frozenColumns.semana"
                    :style="{
                        width: '20rem',
                        display: showColumns.semana ? '' : 'none',
                    }"
                    :exportable="exportColumns.semana"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.week }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Semana"
                        />
                    </template>
                </Column>
                <Column
                    field="year"
                    header="Año"
                    :filter="true"
                    :frozen="frozenColumns.anio"
                    :style="{
                        width: '20rem',
                        display: showColumns.anio ? '' : 'none',
                    }"
                    :exportable="exportColumns.anio"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.year }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Año"
                        />
                    </template>
                </Column>
                <Column
                    field="start_week"
                    header="Fecha Inicio"
                    :frozen="frozenColumns.fecha_inicio"
                    sortable
                    :style="{
                        width: '20rem',
                        display: showColumns.fecha_inicio ? '' : 'none',
                    }"
                    :exportable="exportColumns.fecha_inicio"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.start_week }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Fecha Inicio"
                        />
                    </template>
                </Column>
                <Column
                    field="end_week"
                    header="Fecha Fin"
                    :frozen="frozenColumns.fecha_fin"
                    sortable
                    :style="{
                        width: '20rem',
                        display: showColumns.fecha_fin ? '' : 'none',
                    }"
                    :exportable="exportColumns.fecha_fin"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.end_week }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Fecha Fin"
                        />
                    </template>
                </Column>
                <Column
                    field="code"
                    header="Planta"
                    sortable
                    :frozen="frozenColumns.planta"
                    :style="{
                        width: '20rem',
                        display: showColumns.planta ? '' : 'none',
                    }"
                    :exportable="exportColumns.planta"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.code }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por planta"
                        /> </template
                ></Column>
                <Column
                    field="estatus"
                    header="Estatus"
                    sortable
                    :frozen="frozenColumns.estatus"
                    :style="{
                        width: '20rem',
                        display: showColumns.estatus ? '' : 'none',
                    }"
                    :exportable="exportColumns.telefono"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <Tag
                            v-else
                            :value="data.estatus === 1 ? 'ACTIVO' : 'DESACTIVADO'"
                            :severity="data.estatus === 1 ? 'success' : 'warning'"
                            rounded
                        />
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Telefono"
                        /> </template
                ></Column>
            </DataTable>
            <Dialog
                v-model:visible="otherFilterDialog"
                :style="{ width: '450px' }"
                header="Seleccionar filtros adicionales"
                :modal="true"
            >
                <div class="flex flex-col gap-5">

                    <!-- FILTROS ACTIVOS -->
                    <div v-show="hasActiveFilters"
                        class="p-3 rounded-lg bg-surface-100 dark:bg-surface-800">

                        <span class="text-sm font-semibold block mb-2"></span>
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
                    <FloatLabel variant="on" class="flex-1">

                        <Multiselect
                            v-model="selectedBranchOffice"
                            :options="props.branchOffices"
                            optionLabel="code"
                            optionValue="id"
                            filter
                            :filterFields="['code', 'id']"
                            placeholder="Selecciona una planta"
                            class="w-full"
                            display="chip"
                        >


                            <template #value="slotProps">
                                <span v-if="!slotProps.value || slotProps.value.length === 0">
                                    Selecciona una planta
                                </span>

                                <span v-else-if="slotProps.value.length > 5">
                                    {{ slotProps.value.length }} plantas seleccionadas
                                </span>


                            </template>

                            <template #option="{ option }">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-gray-700">({{ option.id }})</span>
                                    <span>{{ option.code }}</span>
                                </div>
                            </template>

                        </Multiselect>
                        <label>Plantas</label>
                    </FloatLabel>
                </div>
                <div class="mt-3">
                    <label class="font-semibold text-sm text-gray-600 dark:text-gray-300 block mb-2">
                        Estatus
                    </label>

                    <div class="flex items-center gap-2">
                        <Select
                            v-model="estatus"
                            :options="[
                                { label: 'Todos', value: null },
                                { label: 'Activo', value: 1 },
                                { label: 'Inactivo', value: 0 }
                            ]"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Selecciona estatus"
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
                        @click="applyFilters"
                        :loading="submitted"
                    />
                </template>
            </Dialog>
            <Dialog
                v-model:visible="createBlockIncidencesDialog"
                :style="{ width: '450px' }"
                header="Bloquear Semana"
                :modal="true"
            >
                <div class="flex justify-center mt-4">
                    <div class="flex flex-col gap-4 w-full max-w-2xl">

                        <div class="flex gap-2 w-full">
                            <FloatLabel variant="on" class="w-[70%]">
                                <Multiselect
                                    v-model="selectedBranchOffice"
                                    :options="props.branchOffices"
                                    optionLabel="code"
                                    optionValue="id"
                                    filter
                                    :filterFields="['code', 'id']"
                                    class="w-full"
                                    display="chip"
                                    placeholder="Selecciona plantas"
                                >
                                    <template #header>
                                        <div class="flex items-center p-3 gap-3 border-b border-gray-200 bg-gray-50">
                                            <Checkbox
                                                :modelValue="selectedBranchOffice.length === props.branchOffices.length"
                                                :binary="true"
                                                @change="toggleAll"
                                            />
                                            <span class="font-bold text-sm text-gray-700 cursor-pointer" @click="toggleAll">
                                                Seleccionar Todas las Plantas
                                            </span>
                                        </div>
                                    </template>

                                    <template #value="slotProps">
                                        <span v-if="!slotProps.value || slotProps.value.length === 0">
                                            Selecciona una planta
                                        </span>
                                        <span v-else-if="slotProps.value.length === props.branchOffices.length">
                                            Todas las plantas seleccionadas
                                        </span>
                                        <span v-else-if="slotProps.value.length > 5">
                                            {{ slotProps.value.length }} plantas seleccionadas
                                        </span>
                                    </template>

                                    <template #option="{ option }">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-gray-700">({{ option.id }})</span>
                                            <span>{{ option.code }}</span>
                                        </div>
                                    </template>
                                </Multiselect>
                                <label>Plantas</label>
                            </FloatLabel>

                            <FloatLabel variant="on" class="w-[30%]">
                                <Select
                                    v-model="selectedAnio"
                                    :options="anios"
                                    optionLabel="label"
                                    optionValue="value"
                                    filter
                                    filterPlaceholder="Buscar año..."
                                    class="w-full"
                                />
                                <label>Año</label>
                            </FloatLabel>
                        </div>

                    </div>
                </div>

                <template #footer>
                    <Button
                        label="Cancelar"
                        icon="pi pi-times"
                        severity="danger"
                        @click="createBlockIncidencesDialog = false"
                    />
                    <Button
                        label="Crear"
                        icon="pi pi-save"
                        severity="warn"
                        @click="createBlock"
                        :loading="submitted"
                    />
                </template>
            </Dialog>
            <Dialog
                v-model:visible="approveDialog"
                :style="{ width: '450px' }"
                header="Confirmar Acción"
                :modal="true"
            >
                <div
                    :class="[
                        'border-l-4 p-4 rounded',
                        selectedRow?.estatus === 0 ? 'bg-green-50 border-green-500' : 'bg-red-50 border-red-500'
                    ]"
                >
                    <div class="flex items-center">
                        <i :class="[
                            'pi text-2xl mr-3',
                            selectedRow?.estatus === 0 ? 'pi-check-circle text-green-600' : 'pi-exclamation-triangle text-red-600'
                        ]"></i>
                        <div>
                            <h3 :class="['font-bold', selectedRow?.estatus === 0 ? 'text-green-800' : 'text-red-800']">
                                ¿Estás seguro de {{ selectedRow?.estatus === 0 ? 'activar' : 'desactivar' }}?
                            </h3>
                            <p :class="['text-sm', selectedRow?.estatus === 0 ? 'text-green-700' : 'text-red-700']">
                                Esta acción cambiará el estado de la semana {{ selectedRow?.week }} a
                                <strong>{{ selectedRow?.estatus === 0 ? 'Activo' : 'Inactivo' }}</strong>.
                            </p>
                        </div>
                    </div>
                </div>

                <template #footer>
                    <Button label="No" icon="pi pi-times" text @click="approveDialog = false" severity="secondary" />
                    <Button
                        :label="selectedRow?.estatus === 0 ? 'Activar' : 'Desactivar'"
                        :icon="selectedRow?.estatus === 0 ? 'pi pi-check' : 'pi pi-power-off'"
                        @click="accionesBloqueo"
                        :severity="selectedRow?.estatus === 0 ? 'success' : 'danger'"
                        :loading="loading"
                    />
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>
