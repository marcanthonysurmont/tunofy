<template>
    <h1 class="text-4xl sm:text-5xl font-medium mb-6">Users</h1>
    <!-- <p class="mb-6">Manage the users in your playlist.</p> -->
    <SearchBarUsers />
    <UsersList />
    <Pagination :elements="usersProp" />
</template>

<script setup>
import UsersList from "@/components/subpages/manage/UsersList.vue";
import Pagination from "@/components/pagination/Pagination.vue";
import SearchBarUsers from "@/components/subpages/manage/SearchBarUsers.vue";
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const page = usePage();
const usersProp = computed(() => page.props.collaborators);
</script>
