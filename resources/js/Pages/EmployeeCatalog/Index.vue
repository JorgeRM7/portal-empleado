<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToastService } from "@/Stores/toastService";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import { onMounted, ref, computed, reactive, watch  } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import { useNotifications } from "@/composables/useNotifications";
import { router } from '@inertiajs/vue3';
import { useDownloadConfirm } from "@/composables/useDownloadConfirm";
import ConfirmDialog from 'primevue/confirmdialog';
import { useAuthz } from "@/composables/useAuthz";
const { can } = useAuthz();

/* =====================
PROPS
===================== */
const props = defineProps({
    branchOffices: Array,
    departments: Array,
    employees: Array,
    data: Array,
});

//Función para mostrar toast de éxito y error
const { showError, showSuccess, showValidationError } = useToastService();

const loadingRedirect = ref(false);

const progresoManual = ref(0);
const archivoSeleccionado = ref(null);

const animarBarraProgreso = () => {
    progresoManual.value = 0;
    const intervaloMs = 500;
    const timer = setInterval(() => {
        progresoManual.value += 10;

        if (progresoManual.value >= 100) {
            progresoManual.value = 100;
            clearInterval(timer);
        }
    }, intervaloMs);
};

const onSelectedFiles = (event) => {
    archivoSeleccionado.value = event.files[0];
    animarBarraProgreso();
};

//Filas seleccionadas
const selected = ref([]);

/* =====================
ESTADO GENERAL
===================== */
const employeesFilterBranchOffice = ref([]);
const employeesCatalog = ref([]);
const selectedEstados = ref([]);
const selectedDepartment = ref([]);

const loadingExport = ref(false)
const showDialogCreateEmployee = ref(false);
const empleadoSeleccionado = ref(null);
const selectedValue = ref(null);
const treeKey = ref(0);
const formatoSeleccionado = ref(null);
const approveDialog = ref(false);
const selectedRow = ref(null);
const loadingEliminar = ref(false);
const edicion_masiva = ref(false);
const loadingEmployees = ref(false);

const { openDownloadConfirm } = useDownloadConfirm();

const handleReporte = async (tipo) => {
    const exclusionList = ["descargar_plantilla", "edicion_masiva"];

    if (!exclusionList.includes(tipo)) {
        if (!selected.value || selected.value.length === 0) {
            showValidationError("Debes seleccionar al menos un empleado.");
            selectedValue.value = null;
            treeKey.value++;
            return;
        }
    }
    if (tipo === 'descargar_plantilla') {
        descargarArchivoLocal();
        return;
    }

    if (tipo === 'edicion_masiva') {
        // edicion_masiva.value = true;
        router.get(route('catalog.massive-edition'));
        return;
    }
    const reportes = {
        validacion_cuentas: { title: "Generando reporte Validación", report_name: "VALIDACION_CUENTAS" },
        alta_imss: { title: "Generando reporte IMSS", report_name: "ALTAS_IMSS" },
        alta_sua: { title: "Generando reporte SUA", report_name: "ALTAS_SUA" },
        exportar_noi: { title: "Generando Exportación NOI", report_name: "NOI" }
    };

    const reporte = reportes[tipo];
    if (!reporte) return;

    openDownloadConfirm({
        title: reporte.title,
        delay: 1000,
        onCSV: () => generarReporte(reporte.report_name, "csv"),
        onXLSX: () => generarReporte(reporte.report_name, "excel")
    });
};

const descargarArchivoLocal = () => {
    const url = '/plantillas/plantillas_empleados.csv';
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', 'plantillas_empleados.csv');
    document.body.appendChild(link);
    link.click();
    link.remove();
};

const generarReporte = async (reportName, type) => {
    // console.log(reportName, type);
    try {

        toast.add({
            severity: "Secondary",
            summary: "Descargando",
            detail: "Espera en lo que se descarga tu archivo...",
            group: "download",
            closable: false,
            life: 0,
        });

        loadingExport.value = true;

        // Extraemos solo los IDs
        const employeesIds = selected.value.map(e => e.id);

        let url_dow;
        let payload;

        if (reportName === 'NOI') {

            url_dow = '/employee/catalog/downloadAll';

            payload = {
                type: type,
                ids_exportar: employeesIds,
                report: reportName
            };

        } else {

            url_dow = '/employee/catalog/download';

            payload = {
                empleados_list: employeesIds,
                type: type,
                report_name: reportName
            };

            columnsDialog.value = false;

        }

        const response = await axios.post(url_dow, payload, {
            responseType: 'blob'
        });

        const contentDisposition = response.headers['content-disposition'];

        let fileName = 'reporte';

        if (contentDisposition) {
            const match = contentDisposition.match(/filename="?([^"]+)"?/);
            if (match?.[1]) {
                fileName = match[1];
            }
        }

        const blob = new Blob([response.data], {
            type: response.data.type
        });

        const url = window.URL.createObjectURL(blob);

        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', fileName);
        document.body.appendChild(link);
        link.click();
        link.remove();

        window.URL.revokeObjectURL(url);

        toast.removeGroup("download");
        showSuccess();

        selectedValue.value = null;
        treeKey.value++;
    } catch (error) {
        console.error(error);
        showError();
    } finally {
        loadingExport.value = false;
        toast.removeGroup("download");
    }
};

const nodes = ref([
    {
        key: "reportes",
        label: "Reportes",
        icon: "pi pi-chart-bar",
        selectable: false,
        children: [
            {
                key: "validacion_cuentas",
                label: "Validación de Cuentas",
                icon: "pi pi-check-circle",
                permission: "catalog.export-cuentas",
            },
            {
                key: "alta_imss",
                label: "Alta IMSS",
                icon: "pi pi-id-card",
                permission: "catalog.export-imss",
            },
            {
                key: "alta_sua",
                label: "Alta SUA",
                icon: "pi pi-upload",
                permission: "catalog.export-sua",
            },
            {
                key: "exportar_noi",
                label: "Exportar NOI",
                icon: "pi pi-file-excel",
                permission: "catalog.export-noi",
            }
        ],
    },
    {
        key: "descargar_plantilla",
        label: "Descargar Plantilla",
        icon: "pi pi-download",
    },
    {
        key: "edicion_masiva",
        label: "Edición Masiva",
        icon: "pi pi-pencil",
    },
]);

const filteredNodes = computed(() => {
    return nodes.value
        .map((node) => {
            // Si tiene children (como "reportes")
            if (node.children) {
                const visibleChildren = node.children.filter((child) => {
                    if (!child.permission) return true;
                    return can(child.permission);
                });

                if (visibleChildren.length > 0) {
                    return {
                        ...node,
                        children: visibleChildren,
                    };
                }
                return null;
            }

            // Nodo sin hijos
            if (!node.permission || can(node.permission)) {
                return node;
            }

            return null;
        })
        .filter((node) => node !== null);
});

watch(selectedValue, (value) => {
    if (!value) return;

    const selectedKey = Object.keys(value)[0];
    console.log(selectedKey);
    handleReporte(selectedKey);

    selectedValue.value = null;
    treeKey.value++;
});

const crearEmpleado = () => {
    showDialogCreateEmployee.value = true;
};

const { sendNotification } = useNotifications();

const confirmarCambioEstatus = (data) => {
    console.log(data);
    selectedRow.value = data;
    approveDialog.value = true;
};

const eliminarEmpleado = () => {
    if (!selectedRow.value) return;

    loadingEliminar.value = true;
    router.post(route('catalog.termination'), {
        employee_id: selectedRow.value.id,
        type: 'delete',
        termination_date: new Date().toISOString().split('T')[0]
    }, {
        onSuccess: () => {
            loadingEliminar.value = false;
            approveDialog.value = false;
            selectedRow.value = null;
            showSuccess();
        },
        onError: () => {
            loadingEliminar.value = false;
            showError();
        }
    });
};

//Inicializar filtros globales de tabla
const filters = ref({});

//Inicializar variable de carga para mostrar esqueleto
const loading = ref(true);

// Select para estados
// const selectedEstados = ref();
const estados = ref([
    { name: 'Alta', code: 'entry' },
    { name: 'Baja', code: 'termination' },
    { name: 'Reingreso', code: 'reentry' },
    { name: 'Traspaso', code: 'change' }
]);

