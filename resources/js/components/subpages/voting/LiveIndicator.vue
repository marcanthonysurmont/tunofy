<template>
    <div class="relative w-4 h-4">
        <div
            class="absolute inset-0 rounded-full animate-ping"
            :class="{
                'bg-red-400': isLive,
                'bg-gray-400': !isLive,
            }"
        ></div>
        <div
            class="w-4 h-4 rounded-full z-10"
            :class="{
                'bg-red-400': isLive,
                'bg-gray-400': !isLive,
            }"
        ></div>
    </div>
</template>

<script setup>
const props = defineProps({
    isLive: {
        type: Boolean,
        required: true,
    },
});
</script>
