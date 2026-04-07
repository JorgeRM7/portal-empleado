<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { computed } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";

const props = defineProps({
    view: Object, // { id, name, url, base }
    allowedActions: Array, // ["create","read",...]
    permissionMatrix: Array, // [{ action, name, exists }]
});

const page = usePage();

const form = useForm({
    view_id: props.view.id,
    guard_name: "web",
    actions: props.permissionMatrix
        .filter((p) => p.exists)
        .map((p) => p.action),
});

const allSelected = computed(
    () => form.actions.length === props.allowedActions.length,
);

const toggleAll = (checked) => {
    form.actions = checked ? [...props.allowedActions] : [];
};

const preview = computed(() =>
    form.actions.map((a) => `${props.view.base}.${a}`),
);

const submit = () => {
    form.patch(route("permissions.updateByView"), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout title="Editar permisos">
        <div class="card border rounded-xl p-4">
            <div class="flex items-center justify-between mb-4">
                <h4 class="m-0">Permisos — {{ props.view.name }}</h4>
            </div>

            <div v-if="page.props.flash?.success" class="mb-3 text-sm">
                {{ page.props.flash.success }}
            </div>

            <div class="mb-4">
                <p class="">
                    URL: <span class="font-mono">{{ props.view.url }}</span>
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <Chip
                    v-for="p in props.permissionMatrix"
                    :key="p.name"
                    class="flex items-center gap-2 px-2 py-1 text-xs rounded-full bg-gray-50 border"
                >
                    <input
                        type="checkbox"
                        :value="p.action"
                        v-model="form.actions"
                    />
                    <span class="font-mono">{{ p.name }}</span>
                </Chip>
            </div>

            <p v-if="form.errors.actions" class="text-xs text-red-600 mt-2">
                {{ form.errors.actions }}
            </p>

            <div class="mt-6">
                <p class="text-sm font-medium mb-2">Preview</p>
                <div v-if="preview.length" class="flex flex-wrap gap-2">
                    <Chip
                        v-for="p in preview"
                        :key="p"
                        class="px-2 py-1 text-xs rounded-full bg-gray-100 border font-mono"
                    >
                        {{ p }}
                    </Chip>
                </div>
                <p v-else class="text-xs text-gray-400 italic">
                    No hay acciones seleccionadas.
                </p>
            </div>

            <div class="mt-6 flex gap-2 justify-end">
                <Button
                    class="px-4 py-2 rounded-lg border"
                    type="button"
                    @click="submit"
                    :loading="form.processing"
                >
                    Guardar cambios
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
