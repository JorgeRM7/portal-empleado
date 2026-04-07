<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToastService } from "@/Stores/toastService";
import { onMounted, ref, computed, watch } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { router, usePage } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { useAuthz } from "@/composables/useAuthz";
const { can } = useAuthz();

const props = defineProps({
    Schedules: Object,
});
// console.log(props.Schedules);

const rows = computed(() => props.Schedules ?? []);

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
        entry_time: {
            operator: FilterOperator.OR,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        leave_time: {
            operator: FilterOperator.OR,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        normal_double_overtime: {
            operator: FilterOperator.OR,
            constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }],
        },
        normal_triple_overtime: {
            operator: FilterOperator.OR,
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
    entry_time: true,
    leave_time: true,
    normal_double_overtime: true,
    normal_triple_overtime: true,
});

//Columnas fijas
const frozenColumns = ref({
    acciones: true,
    id: false,
    name: false,
    entry_time: false,
    leave_time: false,
    normal_double_overtime: false,
    normal_triple_overtime: false,
});

// Traducciones
const columnLabels = {
    acciones: "Acciones",
    id: "Clave",
    name: "Nombre",
    entry_time: "Hora de entrada",
    leave_time: "Hora de salida",
    normal_double_overtime: "Horas extra dobles",
    normal_triple_overtime: "Horas extra triple",
};

//Diálogo de filtros adicionales
const otherFilterDialog = ref(false);

//Referencia a la tabla de datos
const dt = ref(null);
const confirm = useConfirm();

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
};

//Cargar datos de la API al montar el componente
onMounted(async () => {
    loading.value = false;
});

const editItem = (id) => {
    if (processingRows.value[id]) return;

    processingRows.value[id] = true;

    router.get(`/catalogs/schedules/${id}/edit`, {
        onFinish: () => {
            processingRows.value[id] = false;
        }
    });
};

const viewItem = (id) => {
    if (processingRows.value[id]) return;

    processingRows.value[id] = true;

    router.get(`/catalogs/schedules/${id}`, {
        onFinish: () => {
            processingRows.value[id] = false;
        }
    });
};

const processingRows = ref({});

