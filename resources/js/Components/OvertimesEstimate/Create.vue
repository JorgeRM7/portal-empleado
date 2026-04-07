<script setup>
import { ref } from "vue";
import { router, useForm } from "@inertiajs/vue3"; // 1. Importamos useForm de Inertia
import { useToastService } from "@/Stores/toastService";

const props = defineProps({
    branchOffices: Array,
    positions: Array,
    motivos: Array,
    schedules: Array,
});

const { showSuccess, showError } = useToastService();

const form = useForm({
    branch_office_id: null,
    week: null,
    position_id: null,
    number_employees: null,
    complete_turn: false,
    schedule_id: null,
    current_turn: false,
    overtime: null,
    double_overtime: null,
    triple_overtime: null,
    motivo: null,
    coment: "",
});

const showMotivosDetails = ref(false);
const hours_schedule = ref(0);

const submit = () => {
    form.post("/employee/employee-overtimes/estimates", {
        preserveScroll: true,
        onSuccess: () => {
            showSuccess();
        },
        onError: (errors) => {
            showError();
        },
    });
};

function convertirAHorasSegundos(hora) {
    let partes = hora.split(":");
    let h = parseInt(partes[0], 10) || 0;
    let m = parseInt(partes[1], 10) || 0;
    let s = parseInt(partes[2], 10) || 0;
    return h * 3600 + m * 60 + s;
}

function diferenciaHoras() {
    const schedule = props.schedules.find(
        (schedule) => schedule.id === form.schedule_id,
    );

    let sEntrada = convertirAHorasSegundos(schedule.entry_time);
    let sSalida = convertirAHorasSegundos(schedule.leave_time);
    if (sSalida < sEntrada) {
        sSalida += 24 * 3600;
    }

    hours_schedule.value = (sSalida - sEntrada) / 3600;

    calculateHours();
}

const calculateHours = (event) => {
    form.overtime = 0;
    form.double_overtime = 0;
    form.triple_overtime = 0;
    if (!form.complete_turn) {
        form.double_overtime = event.value;
    } else if (form.complete_turn && !form.current_turn) {
        form.overtime = hours_schedule.value * form.number_employees;
        form.double_overtime = form.overtime;
    } else if (form.complete_turn && form.current_turn) {
        form.overtime = hours_schedule.value * form.number_employees;
        form.double_overtime = 3 * form.number_employees;
        form.triple_overtime = form.overtime - form.double_overtime;
    }
};
</script>

