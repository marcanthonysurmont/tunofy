<template>
    <div
        class="fixed bottom-0 left-0 right-0 w-full z-40 bg-card-background/40 border-t-2 lg:border-2 backdrop-blur-xl border-card-stroke p-4 lg:fixed lg:bottom-5 lg:left-1/2 lg:-translate-x-1/2 lg:ml-[160px] lg:max-w-2xl lg:rounded-lg xl:max-w-3xl"
    >
        <!-- top part of the player -->
        <div
            v-if="props.mix.authorized.canControlPlayback"
            class="mb-4 flex flex-row justify-between items-center"
        >
            <ToggleSwitchReadValue
                :model-value="isMixActive"
                :disabled="isLoading"
                label="Spotify sync"
                @click="toggleMixActive"
            />

            <!-- Device dropdown -->
            <DeviceDropdown
                v-if="props.mix.authorized.canControlPlayback"
                :devices="devices"
                :selected-device="selectedDevice"
                :is-loading="isLoadingDevices"
                :is-transfering-device="isTransferingDevice"
                @refresh-devices="refreshDevices"
                @update:selected-device="selectDevice"
            />
        </div>

        <!-- Loading states - Prioritize showing one at a time -->
        <div v-if="isLoading" class="loading">
            <div class="flex items-center justify-center">
                <svg
                    class="animate-spin h-5 w-5 mr-2"
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
                <p>Updating state...</p>
            </div>
        </div>

        <!-- Loading state while we're syncing with Spotify -->
        <div v-else-if="isSyncingWithSpotify" class="loading">
            <div class="flex items-center justify-center">
                <svg
                    class="animate-spin h-5 w-5 mr-2"
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
                <p>Syncing with Spotify...</p>
            </div>
        </div>

        <!-- Add queue completion state -->
        <div v-else-if="queueCompleted && isMixActive" class="queue-completed">
            <div class="flex justify-center items-center mb-3">
                <div class="checkmark flex justify-center items-center">
                    <i class="fas fa-check"></i>
                </div>
            </div>
            <h3 class="completion-message">Queue Completed</h3>
            <p class="text-sm text-gray-500 mb-4">
                Add more songs to keep the mix going!
            </p>
            <button
                @click="resetQueue"
                class="px-4 py-2 bg-spotify-green text-white rounded-full text-sm hover:bg-opacity-80 transition"
            >
                Add Songs
            </button>
        </div>

        <!-- Desktop player with no active queue -->
        <div v-else-if="isMixActive === false">
            <div class="flex-row items-center w-full hidden md:flex">
                <div class="track-info flex items-center flex-1 gap-1">
                    <img
                        src="/images/default-song.png"
                        class="album-art"
                        alt="Album Art"
                    />
                    <div class="text-info ml-2">
                        <div class="track-name">
                            <p class="font-semibold">No song playing</p>
                        </div>
                        <div class="artist-name">
                            <p>No song playing</p>
                        </div>
                    </div>
                </div>

                <div
                    v-if="props.mix.authorized.canControlPlayback"
                    class="flex flex-row items-center justify-center flex-none gap-2"
                >
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
                <StatusIndicator
                    :is-playing="isPlaying"
                    :is-sync-disabled="!isMixActive"
                />
            </div>

            <!-- Mobile playback with no active queue -->
            <div
                class="flex flex-row items-center w-full md:hidden gap-2 sm:gap-0"
            >
                <div class="track-info flex items-center flex-1">
                    <img
                        src="/images/default-song.png"
                        class="album-art"
                        alt="Album Art"
                    />
                    <div class="text-info ml-2">
                        <div class="track-name">
                            <p class="font-semibold text-lg">No song playing</p>
                        </div>
                        <div class="artist-name">
                            <p class="text-sm text-zinc-400">No song playing</p>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col gap-3">
                    <div
                        v-if="props.mix.authorized.canControlPlayback"
                        class="flex flex-row items-center justify-center flex-none gap-2"
                    >
                        <ChevronDoubleLeftIcon class="size-6 opacity-50" />
                        <PlayCircleIcon class="size-12 opacity-50" />
                        <ChevronDoubleRightIcon class="size-6 opacity-50" />
                    </div>
                    <StatusIndicator
                        :is-playing="isPlaying"
                        :is-sync-disabled="!isMixActive"
                        v-else
                    />
                </div>
            </div>
        </div>

        <div v-else-if="!currentTrack" class="text-center py-2">
            <p class="text-zinc-400">Nothing is currently playing..</p>
        </div>

        <!-- desktop playback with active queue -->
        <div v-else-if="isMixActive">
            <div class="flex-row items-center w-full hidden md:flex">
                <div class="track-info flex items-center flex-1 gap-1">
                    <img
                        v-if="currentTrack.album?.images?.length"
                        :src="currentTrack.album.images[0].url"
                        class="album-art"
                        alt="Album Art"
                    />
                    <div class="text-info ml-2">
                        <div class="track-name">
                            <p class="font-semibold">
                                {{ currentTrack.name }}
                            </p>
                        </div>
                        <div class="artist-name">
                            <p>
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
                    v-if="props.mix.authorized.canControlPlayback"
                    class="flex flex-row items-center justify-center flex-none gap-2"
                >
                    <ChevronDoubleLeftIcon
                        :class="[
                            devices.length === 0 || selectedDevice === null
                                ? 'opacity-50 !cursor-not-allowed'
                                : '',
                        ]"
                        @click="previousSong"
                        class="size-6 cursor-pointer"
                    />
                    <PauseCircleIcon
                        :class="[
                            devices.length === 0 || selectedDevice === null
                                ? 'opacity-50 !cursor-not-allowed'
                                : '',
                        ]"
                        @click="pauseMix"
                        v-if="isPlaying"
                        class="size-12 cursor-pointer"
                    />
                    <PlayCircleIcon
                        :class="[
                            devices.length === 0 || selectedDevice === null
                                ? 'opacity-50 !cursor-not-allowed'
                                : '',
                        ]"
                        @click="resumeMix"
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
                        @click="skipSong"
                    />
                </div>

                <!-- status indicator for active queue -->
                <StatusIndicator :is-playing="isPlaying" />
            </div>

            <!-- mobile playback with active queue -->
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
                        <div class="track-name">
                            <p class="font-semibold text-lg">
                                {{ currentTrack.name }}
                            </p>
                        </div>
                        <div class="artist-name">
                            <p class="text-sm text-zinc-400">
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
                        v-if="props.mix.authorized.canControlPlayback"
                        class="flex flex-row items-center justify-center flex-none gap-2"
                    >
                        <ChevronDoubleLeftIcon
                            class="size-6 cursor-pointer"
                            @click="previousSong"
                        />
                        <PlayCircleIcon
                            @click="resumeMix"
                            v-if="!isPlaying"
                            class="size-12 cursor-pointer"
                        />
                        <PauseCircleIcon
                            @click="pauseMix"
                            v-if="isPlaying"
                            class="size-12 cursor-pointer"
                        />
                        <ChevronDoubleRightIcon
                            class="size-6 cursor-pointer"
                            @click="skipSong"
                        />
                    </div>
                    <StatusIndicator :is-playing="isPlaying" v-else />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from "vue";
