<script setup>
import { ref } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { router } from "@inertiajs/vue3";
import { useToast } from "primevue";
import * as xlsx from "xlsx";
import { useAuthz } from "@/composables/useAuthz";

const toast = useToast();

const props = defineProps({
    branchOffices: Array,
});

const { can } = useAuthz();

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

const diferencesResume = ref([{}]);
const filters = ref();
const year = ref(getISOWeek().year);
const weekNumber = ref(getISOWeek().week);
const selectedBranchOffice = ref(
    JSON.parse(localStorage.getItem("selectedBranchOffice")),
);
const loading = ref(false);
const selected = ref([]);
const op = ref();
const opMostrarColumnas = ref();
const opFijarColumnas = ref();

const otherFilterDialog = ref(false);
const branchOfficeFilter = ref(selectedBranchOffice.value.id);
const weekFilter = ref(
    `${year.value}-W${String(weekNumber.value).padStart(2, "0")}`,
);

const displayDialog = ref(false);
const groupedNomina = ref([]);
const groupedEstimacion = ref([]);
const loadingDetails = ref(false);
const columnsDialog = ref(false);
const columnsPdfDialog = ref(false);

const otherFilters = ref([
    {
        branch_office_id: selectedBranchOffice.value.code,
        week: `${year.value}-W${String(weekNumber.value).padStart(2, "0")}`,
    },
]);

const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        semana: {
            operator: FilterOperator.AND,
            constraints: [
                { value: null, matchMode: FilterMatchMode.STARTS_WITH },
            ],
        },
        puesto: {
            operator: FilterOperator.AND,
            constraints: [
                { value: null, matchMode: FilterMatchMode.STARTS_WITH },
            ],
        },
        costo_max_por_hora: {
            operator: FilterOperator.AND,
            constraints: [
                { value: null, matchMode: FilterMatchMode.STARTS_WITH },
            ],
        },
        horas_extra_autorizadas: {
            operator: FilterOperator.AND,
            constraints: [
                { value: null, matchMode: FilterMatchMode.STARTS_WITH },
            ],
        },
        importe_autorizado: {
            operator: FilterOperator.AND,
            constraints: [
                { value: null, matchMode: FilterMatchMode.STARTS_WITH },
            ],
        },
        importe_pagado: {
            operator: FilterOperator.AND,
            constraints: [
                { value: null, matchMode: FilterMatchMode.STARTS_WITH },
            ],
        },
        importe_diferencia: {
            operator: FilterOperator.AND,
            constraints: [
                { value: null, matchMode: FilterMatchMode.STARTS_WITH },
            ],
        },
        diferencia_importe: {
            operator: FilterOperator.AND,
            constraints: [
                { value: null, matchMode: FilterMatchMode.STARTS_WITH },
            ],
        },
        diferencia_horas: {
            operator: FilterOperator.AND,
            constraints: [
                { value: null, matchMode: FilterMatchMode.STARTS_WITH },
            ],
        },
    };
};

const showColumns = ref({
    acciones: true,
    semana: true,
    puesto: true,
    costo_max_por_hora: true,
    horas_extra_autorizadas: true,
    importe_autorizado: true,
    importe_pagado: true,
    importe_diferencia: true,
    diferencia_importe: true,
    diferencia_horas: true,
    horas_extra_trabajadas: true,
});

const frozenColumns = ref({
    acciones: false,
    semana: false,
    puesto: false,
    costo_max_por_hora: false,
    horas_extra_autorizadas: false,
    importe_autorizado: false,
    importe_pagado: false,
    importe_diferencia: false,
    diferencia_importe: false,
    diferencia_horas: false,
    horas_extra_trabajadas: false,
});

const exportColumns = ref({
    semana: true,
    puesto: true,
    costo_max_por_hora: true,
    horas_extra_autorizadas: true,
    importe_autorizado: true,
    horas_extra_trabajadas: true,
    importe_pagado: true,
    diferencia_importe: true,
    diferencia_horas: true,
});

