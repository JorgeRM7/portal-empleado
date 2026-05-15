<script setup>
import { ref, onMounted, computed, nextTick, watch } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToast } from "primevue/usetoast";

const toast = useToast();

const isRecording = ref(false);
const isProcessing = ref(false);
const transcript = ref("");
const aiResponse = ref("");
const conversationHistory = ref([]);
let recognition = null;

const requireFile = ref(false);
const pendingArguments = ref(null);
const selectedFile = ref(null);
const fileName = ref("");
const fileInput = ref(null);

const showFileDialog = ref(false);

const showDisclaimer = ref(true);

const scrollContainer = ref(null);

const micState = computed(() => {
    if (isProcessing.value) {
        return {
            icon: "pi pi-spin pi-spinner",
            label: "Procesando...",
            severity: "info",
        };
    }
    if (isRecording.value) {
        return {
            icon: "pi pi-stop-circle",
            label: "Grabando...",
            severity: "danger",
        };
    }
    return { icon: "pi pi-microphone", label: "Hablar", severity: "primary" };
});

onMounted(() => {
    const SpeechRecognition =
        window.SpeechRecognition || window.webkitSpeechRecognition;

    if (SpeechRecognition) {
        recognition = new SpeechRecognition();
        recognition.lang = "es-MX";

        recognition.continuous = true;

        recognition.interimResults = true;

        recognition.onresult = (event) => {
            let currentTranscript = "";
            for (let i = 0; i < event.results.length; i++) {
                currentTranscript += event.results[i][0].transcript;
            }
            transcript.value = currentTranscript;
        };

        recognition.onstart = () => (isRecording.value = true);

        recognition.onend = () => {
            isRecording.value = false;
            if (transcript.value.trim().length > 0) {
                sendToBackend();
            }
        };

        recognition.onerror = (event) => {
            console.error("Error en reconocimiento de voz:", event.error);
            isRecording.value = false;
        };
    }
});

watch(
    () => conversationHistory.value.length,
    () => {
        nextTick(() => {
            if (scrollContainer.value) {
                scrollContainer.value.scrollTop =
                    scrollContainer.value.scrollHeight;
            }
        });
    },
);

const toggleRecording = () => {
    if (!recognition) {
        toast.add({
            severity: "warn",
            summary: "No disponible",
            detail: "Reconocimiento de voz no soportado.",
            life: 3000,
        });
        return;
    }

    if (isRecording.value) {
        recognition.stop();
    } else {
        transcript.value = "";
        recognition.start();
    }
};

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        if (file.size > 10 * 1024 * 1024) {
            toast.add({
                severity: "error",
                summary: "Archivo muy grande",
                detail: "El archivo no debe exceder 10MB.",
                life: 3000,
            });
            return;
        }
        selectedFile.value = file;
        fileName.value = file.name;
    }
};

const sendToBackend = async () => {
    if (isProcessing.value || !transcript.value.trim()) return;
    isProcessing.value = true;

    conversationHistory.value.push({ role: "user", content: transcript.value });

    try {
        const response = await fetch("/chat/ai-assistant", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
            body: JSON.stringify({ messages: conversationHistory.value }),
        });

        const data = await response.json();

        if (data.action === "require_file") {
            requireFile.value = true;
            pendingArguments.value = data.arguments;
            aiResponse.value = data.reply;
            conversationHistory.value.push({
                role: "assistant",
                content: data.reply,
            });
            transcript.value = "";
            showFileDialog.value = true;
            await nextTick();
            isProcessing.value = false;
            return;
        }

        aiResponse.value = data.reply;
        conversationHistory.value.push({
            role: "assistant",
            content: data.reply,
        });
        transcript.value = "";

        toast.add({
            severity: "success",
            summary: "Mensaje procesado",
            life: 2000,
        });
    } catch (error) {
        console.error("Error:", error);
        aiResponse.value = "Hubo un error al conectar con el servidor.";
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudo conectar con el servidor.",
            life: 3000,
        });
    } finally {
        isProcessing.value = false;
    }
};

