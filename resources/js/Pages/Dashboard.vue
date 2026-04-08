<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, onMounted, onUnmounted, computed } from "vue";
import axios from "axios";
import { useToastService } from "../Stores/toastService.js";
import Button from "primevue/button";
import Calendar from "primevue/calendar";
import { Link } from "@inertiajs/vue3";

const { showSuccess, showError } = useToastService();

const props = defineProps({
    users: Number,
    user: Object,
    idEmployee: Array,
});

console.log(props);

// console.log(props.idEmployee?.[0].id);

const currentTime = ref(new Date().toLocaleTimeString());
let intervalId = null;

const loadingDashboard = ref(false);
const payrollStats = ref({});

const holidays = ref([
    { date: "2026-01-01", name: "Año Nuevo" },
    { date: "2026-02-02", name: "Día de la Constitución (puente)" },
    { date: "2026-03-16", name: "Natalicio de Benito Juárez (puente)" },
    { date: "2026-05-01", name: "Día del Trabajo" },
    { date: "2026-09-16", name: "Día de la Independencia" },
    { date: "2026-11-16", name: "Revolución Mexicana (puente)" },
    { date: "2026-12-25", name: "Navidad" },
]);

const plantStats = ref([]);

const selectedHolidayDate = ref(new Date());

const visibleMonth = ref(selectedHolidayDate.value.getMonth());
const visibleYear = ref(selectedHolidayDate.value.getFullYear());

const onMonthChange = (e) => {
    const m = e.month;

    visibleMonth.value = m >= 1 && m <= 12 ? m - 1 : m;
    visibleYear.value = e.year;
};

const parseISOToDate = (iso) => {
    const [y, m, d] = iso.split("-").map(Number);
    return new Date(y, m - 1, d);
};

const isSameMonthYear = (d, month, year) =>
    d.getMonth() === month && d.getFullYear() === year;

const formatDMY = (iso) => {
    const d = parseISOToDate(iso);
    const dd = String(d.getDate()).padStart(2, "0");
    const mm = String(d.getMonth() + 1).padStart(2, "0");
    const yy = d.getFullYear();
    return `${dd}/${mm}/${yy}`;
};

const sortedHolidays = computed(() => {
    const month = visibleMonth.value;
    const year = visibleYear.value;

    return (holidays.value ?? [])
        .filter((h) => isSameMonthYear(parseISOToDate(h.date), month, year))
        .slice()
        .sort((a, b) => parseISOToDate(a.date) - parseISOToDate(b.date))
        .map((h) => ({
            ...h,
            dateFormatted: formatDMY(h.date),
        }));
});

const formatNumber = (number) => {
    return new Intl.NumberFormat().format(number ?? 0);
};

const loadDashboardMetrics = async () => {
    try {
        loadingDashboard.value = true;

        const { data } = await axios.get("/api/dashboard/metrics");

        payrollStats.value = data;
        plantStats.value = data.plant_stats;
    } catch (e) {
        console.error(e);
        showError();
    } finally {
        loadingDashboard.value = false;
    }
};

onMounted(() => {
    intervalId = setInterval(() => {
        currentTime.value = new Date().toLocaleTimeString();
    }, 1000);

    loadDashboardMetrics();
});

onUnmounted(() => {
    clearInterval(intervalId);
});
</script>

