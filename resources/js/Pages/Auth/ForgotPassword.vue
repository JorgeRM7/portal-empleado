<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";

import Card from "primevue/card";
import InputText from "primevue/inputtext";
import Password from "primevue/password";
import Button from "primevue/button";
import Message from "primevue/message";


const currentStep = ref("employee");

const employeeId = ref(null);

const maskedEmail = ref("");

const remainingSeconds = ref(0);

let timer = null;


/*
|--------------------------------------------------------------------------
| Tema claro / oscuro
|--------------------------------------------------------------------------
*/

const isDark = ref(false);

let observer = null;

const readTheme = () => {

    const root = document.documentElement;

    const darkByClass =
        root.classList.contains("dark");

    const darkByDataTheme =
        root.getAttribute("data-theme") === "dark";

    const darkByBsTheme =
        root.getAttribute("data-bs-theme") === "dark";

    isDark.value =
        darkByClass ||
        darkByDataTheme ||
        darkByBsTheme;
};


const bgStyle = computed(() => ({
    backgroundImage: `url(${
        isDark.value
            ? "/assets/media/auth/bg-dark.png"
            : "/assets/media/auth/bg.png"
    })`,

    backgroundSize: "cover",

    backgroundPosition: "center",
}));


/*
|--------------------------------------------------------------------------
| PASO 1 - Número de nómina
|--------------------------------------------------------------------------
*/

const employeeForm = useForm({
    employee_code: "",
});


const sendingCode = ref(false);

const sendCode = async () => {

    if (!employeeForm.employee_code) {
        employeeForm.setError(
            "employee_code",
            "Ingrese su número de nómina."
        );

        return;
    }

    sendingCode.value = true;

    employeeForm.clearErrors();

    try {

        const response = await axios.post(
            route("employee-password.send-code"),
            {
                employee_code: employeeForm.employee_code,
            }
        );

        if (!response.data.success) {
            return;
        }

        employeeId.value = response.data.reset_employee_id;

        maskedEmail.value = response.data.reset_email;

        currentStep.value = "code";

        startTimer();

    } catch (error) {

        console.error(error);

        const message = error.response?.data?.message || "No se pudo enviar el código.";

        employeeForm.setError(  "employee_code",message);

    } finally {

        sendingCode.value = false;
    }
};


/*
|--------------------------------------------------------------------------
| PASO 2 - Código
|--------------------------------------------------------------------------
*/

const codeForm = useForm({
    employee_id: null,
    code: "",
});

const verifyingCode = ref(false);
const verifyCode = async () => {

    if (!codeForm.code) {
        codeForm.setError(
            "code",
            "Ingrese el código de verificación."
        );

        return;
    }

    if (codeForm.code.length !== 6) {
        codeForm.setError(
            "code",
            "El código debe contener 6 dígitos."
        );

        return;
    }

    verifyingCode.value = true;

    codeForm.clearErrors();

    try {

        const response = await axios.post(
            route("employee-password.verify-code"),
            {
                employee_id: employeeId.value,
                code: codeForm.code,
            }
        );

        if (!response.data.success) {
            return;
        }

        employeeId.value = response.data.reset_employee_id;

        currentStep.value = "password";
        stopTimer();
        console.log(
            "Código verificado:",
            response.data
        );

    } catch (error) {

        console.error(error);

        const message =
            error.response?.data?.message ||
            error.response?.data?.errors?.code?.[0] ||
            "No se pudo verificar el código.";

        codeForm.setError(
            "code",
            message
        );

    } finally {

        verifyingCode.value = false;
    }
};

/*
|--------------------------------------------------------------------------
| PASO 3 - Contraseña
|--------------------------------------------------------------------------
*/

const passwordForm = useForm({
    employee_id: null,
    password: "",
    password_confirmation: "",
});


