import { defineStore } from 'pinia';

export const useFullscreenLoaderStore = defineStore('fullscreenLoader', {
    state: () => ({
        isVisible: false,
        message: 'Loading...',
        title: 'Please wait',
    }),
    actions: {
        show() {
            this.isVisible = true;
        },
        hide() {
            this.isVisible = false;
        },
        toggle() {
            this.isVisible = !this.isVisible;
        },
        setMessage(message) {
            this.message = message;
        },
        setTitle(title) {
            this.title = title;
        },
        setText(message, title) {
            this.setMessage(message);
            this.setTitle(title);
        }
    },
});
