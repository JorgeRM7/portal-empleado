<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToastService } from "@/Stores/toastService";
import { useForm } from "@inertiajs/vue3";
import { FilterMatchMode } from "@primevue/core/api";
import { ref } from "vue";
import { router as Inertia } from "@inertiajs/vue3";
import { useToast } from "primevue/usetoast";

const { showSuccess, showError } = useToastService();

const props = defineProps({
    activities: Array,
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const selectedActivities = ref([]);
const activitiesDialog = ref(false);
const submitted = ref(false);
const deleteActivitiesDialog = ref(false);
const openUploadDialog = ref(false);
const toast = useToast();
const visible = ref(false);
const progress = ref(0);
const interval = ref();

const activities = useForm({
    id: null,
    name: "",
    cost: 0,
    active: true,
});

const openNew = () => {
    activities.value = {
        id: null,
        name: "",
        cost: 0,
        active: true,
    };
    submitted.value = false;
    activitiesDialog.value = true;
};

const saveActivity = () => {
    submitted.value = true;

    if (activities.id) {
        activities.put(`/actividades/${activities.id}`, {
            onSuccess: () => {
                activitiesDialog.value = false;
                submitted.value = false;
                showSuccess();
            },
            onError: () => {
                submitted.value = false;
                showError();
            },
        });
    } else {
        activities.post("/actividades", {
            onSuccess: () => {
                activitiesDialog.value = false;
                submitted.value = false;
                showSuccess();
            },
            onError: () => {
                submitted.value = false;
                showError();
            },
        });
    }
};

const editActivities = (activity) => {
    activities.id = activity.id;
    activities.name = activity.name;
    activities.cost = activity.cost;
    activities.active = activity.active;
    activitiesDialog.value = true;
};

const confirmDeleteActivities = (activity) => {
    activities.id = activity.id;
    activities.name = activity.name;
    deleteActivitiesDialog.value = true;
};

const deleteActivity = () => {
    submitted.value = true;
    if (selectedActivities.value.length > 0) {
        const ids = selectedActivities.value.map((act) => act.id);
        Inertia.post(
            "/actividades/delete-multiple",
            {
                ids: ids,
            },
            {
                onSuccess: () => {
                    deleteActivitiesDialog.value = false;
                    submitted.value = false;
                    selectedActivities.value = [];
                    showSuccess();
                },
                onError: () => {
                    submitted.value = false;
                    showError();
                },
            },
        );
        return;
    } else {
        activities.delete(`/actividades/${activities.id}`, {
            onSuccess: () => {
                deleteActivitiesDialog.value = false;
                submitted.value = false;
                showSuccess();
            },
            onError: () => {
                submitted.value = false;
                showError();
            },
        });
    }
};

const fileIcon = (file) => {
    const ext = file.name.split(".").pop()?.toLowerCase();
    if (ext === "csv") return "pi-file"; // ícono genérico de archivo
    if (["xls", "xlsx"].includes(ext)) return "pi-file-excel"; // ícono de Excel
    return "pi-file";
};

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
        try {
            const form = new FormData();
            files.forEach((f) => form.append("files[]", f));

            await axios.post("/api/upload", form);
            await Inertia.reload({ only: ["activities"] });
        } catch (e) {
            console.error("status", e.response?.status);
            console.error("data", e.response?.data);
            showError();
            visible.value = false;
            submitted.value = false;
            toast.removeGroup("headless");
            return;
        }

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

const downloadTemplate = () => {
    submitted.value = true;
    window.location.href =
        "https://docs.google.com/spreadsheets/d/1KY3pebZiuFQu92xynXIt--Nkpp8LtH6aZeUVnhDNMNE/export?format=csv";
    submitted.value = false;
};

console.log(props.activities);
</script>
<template>
    <AppLayout :title="'Actividades'">
        <Toolbar class="mb-6 px-10">
            <template #start>
                <!-- <FileUpload
                    mode="basic"
                    accept=".csv,.xlsx"
                    :maxFileSize="1000000"
                    label="Importar"
                    customUpload
                    chooseLabel="Importar"
                    class="mr-2"
                    auto
                    :chooseButtonProps="{ severity: 'secondary' }"
                /> -->
                <Button
                    label="Importar Actividades"
                    icon="pi pi-upload"
                    severity="secondary"
                    @click="openUploadDialog = true"
                />
                <Button
                    label="Descargar Plantilla"
                    icon="pi pi-download"
                    class="ml-2"
                    severity="secondary"
                    @click="downloadTemplate()"
                    :loading="submitted"
                />
            </template>
            <template #end>
                <Button
                    label="Añadir Actividad"
                    icon="pi pi-plus"
                    class="mr-2"
                    @click="openNew"
                />

                <Button
                    label="Eliminar seleccionados"
                    icon="pi pi-trash"
                    severity="danger"
                    variant="outlined"
                    @click="confirmDeleteActivities"
                    :disabled="
                        !selectedActivities || !selectedActivities.length
                    "
                />
            </template>
        </Toolbar>
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
            <DataTable
                ref="dt"
                v-model:selection="selectedActivities"
                :value="props.activities"
                dataKey="id"
                :paginator="true"
                :rows="10"
                :filters="filters"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} actividades"
            >
                <template #empty> No hay actividades disponibles. </template>
                <template #loading> Cargando... </template>
                <template #header>
                    <div
                        class="flex flex-wrap gap-2 items-center justify-between"
                    >
                        <h4 class="m-0">Actividades</h4>
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
                </template>

                <Column
                    selectionMode="multiple"
                    style="width: 5rem"
                    :exportable="false"
                ></Column>
                <Column :exportable="false">
                    <template #body="slotProps">
                        <Button
                            icon="pi pi-pencil"
                            variant="outlined"
                            rounded
                            class="mr-2"
                            @click="editActivities(slotProps.data)"
                        />
                        <Button
                            icon="pi pi-trash"
                            variant="outlined"
                            rounded
                            severity="danger"
                            @click="confirmDeleteActivities(slotProps.data)"
                        />
                    </template>
                </Column>
                <!-- <Column
                    field="id"
                    header="#"
                    sortable
                    style="min-width: 12rem"
                ></Column> -->
                <Column
                    field="name"
                    header="Actividad"
                    sortable
                    style="min-width: 16rem"
                ></Column>
                <Column field="active" header="Estado" style="min-width: 16rem">
                    <template #body="slotProps">
                        <Tag
                            :value="
                                slotProps.data.active === 1
                                    ? 'Activo'
                                    : 'Desactivado'
                            "
                            :severity="
                                slotProps.data.active === 1
                                    ? 'success'
                                    : 'danger'
                            "
                        ></Tag>
                    </template>
                </Column>
            </DataTable>

            <Dialog
                v-model:visible="activitiesDialog"
                :style="{ width: '450px' }"
                header="Añadir o Editar Actividad"
                :modal="true"
            >
                <div class="flex flex-col gap-6">
                    <div>
                        <label for="name" class="block font-bold mb-3"
                            >Actividad</label
                        >
                        <InputText
                            id="name"
                            v-model.trim="activities.name"
                            required="true"
                            autofocus
                            :invalid="submitted && !activities.name"
                            fluid
                        />
                        <small
                            v-if="activities.errors.name"
                            class="text-red-500"
                            >El nombre es requerido</small
                        >
                    </div>
                    <div>
                        <label for="cost" class="block font-bold mb-3"
                            >Costo</label
                        >

                        <InputNumber
                            v-model="activities.cost"
                            inputId="currency-us"
                            mode="currency"
                            currency="USD"
                            locale="en-US"
                            fluid
                        />
                        <small
                            v-if="activities.errors.cost"
                            class="text-red-500"
                            >El costo es requerido</small
                        >
                    </div>
                    <div>
                        <label for="description" class="block font-bold mb-3"
                            >Estado</label
                        >
                        <ToggleButton
                            v-model="activities.active"
                            onLabel="Activo"
                            offLabel="Desactivado"
                            onIcon="pi pi-check"
                            offIcon="pi pi-times"
                        />
                    </div>
                </div>

                <template #footer>
                    <Button
                        label="Cancelar"
                        icon="pi pi-times"
                        text
                        @click="activitiesDialog = false"
                    />
                    <Button
                        label="Guardar"
                        icon="pi pi-check"
                        @click="saveActivity"
                        :loading="submitted"
                    />
                </template>
            </Dialog>

            <Dialog
                v-model:visible="deleteActivitiesDialog"
                :style="{ width: '450px' }"
                header="Confirm"
                :modal="true"
            >
                <div class="flex items-center gap-4">
                    <i class="pi pi-exclamation-triangle !text-3xl" />
                    <span v-if="!selectedActivities.length"
                        >Estas seguro que deseas eliminar la actividad
                        <b>{{ activities.name }}</b
                        >?</span
                    >
                    <span v-if="selectedActivities.length"
                        >Estas seguro que deseas eliminar las actividades
                        seleccionadas ?</span
                    >
                </div>
                <template #footer>
                    <Button
                        label="No"
                        icon="pi pi-times"
                        text
                        @click="deleteActivitiesDialog = false"
                        severity="secondary"
                        variant="text"
                    />
                    <Button
                        label="Si"
                        icon="pi pi-check"
                        @click="deleteActivity"
                        severity="danger"
                        :loading="submitted"
                    />
                </template>
            </Dialog>

            <Dialog
                v-model:visible="openUploadDialog"
                :style="{ width: '450px' }"
                header="Subir Archivo de Actividades"
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
