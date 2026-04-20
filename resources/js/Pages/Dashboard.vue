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
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { router } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";

// app.directive('tooltip', Tooltip);
const toast = useToast();
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

const vacationsVisible = ref(false);
const incidencesVisible = ref(false);
const loadingData = ref(false);
const vacationsHistory = ref([]);
const incidencesHistory = ref([]);

const modalDetails = ref(false);
const details = ref(null);

const showTermsModal = ref(false);
const loadingTerms = ref(false);

const openDetailsModal = (rawDate) => {
    const date = normalizeDate(rawDate);
    const weekData = getWeekDataByDate(date);

    if (!weekData) return;

    const dayKey = getDayKey(date);

    const englishDayMap = {
        'domingo': 'sunday', 'lunes': 'monday', 'martes': 'tuesday',
        'miercoles': 'wednesday', 'jueves': 'thursday', 'viernes': 'friday', 'sabado': 'saturday'
    };

    const englishKey = englishDayMap[dayKey];

    const rawDayData = weekData[`${englishKey}_data`];

    let parsedInfo = { Turno: 'N/A', Horario: 'N/A', Entrada: null, Salida: null, Checadas: [] };

    if (rawDayData) {
        try {
            parsedInfo = JSON.parse(rawDayData);
        } catch (e) {
            console.error("Error al parsear horario del día", e);
        }
    }

    const attendanceExtras = getDayAttendanceData(date);

    details.value = {
        employee_id: employeeData.value?.id || employee.value?.id,
        employee_name: employeeData.value?.name || 'Empleado',
        department_name: employeeData.value?.department?.name || 'General',
        week_number: weekData.week_number,
        week_year: weekData.week_year,
        incidencia: weekData[`nombre_${dayKey}`] || 'SIN REGISTRO',
        color: weekData[`color_${dayKey}`] || '#64748b',
        descripcion_incidencia: `Registro correspondiente al día ${dayKey} de la semana ${weekData.week_number}.`,

        horario: {
            Turno: parsedInfo.Turno,
            Horario: parsedInfo.Horario,
            Entrada: parsedInfo.Entrada,
            Salida: parsedInfo.Salida,
            Checadas: parsedInfo.Checadas || []
        },

        horas_dobles: attendanceExtras?.horas_dobles || 0,
        horas_triples: attendanceExtras?.horas_triples || 0,
        sunday_premium: attendanceExtras?.sunday_premium || 0,
    };

    modalDetails.value = true;
};


const openVacationsModal = async (id) => {
    if (!id) return;
    vacationsVisible.value = true;
    loadingData.value = true;

    try {
        const { data } = await axios.get(`/dashboard/vacaciones/${id}`);
        vacationsHistory.value = data;

        vacationsHistory.value = data.map(item => ({
            ...item,
            date_formatted: formatDate(item.date)
        }));
    } catch (error) {
        console.error("Error al cargar vacaciones", error);
    } finally {
        loadingData.value = false;
    }
};

