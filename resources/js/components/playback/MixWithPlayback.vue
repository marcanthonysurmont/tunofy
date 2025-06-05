<template>
    <div
        class="fixed bottom-0 left-0 right-0 w-full z-40 bg-card-background/40 border-t-2 lg:border-2 backdrop-blur-xl border-card-stroke p-4 lg:fixed lg:bottom-5 lg:left-1/2 lg:-translate-x-1/2 lg:ml-[160px] lg:max-w-2xl lg:rounded-lg xl:max-w-4xl"
    >
        <!-- top part of the player -->
        <div class="mb-4 flex flex-row justify-between items-center">
            <ToggleSwitchReadValue
                :model-value="isMixActive"
                :disabled="isLoading || queueActivationDisabled"
                :label="isMixActive ? 'Queue active' : 'Queue inactive'"
                @click="emit('toggle-mix-active')"
            />
        </div>

        <!-- Loading states - Prioritize showing one at a time -->
        <div v-if="isLoading" class="text-center text-zinc-400 min-h-[48px]">
            <div class="flex items-center justify-center gap-2">
                <SpinningCircle />
                <p>Updating state...</p>
            </div>
        </div>

        <!-- Loading state while we're syncing with Spotify -->
        <div
            v-else-if="isSyncingWithSpotify"
            class="text-center text-zinc-400 min-h-[48px]"
        >
            <div class="flex items-center justify-center gap-2">
                <SpinningCircle />
                <p>Syncing with Spotify...</p>
            </div>
        </div>

        <!-- desktop playback with no song playing -->
        <div v-else-if="isMixActive === false">
            <div class="flex-row items-center justify-around w-full flex">
                <div class="flex items-center gap-1 w-[30%]">
                    <!-- Placeholder image for no song playing -->
                    <img
                        src="/images/default-song.png"
                        class="size-10 rounded-sm"
                        alt="A placeholder song cover featuring a white background with a blue note"
                    />
                    <div class="flex-1 ml-2 truncate">
                        <div class="text-white text-sm mb-1">
                            <p class="font-semibold truncate">
                                No song playing
                            </p>
                        </div>
                        <div class="text-xs text-zinc-300">
                            <p class="truncate">No song playing</p>
                        </div>
                    </div>
                </div>

                <div
                    class="flex flex-row items-center justify-center flex-none gap-2 self-center w-[40%]"
                >
                    <!-- Disabled buttons when no song is playing -->
                    <ChevronDoubleLeftIcon
                        class="size-6 cursor-not-allowed opacity-50"
                    />
                    <PlayCircleIcon
                        class="size-12 cursor-not-allowed opacity-50"
                    />
                    <ChevronDoubleRightIcon
                        class="size-6 cursor-not-allowed opacity-50"
                    />
                </div>
                <div class="relative w-[30%] justify-end flex">
                    <DeviceDropdown
                        :devices="devices"
                        :selected-device="selectedDevice"
                        :is-loading="isLoadingDevices"
                        :is-transfering-device="isTransferingDevice"
                        @refresh-devices="emit('refresh-devices')"
                        @update:selected-device="emit('select-device', $event)"
                    />
                </div>
            </div>
        </div>

        <div v-else-if="!currentTrack" class="text-center py-2">
            <p class="text-zinc-400">Nothing is currently playing..</p>
        </div>

        <!-- desktop playback with active queue -->
        <div v-else-if="isMixActive">
            <div class="flex-row items-center justify-around w-full flex">
                <div class="flex items-center gap-1 w-[30%]">
                    <img
                        v-if="currentTrack.album?.images?.length"
                        :src="currentTrack.album.images[0].url"
                        class="size-10 rounded-sm"
                        alt="The cover art of the current playing track"
                    />
                    <div class="flex-1 ml-2 truncate">
                        <div class="text-white text-sm mb-1">
                            <p class="font-semibold truncate">
                                {{ currentTrack.name }}
                            </p>
                        </div>
                        <div class="text-xs text-zinc-300">
                            <p class="truncate">
                                {{
                                    currentTrack.artists
                                        ?.map((a) => a.name)
                                        .join(", ")
                                }}
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="flex flex-row items-center justify-center flex-none gap-2 self-center w-[40%]"
                >
                    <ChevronDoubleLeftIcon
                        :class="[
                            devices.length === 0 || selectedDevice === null
                                ? 'opacity-50 !cursor-not-allowed'
                                : '',
                        ]"
                        @click="emit('previous-song')"
                        class="size-6 cursor-pointer"
                    />
                    <PauseCircleIcon
                        :class="[
                            devices.length === 0 || selectedDevice === null
                                ? 'opacity-50 !cursor-not-allowed'
                                : '',
                        ]"
                        @click="emit('pause-mix')"
                        v-if="isPlaying"
                        class="size-12 cursor-pointer"
                    />
                    <PlayCircleIcon
                        :class="[
                            devices.length === 0 || selectedDevice === null
                                ? 'opacity-50 !cursor-not-allowed'
                                : '',
                        ]"
                        @click="emit('resume-mix')"
                        v-if="!isPlaying"
                        class="size-12 cursor-pointer"
                    />
                    <ChevronDoubleRightIcon
                        :class="[
                            devices.length === 0 || selectedDevice === null
                                ? 'opacity-50 !cursor-not-allowed'
                                : '',
                        ]"
                        class="size-6 cursor-pointer"
                        @click="emit('skip-song')"
                    />
                </div>
                <div class="relative w-[30%] justify-end items-center flex">
                    <CoDJSelector
                        v-if="authorization.isOwner"
                        class="mr-3 mb-0.5"
                        @click="showCoDJSelectorDrawer = true"
                    />
                    <DeviceDropdown
                        :devices="devices"
                        :selected-device="selectedDevice"
                        :is-loading="isLoadingDevices"
                        :is-transfering-device="isTransferingDevice"
                        @refresh-devices="emit('refresh-devices')"
                        @update:selected-device="emit('select-device', $event)"
                    />
                </div>
            </div>
        </div>
    </div>
    <CoDJSelectorDrawer
        :is-visible="showCoDJSelectorDrawer"
        :mix="mix"
        @close-drawer="showCoDJSelectorDrawer = false"
    />
</template>

<script setup>
import { ChevronDoubleLeftIcon } from "@heroicons/vue/16/solid";
import { ChevronDoubleRightIcon } from "@heroicons/vue/16/solid";
import { PauseCircleIcon, PlayCircleIcon } from "@heroicons/vue/24/solid";
import ToggleSwitchReadValue from "@/components/forms/ToggleSwitchReadValue.vue";
import SpinningCircle from "@/components/spinners/SpinningCircle.vue";
import DeviceDropdown from "@/components/playback/device/DeviceDropdown.vue";
import CoDJSelector from "@/components/playback/co-dj/CoDJSelector.vue";
import CoDJSelectorDrawer from "@/components/playback/co-dj/CoDJSelectorDrawer.vue";
import { ref, computed } from "vue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();
const authorization = computed(() => page.props.mix.authorized);
const showCoDJSelectorDrawer = ref(false);

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
    queueActivationDisabled: {
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
    "skip-song",
]);
</script>
