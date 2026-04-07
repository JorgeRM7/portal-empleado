<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import OvertimesEstimateEdit from "@/Components/OvertimesEstimate/Edit.vue";
import OvertimesEdit from "@/Components/Overtimes/Edit.vue";

const props = defineProps({
    branchOffices: Array,
    positions: Array,
    motivos: Array,
    view: String,
    schedules: Array,
    employeeOvertimeEstimate: Object,
    employees: Array,
    overtime: Object,
});

console.log(props.employeeOvertimeEstimate);
</script>
<template>
    <AppLayout
        :title="
            view === 'estimate'
                ? 'Editar estimacion de horas extras'
                : 'Editar horas extras por empleado'
        "
    >
        <OvertimesEstimateEdit
            v-if="view === 'estimate'"
            :branchOffices="branchOffices"
            :positions="positions"
            :motivos="motivos"
            :schedules="schedules"
            :employeeOvertimeEstimate="employeeOvertimeEstimate"
        />

        <OvertimesEdit
            v-if="view === 'overtimes'"
            :motivos="motivos"
            :employees="employees"
            :branchOffices="branchOffices"
            :schedules="schedules"
            :overtime="overtime"
        />
    </AppLayout>
</template>