const columnMap = {
    semana: { field: "week", header: "Semana" },
    puesto: { field: "name", header: "Puesto" },
    costo_max_por_hora: { field: "daily_salary", header: "Costo Max Por Hora" },
    horas_extra_autorizadas: {
        field: "total_overtime_estimate",
        header: "Horas Extra Autorizadas",
    },
    importe_autorizado: { field: "hours_to_pay", header: "Importe Autorizado" },
    horas_extra_trabajadas: {
        field: "total_overtimes",
        header: "Horas Extra Trabajadas",
    },
    importe_pagado: { field: "total_paid_overtimes", header: "Importe Pagado" },
    diferencia_horas: {
        field: "diference_hours",
        header: "Diferencia en Horas Extra",
    },
    diferencia_importe: { field: "diference", header: "Diferencia en Importe" },
};

const toggleAccionesMasivas = (event) => {
    op.value.toggle(event);
};

const toggleMostrarColumnas = (event) => {
    opMostrarColumnas.value.toggle(event);
};

const toggleFijarColumnas = (event) => {
    opFijarColumnas.value.toggle(event);
};

const getDailySalaryAndHoursToPay = (
    salary,
    doubleHours,
    tripleHours,
    dailySalaryEmployee,
) => {
    const dailySalary = Math.floor((salary / 8) * 100) / 100;
    const doubleHoursToPay = doubleHours * dailySalary * 2;
    const tripleHoursToPay = tripleHours * dailySalary * 3;
    const hoursToPay =
        Math.floor((doubleHoursToPay + tripleHoursToPay) * 100) / 100;
    const dailySalaryEmployeeFixed =
        Math.floor((dailySalaryEmployee / 8) * 100) / 100;
    const totalPaidOvertimes =
        Math.floor(
            (dailySalaryEmployeeFixed * doubleHours * 2 +
                dailySalaryEmployeeFixed * tripleHours * 3) *
                100,
        ) / 100;
    const diference = Math.floor((hoursToPay - totalPaidOvertimes) * 100) / 100;
    return { dailySalary, hoursToPay, totalPaidOvertimes, diference };
};

const getDiferencesHours = (total_overtime_estimate, total_overtimes) => {
    return total_overtime_estimate - total_overtimes;
};

const applyFilters = async () => {
    loading.value = true;
    otherFilterDialog.value = false;
    const selectedBranchOfficeName = props.branchOffices.find(
        (branchOffice) => branchOffice.id === branchOfficeFilter.value,
    );
    otherFilters.value[0].branch_office_id = selectedBranchOfficeName.code;
    otherFilters.value[0].week = weekFilter.value;

    await axios
        .get("/api/diferences-resume", {
            params: {
                week_year: weekFilter.value,
                planta: branchOfficeFilter.value,
            },
        })
        .then((response) => {
            diferencesResume.value = response.data;
            diferencesResume.value = response.data.map((item) => ({
                ...item,
                week: getWeek(item.week).week,
                daily_salary: getDailySalaryAndHoursToPay(
                    item.daily_salary,
                    item.total_double_overtime_estimate,
                    item.total_triple_overtime_estimate,
                    item.salario_diario,
                ).dailySalary,
                hours_to_pay: getDailySalaryAndHoursToPay(
                    item.daily_salary,
                    item.total_double_overtime_estimate,
                    item.total_triple_overtime_estimate,
                    item.salario_diario,
                ).hoursToPay,
                total_paid_overtimes: getDailySalaryAndHoursToPay(
                    item.daily_salary,
                    item.total_double_overtime_estimate,
                    item.total_triple_overtime_estimate,
                    item.salario_diario,
                ).totalPaidOvertimes,
                diference: getDailySalaryAndHoursToPay(
                    item.daily_salary,
                    item.total_double_overtime_estimate,
                    item.total_triple_overtime_estimate,
                    item.salario_diario,
                ).diference,
            }));
            loading.value = false;
        })
        .catch((error) => {
            console.error(error);
            loading.value = false;
        });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat("es-MX", {
        style: "currency",
        currency: "MXN",
    }).format(value);
};

