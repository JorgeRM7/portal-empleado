<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, watch, reactive, onMounted } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import { useToastService } from "@/Stores/toastService";

const props = defineProps({
    EmployeePolicy: Object,
});
// console.log(props.EmployeePolicy);

const canceling = ref(false);
const page = usePage();
const { showSuccessCustom, showErrorCustom, showValidationError } = useToastService();
const frontErrors = reactive({});

const cancel = () => {
    if (form.processing || canceling.value) return;

    canceling.value = true;

    router.get('/catalogs/policies', {}, {
        preserveScroll: true,
        onFinish: () => {
            canceling.value = false;
        }
    });
};

// Formulario base
const form = useForm({
    name: "",
    week_work_days: null,
    vacation_bonus: null,
    vacation_bonus_year: null,
    absences_discount: null,
    incidences_discount: null,
    vacations: [{ days: null, years: null }],
});

// ---------------------- Opciones para selects ----------------------
const numberOptions = ref(
    Array.from({ length: 51 }, (_, i) => ({
        label: i.toString(),
        value: i
    }))
);

// ---------------------- Manejo de reglas de eficiencia ----------------------
const addRegla = () => {
    form.vacations.push({ days: null, years: null });
};

const removeRegla = (index) => {
    if (form.vacations.length > 1) {
        form.vacations.splice(index, 1);

        // Limpiar errores de esa regla si existen
        if (frontErrors.vacations && frontErrors.vacations[index]) {
            delete frontErrors.vacations[index];
        }
    }
};

const clearReglaError = (index, field) => {
    if (frontErrors.vacations && frontErrors.vacations[index]) {
        delete frontErrors.vacations[index][field];

        // Si no hay más errores en esta regla, eliminar el objeto
        if (Object.keys(frontErrors.vacations[index]).length === 0) {
            delete frontErrors.vacations[index];
        }

        // Si no hay más reglas con errores, eliminar el array
        if (frontErrors.vacations && Object.keys(frontErrors.vacations).length === 0) {
            delete frontErrors.vacations;
        }
    }
};

const clearError = (path) => {
    if (frontErrors[path]) {
        delete frontErrors[path];
    }
};

// ---------------------- Validación del formulario ----------------------
const validateForm = () => {
    // Limpiar errores previos
    Object.keys(frontErrors).forEach(k => delete frontErrors[k]);

    // Nombre
    if (!form.name) {
        frontErrors.name = 'El nombre es obligatorio';
    } else if (form.name.length > 255) {
        frontErrors.name = 'El nombre no debe exceder los 255 caracteres';
    }

    // Días laborales semanal
    // if (form.week_work_days === null || form.week_work_days === undefined || form.week_work_days === '') {
    //     frontErrors.week_work_days = 'El campo "Días laborales semanal" es obligatorio';
    // } else 
    if (form.week_work_days) {
        if (parseInt(form.week_work_days) < 0 || parseInt(form.week_work_days) > 8) {
            frontErrors.week_work_days = 'El valor debe estar entre 0 y 8';
        }
    }

    // Prima vacacional
    if (form.vacation_bonus) {
        if (parseFloat(form.vacation_bonus) < 0 || parseFloat(form.vacation_bonus) > 1) {
            frontErrors.vacation_bonus = 'El valor debe estar entre 0 y 31';
        }
    }

    // Dias de vacaciones
    frontErrors.vacations = [];

    form.vacations.forEach((regla, index) => {
        frontErrors.vacations[index] = {};

        if (!regla.days && regla.days !== 0) {
            frontErrors.vacations[index].days = 'El campo cantidad de días es obligatorio';
        } else if (isNaN(parseInt(regla.days))) {
            frontErrors.vacations[index].days = 'El campo cantidad de días ser un número válido';
        } else if (parseInt(regla.days) < 0) {
            frontErrors.vacations[index].days = 'El campo cantidad de días debe ser un numero entre 0 y 100';
        }

        if (!regla.years && regla.years !== 0) {
            frontErrors.vacations[index].years = 'El campo antigüedad es obligatorio';
        }

        // Limpiar objeto si no tiene errores
        if (Object.keys(frontErrors.vacations[index]).length === 0) {
            delete frontErrors.vacations[index];
        }
    });

    // Si no quedaron errores, eliminar el array
    if (frontErrors.vacations && Object.keys(frontErrors.vacations).length === 0) {
        delete frontErrors.vacations;
    }

    // Retorna true si no hay errores
    return Object.keys(frontErrors).length === 0;
};

const hydrateForm = (data) => {

    // ---------------- Campos base ----------------
    form.name = data.name ?? "";
    form.week_work_days = data.week_work_days ?? null;
    form.vacation_bonus = data.vacation_bonus ?? null;
    form.vacation_bonus_year = Boolean(data.vacation_bonus_year);
    form.absences_discount = Boolean(data.absences_discount);
    form.incidences_discount = Boolean(data.incidences_discount);

    // ---------------- Días vacaciones ----------------
    if (data.vacations) {
        try {
            const parsed = Array.isArray(data.vacations)
                ? data.vacations
                : JSON.parse(data.vacations);

            form.vacations = parsed.map(item => ({
                days: item.days !== null ? Number(item.days) : null,
                years: item.years !== null ? Number(item.years) : null,
            }));

        } catch (e) {
            form.vacations = [{ days: null, years: null }];
        }
    } else {
        form.vacations = [{ days: null, years: null }];
    }

};

onMounted(() => {
    hydrateForm(props.EmployeePolicy);
});

</script>

