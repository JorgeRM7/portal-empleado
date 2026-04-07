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
    CategoryIncidences: Object,
});
// console.log(props.CategoryIncidences);

// Formulario base
const form = useForm({
    id: null,
    name: "",
    code: "",
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
    if (!search.value) return props.CategoryIncidences;
    const q = normalize(search.value);
    return props.CategoryIncidences.filter((item) =>
        [item.name].some((field) =>
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
        ? 'Editar categoría de incidencia'
        : 'Crear categoría de incidencia'
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
    form.name = item.name;
    form.code = item.code;
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
        form.put(`/catalogs/category-incidences/${form.id}`, {
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
        form.post('/catalogs/category-incidences', {
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
    // console.log('Eliminar');

    const truncate = (text, max = 80) =>
    text.length > max ? text.slice(0, max) + '…' : text;

    confirm.require({
        message: `¿Deseas eliminar la categoría "${truncate(row.name)}"?`,
        header: 'Confirmar eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Sí, eliminar',
        rejectLabel: 'Cancelar',
        acceptClass: 'p-button-danger',

        accept: () => {
            processingRows.value[row.id] = true;

            router.delete(`/catalogs/category-incidences/${row.id}`, {
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

    // Nombre
    if (!form.name) {
        frontErrors.name = 'El nombre es obligatorio';
    } else if (form.name.length > 255) {
        frontErrors.name = 'El nombre no debe exceder los 255 caracteres';
    }
    
    // Código común de incidencia
    if (!form.code) {
        frontErrors.code = 'El código común de incidencia es obligatorio';
    } else if (form.code.length > 255) {
        frontErrors.code = 'El código común de incidencia no debe exceder los 255 caracteres';
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
    <AppLayout :title="'Categorías de Incidencias'">
        <ConfirmDialog />
        <div class="card border-none">
            <Toolbar class="mb-6 px-10">
                <template #end>
                    <Button
                        v-if="(can('category-incidences.create'))"
                        label="Crear"
                        icon="pi pi-plus-circle"
                        severity="success"
                        class="ml-2"
                        @click="openNew"
                    />
                </template>
            </Toolbar>

            <h4 class="m-0">Categorías de Incidencias</h4>

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
                            <p class="mb-2">codigo: {{ list.code }}</p>
                            <p class="mb-2">id: {{ list.id }}</p>
                            <!-- <p class="mb-2">{{ list.description }}</p>
                            <Tag
                                icon="pi pi-tag"
                                severity="info"
                                :value="'Tipo: ' + list.type"
                            /> -->
                        </template>
                        <template #footer>
                            <div class="flex gap-4 mt-1">
                                <Button
                                    v-if="(can('category-incidences.delete'))"
                                    label="Eliminar"
                                    severity="danger"
                                    class="w-full"
                                    icon="pi pi-trash"
                                    @click="deleteItem(list)"
                                    :disabled="processingRows[list.id]"
                                    :loading="processingRows[list.id]"
                                />
                                <Button
                                    v-if="(can('category-incidences.edit'))"
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
                    <div>
                        <label for="name" class="block font-bold mb-3">Nombre <span class="text-red-500">*</span></label>
                        <InputText
                            id="name"
                            v-model.trim="form.name"
                            placeholder="Introduce el nombre" 
                            :class="{ 'p-invalid': frontErrors.name }" 
                            class="w-full"
                            @input="clearError('name')" 
                            required
                            :maxlength="255"
                        />
                        <Message
                            v-if="frontErrors.name"
                            severity="error"
                            size="medium"
                            variant="simple"
                        >
                            {{ frontErrors.name }}
                        </Message>
                    </div>
                    <div>
                        <label for="code" class="block font-bold mb-3">Código común de incidencia <span class="text-red-500">*</span></label>
                        <InputText
                            id="code"
                            v-model.trim="form.code"
                            placeholder="Introduce el codigo" 
                            :class="{ 'p-invalid': frontErrors.code }" 
                            class="w-full"
                            @input="clearError('code')" 
                            required
                            :maxlength="255"
                        />
                        <Message
                            v-if="frontErrors.code"
                            severity="error"
                            size="medium"
                            variant="simple"
                        >
                            {{ frontErrors.code }}
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