<template>
    <AppLayout title="Dashboard">
        <!-- FILA SUPERIOR: BIENVENIDA / USUARIOS / HORA -->
        <div class="flex flex-col lg:flex-row gap-4 mb-6">
            <div class="lg:w-1/2 card border-none">
                <div class="flex items-center">
                    <img
                        class="w-16 h-16 rounded-full object-cover"
                        :src="`https://nominas.grupo-ortiz.site/Librerias/img/Fotos/${props.idEmployee[0]?.id}.jpg`"
                        @error="
                            (e) =>
                                (e.target.src =
                                    $page.props.auth.user?.profile_photo_url)
                        "
                        alt="Foto de perfil"
                    />
                    <div>
                        <h1 class="text-xl font-normal mb-0 ml-5 font-display">
                            Bienvenid@ {{ $page.props.auth.user?.name }}
                        </h1>
                        <h2
                            class="text-sm text-gray-600 font-normal ml-5 mt-1 mb-0"
                        >
                            Panel general del sistema de nóminas
                        </h2>
                    </div>
                </div>
            </div>

            <div class="card mb-0 lg:w-1/4 border-none">
                <div class="flex justify-between">
                    <div>
                        <h3 class="block text-lg font-medium mb-2">
                            Usuarios totales
                        </h3>
                        <div
                            class="text-surface-900 dark:text-surface-0 font-medium text-xl"
                        >
                            <h4>{{ formatNumber(props.users) }}</h4>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            Usuarios con acceso al sistema
                        </p>
                    </div>
                    <div
                        class="flex items-center justify-center bg-cyan-100 dark:bg-cyan-400/10 rounded"
                        style="width: 3.5rem; height: 3.5rem"
                    >
                        <i class="pi pi-users text-cyan-500 !text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="card mb-0 lg:w-1/4 border-none">
                <div class="flex justify-between">
                    <div
                        class="text-surface-900 dark:text-surface-0 font-medium text-xl"
                    >
                        <h3 class="block text-lg font-medium mb-2">
                            Hora actual
                        </h3>
                        <h4>{{ currentTime }}</h4>
                        <p class="text-xs text-gray-500 mt-1">Horario local</p>
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

        <!-- FILA DE MÉTRICAS PRINCIPALES -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
            <div class="card">
                <div class="flex justify-between items-center">
                    <div>
                        <span class="text-sm text-gray-500">
                            Empleados totales
                        </span>
                        <h2
                            v-if="!loadingDashboard"
                            class="text-2xl font-semibold mt-1"
                        >
                            {{ formatNumber(payrollStats.total_employees) }}
                        </h2>
                        <Skeleton v-else class="h-6 w-20" />
                        <p class="text-xs text-gray-500 mt-1">
                            Incluye activos e inactivos
                        </p>
                    </div>
                    <div
                        class="flex items-center justify-center bg-emerald-100 dark:bg-emerald-400/10 rounded"
                        style="width: 3rem; height: 3rem"
                    >
                        <i
                            class="pi pi-briefcase text-emerald-500 !text-xl"
                        ></i>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="flex justify-between items-center">
                    <div>
                        <span class="text-sm text-gray-500">
                            Empleados dados de alta
                        </span>
                        <h2
                            v-if="!loadingDashboard"
                            class="text-2xl font-semibold mt-1"
                        >
                            {{ formatNumber(payrollStats.new_employees) }}
                        </h2>
                        <Skeleton v-else class="h-6 w-20" />
                        <p class="text-xs text-gray-500 mt-1">
                            Disponibles para nómina
                        </p>
                    </div>
                    <div
                        class="flex items-center justify-center bg-lime-100 dark:bg-lime-400/10 rounded"
                        style="width: 3rem; height: 3rem"
                    >
                        <i class="pi pi-user-plus text-lime-600 !text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="flex justify-between items-center">
                    <div>
                        <span class="text-sm text-gray-500">
                            Empleados dados de baja
                        </span>
                        <h2
                            v-if="!loadingDashboard"
                            class="text-2xl font-semibold mt-1"
                        >
                            {{
                                formatNumber(payrollStats.termination_employees)
                            }}
                        </h2>
                        <Skeleton v-else class="h-6 w-20" />
                        <p class="text-xs text-gray-500 mt-1">
                            Historial conservado para reportes
                        </p>
                    </div>
                    <div
                        class="flex items-center justify-center bg-rose-100 dark:bg-rose-400/10 rounded"
                        style="width: 3rem; height: 3rem"
                    >
                        <i class="pi pi-user-minus text-rose-500 !text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="flex justify-between items-center">
                    <div>
                        <span class="text-sm text-gray-500">
                            Asistencias registradas hoy
                        </span>
                        <h2
                            v-if="!loadingDashboard"
                            class="text-2xl font-semibold mt-1"
                        >
                            {{ formatNumber(payrollStats.assistances) }}
                        </h2>
                        <Skeleton v-else class="h-6 w-20" />
                        <p class="text-xs text-gray-500 mt-1">
                            Entradas registradas en el día
                        </p>
                    </div>
                    <div
                        class="flex items-center justify-center bg-sky-100 dark:bg-sky-400/10 rounded"
                        style="width: 3rem; height: 3rem"
                    >
                        <i
                            class="pi pi-calendar-clock text-sky-500 !text-xl"
                        ></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
            <div class="lg:col-span-2 flex flex-col">
                <div class="card">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">
                            Resumen de asistencias de hoy
                        </h3>
                        <Link href="/assistences/weekly-assistences">
                            <Button
                                label="Ver asistencias"
                                icon="pi pi-arrow-right"
                                text
                            />
                        </Link>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div
                            class="border rounded-lg p-4 flex flex-col justify-between"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500"
                                    >Presentes</span
                                >
                                <i
                                    class="pi pi-check-circle text-emerald-500 !text-xl"
                                ></i>
                            </div>
                            <h2
                                v-if="!loadingDashboard"
                                class="text-2xl font-semibold mt-2"
                            >
                                {{
                                    formatNumber(
                                        payrollStats.complete_assistances,
                                    )
                                }}
                            </h2>
                            <Skeleton v-else class="h-6 w-20" />
                        </div>

                        <div
                            class="border rounded-lg p-4 flex flex-col justify-between"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500"
                                    >Ausentes</span
                                >
                                <i
                                    class="pi pi-times-circle text-rose-500 !text-xl"
                                ></i>
                            </div>
                            <h2
                                v-if="!loadingDashboard"
                                class="text-2xl font-semibold mt-2"
                            >
                                {{ formatNumber(payrollStats.abstences) }}
                            </h2>
                            <Skeleton v-else class="h-6 w-20" />
                        </div>

                        <div
                            class="border rounded-lg p-4 flex flex-col justify-between"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500"
                                    >Retardos</span
                                >
                                <i
                                    class="pi pi-exclamation-circle text-amber-500 !text-xl"
                                ></i>
                            </div>
                            <h2
                                v-if="!loadingDashboard"
                                class="text-2xl font-semibold mt-2"
                            >
                                {{ formatNumber(payrollStats.late) }}
                            </h2>
                            <Skeleton v-else class="h-6 w-20" />
                        </div>
                    </div>
                </div>
                <div class="card h-[250px] overflow-y-auto">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">
                            Resumen de empleados por planta
                        </h3>
                    </div>

                    <div
                        v-if="loadingDashboard"
                        class="grid grid-cols-1 md:grid-cols-3 gap-4"
                    >
                        <Skeleton class="h-20 w-full" />
                        <Skeleton class="h-20 w-full" />
                        <Skeleton class="h-20 w-full" />
                        <Skeleton class="h-20 w-full" />
                        <Skeleton class="h-20 w-full" />
                        <Skeleton class="h-20 w-full" />
                        <Skeleton class="h-20 w-full" />
                        <Skeleton class="h-20 w-full" />
                        <Skeleton class="h-20 w-full" />
                    </div>

                    <div v-else>
                        <div
                            v-if="!plantStats || plantStats.length === 0"
                            class="text-sm text-gray-500"
                        >
                            No hay información de plantas.
                        </div>

                        <div
                            v-else
                            class="grid grid-cols-1 md:grid-cols-3 gap-4"
                        >
                            <div
                                v-for="(p, i) in plantStats"
                                :key="i"
                                class="border rounded-lg p-4 flex items-center justify-between"
                            >
                                <div class="flex flex-col">
                                    <span class="text-sm text-gray-500"
                                        >Planta</span
                                    >
                                    <span class="text-base font-semibold">{{
                                        p.branch_office
                                    }}</span>
                                </div>

                                <div class="text-right">
                                    <span class="text-sm text-gray-500"
                                        >Empleados</span
                                    >
                                    <div class="text-2xl font-semibold">
                                        {{ formatNumber(p.total_employees) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="mt-4 pt-4 border-t flex items-center justify-between"
                        >
                            <span class="text-sm text-gray-500"
                                >Total general</span
                            >
                            <span class="text-base font-semibold">
                                {{
                                    formatNumber(
                                        plantStats.reduce(
                                            (acc, x) =>
                                                acc + (x.total_employees ?? 0),
                                            0,
                                        ),
                                    )
                                }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">
                        Calendario de días feriados
                    </h3>
                    <span class="text-xs text-gray-500"
                        >Días no laborales considerados en la nómina</span
                    >
                </div>

                <Calendar
                    v-model="selectedHolidayDate"
                    inline
                    :show-button-bar="false"
                    date-format="dd/mm/yy"
                    class="w-full"
                    @month-change="onMonthChange"
                />

                <div class="mt-4">
                    <h4 class="text-sm font-semibold mb-2">
                        Días feriados del mes
                    </h4>

                    <div v-if="loadingDashboard" class="text-sm text-gray-500">
                        Cargando días feriados...
                    </div>

                    <div v-else>
                        <div
                            v-if="sortedHolidays.length === 0"
                            class="text-sm text-gray-500"
                        >
                            No hay días feriados registrados para este mes.
                        </div>

                        <ul
                            v-else
                            class="space-y-2 max-h-52 overflow-auto pr-1"
                        >
                            <li
                                v-for="(holiday, index) in sortedHolidays"
                                :key="index"
                                class="flex justify-between text-sm border rounded px-2 py-1"
                            >
                                <span>{{ holiday.name }}</span>
                                <span class="text-gray-600">{{
                                    holiday.dateFormatted
                                }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- ACCIONES RÁPIDAS -->
        <div class="card">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Acciones rápidas</h3>
                <span class="text-xs text-gray-500">
                    Atajos frecuentes del sistema de nóminas
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <Link href="/employee/catalog">
                    <Button
                        class="w-full justify-start"
                        icon="pi pi-id-card"
                        label="Ver empleados"
                        outlined
                    />
                </Link>

                <Link href="/payroll/payroll-departaments">
                    <Button
                        class="w-full justify-start"
                        icon="pi pi-money-bill"
                        label="Nomina"
                        outlined
                    />
                </Link>

                <Link href="/assistences/weekly-assistences">
                    <Button
                        class="w-full justify-start"
                        icon="pi pi-wallet"
                        label="Ver asistencias"
                        outlined
                    />
                </Link>

                <Link href="/employee/incidences-employee">
                    <Button
                        class="w-full justify-start"
                        icon="pi pi-exclamation-triangle"
                        label="Nueva incidencia"
                        outlined
                    />
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
