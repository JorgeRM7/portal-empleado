<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToast } from "primevue/usetoast";

const props = defineProps({
    log: Object,
    index: Number,
    totalLogs: Number,
    flash: Object,
});

const toast = useToast();
const processing = ref(false);
const activeTab = ref(0);

const formatDate = (date) => {
    if (!date) return "";
    return new Date(date).toLocaleString("es-ES", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
    });
};

const goBack = () => {
    router.visit("/laravel-logs");
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
        ERROR: "pi-times-circle",
        CRITICAL: "pi-exclamation-triangle",
        WARNING: "pi-exclamation-circle",
        INFO: "pi-info-circle",
        DEBUG: "pi-code",
    };
    return map[level?.toUpperCase()] || "pi-file";
};

const copyToClipboard = (text, label) => {
    navigator.clipboard.writeText(text).then(() => {
        toast.add({
            severity: "success",
            summary: "Copiado",
            detail: `${label} copiado al portapapeles`,
            life: 3000,
        });
    });
};

const tabs = ref([
    { label: "Información General", key: "general" },
    { label: "Mensaje Completo", key: "message" },
]);
</script>

<template>
    <AppLayout title="Detalle de Log">
        <div class="p-4">
            <!-- Tabs -->
            <Card>
                <template #content>
                    <Tabs :value="tabs[0].key">
                        <TabList>
                            <Tab
                                v-for="(tab, i) in tabs"
                                :key="i"
                                :value="tab.key"
                                >{{ tab.label }}</Tab
                            >
                        </TabList>
                        <TabPanels>
                            <!-- General Info -->
                            <TabPanel :value="tabs[0].key">
                                <div class="p-4">
                                    <h5 class="mb-3">Información General</h5>

                                    <div class="grid">
                                        <div class="col-12">
                                            <div class="mb-3">
                                                <div
                                                    class="flex justify-between"
                                                >
                                                    <label
                                                        class="text-sm text-500 block mb-2"
                                                        >Mensaje
                                                        Principal</label
                                                    >
                                                    <Button
                                                        icon="pi pi-copy"
                                                        label="Copiar"
                                                        severity="secondary"
                                                        size="small"
                                                        @click="
                                                            copyToClipboard(
                                                                log.message,
                                                                'Mensaje completo',
                                                            )
                                                        "
                                                        class="mb-2"
                                                    />
                                                </div>
                                                <div
                                                    class="p-3 bg-surface-50 border-1 border-surface-200 border-round"
                                                >
                                                    <p class="m-0">
                                                        {{ log.raw_message }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 md-3">
                                            <div class="mb-3">
                                                <label
                                                    class="text-sm text-500 block mb-1"
                                                    >Fecha y Hora</label
                                                >
                                                <p class="font-semibold m-0">
                                                    {{
                                                        formatDate(log.datetime)
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-12 md-6">
                                            <div class="mb-3">
                                                <label
                                                    class="text-sm text-500 block mb-2"
                                                    >Archivo de Log</label
                                                >
                                                <p
                                                    class="m-0 font-mono text-sm"
                                                >
                                                    storage/logs/laravel.log
                                                </p>
                                            </div>
                                        </div>

                                        <div class="col-12 md-6">
                                            <div class="mb-3">
                                                <label
                                                    class="text-sm text-500 block mb-2"
                                                    >Posición</label
                                                >
                                                <p class="m-0">
                                                    Registro {{ index }} de
                                                    {{ totalLogs - 1 }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </TabPanel>

                            <!-- Full Message -->
                            <TabPanel :value="tabs[1].key">
                                <div class="p-4">
                                    <div
                                        class="flex justify-content-between align-items-center mb-3"
                                    >
                                        <h5 class="m-0">Mensaje Completo</h5>
                                        <Button
                                            icon="pi pi-copy"
                                            label="Copiar"
                                            severity="secondary"
                                            size="small"
                                            @click="
                                                copyToClipboard(
                                                    log.message,
                                                    'Mensaje completo',
                                                )
                                            "
                                        />
                                    </div>
                                    <div
                                        class="p-3 bg-surface-50 border-1 border-surface-200 border-round"
                                    >
                                        <pre
                                            class="m-0 whitespace-pre-wrap text-sm"
                                            >{{ log.message }}</pre
                                        >
                                    </div>
                                </div>
                            </TabPanel>

                            <!-- Context -->
                            <TabPanel>
                                <div class="p-4">
                                    <div
                                        class="flex justify-content-between align-items-center mb-3"
                                    >
                                        <h5 class="m-0">Contexto del Log</h5>
                                        <Button
                                            v-if="
                                                log.context &&
                                                Object.keys(log.context)
                                                    .length > 0
                                            "
                                            icon="pi pi-copy"
                                            label="Copiar"
                                            severity="secondary"
                                            size="small"
                                            @click="
                                                copyToClipboard(
                                                    JSON.stringify(
                                                        log.context,
                                                        null,
                                                        2,
                                                    ),
                                                    'Contexto',
                                                )
                                            "
                                        />
                                    </div>
                                    <div
                                        v-if="
                                            log.context &&
                                            Object.keys(log.context).length > 0
                                        "
                                    >
                                        <pre
                                            class="p-3 bg-surface-50 border-1 border-surface-200 border-round m-0 overflow-auto text-sm"
                                            style="max-height: 500px"
                                            >{{
                                                JSON.stringify(
                                                    log.context,
                                                    null,
                                                    2,
                                                )
                                            }}</pre
                                        >
                                    </div>
                                    <div v-else class="text-center py-5">
                                        <i
                                            class="pi pi-info-circle text-500 text-3xl mb-2"
                                        ></i>
                                        <p class="text-500 m-0">
                                            No hay datos de contexto disponibles
                                        </p>
                                    </div>
                                </div>
                            </TabPanel>

                            <!-- Stack Trace -->
                            <TabPanel>
                                <div class="p-4">
                                    <div
                                        class="flex justify-content-between align-items-center mb-3"
                                    >
                                        <h5 class="m-0">Stack Trace</h5>
                                        <Button
                                            v-if="log.stacktrace"
                                            icon="pi pi-copy"
                                            label="Copiar"
                                            severity="secondary"
                                            size="small"
                                            @click="
                                                copyToClipboard(
                                                    log.stacktrace,
                                                    'Stack trace',
                                                )
                                            "
                                        />
                                    </div>
                                    <div v-if="log.stacktrace">
                                        <pre
                                            class="p-3 bg-surface-50 border-1 border-surface-200 border-round m-0 overflow-auto text-sm"
                                            style="
                                                max-height: 600px;
                                                font-family:
                                                    &quot;Courier New&quot;,
                                                    monospace;
                                            "
                                            >{{ log.stacktrace }}</pre
                                        >
                                    </div>
                                    <div v-else class="text-center py-5">
                                        <i
                                            class="pi pi-code text-500 text-3xl mb-2"
                                        ></i>
                                        <p class="text-500 m-0">
                                            No hay stack trace disponible
                                        </p>
                                    </div>
                                </div>
                            </TabPanel>
                        </TabPanels>
                    </Tabs>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
pre {
    white-space: pre-wrap;
    word-wrap: break-word;
}
</style>
