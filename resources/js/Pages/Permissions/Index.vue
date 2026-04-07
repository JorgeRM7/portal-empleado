<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { onMounted, ref } from "vue";
import axios from "axios";

const selected = ref([]);
const op = ref(null);
const opMostrarColumnas = ref(null);
const opFijarColumnas = ref(null);
const filters = ref({});
const viewSelected = ref(null);
const permissions = ref([]);
const allPermissions = ref([]);
const loading = ref(false);

const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        id: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        guard_name: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        name: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        created_at: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
    };
};

const frozenColumns = ref({
    id: false,
    guard_name: false,
    name: false,
});

const showColumns = ref({
    id: true,
    guard_name: true,
    name: true,
});

const exportColumns = ref({
    id: true,
    guard_name: true,
    name: true,
});

const otherFilters = ref([
    {
        startDate: null,
        endDate: null,
    },
]);

initFilters();

const toggleMostrarColumnas = (event) => {
    opMostrarColumnas.value.toggle(event);
};

const toggleFijarColumnas = (event) => {
    opFijarColumnas.value.toggle(event);
};

const changeView = () => {
    const view = allPermissions.value.find((v) => v.id === viewSelected.value);
    permissions.value = view ? view.permissions : [];
};

const clearFilter = () => {
    initFilters();
};

onMounted(async () => {
    loading.value = true;
    const response = await axios.get("/api/permissions-views");
    console.log(response.data);
    allPermissions.value = response.data;
    loading.value = false;
});
</script>

<template>
    <AppLayout title="Permisos">
        <div class="card">
            <Toolbar>
                <template #end>
                    <Link
                        :href="
                            route('permissions.create', { view: viewSelected })
                        "
                    >
                        <Button
                            label="Editar permisos"
                            icon="pi pi-pencil"
                            severity="success"
                            class="ml-2"
                            :disabled="viewSelected == null"
                        />
                    </Link>
                </template>
            </Toolbar>
            <DataTable
                ref="dt"
                v-model:selection="selected"
                :value="permissions"
                dataKey="id"
                :paginator="true"
                :rows="10"
                scrollable
                scrollHeight="400px"
                :filterDelay="500"
                :filters="filters"
                v-model:filters="filters"
                filterDisplay="menu"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} vistas"
            >
                <template #header>
                    <div
                        class="flex flex-wrap gap-2 items-end justify-between mb-6"
                    >
                        <div>
                            <h4 class="m-0">Permisos</h4>

                            <Button
                                type="button"
                                icon="pi pi-filter-slash"
                                rounded
                                severity="secondary"
                                class="mt-5 mr-2"
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
                                    v-model="filters.global.value"
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
                            :label="
                                'Fecha de inicio: ' + otherFilters[0].startDate
                            "
                            removable
                            v-if="otherFilters[0].startDate != null"
                            @remove="removeStartDate"
                        />
                    </div>
                    <Select
                        v-model="viewSelected"
                        :options="allPermissions"
                        optionLabel="name"
                        optionValue="id"
                        class="w-full"
                        :placeholder="
                            loading ? 'Cargando...' : 'Seleccionar vista'
                        "
                        @change="changeView()"
                        :loading="loading"
                        :disabled="loading"
                        :filter="true"
                    >
                    </Select>
                </template>
                <template #empty>
                    Selecciona una vista para ver los permisos
                </template>

                <Column
                    selectionMode="multiple"
                    style="width: 1rem"
                    :exportable="false"
                ></Column>
                <Column
                    field="id"
                    header="ID"
                    :filter="true"
                    :frozen="frozenColumns.id"
                    :style="{
                        width: '5rem',
                        display: showColumns.id ? '' : 'none',
                    }"
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
                    field="name"
                    header="Nombre"
                    :filter="true"
                    :frozen="frozenColumns.name"
                    :style="{
                        width: '15rem',
                        display: showColumns.name ? '' : 'none',
                    }"
                    :exportable="exportColumns.name"
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
                    field="guard_name"
                    header="Guard"
                    :frozen="frozenColumns.guard_name"
                    :style="{
                        width: '5rem',
                        display: showColumns.guard_name ? '' : 'none',
                    }"
                    :exportable="exportColumns.guard_name"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.guard_name }}</span>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>
