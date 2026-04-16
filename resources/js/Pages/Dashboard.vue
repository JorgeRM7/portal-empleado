<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, onMounted, onUnmounted, computed } from "vue";
import { usePage, Link } from "@inertiajs/vue3";
import Button from "primevue/button";
import Tag from "primevue/tag";
import Divider from "primevue/divider";
import Skeleton from "primevue/skeleton";
import Calendar from "primevue/calendar";
import Tooltip from 'primevue/tooltip';

// app.directive('tooltip', Tooltip);
const page = usePage();
const props = defineProps({});

const authUser = computed(() => page.props.auth?.user ?? null);
const employee = computed(() => authUser.value?.employee ?? null);
const employeeData = ref (null);
const employeeVacations = ref (null);
const employeeIncidences = ref (null);
const antiguedad = ref (null);
const loading = ref(true);
const attendanceDate = ref(new Date());
const attendanceData = ref([]);

const employeePhoto = computed(() => {
    const employeeId =
        employee.value?.id ?? props.idEmployee?.[0]?.id ?? authUser.value?.id;

    return employeeId
        ? `https://nominas.grupo-ortiz.site/Librerias/img/Fotos/${employeeId}.jpg`
        : page.props.auth?.user?.profile_photo_url || "";
});


function obtenerEmpleado() {
    loading.value = true;

    let id = employee.value.id;
    axios.get(`/dashboard/show/${id}`)
        .then(response => {
            console.log('Datos del empleado:', response.data);
            employeeData.value = response.data.employee;
            employeeVacations.value = response.data.vacaciones;
            employeeIncidences.value = response.data.incidencias_empleado;
            antiguedad.value = response.data.antiguedad;
            attendanceData.value = response.data.asistencia;
        })
        .catch(error => {
            console.error('Error al obtener datos:', error);
        })
        .finally(() => {
            loading.value = false;
        });
}

function normalizeDate(date) {
    if (date instanceof Date) return date;
    return new Date(date.year, date.month, date.day);
}

function getWeekNumber(date) {
    const tempDate = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
    const dayNum = tempDate.getUTCDay() || 7;
    tempDate.setUTCDate(tempDate.getUTCDate() + 4 - dayNum);
    const yearStart = new Date(Date.UTC(tempDate.getUTCFullYear(), 0, 1));
    const weekNum = Math.ceil((((tempDate - yearStart) / 86400000) + 1) / 7);

    return {
        week: weekNum,
        year: tempDate.getUTCFullYear()
    };
}

function getDayKey(date) {
    const days = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
    return days[date.getDay()];
}

function getWeekDataByDate(date) {
    if (!Array.isArray(attendanceData.value)) return null;

    const { week, year } = getWeekNumber(date);

    return attendanceData.value.find(item =>
        Number(item.week_number) === Number(week) &&
        Number(item.week_year) === Number(year)
    ) || null;
}

function getDayStyle(rawDate) {
    const date = normalizeDate(rawDate);
    const weekData = getWeekDataByDate(date);

    if (!weekData) return {};

    const dayKey = getDayKey(date);
    const colorKey = `color_${dayKey}`;

    if (!weekData[colorKey]) return {};

    return {
        backgroundColor: weekData[colorKey],
        color: '#fff',
        borderRadius: '999px',
        fontWeight: '700'
    };
}

function getDayClass(rawDate) {
    const date = normalizeDate(rawDate);
    const weekData = getWeekDataByDate(date);

    return weekData ? 'attendance-day' : '';
}

function getDayTitle(rawDate) {
    const date = normalizeDate(rawDate);
    const weekData = getWeekDataByDate(date);

    if (!weekData) return '';

    const dayKey = getDayKey(date);
    const nameKey = `nombre_${dayKey}`;

    return weekData[nameKey] || weekData[dayKey] || '';
}

function getTooltipOptions(rawDate) {
    const date = normalizeDate(rawDate);
    const weekData = getWeekDataByDate(date);

    if (!weekData) {
        return {
            value: 'Sin registro',
            class: 'attendance-tooltip'
        };
    }

    const dayKey = getDayKey(date);
    const nameKey = `nombre_${dayKey}`;
    const code = weekData[dayKey] ?? 'N/A';
    const name = weekData[nameKey] ?? code;
    const color = weekData[`color_${dayKey}`] ?? '#64748b';

    return {
        value: `
            <div class="attendance-tooltip-content">
                <div class="attendance-tooltip-header">
                    <span class="attendance-tooltip-dot" style="background:${color}"></span>
                    <span>${name}</span>
                </div>
            </div>
        `,
        escape: false,
        class: 'attendance-tooltip'
    };
}


onMounted(() => {
    obtenerEmpleado();
});


</script>

<style>
.attendance-calendar-wrapper {
    width: 100%;
}

.attendance-calendar {
    width: 100% !important;
}

.attendance-calendar :deep(.p-datepicker) {
    width: 100% !important;
    border: none !important;
    box-shadow: none !important;
    background: transparent !important;
}

.attendance-calendar :deep(.p-datepicker-group) {
    width: 100%;
}

