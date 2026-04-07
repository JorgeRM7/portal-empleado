<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { onMounted, ref } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { router } from "@inertiajs/vue3";
import { useLayout } from "@/Layouts/composables/layout";
import ConfirmationDialog from "@/Components/ConfirmationDialog.vue";
import { useAuthz } from "@/composables/useAuthz";

const props = defineProps({
    employees: Array,
    branch_offices: Array,
});

const { can } = useAuthz();

const columnsDialog = ref(false);
const selected = ref([]);
const op = ref(null);
const opMostrarColumnas = ref(null);
const opFijarColumnas = ref(null);
const loading = ref(false);
const employeeShiftRoles = ref([{}]);
const otherFilterDialog = ref(false);
const deleteDialog = ref(false);
const multipleDeleteDialog = ref(false);
const deleteId = ref(null);

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

const branchOfficeFilter = ref(selectedBranchOffice.value?.id);
const eliminatedFilter = ref(null);
const employeeFilter = ref(null);
const statusFilter = ref("all");
const dt = ref(null);

const transformDate = (date) => {
    if (!date) return null;
    const newDate = new Date(date);
    const year = newDate.getUTCFullYear();
    const month = newDate.getUTCMonth() + 1;
    const day = newDate.getUTCDate();

    return `${year}-${month < 10 ? "0" + month : month}-${day < 10 ? "0" + day : day}`;
};

const employeesByBranchOffice = ref(
    props.employees.filter(
        (employee) =>
            employee.branch_office_id === selectedBranchOffice.value?.id,
    ),
);

const filters = ref({});

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
        shift_role: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        next_shift_role: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        start_date: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        end_date: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        status: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
    };
};

const showColumns = ref({
    acciones: true,
    num_empleado: true,
    nombre_empleado: true,
    fecha_inicio: true,
    fecha_fin: true,
    rol_turno: true,
    turno_siguiente: true,
    status: true,
});

const exportColumns = ref({
    num_empleado: true,
    nombre_empleado: true,
    fecha_inicio: true,
    fecha_fin: true,
    rol_turno: true,
    turno_siguiente: true,
    status: true,
});

const frozenColumns = ref({
    num_empleado: false,
    nombre_empleado: false,
    fecha_inicio: false,
    fecha_fin: false,
    rol_turno: false,
    turno_siguiente: false,
    status: false,
});

const otherFilters = ref([
    {
        employee_id: null,
        include_eliminated: null,
        branch_office: selectedBranchOffice.value?.code,
        status: "all",
    },
]);

const getEmployeeShiftRoles = async () => {
    loading.value = true;
    try {
        const response = await axios.get("/api/employee-shift-roles", {
            params: {
                include_eliminated: false,
                branch_office: selectedBranchOffice.value?.id,
            },
        });
        employeeShiftRoles.value = response.data;
    } catch (error) {
        console.log(error);
    } finally {
        loading.value = false;
    }
};

const applyFilters = async () => {
    loading.value = true;
    const selectedBranchOffice = props.branch_offices.find(
        (branchOffice) => branchOffice.id === branchOfficeFilter.value,
    );
    const employeeName = employeesByBranchOffice.value.find(
        (employee) => employee.id === employeeFilter.value,
    );
    otherFilters.value[0].employee_id = employeeName?.full_name;
    otherFilters.value[0].include_eliminated = eliminatedFilter.value;
    otherFilters.value[0].branch_office = selectedBranchOffice?.code;
    otherFilters.value[0].status = statusFilter.value;
    try {
        const response = await axios.get("/api/employee-shift-roles", {
            params: {
                include_eliminated: eliminatedFilter.value,
                branch_office: branchOfficeFilter.value,
                status: statusFilter.value,
                employee_id: employeeFilter.value,
            },
        });
        employeeShiftRoles.value = response.data;
    } catch (error) {
        console.log(error);
    } finally {
        loading.value = false;
        otherFilterDialog.value = false;
    }
};

const removeFilter = (filter) => {
    otherFilters.value[0][filter] = null;
    switch (filter) {
        case "employee_id":
            employeeFilter.value = null;
            break;
        case "include_eliminated":
            eliminatedFilter.value = null;
            break;
        case "branch_office":
            branchOfficeFilter.value = null;
            break;
        case "status":
            statusFilter.value = null;
            break;
    }
    applyFilters();
};

const statusLabel = (status) => {
    switch (status) {
        case "active":
            return "Activo";
        case "inactive":
            return "Inactivo";
        default:
            return "Todos";
    }
};

const edit = (shiftRole) => {
    router.get(`/employee/employee-shift-roles/${shiftRole}/edit`);
};

const deleteRole = () => {
    loading.value = true;
    router.delete(`/employee/employee-shift-roles/${deleteId.value}`, {
        onSuccess: () => {
            deleteDialog.value = false;
            applyFilters();
        },
        onError: () => {
            deleteDialog.value = false;
            loading.value = false;
        },
    });
};

const saveColumns = () => {
    columnsDialog.value = false;
    dt.value.exportCSV();
};

