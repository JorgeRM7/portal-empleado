<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import axios from "axios";
import { ref, computed, onMounted } from "vue";
import * as XLSX from "xlsx";
import { FilterMatchMode } from '@primevue/core/api';
import { usePage } from "@inertiajs/vue3";
import { router } from '@inertiajs/vue3';
import { useToastService } from "../../Stores/toastService";

const props = defineProps({
    employees: Array,
    employee_id: Number
});

const { showError, showSuccess, showValidationError } = useToastService();

const selectedEmployee = ref(null);
const loading = ref(false);
const employeeData = ref(null);
const activeTab = ref(0);

const getEfficiencySeverity = (value) => {
    if (!value) return "secondary";
    const num = parseFloat(value);
    if (num >= 100) return "success";
    if (num >= 80) return "info";
    if (num >= 60) return "warn";
    return "danger";
};

const getContrastColor = (hexColor) => {
    if (!hexColor || !/^#([0-9A-F]{3}){1,2}$/i.test(hexColor)) {
        return "#000000";
    }

    let hex = hexColor.replace("#", "");
    if (hex.length === 3) {
        hex = hex
            .split("")
            .map((c) => c + c)
            .join("");
    }

    const r = parseInt(hex.substring(0, 2), 16);
    const g = parseInt(hex.substring(2, 4), 16);
    const b = parseInt(hex.substring(4, 6), 16);

    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

    return luminance < 0.6 ? "#ffffff" : "#000000";
};

const getTagStyle = (hexColor) => {
    if (!hexColor) {
        return {};
    }

    const textColor = getContrastColor(hexColor);

    return {
        backgroundColor: hexColor,
        color: textColor,
        borderColor: hexColor,
        fontWeight: "500",
    };
};

const getEmployeeData = async () => {
    if (!selectedEmployee.value) return;

    loading.value = true;
    employeeData.value = null;

    try {
        const response = await axios.get("/api/employees-search", {
            params: { employee_id: selectedEmployee.value },
        });

        console.log(response);

        // Asumimos que el backend regresa { empleado: [ {...} ], ... }
        // Tomamos el primer elemento ya que es un registro único
        if (response.data.empleado?.length > 0) {
            employeeData.value = {
                info: response.data.empleado[0],
                incidencias: response.data.incidencias || [],
                vacaciones: response.data.vacaciones || [],
                asistencia_semanal: response.data.asistencia_semanal || [],
                eficiencias: response.data.eficiencias || [],
                roles_turnos: response.data.roles_turnos || [],
                ciclos_turno: response.data.ciclos_turno || [],
                compensaciones: response.data.compensaciones || [],
                tiempo_extra: response.data.tiempo_extra || [],
                historial_estados: response.data.historial_estados || [],
                recibos_nomina: response.data.recibos_nomina || [],
                tiempoxtiempo: response.data.tiempo_x_tiempo || [],
            };
            activeTab.value = 0;
        }

        console.log(response);
    } catch (error) {
        console.error("Error fetching data:", error);
    } finally {
        loading.value = false;
    }
};

const timefortimeCols = [
    { field: "employee_id", header: "Clave" },
    { field: "employee_name", header: "Empleado" },
    { field: "date", header: "Fecha" },
    { field: "hours", header: "Total Horas TXT" },
    { field: "estado", header: "Estado" },
    { field: "approved_by_name", header: "Aprobado por" },
    { field: "comment", header: "Comentarios" },
];

const totalHoras = computed(() => {
    return (employeeData.value?.tiempoxtiempo || [])
        .reduce((acc, r) => acc + (Number(r.hours) || 0), 0);
});

const incidenceCols = [
    { field: "incidencia", header: "Incidencia" },
    { field: "document_number", header: "Folio/Doc" },
    { field: "validity_from", header: "Vigencia Desde", body: "date" },
    { field: "validity_to", header: "Vigencia Hasta", body: "date" },
    { field: "days", header: "Días" },
    { field: "approved_by", header: "Aprobado Por" },
    { field: "comment", header: "Comentarios" },
];

const vacationCols = [
    { field: "seniority", header: "Antigüedad" },
    { field: "amount", header: "Días" },
    { field: "date", header: "Fecha Asignación", body: "date" },
    { field: "branch_office_id", header: "Sucursal ID" },
];

const totalVacationDays = computed(() => {
    if (!employeeData.value.vacaciones) return 0;
    return employeeData.value.vacaciones.reduce((sum, item) => {
        return sum + (parseFloat(item.amount) || 0);
    }, 0);
});

const attendanceCols = [
    { field: "week_number", header: "Semana" },
    { field: "week_year", header: "Año" },
    { field: "lunes", header: "Lun", body: "badge" },
    { field: "martes", header: "Mar", body: "badge" },
    { field: "miercoles", header: "Mié", body: "badge" },
    { field: "jueves", header: "Jue", body: "badge" },
    { field: "viernes", header: "Vie", body: "badge" },
    { field: "sabado", header: "Sáb", body: "badge" },
    { field: "domingo", header: "Dom", body: "badge" },
];

const efficiencyCols = [
    { field: "code", header: "Sucursal" },
    { field: "month", header: "Mes" },
    { field: "year", header: "Año" },
    { field: "efficiency", header: "Eficiencia", body: "progress" },
];

const shiftCyclesCols = [
    { field: "name", header: "Turno" },
    { field: "entry_time", header: "Entrada" },
    { field: "leave_time", header: "Salida" },
    { field: "started_at", header: "Inicio Ciclo" },
    { field: "ends_at", header: "Fin Ciclo" },
];

const shiftRoleCols = [
    { field: "name", header: "Turno" },
    { field: "start_date", header: "Inicio Ciclo" },
    { field: "end_date", header: "Fin Ciclo" },
    { field: "active", header: "Estatus" },
];

const compensationsCols = [
    { field: "ID", header: "ID" },
    { field: "Empleado", header: "Empleado" },
    { field: "Departamento", header: "Departamento" },
    { field: "Posicion", header: "Puesto" },
    { field: "CompePuesto", header: "Compensación del puesto" },
    { field: "Compensacion", header: "compensación" },
    { field: "Destajo", header: "Destajo" },
    { field: "ExtraCompensacion", header: "Compensación extra" },
    { field: "Transporte", header: "Apoyo trasporte" },
    { field: "Semana", header: "Semana" },
    { field: "Aprovado", header: "Aprobado por" },
    { field: "FechaAprovado", header: "Fecha aprobación" },
];

const statusHistoryCols = [
    { field: "id", header: "ID" },
    { field: "employee_id", header: "Clave empleado" },
    { field: "employee_name", header: "Nombre empleado" },
    { field: "reason_name", header: "Razón" },
    { field: "status", header: "Estatus" },
    { field: "date", header: "Fecha" },
    { field: "content", header: "Observaciones" },
    { field: "user_name", header: "Creado por" },
    { field: "user_name", header: "Valores" },
];

const extraTimeCols = [
    { field: "EmpleadoID", header: "Clave empleado" },
    { field: "Empleado", header: "Empleado" },
    { field: "Fecha", header: "Fecha" },
    { field: "double_overtime", header: "Horas dobles" },
    { field: "triple_overtime", header: "Horas triples" },
    { field: "total_hours", header: "Total horas" },
    { field: "sunday_premium", header: "Prima Dominical" },
    { field: "Extemporaneo", header: "Extemporáneo" },
    { field: "status_extra", header: "Estado" },
];

const payrollReceiptsCols = [
    { field: "acciones", header: "PDF" },
    { field: "id", header: "#" },
    { field: "planta", header: "Planta" },
    { field: "numero_nomina", header: "Número de nómina" },
    { field: "nombre_empleado", header: "Empleado" },
    { field: "semana", header: "Semana" },
    { field: "year", header: "Año" },
    { field: "tipo_recibo", header: "Tipo de recibo" },
    { field: "estatus_correo", header: "Estatus correo" },
    { field: "error_correo", header: "Error de correo" },
];

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const viewPDF = (path, id_recibo) => {
    if (!path.toLowerCase().endsWith('.pdf')) {
        showValidationError("Este archivo no es un PDF y no se puede visualizar aquí.");
        return;
    }
    if (!id_recibo) {
        console.error("ID de factura no proporcionado");
        return;
    }
    const subRoute = path.includes("invoices") ? 'invoice-d' : 'invoice-h';
    window.open(`/payroll/payroll-invoices/${subRoute}/${id_recibo}`, "_blank");
};

