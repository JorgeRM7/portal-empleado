<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToastService } from "@/Stores/toastService";
import { useForm } from "@inertiajs/vue3";
import { computed, onMounted, ref } from "vue";

const props = defineProps({
    txt: {
        type: Object,
        required: true,
    },
});

const { showError, showSuccess } = useToastService();

const selectedBranchOffice = ref(
    JSON.parse(localStorage.getItem("selectedBranchOffice")) || { id: null },
);

const employees = ref([]);
const selectedEmployee = ref(null);
const selectedDate = ref(null);
const loading = ref(false);
const hoursTXT = ref(props.txt.hours);

const form = useForm({
    employee_id: props.txt.employee_id,
    date: props.txt.date,
    hours: props.txt.hours,
});

const employeeOptions = computed(() =>
    employees.value.map((e) => ({
        ...e,
        label: `(${e.id}) ${e.full_name}`,
    })),
);

const stringToDate = (dateString) => {
    if (!dateString) return null;
    const [year, month, day] = dateString.split("-").map(Number);
    return new Date(year, month - 1, day);
};

const getEmployees = async () => {
    if (!selectedBranchOffice.value.id) return;

    loading.value = true;

    try {
        const response = await axios.get(
            `/api/employee-branchOffices?branchOfficeId=${selectedBranchOffice.value.id}`,
        );
        employees.value = response.data;
    } catch (error) {
        console.error("Error cargando empleados", error);
    } finally {
        loading.value = false;
    }
};

const submitForm = () => {
    form.date = selectedDate.value;
    form.employee_id = selectedEmployee.value;
    form.hours = hoursTXT.value;

    form.put(route("txt-history.update", props.txt.id), {
        onSuccess: () => {
            showSuccess();
        },
        onError: () => {
            showError();
        },
    });
};

onMounted(async () => {
    await getEmployees();

    const foundEmployee = employeeOptions.value.find(
        (e) => e.id === props.txt.employee_id,
    );

    if (foundEmployee) {
        selectedEmployee.value = foundEmployee;
    }

    if (props.txt.date) {
        selectedDate.value = stringToDate(props.txt.date);
    }
});

console.log(props.txt);
</script>

<template>
    <AppLayout title="Editar TXT">
        <div class="card">
            <div class="pb-6 border-b rounded-xl overflow-hidden">
                <h2
                    class="text-lg font-bold mb-4 flex align-items-center gap-2"
                >
                    Editar Tiempo Por Tiempo
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-gray-600"
                            >Empleado</label
                        >
                        <span class="p-input-icon-left w-full">
                            <Select
                                v-model="selectedEmployee"
                                :options="employeeOptions"
                                optionLabel="label"
                                placeholder="Buscar colaborador..."
                                class="w-full"
                                filter
                                :filterFields="['id', 'full_name', 'label']"
                                :loading="loading"
                                :disabled="loading"
                            />
                        </span>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-gray-600"
                            >Fecha de Incidencia</label
                        >
                        <DatePicker
                            v-model="selectedDate"
                            dateFormat="dd/mm/yy"
                            showIcon
                            placeholder="Selecciona la fecha"
                            class="w-full"
                            :disabled="loading"
                            :loading="loading"
                        />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-gray-600"
                            >Horas</label
                        >
                        <InputNumber
                            v-model="hoursTXT"
                            placeholder="Selecciona las horas"
                            class="w-full"
                            :disabled="loading"
                            :loading="loading"
                        />
                    </div>
                </div>
                <div
                    class="flex justify-end mt-6 pt-4 border-t border-gray-200"
                >
                    <Button
                        label="Guardar"
                        icon="pi pi-save"
                        class="p-button-primary px-6"
                        @click="submitForm"
                        :loading="loading"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
