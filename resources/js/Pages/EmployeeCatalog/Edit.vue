<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useForm, router } from "@inertiajs/vue3";
import { computed, onMounted, ref, watch } from 'vue';
import { useToastService } from "../../Stores/toastService";
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";

const confirm = useConfirm();
const toast = useToast();
const { showError, showSuccess, showValidationError } = useToastService();

const props = defineProps({
    employee: Object,
    states: Array,
    cities: Array,
    countries: Array,
    tax_systems: Array,
    banks: Array,
    payment_methods: Array,
    positions: Array,
    branch_offices: Array,
    branch_offices_locations: Array,
    departments: Array,
    benefits: Array,
    clasificacions: Array,
    shift_roles: Array,
    genders: Array,
    reasons: Array,
});

const loadingLocations = ref(false);
const locations = ref([]);
const employees = ref([]);
const loadingUsers = ref(false);
const users = ref([]);
const loading = ref(false);
const imageError = ref(false);
const loadingVer = ref(false);
const loadingCatalog = ref(false);

// Campos del formulario de recontratación
const selectedBranchOffice = ref(null);
const valueFrom = ref(null);
const selectedStatusRentry = ref(null);
const days_duration_rentry = ref(null);
const reentryDialog = ref(false);
const submitted = ref(false);

// Campos del formulario de traspaso
const selectedBranchOfficeTransfer = ref(null);
const dateTerminateTransfer = ref(null);
const dateEntryTransfer = ref(null);
const selectedStatusTransfer = ref(null);
const transfer_observations = ref(null);
const efficiency = ref(null);
const transferDialog = ref(false);
const efficiency_month = ref(null);
const submittedTransfer = ref(false);

// Campos del formulario de baja
const dateTerminated = ref(null);
const selectedStatusTerminated = ref(null);
const terminatedDialog = ref(false);
const submittedTerminated = ref(false);
const termination_observations = ref(null);
const showObservationsField = ref(false);
const is_rehireable = ref({ name: 'Sí', value: true });


const rehireables = ref([
    { name: 'Sí', value: true },
    { name: 'No', value: false }
]);

const onAccountInput = (val, field) => {
    const clean = (val || '').replace(/\D/g, '')
    form[field] = clean
}


// FORMULARIO INERTIA
const form = useForm({
    // Datos personales
    employee_id: null,
    name: null,
    surname: null,
    mother_surname: null,
    personal_phone: null,
    birthday: null,
    gender_id: null,
    birth_state_id: null,
    dni: null,
    health_id: null,
    email: null,
    // Domicilio
    street: null,
    external_number: null,
    internal_number: null,
    location_id: null, // Este recibirá el ID al final
    location_data: null, // Para el Autocomplete de Colonia
    city_id: null,
    state_id: null,
    country_id: null,
    postal_code: null,
    // Datos fiscales
    tax_id: null,
    postal_code_tax: null,
    tax_system_id: null,
    // Datos de pago
    account_number: null,
    account_card: null,
    account_code: null,
    daily_salary: null,
    salary: null,
    bank_id: null,
    payment_method_id: null,
    weekly_salary: null,
    // Datos laborales
    entry_date: null,
    termination_date: null,
    status: null,
    position_id: null,
    branch_office_id: null,
    branch_office_location_id: null,
    department_id: null,
    company_phone: null,
    benefits: [],
    employee_parent_id: [], // Array de IDs para el backend
    parent_data: [], // Para el Autocomplete múltiple
    employee_parent_email: null,
    classification_id: null,
    // Información adicional
    user_id: null,
    user_data: null, // Para el Autocomplete de Usuario
    additional_phone: null,
    additional_email: null,
    profession: null,
    level_education: null,
    civil_state: null,
    unit_health: null,
    emergency_name: null,
    emergency_phone: null,
    employee_type: null,
    job_type: null,
    shift_role_id: null,
    medical_unit: null,
    pension_discount: null,
    employer_registration: null,
    type_income: null,
    regime_key: null,
    contribution_basis: null,
    type_contract: null,
    ruta_plantilla: null,
    clave_regimen_cntrato: null,
    holiday_table: null,
    tabla_salario_diario: null,
    days_duration: null,
    beneficiary_name: null,
    porcentaje_beneficiario: null,
    beneficiary_kinship: null,
    nombre_beneficiario2: null,
    porcentaje_beneficiario2: null,
    parentesco_beneficiario2: null,
    external_id: null,
});


let isInitializing = true;
const filteredCities = computed(() => {
    if (!form.state_id) {
        return [];
    }
    return props.cities.filter(city => city.state_id === form.state_id);
});

watch(() => form.state_id, () => {
    if (isInitializing) return;

    form.city_id = null;
});


const shouldShowEfficiencyFields = computed(() => {
    const rawDate = props.employee.employment_data.reentry_date || props.employee.employment_data.entry_date;
    if (!rawDate) return false;

    const baseDate = new Date(rawDate);
    const today = new Date();

    let firstDay7 = new Date(baseDate.getFullYear(), baseDate.getMonth(), 7);

    if (baseDate.getDate() > 7) {
        firstDay7.setMonth(firstDay7.getMonth() + 1);
    }
    const thirdCutoffDate = new Date(firstDay7);
    thirdCutoffDate.setMonth(thirdCutoffDate.getMonth() + 3);

    const gracePeriodStart = new Date(thirdCutoffDate);
    gracePeriodStart.setMonth(gracePeriodStart.getMonth() - 1);
    gracePeriodStart.setDate(29);

    const isPastThirdMonth = today >= thirdCutoffDate;
    const isWithinGracePeriod = today >= gracePeriodStart && today < thirdCutoffDate;

    return isPastThirdMonth || isWithinGracePeriod;
});

const submitReentry = () => {
    let missingFields = [];

    if (!selectedBranchOffice.value) missingFields.push("Planta");
    if (!valueFrom.value) missingFields.push("Fecha de reingreso");
    if (!selectedStatusRentry.value) missingFields.push("Razón de reingreso");
    if (!days_duration_rentry.value) missingFields.push("Dias de duración");

    if (missingFields.length > 0) {
        const mensaje = "Faltan los siguientes campos: " + missingFields.join(", ");

        showValidationError(mensaje);

        console.warn(mensaje);
        return;
    }

    submitted.value = true;

    const formattedDate = valueFrom.value instanceof Date
        ? valueFrom.value.toISOString().split('T')[0]
        : valueFrom.value;

    const payload = {
        employee_id: form.employee_id,
        branch_office_id: selectedBranchOffice.value,
        reentry_date: formattedDate,
        reason_id: selectedStatusRentry.value,
        duration_days: days_duration_rentry.value,
    };

    console.log("🚀 Enviando Recontratación:", payload);

    router.post(route('catalog.reentry'), payload, {
        onSuccess: () => {
            reentryDialog.value = false;
            resetReentryFields();

            router.reload({
                only: ['employee'],
                onFinish: () => {
                    showSuccess();
                }
            });
        },
        onError: (errors) => {
            submitted.value = false;
            showError();
        }
    });
};

// Función para limpiar los campos después de guardar
const resetReentryFields = () => {
    selectedBranchOffice.value = null;
    valueFrom.value = null;
    selectedStatusRentry.value = null;
    days_duration_rentry.value = null;
};

const formatMonthOnly = (d) => {
    if (!(d instanceof Date)) return d;
    const month = (d.getMonth() + 1).toString().padStart(2, '0');
    const year = d.getFullYear();
    return `${month}-${year}`;
};

const submitTrasfer = () => {
    let missingFields = [];

    if (!dateTerminateTransfer.value) missingFields.push("Fecha de baja");
    if (!selectedStatusTransfer.value) missingFields.push("Razón de cambio");
    if (!selectedBranchOfficeTransfer.value) missingFields.push("Planta");
    if (!dateEntryTransfer.value) missingFields.push("Fecha de alta");

    if (shouldShowEfficiencyFields.value) {
        if (!efficiency_month.value) missingFields.push("Mes de eficiencia");
        if (!efficiency.value) missingFields.push("Eficiencia (%)");
    }

    if (missingFields.length > 0) {
        const mensaje = "Faltan los siguientes campos: " + missingFields.join(", ");
        showValidationError(mensaje);
        return;
    }

    submittedTransfer.value = true;

    const formatDate = (d) => d instanceof Date ? d.toISOString().split('T')[0] : d;
    const formatMonth = (d) => {
        if (!(d instanceof Date)) return d;
        const m = d.getMonth() + 1;
        return `${d.getFullYear()}-${m < 10 ? '0' + m : m}-01`;
    };

    const payload = {
        employee_id: form.employee_id,
        termination_date: formatDate(dateTerminateTransfer.value),
        reason_id: selectedStatusTransfer.value,
        branch_office_id: selectedBranchOfficeTransfer.value,
        entry_date: formatDate(dateEntryTransfer.value),
        observations: transfer_observations.value,
        efficiency_month: shouldShowEfficiencyFields.value ? formatMonthOnly(efficiency_month.value) : null,
        efficiency_value: shouldShowEfficiencyFields.value ? efficiency.value : null,
    };

    console.log("📦 Enviando Traspaso:", payload);

    router.post(route('catalog.transfer'), payload, {
        onSuccess: () => {
            transferDialog.value = false;
            router.reload({
                only: ['employee'],
                onFinish: () => {
                    showSuccess();
                }
            });
        },
        onError: (errors) => {
            submittedTransfer.value = false;
            console.error(errors);
            showError();
        }
    });
};

const resetTransferFields = () => {
    dateTerminateTransfer.value = null;
    selectedStatusTransfer.value = null;
    selectedBranchOfficeTransfer.value = null;
    dateEntryTransfer.value = null;
    transfer_observations.value = null;
    efficiency_month.value = null;
    efficiency.value = null;
    submitted.value = false;
};

