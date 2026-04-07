<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { onMounted, reactive, ref } from "vue";
import InputText from "primevue/inputtext";
import Password from "primevue/password";
import Button from "primevue/button";
import Divider from "primevue/divider";
import Select from "primevue/select";
import Chip from "primevue/chip";
import Skeleton from "primevue/skeleton";
import { router, useForm } from "@inertiajs/vue3";
import { useNotifications } from "@/composables/useNotifications";
import { useToastService } from "@/Stores/toastService";
import { useAuthz } from "@/composables/useAuthz";

const { sendNotification } = useNotifications();
const { showError, showSuccess } = useToastService();

const props = defineProps({
    user: Object,
    branchOffices: Array,
    branchOfficesUser: Array,
    availableRoles: Array,
});

const form = useForm({
    username: props.user.username,
    name: props.user.name,
    email: props.user.email,
    password: "",
    branchOffice: props.branchOfficesUser,
    role: props.user.roles?.[0]?.id ?? null,
});

console.log(props.user);

const loading = ref(false);
const permissionsState = reactive({});
const originalPermissionsState = reactive({});
const views = ref([]);
const loadingViews = ref(false);
const hasChanges = ref(false);

const onSubmit = () => {
    loading.value = true;
    form.put("/users/" + props.user.id, {
        onSuccess: () => {
            showSuccess("Usuario actualizado");
            loading.value = false;
        },
        onError: () => {
            showError();
            loading.value = false;
        },
    });
};

const checkChanges = () => {
    hasChanges.value = false;
    views.value.forEach((view) => {
        if (!permissionsState[view.id] || !originalPermissionsState[view.id])
            return;
        view.permissions.forEach((perm) => {
            if (
                permissionsState[view.id][perm.name] !==
                originalPermissionsState[view.id][perm.name]
            ) {
                hasChanges.value = true;
            }
        });
    });
};

const selectAllPermissions = (view) => {
    if (!permissionsState[view.id]) permissionsState[view.id] = {};
    view.permissions.forEach((p) => {
        permissionsState[view.id][p.name] = true;
    });
    checkChanges();
};

const savePermissions = () => {
    loading.value = true;
    if (!hasChanges.value) {
        showSuccess("No hay cambios para guardar");
        return;
    }

    const changes = [];

    views.value.forEach((view) => {
        if (!permissionsState[view.id]) return;

        view.permissions.forEach((perm) => {
            const current = permissionsState[view.id][perm.name];
            const original = originalPermissionsState[view.id]?.[perm.name];

            if (current !== original) {
                changes.push({
                    permission_id: perm.id,
                    permission_name: perm.name,
                    view_name: view.name,
                    assigned: current,
                });
            }
        });
    });

    router.post(
        route("users.permissions.save-overrides", props.user.id),
        {
            permissions: changes,
        },
        {
            onSuccess: () => {
                showSuccess();

                views.value.forEach((view) => {
                    if (!originalPermissionsState[view.id]) {
                        originalPermissionsState[view.id] = {};
                    }
                    view.permissions.forEach((perm) => {
                        originalPermissionsState[view.id][perm.name] =
                            permissionsState[view.id][perm.name];
                    });
                });

                hasChanges.value = false;
                router.reload({ only: ["auth"] });
                loading.value = false;
            },
            onError: (errors) => {
                console.error("❌ Error al guardar:", errors);
                showError();
                loading.value = false;
            },
        },
    );
};

const getBaseValue = (viewId, permName) => {
    const view = views.value.find((v) => v.id === viewId);
    if (!view) return false;
    const perm = view.permissions.find((p) => p.name === permName);
    return perm?.base_assigned ?? false;
};

const resetToRole = (viewId, permName) => {
    const baseValue = getBaseValue(viewId, permName);
    permissionsState[viewId][permName] = baseValue;
    checkChanges();
    showSuccess(`Permiso "${permName}" revertido a valor del rol`);
};

const hasOverride = (viewId, permName) => {
    const view = views.value.find((v) => v.id === viewId);
    if (!view) return false;
    const perm = view.permissions.find((p) => p.name === permName);
    return perm?.is_overridden ?? false;
};

