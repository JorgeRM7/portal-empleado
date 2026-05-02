<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, onMounted, computed } from "vue";
import axios from "axios";
import Card from "primevue/card";
import Button from "primevue/button";
import Avatar from "primevue/avatar";
import Skeleton from "primevue/skeleton";
import Dialog from "primevue/dialog";
import Divider from "primevue/divider";

// Props
const props = defineProps({
    posts: Array,
});

// Reactive data
const postsList = ref(props.posts.slice(0, 10)); // Mostrar solo los primeros 10
const allPosts = ref(props.posts); // Guardar todos los posts
const loading = ref(false);
const selectedPost = ref(null);
const expandModalVisible = ref(false);
const currentIndex = ref(10); // Índice del siguiente post a cargar
const postsPerPage = ref(5); // Cantidad de posts a cargar por scroll
const hasMore = computed(() => currentIndex.value < allPosts.value.length); // ¿Hay más posts?
const loadingMore = ref(false); // Indicador de carga de más posts
const sentinelElement = ref(null); // Elemento centinela para Intersection Observer

// Like logic
const toggleLike = async (post) => {
    if (isNaN(post.likes_count)) post.likes_count = 0;

    const originalStatus = post.user_liked;
    const originalCount = post.likes_count;

    post.user_liked = !post.user_liked;
    post.user_liked ? post.likes_count++ : post.likes_count--;

    try {
        const { data } = await axios.post(`/posts/${post.id}/like`);
        post.likes_count = data.likes_count;
        post.user_liked = data.user_liked;
    } catch (error) {
        console.error("Error al procesar el like", error);
        post.user_liked = originalStatus;
        post.likes_count = originalCount;
        alert("Error al procesar el like");
    }
};

// Expandir post
const expandPost = (post) => {
    selectedPost.value = post;
    expandModalVisible.value = true;
};

// Cargar más posts al llegar al final
const loadMorePosts = () => {
    if (loadingMore.value || !hasMore.value) return;

    loadingMore.value = true;

    // Simular pequeño delay para que el usuario vea que está cargando
    setTimeout(() => {
        const nextIndex = currentIndex.value + postsPerPage.value;
        const newPosts = allPosts.value.slice(currentIndex.value, nextIndex);
        postsList.value.push(...newPosts);
        currentIndex.value = nextIndex;
        loadingMore.value = false;
    }, 300);
};

// Configurar Intersection Observer para scroll infinito
const setupIntersectionObserver = () => {
    if (!sentinelElement.value) return;

    const observer = new IntersectionObserver(
        (entries) => {
            if (
                entries[0].isIntersecting &&
                hasMore.value &&
                !loadingMore.value
            ) {
                loadMorePosts();
            }
        },
        {
            root: null,
            rootMargin: "100px", // Cargar antes de que llegue al final
            threshold: 0.1,
        },
    );

    observer.observe(sentinelElement.value);

    // Retornar función para limpiar el observer
    return () => observer.disconnect();
};

// Verificar si es video
const isVideo = (path) => {
    if (!path) return false;
    const videoExtensions = ["mp4", "webm", "ogg", "mov"];
    const extension = path.split(".").pop().toLowerCase();
    return videoExtensions.includes(extension);
};

const mediaLoading = ref(true);

watch(expandModalVisible, (newValue) => {
    if (newValue) {
        mediaLoading.value = true;
    }
});

const handleMediaLoaded = () => {
    mediaLoading.value = false;
};

