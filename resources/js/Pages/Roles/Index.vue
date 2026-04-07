<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, computed, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import ConfirmationDialog from "@/Components/ConfirmationDialog.vue";
import { useToastService } from "@/Stores/toastService";
import { useToast } from "primevue";

const props = defineProps({
    roles: Array,
});

const { showError, showSuccess } = useToastService();
const toast = useToast();

const page = usePage();
const loading = ref(false);
const first = ref(0);
const rows = ref(10);
const search = ref("");
const deleteDialog = ref(false);
const deleteId = ref(null);

const normalize = (s) =>
    (s ?? "")
        .toString()
        .normalize("NFD")
        .replace(/\p{Diacritic}/gu, "")
        .toLowerCase();

const filteredLists = computed(() => {
    if (!search.value) return props.roles;

    const q = normalize(search.value);
    return props.roles.filter((item) => {
        return normalize(item.name).includes(q);
    });
});

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

function confirmDeleteRol(id) {
    deleteDialog.value = true;
    deleteId.value = id;
}

const deleteRole = () => {
    if (!deleteId.value) return;

    loading.value = true;

    router.delete(`/roles/${deleteId.value}`, {
        preserveScroll: true,
        onSuccess: () => {
            console.log(page.props.flash);
            if (page.props.flash.error) {
                toast.add({
                    severity: "warn",
                    summary: "Error parciales al borrar",
                    detail: page.props.flash.error,
                });
                deleteDialog.value = false;
                deleteId.value = null;
                loading.value = false;
                return;
            }
            showSuccess();
            deleteDialog.value = false;
            deleteId.value = null;
            loading.value = false;
        },
        onError: (errors) => {
            showError();
            deleteDialog.value = false;
            deleteId.value = null;
            loading.value = false;
        },
    });
};

watch([rows, filteredLists], () => {
    if (first.value >= totalRecords.value) first.value = 0;
});
</script>
<template>
    <AppLayout :title="'Roles'">
        <div class="card border-none">
            <Toolbar class="mb-6">
                <template #end>
                    <Link href="roles/create">
                        <Button
                            label="Añadir Rol"
                            icon="pi pi-plus"
                            severity="success"
                            class="mr-2"
                        />
                    </Link>
                </template>
            </Toolbar>
            <h2 class="mb-0 px-2">Roles</h2>
            <div class="flex justify-content-end mb-4 px-2">
                <IconField>
                    <InputIcon>
                        <i class="pi pi-search" />
                    </InputIcon>
                    <InputText
                        placeholder="Buscar..."
                        v-model="search"
                        @input="onSearch"
                        fluid
                    />
                </IconField>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div v-for="list in pagedLists" :key="list.id" class="">
                    <Card>
                        <template #title>{{ list.name }} </template>
                        <template #footer>
                            <div class="flex gap-4 mt-1">
                                <Button
                                    label="Eliminar"
                                    severity="danger"
                                    class="w-full"
                                    icon="pi pi-trash"
                                    @click="confirmDeleteRol(list.id)"
                                />
                                <Button
                                    label="Editar"
                                    severity="warn"
                                    icon="pi pi-pencil"
                                    class="w-full"
                                    @click="
                                        () => {
                                            router.get(`roles/${list.id}/edit`);
                                        }
                                    "
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

            <ConfirmationDialog
                :visible="deleteDialog"
                @confirm="deleteRole"
                @cancel="deleteDialog = false"
                :loading="loading"
                header="¿Estás seguro de eliminar el rol?"
                confirmOrDelete="delete"
            />
        </div>
    </AppLayout>
</template>
