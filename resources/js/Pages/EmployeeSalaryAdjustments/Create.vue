<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, reactive, watch, computed } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import { useToastService } from "@/Stores/toastService";

const props = defineProps({
    Departamentos: Array,
    Puestos: Array,
    Empleados: Array,
    TiposAjuste: Array,
});

const daysOptions = [
    { value: "30", label: "30" },
    { value: "60", label: "60" },
    { value: "90", label: "90" },
];

const canceling = ref(false);
const page = usePage();

const cancel = () => {
    if (form.processing || canceling.value) return;

    canceling.value = true;

    router.get('/employee/employee-salary-adjustments', {}, {
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
    employee_id: null,
    type_salary_adjustment_movement_id: null,
    days_period: null,
    actual_position_id: null,
    actual_department_id: null,
    start_training: '',
    new_position_id: null,
    new_department_id: null,
    base_ajuste: 'ajuste',
    comment: '',
});

const submitForm = () => {

    if (!validateForm()) {
        showValidationError('Hay campos obligatorios sin completar');
        return;
    }

    // console.log('Datos enviados:', form.data());

    form.post('/employee/employee-salary-adjustments', {
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
    if (!form.employee_id) {
        frontErrors.employee_id = 'El campo es obligatorio';
    }
    if (!form.type_salary_adjustment_movement_id) {
        frontErrors.type_salary_adjustment_movement_id = 'El campo es obligatorio';
    }
    if (!form.days_period) {
        frontErrors.days_period = 'El campo es obligatorio';
    }
    if (!form.start_training) {
        frontErrors.start_training = 'El campo es obligatorio';
    }
    if (!form.new_position_id) {
        frontErrors.new_position_id = 'El campo es obligatorio';
    }
    if (!form.new_department_id) {
        frontErrors.new_department_id = 'El campo es obligatorio';
    }
    if (!form.base_ajuste) {
        frontErrors.base_ajuste = 'El campo es obligatorio';
    }
    
    if (String(form.comment).length > 1500) {
        frontErrors.comment = 'El campo no debe exceder los 1500 caracteres';
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

// ##### ACTUALIZAR PUESTO Y DEPARTAMENTO EN BASE AL EMPLEADO #####
watch(() => form.employee_id, (emp) => {
    if (!emp) return

    form.actual_position_id = Number(emp.position_id) || null
    form.actual_department_id = Number(emp.department_id) || null
})

</script>

<template>
    <AppLayout title="Crear ajuste o promoción salarial">

        <div class="card">
            <h2 class="text-2xl font-bold mb-5">
                <i class="pi pi-plus-circle mr-2 text-success"></i>
                Crear ajuste o promoción salarial
            </h2>

            <div class="mb-5">
                
                <!-- ========== GENERAL ========== -->
                <Panel class="mb-4">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Empleado -->
                        <div>
                            <label for="employee_id" class="block font-bold mb-2">
                                Empleado <span class="text-red-500">*</span>
                            </label>
                            <Select
                                id="employee_id"
                                v-model="form.employee_id"
                                :options="employeeOptions"
                                optionLabel="label"
                                placeholder="Seleccione una opción"
                                class="w-full"
                                filter
                                :filterFields="['id', 'full_name', 'label']"
                                :invalid="!!frontErrors.employee_id"
                                @change="clearError('employee_id')"
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
                                v-if="frontErrors.employee_id"
                                severity="error"
                                size="medium"
                                variant="simple"
                            >
                                {{ frontErrors.employee_id }}
                            </Message>
                        </div>

                        <!-- Tipo de ajuste -->
                        <div>
                            <label for="type_salary_adjustment_movement_id" class="block font-bold mb-2">
                                Tipo de ajuste <span class="text-red-500">*</span>
                            </label>
                            <Select
                                id="type_salary_adjustment_movement_id"
                                v-model="form.type_salary_adjustment_movement_id"
                                :options="props.TiposAjuste"
                                optionValue="id"
                                optionLabel="name"
                                placeholder="Seleccione una opción"
                                class="w-full"
                                filter
                                :invalid="!!frontErrors.type_salary_adjustment_movement_id"
                                @change="clearError('type_salary_adjustment_movement_id')"
                            >
                            </Select>
                            <Message
                                v-if="frontErrors.type_salary_adjustment_movement_id"
                                severity="error"
                                size="medium"
                                variant="simple"
                            >
                                {{ frontErrors.type_salary_adjustment_movement_id }}
                            </Message>
                        </div>

                        <!-- Días de periodo -->
                        <div>
                            <label for="days_period" class="block font-bold mb-2">
                                Días de periodo <span class="text-red-500">*</span>
                            </label>
                            <Select
                                id="days_period"
                                v-model="form.days_period"
                                :options="daysOptions"
                                optionValue="value"
                                optionLabel="label"
                                placeholder="Seleccione una opción"
                                class="w-full"
                                filter
                                :invalid="!!frontErrors.days_period"
                                @change="clearError('days_period')"
                            >
                            </Select>
                            <Message
                                v-if="frontErrors.days_period"
                                severity="error"
                                size="medium"
                                variant="simple"
                            >
                                {{ frontErrors.days_period }}
                            </Message>
                        </div>

                        <!-- Puesto actual -->
                        <div>
                            <label for="actual_position_id" class="block font-bold mb-2">
                                Puesto actual
                            </label>
                            <Select
                                id="actual_position_id"
                                disabled
                                v-model="form.actual_position_id"
                                :options="props.Puestos"
                                optionValue="id"
                                optionLabel="name"
                                placeholder="Seleccione una opción"
                                class="w-full"
                                filter
                                :invalid="!!frontErrors.actual_position_id"
                                @change="clearError('actual_position_id')"
                            >
                            </Select>
                            <Message
                                v-if="frontErrors.actual_position_id"
                                severity="error"
                                size="medium"
                                variant="simple"
                            >
                                {{ frontErrors.actual_position_id }}
                            </Message>
                        </div>

                        <!-- Departamento actual -->
                        <div>
                            <label for="actual_department_id" class="block font-bold mb-2">
                                Departamento actual
                            </label>
                            <Select
                                id="actual_department_id"
                                disabled
                                v-model="form.actual_department_id"
                                :options="props.Departamentos"
                                optionValue="id"
                                optionLabel="name"
                                placeholder="Seleccione una opción"
                                class="w-full"
                                filter
                                :invalid="!!frontErrors.actual_department_id"
                                @change="clearError('actual_department_id')"
                            >
                            </Select>
                            <Message
                                v-if="frontErrors.actual_department_id"
                                severity="error"
                                size="medium"
                                variant="simple"
                            >
                                {{ frontErrors.actual_department_id }}
                            </Message>
                        </div>

                        <!-- Fecha de inicio de capacitación -->
                        <div>
                            <label for="start_training" class="block font-bold mb-2">
                                Fecha de inicio de capacitación <span class="text-red-500">*</span>
                            </label>
                            <DatePicker
                                id="start_training"
                                v-model="form.start_training"
                                :options="props.Puestos"
                                optionLabel="name"
                                placeholder="Seleccione una opción"
                                class="w-full"
                                :invalid="!!frontErrors.start_training"
                                @update:modelValue="clearError('start_training')"
                            />
                            <Message
                                v-if="frontErrors.start_training"
                                severity="error"
                                size="medium"
                                variant="simple"
                            >
                                {{ frontErrors.start_training }}
                            </Message>
                        </div>

                        <!-- Nuevo puesto -->
                        <div>
                            <label for="new_position_id" class="block font-bold mb-2">
                                Nuevo puesto <span class="text-red-500">*</span>
                            </label>
                            <Select
                                id="new_position_id"
                                v-model="form.new_position_id"
                                :options="props.Puestos"
                                optionValue="id"
                                optionLabel="name"
                                placeholder="Seleccione una opción"
                                class="w-full"
                                filter
                                :invalid="!!frontErrors.new_position_id"
                                @change="clearError('new_position_id')"
                            >
                            </Select>
                            <Message
                                v-if="frontErrors.new_position_id"
                                severity="error"
                                size="medium"
                                variant="simple"
                            >
                                {{ frontErrors.new_position_id }}
                            </Message>
                        </div>

                        <!-- Nuevo departamento -->
                        <div>
                            <label for="new_department_id" class="block font-bold mb-2">
                                Nuevo departamento <span class="text-red-500">*</span>
                            </label>
                            <Select
                                id="new_department_id"
                                v-model="form.new_department_id"
                                :options="props.Departamentos"
                                optionValue="id"
                                optionLabel="name"
                                placeholder="Seleccione una opción"
                                class="w-full"
                                filter
                                :invalid="!!frontErrors.new_department_id"
                                @change="clearError('new_department_id')"
                            >
                            </Select>
                            <Message
                                v-if="frontErrors.new_department_id"
                                severity="error"
                                size="medium"
                                variant="simple"
                            >
                                {{ frontErrors.new_department_id }}
                            </Message>
                        </div>

                        <!-- ¿El ajuste se realizará con base en ajuste o periodo inicial? -->
                        <div class="md:col-span-3">
                            <label for="new_department_id" class="block font-bold mb-2">
                                ¿El ajuste se realizará con base en ajuste o periodo inicial?
                            </label>

                            <div class="flex flex-wrap gap-4">
                                <div class="flex items-center gap-2">
                                    <RadioButton v-model="form.base_ajuste" name="base_ajuste" value="ajuste" />
                                    <label for="ingredient1">Ajustado</label>
                                </div>
                                <div class="flex items-center gap-2">
                                    <RadioButton v-model="form.base_ajuste" name="base_ajuste" value="prueba" />
                                    <label for="ingredient2">Periodo de prueba</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Observaciones -->
                        <div class="md:col-span-3">
                            <label for="comment" class="block font-bold mb-2">
                                Observaciones
                            </label>
                            <Textarea 
                                id="comment" 
                                v-model="form.comment" 
                                placeholder="Ingresa una descripción." 
                                :class="{ 'p-invalid': frontErrors.comment }" 
                                class="w-full"
                                rows="4"
                                :maxlength="1500"
                                @input="clearError('comment')"
                            />
                            <small class="text-gray-500">{{ form.comment?.length || 0 }}/1500</small>
                            <Message
                                v-if="frontErrors.comment"
                                severity="error"
                                size="medium"
                                variant="simple"
                            >
                                {{ frontErrors.comment }}
                            </Message>
                        </div>

                    </div>
                    
                </Panel>

            </div>

            <!-- BOTONES ABAJO -->
            <div class="flex justify-end gap-3 pt-2">
                <Button label="Cancelar" icon="pi pi-times" severity="secondary" @click="cancel" :loading="canceling" :disabled="form.processing || canceling" />
                <div>
                    <Button label="Guardar" icon="pi pi-save" severity="success" @click="submitForm" :loading="form.processing" :disabled="form.processing || canceling" />
                </div>
            </div>

        </div>
    </AppLayout>
</template>