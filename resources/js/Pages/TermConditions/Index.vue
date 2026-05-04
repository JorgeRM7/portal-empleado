<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref } from "vue";
import Card from "primevue/card";
import Accordion from "primevue/accordion";
import AccordionTab from "primevue/accordiontab";

const props = defineProps({
    data: Array,
});

const sections = ref([0, 1, 2, 3, 4]);
</script>

<template>
    <AppLayout :title="'Términos y Condiciones'">
        <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-4xl">
                <!-- Header Section -->
                <div class="mb-12 text-center">
                    <h1
                        class="text-4xl md:text-5xl font-bold mb-4 tracking-tight"
                    >
                        Términos y Condiciones
                    </h1>
                    <p
                        class="text-lg text-slate-600 dark:text-slate-400 leading-relaxed max-w-2xl mx-auto"
                    >
                        Información importante sobre la protección de datos
                        personales en Mi Portal RH. Lea atentamente antes de
                        utilizar esta plataforma.
                    </p>
                </div>

                <!-- Introduction Card -->
                <Card class="mb-8 shadow-lg border-0 dark:bg-slate-800">
                    <template #content>
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <div
                                    class="flex items-center justify-center h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900"
                                >
                                    <i
                                        class="pi pi-info-circle text-blue-600 dark:text-blue-400"
                                    ></i>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold mb-2">
                                    MI PORTAL DE RH
                                </h3>
                                <p class="leading-relaxed">
                                    En cumplimiento con lo dispuesto por la Ley
                                    Federal de Protección de Datos Personales en
                                    Posesión de los Particulares, la
                                    organización responsable de Mi Portal RH (en
                                    lo sucesivo, "el Responsable") informa a sus
                                    colaboradores lo siguiente:
                                </p>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Accordion Sections -->
                <Accordion
                    :activeIndex="sections"
                    multiple
                    class="shadow-lg border-0"
                >
                    <!-- Section 1: Datos Personales -->
                    <AccordionTab
                        header="📋 Datos personales que se recaban"
                        headerClass="font-semibold text-base"
                    >
                        <div class="space-y-4">
                            <p>
                                A través de Mi Portal RH, se recabarán y
                                tratarán los siguientes datos personales:
                            </p>
                            <ul class="space-y-3">
                                <li class="flex gap-3">
                                    <span class="flex-shrink-0 font-bold"
                                        >•</span
                                    >
                                    <span
                                        ><strong>Identificación:</strong> nombre
                                        completo, número de empleado, CURP,
                                        RFC</span
                                    >
                                </li>
                                <li class="flex gap-3">
                                    <span class="flex-shrink-0 font-bold"
                                        >•</span
                                    >
                                    <span
                                        ><strong>Datos laborales:</strong>
                                        puesto, área, tipo de contrato,
                                        antigüedad, historial laboral</span
                                    >
                                </li>
                                <li class="flex gap-3">
                                    <span class="flex-shrink-0 font-bold"
                                        >•</span
                                    >
                                    <span
                                        ><strong>Contacto:</strong> correo
                                        electrónico, teléfono</span
                                    >
                                </li>
                                <li class="flex gap-3">
                                    <span class="flex-shrink-0 font-bold"
                                        >•</span
                                    >
                                    <span
                                        ><strong>Gestión laboral:</strong>
                                        solicitudes de vacaciones, permisos,
                                        incidencias, tiempos extra,
                                        asistencias</span
                                    >
                                </li>
                                <li class="flex gap-3">
                                    <span class="flex-shrink-0 font-bold"
                                        >•</span
                                    >
                                    <span
                                        ><strong>Datos sensibles:</strong>
                                        información relacionada con
                                        incapacidades médicas (cuando
                                        aplique)</span
                                    >
                                </li>
                            </ul>
                        </div>
                    </AccordionTab>

                    <!-- Section 2: Finalidades -->
                    <AccordionTab
                        header="🎯 Finalidades del tratamiento"
                        headerClass="font-semibold text-base"
                    >
                        <div class="space-y-6">
                            <div>
                                <h4
                                    class="font-semibold mb-3 flex items-center gap-2"
                                >
                                    <span
                                        class="inline-block w-2 h-2 bg-green-600 rounded-full"
                                    ></span>
                                    Finalidades primarias (necesarias)
                                </h4>
                                <ul class="space-y-2 ml-4">
                                    <li class="flex gap-3">
                                        <span class="flex-shrink-0">✓</span>
                                        <span
                                            >Administración y operación de Mi
                                            Portal RH</span
                                        >
                                    </li>
                                    <li class="flex gap-3">
                                        <span class="flex-shrink-0">✓</span>
                                        <span
                                            >Registro y control de incidencias
                                            laborales</span
                                        >
                                    </li>
                                    <li class="flex gap-3">
                                        <span class="flex-shrink-0">✓</span>
                                        <span
                                            >Gestión de vacaciones, permisos y
                                            tiempos extra</span
                                        >
                                    </li>
                                    <li class="flex gap-3">
                                        <span class="flex-shrink-0">✓</span>
                                        <span>Administración de nómina</span>
                                    </li>
                                    <li class="flex gap-3">
                                        <span class="flex-shrink-0">✓</span>
                                        <span
                                            >Control de asistencia y jornada
                                            laboral</span
                                        >
                                    </li>
                                    <li class="flex gap-3">
                                        <span class="flex-shrink-0">✓</span>
                                        <span
                                            >Cumplimiento de obligaciones
                                            legales aplicables</span
                                        >
                                    </li>
                                </ul>
                            </div>

                            <div
                                class="border-t border-slate-200 dark:border-slate-700 pt-6"
                            >
                                <h4
                                    class="font-semibold mb-3 flex items-center gap-2"
                                >
                                    <span
                                        class="inline-block w-2 h-2 bg-amber-600 rounded-full"
                                    ></span>
                                    Finalidades secundarias (opcionales)
                                </h4>
                                <ul class="space-y-2 ml-4">
                                    <li class="flex gap-3">
                                        <span
                                            class="flex-shrink-0 text-amber-600"
                                            >○</span
                                        >
                                        <span
                                            >Elaboración de reportes y análisis
                                            estadísticos internos</span
                                        >
                                    </li>
                                    <li class="flex gap-3">
                                        <span
                                            class="flex-shrink-0 text-amber-600"
                                            >○</span
                                        >
                                        <span
                                            >Mejora de procesos de Recursos
                                            Humanos</span
                                        >
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </AccordionTab>

                    <!-- Section 3: Transferencia -->
                    <AccordionTab
                        header="🔄 Transferencia de datos personales"
                        headerClass="font-semibold text-base"
                    >
                        <div class="space-y-4">
                            <p>
                                Los datos personales podrán ser transferidos a:
                            </p>
                            <div class="space-y-3">
                                <div
                                    class="p-4 rounded-lg border-l-8 border-t-2 border-b-2 border-r-2 border-blue-600"
                                >
                                    <p class="flex gap-3">
                                        <i
                                            class="pi pi-building text-blue-600 dark:text-blue-400 flex-shrink-0"
                                        ></i>
                                        <span
                                            ><strong
                                                >Autoridades competentes</strong
                                            >
                                            cuando así lo requiera la
                                            legislación aplicable</span
                                        >
                                    </p>
                                </div>
                                <div
                                    class="p-4 border-l-8 border-t-2 border-b-2 border-r-2 rounded-lg border-blue-600"
                                >
                                    <p class="flex gap-3">
                                        <i
                                            class="pi pi-lock text-blue-600 dark:text-blue-400 flex-shrink-0"
                                        ></i>
                                        <span
                                            ><strong
                                                >Proveedores de
                                                servicios</strong
                                            >
                                            que operen o soporten Mi Portal RH,
                                            bajo obligaciones de
                                            confidencialidad</span
                                        >
                                    </p>
                                </div>
                            </div>
                        </div>
                    </AccordionTab>

                    <!-- Section 4: Seguridad -->
                    <AccordionTab
                        header="🔒 Medidas de seguridad"
                        headerClass="font-semibold text-base"
                    >
                        <div class="space-y-4">
                            <div
                                class="p-4 rounded-lg border border-green-200 dark:border-green-800"
                            >
                                <p class="mb-4">
                                    El Responsable implementa medidas de
                                    seguridad
                                    <strong
                                        >administrativas, técnicas y
                                        físicas</strong
                                    >
                                    para proteger los datos personales contra
                                    daño, pérdida, alteración, destrucción o uso
                                    no autorizado.
                                </p>
                                <div class="space-y-3 mt-4">
                                    <div class="flex gap-3">
                                        <i
                                            class="pi pi-shield text-green-600 flex-shrink-0"
                                        ></i>
                                        <span class=""
                                            >Acceso personal e
                                            intransferible</span
                                        >
                                    </div>
                                    <div class="flex gap-3">
                                        <i
                                            class="pi pi-shield text-green-600 flex-shrink-0"
                                        ></i>
                                        <span class=""
                                            >Responsabilidad del colaborador en
                                            uso de credenciales</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </AccordionTab>

                    <!-- Section 5: Derechos ARCO -->
                    <AccordionTab
                        header="⚖️ Derechos ARCO"
                        headerClass="font-semibold text-base"
                    >
                        <div class="space-y-4">
                            <p class="mb-4">
                                El titular de los datos personales podrá ejercer
                                sus derechos de:
                            </p>
                            <div class="grid grid-cols-2 gap-3">
                                <div
                                    class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 text-center"
                                >
                                    <i
                                        class="pi pi-eye text-blue-600 text-xl block mb-2 mr-2"
                                    ></i>
                                    <span class="font-semibold text-sm"
                                        >Acceso</span
                                    >
                                </div>
                                <div
                                    class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800 text-center"
                                >
                                    <i
                                        class="pi pi-pencil text-purple-600 text-xl block mb-2 mr-2"
                                    ></i>
                                    <span class="font-semibold text-sm"
                                        >Rectificación</span
                                    >
                                </div>
                                <div
                                    class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800 text-center"
                                >
                                    <i
                                        class="pi pi-trash text-red-600 text-xl block mb-2 mr-2"
                                    ></i>
                                    <span class="font-semibold text-sm"
                                        >Cancelación</span
                                    >
                                </div>
                                <div
                                    class="p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-200 dark:border-orange-800 text-center"
                                >
                                    <i
                                        class="pi pi-times text-orange-600 text-xl block mb-2 mr-2"
                                    ></i>
                                    <span class="font-semibold text-sm"
                                        >Oposición</span
                                    >
                                </div>
                            </div>
                            <p
                                class="text-sm mt-4 italic border-t border-slate-200 dark:border-slate-700 pt-4"
                            >
                                Mediante solicitud a través de los medios
                                definidos por el área de Recursos Humanos.
                            </p>
                        </div>
                    </AccordionTab>
                </Accordion>

                <!-- Additional Info Cards -->
                <div class="grid md:grid-cols-2 gap-6 mt-8">
                    <Card class="shadow-lg border-0 dark:bg-slate-800">
                        <template #header>
                            <div
                                class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-t-lg"
                            ></div>
                        </template>
                        <template #content>
                            <h3
                                class="text-lg font-semibold mb-3 flex items-center gap-2"
                            >
                                <i
                                    class="pi pi-arrow-right text-blue-600 dark:text-blue-400"
                                ></i>
                                Uso de Mi Portal RH
                            </h3>
                            <div class="space-y-2 text-sm">
                                <p>
                                    ✓ Conocimiento y aceptación del Aviso de
                                    Privacidad
                                </p>
                                <p>✓ Información veraz y actualizada</p>
                            </div>
                        </template>
                    </Card>

                    <Card class="shadow-lg border-0 dark:bg-slate-800">
                        <template #header>
                            <div
                                class="bg-gradient-to-r from-amber-500 to-amber-600 h-2 rounded-t-lg"
                            ></div>
                        </template>
                        <template #content>
                            <h3
                                class="text-lg font-semibold mb-3 flex items-center gap-2"
                            >
                                <i
                                    class="pi pi-refresh text-amber-600 dark:text-amber-400"
                                ></i>
                                Cambios al aviso
                            </h3>
                            <p class="text-sm">
                                Puede ser modificado o actualizado en cualquier
                                momento. Las modificaciones serán comunicadas a
                                través de Mi Portal RH o medios internos.
                            </p>
                        </template>
                    </Card>
                </div>

                <!-- Consent Banner -->
                <div
                    class="mt-8 p-6 bg-gradient-to-r from-blue-600 to-blue-700 dark:from-blue-900 dark:to-blue-800 rounded-lg shadow-lg"
                >
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <i
                                class="pi pi-check-circle text-white text-2xl mt-1"
                            ></i>
                        </div>
                        <div>
                            <h3 class="text-white font-bold text-lg mb-2">
                                Consentimiento
                            </h3>
                            <p class="text-blue-50 leading-relaxed">
                                Al acceder y utilizar Mi Portal RH, el
                                colaborador otorga su consentimiento para el
                                tratamiento de sus datos personales conforme a
                                lo establecido en este aviso.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="mt-10 pt-6 border-t border-slate-200 dark:border-slate-700 text-center"
                >
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">
                        <strong>Última actualización:</strong>
                        {{
                            new Date().toLocaleDateString("es-MX", {
                                year: "numeric",
                                month: "long",
                                day: "numeric",
                            })
                        }}
                    </p>
                    <p class="text-xs text-slate-400 dark:text-slate-500">
                        Mi Portal RH © {{ new Date().getFullYear() }}
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
