<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { computed, ref } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { useToast } from "primevue/usetoast";

const page = usePage();
const toast = useToast();
const user = computed(() => page.props?.user ?? page.props?.auth?.user ?? {});

const props = defineProps({
    idEmployee: Array,
});

const editing = ref(false);

const form = ref({
    name: "",
    email: "",
    phone: "",
    bio: "",
});

const changePasswordForm = useForm({
    password: "",
    password_confirmation: "",
    current_password: "",
});

const loading = ref(false);

const changingPassword = ref(false);

const initForm = () => {
    form.value = {
        name: user.value?.name ?? "",
        email: user.value?.email ?? "",
        phone: user.value?.phone ?? "",
        bio: user.value?.bio ?? "",
    };
};

initForm();

const initials = computed(() => {
    const n = (user.value?.name ?? "").trim();
    if (!n) return "U";
    const parts = n.split(/\s+/).slice(0, 2);
    return parts.map((p) => p[0]?.toUpperCase()).join("");
});

const createdAt = computed(() => {
    const raw = user.value?.created_at;
    if (!raw) return "";
    // Si viene como ISO string, se ve bien con toLocaleDateString
    const d = new Date(raw);
    if (Number.isNaN(d.getTime())) return String(raw);
    return d.toLocaleDateString();
});

const cancelEdit = () => {
    initForm();
    editing.value = false;
};

const save = async () => {
    editing.value = false;
};

const changePassword = () => {
    loading.value = true;
    changePasswordForm.put(route("user-password.update"), {
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Contraseña cambiada",
                detail: "Tu contraseña ha sido cambiada exitosamente.",
                life: 3000,
            });
            changingPassword.value = false;
            loading.value = false;
        },
        onError: (error) => {
            console.log(error);
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Por favor, verifica los datos ingresados.",
                life: 3000,
            });
            loading.value = false;
        },
    });
};
</script>

<template>
    <AppLayout title="Perfil">
        <Toast />

        <div class="mx-auto w-full p-4 md:p-6 space-y-6">
            <!-- Header -->
            <div
                class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-semibold">Perfil</h1>
                    <p class="text-sm text-surface-600 dark:text-surface-300">
                        Administra tu información personal y preferencias.
                    </p>
                </div>

                <div class="flex gap-2">
                    <Button
                        v-if="!editing"
                        label="Editar"
                        icon="pi pi-pencil"
                        @click="editing = true"
                    />
                    <template v-else>
                        <Button
                            label="Cancelar"
                            icon="pi pi-times"
                            severity="secondary"
                            outlined
                            @click="cancelEdit"
                        />
                        <Button
                            label="Guardar"
                            icon="pi pi-check"
                            @click="save"
                        />
                    </template>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-12">
                <!-- Left: Profile card -->
                <Card class="md:col-span-4">
                    <template #content>
                        <div
                            class="flex flex-col items-center text-center gap-3"
                        >
                            <img
                                class="w-16 h-16 rounded-full object-cover"
                                :src="`https://nominas.grupo-ortiz.site/Librerias/img/Fotos/${props.idEmployee[0].id}.jpg`"
                                @error="
                                    (e) =>
                                        (e.target.src =
                                            $page.props.auth.user?.profile_photo_url)
                                "
                                alt="Foto de perfil"
                            />
                            <div class="space-y-1">
                                <div class="text-lg font-semibold">
                                    {{ user?.name ?? "Usuario" }}
                                </div>
                                <div
                                    class="text-sm text-surface-600 dark:text-surface-300"
                                >
                                    {{ user?.email ?? "sin correo" }}
                                </div>
                            </div>

                            <Divider />

                            <div class="w-full space-y-3 text-left">
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <span
                                        class="text-sm text-surface-600 dark:text-surface-300"
                                        >ID</span
                                    >
                                    <span class="text-sm font-medium">{{
                                        user?.id ?? "-"
                                    }}</span>
                                </div>

                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <span
                                        class="text-sm text-surface-600 dark:text-surface-300"
                                        >Creado el</span
                                    >
                                    <span class="text-sm font-medium">{{
                                        createdAt || "-"
                                    }}</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Right: Details -->
                <div class="md:col-span-8 space-y-6">
                    <Card>
                        <template #title>Información</template>
                        <template #content>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium"
                                        >Nombre</label
                                    >
                                    <InputText
                                        v-model="form.name"
                                        :disabled="!editing"
                                        class="w-full"
                                        placeholder="Tu nombre"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium"
                                        >Email</label
                                    >
                                    <InputText
                                        v-model="form.email"
                                        :disabled="!editing"
                                        class="w-full"
                                        placeholder="correo@dominio.com"
                                    />
                                </div>
                            </div>
                        </template>
                    </Card>

                    <Card>
                        <template #title>Seguridad</template>
                        <template #content>
                            <div
                                class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
                            >
                                <div>
                                    <div class="font-medium">Contraseña</div>
                                    <div
                                        class="text-sm text-surface-600 dark:text-surface-300"
                                    >
                                        Actualiza tu contraseña regularmente.
                                    </div>
                                </div>

                                <Button
                                    label="Cambiar contraseña"
                                    icon="pi pi-lock"
                                    severity="secondary"
                                    outlined
                                    @click="
                                        changingPassword = !changingPassword
                                    "
                                    :loading="loading"
                                />
                            </div>

                            <div
                                v-if="changingPassword"
                                class="flex flex-col gap-2 pt-2"
                            >
                                <div class="flex gap-2">
                                    <div class="w-1/2">
                                        <InputText
                                            v-model="
                                                changePasswordForm.current_password
                                            "
                                            type="password"
                                            placeholder="Contraseña actual"
                                            class="w-full"
                                        />
                                    </div>
                                    <div class="w-1/2">
                                        <InputText
                                            v-model="
                                                changePasswordForm.password
                                            "
                                            type="password"
                                            placeholder="Contraseña"
                                            class="w-full mb-2"
                                        />
                                        <InputText
                                            v-model="
                                                changePasswordForm.password_confirmation
                                            "
                                            type="password"
                                            placeholder="Confirmar contraseña"
                                            class="w-full mb-2"
                                        />
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <Button
                                        label="Cambiar contraseña"
                                        severity="primary"
                                        @click="changePassword"
                                        :loading="loading"
                                    />
                                </div>
                            </div>

                            <Divider />

                            <div
                                class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
                            >
                                <div>
                                    <div class="font-medium">Cerrar sesión</div>
                                    <div
                                        class="text-sm text-surface-600 dark:text-surface-300"
                                    >
                                        Cierra la sesión en este dispositivo.
                                    </div>
                                </div>

                                <Button
                                    label="Cerrar sesión"
                                    icon="pi pi-sign-out"
                                    severity="danger"
                                    outlined
                                    @click="router.post(route('logout'))"
                                />
                            </div>
                        </template>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
