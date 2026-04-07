<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { onMounted, ref } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { router } from "@inertiajs/vue3";
import { useLayout } from "@/Layouts/composables/layout";
import { useToastService } from "@/Stores/toastService";
import { useAuthz } from "@/composables/useAuthz";

const props = defineProps({
    employees: {
        type: Array,
        required: true,
    },
});

const { can } = useAuthz();

const op = ref(null);
const opMostrarColumnas = ref(null);
const opFijarColumnas = ref(null);
const employeeIdFilter = ref(null);
const endDateFilter = ref(0);
const eliminated = ref(0);
const shiftRoleCycles = ref([{}]);
const selected = ref([]);
const dt = ref(null);
const loading = ref(false);
const { isDark } = useLayout();
const columnsDialog = ref(false);
const multipleDeleteDialog = ref(false);

const otherFilterDialog = ref(false);
const employeeFilter = ref(null);
const eliminatedFilter = ref(false);
const filters = ref({});

const { showSuccess, showError } = useToastService();

const getSafeBranchId = () => {
    try {
        const item = localStorage.getItem("selectedBranchOffice");
        if (!item) return null;
        const parsed = JSON.parse(item);
        return parsed || null;
    } catch (e) {
        console.warn("Error leyendo localStorage:", e);
        return null;
    }
};

const selectedBranchOffice = ref(getSafeBranchId());

const toggleAccionesMasivas = (event) => {
    op.value.toggle(event);
};

const toggleMostrarColumnas = (event) => {
    opMostrarColumnas.value.toggle(event);
};

const toggleFijarColumnas = (event) => {
    opFijarColumnas.value.toggle(event);
};

const otherFilters = ref([
    {
        employee_id: null,
        include_eliminated: null,
        end_date: null,
    },
]);

const showColumns = ref({
    acciones: true,
    num_empleado: true,
    nombre_empleado: true,
    fecha_inicio: true,
    fecha_fin: true,
    turno: true,
    horario: true,
});

const exportColumns = ref({
    num_empleado: true,
    nombre_empleado: true,
    fecha_inicio: true,
    fecha_fin: true,
    turno: true,
    horario: true,
});

const frozenColumns = ref({
    num_empleado: false,
    nombre_empleado: false,
    fecha_inicio: false,
    fecha_fin: false,
    turno: false,
    horario: false,
});

const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        employee_id: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        full_name: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        started_at: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        ends_at: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        shift_role_name: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        schedule_name: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
    };
};

const transformDate = (date) => {
    if (!date) return null; // Importante para cuando limpian el filtro
    const newDate = new Date(date);
    const year = newDate.getFullYear();
    const month = newDate.getMonth() + 1;
    const day = newDate.getDate();
    return `${year}-${month < 10 ? "0" + month : month}-${day < 10 ? "0" + day : day}`;
};

const fetchShiftRoleCycles = async () => {
    loading.value = true;
    try {
        const response = await axios.get("/api/shift-role-cycles", {
            params: {
                employee_id: employeeIdFilter.value,
                end_date: endDateFilter.value,
                eliminated: eliminated.value,
                branch_office: selectedBranchOffice.value?.id,
            },
        });
        console.log(response.data);
        shiftRoleCycles.value = response.data;
    } catch (error) {
        console.log(error);
    } finally {
        loading.value = false;
    }
};

const edit = (id) => {
    router.get(`/employee/shift-role-cycles/${id}/edit`);
};

const employeesByBranchOffice = ref(
    props.employees.filter(
        (employee) =>
            employee.branch_office_id === selectedBranchOffice.value?.id,
    ),
);

const deleteId = ref(null);
const deleteDialog = ref(false);

const saveColumns = () => {
    columnsDialog.value = false;
    dt.value.exportCSV();
};

const removeFilter = (filter) => {
    otherFilters.value[0][filter] = null;
    switch (filter) {
        case "employee_id":
            employeeFilter.value = null;
            break;
        case "end_date":
            endDateFilter.value = null;
            break;
        case "include_eliminated":
            eliminatedFilter.value = null;
            break;
    }
    applyFilters();
};

const applyFilters = async () => {
    loading.value = true;

    otherFilters.value[0].employee_id = employeeFilter.value;
    otherFilters.value[0].include_eliminated = eliminatedFilter.value;
    otherFilters.value[0].end_date = endDateFilter.value;
    try {
        const response = await axios.get("/api/shift-role-cycles", {
            params: {
                employee_id: employeeFilter.value,
                eliminated: eliminatedFilter.value,
                end_date: endDateFilter.value,
                branch_office: selectedBranchOffice.value?.id,
            },
        });
        console.log(response.data);
        shiftRoleCycles.value = response.data;
    } catch (error) {
        console.log(error);
    } finally {
        loading.value = false;
        otherFilterDialog.value = false;
    }
};

