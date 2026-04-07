<script setup>
import { useLayout } from "@/Layouts/composables/layout";
import { useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import axios from "axios";
import { useToastService } from "@/Stores/toastService";

const props = defineProps({
    motivos: Array,
    employees: Array,
    branchOffices: Array,
    payWeeks: Array,
});

const { isDark } = useLayout();
const { showSuccess, showError } = useToastService();

const selectedEmployee = ref();
const selectedDate = ref();
const loading = ref(false);
const attendanceData = ref(null);
const noData = ref(false);
const momentCopy = ref();
const hoursCopy = ref();
const sending = ref(false);

const getSafeBranchId = () => {
    try {
        const item = localStorage.getItem("selectedBranchOffice");
        if (!item) return null;
        const parsed = JSON.parse(item);
        return parsed || null;
    } catch (e) {
        console.warn("Error leyendo localStorage:", e);
        return null;
    }
};

const selectedBranchOffice = ref(getSafeBranchId());

const employeesByBranchOffice = ref(
    props.employees.filter(
        (employee) =>
            employee.branch_office_id === selectedBranchOffice.value.id,
    ),
);

const employeeOptions = computed(() =>
    employeesByBranchOffice.value.map((e) => ({
        ...e,
        label: `(${e.id}) ${e.full_name}`,
    })),
);

const momentOptions = ref([
    { label: "Antes de Entrada", value: "before", icon: "pi pi-arrow-left" },
    { label: "Después de Salida", value: "after", icon: "pi pi-arrow-right" },
    { label: "Ambos Turnos", value: "both", icon: "pi pi-arrows-h" },
]);

const form = useForm({
    employee_id: null,
    date: null,

    extemporaneous: false,
    pay_week_id: null,

    time_for_time: false,

    full_shift: false,
    double_shift: false,
    shift_id: null,

    external_pay: false,
    plant_id: null,

    motivo_id: null,

    moment: null,
    hours: 0,
    double_overtime: 0,
    triple_overtime: 0,

    observations: "",

    shift_entry_time: null,
    shift_leave_time: null,

    has_error: 0,
});

const canPickPayWeek = computed(() => form.extemporaneous);

const externalPayDisabled = computed(() => form.time_for_time);
const plantDisabled = computed(() => form.time_for_time || !form.external_pay);

const shiftSelectEnabled = computed(() => form.full_shift);
const normalHoursDisabled = computed(() => form.full_shift);

watch(
    () => form.extemporaneous,
    (v) => {
        if (!v) form.pay_week_id = null;
    },
);

watch(
    () => form.time_for_time,
    (v) => {
        if (v) {
            form.external_pay = false;
            form.plant_id = null;
        }
    },
);

watch(
    () => form.external_pay,
    (v) => {
        if (!v) form.plant_id = null;
    },
);

watch(
    () => form.full_shift,
    (v) => {
        if (!v) {
            form.shift_id = null;
        } else {
            form.hours = 0;
            form.moment = null;
        }
    },
);

const formatDate = (date) => {
    const month = date.getMonth() + 1;
    const day = date.getDate();
    const year = date.getFullYear();
    return `${year}-${month < 10 ? "0" + month : month}-${day < 10 ? "0" + day : day}`;
};

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

    if (!shiftStart || !shiftEnd || !actualIn || !actualOut) return;

    if (shiftEnd < shiftStart) shiftEnd.setDate(shiftEnd.getDate() + 1);
    if (actualOut < actualIn) actualOut.setDate(actualOut.getDate() + 1);

    let minutesBefore = (shiftStart - actualIn) / (1000 * 60);
    let minutesAfter = (actualOut - shiftEnd) / (1000 * 60);

    const TOLERANCE = 10;
    const validMinutesBefore = minutesBefore > TOLERANCE ? minutesBefore : 0;
    const validMinutesAfter = minutesAfter > TOLERANCE ? minutesAfter : 0;

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

    const totalHours = Math.round((totalMinutes / 60) * 2) / 2;

    form.moment = calculatedMoment;
    momentCopy.value = calculatedMoment;
    form.hours = totalHours > 0 ? totalHours : 0;
    hoursCopy.value = totalHours > 0 ? totalHours : 0;
    form.double_overtime = totalHours > 0 ? totalHours : 0;
};

