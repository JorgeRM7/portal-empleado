<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { onMounted, ref } from "vue";
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";
import axios from "axios";
import { useToastService } from "@/Stores/toastService";
import { router } from "@inertiajs/vue3";
import { useToast } from "primevue";
import { useLayout } from "@/Layouts/composables/layout";
import { useAuthz } from "@/composables/useAuthz";

const props = defineProps({
    email: String,
});

const { can } = useAuthz();

const toast = useToast();

const { showSuccess, showError } = useToastService();

const { isDark } = useLayout();

const columnsDialog = ref(false);

function getISOWeek(date = new Date()) {
    const d = new Date(
        Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()),
    );
    const dayNum = d.getUTCDay() || 7; // domingo = 7
    d.setUTCDate(d.getUTCDate() + 4 - dayNum);
    const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
    const weekNo = Math.ceil(((d - yearStart) / 86400000 + 1) / 7);
    return {
        year: d.getUTCFullYear(),
        week: weekNo,
        iso: `${d.getUTCFullYear()}-W${String(weekNo).padStart(2, "0")}`,
    };
}

const selected = ref([]);
const payrolls = ref([{}]);
const otherFilterDialog = ref(false);
const filters = ref();
const op = ref(null);
const opMostrarColumnas = ref(null);
const opFijarColumnas = ref(null);

const weekNumber = ref(getISOWeek().week);
const year = ref(getISOWeek().year);

const employeeFilter = ref();
const payrollTypeFilter = ref();
const statusFilter = ref();
const weekFilter = ref(`${year.value}-W${weekNumber.value}`);
const multipleDeleteDialog = ref(false);
const sendEmailDialog = ref(false);
const email = ref(props.email);
const idInvoice = ref(null);

const loading = ref(false);

const clearFilter = () => {
    initFilters();
};

const toggleAccionesMasivas = (event) => {
    op.value.toggle(event);
};

const toggleMostrarColumnas = (event) => {
    opMostrarColumnas.value.toggle(event);
};

const toggleFijarColumnas = (event) => {
    opFijarColumnas.value.toggle(event);
};

const showColumns = ref({
    acciones: true,
    id: true,
    planta: true,
    num_empleado: true,
    empleado: true,
    semana: true,
    anio: true,
    tipo_recibo: true,
    estatus_correo: true,
});

const exportColumns = ref({
    id: true,
    planta: true,
    num_empleado: true,
    empleado: true,
    semana: true,
    anio: true,
    tipo_recibo: true,
    estatus_correo: true,
});

const frozenColumns = ref({
    acciones: true,
    id: false,
    planta: false,
    num_empleado: false,
    empleado: false,
    semana: false,
    anio: false,
    tipo_recibo: false,
    estatus_correo: false,
});

const otherFilters = ref([
    {
        week: null,
    },
]);

const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        id: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        branch_office: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        employee_id: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        employee: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        week: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        year: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        payroll_type: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
        status: {
            operator: FilterOperator.AND,
            constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }],
        },
    };
};

const getSeverity = (estatus_correo, send_correo) => {
    if (estatus_correo == null && send_correo == 0) {
        return "warn";
    }

    if (estatus_correo == null && send_correo == 1) {
        return "info";
    }

    if (estatus_correo != null && send_correo == 1) {
        return "success";
    }
};

const getLabel = (estatus_correo, send_correo) => {
    if (estatus_correo == null && send_correo == 0) {
        return "SIN ENVIAR";
    }

    if (estatus_correo == null && send_correo == 1) {
        return "ENVIANDO";
    }

    if (estatus_correo != null && send_correo == 1) {
        return "ENVIADO";
    }
};

const applyFilters = async () => {
    loading.value = true;
    otherFilterDialog.value = false;

    let weekf = null;
    let yearf = null;

    if (weekFilter.value) {
        [yearf, weekf] = weekFilter.value.split("-W");
    }

    otherFilters.value[0].week = weekFilter.value;

    const response = await axios.get("/payroll-invoice", {
        params: {
            semana: weekf,
            anio: yearf,
        },
    });

    payrolls.value = response.data;
    loading.value = false;
};

const getData = async () => {
    loading.value = true;
    const response = await axios.get("/payroll-invoice");

    payrolls.value = response.data;
    loading.value = false;
};

