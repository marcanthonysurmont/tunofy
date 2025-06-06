<template>
    <div
        class="fixed bottom-0 left-0 right-0 w-full z-40 bg-card-background/40 border-t-2 lg:border-2 backdrop-blur-xl border-card-stroke p-4 lg:fixed lg:bottom-5 lg:left-1/2 lg:-translate-x-1/2 lg:ml-[160px] lg:max-w-2xl lg:rounded-lg xl:max-w-3xl"
    >
        <!-- Loading states - Prioritize showing one at a time -->
        <div v-if="isLoading" class="text-center p-5 text-zinc-400">
            <div class="flex items-center justify-center">
                <SpinningCircle />
                <p>Updating state...</p>
            </div>
        </div>

        <!-- Loading state while we're syncing with Spotify -->
        <div
            v-else-if="isSyncingWithSpotify"
            class="text-center p-5 text-zinc-400"
        >
            <div class="flex items-center justify-center">
                <SpinningCircle />
                <p>Syncing with Spotify...</p>
            </div>
        </div>

        <!-- Desktop player with no active queue -->
        <div v-else-if="isMixActive === false">
            <div class="flex-row items-center w-full hidden md:flex">
                <div class="flex items-center flex-1 gap-1">
                    <img
                        src="/images/default-song.png"
                        class="size-12 rounded-sm"
                        alt="A placeholder song cover featuring a white background with a blue note"
                    />
                    <div class="flex-1 ml-2">
                        <div class="mb-1">
                            <p class="font-semibold text-sm">No song playing</p>
                        </div>
                        <div class="text-zinc-400 text-sm">
                            <p class="text-xs">No song playing</p>
                        </div>
                    </div>
                </div>
                <StatusIndicator
                    :is-playing="isPlaying"
                    :is-sync-disabled="!isMixActive"
                />
            </div>
            <div
                class="flex flex-row items-center w-full md:hidden gap-2 sm:gap-0"
            >
                <div class="flex items-center flex-1">
                    <img
                        src="/images/default-song.png"
                        class="size-12 rounded-sm"
                        alt="A placeholder song cover featuring a white background with a blue note"
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
                    <StatusIndicator
                        :is-playing="isPlaying"
                        :is-sync-disabled="!isMixActive"
                    />
                </div>
            </div>
        </div>

        <div v-else-if="!currentTrack" class="text-center py-2">
            <p class="text-zinc-400">Nothing is currently playing..</p>
        </div>

        <div v-else-if="isMixActive">
            <div class="flex-row items-center w-full hidden md:flex">
                <div class="flex items-center flex-1 gap-1">
                    <img
                        v-if="currentTrack.album?.images?.length"
                        :src="currentTrack.album.images[0].url"
                        class="size-14 rounded-sm"
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
                <div class="flex flex-row items-center gap-4">
                    <CoDJSelector
                        v-if="authorization.isOwner"
                        @click.stop="showCoDjSelector = true"
                    />
                    <StatusIndicator :is-playing="isPlaying" />
                </div>
            </div>

            <div
                class="flex flex-row items-center w-full md:hidden gap-2 sm:gap-0 justify-between"
            >
                <div class="flex items-center flex-1 max-w-[60%]">
                    <img
                        v-if="currentTrack.album?.images?.length"
                        :src="currentTrack.album.images[0].url"
                        class="size-12 rounded-sm"
                        alt="The cover art of the current playing track"
                    />
                    <div class="flex-1 ml-2 truncate">
                        <div class="mb-1">
                            <p class="font-semibold text-sm truncate">
                                {{ currentTrack.name }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-400 truncate">
                                {{
                                    currentTrack.artists
                                        ?.map((a) => a.name)
                                        .join(", ")
                                }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex flex-row gap-3 items-center max-w-[40%]">
                    <CoDJSelector
                        v-if="authorization.isOwner"
                        @click.stop="showCoDjSelector = true"
                    />
                    <StatusIndicator :is-playing="isPlaying" />
                </div>
            </div>
        </div>
    </div>
    <SelectCoDJModal
        v-if="!isLargeBreakPoint"
        :is-visible="showCoDjSelector"
        :mix="mix"
        @close-modal="showCoDjSelector = false"
    />

    <CoDJSelectorDrawer
        v-if="isLargeBreakPoint"
        :is-visible="showCoDjSelector"
        :mix="mix"
        @close-drawer="showCoDjSelector = false"
    />
</template>

<script setup>
import StatusIndicator from "@/components/playback/StatusIndicator.vue";
import SpinningCircle from "@/components/spinners/SpinningCircle.vue";
import CoDJSelector from "@/components/playback/co-dj/CoDJSelector.vue";
import { usePage } from "@inertiajs/vue3";
import { computed, ref, onMounted, onUnmounted } from "vue";
import SelectCoDJModal from "@/components/modals/co-dj/SelectCoDJModal.vue";
import CoDJSelectorDrawer from "@/components/playback/co-dj/CoDJSelectorDrawer.vue";

const page = usePage();
const mix = computed(() => page.props.mix);
const authorization = computed(() => page.props.mix.authorized);

const showCoDjSelector = ref(false);

defineProps({
    isLoading: Boolean,
    isSyncingWithSpotify: Boolean,
    isMixActive: [Boolean, Number],
    currentTrack: Object,
    isPlaying: Boolean,
});

const isLargeBreakPoint = ref(window.innerWidth >= 1024); // Updated to be true when screen is >= 1024

function updateScreenSize() {
    isLargeBreakPoint.value = window.innerWidth >= 1024; // Check if screen width is >= 1024
}

onMounted(() => {
    window.addEventListener("resize", updateScreenSize);
});

onUnmounted(() => {
    window.removeEventListener("resize", updateScreenSize);
});
</script>