const resettingPassword = ref(false);
const resetPassword = async () => {

    passwordForm.clearErrors();

    if (!passwordForm.password) {
        passwordForm.setError(
            "password",
            "Ingrese una nueva contraseña."
        );

        return;
    }

    if (passwordForm.password.length < 8) {
        passwordForm.setError(
            "password",
            "La contraseña debe tener al menos 8 caracteres."
        );

        return;
    }

    if (
        passwordForm.password !==
        passwordForm.password_confirmation
    ) {
        passwordForm.setError(
            "password_confirmation",
            "Las contraseñas no coinciden."
        );

        return;
    }

    resettingPassword.value = true;

    try {

        const response = await axios.post(
            route("employee-password.reset"),
            {
                employee_id: employeeId.value,
                password: passwordForm.password,
                password_confirmation:
                    passwordForm.password_confirmation,
            }
        );

        if (!response.data.success) {
            return;
        }

        console.log(response.data);

        /*
        |--------------------------------------------------------------------------
        | Ir al login
        |--------------------------------------------------------------------------
        */

        window.location.href = route("login");

    } catch (error) {

        console.error(error);

        /*
        |--------------------------------------------------------------------------
        | Errores de validación Laravel
        |--------------------------------------------------------------------------
        */

        if (error.response?.data?.errors) {

            const errors =
                error.response.data.errors;

            if (errors.password) {
                passwordForm.setError(
                    "password",
                    errors.password[0]
                );
            }

            if (errors.password_confirmation) {
                passwordForm.setError(
                    "password_confirmation",
                    errors.password_confirmation[0]
                );
            }

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Errores controlados
        |--------------------------------------------------------------------------
        */

        const message =
            error.response?.data?.message ||
            "No se pudo cambiar la contraseña.";

        passwordForm.setError(
            "password",
            message
        );

    } finally {

        resettingPassword.value = false;
    }
};
/*
|--------------------------------------------------------------------------
| Temporizador
|--------------------------------------------------------------------------
*/

const startTimer = () => {

    stopTimer();

    remainingSeconds.value =
        15 * 60;

    timer = setInterval(() => {

        if (
            remainingSeconds.value > 0
        ) {

            remainingSeconds.value--;

        } else {

            stopTimer();
        }

    }, 1000);
};


const stopTimer = () => {

    if (timer) {

        clearInterval(timer);

        timer = null;
    }
};


const formattedTime = computed(() => {

    const minutes =
        Math.floor(
            remainingSeconds.value / 60
        );

    const seconds =
        remainingSeconds.value % 60;

    return `${String(minutes).padStart(2, "0")}:${String(seconds).padStart(2, "0")}`;
});


/*
|--------------------------------------------------------------------------
| Volver al paso de nómina
|--------------------------------------------------------------------------
*/

const restartRecovery = () => {

    stopTimer();

    currentStep.value =
        "employee";

    employeeId.value =
        null;

    maskedEmail.value =
        "";

    remainingSeconds.value =
        0;

    employeeForm.reset();

    codeForm.reset();

    passwordForm.reset();

    employeeForm.clearErrors();

    codeForm.clearErrors();

    passwordForm.clearErrors();
};


/*
|--------------------------------------------------------------------------
| Mounted
|--------------------------------------------------------------------------
*/

onMounted(() => {

    readTheme();

    observer =
        new MutationObserver(
            readTheme
        );

    observer.observe(
        document.documentElement,
        {
            attributes: true,

            attributeFilter: [
                "class",
                "data-theme",
                "data-bs-theme",
            ],
        }
    );
});


onBeforeUnmount(() => {

    stopTimer();

    observer?.disconnect();
});
</script>


<template>

    <Head title="Recuperar contraseña" />


    <!-- Layout igual al Login -->

    <div class="min-h-screen grid grid-cols-1 lg:grid-cols-10">


        <!-- =========================================================
             LADO IZQUIERDO
             ========================================================= -->

        <div
            class="hidden lg:block lg:col-span-6 relative"
            :style="bgStyle"
        >

            <div class="absolute inset-0 bg-black/10"></div>


            <div class="absolute left-10 bottom-10 z-10 text-white">

                <div class="text-xl opacity-80">
                    Mi Portal RH
                </div>

                <div class="text-5xl font-semibold mt-1">
                    Recuperar acceso
                </div>

                <div class="opacity-80 mt-1">
                    Restablezca su contraseña de forma segura
                </div>

            </div>

        </div>


        <!-- =========================================================
             LADO DERECHO
             ========================================================= -->

        <div class="col-span-4 flex items-center justify-center p-6">

            <Card
                class="shadow-none border-0 w-full h-full flex flex-col justify-center"
            >

                <template #content>


                    <!-- Logo -->

                    <div class="flex justify-center mb-1">

                        <img
                            src="/assets/media/logos/logo.png"
                            alt="Mi Portal RH"
                            class="w-80 h-80 object-contain"
                        />

                    </div>


                    <!-- =================================================
                         TITULO
                         ================================================= -->

                    <div class="mb-6">

                        <div class="text-4xl font-semibold text-center">

                            <template v-if="currentStep === 'employee'">
                                Recuperar contraseña
                            </template>

                            <template v-else-if="currentStep === 'code'">
                                Verificar código
                            </template>

                            <template v-else>
                                Nueva contraseña
                            </template>

                        </div>


                        <div class="text-gray-500 mt-1 text-center">

                            <template v-if="currentStep === 'employee'">
                                Ingrese su número de nómina
                            </template>

                            <template v-else-if="currentStep === 'code'">
                                Confirme el código enviado a su correo
                            </template>

                            <template v-else>
                                Cree una nueva contraseña
                            </template>

                        </div>

                    </div>


                    <!-- =================================================
                         INDICADOR DE PASOS
                         ================================================= -->

                    <div class="flex items-center justify-center gap-2 mb-6">

                        <div
                            class="w-9 h-9 rounded-full flex items-center justify-center font-semibold"
                            :class="
                                currentStep === 'employee'
                                    ? 'bg-primary text-white'
                                    : 'bg-green-500 text-white'
                            "
                        >
                            <i
                                v-if="currentStep !== 'employee'"
                                class="pi pi-check"
                            ></i>

                            <span v-else>
                                1
                            </span>
                        </div>


                        <div
                            class="w-12 h-1 rounded"
                            :class="
                                currentStep !== 'employee'
                                    ? 'bg-green-500'
                                    : 'bg-gray-200'
                            "
                        ></div>


                        <div
                            class="w-9 h-9 rounded-full flex items-center justify-center font-semibold"
                            :class="
                                currentStep === 'code'
                                    ? 'bg-primary text-white'
                                    : currentStep === 'password'
                                        ? 'bg-green-500 text-white'
                                        : 'bg-gray-200 text-gray-500'
                            "
                        >
                            <i
                                v-if="currentStep === 'password'"
                                class="pi pi-check"
                            ></i>

                            <span v-else>
                                2
                            </span>
                        </div>


                        <div
                            class="w-12 h-1 rounded"
                            :class="
                                currentStep === 'password'
                                    ? 'bg-green-500'
                                    : 'bg-gray-200'
                            "
                        ></div>


                        <div
                            class="w-9 h-9 rounded-full flex items-center justify-center font-semibold"
                            :class="
                                currentStep === 'password'
                                    ? 'bg-primary text-white'
                                    : 'bg-gray-200 text-gray-500'
                            "
                        >
                            3
                        </div>

                    </div>


                    <!-- =================================================
                         PASO 1
                         ================================================= -->

                    <form
                        v-if="currentStep === 'employee'"
                        @submit.prevent="sendCode"
                        class="flex flex-col gap-4"
                        autocomplete="off"
                    >

                        <Message
                            severity="info"
                            :closable="false"
                        >

                            Ingrese su número de nómina.

                            <br>

                            Enviaremos un código de verificación al correo registrado en Mi Portal RH.

                        </Message>


                        <div>

                            <label class="block mb-2 font-medium">
                                Número de nómina
                            </label>


                            <InputText
                                v-model="employeeForm.employee_code"
                                autocomplete="off"
                                placeholder="Ej. 15358"
                                class="w-full"
                                autofocus
                                :invalid="!!employeeForm.errors.employee_code"
                            />


                            <small
                                v-if="employeeForm.errors.employee_code"
                                class="p-error"
                            >
                                {{ employeeForm.errors.employee_code }}
                            </small>

                        </div>


                        <Button
                            type="submit"
                            label="Enviar código"
                            icon="pi pi-envelope"
                            class="w-full"
                            :loading="employeeForm.processing"
                            :disabled="
                                employeeForm.processing ||
                                !employeeForm.employee_code
                            "
                        />


                        <Link
                            :href="route('login')"
                            class="text-center text-sm text-primary hover:underline mt-2"
                        >
                            <i class="pi pi-arrow-left mr-1"></i>

                            Regresar al inicio de sesión
                        </Link>

                    </form>


                    <!-- =================================================
                         PASO 2
                         ================================================= -->

                    <form
                        v-else-if="currentStep === 'code'"
                        @submit.prevent="verifyCode"
                        class="flex flex-col gap-4"
                        autocomplete="off"
                    >

                        <Message
                            severity="success"
                            :closable="false"
                        >

                            Enviamos un código de verificación a:

                            <strong>
                                {{ maskedEmail }}
                            </strong>

                        </Message>


                        <!-- Temporizador -->

                        <div class="text-center py-2">

                            <div class="text-sm text-gray-500">
                                Tiempo restante
                            </div>


                            <div
                                class="text-4xl font-bold mt-1"
                                :class="
                                    remainingSeconds <= 60
                                        ? 'text-red-500'
                                        : 'text-primary'
                                "
                            >
                                {{ formattedTime }}
                            </div>


                            <small class="text-gray-500">
                                El código es válido durante 15 minutos.
                            </small>

                        </div>


                        <div>

                            <label class="block mb-2 font-medium">
                                Código de verificación
                            </label>


                            <InputText
                                v-model="codeForm.code"
                                maxlength="6"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                placeholder="000000"
                                class="w-full text-center text-2xl tracking-[0.5em]"
                                :invalid="!!codeForm.errors.code"
                            />


                            <small
                                v-if="codeForm.errors.code"
                                class="p-error"
                            >
                                {{ codeForm.errors.code }}
                            </small>

                        </div>


                        <Message
                            v-if="remainingSeconds <= 0"
                            severity="error"
                            :closable="false"
                        >
                            El código ha expirado. Inicie nuevamente el proceso.
                        </Message>


                        <Button
                            type="submit"
                            label="Verificar código"
                            icon="pi pi-check-circle"
                            class="w-full"
                            :loading="codeForm.processing"
                            :disabled="
                                codeForm.processing ||
                                remainingSeconds <= 0 ||
                                codeForm.code.length !== 6
                            "
                        />


                        <Button
                            type="button"
                            label="Solicitar otro código"
                            icon="pi pi-refresh"
                            severity="secondary"
                            text
                            class="w-full"
                            @click="restartRecovery"
                        />

                    </form>


                    <!-- =================================================
                         PASO 3
                         ================================================= -->

                    <form
                        v-else
                        @submit.prevent="resetPassword"
                        class="flex flex-col gap-4"
                        autocomplete="off"
                    >

                        <Message
                            severity="success"
                            :closable="false"
                        >
                            Código verificado correctamente.

                            Ahora puede establecer una nueva contraseña.
                        </Message>


                        <div>

                            <label class="block mb-2 font-medium">
                                Nueva contraseña
                            </label>


                            <Password
                                v-model="passwordForm.password"
                                toggleMask
                                :feedback="true"
                                autocomplete="new-password"
                                placeholder="••••••••"
                                class="w-full"
                                inputClass="w-full"
                                :invalid="!!passwordForm.errors.password"
                            />


                            <small
                                v-if="passwordForm.errors.password"
                                class="p-error"
                            >
                                {{ passwordForm.errors.password }}
                            </small>

                        </div>


                        <div>

                            <label class="block mb-2 font-medium">
                                Confirmar contraseña
                            </label>


                            <Password
                                v-model="passwordForm.password_confirmation"
                                toggleMask
                                :feedback="false"
                                autocomplete="new-password"
                                placeholder="••••••••"
                                class="w-full"
                                inputClass="w-full"
                            />

                        </div>
                        <small v-if="passwordForm.errors.password_confirmation" class="p-error">
                            {{ passwordForm.errors.password_confirmation }}
                        </small>


                        <Button
                            type="submit"
                            label="Cambiar contraseña"
                            icon="pi pi-lock"
                            severity="success"
                            class="w-full"
                            :loading="resettingPassword"
                            :disabled="resettingPassword || !passwordForm.password || !passwordForm.password_confirmation"
                        />

                    </form>

                </template>

            </Card>

        </div>

    </div>

</template>