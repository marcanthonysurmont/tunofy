import { reactive } from 'vue';

export default reactive({
    items: [],
    add(toast) {
        //clear existing toasts to ensure only one is visible
        this.items = [];
        this.items.unshift({
            key: Symbol(),
            ...toast
        });
    },
    remove(index) {
        this.items.splice(index, 1);
    }
});
