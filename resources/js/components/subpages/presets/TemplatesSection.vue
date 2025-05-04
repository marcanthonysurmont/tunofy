<template>
    <h1 class="text-4xl sm:text-5xl font-medium mb-6">Quick Templates</h1>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div
            class="relative bg-card-background border-2 p-4 rounded-lg cursor-pointer transition-all duration-200"
            :class="
                selected === index
                    ? 'border-blue-500 shadow-lg'
                    : 'border-card-stroke'
            "
            v-for="(template, index) in templates"
            :key="index"
            @click="selected = index"
        >
            <CheckCircleIcon
                v-if="selected === index"
                class="absolute top-2 right-2 w-6 h-6 text-blue-500"
            />
            <h2 class="text-2xl font-semibold mb-4 font-body">
                {{ template.name }}
            </h2>
            <p>{{ template.description }}</p>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";
import { CheckCircleIcon } from "@heroicons/vue/24/solid";

const templates = [
    {
        name: "Quick Party",
        description:
            "The perfect option for a party where time is limited but fun is priority.",
        image: "/images/marathon.jpg",
    },
    {
        name: "Marathon",
        description:
            "The perfect option for a party where time is limited but fun is priority.",
        image: "/images/party.jpg",
    },
    {
        name: "Request Night",
        description:
            "The perfect option for a party where time is limited but fun is priority.",
        image: "/images/chill.jpg",
    },
    {
        name: "Custom",
        description:
            "Configure your own settings for this playlist according to your needs.",
        image: "/images/workout.jpg",
    },
];
const selected = ref(0);
</script>
