<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { onMounted, ref } from "vue";
import { useToastService } from "@/Stores/toastService";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { router } from "@inertiajs/vue3";
import { useLayout } from "@/Layouts/composables/layout";
import { useAuthz } from "@/composables/useAuthz";

const props = defineProps({
    employees: {
        type: Array,
        required: true,
    },
    departments: {
        type: Array,
        required: true,
    },
    branchOffices: {
        type: Array,
        required: true,
    },
});

const { showSuccess, showError } = useToastService();
const { isDark } = useLayout();
const { can } = useAuthz();

const selectedBranchOffice = ref(
    JSON.parse(localStorage.getItem("selectedBranchOffice")),
);

const branch_office_id = ref(selectedBranchOffice.value.id);

const employeesByBranchOffice = ref(
    props.employees.filter(
        (employee) =>
            employee.branch_office_id === selectedBranchOffice.value.id,
    ),
);

const weekNumber = ref(getISOWeek().week);
const year = ref(getISOWeek().year);
const loading = ref(false);
const op = ref(null);
const opMostrarColumnas = ref(null);
const opFijarColumnas = ref(null);
const showConfirmDialog = ref(false);
const isOvertimeValid = ref(false);
const hours = ref(0);
const moment = ref(null);
const loadingAttendanceInfo = ref(false);
const idToValidate = ref(null);
const approveDialog = ref(false);
const txtToApprove = ref(null);
const deleteDialog = ref(false);
const txtToDelete = ref(null);
const declineDialog = ref(false);
const txtToDecline = ref(null);
const txtToUpload = ref(null);

const branchOfficeFilter = ref(branch_office_id.value);
const departmentFilter = ref(null);
const weekFilter = ref(
    `${year.value}-W${weekNumber.value < 10 ? "0" + weekNumber.value : weekNumber.value}`,
);
const statusFilter = ref(null);
const employeeFilter = ref(null);
const eliminatedFilter = ref(false);

const showColumns = ref({
    acciones: true,
    clave_empleado: true,
    nombre_empleado: true,
    semana: true,
    anio: true,
    fecha: true,
    horario: true,
    horas: true,
    aprobado_por: true,
    fecha_aprobacion: true,
    area: true,
    status: true,
    fecha_validacion: true,
});

const exportColumns = ref({
    clave_empleado: true,
    nombre_empleado: true,
    semana: true,
    anio: true,
    fecha: true,
    horario: true,
    horas: true,
    aprobado_por: true,
    fecha_aprobacion: true,
    area: true,
    status: true,
    fecha_validacion: true,
});

const frozenColumns = ref({
    acciones: true,
    clave_empleado: true,
    nombre_empleado: false,
    semana: false,
    anio: false,
    fecha: false,
    horario: false,
    horas: false,
    aprobado_por: false,
    fecha_aprobacion: false,
    area: false,
    status: false,
    fecha_validacion: false,
});

function getISOWeek(date = new Date()) {
    const d = new Date(
        Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()),
    );
    const dayNum = d.getUTCDay() || 7; // domingo = 7
    d.setUTCDate(d.getUTCDate() + 4 - dayNum);
    const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
    const weekNo = Math.ceil(((d - yearStart) / 86400000 + 1) / 7);
    return {
        year: d.getUTCFullYear(),
        week: weekNo,
        iso: `${d.getUTCFullYear()}-W${String(weekNo).padStart(2, "0")}`,
    };
}

const getStatusLabel = (approved_at, declined_at, validated_at) => {
    if (approved_at) {
        return "Aprobado";
    }
    if (validated_at) {
        return "Validado";
    }
    if (declined_at) {
        return "Rechazado";
    }
    return "Pendiente";
};

const getStatusSeverity = (approved_at, declined_at, validated_at) => {
    if (approved_at) {
        return "success";
    }
    if (validated_at) {
        return "info";
    }
    if (declined_at) {
        return "danger";
    }
    return "warning";
};

const txts = ref([{}]);
const selected = ref([]);
const attendanceData = ref(null);
const filters = ref({});
const uploadDocumentDialog = ref(false);
const columnsDialog = ref(false);
const dt = ref(null);

const toggleAccionesMasivas = (event) => {
    op.value.toggle(event);
};

const toggleMostrarColumnas = (event) => {
    opMostrarColumnas.value.toggle(event);
};

const toggleFijarColumnas = (event) => {
    opFijarColumnas.value.toggle(event);
};

const saveColumns = () => {
    columnsDialog.value = false;
    dt.value.exportCSV();
};

const otherFilters = ref([
    {
        branch_office_id: selectedBranchOffice.value.code,
        employee_id: null,
        department: null,
        includeEliminated: null,
        week: `${year.value}-W${String(weekNumber.value).padStart(2, "0")}`,
        status: null,
    },
]);

const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        employee_id: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        employee_name: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        status: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        date: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        schedule_name: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        hours: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        approved_by_name: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        approved_at: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        area: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        validated_at: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
    };
};

const fetchAttendanceInfo = async (employeeId, date) => {
    console.log(employeeId, date);

    if (employeeId && date) {
        const response = await axios.get(
            `/api/txts/search-employee-data?date=${date}&employee_id=${employeeId}`,
        );
        attendanceData.value = response.data[0];
        console.log(response.data);
    }
};

const parseTime = (timeStr) => {
    if (!timeStr) return null;
    const [hours, minutes] = timeStr.split(":").map(Number);
    const date = new Date();
    date.setHours(hours, minutes, 0, 0);
    return date;
};