//Función para inicializar filtros
const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },

        id: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }]
        },

        'branch_office.code': {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }]
        },

        foto: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }]
        },

        full_name: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }]
        },

        surname: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }]
        },

        mother_surname: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }]
        },

        name: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }]
        },

        'payment_data.daily_salary': {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }]
        },

        'gender.name': {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }]
        },

        birthday: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }]
        },

        entry_date: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }]
        },

        'department.name': {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }]
        },

        status: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }]
        },

        'position.name': {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }]
        },

        antiguedad: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }]
        },

        termination_date: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }]
        },

        termination_date: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }]
        },

        health_id: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }]
        },

        'state.name': {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }]
        },

        dni: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }]
        }
    };
};

initFilters();

//Columnas de exportación
const exportColumns = ref({
    clave_empleado: true,
    apellido_paterno: true,
    nombre: true,
    genero: true,
    fecha_ingreso: true,
    estatus: true,
    antiguedad: true,
    fecha_baja: true,
    entidad_nacimiento: true,
    planta: true,
    nombre_empleado: true,
    apellido_materno: true,
    salario_diario: true,
    fecha_nacimiento: true,
    departamento: true,
    puesto: true,
    // dias_vacaciones_disponibles: true,
    imss: true,
    curp: true,
});

const exportFields = ref({
    personales: {
        label: "Datos Personales",
        fields: {
            claveEmpleado: { label: "Clave Empleado", checked: false },
            nombre: { label: "Nombre", checked: false },
            apellidoPaterno: { label: "Apellido Paterno", checked: false },
            apellidoMaterno: { label: "Apellido Materno", checked: false },
            telefonoPersonal: { label: "Telefono Personal", checked: false },
            fechaNacimiento: { label: "Fecha Nacimiento", checked: false },
            genero: { label: "Genero", checked: false },
            entidadNacimiento: { label: "Entidad Nacimiento", checked: false },
            curp: { label: "CURP", checked: false },
            nss: { label: "NSS", checked: false },
            correo: { label: "Correo", checked: false },
            calle: { label: "Calle", checked: false },
            numInterior: { label: "Numero Interior", checked: false },
            numExterior: { label: "Numero Exterior", checked: false },
            estado: { label: "Estado", checked: false },
            ciudad: { label: "Ciudad", checked: false },
            colonias:{ label: "Colonia", checked: false },
            codigoPostal: { label: "Codigo Postal", checked: false },
            pais: { label: "Pais", checked: false },
        }
    },
    fiscales: {
        label: "Datos Fiscales",
        fields: {
            rfc: { label: "RFC", checked: false },
            codigoPostalFiscal: { label: "Codigo Postal Fiscal", checked: false },
            regimen: { label: "Regimen", checked: false },
        }
    },
    pago: {
        label: "Datos de Pago",
        fields: {
            numeroCuenta: { label: "Numero Cuenta", checked: false },
            numeroTarjeta: { label: "Numero Tarjeta", checked: false },
            clabe: { label: "Clabe Interbancaria", checked: false },
            salarioDiario: { label: "Salario Diario", checked: false },
            sdi: { label: "SDI", checked: false },
            banco: { label: "Banco", checked: false },
            metodoPago: { label: "Metodo Pago", checked: false },
            salarioSemanal: { label: "Salario Semanal", checked: false },
        }
    },
    laborales: {
        label: "Datos Laborales",
        fields: {
            fechaIngreso: { label: "Fecha Ingreso", checked: false },
            fechaTerminacion: { label: "Fecha Terminacion", checked: false },
            estadoEmpleado: { label: "Estado Empleado", checked: false },
            fechaReingreso: { label: "Fecha Reingreso", checked: false },
            jefeInmediato: { label: "Jefe Inmediato", checked: false },
            correoJefeInmediato: { label: "Correo Jefe Inmediato", checked: false },
            puesto: { label: "Puesto", checked: false },
            planta: { label: "Planta", checked: false },
            ubicacion: { label: "Ubicacion", checked: false },
            departamento: { label: "Departamento", checked: false },
            telefonoEmpresa: { label: "Telefono Empresa", checked: false },
            prestaciones: { label: "Prestaciones", checked: false },
        }
    },
    adicional: {
        label: "Información Adicional",
        fields: {
            usuarioAsignado: { label: "Usuario Asignado", checked: false },
            telefonoAdicional: { label: "Telefono Adicional", checked: false },
            correoAdicional: { label: "Correo Adicional", checked: false },
            profesion: { label: "Profeccion", checked: false },
            nivelEstudios: { label: "Nivel Estudios", checked: false },
            estadoCivil: { label: "Estado Civil", checked: false },
            tipoSangre: { label: "Tipo Sangre", checked: false },
            personaEmergencia: { label: "Persona Emergencia", checked: false },
            telefonoEmergencia: { label: "Telefono Emergencia", checked: false },
            tipoEmpleado: { label: "Tipo Empleado", checked: false },
            tipoJornada: { label: "Tipo Jornada", checked: false },
            turno: { label: "Turno", checked: false },
            unidadMedica: { label: "Unidad Medica", checked: false },
            descuentoPension: { label: "Descuento Pension", checked: false },
            registroPatronal: { label: "Registro Patronal", checked: false },
            clasificacion: { label: "Clasificacion", checked: false },
            tipoOngresos: { label: "Tipo Ingresos", checked: false },
            claveRegimen: { label: "Clave Regimen", checked: false },
            baseCotizacion: { label: "Base Cotizacion", checked: false },
            tipoContrato: { label: "Tipo Contrato", checked: false },
            rutaPlantilla: { label: "Ruta Plantilla", checked: false },
            claveRegimenContrato: { label: "Clave Regimen Contrato", checked: false },
            tablaVacaciones: { label: "Tabla Vacaciones", checked: false },
            tablaSalario: { label: "Tabla Salario", checked: false },
            diasDuracion: { label: "Dias Duracion", checked: false },
            nombreBeneficiario: { label: "Nombre Beneficiario", checked: false },
            parentezcoBeneficiario: { label: "Parentezco Beneficiario", checked: false },
            nombreBeneficiario2: { label: "Nombre Beneficiario 2", checked: false },
            porcentajeBeneficiario2: { label: "Porcentaje Beneficiario 2", checked: false },
            parentezcoBeneficiario2: { label: "Parentezco Beneficiario 2", checked: false },
        }
    }

});

// Exportar
const selectAll = computed({
    get() {
        return Object.values(exportFields.value).every(group =>
            Object.values(group.fields).every(field => field.checked)
        );
    },
    set(value) {
        Object.values(exportFields.value).forEach(group => {
            Object.values(group.fields).forEach(field => {
                field.checked = value;
            });
        });
    }
});

const onSelectAllChange = (event) => {
    const allEmployees = employeesCatalog.value;

    if (event.checked) {
        selected.value = [...allEmployees];
    } else {
        selected.value = [];
    }
};

const employeeRentry = (event) => {
    const empleado = event.value;

    if (empleado && empleado.id) {
        loadingRedirect.value = true;

        router.get(route('catalog.edit', empleado.id), {}, {
            onFinish: () => {
                loadingRedirect.value = false;
            }
        });
    }
};

const getSelectedFields = () => {
    const selected = [];

    Object.values(exportFields.value).forEach(group => {
        Object.values(group.fields).forEach(field => {
            if (field.checked) {
                selected.push(field.label);
            }
        });
    });

    return selected;
};

const saveColumns = async () => {
    const campos = getSelectedFields();

    // Validar que haya empleados seleccionados
    if (!selected.value || selected.value.length === 0) {
        showValidationError('Debes seleccionar al menos un empleado a exportar');
        return;
    }

    // Validar que haya campos seleccionados
    if (!campos.length) {
        showValidationError('Debes seleccionar al menos un campo a exportar');
        return;
    }

    const tipo = await new Promise((resolve) => {
        openDownloadConfirm({
            delay: 1000,
            onCSV: () => resolve("csv"),
            onXLSX: () => resolve("excel")
        });
    });

    if (!tipo) return;

    try {
        toast.add({
            severity: "secondary",
            summary: "Generando archivo",
            detail: "Procesando registros, por favor espera...",
            group: "download",
            closable: false,
            life: 0,
        });

        columnsDialog.value = false;

        const ids = selected.value.map(e => e.id);

        const response = await axios.post('/employee/catalog/downloadAll', {
            type: tipo,
            campos: campos,
            ids_exportar: ids
        }, {
            responseType: 'blob'
        });

        // Lógica de descarga del archivo
        const blob = new Blob([response.data], { type: response.data.type });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;

        const contentDisposition = response.headers['content-disposition'];
        let fileName = `reporte_empleados_${new Date().getTime()}.${tipo === 'excel' ? 'xlsx' : 'csv'}`;

        if (contentDisposition) {
            const match = contentDisposition.match(/filename="?([^"]+)"?/);
            if (match?.[1]) fileName = match[1];
        }

        link.setAttribute('download', fileName);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);

        showSuccess();

    } catch (error) {
        console.error("Error en exportación:", error);
        showError("Error al exportar. El volumen de datos podría ser demasiado grande.");
    } finally {
        toast.removeGroup("download");
    }
};

