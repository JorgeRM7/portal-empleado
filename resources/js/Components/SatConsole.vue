<script setup>
import {
    ref,
    computed,
    onBeforeUnmount,
    nextTick,
    onMounted,
    reactive,
} from "vue";

const props = defineProps({
    apiUrl: { type: String, default: "/api/sat-download" },
    heartbeatTimeout: { type: Number, default: 60000 }, // 60s sin actividad = timeout
    branches: { type: Array, default: () => [] },
});

const emit = defineEmits(["complete", "error", "log", "status-change"]);

const dateRange = ref([new Date(), new Date()]);
const branchId = ref(null);

const branches = computed(() => {
    return props.branches;
});

// 🔹 Estado
const logs = ref([]);
const isRunning = ref(false);
const eventSource = ref(null);
const consoleBody = ref(null);
const lastHeartbeat = ref(null);
const heartbeatTimer = ref(null);
const connectionTimeout = ref(null);

// 🔹 Stats
const stats = reactive({
    branches: 0,
    totalCfdi: 0,
    downloaded: 0,
    uploaded: 0,
    errors: 0,
    currentBranch: null,
});

// 🔹 Computed
const statusSeverity = computed(() => {
    if (isRunning.value) return "warn";
    if (stats.errors > 0) return "danger";
    if (logs.value.length > 0) return "success";
    return "secondary";
});

const statusLabel = computed(() => {
    if (isRunning.value)
        return { label: "Ejecutando...", icon: "pi pi-spin pi-spinner" };
    if (stats.errors > 0)
        return { label: "Con errores", icon: "pi pi-exclamation-triangle" };
    if (logs.value.length > 0)
        return { label: "Completado", icon: "pi pi-check-circle" };
    return { label: "Listo", icon: "pi pi-clock" };
});

// 🔹 Inicialización
onMounted(() => {
    // Limpiar estado previo si existe
    cleanupResources();
});

const formatDateForBackend = (date) => {
    if (!date) return null;

    // Si es un objeto Date, convertir a YYYY-MM-DD
    if (date instanceof Date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");
        return `${year}-${month}-${day}`;
    }

    // Si ya es string, retornar tal cual
    return date;
};

// 🔹 Métodos principales
const startDownload = async () => {
    if (isRunning.value) return;

    // Resetear todo
    cleanupResources();
    isRunning.value = true;
    emit("status-change", "running");

    // Timeout de seguridad: si no hay respuesta en 10s, asumir error
    connectionTimeout.value = setTimeout(() => {
        if (isRunning.value && logs.value.length === 0) {
            handleFatalError(
                "⏱️ Timeout: El servidor no respondió en 10 segundos",
            );
        }
    }, 10000);

    try {
        const params = new URLSearchParams();
        params.append("branch_id", branchId.value || null);
        params.append("date_to", formatDateForBackend(dateRange.value[1]));
        params.append("date_from", formatDateForBackend(dateRange.value[0]));

        const url = props.apiUrl.includes("?")
            ? `${props.apiUrl}&${params.toString()}`
            : `${props.apiUrl}?${params.toString()}`;
        // Crear EventSource con configuración explícita
        eventSource.value = new EventSource(url, {
            withCredentials: false,
        });

        // Configurar handlers
        setupEventSourceHandlers();

        // Iniciar heartbeat monitor
        startHeartbeatMonitor();
    } catch (error) {
        handleFatalError(`❌ Error al iniciar conexión: ${error.message}`);
    }
};

