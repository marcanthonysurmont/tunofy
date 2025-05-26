<template>
    <section class="mb-16">
        <div class="flex flex-row items-center">
            <h1 class="text-3xl sm:text-4xl font-medium">User facts</h1>
            <RegularButton color="blue" external-class="inline-flex ml-6"
                >Refresh cards</RegularButton
            >
        </div>
        <p class="mb-8 text-muted">
            Here are some fun facts about the users in your mix.
        </p>

        <div class="relative">
            <div
                ref="scrollContainer"
                class="flex overflow-x-auto gap-4 pb-12 hide-scrollbar relative"
            >
                <div
                    v-for="card in displayCards"
                    :key="card.uniqueId"
                    class="peeking-item flex-shrink-0 cursor-pointer"
                    @click="toggleFlip(card.uniqueId)"
                >
                    <div
                        class="relative w-full h-96 transition-transform duration-500"
                        :class="{
                            'rotate-y-180': flippedCards.has(card.uniqueId),
                        }"
                        style="transform-style: preserve-3d"
                    >
                        <!-- front side -->
                        <div
                            class="absolute inset-0 text-center bg-card-background overflow-hidden border-2 border-card-stroke shadow-md rounded-lg p-4 flex flex-col items-center justify-center gap-1 backface-hidden"
                        >
                            <QuestionMarkCircleIcon
                                class="absolute size-12 scale-500 rotate-12 opacity-10 text-zinc-600"
                            />
                            <h3 class="text-zinc-100 text-2xl font-medium">
                                {{ card.frontTitle }}
                            </h3>
                            <p class="text-muted text-sm mt-2">Tap to reveal</p>
                        </div>

                        <!-- back side -->
                        <div
                            class="absolute inset-0 bg-zinc-800 border-2 border-card-stroke shadow-md rounded-lg p-4 flex flex-col items-center justify-center gap-1 text-center text-zinc-100 backface-hidden"
                            style="transform: rotateY(180deg)"
                            :style="{
                                background: `linear-gradient(135deg, ${
                                    card.dominantColor?.replace(
                                        '1)',
                                        '0.30)'
                                    ) || '#44444422'
                                } 0%, #27272a 100%)`,
                                borderColor: card.dominantColor || '#444',
                            }"
                        >
                            <!-- due to cross origin bullshit, we have to make sure
                            to only do this if the image is not from tunofy -->
                            <img
                                :src="card.user.avatar_url"
                                alt="User Avatar"
                                @load="
                                    setDominantColor(
                                        card,
                                        $event.target,
                                        card.user.avatar_url
                                    )
                                "
                                v-bind="
                                    card.user.avatar_url.includes('tunofy')
                                        ? {}
                                        : { crossorigin: 'anonymous' }
                                "
                                class="size-32 rounded-full mb-2 object-cover"
                            />
                            <h3 class="text-zinc-100 text-2xl font-medium">
                                {{ card.user.name }}
                            </h3>
                            <h4 class="text-zinc-200 text-lg">
                                {{ card.backValue }}
                                {{ card.backTitle.toLowerCase() }}
                            </h4>
                            <p class="text-muted text-base mt-1">
                                {{ card.backDescription }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div
                class="absolute right-6 bottom-0 flex flex-row items-center gap-2"
            >
                <ChevronLeftIcon
                    :class="[
                        'w-8 h-8 text-dark-white rounded-full bg-zinc-800 hover:bg-zinc-700 border-2 border-card-stroke p-1 cursor-pointer',
                        { 'opacity-40 pointer-events-none': isAtStart },
                    ]"
                    @click="scrollLeft"
                />

                <ChevronRightIcon
                    :class="[
                        'w-8 h-8 text-dark-white rounded-full bg-zinc-800 hover:bg-zinc-700 border-2 border-card-stroke p-1 cursor-pointer',
                        { 'opacity-40 pointer-events-none': isAtEnd },
                    ]"
                    @click="scrollRight"
                />
            </div>
        </div>
    </section>
</template>

