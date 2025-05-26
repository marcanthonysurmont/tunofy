<template>
    <div class="flex items-center justify-center gap-1 h-6">
        <div
            v-for="(bar, index) in bars"
            :key="index"
            class="w-1 rounded-full wave-bar"
            :class="selectedColor"
            :style="{ animationDelay: `${index * 0.1}s` }"
        ></div>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    barCount: {
        type: Number,
        default: 5,
    },
    color: {
        type: String,
        default: "white",
    },
    size: {
        type: String,
        default: "md",
        validator: (value) => ["sm", "md", "lg"].includes(value),
    },
});

//makes it [0, 1, 2, 3, 4] if barCount is 5
const bars = computed(() => [...Array(props.barCount).keys()]);

const selectedColor = computed(() => {
    switch (props.color) {
        case "primary":
            return "bg-primary";
        case "white":
            return "bg-white";
        default:
            return "bg-white";
    }
});
</script>

<style scoped>
.wave-bar {
    animation: wave 1.2s ease-in-out infinite;
}

@keyframes wave {
    0%,
    100% {
        height: 0.25rem;
    }
    50% {
        height: 1.5rem;
    }
}
</style>
