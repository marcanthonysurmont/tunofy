<template>
    <SwitchGroup as="div" class="flex items-center">
        <div
            :class="[
                value ? 'bg-primary' : 'bg-zinc-700',
                'relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent transition-all duration-200 ease-in-out',
                disabled && 'opacity-50 cursor-not-allowed',
            ]"
        >
            <span
                aria-hidden="true"
                :class="[
                    value ? 'translate-x-5' : 'translate-x-0',
                    'pointer-events-none inline-block size-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out',
                ]"
            />
        </div>
        <SwitchLabel as="span" class="ml-3 text-sm text-zinc-100" @click.stop>
            <span
                class="font-medium transition-all duration-200 ease-in-out"
                :class="disabled ? 'opacity-40' : ''"
            >
                {{ isLoading ? "Loading..." : label }}
            </span>
            <span class="text-zinc-500">{{ description }}</span>
        </SwitchLabel>
    </SwitchGroup>
</template>

<script setup>
import { computed, defineProps } from "vue";
import { SwitchGroup, SwitchLabel } from "@headlessui/vue";

const props = defineProps({
    modelValue: {
        type: [Boolean, Number],
        default: false,
    },
    label: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: "",
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    isLoading: {
        type: Boolean,
        default: false,
    },
});

const value = computed(() => Boolean(props.modelValue));
</script>
