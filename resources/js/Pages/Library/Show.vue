<script setup>
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";

const page = usePage();
const event = page.props.event;

const formatDate = (date) => {
    return new Date(date).toLocaleString('es-MX');
};

const cancel = () => {
    router.get('/assistences/events');
};
</script>

<template>
    <AppLayout :title="'Detalle del evento'">
        <div class="p-4 lg:p-6">
            <Card>
                <template #title>
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-bold">
                            <i class="pi pi-calendar mr-2 text-primary"></i>
                            {{ event.title }}
                        </h2>
                        <Tag
                            :value="event.all_day ? 'Todo el día' : 'Evento normal'"
                            :severity="event.all_day ? 'info' : 'success'"
                        />
                    </div>
                </template>

                <template #content>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <div>
                            <p class="font-semibold mb-1">Fecha inicio</p>
                            <p>{{ formatDate(event.start_date) }}</p>
                        </div>

                        <div>
                            <p class="font-semibold mb-1">Fecha fin</p>
                            <p>
                                {{ event.end_date ? formatDate(event.end_date) : '—' }}
                            </p>
                        </div>

                        <div>
                            <p class="font-semibold mb-1">Feriado</p>
                            <Tag
                                :value="event.holiday ? 'Sí' : 'No'"
                                :severity="event.holiday ? 'danger' : 'secondary'"
                            />
                        </div>

                        <div>
                            <p class="font-semibold mb-1">Color</p>
                            <div class="flex items-center gap-2">
                                <span
                                    :style="{
                                        backgroundColor: event.color,
                                        width: '25px',
                                        height: '25px',
                                        display: 'inline-block',
                                        borderRadius: '4px'
                                    }"
                                ></span>
                                <span>{{ event.color }}</span>
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <p class="font-semibold mb-1">Descripción</p>
                            <p class="p-3 rounded">
                                {{ event.description || 'Sin descripción' }}
                            </p>
                        </div>
                        <div class="md:col-span-2 flex justify-end ">
                            <Button
                                label="Cancelar"
                                icon="pi pi-times"
                                severity="secondary"
                                @click="cancel"
                            />
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
