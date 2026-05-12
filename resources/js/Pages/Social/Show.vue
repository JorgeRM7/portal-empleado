<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref } from "vue";
import axios from "axios";
import Card from "primevue/card";
import Button from "primevue/button";
import Avatar from "primevue/avatar";
import Skeleton from "primevue/skeleton";
import Divider from "primevue/divider";

const props = defineProps({
    post: Object,
});

// Reactive data
const post = ref(props.post[0]);
const loadingLike = ref(false);

// Like logic
const toggleLike = async () => {
    if (isNaN(post.value.likes_count)) post.value.likes_count = 0;

    const originalStatus = post.value.user_liked;
    const originalCount = post.value.likes_count;

    post.value.user_liked = !post.value.user_liked;
    post.value.user_liked ? post.value.likes_count++ : post.value.likes_count--;

    try {
        const { data } = await axios.post(`/posts/${post.value.id}/like`);
        post.value.likes_count = data.likes_count;
        post.value.user_liked = data.user_liked;
    } catch (error) {
        console.error("Error al procesar el like", error);
        post.value.user_liked = originalStatus;
        post.value.likes_count = originalCount;
        alert("Error al procesar el like");
    }
};

// Verificar si es video
const isVideo = (path) => {
    if (!path) return false;
    const videoExtensions = ["mp4", "webm", "ogg", "mov"];
    const extension = path.split(".").pop().toLowerCase();
    return videoExtensions.includes(extension);
};

// Función para detectar dimensiones cuando la imagen carga
const onImageLoad = (event) => {
    const img = event.target;
    post.value.imgWidth = img.naturalWidth;
    post.value.imgHeight = img.naturalHeight;
    post.value.isLoaded = true;
};

// Función para obtener aspectRatio de imagen
const getImageAspectRatio = () => {
    if (post.value.imgWidth && post.value.imgHeight) {
        return (post.value.imgWidth / post.value.imgHeight) * 100;
    }
    return 100;
};

// console.log(props.post);
</script>

<template>
    <AppLayout title="Publicación">
        <div class="post-detail-wrapper">
            <div class="post-detail-container">
                <!-- Breadcrumb / Navigation -->
                <div class="post-navigation">
                    <a href="/posts" class="back-link">
                        <i class="pi pi-arrow-left"></i>
                        Volver al feed
                    </a>
                </div>

                <!-- Post Card -->
                <Card class="post-detail-card">
                    <!-- Header del post -->
                    <template #title> </template>

                    <!-- Imagen/Video del post -->
                    <template #header>
                        <div class="post-header">
                            <div class="author-info">
                                <Avatar
                                    :image="`https://nominas.grupo-ortiz.site/Librerias/img/Fotos/${post.user?.employee_id}.jpg`"
                                    shape="circle"
                                    class="author-avatar"
                                    v-if="post.anonymous == 0"
                                />
                                <Avatar
                                    icon="pi pi-user"
                                    shape="circle"
                                    class="author-avatar"
                                    v-else
                                />

                                <div class="author-details">
                                    <div class="author-name">
                                        {{
                                            post.anonymous == 0
                                                ? post.user?.name
                                                : "Usuario Anónimo"
                                        }}
                                    </div>
                                    <div class="author-position">
                                        {{
                                            post.anonymous == 0
                                                ? post.user?.position
                                                : ""
                                        }}
                                    </div>
                                    <div class="post-time">
                                        {{ post.created_at }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div
                            class="post-media-container"
                            :style="{
                                paddingBottom: getImageAspectRatio() + '%',
                            }"
                        >
                            <!-- Skeleton loading -->
                            <div v-if="!post.isLoaded" class="skeleton-loader">
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
                                @load="onImageLoad($event)"
                            />
                        </div>
                    </template>

                    <!-- Título -->
                    <template #content>
                        <div class="post-detail-content">
                            <h1 class="post-title">{{ post.title }}</h1>
                            <p class="post-description">
                                {{ post.description }}
                            </p>
                        </div>
                    </template>

                    <!-- Footer con interacciones -->
                    <template #footer>
                        <div class="post-detail-footer">
                            <div class="likes-counter">
                                <i class="pi pi-heart-fill"></i>
                                <span>{{ post.likes_count ?? 0 }}</span>
                            </div>

                            <!-- Likers avatars -->
                            <div
                                v-if="post.likers && post.likers.length > 0"
                                class="likers-section"
                            >
                                <div class="likers-label">Le dieron like:</div>
                                <div class="likers-container">
                                    <div
                                        v-for="liker in post.likers.slice(0, 8)"
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
                                @click="toggleLike"
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
                                :loading="loadingLike"
                            />
                        </div>
                    </template>
                </Card>
            </div>
        </div>
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

/* Post detail wrapper */
.post-detail-wrapper {
    background-color: var(--secondary-bg);
    min-height: 100vh;
    padding: 20px 0;
}

.post-detail-container {
    max-width: 700px;
    margin: 0 auto;
    padding: 0 12px;
}

/* Navigation */
.post-navigation {
    margin-bottom: 24px;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #0a66c2;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    padding: 8px 12px;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.back-link:hover {
    background-color: var(--hover-bg);
    color: #0a5aa0;
}

.back-link i {
    font-size: 16px;
}

/* Post detail card */
.post-detail-card {
    border-radius: 8px !important;
    border: none !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
    overflow: hidden !important;
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
    width: 48px !important;
    height: 48px !important;
    font-size: 20px !important;
}

.author-details {
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.author-name {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 15px;
    line-height: 1.4;
}

.author-position {
    font-size: 12px;
    color: var(--text-secondary);
    margin-top: 2px;
    font-weight: 500;
}

.post-time {
    font-size: 12px;
    color: var(--text-secondary);
    margin-top: 2px;
}

/* Media container */
.post-media-container {
    position: relative;
    width: 100%;
    height: 0;
    overflow: hidden;
    background-color: var(--secondary-bg);
    margin: 0 -16px 0 -16px;
    width: calc(100% + 32px);
}

.skeleton-loader {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1;
}

.post-media {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.post-media.loaded {
    opacity: 1;
}

/* Post content */
.post-detail-content {
    padding: 0;
}

.post-title {
    font-size: 24px;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 12px 0;
    line-height: 1.3;
}

.post-description {
    color: var(--text-primary);
    font-size: 15px;
    line-height: 1.6;
    margin: 0;
    white-space: pre-wrap;
    word-wrap: break-word;
}

/* Post footer */
.post-detail-footer {
    padding: 8px 0;
    margin: 0 -16px -16px;
    padding: 8px 16px 0;
}

.likes-counter {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: var(--text-secondary);
    margin-bottom: 12px;
    font-weight: 600;
}

.likes-counter i {
    color: #e4405f;
    font-size: 16px;
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
    height: 40px !important;
    font-size: 15px !important;
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

/* Responsive */
@media (max-width: 768px) {
    .post-detail-container {
        padding: 0;
    }

    .post-detail-card {
        border-radius: 0 !important;
    }

    .post-navigation {
        padding: 12px;
        margin-bottom: 12px;
    }

    .post-title {
        font-size: 20px;
    }

    .author-avatar {
        width: 40px !important;
        height: 40px !important;
        font-size: 18px !important;
    }

    .post-description {
        font-size: 14px;
    }
}

@media (max-width: 480px) {
    .post-detail-wrapper {
        padding: 0;
    }

    .post-title {
        font-size: 18px;
    }

    .post-description {
        font-size: 13px;
    }

    .action-button {
        height: 36px !important;
        font-size: 13px !important;
    }

    .back-link {
        font-size: 12px;
        padding: 6px 8px;
    }
}
</style>
