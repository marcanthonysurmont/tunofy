<template>
    <!-- card -->
    <div class="flex flex-col items-center">
        <div
            v-for="(song, index) in songs"
            :key="song.id"
            :class="[
                'w-[300px] h-[400px] rounded-2xl shadow-lg flex flex-col justify-end mb-4 bg-cover bg-center relative border-2 border-card-stroke overflow-hidden',
                resetSwipe || transitionToNext
                    ? 'transition-transform duration-200 ease-in-out'
                    : '',
                isNewCardAnimating && index === currentIndex
                    ? 'card-enter'
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
            <!-- Audio play button overlay -->
            <div
                class="absolute top-4 right-4 z-20"
                v-if="swipeLengthX >= -15 && swipeLengthX <= 15"
            >
                <button
                    @click.stop="toggleAudio(song.track_id)"
                    class="w-12 h-12 bg-primary bg-opacity-50 rounded-full flex items-center justify-center hover:bg-opacity-70 transition-all"
                >
                    <svg
                        v-if="currentPlayingId !== song.track_id || !isPlaying"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="white"
                        class="w-6 h-6"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M4.5 5.653c0-1.426 1.529-2.33 2.779-1.643l11.54 6.348c1.295.712 1.295 2.573 0 3.285L7.28 19.991c-1.25.687-2.779-.217-2.779-1.643V5.653z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    <svg
                        v-else
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="white"
                        class="w-6 h-6"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M6.75 5.25a.75.75 0 01.75-.75H9a.75.75 0 01.75.75v13.5a.75.75 0 01-.75.75H7.5a.75.75 0 01-.75-.75V5.25zm7.5 0A.75.75 0 0115 4.5h1.5a.75.75 0 01.75.75v13.5a.75.75 0 01-.75.75H15a.75.75 0 01-.75-.75V5.25z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </button>
            </div>

            <div class="gradient-bg"></div>
            <div
                v-if="skullAnimation"
                class="absolute inset-0 z-20 bg-black bg-opacity-70 flex items-center justify-center"
                :class="{ 'fade-out': skullAnimationFading }"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 512 512"
                    class="w-32 h-32 text-white animate-pulse"
                    :class="{ 'scale-up': skullAnimation }"
                >
                    <path
                        fill="currentColor"
                        d="M416 398.9c58.5-41.1 96-104.1 96-174.9C512 100.3 397.4 0 256 0S0 100.3 0 224c0 70.7 37.5 133.8 96 174.9c0 .4 0 .7 0 1.1l0 64c0 26.5 21.5 48 48 48l48 0 0-48c0-8.8 7.2-16 16-16s16 7.2 16 16l0 48 64 0 0-48c0-8.8 7.2-16 16-16s16 7.2 16 16l0 48 48 0c26.5 0 48-21.5 48-48l0-64c0-.4 0-.7 0-1.1zM96 256a64 64 0 1 1 128 0A64 64 0 1 1 96 256zm256-64a64 64 0 1 1 0 128 64 64 0 1 1 0-128z"
                    />
                </svg>
            </div>
            <div class="relative z-10 text-white text-left px-4 pt-2 pb-5">
                <h2 class="text-2xl font-semibold">{{ song.name }}</h2>
                <p class="text-zinc-300">{{ song.artist }}</p>
            </div>
            <div
                :style="{ opacity: likeOpacity }"
                class="absolute z-10 text-[#74e3b8] text-left px-4 pt-2 pb-5 top-0 right-0"
            >
                <h1 class="font-semibold text-2xl">LIKE</h1>
            </div>
            <div
                :style="{ opacity: dislikeOpacity }"
                class="absolute z-10 text-[#e95a6c] text-left px-4 pt-2 pb-5 top-0 left-0"
            >
                <h1 class="font-semibold text-2xl">DISLIKE</h1>
            </div>
        </div>

        <!-- action buttons -->
        <div class="mt-5 flex gap-6">
            <div
                class="w-16 h-16 rounded-full bg-zinc-800 flex items-center justify-center border-1 border-zinc-700"
            >
                <button
                    @click="clickLeft"
                    :class="{ bounce: bounceState.left }"
                >
                    <XMarkIcon
                        class="size-8 text-[#e95a6c] stroke-2 stroke-[#e95a6c]"
                    />
                </button>
            </div>
            <div
                class="w-16 h-16 rounded-full bg-zinc-800 flex items-center justify-center border-1 border-zinc-700"
            >
                <button
                    @click="kill"
                    :class="{ bounce: bounceState.kill }"
                    class="text-white font-bold"
                >
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
                <button
                    @click="clickRight"
                    :class="{ bounce: bounceState.right }"
                >
                    <HeartIcon class="size-8 text-[#74e3b8]" />
                </button>
            </div>
        </div>

        <!-- Hidden audio elements container -->
        <div class="hidden">
            <audio
                v-for="(preview, trackId) in audioPreviewCache"
                :key="trackId"
                :ref="
                    (el) => {
                        if (el) audioElements[trackId] = el;
                    }
                "
                :src="preview"
                preload="auto"
                @play="handlePlay(trackId)"
                @pause="isPlaying = false"
                @ended="isPlaying = false"
            ></audio>
        </div>
    </div>
