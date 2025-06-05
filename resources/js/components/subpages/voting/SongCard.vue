<template>
    <div
        :class="[
            'w-[300px] h-[400px] rounded-2xl shadow-lg flex flex-col justify-end mb-4 bg-cover bg-center relative border-2 border-card-stroke overflow-hidden',
            resetSwipe || transitionToNext
                ? 'transition-transform duration-200 ease-in-out'
                : '',
            isNewCardAnimating ? 'card-enter' : '',
        ]"
        :style="{
            backgroundImage: `url(${song.image_url})`,
            transform: cardTransform,
            opacity: cardOpacity,
            cursor: isDisabled
                ? 'not-allowed'
                : isDragging
                ? 'grabbing'
                : 'grab',
        }"
        v-show="isActive"
        @mousedown="startDrag"
        @mousemove="onDrag"
        @mouseup="endDrag"
        @mouseleave="endDrag"
        @touchstart.passive="startDrag"
        @touchmove.passive="onDrag"
        @touchend.passive="endDrag"
    >
        <!-- Audio play button overlay -->
        <transition name="fade">
            <div
                class="absolute top-4 right-4 z-20"
                v-if="swipeLengthX >= -15 && swipeLengthX <= 15"
            >
                <button
                    :class="
                        hasPreview
                            ? 'opacity-100 bg-primary cursor-pointer'
                            : 'opacity-75 cursor-not-allowed bg-zinc-500'
                    "
                    :disabled="!hasPreview"
                    @click.stop="() => emits('toggle-audio', song.spotify_id)"
                    class="w-12 h-12 bg-opacity-50 rounded-full flex items-center justify-center hover:bg-opacity-70 transition-all"
                >
                    <PlayIcon
                        v-if="
                            currentPlayingId !== song.spotify_id || !isPlaying
                        "
                        class="w-6 h-6 text-white"
                    />
                    <PauseIcon v-else class="w-6 h-6 text-white" />
                </button>
            </div>
        </transition>

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
            <p class="text-zinc-300 mb-6">{{ song.artist }}</p>
            <div class="flex items-center gap-2 text-xs text-zinc-300">
                <img
                    :src="song.user.avatar_url"
                    alt="Avatar of the user who added the song to the mix"
                    class="w-5 h-5 rounded-full object-cover"
                />
                <span class="font-medium text-zinc-400">{{
                    song.user.name
                }}</span>
            </div>
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
</template>

<script setup>
import { PlayIcon, PauseIcon } from "@heroicons/vue/24/solid";

const props = defineProps({
    song: Object,
    isActive: Boolean,
    swipeLengthX: Number,
    likeOpacity: Number,
    dislikeOpacity: Number,
    skullAnimation: Boolean,
    skullAnimationFading: Boolean,
    isNewCardAnimating: Boolean,
    resetSwipe: Boolean,
    transitionToNext: Boolean,
    isDragging: Boolean,
    isDisabled: Boolean,
    currentPlayingId: String,
    isPlaying: Boolean,
    cardTransform: String,
    cardOpacity: Number,
    hasPreview: Boolean,
});

const emits = defineEmits([
    "toggle-audio",
    "start-drag",
    "on-drag",
    "end-drag",
]);

function startDrag(e) {
    emits("start-drag", e);
}
function onDrag(e) {
    emits("on-drag", e);
}
function endDrag(e) {
    emits("end-drag", e);
}
</script>

<style scoped>
.gradient-bg {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgb(0, 0, 0) 25%, transparent 100%);
    opacity: 1;
    z-index: 0;
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
