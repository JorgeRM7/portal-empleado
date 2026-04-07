<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToastService } from "@/Stores/toastService";
import { useToast } from "primevue/usetoast";
import { onMounted, ref, computed, watch } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { router, usePage } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import * as XLSX from "xlsx";
import { useAuthz } from "@/composables/useAuthz";

const { can } = useAuthz();

const props = defineProps({
    Incidence: Object,
});
// console.log(props.Incidence);

const rows = computed(() => props.Incidence ?? []);

//Inicializar filtros globales de tabla
const filters = ref({});

//Inicializar variable de carga para mostrar esqueleto
const loading = ref(true);

//Función para mostrar toast de éxito y error
const { showErrorCustom, showSuccessCustom } = useToastService();

const page = usePage();

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            showSuccessCustom(flash.success);
        }

        if (flash?.error) {
            showErrorCustom(flash.error);
        }
    },
    { immediate: true }
);

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
        code: {
            operator: FilterOperator.OR,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        description: {
            operator: FilterOperator.OR,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        read_only: { value: null, matchMode: FilterMatchMode.EQUALS },
        active: { value: null, matchMode: FilterMatchMode.EQUALS },
    };
};

initFilters();

//Columnas visibles
const showColumns = ref({
    acciones: true,
    id: false,
    name: true,
    code: true,
    description: true,
    read_only: true,
    active: true,
    color: true,
});

//Columnas fijas
const frozenColumns = ref({
    acciones: true,
    id: false,
    name: false,
    code: false,
    description: false,
    read_only: false,
    active: false,
    color: false,
});

const columnLabels = {
    acciones: "Acciones",
    id: "Id",
    name: "Nombre",
    code: "Código",
    description: "Descripción",
    read_only: "Solo lectura",
    active: "Activo",
    color: "Color indicativo",
};

//Diálogo de filtros adicionales
const otherFilterDialog = ref(false);

//Referencia a la tabla de datos
const dt = ref(null);
const confirm = useConfirm();

//Columnas de exportación
const exportColumns = ref({
    id: true,
    name: true,
    code: true,
    description: true,
    read_only: true,
    active: true,
    color: true,
});

//Función para guardar las columnas seleccionadas para exportar
// const saveColumns = () => {
//     columnsDialog.value = false;
//     dt.value.exportCSV();
// };

const saveColumns = () => {
    columnsDialog.value = false;

    // 1️⃣ Tomar datos filtrados
    const filteredData = dt.value?.processedData || rows.value;

    // 2️⃣ Obtener columnas visibles
    const visibleColumns = Object.keys(exportColumns.value)
        .filter(key => exportColumns.value[key] && key !== "acciones");

    // 3️⃣ Construir datos exportables con headers personalizados
    const exportData = filteredData.map(row => {
        const newRow = {};

        visibleColumns.forEach(col => {
            let value = row[col];

            if (typeof value === "string") {
                value = value
                    .replace(/[\r\n]+/g, " ")
                    .replace(/\s+/g, " ")
                    .trim();
            }

            // Conversión opcional de booleanos
            if (typeof value === "boolean") {
                value = value ? 1 : 0;
            }

            newRow[columnLabels[col]] = value;
        });

        return newRow;
    });

    const worksheet = XLSX.utils.json_to_sheet(exportData);

    // 4️⃣ Auto ancho con máximo
    const colWidths = Object.keys(exportData[0] || {}).map(key => ({
        wch: Math.min(
            50,
            Math.max(
                key.length,
                ...exportData.map(r => (r[key]?.toString().length || 0))
            )
        )
    }));

    worksheet["!cols"] = colWidths;

    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Incidencias");

    XLSX.writeFile(workbook, "Incidencias.xlsx");
};

//Diálogo de selección de columnas
const columnsDialog = ref(false);

//Estado de diálogo de subida de archivos
const openUploadDialog = ref(false);

//Estado de progreso de subida
const progress = ref(0);

