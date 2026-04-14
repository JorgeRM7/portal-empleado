<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToastService } from "@/Stores/toastService";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import { onMounted, ref, watch, computed } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { useNotifications } from "@/composables/useNotifications";
import { usePrimeVue } from 'primevue/config';
import { useConfirm } from "primevue/useconfirm";
import ConfirmDialog from 'primevue/confirmdialog';
import { nextTick } from 'vue';

const props = defineProps({
    history: Array
});

const confirm = useConfirm();

const texto = ref("");
const opciones = ref([]);
const complaintDescription = ref("");
// const Case = ref("");
const seleccionada = ref(null);
const loading = ref(false);
const $primevue = usePrimeVue();
const toast = useToast();
const totalSize = ref(0);
const totalSizePercent = ref(0);
const files = ref([]);
const MAX_SIZE = 5000000;
const isCollapsed = ref(false);
const selectedOption = ref(null);
const otherSubject = ref('');
const fileUploadRef = ref(null);
const isReverting = ref(false);
const sending = ref(false);
//variables del modal
const showEditModal = ref(false);
const editandoId = ref(null);
const editSelectedOption = ref(null);
const editOtherSubject = ref('');
const editComplaintDescription = ref('');
const editFiles = ref([]);
const editExistingFiles = ref([]);
const editNewFiles = ref([]);
const editRemovedFiles = ref([]);

const options = ref([
    { name: 'Seleccione un Asunto...', code: '' },
    { name: 'Nomina', code: 'NOM' },
    { name: 'Compensaciones', code: 'COM' },
    { name: 'Tiempos extra', code: 'EXT' },
    { name: 'Aclaración de Incidencias', code: 'ACI' },
    { name: 'Cambio de datos personales', code: 'CDP' },
    { name: 'Cambio de datos bancarios', code: 'CDB' },
    { name: 'Vacaciones', code: 'VAC' },
    { name: 'Descuento de créditos y pensiones', code: 'DCP' },
    { name: 'Despensas y Vales de despensa', code: 'DVD' },
    { name: 'Incentivos anuales', code: 'INC' },
    { name: 'Constancias (Fonacot, Infonavit, Antigüedad, Visa, Escolar, Otro tipo)', code: 'CON' },
    { name: 'Otros', code: 'OTR' }
]);

watch(selectedOption, async (newVal, oldVal) => {
    if (isReverting.value) {
        isReverting.value = false;
        return;
    }

    if (!oldVal || newVal === oldVal) return;

    const tieneContenido = complaintDescription.value.trim() !== '' ||
        otherSubject.value.trim() !== '' ||
        files.value.length > 0;

    if (tieneContenido) {
        confirm.require({
            message: 'Si cambia el asunto, se borrará la descripción y los archivos cargados. ¿Desea continuar?',
            header: 'Confirmación de Cambio',
            icon: 'pi pi-exclamation-triangle',
            rejectProps: {
                label: 'Cancelar',
                severity: 'secondary',
                outlined: true
            },
            acceptProps: {
                label: 'Aceptar',
                severity: 'danger'
            },
            accept: () => {
                // Lógica de limpieza
                complaintDescription.value = "";
                otherSubject.value = "";
                opciones.value = [];
                files.value = [];
                totalSize.value = 0;
                totalSizePercent.value = 0;

                if (fileUploadRef.value) {
                    fileUploadRef.value.clear();
                }
            },
            reject: async () => {
                isReverting.value = true;
                await nextTick();
                selectedOption.value = oldVal;
            }
        });
    }
});

const reportesRecientes = computed(() => {
    return props.history
        .map(reporte => {
            // Lógica de colores (puedes usar un objeto para que sea más limpio)
            const colors = {
                'Pendiente': '#b91c1c',
                'En Revisión': '#2563eb',
                'Resuelto': '#4b5563'
            };

            const statusActual = reporte.status || 'Pendiente';

            return {
                id: reporte.id,
                titulo: reporte.case,
                fecha: formatearFecha(reporte.date),
                status: statusActual,
                statusColor: colors[statusActual] || '#b91c1c',
                rawStatus: statusActual,
                archivos: reporte.archivos || []
            };
        })
        .slice(0, 5);
});


