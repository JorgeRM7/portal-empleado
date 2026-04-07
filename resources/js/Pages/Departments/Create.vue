<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, reactive } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import { useToastService } from "@/Stores/toastService";

const props = defineProps({
    PayrollTypes: Object,
});

const canceling = ref(false);
const page = usePage();

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

const { showSuccessCustom, showErrorCustom, showValidationError } = useToastService();
const frontErrors = reactive({});

// --- Formulario Principal ---
const form = useForm({
    name: '',
    external_id: null,
    payroll_type_id: null,
    holiday: false,
    description: '',
});

const submitForm = () => {

    if (!validateForm()) {
        showValidationError('Hay campos obligatorios sin completar');
        return;
    }

    form.post('/catalogs/departments', {
        preserveScroll: true,
        onSuccess: () => {
            if (page.props.flash?.success) {
                showSuccessCustom(page.props.flash.success);
            }
            if (page.props.flash?.error) {
                showErrorCustom(page.props.flash.error);
            }
        },
        onError: (errors) => {
            Object.values(errors).forEach(err => showErrorCustom(err));
        },
    });

};

const validateForm = () => {
    // Limpiar errores previos
    Object.keys(frontErrors).forEach(k => delete frontErrors[k]);

    // Nombre
    if (!form.name) {
        frontErrors.name = 'El nombre es obligatorio';
    } else if (form.name.length > 255) {
        frontErrors.name = 'El nombre no debe exceder los 255 caracteres';
    }

    // Id Netsuite
    if (form.external_id && form.external_id < 0) {
        frontErrors.external_id = 'El Id de Netsuite debe ser un número positivo';
    }

    // Descripción
    if (form.description && form.description.length > 500) {
        frontErrors.description = 'La descripción no debe exceder los 500 caracteres';
    }

    // Retorna true si no hay errores
    return Object.keys(frontErrors).length === 0;
};

const clearError = (path) => {
    if (frontErrors[path]) {
        delete frontErrors[path];
    }
};

</script>

<template>
    <AppLayout title="Crear Departamento">
        <div class="card space-y-6">
            <h2 class="text-2xl font-bold mb-5">
                <i class="pi pi-plus-circle mr-2 text-success"></i>
                Crear Departamento
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
                <Button label="Cancelar" icon="pi pi-times" severity="secondary" @click="cancel" :loading="canceling" :disabled="form.processing || canceling" />
                <div>
                    <Button label="Guardar" icon="pi pi-save" severity="success" @click="submitForm" :loading="form.processing" :disabled="form.processing || canceling" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>