import axios from "axios";
import { router, usePage } from "@inertiajs/vue3";
import { ChevronDoubleLeftIcon } from "@heroicons/vue/16/solid";
import { ChevronDoubleRightIcon } from "@heroicons/vue/16/solid";
import { PauseCircleIcon, PlayCircleIcon } from "@heroicons/vue/24/solid";
import ToggleSwitchReadValue from "@/components/forms/ToggleSwitchReadValue.vue";
import StatusIndicator from "@/components/playback/StatusIndicator.vue";
import DeviceDropdown from "./DeviceDropdown.vue";
import toast from "@/stores/StoreToast.js";

const page = usePage();

const props = defineProps({
    mix: {
        type: Object,
        required: true,
    },
});

// Current state variables
const isMixActive = ref(props.mix?.is_active || false);
const isLoading = ref(false);
const isTransferingDevice = ref(false);
const currentTrack = ref(null);
const isPlaying = ref(false);
const isSyncingWithSpotify = ref(false);
const queueCompleted = ref(false);

const lastEventTime = ref(0);
const lastToggleTime = ref(0);
let syncTimeoutId = null;

// Device selection variables
const devices = ref(page.props.devices || []);
const noDeviceSelectedError = ref(false);
const isLoadingDevices = ref(false);

const authorization = computed(() => page.props.mix.authorized);

watch(
    () => isPlaying.value,
    async (newValue) => {
        if (!newValue && authorization.value.canControlPlayback) {
            await refreshDevices();
            // if (selectedDevice.value === null) {
            //     noDeviceSelectedError.value = true;
            // } else {
            //     noDeviceSelectedError.value = false;
            // }
        }
    }
);

// Find initially active device from the devices array
const selectedDevice = ref(devices.value.find((d) => d.is_active) || null);

watch(
    () => devices.value,
    (newDevices) => {
        if (newDevices.length === 0) {
            selectedDevice.value = null;
        }
    }
);

