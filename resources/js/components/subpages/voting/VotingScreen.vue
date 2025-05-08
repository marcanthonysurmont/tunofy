<template>
    <!-- card -->
    <div class="flex flex-col items-center">
        <div
            v-for="(song, index) in songs"
            :key="song.id"
            :class="[
                'w-[300px] h-[400px] rounded-2xl shadow-lg flex flex-col justify-end mb-4 bg-cover bg-center relative border-2 border-card-stroke overflow-hidden',
                resetSwipe || transitionToNext
                    ? 'transition-transform duration-300 ease-in-out'
                    : '',
            ]"
            :style="{
                backgroundImage: `url(${song.cover})`,
                transform: cardTransform(index),
                opacity: cardOpacity(index),
                cursor: isDragging ? 'grabbing' : 'grab',
            }"
            v-show="index === currentIndex"
            @mousedown="startDrag"
            @mousemove="onDrag"
            @mouseup="endDrag"
            @mouseleave="endDrag"
            @touchstart.passive="startDrag"
            @touchmove.passive="onDrag"
            @touchend.passive="endDrag"
        >
            <div class="gradient-bg"></div>
            <div class="relative z-10 text-white text-left px-4 pt-2 pb-5">
                <h2 class="text-2xl font-semibold">{{ song.name }}</h2>
                <p class="text-zinc-300">{{ song.artist }}</p>
            </div>
        </div>

        <!-- action buttons -->
        <div class="mt-5 flex gap-6">
            <div
                class="w-16 h-16 rounded-full bg-zinc-800 flex items-center justify-center border-1 border-zinc-700"
            >
                <button @click="swipeLeft">
                    <XMarkIcon
                        class="size-8 text-[#e95a6c] stroke-2 stroke-[#e95a6c]"
                    />
                </button>
            </div>
            <div
                class="w-16 h-16 rounded-full bg-zinc-800 flex items-center justify-center border-1 border-zinc-700"
            >
                <button @click="kill" class="text-white font-bold">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 512 512"
                        fill="currentColor"
                        class="w-8 h-8 text-white"
                    >
                        <path
                            d="M416 398.9c58.5-41.1 96-104.1 96-174.9C512 100.3 397.4 0 256 0S0 100.3 0 224c0 70.7 37.5 133.8 96 174.9c0 .4 0 .7 0 1.1l0 64c0 26.5 21.5 48 48 48l48 0 0-48c0-8.8 7.2-16 16-16s16 7.2 16 16l0 48 64 0 0-48c0-8.8 7.2-16 16-16s16 7.2 16 16l0 48 48 0c26.5 0 48-21.5 48-48l0-64c0-.4 0-.7 0-1.1zM96 256a64 64 0 1 1 128 0A64 64 0 1 1 96 256zm256-64a64 64 0 1 1 0 128 64 64 0 1 1 0-128z"
                        />
                    </svg>
                </button>
            </div>
            <div
                class="w-16 h-16 rounded-full bg-zinc-800 border-1 border-zinc-700 flex items-center justify-center"
            >
                <button @click="swipeRight">
                    <HeartIcon class="size-8 text-[#74e3b8]" />
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { HeartIcon, XMarkIcon } from "@heroicons/vue/24/solid";
import { ref } from "vue";

const currentIndex = ref(0);
const resetSwipe = ref(false);
const transitionToNext = ref(false);
const swipeLengthX = ref(0);
const isDragging = ref(false);
const startX = ref(0);
const dragDirection = ref(null);

const emit = defineEmits(["endVoting"]);

const SWIPE_THRESHOLD = 100;

function startDrag(event) {
    isDragging.value = true;
    startX.value = getClientX(event);
    dragDirection.value = null;

    //prevent default to avoid text selection during drag
    if (event.type === "mousedown") {
        event.preventDefault();
    }
}

function onDrag(event) {
    if (!isDragging.value) {
        return;
    }

    const currentX = getClientX(event);
    const deltaX = currentX - startX.value;

    // Determine drag direction
    if (deltaX < 0) {
        dragDirection.value = "left";
    } else if (deltaX > 0) {
        dragDirection.value = "right";
    }

    swipeLengthX.value = deltaX;

    //prevent default to avoid scrolling during drag
    if (event.type === "mousemove") {
        event.preventDefault();
    }
}

function endDrag() {
    if (!isDragging.value) {
        return;
    }

    if (Math.abs(swipeLengthX.value) > SWIPE_THRESHOLD) {
        const moveDirection = swipeLengthX.value < 0 ? -1 : 1;
        const targetX = moveDirection * 300;
        swipeLengthX.value = targetX;
        transitionToNext.value = true;

        setTimeout(() => {
            if (dragDirection.value === "left") {
                swipeLeft();
            } else if (dragDirection.value === "right") {
                swipeRight();
            }
            transitionToNext.value = false;
            swipeLengthX.value = 0;
        }, 200);
    } else {
        resetSwipe.value = true;
        swipeLengthX.value = 0;
        setTimeout(() => {
            resetSwipe.value = false;
        }, 300);
    }

    isDragging.value = false;
}

//helper function to get clientX from both mouse and touch events
function getClientX(event) {
    if (event.touches && event.touches.length > 0) {
        return event.touches[0].clientX;
    } else if (event.changedTouches && event.changedTouches.length > 0) {
        return event.changedTouches[0].clientX;
    } else {
        return event.clientX;
    }
}

function swipeLeft() {
    nextSong();
}

function swipeRight() {
    nextSong();
}

function kill() {
    nextSong();
}

function nextSong() {
    if (currentIndex.value < songs.value.length - 1) {
        currentIndex.value++;
    } else {
        console.log("End of the list");
        emit("endVoting");
    }
}

function cardTransform(index) {
    if (index !== currentIndex.value) return "scale(0.9)";
    if (
        resetSwipe.value ||
        (transitionToNext.value && swipeLengthX.value === 0)
    )
        return "translateX(0px) rotate(0deg)";
    return `translateX(${swipeLengthX.value}px) rotate(${
        swipeLengthX.value / 10
    }deg)`;
}

function cardOpacity(index) {
    if (index !== currentIndex.value) {
        return 1;
    }
    return 1 - Math.abs(swipeLengthX.value) / 300;
}

const songs = ref([
    {
        id: 1,
        name: "Into the Rodeo",
        artist: "Travis Scott",
        cover: "https://i.scdn.co/image/ab67616d0000b273f54b99bf27cda88f4a7403ce",
    },
    {
        id: 2,
        name: "FE!N",
        artist: "Travis Scott",
        cover: "https://i.scdn.co/image/ab67616d0000b273cc392813bfd8f63d4d5f4a95",
    },
    {
        id: 3,
        name: "Pornography",
        artist: "Travis Scott",
        cover: "https://i.scdn.co/image/ab67616d0000b2734f0fd9dad63977146e685700",
    },
]);
</script>

<style scoped>
.gradient-bg {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgb(0, 0, 0) 25%, transparent 100%);
    opacity: 1;
    z-index: 0;
}
</style>