const fetchAttendanceInfo = async () => {
    loading.value = true;
    momentCopy.value = null;
    hoursCopy.value = null;
    form.double_overtime = 0;
    form.triple_overtime = 0;

    if (selectedEmployee.value && selectedDate.value) {
        const dateStr = formatDate(selectedDate.value);
        form.employee_id = selectedEmployee.value.id;
        form.date = dateStr;

        const response = await axios.get(
            `/api/employee-overtime?date=${dateStr}&employee_id=${selectedEmployee.value.id}`,
        );

        console.log(response);

        attendanceData.value = response.data[0];
        noData.value = response.data.length === 0;

        if (!noData.value) {
            form.shift_entry_time = attendanceData.value.entradaTeorica;
            form.shift_leave_time = attendanceData.value.salidaTeorica;
            form.shift_id = attendanceData.value.schedule_id;
            calculateOvertime();
        } else {
            form.shift_entry_time = null;
            form.shift_leave_time = null;
            form.shift_id = null;
        }
    } else {
        attendanceData.value = null;
        noData.value = false;
    }

    loading.value = false;
};

function convertirAHorasSegundos(hora) {
    let partes = hora.split(":");
    let h = parseInt(partes[0], 10) || 0;
    let m = parseInt(partes[1], 10) || 0;
    let s = parseInt(partes[2], 10) || 0;
    return h * 3600 + m * 60 + s;
}

const calculateHours = (event) => {
    form.overtime = 0;
    form.double_overtime = 0;
    form.triple_overtime = 0;
    if (!form.full_shift) {
        form.double_overtime = event.value < 9 ? event.value : 9;
    } else if (form.full_shift && !form.double_shift) {
        form.double_overtime = form.hours;
    } else {
        form.double_overtime = 3;
        form.triple_overtime = form.hours - 3;
    }
};

function diferenciaHoras() {
    const schedule = props.schedules.find(
        (schedule) => schedule.id === form.shift_id,
    );

    let sEntrada = convertirAHorasSegundos(schedule.entry_time);
    let sSalida = convertirAHorasSegundos(schedule.leave_time);
    if (sSalida < sEntrada) {
        sSalida += 24 * 3600;
    }

    form.hours = (sSalida - sEntrada) / 3600;

    form.shift_entry_time = schedule.entry_time;
    form.shift_leave_time = schedule.leave_time;

    calculateHours();
}

watch([selectedEmployee, selectedDate], fetchAttendanceInfo);

