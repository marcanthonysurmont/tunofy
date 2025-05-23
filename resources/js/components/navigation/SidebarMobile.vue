<template>
    <div
        class="flex grow flex-col gap-y-5 bg-navbar-background px-6 pb-2 ring-1 h-[100dvh] ring-white/10"
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
        <nav class="flex flex-1 flex-col">
            <ul role="list" class="flex flex-1 flex-col gap-y-4">
                <li class="min-h-[200px] mb-4">
                    <div
                        class="flex flex-row justify-between items-center mb-3"
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
                        class="flex flex-col gap-y-4 overflow-y-auto max-h-[calc(50dvh-160px)]"
                    >
                        <YourMixesList />
                    </ul>
                </li>
                <li class="min-h-[200px]">
                    <div
                        class="flex flex-row justify-between items-center mb-3"
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
                        class="flex flex-col gap-y-4 overflow-y-auto max-h-[calc(50dvh-160px)]"
                    >
                        <JoinedMixesList />
                    </ul>
                </li>
            </ul>
        </nav>
        <li
            class="-mx-6 mt-auto flex flex-row items-center justify-between border-t border-regular-stroke px-6 py-3"
        >
            <a
                href="#"
                class="flex items-center gap-x-2 text-md/6 font-bold text-white"
            >
                <img
                    class="size-10 border-regular-stroke border-2 rounded-full bg-zinc-700"
                    v-lazy="{
                        src: user.avatar || '/images/default-avatar.jpg',
                        error: '/images/default-avatar.jpg',
                        loading: '/images/default-avatar.jpg',
                    }"
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
