<template>
    <div class="swipe-container flex flex-col items-center">
        <div
            v-for="(song, index) in songs"
            :key="song.id"
            v-show="index === currentIndex"
            class="w-[300px] h-[400px] rounded-2xl shadow-lg flex flex-col items-center justify-center"
            @mousedown="startDrag"
            @mouseup="endDrag"
        >
            <img
                :src="song.cover"
                alt="cover"
                class="w-[200px] h-[200px] object-cover rounded-lg"
            />
            <h2 class="text-2xl font-semibold">{{ song.name }}</h2>
            <p class="">{{ song.artist }}</p>
        </div>

        <div class="mt-5 flex gap-6">
            <div
                class="w-16 h-16 rounded-full bg-red-500 flex items-center justify-center"
            >
                <button @click="swipeLeft">
                    <XMarkIcon class="size-8 text-white" />
                </button>
            </div>
            <div
                class="w-16 h-16 rounded-full bg-gray-500 flex items-center justify-center"
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
                class="w-16 h-16 rounded-full bg-green-500 flex items-center justify-center"
            >
                <button @click="swipeRight">
                    <HeartIcon class="size-8 text-white" />
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { HeartIcon, XMarkIcon } from "@heroicons/vue/24/solid";
import { ref } from "vue";

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
        cover: "https://i.scdn.co/image/ab67616d0000b273f54b99bf27cda88f4a7403ce",
    },
    {
        id: 3,
        name: "Pornography",
        artist: "Travis Scott",
        cover: "https://i.scdn.co/image/ab67616d0000b273f54b99bf27cda88f4a7403ce",
    },
    { id: 4, name: "None", artist: "None", cover: "/images/default-song.png" },
]);

const currentIndex = ref(0);
const swipeLeft = () => {
    console.log("Disliked:", songs.value[currentIndex.value]);
    nextSong();
};
const swipeRight = () => {
    console.log("Liked:", songs.value[currentIndex.value]);
    nextSong();
};
const kill = () => {
    console.log("Killed:", songs.value[currentIndex.value]);
    nextSong();
};
const nextSong = () => {
    if (currentIndex.value < songs.value.length - 1) {
        currentIndex.value++;
    } else {
        console.log("No more songs");
    }
};

const startDrag = (e) => {};
const endDrag = (e) => {};
</script>

<style scoped></style>
