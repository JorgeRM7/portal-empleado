<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { onMounted, ref, watch, reactive } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import { useToastService } from "@/Stores/toastService";

const props = defineProps({
    Benefits: Object,
    CategoryIncidences: Object,
});

// console.log(props.Benefits);

const canceling = ref(false);
const page = usePage();
const { showSuccessCustom, showErrorCustom, showValidationError } = useToastService();
const frontErrors = reactive({});

const cancel = () => {
    if (form.processing || canceling.value) return;

    canceling.value = true;

    router.get('/catalogs/benefits', {}, {
        preserveScroll: true,
        onFinish: () => {
            canceling.value = false;
        }
    });
};

// Formulario base
const form = useForm({
	name: "",
	description: "",
	type: null,
	each: null,
	cut_day: null,
	conditioned: false,
	conditioned_seniority: false,
	conditioned_efficiency: false,
	efficiency_rules: [{ amount: null, operator: null, percent: null }],
	link: false,
	incidence_category: null,
	quantity: null,
	active: true,
});

// ---------------------- Opciones para selects ----------------------
const tiposOptions = ref([
    { label: 'Días', value: 'days' },
    { label: 'Meses', value: 'months' }
]);

const cadaOptions = ref([
    ...Array.from({ length: 31 }, (_, i) => ({
        label: (i).toString(),
        value: i
    }))
]);

const corteOptions = ref([
    ...Array.from({ length: 32 }, (_, i) => ({
        label: (i).toString(),
        value: i
    }))
]);

const operadoresOptions = ref([
    { label: 'Mayor que (>)', value: '>' },
    { label: 'Mayor o igual que (≥)', value: '>=' },
    { label: 'Igual a (=)', value: '=' },
    { label: 'Menor o igual que (≤)', value: '<=' },
    { label: 'Menor que (<)', value: '<' },
]);

// ---------------------- Manejo de reglas de eficiencia ----------------------
const addRegla = () => {
    form.efficiency_rules.push({ amount: null, operator: null, percent: null });
};

const removeRegla = (index) => {
    if (form.efficiency_rules.length > 1) {
        form.efficiency_rules.splice(index, 1);
        
        // Limpiar errores de esa regla si existen
        if (frontErrors.efficiency_rules && frontErrors.efficiency_rules[index]) {
            delete frontErrors.efficiency_rules[index];
        }
    }
};

const clearReglaError = (index, field) => {
    if (frontErrors.efficiency_rules && frontErrors.efficiency_rules[index]) {
        delete frontErrors.efficiency_rules[index][field];
        
        // Si no hay más errores en esta regla, eliminar el objeto
        if (Object.keys(frontErrors.efficiency_rules[index]).length === 0) {
            delete frontErrors.efficiency_rules[index];
        }
        
        // Si no hay más reglas con errores, eliminar el array
        if (frontErrors.efficiency_rules && Object.keys(frontErrors.efficiency_rules).length === 0) {
            delete frontErrors.efficiency_rules;
        }
    }
};

const clearError = (path) => {
    if (frontErrors[path]) {
        delete frontErrors[path];
    }
};

// ---------------------- Watch para limpiar campos cuando se desactivan ----------------------
watch(() => form.conditioned_efficiency, (newVal) => {
	if (!newVal) {
		// Si se desactiva, limpiar reglas de eficiencia
		form.efficiency_rules = [{ amount: null, operator: null, percent: null }];
		if (frontErrors.efficiency_rules) {
			delete frontErrors.efficiency_rules;
		}
	}
});

const hydrateForm = (data) => {

	// ---------------- Campos base ----------------
	form.name = data.name ?? "";
	form.description = data.description ?? "";
	form.type = data.type ?? null;
	form.each = data.each ?? null;
	form.cut_day = data.day_cutoff ?? null;

	// ---------------- Switches (normalizados) ----------------
	form.conditioned = Boolean(data.conditioned);
	form.conditioned_seniority = Boolean(data.conditioned_seniority);
	form.conditioned_efficiency = Boolean(data.conditioned_efficiency);

	// ---------------- Reglas de eficiencia ----------------
	if (form.conditioned_efficiency) {
		if (data.efficiency_rules) {
			try {
				form.efficiency_rules = Array.isArray(data.efficiency_rules)
					? data.efficiency_rules
					: JSON.parse(data.efficiency_rules);
			} catch (e) {
				form.efficiency_rules = [{ amount: null, operator: null, percent: null }];
			}
		} else {
			form.efficiency_rules = [{ amount: null, operator: null, percent: null }];
		}
	} else {
		form.efficiency_rules = [{ amount: null, operator: null, percent: null }];
	}
	// ---------------- Relación categoría incidencia ----------------
	if (data.has_incidence_relation) {
		form.link = true;
		form.incidence_category = data.category_incidence_id;
		form.quantity = data.quantity;
		form.active = Boolean(data.active);
	} else {
		form.link = false;
		form.incidence_category = null;
		form.quantity = null;
		form.active = true;
	}
};

