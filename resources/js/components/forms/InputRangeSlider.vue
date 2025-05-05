<template>
    <div class="w-full" :class="{ 'opacity-40 cursor-not-allowed': disabled }">
        <label v-if="label" class="block text-sm font-medium text-white mb-4">
            {{ label }}
        </label>

        <div class="flex items-center justify-between text-xs text-gray-400">
            <span>{{ min }}</span>
            <span>{{ modelValue }}</span>
            <span>{{ max }}</span>
        </div>

        <input
            type="range"
            :class="disabled ? 'cursor-not-allowed' : ''"
            class="w-full appearance-none bg-zinc-700 h-1 rounded-lg outline-none transition-all duration-200 [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:w-4 [&::-webkit-slider-thumb]:h-4 [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:bg-white [&::-webkit-slider-thumb]:shadow [&::-webkit-slider-thumb]:hover:bg-gray-200 [&::-moz-range-thumb]:appearance-none [&::-moz-range-thumb]:w-4 [&::-moz-range-thumb]:h-4 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:bg-white"
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
defineProps({
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
});

defineEmits(["update:modelValue"]);
</script>
