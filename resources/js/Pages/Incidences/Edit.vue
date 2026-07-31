<script setup>
import { computed, onMounted, ref, watch } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { router, usePage } from "@inertiajs/vue3";
import { useToastService } from "@/Stores/toastService";

const props = defineProps({
    incidence: {
        type: Object,
        required: true,
    },
    employeeData: {
        type: Object,
        required: true,
    },
    schedules: {
        type: Array,
        required: true,
    },
    allincidences: {
        type: Array,
        required: true,
    },
});

const { showSuccess, showError } = useToastService();

const employees = ref([props.employeeData]);
const schedules = ref(props.schedules);
const blockedWeeks = ref([]);
const currentIncidenceId = computed(() => props.incidence?.id);

const employeeId = ref(props.employeeData.id);

const incidencesByEmployee = ref([]);

const sending = ref(false);

const branchOfficeId = ref(props.incidence?.branch_office_id);

const incidences = computed(
    () => incidencesByEmployee.value[employeeId.value] ?? [],
);

const loading = ref(false);

const employeeOptions = computed(() =>
    employees.value.map((e) => ({
        ...e,
        label: `(${e.id}) ${e.full_name}`,
    })),
);

// ========== UTILIDADES DE FECHA ==========
function atMidnight(d) {
    const x = new Date(d);
    x.setHours(0, 0, 0, 0);
    return x;
}

function ymd(d) {
    const x = atMidnight(d);
    const yyyy = x.getFullYear();
    const mm = String(x.getMonth() + 1).padStart(2, "0");
    const dd = String(x.getDate()).padStart(2, "0");
    return `${yyyy}-${mm}-${dd}`;
}

function parseYmdToDate(s) {
    if (s === null) return null;
    const [y, m, d] = s.split("-").map(Number);
    return new Date(y, m - 1, d);
}

function addDays(d, n) {
    const x = atMidnight(d);
    x.setDate(x.getDate() + n);
    return x;
}

// ========== VARIABLES PARA BLOQUEO POR SEMANA ==========
const minIsoWeek = ref(null);
const minIsoYear = ref(null);

function isoWeekStartDate(year, week) {
    const jan4 = new Date(year, 0, 4);
    const jan4Day = jan4.getDay() || 7;
    const mondayWeek1 = new Date(jan4);
    mondayWeek1.setDate(jan4.getDate() - (jan4Day - 1));

    const result = new Date(mondayWeek1);
    result.setDate(mondayWeek1.getDate() + (week - 1) * 7);
    result.setHours(0, 0, 0, 0);
    return result;
}

const page = usePage();
const auth = page.props.auth;

const allowedFromDate = computed(() =>
    false
        ? null
        : isoWeekStartDate(Number(minIsoYear.value), Number(minIsoWeek.value)),
);
// =======================================================
// ========== GRUPOS DE INCIDENCIAS ==========
const GROUPS = {
    TXT: new Set([23]),
    VAC: new Set([3]),
};
const SHIFT_IDS = new Set([20, 18, 19]);

const busyMap = computed(() => {
    const map = new Map();

    for (const inc of incidences.value) {
        console.log("inc", inc);
        if (inc.incidence_id === currentIncidenceId.value) {
            continue;
        }
        const start = parseYmdToDate(inc.start_date);
        const end = parseYmdToDate(inc.end_date);

        // ðŸ‘‰ Caso especial: SHIFT (solo fechas puntuales)
        if (SHIFT_IDS.has(Number(inc.id))) {
            if (start) {
                map.set(ymd(start), {
                    type: inc.type,
                    id: inc.id,
                    type_color: inc.type_color,
                });
            }

            if (end && ymd(end) !== ymd(start)) {
                map.set(ymd(end), {
                    type: inc.type,
                    id: inc.id,
                    type_color: inc.type_color,
                });
            }

            continue; // ðŸ”¥ importante: NO entrar al while
        }

        // ðŸ‘‰ Comportamiento normal (bloquear rango completo)
        const cur = atMidnight(start);
        while (cur <= end) {
            map.set(ymd(cur), {
                type: inc.type,
                id: inc.id,
                type_color: inc.type_color,
            });
            cur.setDate(cur.getDate() + 1);
        }
    }

    return map;
});

