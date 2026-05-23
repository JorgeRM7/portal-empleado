<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { computed, ref } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import Tag from "primevue/tag";
import Button from "primevue/button";
import Divider from "primevue/divider";
import SplitButton from "primevue/splitbutton";
import Dialog from "primevue/dialog";
import axios from "axios";

const page = usePage();

// 📦 ticket seguro
const ticket = computed(() => page.props.ticket ?? null);

// ⭐ estados
const califcarDialog = ref(false);
const selectedTicket = ref(null);
const deleting = ref(false);
const rating = ref(0);

// 🎯 status helpers
const getStatusSeverity = (status) => {
    switch (status?.toLowerCase()) {
        case "pendiente":
            return "warn";
        case "escalado":
            return "info";
        case "resuelto":
            return "success";
        default:
            return "secondary";
    }
};

const getStatusIcon = (status) => {
    switch (status?.toLowerCase()) {
        case "pendiente":
            return "pi pi-clock";
        case "escalado":
            return "pi pi-arrow-up-right";
        case "resuelto":
            return "pi pi-check-circle";
        default:
            return "pi pi-info-circle";
    }
};

// 📎 archivos
const openFile = (url) => {
    window.open(url, "_blank");
};

const formatFiles = (archivos) => {
    if (!archivos?.length) return [];

    return archivos.map((file) => ({
        label: file.name,
        icon: "pi pi-paperclip",
        command: () => openFile(file.url),
    }));
};

// 🔙 back
const goBack = () => {
    router.visit("/complaints");
};

// ⭐ enviar rating
const submitRating = async () => {
    if (!rating.value) return;

    deleting.value = true;

    try {
        await axios.post("/complaints/rate-response", {
            id_complaint: ticket.value.id,
            rating: rating.value,
        });

        rating.value = 0;
        califcarDialog.value = false;
        selectedTicket.value = null;

    } catch (error) {
        console.error("Error al enviar calificación:", error);
    } finally {
        deleting.value = false;
    }
};
</script>

<template>
    <AppLayout title="Detalle del Ticket">

        <div class="card" v-if="ticket">

            <!-- HEADER -->
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h2 class="text-2xl font-bold">
                        Ticket #{{ ticket?.id }}
                    </h2>
                    <p class="text-gray-500 text-sm">
                        {{ ticket?.date }} {{ ticket?.hour }}
                    </p>
                </div>

                <Button
                    icon="pi pi-arrow-left"
                    label="Regresar"
                    severity="secondary"
                    @click="goBack"
                />
            </div>

            <!-- INFO GRID -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <!-- ASUNTO -->
                <div class="p-4 border rounded-xl">
                    <p class="text-xs text-gray-500">Asunto</p>
                    <p class="font-semibold">{{ ticket?.subject }}</p>
                </div>

                <!-- ESTATUS -->
                <div class="p-4 border rounded-xl">
                    <p class="text-xs text-gray-500">Estatus</p>

                    <Tag
                        :value="ticket?.status"
                        :severity="getStatusSeverity(ticket?.status)"
                        :icon="getStatusIcon(ticket?.status)"
                    />
                </div>

                <!-- DESCRIPCIÓN -->
                <div class="p-4 border rounded-xl col-span-2">
                    <p class="text-xs text-gray-500">Descripción del Ticket</p>
                    <p class="whitespace-pre-line">
                        {{ ticket?.case }}
                    </p>
                </div>

                <!-- ACLARACIÓN -->
                <div class="p-4 border rounded-xl col-span-2">
                    <p class="text-xs text-gray-500">Aclaración</p>

                    <p v-if="ticket?.response">
                        {{ ticket.response }}
                    </p>

                    <p v-else class="text-gray-400">
                        Sin respuesta aún
                    </p>
                </div>

                <!-- EVIDENCIA -->
                <div class="p-4 border rounded-xl col-span-2">
                    <p class="text-xs text-gray-500 mb-2">Evidencia</p>

                    <div v-if="ticket?.archivos?.length">
                        <SplitButton
                            icon="pi pi-paperclip"
                            label="Ver archivos"
                            severity="info"
                            :model="formatFiles(ticket.archivos)"
                        />
                    </div>

                    <p v-else class="text-gray-400">
                        Sin evidencias
                    </p>
                </div>
            </div>

            <Divider />

            <!-- CALIFICACIÓN -->
            <div class="p-4 border rounded-xl mt-4">

                <p class="text-xs text-gray-500 mb-2">
                    Calificación de respuesta
                </p>

                <!-- BOTÓN CALIFICAR -->
                <div v-if="ticket?.response && ticket?.rate === null">
                    <Button
                        icon="pi pi-star"
                        label="Calificar respuesta"
                        severity="success"
                        @click="califcarDialog = true"
                    />
                </div>

                <!-- ESTRELLAS -->
                <div v-else-if="ticket?.rate">
                    <div class="flex gap-1">
                        <i
                            v-for="i in 5"
                            :key="i"
                            class="pi"
                            :class="i <= ticket.rate ? 'pi-star-fill text-yellow-400' : 'pi-star text-gray-300'"
                        />
                    </div>
                </div>

                <p v-else class="text-gray-400">
                    No disponible
                </p>

            </div>

        </div>

        <!-- MODAL -->
        <Dialog
            v-model:visible="califcarDialog"
            header="Calificar respuesta"
            modal
            :style="{ width: '500px' }"
        >
            <div class="flex flex-col gap-6 py-4">

                <p class="text-center">
                    ¿Qué calificación le das a esta respuesta?
                </p>

                <div class="flex justify-center gap-3">
                    <button
                        v-for="i in 5"
                        :key="i"
                        @click="rating = i"
                        class="text-3xl transition hover:scale-125"
                        :class="i <= rating ? 'text-yellow-400' : 'text-gray-300'"
                    >
                        <i class="pi pi-star-fill"></i>
                    </button>
                </div>

                <p class="text-center text-sm text-gray-500">
                    {{ rating }} / 5
                </p>

            </div>

            <template #footer>
                <Button
                    label="Cancelar"
                    text
                    severity="secondary"
                    @click="califcarDialog = false"
                />
                <Button
                    label="Enviar"
                    severity="success"
                    :disabled="rating === 0"
                    :loading="deleting"
                    @click="submitRating"
                />
            </template>
        </Dialog>

    </AppLayout>
</template>
