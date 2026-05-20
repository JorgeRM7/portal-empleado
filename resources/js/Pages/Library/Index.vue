<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref } from "vue";

const props = defineProps({
    Incidences: {
        type: Array,
        default: () => [],
    },
});

const loading = ref(true);
const otherFilterDialog = ref(false);

const openVideo = (url) => {
    if (!url) {
        alert("Esta incidencia no tiene video disponible.");
        return;
    }

    window.open(url, "_blank", "noopener,noreferrer");
};
</script>


<template>
    <AppLayout :title="'Biblioteca de incidencias'">
        <div class="card">
            <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <div
                        v-for="incidence in props.Incidences"
                        :key="incidence.id"
                        class="relative overflow-hidden rounded-2xl cursor-pointer group min-h-[230px] shadow-lg"
                        @click="openVideo(incidence.url_video)"
                    >
                        <!-- Fondo azul -->
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-700 via-blue-600 to-cyan-500"></div>

                        <!-- Decoración -->
                        <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full"></div>
                        <div class="absolute -bottom-14 -left-14 w-52 h-52 bg-white/10 rounded-full"></div>
                        <div class="absolute top-6 left-6 w-16 h-16 bg-white/10 rounded-full blur-sm"></div>

                        <!-- Contenido -->
                        <div class="relative z-10 h-full p-6 flex flex-col justify-between text-white">
                            <div>
                                <div class="flex items-center justify-between gap-3 mb-5">
                                    <span class="px-3 py-1 rounded-full bg-white/20 backdrop-blur text-xs font-semibold">
                                        {{ incidence.code }}
                                    </span>

                                    <span
                                        v-if="incidence.requires_auth"
                                        class="px-3 py-1 rounded-full bg-yellow-400/90 text-blue-950 text-xs font-semibold"
                                    >
                                        Requiere autorización
                                    </span>
                                </div>

                                <h5 class="text-xl font-bold mb-3 text-white">
                                    {{ incidence.name }}
                                </h5>

                                <p class="text-sm text-white/80 line-clamp-3">
                                    {{ incidence.description }}
                                </p>
                            </div>

                            <div class="flex items-center justify-between mt-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-14 h-14 rounded-full bg-white text-blue-700 flex items-center justify-center shadow-xl group-hover:scale-110 transition">
                                        <iconify-icon icon="solar:play-bold" class="text-3xl"></iconify-icon>
                                    </div>

                                    <div>
                                        <p class="mb-0 text-sm font-semibold text-white">
                                            Ver video
                                        </p>
                                        <p class="mb-0 text-xs text-white/70">
                                            Abrir en otra pestaña
                                        </p>
                                    </div>
                                </div>

                                <iconify-icon
                                    icon="solar:arrow-right-up-linear"
                                    class="text-3xl text-white/80 group-hover:translate-x-1 group-hover:-translate-y-1 transition"
                                ></iconify-icon>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="!props.Incidences || props.Incidences.length === 0"
                    class="text-center py-10 text-neutral-500 dark:text-neutral-300"
                >
                    No hay incidencias disponibles.
                </div>
            </div>
        </div>
    </AppLayout>
</template>