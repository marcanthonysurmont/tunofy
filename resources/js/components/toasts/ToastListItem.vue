<template>
    <ToastListItemSuccess
        v-if="type === 'success'"
        :message="message"
        @remove-parent="removeToast"
    />
    <ToastListItemDanger
        v-else-if="type === 'danger'"
        :message="message"
        @remove-parent="removeToast"
    />
</template>

<script setup>
import { onMounted, ref, computed } from "vue";
import ToastListItemSuccess from "./ToastListItemSuccess.vue";
import ToastListItemDanger from "./ToastListItemDanger.vue";

const props = defineProps({
    notification: {
        type: Object,
        required: false,
    },
    message: String,
    duration: {
        type: Number,
        default: 3000,
    },
    type: {
        type: String,
        default: "success",
    },
});

function removeToast() {
    emit("remove");
}

//after X duration provided by prop, remove toast
onMounted(() => {
    setTimeout(() => removeToast(), props.duration);
});

const emit = defineEmits(["remove"]);
</script>