const deleteMultipleRoles = () => {
    loading.value = true;
    router.post(
        `/employee/employee-shift-roles/delete-multiple`,
        {
            ids: selected.value.map((item) => item.id),
        },
        {
            onSuccess: () => {
                applyFilters();
                loading.value = false;
            },
            onError: () => {
                loading.value = false;
            },
        },
    );
};

initFilters();

onMounted(() => {
    getEmployeeShiftRoles();
});
</script>

<template>
    <AppLayout title="Rol de turno">
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
                        v-if="can('employee-shift-roles.export')"
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
                        v-if="can('employee-shift-roles.multiple-delete')"
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
                                            v-if="
                                                can(
                                                    'employee-shift-roles.multiple-delete',
                                                )
                                            "
                                            class="mt-2 ml-2"
                                            @click="multipleDeleteDialog = true"
                                        />
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </Popover>

                    <Link
                        v-if="can('employee-shift-roles.create')"
                        :href="route('employee-shift-roles.create')"
                    >
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
                :value="employeeShiftRoles"
                dataKey="id"
                :paginator="true"
                :rows="10"
                scrollable
                reorderableColumns
                scrollHeight="400px"
                :filterDelay="500"
                v-model:filters="filters"
                exportFilename="roles-de-turno"
                filterDisplay="menu"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} roles de turno"
            >
                <template #header>
                    <div
                        class="flex flex-wrap gap-2 items-end justify-between mb-6"
                    >
                        <div>
                            <h4 class="m-0">Rol de turno por empleado</h4>
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
                                    'Planta: ' + otherFilters[0].branch_office
                                "
                                v-if="otherFilters[0].branch_office != null"
                                removable
                                @remove="removeFilter('branch_office')"
                                :removable="!loading"
                            />
                        </div>
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
                                :label="`Estatus: ${statusLabel(
                                    otherFilters[0].status,
                                )}`"
                                v-if="otherFilters[0].status"
                                removable
                                @remove="removeFilter('status')"
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
                                v-if="can('employee-shift-roles.edit')"
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
                                v-if="can('employee-shift-roles.delete')"
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
                    field="shift_role_name"
                    header="Rol de turno"
                    :filter="true"
                    columnKey="shift_role_name"
                    :frozen="frozenColumns.rol_turno"
                    :style="{
                        width: '10rem',
                        display: showColumns.rol_turno ? '' : 'none',
                    }"
                    :exportable="exportColumns.rol_turno"
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
                    field="next_shift_role_name"
                    header="Rol de turno siguiente"
                    :filter="true"
                    columnKey="next_shift_role_name"
                    :frozen="frozenColumns.turno_siguiente"
                    :style="{
                        width: '5rem',
                        display: showColumns.turno_siguiente ? '' : 'none',
                    }"
                    :exportable="exportColumns.turno_siguiente"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.next_shift_role_name }}</span>
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
                    field="start_date"
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
                        <span v-else>{{ transformDate(data.start_date) }}</span>
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
                    field="end_date"
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
                        <span v-else>{{ transformDate(data.end_date) }}</span>
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
                    field="status"
                    header="Estado"
                    :filter="true"
                    columnKey="status"
                    :frozen="frozenColumns.status"
                    :style="{
                        width: '5rem',
                        display: showColumns.status ? '' : 'none',
                    }"
                    :exportable="exportColumns.status"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>
                            <Tag
                                :value="data.active ? 'Activo' : 'Inactivo'"
                                :severity="data.active ? 'success' : 'danger'"
                            />
                        </span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Estado"
                        />
                    </template>
                </Column>
            </DataTable>
            <Dialog
                v-model:visible="otherFilterDialog"
                :style="{ width: '450px' }"
                header="Seleccionar filtros adicionales"
                :modal="true"
            >
                <div class="flex flex-wrap -mx-2">
                    <div class="w-1/2 px-2 mb-4">
                        <label class="block mb-2 font-medium">Planta</label>
                        <Select
                            v-model="branchOfficeFilter"
                            :options="props.branch_offices"
                            optionValue="id"
                            optionLabel="code"
                            showClear
                            placeholder="Selecciona una planta"
                            class="w-full"
                        >
                        </Select>
                    </div>
                    <div class="w-1/2 px-2 mb-4">
                        <label class="block mb-2 font-medium">Estatus</label>
                        <Select
                            v-model="statusFilter"
                            :options="[
                                { label: 'Todos', value: 'all' },
                                { label: 'Activo', value: 'active' },
                                { label: 'Inactivo', value: 'inactive' },
                            ]"
                            optionValue="value"
                            optionLabel="label"
                            showClear
                            placeholder="Selecciona un estatus"
                            class="w-full"
                        >
                        </Select>
                    </div>
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
            <ConfirmationDialog
                v-model:visible="deleteDialog"
                header="¿Estas seguro de eliminar este rol de turno?"
                :loading="loading"
                @confirm="deleteRole"
                @cancel="deleteDialog = false"
                confirmOrDelete="delete"
            />
            <ConfirmationDialog
                v-model:visible="multipleDeleteDialog"
                header="¿Estas seguro de eliminar estos roles de turno?"
                :loading="loading"
                @confirm="deleteMultipleRoles"
                @cancel="multipleDeleteDialog = false"
                confirmOrDelete="delete"
            />
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