<template>
    <AppLayout title="Detalle de la Regla">
        <div>

            <div class="card">

                <h2 class="text-2xl font-bold mb-5">
                    <i class="pi pi-eye mr-2 text-info"></i>
                    Detalle de la Regla
                </h2>

                <div class="mb-5">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Nombre -->
                        <div class="field mb-4">
                            <label for="nombre" class="block font-bold mb-2">
                                Nombre <span class="text-red-500">*</span>
                            </label>
                            <InputText readonly id="nombre" v-model="form.name" placeholder="Ej. regla para vacaciones"
                                :class="{ 'p-invalid': frontErrors.name }" class="w-full" @input="clearError('name')" />
                            <Message v-if="frontErrors.name" severity="error" size="medium" variant="simple">
                                {{ frontErrors.name }}
                            </Message>
                        </div>

                        <!-- Días laborales semanal -->
                        <div class="field">
                            <label class="block font-bold mb-2">
                                Días laborales semanal
                            </label>
                            <InputNumber readonly id="week_work_days" v-model="form.week_work_days"
                                placeholder="Ej. valor de 0 a 8" class="w-full"
                                :inputClass="{ 'p-invalid': frontErrors.week_work_days }" :min="0" :max="8" :step="1"
                                locale="en-US" @blur="clearReglaError(index, 'week_work_days')" />
                            <Message v-if="frontErrors.week_work_days" severity="error" size="medium" variant="simple">
                                {{ frontErrors.week_work_days }}
                            </Message>
                        </div>

                        <!-- Prima vacacional -->
                        <div class="field">
                            <label class="block font-bold mb-2">
                                Prima vacacional
                            </label>
                            <InputNumber readonly id="vacation_bonus" v-model="form.vacation_bonus"
                                placeholder="Ej. 0.3, valor entre 0 y 1" class="w-full"
                                :inputClass="{ 'p-invalid': frontErrors.vacation_bonus }" :min="0" :max="1" :step="0.01"
                                :minFractionDigits="1" :maxFractionDigits="2" mode="decimal" locale="en-US"
                                @blur="clearReglaError(index, 'vacation_bonus')" />
                            <Message v-if="frontErrors.vacation_bonus" severity="error" size="medium" variant="simple">
                                {{ frontErrors.vacation_bonus }}
                            </Message>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                        <!-- Prima vacacional al año -->
                        <div class="flex align-items-center mb-4">
                            <div class="card w-full shadow-none p-3">
                                <div class="flex align-items-center justify-content-between">
                                    <div class="flex align-items-center">
                                        <label class="font-bold">Prima vacacional al año</label>
                                    </div>
                                    <ToggleSwitch v-model="form.vacation_bonus_year" readonly />
                                </div>
                            </div>
                        </div>

                        <!-- Se decuentan las faltas -->
                        <div class="flex align-items-center mb-4">
                            <div class="card w-full shadow-none p-3">
                                <div class="flex align-items-center justify-content-between">
                                    <div class="flex align-items-center">
                                        <label class="font-bold">Se decuentan las faltas</label>
                                    </div>
                                    <ToggleSwitch v-model="form.absences_discount" readonly />
                                </div>
                            </div>
                        </div>

                        <!-- Se decuentan las incapacidade -->
                        <div class="flex align-items-center mb-4">
                            <div class="card w-full shadow-none p-3">
                                <div class="flex align-items-center justify-content-between">
                                    <div class="flex align-items-center">
                                        <label class="font-bold">Se decuentan las incapacidade</label>
                                    </div>
                                    <ToggleSwitch v-model="form.incidences_discount" readonly />
                                </div>
                            </div>
                        </div>

                    </div>

                    <Divider />

                    <div class="mb-4">
                        <h4 class="font-bold mb-3">Días de vacaciones <span class="text-red-500">*</span></h4>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                            <div v-for="(regla, index) in form.vacations" :key="index">

                                <div class="card p-3">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-shadow-2">

                                        <!-- Cantidad de días -->
                                        <div class="field">
                                            <label :for="`days-${index}`" class="block font-bold mb-2">
                                                Cantidad de días <span class="text-red-500">*</span>
                                            </label>
                                            <InputNumber readonly :id="`days-${index}`" v-model="regla.days"
                                                placeholder="Ej. 14" class="w-full"
                                                :inputClass="{ 'p-invalid': frontErrors.vacations?.[index]?.days }"
                                                :min="0" :max="100" locale="en-US"
                                                @blur="clearReglaError(index, 'days')" />
                                            <Message v-if="frontErrors.vacations?.[index]?.days" severity="error"
                                                size="medium" variant="simple">
                                                {{ frontErrors.vacations[index].days }}
                                            </Message>
                                        </div>

                                        <!-- Antigüedad -->
                                        <div class="field">
                                            <label :for="`years-${index}`" class="block font-bold mb-2">
                                                Antigüedad <span class="text-red-500">*</span>
                                            </label>
                                            <Select disabled :id="`years-${index}`" v-model="regla.years"
                                                :options="numberOptions" optionLabel="label" optionValue="value"
                                                placeholder="Seleccione una opción" filter class="w-full"
                                                :class="{ 'p-invalid': frontErrors.vacations?.[index]?.years }"
                                                @change="clearReglaError(index, 'years')" />
                                            <Message v-if="frontErrors.vacations?.[index]?.years" severity="error"
                                                size="medium" variant="simple">
                                                {{ frontErrors.vacations[index].years }}
                                            </Message>
                                        </div>

                                    </div>

                                </div>

                            </div>
                        </div>
                    </div>
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