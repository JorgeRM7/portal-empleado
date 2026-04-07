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
    <AppLayout title="Ver Puesto">
        <div class="card space-y-6">
            <h2 class="text-2xl font-bold mb-5">
                <i class="pi pi-eye mr-2 text-info"></i>
                Detalle del Puesto
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
                        readonly
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
                        readonly
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
                            readonly
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
                            readonly
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
                            readonly
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
                            readonly
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
                            readonly
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
                            readonly
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
                            disabled
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
                            readonly
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
                            readonly
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
                            readonly
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
                            readonly
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
                            readonly
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
                            readonly
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
                            disabled
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
                <Button label="Volver" icon="pi pi-arrow-left" severity="secondary" @click="cancel" :loading="canceling" :disabled="form.processing || canceling" />
            </div>
        </div>
    </AppLayout>
</template>