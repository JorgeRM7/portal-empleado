<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useForm } from "@inertiajs/vue3";
import { computed, ref, watch, reactive } from "vue";
import { useToastService } from "../../Stores/toastService.js";
import { router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { useAuthz } from "@/composables/useAuthz";

const { can } = useAuthz();

const { showSuccessCustom, showError, showValidationError, showErrorCustom } = useToastService();

const props = defineProps({
    Classifications: Object,
    BranchOffices: Array,
});
// console.log(props.Classifications);

// Formulario base
const form = useForm({
    id: null,
    branch_office_id: null,
    classification: null,
    description: ''
});

// Estados generales
const search = ref("");
const dialog = ref(false);
const confirm = useConfirm();
const processingRows = ref({});
const first = ref(0);
const rows = ref(9);

// 🔍 Función de normalización para búsqueda
const normalize = (s) =>
    (s ?? "")
        .toString()
        .normalize("NFD")
        .replace(/\p{Diacritic}/gu, "")
        .toLowerCase();

// 🔎 Filtrado
const filteredLists = computed(() => {
    if (!search.value) return props.Classifications;
    const q = normalize(search.value);
    return props.Classifications.filter((item) =>
        [item.id, item.branch_office, item.classification, item.description].some((field) =>
            normalize(field).includes(q)
        )
    );
});

// 📄 Paginación
const pagedLists = computed(() => {
    const start = first.value;
    const end = start + rows.value;
    return filteredLists.value.slice(start, end);
});
const totalRecords = computed(() => filteredLists.value.length);

function onSearch() {
    first.value = 0;
}

function onPage(e) {
    first.value = e.first;
    rows.value = e.rows;
}

watch([rows, filteredLists], () => {
    if (first.value >= totalRecords.value) first.value = 0;
});

const isEditMode = computed(() => !!form.id);

const dialogTitle = computed(() =>
    isEditMode.value
        ? 'Editar clasificación'
        : 'Crear clasificación'
);

// ➕ Crear nuevo
const openNew = () => {
    form.reset();
    form.id = null;
    dialog.value = true;
};

// ✏️ Editar
const editStatusReason = (item) => {
    form.id = item.id;
    form.branch_office_id = item.branch_office_id;
    form.classification = item.classification;
    form.description = item.description;
    dialog.value = true;
};

const frontErrors = reactive({});

// 💾 Guardar (crear o actualizar)
const saveItem = () => {

    if (!validateForm()) {
        showValidationError('Hay campos obligatorios sin completar');
        return;
    }

    if (form.id) {
        form.put(`/catalogs/classifications/${form.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                dialog.value = false;
                form.reset();
                showSuccessCustom('Categoria editada correctamente');
            },
            onError: () => {
                Object.values(form.errors).forEach(err => showErrorCustom(err));
            },
        });
    } else {
        form.post('/catalogs/classifications', {
            preserveScroll: true,
            onSuccess: () => {
                dialog.value = false;
                form.reset();
                showSuccessCustom('Categoria creada correctamente');
            },
            onError: () => {
                Object.values(form.errors).forEach(err => showErrorCustom(err));
            },
        });
    }
};

// 🗑️ Eliminar
const deleteItem = (row) => {

    confirm.require({
        message: `¿Deseas eliminar la clasificación "${row.id}"?`,
        header: 'Confirmar eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Sí, eliminar',
        rejectLabel: 'Cancelar',
        acceptClass: 'p-button-danger',

        accept: () => {
            processingRows.value[row.id] = true;

            router.delete(`/catalogs/classifications/${row.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    showSuccessCustom('Registro eliminado correctamente');
                },
                onError: () => {
                    showError('Error al eliminar el registro');
                },
                onFinish: () => {
                    processingRows.value[row.id] = false;
                }
            });
        },
    });
};

const validateForm = () => {

    // Limpiar errores previos
    Object.keys(frontErrors).forEach(k => delete frontErrors[k]);

    // Planta
    if (!form.branch_office_id) {
        frontErrors.branch_office_id = 'El campo es obligatorio';
    }

    // Clasificación
    if (!form.classification && form.classification < 0) {
        frontErrors.classification = 'El campo es obligatorio';
    } else if (isNaN(parseInt(form.classification))) {
        frontErrors.classification = 'El campo ser un número válido';
    }

    // Descripción
    if (form.description && String(form.description).length > 1000) {
        frontErrors.description = 'El campo debe ser maximo 1000 caracteres';
    }

    // Retorna true si no hay errores
    return Object.keys(frontErrors).length === 0;
};

const clearError = (path) => {
    if (frontErrors[path]) {
        delete frontErrors[path];
    }
};

</script>

