<script setup>
import { computed, onMounted, ref, watch } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { useForm } from "@inertiajs/vue3";
import { useToastService } from "@/Stores/toastService";
import { useLayout } from "@/Layouts/composables/layout";

const { showError, showSuccess } = useToastService();

const { isDark } = useLayout();

const selectedEmployee = ref(null);
const selectedDate = ref(null);
const attendanceData = ref(null);
const noData = ref(false);
const loading = ref(false);

const selectedBranchOffice = ref(
    JSON.parse(localStorage.getItem("selectedBranchOffice")),
);

console.log(selectedBranchOffice.value.id);

const form = useForm({
    moment: null,
    hours: 0,
    observations: "",
    branchOfficeId: selectedBranchOffice.value.id,
    employeeId: null,
    date: null,
    schedule_entry: null,
    schedule_exit: null,
    scheduleId: null,
});

const momentOptions = ref([
    { label: "Antes de Entrada", value: "before", icon: "pi pi-arrow-left" },
    { label: "Después de Salida", value: "after", icon: "pi pi-arrow-right" },
    { label: "Ambos Turnos", value: "both", icon: "pi pi-arrows-h" },
]);
const employees = ref([]);

const formatDate = (date) => {
    const month = date.getMonth() + 1;
    const day = date.getDate();
    const year = date.getFullYear();
    return `${year}-${month < 10 ? "0" + month : month}-${day < 10 ? "0" + day : day}`;
};

const fetchAttendanceInfo = async () => {
    console.log(selectedEmployee.value, selectedDate.value);
    loading.value = true;

    if (selectedEmployee.value && selectedDate.value) {
        const response = await axios.get(
            `/api/txts/search-employee-data?date=${formatDate(selectedDate.value)}&employee_id=${selectedEmployee.value.id}`,
        );
        console.log(response.data);
        attendanceData.value = response.data[0];
        noData.value = response.data.length === 0;
        calculateOvertime();
    }
    loading.value = false;
};

const getEmployees = async () => {
    loading.value = true;
    const response = await axios.get(
        `/api/employee-branchOffices?branchOfficeId=${selectedBranchOffice.value.id}`,
    );
    console.log(response.data);
    employees.value = response.data;

    loading.value = false;
};

const employeeOptions = computed(() =>
    employees.value.map((e) => ({
        ...e,
        label: `(${e.id}) ${e.full_name}`,
    })),
);

const parseTime = (timeStr) => {
    if (!timeStr) return null;
    const [hours, minutes] = timeStr.split(":").map(Number);
    const date = new Date();
    date.setHours(hours, minutes, 0, 0);
    return date;
};

const calculateOvertime = () => {
    if (!attendanceData.value) return;
    const { entradaTeorica, salidaTeorica, entradaReal, salidaReal } =
        attendanceData.value;

    const shiftStart = parseTime(entradaTeorica);
    const shiftEnd = parseTime(salidaTeorica);
    const actualIn = parseTime(entradaReal);
    const actualOut = parseTime(salidaReal);

    // Validación básica: Si falta algún dato, no calculamos nada
    if (!shiftStart || !shiftEnd || !actualIn || !actualOut) return;

    if (shiftEnd < shiftStart) {
        shiftEnd.setDate(shiftEnd.getDate() + 1);
    }
    if (actualOut < actualIn) {
        actualOut.setDate(actualOut.getDate() + 1);
    }

    let minutesBefore = (shiftStart - actualIn) / (1000 * 60);
    let minutesAfter = (actualOut - shiftEnd) / (1000 * 60);

    const TOLERANCE = 10;

    let validMinutesBefore = minutesBefore > TOLERANCE ? minutesBefore : 0;
    let validMinutesAfter = minutesAfter > TOLERANCE ? minutesAfter : 0;
    let calculatedMoment = null;
    let totalMinutes = 0;

    if (validMinutesBefore > 0 && validMinutesAfter > 0) {
        calculatedMoment = "both";
        totalMinutes = validMinutesBefore + validMinutesAfter;
    } else if (validMinutesBefore > 0) {
        calculatedMoment = "before";
        totalMinutes = validMinutesBefore;
    } else if (validMinutesAfter > 0) {
        calculatedMoment = "after";
        totalMinutes = validMinutesAfter;
    }

    const totalHours = Math.floor(totalMinutes / 60);

    form.moment = calculatedMoment;
    form.hours = totalHours > 0 ? totalHours : 0;

    console.log(calculatedMoment, totalHours);
};

watch([selectedEmployee, selectedDate], () => {
    fetchAttendanceInfo();
});

onMounted(() => {
    getEmployees();
});

const submitForm = () => {
    form.employeeId = selectedEmployee.value.id;
    form.date = formatDate(selectedDate.value);
    form.schedule_entry = attendanceData.value?.entradaTeorica;
    form.schedule_exit = attendanceData.value?.salidaTeorica;
    form.scheduleId = attendanceData.value?.schedule_id;
    loading.value = true;
    form.post(route("txt.store"), {
        onSuccess: () => {
            form.reset();
            showSuccess();
        },
        onError: (errors) => {
            console.log(errors);
            showError();
        },
        onFinish: () => {
            loading.value = false;
        },
    });
};
</script>

