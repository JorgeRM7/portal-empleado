<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import { useToastService } from "../../Stores/toastService.js";
import { useAuthz } from "@/composables/useAuthz";

const { can } = useAuthz();

const { showSuccess, showError } = useToastService();

const props = defineProps({
    MovementTypes: Array,
});

// Formulario base
const movementType = useForm({
    id: null,
    name: "",
});

// Estados generales
const search = ref("");
const submitted = ref(false);
const movementTypeDialog = ref(false);
const deleteMovementTypeDialog = ref(false);
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
    if (!search.value) return props.MovementTypes;
    const q = normalize(search.value);
    return props.MovementTypes.filter((item) =>
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

// ➕ Crear nuevo
const openNew = () => {
    movementType.reset();
    movementType.id = null;
    submitted.value = false;
    movementTypeDialog.value = true;
};

// ✏️ Editar
const editMovementType = (item) => {
    movementType.id = item.id;
    movementType.name = item.name;
    movementTypeDialog.value = true;
};

// 💾 Guardar (crear o actualizar)
const saveMovementType = () => {
    submitted.value = true;

    if (movementType.name) {
        if (movementType.id) {
            movementType.put(`/catalogs/movement-types/${movementType.id}`, {
                onSuccess: () => {
                    movementTypeDialog.value = false;
                    movementType.reset();
                    showSuccess();
                    submitted.value = false;
                },
                onError: () => {
                    showError();
                    submitted.value = false;
                },
            });
        } else {
            movementType.post("/catalogs/movement-types", {
                onSuccess: () => {
                    movementTypeDialog.value = false;
                    movementType.reset();
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
const deleteMovementType = () => {
    submitted.value = true;
    movementType.delete(`/catalogs/movement-types/${movementType.id}`, {
        onSuccess: () => {
            deleteMovementTypeDialog.value = false;
            movementType.reset();
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
    <AppLayout :title="'Tipos De Movimientos'">
        <div class="card border-none">
            <Toolbar class="mb-6 px-10">
                <template #end>
                    <Button
                        v-if="(can('movement-types.create'))"
                        label="Crear"
                        icon="pi pi-plus-circle"
                        severity="success"
                        class="ml-2"
                        @click="openNew"
                    />
                </template>
            </Toolbar>

            <h4 class="m-0">Tipos De Movimientos</h4>

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
                                    v-if="(can('movement-types.delete'))"
                                    label="Eliminar"
                                    severity="danger"
                                    class="w-full"
                                    icon="pi pi-trash"
                                    @click="(deleteMovementTypeDialog = true, movementType.id = list.id, movementType.name = list.name)"
                                />
                                <Button
                                    v-if="(can('movement-types.edit'))"
                                    label="Editar"
                                    severity="warn"
                                    icon="pi pi-pencil"
                                    class="w-full"
                                    @click="editMovementType(list)"
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
                v-model:visible="movementTypeDialog"
                :style="{ width: '450px' }"
                header="Añadir o Editar Tipo De Movimiento"
                :modal="true"
            >
                <div class="flex flex-col gap-6">
                    <div>
                        <label for="name" class="block font-bold mb-3">Tipo De Movimiento</label>
                        <InputText
                            id="name"
                            v-model.trim="movementType.name"
                            required
                            autofocus
                            :invalid="submitted && !movementType.name"
                            fluid
                            :maxlength="255"
                        />
                        <small v-if="submitted && !movementType.name" class="text-red-500">
                            El nombre es requerido
                        </small>
                    </div>

                </div>

                <template #footer>
                    <Button
                        label="Cancelar"
                        icon="pi pi-times"
                        severity="secondary" 
                        text
                        @click="movementTypeDialog = false"
                    />
                    <Button
                        label="Guardar"
                        icon="pi pi-save" 
                        severity="success" 
                        @click="saveMovementType"
                        :loading="movementType.processing"
                        :class="{ 'p-invalid': submitted && !movementType.name }"
                    />
                </template>
            </Dialog>

            <!-- Dialogo Eliminar -->
            <Dialog
                v-model:visible="deleteMovementTypeDialog"
                :style="{ width: '450px' }"
                header="Confirmar"
                :modal="true"
            >
                <div class="flex items-center gap-4">
                    <i class="pi pi-exclamation-triangle !text-3xl" />
                    <span>
                        ¿Seguro que deseas eliminar el tipo de movimiento
                        <b>{{ movementType.name }}</b>?
                    </span>
                </div>
                <template #footer>
                    <Button
                        label="No"
                        icon="pi pi-times"
                        text
                        @click="deleteMovementTypeDialog = false"
                    />
                    <Button
                        label="Sí"
                        icon="pi pi-check"
                        @click="deleteMovementType"
                        severity="danger"
                        :loading="submitted"
                    />
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>

