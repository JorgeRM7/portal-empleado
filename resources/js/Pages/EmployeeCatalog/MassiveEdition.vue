<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useForm, router } from "@inertiajs/vue3";
import { computed, onMounted, ref, watch } from 'vue';
import { useToastService } from "../../Stores/toastService";
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import Papa from "papaparse";

const toast = useToast();
const { showError, showSuccess, showValidationError } = useToastService();

//Variables
const submitted = ref(false);
const visible = ref(false);
const progress = ref(0);
const interval = ref();
const headers = ref([]);
const totalColumnas = ref(0);
const fileUploadKey = ref(0);
const loading = ref(false);

const csvData = ref([])
const mapeo = ref({})
const errores = ref([])
const confirm = useConfirm()

const showErroresDialog = ref(false)

const approveDialog = ref(false)
const mapeoPendiente = ref(null)

const showResultadosDialog = ref(false);
const resultadosFinales = ref(null);

const resetAll = () => {
    headers.value = []
    totalColumnas.value = 0
    csvData.value = []
    mapeo.value = {}
    errores.value = []
    fileUploadKey.value++
}

const abrirDialogErrores = (listaErrores) => {
    errores.value = listaErrores
    showErroresDialog.value = true
}

const isDark = computed(() => document.documentElement.classList.contains('dark'));

const formatSize = (bytes) => {
    if (!bytes) return '0 B';

    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const onCustomUploader = async ({ files }) => {
    const file = files[0];

    Papa.parse(file, {
        header: true,
        skipEmptyLines: true,
        complete: (results) => {

            console.log("📦 CSV PARSEADO:", results.data);

            // HEADERS
            headers.value = Object.keys(results.data[0] || {}).map(h => ({
                header: h
            }));

            totalColumnas.value = headers.value.length;

            // DATA
            csvData.value = results.data;

        },
        error: (err) => {
            console.error("❌ Error leyendo CSV:", err);
        }
    });
};

const validacionTipos = {
    numeros: [
        'id',
        'salary',
        'daily_salary',
        'weekly_salary',
        'account_number',
        'account_card',
        'porcentaje_beneficiario',
        'porcentaje_beneficiario2',
        'days_duration'
    ],
    fechas: [
        'birthday',
        'entry_date',
        'termination_date',
        'start_date'
    ],
    texto: [
        'name',
        'surname',
        'mother_surname'
    ],
    email: [
        'email',
        'employee_parent_email',
        'company_email',
        'additional_email'
    ]
}

const validarCampo = (campo, valor) => {
    if (!valor) return true

    // NUMEROS
    if (validacionTipos.numeros.includes(campo)) {
        return !isNaN(parseFloat(valor))
    }

    // FECHAS
    if (validacionTipos.fechas.includes(campo)) {
        return /^\d{2}\/\d{2}\/\d{4}$/.test(valor)
    }

    // EMAIL
    if (validacionTipos.email.includes(campo)) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valor)
    }

    return true
}

const validarRegistrosCSV = (mapeoActual) => {
    const erroresTemp = []

    csvData.value.forEach((fila, index) => {
        const numeroFila = index + 2

        Object.entries(mapeoActual).forEach(([csv, bd]) => {
            const valor = fila[csv]

            if (!validarCampo(bd, valor)) {
                erroresTemp.push({
                    fila: numeroFila,
                    campo: getLabelFromValue(bd),
                    valor
                })
            }
        })
    })

    return erroresTemp
}

const validarMapeoBasico = (mapeoActual) => {
    const errores = []

    Object.entries(mapeoActual).forEach(([csv, bd]) => {
        const muestra = csvData.value[0]?.[csv]

        if (bd === 'birthday' && muestra && !/^\d{2}\/\d{2}\/\d{4}$/.test(muestra)) {
            errores.push(`La columna "${csv}" no parece ser una fecha válida (dd/mm/yyyy)`)
        }
    })

    return errores
}

const transformarDatos = (mapeoActual) => {
    return csvData.value.map(fila => {
        const nuevo = {}

        Object.entries(mapeoActual).forEach(([csv, bd]) => {
            nuevo[bd] = fila[csv]
        })

        return nuevo
    })
}

