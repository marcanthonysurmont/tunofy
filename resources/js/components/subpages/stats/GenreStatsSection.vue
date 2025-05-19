<template>
    <section class="mb-16">
        <h1 class="text-3xl sm:text-4xl font-medium mb-2">Top genres</h1>
        <p class="mb-8">These genres were the most popular.</p>
        <div
            v-for="genre in genres"
            :key="genre.name"
            class="flex items-center mb-2 max-w-3xl"
        >
            <div
                ref="barTarget"
                class="text-white bg-primary px-4 py-1 rounded-4xl transition-all duration-1000 ease-out"
                :style="{
                    width: targetIsVisible ? `${genre.frequency}%` : '0%',
                }"
            >
                <p class="text-sm font-bold">{{ genre.name }}</p>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref } from "vue";
import { useIntersectionObserver } from "@vueuse/core";

const genres = [
    { name: "Rap", frequency: 90 },
    { name: "Pop", frequency: 75 },
    { name: "Rock", frequency: 60 },
    { name: "Jazz", frequency: 40 },
    { name: "Classical", frequency: 30 },
    { name: "Metal", frequency: 20 },
];

const barTarget = ref(null);
const targetIsVisible = ref(false);

//observes if element enters or leaves viewport
useIntersectionObserver(
    barTarget,
    ([{ isIntersecting }]) => {
        if (isIntersecting) {
            targetIsVisible.value = true;
        }
    },
    //10% visible threshold
    { threshold: 0.1 }
);
</script>
