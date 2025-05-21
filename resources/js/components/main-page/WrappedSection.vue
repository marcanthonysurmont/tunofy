<template>
    <div
        class="flex flex-col md:flex-row bg-gradient-to-r from-blue-950 via-blue-800 to-cyan-600 rounded-2xl shadow-lg overflow-hidden"
    >
        <!-- <img
            :src="imgUrl"
            alt="Wrapped image"
            class="w-full md:w-1/5 object-cover aspect-square"
        /> -->
        <div
            class="p-6 flex flex-col justify-center text-center md:text-left gap-4"
        >
            <h2 class="text-3xl md:text-4xl font-bold text-zinc-100">
                {{ title }}
            </h2>
            <p class="text-zinc-200 text-base md:text-lg max-w-xl">
                {{ description }}
            </p>
            <button
                @click="visitMix"
                class="self-center md:self-start bg-zinc-100 text-zinc-900 text-sm md:text-base font-semibold px-5 py-2 rounded-full hover:bg-zinc-300 transition"
            >
                {{ ctaText }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { router } from "@inertiajs/vue3";
defineProps({
    imgUrl: String,
    title: String,
    description: String,
    ctaText: String,
    onClick: Function,
});

function visitMix() {
    router.visit(route("remix"));
}
</script>