const submitFileAndData = async () => {
    if (!selectedFile.value) {
        toast.add({
            severity: "warn",
            summary: "Archivo requerido",
            detail: "Por favor, selecciona un documento.",
            life: 3000,
        });
        return;
    }

    isProcessing.value = true;

    const formData = new FormData();
    formData.append("document", selectedFile.value);
    formData.append("is_file_submission", "true");
    formData.append("arguments", JSON.stringify(pendingArguments.value));

    try {
        const response = await fetch("/chat/ai-assistant", {
            method: "POST",
            headers: {
                Accept: "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
            body: formData,
        });

        const data = await response.json();

        aiResponse.value = data.reply;
        requireFile.value = false;
        pendingArguments.value = null;
        selectedFile.value = null;
        fileName.value = "";
        showFileDialog.value = false;

        conversationHistory.value.push({
            role: "assistant",
            content: data.reply,
        });

        toast.add({
            severity: "success",
            summary: "Documento subido",
            detail: "Tu incidencia ha sido registrada.",
            life: 3000,
        });
    } catch (error) {
        console.error("Error:", error);
        toast.add({
            severity: "error",
            summary: "Error de carga",
            detail: "No se pudo subir el archivo.",
            life: 3000,
        });
    } finally {
        isProcessing.value = false;
    }
};

const clearConversation = () => {
    if (confirm("¿Deseas iniciar una nueva conversación?")) {
        conversationHistory.value = [];
        aiResponse.value = "";
        transcript.value = "";
        requireFile.value = false;
        pendingArguments.value = null;
        selectedFile.value = null;
        fileName.value = "";
        showFileDialog.value = false;
        toast.add({
            severity: "info",
            summary: "Conversación limpiada",
            life: 2000,
        });
    }
};
</script>

<template>
    <AppLayout>
        <!-- Toast notifications -->
        <div class="fixed top-4 right-4 z-50">
            <Teleport to="body">
                <div class="p-toast"></div>
            </Teleport>
        </div>

        <div class="card">
            <div>
                <!-- Disclaimer discreto -->
                <Transition name="slide-fade">
                    <div
                        v-if="showDisclaimer"
                        class="mb-6 flex items-center justify-between gap-3 px-3.5 py-2.5 bg-amber-50 border border-amber-200 rounded-lg backdrop-blur-sm"
                    >
                        <div class="flex items-center gap-2.5">
                            <i
                                class="pi pi-info-circle text-amber-600 text-xs"
                            ></i>
                            <span class="text-sm text-amber-700">
                                <strong>Nota:</strong> La IA puede cometer
                                errores. Verifica información importante.
                            </span>
                        </div>
                        <button
                            @click="showDisclaimer = false"
                            class="text-amber-400 hover:text-amber-600 transition-colors"
                        >
                            <i class="pi pi-times text-sm"></i>
                        </button>
                    </div>
                </Transition>

                <!-- Encabezado -->
                <div class="mb-8">
                    <div class="flex items-baseline justify-between mb-2">
                        <h1
                            class="text-4xl font-light text-gray-900 tracking-tight"
                        >
                            Asistente Virtual
                        </h1>
                        <span
                            class="text-xs text-gray-400 uppercase tracking-widest"
                            >Beta</span
                        >
                    </div>
                    <p class="text-sm text-gray-500">
                        Consulta sobre incidencias, vacaciones y solicitudes
                    </p>
                </div>

                <!-- Contenedor principal -->
                <div class="space-y-10 w-full">
                    <!-- Panel de conversación -->
                    <div class="max-h-[500px] overflow-y-auto flex flex-col">
                        <!-- Historial -->
                        <div
                            v-if="conversationHistory.length > 0"
                            ref="scrollContainer"
                            class="flex-1 overflow-y-auto"
                        >
                            <div class="">
                                <div
                                    v-for="(msg, idx) in conversationHistory"
                                    :key="idx"
                                    :class="[
                                        'px-5 py-4 transition-colors',
                                        msg.role === 'user' ? 'card' : 'card',
                                    ]"
                                >
                                    <div class="flex gap-3">
                                        <Avatar
                                            :label="
                                                msg.role === 'user' ? 'T' : 'IA'
                                            "
                                            :style="{
                                                backgroundColor:
                                                    msg.role === 'user'
                                                        ? '#3b82f6'
                                                        : '#6b7280',
                                                color: '#ffffff',
                                            }"
                                            shape="circle"
                                            size="small"
                                        />
                                        <div class="flex-1 min-w-0">
                                            <p
                                                class="text-xs font-semibold text-gray-600 mb-1.5"
                                            >
                                                {{
                                                    msg.role === "user"
                                                        ? "Tú"
                                                        : "Asistente IA"
                                                }}
                                            </p>
                                            <p
                                                class="text-sm text-gray-700 leading-relaxed break-words whitespace-pre-wrap"
                                            >
                                                {{ msg.content }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Input area -->
                        <div>
                            <Textarea
                                v-model="transcript"
                                :auto-resize="true"
                                rows="2"
                                placeholder="Escribe tu pregunta..."
                                class="w-full text-sm mt-5"
                                :disabled="requireFile || isProcessing"
                            />
                        </div>
                    </div>

                    <!-- Controles -->
                    <div class="flex gap-3">
                        <Button
                            :icon="micState.icon"
                            :label="micState.label"
                            :severity="micState.severity"
                            @click="toggleRecording"
                            :disabled="requireFile || isProcessing"
                            class="flex-1"
                            size="large"
                            :pt="{
                                root: {
                                    class: isRecording ? 'pulse' : '',
                                },
                            }"
                        />
                        <Button
                            icon="pi pi-send"
                            label="Enviar"
                            severity="success"
                            @click="sendToBackend"
                            :disabled="
                                !transcript.trim() ||
                                requireFile ||
                                isProcessing
                            "
                            class="flex-1"
                            size="large"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Dialog de archivo mejorado -->
        <Dialog
            v-model:visible="showFileDialog"
            header="Adjuntar documento"
            :modal="true"
            :style="{ width: '90vw', maxWidth: '500px' }"
            :pt="{
                root: {
                    class: 'rounded-xl shadow-lg border border-gray-200',
                },
                header: {
                    class: 'bg-white border-b border-gray-100 p-5',
                },
                content: {
                    class: 'p-5 space-y-5',
                },
            }"
        >
            <div class="space-y-5">
                <p class="text-sm text-gray-600 leading-relaxed">
                    {{ aiResponse }}
                </p>

                <!-- Zona de carga -->
                <div
                    class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:bg-gray-50 hover:border-gray-400 transition-all cursor-pointer"
                    @click="$refs.fileInput?.click()"
                >
                    <input
                        ref="fileInput"
                        type="file"
                        @change="handleFileChange"
                        class="hidden"
                    />
                    <i
                        class="pi pi-cloud-upload text-3xl text-gray-400 mb-3 block"
                    ></i>
                    <p class="text-sm font-medium text-gray-700">
                        Selecciona un archivo
                    </p>
                    <p class="text-xs text-gray-500 mt-1">Máximo 10MB</p>
                </div>

                <!-- Archivo seleccionado -->
                <Transition name="scale">
                    <div
                        v-if="fileName"
                        class="flex items-center gap-3 p-4 bg-emerald-50 rounded-lg border border-emerald-200"
                    >
                        <i class="pi pi-check-circle text-emerald-600"></i>
                        <div class="flex-1 min-w-0">
                            <p
                                class="text-sm font-medium text-emerald-700 truncate"
                            >
                                {{ fileName }}
                            </p>
                            <p class="text-xs text-emerald-600">
                                Listo para subir
                            </p>
                        </div>
                    </div>
                </Transition>

                <!-- Botones -->
                <div class="flex gap-3 pt-4 border-t border-gray-100">
                    <Button
                        label="Cancelar"
                        severity="secondary"
                        @click="showFileDialog = false"
                        class="flex-1"
                    />
                    <Button
                        label="Subir y Continuar"
                        icon="pi pi-check"
                        @click="submitFileAndData"
                        :disabled="!selectedFile || isProcessing"
                        :loading="isProcessing"
                        class="flex-1"
                    />
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
/* Transiciones */
.slide-fade-enter-active {
    transition: all 0.3s ease;
}

.slide-fade-leave-active {
    transition: all 0.2s ease;
}

.slide-fade-enter-from {
    transform: translateX(-10px);
    opacity: 0;
}

.slide-fade-leave-to {
    transform: translateX(10px);
    opacity: 0;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.scale-enter-active,
.scale-leave-active {
    transition: all 0.2s ease;
}

.scale-enter-from {
    transform: scale(0.95);
    opacity: 0;
}

.scale-leave-to {
    transform: scale(0.95);
    opacity: 0;
}

/* Animación pulse para grabación */
:deep(.p-button.pulse) {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

/* Scrollbar styling */
:deep(.max-h-96) {
    scrollbar-width: thin;
    scrollbar-color: #d1d5db #f3f4f6;
}

:deep(.max-h-96::-webkit-scrollbar) {
    width: 6px;
}

:deep(.max-h-96::-webkit-scrollbar-track) {
    background: #f3f4f6;
    border-radius: 10px;
}

:deep(.max-h-96::-webkit-scrollbar-thumb) {
    background: #d1d5db;
    border-radius: 10px;
}

:deep(.max-h-96::-webkit-scrollbar-thumb:hover) {
    background: #9ca3af;
}
</style>