// WebSocket real-time
onMounted(() => {
    console.log("Iniciando escucha de WebSockets...");

    // Configurar Intersection Observer para scroll infinito
    setupIntersectionObserver();

    if (window.Echo) {
        window.Echo.leaveChannel("posts");

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

        window.Echo.channel("social-wall")
            .subscribed(() => {
                console.log("✅ ¡Suscrito con éxito al canal 'social-wall'!");
            })
            .listen(".PostCreated", (e) => {
                console.log("🚀 POST NUEVO RECIBIDO:", e.post);
                postsList.value.unshift(e.post);
                allPosts.value.unshift(e.post); // Agregar también a allPosts
                currentIndex.value++; // Aumentar el índice
            });

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
        <!-- Contenedor principal del feed -->
        <div class="news-feed-wrapper">
            <div class="news-feed-container">
                <div class="feed-header">
                    <h1 class="feed-title">Feed de Noticias</h1>
                </div>

                <!-- Posts -->
                <transition-group
                    name="fade-slide"
                    tag="div"
                    class="posts-list"
                >
                    <div
                        v-for="post in postsList"
                        :key="post.id"
                        class="post-wrapper"
                    >
                        <Card class="post-card">
                            <!-- Header del post -->
                            <template #title>
                                <div class="post-header">
                                    <div class="author-info">
                                        <Avatar
                                            :image="`https://nominas.grupo-ortiz.site/Librerias/img/Fotos/${post.user?.employee_id}.jpg`"
                                            shape="circle"
                                            class="author-avatar"
                                        />
                                        <div class="author-details">
                                            <div class="author-name">
                                                {{ post.user?.name }}
                                            </div>
                                            <div class="post-title">
                                                {{ post.title }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Imagen/Video del post -->
                            <template #header>
                                <div
                                    class="post-media-container"
                                    @click="expandPost(post)"
                                >
                                    <!-- Skeleton loading -->
                                    <div
                                        v-if="!post.isLoaded"
                                        class="skeleton-loader"
                                    >
                                        <Skeleton width="100%" height="100%" />
                                    </div>

                                    <!-- Video -->
                                    <video
                                        v-if="isVideo(post.path)"
                                        :src="`/storage/img/social/${post.path}`"
                                        class="post-media"
                                        :class="{ loaded: post.isLoaded }"
                                        controls
                                        @loadeddata="post.isLoaded = true"
                                    ></video>

                                    <!-- Imagen -->
                                    <img
                                        v-else
                                        :src="`/storage/img/social/${post.path}`"
                                        class="post-media"
                                        :class="{ loaded: post.isLoaded }"
                                        @load="post.isLoaded = true"
                                        loading="lazy"
                                        decoding="async"
                                    />

                                    <!-- Overlay expansión -->
                                    <div class="media-overlay">
                                        <div class="expand-icon">
                                            <i class="pi pi-search-plus"></i>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Descripción -->
                            <template #content>
                                <p class="post-description">
                                    {{ post.description }}
                                </p>
                            </template>

                            <!-- Footer con interacciones -->
                            <template #footer>
                                <div class="post-footer">
                                    <div class="likes-counter">
                                        <i class="pi pi-heart-fill"></i>
                                        <span>{{ post.likes_count ?? 0 }}</span>
                                    </div>

                                    <!-- Likers avatars -->
                                    <div
                                        v-if="
                                            post.likers &&
                                            post.likers.length > 0
                                        "
                                        class="likers-section"
                                    >
                                        <div class="likers-label">
                                            Le dieron like:
                                        </div>
                                        <div class="likers-container">
                                            <div
                                                v-for="liker in post.likers.slice(
                                                    0,
                                                    8,
                                                )"
                                                :key="liker.id"
                                                class="liker-avatar"
                                                :title="liker.name"
                                            >
                                                <Avatar
                                                    :image="`https://nominas.grupo-ortiz.site/Librerias/img/Fotos/${liker.id}.jpg`"
                                                    shape="circle"
                                                    size="small"
                                                    class="avatar-small"
                                                />
                                            </div>

                                            <!-- Mostrar +X si hay más -->
                                            <div
                                                v-if="post.likers.length > 8"
                                                class="likers-more"
                                                :title="`${post.likers
                                                    .slice(8)
                                                    .map((l) => l.name)
                                                    .join(', ')}`"
                                            >
                                                +{{ post.likers.length - 8 }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <Divider class="mt-10" />

                                <div class="post-actions">
                                    <Button
                                        @click="toggleLike(post)"
                                        :icon="
                                            post.user_liked
                                                ? 'pi pi-heart-fill'
                                                : 'pi pi-heart'
                                        "
                                        label="Me gusta"
                                        :class="[
                                            'action-button',
                                            {
                                                liked: post.user_liked,
                                            },
                                        ]"
                                    />

                                    <!-- <Button
                                        icon="pi pi-comment"
                                        label="Comentar"
                                        class="action-button"
                                    />

                                    <Button
                                        icon="pi pi-share-alt"
                                        label="Compartir"
                                        class="action-button"
                                    /> -->
                                </div>
                            </template>
                        </Card>
                    </div>
                </transition-group>

                <!-- Centinela para scroll infinito -->
                <div
                    ref="sentinelElement"
                    class="scroll-sentinel"
                    v-show="hasMore"
                >
                    <!-- Indicador de carga de más posts -->
                    <div v-if="loadingMore" class="loading-more">
                        <Skeleton width="100%" height="300px" class="mb-4" />
                        <Skeleton width="100%" height="300px" class="mb-4" />
                    </div>
                </div>

                <!-- Mensaje cuando no hay más posts -->
                <div
                    v-if="!hasMore && postsList.length > 0"
                    class="no-more-posts"
                >
                    <p>No hay más publicaciones</p>
                </div>
            </div>
        </div>

        <!-- Modal de expansión del post -->
        <Dialog
            v-model:visible="expandModalVisible"
            modal
            :header="`Publicación de ${selectedPost?.user?.name}`"
            :style="{ width: '90vw', maxWidth: '900px' }"
            @hide="selectedPost = null"
            class="expand-dialog"
        >
            <div v-if="selectedPost" class="expanded-post">
                <!-- Media expandida con Skeleton -->
                <div class="expanded-media">
                    <!-- Skeleton: Solo se muestra mientras mediaLoading sea true -->
                    <Skeleton
                        v-if="mediaLoading"
                        width="100%"
                        height="450px"
                        class="expanded-skeleton"
                    />

                    <!-- Video -->
                    <video
                        v-if="isVideo(selectedPost.path)"
                        :src="`/storage/img/social/${selectedPost.path}`"
                        class="expanded-video"
                        :class="{ 'hidden-media': mediaLoading }"
                        controls
                        autoplay
                        @loadeddata="handleMediaLoaded"
                    ></video>

                    <!-- Imagen -->
                    <img
                        v-else
                        :src="`/storage/img/social/${selectedPost.path}`"
                        class="expanded-image"
                        :class="{ 'hidden-media': mediaLoading }"
                        @load="handleMediaLoaded"
                    />
                </div>

                <!-- Contenido del post expandido -->
                <div class="expanded-content">
                    <div class="expanded-header">
                        <div class="author-section">
                            <Avatar
                                :image="`https://nominas.grupo-ortiz.site/Librerias/img/Fotos/${selectedPost.user?.employee_id}.jpg`"
                                shape="circle"
                                size="large"
                            />
                            <div class="author-info-expanded">
                                <div class="author-name">
                                    {{ selectedPost.user?.name }}
                                </div>
                                <div class="post-time">Hace poco</div>
                            </div>
                        </div>
                    </div>

                    <Divider />

                    <div class="expanded-text">
                        <h3 class="expanded-title">{{ selectedPost.title }}</h3>
                        <p class="expanded-description">
                            {{ selectedPost.description }}
                        </p>
                    </div>

                    <Divider />

                    <div class="expanded-stats">
                        <span class="stat-item">
                            <i class="pi pi-heart-fill"></i>
                            {{ selectedPost.likes_count ?? 0 }} Me gusta
                        </span>
                    </div>

                    <div
                        v-if="selectedPost.likers?.length > 0"
                        class="expanded-likers"
                    >
                        <div class="expanded-likers-label">Le dieron like:</div>
                        <div class="expanded-likers-list">
                            <div
                                v-for="(liker, index) in selectedPost.likers"
                                :key="liker.id"
                                class="expanded-liker-item"
                            >
                                <Avatar
                                    :image="`https://nominas.grupo-ortiz.site/Librerias/img/Fotos/${liker.id}.jpg`"
                                    shape="circle"
                                    size="small"
                                    class="avatar-small"
                                />
                                <span class="liker-name">{{ liker.name }}</span>
                                <span
                                    v-if="
                                        index < selectedPost.likers.length - 1
                                    "
                                    class="liker-separator"
                                    >•</span
                                >
                            </div>
                        </div>
                    </div>

                    <Divider />

                    <div class="expanded-actions">
                        <Button
                            @click="toggleLike(selectedPost)"
                            :icon="
                                selectedPost.user_liked
                                    ? 'pi pi-heart-fill'
                                    : 'pi pi-heart'
                            "
                            label="Me gusta"
                            :class="[
                                'action-button-expanded',
                                { liked: selectedPost.user_liked },
                            ]"
                        />
                    </div>
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
/* Variables CSS */
:root {
    --primary-bg: #ffffff;
    --secondary-bg: #f0f2f5;
    --border-color: #ccc;
    --text-primary: #050505;
    --text-secondary: #65676b;
    --accent-color: #e4405f;
    --hover-bg: #f2f2f2;
}

/* Feed wrapper */
.news-feed-wrapper {
    background-color: var(--secondary-bg);
    min-height: 100vh;
    padding: 20px 0;
}

.news-feed-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 0 12px;
}

