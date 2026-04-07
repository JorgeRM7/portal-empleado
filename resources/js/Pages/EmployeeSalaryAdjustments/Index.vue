<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import Adjustments from "@/Components/EmployeeSalaryAdjustments/Adjustments.vue";
import Weekly from "@/Components/EmployeeSalaryAdjustments/Weekly.vue";
import { useToastService } from "@/Stores/toastService";
import { watch } from "vue";
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    Plantas: Array,
    Departamentos: Array,
    Empleados: Array,
});
// console.log(props.Ajustes);

//Función para mostrar toast de éxito y error
const { showErrorCustom, showSuccessCustom } = useToastService();

const page = usePage();
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            showSuccessCustom(flash.success);
        }

        if (flash?.error) {
            showErrorCustom(flash.error);
        }
    },
    { immediate: true }
);




</script>

<template>
    <AppLayout :title="'Ajustes, promociones y cambios de puesto'">
        <div class="card">
            
            <Tabs value="0">
                <TabList>
                    <Tab value="0">
                        <i class="pi pi-money-bill mr-2"></i>
                        Ajustes salariales
                    </Tab>

                    <Tab value="1">
                        <i class="pi pi-calendar mr-2"></i>
                        Semanal
                    </Tab>

                    <Tab value="2">
                        <i class="pi pi-check-circle mr-2"></i>
                        Aceptados
                    </Tab>
                </TabList>
                <TabPanels>
                    <TabPanel value="0">
                        <Adjustments 
                            :Plantas="Plantas"
                            :Departamentos="Departamentos"
                            :Empleados="Empleados"
                            /> 
                    </TabPanel>
                    <TabPanel value="1">
                        <Weekly 
                            :Plantas="Plantas"
                            :Departamentos="Departamentos"
                            :Empleados="Empleados"
                            />
                    </TabPanel>
                    <TabPanel value="2">

                    </TabPanel>
                </TabPanels>
            </Tabs>
            
        </div>
    </AppLayout>
</template>
