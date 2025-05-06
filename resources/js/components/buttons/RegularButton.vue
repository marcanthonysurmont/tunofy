<template>
    <button
        :class="[
            'relative inline-flex items-center justify-center font-semibold transition disabled:opacity-60 disabled:cursor-not-allowed',
            type === 'primary'
                ? 'bg-blue-600 text-white hover:bg-blue-700'
                : '',
            type === 'secondary'
                ? 'bg-zinc-600 text-white hover:bg-zinc-700'
                : '',
            block ? 'w-full' : '',
            rounded ? 'rounded-full' : 'rounded-md',
            'px-5 py-2',
        ]"
        :disabled="disabled || loading"
        @click="$emit('click')"
    >
        <span
            class="inline-block font-semibold"
            :class="{ invisible: loading }"
        >
            <slot />
        </span>
        <span
            v-if="loading"
            class="absolute w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"
        ></span>
    </button>
</template>

<script setup>
import { defineProps, defineEmits } from "vue";

const props = defineProps({
    type: {
        type: String,
        default: "primary",
        validator: (val) => ["primary", "secondary"].includes(val),
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    block: {
        type: Boolean,
        default: false,
    },
    rounded: {
        type: Boolean,
        default: false,
    },
});

defineEmits(["click"]);
</script>

<style scoped>
.loader {
    position: absolute;
    width: 1.2em;
    height: 1.2em;
    border: 2px solid white;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