const getDocument = (urlPath, invoiceId) => {
    console.log(invoiceId);
    if (urlPath.includes("invoices")) {
        window.open(`payroll-invoices/invoice-d/${invoiceId}`, "_blank");
        return;
    }

    window.open(`payroll-invoices/invoice-h/${invoiceId}`, "_blank");
};

const sendMail = () => {
    loading.value = true;
    const ids = selected.value.map((item) => item.id);
    router.post(
        `/payroll/payroll-invoices/send-mail`,
        {
            ids: ids,
        },
        {
            onSuccess: async () => {
                showSuccess();
                applyFilters();
            },
            onError: () => {
                showError();
            },
        },
    );
};

const multipleDelete = () => {
    loading.value = true;
    const ids = selected.value.map((item) => item.id);
    router.post(
        `/payroll/payroll-invoices/multiple-delete`,
        {
            ids: ids,
        },
        {
            onSuccess: async () => {
                showSuccess();
                applyFilters();
                multipleDeleteDialog.value = false;
            },
            onError: () => {
                showError();
                multipleDeleteDialog.value = false;
            },
        },
    );
};

const removeFilter = (filter) => {
    otherFilters.value[0][filter] = null;
    switch (filter) {
        case "branch_office_id":
            branchOfficeFilter.value = null;
            break;
        case "employee_id":
            employeeFilter.value = null;
            break;
        case "week":
            weekFilter.value = null;
            break;
        case "pay_roll_type":
            payrollTypeFilter.value = null;
            break;
        case "status":
            statusFilter.value = null;
            break;
    }
    applyFilters();
};

