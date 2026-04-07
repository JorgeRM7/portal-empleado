<script setup>
import { computed, onMounted, ref, watch } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { useForm } from "@inertiajs/vue3";
import { useToastService } from "@/Stores/toastService";
import axios from "axios"; // Asegúrate de importar axios

const props = defineProps({
    txt: {
        type: Object,
        default: () => ({}), // Valor por defecto para evitar errores si es null
    },
});

const { showError, showSuccess } = useToastService();

// --- VARIABLES REACTIVAS ---
const selectedEmployee = ref(null);
const selectedDate = ref(null);
const attendanceData = ref(null);
const noData = ref(false);
const loading = ref(false);
const employees = ref([]);

const selectedBranchOffice = ref(
    JSON.parse(localStorage.getItem("selectedBranchOffice")) || { id: null },
);

const form = useForm({
    moment: props.txt?.moment || null,
    hours: props.txt?.hours || 0,
    observations: props.txt?.observations || "",
    branchOfficeId: props.txt?.branchOfficeId || selectedBranchOffice.value.id,
    employeeId: props.txt?.employee_id || null,
    date: props.txt?.date || null,
    schedule_entry: props.txt?.schedule_entry_time || null,
    schedule_exit: props.txt?.schedule_leave_time || null,
    scheduleId: props.txt?.schedule_id || null,
    // Agregamos el ID del registro para saber si es edición
    id: props.txt?.id || null,
});

const momentOptions = ref([
    { label: "Antes de Entrada", value: "before", icon: "pi pi-arrow-left" },
    { label: "Después de Salida", value: "after", icon: "pi pi-arrow-right" },
    { label: "Ambos Turnos", value: "both", icon: "pi pi-arrows-h" },
]);

// --- HELPERS DE FECHAS ---

// Convierte objeto Date a String "YYYY-MM-DD" para la API
const formatDate = (date) => {
    if (!date) return "";
    const month = date.getMonth() + 1;
    const day = date.getDate();
    const year = date.getFullYear();
    return `${year}-${month < 10 ? "0" + month : month}-${day < 10 ? "0" + day : day}`;
};

// Convierte String "YYYY-MM-DD" a objeto Date (sin problemas de zona horaria)
const stringToDate = (dateString) => {
    if (!dateString) return null;
    const [year, month, day] = dateString.split("-").map(Number);
    return new Date(year, month - 1, day);
};

// Convierte hora HH:mm a Date
const parseTime = (timeStr) => {
    if (!timeStr) return null;
    const [hours, minutes] = timeStr.split(":").map(Number);
    const date = new Date();
    date.setHours(hours, minutes, 0, 0);
    return date;
};

// --- API & LÓGICA ---

const employeeOptions = computed(() =>
    employees.value.map((e) => ({
        ...e,
        label: `(${e.id}) ${e.full_name}`,
    })),
);

const getEmployees = async () => {
    if (!selectedBranchOffice.value.id) return;

    // No activamos loading global para no parpadear toda la interfaz,
    // pero podrías usar un loading local si quisieras.
    try {
        const response = await axios.get(
            `/api/employee-branchOffices?branchOfficeId=${selectedBranchOffice.value.id}`,
        );
        employees.value = response.data;
    } catch (error) {
        console.error("Error cargando empleados", error);
    }
};

const fetchAttendanceInfo = async () => {
    // Evitar llamadas si faltan datos
    if (!selectedEmployee.value || !selectedDate.value) return;

    loading.value = true;
    noData.value = false;
    attendanceData.value = null;

    try {
        const dateFormatted = formatDate(selectedDate.value);
        // Aseguramos enviar el ID, ya sea del objeto o directo
        const empId = selectedEmployee.value.id || selectedEmployee.value;

        const response = await axios.get(
            `/api/txts/search-employee-data?date=${dateFormatted}&employee_id=${empId}`,
        );

        if (response.data && response.data.length > 0) {
            attendanceData.value = response.data[0];
            noData.value = false;
            // Si es una creación nueva, calculamos. Si es edición, respetamos lo guardado o recalculamos.
            // Aquí recalculamos para asegurar consistencia visual.
            calculateOvertime();
        } else {
            attendanceData.value = null;
            noData.value = true;
        }
    } catch (error) {
        console.error(error);
        noData.value = true;
    } finally {
        loading.value = false;
    }
};

const calculateOvertime = () => {
    if (!attendanceData.value) return;

    // ... (Tu lógica de cálculo se mantiene igual)
    const { entradaTeorica, salidaTeorica, entradaReal, salidaReal } =
        attendanceData.value;
    const shiftStart = parseTime(entradaTeorica);
    const shiftEnd = parseTime(salidaTeorica);
    const actualIn = parseTime(entradaReal);
    const actualOut = parseTime(salidaReal);

    if (!shiftStart || !shiftEnd || !actualIn || !actualOut) return;

    if (shiftEnd < shiftStart) shiftEnd.setDate(shiftEnd.getDate() + 1);
    if (actualOut < actualIn) actualOut.setDate(actualOut.getDate() + 1);

    const TOLERANCE = 10;
    let minutesBefore = (shiftStart - actualIn) / (1000 * 60);
    let minutesAfter = (actualOut - shiftEnd) / (1000 * 60);

    let validMinutesBefore = minutesBefore > TOLERANCE ? minutesBefore : 0;
    let validMinutesAfter = minutesAfter > TOLERANCE ? minutesAfter : 0;

    // NOTA: Si estamos EDITANDO, tal vez no quieras sobrescribir los valores manuales
    // que el usuario ya guardó. Podrías poner una condición aquí:
    // if (!props.txt.id) { ... lógica de auto-llenado ... }

    // Por ahora dejo tu lógica original que calcula y sugiere:
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

    // Solo sobrescribimos si el formulario está vacío o es un registro nuevo
    // O si prefieres que siempre recalcule, quita el 'if'
    if (!form.id) {
        const totalHours = Math.floor(totalMinutes / 60);
        form.moment = calculatedMoment;
        form.hours = totalHours > 0 ? totalHours : 0;
    }
};

