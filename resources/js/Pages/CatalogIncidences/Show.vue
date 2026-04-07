<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, reactive, watch, onMounted, computed } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import { useToastService } from "@/Stores/toastService";

const props = defineProps({
    Incidence: Object,
    CategoryIncidences: Object,
});

const tipeOptions = [
    { value: "days", label: "Días" },
    { value: "weeks", label: "Semanas" },
    { value: "months", label: "Meses" },
];

const timeOptions = ref(
    Array.from({ length: 51 }, (_, i) => ({
        label: i.toString(),
        value: i
    }))
);

const canceling = ref(false);

const cancel = () => {
    if (form.processing || canceling.value) return;

    canceling.value = true;

    router.get('/catalogs/incidences', {}, {
        preserveScroll: true,
        onFinish: () => {
            canceling.value = false;
        }
    });
};

const frontErrors = reactive({});

// --- Formulario Principal ---
const form = useForm({
    // ===== GENERAL =====
    name: '',
    code: '',
    category_incidence_id: null,
    external_code: '',
    incapacity_code: null,
    color: '',
    description: '',

    // ===== REGLAS =====
    active: true,
    read_only: false,
    requires_document: false,
    requires_code: false,
    requires_auth: false,
    requested_by_user: false,

    // ===== CONFIG TIEMPOS =====
    requires_advance: false,
    advance_each: null,
    advance_type: null,
    requires_schedule: false,
    requires_date: false,
    requires_rest_date: false,

    // ===== MULTIMEDIA =====
    url_video: '',
    video: null,

});

// --- Autogenerar desde name ---
watch(
    () => form.name,
    (newValue) => {

        if (!newValue) {
            form.code = '';
            return;
        }

        // Reemplazar símbolos por espacio
        const cleaned = newValue.replace(/[^a-zA-Z0-9\s]/g, ' ');

        const tokens = cleaned
            .trim()
            .split(/\s+/)
            .filter(Boolean);

        let result = '';

        tokens.forEach(token => {

            // Si empieza con número
            if (/^\d/.test(token)) {
                const numberMatch = token.match(/^\d+/);
                if (numberMatch) {
                    result += numberMatch[0];
                }
            }
            // Si empieza con letra
            else if (/^[a-zA-Z]/.test(token)) {
                result += token[0];
            }

        });

        form.code = result.toUpperCase();
    }
);

const onSelectVideo = (event) => {
    form.video = event.files[0];
};

const clearError = (path) => {
    if (frontErrors[path]) {
        delete frontErrors[path];
    }
};

