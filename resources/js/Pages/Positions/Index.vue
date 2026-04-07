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
    Positions: Object,
});
// console.log(props.Positions);

const rows = computed(() => props.Positions ?? []);

const getTypeTag = (value) => {
    switch (value) {
        case 'automatic':
        case 'automático':
            return { label: 'Automático', severity: 'info' };

        case 'efficiency':
        case 'eficiencia':
            return { label: 'Eficiencia', severity: 'success' };

        case 'trial':
        case 'periodo_prueba':
            return { label: 'Periodo de prueba', severity: 'warning' };

        case 'none':
        case null:
        default:
            return { label: 'Ninguna', severity: 'secondary' };
    }
};

//Inicializar filtros globales de tabla
const filters = ref({});

//Inicializar variable de carga para mostrar esqueleto
const loading = ref(true);

//Función para mostrar toast de éxito y error
const { showSuccess, showError, showErrorCustom, showSuccessCustom } = useToastService();

//Función para inicializar filtros
const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        id: {
            operator: FilterOperator.OR,
            constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }],
        },
        name: {
            operator: FilterOperator.OR,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        description: {
            operator: FilterOperator.OR,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        type: {
            operator: FilterOperator.OR,
            constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }],
        },
        type_adjust: {
            operator: FilterOperator.OR,
            constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }],
        },
    };
};

initFilters();

//Columnas visibles
const showColumns = ref({
    acciones: true,
    id: true,
    name: true,
    description: true,
    daily_salary: true,
    daily_salary_in_trial: true,
    compensation_in_trial: true,
    compensations_adjust: true,
    type: true,
    pa_in_trial: true,
    pp_in_trial: true,
    pa_adjust: true,
    pp_adjust: true,
    perceptions_in_trial: true,
    perceptions_adjust: true,
    net_in_trial: true,
    net_in_adjust: true,
    type_adjust: true,
});

const columnLabels = {
    acciones: "Acciones",
    id: "ID",
    name: "Puesto",
    description: "Descripción",
    daily_salary: "Salario Diario",
    daily_salary_in_trial: "Salario Prueba",
    compensation_in_trial: "Comp. Prueba",
    compensations_adjust: "Comp. Ajuste",
    type: "Determinación",
    pa_in_trial: "Prem. Asist. Prueba",
    pp_in_trial: "Prem. Punt. Prueba",
    pa_adjust: "Prem. Asist. Ajuste",
    pp_adjust: "Prem. Punt. Ajuste",
    perceptions_in_trial: "Percepciones Prueba",
    perceptions_adjust: "Percepciones Ajuste",
    net_in_trial: "Neto Prueba",
    net_in_adjust: "Neto Ajuste",
    type_adjust: "Tipo Ajuste",
};

//Diálogo de filtros adicionales
const otherFilterDialog = ref(false);

//Referencia a la tabla de datos
const dt = ref(null);
const confirm = useConfirm();

//Columnas fijas
const frozenColumns = ref({
    acciones: false,
    id: false,
    name: false,
    description: false,
    daily_salary: false,
    daily_salary_in_trial: false,
    compensation_in_trial: false,
    compensations_adjust: false,
    type: false,
    pa_in_trial: false,
    pp_in_trial: false,
    pa_adjust: false,
    pp_adjust: false,
    perceptions_in_trial: false,
    perceptions_adjust: false,
    net_in_trial: false,
    net_in_adjust: false,
    type_adjust: false,
});

//Columnas de exportación
const exportColumns = ref({
    id: true,
    name: true,
    description: true,
    daily_salary: true,
    daily_salary_in_trial: true,
    compensation_in_trial: true,
    compensations_adjust: true,
    type: true,
    pa_in_trial: true,
    pp_in_trial: true,
    pa_adjust: true,
    pp_adjust: true,
    perceptions_in_trial: true,
    perceptions_adjust: true,
    net_in_trial: true,
    net_in_adjust: true,
    type_adjust: true,
});

//Función para guardar las columnas seleccionadas para exportar
const saveColumns = () => {
    columnsDialog.value = false;
    dt.value.exportCSV();
};

//Diálogo de selección de columnas
const columnsDialog = ref(false);

//Estado de diálogo de subida de archivos
const openUploadDialog = ref(false);

//Estado de progreso de subida
const progress = ref(0);

//Estado de visibilidad del toast
const visible = ref(false);

