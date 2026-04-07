<script setup>
import axios from "axios";
import { ref, computed, nextTick, watch, onMounted } from "vue";

const props = defineProps({
    endpoint: { type: String, default: "/assistant/chat" },
    module: { type: String, default: "General" },
    useBackend: { type: Boolean, default: false },
    systemPrompt: { type: String, default: "" },
    extraContext: { type: Object, default: () => ({}) },
});

const open = ref(false);
const expanded = ref(false);
const loading = ref(false);
const prompt = ref("");
const messagesContainer = ref(null);
const textareaRef = ref(null);
const notificationCount = ref(0);

const currentContext = ref(`Módulo: ${props.module}`);

const quickSuggestions = ref([
    { label: "Abrir ticket", text: "Necesito abrir un ticket porque " },
    { label: "Reportar error", text: "Tengo un error: " },
    { label: "Consulta", text: "Tengo una duda sobre " },
]);

const messages = ref([
    {
        role: "assistant",
        content:
            "Hola, soy tu asistente de soporte.\n\nPuedo ayudarte con:\n• Reportar errores técnicos\n• Abrir tickets de soporte\n• Resolver dudas del sistema\n\n¿En qué puedo ayudarte?",
        cards: [
            { title: "Reportar error", suggestion: "Tengo un error: " },
            { title: "Abrir ticket", suggestion: "Necesito un ticket: " },
            { title: "Consulta general", suggestion: "Tengo una duda: " },
        ],
        timestamp: new Date(),
    },
]);

const autoResize = () => {
    if (textareaRef.value) {
        textareaRef.value.style.height = "auto";
        textareaRef.value.style.height = `${Math.min(textareaRef.value.scrollHeight, 120)}px`;
    }
};

const fillPrompt = (text) => {
    prompt.value = text;
    nextTick(() => {
        if (textareaRef.value) {
            textareaRef.value.focus();
            autoResize();
        }
    });
};

const handleCardClick = (card) => {
    if (card.suggestion) {
        fillPrompt(card.suggestion);
    }
};

const toggleAssistant = () => {
    open.value = !open.value;
    if (open.value) {
        notificationCount.value = 0;
        nextTick(() => scrollToBottom());
    }
};

const toggleExpand = () => {
    expanded.value = !expanded.value;
    nextTick(() => scrollToBottom());
};

const clearChat = () => {
    if (confirm("¿Limpiar conversación?")) {
        messages.value = [messages.value[0]];
        scrollToBottom();
    }
};

const sendMessage = async () => {
    if (!prompt.value.trim() || loading.value) return;

    const text = prompt.value.trim();

    messages.value.push({
        role: "user",
        content: text,
        timestamp: new Date(),
    });

    prompt.value = "";
    loading.value = true;

    if (textareaRef.value) textareaRef.value.style.height = "auto";

    await nextTick();
    scrollToBottom();

    if (props.useBackend) {
        await sendToBackend(text);
    } else {
        setTimeout(async () => {
            messages.value.push(buildDemoResponse(text));
            loading.value = false;
            await nextTick();
            scrollToBottom();
        }, 600);
    }
};

const sendToBackend = async (text) => {
    try {
        const headers = {
            "Content-Type": "application/json",
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
        };

        const response = await axios.post(
            props.endpoint,
            {
                message: text,
                history: messages.value.slice(0, -1).map((m) => ({
                    role: m.role,
                    content: m.content,
                })),
            },
            { headers },
        );

        const data = response?.data || {};
        const replyText =
            data.response || data.reply || "No se recibió respuesta.";

        messages.value.push({
            role: "assistant",
            content: replyText,
            timestamp: new Date(),
        });
    } catch (error) {
        messages.value.push({
            role: "assistant",
            content: "Error de conexión. Intenta de nuevo.",
            timestamp: new Date(),
        });
    } finally {
        loading.value = false;
        await nextTick();
        scrollToBottom();
    }
};

const buildDemoResponse = (text) => {
    const lower = text.toLowerCase();

    if (lower.includes("ticket")) {
        return {
            role: "assistant",
            content:
                "Para crear un ticket, necesito:\n1. ¿En qué módulo tienes el problema?\n2. ¿Qué mensaje de error ves?\n3. ¿Qué acción realizabas?",
            timestamp: new Date(),
        };
    }

    if (lower.includes("error")) {
        return {
            role: "assistant",
            content:
                "¿Podrías darme más detalles?\n• ¿En qué sección ocurrió?\n• ¿Qué mensaje ves?\n• ¿Pasos para reproducirlo?",
            timestamp: new Date(),
        };
    }

    return {
        role: "assistant",
        content: "Recibí tu consulta. ¿Podrías darme más detalles?",
        timestamp: new Date(),
    };
};

