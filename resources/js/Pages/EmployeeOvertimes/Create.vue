<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import OvertimesEstimateCreate from "@/Components/OvertimesEstimate/Create.vue";
import OvertimesCreate from "@/Components/Overtimes/Create.vue";

const props = defineProps({
    branchOffices: Array,
    positions: Array,
    motivos: Array,
    view: String,
    schedules: Array,
    employees: Array,
});
</script>
<template>
    <AppLayout
        :title="
            view === 'estimate'
                ? 'Crear estimacion de horas extras'
                : 'Crear horas extras por empleado'
        "
    >
        <OvertimesEstimateCreate
            v-if="view === 'estimate'"
            :branchOffices="branchOffices"
            :positions="positions"
            :motivos="motivos"
            :schedules="schedules"
        />
        <OvertimesCreate
            v-else
            :motivos="motivos"
            :employees="employees"
            :branchOffices="branchOffices"
            :schedules="schedules"
        />
    </AppLayout>
</template>