//Referencia al servicio de toast personalizado
const interval = ref();

//Estado de envío
const submitted = ref(false);

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

//Cargar datos de la API al montar el componente
onMounted(async () => {
    loading.value = false;
});

const editItem = (id) => {
    if (processingRows.value[id]) return;

    processingRows.value[id] = true;

    router.get(`/catalogs/positions/${id}/edit`, {
        onFinish: () => {
            processingRows.value[id] = false;
        }
    });
};

const viewItem = (id) => {
    if (processingRows.value[id]) return;

    processingRows.value[id] = true;

    router.get(`/catalogs/positions/${id}`, {
        onFinish: () => {
            processingRows.value[id] = false;
        }
    });
};

const processingRows = ref({});

const deleteItem = (row) => {
    confirm.require({
        message: `¿Deseas eliminar el puesto "${row.name}"?`,
        header: 'Confirmar eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Sí, eliminar',
        rejectLabel: 'Cancelar',
        acceptClass: 'p-button-danger',

        accept: () => {
            processingRows.value[row.id] = true;

            router.delete(`/catalogs/positions/${row.id}`, {
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

            router.post('/catalogs/positions/delete-multiple', {
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

// -------------- Subida desde csv -------------- 
// Ícono por extensión
const fileIcon = (file) => {
    const ext = file.name.split(".").pop()?.toLowerCase();
    if (ext === "csv") return "pi pi-file";
    if (["xls", "xlsx"].includes(ext)) return "pi pi-file-excel";
    return "pi pi-file";
};

const toast = useToast();
const importErrors = ref([]);
const showErrors = ref(false);

const onCustomUploader = async ({ files }) => {
    if (!files || !files.length) return;

    const file = files[files.length - 1];

    confirm.require({
        message: `¿Deseas procesar el archivo "${file.name}"? 
            Este proceso puede crear o actualizar múltiples registros.`,
        header: 'Confirmar carga masiva',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Sí, procesar',
        acceptClass: 'p-button-warning',
        rejectLabel: 'Cancelar',
        rejectClass: 'p-button-secondary',

        accept: async () => {
            const formData = new FormData();
            formData.append('file', file);

            submitted.value = true;
            progress.value = 0;

            try {
                if (!visible.value) {
                    toast.add({
                        severity: 'custom',
                        summary: 'Subiendo archivo...',
                        group: 'headless',
                        styleClass: 'backdrop-blur-lg rounded-2xl',
                    });
                    visible.value = true;
                }

                const { data } = await axios.post(
                    route('positions.import'),
                    formData,
                    {
                        headers: {
                            'Content-Type': 'multipart/form-data',
                            Accept: 'application/json',
                        },
                        onUploadProgress: (event) => {
                            if (!event.total) return;
                            progress.value = Math.round(
                                (event.loaded * 90) / event.total
                            );
                        },
                    }
                );

                showSuccessCustom(data?.message || 'Importación completada.');
                openUploadDialog.value = false;
                console.warn(data);

                // mostrar errores si los hay
                if (data.errors.length > 0) {
                    console.log('Entra a errores');
                    console.log(data.errors);
                    importErrors.value = data.errors;
                    console.log('carga la tabla de errores');
                    showErrors.value = true;
                    console.log('muestra el diálogo de errores');
                }

                try {
                    router.reload({
                        only: ['Positions'],
                        preserveScroll: true,
                    });
                } catch (err) {
                    console.warn('Import OK, pero error al recargar tabla', err);
                }

            } catch (e) {
                console.error(e.response);
                const status = e?.response?.status ?? 0;

                let msg =
                    e?.response?.data?.message ||
                    e?.response?.data?.errors?.file?.[0] ||
                    '';

                if (!msg) {
                    msg =
                        'No se pudo importar el archivo. Verifica que el CSV sea válido.';
                }

                showErrorCustom(msg);
            } finally {
                submitted.value = false;
                setTimeout(() => {
                    toast.removeGroup('headless');
                    visible.value = false;
                    progress.value = 0;
                }, 800);
            }
        },
    });

};

const downloadTemplate = () => {
    window.location.href = route('positions.import.template');
};







</script>

<template>
    <AppLayout :title="'Puestos'">
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
                        v-if="(can('positions.create'))"
                        type="button"
                        icon="pi pi-download"
                        label="Importar"
                        severity="contrast"
                        class="mt-2 ml-2"
                        @click="openUploadDialog = true"
                    />
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
                                            v-if="(can('positions.delete'))"
                                            icon="pi pi-trash"
                                            label="Eliminar seleccionados"
                                            severity="danger"
                                            text
                                            @click="deleteSelectedItems"
                                        />
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </Popover>
                    <Link
                        href="/catalogs/positions/create"
                    >
                        <Button
                            v-if="(can('positions.create'))"
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
                :rows="10"
                scrollable
                scrollHeight=800px
                v-model:filters="filters"
                filterDisplay="menu"
                exportFilename="Puestos"
                :globalFilterFields="[ 
                    'id',
                    'name',
                    'description',
                    'daily_salary',
                    'daily_salary_in_trial',
                    'compensation_in_trial',
                    'compensations_adjust',
                    'type',
                    'pa_in_trial',
                    'pp_in_trial',
                    'pa_adjust',
                    'pp_adjust',
                    'perceptions_in_trial',
                    'perceptions_adjust',
                    'net_in_trial',
                    'net_in_adjust',
                    'type_adjust',
                ]"
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
                            <h4 class="m-0">Puestos</h4>
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
                </template>

                <!-- Selección -->
                    <Column
                        selectionMode="multiple"
                        style="width: 4rem"
                        :exportable="false"
                    />

                    <!-- Acciones -->
                    <Column
                        header="Acciones"
                        style="min-width: 14rem"
                        :exportable="false"
                        :frozen="frozenColumns.acciones"
                        :style="{
                            display: showColumns.acciones ? '' : 'none',
                        }"
                    >
                        <template #body="slotProps">
                            <Skeleton v-if="loading" />
                            <div v-else>
                                <Button
                                    v-if="(can('positions.edit'))"
                                    icon="pi pi-pencil"
                                    class="mr-2"
                                    severity="warn"
                                    v-tooltip.top="'Editar'"
                                    rounded
                                    :disabled="processingRows[slotProps.data.id]"
                                    :loading="processingRows[slotProps.data.id]"
                                    @click="editItem(slotProps.data.id)"
                                />
                                <Button
                                    v-if="(can('positions.delete'))"
                                    icon="pi pi-trash"
                                    class="mr-2"
                                    severity="danger"
                                    v-tooltip.top="'Eliminar'"
                                    rounded
                                    :disabled="processingRows[slotProps.data.id]"
                                    :loading="processingRows[slotProps.data.id]"
                                    @click="deleteItem(slotProps.data)"
                                />
                                <Button
                                    icon="pi pi-eye"
                                    class="mr-2"
                                    severity="help"
                                    v-tooltip.top="'Ver detalles'"
                                    rounded
                                    :disabled="processingRows[slotProps.data.id]"
                                    :loading="processingRows[slotProps.data.id]"
                                    @click="viewItem(slotProps.data.id)"
                                />
                            </div>
                        </template>
                    </Column>

                    <!-- ID -->
                    <Column
                        field="id"
                        :exportable="exportColumns.id"
                        header="ID"
                        sortable
                        filter
                        :frozen="frozenColumns.id"
                        :style="{
                            display: showColumns.id ? '' : 'none',
                        }"
                        :showFilterMatchModes="false"
                        :showFilterOperator="false"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading" />
                            <span v-else>{{ data.id }}</span>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText
                                v-model="filterModel.value"
                                placeholder="Buscar ID"
                            />
                        </template>
                    </Column>

                    <!-- Puesto -->
                    <Column
                        field="name"
                        :exportable="exportColumns.name"
                        header="Puesto"
                        sortable
                        :frozen="frozenColumns.name"
                        :style="{
                            display: showColumns.name ? '' : 'none',
                        }"
                        style="min-width: 16rem"
                        :showFilterMatchModes="false"
                        :showFilterOperator="false"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading" />
                            <span v-else>{{ data.name }}</span>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText
                                v-model="filterModel.value"
                                placeholder="Buscar puesto"
                            />
                        </template>
                    </Column>

                    <!-- Descripción -->
                    <Column
                        field="description"
                        :exportable="exportColumns.description"
                        header="Descripción"
                        filter
                        style="min-width: 26rem"
                        :frozen="frozenColumns.description"
                        :style="{
                            display: showColumns.description ? '' : 'none',
                        }"
                        :showFilterMatchModes="false"
                        :showFilterOperator="false"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading" />
                            <span v-else class="line-clamp-2">
                                {{ data.description }}
                            </span>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText
                                v-model="filterModel.value"
                                placeholder="Buscar descripción"
                            />
                        </template>
                    </Column>

                    <!-- Salario Diario -->
                    <Column
                        field="daily_salary"
                        :exportable="exportColumns.daily_salary"
                        header="Salario Diario"
                        sortable
                        filter
                        :frozen="frozenColumns.daily_salary"
                        :style="{
                            display: showColumns.daily_salary ? '' : 'none',
                        }"
                        style="min-width: 10rem"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading" />
                            <span v-else>$ {{ Number(data.daily_salary).toFixed(2) }}</span>
                        </template>
                    </Column>

                    <!-- Salario Prueba -->
                    <Column
                        field="daily_salary_in_trial"
                        :exportable="exportColumns.daily_salary_in_trial"
                        header="Salario Prueba"
                        sortable
                        filter
                        :frozen="frozenColumns.daily_salary_in_trial"
                        :style="{
                            display: showColumns.daily_salary_in_trial ? '' : 'none',
                        }"
                        style="min-width: 10rem"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading" />
                            <span v-else>$ {{ Number(data.daily_salary_in_trial).toFixed(2) }}</span>
                        </template>
                    </Column>

                    <!-- Comp Prueba -->
                    <Column
                        field="compensation_in_trial"
                        :exportable="exportColumns.compensation_in_trial"
                        header="Comp. Prueba"
                        sortable
                        filter
                        :frozen="frozenColumns.compensation_in_trial"
                        :style="{
                            display: showColumns.compensation_in_trial ? '' : 'none',
                        }"
                        style="min-width: 10rem"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading" />
                            <span v-else>$ {{ Number(data.compensation_in_trial).toFixed(2) }}</span>
                        </template>
                    </Column>

                    <!-- Comp Ajuste -->
                    <Column
                        field="compensations_adjust"
                        :exportable="exportColumns.compensations_adjust"
                        header="Comp. Ajuste"
                        sortable
                        filter
                        :frozen="frozenColumns.compensations_adjust"
                        :style="{
                            display: showColumns.compensations_adjust ? '' : 'none',
                        }"
                        style="min-width: 10rem"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading" />
                            <span v-else>$ {{ Number(data.compensations_adjust).toFixed(2) }}</span>
                        </template>
                    </Column>

                    <!-- Determinación -->
                    <Column
                        field="type"
                        :exportable="exportColumns.type"
                        header="Determinación"
                        sortable
                        filter
                        :showFilterMatchModes="false"
                        :showFilterOperator="false"
                        :frozen="frozenColumns.type"
                        :style="{
                            display: showColumns.type ? '' : 'none',
                        }"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading" />
                            <Tag
                                v-else
                                :value="getTypeTag(data.type).label"
                                :severity="getTypeTag(data.type).severity"
                            />
                        </template>

                        <template #filter="{ filterModel }">
                            <Select
                                v-model="filterModel.value"
                                :options="[
                                    { label: 'Automático', value: 'automatic' },
                                    { label: 'Eficiencia', value: 'efficiency' },
                                    { label: 'Periodo de prueba', value: 'trial' },
                                    { label: 'Ninguna', value: 'none' }
                                ]"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Selecciona tipo"
                                showClear
                            />
                        </template>
                    </Column>

                    <!-- Prem asist prueba -->
                    <Column
                        field="pa_in_trial"
                        :exportable="exportColumns.pa_in_trial"
                        header="Prem. Asist. Prueba"
                        sortable
                        filter
                        :frozen="frozenColumns.pa_in_trial"
                        :style="{
                            display: showColumns.pa_in_trial ? '' : 'none',
                        }"
                        style="min-width: 10rem"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading" />
                            <span v-else>{{ (Number(data.pa_in_trial) * 100).toFixed(2) }} %</span>
                        </template>
                    </Column>

                    <!-- Prem punt prueba -->
                    <Column
                        field="pp_in_trial"
                        :exportable="exportColumns.pp_in_trial"
                        header="Prem. Punt. Prueba"
                        sortable
                        filter
                        :frozen="frozenColumns.pp_in_trial"
                        :style="{
                            display: showColumns.pp_in_trial ? '' : 'none',
                        }"
                        style="min-width: 10rem"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading" />
                            <span v-else>{{ (Number(data.pp_in_trial) * 100).toFixed(2) }} %</span>
                        </template>
                    </Column>

                    <!-- Prem asist ajuste -->
                    <Column
                        field="pa_adjust"
                        :exportable="exportColumns.pa_adjust"
                        header="Prem. Asist. Ajuste"
                        sortable
                        filter
                        :frozen="frozenColumns.pa_adjust"
                        :style="{
                            display: showColumns.pa_adjust ? '' : 'none',
                        }"
                        style="min-width: 10rem"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading" />
                            <span v-else>{{ (Number(data.pa_adjust) * 100).toFixed(2) }} %</span>
                        </template>
                    </Column>

                    <!-- Prem punt ajuste -->
                    <Column
                        field="pp_adjust"
                        :exportable="exportColumns.pp_adjust"
                        header="Prem. Punt. Ajuste"
                        sortable
                        filter
                        :frozen="frozenColumns.pp_adjust"
                        :style="{
                            display: showColumns.pp_adjust ? '' : 'none',
                        }"
                        style="min-width: 10rem"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading" />
                            <span v-else>{{ (Number(data.pp_adjust) * 100).toFixed(2) }} %</span>
                        </template>
                    </Column>

                    <!-- Percepciones prueba -->
                    <Column
                        field="perceptions_in_trial"
                        :exportable="exportColumns.perceptions_in_trial"
                        header="Percepciones Prueba"
                        sortable
                        filter
                        :frozen="frozenColumns.perceptions_in_trial"
                        :style="{
                            display: showColumns.perceptions_in_trial ? '' : 'none',
                        }"
                        style="min-width: 10rem"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading" />
                            <span v-else>$ {{ Number(data.perceptions_in_trial).toFixed(2) }}</span>
                        </template>
                    </Column>

                    <!-- Percepciones ajuste -->
                    <Column
                        field="perceptions_adjust"
                        :exportable="exportColumns.perceptions_adjust"
                        header="Percepciones Ajuste"
                        sortable
                        filter
                        :frozen="frozenColumns.perceptions_adjust"
                        :style="{
                            display: showColumns.perceptions_adjust ? '' : 'none',
                        }"
                        style="min-width: 10rem"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading" />
                            <span v-else>$ {{ Number(data.perceptions_adjust).toFixed(2) }}</span>
                        </template>
                    </Column>

                    <!-- Neto prueba -->
                    <Column
                        field="net_in_trial"
                        :exportable="exportColumns.net_in_trial"
                        header="Neto Prueba"
                        sortable
                        filter
                        :frozen="frozenColumns.net_in_trial"
                        :style="{
                            display: showColumns.net_in_trial ? '' : 'none',
                        }"
                        style="min-width: 10rem"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading" />
                            <span v-else>$ {{ Number(data.net_in_trial).toFixed(2) }}</span>
                        </template>
                    </Column>

                    <!-- Neto ajuste -->
                    <Column
                        field="net_in_adjust"
                        :exportable="exportColumns.net_in_adjust"
                        header="Neto Ajuste"
                        sortable
                        filter
                        :frozen="frozenColumns.net_in_adjust"
                        :style="{
                            display: showColumns.net_in_adjust ? '' : 'none',
                        }"
                        style="min-width: 10rem"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading" />
                            <span v-else>$ {{ Number(data.net_in_adjust).toFixed(2) }}</span>
                        </template>
                    </Column>

                    <!-- Tipo ajuste -->
                    <Column
                        field="type_adjust"
                        :exportable="exportColumns.type_adjust"
                        header="Tipo Ajuste"
                        sortable
                        filter
                        :frozen="frozenColumns.type_adjust"
                        :style="{
                            display: showColumns.type_adjust ? '' : 'none',
                        }"
                        :showFilterMatchModes="false"
                        :showFilterOperator="false"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading" />
                            <Tag
                                v-else
                                :value="getTypeTag(data.type_adjust).label"
                                :severity="getTypeTag(data.type_adjust).severity"
                            />
                        </template>

                        <template #filter="{ filterModel }">
                            <Select
                                v-model="filterModel.value"
                                :options="[
                                    { label: 'Automático', value: 'automatic' },
                                    { label: 'Eficiencia', value: 'efficiency' },
                                    { label: 'Periodo de prueba', value: 'trial' },
                                    { label: 'Ninguna', value: 'none' }
                                ]"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Selecciona tipo"
                                showClear
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
            <Dialog v-model:visible="openUploadDialog" :style="{ width: '450px' }" header="Subir Archivo" :modal="true">
                <div class="flex flex-col gap-6">
                    <FileUpload
                        mode="advanced"
                        ref="fileUploadRef"
                        name="files[]"
                        accept=".csv"
                        :maxFileSize="2000000"
                        :multiple="false"
                        customUpload
                        @uploader="onCustomUploader"
                    >
                        @uploader="onCustomUploader">
                        <template #content="{
                            files,
                            uploadedFiles,
                            removeUploadedFileCallback,
                            removeFileCallback,
                        }">
                            <div v-if="files.length">
                                <h5>Pendientes</h5>
                                <div class="flex flex-wrap gap-4">
                                    <div
                                        v-if="files.length"
                                        class="p-4 border rounded-md flex flex-col items-center gap-2 w-52"
                                    >
                                        <i :class="fileIcon(files[files.length - 1])" class="pi text-5xl" />

                                        <span class="font-semibold text-ellipsis max-w-44 whitespace-nowrap overflow-hidden">
                                            {{ files[files.length - 1].name }}
                                        </span>

                                        <small>
                                            {{ (files[files.length - 1].size / 1024).toFixed(2) }} KB
                                        </small>

                                        <Button
                                            icon="pi pi-times"
                                            rounded
                                            severity="danger"
                                            variant="outlined"
                                            @click="removeFileCallback(files.length - 1)"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div v-if="uploadedFiles.length" class="mt-6">
                                <h5>Completados</h5>
                                <div class="flex flex-wrap gap-4">
                                    <div v-for="(file, i) in uploadedFiles" :key="file.name + file.size"
                                        class="p-4 border rounded-md flex flex-col items-center gap-2 w-52">
                                        <i :class="fileIcon(file)" class="pi text-5xl" />
                                        <span
                                            class="font-semibold text-ellipsis max-w-44 whitespace-nowrap overflow-hidden">
                                            {{ file.name }}
                                        </span>
                                        <Badge value="OK" severity="success" />
                                        <Button icon="pi pi-times" @click="removeUploadedFileCallback(i)" rounded
                                            severity="danger" variant="outlined" />
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template #empty>
                            <span>Arrastra y suelta tu archivo CSV aquí.</span>
                        </template>
                    </FileUpload>
                    <Message severity="info" :closable="false">
                        Los Departamentos se importan desde un archivo CSV en formato UTF-8 (delimitado por comas), cuyos encabezados deben coincidir exactamente con los de la plantilla.
                        <br>
                        <br>
                        Los registros que incluyan un ID existente serán actualizados.
                        <br>
                        Los registros sin ID se crearán como nuevos.
                        <br>
                        <br>
                        El campo ID puede dejarse vacío para generar un nuevo registro automáticamente.
                        <br>
                        Hay que cuidar que los campos Determinacion y Tipo ajuste vengan como en el ejemplo.
                        <br>
                        <br>
                        Recomendación: realizar la creación y actualización en procesos separados, ya que un registro recién creado puede ser actualizado inmediatamente si se reutiliza su ID dentro del mismo archivo.
                    </Message>
                    <div class="flex">
                        <Button
                            icon="pi pi-file"
                            label="Descargar Plantilla"
                            severity="secondary"
                            class="m-1"
                            as="a"
                            @click="downloadTemplate"
                        />
                    </div>
                </div>
            </Dialog>
            <Dialog v-model:visible="showErrors" :style="{ minWidth: '450px' }" header="Errores durante la importación" :modal="true">
                <DataTable
                    :value="importErrors"
                    stripedRows
                    responsiveLayout="scroll"
                    class="p-datatable p-1"
                >
                    <Column field="row" header="Fila" style="width: 6rem" />

                    <Column header="Errores">
                        <template #body="{ data }">
                            <ul class="list-disc pl-5 space-y-1">
                                <li
                                    v-for="(error, i) in data.errors"
                                    :key="i"
                                    class="text-amber-500"
                                >
                                    {{ error }}
                                </li>
                            </ul>
                        </template>
                    </Column>
                </DataTable>

                <template #footer>
                    <Button
                        label="Cerrar"
                        icon="pi pi-times"
                        severity="secondary"
                        @click="showErrors = false"
                    />
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>
