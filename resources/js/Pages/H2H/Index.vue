<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import axios from "axios";
import { ref } from "vue";

const loading = ref(false);
const result = ref(null);
const progress = ref(0);
const logs = ref([]);
const history = ref([]);

const addLog = (message, type = "info") => {
    logs.value.unshift({
        message,
        type,
        time: new Date().toLocaleTimeString()
    });
};

const retryCount = ref(0);
const maxRetries = 2;

const sendDocuments = async () => {

    if (loading.value) return;

    loading.value = true;
    progress.value = 0;
    logs.value = [];
    result.value = null;

    addLog("🚀 Iniciando proceso...");

    try {
        const interval = setInterval(() => {
            if (progress.value < 90) progress.value += 10;
        }, 500);

        const response = await axios.get('/h2h/send');

        clearInterval(interval);

        result.value = response.data;
        progress.value = 100;

        console.log(result.value)

        addLog(`✅ Proceso terminado. Enviados: ${response.data.processed}`, "success");
        history.value.unshift({
            date: new Date().toLocaleString(),
            ...response.data
        });

        retryCount.value = 0;

    } catch (error) {

        addLog("❌ Error en el proceso", "error");
        if (retryCount.value < maxRetries) {
            retryCount.value++;
            addLog(`🔁 Reintentando (${retryCount.value})...`, "warning");
            loading.value = false;
            return sendDocuments();
        }

    } finally {
        loading.value = false;
    }
};
</script>
<template>
    <AppLayout :title="'H2H'">
        <div class="p-4 lg:p-6">
            <Card>
                <template #title>
                    <div class="flex justify-between items-center">

                        <div>
                            <h2 class="text-2xl font-bold flex items-center gap-2">
                                <i class="pi pi-send text-orange-500"></i>
                                H2H → NetSuite
                            </h2>
                            <p class="text-sm text-gray-500">
                                Envío de documentos y monitoreo
                            </p>
                        </div>

                        <Button
                            :label="loading ? 'Procesando...' : 'Ejecutar Proceso'"
                            icon="pi pi-send"
                            severity="success"
                            :loading="loading"
                            :disabled="loading"
                            class="px-5 py-2 text-sm font-semibold rounded-lg shadow-md"
                            @click="sendDocuments"
                        />

                    </div>
                </template>

                <template #content>

                    <div class="space-y-6">

                        <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                            <div 
                                class="bg-orange-500 h-4 transition-all duration-500"
                                :style="{ width: progress + '%' }"
                            ></div>
                        </div>

                        <div v-if="result" class="grid grid-cols-2 md:grid-cols-4 gap-4">

                            <div class="bg-blue-100 shadow p-4 rounded-xl text-center">
                                <p class="text-blue-700">Total</p>
                                <p class="text-2xl font-bold text-blue-700">{{ result.total }}</p>
                            </div>

                            <div class="bg-green-100 p-4 rounded-xl text-center">
                                <p class="text-green-700">Enviados</p>
                                <p class="text-2xl font-bold text-green-700">{{ result.processed }}</p>
                            </div>

                            <div class="bg-yellow-100 p-4 rounded-xl text-center">
                                <p class="text-yellow-700">Omitidos</p>
                                <p class="text-2xl font-bold text-yellow-700">{{ result.skipped }}</p>
                            </div>

                            <div class="bg-red-100 p-4 rounded-xl text-center">
                                <p class="text-red-700">Errores</p>
                                <p class="text-2xl font-bold text-red-700">{{ result.errors }}</p>
                            </div>

                        </div>


                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-black text-green-400 p-4 rounded-xl h-60 overflow-auto font-mono text-sm">
                                <div v-for="(log, index) in logs" :key="index">
                                    [{{ log.time }}] {{ log.message }}
                                </div>
                            </div>
                            <div class="shadow rounded-xl p-4">
                                <h3 class="font-bold mb-2">Historial</h3>

                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="text-left border-b">
                                            <th>Fecha</th>
                                            <th>Total</th>
                                            <th>Enviados</th>
                                            <th>Errores</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(h, i) in history" :key="i" class="border-b">
                                            <td>{{ h.date }}</td>
                                            <td>{{ h.total }}</td>
                                            <td class="text-green-600">{{ h.processed }}</td>
                                            <td class="text-red-600">{{ h.errors }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>