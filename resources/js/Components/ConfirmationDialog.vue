<script setup>
import { useLayout } from "@/Layouts/composables/layout";

const { isDark } = useLayout();

const props = defineProps({
    visible: {
        type: Boolean,
        required: true,
    },
    header: {
        type: String,
        required: true,
    },
    loading: {
        type: Boolean,
        required: true,
    },
    confirmOrDelete: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(["confirm", "cancel"]);
</script>

<template>
    <Dialog
        v-model:visible="props.visible"
        :style="{ width: '600px' }"
        :header="'Confirmar Accion'"
        :modal="true"
        v-if="props.confirmOrDelete === 'delete'"
    >
        <div
            :class="{
                'bg-red-50 border-l-4 border-red-500 p-4 rounded': !isDark,
                'bg-red-950 border-l-4 border-red-500 p-4 rounded': isDark,
            }"
        >
            <div class="flex items-center">
                <i
                    class="pi pi-exclamation-triangle text-red-600 text-3xl mr-3"
                ></i>
                <div>
                    <h3
                        :class="{
                            'font-bold text-red-800': !isDark,
                            'font-bold text-red-200': isDark,
                        }"
                    >
                        {{ props.header }}
                    </h3>
                    <p
                        :class="{
                            'text-sm text-red-700': !isDark,
                            'text-sm text-red-300': isDark,
                        }"
                    >
                        Esta accion no podrá ser deshecha
                    </p>
                </div>
            </div>
        </div>
        <template #footer>
            <Button
                label="No"
                icon="pi pi-times"
                text
                @click="emit('cancel')"
                severity="secondary"
                variant="text"
                :loading="props.loading"
            />
            <Button
                label="Si"
                icon="pi pi-check"
                @click="emit('confirm')"
                severity="danger"
                :loading="props.loading"
            />
        </template>
    </Dialog>

    <Dialog
        v-model:visible="props.visible"
        :style="{ width: '600px' }"
        :header="'Confirmar acción'"
        :modal="true"
        v-if="props.confirmOrDelete === 'confirm'"
    >
        <div
            :class="{
                'bg-green-50 border-l-4 border-green-500 p-4 rounded': !isDark,
                'bg-green-950 border-l-4 border-green-500 p-4 rounded': isDark,
            }"
        >
            <div class="flex items-center">
                <i
                    class="pi pi-exclamation-triangle text-green-600 text-3xl mr-3"
                ></i>
                <div>
                    <h3
                        :class="{
                            'font-bold text-green-800': !isDark,
                            'font-bold text-green-200': isDark,
                        }"
                    >
                        {{ props.header }}
                    </h3>
                    <p
                        :class="{
                            'text-sm text-green-700': !isDark,
                            'text-sm text-green-300': isDark,
                        }"
                    >
                        Esta accion no podrá ser deshecha
                    </p>
                </div>
            </div>
        </div>
        <template #footer>
            <Button
                label="No"
                icon="pi pi-times"
                text
                @click="emit('cancel')"
                severity="secondary"
                variant="text"
                :loading="props.loading"
            />
            <Button
                label="Si"
                icon="pi pi-check"
                @click="emit('confirm')"
                severity="success"
                :loading="props.loading"
            />
        </template>
    </Dialog>
</template>
