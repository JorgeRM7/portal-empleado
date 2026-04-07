import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";

export function useDownloadConfirm() {
    const toast = useToast();
    const confirm = useConfirm();

    const openDownloadConfirm = ({
        title = "Generando archivo",
        message = "Se está preparando tu archivo...",
        delay = 4000,
        onCSV,
        onXLSX
    }) => {

        toast.add({
            severity: "custom",
            summary: title,
            detail: message,
            group: "download",
            closable: false,
            life: delay,
            icon: "pi pi-spin pi-spinner"
        });

        setTimeout(() => {
            confirm.require({
                header: "Archivo listo",
                message: "Selecciona el formato para descargar:",
                icon: "pi pi-check-circle",
                acceptLabel: "CSV",
                rejectLabel: "XLSX",
                accept: () => {
                    if (onCSV) onCSV();
                },
                reject: () => {
                    if (onXLSX) onXLSX();
                }
            });
        }, delay);
    };

    return { openDownloadConfirm };
}
