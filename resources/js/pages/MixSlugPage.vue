<template>
    <Head :title="`Tunofy | ${nameOfMix}`" />
    <AppLayout>
        <TabNav :tabs="tabs" @tab-changed="setActiveTab" />
        <Transition name="fade-with-slide" appear mode="out-in">
            <div :key="activeTab">
                <OverviewSubPage v-if="activeTab === 'Overview'" />
                <VotingComponent v-else-if="activeTab === 'Voting'" />
                <StatsComponent v-else-if="activeTab === 'Stats'" />
            </div>
        </Transition>
    </AppLayout>
</template>

<script setup>
import { ref } from "vue";

import AppLayout from "@/layouts/AppLayout.vue";
import TabNav from "@/components/navigation/TabNav.vue";
import VotingComponent from "@/components/subpages/VotingComponent.vue";
import StatsComponent from "@/components/subpages/StatsComponent.vue";
import { Head, usePage } from "@inertiajs/vue3";
import OverviewSubPage from "@/components/subpages/overview/OverviewSubPage.vue";

const tabs = ref([
    { name: "Overview", active: true },
    { name: "Voting", active: false },
    { name: "Stats", active: false },
]);

const page = usePage();
const nameOfMix = page.props.mix.name;

const activeTab = ref("Overview");

const setActiveTab = (tabName) => {
    activeTab.value = tabName;
    tabs.value.forEach((tab) => {
        tab.active = tab.name === tabName;
    });
    console.log("Active tab changed to:", activeTab.value);
};
</script>
