<template>
    <ul role="list">
        <li
            v-for="person in people"
            :key="person.email"
            class="flex justify-between gap-x-6 py-5"
        >
            <div class="flex min-w-0 gap-x-4">
                <img
                    class="size-12 flex-none rounded-full bg-zinc-50"
                    :src="person.imageUrl"
                    alt=""
                />
                <div class="min-w-0 flex-auto">
                    <p class="text-md font-semibold text-zinc-200">
                        <span class="font-semibold">{{ person.name }}</span>
                    </p>
                    <p class="mt-1 flex text-xs/5 text-zinc-400">
                        <span class="truncate">{{ person.email }}</span>
                    </p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-x-6">
                <div class="flex flex-col items-end">
                    <span
                        class="inline-block rounded-full px-4 py-1.5 text-xs font-semibold bg-[#2B55CC]/10 text-[#91A8E8]"
                    >
                        {{ person.role }}
                    </span>
                </div>
                <Menu as="div" class="relative flex-none">
                    <MenuButton class="-m-2.5 block p-2.5 text-zinc-400">
                        <span class="sr-only">Open options</span>
                        <EllipsisVerticalIcon
                            class="size-5"
                            aria-hidden="true"
                        />
                    </MenuButton>
                    <transition
                        enter-active-class="transition ease-out duration-100"
                        enter-from-class="transform opacity-0 scale-95"
                        enter-to-class="transform opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="transform opacity-100 scale-100"
                        leave-to-class="transform opacity-0 scale-95"
                    >
                        <MenuItems
                            class="shadow-2xl z-[1000] absolute right-0 mt-2 w-56 xs:max-h-36 md:max-h-64 overflow-y-auto custom-scrollbar origin-top-right divide-y-2 divide-card-stroke rounded-md bg-card-background border-2 border-card-stroke ring-1 ring-black/5 focus:outline-none"
                        >
                            <MenuItem v-slot="{ active }">
                                <button
                                    :class="[
                                        active
                                            ? 'bg-card-background-lighter text-dark-white cursor-pointer'
                                            : 'text-white',
                                        'group flex w-full items-center rounded-md px-2 py-2 text-sm',
                                    ]"
                                >
                                    <ShieldCheckIcon
                                        :active="active"
                                        class="mr-2 h-5 w-5 text-white"
                                        aria-hidden="true"
                                    />
                                    <span class="font-medium align-middle"
                                        >Edit permission</span
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
                                    <XMarkIcon
                                        :active="active"
                                        class="mr-2 h-5 w-5 text-white"
                                        aria-hidden="true"
                                    />
                                    <span class="font-medium align-middle"
                                        >Kick from mix</span
                                    >
                                </button>
                            </MenuItem>
                        </MenuItems>
                    </transition>
                </Menu>
            </div>
        </li>
    </ul>
</template>

<script setup>
import { Menu, MenuButton, MenuItem, MenuItems } from "@headlessui/vue";
import { EllipsisVerticalIcon } from "@heroicons/vue/20/solid";
import {
    LockClosedIcon,
    ShieldCheckIcon,
    ShieldExclamationIcon,
    TrashIcon,
    XMarkIcon,
} from "@heroicons/vue/24/outline";

const people = [
    {
        name: "Gilles Serrien",
        email: "serriengilles@gmail.com",
        role: "View",
        imageUrl:
            "https://platform-lookaside.fbsbx.com/platform/profilepic/?asid=1300984920068935&height=300&width=300&ext=1749209019&hash=AbYl5Tspo9hy5H4K5hKUb8_-",
    },
    {
        name: "Gilles Serrien",
        email: "serriengilles@gmail.com",
        role: "Editor",
        imageUrl:
            "https://platform-lookaside.fbsbx.com/platform/profilepic/?asid=1300984920068935&height=300&width=300&ext=1749209019&hash=AbYl5Tspo9hy5H4K5hKUb8_-",
    },
    {
        name: "Gilles Serrien",
        email: "serriengilles@gmail.com",
        role: "Contributor",
        imageUrl:
            "https://platform-lookaside.fbsbx.com/platform/profilepic/?asid=1300984920068935&height=300&width=300&ext=1749209019&hash=AbYl5Tspo9hy5H4K5hKUb8_-",
    },
    {
        name: "Gilles Serrien",
        email: "serriengilles@gmail.com",
        role: "Editor",
        imageUrl:
            "https://platform-lookaside.fbsbx.com/platform/profilepic/?asid=1300984920068935&height=300&width=300&ext=1749209019&hash=AbYl5Tspo9hy5H4K5hKUb8_-",
    },
    {
        name: "Gilles Serrien",
        email: "serriengilles@gmail.com",
        role: "Contributor",
        imageUrl:
            "https://platform-lookaside.fbsbx.com/platform/profilepic/?asid=1300984920068935&height=300&width=300&ext=1749209019&hash=AbYl5Tspo9hy5H4K5hKUb8_-",
    },
    {
        name: "Gilles Serrien",
        email: "serriengilles@gmail.com",
        role: "View",
        imageUrl:
            "https://platform-lookaside.fbsbx.com/platform/profilepic/?asid=1300984920068935&height=300&width=300&ext=1749209019&hash=AbYl5Tspo9hy5H4K5hKUb8_-",
    },
];
</script>
