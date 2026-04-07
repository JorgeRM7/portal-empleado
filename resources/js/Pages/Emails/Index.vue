<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToast } from "primevue/usetoast";
import { onMounted, ref } from "vue";
import axios from "axios";

const props = defineProps({
    data: Array,
})

//Inicializar variable de carga
const loading = ref(true);

//Indicador de envío
const sending = ref(true);

//Columnas de exportación
const exportColumns = ref({
    Correos: true,
    Planta: true,
    Semana: true,
    Año: true,
});

//Columnas visibles
const showColumns = ref({
    Correos: true,
    Planta: true,
    Semana: true,
    Año: true,
})

//Referencia tabla
const dt = ref(null);
const toast = useToast();

//Columnas fijas
const frozenColumns = ref({
    Correos: false,
    Planta: false,
    Semana: false,
    Año: false,
});

const rows = ref([{
    correos: '',
    code: '',
    week: '',
    year: '',
}, ]);


const load = async () => {
    try {
        const response = await axios.get('/payroll/payroll-emails/filter-data');
        rows.value = response.data.rows
        console.log(rows)
    } catch (error) {
        console.error(error)

    }
}

//Actualización manual
const actualizar = async () => {

    loading.value = true

    await load()

    loading.value = false

    toast.add({
        severity: "success",
        summary: "Actualizado",
        detail: "Datos actualizados correctamente",
        life: 3000,
    })
}

onMounted(async () => {

    rows.value = props.data
    loading.value = false

    //Actualizar cada 1 minuto
    setInterval(async () => {

        await load()

    }, 60000)

})
</script>

<style>
.pulse-mail {
    animation: pulseMail 1.5s infinite;
}

@keyframes pulseMail {
    0% {
        transform: scale(1);
    }

    50% {
        transform: scale(1.1);
    }

    100% {
        transform: scale(1);
    }
}

.header-live {
    display: flex;
    align-items: center;
    gap: 10px;
}
</style>

<template>
<AppLayout :title="'Correos'">

    <div class="card">
        <Toolbar>
            <template #start>
                <Button type="button" icon="pi pi-upload" label="Exportar" severity="secondary" class="mt-2 ml-2" />
            </template>
            <template #end>
                <Button label="Actualizar" icon="pi pi-refresh" severity="success" class="ml-2" @click="actualizar" />
            </template>

        </Toolbar>

        <DataTable 
            ref="dt" 
            :value="rows" 
            dataKey="id" 
            :paginator="true" 
            :rows="50" 
            scrollable 
            scrollHeight="600px" 
            tableStyle="min-width: 110rem" 
            filterDisplay="menu" 
            exportFilename="Correos" 
            :globalFilterFields="['title']" 
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown" currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} datos de correos">
            <template #header>
                <div class="flex justify-between items-center mb-6">
                    <div class="header-live">
                        <h4 class="m-0 text-xl font-semibold">
                            Monitoreo de envío de correos
                        </h4>
                    </div>
                </div>
            </template>
            <Column 
                field="correos" 
                header="Correos" 
                :filter="true" 
                :frozen="frozenColumns.Correos" 
                :style="{
                    width: '10rem',
                    display: showColumns.Correos ? '' : 'none',
                }" 
                :exportable="exportColumns.Correos">
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <Tag v-else severity="danger" class="pulse-mail">
                        <i class="pi pi-envelope mr-2"></i>
                        {{ data.correos }}
                    </Tag>
                </template>
                <template #filter="{ filterModel }">
                    <InputText v-model="filterModel.value" type="text" placeholder="Buscar" />
                </template>
            </Column>
            <Column 
                field="correos" 
                header="" 
                :filter="true" 
                :frozen="frozenColumns.Correos" 
                :style="{
                    width: '10rem',
                    display: showColumns.Correos ? '' : 'none',
                }" 
                :exportable="exportColumns.Correos">
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <Tag v-else severity="success">
                        <i class="pi pi-spin pi-spinner mr-2"></i>
                        Enviando correos...
                    </Tag>
                </template>
                <template #filter="{ filterModel }">
                    <InputText v-model="filterModel.value" type="text" placeholder="Buscar" />
                </template>
            </Column>
            <Column 
                field="code" 
                header="Planta" 
                :filter="true" 
                :frozen="frozenColumns.Planta" 
                :style="{
                    width: '10rem',
                    display: showColumns.Planta ? '' : 'none',
                }" 
                :exportable="exportColumns.Planta">
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <span v-else class="flex items-center gap-2">
                        <i class="pi pi-building text-primary"></i>
                        <strong>{{ data.code }}</strong>
                    </span>
                </template>
            </Column>
            <Column 
                field="week"
                header="Semana"
                :filter="true" 
                :frozen="frozenColumns.Semana" 
                :style="{
                    width: '10rem',
                    display: showColumns.Semana ? '' : 'none',
                }" 
                :exportable="exportColumns.Semana">

                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <Tag v-else severity="info">
                            Semana {{ data.week }}
                        </Tag>
                    </template>
            </Column>
            <Column 
                field="year" 
                header="Año" 
                :filter="true" 
                :frozen="frozenColumns.Año" 
                :style="{
                    width: '10rem',
                    display: showColumns.Año ? '' : 'none',
                }" 
                :exportable="exportColumns.Año">
                <template #body="{ data }">
                    <Skeleton v-if="loading"></Skeleton>
                    <Tag v-else severity="secondary">
                        {{ data.year }}
                    </Tag>
                </template>
            </Column>
        </DataTable>
    </div>
</AppLayout>
</template>