/* Header */
.feed-header {
    margin-bottom: 24px;
}

.feed-title {
    font-size: 32px;
    font-weight: 800;
    color: var(--text-primary);
    margin: 0;
    letter-spacing: -0.5px;
}

/* Posts list */
.posts-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.post-wrapper {
    animation: slideUp 0.4s ease-out;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Card del post */
.post-card {
    border-radius: 8px !important;
    border: none !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
    transition: box-shadow 0.2s ease;
    overflow: hidden !important;
}

.post-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15) !important;
}

/* Post header */
.post-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 0;
    margin: 0;
}

.author-info {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.author-avatar {
    width: 40px !important;
    height: 40px !important;
    font-size: 18px !important;
}

.author-details {
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.author-name {
    font-weight: 500;
    color: var(--text-primary);
    font-size: 14px;
    line-height: 1.4;
}

.post-title {
    font-size: 12px;
    color: var(--text-secondary);
    margin-top: 2px;
}

/* Media container */
.post-media-container {
    position: relative;
    width: 100%;
    /* Eliminamos height: 0 y padding-bottom: 100% para que no sea un cuadrado forzado */
    height: auto;
    overflow: hidden;
    background-color: var(--secondary-bg);
    cursor: pointer;
    transition: all 0.3s ease;
    /* Ajustamos margenes para que se vea bien en el card */
    margin: 0 0 12px 0;
}

.post-media-container:hover .media-overlay {
    opacity: 1;
    visibility: visible;
}

.skeleton-loader {
    width: 100%;
    height: 300px; /* Una altura base mientras carga */
}

.post-media {
    /* Cambiamos de absolute a relative para que el contenedor reconozca su altura */
    position: relative;
    display: block;
    width: 100%;
    height: auto; /* Permite que la altura sea proporcional al ancho */
    max-height: 800px; /* Opcional: evita que imágenes excesivamente largas rompan el feed */
    object-fit: contain; /* Asegura que se vea toda la imagen sin recortes */
    opacity: 0;
    transition: opacity 0.3s ease;
}

.post-media.loaded {
    opacity: 1;
}

/* Overlay para expansión */
.media-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition:
        opacity 0.2s ease,
        visibility 0.2s ease;
    z-index: 2;
}

.expand-icon {
    color: white;
    font-size: 40px;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

/* Post description */
.post-description {
    color: var(--text-primary);
    font-size: 14px;
    line-height: 1.5;
    margin: 0;
    word-wrap: break-word;
}

/* Post footer */
.post-footer {
    padding: 8px 0;
    margin: 0 -16px -16px;
    padding: 8px 16px 0;
}

.likes-counter {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: var(--text-secondary);
    margin-bottom: 8px;
}

.likes-counter i {
    color: #e4405f;
    font-size: 14px;
}

/* Likers section */
.likers-section {
    margin-top: 12px;
    padding-top: 8px;
    border-top: 1px solid var(--border-color);
}

.likers-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--text-secondary);
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.likers-container {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.liker-avatar {
    display: flex;
    align-items: center;
    cursor: pointer;
    transition: transform 0.2s ease;
}

.liker-avatar:hover {
    transform: scale(1.1);
}

.avatar-small :deep(.p-avatar) {
    width: 28px !important;
    height: 28px !important;
    font-size: 12px !important;
}

.likers-more {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background-color: var(--secondary-bg);
    font-size: 11px;
    font-weight: 600;
    color: var(--text-secondary);
    border: 1px solid var(--border-color);
    cursor: help;
    transition: all 0.2s ease;
}

.likers-more:hover {
    background-color: #e8e8e8;
    transform: scale(1.1);
}

/* Post actions */
.post-actions {
    display: flex;
    gap: 12px;
    padding: 8px 0;
    margin: 0 -16px -16px;
    padding: 8px 16px 0;
}

.action-button {
    flex: 1;
    border: none !important;
    background: transparent !important;
    color: var(--text-secondary) !important;
    border-radius: 4px !important;
    height: 36px !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    transition: all 0.2s ease !important;
}

.action-button:hover {
    background-color: var(--hover-bg) !important;
    color: #0a66c2 !important;
}

.action-button.liked {
    color: #e4405f !important;
}

/* Loading section - Scroll Infinito */
.scroll-sentinel {
    height: 200px;
    margin-top: 24px;
}

.loading-more {
    padding: 0 12px;
}

.no-more-posts {
    text-align: center;
    padding: 32px 12px;
    color: var(--text-secondary);
    font-size: 14px;
    margin-top: 24px;
}

.no-more-posts p {
    margin: 0;
}

/* Dialog de expansión */
.expand-dialog :deep(.p-dialog-header) {
    border-bottom: 1px solid var(--border-color);
    padding: 16px;
}

.expand-dialog :deep(.p-dialog-content) {
    padding: 0 !important;
}

.expanded-post {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
    max-height: 600px;
    height: 100%;
}

.expanded-media {
    background-color: var(--secondary-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 300px;
    overflow: hidden;
}

.expanded-image,
.expanded-video {
    max-width: 100%;
    max-height: 100%;
    width: auto;
    height: auto;
    object-fit: contain;
}

.expanded-content {
    display: flex;
    flex-direction: column;
    padding: 16px;
    overflow-y: auto;
    background-color: var(--primary-bg);
}

.expanded-header {
    display: flex;
    align-items: center;
    gap: 12px;
}

.author-section {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.author-info-expanded {
    display: flex;
    flex-direction: column;
}

.author-name {
    font-weight: 500;
    color: var(--text-primary);
    font-size: 14px;
}

.post-time {
    font-size: 12px;
    color: var(--text-secondary);
}

.expanded-text {
    flex: 1;
}

.expanded-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 8px 0;
}

.expanded-description {
    font-size: 14px;
    color: var(--text-primary);
    line-height: 1.5;
    margin: 0;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.expanded-stats {
    display: flex;
    gap: 16px;
    font-size: 12px;
    color: var(--text-secondary);
    padding: 8px 0;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

.stat-item i {
    color: #e4405f;
}

/* Expanded likers section */
.expanded-likers {
    margin: 8px 0;
    padding: 8px 0;
}

.expanded-likers-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--text-secondary);
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.expanded-likers-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}

.expanded-liker-item {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    background-color: var(--secondary-bg);
    border-radius: 20px;
    font-size: 12px;
    color: var(--text-primary);
    transition: all 0.2s ease;
}

.expanded-liker-item:hover {
    background-color: #e8e8e8;
}

.liker-name {
    font-weight: 500;
}

.liker-separator {
    color: var(--text-secondary);
    margin-left: 2px;
}

.expanded-actions {
    display: flex;
    gap: 8px;
    padding: 8px 0 0;
}

.action-button-expanded {
    flex: 1;
    border: none !important;
    background: transparent !important;
    color: var(--text-secondary) !important;
    border-radius: 4px !important;
    height: 32px !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    transition: all 0.2s ease !important;
}

.action-button-expanded:hover {
    background-color: var(--hover-bg) !important;
    color: #0a66c2 !important;
}

.action-button-expanded.liked {
    color: #e4405f !important;
}

/* Transiciones */
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.3s ease;
}

.fade-slide-enter-from {
    opacity: 0;
    transform: translateY(10px);
}

.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

/* Responsive */
@media (max-width: 768px) {
    .expanded-post {
        grid-template-columns: 1fr;
        max-height: none;
    }

    .expanded-media {
        min-height: 400px;
        order: -1;
    }

    .expanded-content {
        max-height: 300px;
    }

    .feed-title {
        font-size: 24px;
    }

    .action-button,
    .action-button-expanded {
        font-size: 12px !important;
    }
}

@media (max-width: 480px) {
    .news-feed-container {
        padding: 0;
    }

    .post-wrapper {
        border-radius: 0;
    }

    .post-card {
        border-radius: 0 !important;
    }

    .feed-title {
        padding: 0 12px;
        font-size: 20px;
    }

    .feed-header {
        padding: 12px;
        margin-bottom: 12px;
    }

    .expand-dialog :deep(.p-dialog) {
        width: 100vw !important;
        max-width: 100vw !important;
        margin: 0 !important;
    }
}

.hidden-media {
    display: none;
}

.expanded-media {
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: #f4f4f4; /* Fondo neutro mientras carga */
    min-height: 300px;
}

.expanded-skeleton {
    border-radius: 8px;
}

.expanded-image,
.expanded-video {
    max-width: 100%;
    max-height: 70vh;
    object-fit: contain;
}
</style>
