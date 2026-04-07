<script setup>
import { onMounted, ref } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { FilterMatchMode } from "@primevue/core/api";

// Props desde Laravel
// const props = defineProps({
//     logs: Object,
//     filters: Object,
// });

// Estado local
const processing = ref(false);
const globalFilters = ref({});

const logs = ref([{}]);

const initFilters = () => {
    globalFilters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    };
};

initFilters();

// Filtros
const filters = ref({
    action: null,
    table_name: null,
    search: null,
    user_id: null,
    relationship_id: null,
    date_from: new Date().toISOString().split("T")[0],
    date_to: new Date().toISOString().split("T")[0],
});

// Formatear fecha
const formatDate = (date) => {
    if (!date) return "";
    const d = new Date(date);
    return d.toLocaleString("es-ES", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

// Badge color por acción
const getActionSeverity = (action) => {
    const map = {
        INSERT: "success",
        UPDATE: "warning",
        DELETE: "danger",
        SELECT: "info",
    };
    return map[action] || "secondary";
};

// Filtrar
const applyFilters = async () => {
    processing.value = true;
    const response = await axios.get("/api/activity-logs", {
        params: {
            action: filters.value.action,
            table_name: filters.value.table_name,
            user_id: filters.value.user_id,
            relationship_id: filters.value.relationship_id,
            date_from: filters.value.date_from,
            date_to: filters.value.date_to,
        },
    });

    console.log(response.data);
    logs.value = response.data;
    otherFilterDialog.value = false;
    processing.value = false;
};

// Limpiar filtros
const clearFilters = async () => {
    filters.value = {
        action: "",
        table_name: "",
        search: "",
        user_id: "",
        relationship_id: "",
        date_from: new Date().toISOString().split("T")[0],
        date_to: new Date().toISOString().split("T")[0],
    };
    await getLogs();
};

// Ver detalle
const viewDetail = (log) => {
    router.visit(`/logs/${log.id}`);
};

// Obtener nombre de usuario
const getUserName = (log) => {
    return log.user_name || log.user_email || "Sistema";
};

const selected = ref([]);
const otherFilterDialog = ref(false);

const getLogs = async () => {
    processing.value = true;
    const response = await axios.get("/api/activity-logs", {
        params: {
            action: filters.value.action,
            table_name: filters.value.table_name,
            user_id: filters.value.user_id,
            relationship_id: filters.value.relationship_id,
            date_from: filters.value.date_from,
            date_to: filters.value.date_to,
        },
    });

    console.log(response.data);
    logs.value = response.data;
    processing.value = false;
};

onMounted(async () => {
    await getLogs();
});
</script>

<template>
    <AppLayout title="Registro de Actividades">
        <div class="p-4">
            <!-- 📋 Tabla de logs -->
            <Card>
                <template #content>
                    <DataTable
                        ref="dt"
                        v-model:selection="selected"
                        :value="logs"
                        dataKey="id"
                        :paginator="true"
                        :rows="10"
                        scrollable
                        reorderableColumns
                        scrollHeight="400px"
                        :filterDelay="500"
                        v-model:filters="globalFilters"
                        filterDisplay="menu"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} registros"
                    >
                        <template #header>
                            <div
                                class="flex flex-wrap gap-2 items-end justify-between mb-6"
                            >
                                <div>
                                    <h4 class="m-0">Logs</h4>
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
                                        @click="clearFilters"
                                    />
                                </div>
                                <div class="flex">
                                    <IconField>
                                        <InputIcon>
                                            <i class="pi pi-search" />
                                        </InputIcon>
                                        <InputText
                                            v-model="globalFilters.global.value"
                                            placeholder="Buscar..."
                                        />
                                    </IconField>
                                </div>
                            </div>
                        </template>
                        <Column
                            field="action"
                            header="Acción"
                            style="width: 100px"
                        >
                            <template #body="slotProps">
                                <Tag
                                    :value="slotProps.data.action"
                                    :severity="
                                        getActionSeverity(slotProps.data.action)
                                    "
                                    v-if="!processing"
                                />
                                <Skeleton v-else />
                            </template>
                        </Column>

                        <Column
                            field="table_name"
                            header="Tabla"
                            style="width: 200px"
                        >
                            <template #body="slotProps">
                                <code v-if="!processing" class="text-sm">{{
                                    slotProps.data.table_name
                                }}</code>
                                <Skeleton v-else />
                            </template>
                        </Column>

                        <Column
                            field="relationship_id"
                            header="ID Registro"
                            style="width: 120px"
                        >
                            <template #body="slotProps">
                                <span
                                    v-if="!processing"
                                    class="font-mono text-sm"
                                    >{{ slotProps.data.relationship_id }}</span
                                >
                                <Skeleton v-else />
                            </template>
                        </Column>

                        <Column
                            field="user_email"
                            header="Usuario"
                            style="width: 180px"
                        >
                            <template #body="slotProps">
                                <span v-if="!processing">{{
                                    getUserName(slotProps.data)
                                }}</span>
                                <Skeleton v-else />
                            </template>
                        </Column>

                        <Column
                            field="date"
                            header="Fecha"
                            style="width: 150px"
                        >
                            <template #body="slotProps">
                                <div class="flex flex-column">
                                    <span v-if="!processing">{{
                                        formatDate(slotProps.data.date)
                                    }}</span>
                                    <Skeleton v-else />
                                </div>
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
                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <label class="block font-medium mb-2"
                                    >Acción</label
                                >
                                <Dropdown
                                    v-model="filters.action"
                                    :options="[
                                        { label: 'Todas', value: '' },
                                        { label: 'INSERT', value: 'INSERT' },
                                        { label: 'UPDATE', value: 'UPDATE' },
                                        { label: 'DELETE', value: 'DELETE' },
                                        { label: 'APPROVE', value: 'APPROVE' },
                                        { label: 'REJECT', value: 'REJECT' },
                                    ]"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Seleccionar"
                                    class="w-full"
                                />
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <label class="block font-medium mb-2"
                                    >Tabla</label
                                >
                                <InputText
                                    v-model="filters.table_name"
                                    placeholder="Nombre de tabla..."
                                    class="w-full"
                                />
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <label class="block font-medium mb-2"
                                    >Buscar ID de registro</label
                                >
                                <InputText
                                    v-model="filters.relationship_id"
                                    placeholder="ID..."
                                    class="w-full"
                                />
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <label class="block font-medium mb-2"
                                    >Desde</label
                                >
                                <InputText
                                    v-model="filters.date_from"
                                    type="date"
                                    class="w-full"
                                />
                            </div>

                            <div class="w-full md:w-1/2 px-2 mb-4">
                                <label class="block font-medium mb-2"
                                    >Hasta</label
                                >
                                <InputText
                                    v-model="filters.date_to"
                                    type="date"
                                    class="w-full"
                                />
                            </div>
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
                    </Dialog>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
.border-primary {
    border-left: 4px solid var(--primary-color);
}
.border-warning {
    border-left: 4px solid var(--orange-500);
}
.border-success {
    border-left: 4px solid var(--green-500);
}
</style>
