<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, onMounted, onUnmounted } from "vue";
import axios from "axios";

const props = defineProps({
    portals: Array,
    users: Number,
    user: Object,
});

console.log(props.user[0]);

const currentTime = ref(new Date().toLocaleTimeString());
const options = ref(["list", "grid"]);
const layout = ref("list");
const isLoading = ref(true);

const itemScreenshot = ref("");

const screenshot = (url) => {
    return `https://api.microlink.io/?url=${encodeURIComponent(url)}&screenshot=true&meta=false&embed=screenshot.url`;
};

let intervalId = null;

const getStatus = (portal) => {
    switch (portal.status) {
        case "active":
            return "success";

        case "maintenance":
            return "warn";

        case "inactive":
            return "danger";

        default:
            return null;
    }
};

const getActiveStatus = (portal) => {
    switch (portal.status) {
        case "active":
            return "Activo";
        case "maintenance":
            return "En mantenimiento";
        case "inactive":
            return "Inactivo";
    }
};

const abrirUrl = (url) => {
    window.open(url, "_blank");
};

onMounted(() => {
    intervalId = setInterval(() => {
        currentTime.value = new Date().toLocaleTimeString();
    }, 1000);
});

onUnmounted(() => {
    clearInterval(intervalId);
});
</script>

<template>
    <AppLayout title="Dashboard">
        <div class="flex h-1/6">
            <div class="w-1/2 mr-10 h-full card border-none">
                <div class="flex items-center">
                    <img
                        class="w-16 h-16 rounded-full object-cover"
                        :src="$page.props.auth.user?.profile_photo_url"
                        alt=""
                    />
                    <div>
                        <h1 class="text-xl font-normal mb-0 ml-5 font-display">
                            Bienvenid@ {{ $page.props.auth.user?.name }}
                        </h1>
                        <h2
                            class="text-sm text-gray-600 font-normal ml-5 mt-0 mb-0"
                        >
                            {{ props.user[0] }}
                        </h2>
                    </div>
                </div>
            </div>
            <div class="card mb-0 w-1/4 mr-10 border-none">
                <div class="flex justify-between">
                    <div>
                        <h3 class="block text-lg font-medium mb-4">
                            Usuarios totales
                        </h3>
                        <div
                            class="text-surface-900 dark:text-surface-0 font-medium text-xl"
                        >
                            <h4>{{ props.users }}</h4>
                        </div>
                    </div>
                    <div
                        class="flex items-center justify-center bg-cyan-100 dark:bg-cyan-400/10 rounded"
                        style="width: 3.5rem; height: 3.5rem"
                    >
                        <i class="pi pi-users text-cyan-500 !text-2xl"></i>
                    </div>
                </div>
            </div>
            <div class="card mb-0 w-1/4 border-none">
                <div class="flex justify-between">
                    <div
                        class="text-surface-900 dark:text-surface-0 font-medium text-xl"
                    >
                        <h3 class="block text-lg font-medium mb-4">
                            Hora actual
                        </h3>

                        <h4>{{ currentTime }}</h4>
                    </div>
                    <div
                        class="flex items-center justify-center bg-indigo-100 dark:bg-indigo-400/10 rounded mt-2"
                        style="width: 3.5rem; height: 3.5rem"
                    >
                        <i class="pi pi-clock text-indigo-500 !text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="card border-none mt-10">
            <h2 class="text-3xl font-semibold mb-4">Portales</h2>
            <DataView :value="props.portals" :layout="layout">
                <template #header>
                    <div class="flex justify-end">
                        <SelectButton
                            v-model="layout"
                            :options="options"
                            :allowEmpty="false"
                        >
                            <template #option="{ option }">
                                <i
                                    :class="[
                                        option === 'list'
                                            ? 'pi pi-bars'
                                            : 'pi pi-table',
                                    ]"
                                />
                            </template>
                        </SelectButton>
                    </div>
                </template>

                <template #list="slotProps">
                    <div class="flex flex-col">
                        <div
                            v-for="(item, index) in slotProps.items"
                            :key="index"
                        >
                            <div
                                class="flex flex-col sm:flex-row sm:items-center p-6 gap-4"
                                :class="{
                                    'border-t border-surface-200 dark:border-surface-700':
                                        index !== 0,
                                }"
                            >
                                <div class="md:w-40 relative">
                                    <template v-if="isLoading">
                                        <Skeleton
                                            width="100%"
                                            height="80px"
                                            class="rounded"
                                        ></Skeleton>
                                    </template>
                                    <img
                                        :class="{
                                            hidden: isLoading,
                                            block: !isLoading,
                                        }"
                                        class="xl:block mx-auto rounded w-full object-cover"
                                        :src="screenshot(item.url)"
                                        :alt="item.name"
                                        @load="isLoading = false"
                                    />
                                    <div
                                        class="absolute rounded-border"
                                        style="left: 4px; top: 4px"
                                    >
                                        <Tag
                                            :value="getActiveStatus(item)"
                                            :severity="getStatus(item)"
                                        >
                                        </Tag>
                                    </div>
                                </div>
                                <div
                                    class="flex flex-col md:flex-row justify-between md:items-center flex-1 gap-6"
                                >
                                    <div
                                        class="flex flex-row md:flex-col justify-between items-start gap-2"
                                    >
                                        <div>
                                            <span
                                                class="font-medium text-surface-500 dark:text-surface-400 text-sm"
                                                >{{ item.name }}</span
                                            >
                                            <div
                                                class="text-lg font-medium mt-2"
                                            >
                                                {{ item.description }}
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        class="flex flex-col md:items-end gap-8"
                                    >
                                        <div
                                            class="flex flex-row-reverse md:flex-row gap-2"
                                        >
                                            <Button
                                                icon="pi pi-external-link
