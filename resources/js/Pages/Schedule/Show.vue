<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, reactive, watch } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import { useToastService } from "@/Stores/toastService";

const props = defineProps({
    Schedules: Object,
});

const canceling = ref(false);
const page = usePage();
const { showSuccessCustom, showErrorCustom, showValidationError } = useToastService();
const frontErrors = reactive({});

const cancel = () => {
    if (form.processing || canceling.value) return;

    canceling.value = true;

    router.get('/catalogs/schedules', {}, {
        preserveScroll: true,
        onFinish: () => {
            canceling.value = false;
        }
    });
};

// Formulario base
const form = useForm({
    // ===== GENERAL =====
    name: '',
    entry_time: '',
    leave_time: '',
    normal_double_overtime: null,
    normal_triple_overtime: null,

    // ===== TIEMPO EXTRA =====
    double_overtime: null,
    double_overtime_hour_value: null,
    triple_overtime: null,
    triple_overtime_hour_value: null,

    // ===== TOLERANCIAS =====
    tolerance_before_entry_time: null,
    tolerance_before_entry_type: null,
    tolerance_after_entry_time: null,
    tolerance_after_entry_type: null,
    tolerance_before_leave_time: null,
    tolerance_before_leave_type: null,
    tolerance_after_leave_time: null,
    tolerance_after_leave_type: null,

    // ===== TOLERANCIAS TIEMPO EXTRA =====
    tolerance_overtime_before_entry_time: null,
    tolerance_overtime_before_entry_type: null,
    tolerance_overtime_after_entry_time: null,
    tolerance_overtime_after_entry_type: null,
    tolerance_overtime_before_leave_time: null,
    tolerance_overtime_before_leave_type: null,
    tolerance_overtime_after_leave_time: null,
    tolerance_overtime_after_leave_type: null,
});

// ---------------------- Opciones para selects ----------------------
const toleranceTypeOptions = [
    { label: "Segundos", value: "seconds" },
    { label: "Minutos", value: "minutes" },
    { label: "Horas", value: "hours" },
];

