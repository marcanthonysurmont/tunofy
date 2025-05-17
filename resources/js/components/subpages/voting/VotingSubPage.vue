<template>
    <div class="flex flex-row items-center mb-6 lg:mb-8">
        <!-- <LiveIndicator :isLive="true" class="mb-2 mr-4" /> -->
        <h1 class="text-4xl sm:text-5xl font-medium mb-2">Live Rankings</h1>
        <RegularButton
            color="blue"
            @click="isVisible = true"
            external-class="hidden lg:inline-flex mb-2 ml-6"
        >
            Vote Now
        </RegularButton>
    </div>

    <ul class="flex flex-col gap-3">
        <li
            class="flex flex-row justify-between px-2 py-2 rounded-lg bg-card-background border-2 border-card-stroke text-zinc-200 font-semibold text-sm"
        >
            <div class="w-8 text-center">#</div>
            <div class="flex-1 pl-2">Song</div>
            <div class="w-16 text-center">Rank</div>
        </li>

        <li
            v-for="(song, index) in songsWithRankChange"
            :key="song.id || index"
            class="flex flex-row justify-between items-center px-1 py-2 rounded-lg bg-card-background border-2 border-card-stroke"
        >
            <!-- Rank number -->
            <div class="w-8 text-center font-medium text-sm mr-2">
                {{ index + 1 }}
            </div>

            <!-- Cover + Song info -->
            <div class="flex items-center gap-2 flex-1 min-w-0">
                <img
                    v-lazy="{
                        src: song.image_url,
                        error: '/images/default-song.png',
                        loading: '/images/default-song.png',
                    }"
                    alt="cover"
                    class="size-14 rounded flex-shrink-0"
                />
                <div
                    class="min-w-0 max-w-[75%] sm:max-w-xs md:max-w-md lg:max-w-lg"
                >
                    <div class="font-medium truncate text-sm mb-1">
                        {{ song.name }}
                    </div>
                    <div class="text-zinc-400 text-xs sm:text-sm truncate">
                        {{ song.artist }}
                    </div>
                </div>
            </div>

            <div
                class="w-16 flex items-start justify-center gap-1 text-sm font-medium text-zinc-400"
            >
                <template v-if="song.rankChange > 0">
                    <svg
                        fill="currentColor"
                        viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                        class="size-5 text-green-400"
                    >
                        <path d="M10 5l5 7H5l5-7z" />
                    </svg>
                    <p>{{ song.rankChange }}</p>
                </template>
                <template v-else-if="song.rankChange < 0">
                    <svg
                        fill="currentColor"
                        viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                        class="size-5 text-red-400"
                    >
                        <path d="M10 15l-5-7h10l-5 7z" />
                    </svg>
                    <p>{{ song.rankChange }}</p>
                </template>
                <template v-else> — </template>
            </div>
        </li>
    </ul>

    <!-- floating vote button for mobile -->
    <teleport to="body">
        <RegularButton
            color="blue"
            @click="isVisible = true"
            :class="[
                'fixed-vote-button bottom-32 right-4 !fixed lg:hidden transition-all duration-300 ease-in-out',
                { 'animate-pulse': !isVisible },
            ]"
        >
            Vote Now
        </RegularButton>
    </teleport>

    <VotingFullScreen
        :is-visible="isVisible"
        @close-fullscreen="isVisible = false"
    />
</template>

<script setup>
import { computed, ref } from "vue";
import RegularButton from "@/components/buttons/RegularButton.vue";
import VotingFullScreen from "@/components/subpages/voting/VotingFullScreen.vue";
import { usePage } from "@inertiajs/vue3";
import LiveIndicator from "@/components/subpages/voting/LiveIndicator.vue";
import { HandThumbDownIcon, HandThumbUpIcon } from "@heroicons/vue/24/outline";

const page = usePage();
const props = computed(() => page.props);

const isVisible = ref(false);

const songsWithRankChange = computed(() => {
    return props.value.mix.songs
        .map((song) => ({
            ...song,
            rankChange: Math.floor(Math.random() * 21) - 10,
        }))
        .sort((a, b) => b.rankChange - a.rankChange);
});
</script>
