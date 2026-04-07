<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { router } from "@inertiajs/vue3";
import { useToastService } from "@/Stores/toastService";

const props = defineProps({
    payrollTypes: Array,
});

const { showSuccess, showError } = useToastService();

const op = ref(null);
const selected = ref([]);
const loading = ref(false);
const filters = ref({});
const deleteDialog = ref(false);
const deleteMultipleDialog = ref(false);
const payrollTypeToDelete = ref(null);

const exportColumns = ref({
    name: true,
    id: true,
});

const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        id: {
            operator: FilterOperator.OR,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        name: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        active: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }],
        },
    };
};

initFilters();

const clearFilter = () => {
    initFilters();
};

const setPayrollTypeToDelete = (payrollType) => {
    payrollTypeToDelete.value = payrollType;
    deleteDialog.value = true;
};

const deleteMultiple = () => {
    loading.value = true;
    router.post(
        route("payroll-types.delete-multiple"),
        {
            ids: selected.value.map((payrollType) => payrollType.id),
        },
        {
            onSuccess: () => {
                loading.value = false;
                deleteMultipleDialog.value = false;
                showSuccess();
            },
            onError: () => {
                loading.value = false;
                showError();
            },
        },
    );
};

const deletePayrollType = () => {
    loading.value = true;
    router.delete(
        route("payroll-types.destroy", payrollTypeToDelete.value.id),
        {
            onSuccess: () => {
                loading.value = false;
                deleteDialog.value = false;
                showSuccess();
            },
            onError: () => {
                loading.value = false;
                showError();
            },
        },
    );
};

const toggleAccionesMasivas = (event) => {
    op.value.toggle(event);
};

console.log(props.payrollTypes);
</script>

<template>
    <AppLayout :title="'Tipos de Asiento'">
        <div class="card">
            <Toolbar>
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
                                            type="button"
                                            icon="pi pi-trash"
                                            label="Eliminar seleccionados"
                                            severity="danger"
                                            text
                                            class="mt-2 ml-2"
                                            @click="deleteMultipleDialog = true"
                                        />
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </Popover>
                    <Button
                        label="Crear"
                        icon="pi pi-plus-circle"
                        severity="success"
                        class="ml-2"
                        @click="router.visit(route('payroll-types.create'))"
                    />
                </template>
            </Toolbar>
            <DataTable
                ref="dt"
                v-model:selection="selected"
                :value="props.payrollTypes"
                dataKey="id"
                :paginator="true"
                :rows="10"
                scrollable
                scrollHeight="400px"
                :filterDelay="500"
                v-model:filters="filters"
                :globalFilterFields="['id', 'name']"
                filterDisplay="menu"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} tipos de asiento"
            >
                <template #header>
                    <div
                        class="flex flex-wrap gap-2 items-end justify-between mb-6"
                    >
                        <div>
                            <h4 class="m-0">Tipos de Asiento</h4>

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
                        </div>
                    </div>
                </template>
                <Column
                    selectionMode="multiple"
                    style="width: 1rem"
                    :exportable="false"
                ></Column>
                <Column
                    :exportable="false"
                    :style="{
                        width: '5rem',
                    }"
                    header="Acciones"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <div v-else>
                            <Button
                                icon="pi pi-pencil"
                                class="mr-2"
                                rounded
                                v-tooltip.top="'Editar'"
                                severity="warn"
                                @click="
                                    () => {
                                        router.visit(
                                            route(
                                                'payroll-types.edit',
                                                data.id,
                                            ),
                                        );
                                    }
                                "
                            />
                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                v-tooltip.top="'Eliminar'"
                                class="mr-2"
                                rounded
                                @click="
                                    () => {
                                        setPayrollTypeToDelete(data);
                                    }
                                "
                            />
                        </div>
                    </template>
                </Column>
                <Column
                    field="id"
                    header="ID"
                    :filter="true"
                    :style="{
                        width: '5rem',
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
                    :style="{
                        width: '5rem',
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
                            placeholder="Buscar por nombre"
                        />
                    </template>
                </Column>
                <Column
                    field="active"
                    header="Activo"
                    :filter="true"
                    :style="{
                        width: '5rem',
                    }"
                    :exportable="exportColumns.active"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <Badge
                            v-else
                            :value="data.active ? 'Sí' : 'No'"
                            :severity="data.active ? 'success' : 'danger'"
                        />
                    </template>
                    <template #filter="{ filterModel }">
                        <Dropdown
                            v-model="filterModel.value"
                            :options="[
                                { label: 'Sí', value: true },
                                { label: 'No', value: false },
                            ]"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Todos"
                            showClear
                            class="w-full"
                        />
                    </template>
                </Column>
            </DataTable>
        </div>
        <Dialog
            v-model:visible="deleteDialog"
            :style="{ width: '450px' }"
            header="Confirmar eliminación"
            :modal="true"
        >
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span v-if="payrollTypeToDelete.id"
                    >Estas seguro de eliminar el tipo de asiento con el id
                    <b>{{ payrollTypeToDelete.id }}</b
                    >?</span
                >
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
                    @click="deletePayrollType"
                    severity="danger"
                    :loading="loading"
                />
            </template>
        </Dialog>
        <Dialog
            v-model:visible="deleteMultipleDialog"
            :style="{ width: '450px' }"
            header="Confirmar eliminación múltiple"
            :modal="true"
        >
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span
                    >Estas seguro de eliminar los tipos de asiento
                    seleccionados?</span
                >
            </div>
            <template #footer>
                <Button
                    label="No"
                    icon="pi pi-times"
                    text
                    @click="deleteMultipleDialog = false"
                    severity="secondary"
                    variant="text"
                    :loading="loading"
                />
                <Button
                    label="Si"
                    icon="pi pi-check"
                    @click="deleteMultiple"
                    severity="danger"
                    :loading="loading"
                />
            </template>
        </Dialog>
    </AppLayout>
</template>