onMounted(async () => {
    loadingViews.value = true;

    const response = await axios.get("/api/permissions-views-users", {
        params: { id: props.user.id },
    });

    views.value = response.data;

    views.value.forEach((view) => {
        permissionsState[view.id] = {};
        originalPermissionsState[view.id] = {};

        view.permissions.forEach((perm) => {
            permissionsState[view.id][perm.name] = perm.assigned;

            originalPermissionsState[view.id][perm.name] = perm.assigned;
        });
    });

    loadingViews.value = false;
});
</script>
<template>
    <AppLayout :title="'Usuarios'">
        <div class="card">
            <div class="flex items-center justify-between mb-4">
                <h4 class="m-0">Editar Usuario</h4>
            </div>

            <form @submit.prevent="onSubmit" class="space-y-6">
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
                                    >Nombre de Usuario</label
                                >
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
                                    >Nombre completo</label
                                >
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
                                    >Correo electrónico</label
                                >
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
                                    >Contraseña</label
                                >
                                <Password
                                    v-model="form.password"
                                    class="w-full"
                                    inputClass="w-full"
                                    :feedback="false"
                                    placeholder="********"
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
                    <div class="card border rounded-xl p-4">
                        <h5 class="text-sm font-semibold mb-4 text-gray-700">
                            Configuración
                        </h5>
                        <div class="space-y-4">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                    >Rol asignado</label
                                >
                                <Select
                                    v-model="form.role"
                                    :options="props.availableRoles"
                                    optionLabel="name"
                                    optionValue="id"
                                    placeholder="Selecciona un rol"
                                    class="w-full"
                                />
                                <small class="text-gray-500">
                                    Los permisos base vendrán de este rol.
                                    Puedes personalizarlos abajo.
                                </small>
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                    >Planta</label
                                >
                                <Multiselect
                                    v-model="form.branchOffice"
                                    display="chip"
                                    :default-value="props.branchOfficesUser"
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
                        </div>
                    </div>
                </div>

                <Divider />

                <div class="flex justify-end gap-3">
                    <Button
                        type="button"
                        label="Cancelar"
                        @click="router.visit('/users')"
                        class="px-4"
                        severity="secondary"
                    />
                    <Button
                        type="submit"
                        label="Guardar usuario"
                        class="px-4"
                        :loading="loading"
                    />
                </div>
            </form>
        </div>

        <!-- Sección de Permisos -->
        <div class="card mt-6">
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-semibold">Vistas y permisos</h1>
                    <Button
                        label="Guardar cambios de permisos"
                        icon="pi pi-save"
                        :loading="loading"
                        :disabled="!hasChanges || loadingViews"
                        @click="savePermissions"
                        severity="success"
                    />
                </div>

                <div
                    class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4"
                    v-if="!loadingViews"
                >
                    <div
                        v-for="view in views"
                        :key="view.id"
                        class="card border rounded-xl p-4 flex flex-col gap-3"
                    >
                        <div class="flex justify-between items-start">
                            <div>
                                <h2 class="text-lg font-semibold">
                                    {{ view.name }}
                                </h2>
                                <p class="text-xs text-gray-500">
                                    Base permisos:
                                    <span class="font-mono">{{
                                        view.base
                                    }}</span>
                                </p>
                                <p class="text-xs text-gray-400">
                                    URL:
                                    <span class="font-mono">{{
                                        view.url
                                    }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="mt-2">
                            <p class="text-xs font-medium text-gray-600 mb-1">
                                Permisos
                            </p>
                            <div class="mb-2">
                                <Chip
                                    class="flex items-center gap-1 px-2 py-1 text-xs rounded-full bg-gray-50 border"
                                >
                                    <input
                                        type="checkbox"
                                        class="rounded border-gray-300"
                                        @change="selectAllPermissions(view)"
                                        :checked="
                                            view.permissions.every(
                                                (p) =>
                                                    permissionsState[view.id]?.[
                                                        p.name
                                                    ],
                                            )
                                        "
                                    />
                                    <span class="font-mono"
                                        >Seleccionar todo</span
                                    >
                                </Chip>
                            </div>

                            <div
                                v-if="view.permissions?.length"
                                class="flex flex-wrap gap-2"
                            >
                                <Chip
                                    v-for="perm in view.permissions"
                                    :key="perm.name"
                                    class="flex items-center gap-1 px-2 py-1 text-xs rounded-full border transition-all"
                                    :class="{
                                        'bg-green-50 border-green-300 text-green-700':
                                            permissionsState[view.id][
                                                perm.name
                                            ] === true,
                                        'bg-red-50 border-red-300 text-red-700':
                                            permissionsState[view.id][
                                                perm.name
                                            ] === false,
                                        'bg-gray-50 border-gray-200 text-gray-600':
                                            permissionsState[view.id][
                                                perm.name
                                            ] === null,
                                        'ring-2 ring-orange-400': hasOverride(
                                            view.id,
                                            perm.name,
                                        ),
                                    }"
                                >
                                    <input
                                        type="checkbox"
                                        class="rounded border-gray-300 cursor-pointer"
                                        :disabled="loading"
                                        v-model="
                                            permissionsState[view.id][perm.name]
                                        "
                                        @change="checkChanges"
                                    />
                                    <span class="font-mono">{{
                                        perm.name
                                    }}</span>

                                    <!-- Badge de override -->
                                    <span
                                        v-if="hasOverride(view.id, perm.name)"
                                        class="ml-1 px-1.5 py-0.5 text-[9px] font-bold rounded bg-orange-100 text-orange-700 cursor-help"
                                        title="Permiso personalizado (anula el rol base)"
                                    >
                                        ✎
                                    </span>

                                    <!-- Botón revertir -->
                                    <button
                                        v-if="hasOverride(view.id, perm.name)"
                                        type="button"
                                        class="ml-1 text-[10px] text-gray-400 hover:text-blue-500 transition-colors"
                                        @click="resetToRole(view.id, perm.name)"
                                        title="Revertir a valor del rol"
                                        :disabled="loading"
                                    >
                                        ↺
                                    </button>
                                </Chip>
                            </div>
                            <p v-else class="text-xs text-gray-400 italic">
                                Esta vista aún no tiene permisos generados.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Skeletons -->
                <div
                    class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4"
                    v-if="loadingViews"
                >
                    <div
                        v-for="i in 6"
                        :key="i"
                        class="card border rounded-xl p-4 flex flex-col gap-3"
                    >
                        <Skeleton width="10rem" class="mb-2"></Skeleton>
                        <Skeleton width="5rem" class="mb-2"></Skeleton>
                        <Skeleton height=".5rem"></Skeleton>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
