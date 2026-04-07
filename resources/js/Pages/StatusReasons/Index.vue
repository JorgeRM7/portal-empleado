<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import { useToastService } from "../../Stores/toastService.js";
import { useAuthz } from "@/composables/useAuthz";
const { can } = useAuthz();

const { showSuccess, showError } = useToastService();

const props = defineProps({
    StatusReasons: Array,
});

// Formulario base
const statusReason = useForm({
    id: null,
    name: "",
});

// Estados generales
const search = ref("");
const submitted = ref(false);
const statusReasonDialog = ref(false);
const deleteStatusReasonDialog = ref(false);
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
    if (!search.value) return props.StatusReasons;
    const q = normalize(search.value);
    return props.StatusReasons.filter((item) =>
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
    statusReason.reset();
    statusReason.id = null;
    submitted.value = false;
    statusReasonDialog.value = true;
};

// ✏️ Editar
const editStatusReason = (item) => {
    statusReason.id = item.id;
    statusReason.name = item.name;
    statusReasonDialog.value = true;
};

// 💾 Guardar (crear o actualizar)
const saveStatusReason = () => {
    submitted.value = true;

    if (statusReason.name) {
        if (statusReason.id) {
            statusReason.put(`/catalogs/status-reasons/${statusReason.id}`, {
                onSuccess: () => {
                    statusReasonDialog.value = false;
                    statusReason.reset();
                    showSuccess();
                    submitted.value = false;
                },
                onError: () => {
                    showError();
                    submitted.value = false;
                },
            });
        } else {
            statusReason.post("/catalogs/status-reasons", {
                onSuccess: () => {
                    statusReasonDialog.value = false;
                    statusReason.reset();
                    showSuccess();
                    submitted.value = false;
                },
                onError: () => {
                    showError();
                    submitted.value = false;
                },
            });
        }
    }else{
        submitted.value = false;
    }
};

// 🗑️ Eliminar
const deleteStatusReason = () => {
    submitted.value = true;
    statusReason.delete(`/catalogs/status-reasons/${statusReason.id}`, {
        onSuccess: () => {
            deleteStatusReasonDialog.value = false;
            statusReason.reset();
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
    <AppLayout :title="'Razones de estado'">
        <div class="card border-none">
            <Toolbar class="mb-6 px-10">
                <template #end>
                    <Button
                        label="Crear"
                        icon="pi pi-plus-circle"
                        severity="success"
                        class="ml-2"
                        @click="openNew"
                        v-if="(can('status-reasons.create'))"
                    />
                </template>
            </Toolbar>

            <h4 class="m-0">Razones de estado</h4>

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
                                    label="Eliminar"
                                    severity="danger"
                                    class="w-full"
                                    icon="pi pi-trash"
                                    @click="(deleteStatusReasonDialog = true, statusReason.id = list.id, statusReason.name = list.name)"
                                    v-if="(can('status-reasons.delete'))"
                                />
                                <Button
                                    label="Editar"
                                    severity="warn"
                                    icon="pi pi-pencil"
                                    class="w-full"
                                    @click="editStatusReason(list)"
                                    v-if="(can('status-reasons.edit'))"
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
                v-model:visible="statusReasonDialog"
                :style="{ width: '450px' }"
                header="Añadir o Editar Razon De Estado"
                :modal="true"
            >
                <div class="flex flex-col gap-6">
                    <div>
                        <label for="name" class="block font-bold mb-3">Razon De Estado</label>
                        <InputText
                            id="name"
                            v-model.trim="statusReason.name"
                            required
                            autofocus
                            :invalid="submitted || !statusReason.name"
                            fluid
                        />
                        <small v-if="statusReason.errors.name" class="text-red-500">
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
                        @click="statusReasonDialog = false"
                    />
                    <Button
                        label="Guardar"
                        icon="pi pi-save" 
                        severity="success" 
                        @click="saveStatusReason"
                        :loading="submitted"
                        :class="{ 'p-invalid': submitted && !statusReason.name }"
                    />
                </template>
            </Dialog>

            <!-- Dialogo Eliminar -->
            <Dialog
                v-model:visible="deleteStatusReasonDialog"
                :style="{ width: '450px' }"
                header="Confirmar"
                :modal="true"
            >
                <div class="flex items-center gap-4">
                    <i class="pi pi-exclamation-triangle !text-3xl" />
                    <span>
                        ¿Seguro que deseas eliminar la razón de estado
                        <b>{{ statusReason.name }}</b>?
                    </span>
                </div>
                <template #footer>
                    <Button
                        label="No"
                        icon="pi pi-times"
                        text
                        @click="deleteStatusReasonDialog = false"
                    />
                    <Button
                        label="Sí"
                        icon="pi pi-check"
                        @click="deleteStatusReason"
                        severity="danger"
                        :loading="submitted"
                    />
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>