const deleteShiftRoleCycle = () => {
    loading.value = true;
    try {
        router.delete(`/employee/shift-role-cycles/${deleteId.value}`, {
            onSuccess: () => {
                showSuccess();
                applyFilters();
                deleteDialog.value = false;
            },
            onError: () => {
                showError();
                deleteDialog.value = false;
                loading.value = false;
            },
        });
    } catch (error) {
        console.log(error);
    }
};

const deleteMultipleShiftRoleCycles = () => {
    loading.value = true;
    try {
        router.post(
            `/employee/shift-role-cycles/delete-multiple`,
            {
                params: {
                    ids: selected.value.map((item) => item.id),
                },
            },
            {
                onSuccess: () => {
                    showSuccess();
                    applyFilters();
                    multipleDeleteDialog.value = false;
                    selected.value = [];
                },
                onError: () => {
                    showError();
                    multipleDeleteDialog.value = false;
                    loading.value = false;
                },
            },
        );
    } catch (error) {
        console.log(error);
    }
};

const clearFilters = () => {
    employeeFilter.value = null;
    eliminatedFilter.value = null;
    endDateFilter.value = null;
    otherFilters.value[0].employee_id = null;
    otherFilters.value[0].include_eliminated = null;
    otherFilters.value[0].end_date = null;
    initFilters();
    applyFilters();
};

initFilters();
onMounted(() => {
    fetchShiftRoleCycles();
});
</script>

