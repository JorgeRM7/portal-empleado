<script setup>
import { ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { router, useForm } from "@inertiajs/vue3";
import { useToastService } from "@/Stores/toastService";

const { showSuccess, showError } = useToastService();

const props = defineProps({
    employees: {
        type: Array,
        default: () => [],
    },
});

const weekYear = ref(null);
const employeeSelected = ref(null);
const loading = ref(false);

const form = useForm({
    employee_id: null,
    percent: null,
    compensation: null,
    transport: null,
    piece_work: null,
    extra_compensation: null,
    total: null,
    comment: null,
    week_number: null,
    week_year: null,
    salary_payments: null,
    branch_office_id: null,
});

const getSafeBranchId = () => {
    try {
        const item = localStorage.getItem("selectedBranchOffice");
        if (!item) return null;
        const parsed = JSON.parse(item);
        return parsed || null;
    } catch (e) {
        console.warn("Error leyendo localStorage:", e);
        return null;
    }
};

const selectedBranchOffice = ref(getSafeBranchId());

const submit = () => {
    loading.value = true;
    const [year, weekNumber] = weekYear.value.split("-W");
    form.week_number = weekNumber;
    form.week_year = year;
    form.employee_id = employeeSelected.value.id;
    const salaryPayments = {
        compensation: form.compensation,
        transport: form.transport,
        extra_compensation: form.extra_compensation,
    };

    const salaryPaymentsString = JSON.stringify(salaryPayments);

    form.salary_payments = salaryPaymentsString;

    form.total =
        form.compensation +
        form.transport +
        form.piece_work +
        form.extra_compensation;
    form.branch_office_id = selectedBranchOffice.value?.id;

    form.post(route("/compensations"), {
        onSuccess: () => {
            showSuccess();
            loading.value = false;
        },
        onError: () => {
            showError();
            loading.value = false;
        },
    });
};

const cancel = () => {
    router.visit(route("/compensations"));
};

console.log(props.employees);
</script>

<template>
    <AppLayout title="Crear Compensación">
        <div class="card">
            <div class="mb-4">
                <h4 class="m-0">Añadir Nueva Compensación</h4>
            </div>

            <Divider />
            <div class="flex flex-wrap -mx-2">
                <div class="w-full md:w-1/2 px-2 mb-4">
                    <label class="block mb-2 font-medium">Empleado</label>
                    <Select
                        v-model="employeeSelected"
                        :options="props.employees"
                        optionLabel="full_name"
                        filter
                        :filterFields="['id', 'full_name']"
                        showClear
                        placeholder="Selecciona un empleado"
                        class="w-full"
                    >
                        <template #option="{ option }">
                            ({{ option.id }}) {{ option.full_name }}
                        </template>

                        <template #value="{ value }">
                            <span v-if="value">
                                ({{ value.id }}) {{ value.full_name }}
                            </span>
                            <span v-else class="text-gray-400"
                                >Selecciona un empleado</span
                            >
                        </template>
                    </Select>
                    <small v-if="form.errors.employee_id" class="text-red-500"
                        >El empleado es obligatorio</small
                    >
                </div>

                <!-- Semana (type=week => AAAA-WSS) -->
                <div class="w-full md:w-1/2 px-2 mb-4">
                    <label class="block mb-2 font-medium">Semana</label>
                    <InputText v-model="weekYear" type="week" class="w-full" />
                    <small v-if="form.errors.week_year" class="text-red-500"
                        >La semana es obligatoria</small
                    >
                </div>

                <!-- Eficiencia -->
                <div class="w-full md:w-1/2 px-2 mb-4">
                    <label class="block mb-2 font-medium">Eficiencia</label>
                    <InputNumber
                        v-model="form.percent"
                        mode="decimal"
                        suffix="%"
                        :min="0"
                        :max="100"
                        :minFractionDigits="0"
                        :maxFractionDigits="2"
                        placeholder="0 - 100%"
                        class="w-full"
                        inputClass="w-full"
                        @update:modelValue="
                            (val) => {
                                if (val > 100) form.eficiencia = 100;
                                if (val < 0) form.eficiencia = 0;
                            }
                        "
                    />
                    <small v-if="form.errors.percent" class="text-red-500"
                        >La eficiencia es obligatoria</small
                    >
                </div>

                <!-- Compensación -->
                <div class="w-full md:w-1/2 px-2 mb-4">
                    <label class="block mb-2 font-medium">Compensación</label>
                    <InputNumber
                        v-model="form.compensation"
                        mode="decimal"
                        :min="0"
                        :max="100000000"
                        :maxFractionDigits="2"
                        placeholder="0.00"
                        class="w-full"
                        inputClass="w-full"
                    />
                    <small v-if="form.errors.compensation" class="text-red-500"
                        >La compensación es obligatoria</small
                    >
                </div>

                <!-- Destajo -->
                <div class="w-full md:w-1/2 px-2 mb-4">
                    <label class="block mb-2 font-medium">Destajo</label>
                    <InputNumber
                        v-model="form.piece_work"
                        mode="decimal"
                        :min="0"
                        :max="100000000"
                        :maxFractionDigits="2"
                        placeholder="0.00"
                        class="w-full"
                        inputClass="w-full"
                    />
                </div>

                <!-- Compensación Extra -->
                <div class="w-full md:w-1/2 px-2 mb-4">
                    <label class="block mb-2 font-medium"
                        >Compensación Extra</label
                    >
                    <InputNumber
                        v-model="form.extra_compensation"
                        mode="decimal"
                        :min="0"
                        :max="100000000"
                        :maxFractionDigits="2"
                        placeholder="0.00"
                        class="w-full"
                        inputClass="w-full"
                    />
                </div>

                <!-- Apoyo Transporte -->
                <div class="w-full md:w-1/2 px-2 mb-4">
                    <label class="block mb-2 font-medium"
                        >Apoyo Transporte</label
                    >
                    <InputNumber
                        v-model="form.transport"
                        mode="decimal"
                        :min="0"
                        :max="1000000"
                        :maxFractionDigits="2"
                        placeholder="0.00"
                        class="w-full"
                        inputClass="w-full"
                    />
                </div>

                <!-- (opcional) espacio para completar la segunda columna en esa fila -->
                <div class="hidden md:block md:w-1/2 px-2 mb-4"></div>

                <!-- Descripción (ancho completo) -->
                <div class="w-full px-2 mb-2">
                    <label class="block mb-2 font-medium">Descripción</label>
                    <Textarea
                        v-model="form.comment"
                        rows="4"
                        autoResize
                        class="w-full"
                        placeholder="Notas u observaciones..."
                    />
                </div>
            </div>

            <!-- Botones abajo derecha -->
            <div class="mt-6 flex justify-end gap-2">
                <Button
                    label="Cancelar"
                    icon="pi pi-times"
                    severity="secondary"
                    outlined
                    @click="cancel"
                    :loading="loading"
                />
                <Button
                    label="Guardar"
                    icon="pi pi-check"
                    @click="submit"
                    :loading="loading"
                />
            </div>
        </div>
    </AppLayout>
</template>