//Columnas visibles
const showColumns = ref({
    acciones: true,
    clave_empleado: true,
    foto: false,
    apellido_paterno: false,
    nombre: false,
    genero: true,
    fecha_ingreso: true,
    estatus: false,
    antiguedad: false,
    fecha_baja: false,
    entidad_nacimiento: true,
    planta: false,
    nombre_empleado: true,
    apellido_materno: false,
    salario_diario: true,
    fecha_nacimiento: true,
    departamento: false,
    puesto: false,
    // dias_vacaciones_disponibles: false,
    imss: true,
    curp: true,
});

//Filtros adicionales
const otherFilters = ref([
    {
        startDate: null,
        endDate: null,
    },
]);

//Diálogo de filtros adicionales
const otherFilterDialog = ref(false);

//Filtros del modal
const startDate = ref();
const endDate = ref();

//Referencia a la tabla de datos
const dt = ref(null);
const toast = useToast();

//Columnas fijas
const frozenColumns = ref({
    acciones: true,
    clave_empleado: true,
    foto: false,
    apellido_paterno: false,
    nombre: false,
    genero: false,
    fecha_ingreso: false,
    estatus: false,
    antiguedad: false,
    fecha_baja: false,
    entidad_nacimiento: false,
    planta: false,
    nombre_empleado: true,
    apellido_materno: false,
    salario_diario: false,
    fecha_nacimiento: false,
    departamento: false,
    puesto: false,
    // dias_vacaciones_disponibles: false,
    imss: false,
    curp: false,
});

//Diálogo de selección de columnas
const columnsDialog = ref(false);

//Estado de envío
const submitted = ref(false);

//Referencias a los popovers
const op = ref();
const opMostrarColumnas = ref();
const opFijarColumnas = ref();

//Estado de diálogo de subida de archivos
const openUploadDialog = ref(false);

//Estado de progreso de subida
const progress = ref(0);

//Estado de visibilidad del toast
const visible = ref(false);

//Referencia al servicio de toast personalizado
const interval = ref();

//Funciones para alternar los popovers
const toggleAccionesMasivas = (event) => {
    op.value.toggle(event);
};

const toggleMostrarColumnas = (event) => {
    opMostrarColumnas.value.toggle(event);
};

const toggleFijarColumnas = (event) => {
    opFijarColumnas.value.toggle(event);
};

const continuarRegistro = () => {
    showDialogCreateEmployee.value = false;

    router.visit(route('catalog.create'));
};

/* =====================
FILTROS ACTIVOS (CHIPS)
===================== */
const activeFilters = reactive({
    branchOffice: [],
    dateFrom: null,
    dateTo: null,
    estado: null,
    department: null,
    employee: null,
    sinRol: false
});

const selectedEstadosData = computed(() => {
    if (!activeFilters.estado || !activeFilters.estado.length) return [];
    return estados.value.filter(e => activeFilters.estado.includes(e.code));
});

const selectedDepartmentsData = computed(() => {
    if (!activeFilters.department || !activeFilters.department.length) return [];
    return props.departments.filter(d => activeFilters.department.includes(d.id));
});

const removeIndividualEstado = (code) => {
    activeFilters.estado = activeFilters.estado.filter(c => c !== code);
    selectedEstados.value = [...activeFilters.estado];
    loadCatalog();
};

const removeIndividualDepartment = (id) => {
    activeFilters.department = activeFilters.department.filter(d => d !== id);
    selectedDepartment.value = [...activeFilters.department];
    loadCatalog();
};

const formatDate = (date) => {
    if (!date) return null;

    const d = new Date(date);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();

    return `${day}-${month}-${year}`;
};

const dateRangeLabel = computed(() => {
    if (!activeFilters.dateFrom && !activeFilters.dateTo) return null;

    const from = formatDate(activeFilters.dateFrom);
    const to = formatDate(activeFilters.dateTo);

    return `${from ?? '...'} - ${to ?? '...'}`;
});

const sinRolLabel = computed(() => {
    return activeFilters.sinRol ? 'Sin rol de turno' : null;
});

/* =====================
FILTROS UI
===================== */
const selectedBranchOffice = ref([]);
const selectedEmployees = ref([]);
const filteredBranchOffices = ref([]);
const valueFrom = ref(null);
const valueTo = ref(null);
// const selectedDepartment = ref(null);
const checked = ref(false);

/* =====================
REMOVE FILTER
===================== */

const removeFilter = (type, id = null) => {
    switch (type) {

        case 'branchOffice':
            if (id !== null) {
                selectedBranchOffice.value.filter(b => b !== id);
                activeFilters.branchOffice = activeFilters.branchOffice.filter(branchId => branchId !== id);
            } else {
                selectedBranchOffice.value = [];
                activeFilters.branchOffice = [];
            }
            localStorage.setItem("selectedBranchOffice", JSON.stringify(activeFilters.branchOffice));
            break;

        case 'estado':
            selectedEstados.value = null;
            activeFilters.estado = null;
            break;

        case 'department':
            selectedDepartment.value = null;
            activeFilters.department = null;
            break;

        case 'date':
            valueFrom.value = null;
            valueTo.value = null;
            activeFilters.dateFrom = null;
            activeFilters.dateTo = null;
            break;

        case 'employees':
            selectedEmployees.value = [];
            activeFilters.employee = [];
            break;

        case 'sinRol':
            checked.value = false;
            activeFilters.sinRol = false;
            break;
    }

    loadCatalog();
};

const removePlant = (plantId) => {
    selectedBranchOffice.value =
        selectedBranchOffice.value.filter(id => id !== plantId)

    activeFilters.branchOffice =
        activeFilters.branchOffice.filter(id => id !== plantId)

    loadCatalog()
}

const getPlantName = (id) => {
    const plant = props.branchOffices.find(b => b.id === id)
    return plant ? plant.code : id
}

const allBranchOfficeIds = computed(() =>
    props.branchOffices.map(b => b.id)
)

const branchOfficesWithAll = computed(() => [
    { id: 'all', code: 'TODAS LAS PLANTAS' },
    ...props.branchOffices
])

const updateBranches = (values) => {
    const allIds = allBranchOfficeIds.value
    const hadAllSelected =
        selectedBranchOffice.value.length === allIds.length + 1 &&
        selectedBranchOffice.value.includes('all')

    const hasAllNow = values.includes('all')

    if (hasAllNow && !hadAllSelected && values.length !== allIds.length) {
        selectedBranchOffice.value = ['all', ...allIds]
        return
    }

    if (!hasAllNow && hadAllSelected && values.length === allIds.length) {
        selectedBranchOffice.value = []
        return
    }

    if (hasAllNow && values.length < allIds.length + 1) {
        selectedBranchOffice.value = values.filter(v => v !== 'all')
        return
    }

    const onlyRealIds = values.filter(v => v !== 'all')
    if (onlyRealIds.length === allIds.length) {
        selectedBranchOffice.value = ['all', ...allIds]
        return
    }

    selectedBranchOffice.value = onlyRealIds
}

const getEmployeeName = (id) => {
    const emp = employeesFilterBranchOffice.value.find(e => e.id === id);
    return emp ? emp.full_name : `ID: ${id}`;
};

const removeIndividualEmployee = (empId) => {
    activeFilters.employee = activeFilters.employee.filter(id => id !== empId);
    selectedEmployees.value = selectedEmployees.value.filter(v => {
        const id = typeof v === 'object' ? v.id : v;
        return Number(id) !== Number(empId);
    });
    loadCatalog();
};

