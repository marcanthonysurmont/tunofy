<template>
    <div class="flex flex-row items-center mb-6 lg:mb-8">
        <!-- <LiveIndicator :isLive="true" class="mb-2 mr-4" /> -->
        <h1 class="text-3xl sm:text-4xl font-medium mb-2">Live Rankings</h1>
        <RegularButton
            v-if="votableSongs.length > 0"
            color="blue"
            @click="isVisible = true"
            external-class="hidden lg:inline-flex mb-2 ml-6"
        >
            Vote Now
        </RegularButton>
    </div>

    <ul class="flex flex-col gap-3">
        <li
            v-for="(song, index) in rankedSongs"
            :key="song.id || index"
            class="flex flex-row justify-between items-center px-1 py-2 rounded-lg"
        >
            <!-- Rank number -->
            <div
                class="w-12 md:w-14 font-medium text-left text-sm mr-2 flex flex-col px-2 gap-1"
            >
                <!-- index number -->
                <p class="text-xl font-medium">
                    {{ index + 1 }}
                </p>

                <!-- rank change row -->
                <template v-if="song.rankChange !== 0">
                    <div class="flex flex-row items-start">
                        <p
                            class="sm:text-sm"
                            :class="[
                                song.rankChange > 0
                                    ? 'text-green-400'
                                    : 'text-red-400',
                            ]"
                        >
                            {{ Math.abs(song.rankChange) }}
                        </p>
                        <svg
                            fill="currentColor"
                            viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                            :class="[
                                'size-5',
                                song.rankChange > 0
                                    ? 'text-green-400'
                                    : 'text-red-400',
                            ]"
                        >
                            <path
                                :d="
                                    song.rankChange > 0
                                        ? 'M10 5l5 7H5l5-7z'
                                        : 'M10 15l-5-7h10l-5 7z'
                                "
                            />
                        </svg>
                    </div>
                </template>
                <template v-else>
                    <p>—</p>
                </template>
            </div>

            <!-- Cover + Song info -->
            <div class="flex items-center gap-2 flex-1 min-w-0">
                <img
                    v-lazy="{
                        src: song.song.image_url,
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
                        {{ song.song.name }}
                    </div>
                    <div class="text-zinc-400 text-xs sm:text-sm truncate">
                        {{ song.song.artist }}
                    </div>
                </div>
            </div>
        </li>
    </ul>

    <!-- floating vote button for mobile -->
    <teleport to="body">
        <RegularButton
            v-if="votableSongs.length > 0"
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

const page = usePage();
const props = computed(() => page.props);
const isVisible = ref(false);
const votableSongs = computed(
    () => Object.values(props.value.votableSongs) || {}
);

// Calculate rank change based on likes and dislikes
const rankedSongs = computed(() => {
    return props.value.allPendingSongs.map((song) => ({
        ...song,
        rankChange: (song.like_count || 0) - (song.dislike_count || 0),
    }));
});
</script>
