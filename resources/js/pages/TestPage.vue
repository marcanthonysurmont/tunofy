<template>
    <AppLayout>
        <TabNav :tabs="tabs" @tab-changed="setActiveTab" />
        <Transition name="fade-with-slide" appear mode="out-in">
            <div :key="activeTab">
                <div v-if="activeTab === 'Overview'">
                    <OverviewComponent />
                    
                    <!-- Only show the playback component on mix detail page -->
                    <MixPlayback v-if="isDetailPage" :mix="mix" />
                </div>
                <VotingComponent v-else-if="activeTab === 'Voting'" />
                <StatsComponent v-else-if="activeTab === 'Stats'" />
            </div>
        </Transition>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

import AppLayout from '@/layouts/AppLayout.vue'
import TabNav from '@/components/navigation/TabNav.vue'
import OverviewComponent from '@/components/subpages/OverviewComponent.vue'
import VotingComponent from '@/components/subpages/VotingComponent.vue'
import StatsComponent from '@/components/subpages/StatsComponent.vue'
import MixPlayback from '@/components/MixPlayback.vue'

// Get the page props from Inertia
const page = usePage()

// Determine if we're on the mix detail page
const isDetailPage = computed(() => {
    return !!page.props.mix
})

// Get the mix from page props if available
const mix = computed(() => {
    return page.props.mix || null
})

const tabs = ref([
    { name: 'Overview', active: true },
    { name: 'Voting', active: false },
    { name: 'Stats', active: false },
])

const activeTab = ref('Overview')

const setActiveTab = (tabName) => {
    activeTab.value = tabName
    tabs.value.forEach(tab => {
        tab.active = tab.name === tabName
    })
    console.log('Active tab changed to:', activeTab.value)
}
</script>