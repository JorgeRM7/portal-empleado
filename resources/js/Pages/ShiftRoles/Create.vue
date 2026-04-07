<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToastService } from "@/Stores/toastService";
import { useForm } from '@inertiajs/vue3';

const canceling = ref(false);

const cancel = () => {
    if (form.processing || canceling.value) return;

    canceling.value = true;

    router.get('/catalogs/shift-roles', {}, {
        preserveScroll: true,
        onFinish: () => {
            canceling.value = false;
        }
    });
};

// Horarios
const props = defineProps({
    Schedules: Array,
});

// console.log(props);

const everyOptions = ref([0,1,2,3,4,5,6,7,8,9,10,11,12]);
const workTypeOptions = ref([
    { label: 'Días', value: 'days' },
    { label: 'Semanas', value: 'weeks' },
    { label: 'Meses', value: 'months' },
]);

// --- Estado del Formulario ---
const initialRule = () => ({
    isRestDay: false,
    schedule: null,
    every: null,
    type: null,
    sundayBonus: false,
});

const form = useForm({
    shiftName: '',
    holidayRule: false,
    isDynamicShift: false,
    
    weeklySchedule: {
        monday: { label: 'Lunes', isRestDay: false, schedule: null, hasSundayBonus: false, isSunday: false },
        tuesday: { label: 'Martes', isRestDay: false, schedule: null, hasSundayBonus: false, isSunday: false },
        wednesday: { label: 'Miércoles', isRestDay: false, schedule: null, hasSundayBonus: false, isSunday: false },
        thursday: { label: 'Jueves', isRestDay: false, schedule: null, hasSundayBonus: false, isSunday: false },
        friday: { label: 'Viernes', isRestDay: false, schedule: null, hasSundayBonus: false, isSunday: false },
        saturday: { label: 'Sábado', isRestDay: false, schedule: null, hasSundayBonus: false, isSunday: false },
        sunday: { label: 'Domingo', isRestDay: true, schedule: null, hasSundayBonus: false, isSunday: true },
    },
    
    dynamicRules: [initialRule()],
});

// --- Métodos de Ayuda ---
const getIcon = (dayKey) => {
    switch (dayKey) {
        case 'monday': return 'pi pi-calendar';
        case 'tuesday': return 'pi pi-calendar';
        case 'wednesday': return 'pi pi-calendar';
        case 'thursday': return 'pi pi-calendar';
        case 'friday': return 'pi pi-calendar';
        case 'saturday': return 'pi pi-calendar';
        case 'sunday': return 'pi pi-calendar';
        default: return 'pi pi-circle';
    }
};

const addRule = () => {
    form.dynamicRules.push(initialRule());
};

const removeRule = (index) => {
    form.dynamicRules.splice(index, 1);
};

const { showSuccess, showError } = useToastService();

const frontErrors = reactive({});

const submitForm = () => {

    if (!validateForm()) {
        showError('Hay campos obligatorios sin completar');
        return;
    }

    form.post('/catalogs/shift-roles', {
        preserveScroll: true,
        onSuccess: () => {
            showSuccess('Rol de turno creado correctamente');
        },
        onError: () => {
            Object.values(form.errors).forEach(err => showError(err));
        },
    });
};

const validateForm = () => {
    Object.keys(frontErrors).forEach(k => delete frontErrors[k]);

    // Nombre
    if (!form.shiftName) {
        frontErrors.shiftName = 'El nombre del turno es obligatorio';
    }

    // Turno fijo
    if (!form.isDynamicShift) {
        Object.entries(form.weeklySchedule).forEach(([key, day]) => {
            if (!day.isRestDay && !day.schedule) {
                frontErrors[`weeklySchedule.${key}.schedule`] = 'Horario requerido';
            }
        });
    }

    // Turno dinámico
    if (form.isDynamicShift) {
        form.dynamicRules.forEach((rule, index) => {
            if (!rule.isRestDay && !rule.schedule) {
                frontErrors[`dynamicRules.${index}.schedule`] = 'Horario requerido';
            }
            if (rule.every === null) {
                frontErrors[`dynamicRules.${index}.every`] = 'Campo requerido';
            }
            if (!rule.type) {
                frontErrors[`dynamicRules.${index}.type`] = 'Campo requerido';
            }
        });
    }

    return Object.keys(frontErrors).length === 0;
};

const clearError = (path) => {
    if (frontErrors[path]) {
        delete frontErrors[path];
    }
};

// // --- Métodos de Acción del Formulario ---
// const submitForm = () => {
//     console.log('Datos a enviar:', JSON.stringify(form, null, 2));
//     alert('Formulario enviado (revisar consola)');
// };
</script>