const submitForm = () => {
    sending.value = true;
    if (hoursCopy.value !== form.hours || momentCopy.value !== form.moment) {
        form.has_error = 1;
    } else {
        form.has_error = 0;
    }

    console.log(
        hoursCopy.value !== form.hours || momentCopy.value !== form.moment,
    );
    form.post(route("overtimes.store"), {
        preserveScroll: true,
        onSuccess: () => {
            showSuccess();
            sending.value = false;
        },
        onError: () => {
            showError();
            sending.value = false;
        },
    });
};
</script>
<template>
    <div class="w-full">
        <div class="card rounded-xl overflow-hidden">
            <div class="pb-6 border-b">
                <h2
                    class="text-lg font-bold mb-4 flex align-items-center gap-2"
                >
                    Crear Horas Extra
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
                            >Fecha</label
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
                <div class="pt-6">
                    <div
                        v-if="!selectedEmployee || !selectedDate"
                        class="flex flex-col items-center justify-center py-10 text-gray-400"
                    >
                        <i
                            class="pi pi-calendar-times text-4xl mb-3 opacity-50"
                        ></i>
                        <p>Selecciona un empleado y una fecha para comenzar.</p>
                    </div>
                    <div
                        v-else-if="selectedEmployee && selectedDate && !loading"
                        class="animate-fade-in"
                    >
                        <Message
                            v-if="noData"
                            severity="warn"
                            :closable="false"
                            class="mb-4"
                        >
                            No se encontró asistencia/turno para este empleado
                            en esta fecha. Puedes registrar las horas extra
                            manualmente.
                        </Message>
                        <div
                            v-if="!noData"
                            class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 p-4"
                        >
                            <div
                                class="rounded-lg p-7 border border-blue-100 relative overflow-hidden"
                                :class="isDark ? 'bg-blue-800' : 'bg-blue-50'"
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
                                        <span class="text-sm text-blue-600"
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
                                    class="text-green-800 font-semibold mb-3 flex items-center gap-2 mt-0"
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
                        <!-- ... tu encabezado y tarjetas de turno/asistencia se quedan igual ... -->

                        <Divider align="center" type="dashed">
                            <span class="px-3 text-sm font-medium"
                                >Registro de Horas Extra</span
                            >
                        </Divider>

                        <div
                            class="mt-6 rounded-xl p-6 border"
                            :class="
                                isDark
                                    ? 'bg-surface-900 border-surface-700'
                                    : 'bg-gray-50 border-gray-200'
                            "
                        >
                            <!-- CONFIGURACIÓN -->
                            <div class="mb-6">
                                <h3
                                    class="text-sm font-bold mb-4"
                                    :class="
                                        isDark
                                            ? 'text-surface-0'
                                            : 'text-gray-800'
                                    "
                                >
                                    Configuración
                                </h3>

                                <div
                                    class="grid grid-cols-1 lg:grid-cols-12 gap-6"
                                >
                                    <!-- BLOQUE: AJUSTES DE PAGO -->
                                    <div
                                        class="lg:col-span-4 rounded-xl border p-4"
                                        :class="
                                            isDark
                                                ? 'bg-surface-800 border-surface-700'
                                                : 'bg-white border-gray-200'
                                        "
                                    >
                                        <div
                                            class="flex items-center gap-2 mb-3"
                                        >
                                            <i
                                                class="pi pi-wallet"
                                                :class="
                                                    isDark
                                                        ? 'text-surface-200'
                                                        : 'text-gray-600'
                                                "
                                            ></i>
                                            <span
                                                class="text-sm font-semibold"
                                                :class="
                                                    isDark
                                                        ? 'text-surface-100'
                                                        : 'text-gray-800'
                                                "
                                            >
                                                Ajustes de pago
                                            </span>
                                        </div>

                                        <!-- Extemporáneo -->
                                        <div
                                            class="flex items-start justify-between gap-4"
                                        >
                                            <div class="flex flex-col">
                                                <span
                                                    class="text-sm font-medium"
                                                    :class="
                                                        isDark
                                                            ? 'text-surface-200'
                                                            : 'text-gray-700'
                                                    "
                                                >
                                                    Extemporáneo
                                                </span>
                                                <small
                                                    :class="
                                                        isDark
                                                            ? 'text-surface-400'
                                                            : 'text-gray-500'
                                                    "
                                                >
                                                    Habilita semana de pago
                                                </small>
                                            </div>
                                            <InputSwitch
                                                v-model="form.extemporaneous"
                                            />
                                        </div>

                                        <Divider class="my-4" />

                                        <!-- Semana de pago -->
                                        <div class="flex flex-col gap-2">
                                            <label
                                                class="text-sm font-medium"
                                                :class="
                                                    isDark
                                                        ? 'text-surface-200'
                                                        : 'text-gray-700'
                                                "
                                            >
                                                Semana de pago
                                            </label>
                                            <input
                                                type="week"
                                                class="w-full rounded-md border-gray-300"
                                                :disabled="!canPickPayWeek"
                                                v-model="form.pay_week_id"
                                            />
                                            <small
                                                class="p-error text-red-500"
                                                v-if="form.errors.pay_week_id"
                                                >{{
                                                    form.errors.pay_week_id
                                                }}</small
                                            >
                                            <small
                                                v-if="!canPickPayWeek"
                                                :class="
                                                    isDark
                                                        ? 'text-surface-400'
                                                        : 'text-gray-500'
                                                "
                                            >
                                                Activa “Extemporáneo” para
                                                seleccionar semana.
                                            </small>
                                            <small
                                                v-else
                                                :class="
                                                    isDark
                                                        ? 'text-surface-400'
                                                        : 'text-gray-500'
                                                "
                                            >
                                                Se guardará la semana
                                                seleccionada - 1 (ej. selecciona
                                                semana 8 se guardará la semana
                                                7)
                                            </small>
                                        </div>
                                    </div>

                                    <!-- BLOQUE: MODALIDAD -->
                                    <div
                                        class="lg:col-span-4 rounded-xl border p-4"
                                        :class="
                                            isDark
                                                ? 'bg-surface-800 border-surface-700'
                                                : 'bg-white border-gray-200'
                                        "
                                    >
                                        <div
                                            class="flex items-center gap-2 mb-3"
                                        >
                                            <i
                                                class="pi pi-sliders-h"
                                                :class="
                                                    isDark
                                                        ? 'text-surface-200'
                                                        : 'text-gray-600'
                                                "
                                            ></i>
                                            <span
                                                class="text-sm font-semibold"
                                                :class="
                                                    isDark
                                                        ? 'text-surface-100'
                                                        : 'text-gray-800'
                                                "
                                            >
                                                Modalidad
                                            </span>
                                        </div>

                                        <!-- Tiempo por tiempo -->
                                        <div
                                            class="flex items-start justify-between gap-4"
                                        >
                                            <div class="flex flex-col">
                                                <span
                                                    class="text-sm font-medium"
                                                    :class="
                                                        isDark
                                                            ? 'text-surface-200'
                                                            : 'text-gray-700'
                                                    "
                                                >
                                                    Tiempo por tiempo
                                                </span>
                                                <small
                                                    :class="
                                                        isDark
                                                            ? 'text-surface-400'
                                                            : 'text-gray-500'
                                                    "
                                                >
                                                    Inhabilita pago externo y
                                                    planta
                                                </small>
                                            </div>
                                            <InputSwitch
                                                v-model="form.time_for_time"
                                            />
                                        </div>

                                        <Divider class="my-4" />

                                        <!-- Pago externo -->
                                        <div
                                            class="flex items-start justify-between gap-4"
                                        >
                                            <div class="flex flex-col">
                                                <span
                                                    class="text-sm font-medium"
                                                    :class="
                                                        isDark
                                                            ? 'text-surface-200'
                                                            : 'text-gray-700'
                                                    "
                                                >
                                                    Pago externo
                                                </span>
                                                <small
                                                    v-if="externalPayDisabled"
                                                    class="text-xs"
                                                    :class="
                                                        isDark
                                                            ? 'text-surface-400'
                                                            : 'text-gray-500'
                                                    "
                                                >
                                                    Deshabilitado por “Tiempo
                                                    por tiempo”.
                                                </small>
                                                <small
                                                    v-else
                                                    class="text-xs"
                                                    :class="
                                                        isDark
                                                            ? 'text-surface-400'
                                                            : 'text-gray-500'
                                                    "
                                                >
                                                    Habilita selección de planta
                                                </small>
                                            </div>
                                            <InputSwitch
                                                v-model="form.external_pay"
                                                :disabled="externalPayDisabled"
                                            />
                                        </div>

                                        <Divider class="my-4" />

                                        <!-- Planta -->
                                        <div class="flex flex-col gap-2">
                                            <label
                                                class="text-sm font-medium"
                                                :class="
                                                    isDark
                                                        ? 'text-surface-200'
                                                        : 'text-gray-700'
                                                "
                                            >
                                                Planta
                                            </label>
                                            <Select
                                                v-model="form.plant_id"
                                                :options="props.branchOffices"
                                                optionLabel="code"
                                                optionValue="id"
                                                placeholder="Seleccione una planta"
                                                class="w-full"
                                                :disabled="plantDisabled"
                                            />
                                            <small
                                                class="p-error text-red-500"
                                                v-if="form.errors.plant_id"
                                                >{{
                                                    form.errors.plant_id
                                                }}</small
                                            >
                                            <small
                                                v-if="plantDisabled"
                                                class="text-xs"
                                                :class="
                                                    isDark
                                                        ? 'text-surface-400'
                                                        : 'text-gray-500'
                                                "
                                            >
                                                Disponible solo con “Pago
                                                externo” y sin “Tiempo por
                                                tiempo”.
                                            </small>
                                        </div>
                                    </div>

                                    <!-- BLOQUE: TURNO -->
                                    <div
                                        class="lg:col-span-4 rounded-xl border p-4"
                                        :class="
                                            isDark
                                                ? 'bg-surface-800 border-surface-700'
                                                : 'bg-white border-gray-200'
                                        "
                                    >
                                        <div
                                            class="flex items-center gap-2 mb-3"
                                        >
                                            <i
                                                class="pi pi-calendar"
                                                :class="
                                                    isDark
                                                        ? 'text-surface-200'
                                                        : 'text-gray-600'
                                                "
                                            ></i>
                                            <span
                                                class="text-sm font-semibold"
                                                :class="
                                                    isDark
                                                        ? 'text-surface-100'
                                                        : 'text-gray-800'
                                                "
                                            >
                                                Turno
                                            </span>
                                        </div>

                                        <!-- Turno completo -->
                                        <div
                                            class="flex items-start justify-between gap-4"
                                        >
                                            <div class="flex flex-col">
                                                <span
                                                    class="text-sm font-medium"
                                                    :class="
                                                        isDark
                                                            ? 'text-surface-200'
                                                            : 'text-gray-700'
                                                    "
                                                >
                                                    Turno completo
                                                </span>
                                                <small
                                                    :class="
                                                        isDark
                                                            ? 'text-surface-400'
                                                            : 'text-gray-500'
                                                    "
                                                >
                                                    Habilita selección de turno
                                                    (y bloquea horas normales)
                                                </small>
                                            </div>
                                            <InputSwitch
                                                v-model="form.full_shift"
                                            />
                                        </div>

                                        <Divider class="my-4" />

                                        <div
                                            class="flex items-start justify-between gap-4"
                                        >
                                            <div class="flex flex-col">
                                                <span
                                                    class="text-sm font-medium"
                                                    :class="
                                                        isDark
                                                            ? 'text-surface-200'
                                                            : 'text-gray-700'
                                                    "
                                                >
                                                    Doble Turno
                                                </span>
                                            </div>
                                            <InputSwitch
                                                v-model="form.double_shift"
                                                :disabled="!shiftSelectEnabled"
                                                @change="calculateHours()"
                                            />
                                        </div>

                                        <Divider class="my-4" />

                                        <!-- Select Turno -->
                                        <div class="flex flex-col gap-2">
                                            <label
                                                class="text-sm font-medium"
                                                :class="
                                                    isDark
                                                        ? 'text-surface-200'
                                                        : 'text-gray-700'
                                                "
                                            >
                                                Turno
                                            </label>
                                            <Select
                                                v-model="form.shift_id"
                                                :options="props.schedules"
                                                optionLabel="name"
                                                optionValue="id"
                                                placeholder="Seleccione un turno"
                                                class="w-full"
                                                filter
                                                :disabled="!shiftSelectEnabled"
                                                @change="diferenciaHoras()"
                                            />
                                            <small
                                                class="p-error text-red-500"
                                                v-if="form.errors.shift_id"
                                                >{{
                                                    form.errors.shift_id
                                                }}</small
                                            >
                                            <small
                                                v-if="!shiftSelectEnabled"
                                                :class="
                                                    isDark
                                                        ? 'text-surface-400'
                                                        : 'text-gray-500'
                                                "
                                            >
                                                Activa “Turno completo” para
                                                seleccionar turno.
                                            </small>
                                        </div>
                                    </div>

                                    <!-- MOTIVOS (ANCHO COMPLETO ABAJO) -->
                                    <div
                                        class="lg:col-span-12 flex flex-col gap-2"
                                    >
                                        <label
                                            class="text-sm font-medium"
                                            :class="
                                                isDark
                                                    ? 'text-surface-200'
                                                    : 'text-gray-700'
                                            "
                                        >
                                            Motivos
                                        </label>
                                        <Select
                                            v-model="form.motivo_id"
                                            :options="props.motivos"
                                            :optionLabel="
                                                (option) =>
                                                    '(' +
                                                    option.id +
                                                    ') ' +
                                                    option.name
                                            "
                                            filter
                                            optionValue="id"
                                            placeholder="Seleccione un motivo"
                                            class="w-full"
                                        />
                                        <small
                                            class="p-error text-red-500"
                                            v-if="form.errors.motivo_id"
                                            >{{ form.errors.motivo_id }}</small
                                        >
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- HORAS -->
                            <div class="mb-6">
                                <h3
                                    class="text-sm font-bold mb-4"
                                    :class="
                                        isDark
                                            ? 'text-surface-0'
                                            : 'text-gray-800'
                                    "
                                >
                                    Horas extra
                                </h3>

                                <div class="grid grid-cols-3 gap-6">
                                    <!-- Momento -->
                                    <div class="col-span-1 flex flex-col gap-3">
                                        <label
                                            class="text-sm font-bold"
                                            :class="
                                                isDark
                                                    ? 'text-surface-200'
                                                    : 'text-gray-700'
                                            "
                                        >
                                            Momento de las Horas Extra
                                        </label>

                                        <SelectButton
                                            v-model="form.moment"
                                            :options="momentOptions"
                                            optionLabel="label"
                                            optionValue="value"
                                            class="w-full custom-select-button flex justify-between"
                                        >
                                            <template #option="slotProps">
                                                <div
                                                    class="flex flex-col items-center py-2 w-full"
                                                >
                                                    <i
                                                        :class="
                                                            slotProps.option
                                                                .icon
                                                        "
                                                        class="mb-1 text-lg"
                                                    ></i>
                                                    <span class="text-xs">{{
                                                        slotProps.option.label
                                                    }}</span>
                                                </div>
                                            </template>
                                        </SelectButton>

                                        <small class="text-red-500 text-xs">{{
                                            form.errors.moment
                                        }}</small>
                                    </div>

                                    <!-- Horas normales -->
                                    <div class="col-span-1 flex flex-col gap-3">
                                        <label
                                            class="text-sm font-bold"
                                            :class="
                                                isDark
                                                    ? 'text-surface-200'
                                                    : 'text-gray-700'
                                            "
                                        >
                                            Total Horas
                                        </label>

                                        <InputNumber
                                            v-model="form.hours"
                                            buttonLayout="horizontal"
                                            :min="0"
                                            :max="500"
                                            :step="0.5"
                                            suffix=" Hrs"
                                            inputClass="text-center font-bold"
                                            class="w-full"
                                            :disabled="normalHoursDisabled"
                                            @input="calculateHours"
                                        >
                                        </InputNumber>

                                        <small
                                            :class="
                                                isDark
                                                    ? 'text-surface-400'
                                                    : 'text-gray-500'
                                            "
                                            class="text-xs"
                                        >
                                            Incrementos de 30 min (0.5)
                                        </small>
                                        <small class="text-red-500 text-xs">{{
                                            form.errors.hours
                                        }}</small>
                                    </div>

                                    <!-- Dobles y triples (tarjetas) -->
                                    <div class="col-span-1">
                                        <div class="flex gap-4 mt-1">
                                            <div
                                                class="flex-1 surface-card p-3 border-round-lg shadow-1 border-left-3 border-blue-500 flex justify-content-between align-items-center"
                                                :class="
                                                    isDark
                                                        ? 'bg-surface-800'
                                                        : ''
                                                "
                                            >
                                                <div
                                                    class="flex flex-column gap-1"
                                                >
                                                    <span
                                                        class="text-600 font-medium text-sm"
                                                        >Horas Dobles</span
                                                    >
                                                    <span
                                                        class="text-3xl font-bold text-blue-600"
                                                    >
                                                        {{
                                                            form.double_overtime ||
                                                            0
                                                        }}
                                                        hrs
                                                    </span>
                                                </div>
                                                <i
                                                    class="pi pi-clock text-blue-300 text-5xl"
                                                ></i>
                                            </div>

                                            <div
                                                class="flex-1 surface-card p-3 border-round-lg shadow-1 border-left-3 border-purple-500 flex justify-content-between align-items-center"
                                                :class="
                                                    isDark
                                                        ? 'bg-surface-800'
                                                        : ''
                                                "
                                            >
                                                <div
                                                    class="flex flex-column gap-1"
                                                >
                                                    <span
                                                        class="text-600 font-medium text-sm"
                                                        >Horas Triples</span
                                                    >
                                                    <span
                                                        class="text-3xl font-bold text-purple-600"
                                                    >
                                                        {{
                                                            form.triple_overtime ||
                                                            0
                                                        }}
                                                        hrs
                                                    </span>
                                                </div>
                                                <i
                                                    class="pi pi-bolt text-purple-300 text-5xl"
                                                ></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- OBSERVACIONES -->
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                                <div class="lg:col-span-12 flex flex-col gap-3">
                                    <label
                                        class="text-sm font-bold"
                                        :class="
                                            isDark
                                                ? 'text-surface-200'
                                                : 'text-gray-700'
                                        "
                                    >
                                        Observaciones / Justificación
                                    </label>
                                    <Textarea
                                        v-model="form.observations"
                                        rows="3"
                                        class="w-full resize-none"
                                        :class="
                                            isDark
                                                ? 'bg-surface-900 text-surface-0 border-surface-700'
                                                : 'bg-white'
                                        "
                                        placeholder="Describe brevemente la razón..."
                                    />
                                </div>
                            </div>

                            <div
                                class="flex justify-end mt-6 pt-4 border-t"
                                :class="
                                    isDark
                                        ? 'border-surface-700'
                                        : 'border-gray-200'
                                "
                            >
                                <Button
                                    label="Guardar"
                                    icon="pi pi-save"
                                    class="p-button-primary px-6"
                                    @click="submitForm"
                                    :disabled="
                                        sending ||
                                        (normalHoursDisabled
                                            ? !form.shift_id
                                            : !form.moment || form.hours <= 0)
                                    "
                                    :loading="sending"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
