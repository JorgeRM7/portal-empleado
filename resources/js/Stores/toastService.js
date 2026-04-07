import { useToast } from "primevue/usetoast";

export function useToastService() {
    const toast = useToast();

    const baseToast = (severity, summary, detail, life = 3000) => {
        toast.add({
            severity,
            summary,
            detail,
            life
        });
    };

    const showSuccess = (message = 'Proceso completado correctamente.') => {
        toast.add({ severity: 'success', summary: 'Éxito', detail:'El proceso se realizo correctamente', life: 3000 });
    };

    const showError = (message = 'El proceso tuvo un error.') => {
        toast.add({ severity: 'error', summary: 'Error', detail:'Hubo un error en el proceso', life: 3000 });
    };

    // Exito mensaje personalizado
    const showSuccessCustom = (message = 'Proceso completado correctamente.') => {
        baseToast('success', 'Éxito', message, 15000);
    };

    // Error mensaje personalizado
    const showErrorCustom = (message = 'Hubo un error en el proceso.') => {
        baseToast('error', 'Error', message, 15000);
    };

    // Errores de validación
    const showValidationError = (message) => {
        baseToast('warn', 'Validación', message, 10000);
    };

    const processingTurn = () => {
        toast.add({
            severity: 'info',
            summary: 'Procesando',
            detail: 'Revisión de turnos en proceso...',
            group: 'processing',
            life: 0,
            icon: 'pi pi-spin pi-spinner'
        });
    };

    // Envio de nomina
    const processingPayroll = () => {
        toast.add({
            severity: 'info',
            summary: 'Procesando',
            detail: 'Envio de nomina en proceso...',
            group: 'processing',
            life: 0,
            icon: 'pi pi-spin pi-spinner'
        });
    };

    const errorPayroll = (data) => {
        toast.add({
            severity: 'error',
            summary: 'Error al procesar nómina',
            detail: `
                Crédito: $${data.credito}
                Débito: $${data.debito}
                Diferencia: $${data.diferencia}
            `,
            group: 'processing',
            life: 5000
        });

    };


    return {
        showSuccess,
        showError,
        showSuccessCustom,
        showErrorCustom,
        showValidationError,
        processingTurn,
        processingPayroll,
        errorPayroll
    };
}