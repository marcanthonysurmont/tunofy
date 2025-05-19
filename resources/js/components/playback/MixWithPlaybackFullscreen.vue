<template>
    <Transition name="slide-up" appear>
        <div
            v-if="isVisible"
            class="fixed inset-0 z-50 flex flex-col items-center justify-between p-4"
            :style="backgroundStyle"
        >
            <div
                class="relative flex items-center justify-center w-full mb-0 mt-4"
            >
                <button
                    @click="emit('close-fullscreen')"
                    class="absolute left-4 text-3xl"
                >
                    <ChevronDownIcon class="size-8 text-white" />
                </button>
                <div
                    class="max-w-[60%] overflow-hidden whitespace-nowrap truncate"
                >
                    <h1 class="truncate text-white text-3xl font-medium">
                        {{ mix.name }}
                    </h1>
                </div>
            </div>

            <!-- when no music is playing -->
            <div class="mt-10 w-3/4 max-w-xs" v-if="currentTrack === null">
                <img
                    src="/images/default-song.png"
                    alt="Album cover"
                    class="rounded-xl w-full"
                    ref="defaultCoverRef"
                    @load="extractColorsFromDefaultCover"
                />
                <div class="text-left mt-2">
                    <h2 class="font-semibold text-md font-body text-white">
                        No song playing
                    </h2>
                    <p class="text-sm text-zinc-40">No artist found</p>
                </div>
            </div>

            <!-- when music is playing -->
            <div class="mt-10 w-3/4 max-w-xs" v-else>
                <img
                    :src="currentTrack.album.images[0].url"
                    alt="Album cover"
                    class="rounded-xl w-full"
                    ref="albumCoverRef"
                    @load="extractColors"
                    crossorigin="anonymous"
                />
                <div class="text-left mt-2">
                    <h2 class="font-semibold text-md font-body text-white">
                        {{ currentTrack.name }}
                    </h2>
                    <p class="text-sm text-zinc-400">
                        {{
                            currentTrack.artists?.map((a) => a.name).join(", ")
                        }}
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-between mb-10 w-full px-4">
                <!-- device Selector -->
                <DeviceSelectorStatus
                    @click.stop="showDeviceSelector"
                    :selected-device="selectedDevice"
                    :devices="devices"
                />

                <!-- playback Controls -->
                <div class="flex items-center gap-4">
                    <BackwardIcon
                        @click="previousSong"
                        class="size-8 text-white"
                        :class="[
                            isMixActive
                                ? 'cursor-pointer'
                                : 'cursor-not-allowed opacity-50',
                        ]"
                    />
                    <PauseCircleIcon
                        v-if="isPlaying"
                        @click="pauseSong"
                        class="size-16 text-white"
                        :class="[
                            isMixActive
                                ? 'cursor-pointer'
                                : 'cursor-not-allowed opacity-50',
                        ]"
                    />
                    <PlayCircleIcon
                        v-else
                        @click="playSong"
                        class="size-16 text-white"
                        :class="[
                            isMixActive
                                ? 'cursor-pointer'
                                : 'cursor-not-allowed opacity-50',
                        ]"
                    />
                    <ForwardIcon
                        @click="nextSong"
                        class="size-8 text-white"
                        :class="[
                            isMixActive
                                ? 'cursor-pointer'
                                : 'cursor-not-allowed opacity-50',
                        ]"
                    />
                </div>
                <CoDJSelector
                    @click.stop="showCoDjSelector"
                    v-if="currentTrack !== null && mix.authorized.isOwner"
                />
                <div class="w-5" v-else></div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import {
    ChevronDownIcon,
    PlayCircleIcon,
    ForwardIcon,
    BackwardIcon,
    PauseCircleIcon,
} from "@heroicons/vue/24/solid";

import DeviceSelectorStatus from "@/components/playback/device/DeviceSelectorStatus.vue";
import CoDJSelector from "@/components/playback/co-dj/CoDJSelector.vue";

const emit = defineEmits([
    "close-fullscreen",
    "pause-song",
    "play-song",
    "next-song",
    "previous-song",
    "show-device-selector",
    "show-co-dj-selector",
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
    devices: {
        type: Array,
        required: true,
    },
    selectedDevice: {
        required: true,
    },
});

watch(
    () => props.isVisible,
    (newValue) => {
        if (newValue) {
            document.body.style.overflow = "hidden";
        } else {
            document.body.style.overflow = "";
        }
    }
);

const dominantColor = ref("rgb(18, 18, 18)");
const albumCoverRef = ref(null);
const defaultCoverRef = ref(null);

const backgroundStyle = computed(() => {
    return {
        background: `linear-gradient(to bottom, ${dominantColor.value} 0%, rgb(18, 18, 18) 100%)`,
    };
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

function showDeviceSelector() {
    emit("show-device-selector");
}

function showCoDjSelector() {
    emit("show-co-dj-selector");
}

function extractColors() {
    if (!albumCoverRef.value) return;

    try {
        const img = albumCoverRef.value;

        // Create a canvas to analyze the image
        const canvas = document.createElement("canvas");
        const ctx = canvas.getContext("2d");
        canvas.width = img.width;
        canvas.height = img.height;

        //draw the image on the canvas
        ctx.drawImage(img, 0, 0, img.width, img.height);

        //sample pixels from the top area (where Spotify usually takes color from)
        const imageData = ctx.getImageData(
            0,
            0,
            img.width,
            Math.min(img.height / 3, 100)
        ).data;

        //calculate average color from the top section
        let r = 0,
            g = 0,
            b = 0,
            count = 0;

        for (let i = 0; i < imageData.length; i += 4) {
            r += imageData[i];
            g += imageData[i + 1];
            b += imageData[i + 2];
            count++;
        }

        if (count > 0) {
            r = Math.floor(r / count);
            g = Math.floor(g / count);
            b = Math.floor(b / count);

            //adjust brightness to make it more vibrant but not too light
            const brightness = (r + g + b) / 3;
            const targetBrightness = 100;

            if (brightness > 0) {
                const factor = Math.min(targetBrightness / brightness, 1.5);
                r = Math.min(Math.floor(r * factor), 255);
                g = Math.min(Math.floor(g * factor), 255);
                b = Math.min(Math.floor(b * factor), 255);
            }

            dominantColor.value = `rgb(${r}, ${g}, ${b})`;
        }
    } catch (error) {
        console.error("Error extracting colors:", error);
        dominantColor.value = "rgb(18, 18, 18)";
    }
}

function extractColorsFromDefaultCover() {
    if (!defaultCoverRef.value) return;
    try {
        //use a standard color for default cover
        dominantColor.value = "rgb(83, 83, 83)";
    } catch (error) {
        console.error("Error setting default color:", error);
        dominantColor.value = "rgb(18, 18, 18)";
    }
}

//watch for changes in current track to update the gradient
watch(
    () => props.currentTrack,
    (newTrack) => {
        if (newTrack) {
            //the @load event on the image will trigger extractColors
        } else {
            extractColorsFromDefaultCover();
        }
    },
    { immediate: true }
);

//extract colors when the component becomes visible
watch(
    () => props.isVisible,
    (visible) => {
        if (visible && props.currentTrack) {
            // Need to use nextTick to ensure the image is in the DOM
            setTimeout(extractColors, 50);
        }
    }
);

onMounted(() => {
    if (props.currentTrack) {
        setTimeout(extractColors, 50);
    } else {
        extractColorsFromDefaultCover();
    }
});
</script>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
    transition: transform 0.15s ease, background 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
    transform: translateY(100%);
}
</style>
