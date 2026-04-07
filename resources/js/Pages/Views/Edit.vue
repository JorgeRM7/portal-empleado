<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useForm, Link, router } from "@inertiajs/vue3";
import InputText from "primevue/inputtext";
import Dropdown from "primevue/dropdown";
import Button from "primevue/button";
import { useToastService } from "@/Stores/toastService";
import { ref } from "vue";

const { showSuccess, showError } = useToastService();

const props = defineProps({
    modules: {
        type: Array,
        default: () => [],
    },
    view: {
        type: Object,
        default: () => {},
    },
});

const loading = ref(false);
const confirmPermissions = ref(false);
const asignPermissions = ref(false);

const form = useForm({
    name: props.view.name,
    url: props.view.url,
    assignPermissions: false,
});

const confirmSubmit = () => {
    confirmPermissions.value = true;
};

const submit = () => {
    loading.value = true;
    form.assignPermissions = asignPermissions.value;
    form.put(route("views.update", props.view.id), {
        onSuccess: () => {
            form.reset();
            showSuccess();
        },
        onError: () => {
            showError();
        },
        onFinish: () => {
            loading.value = false;
        },
    });
};

console.log(props.view);
</script>

<template>
    <AppLayout title="Editar Vista">
        <div class="card">
            <div class="flex items-center justify-between mb-4">
                <h4 class="m-0">Editar Vista</h4>
            </div>

            <form
                @submit.prevent="submit"
                class="grid grid-cols-1 md:grid-cols-2 gap-4"
            >
                <!-- Nombre de la vista -->
                <div class="flex flex-col gap-2">
                    <label for="name" class="font-medium"
                        >Nombre de la vista</label
                    >
                    <InputText
                        id="name"
                        v-model="form.name"
                        placeholder="Ej. Gestión de usuarios"
                        :class="{ 'p-invalid': form.errors.name }"
                    />
                    <small v-if="form.errors.name" class="p-error">
                        {{ form.errors.name }}
                    </small>
                </div>

                <!-- URL de la vista -->
                <div class="flex flex-col gap-2">
                    <label for="url" class="font-medium">URL</label>
                    <InputText
                        id="url"
                        v-model="form.url"
                        placeholder="Ej. /users"
                        :class="{ 'p-invalid': form.errors.url }"
                    />
                    <small v-if="form.errors.url" class="p-error">
                        {{ form.errors.url }}
                    </small>
                </div>

                <!-- Botones -->
                <div class="md:col-span-2 flex justify-end gap-2 mt-2">
                    <Button
                        type="button"
                        label="Cancelar"
                        severity="secondary"
                        outlined
                        @click="router.visit('/views')"
                        :disabled="form.processing"
                    />
                    <Button
                        label="Guardar vista"
                        :loading="form.processing"
                        @click="confirmSubmit"
                    />
                </div>
            </form>
        </div>
        <Dialog
            v-model:visible="confirmPermissions"
            :style="{ width: '450px' }"
            header="Asignar permisos"
            :modal="true"
        >
            <div class="flex items-center gap-4">
                <span
                    >Quieres editar también todos los permisos de esta
                    vista?</span
                >
            </div>
            <template #footer>
                <Button
                    label="No"
                    icon="pi pi-times"
                    text
                    @click="
                        () => {
                            asignPermissions = false;
                            submit();
                        }
                    "
                    severity="secondary"
                    variant="text"
                    :loading="loading"
                />
                <Button
                    label="Si"
                    icon="pi pi-check"
                    @click="
                        () => {
                            asignPermissions = true;
                            submit();
                        }
                    "
                    severity="success"
                    :loading="loading"
                />
            </template>
        </Dialog>
    </AppLayout>
</template>
