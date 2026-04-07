<script setup>
import { computed, onMounted, ref, watch } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { useForm, router, usePage } from '@inertiajs/vue3';
import { useToastService } from "@/Stores/toastService";

const { showSuccess, showError } = useToastService();



const props = defineProps({
    branchOffices: Array,
    payrrollTypes: Array,
})


const form = useForm({
    payroll_type_id: null,
    branch_office_id: null,
    file_excel: null,
    file_pdf: null,
    date: null,
});
const loading = ref();
const errors = ref({});


const validate = () => {
    errors.value = {};

    if (!form.branch_office_id) errors.value.branch_office_id = "La planta es obligatoria";
    if (!form.payroll_type_id) errors.value.payroll_type_id = "El tipo de asiento es obligatorio";
    if (!form.date) errors.value.date = "La fecha es obligatorio";
    if (!form.file_excel) errors.value.file = "El archivo de nomina es obligatorio";
    if (!form.file_pdf) errors.value.pdf_file = "El archivo de nomina PDF es obligatorio";

    

    return Object.keys(errors.value).length === 0;
};


const submitForm = () => {

    if (!validate()) {
        showError('Hay campos obligatorios sin completar');
        return;
    }
    form.post('/payroll/payroll-departaments', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            showSuccess('Nomina creada correctamente')
            // router.get('/payroll/payroll-departaments')
        },
        onError: (errors) => {
            console.log(errors)
            Object.values(errors).forEach(err => showError(err))

        },

    });

};

const cancel = () => {
    router.get('/payroll/payroll-departaments');
};

const file = ref(null)
const progress = ref(0)

const handleFile = (event) => {
    const selected = event.target.files[0]

    if (!selected) return

    file.value = selected
    form.file_excel = file.value;
    simulateUpload()
}

const handleDrop = (event) => {
    const dropped = event.dataTransfer.files[0]

    if (!dropped) return

    file.value = dropped
    simulateUpload()
}

const simulateUpload = () => {

    progress.value = 0

    const interval = setInterval(() => {

        if (progress.value >= 100) {
            clearInterval(interval)
        } else {
            progress.value += 10
        }

    }, 200)

}

const removeFile = () => {
    file.value = null
    progress.value = 0
    form.file_excel = null
}

const pdfFile = ref(null)
const pdfProgress = ref(0)

const handlePdf = (event) => {

    const selected = event.target.files[0]

    if (!selected) return

    if (selected.type !== "application/pdf") {
        alert("Solo se permiten archivos PDF")
        return
    }

    pdfFile.value = selected;
    form.file_pdf = pdfFile.value;
    simulatePdfUpload()

}

const handleDropPdf = (event) => {

    const dropped = event.dataTransfer.files[0]

    if (!dropped) return

    if (dropped.type !== "application/pdf") {
        alert("Solo se permiten archivos PDF")
        return
    }

    pdfFile.value = dropped
    simulatePdfUpload()

}

const simulatePdfUpload = () => {

    pdfProgress.value = 0

    const interval = setInterval(() => {

        if (pdfProgress.value >= 100) {
            clearInterval(interval)
        } else {
            pdfProgress.value += 10
        }

    }, 200)

}

const removePdf = () => {
    pdfFile.value = null
    pdfProgress.value = 0
}

</script>