// Función maestra para agrupar y sumar los datos
const procesarYAgruparDatos = (arreglo) => {
    if (!arreglo || arreglo.length === 0) return [];

    const agrupado = {};

    arreglo.forEach((item) => {
        // Extraemos las propiedades del objeto actual
        const {
            motivo,
            puesto,
            overtime,
            double_overtime,
            triple_overtime,
            importe_a_pagar,
            salariodiario,
        } = item;

        // 1. Si no existe el motivo en nuestro objeto, lo creamos
        if (!agrupado[motivo]) {
            agrupado[motivo] = {};
        }

        // 2. Si no existe el puesto dentro de ese motivo, lo inicializamos en 0
        if (!agrupado[motivo][puesto]) {
            agrupado[motivo][puesto] = {
                puesto: puesto,
                salariodiario: salariodiario, // Lo guardamos solo para mostrar
                overtime: 0,
                double_overtime: 0,
                triple_overtime: 0,
                importe_a_pagar: 0,
            };
        }

        // 3. Sumamos los valores. Usamos parseFloat para convertir los strings a números.
        agrupado[motivo][puesto].overtime += parseFloat(overtime) || 0;
        agrupado[motivo][puesto].double_overtime +=
            parseFloat(double_overtime) || 0;
        agrupado[motivo][puesto].triple_overtime +=
            parseFloat(triple_overtime) || 0;
        agrupado[motivo][puesto].importe_a_pagar +=
            parseFloat(importe_a_pagar) || 0;
    });

    // 4. Convertimos el objeto anidado en un arreglo para que Vue lo pueda iterar fácilmente con v-for
    return Object.keys(agrupado).map((motivoKey) => ({
        motivo: motivoKey,
        puestos: Object.values(agrupado[motivoKey]),
    }));
};

const getDetails = async (idpuesto) => {
    loadingDetails.value = true;

    try {
        const response = await axios.get("/employee/diferences-resume/show", {
            params: {
                id: idpuesto,
                semana_anio: otherFilters.value[0].week,
            },
        });

        console.log(response.data);

        const { dataEstimacionArray, dataNominaArray } = response.data;

        groupedEstimacion.value = procesarYAgruparDatos(
            dataEstimacionArray || [],
        );
        groupedNomina.value = procesarYAgruparDatos(dataNominaArray || []);
        displayDialog.value = true;
    } catch (error) {
        console.error("Error al obtener los detalles:", error);
    } finally {
        loadingDetails.value = false;
    }
};

const getDiferencesResume = () => {
    loading.value = true;
    axios
        .get("/api/diferences-resume", {
            params: {
                week_year: weekFilter.value,
                planta: branchOfficeFilter.value,
            },
        })
        .then((response) => {
            diferencesResume.value = response.data;
            diferencesResume.value = response.data.map((item) => ({
                ...item,
                week: getWeek(item.week).week,
                daily_salary: getDailySalaryAndHoursToPay(
                    item.daily_salary,
                    item.total_double_overtime_estimate,
                    item.total_triple_overtime_estimate,
                    item.salario_diario,
                ).dailySalary,
                hours_to_pay: getDailySalaryAndHoursToPay(
                    item.daily_salary,
                    item.total_double_overtime_estimate,
                    item.total_triple_overtime_estimate,
                    item.salario_diario,
                ).hoursToPay,
                total_paid_overtimes: getDailySalaryAndHoursToPay(
                    item.daily_salary,
                    item.total_double_overtime_estimate,
                    item.total_triple_overtime_estimate,
                    item.salario_diario,
                ).totalPaidOvertimes,
                diference_hours: getDiferencesHours(
                    item.total_overtime_estimate,
                    item.total_overtimes,
                ),
                diference: getDailySalaryAndHoursToPay(
                    item.daily_salary,
                    item.total_double_overtime_estimate,
                    item.total_triple_overtime_estimate,
                    item.salario_diario,
                ).diference,
                worked_extra_hours:
                    Math.floor(item.total_overtimes * 100) / 100,
            }));
            loading.value = false;
        });
};

const getWeek = (week_year) => {
    if (!week_year) return "";
    const week = week_year.split("-W")[1];
    return { week };
};

