<template>
    <!-- Static sidebar for DESKTOP -->
    <div
        class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-80 lg:flex-col h-screen"
    >
        <div
            class="flex grow flex-col gap-y-5 bg-navbar-background border-r-2 border-regular-stroke px-6 h-full"
        >
            <div class="flex h-16 shrink-0 items-center">
                <Link :href="route('app')">
                    <img
                        class="h-8 w-auto"
                        src="/images/logos/tunofy-logo-white.png"
                        alt="Tunofy"
                    />
                </Link>
            </div>
            <nav class="flex flex-1 flex-col min-h-0">
                <ul role="list" class="flex flex-1 flex-col gap-y-7 min-h-0">
                    <li class="flex flex-col mb-8 flex-1 min-h-0">
                        <div
                            class="flex flex-row justify-between items-center mb-6"
                        >
                            <h1 class="text-2xl font-medium heading-center">
                                Your Mixes
                            </h1>
                            <PlusIcon
                                @click="emit('show-add-mix-modal')"
                                class="size-8 text-white bg-primary p-1.5 cursor-pointer rounded-lg custom-item-hover"
                            />
                        </div>
                        <ul
                            role="list"
                            class="flex-1 flex flex-col gap-y-4 overflow-y-auto custom-scrollbar-hover min-h-0"
                        >
                            <YourMixesList />
                        </ul>
                    </li>
                    <li class="flex flex-col mb-8 flex-1 min-h-0">
                        <div
                            class="flex flex-row justify-between items-center mb-6"
                        >
                            <h1 class="text-2xl font-medium heading-center">
                                Joined Mixes
                            </h1>
                            <PlusIcon
                                @click="emit('show-join-mix-modal')"
                                class="size-8 text-white bg-primary p-1.5 cursor-pointer rounded-lg custom-item-hover"
                            />
                        </div>
                        <ul
                            role="list"
                            class="flex-1 flex flex-col gap-y-4 overflow-y-auto custom-scrollbar-hover min-h-0"
                        >
                            <JoinedMixesList />
                        </ul>
                    </li>
                    <li
                        class="-mx-6 mt-auto flex flex-row items-center justify-between border-t border-regular-stroke px-6 py-3"
                    >
                        <a
                            href="#"
                            class="flex items-center gap-x-2 text-md/6 font-bold text-white"
                        >
                            <img
                                class="size-10 border-regular-stroke border-2 rounded-full bg-zinc-700"
                                :src="
                                    user.avatar || '/images/default-avatar.jpg'
                                "
                                alt="User avatar of logged in user"
                            />
                            <span class="sr-only">Your profile</span>
                            <span aria-hidden="true" class="font-medium">{{
                                user.name
                            }}</span>
                        </a>
                        <div class="flex items-center gap-x-2">
                            <Link :href="route('settings')">
                                <Cog8ToothIcon
                                    class="size-6 stroke-2 text-white font-bold cursor-pointer custom-item-hover"
                                />
                            </Link>
                            <Link :href="route('logout')" method="POST">
                                <ArrowLeftEndOnRectangleIcon
                                    class="size-6 stroke-2 text-white font-bold cursor-pointer custom-item-hover"
                                />
                            </Link>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</template>

<script setup>
import JoinedMixesList from "@/components/mixes/JoinedMixesList.vue";
import YourMixesList from "@/components/mixes/YourMixesList.vue";
import { Link, usePage } from "@inertiajs/vue3";
import {
    PlusIcon,
    ArrowLeftEndOnRectangleIcon,
    Cog8ToothIcon,
} from "@heroicons/vue/24/outline";

const emit = defineEmits(["show-join-mix-modal", "show-add-mix-modal"]);

const page = usePage();
const user = page.props.user;
</script>
