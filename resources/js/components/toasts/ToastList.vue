<template>
    <TransitionGroup
        tag="div"
        enter-from-class="translate-y-[-100%] opacity-0 md:translate-y-full"
        enter-to-class="translate-y-0 opacity-100"
        enter-active-class="transition-all duration-300"
        move-class="transition-all duration-300"
        leave-active-class="hidden"
        class="fixed top-0 z-[99999] flex max-h-screen w-full flex-col-reverse p-4 lg:bottom-0 lg:right-0 lg:top-auto lg:flex-col lg:max-w-[400px]"
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

let removeFinishEventListener = Inertia.on("finish", () => {
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
