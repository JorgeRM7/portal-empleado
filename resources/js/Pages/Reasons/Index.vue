<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import { useToastService } from "../../Stores/toastService.js";
import { useAuthz } from "@/composables/useAuthz";

const { can } = useAuthz();

const { showSuccess, showError } = useToastService();

const props = defineProps({
    Reasons: Array,
});

// Formulario base
const reason = useForm({
    id: null,
    name: "",
    description: "",
    type: "",
});

// Estados generales
const search = ref("");
const submitted = ref(false);
const reasonsDialog = ref(false);
const deleteReasonsDialog = ref(false);
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
    if (!search.value) return props.Reasons;
    const q = normalize(search.value);
    return props.Reasons.filter((item) =>
        [item.name, item.description, item.type].some((field) =>
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
    reason.reset();
    reason.id = null;
    submitted.value = false;
    reasonsDialog.value = true;
};

// ✏️ Editar
const editReason = (item) => {
    reason.id = item.id;
    reason.name = item.name;
    reason.description = item.description;
    reason.type = item.type;
    reasonsDialog.value = true;
};

// 💾 Guardar (crear o actualizar)
const saveReason = () => {
    submitted.value = true;

    if (reason.name && reason.description && reason.type) {
        if (reason.id) {
            reason.put(`/catalogs/reasons/${reason.id}`, {
                onSuccess: () => {
                    reasonsDialog.value = false;
                    reason.reset();
                    showSuccess();
                    submitted.value = false;
                },
                onError: () => {
                    showError();
                    submitted.value = false;
                },
            });
        } else {
            reason.post("/catalogs/reasons", {
                onSuccess: () => {
                    reasonsDialog.value = false;
                    reason.reset();
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
const deleteReason = () => {
    submitted.value = true;
    reason.delete(`/catalogs/reasons/${reason.id}`, {
        onSuccess: () => {
            deleteReasonsDialog.value = false;
            reason.reset();
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
    <AppLayout :title="'Motivos'">
        <div class="card border-none">
            <Toolbar class="mb-6 px-10">
                <template #end>
                    <Button
                        v-if="(can('reasons.create'))"
                        label="Crear"
                        icon="pi pi-plus-circle"
                        severity="success"
                        class="ml-2"
                        @click="openNew"
                    />
                </template>
            </Toolbar>

            <h4 class="m-0">Motivos</h4>

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
                            <p class="mb-2">{{ list.description }}</p>
                            <Tag
                                icon="pi pi-tag"
                                severity="info"
                                :value="'Tipo: ' + list.type"
                            />
                        </template>
                        <template #footer>
                            <div class="flex gap-4 mt-1">
                                <Button
                                    v-if="(can('reasons.delete'))"
                                    label="Eliminar"
                                    severity="danger"
                                    class="w-full"
                                    icon="pi pi-trash"
                                    @click="(deleteReasonsDialog = true, reason.id = list.id, reason.name = list.name)"
                                />
                                <Button
                                    v-if="(can('reasons.edit'))"
                                    label="Editar"
                                    severity="warn"
                                    icon="pi pi-pencil"
                                    class="w-full"
                                    @click="editReason(list)"
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
                v-model:visible="reasonsDialog"
                :style="{ width: '450px' }"
                header="Añadir o Editar Motivo"
                :modal="true"
            >
                <div class="flex flex-col gap-6">
                    <div>
                        <label for="name" class="block font-bold mb-3">Motivo</label>
                        <InputText
                            id="name"
                            v-model.trim="reason.name"
                            required
                            autofocus
                            :invalid="submitted && !reason.name"
                            fluid
                            maxlength="255"
                        />
                        <Message
                            v-if="submitted && !reason.name"
                            severity="error"
                            size="medium"
                            variant="simple"
                        >
                            El nombre es requerido
                        </Message>
                    </div>

                    <div>
                        <label for="description" class="block font-bold mb-3">
                            Descripción
                        </label>
                        <Textarea
                            id="description"
                            v-model.trim="reason.description"
                            required
                            rows="3"
                            :invalid="submitted && !reason.description"
                            fluid
                            maxlength="500"
                        />
                        <Message
                            v-if="submitted && !reason.description"
                            severity="error"
                            size="medium"
                            variant="simple"
                        >
                            La descripción es requerida.
                        </Message>
                    </div>

                    <div>
                        <label for="type" class="block font-bold mb-3">Tipo</label>
                        <InputText
                            id="type"
                            v-model.trim="reason.type"
                            required
                            :invalid="submitted && !reason.type"
                            fluid
                            maxlength="255"
                        />
                        <Message
                            v-if="submitted && !reason.type"
                            severity="error"
                            size="medium"
                            variant="simple"
                        >
                            El tipo es requerido.
                        </Message>
                    </div>
                </div>

                <template #footer>
                    <Button
                        label="Cancelar"
                        icon="pi pi-times"
                        severity="secondary" 
                        text
                        @click="reasonsDialog = false"
                    />
                    <Button
                        label="Guardar"
                        icon="pi pi-save" 
                        severity="success" 
                        @click="saveReason"
                        :loading="reason.processing"
                    />
                </template>
            </Dialog>

            <!-- Dialogo Eliminar -->
            <Dialog
                v-model:visible="deleteReasonsDialog"
                :style="{ width: '450px' }"
                header="Confirmar"
                :modal="true"
            >
                <div class="flex items-center gap-4">
                    <i class="pi pi-exclamation-triangle !text-3xl" />
                    <span>
                        ¿Seguro que deseas eliminar el motivo
                        <b>{{ reason.name }}</b>?
                    </span>
                </div>
                <template #footer>
                    <Button
                        label="No"
                        icon="pi pi-times"
                        text
                        @click="deleteReasonsDialog = false"
                    />
                    <Button
                        label="Sí"
                        icon="pi pi-check"
                        @click="deleteReason"
                        severity="danger"
                        :loading="submitted"
                    />
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>