// ---------------------- Validación del formulario ----------------------
const rules = {
    // ===== GENERAL =====
    name: [
        { rule: 'required', message: 'El campo es obligatorio' },
        { rule: 'max', value: 255, message: 'El campo no debe exceder los 255 caracteres' }
    ],
    entry_time: [
        { rule: 'required', message: 'El campo es obligatorio' }
    ],
    leave_time: [
        { rule: 'required', message: 'El campo es obligatorio' }
    ],
    normal_double_overtime: [
        { rule: 'min', value: 0, message: 'El campo debe ser igual o mayor a 0' }
    ],
    normal_triple_overtime: [
        { rule: 'min', value: 0, message: 'El campo debe ser igual o mayor a 0' }
    ],

    // ===== TOLERANCIAS =====
    tolerance_before_entry_time: [
        { rule: 'required', message: 'El campo es obligatorio' },
        { rule: 'min', value: 0, message: 'El campo debe ser igual o mayor a 0' }
    ],
    tolerance_before_entry_type: [
        { rule: 'required', message: 'El campo es obligatorio' }
    ],
    tolerance_after_entry_time: [
        { rule: 'required', message: 'El campo es obligatorio' },
        { rule: 'min', value: 0, message: 'El campo debe ser igual o mayor a 0' }
    ],
    tolerance_after_entry_type: [
        { rule: 'required', message: 'El campo es obligatorio' }
    ],
    tolerance_before_leave_time: [
        { rule: 'required', message: 'El campo es obligatorio' },
        { rule: 'min', value: 0, message: 'El campo debe ser igual o mayor a 0' }
    ],
    tolerance_before_leave_type: [
        { rule: 'required', message: 'El campo es obligatorio' }
    ],
    tolerance_after_leave_time: [
        { rule: 'required', message: 'El campo es obligatorio' },
        { rule: 'min', value: 0, message: 'El campo debe ser igual o mayor a 0' }
    ],
    tolerance_after_leave_type: [
        { rule: 'required', message: 'El campo es obligatorio' }
    ],

    // ===== TIEMPO EXTRA =====
    double_overtime: [
        { rule: 'min', value: 0, message: 'El campo debe ser igual o mayor a 0' }
    ],
    double_overtime_hour_value: [
        { rule: 'min', value: 0, message: 'El campo debe ser igual o mayor a 0' }
    ],
    triple_overtime: [
        { rule: 'min', value: 0, message: 'El campo debe ser igual o mayor a 0' }
    ],
    triple_overtime_hour_value: [
        { rule: 'min', value: 0, message: 'El campo debe ser igual o mayor a 0' }
    ],

    // ===== TOLERANCIAS TIEMPO EXTRA =====
    tolerance_overtime_before_entry_time: [
        { rule: 'required', message: 'El campo es obligatorio' },
        { rule: 'min', value: 0, message: 'El campo debe ser igual o mayor a 0' }
    ],
    tolerance_overtime_before_entry_type: [
        { rule: 'required', message: 'El campo es obligatorio' }
    ],
    tolerance_overtime_after_entry_time: [
        { rule: 'required', message: 'El campo es obligatorio' },
        { rule: 'min', value: 0, message: 'El campo debe ser igual o mayor a 0' }
    ],
    tolerance_overtime_after_entry_type: [
        { rule: 'required', message: 'El campo es obligatorio' }
    ],
    tolerance_overtime_before_leave_time: [
        { rule: 'required', message: 'El campo es obligatorio' },
        { rule: 'min', value: 0, message: 'El campo debe ser igual o mayor a 0' }
    ],
    tolerance_overtime_before_leave_type: [
        { rule: 'required', message: 'El campo es obligatorio' }
    ],
    tolerance_overtime_after_leave_time: [
        { rule: 'required', message: 'El campo es obligatorio' },
        { rule: 'min', value: 0, message: 'El campo debe ser igual o mayor a 0' }
    ],
    tolerance_overtime_after_leave_type: [
        { rule: 'required', message: 'El campo es obligatorio' }
    ],
};

const validators = {
    required: (value) =>
        value !== null && value !== undefined && value !== '',

    max: (value, max) =>
        value === null || value === '' || String(value).length <= max,

    min: (value, min) =>
        value === null || value === '' || parseFloat(value) >= min,
};

const validateForm = () => {
    Object.keys(frontErrors).forEach(k => delete frontErrors[k]);

    for (const field in rules) {

        // Si es array dinámico
        if (field.includes('*')) {

            const [arrayName, , subField] = field.split('.');

            form[arrayName]?.forEach((item, index) => {
                const realPath = `${arrayName}.${index}.${subField}`;
                const value = item[subField];

                for (const r of rules[field]) {
                    const isValid = validators[r.rule](value, r.value);

                    if (!isValid) {
                        frontErrors[realPath] = r.message;
                        break;
                    }
                }
            });

        } else {
            const value = form[field];

            for (const r of rules[field]) {
                const isValid = validators[r.rule](value, r.value);

                if (!isValid) {
                    frontErrors[field] = r.message;
                    break;
                }
            }
        }
    }

    return Object.keys(frontErrors).length === 0;
};

const clearError = (path) => {
    if (frontErrors[path]) {
        delete frontErrors[path];
    }
};

