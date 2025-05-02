<template>
    <div>
        <label :for="id" class="block text-sm/6 font-medium text-white">
            {{ label }}
        </label>
        <div :class="['mt-2 grid grid-cols-1', hasError ? 'relative' : '']">
            <div class="relative">
                <input
                    :id="id"
                    type="checkbox"
                    v-model="proxyChecked"
                    :true-value="true"
                    :false-value="false"
                    @change="onChange"
                    :aria-invalid="hasError ? 'true' : undefined"
                    :aria-describedby="hasError ? `${id}-error` : undefined"
                    class="peer appearance-none h-5 w-5 rounded-md bg-checkbox-background outline-1 -outline-offset-1 outline-checkbox-stroke checked:bg-checkbox-background"
                    :class="[
                        hasError
                            ? 'outline-red-500 focus:outline-red-600'
                            : 'outline-checkbox-stroke focus:outline-primary',
                    ]"
                />
                <svg
                    class="absolute left-0.5 top-0.5 h-4 w-4 text-primary pointer-events-none opacity-0 peer-checked:opacity-100"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="3"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
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
import { computed } from "vue";
import { ExclamationCircleIcon } from "@heroicons/vue/16/solid";

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    label: String,
    id: String,
    error: {
        type: String,
        default: "",
    },
});

const emit = defineEmits(["update:modelValue"]);

const hasError = !!props.error;

const proxyChecked = computed({
    get() {
        return props.modelValue;
    },
    set(val) {
        emit("update:modelValue", val);
    },
});

const onChange = () => {
    emit("update:modelValue", proxyChecked.value);
};
</script>

<style scoped>
/* Additional styling here if needed */
</style>
