import { defineStore } from 'pinia'
import { ref } from 'vue'

export const StoreConfirmationModal = defineStore('confirm', () => {
    const isVisible = ref(false)
    const title = ref('Are you sure?')
    const text = ref('')
    let resolveFn = null

    function confirm({ title: t, text: tx }) {
        title.value = t || 'Are you sure?'
        text.value = tx || ''
        isVisible.value = true

        return new Promise((resolve) => {
            resolveFn = resolve
        })
    }

    function cancel() {
        isVisible.value = false
        if (resolveFn) resolveFn(false)
    }

    function accept() {
        isVisible.value = false
        if (resolveFn) resolveFn(true)
    }

    return {
        isVisible,
        title,
        text,
        confirm,
        cancel,
        accept,
    }
})
