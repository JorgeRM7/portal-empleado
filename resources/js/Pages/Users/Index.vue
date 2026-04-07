<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToastService } from "@/Stores/toastService";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import { onMounted, ref } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { useNotifications } from "@/composables/useNotifications";
import { Link, router, useForm } from "@inertiajs/vue3";
import { RouterLink } from "vue-router";
import { useAuthz } from "@/composables/useAuthz";

const { sendNotification } = useNotifications();

const { can } = useAuthz();

const props = defineProps({
    users: Array,
});

console.log(props.users);

//Inicializar filtros globales de tabla
const filters = ref({});

//Inicializar variable de carga para mostrar esqueleto
const loading = ref(false);

//Función para mostrar toast de éxito y error
const { showSuccess, showError } = useToastService();

//Función para inicializar filtros
const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        id: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        name: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        email: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        username: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        rol: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
    };
};

initFilters();

//Columnas de exportación
const exportColumns = ref({
    id: true,
    rol: true,
    name: true,
    email: true,
    profile_photo: true,
    username: true,
});

//Columnas visibles
const showColumns = ref({
    acciones: true,
    id: true,
    nombre: true,
    email: true,
    foto: true,
    usuario: true,
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
    name: false,
    email: false,
    profile_photo: false,
    username: false,
});

//Diálogo de selección de columnas
const columnsDialog = ref(false);

//Estado de envío
const submitted = ref(false);

const user = useForm({
    id: null,
});

//Referencias a los popovers
const op = ref();
const opMostrarColumnas = ref();
const opFijarColumnas = ref();
const opDelete = ref();
const deleteDialog = ref(false);

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
const users = ref([]);
const total = ref(0);
const rows = ref(10);
const page = ref(1);

const loadUsers = async () => {
    loading.value = true;
    const { data } = await axios.get("/api/users", {
        params: {
            page: page.value,
            per_page: rows.value,
            filters: JSON.stringify(filters.value),
        },
    });

    users.value = data.data;
    total.value = data.total;

    loading.value = false;
};