const disabledDates = computed(() => {
    const out = [];
    for (const k of busyMap.value.keys()) out.push(parseYmdToDate(k));

    if (true) {
        for (const week of blockedWeeks.value) {
            let current = parseYmdToDate(week.start_week);
            const end = parseYmdToDate(week.end_week);
            while (current <= end) {
                out.push(new Date(current));
                current = addDays(current, 1);
            }
        }
    }
    return out;
});

function isBusyDate(dateObj) {
    return busyMap.value.has(ymd(dateObj));
}

function busyType(dateObj) {
    return busyMap.value.get(ymd(dateObj))?.type ?? null;
}

function busyTypeColor(dateObj) {
    return busyMap.value.get(ymd(dateObj))?.type_color ?? null;
}

// ========== FORMULARIO ==========
const range = ref(null);
const form = ref({
    incidence_id: props.incidence?.incidence_id,
    notes: props.incidence?.comment,
    range: [
        props.incidence?.validity_from
            ? new Date(props.incidence.validity_from + "T00:00:00")
            : null,
        props.incidence?.validity_to
            ? new Date(props.incidence.validity_to + "T00:00:00")
            : null,
    ],
    singleDate: props.incidence?.validity_from
        ? new Date(props.incidence.validity_from + "T00:00:00")
        : null,
    shift_hours: null,
    available_txt_hours: 0,
    txt_hours_to_register: props.incidence?.hours_txt,
    advance_date: props.incidence?.before_date
        ? new Date(props.incidence.before_date + "T00:00:00")
        : null,
    rest_date: props.incidence?.rest_date
        ? new Date(props.incidence.rest_date + "T00:00:00")
        : null,
    schedule: props.incidence?.schedule_id,
    document: props.incidence?.document,
    document_number: props.incidence?.document_number,
    employee_id: props.incidence?.employee_id,
    days_available: null,
    branch_office_id: props.incidence?.branch_office_id,
    lastWeekNumber: null,
});

const daysCalculated = computed(() => {
    const r = form.value.range;
    if (!r?.[0] || !r?.[1]) return 0;
    const a = new Date(r[0]);
    a.setHours(0, 0, 0, 0);
    const b = new Date(r[1]);
    b.setHours(0, 0, 0, 0);
    const diff = Math.round((b - a) / (1000 * 60 * 60 * 24));
    return diff >= 0 ? diff + 1 : 0;
});

const daysEditable = ref(daysCalculated.value);
const description = ref("");
watch(daysCalculated, (newVal) => {
    daysEditable.value = newVal;
});

const incidenceUI = computed(() => {
    const id = Number(form.value.incidence_id);
    const selectedIncidence = props.allincidences.find(
        (incidence) => incidence.id === id,
    );
    description.value = selectedIncidence?.description;
    if (GROUPS.TXT.has(id)) {
        return {
            key: "TXT",
            fields: [
                "shift_hours",
                "day",
                "available_txt_hours",
                "txt_hours_to_register",
                "notes",
                "schedule",
            ],
        };
    }
    if (GROUPS.VAC.has(id)) {
        return {
            key: "VAC",
            fields: ["range", "days_to_register", "days_available", "notes"],
        };
    }
    const fields = ["notes"];
    const usesSpecialDates =
        selectedIncidence?.requires_date ||
        selectedIncidence?.requires_rest_date;

    if (usesSpecialDates) {
        if (selectedIncidence?.requires_date) fields.push("advance_date");
        if (selectedIncidence?.requires_rest_date) fields.push("rest_date");
    } else {
        fields.push("range", "days_to_register");
    }

    if (selectedIncidence?.requires_schedule) fields.push("schedule");
    if (selectedIncidence?.requires_document) fields.push("document");
    if (selectedIncidence?.requires_code) fields.push("document_number");

    return { key: "CONFIGURED", fields };
});

const advanceDateLabel = computed(() =>
    Number(form.value.incidence_id) === 19
        ? "Fecha de reposiciÃ³n"
        : "Fecha de adelanto",
);

const typeOptions = ref(
    props.allincidences.map((inc) => ({
        label: inc.name,
        value: inc.id,
    })),
);

