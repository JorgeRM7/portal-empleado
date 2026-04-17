<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToastService } from "@/Stores/toastService";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import { onMounted, ref } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { useNotifications } from "@/composables/useNotifications";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    history: Array,
});

const otherSubject = ref("");
const selectedOption = ref(null);
const selectedOptionStatus = ref(null);
const editDialog = ref(false);
const editingQueja = ref(null);
// campos
const editSelectedOption = ref(null);
const editComplaintDescription = ref("");
const editOtherSubject = ref("");
// IA
const editOpciones = ref([]);
const editSeleccionada = ref(null);
const editLoading = ref(false);
const editIsCollapsed = ref(false);
// archivos
const editExistingFiles = ref([]);
const editNewFiles = ref([]);
const editRemovedFiles = ref([]);
const deleteDialog = ref(false);
const quejaAEliminar = ref(null);

const updating = ref(false);
const deleting = ref(false);

const dates = ref(null);
const rows = ref([{}]);

const { sendNotification } = useNotifications();

const crearQueja = () => {
    router.get("/complaints/create");
};

const formatFileItems = (archivos) => {
    return archivos.map((file) => ({
        label: file.name,
        icon: "pi pi-external-link",
        command: () => {
            window.open(file.url, "_blank");
        },
    }));
};

const viewFirstFile = (url) => {
    window.open(url, "_blank");
};

const getStatusSeverity = (status) => {
    switch (status?.toLowerCase()) {
        case "pendiente":
            return "warn";

        case "escalado":
            return "info";

        case "resuelto":
            return "success";

        default:
            return "secondary";
    }
};

const getStatusIcon = (status) => {
    switch (status?.toLowerCase()) {
        case "pendiente":
            return "pi pi-clock";

        case "escalado":
            return "pi pi-arrow-up-right";

        case "resuelto":
            return "pi pi-check-circle";

        default:
            return "pi pi-info-circle";
    }
};

const options = ref([
    { name: "Seleccione un Asunto...", code: "" },
    { name: "Nomina", code: "NOM" },
    { name: "Compensaciones", code: "COM" },
    { name: "Tiempos extra", code: "EXT" },
    { name: "Aclaración de Incidencias", code: "ACI" },
    { name: "Cambio de datos personales", code: "CDP" },
    { name: "Cambio de datos bancarios", code: "CDB" },
    { name: "Vacaciones", code: "VAC" },
    { name: "Descuento de créditos y pensiones", code: "DCP" },
    { name: "Despensas y Vales de despensa", code: "DVD" },
    { name: "Incentivos anuales", code: "INC" },
    {
        name: "Constancias (Fonacot, Infonavit, Antigüedad, Visa, Escolar, Otro tipo)",
        code: "CON",
    },
    { name: "Otros", code: "OTR" },
]);

const optionsStatus = ref([
    { name: "Pendiente", code: "PE" },
    { name: "Escalado", code: "ES" },
    { name: "Resuelto", code: "RE" },
]);

const editarQueja = (queja) => {
    editingQueja.value = queja;

    editComplaintDescription.value = queja.case;

    const match = options.value.find(
        (o) => o.name.toLowerCase() === queja.subject?.toLowerCase(),
    );

    if (match && match.code !== "") {
        editSelectedOption.value = match;
        editOtherSubject.value = "";
    } else {
        const otherOption = options.value.find((o) => o.code === "OTR");

        editSelectedOption.value = otherOption;
        editOtherSubject.value = queja.subject;
    }

    editExistingFiles.value = queja.archivos || [];
    editNewFiles.value = [];
    editRemovedFiles.value = [];

    editOpciones.value = [];
    editSeleccionada.value = null;
    editIsCollapsed.value = false;

    editDialog.value = true;
};

const usarEdit = (op) => {
    editComplaintDescription.value = op;
    editSeleccionada.value = op;
    editIsCollapsed.value = true;

    toast.add({
        severity: "success",
        summary: "Texto actualizado",
        detail: "Se aplicó la redacción sugerida.",
        life: 2000,
    });
};

