<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToastService } from "@/Stores/toastService";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import { onMounted, ref } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { useNotifications } from "@/composables/useNotifications";

const { sendNotification } = useNotifications();

async function crearNotificacion() {
    const { data } = await axios.post("/prueba");

    sendNotification();
}

async function eliminarNotificacion() {
    const { data } = await axios.delete("/prueba");

    sendNotification();
}

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
        rol: { value: null, matchMode: FilterMatchMode.CONTAINS },
        correo: { value: null, matchMode: FilterMatchMode.CONTAINS },
        nombre: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        id: { value: null, matchMode: FilterMatchMode.CONTAINS },
    };
};

initFilters();

//Columnas de exportación
const exportColumns = ref({
    id: true,
    rol: true,
    correo: true,
    nombre: true,
    telefono: true,
    direccion: true,
    ciudad: true,
});

//Columnas visibles
const showColumns = ref({
    acciones: true,
    id: true,
    rol: true,
    correo: true,
    nombre: true,
    telefono: true,
    direccion: true,
    ciudad: true,
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

//Referencia a la tabla de datos
const dt = ref(null);
const toast = useToast();

//Columnas fijas
const frozenColumns = ref({
    acciones: false,
    id: false,
    rol: false,
    correo: false,
    nombre: false,
    telefono: false,
    direccion: false,
    ciudad: false,
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
        id: 1,
        nombre: "",
        correo: "",
        rol: "",
        telefono: "",
        direccion: "",
        ciudad: "",
    },
]);

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
    otherFilters.value = [
        {
            startDate: null,
            endDate: null,
        },
    ];
};

//Función para aplicar filtros adicionales
const applyFilters = () => {
    otherFilterDialog.value = false;

    // Formatear fechas a AAAA-MM-DD
    const formatDate = (date) => {
        const d = new Date(date);
        const month = "" + (d.getMonth() + 1);
        const day = "" + d.getDate();
        const year = d.getFullYear();

        return [year, month.padStart(2, "0"), day.padStart(2, "0")].join("-");
    };
    otherFilters.value = [
        {
            startDate: formatDate(startDate.value),
            endDate: formatDate(endDate.value),
        },
    ];
    console.log("Filtros aplicados:", otherFilters.value);
};

//Función para remover la fecha de inicio
function removeStartDate() {
    loading.value = true;
    otherFilters.value[0].startDate = null;

    setTimeout(() => {
        loading.value = false;
    }, 500);
}

//Función para obtener el ícono del archivo según su extensión
const fileIcon = (file) => {
    const ext = file.name.split(".").pop()?.toLowerCase();
    if (ext === "csv") return "pi-file"; // ícono genérico de archivo
    if (["xls", "xlsx"].includes(ext)) return "pi-file-excel"; // ícono de Excel
    return "pi-file";
};

//Función para manejar la subida personalizada de archivos
const onCustomUploader = async ({ files, options }) => {
    console.log("custom upload", files);

    submitted.value = true;

    if (!visible.value) {
        toast.add({
            severity: "custom",
            summary: "Subiendo archivos.",
            group: "headless",
            styleClass: "backdrop-blur-lg rounded-2xl",
        });
        visible.value = true;
        progress.value = 0;

        if (interval.value) {
            clearInterval(interval.value);
        }

        interval.value = setInterval(() => {
            if (progress.value <= 100) {
                progress.value = progress.value + 20;
            }

            if (progress.value >= 100) {
                progress.value = 100;
                clearInterval(interval.value);
                toast.removeGroup("headless");
                showSuccess();
                openUploadDialog.value = false;
                visible.value = false;
                submitted.value = false;
            }
        }, 1000);
    }
};

