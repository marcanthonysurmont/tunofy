<template>
    <div>
        <div class="flex justify-between">
            <label :for="id" class="block text-sm/6 font-medium text-white">
                {{ label }}
            </label>
            <span
                v-if="optional"
                class="text-sm/6 text-zinc-500"
                :id="`${id}-optional`"
                >Optional</span
            >
        </div>

        <div :class="['mt-2 grid grid-cols-1', hasError ? 'relative' : '']">
            <div class="relative w-fit">
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
                        'block appearance-none border-1 border-inputfield-stroke rounded-md py-1.5 pr-10 pl-3 text-base outline-1 -outline-offset-1 placeholder:text-zinc-500 focus:outline-1 focus:-outline-offset-1 sm:text-sm/6',
                        hasError
                            ? 'bg-inputfield-background text-red-400 outline-red-500 focus:outline-red-600 placeholder:text-red-500'
                            : 'bg-inputfield-background text-white outline-inputfield-stroke focus:outline-primary',
                        type === 'number' ? 'custom-number-input' : '',
                        inputClass,
                    ]"
                />

                <!-- Custom chevrons for number input -->
                <div
                    v-if="type === 'number'"
                    class="absolute inset-y-0 right-2 flex flex-col items-center justify-center"
                >
                    <svg
                        @click="increment"
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-2.5 w-2.5 text-zinc-400 cursor-pointer hover:text-zinc-200"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 15l-7-7-7 7"
                        />
                    </svg>
                    <svg
                        @click="decrement"
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-2.5 w-2.5 mt-1 text-zinc-400 cursor-pointer hover:text-zinc-200"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 9l7 7 7-7"
                        />
                    </svg>
                </div>
            </div>

            <!-- Error icon -->
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
    modelValue: [String, Number],
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
    inputClass: {
        type: [String, Array, Object],
        default: "",
    },
});

const hasError = computed(() => !!props.error);
const getAriaDescribedBy = computed(() => {
    if (hasError.value) return `${props.id}-error`;
    if (props.optional) return `${props.id}-optional`;
    return undefined;
});

const emit = defineEmits(["update:modelValue"]);

function increment() {
    const value = Number(props.modelValue) || 0;
    emit("update:modelValue", value + 1);
}

function decrement() {
    const value = Number(props.modelValue) || 0;
    emit("update:modelValue", value - 1);
}
</script>

<style scoped>
/* Remove native arrows */
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
input[type="number"] {
    -moz-appearance: textfield;
}
</style>
