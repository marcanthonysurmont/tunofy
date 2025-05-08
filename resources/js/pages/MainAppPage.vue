<template>
    <Head title="Tunofy | App" />
    <AppLayout>
        <div class="w-full h-full flex justify-center items-center">
            <Transition name="fade-with-slide" appear mode="out-in">
                <h1 class="text-5xl">Empty for now.</h1>
            </Transition>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from "vue";

import AppLayout from "@/layouts/AppLayout.vue";
import { Head } from "@inertiajs/vue3";

const tabs = ref([
    { name: "Overview", active: true },
    { name: "Voting", active: false },
    { name: "Stats", active: false },
]);

const activeTab = ref("Overview");

const setActiveTab = (tabName) => {
    activeTab.value = tabName;
    tabs.value.forEach((tab) => {
        tab.active = tab.name === tabName;
    });
    console.log("Active tab changed to:", activeTab.value);
};
</script>
