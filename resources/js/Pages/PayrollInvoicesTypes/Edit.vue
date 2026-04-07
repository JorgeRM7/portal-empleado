<script setup>
import { computed, onMounted, ref, watch } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { useForm, router, usePage } from '@inertiajs/vue3';
import { useToastService } from "@/Stores/toastService";

const { showSuccess, showError } = useToastService();
const page = usePage();
const payrollInvoiceType = page.props.data || null;


const form = useForm({
    id: payrollInvoiceType?.id || null,
    name: payrollInvoiceType?.name || '',
    status: payrollInvoiceType?.status || '',
});
const loading = ref();
const errors = ref({});


const validate = () => {
    errors.value = {};

    if (!form.name) errors.value.name = "El nombre es obligatorio";
    return Object.keys(errors.value).length === 0;
};


const submitForm = () => {

    if (!validate()) {
        showError('Hay campos obligatorios sin completar');
        return;
    }

    form.put(route('payroll-invoice-types.update', form.id),{
        preserveScroll: true,
        onSuccess: () => {
            showSuccess('Cuenta actualizada correctamente');
            // form.reset();
        },
        onError: (errors) => {
            Object.values(errors).forEach(err => showError(err));
        },
    });
};

const cancel = () => {
    router.get('/payroll/payroll-invoice-types');
};

</script>

<template>
    <AppLayout :title="'Editar cuenta'">
        <div class="p-4 lg:p-6">
            <div class="grid gap-4">
                <Card>
                   
                    <template #title>
                        <h2 class="text-2xl font-bold mb-5">
                            <i class="pi pi-plus-circle mr-2 text-success"></i>
                            Editar tipo de recibo
                        </h2>
                    </template>
                    <template #content>
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                            <div class="col-span-1 md:col-span-6">
                                <label class="block mb-1 font-medium">Nombre</label>
                                <InputText
                                    v-model="form.name"
                                    class="w-full"
                                    placeholder="Escribe el nombre"
                                />
                                <small class="text-red-500" v-if="errors.name">
                                    {{ errors.name }}
                                </small>
                            </div>
                            <div class="col-span-1 md:col-span-6">
                                <label class="block mb-1 font-medium">Activo</label>

                                <Dropdown
                                    v-model="form.status"
                                    :options="[
                                        { label: 'Activo', value: 1 },
                                        { label: 'Inactivo', value: 0 }
                                    ]"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Selecciona estado"
                                    class="w-full"
                                />
                            </div>
                            
                            <div class="md:col-span-12 flex justify-end gap-3 mt-6 border-t pt-4">

                                <Button
                                    label="Cancelar"
                                    icon="pi pi-times"
                                    severity="secondary"
                                    @click="cancel"
                                />
                                <Button
                                    label="Guardar"
                                    icon="pi pi-save"
                                    severity="success"
                                    :loading="form.processing"
                                    :disabled="form.processing"
                                    @click="submitForm"
                                />
                            </div>
                        </div>
                    </template>
                </Card>                
            </div>
        </div>
    </AppLayout>
</template>
