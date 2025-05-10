<template>
    <Head :title="`Tunofy | ${nameOfMix}`" />
    <AppLayout>
        <TabNav :tabs="tabs" @tab-changed="setActiveTab" />
        <Transition name="fade-with-slide" appear mode="out-in">
            <div
                :key="activeTab"
                class="relative"
                :class="activeTab === 'Overview' ? 'mb-0' : 'mb-32'"
            >
                <OverviewSubPage v-if="activeTab === 'Overview'" />
                <VotingSubPage v-else-if="activeTab === 'Voting'" />
                <StatsSubPage v-else-if="activeTab === 'Stats'" />
                <PresetsSubPage v-else-if="activeTab === 'Presets'" />
                <ManageSubPage v-else-if="activeTab === 'Manage'" />
            </div>
        </Transition>
        <MixController :mix="mix" />
    </AppLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import AppLayout from "@/layouts/AppLayout.vue";
import TabNav from "@/components/navigation/TabNav.vue";
import { Head, usePage } from "@inertiajs/vue3";
import OverviewSubPage from "@/components/subpages/overview/OverviewSubPage.vue";
import PresetsSubPage from "@/components/subpages/presets/PresetsSubPage.vue";
import StatsSubPage from "@/components/subpages/stats/StatsSubPage.vue";
import VotingSubPage from "@/components/subpages/voting/VotingSubPage.vue";
import MixController from "@/components/playback/MixController.vue";
import ManageSubPage from "@/components/subpages/manage/ManageSubPage.vue";

const tabs = ref([
    { name: "Overview", active: true, id: "overview" },
    { name: "Voting", active: false, id: "voting" },
    { name: "Stats", active: false, id: "stats" },
    { name: "Presets", active: false, id: "presets" },
    { name: "Manage", active: false, id: "manage" },
]);

const page = usePage();
const nameOfMix = page.props.mix.name;
const mix = computed(() => {
    return page.props.mix || null;
});

const activeTab = ref("Overview");
const setActiveTab = (tabName) => {
    activeTab.value = tabName;
    tabs.value.forEach((tab) => {
        tab.active = tab.name === tabName;
    });
};
</script>
