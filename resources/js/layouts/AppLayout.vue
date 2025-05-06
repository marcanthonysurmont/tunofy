<template>
    <div>
        <TransitionRoot as="template" :show="sidebarOpen">
            <Dialog
                class="relative z-50 lg:hidden"
                @close="sidebarOpen = false"
            >
                <TransitionChild
                    as="template"
                    enter="transition-opacity ease-linear duration-300"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="transition-opacity ease-linear duration-300"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-navbar-background/80" />
                </TransitionChild>

                <div class="fixed inset-0 flex">
                    <TransitionChild
                        as="template"
                        enter="transition ease-in-out duration-300 transform"
                        enter-from="-translate-x-full"
                        enter-to="translate-x-0"
                        leave="transition ease-in-out duration-300 transform"
                        leave-from="translate-x-0"
                        leave-to="-translate-x-full"
                    >
                        <DialogPanel
                            class="relative mr-16 flex w-full max-w-xs flex-1"
                        >
                            <TransitionChild
                                as="template"
                                enter="ease-in-out duration-300"
                                enter-from="opacity-0"
                                enter-to="opacity-100"
                                leave="ease-in-out duration-300"
                                leave-from="opacity-100"
                                leave-to="opacity-0"
                            >
                                <div
                                    class="absolute top-0 left-full flex w-16 justify-center pt-5"
                                >
                                    <button
                                        type="button"
                                        class="-m-2.5 p-2.5"
                                        @click="sidebarOpen = false"
                                    >
                                        <span class="sr-only"
                                            >Close sidebar</span
                                        >
                                        <XMarkIcon
                                            class="size-6 text-white"
                                            aria-hidden="true"
                                        />
                                    </button>
                                </div>
                            </TransitionChild>
                            <!-- Sidebar component, swap this element with another sidebar if you like -->
                            <div
                                class="flex grow flex-col gap-y-5 overflow-y-auto bg-navbar-background px-6 pb-2 ring-1 ring-white/10"
                            >
                                <div class="flex h-16 shrink-0 items-center">
                                    <img
                                        class="h-8 w-auto"
                                        src="/images/logos/tunofy-logo-white.png"
                                        alt="Tunofy"
                                    />
                                </div>
                                <nav class="flex flex-1 flex-col">
                                    <ul
                                        role="list"
                                        class="flex flex-1 flex-col gap-y-7"
                                    >
                                        <li class="min-h-[200px] mb-8">
                                            <div
                                                class="flex flex-row justify-between items-center mb-6"
                                            >
                                                <h1
                                                    class="text-2xl font-medium heading-center"
                                                >
                                                    Your Mixes
                                                </h1>
                                                <PlusIcon
                                                    @click="
                                                        isAddMixModalVisible = true
                                                    "
                                                    class="size-8 text-white bg-primary p-1.5 cursor-pointer rounded-lg custom-item-hover"
                                                />
                                            </div>
                                            <ul
                                                role="list"
                                                class="flex flex-col gap-y-4"
                                            >
                                                <YourMixesList />
                                            </ul>
                                        </li>
                                        <li class="min-h-[200px] mb-8">
                                            <div
                                                class="flex flex-row justify-between items-center"
                                            >
                                                <h1
                                                    class="text-2xl font-medium heading-center"
                                                >
                                                    Joined Mixes
                                                </h1>
                                                <PlusIcon
                                                    @click="
                                                        isJoinMixModalVisible = true
                                                    "
                                                    class="size-8 text-white bg-primary p-1.5 cursor-pointer rounded-lg custom-item-hover"
                                                />
                                            </div>
                                            <ul
                                                role="list"
                                                class="flex flex-col gap-y-4"
                                            >
                                                <JoinedMixesList />
                                            </ul>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Static sidebar for DESKTOP -->
        <div
            class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-80 lg:flex-col"
        >
            <div
                class="flex grow flex-col gap-y-5 overflow-y-auto bg-navbar-background border-r-2 border-regular-stroke px-6"
            >
                <div class="flex h-16 shrink-0 items-center">
                    <img
                        class="h-8 w-auto"
                        src="/images/logos/tunofy-logo-white.png"
                        alt="Tunofy"
                    />
                </div>
                <nav class="flex flex-1 flex-col">
                    <ul role="list" class="flex flex-1 flex-col gap-y-7">
                        <li class="min-h-[200px] mb-8">
                            <div
                                class="flex flex-row justify-between items-center mb-6"
                            >
                                <h1 class="text-2xl font-medium heading-center">
                                    Your Mixes
                                </h1>
                                <PlusIcon
                                    @click="isAddMixModalVisible = true"
                                    class="size-8 text-white bg-primary p-1.5 cursor-pointer rounded-lg custom-item-hover"
                                />
                            </div>
                            <ul
                                role="list"
                                class="flex flex-col max-h-[800px] overflow-y-auto custom-scrollbar gap-y-4"
                            >
                                <YourMixesList />
                            </ul>
                        </li>
                        <li class="min-h-[200px] mb-8">
                            <div
                                class="flex flex-row justify-between items-center"
                            >
                                <h1 class="text-2xl font-medium heading-center">
                                    Joined Mixes
                                </h1>
                                <PlusIcon
                                    @click="isJoinMixModalVisible = true"
                                    class="size-8 text-white bg-primary p-1.5 cursor-pointer rounded-lg custom-item-hover"
                                />
                            </div>
                            <ul
                                role="list"
                                class="flex flex-col max-h-[800px] overflow-y-auto custom-scrollbar gap-y-4"
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
                                        user.avatar ||
                                        '/images/default-avatar.jpg'
                                    "
                                    alt="User avatar of logged in user"
                                />
                                <span class="sr-only">Your profile</span>
                                <span aria-hidden="true" class="font-medium">{{
                                    user.name
                                }}</span>
                            </a>
                            <div class="flex items-center gap-x-2">
                                <Link :href="route('logout')" method="GET">
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

        <div
            class="sticky top-0 z-40 flex items-center gap-x-6 bg-navbar-background px-4 py-4 shadow-xs sm:px-6 lg:hidden border-b-2 border-regular-stroke"
        >
            <button
                type="button"
                class="-m-2.5 p-2.5 text-gray-400 lg:hidden"
                @click="sidebarOpen = true"
            >
                <span class="sr-only">Open sidebar</span>
                <Bars3Icon class="size-6" aria-hidden="true" />
            </button>
            <!-- <div class="flex-1 text-sm/6 font-semibold text-white">
                Dashboard
            </div> -->
            <!-- <a href="#">
                <span class="sr-only">Your profile</span>
                <img
                    class="size-8 rounded-full bg-zinc-700"
                    :src="user.avatar"
                    alt="Avatar of logged in user"
                />
            </a> -->
        </div>

        <main class="py-10 lg:pl-80">
            <div class="px-4 sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>
    </div>
    <CreateMixModal
        :is-visible="isAddMixModalVisible"
        @close-modal="closeAddMixModal"
    />
    <JoinMixModal
        :is-visible="isJoinMixModalVisible"
        @close-modal="closeJoinMixModal"
    />
    <ToastList />
</template>

<script setup>
import {
    Dialog,
    DialogPanel,
    TransitionChild,
    TransitionRoot,
} from "@headlessui/vue";
import {
    ArrowLeftEndOnRectangleIcon,
    Bars3Icon,
    PlusIcon,
    XMarkIcon,
} from "@heroicons/vue/24/outline";

import { ref } from "vue";
import { Link } from "@inertiajs/vue3";
import CreateMixModal from "@/components/modals/mixes/CreateMixModal.vue";
import JoinMixModal from "@/components/modals/mixes/JoinMixModal.vue";
import YourMixesList from "@/components/mixes/YourMixesList.vue";
import ToastList from "@/components/toasts/ToastList.vue";
import { usePage } from "@inertiajs/vue3";
import JoinedMixesList from "../components/mixes/JoinedMixesList.vue";

const page = usePage();
const user = page.props.user;

const sidebarOpen = ref(false);

const isAddMixModalVisible = ref(false);
function closeAddMixModal() {
    isAddMixModalVisible.value = false;
}

const isJoinMixModalVisible = ref(false);
function closeJoinMixModal() {
    isJoinMixModalVisible.value = false;
}
</script>
