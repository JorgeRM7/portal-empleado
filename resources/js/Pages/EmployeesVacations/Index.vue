<script setup>
import { ref, onMounted } from 'vue'
import AppLayout from "@/Layouts/AppLayout.vue";
import VacacionesTab from '@/Components/TabsVacaciones.vue'
import SaldosTab from '@/Components/TabsSaldosxEmpleado.vue'
import MovimientosTab from '@/Components/TabsMovimientos.vue'

const props = defineProps({
    branchOffices: Array,
    departments: Array,

});

const activeTab = ref('vacaciones')

onMounted(() => {
    activeTab.value = 'vacaciones'
})
</script>

<template>
    <AppLayout :title="'Employees'">
        <Toast position="top-center" group="headless" @close="visible = false">
            <template #container="{ message, closeCallback }">
                <section
                    class="flex flex-col p-4 gap-4 w-full bg-gray-100 dark:bg-gray-800 rounded-xl"
                >
                    <div class="flex items-center gap-5">
                        <i class="pi pi-cloud-upload text-2xl text-white"></i>
                        <span class="font-bold text-base text-white">{{
                            message.summary
                        }}</span>
                    </div>
                    <div class="flex flex-col gap-2">
                        <ProgressBar
                            :value="progress"
                            :showValue="false"
                            :style="{ height: '4px' }"
                            pt:value:class="!bg-primary-50 dark:!bg-primary-900"
                            class="!bg-primary/80"
                        ></ProgressBar>
                        <label class="text-sm font-bold text-white"
                            >{{ progress }}% subido</label
                        >
                    </div>
                </section>
            </template>
        </Toast>

        <div class="card mt-4">
            <Tabs v-model:value="activeTab">
                <TabList>
                    <Tab value="vacaciones">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-calendar" />
                            <span>Vacaciones</span>
                        </div>
                    </Tab>
                    <Tab value="saldos">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-wallet" />
                            <span>Saldos por empleado</span>
                        </div>
                    </Tab>
                    <Tab value="movimientos">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-sync" />
                            <span>Movimientos</span>
                        </div>
                    </Tab>
                </TabList>

                <TabPanels>
                    <TabPanel value="vacaciones">
                        <VacacionesTab
                            :branchOffices="branchOffices"
                            :departments="departments"
                        />
                    </TabPanel>
                    <TabPanel value="saldos">
                        <SaldosTab
                            :branchOffices="branchOffices"
                            :departments="departments"
                        />
                    </TabPanel>
                    <TabPanel value="movimientos">
                        <MovimientosTab
                            :branchOffices="branchOffices"
                            :departments="departments"
                        />
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </div>

    </AppLayout>
</template>