const downloadSelected = async () => {
    if (selected.value.length === 0) {
        toast.add({
            severity: "warn",
            summary: "Advertencia",
            detail: "Seleccione al menos un archivo",
            life: 3000,
        });
        return;
    }

    const ids = selected.value.map((item) => item.id);

    loading.value = true;

    try {
        const response = await axios.post(
            "/payroll/payroll-invoices/download",
            {
                ids: ids,
            },
            {
                responseType: "blob",
                validateStatus: () => true,
            },
        );

        if (response.status >= 400) {
            let errorMessage = "Error al descargar";
            let errorDetails = [];

            try {
                // Los errores vienen como texto/JSON, no como blob
                const errorText = await response.data.text();
                if (errorText) {
                    try {
                        const errorJson = JSON.parse(errorText);
                        errorMessage = errorJson.message || errorMessage;
                        errorDetails = errorJson.errores || [];
                    } catch (e) {
                        errorMessage = errorText || errorMessage;
                    }
                }
            } catch (e) {
                console.error("Error al leer mensaje de error:", e);
            }

            toast.add({
                severity: "error",
                summary: "❌ Error en la descarga",
                detail: errorMessage,
                life: 8000,
            });

            if (errorDetails.length > 0) {
                setTimeout(() => {
                    toast.add({
                        severity: "error",
                        summary: `📋 ${errorDetails.length} archivo(s) con errores`,
                        detail: errorDetails
                            .map((e) => `• ID ${e.id}: ${e.error}`)
                            .join("\n"),
                        life: 10000,
                    });
                }, 1000);
            }

            loading.value = false;
            return;
        }

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement("a");
        link.href = url;

        const errorsEncoded = response.headers["x-download-errors"];

        const disposition = response.headers["content-disposition"];
        let filename =
            "nomina_" + new Date().toISOString().slice(0, 10) + ".zip";
        if (disposition && disposition.indexOf("attachment") !== -1) {
            const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
            const matches = filenameRegex.exec(disposition);
            if (matches != null && matches[1]) {
                filename = matches[1].replace(/['"]/g, "");
            }
        }

        link.setAttribute("download", filename);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);

        if (errorsEncoded) {
            try {
                const errorsData = JSON.parse(atob(errorsEncoded));

                toast.add({
                    severity: errorsData.total_errores > 0 ? "warn" : "success",
                    summary:
                        errorsData.total_errores > 0
                            ? "⚠️ Descarga parcial completada"
                            : "✅ Descarga exitosa",
                    detail: `${errorsData.total_exitosos} de ${errorsData.total_solicitados} archivos descargados correctamente`,
                    life: 8000,
                });

                if (errorsData.total_errores > 0) {
                    setTimeout(() => {
                        toast.add({
                            severity: "warn",
                            summary: `📋 ${errorsData.total_errores} archivo(s) no descargados`,
                            detail: errorsData.errores
                                .map((e) => `• ID ${e.id}: ${e.error}`)
                                .join("\n"),
                            life: 12000,
                        });
                    }, 1500);
                }
            } catch (e) {
                console.error("Error al decodificar errores:", e);
            }
        } else {
            toast.add({
                severity: "success",
                summary: "✅ Descarga completada",
                detail: `${ids.length} archivo(s) descargado(s) correctamente`,
                life: 5000,
            });
        }
    } catch (error) {
        console.error("Error en la descarga:", error);

        let errorMessage = "Error al conectar con el servidor";

        if (error.code === "ECONNABORTED") {
            errorMessage =
                "La descarga tardó demasiado. Intente con menos archivos.";
        } else if (error.code === "ERR_NETWORK") {
            errorMessage = "Error de red. Verifique su conexión a internet.";
        } else if (error.response) {
            errorMessage =
                error.response.data?.message || "Error en el servidor";
        }

        toast.add({
            severity: "error",
            summary: "❌ Error en la descarga",
            detail: errorMessage,
            life: 8000,
        });
        if (error.response?.data?.errores) {
            setTimeout(() => {
                toast.add({
                    severity: "error",
                    summary: `📋 ${error.response.data.errores.length} archivo(s) con errores`,
                    detail: error.response.data.errores
                        .map((e) => `• ID ${e.id}: ${e.error}`)
                        .join("\n"),
                    life: 10000,
                });
            }, 1000);
        }
    } finally {
        loading.value = false;
    }
};

const sendInvoiceEmail = async () => {
    loading.value = true;
    try {
        await axios.post(route("payroll-invoices.send-mail"), {
            id_recibo: idInvoice.value,
            correo: email.value,
        });
        toast.add({
            severity: "success",
            summary: "✅ Correo enviado",
            detail: "Correo enviado correctamente",
            life: 5000,
        });
    } catch (error) {
        console.error("Error al enviar el correo:", error);

        let errorMessage = "Error al conectar con el servidor";

        if (error.code === "ECONNABORTED") {
            errorMessage =
                "El envío del correo tardó demasiado. Intente nuevamente.";
        } else if (error.code === "ERR_NETWORK") {
            errorMessage = "Error de red. Verifique su conexión a internet.";
        } else if (error.response) {
            errorMessage =
                error.response.data?.message || "Error en el servidor";
        }

        toast.add({
            severity: "error",
            summary: "❌ Error al enviar el correo",
            detail: errorMessage,
            life: 8000,
        });
    } finally {
        loading.value = false;
        sendEmailDialog.value = false;
    }
};

onMounted(() => {
    getData();
});

initFilters();
</script>

<template>
    <AppLayout title="Recibos de Nómina">
        <div class="card">
            <!-- <Toolbar>
                <template #start> </template>
                <template #end>
                    <Button
                        type="button"
                        label="Acciones Masivas"
                        class="min-w-48"
                        icon="pi pi-wrench"
                        @click="toggleAccionesMasivas"
                        :disabled="selected.length === 0"
                        v-if="can('payroll-invoices.multiple-delete')"
                    />
                    <Popover ref="op">
                        <div class="flex flex-col gap-4">
                            <div>
                                <span class="font-medium block mb-2"
                                    >Acciones Masivas</span
                                >
                                <ul class="list-none p-0 m-0 flex flex-col">
                                    <li
                                        class="flex items-center gap-2 px-1 py-1 hover:bg-emphasis cursor-pointer rounded-border"
                                    >
                                        <Button
                                            type="button"
                                            icon="pi pi-trash"
                                            label="Eliminar seleccionados"
                                            severity="danger"
                                            text
                                            class="mt-2 ml-2"
                                            @click="multipleDeleteDialog = true"
                                            v-if="
                                                can(
                                                    'payroll-invoices.multiple-delete',
                                                )
                                            "
                                        />
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </Popover>
                    <Button
                        type="button"
                        icon="pi pi-download"
                        label="Descargar"
                        severity="contrast"
                        class="ml-2"
                        :disabled="selected.length == 0 || loading"
                        :loading="loading"
                        @click="downloadSelected"
                    />
                </template>
            </Toolbar> -->
            <DataTable
                ref="dt"
                v-model:selection="selected"
                :value="payrolls"
                dataKey="id"
                :paginator="true"
                :rows="10"
                scrollable
                reorderableColumns
                scrollHeight="400px"
                :filterDelay="500"
                v-model:filters="filters"
                filterDisplay="menu"
                :globalFilterFields="[
                    'nombre_empleado',
                    'id',
                    'planta',
                    'numero_nomina',
                    'semana',
                    'year',
                    'tipo_recibo',
                ]"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} recibos de nomina"
            >
                <template #header>
                    <div
                        class="flex flex-wrap gap-2 items-end justify-between mb-6"
                    >
                        <div>
                            <h4 class="m-0">Recibos de Nómina</h4>
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
                                    v-model="filters.global.value"
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
                    <div class="flex gap-1">
                        <div class="mb-2">
                            <Chip
                                :label="'Semana: ' + otherFilters[0].week"
                                v-if="otherFilters[0].week !== null"
                                removable
                                @remove="removeFilter('week')"
                                :removable="!loading"
                            />
                        </div>
                    </div>
                </template>
                <Column
                    selectionMode="multiple"
                    style="width: 1rem"
                    :exportable="false"
                    columnKey="selection"
                ></Column>
                <Column
                    :exportable="false"
                    columnKey="acciones"
                    :style="{
                        width: '10rem',
                        display: showColumns.acciones ? '' : 'none',
                        minWidth: '10rem',
                    }"
                    header="Acciones"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <div v-else>
                            <Button
                                icon="pi pi-eye"
                                class="mr-2"
                                rounded
                                v-tooltip.top="'Ver'"
                                severity="help"
                                @click="getDocument(data.pdf_path, data.id)"
                            />
                            <Button
                                icon="pi pi-envelope"
                                class="mr-2"
                                rounded
                                v-tooltip.top="'Enviar correo'"
                                severity="info"
                                @click="
                                    sendEmailDialog = true;
                                    idInvoice = data.id;
                                "
                            />
                        </div>
                    </template>
                </Column>
                <Column
                    field="id"
                    header="#"
                    :filter="true"
                    columnKey="id"
                    :frozen="frozenColumns.id"
                    :style="{
                        width: '5rem',
                        display: showColumns.id ? '' : 'none',
                    }"
                    :exportable="exportColumns.id"
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
                    field="branch_office"
                    header="Planta"
                    :filter="true"
                    columnKey="id"
                    :frozen="frozenColumns.planta"
                    :style="{
                        width: '5rem',
                        display: showColumns.planta ? '' : 'none',
                    }"
                    :exportable="exportColumns.planta"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.planta }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Planta"
                        />
                    </template>
                </Column>
                <Column
                    field="employee_id"
                    header="Numero de Nómina"
                    :filter="true"
                    columnKey="id"
                    :frozen="frozenColumns.num_empleado"
                    :style="{
                        width: '5rem',
                        display: showColumns.num_empleado ? '' : 'none',
                    }"
                    :exportable="exportColumns.num_empleado"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.numero_nomina }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Numero de Nomina"
                        />
                    </template>
                </Column>
                <Column
                    field="employee"
                    header="Empleado"
                    :filter="true"
                    columnKey="id"
                    :frozen="frozenColumns.empleado"
                    :style="{
                        width: '10rem',
                        display: showColumns.empleado ? '' : 'none',
                    }"
                    :exportable="exportColumns.empleado"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.nombre_empleado }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Empleado"
                        />
                    </template>
                </Column>
                <Column
                    field="week"
                    header="Semana"
                    :filter="true"
                    columnKey="id"
                    :frozen="frozenColumns.semana"
                    :style="{
                        width: '5rem',
                        display: showColumns.semana ? '' : 'none',
                    }"
                    :exportable="exportColumns.semana"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.semana }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Semana"
                        />
                    </template>
                </Column>
                <Column
                    field="year"
                    header="Año"
                    :filter="true"
                    columnKey="id"
                    :frozen="frozenColumns.anio"
                    :style="{
                        width: '5rem',
                        display: showColumns.anio ? '' : 'none',
                    }"
                    :exportable="exportColumns.anio"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.year }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Año"
                        />
                    </template>
                </Column>
                <Column
                    field="payroll_type"
                    header="Tipo de Recibo"
                    :filter="true"
                    columnKey="id"
                    :frozen="frozenColumns.tipo_recibo"
                    :style="{
                        width: '5rem',
                        display: showColumns.tipo_recibo ? '' : 'none',
                    }"
                    :exportable="exportColumns.tipo_recibo"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>{{ data.tipo_recibo }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Tipo de Recibo"
                        />
                    </template>
                </Column>
                <Column
                    field="status"
                    header="Estatus Correo"
                    :filter="true"
                    columnKey="id"
                    :frozen="frozenColumns.estatus_correo"
                    :style="{
                        width: '5rem',
                        display: showColumns.estatus_correo ? '' : 'none',
                    }"
                    :exportable="exportColumns.estatus_correo"
                >
                    <template #body="{ data }">
                        <Skeleton v-if="loading"></Skeleton>
                        <span v-else>
                            <Tag
                                :severity="
                                    getSeverity(
                                        data.estatus_correo,
                                        data.send_correo,
                                    )
                                "
                                :value="
                                    getLabel(
                                        data.estatus_correo,
                                        data.send_correo,
                                    )
                                "
                            />
                        </span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText
                            v-model="filterModel.value"
                            type="text"
                            placeholder="Buscar por Estatus Correo"
                        />
                    </template>
                </Column>
            </DataTable>
            <Dialog
                v-model:visible="otherFilterDialog"
                :style="{ width: '450px' }"
                header="Seleccionar filtros adicionales"
                :modal="true"
            >
                <div class="flex flex-wrap -mx-2">
                    <!-- Semana (type=week => AAAA-WSS) -->
                    <div class="w-full md:w-full px-2 mb-4">
                        <label class="block mb-2 font-medium">Semana</label>
                        <InputText
                            v-model="weekFilter"
                            type="week"
                            class="w-full"
                        />
                    </div>
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
                    />
                </template>
            </Dialog>
            <Dialog
                v-model:visible="multipleDeleteDialog"
                :style="{ width: '600px' }"
                header="Confirmar eliminación múltiple"
                :modal="true"
            >
                <div
                    :class="{
                        'bg-red-50 border-l-4 border-red-500 p-4 rounded':
                            !isDark,
                        'bg-red-950 border-l-4 border-red-500 p-4 rounded':
                            isDark,
                    }"
                >
                    <div class="flex items-center">
                        <i
                            class="pi pi-exclamation-triangle text-red-600 text-3xl mr-3"
                        ></i>
                        <div>
                            <h3
                                :class="{
                                    'font-bold text-red-800': !isDark,
                                    'font-bold text-red-200': isDark,
                                }"
                            >
                                ¿Estás seguro de eliminar los recibos
                                seleccionados?
                            </h3>
                            <p
                                :class="{
                                    'text-sm text-red-700': !isDark,
                                    'text-sm text-red-300': isDark,
                                }"
                            >
                                Esta acción eliminará los recibos seleccionados.
                            </p>
                        </div>
                    </div>
                </div>
                <template #footer>
                    <Button
                        label="No"
                        icon="pi pi-times"
                        text
                        @click="multipleDeleteDialog = false"
                        severity="secondary"
                        variant="text"
                        :loading="loading"
                    />
                    <Button
                        label="Si"
                        icon="pi pi-check"
                        @click="multipleDelete()"
                        severity="danger"
                        :loading="loading"
                    />
                </template>
            </Dialog>
            <Dialog
                v-model:visible="sendEmailDialog"
                :style="{ width: '450px' }"
                header="Correo para enviar"
                :modal="true"
            >
                <div class="flex flex-wrap -mx-2">
                    <!-- Semana (type=week => AAAA-WSS) -->
                    <div class="w-full md:w-full px-2 mb-4">
                        <label class="block mb-2 font-medium">Correo</label>
                        <InputText
                            v-model="email"
                            type="email"
                            class="w-full"
                        />
                    </div>
                </div>

                <template #footer>
                    <Button
                        label="Cancelar"
                        icon="pi pi-times"
                        severity="danger"
                        @click="sendEmailDialog = false"
                    />
                    <Button
                        label="Enviar"
                        icon="pi pi-envelope"
                        severity="info"
                        :loading="loading"
                        @click="sendInvoiceEmail"
                    />
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>
