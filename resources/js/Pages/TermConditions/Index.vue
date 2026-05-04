<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref } from "vue";
import Button from "primevue/button";
import Dialog from "primevue/dialog";

const props = defineProps({
    data: Array,
});

// Control del Dialog
const visible = ref(false);

const openDocument = () => {
    visible.value = true;
};
</script>

<template>
    <AppLayout :title="'Terminos y condiciones'">
        <div class="py-8 relative">
            <div class="mx-auto px-4 sm:px-6 lg:px-8">
                <div class="absolute inset-0 pointer-events-none card"></div>

                <div class="relative px-6 py-8 md:px-8 md:py-10">
                    <h1
                        class="mt-4 text-3xl md:text-5xl font-extrabold tracking-tight"
                    >
                        Terminos y condiciones
                    </h1>

                    <p
                        class="mt-3 max-w-3xl text-sm md:text-base leading-relaxed"
                    >
                        Lea atentamente estos términos y condiciones antes de
                        utilizar esta plataforma.
                    </p>

                    <div
                        class="p-4 surface-border border-round surface-section"
                    >
                        <p class="leading-normal">
                            <strong>MI PORTAL DE RH</strong><br />
                            En cumplimiento con lo dispuesto por la Ley Federal
                            de Protección de Datos Personales en Posesión de los
                            Particulares, la organización responsable de Mi
                            Portal RH (en lo sucesivo, “el Responsable”) informa
                            a sus colaboradores lo siguiente:
                        </p>

                        <Divider />

                        <p class="leading-normal">
                            <strong>Datos personales que se recaban</strong>
                            <br />
                            A través de Mi Portal RH, se recabarán y tratarán
                            los siguientes datos personales:
                        </p>
                        <ul class="list-disc ml-6">
                            <li>
                                Datos de identificación: nombre completo, número
                                de empleado, CURP, RFC
                            </li>
                            <li>
                                Datos laborales: puesto, área, tipo de contrato,
                                antigüedad, historial laboral
                            </li>
                            <li>
                                Datos de contacto: correo electrónico, teléfono
                            </li>
                            <li>
                                Datos de gestión laboral: solicitudes de
                                vacaciones, permisos, incidencias, tiempos
                                extra, asistencias
                            </li>
                            <li>
                                Datos sensibles (cuando aplique): información
                                relacionada con incapacidades médicas
                            </li>
                        </ul>
                        <Divider />
                        <p class="leading-normal mt-5">
                            <strong>Finalidades del tratamiento</strong>
                            <br />
                            <strong>Finalidades primarias (necesarias):</strong>
                        </p>
                        <ul class="list-disc ml-6">
                            <li>Administración y operación de Mi Portal RH</li>
                            <li>Registro y control de incidencias laborales</li>
                            <li>
                                Gestión de vacaciones, permisos y tiempos extra
                            </li>
                            <li>Administración de nómina</li>
                            <li>Control de asistencia y jornada laboral</li>
                            <li>
                                Cumplimiento de obligaciones legales aplicables
                            </li>
                        </ul>
                        <p class="leading-normal mt-5">
                            <strong
                                >Finalidades secundarias (opcionales):</strong
                            >
                        </p>
                        <ul class="list-disc ml-6">
                            <li>
                                Elaboración de reportes y análisis estadísticos
                                internos
                            </li>
                            <li>Mejora de procesos de Recursos Humanos</li>
                        </ul>
                        <Divider />
                        <p class="leading-normal mt-5">
                            <strong>Transferencia de datos personales</strong>
                            <br />
                            Los datos personales podrán ser transferidos a:
                        </p>
                        <ul class="list-disc ml-6">
                            <li>
                                Autoridades competentes cuando así lo requiera
                                la legislación aplicable
                            </li>
                            <li>
                                Proveedores de servicios que operen o soporten
                                Mi Portal RH (por ejemplo, sistemas tecnológicos
                                o servicios de nómina), bajo obligaciones de
                                confidencialidad
                            </li>
                        </ul>
                        <Divider />
                        <p class="leading-normal mt-5">
                            <strong>Medidas de seguridad</strong>
                            <br />
                            El Responsable implementa medidas de seguridad
                            administrativas, técnicas y físicas para proteger
                            los datos personales contra daño, pérdida,
                            alteración, destrucción o uso no autorizado.
                            <br />
                            El acceso a Mi Portal RH es personal e
                            intransferible, siendo responsabilidad del
                            colaborador el uso adecuado de sus credenciales.
                        </p>
                        <Divider />
                        <p class="leading-normal mt-5">
                            <strong>Derechos ARCO</strong>
                            <br />
                            El titular de los datos personales podrá ejercer sus
                            derechos de:
                        </p>
                        <ul class="list-disc ml-6">
                            <li>Acceso</li>
                            <li>Rectificación</li>
                            <li>Cancelación</li>
                            <li>Oposición</li>
                        </ul>
                        <p class="leading-normal mt-5">
                            mediante solicitud a través de los medios definidos
                            por el área de Recursos Humanos.
                        </p>
                        <Divider />
                        <p class="leading-normal mt-5">
                            <strong>Uso de Mi Portal RH</strong>
                            <br />
                            El uso de Mi Portal RH implica el conocimiento y
                            aceptación del presente Aviso de Privacidad.
                            <br />
                            El colaborador se compromete a proporcionar
                            información veraz y actualizada.
                        </p>
                        <Divider />
                        <p class="leading-normal mt-5">
                            <strong>Cambios al aviso de privacidad</strong>
                            <br />
                            El presente aviso podrá ser modificado o actualizado
                            en cualquier momento. Las modificaciones serán
                            comunicadas a través de Mi Portal RH o medios
                            internos.
                        </p>
                        <Divider />
                        <p class="leading-normal mt-5">
                            <strong>Consentimiento</strong>
                            <br />
                            Al acceder y utilizar Mi Portal RH, el colaborador
                            otorga su consentimiento para el tratamiento de sus
                            datos personales conforme a lo establecido en este
                            aviso.
                        </p>
                    </div>
                </div>

                <div
                    class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400"
                >
                    Ultima actualización: {{ new Date().toLocaleDateString() }}
                </div>
            </div>
        </div>

        <!-- <Dialog
            v-model:visible="visible"
            modal
            header="Terminos y Condiciones"
            :style="{ width: '70vw' }"
            :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
        >
            <div class="p-4 border-1 surface-border border-round surface-section">
                <p class="leading-normal">
                    PDF
                </p>
            </div>

            <template #footer>
                <Button label="Cerrar" icon="pi pi-check" @click="visible = false" autofocus />
            </template>
        </Dialog> -->
    </AppLayout>
</template>