//Estado de visibilidad del toast
const visible = ref(false);

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

    router.get(`/catalogs/incidences/${id}/edit`, {
        onFinish: () => {
            processingRows.value[id] = false;
        }
    });
};

const viewItem = (id) => {
    if (processingRows.value[id]) return;

    processingRows.value[id] = true;

    router.get(`/catalogs/incidences/${id}`, {
        onFinish: () => {
            processingRows.value[id] = false;
        }
    });
};

const processingRows = ref({});

const deleteItem = (row) => {
    // console.log(row);
    confirm.require({
        message: `¿Deseas eliminar el departamento "${row.nombre}"?`,
        header: 'Confirmar eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Sí, eliminar',
        rejectLabel: 'Cancelar',
        acceptClass: 'p-button-danger',

        accept: () => {
            processingRows.value[row.id] = true;

            router.delete(`/catalogs/incidences/${row.id}`, {
                preserveScroll: true,
                // onSuccess: () => {
                //     showSuccess('Registro eliminado correctamente');
                // },
                // onError: () => {
                //     showError('Error al eliminar el registro');
                // },
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

            router.post('/catalogs/incidences/delete-multiple', {
                ids
            }, {
                preserveScroll: true,
                // onSuccess: () => {
                //     showSuccess('Registros eliminados correctamente');
                //     selected.value = [];
                // },
                // onError: () => {
                //     showError('Error al eliminar los registros');
                // },
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
                    route('catalogs.incidences.import'),
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
                        only: ['Incidence'],
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
    window.location.href = route('catalogs.incidences.import.template');
};

const getContrastColor = (hex) => {
    if (!hex) return "#000000"

    const color = hex.replace("#", "")
    const r = parseInt(color.substring(0, 2), 16)
    const g = parseInt(color.substring(2, 4), 16)
    const b = parseInt(color.substring(4, 6), 16)

    // luminancia perceptual
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b)

    return luminance > 186 ? "#000000" : "#ffffff"
}

const showDescriptionModal = ref(false)
const selectedDescription = ref('')

const openDescription = (data) => {
    selectedDescription.value = data.description
    showDescriptionModal.value = true
}








</script>

<template>
    <AppLayout :title="'Incidencias'">
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
                        v-if="(can('incidences.create'))"
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
                                            v-if="(can('incidences.delete'))"
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
                        href="/catalogs/incidences/create"
                    >
                        <Button
                            v-if="(can('incidences.create'))"
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
                exportFilename="Incidencias"
                :globalFilterFields="[ 
                    'acciones',
                    'id',
                    'name',
                    'code',
                    'description',
                    'read_only',
                    'active',
                    'color',
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
                            <h4 class="m-0">Incidencias</h4>
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
                                v-if="(can('incidences.edit'))"
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
                                v-if="(can('incidences.delete'))"
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
                    header="ID"
                    sortable
                    filter
                    :frozen="frozenColumns.id"
                    :style="{ display: showColumns.id ? '' : 'none' }"
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
                            placeholder="Buscar por id"
                        />
                    </template>
                </Column>

                <!-- Nombre -->
                <Column
                    field="name"
                    header="Nombre"
                    sortable
                    filter
                    :frozen="frozenColumns.name"
                    :style="{ display: showColumns.name ? '' : 'none' }"
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
                            placeholder="Buscar por Nombre"
                        />
                    </template>
                </Column>

                <!-- Código -->
                <Column
                    field="code"
                    header="Código"
                    sortable
                    filter
                    :frozen="frozenColumns.code"
                    :style="{ display: showColumns.code ? '' : 'none' }"
                    :showFilterMatchModes="false"
                    :showFilterOperator="false"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading" />
                        <span
                            v-else
                            class="px-3 py-1 rounded-md text-sm font-medium inline-block"
                            :style="{
                                backgroundColor: data.color,
                                color: getContrastColor(data.color)
                            }"
                        >
                            {{ data.code }}
                        </span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            placeholder="Buscar por Código"
                        />
                    </template>
                </Column>

                <!-- Descripción -->
                <Column
                    field="description"
                    header="Descripción"
                    sortable
                    filter
                    :frozen="frozenColumns.description"
                    :style="{ display: showColumns.description ? '' : 'none' }"
                    :showFilterMatchModes="false"
                    :showFilterOperator="false"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading" />

                        <div v-else class="max-w-xs">
                            <p class="line-clamp-3 text-gray-700 my-0">
                                {{ data.description }}
                            </p>

                            <button
                                v-if="data.description?.length > 120"
                                @click="openDescription(data)"
                                class="text-primary text-xs my-0 hover:underline"
                            >
                                Ver más
                            </button>
                        </div>
                    </template>

                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            placeholder="Buscar por Descripción"
                        />
                    </template>
                </Column>

                <!-- Solo lectura -->
                <Column
                    field="read_only"
                    header="Solo lectura"
                    sortable
                    filter
                    :frozen="frozenColumns.read_only"
                    :style="{ display: showColumns.read_only ? '' : 'none' }"
                    :showFilterMatchModes="false"
                    :showFilterOperator="false"
                >
                    <template #body="{ data }">
                        <i
                            :class="[
                                'pi',
                                data.read_only
                                    ? 'pi-check-circle text-emerald-500'
                                    : 'pi-times-circle text-rose-500',
                                'text-xl'
                            ]"
                        />
                    </template>

                    <template #filter="{ filterModel }">
                        <Select
                            v-model="filterModel.value"
                            :options="[
                                { label: 'Sí', value: true },
                                { label: 'No', value: false }
                            ]"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="¿Solo lectura?"
                            showClear
                        />
                    </template>
                </Column>

                <!-- Activo -->
                <Column
                    field="active"
                    header="Activo"
                    sortable
                    filter
                    :frozen="frozenColumns.active"
                    :style="{ display: showColumns.active ? '' : 'none' }"
                    :showFilterMatchModes="false"
                    :showFilterOperator="false"
                >
                    <template #body="{ data }">
                        <i
                            :class="[
                                'pi',
                                data.active
                                    ? 'pi-check-circle text-emerald-500'
                                    : 'pi-times-circle text-rose-500',
                                'text-xl'
                            ]"
                        />
                    </template>

                    <template #filter="{ filterModel }">
                        <Select
                            v-model="filterModel.value"
                            :options="[
                                { label: 'Sí', value: true },
                                { label: 'No', value: false }
                            ]"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="¿Está activo?"
                            showClear
                        />
                    </template>
                </Column>

                <!-- Color indicativo -->
                <Column
                    field="color"
                    header="Color indicativo"
                    sortable
                    :frozen="frozenColumns.color"
                    :style="{ display: showColumns.color ? '' : 'none' }"
                    :showFilterMatchModes="false"
                    :showFilterOperator="false"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading" />
                        <div v-else class="flex items-center">
                            <span
                                class="w-16 h-8 rounded border border-gray-300 cursor-pointer"
                                :style="{ backgroundColor: data.color }"
                                v-tooltip="data.color"
                            ></span>
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
                        Las Incidencias se importan desde un archivo CSV en formato UTF-8 (delimitado por comas), cuyos encabezados deben coincidir exactamente con los de la plantilla.
                        <br>
                        <br>
                        Los registros que incluyan un ID existente serán actualizados.
                        <br>
                        Los registros sin ID se crearán como nuevos.
                        <br>
                        <br>
                        El campo ID puede dejarse vacío para generar un nuevo registro automáticamente.
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
            <Dialog
                v-model:visible="showDescriptionModal"
                modal
                header="Descripción"
                class="w-[500px]"
            >
                <p class="whitespace-pre-line">
                    {{ selectedDescription }}
                </p>
            </Dialog>
        </div>
    </AppLayout>
</template>
