<template>
    <div
        class="fixed bottom-0 left-0 right-0 w-full z-50 bg-card-background border-t-2 border-card-stroke p-4 md:sticky md:bottom-6 md:w-full md:border-2 md:rounded-lg md:max-w-2xl lg:max-w-3xl md:mx-auto"
    >
        <div v-if="props.mix.authorized.isOwner" class="text-center mb-4">
            <ToggleSwitchReadValue
                :model-value="isMixActive"
                :disabled="isLoading"
                :label="
                    isMixActive
                        ? 'Spotify sync enabled'
                        : 'Spotify sync disabled'
                "
                @click="toggleMixActive"
            />
        </div>
        <!-- Loading states - Prioritize showing one at a time -->
        <div v-if="isLoading" class="loading">Updating state...</div>

        <!-- Loading state while we're syncing with Spotify -->
        <div v-else-if="isSyncingWithSpotify" class="loading">
            <div class="flex items-center justify-center">
                <svg
                    class="animate-spin h-5 w-5 mr-2 text-spotify-green"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                </svg>
                Syncing with Spotify...
            </div>
        </div>

        <!-- Add queue completion state - explicit Boolean check -->
        <div v-else-if="queueCompleted === true" class="queue-completed">
            <div class="checkmark">✓</div>
            <div class="completion-message">
                Queue completed! All songs have been played.
            </div>
        </div>

        <!-- Content based on active state - explicit Boolean check -->
        <div v-else-if="isMixActive === false" class="not-active">
            Playback polling is inactive for this mix
            <div v-if="props.mix.authorized.isOwner" class="mix-controls">
                <RegularButton
                    color="secondary"
                    @click="toggleMixActive"
                    :loading="isLoading"
                >
                    {{
                        isMixActive ? "Deactivate Polling" : "Activate Polling"
                    }}
                </RegularButton>
            </div>
        </div>

        <div v-else-if="!currentTrack" class="text-center py-2">
            <p class="text-zinc-400">Nothing is currently playing..</p>
        </div>

        <!-- desktop playback -->
        <div v-else>
            <div class="flex-row items-center w-full hidden md:flex">
                <div class="track-info flex items-center flex-1 gap-1">
                    <img
                        v-if="currentTrack.album?.images?.length"
                        :src="currentTrack.album.images[0].url"
                        class="album-art"
                        alt="Album Art"
                    />
                    <div class="text-info ml-2">
                        <div class="track-name">{{ currentTrack.name }}</div>
                        <div class="artist-name">
                            {{
                                currentTrack.artists
                                    ?.map((a) => a.name)
                                    .join(", ")
                            }}
                        </div>
                    </div>
                </div>

                <div
                    v-if="props.mix.authorized.isOwner"
                    class="flex flex-row items-center justify-center flex-none gap-2"
                >
                    <ChevronDoubleLeftIcon
                        class="size-6 text-spotify-green cursor-pointer"
                    />
                    <PauseCircleIcon
                        v-if="isPlaying"
                        class="size-12 cursor-pointer"
                    />
                    <PlayCircleIcon
                        v-if="!isPlaying"
                        class="size-12 text-spotify-green cursor-pointer"
                    />
                    <ChevronDoubleRightIcon
                        class="size-6 text-spotify-green cursor-pointer"
                    />
                </div>

                <div class="text-right flex-1 flex justify-end items-center">
                    <span
                        class="px-3 py-2 rounded text-xs font-semibold flex items-center gap-x-2"
                        :class="{
                            'bg-green-900 text-green-400': isPlaying,
                            'bg-zinc-950 text-zinc-400': !isPlaying,
                        }"
                    >
                        <div
                            class="size-2 rounded-full"
                            :class="{
                                'bg-green-400 ': isPlaying,
                                'bg-zinc-400 ': !isPlaying,
                            }"
                        ></div>
                        {{ isPlaying ? "Now Playing" : "Paused" }}
                    </span>
                </div>
            </div>

            <!-- mobile playback -->
            <div
                class="flex flex-row items-center w-full md:hidden gap-2 sm:gap-0"
            >
                <div class="track-info flex items-center flex-1">
                    <img
                        v-if="currentTrack.album?.images?.length"
                        :src="currentTrack.album.images[0].url"
                        class="album-art"
                        alt="Album Art"
                    />
                    <div class="text-info ml-2">
                        <div class="track-name">{{ currentTrack.name }}</div>
                        <div class="artist-name">
                            {{
                                currentTrack.artists
                                    ?.map((a) => a.name)
                                    .join(", ")
                            }}
                        </div>
                    </div>
                </div>
                <div class="flex flex-col gap-3">
                    <div
                        v-if="props.mix.authorized.isOwner"
                        class="flex flex-row items-center justify-center flex-none gap-2"
                    >
                        <ChevronDoubleLeftIcon class="size-6 cursor-pointer" />
                        <PlayCircleIcon
                            v-if="!isPlaying"
                            class="size-12 cursor-pointer"
                        />
                        <PauseCircleIcon
                            v-if="isPlaying"
                            class="size-12 cursor-pointer"
                        />
                        <ChevronDoubleRightIcon class="size-6 cursor-pointer" />
                    </div>
                    <div v-else>
                        <span
                            class="px-3 py-2 rounded text-xs font-semibold flex items-center gap-x-2"
                            :class="{
                                'bg-green-900 text-green-400': isPlaying,
                                'bg-zinc-950 text-zinc-400': !isPlaying,
                            }"
                        >
                            <div
                                class="size-2 rounded-full"
                                :class="{
                                    'bg-green-400 ': isPlaying,
                                    'bg-zinc-400 ': !isPlaying,
                                }"
                            ></div>
                            {{ isPlaying ? "Now Playing" : "Paused" }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from "vue";
import axios from "axios";
import { ChevronDoubleLeftIcon } from "@heroicons/vue/16/solid";
import { ChevronDoubleRightIcon } from "@heroicons/vue/16/solid";
import { PauseCircleIcon, PlayCircleIcon } from "@heroicons/vue/24/solid";
import RegularButton from "@/components/buttons/RegularButton.vue";
import ToggleSwitchReadValue from "@/components/forms/ToggleSwitchReadValue.vue";

const props = defineProps({
    mix: {
        type: Object,
        required: true,
    },
});

//state tracking variables + regular variables
const isMixActive = ref(props.mix?.is_active || false);
const isLoading = ref(false);
const currentTrack = ref(null);
const isPlaying = ref(false);
const isSyncingWithSpotify = ref(false);
const queueCompleted = ref(false);

const lastEventTime = ref(0);
const lastToggleTime = ref(0);
let syncTimeoutId = null;

//debug panel toggle (can be removed in production)
const showDebug = ref(false);

// Toggle mix active state
async function toggleMixActive() {
    try {
        console.log("Toggling mix active state");
        // Prevent rapid toggling
        if (Date.now() - lastToggleTime.value < 1000) return;
        lastToggleTime.value = Date.now();

        // Set loading state
        isLoading.value = true;

        // Optimistically update UI
        const targetActive = !isMixActive.value;
        // isMixActive.value = targetActive;

        // Update UI immediately
        if (targetActive) {
            setSyncingState();
            currentTrack.value = null;
            isPlaying.value = false;
            queueCompleted.value = false;
        } else {
            clearSyncingState();
            currentTrack.value = null;
            isPlaying.value = false;
        }

        // Make API call
        const response = await axios.post("/api/spotify/set-mix-active", {
            mix_id: props.mix.id,
            active: targetActive,
            reset_queue: targetActive,
        });

        // Confirm server status
        // isMixActive.value = response.data.activation.is_active;
        // console.log(response.data);
        // console.log("undefined here?", isMixActive.value);

        // If activating, handle initial data
        if (isMixActive.value && response.data.playback_data) {
            updatePlayerState(response.data.playback_data);
        }
    } catch (err) {
        console.error("Error toggling mix active status:", err);
        clearSyncingState();
    } finally {
        setTimeout(() => (isLoading.value = false), 500);
    }
}

//refresh mix state from server
async function refreshMixState() {
    try {
        const response = await axios.get("/api/spotify/request-status", {
            params: { mix_id: props.mix.id },
        });

        // Update state from server
        isMixActive.value = response.data.is_active;

        // Handle playback data
        if (response.data.playback_data) {
            updatePlayerState(response.data.playback_data);
        } else if (isMixActive.value) {
            setSyncingState();
        } else {
            clearSyncingState();
            currentTrack.value = null;
            isPlaying.value = false;
        }
    } catch (err) {
        console.error("Error refreshing mix state:", err);
    }
}

//syncing state management
function clearSyncingState() {
    isSyncingWithSpotify.value = false;
    if (syncTimeoutId) {
        clearTimeout(syncTimeoutId);
        syncTimeoutId = null;
    }
}

function setSyncingState() {
    isSyncingWithSpotify.value = true;
    if (syncTimeoutId) clearTimeout(syncTimeoutId);
    // Auto-clear after 10s to prevent getting stuck
    syncTimeoutId = setTimeout(
        () => (isSyncingWithSpotify.value = false),
        10000
    );
}

//update player with playback data
function updatePlayerState(playbackData) {
    if (!playbackData) return;

    isPlaying.value = playbackData.is_playing === true;
    currentTrack.value = playbackData.item;

    // Clear syncing state if we have track data
    if (currentTrack.value) clearSyncingState();
}

onMounted(() => {
    if (props.mix) {
        // Initialize state
        isMixActive.value = !!props.mix.is_active;
        if (isMixActive.value) setSyncingState();

        // Initial data load
        refreshMixState();

        //setup WebSocket listeners and listen for events
        Echo.channel(`mix.${props.mix.id}`)
            .listen(".playback-data", (e) => {
                //ignore out-of-sequence events
                const eventTime = e.timestamp || Date.now();
                if (eventTime < lastEventTime.value) return;
                lastEventTime.value = eventTime;

                if (
                    !isMixActive.value &&
                    e.playback_data?.item &&
                    e.playback_data.is_playing
                ) {
                    // console.log("Mix is active, updating state");
                    isMixActive.value = true;
                }

                //update player
                updatePlayerState(e.playback_data);
            })
            .listen(".mix-status-changed", (e) => {
                //ignore out-of-sequence events
                const eventTime = e.timestamp || Date.now();
                if (eventTime < lastEventTime.value) return;
                lastEventTime.value = eventTime;

                //update active state
                isMixActive.value = e.isActive;

                //handle state changes
                if (!e.isActive) {
                    clearSyncingState();
                    currentTrack.value = null;
                    isPlaying.value = false;
                    if (e.reason === "queue_completed")
                        queueCompleted.value = true;
                } else {
                    setSyncingState();
                    queueCompleted.value = false;
                }
            });
    }
});

//clean up
onUnmounted(() => {
    if (syncTimeoutId) clearTimeout(syncTimeoutId);
    if (props.mix) Echo.leave(`mix.${props.mix.id}`);
});
</script>

<style scoped>
/* .playback-container {
    background-color: #222;
    border-radius: 8px;
    padding: 16px;
    color: white;
    max-width: 500px;
    margin: 0 auto;
} */

.loading,
.error,
.not-playing,
.not-active {
    text-align: center;
    padding: 20px;
    color: #aaa;
}

.queue-completed {
    text-align: center;
    padding: 24px 16px;
    animation: fadeIn 0.5s ease-out;
}

.checkmark {
    color: #1db954;
    font-size: 32px;
    margin-bottom: 12px;
    background-color: rgba(29, 185, 84, 0.1);
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
}

.completion-message {
    font-size: 1.1rem;
    color: #fff;
    margin-bottom: 8px;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.track-info {
    display: flex;
    align-items: center;
}

.album-art {
    width: 60px;
    height: 60px;
    border-radius: 4px;
    margin-right: 0px;
}

.text-info {
    flex: 1;
}

.track-name {
    font-weight: bold;
    font-size: 1.1rem;
    margin-bottom: 4px;
}

.artist-name {
    color: #aaa;
    font-size: 0.9rem;
}

.play-status {
    text-align: center;
    font-size: 0.8rem;
    color: #aaa;
    background-color: rgba(0, 0, 0, 0.2);
    padding: 4px 8px;
    border-radius: 12px;
    display: inline-block;
    margin-top: 8px;
}

.play-status.is-playing {
    color: #1db954; /* Spotify green */
}

.mix-controls {
    text-align: center;
}

.control-button {
    background-color: #333;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 8px 16px;
    font-size: 0.9rem;
    cursor: pointer;
}

.control-button.active {
    background-color: #1db954; /* Spotify green */
}

.control-button.inactive {
    background-color: #444;
}

.control-button:hover:not(:disabled) {
    opacity: 0.9;
}

.control-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