onMounted(() => {
    hydrateForm(props.Benefits);
});

</script>

<template>
    <AppLayout title="Detalle de la Prestación">
        <div>
            
            <div class="card">
                    
                <h2 class="text-2xl font-bold mb-5">
                    <i class="pi pi-eye mr-2 text-info"></i>
                    Detalle de la Prestación
                </h2>

                <!-- Nombre -->
                <div class="field mb-4">
                    <label for="nombre" class="block font-bold mb-2">
                        Nombre <span class="text-red-500">*</span>
                    </label>
                    <InputText 
                        id="nombre" 
                        v-model="form.name" 
                        placeholder="Ej. Compensación por productividad" 
                        :class="{ 'p-invalid': frontErrors.name }" 
                        class="w-full"
                        @input="clearError('name')"
                        readonly
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
                
                <!-- Descripción -->
                <div class="field mb-4">
                    <label for="descripcion" class="block font-bold mb-2">
                        Descripción <span class="text-red-500">*</span>
                    </label>
                    <Textarea 
                        id="descripcion" 
                        v-model="form.description" 
                        placeholder="Descripción de la prestación" 
                        :class="{ 'p-invalid': frontErrors.description }" 
                        class="w-full"
                        rows="3"
                        :maxlength="255"
                        @input="clearError('description')" 
                        readonly
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

                <Divider />

                <!-- Tipo y rangos -->
                <div class="mb-5">
                    <h3 class="text-lg font-bold mb-3">Tipo y rangos</h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Tipo -->
                        <div class="field mb-4">
                            <label for="tipo" class="block font-bold mb-2">
                                Tipo <span class="text-red-500">*</span>
                            </label>
                            <Select 
                                id="tipo" 
                                v-model="form.type" 
                                :options="tiposOptions" 
                                optionLabel="label" 
                                optionValue="value" 
                                placeholder="Seleccione una opción" 
                                class="w-full" 
                                :class="{ 'p-invalid': frontErrors.type }"
                                @change="clearError('type')"
                                disabled
                            />
                            <Message
                                v-if="frontErrors.type"
                                severity="error"
                                size="medium"
                                variant="simple"
                            >
                                {{ frontErrors.type }}
                            </Message>
                        </div>
                        
                        <!-- Cada -->
                        <div class="field mb-4">
                            <label for="cada" class="block font-bold mb-2">
                                Cada <span class="text-red-500">*</span>
                            </label>
                            <Select 
                                id="cada" 
                                v-model="form.each" 
                                :options="cadaOptions" 
                                optionLabel="label" 
                                optionValue="value" 
                                placeholder="Seleccione una opción" 
                                class="w-full" 
                                :class="{ 'p-invalid': frontErrors.each }"
                                @change="clearError('each')"
                                disabled
                            />
                            <Message
                                v-if="frontErrors.each"
                                severity="error"
                                size="medium"
                                variant="simple"
                            >
                                {{ frontErrors.each }}
                            </Message>
                        </div>

                        <!-- Día de corte -->
                        <div class="field mb-4">
                            <label for="cut_day" class="block font-bold mb-2">
                                Día de corte <span class="text-red-500">*</span>
                            </label>
                            <Select 
                                id="cut_day" 
                                v-model="form.cut_day" 
                                :options="corteOptions" 
                                optionLabel="label" 
                                optionValue="value" 
                                placeholder="Seleccione una opción" 
                                class="w-full" 
                                :class="{ 'p-invalid': frontErrors.cut_day }"
                                @change="clearError('cut_day')"
                                disabled
                            />
                            <Message
                                v-if="frontErrors.cut_day"
                                severity="error"
                                size="medium"
                                variant="simple"
                            >
                                {{ frontErrors.cut_day }}
                            </Message>
                        </div>
                    </div>
                    
                </div>

                <Divider />

                <!-- Vincular categoría de incidencia -->
                <div class="mb-5">
                    <h3 class="text-lg font-bold mb-3">Vincular categoría de incidencia</h3>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Vincular -->
                        <div class="md:col-span-1 mt-auto">
                            <div class="flex align-items-center mb-4">
                                <div class="card w-full shadow-none p-3">
                                    <div class="flex align-items-center justify-content-between">
                                        <div class="flex align-items-center">
                                            <label class="font-bold">Vincular</label>
                                        </div>
                                        <ToggleSwitch v-model="form.link" readonly/>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="md:col-span-3">
                                
                            <div v-if="form.link">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <!-- Categoría de incidencia -->
                                    <div class="field mb-4">
                                        <label for="categoria_incidencia" class="block font-bold mb-2">
                                            Categoría de incidencia <span class="text-red-500">*</span>
                                        </label>
                                        <Select 
                                            id="categoria_incidencia" 
                                            v-model="form.incidence_category" 
                                            :options="props.CategoryIncidences" 
                                            optionLabel="name" 
                                            optionValue="id" 
                                            placeholder="Seleccione una opción" 
                                            class="w-full" 
                                            :class="{ 'p-invalid': frontErrors.incidence_category }"
                                            @change="clearError('incidence_category')"
                                            disabled
                                        />
                                        <Message
                                            v-if="frontErrors.incidence_category"
                                            severity="error"
                                            size="medium"
                                            variant="simple"
                                        >
                                            {{ frontErrors.incidence_category }}
                                        </Message>
                                    </div>

                                    <!-- Cantidad -->
                                    <div class="field mb-4">
                                        <label for="cantidad" class="block font-bold mb-2">
                                            Cantidad <span class="text-red-500">*</span>
                                        </label>
                                        <InputNumber 
                                            id="cantidad" 
                                            v-model="form.quantity" 
                                            placeholder="Ej. 2" 
                                            :inputClass="{ 'p-invalid': frontErrors.quantity }" 
                                            class="w-full"
                                            @blur="clearError('quantity')"
                                            :min="0"
                                            readonly
                                        />
                                        <Message
                                            v-if="frontErrors.quantity"
                                            severity="error"
                                            size="medium"
                                            variant="simple"
                                        >
                                            {{ frontErrors.quantity }}
                                        </Message>
                                    </div>

                                    <!-- Activo -->
                                    <div class="flex align-items-center mb-4 mt-auto">
                                        <div class="card w-full shadow-none p-3">
                                            <div class="flex align-items-center justify-content-between">
                                                <div class="flex align-items-center">
                                                    <label class="font-bold">Activo</label>
                                                </div>
                                                <ToggleSwitch v-model="form.active" readonly/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    
                </div>

                <Divider />

                <!-- Sección: Condicionadas -->
                <div class="mb-5">
                    <h3 class="text-lg font-bold mb-3">Condicionados</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                        <!-- Condicionado Falta -->
                        <div class="flex align-items-center mb-4">
                            <div class="card w-full shadow-none p-3">
                                <div class="flex align-items-center justify-content-between">
                                    <div class="flex align-items-center">
                                        <label class="font-bold">Condicionado Falta</label>
                                    </div>
                                    <ToggleSwitch v-model="form.conditioned" readonly/>
                                </div>
                            </div>
                        </div>
                            
                        <!-- Condicionado Antigüedad -->
                        <div class="flex align-items-center mb-4">
                            <div class="card w-full shadow-none p-3">
                                <div class="flex align-items-center justify-content-between">
                                    <div class="flex align-items-center">
                                        <label class="font-bold">Condicionado Antigüedad</label>
                                    </div>
                                    <ToggleSwitch v-model="form.conditioned_seniority" readonly/>
                                </div>
                            </div>
                        </div>
                            
                        <!-- Condicionado Eficiencia -->
                        <div class="flex align-items-center mb-4">
                            <div class="card w-full shadow-none p-3">
                                <div class="flex align-items-center justify-content-between">
                                    <div class="flex align-items-center">
                                        <label class="font-bold">Condicionado Eficiencia</label>
                                    </div>
                                    <ToggleSwitch v-model="form.conditioned_efficiency" readonly/>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div v-if="form.conditioned_efficiency" class="mb-4">
                        <h4 class="font-bold mb-3">Reglas de eficiencia*</h4>
                        
                        <div v-for="(regla, index) in form.efficiency_rules" :key="index" class="p-shadow-2 card p-3">
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                                <!-- Monto -->
                                <div class="field">
                                    <label :for="`amount-${index}`" class="block font-bold mb-2">
                                        Monto <span class="text-red-500">*</span>
                                    </label>
                                    <InputNumber
                                        :id="`amount-${index}`"
                                        v-model="regla.amount"
                                        placeholder="Ej. 100"
                                        class="w-full"
                                        :inputClass="{ 'p-invalid': frontErrors.efficiency_rules?.[index]?.amount }"
                                        :min="0"
                                        mode="decimal"
                                        :minFractionDigits="1"
                                        locale="en-US"
                                        @blur="clearReglaError(index, 'amount')"
                                        readonly
                                    />
                                    <Message
                                        v-if="frontErrors.efficiency_rules?.[index]?.amount"
                                        severity="error"
                                        size="medium"
                                        variant="simple"
                                    >
                                        {{ frontErrors.efficiency_rules[index].amount }}
                                    </Message>
                                </div>

                                <!-- Operador -->
                                <div class="field">
                                    <label :for="`operator-${index}`" class="block font-bold mb-2">
                                        Operador <span class="text-red-500">*</span>
                                    </label>
                                    <Select
                                        :id="`operator-${index}`"
                                        v-model="regla.operator"
                                        :options="operadoresOptions"
                                        optionLabel="label"
                                        optionValue="value"
                                        placeholder="Seleccione una opción"
                                        class="w-full"
                                        :class="{ 'p-invalid': frontErrors.efficiency_rules?.[index]?.operator }"
                                        @change="clearReglaError(index, 'operator')"
                                        disabled
                                    />
                                    <Message
                                        v-if="frontErrors.efficiency_rules?.[index]?.operator"
                                        severity="error"
                                        size="medium"
                                        variant="simple"
                                    >
                                        {{ frontErrors.efficiency_rules[index].operator }}
                                    </Message>
                                </div>

                                <!-- Valor -->
                                <div class="field">
                                    <label :for="`value-${index}`" class="block font-bold mb-2">
                                        Valor <span class="text-red-500">*</span>
                                    </label>
                                    <InputNumber
                                        :id="`value-${index}`"
                                        v-model="regla.percent"
                                        placeholder="Ej. valor de 0 a 100"
                                        class="w-full"
                                        :inputClass="{ 'p-invalid': frontErrors.efficiency_rules?.[index]?.percent }"
                                        :min="0"
                                        :max="100"
                                        :step="0.01"
                                        :minFractionDigits="1"
                                        :maxFractionDigits="4"
                                        mode="decimal"
                                        locale="en-US"
                                        @blur="clearReglaError(index, 'percent')"
                                        readonly
                                    />
                                    <Message
                                        v-if="frontErrors.efficiency_rules?.[index]?.percent"
                                        severity="error"
                                        size="medium"
                                        variant="simple"
                                    >
                                        {{ frontErrors.efficiency_rules[index].percent }}
                                    </Message>
                                </div>

                            </div>


                            <div class="flex align-items-center mt-3">
                                <!-- <Button 
                                    v-if="form.efficiency_rules.length > 1"
                                    icon="pi pi-trash" 
                                    severity="danger" 
                                    text 
                                    class="p-button-rounded p-button-sm ml-auto"
                                    @click="removeRegla(index)"
                                /> -->
                            </div>
                        </div>

                        <!-- <Button 
                            label="Agregar regla" 
                            icon="pi pi-plus" 
                            class="p-button-outlined mt-2" 
                            @click="addRegla"
                        /> -->
                    </div>
                    
                    <!-- Ejemplo explicativo (solo si está activado) -->
                    <div v-if="form.conditioned_efficiency" class="p-3 surface-ground border-round mt-3">
                        <p class="text-sm m-0">
                            <strong>Ejemplo:</strong> 100 > 90, quiere decir que se le dará el 100% de la compensación siempre que sea > 90.
                        </p>
                    </div>
                </div>
            

                <!-- BOTONES ABAJO -->
                <div class="flex justify-end gap-3 pt-2">
                    <Button label="Volver" icon="pi pi-arrow-left" severity="secondary" @click="cancel" :loading="canceling" :disabled="form.processing || canceling" />
                </div>
            
            </div>

        </div>

    </AppLayout>
</template>