const mejorarTextoEdit = async () => {
    if (
        !editComplaintDescription.value.trim() ||
        editComplaintDescription.value.length > 300
    ) {
        toast.add({
            severity: "warn",
            summary: "Atención",
            detail: "El texto debe tener entre 1 y 300 caracteres.",
            life: 3000,
        });
        return;
    }

    editLoading.value = true;
    editOpciones.value = [];

    try {
        const res = await axios.post("/complaints/improve-writing", {
            texto: editComplaintDescription.value,
            asunto_cod: editSelectedOption.value?.code,
            asunto_texto:
                editSelectedOption.value?.code === "OTR"
                    ? editOtherSubject.value
                    : editSelectedOption.value?.name,
        });

        if (res.data.success) {
            editOpciones.value = res.data.opciones;
            editSeleccionada.value = null;
            editIsCollapsed.value = false;
        }
    } catch (error) {
        console.error(error);
        showError("Error con IA");
    } finally {
        editLoading.value = false;
    }
};

const removeExistingFile = (file) => {
    editExistingFiles.value = editExistingFiles.value.filter((f) => f !== file);

    editRemovedFiles.value.push(file.name);
};

const onEditFiles = (event) => {
    editNewFiles.value = event.files;
};

const actualizarQueja = async () => {
    updating.value = true;
    try {
        const formData = new FormData();

        formData.append("_method", "PUT");

        formData.append("descripcion", editComplaintDescription.value);

        const asuntoFinal =
            editSelectedOption.value?.code === "OTR"
                ? editOtherSubject.value
                : editSelectedOption.value?.name;

        formData.append("asunto_texto", asuntoFinal);

        // 🔹 nuevos archivos
        editNewFiles.value.forEach((file) => {
            formData.append("archivos[]", file);
        });

        // 🔹 eliminados
        editRemovedFiles.value.forEach((id) => {
            formData.append("deleted_files[]", id);
        });

        await axios.post(`/complaints/${editingQueja.value.id}`, formData, {
            headers: { "Content-Type": "multipart/form-data" },
        });

        showSuccess("Queja actualizada");
        editDialog.value = false;

        router.reload();
    } catch (error) {
        console.error(error);
        showError("Error al actualizar");
    } finally {
        updating.value = false;
    }
};

const truncateText = (text, length = 50) => {
    if (!text) return "";
    return text.length > length ? text.substring(0, length) + "..." : text;
};

const confirmarEliminar = (queja) => {
    quejaAEliminar.value = queja;
    deleteDialog.value = true;
};

const eliminarQueja = async () => {
    deleting.value = true;
    try {
        await axios.delete(`/complaints/${quejaAEliminar.value.id}`);

        showSuccess();

        deleteDialog.value = false;

        aplicarFiltros();
    } catch (error) {
        console.error(error);
        showError("Error al eliminar la queja");
    } finally {
        deleting.value = false;
    }
};

//Inicializar filtros globales de tabla
const filters = ref({});

//Inicializar variable de carga para mostrar esqueleto
const loading = ref(true);

//Función para mostrar toast de éxito y error
const { showSuccess, showError } = useToastService();

//Función para inicializar filtros
const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },

        id: { value: null, matchMode: FilterMatchMode.CONTAINS },
        employee_id: { value: null, matchMode: FilterMatchMode.CONTAINS },
        subject: { value: null, matchMode: FilterMatchMode.CONTAINS },
        case: { value: null, matchMode: FilterMatchMode.CONTAINS },
        date: { value: null, matchMode: FilterMatchMode.CONTAINS },
        hour: { value: null, matchMode: FilterMatchMode.CONTAINS },
        status: { value: null, matchMode: FilterMatchMode.CONTAINS },
    };
};

initFilters();

//Columnas de exportación
const exportColumns = ref({
    id: true,
    clave_empleado: true,
    asunto: true,
    queja: true,
    fecha_hora: true,
    estatus: true,
    evidencia: true,
    respuesta: true,
});

//Columnas visibles
const showColumns = ref({
    acciones: true,
    id: true,
    clave_empleado: true,
    asunto: true,
    queja: true,
    fecha_hora: true,
    estatus: true,
    evidencia: true,
    respuesta: true,
});

//Filtros adicionales
const otherFilters = ref({
    startDate: null,
    endDate: null,
    status: null,
    subject: null,
});

//Diálogo de filtros adicionales
const otherFilterDialog = ref(false);

//Referencia a la tabla de datos
const dt = ref(null);
const toast = useToast();

