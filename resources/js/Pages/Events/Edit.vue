<script setup>
import { onMounted, ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { useForm, router, usePage } from '@inertiajs/vue3';
import { useToastService } from "@/Stores/toastService";

const { showSuccess, showError } = useToastService();
const page = usePage();

const event = page.props.event;

const form = useForm({
    id: event.id,
    title: event.title,
    holiday: event.holiday,
    description: event.description,
    color: event.color,
    all_day: event.all_day,
    start_date: new Date(event.start_date),
    end_date: event.end_date ? new Date(event.end_date) : null
});

const errors = ref({});

const validate = () => {
    errors.value = {};

    if (!form.title) errors.value.title = "El titulo es obligatorio";
    if (!form.start_date) errors.value.start_date = "La fecha de inicio es obligatoria";

    return Object.keys(errors.value).length === 0;
};

const submitForm = () => {

    if (!validate()) {
        showError('Hay campos obligatorios sin completar');
        return;
    }

    form.put(route('events.update', form.id), {
        preserveScroll: true,
        onSuccess: () => {
            showSuccess('Evento actualizado correctamente');
        },
        onError: (errors) => {
            Object.values(errors).forEach(err => showError(err));
        },
    });
};

const cancel = () => {
    router.get('/assistences/events');
};

</script>


<template>
    <AppLayout :title="'Actualizar evento'">
        <div class="p-4 lg:p-6">
            <div class="grid gap-4">
                <Card>
                    <template #title>
                        <h2 class="text-2xl font-bold mb-5">
                            <i class="pi pi-plus-circle mr-2 text-success"></i>
                            Actualizar evento
                        </h2>
                    </template>
                    <template #content>
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                            <div class="col-span-1 md:col-span-12">
                                <label class="block mb-1 font-medium">Tiulo</label>
                                <InputText
                                    v-model="form.title"
                                    class="w-full"
                                    placeholder="Escribe el nombre"
                                />
                                <small class="text-red-500" v-if="errors.title">
                                    {{ errors.title }}
                                </small>
                            </div>
                            <div class="col-span-1 md:col-span-6">
                                <label class="block mb-1 font-medium">Fecha de inicio</label>
                                <DatePicker id="datepicker-24h" v-model="form.start_date" showTime hourFormat="24" fluid />
                                <small class="text-red-500" v-if="errors.start_date">
                                    {{ errors.start_date }}
                                </small>
                            </div>
                            <div class="col-span-1 md:col-span-6">
                                <label class="block mb-1 font-medium">Fecha de fin</label>
                                <DatePicker id="datepicker-24h" v-model="form.end_date" showTime hourFormat="24" fluid />
                            </div>
                            <div class="col-span-1 md:col-span-4">
                                 <label class="block mb-1 font-medium">Todo el dia</label>
                                <ToggleSwitch v-model="form.all_day">
                                    <template #handle="{ checked }">
                                        <i :class="['!text-xs pi', { 'pi-check': checked, 'pi-times': !checked }]" />
                                    </template>
                                </ToggleSwitch>

                            </div>
                            <div class="col-span-1 md:col-span-4">
                                 <label class="block mb-1 font-medium">Feriado</label>
                                <ToggleSwitch v-model="form.holiday">
                                    <template #handle="{ checked }">
                                        <i :class="['!text-xs pi', { 'pi-check': checked, 'pi-times': !checked }]" />
                                    </template>
                                </ToggleSwitch>

                            </div>
                             <div class="col-span-1 md:col-span-4">
                                <label class="block mb-1 font-medium">Color</label>
                                <ColorPicker v-model="form.color" />
                            </div>
                            <div class="col-span-1 md:col-span-12">
                                <label class="block mb-1 font-medium">Descripción</label>
                                <Textarea v-model="form.description" rows="5" class="w-full" />
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
