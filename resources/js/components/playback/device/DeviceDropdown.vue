<template>
    <div class="top-16">
        <Listbox
            v-model="internalSelectedDevice"
            @update:modelValue="handleSelect"
        >
            <div class="mt-1">
                <ListboxButton>
                    <div class="relative">
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
    ChevronDownIcon,
    DevicePhoneMobileIcon,
    ComputerDesktopIcon,
    ArrowPathIcon,
    XCircleIcon,
    ExclamationCircleIcon,
} from "@heroicons/vue/24/solid";

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