const setupEventSourceHandlers = () => {
    if (!eventSource.value) return;

    eventSource.value.onopen = () => {
        addLog(
            "🔗 Conectado al servidor",
            "success",
            new Date().toLocaleTimeString("es-MX"),
        );
        updateHeartbeat();
        // Cancelar timeout de conexión inicial
        if (connectionTimeout.value) {
            clearTimeout(connectionTimeout.value);
            connectionTimeout.value = null;
        }
    };

    eventSource.value.onmessage = (event) => {
        updateHeartbeat();

        try {
            // Intentar parsear como JSON primero
            let data;
            try {
                data = JSON.parse(event.data);
            } catch {
                // Si no es JSON, tratar como log plano
                addLog(
                    event.data,
                    "info",
                    new Date().toLocaleTimeString("es-MX"),
                );
                return;
            }

            // Procesar según tipo
            if (data.type === "log") {
                handleLogMessage(data.message, data.timestamp);
            } else if (data.type === "heartbeat") {
                // Mensaje de heartbeat del backend (opcional)
                updateHeartbeat();
            } else if (data.type === "complete") {
                handleComplete(data.stats || null);
            } else if (data.type === "error") {
                handleBackendError(data.message);
            } else {
                // Fallback: tratar como log info
                addLog(
                    event.data,
                    "info",
                    data.timestamp || new Date().toLocaleTimeString("es-MX"),
                );
            }
        } catch (e) {
            console.error("Error procesando mensaje SSE:", e);
            addLog(
                `⚠️ Error procesando mensaje: ${e.message}`,
                "warning",
                new Date().toLocaleTimeString("es-MX"),
            );
        }
    };

    eventSource.value.onerror = (err) => {
        console.error("EventSource error:", err);

        // EventSource.onerror se dispara también al cerrar, verificar readyState
        if (eventSource.value?.readyState === EventSource.CLOSED) {
            // Cierre normal
            if (isRunning.value) {
                handleComplete();
            }
        } else {
            // Error real de conexión
            handleFatalError(
                "🔌 Error de conexión con el servidor, intenta de nuevo",
            );
        }
    };
};

const handleLogMessage = (message, timestamp) => {
    addLog(message, "info", timestamp);
    updateStatsFromMessage(message);
    emit("log", { message, timestamp });
};

const handleBackendError = (message) => {
    addLog(`❌ ${message}`, "error", new Date().toLocaleTimeString("es-MX"));
    stats.errors++;
    emit("error", new Error(message));
};

const handleFatalError = (message) => {
    addLog(message, "error", new Date().toLocaleTimeString("es-MX"));
    stopAll();
    emit("error", new Error(message.replace("❌ ", "")));
};

const handleComplete = (finalStats = null) => {
    if (finalStats) {
        Object.assign(stats, finalStats);
    }

    addLog(
        "🎉 Proceso finalizado",
        "success",
        new Date().toLocaleTimeString("es-MX"),
    );
    stopAll();
    emit("complete", { ...stats });
};

const updateStatsFromMessage = (message) => {
    const matchers = {
        branch: /Procesando branch (\d+)/,
        cfdi: /Encontrados (\d+) CFDI/,
        downloaded: /Descargados (\d+) XML/,
        uploaded: /Subido (XML|PDF):/,
        saved: /Registrado en BD:/,
        error: /❌|Error|Fallo|inválida|excepción/i,
        completeBranch: /✅ Branch \d+ completado/,
    };

    if (message.match(matchers.branch)) {
        stats.branches++;
        stats.currentBranch = message.match(matchers.branch)[1];
    }
    if (message.match(matchers.cfdi))
        stats.totalCfdi = parseInt(message.match(matchers.cfdi)[1]);
    if (message.match(matchers.downloaded))
        stats.downloaded = parseInt(message.match(matchers.downloaded)[1]);
    if (message.match(matchers.uploaded) || message.match(matchers.saved))
        stats.uploaded++;
    if (message.match(matchers.error)) stats.errors++;
    if (message.match(matchers.completeBranch)) stats.currentBranch = null;
};

const startHeartbeatMonitor = () => {
    lastHeartbeat.value = Date.now();

    heartbeatTimer.value = setInterval(() => {
        const elapsed = Date.now() - (lastHeartbeat.value || 0);

        // if (isRunning.value && elapsed > props.heartbeatTimeout) {
        //     handleFatalError(
        //         `⏱️ Sin actividad por ${Math.round(elapsed / 1000)}s - Conexión perdida, intenta de nuevo`,
        //     );
        // }
    }, 5000);
};

const updateHeartbeat = () => {
    lastHeartbeat.value = Date.now();
};

// 🔹 Limpieza de recursos
const stopAll = () => {
    isRunning.value = false;
    emit("status-change", "idle");

    // Limpiar timers
    if (heartbeatTimer.value) {
        clearInterval(heartbeatTimer.value);
        heartbeatTimer.value = null;
    }
    if (connectionTimeout.value) {
        clearTimeout(connectionTimeout.value);
        connectionTimeout.value = null;
    }

    // Cerrar EventSource explícitamente
    if (eventSource.value) {
        eventSource.value.close();
        eventSource.value = null;
    }
};

const cleanupResources = () => {
    stopAll();
    logs.value = [];
    Object.assign(stats, {
        branches: 0,
        totalCfdi: 0,
        downloaded: 0,
        uploaded: 0,
        errors: 0,
        currentBranch: null,
    });
};

