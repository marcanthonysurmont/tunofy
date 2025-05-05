<template>
    <Head :title="`Tunofy | ${nameOfMix}`" />
    <AppLayout>
        <TabNav :tabs="tabs" @tab-changed="setActiveTab" />
        <Transition name="fade-with-slide" appear mode="out-in">
            <div :key="activeTab">
                <OverviewSubPage v-if="activeTab === 'Overview'" />
                <VotingSubPage v-else-if="activeTab === 'Voting'" />
                <StatsSubPage v-else-if="activeTab === 'Stats'" />
                <PresetsSubPage v-else-if="activeTab === 'Presets'" />
            </div>
        </Transition>
    </AppLayout>
</template>

<script setup>
import { ref } from "vue";
import AppLayout from "@/layouts/AppLayout.vue";
import TabNav from "@/components/navigation/TabNav.vue";
import { Head, usePage } from "@inertiajs/vue3";
import OverviewSubPage from "@/components/subpages/overview/OverviewSubPage.vue";
import PresetsSubPage from "@/components/subpages/presets/PresetsSubPage.vue";
import StatsSubPage from "@/components/subpages/stats/StatsSubPage.vue";
import VotingSubPage from "@/components/subpages/voting/VotingSubPage.vue";

const tabs = ref([
    { name: "Overview", active: true },
    { name: "Voting", active: false },
    { name: "Stats", active: false },
    { name: "Presets", active: false },
]);

const page = usePage();
const nameOfMix = page.props.mix.name;

const activeTab = ref("Overview");
const setActiveTab = (tabName) => {
    activeTab.value = tabName;
    tabs.value.forEach((tab) => {
        tab.active = tab.name === tabName;
    });
};
</script>
