<script setup>
import { computed } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { router } from "@inertiajs/vue3";
import { useLayout } from "@/Layouts/composables/layout";

const props = defineProps({
    incidence: {
        type: Object,
        required: true,
    },
});

console.log(props.incidence);

const { isDark } = useLayout();

const GROUPS = {
    TXT: new Set([23]),
    VAC: new Set([3]),
    SHIFT: new Set([20, 17, 19]),
    DOC: new Set([53, 10, 8, 22, 56, 5, 4, 7, 6, 49, 29, 14, 15, 13]),
};

const incidenceUI = computed(() => {
    const id = Number(props.incidence[0]?.incidence_id);

    if (GROUPS.TXT.has(id)) {
        return {
            key: "TXT",
            fields: [
                "shift_hours",
                "range",
                "txt_hours_to_register",
                "notes",
                "schedule",
            ],
        };
    }
    if (GROUPS.VAC.has(id)) {
        return {
            key: "VAC",
            fields: ["range", "days_to_register", "notes"],
        };
    }
    if (GROUPS.SHIFT.has(id)) {
        return {
            key: "SHIFT",
            fields: ["advance_date", "rest_date", "schedule", "notes"],
        };
    }
    if (GROUPS.DOC.has(id)) {
        return {
            key: "DOC",
            fields: [
                "range",
                "days_to_register",
                "document",
                "document_number",
                "notes",
            ],
        };
    }
    return { key: "DEFAULT", fields: ["range", "days_to_register", "notes"] };
});

const formatDate = (dateString) => {
    if (!dateString) return "—";
    const date = new Date(dateString);
    return date.toLocaleDateString("es-MX", {
        timeZone: "UTC",
        year: "numeric",
        month: "long",
        day: "numeric",
    });
};

const goBack = () => {
    router.get(route("/incidences"));
};

const getDocument = (urlPath) => {
    if (urlPath.includes("incidences")) {
        return route("incidences.document", props.incidence[0].id);
    }
    return `https://nomina.grupo-ortiz.site/Models/${urlPath}`;
};
</script>

