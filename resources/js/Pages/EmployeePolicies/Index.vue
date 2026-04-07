<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { router, usePage } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import { useToastService } from "../../Stores/toastService.js";
import { useConfirm } from 'primevue/useconfirm';
import { useAuthz } from "@/composables/useAuthz";

const { can } = useAuthz();

const { showSuccessCustom, showError, showValidationError, showErrorCustom } = useToastService();

const page = usePage();

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            showSuccessCustom(flash.success);
        }

        if (flash?.error) {
            showErrorCustom(flash.error);
        }
    },
    { immediate: true }
);

const props = defineProps({
	EmployeePolicy: Object,
});
console.log(props.EmployeePolicy);

// Estados generales
const search = ref("");
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
	if (!search.value) return props.EmployeePolicy;
	const q = normalize(search.value);
	return props.EmployeePolicy.filter((item) =>
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

// ---------------------- Eliminar ----------------------
const deleteItem = (row) => {
	confirm.require({
		message: `¿Deseas eliminar la regla "${row.name}"?`,
		header: 'Confirmar eliminación',
		icon: 'pi pi-exclamation-triangle',
		acceptLabel: 'Sí, eliminar',
		rejectLabel: 'Cancelar',
		acceptClass: 'p-button-danger',

		accept: () => {
			processingRows.value[row.id] = true;

			router.delete(`/catalogs/policies/${row.id}`, {
				preserveScroll: true,
				// onSuccess: () => {
				// 	showSuccessCustom('Registro eliminado correctamente');
				// },
				// onError: () => {
				// 	showError('Error al eliminar el registro');
				// },
				onFinish: () => {
					processingRows.value[row.id] = false;
				}
			});
		},
	});
};

const editItem = (id) => {
    if (processingRows.value[id]) return;

    processingRows.value[id] = true;

    router.get(`/catalogs/policies/${id}/edit`, {
        onFinish: () => {
            processingRows.value[id] = false;
        }
    });
};

const viewItem = (id) => {
    if (processingRows.value[id]) return;

    processingRows.value[id] = true;

    router.get(`/catalogs/policies/${id}`, {
        onFinish: () => {
            processingRows.value[id] = false;
        }
    });
};

</script>

<template>
	<AppLayout :title="'Reglas'">
		<ConfirmDialog />
		<div class="card border-none">
			<Toolbar class="mb-6 px-10">
				<template #end>
					<Link
                        href="/catalogs/policies/create"
                    >
                        <Button
							v-if="(can('policies.create'))"
                            label="Crear"
                            icon="pi pi-plus-circle"
                            severity="success"
                            class="ml-2"
                        />
                    </Link>
				</template>
			</Toolbar>

			<h4 class="m-0">Reglas</h4>

			<div class="flex justify-content-end mb-4">
				<IconField>
					<InputIcon><i class="pi pi-search" /></InputIcon>
					<InputText placeholder="Buscar..." v-model="search" @input="onSearch" fluid />
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
							<!-- class="divide-y" -->
							<div>
								<div class="flex items-center gap-3 py-2">
									<i
										class="pi text-lg"
										:class="list.vacation_bonus_year 
											? 'pi-check-circle text-green-500' 
											: 'pi-times-circle text-red-500'"
									/>
									<span class="text-gray-700">Prima vacacional al año</span>
								</div>

								<div class="flex items-center gap-3 py-2">
									<i
										class="pi text-lg"
										:class="list.absences_discount 
											? 'pi-check-circle text-green-500' 
											: 'pi-times-circle text-red-500'"
									/>
									<span class="text-gray-700">Se descuentan las faltas</span>
								</div>

								<div class="flex items-center gap-3 py-2">
									<i
										class="pi text-lg"
										:class="list.incidences_discount 
											? 'pi-check-circle text-green-500' 
											: 'pi-times-circle text-red-500'"
									/>
									<span class="text-gray-700">Se descuentan las incapacidades</span>
								</div>
								
								<div class="flex items-center gap-3 py-2 text-lg">
									<p class="text-gray-800">
										<b>Dias laborales semanal:</b> {{ list.week_work_days }}
									</p>
								</div>
								<div class="flex items-center gap-3 py-2 text-lg">
									<p class="text-gray-800">
										<b>Prima vacacional:</b> {{ list.vacation_bonus }}
									</p>
								</div>
							</div>
						</template>
						<template #footer>
							<div class="flex gap-4 mt-1">
								<Button v-if="(can('policies.delete'))"
									label="Eliminar" severity="danger" class="w-full" icon="pi pi-trash"
									@click="deleteItem(list)" :disabled="processingRows[list.id]"
									:loading="processingRows[list.id]" />
								<Button v-if="(can('policies.edit'))"
									label="Editar" severity="warn" icon="pi pi-pencil" class="w-full"
									@click="editItem(list.id)" :disabled="processingRows[list.id]"
									:loading="processingRows[list.id]" />
								<Button label="Ver" severity="help" icon="pi pi-eye" class="w-full"
									@click="viewItem(list.id)" :disabled="processingRows[list.id]"
									:loading="processingRows[list.id]" />
							</div>
						</template>
					</Card>
				</div>
			</div>

			<Paginator :first="first" :rows="rows" :totalRecords="totalRecords" @page="onPage" />

		</div>
	</AppLayout>
</template>
