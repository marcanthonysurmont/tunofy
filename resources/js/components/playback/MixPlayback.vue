<template>
    <div
        class="fixed bottom-0 left-0 right-0 w-full z-[999] bg-card-background/40 border-t-2 lg:border-2 backdrop-blur-xl border-card-stroke p-4 lg:fixed lg:bottom-5 lg:left-1/2 lg:-translate-x-1/2 lg:ml-[160px] lg:max-w-2xl lg:rounded-lg xl:max-w-3xl"
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

        <!-- Add queue completion state - explicit Boolean check -->
        <div v-else-if="queueCompleted === true" class="queue-completed">
            <div class="checkmark">✓</div>
            <div class="completion-message">
                <p>Queue completed! All songs have been played.</p>
            </div>
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

                <!-- Device selector RIGHT ABOVE StatusIndicator -->
                <div v-if="props.mix.authorized.canControlPlayback" class="mr-4 relative" ref="deviceDropdownRef">
                    <button 
                        @click="toggleDeviceDropdown($event)" 
                        type="button" 
                        class="flex items-center gap-x-1 text-sm font-semibold leading-6 text-zinc-200 bg-card-background/80 px-3 py-1.5 rounded-md border border-zinc-700"
                    >
                        <div class="flex items-center">
                            <span>{{ selectedDevice ? selectedDevice.name : 'Select device' }}</span>
                            <ComputerDesktopIcon v-if="selectedDevice" class="ml-2 h-4 w-4 text-zinc-400" />
                        </div>
                        <ChevronDownIcon class="h-5 w-5 text-zinc-400" aria-hidden="true" />
                    </button>

                    <div 
                        v-if="isDeviceDropdownOpen" 
                        class="absolute bottom-full mb-2 right-0 z-10 w-56 origin-bottom-right rounded-md bg-card-background shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                        role="menu"
                    >
                        <div v-if="isLoadingDevices" class="p-4 text-center">
                            <svg class="animate-spin h-5 w-5 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="mt-2 text-sm text-zinc-400">Loading devices...</p>
                        </div>
                        
                        <div v-else-if="devices.length === 0" class="p-4 text-center">
                            <p class="text-sm text-zinc-400">No devices found</p>
                            <p class="text-xs text-zinc-500 mt-1">Make sure Spotify is open on at least one device</p>
                        </div>
                        
                        <div v-else class="py-1" role="none">
                            <button
                                v-for="device in devices" 
                                :key="device.id"
                                @click="selectDevice(device)"
                                class="w-full text-left px-4 py-2 text-sm text-zinc-200 hover:bg-zinc-700 hover:text-white flex items-center justify-between"
                                :class="{'bg-zinc-800': selectedDevice && selectedDevice.id === device.id}"
                                role="menuitem"
                            >
                                <span>{{ device.name }}</span>
                                <CheckIcon v-if="selectedDevice && selectedDevice.id === device.id" class="h-4 w-4 text-primary" />
                            </button>
                        </div>
                        
                        <div class="border-t border-zinc-700 py-1">
                            <button
                                @click="refreshDevices"
                                class="w-full text-left px-4 py-2 text-sm text-zinc-200 hover:bg-zinc-700 hover:text-white flex items-center"
                                role="menuitem"
                            >
                                <ArrowPathIcon class="mr-2 h-4 w-4" />
                                Refresh devices
                            </button>
                        </div>
                    </div>
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
                    
                    <!-- Device selector for mobile above status indicator -->
                    <div v-if="props.mix.authorized.canControlPlayback" class="mb-2 relative" ref="deviceDropdownRefMobile">
                        <button 
                            @click="toggleDeviceDropdown($event)" 
                            type="button" 
                            class="flex items-center gap-x-1 text-sm font-semibold leading-6 text-zinc-200 bg-card-background/80 px-3 py-1.5 rounded-md border border-zinc-700"
                        >
                            <span class="truncate max-w-[100px]">{{ selectedDevice ? selectedDevice.name : 'Select device' }}</span>
                            <ChevronDownIcon class="h-5 w-5 text-zinc-400" aria-hidden="true" />
                        </button>
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
                        @click="previousSong"
                        class="size-6 cursor-pointer"
                    />
                    <PauseCircleIcon
                        @click="pauseMix"
                        v-if="isPlaying"
                        class="size-12 cursor-pointer"
                    />
                    <PlayCircleIcon
                        @click="resumeMix"
                        v-if="!isPlaying"
                        class="size-12 cursor-pointer"
                    />
                    <ChevronDoubleRightIcon
                        class="size-6 cursor-pointer"
                        @click="skipSong"
                    />
                </div>

                <!-- Device selector above status indicator for active queue -->
                <div v-if="props.mix.authorized.canControlPlayback" class="mr-4 relative" ref="deviceDropdownRefActive">
                    <button 
                        @click="toggleDeviceDropdown($event)" 
                        type="button" 
                        class="flex items-center gap-x-1 text-sm font-semibold leading-6 text-zinc-200 bg-card-background/80 px-3 py-1.5 rounded-md border border-zinc-700"
                    >
                        <div class="flex items-center">
                            <span>{{ selectedDevice ? selectedDevice.name : 'Select device' }}</span>
                            <ComputerDesktopIcon v-if="selectedDevice" class="ml-2 h-4 w-4 text-zinc-400" />
                        </div>
                        <ChevronDownIcon class="h-5 w-5 text-zinc-400" aria-hidden="true" />
                    </button>
                    
                    <!-- This is the dropdown menu that was missing -->
                    <div 
                        v-if="isDeviceDropdownOpen" 
                        class="absolute bottom-full mb-2 right-0 z-10 w-56 origin-bottom-right rounded-md bg-card-background shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                        role="menu"
                    >
                        <div v-if="isLoadingDevices" class="p-4 text-center">
                            <svg class="animate-spin h-5 w-5 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="mt-2 text-sm text-zinc-400">Loading devices...</p>
                        </div>
                        
                        <div v-else-if="devices.length === 0" class="p-4 text-center">
                            <p class="text-sm text-zinc-400">No devices found</p>
                            <p class="text-xs text-zinc-500 mt-1">Make sure Spotify is open on at least one device</p>
                        </div>
                        
                        <div v-else class="py-1" role="none">
                            <button
                                v-for="device in devices" 
                                :key="device.id"
                                @click="selectDevice(device)"
                                class="w-full text-left px-4 py-2 text-sm text-zinc-200 hover:bg-zinc-700 hover:text-white flex items-center justify-between"
                                :class="{'bg-zinc-800': selectedDevice && selectedDevice.id === device.id}"
                                role="menuitem"
                            >
                                <span>{{ device.name }}</span>
                                <CheckIcon v-if="selectedDevice && selectedDevice.id === device.id" class="h-4 w-4 text-primary" />
                            </button>
                        </div>
                        
                        <div class="border-t border-zinc-700 py-1">
                            <button
                                @click="refreshDevices"
                                class="w-full text-left px-4 py-2 text-sm text-zinc-200 hover:bg-zinc-700 hover:text-white flex items-center"
                                role="menuitem"
                            >
                                <ArrowPathIcon class="mr-2 h-4 w-4" />
                                Refresh devices
                            </button>
                        </div>
                    </div>
                </div>

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
import { usePage } from '@inertiajs/vue3'; // Import usePage
import { ChevronDoubleLeftIcon } from "@heroicons/vue/16/solid";
import { ChevronDoubleRightIcon } from "@heroicons/vue/16/solid";
import { 
    PauseCircleIcon, 
    PlayCircleIcon, 
    ComputerDesktopIcon, 
    CheckIcon,
    ChevronDownIcon,
    ArrowPathIcon 
} from "@heroicons/vue/24/solid";
import ToggleSwitchReadValue from "@/components/forms/ToggleSwitchReadValue.vue";
import StatusIndicator from "@/components/playback/StatusIndicator.vue";

