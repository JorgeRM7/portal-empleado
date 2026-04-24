<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref } from "vue";
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';

const props = defineProps({
    data: Array,
});

// Control del Dialog
const visible = ref(false);

const openDocument = () => {
    visible.value = true;
};
</script>

<template>
    <AppLayout :title="'Terminos y condiciones'">
        <div class="py-8 relative"> <div class="mx-auto px-4 sm:px-6 lg:px-8">

                <div class="absolute inset-0 pointer-events-none bg-[radial-gradient(circle_at_top_right,_rgba(59,130,246,0.12),_transparent_28%),radial-gradient(circle_at_bottom_left,_rgba(16,185,129,0.10),_transparent_30%)]"></div>

                <div class="relative px-6 py-8 md:px-8 md:py-10">
                    <Button
                        icon="pi pi-file"
                        label="Documento legal"
                        @click="openDocument"
                        rounded
                        class="bg-blue-600 border-none px-4 py-2 text-sm font-bold shadow-md hover:bg-blue-700 transition-colors"
                    />

                    <h1 class="mt-4 text-3xl md:text-5xl font-extrabold tracking-tight text-slate-900">
                        Terminos y condiciones
                    </h1>

                    <p class="mt-3 max-w-3xl text-sm md:text-base leading-relaxed text-slate-600">
                        Lea atentamente estos términos y condiciones antes de utilizar esta plataforma.
                    </p>
                </div>

                <div class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
                    Ultima actualización: {{ new Date().toLocaleDateString() }}
                </div>
            </div>
        </div>

        <Dialog
            v-model:visible="visible"
            modal
            header="Terminos y Condiciones"
            :style="{ width: '70vw' }"
            :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
        >
            <div class="p-4 border-1 surface-border border-round surface-section">
                <p class="leading-normal">
                    PDF
                </p>
            </div>

            <template #footer>
                <Button label="Cerrar" icon="pi pi-check" @click="visible = false" autofocus />
            </template>
        </Dialog>

    </AppLayout>
</template>
