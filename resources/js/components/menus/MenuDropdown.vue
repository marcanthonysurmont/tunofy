<template>
    <Menu as="div" class="relative inline-block text-left">
        <div>
            <MenuButton
                class="inline-flex w-full justify-center rounded-md text-sm font-medium text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/75 cursor-pointer"
            >
                <EllipsisHorizontalIcon class="size-9" />
            </MenuButton>
        </div>

        <transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <MenuItems
                class="absolute md:left-0 right-0 mt-2 w-56 origin-top-right divide-y divide-gray-100 rounded-md bg-card-background border-2 border-card-stroke shadow-lg ring-1 ring-black/5 focus:outline-none"
            >
                <div class="px-1.5 py-1.5">
                    <slot></slot>
                </div>
            </MenuItems>
        </transition>
    </Menu>
</template>

<script setup>
import { Menu, MenuButton, MenuItems, MenuItem } from "@headlessui/vue";
import { EllipsisHorizontalIcon } from "@heroicons/vue/24/outline";
</script>