const confirmTermination = () => {
    if (!dateTerminated.value || !selectedStatusTerminated.value) {
        toast.add({ severity: 'warn', summary: 'Atención', detail: 'Completa la fecha y razón primero', life: 3000 });
        return;
    }

    confirm.require({
        message: '¿Está seguro de que desea dar de baja a este empleado? Esta acción cambiará su estado administrativo.',
        header: 'Confirmación de Baja',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'No, cancelar',
        acceptLabel: 'Sí, continuar',
        rejectClass: 'p-button-secondary p-button-text',
        acceptClass: 'p-button-danger',
        accept: () => {
            showObservationsField.value = true;
        }
    });
};

const resetTerminatedferFields = () => {
    dateTerminated.value = null;
    selectedStatusTerminated.value = null;
    termination_observations.value = null;
    is_rehireable.value = { name: 'Sí', value: true };
    showObservationsField.value = false;
    submittedTerminated.value = false;
};

const submitTerminated = () => {
    if (!termination_observations.value || termination_observations.value.trim() === '') {
        toast.add({ severity: 'error', summary: 'Faltan detalles', detail: 'Por favor escriba los motivos de la baja.', life: 3000 });
        return;
    }

    submittedTerminated.value = true;

    const payload = {
        employee_id: form.employee_id,
        termination_date: dateTerminated.value instanceof Date
            ? dateTerminated.value.toISOString().split('T')[0]
            : dateTerminated.value,
        reason_id: selectedStatusTerminated.value,
        observations: termination_observations.value,
        rehireable: is_rehireable.value === true || is_rehireable.value?.value === true ? 1 : 0,
        type: 'termination',
    };

    router.post(route('catalog.termination'), payload, {
        onSuccess: () => {
            terminatedDialog.value = false;
            resetTerminatedferFields();
            showSuccess();
            router.reload({ only: ['employee'] });
        },
        onError: () => {
            showError();
            submittedTerminated.value = false;
        }
    });
};

const imageUrl = computed(() =>
    form.employee_id
        ? `https://nominas.grupo-ortiz.site/Librerias/img/Fotos/${form.employee_id}.jpg`
        : null
)

watch(() => form.id, () => {
    imageError.value = false

})

const submitForm = () => {
    // console.log("prueba");
    form.clearErrors();
    let erroresVisibles = [];
    loading.value = true;

    const requeridos = {
        employee_id: "Clave de Empleado",
        name: "Nombre",
        surname: "Apellido Paterno",
        mother_surname: "Apellido Materno",
        personal_phone: "Teléfono Personal",
        birthday: "Fecha de Nacimiento",
        gender_id: "Género",
        birth_state_id: "Estado de Nacimiento",
        dni: "CURP",
        health_id: "NSS",
        email: "Correo Electrónico",
        street: "Calle",
        external_number: "Número exterior",
        state_id: "Estado",
        city_id: "Ciudad",
        // location_id: "Colonia",
        location_data: "Colonia",
        postal_code: "Código Postal",
        country_id: "País",
        tax_id: "RFC",
        postal_code_tax: "Código Postal Fiscal",
        tax_system_id: "Régimen Fiscal",
        account_number: "Número de Cuenta",
        account_code: "CLABE",
        daily_salary: "Salario Diario",
        salary: "Salario Mensual",
        bank_id: "Banco",
        payment_method_id: "Método de Pago",
        weekly_salary: "Salario Semanal",
        entry_date: "Fecha de Ingreso",
        status: "Estatus",
        position_id: "Puesto",
        branch_office_id: "Sucursal",
        department_id: "Departamento",
        benefits: "Prestaciones",
        employee_parent_id: "Jefe Inmediato",
        level_education: "Nivel de Educación",
        shift_role_id: "Turno",
        employer_registration: "Registro Patronal",
        days_duration: "Duración del Contrato",
        beneficiary_name: "Nombre del Beneficiario",
        porcentaje_beneficiario: "Porcentaje del Beneficiario",
        beneficiary_kinship: "Parentesco del Beneficiario",
    };

    for (const [key, label] of Object.entries(requeridos)) {
        const valor = form[key];

        if (valor === null || valor === "" || valor === undefined) {
            form.setError(key, "Este campo es obligatorio");
            erroresVisibles.push(label);
        }
        else if (key === 'email') {
            const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!pattern.test(valor)) {
                form.setError(key, "El formato del correo es inválido");
                erroresVisibles.push(`${label} (Formato incorrecto)`);
            }
        }
    }

    if (erroresVisibles.length > 0) {
        showValidationError(
            `Faltan los siguientes campos: ${erroresVisibles.join(", ")}`
        );
        loading.value = false;
        return;
    }

    form.transform((data) => {
        const isObject = typeof data.location_data === 'object' && data.location_data !== null;

        const payload = {
            ...data,
            location_id: isObject ? data.location_data.id : null,
            location_name: isObject ? data.location_data.name : data.location_data,

            employee_parent_id: Array.isArray(data.parent_data)
                ? data.parent_data.map(p => p.id)
                : [],
            user_id: (data.user_data && typeof data.user_data === 'object')
                ? data.user_data.id
                : data.user_data,

            location_data: undefined,
            parent_data: undefined,
            user_data: undefined,
        };

        console.log("🚀 Payload procesado:", payload);
        return payload;
    })
    .put(route("catalog.update", form.employee_id), {
        preserveScroll: true,
        onSuccess: (page) => {
            showSuccess();
            router.reload({ only: ['employee'] });
            console.log("¡Guardado exitoso!");
        },
        onError: (errors) => {
            loading.value = false;
            showError();
        },
        onFinish: () => {
            loading.value = false;
        },
    });

};

const loadEmployeeData = async () => {

    Object.keys(props.employee.personal_data).forEach(key => {
        if (form.hasOwnProperty(key)) {
            form[key] = props.employee.personal_data[key] === 'NULL'
                ? null
                : props.employee.personal_data[key];
        }
    });

    Object.keys(props.employee.payment_data).forEach(key => {
        if (form.hasOwnProperty(key)) {
            form[key] = props.employee.payment_data[key] === 'NULL'
                ? null
                : props.employee.payment_data[key];
        }
    });

    Object.keys(props.employee.fiscal_data).forEach(key => {
        if (form.hasOwnProperty(key)) {
            form[key] = props.employee.fiscal_data[key] === 'NULL'
                ? null
                : props.employee.fiscal_data[key];
        }
    });


    Object.keys(props.employee.employment_data).forEach(key => {

        if (key === 'benefits' && Array.isArray(props.employee.employment_data[key])) {
            form.benefits = props.employee.employment_data[key].map(item => item.benefit_id);
        }
        else if (key !== 'employee_parent_id') {
            if (form.hasOwnProperty(key)) {
                form[key] = props.employee.employment_data[key] === 'NULL'
                    ? null
                    : props.employee.employment_data[key];
            }
        }
    });

    const parentIds = props.employee.employment_data['employee_parent_id'];

    if (parentIds) {
        const uniqueIds = parentIds.split(',')
            .map(id => id.trim())
            .filter((val, i, self) => val !== "" && self.indexOf(val) === i);

        await loadParentEmployees(uniqueIds);
    }

    Object.keys(props.employee.address_data[0] || {}).forEach(key => {
        if (form.hasOwnProperty(key)) {
            form[key] = props.employee.address_data[0][key] === 'NULL'
                ? null
                : props.employee.address_data[0][key];
        }

    });

    console.log("Valores originales en additional_info:", props.employee.additional_info);

    // Object.keys(props.employee.additional_info || {}).forEach(key => {
    //     if (form.hasOwnProperty(key)) {
    //         const valor = props.employee.additional_info[key] === 'NULL' ? null : props.employee.additional_info[key];

    //         console.log(`Asignando a form.${key}:`, valor); // <--- Ver cada asignación

    //         form[key] = valor;
    //     } else {
    //         console.warn(`La llave "${key}" existe en additional_info pero NO en el objeto form.`);
    //     }
    // });

    // console.log("Objeto form actualizado:", form);

    Object.keys(props.employee.additional_info || {}).forEach(key => {
        if (form.hasOwnProperty(key)) {
            form[key] = props.employee.additional_info[key] === 'NULL'
                ? null
                : props.employee.additional_info[key];
        }
    });

    if (props.employee.address_data?.[0]?.location_id) {
        const targetId = props.employee.address_data[0].location_id;

        try {
            const res = await axios.get('/employee/catalog/locations-search', {
                params: { q: targetId }
            });

            if (res.data.length) {
                const location = res.data.find(l => String(l.id) === String(targetId)) || res.data[0];

                const formatted = {
                    ...location,
                    label: `(${location.id}) ${location.name}`
                };

                form.location_data = formatted;
                form.location_id = location.id;

                locations.value = [formatted];
            }
        } catch (e) {
            console.error('Error al recuperar colonia:', e);
        }
    }

    if (props.employee.additional_info?.user_id) {
        const id = props.employee.additional_info.user_id;

        const res = await axios.get('/users/users-search', {
            params: { q: id }
        });

        if (res.data.length) {
            const user = res.data[0];

            const formatted = {
                ...user,
                label: `(${user.id}) ${user.username}`
            };

            form.user_data = formatted;
            users.value = [formatted];
        }
    }
};

const loadParentEmployees = async (ids) => {
    try {
        const requests = ids.map(id =>
            axios.get('/employee/catalog/employee-search', {
                params: { q: id }
            })
        );
        const responses = await Promise.all(requests);
        const matchedEmployees = responses
            .map(res => res.data && res.data.length ? res.data[0] : null)
            .filter(Boolean);
        form.parent_data = [...matchedEmployees];
    } catch (e) {
        console.error('Error cargando jefes:', e);
        form.parent_data = [];
    }
};

const formatRFC = (event) => {
    form.tax_id = form.tax_id.toUpperCase();
    validateRFC();
};

const validateRFC = () => {
    if (!form.tax_id) return;
    form.tax_id = form.tax_id.toUpperCase();
    const rfcRegex =
        /^([A-ZÑ&]{3,4}) ?(?:- ?)?(\d{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12]\d|3[01])) ?(?:- ?)?([A-Z\d]{2})([A\d])$/;

    if (!rfcRegex.test(form.tax_id)) {
        form.setError('tax_id', 'Formato de RFC inválido');
    } else {
        form.clearErrors('tax_id');
    }
};

