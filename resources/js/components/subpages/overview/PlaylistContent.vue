<template>
    <div>
        <div class="mt-8 flow-root">
            <div class="w-full">
                <table class="w-full text-left table-fixed">
                    <thead>
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
                                class="py-3.5 pl-1 sm:pl-3 text-right w-8 sm:w-10"
                            ></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(song, index) in songs"
                            :key="song.id"
                            class="border-t border-zinc-800"
                        >
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
                                        class="min-w-0 flex-1 max-w-[75%] sm:max-w-xs md: md:max-w-md lg:max-w-lg"
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
                            <td class="py-2 sm:py-4 pl-1 sm:pl-3 text-right">
                                <button
                                    @click="deleteSong(song.id)"
                                    class="p-1 sm:p-2 cursor-pointer"
                                >
                                    <TrashIcon
                                        class="h-4 w-4 sm:h-5 sm:w-5 text-red-500 hover:text-red-700"
                                    />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import { TrashIcon } from "@heroicons/vue/24/outline";

const page = usePage();
const props = computed(() => page.props);
const songs = computed(() => props.value.mix.songs);

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
</script>