const confirmarImportacion = () => {
    const mapeoActual = obtenerMapeo()

    console.log("🧩 MAPEO COMPLETO:", mapeoActual)

    if (Object.keys(mapeoActual).length === 0) {
        showValidationError('Debes mapear al menos una columna')
        return
    }

    const columnasMapeadas = Object.values(mapeoActual);
    const claveMapeada = columnasMapeadas.includes('code');

    if (!claveMapeada) {
        showValidationError('La "Clave Empleado" es obligatoria para poder identificar a quién actualizar.');
        return;
    }

    const nombreColumnaCSVClave = Object.keys(mapeoActual).find(key => mapeoActual[key] === 'code');
    const hayClavesVacias = csvData.value.some((fila, index) => {
        const valor = fila[nombreColumnaCSVClave];
        return valor === null || valor === undefined || String(valor).trim() === '';
    });

    if (hayClavesVacias) {
        showValidationError('Se detectaron filas con la "Clave Empleado" vacía. Todos los registros deben tener clave.');
        return;
    }

    // 🔍 DEBUG POR COLUMNA (Opcional, lo mantengo de tu código original)
    Object.entries(mapeoActual).forEach(([csv, bd]) => {
        const muestra = csvData.value[0]?.[csv]
        if (bd === 'birthday') {
            console.log("📅 ¿Cumple formato dd/mm/yyyy?:", /^\d{2}\/\d{2}\/\d{4}$/.test(muestra))
        }
    })

    const erroresMapeo = validarMapeoBasico(mapeoActual)

    if (erroresMapeo.length > 0) {
        errores.value = erroresMapeo.map(e => ({
            fila: 'General',
            campo: 'Mapeo',
            valor: e
        }))
        showErroresDialog.value = true
        return
    }

    mapeoPendiente.value = mapeoActual
    approveDialog.value = true
}

const massiveUpdate = () => {
    approveDialog.value = false

    if (mapeoPendiente.value) {
        realizarActualizacionMasiva(mapeoPendiente.value)
    }
}

const erroresAgrupados = computed(() => {
    const grupos = {}

    errores.value.forEach(err => {
        if (!grupos[err.fila]) {
            grupos[err.fila] = []
        }
        grupos[err.fila].push(err)
    })

    return Object.entries(grupos).map(([fila, items]) => ({
        fila,
        errores: items
    }))
})

const realizarActualizacionMasiva = async (mapeoActual) => {
    const erroresValidacion = validarRegistrosCSV(mapeoActual);

    if (erroresValidacion.length > 0) {
        abrirDialogErrores(erroresValidacion);
        showValidationError(`Se encontraron ${erroresValidacion.length} errores`);
        return;
    }

    visible.value = true;
    progress.value = 0;

    toast.add({
        severity: "custom",
        summary: "Actualizando registros...",
        group: "headless",
        life: 0
    });

    if (interval.value) clearInterval(interval.value);
    interval.value = setInterval(() => {
        if (progress.value < 90) {
            progress.value += 5;
        }
    }, 200);

    loading.value = true;

    try {
        const response = await axios.post(route('catalog.actualizacionMasiva'), {
            mapeo: mapeoActual,
            datos: transformarDatos(mapeoActual)
        });

        const data = response.data;
        console.log("Respuesta recibida:", data);

        if (data.success) {
            progress.value = 100;

            setTimeout(() => {
                clearInterval(interval.value);
                toast.removeGroup("headless");

                resultadosFinales.value = data;

                showResultadosDialog.value = true;

                visible.value = false;
                loading.value = false;
                if (typeof resetAll === "function") resetAll();

                showSuccess();
            }, 500);
        }
    } catch (error) {
        clearInterval(interval.value);
        toast.removeGroup("headless");
        visible.value = false;
        loading.value = false;

        console.error("Error en la actualización:", error);
        const detailError = error.response?.data?.error || "No se pudo procesar la actualización masiva.";

        toast.add({
            severity: "error",
            summary: "Error de Importación",
            detail: detailError,
            life: 4000
        });
    } finally {
        loading.value = false;
    }
};

