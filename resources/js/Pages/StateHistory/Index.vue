<script setup>
import { onMounted, ref, watch } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToast } from "primevue/usetoast";
import { FilterMatchMode } from "@primevue/core/api";
import axios from "axios";
import { useForm } from "@inertiajs/vue3";
import { useToastService } from "@/Stores/toastService";
import ConfirmationDialog from "@/Components/ConfirmationDialog.vue";

const props = defineProps({
    employees: Array,
    statusReason: Array,
});

const { showSuccess, showError } = useToastService();

const form = useForm({
    status: null,
    content: null,
    date: null,
    reason_id: null,
    employee_id: null,
    id: null,
});

const getSafeBranchId = () => {
    try {
        const item = localStorage.getItem("selectedBranchOffice");
        if (!item) return null;
        const parsed = JSON.parse(item);
        return parsed || null;
    } catch (e) {
        console.warn("Error leyendo localStorage:", e);
        return null;
    }
};

const selectedBranchOffice = ref(getSafeBranchId());

const employeesByBranchOffice = ref(
    props.employees.filter(
        (employee) =>
            employee.branch_office_id === selectedBranchOffice.value.id,
    ),
);

const filters = ref();
const op = ref(null);
const actualRow = ref(null);

const toggleAccionesMasivas = (event, id) => {
    op.value.toggle(event);
    actualRow.value = id;
};

const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    };
};

initFilters();

const selectedEmployee = ref();

const statusHistory = ref([]);

const dialogVisible = ref(false);
const dialogEditVisible = ref(false);
const editingStatus = ref(null);
const newStatus = ref({ type: null, date: new Date(), notes: "" });
const toast = useToast();
const deleteId = ref();

const statusOptions = [
    { label: "Alta", value: "entry" },
    { label: "Baja", value: "termination" },
    { label: "Traspaso", value: "change" },
    { label: "Reingreso", value: "reentry" },
];

const getData = async (id) => {
    loading.value = true;
    const response = await axios.get(`/api/state-history?id=${id}`);

    statusHistory.value = response.data;

    loading.value = false;
};

const addStatus = () => {
    editingStatus.value = null;
    newStatus.value = { type: null, date: new Date(), notes: "" };
    dialogVisible.value = true;
};

const editStatus = (status) => {
    dialogEditVisible.value = true;
    form.employee_id = selectedEmployee.value.id;
    form.id = status.id;
    form.date = status.date;
    form.content = status.content;
    form.reason_id = status.reason_id;
    form.status = status.status;
};

const saveStatus = () => {
    loading.value = true;
    form.employee_id = selectedEmployee.value.id;
    form.post(route("state-history.store"), {
        onSuccess: async () => {
            await getData();
            showSuccess();
            dialogVisible.value = false;
            form.reset();
        },
        onError: () => {
            showError();
            loading.value = false;
        },
    });
};

const editStatusHistory = () => {
    loading.value = true;
    form.employee_id = selectedEmployee.value.id;
    form.put(route("state-history.update", form.id), {
        onSuccess: async () => {
            await getData();
            showSuccess();
            dialogEditVisible.value = false;
            form.reset();
        },
        onError: () => {
            showError();
            loading.value = false;
        },
    });
};

const deleteDialog = ref(false);

const deleteStatus = (status) => {
    loading.value = true;
    form.employee_id = selectedEmployee.value.id;
    form.delete(route("state-history.destroy", deleteId.value), {
        onSuccess: async () => {
            await getData();
            showSuccess();
            deleteDialog.value = false;
            form.reset();
        },
        onError: () => {
            showError();
            loading.value = false;
        },
    });
};

const getStatusColor = (type) => {
    switch (type) {
        case "entry":
            return "bg-green-500";
            break;
        case "termination":
            return "bg-red-500";
            break;
        case "change":
            return "bg-violet-500";
            break;
        case "reentry":
            return "bg-cyan-500";
            break;
    }
};

