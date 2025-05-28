<template>
    <div class="mt-5 flex gap-6">
        <div
            @click="emits('click-left')"
            class="w-16 h-16 rounded-full bg-zinc-800 flex items-center justify-center border-1 border-zinc-700 cursor-pointer"
        >
            <button
                :class="{ bounce: bounceState.left }"
                class="cursor-pointer disabled:opacity-40"
                :disabled="isDisabled"
            >
                <XMarkIcon
                    class="size-8 text-[#e95a6c] stroke-2 stroke-[#e95a6c]"
                />
            </button>
        </div>
        <div
            @click="emits('kill')"
            class="w-16 h-16 rounded-full bg-zinc-800 flex items-center justify-center border-1 border-zinc-700 cursor-pointer"
        >
            <button
                :class="{ bounce: bounceState.kill }"
                :disabled="isDisabled"
                class="text-white font-bold cursor-pointer disabled:opacity-40"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 512 512"
                    fill="currentColor"
                    class="w-8 h-8 text-white"
                >
                    <path
                        d="M416 398.9c58.5-41.1 96-104.1 96-174.9C512 100.3 397.4 0 256 0S0 100.3 0 224c0 70.7 37.5 133.8 96 174.9c0 .4 0 .7 0 1.1l0 64c0 26.5 21.5 48 48 48l48 0 0-48c0-8.8 7.2-16 16-16s16 7.2 16 16l0 48 64 0 0-48c0-8.8 7.2-16 16-16s16 7.2 16 16l0 48 48 0c26.5 0 48-21.5 48-48l0-64c0-.4 0-.7 0-1.1zM96 256a64 64 0 1 1 128 0A64 64 0 1 1 96 256zm256-64a64 64 0 1 1 0 128 64 64 0 1 1 0-128z"
                    />
                </svg>
            </button>
        </div>
        <div
            class="w-16 h-16 rounded-full bg-zinc-800 border-1 border-zinc-700 flex items-center justify-center cursor-pointer"
            @click="emits('click-right')"
        >
            <button
                :class="{ bounce: bounceState.right }"
                class="cursor-pointer disabled:opacity-40"
                :disabled="isDisabled"
            >
                <HeartIcon class="size-8 text-[#74e3b8]" />
            </button>
        </div>
    </div>
</template>

<script setup>
import { HeartIcon, XMarkIcon } from "@heroicons/vue/24/solid";
const props = defineProps({
    bounceState: Object,
    isDisabled: Boolean,
});

const emits = defineEmits(["click-left", "click-right", "kill"]);
</script>

<style scoped>
.bounce {
    animation: bounceEffect 0.3s ease;
}

@keyframes bounceEffect {
    0% {
        transform: scale(1);
    }
    30% {
        transform: scale(0.9);
    }
    50% {
        transform: scale(1.1);
    }
    70% {
        transform: scale(0.95);
    }
    100% {
        transform: scale(1);
    }
}
</style>
