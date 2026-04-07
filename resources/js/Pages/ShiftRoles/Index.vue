<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToastService } from "@/Stores/toastService";
import { useToast } from "primevue/usetoast";
import { onMounted, ref, computed } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { useAuthz } from "@/composables/useAuthz";

const { can } = useAuthz();

const props = defineProps({
    ShiftRoles: Array,
});
// console.log(props.ShiftRoles);

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
        dynamic: { value: null, matchMode: FilterMatchMode.EQUALS },
        name: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        id: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }],
        },
    };
};

initFilters();

//Columnas visibles
const showColumns = ref({
    acciones: true,
    id: false,
    name: true,
    dynamic: true,
});

//Filtros adicionales
const otherFilters = ref([
    {
        startDate: null,
        endDate: null,
    },
]);

const columnLabels = {
    acciones: "Acciones",
    id: "ID",
    name: "Nombre",
    dynamic: "Dinámico",
};

//Diálogo de filtros adicionales
const otherFilterDialog = ref(false);

//Filtros del modal
const startDate = ref();
const endDate = ref();

//Referencia a la tabla de datos
const dt = ref(null);
const toast = useToast();
const confirm = useConfirm();

//Columnas fijas
const frozenColumns = ref({
    acciones: false,
    id: false,
    name: false,
    dynamic: false,
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

const rows = computed(() => props.ShiftRoles);

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

// //Función para aplicar filtros adicionales
// const applyFilters = () => {
//     otherFilterDialog.value = false;

//     // Formatear fechas a AAAA-MM-DD
//     const formatDate = (date) => {
//         const d = new Date(date);
//         const month = "" + (d.getMonth() + 1);
//         const day = "" + d.getDate();
//         const year = d.getFullYear();

//         return [year, month.padStart(2, "0"), day.padStart(2, "0")].join("-");
//     };
//     otherFilters.value = [
//         {
//             startDate: formatDate(startDate.value),
//             endDate: formatDate(endDate.value),
//         },
//     ];
//     console.log("Filtros aplicados:", otherFilters.value);
// };

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

    router.get(`/catalogs/shift-roles/${id}/edit`, {
        onFinish: () => {
            processingRows.value[id] = false;
        }
    });
};

const viewShiftRole = (id) => {
    if (processingRows.value[id]) return;

    processingRows.value[id] = true;

    router.get(`/catalogs/shift-roles/${id}`, {
        onFinish: () => {
            processingRows.value[id] = false;
        }
    });
};

const processingRows = ref({});

const deleteShiftRole = (row) => {
    confirm.require({
        message: `¿Deseas eliminar el rol "${row.name}"?`,
        header: 'Confirmar eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Sí, eliminar',
        rejectLabel: 'Cancelar',
        acceptClass: 'p-button-danger',

        accept: () => {
            processingRows.value[row.id] = true;

            router.delete(`/catalogs/shift-roles/${row.id}`, {
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

const deleteSelectedShiftRoles = () => {
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

            router.post('/catalogs/shift-roles/delete-multiple', {
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

const creating = ref(false);

</script>

<template>
    <AppLayout :title="'Rol De Turno'">
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
                                            v-if="(can('shift-roles.delete'))"
                                            icon="pi pi-trash"
                                            label="Eliminar seleccionados"
                                            severity="danger"
                                            text
                                            @click="deleteSelectedShiftRoles"
                                        />
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </Popover>
                    <Link
                        href="/catalogs/shift-roles/create"
                        :class="{ 'pointer-events-none opacity-70': creating }"
                        @click="creating = true"
                    >
                        <Button
                            v-if="(can('shift-roles.create'))"
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
                scrollHeight="800px"
                v-model:filters="filters"
                filterDisplay="menu"
                exportFilename="prueba"
                :globalFilterFields="['id', 'dynamic', 'name']"
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
                            <h4 class="m-0">Rol De Turno</h4>
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
                >
                    <template #body="slotProps">
                        <Skeleton v-if="loading"></Skeleton>
                        <div v-else>
                            <Button
                                v-if="(can('shift-roles.edit'))"
                                icon="pi pi-pencil"
                                class="mr-2"
                                rounded
                                v-tooltip.top="'Editar'"
                                severity="warn"
                                :loading="processingRows[slotProps.data.id]"
                                :disabled="processingRows[slotProps.data.id]"
                                @click="goToEdit(slotProps.data.id)"
                            />
                            <Button
                                v-if="(can('shift-roles.delete'))"
                                icon="pi pi-trash"
                                severity="danger"
                                v-tooltip.top="'Eliminar'"
                                class="mr-2"
                                rounded
                                :loading="processingRows[slotProps.data.id]"
                                :disabled="processingRows[slotProps.data.id]"
                                @click="deleteShiftRole(slotProps.data)"
                            />
                            <Button
                                icon="pi pi-eye"
                                severity="help"
                                v-tooltip.top="'Ver detalles'"
                                rounded
                                :loading="processingRows[slotProps.data.id]"
                                :disabled="processingRows[slotProps.data.id]"
                                @click="viewShiftRole(slotProps.data.id)"
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
                    field="name"
                    header="Nombre"
                    sortable
                    :frozen="frozenColumns.name"
                    :style="{
                        display: showColumns.name ? '' : 'none',
                    }"
                    :showFilterMatchModes="false"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.name }}</span>
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
                    field="dynamic"
                    header="Dinámico"
                    sortable
                    :showFilterMatchModes="false"
                    :frozen="frozenColumns.dynamic"
                    :style="{
                        display: showColumns.dynamic ? '' : 'none',
                    }"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <div v-else class="flex justify-center">
                            <i
                                v-if="data.dynamic == 1"
                                class="pi pi-check-circle text-blue-500 text-xl"
                            ></i>

                            <i
                                v-else
                                class="pi pi-times-circle text-gray-500 text-xl"
                            ></i>
                        </div>
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
                            placeholder="Filtrar dinámico"
                            showClear
                        />
                    </template>
                </Column>
            </DataTable>
            <!-- <Dialog
                v-model:visible="otherFilterDialog"
                :style="{ width: '450px' }"
                header="Seleccionar filtros adicionales"
                :modal="true"
            >
                <div class="flex flex-col gap-6">
                    <DatePicker v-model="startDate" dateFormat="yy-mm-dd" />
                    <DatePicker v-model="endDate" dateFormat="yy-mm-dd" />
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
            </Dialog> -->
        </div>
    </AppLayout>
</template>
