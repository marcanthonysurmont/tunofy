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
                        alt="Tunofy Logo"
                    />
                </Link>
            </div>
            <nav class="flex flex-1 flex-col min-h-0">
                <div class="flex flex-1 flex-col gap-y-7 min-h-0">
                    <div class="flex flex-col mb-8 flex-1 min-h-0">
                        <div
                            class="flex flex-row justify-between items-center mb-2"
                        >
                            <h1 class="text-2xl font-medium heading-center">
                                Your Mixes
                            </h1>
                            <button
                                v-if="user.authorized.hasPremium"
                                @click="emit('show-add-mix-modal')"
                                class="size-8 bg-primary p-1.5 rounded-lg custom-item-hover flex items-center justify-center cursor-pointer"
                                aria-label="Add a new mix"
                                title="Add a new mix"
                                type="button"
                            >
                                <PlusIcon
                                    class="text-white w-5 h-5"
                                    aria-hidden="true"
                                />
                            </button>
                        </div>
                        <div
                            class="flex-1 flex flex-col gap-y-4 overflow-y-auto custom-scrollbar-hover min-h-0"
                        >
                            <YourMixesList />
                        </div>
                    </div>
                    <div class="flex flex-col mb-8 flex-1 min-h-0">
                        <div
                            class="flex flex-row justify-between items-center mb-2"
                        >
                            <h1 class="text-2xl font-medium heading-center">
                                Joined Mixes
                            </h1>
                            <button
                                @click="emit('show-join-mix-modal')"
                                class="size-8 bg-primary p-1.5 rounded-lg custom-item-hover flex items-center justify-center cursor-pointer"
                                aria-label="Join a mix"
                                title="Join a mix"
                                type="button"
                            >
                                <PlusIcon
                                    class="text-white w-5 h-5"
                                    aria-hidden="true"
                                />
                            </button>
                        </div>
                        <div
                            class="flex-1 flex flex-col gap-y-4 overflow-y-auto custom-scrollbar-hover min-h-0"
                        >
                            <JoinedMixesList />
                        </div>
                    </div>
                    <div
                        class="-mx-6 mt-auto flex flex-row items-center justify-between border-t border-regular-stroke px-6 py-3"
                    >
                        <div
                            class="flex items-center gap-x-2 text-md/6 font-bold text-white"
                        >
                            <img
                                class="size-10 border-regular-stroke border-2 rounded-full bg-zinc-700"
                                v-lazy="{
                                    src:
                                        user.avatar ||
                                        '/images/default-avatar.jpg',
                                    error: '/images/default-avatar.jpg',
                                    loading: '/images/default-avatar.jpg',
                                }"
                                alt="User avatar of logged in user"
                            />
                            <span class="font-medium">{{ user.name }}</span>
                        </div>
                        <div class="flex items-center gap-x-2">
                            <Link
                                :href="route('settings')"
                                aria-label="Settings"
                                title="Settings"
                            >
                                <Cog8ToothIcon
                                    aria-hidden="true"
                                    class="size-6 stroke-2 text-white font-bold cursor-pointer custom-item-hover"
                                />
                            </Link>
                            <Link
                                :href="route('logout')"
                                method="POST"
                                aria-label="Log out"
                                title="Log out"
                            >
                                <ArrowLeftEndOnRectangleIcon
                                    aria-hidden="true"
                                    class="size-6 stroke-2 text-white font-bold cursor-pointer custom-item-hover"
                                />
                            </Link>
                        </div>
                    </div>
                </div>
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
