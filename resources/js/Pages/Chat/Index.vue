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

// Configuración del reconocimiento de voz nativo del navegador
onMounted(() => {
    // Verificamos si el navegador soporta la API de voz
    const SpeechRecognition =
        window.SpeechRecognition || window.webkitSpeechRecognition;

    if (SpeechRecognition) {
        recognition = new SpeechRecognition();
        recognition.lang = "es-MX"; // Configurado para español
        recognition.continuous = false; // Se detiene automáticamente cuando el usuario hace una pausa
        recognition.interimResults = false; // Solo devuelve el resultado final

        // Evento que se dispara cuando el navegador reconoce texto
        recognition.onresult = (event) => {
            const currentTranscript = event.results[0][0].transcript;
            transcript.value = currentTranscript;
        };

        // Eventos para controlar el estado del botón
        recognition.onstart = () => (isRecording.value = true);
        recognition.onend = () => (isRecording.value = false);
        recognition.onerror = (event) => {
            console.error("Error en el reconocimiento de voz:", event.error);
            isRecording.value = false;
        };
    } else {
        console.warn("Tu navegador no soporta el reconocimiento de voz.");
    }
});

// Función para encender o apagar el micrófono
const toggleRecording = () => {
    if (!recognition)
        return alert("Reconocimiento de voz no soportado en este navegador.");

    if (isRecording.value) {
        recognition.stop();
    } else {
        transcript.value = ""; // Limpiamos el texto anterior
        recognition.start();
    }
};

// Función para enviar el texto a nuestro backend de Laravel
const sendToBackend = async () => {
    aiResponse.value = "Procesando...";

    // 1. Guardamos lo que el usuario acaba de decir en el historial
    conversationHistory.value.push({ role: "user", content: transcript.value });

    try {
        const response = await fetch("/api/ai-assistant", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
            },
            // 2. Enviamos el historial completo, no solo el último mensaje
            body: JSON.stringify({ messages: conversationHistory.value }),
        });

        const data = await response.json();
        aiResponse.value = data.reply;

        // 3. Guardamos la respuesta de la IA en el historial para que lo recuerde la próxima vez
        conversationHistory.value.push({
            role: "assistant",
            content: data.reply,
        });

        // Limpiamos el cuadro de texto para la siguiente interacción
        transcript.value = "";
    } catch (error) {
        console.error("Error al conectar con el backend:", error);
        aiResponse.value = "Hubo un error al comunicarse con el servidor.";
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
                <!-- Botón de PrimeVue para iniciar/detener la grabación -->
                <Button
                    :icon="
                        isRecording ? 'pi pi-stop-circle' : 'pi pi-microphone'
                    "
                    :label="isRecording ? 'Escuchando...' : 'Hablar'"
                    :severity="isRecording ? 'danger' : 'primary'"
                    @click="toggleRecording"
                />

                <!-- Botón para enviar el texto al backend -->
                <Button
                    icon="pi pi-send"
                    label="Enviar a IA"
                    severity="success"
                    :disabled="!transcript"
                    @click="sendToBackend"
                />
            </div>

            <!-- Textarea de PrimeVue para mostrar el texto reconocido -->
            <Textarea
                v-model="transcript"
                rows="4"
                cols="50"
                placeholder="Lo que digas aparecerá aquí..."
                class="w-full"
            />

            <!-- Aquí mostraremos la respuesta del backend/IA -->
            <div v-if="aiResponse" class="mt-4 p-3 surface-100 border-round">
                <strong>Respuesta de la IA:</strong>
                <p>{{ aiResponse }}</p>
            </div>
        </div>
    </AppLayout>
</template>