const calculateOvertime = (requestedMoment = null, requestedHours = null) => {
    // 1. Verificación de seguridad
    if (!attendanceData.value) return false;

    const { entradaTeorica, salidaTeorica, entradaReal, salidaReal } =
        attendanceData.value;
    const shiftStart = parseTime(entradaTeorica);
    const shiftEnd = parseTime(salidaTeorica);
    const actualIn = parseTime(entradaReal);
    const actualOut = parseTime(salidaReal);

    if (!shiftStart || !shiftEnd || !actualIn || !actualOut) return false;

    // Ajuste por cruce de día (Turno nocturno)
    if (shiftEnd < shiftStart) shiftEnd.setDate(shiftEnd.getDate() + 1);
    if (actualOut < actualIn) actualOut.setDate(actualOut.getDate() + 1);

    // 2. Cálculos base (Minutos)
    const TOLERANCE = 10;

    let minutesBefore = (shiftStart - actualIn) / (1000 * 60);
    let minutesAfter = (actualOut - shiftEnd) / (1000 * 60);

    // Aplicamos tolerancia individualmente (Si es <= 10, cuenta como 0)
    let validMinutesBefore = minutesBefore > TOLERANCE ? minutesBefore : 0;
    let validMinutesAfter = minutesAfter > TOLERANCE ? minutesAfter : 0;

    // 3. Determinar cuántos minutos VÁLIDOS hay según el momento solicitado
    let calculatedMinutesToCheck = 0;

    if (requestedMoment === "before") {
        calculatedMinutesToCheck = validMinutesBefore;
    } else if (requestedMoment === "after") {
        calculatedMinutesToCheck = validMinutesAfter;
    } else if (requestedMoment === "both") {
        calculatedMinutesToCheck = validMinutesBefore + validMinutesAfter;
    } else {
        // Si no se especifica momento, sumamos todo lo que haya (modo automático)
        calculatedMinutesToCheck = validMinutesBefore + validMinutesAfter;
    }

    // 4. Convertir minutos reales a horas enteras (truncado)
    const rawHours = calculatedMinutesToCheck / 60;

    const systemCalculatedHours = Math.floor(rawHours * 2) / 2;

    // 5. VALIDACIÓN / RECTIFICACIÓN
    // Si nos pasaron horas específicas para validar:
    if (requestedHours !== null && requestedHours > 0) {
        // Retorna TRUE solo si lo que calculó el sistema es MAYOR o IGUAL a lo que pide el usuario
        // Ejemplo: Sistema calculó 2 horas, usuario pide 2 -> TRUE
        // Ejemplo: Sistema calculó 1 hora, usuario pide 2 -> FALSE
        console.log(
            `Validando: Pide ${requestedHours}h vs Sistema ${systemCalculatedHours}h (${requestedMoment})`,
        );
        return systemCalculatedHours >= requestedHours;
    }

    // Si no pasaron horas (uso general), retorna true si existe ALGO de tiempo extra
    return systemCalculatedHours > 0;
};

const handlePreSubmit = async (moment, employee_id, date, id, hoursTXT) => {
    loadingAttendanceInfo.value = true;
    idToValidate.value = id;
    await fetchAttendanceInfo(employee_id, date);

    const calculatedValid = calculateOvertime(moment, hoursTXT);

    hours.value = parseFloat(hoursTXT);
    isOvertimeValid.value = calculatedValid;
    showConfirmDialog.value = true;
    loadingAttendanceInfo.value = false;
};

const confirmValidate = () => {
    loading.value = true;
    router.post(
        route("txt.validate"),
        {
            id: idToValidate.value,
        },
        {
            onSuccess: () => {
                showConfirmDialog.value = false;
                showSuccess();
                applyFilters();
            },
            onError: () => {
                showConfirmDialog.value = false;
                loading.value = false;
                showError();
            },
        },
    );
};

const approveTXT = () => {
    loading.value = true;
    router.post(
        route("txt.approve"),
        {
            id: txtToApprove.value,
        },
        {
            onSuccess: () => {
                approveDialog.value = false;
                showSuccess();
                applyFilters();
            },
            onError: () => {
                approveDialog.value = false;
                loading.value = false;
                showError();
            },
        },
    );
};

const declineTXT = () => {
    loading.value = true;
    router.post(
        route("txt.decline"),
        {
            id: txtToDecline.value,
        },
        {
            onSuccess: () => {
                declineDialog.value = false;
                showSuccess();
                applyFilters();
            },
            onError: () => {
                declineDialog.value = false;
                loading.value = false;
                showError();
            },
        },
    );
};

const applyFilters = async () => {
    loading.value = true;
    otherFilterDialog.value = false;
    const selectedBranchOffice = props.branchOffices.find(
        (branchOffice) => branchOffice.id === branchOfficeFilter.value,
    );

    const departmentName = props.departments.find(
        (department) => department.id === departmentFilter.value,
    );

    console.log(selectedBranchOffice);

    otherFilters.value[0].employee_id = employeeFilter.value;
    otherFilters.value[0].week = weekFilter.value;
    otherFilters.value[0].includeEliminated = eliminatedFilter.value;
    otherFilters.value[0].branch_office_id = selectedBranchOffice?.code;
    otherFilters.value[0].department = departmentName?.name;
    otherFilters.value[0].status = statusFilter.value;

    let year = null;
    let week = null;

    if (otherFilters.value[0].week != null) {
        [year, week] = otherFilters.value[0].week.split("-W");
    }

    console.log(
        selectedBranchOffice?.id,
        week,
        year,
        statusFilter.value,
        employeeFilter.value,
        departmentFilter.value,
        eliminatedFilter.value,
    );

    await axios
        .get("/api/txts", {
            params: {
                branch_office_id: selectedBranchOffice?.id,
                week: week,
                year: year,
                status: statusFilter.value,
                employee_id: employeeFilter.value,
                department_id: departmentFilter.value,
                eliminated: eliminatedFilter.value,
            },
        })
        .then((response) => {
            txts.value = response.data;
            txts.value = response.data;
            txts.value = response.data.map((txt) => ({
                ...txt,
                status: getStatusLabel(
                    txt.approved_at,
                    txt.declined_at,
                    txt.validated_at,
                ),
            }));
        })
        .catch((error) => {
            console.error(error);
        })
        .finally(() => {
            loading.value = false;
        });
};