const obtenerMapeo = () => {
    const map = {};

    headers.value.forEach(h => {
        if (h.field !== null && h.field !== undefined && h.field !== '') {
            map[h.header] = h.field;
        }
    });

    return map;
};

const camposSistema = ref([
    { label: '--- No importar ---', value: null },
    { label: 'Clave Empleado', value: 'code' },
    { label: 'Id Externo', value: 'external_id' },
    { label: 'Nombre', value: 'name' },
    { label: 'Apellido Paterno', value: 'surname' },
    { label: 'Apellido Materno', value: 'mother_surname' },
    { label: 'Correo', value: 'email' },
    { label: 'Teléfono Personal', value: 'personal_phone' },
    { label: 'Teléfono Empresarial', value: 'company_phone' },
    { label: 'RFC', value: 'tax_id' },
    { label: 'Código Postal Fiscal', value: 'tax_postal_code' },
    { label: 'Rol Turno', value: 'shift_role_id' },
    { label: 'Inicio Rol Turno', value: 'start_date' },
    { label: 'Salario Diario', value: 'daily_salary' },
    { label: 'SDI', value: 'salary' },
    { label: 'Forma de pago', value: 'payment_method_id' },
    { label: 'Nombre de Banco', value: 'bank_id' },
    { label: 'Cuenta de Cheques', value: 'account_number' },
    { label: 'CLABE Interbancaria', value: 'account_code' },
    { label: 'Número de Tarjeta', value: 'account_card' },
    { label: 'Salario Semanal', value: 'weekly_salary' },
    { label: 'CURP/DNI', value: 'dni' },
    { label: 'IMSS', value: 'health_id' },
    { label: 'Departamento', value: 'department_id' },
    { label: 'Género', value: 'gender_id' },
    { label: 'Puesto', value: 'position_id' },
    { label: 'Fecha de Nacimiento', value: 'birthday' },
    { label: 'Entidad de Nacimiento', value: 'birth_state_id' },
    { label: 'Fecha de Ingreso', value: 'entry_date' },
    { label: 'Estatus', value: 'status' },
    { label: 'Calle', value: 'street' },
    { label: 'Colonia', value: 'location_id' },
    { label: 'Ciudad', value: 'city_id' },
    { label: 'Estado', value: 'state_id' },
    { label: 'Código Postal', value: 'employees_postal_code' },
    { label: 'Nivel de Estudios', value: 'level_education' },
    { label: 'Profesión', value: 'profession' },
    { label: 'Registro Patronal', value: 'employer_registration' },
    { label: 'Tipo de Empleado', value: 'employee_type' },
    { label: 'Prestaciones', value: 'benefits' },
    { label: 'Estado Civil', value: 'civil_state' },
    { label: 'Nombre del Beneficiario', value: 'beneficiary_name' },
    { label: 'Parentesco con Beneficiario', value: 'beneficiary_kinship' },
    { label: 'Jefe Inmediato', value: 'employee_parent_id' },
    { label: 'Empresa', value: 'company_id' },
    { label: 'Días de Duración', value: 'days_duration' },

    { label: 'Nombre Completo', value: 'full_name' },
    { label: 'Correo Corporativo', value: 'company_email' },
    { label: 'Correo Adicional', value: 'additional_email' },
    { label: 'Teléfono Adicional', value: 'additional_phone' },
    { label: 'Fecha de Baja', value: 'termination_date' },
    { label: 'Fecha de Reingreso', value: 'reentry_date' },
    { label: 'Fecha de Transferencia', value: 'transfer_date' },
    { label: 'Recontratable', value: 'rehireable' },
    { label: 'Planta', value: 'branch_office_id' },
    { label: 'Ubicación', value: 'branch_office_location_id' },
    { label: 'Máquina de Sucursal', value: 'current_branch_office_machine_id' },
    { label: 'Área', value: 'area_id' },
    { label: 'Turno (Información Adicional)', value: 'shift_role_id' },
    { label: 'Política', value: 'employee_policy_id' },
    { label: 'Tipo de Pago', value: 'payment_type' },
    { label: 'Candidato', value: 'candidate_id' },
    { label: 'Correo de Jefe Inmediato', value: 'employee_parent_email' },
    { label: 'Régimen Fiscal', value: 'tax_system_id' },
    { label: 'Código Fiscal', value: 'fiscal_code' },
    { label: 'Número Externo', value: 'external_number' },
    { label: 'Número Interno', value: 'internal_number' },
    { label: 'País', value: 'country_id' },
    { label: 'Tipo de Sangre', value: 'blood_type' },
    { label: 'Contacto de Emergencia', value: 'emergency_name' },
    { label: 'Teléfono de Emergencia', value: 'emergency_phone' },
    { label: 'Tipo de Jornada', value: 'tipo_jornada' },
    { label: 'Tipo de Trabajo', value: 'job_type' },
    { label: 'Tipo de Contrato', value: 'contract_type' },
    { label: 'Código Tipo de Contrato', value: 'contract_type_code' },
    { label: 'Unidad de Salud', value: 'unit_health' },
    { label: 'Descuento de Pensión', value: 'discount_pension' },
    { label: 'Clasificación', value: 'classification_id' },
    { label: 'Base de Cotización', value: 'base_cotization' },
    { label: 'Porcentaje del Beneficiario', value: 'porcentaje_beneficiario' },
    { label: 'Nombre del Beneficiario 2', value: 'nombre_beneficiario2' },
    { label: 'Porcentaje del Beneficiario 2', value: 'porcentaje_beneficiario2' },
    { label: 'Relación con Beneficiario 2', value: 'parentesco_beneficiario2' },
    { label: 'Ruta de Plantilla', value: 'template_path' },
    { label: 'Usuario', value: 'user_id' },
    { label: 'Cuenta', value: 'account_id' },
    { label: 'Términos y Condiciones', value: 'terms_condition' },
    // { label: 'Fecha de Creación', value: 'created_at' },
    // { label: 'Fecha de Actualización', value: 'updated_at' },
    // { label: 'Fecha de Eliminación', value: 'deleted_at' }

]);

