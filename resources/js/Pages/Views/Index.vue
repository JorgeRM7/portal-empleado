<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { useToastService } from "@/Stores/toastService";

const props = defineProps({
    views: Array,
});

const { showSuccess, showError } = useToastService();

const selected = ref([]);
const op = ref(null);
const opMostrarColumnas = ref(null);
const opFijarColumnas = ref(null);
const columnsDialog = ref(false);
const filters = ref({});
const otherFilterDialog = ref(false);
const openUploadDialog = ref(false);
const deleteDialog = ref(false);
const deleteMultipleDialog = ref(false);
const viewToDelete = ref(null);

const frozenColumns = ref({
    acciones: false,
    id: false,
    modulo: false,
    name: false,
    url: false,
});

const showColumns = ref({
    acciones: true,
    id: true,
    modulo: true,
    name: true,
    url: true,
});

const exportColumns = ref({
    id: true,
    modulo: true,
    name: true,
    url: true,
});

const otherFilters = ref([
    {
        startDate: null,
        endDate: null,
    },
]);

const loading = ref(false);

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
        url: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        modulo: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
    };
};

initFilters();

const toggleAccionesMasivas = (event) => {
    op.value.toggle(event);
};

const toggleMostrarColumnas = (event) => {
    opMostrarColumnas.value.toggle(event);
};

const toggleFijarColumnas = (event) => {
    opFijarColumnas.value.toggle(event);
};

const confirmDeleteMultiple = () => {
    deleteMultipleDialog.value = true;
};

const deleteMultiple = () => {
    loading.value = true;
    router.post(
        "/views/delete-multiple",
        {
            ids: selected.value.map((item) => item.id),
        },
        {
            onSuccess: () => {
                selected.value = [];
                deleteMultipleDialog.value = false;
                showSuccess();
            },
            onError: () => {
                selected.value = [];
                deleteMultipleDialog.value = false;
                showError();
            },
            onFinish: () => {
                loading.value = false;
            },
        },
    );
};

const confirmDelete = () => {
    deleteDialog.value = true;
};

const clearFilter = () => {
    initFilters();
};

const deleteView = () => {
    loading.value = true;
    router.delete("/views/" + viewToDelete.value.id, {
        onSuccess: () => {
            viewToDelete.value = null;
            deleteDialog.value = false;
            showSuccess();
        },
        onError: () => {
            viewToDelete.value = null;
            deleteDialog.value = false;
            showError();
        },
        onFinish: () => {
            loading.value = false;
        },
    });
};

console.log(props.views);
</script>

<template>
    <AppLayout title="Vistas">
        <div class="card">
            <Toolbar>
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
                                            icon="pi pi-trash"
                                            label="Eliminar seleccionados"
                                            severity="danger"
                                            text
                                            class="mt-2 ml-2"
                                            @click="confirmDeleteMultiple()"
                                        />
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </Popover>

                    <Link :href="route('views.create')">
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
                :value="props.views"
                dataKey="id"
                :paginator="true"
                :rows="10"
                scrollable
                scrollHeight="400px"
                :filterDelay="500"
                v-model:filters="filters"
                filterDisplay="menu"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} vistas"
            >
                <template #header>
                    <div
                        class="flex flex-wrap gap-2 items-end justify-between mb-6"
                    >
                        <div>
                            <h4 class="m-0">Vistas</h4>

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
                                severity="warn"
                                @click="
                                    () => {
                                        router.visit(
                                            route('views.edit', data.id),
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
                                        viewToDelete = data;
                                        deleteDialog = true;
                                    }
                                "
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
                        display: showColumns.name ? '' : 'none',
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
                    field="url"
                    header="URL"
                    :frozen="frozenColumns.url"
                    :style="{
                        width: '5rem',
                        display: showColumns.url ? '' : 'none',
                    }"
                    :exportable="exportColumns.url"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.url }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por URL"
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
                    <span v-if="viewToDelete.id"
                        >Estas seguro de eliminar la vista con el id
                        <b>{{ viewToDelete.id }}</b
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
                        :loading="loading"
                    />
                    <Button
                        label="Si"
                        icon="pi pi-check"
                        @click="deleteView"
                        severity="danger"
                        :loading="loading"
                    />
                </template>
            </Dialog>
            <Dialog
                v-model:visible="deleteMultipleDialog"
                :style="{ width: '450px' }"
                header="Confirmar eliminación múltiple"
                :modal="true"
            >
                <div class="flex items-center gap-4">
                    <i class="pi pi-exclamation-triangle !text-3xl" />
                    <span
                        >Estas seguro de eliminar las vistas
                        seleccionadas?</span
                    >
                </div>
                <template #footer>
                    <Button
                        label="No"
                        icon="pi pi-times"
                        text
                        @click="deleteMultipleDialog = false"
                        severity="secondary"
                        variant="text"
                        :loading="loading"
                    />
                    <Button
                        label="Si"
                        icon="pi pi-check"
                        @click="deleteMultiple"
                        severity="danger"
                        :loading="loading"
                    />
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>
