<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToastService } from "@/Stores/toastService";
import { onMounted, ref, computed } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { useAuthz } from "@/composables/useAuthz";

const { can } = useAuthz();

const props = defineProps({
    Locations: Array,
});
// console.log(props.Locations);

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
        location_name: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        city_name: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        state_name: {
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
    location_name: true,
    state_name: true,
    city_name: true
});

const columnLabels = {
    acciones: "Acciones",
    id: "ID",
    location_name: "Colonia",
    state_name: "Estado",
    city_name: "Ciudad"
};

//Diálogo de filtros adicionales
const otherFilterDialog = ref(false);

//Columnas fijas
const frozenColumns = ref({
    acciones: true,
    id: false,
    location_name: false,
    state_name: false,
    city_name: false
});

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

const rows = computed(() => props.Locations);

//Cargar datos de la API al montar el componente
onMounted(async () => {
    loading.value = false;
});

const goToEdit = (id) => {
    if (processingRows.value[id]) return;

    processingRows.value[id] = true;

    router.get(`/catalogs/locations/${id}/edit`, {
        onFinish: () => {
            processingRows.value[id] = false;
        }
    });
};

const viewShiftRole = (id) => {
    if (processingRows.value[id]) return;

    processingRows.value[id] = true;

    router.get(`/catalogs/locations/${id}`, {
        onFinish: () => {
            processingRows.value[id] = false;
        }
    });
};

const processingRows = ref({});

const massProcessing = ref(false);

const creating = ref(false);

</script>

<template>
    <AppLayout :title="'Direcciones'">
        <ConfirmDialog />
        <div class="card">
            <Toolbar>
                <template #start>
                </template>

                <template #end>
                    <Link
                        href="/catalogs/locations/create"
                        :class="{ 'pointer-events-none opacity-70': creating }"
                        @click="creating = true"
                    >
                        <Button
                            v-if="(can('locations.create'))"
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
                :value="rows"
                dataKey="id"
                :paginator="true"
                :rows="10"
                scrollable
                scrollHeight="800px"
                v-model:filters="filters"
                filterDisplay="menu"
                exportFilename="prueba"
                :globalFilterFields="[ 'id', 'location_name', 'state_name', 'city_name' ]"
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
                            <h4 class="m-0">Direcciones</h4>
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

                <!-- acciones: "Acciones" -->
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
                                v-if="(can('locations.edit'))"
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

                <!-- id: "ID" -->
                <Column
                    field="id"
                    header="ID"
                    sortable
                    :frozen="frozenColumns.id"
                    :style="{
                        display: showColumns.id ? '' : 'none',
                    }"
                    :showFilterMatchModes="false" :showFilterOperator="false"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.id }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="ID"
                        />
                    </template>
                </Column>

                <!-- location_name: "Colonia" -->
                <Column
                    field="location_name"
                    header="Colonia"
                    sortable
                    :frozen="frozenColumns.location_name"
                    :style="{
                        display: showColumns.location_name ? '' : 'none',
                    }"
                    :showFilterMatchModes="false" :showFilterOperator="false"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.location_name }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Colonia"
                        /> 
                    </template>
                </Column>

                <!-- city_name: "Ciudad" -->
                <Column
                    field="city_name"
                    header="Ciudad"
                    sortable
                    :frozen="frozenColumns.city_name"
                    :style="{
                        display: showColumns.city_name ? '' : 'none',
                    }"
                    :showFilterMatchModes="false" :showFilterOperator="false"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.city_name }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Ciudad"
                        /> 
                    </template>
                </Column>
                
                <!-- state_name: "Estado" -->
                <Column
                    field="state_name"
                    header="Estado"
                    sortable
                    :frozen="frozenColumns.state_name"
                    :style="{
                        display: showColumns.state_name ? '' : 'none',
                    }"
                    :showFilterMatchModes="false" :showFilterOperator="false"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.state_name }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Estado"
                        /> 
                    </template>
                </Column>

            </DataTable>
        </div>
    </AppLayout>
</template>