const hydrateForm = (data) => {

    if (!data) return;

    // ===== GENERAL =====
    form.name = data.name ?? "";

    form.entry_time = data.entry_time ? formatTime(data.entry_time) : null;

    form.leave_time = data.leave_time ? formatTime(data.leave_time) : null;

    form.normal_double_overtime = data.normal_double_overtime ?? 0;
    form.normal_triple_overtime = data.normal_triple_overtime ?? 0;

    // ===== TIEMPO EXTRA =====
    form.double_overtime = data.double_overtime ?? 0;
    form.double_overtime_hour_value = data.double_overtime_hour_value ?? 0;

    form.triple_overtime = data.triple_overtime ?? 0;
    form.triple_overtime_hour_value = data.triple_overtime_hour_value ?? 0;

    // ===== TOLERANCIAS =====
    form.tolerance_before_entry_time = data.tolerance_before_entry_time ?? 0;
    form.tolerance_before_entry_type = data.tolerance_before_entry_type ?? "";

    form.tolerance_after_entry_time = data.tolerance_after_entry_time ?? 0;
    form.tolerance_after_entry_type = data.tolerance_after_entry_type ?? "";

    form.tolerance_before_leave_time = data.tolerance_before_leave_time ?? 0;
    form.tolerance_before_leave_type = data.tolerance_before_leave_type ?? "";

    form.tolerance_after_leave_time = data.tolerance_after_leave_time ?? 0;
    form.tolerance_after_leave_type = data.tolerance_after_leave_type ?? "";

    // ===== TOLERANCIAS TIEMPO EXTRA =====
    form.tolerance_overtime_before_entry_time = data.tolerance_overtime_before_entry_time ?? 0;
    form.tolerance_overtime_before_entry_type = data.tolerance_overtime_before_entry_type ?? "";

    form.tolerance_overtime_after_entry_time = data.tolerance_overtime_after_entry_time ?? 0;
    form.tolerance_overtime_after_entry_type = data.tolerance_overtime_after_entry_type ?? "";

    form.tolerance_overtime_before_leave_time = data.tolerance_overtime_before_leave_time ?? 0;
    form.tolerance_overtime_before_leave_type = data.tolerance_overtime_before_leave_type ?? "";

    form.tolerance_overtime_after_leave_time = data.tolerance_overtime_after_leave_time ?? 0;
    form.tolerance_overtime_after_leave_type = data.tolerance_overtime_after_leave_type ?? "";
};

const formatTime = (time) => {
    if (!time) return null;
    return time.slice(0, 5); // "16:00:00" → "16:00"
};

watch(
    () => props.Schedules,
    (val) => {
        if (val) hydrateForm(val);
    },
    { immediate: true }
);

</script>

