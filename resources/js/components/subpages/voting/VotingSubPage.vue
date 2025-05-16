<template>
    <div class="flex flex-row items-center mb-6 lg:mb-8">
        <LiveIndicator :isLive="true" class="mb-2 mr-4" />
        <h1 class="text-4xl sm:text-5xl font-medium mb-2">Live Rankings</h1>
        <RegularButton
            color="blue"
            @click="isVisible = true"
            external-class="hidden lg:inline-flex mb-2 ml-6"
        >
            Vote now!
        </RegularButton>
    </div>

    <ul class="flex flex-col gap-3">
        <div
            v-for="(song, index) in props.mix.songs"
            class="flex flex-row justify-between px-2 py-2 rounded-lg bg-card-background border-2 border-card-stroke"
        >
            <div>
                <div class="flex items-center gap-2 sm:gap-3">
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
                        class="min-w-0 flex-1 max-w-[75%] sm:max-w-xs md:max-w-md lg:max-w-lg"
                    >
                        <div class="font-medium truncate text-sm">
                            {{ song.name }}
                        </div>
                        <div class="text-zinc-400 text-xs sm:text-sm truncate">
                            {{ song.artist }}
                        </div>
                        <div class="flex flex-row items-center gap-2 mt-2">
                            <!-- <HandThumbUpIcon class="size-4 text-zinc-400" /> -->
                            <svg
                                rpl=""
                                fill="currentColor"
                                icon-name="upvote-outline"
                                viewBox="0 0 20 20"
                                xmlns="http://www.w3.org/2000/svg"
                                class="text-zinc-400 size-3"
                            >
                                <path
                                    d="M10 19c-.072 0-.145 0-.218-.006A4.1 4.1 0 0 1 6 14.816V11H2.862a1.751 1.751 0 0 1-1.234-2.993L9.41.28a.836.836 0 0 1 1.18 0l7.782 7.727A1.751 1.751 0 0 1 17.139 11H14v3.882a4.134 4.134 0 0 1-.854 2.592A3.99 3.99 0 0 1 10 19Zm0-17.193L2.685 9.071a.251.251 0 0 0 .177.429H7.5v5.316A2.63 2.63 0 0 0 9.864 17.5a2.441 2.441 0 0 0 1.856-.682A2.478 2.478 0 0 0 12.5 15V9.5h4.639a.25.25 0 0 0 .176-.429L10 1.807Z"
                                ></path>
                            </svg>
                            <p class="text-xs text-zinc-400">
                                {{
                                    Math.floor(Math.random() * (10 - -5 + 1)) +
                                    -5
                                }}
                            </p>
                            <svg
                                rpl=""
                                fill="currentColor"
                                icon-name="downvote-outline"
                                viewBox="0 0 20 20"
                                xmlns="http://www.w3.org/2000/svg"
                                class="text-zinc-400 size-3"
                            >
                                <path
                                    d="M10 1c.072 0 .145 0 .218.006A4.1 4.1 0 0 1 14 5.184V9h3.138a1.751 1.751 0 0 1 1.234 2.993L10.59 19.72a.836.836 0 0 1-1.18 0l-7.782-7.727A1.751 1.751 0 0 1 2.861 9H6V5.118a4.134 4.134 0 0 1 .854-2.592A3.99 3.99 0 0 1 10 1Zm0 17.193 7.315-7.264a.251.251 0 0 0-.177-.429H12.5V5.184A2.631 2.631 0 0 0 10.136 2.5a2.441 2.441 0 0 0-1.856.682A2.478 2.478 0 0 0 7.5 5v5.5H2.861a.251.251 0 0 0-.176.429L10 18.193Z"
                                ></path>
                            </svg>
                            <!-- <HandThumbDownIcon class="size-4 text-zinc-400" /> -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
            Vote now!
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
</script>
