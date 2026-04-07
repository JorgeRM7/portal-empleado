<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, reactive, onMounted } from "vue";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Divider from "primevue/divider";
import Chip from "primevue/chip";
import Skeleton from "primevue/skeleton";
import Select from "primevue/select";
import { router, useForm } from "@inertiajs/vue3";
import { useToastService } from "@/Stores/toastService";

const { showError, showSuccess } = useToastService();

const props = defineProps({
    role: Object,
    availableGuards: Array,
    views: Array, // Viene desde Inertia: vistas con permisos
});

// Formulario del rol
const form = useForm({
    name: props.role.name,
    guard_name: props.role.guard_name,
    permissions: [], // IDs de permisos asignados
});

const loading = ref(false);
const permissionsState = ref({});
const originalPermissionsState = ref({});
const hasChanges = ref(false);

// Inicializar estados de permisos
onMounted(() => {
    // Validar que props.views exista
    if (!props.views || !Array.isArray(props.views)) {
        console.warn("⚠️ props.views no está disponible");
        return;
    }

    props.views.forEach((view) => {
        // Inicializar el objeto para esta vista
        permissionsState.value[view.id] = {};
        originalPermissionsState.value[view.id] = {};

        view.permissions.forEach((perm) => {
            permissionsState.value[view.id][perm.name] = perm.assigned;
            originalPermissionsState.value[view.id][perm.name] = perm.assigned;

            if (perm.assigned) {
                if (!form.permissions.includes(perm.id)) {
                    form.permissions.push(perm.id);
                }
            }
        });
    });

    console.log("✅ Estados inicializados:", {
        viewsCount: props.views.length,
        permissionsState: Object.keys(permissionsState.value),
    });
});

// Detectar cambios en permisos
const checkChanges = () => {
    hasChanges.value = false;

    props.views.forEach((view) => {
        if (
            !permissionsState.value[view.id] ||
            !originalPermissionsState.value[view.id]
        )
            return;

        view.permissions.forEach((perm) => {
            const current = permissionsState.value[view.id][perm.name];
            const original = originalPermissionsState.value[view.id][perm.name];

            if (current !== original) {
                hasChanges.value = true;
            }
        });
    });

    if (form.name !== props.role.name) {
        hasChanges.value = true;
    }
};

// Toggle individual de permiso (solo UI)
const togglePermission = (viewId, perm) => {
    // Asegurar que el objeto existe
    if (!permissionsState.value[viewId]) {
        permissionsState.value[viewId] = {};
    }

    // Toggle del valor
    permissionsState.value[viewId][perm.name] =
        !permissionsState.value[viewId][perm.name];

    // Actualizar array de permisos del form
    const permIndex = form.permissions.indexOf(perm.id);
    if (permissionsState.value[viewId][perm.name]) {
        if (permIndex === -1) form.permissions.push(perm.id);
    } else {
        if (permIndex !== -1) form.permissions.splice(permIndex, 1);
    }

    checkChanges();
};

// Seleccionar/deseleccionar todos los permisos de una vista
const toggleViewPermissions = (view) => {
    // Asegurar inicialización
    if (!permissionsState.value[view.id]) {
        permissionsState.value[view.id] = {};
    }

    const allAssigned = allViewPermissionsAssigned(view);

    view.permissions.forEach((perm) => {
        permissionsState.value[view.id][perm.name] = !allAssigned;

        if (!allAssigned) {
            if (!form.permissions.includes(perm.id)) {
                form.permissions.push(perm.id);
            }
        } else {
            const index = form.permissions.indexOf(perm.id);
            if (index !== -1) form.permissions.splice(index, 1);
        }
    });

    checkChanges();
};

// Verificar si todos los permisos de una vista están seleccionados
const allViewPermissionsAssigned = (view) => {
    if (!view.permissions.length) return false;
    return view.permissions.every((p) => getPermissionState(view.id, p.name));
};

const onSubmit = () => {
    loading.value = true;

    form.put(`/roles/${props.role.id}`, {
        onSuccess: () => {
            showSuccess();
            loading.value = false;

            // Actualizar estados originales
            props.views.forEach((view) => {
                if (!originalPermissionsState.value[view.id]) {
                    originalPermissionsState.value[view.id] = {};
                }
                view.permissions.forEach((perm) => {
                    originalPermissionsState.value[view.id][perm.name] =
                        permissionsState.value[view.id][perm.name];
                });
            });
            checkChanges();
        },
        onError: (errors) => {
            showError();
            console.error(errors);
            loading.value = false;
        },
    });
};

const getPermissionState = (viewId, permName) => {
    if (!permissionsState.value[viewId]) {
        return false;
    }
    return permissionsState.value[viewId][permName] ?? false;
};

// Navegar de vuelta a lista de roles
const goBack = () => {
    router.visit("/roles");
};
</script>

