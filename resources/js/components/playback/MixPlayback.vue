<template>
    <div
        class="fixed bottom-0 left-0 right-0 w-full z-[999] bg-card-background/40 border-t-2 lg:border-2 backdrop-blur-xl border-card-stroke p-4 lg:fixed lg:bottom-5 lg:left-1/2 lg:-translate-x-1/2 lg:ml-[160px] lg:max-w-2xl lg:rounded-lg xl:max-w-3xl"
    >
        <!-- top part of the player -->
        <div
            v-if="props.mix.authorized.isOwner"
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
                    v-if="props.mix.authorized.isOwner"
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
                <div v-if="props.mix.authorized.isOwner" class="mr-4 relative" ref="deviceDropdownRef">
                    <button 
                        @click="isDeviceDropdownOpen = !isDeviceDropdownOpen" 
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
                        v-if="props.mix.authorized.isOwner"
                        class="flex flex-row items-center justify-center flex-none gap-2"
                    >
                        <ChevronDoubleLeftIcon class="size-6 opacity-50" />
                        <PlayCircleIcon class="size-12 opacity-50" />
                        <ChevronDoubleRightIcon class="size-6 opacity-50" />
                    </div>
                    
                    <!-- Device selector for mobile above status indicator -->
                    <div v-if="props.mix.authorized.isOwner" class="mb-2 relative" ref="deviceDropdownRefMobile">
                        <button 
                            @click="isDeviceDropdownOpen = !isDeviceDropdownOpen" 
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
                    v-if="props.mix.authorized.isOwner"
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
                <div v-if="props.mix.authorized.isOwner" class="mr-4 relative" ref="deviceDropdownRefActive">
                    <button 
                        @click="isDeviceDropdownOpen = !isDeviceDropdownOpen" 
                        type="button" 
                        class="flex items-center gap-x-1 text-sm font-semibold leading-6 text-zinc-200 bg-card-background/80 px-3 py-1.5 rounded-md border border-zinc-700"
                    >
                        <div class="flex items-center">
                            <span>{{ selectedDevice ? selectedDevice.name : 'Select device' }}</span>
                            <ComputerDesktopIcon v-if="selectedDevice" class="ml-2 h-4 w-4 text-zinc-400" />
                        </div>
                        <ChevronDownIcon class="h-5 w-5 text-zinc-400" aria-hidden="true" />
                    </button>
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
                        v-if="props.mix.authorized.isOwner"
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

// Device selection variables
const devices = ref(page.props.devices || []);
const isDeviceDropdownOpen = ref(false);
const isLoadingDevices = ref(false);
const deviceDropdownRef = ref(null);

// Find initially active device from the devices array
const selectedDevice = ref(devices.value.find(d => d.is_active) || null);

// Handle click outside dropdown
function handleClickOutside(event) {
    if (deviceDropdownRef.value && !deviceDropdownRef.value.contains(event.target)) {
        isDeviceDropdownOpen.value = false;
    }
}

// Add and remove event listeners for click outside
onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
    
    if (props.mix) {
        // Only fetch devices if not provided in page props
        if (devices.value.length === 0 && props.mix.authorized.isOwner) {
            refreshDevices();
        }
        
        // Rest of your onMounted code...
        
        // Add WebSocket listener for device changes
        Echo.channel(`mix.${props.mix.id}`)
            .listen(".device-changed", (e) => {
                console.log("Device changed event:", e);
                if (e.device) {
                    // Look for device in current list or add it
                    const existingDevice = devices.value.find(d => d.id === e.device.id);
                    if (existingDevice) {
                        selectedDevice.value = existingDevice;
                    } else {
                        // Add device to list and select it
                        devices.value.push(e.device);
                        selectedDevice.value = e.device;
                    }
                }
            });
            
        // Your existing Echo listeners...
    }
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});

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
        isLoading.value = true;
        
        const response = await axios.post('/api/spotify/transfer-playback', {
            mix_id: props.mix.id,
            device_id: deviceId
        });
        
        if (!response.data.success) {
            console.error('Failed to transfer playback');
        }
    } catch (error) {
        console.error('Error transferring playback:', error);
    } finally {
        isLoading.value = false;
    }
}

// Update toggleMixActive to use selected device
async function toggleMixActive() {
    try {
        console.log("Toggling mix active state");
        // Prevent rapid toggling
        if (Date.now() - lastToggleTime.value < 1000) return;
        lastToggleTime.value = Date.now();

        // Check if a device is selected when activating
        if (!isMixActive.value && !selectedDevice.value) {
            // If no device selected but devices exist, use the first one
            if (devices.value.length > 0) {
                selectedDevice.value = devices.value[0];
            } else {
                // Fetch devices if none available
                await refreshDevices();
                
                if (devices.value.length === 0) {
                    console.error("No Spotify devices available");
                    return;
                }
                
                selectedDevice.value = devices.value[0];
            }
        }

        // Set loading state
        isLoading.value = true;

        // Optimistically update UI
        const targetActive = !isMixActive.value;

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

        // Make API call with device ID
        const response = await axios.post("/api/spotify/set-mix-active", {
            mix_id: props.mix.id,
            active: targetActive,
            reset_queue: targetActive,
            device_id: selectedDevice.value?.id
        });

        // Rest of your existing function...
    } catch (err) {
        console.error("Error toggling mix active status:", err);
        clearSyncingState();
    } finally {
        setTimeout(() => (isLoading.value = false), 500);
    }
}

// Rest of your script remains the same...
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
