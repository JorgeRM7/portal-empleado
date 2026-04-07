<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, reactive, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useToastService } from "@/Stores/toastService";

const canceling = ref(false);

const cancel = () => {
    if (form.processing || canceling.value) return;

    canceling.value = true;

    router.get('/catalogs/positions', {}, {
        preserveScroll: true,
        onFinish: () => {
            canceling.value = false;
        }
    });
};

const props = defineProps({
    Position: Object,
});

const { showSuccess, showErrorCustom, showValidationError } = useToastService();
const frontErrors = reactive({});

// --- Formulario Principal ---
const form = useForm({
    puesto: '',
    descripcion: '',
    salarioDiarioPrueba: '',
    netoSemanalPrueba: '',
    paPrueba: 0.0,
    ppPrueba: 0.0,
    compensacionPrueba: '',
    totalPercepcionesPrueba: '',
    tipoCompensacionPrueba: null,
    salarioDiario: '',
    netoSemanal: '',
    pa: 0.0,
    pp: 0.0,
    compensacion: '',
    totalPercepciones: '',
    tipoCompensacion: null,
});

const tiposCompensacion = [
    { label: 'Automático', value: 'auto' },
    { label: 'Eficiencia', value: 'efficiency' },
    { label: 'Periodo de prueba', value: 'trial' },
    { label: 'Ninguna', value: 'none' },
];

