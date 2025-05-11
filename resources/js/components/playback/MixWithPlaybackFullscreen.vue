<template>
    <Transition name="slide-up" appear>
        <div
            v-if="isVisible"
            class="fixed inset-0 z-50 bg-background-page text-white flex flex-col items-center justify-between p-4"
        >
            <div class="flex flex-row items-center justify-start w-full mb-8">
                <button @click="emit('close-fullscreen')" class="text-3xl">
                    <ChevronDownIcon class="size-8 text-white" />
                </button>
            </div>
            <div class="mt-10 w-3/4 max-w-xs" v-if="currentTrack === null">
                <img
                    src="/images/default-song.png"
                    alt="Album cover"
                    class="rounded-xl w-full"
                />
                <div class="text-left">
                    <h2 class="text-2xl font-semibold mt-6">No song playing</h2>
                    <p class="text-gray-400 text-sm">No artist found</p>
                </div>
            </div>
            <div class="mt-10 w-3/4 max-w-xs" v-else>
                <img
                    :src="currentTrack.album.images[0].url"
                    alt="Album cover"
                    class="rounded-xl w-full"
                />
                <div class="text-left">
                    <h2 class="font-semibold text-md font-body">
                        {{ currentTrack.name }}
                    </h2>
                    <p class="text-sm text-zinc-400">
                        {{
                            currentTrack.artists?.map((a) => a.name).join(", ")
                        }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-4 mb-10">
                <BackwardIcon
                    @click="previousSong"
                    class="size-8"
                    :class="[
                        isMixActive
                            ? 'cursor-pointer'
                            : 'cursor-not-allowed opacity-50',
                    ]"
                />
                <PauseCircleIcon
                    v-if="isPlaying"
                    class="size-16"
                    :class="[
                        isMixActive
                            ? 'cursor-pointer'
                            : 'cursor-not-allowed opacity-50',
                    ]"
                    @click="pauseSong"
                />
                <PlayCircleIcon
                    v-else
                    class="size-16"
                    :class="[
                        isMixActive
                            ? 'cursor-pointer'
                            : 'cursor-not-allowed opacity-50',
                    ]"
                    @click="playSong"
                />
                <ForwardIcon
                    @click="nextSong"
                    class="size-8"
                    :class="[
                        isMixActive
                            ? 'cursor-pointer'
                            : 'cursor-not-allowed opacity-50',
                    ]"
                />
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
    transition: transform 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
    transform: translateY(100%);
}
</style>

<script setup>
import {
    ChevronDownIcon,
    PlayCircleIcon,
    ForwardIcon,
    BackwardIcon,
    PauseCircleIcon,
} from "@heroicons/vue/24/solid";
const emit = defineEmits([
    "close-fullscreen",
    "pause-song",
    "play-song",
    "next-song",
    "previous-song",
]);

const props = defineProps({
    mix: {
        type: Object,
        required: true,
    },
    isVisible: {
        type: Boolean,
        default: false,
    },
    isPlaying: {
        type: Boolean,
        default: false,
    },
    currentTrack: {
        type: Object,
        default: null,
    },
    isMixActive: {
        type: [Number, Boolean],
        default: false,
    },
});

function previousSong() {
    if (props.isMixActive === false) {
        return;
    }
    emit("previous-song");
}

function nextSong() {
    if (props.isMixActive === false) {
        return;
    }
    emit("next-song");
}

function playSong() {
    if (props.isMixActive === false) {
        return;
    }
    emit("play-song");
}

function pauseSong() {
    if (props.isMixActive === false) {
        return;
    }
    emit("pause-song");
}
</script>
