<script setup>
import { router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
    log: Object,
    oldData: Object,
    newData: Object,
});

const formatDate = (date) => {
    if (!date) return "";
    return new Date(date).toLocaleString("es-ES");
};

const goBack = () => {
    router.visit("/admin/logs");
};

const getActionSeverity = (action) => {
    const map = {
        INSERT: "success",
        UPDATE: "warning",
        DELETE: "danger",
    };
    return map[action] || "secondary";
};

const getUserName = () => {
    return props.log.user_name || props.log.user_email || "Sistema";
};
</script>

<template>
    <AppLayout title="Detalle de Actividad">
        <template #header>
            <div class="flex justify-content-between align-items-center">
                <h2 class="text-xl font-semibold text-gray-900 m-0">
                    🔍 Detalle de Actividad #{{ log.id }}
                </h2>
                <Button
                    icon="pi pi-arrow-left"
                    label="Volver"
                    severity="secondary"
                    outlined
                    @click="goBack"
                />
            </div>
        </template>

        <div class="p-4 grid">
            <!-- 📋 Información principal -->
            <div class="col-12 lg-8">
                <Card class="mb-4">
                    <template #header>
                        <div
                            class="flex justify-content-between align-items-center"
                        >
                            <h5 class="m-0">📋 Información de la Actividad</h5>
                            <Tag
                                :value="log.action"
                                :severity="getActionSeverity(log.action)"
                            />
                        </div>
                    </template>
                    <template #content>
                        <div class="flex flex-column gap-3">
                            <div class="grid">
                                <div class="col-6">
                                    <label class="text-sm text-500"
                                        >Tabla</label
                                    >
                                    <p class="m-0">
                                        <code>{{ log.table_name }}</code>
                                    </p>
                                </div>
                                <div class="col-6">
                                    <label class="text-sm text-500"
                                        >ID Registro</label
                                    >
                                    <p class="m-0">
                                        <span class="font-mono">{{
                                            log.relationship_id
                                        }}</span>
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label class="text-sm text-500">Usuario</label>
                                <p class="m-0">{{ getUserName() }}</p>
                            </div>

                            <div>
                                <label class="text-sm text-500">Fecha</label>
                                <p class="m-0">{{ formatDate(log.date) }}</p>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- 📦 Datos Antiguos (old_data) -->
                <Card v-if="oldData" class="mb-4">
                    <template #header>
                        <h6 class="m-0">📦 Datos Antiguos (old_data)</h6>
                    </template>
                    <template #content>
                        <pre
                            class="bg-surface-100 p-3 border-round overflow-auto"
                            style="max-height: 400px; font-size: 0.85rem"
                            >{{ JSON.stringify(oldData, null, 2) }}</pre
                        >
                    </template>
                </Card>

                <!-- 📦 Datos Nuevos (new_data) - Si existe -->
                <Card v-if="newData" class="mb-4">
                    <template #header>
                        <h6 class="m-0">📦 Datos Nuevos (new_data)</h6>
                    </template>
                    <template #content>
                        <pre
                            class="bg-surface-100 p-3 border-round overflow-auto"
                            style="max-height: 400px; font-size: 0.85rem"
                            >{{ JSON.stringify(newData, null, 2) }}</pre
                        >
                    </template>
                </Card>
            </div>

            <!-- 📊 Metadatos -->
            <div class="col-12 lg-4">
                <Card class="mb-4">
                    <template #header>
                        <h6 class="m-0">📊 Metadatos</h6>
                    </template>
                    <template #content>
                        <div class="flex flex-column gap-3">
                            <div>
                                <label class="text-sm text-500"
                                    >ID Actividad</label
                                >
                                <p class="m-0 font-mono">{{ log.id }}</p>
                            </div>
                            <div>
                                <label class="text-sm text-500">Acción</label>
                                <p class="m-0">{{ log.action }}</p>
                            </div>
                            <div>
                                <label class="text-sm text-500"
                                    >Tabla Afectada</label
                                >
                                <p class="m-0">
                                    <code>{{ log.table_name }}</code>
                                </p>
                            </div>
                            <div>
                                <label class="text-sm text-500"
                                    >Registro ID</label
                                >
                                <p class="m-0 font-mono">
                                    {{ log.relationship_id }}
                                </p>
                            </div>
                            <div>
                                <label class="text-sm text-500"
                                    >Usuario ID</label
                                >
                                <p class="m-0">
                                    {{ log.user_id || "Sistema" }}
                                </p>
                            </div>
                            <div>
                                <label class="text-sm text-500"
                                    >Fecha/Hora</label
                                >
                                <p class="m-0">{{ formatDate(log.date) }}</p>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
