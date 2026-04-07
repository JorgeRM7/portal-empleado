<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToastService } from "@/Stores/toastService";
import { router, useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const { showSuccess, showError } = useToastService();

const props = defineProps({
    employees: Array,
    shifts: Array,
    shifRole: Object,
});

const stringToLocalDate = (dateString) => {
    if (!dateString) return null;

    const [year, month, day] = dateString.split("T")[0].split("-").map(Number);

    return new Date(year, month - 1, day, 12, 0, 0);
};

const dateToString = (dateObj) => {
    if (!dateObj) return null;
    if (typeof dateObj === "string") return dateObj.split("T")[0];

    const year = dateObj.getFullYear();
    const month = String(dateObj.getMonth() + 1).padStart(2, "0");
    const day = String(dateObj.getDate()).padStart(2, "0");

    return `${year}-${month}-${day}`;
};

const form = useForm({
    employee_id: props.shifRole.employee_id,
    next_shift_role_id: props.shifRole.next_shift_role_id,
    shift_role_id: props.shifRole.shift_role_id,
    start_date: stringToLocalDate(props.shifRole.start_date),
    end_date: stringToLocalDate(props.shifRole.end_date),
    active: props.shifRole.active == 1 ? true : false,
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
    console.log(dateToString(form.start_date));
    console.log(dateToString(form.end_date));
    form.put(route("employee-shift-roles.update", props.shifRole.id), {
        onSuccess: () => {
            showSuccess();
        },
        onError: () => {
            showError();
        },
    });
};

console.log(props.shifRole);
</script>

<template>
    <AppLayout title="Editar rol de turno">
        <div class="card">
            <div class="pb-6 border-b">
                <h2
                    class="text-lg font-bold mb-4 flex align-items-center gap-2"
                >
                    Editar Rol de Turno
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-gray-600"
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
                    <small
                        v-if="form.errors.employee_id"
                        class="text-red-500"
                        >{{ form.errors.employee_id }}</small
                    >
                </div>
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
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                <div class="flex flex-col">
                    <label class="text-sm font-medium text-gray-600"
                        >Fecha de inicio</label
                    >
                    <DatePicker
                        v-model="form.start_date"
                        dateFormat="dd/mm/yy"
                        class="w-full"
                        :class="{ 'p-invalid': form.errors.start_date }"
                        placeholder="Fecha de inicio"
                    />
                    <small v-if="form.errors.start_date" class="text-red-500">{{
                        form.errors.start_date
                    }}</small>
                </div>
                <div class="flex flex-col">
                    <div>
                        <label class="text-sm font-medium text-gray-600"
                            >Fecha de fin</label
                        >
                        <DatePicker
                            v-model="form.end_date"
                            dateFormat="dd/mm/yy"
                            class="w-full"
                            :class="{ 'p-invalid': form.errors.end_date }"
                            placeholder="Fecha de fin"
                        />
                        <small
                            v-if="form.errors.end_date"
                            class="text-red-500"
                            >{{ form.errors.end_date }}</small
                        >
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                <div class="flex flex-col">
                    <label class="text-sm font-medium text-gray-600"
                        >Turno siguiente</label
                    >
                    <Select
                        v-model="form.next_shift_role_id"
                        :options="props.shifts"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Buscar rol de turno..."
                        filter
                        class="w-full"
                        :class="{
                            'p-invalid': form.errors.next_shift_role_id,
                        }"
                    />
                    <small
                        v-if="form.errors.next_shift_role_id"
                        class="text-red-500"
                        >{{ form.errors.next_shift_role_id }}</small
                    >
                </div>
                <div class="flex flex-col mt-2">
                    <label class="text-sm font-medium text-gray-600"
                        >Estado</label
                    >
                    <div class="flex align-items-center gap-2">
                        <ToggleSwitch v-model="form.active" />
                        <span v-if="form.active">Activo</span>
                        <span v-else>Inactivo</span>
                    </div>
                    <small v-if="form.errors.active" class="text-red-500">{{
                        form.errors.active
                    }}</small>
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
                            router.get('/employee/employee-shift-roles');
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
