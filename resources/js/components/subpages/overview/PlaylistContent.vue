<template>
    <div>
        <div class="mt-8 flow-root mb-32">
            <div class="w-full">
                <table class="w-full text-left table-fixed">
                    <thead class="border-b border-zinc-800">
                        <tr>
                            <th
                                class="py-3.5 pr-1 sm:pr-3 text-left text-sm font-semibold text-zinc-200 w-6 sm:w-10"
                            >
                                #
                            </th>
                            <th
                                class="px-1 sm:px-3 py-3.5 text-left text-sm font-semibold text-zinc-200"
                            >
                                Song
                            </th>
                            <th
                                class="px-1 sm:px-3 py-3.5 text-left text-sm font-semibold text-zinc-200 w-14 sm:w-20"
                            >
                                Time
                            </th>
                            <th
                                v-if="windowWidth >= 640"
                                class="px-1 sm:px-3 py-3.5 text-right text-sm font-semibold text-zinc-200 w-24"
                            >
                                Added by
                            </th>
                            <th
                                v-if="windowWidth < 640"
                                class="px-1 sm:px-3 py-3.5 text-left text-sm font-semibold text-zinc-200 w-12 sm:w-16"
                            >
                                By
                            </th>
                            <th
                                v-if="authorization.canRemoveSong"
                                class="px-1 sm:px-3 py-3.5 text-right text-sm font-semibold text-zinc-200 w-12 sm:w-16"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody v-if="songs.length > 0">
                        <tr v-for="(song, index) in songs" :key="song.id">
                            <td
                                class="py-2 sm:py-4 pr-1 sm:pr-3 text-sm text-zinc-100"
                            >
                                {{ index + 1 }}
                            </td>
                            <td
                                class="px-1 sm:px-3 py-2 sm:py-4 text-sm text-zinc-100"
                            >
                                <div class="flex items-center gap-2 sm:gap-3">
                                    <img
                                        :src="song.image_url"
                                        alt="cover"
                                        class="w-8 h-8 sm:w-10 sm:h-10 rounded flex-shrink-0"
                                    />
                                    <div
                                        class="min-w-0 flex-1 max-w-[75%] sm:max-w-xs md:max-w-md lg:max-w-lg"
                                    >
                                        <div
                                            class="font-medium truncate text-sm"
                                        >
                                            {{ song.name }}
                                        </div>
                                        <div
                                            class="text-zinc-400 text-xs sm:text-sm truncate"
                                        >
                                            {{ song.artist }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td
                                class="px-1 sm:px-3 py-2 sm:py-4 text-sm text-zinc-400"
                            >
                                {{ msToMinutes(song.duration_ms) }}
                            </td>
                            <td
                                class="px-1 sm:px-3 py-2 sm:py-4 text-sm text-zinc-400"
                            >
                                <div
                                    class="flex items-center"
                                    :class="
                                        windowWidth < 640
                                            ? 'justify-start'
                                            : 'justify-end'
                                    "
                                >
                                    <img
                                        v-tippy="{ content: song.user.name }"
                                        :src="
                                            song.user.avatar === null
                                                ? '/images/default-avatar.jpg'
                                                : song.user.avatar
                                        "
                                        alt="cover"
                                        class="size-6 sm:size-7 rounded-full flex-shrink-0"
                                    />
                                </div>
                            </td>

                            <td
                                class="py-2 sm:py-4 pl-1 sm:pl-3 text-right"
                                v-if="authorization.canRemoveSong"
                            >
                                <div class="flex items-center justify-end">
                                    <button
                                        v-tippy="{ content: 'Delete song' }"
                                        @click="deleteSong(song.id)"
                                        class="pl-1 sm:px-2 cursor-pointer"
                                    >
                                        <TrashIcon
                                            class="size-5 sm:size-6 text-zinc-400 hover:text-zinc-500"
                                        />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="!songs.length > 0" class="mb-32 mt-8">
                    <p
                        class="text-white text-left text-base"
                        v-if="authorization.canAddSong"
                    >
                        No songs found. Search for a song and add it!
                    </p>
                    <p v-else class="text-white text-center sm:text-left">
                        No songs found..
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import { TrashIcon } from "@heroicons/vue/24/outline";

const page = usePage();
const props = computed(() => page.props);
const songs = computed(() => props.value.mix.songs);
const authorization = computed(() => page.props.mix.authorized);

const windowWidth = ref(window.innerWidth);

function getImageUrl(song) {
    return song.avatar === null
        ? "/images/default-avatar.jpg"
        : "/storage/" + song.avatar;
}

function msToMinutes(ms) {
    let minutes = Math.floor(ms / 60000);
    let seconds = Math.floor((ms % 60000) / 1000);

    seconds = seconds < 10 ? "0" + seconds : seconds;

    return `${minutes}:${seconds}`;
}

function deleteSong(id) {
    router.delete(
        route("mix.remove-song", id),
        { preserveScroll: true },
        {
            onSuccess: () => {
                // Optionally, you can show a success message or perform any other action
            },
            onError: (error) => {
                // Handle error if needed
                console.error("Error deleting song:", error);
            },
        }
    );
}

function updateWindowWidth() {
    windowWidth.value = window.innerWidth;
}

onMounted(() => {
    window.addEventListener("resize", updateWindowWidth);
});

onBeforeUnmount(() => {
    window.removeEventListener("resize", updateWindowWidth);
});
</script>
