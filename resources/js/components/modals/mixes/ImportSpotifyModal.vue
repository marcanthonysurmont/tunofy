<template>
    <CreateModalDefault :is-visible="isVisible" @close-modal="closeModal">
        <template #title>
            <h1 class="text-4xl">Import from Spotify</h1>
        </template>
        <template #body>
            <template v-if="!isProcessing && availablePlaylists.length > 0">
                <label class="block text-sm/6 font-medium text-white mb-3">
                    Available playlists
                </label>
                <div
                    class="mb-6 max-h-[300px] overflow-y-auto custom-scrollbar"
                >
                    <template v-for="playlist in availablePlaylists">
                        <div
                            class="flex flex-row gap-3 items-center justify-between mb-2 mr-4"
                        >
                            <div
                                class="flex flex-row gap-3 items-center truncate flex-grow"
                            >
                                <img
                                    v-lazy="{
                                        src:
                                            playlist.images?.[0]?.url ||
                                            'images/default-song.png',
                                        error: '/images/default-song.png',
                                        loading: '/images/default-song.png',
                                    }"
                                    alt="Playlist Image"
                                    class="size-7 rounded-full"
                                />
                                <span
                                    class="text-sm font-medium text-white truncate"
                                >
                                    {{ playlist.name }}
                                </span>
                            </div>
                            <div
                                @click="togglePlaylistSelection(playlist.id)"
                                class="cursor-pointer flex-shrink-0"
                            >
                                <div
                                    v-if="
                                        selectedPlaylists.includes(playlist.id)
                                    "
                                    class="size-5 rounded-full border border-blue-500 bg-blue-500 flex items-center justify-center"
                                >
                                    <CheckIcon class="size-3 text-white" />
                                </div>
                                <div
                                    v-else
                                    class="size-5 rounded-full border border-zinc-300"
                                ></div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
            <template
                v-else-if="
                    !isFetchingPlaylists && availablePlaylists.length === 0
                "
            >
                <p class="text-white text-md mb-4">No playlists available!</p>
            </template>
            <template v-else>
                <div class="flex flex-col items-center justify-center mb-4">
                    <p class="text-white text-sm">
                        Importing playlists... ({{ processedCount }} of
                        {{ selectedPlaylists.length }})
                    </p>
                    <div class="w-full bg-zinc-700 rounded-full h-2 mt-4">
                        <div
                            class="bg-primary h-2 rounded-full transition-all duration-500 ease-in-out"
                            :style="`width: ${
                                (processedCount / selectedPlaylists.length) *
                                100
                            }%`"
                        ></div>
                    </div>
                </div>
            </template>
        </template>
        <template #footer>
            <RegularButton
                color="blue"
                class="w-full"
                @click="importSelectedPlaylists"
                :disabled="isProcessing || selectedPlaylists.length === 0"
                :loading="isProcessing"
            >
                Import {{ selectedPlaylists.length }} Selected
            </RegularButton>
        </template>
    </CreateModalDefault>
</template>

<script setup>
import CreateModalDefault from "@/components/modals/CreateModalDefault.vue";
import RegularButton from "@/components/buttons/RegularButton.vue";
import { router, useForm } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";
import axios from "axios";
import { CheckIcon } from "@heroicons/vue/24/solid";
import { usePlaylistStore } from "@/stores/StorePlaylistContent.js";
import toast from "@/stores/StoreToast.js";

const playlistStore = usePlaylistStore();

const props = defineProps({
    isVisible: Boolean,
    mix: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    playlist_id: null,
});

const availablePlaylists = ref([]);
const isFetchingPlaylists = ref(false);
const isProcessing = ref(false);
const selectedPlaylists = ref([]);
const processedCount = ref(0);

//emit to close modal
const emits = defineEmits(["closeModal"]);

function togglePlaylistSelection(playlistId) {
    const index = selectedPlaylists.value.indexOf(playlistId);
    if (index === -1) {
        selectedPlaylists.value.push(playlistId);
    } else {
        selectedPlaylists.value.splice(index, 1);
    }
}

async function importSelectedPlaylists() {
    if (selectedPlaylists.value.length === 0) {
        return;
    }

    isProcessing.value = true;
    processedCount.value = 0;

    try {
        for (const playlistId of selectedPlaylists.value) {
            try {
                //wait for promise to be resolved inside importPlaylist
                await importPlaylist(playlistId);
                processedCount.value++;
            } catch (error) {
                console.error(`Error importing playlist ${playlistId}:`, error);
            }
        }
    } catch (error) {
    } finally {
        isProcessing.value = false;
        closeModal();
    }
}

// Remove Inertia form usage, use axios for importPlaylist
async function importPlaylist(playlistID) {
    try {
        const response = await axios.post(
            route("mix.import-spotify-playlist", props.mix.id),
            {
                playlist_id: playlistID,
            }
        );

        if (response.data.success) {
            //add toast
            toast.add({
                message: response.data.message,
                type: "success",
            });

            //add imported songs to the store if there is no pagination pages remaining
            if (playlistStore.nextFetchURL === null) {
                playlistStore.addSongs(response.data.imported_songs);
            }
            router.reload({
                only: [
                    "mixDuration",
                    "mix",
                    "your_mixes",
                    "joined_mixes",
                    "success",
                    "danger",
                ],
            });
        } else {
            toast.add({
                message: "Failed to import playlist.",
                type: "danger",
            });
        }
    } catch (error) {
        console.error(error);
    }
}

function closeModal() {
    //if processing, dont allow closing of modal
    if (isProcessing.value) {
        return;
    }
    emits("closeModal");
    clearForm();
}

function clearForm() {
    form.reset();
    form.clearErrors();
    selectedPlaylists.value = [];
    processedCount.value = 0;
}

onMounted(async () => {
    try {
        //flag
        isFetchingPlaylists.value = true;
        //api call to fetch playlists
        const response = await axios.post(route("api.spotify.get-playlist"));
        availablePlaylists.value = response.data;
    } catch (error) {
        console.log("Error fetching playlists: " + error);
    } finally {
        isFetchingPlaylists.value = false;
    }
});
</script>
