<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import axios from "axios";
import { useToastService } from "../../Stores/toastService";
import { Link } from '@inertiajs/vue3'
import { useToast } from "primevue/usetoast";
import { computed, watch } from 'vue';

const toast = useToast();


const props = defineProps({
    states: Array,
    cities: Array,
    // locations: Array,
    countries: Array,
    tax_systems: Array,
    banks: Array,
    payment_methods: Array,
    positions: Array,
    branch_offices: Array,
    branch_offices_locations: Array,
    departments: Array,
    benefits: Array,
    // employees: Array,
    clasificacions: Array,
    // users: Array,
    shift_roles: Array,
    genders: Array,
});

const { showError, showSuccess, showValidationError } = useToastService();
const loading = ref(false);

const loadingLocations = ref(false);
const loadingCatalog = ref(false);
const locations = ref([]);
const employees = ref([]);
const users = ref([]);

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

//filtrar las cciudades cuando cambie el estado
const filteredCities = computed(() => {
    if (!form.state_id) {
        return [];
    }
    return props.cities.filter(city => city.state_id === form.state_id);
});

watch(() => form.state_id, () => {
    form.city_id = null;
});

const onAccountInput = (val, field) => {
    const clean = (val || '').replace(/\D/g, '')
    form[field] = clean
}

const formatRFC = () => {
    if (!form.tax_id) {
        form.clearErrors('tax_id');
        return;
    }
    form.tax_id = form.tax_id.toUpperCase();

    if (form.tax_id.length > 0 && form.tax_id.length !== 13) {
        form.setError("tax_id", "El RFC debe tener exactamente 13 caracteres");
    } else {
        form.clearErrors("tax_id");
    }
};

const validateNSS = () => {
    form.health_id = form.health_id.replace(/\D/g, "");

    const nssRegex = /^\d{11}$/;

    if (form.health_id.length > 0 && !nssRegex.test(form.health_id)) {
        form.setError("health_id", "El NSS debe tener 11 dígitos numéricos");
    } else {
        form.clearErrors("health_id");
    }
};

const validateCURP = () => {
    if (!form.dni) {
        form.clearErrors('dni');
        return;
    }
    form.dni = form.dni.toUpperCase();
    if (form.dni.length > 0 && form.dni.length !== 18) {
        form.setError('dni', 'La CURP debe tener exactamente 18 caracteres');
    } else {
        form.clearErrors('dni');
    }
};

const status = [
    { name: "Alta", id: "entry" },
    { name: "Baja", id: "termination" },
    { name: "Reingreso", id: "reentry" },
    { name: "Traspaso", id: "change" },
];

const goToCatalog = () => {
    loadingCatalog.value = true

    router.get(route('catalog'), {}, {
        onFinish: () => {
            loadingCatalog.value = false
        }
    })
}

const submit = () => {
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
        parent_data: "Jefe Inmediato",
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
        const esVacio = (
            valor === null ||
            valor === undefined ||
            valor === "" ||
            (Array.isArray(valor) && valor.length === 0) ||
            (typeof valor === 'object' && !(valor instanceof Date) && Object.keys(valor).length === 0)
        );

        if (esVacio) {
            form.setError(key, "Este campo es obligatorio");
            erroresVisibles.push(label);
        }
    }

    if (form.dni && form.dni.length !== 18) {
        form.setError('dni', 'La CURP debe tener exactamente 18 caracteres');
        if (!erroresVisibles.includes("CURP")) erroresVisibles.push("CURP (18 caracteres)");
    }

    if (form.tax_id && form.tax_id.length !== 13) {
        form.setError('tax_id', 'El RFC debe tener exactamente 13 caracteres');
        if (!erroresVisibles.includes("RFC")) erroresVisibles.push("RFC (13 caracteres)");
    }

    if (erroresVisibles.length > 0) {
        showValidationError(`Faltan o son incorrectos los campos: ${erroresVisibles.join(", ")}`);
        loading.value = false;
        return;
    }

    form.transform((data) => {
        const isObject = typeof data.location_data === 'object' && data.location_data !== null;

        const parentIdsString = Array.isArray(data.parent_data)
            ? data.parent_data.map(p => (typeof p === 'object' ? p.id : p)).join(', ')
            : '';

        const payload = {
            ...data,
            location_id: isObject ? data.location_data.id : null,
            location_name: isObject ? data.location_data.name : data.location_data,
            employee_parent_id: parentIdsString,
            location_data: undefined,
            parent_data: undefined,
        };

        console.log("Objeto final enviado al backend:", payload);

        return payload;
    })
    .post(route("catalog.store"), {
        onSuccess: (page) => {
            showSuccess();
            loading.value = false;
        },
        onError: (errors) => {
            loading.value = false;
            showError();

            Object.values(errors).forEach(msg => {
                showValidationError(msg);
            });
        },
        onFinish: () => {
            loading.value = false;
        }
    });
};

const searchLocations = async (event) => {
    const term = event.query;
    if (!term || term.length < 3) return;

    loadingLocations.value = true;
    try {
        const res = await axios.get('/employee/catalog/locations-search', {
            params: {
                q: term,
                city_id: form.city_id
            }
        });
        locations.value = res.data;
    } catch (e) {
        console.error(e);
    } finally {
        loadingLocations.value = false;
    }
};

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
    try {
        const term = event.query;

        if (!term || term.length < 3) return;

        const res = await axios.get('/users/users-search', {
            params: {
                q: term,
            }
        });

        users.value = res.data;
        console.log(users.value);

    } catch (e) {
        console.error(e);
    }
};
</script>

