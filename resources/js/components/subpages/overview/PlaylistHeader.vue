<template>
    <div class="flex flex-col gap-2">
        <div
            class="flex flex-col sm:flex-row gap-4 md:gap-8 w-full items-center relative z-10 py-2 pr-2 mt-8 sm:mt-16"
        >
            <img
                :src="getImageUrl(mix)"
                class="size-40 sm:size-64 object-cover flex-shrink-0 rounded-md"
            />

            <div
                class="flex flex-col justify-center items-center sm:items-start gap-3 flex-1 overflow-hidden min-w-0 py-2 text-center sm:text-left"
            >
                <h1
                    class="text-3xl sm:text-4xl md:text-5xl text-white font-normal mt-0 mb-0 leading-none truncate w-full"
                >
                    {{ mix.name }}
                </h1>
                <div class="flex flex-col sm:flex-row items-center">
                    <span
                        class="text-dark-white text-base sm:text-lg mt-0 mb-0 leading-none"
                    >
                        <span class="font-bold">Gilles Serrien</span> • 234
                        songs, approx. 19 hours
                    </span>
                </div>
            </div>
        </div>

        <div
            class="flex flex-row gap-3 justify-center sm:justify-start mt-2 sm:mt-0"
        >
            <UserPlusIcon
                class="size-7 sm:size-9 text-dark-white cursor-pointer custom-item-hover"
            />
            <UserMinusIcon
                class="size-7 sm:size-9 text-dark-white cursor-pointer custom-item-hover"
            />
            <Cog8ToothIcon
                class="size-7 sm:size-9 text-dark-white cursor-pointer custom-item-hover"
            />
            <MenuDropdown>
                <MenuItem v-slot="{ active }">
                    <button
                        :class="[
                            active
                                ? 'bg-card-background-lighter text-dark-white cursor-pointer'
                                : 'text-white',
                            'group flex w-full items-center rounded-md px-2 py-2 text-sm',
                        ]"
                    >
                        <PencilIcon
                            :active="active"
                            class="mr-2 h-5 w-5 text-white"
                            aria-hidden="true"
                        />
                        <span class="font-medium align-middle"
                            >Edit information</span
                        >
                    </button>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                    <button
                        :class="[
                            active
                                ? 'bg-card-background-lighter text-dark-white cursor-pointer'
                                : 'text-white',
                            'group flex w-full items-center rounded-md px-2 py-2 text-sm',
                        ]"
                    >
                        <LockClosedIcon
                            :active="active"
                            class="mr-2 h-5 w-5 text-white"
                            aria-hidden="true"
                        />
                        <span class="font-medium align-middle"
                            >Make private</span
                        >
                    </button>
                </MenuItem>
            </MenuDropdown>
        </div>
    </div>
</template>

<script setup>
import { MenuItem } from "@headlessui/vue";
import {
    Cog8ToothIcon,
    TrashIcon,
    UserMinusIcon,
    UserPlusIcon,
    PencilIcon,
    LockClosedIcon,
} from "@heroicons/vue/24/outline";

import MenuDropdown from "@/components/menus/MenuDropdown.vue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();
const props = page.props;
const mix = props.mix;
console.log(props);

function getImageUrl(song) {
    return song.avatar === null
        ? "/images/default-avatar.jpg"
        : "/storage/" + song.avatar;
}
</script>
