<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, reactive, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useToastService } from "@/Stores/toastService";

const canceling = ref(false);

const cancel = () => {
    if (form.processing || canceling.value) return;

    canceling.value = true;

    router.get('/catalogs/branch-offices', {}, {
        preserveScroll: true,
        onFinish: () => {
            canceling.value = false;
        }
    });
};

const props = defineProps({
    BranchOffices: Object,
    Companies: Object,
    InvoiceLocations: Object,
    EmployeeClasses: Object,
    Departments: Object,
});

const { showSuccess, showError } = useToastService();
const frontErrors = reactive({});

// --- Estado Inicial de Clases de Empleado ---
const initialEmployeeClass = () => ({
    id: null,
    department: null,
    isDefault: false
});

// --- Formulario Principal ---
const form = useForm({
    // Pestaña General
    nombre: '',
    clave: '',
    isActive: true,
    empresa: null,
    compensacionExtra: false,

    // Integración Netsuite
    claveSubsidiaria: '',
    idReporteVentas: '',
    ubicacion: null,
    ubicacionNomina: null,
    tieneReporteVentas: false,
    tieneClasificacion: false,
    employeeClasses: [initialEmployeeClass()],

    // Pestaña Datos Fiscales
    direccion: '',
    representanteLegal: '',
    rfc: '',
    registroPatronal: '',
    telefono: '',
    actividad: '',
    codigoPostal: '',
    numTurnos: 3,

    // Recibos
    recibos: {
        despuesSemana: false,
        usandoRfc: false,
        visibleModulo: true
    },

    // FIEL
    fiel: {
        certificado: null,
        llave: null,
        password: '',
        fechaExpiracion: null
    },
});

// --- Acciones de Dinámicos ---
const addEmployeeClass = () => form.employeeClasses.push(initialEmployeeClass());


// --- Envio y validacion del formulario en front ---
const removeEmployeeClass = (index) => form.employeeClasses.splice(index, 1);

const clearError = (path) => {
    if (frontErrors[path]) {
        delete frontErrors[path];
    }
};

const clearEmployeeError = (index, field) => {
    if (
        frontErrors.employeeClasses?.[index]?.[field]
    ) {
        delete frontErrors.employeeClasses[index][field];

        if (Object.keys(frontErrors.employeeClasses[index]).length === 0) {
            delete frontErrors.employeeClasses[index];
        }

        if (Object.keys(frontErrors.employeeClasses).length === 0) {
            delete frontErrors.employeeClasses;
        }
    }
};

const hydrateForm = (data) => {
    const bo = props.BranchOffices;
    const meta = bo.meta ? JSON.parse(bo.meta) : {};

    // -------- GENERALES ----------
    form.nombre = bo.name ?? '';
    form.clave = bo.code ?? '';
    form.isActive = Boolean(bo.active);
    form.empresa = bo.company_id ?? null;
    form.compensacionExtra = Boolean(meta.compensation_extra);

    // -------- NETSUITE ----------
    form.claveSubsidiaria = bo.internal_code ?? '';
    form.idReporteVentas = meta.reports_sales_id ?? '';
    form.ubicacion = bo.external_location_id
        ? String(bo.external_location_id)
        : null;
    form.ubicacionNomina = bo.payroll_location_id
        ? String(bo.payroll_location_id)
        : null;
    form.tieneReporteVentas = Boolean(bo.is_sales);
    form.tieneClasificacion = Boolean(bo.has_employee_classes);

    // -------- EMPLOYEE CLASSES ----------
    if(bo.employee_classes){
        form.employeeClasses = JSON.parse(bo.employee_classes).map(c => ({
            clase: c.id,
            department: c.department,
            isDefault: Boolean(c.default),
        }));
    }

    // -------- DATOS FISCALES ----------
    form.direccion = meta.address ?? '';
    form.representanteLegal = meta.legal_person ?? '';
    form.rfc = meta.tax_id ?? '';
    form.registroPatronal = meta.code ?? '';
    form.telefono = meta.phone ?? '';
    form.actividad = meta.activity ?? '';
    form.codigoPostal = meta.postal_code ?? '';
    form.numTurnos = Number(meta.schedules ?? 3);

    // -------- RECIBOS ----------
    form.recibos.despuesSemana = Boolean(meta.payroll_after_week);
    form.recibos.usandoRfc = Boolean(meta.payroll_rfc);
    form.recibos.visibleModulo = Boolean(meta.invoice_visible);

    // -------- FIEL (NO archivos) ----------
    form.fiel.password = bo.passphrase ?? '';
    form.fiel.fechaExpiracion = bo.expires_at
        ? new Date(bo.expires_at)
        : null;
    form.fiel.certificado = null;
    form.fiel.llave = null;

    // Debugging tipo de valores
    // console.log('Ubicación:', bo.external_location_id, typeof bo.external_location_id);
    // console.log('Ubicación Nómina:', bo.payroll_location_id, typeof bo.payroll_location_id);
    // console.log('Catálogo:', props.InvoiceLocations.map(l => [l.code, typeof l.code]));

};