<template>
    <div class="card p-5 shadow-2 border-round-xl">
        <h4
            class="text-xl font-semibold mb-5 flex align-items-center gap-2 text-900"
        >
            Registrar Estimación de horas extra
        </h4>

        <form @submit.prevent="submit" class="p-fluid">
            <div class="flex gap-4 mb-5">
                <div class="flex-1 flex flex-column gap-2">
                    <label for="planta" class="font-medium text-900"
                        >Planta</label
                    >
                    <Select
                        id="planta"
                        v-model="form.branch_office_id"
                        :options="branchOffices"
                        optionLabel="code"
                        optionValue="id"
                        placeholder="Selecciona una planta..."
                        filter
                        :class="{ 'p-invalid': form.errors.branch_office_id }"
                    />
                    <small
                        class="p-error text-red-500"
                        v-if="form.errors.branch_office_id"
                        >{{ form.errors.branch_office_id }}</small
                    >
                </div>

                <div class="flex-1 flex flex-column gap-2">
                    <label for="semana" class="font-medium text-900"
                        >Semana</label
                    >
                    <input
                        type="week"
                        id="semana"
                        v-model="form.week"
                        class="p-inputtext p-component"
                        :class="{ 'p-invalid': form.errors.week }"
                    />
                    <small
                        class="p-error text-red-500"
                        v-if="form.errors.week"
                        >{{ form.errors.week }}</small
                    >
                </div>

                <div class="flex-1 flex flex-column gap-2">
                    <label for="puesto" class="font-medium text-900"
                        >Puesto</label
                    >
                    <Select
                        id="puesto"
                        v-model="form.position_id"
                        :options="positions"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Seleccione un puesto..."
                        :class="{ 'p-invalid': form.errors.position_id }"
                        filter
                    />
                    <small
                        class="p-error text-red-500"
                        v-if="form.errors.position_id"
                        >{{ form.errors.position_id }}</small
                    >
                </div>

                <div class="flex-1 flex flex-column gap-2">
                    <label for="numEmpleados" class="font-medium text-900"
                        >Número de empleados</label
                    >
                    <InputNumber
                        id="numEmpleados"
                        v-model="form.number_employees"
                        placeholder="0"
                        @input="calculateHours"
                        :class="{ 'p-invalid': form.errors.number_employees }"
                    />
                    <small
                        class="p-error text-red-500"
                        v-if="form.errors.number_employees"
                        >{{ form.errors.number_employees }}</small
                    >
                </div>
            </div>

            <div class="surface-50 p-4 surface-border mb-5">
                <h3
                    class="text-lg font-semibold text-800 mt-0 mb-4 pb-2 border-bottom-1 surface-border"
                >
                    Cálculo de Horas
                </h3>

                <div class="flex gap-4 align-items-start mb-4">
                    <div class="flex-1 flex flex-column gap-2">
                        <label for="turnoCompleto" class="font-medium text-900"
                            >¿Es turno completo?</label
                        >
                        <div class="mt-1">
                            <ToggleSwitch
                                id="turnoCompleto"
                                v-model="form.complete_turn"
                                @change="calculateHours"
                            />
                        </div>
                    </div>

                    <div class="flex-1 flex flex-column gap-2">
                        <label
                            for="horasExtra"
                            class="font-medium"
                            :class="
                                form.complete_turn ? 'text-500' : 'text-900'
                            "
                        >
                            Horas extra
                        </label>
                        <InputNumber
                            id="horasExtra"
                            v-model="form.overtime"
                            placeholder="0"
                            @input="calculateHours"
                            :disabled="form.complete_turn"
                            :class="{ 'p-invalid': form.errors.overtime }"
                        />
                        <small
                            class="p-error text-red-500"
                            v-if="form.errors.overtime"
                            >{{ form.errors.overtime }}</small
                        >
                    </div>

                    <template v-if="form.complete_turn">
                        <div class="flex-1 flex flex-column gap-2 fade-in">
                            <label for="turno" class="font-medium text-900"
                                >Turno</label
                            >
                            <Select
                                id="turno"
                                v-model="form.schedule_id"
                                :options="props.schedules"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Seleccione un turno..."
                                @change="diferenciaHoras(form.schedule_id)"
                                :class="{
                                    'p-invalid': form.errors.schedule_id,
                                }"
                            />
                            <small
                                class="p-error text-red-500"
                                v-if="form.errors.schedule_id"
                                >{{ form.errors.schedule_id }}</small
                            >
                        </div>

                        <div class="flex-1 flex flex-column gap-2 fade-in">
                            <label for="dobleTurno" class="font-medium text-900"
                                >¿Doble Turno?</label
                            >
                            <div class="mt-1">
                                <ToggleSwitch
                                    id="dobleTurno"
                                    v-model="form.current_turn"
                                    @change="calculateHours"
                                />
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex gap-4 mt-5">
                    <div
                        class="flex-1 surface-card p-3 border-round-lg shadow-1 border-left-3 border-blue-500 flex justify-content-between align-items-center"
                    >
                        <div class="flex flex-column gap-1">
                            <span class="text-600 font-medium text-sm"
                                >Horas Dobles</span
                            >
                            <span class="text-3xl font-bold text-blue-600"
                                >{{ form.double_overtime || 0 }} hrs</span
                            >
                        </div>
                        <i class="pi pi-clock text-blue-100 text-5xl"></i>
                    </div>

                    <div
                        class="flex-1 surface-card p-3 border-round-lg shadow-1 border-left-3 border-purple-500 flex justify-content-between align-items-center"
                    >
                        <div class="flex flex-column gap-1">
                            <span class="text-600 font-medium text-sm"
                                >Horas Triples</span
                            >
                            <span class="text-3xl font-bold text-purple-600"
                                >{{ form.triple_overtime || 0 }} hrs</span
                            >
                        </div>
                        <i class="pi pi-bolt text-purple-100 text-5xl"></i>
                    </div>
                </div>
            </div>

            <div class="flex gap-4 mb-5">
                <div class="w-full flex flex-column gap-2">
                    <div class="flex align-items-center gap-2">
                        <label for="motivo" class="font-medium text-900 mb-0"
                            >Motivo</label
                        >
                        <Button
                            icon="pi pi-question-circle"
                            rounded
                            text
                            severity="info"
                            size="small"
                            class="p-0 w-2rem h-2rem"
                            @click="showMotivosDetails = true"
                            type="button"
                        />
                    </div>
                    <Select
                        id="motivo"
                        v-model="form.motivo"
                        :options="motivos"
                        :optionLabel="
                            (option) => '(' + option.id + ') ' + option.name
                        "
                        optionValue="id"
                        placeholder="Seleccione un motivo..."
                        :class="{ 'p-invalid': form.errors.motivo }"
                        filter
                    />
                    <small
                        class="p-error text-red-500"
                        v-if="form.errors.motivo"
                        >{{ form.errors.motivo }}</small
                    >
                </div>
            </div>
            <div class="flex-1 flex flex-column gap-2">
                <label for="observaciones" class="font-medium text-900"
                    >Observaciones</label
                >
                <Textarea
                    id="observaciones"
                    v-model="form.coment"
                    rows="2"
                    class="w-full"
                    placeholder="Añade cualquier observación o justificación aquí..."
                />
            </div>

            <div class="flex justify-content-end gap-3 pt-4 surface-border">
                <Button
                    label="Cancelar"
                    icon="pi pi-times"
                    severity="secondary"
                    outlined
                    type="button"
                    @click="
                        () => {
                            router.get(route('employee-overtimes.index'));
                        }
                    "
                />
                <Button
                    label="Guardar Estimación"
                    icon="pi pi-save"
                    severity="primary"
                    type="submit"
                    :loading="form.processing"
                />
            </div>
        </form>

        <Dialog
            v-model:visible="showMotivosDetails"
            modal
            header="Detalles de Motivos"
            :style="{ width: '40vw' }"
            :breakpoints="{ '960px': '75vw', '641px': '95vw' }"
        >
            <DataTable
                :value="props.motivos"
                stripedRows
                tableStyle="min-width: 50rem"
            >
                <Column field="id" header="ID"></Column>
                <Column field="name" header="Motivo"></Column>
                <Column field="description" header="Descripción"></Column>
            </DataTable>
            <template #footer>
                <Button
                    label="Entendido"
                    icon="pi pi-check"
                    @click="showMotivosDetails = false"
                    text
                />
            </template>
        </Dialog>
    </div>
</template>

<style scoped>
.fade-in {
    animation: fadeIn 0.3s ease-in-out;
}
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
