<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToast } from "primevue/usetoast";
import ConfirmationDialog from "@/Components/ConfirmationDialog.vue";

const props = defineProps({
    logs: Object,
    totalLogs: Number,
    logSize: Number,
    error: String,
    filters: Object,
    flash: Object,
});

const toast = useToast();
const processing = ref(false);
const clearDialog = ref(false);

const filters = ref({
    level: props.filters?.level || "",
    search: props.filters?.search || "",
    date_from: props.filters?.date_from || "",
    date_to: props.filters?.date_to || "",
});

const formatSize = (bytes) => {
    if (!bytes || bytes === 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + " " + sizes[i];
};

const formatDate = (date) => {
    if (!date) return "";
    const d = new Date(date);
    return d.toLocaleString("es-ES", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
    });
};

const getLevelSeverity = (level) => {
    const map = {
        ERROR: "danger",
        CRITICAL: "danger",
        ALERT: "danger",
        EMERGENCY: "danger",
        WARNING: "warning",
        NOTICE: "info",
        INFO: "info",
        DEBUG: "secondary",
    };
    return map[level?.toUpperCase()] || "secondary";
};

const getLevelIcon = (level) => {
    const map = {
        ERROR: "pi pi-times-circle",
        CRITICAL: "pi pi-exclamation-triangle",
        WARNING: "pi pi-exclamation-circle",
        INFO: "pi pi-info-circle",
        DEBUG: "pi pi-code",
    };
    return map[level?.toUpperCase()] || "pi-file";
};

const applyFilters = () => {
    processing.value = true;
    router.get("/laravel-logs", filters.value, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => (processing.value = false),
    });
};

