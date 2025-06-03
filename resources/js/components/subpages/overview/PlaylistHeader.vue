<template>
    <div class="flex flex-col gap-2">
        <div
            class="flex flex-col sm:flex-row gap-4 md:gap-8 w-full items-center relative z-10"
        >
            <img
                :src="getImageUrl(mix)"
                class="size-56 sm:size-64 object-cover flex-shrink-0 rounded-md"
            />

            <div
                class="flex flex-col justify-start w-full items-center sm:items-start gap-1 flex-1 overflow-hidden min-w-0 text-left"
            >
                <h1
                    class="text-2xl sm:text-4xl md:text-5xl text-white font-medium mt-0 mb-0 leading-none truncate w-full sm:text-nowrap text-wrap"
                >
                    {{ mix.name }}
                </h1>

                <div class="flex flex-col sm:flex-row sm:items-center w-full">
                    <!-- Desktop combined line -->
                    <div
                        class="hidden sm:flex items-center gap-2 text-zinc-300 text-sm sm:text-base"
                    >
                        <img
                            :src="owner.avatar_url"
                            class="size-5 sm:size-6 rounded-full"
                        />
                        <span class="font-medium text-dark-white">{{
                            owner.name
                        }}</span>
                        <span>•</span>
                        <span
                            >{{ mix.mix_count }}
                            {{ mix.mix_count === 1 ? "song" : "songs" }}</span
                        >
                        <span>•</span>
                        <span>{{ mixDuration }}</span>
                    </div>

                    <!-- Mobile stacked -->
                    <div class="flex sm:hidden flex-col items-start gap-1">
                        <div class="flex items-center gap-2">
                            <img
                                :src="owner.avatar_url"
                                class="size-5 sm:size-6 rounded-full"
                            />
                            <span
                                class="font-medium text-dark-white text-sm sm:text-lg"
                            >
                                {{ owner.name }}
                            </span>
                        </div>
                        <span class="text-zinc-300 text-sm sm:text-base">
                            {{ mixDuration }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 mt-4">
            <div class="flex flex-row gap-3 items-center sm:justify-start">
                <ArrowLeftEndOnRectangleIcon
                    v-if="!authorization.isOwner"
                    @click="leaveMix"
                    class="size-6 sm:size-8 text-dark-white cursor-pointer custom-item-hover"
                />
                <SparklesIcon
                    v-if="authorization.isOwner"
                    @click="showUpdateThemeModal = true"
                    v-tippy="{ content: 'Customize theme' }"
                    class="size-6 sm:size-8 text-dark-white cursor-pointer custom-item-hover"
                />
                <MenuDropdown v-if="showMenuDropdown">
                    <div class="px-1.5 py-1.5" v-if="authorization.isOwner">
                        <MenuItem v-slot="{ active }">
                            <button
                                @click="showUpdateMixModal = true"
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
                    </div>
                    <div
                        class="px-1.5 py-1.5"
                        v-if="
                            authorization.canManageCollaborators &&
                            collaborators.length > 0
                        "
                    >
                        <MenuItem
                            v-slot="{ active }"
                            v-if="
                                authorization.isOwner &&
                                collaborators.length > 0 &&
                                mix.co_dj_id !== null
                            "
                        >
                            <button
                                @click="removeCurrentCoDJ"
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
                                    >Remove current co-DJ</span
                                >
                            </button>
                        </MenuItem>
                    </div>
                    <div
                        class="px-1.5 py-1.5"
                        v-if="authorization.isOwner && mix.is_public"
                    >
                        <MenuItem
                            v-slot="{ active }"
                            v-if="authorization.isOwner && mix.is_public"
                        >
                            <button
                                @click="toggleVisibility"
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
                        <MenuItem
                            v-else-if="authorization.isOwner"
                            v-slot="{ active }"
                        >
                            <button
                                @click="toggleVisibility"
                                :class="[
                                    active
                                        ? 'bg-card-background-lighter text-dark-white cursor-pointer'
                                        : 'text-white',
                                    'group flex w-full items-center rounded-md px-2 py-2 text-sm',
                                ]"
                            >
                                <LockOpenIcon
                                    :active="active"
                                    class="mr-2 h-5 w-5 text-white"
                                    aria-hidden="true"
                                />
                                <span class="font-medium align-middle"
                                    >Make public</span
                                >
                            </button>
                        </MenuItem>
                    </div>
                    <div class="px-1.5 py-1.5" v-if="authorization.isOwner">
                        <MenuItem v-slot="{ active }">
                            <button
                                @click="importFromSpotify"
                                :class="[
                                    active
                                        ? 'bg-card-background-lighter text-dark-white cursor-pointer'
                                        : 'text-white',
                                    'group flex w-full items-center rounded-md px-2 py-2 text-sm',
                                ]"
                            >
                                <ArrowDownOnSquareIcon
                                    :active="active"
                                    class="mr-2 h-5 w-5 text-white"
                                    aria-hidden="true"
                                />
                                <span class="font-medium align-middle"
                                    >Import from Spotify</span
                                >
                            </button>
                        </MenuItem>
                    </div>
                    <div class="px-1.5 py-1.5" v-if="authorization.isOwner">
                        <MenuItem v-slot="{ active }">
                            <button
                                @click="deleteMix"
                                :class="[
                                    active
                                        ? 'bg-card-background-lighter text-dark-white cursor-pointer'
                                        : 'text-white',
                                    'group flex w-full items-center rounded-md px-2 py-2 text-sm',
                                ]"
                            >
                                <TrashIcon
                                    :active="active"
                                    class="mr-2 h-5 w-5 text-white"
                                    aria-hidden="true"
                                />
                                <span class="font-medium align-middle"
                                    >Delete mix</span
                                >
                            </button>
                        </MenuItem>
                    </div>
                    <div class="px-1.5 py-1.5">
                        <MenuItem
                            v-slot="{ active }"
                            v-if="authorization.canGenerateSessionCode"
                        >
                            <button
                                @click="showCreateSessionModal = true"
                                :class="[
                                    active
                                        ? 'bg-card-background-lighter text-dark-white cursor-pointer'
                                        : 'text-white',
                                    'group flex w-full items-center rounded-md px-2 py-2 text-sm',
                                ]"
                            >
                                <KeyIcon
                                    class="mr-2 h-5 w-5 text-white"
                                    aria-hidden="true"
                                />
                                <span class="font-medium align-middle"
                                    >Generate session code</span
                                >
                            </button>
                        </MenuItem>
                        <MenuItem
                            v-slot="{ active }"
                            v-if="
                                authorization.isOwner &&
                                authorization.canCopySessionCode
                            "
                        >
                            <button
                                @click="deleteCurrentCode"
                                :class="[
                                    active
                                        ? 'bg-card-background-lighter text-dark-white cursor-pointer'
                                        : 'text-white',
                                    'group flex w-full items-center rounded-md px-2 py-2 text-sm',
                                ]"
                            >
                                <MinusCircleIcon
                                    class="mr-2 h-5 w-5 text-white"
                                    aria-hidden="true"
                                />
                                <span class="font-medium align-middle"
                                    >Delete current code</span
                                >
                            </button>
                        </MenuItem>
                        <MenuItem
                            v-slot="{ active }"
                            v-if="authorization.canCopySessionCode"
                        >
                            <button
                                @click="copyCodeToClipboard"
                                :class="[
                                    active
                                        ? 'bg-card-background-lighter text-dark-white cursor-pointer'
                                        : 'text-white',
                                    'group flex w-full items-center rounded-md px-2 py-2 text-sm',
                                ]"
                            >
                                <ClipboardDocumentIcon
                                    class="mr-2 h-5 w-5 text-white"
                                    aria-hidden="true"
                                />
                                <span class="font-medium align-middle"
                                    >Copy session code</span
                                >
                            </button>
                        </MenuItem>
                    </div>
                </MenuDropdown>
            </div>
        </div>
    </div>
    <teleport to="body">
        <CreateSessionModal
            :is-visible="showCreateSessionModal"
            @close-modal="showCreateSessionModal = false"
        />
        <UpdateMixModal
            :is-visible="showUpdateMixModal"
            @close-modal="showUpdateMixModal = false"
        />
        <UpdateThemeModal
            :is-visible="showUpdateThemeModal"
            @close-modal="showUpdateThemeModal = false"
        />
        <ImportSpotifyModal
            :is-visible="showImportSpotifyModal"
            :mix="mix"
            @close-modal="showImportSpotifyModal = false"
        />
    </teleport>