const getBorderColor = (type) => {
    switch (type) {
        case "entry":
            return "border-green-500";
            break;
        case "termination":
            return "border-red-500";
            break;
        case "change":
            return "border-violet-500";
            break;
        case "reentry":
            return "border-cyan-500";
            break;
    }
};

const getStatusIcon = (type) => {
    switch (type) {
        case "entry":
            return "pi pi-user-plus";
            break;
        case "termination":
            return "pi pi-user-minus";
            break;
        case "change":
            return "pi pi-sign-out";
            break;
        case "reentry":
            return "pi pi-refresh";
            break;
    }
};

const getSeverity = (type) => {
    switch (type) {
        case "entry":
            return "success";
            break;
        case "termination":
            return "danger";
            break;
        case "change":
            return "help";
            break;
        case "reentry":
            return "info";
            break;
    }
};

const getLabel = (type) => {
    switch (type) {
        case "entry":
            return "Ingreso";
            break;
        case "termination":
            return "Baja";
            break;
        case "change":
            return "Traspaso";
            break;
        case "reentry":
            return "Reingreso";
            break;
    }
};

const searchTerm = ref("");
const filteredEmployees = ref(employeesByBranchOffice.value);

console.log(filteredEmployees.value);

const filterEmployees = () => {
    let result = employeesByBranchOffice.value || [];

    if (searchTerm.value) {
        const term = searchTerm.value.toLowerCase();
        result = result.filter(
            (emp) =>
                emp.full_name?.toLowerCase().includes(term) ||
                emp.id?.toString().includes(term),
        );
    }

    filteredEmployees.value = result;
};

// Selección de empleado
const selectEmployee = async (employee) => {
    if (selectedEmployee.value?.id === employee.id) return; // Ya está seleccionado

    selectedEmployee.value = employee;
    loading.value = true;

    try {
        await getData(employee.id);
    } finally {
        loading.value = false;
    }
};

watch(
    () => employeesByBranchOffice.value,
    () => {
        filteredEmployees.value = props.employeesByBranchOffice || [];
    },
    { immediate: true, deep: true },
);

watch(searchTerm, filterEmployees);

const loading = ref(false);

const getDocument = (tipoContrato) => {
    let rutaPDF = "";
    switch (tipoContrato) {
        case "valle":
            rutaPDF =
                "https://nomina.grupo-ortiz.site/pdf/empleados_historial_estados_formato_valle.php";
            break;
        case "plantas":
            rutaPDF =
                "https://nomina.grupo-ortiz.site/pdf/empleados_historial_estados_formato_plantas.php";
            break;
        case "practicantes":
            rutaPDF =
                "https://nomina.grupo-ortiz.site/pdf/empleados_historial_estados_formato_practicantes.php";
            break;
        case "confidencialidad":
            rutaPDF =
                "https://nomina.grupo-ortiz.site/pdf/empleados_historial_estados_formato_confidencialidad.php";
            break;
        case "lactancia":
            rutaPDF =
                "https://nomina.grupo-ortiz.site/pdf/empleados_historial_estados_formato_lactancia.php";
            break;
        case "renuncia":
            rutaPDF =
                "https://nomina.grupo-ortiz.site/pdf/empleados_historial_estados_formato_renuncia.php";
            break;
    }

    window.open(`${rutaPDF}?id=${actualRow.value}`, "_blank");
};

onMounted(() => {
    if (employeesByBranchOffice.value.length) {
        filteredEmployees.value = [...employeesByBranchOffice.value];
    }
});
</script>