const editarReporte = (id) => {
    const reporte = props.history.find(r => r.id === id);
    if (!reporte) return;

    editandoId.value = reporte.id;

    // Asunto
    const optionEncontrada = options.value.find(opt =>
        opt.name.toLowerCase() === (reporte.case || '').toLowerCase()
    );

    if (optionEncontrada) {
        editSelectedOption.value = optionEncontrada;
        editOtherSubject.value = '';
    } else {
        editSelectedOption.value = options.value.find(opt => opt.code === 'OTR');
        editOtherSubject.value = reporte.case;
    }

    // Descripción
    editComplaintDescription.value = reporte.subject || "";

    editExistingFiles.value = (reporte.archivos || []).map(file => ({
        ...file,
        preview: file.url
    }));

    editNewFiles.value = [];
    editRemovedFiles.value = [];

    showEditModal.value = true;
};

const actualizarReporte = async () => {
    try {
        const formData = new FormData();

        formData.append('asunto_cod', editSelectedOption.value.code);
        formData.append(
            'asunto_texto',
            editSelectedOption.value.code === 'OTR'
                ? editOtherSubject.value
                : editSelectedOption.value.name
        );
        formData.append('descripcion', editComplaintDescription.value);

        // 🔥 nuevos archivos
        editNewFiles.value.forEach(file => {
            formData.append('archivos[]', file);
        });

        // 🔥 archivos eliminados
        formData.append('removed_files', JSON.stringify(editRemovedFiles.value));

        await axios.post(`/complaints/${editandoId.value}`, formData);

        toast.add({
            severity: 'success',
            summary: 'Actualizado',
            detail: 'Reporte actualizado correctamente',
            life: 3000
        });

        showEditModal.value = false;

    } catch (error) {
        console.error(error);
    }
};

const showEditFileUpload = computed(() => {
    if (!editSelectedOption.value) return false;

    const allowedCodes = ['CDB', 'CDP', 'DCP', 'CON', 'OTR'];

    return allowedCodes.includes(editSelectedOption.value.code);
});

watch(editSelectedOption, (newVal) => {
    if (!newVal) return;

    const allowedCodes = ['CDB', 'CDP', 'DCP', 'CON', 'OTR'];

    if (!allowedCodes.includes(newVal.code)) {
        // limpiar todo
        editExistingFiles.value = [];
        editNewFiles.value = [];
        editRemovedFiles.value = [];
    }
});

const eliminarReporte = (id) => {
    if(confirm('¿Estás seguro de eliminar este reporte?')) {
        // Tu lógica de router.delete o axios.delete
    }
};

// Helper para que la fecha se vea como "13 Abr"
const formatearFecha = (fechaStr) => {
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    const fecha = new Date(fechaStr);
    return `${fecha.getDate() + 1} ${meses[fecha.getMonth()]}`;
};

const charCountClass = computed(() => {
    const len = complaintDescription.value.length;
    if (len > 250 && len <= 300) return 'text-orange-500 font-bold';
    if (len > 300) return 'text-red-500 font-bold';
    return 'text-gray-400';
});

const usar = (op) => {
    complaintDescription.value = op;
    seleccionada.value = op;
    isCollapsed.value = true;

    toast.add({
        severity: 'success',
        summary: 'Texto Actualizado',
        detail: 'Se ha aplicado la redacción sugerida.',
        life: 2000
    });
};

