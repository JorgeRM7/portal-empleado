<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useToast } from "primevue/usetoast";

import AppLayout from "@/Layouts/AppLayout.vue";
import SatConsole from "@/Components/SatConsole.vue";

const props = defineProps({
    branches: { type: Array, default: () => [] },
});

const router = useRouter();
const toast = useToast();
const consoleRef = ref(null);

// 🔹 Handlers para eventos del console
const handleComplete = (data) => {
    toast.add({
        severity: "success",
        summary: "Descarga completada",
        detail: `✅ ${data.downloaded} CFDI procesados correctamente`,
        life: 5000,
    });
};

const handleError = (error) => {
    toast.add({
        severity: "error",
        summary: "Error en descarga",
        detail: error.message || "Ocurrió un error inesperado",
        life: 7000,
    });
};

const handleLog = (log) => {
    // Opcional: procesar logs individualmente si necesitas
    // console.log('Log recibido:', log)
};
</script>

<template>
    <AppLayout title="Descarga de CFDI">
        <div class="page-container">
            <!-- 🔹 Tarjeta principal con el console -->
            <div class="card">
                <SatConsole
                    ref="consoleRef"
                    api-url="/api/sat-download"
                    :branches="branches"
                    @complete="handleComplete"
                    @error="handleError"
                    @log="handleLog"
                />
            </div>

            <!-- 🔹 Info adicional fuera del console (opcional) -->
            <div class="info-section mt-4">
                <div class="card">
                    <h4>
                        <i class="pi pi-info-circle mr-2"></i>
                        Información del proceso
                    </h4>
                    <ul class="info-list">
                        <li>
                            🔐 Se utiliza la FIEL registrada de cada planta para
                            autenticación con el SAT
                        </li>
                        <li>
                            ⏱️ El proceso puede tardar varios minutos
                            dependiendo del volumen
                        </li>
                        <li>
                            ❎ Si da error al descargar los CFDI, espera 5
                            minutos y vuelve a intentarlo
                        </li>
                        <li>
                            🔄 Si no se seleccionan fechas, se descargarán los
                            CFDI del día actual
                        </li>
                        <li>
                            💡 Si no se selecciona una sucursal, no se
                            descargaran los CFDI
                        </li>
                        <li>
                            💡 Si se va a descargar el mes completo, de
                            preferencia hacerlo de semana en semana para evitar
                            errores de conexión con el SAT
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.page-container {
    padding: 1rem;
}

.card {
    border-radius: 0.5rem;
    padding: 1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.info-section {
    max-width: 800px;
    margin: 0 auto;
}

.info-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 1rem;
}

.info-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 0.75rem 0;
    display: flex;
    align-items: center;
}

.info-list {
    list-style: none;
    padding: 0;
    margin: 0;
    font-size: 0.875rem;
    color: #64748b;
}

.info-list li {
    padding: 0.25rem 0;
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
}

.info-list li::before {
    content: "•";
    color: #22c55e;
    font-weight: bold;
    margin-right: 0.25rem;
}
</style>
