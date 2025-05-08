<template>
    <h1 class="text-4xl sm:text-5xl font-medium mb-2">Users</h1>
    <p class="mb-8">Manage the users in your playlist.</p>
    <SearchBarUsers />
    <UsersList />
    <Pagination :elements="mockElements" />
</template>

<script setup>
import UsersList from "@/components/subpages/manage/UsersList.vue";
import Pagination from "@/components/pagination/Pagination.vue";
import SearchBarUsers from "@/components/subpages/manage/SearchBarUsers.vue";

const mockElements = {
    data: [
        /* some items here just to pass the length check */
        { id: 1 },
        { id: 2 },
    ],
    links: [
        {
            url: null,
            label: "&laquo; Previous",
            active: false,
        },
        {
            url: "http://localhost?page=1",
            label: "1",
            active: true,
        },
        {
            url: "http://localhost?page=2",
            label: "2",
            active: false,
        },
        {
            url: "http://localhost?page=3",
            label: "3",
            active: false,
        },
        {
            url: "http://localhost?page=2",
            label: "Next &raquo;",
            active: false,
        },
    ],
};

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