// --- WATCHERS & LIFECYCLE ---

// Vigilamos cambios MANUALES en los inputs
watch(
    [selectedEmployee, selectedDate],
    ([newEmp, newDate], [oldEmp, oldDate]) => {
        // Solo ejecutamos fetch si los valores realmente cambiaron y no son nulos
        if (newEmp && newDate) {
            // Pequeña validación para evitar bucle infinito al montar
            // Si el valor es igual al inicial, fetchAttendanceInfo ya se llamó en mounted
            const isInitialMount = loading.value === true;
            if (!isInitialMount) {
                fetchAttendanceInfo();
            }
        }
    },
);

onMounted(async () => {
    loading.value = true;

    // 1. Cargar lista de empleados
    await getEmployees();

    // 2. Si hay datos de edición (props.txt tiene datos)
    if (props.txt && props.txt.employee_id) {
        // A. Setear Fecha: Convertir String de Laravel a Objeto Date JS
        if (props.txt.date) {
            selectedDate.value = stringToDate(props.txt.date);
        }

        // B. Setear Empleado: Buscar el OBJETO completo en la lista array
        // Esto es crucial para que el Select lo muestre seleccionado
        const foundEmployee = employeeOptions.value.find(
            (e) => e.id === props.txt.employee_id,
        );

        if (foundEmployee) {
            selectedEmployee.value = foundEmployee;
        }

        // C. Cargar la info del turno para visualizar las tarjetas
        if (selectedDate.value && selectedEmployee.value) {
            await fetchAttendanceInfo();
        }
    }

    loading.value = false;
});

const submitForm = () => {
    if (!selectedEmployee.value) return;

    form.employeeId = selectedEmployee.value.id;
    form.date = formatDate(selectedDate.value);
    if (attendanceData.value) {
        form.schedule_entry = attendanceData.value.entradaTeorica;
        form.schedule_exit = attendanceData.value.salidaTeorica;
        form.scheduleId = attendanceData.value.schedule_id;
    }

    loading.value = true;

    form.put(route("txt.update", { txt: form.id }), {
        onSuccess: () => {
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
    <AppLayout title="Editar Tiempo Por Tiempo">
        <div class="w-full">
            <div class="card rounded-xl overflow-hidden">
                <div class="pb-6 border-b">
                    <h2
                        class="text-lg font-bold mb-4 flex align-items-center gap-2"
                    >
                        Editar Tiempo Por Tiempo
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
                                class="bg-blue-50 rounded-lg p-7 border border-blue-100 relative overflow-hidden"
                            >
                                <div
                                    class="absolute right-0 top-0 p-3 opacity-10"
                                >
                                    <i
                                        class="pi pi-calendar text-6xl text-blue-800"
                                    ></i>
                                </div>
                                <h3
                                    class="text-blue-800 font-semibold mb-3 flex items-center gap-2 mt-0"
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
                                                class="block text-xs text-gray-500 uppercase"
                                                >Entrada</span
                                            >
                                            <span
                                                class="text-lg font-bold text-gray-700"
                                                >{{
                                                    attendanceData?.entradaTeorica
                                                }}</span
                                            >
                                        </div>
                                        <div class="text-center">
                                            <span
                                                class="block text-xs text-gray-500 uppercase"
                                                >Salida</span
                                            >
                                            <span
                                                class="text-lg font-bold text-gray-700"
                                                >{{
                                                    attendanceData?.salidaTeorica
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="bg-green-50 rounded-lg p-7 border border-green-100 relative overflow-hidden"
                            >
                                <div
                                    class="absolute right-0 top-0 p-3 opacity-10"
                                >
                                    <i
                                        class="pi pi-check-circle text-6xl text-green-800"
                                    ></i>
                                </div>
                                <h3
                                    class="text-green-800 font-semibold mb-3 flex items-center gap-2 mt-0"
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
                                            class="text-xs text-green-600 font-medium uppercase mb-1"
                                            >Checó Entrada</span
                                        >
                                        <span
                                            class="text-2xl font-black text-gray-800"
                                            >{{
                                                attendanceData?.entradaReal
                                            }}</span
                                        >
                                        <i
                                            class="pi pi-sign-in text-green-500 mt-1"
                                        ></i>
                                    </div>
                                    <div
                                        class="flex flex-col items-center flex-1"
                                    >
                                        <span
                                            class="text-xs text-green-600 font-medium uppercase mb-1"
                                            >Checó Salida</span
                                        >
                                        <span
                                            class="text-2xl font-black text-gray-800"
                                            >{{
                                                attendanceData?.salidaReal
                                            }}</span
                                        >
                                        <i
                                            class="pi pi-sign-out text-green-500 mt-1"
                                        ></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <Divider align="center" type="dashed">
                            <span
                                class="bg-white px-3 text-gray-400 text-sm font-medium"
                                >Registro de Tiempo Por Tiempo</span
                            >
                        </Divider>

                        <div
                            class="mt-6 bg-gray-50 rounded-xl p-6 border border-gray-200"
                        >
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
                                        class="w-full resize-none bg-white"
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
