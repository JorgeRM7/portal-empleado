<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    item: { type: Object, default: () => ({}) },
    index: { type: Number, default: 0 },
});

function checkActiveRoute(item) {
    if (!item.to) return false;

    const currentPath = window.location.pathname.replace(/\/$/, "");
    const itemPath = item.to.replace(/\/$/, "");

    return currentPath === itemPath || currentPath.startsWith(itemPath + "/");
}
</script>

<template>
    <li>
        <Link
            v-if="item.to"
            :href="item.to"
            :class="[
                'menu-item-link',
                { 'active-route text-primary': checkActiveRoute(item) },
            ]"
        >
            <i
                v-if="item.icon"
                :class="item.icon"
                class="layout-menuitem-icon"
            ></i>

            <div class="flex flex-col">
                <span>{{ item.label }}</span>
                <small v-if="item.section" class="menu-section-text">
                    <!-- {{ item.section }} -->
                </small>
            </div>
        </Link>

        <div v-else class="menu-item-link">
            <i
                v-if="item.icon"
                :class="item.icon"
                class="layout-menuitem-icon"
            ></i>

            <div class="flex flex-col">
                <span>{{ item.label }}</span>
                <small v-if="item.section" class="menu-section-text">
                    {{ item.section }}
                </small>
            </div>
        </div>
    </li>
</template>

<style scoped>
.menu-item-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.9rem 1rem;
    text-decoration: none;
    color: inherit;
    border-radius: 0.5rem;
    transition: all 0.2s ease;
    white-space: normal;
    word-break: break-word;
    margin-bottom: 0.5rem;
}

.menu-item-link:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

.layout-menuitem-icon {
    font-size: 1rem;
    flex-shrink: 0;
    min-width: 1.5rem;
}

.active-route {
    font-weight: 600;
    background-color: rgba(59, 130, 246, 0.12);
}

.menu-section-text {
    font-size: 0.72rem;
    opacity: 0.65;
}
</style>