const filteredVacationsHistory = computed(() => {
    if (!vacationsHistory.value) return [];
    return vacationsHistory.value.filter(item => item.amount !== 0);
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

// Lógica para Incidencias
const openIncidencesModal = async (id) => {
    if (!id) return;
    incidencesVisible.value = true;
    loadingData.value = true;

    try {
        const { data } = await axios.get(`/dashboard/incidencias/${id}`);

        incidencesHistory.value = data.map((incidence) => {
            const statusInfo = getStatus(incidence);
            return {
                ...incidence,
                status: statusInfo.text,
                status_color: statusInfo.color
            };
        });

        console.log(incidencesHistory.value);
    } catch (error) {
        console.error("Error al cargar incidencias", error);
    } finally {
        loadingData.value = false;
    }
};

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
            if (!employeeData.value.terms_condition) {
                showTermsModal.value = true;
            }
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

function getDayAttendanceData(rawDate) {
    const date = normalizeDate(rawDate);
    const weekData = getWeekDataByDate(date);

    if (!weekData) return null;

    const dayKey = getDayKey(date);

    const englishDayMap = {
        'domingo': 'sunday',
        'lunes': 'monday',
        'martes': 'tuesday',
        'miercoles': 'wednesday',
        'jueves': 'thursday',
        'viernes': 'friday',
        'sabado': 'saturday'
    };

    const englishKey = englishDayMap[dayKey];
    const rawDayData = weekData[`${englishKey}_data`];
    let extraDetails = {
        horas_dobles: 0,
        horas_triples: 0,
        sunday_premium: 0
    };

    if (rawDayData) {
        try {
            const parsed = JSON.parse(rawDayData);
            extraDetails.horas_dobles = Number(parsed['Horas dobles']) || 0;
            extraDetails.horas_triples = Number(parsed['Horas triples']) || 0;
            extraDetails.sunday_premium = Number(parsed['sunday_premium']) || 0;
        } catch (e) {
            extraDetails.horas_dobles = 0;
            extraDetails.horas_triples = 0;
            extraDetails.sunday_premium = 0;
        }
    }

    return {
        name: weekData[`nombre_${dayKey}`],
        color: weekData[`color_${dayKey}`],
        code: weekData[dayKey],
        ...extraDetails
    };
}

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },

    status: { value: null, matchMode: FilterMatchMode.EQUALS },

    incidence_name: { value: null, matchMode: FilterMatchMode.CONTAINS },
    before_date: { value: null, matchMode: FilterMatchMode.CONTAINS },
    rest_date: { value: null, matchMode: FilterMatchMode.CONTAINS },
    week_number: { value: null, matchMode: FilterMatchMode.CONTAINS },
    week_year: { value: null, matchMode: FilterMatchMode.CONTAINS },
    hours_txt: { value: null, matchMode: FilterMatchMode.CONTAINS },
    expires_at: { value: null, matchMode: FilterMatchMode.CONTAINS },
    days: { value: null, matchMode: FilterMatchMode.CONTAINS },
    comment: { value: null, matchMode: FilterMatchMode.CONTAINS },
    declined_by: { value: null, matchMode: FilterMatchMode.CONTAINS },
    deleted_by: { value: null, matchMode: FilterMatchMode.CONTAINS },
    approved_by: { value: null, matchMode: FilterMatchMode.CONTAINS },
    approved_at: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const filtersV = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    amount: { value: null, matchMode: FilterMatchMode.CONTAINS },
    seniority: { value: null, matchMode: FilterMatchMode.CONTAINS },
    date: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const showColumns = ref({
    tipo_incidencia: true,
    fecha_inicio: true,
    fecha_fin: true,
    dias: true,
    semana: false,
    año: false,
    horas_txt: false,
    aprobado_por: true,
    fecha_aprobado: true,
    fecha_creado: false,
    eliminado_por: false,
    status: true,
    numero_documento: false,
    fecha_adelanto: false,
    fecha_descanso: false,
    observaciones: false,
    fecha_rechazado: true,
    rechazado_por: true,
});

const showColumnsV = ref({
    dias: true,
    antiguedad: true,
    fecha: true,
});

const getStatus = (data) => {
    if (data.approved_at == null && data.declined_at == null && data.deleted_by == null) {
        return { color: "primary", text: "Pendiente" };
    } else if (data.approved_at != null) {
        return { color: "success", text: "Aprobado" };
    } else if (data.declined_at != null) {
        return { color: "danger", text: "Rechazado" };
    } else if (data.deleted_by != null) {
        return { color: "secondary", text: "Eliminado" };
    }
    return { color: "info", text: "Desconocido" };
};

const weeklyTotals = computed(() => {
    const year = attendanceDate.value.getFullYear();
    const month = attendanceDate.value.getMonth();

    const firstDayOfMonth = new Date(year, month, 1);
    const startOffset = firstDayOfMonth.getDay() === 0 ? 6 : firstDayOfMonth.getDay() - 1;
    const startDate = new Date(firstDayOfMonth);
    startDate.setDate(firstDayOfMonth.getDate() - startOffset);

    const totals = [];
    let currentDay = new Date(startDate);

    for (let week = 0; week < 6; week++) {
        let weekSum = { dob: 0, tri: 0, pvac: 0 };

        for (let day = 0; day < 7; day++) {
            const dayData = getDayAttendanceData(new Date(currentDay));

            if (dayData) {
                weekSum.dob += dayData.horas_dobles || 0;
                weekSum.tri += dayData.horas_triples || 0;
                weekSum.pvac += dayData.sunday_premium || 0;
            }
            currentDay.setDate(currentDay.getDate() + 1);
        }
        totals.push(weekSum);
    }

    return totals;
});

const acceptTerms = () => {
    const employeeId = employeeData.value?.id || employee.value?.id;

    if (!employeeId) {
        console.error("No se encontró el ID del empleado");
        return;
    }

    router.put(route('term-conditions.update', { term_condition: employeeId }), {}, {
        onBefore: () => {
            loadingTerms.value = true;
        },
        onSuccess: () => {
            showTermsModal.value = false;

            toast.add({
                severity: 'success',
                summary: '¡Éxito!',
                detail: 'Has aceptado los términos. Ya puedes navegar.',
                life: 4000
            });
        },
        onError: (errors) => {
            console.error(errors);
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'No se pudieron aceptar los términos.',
                life: 4000
            });
        },
        onFinish: () => {
            loadingTerms.value = false;
        },
        preserveScroll: true
    });

};

const logout = () => {
    router.post(route('logout'), {}, {
        onBefore: () => {
            loadingTerms.value = true;
        },
        onSuccess: () => {
            console.log("Sesión cerrada.");
        }
    });
};