.attendance-calendar :deep(.p-datepicker table) {
    width: 100%;
    table-layout: fixed;
}

.attendance-calendar :deep(.p-datepicker table th) {
    text-align: center;
    padding: 0.85rem 0.35rem;
    font-size: 0.85rem;
    font-weight: 700;
    color: #475569 !important;
}

.attendance-calendar :deep(.p-datepicker table td) {
    padding: 0.45rem 0.2rem;
    text-align: center;
}

.attendance-calendar :deep(.p-datepicker-header) {
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(148, 163, 184, 0.15);
    margin-bottom: 0.75rem;
}

.attendance-calendar :deep(.p-datepicker-title) {
    font-size: 1rem;
    font-weight: 700;
    color: rgb(15 23 42);
}

.dark .attendance-calendar :deep(.p-datepicker-title) {
    color: white;
}

.attendance-day-cell {
    width: 2.35rem;
    height: 2.35rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    border-radius: 14px;
    font-weight: 600;
    transition: all 0.2s ease;
    cursor: default;
}

.attendance-day-cell:hover {
    transform: scale(1.06);
}

.attendance-day-cell.has-attendance {
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.10);
}

.attendance-calendar :deep(.p-disabled),
.attendance-calendar :deep(.p-datepicker-other-month) {
    opacity: 0.35;
}

