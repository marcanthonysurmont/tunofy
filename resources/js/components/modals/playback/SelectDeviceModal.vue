<template>
    <template>
        <Teleport to="body">
            <transition
                enter-active-class="transition duration-100 ease-out"
                enter-from-class="transform scale-95 opacity-0"
                enter-to-class="transform scale-100 opacity-100"
                leave-active-class="transition duration-75 ease-in"
                leave-from-class="transform scale-100 opacity-100"
                leave-to-class="transform scale-95 opacity-0"
            >
                <div
                    v-if="isVisible"
                    class="fixed left-0 top-0 z-[9999] h-screen w-screen px-6 py-8 gap-8 bg-background-page backdrop-blur-md shadow-2xl"
                >
                    <div class="flex flex-row items-start justify-between mb-8">
                        <div class="mt-8">
                            <h1 class="text-3xl flex items-center gap-2">
                                Device selector
                                <ArrowPathIcon
                                    :class="[
                                        'size-6 cursor-pointer',
                                        isSpinningDevices ? 'spin-once' : '',
                                    ]"
                                    @click="refreshDevices"
                                />
                            </h1>
                            <p>Select your prefered playback device.</p>
                        </div>
                        <span class="cursor-pointer text-xl" @click="closeModal"
                            ><XMarkIcon class="size-12 p-2"
                        /></span>
                    </div>
                    <ul
                        class="relative flex list-none flex-col gap-4 p-0"
                        v-auto-animate
                    >
                        <template v-if="devices.length > 0">
                            <li
                                v-for="device in devices"
                                :key="device.id"
                                v-auto-animate
                                class="pr-4 py-2 rounded-lg flex items-center cursor-pointer"
                                :class="{
                                    'text-primary':
                                        internalSelectedDevice?.id ===
                                        device.id,
                                }"
                                @click="handleSelect(device)"
                            >
                                <span v-if="device?.type === 'Smartphone'">
                                    <DevicePhoneMobileIcon
                                        :class="{
                                            'text-primary':
                                                internalSelectedDevice?.id ===
                                                device.id,
                                        }"
                                        class="size-5 mr-2"
                                    />
                                </span>
                                <span v-else-if="device?.type === 'Computer'">
                                    <ComputerDesktopIcon
                                        :class="{
                                            'text-primary':
                                                internalSelectedDevice?.id ===
                                                device.id,
                                        }"
                                        class="size-5 mr-2"
                                    />
                                </span>
                                <p>{{ device.name }}</p>
                            </li>
                        </template>
                        <p v-else class="text-zinc-300">No devices found..</p>
                    </ul>
                </div>
            </transition>
        </Teleport>
    </template>
</template>

<script setup>
import {
    XMarkIcon,
    ComputerDesktopIcon,
    DevicePhoneMobileIcon,
    ArrowPathIcon,
} from "@heroicons/vue/24/solid";

import { ref, watch } from "vue";
const props = defineProps({
    isVisible: {
        type: Boolean,
        default: false,
    },
    devices: {
        type: Array,
        required: true,
    },
    selectedDevice: {
        type: Object,
        default: null,
    },
    isLoading: {
        type: Boolean,
        default: false,
    },
    isTransferingDevice: {
        type: Boolean,
        default: false,
    },
    error: {
        type: Boolean,
        default: false,
    },
});

const isSpinningDevices = ref(false);

watch(
    () => props.isVisible,
    (val) => {
        if (val) {
            refreshDevices();
        }
    }
);

watch(
    () => props.isLoading,
    (newVal) => {
        if (newVal) {
            isSpinningDevices.value = true;
        } else {
            setTimeout(() => {
                isSpinningDevices.value = false;
            }, 500);
        }
    }
);

const emit = defineEmits([
    "update:selected-device",
    "refresh-devices",
    "close-modal",
]);

const internalSelectedDevice = ref(props.selectedDevice);

watch(
    () => props.selectedDevice,
    (val) => {
        internalSelectedDevice.value = val;
    }
);

function handleSelect(device) {
    emit("update:selected-device", device);
    closeModal();
}

function closeModal() {
    emit("close-modal");
}

function refreshDevices() {
    emit("refresh-devices");
}
</script>
<style scoped>
@keyframes spinOnce {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.spin-once {
    animation: spinOnce 0.5s ease-in-out;
}
</style>
