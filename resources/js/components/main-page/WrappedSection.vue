<template>
    <div
        class="flex flex-col md:flex-row bg-cyan-600 rounded-2xl shadow-lg overflow-hidden relative"
    >
        <div
            class="p-8 sm:p-12 flex flex-col justify-center text-center md:text-left gap-4 z-40"
        >
            <h2 class="text-3xl md:text-4xl font-bold text-dark-white">
                {{ title }}
            </h2>
            <p class="text-zinc-200 text-base md:text-lg max-w-xl font-medium">
                {{ description }}
            </p>
            <button
                @click="visitMix"
                class="self-center md:self-start bg-zinc-100 text-zinc-900 text-sm md:text-base font-semibold px-5 py-2 rounded-full hover:bg-zinc-300 transition"
            >
                {{ ctaText }}
            </button>
        </div>
        <div
            class="absolute -bottom-8 -right-8 sm:-bottom-20 sm:-right-20 w-72 sm:w-96 overflow-hidden rotate-45 z-0"
        >
            <div
                class="absolute inset-0 pointer-events-none z-10 custom-gradient"
            ></div>

            <div class="flex flex-col gap-2 relative z-0 p-0.5">
                <Marquee :speed="27" :autoFill="true">
                    <img
                        alt="Cover image of a song"
                        :src="image"
                        class="h-20 sm:h-32 w-auto rounded-sm mr-3"
                        v-for="(image, i) in songsTop"
                        :key="'top-' + i"
                        draggable="false"
                    />
                </Marquee>
                <Marquee :speed="20" :autoFill="true">
                    <img
                        alt="Cover image of a song"
                        :src="image"
                        class="h-20 sm:h-32 w-auto rounded-sm mr-3"
                        v-for="(image, i) in songsMid"
                        :key="'mid-' + i"
                        draggable="false"
                    />
                </Marquee>
                <Marquee :speed="31" :autoFill="true">
                    <img
                        alt="Cover image of a song"
                        :src="image"
                        class="h-20 sm:h-32 w-auto rounded-sm mr-3"
                        v-for="(image, i) in songsBottom"
                        :key="'bot-' + i"
                        draggable="false"
                    />
                </Marquee>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Marquee } from "vue-fast-marquee";
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

const songsTop = [
    "https://i.scdn.co/image/ab67616d0000b273dcd4d70294f17175991ba1bb",
    "https://i.scdn.co/image/ab67616d0000b27332c5d1e207364562fe2160b7",
    "https://i.scdn.co/image/ab67616d0000b273c03c17681e99230377f5dbef",
    "https://i.scdn.co/image/ab67616d0000b273b4eabaa89caca03de8c3fa8d",
    "https://i.scdn.co/image/ab67616d0000b273f569b809ca999649fa704277",
    "https://i.scdn.co/image/ab67616d0000b273e2565f077fcf8d8bc6f401fc",
    "https://i.scdn.co/image/ab67616d0000b2732729a5c5fb3756653da57b0c",
];

const songsMid = [
    "https://i.scdn.co/image/ab67616d0000b273f54b99bf27cda88f4a7403ce",
    "https://i.scdn.co/image/ab67616d0000b27302928b251e41844f5186920e",
    "https://i.scdn.co/image/ab67616d0000b273c4fee55d7b51479627c31f89",
    "https://i.scdn.co/image/ab67616d0000b273da9e59639a9759d8952890c6",
    "https://i.scdn.co/image/ab67616d0000b273806c160566580d6335d1f16c",
    "https://i.scdn.co/image/ab67616d0000b2738b52c6b9bc4e43d873869699",
    "https://i.scdn.co/image/ab67616d0000b273f52f6a4706fea3bde44467c3",
];

const songsBottom = [
    "https://i.scdn.co/image/ab67616d0000b273fbc71c99f9c1296c56dd51b6",
    "https://i.scdn.co/image/ab67616d0000b273338fabbb1729a74d655a6a85",
    "https://i.scdn.co/image/ab67616d0000b27378de8b28de36a74afc0348b5",
    "https://i.scdn.co/image/ab67616d0000b2732cd55246d935a8a77cb4859e",
    "https://i.scdn.co/image/ab67616d0000b273e71ccb392305961631deb63b",
    "https://i.scdn.co/image/ab67616d0000b273aca059cebc1841277db22d1c",
    "https://i.scdn.co/image/ab67616d0000b273881d8d8378cd01099babcd44",
];
</script>

<style scoped>
.custom-gradient {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 10;

    /* tailwind cyan-600 exact match*/
    --gradient-from: oklch(60.9% 0.126 221.723);
    --gradient-to: transparent;
    --gradient-stop: 55%;

    background: linear-gradient(
        to right,
        var(--gradient-from) var(--gradient-stop),
        var(--gradient-to) 100%
    );
}

/* sm breakpoint at 640px */
@media (min-width: 640px) {
    .custom-gradient {
        width: 80%;
        height: 100%;

        background-image: repeating-linear-gradient(
            90deg,
            var(--gradient-from),
            var(--gradient-from) 26%,
            transparent 100%
        );

        background-clip: border-box;
        -webkit-text-fill-color: inherit;
    }
}
</style>