/* =====================
LOAD
===================== */
const loadCatalog = async () => {
    loading.value = true;
    loadingEmployees.value = true;
    console.log(activeFilters.sinRol);

    try {
        const { data } = await axios.get('/employee/catalog/filter', {
            params: {
                branch_office_id: selectedBranchOffice.value.includes('all')
                    ? null
                    : selectedBranchOffice.value,

                date_from: activeFilters.dateFrom,
                date_to: activeFilters.dateTo,
                estado: activeFilters.estado,
                department_id: activeFilters.department,
                employees: activeFilters.employee,
                sinRol: activeFilters.sinRol
            }
        });

        employeesCatalog.value = data.data ?? [];
        employeesFilterBranchOffice.value = data.employees ?? [];
        console.log(employeesCatalog.value);
    } catch (error) {
        employeesCatalog.value = [];
        console.error(error);
    } finally {
        loading.value = false;
        loadingEmployees.value = false;
    }
};

/* =====================
APLICAR FILTROS
===================== */

const applyFilters = () => {
    const branchIds = selectedBranchOffice.value
        .filter(v => v !== 'all')
        .map(v => typeof v === 'object' ? Number(v.id) : Number(v)
    );

    const employeesIds = selectedEmployees.value.map(v =>
        typeof v === 'object' ? Number(v.id) : Number(v)
    );

    activeFilters.branchOffice = branchIds;
    activeFilters.dateFrom = valueFrom.value;
    activeFilters.dateTo = valueTo.value;
    activeFilters.estado = selectedEstados.value || [];
    activeFilters.department = selectedDepartment.value || [];
    activeFilters.employee = employeesIds;
    activeFilters.sinRol = checked.value;

    localStorage.setItem("selectedBranchOffice", JSON.stringify(branchIds));

    loadCatalog();
    otherFilterDialog.value = false;
};

/* =====================
LIMPIAR FILTROS
===================== */
const clearFilter = () => {
    initFilters();
    // selectedEstados.value = null;
    // selectedDepartment.value = null;
    selectedEstados.value = [];
    selectedDepartment.value = [];
    valueFrom.value = null;
    valueTo.value = null;
    selectedEmployees.value = [];
    checked.value = false;
    selected.value = [];



    activeFilters.dateFrom = null;
    activeFilters.dateTo = null;
    activeFilters.estado = [];
    activeFilters.department = [];
    // activeFilters.estado = null;
    // activeFilters.department = null;
    activeFilters.employee = [];
    activeFilters.sinRol = false;

    loadCatalog();
};

//Función para obtener el ícono del archivo según su extensión
const fileIcon = (file) => {
    const ext = file.name.split(".").pop()?.toLowerCase();
    if (ext === "csv") return "pi-file"; // ícono genérico de archivo
    if (["xls", "xlsx"].includes(ext)) return "pi-file-excel"; // ícono de Excel
    return "pi-file";
};

const resultDialog = ref(false);
const importResult = ref({
    insertados: 0,
    fallidos: 0,
    insertados_lista: [],
    fallidos_lista: [],
    errores: []
});

//Función para manejar la subida personalizada de archivos
const onCustomUploader = async ({ files }) => {
    files.forEach(file => {
        const reader = new FileReader();
        reader.onload = (e) => console.log("Contenido:", e.target.result);
        reader.readAsText(file);
    });

    submitted.value = true;
    const formData = new FormData();
    formData.append('employees_csv', files[0]);

    // 2. Iniciar Animación y Toast
    if (!visible.value) {
        toast.add({
            severity: "custom",
            summary: "Subiendo archivos.",
            group: "headless",
            styleClass: "backdrop-blur-lg rounded-2xl",
        });

        visible.value = true;
        progress.value = 0;

        if (interval.value) clearInterval(interval.value);

        interval.value = setInterval(() => {
            if (progress.value < 90) {
                progress.value += 10;
            }
        }, 500);
    }

    try {
        const response = await axios.post(route('catalog.import'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        const data = response.data;

        if (data.success) {
            progress.value = 100;

            setTimeout(() => {
                clearInterval(interval.value);
                toast.removeGroup("headless");

                importResult.value = data;
                resultDialog.value = true;
                openUploadDialog.value = false;

                visible.value = false;
                submitted.value = false;
                showSuccess();
            }, 500);
        }

    } catch (error) {
        clearInterval(interval.value);
        toast.removeGroup("headless");
        visible.value = false;
        submitted.value = false;
        console.error(error);

        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudo importar el archivo",
            life: 3000
        });
    }
};

const editarEmpleado = (id) => {
    router.visit(route('catalog.edit', id));
};

const calcularAntiguedad = (data) => {
    const fechaInicioStr = data.reentry_date ? data.reentry_date : data.entry_date;
    if (!fechaInicioStr) return 'N/A';
    const fechaFin = data.status === 'termination' && data.termination_date
        ? new Date(data.termination_date)
        : new Date();

    const inicio = new Date(fechaInicioStr);

    let anios = fechaFin.getFullYear() - inicio.getFullYear();
    const mes = fechaFin.getMonth() - inicio.getMonth();

    if (mes < 0 || (mes === 0 && fechaFin.getDate() < inicio.getDate())) {
        anios--;
    }

    if (anios < 1) {
        const meses = (fechaFin.getFullYear() - inicio.getFullYear()) * 12 + (fechaFin.getMonth() - inicio.getMonth());

        if (meses < 1) {
            const diferenciaMs = fechaFin - inicio;
            const dias = Math.floor(diferenciaMs / (1000 * 60 * 60 * 24));
            return `${dias} ${dias === 1 ? 'día' : 'días'}`;
        }

        return `${meses} ${meses === 1 ? 'mes' : 'meses'}`;
    }

    return `${anios} ${anios === 1 ? 'año' : 'años'}`;
};

const getStatusDetails = (status) => {
    switch (status) {
        case 'entry':
            return { label: 'Ingreso', severity: 'success' };
        case 'reentry':
            return { label: 'Reingreso', severity: 'info' };
        case 'termination':
            return { label: 'Baja', severity: 'danger' };
        case 'change':
            return { label: 'Traspaso', severity: 'warning' };
        default:
            return { label: status, severity: null };
    }
};

const onImageError = (event) => {
    const fallbackUrl = 'https://nominas.grupo-ortiz.site/Librerias/img/Fotos/usuario.jpg';

    if (event.target.src !== fallbackUrl) {
        event.target.src = fallbackUrl;
    } else {
        event.target.style.display = 'none';
    }
};

/* =====================
MOUNTED
===================== */
onMounted(() => {
    filteredBranchOffices.value = props.branchOffices ?? [];

    const raw = localStorage.getItem("selectedBranchOffice");
    let stored = [];

    try {
        const parsed = JSON.parse(raw);
        if (Array.isArray(parsed)) {
            stored = parsed.map(v => typeof v === 'object' ? Number(v.id) : Number(v));
        } else if (parsed && typeof parsed === 'object') {
            stored = [Number(parsed.id)];
        }
    } catch (e) {
        stored = [];
    }

    if (stored.length) {
        const allIds = props.branchOffices.map(b => b.id);

        const isAllSelected = stored.length === allIds.length;

        if (isAllSelected) {
            selectedBranchOffice.value = ['all', ...stored];
        } else {
            selectedBranchOffice.value = stored;
        }

        activeFilters.branchOffice = stored;
        loadCatalog();
        return;
    }

    loadCatalog([]);
});
</script>

