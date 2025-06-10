<template>
    <div class="w-full" :class="{ 'opacity-40 cursor-not-allowed': disabled }">
        <label
            :for="sliderId"
            v-if="label"
            class="text-sm font-medium text-white mb-4 flex items-center gap-2"
        >
            {{ label }}
            <InformationCircleIcon
                v-if="tooltip"
                v-tippy="{
                    content: tooltip,
                    touch: true,
                    hideOnClick: 'toggle',
                }"
                class="size-5 text-zinc-400 hover:text-zinc-200 transition"
                aria-label="Information"
            />
        </label>

        <div class="flex items-center justify-between text-xs text-zinc-400">
            <span>{{ min }}</span>
            <span>{{ modelValue }}</span>
            <span>{{ max }}</span>
        </div>

        <input
            :id="sliderId"
            type="range"
            :class="[
                disabled ? 'cursor-not-allowed' : 'cursor-pointer',
                'w-full appearance-none bg-zinc-700 h-1 rounded-lg outline-none transition-all duration-200',
                '[&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:w-4 [&::-webkit-slider-thumb]:h-4 [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:bg-white [&::-webkit-slider-thumb]:shadow [&::-webkit-slider-thumb]:hover:bg-zinc-200',
                '[&::-moz-range-thumb]:appearance-none [&::-moz-range-thumb]:w-4 [&::-moz-range-thumb]:h-4 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:bg-white',
                'focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2',
            ]"
            :min="min"
            :max="max"
            :step="step"
            :value="modelValue"
            :disabled="disabled"
            @input="$emit('update:modelValue', +$event.target.value)"
        />
    </div>
</template>

<script setup>
import { InformationCircleIcon } from "@heroicons/vue/24/solid";
import { computed } from "vue";

const props = defineProps({
    label: {
        type: String,
        default: "Undefined Label",
    },
    min: {
        type: Number,
        default: 0,
    },
    max: {
        type: Number,
        default: 100,
    },
    step: {
        type: Number,
        default: 1,
    },
    modelValue: {
        type: Number,
        required: true,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    tooltip: {
        type: String,
        default: "",
    },
});

const sliderId = computed(
    () =>
        `slider-${String(props.label).replace(/\s+/g, "-").toLowerCase()}-${
            props.min
        }-${props.max}`
);

defineEmits(["update:modelValue"]);
</script>
