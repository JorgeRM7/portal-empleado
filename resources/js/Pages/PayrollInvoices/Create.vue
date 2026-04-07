<script setup>
import { computed, ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToast } from "primevue/usetoast";
import { router, useForm } from "@inertiajs/vue3";

const props = defineProps({
    branchOffices: Array,
    payRollInvoiceType: Array,
});

const toast = useToast();

// Estados reactivos
const selectedPlant = ref(null);
const selectedWeek = ref(null);
const selectedReceiptType = ref(null);
const uploadedFile = ref(null);
const uploading = ref(false);
const uploadProgress = ref(0);
const uploadResult = ref(null);

const form = useForm({
    document: null,
    branchoffice: null,
    week: null,
    receipt_type: null,
});

// Manejar selección de archivo
const onFileSelect = (event) => {
    const files = event.files;
    if (files && files.length > 0) {
        uploadedFile.value = files[0];
    }
};

// Subir archivo
const uploadFile = async () => {
    // Validación
    if (!form.branchoffice) {
        toast.add({
            severity: "warn",
            summary: "Validación",
            detail: "Seleccione una planta",
            life: 3000,
        });
        return;
    }

    if (!form.week) {
        toast.add({
            severity: "warn",
            summary: "Validación",
            detail: "Seleccione una semana",
            life: 3000,
        });
        return;
    }

    if (!form.receipt_type) {
        toast.add({
            severity: "warn",
            summary: "Validación",
            detail: "Seleccione un tipo de recibo",
            life: 3000,
        });
        return;
    }

    if (!uploadedFile.value) {
        toast.add({
            severity: "warn",
            summary: "Validación",
            detail: "Seleccione un archivo",
            life: 3000,
        });
        return;
    }

    // Iniciar subida
    uploading.value = true;
    uploadProgress.value = 0;

    // Simular progreso de subida
    const progressInterval = setInterval(() => {
        if (uploadProgress.value < 90) {
            uploadProgress.value += 10;
        }
    }, 200);

    try {
        form.document = uploadedFile.value;
        const response = await axios.post("/payroll/payroll-invoices", form, {
            headers: { "Content-Type": "multipart/form-data" },
            onUploadProgress: (progressEvent) => {
                uploadProgress.value = Math.round(
                    (progressEvent.loaded * 100) / progressEvent.total,
                );
            },
        });

        console.log(response);

        clearInterval(progressInterval);
        uploadProgress.value = 100;

        uploadResult.value = response.data;

        // Toast para notificación general
        toast.add({
            severity:
                response.data.severity === "warning"
                    ? "warn"
                    : response.data.severity,
            summary:
                response.data.severity === "success" ? "Éxito" : "Advertencia",
            detail: response.data.message,
            life: 5000,
        });

        // Limpiar formulario después de 1 segundo
        setTimeout(() => {
            resetForm();
        }, 1000);
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Error al subir el archivo",
            life: 3000,
        });
        console.error(error);
        clearInterval(progressInterval);
    } finally {
        uploading.value = false;
        setTimeout(() => {
            uploadProgress.value = 0;
        }, 500);
    }
};

// Resetear formulario
const resetForm = () => {
    selectedPlant.value = null;
    selectedWeek.value = null;
    selectedReceiptType.value = null;
    uploadedFile.value = null;
    uploadProgress.value = 0;
};

// Obtener nombre del archivo
const getFileName = () => {
    return uploadedFile.value
        ? uploadedFile.value.name
        : "Sin archivos seleccionados";
};

// Obtener clase para el estado del archivo
const getFileStatusClass = () => {
    return uploadedFile.value ? "text-green-500" : "text-gray-400";
};

const getMessageSeverity = computed(() => {
    if (!uploadResult.value) return "info";
    if (uploadResult.value.severity === "error") return "error";
    if (uploadResult.value.total_failidos > 0) return "warn";
    return "success";
});

// Obtener título del Message
const getMessageTitle = computed(() => {
    if (!uploadResult.value) return "";
    if (uploadResult.value.severity === "error") return "Error Crítico";
    if (uploadResult.value.total_failidos > 0) {
        return `⚠️ ${uploadResult.value.total_failidos} archivo(s) con errores`;
    }
    return `✅ ${uploadResult.value.total_guardados} archivo(s) guardado(s)`;
});
</script>