const getLabelFromValue = (value) => {
    const campo = camposSistema.value.find(c => c.value === value);
    return campo ? campo.label : value;
};

</script>

<template>
    <AppLayout title="Edición Masiva">
        <Toast position="top-right" group="headless" @close="visible = false">
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
        <Card>
            <template #title>
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <h3>Edición Masiva</h3>
                    </div>
                </div>
            </template>
            <template #content>
                <!-- <div class="flex justify-center"> -->
                    <!-- <div class="w-96"> -->
                        <FileUpload
                            name="file"
                            :key="fileUploadKey"
                            :customUpload="true"
                            @uploader="onCustomUploader"
                            :multiple="false"
                            accept=".csv"
                            :maxFileSize="1000000"
                            :maxFiles="1"
                            :fileLimit="1"

                        >
                            <!-- HEADER -->
                            <template #header="{ chooseCallback, uploadCallback, clearCallback, files }">
                                <div class="flex flex-wrap justify-between items-center flex-1 gap-4">
                                    <div class="flex gap-2">
                                        <Button
                                            @click="chooseCallback()"
                                            icon="pi pi-file"
                                            rounded
                                            variant="outlined"
                                            severity="secondary"
                                            size="large"
                                            v-tooltip.bottom="'Seleccionar Archivo'"
                                        />

                                        <span v-tooltip.bottom="'Subir Archivo'">
                                            <Button
                                                @click="uploadCallback()"
                                                icon="pi pi-cloud-upload"
                                                rounded
                                                variant="outlined"
                                                severity="success"
                                                size="large"
                                                :disabled="!files || files.length === 0"
                                            />
                                        </span>

                                        <span v-tooltip.bottom="'Limpiar selección'">
                                            <Button
                                                @click="() => {
                                                    clearCallback();
                                                    resetAll();
                                                }"
                                                icon="pi pi-times"
                                                rounded
                                                variant="outlined"
                                                severity="danger"
                                                size="large"
                                                :disabled="!files || files.length === 0"
                                            />
                                        </span>
                                    </div>
                                </div>
                            </template>

                            <!-- CONTENT -->
                            <template #content="{ files, uploadedFiles, removeUploadedFileCallback, removeFileCallback, messages }">
                                <div class="flex flex-col gap-6 pt-4">

                                    <Message
                                        v-for="message of messages"
                                        :key="message"
                                        severity="error"
                                    >
                                        {{ message }}
                                    </Message>

                                    <!-- Archivo seleccionado -->
                                    <div v-if="files.length > 0">
                                        <h5>Archivo seleccionado</h5>
                                        <div class="flex flex-wrap gap-4">
                                            <div
                                                v-for="(file, index) of files"
                                                :key="file.name + file.size"
                                                class="p-6 rounded-border flex flex-col border items-center gap-3"
                                            >
                                                <i class="pi pi-file text-4xl"></i>

                                                <span class="font-semibold text-ellipsis max-w-60 overflow-hidden">
                                                    {{ file.name }}
                                                </span>

                                                <div>{{ formatSize(file.size) }}</div>

                                                <Badge value="Pendiente" severity="warn" />

                                                <Button
                                                    icon="pi pi-times"
                                                    @click="() => {
                                                        removeFileCallback(index);
                                                        resetAll();
                                                    }"
                                                    variant="outlined"
                                                    rounded
                                                    severity="danger"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Archivo subido -->
                                    <div v-if="uploadedFiles.length > 0">
                                        <h5>Subido</h5>
                                        <div class="flex flex-wrap gap-4">
                                            <div
                                                v-for="(file, index) of uploadedFiles"
                                                :key="file.name + file.size"
                                                class="p-6 rounded-border flex flex-col border items-center gap-3"
                                            >
                                                <i class="pi pi-file text-4xl"></i>

                                                <span class="font-semibold text-ellipsis max-w-60 overflow-hidden">
                                                    {{ file.name }}
                                                </span>

                                                <div>{{ formatSize(file.size) }}</div>

                                                <Badge value="Completado" severity="success" />

                                                <Button
                                                    icon="pi pi-times"
                                                    @click="() => {
                                                        removeUploadedFileCallback(index);
                                                        resetAll();
                                                    }"
                                                    variant="outlined"
                                                    rounded
                                                    severity="danger"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- EMPTY -->
                            <template #empty>
                                <div class="flex items-center justify-center flex-col">
                                    <i class="pi pi-file !border-2 !rounded-full !p-8 !text-4xl !text-muted-color" />
                                    <p class="mt-6 mb-0">Arrastra tu archivo CSV aquí o selecciónalo.</p>
                                </div>
                            </template>
                        </FileUpload>
                    <!-- </div> -->
                <!-- </div> -->
            </template>
        </Card>

        <Card v-if="headers.length > 0">
            <template #title>
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <h3>Documento CSV</h3>
                        <span class="text-sm text-gray-500">({{ totalColumnas }} columnas detectadas)</span>
                    </div>
                </div>
            </template>

            <template #content>
                <DataTable :value="headers" scrollable scrollHeight="800px">
                    <Column field="header" header="Columna CSV" />
                    <Column header="Asignar Campo">
                        <template #body="{ data }">
                            <Select
                                v-model="data.field"
                                :options="camposSistema"
                                optionLabel="label"
                                optionValue="value"
                                filter
                                placeholder="Seleccionar campo"
                                class="w-full"
                                showClear
                            >
                                <template #value="slotProps">
                                    <div v-if="slotProps.value === null" class="flex items-center text-gray-400 italic">
                                        <i class="pi pi-minus-circle mr-2"></i>
                                        <div>No importar</div>
                                    </div>

                                    <div v-else-if="slotProps.value" class="flex items-center">
                                        <i class="pi pi-check-circle mr-2 text-green-500" style="font-size: 14px"></i>
                                        <div>{{ getLabelFromValue(slotProps.value) }}</div>
                                    </div>

                                    <span v-else>
                                        {{ slotProps.placeholder }}
                                    </span>
                                </template>
                            </Select>
                        </template>
                    </Column>
                </DataTable>
                <div class="mt-3 text-sm">
                    Columnas mapeadas:
                    {{
                        headers.filter(h => h.field).length
                    }} / {{ headers.length }}
                </div>
                <div class="flex justify-end gap-2 flex-wrap">
                    <Button
                        icon="pi pi-arrow-left"
                        label="Volver"
                        severity="secondary"
                        @click="terminatedDialog = true"
                    />
                    <Button
                        type="button"
                        icon="pi pi-save"
                        :label="loading ? 'Importando...' : 'Importar Datos'"
                        severity="success"
                        :loading="loading"
                        :disabled="loading"
                        @click="confirmarImportacion"
                    />

                </div>
            </template>
        </Card>

        <Dialog
            v-model:visible="showErroresDialog"
            modal
            header="Errores en la importación"
            :style="{ width: '70vw' }"
        >
            <div class="mb-3">
                <Tag severity="danger" class="text-lg">
                    {{ errores.length }} errores encontrados
                </Tag>
            </div>

            <div class="max-h-96 overflow-y-auto">
                <div
                    v-for="grupo in erroresAgrupados"
                    :key="grupo.fila"
                    class="mb-4 p-3 border rounded-lg"
                >
                    <div class="font-bold text-red-500 mb-2">
                        Fila {{ grupo.fila }}
                    </div>

                    <ul class="list-disc ml-5">
                        <li v-for="err in grupo.errores" :key="err.campo">
                            <span class="font-semibold">{{ err.campo }}:</span>
                            <span class="text-red-500">
                                {{ err.valor || '(vacío)' }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <template #footer>
                <Button
                    label="Cerrar"
                    icon="pi pi-times"
                    @click="showErroresDialog = false"
                    severity="secondary"
                />
            </template>
        </Dialog>
        <Dialog
            v-model:visible="approveDialog"
            :style="{ width: '550px' }"
            header="Confirmar actualización masiva"
            :modal="true"
        >
            <!-- HEADER INFO -->
            <div
                :class="{
                    'bg-blue-50 border-l-4 border-blue-500 p-4 rounded mb-4': !isDark,
                    'bg-blue-950 border-l-4 border-blue-500 p-4 rounded mb-4': isDark,
                }"
            >
                <div class="flex items-center">
                    <i class="pi pi-exclamation-triangle text-blue-500 text-2xl mr-3"></i>
                    <div>
                        <h3
                            :class="{
                                'font-bold text-blue-800': !isDark,
                                'font-bold text-blue-200': isDark,
                            }"
                        >
                            ¿Confirmar actualización masiva?
                        </h3>
                        <p
                            :class="{
                                'text-sm text-blue-700': !isDark,
                                'text-sm text-blue-300': isDark,
                            }"
                        >
                            Está a punto de realizar una actualización masiva de
                            <span class="font-semibold">{{ csvData.length }}</span>
                            registros.
                        </p>
                    </div>
                </div>
            </div>

            <!-- WARNING -->
            <div
                :class="{
                    'bg-red-50 border-l-4 border-red-500 p-4 rounded mb-4': !isDark,
                    'bg-red-950 border-l-4 border-red-500 p-4 rounded mb-4': isDark,
                }"
            >
                <div class="flex items-start">
                    <i class="pi pi-exclamation-circle text-red-500 text-xl mr-3 mt-1"></i>
                    <p
                        :class="{
                            'text-sm text-red-700': !isDark,
                            'text-sm text-red-300': isDark,
                        }"
                    >
                        <span class="font-bold">ADVERTENCIA:</span>
                        Esta acción no se puede deshacer. Los datos originales no podrán
                        recuperarse después de la actualización.
                    </p>
                </div>
            </div>

            <!-- CHECKLIST -->
            <div class="mb-2">
                <h4
                    :class="{
                        'font-semibold text-gray-800 mb-2': !isDark,
                        'font-semibold text-gray-200 mb-2': isDark,
                    }"
                >
                    Por favor verifique que:
                </h4>

                <ul
                    :class="{
                        'text-sm text-gray-700 list-disc ml-6 space-y-1': !isDark,
                        'text-sm text-gray-300 list-disc ml-6 space-y-1': isDark,
                    }"
                >
                    <li>El archivo CSV contiene los datos correctos</li>
                    <li>La relación de columnas está asignada correctamente</li>
                    <li>Ha revisado cuidadosamente los datos a actualizar</li>
                </ul>
            </div>

            <!-- FOOTER -->
            <template #footer>
                <Button
                    label="Cancelar"
                    icon="pi pi-times"
                    text
                    @click="approveDialog = false"
                    severity="secondary"
                    :loading="loading"
                />
                <Button
                    label="Sí, proceder con la actualización"
                    icon="pi pi-check"
                    @click="massiveUpdate"
                    severity="succes"
                    :loading="loading"
                />
            </template>
        </Dialog>
        <Dialog
            v-model:visible="showResultadosDialog"
            modal
            header="Resumen de la Operación"
            :style="{ width: '60vw' }"
        >
            <div v-if="resultadosFinales" class="flex flex-col gap-4">
                <div class="flex gap-4 justify-center">
                    <Tag severity="success" :value="'Exitosos: ' + resultadosFinales.actualizados" size="large" />
                    <Tag severity="danger" :value="'Fallidos: ' + resultadosFinales.fallidos" size="large" />
                </div>

                <Divider />

                <div v-if="resultadosFinales.insertados_lista?.length > 0">
                    <h4 class="font-bold text-green-600 mb-3">Actualizados correctamente:</h4>

                    <div class="max-h-[400px] overflow-y-auto border border-green-100 rounded">
                        <Accordion :multiple="true">
                            <AccordionTab v-for="item in resultadosFinales.insertados_lista" :key="item.id">
                                <template #header>
                                    <div class="flex justify-between w-full">
                                        <span class="font-bold">ID: {{ item.id }} - {{ item.nombre }}</span>
                                        <Badge :value="Object.keys(item.cambios).length + ' tablas modif.'" severity="info" class="ml-2"></Badge>
                                    </div>
                                </template>

                                <div v-if="Object.keys(item.cambios).length > 0">
                                    <div v-for="(detalle, tabla) in item.cambios" :key="tabla" class="mb-4 last:mb-0">
                                        <div class="text-sm font-bold uppercase text-gray-500 mb-1 border-b border-gray-200">
                                            {{ tabla.replace('_', ' ') }}
                                        </div>

                                        <div class="grid grid-cols-1 gap-2 ml-2">
                                            <div v-for="(valor, campo) in detalle.after" :key="campo" class="text-sm">
                                                <i class="pi pi-angle-right text-green-500 mr-2"></i>
                                                <span class="font-semibold">{{ campo }}:</span>
                                                <span class="text-gray-400 line-through mx-2">
                                                    {{ detalle.before[campo] ?? 'Nulo' }}
                                                </span>
                                                <i class="pi pi-arrow-right text-xs mx-1"></i>
                                                <span class="text-green-600 font-bold">
                                                    {{ valor }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-sm italic text-gray-400">
                                    No hubo cambios detectados (los datos ya eran iguales).
                                </div>
                            </AccordionTab>
                        </Accordion>
                    </div>
                </div>

                <div v-if="resultadosFinales.fallidos_lista?.length > 0" class="mt-4">
                    <h4 class="font-bold text-red-600 mb-2"><i class="pi pi-times-circle mr-2"></i>No se pudieron actualizar:</h4>
                    <ul class="list-disc ml-6 max-h-40 overflow-y-auto bg-red-50 dark:bg-red-950 p-3 rounded">
                        <li v-for="item in resultadosFinales.fallidos_lista" :key="item.id" class="text-sm text-red-700 dark:text-red-300">
                            <strong>ID: {{ item.id }}</strong> - {{ item.motivo || 'Error desconocido' }}
                        </li>
                    </ul>
                </div>
            </div>

            <template #footer>
                <Button label="Cerrar" icon="pi pi-check" @click="showResultadosDialog = false" class="p-button-outlined" />
            </template>
        </Dialog>
    </AppLayout>
</template>
