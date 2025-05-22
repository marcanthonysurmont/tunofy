<template>
    <SwitchGroup as="div" class="flex items-center">
        <Switch
            v-model="enabled"
            :disabled="disabled"
            :class="[
                enabled ? 'bg-primary' : 'bg-zinc-700',
                disabled && 'opacity-50 cursor-not-allowed',
                'relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent transition-all duration-200 ease-in-out focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 focus:outline-hidden',
            ]"
        >
            <span
                aria-hidden="true"
                :class="[
                    enabled ? 'translate-x-5' : 'translate-x-0',
                    'pointer-events-none inline-block size-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out',
                ]"
            />
        </Switch>
        <SwitchLabel
            as="span"
            class="ml-3 text-sm text-zinc-100 flex items-center gap-1"
        >
            <span
                class="font-medium transition-all duration-200 ease-in-out"
                :class="disabled ? 'opacity-40' : ''"
                @click.stop
            >
                {{ label }}
            </span>
            <span class="text-zinc-500">{{ description }}</span>
            <InformationCircleIcon
                v-if="tooltip"
                class="size-5 text-zinc-400 hover:text-zinc-200 transition"
                v-tippy="{
                    content: tooltip,
                    touch: true,
                }"
                @click.stop
            />
        </SwitchLabel>
    </SwitchGroup>
</template>

<script setup>
import { ref, watch, defineProps, defineEmits } from "vue";
import { Switch, SwitchGroup, SwitchLabel } from "@headlessui/vue";
import { InformationCircleIcon } from "@heroicons/vue/24/solid";

const props = defineProps({
    label: { type: String, required: true },
    description: { type: String, required: false },
    modelValue: { type: [Boolean, Number], default: false },
    disabled: { type: Boolean, default: false },
    tooltip: { type: String, default: null }, // Optional tooltip text
});

const emit = defineEmits(["update:modelValue"]);

const enabled = ref(Boolean(props.modelValue));

watch(
    () => props.modelValue,
    (newValue) => {
        enabled.value = Boolean(newValue);
    }
);

watch(enabled, (newValue) => {
    emit("update:modelValue", newValue ? 1 : 0);
});
</script>