function skipSong() {
    if (devices.value.length === 0 || selectedDevice.value === null) {
        return;
    }
    axios
        .post(`/api/spotify/skip-song/${props.mix.id}`)
        .then((response) => {
            // Check for queue completion explicitly
            if (response.data.queue_completed) {
                console.log("Queue completed after skipping last song");
                queueCompleted.value = true;
                clearSyncingState();
                return;
            }

            if (response.data.success && response.data.song) {
                // Transform the data to match the expected structure
                currentTrack.value = {
                    name: response.data.song.name,
                    album: {
                        images: [{ url: response.data.song.image_url }],
                    },
                    artists: [{ name: response.data.song.artist }],
                    // Add any other required properties
                    id: response.data.song.spotify_id,
                    duration_ms: response.data.song.duration_ms,
                };

                // Update playing state if available
                if (response.data.is_playing !== undefined) {
                    isPlaying.value = response.data.is_playing;
                }

                console.log("Song skipped successfully");
            }
        })
        .catch((error) => {
            console.error("Failed to skip song:", error);
            clearSyncingState(); // Always clear syncing state on error
        });
}

function previousSong() {
    if (devices.value.length === 0 || selectedDevice.value === null) {
        return;
    }
    axios
        .post(`/api/spotify/previous-song/${props.mix.id}`)
        .then((response) => {
            if (response.data.success) {
                console.log("Skipped to previous song successfully");
            }
        })
        .catch((error) => {
            console.error("Failed to skip to previous song:", error);
        });
}

function pauseMix() {
    if (devices.value.length === 0 || selectedDevice.value === null) {
        return;
    }
    axios
        .post(`/api/spotify/pause-mix/${props.mix.id}`)
        .then((response) => {
            if (response.data.success) {
                isPlaying.value = false;
            }
        })
        .catch((error) => {
            console.error("Failed to pause playback:", error);
        });
}

async function resumeMix() {
    if (devices.value.length === 0 || selectedDevice.value === null) {
        return;
    }
    try {
        const payload = {};

        // Add device_id to payload if we have a selected device
        if (devices.value.length === 0) {
            noDeviceSelectedError.value = true;
            return;
        } else {
            noDeviceSelectedError.value = false;
        }

        if (selectedDevice.value) {
            payload.device_id = selectedDevice.value.id;
        }

        const response = await axios.post(
            `/api/spotify/resume-mix/${props.mix.id}`,
            payload
        );

        if (response.data.success) {
            isPlaying.value = true;
        }
    } catch (error) {
        console.error("Failed to resume playback:", error);

        // If error suggests no device, refresh devices and show dropdown
        if (error.response?.status === 500) {
            await refreshDevices();
            if (devices.value.length > 0) {
                noDeviceSelectedError.value = true;
            }
        }
    }
}

// Device selection functions
async function refreshDevices() {
    try {
        isLoadingDevices.value = true;
        const response = await axios.get("/api/spotify/devices");
        devices.value = response.data.devices || [];
        console.log("Devices refreshed:", devices.value);

        // Auto-select the active device if one exists
        const activeDevice = devices.value.find((d) => d.is_active);
        if (activeDevice) {
            selectedDevice.value = activeDevice;
        } else if (!selectedDevice.value && devices.value.length === 1) {
            // Auto-select the only device if no active device
            selectedDevice.value = devices.value[0];
        }
    } catch (error) {
        console.error("Error fetching Spotify devices:", error);
    } finally {
        isLoadingDevices.value = false;
    }
}

function selectDevice(device) {
    // Set selected device
    selectedDevice.value = device;

    // If mix is active, transfer playback
    if (isMixActive.value && isPlaying.value) {
        transferPlayback(device.id);
    }
}

async function transferPlayback(deviceId) {
    try {
        // Only proceed if the mix is active and a different device is selected
        if (!isMixActive.value) {
            console.log("Mix is not active, skipping device transfer");
            return;
        }

        // Check if we're selecting a different device than the current one
        const currentDevice = devices.value.find((d) => d.is_active);
        if (currentDevice && currentDevice.id === deviceId) {
            console.log("Same device selected, skipping transfer");
            return;
        }

        console.log(`Transferring playback to device: ${deviceId}`);

        // Show loading state
        // isLoadingDevices.value = true;
        isTransferingDevice.value = true;

        // Call the API to transfer playback
        const response = await axios.post(
            `/api/spotify/transfer-playback/${props.mix.id}`,
            { deviceId }
        );

        if (response.data.success) {
            console.log("Playback transferred successfully");

            // Update the selected device in the UI
            selectedDevice.value = devices.value.find((d) => d.id === deviceId);

            // Update devices list to reflect the active state
            await refreshDevices();
        }
    } catch (error) {
        console.error("Failed to transfer playback:", error);
    } finally {
        // isLoadingDevices.value = false;
        isTransferingDevice.value = false;
    }
}