const saveColumns = () => {
    const selectedKeys = Object.keys(exportColumns.value).filter(
        (key) => exportColumns.value[key] === true,
    );

    if (selectedKeys.length === 0) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Por favor selecciona al menos una columna para exportar.",
            life: 3000,
        });
        return;
    }

    const sourceData = diferencesResume.value;

    if (!sourceData || sourceData.length === 0) {
        alert("No hay datos para exportar.");
        return;
    }

    const dataToExport = sourceData.map((row) => {
        const rowToExport = {};

        selectedKeys.forEach((key) => {
            const colConfig = columnMap[key];

            if (colConfig) {
                let value = row[colConfig.field];

                rowToExport[colConfig.header] =
                    value !== undefined && value !== null ? value : "";
            }
        });

        return rowToExport;
    });

    const worksheet = xlsx.utils.json_to_sheet(dataToExport);
    const workbook = xlsx.utils.book_new();
    xlsx.utils.book_append_sheet(workbook, worksheet, "Resumen de Diferencias");
    xlsx.writeFile(workbook, "Resumen de Diferencias.xlsx");

    columnsDialog.value = false;
};

const exportToPDF = async () => {
    loading.value = true;
    const selectedKeys = Object.keys(exportColumns.value).filter(
        (key) => exportColumns.value[key] === true,
    );

    if (selectedKeys.length === 0) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Por favor selecciona al menos una columna para exportar.",
            life: 3000,
        });
        return;
    }

    const sourceData = diferencesResume.value;

    if (!sourceData || sourceData.length === 0) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No hay datos para exportar.",
            life: 3000,
        });
        return;
    }

    const headers = selectedKeys.map((key) => columnMap[key].header);

    const rows = sourceData.map((row) => {
        return selectedKeys.map((key) => {
            const colConfig = columnMap[key];
            let value = row[colConfig.field];
            return value !== undefined && value !== null ? value : "";
        });
    });

    try {
        const response = await axios.post(
            "/employee/diferences-resume/generate-pdf",
            {
                headers: headers,
                rows: rows,
                name_branch_office: selectedBranchOffice.value.name,
            },
            {
                responseType: "blob",
            },
        );

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement("a");
        link.href = url;
        const fileName = "Resumen Diferencias.pdf";
        link.setAttribute("download", fileName);
        document.body.appendChild(link);
        link.click();
        link.remove();

        columnsDialog.value = false;
        loading.value = false;
    } catch (error) {
        console.error("Error al generar el PDF:", error);
        alert("Hubo un problema al generar el PDF.");
    }
};

const clearFilter = () => {
    initFilters();
};

initFilters();
getDiferencesResume();
</script>

