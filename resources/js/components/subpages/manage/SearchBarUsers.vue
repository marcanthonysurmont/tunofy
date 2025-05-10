<template>
    <div class="max-w-lg mb-2">
        <div
            class="relative mb-4"
            @focusin="isFocused = true"
            @focusout="isFocused = false"
        >
            <div
                :class="[
                    'flex items-center w-full bg-zinc-900 border border-zinc-800 rounded-xl transition-all duration-300 overflow-hidden',
                    isFocused ? 'shadow-md ring-1 ring-zinc-600' : '',
                ]"
            >
                <MagnifyingGlassIcon
                    class="size-6 ml-4 transition-all duration-300"
                    :class="isFocused ? 'text-white' : 'text-zinc-400'"
                    aria-hidden="true"
                />
                <input
                    type="text"
                    class="w-full bg-transparent text-white placeholder-zinc-400 pl-4 pr-12 py-2 focus:outline-none"
                    v-model="query"
                    placeholder="Search users..."
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
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from "vue";
import { MagnifyingGlassIcon } from "@heroicons/vue/24/solid";
import { usePage } from "@inertiajs/vue3";
import { debounce } from "lodash";

const page = usePage();
const props = page.props;

const isFocused = ref(false);
const query = ref(props.query || "");
const isLoading = ref(false);

const performSearch = debounce(searchUsers, 300);

async function searchUsers() {
    if (!query.value) {
        // Optional: handle empty query state
        return;
    }

    isLoading.value = true;

    try {
        const response = await axios.get(`/mix/search-user/${page.props.mix.id}`, {
            params: { q: query.value },
        });

        console.log("Search results:", response.data);

    } catch (error) {
        console.error("Search failed:", error);
    } finally {
        isLoading.value = false;
    }
}


watch(query, (newQuery) => {
    performSearch(newQuery);
});
</script>