const page = usePage(); // Access the page object

const props = defineProps({
    mix: {
        type: Object,
        required: true,
    }
});

// Current state variables
const isMixActive = ref(props.mix?.is_active || false);
const isLoading = ref(false);
const currentTrack = ref(null);
const isPlaying = ref(false);
const isSyncingWithSpotify = ref(false);
const queueCompleted = ref(false);

const lastEventTime = ref(0);
const lastToggleTime = ref(0);
let syncTimeoutId = null;

// Device selection variables
const devices = ref(page.props.devices || []);
const isDeviceDropdownOpen = ref(false);
const isLoadingDevices = ref(false);
const deviceDropdownRef = ref(null);
const deviceDropdownRefActive = ref(null);
const deviceDropdownRefMobile = ref(null);

// Find initially active device from the devices array
const selectedDevice = ref(devices.value.find(d => d.is_active) || null);

// Handle click outside dropdown
function handleClickOutside(event) {
    // First check if we have any active dropdown
    if (!isDeviceDropdownOpen.value) return;
    
    // Check if the click was outside all possible dropdowns
    const isOutsideAllDropdowns = 
        (!deviceDropdownRef.value || !deviceDropdownRef.value.contains(event.target)) &&
        (!deviceDropdownRefActive.value || !deviceDropdownRefActive.value.contains(event.target)) &&
        (!deviceDropdownRefMobile.value || !deviceDropdownRefMobile.value.contains(event.target));
    
    // If clicked outside any dropdown, close it
    if (isOutsideAllDropdowns) {
        isDeviceDropdownOpen.value = false;
    }
}

