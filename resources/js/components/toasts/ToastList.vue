<template>
    <TransitionGroup
        tag="div"
        enter-from-class="translate-x-full opacity-0"
        leave-to-class="translate-x-full opacity-0"
        enter-active-class="duration-500 transition-all"
        leave-active-class="duration-500 transitional-all absolute"
        move-class="duration-500 transition-all"
        class="fixed bottom-40 md:bottom-42 lg:bottom-6 right-4 z-[10000] w-full max-w-xs space-y-4"
    >
        <ToastListItem
            v-for="(item, index) in toast.items"
            :key="item.key"
            :message="item.message"
            :notification="item.notification"
            :type="item.type"
            :duration="item.duration"
            @remove="remove(index)"
        />
    </TransitionGroup>
</template>

<script setup>
import { onUnmounted } from "vue";
import ToastListItem from "./ToastListItem.vue";
import { Inertia } from "@inertiajs/inertia";
import { usePage } from "@inertiajs/vue3";
import toast from "@/stores/StoreToast.js";

const page = usePage();

let removeFinishEventListener = Inertia.on("finish", (event) => {
    //if msg is success, show success prop
    if (page.props.success) {
        toast.add({
            message: page.props.success,
            type: "success",
        });
    }

    //if msg is success, show success prop
    if (page.props.danger) {
        toast.add({
            message: page.props.danger,
            type: "danger",
        });
    }
});

onUnmounted(() => {
    removeFinishEventListener();
});

function remove(index) {
    toast.remove(index);
}
</script>