const validateNSS = () => {
    if (!form.health_id) return;
    form.health_id = form.health_id.replace(/\D/g, "");
    const nssRegex = /^\d{11}$/;
    if (!nssRegex.test(form.health_id)) {
        form.setError('health_id', 'El NSS debe tener 11 dígitos numéricos');
    } else {
        form.clearErrors('health_id');
    }
};

const validateCURP = () => {
    form.curp = form.curp.toUpperCase().trim();

    const generoDetectado = sexoDesdeCurp(form.curp);

    if (generoDetectado) {
        form.gender = generoDetectado;
    }

    if (!form.curp) {
        errors.curp = "La CURP es requerida";
        return;
    }
    const curpRegex = /^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]{2}$/;
    if (!curpRegex.test(form.curp)) {
        errors.curp = "Formato de CURP inválido";
    } else {
        errors.curp = null;
    }
};

// const isEmailValid = computed(() => {
//     const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
//     return pattern.test(form.email);
// });

const status = [
    { name: "Alta", id: "entry" },
    { name: "Baja", id: "termination" },
    { name: "Reingreso", id: "reentry" },
    { name: "Traspaso", id: "change" },
];

const reentryReasons = computed(() => {
    return props.reasons
        ? props.reasons.filter(r => r.type === 'ALTA')
        : [];
});

const exitReasons = computed(() => {
    return props.reasons
        ? props.reasons.filter(r => r.type === 'BAJA')
        : [];
});

const employmentStatus = computed(() =>
    props.employee?.employment_data?.status
);

onMounted(async () => {
    console.log(props.employee);
    if (!props.employee) return;

    await loadEmployeeData();

    isInitializing = false;
});


const searchLocations = async (event) => {
    const term = event.query;
    if (!term || term.length < 3) return;

    loadingLocations.value = true;

    try {
        const res = await axios.get('/employee/catalog/locations-search', {
            params: { q: term }
        });

        locations.value = res.data.map(l => ({
            ...l,
            label: `(${l.id}) ${l.name}`
        }));

    } catch (e) {
        console.error(e);
    } finally {
        loadingLocations.value = false;
    }
};

watch(() => form.location_data, (val) => {

    if (val && val.id) {
        form.location_id = val.id
    } else {
        form.location_id = null
    }

})

const searchEmployee = async (event) => {
    try {
        const term = event.query;

        if (!term || term.length < 3) {
            employees.value = [];
            return;
        }

        const res = await axios.get('/employee/catalog/employee-search', {
            params: {
                q: term
            }
        });

        employees.value = res.data;

    } catch (e) {
        console.error(e);
        employees.value = [];
    }
};

const searchUsers = async (event) => {

    const term = event.query;
    if (!term || term.length < 3) return;

    loadingUsers.value = true;
    try {
        const res = await axios.get('/users/users-search', {
            params: {
                q: term,
            }
        });
        // users.value = res.data;

        users.value = res.data.map(u => ({
            ...u,
            label: `(${u.id}) ${u.username}`
        }));
    } catch (e) {
        console.error(e);
    } finally {
        loadingUsers.value = false;
    }
};

const goToDetails = () => {
    if (!form.employee_id) {
        showValidationError("No hay id del empleado seleccionado.");
        return;
    }

    loadingVer.value = true;

    router.get(`/employee/${form.employee_id}/search-employee`, {}, {
        onFinish: () => {
            loadingVer.value = false;
        }
    });
};

const goToCatalog = () => {
    loadingCatalog.value = true

    router.get(route('catalog'), {}, {
        onFinish: () => {
            loadingCatalog.value = false
        }
    })
}

</script>

