import { defineStore } from "pinia";

export const useBranchOfficeStore = defineStore("branchOffice", {
    state: () => ({
        selected: null,
    }),
    actions: {
        select(branchOffice) {
            this.selected = branchOffice;
        },
        clear() {
            this.selected = null;
        },
    },
});