<template>
    <AppLayout title="Ciclo de turno por empleado">
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
                        v-if="can('shift-role-cycles.export')"
                    />
                </template>
                <template #end>
                    <Button
                        type="button"
                        label="Acciones Masivas"
                        class="min-w-48"
                        icon="pi pi-wrench"
                        @click="toggleAccionesMasivas"
                        :disabled="selected.length === 0"
                        v-if="can('shift-role-cycles.multiple-delete')"
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
                                            type="button"
                                            icon="pi pi-trash"
                                            label="Eliminar seleccionados"
                                            severity="danger"
                                            text
                                            class="mt-2 ml-2"
                                            @click="multipleDeleteDialog = true"
                                            v-if="
                                                can(
                                                    'shift-role-cycles.multiple-delete',
                                                )
                                            "
                                        />
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </Popover>

                    <Link :href="route('shift-role-cycles.create')">
                        <Button
                            label="Crear"
                            icon="pi pi-plus-circle"
                            severity="success"
                            class="ml-2"
                            v-if="can('shift-role-cycles.create')"
                        />
                    </Link>
                </template>
            </Toolbar>
            <DataTable
                ref="dt"
                v-model:selection="selected"
                :value="shiftRoleCycles"
                dataKey="id"
                :paginator="true"
                :rows="10"
                scrollable
                reorderableColumns
                scrollHeight="400px"
                :filterDelay="500"
                v-model:filters="filters"
                exportFilename="ciclos-de-turno"
                filterDisplay="menu"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} ciclos de turno"
            >
                <template #header>
                    <div
                        class="flex flex-wrap gap-2 items-end justify-between mb-6"
                    >
                        <div>
                            <h4 class="m-0">Ciclo de turno por empleado</h4>
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
                                @click="clearFilters()"
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
                    <div class="flex gap-1">
                        <div class="mb-2">
                            <Chip
                                :label="
                                    'Empleado: ' + otherFilters[0].employee_id
                                "
                                v-if="otherFilters[0].employee_id != null"
                                removable
                                @remove="removeFilter('employee_id')"
                                :removable="!loading"
                            />
                        </div>
                        <div class="mb-2">
                            <Chip
                                :label="`Incluir eliminados: ${
                                    otherFilters[0].include_eliminated
                                        ? 'Si'
                                        : 'No'
                                }`"
                                v-if="otherFilters[0].include_eliminated"
                                removable
                                @remove="removeFilter('include_eliminated')"
                                :removable="!loading"
                            />
                        </div>
                        <div class="mb-2">
                            <Chip
                                :label="`Fecha fin: ${
                                    otherFilters[0].end_date ? 'Si' : 'No'
                                }`"
                                v-if="otherFilters[0].end_date"
                                removable
                                @remove="removeFilter('end_date')"
                                :removable="!loading"
                            />
                        </div>
                    </div>
                </template>

                <Column
                    selectionMode="multiple"
                    style="width: 1rem"
                    :exportable="false"
                    columnKey="selection"
                ></Column>
                <Column
                    :exportable="false"
                    columnKey="acciones"
                    :style="{
                        width: '1rem',
                        display: showColumns.acciones ? '' : 'none',
                        minWidth: '1rem',
                    }"
                    header="Acciones"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <div v-else class="flex gap-2">
                            <Button
                                icon="pi pi-pencil"
                                rounded
                                v-tooltip.top="'Editar'"
                                severity="warn"
                                @click="edit(data.id)"
                                v-if="can('shift-role-cycles.edit')"
                            />
                            <Button
                                icon="pi pi-trash"
                                rounded
                                v-tooltip.top="'Eliminar'"
                                severity="danger"
                                @click="
                                    deleteDialog = true;
                                    deleteId = data.id;
                                "
                                v-if="can('shift-role-cycles.delete')"
                            />
                        </div>
                    </template>
                </Column>
                <Column
                    field="employee_id"
                    header="Clave Empleado"
                    :filter="true"
                    columnKey="employee_id"
                    :frozen="frozenColumns.num_empleado"
                    :style="{
                        width: '2rem',
                        display: showColumns.num_empleado ? '' : 'none',
                    }"
                    :exportable="exportColumns.num_empleado"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.employee_id }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Clave Empleado"
                        />
                    </template>
                </Column>
                <Column
                    field="full_name"
                    header="Nombre Empleado"
                    :filter="true"
                    columnKey="full_name"
                    :frozen="frozenColumns.nombre_empleado"
                    :style="{
                        width: '20rem',
                        display: showColumns.nombre_empleado ? '' : 'none',
                    }"
                    :exportable="exportColumns.nombre_empleado"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.full_name }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Nombre Empleado"
                        />
                    </template>
                </Column>
                <Column
                    field="started_at"
                    header="Fecha Inicio"
                    :filter="true"
                    columnKey="started_at"
                    :frozen="frozenColumns.fecha_inicio"
                    :style="{
                        width: '5rem',
                        display: showColumns.fecha_inicio ? '' : 'none',
                    }"
                    :exportable="exportColumns.fecha_inicio"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ transformDate(data.started_at) }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <DatePicker
                            v-model="filterModel.value"
                            type="date"
                            placeholder="Buscar fecha"
                            class="border-none w-full"
                            @update:modelValue="
                                (val) =>
                                    (filterModel.value = transformDate(val))
                            "
                        />
                    </template>
                </Column>
                <Column
                    field="ends_at"
                    header="Fecha Fin"
                    :filter="true"
                    columnKey="ends_at"
                    :frozen="frozenColumns.fecha_fin"
                    :style="{
                        width: '5rem',
                        display: showColumns.fecha_fin ? '' : 'none',
                    }"
                    :exportable="exportColumns.fecha_fin"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ transformDate(data.ends_at) }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <DatePicker
                            v-model="filterModel.value"
                            type="date"
                            placeholder="Buscar fecha"
                            class="border-none w-full"
                            @update:modelValue="
                                (val) =>
                                    (filterModel.value = transformDate(val))
                            "
                        />
                    </template>
                </Column>

                <Column
                    field="shift_role_name"
                    header="Rol"
                    :filter="true"
                    columnKey="shift_role_name"
                    :frozen="frozenColumns.turno"
                    :style="{
                        width: '10rem',
                        display: showColumns.turno ? '' : 'none',
                    }"
                    :exportable="exportColumns.turno"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.shift_role_name }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Rol"
                        />
                    </template>
                </Column>

                <Column
                    field="schedule_name"
                    header="Horario"
                    :filter="true"
                    columnKey="schedule_name"
                    :frozen="frozenColumns.horario"
                    :style="{
                        width: '5rem',
                        display: showColumns.horario ? '' : 'none',
                    }"
                    :exportable="exportColumns.horario"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.schedule_name }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <Select
                            v-model="filterModel.value"
                            optionLabel="name"
                            optionValue="name"
                            placeholder="Selecciona un horario"
                            :filter="true"
                            filterBy="name"
                            showClear
                        />
                    </template>
                </Column>
            </DataTable>
            <Dialog
                v-model:visible="deleteDialog"
                :style="{ width: '550px' }"
                header="Confirmar eliminación"
                :modal="true"
            >
                <div
                    :class="{
                        'bg-red-50 border-l-4 border-red-500 p-4 rounded':
                            !isDark,
                        'bg-red-950 border-l-4 border-red-500 p-4 rounded':
                            isDark,
                    }"
                >
                    <div class="flex items-center">
                        <i
                            class="pi pi-exclamation-triangle text-red-600 text-3xl mr-3"
                        ></i>
                        <div>
                            <h3
                                :class="{
                                    'font-bold text-red-800': !isDark,
                                    'font-bold text-red-200': isDark,
                                }"
                            >
                                ¿Estás seguro de eliminar este ciclo?
                            </h3>
                            <p
                                :class="{
                                    'text-sm text-red-700': !isDark,
                                    'text-sm text-red-300': isDark,
                                }"
                            >
                                Esta acción eliminará el ciclo y no podrá ser
                                deshecha.
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
                        @click="deleteShiftRoleCycle"
                        severity="danger"
                        :loading="loading"
                    />
                </template>
            </Dialog>
            <Dialog
                v-model:visible="multipleDeleteDialog"
                :style="{ width: '550px' }"
                header="Confirmar eliminación"
                :modal="true"
            >
                <div
                    :class="{
                        'bg-red-50 border-l-4 border-red-500 p-4 rounded':
                            !isDark,
                        'bg-red-950 border-l-4 border-red-500 p-4 rounded':
                            isDark,
                    }"
                >
                    <div class="flex items-center">
                        <i
                            class="pi pi-exclamation-triangle text-red-600 text-3xl mr-3"
                        ></i>
                        <div>
                            <h3
                                :class="{
                                    'font-bold text-red-800': !isDark,
                                    'font-bold text-red-200': isDark,
                                }"
                            >
                                ¿Estás seguro de eliminar estos ciclos?
                            </h3>
                            <p
                                :class="{
                                    'text-sm text-red-700': !isDark,
                                    'text-sm text-red-300': isDark,
                                }"
                            >
                                Esta acción eliminará los ciclos y no podrá ser
                                deshecha.
                            </p>
                        </div>
                    </div>
                </div>
                <template #footer>
                    <Button
                        label="No"
                        icon="pi pi-times"
                        text
                        @click="multipleDeleteDialog = false"
                        severity="secondary"
                        variant="text"
                        :loading="loading"
                    />
                    <Button
                        label="Si"
                        icon="pi pi-check"
                        @click="deleteMultipleShiftRoleCycles"
                        severity="danger"
                        :loading="loading"
                    />
                </template>
            </Dialog>
            <Dialog
                v-model:visible="otherFilterDialog"
                :style="{ width: '450px' }"
                header="Seleccionar filtros adicionales"
                :modal="true"
            >
                <div class="flex flex-wrap -mx-2">
                    <!-- Empleado (buscable)-->
                    <div class="w-full px-2 mb-4">
                        <label class="block mb-2 font-medium">Empleado</label>
                        <Select
                            v-model="employeeFilter"
                            :options="employeesByBranchOffice"
                            optionValue="id"
                            optionLabel="full_name"
                            filter
                            :filterFields="['id', 'full_name']"
                            showClear
                            placeholder="Selecciona un empleado"
                            class="w-full"
                        >
                            <template #option="{ option }">
                                ({{ option.id }}) {{ option.full_name }}
                            </template>
                        </Select>
                    </div>

                    <div class="w-full px-2 mb-4">
                        <label class="block mb-2 font-medium">Fecha fin</label>
                        <Select
                            v-model="endDateFilter"
                            :options="[
                                { label: 'Si', value: 'si' },
                                { label: 'No', value: 'no' },
                            ]"
                            optionValue="value"
                            optionLabel="label"
                            showClear
                            placeholder="¿Tiene fecha fin?"
                            class="w-full"
                        >
                        </Select>
                    </div>

                    <!-- Ver registros eliminados -->
                    <!-- <div class="w-full md:w-1/2 px-2 mb-4 flex items-end">
                        <div class="flex items-center gap-2">
                            <Checkbox
                                v-model="eliminatedFilter"
                                :binary="true"
                                inputId="show_deleted"
                            />
                            <label for="show_deleted" class="font-medium"
                                >Ver registros eliminados</label
                            >
                        </div>
                    </div> -->
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
                        :loading="loading"
                    />
                </template>
            </Dialog>
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
        </div>
    </AppLayout>
</template>