//Cargar datos de la API al montar el componente
onMounted(async () => {
    const { data } = await axios.get("/api/prueba");
    rows.value = Array.isArray(data.data) ? data.data : [];

    loading.value = false;

    console.log(rows.value);
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
                        icon="pi pi-download"
                        label="Importar"
                        severity="contrast"
                        class="mt-2 ml-2"
                        @click="openUploadDialog = true"
                    />
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
                        type="button"
                        label="Acciones Masivas"
                        class="min-w-48"
                        icon="pi pi-wrench"
                        @click="toggleAccionesMasivas($event)"
                        :disabled="selected.length === 0"
                    />
                    <Popover ref="op">
                        <div class="flex flex-col gap-4">
                            <div>
                                <span class="font-medium block mb-2"
                                    >Acciones Masivas</span
                                >
                                <ul class="list-none p-0 m-0 flex flex-col">
                                    <li
                                        class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                                    >
                                        <Button
                                            type="button"
                                            icon="pi pi-check-square"
                                            label="Aprobar seleccionados"
                                            severity="success"
                                            text
                                            class="mt-2 ml-2"
                                        />
                                    </li>
                                    <li
                                        class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                                    >
                                        <Button
                                            type="button"
                                            icon="pi pi-times-circle"
                                            label="Rechazar seleccionados"
                                            severity="warn"
                                            text
                                            class="mt-2 ml-2"
                                        />
                                    </li>
                                    <li
                                        class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                                    >
                                        <Button
                                            type="button"
                                            icon="pi pi-trash"
                                            label="Eliminar seleccionados"
                                            severity="danger"
                                            text
                                            class="mt-2 ml-2"
                                        />
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </Popover>
                    <Button
                        label="Crear"
                        icon="pi pi-plus-circle"
                        severity="success"
                        class="ml-2"
                        @click="crearNotificacion"
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
                exportFilename="prueba"
                :globalFilterFields="['id', 'rol', 'correo', 'nombre']"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} datos de prueba"
            >
                <template #header>
                    <div
                        class="flex flex-wrap gap-2 items-end justify-between mb-6"
                    >
                        <div>
                            <h4 class="m-0">Tabla de Prueba</h4>
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
                            :label="
                                'Fecha de inicio: ' + otherFilters[0].startDate
                            "
                            removable
                            v-if="otherFilters[0].startDate != null"
                            @remove="removeStartDate"
                        />
                    </div>
                </template>

                <Column
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
                                @click="() => {}"
                            />
                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                v-tooltip.top="'Eliminar'"
                                class="mr-2"
                                rounded
                                @click="
                                    () => {
                                        eliminarNotificacion();
                                    }
                                "
                            />
                            <Button
                                icon="pi pi-eye"
                                severity="help"
                                v-tooltip.top="'Ver detalles'"
                                rounded
                                @click="() => {}"
                            />
                        </div>
                    </template>
                </Column>
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
                    field="rol"
                    header="Rol"
                    :filter="true"
                    :frozen="frozenColumns.rol"
                    :style="{
                        width: '20rem',
                        display: showColumns.rol ? '' : 'none',
                    }"
                    :exportable="exportColumns.rol"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.rol }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Rol"
                        />
                    </template>
                </Column>
                <Column
                    field="correo"
                    header="Correo"
                    :frozen="frozenColumns.correo"
                    sortable
                    :style="{
                        width: '20rem',
                        display: showColumns.correo ? '' : 'none',
                    }"
                    :exportable="exportColumns.correo"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.correo }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Correo"
                        />
                    </template>
                </Column>

                <Column
                    field="nombre"
                    header="Nombre"
                    sortable
                    :frozen="frozenColumns.nombre"
                    :style="{
                        width: '20rem',
                        display: showColumns.nombre ? '' : 'none',
                    }"
                    :exportable="exportColumns.nombre"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.nombre }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Nombre"
                        /> </template
                ></Column>
                <Column
                    field="telefono"
                    header="Teléfono"
                    sortable
                    :frozen="frozenColumns.telefono"
                    :style="{
                        width: '20rem',
                        display: showColumns.telefono ? '' : 'none',
                    }"
                    :exportable="exportColumns.telefono"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.telefono }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Telefono"
                        /> </template
                ></Column>
                <Column
                    field="direccion"
                    header="Dirección"
                    sortable
                    :frozen="frozenColumns.direccion"
                    :style="{
                        width: '20rem',
                        display: showColumns.direccion ? '' : 'none',
                    }"
                    :exportable="exportColumns.direccion"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.direccion }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Dirección"
                        /> </template
                ></Column>
                <Column
                    field="ciudad"
                    header="Ciudad"
                    sortable
                    :frozen="frozenColumns.ciudad"
                    :style="{
                        width: '20rem',
                        display: showColumns.ciudad ? '' : 'none',
                    }"
                    :exportable="exportColumns.ciudad"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.ciudad }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Ciudad"
                        /> </template
                ></Column>
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
                <div class="flex flex-col gap-6">
                    <DatePicker v-model="startDate" dateFormat="yy-mm-dd" />
                    <DatePicker v-model="endDate" dateFormat="yy-mm-dd" />
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
                v-model:visible="openUploadDialog"
                :style="{ width: '450px' }"
                header="Subir Archivo "
                :modal="true"
            >
                <div class="flex flex-col gap-6">
                    <FileUpload
                        name="files[]"
                        accept=".csv,.xlsx,.xls"
                        :maxFileSize="1000000"
                        :customUpload="true"
                        @uploader="onCustomUploader"
                    >
                        <template
                            #content="{
                                files,
                                uploadedFiles,
                                removeUploadedFileCallback,
                                removeFileCallback,
                            }"
                        >
                            <div v-if="files.length">
                                <h5>Pendientes</h5>
                                <div class="flex flex-wrap gap-4">
                                    <div
                                        v-for="(file, i) in files"
                                        :key="file.name + file.size"
                                        class="p-4 border rounded-md flex flex-col items-center gap-2 w-52"
                                    >
                                        <!-- Ícono en lugar de imagen -->
                                        <i
                                            :class="fileIcon(file)"
                                            class="pi text-5xl"
                                        />
                                        <span
                                            class="font-semibold text-ellipsis max-w-44 whitespace-nowrap overflow-hidden"
                                        >
                                            {{ file.name }}
                                        </span>
                                        <small
                                            >{{
                                                (file.size / 1024).toFixed(0)
                                            }}
                                            KB</small
                                        >

                                        <Button
                                            icon="pi pi-times"
                                            @click="removeFileCallback(i)"
                                            rounded
                                            severity="danger"
                                            variant="outlined"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div v-if="uploadedFiles.length" class="mt-6">
                                <h5>Completados</h5>
                                <div class="flex flex-wrap gap-4">
                                    <div
                                        v-for="(file, i) in uploadedFiles"
                                        :key="file.name + file.size"
                                        class="p-4 border rounded-md flex flex-col items-center gap-2 w-52"
                                    >
                                        <i
                                            :class="fileIcon(file)"
                                            class="pi text-5xl"
                                        />
                                        <span
                                            class="font-semibold text-ellipsis max-w-44 whitespace-nowrap overflow-hidden"
                                        >
                                            {{ file.name }}
                                        </span>
                                        <Badge value="OK" severity="success" />
                                        <Button
                                            icon="pi pi-times"
                                            @click="
                                                removeUploadedFileCallback(i)
                                            "
                                            rounded
                                            severity="danger"
                                            variant="outlined"
                                        />
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template #empty>
                            <span>Arrastra y suelta tus CSV o Excel aquí.</span>
                        </template>
                    </FileUpload>
                </div>

                <!-- <template #footer>
                    <Button
                        label="Cancelar"
                        icon="pi pi-times"
                        text
                        @click="openUploadDialog = false"
                    />
                    <Button
                        label="Guardar"
                        icon="pi pi-check"
                        @click="saveActivity"
                        :loading="submitted"
                    />
                </template> -->
            </Dialog>
        </div>
    </AppLayout>
</template>
