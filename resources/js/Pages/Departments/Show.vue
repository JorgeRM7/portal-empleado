<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, reactive, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    Departments: Object,
    PayrollTypes: Object,
});

console.log(props.Departments);

const canceling = ref(false);

const cancel = () => {
    if (form.processing || canceling.value) return;

    canceling.value = true;

    router.get('/catalogs/departments', {}, {
        preserveScroll: true,
        onFinish: () => {
            canceling.value = false;
        }
    });
};

const frontErrors = reactive({});

// --- Formulario Principal ---
const form = useForm({
    name: '',
    external_id: null,
    payroll_type_id: null,
    holiday: false,
    description: '',
});

const clearError = (path) => {
    if (frontErrors[path]) {
        delete frontErrors[path];
    }
};

const hydrateForm = (data) => {

    form.name = data.name ?? '';
    form.external_id = data.external_id ?? null;
    form.payroll_type_id = data.payroll_type_id ?? null;
    form.holiday = data.holiday ?? false;
    form.description = data.description ?? '';

};

onMounted(() => {
    hydrateForm(props.Departments);
});

</script>

<template>
    <AppLayout title="Ver Departamento">
        <div class="card space-y-6">
            <h2 class="text-2xl font-bold mb-5">
                <i class="pi pi-eye mr-2 text-info"></i>
                Detalle del Departamento
            </h2>

            <div>
                <div class="row mb-4">
                    <div class="col-12 col-md-6 mb-4">
                        <label for="name" class="block font-bold mb-2">
                            Nombre <span class="text-red-500">*</span>
                        </label>
                        <InputText 
                            id="name" 
                            v-model="form.name" 
                            placeholder="Ingresa un Nombre" 
                            class="w-full"
                            :class="{ 'p-invalid': frontErrors.name }"    
                            @input="clearError('name')" required 
                            readonly
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

                    <div class="col-12 col-md-6 mb-4">
                        <label for="external_id" class="block font-bold mb-2">
                            Id Netsuite
                        </label>
                        <InputNumber 
                            id="external_id" 
                            v-model="form.external_id"
                            placeholder="Introduce el id de netsuite" 
                            :inputClass="{ 'p-invalid': frontErrors.external_id }" 
                            class="w-full"
                            :min=null
                            @blur="clearError('external_id')"
                            readonly
                        />
                        <Message
                            v-if="frontErrors.external_id"
                            severity="error"
                            size="medium"
                            variant="simple"
                        >
                            {{ frontErrors.external_id }}
                        </Message>
                    </div>

                    <div class="col-12 col-md-6 mb-4">
                        <label for="payroll_type_id" class="block font-bold mb-2">
                            Tipo de Asiento
                        </label>
                        <Select 
                            id="payroll_type_id" 
                            v-model="form.payroll_type_id" 
                            :options="props.PayrollTypes" 
                            optionLabel="name" 
                            optionValue="id" 
                            placeholder="Seleccione un tipo de asiento" 
                            class="w-full" filter 
                            showClear 
                            :class="{ 'p-invalid': frontErrors.payroll_type_id }"
                            @change="clearError('payroll_type_id')"
                            disabled
                        />
                        <Message
                            v-if="frontErrors.payroll_type_id"
                            severity="error"
                            size="medium"
                            variant="simple"
                        >
                            {{ frontErrors.payroll_type_id }}
                        </Message>
                    </div>

                    <div class="col-12 col-md-6 mb-4 mt-auto">
                        <div class="card w-full shadow-none p-3">
                            <div class="flex align-items-center justify-content-between">
                                <div class="flex align-items-center">
                                    <label class="font-bold" for="comp-extra">Regla de día festivo</label>
                                </div>
                                <ToggleSwitch 
                                    id="comp-extra" 
                                    v-model="form.holiday" 
                                    readonly
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 mb-4">
                    <label for="description" class="block font-bold mb-2">
                        Descripción
                    </label>
                    <Textarea 
                        id="description" 
                        v-model="form.description" 
                        placeholder="Ingresa una descripción." 
                        :class="{ 'p-invalid': frontErrors.description }" 
                        class="w-full"
                        rows="5"
                        :maxlength="500"
                        @input="clearError('description')"
                        readonly
                    />
                    <small class="text-gray-500">{{ form.description?.length || 0 }}/500</small>
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

            <!-- BOTONES ABAJO -->
            <div class="flex justify-end gap-3 pt-2">
                <Button label="Volver" icon="pi pi-arrow-left" severity="secondary" @click="cancel" :loading="canceling" :disabled="form.processing || canceling" />
            </div>
        </div>
    </AppLayout>
</template>