<template>
    <div
        class="border-b rounded-lg border-card-stroke py-1.5 transition-all duration-500"
    >
        <button
            @click="toggle"
            class="flex items-center justify-between w-full py-1.5 text-left font-medium cursor-pointer"
        >
            <p class="font-headings text-base">{{ question }}</p>
            <svg
                :class="{ 'rotate-180': isOpen }"
                class="w-6 h-6 transition-transform duration-300 ml-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div
            ref="content"
            class="relative overflow-hidden transition-all duration-400"
            :style="{ maxHeight: isOpen ? content.scrollHeight + 'px' : '0px' }"
        >
            <div class="pb-1.5 max-w-7xl">
                <p class="text-sm text-muted">{{ answer }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";

const props = defineProps({
    question: String,
    answer: String,
});

const isOpen = ref(false);
const content = ref(null);

function toggle() {
    isOpen.value = !isOpen.value;
}
</script>

<style scoped>
.rotate-180 {
    transform: rotate(180deg);
}
</style>