const canSave = computed(() => {
    const id = Number(form.value.incidence_id);
    if (!id) return false;
    const ui = incidenceUI.value.key;

    const hasRange = !!form.value.range?.[0] && !!form.value.range?.[1];
    const hasDay = !!form.value.singleDate;

    if (ui === "TXT") {
        return (
            hasDay &&
            form.value.shift_hours != null &&
            form.value.txt_hours_to_register != null
        );
    }
    if (ui === "VAC") {
        return hasRange && form.value.days_available != null;
    }
    const fields = incidenceUI.value.fields;
    const folioIsValid = /^[A-Za-z0-9]{8}$/.test(
        form.value.document_number?.trim() ?? "",
    );
    return (
        (!fields.includes("range") || hasRange) &&
        (!fields.includes("advance_date") || !!form.value.advance_date) &&
        (!fields.includes("rest_date") || !!form.value.rest_date) &&
        (!fields.includes("schedule") || !!form.value.schedule) &&
        (!fields.includes("document") ||
            !!form.value.document ||
            (Number(props.incidence?.incidence_id) === id &&
                !!props.incidence?.file_path)) &&
        (!fields.includes("document_number") || folioIsValid)
    );
});

function typeLabel(type) {
    return typeOptions.value.find((x) => x.value === type)?.label ?? type;
}

const errors = ref({});

function saveIncidence() {
    form.value.employee_id = employeeId.value;
    form.value.days_to_register = daysEditable.value;
    if (form.value.incidence_id === 19) {
        if (form.value.advance_date < form.value.rest_date) {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "La fecha de reposiciÃ³n debe ser menor a la fecha de descanso",
                life: 3000,
            });
            return;
        }
    }

    if (form.value.incidence_id === 20) {
        if (form.value.advance_date > form.value.rest_date) {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "La fecha de descanso debe ser menor a la fecha de adelanto",
                life: 3000,
            });
            return;
        }
    }
    sending.value = true;
    router.put(
        route("incidences-employee.update", props.incidence.id),
        form.value,
        {
            onSuccess: () => {
                sending.value = false;
                showSuccess();
            },
            onError: (e) => {
                sending.value = false;
                showError();
                errors.value = e;
            },
        },
    );
}

const entryTime = ref(null);
const leaveTime = ref(null);

function updateShiftHours() {
    const schedule = schedules.value.find((x) => x.id === form.value.schedule);
    if (!schedule) return;
    const shiftTotalHours =
        parseInt(schedule.leave_time) - parseInt(schedule.entry_time);
    entryTime.value = schedule.entry_time;
    leaveTime.value = schedule.leave_time;
    form.value.shift_hours = shiftTotalHours;
}

const getData = () => {
    if (!employeeId.value) return;
    loading.value = true;
    axios
        .get("/incidences/employee", {
            params: { employee_id: employeeId.value },
        })
        .then((response) => {
            const employeeRows = response.data[employeeId.value] ?? [];
            const summary = employeeRows[0] ?? {};

            incidencesByEmployee.value = employeeRows.some((row) => row.id)
                ? response.data
                : {};
            form.value.available_txt_hours = summary.total_hours ?? 0;
            form.value.days_available = summary.vacations ?? 0;
        })
        .catch((error) => {
            console.error(error);
            loading.value = false;
        });
};

// ========== LÃ“GICA DE RANGO Y FECHAS OCUPADAS ==========
const minRangeDate = ref(null);
const maxRangeDate = ref(null);

function findNextBusyDate(fromDate) {
    const startKey = ymd(fromDate);
    let cur = atMidnight(fromDate);
    for (let i = 0; i < 366; i++) {
        const key = ymd(cur);
        if (key !== startKey && busyMap.value.has(key)) return new Date(cur);
        cur.setDate(cur.getDate() + 1);
    }
    return null;
}

