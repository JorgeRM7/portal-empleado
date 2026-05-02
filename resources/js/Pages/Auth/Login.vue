<script setup>
import { useForm, Link as InertiaLink, Head } from "@inertiajs/vue3";

// PrimeVue
import Card from "primevue/card";
import InputText from "primevue/inputtext";
import Password from "primevue/password";
import Checkbox from "primevue/checkbox";
import Button from "primevue/button";
import Message from "primevue/message";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import Dialog from "primevue/dialog";
defineProps({
    status: String,
});

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submitForm = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};

const isDark = ref(false);

const readTheme = () => {
    const root = document.documentElement;

    // Caso Tailwind: <html class="dark">
    const darkByClass = root.classList.contains("dark");

    // Caso atributo: <html data-theme="dark"> o data-bs-theme="dark"
    const darkByDataTheme = root.getAttribute("data-theme") === "dark";
    const darkByBsTheme = root.getAttribute("data-bs-theme") === "dark";

    isDark.value = darkByClass || darkByDataTheme || darkByBsTheme;
};

let observer;


// onBeforeUnmount(() => observer?.disconnect());

const bgStyle = computed(() => ({
    backgroundImage: `url(${
        isDark.value
            ? "/assets/media/auth/bg-dark.png"
            : "/assets/media/auth/bg.png"
    })`,
    backgroundSize: "cover",
    backgroundPosition: "center",
}));

const deferredPrompt = ref(null);
const showInstallButton = ref(false);
const showIosInstall = ref(false);
const isIos = ref(false);
const isStandalone = ref(false);

const detectDevice = () => {
    const userAgent = window.navigator.userAgent.toLowerCase();

    isIos.value = /iphone|ipad|ipod/.test(userAgent);

    isStandalone.value =
        window.matchMedia("(display-mode: standalone)").matches ||
        window.navigator.standalone === true;
};

const installApp = async () => {
    // iPhone / iPad
    if (isIos.value) {
        showIosInstall.value = true;
        return;
    }

    // Android / Chrome
    if (!deferredPrompt.value) {
        return;
    }

    deferredPrompt.value.prompt();

    const choiceResult = await deferredPrompt.value.userChoice;

    if (choiceResult.outcome === "accepted") {
        console.log("Usuario instaló la app");
    } else {
        console.log("Usuario canceló instalación");
    }

    deferredPrompt.value = null;
    showInstallButton.value = false;
};

const handleBeforeInstallPrompt = (event) => {
    event.preventDefault();

    deferredPrompt.value = event;
    showInstallButton.value = true;
};

const handleAppInstalled = () => {
    deferredPrompt.value = null;
    showInstallButton.value = false;
};


// onMounted(() => {
//     readTheme();

//     observer = new MutationObserver(readTheme);
//     observer.observe(document.documentElement, {
//         attributes: true,
//         attributeFilter: ["class", "data-theme", "data-bs-theme"],
//     });
// });

onBeforeUnmount(() => {
    observer?.disconnect();

    window.removeEventListener("beforeinstallprompt", handleBeforeInstallPrompt);
    window.removeEventListener("appinstalled", handleAppInstalled);
});

onMounted(() => {
    readTheme();
    detectDevice();

    observer = new MutationObserver(readTheme);
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ["class", "data-theme", "data-bs-theme"],
    });

    window.addEventListener("beforeinstallprompt", handleBeforeInstallPrompt);
    window.addEventListener("appinstalled", handleAppInstalled);

    // En iOS no existe beforeinstallprompt,
    // entonces mostramos botón manual si no está instalada.
    if (isIos.value && !isStandalone.value) {
        showInstallButton.value = true;
    }
});



</script>

