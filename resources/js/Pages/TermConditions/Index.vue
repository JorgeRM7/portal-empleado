<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { onMounted, ref } from "vue";

const props = defineProps({
    data: Array,
});

onMounted(async () => {});

</script>

<template>
    <AppLayout :title="'Terminos y condiciones'">
        <div class="py-8">
            <div class="mx-auto px-4 sm:px-6 lg:px-8">
                
                <div
                    class="absolute inset-0 pointer-events-none bg-[radial-gradient(circle_at_top_right,_rgba(59,130,246,0.12),_transparent_28%),radial-gradient(circle_at_bottom_left,_rgba(16,185,129,0.10),_transparent_30%)]"
                ></div>

                <div class="relative px-6 py-8 md:px-8 md:py-10">
                    <div class="inline-flex items-center rounded-full bg-blue-600 px-4 py-2 text-sm font-bold text-white shadow">
                        <i class="pi pi-file mr-2"></i>
                        Documento legal
                    </div>

                    <h1 class="mt-4 text-3xl md:text-5xl font-extrabold tracking-tight dark:text-white">
                        Terminos y condiciones
                    </h1>

                    <p class="mt-3 max-w-3xl text-sm md:text-base dark:text-slate-300 leading-relaxed">
                        Lea atentamente estos términos y condiciones antes de utilizar esta plataforma.
                    </p>
                </div>

                <div class="mt-6 text-center text-sm  dark:text-slate-400">
                    Ultima actualización: {{ new Date().toLocaleDateString() }}
                </div>
            </div>
        </div>
    </AppLayout>
</template>