<template>
    <AppLayout :title="`Editar Rol: ${role.name}`">
        <div class="card">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">
                        Editar Rol
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Configura el nombre y los permisos del rol
                    </p>
                </div>
                <Button
                    label="Volver"
                    severity="secondary"
                    @click="goBack"
                    icon="pi pi-arrow-left"
                />
            </div>

            <!-- Formulario del Rol -->
            <form @submit.prevent="onSubmit" class="space-y-6">
                <div class="grid grid-cols-1 gap-6">
                    <!-- Sección: Información del Rol -->
                    <div class="lg:col-span-1">
                        <div class="card border rounded-xl p-4">
                            <h3
                                class="text-sm font-semibold mb-4 text-gray-700 border-b pb-2"
                            >
                                Información del Rol
                            </h3>

                            <div class="space-y-4">
                                <!-- Nombre del Rol -->
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                    >
                                        Nombre del Rol *
                                    </label>
                                    <InputText
                                        v-model="form.name"
                                        class="w-full"
                                        placeholder="Ej: Administrador"
                                        @input="checkChanges"
                                    />
                                    <small
                                        v-if="form.errors.name"
                                        class="text-red-500 text-xs"
                                    >
                                        {{ form.errors.name }}
                                    </small>
                                </div>

                                <!-- Metadata -->
                                <Divider />
                                <div class="text-xs text-gray-500 space-y-1">
                                    <p><strong>ID:</strong> {{ role.id }}</p>
                                    <p>
                                        <strong>Creado:</strong>
                                        {{
                                            new Date(
                                                role.created_at,
                                            ).toLocaleDateString()
                                        }}
                                    </p>
                                    <p>
                                        <strong>Actualizado:</strong>
                                        {{
                                            new Date(
                                                role.updated_at,
                                            ).toLocaleDateString()
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección: Permisos por Vista -->
                    <div class="lg:col-span-2">
                        <div class="card border rounded-xl p-4">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-semibold text-gray-700">
                                    Permisos por Vista
                                </h3>
                                <Chip v-if="hasChanges" class="p-2">
                                    <Message
                                        severity="warn"
                                        icon="pi pi-exclamation-triangle"
                                        >Cambios Sin Guardar</Message
                                    >
                                </Chip>
                            </div>

                            <!-- Grid de Vistas -->
                            <div class="space-y-4">
                                <div
                                    v-for="view in props.views"
                                    :key="view.id"
                                    class="border rounded-lg p-4 hover:border-blue-300 transition-colors"
                                >
                                    <!-- Header de la Vista -->
                                    <div
                                        class="flex items-start justify-between mb-3"
                                    >
                                        <div>
                                            <h4 class="text-gray-800">
                                                {{ view.name }}
                                            </h4>
                                            <p
                                                class="text-xs text-gray-500 mt-0.5"
                                            >
                                                <span
                                                    class="font-mono bg-gray-100 px-1.5 py-0.5 rounded"
                                                    >{{ view.base }}</span
                                                >
                                                <span class="mx-1">•</span>
                                                <span
                                                    class="font-mono text-gray-400"
                                                    >{{ view.url }}</span
                                                >
                                            </p>
                                        </div>

                                        <!-- Checkbox "Seleccionar todo" por vista -->
                                        <Chip
                                            class="cursor-pointer transition-colors"
                                            @click="toggleViewPermissions(view)"
                                        >
                                            <input
                                                type="checkbox"
                                                class="mr-1 rounded border-gray-300"
                                                :checked="
                                                    allViewPermissionsAssigned(
                                                        view,
                                                    )
                                                "
                                            />
                                            <span class="text-xs font-mono"
                                                >Todo</span
                                            >
                                        </Chip>
                                    </div>

                                    <!-- Lista de Permisos -->
                                    <div
                                        v-if="view.permissions.length"
                                        class="flex flex-wrap gap-2"
                                    >
                                        <Chip
                                            v-for="perm in view.permissions"
                                            :key="perm.id"
                                            class="flex items-center gap-1 px-2 py-1 text-xs rounded-full border transition-all cursor-pointer select-none"
                                            :class="{
                                                'bg-green-50 border-green-300 text-green-700':
                                                    getPermissionState(
                                                        view.id,
                                                        perm.name,
                                                    ),
                                                'bg-gray-50 border-gray-200 text-gray-600 ':
                                                    !getPermissionState(
                                                        view.id,
                                                        perm.name,
                                                    ),
                                            }"
                                            @click="
                                                togglePermission(view.id, perm)
                                            "
                                        >
                                            <input
                                                type="checkbox"
                                                class="mr-1 rounded pointer-events-none"
                                                :checked="
                                                    getPermissionState(
                                                        view.id,
                                                        perm.name,
                                                    )
                                                "
                                                @click.stop
                                            />
                                            <span class="font-mono">{{
                                                perm.name
                                            }}</span>
                                        </Chip>
                                    </div>
                                    <p
                                        v-else
                                        class="text-xs text-gray-400 italic"
                                    >
                                        Sin permisos registrados para esta vista
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Acciones -->
                <Divider />
                <div class="flex items-center justify-end gap-3">
                    <Button
                        type="button"
                        label="Cancelar"
                        severity="secondary"
                        @click="goBack"
                        :disabled="loading"
                    />
                    <Button
                        type="submit"
                        label="Guardar Cambios"
                        icon="pi pi-save"
                        :loading="loading"
                        :disabled="!hasChanges || loading"
                        severity="success"
                    />
                </div>
            </form>
        </div>

        <!-- Toast para mensajes (si no usas el composable global) -->
        <Toast />
    </AppLayout>
</template>
