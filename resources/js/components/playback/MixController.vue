<template>
    <MixWithPlaybackMobile
        :mix="mix"
        :isMixActive="isMixActive"
        :isPlaying="isPlaying"
        :currentTrack="currentTrack"
        :devices="devices"
        :selectedDevice="selectedDevice"
        :isLoading="isLoading"
        :isTransferingDevice="isTransferingDevice"
        :isLoadingDevices="isLoadingDevices"
        :isSyncingWithSpotify="isSyncingWithSpotify"
        :queueActivationDisabled="queueActivationDisabled"
        @pause-mix="pauseMix"
        @resume-mix="resumeMix"
        @refresh-devices="refreshDevices"
        @select-device="selectDevice"
        @toggle-mix-active="toggleMixActive"
        @next-song="skipSong"
        @previous-song="previousSong"
        v-if="windowSize < 1024 && authorization.canControlPlayback"
    />
    <MixWithPlayback
        :mix="mix"
        :isMixActive="isMixActive"
        :isPlaying="isPlaying"
        :currentTrack="currentTrack"
        :devices="devices"
        :selectedDevice="selectedDevice"
        :isLoading="isLoading"
        :isTransferingDevice="isTransferingDevice"
        :isLoadingDevices="isLoadingDevices"
        :isSyncingWithSpotify="isSyncingWithSpotify"
        :queueActivationDisabled="queueActivationDisabled"
        @pause-mix="pauseMix"
        @resume-mix="resumeMix"
        @refresh-devices="refreshDevices"
        @select-device="selectDevice"
        @toggle-mix-active="toggleMixActive"
        @previous-song="previousSong"
        @skip-song="skipSong"
        v-else-if="windowSize >= 1024 && authorization.canControlPlayback"
    />
    <MixWithoutPlayback
        :isLoading="isLoading"
        :isSyncingWithSpotify="isSyncingWithSpotify"
        :isMixActive="isMixActive"
        :currentTrack="currentTrack"
        :isPlaying="isPlaying"
        :queueActivationDisabled="queueActivationDisabled"
        v-else
    />
</template>

<script setup>
import MixWithoutPlayback from "./MixWithoutPlayback.vue";
import MixWithPlayback from "./MixWithPlayback.vue";
import MixWithPlaybackMobile from "./MixWithPlaybackMobile.vue";
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import toast from "@/stores/StoreToast.js";
import { useBattery } from "@vueuse/core";
import { StoreInformationModal } from "@/stores/StoreInformationModal";
import emitter from "@/eventBus.js";

const page = usePage();
const windowSize = ref(window.innerWidth);
const props = defineProps({
    mix: {
        type: Object,
        required: true,
    },
    // songs: {
    //     type: Object,
    //     required: true,
    // },
});
//state variables
const songs = computed(() => page.props.songs);
const user = page.props.user;
const isMixActive = ref(props.mix?.is_active || false);
const isLoading = ref(false);
const isTransferingDevice = ref(false);
const currentTrack = ref(null);
const isPlaying = ref(false);
const isSyncingWithSpotify = ref(false);
const queueCompleted = ref(false);
const isQueueComplicated = computed(
    () => queueCompleted.value && isMixActive.value
);
const showQueueCompletedModal = ref(false);

const lastEventTime = ref(0);
const lastToggleTime = ref(0);
let syncTimeoutId = null;

const devices = ref(page.props.devices || []);
const noDeviceSelectedError = ref(false);
const isLoadingDevices = ref(false);

const authorization = computed(() => page.props.mix.authorized);

//check if queue is completed. if so, show the modal
watch(
    () => isQueueComplicated.value,
    (newValue) => {
        if (newValue) {
            showQueueCompletedModal.value = true;
        } else {
            showQueueCompletedModal.value = false;
        }
    }
);

// Find initially active device from the devices array
const selectedDevice = ref(devices.value.find((d) => d.is_active) || null);

