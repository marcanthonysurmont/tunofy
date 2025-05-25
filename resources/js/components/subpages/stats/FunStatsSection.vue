<template>
    <section class="mb-16">
        <h1 class="text-3xl sm:text-4xl font-medium mb-6">Fun stats</h1>
        <!-- <p class="mb-8">Blablabla</p> -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
            <!-- Amount of songs -->
            <div
                class="relative bg-card-background px-4 py-6 sm:px-6 lg:px-8 border-card-stroke border-2 rounded-sm overflow-hidden"
            >
                <p class="text-sm/6 font-medium text-zinc-400">
                    Amount of songs played
                </p>
                <p class="mt-2 flex items-baseline gap-x-2">
                    <span
                        class="text-4xl font-semibold tracking-tight text-white"
                    >
                        <NumberFlow
                            :value="mixStats.songs_played"
                            :will-change="true"
                        />
                    </span>
                </p>
                <div
                    class="absolute bottom-2 right-2 scale-300 rotate-12 opacity-10"
                >
                    <svg
                        viewBox="0 0 24 24"
                        width="32"
                        height="32"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <circle
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="white"
                            stroke-width="2"
                        />
                        <circle
                            cx="12"
                            cy="12"
                            r="3"
                            stroke="white"
                            stroke-width="2"
                        />
                        <circle cx="12" cy="12" r="1" fill="white" />
                    </svg>
                </div>
            </div>

            <!-- Average song kills -->
            <div
                class="relative bg-card-background px-4 py-6 sm:px-6 lg:px-8 border-card-stroke border-2 rounded-sm overflow-hidden"
            >
                <p class="text-sm/6 font-medium text-zinc-400">
                    Amount of songs killed
                </p>
                <p class="mt-2 flex items-baseline gap-x-2">
                    <span
                        class="text-4xl font-semibold tracking-tight text-white"
                    >
                        <NumberFlow
                            :value="mixStats.songs_killed"
                            :will-change="true"
                        />
                    </span>
                    <span class="text-sm text-zinc-400">
                        {{ mixStats.songs_killed === 1 ? "kill" : "kills" }}
                    </span>
                </p>
                <div
                    class="absolute bottom-4 right-4 scale-225 rotate-12 opacity-10"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 512 512"
                        width="32"
                        height="32"
                        fill="white"
                    >
                        <path
                            d="M416 398.9c58.5-41.1 96-104.1 96-174.9C512 100.3 397.4 0 256 0S0 100.3 0 224c0 70.7 37.5 133.8 96 174.9c0 .4 0 .7 0 1.1l0 64c0 26.5 21.5 48 48 48l48 0 0-48c0-8.8 7.2-16 16-16s16 7.2 16 16l0 48 64 0 0-48c0-8.8 7.2-16 16-16s16 7.2 16 16l0 48 48 0c26.5 0 48-21.5 48-48l0-64c0-.4 0-.7 0-1.1zM96 256a64 64 0 1 1 128 0A64 64 0 1 1 96 256zm256-64a64 64 0 1 1 0 128 64 64 0 1 1 0-128z"
                        />
                    </svg>
                </div>
            </div>

            <!-- Most votes for a song -->
            <div
                class="relative bg-card-background px-4 py-6 sm:px-6 lg:px-8 border-card-stroke border-2 rounded-sm overflow-hidden"
            >
                <p class="text-sm/6 font-medium text-zinc-400">Total votes</p>
                <p class="mt-2 flex items-baseline gap-x-2">
                    <span
                        class="text-4xl font-semibold tracking-tight text-white"
                    >
                        <NumberFlow
                            :value="mixStats.total_votes"
                            :will-change="true"
                        />
                    </span>
                </p>
                <div
                    class="absolute bottom-2 right-2 scale-300 rotate-12 opacity-10"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 448 512"
                        width="32"
                        height="32"
                        fill="white"
                    >
                        <path
                            d="M64 32C28.7 32 0 60.7 0 96L0 416c0 35.3 28.7 64 64 64l320 0c35.3 0 64-28.7 64-64l0-320c0-35.3-28.7-64-64-64L64 32zm64 192c17.7 0 32 14.3 32 32l0 96c0 17.7-14.3 32-32 32s-32-14.3-32-32l0-96c0-17.7 14.3-32 32-32zm64-64c0-17.7 14.3-32 32-32s32 14.3 32 32l0 192c0 17.7-14.3 32-32 32s-32-14.3-32-32l0-192zM320 288c17.7 0 32 14.3 32 32l0 32c0 17.7-14.3 32-32 32s-32-14.3-32-32l0-32c0-17.7 14.3-32 32-32z"
                        />
                    </svg>
                </div>
            </div>

            <!-- Minutes listened -->
            <div
                class="relative bg-card-background px-4 py-6 sm:px-6 lg:px-8 border-card-stroke border-2 rounded-sm overflow-hidden"
            >
                <p class="text-sm/6 font-medium text-zinc-400">
                    Minutes listened
                </p>
                <p class="mt-2 flex items-baseline gap-x-2">
                    <span
                        class="text-4xl font-semibold tracking-tight text-white"
                    >
                        <NumberFlow
                            :value="Math.round(mixStats.minutes_played)"
                            :will-change="true"
                        />
                    </span>
                </p>
                <div
                    class="absolute bottom-2 right-2 scale-300 rotate-12 opacity-10"
                >
                    <ClockIcon class="size-8" />
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ClockIcon } from "@heroicons/vue/24/outline";
import { usePage } from "@inertiajs/vue3";
import NumberFlow from "@number-flow/vue";
import { computed, onMounted, ref } from "vue";

const page = usePage();
const props = computed(() => page.props);
const mixStats = computed(() => props.value.mixStats);
</script>
