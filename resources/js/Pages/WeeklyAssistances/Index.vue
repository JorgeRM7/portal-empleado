<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToastService } from "@/Stores/toastService";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import { onMounted, ref } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { router } from "@inertiajs/vue3";
import { useDownloadConfirm } from "@/composables/useDownloadConfirm";

const props = defineProps({
    data: Array,
    dates: Array,
    branch_offices: Array,
    deparments: Array,
    employees: Array,
    incidences: Array,
});

// console.log(props)

// Variables globales
const branch_offices = ref();
const employees = ref();
const deparments = ref();
const incidences = ref();

const modalDetails = ref(false);
const details = ref(null);

const modalTurns = ref(false);

//Inicializar filtros globales de tabla
const filters = ref({});

//Inicializar variable de carga para mostrar esqueleto
const loading = ref(true);

//Función para mostrar toast de éxito y error
const { showSuccess, showError, processingTurn, showValidationError } =
    useToastService();
const { openDownloadConfirm } = useDownloadConfirm();

//Función para inicializar filtros
const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        employee_id: { value: null, matchMode: FilterMatchMode.CONTAINS },
        employee_name: { value: null, matchMode: FilterMatchMode.CONTAINS },
        department_name: { value: null, matchMode: FilterMatchMode.CONTAINS },
        position_name: { value: null, matchMode: FilterMatchMode.CONTAINS },
        entry_date: { value: null, matchMode: FilterMatchMode.CONTAINS },
        monday_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
        friday_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
        wednesday_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
        tuesday_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
        saturday_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
        sunday_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
        thursday_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
        thursday_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
    };
};

initFilters();

//Columnas de exportación
const exportColumns = ref({
    Clave: true,
    Empleado: true,
    Departamento: true,
    Puesto: true,
    "Fecha Ingreso": true,
    Lunes: true,
    "Horario Lunes": true,
    Martes: true,
    "Horario Martes": true,
    Miercoles: true,
    "Horario Miercoles": true,
    Jueves: true,
    "Horario Jueves": true,
    Viernes: true,
    "Horario Viernes": true,
    Sabado: true,
    "Horario Sabado": true,
    Domingo: true,
    "Horario Domingo": true,
    "Prima Dominical": true,
    Dobles: true,
    Triples: true,
    Faltas: true,
    Planta: true,
    Semana: true,
    Año: true,
});

//Columnas visibles
const showColumns = ref({
    Clave: true,
    Empleado: true,
    Departamento: true,
    Puesto: true,
    Fecha_ingreso: true,
    Lunes: true,
    Horario_Lunes: false,
    Martes: true,
    Horario_Martes: false,
    Miercoles: true,
    Horario_Miercoles: false,
    Jueves: true,
    Horario_Jueves: false,
    Viernes: true,
    Horario_Viernes: false,
    Sabado: true,
    Horario_Sabado: false,
    Domingo: true,
    Horario_Domingo: false,
    Prima_Domical: true,
    Dobles: true,
    Triples: true,
    Faltas: true,
    Planta: false,
    Semana: false,
    Año: false,
});

//Filtros adicionales
const otherFilters = ref([
    {
        startDate: null,
        endDate: null,
    },
]);

//Diálogo de filtros adicionales
const otherFilterDialog = ref(false);

//Filtros del modal
const startDate = ref();
const endDate = ref();
const selectedBranchOfficeId = ref(null);
const selectedWeek = ref(null);
const selectedYear = ref(null);
const selectedBranchOfficeName = ref(null);
const selectedEmployees = ref(null);
const selectedDeparments = ref(null);
const selectedIncidences = ref(null);

//Referencia a la tabla de datos
const dt = ref(null);
const toast = useToast();

//Columnas fijas
const frozenColumns = ref({
    Clave: true,
    Empleado: true,
    Departamento: false,
    Puesto: false,
    Fecha_ingreso: false,
    Lunes: false,
    Horario_Lunes: false,
    Martes: false,
    Horario_Martes: false,
    Miercoles: false,
    Horario_Miercoles: false,
    Jueves: false,
    Horario_Jueves: false,
    Viernes: false,
    Horario_Viernes: false,
    Sabado: false,
    Horario_Sabado: false,
    Domingo: false,
    Horario_Domingo: false,
    Prima_Domical: false,
    Dobles: false,
    Triples: false,
    Faltas: false,
    Planta: false,
    Semana: false,
    Año: false,
});

//Diálogo de selección de columnas
const columnsDialog = ref(false);

//Estado de envío
const submitted = ref(false);

//Referencias a los popovers
const op = ref();
const opMostrarColumnas = ref();
const opFijarColumnas = ref();

//Estado de diálogo de subida de archivos
const openUploadDialog = ref(false);

//Estado de progreso de subida
const progress = ref(0);

//Estado de visibilidad del toast
const visible = ref(false);

//Referencia al servicio de toast personalizado
const interval = ref();

//Funciones para alternar los popovers
const toggleAccionesMasivas = (event) => {
    op.value.toggle(event);
};

const toggleMostrarColumnas = (event) => {
    opMostrarColumnas.value.toggle(event);
};

const toggleFijarColumnas = (event) => {
    opFijarColumnas.value.toggle(event);
};

//Inicializacion de una fila para mostrar el esqueleto de inicio en lo que carga la tabla
const rows = ref([
    {
        numero_nomina: 0,
        empleado: "",
        departamento: "",
        puesto: "",
        fecha_ingreso: "",
        lunes: "",
        martes: "",
        miercoles: "",
        jueves: "",
        viernes: "",
        sabado: "",
        domingo: "",
    },
]);
const dates = ref();
//Filas seleccionadas
const selected = ref([]);

const getSelectedFields = () => {
    return Object.keys(exportColumns.value).filter(
        (key) => exportColumns.value[key],
    );
};

//Función para guardar las columnas seleccionadas para exportar
const saveColumns = async () => {
    const campos = getSelectedFields();
    columnsDialog.value = false;
    const [anio, semana] = selectedWeek.value.split("-W");
    try {
        toast.add({
            severity: "info",
            summary: "Descargando",
            detail: "Espera en lo que se descarga tu archivo...",
            group: "download",
            closable: false,
            life: 0,
        });

        const response = await axios.post(
            "/assistences/weekly-assistences/downloadAll",
            {
                campos: campos,
                empleados: selected.value.map((e) => e.employee_id),
                anio: parseInt(anio),
                semana: parseInt(semana, 10),
            },
            {
                responseType: "blob",
            },
        );
        const contentDisposition = response.headers["content-disposition"];

        let fileName = "reporte";

        if (contentDisposition) {
            const match = contentDisposition.match(/filename="?([^"]+)"?/);
            if (match?.[1]) {
                fileName = match[1];
            }
        }

        const blob = new Blob([response.data], {
            type: response.data.type,
        });

        const url = window.URL.createObjectURL(blob);

        const link = document.createElement("a");
        link.href = url;
        link.setAttribute("download", fileName);
        document.body.appendChild(link);
        link.click();
        link.remove();

        window.URL.revokeObjectURL(url);
        toast.removeGroup("download");

        showSuccess();
    } catch (error) {
        toast.removeGroup("download");

        console.error(error);
    }
};