<template>
    <AppLayout title="Detalle del Horario">
        <div>

            <div class="card">

                <h2 class="text-2xl font-bold mb-5">
                    <i class="pi pi-eye mr-2 text-info"></i>
                    Detalle del Horario
                </h2>

                <div class="mb-5">

                    <!-- ========== GENERAL ========== -->
                    <Panel class="mb-4">

                        <template #header>
                            <h5 class="font-semibold m-0">General</h5>
                        </template>

                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <!-- Nombre -->
                            <div class="field mb-4">
                                <label for="nombre" class="block font-bold mb-2">
                                    Nombre del horario <span class="text-red-500">*</span>
                                </label>
                                <InputText readonly id="nombre" v-model="form.name" placeholder="Ej. 12 X 12"
                                    :invalid="!!frontErrors.name" class="w-full" @input="clearError('name')" />
                                <Message v-if="frontErrors.name" severity="error" size="medium" variant="simple">
                                    {{ frontErrors.name }}
                                </Message>
                            </div>

                            <!-- Hora de entrada -->
                            <div class="field">
                                <label class="block font-bold mb-2">
                                    Hora de entrada <span class="text-red-500">*</span>
                                </label>
                                <InputText readonly type="time" id="entry_time" v-model="form.entry_time" timeOnly
                                    placeholder="Ej. 09:00" class="w-full" :invalid="!!frontErrors.entry_time"
                                    @update:modelValue="clearError('entry_time')" />
                                <Message v-if="frontErrors.entry_time" severity="error" size="medium" variant="simple">
                                    {{ frontErrors.entry_time }}
                                </Message>
                            </div>

                            <!-- Hora de salida -->
                            <div class="field">
                                <label class="block font-bold mb-2">
                                    Hora de salida <span class="text-red-500">*</span>
                                </label>
                                <InputText readonly type="time" id="leave_time" v-model="form.leave_time" timeOnly
                                    placeholder="Ej. 18:00" class="w-full" :invalid="!!frontErrors.leave_time"
                                    @update:modelValue="clearError('leave_time')" />
                                <Message v-if="frontErrors.leave_time" severity="error" size="medium" variant="simple">
                                    {{ frontErrors.leave_time }}
                                </Message>
                            </div>

                            <!-- Horas extra dobles -->
                            <div class="field">
                                <label class="block font-bold mb-2">
                                    Horas extra dobles
                                </label>
                                <InputNumber readonly id="normal_double_overtime" v-model="form.normal_double_overtime"
                                    placeholder="Ej. 1.5" class="w-full" :invalid="!!frontErrors.normal_double_overtime"
                                    :step="0.01" :min="0" :minFractionDigits="1" :maxFractionDigits="2" mode="decimal"
                                    locale="en-US" @keydown="clearError('normal_double_overtime')" />
                                <Message v-if="frontErrors.normal_double_overtime" severity="error" size="medium"
                                    variant="simple">
                                    {{ frontErrors.normal_double_overtime }}
                                </Message>
                            </div>

                            <!-- Horas extra triple -->
                            <div class="field">
                                <label class="block font-bold mb-2">
                                    Horas extra triple
                                </label>
                                <InputNumber readonly id="normal_triple_overtime" v-model="form.normal_triple_overtime"
                                    placeholder="Ej. 2.5" class="w-full" :invalid="!!frontErrors.normal_triple_overtime"
                                    :step="0.01" :min="0" :minFractionDigits="1" :maxFractionDigits="2" mode="decimal"
                                    locale="en-US" @keydown="clearError('normal_triple_overtime')" />
                                <Message v-if="frontErrors.normal_triple_overtime" severity="error" size="medium"
                                    variant="simple">
                                    {{ frontErrors.normal_triple_overtime }}
                                </Message>
                            </div>
                        </div>
                    </Panel>

                    <!-- ========== TOLERANCIAS ========== -->
                    <Panel class="mb-4">

                        <template #header>
                            <h5 class="font-semibold m-0">Tolerancias</h5>
                        </template>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                            <Divider align="left" type="solid" class="col-span-1 md:col-span-4 my-1"
                                style="--p-divider-border-color: #0ea5e9;">
                                <b class="text-sky-400">Entrada</b>
                            </Divider>

                            <!-- Tolerancia antes de la entrada -->
                            <div class="field">
                                <label class="block font-bold mb-2">
                                    Tolerancia antes de la entrada <span class="text-red-500">*</span>
                                </label>
                                <InputNumber readonly id="tolerance_before_entry_time" v-model="form.tolerance_before_entry_time"
                                    class="w-full" :invalid="!!frontErrors.tolerance_before_entry_time" :min="0"
                                    locale="en-US" @keydown="clearError('tolerance_before_entry_time')" />
                                <Message v-if="frontErrors.tolerance_before_entry_time" severity="error" size="medium"
                                    variant="simple">
                                    {{ frontErrors.tolerance_before_entry_time }}
                                </Message>
                            </div>

                            <!-- Tipo tolerancia antes de la entrada -->
                            <div class="field">
                                <label class="block font-bold mb-2">
                                    Tipo tolerancia antes de la entrada <span class="text-red-500">*</span>
                                </label>
                                <Select disabled id="tolerance_before_entry_type" v-model="form.tolerance_before_entry_type"
                                    :options="toleranceTypeOptions" optionLabel="label" optionValue="value"
                                    placeholder="Seleccione una opción" class="w-full"
                                    :invalid="!!frontErrors.tolerance_before_entry_type"
                                    @change="clearError('tolerance_before_entry_type')" />
                                <Message v-if="frontErrors.tolerance_before_entry_type" severity="error" size="medium"
                                    variant="simple">
                                    {{ frontErrors.tolerance_before_entry_type }}
                                </Message>
                            </div>

                            <!-- Tolerancia después de la entrada -->
                            <div class="field">
                                <label class="block font-bold mb-2">
                                    Tolerancia después de la entrada <span class="text-red-500">*</span>
                                </label>
                                <InputNumber readonly id="tolerance_after_entry_time" v-model="form.tolerance_after_entry_time"
                                    class="w-full" :invalid="!!frontErrors.tolerance_after_entry_time" :min="0"
                                    locale="en-US" @keydown="clearError('tolerance_after_entry_time')" />
                                <Message v-if="frontErrors.tolerance_after_entry_time" severity="error" size="medium"
                                    variant="simple">
                                    {{ frontErrors.tolerance_after_entry_time }}
                                </Message>
                            </div>

                            <!-- Tipo tolerancia después de la entrada -->
                            <div class="field">
                                <label class="block font-bold mb-2">
                                    Tipo tolerancia después de la entrada <span class="text-red-500">*</span>
                                </label>
                                <Select disabled id="tolerance_after_entry_type" v-model="form.tolerance_after_entry_type"
                                    :options="toleranceTypeOptions" optionLabel="label" optionValue="value"
                                    placeholder="Seleccione una opción" class="w-full"
                                    :invalid="!!frontErrors.tolerance_after_entry_type"
                                    @change="clearError('tolerance_after_entry_type')" />
                                <Message v-if="frontErrors.tolerance_after_entry_type" severity="error" size="medium"
                                    variant="simple">
                                    {{ frontErrors.tolerance_after_entry_type }}
                                </Message>
                            </div>

                            <Divider align="left" type="solid" class="col-span-1 md:col-span-4 my-1"
                                style="--p-divider-border-color: #c084fc;">
                                <b class="text-purple-400">Salida</b>
                            </Divider>

                            <!-- Tolerancia antes de la salida -->
                            <div class="field">
                                <label class="block font-bold mb-2">
                                    Tolerancia antes de la salida <span class="text-red-500">*</span>
                                </label>
                                <InputNumber readonly id="tolerance_before_leave_time" v-model="form.tolerance_before_leave_time"
                                    class="w-full" :invalid="!!frontErrors.tolerance_before_leave_time" :min="0"
                                    locale="en-US" @keydown="clearError('tolerance_before_leave_time')" />
                                <Message v-if="frontErrors.tolerance_before_leave_time" severity="error" size="medium"
                                    variant="simple">
                                    {{ frontErrors.tolerance_before_leave_time }}
                                </Message>
                            </div>

                            <!-- Tipo tolerancia antes de la salida -->
                            <div class="field">
                                <label class="block font-bold mb-2">
                                    Tipo tolerancia antes de la salida <span class="text-red-500">*</span>
                                </label>
                                <Select disabled id="tolerance_before_leave_type" v-model="form.tolerance_before_leave_type"
                                    :options="toleranceTypeOptions" optionLabel="label" optionValue="value"
                                    placeholder="Seleccione una opción" class="w-full"
                                    :invalid="!!frontErrors.tolerance_before_leave_type"
                                    @change="clearError('tolerance_before_leave_type')" />
                                <Message v-if="frontErrors.tolerance_before_leave_type" severity="error" size="medium"
                                    variant="simple">
                                    {{ frontErrors.tolerance_before_leave_type }}
                                </Message>
                            </div>

                            <!-- Tolerancia después de la salida -->
                            <div class="field">
                                <label class="block font-bold mb-2">
                                    Tolerancia después de la salida <span class="text-red-500">*</span>
                                </label>
                                <InputNumber readonly id="tolerance_after_leave_time" v-model="form.tolerance_after_leave_time"
                                    class="w-full" :invalid="!!frontErrors.tolerance_after_leave_time" :min="0"
                                    locale="en-US" @keydown="clearError('tolerance_after_leave_time')" />
                                <Message v-if="frontErrors.tolerance_after_leave_time" severity="error" size="medium"
                                    variant="simple">
                                    {{ frontErrors.tolerance_after_leave_time }}
                                </Message>
                            </div>

                            <!-- Tipo tolerancia después de la salida -->
                            <div class="field">
                                <label class="block font-bold mb-2">
                                    Tipo tolerancia después de la salida <span class="text-red-500">*</span>
                                </label>
                                <Select disabled id="tolerance_after_leave_type" v-model="form.tolerance_after_leave_type"
                                    :options="toleranceTypeOptions" optionLabel="label" optionValue="value"
                                    placeholder="Seleccione una opción" class="w-full"
                                    :invalid="!!frontErrors.tolerance_after_leave_type"
                                    @change="clearError('tolerance_after_leave_type')" />
                                <Message v-if="frontErrors.tolerance_after_leave_type" severity="error" size="medium"
                                    variant="simple">
                                    {{ frontErrors.tolerance_after_leave_type }}
                                </Message>
                            </div>

                        </div>
                    </Panel>

                    <!-- ========== TIEMPO EXTRA ========== -->
                    <Panel class="mb-4">

                        <template #header>
                            <h5 class="font-semibold m-0">Tiempos extra</h5>
                        </template>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                            <!-- Horas extra dobles -->
                            <div class="field">
                                <label class="block font-bold mb-2">
                                    Horas extra dobles
                                </label>
                                <InputNumber readonly id="double_overtime" v-model="form.double_overtime" placeholder="Ej. 3"
                                    class="w-full" :invalid="!!frontErrors.double_overtime" :step="0.01" :min="0"
                                    :minFractionDigits="1" :maxFractionDigits="2" mode="decimal" locale="en-US"
                                    @keydown="clearError('double_overtime')" />
                                <Message v-if="frontErrors.double_overtime" severity="error" size="medium"
                                    variant="simple">
                                    {{ frontErrors.double_overtime }}
                                </Message>
                            </div>

                            <!-- Formula hora extra doble -->
                            <div class="field">
                                <label class="block font-bold mb-2">
                                    Formula hora extra doble
                                </label>
                                <InputNumber readonly id="double_overtime_hour_value" v-model="form.double_overtime_hour_value"
                                    placeholder="Ej. 0" class="w-full"
                                    :invalid="!!frontErrors.double_overtime_hour_value" :step="0.01" :min="0"
                                    :minFractionDigits="1" :maxFractionDigits="2" mode="decimal" locale="en-US"
                                    @keydown="clearError('double_overtime_hour_value')" />
                                <Message v-if="frontErrors.double_overtime_hour_value" severity="error" size="medium"
                                    variant="simple">
                                    {{ frontErrors.double_overtime_hour_value }}
                                </Message>
                            </div>

                            <!-- Horas extra triples -->
                            <div class="field">
                                <label class="block font-bold mb-2">
                                    Horas extra triples
                                </label>
                                <InputNumber readonly id="triple_overtime" v-model="form.triple_overtime" placeholder="Ej. 4.5"
                                    class="w-full" :invalid="!!frontErrors.triple_overtime" :step="0.01" :min="0"
                                    :minFractionDigits="1" :maxFractionDigits="2" mode="decimal" locale="en-US"
                                    @keydown="clearError('triple_overtime')" />
                                <Message v-if="frontErrors.triple_overtime" severity="error" size="medium"
                                    variant="simple">
                                    {{ frontErrors.triple_overtime }}
                                </Message>
                            </div>

                            <!-- Formula hora extra triple -->
                            <div class="field">
                                <label class="block font-bold mb-2">
                                    Formula hora extra triple
                                </label>
                                <InputNumber readonly id="triple_overtime_hour_value" v-model="form.triple_overtime_hour_value"
                                    placeholder="Ej. 0" class="w-full"
                                    :invalid="!!frontErrors.triple_overtime_hour_value" :step="0.01" :min="0"
                                    :minFractionDigits="1" :maxFractionDigits="2" mode="decimal" locale="en-US"
                                    @keydown="clearError('triple_overtime_hour_value')" />
                                <Message v-if="frontErrors.triple_overtime_hour_value" severity="error" size="medium"
                                    variant="simple">
                                    {{ frontErrors.triple_overtime_hour_value }}
                                </Message>
                            </div>

                        </div>
                    </Panel>

                    <!-- ========== TOLERANCIAS TIEMPO EXTRA ========== -->
                    <Panel class="mb-4">

                        <template #header>
                            <h5 class="font-semibold m-0">Tolerancias tiempo extra</h5>
                        </template>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                            <Divider align="left" type="solid" class="col-span-1 md:col-span-4 my-1"
                                style="--p-divider-border-color: #0ea5e9;">
                                <b class="text-sky-400">Entrada</b>
                            </Divider>

                            <!-- Tolerancia antes de la entrada -->
                            <div class="field">
                                <label class="block font-bold mb-2">
                                    Tolerancia antes de la entrada <span class="text-red-500">*</span>
                                </label>
                                <InputNumber readonly id="tolerance_overtime_before_entry_time"
                                    v-model="form.tolerance_overtime_before_entry_time" class="w-full"
                                    :invalid="!!frontErrors.tolerance_overtime_before_entry_time" :min="0"
                                    locale="en-US" @keydown="clearError('tolerance_overtime_before_entry_time')" />
                                <Message v-if="frontErrors.tolerance_overtime_before_entry_time" severity="error"
                                    size="medium" variant="simple">
                                    {{ frontErrors.tolerance_overtime_before_entry_time }}
                                </Message>
                            </div>

                            <!-- Tipo tolerancia antes de la entrada -->
                            <div class="field">
                                <label class="block font-bold mb-2">
                                    Tipo tolerancia antes de la entrada <span class="text-red-500">*</span>
                                </label>
                                <Select disabled id="tolerance_overtime_before_entry_type"
                                    v-model="form.tolerance_overtime_before_entry_type" :options="toleranceTypeOptions"
                                    optionLabel="label" optionValue="value" placeholder="Seleccione una opción"
                                    class="w-full" :invalid="!!frontErrors.tolerance_overtime_before_entry_type"
                                    @change="clearError('tolerance_overtime_before_entry_type')" />
                                <Message v-if="frontErrors.tolerance_overtime_before_entry_type" severity="error"
                                    size="medium" variant="simple">
                                    {{ frontErrors.tolerance_overtime_before_entry_type }}
                                </Message>
                            </div>

                            <!-- Tolerancia después de la entrada -->
                            <div class="field">
                                <label class="block font-bold mb-2">
                                    Tolerancia después de la entrada <span class="text-red-500">*</span>
                                </label>
                                <InputNumber readonly id="tolerance_overtime_after_entry_time"
                                    v-model="form.tolerance_overtime_after_entry_time" class="w-full"
                                    :invalid="!!frontErrors.tolerance_overtime_after_entry_time" :min="0" locale="en-US"
                                    @keydown="clearError('tolerance_overtime_after_entry_time')" />
                                <Message v-if="frontErrors.tolerance_overtime_after_entry_time" severity="error"
                                    size="medium" variant="simple">
                                    {{ frontErrors.tolerance_overtime_after_entry_time }}
                                </Message>
                            </div>

                            <!-- Tipo tolerancia después de la entrada -->
                            <div class="field">
                                <label class="block font-bold mb-2">
                                    Tipo tolerancia después de la entrada <span class="text-red-500">*</span>
                                </label>
                                <Select disabled id="tolerance_overtime_after_entry_type"
                                    v-model="form.tolerance_overtime_after_entry_type" :options="toleranceTypeOptions"
                                    optionLabel="label" optionValue="value" placeholder="Seleccione una opción"
                                    class="w-full" :invalid="!!frontErrors.tolerance_overtime_after_entry_type"
                                    @change="clearError('tolerance_overtime_after_entry_type')" />
                                <Message v-if="frontErrors.tolerance_overtime_after_entry_type" severity="error"
                                    size="medium" variant="simple">
                                    {{ frontErrors.tolerance_overtime_after_entry_type }}
                                </Message>
                            </div>

                            <Divider align="left" type="solid" class="col-span-1 md:col-span-4 my-1"
                                style="--p-divider-border-color: #c084fc;">
                                <b class="text-purple-400">Salida</b>
                            </Divider>

                            <!-- Tolerancia antes de la salida -->
                            <div class="field">
                                <label class="block font-bold mb-2">
                                    Tolerancia antes de la salida <span class="text-red-500">*</span>
                                </label>
                                <InputNumber readonly id="tolerance_overtime_before_leave_time"
                                    v-model="form.tolerance_overtime_before_leave_time" class="w-full"
                                    :invalid="!!frontErrors.tolerance_overtime_before_leave_time" :min="0"
                                    locale="en-US" @keydown="clearError('tolerance_overtime_before_leave_time')" />
                                <Message v-if="frontErrors.tolerance_overtime_before_leave_time" severity="error"
                                    size="medium" variant="simple">
                                    {{ frontErrors.tolerance_overtime_before_leave_time }}
                                </Message>
                            </div>

                            <!-- Tipo tolerancia antes de la salida -->
                            <div class="field">
                                <label class="block font-bold mb-2">
                                    Tipo tolerancia antes de la salida <span class="text-red-500">*</span>
                                </label>
                                <Select disabled id="tolerance_overtime_before_leave_type"
                                    v-model="form.tolerance_overtime_before_leave_type" :options="toleranceTypeOptions"
                                    optionLabel="label" optionValue="value" placeholder="Seleccione una opción"
                                    class="w-full" :invalid="!!frontErrors.tolerance_overtime_before_leave_type"
                                    @change="clearError('tolerance_overtime_before_leave_type')" />
                                <Message v-if="frontErrors.tolerance_overtime_before_leave_type" severity="error"
                                    size="medium" variant="simple">
                                    {{ frontErrors.tolerance_overtime_before_leave_type }}
                                </Message>
                            </div>

                            <!-- Tolerancia después de la salida -->
                            <div class="field">
                                <label class="block font-bold mb-2">
                                    Tolerancia después de la salida <span class="text-red-500">*</span>
                                </label>
                                <InputNumber readonly id="tolerance_overtime_after_leave_time"
                                    v-model="form.tolerance_overtime_after_leave_time" class="w-full"
                                    :invalid="!!frontErrors.tolerance_overtime_after_leave_time" :min="0" locale="en-US"
                                    @keydown="clearError('tolerance_overtime_after_leave_time')" />
                                <Message v-if="frontErrors.tolerance_overtime_after_leave_time" severity="error"
                                    size="medium" variant="simple">
                                    {{ frontErrors.tolerance_overtime_after_leave_time }}
                                </Message>
                            </div>

                            <!-- Tipo tolerancia después de la salida -->
                            <div class="field">
                                <label class="block font-bold mb-2">
                                    Tipo tolerancia después de la salida <span class="text-red-500">*</span>
                                </label>
                                <Select disabled id="tolerance_overtime_after_leave_type"
                                    v-model="form.tolerance_overtime_after_leave_type" :options="toleranceTypeOptions"
                                    optionLabel="label" optionValue="value" placeholder="Seleccione una opción"
                                    class="w-full" :invalid="!!frontErrors.tolerance_overtime_after_leave_type"
                                    @change="clearError('tolerance_overtime_after_leave_type')" />
                                <Message v-if="frontErrors.tolerance_overtime_after_leave_type" severity="error"
                                    size="medium" variant="simple">
                                    {{ frontErrors.tolerance_overtime_after_leave_type }}
                                </Message>
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

        </div>

    </AppLayout>
</template>