<template>
    <AppLayout title="Ver Incidencia">
        <div class="w-full p-4 sm:p-6 lg:p-8 card">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">
                        Detalles de la Incidencia
                    </h2>
                    <p class="text-gray-500 text-sm mt-1">
                        Revisa la información completa del registro
                    </p>
                </div>
            </div>

            <div
                class="shadow-sm border border-gray-200 rounded-2xl overflow-hidden"
            >
                <div
                    class="p-6 md:p-8 transition-all"
                    :class="{
                        'border-gray-100 bg-gray-50': !isDark,
                        'border-gray-700 bg-gray-800': isDark,
                    }"
                >
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex items-center gap-4">
                            <div
                                class="w-14 h-14 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-2xl shadow-inner"
                            >
                                <i class="pi pi-user"></i>
                            </div>
                            <div>
                                <p
                                    class="text-sm font-semibold uppercase tracking-wider"
                                >
                                    Empleado
                                </p>
                                <p class="text-xl font-bold mt-1">
                                    {{
                                        incidence[0]?.full_name ||
                                        "Empleado no encontrado"
                                    }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 md:justify-end">
                            <div class="text-left md:text-right">
                                <p
                                    class="text-sm font-semibold text-gray-500 uppercase tracking-wider"
                                >
                                    Tipo de Incidencia
                                </p>
                                <div class="mt-2">
                                    <Tag
                                        severity="info"
                                        class="text-sm px-3 py-1 rounded-full shadow-sm"
                                        :value="incidence[0]?.incidence_name"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 md:p-8">
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6"
                    >
                        <div
                            v-if="incidenceUI.fields.includes('range')"
                            class="flex items-start gap-4 p-4 rounded-xl transition-all duration-200"
                            :class="{
                                'border-gray-100 bg-gray-50': !isDark,
                                'border-gray-700 bg-gray-800': isDark,
                            }"
                        >
                            <div
                                class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0"
                            >
                                <i class="pi pi-calendar"></i>
                            </div>
                            <div>
                                <p
                                    class="text-xs font-bold uppercase tracking-wide"
                                    :class="{
                                        'text-gray-400': !isDark,
                                        'text-gray-500': isDark,
                                    }"
                                >
                                    {{
                                        incidenceUI.key === "SHIFT"
                                            ? "Vigencia"
                                            : "Vigencia (Desde / Hasta)"
                                    }}
                                </p>
                                <p
                                    class="font-medium mt-1"
                                    :class="{
                                        'text-slate-800': !isDark,
                                        'text-slate-200': isDark,
                                    }"
                                >
                                    {{
                                        formatDate(incidence[0]?.validity_from)
                                    }}
                                    <br />
                                    <span class="text-gray-400">al</span> <br />
                                    {{ formatDate(incidence[0]?.validity_to) }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="
                                incidenceUI.fields.includes('days_to_register')
                            "
                            class="flex items-start gap-4 p-4 rounded-xl transition-all duration-200"
                            :class="{
                                'border-gray-100 bg-gray-50': !isDark,
                                'border-gray-700 bg-gray-800': isDark,
                            }"
                        >
                            <div
                                class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0"
                            >
                                <i class="pi pi-sun"></i>
                            </div>
                            <div>
                                <p
                                    class="text-xs font-bold uppercase tracking-wide"
                                    :class="{
                                        'text-gray-400': !isDark,
                                        'text-gray-500': isDark,
                                    }"
                                >
                                    Días Registrados
                                </p>
                                <p
                                    class="font-medium mt-1 text-lg"
                                    :class="{
                                        'text-slate-800': !isDark,
                                        'text-slate-200': isDark,
                                    }"
                                >
                                    {{ incidence[0]?.days }} día(s)
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="incidenceUI.fields.includes('schedule')"
                            class="flex items-start gap-4 p-4 rounded-xl transition-all duration-200"
                            :class="{
                                'border-gray-100 bg-gray-50': !isDark,
                                'border-gray-700 bg-gray-800': isDark,
                            }"
                        >
                            <div
                                class="w-10 h-10 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center shrink-0"
                            >
                                <i class="pi pi-clock"></i>
                            </div>
                            <div>
                                <p
                                    class="text-xs font-bold uppercase tracking-wide"
                                    :class="{
                                        'text-gray-400': !isDark,
                                        'text-gray-500': isDark,
                                    }"
                                >
                                    Turno
                                </p>
                                <p
                                    class="font-medium mt-1"
                                    :class="{
                                        'text-slate-800': !isDark,
                                        'text-slate-200': isDark,
                                    }"
                                >
                                    {{ incidence[0]?.schedule_name || "—" }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="
                                incidenceUI.fields.includes(
                                    'txt_hours_to_register',
                                )
                            "
                            class="flex items-start gap-4 p-4 rounded-xl transition-all duration-200"
                            :class="{
                                'border-gray-100 bg-gray-50': !isDark,
                                'border-gray-700 bg-gray-800': isDark,
                            }"
                        >
                            <div
                                class="w-10 h-10 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center shrink-0"
                            >
                                <i class="pi pi-stopwatch"></i>
                            </div>
                            <div>
                                <p
                                    class="text-xs font-bold uppercase tracking-wide"
                                    :class="{
                                        'text-gray-400': !isDark,
                                        'text-gray-500': isDark,
                                    }"
                                >
                                    Horas TXT
                                </p>
                                <p
                                    class="font-medium mt-1 text-lg"
                                    :class="{
                                        'text-slate-800': !isDark,
                                        'text-slate-200': isDark,
                                    }"
                                >
                                    {{ incidence[0]?.hours_txt }} hrs
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="incidenceUI.fields.includes('advance_date')"
                            class="flex items-start gap-4 p-4 rounded-xl transition-all duration-200"
                            :class="{
                                'border-gray-100 bg-gray-50': !isDark,
                                'border-gray-700 bg-gray-800': isDark,
                            }"
                        >
                            <div
                                class="w-10 h-10 rounded-full bg-sky-100 text-sky-600 flex items-center justify-center shrink-0"
                            >
                                <i class="pi pi-calendar-plus"></i>
                            </div>
                            <div>
                                <p
                                    class="text-xs font-bold uppercase tracking-wide"
                                    :class="{
                                        'text-gray-400': !isDark,
                                        'text-gray-500': isDark,
                                    }"
                                >
                                    Fecha de Adelanto
                                </p>
                                <p
                                    class="font-medium mt-1"
                                    :class="{
                                        'text-slate-800': !isDark,
                                        'text-slate-200': isDark,
                                    }"
                                >
                                    {{ formatDate(incidence[0]?.before_date) }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="incidenceUI.fields.includes('rest_date')"
                            class="flex items-start gap-4 p-4 rounded-xl transition-all duration-200"
                            :class="{
                                'border-gray-100 bg-gray-50': !isDark,
                                'border-gray-700 bg-gray-800': isDark,
                            }"
                        >
                            <div
                                class="w-10 h-10 rounded-full bg-teal-100 text-teal-600 flex items-center justify-center shrink-0"
                            >
                                <i class="pi pi-calendar-minus"></i>
                            </div>
                            <div>
                                <p
                                    class="text-xs font-bold uppercase tracking-wide"
                                    :class="{
                                        'text-gray-400': !isDark,
                                        'text-gray-500': isDark,
                                    }"
                                >
                                    Fecha de Descanso
                                </p>
                                <p
                                    class="font-medium mt-1"
                                    :class="{
                                        'text-slate-800': !isDark,
                                        'text-slate-200': isDark,
                                    }"
                                >
                                    {{ formatDate(incidence[0]?.rest_date) }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="
                                incidenceUI.fields.includes('document_number')
                            "
                            class="flex items-start gap-4 p-4 rounded-xl transition-all duration-200"
                            :class="{
                                'border-gray-100 bg-gray-50': !isDark,
                                'border-gray-700 bg-gray-800': isDark,
                            }"
                        >
                            <div
                                class="w-10 h-10 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center shrink-0"
                            >
                                <i class="pi pi-hashtag"></i>
                            </div>
                            <div>
                                <p
                                    class="text-xs font-bold uppercase tracking-wide"
                                    :class="{
                                        'text-gray-400': !isDark,
                                        'text-gray-500': isDark,
                                    }"
                                >
                                    Folio del Documento
                                </p>
                                <p
                                    class="sfont-medium mt-1"
                                    :class="{
                                        'text-slate-800': !isDark,
                                        'text-slate-200': isDark,
                                    }"
                                >
                                    {{ incidence[0]?.document_number || "—" }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="incidenceUI.fields.includes('document')"
                        class="mt-8"
                    >
                        <div
                            class="flex items-center justify-between p-5 rounded-xl"
                            :class="{
                                'bg-blue-50 border border-blue-100': !isDark,
                                'bg-blue-600 border border-blue-800': isDark,
                            }"
                        >
                            <div class="flex items-center gap-4">
                                <i
                                    class="pi pi-file-pdf text-3xl"
                                    :class="{
                                        'text-blue-500': !isDark,
                                        'text-blue-400': isDark,
                                    }"
                                ></i>
                                <div>
                                    <p
                                        class="font-semibold"
                                        :class="{
                                            'text-blue-900': !isDark,
                                            'text-blue-100': isDark,
                                        }"
                                    >
                                        Documento Comprobante
                                    </p>
                                    <p
                                        v-if="!incidence[0]?.file_path"
                                        class="text-sm mt-1"
                                        :class="{
                                            'text-blue-600': !isDark,
                                            'text-blue-400': isDark,
                                        }"
                                    >
                                        No hay archivo adjunto en este registro.
                                    </p>
                                    <p
                                        v-else
                                        class="text-sm mt-1"
                                        :class="{
                                            'text-blue-600': !isDark,
                                            'text-blue-400': isDark,
                                        }"
                                    >
                                        El documento de respaldo está listo para
                                        visualizarse.
                                    </p>
                                </div>
                            </div>
                            <div v-if="incidence[0]?.file_path">
                                <a
                                    :href="getDocument(incidence[0]?.file_path)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <Button
                                        icon="pi pi-external-link"
                                        label="Ver Archivo"
                                        severity="info"
                                        class="rounded-full shadow-sm hover:shadow-md"
                                    />
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-around mt-5">
                        <div
                            v-if="incidence[0]?.approved_by"
                            class="flex items-start gap-4 p-4 rounded-xl transition-all duration-200 w-1/2 mr-5"
                            :class="{
                                'border-gray-100 bg-gray-50': !isDark,
                                'border-gray-700 bg-gray-800': isDark,
                            }"
                        >
                            <div
                                class="w-10 h-10 rounded-full bg-green-100 text-green-600 flex items-center justify-center shrink-0"
                            >
                                <i class="pi pi-user"></i>
                            </div>
                            <div>
                                <p
                                    class="text-xs font-bold uppercase tracking-wide"
                                    :class="{
                                        'text-gray-400': !isDark,
                                        'text-gray-500': isDark,
                                    }"
                                >
                                    Aprobado por
                                </p>
                                <p
                                    class="font-medium mt-1"
                                    :class="{
                                        'text-slate-800': !isDark,
                                        'text-slate-200': isDark,
                                    }"
                                >
                                    {{ incidence[0]?.name }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="incidence[0]?.approved_by"
                            class="flex items-start gap-4 p-4 rounded-xl transition-all duration-200 w-1/2"
                            :class="{
                                'border-gray-100 bg-gray-50': !isDark,
                                'border-gray-700 bg-gray-800': isDark,
                            }"
                        >
                            <div
                                class="w-10 h-10 rounded-full bg-green-100 text-green-600 flex items-center justify-center shrink-0"
                            >
                                <i class="pi pi-clock"></i>
                            </div>
                            <div>
                                <p
                                    class="text-xs font-bold uppercase tracking-wide"
                                    :class="{
                                        'text-gray-400': !isDark,
                                        'text-gray-500': isDark,
                                    }"
                                >
                                    Fecha de Aprobación
                                </p>
                                <p
                                    class="font-medium mt-1"
                                    :class="{
                                        'text-slate-800': !isDark,
                                        'text-slate-200': isDark,
                                    }"
                                >
                                    {{ formatDate(incidence[0]?.approved_at) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 mb-3 mt-5">
                        <i class="pi pi-info-circle text-gray-400"></i>
                        <h3
                            class="text-sm font-bold text-gray-500 uppercase tracking-wide"
                        >
                            Descripción de la Incidencia
                        </h3>
                    </div>

                    <Message severity="info">
                        {{ incidence[0]?.description }}
                    </Message>

                    <div
                        v-if="
                            incidenceUI.fields.includes('notes') &&
                            incidence[0]?.comment
                        "
                        class="mt-8"
                    >
                        <div class="flex items-center gap-2 mb-3">
                            <i class="pi pi-align-left text-gray-400"></i>
                            <h3
                                class="text-sm font-bold text-gray-500 uppercase tracking-wide"
                            >
                                Notas y Comentarios
                            </h3>
                        </div>
                        <div
                            class="p-5 rounded-xl"
                            :class="{
                                'bg-amber-50 border border-amber-100': !isDark,
                                'bg-amber-600 border border-amber-800': isDark,
                            }"
                        >
                            <p
                                class="whitespace-pre-wrap leading-relaxed"
                                :class="{
                                    'text-amber-900': !isDark,
                                    'text-amber-100': isDark,
                                }"
                            >
                                {{ incidence[0]?.comment }}
                            </p>
                        </div>
                    </div>
                    <div class="flex justify-end mt-6">
                        <Button
                            label="Volver"
                            icon="pi pi-arrow-left"
                            severity="secondary"
                            @click="
                                () => {
                                    router.get('/incidences-employee');
                                }
                            "
                            class="rounded-full"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
