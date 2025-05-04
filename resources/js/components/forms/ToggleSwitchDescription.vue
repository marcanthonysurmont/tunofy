<template>
    <SwitchGroup as="div" class="flex items-center">
        <Switch
            v-model="enabled"
            :class="[
                enabled ? 'bg-primary' : 'bg-zinc-700',
                'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 focus:outline-hidden',
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
        <SwitchLabel as="span" class="ml-3 text-sm text-zinc-100">
            <span class="font-medium">{{ label }}</span>
            <span class="text-zinc-500">{{ description }}</span>
        </SwitchLabel>
    </SwitchGroup>
</template>

<script setup>
import { ref } from "vue";
import { Switch, SwitchGroup, SwitchLabel } from "@headlessui/vue";

// Props
const props = defineProps({
    label: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
    initialState: {
        type: Boolean,
        default: false,
    },
});

// Refs
const enabled = ref(props.initialState);
</script>