const mejorarTexto = async () => {
    // Validamos que no esté vacío y no exceda los 300
    if (!complaintDescription.value.trim() || complaintDescription.value.length > 300) {
        toast.add({ severity: 'warn', summary: 'Atención', detail: 'El texto debe tener entre 1 y 300 caracteres.', life: 3000 });
        return;
    }

    loading.value = true;
    opciones.value = [];

    try {
        const res = await axios.post("/complaints/improve-writing", {
            texto: complaintDescription.value,
            asunto_cod: selectedOption.value?.code,
            asunto_texto: selectedOption.value?.code === 'OTR'
                ? otherSubject.value
                : selectedOption.value?.name,
        });

        if (res.data.success) {
            opciones.value = res.data.opciones;
            seleccionada.value = null;
            isCollapsed.value = false;
        }
    } catch (error) {
        console.error("Error al mejorar texto:", error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo conectar con el servicio de IA.', life: 3000 });
    } finally {
        loading.value = false;
    }
};

const onRemoveTemplatingFile = (file, removeFileCallback, index) => {
    removeFileCallback(index);

    totalSize.value -= file.size;

    if (totalSize.value < 0) totalSize.value = 0;

    totalSizePercent.value = (totalSize.value / MAX_SIZE) * 100;
};

const onClearTemplatingUpload = (clearCallback) => {
    clearCallback();
    totalSize.value = 0;
    totalSizePercent.value = 0;
    files.value = [];
};

const onSelectedFiles = (event) => {
    files.value = event.files;
    totalSize.value = 0;

    files.value.forEach((file) => {
        totalSize.value += file.size;
    });

    totalSizePercent.value = (totalSize.value / MAX_SIZE) * 100;
};

const uploadEvent = (callback) => {
    totalSizePercent.value = (totalSize.value / MAX_SIZE) * 100;
    callback();
};

const onTemplatedUpload = () => {
    toast.add({ severity: "info", summary: "Success", detail: "File Uploaded", life: 3000 });
};

const removeExistingFile = (index, file) => {
    editRemovedFiles.value.push(file.id || file.url);
    editExistingFiles.value.splice(index, 1);
};

const onEditFilesSelected = (event) => {
    editNewFiles.value = [...editNewFiles.value, ...event.files];
};

const formatSize = (bytes) => {
    const k = 1024;
    const dm = 2;
    const sizes = $primevue.config.locale.fileSizeTypes;

    if (bytes === 0) return `0 ${sizes[0]}`;

    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
};

const showFileUpload = computed(() => {
    if (!selectedOption.value) return false;

    const allowedCodes = ['CDB', 'CDP', 'DCP', 'CON', 'OTR'];

    return allowedCodes.includes(selectedOption.value.code);
});

watch(selectedOption, (newVal) => {
    if (newVal && !['CDB', 'CDP', 'DCP', 'CON', 'OTR'].includes(newVal.code)) {
        files.value = [];
        totalSize.value = 0;
        totalSizePercent.value = 0;
        if (fileUploadRef.value) fileUploadRef.value.clear();
    }
});

const enviarQueja = async () => {
    if (!selectedOption.value || !complaintDescription.value.trim()) {
        toast.add({
            severity: 'warn',
            summary: 'Atención',
            detail: 'Por favor, selecciona un asunto y describe tu queja.',
            life: 3000
        });
        return;
    }

    sending.value = true;

    try {
        const formData = new FormData();

        // Datos básicos
        formData.append('asunto_cod', selectedOption.value.code);
        formData.append('asunto_texto', selectedOption.value.code === 'OTR'
            ? otherSubject.value
            : selectedOption.value.name);
        formData.append('descripcion', complaintDescription.value);

        if (files.value.length > 0) {
            files.value.forEach((file) => {
                formData.append('archivos[]', file);
            });
        }

        const res = await axios.post("/complaints", formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        if (res.data.success) {
            toast.add({
                severity: 'success',
                summary: 'Enviado',
                detail: 'Tu queja ha sido registrada correctamente.',
                life: 3000
            });

            limpiarFormulario();
        }
    } catch (error) {
        console.error("Error al enviar queja:", error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'No se pudo enviar la queja.',
            life: 3000
        });
    } finally {
        sending.value = false;
    }
};

const limpiarFormulario = () => {
    selectedOption.value = null;
    complaintDescription.value = "";
    otherSubject.value = "";
    opciones.value = [];
    onClearTemplatingUpload(() => {
        if (fileUploadRef.value) fileUploadRef.value.clear();
    });
};

/* =====================
MOUNTED
===================== */
onMounted(() => {
    console.log(props.history)
});

</script>

<template>
    <AppLayout :title="'Modúlo de Quejas'">
        <ConfirmDialog />
        <div class="card">
            <!-- <pre>
                {{ props }}
            </pre> -->
            <Toolbar>
                <template #start>
                    <div class="flex flex-col">
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 leading-tight">
                            Canal de Denuncias Confidencial
                        </h1>
                        <p class="text-sm md:text-base text-gray-500 mt-1">
                            Tu voz es fundamental para mantener un entorno de trabajo íntegro y seguro.
                        </p>
                    </div>
                </template>
            </Toolbar>
        </div>
        <div class="card flex flex-col lg:flex-row gap-6 p-4">

            <div class="card w-full lg:w-9/12 p-6 rounded-xl shadow-sm">
                <h2 class="text-xl font-bold mb-4">Presentar Nueva Queja</h2>
                <p class="text-sm md:text-base text-gray-500 mt-1">
                    Complete los detalles a continuación para iniciar el proceso de revisión.
                </p>

                <div class="flex flex-column gap-2">
                    <Dropdown
                        v-model="selectedOption"
                        :options="options"
                        filter
                        optionLabel="name"
                        placeholder="Seleccione un Asunto..."
                        class="w-full md:w-30rem"
                    >
                        <template #value="slotProps">
                            <div v-if="slotProps.value" class="flex align-items-center">
                                <div>{{ slotProps.value.name }}</div>
                            </div>
                            <span v-else>
                                {{ slotProps.placeholder }}
                            </span>
                        </template>
                        <template #option="slotProps">
                            <div class="flex align-items-center">
                                <div>{{ slotProps.option.name }}</div>
                            </div>
                        </template>
                    </Dropdown>

                    <div v-if="selectedOption && selectedOption.code === 'OTR'" class="mt-2">
                        <label for="other_subject" class="block mb-2 font-medium">Especifique el asunto:</label>
                        <InputText
                            id="other_subject"
                            v-model="otherSubject"
                            placeholder="Escriba el motivo aquí..."
                            class="w-full md:w-30rem"
                        />
                    </div>
                </div>

                <Divider />

                <div class="mb-6 text-center">
                    <div class="flex justify-end mb-1">
                        <small :class="charCountClass">
                            {{ complaintDescription.length }} / 300
                        </small>
                    </div>

                    <Textarea
                        v-model="complaintDescription"
                        rows="5"
                        class="w-full"
                        :class="{ 'p-invalid': complaintDescription.length > 300 }"
                        placeholder="Describe tu Queja..."
                    />

                    <div class="flex justify-center gap-2 mt-3">
                        <Button
                            label="Redactar con IA"
                            severity="help"
                            raised
                            icon="pi pi-sparkles"
                            :loading="loading"
                            :disabled="!complaintDescription.trim() || complaintDescription.length > 300"
                            @click="mejorarTexto"
                        />
                        <Button
                            v-if="opciones.length"
                            icon="pi pi-refresh"
                            label="Regenerar"
                            severity="secondary"
                            outlined
                            :loading="loading"
                            @click="mejorarTexto"
                        />
                    </div>

                    <div v-if="loading" class="mt-4 grid gap-3">
                        <div v-for="i in 3" :key="i" class="p-4 border rounded-lg">
                            <Skeleton height="1rem" class="mb-2" />
                            <Skeleton height="1rem" width="80%" />
                        </div>
                    </div>

                    <Fieldset
                        v-if="opciones.length > 0 && !loading"
                        v-model:collapsed="isCollapsed"
                        legend="Sugerencias de Redacción"
                        :toggleable="true"
                        class="mt-4"
                    >
                        <TransitionGroup
                            name="fade-slide"
                            tag="div"
                            class="grid gap-3 text-left"
                        >
                            <div
                                v-for="(op, i) in opciones"
                                :key="op"
                                @click="usar(op)"
                                :class="[
                                    'p-4 border rounded-xl cursor-pointer transition-all duration-300',
                                    seleccionada === op
                                        ? 'shadow-md scale-[1.02] border-green-500 bg-green-50/10'
                                        : 'hover:shadow-lg hover:border-purple-400 hover:scale-[1.01]'
                                ]"
                            >
                                <div class="flex justify-between items-center mb-2">
                                    <Tag
                                        :value="`Opción ${i + 1}`"
                                        :severity="seleccionada === op ? 'success' : 'secondary'"
                                        class="text-xs"
                                    />
                                    <i v-if="seleccionada === op" class="pi pi-check text-green-600"></i>
                                </div>

                                <p class="mb-3 text-sm leading-relaxed text-gray-700">
                                    {{ op }}
                                </p>

                                <div class="text-right">
                                    <Button
                                        label="Usar este"
                                        size="small"
                                        severity="success"
                                        outlined
                                        @click.stop="usar(op)"
                                    />
                                </div>
                            </div>
                        </TransitionGroup>
                    </Fieldset>
                </div>

                <div v-if="showFileUpload" class="mt-4">
                    <Toast />
                    <FileUpload
                        name="demo[]"
                        url="/api/upload"
                        @upload="onTemplatedUpload($event)"
                        :multiple="true"
                        accept="image/*,.pdf,"
                        :maxFileSize="5000000"
                        @select="onSelectedFiles"
                    >
                        <!-- HEADER -->
                        <template #header="{ chooseCallback, uploadCallback, clearCallback, files }">
                            <div class="flex flex-wrap justify-content-between align-items-center flex-1 gap-2">
                                <div class="flex gap-2">
                                    <Button @click="chooseCallback()" icon="pi pi-images" rounded outlined />
                                    <Button @click="uploadEvent(uploadCallback)" icon="pi pi-cloud-upload" rounded outlined severity="success" :disabled="!files || files.length === 0" />
                                    <Button
                                        @click="onClearTemplatingUpload(clearCallback)"
                                        icon="pi pi-times"
                                        rounded
                                        outlined
                                        severity="danger"
                                        :disabled="!files || files.length === 0"
                                    />
                                </div>

                                <ProgressBar
                                    :value="totalSizePercent"
                                    :showValue="false"
                                    :class="['md:w-20rem h-1rem w-full md:ml-auto', { 'exceeded-progress-bar': totalSizePercent > 100 }]"
                                >
                                    <span class="white-space-nowrap">{{ totalSize }}B / 1Mb</span>
                                </ProgressBar>
                            </div>
                        </template>

                        <!-- CONTENT -->
                        <template #content="{ files, uploadedFiles, removeUploadedFileCallback, removeFileCallback }">

                            <!-- PENDING -->
                            <div v-if="files.length > 0">
                                <h5>Pending</h5>
                                <div class="flex flex-wrap p-0 sm:p-5 gap-5">
                                    <div
                                        v-for="(file, index) of files"
                                        :key="file.name + file.type + file.size"
                                        class="card m-0 px-6 flex flex-column border-1 surface-border align-items-center gap-3"
                                    >
                                        <!-- PREVIEW -->
                                        <div>
                                            <!-- Imagen -->
                                            <img
                                                v-if="file.type.startsWith('image')"
                                                :src="file.objectURL"
                                                width="100"
                                                height="80"
                                            />

                                            <!-- PDF -->
                                            <iframe
                                                v-else-if="file.type === 'application/pdf'"
                                                :src="file.objectURL"
                                                width="100"
                                                height="80"
                                            ></iframe>

                                            <!-- Word -->
                                            <i
                                                v-else-if="file.type.includes('word')"
                                                class="pi pi-file-word"
                                                style="font-size: 2rem; color:#2b579a;"
                                            ></i>

                                            <!-- Otros -->
                                            <i
                                                v-else
                                                class="pi pi-file"
                                                style="font-size: 2rem;"
                                            ></i>
                                        </div>

                                        <span class="font-semibold">{{ file.name }}</span>
                                        <div>{{ formatSize(file.size) }}</div>
                                        <Badge value="Pending" severity="warning" />

                                        <Button
                                            icon="pi pi-times"
                                            @click="onRemoveTemplatingFile(file, removeFileCallback, index)"
                                            outlined
                                            rounded
                                            severity="danger"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- COMPLETED -->
                            <div v-if="uploadedFiles.length > 0">
                                <h5>Completed</h5>
                                <div class="flex flex-wrap p-0 sm:p-5 gap-5">
                                    <div
                                        v-for="(file, index) of uploadedFiles"
                                        :key="file.name + file.type + file.size"
                                        class="card m-0 px-6 flex flex-column border-1 surface-border align-items-center gap-3"
                                    >
                                        <div>
                                            <i class="pi pi-check-circle" style="font-size: 2rem; color: green;"></i>
                                        </div>

                                        <span class="font-semibold">{{ file.name }}</span>
                                        <div>{{ formatSize(file.size) }}</div>
                                        <Badge value="Completed" class="mt-3" severity="success" />

                                        <Button
                                            icon="pi pi-times"
                                            @click="removeUploadedFileCallback(index)"
                                            outlined
                                            rounded
                                            severity="danger"
                                        />
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- EMPTY -->
                        <template #empty>
                            <div class="flex items-center justify-center flex-col">
                                <div class="border-2 border-dashed border-gray-400 rounded-full p-8 flex items-center justify-center">
                                    <i class="pi pi-cloud-upload text-5xl text-gray-400"></i>
                                </div>
                                <p class="mt-4 text-gray-500">Arrastra y suelta los archivos aquí para subirlos.</p>
                                <p class="text-gray-500">Los documentos son opcionales, ya que son para enviar evidencias.</p>
                            </div>
                        </template>
                    </FileUpload>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <!-- <Button
                        label="Cancelar"
                        severity="danger"
                        icon="pi pi-times"
                        raised
                    /> -->
                    <Button
                        label="Enviar Queja"
                        severity="success"
                        icon="pi pi-send"
                        raised
                        :loading="sending"
                        @click="enviarQueja"
                    />
                </div>
            </div>
            <!-- Segunda vista -->
            <div class="w-full lg:w-3/12 flex flex-col gap-4">

                <div class="card p-5 rounded-3xl shadow-sm border-none bg-[#f1f4f8]">
                    <div class="flex justify-between items-center mb-5 px-1">
                        <h3 class="font-bold uppercase text-sm tracking-wider">
                            Mis Reportes Recientes
                        </h3>
                        <Button label="Ver todo" link class="p-0 text-blue-600 font-bold text-sm" />
                    </div>

                    <div class="flex flex-col gap-3 max-h-[400px] overflow-y-auto pr-2 custom-scroll">
                        <div
                            v-for="reporte in reportesRecientes"
                            :key="reporte.id"
                            class="p-4 rounded-2xl shadow-sm border border-gray-50 flex flex-col gap-2 hover:shadow-md transition-shadow cursor-pointer relative group"
                        >
                            <div class="flex justify-between items-start">
                                <div class="flex flex-col">
                                    <span class="font-bold text-[15px] leading-tight">
                                        {{ reporte.titulo }}
                                    </span>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span
                                            class="w-2 h-2 rounded-full"
                                            :style="{ backgroundColor: reporte.statusColor }"
                                        ></span>
                                        <span class="text-[10px] font-bold uppercase tracking-widest text-gray-600">
                                            {{ reporte.status }}
                                        </span>
                                    </div>
                                    <div v-if="reporte.archivos && reporte.archivos.length > 0" class="flex flex-wrap gap-2 mt-3">
                                        <a
                                            v-for="archivo in reporte.archivos"
                                            :key="archivo.url"
                                            :href="archivo.url"
                                            target="_blank"
                                            class="no-underline"
                                            @click.stop
                                        >
                                            <Button
                                                :label="archivo.name"
                                                icon="pi pi-paperclip"
                                                severity="secondary"
                                                size="small"
                                                outlined
                                                class="py-1 px-2 text-[10px] h-auto"
                                            />
                                        </a>
                                    </div>
                                </div>

                                <div class="flex flex-col items-end gap-2">
                                    <span class="text-xs font-medium text-gray-400">
                                        {{ reporte.fecha }}
                                    </span>

                                    <div class="flex gap-2 opacity-100 group-hover:opacity-100 transition-opacity">
                                        <Button
                                            v-if="reporte.status === 'Pendiente'"
                                            icon="pi pi-pencil"
                                            rounded
                                            aria-label="Editar"
                                            severity="warn"
                                            @click.stop="editarReporte(reporte.id)"
                                        />

                                        <Button
                                            icon="pi pi-trash"
                                            rounded
                                            aria-label="Eliminar"
                                            severity="danger"
                                            @click.stop="eliminarReporte(reporte.id)"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p v-if="reportesRecientes.length === 0" class="text-xs italic text-center py-10 text-gray-400">
                            No hay reportes recientes para mostrar.
                        </p>
                    </div>
                </div>

                <div class="confidential-card">
                    <div class="flex flex-col gap-4">
                        <div class="bg-white/20 w-12 h-12 rounded-full flex items-center justify-center">
                            <i class="pi pi-shield text-2xl text-white"></i>
                        </div>

                        <h2 class="text-xl font-bold text-white leading-tight">
                            Compromiso de Confidencialidad
                        </h2>

                        <p class="text-sm text-blue-50 leading-relaxed">
                            Toda queja o denuncia registrada en este portal está protegida
                            por un ecosistema de seguridad diseñado para blindar la integridad
                            del usuario. La información proporcionada se procesa mediante
                            protocolos de cifrado avanzado, asegurando que el contenido de
                            su reporte permanezca inaccesible para terceros ajenos al proceso.
                            Nos comprometemos a mantener el anonimato absoluto
                            de quien lo solicite, gestionando cada caso bajo políticas de estricta
                            confidencialidad. El flujo de datos está limitado exclusivamente a los
                            miembros facultados de la empresa, garantizando un entorno de confianza donde
                            su identidad y sus declaraciones están plenamente resguardadas contra cualquier
                            acceso no autorizado o filtración.
                        </p>

                        <!-- <Button
                            label="LEER PROTOCOLO"
                            icon="pi pi-arrow-right"
                            iconPos="right"
                            class="protocol-btn border-none"
                        /> -->
                    </div>
                </div>
            </div>
        </div>
        <Dialog
            v-model:visible="showEditModal"
            header="Editar Queja"
            :style="{ width: '600px' }"
            modal
        >
            <div class="flex flex-col gap-4">

                <!-- Asunto -->
                <Dropdown
                    v-model="editSelectedOption"
                    :options="options"
                    optionLabel="name"
                    placeholder="Seleccione un asunto"
                    class="w-full"
                />

                <!-- Otros -->
                <div v-if="editSelectedOption?.code === 'OTR'">
                    <InputText
                        v-model="editOtherSubject"
                        placeholder="Especifique el asunto"
                        class="w-full"
                    />
                </div>

                <!-- Descripción -->
                <Textarea
                    v-model="editComplaintDescription"
                    rows="4"
                    class="w-full"
                    placeholder="Describe tu queja..."
                />

                <div v-if="showEditFileUpload" class="mt-4">
                    <h4 class="font-bold mb-2">Archivos</h4>

                    <!-- 📂 EXISTENTES -->
                    <div v-if="editExistingFiles.length" class="flex flex-wrap gap-3 mb-4">
                        <div
                            v-for="(file, index) in editExistingFiles"
                            :key="file.url"
                            class="border rounded-lg p-3 flex flex-col items-center gap-2 w-28"
                        >
                            <!-- Preview -->
                            <img
                                v-if="file.url && file.url.match(/\.(jpg|jpeg|png|gif)$/i)"
                                :src="file.url"
                                class="w-20 h-16 object-cover"
                            />

                            <iframe
                                v-else-if="file.url && file.url.includes('.pdf')"
                                :src="file.url"
                                class="w-20 h-16"
                            ></iframe>

                            <i v-else class="pi pi-file text-2xl"></i>

                            <span class="text-xs text-center">{{ file.name }}</span>

                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                text
                                @click="removeExistingFile(index, file)"
                            />
                        </div>
                    </div>

                    <!-- 📤 NUEVOS -->
                    <FileUpload
                        mode="basic"
                        chooseLabel="Agregar archivos"
                        multiple
                        accept="image/*,.pdf"
                        @select="onEditFilesSelected"
                    />

                    <!-- Preview nuevos -->
                    <div v-if="editNewFiles.length" class="flex flex-wrap gap-3 mt-3">
                        <div
                            v-for="(file, index) in editNewFiles"
                            :key="file.name + index"
                            class="border rounded-lg p-3 flex flex-col items-center gap-2 w-28"
                        >
                            <img
                                v-if="file.type.startsWith('image')"
                                :src="file.objectURL"
                                class="w-20 h-16 object-cover"
                            />

                            <iframe
                                v-else-if="file.type === 'application/pdf'"
                                :src="file.objectURL"
                                class="w-20 h-16"
                            ></iframe>

                            <i v-else class="pi pi-file text-2xl"></i>

                            <span class="text-xs text-center">{{ file.name }}</span>

                            <Button
                                icon="pi pi-times"
                                severity="danger"
                                text
                                @click="editNewFiles.splice(index, 1)"
                            />
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="flex justify-end gap-2 mt-4">
                    <Button
                        label="Cancelar"
                        severity="secondary"
                        @click="showEditModal = false"
                    />
                    <Button
                        label="Guardar Cambios"
                        severity="success"
                        icon="pi pi-save"
                        @click="actualizarReporte"
                    />
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
<style>
/* ANIMACIONES */
.fade-slide-enter-active {
    transition: all 0.4s ease;
}

.fade-slide-leave-active {
    transition: all 0.2s ease;
    opacity: 0;
}

.fade-slide-enter-from {
    opacity: 0;
    transform: translateY(10px);
}

.fade-slide-enter-to {
    opacity: 1;
    transform: translateY(0);
}

.confidential-card {
    background: linear-gradient(135deg, #0052d4, #0066eb);
    padding: 2rem;
    border-radius: 1.5rem;
    box-shadow: 0 10px 25px -5px rgba(0, 82, 212, 0.3);
}

.protocol-btn {
    background-color: #3b82f6 !important;
    color: white !important;
    font-weight: 600 !important;
    letter-spacing: 0.5px;
    border-radius: 12px !important;
    padding: 0.75rem !important;
    transition: transform 0.2s ease;
}

.protocol-btn:hover {
    background-color: #2563eb !important;
    transform: translateY(-2px);
}

/* Ajuste para que el icono del botón se vea bien */
.protocol-btn .pi {
    font-size: 0.9rem;
}

.custom-scroll::-webkit-scrollbar {
    width: 6px;
}
.custom-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scroll::-webkit-scrollbar-thumb {
    background: #e2e8f0; /* Color gris suave */
    border-radius: 10px;
}
.custom-scroll::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}
</style>