<template>
    <AppLayout title="Crear Empleado">
        <!-- <pre>
        {{ form }}
        </pre> -->
        <form @submit.prevent="submit">
            <div class="grid grid-cols-1 md:grid-cols-1">
                <Card>
                    <template #title>
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <h3>Crear Empleado</h3>
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
                                                        <Avatar icon="pi pi-prime" shape="circle" size="xlarge"
                                                            class="bg-primary text-white shadow-3"
                                                            style="width: 7rem; height: 7rem; font-size: 2.5rem" />

                                                        <div class="field break-inside-avoid">
                                                            <label for="employee_id" class="block mb-2 font-semibold text-900">
                                                                Número de Nómina
                                                            </label>

                                                            <InputGroup class="w-full">
                                                                <InputGroupAddon>
                                                                    <i class="pi pi-id-card"></i>
                                                                </InputGroupAddon>
                                                                <InputText v-model="form.employee_id" class="w-full"
                                                                :class="{ 'p-invalid': form.errors.employee_id }"/>
                                                            </InputGroup>
                                                            <small class="p-error" v-if="form.errors.employee_id">{{
                                                                form.errors.employee_id
                                                                }}</small>
                                                        </div>
                                                    </div>

                                                    <div class="flex gap-2 flex-wrap justify-content-center">

                                                        <Button
                                                            icon="pi pi-arrow-left"
                                                            :label="loadingCatalog ? 'Redireccionando...' : 'Catálogo'"
                                                            severity="secondary"
                                                            :loading="loadingCatalog"
                                                            :disabled="loadingCatalog"
                                                            @click="goToCatalog"
                                                        />
                                                        <Button
                                                            type="submit"
                                                            icon="pi pi-save"
                                                            :label="
                                                                loading ? 'Guardando...' : 'Guardar'
                                                            "
                                                            severity="success"
                                                            :loading="loading"
                                                            :disabled="loading"
                                                        />

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
                                                        <!-- <InputText
                                                            v-model="form.personal_phone"
                                                            maxlength="10"
                                                            :class="{ 'p-invalid': form.errors.personal_phone }"
                                                        /> -->
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
                                                            placeholder="AAAA-MM-DD"
                                                            :invalid="!!form.errors.birthday"
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
                                                    <label for="location_data" class="block mb-2 font-semibold text-900">
                                                        Colonia
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-map-marker"></i>
                                                        </InputGroupAddon>

                                                        <AutoComplete
                                                            v-model="form.location_data"
                                                            :suggestions="locations"
                                                            :loading="loadingLocations"
                                                            @complete="searchLocations"
                                                            optionLabel="name"
                                                            optionValue="id"
                                                            placeholder="Escribe al menos 3 letras..."
                                                            class="w-full"
                                                            :invalid="!!form.errors.location_data"
                                                            :class="{ 'p-invalid': form.errors.location_data }"
                                                        />
                                                    </InputGroup>
                                                    <small class="p-error" v-if="form.errors.location_data">{{
                                                        form.errors.location_data
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

                                                        <InputText
                                                            v-model="form.account_number"
                                                            @input="e => {
                                                                form.account_number = e.target.value.replace(/\D/g, '')
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
                                                    <label for="account_card" class="block mb-2 font-semibold text-900">
                                                        Tarjeta
                                                    </label>
                                                    <InputGroup class="w-full">
                                                        <InputGroupAddon>
                                                            <i class="pi pi-credit-card"></i>
                                                        </InputGroupAddon>

                                                        <InputText
                                                            v-model="form.account_card"
                                                            @input="e => {
                                                                form.account_card = e.target.value.replace(/\D/g, '')
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
                                                        :invalid="!!form.errors.daily_salary"
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
                                                        :invalid="!!form.errors.salary"
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
                                                        :invalid="!!form.errors.weekly_salary"
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
                                                        <DatePicker v-model="form.entry_date"
                                                        :invalid="!!form.errors.entry_date"
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
                                                        <DatePicker v-model="form.termination_date" :class="{
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
                                                        <!-- <InputText
                                                            v-model="form.company_phone"
                                                            maxlength="10"
                                                            :class="{ 'p-invalid': form.errors.company_phone }"
                                                        /> -->
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
                                                            optionValue="id"
                                                            multiple
                                                            dropdown
                                                            class="w-full"
                                                            :class="{ 'p-invalid': form.errors.parent_data }"
                                                            placeholder="Escribe al menos 3 letras..."
                                                        >
                                                            <!-- Cómo se ve en la lista -->
                                                                <template #option="{ option }">
                                                                    <div>
                                                                        ({{ option.id }}) {{ option.full_name }}
                                                                    </div>
                                                                </template>

                                                                <!-- Cómo se ve cuando ya lo seleccionas -->
                                                                <template #value="{ value }">
                                                                    <div v-if="value">
                                                                        ({{ value.id }}) {{ value.full_name }}
                                                                    </div>
                                                                </template>
                                                        </AutoComplete>
                                                    </InputGroup>

                                                    <small class="p-error" v-if="form.errors.parent_data">
                                                        {{ form.errors.parent_data }}
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
                                                    <label for="user_data" class="block mb-2 font-semibold text-900">
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
                                                            optionLabel="username"
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
                                                            .user_data
                                                    ">{{
                                                        form.errors
                                                            .user_data
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
                                                        <!-- <InputNumber v-model="form.additional_phone" :class="{
                                                            'p-invalid':
                                                                form.errors
                                                                    .additional_phone,
                                                        }" placeholder="Teléfono" class="w-full"
                                                            :useGrouping="false" /> -->
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
    </AppLayout>
</template>
<style scoped>
.sticky-header {
    position: sticky;
    top: 70px;
    z-index: 1000;
}
</style>