const onPage = (event) => {
    // PrimeVue usa 0-based index para la página
    page.value = event.page + 1;
    rows.value = event.rows;
    loadUsers();
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
    loadUsers();
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

const onFilter = () => {
    page.value = 1;
    loadUsers();
};

const search = ref("");

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

const deleteUser = () => {
    console.log("Borrando usuario", opDelete.value);
    submitted.value = true;
    user.delete(`/users/${user.id}`, {
        onSuccess: () => {
            showSuccess();
            deleteDialog.value = false;
            opDelete.value = null;
            submitted.value = false;
            loadUsers();
        },
        onError: () => {
            showError();
            deleteDialog.value = false;
            submitted.value = false;
        },
    });
};

const deleteMultipleUsers = () => {
    console.log("Borrando usuarios", selected.value);
    submitted.value = true;
    router.post(
        `/users/delete-multiple`,
        {
            users: selected.value.map((user) => user.id),
        },
        {
            onSuccess: () => {
                showSuccess();
                selected.value = [];
                submitted.value = false;
                loadUsers();
            },
            onError: () => {
                showError();
                submitted.value = false;
            },
        },
    );
};

//Cargar datos de la API al montar el componente
onMounted(async () => {
    loadUsers();
});
</script>

<template>
    <AppLayout :title="'Usuarios'">
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
                    <!-- <Button
                        type="button"
                        icon="pi pi-download"
                        label="Importar"
                        severity="contrast"
                        class="mt-2 ml-2"
                        @click="openUploadDialog = true"
                    /> -->
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
                            <div v-if="can('users.delete')">
                                <span class="font-medium block mb-2"
                                    >Acciones Masivas</span
                                >
                                <ul class="list-none p-0 m-0 flex flex-col">
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
                                            @click="deleteMultipleUsers()"
                                        />
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </Popover>

                    <Link :href="route('users.create')">
                        <Button
                            label="Crear"
                            v-if="can('users.create')"
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
                :value="users"
                :lazy="true"
                dataKey="id"
                :paginator="true"
                :rows="rows"
                :totalRecords="total"
                scrollable
                scrollHeight="400px"
                tableStyle="min-width: 110rem"
                v-model:filters="filters"
                @filter="onFilter"
                :filterDelay="500"
                filterDisplay="menu"
                exportFilename="prueba"
                :globalFilterFields="[
                    'id',
                    'rol',
                    'email',
                    'name',
                    'username',
                    'profile_photo',
                ]"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} usuarios"
                @page="onPage"
            >
                <template #header>
                    <div
                        class="flex flex-wrap gap-2 items-end justify-between mb-6"
                    >
                        <div>
                            <h4 class="m-0">Usuarios</h4>

                            <Button
                                type="button"
                                icon="pi pi-filter-slash"
                                rounded
                                severity="secondary"
                                class="mt-5 mr-2"
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
                                    @keydown.enter="onFilter"
                                    v-tooltip.top="'Enter para buscar'"
                                />
                            </IconField>
                            <Button
                                type="button"
                                rounded
                                class="ml-2"
                                v-tooltip.top="'Mostrar/Ocultar columnas'"
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
                <template #empty>
                    <Skeleton class="w-full h-4" />
                </template>

                <Column
                    selectionMode="multiple"
                    style="width: 1rem"
                    :exportable="false"
                ></Column>
                <Column
                    :exportable="false"
                    :style="{
                        width: '5rem',
                        display: showColumns.acciones ? '' : 'none',
                    }"
                    header="Acciones"
                    :frozen="frozenColumns.acciones"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <div v-else>
                            <Button
                                icon="pi pi-pencil"
                                class="mr-2"
                                rounded
                                v-tooltip.top="'Editar'"
                                v-if="can('users.edit')"
                                severity="warn"
                                @click="
                                    () => {
                                        router.visit(
                                            route('users.edit', data.id),
                                        );
                                    }
                                "
                            />
                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                v-tooltip.top="'Eliminar'"
                                v-if="can('users.delete')"
                                class="mr-2"
                                rounded
                                @click="
                                    () => {
                                        user.id = data.id;
                                        deleteDialog = true;
                                    }
                                "
                            />

                            <!-- <Button
                                icon="pi pi-pencil"
                                class="mr-2"
                                rounded
                                v-tooltip.top="'Editar'"
                                severity="warn"
                                @click="
                                    () => {
                                        router.visit(
                                            route('users.edit', data.id),
                                        );
                                    }
                                "
                            />
                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                v-tooltip.top="'Eliminar'"
                                class="mr-2"
                                rounded
                                @click="
                                    () => {
                                        user.id = data.id;
                                        deleteDialog = true;
                                    }
                                "
                            /> -->
                        </div>
                    </template>
                </Column>
                <Column
                    header="Foto de perfil"
                    field="profile_photo_url"
                    :style="{
                        width: '5rem',
                        display: showColumns.foto ? '' : 'none',
                    }"
                >
                    <template #body="{ data }">
                        <Skeleton
                            v-if="loading"
                            shape="circle"
                            size="3rem"
                            class="mr-2"
                        ></Skeleton>
                        <div v-else class="flex items-center gap-2">
                            <img
                                :alt="data.name"
                                :src="`${data.profile_photo_url}`"
                                class="rounded-full"
                                style="width: 32px"
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
                        width: '5rem',
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
                    field="name"
                    header="Nombre"
                    :filter="true"
                    :frozen="frozenColumns.name"
                    :style="{
                        width: '15rem',
                        display: showColumns.nombre ? '' : 'none',
                    }"
                    :exportable="exportColumns.name"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.name }}</span>
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
                    field="email"
                    header="Correo"
                    :frozen="frozenColumns.email"
                    :style="{
                        width: '5rem',
                        display: showColumns.email ? '' : 'none',
                    }"
                    :exportable="exportColumns.email"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.email }}</span>
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
                    field="username"
                    header="Nombre de Usuario"
                    :filter="true"
                    :frozen="frozenColumns.username"
                    :style="{
                        width: '5rem',
                        display: showColumns.usuario ? '' : 'none',
                    }"
                    :exportable="exportColumns.username"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.username }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Nombre de Usuario"
                        />
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
            <Dialog
                v-model:visible="deleteDialog"
                :style="{ width: '450px' }"
                header="Confirmar eliminación"
                :modal="true"
            >
                <div class="flex items-center gap-4">
                    <i class="pi pi-exclamation-triangle !text-3xl" />
                    <span v-if="user.id"
                        >Estas seguro de eliminar el usuario con el id
                        <b>{{ user.id }}</b
                        >?</span
                    >
                </div>
                <template #footer>
                    <Button
                        label="No"
                        icon="pi pi-times"
                        text
                        @click="deleteDialog = false"
                        severity="secondary"
                        variant="text"
                        :loading="submitted"
                    />
                    <Button
                        label="Si"
                        icon="pi pi-check"
                        @click="deleteUser"
                        severity="danger"
                        :loading="submitted"
                    />
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>
