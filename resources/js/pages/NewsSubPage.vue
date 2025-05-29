<template>
    <Head title="Tunofy | News" />
    <AppLayout>
        <div class="flex items-center w-full mb-8">
            <div class="flex-none">
                <Link :href="route('app')">
                    <ChevronLeftIcon class="size-8 text-white cursor-pointer" />
                </Link>
            </div>
            <!-- <div class="flex-grow text-center">
                <h1 class="text-4xl">Version 2.1 Released</h1>
            </div>
            <div class="flex-none w-8"></div> -->
        </div>

        <div class="w-full h-72 sm:h-96 mb-8">
            <img
                class="w-full h-full object-cover rounded-2xl object-center"
                src="https://cdn.prod.website-files.com/5e0f1144930a8bc8aace526c/65d5c1f4a90585d36de4f2f0_Embed%20your%20DevCard-p-1080.jpg"
                alt="News Image"
            />
        </div>
        <div
            class="px-3 py-4 bg-card-background rounded-xl border-l-4 border-l-primary mb-8"
        >
            <div class="flex flex-row gap-4 justify-between items-center">
                <div class="flex flex-row gap-4 items-center">
                    <img
                        src="https://platform-lookaside.fbsbx.com/platform/profilepic/?asid=1300984920068935&height=300&width=300&ext=1751012523&hash=AT9KBeR_i11e0kb1SgoWCagw"
                        class="size-14 rounded-full"
                    />
                    <div class="flex flex-col gap-2 justify-center">
                        <p class="text-base font-medium">Gilles Serrien</p>
                        <p class="text-xs sm:text-sm text-muted">
                            Published May 29, 2025
                        </p>
                    </div>
                </div>
                <div class="flex flex-row gap-2.5">
                    <Instagram class="size-6" />
                    <Linkedin class="size-6" />
                </div>
            </div>
        </div>
        <h1 class="text-4xl sm:text-5xl font-semibold">
            Tunofy 1.0 just launched!
        </h1>

        <div class="mt-8">
            <h2 class="text-xl sm:text-2xl font-medium mb-2">
                Revolutionizing Music Creation
            </h2>
            <p class="sm:text-lg mb-6 text-dark-white">
                The music industry is experiencing a seismic shift as artificial
                intelligence technology reaches unprecedented levels of
                sophistication. Musicians and producers alike are discovering
                new ways to harness AI tools to enhance their creativity,
                streamline workflows, and break down barriers that once limited
                musical expression. From indie artists to major labels, the
                impact of these innovations is spreading rapidly.
            </p>

            <h2 class="text-xl sm:text-2xl font-medium mb-2">
                AI-Powered Music Generators
            </h2>
            <p class="sm:text-lg mb-6 text-dark-white">
                A new wave of AI-powered music generators is not just creating
                melodies but composing entire symphonies that rival human
                creativity. Leading tech companies have unveiled platforms
                capable of generating original compositions in any genre within
                minutes. These tools allow artists to experiment with new styles
                or quickly produce backing tracks, opening up exciting
                opportunities across the industry.
            </p>

            <h2 class="text-xl sm:text-2xl font-medium mb-2">How It Works</h2>
            <p class="sm:text-lg mb-6 text-dark-white">
                These sophisticated systems analyze millions of musical
                patterns, harmonies, and structures from diverse datasets to
                create uniquely compelling pieces that resonate with listeners
                across demographics. By leveraging deep learning models and
                neural networks, AI can understand and replicate the nuances of
                different instruments, rhythms, and emotional tones, delivering
                music that feels authentic and fresh.
            </p>

            <h2 class="text-xl sm:text-2xl font-medium mb-2">User Feedback</h2>
            <p class="sm:text-lg text-dark-white">
                These sophisticated systems analyze millions of musical
                patterns, harmonies, and structures from diverse datasets to
                create uniquely compelling pieces that resonate with listeners
                across demographics. By leveraging deep learning models and
                neural networks, AI can understand and replicate the nuances of
                different instruments, rhythms, and emotional tones, delivering
                music that feels authentic and fresh.
            </p>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/layouts/AppLayout.vue";
import { ChevronLeftIcon } from "@heroicons/vue/24/solid";
import { Head, Link } from "@inertiajs/vue3";
import { Instagram, Linkedin } from "lucide-vue-next";
</script>