<template>
    <AppLayout title="Crear Rol">
        <div class="card space-y-6">

            <h2 class="text-2xl font-bold mb-5">
                <i class="pi pi-plus-circle mr-2 text-success"></i>
                Crear Rol De Turno
            </h2>

            <div class="p-4">

                <div class="p-formgrid grid p-nogutter p-fluid">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                        <!-- Nombre -->
                        <div class="field mb-4">
                            <label for="shiftName" class="block font-bold mb-2">
                                Nombre del turno <span class="text-red-500">*</span>
                            </label>
                            <InputText 
                                id="shiftName" 
                                v-model="form.shiftName" 
                                required 
                                class="w-full"
                                :class="{ 'p-invalid': frontErrors.shiftName }" 
                                @input="clearError('shiftName')" 
                            />
                            <Message
                                v-if="frontErrors.shiftName"
                                severity="error"
                                size="medium"
                                variant="simple"
                            >
                                {{ frontErrors.shiftName }}
                            </Message>
                        </div>
                        <div class="field mb-4 mt-auto">
                            <div class="card w-full shadow-none p-3">
                                <div class="flex align-items-center justify-content-between">
                                    <div class="flex align-items-center">
                                        <label for="dynamicShift" class="font-bold">Turno dinámico</label>
                                    </div>
                                    <ToggleSwitch id="dynamicShift" v-model="form.isDynamicShift" />
                                </div>
                            </div>
                        </div>
                        <div class="field mb-4 mt-auto">
                            <div class="card w-full shadow-none p-3">
                                <div class="flex align-items-center justify-content-between">
                                    <div class="flex align-items-center">
                                        <label for="holidayRule" class="font-bold">Regla de día festivo</label>
                                    </div>
                                    <ToggleSwitch id="holidayRule" v-model="form.holidayRule" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <Divider />
                    </div>

                    <template v-if="!form.isDynamicShift">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div v-for="(day, key) in form.weeklySchedule" :key="key" class="col-12 lg:col-4 md:col-6">
                                <Card class="p-0 card">
                                    <template #title>
                                        <div class="flex align-items-center">
                                            <i :class="getIcon(key)" class="mr-2 text-xl text-primary"></i>
                                            <span class="text-lg font-semibold">{{ day.label }}</span>
                                        </div>
                                    </template>
                                    <template #content>
                                        <div class="field my-4 mt-auto">
                                            <div class="card w-full shadow-none p-3">
                                                <div class="flex align-items-center justify-content-between">
                                                    <div class="flex align-items-center">
                                                        <label :for="`rest-${key}`" class="font-bold">Día de Descanso</label>
                                                    </div>
                                                    <ToggleSwitch :id="`rest-${key}`" v-model="day.isRestDay"/>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="field" v-if="!day.isRestDay">
                                            <label :for="`schedule-${key}`" class="font-medium me-3">Horario <span class="text-red-500">*</span></label>
                                            <Select :id="`schedule-${key}`" v-model="day.schedule" :options="props.Schedules" optionLabel="name" optionValue="id" placeholder="Selecciona un horario" class="w-full" filter
                                                :class="{ 'p-invalid': frontErrors[`weeklySchedule.${key}.schedule`] }" 
                                                @change="clearError(`weeklySchedule.${key}.schedule`)" />
                                            <Message
                                                v-if="frontErrors[`weeklySchedule.${key}.schedule`]"
                                                severity="error"
                                                size="medium"
                                                variant="simple"
                                            >
                                                {{ frontErrors[`weeklySchedule.${key}.schedule`] }}
                                            </Message>
                                        </div>

                                        <div class="field mb-4 my-4" v-if="!day.isRestDay && day.isSunday">
                                            <div class="card w-full shadow-none p-3">
                                                <div class="flex align-items-center justify-content-between">
                                                    <div class="flex align-items-center">
                                                        <label :for="`prima-${key}`" class="font-bold">Prima dominical</label>
                                                    </div>
                                                    <ToggleSwitch :id="`prima-${key}`" v-model="day.hasSundayBonus" />
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </Card>
                            </div>
                        </div>
                    </template>

                    <template v-else>
                        <div class="col-12 mb-3">
                            <h3 class="text-xl font-bold">Reglas de Turno Rotativo</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div v-for="(rule, index) in form.dynamicRules" :key="index" class="col-12">
                                <Card class="p-2 card">
                                    <template #content>
                                        <div class="grid p-fluid formgrid">
                                            <div class="col-12 md:col-4 lg:col-3 field mb-3">
                                                <div class="card w-full shadow-none p-3 mb-3">
                                                    <div class="flex align-items-center justify-content-between">
                                                        <div class="flex align-items-center">
                                                            <label :for="`rule-rest-${index}`" class="font-bold">Descanso</label>
                                                        </div>
                                                        <ToggleSwitch :id="`rule-rest-${index}`" v-model="rule.isRestDay"/>
                                                    </div>
                                                </div>
                                                <div class="field" v-if="!rule.isRestDay">
                                                    <label for="Horario" class="font-medium">Horario <span class="text-red-500">*</span></label>
                                                    <Select v-model="rule.schedule" :options="props.Schedules" optionLabel="name" optionValue="id" placeholder="Selecciona horario" class="w-full" filter 
                                                        :class="{ 'p-invalid': frontErrors[`dynamicRules.${index}.schedule`] }" 
                                                        @change="clearError(`dynamicRules.${index}.schedule`)" />
                                                    <Message
                                                        v-if="frontErrors[`dynamicRules.${index}.schedule`]"
                                                        severity="error"
                                                        size="medium"
                                                        variant="simple"
                                                    >
                                                        {{ frontErrors[`dynamicRules.${index}.schedule`] }}
                                                    </Message>
                                                </div>
                                            </div>

                                            <div class="col-12 md:col-6 lg:col-7 field grid mb-3">
                                                <div class="col-12 sm:col-6 field mb-3">
                                                    <label for="Cada" class="font-medium">Cada <span class="text-red-500">*</span></label>
                                                    <Select v-model="rule.every" :options="everyOptions" placeholder="Días/Semanas" class="w-full"
                                                        :class="{ 'p-invalid': frontErrors[`dynamicRules.${index}.every`] }" 
                                                        @change="clearError(`dynamicRules.${index}.every`)" />
                                                    <Message
                                                        v-if="frontErrors[`dynamicRules.${index}.every`]"
                                                        severity="error"
                                                        size="medium"
                                                        variant="simple"
                                                    >
                                                        {{ frontErrors[`dynamicRules.${index}.every`] }}
                                                    </Message>
                                                </div>
                                                <div class="col-12 sm:col-6 field mb-3">
                                                    <label for="Tipo" class="font-medium">Tipo <span class="text-red-500">*</span></label>
                                                    <Select v-model="rule.type" :options="workTypeOptions" optionLabel="label" optionValue="value" placeholder="Selecciona tipo" class="w-full"
                                                        :class="{ 'p-invalid': frontErrors[`dynamicRules.${index}.type`] }" 
                                                        @change="clearError(`dynamicRules.${index}.type`)" />
                                                    <Message
                                                        v-if="frontErrors[`dynamicRules.${index}.type`]"
                                                        severity="error"
                                                        size="medium"
                                                        variant="simple"
                                                    >
                                                        {{ frontErrors[`dynamicRules.${index}.type`] }}
                                                    </Message>
                                                </div>
                                                <div class="col-12 mt-2" v-if="!rule.isRestDay">
                                                    <div class="card w-full shadow-none p-3 my-0">
                                                        <div class="flex align-items-center justify-content-between">
                                                            <div class="flex align-items-center">
                                                                <label class="font-bold">Prima Dominical</label>
                                                            </div>
                                                            <ToggleSwitch v-model="rule.sundayBonus" />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12 md:col-2 lg:col-2 flex justify-content-end align-items-start">
                                                <Button 
                                                    v-if="form.dynamicRules.length > 1"
                                                    icon="pi pi-trash" 
                                                    class="p-button-rounded p-button-danger p-button-text p-button-sm" 
                                                    @click="removeRule(index)" 
                                                />
                                            </div>
                                        </div>
                                    </template>
                                </Card>
                            </div>
                        </div>

                        <div class="col-12 mt-3">
                            <Button label="Añadir nueva regla" icon="pi pi-plus" class="p-button-outlined" @click="addRule" />
                        </div>
                    </template>

                    <div class="col-12 mt-5">
                        <Divider />
                    </div>
                    <!-- BOTONES ABAJO -->
                    <div class="flex justify-end gap-3 pt-2">
                        <Button label="Cancelar" icon="pi pi-times" severity="secondary" @click="cancel" :loading="canceling" :disabled="form.processing || canceling" />
                        <div>
                            <Button label="Guardar" icon="pi pi-save" severity="success" @click="submitForm" :loading="form.processing" :disabled="form.processing || canceling" />
                        </div>
                    </div>
                </div>
            </div>
        
        </div>
    </AppLayout>
</template>