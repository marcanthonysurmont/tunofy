<template>
    <div class="top-16">
        <Listbox
            v-model="internalSelectedDevice"
            @update:modelValue="handleSelect"
        >
            <div class="mt-1">
                <ListboxButton>
                    <DeviceSelectorStatus
                        :selected-device="selectedDevice"
                        :devices="devices"
                    />
                </ListboxButton>

                <transition
                    enter-active-class="transition duration-100 ease-out"
                    enter-from-class="transform scale-95 opacity-0"
                    enter-to-class="transform scale-100 opacity-100"
                    leave-active-class="transition duration-75 ease-in"
                    leave-from-class="transform scale-100 opacity-100"
                    leave-to-class="transform scale-95 opacity-0"
                >
                    <ListboxOptions
                        class="absolute bottom-full mb-2 right-0 max-h-60 w-60 md:w-80 overflow-auto rounded-md bg-card-background py-1 text-base shadow-lg ring-1 ring-card-stroke focus:outline-none sm:text-sm z-50"
                    >
                        <template v-if="isLoading">
                            <ul key="loading">
                                <li
                                    class="px-4 py-2 text-center text-sm text-zinc-400"
                                >
                                    <svg
                                        class="animate-spin h-5 w-5 mx-auto mb-1"
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
                                        />
                                        <path
                                            class="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.37 0 0 5.37 0 12h4zm2 5.29A8 8 0 014 12H0c0 3.04 1.13 5.82 3 7.94l3-2.65z"
                                        />
                                    </svg>
                                    Loading devices...
                                </li>
                            </ul>
                        </template>

                        <template v-else-if="isTransferingDevice">
                            <ul key="loading-transfer">
                                <li
                                    class="px-4 py-2 text-center text-sm text-zinc-400"
                                >
                                    <svg
                                        class="animate-spin h-5 w-5 mx-auto mb-1"
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
                                        />
                                        <path
                                            class="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.37 0 0 5.37 0 12h4zm2 5.29A8 8 0 014 12H0c0 3.04 1.13 5.82 3 7.94l3-2.65z"
                                        />
                                    </svg>
                                    Transfering playback...
                                </li>
                            </ul>
                        </template>

                        <template v-else-if="devices.length === 0">
                            <ul key="no-devices">
                                <li
                                    class="px-4 py-2 text-center text-lg text-zinc-300"
                                >
                                    No devices found..
                                    <p class="text-sm text-zinc-400 mt-1">
                                        Make sure Spotify is open on at least
                                        one device.
                                    </p>
                                </li>
                            </ul>
                        </template>

                        <template v-else>
                            <ul key="devices">
                                <ListboxOption
                                    v-for="device in devices"
                                    :key="device.id"
                                    :value="device"
                                    v-slot="{ active, selected }"
                                    as="template"
                                >
                                    <li
                                        :class="[
                                            active
                                                ? 'bg-zinc-800 text-zinc-100'
                                                : 'text-zinc-300',
                                            'relative cursor-pointer select-none py-2 pl-3 pr-4 transition-colors duration-200 ease-in-out',
                                        ]"
                                    >
                                        <span
                                            :class="
                                                selected
                                                    ? 'font-medium'
                                                    : 'font-normal'
                                            "
                                        >
                                            <span class="flex items-center">
                                                <span
                                                    v-if="
                                                        device.type ===
                                                        'Smartphone'
                                                    "
                                                >
                                                    <DevicePhoneMobileIcon
                                                        class="h-4 w-4 text-zinc-400 mr-2"
                                                    />
                                                </span>
                                                <span
                                                    v-else-if="
                                                        device.type ===
                                                        'Computer'
                                                    "
                                                >
                                                    <ComputerDesktopIcon
                                                        class="h-4 w-4 text-zinc-400 mr-2"
                                                    />
                                                </span>
                                                <span>{{ device.name }}</span>
                                            </span>
                                        </span>
                                        <span
                                            v-if="selected"
                                            class="absolute inset-y-0 right-2 flex items-center pl-3 text-zinc-500"
                                        >
                                            <CheckIcon
                                                class="h-5 w-5 text-primary"
                                                aria-hidden="true"
                                            />
                                        </span>
                                    </li>
                                </ListboxOption>
                            </ul>
                        </template>
                        <li
                            @click="emit('refresh-devices')"
                            class="cursor-pointer text-zinc-400 hover:text-white hover:bg-zinc-800 flex items-center pl-3 pr-4 py-3 text-sm border-t border-card-stroke transition-colors duration-200 ease-in-ou"
                        >
                            <ArrowPathIcon class="h-4 w-4 mr-2" />
                            Refresh devices
                        </li>
                    </ListboxOptions>
                </transition>
            </div>
        </Listbox>
    </div>
</template>

<script setup>
import {
    Listbox,
    ListboxButton,
    ListboxOptions,
    ListboxOption,
} from "@headlessui/vue";
import {
    CheckIcon,
    DevicePhoneMobileIcon,
    ComputerDesktopIcon,
    ArrowPathIcon,
} from "@heroicons/vue/24/solid";

import DeviceSelectorStatus from "@/components/playback/device/DeviceSelectorStatus.vue";

import { ref, watch } from "vue";
const props = defineProps({
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

const emit = defineEmits(["update:selected-device", "refresh-devices"]);

const internalSelectedDevice = ref(props.selectedDevice);

watch(
    () => props.selectedDevice,
    (val) => {
        internalSelectedDevice.value = val;
    }
);

function handleSelect(device) {
    emit("update:selected-device", device);
}
</script>
