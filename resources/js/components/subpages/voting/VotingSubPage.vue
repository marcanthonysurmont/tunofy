<template>
    <div class="flex flex-row items-center mb-4 lg:mb-5">
        <!-- <LiveIndicator :isLive="true" class="mb-2 mr-4" /> -->
        <div class="flex flex-col">
            <h1 class="text-3xl sm:text-4xl font-medium mb-3">Live Rankings</h1>
            <p v-if="rankedSongs.length === 0" class="text-muted">
                No voting ranks are available to display. Come back later!
            </p>
        </div>
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
        <transition-group name="list">
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
                    <p
                        class="font-medium"
                        :class="
                            index === 0 && song.is_killed === 0
                                ? 'text-xl'
                                : 'text-sm'
                        "
                    >
                        {{ index + 1 }}
                    </p>
                    <transition name="fade-with-slide" mode="out-in">
                        <!-- rank change row -->
                        <template v-if="song.is_killed === 1">
                            <p>—</p>
                        </template>
                        <template v-else-if="song.rankChange !== 0">
                            <div class="flex flex-row items-start">
                                <p
                                    class="sm:text-sm"
                                    :class="[
                                        song.rankChange > 0
                                            ? 'text-green-400'
                                            : 'text-red-400',
                                    ]"
                                >
                                    <NumberFlow
                                        :will-change
                                        :animated
                                        :value="Math.abs(song.rankChange)"
                                    />
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
                        <template v-else-if="song.rankChange === 0">
                            <p>—</p>
                        </template>
                    </transition>
                </div>
                <div
                    class="flex items-center gap-2 flex-1 min-w-0"
                    :class="song.is_killed === 1 ? 'opacity-50' : ''"
                >
                    <div class="relative">
                        <img
                            v-lazy="{
                                src: song.song.image_url,
                                error: '/images/default-song.png',
                                loading: '/images/default-song.png',
                            }"
                            alt="cover"
                            :class="[
                                'rounded flex-shrink-0',
                                index === 0 && song.is_killed === 0
                                    ? 'size-20'
                                    : 'size-11',
                                { 'brightness-30': song.is_killed === 1 },
                            ]"
                        />
                        <div
                            v-if="song.is_killed === 1"
                            class="absolute inset-0 flex items-center justify-center pointer-events-none"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 512 512"
                                fill="currentColor"
                                class="w-10 h-10 text-white bg-black/60 rounded-full p-1"
                            >
                                <path
                                    d="M416 398.9c58.5-41.1 96-104.1 96-174.9C512 100.3 397.4 0 256 0S0 100.3 0 224c0 70.7 37.5 133.8 96 174.9c0 .4 0 .7 0 1.1l0 64c0 26.5 21.5 48 48 48l48 0 0-48c0-8.8 7.2-16 16-16s16 7.2 16 16l0 48 64 0 0-48c0-8.8 7.2-16 16-16s16 7.2 16 16l0 48 48 0c26.5 0 48-21.5 48-48l0-64c0-.4 0-.7 0-1.1zM96 256a64 64 0 1 1 128 0A64 64 0 1 1 96 256zm256-64a64 64 0 1 1 0 128 64 64 0 1 1 0-128z"
                                />
                            </svg>
                        </div>
                    </div>
                    <div
                        class="ml-1 min-w-0 max-w-[75%] sm:max-w-xs md:max-w-md lg:max-w-lg"
                    >
                        <div
                            v-if="index === 0 && song.is_killed === 0"
                            class="flex items-center gap-1 mb-1"
                        >
                            <span
                                class="inline-flex items-center rounded-md bg-blue-400/10 px-2 py-1 text-xs font-medium text-blue-400 ring-1 ring-blue-400/30 ring-inset"
                                >Next up</span
                            >
                        </div>

                        <div
                            class="font-medium truncate mb-1"
                            :class="
                                index === 0 && song.is_killed === 0
                                    ? 'text-base'
                                    : 'text-xs'
                            "
                        >
                            {{ song.song.name }}
                        </div>
                        <div
                            class="text-muted truncate"
                            :class="
                                index === 0 && song.is_killed === 0
                                    ? 'text-sm'
                                    : 'text-xs'
                            "
                        >
                            {{ song.song.artist }}
                        </div>
                    </div>
                </div>
            </li>
        </transition-group>
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
import { computed, onMounted, ref } from "vue";
import RegularButton from "@/components/buttons/RegularButton.vue";
import VotingFullScreen from "@/components/subpages/voting/VotingFullScreen.vue";
import { router, usePage } from "@inertiajs/vue3";
import NumberFlow from "@number-flow/vue";

const page = usePage();
const props = computed(() => page.props);
const isVisible = ref(false);
const votableSongs = computed(
    () => Object.values(props.value.votableSongs) || {}
);

// Calculate rank change based on likes and dislikes
const rankedSongs = computed(() => {
    return props.value.allPendingSongs
        .map((song) => ({
            ...song,
            rankChange: (song.like_count || 0) - (song.dislike_count || 0),
        }))
        .sort((a, b) => {
            //non-killed songs first, then killed songs
            if (a.is_killed === b.is_killed) return 0;
            return a.is_killed === 1 ? 1 : -1;
        });
});

onMounted(() => {
    Echo.channel(`mix.${props.value.mix.id}`)
        .listen(".vote-updated", () => {
            router.reload({ only: ["allPendingSongs", "success", "danger"] });
            console.log("Vote updated");
        })
        .listen(".queue-state-updated", () => {
            router.reload({
                only: ["allPendingSongs", "votableSongs", "success", "danger"],
            });
            console.log("Queue state updated");
        });
});
</script>