</template>

<script setup>
import { HeartIcon, XMarkIcon } from "@heroicons/vue/24/solid";
import axios from "axios";
import { ref, computed, onMounted, watch } from "vue";

const likeOpacity = computed(() => {
    return swipeLengthX.value > 0 ? Math.min(swipeLengthX.value / 100, 1) : 0;
});
const dislikeOpacity = computed(() => {
    return swipeLengthX.value < 0
        ? Math.min(Math.abs(swipeLengthX.value) / 100, 1)
        : 0;
});

const bounceState = ref({
    left: false,
    right: false,
    kill: false,
});
function bounceButton(button) {
    bounceState.value[button] = true;
    setTimeout(() => {
        bounceState.value[button] = false;
    }, 300);
}

const currentIndex = ref(0);
const resetSwipe = ref(false);
const transitionToNext = ref(false);
const swipeLengthX = ref(0);
const isDragging = ref(false);
const startX = ref(0);
const dragDirection = ref(null);
const skullAnimation = ref(false);
const skullAnimationFading = ref(false);
const isNewCardAnimating = ref(false);

// Audio related state
const audioPreviewCache = ref({});
const audioElements = ref({});
const isPlaying = ref(false);
const currentPlayingId = ref(null);
const loadingQueue = ref([]);
const isLoading = ref(false);

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

function fakeSwipe(direction) {
    let progress = 0;
    const duration = 300;
    const maxDistance = 300;
    const multiplier = direction === "left" ? -1 : 1;
    const startTime = performance.now();

    const animate = (currentTime) => {
        const elapsed = currentTime - startTime;
        progress = Math.min(elapsed / duration, 1);

        //stars fast, eases at the end
        const eased = 1 - Math.pow(1 - progress, 3);

        swipeLengthX.value = multiplier * (maxDistance * eased);

        if (progress < 1) {
            requestAnimationFrame(animate);
        } else {
            transitionToNext.value = true;
            setTimeout(() => {
                if (direction === "left") swipeLeft();
                else swipeRight();
                swipeLengthX.value = 0;
                transitionToNext.value = false;
            }, 200);
        }
    };

    requestAnimationFrame(animate);
}

function swipeLeft() {
    bounceButton("left");
    nextSong();
}

function clickLeft() {
    bounceButton("left");
    fakeSwipe("left");
}

function swipeRight() {
    bounceButton("right");
    nextSong();
}

function clickRight() {
    bounceButton("right");
    fakeSwipe("right");
}

function kill() {
    bounceButton("kill");
    skullAnimation.value = true;

    const shakeIntensity = 10;
    const shakeDuration = 50;
    const shakeCount = 5;

    let shakeIteration = 0;
    const shakeInterval = setInterval(() => {
        swipeLengthX.value =
            Math.random() * shakeIntensity * 2 - shakeIntensity;

        shakeIteration++;
        if (shakeIteration >= shakeCount) {
            clearInterval(shakeInterval);

            //start fade out animation after shake
            setTimeout(() => {
                nextSong();
                skullAnimationFading.value = true;

                //reset position and transition to next card
                swipeLengthX.value = 0;
                transitionToNext.value = true;

                setTimeout(() => {
                    transitionToNext.value = false;
                    skullAnimation.value = false;
                    skullAnimationFading.value = false;
                }, 300);
            }, 600);
        }
    }, shakeDuration);
}