<script setup>
import {
    ChevronLeftIcon,
    ChevronRightIcon,
    QuestionMarkCircleIcon,
} from "@heroicons/vue/24/solid";
import { ref, onMounted, computed } from "vue";
import RegularButton from "@/components/buttons/RegularButton.vue";
import { usePage } from "@inertiajs/vue3";
import { FastAverageColor } from "fast-average-color";

const flippedCards = ref(new Set());

const page = usePage();
const cards = ref(page.props.userStats || []);

//card
const cardTypes = {
    songsAdded: {
        condition: (stats) => stats.songs_added > 0,
        frontEmoji: "🎵",
        frontTitle: "Music Curator",
        frontSubtitle: "Someone's been busy...",
        backEmoji: "🎶",
        backTitle: "Songs Added",
        backValue: (stats) => stats.songs_added,
        backDescription: (stats) =>
            `${
                stats.user.name.split(" ")[0]
            } is building the perfect playlist!`,
        priority: (stats) => stats.songs_added * 2,
    },
    totalVotes: {
        condition: (stats) => stats.total_votes > 10,
        frontEmoji: "🗳️",
        frontTitle: "Decision Maker",
        frontSubtitle: "Votes have been cast...",
        backEmoji: "📊",
        backTitle: "Total Votes",
        backValue: (stats) => stats.total_votes,
        backDescription: (stats) =>
            `${stats.user.name.split(" ")[0]} has strong opinions about music!`,
        priority: (stats) => stats.total_votes,
    },
    songsLiked: {
        condition: (stats) => stats.songs_liked > 5,
        frontTitle: "Music Lover",
        frontSubtitle: "Hearts have been given...",
        backEmoji: "💕",
        backTitle: "Songs Liked",
        backValue: (stats) => stats.songs_liked,
        backDescription: (stats) =>
            `${stats.user.name.split(" ")[0]} spreads the musical love!`,
        priority: (stats) => stats.songs_liked * 1.5,
    },
    songsDisliked: {
        condition: (stats) => stats.songs_disliked > 3,
        frontTitle: "Music Critic",
        frontSubtitle: "Not everything hits...",
        backEmoji: "👎",
        backTitle: "Songs Disliked",
        backValue: (stats) => stats.songs_disliked,
        backDescription: (stats) =>
            `${stats.user.name.split(" ")[0]} has high standards!`,
        priority: (stats) => stats.songs_disliked * 1.2,
    },
    songsKilled: {
        condition: (stats) => stats.songs_killed > 0,
        frontTitle: "Song Assassin",
        frontSubtitle: "Some didn't survive...",
        backEmoji: "⚡",
        backTitle: "Songs Killed",
        backValue: (stats) => stats.songs_killed,
        backDescription: (stats) =>
            `${stats.user.name.split(" ")[0]} showed no mercy!`,
        priority: (stats) => stats.songs_killed * 10, //high priority for kills
    },
    likeDislikeRatio: {
        condition: (stats) => stats.songs_liked > 0 && stats.songs_disliked > 0,
        frontTitle: "The Balancer",
        frontSubtitle: "Perfectly balanced...",
        backEmoji: "📈",
        backTitle: "Like/Dislike Ratio",
        backValue: (stats) =>
            `${Math.round(
                (stats.songs_liked /
                    (stats.songs_liked + stats.songs_disliked)) *
                    100
            )}%`,
        backDescription: (stats) =>
            `${stats.user.name.split(" ")[0]} likes ${Math.round(
                (stats.songs_liked /
                    (stats.songs_liked + stats.songs_disliked)) *
                    100
            )}% of what they vote on!`,
        priority: (stats) => Math.abs(stats.songs_liked - stats.songs_disliked),
    },
    activeParticipant: {
        condition: (stats) => stats.total_votes > 20,
        frontTitle: "Super Active",
        frontSubtitle: "Always engaged...",
        backEmoji: "⭐",
        backTitle: "",
        backValue: (stats) => "MVP",
        backDescription: (stats) =>
            `${stats.user.name.split(" ")[0]} is keeping the mix alive!`,
        priority: (stats) => (stats.total_votes > 30 ? 100 : 50),
    },
    newbie: {
        condition: (stats) => stats.total_votes <= 5 && stats.total_votes > 0,
        frontTitle: "Fresh Face",
        frontSubtitle: "Just getting started...",
        backEmoji: "👋",
        backTitle: "New to the Mix",
        backValue: (stats) => "Rookie",
        backDescription: (stats) =>
            `${stats.user.name.split(" ")[0]} is finding their rhythm!`,
        priority: () => 5,
    },
};