//Función para limpiar filtros
const clearFilter = () => {
    selectedEmployees.value = null;
    selectedDeparments.value = null;
    selectedIncidences.value = null;
    selected.value = [];
    filter_data();
};

//Función para aplicar filtros adicionales
const filter_data = async () => {
    loading.value = true;
    selected.value = [];
    const [anio, semana] = selectedWeek.value.split("-W");
    otherFilterDialog.value = false;
    selectedYear.value = anio;
    selectedBranchOfficeName.value = selectedBranchOfficeId.value.code;
    try {
        const response = await axios.get(
            "/assistences/weekly-assistences/filter-data",
            {
                params: {
                    planta: selectedBranchOfficeId.value.id,
                    anio: parseInt(anio),
                    semana: parseInt(semana, 10),
                    empleados: selectedEmployees.value,
                    departamento: selectedDeparments.value,
                    incidencia: selectedIncidences.value,
                },
            },
        );
        rows.value = response.data.data;
        dates.value = response.data.dates;
        employees.value = response.data.employees;
        loading.value = false;
    } catch (error) {
        console.error("Error refrescando tabla:", error);
        showError("No se pudo actualizar la tabla");
    }
};

onMounted(async () => {
    rows.value = props.data;
    branch_offices.value = props.branch_offices;
    deparments.value = props.deparments;
    employees.value = props.employees;
    incidences.value = props.incidences;
    dates.value = props.dates;
    selectedWeek.value = getCurrentWeek();
    const savedBranch = JSON.parse(
        localStorage.getItem("selectedBranchOffice"),
    );
    if (savedBranch) {
        const match = branch_offices.value.find((b) => b.id === savedBranch.id);
        selectedBranchOfficeId.value = match ?? null;
        selectedBranchOfficeName.value = selectedBranchOfficeId.value.code;
    }
    filter_data();
});

const show_details = (data, day) => {
    const checadasDelDia = data.checadas?.[day] ?? [];
    const raw = data[`${day}_data`];
    let horarioParsed = null;

    if (raw) {
        if (typeof raw === "object") {
            horarioParsed = raw;
        } else {
            try {
                const cleaned = raw.replace(/[\t\n\r]/g, "").trim();

                horarioParsed = JSON.parse(cleaned);
            } catch (e) {
                console.error(
                    "Error parseando horario:",
                    e,
                    "\nRaw recibido:",
                    raw,
                );
                horarioParsed = null;
            }
        }
    }
    details.value = {
        employee_id: data.employee_id,
        employee_name: data.employee_name,
        department_name: data.department_name,
        dia: day,
        horario: horarioParsed,
        incidencia: data[`${day}_incidence`] ?? "Sin incidencia",
        descripcion_incidencia: data[`${day}_description`] ?? "Sin descripción",
        color: data[`${day}_color`],
        week_number: data.week_number,
        week_year: data.week_year,
        checadas: checadasDelDia,
    };

    modalDetails.value = true;
};

// const revisarIncidencias = () => {
//     const fecha_inicial = startDate.value;
//     const fecha_final = endDate.value;

//     if (!fecha_inicial || !fecha_final) {
//         showError("Selecciona fecha inicial y final");
//         return;
//     }

//     const selectedRows = selected.value.map((e) => e.employee_id);

//     if (selectedRows.length < 1) {
//         showError("Selecciona al menos un empleado");
//         return;
//     }

//     const fechas = [];
//     let fInicio = new Date(fecha_inicial);
//     const fFin = new Date(fecha_final);

//     while (fInicio <= fFin) {
//         fechas.push(fInicio.toISOString().split("T")[0]);
//         fInicio.setDate(fInicio.getDate() + 1);
//     }

//     modalTurns.value = false;
//     toast.add({
//         severity: "info",
//         summary: "Revisión de turnos iniciada",
//         life: 5000,
//     });

//     let url =
//         "https://grupo-ortiz.site/apis/Controllers/weeklyAsistenceController.php?op=revisar-turno";

//     let peticiones = [];

//     selectedRows.forEach((id) => {
//         fechas.forEach((fecha) => {
//             peticiones.push(() =>
//                 $.ajax({
//                     url: url,
//                     method: "POST",
//                     data: { id: id, validity_from: fecha },
//                 })
//                     .then(function (response) {
//                         let res =
//                             typeof response === "string"
//                                 ? JSON.parse(response)
//                                 : response;

//                         res.empleadoId = id;
//                         res.fechaError = fecha;

//                         return res;
//                     })
//                     .catch(function () {
//                         return {
//                             estatus: "error",
//                             message: "No se encontró un rol de turno activo",
//                             empleadoId: id,
//                             fechaError: fecha,
//                         };
//                     }),
//             );
//         });
//     });

//     const total = peticiones.length;
//     let procesados = 0;
//     let resultados = [];

//     const tamañoLote = 70;

//     const ejecutar = async () => {
//         for (let i = 0; i < peticiones.length; i += tamañoLote) {
//             const lote = peticiones.slice(i, i + tamañoLote);

//             const res = await Promise.all(lote.map((fn) => fn()));

//             resultados = resultados.concat(res);

//             procesados += lote.length;

//             const porcentaje = Math.round((procesados / total) * 100);
//             toast.add({
//                 severity: "info",
//                 summary: "Procesando...",
//                 detail: `${porcentaje}% (${procesados}/${total})`,
//                 life: 5000,
//             });

//             await new Promise((r) => setTimeout(r, 120));
//         }

//         const errores = resultados.filter(
//             (res) => res && res.estatus === "error",
//         );

//         toast.add({
//             severity: "success",
//             summary: "Finalizado",
//             detail: `Procesados: ${total}`,
//             life: 5000,
//         });
//         filter_data();
//     };

//     ejecutar().catch((err) => {
//         showError();
//         console.error("Error crítico:", err);
//     });
// };


