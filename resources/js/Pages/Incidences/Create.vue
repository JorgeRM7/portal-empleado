<script setup>
import { computed, onMounted, ref, watch } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { router } from "@inertiajs/vue3";
import { useToastService } from "@/Stores/toastService";
import { useToast } from "primevue";

const props = defineProps({
    employeeId: {
        type: Number,
        required: true,
    },
    branchOfficeId: {
        type: Number,
        required: true,
    },
});

const { showSuccess, showError } = useToastService();
const toast = useToast();

const employees = ref([]);
const schedules = ref([]);
const allincidences = ref([]);

const employeeId = ref(props.employeeId);

const incidencesByEmployee = ref([]);

const sending = ref(false);

const incidences = computed(
    () => incidencesByEmployee.value[employeeId.value] ?? [],
);

const loading = ref(false);

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

const busyMap = computed(() => {
    const map = new Map();
    for (const inc of incidences.value) {
        const start = parseYmdToDate(inc.start_date);
        const end = parseYmdToDate(inc.end_date);
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

const range = ref(null);
const form = ref({
    incidence_id: null,
    notes: "",
    range: null,
    singleDate: null,
    shift_hours: null,
    available_txt_hours: 0,
    txt_hours_to_register: null,
    advance_date: null,
    rest_date: null,
    schedule: null,
    document: null,
    document_number: "",
    employee_id: props.employeeId,
    days_available: null,
    branch_office_id: props.branchOfficeId,
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

watch(daysCalculated, (newVal) => {
    daysEditable.value = newVal;
});

// Tus grupos por ID
const GROUPS = {
    TXT: new Set([23]),
    VAC: new Set([3]),
    SHIFT: new Set([20, 18, 19]),
    DOC: new Set([53, 10, 8, 22, 56, 5, 4, 7, 6, 49, 29, 14, 15, 13]),
};

const incidenceUI = computed(() => {
    const id = Number(form.value.incidence_id);
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
    if (GROUPS.SHIFT.has(id)) {
        return {
            key: "SHIFT",
            fields: ["advance_date", "rest_date", "schedule", "notes"],
        };
    }
    if (GROUPS.DOC.has(id)) {
        return {
            key: "DOC",
            fields: [
                "range",
                "days_to_register",
                "document",
                "document_number",
                "notes",
            ],
        };
    }
    return { key: "DEFAULT", fields: ["range", "days_to_register", "notes"] };
});

const typeOptions = ref([]);

const canSave = computed(() => {
    const id = Number(form.value.incidence_id);
    if (!id) return false;

    const ui = incidenceUI.value.key;

    if (ui === "SHIFT") {
        return (
            !!form.value.advance_date &&
            !!form.value.rest_date &&
            !!form.value.schedule
        );
    }

    // todos los que usan rango:
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
    if (ui === "DOC") {
        return (
            hasRange && !!form.value.document && !!form.value.document_number
        );
    }
    return hasRange; // DEFAULT
});

function typeLabel(type) {
    return typeOptions.value.find((x) => x.value === type)?.label ?? type;
}

function saveIncidence() {
    form.value.days_to_register = daysEditable.value;
    if (form.value.incidence_id === 23) {
        if (!form.value.singleDate) {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Por favor seleccione un día",
                life: 3000,
            });
            return;
        }
    }

    if (form.value.incidence_id === 19) {
        if (form.value.advance_date < form.value.rest_date) {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "La fecha de reposición debe ser menor a la fecha de descanso",
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
    router.post(route("incidences-employee.store"), form.value, {
        onSuccess: () => {
            sending.value = false;
            showSuccess();
        },
        onError: () => {
            sending.value = false;
            showError();
        },
    });
}

function updateShiftHours() {
    const schedule = schedules.value.find((x) => x.id === form.value.schedule);
    const shiftTotalHours =
        parseInt(schedule.leave_time) - parseInt(schedule.entry_time);
    form.value.shift_hours = shiftTotalHours;
}

const getData = async () => {
    if (!employeeId.value) return;
    loading.value = true;

    await axios
        .get("/incidences/employee", {
            params: {
                employee_id: employeeId.value,
            },
        })
        .then((response) => {
            console.log("incidencias por empleado", response.data);
            if (!response.data[employeeId.value][0].id) {
                incidencesByEmployee.value = {};
                loading.value = false;
                return;
            }
            incidencesByEmployee.value = response.data;
            loading.value = false;

            console.log(incidencesByEmployee.value[employeeId.value][0]);

            form.value.available_txt_hours =
                incidencesByEmployee.value[employeeId.value][0].total_hours;
            form.value.days_available =
                incidencesByEmployee.value[employeeId.value][0].vacations;
        })
        .catch((error) => {
            console.error(error);
            loading.value = false;
        });
};

function addDays(d, n) {
    const x = atMidnight(d);
    x.setDate(x.getDate() + n);
    return x;
}

const minRangeDate = ref(null);
const maxRangeDate = ref(null);

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

const allowedFromDate = computed(() =>
    isoWeekStartDate(Number(minIsoYear.value), Number(minIsoWeek.value)),
);

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

onMounted(async () => {
    loading.value = true;
    await axios
        .get("/incidences/getIncidencesDataLoad")
        .then(async (response) => {
            console.log(response.data);
            employees.value = response.data.employees;
            schedules.value = response.data.schedules;
            allincidences.value = response.data.allincidences;
            minIsoWeek.value = response.data.lastWeekNumber[0].week;
            minIsoYear.value = response.data.lastWeekNumber[0].year;

            typeOptions.value = allincidences.value.map((inc) => ({
                label: inc.name,
                value: inc.id,
            }));

            await getData();

            console.log(typeOptions.value);
        });
});

watch(employeeId, () => {
    range.value = null;
    form.value.notes = "";
});
</script>

<template>
    <AppLayout :title="'Crear Incidencia'">
        <Toast />
        <div class="p-4 lg:p-6">
            <div class="grid gap-4">
                <!-- <Card>
                    <template #title>Crear Incidencia</template>
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
                                    :disabled="loading"
                                    @change="getData()"
                                />
                            </div>
                        </div>
                    </template>
                </Card> -->
                <!-- <div class="grid gap-4 max-sm:grid-cols-1 lg:grid-cols-2">
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
                    <template #title>Crear incidencia</template>
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
                                    />
                                    <small class="text-gray-500">
                                        Los campos se ajustan según la
                                        incidencia.
                                    </small>
                                </div>

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
                                >No hay horas TXT disponibles, por favor carga
                                horas TXT a este empleado</Message
                            >
                            <!-- Horario -->
                            <div
                                v-if="
                                    incidenceUI.fields.includes('schedule') ||
                                    form.incidence_id == 17
                                "
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
                                    :disabled="
                                        form.available_txt_hours <= 0 &&
                                        form.incidence_id == 23
                                    "
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
                                        :disabled="
                                            form.available_txt_hours <= 0 &&
                                            form.incidence_id == 23
                                        "
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
                                        >Días disponibles</label
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
                                        :max="form.shift_hours"
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
                                        :max="form.available_txt_hours"
                                        placeholder="0"
                                        :disabled="
                                            form.available_txt_hours <= 0 &&
                                            form.incidence_id == 23
                                        "
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

                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <div class="text-sm text-gray-500">
                                    * Campos visibles dependen de la incidencia
                                    seleccionada.
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <Button
                                label="Cancelar"
                                @click="
                                    () => {
                                        router.get('/incidences-employee');
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

:deep(.warn-skeleton) {
    /* PrimeVue Skeleton usa estas variables */
    --p-skeleton-background: color-mix(
        in srgb,
        var(--p-orange-500),
        transparent 75%
    );
    --p-skeleton-animation-background: color-mix(
        in srgb,
        var(--p-orange-500),
        transparent 55%
    );
    border-radius: 0.5rem;
}
</style>