const deleteItem = (row) => {
    console.log(row);
    confirm.require({
        message: `¿Deseas eliminar el horario "${row.name}"?`,
        header: 'Confirmar eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Sí, eliminar',
        rejectLabel: 'Cancelar',
        acceptClass: 'p-button-danger',

        accept: () => {
            processingRows.value[row.id] = true;

            router.delete(`/catalogs/schedules/${row.id}`, {
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

            router.post('/catalogs/schedules/delete-multiple', {
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








</script>

<template>
    <AppLayout :title="'Horarios'">
        <ConfirmDialog />
        <div class="card">
            <Toolbar>
                <template #start>
                    
                </template>

                <template #end>
                    <Button type="button" label="Acciones Masivas" class="min-w-48" icon="pi pi-wrench"
                        @click="toggleAccionesMasivas($event)" :disabled="selected.length === 0" />
                    <Popover ref="op">
                        <div class="flex flex-col gap-4">
                            <div>
                                <span class="font-medium block mb-2">Acciones Masivas</span>
                                <ul class="list-none p-0 m-0 flex flex-col">
                                    <li
                                        class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border">
                                        <Button icon="pi pi-trash" label="Eliminar seleccionados" severity="danger" text
                                            @click="deleteSelectedItems" 
                                            v-if="(can('schedules.delete'))" 
                                            />
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </Popover>
                    <Link href="/catalogs/schedules/create">
                        <Button label="Crear" icon="pi pi-plus-circle" severity="success" class="ml-2" 
                            v-if="(can('schedules.create'))"   
                        />
                    </Link>
                </template>
            </Toolbar>
            <DataTable ref="dt" v-model:selection="selected" :value="rows" dataKey="id" :paginator="true" :rows="10"
                scrollable scrollHeight=800px v-model:filters="filters" filterDisplay="menu"
                exportFilename="Horarios" :globalFilterFields="[
                    'id',
                    'name',
                    'entry_time',
                    'leave_time',
                    'normal_double_overtime',
                    'normal_triple_overtime',
                ]"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} datos de prueba" removableSort
                :rowsPerPageOptions="[10, 20, 50, 100]" :loading="massProcessing">
                <template #header>
                    <div class="flex flex-wrap gap-2 items-end justify-between mb-6">
                        <div>
                            <h4 class="m-0">Horarios</h4>
                            <Button icon="pi pi-filter" rounded v-tooltip.top="'Mostrar mas filtros'"
                                @click="otherFilterDialog = true" disabled />
                            <Button type="button" icon="pi pi-filter-slash" rounded severity="secondary"
                                class="mt-5 ml-2 mr-2" v-tooltip.top="'Limpiar filtros'" @click="clearFilter()" />
                            <Tag v-if="selected.length > 0" severity="info"
                                :value="'Seleccionados: ' + selected.length"></Tag>
                        </div>
                        <div class="flex">
                            <IconField>
                                <InputIcon>
                                    <i class="pi pi-search" />
                                </InputIcon>
                                <InputText v-model="filters['global'].value" placeholder="Buscar..." />
                            </IconField>
                            <Button type="button" rounded class="ml-2" icon="pi pi-sliders-v" severity="secondary"
                                @click="toggleMostrarColumnas($event)" />
                            <Button icon="pi pi-lock" rounded v-tooltip.top="'Alternar columnas fijas'" class="ml-2"
                                severity="secondary" @click="toggleFijarColumnas($event)" />

                            <Popover ref="opMostrarColumnas">
                                <div class="flex flex-col gap-4">
                                    <div>
                                        <span class="font-medium block mb-2">Mostrar/Ocultar Columnas</span>
                                        <ul class="list-none p-0 m-0 flex flex-col">
                                            <li v-for="( value, key ) in showColumns" :key="key"
                                                class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                                                :binary="true">
                                                <Checkbox v-model="showColumns[key]" :inputId="key" :binary="true" />
                                                <label :for="key" class="font-medium text-base">
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
                                        <span class="font-medium block mb-2">Fijar Columnas</span>
                                        <ul class="list-none p-0 m-0 flex flex-col">
                                            <li v-for="(value, key ) in frozenColumns" :key="key"
                                                class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                                                :binary="true">
                                                <Checkbox v-model="frozenColumns[key]" :inputId="key" :binary="true" />
                                                <label :for="key" class="font-medium text-base">
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
                <Column selectionMode="multiple" style="width: 4rem" :exportable="false" />

                <!-- Acciones -->
                <Column header="Acciones" style="min-width: 14rem" :exportable="false" :frozen="frozenColumns.acciones"
                    :style="{
                        display: showColumns.acciones ? '' : 'none',
                    }">
                    <template #body="slotProps">
                        <Skeleton v-if="loading" />
                        <div v-else>
                            <Button icon="pi pi-pencil" class="mr-2" severity="warn" v-tooltip.top="'Editar'" rounded
                                :disabled="processingRows[slotProps.data.id]"
                                :loading="processingRows[slotProps.data.id]" @click="editItem(slotProps.data.id)" 
                                v-if="(can('schedules.edit'))" 
                                />
                            <Button icon="pi pi-trash" class="mr-2" severity="danger" v-tooltip.top="'Eliminar'" rounded
                                :disabled="processingRows[slotProps.data.id]"
                                :loading="processingRows[slotProps.data.id]" @click="deleteItem(slotProps.data)"
                                v-if="(can('schedules.delete'))"  
                                />
                            <Button icon="pi pi-eye" class="mr-2" severity="help" v-tooltip.top="'Ver detalles'" rounded
                                :disabled="processingRows[slotProps.data.id]"
                                :loading="processingRows[slotProps.data.id]" @click="viewItem(slotProps.data.id)" />
                        </div>
                    </template>
                </Column>

                <!-- id -->
                <Column field="id" header="Clave" sortable filter :frozen="frozenColumns.id" :style="{
                    display: showColumns.id ? '' : 'none',
                }" :showFilterMatchModes="false" :showFilterOperator="false">
                    <template #body="{ data }">
                        <Skeleton v-if="loading" />
                        <span v-else>{{ data.id }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText v-model="filterModel.value" placeholder="Buscar id" />
                    </template>
                </Column>
                
                <!-- Nombre -->
                <Column field="name" header="Nombre" sortable filter :frozen="frozenColumns.name" :style="{
                    display: showColumns.name ? '' : 'none',
                }" :showFilterMatchModes="false" :showFilterOperator="false">
                    <template #body="{ data }">
                        <Skeleton v-if="loading" />
                        <span v-else>{{ data.name }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText v-model="filterModel.value" placeholder="Buscar por nombre" />
                    </template>
                </Column>

                <!-- Hora de entrada -->
                <Column field="entry_time" header="Hora de entrada" sortable filter :frozen="frozenColumns.entry_time" :style="{
                    display: showColumns.entry_time ? '' : 'none',
                }" :showFilterMatchModes="false" :showFilterOperator="false">
                    <template #body="{ data }">
                        <Skeleton v-if="loading" />
                        <span v-else>{{ data.entry_time }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText v-model="filterModel.value" placeholder="Buscar por hora de entrada" />
                    </template>
                </Column>

                <!-- Hora de salida -->
                <Column field="leave_time" header="Hora de salida" sortable filter :frozen="frozenColumns.leave_time" :style="{
                    display: showColumns.leave_time ? '' : 'none',
                }" :showFilterMatchModes="false" :showFilterOperator="false">
                    <template #body="{ data }">
                        <Skeleton v-if="loading" />
                        <span v-else>{{ data.leave_time }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText v-model="filterModel.value" placeholder="Buscar por hora de salida" />
                    </template>
                </Column>

                <!-- Horas extra dobles -->
                <Column field="normal_double_overtime" header="Horas extra dobles" sortable filter :frozen="frozenColumns.normal_double_overtime" :style="{
                    display: showColumns.normal_double_overtime ? '' : 'none',
                }" :showFilterMatchModes="false" :showFilterOperator="false">
                    <template #body="{ data }">
                        <Skeleton v-if="loading" />
                        <span v-else>{{ data.normal_double_overtime }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText v-model="filterModel.value" placeholder="Buscar por horas extra dobles" />
                    </template>
                </Column>

                <!-- Horas extra triple -->
                <Column field="normal_triple_overtime" header="Horas extra triple" sortable filter :frozen="frozenColumns.normal_triple_overtime" :style="{
                    display: showColumns.normal_triple_overtime ? '' : 'none',
                }" :showFilterMatchModes="false" :showFilterOperator="false">
                    <template #body="{ data }">
                        <Skeleton v-if="loading" />
                        <span v-else>{{ data.normal_triple_overtime }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText v-model="filterModel.value" placeholder="Buscar por horas extra triple" />
                    </template>
                </Column>

            </DataTable>
            
        </div>
    </AppLayout>
</template>