</template>

<script setup>
import { MenuItem } from "@headlessui/vue";
import {
    TrashIcon,
    PencilIcon,
    LockClosedIcon,
    KeyIcon,
    ClipboardDocumentIcon,
    MinusCircleIcon,
    LockOpenIcon,
    SparklesIcon,
    ArrowDownOnSquareIcon,
    ArrowLeftEndOnRectangleIcon,
} from "@heroicons/vue/24/outline";

import MenuDropdown from "@/components/menus/MenuDropdown.vue";
import CreateSessionModal from "@/components/modals/sessions/CreateSessionModal.vue";
import UpdateThemeModal from "@/components/modals/mixes/UpdateThemeModal.vue";
import { router, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import toast from "@/stores/StoreToast.js";
import UpdateMixModal from "@/components/modals/mixes/UpdateMixModal.vue";
import { StoreConfirmationModal } from "@/stores/StoreConfirmationModal";
import { XMarkIcon } from "@heroicons/vue/24/solid";
import ImportSpotifyModal from "@/components/modals/mixes/ImportSpotifyModal.vue";

const storeConfirmationModal = StoreConfirmationModal();

const page = usePage();
const props = computed(() => page.props);
const mix = computed(() => props.value.mix);
const songs = computed(() => props.value.songs);
const owner = computed(() => props.value.owner);
const authorization = computed(() => page.props.mix.authorized);
const collaborators = computed(() => page.props.collaborators);

const showCreateSessionModal = ref(false);
const showUpdateMixModal = ref(false);
const showUpdateThemeModal = ref(false);
const showImportSpotifyModal = ref(false);

const mixDuration = computed(() => page.props.mixDuration);

const showMenuDropdown = computed(() => {
    return (
        authorization.value.canUpdate ||
        authorization.value.isOwner ||
        authorization.value.canGenerateSessionCode ||
        authorization.value.isOwner ||
        authorization.value.canCopySessionCode
    );
});

function importFromSpotify() {
    showImportSpotifyModal.value = true;
}

function getImageUrl(song) {
    return song.avatar === null
        ? "/images/default-song.png"
        : "/storage/" + song.avatar;
}

async function deleteMix() {
    const confirmed = await storeConfirmationModal.confirm({
        title: "Remove this mix?",
        text: "This action is permanent and cannot be undone. Are you sure?",
    });
    if (confirmed) {
        router.delete(route("mix.destroy", mix.value.id), {
            onError: (error) => {
                console.error("Error deleting mix:", error);
            },
        });
    }
}
function copyCodeToClipboard() {
    navigator.clipboard.writeText(mix.value.session_code).then(() => {
        toast.add({
            message: `Copied to clipboard!`,
            type: "success",
        });
    });
}

async function deleteCurrentCode() {
    const confirmed = await storeConfirmationModal.confirm({
        title: "Delete this session code?",
        text: "This code will be deleted and will become invalid. Are you sure?",
    });

    if (confirmed) {
        router.post(
            route("mix.remove-session-code", mix.value.id),
            {},
            { preserveScroll: true },
            {
                onFinish: () => {},
                onError: (error) => {
                    console.error("Error deleting session code:", error);
                },
            }
        );
    }
}

async function removeCurrentCoDJ() {
    const confirmed = await storeConfirmationModal.confirm({
        title: "Remove current co-DJ?",
        text: "This will remove the current co-DJ from this mix. Are you sure?",
    });

    if (confirmed) {
        router.post(
            route("mix.remove-co-dj", mix.value.id),
            {},
            { preserveScroll: true },
            {
                onFinish: () => {},
                onError: (error) => {
                    console.error("Error removing co-DJ:", error);
                },
            }
        );
    }
}
async function toggleVisibility() {
    let confirmed = false;

    //if mix is public, then show confirmation modal to indicate that it will be made private
    if (mix.value.is_public === 1) {
        confirmed = await storeConfirmationModal.confirm({
            title: "Make this mix private?",
            text: "Non invited users won't be able to view this mix via the link anymore.",
        });
    } else {
        confirmed = await storeConfirmationModal.confirm({
            title: "Make this mix public?",
            text: "Everyone with access to the link can view this mix.",
        });
    }

    if (confirmed) {
        router.post(
            route("mix.toggle-visibility", mix.value.id),
            {},
            {
                preserveScroll: true,
                onFinish: () => {},
                onError: (error) => {
                    console.error("Error toggling visibility:", error);
                },
            }
        );
    }
}

async function leaveMix() {
    const confirmed = await storeConfirmationModal.confirm({
        title: "Leave mix?",
        text: "You will no longer be a part of this mix and will lose access to it.",
    });

    if (confirmed) {
        router.post(
            route("mix.remove-user-access", mix.value.id),
            {
                user_id: page.props.user.id,
            },
            {
                onError: (error) => {
                    console.error("Error leaving mix:", error);
                },
                onFinish: () => {
                    router.visit(route("app"));
                },
            }
        );
    }
}
</script>
