<template>
    <div class="max-w-md mb-2" v-if="users.length > 0">
        <div
            class="relative mb-4"
            @focusin="isFocused = true"
            @focusout.stop="isFocused = false"
        >
            <div
                :class="[
                    'flex items-center w-full bg-zinc-900 border border-zinc-800 rounded-xl transition-all duration-200 overflow-hidden',
                    isFocused ? 'shadow-md ring-1 ring-zinc-600' : '',
                ]"
            >
                <MagnifyingGlassIcon
                    class="size-6 ml-4 transition-all duration-300"
                    :class="isFocused ? 'text-white' : 'text-zinc-400'"
                    aria-hidden="true"
                />
                <input
                    ref="searchInput"
                    v-model="query"
                    type="text"
                    class="w-full bg-transparent text-white placeholder-zinc-400 pl-4 pr-12 py-2 focus:outline-none"
                    @input="handleSearch"
                    placeholder="Search users.."
                    autocomplete="off"
                    autocorrect="off"
                    autocapitalize="off"
                    spellcheck="false"
                />
                <XMarkIcon
                    v-if="!isLoading && query.length > 0"
                    @click.stop="resetQuery"
                    class="size-6 absolute right-4 transition-all duration-200 cursor-pointer text-zinc-400 hover:text-white"
                />
                <div v-else-if="isLoading" class="mr-4 absolute right-0">
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
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { MagnifyingGlassIcon, XMarkIcon } from "@heroicons/vue/24/solid";
import { usePage } from "@inertiajs/vue3";
import { debounce } from "lodash";

const page = usePage();
const props = page.props;
const users = computed(() => page.props.collaborators.data);

const isFocused = ref(false);
const query = ref(props.query || "");
const isLoading = ref(false);
const searchInput = ref(null);

const emit = defineEmits(["search-updated", "is-searching", "clear-search"]);

watch(
    query,
    (newValue) => {
        if (newValue.length > 0) {
            emit("is-searching", true);
            return;
        }
        emit("is-searching", false);
    },
    { immediate: true }
);

function handleSearch(event) {
    //map the event to the query variable
    query.value = event.target.value;
    isLoading.value = true;
    performSearch(query.value);
}

const performSearch = debounce(searchUsers, 300);

async function searchUsers() {
    if (!query.value) {
        isLoading.value = false;
        return;
    }
    isLoading.value = true;
    try {
        const response = await axios.get(
            `/mix/search-user/${page.props.mix.id}`,
            {
                params: { q: query.value },
            }
        );
        console.log("Search results:", response.data.data);
        emit("search-updated", response.data.data);
    } catch (error) {
        console.error("Search failed:", error);
    } finally {
        isLoading.value = false;
    }
}

function resetQuery() {
    query.value = "";
    isLoading.value = false;
    //focus again when user clears input because it automatically unfocuses
    searchInput.value?.focus();
    emit("clear-search");
}
</script>