<template>
    <Toolbar>
        <template #start>
            <Button
                type="button"
                icon="pi pi-file-excel"
                label="Exportar XLSX"
                severity="secondary"
                class="mt-2 ml-2"
                @click="columnsDialog = true"
                v-if="can('differences.export')"
            />
            <Button
                type="button"
                icon="pi pi-file-pdf"
                label="Exportar PDF"
                severity="contrast"
                class="mt-2 ml-2"
                @click="columnsPdfDialog = true"
                v-if="can('differences.export')"
            />
        </template>
    </Toolbar>
    <DataTable
        ref="dt"
        v-model:selection="selected"
        :value="diferencesResume"
        dataKey="id"
        :paginator="true"
        :rows="10"
        scrollable
        reorderableColumns
        scrollHeight="400px"
        :filterDelay="500"
        v-model:filters="filters"
        filterDisplay="menu"
        exportFilename="resumen_de_diferencias"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} resumen de diferencias"
    >
        <template #header>
            <div class="flex flex-wrap gap-2 items-end justify-between mb-6">
                <div>
                    <h4 class="m-0">Resumen de diferencias</h4>
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
                                <ul class="list-none p-0 m-0 flex flex-col">
                                    <li
                                        v-for="(value, key) in showColumns"
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
                                                key.charAt(0).toUpperCase() +
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
                                <ul class="list-none p-0 m-0 flex flex-col">
                                    <li
                                        v-for="(value, key) in frozenColumns"
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
                                                key.charAt(0).toUpperCase() +
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
                        :label="'Planta: ' + otherFilters[0].branch_office_id"
                        v-if="otherFilters[0].branch_office_id != null"
                    />
                </div>
                <div class="mb-2">
                    <Chip
                        :label="'Empleado: ' + otherFilters[0].employee_id"
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
                            otherFilters[0].includeEliminated ? 'Si' : 'No'
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
                width: '5rem',
                display: showColumns.acciones ? '' : 'none',
                minWidth: '5rem',
            }"
            :frozen="frozenColumns.acciones"
            header="Acciones"
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <div v-else>
                    <Button
                        icon="pi pi-eye"
                        class="mr-2"
                        rounded
                        v-tooltip.top="'Ver'"
                        severity="help"
                        :disabled="loadingDetails"
                        :loading="loadingDetails"
                        @click="getDetails(data.idpuesto)"
                    />
                </div>
            </template>
        </Column>
        <Column
            field="semana"
            header="Semana"
            :filter="true"
            columnKey="semana"
            :frozen="frozenColumns.semana"
            :style="{
                width: '5rem',
                display: showColumns.semana ? '' : 'none',
            }"
            :exportable="exportColumns.semana"
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>{{ data.week }}</span>
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
            field="puesto"
            header="Puesto"
            :filter="true"
            columnKey="puesto"
            :frozen="frozenColumns.puesto"
            :style="{
                width: '5rem',
                display: showColumns.puesto ? '' : 'none',
            }"
            :exportable="exportColumns.puesto"
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>{{ data.name }}</span>
            </template>
            <template #filter="{ filterModel }">
                <InputText
                    v-model="filterModel.value"
                    type="text"
                    placeholder="Buscar por Puesto"
                />
            </template>
        </Column>
        <Column
            field="costo_max_por_hora"
            header="Costo Max Por Hora"
            :filter="true"
            columnKey="costo_max_por_hora"
            :frozen="frozenColumns.costo_max_por_hora"
            :style="{
                width: '5rem',
                display: showColumns.costo_max_por_hora ? '' : 'none',
            }"
            :exportable="exportColumns.costo_max_por_hora"
            :sortable="true"
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>{{ data.daily_salary }}</span>
            </template>
        </Column>
        <Column
            field="horas_extra_autorizadas"
            header="Horas Extra Autorizadas"
            :filter="true"
            columnKey="horas_extra_autorizadas"
            :frozen="frozenColumns.horas_extra_autorizadas"
            :style="{
                width: '5rem',
                display: showColumns.horas_extra_autorizadas ? '' : 'none',
            }"
            :exportable="exportColumns.horas_extra_autorizadas"
            :sortable="true"
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>{{ data.total_overtime_estimate }}</span>
            </template>
        </Column>
        <Column
            field="importe_autorizado"
            header="Importe Autorizado"
            :filter="true"
            columnKey="importe_autorizado"
            :frozen="frozenColumns.importe_autorizado"
            :style="{
                width: '5rem',
                display: showColumns.importe_autorizado ? '' : 'none',
            }"
            :exportable="exportColumns.importe_autorizado"
            :sortable="true"
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>{{ data.hours_to_pay }}</span>
            </template>
        </Column>
        <Column
            field="horas_extra_trabajadas"
            header="Horas Extra Trabajadas"
            :filter="true"
            columnKey="horas_extra_trabajadas"
            :frozen="frozenColumns.horas_extra_trabajadas"
            :style="{
                width: '5rem',
                display: showColumns.horas_extra_trabajadas ? '' : 'none',
            }"
            :exportable="exportColumns.horas_extra_trabajadas"
            :sortable="true"
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>{{ data.worked_extra_hours }}</span>
            </template>
        </Column>
        <Column
            field="importe_pagado"
            header="Importe Pagado"
            :filter="true"
            columnKey="importe_pagado"
            :frozen="frozenColumns.importe_pagado"
            :style="{
                width: '5rem',
                display: showColumns.importe_pagado ? '' : 'none',
            }"
            :exportable="exportColumns.importe_pagado"
            :sortable="true"
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>{{ data.total_paid_overtimes }}</span>
            </template>
        </Column>
        <Column
            field="diferencia_horas"
            header="Diferencia en Horas Extra"
            :filter="true"
            columnKey="diferencia_horas"
            :frozen="frozenColumns.diferencia_horas"
            :style="{
                width: '5rem',
                display: showColumns.diferencia_horas ? '' : 'none',
            }"
            :exportable="exportColumns.diferencia_horas"
            :sortable="true"
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>{{ data.diference_hours }}</span>
            </template>
        </Column>
        <Column
            field="diferencia_importe"
            header="Diferencia en Importe"
            :filter="true"
            columnKey="diferencia_importe"
            :frozen="frozenColumns.diferencia_importe"
            :style="{
                width: '5rem',
                display: showColumns.diferencia_importe ? '' : 'none',
            }"
            :exportable="exportColumns.diferencia_importe"
            :sortable="true"
        >
            <template #body="{ data }">
                <Skeleton v-if="loading"></Skeleton>
                <span v-else>{{ data.diference }}</span>
            </template>
        </Column>
    </DataTable>
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

            <!-- Semana (type=week => AAAA-WSS) -->
            <div class="w-full md:w-1/2 px-2 mb-4">
                <label class="block mb-2 font-medium">Semana</label>
                <InputText v-model="weekFilter" type="week" class="w-full" />
            </div>
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
        v-model:visible="displayDialog"
        header="Resumen de Diferencias"
        :style="{ width: '80vw' }"
        maximizable
        modal
    >
        <div v-if="groupedNomina.length > 0">
            <h3 class="text-xl font-bold mb-3">Datos de Nómina</h3>
            <Accordion expandIcon="pi pi-plus" collapseIcon="pi pi-minus">
                <AccordionTab
                    v-for="(grupo, index) in groupedNomina"
                    :key="'nom-' + index"
                    :header="`Motivo: ${grupo.motivo}`"
                >
                    <DataTable
                        :value="grupo.puestos"
                        responsiveLayout="scroll"
                        showGridlines
                        class="p-datatable-sm"
                    >
                        <Column field="puesto" header="Puesto"></Column>
                        <Column field="salariodiario" header="Salario Diario">
                            <template #body="{ data }">
                                {{ formatCurrency(data.salariodiario) }}
                            </template>
                        </Column>
                        <Column field="overtime" header="Horas Extra"></Column>
                        <Column
                            field="double_overtime"
                            header="Dobles"
                        ></Column>
                        <Column
                            field="triple_overtime"
                            header="Triples"
                        ></Column>
                        <Column field="importe_a_pagar" header="Importe Total">
                            <template #body="{ data }">
                                <span class="font-bold text-green-600">{{
                                    formatCurrency(data.importe_a_pagar)
                                }}</span>
                            </template>
                        </Column>
                    </DataTable>
                </AccordionTab>
            </Accordion>
        </div>

        <div v-if="groupedEstimacion.length > 0" class="mt-5">
            <h3 class="text-xl font-bold mb-3">Datos de Estimación</h3>
            <Accordion
                value="0"
                expandIcon="pi pi-plus"
                collapseIcon="pi pi-minus"
            >
                <AccordionTab
                    v-for="(grupo, index) in groupedEstimacion"
                    :key="'est-' + index"
                    :header="`Motivo: ${grupo.motivo}`"
                >
                    <DataTable
                        :value="grupo.puestos"
                        responsiveLayout="scroll"
                        showGridlines
                        class="p-datatable-sm"
                    >
                        <Column field="puesto" header="Puesto"></Column>
                        <Column field="salariodiario" header="Salario Diario">
                            <template #body="{ data }">
                                {{ formatCurrency(data.salariodiario) }}
                            </template>
                        </Column>
                        <Column field="overtime" header="Horas Extra"></Column>
                        <Column
                            field="double_overtime"
                            header="Dobles"
                        ></Column>
                        <Column
                            field="triple_overtime"
                            header="Triples"
                        ></Column>
                        <Column field="importe_a_pagar" header="Importe Total">
                            <template #body="{ data }">
                                <span class="font-bold text-green-600">{{
                                    formatCurrency(data.importe_a_pagar)
                                }}</span>
                            </template>
                        </Column>
                    </DataTable>
                </AccordionTab>
            </Accordion>
        </div>

        <div
            v-if="
                !groupedNomina.length && !groupedEstimacion.length && !loading
            "
        >
            <p>No se encontraron datos para mostrar.</p>
        </div>

        <template #footer>
            <Button
                label="Cerrar"
                icon="pi pi-times"
                @click="displayDialog = false"
                class="p-button-text"
            />
        </template>
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
    <Dialog
        v-model:visible="columnsPdfDialog"
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
                @click="columnsPdfDialog = false"
            />
            <Button
                label="Exportar"
                icon="pi pi-save"
                severity="success"
                @click="exportToPDF"
                :loading="loading"
            />
        </template>
    </Dialog>
</template>