<template>
    <AppLayout :title="'Clasificaciones'">
        <ConfirmDialog />
        <div class="card border-none">
            <Toolbar class="mb-6 px-10">
                <template #end>
                    <Button
                        v-if="(can('classifications.create'))"
                        label="Crear"
                        icon="pi pi-plus-circle"
                        severity="success"
                        class="ml-2"
                        @click="openNew"
                    />
                </template>
            </Toolbar>

            <h4 class="m-0">Clasificaciones</h4>

            <div class="flex justify-content-end mb-4">
                <IconField>
                    <InputIcon><i class="pi pi-search" /></InputIcon>
                    <InputText
                        placeholder="Buscar..."
                        v-model="search"
                        @input="onSearch"
                        fluid
                    />
                </IconField>
            </div>

            <!-- Cards -->
            <div class="grid grid-cols-3 gap-4">
                <div v-for="list in pagedLists" :key="list.id">
                    <Card>
                        <template #title>
                            <span class="font-bold">{{ list.name }}</span>
                        </template>
                        <template #content>
                            <p class="mb-2 text-lg"><b>ID:</b> {{ list.id }}</p>
                            <p class="mb-2 text-lg"><b>Planta:</b> {{ list.branch_office }}</p>
                            <p class="mb-2 text-lg"><b>Clasificación:</b> {{ list.classification }}</p>
                            <p class="mb-2 text-lg"><b>Descripción:</b> {{ list.description }}</p>
                        </template>
                        <template #footer>
                            <div class="flex gap-4 mt-1">
                                <Button
                                    v-if="(can('classifications.delete'))"
                                    label="Eliminar"
                                    severity="danger"
                                    class="w-full"
                                    icon="pi pi-trash"
                                    @click="deleteItem(list)"
                                    :disabled="processingRows[list.id]"
                                    :loading="processingRows[list.id]"
                                />
                                <Button
                                    v-if="(can('classifications.edit'))"
                                    label="Editar"
                                    severity="warn"
                                    icon="pi pi-pencil"
                                    class="w-full"
                                    @click="editStatusReason(list)"
                                    :disabled="processingRows[list.id]"
                                    :loading="processingRows[list.id]"
                                />
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <Paginator
                :first="first"
                :rows="rows"
                :totalRecords="totalRecords"
                @page="onPage"
            />

            <!-- Dialogo Crear/Editar -->
            <Dialog
                v-model:visible="dialog"
                :style="{ minWidth: '450px' }"
                :header="dialogTitle"
                :modal="true"
            >
                <div class="flex flex-col gap-6">

                    <!-- Planta -->
                    <div>
                        <label for="branch_office_id" class="block font-bold mb-2">
                            Planta <span class="text-red-500">*</span>
                        </label>
                        <Select
                            id="branch_office_id"
                            v-model="form.branch_office_id"
                            :options="props.BranchOffices"
                            optionValue="id"
                            optionLabel="code"
                            placeholder="Seleccione una opción"
                            class="w-full"
                            filter
                            :invalid="!!frontErrors.branch_office_id"
                            @change="clearError('branch_office_id')"
                        >
                        </Select>
                        <Message
                            v-if="frontErrors.branch_office_id"
                            severity="error"
                            size="medium"
                            variant="simple"
                        >
                            {{ frontErrors.branch_office_id }}
                        </Message>
                    </div>

                    <!-- Clasificación -->
                    <div class="mb-4">
                        <label class="block font-bold mb-2">
                            Clasificación <span class="text-red-500">*</span>
                        </label>
                        <InputNumber v-model="form.classification"
                            placeholder="Clasificación"
                            class="w-full"
                            :min="0"
                            :inputClass="{ 'p-invalid': frontErrors.classification }" 
                            @blur="clearError('classification')"
                        />
                        <Message
                            v-if="frontErrors.classification"
                            severity="error"
                            size="medium"
                            variant="simple"
                        >
                            {{ frontErrors.classification }}
                        </Message>
                    </div>

                    <!-- Descripción -->
                    <div class="col-span-2 mb-4">
                        <label class="block font-bold mb-2">
                            Descripción 
                        </label>
                        <Textarea v-model="form.description"
                            rows="3"
                            autoResize
                            placeholder="Descripción..."
                            class="w-full" 
                            :maxlength="1000"
                            :inputClass="{ 'p-invalid': frontErrors.description }" 
                            @input="clearError('description')"
                        />
                        <small class="text-gray-500">{{ form.description?.length || 0 }}/1000</small>
                        <Message
                            v-if="frontErrors.description"
                            severity="error"
                            size="medium"
                            variant="simple"
                        >
                            {{ frontErrors.description }}
                        </Message>
                    </div>

                </div>

                <template #footer>
                    <Button
                        label="Cancelar"
                        icon="pi pi-times"
                        severity="secondary" 
                        text
                        @click="dialog = false"
                        :loading="form.processing" 
                        :disabled="form.processing" 
                    />
                    <Button
                        label="Guardar"
                        icon="pi pi-save" 
                        severity="success" 
                        @click="saveItem"
                        :loading="form.processing" 
                        :disabled="form.processing" 
                    />
                </template>
            </Dialog>

        </div>
    </AppLayout>
</template>