const parseExtraTime = (jsonString) => {
    try {
        return jsonString ? JSON.parse(jsonString) : {};
    } catch (e) {
        return {};
    }
};

const getStatusLabel = (status) => {
    const labels = {
        entry: "Ingreso",
        reentry: "Reingreso",
        change: "Traspaso",
        termination: "Terminación",
    };
    return labels[status] || status;
};

const getStatusSeverity = (status) => {
    const severities = {
        entry: "success",
        reentry: "info",
        change: "warn",
        termination: "danger",
    };
    return severities[status] || "secondary"; // Gris por defecto
};

// Mapea el status de la BD a etiqueta visible y severidad del Tag
const getStatusInfo = (status) => {
    const statusMap = {
        entry: { label: "Ingreso", severity: "success" },
        reentry: { label: "Reingreso", severity: "info" },
        termination: { label: "Baja", severity: "danger" },
        change: { label: "Traspaso", severity: "warn" },
        active: { label: "Activo", severity: "success" },
    };
    return statusMap[status] || { label: status, severity: "secondary" };
};

const formatDate = (dateString) => {
    if (!dateString) return "—";

    const date = new Date(dateString + "T00:00:00Z");
    if (isNaN(date.getTime())) return "—";

    const day = date.getUTCDate();
    const month = date.getUTCMonth();
    const year = date.getUTCFullYear();

    const months = [
        "ene",
        "feb",
        "mar",
        "abr",
        "may",
        "jun",
        "jul",
        "ago",
        "sep",
        "oct",
        "nov",
        "dic",
    ];

    return `${day} ${months[month]} ${year}`;
};

const getRehireableInfo = (rehirable, status) => {
    if (status !== "termination") return null;

    if (rehirable === 1 || rehirable === true) {
        return {
            label: "Recontratable",
            severity: "success",
            icon: "pi pi-refresh",
        };
    }
    return {
        label: "No recontratable",
        severity: "danger",
        icon: "pi pi-times",
    };
};

const calculateSeniority = (startDate, endDate = null) => {
    if (!startDate) return "—";

    const startParts = startDate
        .toString()
        .split("T")[0]
        .split("-")
        .map(Number);
    const [startYear, startMonth, startDay] = startParts;

    if (!startYear || !startMonth || !startDay) return "—";

    let endYear, endMonth, endDay;

    if (endDate) {
        const endParts = endDate
            .toString()
            .split("T")[0]
            .split("-")
            .map(Number);
        [endYear, endMonth, endDay] = endParts;
        if (!endYear || !endMonth || !endDay) return "—";
    } else {
        const now = new Date();
        endYear = now.getFullYear();
        endMonth = now.getMonth() + 1;
        endDay = now.getDate();
    }

    let years = endYear - startYear;
    let months = endMonth - startMonth;

    if (endDay < startDay) {
        months -= 1;
    }

    if (months < 0) {
        years -= 1;
        months += 12;
    }

    if (years > 0 && months > 0) {
        return `${years} ${years === 1 ? "año" : "años"}, ${months} ${months === 1 ? "mes" : "meses"}`;
    } else if (years > 0) {
        return `${years} ${years === 1 ? "año" : "años"}`;
    } else if (months > 0) {
        return `${months} ${months === 1 ? "mes" : "meses"}`;
    } else {
        return "Menos de 1 mes";
    }
};

const exportToXLSX = (data, filename, sheetName = "Datos") => {
    if (!data || data.length === 0) {
        alert("No hay datos para exportar");
        return;
    }

    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.json_to_sheet(data);

    const wscols = Object.keys(data[0]).map((key) => ({ wch: 20 }));
    ws["!cols"] = wscols;

    XLSX.utils.book_append_sheet(wb, ws, sheetName);

    XLSX.writeFile(wb, `${filename}.xlsx`);
};