const submitForm = () => {

    if (!validateForm()) {
        showValidationError('Hay campos obligatorios sin completar');
        return;
    }

    form.put(`/catalogs/positions/${props.Position.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showSuccess('Puesto actualizado correctamente');
        },
        onError: () => {
            Object.values(form.errors).forEach(err => showErrorCustom(err));
        },
    });

};

const validateForm = () => {
    // Limpiar errores previos
    Object.keys(frontErrors).forEach(k => delete frontErrors[k]);

    /* ======================
        VALIDACIONES GENERALES
    ====================== */

    // Puesto
    if (!form.puesto) {
        frontErrors.puesto = 'El puesto es obligatorio';
    } else if (form.puesto.length > 255) {
        frontErrors.puesto = 'El puesto no debe exceder los 255 caracteres';
    }

    // Descripción
    if (!form.descripcion) {
        frontErrors.descripcion = 'La descripción es obligatoria';
    } else if (form.descripcion.length > 500) {
        frontErrors.descripcion = 'La descripción no debe exceder los 500 caracteres';
    }

    /* ======================
        SECCIÓN: INICIALES O PRUEBA
    ====================== */

    // Salario Diario Prueba
    if (!form.salarioDiarioPrueba && form.salarioDiarioPrueba !== 0) {
        frontErrors.salarioDiarioPrueba = 'El salario diario de prueba es obligatorio';
    } else if (isNaN(parseFloat(form.salarioDiarioPrueba))) {
        frontErrors.salarioDiarioPrueba = 'El salario diario de prueba debe ser un número válido';
    }

    // Neto Semanal Prueba
    if (!form.netoSemanalPrueba && form.netoSemanalPrueba !== 0) {
        frontErrors.netoSemanalPrueba = 'El neto semanal de prueba es obligatorio';
    } else if (isNaN(parseFloat(form.netoSemanalPrueba))) {
        frontErrors.netoSemanalPrueba = 'El neto semanal de prueba debe ser un número válido';
    }

    // %PA Prueba
    if (!form.paPrueba && form.paPrueba !== 0) {
        frontErrors.paPrueba = 'El %PA de prueba es obligatorio';
    } else if (isNaN(parseFloat(form.paPrueba))) {
        frontErrors.paPrueba = 'El %PA de prueba debe ser un número válido';
    } else if (parseFloat(form.paPrueba) < 0 || parseFloat(form.paPrueba) > 1) {
        frontErrors.paPrueba = 'El %PA debe estar entre 0 y 1';
    }

    // %PP Prueba
    if (!form.ppPrueba && form.ppPrueba !== 0) {
        frontErrors.ppPrueba = 'El %PP de prueba es obligatorio';
    } else if (isNaN(parseFloat(form.ppPrueba))) {
        frontErrors.ppPrueba = 'El %PP de prueba debe ser un número válido';
    } else if (parseFloat(form.ppPrueba) < 0 || parseFloat(form.ppPrueba) > 1) {
        frontErrors.ppPrueba = 'El %PP debe estar entre 0 y 1';
    }

    // Compensación Prueba
    if (!form.compensacionPrueba && form.compensacionPrueba !== 0) {
        frontErrors.compensacionPrueba = 'La compensación de prueba es obligatoria';
    } else if (isNaN(parseFloat(form.compensacionPrueba))) {
        frontErrors.compensacionPrueba = 'La compensación de prueba debe ser un número válido';
    }

    // Total Percepciones Prueba
    if (!form.totalPercepcionesPrueba && form.totalPercepcionesPrueba !== 0) {
        frontErrors.totalPercepcionesPrueba = 'El total de percepciones de prueba es obligatorio';
    } else if (isNaN(parseFloat(form.totalPercepcionesPrueba))) {
        frontErrors.totalPercepcionesPrueba = 'El total de percepciones de prueba debe ser un número válido';
    }

    // Tipo de Compensación Prueba
    if (!form.tipoCompensacionPrueba) {
        frontErrors.tipoCompensacionPrueba = 'El tipo de compensación de prueba es obligatorio';
    }

    /* ======================
        SECCIÓN: AJUSTADOS
    ====================== */

    // Salario Diario Ajustado
    if (!form.salarioDiario && form.salarioDiario !== 0) {
        frontErrors.salarioDiario = 'El salario diario ajustado es obligatorio';
    } else if (isNaN(parseFloat(form.salarioDiario))) {
        frontErrors.salarioDiario = 'El salario diario ajustado debe ser un número válido';
    }

    // Neto Semanal Ajustado
    if (!form.netoSemanal && form.netoSemanal !== 0) {
        frontErrors.netoSemanal = 'El neto semanal ajustado es obligatorio';
    } else if (isNaN(parseFloat(form.netoSemanal))) {
        frontErrors.netoSemanal = 'El neto semanal ajustado debe ser un número válido';
    }

    // %PA Ajustado
    if (!form.pa && form.pa !== 0) {
        frontErrors.pa = 'El %PA ajustado es obligatorio';
    } else if (isNaN(parseFloat(form.pa))) {
        frontErrors.pa = 'El %PA ajustado debe ser un número válido';
    } else if (parseFloat(form.pa) < 0 || parseFloat(form.pa) > 1) {
        frontErrors.pa = 'El %PA ajustado debe estar entre 0 y 1';
    }

    // %PP Ajustado
    if (!form.pp && form.pp !== 0) {
        frontErrors.pp = 'El %PP ajustado es obligatorio';
    } else if (isNaN(parseFloat(form.pp))) {
        frontErrors.pp = 'El %PP ajustado debe ser un número válido';
    } else if (parseFloat(form.pp) < 0 || parseFloat(form.pp) > 1) {
        frontErrors.pp = 'El %PP ajustado debe estar entre 0 y 1';
    }

    // Compensación Ajustada
    if (!form.compensacion && form.compensacion !== 0) {
        frontErrors.compensacion = 'La compensación ajustada es obligatoria';
    } else if (isNaN(parseFloat(form.compensacion))) {
        frontErrors.compensacion = 'La compensación ajustada debe ser un número válido';
    }

    // Total Percepciones Ajustado
    if (!form.totalPercepciones && form.totalPercepciones !== 0) {
        frontErrors.totalPercepciones = 'El total de percepciones ajustado es obligatorio';
    } else if (isNaN(parseFloat(form.totalPercepciones))) {
        frontErrors.totalPercepciones = 'El total de percepciones ajustado debe ser un número válido';
    }

    // Tipo de Compensación Ajustado
    if (!form.tipoCompensacion) {
        frontErrors.tipoCompensacion = 'El tipo de compensación ajustado es obligatorio';
    }

    /* ======================
        VALIDACIONES DE CONSISTENCIA (OPCIONAL)
    ====================== */

    // Validaciones adicionales de consistencia (puedes comentarlas si no son necesarias)

    // Validar que los valores no sean negativos (excepto porcentajes que ya validamos)
    const decimalFields = [
        'salarioDiarioPrueba', 'netoSemanalPrueba', 'compensacionPrueba', 'totalPercepcionesPrueba',
        'salarioDiario', 'netoSemanal', 'compensacion', 'totalPercepciones'
    ];

    decimalFields.forEach(field => {
        const value = parseFloat(form[field]);
        if (!isNaN(value) && value < 0) {
            frontErrors[field] = frontErrors[field] || `${field.replace(/([A-Z])/g, ' $1').toLowerCase()} no puede ser negativo`;
        }
    });

    // Validar que el tipo de compensación sea válido
    const validTipoCompensacionValues = ['auto', 'efficiency', 'trial', 'none'];
    
    if (form.tipoCompensacionPrueba && !validTipoCompensacionValues.includes(form.tipoCompensacionPrueba)) {
        frontErrors.tipoCompensacionPrueba = frontErrors.tipoCompensacionPrueba || 'Tipo de compensación no válido';
    }
    
    if (form.tipoCompensacion && !validTipoCompensacionValues.includes(form.tipoCompensacion)) {
        frontErrors.tipoCompensacion = frontErrors.tipoCompensacion || 'Tipo de compensación no válido';
    }

    // Retorna true si no hay errores
    return Object.keys(frontErrors).length === 0;
};

const clearError = (path) => {
    if (frontErrors[path]) {
        delete frontErrors[path];
    }
};

const hydrateForm = (data) => {

    form.puesto = data.name ?? '';
    form.descripcion = data.description ?? '';
    form.salarioDiarioPrueba = data.daily_salary_in_trial ?? '';
    form.netoSemanalPrueba = data.net_in_trial ?? '';
    form.paPrueba = data.pa_in_trial ?? '';
    form.ppPrueba = data.pp_in_trial ?? '';
    form.compensacionPrueba = data.compensation_in_trial ?? '';
    form.totalPercepcionesPrueba = data.perceptions_in_trial ?? '';
    form.tipoCompensacionPrueba = data.type ?? '';
    form.salarioDiario = data.daily_salary ?? '';
    form.netoSemanal = data.net_in_adjust ?? '';
    form.pa = data.pa_adjust ?? '';
    form.pp = data.pp_adjust ?? '';
    form.compensacion = data.compensations_adjust ?? '';
    form.totalPercepciones = data.perceptions_adjust ?? '';
    form.tipoCompensacion = data.type_adjust ?? '';

};

onMounted(() => {
    hydrateForm(props.Position);
});

</script>

<template>
    <AppLayout title="Editar Puesto">
        <div class="card space-y-6">
            <h2 class="text-2xl font-bold mb-5">
                <i class="pi pi-pencil mr-2 text-warning"></i>
                Editar Puesto
            </h2>

            <div class="row">

                <div class="col-12 col-md-12 mb-4">
                    <label for="puesto" class="block font-bold mb-2">
                        Puesto <span class="text-red-500">*</span>
                    </label>
                    <InputText 
                        id="puesto" 
                        v-model="form.puesto" 
                        placeholder="Introduce el puesto" 
                        :class="{ 'p-invalid': frontErrors.puesto }" 
                        class="w-full"
                        @input="clearError('puesto')" 
                        required 
                    />
                    <Message
                        v-if="frontErrors.puesto"
                        severity="error"
                        size="medium"
                        variant="simple"
                    >
                        {{ frontErrors.puesto }}
                    </Message>
                </div>
            </div>

            <!-- Descripción -->
            <div class="row mb-5">
                <div class="col-12 mb-4">
                    <label for="descripcion" class="block font-bold mb-2">
                        Descripción <span class="text-red-500">*</span>
                    </label>
                    <Textarea 
                        id="descripcion" 
                        v-model="form.descripcion" 
                        placeholder="Escribe la descripción aquí..." 
                        :class="{ 'p-invalid': frontErrors.descripcion }" 
                        class="w-full"
                        rows="5"
                        :maxlength="500"
                        @input="clearError('descripcion')"
                    />
                    <small class="text-gray-500">{{ form.descripcion?.length || 0 }}/500</small>
                    <Message
                        v-if="frontErrors.descripcion"
                        severity="error"
                        size="medium"
                        variant="simple"
                    >
                        {{ frontErrors.descripcion }}
                    </Message>
                </div>
            </div>

            <Divider />
            <!-- Sección: Iniciales o Prueba -->
            <div class="mb-4">
                <h4 class="text-amber-600 font-bold mb-3">Iniciales o Prueba</h4>
                
                <!-- Salario Diario y Neto -->
                <div class="row mb-4">
                    <div class="col-12 col-md-6 mb-4">
                        <label for="salarioDiarioPrueba" class="block font-bold mb-2">
                            Salario Diario <span class="text-red-500">*</span>
                        </label>
                        <InputNumber 
                            id="salarioDiarioPrueba" 
                            v-model="form.salarioDiarioPrueba" 
                            placeholder="Introduce el salario de prueba" 
                            :inputClass="{ 'p-invalid': frontErrors.salarioDiarioPrueba }" 
                            class="w-full"
                            @blur="clearError('salarioDiarioPrueba')"
                            :min="0"
                        />
                        <Message
                            v-if="frontErrors.salarioDiarioPrueba"
                            severity="error"
                            size="medium"
                            variant="simple"
                        >
                            {{ frontErrors.salarioDiarioPrueba }}
                        </Message>
                    </div>

                    <div class="col-12 col-md-6 mb-4">
                        <label for="netoSemanalPrueba" class="block font-bold mb-2">
                            Neto <span class="text-red-500">*</span>
                        </label>
                        <InputNumber 
                            id="netoSemanalPrueba" 
                            v-model="form.netoSemanalPrueba"
                            placeholder="Introduce el neto semanal" 
                            :inputClass="{ 'p-invalid': frontErrors.netoSemanalPrueba }" 
                            class="w-full"
                            :min="0"
                            @blur="clearError('netoSemanalPrueba')"
                        />
                        <Message
                            v-if="frontErrors.netoSemanalPrueba"
                            severity="error"
                            size="medium"
                            variant="simple"
                        >
                            {{ frontErrors.netoSemanalPrueba }}
                        </Message>
                    </div>
                </div>

                <!-- %PA y %PP -->
                <div class="row mb-4">
                    <div class="col-12 col-md-6 mb-4">
                        <label for="paPrueba" class="block font-bold mb-2">
                            %PA <span class="text-red-500">*</span>
                        </label>
                        <InputNumber 
                            id="paPrueba" 
                            v-model="form.paPrueba" 
                            placeholder="Premios asistencia prueba" 
                            :inputClass="{ 'p-invalid': frontErrors.paPrueba }" 
                            class="w-full"
                            :min="0"
                            :max="1"
                            :step="0.01"
                            :minFractionDigits="1"
                            :maxFractionDigits="4"
                            mode="decimal"
                            locale="en-US"
                            @blur="clearError('paPrueba')"
                        />
                        <Message
                            v-if="frontErrors.paPrueba"
                            severity="error"
                            size="medium"
                            variant="simple"
                        >
                            {{ frontErrors.paPrueba }}
                        </Message>
                    </div>

                    <div class="col-12 col-md-6 mb-4">
                        <label for="ppPrueba" class="block font-bold mb-2">
                            %PP <span class="text-red-500">*</span>
                        </label>
                        <InputNumber 
                            id="ppPrueba" 
                            v-model="form.ppPrueba" 
                            placeholder="Premios puntualidad prueba" 
                            :inputClass="{ 'p-invalid': frontErrors.ppPrueba }" 
                            class="w-full"
                            :min="0"
                            :max="1"
                            :step="0.01"
                            :minFractionDigits="1"
                            :maxFractionDigits="4"
                            mode="decimal"
                            locale="en-US"
                            @blur="clearError('ppPrueba')"
                        />
                        <Message
                            v-if="frontErrors.ppPrueba"
                            severity="error"
                            size="medium"
                            variant="simple"
                        >
                            {{ frontErrors.ppPrueba }}
                        </Message>
                    </div>
                </div>

                <!-- Compensación, Total Percepciones, Tipo de compensación -->
                <div class="row mb-4">
                    <div class="col-12 col-md-4 mb-4">
                        <label for="compensacionPrueba" class="block font-bold mb-2">
                            Compensación <span class="text-red-500">*</span>
                        </label>
                        <InputNumber 
                            id="compensacionPrueba" 
                            v-model="form.compensacionPrueba" 
                            placeholder="Introduce la compensación de prueba" 
                            :inputClass="{ 'p-invalid': frontErrors.compensacionPrueba }" 
                            class="w-full"
                            :min="0"
                            @blur="clearError('compensacionPrueba')"
                        />
                        <Message
                            v-if="frontErrors.compensacionPrueba"
                            severity="error"
                            size="medium"
                            variant="simple"
                        >
                            {{ frontErrors.compensacionPrueba }}
                        </Message>
                    </div>

                    <div class="col-12 col-md-4 mb-4">
                        <label for="totalPercepcionesPrueba" class="block font-bold mb-2">
                            Total Percepciones <span class="text-red-500">*</span>
                        </label>
                        <InputNumber 
                            id="totalPercepcionesPrueba" 
                            v-model="form.totalPercepcionesPrueba" 
                            placeholder="Introduce la compensación de prueba" 
                            :inputClass="{ 'p-invalid': frontErrors.totalPercepcionesPrueba }" 
                            class="w-full"
                            :min="0"
                            @blur="clearError('totalPercepcionesPrueba')"
                        />
                        <Message
                            v-if="frontErrors.totalPercepcionesPrueba"
                            severity="error"
                            size="medium"
                            variant="simple"
                        >
                            {{ frontErrors.totalPercepcionesPrueba }}
                        </Message>
                    </div>

                    <div class="col-12 col-md-4 mb-4">
                        <label for="tipoCompensacionPrueba" class="block font-bold mb-2">
                            Tipo de compensación <span class="text-red-500">*</span>
                        </label>
                        <Select 
                            id="tipoCompensacionPrueba" 
                            v-model="form.tipoCompensacionPrueba" 
                            :options="tiposCompensacion" 
                            optionLabel="label" 
                            optionValue="value" 
                            placeholder="Seleccione un tipo" 
                            class="w-full" 
                            :class="{ 'p-invalid': frontErrors.tipoCompensacionPrueba }"
                            @change="clearError('tipoCompensacionPrueba')"
                        />
                        <Message
                            v-if="frontErrors.tipoCompensacionPrueba"
                            severity="error"
                            size="medium"
                            variant="simple"
                        >
                            {{ frontErrors.tipoCompensacionPrueba }}
                        </Message>
                    </div>
                    <span class="text-gray-500">
                        Esta compensación aplica solo durante el periodo de prueba.
                    </span>
                </div>
            </div>

            <Divider />

            <!-- Sección: Ajustados -->
            <div class="mt-5">
                <h4 class="text-amber-600 font-bold mb-3">Ajustados</h4>
                
                <!-- Salario Diario* y Neto -->
                <div class="row mb-4">
                    <div class="col-12 col-md-6 mb-4">
                        <label for="salarioDiario" class="block font-bold mb-2">
                            Salario Diario <span class="text-red-500">*</span>
                        </label>
                        <InputNumber 
                            id="salarioDiario" 
                            v-model="form.salarioDiario" 
                            placeholder="Introduce el salario diario" 
                            :inputClass="{ 'p-invalid': frontErrors.salarioDiario }" 
                            class="w-full"
                            :min="0"
                            @blur="clearError('salarioDiario')"
                        />
                        <Message
                            v-if="frontErrors.salarioDiario"
                            severity="error"
                            size="medium"
                            variant="simple"
                        >
                            {{ frontErrors.salarioDiario }}
                        </Message>
                    </div>

                    <div class="col-12 col-md-6 mb-4">
                        <label for="netoSemanal" class="block font-bold mb-2">
                            Neto <span class="text-red-500">*</span>
                        </label>
                        <InputNumber 
                            id="netoSemanal" 
                            v-model="form.netoSemanal" 
                            placeholder="Introduce el neto semanal" 
                            :inputClass="{ 'p-invalid': frontErrors.netoSemanal }" 
                            class="w-full"
                            :min="0"
                            @blur="clearError('netoSemanal')"
                        />
                        <Message
                            v-if="frontErrors.netoSemanal"
                            severity="error"
                            size="medium"
                            variant="simple"
                        >
                            {{ frontErrors.netoSemanal }}
                        </Message>
                    </div>
                </div>

                <!-- %PA y %PP -->
                <div class="row mb-4">
                    <div class="col-12 col-md-6 mb-4">
                        <label for="pa" class="block font-bold mb-2">
                            %PA <span class="text-red-500">*</span>
                        </label>
                        <InputNumber 
                            id="pa" 
                            v-model="form.pa" 
                            placeholder="Premios asistencia" 
                            :inputClass="{ 'p-invalid': frontErrors.pa }" 
                            class="w-full"
                            :min="0"
                            :max="1"
                            :step="0.01"
                            :minFractionDigits="1"
                            :maxFractionDigits="4"
                            mode="decimal"
                            locale="en-US"
                            @blur="clearError('pa')"
                        />
                        <Message
                            v-if="frontErrors.pa"
                            severity="error"
                            size="medium"
                            variant="simple"
                        >
                            {{ frontErrors.pa }}
                        </Message>
                    </div>

                    <div class="col-12 col-md-6 mb-4">
                        <label for="pp" class="block font-bold mb-2">
                            %PP <span class="text-red-500">*</span>
                        </label>
                        <InputNumber 
                            id="pp" 
                            v-model="form.pp" 
                            placeholder="Premios puntualidad" 
                            :inputClass="{ 'p-invalid': frontErrors.pp }" 
                            class="w-full"
                            :min="0"
                            :max="1"
                            :step="0.01"
                            :minFractionDigits="1"
                            :maxFractionDigits="4"
                            mode="decimal"
                            locale="en-US"
                            @blur="clearError('pp')"
                        />
                        <Message
                            v-if="frontErrors.pp"
                            severity="error"
                            size="medium"
                            variant="simple"
                        >
                            {{ frontErrors.pp }}
                        </Message>
                    </div>
                </div>

                <!-- Compensación*, Total Percepciones*, Tipo de compensación* -->
                <div class="row mb-4">
                    <div class="col-12 col-md-4 mb-4">
                        <label for="compensacion" class="block font-bold mb-2">
                            Compensación <span class="text-red-500">*</span>
                        </label>
                        <InputNumber 
                            id="compensacion" 
                            v-model="form.compensacion" 
                            placeholder="Introduce la compensación" 
                            :inputClass="{ 'p-invalid': frontErrors.compensacion }" 
                            class="w-full"
                            :min="0"
                            @blur="clearError('compensacion')"
                        />
                        <Message
                            v-if="frontErrors.compensacion"
                            severity="error"
                            size="medium"
                            variant="simple"
                        >
                            {{ frontErrors.compensacion }}
                        </Message>
                    </div>

                    <div class="col-12 col-md-4 mb-4">
                        <label for="totalPercepciones" class="block font-bold mb-2">
                            Total Percepciones <span class="text-red-500">*</span>
                        </label>
                        <InputNumber 
                            id="totalPercepciones" 
                            v-model="form.totalPercepciones" 
                            placeholder="Introduce el total de percepciones" 
                            :inputClass="{ 'p-invalid': frontErrors.totalPercepciones }" 
                            class="w-full"
                            :min="0"
                            @blur="clearError('totalPercepciones')"
                        />
                        <Message
                            v-if="frontErrors.totalPercepciones"
                            severity="error"
                            size="medium"
                            variant="simple"
                        >
                            {{ frontErrors.totalPercepciones }}
                        </Message>
                    </div>

                    <div class="col-12 col-md-4 mb-4">
                        <label for="tipoCompensacion" class="block font-bold mb-2">
                            Tipo de compensación <span class="text-red-500">*</span>
                        </label>
                        <Select 
                            id="tipoCompensacion" 
                            v-model="form.tipoCompensacion" 
                            :options="tiposCompensacion" 
                            optionLabel="label" 
                            optionValue="value" 
                            placeholder="Seleccione un tipo" 
                            class="w-full" 
                            :class="{ 'p-invalid': frontErrors.tipoCompensacion }"
                            @change="clearError('tipoCompensacion')"
                        />
                        <Message
                            v-if="frontErrors.tipoCompensacion"
                            severity="error"
                            size="medium"
                            variant="simple"
                        >
                            {{ frontErrors.tipoCompensacion }}
                        </Message>
                    </div>
                </div>
            </div>

            <!-- BOTONES ABAJO -->
            <div class="flex justify-end gap-3 pt-2">
                <Button label="Cancelar" icon="pi pi-times" severity="secondary" @click="cancel" :loading="canceling" :disabled="form.processing || canceling" />
                <div>
                    <Button label="Actualizar" icon="pi pi-save" severity="success" @click="submitForm" 
                    :loading="form.processing" :disabled="form.processing || canceling" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>