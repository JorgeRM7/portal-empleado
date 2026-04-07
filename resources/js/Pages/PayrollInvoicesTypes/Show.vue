<script setup>
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";

const page = usePage();
const payrollType = page.props.data;

const goBack = () => {
    router.get('/payroll/payroll-invoice-types');
};
</script>

<template>
    <AppLayout :title="'Detalle del tipo de recibo'">
        <div class="p-4 lg:p-6">
            <Card class="shadow-2 border-0 rounded-2xl overflow-hidden">
                <template #title>
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center">
                                <i class="pi pi-file-edit text-2xl text-blue-600"></i>
                            </div>

                            <div>
                                <h2 class="text-2xl font-bold text-900 m-0">
                                    {{ payrollType.name || 'Detalle del tipo de recibo' }}
                                </h2>
                                <p class="text-sm text-600 mt-1 mb-0">
                                    Información general del tipo de recibo seleccionado
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <Button
                                label="Volver al listado"
                                icon="pi pi-arrow-left"
                                severity="secondary"
                                outlined
                                @click="goBack"
                            />
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>