onMounted(() => {
    obtenerEmpleado();
});
</script>

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
                                            Fecha de entrada
                                        </div>
                                        <p class="font-semibold">
                                            {{ employeeData?.entry_date }}
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

                        <div
                            @click="openVacationsModal(employee?.id)"
                            class="rounded-3xl border border-slate-200 p-5 min-h-[150px] h-full transition-all duration-300 hover:-translate-y-1 hover:shadow-md cursor-pointer active:scale-95"
                        >
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

                        <div
                            @click="openIncidencesModal(employee?.id)"
                            class="rounded-3xl border border-slate-200 p-5 min-h-[150px] h-full transition-all duration-300 hover:-translate-y-1 hover:shadow-md cursor-pointer active:scale-95"
                        >
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

                    <div class="border border-gray-300 w-full">
                        <Calendar
                            v-model="attendanceDate"
                            inline
                            @date-select="openDetailsModal"
                            class="w-full custom-calendar"
                        >
                            <template #date="{ date }">
                                <div class="attendance-day-cell">
                                    <span class="day-number">{{ date.day }}</span>

                                    <template v-if="getDayAttendanceData(date)">
                                        <div
                                            class="attendance-tag"
                                            :style="{ backgroundColor: getDayAttendanceData(date)?.color}"
                                            v-tooltip.top="getDayAttendanceData(date)?.name"
                                        >
                                            <span class="text-full">{{ getDayAttendanceData(date)?.code }}</span>
                                            <span class="text-short">{{ getDayAttendanceData(date)?.code || getDayAttendanceData(date)?.name?.substring(0, 2) }}</span>
                                        </div>

                                        <!-- <div class="extra-columns-container">
                                            <div class="col-item" :class="{ 'has-value': (getDayAttendanceData(date)?.horas_dobles || 0) > 0 }">
                                                <span class="label">Dob:</span> {{ getDayAttendanceData(date)?.horas_dobles || 0 }}
                                            </div>

                                            <div class="col-item" :class="{ 'has-value': (getDayAttendanceData(date)?.horas_triples || 0) > 0 }">
                                                <span class="label">Tri:</span> {{ getDayAttendanceData(date)?.horas_triples || 0 }}
                                            </div>

                                            <div class="col-item" :class="{ 'has-value': (getDayAttendanceData(date)?.sunday_premium || 0) > 0 }">
                                                <span class="label">PVac:</span> {{ getDayAttendanceData(date)?.sunday_premium || 0 }}
                                            </div>
                                        </div> -->
                                    </template>
                                </div>
                            </template>
                        </Calendar>
                    </div>
                </div>
            </div>
        </div>
        <Dialog
            v-model:visible="vacationsVisible"
            header="Historial de Vacaciones"
            modal
            :style="{ width: '60vw' }"
            :breakpoints="{
                '768px': '90vw',
                '640px': '100vw'
            }"
            :pt="{
                root: { class: 'm-0' }
            }"
            class="p-fluid"
        >
            <div v-if="loadingData">
                <Skeleton width="100%" height="150px"></Skeleton>
            </div>
            <div v-else>
                <DataTable
                    :value="filteredVacationsHistory"
                    dataKey="id"
                    paginator
                    :rows="5"
                    stripedRows
                    showGridlines
                    size="small"
                    v-model:filters="filtersV"
                    filterDisplay="menu"
                    :globalFilterFields="['amount', 'seniority', 'date_formatted']"
                    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} vacaciones"
                >

                    <template #header>
                        <div
                            class="flex flex-wrap gap-2 items-end justify-between mb-6"
                        >
                            <div>
                                <h4 class="m-0">Tabla de Vacaciones</h4>
                                <Tag
                                    :value="'Total: ' + employeeVacations?.vacaciones_disponibles + ' Días'"
                                    icon="pi pi-sun"
                                    severity="info"
                                />
                            </div>
                            <div class="flex">
                                <IconField>
                                    <InputIcon>
                                        <i class="pi pi-search" />
                                    </InputIcon>
                                    <InputText
                                        v-model="filtersV['global'].value"
                                        placeholder="Buscar..."
                                    />
                                </IconField>
                            </div>
                        </div>
                    </template>

                    <Column
                        field="amount"
                        header="Días"
                        :filter="true"
                        :style="{
                            width: '20rem',
                            display: showColumnsV.dias ? '' : 'none',
                        }"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading"></Skeleton>
                            <Tag v-else :value="data.amount + ' días'" severity="success" />
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText
                                v-model="filterModel.value"
                                type="text"
                                placeholder="Buscar por Días"
                            />
                        </template>
                    </Column>
                    <!-- <Column
                        field="seniority"
                        header="Antigüedad"
                        :filter="true"
                        :style="{
                            width: '20rem',
                            display: showColumnsV.antiguedad ? '' : 'none',
                        }"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading"></Skeleton>
                            <span v-else>{{ data.seniority }} años</span>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText
                                v-model="filterModel.value"
                                type="text"
                                placeholder="Buscar por Antigüedad"
                            />
                        </template>
                    </Column> -->
                    <Column
                        field="date_formatted"
                        header="Fecha"
                        sortable
                        :style="{
                            width: '20rem',
                            display: showColumnsV.fecha ? '' : 'none',
                        }"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading"></Skeleton>

                            <template v-else>
                                <i class="pi pi-calendar mr-2"></i>
                                {{ data.date_formatted }}
                            </template>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText
                                v-model="filterModel.value"
                                type="text"
                                placeholder="Buscar por Fecha"
                            />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </Dialog>

        <Dialog
            v-model:visible="incidencesVisible"
            header="Registro de Incidencias"
            modal
            :style="{ width: '60vw' }"
            :breakpoints="{
                '768px': '90vw',
                '640px': '100vw'
            }"
            :pt="{
                root: { class: 'm-0' }
            }"
            class="p-fluid"
        >
            <div v-if="loadingData">
                <Skeleton width="100%" height="150px"></Skeleton>
            </div>
            <div v-else>
                <DataTable
                    ref="dt"
                    v-model:selection="selected"
                    :value="incidencesHistory"
                    dataKey="id"
                    :paginator="true"
                    :rows="10"
                    scrollable
                    scrollHeight="400px"
                    tableStyle="min-width: 110rem"
                    v-model:filters="filters"
                    filterDisplay="menu"
                    :globalFilterFields="[
                        'status',
                        'incidence_name',
                        'before_date',
                        'rest_date',
                        'week_number',
                        'week_year',
                        'hours_txt',
                        'expires_at',
                        'days',
                        'comment',
                        'declined_by',
                        'deleted_by',
                        'approved_by',
                        'approved_at'
                    ]"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} datos de Incidencias"
                >
                    <template #header>
                        <div
                            class="flex flex-wrap gap-2 items-end justify-between mb-6"
                        >
                            <div>
                                <h4 class="m-0">Tabla de Incidencias</h4>
                            </div>
                            <div class="flex">
                                <IconField>
                                    <InputIcon>
                                        <i class="pi pi-search" />
                                    </InputIcon>
                                    <InputText
                                        v-model="filters['global'].value"
                                        placeholder="Buscar..."
                                    />
                                </IconField>
                            </div>
                        </div>
                    </template>

                    <Column
                        field="status"
                        header="Estatus"
                        :filter="true"
                        columnKey="status"
                        style="width: 5rem"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loadingData"></Skeleton>
                            <Tag
                                v-else
                                :value="data.status"
                                :severity="data.status_color"
                            />
                        </template>

                        <template #filter="{ filterModel }">
                            <Select
                                v-model="filterModel.value"
                                :options="[
                                    { label: 'Pendiente', value: 'Pendiente' },
                                    { label: 'Aprobado', value: 'Aprobado' },
                                    { label: 'Rechazado', value: 'Rechazado' },
                                    { label: 'Eliminado', value: 'Eliminado' }
                                ]"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Buscar por Estatus"
                            />
                        </template>
                    </Column>
                    <Column
                        field="incidence_name"
                        header="Incidencia"
                        :filter="true"
                        columnKey="incidence_name"
                        :style="{
                            width: '5rem',
                            display: showColumns.tipo_incidencia ? '' : 'none',
                        }"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading"></Skeleton>
                            <span v-else>{{ data.incidence_name }}</span>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText
                                v-model="filterModel.value"
                                type="text"
                                placeholder="Buscar por Incidencia"
                            />
                        </template>
                    </Column>

                    <Column
                        field="validity_from"
                        header="Fecha de inicio"
                        :filter="true"
                        filterMatchMode="equals"
                        columnKey="validity_from"
                        :style="{
                            width: '5rem',
                            display: showColumns.fecha_inicio ? '' : 'none',
                        }"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading"></Skeleton>
                            <span v-else>{{ data.validity_from }}</span>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText
                                v-model="filterModel.value"
                                type="text"
                                placeholder="Buscar por Fecha de Inicio"
                            />
                        </template>
                    </Column>
                    <Column
                        field="validity_to"
                        header="Fecha de fin"
                        :filter="true"
                        columnKey="validity_to"
                        :style="{
                            width: '5rem',
                            display: showColumns.fecha_fin ? '' : 'none',
                        }"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading"></Skeleton>
                            <span v-else>{{ data.validity_to }}</span>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText
                                v-model="filterModel.value"
                                type="text"
                                placeholder="Buscar por Fecha de Fin"
                            />
                        </template>
                    </Column>
                    <Column
                        field="days"
                        header="Días"
                        :filter="true"
                        columnKey="days"
                        :style="{
                            width: '5rem',
                            display: showColumns.dias ? '' : 'none',
                        }"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading"></Skeleton>
                            <span v-else>{{ data.days }}</span>
                        </template>
                    </Column>
                    <Column
                        field="approved_at"
                        header="Fecha de aprobación"
                        :filter="true"
                        columnKey="approved_at"
                        :style="{
                            width: '5rem',
                            display: showColumns.fecha_aprobado ? '' : 'none',
                        }"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading"></Skeleton>
                            <span v-else>{{ formatDate(data.approved_at) }}</span>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText
                                v-model="filterModel.value"
                                type="text"
                                placeholder="Buscar por Fecha de aprobación"
                            />
                        </template>
                    </Column>
                    <Column
                        field="approved_by"
                        header="Aprobado por"
                        :filter="true"
                        columnKey="approved_by"
                        :style="{
                            width: '5rem',
                            display: showColumns.aprobado_por ? '' : 'none',
                        }"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading"></Skeleton>
                            <span v-else>{{ data.approved_by }}</span>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText
                                v-model="filterModel.value"
                                type="text"
                                placeholder="Buscar por Aprobado por"
                            />
                        </template>
                    </Column>
                    <Column
                        field="declined_at"
                        header="Fecha de rechazo"
                        :filter="true"
                        columnKey="declined_at"
                        :style="{
                            width: '5rem',
                            display: showColumns.fecha_rechazado ? '' : 'none',
                        }"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading"></Skeleton>
                            <span v-else>{{ formatDate(data.declined_at) }}</span>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText
                                v-model="filterModel.value"
                                type="text"
                                placeholder="Buscar por Fecha de rechazo"
                            />
                        </template>
                    </Column>
                    <Column
                        field="declined_by"
                        header="Rechazado por"
                        :filter="true"
                        columnKey="declined_by"
                        :style="{
                            width: '5rem',
                            display: showColumns.rechazado_por ? '' : 'none',
                        }"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading"></Skeleton>
                            <span v-else>{{ data.declined_by }}</span>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText
                                v-model="filterModel.value"
                                type="text"
                                placeholder="Buscar por Rechazado por"
                            />
                        </template>
                    </Column>

                    <Column
                        field="document_number"
                        header="Número de documento"
                        :filter="true"
                        columnKey="document_number"
                        :style="{
                            width: '5rem',
                            display: showColumns.numero_documento ? '' : 'none',
                        }"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading"></Skeleton>
                            <span v-else>{{ data.document_number }}</span>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText
                                v-model="filterModel.value"
                                type="text"
                                placeholder="Buscar por Número de documento"
                            />
                        </template>
                    </Column>
                    <Column
                        field="before_date"
                        header="Fecha de adelanto"
                        :filter="true"
                        columnKey="before_date"
                        :style="{
                            width: '5rem',
                            display: showColumns.fecha_adelanto ? '' : 'none',
                        }"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading"></Skeleton>
                            <span v-else>{{ data.before_date }}</span>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText
                                v-model="filterModel.value"
                                type="text"
                                placeholder="Buscar por Fecha de adelanto"
                            />
                        </template>
                    </Column>
                    <Column
                        field="rest_date"
                        header="Fecha de descanso"
                        :filter="true"
                        columnKey="rest_date"
                        :style="{
                            width: '5rem',
                            display: showColumns.fecha_descanso ? '' : 'none',
                        }"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading"></Skeleton>
                            <span v-else>{{ data.rest_date }}</span>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText
                                v-model="filterModel.value"
                                type="text"
                                placeholder="Buscar por Fecha de descanso"
                            />
                        </template>
                    </Column>
                    <Column
                        field="created_at"
                        header="Fecha de creación"
                        :filter="true"
                        columnKey="created_at"
                        :style="{
                            width: '5rem',
                            display: showColumns.fecha_creado ? '' : 'none',
                        }"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading"></Skeleton>
                            <span v-else>{{ data.created_at }}</span>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText
                                v-model="filterModel.value"
                                type="text"
                                placeholder="Buscar por Fecha de creación"
                            />
                        </template>
                    </Column>
                    <Column
                        field="hours_txt"
                        header="Horas TXT"
                        :filter="true"
                        columnKey="hours_txt"
                        :style="{
                            width: '5rem',
                            display: showColumns.horas_txt ? '' : 'none',
                        }"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading"></Skeleton>
                            <span v-else>{{ data.hours_txt }}</span>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText
                                v-model="filterModel.value"
                                type="text"
                                placeholder="Buscar por Horas TXT"
                            />
                        </template>
                    </Column>
                    <Column
                        field="week_year"
                        header="Año"
                        :filter="true"
                        columnKey="week_year"
                        :style="{
                            width: '5rem',
                            display: showColumns.año ? '' : 'none',
                        }"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading"></Skeleton>
                            <span v-else>{{ data.week_year }}</span>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText
                                v-model="filterModel.value"
                                type="text"
                                placeholder="Buscar por Año"
                            />
                        </template>
                    </Column>
                    <Column
                        field="week_number"
                        header="Semana"
                        :filter="true"
                        columnKey="week_number"
                        :style="{
                            width: '5rem',
                            display: showColumns.semana ? '' : 'none',
                        }"
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading"></Skeleton>
                            <span v-else>{{ data.week_number }}</span>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText
                                v-model="filterModel.value"
                                type="text"
                                placeholder="Buscar por Semana"
                            />
                        </template>
                    </Column>
                    <Column
                        field="deleted_by"
                        header="Eliminado por"
                        :filter="true"
                        columnKey="comment"
                        :style="{
                            width: '5rem',
                            display: showColumns.eliminado_por ? '' : 'none',
                        }"
                        sortable
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading"></Skeleton>
                            <span v-else>{{ data.deleted_by }}</span>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText
                                v-model="filterModel.value"
                                type="text"
                                placeholder="Buscar por Eliminado por"
                            />
                        </template>
                    </Column>
                    <Column
                        field="comment"
                        header="Observaciones"
                        :filter="true"
                        columnKey="comment"
                        :style="{
                            width: '5rem',
                            display: showColumns.observaciones ? '' : 'none',
                        }"
                        sortable
                    >
                        <template #body="{ data }">
                            <Skeleton v-if="loading"></Skeleton>
                            <span v-else>{{ data.comment }}</span>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </Dialog>
        <Dialog
            v-model:visible="modalDetails"
            modal
            header="Detalle de asistencia"
            :style="{ width: '70rem', maxWidth: '95vw' }"
            dismissableMask
            class="p-dialog-custom"
        >
            <template v-if="details">
                <div class="grid grid-cols-2 gap-4 text-gray-700">
                    <div class="flex flex-col gap-4">
                        <div class="rounded-xl shadow-sm border p-4">
                            <div
                                class="flex flex-col items-center text-center gap-3"
                            >
                                <img
                                    :src="`https://nominas.grupo-ortiz.site/Librerias/img/Fotos/${details?.employee_id}.jpg`"
                                    alt="Foto"
                                    class="w-32 h-32 rounded-full object-cover border-4 border-white shadow-xl"
                                />

                                <div>
                                    <h3
                                        class="text-lg font-bold text-gray-900 uppercase tracking-wide"
                                    >
                                        ({{ details?.employee_id }})
                                        {{ details?.employee_name }}
                                    </h3>
                                    <p class="text-sm text-gray-500">
                                        {{ details?.department_name }}
                                    </p>
                                </div>
                            </div>

                            <div
                                class="grid grid-cols-3 text-sm text-gray-600 mt-3"
                            >
                                <div>
                                    <p
                                        class="text-xs font-semibold text-gray-400"
                                    >
                                        Fecha
                                    </p>
                                    <p>
                                        {{
                                            details?.horario?.Checadas?.[0]?.access_date ?? "SIN REGISTRO"
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-xs font-semibold text-gray-400"
                                    >
                                        Semana
                                    </p>
                                    <p>
                                        {{
                                            details?.week_number ??
                                            "SIN REGISTRO"
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-xs font-semibold text-gray-400"
                                    >
                                        Año
                                    </p>
                                    <p>
                                        {{
                                            details?.week_year ??
                                            "SIN REGISTRO"
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-xl shadow-sm border p-4">
                            <div class="grid grid-cols-2 text-sm">
                                <div>
                                    <p
                                        class="font-semibold text-gray-500 mb-1"
                                    >
                                        Turno
                                    </p>
                                    <p class="text-gray-800">
                                        {{
                                            details?.horario?.Turno || "N/A"
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="font-semibold text-gray-500 mb-1"
                                    >
                                        Horario
                                    </p>
                                    <p class="text-gray-800">
                                        {{
                                            details?.horario?.Horario ||
                                            "N/A"
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="font-semibold text-gray-500 mb-1"
                                    >
                                        Entrada
                                    </p>
                                    <p class="text-green-500 font-semibold">
                                        {{
                                            details?.horario?.Entrada ||
                                            "N/A"
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="font-semibold text-gray-500 mb-1"
                                    >
                                        Salida
                                    </p>
                                    <p class="text-red-500 font-semibold">
                                        {{
                                            details.horario?.Salida || "N/A"
                                        }}
                                    </p>
                                </div>
                            </div>
                            <div class="mt-2">
                                <Badge
                                    value="Turno diurno"
                                    severity="secondary"
                                    style="
                                        background: #ccc;
                                        color: #333;
                                        font-size: 0.7rem;
                                    "
                                />
                            </div>
                        </div>
                        <div class="rounded-xl shadow-sm border p-4">
                            <div class="mb-2">
                                <h4 class="font-semibold text-gray-700 text-sm mb-2">
                                    Incidencia
                                </h4>

                                <Badge
                                    :value="details?.incidencia"
                                    class="text-[10px] sm:text-xs md:text-sm px-2 py-2"
                                    :style="{
                                        backgroundColor: details?.color,
                                        color: '#fff',
                                        whiteSpace: 'normal',
                                        wordBreak: 'break-word',
                                        display: 'inline-flex',
                                        alignItems: 'center',
                                        height: 'auto',
                                        minHeight: '1.5rem',
                                        lineHeight: '1.2'
                                    }"
                                />
                            </div>
                            <p class="text-sm text-gray-600 leading-snug">
                                {{ details?.descripcion_incidencia }}
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col gap-4">

                        <div class="rounded-xl shadow-sm border p-4 flex flex-col h-full">

                            <div class="rounded-t-lg px-4 py-2 border-b text-gray-600 font-semibold">
                                Historial de checadas
                            </div>

                            <div class="p-4 flex-1 overflow-y-auto max-h-[400px]">
                                <div class="space-y-3">
                                    <div
                                        v-for="(item, index) in details?.horario?.Checadas || []"
                                        :key="index"
                                        class="flex items-start gap-3 relative"
                                    >
                                        <div
                                            class="absolute left-4 top-6 bottom-0 w-px bg-gray-200"
                                            v-if="index < (details?.horario?.Checadas?.length - 1)"
                                        ></div>

                                        <div class="w-8 h-8 flex items-center justify-center rounded-full border-purple-200 z-10">
                                            <i class="pi pi-clock text-purple-500 text-xs"></i>
                                        </div>

                                        <div class="flex-1 rounded-md p-3 border border-gray-200">
                                            <div class="flex justify-between items-center mb-1">
                                                <span class="text-xs text-gray-400">
                                                    Checada {{ index + 1 }}
                                                </span>
                                            </div>

                                            <div class="flex gap-4 text-sm text-gray-600">
                                                <div>📅 <b>{{ item?.access_date }}</b></div>
                                                <div>⏰ <b>{{ item?.access_time }}</b></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t pt-3  flex gap-2 flex-wrap">
                                <InlineMessage severity="info">
                                    Dobles: <b>{{ details?.horas_dobles || 0 }}</b>
                                </InlineMessage>

                                <InlineMessage severity="warn">
                                    Triples: <b>{{ details?.horas_triples || 0 }}</b>
                                </InlineMessage>

                                <InlineMessage severity="success">
                                    Prima Vacacional: <b>{{ details?.sunday_premium || 0 }}</b>
                                </InlineMessage>
                            </div>

                        </div>

                    </div>
                </div>
            </template>

            <template #footer>
                <Button
                    label="Cerrar"
                    icon="pi pi-times"
                    class="p-button-sm p-button-secondary"
                    @click="modalDetails = false"
                />
            </template>
        </Dialog>
        <Dialog
            v-model:visible="showTermsModal"
            header="Términos y Condiciones de Uso"
            :modal="true"
            :closable="false"
            :closeOnEscape="false"
            :draggable="false"
            class="mx-4 w-full md:w-[650px]"
        >
            <div class="flex flex-col gap-4">
                <div class="p-4 rounded-2xl flex items-start gap-3">
                    <InlineMessage severity="info">Para continuar utilizando el <strong>Mi Portal RH</strong>, es necesario que leas y aceptes los términos de confidencialidad y uso de datos.</InlineMessage>
                </div>

                <!-- <div class="flex-grow w-full bg-gray-200 dark:bg-gray-900">
                    <iframe
                        src="/path-to-your-pdf.pdf#toolbar=0"
                        class="w-full h-full border-none"
                        type="application/pdf"
                    >
                        <div class="p-10 text-center">
                            <p>Tu navegador no puede mostrar el PDF directamente.</p>
                            <a href="/path-to-your-pdf.pdf" target="_blank" class="text-blue-500 underline">Haz clic aquí para descargar el documento.</a>
                        </div>
                    </iframe>
                </div> -->

                <div class="p-6 rounded-xl border border-gray-100 dark:border-gray-700 max-h-[350px] overflow-y-auto text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                    <h3 class="font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2">
                        <i class="pi pi-shield text-emerald-500"></i>
                        Aviso de Privacidad y Confidencialidad
                    </h3>

                    <ul class="flex flex-col gap-4 list-none p-0">
                        <li class="flex gap-3">
                            <i class="pi pi-user-focus mt-1 text-blue-500"></i>
                            <span><strong>Propiedad de la Información:</strong> Reconoces que los datos personales y laborales mostrados en este portal te pertenecen. La plataforma actúa únicamente como un medio informativo para facilitar tu acceso a ellos.</span>
                        </li>

                        <li class="flex gap-3">
                            <i class="pi pi-eye-slash mt-1 text-orange-500"></i>
                            <span><strong>Finalidad No Lucrativa:</strong> Este portal tiene fines estrictamente informativos y administrativos. Se prohíbe el uso de la información aquí contenida para cualquier fin comercial o de lucro ajeno a la relación laboral.</span>
                        </li>

                        <li class="flex gap-3">
                            <i class="pi pi-comments mt-1 text-purple-500"></i>
                            <span><strong>Canales de Quejas:</strong> Contamos con un sistema de quejas y sugerencias. Te garantizamos que el proceso de reporte es <strong>completamente anónimo</strong>, diseñado para proteger tu integridad y fomentar un ambiente laboral seguro.</span>
                        </li>

                        <li class="flex gap-3">
                            <i class="pi pi-lock mt-1 text-emerald-500"></i>
                            <span><strong>Protección de Datos:</strong> Nos comprometemos a no compartir, vender ni utilizar tu información personal para fines distintos a los informativos internos de la organización.</span>
                        </li>
                    </ul>

                    <div class="mt-6 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-xs border border-blue-100 dark:border-blue-800 italic">
                        Al hacer clic en "Aceptar y continuar", confirmas que has leído y estás de acuerdo con el manejo de tus datos bajo los lineamientos anteriormente descritos.
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-end gap-3 mt-2">
                    <Button
                        label="No acepto"
                        icon="pi pi-sign-out"
                        :icon="loadingTerms ? 'pi pi-spin pi-spinner' : 'pi pi-sign-out'"
                        :loading="loadingTerms"
                        severity="danger"
                        text
                        @click="logout"
                        class="w-full sm:w-auto"
                    />
                    <Button
                        label="Aceptar y continuar"
                        icon="pi pi-check"
                        :icon="loadingTerms ? 'pi pi-spin pi-spinner' : 'pi pi-check'"
                        :loading="loadingTerms"
                        severity="success"
                        @click="acceptTerms"
                        class="w-full sm:w-auto shadow-lg shadow-emerald-500/20"
                        raised
                    />
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
<style>
.custom-calendar.p-calendar,
.custom-calendar .p-datepicker-calendar-container,
.custom-calendar table {
    width: 100% !important;
}

.custom-calendar .p-datepicker-calendar th,
.custom-calendar .p-datepicker-calendar td {
    text-align: center;
    padding: 0.5rem;
}

.custom-calendar .p-datepicker-calendar td > span,
.custom-calendar .p-datepicker-calendar td > div {
    margin: 0 auto;
}

.attendance-day-cell {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    padding: 4px 0;
    min-height: 50px;
    width: 100%;
}

.day-number {
    font-weight: bold;
    margin-bottom: 4px;
}

.custom-calendar table {
    width: 100% !important;
    table-layout: fixed !important;
    border-collapse: collapse;
}

.custom-calendar .p-datepicker-calendar td > span {
    height: auto !important;
    min-height: 60px;
    width: 100% !important;
    display: block !important;
    padding: 4px !important;
    border-radius: 4px !important;
}

.attendance-day-cell {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
}

.day-number {
    font-weight: bold;
    font-size: 0.9rem;
    margin-bottom: 4px;
}

/* Estilos Base del Tag */
.attendance-tag {
    font-size: 0.75rem;
    color: white;
    padding: 2px 4px;
    border-radius: 5px;
    width: 20%;
    text-align: center;
    font-weight: 500;
    min-height: 1.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Control de visibilidad */
.text-short { display: none; }
.text-full { display: inline; }

.text-full {
    display: inline-block;
    font-weight: bold;
    font-size: 0.8rem;
    width: 24px;
    text-align: center;
}

@media (max-width: 768px) {
    .text-full {
        display: none;
    }
    .text-short {
        display: inline;
        font-weight: bold;
        font-size: 0.8rem;
    }

    .attendance-tag {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        margin: 0 auto;
        padding: 0;
    }

    .custom-calendar .p-datepicker-calendar td {
        padding: 5px 2px !important;
    }
}

.custom-calendar table {
    table-layout: fixed !important;
    width: 100% !important;
}

.empty-cell {
    background: transparent;
    color: transparent;
}

.extra-columns-container {
    display: flex;
    flex-direction: column;
    width: 100%;
    margin-top: 5px;
    border-top: 1px solid #eee;
    padding-top: 2px;
}

.col-item {
    display: flex;
    justify-content: space-between;
    font-size: 0.65rem;
    padding: 1px 4px;
    color: #94a3b8;
    border-bottom: 1px idotted #f1f5f9;
}

.col-item.has-value {
    color: #1e293b;
    font-weight: bold;
}

.col-item .label {
    font-weight: 600;
    color: #64748b;
}

.col-item.has-value:nth-child(1) { color: #f59e0b; }
.col-item.has-value:nth-child(2) { color: #ef4444; }
.col-item.has-value:nth-child(3) { color: #8b5cf6; }

.attendance-day-cell {
    min-height: 100px;
    justify-content: flex-start;
}

/* 1. PONER GRIS LIGERO (MODO CLARO) */
.custom-calendar.p-datepicker .p-datepicker-calendar td > span.p-datepicker-day-selected,
.custom-calendar.p-datepicker .p-datepicker-calendar td > span.p-highlight,
.custom-calendar.p-datepicker .p-datepicker-calendar td > span[aria-selected="true"] {
    background-color: #f1f5f9 !important;
    color: #1e293b !important;
    box-shadow: none !important;
    outline: none !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 6px !important;
}

/* 2. AJUSTE PARA MODO OSCURO */
.dark .custom-calendar.p-datepicker .p-datepicker-calendar td > span.p-datepicker-day-selected,
.dark .custom-calendar.p-datepicker .p-datepicker-calendar td > span.p-highlight {
    background-color: rgba(255, 255, 255, 0.1) !important;
    border-color: rgba(255, 255, 255, 0.2) !important;
    color: #f8fafc !important;
}

/* 3. REPARAR EL TEXTO DE LAS ETIQUETAS EN MODO OSCURO */
.dark .custom-calendar .col-item,
.dark .custom-calendar .col-item .label,
.dark .custom-calendar .day-number {
    color: #f8fafc !important;
}

/* 4. LÍNEA DIVISORIA */
.dark .custom-calendar .extra-columns-container {
    border-top-color: rgba(255, 255, 255, 0.1) !important;
}

</style>
