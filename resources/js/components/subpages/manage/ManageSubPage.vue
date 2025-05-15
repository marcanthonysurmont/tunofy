<template>
    <h1 class="text-4xl sm:text-5xl font-medium mb-6">Users</h1>
    <!-- <p class="mb-6">Manage the users in your playlist.</p> -->
    <SearchBarUsers
        @clear-search="searchResult = {}"
        @search-updated="searchResult = $event"
        @is-searching="isSearching = $event"
    />
    <UsersList :search-result="searchResult" :is-searching="isSearching" />
    <Pagination :elements="usersProp.meta" v-if="!isSearching" />
    <p
        class="text-sm text-zinc-300 border-t border-zinc-800 py-3"
        v-if="
            usersProp.meta.total <= usersProp.meta.per_page &&
            !isSearching &&
            usersProp.length > 0
        "
    >
        Showing all results
    </p>
</template>

<script setup>
import UsersList from "@/components/subpages/manage/UsersList.vue";
import Pagination from "@/components/pagination/Pagination.vue";
import SearchBarUsers from "@/components/subpages/manage/SearchBarUsers.vue";
import { usePage } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";

const page = usePage();
const usersProp = computed(() => page.props.collaborators);
const searchResult = ref({});
const isSearching = ref(false);
watch(
    searchResult,
    (newValue) => {
        console.log("Search result updated:", newValue);
    },
    { immediate: true }
);

watch(
    isSearching,
    (newValue) => {
        console.log(newValue);
    },
    { immediate: true }
);
</script>
