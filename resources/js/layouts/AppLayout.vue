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
                            <SidebarMobile
                                @show-join-mix-modal="
                                    isJoinMixModalVisible = true
                                "
                                @show-add-mix-modal="
                                    isAddMixModalVisible = true
                                "
                            />
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Static sidebar for DESKTOP -->
        <SidebarDesktop
            @show-join-mix-modal="isJoinMixModalVisible = true"
            @show-add-mix-modal="isAddMixModalVisible = true"
        />

        <!-- top part of navigation mobile-->
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

        <main class="py-10 lg:pl-80 max-w-[1955px] mx-auto">
            <div class="px-4 sm:px-6 lg:px-8 xl:px-12">
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
    <DeleteConfirmationModal
        :is-visible="storeConfirmationModal.isVisible"
        :title="storeConfirmationModal.title"
        :text="storeConfirmationModal.text"
        @close-modal="storeConfirmationModal.cancel"
        @confirm="storeConfirmationModal.accept"
    />
</template>

<script setup>
import {
    Dialog,
    DialogPanel,
    TransitionChild,
    TransitionRoot,
} from "@headlessui/vue";
import { Bars3Icon, XMarkIcon } from "@heroicons/vue/24/outline";

import { ref } from "vue";
import CreateMixModal from "@/components/modals/mixes/CreateMixModal.vue";
import JoinMixModal from "@/components/modals/mixes/JoinMixModal.vue";
import ToastList from "@/components/toasts/ToastList.vue";
import { usePage } from "@inertiajs/vue3";
import DeleteConfirmationModal from "@/components/modals/DeleteConfirmationModal.vue";
import { StoreConfirmationModal } from "@/stores/StoreConfirmationModal";
import SidebarDesktop from "@/components/navigation/SidebarDesktop.vue";
import SidebarMobile from "@/components/navigation/SidebarMobile.vue";
const storeConfirmationModal = StoreConfirmationModal();

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
