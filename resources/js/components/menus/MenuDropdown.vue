<template>
    <Menu as="div" class="relative inline-block text-left">
        <div>
            <Float
                flip
                enter="transition duration-100 ease-out"
                enter-from="transform scale-95 opacity-0"
                enter-to="transform scale-100 opacity-100"
                leave="transition duration-75 ease-in"
                leave-from="transform scale-100 opacity-100"
                leave-to="transform scale-95 opacity-0"
            >
                <MenuButton
                    class="inline-flex w-full z-10 justify-center rounded-md text-sm font-medium text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/75 cursor-pointer"
                >
                    <EllipsisHorizontalIcon class="size-9" />
                </MenuButton>
                <MenuItems
                    class="shadow-2xl z-[10000] mt-2 w-56 xs:max-h-36 md:max-h-64 overflow-y-auto custom-scrollbar origin-top-right divide-y divide-gray-100 rounded-md bg-card-background border-2 border-card-stroke ring-1 ring-black/5 focus:outline-none"
                >
                    <div class="px-1.5 py-1.5">
                        <slot></slot>
                    </div>
                </MenuItems>
            </Float>
        </div>
    </Menu>
</template>

<script setup>
import { Menu, MenuButton, MenuItems, MenuItem } from "@headlessui/vue";
import { EllipsisHorizontalIcon } from "@heroicons/vue/24/outline";
import { Float } from "@headlessui-float/vue";
</script>
