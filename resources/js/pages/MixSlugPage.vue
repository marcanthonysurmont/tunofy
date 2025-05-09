<template>
    <Head :title="`Tunofy | ${nameOfMix}`" />
    <AppLayout>
        <TabNav :tabs="tabs" @tab-changed="setActiveTab" />
        <Transition name="fade-with-slide" appear mode="out-in" :duration="300">
            <div
                :key="localActiveTab"
                class="relative"
                :class="localActiveTab === 'Overview' ? 'mb-0' : 'mb-32'"
            >
                <OverviewSubPage v-if="localActiveTab === 'Overview'" />
                <VotingSubPage v-else-if="localActiveTab === 'Voting'" />
                <StatsSubPage v-else-if="localActiveTab === 'Stats'" />
                <PresetsSubPage v-else-if="localActiveTab === 'Presets'" />
                <ManageSubPage v-else-if="localActiveTab === 'Manage'" />
            </div>
        </Transition>
        <MixPlayback :mix="mix" />
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import AppLayout from "@/layouts/AppLayout.vue";
import TabNav from "@/components/navigation/TabNav.vue";
import { Head, usePage } from "@inertiajs/vue3";
import OverviewSubPage from "@/components/subpages/overview/OverviewSubPage.vue";
import PresetsSubPage from "@/components/subpages/presets/PresetsSubPage.vue";
import StatsSubPage from "@/components/subpages/stats/StatsSubPage.vue";
import VotingSubPage from "@/components/subpages/voting/VotingSubPage.vue";
import MixPlayback from "@/components/playback/MixPlayback.vue";
import ManageSubPage from "@/components/subpages/manage/ManageSubPage.vue";

const tabs = ref([
    { name: "Overview", active: true, id: "overview" },
    { name: "Voting", active: false, id: "voting" },
    { name: "Stats", active: false, id: "stats" },
    { name: "Presets", active: false, id: "presets" },
    { name: "Manage", active: false, id: "manage" },
]);

const page = usePage();
const nameOfMix = computed(() => page.props.mix?.name || 'Mix');
const mix = computed(() => page.props.mix || null);

// Track currently active tab in local state first
const localActiveTab = ref("Overview");

// Initialize based on URL or props
onMounted(() => {
    const tabFromProps = page.props.activeTab;
    if (tabFromProps) {
        localActiveTab.value = tabFromProps.charAt(0).toUpperCase() + tabFromProps.slice(1);
    }
    
    tabs.value.forEach((tab) => {
        tab.active = tab.name.toLowerCase() === localActiveTab.value.toLowerCase();
    });
});

// Add a variable to track if we're currently in a transition
const isTransitioning = ref(false);

const setActiveTab = (tabName) => {
    // Don't do anything if we're already on this tab or transitioning
    if (localActiveTab.value.toLowerCase() === tabName.toLowerCase() || isTransitioning.value) {
        return;
    }
    
    // Set the flag to prevent multiple rapid transitions
    isTransitioning.value = true;
    
    // Update local state immediately to trigger the UI change
    localActiveTab.value = tabName;
    
    // Update tab state for visual feedback in the tab bar
    tabs.value.forEach((tab) => {
        tab.active = tab.name === tabName;
    });
    
    // Use browser's History API to update URL without page reload
    if (mix.value?.slug) {
        const newUrl = `/${mix.value.slug}/${tabName.toLowerCase()}`;
        // Update URL without triggering a page reload
        window.history.pushState(
            { tab: tabName.toLowerCase() }, 
            '', 
            newUrl
        );
    }
    
    // Reset transition flag after animation completes
    setTimeout(() => {
        isTransitioning.value = false;
    }, 300);
};

// Handle browser back/forward buttons
window.addEventListener('popstate', (event) => {
    const urlParts = window.location.pathname.split('/');
    const tabFromUrl = urlParts[urlParts.length - 1];
    
    if (tabFromUrl) {
        const tabName = tabFromUrl.charAt(0).toUpperCase() + tabFromUrl.slice(1);
        localActiveTab.value = tabName;
        
        tabs.value.forEach((tab) => {
            tab.active = tab.name.toLowerCase() === tabName.toLowerCase();
        });
    }
});
</script>