const removeFilter = (filter) => {
    otherFilters.value[0][filter] = null;
    switch (filter) {
        case "branch_office_id":
            branchOfficeFilter.value = null;
            break;
        case "employee_id":
            employeeFilter.value = null;
            break;
        case "week":
            weekFilter.value = null;
            break;
        case "includeEliminated":
            eliminatedFilter.value = null;
            break;
        case "department":
            departmentFilter.value = null;
            break;
        case "status":
            statusFilter.value = null;
            break;
    }
    applyFilters();
};

const clearFilter = () => {
    initFilters();
};

initFilters();

const otherFilterDialog = ref(false);

const fetchTxt = async () => {
    try {
        loading.value = true;
        const response = await axios.get("/api/txts", {
            params: {
                branch_office_id: branch_office_id.value,
                week: weekNumber.value,
                year: year.value,
                eliminated: false,
            },
        });
        console.log(response.data);
        txts.value = response.data;
        txts.value = response.data.map((txt) => ({
            ...txt,
            status: getStatusLabel(
                txt.approved_at,
                txt.declined_at,
                txt.validated_at,
            ),
        }));
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
};

const editTXT = (id) => {
    router.get(route("txt.edit", id));
};

const deleteTXT = () => {
    loading.value = true;
    router.delete(route("txt.destroy", { txt: txtToDelete.value }), {
        onSuccess: () => {
            applyFilters();
            deleteDialog.value = false;
            showSuccess();
        },
        onError: () => {
            deleteDialog.value = false;
            loading.value = false;
            showError();
        },
    });
};

const transformDate = (date) => {
    if (!date) return null;
    const newDate = new Date(date);
    const year = newDate.getUTCFullYear();
    const month = newDate.getUTCMonth() + 1;
    const day = newDate.getUTCDate();

    return `${year}-${month < 10 ? "0" + month : month}-${day < 10 ? "0" + day : day}`;
};

const onUpload = (event) => {
    const file = event.files[0];

    const formData = new FormData();
    formData.append("document", file);
    formData.append("txt_id", txtToUpload.value);

    router.post(route("txt.upload"), formData, {
        onSuccess: () => {
            applyFilters();
            uploadDocumentDialog.value = false;
            showSuccess();
        },
        onError: () => {
            uploadDocumentDialog.value = false;
            loading.value = false;
            showError();
        },
    });
};

const getDocument = (urlPath, txtId) => {
    if (urlPath.includes("txt")) {
        window.open(route("txt.download", txtId), "_blank");
        return;
    }

    window.open("https://nomina.grupo-ortiz.site" + urlPath, "_blank");
};

onMounted(() => {
    fetchTxt();
});
</script>

<template>
    <AppLayout title="Tiempo por Tiempo">
        <div class="card">
            <Toolbar>
                <template #start>
                    <Button
                        type="button"
                        icon="pi pi-upload"
                        label="Exportar"
                        severity="secondary"
                        class="mt-2 ml-2"
                        @click="columnsDialog = true"
                        v-if="can('txt.export')"
                    />
                </template>
                <template #end>
                    <Link v-if="can('txt.create')" :href="route('txt.create')">
                        <Button
                            label="Crear"
                            icon="pi pi-plus-circle"
                            severity="success"
                            class="ml-2"
                        />
                    </Link>
                </template>
            </Toolbar>
            <DataTable
                ref="dt"
                v-model:selection="selected"
                :value="txts"
                dataKey="id"
                :paginator="true"
                :rows="10"
                scrollable
                reorderableColumns
                scrollHeight="400px"
                :filterDelay="500"
                v-model:filters="filters"
                filterDisplay="menu"
                exportFilename="tiempo_por_tiempo"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} tiempos por tiempo"
            >
                <template #header>
                    <div
                        class="flex flex-wrap gap-2 items-end justify-between mb-6"
                    >
                        <div>
                            <h4 class="m-0">Tiempo por Tiempo</h4>
                            <Button
                                icon="pi pi-filter"
                                rounded
                                v-tooltip.top="'Mostrar mas filtros'"
                                @click="otherFilterDialog = true"
                            />
                            <Button
                                type="button"
                                icon="pi pi-filter-slash"
                                rounded
                                severity="secondary"
                                class="mt-5 ml-2 mr-2"
                                v-tooltip.top="'Limpiar filtros'"
                                @click="clearFilter()"
                            />
                            <Tag
                                v-if="selected.length > 0"
                                severity="info"
                                :value="'Seleccionados: ' + selected.length"
                            ></Tag>
                        </div>
                        <div class="flex">
                            <IconField>
                                <InputIcon>
                                    <i class="pi pi-search" />
                                </InputIcon>
                                <InputText
                                    v-model="filters.global.value"
                                    placeholder="Buscar..."
                                />
                            </IconField>

                            <Button
                                type="button"
                                rounded
                                class="ml-2"
                                icon="pi pi-sliders-v"
                                severity="secondary"
                                @click="toggleMostrarColumnas($event)"
                            />
                            <Button
                                icon="pi pi-lock"
                                rounded
                                v-tooltip.top="'Alternar columnas fijas'"
                                class="ml-2"
                                severity="secondary"
                                @click="toggleFijarColumnas($event)"
                            />

                            <Popover ref="opMostrarColumnas">
                                <div class="flex flex-col gap-4">
                                    <div>
                                        <span class="font-medium block mb-2"
                                            >Mostrar/Ocultar Columnas</span
                                        >
                                        <ul
                                            class="list-none p-0 m-0 flex flex-col"
                                        >
                                            <li
                                                v-for="(
                                                    value, key
                                                ) in showColumns"
                                                :key="key"
                                                class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                                                :binary="true"
                                            >
                                                <Checkbox
                                                    v-model="showColumns[key]"
                                                    :inputId="key"
                                                    :binary="true"
                                                />
                                                <label
                                                    :for="key"
                                                    class="font-medium text-base"
                                                >
                                                    {{
                                                        key
                                                            .charAt(0)
                                                            .toUpperCase() +
                                                        key.slice(1)
                                                    }}
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </Popover>
                            <Popover ref="opFijarColumnas">
                                <div class="flex flex-col gap-4">
                                    <div>
                                        <span class="font-medium block mb-2"
                                            >Fijar Columnas</span
                                        >
                                        <ul
                                            class="list-none p-0 m-0 flex flex-col"
                                        >
                                            <li
                                                v-for="(
                                                    value, key
                                                ) in frozenColumns"
                                                :key="key"
                                                class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                                                :binary="true"
                                            >
                                                <Checkbox
                                                    v-model="frozenColumns[key]"
                                                    :inputId="key"
                                                    :binary="true"
                                                />
                                                <label
                                                    :for="key"
                                                    class="font-medium text-base"
                                                >
                                                    {{
                                                        key
                                                            .charAt(0)
                                                            .toUpperCase() +
                                                        key.slice(1)
                                                    }}
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </Popover>
                        </div>
                    </div>
                    <div class="flex gap-1">
                        <div class="mb-2">
                            <Chip
                                :label="
                                    'Planta: ' +
                                    otherFilters[0].branch_office_id
                                "
                                v-if="otherFilters[0].branch_office_id != null"
                            />
                        </div>
                        <div class="mb-2">
                            <Chip
                                :label="
                                    'Empleado: ' + otherFilters[0].employee_id
                                "
                                v-if="otherFilters[0].employee_id != null"
                                removable
                                @remove="removeFilter('employee_id')"
                                :removable="!loading"
                            />
                        </div>
                        <div class="mb-2">
                            <Chip
                                :label="'Semana: ' + otherFilters[0].week"
                                v-if="otherFilters[0].week != null"
                                removable
                                @remove="removeFilter('week')"
                                :removable="!loading"
                            />
                        </div>
                        <div class="mb-2">
                            <Chip
                                :label="`Incluir eliminados: ${
                                    otherFilters[0].includeEliminated
                                        ? 'Si'
                                        : 'No'
                                }`"
                                v-if="otherFilters[0].includeEliminated"
                                removable
                                @remove="removeFilter('includeEliminated')"
                                :removable="!loading"
                            />
                        </div>
                        <div class="mb-2">
                            <Chip
                                :label="`Departamento: ${otherFilters[0].department}`"
                                v-if="otherFilters[0].department != null"
                                removable
                                @remove="removeFilter('department')"
                                :removable="!loading"
                            />
                        </div>
                        <div class="mb-2">
                            <Chip
                                :label="`Estado: ${otherFilters[0].status}`"
                                v-if="otherFilters[0].status != null"
                                removable
                                @remove="removeFilter('status')"
                                :removable="!loading"
                            />
                        </div>
                    </div>
                </template>
                <Column
                    :exportable="false"
                    columnKey="acciones"
                    :style="{
                        width: '18rem',
                        display: showColumns.acciones ? '' : 'none',
                        minWidth: '18rem',
                    }"
                    header="Acciones"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <div v-else>
                            <Button
                                icon="pi pi-pencil"
                                class="mr-2"
                                rounded
                                v-tooltip.top="'Editar'"
                                severity="warn"
                                v-if="
                                    data.approved_at == null &&
                                    data.declined_at == null &&
                                    data.deleted_by == null &&
                                    data.validated_at == null &&
                                    can('txt.edit')
                                "
                                :loading="loadingAttendanceInfo"
                                @click="editTXT(data.id)"
                            />
                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                v-tooltip.top="'Eliminar'"
                                class="mr-2"
                                rounded
                                @click="
                                    () => {
                                        txtToDelete = data.id;
                                        deleteDialog = true;
                                        console.log(txtToDelete.id);
                                    }
                                "
                                v-if="
                                    data.deleted_by == null && can('txt.delete')
                                "
                                :loading="loadingAttendanceInfo"
                            />
                            <Button
                                icon="pi pi-check"
                                severity="success"
                                v-tooltip.top="'Aprobar'"
                                class="mr-2"
                                rounded
                                v-if="
                                    data.approved_at == null &&
                                    data.declined_at == null &&
                                    data.deleted_by == null &&
                                    data.validated_at != null &&
                                    can('txt.approve')
                                "
                                @click="
                                    txtToApprove = data.id;
                                    approveDialog = true;
                                "
                                :loading="loadingAttendanceInfo"
                            />
                            <Button
                                icon="pi pi-check-circle"
                                severity="success"
                                v-tooltip.top="'Validar'"
                                class="mr-2"
                                rounded
                                v-if="
                                    data.validated_at == null &&
                                    data.approved_at == null &&
                                    data.deleted_by == null &&
                                    data.declined_at == null &&
                                    can('txt.validate')
                                "
                                @click="
                                    incidenceToValidate = data;
                                    validateDialog = true;
                                    handlePreSubmit(
                                        data.moment,
                                        data.employee_id,
                                        data.date,
                                        data.id,
                                        data.hours,
                                    );
                                "
                                :loading="loadingAttendanceInfo"
                            />
                            <Button
                                icon="pi pi-times"
                                severity="danger"
                                v-tooltip.top="'Rechazar'"
                                class="mr-2"
                                rounded
                                v-if="
                                    data.declined_at == null &&
                                    data.approved_at == null &&
                                    data.deleted_by == null &&
                                    can('txt.reject')
                                "
                                @click="
                                    txtToDecline = data.id;
                                    declineDialog = true;
                                "
                                :loading="loadingAttendanceInfo"
                            />
                            <Button
                                icon="pi pi-upload"
                                severity="help"
                                v-tooltip.top="'Subir comprobante'"
                                class="mr-2"
                                rounded
                                v-if="
                                    data.declined_at == null &&
                                    data.approved_at == null &&
                                    data.deleted_by == null &&
                                    data.validated_at == null &&
                                    data.file_path == null
                                "
                                @click="
                                    uploadDocumentDialog = true;
                                    txtToUpload = data.id;
                                "
                                :loading="loadingAttendanceInfo"
                            />
                            <Button
                                icon="pi pi-file"
                                severity="help"
                                v-tooltip.top="'Ver comprobante'"
                                class="mr-2"
                                rounded
                                v-if="data.file_path != null"
                                @click="getDocument(data.file_path, data.id)"
                                :loading="loadingAttendanceInfo"
                            />
                        </div>
                    </template>
                </Column>
                <Column
                    field="employee_id"
                    header="Clave Empleado"
                    :filter="true"
                    columnKey="clave_empleado"
                    :frozen="frozenColumns.clave_empleado"
                    :style="{
                        width: '5rem',
                        display: showColumns.clave_empleado ? '' : 'none',
                    }"
                    :exportable="exportColumns.clave_empleado"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.employee_id }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Clave Empleado"
                        />
                    </template>
                </Column>
                <Column
                    field="employee_name"
                    header="Nombre Empleado"
                    :filter="true"
                    columnKey="nombre_empleado"
                    :frozen="frozenColumns.nombre_empleado"
                    :style="{
                        width: '5rem',
                        display: showColumns.nombre_empleado ? '' : 'none',
                    }"
                    :exportable="exportColumns.nombre_empleado"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.employee_name }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Nombre Empleado"
                        />
                    </template>
                </Column>
                <Column
                    field="status"
                    header="Estado"
                    :filter="true"
                    columnKey="status"
                    :frozen="frozenColumns.status"
                    :style="{
                        width: '5rem',
                        display: showColumns.status ? '' : 'none',
                    }"
                    :exportable="exportColumns.status"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>
                            <Tag
                                :value="data.status"
                                :severity="
                                    getStatusSeverity(
                                        data.approved_at,
                                        data.declined_at,
                                        data.validated_at,
                                    )
                                "
                            />
                        </span>
                    </template>
                    <template #filter="{ filterModel, filterCallback }">
                        <Select
                            v-model="filterModel.value"
                            :options="[
                                { label: 'Pendiente', value: 'Pendiente' },
                                { label: 'Aprobado', value: 'Aprobado' },
                                { label: 'Rechazado', value: 'Rechazado' },
                                { label: 'Validado', value: 'Validado' },
                            ]"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Selecciona un estado"
                            :filter="true"
                            filterBy="label"
                            showClear
                        />
                    </template>
                </Column>
                <Column
                    field="date"
                    header="Fecha"
                    :filter="true"
                    columnKey="fecha"
                    :frozen="frozenColumns.fecha"
                    :style="{
                        width: '5rem',
                        display: showColumns.fecha ? '' : 'none',
                    }"
                    :exportable="exportColumns.fecha"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.date }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <DatePicker
                            v-model="filterModel.value"
                            type="date"
                            placeholder="Buscar fecha"
                            class="border-none w-full"
                            @update:modelValue="
                                (val) =>
                                    (filterModel.value = transformDate(val))
                            "
                        />
                    </template>
                </Column>
                <Column
                    field="schedule_name"
                    header="Horario"
                    :filter="true"
                    columnKey="horario"
                    :frozen="frozenColumns.horario"
                    :style="{
                        width: '5rem',
                        display: showColumns.horario ? '' : 'none',
                    }"
                    :exportable="exportColumns.horario"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.schedule_name }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Horario"
                        />
                    </template>
                </Column>

                <Column
                    field="hours"
                    header="Horas TXT"
                    :filter="true"
                    columnKey="horas"
                    :frozen="frozenColumns.horas"
                    :style="{
                        width: '5rem',
                        display: showColumns.horas ? '' : 'none',
                    }"
                    :exportable="exportColumns.horas"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.hours }}</span>
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
                    field="area"
                    header="Departamento"
                    :filter="true"
                    columnKey="area"
                    :frozen="frozenColumns.area"
                    :style="{
                        width: '5rem',
                        display: showColumns.area ? '' : 'none',
                    }"
                    :exportable="exportColumns.area"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.area }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <Select
                            v-model="filterModel.value"
                            :options="props.departments"
                            optionLabel="name"
                            optionValue="name"
                            placeholder="Selecciona un departamento"
                            :filter="true"
                            filterBy="name"
                            showClear
                        />
                    </template>
                </Column>
                <Column
                    field="approved_by_name"
                    header="Aprobado por"
                    :filter="true"
                    columnKey="aprobado_por"
                    :frozen="frozenColumns.aprobado_por"
                    :style="{
                        width: '5rem',
                        display: showColumns.aprobado_por ? '' : 'none',
                    }"
                    :exportable="exportColumns.aprobado_por"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.approved_by_name }}</span>
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
                    field="validated_at"
                    header="Fecha de validación"
                    :filter="true"
                    columnKey="fecha_validacion"
                    :frozen="frozenColumns.fecha_validacion"
                    :style="{
                        width: '5rem',
                        display: showColumns.fecha_validacion ? '' : 'none',
                    }"
                    :exportable="exportColumns.fecha_validacion"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{
                            transformDate(data.validated_at)
                        }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <DatePicker
                            v-model="filterModel.value"
                            type="date"
                            placeholder="Buscar fecha"
                            class="border-none w-full"
                            @update:modelValue="
                                (val) =>
                                    (filterModel.value = transformDate(val))
                            "
                        />
                    </template>
                </Column>
                <Column
                    field="approved_at"
                    header="Fecha de aprobación"
                    :filter="true"
                    columnKey="fecha_aprobacion"
                    :frozen="frozenColumns.fecha_aprobacion"
                    :style="{
                        width: '5rem',
                        display: showColumns.fecha_aprobacion ? '' : 'none',
                    }"
                    :exportable="exportColumns.fecha_aprobacion"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{
                            transformDate(data.approved_at)
                        }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <DatePicker
                            v-model="filterModel.value"
                            type="date"
                            placeholder="Buscar fecha"
                            class="border-none w-full"
                            @update:modelValue="
                                (val) =>
                                    (filterModel.value = transformDate(val))
                            "
                        />
                    </template>
                </Column>
            </DataTable>
            <Dialog
                v-model:visible="showConfirmDialog"
                modal
                header="Confirmación de Registro"
                :style="{ width: '50vw' }"
                :breakpoints="{ '960px': '75vw', '641px': '90vw' }"
            >
                <div
                    v-if="isOvertimeValid && attendanceData"
                    class="flex flex-col gap-4"
                >
                    <div
                        :class="{
                            'bg-green-50 border-l-4 border-green-500 p-4 rounded':
                                !isDark,
                            'bg-green-950 border-l-4 border-green-500 p-4 rounded':
                                isDark,
                        }"
                    >
                        <div class="flex items-center">
                            <i
                                class="pi pi-check-circle text-green-600 text-2xl mr-3"
                            ></i>
                            <div>
                                <h3
                                    :class="{
                                        'font-bold text-green-800': !isDark,
                                        'font-bold text-green-200': isDark,
                                    }"
                                >
                                    Validación Exitosa
                                </h3>
                                <p
                                    :class="{
                                        'text-sm text-green-700': !isDark,
                                        'text-sm text-green-300': isDark,
                                    }"
                                >
                                    Las checadas coinciden con el tiempo extra
                                    solicitado.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-2 gap-4 text-sm mt-2 border p-3 rounded-lg card mb-0"
                    >
                        <div>
                            <span class="block font-bold">Horario Turno</span>
                            <span class="text-lg">
                                {{ attendanceData.entradaTeorica }} -
                                {{ attendanceData.salidaTeorica }}
                            </span>
                        </div>
                        <div>
                            <span class="block font-bold"
                                >Horario Checadas</span
                            >
                            <span class="text-lg">
                                {{ attendanceData.entradaReal }} -
                                {{ attendanceData.salidaReal }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <p class="text-lg font-medium">
                            ¿Estás seguro de <strong>aprobar</strong> este TXT
                            por
                            <span class="text-green-600">{{ hours }} horas</span
                            >?
                        </p>
                    </div>
                </div>

                <div v-else class="flex flex-col gap-4">
                    <div
                        :class="{
                            'bg-orange-50 border-l-4 border-orange-500 p-4 rounded':
                                !isDark,
                            'bg-orange-950 border-l-4 border-orange-500 p-4 rounded':
                                isDark,
                        }"
                    >
                        <div class="flex items-center">
                            <i
                                class="pi pi-exclamation-triangle text-orange-600 text-3xl mr-3"
                            ></i>
                            <div>
                                <h3
                                    :class="{
                                        'font-bold text-orange-800': !isDark,
                                        'font-bold text-orange-200': isDark,
                                    }"
                                >
                                    Sin respaldo de reloj
                                </h3>
                                <p
                                    :class="{
                                        'text-sm text-orange-700': !isDark,
                                        'text-sm text-orange-300': isDark,
                                    }"
                                >
                                    El sistema no detecta horas extra válidas en
                                    el momento seleccionado.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 px-4">
                        <p class="text-lg text-gray-700">
                            ¿Estás seguro de
                            <strong>validar este registro manualmente</strong>
                            sin las checadas correspondientes?
                        </p>
                    </div>
                </div>

                <template #footer>
                    <Button
                        label="Cancelar"
                        icon="pi pi-times"
                        text
                        @click="showConfirmDialog = false"
                        class="p-button-secondary"
                    />
                    <Button
                        :label="
                            isOvertimeValid
                                ? 'Aprobar y Guardar'
                                : 'Forzar Guardado'
                        "
                        :icon="
                            isOvertimeValid
                                ? 'pi pi-check'
                                : 'pi pi-exclamation-circle'
                        "
                        :severity="isOvertimeValid ? 'success' : 'warning'"
                        @click="confirmValidate"
                        autofocus
                    />
                </template>
            </Dialog>
            <Dialog
                v-model:visible="approveDialog"
                :style="{ width: '500px' }"
                header="Confirmar aprobación"
                :modal="true"
            >
                <div
                    :class="{
                        'bg-green-50 border-l-4 border-green-500 p-4 rounded':
                            !isDark,
                        'bg-green-950 border-l-4 border-green-500 p-4 rounded':
                            isDark,
                    }"
                >
                    <div class="flex items-center">
                        <i
                            class="pi pi-check-circle text-green-600 text-2xl mr-3"
                        ></i>
                        <div>
                            <h3
                                :class="{
                                    'font-bold text-green-800': !isDark,
                                    'font-bold text-green-200': isDark,
                                }"
                            >
                                Estas seguro de aprobar este TXT?
                            </h3>
                            <p
                                :class="{
                                    'text-sm text-green-700': !isDark,
                                    'text-sm text-green-300': isDark,
                                }"
                            >
                                Esta acción aprobará el TXT y pasará a estado
                                "Aprobado".
                            </p>
                        </div>
                    </div>
                </div>
                <template #footer>
                    <Button
                        label="No"
                        icon="pi pi-times"
                        text
                        @click="approveDialog = false"
                        severity="secondary"
                        variant="text"
                        :loading="loading"
                    />
                    <Button
                        label="Si"
                        icon="pi pi-check"
                        @click="approveTXT"
                        severity="success"
                        :loading="loading"
                    />
                </template>
            </Dialog>

            <Dialog
                v-model:visible="deleteDialog"
                :style="{ width: '550px' }"
                header="Confirmar eliminación"
                :modal="true"
            >
                <div
                    :class="{
                        'bg-red-50 border-l-4 border-red-500 p-4 rounded':
                            !isDark,
                        'bg-red-950 border-l-4 border-red-500 p-4 rounded':
                            isDark,
                    }"
                >
                    <div class="flex items-center">
                        <i
                            class="pi pi-exclamation-triangle text-red-600 text-3xl mr-3"
                        ></i>
                        <div>
                            <h3
                                :class="{
                                    'font-bold text-red-800': !isDark,
                                    'font-bold text-red-200': isDark,
                                }"
                            >
                                ¿Estás seguro de eliminar este TXT?
                            </h3>
                            <p
                                :class="{
                                    'text-sm text-red-700': !isDark,
                                    'text-sm text-red-300': isDark,
                                }"
                            >
                                Esta acción eliminará el TXT y no podrá ser
                                deshecha.
                            </p>
                        </div>
                    </div>
                </div>
                <template #footer>
                    <Button
                        label="No"
                        icon="pi pi-times"
                        text
                        @click="deleteDialog = false"
                        severity="secondary"
                        variant="text"
                        :loading="loading"
                    />
                    <Button
                        label="Si"
                        icon="pi pi-check"
                        @click="deleteTXT"
                        severity="danger"
                        :loading="loading"
                    />
                </template>
            </Dialog>
            <Dialog
                v-model:visible="declineDialog"
                :style="{ width: '550px' }"
                header="Confirmar rechazo"
                :modal="true"
            >
                <div
                    :class="{
                        'bg-red-50 border-l-4 border-red-500 p-4 rounded':
                            !isDark,
                        'bg-red-950 border-l-4 border-red-500 p-4 rounded':
                            isDark,
                    }"
                >
                    <div class="flex items-center">
                        <i
                            class="pi pi-exclamation-triangle text-red-600 text-3xl mr-3"
                        ></i>
                        <div>
                            <h3
                                :class="{
                                    'font-bold text-red-800': !isDark,
                                    'font-bold text-red-200': isDark,
                                }"
                            >
                                ¿Estás seguro de rechazar este TXT?
                            </h3>
                            <p
                                :class="{
                                    'text-sm text-red-700': !isDark,
                                    'text-sm text-red-300': isDark,
                                }"
                            >
                                Esta acción rechazará el TXT y no podrá ser
                                deshecha.
                            </p>
                        </div>
                    </div>
                </div>
                <template #footer>
                    <Button
                        label="No"
                        icon="pi pi-times"
                        text
                        @click="declineDialog = false"
                        severity="secondary"
                        variant="text"
                        :loading="loading"
                    />
                    <Button
                        label="Si"
                        icon="pi pi-check"
                        @click="declineTXT"
                        severity="danger"
                        :loading="loading"
                    />
                </template>
            </Dialog>
            <Dialog
                v-model:visible="otherFilterDialog"
                :style="{ width: '450px' }"
                header="Seleccionar filtros adicionales"
                :modal="true"
            >
                <div class="flex flex-wrap -mx-2">
                    <!-- Planta -->
                    <div class="w-full md:w-1/2 px-2 mb-4">
                        <label class="block mb-2 font-medium">Planta</label>
                        <Select
                            v-model="branchOfficeFilter"
                            :options="props.branchOffices"
                            optionLabel="code"
                            optionValue="id"
                            placeholder="Selecciona una planta"
                            class="w-full"
                            filter
                            filterBy="code"
                        />
                    </div>

                    <!-- Departamento -->
                    <div class="w-full md:w-1/2 px-2 mb-4">
                        <label class="block mb-2 font-medium"
                            >Departamento</label
                        >
                        <Select
                            v-model="departmentFilter"
                            :options="props.departments"
                            optionLabel="name"
                            optionValue="id"
                            showClear
                            filter
                            placeholder="Selecciona un departamento"
                            class="w-full"
                        />
                    </div>

                    <!-- Semana (type=week => AAAA-WSS) -->
                    <div class="w-full md:w-1/2 px-2 mb-4">
                        <label class="block mb-2 font-medium">Semana</label>
                        <InputText
                            v-model="weekFilter"
                            type="week"
                            class="w-full"
                        />
                    </div>

                    <div class="w-full md:w-1/2 px-2 mb-4">
                        <label class="block mb-2 font-medium">Estado</label>
                        <Select
                            v-model="statusFilter"
                            :options="[
                                { label: 'Pendiente', value: 'pendiente' },
                                { label: 'Aprobado', value: 'aprobado' },
                                { label: 'Rechazado', value: 'rechazado' },
                                { label: 'Validado', value: 'validado' },
                            ]"
                            optionValue="value"
                            optionLabel="label"
                            showClear
                            placeholder="Selecciona un estado"
                            class="w-full"
                        >
                            <template #option="{ option }">
                                {{ option.label }}
                            </template>
                        </Select>
                    </div>

                    <!-- Empleado (buscable)-->
                    <div class="w-full md:w-1/2 px-2 mb-4">
                        <label class="block mb-2 font-medium">Empleado</label>
                        <Select
                            v-model="employeeFilter"
                            :options="employeesByBranchOffice"
                            optionValue="id"
                            optionLabel="full_name"
                            filter
                            :filterFields="['id', 'full_name']"
                            showClear
                            placeholder="Selecciona un empleado"
                            class="w-full"
                        >
                            <template #option="{ option }">
                                ({{ option.id }}) {{ option.full_name }}
                            </template>
                        </Select>
                    </div>

                    <!-- Ver registros eliminados -->
                    <!-- <div class="w-full md:w-1/2 px-2 mb-4 flex items-end">
                        <div class="flex items-center gap-2">
                            <Checkbox
                                v-model="eliminatedFilter"
                                :binary="true"
                                inputId="show_deleted"
                            />
                            <label for="show_deleted" class="font-medium"
                                >Ver registros eliminados</label
                            >
                        </div>
                    </div> -->
                </div>

                <template #footer>
                    <Button
                        label="Cancelar"
                        icon="pi pi-times"
                        severity="danger"
                        @click="otherFilterDialog = false"
                    />
                    <Button
                        label="Filtrar"
                        icon="pi pi-filter"
                        severity="warn"
                        @click="applyFilters"
                        :loading="loading"
                    />
                </template>
            </Dialog>
            <Dialog
                v-model:visible="uploadDocumentDialog"
                :style="{ width: '550px' }"
                header="Subir comprobante"
                :modal="true"
            >
                <FileUpload
                    name="document"
                    accept="image/*, .pdf"
                    :maxFileSize="1000000"
                    :fileLimit="1"
                    multiple
                    @uploader="onUpload"
                    customUpload
                >
                    <template #content="{ files, removeFileCallback }">
                        <div
                            v-if="files.length > 0"
                            class="flex flex-wrap p-0 sm:p-5 gap-5"
                        >
                            <div
                                v-for="(file, index) of files"
                                :key="file.name + file.size"
                                class="card m-0 px-6 flex flex-column border-1 surface-border align-items-center gap-3"
                            >
                                <div>
                                    <img
                                        v-if="file.type.startsWith('image/')"
                                        role="presentation"
                                        :alt="file.name"
                                        :src="file.objectURL"
                                        width="100"
                                        height="50"
                                        style="object-fit: cover"
                                    />
                                    <i
                                        v-else-if="
                                            file.type === 'application/pdf'
                                        "
                                        class="pi pi-file-pdf text-6xl text-red-500"
                                    ></i>
                                    <i
                                        v-else
                                        class="pi pi-file text-6xl text-gray-500"
                                    ></i>
                                </div>

                                <span class="font-semibold">{{
                                    file.name
                                }}</span>

                                <Button
                                    icon="pi pi-times"
                                    @click="removeFileCallback(index)"
                                    outlined
                                    rounded
                                    severity="danger"
                                />
                            </div>
                        </div>
                    </template>

                    <template #empty>
                        <div
                            class="flex align-items-center justify-content-center flex-column"
                        >
                            <i
                                class="pi pi-cloud-upload circle p-5 text-8xl text-400 border-400"
                            />
                            <p class="mt-4 mb-0">
                                Arrastra y suelta archivos aquí.
                            </p>
                        </div>
                    </template>
                </FileUpload>
            </Dialog>
            <Dialog
                v-model:visible="columnsDialog"
                :style="{ width: '450px' }"
                header="Seleccionar columnas a exportar"
                :modal="true"
            >
                <div class="flex flex-col gap-6">
                    <div
                        v-for="(value, key) in exportColumns"
                        :key="key"
                        class="flex align-items-center gap-3"
                    >
                        <Checkbox
                            v-model="exportColumns[key]"
                            :inputId="key"
                            :binary="true"
                        />
                        <label :for="key" class="font-medium text-base">{{
                            key.charAt(0).toUpperCase() + key.slice(1)
                        }}</label>
                    </div>
                </div>

                <template #footer>
                    <Button
                        label="Cancelar"
                        icon="pi pi-times"
                        severity="danger"
                        @click="columnsDialog = false"
                    />
                    <Button
                        label="Exportar"
                        icon="pi pi-save"
                        severity="success"
                        @click="saveColumns"
                    />
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>