const revisarIncidencias = async () => {
    const fecha_inicial = startDate.value;
    const fecha_final = endDate.value;

    if (!fecha_inicial || !fecha_final) {
        showError("Selecciona fecha inicial y final");
        return;
    }

    const selectedRows = selected.value.map((e) => e.employee_id);

    if (selectedRows.length < 1) {
        showError("Selecciona al menos un empleado");
        return;
    }

    const fechas = [];
    let fInicio = new Date(fecha_inicial);
    const fFin = new Date(fecha_final);

    while (fInicio <= fFin) {
        fechas.push(fInicio.toISOString().split("T")[0]);
        fInicio.setDate(fInicio.getDate() + 1);
    }

    modalTurns.value = false;

    toast.add({
        severity: "info",
        summary: "Revisión de turnos iniciada",
        life: 5000,
    });

    const url = "/assistences/weekly-assistances/check-turn";

    let peticiones = [];

    selectedRows.forEach((id) => {
        fechas.forEach((fecha) => {
            peticiones.push(() =>
                axios.post(url, {
                    id: id,
                    validity_from: fecha,
                })
                .then((response) => {
                    let res = response.data;

                    res.empleadoId = id;
                    res.fechaError = fecha;
                    console.log(res)

                    return res;
                })
                .catch((error) => {
                    console.error("Error en petición:", error);

                    return {
                        estatus: "error",
                        message: error?.response?.data?.message || "No se encontró un rol de turno activo",
                        empleadoId: id,
                        fechaError: fecha,
                    };
                })
            );
        });
    });

    const total = peticiones.length;
    let procesados = 0;
    let resultados = [];

    const tamañoLote = 200;

    try {
        for (let i = 0; i < peticiones.length; i += tamañoLote) {
            const lote = peticiones.slice(i, i + tamañoLote);

            const res = await Promise.all(lote.map((fn) => fn()));

            resultados = resultados.concat(res);

            procesados += lote.length;

            const porcentaje = Math.round((procesados / total) * 100);

            toast.add({
                severity: "info",
                summary: "Procesando...",
                detail: `${porcentaje}% (${procesados}/${total})`,
                life: 3000,
            });

            await new Promise((r) => setTimeout(r, 120));
        }

        const errores = resultados.filter(
            (res) => res && res.estatus === "error"
        );

        console.log("Errores detectados:", errores);

        toast.add({
            severity: "success",
            summary: "Finalizado",
            detail: `Procesados: ${total}`,
            life: 5000,
        });

        filter_data();
    } catch (err) {
        console.error("Error crítico:", err);
        showError("Ocurrió un error al procesar la revisión");
    }
};

const getCurrentWeek = () => {
    const date = new Date();
    const d = new Date(
        Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()),
    );
    d.setUTCDate(d.getUTCDate() + 4 - (d.getUTCDay() || 7));
    const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
    const weekNo = Math.ceil(((d - yearStart) / 86400000 + 1) / 7);
    return `${d.getUTCFullYear()}-W${weekNo.toString().padStart(2, "0")}`;
};

const parseJSON = (val) => {
    try {
        return typeof val === "string" ? JSON.parse(val) : val;
    } catch (e) {
        return null;
    }
};
</script>

