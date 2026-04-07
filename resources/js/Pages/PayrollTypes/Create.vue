<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { router, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import { useToast } from "primevue/usetoast";
import { useToastService } from "@/Stores/toastService";

const toast = useToast();

const { showSuccess, showError } = useToastService();

const props = defineProps({
    branchOffices: Array,
    payrollAccounts: Array,
    keys: Array,
});

const selectedBranchOffices = ref([]);
const showDialog = ref(false);
const verify = ref(false);
const loading = ref(false);
const staticFields = ref([
    {
        account_id: "",
        code: "",
        label: "",
        external_id: "",
    },
]);
const debits = ref([
    {
        account_id: "",
        code: "",
        label: "",
        key: "",
        external_id: "",
    },
]);
const credits = ref([
    {
        account_id: "",
        code: "",
        label: "",
        key: "",
        external_id: "",
    },
]);

const form = useForm({
    name: "",
    active: true,
    requires_date: true,
    apply_departments: true,
    branch_offices: "",
    static_fields: "",
    debit_fields: "",
    credit_fields: "",
});

const addStaticField = () => {
    staticFields.value.push({
        account_id: "",
        code: "",
        label: "",
        external_id: "",
    });
};

const removeStaticField = () => {
    staticFields.value.pop();
};

const addDebitField = () => {
    debits.value.push({
        account_id: "",
        code: "",
        label: "",
        key: "",
        external_id: "",
    });
};

const removeDebitField = () => {
    debits.value.pop();
};

const addCreditField = () => {
    credits.value.push({
        account_id: "",
        code: "",
        label: "",
        key: "",
        external_id: "",
    });
};

const removeCreditField = () => {
    credits.value.pop();
};

const verifyForm = () => {
    staticFields.value.forEach((field) => {
        if (
            field.account_id === "" ||
            field.code === "" ||
            field.label === "" ||
            field.external_id === ""
        ) {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Debe completar todos los campos del campo estatico",
                life: 3000,
            });
            verify.value = false;
        }
    });
    debits.value.forEach((field) => {
        if (
            field.account_id === "" ||
            field.code === "" ||
            field.label === "" ||
            field.external_id === ""
        ) {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Debe completar todos los campos del campo de debito",
                life: 3000,
            });
            verify.value = false;
        }
    });
    credits.value.forEach((field) => {
        if (
            field.account_id === "" ||
            field.code === "" ||
            field.label === "" ||
            field.external_id === ""
        ) {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Debe completar todos los campos del campo de credito",
                life: 3000,
            });
            verify.value = false;
        }
    });
    verify.value = true;
};

const submit = () => {
    verifyForm();
    loading.value = true;
    if (verify.value) {
        form.static_fields = JSON.stringify(staticFields.value);
        form.debit_fields = JSON.stringify(debits.value);
        form.credit_fields = JSON.stringify(credits.value);
        form.branch_offices = selectedBranchOffices.value.map(
            (branchOffice) => branchOffice.id,
        );
        form.post(route("payroll-types.store"), {
            onSuccess: () => {
                loading.value = false;
                showSuccess();
            },
            onError: () => {
                loading.value = false;
                showError();
            },
        });
    }
};

console.log(props.keys);
</script>