<template>
    <Head title="Login" />

    <!-- Layout split: 3/4 imagen, 1/4 login -->
    <div class="min-h-screen grid grid-cols-1 lg:grid-cols-10">
        <!-- Imagen / lado izquierdo -->
        <div class="hidden lg:block lg:col-span-6 relative" :style="bgStyle">
            <!-- Overlay minimal -->
            <div class="absolute inset-0 bg-black/10"></div>

            <div class="absolute left-10 bottom-10 z-10 text-white">
                <div class="text-xl opacity-80">Mi Portal RH</div>
                <div class="text-5xl font-semibold mt-1">Bienvenido</div>
                <div class="opacity-80 mt-1">Inicia sesión para continuar</div>
            </div>
        </div>

        <!-- Lado derecho: login -->
        <div class="col-span-4 flex items-center justify-center p-6">
            <Card
                class="shadow-none border-0 w-full h-full flex flex-col justify-center"
            >
                <template #content>

                    <div class="flex justify-center mb-1">
                        <img
                            src="/assets/media/logos/logo.png"
                            alt="Mi Portal RH"
                            class="w-80 h-80 object-contain"
                        />
                    </div>
                    <!-- Header minimal -->
                    <div class="mb-6">
                        <div class="text-4xl font-semibold text-center">
                            Iniciar sesión
                        </div>
                        <div class="text-gray-500 mt-1 text-center">
                            Ingresa tus credenciales
                        </div>
                    </div>

                    <Message
                        v-if="status"
                        severity="info"
                        :closable="false"
                        class="mb-4"
                    >
                        {{ status }}
                    </Message>

                    <form
                        @submit.prevent="submitForm"
                        class="flex flex-col gap-4"
                    >
                        <!-- Email -->
                        <div>
                            <label class="block mb-2 font-medium">
                                Usuario o Email
                            </label>
                            <InputText
                                v-model="form.email"
                                autocomplete="username"
                                placeholder="Usuario o Email"
                                class="w-full"
                                :invalid="!!form.errors.email"
                                name="email"
                            />
                            <small v-if="form.errors.email" class="p-error">
                                {{ form.errors.email }}
                            </small>
                        </div>

                        <!-- Password -->
                        <div>
                            <label class="block mb-2 font-medium">
                                Contraseña
                            </label>
                            <Password
                                v-model="form.password"
                                :feedback="false"
                                toggleMask
                                autocomplete="current-password"
                                placeholder="••••••••"
                                class="w-full"
                                inputClass="w-full"
                                :invalid="!!form.errors.password"
                                name="password"
                            />
                            <small v-if="form.errors.password" class="p-error">
                                {{ form.errors.password }}
                            </small>
                        </div>

                        <!-- Submit -->
                        <Button
                            type="submit"
                            label="Entrar"
                            icon="pi pi-sign-in"
                            class="w-full"
                            :loading="form.processing"
                            :disabled="form.processing"
                        />
                        <Button
                            v-if="showInstallButton && !isStandalone"
                            type="button"
                            label="Crear acceso directo"
                            icon="pi pi-mobile"
                            class="w-full p-button-outlined"
                            @click="installApp"
                        />
                    </form>
                </template>
            </Card>
        </div>
        
    </div>
    <Dialog
            v-model:visible="showIosInstall"
            modal
            header="Agregar a pantalla de inicio"
            :style="{ width: '90%', maxWidth: '420px' }"
        >
            <div class="space-y-4">
                <p class="text-gray-600">
                    Para crear el acceso directo en iPhone:
                </p>

                <ol class="list-decimal pl-5 space-y-2 text-gray-700">
                    <li>Abre este portal desde Safari.</li>
                    <li>Toca el botón de compartir.</li>
                    <li>Selecciona <strong>Agregar a pantalla de inicio</strong>.</li>
                    <li>Presiona <strong>Agregar</strong>.</li>
                </ol>

                <Message severity="info" :closable="false">
                    En iPhone no se puede instalar automáticamente, Apple requiere que el usuario lo agregue manualmente.
                </Message>
            </div>
        </Dialog>
</template>
