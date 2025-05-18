// stores/modalStore.js
import { defineStore } from 'pinia';

export const StoreInformationModal = defineStore('modal', {
    state: () => ({
        isVisible: false,
        title: '',
        text: '',
    }),
    actions: {
        showInfoModal(title, text) {
            this.title = title;
            this.text = text;
            this.isVisible = true;
        },
        hideInfoModal() {
            this.isVisible = false;
        },
    },
});