const hasActiveConflictingMixes = computed(() => {
    return (
        page.props.activeConflictingMixes &&
        page.props.activeConflictingMixes.length > 0
    );
});

const queueActivationDisabled = computed(() => {
    // Disable the button if there are conflicting mixes and the mix isn't active already
    return (
        (hasActiveConflictingMixes.value && !isMixActive.value) ||
        isLoading.value ||
        songs.value.length === 0
    );
});

watch(
    () => devices.value,
    (newDevices) => {
        if (newDevices.length === 0) {
            selectedDevice.value = null;
        }
    }
);

const { charging, level } = useBattery();
const storeInformationModal = StoreInformationModal();

//battery level reminder. sadly doesnt work on ios but on adroid it should most of the time depending on browser
watch(
    [charging, level, isMixActive],
    async ([isCharging, batteryLevel, isMixActive]) => {
        const hasSeenReminder =
            localStorage.getItem("coDjReminderShown") === "true";

        if (
            !isCharging &&
            batteryLevel === 0.1 &&
            !hasSeenReminder &&
            isMixActive
        ) {
            storeInformationModal.showInfoModal(
                "Battery almost empty",
                "Remember that you can assign another premium user as the co-dj for this mix while you charge your device!"
            );
            localStorage.setItem("coDjReminderShown", "true");
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
            refreshDevices();
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

        //auto-select the active device if one exists
        const activeDevice = devices.value.find((d) => d.is_active);
        if (activeDevice) {
            selectedDevice.value = activeDevice;
        }

        // if(!activeDevice) {
        //     selectedDevice.value = null;
        // }

        // else if (!selectedDevice.value && devices.value.length === 1) {
        //     // Auto-select the only device if no active device
        //     selectedDevice.value = devices.value[0];
        // }
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
        console.log(
            route("api.spotify.transfer-playback", { mix: props.mix.id })
        );
        const response = await axios.post(
            route("api.spotify.transfer-playback", { mix: props.mix.id }),
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
        if (queueActivationDisabled.value) {
            toast.add({
                message: `Only one personal mix queue can be active.`,
                type: "danger",
            });
            return;
        }
        if (!user.authorized.hasPremium) {
            toast.add({
                message: `You must have premium to activate the queue.`,
                type: "danger",
            });
            return;
        }
        await refreshDevices();
        //if no active device is selected, show toast.
        if (selectedDevice.value === null && !isMixActive.value) {
            toast.add({
                message: "You must select a device first to start the queue.",
                type: "danger",
            });
            return;
        }

        if (songs.value.data.length === 0 && !isMixActive.value) {
            toast.add({
                message: `You must add at least one song to the mix.`,
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
        emitter.emit("mix-toggled");
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
    window.addEventListener("resize", () => {
        windowSize.value = window.innerWidth;
    });
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
                } else if (e.reason === "queue_completed") {
                    // Handle queue completion while keeping mix active
                    clearSyncingState();
                    queueCompleted.value = true;
                    isPlaying.value = false;

                    // Show queue completed modal
                    showQueueCompletedModal.value = true;
                }

                router.reload({
                    only: ["your_mixes", "joined_mixes", "success", "danger"],
                });

                if (e.reason === "other_mix") {
                    router.reload({
                        only: ["activeConflictingMixes", "success", "danger"],
                    });
                }
            })
            .listen(".device.updated", async () => {
                await refreshDevices();
            })
            .listen(".user-access-updated", () => {
                router.reload({
                    only: ["collaborators", "success", "danger"],
                });
            });

        Echo.private("user." + page.props.user.id).listen(
            ".co-dj-updated",
            async () => {
                await refreshDevices();

                router.reload({ only: ["mix", "success", "danger"] });
            }
        );
    }
});

onUnmounted(() => {
    Echo.leave(`mix.${props.mix.id}`);
    Echo.leave(`user.${page.props.user.id}`);
    window.removeEventListener("resize", () => {
        windowSize.value = window.innerWidth;
    });
});
</script>
