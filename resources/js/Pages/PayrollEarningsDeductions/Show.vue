<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, reactive, onMounted, computed } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import { useToastService } from "@/Stores/toastService";

const props = defineProps({
    PerDed: Object,
    Empleados: Array,
    PersepcionesDeducciones: Array
});

const canceling = ref(false);
const page = usePage();

const cancel = () => {
    if (form.processing || canceling.value) return;

    canceling.value = true;

    router.get('/payroll/payroll-earnings-deductions', {}, {
        preserveScroll: true,
        onFinish: () => {
            canceling.value = false;
        }
    });
};

const { showSuccessCustom, showErrorCustom, showValidationError } = useToastService();
const frontErrors = reactive({});

// --- Formulario Principal ---
const form = useForm({
    employee: null,
    salary_payment: null,
    start_date: '',
    end_date: '',
});

const submitForm = () => {

    if (!validateForm()) {
        showValidationError('Hay campos obligatorios sin completar');
        return;
    }

    // console.log('Datos enviados:', form.data());
    form.put(`/payroll/payroll-earnings-deductions/${props.PerDed.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            if (page.props.flash?.success) {
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

};

// ---------------------- Validación del formulario ----------------------
const validateForm = () => {

    // Limpiar errores
    Object.keys(frontErrors).forEach(k => delete frontErrors[k]);

    // ===== GENERAL =====
    if (!form.employee) {
        frontErrors.employee = 'El campo es obligatorio';
    }
    if (!form.salary_payment) {
        frontErrors.salary_payment = 'El campo es obligatorio';
    }
    if (!form.start_date) {
        frontErrors.start_date = 'El campo es obligatorio';
    }
    if (!form.end_date) {
        frontErrors.end_date = 'El campo es obligatorio';
    }
    if (form.start_date && form.end_date) {
        const start = new Date(form.start_date)
        const end = new Date(form.end_date)

        if (end < start) {
            frontErrors.end_date = 'La fecha final no puede ser menor que la inicial'
        }
    }

    return Object.keys(frontErrors).length === 0;
};

const clearError = (path) => {
    if (frontErrors[path]) {
        delete frontErrors[path];
    }
};

// ##### FILTRAR EMPLEADOS EN BASE A LA PLANTA #####
const storedBranch = JSON.parse(localStorage.getItem("selectedBranchOffice"));

const employeeOptions = computed(() => {
    return props.Empleados
        .filter(emp => {
            if (!storedBranch) return true

            return emp.branch_office_id === storedBranch.id
        })
        .map(emp => ({
            ...emp,
            label: `(${emp.id}) ${emp.full_name}`,
        }))
})

const hydrateForm = async (data) => {

    form.employee = employeeOptions.value.find(
        e => Number(e.id) === Number(data.employee_id)
    ) ?? null,
    form.salary_payment = props.PersepcionesDeducciones.find(
        p => Number(p.id) === Number(data.salary_payment_id)
    ) ?? null,
    form.start_date = data.start_date ?? '',
    form.end_date = data.end_date ?? ''
}

onMounted(() => {
    hydrateForm(props.PerDed);
});

</script>

<template>
    <AppLayout title="Detalle Percepcion/Deducción">

        <div class="card">
            <h2 class="text-2xl font-bold mb-5">
                <i class="pi pi-eye mr-2 text-info"></i>
                Detalle Percepcion/Deducción
            </h2>

            <div class="mb-5">
                
                <Panel class="mb-4">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Empleado -->
                        <div>
                            <label for="employee" class="block font-bold mb-2">
                                Empleado <span class="text-red-500">*</span>
                            </label>
                            <Select
                                disabled
                                id="employee"
                                v-model="form.employee"
                                :options="employeeOptions"
                                optionLabel="label"
                                placeholder="Seleccione una opción"
                                class="w-full"
                                filter
                                :filterFields="['id', 'full_name', 'label']"
                                :invalid="!!frontErrors.employee"
                                @change="clearError('employee')"
                            >
                                <template #option="slotProps">
                                    ({{ slotProps.option.id }}) {{ slotProps.option.full_name }}
                                </template>

                                <template #value="slotProps">
                                    <span v-if="slotProps.value">
                                        ({{ slotProps.value.id }}) {{ slotProps.value.full_name }}
                                    </span>
                                    <span v-else class="text-color-secondary">
                                        Seleccione una opción
                                    </span>
                                </template>
                            </Select>
                            <Message
                                v-if="frontErrors.employee"
                                severity="error"
                                size="medium"
                                variant="simple"
                            >
                                {{ frontErrors.employee }}
                            </Message>
                        </div>

                        <!-- Percepción o Deducción -->
                        <div>
                            <label for="salary_payment" class="block font-bold mb-2">
                                Percepción o Deducción <span class="text-red-500">*</span>
                            </label>
                            <Select
                                disabled
                                id="salary_payment"
                                v-model="form.salary_payment"
                                :options="props.PersepcionesDeducciones"
                                optionLabel="name"
                                placeholder="Seleccione una opción"
                                class="w-full"
                                filter
                                :invalid="!!frontErrors.salary_payment"
                                @change="clearError('salary_payment')"
                            >
                            </Select>
                            <Message
                                v-if="frontErrors.salary_payment"
                                severity="error"
                                size="medium"
                                variant="simple"
                            >
                                {{ frontErrors.salary_payment }}
                            </Message>
                        </div>

                        <!-- Fecha de inicio -->
                        <div>
                            <label for="start_date" class="block font-bold mb-2">
                                Fecha de inicio <span class="text-red-500">*</span>
                            </label>
                            <DatePicker
                                readonly
                                id="start_date"
                                v-model="form.start_date"
                                :options="props.Puestos"
                                placeholder="Seleccione una opción"
                                class="w-full"
                                :invalid="!!frontErrors.start_date"
                                @update:modelValue="clearError('start_date')"
                            />
                            <Message
                                v-if="frontErrors.start_date"
                                severity="error"
                                size="medium"
                                variant="simple"
                            >
                                {{ frontErrors.start_date }}
                            </Message>
                        </div>

                        <!-- Fecha final -->
                        <div>
                            <label for="end_date" class="block font-bold mb-2">
                                Fecha final <span class="text-red-500">*</span>
                            </label>
                            <DatePicker
                                readonly
                                id="end_date"
                                v-model="form.end_date"
                                :options="props.Puestos"
                                placeholder="Seleccione una opción"
                                class="w-full"
                                :invalid="!!frontErrors.end_date"
                                @update:modelValue="clearError('end_date')"
                            />
                            <Message
                                v-if="frontErrors.end_date"
                                severity="error"
                                size="medium"
                                variant="simple"
                            >
                                {{ frontErrors.end_date }}
                            </Message>
                        </div>

                    </div>
                    
                </Panel>

            </div>

            <!-- BOTONES ABAJO -->
            <div class="flex justify-end gap-3 pt-2">
                <Button label="Volver" icon="pi pi-arrow-left" severity="secondary" @click="cancel"
                    :loading="canceling" :disabled="form.processing || canceling" />
            </div>

        </div>
    </AppLayout>
</template>