watch(
    () => form.value.range?.[0],
    (start) => {
        maxRangeDate.value = null;
        minRangeDate.value = null;

        if (!start) return;

        // Validar que la fecha no sea anterior a la Ãºltima semana disponible
        // if (allowedFromDate.value && start < allowedFromDate.value) {
        //     showError(
        //         "No se pueden seleccionar fechas anteriores a la Ãºltima semana disponible",
        //     );
        //     form.value.range = null;
        //     return;
        // }

        if (isBusyDate(start)) {
            showError();
            form.value.range = null;
            return;
        }

        const nextBusy = findNextBusyDate(start);
        if (nextBusy) {
            maxRangeDate.value = addDays(nextBusy, -1);
        }
    },
);
// =======================================================

onMounted(() => {
    getData();

    axios
        .get("/incidences/getIncidencesDataLoad", {
            params: {
                branch_office_id: branchOfficeId.value,
            },
        })
        .then((response) => {
            blockedWeeks.value = response.data.blockedWeeks ?? [];
            if (response.data?.lastWeekNumber?.[0]) {
                minIsoWeek.value = response.data.lastWeekNumber[0].week;
                minIsoYear.value = response.data.lastWeekNumber[0].year;
                form.value.lastWeekNumber =
                    response.data.lastWeekNumber[0].week;
            }
            loading.value = false;
        })
        .catch((error) => {
            console.warn("Error cargando Ãºltima semana disponible:", error);
        });

    updateShiftHours();
});

function completeRange() {
    if (form.value.range && form.value.range[0] && !form.value.range[1]) {
        form.value.range = [form.value.range[0], form.value.range[0]];
    }
}

watch(employeeId, () => {
    range.value = null;
    form.value.notes = "";
});

watch(
    () => form.value.schedule,
    () => {
        updateShiftHours();
    },
);
</script>

