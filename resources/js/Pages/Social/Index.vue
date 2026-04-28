<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, onMounted } from "vue";
import axios from "axios"; // Asegúrate de tener axios importado
import Card from "primevue/card";
import Button from "primevue/button";
import Avatar from "primevue/avatar";
import Skeleton from "primevue/skeleton";

// 1. Recibir los datos reales desde el controlador (Inertia)
const props = defineProps({
    posts: Array,
});

// 2. Hacerlos reactivos para que la UI se actualice al recibir eventos de Echo
const postsList = ref(props.posts);
const loading = ref(false);

// --- LÓGICA DEL LIKE (CONECTADA AL BACKEND) ---
const toggleLike = async (post) => {
    // 1. Asegurar que el conteo sea un número antes de empezar
    if (isNaN(post.likes_count)) post.likes_count = 0;

    const originalStatus = post.user_liked;
    const originalCount = post.likes_count;

    // 2. Cambio visual inmediato (Optimistic UI)
    post.user_liked = !post.user_liked;
    post.user_liked ? post.likes_count++ : post.likes_count--;

    try {
        const { data } = await axios.post(`/posts/${post.id}/like`);

        // 3. Sincronizar con lo que el servidor mandó
        // Usamos los nombres exactos: likes_count y user_liked
        post.likes_count = data.likes_count;
        post.user_liked = data.user_liked;
    } catch (error) {
        // Revertir si algo sale mal
        console.error("Error al procesar el like", error);
        post.user_liked = originalStatus;
        post.likes_count = originalCount;
        alert("Error al procesar el like");
    }
};

const isVideo = (path) => {
    if (!path) return false;
    const videoExtensions = ["mp4", "webm", "ogg", "mov"];
    const extension = path.split(".").pop().toLowerCase();
    return videoExtensions.includes(extension);
};

// --- ESCUCHAR TIEMPO REAL ---
onMounted(() => {
    console.log("Iniciando escucha de WebSockets...");

    if (window.Echo) {
        // 1. Forzar salida de cualquier suscripción previa (limpieza)
        window.Echo.leaveChannel("posts");

        // 2. Suscribirse de nuevo
        window.Echo.channel("posts")
            .subscribed(() => {
                console.log("✅ ¡Suscrito con éxito al canal 'posts'!");
            })
            .listen(".LikeUpdated", (e) => {
                console.log("🔥 ¡EVENTO CAPTURADO!", e);

                const post = postsList.value.find((p) => p.id == e.postId);
                if (post) {
                    post.likes_count = parseInt(e.likeCount);
                    console.log(
                        `Nuevo conteo para post ${e.postId}: ${e.likeCount}`,
                    );
                }
            });

        // 3. Ver estado de conexión global
        console.log(
            "Estado de Echo:",
            window.Echo.connector.pusher.connection.state,
        );
    } else {
        console.error("❌ Echo no está definido. Revisa tu bootstrap.js");
    }
});
</script>

<template>
    <AppLayout title="Noticias">
        <div class="container mx-auto py-8" style="max-width: 600px">
            <h1 class="text-3xl font-bold mb-6 text-900 px-4">
                Feed de Noticias
            </h1>

            <div v-for="post in postsList" :key="post.id" class="mb-6 px-4">
                <Card class="overflow-hidden shadow-2 border-round-xl">
                    <template #header>
                        <video
                            v-if="isVideo(post.path)"
                            :src="`/storage/img/social/${post.path}`"
                            class="w-full h-full block object-cover transition-opacity duration-500"
                            :class="post.isLoaded ? 'opacity-100' : 'opacity-0'"
                            controls
                            @loadeddata="post.isLoaded = true"
                        ></video>

                        <img
                            v-else
                            :src="`/storage/img/social/${post.path}`"
                            class="w-full h-full block object-cover transition-opacity duration-500"
                            :class="post.isLoaded ? 'opacity-100' : 'opacity-0'"
                            @load="post.isLoaded = true"
                        />
                    </template>

                    <template #title>
                        <div class="flex align-items-center gap-3 mb-2">
                            <Avatar
                                :image="post.user?.avatar"
                                :label="
                                    !post.user?.avatar
                                        ? post.user?.name.charAt(0)
                                        : null
                                "
                                shape="circle"
                                class="bg-primary text-white"
                            />
                            <span class="text-lg font-semibold">{{
                                post.user?.name
                            }}</span>
                        </div>
                        <div class="text-sm text-500 font-medium ml-1">
                            {{ post.title }}
                        </div>
                    </template>

                    <template #content>
                        <p class="m-0 text-700 line-height-3">
                            {{ post.description }}
                        </p>
                    </template>

                    <template #footer>
                        <div
                            class="flex align-items-center justify-content-between pt-3 border-top-1 border-300"
                        >
                            <Button
                                @click="toggleLike(post)"
                                :icon="
                                    post.user_liked
                                        ? 'pi pi-heart-fill'
                                        : 'pi pi-heart'
                                "
                                :label="String(post.likes_count ?? 0)"
                                :class="
                                    post.user_liked
                                        ? 'p-button-danger'
                                        : 'p-button-text p-button-secondary'
                                "
                                rounded
                            />

                            <Button
                                icon="pi pi-share-alt"
                                class="p-button-text p-button-secondary"
                                rounded
                            />
                        </div>
                    </template>
                </Card>
            </div>

            <div v-if="loading" class="px-4">
                <Skeleton width="100%" height="300px" class="mb-4" />
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.container {
    background-color: #f8f9fa;
    min-height: 100vh;
}
</style>
