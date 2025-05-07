<template>
    <div class="flex flex-col gap-2">
        <div
            class="flex flex-col sm:flex-row gap-4 md:gap-8 w-full items-center relative z-10 py-2 pr-2"
        >
            <img
                :src="getImageUrl(mix)"
                class="size-40 sm:size-64 object-cover flex-shrink-0 rounded-md"
            />

            <div
                class="flex flex-col justify-center items-center sm:items-start gap-3 flex-1 overflow-hidden min-w-0 py-2 text-center sm:text-left"
            >
                <h1
                    class="text-3xl sm:text-4xl md:text-5xl text-white font-normal mt-0 mb-0 leading-none truncate w-full sm:text-nowrap text-wrap"
                >
                    {{ mix.name }}
                </h1>
                <div class="flex flex-col sm:flex-row items-center">
                    <span
                        class="text-dark-white text-base sm:text-lg mt-0 mb-0 leading-none"
                    >
                        <span class="font-bold">{{ owner.name }}</span> •
                        {{ mix.songs.length }}
                        {{ mix.songs.length === 1 ? "song" : "songs" }}, approx.
                        {{ readableTime }}
                    </span>
                </div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 mt-2 sm:mt-0">
            <div
                class="flex flex-row gap-3 justify-center items-center sm:justify-start"
            >
                <UserPlusIcon
                    class="size-7 sm:size-9 text-dark-white cursor-pointer custom-item-hover"
                />
                <UserMinusIcon
                    class="size-7 sm:size-9 text-dark-white cursor-pointer custom-item-hover"
                />
                <ShieldExclamationIcon
                    class="size-7 sm:size-9 text-dark-white cursor-pointer custom-item-hover"
                />
                <Cog8ToothIcon
                    class="size-7 sm:size-9 text-dark-white cursor-pointer custom-item-hover"
                />
                <MenuDropdown v-if="showMenuDropdown">
                    <div class="px-1.5 py-1.5">
                        <MenuItem
                            v-slot="{ active }"
                            v-if="authorization.canUpdate"
                        >
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
                            v-if="authorization.isOwner"
                        >
                            <button
                                @click="showAssignDJModal = true"
                                :class="[
                                    active
                                        ? 'bg-card-background-lighter text-dark-white cursor-pointer'
                                        : 'text-white',
                                    'group flex w-full items-center rounded-md px-2 py-2 text-sm',
                                ]"
                            >
                                <MusicalNoteIcon
                                    :active="active"
                                    class="mr-2 h-5 w-5 text-white"
                                    aria-hidden="true"
                                />
                                <span class="font-medium align-middle"
                                    >Assign a co-dj</span
                                >
                            </button>
                        </MenuItem>
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
                    <div class="px-1.5 py-1.5">
                        <MenuItem
                            v-slot="{ active }"
                            v-if="authorization.isOwner"
                        >
                            <button
                                @click="deleteMix"
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
                    </div>
                    <div class="px-1.5 py-1.5">
                        <MenuItem
                            v-slot="{ active }"
                            v-if="authorization.isOwner"
                        >
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
                                mix.session_code !== null
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
                            v-if="
                                authorization.isOwner &&
                                authorization.canCopySessionCode
                            "
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
        <AssignDJModal
            :is-visible="showAssignDJModal"
            @close-modal="showAssignDJModal = false"
        />
    </teleport>
</template>

<script setup>
import { MenuItem } from "@headlessui/vue";
import {
    Cog8ToothIcon,
    TrashIcon,
    UserMinusIcon,
    UserPlusIcon,
    PencilIcon,
    LockClosedIcon,
    KeyIcon,
    ClipboardDocumentIcon,
    MinusCircleIcon,
    MusicalNoteIcon,
    ShieldCheckIcon,
    ShieldExclamationIcon,
} from "@heroicons/vue/24/outline";

import MenuDropdown from "@/components/menus/MenuDropdown.vue";
import CreateSessionModal from "@/components/modals/sessions/CreateSessionModal.vue";
import { router, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import toast from "@/stores/StoreToast.js";
import UpdateMixModal from "@/components/modals/mixes/UpdateMixModal.vue";
import { StoreConfirmationModal } from "@/stores/StoreConfirmationModal";
import AssignDJModal from "@/components/modals/co-dj/AssignDJModal.vue";
import { XMarkIcon } from "@heroicons/vue/24/solid";

const storeConfirmationModal = StoreConfirmationModal();

const page = usePage();
const props = computed(() => page.props);
const mix = computed(() => props.value.mix);
const owner = computed(() => props.value.owner);
const authorization = computed(() => page.props.mix.authorized);
const collaborators = computed(() => page.props.collaborators);

const showCreateSessionModal = ref(false);
const showUpdateMixModal = ref(false);
const showAssignDJModal = ref(false);

const readableTime = computed(() => {
    const totalMs = mix.value.songs.reduce(
        (sum, song) => sum + song.duration_ms,
        0
    );
    const hours = Math.floor(totalMs / 3600000);
    const minutes = Math.floor((totalMs % 3600000) / 60000);
    return `${hours > 0 ? hours + "h " : ""}${minutes}min`;
});

const showMenuDropdown = computed(() => {
    return (
        authorization.value.canUpdate ||
        authorization.value.isOwner ||
        authorization.value.canGenerateSessionCode ||
        (authorization.value.isOwner && authorization.value.canCopySessionCode)
    );
});

function getImageUrl(song) {
    return song.avatar === null
        ? "/images/default-avatar.jpg"
        : "/storage/" + song.avatar;
}

function deleteMix() {
    router.delete(route("mix.destroy", mix.value.id), {
        onError: (error) => {
            console.error("Error deleting mix:", error);
        },
        onFinish: () => {},
    });
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
</script>
