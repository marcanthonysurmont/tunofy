<template>
    <div>
        <Combobox v-model="query">
            <div
                class="relative"
                @focusin="isFocused = true"
                @focusout="isFocused = false"
            >
                <div
                    :class="[
                        'flex items-center w-full bg-card-background border border-zinc-800 rounded-xl transition-all duration-300 overflow-hidden relative',
                        isFocused ? 'shadow-md ring-1 ring-zinc-600' : '',
                    ]"
                    class=""
                >
                    <MagnifyingGlassIcon
                        class="size-6 ml-4 transition-all duration-300"
                        :class="isFocused ? 'text-dark-white' : 'text-zinc-400'"
                        aria-hidden="true"
                    />
                    <ComboboxInput
                        class="w-full bg-transparent text-dark-white placeholder-zinc-400 pl-4 pr-12 py-2 focus:outline-none"
                        @input="handleSearch"
                        placeholder="Search songs..."
                        autocomplete="off"
                        autocorrect="off"
                        autocapitalize="off"
                        spellcheck="false"
                    />
                    <div v-if="isLoading" class="mr-4 absolute right-0">
                        <svg
                            class="animate-spin h-5 w-5 text-zinc-400"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                    </div>
                </div>
                <!-- Search results -->
                <TransitionRoot
                    enter="transition ease-out duration-100"
                    enterFrom="opacity-0 translate-y-1"
                    enterTo="opacity-100 translate-y-0"
                    leave="transition ease-in duration-75"
                    leaveFrom="opacity-100 translate-y-0"
                    leaveTo="opacity-0 translate-y-1"
                >
                    <ComboboxOptions
                        v-if="filteredSongs.length > 0 && isFocused"
                        class="absolute z-50 mt-2 w-full bg-card-background rounded-md border border-card-stroke max-h-82 min-w-64 overflow-y-auto overflow-x-hidden shadow-lg pb-2 custom-scrollbar"
                    >
                        <ComboboxOption
                            v-for="song in filteredSongs"
                            :key="song.id"
                            :value="song"
                            as="template"
                        >
                            <li
                                class="group select-none px-4 py-2 flex items-center justify-between hover:bg-card-background-hover transition duration-200 overflow-hidden"
                                @mousedown.prevent
                            >
                                <div
                                    class="flex items-center space-x-3 flex-1 min-w-0 overflow-hidden"
                                >
                                    <img
                                        :src="song.album.images[0].url"
                                        class="w-10 h-10 rounded-md object-cover flex-shrink-0"
                                    />
                                    <div class="min-w-0 flex-1 overflow-hidden">
                                        <div
                                            class="text-dark-white text-sm font-medium truncate"
                                            :title="song.name"
                                        >
                                            {{ song.name }}
                                        </div>
                                        <div
                                            class="text-zinc-400 text-xs truncate"
                                            :title="
                                                song.artists
                                                    .map(
                                                        (artist) => artist.name
                                                    )
                                                    .join(', ')
                                            "
                                        >
                                            {{
                                                song.artists
                                                    .map(
                                                        (artist) => artist.name
                                                    )
                                                    .join(", ")
                                            }}
                                        </div>
                                    </div>
                                </div>
                                <div class="ml-2 flex-shrink-0">
                                    <button
                                        class="lg:opacity-0 lg:group-hover:opacity-100 opacity-100 p-1 rounded-full transition-all duration-200"
                                        :class="
                                            isSongAdded(song)
                                                ? 'text-green-500 hover:text-green-400 hover:bg-zinc-700/30'
                                                : 'text-zinc-400 hover:text-dark-white hover:bg-zinc-700/30'
                                        "
                                        @mousedown.prevent
                                        @click.prevent.stop="
                                            addToPlaylist(song)
                                        "
                                        type="button"
                                    >
                                        <CheckIcon
                                            v-if="isSongAdded(song)"
                                            class="size-4 cursor-default"
                                            @mousedown.prevent
                                            @click.prevent.stop
                                        />
                                        <PlusIcon
                                            v-else
                                            class="size-4 cursor-pointer"
                                        />
                                    </button>
                                </div>
                            </li>
                        </ComboboxOption>
                    </ComboboxOptions>
                </TransitionRoot>
            </div>
        </Combobox>
    </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import axios from "axios";
import { debounce } from "lodash";

import {
    Combobox,
    ComboboxInput,
    ComboboxOptions,
    ComboboxOption,
    TransitionRoot,
} from "@headlessui/vue";
import { MagnifyingGlassIcon } from "@heroicons/vue/24/solid";
import { PlusIcon, CheckIcon } from "@heroicons/vue/24/outline";
import { useForm, usePage } from "@inertiajs/vue3";

const page = usePage();
const props = page.props;
const mix = props.mix;

const query = ref("");
const isFocused = ref(false);
const isLoading = ref(false);
const songs = ref([]);
const addedSongs = ref(new Set());
const form = useForm({
    spotify_id: "",
    duration_ms: 0,
    name: "",
    artist: "",
    image_url: "",
});

const searchCancelled = ref(false);

//this watch is used to check if the search input field is focused or not
//if it is not focused, we set the searchCancelled to true in order to cancel the search == performance improvement
watch(
    () => isFocused.value,
    (newValue) => {
        if (!newValue) {
            searchCancelled.value = true;
            query.value = "";
            songs.value = [];
        } else {
            searchCancelled.value = false;
        }
    }
);

async function searchSongs(searchQuery) {
    if (searchCancelled.value || !searchQuery.trim()) {
        isLoading.value = false;
        return;
    }
    try {
        const response = await axios.post(route("api.spotify.search"), {
            query: searchQuery,
        });
        songs.value = response.data.songs;
    } catch (error) {
        console.error("Error searching songs:", error);
    } finally {
        isLoading.value = false;
    }
}

//this is runs the searchSongs function with debounce of 300ms
//added to improve performance and avoid too many requests
const debouncedSearch = debounce(searchSongs, 300);

//input handler
function handleSearch(event) {
    //map the event value to the query (input value is a song name like 'Dark Thoughts')
    query.value = event.target.value;
    //show loading spinner
    isLoading.value = true;
    debouncedSearch(query.value);
}

const filteredSongs = computed(() => {
    if (query.value === "") {
        return songs.value;
    }
    return songs.value;
});

function addToPlaylist(song) {
    //if song is already added, return early
    if (addedSongs.value.has(song.id)) {
        return;
    }
    form.spotify_id = song.id;
    form.duration_ms = song.duration_ms;
    form.name = song.name;
    form.artist = song.artists.map((artist) => artist.name).join(", ");
    form.image_url = song.album.images[0].url;

    form.post(
        route("mix.add-song", mix.id),
        {
            preserveScroll: true,
            only: ["songs", "success", "danger"],
        },
        {
            onSuccess: () => {
                //add the song ID to the addedSongs set
                //we do this because we want to track whichs songs have been added to avoid duplicate
                //currently, this resets on reload but i will add a check to make sure it still shows a "checkmark" icon on songs that are in playlist
                addedSongs.value.add(song.id);
            },
            onError: (error) => {
                console.error("Error adding song:", error);
            },
        }
    );
}

//this function checks if a song has been added
function isSongAdded(song) {
    return addedSongs.value.has(song.id);
}
</script>