// 🔹 Métodos de UI
const addLog = (message, type = "info", timestamp = null) => {
    logs.value.push({
        message,
        type,
        timestamp: timestamp || new Date().toLocaleTimeString("es-MX"),
    });
    scrollToBottom();
};

const formatMessage = (message) => {
    return message
        .replace(
            /(✅|🚀|📦|⬇️|📤|💾|🔄|⏭️|📊|🎉|⚠️|❌|🔍|📋|⏳)/g,
            '<span class="emoji">$1</span>',
        )
        .replace(
            /(branch \d+|CFDI|XML|PDF|BD|FIEL|SAT|Spaces)/gi,
            '<span class="keyword">$1</span>',
        )
        .replace(
            /(Error|Fallo|inválida|bloqueada|timeout|excepción)/gi,
            '<span class="error-text">$1</span>',
        );
};

const clearConsole = () => {
    logs.value = [];
};

const scrollToBottom = async () => {
    await nextTick();
    if (consoleBody.value) {
        consoleBody.value.scrollTop = consoleBody.value.scrollHeight;
    }
};

// 🔹 Lifecycle
onBeforeUnmount(() => {
    cleanupResources();
});

// 🔹 Exponer
defineExpose({
    startDownload,
    clearConsole,
    isRunning,
    stats,
    logs,
    stop: stopAll,
});
</script>

<template>
    <div class="card sat-downloader">
        <!-- 🔹 Título principal -->
        <div class="page-header">
            <h2 class="page-title">
                <i class="pi pi-file-pdf mr-2"></i>
                Descarga de CFDI
            </h2>
            <Tag
                :value="statusLabel.label"
                :severity="statusSeverity"
                :icon="statusLabel.icon"
                size="small"
            />
        </div>

        <!-- 🔹 Toolbar con controles (PrimeVue) -->
        <Toolbar class="console-toolbar mb-3">
            <template #start>
                <ButtonGroup>
                    <Button
                        @click="startDownload"
                        :disabled="isRunning"
                        :icon="
                            isRunning ? 'pi pi-spin pi-spinner' : 'pi pi-play'
                        "
                        :label="
                            isRunning ? 'Ejecutando...' : 'Iniciar Descarga'
                        "
                        severity="success"
                        size="small"
                    />
                    <Button
                        @click="clearConsole"
                        :disabled="isRunning || logs.length === 0"
                        icon="pi pi-trash"
                        label="Limpiar"
                        severity="secondary"
                        size="small"
                    />

                    <DatePicker
                        v-model="dateRange"
                        :disabled="isRunning"
                        selectionMode="range"
                        :manualInput="false"
                        placeholder="Seleccionar rango de fechas"
                        size="small"
                        class="mx-2"
                    />

                    <Select
                        v-model="branchId"
                        :options="branches"
                        :disabled="isRunning"
                        optionLabel="code"
                        optionValue="id"
                        placeholder="Seleccionar planta"
                        filter
                    />
                </ButtonGroup>
            </template>

            <template #center>
                <!-- 🔹 Stats visibles SIEMPRE (fuera de la consola) -->
                <div class="stats-bar">
                    <Tag
                        v-if="stats.branches > 0"
                        :value="`🏢 ${stats.branches} Sucursal(es)`"
                        severity="info"
                        size="small"
                        class="stat-tag"
                    />
                    <Tag
                        v-if="stats.totalCfdi > 0"
                        :value="`📄 ${stats.totalCfdi} CFDI encontrados`"
                        severity="info"
                        size="small"
                        class="stat-tag"
                    />
                    <Tag
                        v-if="stats.downloaded > 0"
                        :value="`⬇️ ${stats.downloaded} Descargados`"
                        severity="success"
                        size="small"
                        class="stat-tag"
                    />
                    <Tag
                        v-if="stats.uploaded > 0"
                        :value="`☁️ ${stats.uploaded} Subidos`"
                        severity="success"
                        size="small"
                        class="stat-tag"
                    />
                    <Tag
                        v-if="stats.errors > 0"
                        :value="`⚠️ ${stats.errors} Errores`"
                        severity="danger"
                        size="small"
                        class="stat-tag"
                    />
                </div>
            </template>

            <template #end>
                <div class="toolbar-end">
                    <span v-if="stats.currentBranch" class="current-branch">
                        <i
                            class="pi pi-circle-fill text-green-500 mr-1 animate-pulse"
                        ></i>
                        Planta #{{ stats.currentBranch }}
                    </span>
                    <ProgressSpinner
                        v-if="isRunning"
                        style="width: 20px; height: 20px"
                        strokeWidth="4"
                        fill="transparent"
                        class="ml-2"
                    />
                </div>
            </template>
        </Toolbar>

        <!-- 🔹 Consola SOLO para logs (limpia) -->
        <div class="console-container">
            <div ref="consoleBody" class="console-body">
                <!-- Estado vacío -->
                <div
                    v-if="logs.length === 0 && !isRunning"
                    class="console-empty"
                >
                    <i class="pi pi-terminal text-5xl text-gray-600 mb-4"></i>
                    <p class="text-lg">Consola lista</p>
                    <p class="text-sm mt-1">
                        Presiona "Iniciar Descarga" para comenzar el proceso
                    </p>
                </div>

                <!-- Logs en tiempo real -->
                <div
                    v-for="(log, index) in logs"
                    :key="index"
                    class="log-line"
                    :class="log.type"
                >
                    <span class="log-time">[{{ log.timestamp }}]</span>
                    <span
                        class="log-message"
                        v-html="formatMessage(log.message)"
                    ></span>
                </div>

                <!-- Cursor animado -->
                <div v-if="isRunning" class="log-line typing">
                    <span class="log-time"
                        >[{{ new Date().toLocaleTimeString("es-MX") }}]</span
                    >
                    <span class="cursor">▋</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* 🔹 Contenedor principal */
