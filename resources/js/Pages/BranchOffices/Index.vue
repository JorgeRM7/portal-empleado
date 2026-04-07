<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToastService } from "@/Stores/toastService";
import { useToast } from "primevue/usetoast";
import { onMounted, ref, computed, watch } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { useAuthz } from "@/composables/useAuthz";
const { can } = useAuthz();

const props = defineProps({
    BranchOffices: Object,
});

const rows = computed(() => {
    const data = props.BranchOffices ?? [];

    return data.map(row => {
        row.expiracion_clave = row.expiracion_clave
            ? new Date(row.expiracion_clave)
            : null;

        return row;
    });
});

// Opciones de filtro para empresa
const empresasOptions = computed(() => {
    if (!rows.value) return [];

    return [...new Set(rows.value.map(r => r.empresa))]
        .filter(Boolean)
        .map(e => ({ label: e, value: e }));
});

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
        id: {
            operator: FilterOperator.OR,
            constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }],
        },
        codigo: {
            operator: FilterOperator.OR,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        planta: {
            operator: FilterOperator.OR,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        empresa: {
            operator: FilterOperator.OR,
            constraints: [
                { value: null, matchMode: FilterMatchMode.IN }
            ]
        },
        rfc: {
            operator: FilterOperator.OR,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        clave_netsuite: {
            operator: FilterOperator.OR,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        clave_ubicacion_netsuite: {
            operator: FilterOperator.OR,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        tiene_certificado_fiscal: { value: null, matchMode: FilterMatchMode.EQUALS },
        expiracion_clave: {
            value: null,
            matchMode: FilterMatchMode.BETWEEN,
        },
    };
};

initFilters();

//Columnas visibles
const showColumns = ref({
    acciones: true,
    id: false,
    codigo: true,
    planta: true,
    empresa: true,
    rfc: true,
    clave_netsuite: true,
    clave_ubicacion_netsuite: true,
    tiene_certificado_fiscal: true,
    expiracion_clave: true,
});

const exportColumns = ref({
    id: true,
    codigo: true,
    planta: true,
    empresa: true,
    rfc: true,
    clave_netsuite: true,
    clave_ubicacion_netsuite: true,
    tiene_certificado_fiscal: true,
});

const columnLabels = {
    acciones: "Acciones",
    id: "ID",
    codigo: "Clave",
    planta: "Nombre",
    empresa: "Empresa",
    rfc: "RFC",
    clave_netsuite: "Clave NetSuite",
    clave_ubicacion_netsuite: "Clave Ubicación NetSuite",
    tiene_certificado_fiscal: "Certificado fiscal",
    expiracion_clave: "Expiración de claves fiscales",
};

//Filtros adicionales
const otherFilters = ref([
    {
        startDate: null,
        endDate: null,
    },
]);

//Diálogo de filtros adicionales
const otherFilterDialog = ref(false);

//Filtros del modal
const startDate = ref();
const endDate = ref();

//Referencia a la tabla de datos
const dt = ref(null);
const confirm = useConfirm();

//Columnas fijas
const frozenColumns = ref({
    acciones: false,
    id: false,
    codigo: false,
    planta: false,
    empresa: false,
    rfc: false,
    clave_netsuite: false,
    clave_ubicacion_netsuite: false,
    tiene_certificado_fiscal: false,
    expiracion_clave: false,
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

//Función para limpiar filtros
const clearFilter = () => {
    initFilters();
    otherFilters.value = [
        {
            startDate: null,
            endDate: null,
        },
    ];
};

//Función para aplicar filtros adicionales
const applyFilters = () => {
    otherFilterDialog.value = false;

    // Formatear fechas a AAAA-MM-DD
    const formatDate = (date) => {
        const d = new Date(date);
        const month = "" + (d.getMonth() + 1);
        const day = "" + d.getDate();
        const year = d.getFullYear();

        return [year, month.padStart(2, "0"), day.padStart(2, "0")].join("-");
    };
    otherFilters.value = [
        {
            startDate: formatDate(startDate.value),
            endDate: formatDate(endDate.value),
        },
    ];
    console.log("Filtros aplicados:", otherFilters.value);
};

//Función para remover la fecha de inicio
function removeStartDate() {
    loading.value = true;
    otherFilters.value[0].startDate = null;

    setTimeout(() => {
        loading.value = false;
    }, 500);
}

//Cargar datos de la API al montar el componente
onMounted(async () => {
    loading.value = false;
});

const goToEdit = (id) => {
    if (processingRows.value[id]) return;

    processingRows.value[id] = true;

    router.get(`/catalogs/branch-offices/${id}/edit`, {
        onFinish: () => {
            processingRows.value[id] = false;
        }
    });
};

const viewBranchOffice = (id) => {
    if (processingRows.value[id]) return;

    processingRows.value[id] = true;

    router.get(`/catalogs/branch-offices/${id}`, {
        onFinish: () => {
            processingRows.value[id] = false;
        }
    });
};

const processingRows = ref({});

const deleteBranchOffice = (row) => {
    confirm.require({
        message: `¿Deseas eliminar la planta "${row.planta}"?`,
        header: 'Confirmar eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Sí, eliminar',
        rejectLabel: 'Cancelar',
        acceptClass: 'p-button-danger',

        accept: () => {
            processingRows.value[row.id] = true;

            router.delete(`/catalogs/branch-offices/${row.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    showSuccess('Registro eliminado correctamente');
                },
                onError: () => {
                    showError('Error al eliminar el registro');
                },
                onFinish: () => {
                    processingRows.value[row.id] = false;
                }
            });
        },
    });
};

const deleteSelectedBranchOffices = () => {
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

            router.post('/catalogs/branch-offices/delete-multiple', {
                ids
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    showSuccess('Registros eliminados correctamente');
                    selected.value = [];
                },
                onError: () => {
                    showError('Error al eliminar los registros');
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

// watch para poder filtrar en rango de 1 dia 2025-01-10 00:00:00, 2025-01-10 23:59:59.999
watch(
    () => filters.value.expiracion_clave.value,
    (range) => {
        if (!Array.isArray(range) || !range[0]) return;

        const start = new Date(range[0]);
        const end = new Date(range[1] ?? range[0]);

        // normalizar
        start.setHours(0, 0, 0, 0);
        end.setHours(23, 59, 59, 999);

        // GUARDIA CRÍTICA (evita loop)
        const alreadyNormalized =
            range[0]?.getTime() === start.getTime() &&
            range[1]?.getTime() === end.getTime();

        if (alreadyNormalized) return;

        filters.value.expiracion_clave.value = [start, end];
    },
    { flush: 'post' }
);

//Función para guardar las columnas seleccionadas para exportar
const saveColumns = () => {
    columnsDialog.value = false;
    dt.value.exportCSV();
};

</script>

<template>
    <AppLayout :title="'Plantas'">
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
        <ConfirmDialog />
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
                                            icon="pi pi-trash"
                                            label="Eliminar seleccionados"
                                            severity="danger"
                                            text
                                            @click="deleteSelectedBranchOffices"
                                            v-if="(can('branch-offices.multiple-delete'))"
                                        />
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </Popover>
                    <Link
                        href="/catalogs/branch-offices/create"
                    >
                        <Button
                            label="Crear"
                            icon="pi pi-plus-circle"
                            severity="success"
                            class="ml-2"
                            v-if="(can('branch-offices.create'))"
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
                :rows="10"
                scrollable
                scrollHeight=800px
                v-model:filters="filters"
                filterDisplay="menu"
                exportFilename="Plantas"
                :globalFilterFields="[ 'id', 'codigo', 'planta', 'empresa', 'rfc', 'clave_netsuite', 'clave_ubicacion_netsuite', 'tiene_certificado_fiscal', 'expiracion_clave']"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} datos de prueba"
                removableSort
                :rowsPerPageOptions="[10, 20, 50, 100]"
                :loading="massProcessing"
            >
                <template #header>
                    <div
                        class="flex flex-wrap gap-2 items-end justify-between mb-6"
                    >
                        <div>
                            <h4 class="m-0">Plantas</h4>
                            <Button
                                icon="pi pi-filter"
                                rounded
                                v-tooltip.top="'Mostrar mas filtros'"
                                @click="otherFilterDialog = true"
                                disabled
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
                            :label="
                                'Fecha de inicio: ' + otherFilters[0].startDate
                            "
                            removable
                            v-if="otherFilters[0].startDate != null"
                            @remove="removeStartDate"
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
                        display: showColumns.acciones ? '' : 'none',
                    }"
                    header="Acciones"
                    :frozen="frozenColumns.acciones"
                    style="min-width: 14rem"
                >
                    <template #body="slotProps">
                        <Skeleton v-if="loading"></Skeleton>
                        <div v-else>
                            <Button
                                icon="pi pi-pencil"
                                class="mr-2"
                                rounded
                                v-tooltip.top="'Editar'"
                                severity="warn"
                                :disabled="processingRows[slotProps.data.id]"
                                :loading="processingRows[slotProps.data.id]"
                                @click="goToEdit(slotProps.data.id)"
                                v-if="(can('branch-offices.edit'))"
                            />
                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                v-tooltip.top="'Eliminar'"
                                class="mr-2"
                                rounded
                                :disabled="processingRows[slotProps.data.id]"
                                :loading="processingRows[slotProps.data.id]"
                                @click="deleteBranchOffice(slotProps.data)"
                                v-if="(can('branch-offices.delete'))"
                            />
                            <Button
                                icon="pi pi-eye"
                                severity="help"
                                v-tooltip.top="'Ver detalles'"
                                rounded
                                :disabled="processingRows[slotProps.data.id]"
                                :loading="processingRows[slotProps.data.id]"
                                @click="viewBranchOffice(slotProps.data.id)"
                            />
                        </div>
                    </template>
                </Column>
                <Column
                    field="id"
                    header="ID"
                    sortable
                    :frozen="frozenColumns.id"
                    :style="{
                        display: showColumns.id ? '' : 'none',
                    }"
                    :showFilterMatchModes="false"
                    :showFilterOperator="false"
                    :exportable="exportColumns.id"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.id }}</span>
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
                    field="codigo"
                    header="Clave"
                    sortable
                    :frozen="frozenColumns.codigo"
                    :style="{
                        display: showColumns.codigo ? '' : 'none',
                    }"
                    :showFilterMatchModes="false"
                    :showFilterOperator="false"
                    :exportable="exportColumns.codigo"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.codigo }}</span>
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
                    field="planta"
                    header="Nombre"
                    sortable
                    :frozen="frozenColumns.planta"
                    :style="{
                        display: showColumns.planta ? '' : 'none',
                    }"
                    style="min-width: 24rem"
                    :showFilterMatchModes="false"
                    :showFilterOperator="false"
                    :exportable="exportColumns.planta"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.planta }}</span>
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
                    field="empresa"
                    header="Empresa"
                    sortable
                    :frozen="frozenColumns.empresa"
                    :style="{
                        display: showColumns.empresa ? '' : 'none',
                    }"
                    :showFilterMatchModes="false"
                    :showFilterOperator="false"
                    :exportable="exportColumns.empresa"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.empresa }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <MultiSelect
                            v-model="filterModel.value"
                            :options="empresasOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Selecciona empresa(s)"
                            class="w-full"
                            display="chip"
                        />
                    </template>
                </Column>
                <Column
                    field="rfc"
                    header="RFC"
                    sortable
                    :frozen="frozenColumns.rfc"
                    :style="{
                        display: showColumns.rfc ? '' : 'none',
                    }"
                    :showFilterMatchModes="false"
                    :showFilterOperator="false"
                    :exportable="exportColumns.rfc"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.rfc }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por RFC"
                        /> 
                    </template>
                </Column>
                <Column
                    field="clave_netsuite"
                    header="Clave NetSuite"
                    sortable
                    :frozen="frozenColumns.clave_netsuite"
                    :style="{
                        display: showColumns.clave_netsuite ? '' : 'none',
                    }"
                    :showFilterMatchModes="false"
                    :showFilterOperator="false"
                    :exportable="exportColumns.clave_netsuite"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.clave_netsuite }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Clave NetSuite"
                        /> 
                    </template>
                </Column>
                <Column
                    field="clave_ubicacion_netsuite"
                    header="Clave Ubicación NetSuite"
                    sortable
                    :frozen="frozenColumns.clave_ubicacion_netsuite"
                    :style="{
                        display: showColumns.clave_ubicacion_netsuite ? '' : 'none',
                    }"
                    :showFilterMatchModes="false"
                    :showFilterOperator="false"
                    :exportable="exportColumns.clave_ubicacion_netsuite"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.clave_ubicacion_netsuite }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Clave Ubicación NetSuite"
                        /> 
                    </template>
                </Column>
                <Column
                    field="tiene_certificado_fiscal"
                    header="Certificado fiscal"
                    sortable
                    :exportable="exportColumns.tiene_certificado_fiscal"
                    :showFilterMatchModes="false"
                    :showFilterOperator="false"
                    :frozen="frozenColumns.tiene_certificado_fiscal"
                    :style="{
                        display: showColumns.tiene_certificado_fiscal ? '' : 'none',
                    }"
                >
                    <template #body="{ data }">
                        <i
                            :class="[
                                'pi',
                                data.tiene_certificado_fiscal
                                    ? 'pi-check-circle text-green-500'
                                    : 'pi-times-circle text-rose-400',
                                'text-xl'
                            ]"
                        />
                    </template>

                    <template #filter="{ filterModel }">
                        <Select
                            v-model="filterModel.value"
                            :options="[
                                { label: 'Sí', value: 1 },
                                { label: 'No', value: 0 }
                            ]"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="¿Tiene certificado?"
                            showClear
                        />
                    </template>
                </Column>
                <Column
                    field="expiracion_clave"
                    header="Expiración de claves fiscales"
                    dataType="date"
                    sortable
                    filter
                    :showFilterMatchModes="false"
                    :showFilterOperator="false"
                    :style="{
                        display: showColumns.expiracion_clave ? '' : 'none',
                    }"
                    :exportable="false"
                >
                    <template #body="{ data }">
                        {{ data.expiracion_clave?.toLocaleDateString('es-MX') }}
                    </template>

                    <template #filter="{ filterModel }">
                        <DatePicker
                            v-model="filterModel.value"
                            selectionMode="range"
                            placeholder="Selecciona rango"
                            dateFormat="yy-mm-dd"
                            showIcon
                            appendTo="body"
                            fluid
                            hideOnRangeSelection
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
                        <label :for="key" class="font-medium text-base">
                            <!-- {{ key.charAt(0).toUpperCase() + key.slice(1) }} -->
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
        </div>
    </AppLayout>
</template>
