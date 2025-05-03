<template>
    <fieldset>
        <legend class="text-sm/6 font-semibold text-zinc-100">
            {{ legend }}
        </legend>
        <p v-if="description" class="mt-1 text-sm/6 text-zinc-400">
            {{ description }}
        </p>
        <div
            class="mt-6 space-y-6 sm:flex sm:items-center sm:space-y-0 sm:space-x-10"
        >
            <div
                v-for="option in options"
                :key="option.id"
                class="flex items-center"
            >
                <input
                    type="radio"
                    :id="option.id"
                    :name="name"
                    :value="option.id"
                    v-model="localValue"
                    class="relative size-4 appearance-none rounded-full border border-[color:var(--color-card-stroke)] bg-[color:var(--color-card-background)] before:absolute before:inset-1 before:rounded-full before:bg-[color:var(--color-card-background)] not-checked:before:hidden checked:border-[color:var(--color-primary)] checked:bg-[color:var(--color-primary)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[color:var(--color-primary)] disabled:border-zinc-700 disabled:bg-zinc-800 disabled:before:bg-zinc-700"
                />
                <label
                    :for="option.id"
                    class="ml-3 block text-sm/6 font-medium text-zinc-100"
                >
                    {{ option.title }}
                </label>
            </div>
        </div>
    </fieldset>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    options: Array,
    modelValue: String,
    name: String,
    legend: String,
    description: String,
});

const emit = defineEmits(["update:modelValue"]);

const localValue = computed({
    get: () => props.modelValue,
    set: (val) => emit("update:modelValue", val),
});
</script>