"
                                                @click="abrirUrl(item.url)"
                                                label="Ver portal"
                                                class="flex-auto md:flex-initial whitespace-nowrap"
                                            ></Button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <template #grid="slotProps">
                    <div class="grid grid-cols-12 gap-4">
                        <div
                            v-for="(item, index) in slotProps.items"
                            :key="index"
                            class="col-span-12 sm:col-span-6 md:col-span-4 xl:col-span-6 p-2"
                        >
                            <div
                                class="p-6 border bg-surface-0 dark:bg-surface-900 rounded flex flex-col"
                            >
                                <div
                                    class="bg-surface-50 flex justify-center rounded p-4"
                                >
                                    <div class="relative mx-auto">
                                        <img
                                            class="rounded w-full"
                                            :src="screenshot(item.url)"
                                            :alt="item.name"
                                            style="max-width: 600px"
                                        />
                                        <div
                                            class="absolute rounded-border"
                                            style="left: 4px; top: 4px"
                                        >
                                            <Tag
                                                :value="getActiveStatus(item)"
                                                :severity="getStatus(item)"
                                            >
                                                {{ getActiveStatus(item) }}
                                            </Tag>
                                        </div>
                                    </div>
                                </div>
                                <div class="pt-6">
                                    <div
                                        class="flex flex-row justify-between items-start gap-2"
                                    >
                                        <div>
                                            <span
                                                class="font-medium text-surface-500 dark:text-surface-400 text-sm"
                                                >{{ item.name }}</span
                                            >
                                            <div
                                                class="text-lg font-medium mt-1"
                                            >
                                                {{ item.description }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-6 mt-6">
                                        <div class="flex gap-2">
                                            <Button
                                                icon="pi pi-external-link"
                                                label="Ver portal"
                                                class="flex-auto whitespace-nowrap py-4"
                                                @click="abrirUrl(item.url)"
                                            ></Button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </DataView>
        </div>
    </AppLayout>
</template>
