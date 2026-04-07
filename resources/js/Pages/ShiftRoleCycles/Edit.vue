<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToastService } from "@/Stores/toastService";
import { router, useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const { showSuccess, showError } = useToastService();

const props = defineProps({
    employees: Array,
    schedules: Array,
    shifts: Array,
    shiftRoleCycle: Object,
});

console.log(props.shiftRoleCycle);

const form = useForm({
    employee_id: props.shiftRoleCycle.employee_id,
    schedule_id: props.shiftRoleCycle.schedule_id,
    shift_role_id: props.shiftRoleCycle.shift_role_id,
    started_at: props.shiftRoleCycle.started_at,
    ends_at: props.shiftRoleCycle.ends_at,
    not_end_date: props.shiftRoleCycle.ends_at ? false : true,
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

const transformDate = (date) => {
    if (!date) return null; // Importante para cuando limpian el filtro
    const newDate = new Date(date);
    const year = newDate.getFullYear();
    const month = newDate.getMonth() + 1;
    const day = newDate.getDate();
    return `${year}-${month < 10 ? "0" + month : month}-${day < 10 ? "0" + day : day} 00:00:00`;
};

const employeesByBranchOffice = ref(
    props.employees.filter(
        (employee) =>
            employee.branch_office_id === selectedBranchOffice.value.id,
    ),
);

const employeeOptions = computed(() =>
    employeesByBranchOffice.value.map((e) => ({
        ...e,
        label: `(${e.id}) ${e.full_name}`,
    })),
);

const submitForm = () => {
    form.started_at = transformDate(form.started_at);
    form.ends_at = transformDate(form.ends_at);

    form.put(route("shift-role-cycles.update", props.shiftRoleCycle[0].id), {
        onSuccess: () => {
            showSuccess();
            form.reset();
        },
        onError: () => {
            showError();
        },
    });
};
</script>

<template>
    <AppLayout title="Crear ciclo de turno">
        <div class="card">
            <div class="pb-6 border-b">
                <h2
                    class="text-lg font-bold mb-4 flex align-items-center gap-2"
                >
                    Crear Ciclo de Turno
                </h2>
            </div>
            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium text-gray-600 mt-4"
                    >Empleado</label
                >
                <span class="p-input-icon-left w-full">
                    <Select
                        v-model="form.employee_id"
                        :options="employeeOptions"
                        optionLabel="label"
                        optionValue="id"
                        placeholder="Buscar empleado"
                        filter
                        class="w-full"
                        :class="{ 'p-invalid': form.errors.employee_id }"
                    />
                </span>
                <small v-if="form.errors.employee_id" class="text-red-500">{{
                    form.errors.employee_id
                }}</small>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-gray-600"
                        >Rol de turno</label
                    >
                    <Select
                        v-model="form.shift_role_id"
                        :options="props.shifts"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Buscar rol de turno..."
                        filter
                        class="w-full"
                        :class="{ 'p-invalid': form.errors.shift_role_id }"
                    />
                    <small
                        v-if="form.errors.shift_role_id"
                        class="text-red-500"
                        >{{ form.errors.shift_role_id }}</small
                    >
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-gray-600"
                        >Horario</label
                    >
                    <Select
                        v-model="form.schedule_id"
                        :options="props.schedules"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Buscar horario..."
                        filter
                        class="w-full"
                        :class="{ 'p-invalid': form.errors.schedule_id }"
                    />
                    <small
                        v-if="form.errors.schedule_id"
                        class="text-red-500"
                        >{{ form.errors.schedule_id }}</small
                    >
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-gray-600"
                        >Fecha de inicio</label
                    >
                    <DatePicker
                        v-model="form.started_at"
                        dateFormat="dd/mm/yy"
                        class="w-full"
                        :class="{ 'p-invalid': form.errors.started_at }"
                        placeholder="Fecha de inicio"
                    />
                    <small v-if="form.errors.started_at" class="text-red-500">{{
                        form.errors.started_at
                    }}</small>
                </div>
                <div class="flex flex-col gap-2">
                    <div>
                        <label class="text-sm font-medium text-gray-600 mt-2"
                            >Fecha de fin</label
                        >
                        <DatePicker
                            v-model="form.ends_at"
                            dateFormat="dd/mm/yy"
                            class="w-full"
                            :class="{ 'p-invalid': form.errors.ends_at }"
                            placeholder="Fecha de fin"
                            :disabled="form.not_end_date"
                        />
                        <small
                            v-if="form.errors.ends_at"
                            class="text-red-500"
                            >{{ form.errors.ends_at }}</small
                        >
                    </div>
                    <div>
                        <Checkbox
                            v-model="form.not_end_date"
                            :binary="true"
                            @change="
                                form.ends_at = null;
                                form.errors.ends_at = null;
                            "
                        />
                        <label class="text-sm font-medium text-gray-600 ml-2"
                            >Sin fecha de fin</label
                        >
                    </div>
                </div>
            </div>
            <div class="flex justify-end mt-4">
                <Button
                    label="Cancelar"
                    icon="pi pi-times"
                    severity="secondary"
                    class="mr-2"
                    :loading="form.processing"
                    @click="
                        () => {
                            router.get('/employee/shift-role-cycles');
                        }
                    "
                />
                <Button
                    label="Guardar"
                    icon="pi pi-save"
                    :loading="form.processing"
                    @click="submitForm"
                />
            </div>
        </div>
    </AppLayout>
</template>
