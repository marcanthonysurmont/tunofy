<template>
    <div
        @click="showPlayerFullscreen"
        class="fixed bottom-0 left-0 right-0 w-full z-40 bg-card-background/40 border-t-2 lg:border-2 backdrop-blur-xl border-card-stroke p-4 lg:fixed lg:bottom-5 lg:left-1/2 lg:-translate-x-1/2 lg:ml-[160px] lg:max-w-2xl lg:rounded-lg xl:max-w-4xl"
    >
        <div class="mb-4 flex flex-row justify-between items-center">
            <ToggleSwitchReadValue
                :model-value="isMixActive"
                :disabled="isLoading"
                :label="isMixActive ? 'Queue active' : 'Queue inactive'"
                @click.stop="emit('toggle-mix-active')"
            />
        </div>

        <!-- Loading states - Prioritize showing one at a time -->
        <div v-if="isLoading" class="text-center p-5 text-zinc-400">
            <div class="flex items-center justify-center">
                <SpinningCircle />
                <p>Updating state...</p>
            </div>
        </div>

        <div
            v-else-if="isSyncingWithSpotify"
            class="text-center p-5 text-zinc-400"
        >
            <div class="flex items-center justify-center">
                <SpinningCircle />
                <p>Syncing with Spotify...</p>
            </div>
        </div>

        <div v-else-if="isMixActive === false">
            <div
                class="flex flex-row items-center w-full lg:hidden gap-2 sm:gap-0"
            >
                <div class="flex items-center flex-1">
                    <img
                        src="/images/default-song.png"
                        class="size-10 rounded-sm"
                        alt="Album Art"
                    />
                    <div class="flex-1 ml-2">
                        <div class="mb-1">
                            <p class="font-semibold text-sm">No song playing</p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-400">No song playing</p>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col gap-3">
                    <div
                        class="flex flex-row items-center justify-center flex-none gap-4"
                    >
                        <div
                            class="relative"
                            @click.stop="showDeviceSelectorModal = true"
                        >
                            <svg
                                data-encore-id="icon"
                                role="img"
                                aria-hidden="true"
                                viewBox="0 0 16 16"
                                class="size-5 text-white"
                                fill="currentColor"
                            >
                                <path
                                    d="M6 2.75C6 1.784 6.784 1 7.75 1h6.5c.966 0 1.75.784 1.75 1.75v10.5A1.75 1.75 0 0 1 14.25 15h-6.5A1.75 1.75 0 0 1 6 13.25V2.75zm1.75-.25a.25.25 0 0 0-.25.25v10.5c0 .138.112.25.25.25h6.5a.25.25 0 0 0 .25-.25V2.75a.25.25 0 0 0-.25-.25h-6.5zm-6 0a.25.25 0 0 0-.25.25v6.5c0 .138.112.25.25.25H4V11H1.75A1.75 1.75 0 0 1 0 9.25v-6.5C0 1.784.784 1 1.75 1H4v1.5H1.75zM4 15H2v-1.5h2V15z"
                                ></path>
                                <path
                                    d="M13 10a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm-1-5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"
                                ></path>
                            </svg>
                            <XCircleIcon
                                v-if="devices.length === 0"
                                class="absolute -top-2 -right-2 text-red-500 size-4"
                            />
                            <ExclamationCircleIcon
                                v-else-if="selectedDevice === null"
                                class="absolute -top-2 -right-2 text-yellow-500 size-4"
                            />
                        </div>
                        <PlayIcon class="size-7 opacity-50" @click.stop />
                    </div>
                </div>
            </div>
        </div>

        <div v-else-if="!currentTrack" class="text-center py-2">
            <p class="text-zinc-400">Nothing is currently playing..</p>
        </div>

        <div v-else-if="isMixActive">
            <div
                class="flex flex-row items-center w-full lg:hidden gap-2 sm:gap-0"
            >
                <div class="flex items-center flex-1">
                    <img
                        v-if="currentTrack.album?.images?.length"
                        :src="currentTrack.album.images[0].url"
                        class="size-10 rounded-sm"
                        alt="Album Art"
                    />
                    <div class="flex-1 ml-2">
                        <div class="mb-1">
                            <p class="font-semibold text-sm">
                                {{ currentTrack.name }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-400">
                                {{
                                    currentTrack.artists
                                        ?.map((a) => a.name)
                                        .join(", ")
                                }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col gap-3">
                    <div
                        class="flex flex-row items-center justify-center flex-none gap-2"
                    >
                        <div
                            class="relative"
                            @click.stop="showDeviceSelectorModal = true"
                        >
                            <svg
                                data-encore-id="icon"
                                role="img"
                                aria-hidden="true"
                                viewBox="0 0 16 16"
                                class="size-5 text-white"
                                fill="currentColor"
                            >
                                <path
                                    d="M6 2.75C6 1.784 6.784 1 7.75 1h6.5c.966 0 1.75.784 1.75 1.75v10.5A1.75 1.75 0 0 1 14.25 15h-6.5A1.75 1.75 0 0 1 6 13.25V2.75zm1.75-.25a.25.25 0 0 0-.25.25v10.5c0 .138.112.25.25.25h6.5a.25.25 0 0 0 .25-.25V2.75a.25.25 0 0 0-.25-.25h-6.5zm-6 0a.25.25 0 0 0-.25.25v6.5c0 .138.112.25.25.25H4V11H1.75A1.75 1.75 0 0 1 0 9.25v-6.5C0 1.784.784 1 1.75 1H4v1.5H1.75zM4 15H2v-1.5h2V15z"
                                ></path>
                                <path
                                    d="M13 10a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm-1-5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"
                                ></path>
                            </svg>
                            <XCircleIcon
                                v-if="devices.length === 0"
                                class="absolute -top-2 -right-2 text-red-500 size-4 animate-bounce"
                            />
                            <ExclamationCircleIcon
                                v-else-if="selectedDevice === null"
                                class="absolute -top-2 -right-2 text-yellow-500 size-4 animate-bounce"
                            />
                        </div>
                        <PlayIcon
                            @click.stop="emit('resume-mix')"
                            v-if="!isPlaying"
                            class="size-7 cursor-pointer"
                            :class="selectedDevice === null ? 'opacity-50' : ''"
                        />
                        <PauseIcon
                            @click.stop="emit('pause-mix')"
                            v-if="isPlaying"
                            class="size-7 cursor-pointer"
                            :class="selectedDevice === null ? 'opacity-50' : ''"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
    <SelectDeviceModal
        :is-visible="showDeviceSelectorModal"
        :devices="devices"
        :selected-device="selectedDevice"
        :is-loading="isLoadingDevices"
        @update:selected-device="emit('select-device', $event)"
        @refresh-devices="emit('refresh-devices')"
        @close-modal="showDeviceSelectorModal = false"
    />
    <MixWithPlaybackFullscreen
        @close-fullscreen="showPlaybackFullscreen = false"
        @previous-song="emit('previous-song')"
        @play-song="emit('resume-mix')"
        @pause-song="emit('pause-mix')"
        @next-song="emit('next-song')"
        @show-device-selector="showDeviceSelectorModal = true"
        :is-visible="showPlaybackFullscreen"
        :mix="mix"
        :is-playing="isPlaying"
        :current-track="currentTrack"
        :is-mix-active="isMixActive"
        :devices="devices"
        :selected-device="selectedDevice"
    />
</template>

<script setup>
import { ref } from "vue";
import SpinningCircle from "@/components/spinners/SpinningCircle.vue";
import ToggleSwitchReadValue from "@/components/forms/ToggleSwitchReadValue.vue";
import SelectDeviceModal from "@/components/modals/playback/SelectDeviceModal.vue";
import {
    ExclamationCircleIcon,
    PlayIcon,
    PauseIcon,
    XCircleIcon,
} from "@heroicons/vue/16/solid";
import MixWithPlaybackFullscreen from "./MixWithPlaybackFullscreen.vue";

const props = defineProps({
    mix: {
        type: Object,
        required: true,
    },
    devices: {
        type: Array,
        required: true,
    },
    selectedDevice: {
        required: true,
    },
    isMixActive: {
        type: [Boolean, Number],
        required: true,
    },
    isPlaying: {
        type: Boolean,
        required: true,
    },
    currentTrack: {
        type: Object,
        required: false,
    },
    isLoading: {
        type: Boolean,
        required: true,
    },
    isSyncingWithSpotify: {
        type: Boolean,
        required: true,
    },
    isLoadingDevices: {
        type: Boolean,
        required: true,
    },
    isTransferingDevice: {
        type: Boolean,
        required: true,
    },
});

const emit = defineEmits([
    "toggle-mix-active",
    "resume-mix",
    "pause-mix",
    "select-device",
    "refresh-devices",
    "close-queue-modal",
    "previous-song",
    "next-song",
]);

const showDeviceSelectorModal = ref(false);
const showPlaybackFullscreen = ref(false);

function showPlayerFullscreen() {
    showPlaybackFullscreen.value = true;
}
</script>