<template>
    <AppLayout :title="'Editar Incidencia'">
        <div class="p-4 lg:p-6">
            <div class="grid gap-4">
                <!-- <Card>
                    <template #title>Editar Incidencia</template>
                    <template #content>
                        <div class="flex flex-col gap-3">
                            <div class="flex flex-col gap-2 w-full">
                                <label class="text-sm font-medium"
                                    >Empleado</label
                                >
                                <Select
                                    v-model="employeeId"
                                    :options="employeeOptions"
                                    optionLabel="label"
                                    optionValue="id"
                                    filter
                                    :filterFields="['id', 'full_name', 'label']"
                                    showClear
                                    placeholder="Selecciona un empleado"
                                    class="w-full"
                                    :loading="loading"
                                    :disabled="true"
                                    @change="getData()"
                                />
                            </div>
                        </div>
                    </template>
                </Card> -->
                <!-- <div class="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <template #title>Fechas no disponibles</template>
                        <template #content>
                            <Message
                                severity="warn"
                                icon="pi pi-exclamation-circle"
                                class="mb-4"
                            >
                                <div v-if="!loading">
                                    <p>
                                        La ultima semana disponible es la semana
                                        {{ minIsoWeek }} del año
                                        {{ minIsoYear }}
                                    </p>
                                </div>

                                <div v-else>
                                    <Skeleton
                                        class="warn-skeleton"
                                        width="25rem"
                                    />
                                </div>
                            </Message>
                            <DatePicker
                                inline
                                showWeek
                                :numberOfMonths="2"
                                class="w-full"
                                :manualInput="false"
                                :disabledDates="disabledDates"
                                :minDate="allowedFromDate"
                            >
                                <template #date="slotProps">
                                    <span
                                        class="day-cell"
                                        :class="{
                                            'day-busy': isBusyDate(
                                                new Date(
                                                    slotProps.date.year,
                                                    slotProps.date.month,
                                                    slotProps.date.day,
                                                ),
                                            ),
                                        }"
                                        :style="{
                                            'background-color': busyTypeColor(
                                                new Date(
                                                    slotProps.date.year,
                                                    slotProps.date.month,
                                                    slotProps.date.day,
                                                ),
                                            ),
                                        }"
                                        :title="
                                            isBusyDate(
                                                new Date(
                                                    slotProps.date.year,
                                                    slotProps.date.month,
                                                    slotProps.date.day,
                                                ),
                                            )
                                                ? `Ocupado: ${typeLabel(
                                                      busyType(
                                                          new Date(
                                                              slotProps.date.year,
                                                              slotProps.date.month,
                                                              slotProps.date.day,
                                                          ),
                                                      ),
                                                  )}`
                                                : ''
                                        "
                                    >
                                        {{ slotProps.date.day }}
                                    </span>
                                </template>
                            </DatePicker>
                        </template>
                    </Card>

                    <Card>
                        <template #title>Incidencias registradas</template>
                        <template #content>
                            <DataTable
                                :value="incidences"
                                stripedRows
                                class="w-full"
                                :paginator="true"
                                :rows="3"
                            >
                                <template #empty>
                                    <div class="flex justify-center">
                                        <span
                                            >No hay incidencias
                                            registradas</span
                                        >
                                    </div>
                                </template>
                                <Column field="type" header="Tipo">
                                    <template #body="{ data }">
                                        <Tag
                                            severity="info"
                                            :value="typeLabel(data.type)"
                                        />
                                    </template>
                                </Column>
                                <Column field="start_date" header="Inicio" />
                                <Column field="end_date" header="Fin" />
                                <Column field="notes" header="Notas" />
                            </DataTable>
                        </template>
                    </Card>
                </div> -->

                <Card>
                    <template #title>Editar incidencia</template>
                    <template #content>
                        <div class="flex flex-col gap-4">
                            <div class="grid gap-3">
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-medium"
                                        >Incidencia</label
                                    >
                                    <Select
                                        v-model="form.incidence_id"
                                        :options="typeOptions"
                                        optionLabel="label"
                                        optionValue="value"
                                        filter
                                        placeholder="Selecciona una incidencia"
                                        class="w-full"
                                        :loading="loading"
                                        :disabled="loading"
                                        translate="no"
                                    />
                                    <small class="text-gray-500">
                                        Los campos se ajustan según la
                                        incidencia.
                                    </small>
                                </div>
                                <Message
                                    severity="warn"
                                    icon="pi pi-exclamation-circle"
                                    class="mb-4"
                                >
                                    <div>
                                        <p>
                                            Puedes subir incidencias solo de la
                                            semana actual en adelante, si
                                            requieres subir incidencias de
                                            semanas anteriores puedes crear un
                                            ticket dando click
                                            <Link
                                                href="/complaints/create"
                                                class="text-yellow-700 underline font-bold"
                                            >
                                                aqui</Link
                                            >
                                        </p>
                                    </div>
                                </Message>
                                <Message
                                    v-if="description"
                                    class="mt-2"
                                    severity="info"
                                    icon="pi pi-exclamation-circle"
                                >
                                    {{ description }}
                                </Message>

                                <!-- Resumen rápido -->
                                <!-- <div class="flex flex-col gap-2">
                                    <label class="text-sm font-medium"
                                        >Resumen</label
                                    >
                                    <div class="p-3 border rounded-lg text-sm">
                                        <div>
                                            <b>Tipo de incidencia:</b>
                                            {{ incidenceUI.key }}
                                        </div>
                                        <div
                                            v-if="
                                                form.range?.[0] &&
                                                form.range?.[1]
                                            "
                                        >
                                            <b>Días a registrar:</b>
                                            {{ daysToRegister }}
                                        </div>
                                        <div v-else class="text-gray-500">
                                            Selecciona fechas para ver cálculos.
                                        </div>
                                    </div>
                                </div> -->
                            </div>

                            <Divider />
                            <Message
                                severity="warn"
                                icon="pi pi-exclamation-triangle"
                                v-if="
                                    form.available_txt_hours <= 0 &&
                                    form.incidence_id == 23
                                "
                                >No tienes horas TXT disponibles, te
                                recomendamos pedir que te carguen mas
                                horas</Message
                            >
                            <!-- Horario -->
                            <div
                                v-if="incidenceUI.fields.includes('schedule')"
                                class="flex flex-col gap-2 md:col-span-2"
                            >
                                <label class="text-sm font-medium"
                                    >Horario</label
                                >
                                <Select
                                    v-model="form.schedule"
                                    :options="schedules"
                                    optionLabel="name"
                                    optionValue="id"
                                    class="w-full"
                                    placeholder="Selecciona un horario"
                                    @update:modelValue="updateShiftHours"
                                />
                            </div>

                            <!-- Paso 2: Campos dinámicos -->
                            <div class="grid gap-3 md:grid-cols-2">
                                <!-- Horas del turno -->
                                <div
                                    v-if="
                                        incidenceUI.fields.includes(
                                            'shift_hours',
                                        )
                                    "
                                    class="flex flex-col gap-2"
                                >
                                    <label class="text-sm font-medium"
                                        >Horas del turno</label
                                    >
                                    <InputNumber
                                        v-model="form.shift_hours"
                                        class="w-full"
                                        :min="0"
                                        :max="24"
                                        disabled
                                        placeholder="Selecciona un horario"
                                    />
                                </div>

                                <!-- Días disponibles (placeholder) -->
                                <div
                                    v-if="
                                        incidenceUI.fields.includes(
                                            'days_available',
                                        )
                                    "
                                    class="flex flex-col gap-2"
                                >
                                    <label class="text-sm font-medium"
                                        >Días disponibles (antes de esta
                                        incidencia)</label
                                    >
                                    <InputText
                                        :value="form.days_available ?? '—'"
                                        class="w-full"
                                        disabled
                                    />
                                    <small class="text-gray-500"
                                        >Se obtiene según
                                        empleado/periodo.</small
                                    >
                                </div>

                                <!-- Rango / vigencia -->
                                <div
                                    v-if="incidenceUI.fields.includes('range')"
                                    class="flex flex-col gap-2"
                                >
                                    <label class="text-sm font-medium">
                                        {{
                                            incidenceUI.key === "SHIFT"
                                                ? "Vigencia"
                                                : "Vigencia (desde / hasta)"
                                        }}
                                    </label>
                                    <DatePicker
                                        v-model="form.range"
                                        selectionMode="range"
                                        :manualInput="false"
                                        showIcon
                                        iconDisplay="input"
                                        placeholder="Selecciona un rango"
                                        class="w-full"
                                        :disabledDates="disabledDates"
                                        :minDate="allowedFromDate"
                                        :maxDate="maxRangeDate"
                                        :disabled="
                                            form.available_txt_hours <= 0 &&
                                            form.incidence_id == 23
                                        "
                                        @hide="completeRange"
                                    />
                                    <small class="text-gray-500">
                                        El rango no puede incluir días ocupados.
                                    </small>
                                </div>

                                <div
                                    v-if="incidenceUI.fields.includes('day')"
                                    class="flex flex-col gap-2"
                                >
                                    <label class="text-sm font-medium">
                                        Dia de TXT
                                    </label>
                                    <DatePicker
                                        v-model="form.singleDate"
                                        :manualInput="false"
                                        showIcon
                                        iconDisplay="input"
                                        placeholder="Selecciona un dia"
                                        class="w-full"
                                        :disabledDates="disabledDates"
                                        :minDate="allowedFromDate"
                                        :maxDate="maxRangeDate"
                                    />

                                    <small class="text-gray-500">
                                        No puede incluir días ocupados.
                                    </small>
                                </div>

                                <!-- Días a registrar (calculado) -->
                                <div
                                    v-if="
                                        incidenceUI.fields.includes(
                                            'days_to_register',
                                        )
                                    "
                                    class="flex flex-col gap-2"
                                >
                                    <label class="text-sm font-medium"
                                        >Días a registrar</label
                                    >
                                    <InputText
                                        v-model="daysEditable"
                                        type="number"
                                        class="w-full"
                                    />
                                    <small class="text-gray-500"
                                        >Se calcula automáticamente con las
                                        fechas.</small
                                    >
                                </div>

                                <!-- Horas TXT disponibles -->
                                <div
                                    v-if="
                                        incidenceUI.fields.includes(
                                            'available_txt_hours',
                                        )
                                    "
                                    class="flex flex-col gap-2"
                                >
                                    <label class="text-sm font-medium"
                                        >Horas TXT disponibles</label
                                    >
                                    <InputNumber
                                        v-model="form.available_txt_hours"
                                        class="w-full"
                                        :min="0"
                                        disabled
                                    />
                                </div>

                                <!-- Horas TXT a registrar -->
                                <div
                                    v-if="
                                        incidenceUI.fields.includes(
                                            'txt_hours_to_register',
                                        )
                                    "
                                    class="flex flex-col gap-2"
                                >
                                    <label class="text-sm font-medium"
                                        >Horas TXT a registrar</label
                                    >
                                    <InputNumber
                                        v-model="form.txt_hours_to_register"
                                        class="w-full"
                                        :min="0"
                                        placeholder="0"
                                        locale="en-US"
                                        :minFractionDigits="1"
                                        :maxFractionDigits="1"
                                    />
                                </div>

                                <!-- Fecha adelanto -->
                                <div
                                    v-if="
                                        incidenceUI.fields.includes(
                                            'advance_date',
                                        )
                                    "
                                    class="flex flex-col gap-2"
                                >
                                    <label class="text-sm font-medium">{{
                                        form.incidence_id == 20
                                            ? "Fecha de Adelanto"
                                            : "Fecha de Reposición"
                                    }}</label>
                                    <DatePicker
                                        v-model="form.advance_date"
                                        :manualInput="false"
                                        showIcon
                                        class="w-full"
                                        :disabledDates="disabledDates"
                                        :minDate="allowedFromDate"
                                    />
                                </div>

                                <!-- Fecha descanso -->
                                <div
                                    v-if="
                                        incidenceUI.fields.includes('rest_date')
                                    "
                                    class="flex flex-col gap-2"
                                >
                                    <label class="text-sm font-medium"
                                        >Fecha de descanso</label
                                    >
                                    <DatePicker
                                        v-model="form.rest_date"
                                        :manualInput="false"
                                        showIcon
                                        class="w-full"
                                        :disabledDates="disabledDates"
                                        :minDate="allowedFromDate"
                                    />
                                </div>

                                <!-- Documento -->
                                <div
                                    v-if="
                                        incidenceUI.fields.includes('document')
                                    "
                                    class="flex flex-col gap-2"
                                >
                                    <label class="text-sm font-medium"
                                        >Documento comprobante</label
                                    >
                                    <FileUpload
                                        mode="basic"
                                        name="document"
                                        chooseLabel="Adjuntar"
                                        :auto="false"
                                        customUpload
                                        @select="
                                            (e) =>
                                                (form.document =
                                                    e.files?.[0] ?? null)
                                        "
                                    />
                                    <small class="text-gray-500"
                                        >PDF o imagen según aplique.</small
                                    >
                                </div>

                                <!-- Folio -->
                                <div
                                    v-if="
                                        incidenceUI.fields.includes(
                                            'document_number',
                                        )
                                    "
                                    class="flex flex-col gap-2"
                                >
                                    <label class="text-sm font-medium"
                                        >Folio</label
                                    >
                                    <InputText
                                        v-model="form.document_number"
                                        maxlength="8"
                                        class="w-full"
                                    />
                                </div>
                            </div>

                            <!-- Notas siempre al final -->
                            <div
                                v-if="incidenceUI.fields.includes('notes')"
                                class="flex flex-col gap-2"
                            >
                                <label class="text-sm font-medium">Notas</label>
                                <Textarea
                                    v-model="form.notes"
                                    rows="3"
                                    autoResize
                                />
                            </div>

                            <!-- Paso 3 -->
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <div class="text-sm text-gray-500">
                                    * Campos visibles dependen de la incidencia
                                    seleccionada.
                                </div>
                                <div class="flex justify-end">
                                    <Button
                                        label="Cancelar"
                                        @click="
                                            () => {
                                                router.get(
                                                    '/incidences-employee',
                                                );
                                            }
                                        "
                                        icon="pi pi-times"
                                        severity="secondary"
                                        :loading="sending"
                                        class="mr-5"
                                    />

                                    <Button
                                        label="Guardar"
                                        icon="pi pi-save"
                                        @click="saveIncidence"
                                        :disabled="!canSave || sending"
                                        :loading="sending"
                                    />
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.day-cell {
    display: inline-flex;
    width: 2rem;
    height: 2rem;
    align-items: center;
    justify-content: center;
    border-radius: 0.6rem;
}
.day-busy {
    font-weight: 700;
    opacity: 0.95;
}
</style>