<template>
    <AppLayout title="Historial de Estados">
        <div class="flex flex-col md:flex-row gap-4 h-full p-4">
            <!-- PANEL IZQUIERDO: SELECTOR DE EMPLEADOS -->
            <Card class="w-1/3 h-fit shadow-md">
                <template #title>
                    <div class="flex justify-between items-center">
                        <span>Empleados</span>
                        <Tag
                            :value="employeesByBranchOffice?.length || 0"
                            severity="secondary"
                            rounded
                        />
                    </div>
                </template>

                <template #content>
                    <!-- Buscador -->
                    <div class="relative mb-4">
                        <IconField class="w-full">
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText
                                v-model="searchTerm"
                                placeholder="Buscar por nombre o nómina..."
                                class="w-full"
                                @input="filterEmployees"
                            />
                            <InputIcon
                                v-if="searchTerm"
                                class="cursor-pointer"
                                @click="
                                    searchTerm = '';
                                    filterEmployees();
                                "
                            >
                                <i class="pi pi-times" />
                            </InputIcon>
                        </IconField>
                    </div>

                    <!-- Lista de empleados (VirtualScroller para rendimiento) -->
                    <div class="max-h-[500px] overflow-y-auto pr-2">
                        <VirtualScroller
                            :items="filteredEmployees"
                            :itemSize="70"
                            style="height: 500px"
                            :options="{ itemSize: 70 }"
                        >
                            <template #item="{ item, options }" :key="item.id">
                                <div
                                    class="flex items-center gap-3 p-3 mb-2 rounded-lg cursor-pointer transition-all border-2"
                                    :class="[
                                        selectedEmployee?.id === item.id
                                            ? 'border-primary bg-primary/5 shadow-sm'
                                            : 'border-transparent hover:border-surface-300 hover:bg-surface-50',
                                    ]"
                                    @click="selectEmployee(item)"
                                >
                                    <!-- Avatar con estado -->
                                    <div class="relative">
                                        <img
                                            :src="`https://nominas.grupo-ortiz.site/Librerias/img/Fotos/${item.id}.jpg`"
                                            :alt="item.full_name"
                                            class="w-12 h-12 rounded-full object-cover border-2 border-white shadow-sm"
                                            @error="
                                                (e) =>
                                                    (e.target.src =
                                                        'https://ui-avatars.com/api/?name=' +
                                                        encodeURIComponent(
                                                            item.full_name,
                                                        ) +
                                                        '&background=random')
                                            "
                                        />
                                    </div>

                                    <!-- Información -->
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-sm truncate">
                                            {{ item.full_name }}
                                        </p>
                                        <p class="text-xs text-color-secondary">
                                            Nómina: {{ item.id }}
                                        </p>
                                    </div>
                                </div>
                            </template>

                            <!-- Loading interno del VirtualScroller -->
                            <template #loading>
                                <div class="flex flex-col gap-2 p-3">
                                    <Skeleton
                                        width="100%"
                                        height="70px"
                                        border-radius="8px"
                                        v-for="i in 5"
                                        :key="i"
                                    />
                                </div>
                            </template>
                        </VirtualScroller>
                    </div>

                    <!-- Mensaje sin resultados -->
                    <div
                        v-if="filteredEmployees.length === 0 && searchTerm"
                        class="text-center py-8 text-color-secondary"
                    >
                        <i class="pi pi-search text-2xl mb-2 block" />
                        <p class="text-sm">
                            No se encontraron empleados con "{{ searchTerm }}"
                        </p>
                        <Button
                            label="Limpiar búsqueda"
                            text
                            size="small"
                            @click="
                                searchTerm = '';
                                filterEmployees();
                            "
                            class="mt-2"
                        />
                    </div>
                </template>
            </Card>

            <!-- PANEL DERECHO: HISTORIAL (TIMELINE) -->
            <Card
                v-if="selectedEmployee"
                class="w-3/4 shadow-md flex flex-col h-[75vh]"
            >
                <!-- HEADER: Se mantiene fijo (no hace scroll) -->
                <template #header>
                    <div
                        class="flex justify-between items-center p-4 border-b border-surface-200"
                    >
                        <div class="flex items-center gap-3">
                            <img
                                :src="`https://nominas.grupo-ortiz.site/Librerias/img/Fotos/${selectedEmployee.id}.jpg`"
                                class="w-14 h-14 rounded-full border-2 border-primary"
                            />
                            <div>
                                <h2 class="text-xl font-bold m-0">
                                    {{ selectedEmployee.full_name }}
                                </h2>
                                <span class="text-sm text-color-secondary">{{
                                    selectedEmployee.id
                                }}</span>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <Button
                                label="Nuevo Estado"
                                icon="pi pi-plus"
                                @click="addStatus"
                                class="p-button-outlined"
                            />
                        </div>
                    </div>
                </template>

                <!-- CONTENT: Área scrollable -->
                <template #content>
                    <!-- flex-1: ocupa el espacio restante | overflow-y-auto: activa el scroll -->
                    <div class="p-4 flex-1 overflow-y-auto h-[calc(70vh-80px)]">
                        <!-- SKELETON: Se muestra mientras carga -->
                        <div v-if="loading" class="flex flex-col gap-4">
                            <!-- Item Skeleton 1 -->
                            <div class="flex gap-4">
                                <Skeleton
                                    shape="circle"
                                    size="2rem"
                                    class="flex-shrink-0"
                                />
                                <Card
                                    class="w-full shadow-sm border-l-4 border-surface-200"
                                >
                                    <template #title>
                                        <div
                                            class="flex justify-between items-start mb-2"
                                        >
                                            <Skeleton
                                                width="6rem"
                                                height="1.5rem"
                                            />
                                            <Skeleton
                                                width="5rem"
                                                height="1rem"
                                            />
                                        </div>
                                    </template>
                                    <template #content>
                                        <Skeleton
                                            width="100%"
                                            height="1rem"
                                            class="mb-2"
                                        />
                                        <Skeleton
                                            width="80%"
                                            height="1rem"
                                            class="mb-3"
                                        />
                                        <div
                                            class="flex gap-2 pt-3 border-t border-surface-100"
                                        >
                                            <Skeleton
                                                shape="circle"
                                                size="2rem"
                                            />
                                            <Skeleton
                                                shape="circle"
                                                size="2rem"
                                            />
                                        </div>
                                    </template>
                                </Card>
                            </div>
                            <!-- Repite skeleton items... -->
                        </div>

                        <!-- TIMELINE REAL: Se muestra cuando hay datos -->
                        <Timeline
                            v-else
                            :value="statusHistory"
                            align="alternate"
                            class="customized-timeline"
                        >
                            <template #marker="{ item }">
                                <span
                                    class="flex items-center justify-center w-8 h-8 rounded-full shadow"
                                    :class="getStatusColor(item.status)"
                                >
                                    <i
                                        class="text-white"
                                        :class="getStatusIcon(item.status)"
                                    ></i>
                                </span>
                            </template>
                            <template #content="{ item }">
                                <Card
                                    class="mb-3 shadow-sm hover:shadow-md transition-shadow border-l-4"
                                    :class="getBorderColor(item.status)"
                                >
                                    <template #title>
                                        <div
                                            class="flex justify-between items-start"
                                        >
                                            <Tag
                                                :value="getLabel(item.status)"
                                                :severity="
                                                    getSeverity(item.status)
                                                "
                                                :class="
                                                    item.status == 'change' &&
                                                    '!bg-purple-200 !text-purple-600'
                                                "
                                            />
                                            <span
                                                class="text-sm text-color-secondary"
                                            >
                                                {{ item.date }}
                                            </span>
                                        </div>
                                    </template>
                                    <template #content>
                                        <p class="mb-2 text-sm">
                                            {{ item.planta }}
                                        </p>
                                        <p class="m-0 text-sm">
                                            {{ item.content }}
                                        </p>
                                        <div
                                            class="flex gap-2 mt-3 pt-3 border-t border-surface-100"
                                        >
                                            <Button
                                                icon="pi pi-pencil"
                                                rounded
                                                severity="warn"
                                                @click="editStatus(item)"
                                            />
                                            <Button
                                                icon="pi pi-trash"
                                                rounded
                                                severity="danger"
                                                @click="
                                                    () => {
                                                        deleteDialog = true;
                                                        deleteId = item.id;
                                                    }
                                                "
                                            />
                                            <Button
                                                icon="pi pi-file"
                                                rounded
                                                severity="contrast"
                                                @click="
                                                    toggleAccionesMasivas(
                                                        $event,
                                                        item.id,
                                                    )
                                                "
                                            />
                                            <Popover ref="op">
                                                <div
                                                    class="flex flex-col gap-4"
                                                >
                                                    <div>
                                                        <span
                                                            class="font-medium block mb-2"
                                                            >Generar
                                                            Formato</span
                                                        >
                                                        <ul
                                                            class="list-none p-0 m-0 flex flex-col"
                                                        >
                                                            <li
                                                                class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                                                            >
                                                                <Button
                                                                    type="button"
                                                                    icon="pi pi-file"
                                                                    severity="contrast"
                                                                    label="Contrato Determinado del Valle"
                                                                    text
                                                                    @click="
                                                                        () => {
                                                                            getDocument(
                                                                                'valle',
                                                                            );
                                                                        }
                                                                    "
                                                                />
                                                            </li>
                                                            <li
                                                                class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                                                            >
                                                                <Button
                                                                    type="button"
                                                                    icon="pi pi-file"
                                                                    severity="contrast"
                                                                    label="Contrato Plantas"
                                                                    text
                                                                    @click="
                                                                        () => {
                                                                            getDocument(
                                                                                'plantas',
                                                                            );
                                                                        }
                                                                    "
                                                                />
                                                            </li>
                                                            <li
                                                                class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                                                            >
                                                                <Button
                                                                    type="button"
                                                                    icon="pi pi-file"
                                                                    severity="contrast"
                                                                    label="Convenio Practicantes"
                                                                    text
                                                                    @click="
                                                                        () => {
                                                                            getDocument(
                                                                                'practicantes',
                                                                            );
                                                                        }
                                                                    "
                                                                />
                                                            </li>
                                                            <li
                                                                class="flex items-start justify-start gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                                                            >
                                                                <Button
                                                                    type="button"
                                                                    icon="pi pi-file"
                                                                    severity="contrast"
                                                                    label="Convenio Conf. Colaboradores"
                                                                    text
                                                                    @click="
                                                                        () => {
                                                                            getDocument(
                                                                                'confidencialidad',
                                                                            );
                                                                        }
                                                                    "
                                                                />
                                                            </li>
                                                            <li
                                                                class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                                                            >
                                                                <Button
                                                                    type="button"
                                                                    icon="pi pi-file"
                                                                    severity="contrast"
                                                                    label="Formato Lactancia"
                                                                    text
                                                                    @click="
                                                                        () => {
                                                                            getDocument(
                                                                                'lactancia',
                                                                            );
                                                                        }
                                                                    "
                                                                />
                                                            </li>
                                                            <li
                                                                class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                                                            >
                                                                <Button
                                                                    type="button"
                                                                    icon="pi pi-file"
                                                                    severity="contrast"
                                                                    label="Renuncia"
                                                                    text
                                                                    @click="
                                                                        () => {
                                                                            getDocument(
                                                                                'renuncia',
                                                                            );
                                                                        }
                                                                    "
                                                                />
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </Popover>
                                        </div>
                                    </template>
                                </Card>
                            </template>
                        </Timeline>
                    </div>
                </template>
            </Card>

            <div
                v-else
                class="w-full flex items-center justify-center text-color-secondary"
            >
                <i class="pi pi-user text-4xl mr-2"></i> Seleccione un empleado
                para ver su historial
            </div>
        </div>

        <!-- MODAL DE CREACIÓN/EDICIÓN -->
        <Dialog
            v-model:visible="dialogVisible"
            modal
            header="Añadir Estado"
            :style="{ width: '30rem' }"
        >
            <div class="flex flex-col gap-4">
                <div>
                    <label class="block font-bold mb-2">Tipo de Estado</label>
                    <Select
                        v-model="form.status"
                        :options="statusOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Seleccionar..."
                        class="w-full"
                    />
                    <small
                        class="p-error text-red-500"
                        v-if="form.errors.status"
                        >{{ form.errors.status }}</small
                    >
                </div>
                <div>
                    <label class="block font-bold mb-2">Razon</label>
                    <Select
                        v-model="form.reason_id"
                        :options="props.statusReason"
                        optionLabel="name"
                        optionValue="id"
                        filter
                        placeholder="Seleccionar..."
                        class="w-full"
                    />
                    <small
                        class="p-error text-red-500"
                        v-if="form.errors.reason_id"
                        >{{ form.errors.reason_id }}</small
                    >
                </div>
                <div>
                    <label class="block font-bold mb-2">Fecha</label>
                    <Calendar
                        v-model="form.date"
                        showIcon
                        iconDisplay="input"
                        class="w-full"
                    />
                    <small
                        class="p-error text-red-500"
                        v-if="form.errors.date"
                        >{{ form.errors.date }}</small
                    >
                </div>
                <div>
                    <label class="block font-bold mb-2"
                        >Notas / Observaciones</label
                    >
                    <InputText
                        v-model="form.content"
                        class="w-full"
                        placeholder="Ej. Motivo del traspaso..."
                    />
                </div>
            </div>
            <template #footer>
                <Button
                    label="Cancelar"
                    icon="pi pi-times"
                    @click="dialogVisible = false"
                    text
                    class="p-button-text"
                />
                <Button
                    label="Guardar"
                    icon="pi pi-check"
                    @click="saveStatus"
                    :loading="loading"
                    autofocus
                />
            </template>
        </Dialog>
        <Dialog
            v-model:visible="dialogEditVisible"
            modal
            header="Editar Estado"
            :style="{ width: '30rem' }"
        >
            <div class="flex flex-col gap-4">
                <div>
                    <label class="block font-bold mb-2">Tipo de Estado</label>
                    <Select
                        v-model="form.status"
                        :options="statusOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Seleccionar..."
                        class="w-full"
                    />
                    <small
                        class="p-error text-red-500"
                        v-if="form.errors.status"
                        >{{ form.errors.status }}</small
                    >
                </div>
                <div>
                    <label class="block font-bold mb-2">Razon</label>
                    <Select
                        v-model="form.reason_id"
                        :options="props.statusReason"
                        optionLabel="name"
                        optionValue="id"
                        filter
                        placeholder="Seleccionar..."
                        class="w-full"
                    />
                    <small
                        class="p-error text-red-500"
                        v-if="form.errors.reason_id"
                        >{{ form.errors.reason_id }}</small
                    >
                </div>
                <div>
                    <label class="block font-bold mb-2">Fecha</label>
                    <Calendar
                        v-model="form.date"
                        showIcon
                        iconDisplay="input"
                        class="w-full"
                    />
                    <small
                        class="p-error text-red-500"
                        v-if="form.errors.date"
                        >{{ form.errors.date }}</small
                    >
                </div>
                <div>
                    <label class="block font-bold mb-2"
                        >Notas / Observaciones</label
                    >
                    <InputText
                        v-model="form.content"
                        class="w-full"
                        placeholder="Ej. Motivo del traspaso..."
                    />
                </div>
            </div>
            <template #footer>
                <Button
                    label="Cancelar"
                    icon="pi pi-times"
                    @click="dialogEditVisible = false"
                    text
                    class="p-button-text"
                />
                <Button
                    label="Guardar"
                    icon="pi pi-check"
                    @click="editStatusHistory"
                    :loading="loading"
                    autofocus
                />
            </template>
        </Dialog>
        <ConfirmationDialog
            :visible="deleteDialog"
            @confirm="deleteStatus"
            @cancel="deleteDialog = false"
            :loading="loading"
            header="¿Estás seguro de eliminar este estado?"
            confirmOrDelete="delete"
        />

        <Toast />
    </AppLayout>
</template>

<style scoped>
/* Ajustes opcionales para la timeline */
.customized-timeline .p-timeline-event-content,
.customized-timeline .p-timeline-event-opposite {
    line-height: 1;
}
</style>
