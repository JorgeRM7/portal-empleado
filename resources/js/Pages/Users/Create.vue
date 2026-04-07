<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";

import { reactive, ref } from "vue";
import InputText from "primevue/inputtext";
import Password from "primevue/password";
import Dropdown from "primevue/dropdown";
import Button from "primevue/button";
import Divider from "primevue/divider";
import { Link, router, useForm } from "@inertiajs/vue3";
import { useNotifications } from "@/composables/useNotifications";
import { useToastService } from "@/Stores/toastService";

const { showSuccess, showError } = useToastService();

const { sendNotification } = useNotifications();

const props = defineProps({
    branchOffices: Array,
});

const confirmPermissions = ref(false);
const loading = ref(false);
const asignPermissions = ref(false);

const form = useForm({
    username: "",
    name: "",
    email: "",
    password: "",
    branchOffice: [],
    asignPermissions: false,
});

const selectedBranchOffices = ref([]);

const onSubmit = () => {
    console.log("Enviando formulario", form, selectedBranchOffices.value);
    form.branchOffice = selectedBranchOffices.value;
    form.asignPermissions = asignPermissions.value;

    loading.value = true;

    form.post("/users", {
        onSuccess: () => {
            console.log("Usuario enviado");
            form.reset();
            showSuccess();
            sendNotification();
            loading.value = false;
        },
        onError: (e) => {
            console.log(e);
            showError();
            loading.value = false;
            confirmPermissions.value = false;
        },
    });
};
</script>

<template>
    <AppLayout :title="'Usuarios'">
        <div class="card">
            <div class="flex items-center justify-between mb-4">
                <h4 class="m-0">Añadir Nuevo Usuario</h4>
            </div>

            <form @submit.prevent="onSubmit" class="space-y-6">
                <!-- Secciones en 2 columnas -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Sección General -->
                    <div class="card border rounded-xl p-4">
                        <h5 class="text-sm font-semibold mb-4 text-gray-700">
                            General
                        </h5>

                        <div class="space-y-4">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                >
                                    Nombre de Usuario
                                </label>
                                <InputText
                                    v-model="form.username"
                                    class="w-full"
                                    placeholder="NOMBRE.APELLIDO"
                                />
                                <small
                                    v-if="form.errors.username"
                                    class="text-red-500"
                                    >El nombre de usuario no es valido o ya esta
                                    en uso.</small
                                >
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                >
                                    Nombre completo
                                </label>
                                <InputText
                                    v-model="form.name"
                                    class="w-full"
                                    placeholder="Nombre Completo"
                                />
                                <small
                                    v-if="form.errors.name"
                                    class="text-red-500"
                                    >El nombre no es valido.</small
                                >
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                >
                                    Correo electrónico
                                </label>
                                <InputText
                                    v-model="form.email"
                                    class="w-full"
                                    placeholder="correo@grupo-ortiz.com"
                                />
                                <small
                                    v-if="form.errors.email"
                                    class="text-red-500"
                                    >El email no es valido.</small
                                >
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                >
                                    Contraseña
                                </label>
                                <Password
                                    v-model="form.password"
                                    class="w-full"
                                    inputClass="w-full"
                                    placeholder="Contraseña"
                                    :feedback="false"
                                    toggleMask
                                />
                                <small
                                    v-if="form.errors.password"
                                    class="text-red-500"
                                    >La contraseña no es valida, debe tener
                                    minimo 8 caracteres.</small
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Sección Configuración -->
                    <div class="card border rounded-xl p-4 h-1/2">
                        <h5 class="text-sm font-semibold mb-4 text-gray-700">
                            Configuración
                        </h5>

                        <div class="space-y-4">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                >
                                    Planta
                                </label>
                                <Multiselect
                                    v-model="selectedBranchOffices"
                                    display="chip"
                                    :options="props.branchOffices"
                                    optionLabel="code"
                                    filter
                                    optionValue="id"
                                    placeholder="Selecciona una planta"
                                    class="w-full"
                                >
                                    <template #value="slotProps">
                                        <span
                                            v-if="
                                                !slotProps.value ||
                                                !slotProps.value.length
                                            "
                                        >
                                            Selecciona una planta
                                        </span>

                                        <span
                                            v-else-if="
                                                slotProps.value.length > 5
                                            "
                                        >
                                            {{ slotProps.value.length }} plantas
                                            seleccionadas
                                        </span>
                                    </template>
                                </Multiselect>
                            </div>

                            <!-- <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Rol
                                </label>
                                <Dropdown v-model="form.rol" :options="roles" optionLabel="label" optionValue="value"
                                    placeholder="Selecciona un rol" class="w-full" />
                            </div> -->
                        </div>
                    </div>
                </div>

                <Divider />

                <!-- Acciones -->
                <div class="flex justify-end gap-3">
                    <Button
                        type="button"
                        @click="() => router.visit('/users')"
                        label="Cancelar"
                        class="px-4"
                        severity="secondary"
                    />
                    <Button
                        type="button"
                        @click="confirmPermissions = true"
                        label="Guardar usuario"
                        class="px-4"
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
                <span>Quieres asignar permisos a este usuario creado?</span>
            </div>
            <template #footer>
                <Button
                    label="No"
                    icon="pi pi-times"
                    text
                    @click="
                        () => {
                            asignPermissions = false;
                            onSubmit();
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
                            onSubmit();
                        }
                    "
                    severity="success"
                    :loading="loading"
                />
            </template>
        </Dialog>
    </AppLayout>
</template>
