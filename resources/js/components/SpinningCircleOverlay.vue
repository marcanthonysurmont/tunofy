<template>
    <div
        v-if="isVisible"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/85"
    >
        <div class="text-center p-10">
            <SpinningCircle customClass="mx-auto mb-8" size="h-8 w-8" />
            <h1 class="text-4xl font-bold mb-4 text-white">{{ title }}</h1>
            <p class="text-base text-dark-white">{{ description }}</p>
        </div>
    </div>
</template>

<script setup>
import { defineProps, watch } from "vue";
import SpinningCircle from "@/components/spinners/SpinningCircle.vue";

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: "",
    },
    isVisible: {
        type: Boolean,
        default: true,
    },
});

watch(
    () => props.isVisible,
    (newValue) => {
        if (newValue) {
            document.body.style.overflow = "hidden";
        } else {
            document.body.style.overflow = "";
        }
    }
);
</script>
