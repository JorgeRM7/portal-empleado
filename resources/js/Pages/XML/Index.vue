<script setup>
import { ref, onMounted } from 'vue'
import AppLayout from "@/Layouts/AppLayout.vue";
import PendientesTab from '@/Components/TabsPendientes.vue'
// import SaldosTab from '@/Components/TabsSaldosxEmpleado.vue'
// import MovimientosTab from '@/Components/TabsMovimientos.vue'

const props = defineProps({
    // branchOffices: Array,
    // departments: Array,
    // class_netsuite: Array,

    BranchOffices: Array,
    Departments: Array,
    NetsuiteClass: Array,
    NetsuiteExpenseCategory: Array,
    InvoiceTerm: Array,
    InvoiceAccountingList: Array,
    InvoiceArticle: Array,
    InvoiceExclusionCategory: Array,
    InvoiceOperationType: Array,
    InvoiceLocation: Array
});

const activeTab = ref('pendientes')

onMounted(() => {
    activeTab.value = 'pendientes'
})
</script>

<template>
    <AppLayout :title="'Facturación'">
        <!-- <pre>
            {{ props }}
        </pre> -->
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
                    <Tab value="pendientes">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-clock" />
                            <span>Pendientes</span>
                        </div>
                    </Tab>
                    <Tab value="canceladas">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-ban" />
                            <span>Canceladas</span>
                        </div>
                    </Tab>
                    <Tab value="correctas">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-check-circle" />
                            <span>Correctas</span>
                        </div>
                    </Tab>
                    <Tab value="error">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-times-circle" />
                            <span>Error</span>
                        </div>
                    </Tab>
                </TabList>

                <TabPanels>
                    <TabPanel value="pendientes">
                        <PendientesTab
                            :BranchOffices="BranchOffices"
                            :Departments="Departments"
                            :NetsuiteClass="NetsuiteClass"
                            :NetsuiteExpenseCategory="NetsuiteExpenseCategory"
                            :InvoiceTerm="InvoiceTerm"
                            :InvoiceAccountingList="InvoiceAccountingList"
                            :InvoiceArticle="InvoiceArticle"
                            :InvoiceExclusionCategory="InvoiceExclusionCategory"
                            :InvoiceOperationType="InvoiceOperationType"
                            :InvoiceLocation="InvoiceLocation"
                        />
                    </TabPanel>
                    <TabPanel value="canceladas">
                        <!-- <SaldosTab
                            :branchOffices="branchOffices"
                            :departments="departments"
                        /> -->
                    </TabPanel>
                    <TabPanel value="correctas">
                        <!-- <MovimientosTab
                            :branchOffices="branchOffices"
                            :departments="departments"
                        /> -->
                    </TabPanel>
                    <TabPanel value="error">
                        <!-- <MovimientosTab
                            :branchOffices="branchOffices"
                            :departments="departments"
                        /> -->
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </div>

    </AppLayout>
</template>
