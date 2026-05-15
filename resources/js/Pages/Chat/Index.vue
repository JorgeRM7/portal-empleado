<script setup>
import { ref, onMounted } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "primevue/button";
import Textarea from "primevue/textarea";

// Variables reactivas (ref) para manejar el estado
const isRecording = ref(false);
const transcript = ref("");
const aiResponse = ref("");
let recognition = null;
const conversationHistory = ref([]);

// NUEVAS VARIABLES: Para el manejo de subida de archivos
const requireFile = ref(false);
const pendingArguments = ref(null);
const selectedFile = ref(null);

// Configuración del reconocimiento de voz nativo del navegador
onMounted(() => {
    const SpeechRecognition =
        window.SpeechRecognition || window.webkitSpeechRecognition;

    if (SpeechRecognition) {
        recognition = new SpeechRecognition();
        recognition.lang = "es-MX";
        recognition.continuous = false;
        recognition.interimResults = false;

        recognition.onresult = (event) => {
            transcript.value = event.results[0][0].transcript;
        };

        recognition.onstart = () => (isRecording.value = true);
        recognition.onend = () => {
            isRecording.value = false;
            if (transcript.value.trim().length > 0) {
                sendToBackend();
            }
        };
        recognition.onerror = (event) => {
            console.error("Error en el reconocimiento de voz:", event.error);
            isRecording.value = false;
        };
    } else {
        console.warn("Tu navegador no soporta el reconocimiento de voz.");
    }
});

const toggleRecording = () => {
    if (!recognition)
        return alert("Reconocimiento de voz no soportado en este navegador.");

    if (isRecording.value) {
        recognition.stop();
    } else {
        transcript.value = "";
        recognition.start();
    }
};

// Capturar el archivo seleccionado por el usuario
const handleFileChange = (event) => {
    selectedFile.value = event.target.files[0];
};

const sendToBackend = async () => {
    aiResponse.value = "Procesando...";
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

        // NUEVO: Detectar si el backend solicita un archivo
        if (data.action === "require_file") {
            requireFile.value = true;
            pendingArguments.value = data.arguments; // Guardamos los datos recolectados por la IA
            aiResponse.value = data.reply;
            conversationHistory.value.push({
                role: "assistant",
                content: data.reply,
            });
            transcript.value = "";
            return; // Detenemos la ejecución aquí
        }

        aiResponse.value = data.reply;
        conversationHistory.value.push({
            role: "assistant",
            content: data.reply,
        });
        transcript.value = "";
    } catch (error) {
        console.error("Error al conectar con el backend:", error);
        aiResponse.value = "Hubo un error al comunicarse con el servidor.";
    }
};

// NUEVA FUNCIÓN: Enviar el archivo y los argumentos al backend
const submitFileAndData = async () => {
    if (!selectedFile.value) {
        alert("Por favor, selecciona un documento primero.");
        return;
    }

    aiResponse.value = "Subiendo documento y registrando incidencia...";

    // Usamos FormData para poder enviar un archivo físico
    const formData = new FormData();
    formData.append("document", selectedFile.value);
    formData.append("is_file_submission", "true"); // Bandera para avisarle al backend
    formData.append("arguments", JSON.stringify(pendingArguments.value));

    try {
        const response = await fetch("/chat/ai-assistant", {
            method: "POST",
            headers: {
                Accept: "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
                // Nota: Al usar FormData NO enviamos "Content-Type", el navegador lo asigna automáticamente
            },
            body: formData,
        });

        const data = await response.json();

        // Limpiamos la interfaz tras el éxito
        aiResponse.value = data.reply;
        requireFile.value = false;
        pendingArguments.value = null;
        selectedFile.value = null;

        conversationHistory.value.push({
            role: "assistant",
            content: data.reply,
        });
    } catch (error) {
        console.error("Error al subir el archivo:", error);
        aiResponse.value =
            "Hubo un error al subir el archivo e intentar guardar.";
    }
};
</script>

<template>
    <AppLayout>
        <div class="p-4 border border-round surface-border">
            <h3>Asistente Virtual del Empleado</h3>
            <p>
                Presiona el botón de micrófono y dime qué necesitas (ej. "Quiero
                registrar una incidencia").
            </p>

            <div class="flex gap-2 mb-3">
                <Button
                    :icon="
                        isRecording ? 'pi pi-stop-circle' : 'pi pi-microphone'
                    "
                    :label="isRecording ? 'Escuchando...' : 'Hablar'"
                    :severity="isRecording ? 'danger' : 'primary'"
                    @click="toggleRecording"
                    :disabled="requireFile"
                />

                <Button
                    icon="pi pi-send"
                    label="Enviar a IA"
                    severity="success"
                    :disabled="!transcript || requireFile"
                    @click="sendToBackend"
                />
            </div>

            <!-- Deshabilitamos el textarea si estamos esperando un archivo -->
            <Textarea
                v-model="transcript"
                rows="4"
                cols="50"
                placeholder="Lo que digas aparecerá aquí..."
                class="w-full"
                :disabled="requireFile"
            />

            <div v-if="aiResponse" class="mt-4 p-3 surface-100 border-round">
                <strong>Respuesta de la IA:</strong>
                <p class="whitespace-pre-line">{{ aiResponse }}</p>

                <!-- NUEVA SECCIÓN: Input de archivo dinámico -->
                <div
                    v-if="requireFile"
                    class="mt-3 p-3 border-1 border-gray-300 border-round surface-0"
                >
                    <label class="block font-bold mb-2"
                        >Adjuntar comprobante:</label
                    >
                    <input
                        type="file"
                        @change="handleFileChange"
                        class="mb-3 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                    />
                    <Button
                        label="Subir y Finalizar Registro"
                        icon="pi pi-check"
                        severity="info"
                        @click="submitFileAndData"
                        :disabled="!selectedFile"
                    />

                    <Button
                        label="Cancelar"
                        icon="pi pi-times"
                        severity="secondary"
                        class="ml-2"
                        @click="requireFile = false"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
