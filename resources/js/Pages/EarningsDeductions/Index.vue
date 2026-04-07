<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import { useToastService } from "../../Stores/toastService.js";
import { useAuthz } from "@/composables/useAuthz";
const { can } = useAuthz();

const { showSuccess, showError } = useToastService();

const props = defineProps({
    EarningsDeductions: Array,
});

// Formulario base
const earningDeduction = useForm({
    id: null,
    name: "",
    code: "",
    description: "",
    rules: "",
    apply: "",
    apply_piecework: "",
    type: "",
});

// Selects
const pieceworkOptions = ref([
    { name: 'Si', code: 'S' },
    { name: 'No', code: 'N' },
]);

// Selects
const typeOptions = ref([
    { name: 'Percepción', code: 'P' },
    { name: 'Deducción', code: 'D' },
]);

// Estados generales
const search = ref("");
const submitted = ref(false);
const statusEarningDeductionDialog = ref(false);
const deleteEarningDeductionDialog = ref(false);
const first = ref(0);
const rows = ref(9);
const filterPanel = ref(null);

// 🔍 Función de normalización para búsqueda
const normalize = (s) =>
    (s ?? "")
        .toString()
        .normalize("NFD")
        .replace(/\p{Diacritic}/gu, "")
        .toLowerCase();

// 🔎 Filtrado
const filteredLists = computed(() => {
    const q = normalize(search.value);

    return props.EarningsDeductions.filter((item) => {
        // Filtro por búsqueda
        const matchesSearch =
            !q ||
            [item.name, item.code, item.type].some((field) =>
                normalize(field).includes(q)
            );

        // Filtro por tipo
        const matchesType =
            typeFilter.value === "ALL" || item.type === typeFilter.value;

        return matchesSearch && matchesType;
    });
});

// Boton de filtro
const typeFilter = ref("ALL");

const selectFilter = (value) => {
    typeFilter.value = value;
    filterPanel.value.hide(); // cerrar el panel
    first.value = 0;          // reiniciar paginación si aplicas paginación
};

const clearFilter = () => {
    typeFilter.value = "ALL";  // reset tipo
    search.value = "";         // reset búsqueda
    first.value = 0;           // reinicia paginación
};


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

// ➕ Crear nuevo
const openNew = () => {
    earningDeduction.reset();
    earningDeduction.id = null;
    submitted.value = false;
    statusEarningDeductionDialog.value = true;
};

// ✏️ Editar
const editEarningDeduction = (item) => {
    earningDeduction.id = item.id;
    earningDeduction.name = item.name;
    earningDeduction.code = item.code;
    earningDeduction.description = item.description;
    earningDeduction.rules = item.rules;
    earningDeduction.apply =
        item.apply && item.apply.toLowerCase() !== 'n'
            ? item.apply
            : '';
    earningDeduction.apply_piecework = item.apply_piecework;
    earningDeduction.type = item.type;
    statusEarningDeductionDialog.value = true;
};

// 💾 Guardar (crear o actualizar)
const saveEarningDeduction = () => {
    submitted.value = true;

    if (earningDeduction.name && earningDeduction.code && earningDeduction.apply_piecework && earningDeduction.type) {
        if (earningDeduction.id) {
            earningDeduction.put(`/catalogs/earnings-deductions/${earningDeduction.id}`, {
                onSuccess: () => {
                    statusEarningDeductionDialog.value = false;
                    earningDeduction.reset();
                    showSuccess();
                    submitted.value = false;
                },
                onError: () => {
                    showError();
                    submitted.value = false;
                },
            });
        } else {
            earningDeduction.post("/catalogs/earnings-deductions", {
                onSuccess: () => {
                    statusEarningDeductionDialog.value = false;
                    earningDeduction.reset();
                    showSuccess();
                    submitted.value = false;
                },
                onError: () => {
                    showError();
                    submitted.value = false;
                },
            });
        }
    }
};

