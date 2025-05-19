<template>
    <section class="mb-16">
        <h1 class="text-3xl sm:text-4xl font-medium mb-2">User facts</h1>
        <p class="mb-8">Here are some fun facts about the users in your mix.</p>

        <div class="relative">
            <div
                ref="scrollContainer"
                class="flex overflow-x-auto gap-4 pb-12 hide-scrollbar relative"
            >
                <div
                    v-for="card in cards"
                    :key="card.id"
                    class="peeking-item flex-shrink-0 cursor-pointer"
                    @click="toggleFlip(card.id)"
                >
                    <div
                        class="relative w-full h-64 transition-transform duration-500"
                        :class="{ 'rotate-y-180': flippedCards.has(card.id) }"
                        style="transform-style: preserve-3d"
                    >
                        <!-- front side -->
                        <div
                            class="absolute inset-0 text-center bg-card-background overflow-hidden border-2 border-card-stroke shadow-md rounded-lg p-4 flex flex-col items-center justify-center gap-1 backface-hidden"
                        >
                            <QuestionMarkCircleIcon
                                class="absolute size-12 scale-500 rotate-12 opacity-10 text-zinc-600"
                            />
                            <h3 class="text-zinc-300 text-lg">
                                {{ card.title }}
                            </h3>
                            <p class="text-zinc-400">Tap to reveal</p>
                        </div>

                        <!-- back side -->
                        <div
                            class="absolute inset-0 bg-zinc-800 border-2 border-card-stroke shadow-md rounded-lg p-4 flex flex-col items-center justify-center gap-1 text-center text-zinc-100 backface-hidden"
                            style="transform: rotateY(180deg)"
                        >
                            <h3 class="text-zinc-100 text-lg">Placeholder</h3>
                            <p class="text-zinc-400">{{ card.subtitle }}</p>
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
import { ref, onMounted } from "vue";

const flippedCards = ref(new Set());

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

const cards = [
    { id: 1, title: "Encrypted File #1", subtitle: "Unknown Origin" },
    { id: 2, title: "Lost Artifact", subtitle: "Recovered in 1987" },
    { id: 3, title: "Classified Document", subtitle: "Top Secret" },
    { id: 4, title: "Unlabeled USB", subtitle: "Do Not Open" },
    { id: 5, title: "Redacted Note", subtitle: "From Unknown Sender" },
    { id: 6, title: "Abandoned Device", subtitle: "Last active: 2y ago" },
    { id: 7, title: "Abandoned Device", subtitle: "Last active: 2y ago" },
];

onMounted(() => {
    updateScrollState();
    scrollContainer.value.addEventListener("scroll", updateScrollState);
});
</script>

<style scoped>
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