<template>
    <AppLayout title="Crear Tiempo Por Tiempo">
        <div class="w-full">
            <div class="card rounded-xl overflow-hidden">
                <div class="pb-6 border-b">
                    <h2
                        class="text-lg font-bold mb-4 flex align-items-center gap-2"
                    >
                        Crear Tiempo Por Tiempo
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-gray-600"
                                >Empleado</label
                            >
                            <span class="p-input-icon-left w-full">
                                <Select
                                    v-model="selectedEmployee"
                                    :options="employeeOptions"
                                    optionLabel="label"
                                    placeholder="Buscar colaborador..."
                                    class="w-full"
                                    filter
                                    :filterFields="['id', 'full_name', 'label']"
                                    :loading="loading"
                                    :disabled="loading"
                                />
                            </span>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-gray-600"
                                >Fecha de Incidencia</label
                            >
                            <DatePicker
                                v-model="selectedDate"
                                dateFormat="dd/mm/yy"
                                showIcon
                                placeholder="Selecciona la fecha"
                                class="w-full"
                                :disabled="loading"
                                :loading="loading"
                            />
                        </div>
                    </div>
                </div>

                <div class="pt-6">
                    <Message
                        severity="warn"
                        icon="pi pi-exclamation-circle"
                        class="mb-4 w-full"
                        v-if="!loading && noData"
                    >
                        <div>
                            <p>
                                No se encontraron datos para el empleado
                                {{ selectedEmployee?.full_name }} y la fecha
                                {{ formatDate(selectedDate) }}.
                            </p>
                        </div>
                    </Message>
                    <div
                        v-if="!attendanceData && !noData"
                        class="flex flex-col items-center justify-center py-10 text-gray-400"
                    >
                        <i
                            class="pi pi-calendar-times text-4xl mb-3 opacity-50"
                        ></i>
                        <p>Selecciona un empleado y una fecha para comenzar.</p>
                    </div>

                    <div v-else-if="!noData" class="animate-fade-in">
                        <div
                            class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 p-4"
                        >
                            <div
                                class="rounded-lg p-7 border border-blue-100 relative overflow-hidden"
                                :class="isDark ? 'bg-blue-700' : 'bg-blue-50'"
                            >
                                <div
                                    class="absolute right-0 top-0 p-3 opacity-10"
                                >
                                    <i
                                        class="pi pi-calendar text-6xl"
                                        :class="
                                            isDark
                                                ? 'text-blue-100'
                                                : 'text-blue-800'
                                        "
                                    ></i>
                                </div>
                                <h3
                                    class="font-semibold mb-3 flex items-center gap-2 mt-0"
                                    :class="
                                        isDark
                                            ? 'text-blue-100'
                                            : 'text-blue-800'
                                    "
                                >
                                    <i class="pi pi-info-circle"></i> Turno
                                    Asignado
                                </h3>
                                <div class="space-y-2 mt-7">
                                    <div
                                        class="flex justify-between items-center"
                                    >
                                        <span
                                            class="text-sm"
                                            :class="
                                                isDark
                                                    ? 'text-blue-100'
                                                    : 'text-blue-600'
                                            "
                                            >Nombre:</span
                                        >
                                        <Tag
                                            :value="attendanceData?.turno"
                                            severity="info"
                                            rounded
                                        ></Tag>
                                    </div>
                                    <Divider class="my-2" />
                                    <div class="flex justify-between">
                                        <div class="text-center">
                                            <span
                                                class="block text-xs uppercase"
                                                :class="
                                                    isDark
                                                        ? 'text-blue-200'
                                                        : 'text-blue-600'
                                                "
                                                >Entrada</span
                                            >
                                            <span class="text-lg font-bold">{{
                                                attendanceData?.entradaTeorica
                                            }}</span>
                                        </div>
                                        <div class="text-center">
                                            <span
                                                class="block text-xs uppercase"
                                                :class="
                                                    isDark
                                                        ? 'text-blue-200'
                                                        : 'text-blue-600'
                                                "
                                                >Salida</span
                                            >
                                            <span class="text-lg font-bold">{{
                                                attendanceData?.salidaTeorica
                                            }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="bg-green-50 rounded-lg p-7 border border-green-100 relative overflow-hidden"
                                :class="isDark ? 'bg-green-700' : 'bg-green-50'"
                            >
                                <div
                                    class="absolute right-0 top-0 p-3 opacity-10"
                                >
                                    <i
                                        class="pi pi-check-circle text-6xl text-green-800"
                                        :class="
                                            isDark
                                                ? 'text-green-100'
                                                : 'text-green-800'
                                        "
                                    ></i>
                                </div>
                                <h3
                                    class="font-semibold mb-3 flex items-center gap-2 mt-0"
                                    :class="
                                        isDark
                                            ? 'text-green-100'
                                            : 'text-green-800'
                                    "
                                >
                                    <i class="pi pi-clock"></i> Asistencia
                                    Registrada
                                </h3>
                                <div
                                    class="flex items-center justify-between pb-2 mt-7"
                                >
                                    <div
                                        class="flex flex-col items-center flex-1 border-r border-green-200"
                                    >
                                        <span
                                            class="text-xs font-medium uppercase mb-1"
                                            :class="
                                                isDark
                                                    ? 'text-green-200'
                                                    : 'text-green-600'
                                            "
                                            >Checó Entrada</span
                                        >
                                        <span class="text-2xl font-black">{{
                                            attendanceData?.entradaReal
                                        }}</span>
                                        <i
                                            class="pi pi-sign-in mt-1"
                                            :class="
                                                isDark
                                                    ? 'text-green-200'
                                                    : 'text-green-500'
                                            "
                                        ></i>
                                    </div>
                                    <div
                                        class="flex flex-col items-center flex-1"
                                    >
                                        <span
                                            class="text-xs font-medium uppercase mb-1"
                                            :class="
                                                isDark
                                                    ? 'text-green-200'
                                                    : 'text-green-600'
                                            "
                                            >Checó Salida</span
                                        >
                                        <span class="text-2xl font-black">{{
                                            attendanceData?.salidaReal
                                        }}</span>
                                        <i
                                            class="pi pi-sign-out mt-1"
                                            :class="
                                                isDark
                                                    ? 'text-green-200'
                                                    : 'text-green-500'
                                            "
                                        ></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <Divider align="center" type="dashed">
                            <span class="px-3 text-sm font-medium"
                                >Registro de Tiempo Por Tiempo</span
                            >
                        </Divider>

                        <div class="mt-6 rounded-xl p-6 border border-gray-200">
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                                <div class="lg:col-span-4 flex flex-col gap-3">
                                    <label
                                        class="text-sm font-bold text-gray-700"
                                        >Momento del TxT</label
                                    >
                                    <SelectButton
                                        v-model="form.moment"
                                        :options="momentOptions"
                                        optionLabel="label"
                                        optionValue="value"
                                        class="w-full custom-select-button"
                                    >
                                        <template #option="slotProps">
                                            <div
                                                class="flex flex-col items-center py-2 w-full"
                                            >
                                                <i
                                                    :class="
                                                        slotProps.option.icon
                                                    "
                                                    class="mb-1 text-lg"
                                                ></i>
                                                <span class="text-xs">{{
                                                    slotProps.option.label
                                                }}</span>
                                                <small
                                                    class="text-red-500 text-xs"
                                                    >{{
                                                        form.errors.moment
                                                    }}</small
                                                >
                                            </div>
                                        </template>
                                    </SelectButton>
                                </div>

                                <div class="lg:col-span-3 flex flex-col gap-3">
                                    <label
                                        class="text-sm font-bold text-gray-700"
                                        >Total Horas</label
                                    >
                                    <InputNumber
                                        v-model="form.hours"
                                        showButtons
                                        buttonLayout="horizontal"
                                        :step="0.5"
                                        :min="0"
                                        :max="24"
                                        suffix=" Hrs"
                                        inputClass="text-center font-bold"
                                        class="w-full"
                                    >
                                        <template #incrementbuttonicon>
                                            <span class="pi pi-plus" />
                                        </template>
                                        <template #decrementbuttonicon>
                                            <span class="pi pi-minus" />
                                        </template>
                                    </InputNumber>
                                    <small class="text-gray-500 text-xs"
                                        >Incrementos de 30 min (0.5)</small
                                    >
                                    <small class="text-red-500 text-xs">{{
                                        form.errors.hours
                                    }}</small>
                                </div>

                                <div class="lg:col-span-5 flex flex-col gap-3">
                                    <label
                                        class="text-sm font-bold text-gray-700"
                                        >Observaciones / Justificación</label
                                    >
                                    <Textarea
                                        v-model="form.observations"
                                        rows="3"
                                        class="w-full resize-none"
                                        placeholder="Describa brevemente la razón del tiempo por tiempo..."
                                    />
                                </div>
                            </div>

                            <div
                                class="flex justify-end mt-6 pt-4 border-t border-gray-200"
                            >
                                <Button
                                    label="Guardar"
                                    icon="pi pi-save"
                                    class="p-button-primary px-6"
                                    @click="submitForm"
                                    :disabled="
                                        !form.moment ||
                                        form.hours <= 0 ||
                                        loading
                                    "
                                    :loading="loading"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Animación suave para cuando aparecen los datos */
.animate-fade-in {
    animation: fadeIn 0.5s ease-out forwards;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Ajustes finos para el SelectButton para que se vea más moderno */
:deep(.p-selectbutton .p-button) {
    flex: 1;
    transition: all 0.2s;
}
:deep(.p-selectbutton .p-button.p-highlight) {
    background-color: var(--primary-color); /* O tu color corporativo */
    border-color: var(--primary-color);
}
</style>