.sat-downloader {
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 500px;
    max-height: 500px;
}

/* 🔹 Header con título */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0 1rem 0;
    margin-bottom: 0.5rem;
}

.page-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
}

/* 🔹 Toolbar personalizada */
.console-toolbar {
    border-radius: 0.5rem !important;
    padding: 0.5rem 1rem !important;
}

:deep(.p-toolbar) {
    padding: 0.5rem 1rem !important;
}

.stats-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    justify-content: center;
}

.stat-tag {
    margin: 0 !important;
}

.toolbar-end {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.current-branch {
    font-size: 0.75rem;
    color: #64748b;
    display: flex;
    align-items: center;
}

/* 🔹 Contenedor de consola */
.console-container {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
    background: #1e1e1e;
    border-radius: 0.5rem;
    border: 1px solid #333;
    overflow: hidden;
}

/* 🔹 Área de logs con scroll */
.console-body {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 1rem;
    background: #1e1e1e;
    color: #d4d4d4;
    font-family: "Fira Code", "Consolas", monospace;
    font-size: 0.8125rem;
    line-height: 1.6;
}

/* Estado vacío */
.console-empty {
    text-align: center;
    padding: 4rem 2rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
}

/* Líneas de log */
.log-line {
    display: flex;
    gap: 0.75rem;
    padding: 0.125rem 0;
    white-space: pre-wrap;
    word-break: break-word;
}

.log-time {
    color: #6a9955;
    flex-shrink: 0;
    font-size: 0.6875rem;
    font-family: monospace;
    opacity: 0.8;
}

.log-message {
    flex: 1;
    min-width: 0;
}

/* Colores por tipo */
.log-line.info .log-message {
    color: #d4d4d4;
}
.log-line.success .log-message {
    color: #6adb8e;
}
.log-line.warning .log-message {
    color: #ffd77b;
}
.log-line.error .log-message {
    color: #f48771;
}

/* Highlight de elementos */
.emoji {
    margin-right: 0.25rem;
}
.keyword {
    color: #569cd6;
    font-weight: 500;
}
.error-text {
    color: #f48771;
    font-weight: 500;
}

/* Cursor animado */
.log-line.typing .cursor {
    animation: blink 1s infinite;
    color: #6adb8e;
    font-weight: bold;
}
@keyframes blink {
    0%,
    50% {
        opacity: 1;
    }
    51%,
    100% {
        opacity: 0;
    }
}

/* Scrollbar */
.console-body::-webkit-scrollbar {
    width: 6px;
}
.console-body::-webkit-scrollbar-track {
    background: #1e1e1e;
}
.console-body::-webkit-scrollbar-thumb {
    background: #444;
    border-radius: 3px;
}
.console-body::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .stats-bar {
        justify-content: flex-start;
    }

    .console-toolbar :deep(.p-toolbar-start),
    .console-toolbar :deep(.p-toolbar-end) {
        flex: 100%;
        justify-content: flex-start;
    }

    .console-toolbar :deep(.p-toolbar-center) {
        order: 3;
        margin-top: 0.5rem;
        width: 100%;
    }
}
</style>