const formatTime = (date) => {
    if (!date) return "";
    return new Intl.DateTimeFormat("es-MX", {
        hour: "2-digit",
        minute: "2-digit",
    }).format(new Date(date));
};

const scrollToBottom = () => {
    if (!messagesContainer.value) return;
    messagesContainer.value.scrollTo({
        top: messagesContainer.value.scrollHeight,
        behavior: "smooth",
    });
};

watch(prompt, () => autoResize());
watch(
    messages,
    async () => {
        await nextTick();
        scrollToBottom();
    },
    { deep: true },
);

onMounted(() => {
    const style = document.createElement("style");
    style.textContent = `
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(8px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .fab-enter-active, .fab-leave-active { transition: all 0.25s ease; }
    .fab-enter-from, .fab-leave-to { opacity: 0; transform: scale(0.8) translateY(20px); }
    
    .panel-enter-active, .panel-leave-active { transition: all 0.3s ease; }
    .panel-enter-from, .panel-leave-to { opacity: 0; transform: translateY(24px) scale(0.98); }
    
    .assistant-scroll::-webkit-scrollbar { width: 4px; }
    .assistant-scroll::-webkit-scrollbar-track { background: transparent; }
    .assistant-scroll::-webkit-scrollbar-thumb { 
      background: rgba(148, 163, 184, 0.2); 
      border-radius: 999px; 
    }
  `;
    document.head.appendChild(style);
});
</script>
<template>
    <div class="fixed bottom-6 right-6 z-[9999] font-sans">
        <!-- BOTÓN FLOTANTE -->
        <transition name="fab">
            <button
                v-if="!open"
                @click="toggleAssistant"
                class="group relative flex items-center justify-center w-14 h-14 rounded-full bg-orange-600 hover:bg-orange-700 text-white hover:scale-105 active:scale-95 transition-all duration-300 ease-out shadow-xl"
                type="button"
                aria-label="Abrir asistente"
            >
                <i class="pi pi-sparkles text-xl"></i>
            </button>
        </transition>

        <!-- PANEL DE CHAT -->
        <transition name="panel">
            <div
                v-if="open"
                :class="[
                    expanded
                        ? 'w-[96vw] max-w-[1200px] h-[88vh]'
                        : 'w-[400px] max-w-[calc(100vw-24px)] h-[680px] max-h-[82vh]',
                ]"
                class="rounded-2xl overflow-hidden bg-orange-600 text-slate-100 shadow-2xl flex flex-col transition-all duration-300 ease-out"
            >
                <!-- HEADER -->
                <div class="px-5 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="relative">
                                <div
                                    class="w-10 h-10 rounded-full bg-white flex items-center justify-center"
                                >
                                    <i
                                        class="pi pi-sparkles text-orange-600 text-sm"
                                    ></i>
                                </div>
                                <span
                                    class="absolute bottom-0 right-0 w-3 h-3 rounded-full bg-green-500 border-2 border-slate-900"
                                ></span>
                            </div>
                            <div>
                                <p class="text-lg font-semibold">
                                    Asistente - Grupo Ortiz
                                </p>
                                <p class="text-xs">En línea</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-1">
                            <button
                                @click="clearChat"
                                class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
                            >
                                <i class="pi pi-trash text-xs"></i>
                            </button>
                            <button
                                @click="toggleExpand"
                                class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
                            >
                                <i
                                    :class="
                                        expanded
                                            ? 'pi pi-window-minimize'
                                            : 'pi pi-window-maximize'
                                    "
                                    class="text-xs"
                                ></i>
                            </button>
                            <button
                                @click="toggleAssistant"
                                class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
                            >
                                <i class="pi pi-times text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- MENSAJES -->
                <div
                    ref="messagesContainer"
                    class="flex-1 overflow-y-auto px-4 py-4 space-y-4 bg-slate-900"
                >
                    <!-- Mensaje de bienvenida -->
                    <div
                        v-if="
                            messages.length === 1 &&
                            messages[0].role === 'assistant'
                        "
                        class="flex justify-start"
                    >
                        <div class="max-w-[85%]">
                            <div
                                class="bg-slate-800 rounded-2xl rounded-tl-sm px-4 py-3"
                            >
                                <p
                                    class="text-sm leading-relaxed whitespace-pre-line text-slate-300"
                                >
                                    {{ messages[0].content }}
                                </p>
                            </div>

                            <!-- Sugerencias -->
                            <div
                                v-if="messages[0].cards?.length"
                                class="mt-3 space-y-2"
                            >
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="(card, i) in messages[0].cards"
                                        :key="i"
                                        @click="
                                            fillPrompt(
                                                card.suggestion || card.title,
                                            )
                                        "
                                        class="px-3 py-2 rounded-lg bg-slate-800/50 hover:bg-slate-700 text-left text-sm text-slate-300 transition-colors"
                                    >
                                        {{ card.title }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resto de mensajes -->
                    <template
                        v-for="(message, index) in messages.slice(1)"
                        :key="index"
                    >
                        <div
                            class="flex"
                            :class="
                                message.role === 'user'
                                    ? 'justify-end'
                                    : 'justify-start'
                            "
                        >
                            <div
                                :class="[
                                    message.role === 'user'
                                        ? 'bg-white text-slate-900 rounded-2xl rounded-br-sm'
                                        : 'bg-slate-800 text-slate-300 rounded-2xl rounded-bl-sm',
                                ]"
                                class="max-w-[85%] px-4 py-2.5"
                            >
                                <div v-if="message.role === 'user'">
                                    <p class="text-sm font-semibold">Yo</p>
                                    <p
                                        class="text-sm leading-relaxed whitespace-pre-line"
                                    >
                                        {{ message.content }}
                                    </p>
                                </div>
                                <div v-else>
                                    <div class="flex items-center gap-2">
                                        <i class="pi pi-sparkles"></i>
                                        <p class="text-sm font-semibold">
                                            Asistente
                                        </p>
                                    </div>
                                    <p
                                        class="text-sm leading-relaxed whitespace-pre-line"
                                    >
                                        {{ message.content }}
                                    </p>
                                </div>
                                <p
                                    class="text-[10px] mt-1 opacity-60 text-right"
                                >
                                    {{ formatTime(message.timestamp) }}
                                </p>
                            </div>
                        </div>
                    </template>

                    <!-- Loading -->
                    <div v-if="loading" class="flex justify-start">
                        <div
                            class="bg-slate-800 rounded-2xl rounded-bl-sm px-4 py-3"
                        >
                            <div class="flex items-center gap-1.5">
                                <span
                                    class="w-2 h-2 bg-slate-500 rounded-full animate-bounce"
                                ></span>
                                <span
                                    class="w-2 h-2 bg-slate-500 rounded-full animate-bounce [animation-delay:0.15s]"
                                ></span>
                                <span
                                    class="w-2 h-2 bg-slate-500 rounded-full animate-bounce [animation-delay:0.3s]"
                                ></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- INPUT -->
                <div class="p-4 bg-slate-900">
                    <!-- Sugerencias rápidas -->
                    <div
                        v-if="messages.length <= 1"
                        class="mb-3 flex flex-wrap gap-2"
                    >
                        <button
                            v-for="(action, i) in quickSuggestions"
                            :key="i"
                            @click="fillPrompt(action.text)"
                            class="px-3 py-1.5 rounded-full text-xs bg-slate-800 hover:bg-slate-700 text-slate-400 transition-colors"
                        >
                            {{ action.label }}
                        </button>
                    </div>

                    <!-- Input area -->
                    <div class="flex items-end gap-2">
                        <textarea
                            v-model="prompt"
                            ref="textareaRef"
                            rows="1"
                            placeholder="Escribe un mensaje..."
                            class="flex-1 bg-slate-800 rounded-2xl px-4 py-3 text-sm text-slate-100 placeholder:text-slate-500 outline-none resize-none overflow-hidden transition-all duration-200"
                            @input="autoResize"
                            @keydown.enter.exact.prevent="sendMessage"
                            maxlength="500"
                        ></textarea>

                        <button
                            @click="sendMessage"
                            :disabled="!prompt.trim() || loading"
                            class="w-11 h-11 rounded-2xl bg-white text-slate-900 hover:bg-slate-200 disabled:opacity-30 disabled:cursor-not-allowed flex items-center justify-center transition-all duration-200"
                            type="button"
                        >
                            <i class="pi pi-send text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>