//Columnas fijas
const frozenColumns = ref({
    acciones: false,
    id: false,
    clave_empleado: false,
    asunto: false,
    queja: false,
    fecha_hora: false,
    estatus: false,
    evidencia: false,
    respuesta: false,
});

//Diálogo de selección de columnas
const columnsDialog = ref(false);

//Estado de envío
const submitted = ref(false);

//Referencias a los popovers
const op = ref();
const opMostrarColumnas = ref();
const opFijarColumnas = ref();

//Estado de progreso de subida
const progress = ref(0);

//Estado de visibilidad del toast
const visible = ref(false);

const toggleMostrarColumnas = (event) => {
    opMostrarColumnas.value.toggle(event);
};

const toggleFijarColumnas = (event) => {
    opFijarColumnas.value.toggle(event);
};

//Filas seleccionadas
const selected = ref([]);

//Función para guardar las columnas seleccionadas para exportar
const saveColumns = () => {
    columnsDialog.value = false;

    dt.value.exportCSV();
};

//Función para limpiar filtros
const clearFilter = () => {
    initFilters();

    otherFilters.value = {
        startDate: null,
        endDate: null,
        status: null,
        subject: null,
    };

    dates.value = null;
    selectedOption.value = null;
    selectedOptionStatus.value = null;
    otherSubject.value = "";

    aplicarFiltros();
};

const applyFilters = () => {
    otherFilterDialog.value = false;

    const formatDate = (date) => {
        if (!date) return null;

        const d = new Date(date);
        const month = "" + (d.getMonth() + 1);
        const day = "" + d.getDate();
        const year = d.getFullYear();

        return [year, month.padStart(2, "0"), day.padStart(2, "0")].join("-");
    };

    otherFilters.value.startDate = dates.value?.[0]
        ? formatDate(dates.value[0])
        : null;

    otherFilters.value.endDate = dates.value?.[1]
        ? formatDate(dates.value[1])
        : null;

    otherFilters.value.status = selectedOptionStatus.value?.code || null;

    otherFilters.value.subject =
        selectedOption.value?.code === "OTR"
            ? otherSubject.value
            : selectedOption.value?.code || null;

    aplicarFiltros();
};