const displayCards = computed(() => {
    const userCards = new Map();

    cards.value.forEach((userStat) => {
        let bestCard = null;

        Object.entries(cardTypes).forEach(([type, cardConfig]) => {
            if (cardConfig.condition(userStat)) {
                const card = {
                    uniqueId: `${userStat.id}-${type}`,
                    user: userStat.user,
                    frontTitle: cardConfig.frontTitle,
                    frontSubtitle: cardConfig.frontSubtitle,
                    backEmoji: cardConfig.backEmoji,
                    backTitle: cardConfig.backTitle,
                    backValue:
                        typeof cardConfig.backValue === "function"
                            ? cardConfig.backValue(userStat)
                            : cardConfig.backValue,
                    backDescription:
                        typeof cardConfig.backDescription === "function"
                            ? cardConfig.backDescription(userStat)
                            : cardConfig.backDescription,
                    priority: cardConfig.priority(userStat),
                    dominantColor: "#444",
                };
                if (!bestCard || card.priority > bestCard.priority) {
                    bestCard = card;
                }
            }
        });

        if (bestCard) {
            userCards.set(userStat.id, bestCard);
        }
    });

    //sort by priority and take max 5 cards
    return Array.from(userCards.values())
        .sort((a, b) => b.priority - a.priority)
        .slice(0, 5);
});

function setDominantColor(card, imgEl, url) {
    try {
        if (url.includes("tunofy")) {
            return;
        }
        const fac = new FastAverageColor();
        fac.getColorAsync(imgEl)
            .then((color) => {
                card.dominantColor = color.rgba;
            })
            .catch(() => {
                //when error happens use default color
                card.dominantColor = "#444";
            });
    } catch (e) {
        card.dominantColor = "#444";
    }
}

function toggleFlip(id) {
    if (flippedCards.value.has(id)) {
        flippedCards.value.delete(id);
    } else {
        flippedCards.value.add(id);
    }
}

const scrollContainer = ref(null);
const isAtStart = ref(true);
const isAtEnd = ref(false);

function scrollLeft() {
    scrollContainer.value.scrollBy({ left: -200, behavior: "smooth" });
}

function scrollRight() {
    scrollContainer.value.scrollBy({ left: 200, behavior: "smooth" });
}

function updateScrollState() {
    const el = scrollContainer.value;
    isAtStart.value = el.scrollLeft === 0;
    isAtEnd.value = el.scrollLeft + el.clientWidth >= el.scrollWidth - 1;
}

onMounted(() => {
    updateScrollState();
    scrollContainer.value.addEventListener("scroll", updateScrollState);
});
</script>

<style scoped>
.faded-edges {
    mask-image: radial-gradient(
        circle,
        rgba(0, 0, 0, 1) 60%,
        rgba(0, 0, 0, 0) 100%
    );
    -webkit-mask-image: radial-gradient(
        circle,
        rgba(0, 0, 0, 1) 60%,
        rgba(0, 0, 0, 0) 100%
    );
}

.backface-hidden {
    backface-visibility: hidden;
}

.rotate-y-180 {
    transform: rotateY(180deg);
}

@media (max-width: 468px) {
    .peeking-item {
        min-width: 40dvw;
        max-width: 250px;
    }
}

@media (min-width: 469px) {
    .peeking-item {
        min-width: 42dvw;
        max-width: 250px;
    }
}

@media (min-width: 768px) {
    .peeking-item {
        min-width: 250px;
    }
}
</style>
