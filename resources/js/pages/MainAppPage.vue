<template>
    <Head title="Tunofy | App" />
    <AppLayout>
        <Transition name="fade-with-slide" appear mode="out-in">
            <NewsSection />
        </Transition>
    </AppLayout>
</template>

<script setup>
import { ref } from "vue";

import AppLayout from "@/layouts/AppLayout.vue";
import { Head } from "@inertiajs/vue3";
import NewsSection from "@/components/main-page/NewsSection.vue";

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