const aplicarFiltros = async () => {
    loading.value = true;

    try {
        const res = await axios.get("/complaints/filter-data", {
            params: otherFilters.value,
        });

        rows.value = res.data.data;
        console.log(rows.value);
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
};

const removeStartDate = () => {
    otherFilters.value.startDate = null;
    dates.value = null;

    aplicarFiltros();
};

onMounted(async () => {
    aplicarFiltros();
});
</script>

<template>
    <AppLayout :title="'Prueba'">
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
                        label="Crear"
                        icon="pi pi-plus-circle"
                        severity="success"
                        class="ml-2"
                        @click="crearQueja"
                    />
                </template>
            </Toolbar>
            <DataTable
                ref="dt"
                v-model:selection="selected"
                :value="rows"
                dataKey="id"
                :paginator="true"
                :rows="10"
                scrollable
                scrollHeight="400px"
                tableStyle="min-width: 110rem"
                v-model:filters="filters"
                filterDisplay="menu"
                exportFilename="Historial_de_Quejas"
                :globalFilterFields="[
                    'id',
                    'employee_id',
                    'subject',
                    'case',
                    'date',
                    'hour',
                    'status',
                ]"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} datos de Quejas"
            >
                <template #header>
                    <div
                        class="flex flex-wrap gap-2 items-end justify-between mb-6"
                    >
                        <div>
                            <h4 class="m-0">Historial de Quejas</h4>
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
                        <Chip
                            v-if="otherFilters.status"
                            :label="'Estatus: ' + otherFilters.status"
                            removable
                            @remove="
                                () => {
                                    otherFilters.status = null;
                                    selectedOptionStatus = null;
                                    aplicarFiltros();
                                }
                            "
                        />

                        <Chip
                            v-if="otherFilters.subject"
                            :label="'Asunto: ' + otherFilters.subject"
                            removable
                            @remove="
                                () => {
                                    otherFilters.subject = null;
                                    selectedOption = null;
                                    otherSubject = '';
                                    aplicarFiltros();
                                }
                            "
                        />

                        <Chip
                            v-if="otherFilters.startDate"
                            :label="'Inicio: ' + otherFilters.startDate"
                            removable
                            @remove="removeStartDate"
                        />

                        <Chip
                            v-if="otherFilters.endDate"
                            :label="'Fin: ' + otherFilters.endDate"
                            removable
                            @remove="
                                () => {
                                    otherFilters.endDate = null;
                                    dates = null;
                                    aplicarFiltros();
                                }
                            "
                        />
                    </div>
                </template>

                <!-- <Column
                    selectionMode="multiple"
                    style="width: 5rem"
                    :exportable="false"
                ></Column>
                <Column
                    :exportable="false"
                    :style="{
                        width: '22rem',
                        display: showColumns.acciones ? '' : 'none',
                    }"
                    header="Acciones"
                    :frozen="frozenColumns.acciones"
                >
                    <template #body="slotProps">
                        <Skeleton v-if="loading"></Skeleton>
                        <div v-else>
                            <Button
                                icon="pi pi-pencil"
                                class="mr-2"
                                rounded
                                v-tooltip.top="'Editar'"
                                severity="warn"
                                @click="editarQueja(slotProps.data)"
                            />
                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                v-tooltip.top="'Eliminar'"
                                class="mr-2"
                                rounded
                                @click="confirmarEliminar(slotProps.data)"
                            />
                        </div>
                    </template>
                </Column> -->
                <Column
                    field="id"
                    header="ID"
                    :filter="true"
                    :frozen="frozenColumns.id"
                    :style="{
                        width: '20rem',
                        display: showColumns.id ? '' : 'none',
                    }"
                    :exportable="exportColumns.id"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.id }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por ID"
                        />
                    </template>
                </Column>
                <Column
                    field="employee_id"
                    header="Clave de empleado"
                    :filter="true"
                    :frozen="frozenColumns.clave_empleado"
                    :style="{
                        width: '20rem',
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
                            placeholder="Buscar por Clave de empleado"
                        />
                    </template>
                </Column>
                <Column
                    field="subject"
                    header="Asunto"
                    :frozen="frozenColumns.asunto"
                    sortable
                    :style="{
                        width: '20rem',
                        display: showColumns.asunto ? '' : 'none',
                    }"
                    :exportable="exportColumns.asunto"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.subject }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Asunto"
                        />
                    </template>
                </Column>

                <Column
                    field="case"
                    header="Queja"
                    sortable
                    :frozen="frozenColumns.queja"
                    :style="{
                        width: '20rem',
                        display: showColumns.queja ? '' : 'none',
                    }"
                    :exportable="exportColumns.queja"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>

                        <div v-else>
                            <span
                                v-tooltip.top="data.case"
                                class="cursor-pointer"
                            >
                                {{ truncateText(data.case, 60) }}
                            </span>

                            <span
                                v-if="data.case && data.case.length > 60"
                                class="text-blue-500 ml-2 cursor-pointer"
                                v-tooltip.bottom="data.case"
                            >
                                Leer más
                            </span>
                        </div>
                    </template>

                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Queja"
                        />
                    </template>
                </Column>
                <Column
                    field="response"
                    header="Respuesta"
                    sortable
                    :frozen="frozenColumns.respuesta"
                    :style="{
                        width: '20rem',
                        display: showColumns.respuesta ? '' : 'none',
                    }"
                    :exportable="exportColumns.respuesta"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>

                        <div v-else>
                            <span
                                v-tooltip.top="data.case"
                                class="cursor-pointer"
                            >
                                {{ truncateText(data.response, 60) }}
                            </span>

                            <span
                                v-if="
                                    data.response && data.response.length > 60
                                "
                                class="text-blue-500 ml-2 cursor-pointer"
                                v-tooltip.bottom="data.response"
                            >
                                Leer más
                            </span>
                        </div>
                    </template>

                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Queja"
                        />
                    </template>
                </Column>
                <Column
                    field="date"
                    header="Fecha y Hora"
                    sortable
                    :frozen="frozenColumns.fecha_hora"
                    :style="{
                        width: '20rem',
                        display: showColumns.fecha_hora ? '' : 'none',
                    }"
                    :exportable="exportColumns.fecha_hora"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.date + " " + data.hour }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Fecha y Hora"
                        /> </template
                ></Column>
                <!-- <Column
                    field="hour"
                    header="Hora"
                    sortable
                    :frozen="frozenColumns.hora"
                    :style="{
                        width: '20rem',
                        display: showColumns.hora ? '' : 'none',
                    }"
                    :exportable="exportColumns.hora"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.hour }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Hora"
                        /> </template
                ></Column> -->
                <Column
                    field="status"
                    header="Estatus"
                    sortable
                    :frozen="frozenColumns.estatus"
                    :style="{
                        width: '20rem',
                        display: showColumns.estatus ? '' : 'none',
                    }"
                    :exportable="exportColumns.estatus"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <div v-else>
                            <Tag
                                :value="data.status"
                                :severity="getStatusSeverity(data.status)"
                                :icon="getStatusIcon(data.status)"
                            />
                        </div>
                    </template>

                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Estatus"
                        />
                    </template>
                </Column>
                <Column
                    field="archivos"
                    header="Evidencia"
                    :frozen="frozenColumns.evidencia"
                    :style="{
                        width: '20rem',
                        display: showColumns.evidencia ? '' : 'none',
                    }"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>

                        <div
                            v-else-if="
                                data.archivos && data.archivos.length > 0
                            "
                        >
                            <SplitButton
                                icon="pi pi-paperclip"
                                severity="info"
                                text
                                size="small"
                                :model="formatFileItems(data.archivos)"
                            />
                        </div>

                        <span v-else class="text-muted-color text-sm">
                            Sin evidencias
                        </span>
                    </template>
                </Column>
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
                header="Seleccionar filtros adicionales"
                :modal="true"
            >
                <div class="flex flex-col gap-2">
                    <Dropdown
                        v-model="selectedOption"
                        :options="options"
                        filter
                        optionLabel="name"
                        placeholder="Seleccione un Asunto..."
                        class="w-full md:w-30rem"
                    >
                        <template #value="slotProps">
                            <div
                                v-if="slotProps.value"
                                class="flex align-items-center"
                            >
                                <div>{{ slotProps.value.name }}</div>
                            </div>
                            <span v-else>
                                {{ slotProps.placeholder }}
                            </span>
                        </template>
                        <template #option="slotProps">
                            <div class="flex align-items-center">
                                <div>{{ slotProps.option.name }}</div>
                            </div>
                        </template>
                    </Dropdown>

                    <div
                        v-if="selectedOption && selectedOption.code === 'OTR'"
                        class="mt-4"
                    >
                        <label
                            for="other_subject"
                            class="block mb-2 font-medium"
                            >Especifique el asunto:</label
                        >
                        <InputText
                            id="other_subject"
                            v-model="otherSubject"
                            placeholder="Escriba el motivo aquí..."
                            class="w-full md:w-30rem"
                        />
                    </div>
                </div>
                <div class="flex flex-col gap-2 mt-2">
                    <Calendar
                        v-model="dates"
                        selectionMode="range"
                        :manualInput="false"
                        placeholder="Rango de fechas"
                    />

                    <Dropdown
                        v-model="selectedOptionStatus"
                        :options="optionsStatus"
                        filter
                        optionLabel="name"
                        placeholder="Seleccione un Estatus..."
                        class="w-full md:w-30rem"
                    >
                        <template #value="slotProps">
                            <div
                                v-if="slotProps.value"
                                class="flex align-items-center"
                            >
                                <div>{{ slotProps.value.name }}</div>
                            </div>
                            <span v-else>
                                {{ slotProps.placeholder }}
                            </span>
                        </template>
                        <template #option="slotProps">
                            <div class="flex align-items-center">
                                <div>{{ slotProps.option.name }}</div>
                            </div>
                        </template>
                    </Dropdown>
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
                        :loading="submitted"
                    />
                </template>
            </Dialog>
            <Dialog
                v-model:visible="editDialog"
                header="Editar Queja"
                :modal="true"
                :style="{ width: '600px' }"
            >
                <!-- ASUNTO -->
                <Dropdown
                    v-model="editSelectedOption"
                    :options="options"
                    optionLabel="name"
                    placeholder="Seleccione un Asunto..."
                    class="w-full mb-3"
                />

                <!-- OTRO -->
                <InputText
                    v-if="editSelectedOption?.code === 'OTR'"
                    v-model="editOtherSubject"
                    placeholder="Especifique el asunto"
                    class="w-full mb-3"
                />

                <!-- DESCRIPCIÓN + IA -->
                <div class="mb-4">
                    <div class="flex justify-end mb-1">
                        <small
                            >{{ editComplaintDescription.length }} / 300</small
                        >
                    </div>

                    <Textarea
                        v-model="editComplaintDescription"
                        rows="4"
                        class="w-full"
                        placeholder="Describe tu Queja..."
                    />

                    <div class="flex justify-center gap-2 mt-3">
                        <Button
                            label="Redactar con IA"
                            icon="pi pi-sparkles"
                            :loading="editLoading"
                            :disabled="!editComplaintDescription.trim()"
                            @click="mejorarTextoEdit"
                        />

                        <Button
                            v-if="editOpciones.length"
                            label="Regenerar"
                            icon="pi pi-refresh"
                            @click="mejorarTextoEdit"
                        />
                    </div>

                    <!-- sugerencias -->
                    <Fieldset
                        v-if="editOpciones.length > 0 && !editLoading"
                        v-model:collapsed="editIsCollapsed"
                        legend="Sugerencias de Redacción"
                        :toggleable="true"
                        class="mt-4"
                    >
                        <TransitionGroup
                            name="fade-slide"
                            tag="div"
                            class="grid gap-3 text-left"
                        >
                            <div
                                v-for="(op, i) in editOpciones"
                                :key="op"
                                @click="usarEdit(op)"
                                :class="[
                                    'p-4 border rounded-xl cursor-pointer transition-all duration-300',
                                    editSeleccionada === op
                                        ? 'shadow-md scale-[1.02] border-green-500 bg-green-50/10'
                                        : 'hover:shadow-lg hover:border-purple-400 hover:scale-[1.01]',
                                ]"
                            >
                                <div
                                    class="flex justify-between items-center mb-2"
                                >
                                    <Tag
                                        :value="`Opción ${i + 1}`"
                                        :severity="
                                            editSeleccionada === op
                                                ? 'success'
                                                : 'secondary'
                                        "
                                        class="text-xs"
                                    />
                                    <i
                                        v-if="editSeleccionada === op"
                                        class="pi pi-check text-green-600"
                                    ></i>
                                </div>

                                <p
                                    class="mb-3 text-sm leading-relaxed text-gray-700"
                                >
                                    {{ op }}
                                </p>

                                <div class="text-right">
                                    <Button
                                        label="Usar este"
                                        size="small"
                                        severity="success"
                                        outlined
                                        @click.stop="usarEdit(op)"
                                    />
                                </div>
                            </div>
                        </TransitionGroup>
                    </Fieldset>
                </div>

                <!-- ARCHIVOS -->
                <div v-if="editExistingFiles.length">
                    <h5>Archivos actuales</h5>

                    <div
                        v-for="file in editExistingFiles"
                        :key="file.id"
                        class="flex justify-between mb-2"
                    >
                        <span>{{ file.name }}</span>
                        <Button
                            icon="pi pi-times"
                            severity="danger"
                            text
                            @click="removeExistingFile(file)"
                        />
                    </div>
                </div>

                <FileUpload
                    v-if="editExistingFiles && editExistingFiles.length > 0"
                    mode="basic"
                    multiple
                    customUpload
                    @select="onEditFiles"
                    chooseLabel="Agregar archivos"
                    class="mt-3"
                />

                <template #footer>
                    <Button
                        label="Cancelar"
                        icon="pi pi-times"
                        severity="danger"
                        @click="editDialog = false"
                    />
                    <Button
                        :label="updating ? 'Actualizando...' : 'Guardar'"
                        :icon="
                            updating ? 'pi pi-spin pi-spinner' : 'pi pi-check'
                        "
                        severity="success"
                        :loading="updating"
                        @click="actualizarQueja"
                    />
                </template>
            </Dialog>
            <Dialog
                v-model:visible="deleteDialog"
                :style="{ width: '600px' }"
                header="Confirmar eliminación?"
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
                                ¿Estás seguro de eliminar esta Queja?
                            </h3>
                            <p
                                :class="{
                                    'text-sm text-red-700': !isDark,
                                    'text-sm text-red-300': isDark,
                                }"
                            >
                                Esta acción eliminará la queja de forma
                                permanente.
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
                    />
                    <Button
                        label="Si"
                        icon="pi pi-check"
                        @click="eliminarQueja"
                        severity="danger"
                        :loading="deleting"
                    />
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>