function toggleDeviceDropdown(event) {
    // Stop propagation to prevent immediate closing
    event.stopPropagation();
    isDeviceDropdownOpen.value = !isDeviceDropdownOpen.value;
}

function skipSong() {
    axios.post(`/api/spotify/skip-song/${props.mix.id}`)
        .then(response => {
            if (response.data.success && response.data.song) {
                // Transform the data to match the expected structure
                currentTrack.value = {
                    name: response.data.song.name,
                    album: {
                        images: [{ url: response.data.song.image_url }]
                    },
                    artists: [{ name: response.data.song.artist }],
                    // Add any other required properties
                    id: response.data.song.spotify_id,
                    duration_ms: response.data.song.duration_ms
                };
                
                // Update playing state if available
                if (response.data.is_playing !== undefined) {
                    isPlaying.value = response.data.is_playing;
                }
                
                console.log('Song skipped successfully');
            }
        })
        .catch((error) => {
            console.error("Failed to skip song:", error);
        });
}

function previousSong() {
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

function resumeMix() {
    axios
        .post(`/api/spotify/resume-mix/${props.mix.id}`)
        .then((response) => {
            if (response.data.success) {
                isPlaying.value = true;
            }
        })
        .catch((error) => {
            console.error("Failed to resume playback:", error);
        });
}

// Device selection functions
async function refreshDevices() {
    try {
        isLoadingDevices.value = true;
        const response = await axios.get('/api/spotify/devices');
        devices.value = response.data.devices || [];
        
        // Auto-select the active device if one exists
        const activeDevice = devices.value.find(d => d.is_active);
        if (activeDevice) {
            selectedDevice.value = activeDevice;
        } else if (!selectedDevice.value && devices.value.length === 1) {
            // Auto-select the only device if no active device
            selectedDevice.value = devices.value[0];
        }
    } catch (error) {
        console.error('Error fetching Spotify devices:', error);
    } finally {
        isLoadingDevices.value = false;
    }
}

function selectDevice(device) {
    // Close dropdown
    isDeviceDropdownOpen.value = false;
    
    // Set selected device
    selectedDevice.value = device;
    
    // If mix is active, transfer playback
    if (isMixActive.value) {
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
        const currentDevice = devices.value.find(d => d.is_active);
        if (currentDevice && currentDevice.id === deviceId) {
            console.log("Same device selected, skipping transfer");
            return;
        }
        
        console.log(`Transferring playback to device: ${deviceId}`);
        
        // Show loading state
        isLoadingDevices.value = true;
        
        // Call the API to transfer playback
        const response = await axios.post(
            `/api/spotify/transfer-playback/${props.mix.id}`,
            { deviceId }
        );
        
        if (response.data.success) {
            console.log("Playback transferred successfully");
            
            // Update the selected device in the UI
            selectedDevice.value = devices.value.find(d => d.id === deviceId);
            
            // Update devices list to reflect the active state
            await refreshDevices();
        }
    } catch (error) {
        console.error("Failed to transfer playback:", error);
    } finally {
        isLoadingDevices.value = false;
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
        // Prevent rapid toggling
        if (Date.now() - lastToggleTime.value < 1000) return;
        lastToggleTime.value = Date.now();
        
        // Set loading state
        isLoading.value = true;
        
        // Optimistically update UI
        const targetActive = !isMixActive.value;
        
        // Prepare the device ID to send
        const deviceIdToUse = selectedDevice.value ? selectedDevice.value.id : null;
        console.log("Using device ID for toggle:", deviceIdToUse);
        
        // Make API call with device ID
        const response = await axios.post(
            `/api/spotify/set-mix-active/${props.mix.id}`,
            {
                active: targetActive,
                deviceId: deviceIdToUse, // Explicitly include device ID
                reset_queue: true
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
    document.addEventListener('mousedown', handleClickOutside);
    
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
                    console.log("Real track data received via WebSocket", e.playback_data);
                    
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
                } else {
                    setSyncingState();
                    queueCompleted.value = false;
                }
            })
    }
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
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