<template>
    <AppLayout :title="'Asistencia Semanal'">
        <Toast group="download" position="top-right" />
        <Toast group="processing" />
        <Toast position="top-center" group="headless" @close="visible = false">
            <template #container="{ message, closeCallback }">
                <section
                    class="flex flex-col p-4 gap-4 w-full bg-gray-100 dark:bg-gray-800 rounded-xl"
                >
                    <div class="flex items-center gap-5">
                        <i class="pi pi-cloud-upload text-2xl text-white"></i>
                        <span class="font-bold text-base text-white">{{
                            message.summary
                        }}</span>
                    </div>
                    <div class="flex flex-col gap-2">
                        <ProgressBar
                            :value="progress"
                            :showValue="false"
                            :style="{ height: '4px' }"
                            pt:value:class="!bg-primary-50 dark:!bg-primary-900"
                            class="!bg-primary/80"
                        ></ProgressBar>
                        <label class="text-sm font-bold text-white"
                            >{{ progress }}% subido</label
                        >
                    </div>
                </section>
            </template>
        </Toast>
        <div class="card">
            <!-- <pre>{{ rows }}</pre> -->

            <Toolbar>
                <template #start>
                    <Button
                        type="button"
                        icon="pi pi-upload"
                        label="Exportar"
                        severity="secondary"
                        class="mt-2 ml-2"
                        @click="columnsDialog = true"
                    />
                </template>

                <template #end>
                    <Button
                        label="Revisar turnos"
                        icon="pi pi-send"
                        severity="success"
                        @click="modalTurns = true"
                    />
                </template>
            </Toolbar>

            <DataTable
                ref="dt"
                v-model:selection="selected"
                :value="rows"
                dataKey="employee_id"
                :paginator="true"
                :rows="50"
                scrollable
                scrollHeight="600px"
                tableStyle="min-width: 110rem"
                v-model:filters="filters"
                filterDisplay="menu"
                exportFilename="Asistencia Semanal"
                :globalFilterFields="[
                    'employee_id',
                    'employee_name',
                    'department_name',
                    'position_name',
                    'entry_date',
                    'monday_code',
                    'tuesday_code',
                    'wednesday_code',
                    'thursday_code',
                    'friday_code',
                    'saturday_code',
                    'sunday_code',
                ]"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} datos de asistencia"
            >
                <template #header>
                    <div
                        class="flex flex-wrap gap-2 items-end justify-between mb-6"
                    >
                        <div>
                            <h4 class="m-0">Asistencia Semanal</h4>
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
                                    v-model="filters['global'].value"
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
                    <div class="mb-2">
                        <Chip :label="'Semanas: ' + selectedWeek" />
                        <Chip
                            v-if="selectedBranchOfficeName != null"
                            :label="'Planta: ' + selectedBranchOfficeName"
                        />
                        <Chip
                            v-if="selectedEmployees"
                            :label="'Empleados: ' + selectedEmployees"
                            removable
                            @remove="
                                () => {
                                    selected = [];
                                    selectedEmployees = null;
                                    filter_data();
                                }
                            "
                        />
                        <Chip
                            v-if="selectedDeparments"
                            :label="'Departamentos: ' + selectedDeparments"
                            removable
                            @remove="
                                () => {
                                    selected = [];
                                    selectedDeparments = null;
                                    filter_data();
                                }
                            "
                        />
                        <Chip
                            v-if="selectedIncidences"
                            :label="'Incidencias: ' + selectedIncidences"
                            removable
                            @remove="
                                () => {
                                    selected = [];
                                    selectedIncidences = null;
                                    filter_data();
                                }
                            "
                        />
                    </div>
                </template>

                <Column
                    selectionMode="multiple"
                    style="width: 1rem"
                    :exportable="false"
                    :frozen="true"
                ></Column>
                <Column
                    field="employee_id"
                    header="Clave"
                    sortable
                    :filter="true"
                    :frozen="frozenColumns.Clave"
                    :style="{
                        width: '1rem',
                        display: showColumns.Clave ? '' : 'none',
                    }"
                    :exportable="exportColumns.Clave"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.employee_id }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Numero"
                        />
                    </template>
                </Column>
                <Column
                    field="employee_name"
                    header="Empeado"
                    sortable
                    :filter="true"
                    :frozen="frozenColumns.Empleado"
                    :style="{
                        width: '20rem',
                        display: showColumns.Empleado ? '' : 'none',
                    }"
                    :exportable="exportColumns.Empleado"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.employee_name }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Nombre"
                        />
                    </template>
                </Column>
                <Column
                    field="planta"
                    header="Planta"
                    sortable
                    :filter="true"
                    :frozen="frozenColumns.Planta"
                    :style="{
                        width: '20rem',
                        display: showColumns.Planta ? '' : 'none',
                    }"
                    :exportable="exportColumns.Planta"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.planta }}</span>
                    </template>
                </Column>
                <Column
                    field="department_name"
                    header="Departamento"
                    :frozen="frozenColumns.Departamento"
                    sortable
                    :style="{
                        width: '20rem',
                        display: showColumns.Departamento ? '' : 'none',
                    }"
                    :exportable="exportColumns.Departamento"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.department_name }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Departamento"
                        />
                    </template>
                </Column>
                <Column
                    field="position_name"
                    header="Puesto"
                    sortable
                    :frozen="frozenColumns.Puesto"
                    :style="{
                        width: '20rem',
                        display: showColumns.Puesto ? '' : 'none',
                    }"
                    :exportable="exportColumns.Puesto"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.position_name }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Puesto"
                        /> </template
                ></Column>
                <Column
                    field="week_number"
                    header="Semana"
                    sortable
                    :frozen="frozenColumns.Semana"
                    :style="{
                        width: '20rem',
                        display: showColumns.Semana ? '' : 'none',
                    }"
                    :exportable="exportColumns.Semana"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.week_number }}</span>
                    </template>
                </Column>
                <Column
                    field="week_year"
                    header="Año"
                    sortable
                    :frozen="frozenColumns.Año"
                    :style="{
                        width: '20rem',
                        display: showColumns.Año ? '' : 'none',
                    }"
                    :exportable="exportColumns.Año"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.week_year }}</span>
                    </template>
                </Column>
                <Column
                    field="entry_date"
                    header="Fecha Ingreso"
                    sortable
                    :frozen="frozenColumns.Fecha_ingreso"
                    :style="{
                        width: '20rem',
                        display: showColumns.Fecha_ingreso ? '' : 'none',
                    }"
                    :exportable="exportColumns.Fecha_ingreso"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.entry_date }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar"
                        /> </template
                ></Column>
                <Column
                    field="monday_code"
                    :header="'Lunes (' + dates?.monday ?? '' + ')'"
                    sortable
                    :frozen="frozenColumns.Lunes"
                    :style="{
                        width: '20rem',
                        display: showColumns.Lunes ? '' : 'none',
                    }"
                    :exportable="exportColumns.Lunes"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <Badge
                            :value="data.monday_code"
                            size="xlarge"
                            severity="success"
                            v-tooltip.top="data.monday_incidence"
                            v-else
                            :style="{
                                backgroundColor: data.monday_color,
                                color: '#fff',
                                cursor: 'pointer',
                            }"
                            v-if="data.monday_code"
                            @click="show_details(data, 'monday', )"
                        >
                        </Badge>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar"
                        /> </template
                ></Column>
                <Column
                    field="monday_data"
                    header="Horario Lunes"
                    sortable
                    :frozen="frozenColumns.Horario_Lunes"
                    :style="{
                        width: '10rem',
                        display: showColumns.Horario_Lunes ? '' : 'none',
                    }"
                    :exportable="exportColumns.Horario_Lunes"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading" width="100%" height="4rem" />
                        <div
                            v-else
                            class="p-2 rounded-md border border-gray-200 text-xs text-gray-700 leading-tight"
                        >
                            <template v-if="parseJSON(data?.monday_data)">
                                <div>
                                    <b>Turno:</b>
                                    {{ parseJSON(data?.monday_data)?.Turno }}
                                </div>
                                <div>
                                    <b>Horario:</b>
                                    {{ parseJSON(data?.monday_data)?.Horario }}
                                </div>
                                <div>
                                    <b>Entrada:</b>
                                    {{ parseJSON(data?.monday_data)?.Entrada }}
                                </div>
                                <div>
                                    <b>Salida:</b>
                                    {{ parseJSON(data?.monday_data)?.Salida }}
                                </div>
                                <div>
                                    <b>Dobles:</b>
                                    {{
                                        parseJSON(data?.monday_data)?.[
                                            "Horas dobles"
                                        ]
                                    }}
                                </div>
                                <div>
                                    <b>Triples:</b>
                                    {{
                                        parseJSON(data?.monday_data)?.[
                                            "Horas triples"
                                        ]
                                    }}
                                </div>
                                <div
                                    v-if="
                                        parseJSON(data?.monday_data)?.Checadas
                                            ?.length
                                    "
                                >
                                    <b>Checadas:</b>
                                    <span>
                                        {{
                                            parseJSON(data?.monday_data)
                                                .Checadas.map(
                                                    (c) => c?.access_time,
                                                )
                                                .join(", ")
                                        }}
                                    </span>
                                </div>
                                <div
                                    v-else
                                    class="text-gray-400 italic text-center py-2"
                                >
                                    Sin checadas
                                </div>
                            </template>
                            <template v-else>
                                <div
                                    class="text-center text-gray-400 italic py-2"
                                >
                                    Sin datos
                                </div>
                            </template>
                        </div>
                    </template>

                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar"
                            class="p-inputtext-sm w-full"
                        />
                    </template>
                </Column>
                <Column
                    field="tuesday_code"
                    :header="'Martes (' + dates?.tuesday + ')'"
                    sortable
                    :frozen="frozenColumns.Martes"
                    :style="{
                        width: '20rem',
                        display: showColumns.Martes ? '' : 'none',
                    }"
                    :exportable="exportColumns.Martes"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <Badge
                            :value="data.tuesday_code"
                            size="xlarge"
                            severity="success"
                            v-tooltip.top="data.tuesday_incidence"
                            v-else
                            :style="{
                                backgroundColor: data.tuesday_color,
                                color: '#fff',
                                cursor: 'pointer',
                            }"
                            v-if="data.tuesday_code"
                            @click="show_details(data, 'tuesday')"
                        >
                        </Badge>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar"
                        /> </template
                ></Column>
                <Column
                    field="tuesday_data"
                    header="Horario Martes"
                    sortable
                    :frozen="frozenColumns.Horario_Martes"
                    :style="{
                        width: '10rem',
                        display: showColumns.Horario_Martes ? '' : 'none',
                    }"
                    :exportable="exportColumns.Horario_Martes"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading" width="100%" height="4rem" />
                        <div
                            v-else
                            class="p-2 rounded-md border border-gray-200 text-xs text-gray-700 leading-tight"
                        >
                            <template v-if="parseJSON(data?.tuesday_data)">
                                <div>
                                    <b>Turno:</b>
                                    {{ parseJSON(data?.tuesday_data)?.Turno }}
                                </div>
                                <div>
                                    <b>Horario:</b>
                                    {{ parseJSON(data?.tuesday_data)?.Horario }}
                                </div>
                                <div>
                                    <b>Entrada:</b>
                                    {{ parseJSON(data?.tuesday_data)?.Entrada }}
                                </div>
                                <div>
                                    <b>Salida:</b>
                                    {{ parseJSON(data?.tuesday_data)?.Salida }}
                                </div>
                                <div>
                                    <b>Dobles:</b>
                                    {{
                                        parseJSON(data?.tuesday_data)?.[
                                            "Horas dobles"
                                        ]
                                    }}
                                </div>
                                <div>
                                    <b>Triples:</b>
                                    {{
                                        parseJSON(data?.tuesday_data)?.[
                                            "Horas triples"
                                        ]
                                    }}
                                </div>
                                <div
                                    v-if="
                                        parseJSON(data?.tuesday_data)?.Checadas
                                            ?.length
                                    "
                                >
                                    <b>Checadas:</b>
                                    <span>
                                        {{
                                            parseJSON(data?.tuesday_data)
                                                ?.Checadas.map(
                                                    (c) => c.access_time,
                                                )
                                                .join(", ")
                                        }}
                                    </span>
                                </div>
                                <div
                                    v-else
                                    class="text-gray-400 italic text-center py-2"
                                >
                                    Sin checadas
                                </div>
                            </template>
                            <template v-else>
                                <div
                                    class="text-center text-gray-400 italic py-2"
                                >
                                    Sin datos
                                </div>
                            </template>
                        </div>
                    </template>

                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar"
                            class="p-inputtext-sm w-full"
                        />
                    </template>
                </Column>
                <Column
                    field="wednesday_code"
                    :header="'Miercoles (' + dates?.wednesday + ')'"
                    sortable
                    :frozen="frozenColumns.Miercoles"
                    :style="{
                        width: '20rem',
                        display: showColumns.Miercoles ? '' : 'none',
                    }"
                    :exportable="exportColumns.Miercoles"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <Badge
                            :value="data.wednesday_code"
                            size="xlarge"
                            severity="success"
                            v-tooltip.top="data.wednesday_incidence"
                            v-else
                            :style="{
                                backgroundColor: data.wednesday_color,
                                color: '#fff',
                                cursor: 'pointer',
                            }"
                            v-if="data.wednesday_code"
                            @click="show_details(data, 'wednesday')"
                        >
                        </Badge>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar"
                        /> </template
                ></Column>
                <Column
                    field="wednesday_data"
                    header="Horario Miercoles"
                    sortable
                    :frozen="frozenColumns.Horario_Miercoles"
                    :style="{
                        width: '10rem',
                        display: showColumns.Horario_Miercoles ? '' : 'none',
                    }"
                    :exportable="exportColumns.Horario_Miercoles"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading" width="100%" height="4rem" />
                        <div
                            v-else
                            class="p-2 rounded-md border border-gray-200 text-xs text-gray-700 leading-tight"
                        >
                            <template v-if="parseJSON(data?.wednesday_data)">
                                <div>
                                    <b>Turno:</b>
                                    {{ parseJSON(data?.wednesday_data)?.Turno }}
                                </div>
                                <div>
                                    <b>Horario:</b>
                                    {{
                                        parseJSON(data?.wednesday_data)?.Horario
                                    }}
                                </div>
                                <div>
                                    <b>Entrada:</b>
                                    {{
                                        parseJSON(data?.wednesday_data)?.Entrada
                                    }}
                                </div>
                                <div>
                                    <b>Salida:</b>
                                    {{
                                        parseJSON(data?.wednesday_data)?.Salida
                                    }}
                                </div>
                                <div>
                                    <b>Dobles:</b>
                                    {{
                                        parseJSON(data?.wednesday_data)?.[
                                            "Horas dobles"
                                        ]
                                    }}
                                </div>
                                <div>
                                    <b>Triples:</b>
                                    {{
                                        parseJSON(data?.wednesday_data)?.[
                                            "Horas triples"
                                        ]
                                    }}
                                </div>
                                <div
                                    v-if="
                                        parseJSON(data?.wednesday_data)
                                            ?.Checadas?.length
                                    "
                                >
                                    <b>Checadas:</b>
                                    <span>
                                        {{
                                            parseJSON(data?.wednesday_data)
                                                .Checadas.map(
                                                    (c) => c?.access_time,
                                                )
                                                .join(", ")
                                        }}
                                    </span>
                                </div>
                                <div
                                    v-else
                                    class="text-gray-400 italic text-center py-2"
                                >
                                    Sin checadas
                                </div>
                            </template>
                            <template v-else>
                                <div
                                    class="text-center text-gray-400 italic py-2"
                                >
                                    Sin datos
                                </div>
                            </template>
                        </div>
                    </template>

                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar"
                            class="p-inputtext-sm w-full"
                        />
                    </template>
                </Column>
                <Column
                    field="thursday_code"
                    :header="'Jueves (' + dates?.thursday + ')'"
                    sortable
                    :frozen="frozenColumns.Jueves"
                    :style="{
                        width: '20rem',
                        display: showColumns.Jueves ? '' : 'none',
                    }"
                    :exportable="exportColumns.Jueves"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <Badge
                            :value="data.thursday_code"
                            size="xlarge"
                            severity="success"
                            v-tooltip.top="data.thursday_incidence"
                            v-else
                            :style="{
                                backgroundColor: data.thursday_color,
                                color: '#fff',
                                cursor: 'pointer',
                            }"
                            v-if="data.thursday_code"
                            @click="show_details(data, 'thursday')"
                        >
                        </Badge>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar"
                        /> </template
                ></Column>
                <Column
                    field="thursday_data"
                    header="Horario Jueves"
                    sortable
                    :frozen="frozenColumns.Horario_Jueves"
                    :style="{
                        width: '10rem',
                        display: showColumns.Horario_Jueves ? '' : 'none',
                    }"
                    :exportable="exportColumns.Horario_Jueves"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading" width="100%" height="4rem" />
                        <div
                            v-else
                            class="p-2 rounded-md border border-gray-200 text-xs text-gray-700 leading-tight"
                        >
                            <template v-if="parseJSON(data?.thursday_data)">
                                <div>
                                    <b>Turno:</b>
                                    {{ parseJSON(data?.thursday_data)?.Turno }}
                                </div>
                                <div>
                                    <b>Horario:</b>
                                    {{
                                        parseJSON(data?.thursday_data)?.Horario
                                    }}
                                </div>
                                <div>
                                    <b>Entrada:</b>
                                    {{
                                        parseJSON(data?.thursday_data)?.Entrada
                                    }}
                                </div>
                                <div>
                                    <b>Salida:</b>
                                    {{ parseJSON(data?.thursday_data)?.Salida }}
                                </div>
                                <div>
                                    <b>Dobles:</b>
                                    {{
                                        parseJSON(data?.thursday_data)?.[
                                            "Horas dobles"
                                        ]
                                    }}
                                </div>
                                <div>
                                    <b>Triples:</b>
                                    {{
                                        parseJSON(data?.thursday_data)?.[
                                            "Horas triples"
                                        ]
                                    }}
                                </div>
                                <div
                                    v-if="
                                        parseJSON(data?.thursday_data)?.Checadas
                                            ?.length
                                    "
                                >
                                    <b>Checadas:</b>
                                    <span>
                                        {{
                                            parseJSON(data?.thursday_data)
                                                .Checadas.map(
                                                    (c) => c?.access_time,
                                                )
                                                .join(", ")
                                        }}
                                    </span>
                                </div>
                                <div
                                    v-else
                                    class="text-gray-400 italic text-center py-2"
                                >
                                    Sin checadas
                                </div>
                            </template>
                            <template v-else>
                                <div
                                    class="text-center text-gray-400 italic py-2"
                                >
                                    Sin datos
                                </div>
                            </template>
                        </div>
                    </template>

                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar"
                            class="p-inputtext-sm w-full"
                        />
                    </template>
                </Column>
                <Column
                    field="friday_code"
                    :header="'Viernes (' + dates?.friday + ')'"
                    sortable
                    :frozen="frozenColumns.Viernes"
                    :style="{
                        width: '20rem',
                        display: showColumns.Viernes ? '' : 'none',
                    }"
                    :exportable="exportColumns.Viernes"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <Badge
                            :value="data.friday_code"
                            size="xlarge"
                            severity="success"
                            v-tooltip.top="data.friday_incidence"
                            v-else
                            :style="{
                                backgroundColor: data.friday_color,
                                color: '#fff',
                                cursor: 'pointer',
                            }"
                            v-if="data.friday_code"
                            @click="show_details(data, 'friday')"
                        >
                        </Badge>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar"
                        /> </template
                ></Column>
                <Column
                    field="friday_data"
                    header="Horario Viernes"
                    sortable
                    :frozen="frozenColumns.Horario_Viernes"
                    :style="{
                        width: '10rem',
                        display: showColumns.Horario_Viernes ? '' : 'none',
                    }"
                    :exportable="exportColumns.Horario_Viernes"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading" width="100%" height="4rem" />
                        <div
                            v-else
                            class="p-2 rounded-md border border-gray-200 text-xs text-gray-700 leading-tight"
                        >
                            <template v-if="parseJSON(data?.friday_data)">
                                <div>
                                    <b>Turno:</b>
                                    {{ parseJSON(data?.friday_data)?.Turno }}
                                </div>
                                <div>
                                    <b>Horario:</b>
                                    {{ parseJSON(data?.friday_data)?.Horario }}
                                </div>
                                <div>
                                    <b>Entrada:</b>
                                    {{ parseJSON(data?.friday_data)?.Entrada }}
                                </div>
                                <div>
                                    <b>Salida:</b>
                                    {{ parseJSON(data?.friday_data)?.Salida }}
                                </div>
                                <div>
                                    <b>Dobles:</b>
                                    {{
                                        parseJSON(data?.friday_data)?.[
                                            "Horas dobles"
                                        ]
                                    }}
                                </div>
                                <div>
                                    <b>Triples:</b>
                                    {{
                                        parseJSON(data?.thursday_data)?.[
                                            "Horas triples"
                                        ]
                                    }}
                                </div>
                                <div
                                    v-if="
                                        parseJSON(data?.friday_data)?.Checadas
                                            ?.length
                                    "
                                >
                                    <b>Checadas:</b>
                                    <span>
                                        {{
                                            parseJSON(data?.friday_data)
                                                .Checadas.map(
                                                    (c) => c?.access_time,
                                                )
                                                .join(", ")
                                        }}
                                    </span>
                                </div>
                                <div
                                    v-else
                                    class="text-gray-400 italic text-center py-2"
                                >
                                    Sin checadas
                                </div>
                            </template>
                            <template v-else>
                                <div
                                    class="text-center text-gray-400 italic py-2"
                                >
                                    Sin datos
                                </div>
                            </template>
                        </div>
                    </template>

                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar"
                            class="p-inputtext-sm w-full"
                        />
                    </template>
                </Column>
                <Column
                    field="saturday_code"
                    :header="'Sabado (' + dates?.saturday + ')'"
                    sortable
                    :frozen="frozenColumns.Sabado"
                    :style="{
                        width: '20rem',
                        display: showColumns.Sabado ? '' : 'none',
                    }"
                    :exportable="exportColumns.Sabado"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <Badge
                            :value="data.saturday_code"
                            size="xlarge"
                            severity="success"
                            v-tooltip.top="data.saturday_incidence"
                            v-else
                            :style="{
                                backgroundColor: data.saturday_color,
                                color: '#fff',
                                cursor: 'pointer',
                            }"
                            v-if="data.saturday_code"
                            @click="show_details(data, 'saturday')"
                        >
                        </Badge>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar"
                        /> </template
                ></Column>
                <Column
                    field="saturday_data"
                    header="Horario Sabado"
                    sortable
                    :frozen="frozenColumns.Horario_Sabado"
                    :style="{
                        width: '10rem',
                        display: showColumns.Horario_Sabado ? '' : 'none',
                    }"
                    :exportable="exportColumns['Horario Sabado']"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading" width="100%" height="4rem" />
                        <div
                            v-else
                            class="p-2 rounded-md border border-gray-200 text-xs text-gray-700 leading-tight"
                        >
                            <template v-if="parseJSON(data?.saturday_data)">
                                <div>
                                    <b>Turno:</b>
                                    {{ parseJSON(data?.saturday_data)?.Turno }}
                                </div>
                                <div>
                                    <b>Horario:</b>
                                    {{
                                        parseJSON(data?.saturday_data)?.Horario
                                    }}
                                </div>
                                <div>
                                    <b>Entrada:</b>
                                    {{
                                        parseJSON(data?.saturday_data)?.Entrada
                                    }}
                                </div>
                                <div>
                                    <b>Salida:</b>
                                    {{ parseJSON(data?.saturday_data)?.Salida }}
                                </div>
                                <div>
                                    <b>Dobles:</b>
                                    {{
                                        parseJSON(data?.saturday_data)?.[
                                            "Horas dobles"
                                        ]
                                    }}
                                </div>
                                <div>
                                    <b>Triples:</b>
                                    {{
                                        parseJSON(data?.saturday_data)?.[
                                            "Horas triples"
                                        ]
                                    }}
                                </div>
                                <div
                                    v-if="
                                        parseJSON(data?.saturday_data)?.Checadas
                                            ?.length
                                    "
                                >
                                    <b>Checadas:</b>
                                    <span>
                                        {{
                                            parseJSON(data?.saturday_data)
                                                .Checadas.map(
                                                    (c) => c?.access_time,
                                                )
                                                .join(", ")
                                        }}
                                    </span>
                                </div>
                                <div
                                    v-else
                                    class="text-gray-400 italic text-center py-2"
                                >
                                    Sin checadas
                                </div>
                            </template>
                            <template v-else>
                                <div
                                    class="text-center text-gray-400 italic py-2"
                                >
                                    Sin datos
                                </div>
                            </template>
                        </div>
                    </template>

                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar"
                            class="p-inputtext-sm w-full"
                        />
                    </template>
                </Column>
                <Column
                    field="sunday_code"
                    :header="'Domingo (' + dates?.sunday + ')'"
                    sortable
                    :frozen="frozenColumns.Domingo"
                    :style="{
                        width: '20rem',
                        display: showColumns.Domingo ? '' : 'none',
                    }"
                    :exportable="exportColumns.Domingo"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <Badge
                            :value="data.sunday_code"
                            size="xlarge"
                            severity="success"
                            v-tooltip.top="data.sunday_incidence"
                            v-else
                            :style="{
                                backgroundColor: data.sunday_color,
                                color: '#fff',
                                cursor: 'pointer',
                            }"
                            v-if="data.sunday_code"
                            @click="show_details(data, 'sunday')"
                        >
                        </Badge>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar"
                        /> </template
                ></Column>
                <Column
                    field="sunday_data"
                    header="Horario Domingo"
                    sortable
                    :frozen="frozenColumns.Horario_Domingo"
                    :style="{
                        width: '10rem',
                        display: showColumns.Horario_Domingo ? '' : 'none',
                    }"
                    :exportable="exportColumns.Horario_Domingo"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading" width="100%" height="4rem" />
                        <div
                            v-else
                            class="p-2 rounded-md border border-gray-200 text-xs text-gray-700 leading-tight"
                        >
                            <template v-if="parseJSON(data?.sunday_data)">
                                <div>
                                    <b>Turno:</b>
                                    {{ parseJSON(data?.sunday_data)?.Turno }}
                                </div>
                                <div>
                                    <b>Horario:</b>
                                    {{ parseJSON(data?.sunday_data)?.Horario }}
                                </div>
                                <div>
                                    <b>Entrada:</b>
                                    {{ parseJSON(data?.sunday_data)?.Entrada }}
                                </div>
                                <div>
                                    <b>Salida:</b>
                                    {{ parseJSON(data?.saturday_data)?.Salida }}
                                </div>
                                <div>
                                    <b>Dobles:</b>
                                    {{
                                        parseJSON(data?.saturday_data)?.[
                                            "Horas dobles"
                                        ]
                                    }}
                                </div>
                                <div>
                                    <b>Triples:</b>
                                    {{
                                        parseJSON(data?.saturday_data)?.[
                                            "Horas triples"
                                        ]
                                    }}
                                </div>
                                <div
                                    v-if="
                                        parseJSON(data?.saturday_data)?.Checadas
                                            ?.length
                                    "
                                >
                                    <b>Checadas:</b>
                                    <span>
                                        {{
                                            parseJSON(data?.saturday_data)
                                                .Checadas.map(
                                                    (c) => c?.access_time,
                                                )
                                                .join(", ")
                                        }}
                                    </span>
                                </div>
                                <div
                                    v-else
                                    class="text-gray-400 italic text-center py-2"
                                >
                                    Sin checadas
                                </div>
                            </template>
                            <template v-else>
                                <div
                                    class="text-center text-gray-400 italic py-2"
                                >
                                    Sin datos
                                </div>
                            </template>
                        </div>
                    </template>

                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar"
                            class="p-inputtext-sm w-full"
                        />
                    </template>
                </Column>
                <Column
                    field="total_horas_dobles"
                    header="Horas Dobles"
                    :filter="true"
                    :frozen="frozenColumns.Dobles"
                    :style="{
                        width: '1rem',
                        display: showColumns.Dobles ? '' : 'none',
                    }"
                    :exportable="exportColumns.Dobles"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.total_horas_dobles }}</span>
                    </template>
                </Column>
                <Column
                    field="total_horas_triples"
                    header="Horas Triples"
                    :filter="true"
                    :frozen="frozenColumns.Triples"
                    :style="{
                        width: '1rem',
                        display: showColumns.Triples ? '' : 'none',
                    }"
                    :exportable="exportColumns.Triples"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.total_horas_triples }}</span>
                    </template>
                </Column>
                <Column
                    field="faltas"
                    header="Faltas"
                    sortable
                    :frozen="frozenColumns.Faltas"
                    :style="{
                        width: '20rem',
                        display: showColumns.Faltas ? '' : 'none',
                    }"
                    :exportable="exportColumns.Faltas"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.faltas }}</span>
                    </template>

                    ></Column
                >
                <Column
                    field="prima_dominical"
                    header="Prima Dominical"
                    sortable
                    :frozen="frozenColumns.Prima_Domical"
                    :style="{
                        width: '20rem',
                        display: showColumns.Prima_Domical ? '' : 'none',
                    }"
                    :exportable="exportColumns.Prima_Domical"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.sunday_premium }}</span>
                    </template>

                    ></Column
                >
            </DataTable>

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
                        :loading="submitted"
                    />
                </template>
            </Dialog>

            <Dialog
                v-model:visible="otherFilterDialog"
                :style="{ width: '450px' }"
                header="Filtros"
                :modal="true"
            >
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Planta
                    </label>
                    <Select
                        v-model="selectedBranchOfficeId"
                        display="chip"
                        :options="branch_offices"
                        optionLabel="code"
                        filter
                        placeholder="Selecciona una planta"
                        class="w-full"
                    />
                </div>
                <div>
                    <label
                        class="font-semibold text-sm text-gray-600 dark:text-gray-300"
                    >
                        Semana
                    </label>
                    <InputText
                        type="week"
                        v-model="selectedWeek"
                        class="w-full"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Departamentos
                    </label>
                    <Multiselect
                        v-model="selectedDeparments"
                        display="chip"
                        :options="deparments"
                        optionLabel="name"
                        filter
                        optionValue="id"
                        placeholder="Selecciona un departamento"
                        class="w-full"
                    >
                        <template #value="slotProps">
                            <span
                                v-if="
                                    !slotProps.value || !slotProps.value.length
                                "
                            >
                                Selecciona un departamento
                            </span>

                            <span v-else-if="slotProps.value.length > 5">
                                {{ slotProps.value.length }} departamentos
                                seleccionados
                            </span>
                        </template>
                    </Multiselect>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Incidencias
                    </label>
                    <Multiselect
                        v-model="selectedIncidences"
                        display="chip"
                        :options="incidences"
                        optionLabel="name"
                        filter
                        optionValue="id"
                        placeholder="Selecciona una incidencia"
                        class="w-full"
                    >
                        <template #value="slotProps">
                            <span
                                v-if="
                                    !slotProps.value || !slotProps.value.length
                                "
                            >
                                Selecciona una incidencia
                            </span>

                            <span v-else-if="slotProps.value.length > 5">
                                {{ slotProps.value.length }} incidencias
                                seleccionadas
                            </span>
                        </template>
                    </Multiselect>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Empleados
                    </label>

                    <Multiselect
                        v-model="selectedEmployees"
                        :options="employees"
                        optionLabel="full_name"
                        optionValue="id"
                        filter
                        :filterFields="['full_name', 'id']"
                        placeholder="Selecciona un empleado"
                        class="w-full"
                        display="chip"
                    >
                        <!-- Texto cuando no hay selección -->
                        <template #value="slotProps">
                            <span
                                v-if="
                                    !slotProps.value ||
                                    slotProps.value.length === 0
                                "
                            >
                                Selecciona un empleado
                            </span>

                            <span v-else-if="slotProps.value.length > 5">
                                {{ slotProps.value.length }} empleados
                                seleccionados
                            </span>
                        </template>

                        <!-- Personalizar cómo se muestran las opciones -->
                        <template #option="{ option }">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-gray-700"
                                    >({{ option.id }})</span
                                >
                                <span>{{ option.full_name }}</span>
                            </div>
                        </template>
                    </Multiselect>
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
                        @click="filter_data"
                        :loading="submitted"
                    />
                </template>
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
                                <div
                                    class="flex items-center justify-between mb-2"
                                >
                                    <h4
                                        class="font-semibold text-gray-700 text-sm"
                                    >
                                        Incidencia
                                    </h4>
                                    <Badge
                                        :value="details?.incidencia"
                                        :style="{
                                            backgroundColor: details?.color,
                                            color: '#fff',
                                        }"
                                    />
                                </div>
                                <p class="text-sm text-gray-600 leading-snug">
                                    {{ details?.descripcion_incidencia }}
                                </p>
                            </div>
                        </div>
                        <div class="rounded-xl shadow-sm border p-4">
                            <div
                                class="rounded-t-lg px-4 py-2 border-b text-gray-600 font-semibold"
                            >
                                Historial de checadas
                            </div>

                            <div class="p-4 rounded-lg shadow-sm">
                                <div class="space-y-3">
                                    <div v-for="(item, index) in details?.horario?.Checadas || []" :key="index" class="flex items-start gap-3 relative">
                                        <div
                                            class="absolute left-4 top-6 bottom-0 w-px"
                                            v-if="
                                                index <
                                                details.horario.length - 1
                                            "
                                        ></div>
                                        <div
                                            class="w-8 h-8 flex items-center justify-center rounded-full border-purple-200 z-10"
                                        >
                                            <i
                                                class="pi pi-clock text-purple-500 text-xs"
                                            ></i>
                                        </div>
                                        <div
                                            class="flex-1 rounded-md p-3 border border-gray-200"
                                        >
                                            <div
                                                class="flex justify-between items-center mb-1"
                                            >
                                                <p
                                                    class="text-sm font-semibold text-gray-700"
                                                >
                                                    {{ item?.device_name }}
                                                </p>
                                                <span
                                                    class="text-xs text-gray-400"
                                                >
                                                    Checada {{ index + 1 }}
                                                </span>
                                            </div>

                                            <div
                                                class="flex gap-4 text-sm text-gray-600"
                                            >
                                                <div>
                                                    📅
                                                    <b>{{
                                                        item?.access_date
                                                    }}</b>
                                                </div>
                                                <div>
                                                    ⏰
                                                    <b>{{
                                                        item?.access_time
                                                    }}</b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
                v-model:visible="modalTurns"
                :style="{ width: '450px' }"
                header="Revisar turnos"
                :modal="true"
            >
                <div class="grid grid-cols-2 gap-6">
                    <div class="flex flex-col gap-2">
                        <label class="font-semibold text-sm text-gray-600"
                            >Fecha inicio</label
                        >
                        <DatePicker
                            v-model="startDate"
                            dateFormat="yy-mm-dd"
                            placeholder="Inicio"
                            showIcon
                        />
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="font-semibold text-sm text-gray-600"
                            >Fecha fin</label
                        >
                        <DatePicker
                            v-model="endDate"
                            dateFormat="yy-mm-dd"
                            placeholder="Fin"
                            showIcon
                        />
                    </div>
                </div>

                <template #footer>
                    <Button
                        label="Cancelar"
                        icon="pi pi-times"
                        severity="danger"
                        @click="modalTurns = false"
                    />
                    <Button
                        v-if="selected.length >= 1"
                        label="Revisar"
                        icon="pi pi-send"
                        severity="success"
                        @click="revisarIncidencias"
                        :loading="submitted"
                        style="color: #fff"
                    />
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>
