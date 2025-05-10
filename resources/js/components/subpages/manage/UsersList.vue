<template>
    <ul role="list">
        <li
            v-for="user in users"
            :key="user.id"
            class="flex justify-between gap-x-6 py-5"
        >
            <div class="flex min-w-0 gap-x-4 items-center">
                <img
                    class="size-12 flex-none rounded-full bg-zinc-50"
                    :src="
                        user.avatar === null
                            ? '/images/default-avatar.jpg'
                            : user.imageUrl
                    "
                    alt=""
                />
                <div class="min-w-0 flex-auto">
                    <p class="text-md font-semibold text-zinc-200">
                        <span class="font-semibold">{{ user.name }}</span>
                    </p>
                    <!-- <p class="mt-1 flex text-xs/5 text-zinc-400">
                        <span class="truncate">{{ user.email }}</span>
                    </p> -->
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-x-6">
                <div class="flex flex-col items-end">
                    <span
                        class="inline-block rounded-full px-4 py-1.5 text-xs font-semibold bg-[#2B55CC]/10 text-[#91A8E8]"
                    >
                        {{ user.pivot.permission }}
                    </span>
                </div>
                <Menu as="div" class="relative flex-none">
                    <MenuButton
                        class="-m-2.5 block p-2.5 text-zinc-400 cursor-pointer"
                    >
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
                            <MenuItem
                                v-slot="{ active }"
                                @click="kickUser(user)"
                            >
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
import { router, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import {
    LockClosedIcon,
    ShieldCheckIcon,
    ShieldExclamationIcon,
    TrashIcon,
    XMarkIcon,
} from "@heroicons/vue/24/outline";

const page = usePage();
const users = computed(() => page.props.collaborators.data);
const usersProp = computed(() => page.props.collaborators);

function kickUser(user) {
    router.post(
        `/mix/remove-user-access/${page.props.mix.id}`,
        {
            user_id: user.id,
        },
        {
            preserveScroll: true,
        }
    );
}
</script>