// 🗑️ Eliminar
const deleteEarningDeduction = () => {
    submitted.value = true;
    earningDeduction.delete(`/catalogs/earnings-deductions/${earningDeduction.id}`, {
        onSuccess: () => {
            deleteEarningDeductionDialog.value = false;
            earningDeduction.reset();
            showSuccess();
            submitted.value = false;
        },
        onError: () => {
            showError();
            submitted.value = false;
        },
    });
};
</script>

<template>
    <AppLayout :title="'Percepciones y Deducciones'">
        <div class="card border-none">
            <Toolbar class="mb-6 px-10">
                <template #end>
                    <Button
                        label="Crear"
                        icon="pi pi-plus-circle"
                        severity="success"
                        class="ml-2"
                        @click="openNew"
                        v-if="(can('earnings-deductions.create'))"
                    />
                </template>
            </Toolbar>

            <h4 class="m-0">Percepciones y Deducciones</h4>
            <div class="flex flex-wrap gap-2 items-end justify-between mb-6">
                <div>
                    <!-- <Button
                        icon="pi pi-filter"
                        rounded
                        v-tooltip.top="'Mostrar mas filtros'"
                        @click="otherFilterDialog = true"
                    /> -->
                    <Button
                        icon="pi pi-filter"
                        rounded
                        v-tooltip.top="'Filtrar por tipo'"
                        @click="filterPanel.toggle($event)"
                    />
                    <Popover ref="filterPanel">
                        <div class="flex flex-col w-40">
                            <Button label="Todos" icon="pi pi-circle" @click="selectFilter('ALL')" text />
                            <Button label="Percepciones" icon="pi pi-arrow-up" @click="selectFilter('P')" text />
                            <Button label="Deducciones" icon="pi pi-arrow-down" @click="selectFilter('D')" text />
                        </div>
                    </Popover>
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
            </div>

            <!-- Cards -->
            <div class="grid grid-cols-3 gap-4">
                <div v-for="list in pagedLists" :key="list.id">
                    <Card>
                        <template #title>
                            <span class="font-bold">{{ list.name }}</span>
                        </template>
                        <template #content>
                            <div class="text-sm text-gray-700 space-y-2">
                                <p>
                                    <b>Código:</b> {{ list.code }}
                                </p>
                                <p>
                                    <b>Tipo:</b> {{ list.type }}
                                </p>
                                <p v-if="list.rules">
                                    <b>Reglas:</b> {{ list.rules }}
                                </p>
                                <p>
                                    <b>Aplica destajo:</b> 
                                    {{ list.apply_piecework }}
                                </p>
                            </div>
                        </template>
                        <template #footer>
                            <div class="flex gap-4 mt-1">
                                <Button
                                    label="Eliminar"
                                    severity="danger"
                                    class="w-full"
                                    icon="pi pi-trash"
                                    @click="(deleteEarningDeductionDialog = true, earningDeduction.id = list.id, earningDeduction.name = list.name)"
                                    v-if="(can('earnings-deductions.delete'))"
                                />
                                <Button
                                    label="Editar"
                                    severity="warn"
                                    icon="pi pi-pencil"
                                    class="w-full"
                                    @click="editEarningDeduction(list)"
                                    v-if="(can('earnings-deductions.edit'))"
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
                v-model:visible="statusEarningDeductionDialog"
                :style="{ width: '450px' }"
                header="Añadir o Editar Percepcion o Deduccion"
                :modal="true"
            >
                <div class="flex flex-col gap-6">
                    <div>
                        <label for="name" class="block font-bold mb-3">Nombre</label>
                        <InputText
                            id="name"
                            v-model.trim="earningDeduction.name"
                            required
                            autofocus
                            :invalid="submitted && !earningDeduction.name"
                            fluid
                        />
                        <Message 
                            v-if="submitted && !earningDeduction.name" 
                            severity="error"
                            size="medium"
                            variant="simple"
                        >
                            El nombre es requerido
                        </Message>
                    </div>
                    <div>
                        <label for="code" class="block font-bold mb-3">Código</label>
                        <InputText
                            id="code"
                            v-model.trim="earningDeduction.code"
                            required
                            autofocus
                            :invalid="submitted && !earningDeduction.code"
                            fluid
                        />
                        <Message 
                            v-if="submitted && !earningDeduction.code" 
                            severity="error"
                            size="medium"
                            variant="simple"
                        >
                            El código es requerido
                        </Message>
                    </div>
                    <div>
                        <label for="description" class="block font-bold mb-3">Descripción</label>
                        <InputText
                            id="description"
                            v-model.trim="earningDeduction.description"
                            autofocus
                            fluid
                        />
                    </div>
                    <div>
                        <label for="rules" class="block font-bold mb-3">Reglas</label>
                        <InputText
                            id="rules"
                            v-model.trim="earningDeduction.rules"
                            autofocus
                            fluid
                        />
                    </div>
                    <div>
                        <label for="apply" class="block font-bold mb-3">Aplicación</label>
                        <InputNumber 
                            id="apply"
                            v-model.trim="earningDeduction.apply"
                            autofocus
                            inputId="minmax-buttons" 
                            mode="decimal" 
                            showButtons 
                            fluid 
                        />
                    </div>
                    <div>
                        <label for="apply_piecework" class="block font-bold mb-3">Aplica destajo</label>
                        <Select 
                            id="apply_piecework"
                            v-model.trim="earningDeduction.apply_piecework"
                            required
                            autofocus
                            :invalid="submitted && !earningDeduction.apply_piecework"
                            fluid
                            :options="pieceworkOptions"
                            optionLabel="name"
                            optionValue="code"
                        />
                        <Message 
                            v-if="submitted && !earningDeduction.apply_piecework" 
                            severity="error"
                            size="medium"
                            variant="simple"
                        >
                            Aplica destajo es requerido
                        </Message>
                    </div>
                    <div>
                        <label for="type" class="block font-bold mb-3">Percepción o deducción</label>
                        <Select 
                            id="type"
                            v-model.trim="earningDeduction.type"
                            required
                            autofocus
                            :invalid="submitted && !earningDeduction.type"
                            fluid
                            :options="typeOptions"
                            optionLabel="name"
                            optionValue="code"
                        />
                        <Message 
                            v-if="submitted && !earningDeduction.type" 
                            severity="error"
                            size="medium"
                            variant="simple"
                        >
                            La percepción o deducción es requerida
                        </Message>
                    </div>

                </div>

                <template #footer>
                    <Button
                        label="Cancelar"
                        icon="pi pi-times"
                        severity="secondary" 
                        text
                        @click="statusEarningDeductionDialog = false"
                    />
                    <Button
                        label="Guardar"
                        icon="pi pi-save" 
                        severity="success" 
                        @click="saveEarningDeduction"
                        :loading="earningDeduction.processing"
                        :class="{ 'p-invalid': submitted && !earningDeduction.name }"
                    />
                </template>
            </Dialog>

            <!-- Dialogo Eliminar -->
            <Dialog
                v-model:visible="deleteEarningDeductionDialog"
                :style="{ width: '450px' }"
                header="Confirmar"
                :modal="true"
            >
                <div class="flex items-center gap-4">
                    <i class="pi pi-exclamation-triangle !text-3xl" />
                    <span>
                        ¿Seguro que deseas eliminar la razón de estado
                        <b>{{ earningDeduction.name }}</b>?
                    </span>
                </div>
                <template #footer>
                    <Button
                        label="No"
                        icon="pi pi-times"
                        text
                        @click="deleteEarningDeductionDialog = false"
                    />
                    <Button
                        label="Sí"
                        icon="pi pi-check"
                        @click="deleteEarningDeduction"
                        severity="danger"
                        :loading="submitted"
                    />
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>

