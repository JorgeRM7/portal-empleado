<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useForm, router, usePage } from "@inertiajs/vue3";
import { computed, ref, watch, reactive } from "vue";
import { useToastService } from "../../Stores/toastService.js";
import { useConfirm } from 'primevue/useconfirm';
import { useAuthz } from "@/composables/useAuthz";

const { can } = useAuthz();

const { showSuccessCustom, showError, showValidationError, showErrorCustom } = useToastService();

const props = defineProps({
	Companies: Object,
});
// console.log(props.Companies);

// Formulario base
const form = useForm({
	id: null,
	name: "",
	description: "",
    code: "",
});

// Estados generales
const search = ref("");
const dialog = ref(false);
const confirm = useConfirm();
const processingRows = ref({});
const first = ref(0);
const rows = ref(9);
const frontErrors = reactive({});
const page = usePage();

// 🔍 Función de normalización para búsqueda
const normalize = (s) =>
	(s ?? "")
		.toString()
		.normalize("NFD")
		.replace(/\p{Diacritic}/gu, "")
		.toLowerCase();

// 🔎 Filtrado
const filteredLists = computed(() => {
	if (!search.value) return props.Companies;
	const q = normalize(search.value);
	return props.Companies.filter((item) =>
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
		? 'Editar empresa'
		: 'Crear empresa'
);

// ➕ Crear nuevo
const openNew = () => {
	form.reset();
	form.id = null;
	// Limpiar errores de frontend
	Object.keys(frontErrors).forEach(k => delete frontErrors[k]);
	dialog.value = true;
};

// ✏️ Editar
const editItem = (item) => {

	// ---------------- Reset completo ----------------
	form.reset();

	// ---------------- Campos base ----------------
	form.id = item.id;
	form.name = item.name ?? "";
	form.description = item.description ?? "";
    form.code = item.code ?? "";

	// ---------------- Limpiar errores frontend ----------------
	Object.keys(frontErrors).forEach(k => delete frontErrors[k]);

	dialog.value = true;
};

const clearError = (path) => {
    if (frontErrors[path]) {
        delete frontErrors[path];
    }
};

// ---------------------- Validación del formulario ----------------------
const validateForm = () => {
	// Limpiar errores previos
	Object.keys(frontErrors).forEach(k => delete frontErrors[k]);

	// Nombre
	if (!form.name) {
		frontErrors.name = 'El nombre es obligatorio';
	} else if (form.name.length > 255) {
		frontErrors.name = 'El nombre no debe exceder los 255 caracteres';
	}

	// Codigo
	if (!form.code) {
		frontErrors.code = 'El código es obligatorio';
	} else if (form.code.length > 255) {
		frontErrors.code = 'El código no debe exceder los 255 caracteres';
	}

	// Descripción
	if (form.description.length > 255) {
		frontErrors.description = 'La descripción no debe exceder los 255 caracteres';
	}
    
	// Retorna true si no hay errores
	return Object.keys(frontErrors).length === 0;
};

// ---------------------- Guardar (crear o actualizar) ----------------------
const saveItem = () => {
	if (!validateForm()) {
		showValidationError('Hay campos obligatorios sin completar');
		return;
	}

	if (form.id) {
		// Actualizar
		form.put(`/catalogs/companies/${form.id}`, {
			preserveScroll: true,
			onSuccess: () => {
				if (page.props.flash?.success) {
					form.reset();
					dialog.value = false;
					showSuccessCustom(page.props.flash.success);
				}

				if (page.props.flash?.error) {
					showErrorCustom(page.props.flash.error);
				}
			},
			onError: (errors) => {
				Object.values(errors).forEach(err => showErrorCustom(err));
			},
		});
	} else {
		// Crear
		form.post('/catalogs/companies', {
			preserveScroll: true,
			onSuccess: () => {
				if (page.props.flash?.success) {
					form.efficiency_rules = [{ amount: null, operator: null, percent: null }];
					dialog.value = false;
					form.reset();
					showSuccessCustom(page.props.flash.success);
				}
				if (page.props.flash?.error) {
					showErrorCustom(page.props.flash.error);
				}
			},
			onError: (errors) => {
				Object.values(errors).forEach(err => showErrorCustom(err));
			},
		});
	}
};

// const saveItem = () => {
//     console.log('Datos a enviar:', JSON.stringify(form, null, 2));
//     alert('Formulario enviado (revisar consola)');
// };

// ---------------------- Eliminar ----------------------
const deleteItem = (row) => {
	confirm.require({
		message: `¿Deseas eliminar la empresa "${row.name}"?`,
		header: 'Confirmar eliminación',
		icon: 'pi pi-exclamation-triangle',
		acceptLabel: 'Sí, eliminar',
		rejectLabel: 'Cancelar',
		acceptClass: 'p-button-danger',

		accept: () => {
			processingRows.value[row.id] = true;

			router.delete(`/catalogs/companies/${row.id}`, {
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

</script>

<template>
	<AppLayout :title="'Empresas'">
		<ConfirmDialog />
		<div class="card border-none">
			<Toolbar class="mb-6 px-10">
				<template #end>
					<Button v-if="(can('companies.create'))" 
						label="Crear" icon="pi pi-plus-circle" severity="success" class="ml-2" @click="openNew" />
				</template>
			</Toolbar>

			<h4 class="m-0">Empresas</h4>

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

                            <!-- Código -->
                            <div class="col-12 md:col-4">
                                <span class="text-sm text-500 block mb-1">
                                    Código
                                </span>
                                <span class="text-lg font-semibold text-900">
                                    {{ list.code }}
                                </span>
                            </div>

                            <!-- Descripción -->
                            <div class="col-12 md:col-8">
                                <span class="text-sm text-500 block mb-1">
                                    Descripción
                                </span>
                                <span class="text-base text-700 line-height-3">
                                    {{ list.description }}
                                </span>
                            </div>
						</template>
						<template #footer>
							<div class="flex gap-4 mt-1">
								<Button v-if="(can('companies.delete'))" 
									label="Eliminar" severity="danger" class="w-full" icon="pi pi-trash"
									@click="deleteItem(list)" :disabled="processingRows[list.id]"
									:loading="processingRows[list.id]" />
								<Button v-if="(can('companies.edit'))"
									label="Editar" severity="warn" icon="pi pi-pencil" class="w-full"
									@click="editItem(list)" :disabled="processingRows[list.id]"
									:loading="processingRows[list.id]" />
							</div>
						</template>
					</Card>
				</div>
			</div>

			<Paginator :first="first" :rows="rows" :totalRecords="totalRecords" @page="onPage" />

			<!-- Dialogo Crear/Editar -->
			<Dialog v-model:visible="dialog" :style="{ minWidth: '600px' }" :header="dialogTitle" :modal="true" class="p-fluid">
				<div class="card">

					<!-- Nombre -->
					<div class="field mb-4">
						<label for="nombre" class="block font-bold mb-2">
							Nombre <span class="text-red-500">*</span>
						</label>
						<InputText 
							id="nombre" 
							v-model="form.name" 
							placeholder="Ingresa el nombre de la empresa" 
							:class="{ 'p-invalid': frontErrors.name }" 
							class="w-full"
							:maxlength="255"
							@input="clearError('name')" 
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

                    <!-- Código -->
					<div class="field mb-4">
						<label for="codigo" class="block font-bold mb-2">
							Código <span class="text-red-500">*</span>
						</label>
						<InputText 
							id="codigo" 
							v-model="form.code" 
							placeholder="Ingresa el código de la empresa" 
							:class="{ 'p-invalid': frontErrors.code }" 
							class="w-full"
							:maxlength="255"
							@input="clearError('code')" 
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

                    <Divider />
					
					<!-- Descripción -->
					<div class="field mb-4">
						<label for="descripcion" class="block font-bold mb-2">
							Descripción
						</label>
						<Textarea 
							id="descripcion" 
							v-model="form.description" 
							placeholder="Ingresa la descripción de la empresa" 
							:class="{ 'p-invalid': frontErrors.description }" 
							class="w-full"
							rows="3"
							:maxlength="255"
							@input="clearError('description')" 
						/>
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
					<Button label="Cancelar" icon="pi pi-times" severity="secondary" text @click="dialog = false"
						:loading="form.processing" :disabled="form.processing" />
					<Button label="Guardar" icon="pi pi-save" severity="success" @click="saveItem"
						:loading="form.processing" :disabled="form.processing" />
				</template>
			</Dialog>

		</div>
	</AppLayout>
</template>