<template>
    <AppLayout :title="'Crear Nomina'">
        <div class="p-4 lg:p-6">
            <div class="grid gap-4">
                <Card>
                    <template #title>
                        <h2 class="text-2xl font-bold mb-5">
                            <i class="pi pi-plus-circle mr-2 text-success"></i>
                            Crear Nomina
                        </h2>
                    </template>
                    <template #content>
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                            <div class="col-span-1 md:col-span-4">
                                <label class="block mb-1 font-medium">Planta</label>
                                <Select v-model="form.branch_office_id" display="chip"
                                    :options="props.branchOffices" optionLabel="code" optionValue="id" filter placeholder="Selecciona una planta" 
                                    class="w-full" 
                                    />
                                <small class="text-red-500" v-if="errors.branch_office_id">
                                    {{ errors.branch_office_id }}
                                </small>
                            </div>
                            <div class="col-span-1 md:col-span-4">
                                <label class="block mb-1 font-medium">Tipo de asiento</label>
                                <Select v-model="form.payroll_type_id" display="chip"
                                    :options="props.payrrollTypes" optionLabel="name" optionValue="id" filter placeholder="Selecciona un tipo de asiento" 
                                    class="w-full" 
                                    />
                                <small class="text-red-500" v-if="errors.payroll_type_id">
                                    {{ errors.payroll_type_id }}
                                </small>
                            </div>
                            <div class="col-span-1 md:col-span-4">
                                <label class="block mb-1 font-medium">Fecha</label>
                                <DatePicker v-model="form.date" showIcon fluid :showOnFocus="false" />
                                <small class="text-red-500" v-if="errors.date">
                                    {{ errors.date }}
                                </small>
                            </div>
                            <div class="col-span-1 md:col-span-6">
                                <div 
                                    class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-organge-500 transition cursor-pointer "
                                    @dragover.prevent
                                    @drop.prevent="handleDrop"
                                    @click="$refs.fileInput.click()"
                                >
                                    <input
                                        ref="fileInput"
                                        type="file"
                                        accept=".csv,text/csv"
                                        class="hidden"
                                        @change="handleFile"
                                    />

                                    <div v-if="!file">
                                        <i class="pi pi-file-excel text-4xl text-green-600 mb-3"></i>
                                        <p class="text-gray-700 font-semibold">
                                            Arrastra tu archivo de nomina aquí o haz clic para subir
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            Formatos permitidos: CSV
                                        </p>
                                    </div>

                                    <div v-else class="space-y-3">

                                        <div class="flex items-center justify-center gap-2">
                                            <i class="pi pi-file-excel text-green-600"></i>

                                            <span class="font-medium text-gray-700">
                                                {{ file.name }}
                                            </span>

                                            <Button
                                                icon="pi pi-times"
                                                severity="danger"
                                                text
                                                rounded
                                                @click.stop="removeFile"
                                            />
                                        </div>

                                        <div class="w-full rounded-full h-3 overflow-hidden">
                                            <div
                                                class="bg-orange-500 h-3 transition-all duration-300"
                                                :style="{ width: progress + '%' }"
                                            ></div>
                                        </div>

                                        <span class="text-sm text-gray-600">
                                            {{ progress }} %
                                        </span>

                                    </div>
                                    <small class="text-red-500" v-if="errors.file">
                                        {{ errors.file }}
                                    </small>

                                </div>

                            </div>
                            <div class="col-span-1 md:col-span-6">

                                <div 
                                    class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-orange-500 transition cursor-pointer"
                                    @dragover.prevent
                                    @drop.prevent="handleDropPdf"
                                    @click="$refs.pdfInput.click()"
                                >
                                    <input
                                        ref="pdfInput"
                                        type="file"
                                        accept="application/pdf"
                                        class="hidden"
                                        @change="handlePdf"
                                    />

                                    
                                    <div v-if="!pdfFile">
                                        <i class="pi pi-file-pdf text-4xl text-red-500 mb-3"></i>
                                        <p class="text-gray-700 font-semibold">
                                            Arrastra tu PDF aquí o haz clic para subir
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            Solo archivos PDF permitidos
                                        </p>
                                    </div>

                                    
                                    <div v-else class="space-y-3">

                                        <div class="flex items-center justify-center gap-2">
                                            <i class="pi pi-file-pdf text-red-600"></i>
                                            <span class="font-medium text-gray-700">
                                                {{ pdfFile.name }}
                                            </span>

                                            <Button
                                                icon="pi pi-times"
                                                severity="danger"
                                                text
                                                rounded
                                                @click.stop="removePdf"
                                            />
                                        </div>

                                        <div class="w-full rounded-full h-3 overflow-hidden">
                                            <div
                                                class="bg-gradient-to-r from-orange-400 to-orange-600 h-3 transition-all duration-300"
                                                :style="{ width: pdfProgress + '%' }"
                                            ></div>
                                        </div>

                                        <span class="text-sm text-gray-600">
                                            {{ pdfProgress }} %
                                        </span>
                                    </div>
                                    <small class="text-red-500" v-if="errors.pdf_file">
                                        {{ errors.pdf_file }}
                                    </small>

                                </div>

                            </div>

                            <div class="md:col-span-12 flex justify-end gap-3 mt-6 border-t pt-4">

                                <Button
                                    label="Cancelar"
                                    icon="pi pi-times"
                                    severity="secondary"
                                    :loading="form.processing"
                                    :disabled="form.processing"
                                    @click="cancel"
                                />
                                <Button
                                    label="Guardar"
                                    icon="pi pi-save"
                                    severity="success"
                                    :loading="form.processing"
                                    :disabled="form.processing"
                                    @click="submitForm"
                                />
                            </div>
                        </div>
                    </template>
                </Card>                
            </div>
        </div>
    </AppLayout>
</template>