<template>
    <AppLayout title="Actualizar Empleado">
        <!-- <pre>
            {{ props.employee }}
        </pre> -->
        <form @submit.prevent="submitForm" novalidate>
            <div class="grid grid-cols-1 md:grid-cols-1">
                <Card>
                    <template #title>
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <h3>Actualizar Empleado</h3>
                            </div>
                        </div>
                    </template>
                    <template #content>
                        <div class="grid">
                            <div class="col-12">
                                <div class="shadow-none border-none mt-5">
                                    <div class="sticky-header mb-4">
                                        <Card class="shadow-3 border-round-xl surface-card">

                                            <template #content>
                                                <div
                                                    class="flex align-items-center justify-content-between flex-wrap gap-3">

                                                    <div class="flex align-items-center gap-4">
                                                        <Avatar
                                                            shape="circle"
                                                            size="xlarge"
                                                            class="shadow-3 flex align-items-center justify-content-center bg-primary text-white"
                                                            style="width: 10rem; height: 10rem"
                                                        >
                                                            <template v-if="!imageError">
                                                                <img
                                                                    :src="imageUrl"
                                                                    @error="imageError = true"
                                                                    style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;"
                                                                />
                                                            </template>

                                                            <template v-else>
                                                                <i class="pi pi-user" style="font-size: 3rem"></i>
                                                            </template>
                                                        </Avatar>

                                                        <div class="field break-inside-avoid">
                                                            <label for="employee_id" class="block mb-2 font-semibold text-900">
                                                                Número de Nómina
                                                            </label>

                                                            <InputGroup class="w-full">
                                                                <InputGroupAddon>
                                                                    <i class="pi pi-id-card"></i>
                                                                </InputGroupAddon>
                                                                <InputText v-model="form.employee_id" class="w-full" readonly
                                                                :class="{ 'p-invalid': form.errors.employee_id }"/>
                                                            </InputGroup>
                                                            <small class="p-error" v-if="form.errors.employee_id">{{
                                                                form.errors.employee_id
                                                                }}</small>
                                                        </div>
                                                    </div>

                                                    <div class="flex gap-2 flex-wrap justify-content-center">

                                                        <!-- Botón Regresar -->
                                                        <Button
                                                            icon="pi pi-arrow-left"
                                                            :label="loadingCatalog ? 'Redireccionando...' : 'Catálogo'"
                                                            severity="secondary"
                                                            :loading="loadingCatalog"
                                                            :disabled="loadingCatalog"
                                                            @click="goToCatalog"
                                                        />

                                                        <Button
                                                            icon="pi pi-eye"
                                                            :label="loadingVer ? 'Redireccionando...' : 'Detalles de Empleado'"
                                                            severity="info"
                                                            :loading="loadingVer"
                                                            @click="goToDetails"
                                                        />

                                                        <!-- Si está en termination -->
                                                        <Button
                                                            v-if="employmentStatus === 'termination'"
                                                            icon="pi pi-refresh"
                                                            label="Recontratar"
                                                            severity="warn"
                                                            @click="reentryDialog = true"
                                                        />

                                                        <!-- Si NO está en termination -->
                                                        <template v-else>
                                                            <Button
                                                                type="submit"
                                                                icon="pi pi-save"
                                                                :label="
                                                                    loading ? 'Guardando...' : 'Guardar Cambios'
                                                                "
                                                                severity="success"
                                                                :loading="loading"
                                                                :disabled="loading"
                                                            />

                                                            <Button
                                                                icon="pi pi-send"
                                                                label="Traspaso"
                                                                severity="help"
                                                                @click="transferDialog = true"
                                                            />
                                                            <Button
                                                                icon="pi pi-times"
                                                                label="Dar Baja"
                                                                severity="danger"
                                                                @click="terminatedDialog = true"
                                                            />
                                                        </template>

                                                    </div>

                                                </div>
                                            </template>

                                        </Card>
                                    </div>
                                    <Card class="shadow-none border-none mt-5">
                                        <template #title><i class="pi pi-user text-orange-500"></i>
                                            Datos Personales
                                        </template>
                                        <template #content>
                                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                                <div class="field break-inside-avoid">
                                                    <label for="name" class="block mb-2 font-semibold text-900">
                                                        Nombre
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-user"></i>
                                                        </InputGroupAddon>
                                                        <InputText v-model="form.name" class="w-full"
                                                        :class="{ 'p-invalid': form.errors.name }"/>
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.name">{{
                                                        form.errors.name
                                                        }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="surname" class="block mb-2 font-semibold text-900">
                                                        Apellido Paterno
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-user"></i>
                                                        </InputGroupAddon>
                                                        <InputText v-model="form.surname" class="w-full"
                                                        :class="{ 'p-invalid': form.errors.surname }"/>
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.surname">{{
                                                        form.errors.surname
                                                        }}</small>
                                                </div>

                                                <div class="field break-inside-avoid">
                                                    <label for="mother_surname" class="block mb-2 font-semibold text-900">
                                                        Apellido Materno
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-user"></i>
                                                        </InputGroupAddon>
                                                        <InputText v-model="form.mother_surname" class="w-full"
                                                        :class="{ 'p-invalid': form.errors.mother_surname }"/>
                                                    </InputGroup>
                                                    <small class="p-error" v-if="
                                                        form.errors.mother_surname
                                                    ">{{
                                                        form.errors.mother_surname
                                                    }}</small>
                                                </div>

                                                <div class="field break-inside-avoid">
                                                    <label for="personal_phone"
                                                        class="block mb-2 font-semibold text-900">
                                                        Teléfono
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-phone"></i>
                                                        </InputGroupAddon>
                                                        <InputText
                                                            v-model="form.personal_phone"
                                                            :modelValue="form.personal_phone"
                                                            @update:modelValue="onAccountInput"
                                                            @keydown="e => {
                                                                if (
                                                                    !/[0-9]/.test(e.key) &&
                                                                    !['Backspace','Tab','ArrowLeft','ArrowRight','Delete'].includes(e.key)
                                                                ) {
                                                                    e.preventDefault()
                                                                }
                                                            }"
                                                            class="w-full"
                                                            :class="{ 'p-invalid': form.errors.personal_phone }"
                                                            inputmode="numeric"
                                                            maxlength="10"
                                                            placeholder="4400112233"
                                                        />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.personal_phone">{{
                                                        form.errors.personal_phone
                                                        }}</small>
                                                </div>

                                                <div class="field break-inside-avoid">
                                                    <label for="birthday" class="block mb-2 font-semibold text-900">
                                                        Fecha de nacimiento
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-calendar"></i>
                                                        </InputGroupAddon>

                                                        <DatePicker v-model="form.birthday" dateFormat="yy-mm-dd"
                                                            showIcon :class="{
                                                                'p-invalid':
                                                                    form.errors
                                                                        .birthday,
                                                            }" fluid iconDisplay="input" class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.birthday">{{
                                                        form.errors.birthday
                                                        }}</small>
                                                </div>

                                                <div class="field break-inside-avoid">
                                                    <label for="gender_id" class="block mb-2 font-semibold text-900">
                                                        Genero
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-mars"></i>
                                                        </InputGroupAddon>

                                                        <Select v-model="form.gender_id" :options="genders" filter
                                                            optionLabel="name" optionValue="id" :class="{
                                                                'p-invalid':
                                                                    form.errors
                                                                        .gender_id,
                                                            }" placeholder="Selecciona una opción ..."
                                                            class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.gender_id">{{
                                                        form.errors.gender_id
                                                        }}</small>
                                                </div>

                                                <div class="field break-inside-avoid">
                                                    <label for="birth_state_id" class="block mb-2 font-semibold text-900">
                                                        Estado de Nacimiento
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-map"></i>
                                                        </InputGroupAddon>

                                                        <Select v-model="form.birth_state_id" :options="props.states" filter
                                                            optionLabel="name" optionValue="id" :class="{
                                                                'p-invalid':
                                                                    form.errors
                                                                        .birth_state_id,
                                                            }" placeholder="Selecciona una opción ..."
                                                            class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.birth_state_id">{{
                                                        form.errors.birth_state_id
                                                        }}</small>
                                                </div>

                                                <div class="field break-inside-avoid">
                                                    <label for="dni" class="block mb-2 font-semibold text-900">
                                                        CURP
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-id-card"></i>
                                                        </InputGroupAddon>

                                                        <InputText id="curp" v-model="form.dni" class="w-full" :class="{
                                                            'p-invalid':
                                                                form.errors.dni,
                                                        }" maxlength="18" @input="validateCURP"
                                                            placeholder="18 caracteres" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.dni">{{
                                                        form.errors.dni
                                                        }}</small>
                                                </div>

                                                <div class="field break-inside-avoid">
                                                    <label for="health_id" class="block mb-2 font-semibold text-900">
                                                        NSS
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-shield"></i>
                                                        </InputGroupAddon>

                                                        <InputText
                                                            v-model="form.health_id"
                                                            :modelValue="form.health_id"
                                                            @input="validateNSS"
                                                            @update:modelValue="onAccountInput"
                                                            @keydown="e => {
                                                                if (
                                                                    !/[0-9]/.test(e.key) &&
                                                                    !['Backspace','Tab','ArrowLeft','ArrowRight','Delete'].includes(e.key)
                                                                ) {
                                                                    e.preventDefault()
                                                                }
                                                            }"
                                                            class="w-full"
                                                            :class="{ 'p-invalid': form.errors.health_id }"
                                                            inputmode="numeric"
                                                            maxlength="11"
                                                            placeholder="11 dígitos"
                                                        />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.health_id">{{
                                                        form.errors.health_id
                                                    }}</small>
                                                </div>

                                                <div class="field break-inside-avoid">
                                                    <label for="email" class="block mb-2 font-semibold text-900">
                                                        Correo
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-envelope"></i>
                                                        </InputGroupAddon>
                                                        <InputText type="email" v-model="form.email" :class="{
                                                            'p-invalid':
                                                                form.errors
                                                                    .email,
                                                        }" class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.email">{{
                                                        form.errors.email
                                                        }}</small>
                                                </div>
                                            </div>
                                        </template>
                                    </Card>
                                    <Card class="shadow-none border-none">
                                        <template #title><i class="pi pi-map text-orange-500"></i>
                                            Domicilio
                                        </template>
                                        <template #content>
                                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                                <div class="field break-inside-avoid">
                                                    <label for="street" class="block mb-2 font-semibold text-900">
                                                        Calle
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-map-marker"></i>
                                                        </InputGroupAddon>

                                                        <InputText v-model="form.street" :class="{
                                                            'p-invalid':
                                                                form.errors
                                                                    .street,
                                                        }" id="home_street" class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.street">{{
                                                        form.errors.street
                                                        }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="external_number"
                                                        class="block mb-2 font-semibold text-900">
                                                        Número Exterior
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-hashtag"></i>
                                                        </InputGroupAddon>

                                                        <InputText v-model="form.external_number"
                                                        :class="{
                                                            'p-invalid':
                                                                form.errors
                                                                    .external_number,
                                                        }" id="external_number" class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="
                                                        form.errors
                                                            .external_number
                                                    ">{{
                                                        form.errors
                                                            .external_number
                                                    }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="internal_number"
                                                        class="block mb-2 font-semibold text-900">
                                                        Número Interior
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-hashtag"></i>
                                                        </InputGroupAddon>

                                                        <InputText v-model="form.internal_number"
                                                        :class="{
                                                            'p-invalid':
                                                                form.errors
                                                                    .internal_number,
                                                        }" id="internal_number" class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="
                                                        form.errors
                                                            .internal_number
                                                    ">{{
                                                        form.errors
                                                            .internal_number
                                                    }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="state_id" class="block mb-2 font-semibold text-900">
                                                        Estado
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-map"></i>
                                                        </InputGroupAddon>

                                                        <Select v-model="form.state_id" :options="props.states" filter
                                                            optionLabel="name" optionValue="id" :class="{
                                                                'p-invalid':
                                                                    form.errors
                                                                        .state_id,
                                                            }" placeholder="Selecciona una opción ..."
                                                            class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.state_id">{{
                                                        form.errors.state_id
                                                        }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="city_id" class="block mb-2 font-semibold text-900">
                                                        Ciudad
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-map-marker"></i>
                                                        </InputGroupAddon>

                                                        <Select v-model="form.city_id" :options="filteredCities" filter
                                                            optionLabel="name" optionValue="id" :class="{
                                                                'p-invalid':
                                                                    form.errors
                                                                        .city_id,
                                                            }" placeholder="Selecciona una opción ..."
                                                            class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.city_id">{{
                                                        form.errors.city_id
                                                        }}</small>
                                                </div>

                                                <div class="field break-inside-avoid">
                                                    <label for="location_id" class="block mb-2 font-semibold text-900">
                                                        Colonia
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-map-marker"></i>
                                                        </InputGroupAddon>

                                                        <AutoComplete
                                                            v-model="form.location_data"
                                                            :suggestions="locations"
                                                            :completeDelay="500"
                                                            :loading="loadingLocations"
                                                            dataKey="id"
                                                            @complete="searchLocations"
                                                            optionLabel="label"
                                                            placeholder="Escribe al menos 3 letras..."
                                                            class="w-full"
                                                            :class="{ 'p-invalid': form.errors.location_id }"
                                                        >

                                                            <!-- Cómo se ve en la lista -->
                                                            <template #option="{ option }">
                                                                <div>
                                                                    ({{ option.id }}) {{ option.name }}
                                                                </div>
                                                            </template>

                                                            <!-- Cómo se ve cuando ya lo seleccionas -->
                                                            <template #value="{ value }">
                                                                <div v-if="value">
                                                                    ({{ value.id }}) {{ value.name }}
                                                                </div>
                                                            </template>
                                                        </AutoComplete>

                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.location_id">{{
                                                        form.errors.location_id
                                                        }}</small>
                                                </div>

                                                <div class="field break-inside-avoid">
                                                    <label for="postal_code" class="block mb-2 font-semibold text-900">
                                                        Codigo Postal
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-map"></i>
                                                        </InputGroupAddon>
                                                        <InputText
                                                            v-model="form.postal_code"
                                                            maxlength="5"
                                                            :class="{ 'p-invalid': form.errors.postal_code }"
                                                            class="w-full"
                                                        />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="
                                                        form.errors.postal_code
                                                    ">{{
                                                        form.errors.postal_code
                                                    }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="country_id" class="block mb-2 font-semibold text-900">
                                                        País
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-map-marker"></i>
                                                        </InputGroupAddon>

                                                        <Select v-model="form.country_id" :options="props.countries"
                                                            filter optionLabel="name" optionValue="id" :class="{
                                                                'p-invalid':
                                                                    form.errors
                                                                        .country_id,
                                                            }" placeholder="Selecciona una opción ..."
                                                            class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.country_id">{{
                                                        form.errors.country_id
                                                        }}</small>
                                                </div>
                                            </div>
                                        </template>
                                    </Card>
                                    <Card class="shadow-none border-none">
                                        <template #title><i class="pi pi-id-card text-orange-500"></i>
                                            Datos Fiscales
                                        </template>
                                        <template #content>
                                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                                <div class="field break-inside-avoid">
                                                    <label for="tax_id" class="block mb-2 font-semibold text-900">
                                                        RFC
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-id-card"></i>
                                                        </InputGroupAddon>

                                                        <InputText id="rfc" v-model="form.tax_id" class="w-full"
                                                            :class="{ 'p-invalid': form.errors.tax_id }" maxlength="13"
                                                            @input="formatRFC" placeholder="13 caracteres" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.tax_id">{{
                                                        form.errors.tax_id
                                                    }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="postal_code_tax" class="block mb-2 font-semibold text-900">
                                                        Codigo Postal
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-map"></i>
                                                        </InputGroupAddon>
                                                        <InputText
                                                            v-model="form.postal_code_tax"
                                                            maxlength="5"
                                                            :class="[
                                                                'w-full',
                                                                { 'p-invalid': form.errors.postal_code_tax }
                                                            ]"
                                                        />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="
                                                        form.errors.postal_code_tax
                                                    ">{{
                                                        form.errors.postal_code_tax
                                                    }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="tax_system_id" class="block mb-2 font-semibold text-900">
                                                        Regimen Fiscal
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-map-marker"></i>
                                                        </InputGroupAddon>

                                                        <Select v-model="form.tax_system_id" :options="props.tax_systems"
                                                            filter optionLabel="name" optionValue="id" :class="{
                                                                'p-invalid':
                                                                    form.errors
                                                                        .tax_system_id,
                                                            }" placeholder="Selecciona una opción ..."
                                                            class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.tax_system_id">{{
                                                        form.errors.tax_system_id
                                                        }}</small>
                                                </div>
                                            </div>
                                        </template>
                                    </Card>
                                    <Card class="shadow-none border-none">
                                        <template #title><i class="pi pi-dollar text-orange-500"></i>
                                            Datos de Pago
                                        </template>
                                        <template #content>
                                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                                <div class="field break-inside-avoid">
                                                    <label for="account_number" class="block mb-2 font-semibold text-900">
                                                        Cuenta
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-hashtag"></i>
                                                        </InputGroupAddon>

                                                        <InputText v-model="form.account_number"
                                                            :modelValue="form.account_number"
                                                            @update:modelValue="onAccountInput"
                                                            @keydown="e => {
                                                                if (
                                                                    !/[0-9]/.test(e.key) &&
                                                                    !['Backspace','Tab','ArrowLeft','ArrowRight','Delete'].includes(e.key)
                                                                ) {
                                                                    e.preventDefault()
                                                                }
                                                            }"
                                                            class="w-full"
                                                            :class="{ 'p-invalid': form.errors.account_number }"
                                                            inputmode="numeric"
                                                            maxlength="11"
                                                            placeholder="10 - 11 dígitos"
                                                        />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="
                                                        form.errors
                                                            .account_number
                                                    ">{{
                                                        form.errors
                                                            .account_number
                                                    }}</small>
                                                </div>

                                                <div class="field break-inside-avoid">
                                                    <label for="rfc" class="block mb-2 font-semibold text-900">
                                                        Tarjeta
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-credit-card"></i>
                                                        </InputGroupAddon>

                                                        <InputText v-model="form.account_card"
                                                            :modelValue="form.account_card"
                                                            @update:modelValue="onAccountInput"
                                                            @keydown="e => {
                                                                if (
                                                                    !/[0-9]/.test(e.key) &&
                                                                    !['Backspace','Tab','ArrowLeft','ArrowRight','Delete'].includes(e.key)
                                                                ) {
                                                                    e.preventDefault()
                                                                }
                                                            }"
                                                            class="w-full"
                                                            :class="{ 'p-invalid': form.errors.account_card }"
                                                            inputmode="numeric"
                                                            maxlength="16"
                                                            placeholder="16 dígitos"
                                                        />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="
                                                        form.errors.account_card
                                                    ">{{
                                                        form.errors.account_card
                                                    }}</small>
                                                </div>

                                                <div class="field break-inside-avoid">
                                                    <label for="rfc" class="block mb-2 font-semibold text-900">
                                                        CLABE
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-lock"></i>
                                                        </InputGroupAddon>

                                                        <InputText v-model="form.account_code"
                                                            :modelValue="form.account_code"
                                                            @update:modelValue="onAccountInput"
                                                            @keydown="e => {
                                                                if (
                                                                    !/[0-9]/.test(e.key) &&
                                                                    !['Backspace','Tab','ArrowLeft','ArrowRight','Delete'].includes(e.key)
                                                                ) {
                                                                    e.preventDefault()
                                                                }
                                                            }"
                                                            class="w-full"
                                                            :class="{ 'p-invalid': form.errors.account_code }"
                                                            inputmode="numeric"
                                                            maxlength="18"
                                                            placeholder="18 dígitos"
                                                        />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="
                                                        form.errors.account_code
                                                    ">{{
                                                        form.errors.account_code
                                                    }}</small>
                                                </div>

                                                <div class="field break-inside-avoid">
                                                    <label for="daily_salary" class="block mb-2 font-semibold text-900">
                                                        Salario Diario
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-map"></i>
                                                        </InputGroupAddon>
                                                        <InputNumber v-model="form.daily_salary"
                                                        :class="{
                                                            'p-invalid':
                                                                form.errors
                                                                    .daily_salary,
                                                        }" class="w-full" :useGrouping="false" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="
                                                        form.errors.daily_salary
                                                    ">{{
                                                        form.errors.daily_salary
                                                    }}</small>
                                                </div>

                                                <div class="field break-inside-avoid">
                                                    <label for="salary" class="block mb-2 font-semibold text-900">
                                                        SDI
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-map"></i>
                                                        </InputGroupAddon>
                                                        <InputNumber v-model="form.salary"
                                                        :class="{
                                                            'p-invalid':
                                                                form.errors
                                                                    .salary,
                                                        }" class="w-full" :useGrouping="false" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="
                                                        form.errors.salary
                                                    ">{{
                                                        form.errors.salary
                                                    }}</small>
                                                </div>

                                                <div class="field break-inside-avoid">
                                                    <label for="rfc" class="block mb-2 font-semibold text-900">
                                                        Banco
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-wallet"></i>
                                                        </InputGroupAddon>

                                                        <Select v-model="form.bank_id" :options="props.banks" filter
                                                            optionLabel="name" optionValue="id" :class="{
                                                                'p-invalid':
                                                                    form.errors
                                                                        .bank_id,
                                                            }" placeholder="Selecciona una opción ..."
                                                            class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.bank_id">{{
                                                        form.errors.bank_id
                                                        }}</small>
                                                </div>

                                                <div class="field break-inside-avoid">
                                                    <label for="payment_method_id"
                                                        class="block mb-2 font-semibold text-900">
                                                        Método de Pago
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-wallet"></i>
                                                        </InputGroupAddon>

                                                        <Select v-model="form.payment_method_id" :options="props.payment_methods"
                                                            filter optionLabel="name" optionValue="id" :class="{
                                                                'p-invalid':
                                                                    form.errors
                                                                        .payment_method_id,
                                                            }" placeholder="Selecciona una opción ..."
                                                            class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.payment_method_id">{{
                                                        form.errors.payment_method_id
                                                        }}</small>
                                                </div>

                                                <div class="field break-inside-avoid">
                                                    <label for="weekly_salary"
                                                        class="block mb-2 font-semibold text-900">
                                                        Salario Semanal
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-map"></i>
                                                        </InputGroupAddon>
                                                        <InputNumber v-model="form.weekly_salary"
                                                        :class="{
                                                            'p-invalid':
                                                                form.errors
                                                                    .weekly_salary,
                                                        }" class="w-full" :useGrouping="false" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="
                                                        form.errors.weekly_salary
                                                    ">{{
                                                        form.errors.weekly_salary
                                                    }}</small>
                                                </div>
                                            </div>
                                        </template>
                                    </Card>
                                    <Card class="shadow-none border-none">
                                        <template #title><i class="pi pi-briefcase text-orange-500"></i>
                                            Datos Laborales
                                        </template>
                                        <template #content>
                                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                                <div class="field break-inside-avoid">
                                                    <label for="entry_date" class="block mb-2 font-semibold text-900">
                                                        Fecha de Ingreso
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-calendar"></i>
                                                        </InputGroupAddon>
                                                        <DatePicker v-model="form.entry_date" dateFormat="yy-mm-dd"
                                                        :class="{
                                                            'p-invalid':
                                                                form.errors
                                                                    .entry_date,
                                                        }" class="w-full" fluid iconDisplay="input" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="
                                                        form.errors.entry_date
                                                    ">{{
                                                        form.errors.entry_date
                                                    }}</small>
                                                </div>

                                                <div class="field break-inside-avoid">
                                                    <label for="termination_date"
                                                        class="block mb-2 font-semibold text-900">
                                                        Fecha de Terminación
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-calendar"></i>
                                                        </InputGroupAddon>
                                                        <DatePicker v-model="form.termination_date" dateFormat="yy-mm-dd"
                                                        :class="{
                                                            'p-invalid':
                                                                form.errors
                                                                    .termination_date,
                                                        }" class="w-full" fluid iconDisplay="input" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="
                                                        form.errors.termination_date
                                                    ">{{
                                                        form.errors.termination_date
                                                    }}</small>
                                                </div>

                                                <div class="field break-inside-avoid">
                                                    <label for="status" class="block mb-2 font-semibold text-900">
                                                        Estado
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-history"></i>
                                                        </InputGroupAddon>

                                                        <Select v-model="form.status" :options="status" filter
                                                            optionLabel="name" optionValue="id" :class="{
                                                                'p-invalid':
                                                                    form.errors
                                                                        .status,
                                                            }" placeholder="Selecciona una opción ..."
                                                            class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.status">{{
                                                        form.errors.status
                                                        }}</small>
                                                </div>

                                                <div class="field break-inside-avoid">
                                                    <label for="position_id" class="block mb-2 font-semibold text-900">
                                                        Puesto
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-briefcase"></i>
                                                        </InputGroupAddon>

                                                        <Select v-model="form.position_id"
                                                        :options="props.positions"
                                                        filter optionLabel="name" optionValue="id" :class="{
                                                                'p-invalid':
                                                                    form.errors
                                                                        .position_id,
                                                        }" placeholder="Selecciona una opción ..."
                                                        class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="
                                                        form.errors.position_id
                                                    ">{{
                                                        form.errors.position_id
                                                        }}</small>
                                                </div>

                                                <div class="field break-inside-avoid">
                                                    <label for="branch_office_id"
                                                        class="block mb-2 font-semibold text-900">
                                                        Planta
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-building"></i>
                                                        </InputGroupAddon>

                                                        <Select v-model="form.branch_office_id"
                                                        :options="props.branch_offices"
                                                        filter optionLabel="code" optionValue="id" :class="{
                                                                'p-invalid':
                                                                    form.errors
                                                                        .branch_office_id,
                                                        }" placeholder="Selecciona una opción ..."
                                                        class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="
                                                        form.errors.branch_office_id
                                                    ">{{
                                                        form.errors.branch_office_id
                                                        }}</small>
                                                </div>

                                                <div class="field break-inside-avoid">
                                                    <label for="branch_office_location_id"
                                                        class="block mb-2 font-semibold text-900">
                                                        Ubicación
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-building"></i>
                                                        </InputGroupAddon>

                                                        <Select v-model="form.branch_office_location_id"
                                                        :options="props.branch_offices_locations"
                                                        filter optionLabel="name" optionValue="id" :class="{
                                                                'p-invalid':
                                                                    form.errors
                                                                        .branch_office_location_id,
                                                        }" placeholder="Selecciona una opción ..."
                                                        class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="
                                                        form.errors.branch_office_location_id
                                                    ">{{
                                                        form.errors.branch_office_location_id
                                                        }}</small>
                                                </div>

                                                <div class="field break-inside-avoid">
                                                    <label for="rfc" class="block mb-2 font-semibold text-900">
                                                        Departamento
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-sitemap"></i>
                                                        </InputGroupAddon>

                                                        <Select v-model="form.department_id"
                                                        :options="props.departments"
                                                        filter optionLabel="name" optionValue="id" :class="{
                                                                'p-invalid':
                                                                    form.errors
                                                                        .department_id,
                                                        }" placeholder="Selecciona una opción ..."
                                                        class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="
                                                        form.errors
                                                            .department_id
                                                    ">{{
                                                        form.errors
                                                            .department_id
                                                    }}</small>
                                                </div>

                                                <div class="field break-inside-avoid">
                                                    <label for="company_phone"
                                                        class="block mb-2 font-semibold text-900">
                                                        Teléfono de la Empresa
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-phone"></i>
                                                        </InputGroupAddon>
                                                        <InputText
                                                            v-model="form.company_phone"
                                                            :modelValue="form.company_phone"
                                                            @update:modelValue="onAccountInput"
                                                            @keydown="e => {
                                                                if (
                                                                    !/[0-9]/.test(e.key) &&
                                                                    !['Backspace','Tab','ArrowLeft','ArrowRight','Delete'].includes(e.key)
                                                                ) {
                                                                    e.preventDefault()
                                                                }
                                                            }"
                                                            class="w-full"
                                                            :class="{ 'p-invalid': form.errors.company_phone }"
                                                            inputmode="numeric"
                                                            maxlength="10"
                                                            placeholder="4400112233"
                                                        />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.company_phone">{{
                                                        form.errors.company_phone
                                                        }}</small>
                                                </div>

                                                <div class="field break-inside-avoid">
                                                    <label class="block mb-2 font-semibold text-900">
                                                        Prestaciones
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-shopping-bag"></i>
                                                        </InputGroupAddon>

                                                        <Multiselect v-model="form.benefits" :options="props.benefits"
                                                            optionLabel="name" optionValue="id" filter
                                                            :filterFields="['name', 'id']"
                                                            placeholder="Selecciona las prestaciones" class="w-full"
                                                            display="chip"
                                                            :class="{ 'p-invalid': form.errors.benefits }">

                                                            <template #value="slotProps">
                                                                <span
                                                                    v-if="!slotProps.value || slotProps.value.length === 0">
                                                                    Selecciona las prestaciones
                                                                </span>

                                                                <span v-else-if="slotProps.value.length > 5">
                                                                    {{ slotProps.value.length }} prestaciones
                                                                    seleccionadas
                                                                </span>
                                                            </template>

                                                            <template #option="{ option }">
                                                                <div class="flex items-center gap-2">
                                                                    <span class="font-bold text-gray-700">
                                                                        ({{ option.id }})
                                                                    </span>
                                                                    <span>{{ option.name }}</span>
                                                                </div>
                                                            </template>

                                                        </Multiselect>
                                                    </InputGroup>

                                                    <small class="p-error" v-if="form.errors.benefits">
                                                        {{ form.errors.benefits }}
                                                    </small>
                                                </div>

                                                <div class="field break-inside-avoid">
                                                    <label class="block mb-2 font-semibold text-900">
                                                        Jefe(s) Inmediato(s)
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-users"></i>
                                                        </InputGroupAddon>

                                                        <AutoComplete
                                                            v-model="form.parent_data"
                                                            :suggestions="employees"
                                                            @complete="searchEmployee"
                                                            optionLabel="full_name"
                                                            multiple
                                                            class="w-full"
                                                        >
                                                            <template #chip="{ value, removeCallback }">
                                                                <div class="flex align-items-center gap-2">
                                                                    <span>({{ value.id }}) {{ value.full_name }}</span>
                                                                    <span
                                                                        class="pi pi-times-circle cursor-pointer ml-2 text-sm"
                                                                        @click="removeCallback"
                                                                    ></span>
                                                                </div>
                                                            </template>

                                                            <template #option="{ option }">
                                                                <div>({{ option.id }}) {{ option.full_name }}</div>
                                                            </template>
                                                        </AutoComplete>
                                                    </InputGroup>

                                                    <small class="p-error" v-if="form.errors.employee_parent_id">
                                                        {{ form.errors.employee_parent_id }}
                                                    </small>
                                                </div>

                                                <div class="field break-inside-avoid">
                                                    <label for="employee_parent_email"
                                                        class="block mb-2 font-semibold text-900">
                                                        Correo Jefe Inmediato
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-envelope"></i>
                                                        </InputGroupAddon>
                                                        <InputText
                                                            type="email"
                                                            v-model="form.employee_parent_email"
                                                            :class="{
                                                                'p-invalid':
                                                                form.errors
                                                                .employee_parent_email,}"
                                                            class="w-full"
                                                        />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.employee_parent_email">{{
                                                        form.errors.employee_parent_email
                                                        }}</small>
                                                </div>

                                                <div class="field break-inside-avoid">
                                                    <label for="classification_id"
                                                        class="block mb-2 font-semibold text-900">
                                                        Clasificación
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-cog"></i>
                                                        </InputGroupAddon>

                                                        <Select v-model="form.classification_id
                                                            " :options="props.clasificacions
                                                                " filter optionLabel="description" optionValue="id" :class="{
                                                                    'p-invalid':
                                                                        form.errors
                                                                            .classification_id,
                                                                }" placeholder="Selecciona una opción ..."
                                                            class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="
                                                        form.errors
                                                            .classification_id
                                                    ">{{
                                                        form.errors
                                                            .classification_id
                                                    }}</small>
                                                </div>

                                            </div>
                                        </template>
                                    </Card>
                                    <Card class="shadow-none border-none">
                                        <template #title><i class="pi pi-file-plus text-orange-500"></i>
                                            Información Adiccional
                                        </template>
                                        <template #content>
                                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                                <div class="field break-inside-avoid">
                                                    <label for="user_id" class="block mb-2 font-semibold text-900">
                                                        Usuario Asignado
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-user"></i>
                                                        </InputGroupAddon>

                                                        <AutoComplete
                                                            v-model="form.user_data"
                                                            :suggestions="users"
                                                            @complete="searchUsers"
                                                            dataKey="id"
                                                            optionLabel="label"
                                                            optionValue="id"
                                                            placeholder="Escribe al menos 3 letras..."
                                                            class="w-full"
                                                        >
                                                            <!-- Cómo se ve en la lista -->
                                                            <template #option="{ option }">
                                                                <div>
                                                                    ({{ option.id }}) {{ option.username }}
                                                                </div>
                                                            </template>

                                                            <!-- Cómo se ve cuando ya lo seleccionas -->
                                                            <template #value="{ value }">
                                                                <div v-if="value">
                                                                    ({{ value.id }}) {{ value.username }}
                                                                </div>
                                                            </template>
                                                        </AutoComplete>
                                                    </InputGroup>
                                                    <small class="p-error" v-if="
                                                        form.errors
                                                            .user_id
                                                    ">{{
                                                        form.errors
                                                            .user_id
                                                    }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="additional_phone"
                                                        class="block mb-2 font-semibold text-900">
                                                        Teléfono Adicional
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-phone"></i>
                                                        </InputGroupAddon>
                                                        <InputText
                                                            v-model="form.additional_phone"
                                                            :modelValue="form.additional_phone"
                                                            @update:modelValue="onAccountInput"
                                                            @keydown="e => {
                                                                if (
                                                                    !/[0-9]/.test(e.key) &&
                                                                    !['Backspace','Tab','ArrowLeft','ArrowRight','Delete'].includes(e.key)
                                                                ) {
                                                                    e.preventDefault()
                                                                }
                                                            }"
                                                            class="w-full"
                                                            :class="{ 'p-invalid': form.errors.additional_phone }"
                                                            inputmode="numeric"
                                                            maxlength="10"
                                                            placeholder="4400112233"
                                                        />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.additional_phone">{{
                                                        form.errors.additional_phone
                                                        }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="additional_email"
                                                        class="block mb-2 font-semibold text-900">
                                                        Correo Adicional
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-envelope"></i>
                                                        </InputGroupAddon>
                                                        <InputText
                                                            type="email"
                                                            v-model="form.additional_email"
                                                            :class="{
                                                                'p-invalid':
                                                                form.errors
                                                                .additional_email,}"
                                                            class="w-full"
                                                        />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.additional_email">{{
                                                        form.errors.additional_email
                                                        }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="profession" class="block mb-2 font-semibold text-900">
                                                        Profesión
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-user"></i>
                                                        </InputGroupAddon>
                                                        <InputText v-model="form.profession" class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.profession">{{
                                                        form.errors.profession
                                                        }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="level_education" class="block mb-2 font-semibold text-900">
                                                        Nivel de estudios
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-book"></i>
                                                        </InputGroupAddon>
                                                        <InputText
                                                        v-model="form.level_education"
                                                        :class="{
                                                            'p-invalid':
                                                                form.errors
                                                                    .level_education,
                                                        }"
                                                        class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.level_education">{{
                                                        form.errors.level_education
                                                        }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="civil_state" class="block mb-2 font-semibold text-900">
                                                        Estado civil
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-id-card"></i>
                                                        </InputGroupAddon>
                                                        <InputText v-model="form.civil_state" class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.civil_state">{{
                                                        form.errors.civil_state
                                                        }}</small>
                                                </div>

                                                <div class="field break-inside-avoid">
                                                    <label for="unit_health" class="block mb-2 font-semibold text-900">
                                                        Tipo de sangre
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-heart"></i>
                                                        </InputGroupAddon>
                                                        <InputText v-model="form.unit_health" class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.unit_health">{{
                                                        form.errors.unit_health
                                                        }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="emergency_name"
                                                        class="block mb-2 font-semibold text-900">
                                                        Persona de emergencia
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-user"></i>
                                                        </InputGroupAddon>
                                                        <InputText v-model="form.emergency_name" class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.emergency_name">{{
                                                        form.errors.emergency_name
                                                        }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="emergency_phone"
                                                        class="block mb-2 font-semibold text-900">
                                                        Teléfono de emergencia
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-phone"></i>
                                                        </InputGroupAddon>
                                                        <!-- <InputNumber v-model="form.emergency_phone" :class="{
                                                            'p-invalid':
                                                                form.errors
                                                                    .emergency_phone,
                                                        }" placeholder="Teléfono" class="w-full"
                                                            :useGrouping="false" /> -->
                                                        <InputText
                                                            v-model="form.emergency_phone"
                                                            :modelValue="form.emergency_phone"
                                                            @update:modelValue="onAccountInput"
                                                            @keydown="e => {
                                                                if (
                                                                    !/[0-9]/.test(e.key) &&
                                                                    !['Backspace','Tab','ArrowLeft','ArrowRight','Delete'].includes(e.key)
                                                                ) {
                                                                    e.preventDefault()
                                                                }
                                                            }"
                                                            class="w-full"
                                                            :class="{ 'p-invalid': form.errors.emergency_phone }"
                                                            inputmode="numeric"
                                                            maxlength="10"
                                                            placeholder="4400112233"
                                                        />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.emergency_phone">{{
                                                        form.errors.emergency_phone
                                                        }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="employee_type"
                                                        class="block mb-2 font-semibold text-900">
                                                        Tipo de empleado
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-user"></i>
                                                        </InputGroupAddon>
                                                        <InputText v-model="form.employee_type" class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.employee_type">{{
                                                        form.errors.employee_type
                                                        }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="job_type" class="block mb-2 font-semibold text-900">
                                                        Tipo de jornada
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-briefcase"></i>
                                                        </InputGroupAddon>
                                                        <InputText v-model="form.job_type" class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.job_type">{{
                                                        form.errors.job_type
                                                        }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="shift_role_id"
                                                        class="block mb-2 font-semibold text-900">
                                                        Turno
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-clock"></i>
                                                        </InputGroupAddon>
                                                        <Select v-model="form.shift_role_id"
                                                        :options="props.shift_roles"
                                                        filter optionLabel="name"
                                                        optionValue="id" :class="{
                                                            'p-invalid':
                                                                form.errors
                                                                    .shift_role_id,
                                                        }" placeholder="Selecciona una opción ..."
                                                        class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="
                                                        form.errors
                                                            .shift_role_id
                                                    ">{{
                                                        form.errors
                                                            .shift_role_id
                                                    }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="medical_unit" class="block mb-2 font-semibold text-900">
                                                        Unidad Medica
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-heart"></i>
                                                        </InputGroupAddon>
                                                        <InputText v-model="form.medical_unit" class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.medical_unit">{{
                                                        form.errors.medical_unit
                                                        }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="pension_discount"
                                                        class="block mb-2 font-semibold text-900">
                                                        Descuento de pensión
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-percentage"></i>
                                                        </InputGroupAddon>
                                                        <InputNumber
                                                            v-model="form.pension_discount"
                                                            suffix="%"
                                                            :min="0"
                                                            :max="100"
                                                            :useGrouping="false"
                                                            class="w-full"
                                                        />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.pension_discount">{{
                                                        form.errors.pension_discount
                                                        }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="employer_registration"
                                                        class="block mb-2 font-semibold text-900">
                                                        Registro patronal
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-briefcase"></i>
                                                        </InputGroupAddon>
                                                        <InputText v-model="form.employer_registration"
                                                        class="w-full"
                                                        :class="{
                                                        'p-invalid':
                                                            form.errors
                                                                .employer_registration,
                                                        }"/>
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.employer_registration">{{
                                                        form.errors.employer_registration
                                                        }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="type_income" class="block mb-2 font-semibold text-900">
                                                        Tipo de ingresos
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-money-bill"></i>
                                                        </InputGroupAddon>
                                                        <InputText v-model="form.type_income" class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.type_income">{{
                                                        form.errors.type_income
                                                        }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="regime_key" class="block mb-2 font-semibold text-900">
                                                        Clave de regimen
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-key"></i>
                                                        </InputGroupAddon>
                                                        <InputText v-model="form.regime_key" class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.regime_key">{{
                                                        form.errors.regime_key
                                                        }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="contribution_basis"
                                                        class="block mb-2 font-semibold text-900">
                                                        Base de cotización
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-chart-line"></i>
                                                        </InputGroupAddon>
                                                        <InputText v-model="form.contribution_basis" class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.contribution_basis">{{
                                                        form.errors.contribution_basis
                                                        }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="type_contract"
                                                        class="block mb-2 font-semibold text-900">
                                                        Tipo de contrato
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-file-edit"></i>
                                                        </InputGroupAddon>
                                                        <InputText v-model="form.type_contract" class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.type_contract">{{
                                                        form.errors.type_contract
                                                        }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="ruta_plantilla"
                                                        class="block mb-2 font-semibold text-900">
                                                        Ruta de plantilla
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-folder-open"></i>
                                                        </InputGroupAddon>
                                                        <InputText v-model="form.ruta_plantilla" class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.ruta_plantilla">{{
                                                        form.errors.ruta_plantilla
                                                        }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="clave_regimen_cntrato"
                                                        class="block mb-2 font-semibold text-900">
                                                        Clave de regimen de contrato
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-id-card"></i>
                                                        </InputGroupAddon>
                                                        <InputText v-model="form.clave_regimen_cntrato" class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.clave_regimen_cntrato">{{
                                                        form.errors.clave_regimen_cntrato
                                                        }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="holiday_table"
                                                        class="block mb-2 font-semibold text-900">
                                                        Tabla de vacaciones
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-sun"></i>
                                                        </InputGroupAddon>
                                                        <InputText v-model="form.holiday_table" class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.holiday_table">{{
                                                        form.errors.holiday_table
                                                        }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="tabla_salario_diario"
                                                        class="block mb-2 font-semibold text-900">
                                                        Tabla de salario diario
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-table"></i>
                                                        </InputGroupAddon>
                                                        <InputText v-model="form.tabla_salario_diario" class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.tabla_salario_diario">{{
                                                        form.errors.tabla_salario_diario
                                                        }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="days_duration"
                                                        class="block mb-2 font-semibold text-900">
                                                        Días de duración
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-clock"></i>
                                                        </InputGroupAddon>
                                                        <InputText v-model="form.days_duration"
                                                        class="w-full"
                                                        :class="{
                                                        'p-invalid':
                                                            form.errors
                                                                .days_duration,
                                                        }"/>
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.days_duration">{{
                                                        form.errors.days_duration
                                                        }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="beneficiary_name"
                                                        class="block mb-2 font-semibold text-900">
                                                        Nombre del beneficiario
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-user"></i>
                                                        </InputGroupAddon>
                                                        <InputText v-model="form.beneficiary_name"
                                                        class="w-full"
                                                        :class="{
                                                        'p-invalid':
                                                            form.errors
                                                                .beneficiary_name,
                                                        }"/>
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.beneficiary_name">{{
                                                        form.errors.beneficiary_name
                                                        }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="porcentaje_beneficiario" class="block mb-2 font-semibold text-900">
                                                        Porcentaje del Beneficiario
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-percentage"></i>
                                                        </InputGroupAddon>
                                                        <InputNumber
                                                            v-model="form.porcentaje_beneficiario"
                                                            suffix="%"
                                                            :min="0"
                                                            :max="100"
                                                            :useGrouping="false"
                                                            :inputClass="{
                                                                'p-invalid': form.errors.porcentaje_beneficiario
                                                            }"
                                                            class="w-full"
                                                        />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="
                                                        form.errors
                                                            .porcentaje_beneficiario
                                                    ">{{
                                                        form.errors
                                                            .porcentaje_beneficiario
                                                    }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="beneficiary_kinship"
                                                        class="block mb-2 font-semibold text-900">
                                                        Parentesco del beneficiario
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-heart"></i>
                                                        </InputGroupAddon>
                                                        <InputText v-model="form.beneficiary_kinship"
                                                        class="w-full"
                                                        :class="{
                                                        'p-invalid':
                                                            form.errors
                                                                .beneficiary_kinship,
                                                        }"/>
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.beneficiary_kinship">{{
                                                        form.errors.beneficiary_kinship
                                                        }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="nombre_beneficiario2"
                                                        class="block mb-2 font-semibold text-900">
                                                        Nombre del beneficiario 2
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-user"></i>
                                                        </InputGroupAddon>
                                                        <InputText v-model="form.nombre_beneficiario2" class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.nombre_beneficiario2">{{
                                                        form.errors.nombre_beneficiario2
                                                        }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="porcentaje_beneficiario2" class="block mb-2 font-semibold text-900">
                                                        Porcentaje del Beneficiario 2
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-percentage"></i>
                                                        </InputGroupAddon>

                                                        <InputNumber
                                                            v-model="form.porcentaje_beneficiario2"
                                                            suffix="%"
                                                            :min="0"
                                                            :max="100"
                                                            :useGrouping="false"
                                                            :class="{
                                                                'p-invalid':
                                                                    form.errors
                                                                        .porcentaje_beneficiario2,
                                                            }"
                                                            class="w-full"
                                                        />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="
                                                        form.errors
                                                            .porcentaje_beneficiario2
                                                    ">{{
                                                        form.errors
                                                            .porcentaje_beneficiario2
                                                    }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="parentesco_beneficiario2"
                                                        class="block mb-2 font-semibold text-900">
                                                        Parentesco del beneficiario 2
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-heart"></i>
                                                        </InputGroupAddon>
                                                        <InputText v-model="form.parentesco_beneficiario2" class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.parentesco_beneficiario2">{{
                                                        form.errors.parentesco_beneficiario2
                                                        }}</small>
                                                </div>
                                                <div class="field break-inside-avoid">
                                                    <label for="external_id"
                                                        class="block mb-2 font-semibold text-900">
                                                        Numero externo
                                                    </label>

                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-hashtag"></i>
                                                        </InputGroupAddon>
                                                        <InputText v-model="form.external_id" class="w-full" />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.external_id">{{
                                                        form.errors.external_id
                                                        }}</small>
                                                </div>
                                            </div>
                                        </template>
                                    </Card>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
        </form>
        <Dialog
            v-model:visible="reentryDialog"
            @hide="resetReentryFields"
            :style="{ width: '450px' }"
            header="Recontratar Empleado"
            :modal="true"
            class="p-fluid"
        >
            <div class="flex flex-col gap-6 mt-4">
                <div class="flex gap-4 items-start">
                    <FloatLabel variant="on" class="flex-[3]">
                        <Select
                            v-model="selectedBranchOffice"
                            :options="props.branch_offices"
                            optionLabel="code"
                            optionValue="id"
                            filter
                            :filterFields="['code', 'id']"
                            class="w-full"
                        >
                            <template #value="slotProps">
                                <div v-if="slotProps.value" class="flex items-center">
                                    <span class="font-bold mr-2">({{ slotProps.value }})</span>
                                    <span>{{ props.branch_offices.find(b => b.id === slotProps.value)?.code }}</span>
                                </div>
                                <span v-else>&nbsp;</span>
                            </template>
                            <template #option="{ option }">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-gray-700">({{ option.id }})</span>
                                    <span>{{ option.code }}</span>
                                </div>
                            </template>
                        </Select>
                        <label>Planta</label>
                    </FloatLabel>

                    <FloatLabel variant="on" class="flex-1">
                        <InputText
                            v-model="days_duration_rentry"
                            class="w-full"
                            maxlength="3"
                            v-keyfilter.num
                        />
                        <label>Días</label>
                    </FloatLabel>
                </div>

                <div class="flex gap-4">
                    <FloatLabel variant="on" class="flex-1">
                        <DatePicker
                            v-model="valueFrom"
                            inputId="fecha_reingreso"
                            showIcon
                            iconDisplay="input"
                            variant="filled"
                            class="w-full"
                            dateFormat="yy-mm-dd"
                        />
                        <label for="fecha_reingreso">Fecha de reingreso</label>
                    </FloatLabel>

                    <FloatLabel variant="on" class="flex-1">
                        <Select
                            v-model="selectedStatusRentry"
                            :options="reentryReasons"
                            optionLabel="name"
                            optionValue="id"
                            inputId="razon_reingreso"
                            filter
                            class="w-full"
                        />
                        <label for="razon_reingreso">Razón de reingreso</label>
                    </FloatLabel>
                </div>
            </div>

            <template #footer>
                <Button
                    label="Cancelar"
                    icon="pi pi-times"
                    severity="danger"
                    text
                    @click="reentryDialog = false"
                    :disabled="submitted"
                />
                <Button
                    label="Guardar"
                    icon="pi pi-check"
                    severity="success"
                    @click="submitReentry"
                    :loading="submitted"
                />
            </template>
        </Dialog>
        <Dialog
            v-model:visible="transferDialog"
            @hide="resetTransferFields"
            :style="{ width: '450px' }"
            header="Traspasar Empleado"
            :modal="true"
            class="p-fluid"
        >
            <div class="flex flex-col gap-6 mt-4">
                <div class="flex gap-4 items-start">
                    <FloatLabel variant="on" class="flex-1">
                        <DatePicker
                            v-model="dateTerminateTransfer"
                            inputId="fecha_baja"
                            showIcon
                            iconDisplay="input"
                            variant="filled"
                            class="w-full"
                            dateFormat="yy-mm-dd"
                        />
                        <label for="fecha_baja">Fecha de baja</label>
                    </FloatLabel>

                    <FloatLabel variant="on" class="flex-1">
                        <Select
                            v-model="selectedStatusTransfer"
                            :options="reentryReasons"
                            optionLabel="name"
                            optionValue="id"
                            inputId="razon_cambio"
                            filter
                            class="w-full"
                        />
                        <label for="razon_cambio">Razón de cambio</label>
                    </FloatLabel>
                </div>

                <div class="flex gap-4">
                    <FloatLabel variant="on" class="flex-1">
                        <Select
                            v-model="selectedBranchOfficeTransfer"
                            :options="props.branch_offices"
                            optionLabel="code"
                            optionValue="id"
                            filter
                            :filterFields="['code', 'id']"
                            class="w-full"
                            :optionDisabled="(option) => option.id === employee.employment_data.branch_office_id"
                        >
                            <template #value="slotProps">
                                <div v-if="slotProps.value" class="flex items-center">
                                    <span class="font-bold mr-2">({{ slotProps.value }})</span>
                                    <span>{{ props.branch_offices.find(b => b.id === slotProps.value)?.code }}</span>
                                </div>
                                <span v-else>&nbsp;</span>
                            </template>
                            <template #option="{ option }">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-gray-700">({{ option.id }})</span>
                                    <span>{{ option.code }}</span>
                                </div>
                            </template>
                        </Select>
                        <label>Planta</label>
                    </FloatLabel>

                    <FloatLabel variant="on" class="flex-1">
                        <DatePicker
                            v-model="dateEntryTransfer"
                            inputId="fecha_alta"
                            showIcon
                            iconDisplay="input"
                            variant="filled"
                            class="w-full"
                            dateFormat="yy-mm-dd"
                        />
                        <label for="fecha_alta">Fecha de alta</label>
                    </FloatLabel>
                </div>
                <div class="flex gap-4">
                    <FloatLabel variant="on" class="flex-1">
                        <Textarea id="on_label" v-model="transfer_observations" rows="5" cols="30" class="w-full" style="resize: none" />
                        <label for="on_label">Observaciones</label>
                    </FloatLabel>
                </div>
                <div v-if="shouldShowEfficiencyFields" class="flex gap-4 mt-4">
                    <FloatLabel variant="on" class="flex-1">
                        <DatePicker
                            v-model="efficiency_month"
                            view="month"
                            showIcon
                            dateFormat="mm/yy"
                            class="w-full"
                        />
                        <label for="on_label">Mes de eficiencia</label>
                    </FloatLabel>
                    <FloatLabel variant="on" class="flex-1">
                        <IconField>
                            <InputText
                                v-model="efficiency"
                                class="w-full"
                                maxlength="3"
                                v-keyfilter.num
                            />
                            <InputIcon class="pi pi-percentage" />
                        </IconField>
                        <label>Eficiencia</label>
                    </FloatLabel>
                </div>
            </div>


            <template #footer>
                <Button
                    label="Cancelar"
                    icon="pi pi-times"
                    severity="danger"
                    text
                    @click="transferDialog = false"
                    :disabled="submittedTransfer"
                />
                <Button
                    label="Guardar"
                    icon="pi pi-check"
                    severity="success"
                    @click="submitTrasfer"
                    :loading="submittedTransfer"
                />
            </template>
        </Dialog>
        <Dialog
            v-model:visible="terminatedDialog"
            @hide="resetTerminatedferFields"
            :style="{ width: '450px' }"
            header="Dar de baja al empleado"
            :modal="true"
            class="p-fluid"
        >
            <div class="flex flex-col gap-6 mt-4">
                <div class="flex gap-4 items-start">
                    <FloatLabel variant="on" class="flex-1">
                        <DatePicker v-model="dateTerminated" inputId="fecha_baja" showIcon iconDisplay="input" variant="filled" class="w-full" dateFormat="yy-mm-dd" />
                        <label for="fecha_baja">Fecha de baja</label>
                    </FloatLabel>

                    <FloatLabel variant="on" class="flex-1">
                        <Select v-model="selectedStatusTerminated" :options="exitReasons" optionLabel="name" optionValue="id" inputId="razon_cambio" filter class="w-full" />
                        <label for="razon_cambio">Razón de cambio</label>
                    </FloatLabel>
                </div>

                <div v-if="showObservationsField" class="flex flex-col gap-2 animate-fadein">
                    <FloatLabel variant="on" class="flex-1">
                        <Textarea
                            v-model="termination_observations"
                            rows="3"
                            class="w-full"
                            style="resize: none"
                            placeholder="Escriba los motivos o detalles de la baja..."
                        />
                        <label>Detalles de la baja (Obligatorio)</label>
                    </FloatLabel>
                    <div class="flex flex-col gap-3">
                        <label class="text-sm flex-1">
                            ¿El empleado es apto para ser <strong>recontratado</strong> en el futuro?
                        </label>
                        <FloatLabel variant="on" class="flex-1">
                            <Select
                                v-model="is_rehireable"
                                inputId="rehireable_select"
                                :options="rehireables"
                                optionLabel="name"
                                class="w-full"
                            />
                            <label for="rehireable_select">Seleccione una opción</label>
                        </FloatLabel>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Cancelar" icon="pi pi-times" severity="danger" text @click="terminatedDialog = false" :disabled="submittedTerminated" />

                <Button
                    v-if="!showObservationsField"
                    label="Continuar"
                    icon="pi pi-arrow-right"
                    severity="warning"
                    @click="confirmTermination"
                />
                <Button
                    v-else
                    label="Confirmar Baja Definitiva"
                    icon="pi pi-check"
                    severity="success"
                    @click="submitTerminated"
                    :loading="submittedTerminated"
                />
            </template>
        </Dialog>

        <ConfirmDialog></ConfirmDialog>
    </AppLayout>
</template>
<style scoped>
.sticky-header {
    position: sticky;
    top: 70px;
    z-index: 1000;
}

:deep(.p-autocomplete-loader) {
    z-index: 10;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    display: block !important;
}

:deep(.p-autocomplete-loader i) {
    color: var(--primary-color);
}

</style>
