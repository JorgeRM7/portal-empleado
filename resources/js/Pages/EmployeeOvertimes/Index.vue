<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import OvertimesEstimateIndex from "@/Components/OvertimesEstimate/Index.vue";
import DiferencesResumeIndex from "@/Components/DiferencesResume/Index.vue";
import Overtimes from "@/Components/Overtimes/Index.vue";
import { usePage } from "@inertiajs/vue3";
import { useAuthz } from "@/composables/useAuthz";

const props = defineProps({
    branchOffices: Array,
    departments: Array,
    employees: Array,
    positions: Array,
    motivos: Array,
});

const page = usePage();

const { can } = useAuthz();

const actualPage = page.props.flash.page === null ? "0" : page.props.flash.page;

console.log(page.props.flash.page);
</script>

<template>
    <AppLayout title="Tiempos Extra: Estimaciones y pagos">
        <div class="card">
            <Tabs :value="actualPage">
                <TabList>
                    <Tab value="0" v-if="can('estimates.index')"
                        >Estimacion de horas extras</Tab
                    >
                    <Tab value="1" v-if="can('overtimes.index')"
                        >Horas Extra por empleado</Tab
                    >
                    <Tab value="2" v-if="can('differences.index')"
                        >Resumen de diferencias</Tab
                    >
                </TabList>
                <TabPanels>
                    <TabPanel value="0" v-if="can('estimates.index')">
                        <OvertimesEstimateIndex
                            :branchOffices="branchOffices"
                            :positions="positions"
                        />
                    </TabPanel>
                    <TabPanel value="1" v-if="can('overtimes.index')">
                        <Overtimes
                            :employees="employees"
                            :branchOffices="branchOffices"
                            :motivos="motivos"
                            :departments="departments"
                        />
                    </TabPanel>
                    <TabPanel value="2" v-if="can('differences.index')">
                        <DiferencesResumeIndex :branchOffices="branchOffices" />
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </div>
    </AppLayout>
</template>
