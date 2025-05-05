<template>
    <h1 class="text-4xl sm:text-5xl font-medium mb-6">Quick Templates</h1>
    <div
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-4 gap-4 mb-12"
    >
        <div
            class="relative bg-card-background border-2 p-4 rounded-lg cursor-pointer transition-all duration-200"
            :class="
                templatesStore.selectedTemplateIndex === index
                    ? 'border-blue-500 shadow-lg'
                    : 'border-card-stroke'
            "
            v-for="(template, index) in templatesStore.templates"
            :key="index"
            @click="templatesStore.selectTemplate(index)"
        >
            <CheckCircleIcon
                v-if="templatesStore.selectedTemplateIndex === index"
                class="absolute top-2 right-2 w-6 h-6 text-blue-500"
            />
            <h2 class="text-2xl font-semibold font-body">
                {{ template.name }}
            </h2>
            <p>{{ template.description }}</p>
        </div>
    </div>
</template>

<script setup>
import { CheckCircleIcon } from "@heroicons/vue/24/solid";
import { useTemplatesStore } from "@/stores/StorePresets.js";

const templatesStore = useTemplatesStore();
</script>