<template>
    <AppLayout title="Subir Recibos">
        <!-- Contenedor principal a ancho completo -->
        <div class="card">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold">Subir recibos</h1>
                <p class="text-gray-500 dark:text-gray-400 mt-2">
                    Carga los archivos comprimidos con los recibos de los
                    empleados
                </p>
            </div>

            <!-- Card del formulario -->
            <div class="w-full rounded-xl">
                <div>
                    <!-- Grid del formulario -->
                    <div class="grid lg:grid-cols-4 gap-6">
                        <!-- Planta (Full width en mobile, 2 columnas en desktop) -->
                        <div class="col-span-2 lg:col-span-4">
                            <label for="plant">
                                Planta <span class="text-red-500">*</span>
                            </label>
                            <Select
                                filter
                                id="plant"
                                v-model="form.branchoffice"
                                :options="branchOffices"
                                optionLabel="code"
                                option-value="id"
                                placeholder="Selecciona una planta..."
                                class="w-full mt-2"
                                :disabled="uploading"
                            />
                        </div>

                        <!-- Semana -->
                        <div class="col-span-2">
                            <label for="week">
                                Semana <span class="text-red-500">*</span>
                            </label>
                            <!-- <Dropdown
                                id="week"
                                v-model="form.week"
                                :options="weeks"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Selecciona..."
                                class="w-full mt-2"
                                :disabled="uploading"
                            /> -->
                            <InputText
                                type="week"
                                v-model="form.week"
                                class="w-full mt-2"
                            />
                        </div>

                        <!-- Tipo de recibo -->
                        <div class="col-span-2">
                            <label for="receiptType">
                                Tipo de recibo
                                <span class="text-red-500">*</span>
                            </label>
                            <Dropdown
                                id="receiptType"
                                v-model="form.receipt_type"
                                :options="payRollInvoiceType"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Selecciona..."
                                class="w-full mt-2"
                                :disabled="uploading"
                            />
                        </div>

                        <!-- Área de subida de archivo (Full width) -->
                        <div class="md:col-span-2 lg:col-span-4">
                            <label>
                                Archivo comprimido
                                <span class="text-red-500">*</span>
                            </label>

                            <!-- Área de upload con borde punteado -->
                            <div
                                class="relative border-1 mt-2 rounded-xl p-8 transition-all duration-300"
                            >
                                <!-- Icono de archivo -->
                                <div
                                    class="flex flex-col items-center justify-center"
                                >
                                    <div
                                        class="w-16 h-16 rounded-full flex items-center justify-center mb-4 transition-colors duration-300"
                                    >
                                        <svg
                                            v-if="!uploadedFile"
                                            class="w-8 h-8 text-gray-400"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                                            />
                                        </svg>
                                        <svg
                                            v-else
                                            class="w-8 h-8 text-green-500"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </div>

                                    <!-- Texto descriptivo -->
                                    <p
                                        class="text-sm text-gray-500 dark:text-gray-400 mb-4"
                                    >
                                        Selecciona un archivo comprimido (.zip o
                                        .rar)
                                    </p>

                                    <!-- Botón de selección de archivo -->
                                    <FileUpload
                                        mode="basic"
                                        :auto="false"
                                        accept=".zip,.rar"
                                        :maxFileSize="1000000000000000"
                                        @select="onFileSelect"
                                        :disabled="uploading"
                                        chooseLabel="Seleccionar archivo"
                                        class="mb-4"
                                    >
                                        <template #content>
                                            <div></div>
                                        </template>
                                    </FileUpload>

                                    <!-- Nombre del archivo seleccionado -->
                                    <div class="flex items-center gap-2 mt-2">
                                        <span
                                            class="text-sm font-medium"
                                            :class="getFileStatusClass()"
                                        >
                                            {{ getFileName() }}
                                        </span>
                                        <span
                                            v-if="uploadedFile"
                                            class="text-xs text-gray-400"
                                        >
                                            ({{
                                                (
                                                    uploadedFile.size / 1024
                                                ).toFixed(2)
                                            }}
                                            KB)
                                        </span>
                                    </div>
                                </div>

                                <!-- Barra de progreso -->
                                <div
                                    v-if="uploading || uploadProgress > 0"
                                    class="mt-6"
                                >
                                    <div
                                        class="flex items-center justify-between mb-2"
                                    >
                                        <span
                                            class="text-sm font-medium animate-pulse"
                                        >
                                            {{
                                                uploading
                                                    ? "Subiendo archivo..."
                                                    : "Completado"
                                            }}
                                        </span>
                                        <span
                                            class="text-sm font-semibold text-gray-700 dark:text-gray-300"
                                        >
                                            {{ uploadProgress }}%
                                        </span>
                                    </div>
                                    <ProgressBar
                                        :value="uploadProgress"
                                        class="h-3 rounded-full"
                                    />
                                </div>
                            </div>

                            <!-- Información de formatos soportados -->
                            <div
                                class="mt-3 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                <span
                                    >Formatos soportados: .zip, .rar (Máx.
                                    10MB)</span
                                >
                            </div>
                            <Message
                                v-if="uploadResult"
                                :severity="getMessageSeverity"
                                :closable="true"
                                @close="uploadResult = null"
                                class="mt-4"
                            >
                                <template #header>
                                    <div
                                        class="flex items-center gap-2 font-semibold text-lg"
                                    >
                                        <span
                                            v-if="
                                                getMessageSeverity === 'success'
                                            "
                                            >✅</span
                                        >
                                        <span
                                            v-else-if="
                                                getMessageSeverity === 'warn'
                                            "
                                            >⚠️</span
                                        >
                                        <span v-else>❌</span>
                                        {{ getMessageTitle }}
                                    </div>
                                </template>

                                <!-- Resumen -->
                                <div class="mb-3">
                                    <p class="text-sm">
                                        {{ uploadResult.message }}
                                    </p>
                                </div>

                                <!-- Lista de archivos guardados -->
                                <div
                                    v-if="
                                        uploadResult.archivos_guardados &&
                                        uploadResult.archivos_guardados.length >
                                            0
                                    "
                                    class="mb-3"
                                >
                                    <details class="text-sm">
                                        <summary
                                            class="cursor-pointer text-green-600 dark:text-green-400 font-medium hover:underline"
                                        >
                                            📁 Archivos guardados ({{
                                                uploadResult.archivos_guardados
                                                    .length
                                            }})
                                        </summary>
                                        <ul
                                            class="mt-2 space-y-1 ml-4 max-h-40 overflow-y-auto"
                                        >
                                            <li
                                                v-for="archivo in uploadResult.archivos_guardados"
                                                :key="archivo.ruta"
                                                class="text-gray-700 dark:text-gray-300 flex items-center gap-2"
                                            >
                                                <span class="text-green-500"
                                                    >✓</span
                                                >
                                                <span class="truncate">{{
                                                    archivo.nombre
                                                }}</span>
                                                <span
                                                    class="text-xs text-gray-400"
                                                    >({{
                                                        (
                                                            archivo.tamaño /
                                                            1024
                                                        ).toFixed(1)
                                                    }}
                                                    KB)</span
                                                >
                                            </li>
                                        </ul>
                                    </details>
                                </div>

                                <!-- Lista de archivos fallidos -->
                                <div
                                    v-if="
                                        uploadResult.archivos_failidos &&
                                        uploadResult.archivos_failidos.length >
                                            0
                                    "
                                    class="mt-3"
                                    severity="error"
                                >
                                    <details close class="text-sm">
                                        <summary
                                            class="cursor-pointer text-red-600 font-medium hover:underline"
                                        >
                                            🚫 Archivos con errores ({{
                                                uploadResult.archivos_failidos
                                                    .length
                                            }})
                                        </summary>
                                        <ul
                                            class="mt-2 space-y-2 ml-4 max-h-60 overflow-y-auto"
                                        >
                                            <li
                                                v-for="archivo in uploadResult.archivos_failidos"
                                                :key="archivo.nombre"
                                                class="text-gray-700 dark:text-gray-300"
                                            >
                                                <div
                                                    class="flex items-start gap-2"
                                                >
                                                    <span
                                                        class="text-red-500 mt-0.5"
                                                        >✗</span
                                                    >
                                                    <div>
                                                        <p class="font-medium">
                                                            {{ archivo.nombre }}
                                                        </p>
                                                        <p
                                                            class="text-xs text-red-500 mt-1"
                                                        >
                                                            {{ archivo.error }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </details>
                                </div>

                                <!-- Instrucciones para archivos fallidos -->
                                <Message
                                    v-if="
                                        uploadResult.archivos_failidos &&
                                        uploadResult.archivos_failidos.length >
                                            0
                                    "
                                    severity="info"
                                    class="mt-5"
                                >
                                    <p class="text-xs">
                                        <strong>💡 Solución:</strong> Asegúrate
                                        que el nombre del archivo incluya el ID
                                        del empleado después de un guion bajo.
                                        <br />
                                        <span>
                                            <strong
                                                >Ejemplo válido:
                                                documento_12345.pdf</strong
                                            >
                                        </span>
                                    </p>
                                </Message>
                            </Message>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div
                        class="flex flex-col sm:flex-row gap-4 justify-end mt-8 pt-6 border-t border-gray-200 dark:border-gray-700"
                    >
                        <Button
                            label="Cancelar"
                            severity="secondary"
                            outlined
                            @click="
                                () => {
                                    router.get('/payroll/payroll-invoices');
                                }
                            "
                            :disabled="uploading"
                            class="border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700"
                        />
                        <Button
                            label="Guardar"
                            icon="pi pi-save"
                            @click="uploadFile"
                            :loading="uploading"
                            :disabled="uploading"
                            class="bg-blue-600 hover:bg-blue-700 text-white border-none"
                        />
                    </div>
                </div>
            </div>

            <!-- Toast Notifications -->
            <Toast />
        </div>
    </AppLayout>
</template>

<style>
/* Animación de carga */
@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.6;
    }
}

.animate-pulse {
    animation: pulse 1.5s infinite;
}
</style>