function nextSong() {
    if (currentIndex.value < songs.value.length - 1) {
        // Pause current audio if playing
        if (isPlaying.value && currentPlayingId.value) {
            audioElements.value[currentPlayingId.value]?.pause();
        }

        currentIndex.value++;
        isNewCardAnimating.value = true;

        // Preload next song's audio if available
        const currentSong = songs.value[currentIndex.value];
        const nextSong = songs.value[currentIndex.value + 1];

        // Make sure current song's audio is loaded
        if (currentSong && !audioPreviewCache.value[currentSong.track_id]) {
            getSongFile(currentSong.track_id);
        }

        // Preload next song's audio if it exists
        if (nextSong && !audioPreviewCache.value[nextSong.track_id]) {
            getSongFile(nextSong.track_id);
        }

        //reset animation flag after animation completes
        setTimeout(() => {
            isNewCardAnimating.value = false;
        }, 500);
    } else {
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

    if (skullAnimation.value) {
        return 1;
    }

    return 1 - Math.abs(swipeLengthX.value) / 300;
}

const songs = ref([
    {
        id: 1,
        track_id: "6TQwgRWmnovDECDrHVOxlY",
        name: "The Prayer",
        artist: "Travis Scott",
        cover: "https://i.scdn.co/image/ab67616d0000b2730fc93fe41791c5aa51ae9645",
    },
    {
        id: 2,
        track_id: "42VsgItocQwOQC3XWZ8JNA",
        name: "FE!N",
        artist: "Travis Scott",
        cover: "https://i.scdn.co/image/ab67616d0000b273881d8d8378cd01099babcd44",
    },
    {
        id: 3,
        track_id: "2QeQNF182V61Im0QpjdVta",
        name: "Pornography",
        artist: "Travis Scott",
        cover: "https://i.scdn.co/image/ab67616d0000b2736cfd9a7353f98f5165ea6160",
    },
]);

function toggleAudio(trackId) {
    //if we don't have the audio element yet, queue it up
    if (!audioPreviewCache.value[trackId]) {
        getSongFile(trackId);
        return;
    }

    const audioElement = audioElements.value[trackId];
    audioElement.volume = 0.3;

    if (!audioElement) {
        return;
    }

    //if this is the currently playing audio, toggle play/pause
    if (currentPlayingId.value === trackId) {
        if (isPlaying.value) {
            audioElement.pause();
            isPlaying.value = false;
        } else {
            audioElement.play();
            isPlaying.value = true;
        }
    } else {
        //if another audio is playing, pause it
        if (
            currentPlayingId.value &&
            audioElements.value[currentPlayingId.value]
        ) {
            audioElements.value[currentPlayingId.value].pause();
        }

        //play the new audio
        audioElement.play();
        currentPlayingId.value = trackId;
        isPlaying.value = true;
    }
}

function handlePlay(trackId) {
    //update state when an audio starts playing
    currentPlayingId.value = trackId;
    isPlaying.value = true;
}

async function getSongFile(trackId) {
    //if already in cache, don't fetch again
    if (audioPreviewCache.value[trackId]) {
        return;
    }

    //if already being loaded, don't duplicate the request
    if (loadingQueue.value.includes(trackId)) {
        return;
    }

    //add to loading queue
    loadingQueue.value.push(trackId);

    //if another load is in progress, just queue this one
    if (isLoading.value) {
        return;
    }

    //process loading queue
    processLoadingQueue();
}

async function processLoadingQueue() {
    //if queue is empty, we're done
    if (loadingQueue.value.length === 0) {
        isLoading.value = false;
        return;
    }

    //set loading flag
    isLoading.value = true;

    //get next track ID to load
    const trackId = loadingQueue.value.shift();

    try {
        const response = await axios.post(route("api.spotify.track-preview"), {
            track_id: trackId,
        });

        //store preview URL in cache
        if (response.data && response.data.preview_url) {
            audioPreviewCache.value[trackId] = response.data.preview_url;
        }
    } catch (error) {
        console.error(`Error loading preview for track ${trackId}:`, error);
    } finally {
        //process next item in queue
        processLoadingQueue();
    }
}

onMounted(() => {
    //initialize audio loading -- preload first song
    if (songs.value.length > 0) {
        const currentSong = songs.value[currentIndex.value];
        getSongFile(currentSong.track_id);

        //queue up next song if available
        if (songs.value.length > 1) {
            setTimeout(() => {
                getSongFile(songs.value[1].track_id);
            }, 1000);
        }

        //queue remaining songs with a delay
        if (songs.value.length > 2) {
            setTimeout(() => {
                for (let i = 2; i < songs.value.length; i++) {
                    getSongFile(songs.value[i].track_id);
                }
            }, 2000);
        }
    }
});

//watch for index changes to prefetch audio
watch(currentIndex, (newIndex) => {
    const nextIndex = newIndex + 1;
    if (nextIndex < songs.value.length) {
        getSongFile(songs.value[nextIndex].track_id);
    }
});
</script>

<style scoped>
.gradient-bg {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgb(0, 0, 0) 25%, transparent 100%);
    opacity: 1;
    z-index: 0;
}

.bounce {
    animation: bounceEffect 0.3s ease;
}

@keyframes bounceEffect {
    0% {
        transform: scale(1);
    }
    30% {
        transform: scale(0.9);
    }
    50% {
        transform: scale(1.1);
    }
    70% {
        transform: scale(0.95);
    }
    100% {
        transform: scale(1);
    }
}

.card-enter {
    animation: cardEnterAnimation 0.3s ease-out forwards;
    transform-origin: center;
}

@keyframes cardEnterAnimation {
    0% {
        transform: scale(0.8);
        opacity: 0.5;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.scale-up {
    animation: scaleUp 0.5s ease-in-out;
}

@keyframes scaleUp {
    0% {
        transform: scale(0.5);
        opacity: 0;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.fade-out {
    animation: fadeOut 0.3s ease-in-out forwards;
}

@keyframes fadeOut {
    0% {
        opacity: 1;
    }
    100% {
        opacity: 0;
    }
}
</style>