const clearFilters = () => {
    filters.value = {
        level: "",
        search: "",
        date_from: "",
        date_to: "",
    };
    router.get(
        "/laravel-logs",
        {},
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const viewDetail = (index) => {
    router.visit(`/laravel-logs/${index}`);
};

const clearLogs = () => {
    processing.value = true;
    router.post(
        "/laravel-logs/clear",
        {},
        {
            onFinish: () => {
                processing.value = false;
                clearDialog.value = false;
            },
        },
    );
};

const downloadLogs = () => {
    window.open("/api/laravel-logs/download", "_blank");
};

const goToPage = (page) => {
    if (page < 1 || page > props.logs.last_page) return;

    processing.value = true;
    router.get(
        "/laravel-logs",
        { ...filters.value, page },
        {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => (processing.value = false),
        },
    );
};

if (props.flash?.success) {
    toast.add({
        severity: "success",
        summary: "Éxito",
        detail: props.flash.success,
        life: 5000,
    });
}

if (props.flash?.error) {
    toast.add({
        severity: "error",
        summary: "Error",
        detail: props.flash.error,
        life: 5000,
    });
}

if (props.error) {
    toast.add({
        severity: "error",
        summary: "Error",
        detail: props.error,
        life: 8000,
    });
}
</script>

<template>
    <AppLayout title="Laravel Logs">
        <div class="card flex">
            <h2 class="text-2xl font-semibold text-gray-900 m-0">
                Laravel Logs
            </h2>
        </div>

        <div>
            <!-- Error Alert -->
            <Alert v-if="error" severity="error" :closable="false" class="mb-4">
                {{ error }}
            </Alert>

            <!-- Stats Cards -->
            <div class="flex justify-between mb-4">
                <div class="col-3">
                    <Card class="h-full">
                        <template #content>
                            <div class="flex align-items-center gap-3">
                                <div class="p-3 bg-primary-100 border-round">
                                    <i
                                        class="pi pi-file text-primary text-xl"
                                    ></i>
                                </div>
                                <div>
                                    <p class="text-sm text-500 m-0">
                                        Total de Logs
                                    </p>
                                    <h3 class="text-2xl font-bold m-0">
                                        {{ totalLogs?.toLocaleString() || 0 }}
                                    </h3>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
                <div class="col-3 md-4">
                    <Card class="h-full">
                        <template #content>
                            <div class="flex align-items-center gap-3">
                                <div class="p-3 border-round">
                                    <i
                                        class="pi pi-database text-orange-500 text-xl"
                                    ></i>
                                </div>
                                <div>
                                    <p class="text-sm text-500 m-0">
                                        Tamaño del Archivo
                                    </p>
                                    <h3
                                        class="text-2xl font-bold m-0 text-orange-500"
                                    >
                                        {{ formatSize(logSize) }}
                                    </h3>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
                <div class="col-3 md-4">
                    <Card class="h-full">
                        <template #content>
                            <div class="flex align-items-center gap-3">
                                <div class="p-3 border-round">
                                    <i
                                        class="pi pi-chart-line text-green-500 text-xl"
                                    ></i>
                                </div>
                                <div>
                                    <p class="text-sm text-500 m-0">
                                        Mostrando
                                    </p>
                                    <h3
                                        class="text-2xl font-bold m-0 text-green-500"
                                    >
                                        {{ logs?.data?.length || 0 }} /
                                        {{ logs?.total || 0 }}
                                    </h3>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- Filters Card -->
            <!-- <Card class="mb-4">
                <template #header>
                    <div class="px-4 pt-4">
                        <h5 class="m-0 text-lg font-semibold">
                            Filtros de Búsqueda
                        </h5>
                    </div>
                </template>
                <template #content>
                    <div class="grid">
                        <div class="col-12 md-3">
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >Nivel</label
                            >
                            <Dropdown
                                v-model="filters.level"
                                :options="[
                                    { label: 'Todos los niveles', value: '' },
                                    { label: 'ERROR', value: 'ERROR' },
                                    { label: 'CRITICAL', value: 'CRITICAL' },
                                    { label: 'WARNING', value: 'WARNING' },
                                    { label: 'INFO', value: 'INFO' },
                                    { label: 'DEBUG', value: 'DEBUG' },
                                ]"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Seleccionar nivel"
                                class="w-full"
                            />
                        </div>
                        <div class="col-12 md-4">
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >Búsqueda</label
                            >
                            <InputGroup>
                                <InputGroupAddon>
                                    <i class="pi pi-search"></i>
                                </InputGroupAddon>
                                <InputText
                                    v-model="filters.search"
                                    placeholder="Buscar en mensaje o contexto..."
                                    class="w-full"
                                />
                            </InputGroup>
                        </div>
                        <div class="col-12 md-2">
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >Fecha inicial</label
                            >
                            <InputText
                                v-model="filters.date_from"
                                type="date"
                                class="w-full"
                            />
                        </div>
                        <div class="col-12 md-2">
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >Fecha final</label
                            >
                            <InputText
                                v-model="filters.date_to"
                                type="date"
                                class="w-full"
                            />
                        </div>
                        <div class="col-12 flex gap-2 mt-2">
                            <Button
                                icon="pi pi-filter"
                                label="Aplicar Filtros"
                                @click="applyFilters"
                                :loading="processing"
                            />
                            <Button
                                icon="pi pi-refresh"
                                label="Limpiar"
                                severity="secondary"
                                outlined
                                @click="clearFilters"
                            />
                        </div>
                    </div>
                </template>
            </Card> -->

            <!-- Logs Table -->
            <Card>
                <template #header>
                    <div
                        class="px-4 pt-4 flex justify-content-between align-items-center"
                    >
                        <h5 class="m-0 text-lg font-semibold">
                            Registros de Logs
                        </h5>
                        <div class="flex gap-2">
                            <Button
                                icon="pi pi-download"
                                label="Descargar"
                                severity="secondary"
                                outlined
                                @click="downloadLogs"
                            />
                            <Button
                                icon="pi pi-trash"
                                label="Limpiar"
                                severity="danger"
                                outlined
                                @click="clearDialog = true"
                            />
                        </div>
                    </div>
                </template>
                <template #content>
                    <div
                        v-if="!logs?.data || logs.data.length === 0"
                        class="text-center py-5"
                    >
                        <i class="pi pi-inbox text-500 text-4xl mb-3"></i>
                        <p class="text-500 m-0">No se encontraron logs</p>
                    </div>

                    <div v-else class="flex flex-column gap-2">
                        <div
                            v-for="(log, index) in logs.data"
                            :key="index"
                            class="p-3 border-1 border-surface-200 border-round hover:bg-surface-50 cursor-pointer transition-all transition-duration-200"
                            @click="
                                viewDetail(logs.current_page * 50 - 50 + index)
                            "
                        >
                            <div
                                class="flex justify-content-between align-items-start gap-3 mb-2"
                            >
                                <div class="flex align-items-center gap-2">
                                    <Tag
                                        :value="log.level"
                                        :severity="getLevelSeverity(log.level)"
                                        :icon="getLevelIcon(log.level)"
                                    />
                                    <span class="text-sm text-500 font-mono">{{
                                        formatDate(log.datetime)
                                    }}</span>
                                </div>
                                <Button
                                    icon="pi pi-eye"
                                    severity="secondary"
                                    text
                                    rounded
                                    size="small"
                                    class="flex-shrink-0"
                                />
                            </div>

                            <p class="text-sm font-medium mb-1">
                                {{ log.raw_message }}
                            </p>

                            <small class="text-500 line-clamp-2">
                                {{
                                    log.message
                                        ?.replace(log.raw_message, "")
                                        .trim()
                                        .substring(0, 150)
                                }}
                            </small>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="logs?.last_page > 1"
                        class="flex justify-content-center align-items-center gap-2 mt-4"
                    >
                        <Button
                            icon="pi pi-chevron-left"
                            label="Anterior"
                            severity="secondary"
                            outlined
                            @click="goToPage(logs.current_page - 1)"
                            :disabled="logs.current_page === 1"
                            :loading="processing"
                        />

                        <div class="flex align-items-center gap-1">
                            <span class="text-sm text-500 px-2">
                                Página {{ logs.current_page }} de
                                {{ logs.last_page }}
                            </span>
                        </div>

                        <Button
                            icon="pi pi-chevron-right"
                            label="Siguiente"
                            severity="secondary"
                            outlined
                            @click="goToPage(logs.current_page + 1)"
                            :disabled="logs.current_page === logs.last_page"
                            :loading="processing"
                        />
                    </div>
                </template>
            </Card>
        </div>

        <!-- Clear Dialog -->
        <ConfirmationDialog
            :visible="clearDialog"
            @confirm="clearLogs"
            @cancel="clearDialog = false"
            header="¿Está seguro de que desea eliminar todos los registros de
                    logs?"
            confirmOrDelete="delete"
            :loading="processing"
        />
        <!-- <Dialog
            v-model:visible="clearDialog"
            modal
            header="Limpiar Logs"
            :style="{ width: '35rem' }"
        >
            <div class="flex flex-column gap-3">
                <p class="m-0">
                    ¿Está seguro de que desea eliminar todos los registros de
                    logs?
                </p>
                <Alert severity="warn" :closable="false">
                    <p class="m-0 text-sm">
                        Esta acción es irreversible. Se eliminará
                        permanentemente todo el contenido del archivo
                        laravel.log
                    </p>
                </Alert>
            </div>
            <template #footer>
                <Button
                    label="Cancelar"
                    severity="secondary"
                    @click="clearDialog = false"
                />
                <Button
                    label="Eliminar Todo"
                    severity="danger"
                    @click="clearLogs"
                    :loading="processing"
                />
            </template>
        </Dialog> -->
    </AppLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