<template>
    <AppLayout :title="'Crear Tipo de Asiento'">
        <pre>
            <!-- {{ props.payrollAccounts }} -->
            <!-- {{ props.keys }} -->
        </pre>
        <div class="card">
            <h4 class="mb-0">Campos Generales</h4>
            <div class="space-y-4 mt-4">
                <label for="name" class="block">Nombre</label>
                <InputText
                    placeholder="Nombre del tipo de asiento"
                    class="w-full"
                    id="name"
                    v-model="form.name"
                />
                <small v-if="form.errors.name" class="text-red-500"
                    >El nombre es requerido</small
                >
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-4">
                <div>
                    <label class="block mb-2">Activo</label>
                    <ToggleSwitch v-model="form.active" />
                </div>
                <div>
                    <label class="block mb-2">Requiere fecha</label>
                    <ToggleSwitch v-model="form.requires_date" />
                </div>
                <div>
                    <label class="block mb-2">Aplica Departamento</label>
                    <ToggleSwitch v-model="form.apply_departments" />
                </div>
            </div>
        </div>
        <div class="card">
            <h4 class="mb-0">Plantas</h4>
            <div class="space-y-4 mt-4">
                <MultiSelect
                    v-model="selectedBranchOffices"
                    :options="props.branchOffices"
                    optionLabel="code"
                    placeholder="Seleccionar plantas"
                    filter
                    class="w-full"
                    display="chip"
                />

                <small v-if="form.errors.branch_offices" class="text-red-500"
                    >Debe seleccionar al menos una planta</small
                >
            </div>
        </div>
        <div class="card">
            <h4 class="mb-0">Campos</h4>
            <Tabs value="0">
                <TabList>
                    <Tab value="0">Estaticos</Tab>
                    <Tab value="1">Debito</Tab>
                    <Tab value="2">Credito</Tab>
                </TabList>
                <TabPanels>
                    <TabPanel value="0">
                        <div
                            class="flex justify-between items-center gap-2 mb-5"
                            v-for="(staticField, index) in staticFields"
                            :key="index"
                        >
                            <Select
                                v-model="staticField.account_id"
                                :options="props.payrollAccounts"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Seleccionar cuenta"
                                class="w-full"
                                filter
                                showClear
                                @update:modelValue="
                                    (id) => {
                                        const acc = props.payrollAccounts.find(
                                            (a) => String(a.id) === String(id),
                                        );
                                        staticField.code = acc?.number ?? null;
                                        staticField.external_id =
                                            acc?.code ?? null;
                                    }
                                "
                            />
                            <InputText
                                v-model="staticField.code"
                                placeholder="Codigo"
                                class="w-full"
                                disabled
                            />
                            <InputText
                                v-model="staticField.label"
                                placeholder="Etiqueta"
                                class="w-full"
                            />

                            <div class="flex items-center justify-center">
                                <Button
                                    severity="danger"
                                    icon="pi pi-trash"
                                    size="small"
                                    label="Eliminar"
                                    @click="removeStaticField"
                                />
                            </div>
                        </div>
                        <div class="flex justify-center">
                            <Button
                                label="Agregar"
                                severity="success"
                                icon="pi pi-plus"
                                class="w-1/3"
                                @click="addStaticField"
                            />
                        </div>
                    </TabPanel>
                    <TabPanel value="1">
                        <div
                            class="grid gap-2 mb-5 items-center"
                            style="grid-template-columns: 2fr 1fr 1.5fr 1fr auto;"
                            v-for="(debitField, index) in debits"
                            :key="index"
                        >
                            <Select
                                v-model="debitField.account_id"
                                :options="props.payrollAccounts"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Seleccionar cuenta"
                                class="w-full"
                                filter
                                showClear
                                @update:modelValue="
                                    (id) => {
                                        const acc = props.payrollAccounts.find(
                                            (a) => String(a.id) === String(id),
                                        );
                                        debitField.code = acc?.number ?? null;
                                        debitField.external_id =
                                            acc?.code ?? null;
                                    }
                                "
                            />
                            <InputText
                                v-model="debitField.code"
                                placeholder="Codigo"
                                class="w-full"
                                disabled
                            />
                            <InputText
                                v-model="debitField.label"
                                placeholder="Etiqueta"
                                class="w-full"
                            />
                            <Select
                                v-model="debitField.key"
                                :options="props.keys"
                                placeholder="Seleccionar llave"
                                class="w-full"
                                filter
                                showClear
                            />

                            <div class="flex items-center justify-center">
                                <Button
                                    severity="danger"
                                    icon="pi pi-trash"
                                    size="small"
                                    label="Eliminar"
                                    @click="removeDebitField"
                                />
                            </div>
                        </div>
                        <div class="flex justify-center">
                            <Button
                                label="Agregar"
                                severity="success"
                                icon="pi pi-plus"
                                class="w-1/3"
                                @click="addDebitField"
                            />
                        </div>
                    </TabPanel>
                    <TabPanel value="2">
                        <div
                            class="grid gap-2 mb-5 items-center"
                            style="grid-template-columns: 2fr 1fr 1.5fr 1fr auto;"
                            v-for="(creditField, index) in credits"
                            :key="index"
                        >
                            <Select
                                v-model="creditField.account_id"
                                :options="props.payrollAccounts"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Seleccionar cuenta"
                                class="w-full"
                                filter
                                showClear
                                @update:modelValue="
                                    (id) => {
                                        const acc = props.payrollAccounts.find(
                                            (a) => String(a.id) === String(id),
                                        );
                                        creditField.code = acc?.number ?? null;
                                        creditField.external_id =
                                            acc?.code ?? null;
                                    }
                                "
                            />
                            <InputText
                                v-model="creditField.code"
                                placeholder="Codigo"
                                class="w-full"
                                disabled
                            />
                            <InputText
                                v-model="creditField.label"
                                placeholder="Etiqueta"
                                class="w-full"
                            />
                            <Select
                                v-model="creditField.key"
                                :options="props.keys"
                                placeholder="Seleccionar llave"
                                class="w-full"
                                filter
                                showClear
                            />

                            <div class="flex items-center justify-center">
                                <Button
                                    severity="danger"
                                    icon="pi pi-trash"
                                    size="small"
                                    label="Eliminar"
                                    @click="removeCreditField"
                                />
                            </div>
                        </div>
                        <div class="flex justify-center">
                            <Button
                                label="Agregar"
                                severity="success"
                                icon="pi pi-plus"
                                class="w-1/3"
                                @click="addCreditField"
                            />
                        </div>
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </div>
        <div class="card">
            <div class="flex justify-end gap-2">
                <Button
                    label="Cancelar"
                    severity="secondary"
                    @click="router.visit(route('/payroll-types'))"
                    :loading="loading"
                />
                <Button
                    label="Guardar"
                    severity="primary"
                    @click="showDialog = true"
                    :loading="loading"
                />
            </div>
        </div>

        <Dialog
            v-model:visible="showDialog"
            :header="'Estás seguro de realizar esta acción?'"
            :style="{ width: '400px' }"
            :modal="true"
        >
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span
                    >Al confirmar, se creara un nuevo tipo de asiento con los
                    datos proporcionados.</span
                >
            </div>
            <template #footer>
                <Button
                    label="Cancelar"
                    icon="pi pi-times"
                    severity="secondary"
                    @click="showDialog = false"
                    :loading="loading"
                />
                <Button
                    label="Confirmar"
                    icon="pi pi-check"
                    severity="success"
                    @click="submit()"
                    :loading="loading"
                />
            </template>
        </Dialog>
    </AppLayout>
</template>
