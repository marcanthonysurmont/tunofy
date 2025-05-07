<template>
    <h1 class="text-4xl sm:text-5xl font-medium mb-2">Users</h1>
    <p class="mb-8">Manage the users in your playlist</p>
    <!-- <UsersTable /> -->
    <UsersList />
</template>

<script setup>
import UsersList from "@/components/subpages/manage/UsersList.vue";
// import UsersTable from "@/components/subpages/manage/UsersTable.vue";
// import { ref, onMounted, onBeforeUnmount } from "vue";

// const windowWidth = ref(window.innerWidth);
// function updateWindowWidth() {
//     windowWidth.value = window.innerWidth;
// }

// onMounted(() => {
//     window.addEventListener("resize", updateWindowWidth);
// });

// onBeforeUnmount(() => {
//     window.removeEventListener("resize", updateWindowWidth);
// });
</script>