const hydrateForm = (data) => {

    // ===== GENERAL =====
    form.name = data.name ?? "";
    form.code = data.code ?? "";
    form.category_incidence_id = data.category_incidence_id ?? null;
    form.external_code = data.external_code ?? "";
    form.incapacity_code = data.incapacity_code ?? null;
    form.color = data.color ? data.color.replace(/^#/, '') : ""
    form.description = data.description ?? "";

    // ===== REGLAS =====
    form.active = data.active ?? true;
    form.read_only = data.read_only ?? false;
    form.requires_document = data.requires_document ?? false;
    form.requires_code = data.requires_code ?? false;
    form.requires_auth = data.requires_auth ?? false;
    form.requested_by_user = data.requested_by_user ?? false;

    // ===== CONFIG TIEMPOS =====
    form.requires_advance = data.requires_advance ?? false;
    form.advance_each = data.advance_each ?? null;
    form.advance_type = data.advance_type ?? null;
    form.requires_schedule = data.requires_schedule ?? false;
    form.requires_date = data.requires_date ?? false;
    form.requires_rest_date = data.requires_rest_date ?? false;

    // // ===== MULTIMEDIA =====
    // form.url_video = data.url_video ?? '';
    // form.video = data.video ?? null;

};

onMounted(() => {
    hydrateForm(props.Incidence);
});

const allowedExtensions = ['mp4', 'mov', 'webm'];

// validar que haya video y en un formato valido
const validVideoUrl = computed(() => {
    const url = props.Incidence?.url_video

    if (!url) return null

    try {
        const parsed = new URL(url)

        // Quitar slash final
        const pathname = parsed.pathname.replace(/\/+$/, '')

        const filename = pathname.split('/').pop()

        // Si no hay archivo real
        if (!filename || !filename.includes('.')) return null

        const extension = filename.split('.').pop().toLowerCase()

        if (!allowedExtensions.includes(extension)) return null

        return url

    } catch (e) {
        return null
    }
})


</script>

<template>
    <AppLayout title="Detalle de la Incidencia">
        <div class="card space-y-6">
            <h2 class="text-2xl font-bold mb-5">
                <i class="pi pi-eye mr-2 text-info"></i>
                Detalle de la Incidencia
            </h2>

            <div class="mb-5">
                
                <!-- ========== GENERAL ========== -->
                <Panel class="mb-4">

                    <template #header>
                        <h5 class="font-semibold m-0">General</h5>
                    </template>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Nombre -->
                        <div class="field mb-4">
                            <label for="name" class="block font-bold mb-2">
                                Nombre <span class="text-red-500">*</span>
                            </label>
                            <InputText
                                readonly
                                id="name" 
                                v-model="form.name" 
                                placeholder="Nombre" 
                                :invalid="!!frontErrors.name"
                                class="w-full uppercase"
                                @input="clearError('name')" 
                            />
                            <Message
                                v-if="frontErrors.name"
                                severity="error"
                                size="medium"
                                variant="simple"
                            >
                                {{ frontErrors.name }}
                            </Message>
                        </div>
                        
                        <!-- Código -->
                        <div class="field mb-4">
                            <label for="code" class="block font-bold mb-2">
                                Código <span class="text-red-500">*</span>
                            </label>
                            <InputText
                                readonly
                                id="code" 
                                v-model="form.code" 
                                placeholder="Código" 
                                :invalid="!!frontErrors.code"
                                class="w-full"
                                @input="clearError('code')" 
                            />
                            <Message
                                v-if="frontErrors.code"
                                severity="error"
                                size="medium"
                                variant="simple"
                            >
                                {{ frontErrors.name }}
                            </Message>
                        </div>

                        <!-- Categoría -->
                        <div class="field mb-4">
                            <label for="category_incidence_id" class="block font-bold mb-2">
                                Categoría
                            </label>
                            <Select
                                disabled
                                id="category_incidence_id" 
                                v-model="form.category_incidence_id" 
                                :options="props.CategoryIncidences" 
                                optionLabel="name" 
                                optionValue="id" 
                                placeholder="Selecciona una opción" 
                                class="w-full" filter 
                                showClear 
                                :class="{ 'p-invalid': frontErrors.category_incidence_id }"
                                @change="clearError('category_incidence_id')"
                            />
                            <Message
                                v-if="frontErrors.category_incidence_id"
                                severity="error"
                                size="medium"
                                variant="simple"
                            >
                                {{ frontErrors.category_incidence_id }}
                            </Message>
                        </div>

                        <!-- Tipo de incidencia -->
                        <div class="field mb-4">
                            <label for="external_code" class="block font-bold mb-2">
                                Tipo de incidencia <span class="text-red-500">*</span>
                            </label>
                            <InputText
                                readonly
                                id="external_code" 
                                v-model="form.external_code" 
                                placeholder="Tipo de incidencia" 
                                :invalid="!!frontErrors.external_code"
                                class="w-full"
                                @input="clearError('external_code')" 
                            />
                            <Message
                                v-if="frontErrors.external_code"
                                severity="error"
                                size="medium"
                                variant="simple"
                            >
                                {{ frontErrors.external_code }}
                            </Message>
                        </div>
                        
                        <!-- Clave incapacidad -->
                        <div class="field mb-4">
                            <label for="incapacity_code" class="block font-bold mb-2">
                                Clave incapacidad
                            </label>
                            <InputText
                                readonly
                                id="incapacity_code" 
                                v-model="form.incapacity_code" 
                                placeholder="Clave incapacidad" 
                                :invalid="!!frontErrors.incapacity_code"
                                class="w-full"
                                @input="clearError('incapacity_code')" 
                            />
                            <Message
                                v-if="frontErrors.incapacity_code"
                                severity="error"
                                size="medium"
                                variant="simple"
                            >
                                {{ frontErrors.incapacity_code }}
                            </Message>
                        </div>
                        
                        <!-- Color Indicativo -->
                        <div class="field mb-4">
                            <label class="block font-bold mb-2">
                                Color Indicativo <span class="text-red-500">*</span>
                            </label>

                            <div class="flex items-center gap-3">

                                <!-- ColorPicker -->
                                <ColorPicker
                                    disabled
                                    id="color" 
                                    v-model="form.color"
                                    format="hex"
                                    @update:modelValue="clearError('color')"
                                />
                                <!-- Input manual -->
                                <InputText
                                    readonly
                                    v-model="form.color"
                                    class="w-full font-mono uppercase"
                                    placeholder="FFFFFF"
                                    maxlength="6"
                                    :invalid="!!frontErrors.color"
                                    @input="clearError('color')"
                                />
                            </div>
                            <Message
                                v-if="frontErrors.color"
                                    severity="error"
                                    size="medium"
                                    variant="simple"
                                >
                                    {{ frontErrors.color }}
                            </Message>
                        </div>

                        
                        <!-- Descripción -->
                        <div class="field mb-4 col-span-3">
                            <label for="description" class="block font-bold mb-2">
                                Descripción
                            </label>
                            <Textarea
                                readonly
                                id="description" 
                                v-model="form.description" 
                                placeholder="Ingresa una descripción." 
                                :class="{ 'p-invalid': frontErrors.description }" 
                                class="w-full"
                                rows="4"
                                :maxlength="1500"
                                @input="clearError('description')"
                            />
                            <small class="text-gray-500">{{ form.description?.length || 0 }}/1500</small>
                            <Message
                                v-if="frontErrors.description"
                                severity="error"
                                size="medium"
                                variant="simple"
                            >
                                {{ frontErrors.description }}
                            </Message>
                        </div>
                    </div>
                </Panel>
                
                <!-- ========== REGLAS ========== -->
                <Panel class="mb-4">

                    <template #header>
                        <h5 class="font-semibold m-0">Reglas</h5>
                    </template>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        
                        <!-- Activo -->
                        <div class="field mb-4 mt-auto">
                            <div class="card w-full shadow-none p-3">
                                <div class="flex align-items-center justify-content-between">
                                    <div class="flex align-items-center">
                                        <label class="font-bold" for="active">Activo</label>
                                    </div>
                                    <ToggleSwitch readonly id="active" v-model="form.active" />
                                </div>
                            </div>
                        </div>
                        
                        <!-- Solo lectura -->
                        <div class="field mb-4 mt-auto">
                            <div class="card w-full shadow-none p-3">
                                <div class="flex align-items-center justify-content-between">
                                    <div class="flex align-items-center">
                                        <label class="font-bold" for="read_only">Solo lectura</label>
                                    </div>
                                    <ToggleSwitch readonly id="read_only" v-model="form.read_only" />
                                </div>
                            </div>
                        </div>
                        
                        <!-- Requiere documento -->
                        <div class="field mb-4 mt-auto">
                            <div class="card w-full shadow-none p-3">
                                <div class="flex align-items-center justify-content-between">
                                    <div class="flex align-items-center">
                                        <label class="font-bold" for="requires_document">Requiere documento</label>
                                    </div>
                                    <ToggleSwitch readonly id="requires_document" v-model="form.requires_document" />
                                </div>
                            </div>
                        </div>
                        
                        <!-- Requiere folio -->
                        <div class="field mb-4 mt-auto">
                            <div class="card w-full shadow-none p-3">
                                <div class="flex align-items-center justify-content-between">
                                    <div class="flex align-items-center">
                                        <label class="font-bold" for="requires_code">Requiere folio</label>
                                    </div>
                                    <ToggleSwitch readonly id="requires_code" v-model="form.requires_code" />
                                </div>
                            </div>
                        </div>
                        
                        <!-- Requiere autorización -->
                        <div v-if="!form.read_only" class="field mb-4 mt-auto">
                            <div class="card w-full shadow-none p-3">
                                <div class="flex align-items-center justify-content-between">
                                    <div class="flex align-items-center">
                                        <label class="font-bold" for="requires_auth">Requiere autorización</label>
                                    </div>
                                    <ToggleSwitch readonly id="requires_auth" v-model="form.requires_auth" />
                                </div>
                            </div>
                        </div>
                        
                        <!-- Solicitada por usuario -->
                        <div class="field mb-4 mt-auto">
                            <div class="card w-full shadow-none p-3">
                                <div class="flex align-items-center justify-content-between">
                                    <div class="flex align-items-center">
                                        <label class="font-bold" for="requested_by_user">Solicitada por usuario</label>
                                    </div>
                                    <ToggleSwitch readonly id="requested_by_user" v-model="form.requested_by_user" />
                                </div>
                                <p class="text-muted">
                                    Habilitar si podrá ser solicitada por el empleado
                                </p>
                            </div>
                        </div>
                    </div>
                </Panel>

                <!-- ========== CONFIG TIEMPOS ========== -->
                <Panel class="mb-4">

                    <template #header>
                        <h5 class="font-semibold m-0">Configuración de tiempos</h5>
                    </template>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        
                        <!-- Requiere horario -->
                        <div class="field mb-4 mt-auto">
                            <div class="card w-full shadow-none p-3">
                                <div class="flex align-items-center justify-content-between">
                                    <div class="flex align-items-center">
                                        <label class="font-bold" for="requires_schedule">Requiere horario</label>
                                    </div>
                                    <ToggleSwitch readonly id="requires_schedule" v-model="form.requires_schedule" />
                                </div>
                                <p class="text-muted">
                                    Será el horario a modificar
                                </p>
                            </div>
                        </div>
                        
                        <!-- Requiere fecha -->
                        <div class="field mb-4 mt-auto">
                            <div class="card w-full shadow-none p-3">
                                <div class="flex align-items-center justify-content-between">
                                    <div class="flex align-items-center">
                                        <label class="font-bold" for="requires_date">Requiere fecha</label>
                                    </div>
                                    <ToggleSwitch readonly id="requires_date" v-model="form.requires_date" />
                                </div>
                                <p class="text-muted">
                                    Será la fecha a cubrir o que desea agregar como parámetro
                                </p>
                            </div>
                        </div>
                        
                        <!-- Requiere fecha descanso -->
                        <div class="field mb-4 mt-auto">
                            <div class="card w-full shadow-none p-3">
                                <div class="flex align-items-center justify-content-between">
                                    <div class="flex align-items-center">
                                        <label class="font-bold" for="requires_rest_date">Requiere fecha descanso</label>
                                    </div>
                                    <ToggleSwitch readonly id="requires_rest_date" v-model="form.requires_rest_date" />
                                </div>
                                <p class="text-muted">
                                    Será la fecha de descanso o a reponer
                                </p>
                            </div>
                        </div>

                        <!-- Requiere anticipación -->
                        <div v-if="!form.read_only" class="field mb-4 mt-auto">
                            <div class="card w-full shadow-none p-3">
                                <div class="flex align-items-center justify-content-between">
                                    <div class="flex align-items-center">
                                        <label class="font-bold" for="requires_advance">Requiere anticipación</label>
                                    </div>
                                    <ToggleSwitch readonly id="requires_advance" v-model="form.requires_advance" />
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tiempo -->
                        <div v-if="form.requires_advance && !form.read_only" class="field mb-4">
                            <label for="advance_each" class="block font-bold mb-2">
                                Tiempo <span class="text-red-500">*</span>
                            </label>
                            <Select 
                                disabled
                                id="advance_each" 
                                v-model="form.advance_each" 
                                :options="timeOptions" 
                                optionLabel="label" 
                                optionValue="value" 
                                placeholder="Selecciona una opción" 
                                class="w-full" filter 
                                showClear 
                                :class="{ 'p-invalid': frontErrors.advance_each }"
                                @change="clearError('advance_each')"
                            />
                            <Message
                                v-if="frontErrors.advance_each"
                                severity="error"
                                size="medium"
                                variant="simple"
                            >
                                {{ frontErrors.advance_each }}
                            </Message>
                        </div>

                        <!-- Tipo -->
                        <div v-if="form.requires_advance && !form.read_only" class="field mb-4">
                            <label for="Tipo" class="block font-bold mb-2">
                                Tipo <span class="text-red-500">*</span>
                            </label>
                            <Select
                                disabled
                                id="advance_type" 
                                v-model="form.advance_type" 
                                :options="tipeOptions" 
                                optionLabel="label" 
                                optionValue="value" 
                                placeholder="Selecciona una opción" 
                                class="w-full" filter 
                                showClear 
                                :class="{ 'p-invalid': frontErrors.advance_type }"
                                @change="clearError('advance_type')"
                            />
                            <Message
                                v-if="frontErrors.advance_type"
                                severity="error"
                                size="medium"
                                variant="simple"
                            >
                                {{ frontErrors.advance_type }}
                            </Message>
                        </div>
                    </div>
                </Panel>

                <!-- ========== MULTIMEDIA ========== -->
                <Panel class="mb-4">

                    <template #header>
                        <h5 class="font-semibold m-0">Multimedia</h5>
                    </template>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        
                        <!-- Video de incidencia -->
                        <div class="field mb-4 col-span-2 md:col-span-1">
                            <label class="block font-bold mb-2">
                                Video de incidencia
                            </label>
                            <div class="m-1">
                                <small v-if="validVideoUrl" class="text-green-500">
                                    Video cargado
                                </small>

                                <small v-else class="text-violet-500">
                                    No hay video registrado
                                </small>
                            </div>
                            <!-- :maxFileSize="157286400" 150 MB -->
                            <!-- <p class="text-muted">
                                Sube un archivo de video (ej: .mp4, .mov, .webm).
                            </p> -->
                        </div>

                        <!-- Columna derecha: Preview -->
                        <div
                            v-if="validVideoUrl"
                            class="col-span-2 md:col-span-1 flex items-start"
                        >
                            <div class="w-full">
                                <label class="block font-bold mb-2">
                                    Vista previa
                                </label>

                                <video
                                    class="w-full rounded-xl shadow-md border"
                                    controls
                                    preload="metadata"
                                >
                                    <source
                                        :src="validVideoUrl"
                                        type="video/mp4"
                                    />
                                    Tu navegador no soporta reproducción de video.
                                </video>
                            </div>
                        </div>
                    </div>
                </Panel>
                
            </div>

            <!-- BOTONES ABAJO -->
            <div class="flex justify-end gap-3 pt-2">
                <Button label="Volver" icon="pi pi-arrow-left" severity="secondary" @click="cancel"
                    :loading="canceling" :disabled="form.processing || canceling" />
            </div>
        </div>
    </AppLayout>
</template>