onMounted(() => {
    hydrateForm(props.BranchOffices);
});

const downloading = ref(false)

async function downloadKeys(path) {
    // Validación básica
    if (!path) {
        toast.add({
            severity: 'warn',
            summary: 'Archivo no disponible',
            detail: 'No hay un certificado asociado a esta sucursal.',
            life: 3000
        })
        return
    }

    if (downloading.value) return

    downloading.value = true

    try {
        const fullPath = `branch_offices/fiscal_keys/${path}`
        const url = `/files/download/${encodeURIComponent(fullPath)}`

        window.location.href = url

    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudo iniciar la descarga.',
            life: 3000
        })
    } finally {
        setTimeout(() => {
            downloading.value = false
        }, 6000)
    }
}

</script>

<template>
    <AppLayout title="Ver Planta">
        <div class="card space-y-6">
            <h2 class="text-2xl font-bold mb-5">
                <i class="pi pi-eye mr-2 text-info"></i>
                Detalle de la Planta
            </h2>

            <Tabs value="0">
                <TabList>
                    <Tab value="0">General</Tab>
                    <Tab value="1">Datos Fiscales</Tab>
                    <Tab value="2">Integracion NetSuite</Tab>
                </TabList>

                <TabPanels>
                    <TabPanel value="0">
                        <div class="row">
                            <div class="col-12 col-md-4 mb-4">
                                <label for="nombre" class="block font-bold mb-2">
                                    Nombre <span class="text-red-500">*</span>
                                </label>
                                <InputText id="nombre" v-model="form.nombre" placeholder="Ej. Planta Matriz" 
                                    :class="{ 'p-invalid': frontErrors.nombre }" class="w-full"
                                    @input="clearError('nombre')" required readonly />
                                <Message
                                    v-if="frontErrors.nombre"
                                    severity="error"
                                    size="medium"
                                    variant="simple"
                                >
                                    {{ frontErrors.nombre }}
                                </Message>
                            </div>

                            <div class="col-12 col-md-4 mb-4">
                                <label for="clave" class="block font-bold mb-2">
                                    Clave <span class="text-red-500">*</span>
                                </label>
                                <InputText id="clave" v-model="form.clave" placeholder="Ej. MAT" class="w-full"
                                :class="{ 'p-invalid': frontErrors.nombre }"    
                                @input="clearError('clave')" required readonly/>
                                <Message
                                    v-if="frontErrors.clave"
                                    severity="error"
                                    size="medium"
                                    variant="simple"
                                >
                                    {{ frontErrors.clave }}
                                </Message>
                            </div>

                            <div class="col-12 col-md-4 mb-4">
                                <label for="empresa" class="block font-bold mb-2">
                                    Empresa <span class="text-red-500">*</span>
                                </label>
                                <Select id="empresa" v-model="form.empresa" :options="props.Companies" 
                                    optionLabel="name" optionValue="id" placeholder="Seleccione una empresa" 
                                    class="w-full" filter 
                                    :class="{ 'p-invalid': frontErrors.empresa }"
                                    @change="clearError('empresa')" required disabled />
                                <Message
                                    v-if="frontErrors.empresa"
                                    severity="error"
                                    size="medium"
                                    variant="simple"
                                >
                                    {{ frontErrors.empresa }}
                                </Message>
                            </div>

                            <div class="col-12 col-md-4 mb-4">
                                <div class="card w-full shadow-none p-3">
                                    <div class="flex align-items-center justify-content-between">
                                        <div class="flex align-items-center">
                                            <label 
                                                :class="form.isActive ? 'text-success font-semibold' : 'text-secondary'" 
                                                class="font-bold">
                                                Estado: {{ form.isActive ? 'Activo' : 'Inactivo' }}
                                            </label>
                                        </div>
                                        <ToggleSwitch id="isActive" v-model="form.isActive" readonly />
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-4 mb-4">
                                <div class="card w-full shadow-none p-3">
                                    <div class="flex align-items-center justify-content-between">
                                        <div class="flex align-items-center">
                                            <!-- <i class="pi pi-clock mr-3 text-xl text-primary"></i> -->
                                            <label class="font-bold" for="comp-extra">Compensación tiempo extra</label>
                                        </div>
                                        <ToggleSwitch id="comp-extra" v-model="form.compensacionExtra" readonly />
                                    </div>
                                </div>
                            </div>
                        </div>

                    </TabPanel>

                    <TabPanel value="1">
                        <div class="row mt-3">
                            <div class="col-12 mb-4">
                                <label for="direccion" class="block font-bold mb-2">
                                    Dirección <span class="text-red-500">*</span>
                                </label>
                                <Textarea id="direccion" v-model="form.direccion" placeholder="Ej. AVENIDA FRANCISCO I. MADERO PONIENTE 3407-A, COLONIA IRRIGACION, CODIGO POSTAL 58140, MORELIA, MICHOACAN" 
                                    :class="{ 'p-invalid': frontErrors.direccion }" class="w-full"
                                    @input="clearError('direccion')" required readonly/>
                                <Message
                                    v-if="frontErrors.direccion"
                                    severity="error"
                                    size="medium"
                                    variant="simple"
                                >
                                    {{ frontErrors.direccion }}
                                </Message>
                            </div>
                            <div class="col-12 col-md-4 mb-4">
                                <label for="representanteLegal" class="block font-bold mb-2">
                                    Representante legal <span class="text-red-500">*</span>
                                </label>
                                <InputText id="representanteLegal" v-model="form.representanteLegal" placeholder="Ej. JUAN PEREZ GONZALEZ" 
                                    :class="{ 'p-invalid': frontErrors.representanteLegal }" class="w-full"
                                    @input="clearError('representanteLegal')" required readonly />
                                <Message
                                    v-if="frontErrors.representanteLegal"
                                    severity="error"
                                    size="medium"
                                    variant="simple"
                                >
                                    {{ frontErrors.representanteLegal }}
                                </Message>
                            </div>
                            <div class="col-12 col-md-4 mb-4">
                                <label for="rfc" class="block font-bold mb-2">
                                    RFC <span class="text-red-500">*</span>
                                </label>
                                <InputText id="rfc" v-model="form.rfc" placeholder="Ej. PEGA880528XXX" 
                                    :class="{ 'p-invalid': frontErrors.rfc }" class="w-full"
                                    @input="clearError('rfc')" required readonly />
                                <Message
                                    v-if="frontErrors.rfc"
                                    severity="error"
                                    size="medium"
                                    variant="simple"
                                >
                                    {{ frontErrors.rfc }}
                                </Message>
                            </div>
                            <div class="col-12 col-md-4 mb-4">
                                <label for="registroPatronal" class="block font-bold mb-2">
                                    Registro Patronal <span class="text-red-500">*</span>
                                </label>
                                <InputText id="registroPatronal" v-model="form.registroPatronal" placeholder="Ej. C1234567890" 
                                    :class="{ 'p-invalid': frontErrors.registroPatronal }" class="w-full"
                                    @input="clearError('registroPatronal')" required readonly/>
                                <Message
                                    v-if="frontErrors.registroPatronal"
                                    severity="error"
                                    size="medium"
                                    variant="simple"
                                >
                                    {{ frontErrors.registroPatronal }}
                                </Message>
                            </div>
                            <div class="col-12 col-md-4 mb-4">
                                <label for="telefono" class="block font-bold mb-2">
                                    Teléfono <span class="text-red-500">*</span>
                                </label>
                                <InputText id="telefono" v-model="form.telefono" placeholder="Ej. 3281234567" 
                                    :class="{ 'p-invalid': frontErrors.telefono }" class="w-full"
                                    @input="clearError('telefono')" required readonly />
                                <Message
                                    v-if="frontErrors.telefono"
                                    severity="error"
                                    size="medium"
                                    variant="simple"
                                >
                                    {{ frontErrors.telefono }}
                                </Message>
                            </div>
                            <div class="col-12 col-md-8 mb-4">
                                <label for="actividad" class="block font-bold mb-2">
                                    Actividad <span class="text-red-500">*</span>
                                </label>
                                <InputText id="actividad" v-model="form.actividad" placeholder="Ej. Fabricación de otros productos de plástico" 
                                    :class="{ 'p-invalid': frontErrors.actividad }" class="w-full"
                                    @input="clearError('actividad')" required readonly />
                                <Message
                                    v-if="frontErrors.actividad"
                                    severity="error"
                                    size="medium"
                                    variant="simple"
                                >
                                    {{ frontErrors.actividad }}
                                </Message>
                            </div>
                            <div class="col-12 col-md-4 mb-4">
                                <label for="codigoPostal" class="block font-bold mb-2">
                                    Código postal <span class="text-red-500">*</span>
                                </label>
                                <InputNumber id="codigoPostal" v-model="form.codigoPostal" placeholder="Ej. 80270" 
                                    :inputClass="{ 'p-invalid': frontErrors.codigoPostal }" class="w-full" :min="0"
                                    @input="clearError('codigoPostal')" required readonly />
                                <Message
                                    v-if="frontErrors.codigoPostal"
                                    severity="error"
                                    size="medium"
                                    variant="simple"
                                >
                                    {{ frontErrors.codigoPostal }}
                                </Message>
                            </div>
                            <div class="col-12 col-md-4 mb-4">
                                <label for="numTurnos" class="block font-bold mb-2">
                                    Número de turnos <span class="text-red-500">*</span>
                                </label>
                                <InputNumber id="numTurnos" v-model="form.numTurnos" placeholder="Ej. 3" 
                                    :inputClass="{ 'p-invalid': frontErrors.numTurnos }" class="w-full" :min="0" :max="3"
                                    @input="clearError('numTurnos')" required readonly />
                                <Message
                                    v-if="frontErrors.numTurnos"
                                    severity="error"
                                    size="medium"
                                    variant="simple"
                                >
                                    {{ frontErrors.numTurnos }}
                                </Message>
                            </div>
                            <div class="col-12">
                                <div class="surface-ground mt-3 border-round">
                                    <Panel header="Recibos de nómina">
                                        <div class="row">
                                            <div class="col-12 col-md-4 my-4">
                                                <div class="card w-full shadow-none p-3">
                                                    <div class="flex align-items-center justify-content-between">
                                                        <div class="flex align-items-center">
                                                            <label class="font-bold">Despues de la semana</label>
                                                        </div>
                                                        <ToggleSwitch v-model="form.recibos.despuesSemana" readonly />
                                                    </div>
                                                    <p class="text-muted">
                                                        Activar si el patrón de búsqueda para el empleado está después de la semana
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-4 my-4">
                                                <div class="card w-full shadow-none p-3">
                                                    <div class="flex align-items-center justify-content-between">
                                                        <div class="flex align-items-center">
                                                            <label class="font-bold">Usando RFC</label>
                                                        </div>
                                                        <ToggleSwitch v-model="form.recibos.usandoRfc" readonly/>
                                                    </div>
                                                    <p class="text-muted">
                                                        Activar si el patrón de busqueda para el empleado es el rfc
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-4 my-4">
                                                <div class="card w-full shadow-none p-3">
                                                    <div class="flex align-items-center justify-content-between">
                                                        <div class="flex align-items-center">
                                                            <label class="font-bold">Visible en modulo</label>
                                                        </div>
                                                        <ToggleSwitch v-model="form.recibos.visibleModulo" readonly />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </Panel>
                                </div>
                            </div>
                            <div class="col-12 mt-4">
                                <div class="surface-ground mt-3 border-round">
                                    <Panel header="ClaveFiel">
                                        <div class="row">
                                            <div class="col-12 col-md-6 mb-4">
                                                <label class="block font-bold mb-2">
                                                    Certificado
                                                </label>
                                                <FileUpload
                                                    name="certificado"
                                                    mode="basic"
                                                    customUpload
                                                    :auto="false"
                                                    :multiple="false"
                                                    accept=".cer"
                                                    :maxFileSize="5000000"
                                                    disabled
                                                    chooseLabel="Seleccionar archivo"
                                                />
                                                <div class="m-1">
                                                    <small v-if="props.BranchOffices?.certificate_path" class="text-green-500">
                                                        Certificado cargado
                                                        <Button 
                                                            label="Descargar"
                                                            icon="pi pi-download" 
                                                            severity="success" 
                                                            variant="outlined"
                                                            size="small"
                                                            :loading="downloading"
                                                            @click="downloadKeys(props.BranchOffices?.certificate_path)"
                                                        />
                                                    </small>
    
                                                    <small v-else class="text-red-500">
                                                        Certificado no cargado
                                                    </small>
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-6 mb-4">
                                                <label class="block font-bold mb-2">
                                                    Llave
                                                </label>
                                                <FileUpload
                                                    name="Llave"
                                                    mode="basic"
                                                    customUpload
                                                    :auto="false"
                                                    :multiple="false"
                                                    accept=".key"
                                                    :maxFileSize="5000000"
                                                    disabled
                                                    chooseLabel="Seleccionar archivo"
                                                />
                                                <div class="m-1">
                                                    <small v-if="props.BranchOffices?.key_path" class="text-green-500">
                                                        Llave cargada
                                                        <Button 
                                                            label="Descargar"
                                                            icon="pi pi-download" 
                                                            severity="success" 
                                                            variant="outlined"
                                                            size="small"
                                                            :loading="downloading"
                                                            @click="downloadKeys(props.BranchOffices?.key_path)"
                                                        />
                                                    </small>
    
                                                    <small v-else class="text-red-500">
                                                        Llave no cargada
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6 mb-4">
                                                <label for="password" class="block font-bold mb-2">
                                                    Contraseña
                                                </label>
                                                <Password id="password" v-model="form.fiel.password"
                                                    :class="{ 'p-invalid': frontErrors.password }" fluid disabled />
                                            </div>
                                            <div class="col-12 col-md-6 mb-4">
                                                <label for="expirationDate" class="block font-bold mb-2">
                                                    Fecha de expiración
                                                </label>
                                                <DatePicker id="expirationDate" v-model="form.fiel.fechaExpiracion"
                                                    :class="{ 'p-invalid': frontErrors.expirationDate }" class="w-full" readonly/>
                                            </div>
                                        </div>
                                    </Panel>
                                </div>
                            </div>
                        </div>
                    </TabPanel>

                    <TabPanel value="2">
                    
                        <div class="row mt-3">
                            <h4>Integracion NetSuite</h4>
                            <div class="col-12 col-md-4 mb-4">
                                <label for="claveSubsidiaria" class="block font-bold mb-2">
                                    Clave Subsidiaria
                                </label>
                                <InputText id="claveSubsidiaria" v-model="form.claveSubsidiaria" placeholder="Ej. 12" 
                                    :class="{ 'p-invalid': frontErrors.claveSubsidiaria }" class="w-full" readonly/>
                            </div>
                            <div class="col-12 col-md-4 mb-4">
                                <label for="idReporteVentas" class="block font-bold mb-2">
                                    Id Reporte Ventas
                                </label>
                                <InputText id="idReporteVentas" v-model="form.idReporteVentas" placeholder="Ej. 123" 
                                    :class="{ 'p-invalid': frontErrors.idReporteVentas }" class="w-full" readonly/>
                            </div>
                            <div class="col-12 col-md-4 mb-4">
                                <label for="ubicacion" class="block font-bold mb-2">
                                    Ubicación
                                </label>
                                <Select id="ubicacion" v-model="form.ubicacion" :options="props.InvoiceLocations" 
                                    optionLabel="name" optionValue="code" placeholder="Seleccione una opción" 
                                    class="w-full" filter disabled />
                            </div>
                            <div class="col-12 col-md-4 mb-4">
                                <label for="ubicacionNomina" class="block font-bold mb-2">
                                    Ubicación Nómina
                                </label>
                                <Select id="ubicacionNomina" v-model="form.ubicacionNomina" :options="props.InvoiceLocations" 
                                    optionLabel="name" optionValue="code" placeholder="Seleccione una opción" 
                                    class="w-full" filter disabled />
                            </div>
                            <div class="col-12 col-md-4 my-4">
                                <div class="card w-full shadow-none p-3">
                                    <div class="flex align-items-center justify-content-between">
                                        <div class="flex align-items-center">
                                            <label class="font-bold">Tiene reporte de ventas</label>
                                        </div>
                                        <ToggleSwitch v-model="form.tieneReporteVentas" readonly />
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4 my-4">
                                <div class="card w-full shadow-none p-3">
                                    <div class="flex align-items-center justify-content-between">
                                        <div class="flex align-items-center">
                                            <label class="font-bold">Tiene clasificación de empleados</label>
                                        </div>
                                        <ToggleSwitch v-model="form.tieneClasificacion" readonly />
                                    </div>
                                </div>
                            </div>
                            <div v-if="form.tieneClasificacion" class="col-12">
                                <p class="font-bold mb-2">Recursos Humanos <span class="text-red-500">*</span></p>
                                <Message
                                    v-if="frontErrors.employeeClassesDefault"
                                    severity="error"
                                    class="mb-3"
                                >
                                    {{ frontErrors.employeeClassesDefault }}
                                </Message>
                                <div class="row">
                                    <div v-for="(item, index) in form.employeeClasses" :key="index" class="col-12 col-md-6 mb-3">
                                        <Card class="p-shadow-2 card p-0">
                                            <template #content>
                                                <div class="row">
                                                    <div class="col-12 mb-4">
                                                        <label for="ubicacion" class="block font-bold mb-2">
                                                            Clase <span class="text-red-500">*</span>
                                                        </label>
                                                        <Select v-model="item.clase" :options="props.EmployeeClasses" 
                                                            optionLabel="name" optionValue="code" placeholder="Seleccione una opción" 
                                                            class="w-full" filter 
                                                            :class="{ 'p-invalid': frontErrors.employeeClasses?.[index]?.clase }"
                                                            @change="clearEmployeeError(index, 'clase')" required disabled />
                                                        <Message
                                                            v-if="frontErrors.employeeClasses?.[index]?.clase"
                                                            severity="error"
                                                            variant="simple"
                                                        >
                                                            {{ frontErrors.employeeClasses[index].clase }}
                                                        </Message>
                                                    </div>
                                                    <div class="col-12 mb-4">
                                                        <label for="ubicacion" class="block font-bold mb-2">
                                                            Departmento <span class="text-red-500">*</span>
                                                        </label>
                                                        <Select v-model="item.department" :options="props.Departments" 
                                                            optionLabel="name" optionValue="name" placeholder="Seleccione una opción" 
                                                            class="w-full" filter 
                                                            :class="{ 'p-invalid': frontErrors.employeeClasses?.[index]?.department }"
                                                            @change="clearEmployeeError(index, 'department')" required disabled />
                                                        <Message
                                                            v-if="frontErrors.employeeClasses?.[index]?.department"
                                                            severity="error"
                                                            variant="simple"
                                                        >
                                                            {{ frontErrors.employeeClasses[index].department }}
                                                        </Message>
                                                    </div>
                                                    <div class="flex align-items-center">
                                                        <ToggleSwitch v-model="item.isDefault" class="mr-3" readonly />
                                                        <label class="font-medium">Por defecto</label>
                                                    </div>
                                                </div>
                                            </template>
                                        </Card>
                                    </div>
                                </div>
                            </div>
                        </div>
                    
                    </TabPanel>
                </TabPanels>
            </Tabs>

            <!-- BOTONES ABAJO -->
            <div class="flex justify-end gap-3 pt-2">
                <Button label="Volver" icon="pi pi-arrow-left" severity="secondary" @click="cancel" :loading="canceling" :disabled="form.processing || canceling" />
            </div>
        </div>
    </AppLayout>
</template>