const exportToCSV = (data, filename) => {
    if (!data || data.length === 0) {
        alert("No hay datos para exportar");
        return;
    }

    const headers = Object.keys(data[0]);
    const csvContent = [
        headers.join(","),
        ...data.map((row) =>
            headers
                .map((field) => {
                    const value = row[field];
                    const escaped = String(value ?? "").replace(/"/g, '""');
                    return `"${escaped}"`;
                })
                .join(","),
        ),
    ].join("\n");

    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = `${filename}.csv`;
    link.click();
};

const copyToClipboard = async (data, tableName) => {
    if (!data || data.length === 0) {
        alert("No hay datos para copiar");
        return;
    }

    const headers = Object.keys(data[0]);

    const content = [
        headers.join("\t"),
        ...data.map((row) =>
            headers.map((field) => String(row[field] ?? "")).join("\t"),
        ),
    ].join("\n");

    try {
        await navigator.clipboard.writeText(content);
        alert(
            `✅ ${data.length} registros de "${tableName}" copiados al portapapeles`,
        );
    } catch (err) {
        console.error("Error al copiar:", err);
        alert("❌ No se pudo copiar al portapapeles");
    }
};

const printTable = (tableId, title) => {
    const table = document.getElementById(tableId);

    if (!table) {
        alert("❌ No se encontró la tabla para imprimir");
        return;
    }

    const tableHTML =
        table.querySelector("table")?.outerHTML || table.outerHTML;

    const printWindow = window.open("", "_blank", "width=1200,height=800");

    if (!printWindow) {
        alert(
            "⚠️ El navegador bloqueó la ventana emergente. Por favor permite los popups para este sitio.",
        );
        return;
    }

    const html = `<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${title}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/primeicons@1.13.0/primeicons.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            padding: 20px;
            color: #333;
            background: white;
        }
        h1 {
            color: #1e40af;
            border-bottom: 3px solid #1e40af;
            padding-bottom: 15px;
            margin-bottom: 20px;
            font-size: 24px;
        }
        h2 {
            color: #666;
            font-size: 16px;
            margin-bottom: 20px;
            font-weight: normal;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 12px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px 12px;
            text-align: left;
        }
        th {
            background-color: #1e40af;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        tr:hover {
            background-color: #f3f4f6;
        }
        .header-info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .header-info p {
            margin: 5px 0;
            font-size: 13px;
        }
        .header-info strong {
            color: #1e40af;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            font-size: 11px;
            color: #6b7280;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .no-print {
            display: none;
        }
        .btn-print {
            background: #1e40af;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            margin-right: 10px;
        }
        .btn-print:hover {
            background: #1e3a8a;
        }
        .btn-close {
            background: #dc2626;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
        }
        .btn-close:hover {
            background: #b91c1c;
        }
        @media print {
            body { padding: 0; }
            .no-print, .btn-print, .btn-close { display: none !important; }
            .footer { border-top: 1px solid #ddd; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
        }
    </style>
</head>
<body>
    <h1>${title}</h1>

    <div class="header-info">
        <p><strong>Empleado:</strong> ${employeeData.value?.info?.full_name || "N/A"}</p>
        <p><strong>RFC/CURP:</strong> ${employeeData.value?.info?.tax_id || "N/A"}</p>
        <p><strong>Fecha de consulta:</strong> ${new Date().toLocaleString("es-MX")}</p>
    </div>

    ${tableHTML}

    <div class="footer">
        <div>
            <p>Generado el: ${new Date().toLocaleString("es-MX")}</p>
            <p>Sistema de Gestión de Empleados</p>
        </div>
        <div class="no-print">
            <button class="btn-print" onclick="window.print()">🖨️ Imprimir</button>
            <button class="btn-close" onclick="window.close()">❌ Cerrar</button>
        </div>
    </div>

    <script>
        // Auto-imprimir al cargar (opcional, comenta si no lo quieres)
        // window.onload = function() { window.print(); }
    <\/script>
</body>
</html>`;

    printWindow.document.open();
    printWindow.document.write(html);
    printWindow.document.close();

    // Enfocar la ventana
    printWindow.focus();
};

// Función para preparar datos limpios para exportación (quitar campos técnicos)
const prepareExportData = (
    data,
    excludeFields = ["id", "id_original", "employee_id", "branch_office_id"],
) => {
    if (!data || data.length === 0) return [];

    return data.map((row) => {
        const cleanRow = { ...row };
        excludeFields.forEach((field) => delete cleanRow[field]);
        return cleanRow;
    });
};

onMounted(() => {
    if (props.employee_id) {
        selectedEmployee.value = Number(props.employee_id);
        getEmployeeData();
    }
});
</script>

<template>
    <AppLayout :title="'Detalle de Empleado'">
        <!-- 1. Panel de Búsqueda -->
        <!-- <pre>
            {{ employeeData }}
         </pre> -->
        <div class="card">
            <div class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-1 w-full">
                    <label class="block font-semibold mb-2">Empleado</label>
                    <Select
                        v-model="selectedEmployee"
                        :options="props.employees"
                        option-label="full_name"
                        option-value="id"
                        filter
                        :filterFields="['full_name', 'id']"
                        placeholder="Buscar por nombre o ID..."
                        class="w-full"
                    >
                        <template #option="slotProps">
                            <div class="flex items-center">
                                <span class="ml-2"
                                    >({{ slotProps.option.id }})
                                    {{ slotProps.option.full_name }}</span
                                >
                            </div>
                        </template>
                    </Select>
                </div>
                <Button
                    label="Buscar"
                    icon="pi pi-search"
                    @click="getEmployeeData"
                    :loading="loading"
                    severity="primary"
                />
            </div>
        </div>

        <div v-if="employeeData" class="space-y-6">
            <div
                class="card"
            >
                <div class="flex flex-col md:flex-row gap-6 items-start">
                    <div class="mx-auto md:mx-0">
                        <div
                            class="w-24 h-24 bg-blue-100 dark:bg-blue-800 rounded-full flex items-center justify-center text-blue-600 dark:text-blue-200 text-3xl font-bold border-4 border-white dark:border-gray-700 shadow-sm"
                        >
                            {{ employeeData.info.full_name?.charAt(0) || "E" }}
                        </div>
                    </div>

                    <div
                        class="flex-1 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-x-6 gap-y-4 w-full"
                    >
                        <div class="md:col-span-2">
                            <span
                                class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide"
                                >Nombre Completo</span
                            >
                            <p
                                class="text-lg font-bold text-gray-900 dark:text-gray-100 mt-1"
                            >
                                {{ employeeData.info.full_name }}
                            </p>
                            <p
                                class="text-sm text-gray-500 dark:text-gray-400 font-mono mt-1"
                            >
                                <i class="pi pi-id-card mr-1"></i>
                                {{ employeeData.info.tax_id || "Sin RFC/CURP" }}
                            </p>
                        </div>
                        <div
                            class="md:col-span-2 flex items-end justify-start md:justify-end"
                        >
                            <div class="text-right">
                                <span
                                    class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide block"
                                    >Estatus Actual</span
                                >
                                <Tag
                                    :value="
                                        getStatusInfo(employeeData.info.status)
                                            .label
                                    "
                                    :severity="
                                        getStatusInfo(employeeData.info.status)
                                            .severity
                                    "
                                    class="mt-1 text-sm px-3 py-1"
                                />
                            </div>
                        </div>

                        <div>
                            <span
                                class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide"
                                >Puesto</span
                            >
                            <p
                                class="font-semibold text-gray-800 dark:text-gray-200 mt-1"
                            >
                                {{ employeeData.info.position_name || "—" }}
                            </p>
                        </div>
                        <div>
                            <span
                                class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide"
                                >Departamento</span
                            >
                            <p
                                class="font-semibold text-gray-800 dark:text-gray-200 mt-1"
                            >
                                {{ employeeData.info.department_name || "—" }}
                            </p>
                        </div>

                        <div>
                            <span
                                class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide"
                                >Fecha de Ingreso</span
                            >
                            <p
                                class="font-medium text-gray-700 dark:text-gray-300 mt-1 flex items-center gap-2"
                            >
                                <i
                                    class="pi pi-calendar-plus text-green-600"
                                ></i>
                                {{ formatDate(employeeData.info.entry_date) }}
                            </p>
                        </div>
                        <div>
                            <span
                                class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide"
                                >Fecha de Reingreso</span
                            >
                            <p
                                class="font-medium text-gray-700 dark:text-gray-300 mt-1 flex items-center gap-2"
                            >
                                <i class="pi pi-calendar text-blue-600"></i>
                                {{ formatDate(employeeData.info.reentry_date) }}
                            </p>
                        </div>

                        <div>
                            <span
                                class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide"
                                >Fecha de Baja</span
                            >
                            <p
                                class="font-medium text-gray-700 dark:text-gray-300 mt-1 flex items-center gap-2"
                            >
                                <i class="pi pi-calendar text-red-600"></i>
                                {{ formatDate(employeeData.info.termination_date) }}
                            </p>
                        </div>

                        <!-- <div>
                            <span
                                class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide"
                                >Fecha de Baja</span
                            >
                            <p
                                class="font-medium mt-1 flex items-center gap-2"
                            >
                                <i class="pi pi-calendar-times"></i>
                                {{
                                    formatDate(
                                        employeeData.info
                                            .termination_date,
                                    )
                                }}
                            </p>
                        </div> -->

                        <div
                            class="xl:col-span-2 flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400"
                        >
                            <span class="flex items-center gap-1.5">
                                <i class="pi pi-building text-gray-600"></i>
                                {{
                                    employeeData.info.branch_office_name || "—"
                                }}
                            </span>
                            <span class="flex items-center gap-1.5">
                                <i class="pi pi-user text-gray-600"></i>
                                Numero de Empleado:
                                {{ employeeData.info.id || "—" }}
                            </span>
                        </div>

                        <div>
                            <span
                                class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide"
                            >
                                Antigüedad
                            </span>
                            <p
                                class="font-semibold text-gray-800 dark:text-gray-200 mt-1 flex items-center gap-2"
                            >
                                <i class="pi pi-stopwatch text-purple-600"></i>
                                {{
                                    calculateSeniority(
                                        employeeData.info.entry_date,
                                        employeeData.info.termination_date,
                                    )
                                }}
                            </p>
                            <span
                                class="text-xs text-gray-400 dark:text-gray-500 block mt-0.5"
                            >
                                <i class="pi pi-info-circle mr-1"></i>
                                Desde
                                {{ formatDate(employeeData.info.entry_date) }}
                                <span v-if="employeeData.info.termination_date">
                                    hasta
                                    {{
                                        formatDate(
                                            employeeData.info.termination_date,
                                        )
                                    }}
                                </span>
                                <span v-else>hasta hoy</span>
                            </span>
                        </div>

                        <div
                            class="xl:col-span-2 flex justify-start xl:justify-end items-end"
                        >
                            <div
                                v-if="
                                    employeeData.info.status === 'termination'
                                "
                                class="text-right"
                            >
                                <span
                                    class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide block mb-1"
                                    >Elegible para Reingreso</span
                                >
                                <Tag
                                    :value="
                                        getRehireableInfo(
                                            employeeData.info.rehireable,
                                            employeeData.info.status,
                                        )?.label
                                    "
                                    :severity="
                                        getRehireableInfo(
                                            employeeData.info.rehireable,
                                            employeeData.info.status,
                                        )?.severity
                                    "
                                    :icon="
                                        getRehireableInfo(
                                            employeeData.info.rehireable,
                                            employeeData.info.status,
                                        )?.icon
                                    "
                                />
                            </div>
                        </div>

                        <div
                            v-if="employeeData.info.status === 'termination'"
                            class="md:col-span-4 pt-4 border-t border-blue-200 dark:border-blue-800"
                        >
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                                <div class="md:col-span-2">
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                        Motivo de Terminación
                                    </span>
                                    <p class="font-medium text-gray-800 dark:text-gray-200 mt-1 flex flex-col gap-1">
                                        <span class="flex items-start gap-2">
                                            <i class="pi pi-info-circle text-red-500 mt-1"></i>
                                            <span class="font-bold">
                                                {{ employeeData.info.termination_reason || "Sin especificar" }}
                                            </span>
                                        </span>
                                        <span v-if="employeeData.info.termination_observation" class="text-sm text-gray-600 dark:text-gray-400 italic ml-6">
                                            "{{ employeeData.info.termination_observation }}"
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- <pre>
                {{ employeeData }}
            </pre> -->
            <TabView v-model:activeIndex="activeTab">
                <!-- Tab: Incidencias -->
                <TabPanel header="Incidencias">
                    <div class="flex flex-wrap gap-2 mb-4 no-print">
                        <Button
                            label="Excel"
                            icon="pi pi-file-excel"
                            severity="success"
                            size="small"
                            @click="
                                exportToXLSX(
                                    prepareExportData(
                                        employeeData.incidencias,
                                        ['id', 'employee_id'],
                                    ),
                                    `Incidencias_${employeeData.info.full_name?.replace(/\s+/g, '_')}`,
                                )
                            "
                        />
                        <Button
                            label="CSV"
                            icon="pi pi-file"
                            severity="info"
                            size="small"
                            @click="
                                exportToCSV(
                                    prepareExportData(
                                        employeeData.incidencias,
                                        ['id', 'employee_id'],
                                    ),
                                    `Incidencias_${employeeData.info.full_name?.replace(/\s+/g, '_')}`,
                                )
                            "
                        />
                        <Button
                            label="Copiar"
                            icon="pi pi-copy"
                            severity="secondary"
                            size="small"
                            @click="
                                copyToClipboard(
                                    prepareExportData(
                                        employeeData.incidencias,
                                        ['id', 'employee_id'],
                                    ),
                                    'Incidencias',
                                )
                            "
                        />
                        <Button
                            label="Imprimir"
                            icon="pi pi-print"
                            severity="warn"
                            size="small"
                            @click="
                                printTable(
                                    'table-incidencias',
                                    'Reporte de Incidencias',
                                )
                            "
                        />
                        <span class="flex-1"></span>
                        <span class="text-sm text-gray-500 self-center">
                            <i class="pi pi-info-circle mr-1"></i>
                            {{ employeeData.incidencias?.length || 0 }}
                            registros
                        </span>
                    </div>
                    <div id="table-incidencias">
                        <DataTable
                            :value="employeeData.incidencias"
                            tableStyle="min-width: 50rem"
                            stripedRows
                            size="small"
                            :paginator="true"
                            :rows="10"
                        >
                            <template #empty>
                                <div class="text-center py-4 text-gray-500">
                                    No se registraron incidencias.
                                </div>
                            </template>
                            <Column
                                v-for="col of incidenceCols"
                                :key="col.field"
                                :field="col.field"
                                :header="col.header"
                            >
                                <template
                                    #body="slotProps"
                                    v-if="col.body === 'date'"
                                >
                                    {{ formatDate(slotProps.data[col.field]) }}
                                </template>
                                <template #body="slotProps" v-else>
                                    {{ slotProps.data[col.field] }}
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </TabPanel>

                <!-- Tab: Vacaciones -->
                <TabPanel header="Vacaciones">
                    <div class="flex flex-wrap gap-2 mb-4 no-print">
                        <Button
                            label="Excel"
                            icon="pi pi-file-excel"
                            severity="success"
                            size="small"
                            @click="
                                exportToXLSX(
                                    prepareExportData(employeeData.vacaciones, [
                                        'id',
                                        'employee_id',
                                        'branch_office_id',
                                    ]),
                                    `Vacaciones_${employeeData.info.full_name?.replace(/\s+/g, '_')}`,
                                )
                            "
                        />
                        <Button
                            label="CSV"
                            icon="pi pi-file"
                            severity="info"
                            size="small"
                            @click="
                                exportToCSV(
                                    prepareExportData(employeeData.vacaciones, [
                                        'id',
                                        'employee_id',
                                        'branch_office_id',
                                    ]),
                                    `Vacaciones_${employeeData.info.full_name?.replace(/\s+/g, '_')}`,
                                )
                            "
                        />
                        <Button
                            label="Copiar"
                            icon="pi pi-copy"
                            severity="secondary"
                            size="small"
                            @click="
                                copyToClipboard(
                                    prepareExportData(employeeData.vacaciones, [
                                        'id',
                                        'employee_id',
                                        'branch_office_id',
                                    ]),
                                    'Vacaciones',
                                )
                            "
                        />
                        <Button
                            label="Imprimir"
                            icon="pi pi-print"
                            severity="warn"
                            size="small"
                            @click="
                                printTable(
                                    'table-vacaciones',
                                    'Reporte de Vacaciones',
                                )
                            "
                        />
                        <span class="flex-1"></span>
                        <span class="text-sm text-gray-500 self-center">
                            {{ employeeData.vacaciones?.length || 0 }} registros
                        </span>
                    </div>
                    <div id="table-vacaciones">
                        <DataTable
                            :value="employeeData.vacaciones"
                            tableStyle="min-width: 50rem"
                            stripedRows
                            size="small"
                            :paginator="true"
                            :rows="10"
                        >
                            <template #empty>
                                <div class="text-center py-4 text-gray-500">
                                    No hay registros de vacaciones.
                                </div>
                            </template>
                            <Column
                                v-for="col of vacationCols"
                                :key="col.field"
                                :field="col.field"
                                :header="col.header"
                            >
                                <template
                                    #body="slotProps"
                                    v-if="col.body === 'date'"
                                >
                                    {{ formatDate(slotProps.data[col.field]) }}
                                </template>
                                <template #body="slotProps" v-else>
                                    {{ slotProps.data[col.field] }}
                                </template>
                            </Column>
                        </DataTable>
                        <div class="mt-4 flex justify-center w-full">
                            <Tag
                                :severity="totalVacationDays >= 0 ? 'success' : 'danger'"
                                class="text-lg p-3"
                            >
                                <div class="flex items-center gap-2">
                                    <span>
                                        {{ totalVacationDays >= 0 ? 'Días de vacaciones disponibles:' : 'Días de vacaciones en deuda:' }}
                                    </span>
                                    <span class="font-bold">
                                        {{ totalVacationDays >= 0 ? totalVacationDays : Math.abs(totalVacationDays) }}
                                    </span>
                                </div>
                            </Tag>
                        </div>
                    </div>
                </TabPanel>

                <!-- Tab: Asistencia Semanal -->
                <TabPanel header="Asistencia Semanal">
                    <div class="mb-3 text-sm text-gray-500">
                        <i class="pi pi-info-circle"></i> Historial de
                        asistencia agrupado por semana.
                    </div>
                    <div class="flex flex-wrap gap-2 mb-4 no-print">
                        <Button
                            label="Excel"
                            icon="pi pi-file-excel"
                            severity="success"
                            size="small"
                            @click="
                                exportToXLSX(
                                    prepareExportData(
                                        employeeData.asistencia_semanal,
                                        ['id_original'],
                                    ),
                                    `Asistencia_${employeeData.info.full_name?.replace(/\s+/g, '_')}`,
                                )
                            "
                        />
                        <Button
                            label="CSV"
                            icon="pi pi-file"
                            severity="info"
                            size="small"
                            @click="
                                exportToCSV(
                                    prepareExportData(
                                        employeeData.asistencia_semanal,
                                        ['id_original'],
                                    ),
                                    `Asistencia_${employeeData.info.full_name?.replace(/\s+/g, '_')}`,
                                )
                            "
                        />
                        <Button
                            label="Copiar"
                            icon="pi pi-copy"
                            severity="secondary"
                            size="small"
                            @click="
                                copyToClipboard(
                                    prepareExportData(
                                        employeeData.asistencia_semanal,
                                        ['id_original'],
                                    ),
                                    'Asistencia',
                                )
                            "
                        />
                        <Button
                            label="Imprimir"
                            icon="pi pi-print"
                            severity="warn"
                            size="small"
                            @click="
                                printTable(
                                    'table-asistencia',
                                    'Reporte de Asistencia Semanal',
                                )
                            "
                        />
                        <span class="flex-1"></span>
                        <span class="text-sm text-gray-500 self-center">
                            {{ employeeData.asistencia_semanal?.length || 0 }}
                            semanas
                        </span>
                    </div>
                    <div id="table-asistencia">
                        <DataTable
                            :value="employeeData.asistencia_semanal"
                            tableStyle="min-width: 60rem"
                            scrollable
                            scroll-direction="horizontal"
                            stripedRows
                            size="small"
                            :paginator="true"
                            :rows="10"
                            sortField="week_year"
                            :sortOrder="-1"
                        >
                            <template #empty>
                                <div class="text-center py-4 text-gray-500">
                                    No hay registros de asistencia.
                                </div>
                            </template>
                            <Column
                                v-for="col of attendanceCols"
                                :key="col.field"
                                :field="col.field"
                                :header="col.header"
                            >
                                <template
                                    #body="slotProps"
                                    v-if="col.body === 'badge'"
                                >
                                    <Tag
                                        :value="slotProps.data[col.field]"
                                        :style="
                                            getTagStyle(
                                                slotProps.data[
                                                    'color_' + col.field
                                                ],
                                            )
                                        "
                                        size="small"
                                        :pt="{
                                            root: {
                                                class: 'min-w-[2rem] justify-center border',
                                                style: {
                                                    transition: 'all 0.2s',
                                                    boxShadow:
                                                        '0 1px 2px rgba(0,0,0,0.05)',
                                                },
                                            },
                                        }"
                                        :title="
                                            slotProps.data['name_' + col.field]
                                        "
                                    />
                                </template>
                                <template #body="slotProps" v-else>
                                    {{ slotProps.data[col.field] }}
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </TabPanel>

                <!-- Tab: Eficiencias -->
                <TabPanel header="Eficiencias">
                    <div class="flex flex-wrap gap-2 mb-4 no-print">
                        <Button
                            label="Excel"
                            icon="pi pi-file-excel"
                            severity="success"
                            size="small"
                            @click="
                                exportToXLSX(
                                    prepareExportData(
                                        employeeData.eficiencias,
                                        [
                                            'id',
                                            'employee_id',
                                            'branch_office_id',
                                        ],
                                    ),
                                    `Eficiencias_${employeeData.info.full_name?.replace(/\s+/g, '_')}`,
                                )
                            "
                        />
                        <Button
                            label="CSV"
                            icon="pi pi-file"
                            severity="info"
                            size="small"
                            @click="
                                exportToCSV(
                                    prepareExportData(
                                        employeeData.eficiencias,
                                        [
                                            'id',
                                            'employee_id',
                                            'branch_office_id',
                                        ],
                                    ),
                                    `Eficiencias_${employeeData.info.full_name?.replace(/\s+/g, '_')}`,
                                )
                            "
                        />
                        <Button
                            label="Copiar"
                            icon="pi pi-copy"
                            severity="secondary"
                            size="small"
                            @click="
                                copyToClipboard(
                                    prepareExportData(
                                        employeeData.eficiencias,
                                        [
                                            'id',
                                            'employee_id',
                                            'branch_office_id',
                                        ],
                                    ),
                                    'Eficiencias',
                                )
                            "
                        />
                        <Button
                            label="Imprimir"
                            icon="pi pi-print"
                            severity="warn"
                            size="small"
                            @click="
                                printTable(
                                    'table-eficiencias',
                                    'Reporte de Eficiencias',
                                )
                            "
                        />
                        <span class="flex-1"></span>
                        <span class="text-sm text-gray-500 self-center">
                            {{ employeeData.eficiencias?.length || 0 }}
                            registros
                        </span>
                    </div>

                    <div id="table-eficiencias">
                        <DataTable
                            :value="employeeData.eficiencias"
                            tableStyle="min-width: 50rem"
                            stripedRows
                            size="small"
                            :paginator="true"
                            :rows="10"
                        >
                            <template #empty>
                                <div class="text-center py-4 text-gray-500">
                                    No hay registros de eficiencia.
                                </div>
                            </template>
                            <Column
                                v-for="col of efficiencyCols"
                                :key="col.field"
                                :field="col.field"
                                :header="col.header"
                            >
                                <template
                                    #body="slotProps"
                                    v-if="col.body === 'progress'"
                                >
                                    <div class="flex flex-col gap-1">
                                        <div
                                            class="flex justify-between text-xs"
                                        >
                                            <span class="font-bold"
                                                >{{
                                                    slotProps.data[col.field]
                                                }}%</span
                                            >
                                        </div>
                                        <ProgressBar
                                            :value="slotProps.data[col.field]"
                                            :severity="
                                                getEfficiencySeverity(
                                                    slotProps.data[col.field],
                                                )
                                            "
                                            class="h-2"
                                        />
                                    </div>
                                </template>
                                <template #body="slotProps" v-else>
                                    {{ slotProps.data[col.field] }}
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </TabPanel>

                <!-- Tab: Ciclos de Turno -->
                <TabPanel header="Ciclos Turno">
                    <div class="flex flex-wrap gap-2 mb-4 no-print">
                        <Button
                            label="Excel"
                            icon="pi pi-file-excel"
                            severity="success"
                            size="small"
                            @click="
                                exportToXLSX(
                                    prepareExportData(
                                        employeeData.ciclos_turno,
                                        [
                                            'id',
                                            'employee_id',
                                            'branch_office_id',
                                        ],
                                    ),
                                    `Ciclos_de_Turno${employeeData.info.full_name?.replace(/\s+/g, '_')}`,
                                )
                            "
                        />
                        <Button
                            label="CSV"
                            icon="pi pi-file"
                            severity="info"
                            size="small"
                            @click="
                                exportToCSV(
                                    prepareExportData(
                                        employeeData.ciclos_turno,
                                        [
                                            'id',
                                            'employee_id',
                                            'branch_office_id',
                                        ],
                                    ),
                                    `Ciclos_de_Turno${employeeData.info.full_name?.replace(/\s+/g, '_')}`,
                                )
                            "
                        />
                        <Button
                            label="Copiar"
                            icon="pi pi-copy"
                            severity="secondary"
                            size="small"
                            @click="
                                copyToClipboard(
                                    prepareExportData(
                                        employeeData.ciclos_turno,
                                        [
                                            'id',
                                            'employee_id',
                                            'branch_office_id',
                                        ],
                                    ),
                                    'Ciclos de Turno',
                                )
                            "
                        />
                        <Button
                            label="Imprimir"
                            icon="pi pi-print"
                            severity="warn"
                            size="small"
                            @click="
                                printTable(
                                    'table-ciclosTurno',
                                    'Reporte de Ciclos de Turno',
                                )
                            "
                        />
                        <span class="flex-1"></span>
                        <span class="text-sm text-gray-500 self-center">
                            {{ employeeData.ciclos_turno?.length || 0 }}
                            registros
                        </span>
                    </div>

                    <div id="table-ciclosTurno">
                        <DataTable
                            :value="employeeData.ciclos_turno"
                            tableStyle="min-width: 50rem"
                            stripedRows
                            size="small"
                            :paginator="true"
                            :rows="10"
                        >
                            <template #empty>
                                <div class="text-center py-4 text-gray-500">
                                    No hay registros de eficiencia.
                                </div>
                            </template>
                            <Column
                                v-for="col of shiftCyclesCols"
                                :key="col.field"
                                :field="col.field"
                                :header="col.header"
                            >
                                <template #body="slotProps">
                                    <span v-if="col.type === 'date'">
                                        {{
                                            formatDate(
                                                slotProps.data[col.field],
                                            )
                                        }}
                                    </span>
                                    <span v-else>
                                        {{ slotProps.data[col.field] }}
                                    </span>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </TabPanel>

                <!-- Tab: Rol de Turno -->
                <TabPanel header="Rol Turno">
                    <div class="flex flex-wrap gap-2 mb-4 no-print">
                        <Button
                            label="Excel"
                            icon="pi pi-file-excel"
                            severity="success"
                            size="small"
                            @click="
                                exportToXLSX(
                                    prepareExportData(
                                        employeeData.roles_turnos,
                                        [
                                            'id',
                                            'employee_id',
                                            'branch_office_id',
                                        ],
                                    ),
                                    `Rol_de_Turno${employeeData.info.full_name?.replace(/\s+/g, '_')}`,
                                )
                            "
                        />
                        <Button
                            label="CSV"
                            icon="pi pi-file"
                            severity="info"
                            size="small"
                            @click="
                                exportToCSV(
                                    prepareExportData(
                                        employeeData.roles_turnos,
                                        [
                                            'id',
                                            'employee_id',
                                            'branch_office_id',
                                        ],
                                    ),
                                    `Rol_de_Turno${employeeData.info.full_name?.replace(/\s+/g, '_')}`,
                                )
                            "
                        />
                        <Button
                            label="Copiar"
                            icon="pi pi-copy"
                            severity="secondary"
                            size="small"
                            @click="
                                copyToClipboard(
                                    prepareExportData(
                                        employeeData.roles_turnos,
                                        [
                                            'id',
                                            'employee_id',
                                            'branch_office_id',
                                        ],
                                    ),
                                    'Rol de Turno',
                                )
                            "
                        />
                        <Button
                            label="Imprimir"
                            icon="pi pi-print"
                            severity="warn"
                            size="small"
                            @click="
                                printTable(
                                    'table-rolTurno',
                                    'Reporte de Compensaciones',
                                )
                            "
                        />
                        <span class="flex-1"></span>
                        <span class="text-sm text-gray-500 self-center">
                            {{ employeeData.roles_turnos?.length || 0 }}
                            registros
                        </span>
                    </div>

                    <div id="table-rolTurno">
                        <DataTable
                            :value="employeeData.roles_turnos"
                            tableStyle="min-width: 50rem"
                            stripedRows
                            size="small"
                            :paginator="true"
                            :rows="10"
                        >
                            <template #empty>
                                <div class="text-center py-4 text-gray-500">
                                    No hay registros de Rol de Turno.
                                </div>
                            </template>
                            <Column
                                v-for="col of shiftRoleCols"
                                :key="col.field"
                                :field="col.field"
                                :header="col.header"
                            >
                                <template #body="slotProps">
                                    <template v-if="col.field === 'active'">
                                        <Tag
                                            :severity="
                                                slotProps.data.active
                                                    ? 'success'
                                                    : 'danger'
                                            "
                                            :value="
                                                slotProps.data.active
                                                    ? 'Activo'
                                                    : 'Inactivo'
                                            "
                                        />
                                    </template>

                                    <template v-else-if="col.type === 'date'">
                                        {{
                                            slotProps.data[col.field] ||
                                            "Vigente"
                                        }}
                                    </template>

                                    <template v-else>
                                        {{ slotProps.data[col.field] || "--" }}
                                    </template>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </TabPanel>

                <!-- Tab: Compensaciones -->
                <TabPanel header="Compensaciones">
                    <div class="flex flex-wrap gap-2 mb-4 no-print">
                        <Button
                            label="Excel"
                            icon="pi pi-file-excel"
                            severity="success"
                            size="small"
                            @click="
                                exportToXLSX(
                                    prepareExportData(
                                        employeeData.compensaciones,
                                        [
                                            'id',
                                            'employee_id',
                                            'branch_office_id',
                                        ],
                                    ),
                                    `Compensaciones${employeeData.info.full_name?.replace(/\s+/g, '_')}`,
                                )
                            "
                        />
                        <Button
                            label="CSV"
                            icon="pi pi-file"
                            severity="info"
                            size="small"
                            @click="
                                exportToCSV(
                                    prepareExportData(
                                        employeeData.compensaciones,
                                        [
                                            'id',
                                            'employee_id',
                                            'branch_office_id',
                                        ],
                                    ),
                                    `Compensaciones${employeeData.info.full_name?.replace(/\s+/g, '_')}`,
                                )
                            "
                        />
                        <Button
                            label="Copiar"
                            icon="pi pi-copy"
                            severity="secondary"
                            size="small"
                            @click="
                                copyToClipboard(
                                    prepareExportData(
                                        employeeData.compensaciones,
                                        [
                                            'id',
                                            'employee_id',
                                            'branch_office_id',
                                        ],
                                    ),
                                    'Compensaciones',
                                )
                            "
                        />
                        <Button
                            label="Imprimir"
                            icon="pi pi-print"
                            severity="warn"
                            size="small"
                            @click="
                                printTable(
                                    'table-compensaciones',
                                    'Reporte de Compensaciones',
                                )
                            "
                        />
                        <span class="flex-1"></span>
                        <span class="text-sm text-gray-500 self-center">
                            {{ employeeData.compensaciones?.length || 0 }}
                            registros
                        </span>
                    </div>

                    <div id="table-compensaciones">
                        <DataTable
                            :value="employeeData.compensaciones"
                            tableStyle="min-width: 50rem"
                            stripedRows
                            size="small"
                            :paginator="true"
                            :rows="10"
                        >
                            <template #empty>
                                <div class="text-center py-4 text-gray-500">
                                    No hay registros de Compensaciones.
                                </div>
                            </template>
                            <Column
                                v-for="col of compensationsCols"
                                :key="col.field"
                                :field="col.field"
                                :header="col.header"
                            >
                                <template #body="{ data, field }">
                                    <template
                                        v-if="
                                            compensationsCols.find(
                                                (c) => c.field === field,
                                            )?.type === 'date'
                                        "
                                    >
                                        {{ data[field] || "Pendiente" }}
                                    </template>

                                    <template v-else>
                                        {{
                                            data[field] !== null &&
                                            data[field] !== ""
                                                ? data[field]
                                                : "--"
                                        }}
                                    </template>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </TabPanel>

                <!-- Tab: Historial de Esstados -->
                <TabPanel header="Historial de estados">
                    <div class="flex flex-wrap gap-2 mb-4 no-print">
                        <Button
                            label="Excel"
                            icon="pi pi-file-excel"
                            severity="success"
                            size="small"
                            @click="
                                exportToXLSX(
                                    prepareExportData(
                                        employeeData.historial_estados,
                                        [
                                            'id',
                                            'employee_id',
                                            'branch_office_id',
                                        ],
                                    ),
                                    `Historial_Estados${employeeData.info.full_name?.replace(/\s+/g, '_')}`,
                                )
                            "
                        />
                        <Button
                            label="CSV"
                            icon="pi pi-file"
                            severity="info"
                            size="small"
                            @click="
                                exportToCSV(
                                    prepareExportData(
                                        employeeData.historial_estados,
                                        [
                                            'id',
                                            'employee_id',
                                            'branch_office_id',
                                        ],
                                    ),
                                    `Historial_Estados${employeeData.info.full_name?.replace(/\s+/g, '_')}`,
                                )
                            "
                        />
                        <Button
                            label="Copiar"
                            icon="pi pi-copy"
                            severity="secondary"
                            size="small"
                            @click="
                                copyToClipboard(
                                    prepareExportData(
                                        employeeData.historial_estados,
                                        [
                                            'id',
                                            'employee_id',
                                            'branch_office_id',
                                        ],
                                    ),
                                    'Historial_Estados',
                                )
                            "
                        />
                        <Button
                            label="Imprimir"
                            icon="pi pi-print"
                            severity="warn"
                            size="small"
                            @click="
                                printTable(
                                    'table-historial_estados',
                                    'Reporte de Historial_Estados',
                                )
                            "
                        />
                        <span class="flex-1"></span>
                        <span class="text-sm text-gray-500 self-center">
                            {{ employeeData.historial_estados?.length || 0 }}
                            registros
                        </span>
                    </div>

                    <div id="table-historial_estados">
                        <DataTable
                            :value="employeeData.historial_estados"
                            tableStyle="min-width: 50rem"
                            stripedRows
                            size="small"
                            :paginator="true"
                            :rows="10"
                        >
                            <template #empty>
                                <div class="text-center py-4 text-gray-500">
                                    No hay registros de Historial Estados.
                                </div>
                            </template>
                            <Column
                                v-for="col of statusHistoryCols"
                                :key="col.field"
                                :field="col.field"
                                :header="col.header"
                            >
                                <template #body="{ data, field }">
                                    <template v-if="field === 'status'">
                                        <Tag
                                            :severity="
                                                getStatusSeverity(data[field])
                                            "
                                            :value="getStatusLabel(data[field])"
                                        />
                                    </template>

                                    <template
                                        v-else-if="
                                            statusHistoryCols.find(
                                                (c) => c.field === field,
                                            )?.type === 'date'
                                        "
                                    >
                                        {{ data[field] || "--" }}
                                    </template>

                                    <template v-else>
                                        {{
                                            data[field] !== null &&
                                            data[field] !== ""
                                                ? data[field]
                                                : "--"
                                        }}
                                    </template>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </TabPanel>

                <!-- Tab: Tiempo Extra -->
                <TabPanel header="Tiempo Extra">
                    <div class="flex flex-wrap gap-2 mb-4 no-print">
                        <Button
                            label="Excel"
                            icon="pi pi-file-excel"
                            severity="success"
                            size="small"
                            @click="
                                exportToXLSX(
                                    prepareExportData(
                                        employeeData.tiempo_extra,
                                        [
                                            'id',
                                            'employee_id',
                                            'branch_office_id',
                                        ],
                                    ),
                                    `Tiempo_Extra${employeeData.info.full_name?.replace(/\s+/g, '_')}`,
                                )
                            "
                        />
                        <Button
                            label="CSV"
                            icon="pi pi-file"
                            severity="info"
                            size="small"
                            @click="
                                exportToCSV(
                                    prepareExportData(
                                        employeeData.tiempo_extra,
                                        [
                                            'id',
                                            'employee_id',
                                            'branch_office_id',
                                        ],
                                    ),
                                    `Tiempo_Extra${employeeData.info.full_name?.replace(/\s+/g, '_')}`,
                                )
                            "
                        />
                        <Button
                            label="Copiar"
                            icon="pi pi-copy"
                            severity="secondary"
                            size="small"
                            @click="
                                copyToClipboard(
                                    prepareExportData(
                                        employeeData.tiempo_extra,
                                        [
                                            'id',
                                            'employee_id',
                                            'branch_office_id',
                                        ],
                                    ),
                                    'Tiempo_Extra',
                                )
                            "
                        />
                        <Button
                            label="Imprimir"
                            icon="pi pi-print"
                            severity="warn"
                            size="small"
                            @click="
                                printTable(
                                    'table-tiempo_extra',
                                    'Reporte de Tiempo_Extra',
                                )
                            "
                        />
                        <span class="flex-1"></span>
                        <span class="text-sm text-gray-500 self-center">
                            {{ employeeData.tiempo_extra?.length || 0 }}
                            registros
                        </span>
                    </div>

                    <div id="table-tiempo_extra">
                        <DataTable
                            :value="employeeData.tiempo_extra"
                            tableStyle="min-width: 50rem"
                            stripedRows
                            size="small"
                            :paginator="true"
                            :rows="10"
                        >
                            <template #empty>
                                <div class="text-center py-4 text-gray-500">
                                    No hay registros de Tiempos Extra.
                                </div>
                            </template>
                            <Column
                                v-for="col of extraTimeCols"
                                :key="col.field"
                                :field="col.field"
                                :header="col.header"
                            >
                                <template #body="{ data, field }">
                                    <template
                                        v-if="
                                            [
                                                'double_overtime',
                                                'triple_overtime',
                                                'total_hours',
                                                'sunday_premium',
                                            ].includes(field)
                                        "
                                    >
                                        <template
                                            v-if="field === 'double_overtime'"
                                        >
                                            <Tag
                                                severity="contrast"
                                                :value="
                                                    parseExtraTime(
                                                        data.TiemposExtra,
                                                    ).double_overtime || 0
                                                "
                                            />
                                        </template>

                                        <template
                                            v-else-if="
                                                field === 'triple_overtime'
                                            "
                                        >
                                            <Tag
                                                severity="contrast"
                                                :value="
                                                    parseExtraTime(
                                                        data.TiemposExtra,
                                                    ).triple_overtime || 0
                                                "
                                            />
                                        </template>

                                        <template
                                            v-else-if="field === 'total_hours'"
                                        >
                                            <Tag
                                                severity="contrast"
                                                :value="
                                                    parseExtraTime(
                                                        data.TiemposExtra,
                                                    ).total || 0
                                                "
                                            />
                                        </template>
                                        <template
                                            v-else-if="
                                                field === 'sunday_premium'
                                            "
                                        >
                                            <Tag
                                                :severity="
                                                    parseExtraTime(
                                                        data.TiemposExtra,
                                                    ).sunday_premium
                                                        ? 'success'
                                                        : 'danger'
                                                "
                                                :value="
                                                    parseExtraTime(
                                                        data.TiemposExtra,
                                                    ).sunday_premium
                                                        ? '1'
                                                        : '0'
                                                "
                                                class="font-bold"
                                                v-tooltip="
                                                    parseExtraTime(
                                                        data.TiemposExtra,
                                                    ).sunday_premium
                                                        ? 'Con Prima'
                                                        : 'Sin Prima'
                                                "
                                            />
                                        </template>
                                    </template>

                                    <template
                                        v-else-if="field === 'status_extra'"
                                    >
                                        <Tag
                                            v-if="data.Aprovado"
                                            severity="success"
                                            value="Aprobado"
                                            icon="pi pi-check"
                                        />
                                        <Tag
                                            v-else-if="data.Declinado"
                                            severity="danger"
                                            value="Declinado"
                                            icon="pi pi-times"
                                        />
                                        <Tag
                                            v-else
                                            severity="info"
                                            value="Pendiente"
                                            icon="pi pi-clock"
                                        />
                                    </template>

                                    <template
                                        v-else-if="field === 'Extemporaneo'"
                                    >
                                        <Tag
                                            :severity="
                                                data[field] === 1
                                                    ? 'success'
                                                    : 'danger'
                                            "
                                            :icon="
                                                data[field] === 1
                                                    ? 'pi pi-check'
                                                    : 'pi pi-times'
                                            "
                                            class="p-1"
                                            v-tooltip="
                                                data[field] === 1 ? 'Si' : 'No'
                                            "
                                        />
                                    </template>

                                    <template v-else>
                                        {{
                                            data[field] !== null &&
                                            data[field] !== undefined
                                                ? data[field]
                                                : "--"
                                        }}
                                    </template>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </TabPanel>

                <!-- Tab: Recibos de Nomina -->
                <TabPanel header="Recibos de Nomina">
                    <div class="flex flex-wrap gap-2 mb-4 no-print">
                        <Button
                            label="Excel"
                            icon="pi pi-file-excel"
                            severity="success"
                            size="small"
                            @click="
                                exportToXLSX(
                                    prepareExportData(
                                        employeeData.recibos_nomina,
                                        [
                                            'id',
                                            'employee_id',
                                            'branch_office_id',
                                        ],
                                    ),
                                    `Recibos_nomina${employeeData.info.full_name?.replace(/\s+/g, '_')}`,
                                )
                            "
                        />
                        <Button
                            label="CSV"
                            icon="pi pi-file"
                            severity="info"
                            size="small"
                            @click="
                                exportToCSV(
                                    prepareExportData(
                                        employeeData.recibos_nomina,
                                        [
                                            'id',
                                            'employee_id',
                                            'branch_office_id',
                                        ],
                                    ),
                                    `Recibos_nomina${employeeData.info.full_name?.replace(/\s+/g, '_')}`,
                                )
                            "
                        />
                        <Button
                            label="Copiar"
                            icon="pi pi-copy"
                            severity="secondary"
                            size="small"
                            @click="
                                copyToClipboard(
                                    prepareExportData(
                                        employeeData.recibos_nomina,
                                        [
                                            'id',
                                            'employee_id',
                                            'branch_office_id',
                                        ],
                                    ),
                                    'Recibos_nomina',
                                )
                            "
                        />
                        <Button
                            label="Imprimir"
                            icon="pi pi-print"
                            severity="warn"
                            size="small"
                            @click="
                                printTable(
                                    'table-Recibos_nomina',
                                    'Reporte de Recibos_nomina',
                                )
                            "
                        />
                        <span class="flex-1"></span>
                        <span class="text-sm text-gray-500 self-center">
                            {{ employeeData.recibos_nomina?.length || 0 }}
                            registros
                        </span>
                    </div>

                    <div id="table-Recibos_nomina">
                        <DataTable
                            :value="employeeData.recibos_nomina"
                            :filters="filters"
                            dataKey="id"
                            tableStyle="min-width: 50rem"
                            stripedRows
                            size="small"
                            :paginator="true"
                            :rows="10"
                            filterDisplay="menu"
                        >
                            <template #header>
                                <div class="flex justify-content-end align-items-center gap-2 bg-transparent">
                                    <IconField iconPosition="left">
                                        <InputIcon class="pi pi-search" />
                                        <InputText
                                            v-model="filters['global'].value"
                                            placeholder="Buscar recibo..."
                                            class="p-inputtext-sm"
                                            style="width: 250px"
                                        />
                                    </IconField>
                                    <Button
                                        type="button"
                                        icon="pi pi-filter-slash"
                                        label="Limpiar"
                                        outlined
                                        size="small"
                                        @click="filters['global'].value = null"
                                    />
                                </div>
                            </template>
                            <template #empty>
                                <div class="text-center py-4 text-gray-500">No hay registros de Recibos de Nomina.</div>
                            </template>

                            <Column v-for="col of payrollReceiptsCols" :key="col.field" :field="col.field" :header="col.header">
                                <template #body="{ data, field }">

                                    <template v-if="field === 'estatus_correo'">
                                        <Tag
                                            :severity="data[field] === 1 ? 'success' : 'danger'"
                                            :icon="data[field] === 1 ? 'pi pi-check' : 'pi pi-envelope'"
                                            :value="data[field] === 1 ? 'Enviado' : 'Pendiente'"
                                        />
                                    </template>

                                    <template v-else-if="field === 'error_correo'">
                                        <span :class="data[field] ? 'text-red-500' : ''">
                                            {{ data[field] || 'Sin errores' }}
                                        </span>
                                    </template>

                                    <template v-else-if="field === 'acciones'">
                                        <Button
                                            v-if="data.pdf_path"
                                            icon="pi pi-file-pdf"
                                            severity="danger"
                                            text
                                            rounded
                                            v-tooltip="'Ver Recibo'"
                                            @click="viewPDF(data.pdf_path, data.id)"
                                        />
                                        <span v-else>--</span>
                                    </template>

                                    <template v-else>
                                        {{
                                            data[field] !== null &&
                                            data[field] !== undefined
                                                ? data[field]
                                                : "--"
                                        }}
                                    </template>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </TabPanel>

                <!-- Tab: Tiempo por Tiempo -->
                <TabPanel header="Tiempo por tiempo">
                    <div class="flex flex-wrap gap-2 mb-4 no-print">
                        <Button
                            label="Excel"
                            icon="pi pi-file-excel"
                            severity="success"
                            size="small"
                            @click="
                                exportToXLSX(
                                    prepareExportData(
                                        employeeData.tiempoxtiempo,
                                        [
                                            'id',
                                            'employee_id',
                                            'branch_office_id',
                                        ],
                                    ),
                                    `TiempoPorTiempo${employeeData.info.full_name?.replace(/\s+/g, '_')}`,
                                )
                            "
                        />
                        <Button
                            label="CSV"
                            icon="pi pi-file"
                            severity="info"
                            size="small"
                            @click="
                                exportToCSV(
                                    prepareExportData(
                                        employeeData.tiempoxtiempo,
                                        [
                                            'id',
                                            'employee_id',
                                            'branch_office_id',
                                        ],
                                    ),
                                    `TiempoPorTiempo${employeeData.info.full_name?.replace(/\s+/g, '_')}`,
                                )
                            "
                        />
                        <Button
                            label="Copiar"
                            icon="pi pi-copy"
                            severity="secondary"
                            size="small"
                            @click="
                                copyToClipboard(
                                    prepareExportData(
                                        employeeData.tiempoxtiempo,
                                        [
                                            'id',
                                            'employee_id',
                                            'branch_office_id',
                                        ],
                                    ),
                                    'tiempoxtiempo',
                                )
                            "
                        />
                        <Button
                            label="Imprimir"
                            icon="pi pi-print"
                            severity="warn"
                            size="small"
                            @click="
                                printTable(
                                    'table-tiempoxtiempo',
                                    'Reporte de tiempoxtiempo',
                                )
                            "
                        />
                        <span class="flex-1"></span>
                        <span class="text-sm text-gray-500 self-center">
                            {{ employeeData.tiempoxtiempo?.length || 0 }}
                            registros
                        </span>
                    </div>

                    <div id="table-tiempoxtiempo">
                        <DataTable
                            :value="employeeData.tiempoxtiempo"
                            :filters="filters"
                            dataKey="id"
                            tableStyle="min-width: 50rem"
                            stripedRows
                            size="small"
                            :paginator="true"
                            :rows="10"
                            filterDisplay="menu"
                        >
                            <template #header>
                                <div class="flex justify-content-end align-items-center gap-2 bg-transparent">
                                    <IconField iconPosition="left">
                                        <InputIcon class="pi pi-search" />
                                        <InputText
                                            v-model="filters['global'].value"
                                            placeholder="Buscar tiempo por tiempo..."
                                            class="p-inputtext-sm"
                                            style="width: 250px"
                                        />
                                    </IconField>
                                    <Button
                                        type="button"
                                        icon="pi pi-filter-slash"
                                        label="Limpiar"
                                        outlined
                                        size="small"
                                        @click="filters['global'].value = null"
                                    />
                                </div>
                            </template>
                            <template #empty>
                                <div class="text-center py-4 text-gray-500">No hay registros de Tiempor por Tiempo.</div>
                            </template>

                            <Column v-for="col of timefortimeCols" :key="col.field" :field="col.field" :header="col.header">
                            <template #body="{ data, field }">

                                <!-- Fecha -->
                                <template v-if="field === 'date'">
                                    {{ new Date(data.date + 'T00:00:00').toLocaleDateString('es-MX') }}
                                </template>

                                <!-- Horas -->
                                <template v-else-if="field === 'hours'">
                                    {{ Number(data.hours) || 0 }}
                                </template>

                                <!-- Estado -->
                                <template v-else-if="field === 'estado'">
                                    <Tag
                                        :severity="data.validated_at ? 'success' : 'danger'"
                                        :value="data.validated_at ? 'Aprobada' : 'Horas descontadas'"
                                    />
                                </template>

                                <!-- Default -->
                                <template v-else>
                                    {{ data[field] ?? '--' }}
                                </template>

                            </template>
                        </Column>
                        </DataTable>
                        <div class="mt-4 flex justify-center w-full">
                            <Tag
                                :severity="totalHoras >= 0 ? 'success' : 'danger'"
                                class="text-lg p-3"
                            >
                                <div class="flex items-center gap-2">
                                    <span>
                                        {{ totalHoras >= 0 ? 'Total de Horas:' : 'Deuda de Horas:' }}
                                    </span>
                                    <span class="font-bold">
                                        {{ totalHoras >= 0 ? totalHoras : Math.abs(totalHoras) }}
                                    </span>
                                </div>
                            </Tag>
                        </div>
                    </div>
                </TabPanel>
            </TabView>
        </div>

        <div
            v-else-if="!loading"
            class="flex justify-center py-20 text-gray-400"
        >
            <div class="text-center">
                <i class="pi pi-user text-6xl mb-4 opacity-20"></i>
                <p>Selecciona un empleado para ver su expediente</p>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.p-tabview-nav) {
    background: #fff;
}
:deep(.p-datatable-header) {
    background: #f8f9fa;
}

:deep(.p-datatable-header) {
    background: transparent !important; /* O usa el color de tu fondo: #121212 */
    border: none !important;
    padding: 1rem 0; /* Ajusta el espacio para que no se pegue a los botones */
}

</style>