function clearSyncingState() {
    isSyncingWithSpotify.value = false;
    if (syncTimeoutId) {
        clearTimeout(syncTimeoutId);
        syncTimeoutId = null;
    }
}

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

// Update toggleMixActive to use selected device
async function toggleMixActive() {
    try {
        //if no active device is selected, show toast.
        if (selectedDevice.value === null && !isMixActive.value) {
            toast.add({
                message: "You must select a device first to start the sync.",
                type: "danger",
            });
            return;
        }

        // Prevent rapid toggling
        if (Date.now() - lastToggleTime.value < 1000) return;
        lastToggleTime.value = Date.now();

        // Set loading state
        isLoading.value = true;

        // Optimistically update UI
        const targetActive = !isMixActive.value;

        // Always clear queue completed state when toggling, especially when deactivating
        if (!targetActive || queueCompleted.value) {
            queueCompleted.value = false;
        }

        // Prepare the device ID to send
        const deviceIdToUse = selectedDevice.value
            ? selectedDevice.value.id
            : null;
        console.log("Using device ID for toggle:", deviceIdToUse);

        // Make API call with device ID
        const response = await axios.post(
            `/api/spotify/set-mix-active/${props.mix.id}`,
            {
                active: targetActive,
                deviceId: deviceIdToUse,
                reset_queue: true,
            }
        );

        // Handle response as usual
        console.log("Toggle response:", response.data);

        if (response.data.activation && response.data.activation.success) {
            // IMPORTANT: Update the mix active state with the target value
            isMixActive.value = targetActive;

            if (targetActive) {
                // Mix was activated
                setSyncingState();
                // Clear queue completed flag when activating
                queueCompleted.value = false;
            } else {
                // Mix was deactivated
                clearSyncingState();
                // Also clear the current track
                currentTrack.value = null;
                isPlaying.value = false;
            }
        }
    } catch (error) {
        console.error("Error toggling mix state:", error);
    } finally {
        isLoading.value = false;
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

function updatePlayerState(playbackData) {
    console.log("Updating player state with:", playbackData);

    if (!playbackData) return;

    // Update playing state if specified
    if (playbackData.is_playing !== undefined) {
        isPlaying.value = playbackData.is_playing;
    }

    // Only update track info if we have actual track data with an ID
    if (playbackData.item && playbackData.item.id) {
        currentTrack.value = playbackData.item;
        clearSyncingState();
        console.log("Updated UI with real track data");
    }
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
                console.log("Received playback data event:", e);

                // Check for real track data and handle it specially
                if (e.playback_data?.item?.id) {
                    console.log(
                        "Real track data received via WebSocket",
                        e.playback_data
                    );

                    // Always update with real track data regardless of timestamp
                    currentTrack.value = e.playback_data.item;
                    isPlaying.value = e.playback_data.is_playing === true;
                    isMixActive.value = true;
                    clearSyncingState();
                    return;
                }

                // Handle initial activation placeholder data
                if (e.playback_data?.is_initial_activation) {
                    console.log("Initial activation data received");
                    isMixActive.value = true;
                    isPlaying.value = true;
                    // Don't clear syncing yet - wait for real track data
                }

                // Process normal events with timestamp check
                const eventTime = e.timestamp || Date.now();
                if (eventTime < lastEventTime.value) {
                    console.log("Ignoring out-of-sequence event");
                    return;
                }
                lastEventTime.value = eventTime;

                // Update player state for other events
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
                } else if (e.reason === "queue_completed") {
                    // Handle active but queue completed
                    clearSyncingState(); // Make sure this is called
                    queueCompleted.value = true;
                    console.log("Queue completed but mix remains active");
                } else {
                    setSyncingState();
                    queueCompleted.value = false;
                }
            });

        Echo.private("user." + page.props.user.id).listen(
            ".co-dj-updated",
            async () => {
                await refreshDevices();

                router.reload({ only: ["mix"] });
            }
        );
    }
});

onUnmounted(() => {
    Echo.leave(`mix.${props.mix.id}`);
    Echo.leave(`user.${page.props.user.id}`);
});
</script>

<style scoped>
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
    /* Better rendering */
    image-rendering: -webkit-optimize-contrast;
    image-rendering: auto;
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
    color: #1db954;
    /* Spotify green */
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
    background-color: #1db954;
    /* Spotify green */
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
