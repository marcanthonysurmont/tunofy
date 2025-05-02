<template>
    <div>
        <div class="flex justify-between">
            <label :for="id" class="block text-sm/6 font-medium text-white">
                {{ label }}
            </label>
            <span
                v-if="optional"
                class="text-sm/6 text-gray-500"
                :id="`${id}-optional`"
                >Optional</span
            >
        </div>
        <div :class="['mt-2 grid grid-cols-1', hasError ? 'relative' : '']">
            <input
                :id="id"
                :name="name"
                :type="type"
                :placeholder="placeholder"
                :value="modelValue"
                @input="$emit('update:modelValue', $event.target.value)"
                :aria-invalid="hasError ? 'true' : undefined"
                :aria-describedby="getAriaDescribedBy"
                :class="[
                    'col-start-1 row-start-1 block w-full rounded-md py-1.5 pr-10 pl-3 text-base outline-2 -outline-offset-1 placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 sm:text-sm/6',
                    hasError
                        ? 'bg-inputfield-background text-red-400 outline-red-500 focus:outline-red-600 placeholder:text-red-500'
                        : 'bg-inputfield-background text-white outline-inputfield-stroke focus:outline-primary',
                ]"
            />
            <ExclamationCircleIcon
                v-if="hasError"
                class="pointer-events-none col-start-1 row-start-1 mr-3 size-5 self-center justify-self-end text-red-500 sm:size-4"
                aria-hidden="true"
            />
        </div>
        <p
            v-if="hasError"
            class="mt-2 text-sm text-red-500"
            :id="`${id}-error`"
        >
            {{ error }}
        </p>
    </div>
</template>

<script setup>
import { ExclamationCircleIcon } from "@heroicons/vue/16/solid";
import { computed } from "vue";

const props = defineProps({
    modelValue: String,
    label: String,
    name: String,
    id: String,
    type: {
        type: String,
        default: "text",
    },
    placeholder: String,
    error: {
        type: String,
        default: "",
    },
    optional: {
        type: Boolean,
        default: false,
    },
});

const hasError = computed(() => !!props.error);
const getAriaDescribedBy = computed(() => {
    if (hasError.value) return `${props.id}-error`;
    if (props.optional) return `${props.id}-optional`;
    return undefined;
});

defineEmits(["update:modelValue"]);
</script>