<template>
    <AppLayout :title="'Catalogo de Empleados'">
        <!-- <pre>
           {{ employeesCatalog }}
        </pre> -->
        <ConfirmDialog position="topright"/>

        <Toast position="top-center" group="headless" @close="visible = false">
            <template #container="{ message, closeCallback }">
                <section
                    class="flex flex-col p-4 gap-4 w-full bg-gray-100 rounded-xl"
                >
                    <div class="flex items-center gap-5">
                        <i class="pi pi-cloud-upload text-2xl"></i>
                        <span class="font-bold text-base">{{
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
                        <label class="text-sm font-bold"
                            >{{ progress }}% subido</label
                        >
                    </div>
                </section>
            </template>
        </Toast>

        <Toast position="top-right" group="download" @close="visible = false">
            <template #container="{ message, closeCallback }">
                <section
                    class="flex flex-col p-4 gap-4 w-full rounded-xl bg-secondary shadow-lg"
                >
                    <div class="flex items-center gap-3">
                        <!-- Spinner -->
                        <i class="pi pi-spinner pi-spin text-xl"></i>
                        <span class="font-bold text-base">{{ message.summary }}</span>
                    </div>
                    <span>{{ message.detail }}</span>
                </section>
            </template>
        </Toast>

        <div class="card">
            <Toolbar>
                <template #start>
                    <Button
                        v-if="can('catalog.import')"
                        type="button"
                        icon="pi pi-download"
                        label="Importar"
                        severity="contrast"
                        class="mt-2 ml-2"
                        @click="openUploadDialog = true"
                    />
                    <Button
                        v-if="can('catalog.export-Empleados')"
                        type="button"
                        icon="pi pi-upload"
                        label="Exportar"
                        severity="secondary"
                        class="mt-2 ml-2"
                        @click="columnsDialog = true"
                    />
                </template>

                <template #end>
                    <div class="flex align-items-center gap-3 flex-wrap">

                        <!-- <Button
                            type="button"
                            label="Acciones Masivas"
                            class="min-w-12rem"
                            icon="pi pi-wrench"
                            @click="toggleAccionesMasivas($event)"
                            :disabled="selected.length === 0"
                        /> -->

                        <!-- <TreeSelect
                            v-model="selectedValue"
                            :key="treeKey"
                            filter
                            filterMode="strict"
                            :options="nodes"
                            placeholder="Acciones y Reportes"
                            class="min-w-14rem"
                        /> -->

                        <TreeSelect
                            v-model="selectedValue"
                            :key="treeKey"
                            filter
                            filterMode="strict"
                            :options="filteredNodes"
                            placeholder="Acciones y Reportes"
                            class="min-w-14rem"
                        />

                        <Button v-if="can('catalog.create')"
                            label="Crear"
                            icon="pi pi-plus-circle"
                            severity="success"
                            @click="crearEmpleado"
                        />

                    </div>
                </template>
            </Toolbar>
            <DataTable
                ref="dt"
                v-model:selection="selected"
                :value="employeesCatalog "
                dataKey="id"
                :paginator="true"
                :rows="50"  @select-all-change="onSelectAllChange"
                :selectAll="selected?.length === employeesCatalog?.length"
                scrollable
                scrollHeight="400px"
                tableStyle="min-width: 110rem"
                v-model:filters="filters"
                filterDisplay="menu"
                exportFilename="Catálog_empleado"
                :globalFilterFields="[
                    'id',
                    'full_name',
                    'surname',
                    'surname_mother',
                    'dni',
                    'health_id'
                ]"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} datos de empleados"
            >
                <template #header>
                    <div
                        class="flex flex-wrap gap-2 items-end justify-between mb-6"
                    >
                        <div>
                            <h4 class="m-0">Catálogo de Empleados</h4>
                            <Button
                                icon="pi pi-filter"
                                rounded
                                v-tooltip.top="'Mostrar mas filtros'"
                                @click="otherFilterDialog = true"
                            />
                            <Button
                                type="button"
                                icon="pi pi-filter-slash"
                                rounded
                                severity="secondary"
                                class="mt-5 ml-2 mr-2"
                                v-tooltip.top="'Limpiar filtros'"
                                @click="clearFilter()"
                            />
                            <Tag
                                v-if="selected.length > 0"
                                severity="info"
                                :value="'Seleccionados: ' + selected.length"
                            ></Tag>
                        </div>
                        <div class="flex">
                            <IconField>
                                <InputIcon>
                                    <i class="pi pi-search" />
                                </InputIcon>
                                <InputText
                                    v-model="filters['global'].value"
                                    placeholder="Buscar..."
                                />
                            </IconField>
                            <Button
                                type="button"
                                rounded
                                class="ml-2"
                                icon="pi pi-sliders-v"
                                severity="secondary"
                                @click="toggleMostrarColumnas($event)"
                            />
                            <Button
                                icon="pi pi-lock"
                                rounded
                                v-tooltip.top="'Alternar columnas fijas'"
                                class="ml-2"
                                severity="secondary"
                                @click="toggleFijarColumnas($event)"
                            />

                            <Popover ref="opMostrarColumnas">
                                <div class="flex flex-col gap-4">
                                    <div>
                                        <span class="font-medium block mb-2"
                                            >Mostrar/Ocultar Columnas</span
                                        >
                                        <ul
                                            class="list-none p-0 m-0 flex flex-col"
                                        >
                                            <li
                                                v-for="(
                                                    value, key
                                                ) in showColumns"
                                                :key="key"
                                                class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                                                :binary="true"
                                            >
                                                <Checkbox
                                                    v-model="showColumns[key]"
                                                    :inputId="key"
                                                    :binary="true"
                                                />
                                                <label
                                                    :for="key"
                                                    class="font-medium text-base"
                                                >
                                                    {{
                                                        key
                                                            .charAt(0)
                                                            .toUpperCase() +
                                                        key.slice(1)
                                                    }}
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </Popover>
                            <Popover ref="opFijarColumnas">
                                <div class="flex flex-col gap-4">
                                    <div>
                                        <span class="font-medium block mb-2"
                                            >Fijar Columnas</span
                                        >
                                        <ul
                                            class="list-none p-0 m-0 flex flex-col"
                                        >
                                            <li
                                                v-for="(
                                                    value, key
                                                ) in frozenColumns"
                                                :key="key"
                                                class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                                                :binary="true"
                                            >
                                                <Checkbox
                                                    v-model="frozenColumns[key]"
                                                    :inputId="key"
                                                    :binary="true"
                                                />
                                                <label
                                                    :for="key"
                                                    class="font-medium text-base"
                                                >
                                                    {{
                                                        key
                                                            .charAt(0)
                                                            .toUpperCase() +
                                                        key.slice(1)
                                                    }}
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </Popover>
                        </div>
                    </div>
                    <div class="mb-2">
                        <div class="flex flex-wrap gap-2 items-center mt-3">

                            <template v-if="selectedBranchOffice?.includes('all')">
                                <Chip label="Todas las plantas" />
                            </template>
                            <template v-else>
                                <Chip
                                    v-for="plant in selectedBranchOffice"
                                    :key="plant"
                                    @remove="removePlant(plant)"
                                    :label="getPlantName(plant)"
                                />
                            </template>

                            <template v-for="empId in activeFilters.employee" :key="empId">
                                <Chip
                                    :label="getEmployeeName(empId)"
                                    removable
                                    @remove="removeIndividualEmployee(empId)"
                                />
                            </template>

                            <Chip
                                v-for="est in selectedEstadosData"
                                :key="est.code"
                                :label="est.name"
                                removable
                                @remove="removeIndividualEstado(est.code)"
                            />

                            <Chip
                                v-for="dep in selectedDepartmentsData"
                                :key="dep.id"
                                :label="dep.name"
                                removable
                                @remove="removeIndividualDepartment(dep.id)"
                            />

                            <Chip
                                v-if="dateRangeLabel"
                                :label="dateRangeLabel"
                                removable
                                @remove="removeFilter('date')"
                            />

                            <Chip
                                v-if="sinRolLabel"
                                :label="sinRolLabel"
                                removable
                                @remove="removeFilter('sinRol')"
                            />
                        </div>
                    </div>
                </template>

                <Column
                    selectionMode="multiple"
                    style="width: 5rem"
                    :exportable="false"
                ></Column>
                <Column
                    :exportable="false"
                    :style="{
                        minWidth: '120px',
                        maxWidth: '120px',
                        width: '120px'
                    }"
                    header="Acciones"
                    :frozen="frozenColumns.acciones"
                >
                    <template #body="slotProps">
                        <Skeleton v-if="loading"></Skeleton>
                        <div class="flex gap-2 align-items-center justify-content-center white-space-nowrap" v-else>

                            <Button v-if="can('catalog.edit')"
                                icon="pi pi-pencil"
                                rounded
                                severity="warn"
                                v-tooltip.top="'Editar'"
                                @click="editarEmpleado(slotProps.data.id)"
                            />

                            <Button v-if="can('catalog.delete')"
                                icon="pi pi-trash"
                                rounded
                                severity="danger"
                                v-tooltip.top="'Eliminar'"
                                @click="confirmarCambioEstatus(slotProps.data.id)"
                            />

                        </div>
                    </template>
                </Column>
                <Column
                    field="id"
                    header="Clave Empleado"
                    :filter="true"
                    :frozen="frozenColumns.clave_empleado"
                    :style="{
                        width: '20rem',
                        display: showColumns.clave_empleado ? '' : 'none',
                    }"
                    :exportable="exportColumns.clave_empleado"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.id }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por ID"
                        />
                    </template>
                </Column>
                <Column
                    field="branch_office.code"
                    header="Planta"
                    sortable
                    :frozen="frozenColumns.planta"
                    :style="{
                        width: '20rem',
                        display: showColumns.planta ? '' : 'none',
                    }"
                    :exportable="exportColumns.planta"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.branch_office?.code }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Planta"
                        /> </template
                ></Column>
                <Column
                    field="foto"
                    header="Foto"
                    :filter="true"
                    :frozen="frozenColumns.foto"
                    :style="{
                        width: '20rem',
                        display: showColumns.foto ? '' : 'none',
                    }"
                    :exportable="exportColumns.foto"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <div v-else class="flex align-items-center gap-3">
                            <Avatar
                                shape="circle"
                                size="large"
                                class="shadow-1 mr-2"
                                style="background-color: #dee2e6; color: #495057"
                            >
                                <img
                                    :src="`https://nominas.grupo-ortiz.site/Librerias/img/Fotos/${data.id}.jpg`"
                                    @error="onImageError"
                                    style="object-fit: cover;"
                                />
                            </Avatar>
                        </div>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Foto"
                        />
                    </template>
                </Column>
                <Column
                    field="full_name"
                    header="Nombre Empleado"
                    sortable
                    :frozen="frozenColumns.nombre_empleado"
                    :style="{
                        width: '40rem',
                        display: showColumns.nombre_empleado ? '' : 'none',
                    }"
                    :exportable="exportColumns.nombre_empleado"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.full_name }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Nombre Completo"
                        /> </template
                ></Column>
                <Column
                    field="surname"
                    header="Apellido Paterno"
                    :frozen="frozenColumns.apellido_paterno"
                    sortable
                    :style="{
                        width: '20rem',
                        display: showColumns.apellido_paterno ? '' : 'none',
                    }"
                    :exportable="exportColumns.apellido_paterno"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.surname }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Apellido Paterno"
                        />
                    </template>
                </Column>
                <Column
                    field="mother_surname"
                    header="Apellido Materno"
                    sortable
                    :frozen="frozenColumns.apellido_materno"
                    :style="{
                        width: '20rem',
                        display: showColumns.apellido_materno ? '' : 'none',
                    }"
                    :exportable="exportColumns.apellido_materno"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.mother_surname }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Apellido Materno"
                        /> </template
                ></Column>
                <Column
                    field="name"
                    header="Nombre"
                    sortable
                    :frozen="frozenColumns.nombre"
                    :style="{
                        width: '20rem',
                        display: showColumns.nombre ? '' : 'none',
                    }"
                    :exportable="exportColumns.nombre"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.name }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Nombre"
                        /> </template
                ></Column>
                <Column
                    field="payment_data.daily_salary"
                    header="Salario Diario"
                    sortable
                    :frozen="frozenColumns.salario_diario"
                    :style="{
                        width: '20rem',
                        display: showColumns.salario_diario ? '' : 'none',
                    }"
                    :exportable="exportColumns.salario_diario"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ "$ " + (data.payment_data?.daily_salary || 0) }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Salario Diario"
                        /> </template
                ></Column>
                <Column
                    field="gender.name"
                    header="Genero"
                    sortable
                    :frozen="frozenColumns.genero"
                    :style="{
                        width: '20rem',
                        display: showColumns.genero ? '' : 'none',
                    }"
                    :exportable="exportColumns.genero"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.gender?.name }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Genero"
                        /> </template
                ></Column>
                <Column
                    field="birthday"
                    header="Fecha Nacimiento"
                    :filter="true"
                    :frozen="frozenColumns.fecha_nacimiento"
                    :style="{
                        width: '20rem',
                        display: showColumns.fecha_nacimiento ? '' : 'none',
                    }"
                    :exportable="exportColumns.fecha_nacimiento"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.birthday }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Fecha de Nacimiento"
                        />
                    </template>
                </Column>
                <Column
                    field="entry_date"
                    header="Fecha Ingreso"
                    sortable
                    :frozen="frozenColumns.fecha_ingreso"
                    :style="{
                        width: '20rem',
                        display: showColumns.fecha_ingreso ? '' : 'none',
                    }"
                    :exportable="exportColumns.fecha_ingreso"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.entry_date }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Fecha de Ingreso"
                        /> </template
                ></Column>
                <Column
                    field="department.name"
                    header="Departamento"
                    :frozen="frozenColumns.departamento"
                    sortable
                    :style="{
                        width: '20rem',
                        display: showColumns.departamento ? '' : 'none',
                    }"
                    :exportable="exportColumns.departamento"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.department?.name }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Departamento"
                        />
                    </template>
                </Column>
                <Column
                    field="status"
                    header="Estatus"
                    sortable
                    :frozen="frozenColumns.estatus"
                    :style="{
                        width: '20rem',
                        display: showColumns.estatus ? '' : 'none',
                    }"
                    :exportable="exportColumns.estatus"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <template v-else>
                            <Tag
                                :value="getStatusDetails(data.status).label"
                                :severity="getStatusDetails(data.status).severity"
                            />
                        </template>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Estatus"
                        /> </template
                ></Column>
                <Column
                    field="position.name"
                    header="Puesto"
                    sortable
                    :frozen="frozenColumns.puesto"
                    :style="{
                        width: '20rem',
                        display: showColumns.puesto ? '' : 'none',
                    }"
                    :exportable="exportColumns.puesto"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.position?.name }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Pueesto"
                        /> </template
                ></Column>
                <Column
                    field="antiguedad"
                    header="Antiguedad"
                    :filter="true"
                    :frozen="frozenColumns.antiguedad"
                    :style="{
                        width: '20rem',
                        display: showColumns.antiguedad ? '' : 'none',
                    }"
                    :exportable="exportColumns.antiguedad"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ calcularAntiguedad(data) }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Antiguedad"
                        />
                    </template>
                </Column>
                <!-- <Column
                    field="dias_vacaciones_disponibles"
                    header="Dias Vacaciones Disponibles"
                    sortable
                    :frozen="frozenColumns.dias_vacaciones_disponibles"
                    :style="{
                        width: '20rem',
                        display: showColumns.dias_vacaciones_disponibles ? '' : 'none',
                    }"
                    :exportable="exportColumns.dias_vacaciones_disponibles"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.day_vacations_sum_amount ?? 0 }} días</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Telefono"
                        /> </template
                ></Column> -->
                <Column
                    field="termination_date"
                    header="Fecha Baja"
                    :filter="true"
                    :frozen="frozenColumns.fecha_baja"
                    :style="{
                        width: '20rem',
                        display: showColumns.fecha_baja ? '' : 'none',
                    }"
                    :exportable="exportColumns.fecha_baja"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.termination_date }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Fecha de Baja"
                        />
                    </template>
                </Column>
                <Column
                    field="health_id"
                    header="IMSS"
                    sortable
                    :frozen="frozenColumns.imss"
                    :style="{
                        width: '20rem',
                        display: showColumns.imss ? '' : 'none',
                    }"
                    :exportable="exportColumns.imss"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.health_id }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por IMSS"
                        /> </template
                ></Column>
                <Column
                    field="state.name"
                    header="Entidad Nacimiento"
                    :frozen="frozenColumns.entidad_nacimiento"
                    sortable
                    :style="{
                        width: '20rem',
                        display: showColumns.entidad_nacimiento ? '' : 'none',
                    }"
                    :exportable="exportColumns.entidad_nacimiento"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.state?.name }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Estado de Nacimiento"
                        />
                    </template>
                </Column>
                <Column
                    field="dni"
                    header="CURP"
                    sortable
                    :frozen="frozenColumns.curp"
                    :style="{
                        width: '20rem',
                        display: showColumns.curp ? '' : 'none',
                    }"
                    :exportable="exportColumns.curp"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.dni }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por CURP"
                        /> </template
                ></Column>
            </DataTable>
            <Dialog
                v-model:visible="columnsDialog"
                :style="{ width: '600px' }"
                header="Selecciona los datos a exportar"
                :modal="true"
            >
                <div style="max-height:70vh; overflow-y:auto" class="flex flex-column gap-4">

                    <!-- Seleccionar todos -->
                    <div class="flex align-items-center gap-2">
                        <Checkbox v-model="selectAll" binary />
                        <label class="font-semibold">Seleccionar todos</label>
                    </div>


                    <!-- Grupos -->
                    <div
                        v-for="(group, groupKey) in exportFields"
                        :key="groupKey"
                    >
                        <h4 class="mt-3 mb-2 font-bold">
                            {{ group.label }}
                        </h4>
                        <Divider />

                        <div
                            v-for="(fieldData, fieldKey) in group.fields"
                            :key="fieldKey"
                            class="flex align-items-center gap-2 mb-2"
                        >
                            <Checkbox
                                v-model="fieldData.checked"
                                :binary="true"
                            />
                            <label>
                                {{ fieldData.label }}
                            </label>
                        </div>
                    </div>

                </div>

                <template #footer>
                    <Button
                        label="Cancelar"
                        icon="pi pi-times"
                        severity="secondary"
                        @click="columnsDialog = false"
                    />
                    <Button
                        label="Exportar"
                        icon="pi pi-save"
                        severity="success"
                        @click="saveColumns"
                    />
                </template>
            </Dialog>
            <Dialog
                v-model:visible="otherFilterDialog"
                :style="{ width: '450px' }"
                header="Seleccionar filtros adicionales"
                :modal="true"
            >
                <div class="flex flex-col gap-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Planta
                    </label>
                    <Multiselect
                        :modelValue="selectedBranchOffice"
                        @update:modelValue="updateBranches"
                        display="chip"
                        :options="branchOfficesWithAll"
                        optionLabel="code"
                        optionValue="id"
                        filter
                        placeholder="Selecciona una planta"
                        class="w-full"
                    >
                        <template #value="slotProps">
                            <span v-if="!slotProps.value || !slotProps.value.length">
                                Selecciona una planta
                            </span>

                            <span
                                v-else-if="slotProps.value.includes('all') || slotProps.value.length > 5"
                            >
                                {{
                                    slotProps.value.includes('all')
                                        ? props.branchOffices.length
                                        : slotProps.value.length
                                }}
                                plantas seleccionadas
                            </span>
                        </template>
                    </Multiselect>
                </div>
                <div class="flex flex-col gap-5">
                    <FloatLabel variant="on" class="flex-1 mt-4">

                        <Multiselect
                            v-model="selectedEmployees"
                            :options="employeesFilterBranchOffice"
                            optionLabel="full_name"
                            optionValue="id"
                            :loading="loadingEmployees"
                            filter
                            :filterFields="['full_name', 'id']"
                            placeholder="Selecciona un empleado"
                            class="w-full"
                            display="chip"
                            :virtualScrollerOptions="{ itemSize: 30 }"
                        >
                            <template #loadingicon>
                                <i class="pi pi-spin pi-spinner text-primary" style="font-size: 1.2rem"></i>
                            </template>

                            <template #value="slotProps">
                                <span v-if="!slotProps.value || slotProps.value.length === 0">
                                    Selecciona un empleado
                                </span>

                                <span v-else-if="slotProps.value.length > 5">
                                    {{ slotProps.value.length }} empleados seleccionados
                                </span>


                            </template>

                            <template #option="{ option }">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-gray-700">({{ option.id }})</span>
                                    <span>{{ option.full_name }}</span>
                                </div>
                            </template>

                        </Multiselect>
                        <label>Empleados</label>
                    </FloatLabel>
                </div>
                <div class="flex gap-5">
                    <FloatLabel variant="on" class="flex-1 mt-4">
                        <DatePicker
                            v-model="valueFrom"
                            inputId="fecha_desde"
                            showIcon
                            iconDisplay="input"
                            variant="filled"
                            class="w-full"
                        />
                        <label for="fecha_desde">Fecha de alta desde</label>
                    </FloatLabel>

                    <FloatLabel variant="on" class="flex-1 mt-4">
                        <DatePicker
                            v-model="valueTo"
                            inputId="fecha_hasta"
                            showIcon
                            iconDisplay="input"
                            variant="filled"
                            class="w-full"
                        />
                        <label for="fecha_hasta">Fecha de alta hasta</label>
                    </FloatLabel>
                </div>

                <div class="flex gap-5">
                    <FloatLabel variant="on" class="flex-1 mt-4">
                        <Multiselect
                            v-model="selectedEstados"
                            :options="estados"
                            optionLabel="name"
                            optionValue="code"
                            inputId="estado"
                            filter
                            display="chip"
                            class="w-full"
                            placeholder="Selecciona estados"
                        >
                            <template #value="slotProps">
                                <span v-if="!slotProps.value || slotProps.value.length === 0">
                                    Selecciona estado(s)
                                </span>
                                <span v-else-if="slotProps.value.length > 2">
                                    {{ slotProps.value.length }} estados seleccionados
                                </span>
                            </template>
                        </Multiselect>
                        <label for="estado">Estado</label>
                    </FloatLabel>

                    <FloatLabel variant="on" class="flex-1 mt-4">
                        <Multiselect
                            v-model="selectedDepartment"
                            :options="departments"
                            optionLabel="name"
                            optionValue="id"
                            inputId="department"
                            filter
                            display="chip"
                            class="w-full"
                            placeholder="Selecciona departamentos"
                        >
                            <template #value="slotProps">
                                <span v-if="!slotProps.value || slotProps.value.length === 0">
                                    Selecciona departamento(s)
                                </span>
                                <span v-else-if="slotProps.value.length > 2">
                                    {{ slotProps.value.length }} depts. seleccionados
                                </span>
                            </template>
                        </Multiselect>
                        <label for="department">Departamento</label>
                    </FloatLabel>
                </div>
                <div class="flex gap-5">
                    <ToggleSwitch v-model="checked" class="mt-4">
                        <template #handle="{ checked }">
                            <i :class="['!text-xs pi', { 'pi-check': checked, 'pi-times': !checked }]" />
                        </template>
                    </ToggleSwitch>
                    <label for="sinRol" class="mt-4">
                        Sin rol de turno
                    </label>
                </div>

                <template #footer>
                    <Button
                        label="Cancelar"
                        icon="pi pi-times"
                        severity="danger"
                        @click="otherFilterDialog = false"
                    />
                    <Button
                        label="Filtrar"
                        icon="pi pi-filter"
                        severity="warn"
                        @click="applyFilters"
                        :loading="submitted"
                    />
                </template>
            </Dialog>
            <Dialog
                v-model:visible="openUploadDialog"
                :style="{ width: '450px' }"
                header="Subir Archivo "
                :modal="true"
            >
                <div class="flex flex-col gap-6">
                    <FileUpload
                        name="files[]"
                        accept=".csv,.xlsx,.xls"
                        :maxFileSize="1000000"
                        :customUpload="true"
                        :multiple="false"
                        :maxFiles="1"
                        :fileLimit="1"
                        @uploader="onCustomUploader"
                    >
                        <template
                            #content="{
                                files,
                                uploadedFiles,
                                removeUploadedFileCallback,
                                removeFileCallback,
                            }"
                        >
                            <div v-if="files.length">
                                <h5>Pendientes</h5>
                                <div class="flex flex-wrap gap-4">
                                    <div
                                        v-for="(file, i) in files"
                                        :key="file.name + file.size"
                                        class="p-4 border rounded-md flex flex-col items-center gap-2 w-52"
                                    >
                                        <!-- Ícono en lugar de imagen -->
                                        <i
                                            :class="fileIcon(file)"
                                            class="pi text-5xl"
                                        />
                                        <span
                                            class="font-semibold text-ellipsis max-w-44 whitespace-nowrap overflow-hidden"
                                        >
                                            {{ file.name }}
                                        </span>
                                        <small
                                            >{{
                                                (file.size / 1024).toFixed(0)
                                            }}
                                            KB</small
                                        >

                                        <Button
                                            icon="pi pi-times"
                                            @click="removeFileCallback(i)"
                                            rounded
                                            severity="danger"
                                            variant="outlined"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div v-if="uploadedFiles.length" class="mt-6">
                                <h5>Completados</h5>
                                <div class="flex flex-wrap gap-4">
                                    <div
                                        v-for="(file, i) in uploadedFiles"
                                        :key="file.name + file.size"
                                        class="p-4 border rounded-md flex flex-col items-center gap-2 w-52"
                                    >
                                        <i
                                            :class="fileIcon(file)"
                                            class="pi text-5xl"
                                        />
                                        <span
                                            class="font-semibold text-ellipsis max-w-44 whitespace-nowrap overflow-hidden"
                                        >
                                            {{ file.name }}
                                        </span>
                                        <Badge value="OK" severity="success" />
                                        <Button
                                            icon="pi pi-times"
                                            @click="
                                                removeUploadedFileCallback(i)
                                            "
                                            rounded
                                            severity="danger"
                                            variant="outlined"
                                        />
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- <template #empty>
                            <span>Arrastra y suelta tus CSV o Excel aquí.</span>
                        </template> -->
                    </FileUpload>
                </div>

                <!-- <template #footer>
                    <Button
                        label="Cancelar"
                        icon="pi pi-times"
                        text
                        @click="openUploadDialog = false"
                    />
                    <Button
                        label="Guardar"
                        icon="pi pi-check"
                        @click="saveActivity"
                        :loading="submitted"
                    />
                </template> -->
            </Dialog>
            <Dialog
                v-model:visible="showDialogCreateEmployee"
                modal
                header="Validar empleado"
                :style="{ width: '450px' }"
                :closable="!loadingRedirect"
            >
                <div
                    v-if="loadingRedirect"
                    class="flex flex-column justify-content-center align-items-center gap-3 text-center"
                    style="height: 80px;"
                >
                    <!-- <ProgressSpinner style="width: 30px; height: 30px" /> -->
                    <!-- <i class="pi pi-spinner pi-spin" style="font-size: 1.5rem"></i> -->
                    <ProgressSpinner style="width:22px;height:22px" strokeWidth="6" />
                    <span class="text-sm text-500">
                        Preparando reingreso...
                    </span>
                </div>

                <div v-else class="p-fluid">
                    <div class="flex gap-2 p-3 border-round">
                        <i class="pi pi-info-circle text-blue-500 mt-1"></i>

                        <span class="text-base">
                            Antes de crear un nuevo empleado, busca por nombre o número de empleado (si lo recuerda),
                            para verificar si ya había trabajado anteriormente en la empresa.
                            Si ya había trabajado en la empresa selecciónalo y te redirigirá a reingresarlo.
                        </span>
                    </div>

                    <div class="field">
                        <!-- <label>Nombre del empleado</label> -->

                        <Select
                            v-model="empleadoSeleccionado"
                            :options="props.employees"
                            optionLabel="full_name"
                            filter
                            :filterFields="['id', 'full_name']"
                            placeholder="Buscar empleado..."
                            class="w-full"
                            @change="employeeRentry"
                            :virtualScrollerOptions="{ itemSize: 30 }"
                        >

                            <template #value="slotProps">
                                <span v-if="slotProps.value">
                                    {{ slotProps.value.id }} - {{ slotProps.value.full_name }}
                                </span>
                                <span v-else>
                                    {{ slotProps.placeholder }}
                                </span>
                            </template>

                            <template #option="slotProps">
                                <span>
                                    ({{ slotProps.option.id }}) - {{ slotProps.option.full_name }}
                                </span>
                            </template>
                        </Select>
                    </div>

                    <!-- BOTONES -->
                    <div class="flex justify-content-end gap-2 mt-4">

                        <Button
                            label="Cancelar"
                            icon="pi pi-times"
                            severity="secondary"
                            outlined
                            @click="showDialogCreateEmployee = false"
                            :disabled="loadingRedirect"
                        />

                        <Button
                            label="No existe, continuar"
                            icon="pi pi-arrow-right"
                            severity="success"
                            @click="continuarRegistro"
                            :disabled="loadingRedirect"
                        />

                    </div>

                </div>
            </Dialog>
            <Dialog
                v-model:visible="approveDialog"
                :style="{ width: '450px' }"
                header="Confirmar Eliminación"
                :modal="true"
            >
                <div class="border-l-4 p-4 rounded bg-red-50 border-red-500">
                    <div class="flex items-center">
                        <i class="pi pi-exclamation-triangle text-red-600 text-2xl mr-3"></i>
                        <div>
                            <h3 class="font-bold text-red-800">
                                ¿Estás seguro de eliminar este empleado?
                            </h3>
                            <p class="text-sm text-red-700">
                                Esta acción no se puede deshacer.
                            </p>
                        </div>
                    </div>
                </div>

                <template #footer>
                    <Button
                        label="Cancelar"
                        icon="pi pi-times"
                        text
                        severity="secondary"
                        :disabled="loadingEliminar"
                        @click="approveDialog = false"
                    />

                    <Button
                        label="Eliminar"
                        icon="pi pi-trash"
                        severity="danger"
                        @click="eliminarEmpleado"
                        :loading="loadingEliminar"
                        :disabled="loadingEliminar"
                    />
                </template>
            </Dialog>
            <Dialog
                v-model:visible="edicion_masiva"
                modal header="Ediciones Masivas"
                :style="{ width: '50vw' }"
                :breakpoints="{ '1199px': '75vw', '575px': '90vw' }
            ">
                <FileUpload
                    name="demo[]"
                    :multiple="false"
                    accept=".csv"
                    :maxFileSize="1000000"
                    @select="onSelectedFiles"
                    customUpload
                >
                    <template #header="{ chooseCallback, uploadCallback, clearCallback, files }">
                        <div class="flex flex-wrap justify-between items-center flex-1 gap-4">
                            <div class="flex gap-2">
                                <Button @click="chooseCallback()" icon="pi pi-file-excel" rounded variant="outlined" severity="success"></Button>
                                <Button @click="uploadCallback()" icon="pi pi-cloud-upload" rounded variant="outlined" severity="info" :disabled="!files || files.length === 0"></Button>
                                <Button @click="clearCallback(); progresoManual = 0" icon="pi pi-times" rounded variant="outlined" severity="danger" :disabled="!files || files.length === 0"></Button>
                            </div>

                            <div class="flex items-center gap-3 md:ml-auto w-full md:w-20rem">
                                <ProgressBar :value="progresoManual" :showValue="true" class="h-2 w-full">
                                </ProgressBar>
                            </div>
                        </div>
                    </template>

                    <template #content="{ files, messages }">
                        <div v-if="messages.length > 0" class="mb-4">
                            <Message v-for="message of messages" :key="message" severity="error" class="w-full">
                                {{ message.replace('Invalid file type, allowed file types:', 'Archivo no permitido, solo se acepta:') }}
                            </Message>
                        </div>

                        <div v-if="files.length > 0" class="pt-4">
                            <div class="flex flex-col border border-surface rounded-border p-4 items-center gap-2">
                                <i class="pi pi-file-excel text-4xl text-green-500" />
                                <span class="font-bold text-center">{{ files[0].name }}</span>
                                <Badge value="CSV UTF-8" severity="info" />
                            </div>
                        </div>
                    </template>

                    <template #empty>
                        <div class="flex items-center justify-center flex-col py-4">
                            <i class="pi pi-upload !border-2 !rounded-full !p-8 !text-4xl !text-muted-color" />
                            <p class="mt-6 mb-0">Seleccione un archivo CSV (UTF-8) para la edición masiva.</p>
                        </div>
                    </template>
                </FileUpload>
            </Dialog>
            <Dialog
                v-model:visible="resultDialog"
                header="Resultado de Importación"
                :style="{ width: '500px' }"
                modal
            >

                <div class="mb-3 flex gap-2 align-items-center">
                    <b>Insertados:</b>
                    <Tag severity="success" :value="importResult.insertados" />
                </div>
                <div v-if="importResult.insertados_lista.length">
                    <h4>Empleados Insertados</h4>
                    <ul>
                        <li v-for="(emp, index) in importResult.insertados_lista" :key="index">
                            <Tag severity="success" :value="emp.id" class="mr-2" />
                            {{ emp.nombre }} {{ emp.apellido_paterno }} {{ emp.apellido_materno }}
                        </li>
                    </ul>
                </div>

                <Divider class="my-4" />

                <div class="mb-3 flex gap-2 align-items-center">
                    <b>Fallidos:</b>
                    <Tag severity="danger" :value="importResult.fallidos" />
                </div>
                <div v-if="importResult.fallidos_lista.length">
                    <h4>Empleados Fallidos</h4>
                    <ul>
                        <li v-for="(emp, index) in importResult.fallidos_lista" :key="index">
                            <Tag severity="danger" :value="emp.id" class="mr-2" />
                            {{ emp.nombre }} {{ emp.apellido_paterno }} {{ emp.apellido_materno }} - {{ emp.error }}
                        </li>
                    </ul>

                </div>

                <Divider />

                <div v-if="importResult.errores.length">

                    <h4>Errores</h4>

                    <ul>
                        <li v-for="(error, index) in importResult.errores" :key="index">
                            Fila {{ error.fila }} - {{ error.empleado }} :
                            {{ error.error }}
                        </li>
                    </ul>

                    <p class="mt-3 text-sm text-gray-500">
                        Favor de revisar la plantilla o descárgala en la sección
                        <b>Acciones y Reportes</b> para verificar cómo deben subirse los datos.
                    </p>

                </div>

            </Dialog>
        </div>
    </AppLayout>
</template>