.attendance-tooltip .p-tooltip-text {
    background: linear-gradient(135deg, #0f172a, #1e293b) !important;
    color: #fff !important;
    border-radius: 16px !important;
    padding: 0.85rem 1rem !important;
    box-shadow: 0 18px 40px rgba(2, 6, 23, 0.35) !important;
    border: 1px solid rgba(148, 163, 184, 0.18);
    font-size: 0.85rem;
    min-width: 170px;
}

.attendance-tooltip-content {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.attendance-tooltip-header {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    font-weight: 700;
    font-size: 0.9rem;
}

.attendance-tooltip-dot {
    width: 10px;
    height: 10px;
    border-radius: 999px;
    display: inline-block;
}

.attendance-tooltip-code {
    font-size: 0.78rem;
    color: rgba(255, 255, 255, 0.78);
}
.attendance-calendar :deep(.p-datepicker table thead th span) {
    font-size: 0 !important;
    position: relative;
}

.attendance-calendar :deep(.p-datepicker table thead th:nth-child(1) span::after) {
    content: "Lunes";
    font-size: 0.85rem;
}

.attendance-calendar :deep(.p-datepicker table thead th:nth-child(2) span::after) {
    content: "Martes";
    font-size: 0.85rem;
}

.attendance-calendar :deep(.p-datepicker table thead th:nth-child(3) span::after) {
    content: "Miércoles";
    font-size: 0.85rem;
}

.attendance-calendar :deep(.p-datepicker table thead th:nth-child(4) span::after) {
    content: "Jueves";
    font-size: 0.85rem;
}

.attendance-calendar :deep(.p-datepicker table thead th:nth-child(5) span::after) {
    content: "Viernes";
    font-size: 0.85rem;
}

.attendance-calendar :deep(.p-datepicker table thead th:nth-child(6) span::after) {
    content: "Sábado";
    font-size: 0.85rem;
}

.attendance-calendar :deep(.p-datepicker table thead th:nth-child(7) span::after) {
    content: "Domingo";
    font-size: 0.85rem;
}
</style>
<template>
    <AppLayout title="Dashboard">
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
            <div class="xl:col-span-12">
                <div
                    class="card border-none shadow-sm rounded-3xl"
                >
                    <div class="relative p-6 md:p-8">
                        <div class="flex flex-col xl:flex-row xl:items-center gap-6">

                            <div class="flex justify-center xl:justify-start">
                                <div
                                    class="w-24 h-24 md:w-28 md:h-28 rounded-3xl overflow-hidden ring-4 ring-white dark:ring-slate-800 shadow-lg dark:bg-slate-800 shrink-0"
                                >
                                    <img
                                        class="w-full h-full object-cover"
                                        :src="employeePhoto"
                                        @error="(e) => (e.target.src = $page.props.auth.user?.profile_photo_url)"
                                        alt="Foto de perfil"
                                    />
                                </div>
                            </div>
                            <div class="flex-1">


                                <h1 class="text-2xl md:text-3xl font-semibold">
                                    Bienvenid@, {{ employeeData?.full_name }}
                                </h1>

                                <p class="mt-2 text-sm md:text-base">
                                    Consulta tu información laboral, asistencia y accesos rápidos desde un solo lugar.
                                </p>

                                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 mt-5">
                                    <div class="rounded-2xl border border-slate-200 px-4 py-3">
                                        <div class="text-xs text-slate-500 dark:text-slate-400">
                                            Empleado
                                        </div>
                                        <p class="font-semibold">
                                            {{ employeeData?.id }}
                                        </p>
                                    </div>

                                    <div class="rounded-2xl border border-slate-200 px-4 py-3">
                                        <div class="text-xs text-slate-500 dark:text-slate-400">
                                            Puesto
                                        </div>
                                        <p class="font-semibold">
                                            {{ employeeData?.posicion }}
                                        </p>
                                    </div>

                                    <div class="rounded-2xl border border-slate-200 px-4 py-3">
                                        <div class="text-xs text-slate-500 dark:text-slate-400">
                                            Departamento
                                        </div>
                                        <p class="font-semibold">
                                            {{ employeeData?.departamento }}
                                        </p>
                                    </div>

                                    <div class="rounded-2xl border border-slate-200 px-4 py-3">
                                        <div class="text-xs text-slate-500 dark:text-slate-400">
                                            Planta
                                        </div>
                                        <p class="font-semibold">
                                            {{ employeeData?.planta }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="xl:col-span-12">
                <div class="card border-none shadow-sm rounded-3xl">
                    <div class="flex items-center justify-between flex-wrap gap-3 mb-5">
                        <div>
                            <h3 class="text-xl font-semibold mb-1">Accesos rápidos</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                Información frecuente para tu operación diaria.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <div class="rounded-3xl border border-slate-200 p-5 min-h-[150px] h-full transition-all duration-300 hover:-translate-y-1 hover:shadow-md">
                            <div class="flex items-start justify-between gap-4 h-full">
                                <div class="flex flex-col justify-between h-full w-full">
                                    <div>
                                        <h4 class="font-semibold mb-1">
                                            Antigüedad
                                        </h4>

                                        <template v-if="loading">
                                            <Skeleton width="10rem" height="2rem" class="mb-2"></Skeleton>
                                        </template>
                                        <template v-else>
                                            <p class="text-2xl md:text-3xl font-bold">
                                                {{ antiguedad }}
                                            </p>
                                        </template>
                                    </div>

                                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-3">
                                        Tiempo acumulado desde tu fecha de ingreso a la empresa.
                                    </p>
                                </div>

                                <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-blue-100 dark:bg-blue-500/10 shrink-0">
                                    <i class="pi pi-calendar text-blue-600 dark:text-blue-400 !text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-slate-200 p-5 min-h-[150px] h-full transition-all duration-300 hover:-translate-y-1 hover:shadow-md">
                            <div class="flex items-start justify-between gap-4 h-full">
                                <div class="flex flex-col justify-between h-full w-full">
                                    <div>
                                        <h4 class="font-semibold mb-1">
                                            Vacaciones
                                        </h4>

                                        <template v-if="loading">
                                            <Skeleton width="6rem" height="2rem" class="mb-2"></Skeleton>
                                        </template>
                                        <template v-else>
                                            <p class="text-2xl md:text-3xl font-bold">
                                                {{ employeeVacations?.vacaciones_disponibles }}
                                            </p>
                                        </template>
                                    </div>

                                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-3">
                                        Consulta los días disponibles o el estatus actual de tus vacaciones.
                                    </p>
                                </div>

                                <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-emerald-100 dark:bg-emerald-500/10 shrink-0">
                                    <i class="pi pi-briefcase text-emerald-600 dark:text-emerald-400 !text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-slate-200 p-5 min-h-[150px] h-full transition-all duration-300 hover:-translate-y-1 hover:shadow-md">
                            <div class="flex items-start justify-between gap-4 h-full">
                                <div class="flex flex-col justify-between h-full w-full">
                                    <div>
                                        <h4 class="font-semibold mb-1">
                                            Incidencias
                                        </h4>

                                        <template v-if="loading">
                                            <Skeleton width="5rem" height="2rem" class="mb-2"></Skeleton>
                                        </template>
                                        <template v-else>
                                            <p class="text-2xl md:text-3xl font-bold ">
                                                {{ employeeIncidences?.incidencias_empleado }}
                                            </p>
                                        </template>
                                    </div>

                                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-3">
                                        Revisa movimientos, registros o incidencias relacionadas con tu actividad.
                                    </p>
                                </div>

                                <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-orange-100 dark:bg-orange-500/10 shrink-0">
                                    <i class="pi pi-exclamation-circle text-orange-600 dark:text-orange-400 !text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- {{ attendanceData }} -->
            <div class="xl:col-span-12">
                <div class="card border-none shadow-sm rounded-3xl">
                    <div class="flex items-center justify-between flex-wrap gap-3 mb-5">
                        <div>
                            <h3 class="text-xl font-semibold mb-1">Calendario de asistencia</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                Vista rápida de tu comportamiento mensual.
                            </p>
                        </div>
                        <Tag value="Mes actual" severity="info" rounded />
                    </div>

                    <div class="attendance-calendar-wrapper">
                        <Calendar
                            v-model="attendanceDate"
                            inline
                            class="w-full attendance-calendar"
                        >
                            <template #date="slotProps">
                                <div
                                    class="attendance-day-cell"
                                    :class="{ 'has-attendance': !!getWeekDataByDate(normalizeDate(slotProps.date)) }"
                                    :style="getDayStyle(slotProps.date)"
                                    v-tooltip.top="getTooltipOptions(slotProps.date)"
                                >
                                    {{ slotProps.date.day }}
                                </div>
                            </template>
                        </Calendar>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
