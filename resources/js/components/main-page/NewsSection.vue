<template>
    <section class="mb-16">
        <h1 class="text-3xl sm:text-4xl font-medium mb-2">Latest News</h1>
        <p class="mb-8">
            Stay updated with the latest versions, teasers, and announcements.
        </p>

        <div class="relative">
            <!-- Scrollable container -->
            <div
                ref="scrollContainer"
                class="flex overflow-x-auto gap-4 pb-12 hide-scrollbar"
            >
                <div
                    v-for="news in newsItems"
                    :key="news.id"
                    class="peeking-item flex-shrink-0 bg-card-background border-2 border-card-stroke h-auto shadow-md rounded-lg p-4 flex flex-col"
                >
                    <img
                        :src="news.img"
                        alt="News image"
                        class="rounded-md mb-4 object-cover w-full h-32 sm:h-40"
                    />
                    <h3
                        class="text-zinc-100 text-xl font-semibold mb-2 min-h-[68.8px]"
                    >
                        {{ news.title }}
                    </h3>
                    <p class="text-zinc-300 flex-grow mb-4">
                        {{ news.teaser }}
                    </p>
                    <small class="text-zinc-400 mb-4">{{ news.date }}</small>
                    <button
                        class="self-start text-primary hover:text-blue-600 font-semibold cursor-pointer"
                        @click="handleReadMore(news.id)"
                    >
                        Read more &rarr;
                    </button>
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
import { ChevronLeftIcon, ChevronRightIcon } from "@heroicons/vue/24/solid";
import { ref, onMounted } from "vue";

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

function handleReadMore(id) {
    // For now just console.log, you can replace with navigation or modal opening
    console.log("Read more clicked for news id:", id);
}

const newsItems = [
    {
        id: 1,
        img: "https://www.hdwallpapers.in/thumbs/2020/playboi_carti_is_looking_up_wearing_purple_and_black_coat_with_white_tshirt_and_goggles_hd_music-t2.jpg",
        title: "Version 2.1 Released",
        teaser: "Packed with new features and important bug fixes to improve your experience.",
        date: "2025-05-15",
    },
    {
        id: 2,
        img: "https://storage.googleapis.com/pr-newsroom-wp/1/2025/04/AI-Playlist-Newsroom-Header-2048x1166.jpg",
        title: "Sneak Peek: Upcoming UI Overhaul",
        teaser: "Get ready for a fresh, modern look coming soon in the next update.",
        date: "2025-05-10",
    },
    {
        id: 3,
        img: "https://storage.googleapis.com/pr-newsroom-wp/1/2025/05/Final-Use_Aparshakti-Khurana-Dustee-Jenkins-Badshah-1-768x440.jpg",
        title: "Community Contest Winners",
        teaser: "Congratulations to the winners of our latest design contest!",
        date: "2025-05-01",
    },
    {
        id: 4,
        img: "https://storage.googleapis.com/pr-newsroom-wp/1/2025/03/Spotify_FTRGenericHeaders_250219_ML_V02-24-1440x821.jpg",
        title: "Server Maintenance Scheduled",
        teaser: "We'll be offline for maintenance on May 25th from 1 AM to 3 AM UTC.",
        date: "2025-04-28",
    },
    {
        id: 5,
        img: "https://storage.googleapis.com/pr-newsroom-wp/1/2025/05/Play-Counts_FTR_ArticleHeader_1440x820_2xOptimized-1536x875.jpg",
        title: "New Feature Teaser",
        teaser: "We're working on an exciting feature to make collaboration easier.",
        date: "2025-04-20",
    },
    {
        id: 6,
        img: "https://www.hdwallpapers.in/thumbs/2020/playboi_carti_is_looking_up_wearing_purple_and_black_coat_with_white_tshirt_and_goggles_hd_music-t2.jpg",
        title: "Launch of Tunofy!",
        teaser: "Tunofy has officially launched! Explore the roadmap we've set for the next few months.",
        date: "2025-04-15",
    },
];

onMounted(() => {
    updateScrollState();
    scrollContainer.value.addEventListener("scroll", updateScrollState);
});
</script>

<style scoped>
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
